package knowledge

import (
	"context"
	"fmt"
	"strings"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"github.com/ai-eivie/xiaozhi-cloud/internal/store"
	"go.uber.org/zap"
	"gorm.io/gorm"
)

// RAG 知识库检索增强生成引擎
type RAG struct {
	cfg    *config.Config
	db     *gorm.DB
	client *MilvusClient
	embed  EmbeddingService
}

// NewRAG 创建 RAG 引擎
func NewRAG(cfg *config.Config, db *gorm.DB) (*RAG, error) {
	var mc *MilvusClient
	var es EmbeddingService

	// 初始化 Milvus 客户端
	milvusClient, err := newMilvusClient(cfg)
	if err != nil {
		logger.Warn("Milvus 连接失败，知识库功能不可用", zap.Error(err))
	} else {
		mc = &milvusClient
	}

	// 初始化 Embedding 服务
	embeddingSvc, err := newEmbeddingService(cfg)
	if err != nil {
		logger.Warn("Embedding 服务初始化失败", zap.Error(err))
	} else {
		es = embeddingSvc
	}

	return &RAG{
		cfg:    cfg,
		db:     db,
		client: mc,
		embed:  es,
	}, nil
}

// Retrieve 根据问题从知识库中检索相关内容
func (rag *RAG) Retrieve(ctx context.Context, kbID uint, question string, topK int) ([]RetrievalResult, error) {
	if rag.client == nil || rag.embed == nil {
		return nil, fmt.Errorf("知识库服务未就绪")
	}

	kb := getKnowledgeBase(rag.db, kbID)
	if kb == nil {
		return nil, fmt.Errorf("知识库不存在: %d", kbID)
	}

	// Step 1: 问题向量化
	vector, err := rag.embed.Embed(ctx, question)
	if err != nil {
		return nil, fmt.Errorf("问题向量化失败: %w", err)
	}

	// Step 2: Milvus 向量搜索
	collectionName := rag.cfg.Milvus.CollectionName(kb.CollectionName)
	results, err := rag.client.Search(ctx, collectionName, vector, topK)
	if err != nil {
		return nil, fmt.Errorf("向量搜索失败: %w", err)
	}
	return results, nil
}

// BuildContext 构建 LLM Prompt 注入的上下文文本
func (rag *RAG) BuildContext(results []RetrievalResult, maxChars int) string {
	if len(results) == 0 { return "" }

	var sb strings.Builder
	sb.WriteString("以下是与用户问题相关的参考资料：\n\n")
	totalChars := 0

	for i, r := range results {
		entry := fmt.Sprintf("[%d] %s (相关度: %.2f)\n", i+1, r.Content, r.Score)
		if totalChars+len(entry) > maxChars { break }
		sb.WriteString(entry)
		totalChars += len(entry)
	}
	return sb.String()
}

// AddDocument 添加文档到知识库（含分块、向量化、存储）
func (rag *RAG) AddDocument(ctx context.Context, kbID uint, title string, content string) error {
	kb := getKnowledgeBase(rag.db, kbID)
	if kb == nil {
		return fmt.Errorf("知识库不存在: %d", kbID)
	}

	chunks := SplitText(content, kb.ChunkSize, kb.ChunkOverlap)

	vectors, err := rag.embed.BatchEmbed(ctx, chunks)
	if err != nil {
		return fmt.Errorf("向量化失败: %w", err)
	}

	collectionName := rag.cfg.Milvus.CollectionName(kb.CollectionName)
	if err := rag.client.Insert(ctx, collectionName, vectors, chunks); err != nil {
		return fmt.Errorf("向量存储失败: %w", err)
	}

	// 更新统计
	rag.db.Model(&store.KnowledgeBase{}).Where("id = ?", kbID).Updates(map[string]interface{}{
		"document_count": gorm.Expr("document_count + 1"),
		"vector_count":   gorm.Expr("vector_count + ?", len(vectors)),
	})
	return nil
}

// ListKnowledgeBases 列出知识库
func (rag *RAG) ListKnowledgeBases(aid uint) ([]store.KnowledgeBase, error) {
	var kbs []store.KnowledgeBase
	query := rag.db.Model(&store.KnowledgeBase{})
	if aid > 0 { query = query.Where("aid = ?", aid) }
	err := query.Order("id desc").Find(&kbs).Error
	return kbs, err
}

// ==================== 类型定义 ====================

type RetrievalResult struct {
	Content   string  `json:"content"`
	Score     float64 `json:"score"`
	SourceDoc string  `json:"source_doc"`
	ChunkIdx  int     `json:"chunk_idx"`
}

// MilvusClient 接口
type MilvusClient interface {
	Search(ctx context.Context, collectionName string, vector []float32, topK int) ([]SearchResult, error)
	Insert(ctx context.Context, collectionName string, vectors [][]float32, texts []string) error
	DropCollection(ctx context.Context, collectionName string) error
	CreateCollectionIfNotExists(ctx context.Context, name string, dim int) error
}

type SearchResult struct {
	Vector    []float32             `json:"vector"`
	Score     float64               `json:"score"`
	Fields    map[string]interface{} `json:"fields"`
	SourceDoc string                `json:"source_doc"`
	ChunkIdx  int                   `json:"chunk_idx"`
}

// EmbeddingService 接口
type EmbeddingService interface {
	Embed(ctx context.Context, text string) ([]float32, error)
	BatchEmbed(ctx context.Context, texts []string) ([][]float32, error)
}

// ==================== 文本工具函数 ====================

// SplitText 智能文本分块
func SplitText(text string, chunkSize, overlap int) []string {
	runes := []rune(text)
	if len(runes) <= chunkSize {
		return []string{text}
	}
	
	var chunks []string
	start := 0
	for start < len(runes) {
		end := start + chunkSize
		if end > len(runes) { end = len(runes) }
		chunks = append(chunks, string(runes[start:end]))
		start += chunkSize - overlap
	}
	return chunks
}

// getKnowledgeBase 获取知识库记录
func getKnowledgeBase(db *gorm.DB, id uint) *store.KnowledgeBase {
	var kb store.KnowledgeBase
	if err := db.First(&kb, id).Error; err != nil {
		return nil
	}
	return &kb
}
