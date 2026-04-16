package fetcher

import (
	"context"
	"encoding/json"
	"fmt"
	"net/url"
	"time"

	"github.com/gorilla/websocket"
	"go.uber.org/zap"
)

// DouyinFetcher 抖音弹幕抓取器
type DouyinFetcher struct {
	*BaseFetcher
	conn     *websocket.Conn
	roomURL  string
	tiktokEncoder string
}

func NewDouyinFetcher() *DouyinFetcher {
	return &DouyinFetcher{
		BaseFetcher: NewBaseFetcher("douyin"),
	}
}

// Connect 建立抖音直播间 WebSocket 连接
func (f *DouyinFetcher) Connect(ctx context.Context, platformRoomAddr string) error {
	f.platformRoomAddr = platformRoomAddr
	f.roomURL = f.buildWSURL(platformRoomAddr)
	
	dialer := websocket.Dialer{
		HandshakeTimeout: 10 * time.Second,
	}

	conn, _, err := dialer.DialContext(ctx, f.roomURL, nil)
	if err != nil {
		return fmt.Errorf("抖音WebSocket连接失败: %w", err)
	}
	f.conn = conn
	f.running = true
	
	logger.Info("抖音弹幕连接成功",
		zap.String("url", f.roomURL),
	)
	return nil
}

// Start 开始拉取弹幕
func (f *DouyinFetcher) Start(ctx context.Context) (<-chan DanmakuMessage, error) {
	if !f.running {
		return nil, fmt.Errorf("抓取器尚未连接")
	}

	go f.readLoop(ctx)
	return f.msgChan, nil
}

// buildWSURL 构建抖音直播间 WebSocket 地址
func (f *DouyinFetcher) buildWSURL(roomAddr string) string {
	// 抖音直播间地址解析
	// 支持格式: https://live.douyin.com/{room_id} 或直接使用 room_id
	u, err := url.Parse(roomAddr)
	if err != nil || u.Scheme == "" {
		// 如果不是完整 URL，视为 room_id
		roomAddr = "https://live.douyin.com/" + roomAddr
	}

	// 抖音弹幕 WebSocket 接口（需要通过 webchat API 获取真实 WS 地址）
	// 这里是示例实现，实际部署时需根据抖音最新接口调整
	wsURL := "wss://webcast5-ws-web-lq.douyin.com/webcast/im/push/v2/"
	return wsURL
}

// readLoop 读取循环
func (f *DouyinFetcher) readLoop(ctx context.Context) {
	defer func() {
		f.running = false
		if f.conn != nil {
			f.conn.Close()
		}
	}()

	for {
		select {
		case <-ctx.Done():
			return
		case <-f.stopChan:
			return
		default:
			_, message, err := f.conn.ReadMessage()
			if err != nil {
				logger.Warn("抖音弹幕读取错误", zap.Error(err))
				return
			}

			// 解析抖音弹幕数据
			msg, err := f.parseMessage(message)
			if err != nil {
				continue
			}

			select {
			case f.msgChan <- *msg:
			case <-time.After(time.Second):
				logger.Warn("抖音弹幕通道满，丢弃消息")
			}
		}
	}
}

// parseMessage 解析原始消息为标准化格式
func (f *DouyinFetcher) parseMessage(rawData []byte) (*DanmakuMessage, error) {
	// 抖音弹幕数据结构（简化版）
	// 实际数据为 Protobuf 编码，这里展示 JSON 格式的处理流程
	var raw struct {
		Payload struct {
			Common struct {
				Method string `json:"method"`
			} `json:"common"`
			Data json.RawMessage `json:"data"`
		} `json:"payload"`
	}

	if err := json.Unmarshal(rawData, &raw); err != nil {
		return nil, err
	}

	switch raw.Payload.Common.Method {
	case "WebcastChatMessage":
		// 文字弹幕
		var chat struct {
			User struct {
				ID       string `json:"id"`
				NickName string `json:"nickname"`
			} `json:"user"`
			Content string `json:"content"`
		}
		if err := json.Unmarshal(raw.Payload.Data, &chat); err != nil {
			return nil, err
		}

		return &DanmakuMessage{
			Platform:    "douyin",
			Content:     chat.Content,
			UserID:      chat.User.ID,
			UserName:    chat.User.NickName,
			MessageType: "text",
			Timestamp:   time.Now().UnixMilli(),
		}, nil

	case "WebcastGiftMessage":
		// 礼物消息
		var gift struct {
			User struct {
				ID       string `json:"id"`
				NickName string `json:"nickname"`
			} `json:"user"`
			Gift struct {
				Name   string `json:"name"`
				Count  int64  `json:"count"`
			} `json:"gift"`
		}
		if err := json.Unmarshal(raw.Payload.Data, &gift); err != nil {
			return nil, err
		}

		return &DanmakuMessage{
			Platform:    "douyin",
			UserID:      gift.User.ID,
			UserName:    gift.User.NickName,
			MessageType: "gift",
			GiftName:    gift.Gift.Name,
			GiftCount:   gift.Gift.Count,
			Timestamp:   time.Now().UnixMilli(),
		}, nil

	case "WebcastLikeMessage":
		// 点赞
		var like struct {
			User struct {
				ID       string `json:"id"`
				NickName string `json:"nickname"`
			} `json:"user"`
			Count int64 `json:"count"`
		}
		if err := json.Unmarshal(raw.Payload.Data, &like); err != nil {
			return nil, err
		}

		return &DanmakuMessage{
			Platform:    "douyin",
			UserID:      like.User.ID,
			UserName:    like.User.NickName,
			MessageType: "like",
			Timestamp:   time.Now().UnixMilli(),
		}, nil

	case "WebcastSocialMessage":
		// 关注
		var follow struct {
			User struct {
				ID       string `json:"id"`
				NickName string `json:"nickname"`
			} `json:"user"`
		}
		if err := json.Unmarshal(raw.Payload.Data, &follow); err != nil {
			return nil, err
		}

		return &DanmakuMessage{
			Platform:    "douyin",
			UserID:      follow.User.ID,
			UserName:    follow.User.NickName,
			MessageType: "follow",
			Timestamp:   time.Now().UnixMilli(),
		}, nil

	default:
		// 忽略其他类型的消息
		return nil, fmt.Errorf("unknown method: %s", raw.Payload.Common.Method)
	}
}

// ShipinhaoFetcher 视频号弹幕抓取器
type ShipinhaoFetcher struct {
	*BaseFetcher
	conn    *websocket.Conn
	roomURL string
}

func NewShipinhaoFetcher() *ShipinhaoFetcher {
	return &ShipinhaoFetcher{BaseFetcher: NewBaseFetcher("shipinhao")}
}

func (f *ShipinhaoFetcher) Name() string { return f.name }

func (f *ShipinhaoFetcher) Connect(ctx context.Context, platformRoomAddr string) error {
	f.platformRoomAddr = platformRoomAddr
	// TODO: 实现视频号 WebSocket 连接
	f.running = true
	return nil
}

func (f *ShipinhaoFetcher) Start(ctx context.Context) (<-chan DanmakuMessage, error) {
	// TODO: 实现视频号弹幕读取
	return f.msgChan, nil
}

func (f *ShipinhaoFetcher) Stop() error { 
	f.running = false
	return nil 
}

// KuaishouFetcher 快手弹幕抓取器
type KuaishouFetcher struct {
	*BaseFetcher
	conn    *websocket.Conn
	roomURL string
}

func NewKuaishouFetcher() *KuaishouFetcher {
	return &KuaishouFetcher{BaseFetcher: NewBaseFetcher("kuaishou")}
}

func (f *KuaishouFetcher) Name() string { return f.name }

func (f *KuaishouFetcher) Connect(ctx context.Context, platformRoomAddr string) error {
	f.platformRoomAddr = platformRoomAddr
	// TODO: 实现快手 WebSocket 连接
	f.running = true
	return nil
}

func (f *KuaishouFetcher) Start(ctx context.Context) (<-chan DanmakuMessage, error) {
	// TODO: 实现快手弹幕读取
	return f.msgChan, nil
}

func (f *KuaishouFetcher) Stop() error {
	f.running = false
	return nil
}

// TaobaoFetcher 淘宝弹幕抓取器
type TaobaoFetcher struct {
	*BaseFetcher
	conn    *websocket.Conn
	roomURL string
}

func NewTaobaoFetcher() *TaobaoFetcher {
	return &TaobaoFetcher{BaseFetcher: NewBaseFetcher("taobao")}
}

func (f *TaobaoFetcher) Name() string { return f.name }

func (f *TaoshaoFetcher) Connect(ctx context.Context, platformRoomAddr string) error {
	f.platformRoomAddr = platformRoomAddr
	// TODO: 实现淘宝 WebSocket 连接
	f.running = true
	return nil
}

func (f *TaobaoFetcher) Start(ctx context.Context) (<-chan DanmakuMessage, error) {
	// TODO: 实现淘宝弹幕读取
	return f.msgChan, nil
}

func (f *TaobaoFetcher) Stop() error {
	f.running = false
	return nil
}
