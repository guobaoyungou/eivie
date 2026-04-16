package store

import (
	"context"
	"fmt"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"github.com/redis/go-redis/v9"
)

var Rdb *redis.Client

// InitRedis 初始化 Redis 连接
func InitRedis(cfg *config.Config) (*redis.Client, error) {
	rdb := redis.NewClient(&redis.Options{
		Addr:         cfg.Redis.Addr,
		Password:     cfg.Redis.Password,
		DB:           cfg.Redis.DB,
		PoolSize:     cfg.Redis.PoolSize,
		MinIdleConns: cfg.Redis.MinIdleConns,
	})

	ctx := context.Background()
	if err := rdb.Ping(ctx).Err(); err != nil {
		return nil, fmt.Errorf("Redis 连接失败: %w", err)
	}

	Rdb = rdb
	return rdb, nil
}

// ==================== Redis Key 常量 ====================

const (
	// 设备在线状态 Key: device:online:{device_code}
	KeyDeviceOnline = "xiaozhi:device:online:%s"
	// 设备心跳时间 Key: device:heartbeat:{device_code}
	KeyDeviceHeartbeat = "xiaozhi:device:heartbeat:%s"
	// 直播间会话上下文 Key: room:session:{room_id}
	KeyRoomSession = "xiaozhi:room:session:%s"
	// 直播间弹幕限流 Key: room:ratelimit:{room_id}
	KeyRoomRateLimit = "xiaozhi:room:rl:%s"
	// 用户限流 Key: room:user:rl:{room_id}:{user_id}
	KeyUserRateLimit = "xiaozhi:room:user:rl:%s:%s"
	// DFA 敏感词缓存 Key: cache:sensitive_words
	KeySensitiveWordsCache = "xiaozhi:cache:sensitive_words"
	// 知识库向量计数 Key: kb:vectors:{kb_id}
	KeyKBVectors = "xiaozhi:kb:vectors:%s"
	// 直播间在线人数 Key: room:viewers:{room_id}
	KeyRoomViewers = "xiaozhi:room:viewers:%s"
	// WebSocket 连接映射 Key: ws:conn:{conn_id} -> device_code
	KeyWSConnMap = "xiaozhi:ws:conn:%s"
	// 设备到连接映射 Key: ws:device:{device_code} -> conn_ids
	KeyWSDeviceConn = "xiaozhi:ws:device:%s"
)

// ==================== 设备状态操作 ====================

// SetDeviceOnline 设置设备在线状态
func SetDeviceOnline(ctx context.Context, deviceCode string, connID string) error {
	key := fmt.Sprintf(KeyDeviceOnline, deviceCode)
	pipe := Rdb.Pipeline()
	pipe.HSet(ctx, key, "conn_id", connID)
	pipe.HSet(ctx, key, "status", 1)
	pipe.Expire(ctx, key, 120) // 心跳超时时间的两倍
	_, err := pipe.Exec(ctx)
	return err
}

// GetDeviceStatus 获取设备在线状态
func GetDeviceStatus(ctx context.Context, deviceCode string) (map[string]string, error) {
	key := fmt.Sprintf(KeyDeviceOnline, deviceCode)
	return Rdb.HGetAll(ctx, key).Result()
}

// RemoveDeviceOffline 移除设备在线状态
func RemoveDeviceOffline(ctx context.Context, deviceCode string) error {
	key := fmt.Sprintf(KeyDeviceOnline, deviceCode)
	return Rdb.Del(ctx, key).Err()
}

// UpdateDeviceHeartbeat 更新设备心跳
func UpdateDeviceHeartbeat(ctx context.Context, deviceCode string) error {
	key := fmt.Sprintf(KeyDeviceHeartbeat, deviceCode)
	return Rdb.Set(ctx, key, "1", 90).Err() // 90秒过期
}
