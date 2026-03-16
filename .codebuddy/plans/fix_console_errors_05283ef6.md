---
name: fix_console_errors
overview: 修复后台页面控制台错误：解决 showsubnav 函数未定义错误和 WebSocket 连接问题
todos:
  - id: fix-showsubnav-error
    content: 修复 showsubnav 函数定义位置，确保页面加载时函数已定义
    status: completed
  - id: create-websocket-server
    content: 创建 WebSocket 服务器端脚本（Workerman）
    status: completed
    dependencies:
      - fix-showsubnav-error
  - id: config-nginx-websocket
    content: 配置 Nginx WebSocket 代理（wss）
    status: completed
    dependencies:
      - create-websocket-server
  - id: verify-websocket-connection
    content: 验证 WebSocket 连接是否正常
    status: completed
    dependencies:
      - config-nginx-websocket
---

## 用户需求

修复控制台错误并部署 WebSocket 服务

## 核心功能

1. **修复 showsubnav is not defined 错误** - 偶尔在页面首次加载时出现
2. **修复 WebSocket 连接失败错误** - 需要部署 WebSocket 服务器实现实时消息功能
3. **修复 shortcut-menu-box 元素不存在警告** - 已有防御代码，保持现状

## 涉及文件

- /home/www/ai.eivie.cn/app/view/backstage/index.html - 后台首页模板
- 需要创建 WebSocket 服务器脚本

## 技术方案

### 问题分析

#### 1. showsubnav 错误

- **原因**: 函数 `showsubnav` 定义在 HTML 底部 `<script>` 标签中（第 358 行），但在 HTML 中通过 `onclick` 直接调用（第 277 行）
- **解决方案**: 将 `showsubnav`、`hidesubnav` 等关键函数的定义移到 layui 库加载之前，使用 `window.onload` 或 DOMContentLoaded 确保 DOM 加载完成后再绑定事件

#### 2. WebSocket 部署

- **当前状态**: 项目中不存在 WebSocket 服务器端代码
- **需要实现**: 
- 创建 PHP WebSocket 服务器脚本（基于 Workerman 或 Swoole）
- 配置 Nginx WebSocket 代理
- 前端代码已有连接逻辑，只需确保服务器可用

### 架构设计

- WebSocket 服务器采用 Workerman（PHP）实现，与现有 PHP 项目无缝集成
- Nginx 反向代理处理 wss:// 加密连接
- 前端使用长连接心跳机制保持在线状态