package api

import (
	"fmt"
	"net/http"
	"runtime/debug"
	"strings"
	"sync"
	"time"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"github.com/ai-eivie/xiaozhi-cloud/pkg/logger"
	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
	"go.uber.org/zap"
)

// corsMiddleware 跨域中间件
func corsMiddleware(cfg *config.Config) gin.HandlerFunc {
	return func(c *gin.Context) {
		origin := c.GetHeader("Origin")

		allowed := false
		for _, allowedOrigin := range cfg.Server.CORSOrigins {
			if strings.HasSuffix(allowedOrigin, "*") || origin == allowedOrigin {
				allowed = true
				break
			}
		}

		if allowed {
			c.Header("Access-Control-Allow-Origin", origin)
			c.Header("Access-Control-Allow-Credentials", "true")
		}

		c.Header("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS, PATCH")
		c.Header("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Requested-With")
		c.Header("Access-Control-Max-Age", "86400")

		if c.Request.Method == http.MethodOptions {
			c.AbortWithStatus(http.StatusNoContent)
			return
		}

		c.Next()
	}
}

// jwtAuth JWT 认证中间件
func jwtAuth(secret string) gin.HandlerFunc {
	return func(c *gin.Context) {
		tokenStr := c.GetHeader("Authorization")
		if tokenStr == "" || !strings.HasPrefix(tokenStr, "Bearer ") {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{
				"code":    401,
				"message": "未提供认证令牌",
			})
			return
		}

		tokenStr = strings.TrimPrefix(tokenStr, "Bearer ")

		token, err := jwt.Parse(tokenStr, func(t *jwt.Token) (interface{}, error) {
			if _, ok := t.Method.(*jwt.SigningMethodHMAC); !ok {
				return nil, fmt.Errorf("unexpected signing method: %v", t.Header["alg"])
			}
			return []byte(secret), nil
		})

		if err != nil || !token.Valid {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{
				"code":    401,
				"message": "无效或过期的认证令牌",
			})
			return
		}

		claims, ok := token.Claims.(jwt.MapClaims)
		if !ok {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{"code": 401, "message": "无效的令牌格式"})
			return
		}

		c.Set("user_id", claims["sub"])
		c.Set("aid", claims["aid"])
		c.Set("username", claims["username"])
		c.Next()
	}
}

// requestLogger 请求日志中间件
func requestLogger() gin.HandlerFunc {
	return func(c *gin.Context) {
		start := time.Now()
		path := c.Request.URL.Path

		c.Next()

		latency := time.Since(start).Milliseconds()
		status := c.Writer.Status()

		logFields := []zap.Field{
			zap.String("method", c.Request.Method),
			zap.String("path", path),
			zap.Int("status", status),
			zap.Int64("latency_ms", latency),
			zap.String("client_ip", c.ClientIP()),
		}

		if status >= 500 {
			logger.Error("HTTP请求错误", logFields...)
		} else if status >= 400 {
			logger.Warn("HTTP请求警告", logFields...)
		} else if path != "/health" && path != "/api/v1/stats/gateway" {
			logger.Debug("HTTP请求", logFields...)
		}
	}
}

// recovery 异常恢复中间件
func recovery() gin.HandlerFunc {
	return func(c *gin.Context) {
		defer func() {
			if r := recover(); r != nil {
				logger.Error("PANIC RECOVERED",
					zap.Any("error", r),
					zap.String("path", c.Request.URL.Path),
					zap.String("stack", string(debug.Stack())),
				)
				c.AbortWithStatusJSON(http.StatusInternalServerError, gin.H{
					"code":    500,
					"message": "服务器内部错误",
				})
			}
		}()
		c.Next()
	}
}

// limiter 简单限流中间件（基于内存，生产环境建议用 Redis）
var (
	rateLimitMap sync.Map // map[string]rateLimitEntry
)

type rateLimitEntry struct {
	count   int
	resetAt time.Time
}

func limiter() gin.HandlerFunc {
	const maxRequests = 200 // 每10秒最大请求数
	const windowSeconds = 10

	return func(c *gin.Context) {
		clientIP := c.ClientIP()

		var entry rateLimitEntry
		if val, ok := rateLimitMap.Load(clientIP); ok {
			entry = val.(rateLimitEntry)
		}

		now := time.Now()
		if now.After(entry.resetAt) {
			entry = rateLimitEntry{count: 1, resetAt: now.Add(time.Duration(windowSeconds) * time.Second)}
		} else {
			entry.count++
		}

		rateLimitMap.Store(clientIP, entry)

		if entry.count > maxRequests {
			c.Header("X-RateLimit-Limit", fmt.Sprintf("%d", maxRequests))
			c.Header("X-RateLimit-Remaining", "0")
			c.AbortWithStatusJSON(429, gin.H{
				"code":    429,
				"message": "请求过于频繁，请稍后再试",
			})
			return
		}

		c.Header("X-RateLimit-Limit", fmt.Sprintf("%d", maxRequests))
		c.Header("X-RateLimit-Remaining", fmt.Sprintf("%d", maxRequests-entry.count))
		c.Next()
	}
}
