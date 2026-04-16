package gateway

import (
	"encoding/json"

	"github.com/ai-eivie/xiaozhi-cloud/internal/protocol"
	"github.com/ai-eivie/xiaozhi-cloud/pkg/logger"
	"go.uber.org/zap"
)

// handleAudio 处理音频数据消息（ASR 输入流）
func (gw *Gateway) handleAudio(conn *ConnWrapper, msg *protocol.Message) {
	if !conn.Authenticated {
		return
	}

	audioData, err := protocol.ParseAudioData(msg.Data)
	if err != nil {
		logger.Debug("音频数据解析失败",
			zap.String("conn_id", conn.ID),
			zap.Error(err),
		)
		return
	}

	// TODO: 将音频数据转发给 ASR 服务进行处理
	// 这里需要对接语音识别服务，后续实现
	logger.Debug("收到音频数据",
		zap.String("conn_id", conn.ID),
		zap.String("device", conn.DeviceCode),
		zap.Int("payload_len", len(audioData.Payload)),
		zap.Bool("eos", audioData.EndOfSegment),
	)
}

// handleStateChange 处理状态变更消息
func (gw *Gateway) handleStateChange(conn *ConnWrapper, msg *protocol.Message) {
	if !conn.Authenticated {
		return
	}

	stateData, err := protocol.ParseStateChangeData(msg.Data)
	if err != nil {
		return
	}

	logger.Info("设备状态变更",
		zap.String("device", conn.DeviceCode),
		zap.String("state", stateData.State),
	)

	// 根据不同状态做相应处理
	switch stateData.State {
	case "playing":
		// TTS 播放完成或开始
	case "idle":
		// 设备空闲，可以接收新的 TTS 指令
	case "wakeup":
		// 唤醒词检测到
	case "speaking":
		// 用户正在说话（VAD 检测）
	}

	// 广播状态变更到 Live 监控端点
	stateJSON, _ := json.Marshal(map[string]interface{}{
		"type":        "device_state",
		"device_code": conn.DeviceCode,
		"room_id":     conn.RoomID,
		"state":       stateData.State,
		"data":        stateData.Data,
		"timestamp":   protocol.Now(),
	})
	
	gw.broadcastLiveEvent(conn.RoomID, stateJSON)
}

// handleResult 处理结果确认消息
func (gw *Gateway) handleResult(conn *ConnWrapper, msg *protocol.Message) {
	resultData, err := protocol.ParseResultData(msg.Data)
	if err != nil {
		return
	}

	logger.Debug("设备返回结果确认",
		zap.String("device", conn.DeviceCode),
		zap.String("request_id", resultData.RequestID),
		zap.Bool("success", resultData.Success),
	)
}

// broadcastLiveEvent 向直播间所有 Live 监控客户端广播事件
func (gw *Gateway) broadcastLiveEvent(roomID uint, event []byte) {
	gw.connMap.Range(func(key, value interface{}) bool {
		conn := value.(*ConnWrapper)
		// 只发送给该直播间的 Live 连接（未绑定设备的监控连接）
		if conn.RoomID == roomID && !conn.Authenticated && conn.DeviceCode == "" {
			select {
			case conn.sendChan <- event:
			default:
			}
		}
		return true
	})
}
