package logger

import (
	"os"
	"sync"
	"time"

	"go.uber.org/zap"
	"go.uber.org/zap/zapcore"
)

// Options 日志初始化选项
type Options struct {
	Level      string
	Format     string // json | console
	Output     string // stdout | file
	FilePath   string
	MaxSize    int    // MB
	MaxBackups int
	MaxAge     int    // days
	Compress   bool
	TimeFormat string
}

// New 创建新的 zap logger
func New(opts Options) (*zap.Logger, error) {
	level, err := zapcore.ParseLevel(opts.Level)
	if err != nil {
		level = zapcore.InfoLevel
	}

	encoderConfig := zapcore.EncoderConfig{
		TimeKey:        "ts",
		LevelKey:       "level",
		NameKey:        "logger",
		CallerKey:      "caller",
		FunctionKey:    zapcore.OmitKey,
		MessageKey:     "msg",
		StacktraceKey:  "stacktrace",
		LineEnding:     zapcore.DefaultLineEnding,
		EncodeLevel:    zapcore.CapitalColorLevelEncoder,
		EncodeTime:     zapcore.TimeEncoderOfLayout(time.RFC3339),
		EncodeDuration: zapcore.StringDurationEncoder,
		EncodeCaller:   zapcore.ShortCallerEncoder,
	}

	var encoder zapcore.Encoder
	if opts.Format == "json" {
		encoderConfig.EncodeLevel = zapcore.LowercaseLevelEncoder
		encoder = zapcore.NewJSONEncoder(encoderConfig)
	} else {
		encoder = zapcore.NewConsoleEncoder(encoderConfig)
	}

	var writer zapcore.WriteSyncer
	switch opts.Output {
	case "file":
		file, fileErr := os.OpenFile(opts.FilePath, os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0644)
		if fileErr != nil {
			return nil, fileErr
		}
		writer = zapcore.AddSync(file)
	default:
		writer = zapcore.AddSync(os.Stdout)
	}

	core := zapcore.NewCore(
		encoder,
		writer,
		level,
	)

	logger := zap.New(core, zap.AddCaller(), zap.AddStacktrace(zapcore.ErrorLevel))
	return logger, nil
}

// Global 全局日志实例（线程安全）
var (
	globalLogger *zap.Logger
	loggerOnce   sync.Once
)

// SetGlobal 设置全局日志实例
func SetGlobal(l *zap.Logger) {
	loggerOnce.Do(func() {
		globalLogger = l.WithOptions(zap.AddCallerSkip(1))
	})
}

// Global 获取全局日志实例
func Global() *zap.Logger {
	return globalLogger
}

// Debug ...
func Debug(msg string, fields ...zap.Field) {
	if globalLogger != nil {
		globalLogger.Debug(msg, fields...)
	}
}

// Info ...
func Info(msg string, fields ...zap.Field) {
	if globalLogger != nil {
		globalLogger.Info(msg, fields...)
	}
}

// Warn ...
func Warn(msg string, fields ...zap.Field) {
	if globalLogger != nil {
		globalLogger.Warn(msg, fields...)
	}
}

// Error ...
func Error(msg string, fields ...zap.Field) {
	if globalLogger != nil {
		globalLogger.Error(msg, fields...)
	}
}

// Fatal ...
func Fatal(msg string, fields ...zap.Field) {
	if globalLogger != nil {
		globalLogger.Fatal(msg, fields...)
	}
}

// With 创建带字段的子 logger
func With(fields ...zap.Field) *zap.Logger {
	if globalLogger != nil {
		return globalLogger.With(fields...)
	}
	return globalLogger
}
