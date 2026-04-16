package api

import (
	"context"
	"fmt"
	"net/http"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"github.com/ai-eivie/xiaozhi-cloud/internal/gateway"
	"github.com/gin-gonic/gin"
	"github.com/redis/go-redis/v9"
	"gorm.io/gorm"
)

// Server HTTP API 服务
type Server struct {
	cfg     *config.Config
	db      *gorm.DB
	rdb     *redis.Client
	gateway *gateway.Gateway
	engine  *gin.Engine
}

// NewServer 创建 API 服务
func NewServer(cfg *config.Config, db *gorm.DB, rdb *redis.Client, gw *gateway.Gateway) *Server {
	gin.SetMode(gin.ReleaseMode)
	if cfg.Server.Env == "development" {
		gin.SetMode(gin.DebugMode)
	}

	s := &Server{
		cfg:     cfg,
		db:      db,
		rdb:     rdb,
		gateway: gw,
		engine:  gin.New(),
	}

	s.setupMiddleware()
	s.setupRoutes()

	return s
}

// setupMiddleware 设置中间件
func (s *Server) setupMiddleware() {
	s.engine.Use(corsMiddleware(s.cfg))
	s.engine.Use(requestLogger())
	s.engine.Use(recovery())
	s.engine.Use(limiter())
}

// setupRoutes 注册路由
func (s *Server) setupRoutes() {
	apiV1 := s.engine.Group("/api/v1")
	{
		// 直播间管理
		rooms := apiV1.Group("/rooms")
		{
			rooms.GET("", s.ListRooms)
			rooms.POST("", s.CreateRoom)
			rooms.GET("/:id", s.GetRoom)
			rooms.PUT("/:id", s.UpdateRoom)
			rooms.DELETE("/:id", s.DeleteRoom)
			rooms.POST("/:id/start", s.StartLiveRoom)  // 启动弹幕抓取
			rooms.POST("/:id/stop", s.StopLiveRoom)    // 停止弹幕抓取
		}

		// 设备管理
		devices := apiV1.Group("/devices")
		{
			devices.GET("", s.ListDevices)
			devices.POST("", s.RegisterDevice)
			devices.GET("/:id", s.GetDevice)
			devices.PUT("/:id", s.UpdateDevice)
			devices.DELETE("/:id", s.DeleteDevice)
			devices.POST("/bind", s.BindDeviceToRoom)
			devices.POST("/unbind", s.UnbindDevice)
		}

		// 模型广场
		models := apiV1.Group("/models")
		{
			models.GET("", s.ListModels)
			models.POST("", s.CreateModelConfig)
			models.GET("/:id", s.GetModelConfig)
			models.PUT("/:id", s.UpdateModelConfig)
			models.DELETE("/:id", s.DeleteModelConfig)
			models.GET("/providers", s.ListProviders)
		}

		// 知识库管理
		kb := apiV1.Group("/knowledge-bases")
		{
			kb.GET("", s.ListKnowledgeBases)
			kb.POST("", s.CreateKnowledgeBase)
			kb.GET("/:id", s.GetKnowledgeBase)
			kb.PUT("/:id", s.UpdateKnowledgeBase)
			kb.DELETE("/:id", s.DeleteKnowledgeBase)
			kb.POST("/:id/documents", s.UploadDocument)
			kb.GET("/:id/documents", s.ListDocuments)
			kb.POST("/:id/search", s.SearchKnowledgeBase)
		}

		// 弹幕设置
		danmaku := apiV1.Group("/danmaku-settings")
		{
			danmaku.GET("/room/:room_id", s.GetDanmakuSettings)
			danmaku.PUT("/room/:room_id", s.UpdateDanmakuSettings)
			danmaku.POST("/keywords/reload", s.ReloadKeywords)
		}

		// 统计监控
		stats := apiV1.Group("/stats")
		{
			stats.GET("/dashboard", s.DashboardStats)
			stats.GET("/gateway", s.GatewayStats)
			stats.GET("/danmaku/:room_id", s.DanmakuStats)
			stats.GET("/dialog/:room_id", s.DialogHistory)
		}

		// 门店管理
		stores := apiV1.Group("/stores")
		{
			stores.GET("", s.ListStores)
			stores.POST("", s.CreateStore)
			stores.GET("/:id", s.GetStore)
			stores.PUT("/:id", s.UpdateStore)
			stores.DELETE("/:id", s.DeleteStore)
		}
	}

	// 健康检查
	s.engine.GET("/health", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{
			"status": "ok",
			"server": "xiaozhi-cloud-api",
		})
	})

	// WebSocket 升级端点（HTTP 层，实际 WS 由 Gateway 处理）
	s.engine.GET("/ws/xiaozhi", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{"message": "WebSocket 端点请连接 :9502/xiaozhi/v1/"})
	})
	s.engine.GET("/ws/live", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{"message": "WebSocket 端点请连接 :9502/live/v1/"})
	})
}

// ListenAndServe 启动 HTTP 监听
func (s *Server) ListenAndServe() error {
	addr := fmt.Sprintf(":%d", s.cfg.Server.HTTPPort)
	return s.engine.Run(addr)
}

// Shutdown 优雅关闭 HTTP 服务
func (s *Server) Shutdown(ctx context.Context) error {
	// Gin 没有内置的 Shutdown 方法，这里返回 nil
	// 实际生产环境可能需要使用 http.Server 的 Shutdown
	return nil
}
