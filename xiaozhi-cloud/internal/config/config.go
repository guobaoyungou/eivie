package config

import (
	"fmt"
	"os"
	"strings"

	"gopkg.in/yaml.v3"
)

// Config 全局配置结构
type Config struct {
	Server   ServerConfig   `yaml:"server"`
	Database DatabaseConfig `yaml:"database"`
	Redis    RedisConfig    `yaml:"redis"`
	Milvus   MilvusConfig   `yaml:"milvus"`
	LLM      LLMConfig      `yaml:"llm"`
	TTS      TTSConfig      `yaml:"tts"`
	Danmaku  DanmakuConfig  `yaml:"danmaku"`
	Gateway  GatewayConfig  `yaml:"gateway"`
	Session  SessionConfig  `yaml:"session"`
	Log      LogConfig      `yaml:"log"`
}

type ServerConfig struct {
	Name         string   `yaml:"name"`
	Env          string   `yaml:"env"`
	WSPort       int      `yaml:"ws_port"`
	HTTPPort     int      `yaml:"http_port"`
	JWTSecret    string   `yaml:"jwt_secret"`
	JWTExpireHrs int      `yaml:"jwt_expire_hours"`
	CORSOrigins  []string `yaml:"cors_origins"`
}

type DatabaseConfig struct {
	Host            string `yaml:"host"`
	Port            int    `yaml:"port"`
	Username        string `yaml:"username"`
	Password        string `yaml:"password"`
	DBName          string `yaml:"dbname"`
	Prefix          string `yaml:"prefix"`
	MaxOpenConns    int    `yaml:"max_open_conns"`
	MaxIdleConns    int    `yaml:"max_idle_conns"`
	ConnMaxLifetime int    `yaml:"conn_max_lifetime"`
	Charset         string `yaml:"charset"`
}

type RedisConfig struct {
	Addr         string `yaml:"addr"`
	Password     string `yaml:"password"`
	DB           int    `yaml:"db"`
	PoolSize     int    `yaml:"pool_size"`
	MinIdleConns int    `yaml:"min_idle_conns"`
}

type MilvusConfig struct {
	Host             string `yaml:"host"`
	Port             int    `yaml:"port"`
	RestPort         int    `yaml:"rest_port"`
	CollectionPrefix string `yaml:"collection_prefix"`
	Dimension        int    `yaml:"dimension"`
	IndexType        string `yaml:"index_type"`
	Nlist            int    `yaml:"nlist"`
	MetricType       string `yaml:"metric_type"`
	Timeout          int    `yaml:"timeout"`
}

type LLMConfig struct {
	DefaultProvider string                   `yaml:"default_provider"`
	RequestTimeout  int                      `yaml:"request_timeout"`
	StreamTimeout   int                      `yaml:"stream_timeout"`
	MaxRetries      int                      `yaml:"max_retries"`
	Providers       map[string]ProviderConfig `yaml:"providers"`
}

type ProviderConfig struct {
	Name    string      `yaml:"name"`
	APIKey  string      `yaml:"api_key"`
	BaseURL string      `yaml:"base_url"`
	Models  []ModelInfo `yaml:"models"`
}

type ModelInfo struct {
	ID              string  `yaml:"id"`
	DisplayName     string  `yaml:"display_name"`
	MaxTokens       int     `yaml:"max_tokens"`
	SupportsStream  bool    `yaml:"supports_stream"`
	CostPer1KInput  float64 `yaml:"cost_per_1k_input"`
	CostPer1KOutput float64 `yaml:"cost_per_1k_output"`
}

type TTSConfig struct {
	DefaultProvider string                `yaml:"default_provider"`
	Providers       map[string]TTSProvider `yaml:"providers"`
}

type TTSProvider struct {
	Name        string `yaml:"name"`
	Voice       string `yaml:"voice"`
	Rate        string `yaml:"rate"`
	Volume      string `yaml:"volume"`
	AppID       string `yaml:"app_id"`
	AccessToken string `yaml:"access_token"`
	Cluster     string `yaml:"cluster"`
}

type DanmakuConfig struct {
	SensitiveWordsFile string          `yaml:"sensitive_words_file"`
	Sentiment          SentimentConfig `yaml:"sentiment"`
	RateLimit          RateLimitConfig `yaml:"rate_limit"`
}

type SentimentConfig struct {
	Enabled  bool   `yaml:"enabled"`
	Provider string `yaml:"provider"`
	LLMModel string `yaml:"llm_model"`
}

type RateLimitConfig struct {
	Enabled    bool `yaml:"enabled"`
	QPSPerrRoom int `yaml:"qps_per_room"`
	QPSPerUser int  `yaml:"qps_per_user"`
	Burst      int  `yaml:"burst"`
}

type GatewayConfig struct {
	HeartbeatInterval int    `yaml:"heartbeat_interval"`
	HeartbeatTimeout  int    `yaml:"heartbeat_timeout"`
	WriteWait         int    `yaml:"write_wait"`
	ReadWait          int    `yaml:"read_wait"`
	MaxMessageSize    int64  `yaml:"max_message_size"`
	PingMessage       string `yaml:"ping_message"`
	PongMessage       string `yaml:"pong_message"`
	MaxConnections    int    `yaml:"max_connections"`
	MaxRooms          int    `yaml:"max_rooms"`
	MaxDevicesPerRoom int    `yaml:"max_devices_per_room"`
}

type SessionConfig struct {
	MaxContextMessages int `yaml:"max_context_messages"`
	ContextTTL         int `yaml:"context_ttl"`
	SummaryThreshold   int `yaml:"summary_threshold"`
	HistoryRetainDays  int `yaml:"history_retain_days"`
}

type LogConfig struct {
	Level      string `yaml:"level"`
	Format     string `yaml:"format"`
	Output     string `yaml:"output"`
	FilePath   string `yaml:"file_path"`
	MaxSize    int    `yaml:"max_size"`
	MaxBackups int    `yaml:"max_backups"`
	MaxAge     int    `yaml:"max_age"`
	Compress   bool   `yaml:"compress"`
}

var globalConfig *Config

// Load 加载配置文件
func Load(path string) (*Config, error) {
	data, err := os.ReadFile(path)
	if err != nil {
		return nil, fmt.Errorf("读取配置文件失败: %w", err)
	}

	cfg := &Config{}
	if err := yaml.Unmarshal(data, cfg); err != nil {
		return nil, fmt.Errorf("解析 YAML 配置失败: %w", err)
	}
	
	// 处理环境变量替换
	resolveEnvVars(cfg)

	globalConfig = cfg
	return cfg, nil
}

// Get 获取全局配置
func Get() *Config {
	return globalConfig
}

// resolveEnvVars 替换配置中的环境变量占位符 ${VAR_NAME}
func resolveEnvVars(cfg *Config) {
	cfg.LLM.Providers = replaceProviderKeys(cfg.LLM.Providers)
	cfg.TTS.Providers = replaceTTSKeys(cfg.TTS.Providers)
}

func replaceProviderKeys(providers map[string]ProviderConfig) map[string]ProviderConfig {
	result := make(map[string]ProviderConfig)
	for k, v := range providers {
		v.APIKey = os.ExpandEnv(v.APIKey)
		result[k] = v
	}
	return result
}

func replaceTTSKeys(providers map[string]TTSProvider) map[string]TTSProvider {
	result := make(map[string]TTSProvider)
	for k, v := range providers {
		v.AppID = os.ExpandEnv(v.AppID)
		v.AccessToken = os.ExpandEnv(v.AccessToken)
		result[k] = v
	}
	return result
}

// IsProduction 是否生产环境
func (c *ServerConfig) IsProduction() bool {
	return c.Env == "production"
}

// DSN 返回 MySQL 连接字符串
func (d *DatabaseConfig) DSN() string {
	return fmt.Sprintf("%s:%s@tcp(%s:%d)/%s?charset=%s&parseTime=True&loc=Local",
		d.Username, d.Password, d.Host, d.Port, d.DBName, d.Charset)
}

// TablePrefix 返回带前缀的表名
func (d *DatabaseConfig) TablePrefix(table string) string {
	return d.Prefix + table
}

// MilvusEndpoint 返回 Milvus gRPC 地址
func (m *MilvusConfig) Endpoint() string {
	return fmt.Sprintf("%s:%d", m.Host, m.Port)
}

// CollectionName 返回知识库集合名称
func (m *MilvusConfig) CollectionName(kbID string) string {
	sanitized := strings.ReplaceAll(kbID, "-", "_")
	return m.CollectionPrefix + sanitized
}
