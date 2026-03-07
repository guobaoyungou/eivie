# WebSocket 重试次数限制修复

## 问题描述

后台管理页面的 WebSocket 连接在无法连接到服务器时会不断重试，导致控制台出现大量错误信息：

```
WebSocket connection to 'wss://192.168.11.222/wss' failed
```

## 解决方案

限制 WebSocket 每天最多重试 10 次，超过 10 次后停止重试，避免无意义的重复连接尝试。

## 实现细节

### 修改文件
- `/www/wwwroot/eivie/app/view/backstage/index.html`

### 核心功能

1. **每日重试计数器**
   - 使用 `localStorage` 存储每天的重试记录
   - 记录格式：`{date: "日期字符串", count: 重试次数}`
   - 每天零点自动重置计数

2. **重试限制**
   - 每天最多重试 10 次
   - 达到上限后停止自动重连
   - 在控制台显示明确的提示信息

3. **连接成功重置**
   - 当连接成功建立后，自动重置重试计数为 0
   - 确保下次断线可以继续尝试重连

4. **心跳检测保护**
   - 定时心跳（25秒）也会检查重试上限
   - 达到上限后自动停止心跳检测

### 关键变量

```javascript
var wsRetryCount = 0;           // 当前重试次数
var wsMaxRetries = 10;          // 每天最多重试次数
var wsRetryDate = new Date().toDateString();  // 重试日期
```

### 核心函数

#### 获取今日重试次数
```javascript
function getRetryCount() {
    var today = new Date().toDateString();
    var stored = localStorage.getItem('ws_retry_data');
    if (stored) {
        try {
            var data = JSON.parse(stored);
            if (data.date === today) {
                return data.count;
            }
        } catch(e) {}
    }
    return 0;
}
```

#### 保存重试次数
```javascript
function saveRetryCount(count) {
    var today = new Date().toDateString();
    localStorage.setItem('ws_retry_data', JSON.stringify({
        date: today, 
        count: count
    }));
}
```

## 用户体验改进

### 控制台日志
1. **连接尝试时**
   ```
   WebSocket尝试连接... (今日第X次)
   ```

2. **连接成功时**
   ```
   WebSocket连接成功
   ```

3. **连接失败时**
   ```
   WebSocket连接错误 (今日已重试X次)
   ```

4. **达到重试上限时**
   ```
   WebSocket重试次数已达到今日上限(10次)，已停止重连
   已停止WebSocket心跳，今日重试次数已用完
   ```

## 行为说明

### 正常场景
1. 页面加载时尝试连接 WebSocket
2. 如果连接失败，自动重试（最多10次/天）
3. 每次重试记录到 localStorage
4. 连接成功后重置计数器

### 重试限制场景
1. 当天重试次数达到 10 次
2. 后续不再尝试连接
3. 定时心跳也会停止
4. 控制台显示明确提示信息

### 跨天重置
1. 使用日期字符串判断是否为新的一天
2. 新的一天自动从 localStorage 读取（会因日期不匹配返回 0）
3. 重新开始 10 次重试机会

## 测试建议

### 手动测试
1. 在 WebSocket 服务未启动的情况下访问后台
2. 观察控制台日志，应该看到重试次数递增
3. 刷新页面多次，直到达到 10 次限制
4. 验证是否停止重试并显示提示信息
5. 清除 localStorage 或等待第二天，验证重置功能

### 清除重试记录
在浏览器控制台执行：
```javascript
localStorage.removeItem('ws_retry_data');
```

## 影响范围

- ✅ 仅影响后台管理页面的 WebSocket 连接
- ✅ 不影响其他功能
- ✅ 不影响正常的 WebSocket 通信
- ✅ WebSocket 服务正常时，功能完全不受影响

## 注意事项

1. **localStorage 依赖**
   - 需要浏览器支持 localStorage
   - 如果用户禁用了 localStorage，重试限制可能失效（但不会报错）

2. **时区问题**
   - 使用 `toDateString()` 基于本地时区
   - 在不同时区可能有细微差异

3. **连接成功后的行为**
   - 连接成功会重置计数，允许下次断线后再次重试
   - 这是合理的设计，因为成功连接说明服务可用

## 版本信息

- 修改日期：2026-02-28
- 修改文件：`app/view/backstage/index.html`
- 影响版本：当前版本及后续版本

## 相关文档

- WebSocket 配置：`config/gateway_worker.php`
- WebSocket 客户端工具：`app/common/WebsocketClient.php`
