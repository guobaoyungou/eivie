package llm

import (
	"context"
	"encoding/json"
	"fmt"
	"io"
	"sync"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"github.com/ai-eivie/xiaozhi-cloud/internal/store"
	"github.com/sashabaranov/go-openai"
	"go.uber.org/zap"
)

// Gateway LLM 统一网关
type Gateway struct {
	cfg      *config.Config
	db       *gorm.DB
	clients  map[string]*openai.Client // providerID -> OpenAI client (兼容多种API)
	registry *Registry
	mu       sync.RWMutex
}

// ChatRequest 对话请求
type ChatRequest struct {
	SessionID        string            `json:"session_id"`
	RoomID           uint              `json:"room_id"`
	Messages         []Message         `json:"messages"`
	ModelConfig      *ModelConfig      `json:"model_config,omitempty"`
	KnowledgeContext []string          `json:"knowledge_context,omitempty"`
	SystemPrompt     string            `json:"system_prompt,omitempty"`
	Temperature      *float64          `json:"temperature,omitempty"`
	MaxTokens        *int              `json:"max_tokens,omitempty"`
	Stream           bool              `json:"stream"`
}

// Message 对话消息
type Message struct {
	Role    string `json:"role"`    // system/user/assistant
	Content string `json:"content"`
	Name    string `json:"name,omitempty"`
}

// ChatChunk 流式响应块
type ChatChunk struct {
	Content   string `json:"content"`
	Done      bool   `json:"done"`
	Reasoning string `json:"reasoning,omitempty"`
	TokenUsed int    `json:"token_used"`
	Model     string `json:"model"`
}

// ChatResponse 完整响应（非流式）
type ChatResponse struct {
	Content    string `json:"content"`
	Model      string `json:"model"`
	PromptTokens   int `json:"prompt_tokens"`
	CompletionTokens int `json:"completion_tokens"`
	TotalTokens     int `json:"total_tokens"`
}

// NewGateway 创建 LLM 网关
func NewGateway(cfg *config.Config, db *gorm.DB) (*Gateway, error) {
	g := &Gateway{
		cfg:     cfg,
		db:      db,
		clients: make(map[string]*openai.Client),
		registry: NewRegistry(cfg),
	}

	// 初始化所有配置的 Provider 客户端
	for providerID, providerCfg := range cfg.LLM.Providers {
		if err := g.initProviderClient(providerID, providerCfg); err != nil {
			logger.Warn("初始化 LLM Provider 失败",
				zap.String("provider", providerID),
				zap.Error(err),
			)
		}
	}

	return g, nil
}

// initProviderClient 初始化指定 Provider 的客户端
func (g *Gateway) initProviderClient(providerID string, cfg config.ProviderConfig) error {
	if cfg.APIKey == "" || cfg.APIKey == "${"+providerID+"_API_KEY}" {
		return fmt.Errorf("%s API Key 未配置", cfg.Name)
	}

	config := openai.DefaultConfig(cfg.APIKey)
	if cfg.BaseURL != "" {
		config.BaseURL = cfg.BaseURL
	}

	client := openai.NewClientWithConfig(config)
	g.mu.Lock()
	g.clients[providerID] = client
	g.mu.Unlock()

	logger.Info("LLM Provider 客户端已初始化",
		zap.String("provider", cfg.Name),
		zap.String("base_url", cfg.BaseURL),
	)
	return nil
}

// Chat 执行对话（非流式）
func (g *Gateway) Chat(ctx context.Context, req *ChatRequest) (*ChatResponse, error) {
	modelCfg := req.ModelConfig
	if modelCfg == nil {
		var err error
		modelCfg, err = g.GetDefaultModelForRoom(req.RoomID)
		if err != nil {
			return nil, fmt.Errorf("获取默认模型失败: %w", err)
		}
	}

	client, err := g.getClient(modelCfg.Provider)
	if err != nil {
		return nil, err
	}

	// 构建消息列表
	messages := make([]openai.ChatCompletionMessage, 0, len(req.Messages)+2)

	// 添加系统提示词
	systemPrompt := req.SystemPrompt
	if systemPrompt == "" && modelCfg.SystemPrompt != "" {
		systemPrompt = modelCfg.SystemPrompt
	}
	if systemPrompt != "" {
		messages = append(messages, openai.ChatCompletionMessage{
			Role:    "system",
			Content: systemPrompt,
		})
	}

	// 注入知识库上下文
	if len(req.KnowledgeContext) > 0 {
		kbContext := "【参考资料】\n" + joinStrings(req.KnowledgeContext, "\n---\n")
		messages = append(messages, openai.ChatCompletionMessage{
			Role:    "system",
			Content: kbContext,
		})
	}

	// 添加对话历史
	for _, msg := range req.Messages {
		messages = append(messages, openai.ChatCompletionMessage{
			Role:    msg.Role,
			Content: msg.Content,
			Name:    optionalString(msg.Name),
		})
	}

	// 构建请求参数
	temp := g.cfg.LLM.RequestTimeout
	reqOpts := openai.ChatCompletionRequest{
		Model:       modelCfg.ModelID,
		Messages:    messages,
		MaxTokens:   optionalInt(req.MaxTokens, modelCfg.MaxTokens),
		Temperature: optionalFloat(req.Temperature, modelCfg.Temperature),
		TopP:        float64Ptr(modelCfg.TopP),
	}

	resp, err := client.CreateChatCompletion(ctx, reqOpts)
	if err != nil {
		return nil, fmt.Errorf("LLM 调用失败: %w", err)
	}

	if len(resp.Choices) == 0 {
		return nil, fmt.Errorf("LLM 返回空结果")
	}

	return &ChatResponse{
		Content:          resp.Choices[0].Message.Content,
		Model:            resp.Model,
		PromptTokens:     resp.Usage.PromptTokens,
		CompletionTokens: resp.Usage.CompletionTokens,
		TotalTokens:      resp.Usage.TotalTokens,
	}, nil
}

// ChatStream 流式对话，通过 channel 返回 chunks
func (g *Gateway) ChatStream(ctx context.Context, req *ChatRequest) (<-chan ChatChunk, error) {
	modelCfg := req.ModelConfig
	if modelCfg == nil {
		var err error
		modelCfg, err = g.GetDefaultModelForRoom(req.RoomID)
		if err != nil {
			return nil, fmt.Errorf("获取默认模型失败: %w", err)
		}
	}

	client, err := g.getClient(modelCfg.Provider)
	if err != nil {
		return nil, err
	}

	// 构建消息（同 Chat 方法）
	messages := make([]openai.ChatCompletionMessage, 0, len(req.Messages)+2)

	systemPrompt := req.SystemPrompt
	if systemPrompt == "" && modelCfg.SystemPrompt != "" {
		systemPrompt = modelCfg.SystemPrompt
	}
	if systemPrompt != "" {
		messages = append(messages, openai.ChatCompletionMessage{
			Role:    "system",
			Content: systemPrompt,
		})
	}

	if len(req.KnowledgeContext) > 0 {
		kbContext := "【参考资料】\n" + joinStrings(req.KnowledgeContext, "\n---\n")
		messages = append(messages, openai.ChatCompletionMessage{
			Role:    "system",
			Content: kbContext,
		})
	}

	for _, msg := range req.Messages {
		messages = append(messages, openai.ChatCompletionMessage{
			Role:    msg.Role,
			Content: msg.Content,
			Name:    optionalString(msg.Name),
		})
	}

	reqOpts := openai.ChatCompletionRequest{
		Model:       modelCfg.ModelID,
		Messages:    messages,
		MaxTokens:   optionalInt(req.MaxTokens, modelCfg.MaxTokens),
		Temperature: optionalFloat(req.Temperature, modelCfg.Temperature),
		TopP:        float64Ptr(modelCfg.TopP),
	}

	stream, err := client.CreateChatCompletionStream(ctx, reqOpts)
	if err != nil {
		return nil, fmt.Errorf("创建流式连接失败: %w", err)
	}

	chunkChan := make(chan ChatChunk, 256)

	go func() {
		defer close(chunkChan)
		defer stream.Close()

		for {
			response, err := stream.Recv()
			if err != nil {
				if err != io.EOF {
					logger.Error("流式读取错误", zap.Error(err))
				}
				
				// 发送结束标记
				chunkChan <- ChatChunk{Done: true}
				return
			}

			if len(response.Choices) > 0 {
				delta := response.Choices[0].Delta
				isDone := response.Choices[0].FinishReason == "stop" || 
					response.Choices[0].FinishReason == "length"

				chunkChan <- ChatChunk{
					Content:   delta.Content,
					Done:      isDone,
					Reasoning: delta.ReasoningContent,
					Model:     response.Model,
				}

				if isDone {
					return
				}
			}
		}
	}()

	return chunkChan, nil
}

// getClient 获取指定 Provider 的客户端
func (g *Gateway) getClient(providerID string) (*openai.Client, error) {
	g.mu.RLock()
	defer g.mu.RUnlock()

	client, ok := g.clients[providerID]
	if !ok {
		return nil, fmt.Errorf("未找到 Provider: %s", providerID)
	}
	return client, nil
}

// GetDefaultModelForRoom 获取直播间绑定的模型配置
func (g *Gateway) GetDefaultModelForRoom(roomID uint) (*ModelConfig, error) {
	var room store.Room
	if err := g.db.Preload("ModelConfig").First(&room, roomID).Error; err != nil {
		return nil, err
	}

	if room.ModelConfig != nil {
		return convertToLLMConfig(room.ModelConfig), nil
	}

	// 返回全局默认模型
	defaultProvider := g.registry.GetDefaultProvider()
	if defaultProvider != nil && len(defaultProvider.Models) > 0 {
		modelInfo := defaultProvider.Models[0]
		return &ModelConfig{
			ID:             0,
			Name:           modelInfo.DisplayName,
			Provider:       defaultProvider.Name,
			ModelID:        modelInfo.ID,
			MaxTokens:      modelInfo.MaxTokens,
			SupportsStream: modelInfo.SupportsStream,
			Temperature:    0.7,
			TopP:           1.0,
		}, nil
	}

	return nil, fmt.Errorf("未找到可用的 LLM 模型")
}

// ListModels 列出所有可用模型
func (g *Gateway) ListModels() []ModelInfo {
	g.mu.RLock()
	defer g.mu.RUnlock()

	var models []ModelInfo
	for _, p := range g.cfg.LLM.Providers {
		for _, m := range p.Models {
			models = append(models, ModelInfo{
				ID:              m.ID,
				DisplayName:     m.DisplayName,
				Provider:        p.Name,
				MaxTokens:       m.MaxTokens,
				SupportsStream:  m.SupportsStream,
				CostPer1KInput:  m.CostPer1KInput,
				CostPer1KOutput: m.CostPer1KOutput,
			})
		}
	}
	return models
}

// ==================== 辅助函数 ====================

func joinStrings(strs []string, sep string) string {
	result := ""
	for i, s := range strs {
		if i > 0 { result += sep }
		result += s
	}
	return result
}

func optionalInt(val *p, def int) int {
	if val != nil { return *val }
	return def
}

func optionalFloat(val *p, def float64) float64 {
	if val != nil { return *val }
	return def
}

func optionalString(s string) *string {
	if s == "" { return nil }
	return &s
}

func float64Ptr(f float64) *float64 { return &f }

func intPtr(i int) *int { return &i }

// convertToLLMConfig 将数据库 ModelConfig 转为内部格式
func convertToLLMConfig(mc *store.ModelConfig) *ModelConfig {
	var params map[string]interface{}
	if mc.Params != "" {
		json.Unmarshal([]byte(mc.Params), &params)
	}

	return &ModelConfig{
		ID:             mc.ID,
		Name:           mc.Name,
		Provider:       mc.Provider,
		ModelID:        mc.ModelID,
		APIEndpoint:    mc.APIEndpoint,
		MaxTokens:      mc.MaxTokens,
		Temperature:    mc.Temperature,
		TopP:           mc.TopP,
		Params:         params,
		SupportsStream: mc.SupportsStream,
	}
}
