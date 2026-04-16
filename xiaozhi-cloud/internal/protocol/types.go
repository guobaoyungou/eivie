package protocol

import "encoding/json"

// Message xiaozhi-esp32 协议消息结构
type Message struct {
	Type      string          `json:"type"`                // 消息类型
	ID        string          `json:"id,omitempty"`        // 消息唯一ID（用于请求响应匹配）
	Timestamp int64           `json:"timestamp,omitempty"` // 时间戳
	Data      json.RawMessage `json:"data,omitempty"`       // 消息负载（JSON）
}

// ==================== 设备 -> 服务端 消息类型 ====================

const (
	// MsgTypeHello 握手/认证消息
	MsgTypeHello = "hello"
	// MsgTypePing 心跳
	MsgTypePing = "ping"
	// MsgTypeAudio 音频数据流（ASR输入）
	MsgTypeAudio = "audio"
	// MsgTypeStateChange 状态变更通知
	MsgTypeStateChange = "state_change"
	// MsgTypeResult 结果确认
	MsgTypeResult = "result"
	// MsgTypeError 错误报告
	MsgTypeError = "error"
)

// ==================== 服务端 -> 设备 消息类型 ====================

const (
	// MsgTypePong 心跳响应
	MsgTypePong = "pong"
	// MsgTypeTextToSpeech TTS语音合成指令
	MsgTypeTextToSpeech = "tts"
	// MsgTypeIotControl IoT设备控制指令（LED/舵机等）
	MsgTypeIotControl = "iot_control"
	// MsgTypeConfigUpdate 配置下发
	MsgTypeConfigUpdate = "config_update"
	// MsgTypeSystemPrompt 系统提示词更新
	MsgTypeSystemPrompt = "system_prompt"
)

// HelloData hello 握手请求数据
type HelloData struct {
	DeviceCode     string `json:"device_code"`
	DeviceID       string `json:"device_id"`
	FirmwareVersion string `json:"firmware_version"`
	Hardware       string `json:"hardware"`
	ClientVersion  string `json:"client_version"`
	Token          string `json:"token"` // 认证Token
}

// HelloResponse hello 握手响应数据
type HelloResponse struct {
	Code    int    `json:"code"`
	Message string `json:"message"`
	Server  string `json:"server"`
	Version string `json:"version"`
}

// AudioData 音频数据
type AudioData struct {
	SampleRate   int    `json:"sample_rate"`
	Channels     int    `json:"channels"`
	Encoding     string `json:"encoding"` // opus, pcm
	Payload      []byte `json:"payload"`  // base64编码或二进制数据
	EndOfSegment bool   `json:"end_of_segment"` // 是否为音频片段结束标记
}

// StateChangeData 设备状态变更
type StateChangeData struct {
	State string                 `json:"state"`
	Data  map[string]interface{} `json:"data"`
}

// TTSData TTS 指令数据
type TTSData struct {
	Text      string  `json:"text"`
	Voice     *string `json:"voice,omitempty"`
	Rate      *string `json:"rate,omitempty"`
	Volume    *string `json:"volume,omitempty"`
	PlayIndex int     `json:"play_index,omitempty"`
}

// IOTControlData IoT 控制指令
type IOTControlData struct {
	Action string                 `json:"action"` // led, servo, gpio, screen
	Params map[string]interface{} `json:"params"`
}

// ConfigUpdateData 配置更新数据
type ConfigUpdateData struct {
	TTSEnabled bool                   `json:"tts_enabled"`
	TTSVoice   string                  `json:"tts_voice"`
	IOTConfig map[string]interface{}   `json:"iot_config,omitempty"`
}

// SystemPromptData 系统提示词数据
type SystemPromptData struct {
	Prompt string `json:"prompt"`
}

// ResultData 结果确认
type ResultData struct {
	RequestID string `json:"request_id"`
	Success   bool   `json:"success"`
	Message   string `json:"message"`
}

// ErrorData 错误信息
type ErrorData struct {
	Code    int    `json:"code"`
	Message string `json:"message"`
}
