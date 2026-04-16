package main

import (
	"context"
	"flag"
	"fmt"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/ai-eivie/xiaozhi-cloud/api"
	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"github.com/ai-eivie/xiaozhi-cloud/internal/gateway"
	"github.com/ai-eivie/xiaozhi-cloud/internal/store"
	"github.com/ai-eivie/xiaozhi-cloud/pkg/logger"
	"go.uber.org/zap"
)

func main() {
	// 解析命令行参数
	configPath := flag.String("config", "configs/config.yaml", "配置文件路径")
	flag.Parse()

	// 初始化日志
	log, err := logger.New(logger.Options{
		Level:      "info",
		Format:     "console",
		Output:     "stdout",
		FilePath:   "./logs/xiaozhi-cloud.log",
		MaxSize:    100,
		MaxBackups: 7,
		MaxAge:     30,
		Compress:   true,
		TimeFormat: time.RFC3339,
	})
	if err != nil {
		fmt.Fprintf(os.Stderr, "初始化日志失败: %v\n", err)
		os.Exit(1)
	}
	defer log.Sync()
	
	logger.SetGlobal(log)

	// 加载配置
	cfg, err := config.Load(*configPath)
	if err != nil {
		logger.Fatal("加载配置文件失败", zap.Error(err), zap.String("path", *configPath))
	}
	logger.Info("配置文件加载成功",
		zap.String("env", cfg.Server.Env),
		zap.Int("ws_port", cfg.Server.WSPort),
		zap.Int("http_port", cfg.Server.HTTPPort),
	)

	// 初始化数据库
	db, err := store.InitDB(cfg)
	if err != nil {
		logger.Fatal("初始化数据库连接失败", zap.Error(err))
	}
	logger.Info("数据库连接成功")

	// 初始化 Redis
	rdb, err := store.InitRedis(cfg)
	if err != nil {
		logger.Fatal("初始化 Redis 连接失败", zap.Error(err))
	}
	logger.Info("Redis 连接成功")

	// 初始化 WebSocket 网关
	wsGateway := gateway.New(cfg, db, rdb)
	go func() {
		addr := fmt.Sprintf(":%d", cfg.Server.WSPort)
		logger.Info("WebSocket 网关启动", zap.String("addr", addr))
		if err := wsGateway.Start(addr); err != nil && err != http.ErrServerClosed {
			logger.Fatal("WebSocket 网关启动失败", zap.Error(err))
		}
	}()

	// 初始化 HTTP API 服务
	httpServer := api.NewServer(cfg, db, rdb, wsGateway)
	go func() {
		addr := fmt.Sprintf(":%d", cfg.Server.HTTPPort)
		logger.Info("HTTP API 服务启动", zap.String("addr", addr))
		if err := httpServer.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			logger.Fatal("HTTP API 启动失败", zap.Error(err))
		}
	}()

	// 优雅关闭
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	logger.Info("正在优雅关闭服务...")

	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	// 关闭 HTTP 服务
	if err := httpServer.Shutdown(ctx); err != nil {
		logger.Error("HTTP 服务关闭失败", zap.Error(err))
	}

	// 关闭 WebSocket 网关
	wsGateway.Shutdown(ctx)

	// 关闭数据库
	sqlDB, _ := db.DB()
	sqlDB.Close()

	// 关闭 Redis
	rdb.Close()

	logger.Info("服务已安全退出")
}
