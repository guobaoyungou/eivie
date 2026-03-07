# 快速开始指南

## 前置要求

- Node.js 16.x 或 18.x
- npm 或 yarn 或 pnpm

## 安装步骤

### 1. 进入项目目录

```bash
cd /www/wwwroot/eivie/sjkhd
```

### 2. 安装依赖

```bash
npm install
```

如果遇到网络问题，可以使用国内镜像：

```bash
npm install --registry=https://registry.npmmirror.com
```

### 3. 配置环境变量

```bash
cp .env.example .env
```

编辑 `.env` 文件，配置您的 API 服务器地址。

### 4. 启动开发模式

```bash
npm run dev
```

应用将在开发模式下启动，您将看到一个 Electron 窗口。

## 验证安装

启动后应该能看到：
1. ✅ Electron 窗口正常显示
2. ✅ 侧边栏导航菜单
3. ✅ 首页显示监控状态仪表板
4. ✅ 系统托盘图标（右下角/右上角）

## 下一步

- 查看 [README.md](../README.md) 了解更多信息
- 查看 [PROJECT_IMPLEMENTATION_SUMMARY.md](PROJECT_IMPLEMENTATION_SUMMARY.md) 了解项目进度
- 开始实现核心服务（阶段二）

## 常见问题

### 依赖安装失败

尝试清除缓存：
```bash
rm -rf node_modules package-lock.json
npm install
```

### Electron 下载失败

设置 Electron 镜像：
```bash
export ELECTRON_MIRROR=https://npmmirror.com/mirrors/electron/
npm install
```

### 端口被占用

修改 `vite.config.ts` 中的端口号（默认 5173）。

## 技术支持

如有问题，请查看项目文档或联系开发团队。
