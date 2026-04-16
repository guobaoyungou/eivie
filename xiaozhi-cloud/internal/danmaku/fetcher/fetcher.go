package fetcher

import (
	"context"
)

// DanmakuMessage 标准化弹幕消息
type DanmakuMessage struct {
	Platform    string            `json:"platform"`     // 平台标识: douyin/kuaishou/shipinhao/taobao
	RoomID      string            `json:"room_id"`      // 系统直播间ID
	UserID      string            `json:"user_id"`      // 平台用户ID
	UserName    string            `json:"user_name"`    // 用户昵称
	Content     string            `json:"content"`       // 弹幕原文
	MessageType string            `json:"message_type"` // text/gift/like/follow/join
	GiftName    string            `json:"gift_name"`     // 礼物名（如有）
	GiftCount   int64             `json:"gift_count"`    // 礼物数量
	Timestamp   int64             `json:"timestamp"`     // 时间戳
	Extra       map[string]string `json:"extra"`         // 扩展字段
}

// Fetcher 弹幕抓取器统一接口
type Fetcher interface {
	// Connect 建立到平台的弹幕连接
	Connect(ctx context.Context, platformRoomAddr string) error
	// Start 开始拉取弹幕，消息通过返回 channel 输出
	Start(ctx context.Context) (<-chan DanmakuMessage, error)
	// Stop 停止抓取
	Stop() error
	// Name 返回平台名称
	Name() string
	// IsRunning 返回是否正在运行
	IsRunning() bool
}

// BaseFetcher 抓取器基类，提供通用实现
type BaseFetcher struct {
	name        string
	running     bool
	stopChan    chan struct{}
	msgChan     chan DanmakuMessage
	errChan     chan error
	platformRoomAddr string
}

func NewBaseFetcher(name string) *BaseFetcher {
	return &BaseFetcher{
		name:     name,
		stopChan: make(chan struct{}),
		msgChan:  make(chan DanmakuMessage, 10000), // 缓冲队列
		errChan:  make(chan error, 10),
	}
}

func (f *BaseFetcher) Name() string { return f.name }
func (f *BaseFetcher) IsRunning() bool { return f.running }

func (f *BaseFetcher) Stop() error {
	f.running = false
	close(f.stopChan)
	close(f.msgChan)
	return nil
}

// FetcherFactory 抓取器工厂
type Factory struct{}

func NewFactory() *Factory { return &Factory{} }

// Create 根据平台名称创建对应的抓取器实例
func (f *Factory) Create(platform string) Fetcher {
	switch platform {
	case "douyin":
		return NewDouyinFetcher()
	case "shipinhao":
		return NewShipinhaoFetcher()
	case "kuaishou":
		return NewKuaishouFetcher()
	case "taobao":
		return NewTaobaoFetcher()
	default:
		return nil
	}
}
