package pipeline

import (
	"strings"
	"sync"

	"github.com/ai-eivie/xiaozhi-cloud/internal/store"
)

// KeywordDetector 关键词检测器
type KeywordDetector struct {
	db         *gorm.DB
	rulesCache sync.Map // map[uint][]KeywordRule (roomID -> rules)
	mu         sync.RWMutex
}

// KeywordRule 关键词规则
type KeywordRule struct {
	ID        string `json:"id"`
	Name      string `json:"name"`
	Type      string `json:"type"` // exact | contains | regex | prefix
	Pattern   string `json:"pattern"`
	ReplyText string `json:"reply_text"`
	Action    string `json:"action"` // reply_text | tts | ignore | highlight
	Enabled   bool   `json:"enabled"`
	Priority  int    `json:"priority"`
}

func NewKeywordDetector() *KeywordDetector {
	return &KeywordDetector{rulesCache: sync.Map{}}
}

// Match 检测文本中的关键词
func (d *KeywordDetector) Match(text string, roomID uint) []string {
	d.mu.RLock()
	defer d.mu.RUnlock()

	var matched []string
	if rules, ok := d.rulesCache.Load(roomID); ok {
		ruleList := rules.([]KeywordRule)
		for _, rule := range ruleList {
			if !rule.Enabled {
				continue
			}
			isMatch := false
			switch rule.Type {
			case "exact":
				isMatch = text == rule.Pattern
			case "contains":
				isMatch = strings.Contains(text, rule.Pattern)
			case "prefix":
				isMatch = strings.HasPrefix(text, rule.Pattern)
			}
			if isMatch {
				matched = append(matched, rule.Name)
			}
		}
	}
	return matched
}

// LoadRulesForRoom 为直播间加载关键词规则
func (d *KeywordDetector) LoadRulesForRoom(db *gorm.DB, roomID uint) error {
	var room store.Room
	if err := db.Preload("DanmakuSettings").First(&room, roomID).Error; err != nil {
		return err
	}

	if room.DanmakuSettings == nil {
		d.rulesCache.Store(roomID, []KeywordRule{})
		return nil
	}

	rules := make([]KeywordRule, 0)
	for _, r := range room.DanmakuSettings.AutoReplyRules {
		if r.Type == "keyword" && r.Enabled {
			rules = append(rules, KeywordRule{
				ID:        r.ID,
				Name:      r.Name,
				Type:      "contains",
				Pattern:   r.Pattern,
				ReplyText: r.ReplyText,
				Action:    r.ReplyType,
				Priority:  r.Priority,
				Enabled:   true,
			})
		}
	}
	d.rulesCache.Store(roomID, rules)
	return nil
}

// RefreshRules 刷新所有规则缓存
func (d *KeywordDetector) RefreshRules(db *gorm.DB) error {
	var rooms []store.Room
	if err := db.Preload("DanmakuSettings").Find(&rooms).Error; err != nil {
		return err
	}

	newCache := sync.Map{}
	for _, room := range rooms {
		if room.DanmakuSettings == nil {
			continue
		}
		rules := make([]KeywordRule, 0)
		for _, r := range room.DanmakuSettings.AutoReplyRules {
			if r.Type == "keyword" && r.Enabled {
				rules = append(rules, KeywordRule{
					ID:        r.ID,
					Name:      r.Name,
					Type:      "contains",
					Pattern:   r.Pattern,
					ReplyText: r.ReplyText,
					Action:    r.ReplyType,
					Priority:  r.Priority,
					Enabled:   true,
				})
			}
		}
		newCache.Store(room.ID, rules)
	}

	d.mu.Lock()
	d.rulesCache = newCache
	d.mu.Unlock()
	return nil
}
