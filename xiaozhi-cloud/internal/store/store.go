package store

import (
	"context"
	"fmt"
	"time"

	"github.com/redis/go-redis/v9"
	"gorm.io/gorm"
)

// Repo 数据存储封装
type Repo struct {
	db  *gorm.DB
	rdb *redis.Client
}

// NewStore 创建 Repo 实例
func NewStore(db *gorm.DB, rdb *redis.Client) *Repo {
	return &Repo{
		db:  db,
		rdb: rdb,
	}
}

// ==================== 设备状态操作 ====================

// SetDeviceOnline 设置设备在线状态
func (s *Repo) SetDeviceOnline(ctx context.Context, deviceCode string, connID string) error {
	key := fmt.Sprintf(KeyDeviceOnline, deviceCode)
	pipe := s.rdb.Pipeline()
	pipe.HSet(ctx, key, "conn_id", connID)
	pipe.HSet(ctx, key, "status", 1)
	pipe.Expire(ctx, key, 120*time.Second) // 心跳超时时间的两倍
	_, err := pipe.Exec(ctx)
	return err
}

// GetDeviceStatus 获取设备在线状态
func (s *Repo) GetDeviceStatus(ctx context.Context, deviceCode string) (map[string]string, error) {
	key := fmt.Sprintf(KeyDeviceOnline, deviceCode)
	return s.rdb.HGetAll(ctx, key).Result()
}

// SetDeviceOffline 移除设备在线状态
func (s *Repo) SetDeviceOffline(ctx context.Context, deviceCode string) error {
	key := fmt.Sprintf(KeyDeviceOnline, deviceCode)
	return s.rdb.Del(ctx, key).Err()
}

// UpdateDeviceHeartbeat 更新设备心跳
func (s *Repo) UpdateDeviceHeartbeat(ctx context.Context, deviceCode string) error {
	key := fmt.Sprintf(KeyDeviceHeartbeat, deviceCode)
	return s.rdb.Set(ctx, key, "1", 90*time.Second).Err() // 90秒过期
}

// CheckRateLimit 检查直播间弹幕限流 (简化版)
func (s *Repo) CheckRateLimit(ctx context.Context, roomID string, userID string, qpsPerRoom, qpsPerUser int) (bool, error) {
	// 房间级别限流 - 使用简单计数器
	roomKey := fmt.Sprintf(KeyRoomRateLimit, roomID)
	count, err := s.rdb.Incr(ctx, roomKey).Result()
	if err != nil {
		return false, err
	}
	if count == 1 {
		s.rdb.Expire(ctx, roomKey, time.Second)
	}
	if count > int64(qpsPerRoom) {
		return false, nil
	}

	// 用户级别限流
	userKey := fmt.Sprintf(KeyUserRateLimit, roomID, userID)
	userCount, err := s.rdb.Incr(ctx, userKey).Result()
	if err != nil {
		return false, err
	}
	if userCount == 1 {
		s.rdb.Expire(ctx, userKey, time.Second)
	}
	if userCount > int64(qpsPerUser) {
		return false, nil
	}

	return true, nil
}

// ==================== 会话上下文操作 ====================

// GetRoomSession 获取直播间会话上下文
func (s *Repo) GetRoomSession(ctx context.Context, roomID string) (string, error) {
	key := fmt.Sprintf(KeyRoomSession, roomID)
	return s.rdb.Get(ctx, key).Result()
}

// SetRoomSession 设置直播间会话上下文
func (s *Repo) SetRoomSession(ctx context.Context, roomID string, session string, ttl time.Duration) error {
	key := fmt.Sprintf(KeyRoomSession, roomID)
	return s.rdb.Set(ctx, key, session, ttl).Err()
}

// ==================== 敏感词缓存 ====================

// GetSensitiveWordsCache 获取敏感词缓存
func (s *Repo) GetSensitiveWordsCache(ctx context.Context) (string, error) {
	return s.rdb.Get(ctx, KeySensitiveWordsCache).Result()
}

// SetSensitiveWordsCache 设置敏感词缓存
func (s *Repo) SetSensitiveWordsCache(ctx context.Context, words string, ttl time.Duration) error {
	return s.rdb.Set(ctx, KeySensitiveWordsCache, words, ttl).Err()
}

// ==================== 直播间统计 ====================

// IncrRoomViewers 增加直播间观看人数
func (s *Repo) IncrRoomViewers(ctx context.Context, roomID string) error {
	key := fmt.Sprintf(KeyRoomViewers, roomID)
	return s.rdb.Incr(ctx, key).Err()
}

// DecrRoomViewers 减少直播间观看人数
func (s *Repo) DecrRoomViewers(ctx context.Context, roomID string) error {
	key := fmt.Sprintf(KeyRoomViewers, roomID)
	return s.rdb.Decr(ctx, key).Err()
}

// GetRoomViewers 获取直播间观看人数
func (s *Repo) GetRoomViewers(ctx context.Context, roomID string) (int64, error) {
	key := fmt.Sprintf(KeyRoomViewers, roomID)
	return s.rdb.Get(ctx, key).Int64()
}
