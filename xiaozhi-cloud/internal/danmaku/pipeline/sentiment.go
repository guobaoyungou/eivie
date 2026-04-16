package pipeline

import (
	"strings"
	"sync"
	"unicode/utf8"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
)

// SentimentAnalyzer 情绪分析器
type SentimentAnalyzer struct {
	cfg      *config.SentimentConfig
	words    map[string]float64 // word -> score (positive or negative)
	mu       sync.RWMutex
}

const (
	SentimentPositive = "positive"
	SentimentNeutral  = "neutral"
	SentimentNegative = "negative"
)

// 预定义的情绪词库（可扩展）
var defaultPositiveWords = map[string]float64{
	"好":   1.0, "棒": 1.0, "赞": 1.0, "喜欢": 1.0, "爱": 1.0,
	"优秀": 1.0, "厉害": 1.0, "牛": 1.0, "强": 1.0, "漂亮": 1.0,
	"好看": 1.0, "好听": 1.0, "好吃": 1.0, "舒服": 1.0, "开心": 1.0,
	"快乐": 1.0, "幸福": 1.0, "满意": 1.0, "推荐": 1.0, "支持": 1.0,
	"666":  1.0, "哈哈哈": 1.0, "哈哈": 0.8, "笑死": 0.8, "太棒了": 1.2,
	"买买买": 1.2, "冲": 0.8, "想要": 0.8,
}

var defaultNegativeWords = map[string]float64{
	"差": -1.0, "烂": -1.0, "垃圾": -1.5, "难看": -1.0, "难听": -1.0,
	"讨厌": -1.0, "恶心": -1.2, "烦": -0.8, "无聊": -0.8, "失望": -1.0,
	"退货": -1.2, "差评": -1.3, "坑": -1.0, "假货": -1.5, "骗子": -1.8,
	"贵": -0.6, "不值": -0.8, "不好": -0.7, "不行": -0.6, "别买": -1.2,
	"避雷": -1.4, "翻车": -0.9, "拉黑": -1.0, "举报": -1.1,
}

func NewSentimentAnalyzer(cfg *config.SentimentConfig) *SentimentAnalyzer {
	sa := &SentimentAnalyzer{
		cfg:   cfg,
		words: make(map[string]float64),
	}
	
	// 加载默认词库
	for w, s := range defaultPositiveWords {
		sa.words[w] = s
	}
	for w, s := range defaultNegativeWords {
		sa.words[w] = s
	}
	
	return sa
}

// Analyze 分析文本情绪（基于规则，快速高效）
func (sa *SentimentAnalyzer) Analyze(text string) string {
	if !sa.cfg.Enabled {
		return SentimentNeutral
	}

	sa.mu.RLock()
	defer sa.mu.RUnlock()

	var totalScore float64
	wordCount := 0

	// 逐词匹配
	for word, score := range sa.words {
		count := strings.Count(text, word)
		if count > 0 {
			totalScore += float64(count) * score
			wordCount += count * utf8.RuneCountInString(word)
		}
	}

	if wordCount == 0 {
		return SentimentNeutral
	}

	avgScore := totalScore / float64(wordCount)
	threshold := 0.15 // 默认阈值

	if avgScore > threshold {
		return SentimentPositive
	} else if avgScore < -threshold {
		return SentimentNegative
	}
	return SentimentNeutral
}

// GetScore 获取情绪得分 (-1 到 1)
func (sa *SentimentAnalyzer) GetScore(text string) float64 {
	sa.mu.RLock()
	defer sa.mu.RUnlock()

	var totalScore float64
	wordCount := 0

	for word, score := range sa.words {
		count := strings.Count(text, word)
		if count > 0 {
			totalScore += float64(count) * score
			wordCount++
		}
	}

	if wordCount == 0 {
		return 0
	}

	score := totalScore / float64(wordCount)
	if score > 1 { score = 1 }
	if score < -1 { score = -1 }
	return score
}

// AddWord 动态添加情绪词
func (sa *SentimentAnalyzer) AddWord(word string, score float64) {
	sa.mu.Lock()
	defer sa.mu.Unlock()
	sa.words[word] = score
}
