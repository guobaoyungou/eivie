package store

import (
	"time"
)

// Store 门店模型
type Store struct {
	ID          uint      `gorm:"primaryKey" json:"id"`
	Name        string    `gorm:"size:100;not null;uniqueIndex" json:"name"`
	Description string    `gorm:"size:500" json:"description"`
	Address     string    `gorm:"size:255" json:"address"`
	ContactName string    `gorm:"size:50" json:"contact_name"`
	ContactPhone string   `gorm:"size:20" json:"contact_phone"`
	Status      int       `gorm:"default:1;comment:0-停用 1-启用" json:"status"`
	AID         uint      `gorm:"index;comment:关联主站租户ID" json:"aid"`
	CreatedAt   time.Time `json:"created_at"`
	UpdatedAt   time.Time `json:"updated_at"`
	DeletedAt   time.Time `gorm:"index" json:"-"`
}

// TableName 指定表名
func (Store) TableName() string {
	return "ddwx_xiaozhi_stores"
}

// Room 直播间模型
type Room struct {
	ID             uint           `gorm:"primaryKey" json:"id"`
	StoreID        uint           `gorm:"index;not null" json:"store_id"`
	Name           string         `gorm:"size:100;not null" json:"name"`
	CoverURL       string         `gorm:"size:500" json:"cover_url"`
	Description    string         `gorm:"size:1000" json:"description"`
	Status         int            `gorm:"default:1;comment:0-离线 1-直播中 2-暂停" json:"status"`
	SystemPrompt   string         `gorm:"type:text" json:"system_prompt"`
	ModelConfigID  uint           `json:"model_config_id"`
	KnowledgeBaseIDs string       `gorm:"type:text;json" json:"knowledge_base_ids"`
	DanmakuSettings *DanmakuSettings `gorm:"serializer:json" json:"danmaku_settings"`
	SessionConfig  *RoomSessionConfig `gorm:"serializer:json" json:"session_config"`
	AID            uint            `gorm:"index" json:"aid"`
	CreatedAt      time.Time       `json:"created_at"`
	UpdatedAt      time.Time       `json:"updated_at"`
	DeletedAt      time.Time       `gorm:"index" json:"-"`
	
	// 关联（禁用外键约束）
	Store         *Store          `gorm:"-" json:"store,omitempty"`
	Devices       []*Device       `gorm:"-" json:"devices,omitempty"`
	LivePlatforms []LivePlatform  `gorm:"-" json:"live_platforms,omitempty"`
}

func (Room) TableName() string {
	return "ddwx_xiaozhi_rooms"
}

// Device Xmini-C3 设备模型
type Device struct {
	ID              uint      `gorm:"primaryKey" json:"id"`
	RoomID          uint      `gorm:"index" json:"room_id"`
	StoreID         uint      `gorm:"index" json:"store_id"`
	DeviceCode      string    `gorm:"size:64;uniqueIndex;not null;comment:设备唯一标识码" json:"device_code"`
	DeviceName      string    `gorm:"size:100" json:"device_name"`
	FirmwareVersion string    `gorm:"size:50" json:"firmware_version"`
	HardwareType    string    `gorm:"size:50;default:xmini-c3" json:"hardware_type"`
	IPAddress       string    `gorm:"size:45" json:"ip_address"`
	OnlineStatus    int       `gorm:"default:0;comment:0-离线 1-在线" json:"online_status"`
	LastHeartbeat   *time.Time `json:"last_heartbeat"`
	SignalStrength  int       `gorm:"default:0;comment:信号强度 0-100" json:"signal_strength"`
	VolumeLevel     int       `gorm:"default:80;comment:音量等级 0-100" json:"volume_level"`
	MuteStatus      int       `gorm:"default:0;comment:0-正常 1-静音" json:"mute_status"`
	TTSVoice        string    `gorm:"size:100;default:zh-CN-XiaoxiaoNeural" json:"tts_voice"`
	AID             uint      `gorm:"index" json:"aid"`
	CreatedAt       time.Time `json:"created_at"`
	UpdatedAt       time.Time `json:"updated_at"`
	DeletedAt       time.Time `gorm:"index" json:"-"`

	Room *Room `gorm:"-" json:"room,omitempty"`
}

func (Device) TableName() string {
	return "ddwx_xiaozhi_devices"
}

// LivePlatform 直播平台弹幕源配置
type LivePlatform struct {
	ID               uint      `gorm:"primaryKey" json:"id"`
	RoomID           uint      `gorm:"index;not null" json:"room_id"`
	Platform         string    `gorm:"size:20;not null;enum:douyin,shipinhao,kuaishou,taobao" json:"platform"`
	PlatformRoomID   string    `gorm:"size:200;not null;comment:平台直播间ID或地址" json:"platform_room_id"`
	PlatformRoomName string    `gorm:"size:200" json:"platform_room_name"`
	StreamURL        string    `gorm:"size:500" json:"stream_url"`
	Status           int       `gorm:"default:1;comment:0-停止 1-运行中" json:"status"`
	Config           *PlatformConfig `gorm:"serializer:json" json:"config,omitempty"`
	LastMessageTime  *time.Time `json:"last_message_time"`
	TotalMessages    int64     `gorm:"default:0" json:"total_messages"`
	CreatedAt        time.Time `json:"created_at"`
	UpdatedAt        time.Time `json:"updated_at"`
	DeletedAt        time.Time `gorm:"index" json:"-"`

	Room *Room `gorm:"-" json:"room,omitempty"`
}

func (LivePlatform) TableName() string {
	return "ddwx_xiaozhi_live_platforms"
}

// PlatformConfig 平台特定配置
type PlatformConfig struct {
	Cookie          string `json:"cookie,omitempty"`
	Token           string `json:"token,omitempty"`
	ExtraHeaders    map[string]string `json:"extra_headers,omitempty"`
	ProxyAddr       string `json:"proxy_addr,omitempty"`
	AutoReconnect   bool   `json:"auto_reconnect"`
	ReconnectInterval int  `json:"reconnect_interval"` // seconds
}

// DanmakuSettings 弹幕过滤设置（按直播间配置）
type DanmakuSettings struct {
	EnableSensitiveFilter bool                `json:"enable_sensitive_filter"`
	EnableKeywordDetect   bool                `json:"enable_keyword_detect"`
	EnableSentimentAnalyze bool               `json:"enable_sentiment_analyze"`
	KeywordsBlacklist     []string            `json:"keywords_blacklist"`
	KeywordsWhitelist     []string            `json:"keywords_whitelist"`
	SentimentThreshold    float64             `json:"sentiment_threshold"` // 负面情绪阈值
	AutoReplyEnabled      bool                `json:"auto_reply_enabled"`
	AutoReplyRules        []AutoReplyRule     `json:"auto_reply_rules"`
	BlockUsers            []string            `json:"block_users"`
}

// AutoReplyRule 自动回复规则
type AutoReplyRule struct {
	ID          string `json:"id"`
	Name        string `json:"name"`
	Type        string `json:"type"` // keyword | sentiment | gift | follow
	Pattern     string `json:"pattern"`
	ReplyText   string `json:"reply_text"`
	ReplyType   string `json:"reply_type"` // text | tts | action
	Enabled     bool   `json:"enabled"`
	Priority    int    `json:"priority"`
}

// RoomSessionConfig 直播间会话配置
type RoomSessionConfig struct {
	MaxContextMessages int `json:"max_context_messages"`
	ContextTTLSeconds  int `json:"context_ttl_seconds"`
	SummaryThreshold   int `json:"summary_threshold"`
	WelcomeMessage     string `json:"welcome_message"`
	GiftReplyTemplate  string `json:"gift_reply_template"`
	FollowReplyText    string `json:"follow_reply_text"`
}

// ModelConfig LLM 模型配置（存入数据库，供直播间绑定）
type ModelConfig struct {
	ID               uint      `gorm:"primaryKey" json:"id"`
	Name             string    `gorm:"size:100;not null" json:"name"`
	Provider         string    `gorm:"size:30;not null" json:"provider"`
	ModelID          string    `gorm:"size:100;not null" json:"model_id"`
	APIEndpoint      string    `gorm:"size:255" json:"api_endpoint"`
	MaxTokens        int       `gorm:"default:4096" json:"max_tokens"`
	Temperature      float64   `gorm:"default:0.7" json:"temperature"`
	TopP             float64   `gorm:"default:1.0" json:"top_p"`
	Params           string    `gorm:"type:text;json" json:"params"`
	SupportsStream   bool      `gorm:"default:true" json:"supports_stream"`
	CostPer1KTokens  float64   `gorm:"default:0" json:"cost_per_1k_tokens"`
	IsDefault        bool      `gorm:"default:false" json:"is_default"`
	AID              uint      `gorm:"index" json:"aid"`
	CreatedAt        time.Time `json:"created_at"`
	UpdatedAt        time.Time `json:"updated_at"`
	DeletedAt        time.Time `gorm:"index" json:"-"`
}

func (ModelConfig) TableName() string {
	return "ddwx_xiaozhi_model_configs"
}

// KnowledgeBase 知识库模型
type KnowledgeBase struct {
	ID              uint       `gorm:"primaryKey" json:"id"`
	Name            string     `gorm:"size:100;not null" json:"name"`
	Description     string     `gorm:"size:500" json:"description"`
	CollectionName  string     `gorm:"size:100;uniqueIndex" json:"collection_name"`
	EmbeddingModel  string     `gorm:"size:100;default:text-embedding-ada-002" json:"embedding_model"`
	Dimension       int        `gorm:"default:1536" json:"dimension"`
	ChunkSize       int        `gorm:"default:512;comment:分块大小(字符)" json:"chunk_size"`
	ChunkOverlap    int        `gorm:"default:50;comment:分块重叠(字符)" json:"chunk_overlap"`
	DocumentCount   int        `gorm:"default:0" json:"document_count"`
	VectorCount     int64      `gorm:"default:0" json:"vector_count"`
	Status          int        `gorm:"default:1;comment:1-就绪 2-索引中 3-错误" json:"status"`
	AID             uint       `gorm:"index" json:"aid"`
	CreatedAt       time.Time  `json:"created_at"`
	UpdatedAt       time.Time  `json:"updated_at"`
	DeletedAt       time.Time  `gorm:"index" json:"-"`
}

func (KnowledgeBase) TableName() string {
	return "ddwx_xiaozhi_knowledge_bases"
}

// KnowledgeDocument 知识库文档
type KnowledgeDocument struct {
	ID            uint      `gorm:"primaryKey" json:"id"`
	KnowledgeBaseID uint    `gorm:"index;not null" json:"knowledge_base_id"`
	Title         string    `gorm:"size:255" json:"title"`
	FileName      string    `gorm:"size:255" json:"file_name"`
	FileType      string    `gorm:"size:20;json:"file_type"`
FileSize       int64     `json:"file_size"`
	FilePath      string    `gorm:"size:500" json:"file_path"`
	Content       string    `gorm:"type:longtext" json:"content"`
	ChunkCount    int       `gorm:"default:0" json:"chunk_count"`
	Status        int       `gorm:"default:1;comment:0-待处理 1-已解析 2-向量化完成 3-失败" json:"status"`
	ErrorMessage  string    `gorm:"size:500" json:"error_message"`
	CreatedAt     time.Time `json:"created_at"`
	UpdatedAt     time.Time `json:"updated_at"`
	DeletedAt     time.Time `gorm:"index" json:"-"`
}

func (KnowledgeDocument) TableName() string {
	return "ddwx_xiaozhi_kb_documents"
}

// DialogHistory 对话历史记录
type DialogHistory struct {
	ID        uint      `gorm:"primaryKey" json:"id"`
	RoomID    uint      `gorm:"index;not null" json:"room_id"`
	SessionID string    `gorm:"size:64;index" json:"session_id"`
	UserID    string    `gorm:"size:100;index;json:"user_id"` // 弹幕发送者用户ID
	UserName  string    `gorm:"size:100" json:"user_name"`
	Role      string    `gorm:"size:10;not null;enum:user,assistant,system" json:"role"`
	Content   string    `gorm:"type:text;not null" json:"content"`
	TokenUsed int       `gorm:"default:0" json:"token_used"`
	ModelUsed string    `gorm:"size:100" json:"model_used"`
	LatencyMs int       `gorm:"default:0" json:"latency_ms"`
	Sentiment string    `gorm:"size:20" json:"sentiment"` // positive/neutral/negative
	Source    string    `gorm:"size:20;json:"source"`     // danmaku/device/manual
	AID       uint      `gorm:"index" json:"aid"`
CreatedAt time.Time `json:"created_at"`
}

func (DialogHistory) TableName() string {
	return "ddwx_xiaozhi_dialog_history"
}

// DanmakuLog 弹幕日志（用于统计和分析）
type DanmakuLog struct {
	ID           uint      `gorm:"primaryKey" json:"id"`
	RoomID       uint      `gorm:"index;not null" json:"room_id"`
	PlatformID   uint      `gorm:"index" json:"platform_id"`
	Platform     string    `gorm:"size:20" json:"platform"`
	UserID       string    `gorm:"size:100;index" json:"user_id"`
	UserName     string    `gorm:"size:100" json:"user_name"`
	Content      string    `gorm:"type:text;not null" json:"content"`
	MessageType  string    `gorm:"size:20;default:text" json:"message_type"` // text/gift/like/follow
	GiftName     string    `gorm:"size:100" json:"gift_name"`
	GiftCount    int64     `gorm:"default:0" json:"gift_count"`
	IsFiltered   bool      `gorm:"default:false;comment:是否被过滤" json:"is_filtered"`
	FilterReason string    `gorm:"size:100" json:"filter_reason"` // sensitive/keyword/rate_limit
	Sentiment    string    `gorm:"size:20" json:"sentiment"`
	Replied      bool      `gorm:"default:false" json:"replied"`
	ReplyDelayMs int       `gorm:"default:0" json:"reply_delay_ms"`
	CreatedAt    time.Time `json:"created_at"`
}

func (DanmakuLog) TableName() string {
	return "ddwx_xiaozhi_danmaku_logs"
}
