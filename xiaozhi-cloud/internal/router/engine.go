package router

import (
	"context"
	"encoding/json"
	"sync"
	"time"

	"github.com/ai-eivie/xiaozhi-cloud/internal/danmaku/pipeline"
	"github.com/ai-eivie/xiaozhi-cloud/internal/gateway"
)

// Engine 消息路由引擎
type Engine struct {
	gateway    *gateway.Gateway
	rules      map[uint][]Rule // roomID -> rules
	mu         sync.RWMutex
	handlerMap map[string]Handler
}

// Rule 路由规则定义
type Rule struct {
	ID       string `json:"id"`
	Name     string `json:"name"`
	Type     string `json:"type"` // keyword | sentiment | message_type | all
	Pattern  string `json:"pattern"`
	Action   string `json:"action"` // ai_reply | tts_reply | text_reply | ignore | forward
	Priority int    `json:"priority"`
	Enabled  bool   `json:"enabled"`
}

// Handler 路由处理器接口
type Handler interface {
	Handle(ctx context.Context, msg *pipeline.ProcessedDanmaku) error
	Name() string
}

// RouteAction 路由动作结果
type RouteAction struct {
	ActionType string      `json:"action_type"`
	Target     string      `json:"target"`
	Payload    interface{} `json:"payload"`
	Priority   int         `json:"priority"`
}

// NewEngine 创建路由引擎
func NewEngine(gw *gateway.Gateway) *Engine {
	return &Engine{
		gateway:    gw,
		rules:      make(map[uint][]Rule),
		handlerMap: make(map[string]Handler),
	}
}

// RegisterHandler 注册处理器
func (e *Engine) RegisterHandler(h Handler) {
	e.handlerMap[h.Name()] = h
}

// Route 执行路由决策（返回匹配的动作列表）
func (e *Engine) Route(msg *pipeline.ProcessedDanmaku) []*RouteAction {
	var actions []*RouteAction

	e.mu.RLock()
	rules, ok := e.rules[msg.RoomID]
	e.mu.RUnlock()

	if !ok || len(rules) == 0 {
		return append(actions, &RouteAction{ActionType: "ai_reply", Priority: 0})
	}

	bestRule := e.matchBestRule(msg, rules)
	if bestRule != nil {
		action := &RouteAction{
			ActionType: bestRule.Action,
			Priority:   bestRule.Priority,
		}
		switch bestRule.Action {
		case "text_reply", "tts_reply":
			action.Payload = bestRule.Pattern
		}
		actions = append(actions, action)
	} else {
		actions = append(actions, &RouteAction{ActionType: "ai_reply", Priority: 0})
	}
	return actions
}

func (e *Engine) matchBestRule(msg *pipeline.ProcessedDanmaku, rules []Rule) *Rule {
	var best *Rule
	bp := -1
	for _, rule := range rules {
		if !rule.Enabled || rule.Priority <= bp { continue }
		if e.matches(rule, msg) {
			best = &rule
			bp = rule.Priority
		}
	}
	return best
}

func (e *Engine) matches(rule Rule, msg *pipeline.ProcessedDanmaku) bool {
	switch rule.Type {
	case "all": return true
	case "message_type": return msg.Original.MessageType == rule.Pattern
	case "sentiment": return msg.Sentiment == rule.Pattern
	case "keyword":
		for _, kw := range msg.Keywords { if kw == rule.Name { return true } }
		return false
	case "gift": return msg.Original.MessageType == "gift" && msg.Original.GiftName == rule.Pattern
	default: return false
	}
}

// Dispatch 分发动作到处理器
func (e *Engine) Dispatch(ctx context.Context, msg *pipeline.ProcessedDanmaku, actions []*RouteAction) error {
	for _, a := range actions {
		h, ok := e.handlerMap[a.ActionType]
		if !ok { continue }
		if err := h.Handle(ctx, msg); err != nil {
			logger.Error("路由处理器执行错误", zap.String("handler", h.Name()), zap.Error(err))
		}
	}
	return nil
}

// StartPipelineListener 启动管线监听消费循环
func (e *Engine) StartPipelineListener(pl *pipeline.Pipeline) {
	go func() {
		for proc := range pl.OutputChan {
			ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
			actions := e.Route(proc)
			e.Dispatch(ctx, proc, actions)
			cancel()
		}
	}()
}

// LoadRulesForRoom 加载直播间规则
func (e *Engine) LoadRulesForRoom(roomID uint, settings *store.DanmakuSettings) {
	if settings == nil { e.rules[roomID] = nil; return }
	rules := make([]Rule, 0)
	for _, ar := range settings.AutoReplyRules {
		rules = append(rules, Rule{
			ID: ar.ID, Name: ar.Name, Type: ar.Type,
			Pattern: ar.Pattern, Action: ar.ReplyType,
			Priority: ar.Priority, Enabled: ar.Enabled,
		})
	}
	e.rules[roomID] = rules
}
