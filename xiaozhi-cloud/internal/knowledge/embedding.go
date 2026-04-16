package knowledge

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"github.com/sashabaranov/go-openai"
	"go.uber.org/zap"
)

// newMilvusClient 创建 Milvus 客户端（简化版，使用 HTTP REST API）
func newMilvusClient(cfg *config.MilvusConfig) (MilvusClient, error) {
	return &milvusHTTPClient{
		baseURL: fmt.Sprintf("http://%s:%d", cfg.Host, cfg.RestPort),
		timeout: time.Duration(cfg.Timeout) * time.Second,
		dimension: cfg.Dimension,
	}, nil
}

// milvusHTTPClient 基于 HTTP REST API 的 Milvus 客户端
type milvusHTTPClient struct {
 baseURL   string
 timeout   time.Duration
 dimension int
}

func (c *milvusHTTPClient) Search(ctx context.Context, collectionName string, vector []float32, topK int) ([]SearchResult, error) {
	url := c.baseURL + "/collections/" + collectionName + "/search"

	body := map[string]interface{}{
		"vectors":     [][]float32{vector},
		"top_k":       topK,
		"output_fields": []string{"content", "source_doc", "chunk_idx"},
		"params":      map[string]int{"nprobe": 16},
	}

	resp, err := c.doRequest(ctx, http.MethodPost, url, body)
	if err != nil {
		return nil, err
	}

	var result struct {
		Data []struct {
			Score  float64                 `json:"score"`
			Fields map[string]interface{} `json:"fields"`
		} `json:"data"`
	}

	if err := json.Unmarshal(resp, &result); err != nil {
		return nil, fmt.Errorf("解析搜索结果失败: %w", err)
	}

	var results []SearchResult
	for _, item := range result.Data {
		sr := SearchResult{
			Score:  item.Score,
			Fields: item.Fields,
		}
		if content, ok := item.Fields["content"].(string); ok { sr.Content = content }
		if src, ok := item.Fields["source_doc"].(string); ok { sr.SourceDoc = src }
		if idx, ok := item.Fields["chunk_idx"].(float64); ok { sr.ChunkIdx = int(idx) }
		results = append(results, sr)
	}
	return results, nil
}

func (c *milvusHTTPClient) Insert(ctx context.Context, collectionName string, vectors [][]float32, texts []string) error {
	data := make([]map[string]interface{}, len(vectors))
	for i, v := range vectors {
		rowData := map[string]interface{}{
			"vector": v,
			"content": texts[i],
		}
		data[i] = rowData
	}

	body := map[string]interface{}{
		"data": data,
	}

	url := c.baseURL + "/collections/" + collectionName + "/insert"
	_, err := c.doRequest(ctx, http.MethodPost, url, body)
	return err
}

func (c *milvusHTTPClient) DropCollection(ctx context.Context, collectionName string) error {
	url := c.baseURL + "/collections/" + collectionName
	_, err := c.doRequest(ctx, http.MethodDelete, url, nil)
	return err
}

func (c *milvusHTTPClient) CreateCollectionIfNotExists(ctx context.Context, name string, dim int) error {
	// 先检查是否存在
	url := c.baseURL + "/collections/" + name
	resp, err := c.doRequest(ctx, http.MethodGet, url, nil)
	if err == nil && resp != nil {
		return nil // 已存在
	}

	// 创建集合
	createBody := map[string]interface{}{
		"collection_name": name,
		"dimension":       dim,
		"metric_type":     "COSINE",
	}

	createURL := c.baseURL + "/collections"
	_, err = c.doRequest(ctx, http.MethodPost, createURL, createBody)
	return err
}

func (c *milvusHTTPClient) doRequest(ctx context.Context, method, url string, body interface{}) ([]byte, error) {
	var reqBody io.Reader
	if body != nil {
		jsonBody, err := json.Marshal(body)
		if err != nil { return nil, err }
		reqBody = strings.NewReader(string(jsonBody))
	}

	req, err := http.NewRequestWithContext(ctx, method, url, reqBody)
	if err != nil { return nil, err }

	req.Header.Set("Content-Type", "application/json")

	client := &http.Client{Timeout: c.timeout}
	resp, err := client.Do(req)
	if err != nil { return nil, err }
	defer resp.Body.Close()

	result, err := io.ReadAll(resp.Body)
	if err != nil { return nil, err }

	if resp.StatusCode >= 400 {
		return nil, fmt.Errorf("Milvus API 错误 %d: %s", resp.StatusCode, string(result))
	}
	return result, nil
}

// ==================== Embedding Service ====================

// newEmbeddingService 创建嵌入服务（使用 OpenAI 兼容接口）
func newEmbeddingService(cfg *config.LLMConfig) (EmbeddingService, error) {
	defaultProv := cfg.Providers[cfg.DefaultProvider]
	if defaultProv.APIKey == "" {
		return nil, fmt.Errorf("默认 Provider API Key 未配置")
	}

	openaiCfg := openai.DefaultConfig(defaultProv.APIKey)
	if defaultProv.BaseURL != "" {
		openaiCfg.BaseURL = defaultProv.BaseURL
	}

	client := openai.NewClientWithConfig(openaiCfg)
	return &openAIEmbeddingService{
		client: client,
		model:  "text-embedding-ada-002",
	}, nil
}

// openAIEmbeddingService OpenAI 兼容的嵌入服务
type openAIEmbeddingService struct {
	client *openai.Client
	model  string
}

func (s *openAIEmbeddingService) Embed(ctx context.Context, text string) ([]float32, error) {
	resp, err := s.client.CreateEmbeddings(ctx, openai.EmbeddingRequest{
		Input: []string{text},
		Model: s.model,
	})
	if err != nil { return nil, err }

	if len(resp.Data) == 0 {
		return nil, fmt.Errorf("空响应")
	}
	return resp.Data[0].Embedding, nil
}

func (s *openAIEmbeddingService) BatchEmbed(ctx context.Context, texts []string) ([][]float32, error) {
	if len(texts) == 0 { return [][]float32{}, nil }

	// 分批处理（OpenAI 限制单次最多 2048 条）
	batchSize := 512
	var allVectors [][]float32

	for i := 0; i < len(texts); i += batchSize {
		end := i + batchSize
		if end > len(texts) { end = len(texts) }

		batch := texts[i:end]
		resp, err := s.client.CreateEmbeddings(ctx, openai.EmbeddingRequest{
			Input: batch,
			Model: s.model,
		})
		if err != nil { return nil, fmt.Errorf("批次 %d 嵌入失败: %w", i/batchSize, err) }

		for _, d := range resp.Data {
			allVectors = append(allVectors, d.Embedding)
		}
	}

	return allVectors, nil
}

// ==================== 文档处理工具 ====================

// ParseFile 解析文件内容为纯文本
func ParseFile(filePath string) (string, error) {
	ext := strings.ToLower(filepath.Ext(filePath))

	switch ext {
	case ".txt":
		data, err := os.ReadFile(filePath)
		if err != nil { return "", err }
		return string(data), nil
	case ".md":
		data, err := os.ReadFile(filePath)
		if err != nil { return "", err }
		return string(data), nil
	case ".pdf":
		// TODO: 集成 PDF 解析库 (go-unipdf 或 pdfcpu)
		return "", fmt.Errorf("PDF 解析待实现")
	case ".docx":
		// TODO: 集成 DOCX 解析库
		return "", fmt.Errorf("DOCX 解析待实现")
	default:
		data, err := os.ReadFile(filePath)
		if err != nil { return "", err }
		return string(data), nil
	}
}
