package gateway

import (
	"context"

	"github.com/ai-eivie/xiaozhi-cloud/internal/protocol"
	"github.com/ai-eivie/xiaozhi-cloud/pkg/logger"
	"go.uber.org/zap"
)

// handlePing 处理心跳消息
func (gw *Gateway) handlePing(conn *ConnWrapper, msg *protocol.Message) {
	if !conn.Authenticated {
		return // 未认证连接不处理心跳
	}

	conn.UpdateActiveTime()

	// 更新 Redis 心跳时间
	err := gw.storeRepo.UpdateDeviceHeartbeat(context.Background(), conn.DeviceCode)
	if err != nil {
		logger.Warn("更新心跳时间失败",
			zap.String("device_code", conn.DeviceCode),
			zap.Error(err),
		)
	}

	// 响应 Pong
	pong := protocol.BuildPong()
	gw.sendToConn(conn, pong)
}
