# AI旅拍商家客户端 - Electron版

> 基于 Electron + Vue 3 + TypeScript 的跨平台桌面应用

## 项目简介

AI旅拍商家客户端（Electron版）是一款跨平台桌面应用，用于商家端自动监控文件夹、检测照片文件并自动上传至服务器进行AI处理。相比原有的 .NET WPF 版本，本版本支持 Windows、macOS、Linux 三大平台，采用现代化Web技术栈开发，降低维护成本，提升用户体验。

## 核心功能

- ✅ **设备注册与认证** - 设备码注册、Token管理、在线状态维护
- ✅ **文件实时监控** - 多文件夹监控、文件过滤、稳定性检测、MD5去重
- ✅ **自动上传** - 并发上传、断点续传、失败重试、进度展示
- ✅ **心跳保活** - 定时心跳、自动重连、状态同步
- ✅ **配置管理** - 本地配置持久化、敏感信息加密
- ✅ **日志系统** - 分级日志、文件切分、过期清理
- ✅ **系统托盘** - 后台运行、快捷操作、状态提示
- ✅ **统计分析** - 上传统计、速度监控、成功率分析

## 技术栈

| 技术领域 | 技术选型 | 版本要求 |
|---------|---------|----------|
| 框架 | Electron | ^27.0.0 |
| UI框架 | Vue 3 | ^3.3.0 |
| UI组件库 | Element Plus | ^2.4.0 |
| 开发语言 | TypeScript | ^5.2.0 |
| 构建工具 | Vite | ^4.5.0 |
| 状态管理 | Pinia | ^2.1.0 |
| HTTP客户端 | Axios | ^1.6.0 |
| 文件监控 | Chokidar | ^3.5.3 |
| 日志管理 | Electron-log | ^5.0.0 |
| 配置存储 | Electron-store | ^8.1.0 |

## 系统要求

### Windows
- Windows 10 或更高版本
- 64位 或 32位操作系统

### macOS
- macOS 10.13 (High Sierra) 或更高版本
- Intel 或 Apple Silicon (M1/M2) 芯片

### Linux
- 主流发行版（Ubuntu 18.04+、Debian 10+、Fedora 32+）
- 64位操作系统

## 快速开始

### 安装依赖

```bash
cd /www/wwwroot/eivie/sjkhd
npm install
# 或使用 yarn
yarn install
# 或使用 pnpm
pnpm install
```

### 配置环境变量

复制 `.env.example` 为 `.env`，并配置相关参数：

```bash
cp .env.example .env
```

编辑 `.env` 文件：

```env
API_BASE_URL=https://your-api-server.com
API_TIMEOUT=120000
APP_NAME=AI旅拍商家客户端
APP_VERSION=1.0.0
NODE_ENV=development
```

### 开发模式运行

```bash
npm run dev
```

应用将在开发模式下启动，支持热重载。

### 构建生产版本

```bash
# 构建所有平台
npm run dist

# 仅构建 Windows
npm run dist:win

# 仅构建 macOS
npm run dist:mac

# 仅构建 Linux
npm run dist:linux
```

构建产物将输出到 `dist/release/` 目录。

## 项目结构

```
sjkhd/
├── main/                       # 主进程代码
│   ├── index.ts                # 主进程入口
│   ├── window.ts               # 窗口管理
│   ├── tray.ts                 # 系统托盘
│   ├── ipc-handlers.ts         # IPC处理器
│   ├── services/               # 主进程服务
│   ├── models/                 # 数据模型
│   └── utils/                  # 工具函数
├── preload/                    # 预加载脚本
│   └── index.ts                # IPC桥接
├── renderer/                   # 渲染进程代码
│   ├── main.ts                 # Vue应用入口
│   ├── App.vue                 # 根组件
│   ├── views/                  # 页面视图
│   ├── components/             # 通用组件
│   ├── stores/                 # 状态管理
│   ├── api/                    # API封装
│   ├── types/                  # 类型定义
│   └── styles/                 # 样式文件
├── resources/                  # 应用资源
├── docs/                       # 项目文档
└── package.json                # 项目配置
```

## 开发指南

### 代码规范

- 遵循 ESLint 配置的代码规范
- 使用 TypeScript 进行类型安全开发
- 组件命名使用 PascalCase
- 工具函数命名使用 camelCase
- 常量命名使用 UPPER_SNAKE_CASE

### Git 提交规范

```bash
feat: 新功能
fix: 修复bug
docs: 文档更新
style: 代码格式调整
refactor: 重构代码
perf: 性能优化
test: 测试相关
chore: 构建/工具链相关
```

### 调试技巧

1. **主进程调试**：查看终端输出的日志
2. **渲染进程调试**：使用 Chrome DevTools（开发模式自动打开）
3. **IPC通信调试**：在 preload 和 ipc-handlers 中添加 console.log
4. **日志查看**：日志文件位于用户数据目录的 `logs/` 文件夹

## 部署指南

详见 [docs/部署指南.md](docs/部署指南.md)

## 用户手册

详见 [docs/用户手册.md](docs/用户手册.md)

## 常见问题

### 1. 应用无法启动

- 检查是否已安装所有依赖
- 检查 Node.js 版本是否满足要求（建议 16.x 或 18.x）
- 查看日志文件排查具体错误

### 2. 文件监控不工作

- 确认监控路径是否正确
- 检查文件夹是否有读取权限
- 查看日志中的监控服务启动信息

### 3. 上传失败

- 检查网络连接
- 确认设备Token是否有效
- 查看后端API是否正常

### 4. 打包失败

- 确保已安装构建依赖
- Windows 打包需要在 Windows 系统上进行
- macOS 打包需要在 macOS 系统上进行
- Linux 打包建议在 Linux 系统上进行

## 更新日志

查看 [CHANGELOG.md](CHANGELOG.md)

## 许可证

MIT License

## 联系方式

- 技术支持：[support@example.com](mailto:support@example.com)
- 问题反馈：[GitHub Issues](https://github.com/your-repo/issues)

## 致谢

感谢所有为本项目做出贡献的开发者！

---

**注意**：本项目目前处于初始开发阶段，部分功能尚未完全实现。请参考任务列表和开发文档了解最新进展。
