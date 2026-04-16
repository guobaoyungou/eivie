package tts

import (
	"context"
	"fmt"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
)

// TTSRequest TTS 请求
type TTSRequest struct {
	Text      string  `json:"text"`
	Voice     string  `json:"voice"`
	Rate      string  `json:"rate"`     // 语速，如 "+0%", "-20%"
	Volume    string  `json:"volume"`   // 音量
	Format    string  `json:"format"`    // 输出格式: mp3, opus, pcm
	RoomID    uint    `json:"room_id"`
	DeviceCode string `json:"device_code,omitempty"`
}

// TTSResult TTS 结果（音频数据）
type TTSResult struct {
	AudioData []byte `json:"audio_data"` // 音频二进制数据
	Format    string `json:"format"`
	DurationMs int   `json:"duration_ms"`
}

// Provider TTS 提供者接口
type Provider interface {
	// Synthesize 文本转语音
	Synthesize(ctx context.Context, req *TTSRequest) (*TTSResult, error)
	// SynthesizeStream 流式合成
	SynthesizeStream(ctx context.Context, req *TTSRequest) (<-chan AudioChunk, error)
	Name() string
}

// AudioChunk 音频分片
type AudioChunk struct {
	Data     []byte `json:"data"`
	IsLast   bool   `json:"is_last"`
	Duration int    `json:"duration_ms"`
}

// Engine TTS 引擎
type Engine struct {
	cfg       *config.TTSConfig
	providers map[string]Provider
	defaultP  string
}

// NewEngine 创建 TTS 引擎
func NewEngine(cfg *config.TTSConfig) (*Engine, error) {
	e := &Engine{
		cfg:       cfg,
		providers: make(map[string]Provider),
	}

	for name, provCfg := range cfg.Providers {
		switch name {
		case "edge":
			p, err := NewEdgeTTSProvider(provCfg)
			if err != nil {
				logger.Warn("EdgeTTS 初始化失败", zap.Error(err))
				continue
			}
			e.providers[name] = p

		case "volcengine":
			p, err := NewVolcengineTTSProvider(provCfg)
			if err != nil {
				logger.Warn("火山 TTS 初始化失败", zap.Error(err))
				continue
			}
			e.providers[name] = p
		}
	}

	e.defaultP = cfg.DefaultProvider
	logger.Info("TTS 引擎初始化完成", zap.String("default", e.defaultP))
	return e, nil
}

// Synthesize 使用默认或指定 provider 合成语音
func (e *Engine) Synthesize(ctx context.Context, req *TTSRequest) (*TTSResult, error) {
	p := e.getProvider("")
	return p.Synthesize(ctx, req)
}

// SynthesizeStream 流式合成
func (e *Engine) SynthesizeStream(ctx context.Context, req *TTSRequest) (<-chan AudioChunk, error) {
	p := e.getProvider("")
	return p.SynthesizeStream(ctx, req)
}

func (e *Engine) getProvider(name string) Provider {
	if name == "" { name = e.defaultP }
	if p, ok := e.providers[name]; ok { return p }
	return e.providers["edge"] // fallback to edge (free)
}

// ==================== EdgeTTS 实现 ====================

type EdgeTTSProvider struct {
	cfg config.TTSProvider
}

func NewEdgeTTSProvider(cfg config.TTSProvider) (*EdgeTTSProvider, error) {
	return &EdgeTTSProvider{cfg: cfg}, nil
}

func (p *EdgeTTSProvider) Name() string { return "EdgeTTS" }

func (p *EdgeTTSProvider) Synthesize(ctx context.Context, req *TTSRequest) (*TTSResult, error) {
	// EdgeTTS 通过命令行工具调用 (edge-tts 或 edge-tts-go)
	// TODO: 集成 Go 原生 EdgeTTS 库实现
	audioData := []byte{} // 占位
	
	return &TTSResult{
		AudioData: audioData,
		Format:    "mp3",
	}, nil
}

func (p *EdgeTTSProvider) SynthesizeStream(ctx context.Context, req *TTSRequest) (<-chan AudioChunk, error) {
	ch := make(chan AudioChunk, 64)
	close(ch)
	return ch, nil // TODO: 实现流式 EdgeTTS
}

// ==================== VolcengineTTS 实现 ====================

type VolcengineTTSProvider struct {
	cfg config.TTSProvider
}

func NewVolcengineTTSProvider(cfg config.TTSProvider) (*VolcengineTTSProvider, error) {
	return &VolcengineTTSProvider{cfg: cfg}, nil
}

func (p *VolcengineTTSProvider) Name() string { return "VolcengineTTS" }

func (p *VolcengineTTSProvider) Synthesize(ctx context.Context, req *TTSRequest) (*TTSResult, error) {
	// TODO: 对接火山引擎 TTS API
	return nil, fmt.Errorf("火山 TTS 待实现")
}

func (p *VolcengineTTSProvider) SynthesizeStream(ctx context.Context, req *TTSRequest) (<-chan AudioChunk, error) {
	ch := make(chan AudioChunk, 64)
	close(ch)
	return ch, nil
}
