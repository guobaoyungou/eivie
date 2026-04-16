package pipeline

import (
	"context"
	"sync"
	"time"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"github.com/ai-eivie/xiaozhi-cloud/internal/danmaku/fetcher"
	"go.uber.org/zap"
	"gorm.io/gorm"
)

// Pipeline 弹幕处理管线
type Pipeline struct {
	cfg       *config.Config
	db        *gorm.DB
	
	// 处理器
	filter      *Filter
	keyword     *KeywordDetector
	sentiment   *SentimentAnalyzer

	// 运行状态
	fetchers    map[uint]fetcher.Fetcher // roomID -> Fetcher 实例
	fetcherMu   sync.RWMutex

	// 输出通道（过滤后的有效弹幕）
	OutputChan chan *ProcessedDanmaku

	// 统计
	stats      PipelineStats
	statsMu    sync.RWMutex
}

// ProcessedDanmaku 处理后的弹幕消息
type ProcessedDanmaku struct {
	Original  fetcher.DanmakuMessage `json:"original"`
	RoomID    uint                  `json:"room_id"`
	Filtered  bool                  `json:"filtered"`
	FilterReason string             `json:"filter_reason,omitempty"`
	Sentiment string                `json:"sentiment"` // positive/neutral/negative
	Keywords  []string              `json:"keywords_matched"`
	Priority  int                   `json:"priority"` // 0-普通, 1-关键词, 2-礼物, 3-@主播
	ProcessedAt time.Time           `json:"processed_at"`
}

// PipelineStats 管线统计
type PipelineStats struct {
	TotalReceived   int64
	TotalFiltered   int64
	TotalPassed     int64
	SentimentPos    int64
	SentimentNeg    int64
	SentimentNeu    int64
	AvgProcessMs    float64
}

// NewPipeline 创建弹幕处理管线
func NewPipeline(cfg *config.Config, db *gorm.DB) (*Pipeline, error) {
	p := &Pipeline{
		cfg:        cfg,
		db:         db,
		fetchers:   make(map[uint]fetcher.Fetcher),
		OutputChan: make(chan *ProcessedDanmaku, 50000),
	}

	// 初始化过滤器
	filter, err := NewFilter(cfg.Danmaku.SensitiveWordsFile)
	if err != nil {
		logger.Warn("初始化敏感词过滤器失败", zap.Error(err))
	}
	p.filter = filter

	// 初始化关键词检测器
	p.keyword = NewKeywordDetector()

	// 初始化情绪分析器
	p.sentiment = NewSentimentAnalyzer(cfg.Danmaku.Sentiment)

	return p, nil
}

// StartFetcher 为直播间启动弹幕抓取
func (p *Pipeline) StartFetcher(ctx context.Context, roomID uint, platform string, platformRoomAddr string) error {
	p.fetcherMu.Lock()
	defer p.fetcherMu.Unlock()

	if existing, ok := p.fetchers[roomID]; ok && existing.IsRunning() {
		logger.Warn("直播间已有运行中的抓取器", zap.Uint("room_id", roomID))
		return nil
	}

	factory := fetcher.NewFactory()
	f := factory.Create(platform)
	if f == nil {
		return fmt.Errorf("不支持的平台: %s", platform)
	}

	if err := f.Connect(ctx, platformRoomAddr); err != nil {
		return fmt.Errorf("连接 %s 平台失败: %w", platform, err)
	}

	msgChan, err := f.Start(ctx)
	if err != nil {
		return fmt.Errorf("启动 %s 抓取失败: %w", platform, err)
	}

	p.fetchers[roomID] = f

	// 启动该直播间的处理协程
	go p.processLoop(roomID, msgChan)

	logger.Info("弹幕抓取已启动",
		zap.Uint("room_id", roomID),
		zap.String("platform", platform),
	)
	return nil
}

// StopFetcher 停止指定直播间的抓取
func (p *Pipeline) StopFetcher(roomID uint) error {
	p.fetcherMu.Lock()
	defer p.fetcherMu.Unlock()

	if f, ok := p.fetchers[roomID]; ok {
		err := f.Stop()
		delete(p.fetchers, roomID)
		return err
	}
	return nil
}

// StopAll 停止所有抓取器
func (p *Pipeline) StopAll() {
	p.fetcherMu.Lock()
	defer p.fetcherMu.Unlock()

	for roomID, f := range p.fetchers {
		f.Stop()
		delete(p.fetchers, roomID)
	}
}

// processLoop 处理循环 - 对每个直播间独立处理
func (p *Pipeline) processLoop(roomID uint, msgChan <-chan fetcher.DanmakuMessage) {
	for rawMsg := range msgChan {
		startTime := time.Now()

		// 执行完整管线
		result := p.processOne(rawMsg, roomID)

		processDuration := time.Since(startTime).Milliseconds()

		// 更新统计
		p.statsMu.Lock()
		p.stats.TotalReceived++
		if result.Filtered {
			p.stats.TotalFiltered++
		} else {
			p.stats.TotalPassed++
			switch result.Sentiment {
			case "positive": p.stats.SentimentPos++
			case "negative": p.stats.SentimentNeg++
			case "neutral":  p.stats.SentimentNeu++
			}
		}
		// 滑动窗口平均延迟
		p.stats.AvgProcessMs = (p.stats.AvgProcessMs*float64(p.stats.TotalReceived-1) + float64(processDuration)) / float64(p.stats.TotalReceived)
		p.statsMu.Unlock()

		// 输出有效弹幕
		if !result.Filtered {
			select {
			case p.OutputChan <- result:
			default:
				logger.Warn("弹幕输出通道满",
					zap.Uint("room_id", room_id),
				)
			}
		}
	}
}

// processOne 处理单条弹幕（管线核心逻辑）
func (p *Pipeline) processOne(msg fetcher.DanmakuMessage, roomID uint) *ProcessedDanmaku {
	result := &ProcessedDanmaku{
		Original:    msg,
		RoomID:      roomID,
		ProcessedAt: time.Now(),
	}

	// 礼物/关注类消息优先级最高，跳过文字过滤
	if msg.MessageType == "gift" {
		result.Priority = 2
		result.Sentiment = "positive" // 礼物默认正面情绪
		return result
	}
	if msg.MessageType == "follow" || msg.MessageType == "join" {
		result.Priority = 2
		result.Sentiment = "positive"
		return result
	}

	// Step 1: 敏感词过滤 (DFA算法 O(n))
	if p.filter != nil && p.filter.Match(msg.Content) {
		result.Filtered = true
		result.FilterReason = "sensitive_word"
		return result
	}

	// Step 2: 关键词检测
	keywords := p.keyword.Match(msg.Content, roomID)
	if len(keywords) > 0 {
		result.Keywords = keywords
		result.Priority = 1
	}

	// Step 3: 情绪分析
	sentiment := p.sentiment.Analyze(msg.Content)
	result.Sentiment = sentiment

	// Step 4: 限流检查（在更高层处理）
	
	return result
}

// GetStats 获取管线统计
func (p *Pipeline) GetStats() PipelineStats {
	p.statsMu.RLock()
	defer p.statsMu.RUnlock()
	return p.stats
}

// ReloadSensitiveWords 重新加载敏感词库
func (p *Pipeline) ReloadSensitiveWords() error {
	if p.filter != nil {
		return p.filter.Reload(p.cfg.Danmaku.SensitiveWordsFile)
	}
	return nil
}
