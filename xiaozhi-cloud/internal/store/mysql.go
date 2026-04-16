package store

import (
	"fmt"
	"time"

	"github.com/ai-eivie/xiaozhi-cloud/internal/config"
	"gorm.io/driver/mysql"
	"gorm.io/gorm"
	"gorm.io/gorm/logger"
)

var DB *gorm.DB

// InitDB 初始化 MySQL 数据库连接
func InitDB(cfg *config.Config) (*gorm.DB, error) {
	gormCfg := &gorm.Config{
		Logger: logger.Default.LogMode(logger.Info),
	}

	if cfg.Server.Env == "production" {
		gormCfg.Logger = logger.Default.LogMode(logger.Warn)
	}

	db, err := gorm.Open(mysql.Open(cfg.Database.DSN()), gormCfg)
	if err != nil {
		return nil, fmt.Errorf("连接数据库失败: %w", err)
	}

	sqlDB, err := db.DB()
	if err != nil {
		return nil, fmt.Errorf("获取底层 sql.DB 失败: %w", err)
	}

	sqlDB.SetMaxOpenConns(cfg.Database.MaxOpenConns)
	sqlDB.SetMaxIdleConns(cfg.Database.MaxIdleConns)
	sqlDB.SetConnMaxLifetime(time.Duration(cfg.Database.ConnMaxLifetime) * time.Second)

	DB = db

	// 自动迁移数据表
	if err := autoMigrate(db); err != nil {
		return nil, fmt.Errorf("数据库自动迁移失败: %w", err)
	}

	return db, nil
}

// autoMigrate 自动迁移数据表结构
func autoMigrate(db *gorm.DB) error {
	// 禁用外键约束，避免类型不匹配问题
	db.Exec("SET FOREIGN_KEY_CHECKS=0")
	defer db.Exec("SET FOREIGN_KEY_CHECKS=1")
	
	return db.AutoMigrate(
		&Store{},
		&Room{},
		&Device{},
		&LivePlatform{},
		&ModelConfig{},
		&KnowledgeBase{},
		&KnowledgeDocument{},
		&DialogHistory{},
		&DanmakuLog{},
	)
}
