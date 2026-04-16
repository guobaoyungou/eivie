package llm

import (
	"sync"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
)

// ModelInfo 模型信息（对外暴露）
type ModelInfo struct {
	ID              string  `json:"id"`
	DisplayName     string  `json:"display_name"`
	Provider        string  `json:"provider"`
	MaxTokens       int     `json:"max_tokens"`
	SupportsStream  bool    `json:"supports_stream"`
	CostPer1KInput  float64 `json:"cost_per_1k_input"`
	CostPer1KOutput float64 `json:"cost_per_1k_output"`
}

// ModelConfig 模型配置（内部使用）
type ModelConfig struct {
	ID             uint                   `json:"id"`
	Name           string                 `json:"name"`
	Provider       string                 `json:"provider"`
	ModelID        string                 `json:"model_id"`
	APIEndpoint    string                 `json:"api_endpoint"`
	MaxTokens      int                    `json:"max_tokens"`
	Temperature    float64                `json:"temperature"`
	TopP           float64                `json:"top_p"`
	Params         map[string]interface{} `json:"params"`
	SystemPrompt   string                 `json:"system_prompt"`
	SupportsStream bool                   `json:"supports_stream"`
	CostPer1KTokens float64              `json:"cost_per_1k_tokens"`
}

// Registry 模型广场注册表
type Registry struct {
	cfg          *config.Config
	models       map[string]ModelInfo // modelID -> ModelInfo
	providers    map[string][]ModelInfo // provider -> []ModelInfo
	defaultModel string
	mu           sync.RWMutex
}

// NewRegistry 创建模型注册表
func NewRegistry(cfg *config.Registry) *Registry {
	r := &Registry{
		cfg:       cfg,
		models:    make(map[string]ModelInfo),
		providers: make(map[string][]ModelInfo),
	}
	r.loadFromConfig()
	return r
}

// loadFromConfig 从配置加载模型列表
func (r *Registry) loadFromConfig() {
	r.mu.Lock()
	defer r.mu.Unlock()

	for provID, provCfg := range r.cfg.LLM.Providers {
		var models []ModelInfo
		for _, m := range provCfg.Models {
			info := ModelInfo{
				ID:              m.ID,
				DisplayName:     m.DisplayName,
				Provider:        provCfg.Name,
				MaxTokens:       m.MaxTokens,
				SupportsStream:  m.SupportsStream,
				CostPer1KInput:  m.CostPer1KInput,
				CostPer1KOutput: m.CostPer1KOutput,
			}
			
			r.models[m.ID] = info
			models = append(models, info)
		}
		
		r.providers[provID] = models
		
		// 设置第一个模型的默认 Provider
		if len(models) > 0 && r.defaultModel == "" {
			r.defaultModel = models[0].ID
		}
	}
}

// GetModel 根据 modelID 获取模型信息
func (r *Registry) GetModel(modelID string) (*ModelInfo, bool) {
	r.mu.RLock()
	defer r.mu.RUnlock()
	
	if m, ok := r.models[modelID]; ok {
		return &m, true
	}
	return nil, false
}

// GetModelsByProvider 获取指定 Provider 的所有模型
func (r *Registry) GetModelsByProvider(providerName string) []ModelInfo {
	r.mu.RLock()
	defer r.mu.RUnlock()
	
	if models, ok := r.providers[providerName]; ok {
		return models
	}
	return nil
}

// GetAllModels 获取所有可用模型
func (r *Registry) GetAllModels() []ModelInfo {
	r.mu.RLock()
	defer r.mu.RUnlock()
	
	result := make([]ModelInfo, 0, len(r.models))
	for _, m := range r.models {
		result = append(result, m)
	}
	return result
}

// GetDefaultProvider 获取默认 Provider 配置信息
func (r *Registry) GetDefaultProvider() *config.ProviderConfig {
	r.mu.RLock()
	defer r.mu.RUnlock()

	if defaultProv, ok := r.cfg.LLM.Providers[r.cfg.LLM.DefaultProvider]; ok {
		return &defaultProv
	}

	// 返回第一个可用的 Provider
	for _, p := range r.cfg.LLM.Providers {
		cp := p
		return &cp
	}
	return nil
}

// SearchModels 搜索模型（按名称或 ID 模糊匹配）
func (r *Registry) SearchModels(query string) []ModelInfo {
	r.mu.RLock()
	defer r.mu.RUnlock()

	if query == "" {
		return r.GetAllModels()
	}

	var results []ModelInfo
	for _, m := range r.models {
		if containsIgnoreCase(m.DisplayName, query) || containsIgnoreCase(m.ID, query) ||
			containsIgnoreCase(m.Provider, query) {
			results = append(results, m)
		}
	}
	return results
}

// Reload 重新从配置加载（热更新）
func (r *Registry) Reload() {
	r.loadFromConfig()
	logger.Info("模型注册表已重新加载")
}

// AddCustomModel 添加自定义模型配置
func (r *Registry) AddCustomModel(info ModelInfo) error {
	r.mu.Lock()
	defer r.mu.Unlock()

	if _, exists := r.models[info.ID]; exists {
		return fmt.Errorf("模型 %s 已存在", info.ID)
	}

	r.models[info.ID] = info
	r.providers[info.Provider] = append(r.providers[info.Provider], info)
	return nil
}

func containsIgnoreCase(s, substr string) bool {
	return strings.Contains(strings.ToLower(s), strings.ToLower(substr))
}
