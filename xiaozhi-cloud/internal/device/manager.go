package device

import (
	"context"
	"fmt"
	"sync"
	"time"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"github.com/ai-eivie/xiaozhi-cloud/internal/connection"
	"github.com/ai-eivie/xiaozhi-cloud/internal/store"
	"github.com/ai-eivie/xiaozhi-cloud/pkg/logger"
	"github.com/redis/go-redis/v9"
	"go.uber.org/zap"
	"gorm.io/gorm"
)

// DeviceAuthResult 设备认证结果
type DeviceAuthResult struct {
	DeviceCode string
	DeviceName string
	RoomID     uint
	StoreID    uint
}

// Manager 设备管理器（门店-直播间-设备三层资源模型）
type Manager struct {
	db  *gorm.DB
	rdb *redis.Client
	cfg *config.Config

	// 设备连接映射 (deviceCode -> Connection)
	deviceMap sync.Map // map[string]*connection.Connection

	// 会议室连接映射 (roomID -> []Connection)
	roomConnections sync.Map // map[uint][]*connection.Connection
}

// NewManager 创建设备管理器
func NewManager(db *gorm.DB, rdb *redis.Client, cfg *config.Config) *Manager {
	m := &Manager{
		db:  db,
		rdb: rdb,
		cfg: cfg,
	}

	// 启动心跳监控协程
	go m.heartbeatMonitor()

	return m
}

// AuthenticateDevice 验证设备身份
func (m *Manager) AuthenticateDevice(deviceCode, token string) (*DeviceAuthResult, error) {
	var dev store.Device
	err := m.db.Where("device_code = ?", deviceCode).First(&dev).Error
	if err != nil {
		if err == gorm.ErrRecordNotFound {
			return nil, fmt.Errorf("设备未注册: %s", deviceCode)
		}
		return nil, fmt.Errorf("查询设备失败: %w", err)
	}

	// TODO: Token 验证逻辑（对接 PHP 用户体系）
	// 当前简化为检查设备是否存在且未禁用

	if dev.OnlineStatus != 0 && dev.LastHeartbeat != nil {
		// 设备可能已有在线连接，需要判断是否为重连
		lastHeartbeat := time.Since(*dev.LastHeartbeat).Seconds()
		if lastHeartbeat < float64(m.cfg.Gateway.HeartbeatTimeout) {
			logger.Warn("设备已在别处登录",
				zap.String("device_code", deviceCode),
				zap.Float64("last_heartbeat_ago", lastHeartbeat),
			)
			// 可以选择踢掉旧连接或拒绝新连接
		}
	}

	// 更新数据库中的设备状态
	now := time.Now()
	m.db.Model(&store.Device{}).Where("id = ?", dev.ID).Updates(map[string]interface{}{
		"online_status":  1,
		"last_heartbeat": now,
		"ip_address":     "", // 由网关层填充
	})

	result := &DeviceAuthResult{
		DeviceCode: dev.DeviceCode,
		DeviceName: dev.DeviceName,
		RoomID:     dev.RoomID,
		StoreID:    dev.StoreID,
	}

	return result, nil
}

// HandleConnect 处理设备上线
func (m *Manager) HandleConnect(deviceCode string, connID string, conn *connection.Connection, authInfo *DeviceAuthResult) {
	// 存储设备连接
	m.deviceMap.Store(deviceCode, conn)

	// 添加到房间连接列表
	if authInfo.RoomID > 0 {
		conns := m.getRoomConnections(authInfo.RoomID)
		conns = append(conns, conn)
		m.roomConnections.Store(authInfo.RoomID, conns)
	}

	logger.Info("设备已上线",
		zap.String("device_code", deviceCode),
		zap.String("conn_id", connID),
		zap.Uint("room_id", authInfo.RoomID),
	)
}

// HandleDisconnect 处理设备下线
func (m *Manager) HandleDisconnect(deviceCode string, connID string) {
	// 从设备映射移除
	m.deviceMap.Delete(deviceCode)

	// 从房间连接列表移除
	if val, ok := m.deviceMap.Load(deviceCode); ok {
		conn := val.(*connection.Connection)
		if conn.RoomID > 0 {
			conns := m.getRoomConnections(conn.RoomID)
			filtered := make([]*connection.Connection, 0, len(conns))
			for _, c := range conns {
				if c.ID != connID {
					filtered = append(filtered, c)
				}
			}
			m.roomConnections.Store(conn.RoomID, filtered)
		}
	}

	// 更新数据库设备状态
	m.db.Model(&store.Device{}).Where("device_code = ?", deviceCode).Update("online_status", 0)

	logger.Info("设备已下线",
		zap.String("device_code", deviceCode),
		zap.String("conn_id", connID),
	)
}

// GetDeviceConn 获取设备当前连接
func (m *Manager) GetDeviceConn(deviceCode string) *connection.Connection {
	if val, ok := m.deviceMap.Load(deviceCode); ok {
		return val.(*connection.Connection)
	}
	return nil
}

// GetRoomDevices 获取直播间的所有在线设备
func (m *Manager) GetRoomDevices(roomID uint) []*connection.Connection {
	return m.getRoomConnections(roomID)
}

// getRoomConnections 获取直播间连接列表
func (m *Manager) getRoomConnections(roomID uint) []*connection.Connection {
	if val, ok := m.roomConnections.Load(roomID); ok {
		return val.([]*connection.Connection)
	}
	return nil
}

// heartbeatMonitor 心跳超时检测协程
func (m *Manager) heartbeatMonitor() {
	ticker := time.NewTicker(10 * time.Second)
	defer ticker.Stop()

	for range ticker.C {
		ctx := context.Background()

		m.deviceMap.Range(func(key, value interface{}) bool {
			deviceCode := key.(string)
			conn := value.(*connection.Connection)

			// 检查最后活跃时间
			sinceLastActive := time.Since(conn.GetLastActive()).Seconds()
			if sinceLastActive > float64(m.cfg.Gateway.HeartbeatTimeout) {
				logger.Warn("心跳超时，断开设备",
					zap.String("device_code", deviceCode),
					zap.Float64("idle_seconds", sinceLastActive),
				)

				// 断开连接（由网关层的 readPump 触发 closeConnection）
				m.deviceMap.Delete(deviceCode)

				// 更新数据库
				m.db.Model(&store.Device{}).Where("device_code = ?", deviceCode).
					Update("online_status", 0)
			}
			return true
		})

		_ = ctx // 使用 ctx 避免未使用警告
	}
}

// ==================== 门店 & 直播间 CRUD 操作 ====================

// CreateStore 创建门店
func (m *Manager) CreateStore(store *store.Store) error {
	return m.db.Create(store).Error
}

// GetStoreByID 获取门店详情
func (m *Manager) GetStoreByID(id uint) (*store.Store, error) {
	var s store.Store
	err := m.db.Preload("Rooms.Devices").First(&s, id).Error
	return &s, err
}

// ListStores 列出门店
func (m *Manager) ListStores(aid uint, page, pageSize int) ([]store.Store, int64, error) {
	var stores []store.Store
	var total int64

	query := m.db.Model(&store.Store{})
	if aid > 0 {
		query = query.Where("aid = ?", aid)
	}

	query.Count(&total)
	offset := (page - 1) * pageSize
	err := query.Offset(offset).Limit(pageSize).Order("id desc").Find(&stores).Error

	return stores, total, err
}

// UpdateStore 更新门店
func (m *Manager) UpdateStore(store *store.Store) error {
	return m.db.Save(store).Error
}

// DeleteStore 删除门店
func (m *Manager) DeleteStore(id uint) error {
	return m.db.Delete(&store.Store{}, id).Error
}

// CreateRoom 创建直播间
func (m *Manager) CreateRoom(room *store.Room) error {
	return m.db.Create(room).Error
}

// GetRoomByID 获取直播间详情
func (m *Manager) GetRoomByID(id uint) (*store.Room, error) {
	var r store.Room
	err := m.db.Preload("Store").
		Preload("Devices").
		Preload("LivePlatforms").
		Preload("ModelConfig").
		First(&r, id).Error
	return &r, err
}

// ListRooms 列出直播间
func (m *Manager) ListRooms(aid, storeID uint, page, pageSize int) ([]store.Room, int64, error) {
	var rooms []store.Room
	var total int64

	query := m.db.Model(&store.Room{})
	if aid > 0 {
		query = query.Where("aid = ?", aid)
	}
	if storeID > 0 {
		query = query.Where("store_id = ?", storeID)
	}

	query.Count(&total)
	offset := (page - 1) * pageSize
	err := query.Offset(offset).Limit(pageSize).Order("id desc").Find(&rooms).Error

	return rooms, total, err
}

// UpdateRoom 更新直播间
func (m *Manager) UpdateRoom(room *store.Room) error {
	return m.db.Save(room).Error
}

// CreateDevice 注册设备
func (m *Manager) CreateDevice(device *store.Device) error {
	return m.db.Create(device).Error
}

// ListDevices 列出设备
func (m *Manager) ListDevices(aid, roomID, storeID uint, onlineOnly bool, page, pageSize int) ([]store.Device, int64, error) {
	var devices []store.Device
	var total int64

	query := m.db.Model(&store.Device{})
	if aid > 0 {
		query = query.Where("aid = ?", aid)
	}
	if roomID > 0 {
		query = query.Where("room_id = ?", roomID)
	}
	if storeID > 0 {
		query = query.Where("store_id = ?", storeID)
	}
	if onlineOnly {
		query = query.Where("online_status = 1")
	}

	query.Count(&total)
	offset := (page - 1) * pageSize
	err := query.Offset(offset).Limit(pageSize).Order("id desc").Find(&devices).Error

	return devices, total, err
}

// BindDeviceToRoom 绑定设备到直播间
func (m *Manager) BindDeviceToRoom(deviceCode string, roomID uint) error {
	return m.db.Model(&store.Device{}).
		Where("device_code = ?", deviceCode).
		Updates(map[string]interface{}{
			"room_id":       roomID,
			"online_status": 1,
		}).Error
}

// UnbindDevice 解绑设备
func (m *Manager) UnbindDevice(deviceCode string) error {
	return m.db.Model(&store.Device{}).
		Where("device_code = ?", deviceCode).
		Update("room_id", 0).Error
}
