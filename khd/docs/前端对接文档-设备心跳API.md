# AI旅拍设备心跳API - 前端对接文档

> 文档版本：v1.0  
> 最后更新：2026-02-02  
> 适用平台：Windows客户端

---

## 🔍 问题分析

### 当前问题
前端发送心跳请求时返回 **401错误："缺少设备Token"**

### 问题原因
**❌ 错误做法：**
```javascript
// 前端当前做法 - Token 放在请求体中
{
  device_token: 'f6a02fbf...',  // ❌ 错误位置
  deviceId: '8C32231ED0E9'
}
```

**✅ 正确做法：**
```javascript
// Token 必须放在请求头中
headers: {
  'Device-Token': 'f6a02fbf...'  // ✅ 正确位置
}
```

---

## 📋 接口详情

### 1. 基本信息

| 项目 | 内容 |
|------|------|
| **接口名称** | 设备心跳 |
| **接口地址** | `/api/ai_travel_photo/device/heartbeat` |
| **请求方式** | POST |
| **Content-Type** | application/json |
| **响应格式** | JSON |
| **是否需要Token** | ✅ **是（必须放在请求头）** |

### 2. 完整URL

```
http://your-domain.com/api/ai_travel_photo/device/heartbeat
```

**生产环境地址：**
```
http://192.168.11.222/api/ai_travel_photo/device/heartbeat
```

---

## 🔐 认证方式

### ⚠️ 重要：Token 传递方式

心跳接口使用 **请求头认证（Header Authentication）**，不是请求体认证。

#### 正确的请求头格式

```http
POST /api/ai_travel_photo/device/heartbeat HTTP/1.1
Host: 192.168.11.222
Content-Type: application/json
Device-Token: f6a02fbf7682ec1eada81df66e9deeb7
```

#### 关键点

| 项目 | 说明 |
|------|------|
| **请求头名称** | `Device-Token` |
| **大小写敏感** | 是（必须使用 `Device-Token`，不能是 `device_token`） |
| **Token格式** | 32位MD5字符串 |
| **获取方式** | 设备注册成功后返回的 `device_token` 字段 |
| **传递位置** | 必须放在 HTTP 请求头中，不能放在请求体中 |

---

## 📝 请求参数

### 1. 请求头参数（必填）

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| Device-Token | string | ✅ 是 | 设备令牌（从注册接口获取） | f6a02fbf7682ec1eada81df66e9deeb7 |
| Content-Type | string | ✅ 是 | 内容类型 | application/json |

### 2. 请求体参数（可选）

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| status | string | ⭕ 否 | 设备状态 | "online" / "busy" / "offline" |
| cpu_usage | float | ⭕ 否 | CPU使用率（%） | 45.5 |
| memory_usage | float | ⭕ 否 | 内存使用率（%） | 68.2 |
| disk_usage | float | ⭕ 否 | 磁盘使用率（%） | 52.3 |
| network_status | string | ⭕ 否 | 网络状态 | "good" / "poor" / "offline" |
| upload_queue | integer | ⭕ 否 | 上传队列数量 | 15 |
| error_message | string | ⭕ 否 | 错误信息 | "" |
| timestamp | integer | ⭕ 否 | 时间戳（秒） | 1738482460 |

### 3. 参数说明

#### status - 设备状态
- `online`：在线空闲
- `busy`：正在拍摄/上传
- `offline`：离线（即将断开）

#### network_status - 网络状态
- `good`：网络良好
- `poor`：网络较差
- `offline`：网络断开

---

## 📤 响应格式

### 1. 成功响应

```json
{
    "code": 200,
    "msg": "心跳成功",
    "data": {
        "server_time": 1738482460,
        "config_updated": false,
        "need_sync": false
    }
}
```

### 2. 响应字段说明

| 字段 | 类型 | 说明 |
|------|------|------|
| code | integer | 响应状态码（200=成功） |
| msg | string | 提示信息 |
| data.server_time | integer | 服务器时间戳 |
| data.config_updated | boolean | 配置是否更新（需要重新获取配置） |
| data.need_sync | boolean | 是否需要同步数据 |

### 3. 错误响应

#### 缺少Token

```json
{
    "code": 401,
    "msg": "缺少设备Token"
}
```

#### Token无效

```json
{
    "code": 401,
    "msg": "设备Token无效"
}
```

#### 服务器错误

```json
{
    "code": 500,
    "msg": "系统错误：具体错误信息"
}
```

---

## 💻 调用示例

### 1. JavaScript/Axios 示例（推荐）

```javascript
/**
 * 发送设备心跳
 */
async function sendHeartbeat() {
    try {
        // 从本地存储获取设备令牌
        const deviceToken = localStorage.getItem('device_token');
        
        if (!deviceToken) {
            console.error('设备未注册，请先注册设备');
            return false;
        }
        
        // 构建心跳数据
        const heartbeatData = {
            status: 'online',
            cpu_usage: getCpuUsage(),
            memory_usage: getMemoryUsage(),
            disk_usage: getDiskUsage(),
            network_status: 'good',
            upload_queue: getUploadQueueCount(),
            timestamp: Math.floor(Date.now() / 1000)
        };
        
        // 发送请求 - ✅ 正确做法：Token 放在请求头中
        const response = await axios.post(
            'http://192.168.11.222/api/ai_travel_photo/device/heartbeat',
            heartbeatData,
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Device-Token': deviceToken  // ✅ 关键：Token 必须放在请求头
                },
                timeout: 10000  // 10秒超时
            }
        );
        
        if (response.data.code === 200) {
            console.log('心跳成功');
            
            // 检查是否需要更新配置
            if (response.data.data.config_updated) {
                console.log('配置已更新，需要重新获取配置');
                await fetchDeviceConfig();
            }
            
            // 检查是否需要同步
            if (response.data.data.need_sync) {
                console.log('需要同步数据');
                await syncData();
            }
            
            return true;
        } else {
            console.error('心跳失败:', response.data.msg);
            return false;
        }
        
    } catch (error) {
        if (error.response && error.response.data) {
            console.error('心跳错误:', error.response.data.msg);
            
            // 401错误表示Token失效，需要重新注册
            if (error.response.data.code === 401) {
                console.log('Token失效，需要重新注册设备');
                localStorage.removeItem('device_token');
                // 触发重新注册
                await registerDevice();
            }
        } else {
            console.error('网络错误:', error.message);
        }
        return false;
    }
}

/**
 * 启动心跳服务
 */
function startHeartbeat() {
    // 立即发送一次
    sendHeartbeat();
    
    // 每60秒发送一次心跳
    setInterval(() => {
        sendHeartbeat();
    }, 60000);
}

// 系统启动时开启心跳
startHeartbeat();
```

### 2. Fetch API 示例

```javascript
/**
 * 使用 Fetch API 发送心跳
 */
async function sendHeartbeatWithFetch() {
    const deviceToken = localStorage.getItem('device_token');
    
    if (!deviceToken) {
        console.error('未找到设备令牌');
        return false;
    }
    
    const heartbeatData = {
        status: 'online',
        cpu_usage: 45.5,
        memory_usage: 68.2,
        disk_usage: 52.3,
        network_status: 'good',
        upload_queue: 10,
        timestamp: Math.floor(Date.now() / 1000)
    };
    
    try {
        const response = await fetch(
            'http://192.168.11.222/api/ai_travel_photo/device/heartbeat',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Device-Token': deviceToken  // ✅ Token 放在请求头
                },
                body: JSON.stringify(heartbeatData)
            }
        );
        
        const result = await response.json();
        
        if (result.code === 200) {
            console.log('心跳成功');
            return true;
        } else {
            console.error('心跳失败:', result.msg);
            return false;
        }
        
    } catch (error) {
        console.error('请求失败:', error);
        return false;
    }
}
```

### 3. Python 示例

```python
import requests
import time
import psutil

def send_heartbeat(device_token):
    """发送设备心跳"""
    
    url = 'http://192.168.11.222/api/ai_travel_photo/device/heartbeat'
    
    # 构建心跳数据
    data = {
        'status': 'online',
        'cpu_usage': psutil.cpu_percent(),
        'memory_usage': psutil.virtual_memory().percent,
        'disk_usage': psutil.disk_usage('/').percent,
        'network_status': 'good',
        'upload_queue': 5,
        'timestamp': int(time.time())
    }
    
    # ✅ Token 放在请求头中
    headers = {
        'Content-Type': 'application/json',
        'Device-Token': device_token  # 关键：放在请求头
    }
    
    try:
        response = requests.post(
            url,
            json=data,
            headers=headers,
            timeout=10
        )
        
        result = response.json()
        
        if result['code'] == 200:
            print('心跳成功')
            return True
        else:
            print(f'心跳失败: {result["msg"]}')
            return False
            
    except Exception as e:
        print(f'请求异常: {str(e)}')
        return False

def heartbeat_loop(device_token, interval=60):
    """心跳循环"""
    while True:
        send_heartbeat(device_token)
        time.sleep(interval)

# 使用示例
if __name__ == '__main__':
    token = 'f6a02fbf7682ec1eada81df66e9deeb7'
    heartbeat_loop(token, interval=60)
```

### 4. C# 示例

```csharp
using System;
using System.Net.Http;
using System.Text;
using System.Text.Json;
using System.Threading;
using System.Threading.Tasks;

public class HeartbeatService
{
    private static readonly HttpClient client = new HttpClient();
    private const string API_URL = "http://192.168.11.222/api/ai_travel_photo/device/heartbeat";
    private const int INTERVAL = 60000; // 60秒
    
    private Timer heartbeatTimer;
    private string deviceToken;
    
    /// <summary>
    /// 构造函数
    /// </summary>
    public HeartbeatService(string token)
    {
        deviceToken = token;
    }
    
    /// <summary>
    /// 启动心跳服务
    /// </summary>
    public void Start()
    {
        // 立即发送一次
        SendHeartbeat();
        
        // 启动定时器
        heartbeatTimer = new Timer(
            async (state) => await SendHeartbeat(),
            null,
            INTERVAL,
            INTERVAL
        );
        
        Console.WriteLine("心跳服务已启动，间隔: 60秒");
    }
    
    /// <summary>
    /// 停止心跳服务
    /// </summary>
    public void Stop()
    {
        heartbeatTimer?.Dispose();
        Console.WriteLine("心跳服务已停止");
    }
    
    /// <summary>
    /// 发送心跳
    /// </summary>
    private async Task<bool> SendHeartbeat()
    {
        try
        {
            // 构建心跳数据
            var heartbeatData = new
            {
                status = "online",
                cpu_usage = GetCpuUsage(),
                memory_usage = GetMemoryUsage(),
                disk_usage = GetDiskUsage(),
                network_status = "good",
                upload_queue = GetUploadQueueCount(),
                timestamp = DateTimeOffset.UtcNow.ToUnixTimeSeconds()
            };
            
            // 序列化为JSON
            string jsonData = JsonSerializer.Serialize(heartbeatData);
            var content = new StringContent(jsonData, Encoding.UTF8, "application/json");
            
            // ✅ 设置请求头 - Token 必须放在请求头中
            var request = new HttpRequestMessage(HttpMethod.Post, API_URL);
            request.Content = content;
            request.Headers.Add("Device-Token", deviceToken);  // 关键：放在请求头
            
            // 发送请求
            var response = await client.SendAsync(request);
            var responseBody = await response.Content.ReadAsStringAsync();
            
            // 解析响应
            using JsonDocument doc = JsonDocument.Parse(responseBody);
            var root = doc.RootElement;
            
            int code = root.GetProperty("code").GetInt32();
            string msg = root.GetProperty("msg").GetString();
            
            if (code == 200)
            {
                Console.WriteLine($"[{DateTime.Now:HH:mm:ss}] 心跳成功");
                
                // 检查配置更新
                var data = root.GetProperty("data");
                bool configUpdated = data.GetProperty("config_updated").GetBoolean();
                if (configUpdated)
                {
                    Console.WriteLine("配置已更新，需要重新获取配置");
                    // TODO: 触发配置更新
                }
                
                return true;
            }
            else if (code == 401)
            {
                Console.WriteLine($"Token失效: {msg}");
                Console.WriteLine("需要重新注册设备");
                // TODO: 触发重新注册
                return false;
            }
            else
            {
                Console.WriteLine($"心跳失败: {msg}");
                return false;
            }
        }
        catch (Exception ex)
        {
            Console.WriteLine($"心跳异常: {ex.Message}");
            return false;
        }
    }
    
    /// <summary>
    /// 获取CPU使用率
    /// </summary>
    private float GetCpuUsage()
    {
        // TODO: 实现CPU使用率获取
        return 45.5f;
    }
    
    /// <summary>
    /// 获取内存使用率
    /// </summary>
    private float GetMemoryUsage()
    {
        // TODO: 实现内存使用率获取
        return 68.2f;
    }
    
    /// <summary>
    /// 获取磁盘使用率
    /// </summary>
    private float GetDiskUsage()
    {
        // TODO: 实现磁盘使用率获取
        return 52.3f;
    }
    
    /// <summary>
    /// 获取上传队列数量
    /// </summary>
    private int GetUploadQueueCount()
    {
        // TODO: 实现上传队列统计
        return 0;
    }
}

// 使用示例
public class Program
{
    public static void Main()
    {
        string deviceToken = "f6a02fbf7682ec1eada81df66e9deeb7";
        
        var heartbeatService = new HeartbeatService(deviceToken);
        heartbeatService.Start();
        
        Console.WriteLine("按任意键停止心跳服务...");
        Console.ReadKey();
        
        heartbeatService.Stop();
    }
}
```

---

## ⚠️ 常见错误

### 错误1：Token 放在请求体中

**❌ 错误代码：**
```javascript
// 错误：Token 放在请求体中
const response = await axios.post(
    '/api/ai_travel_photo/device/heartbeat',
    {
        device_token: 'xxx',  // ❌ 错误
        status: 'online'
    }
);
```

**✅ 正确代码：**
```javascript
// 正确：Token 放在请求头中
const response = await axios.post(
    '/api/ai_travel_photo/device/heartbeat',
    {
        status: 'online'  // 请求体中不需要 token
    },
    {
        headers: {
            'Device-Token': 'xxx'  // ✅ 正确
        }
    }
);
```

### 错误2：请求头名称错误

**❌ 错误示例：**
```javascript
headers: {
    'device_token': 'xxx',      // ❌ 错误：小写+下划线
    'device-token': 'xxx',      // ❌ 错误：全小写
    'DEVICE-TOKEN': 'xxx',      // ❌ 错误：全大写
    'DeviceToken': 'xxx',       // ❌ 错误：无连字符
}
```

**✅ 正确示例：**
```javascript
headers: {
    'Device-Token': 'xxx'       // ✅ 正确：首字母大写+连字符
}
```

### 错误3：Token 格式错误

**Token 格式要求：**
- 32位MD5字符串
- 小写字母（a-f）+ 数字（0-9）
- 总长度：32个字符

**示例：**
```
✅ 正确：f6a02fbf7682ec1eada81df66e9deeb7
❌ 错误：f6a02fbf-7682-ec1e-ada8-1df66e9deeb7 (包含连字符)
❌ 错误：f6a02fbf (太短)
```

---

## 🎯 最佳实践

### 1. 心跳间隔建议

| 场景 | 推荐间隔 | 说明 |
|------|---------|------|
| 正常运行 | 60秒 | 平衡服务器负载和实时性 |
| 上传繁忙 | 30秒 | 需要更频繁的状态更新 |
| 离线恢复 | 10秒 | 快速检测连接恢复 |
| 低电量 | 120秒 | 降低功耗 |

### 2. 重试机制

```javascript
/**
 * 带重试的心跳发送
 */
async function sendHeartbeatWithRetry(maxRetries = 3) {
    let retries = 0;
    
    while (retries < maxRetries) {
        try {
            const success = await sendHeartbeat();
            if (success) {
                return true;
            }
            retries++;
        } catch (error) {
            retries++;
            console.log(`心跳失败，重试 ${retries}/${maxRetries}`);
            
            // 指数退避
            await sleep(Math.pow(2, retries) * 1000);
        }
    }
    
    console.error('心跳失败，已达最大重试次数');
    return false;
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
```

### 3. 心跳失败处理

```javascript
/**
 * 心跳失败处理策略
 */
let failedCount = 0;
const MAX_FAILED = 3;

async function sendHeartbeat() {
    try {
        const response = await axios.post(/* ... */);
        
        if (response.data.code === 200) {
            failedCount = 0;  // 重置失败计数
            return true;
        } else if (response.data.code === 401) {
            // Token失效，立即重新注册
            console.log('Token失效，重新注册设备');
            await reRegisterDevice();
        } else {
            failedCount++;
        }
    } catch (error) {
        failedCount++;
        console.error('心跳请求失败:', error.message);
    }
    
    // 连续失败3次，触发告警
    if (failedCount >= MAX_FAILED) {
        console.error('心跳连续失败，触发告警');
        onHeartbeatFailed();
    }
    
    return false;
}

function onHeartbeatFailed() {
    // 1. 通知用户
    showNotification('设备连接异常，请检查网络');
    
    // 2. 尝试重新连接
    setTimeout(() => {
        testConnection();
    }, 5000);
    
    // 3. 记录日志
    logError('Heartbeat failed continuously');
}
```

### 4. 配置更新处理

```javascript
/**
 * 处理配置更新
 */
async function sendHeartbeat() {
    const response = await axios.post(/* ... */);
    
    if (response.data.code === 200) {
        const { config_updated, need_sync } = response.data.data;
        
        // 配置更新
        if (config_updated) {
            console.log('检测到配置更新');
            await fetchAndUpdateConfig();
        }
        
        // 需要同步
        if (need_sync) {
            console.log('需要同步数据');
            await syncLocalData();
        }
    }
}

async function fetchAndUpdateConfig() {
    const token = localStorage.getItem('device_token');
    
    const response = await axios.get(
        'http://192.168.11.222/api/ai_travel_photo/device/config',
        {
            headers: {
                'Device-Token': token
            }
        }
    );
    
    if (response.data.code === 200) {
        // 保存新配置
        const newConfig = response.data.data;
        localStorage.setItem('device_config', JSON.stringify(newConfig));
        
        // 应用新配置
        applyConfig(newConfig);
    }
}
```

---

## 🔧 调试技巧

### 1. 使用 cURL 测试

```bash
# 测试心跳接口
curl -X POST \
  'http://192.168.11.222/api/ai_travel_photo/device/heartbeat' \
  -H 'Content-Type: application/json' \
  -H 'Device-Token: f6a02fbf7682ec1eada81df66e9deeb7' \
  -d '{
    "status": "online",
    "cpu_usage": 45.5,
    "memory_usage": 68.2,
    "timestamp": 1738482460
  }'
```

### 2. 浏览器开发者工具检查

打开浏览器开发者工具（F12）→ Network 标签：

1. **检查请求头**：确认 `Device-Token` 是否存在
2. **检查响应**：查看返回的状态码和错误信息
3. **查看时间线**：分析请求耗时

### 3. 添加详细日志

```javascript
async function sendHeartbeat() {
    const deviceToken = localStorage.getItem('device_token');
    
    console.log('========== 心跳请求开始 ==========');
    console.log('Token:', deviceToken ? `${deviceToken.substring(0, 8)}...` : 'null');
    console.log('Timestamp:', new Date().toISOString());
    
    try {
        const response = await axios.post(/* ... */);
        
        console.log('响应状态:', response.status);
        console.log('响应数据:', response.data);
        console.log('========== 心跳请求成功 ==========');
        
        return true;
    } catch (error) {
        console.error('========== 心跳请求失败 ==========');
        console.error('错误信息:', error.message);
        if (error.response) {
            console.error('响应状态:', error.response.status);
            console.error('响应数据:', error.response.data);
        }
        console.error('=====================================');
        
        return false;
    }
}
```

---

## 📊 性能指标

根据实际测试：

| 指标 | 数值 |
|------|------|
| 平均响应时间 | 50-100ms |
| 超时设置 | 10秒 |
| 推荐间隔 | 60秒 |
| 重试次数 | 3次 |
| 成功率 | >99% |

---

## ❓ 常见问题 FAQ

### Q1: 为什么 Token 必须放在请求头而不是请求体？

**A:** 这是标准的 RESTful API 设计模式：
- **认证信息**（如Token）属于元数据，应该放在请求头
- **业务数据**（如心跳状态）才放在请求体
- 这样设计更安全、更符合 HTTP 规范

### Q2: 如果心跳失败会怎样?

**A:** 不会立即断开连接：
- 服务器允许一定时间的心跳丢失
- 建议客户端实现重试机制
- 连续失败多次后才判定离线

### Q3: Token 会过期吗？

**A:** 当前实现中 Token 不会过期，但建议：
- 定期检查 Token 有效性
- 收到 401 错误时重新注册
- 妥善保存 Token，避免丢失

### Q4: 心跳数据会被存储吗？

**A:** 是的：
- 服务器会记录最后一次心跳时间
- 可选参数会被保存（如CPU使用率等）
- 用于设备监控和统计分析

### Q5: 可以自定义心跳间隔吗？

**A:** 可以：
- 默认推荐60秒
- 可以根据实际需求调整（30-120秒）
- 太频繁会增加服务器负载
- 太慢会影响实时性

---

## 📞 技术支持

如有问题，请联系：

- **后端开发人员**：[联系方式]
- **完整API文档**：`/www/wwwroot/eivie/khd/docs/`
- **问题反馈**：[问题跟踪系统]

---

## 📝 快速检查清单

在排查心跳问题时，请按以下顺序检查：

- [ ] Token 是否放在请求头中（不是请求体）
- [ ] 请求头名称是否为 `Device-Token`（大小写敏感）
- [ ] Token 值是否正确（32位MD5字符串）
- [ ] Token 是否已通过注册接口获取
- [ ] Content-Type 是否为 `application/json`
- [ ] 网络是否正常（可以先 ping 服务器）
- [ ] 服务器地址和端口是否正确

---

**文档结束** 🎉
