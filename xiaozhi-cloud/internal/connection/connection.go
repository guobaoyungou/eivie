package connection

import (
	"sync"
	"time"

	"github.com/gorilla/websocket"
)

// Connection 设备连接封装
type Connection struct {
	ID         string
	DeviceCode string
	RoomID     uint
	StoreID    uint
	Conn       *websocket.Conn
	Send       chan []byte
	LastActive time.Time
	mu         sync.RWMutex
}

// NewConnection 创建新连接
func NewConnection(id, deviceCode string, roomID, storeID uint, wsConn *websocket.Conn) *Connection {
	return &Connection{
		ID:         id,
		DeviceCode: deviceCode,
		RoomID:     roomID,
		StoreID:    storeID,
		Conn:       wsConn,
		Send:       make(chan []byte, 256),
		LastActive: time.Now(),
	}
}

// UpdateActiveTime 更新最后活跃时间
func (c *Connection) UpdateActiveTime() {
	c.mu.Lock()
	c.LastActive = time.Now()
	c.mu.Unlock()
}

// GetLastActive 获取最后活跃时间（线程安全）
func (c *Connection) GetLastActive() time.Time {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.LastActive
}

// SafeWrite 线程安全地写入消息
func (c *Connection) SafeWrite(messageType int, data []byte) error {
	c.mu.Lock()
	defer c.mu.Unlock()
	return c.Conn.WriteMessage(messageType, data)
}
