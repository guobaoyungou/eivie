# 小智云端 (XiaoZhi Cloud) - 全平台直播系统

基于 xiaozhi-esp32 开源生态的高并发 AI 直播互动云服务。

## 技术栈

- **核心语言**: Go 1.22+
- **通信协议**: WebSocket (gorilla/websocket)
- **Web框架**: Gin
- **ORM**: GORM
- **缓存**: Redis (go-redis)
- **LLM**: go-openai (统一接口适配多厂商)
- **日志**: zap
- **配置**: YAML

## 功能特性

### 核心能力
- 全平台直播弹幕接入（抖音/淘宝/视频号/快手）
- 高并发 WebSocket 网关（支持 10万+ 设备连接）
- 门店级多设备管理（Xmini-C3 ESP32-C3）
- LLM 模型广场（DeepSeek/通义千问/智谱/豆包/Gemini）
- 对话上下文管理与历史记录
- 智能消息路由引擎
- DFA 敏感词过滤 + 关键词检测 + 情绪分析
- RAG 知识库检索增强生成
- TTS 语音合成（EdgeTTS/火山引擎）

## 项目结构

```
xiaozhi-cloud/
├── cmd/server/main.go      # 服务入口
├── internal/               # 内部业务代码
│   ├── config/             # 配置管理
│   ├── protocol/           # xiaozhi-esp32 协议兼容层
│   ├── gateway/            # WebSocket 网关
│   ├── device/             # 设备/门店/直播间模型
│   ├── danmaku/            # 弹幕处理管线
│   ├── llm/                # LLM 模型广场
│   ├── session/            # 会话与对话管理
│   ├── knowledge/          # 知识库 RAG
│   ├── router/             # 消息路由引擎
│   ├── tts/                # 语音合成
│   └── store/              # 数据访问层
├── api/                    # HTTP REST API
├── pkg/                    # 公共工具库
├── scripts/                # 数据库脚本
├── deployments/            # Docker 部署配置
└── README.md
```

## 快速开始

### 环境要求

- Go 1.22+
- MySQL 8.0+
- Redis 7.0+
- Milvus 2.x（知识库功能）

### 开发环境启动

```bash
# 1. 克隆项目
cd /home/www/ai.eivie.cn/xiaozhi-cloud

# 2. 安装依赖
go mod download

# 3. 初始化数据库
mysql -u root -p guobaoyungou_cn < scripts/migrate.sql

# 4. 复制配置文件并编辑
cp configs/config.yaml.example configs/config.yaml
# 编辑 configs/config.yaml 填入实际配置

# 5. 启动服务
go run cmd/server/main.go

# 服务将在以下端口启动:
# - :9502  WebSocket 网关 (xiaozhi 协议 + 弹幕通道)
# - :9503  HTTP REST API (管理后台)
```

### Docker 部署

```bash
cd deployments
docker compose up -d
```

## 配置说明

主要配置项见 `configs/config.yaml`：

| 配置项 | 说明 |
|--------|------|
| `server.ws_port` | WebSocket 网关端口 (默认 9502) |
| `server.http_port` | HTTP API 端口 (默认 9503) |
| `database` | MySQL 连接配置 |
| `redis` | Redis 连接配置 |
| `milvus` | Milvus 向量数据库配置 |
| `llm.providers` | LLM 服务商 API Key 配置 |

## 协议兼容性

本服务完整实现 xiaozhi-esp32 的 WebSocket 通信协议，Xmini-C3 设备无需修改固件即可直连。

### 协议端点

- **设备连接**: `ws://host:9502/xiaozhi/v1/` (兼容 xiaozhi-esp32 协议)
- **弹幕通道**: `ws://host:9502/live/v1/` (扩展弹幕实时推送)
- **管理API**: `http://host:9503/api/v1/` (RESTful)

## 性能指标

| 指标 | 目标值 |
|------|--------|
| 同时在线设备数 | 10,000+ |
| 单机 WS 连接数 | 50,000+ |
| 弹幕处理延迟 | < 20ms P99 |
| AI 回复首包延迟 | < 1.5s |
| 并发直播间数量 | 1,000+ |

## License

MIT License
