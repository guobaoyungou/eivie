package protocol

import (
	"encoding/json"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
)

// Handler 协议处理器接口
type Handler interface {
	HandleMessage(msg *Message) error
}

// Protocol xiaozhi-esp32 协议实现
type Protocol struct {
	cfg *config.Config
}

// NewProtocol 创建协议处理器
func NewProtocol(cfg *config.Config) *Protocol {
	return &Protocol{cfg: cfg}
}

// DecodeMessage 从 JSON 字节解码协议消息
func (p *Protocol) DecodeMessage(data []byte) (*Message, error) {
	msg := &Message{}
	if err := json.Unmarshal(data, msg); err != nil {
		return nil, err
	}
	return msg, nil
}

// EncodeMessage 将协议消息编码为 JSON 字节
func (p *Protocol) EncodeMessage(msg *Message) ([]byte, error) {
	return json.Marshal(msg)
}

// BuildHelloResponse 构建握手成功响应
func (p *Protocol) BuildHelloResponse(msgID string) *Message {
	resp := &HelloResponse{
		Code:    0,
		Message: "ok",
		Server:  "xiaozhi-cloud",
		Version: "1.0.0",
	}
	data, _ := json.Marshal(resp)
	return &Message{
		Type:      MsgTypeHello,
		ID:        msgID,
		Timestamp: Now(),
		Data:      data,
	}
}

// BuildHelloError 构建握手失败响应
func (p *Protocol) BuildHelloError(msgID string, code int, errMsg string) *Message {
	resp := &HelloResponse{
		Code:    code,
		Message: errMsg,
		Server:  "xiaozhi-cloud",
		Version: "1.0.0",
	}
	data, _ := json.Marshal(resp)
	return &Message{
		Type:      MsgTypeError,
		ID:        msgID,
		Timestamp: Now(),
		Data:      data,
	}
}

// BuildPong 构建心跳响应
func BuildPong() *Message {
	return &Message{
		Type:      MsgTypePong,
		Timestamp: Now(),
	}
}

// BuildTTSCommand 构建 TTS 指令
func (p *Protocol) BuildTTSCommand(text string, voice, rate, volume *string, playIndex int) *Message {
	tts := &TTSData{
		Text:      text,
		Voice:     voice,
		Rate:      rate,
		Volume:    volume,
		PlayIndex: playIndex,
	}
	data, _ := json.Marshal(tts)
	return &Message{
		Type:      MsgTypeTextToSpeech,
		Timestamp: Now(),
		Data:      data,
	}
}

// BuildConfigUpdate 构建配置更新指令
func (p *Protocol) BuildConfigUpdate(config ConfigUpdateData) *Message {
	data, _ := json.Marshal(config)
	return &Message{
		Type:      MsgTypeConfigUpdate,
		Timestamp: Now(),
		Data:      data,
	}
}

// BuildSystemPrompt 构建系统提示词更新
func (p *Protocol) BuildSystemPrompt(prompt string) *Message {
	sp := &SystemPromptData{Prompt: prompt}
	data, _ := json.Marshal(sp)
	return &Message{
		Type:      MsgTypeSystemPrompt,
		Timestamp: Now(),
		Data:      data,
	}
}
