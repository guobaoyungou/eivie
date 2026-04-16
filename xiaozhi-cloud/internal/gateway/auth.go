package gateway

import (
	"context"

	"github.com/ai-eivie/xiaozhi-cloud/internal/protocol"
	"github.com/ai-eivie/xiaozhi-cloud/pkg/logger"
	"go.uber.org/zap"
)

// handleHello 处理设备握手认证
func (gw *Gateway) handleHello(conn *ConnWrapper, msg *protocol.Message) {
	helloData, err := protocol.ParseHelloData(msg.Data)
	if err != nil {
		logger.Warn("Hello 消息解析失败",
			zap.String("conn_id", conn.ID),
			zap.Error(err),
		)
		resp := gw.protocol.BuildHelloError(msg.ID, 400, "无效的 hello 数据格式")
		gw.sendToConn(conn, resp)
		return
	}

	logger.Info("收到设备 Hello 握手",
		zap.String("conn_id", conn.ID),
		zap.String("device_code", helloData.DeviceCode),
		zap.String("hardware", helloData.Hardware),
		zap.String("firmware", helloData.FirmwareVersion),
	)

	// 验证设备（查询数据库）
	devInfo, err := gw.deviceMgr.AuthenticateDevice(helloData.DeviceCode, helloData.Token)
	if err != nil {
		logger.Warn("设备认证失败",
			zap.String("conn_id", conn.ID),
			zap.String("device_code", helloData.DeviceCode),
			zap.Error(err),
		)
		resp := gw.protocol.BuildHelloError(msg.ID, 401, "设备认证失败: "+err.Error())
		gw.sendToConn(conn, resp)
		return
	}

	// 更新连接信息
	conn.DeviceCode = helloData.DeviceCode
	conn.DeviceName = devInfo.DeviceName
	conn.RoomID = devInfo.RoomID
	conn.StoreID = devInfo.StoreID
	conn.Authenticated = true

	// 注册连接到映射表
	gw.connMap.Store(conn.ID, conn)

	// 注册到设备管理器
	gw.deviceMgr.HandleConnect(helloData.DeviceCode, conn.ID, conn.Connection, devInfo)

	// 设置 Redis 在线状态
	gw.storeRepo.SetDeviceOnline(context.Background(), helloData.DeviceCode, conn.ID)

	// 更新统计
	gw.statsMutex.Lock()
	gw.stats.OnlineDevices++
	gw.statsMutex.Unlock()

	// 发送成功响应
	resp := gw.protocol.BuildHelloResponse(msg.ID)
	gw.sendToConn(conn, resp)

	logger.Info("设备认证成功",
		zap.String("conn_id", conn.ID),
		zap.String("device_code", helloData.DeviceCode),
		zap.Uint("room_id", devInfo.RoomID),
		zap.Uint("store_id", devInfo.StoreID),
	)
}

// sendToConn 向连接发送协议消息
func (gw *Gateway) sendToConn(conn *ConnWrapper, msg *protocol.Message) bool {
	data, err := gw.protocol.EncodeMessage(msg)
	if err != nil {
		logger.Error("编码消息失败", zap.Error(err))
		return false
	}
	return gw.SendToConnection(conn.ID, data)
}
