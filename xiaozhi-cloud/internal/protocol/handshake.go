package protocol

import (
	"encoding/json"
	"fmt"
	"time"

	"github.com/google/uuid"
)

// Now 获取当前时间戳(毫秒)
func Now() int64 {
	return time.Now().UnixMilli()
}

// GenerateID 生成消息ID
func GenerateID() string {
	return uuid.New().String()
}

// ParseHelloData 解析握手请求数据
func ParseHelloData(rawData json.RawMessage) (*HelloData, error) {
	var data HelloData
	if err := json.Unmarshal(rawData, &data); err != nil {
		return nil, fmt.Errorf("解析 hello 数据失败: %w", err)
	}
	if data.DeviceCode == "" {
		return nil, fmt.Errorf("device_code 不能为空")
	}
	return &data, nil
}

// ParseAudioData 解析音频数据
func ParseAudioData(rawData json.RawMessage) (*AudioData, error) {
	var data AudioData
	if err := json.Unmarshal(rawData, &data); err != nil {
		return nil, fmt.Errorf("解析 audio 数据失败: %w", err)
	}
	return &data, nil
}

// ParseStateChangeData 解析状态变更数据
func ParseStateChangeData(rawData json.RawMessage) (*StateChangeData, error) {
	var data StateChangeData
	if err := json.Unmarshal(rawData, &data); err != nil {
		return nil, fmt.Errorf("解析 state_change 数据失败: %w", err)
	}
	return &data, nil
}

// ParseResultData 解析结果确认数据
func ParseResultData(rawData json.RawMessage) (*ResultData, error) {
	var data ResultData
	if err := json.Unmarshal(rawData, &data); err != nil {
		return nil, fmt.Errorf("解析 result 数据失败: %w", err)
	}
	return &data, nil
}

// IsHello 判断是否为握手消息
func IsHello(msgType string) bool {
	return msgType == MsgTypeHello
}

// IsPing 判断是否为心跳消息
func IsPing(msgType string) bool {
	return msgType == MsgTypePing
}

// IsAudio 判断是否为音频消息
func IsAudio(msgType string) bool {
	return msgType == MsgTypeAudio
}

// IsStateChange 判断是否为状态变更消息
func IsStateChange(msgType string) bool {
	return msgType == MsgTypeStateChange
}
