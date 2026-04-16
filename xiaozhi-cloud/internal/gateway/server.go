package gateway

import (
	"context"
	"encoding/json"
	"net/http"
	"sync"
	"time"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"github.com/ai-eivie/xiaozhi-cloud/internal/connection"
	"github.com/ai-eivie/xiaozhi-cloud/internal/device"
	"github.com/ai-eivie/xiaozhi-cloud/internal/protocol"
	"github.com/ai-eivie/xiaozhi-cloud/internal/store"
	"github.com/ai-eivie/xiaozhi-cloud/pkg/logger"
	"github.com/gorilla/websocket"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"
	"gorm.io/gorm"
)

// WebSocket 网关升级器配置
var upgrader = websocket.Upgrader{
	ReadBufferSize:  1024,
	WriteBufferSize: 1024,
	CheckOrigin: func(r *http.Request) bool {
		return true // 开发环境允许所有来源
	},
}

// Gateway WebSocket 网关
type Gateway struct {
	cfg        *config.Config
	db         *gorm.DB
	rdb        *redis.Client
	protocol   *protocol.Protocol
	deviceMgr  *device.Manager
	storeRepo  *store.Repo

	// 连接管理
	connMap    sync.Map // connID -> *ConnWrapper
	deviceConn sync.Map // deviceCode -> []connID (一个设备可能有多个连接)

	// 统计
	stats      Stats
	statsMutex sync.RWMutex
}

// Stats 网关统计信息
type Stats struct {
	TotalConnections int64
	OnlineDevices    int64
	MessagesReceived int64
	MessagesSent     int64
}

// ConnWrapper 包装 connection.Connection 添加网关特有字段
type ConnWrapper struct {
	*connection.Connection
	WS            *websocket.Conn
	Authenticated bool
	DeviceName    string
	ConnTime      time.Time
	sendChan      chan []byte
	closeChan     chan struct{}
	doneChan      chan struct{}
}

// New 创建新的网关实例
func New(cfg *config.Config, db *gorm.DB, rdb *redis.Client) *Gateway {
	p := protocol.NewProtocol(cfg)
	dm := device.NewManager(db, rdb, cfg)
	sr := store.NewStore(db, rdb)

	gw := &Gateway{
		cfg:       cfg,
		db:        db,
		rdb:       rdb,
		protocol:  p,
		deviceMgr: dm,
		storeRepo: sr,
	}
	return gw
}

// Start 启动 WebSocket 网关服务
func (gw *Gateway) Start(addr string) error {
	mux := http.NewServeMux()

	// xiaozhi-esp32 设备协议端点（兼容原协议）
	mux.HandleFunc("/xiaozhi/v1/", gw.handleXiaozhiConnection)

	// 直播弹幕通道端点（扩展）
	mux.HandleFunc("/live/v1/", gw.handleLiveConnection)

	// 健康检查端点
	mux.HandleFunc("/health", gw.handleHealth)

	srv := &http.Server{
		Addr:         addr,
		Handler:      mux,
		ReadTimeout:  time.Duration(gw.cfg.Gateway.ReadWait) * time.Second,
		WriteTimeout: time.Duration(gw.cfg.Gateway.WriteWait) * time.Second,
	}

	logger.Info("WebSocket 网关启动",
		zap.String("addr", addr),
		zap.Int("max_connections", gw.cfg.Gateway.MaxConnections),
	)
	return srv.ListenAndServe()
}

// Shutdown 优雅关闭
func (gw *Gateway) Shutdown(ctx context.Context) {
	logger.Info("正在关闭 WebSocket 网关...")
	
	// 关闭所有连接
	gw.connMap.Range(func(key, value interface{}) bool {
		conn := value.(*ConnWrapper)
		gw.closeConnection(conn)
		return true
	})

	logger.Info("WebSocket 网关已关闭")
}

// handleHealth 健康检查
func (gw *Gateway) handleHealth(w http.ResponseWriter, r *http.Request) {
	gw.statsMutex.RLock()
	stats := gw.stats
	gw.statsMutex.RUnlock()

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"status":    "ok",
		"server":    "xiaozhi-cloud-gateway",
		"version":   "1.0.0",
		"timestamp": time.Now().Unix(),
		"stats": map[string]int64{
			"connections": stats.TotalConnections,
			"devices":     stats.OnlineDevices,
			"msgs_in":     stats.MessagesReceived,
			"msgs_out":    stats.MessagesSent,
		},
	})
}

// handleXiaozhiConnection 处理 Xmini-C3 设备连接（兼容 xiaozhi-esp32 协议）
func (gw *Gateway) handleXiaozhiConnection(w http.ResponseWriter, r *http.Request) {
	wsConn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		logger.Warn("WebSocket 升级失败", zap.Error(err))
		return
	}

	connID := protocol.GenerateID()
	
	// 创建连接对象
	c := &ConnWrapper{
		Connection: connection.NewConnection(connID, "", 0, 0, wsConn),
		WS:         wsConn,
		ConnTime:   time.Now(),
		sendChan:   make(chan []byte, 256),
		closeChan:  make(chan struct{}),
		doneChan:   make(chan struct{}),
	}

	gw.statsMutex.Lock()
	gw.stats.TotalConnections++
	gw.statsMutex.Unlock()

	// 启动读写协程
	go gw.readPump(c)
	go gw.writePump(c)

	logger.Info("设备连接建立",
		zap.String("conn_id", c.ID),
		zap.String("remote_addr", r.RemoteAddr),
	)
}

// handleLiveConnection 处理直播管理端连接（弹幕推送/监控）
func (gw *Gateway) handleLiveConnection(w http.ResponseWriter, r *http.Request) {
	wsConn, err := upgrader.Upgrade(w, r, nil)
	if err != nil {
		logger.Warn("Live WebSocket 升级失败", zap.Error(err))
		return
	}

	connID := protocol.GenerateID()
	
	c := &ConnWrapper{
		Connection: connection.NewConnection(connID, "", 0, 0, wsConn),
		WS:         wsConn,
		ConnTime:   time.Now(),
		sendChan:   make(chan []byte, 256),
		closeChan:  make(chan struct{}),
		doneChan:   make(chan struct{}),
	}

	go gw.liveReadPump(c)
	go gw.writePump(c)

	logger.Info("Live 客户端连接建立",
		zap.String("conn_id", c.ID),
		zap.String("remote_addr", r.RemoteAddr),
	)
}

// readPump 从设备读取消息
func (gw *Gateway) readPump(conn *ConnWrapper) {
	defer func() {
		gw.closeConnection(conn)
	}()

	// 设置读取超时 deadline
	conn.WS.SetReadDeadline(time.Now().Add(time.Duration(gw.cfg.Gateway.ReadWait) * time.Second))
	conn.WS.SetPongHandler(func(appData string) error {
		conn.UpdateActiveTime()
		conn.WS.SetReadDeadline(time.Now().Add(time.Duration(gw.cfg.Gateway.ReadWait) * time.Second))
		return nil
	})

	for {
		_, message, err := conn.WS.ReadMessage()
		if err != nil {
			if websocket.IsUnexpectedCloseError(err, websocket.CloseGoingAway, websocket.CloseNormalClosure) {
				logger.Warn("读取消息错误",
					zap.String("conn_id", conn.ID),
					zap.String("device", conn.DeviceCode),
					zap.Error(err),
				)
			} else {
				logger.Debug("连接正常关闭",
					zap.String("conn_id", conn.ID),
					zap.String("device", conn.DeviceCode),
				)
			}
			return
		}

		gw.statsMutex.Lock()
		gw.stats.MessagesReceived++
		gw.statsMutex.Unlock()
		conn.UpdateActiveTime()

		// 处理消息
		gw.handleMessage(conn, message)
	}
}

// writePump 向设备写入消息（异步发送）
func (gw *Gateway) writePump(conn *ConnWrapper) {
	ticker := time.NewTicker(time.Duration(gw.cfg.Gateway.HeartbeatInterval/2) * time.Second)
	defer func() {
		ticker.Stop()
		conn.WS.Close()
		close(conn.doneChan)
	}()

	for {
		select {
		case message, ok := <-conn.sendChan:
			conn.WS.SetWriteDeadline(time.Now().Add(time.Duration(gw.cfg.Gateway.WriteWait) * time.Second))
			if !ok {
				conn.WS.WriteMessage(websocket.CloseMessage, []byte{})
				return
			}

			err := conn.WS.WriteMessage(websocket.TextMessage, message)
			if err != nil {
				logger.Error("写入消息失败",
					zap.String("conn_id", conn.ID),
					zap.Error(err),
				)
				return
			}

			gw.statsMutex.Lock()
			gw.stats.MessagesSent++
			gw.statsMutex.Unlock()

		case <-ticker.C:
			// 发送心跳 Ping
			pingMsg, _ := json.Marshal(map[string]string{"type": "ping"})
			conn.WS.SetWriteDeadline(time.Now().Add(time.Duration(gw.cfg.Gateway.WriteWait) * time.Second))
			if err := conn.WS.WriteMessage(websocket.TextMessage, pingMsg); err != nil {
				return
			}

		case <-conn.closeChan:
			return
		}
	}
}

// liveReadPump Live 连接的读取循环
func (gw *Gateway) liveReadPump(conn *ConnWrapper) {
	defer gw.closeConnection(conn)
	for {
		_, _, err := conn.WS.ReadMessage()
		if err != nil {
			return
		}
		// Live 连接主要用于推送，暂不处理客户端发来的消息
	}
}

// handleMessage 分发处理设备消息
func (gw *Gateway) handleMessage(conn *ConnWrapper, rawMessage []byte) {
	msg, err := gw.protocol.DecodeMessage(rawMessage)
	if err != nil {
		logger.Warn("解析协议消息失败",
			zap.String("conn_id", conn.ID),
			zap.ByteString("raw", rawMessage),
			zap.Error(err),
		)
		return
	}

	switch msg.Type {
	case protocol.MsgTypeHello:
		gw.handleHello(conn, msg)

	case protocol.MsgTypePing:
		gw.handlePing(conn, msg)

	case protocol.MsgTypeAudio:
		gw.handleAudio(conn, msg)

	case protocol.MsgTypeStateChange:
		gw.handleStateChange(conn, msg)

	case protocol.MsgTypeResult:
		gw.handleResult(conn, msg)

	default:
		logger.Debug("未知消息类型",
			zap.String("type", msg.Type),
			zap.String("conn_id", conn.ID),
		)
	}
}

// closeConnection 安全关闭连接
func (gw *Gateway) closeConnection(conn *ConnWrapper) {
	// 防止重复关闭
	select {
	case <-conn.doneChan:
		return
	default:
	}

	// 从映射中移除
	gw.connMap.Delete(conn.ID)

	// 更新设备状态
	if conn.DeviceCode != "" && conn.Authenticated {
		gw.deviceMgr.HandleDisconnect(conn.DeviceCode, conn.ID)
		gw.storeRepo.SetDeviceOffline(context.Background(), conn.DeviceCode)

		gw.statsMutex.Lock()
		gw.stats.OnlineDevices--
		gw.stats.TotalConnections--
		gw.statsMutex.Unlock()
	}

	// 发送关闭信号
	close(conn.closeChan)

	logger.Info("连接关闭",
		zap.String("conn_id", conn.ID),
		zap.String("device", conn.DeviceCode),
	)
}

// SendToConnection 向指定连接发送消息
func (gw *Gateway) SendToConnection(connID string, data []byte) bool {
	if val, ok := gw.connMap.Load(connID); ok {
		conn := val.(*ConnWrapper)
		select {
		case conn.sendChan <- data:
			return true
		case <-time.After(5 * time.Second):
			logger.Warn("发送超时，丢弃消息",
				zap.String("conn_id", connID),
			)
			return false
		}
	}
	return false
}

// BroadcastToRoom 向直播间内所有设备广播消息
func (gw *Gateway) BroadcastToRoom(roomID uint, data []byte) int {
	count := 0
	gw.connMap.Range(func(key, value interface{}) bool {
		conn := value.(*ConnWrapper)
		if conn.RoomID == roomID && conn.Authenticated {
			select {
			case conn.sendChan <- data:
				count++
			default:
			}
		}
		return true
	})
	return count
}

// GetStats 获取网关统计
func (gw *Gateway) GetStats() Stats {
	gw.statsMutex.RLock()
	defer gw.statsMutex.RUnlock()
	return gw.stats
}

// GetOnlineDevices 获取在线设备列表
func (gw *Gateway) GetOnlineDevices() []*ConnWrapper {
	var devices []*ConnWrapper
	gw.connMap.Range(func(key, value interface{}) bool {
		conn := value.(*ConnWrapper)
		if conn.Authenticated && conn.DeviceCode != "" {
			devices = append(devices, conn)
		}
		return true
	})
	return devices
}

// GetDeviceManager 获取设备管理器
func (gw *Gateway) GetDeviceManager() *device.Manager {
	return gw.deviceMgr
}

// GetProtocol 获取协议处理器
func (gw *Gateway) GetProtocol() *protocol.Protocol {
	return gw.protocol
}
