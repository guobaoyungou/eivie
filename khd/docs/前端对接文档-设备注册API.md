# AI旅拍设备注册API - 前端对接文档

> 文档版本：v1.0  
> 最后更新：2026-02-02  
> 适用平台：Windows客户端

---

## 📋 目录

- [1. 接口概述](#1-接口概述)
- [2. 接口详情](#2-接口详情)
- [3. 请求参数](#3-请求参数)
- [4. 响应格式](#4-响应格式)
- [5. 错误码说明](#5-错误码说明)
- [6. 调用示例](#6-调用示例)
- [7. 注意事项](#7-注意事项)
- [8. 测试验证](#8-测试验证)

---

## 1. 接口概述

### 1.1 基本信息

| 项目 | 内容 |
|------|------|
| **接口名称** | 设备注册 |
| **接口地址** | `/api/ai_travel_photo/device/register` |
| **请求方式** | POST |
| **Content-Type** | application/json |
| **响应格式** | JSON |
| **是否需要Token** | ❌ 否（首次注册无需Token） |

### 1.2 功能说明

- 用于Windows客户端首次启动时向服务器注册设备信息
- 注册成功后获取唯一的设备令牌（device_token）
- 支持重复注册检测，相同设备ID再次注册返回已存在的令牌
- 后续所有API调用需携带此令牌进行身份验证

---

## 2. 接口详情

### 2.1 完整URL

```
http://your-domain.com/api/ai_travel_photo/device/register
```

**生产环境地址：**
```
http://192.168.11.222/api/ai_travel_photo/device/register
```

### 2.2 请求头

```http
POST /api/ai_travel_photo/device/register HTTP/1.1
Host: 192.168.11.222
Content-Type: application/json
User-Agent: YourAppName/1.0.0
```

---

## 3. 请求参数

### 3.1 参数列表

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| device_code | string | ✅ 是 | 设备编码（与后台设备管理关联） | "DEVICE_001" |
| device_id | string | ✅ 是 | 设备唯一标识（建议使用硬件ID） | "1BC577A32515" |
| bid | integer | ✅ 是 | 商家ID | 1 |
| aid | integer | ✅ 是 | 应用ID（强制必填） | 1 |
| mdid | integer | ⭕ 否 | 门店ID | 1 |
| device_name | string | ⭕ 否 | 设备名称 | "前台拍摄设备1号" |
| device_type | string | ⭕ 否 | 设备类型 | "windows" |
| mac_address | string | ⭕ 否 | MAC地址 | "00:15:5D:12:34:56" |
| os_version | string | ⭕ 否 | 操作系统版本 | "Windows 10 Pro 64-bit" |
| client_version | string | ⭕ 否 | 客户端版本 | "1.0.0" |
| pc_name | string | ⭕ 否 | 计算机名称 | "WIN-PHOTO-01" |
| cpu_info | string | ⭕ 否 | CPU信息 | "Intel Core i7-10700K" |
| memory_size | string | ⭕ 否 | 内存大小 | "16GB" |
| disk_info | string | ⭕ 否 | 磁盘信息 | "C: 500GB SSD" |
| ip | string | ⭕ 否 | IP地址 | "192.168.1.100" |

### 3.2 必填参数说明

**⚠️ 特别注意：以下4个参数为强制必填**

1. **device_code**：设备编码
   - 必须与后台"设备管理"中配置的设备编码一致
   - 用于关联设备配置信息

2. **device_id**：设备唯一标识
   - 建议使用硬件ID（如主板序列号、CPU ID等）
   - 格式建议：大写字母+数字，12位（如：`1BC577A32515`）
   - 生成方法示例：`md5(硬件信息).substring(0, 12).toUpperCase()`

3. **bid**：商家ID
   - 从配置文件或启动参数获取
   - 用于多商家隔离

4. **aid**：应用ID
   - 从配置文件或启动参数获取
   - **此参数为强制必填，缺失会返回400错误**

### 3.3 可选参数建议

为了更好的设备管理和问题排查，**强烈建议**提供以下参数：

- `device_name`：便于后台识别设备
- `os_version`：用于兼容性分析
- `client_version`：版本管理和升级控制
- `ip`：网络问题排查

---

## 4. 响应格式

### 4.1 响应结构

```json
{
    "code": 200,
    "msg": "提示信息",
    "data": {
        "device_token": "设备令牌",
        "device_id": "设备ID",
        "status": "注册状态"
    }
}
```

### 4.2 成功响应示例

#### 新设备注册成功

```json
{
    "code": 200,
    "msg": "设备注册成功",
    "data": {
        "device_token": "07d3e2662d6c839deed9f3e7987119e4",
        "device_id": "1BC577A32515",
        "status": "registered"
    }
}
```

#### 设备已存在（重复注册）

```json
{
    "code": 200,
    "msg": "设备注册成功",
    "data": {
        "device_token": "07d3e2662d6c839deed9f3e7987119e4",
        "device_id": "1BC577A32515",
        "status": "exists"
    }
}
```

### 4.3 响应字段说明

| 字段 | 类型 | 说明 |
|------|------|------|
| code | integer | 响应状态码（200=成功，400=参数错误，500=服务器错误） |
| msg | string | 提示信息 |
| data.device_token | string | 设备令牌（32位MD5字符串），**需妥善保存** |
| data.device_id | string | 设备ID（回显确认） |
| data.status | string | 注册状态（`registered`=新注册，`exists`=已存在） |

---

## 5. 错误码说明

### 5.1 常见错误

| HTTP状态 | code | msg | 说明 | 解决方案 |
|----------|------|-----|------|----------|
| 200 | 400 | 设备编码不能为空 | 缺少device_code参数 | 检查请求参数 |
| 200 | 400 | 商家ID不能为空 | 缺少bid参数 | 检查请求参数 |
| 200 | 400 | 应用ID不能为空 | 缺少aid参数 | 检查请求参数 |
| 200 | 400 | 设备ID不能为空 | 缺少device_id参数 | 检查请求参数 |
| 200 | 500 | 系统错误：... | 服务器内部错误 | 联系后端开发人员 |
| 404 | - | Not Found | 接口路径错误 | 确认URL是否正确 |

### 5.2 错误处理建议

```javascript
// 错误处理示例
if (response.code === 200) {
    // 注册成功
    const { device_token, status } = response.data;
    
    if (status === 'registered') {
        console.log('首次注册成功');
    } else if (status === 'exists') {
        console.log('设备已存在，返回已有令牌');
    }
    
    // 保存令牌到本地
    saveDeviceToken(device_token);
    
} else if (response.code === 400) {
    // 参数错误
    console.error('参数错误：', response.msg);
    
} else if (response.code === 500) {
    // 服务器错误
    console.error('服务器错误：', response.msg);
}
```

---

## 6. 调用示例

### 6.1 JavaScript/Axios 示例

```javascript
/**
 * 设备注册
 */
async function registerDevice() {
    try {
        const deviceInfo = {
            // 必填参数
            device_code: 'DEVICE_001',              // 从配置获取
            device_id: getHardwareId(),             // 获取硬件ID
            bid: 1,                                 // 从配置获取
            aid: 1,                                 // 从配置获取（强制必填）
            
            // 可选参数（建议提供）
            mdid: 1,
            device_name: '前台拍摄设备1号',
            device_type: 'windows',
            mac_address: getMacAddress(),
            os_version: getOsVersion(),
            client_version: '1.0.0',
            pc_name: getPcName(),
            cpu_info: getCpuInfo(),
            memory_size: getMemorySize(),
            disk_info: getDiskInfo(),
            ip: getLocalIp()
        };
        
        const response = await axios.post(
            'http://192.168.11.222/api/ai_travel_photo/device/register',
            deviceInfo,
            {
                headers: {
                    'Content-Type': 'application/json'
                },
                timeout: 10000  // 10秒超时
            }
        );
        
        if (response.data.code === 200) {
            // 注册成功，保存令牌
            const { device_token, status } = response.data.data;
            
            // 保存到本地存储
            localStorage.setItem('device_token', device_token);
            localStorage.setItem('device_id', deviceInfo.device_id);
            
            console.log('设备注册成功');
            console.log('设备令牌:', device_token);
            console.log('注册状态:', status);
            
            return {
                success: true,
                token: device_token,
                isNewDevice: status === 'registered'
            };
        } else {
            console.error('注册失败:', response.data.msg);
            return {
                success: false,
                error: response.data.msg
            };
        }
        
    } catch (error) {
        console.error('请求失败:', error.message);
        return {
            success: false,
            error: error.message
        };
    }
}

/**
 * 获取硬件ID（示例）
 */
function getHardwareId() {
    // 实际项目中应该使用真实的硬件信息
    // 例如：主板序列号、CPU ID等
    const hardwareInfo = `${navigator.userAgent}_${navigator.platform}`;
    const hash = md5(hardwareInfo);
    return hash.substring(0, 12).toUpperCase();
}
```

### 6.2 Python 示例

```python
import requests
import hashlib
import platform
import uuid

def register_device():
    """设备注册"""
    
    # 生成设备ID
    device_id = hashlib.md5(
        str(uuid.getnode()).encode()
    ).hexdigest()[:12].upper()
    
    # 请求数据
    data = {
        # 必填参数
        'device_code': 'DEVICE_001',
        'device_id': device_id,
        'bid': 1,
        'aid': 1,  # 强制必填
        
        # 可选参数
        'mdid': 1,
        'device_name': 'Windows测试设备',
        'device_type': 'windows',
        'os_version': platform.platform(),
        'client_version': '1.0.0',
        'pc_name': platform.node(),
        'ip': get_local_ip()
    }
    
    try:
        response = requests.post(
            'http://192.168.11.222/api/ai_travel_photo/device/register',
            json=data,
            headers={'Content-Type': 'application/json'},
            timeout=10
        )
        
        result = response.json()
        
        if result['code'] == 200:
            device_token = result['data']['device_token']
            status = result['data']['status']
            
            # 保存令牌
            save_token(device_token)
            
            print(f'注册成功! Token: {device_token}')
            print(f'状态: {status}')
            
            return True, device_token
        else:
            print(f'注册失败: {result["msg"]}')
            return False, None
            
    except Exception as e:
        print(f'请求异常: {str(e)}')
        return False, None

def save_token(token):
    """保存令牌到本地文件"""
    with open('device_token.txt', 'w') as f:
        f.write(token)
```

### 6.3 C# 示例

```csharp
using System;
using System.Net.Http;
using System.Text;
using System.Text.Json;
using System.Threading.Tasks;

public class DeviceRegistration
{
    private static readonly HttpClient client = new HttpClient();
    private const string API_URL = "http://192.168.11.222/api/ai_travel_photo/device/register";
    
    /// <summary>
    /// 设备注册
    /// </summary>
    public static async Task<(bool success, string token)> RegisterDevice()
    {
        try
        {
            // 构建请求数据
            var deviceInfo = new
            {
                // 必填参数
                device_code = "DEVICE_001",
                device_id = GetHardwareId(),
                bid = 1,
                aid = 1,  // 强制必填
                
                // 可选参数
                mdid = 1,
                device_name = "Windows拍摄设备",
                device_type = "windows",
                os_version = Environment.OSVersion.ToString(),
                client_version = "1.0.0",
                pc_name = Environment.MachineName,
                cpu_info = GetCpuInfo(),
                memory_size = GetMemorySize(),
                ip = GetLocalIp()
            };
            
            // 序列化为JSON
            string jsonData = JsonSerializer.Serialize(deviceInfo);
            var content = new StringContent(jsonData, Encoding.UTF8, "application/json");
            
            // 发送请求
            var response = await client.PostAsync(API_URL, content);
            var responseBody = await response.Content.ReadAsStringAsync();
            
            // 解析响应
            using JsonDocument doc = JsonDocument.Parse(responseBody);
            var root = doc.RootElement;
            
            int code = root.GetProperty("code").GetInt32();
            string msg = root.GetProperty("msg").GetString();
            
            if (code == 200)
            {
                var data = root.GetProperty("data");
                string deviceToken = data.GetProperty("device_token").GetString();
                string status = data.GetProperty("status").GetString();
                
                // 保存令牌
                SaveToken(deviceToken);
                
                Console.WriteLine($"注册成功! Token: {deviceToken}");
                Console.WriteLine($"状态: {status}");
                
                return (true, deviceToken);
            }
            else
            {
                Console.WriteLine($"注册失败: {msg}");
                return (false, null);
            }
        }
        catch (Exception ex)
        {
            Console.WriteLine($"请求异常: {ex.Message}");
            return (false, null);
        }
    }
    
    /// <summary>
    /// 获取硬件ID
    /// </summary>
    private static string GetHardwareId()
    {
        // 实际项目中应该使用真实的硬件信息
        // 例如：主板序列号、CPU ID等
        string hardwareInfo = $"{Environment.MachineName}_{Environment.ProcessorCount}";
        using (var md5 = System.Security.Cryptography.MD5.Create())
        {
            byte[] hash = md5.ComputeHash(Encoding.UTF8.GetBytes(hardwareInfo));
            string hexHash = BitConverter.ToString(hash).Replace("-", "");
            return hexHash.Substring(0, 12).ToUpper();
        }
    }
    
    /// <summary>
    /// 保存令牌
    /// </summary>
    private static void SaveToken(string token)
    {
        System.IO.File.WriteAllText("device_token.txt", token);
    }
}
```

---

## 7. 注意事项

### 7.1 ⚠️ 重要提醒

1. **device_token 安全性**
   - 令牌是设备身份凭证，**必须妥善保存**
   - 建议加密存储在本地配置文件中
   - 不要在日志中打印完整令牌

2. **device_id 唯一性**
   - 必须确保设备ID的唯一性和稳定性
   - 不要使用随机数或时间戳
   - 建议使用硬件信息生成（主板序列号、CPU ID等）

3. **aid 参数必填**
   - aid（应用ID）是强制必填参数
   - 缺失会导致 `code: 400` 错误
   - 必须从配置文件或启动参数中获取

4. **重复注册**
   - 相同device_id重复注册会返回已存在的令牌
   - 不会创建新的设备记录
   - 返回的status字段为 `exists`

5. **网络超时**
   - 建议设置10秒超时
   - 失败后可重试3次
   - 重试间隔建议1-3秒

### 7.2 最佳实践

```javascript
// 1. 首次启动时注册
async function initDevice() {
    // 检查是否已有令牌
    let token = localStorage.getItem('device_token');
    
    if (!token) {
        // 首次启动，执行注册
        const result = await registerDevice();
        if (result.success) {
            token = result.token;
        } else {
            // 注册失败，显示错误并退出
            alert('设备注册失败，请检查网络连接');
            return false;
        }
    }
    
    // 验证令牌有效性（可选）
    const isValid = await validateToken(token);
    if (!isValid) {
        // 令牌无效，重新注册
        localStorage.removeItem('device_token');
        return initDevice();
    }
    
    return true;
}

// 2. 后续请求携带令牌
async function uploadPhoto(photoData) {
    const token = localStorage.getItem('device_token');
    
    const response = await axios.post(
        '/api/ai_travel_photo/portrait/upload',
        photoData,
        {
            headers: {
                'Device-Token': token,  // 携带令牌
                'Content-Type': 'multipart/form-data'
            }
        }
    );
    
    return response.data;
}
```

### 7.3 故障排查

| 问题 | 可能原因 | 解决方案 |
|------|----------|----------|
| 404 错误 | URL路径错误 | 确认路径为 `/api/ai_travel_photo/device/register` |
| 参数错误 | 缺少必填参数 | 检查 device_code、device_id、bid、aid 是否都已提供 |
| 网络超时 | 网络不稳定 | 增加超时时间，实现重试机制 |
| 令牌丢失 | 本地存储失败 | 检查存储权限，实现持久化机制 |

---

## 8. 测试验证

### 8.1 测试环境

- **测试服务器**：`http://192.168.11.222`
- **测试商家ID**：`bid=1`
- **测试应用ID**：`aid=1`
- **测试门店ID**：`mdid=1`

### 8.2 测试用例

#### 用例1：正常注册（新设备）

**请求：**
```json
{
    "device_code": "TEST001",
    "device_id": "1BC577A32515",
    "bid": 1,
    "aid": 1,
    "device_name": "测试设备"
}
```

**预期响应：**
```json
{
    "code": 200,
    "msg": "设备注册成功",
    "data": {
        "device_token": "07d3e2662d6c839deed9f3e7987119e4",
        "device_id": "1BC577A32515",
        "status": "registered"
    }
}
```

#### 用例2：重复注册

**请求：**（使用相同的device_id再次注册）
```json
{
    "device_code": "TEST001",
    "device_id": "1BC577A32515",
    "bid": 1,
    "aid": 1
}
```

**预期响应：**
```json
{
    "code": 200,
    "msg": "设备注册成功",
    "data": {
        "device_token": "07d3e2662d6c839deed9f3e7987119e4",
        "device_id": "1BC577A32515",
        "status": "exists"
    }
}
```

#### 用例3：缺少必填参数

**请求：**（缺少device_code）
```json
{
    "device_id": "1BC577A32515",
    "bid": 1,
    "aid": 1
}
```

**预期响应：**
```json
{
    "code": 400,
    "msg": "设备编码不能为空"
}
```

### 8.3 性能指标

根据实际测试结果：

- **平均响应时间**：77-141ms
- **成功率**：100%（参数正确时）
- **并发支持**：良好
- **网络稳定性**：优秀

### 8.4 测试工具

项目中已提供完整测试脚本：
```bash
# 命令行测试
php /www/wwwroot/eivie/test_device_register_api.php

# 或浏览器访问
http://192.168.11.222/test_device_register_api.php
```

---

## 📞 技术支持

如有问题，请联系：

- **后端开发人员**：[联系方式]
- **技术文档**：`/www/wwwroot/eivie/khd/docs/设备注册规范.md`
- **问题反馈**：[问题跟踪系统]

---

## 📝 更新日志

### v1.0 (2026-02-02)
- ✅ 初始版本发布
- ✅ 完成核心功能测试验证
- ✅ 添加多语言调用示例
- ✅ 完善错误处理说明

---

**文档结束** 🎉
