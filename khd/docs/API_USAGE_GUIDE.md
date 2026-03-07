# Windows客户端API使用指南

## 目录

1. [快速入门](#快速入门)
2. [服务初始化](#服务初始化)
3. [设备注册流程](#设备注册流程)
4. [文件监控与上传](#文件监控与上传)
5. [心跳与健康监控](#心跳与健康监控)
6. [日志管理](#日志管理)
7. [配置管理](#配置管理)
8. [错误处理](#错误处理)
9. [完整示例](#完整示例)

---

## 快速入门

### 最小化启动示例

```csharp
using AiTravelClient.Services;
using AiTravelClient.Models;

// 1. 创建核心服务
var logService = new LogService();
var configService = new ConfigService();
var config = configService.GetConfig();
var apiClient = new ApiClient(config.Server.ApiBaseUrl, config.Server.Timeout);

// 2. 初始化设备服务
var deviceService = new DeviceService(configService, apiClient, logService);
bool initialized = await deviceService.InitializeAsync();

if (initialized)
{
    Console.WriteLine("设备已注册，可以正常使用");
}
else
{
    Console.WriteLine("设备未注册，请先注册设备");
}
```

---

## 服务初始化

### 1. 日志服务初始化

```csharp
using AiTravelClient.Services;

// 创建日志服务实例
var logService = new LogService();

// 设置最小日志级别（可选，默认为INFO）
logService.SetMinLevel(LogLevel.DEBUG);

// 订阅日志事件（用于界面显示）
logService.OnLogReceived += (timestamp, level, message) =>
{
    Console.WriteLine($"{timestamp} [{level}] {message}");
};

// 使用日志服务
logService.Info("系统", "应用程序已启动");
logService.Warn("系统", "这是一条警告信息");
logService.Error("系统", "这是一条错误信息");

// 清理过期日志（保留最近30天）
logService.CleanOldLogs(30);
```

### 2. 配置服务初始化

```csharp
using AiTravelClient.Services;

// 创建配置服务实例
var configService = new ConfigService();

// 加载配置
var config = configService.LoadConfig();

// 访问配置项
Console.WriteLine($"API地址: {config.Server.ApiBaseUrl}");
Console.WriteLine($"设备名称: {config.Device.DeviceName}");
Console.WriteLine($"上传并发数: {config.Upload.ConcurrentUploads}");

// 检查是否已注册
bool isRegistered = configService.IsRegistered();
Console.WriteLine($"设备已注册: {isRegistered}");

// 更新配置
config.Server.ApiBaseUrl = "https://new-domain.com";
configService.SaveConfig(config);
```

### 3. API客户端初始化

```csharp
using AiTravelClient.Services;

var config = configService.GetConfig();

// 创建API客户端
var apiClient = new ApiClient(
    baseUrl: config.Server.ApiBaseUrl,
    timeout: config.Server.Timeout
);

// 设置设备Token（如果已注册）
if (!string.IsNullOrEmpty(config.Device.DeviceToken))
{
    apiClient.SetDeviceToken(config.Device.DeviceToken);
}

// 测试连接
bool connected = await apiClient.TestConnectionAsync();
Console.WriteLine($"服务器连接: {(connected ? "正常" : "失败")}");
```

---

## 设备注册流程

### 1. 完整注册流程

```csharp
using AiTravelClient.Services;

var deviceService = new DeviceService(configService, apiClient, logService);

// 设备注册参数
string deviceCode = "DEVICE_CODE_FROM_BACKEND";  // 从后台获取的设备码
string deviceName = "摄影门店设备001";
int bid = 100001;  // 商家ID
int mdid = 1;      // 门店ID（可选）

// 执行注册
var response = await deviceService.RegisterDeviceAsync(
    deviceCode, 
    deviceName, 
    bid, 
    mdid
);

if (response.IsSuccess)
{
    Console.WriteLine("设备注册成功！");
    Console.WriteLine($"设备Token: {response.Data.DeviceToken}");
    Console.WriteLine($"应用ID: {response.Data.Aid}");
    
    // Token会自动保存到配置文件（加密存储）
    // 下次启动时会自动加载
}
else
{
    Console.WriteLine($"设备注册失败: {response.Msg}");
}
```

### 2. 验证Token有效性

```csharp
// 验证本地存储的Token是否有效
bool isValid = await deviceService.VerifyTokenAsync();

if (isValid)
{
    Console.WriteLine("Token有效，可以正常使用");
}
else
{
    Console.WriteLine("Token已失效，请重新注册");
    // 清除本地配置，引导用户重新注册
    config.Device.DeviceToken = "";
    configService.SaveConfig(config);
}
```

### 3. 获取设备信息

```csharp
// 从服务器获取设备详细信息
var deviceInfo = await deviceService.GetDeviceInfoAsync();

if (deviceInfo != null)
{
    Console.WriteLine($"设备ID: {deviceInfo.DeviceId}");
    Console.WriteLine($"设备名称: {deviceInfo.DeviceName}");
    Console.WriteLine($"商家ID: {deviceInfo.Bid}");
    Console.WriteLine($"门店ID: {deviceInfo.Mdid}");
    Console.WriteLine($"设备状态: {(deviceInfo.Status == 1 ? "在线" : "离线")}");
    Console.WriteLine($"最后在线时间: {deviceInfo.LastOnlineTime}");
}
```

---

## 文件监控与上传

### 1. 启动文件监控

```csharp
using AiTravelClient.Services;

var fileWatcherService = new FileWatcherService(configService, logService);

// 订阅文件检测事件
fileWatcherService.OnFileDetected += (filePath) =>
{
    Console.WriteLine($"检测到新文件: {filePath}");
    // 自动加入上传队列
    uploadService.AddToQueue(filePath);
};

// 订阅状态变化事件
fileWatcherService.OnStatusChanged += (status) =>
{
    Console.WriteLine($"监控状态变化: {status}");
};

// 订阅错误事件
fileWatcherService.OnWatcherError += (errorMsg) =>
{
    Console.WriteLine($"监控错误: {errorMsg}");
};

// 启动监控
bool started = fileWatcherService.Start();
if (started)
{
    Console.WriteLine("文件监控已启动");
    var watchPaths = fileWatcherService.GetWatchPaths();
    Console.WriteLine($"监控路径: {string.Join(", ", watchPaths)}");
}
```

### 2. 动态管理监控路径

```csharp
// 添加监控路径
string newPath = @"D:\Photos\NewFolder";
bool added = fileWatcherService.AddWatchPath(newPath);
if (added)
{
    Console.WriteLine($"已添加监控路径: {newPath}");
}

// 移除监控路径
bool removed = fileWatcherService.RemoveWatchPath(newPath);
if (removed)
{
    Console.WriteLine($"已移除监控路径: {newPath}");
}

// 清空已处理文件记录（重新处理所有文件）
fileWatcherService.ClearProcessedFiles();
```

### 3. 启动上传服务

```csharp
using AiTravelClient.Services;

var uploadService = new UploadService(apiClient, configService, logService);

// 订阅上传成功事件
uploadService.OnUploadSuccess += (task) =>
{
    Console.WriteLine($"✓ 上传成功: {task.FileName}");
    Console.WriteLine($"  文件大小: {task.GetFormattedFileSize()}");
    Console.WriteLine($"  耗时: {(task.FinishTime - task.StartTime)?.TotalSeconds:F2}秒");
    
    if (task.IsDuplicate)
    {
        Console.WriteLine($"  (重复文件，已跳过)");
    }
    else
    {
        Console.WriteLine($"  人像ID: {task.PortraitId}");
    }
};

// 订阅上传失败事件
uploadService.OnUploadFailed += (task) =>
{
    Console.WriteLine($"✗ 上传失败: {task.FileName}");
    Console.WriteLine($"  错误: {task.ErrorMessage}");
    Console.WriteLine($"  重试次数: {task.RetryCount}");
};

// 订阅队列状态变化事件
uploadService.OnQueueStatusChanged += (status) =>
{
    Console.WriteLine($"队列状态 - 待上传:{status.PendingCount}, 上传中:{status.UploadingCount}");
};

// 启动上传服务
uploadService.Start();
Console.WriteLine("上传服务已启动");
```

### 4. 手动添加文件到上传队列

```csharp
// 手动添加文件
string filePath = @"D:\Photos\photo001.jpg";
string taskId = uploadService.AddToQueue(filePath);

if (!string.IsNullOrEmpty(taskId))
{
    Console.WriteLine($"文件已加入队列，任务ID: {taskId}");
}
else
{
    Console.WriteLine("添加失败，可能是队列已满或文件信息获取失败");
}

// 获取队列状态
var queueStatus = uploadService.GetQueueStatus();
Console.WriteLine($"队列状态:");
Console.WriteLine($"  待上传: {queueStatus.PendingCount}");
Console.WriteLine($"  上传中: {queueStatus.UploadingCount}");
Console.WriteLine($"  总数: {queueStatus.TotalCount}");

// 获取待上传任务列表
var pendingTasks = uploadService.GetPendingTasks();
foreach (var task in pendingTasks)
{
    Console.WriteLine($"  - {task.FileName} ({task.GetFormattedFileSize()})");
}

// 获取正在上传的任务列表
var uploadingTasks = uploadService.GetUploadingTasks();
foreach (var task in uploadingTasks)
{
    Console.WriteLine($"  ↑ {task.FileName} (重试:{task.RetryCount})");
}
```

### 5. 停止服务

```csharp
// 停止文件监控
fileWatcherService.Stop();
Console.WriteLine("文件监控已停止");

// 停止上传服务（等待当前上传完成）
await uploadService.StopAsync();
Console.WriteLine("上传服务已停止");

// 清空上传队列
uploadService.ClearQueue();
Console.WriteLine("上传队列已清空");
```

---

## 心跳与健康监控

### 1. 启动心跳服务

```csharp
using AiTravelClient.Services;

var heartbeatService = new HeartbeatService(apiClient, configService, logService);

// 订阅心跳成功事件
heartbeatService.OnHeartbeatSuccess += () =>
{
    Console.WriteLine("♥ 心跳正常");
};

// 订阅心跳失败事件
heartbeatService.OnHeartbeatFailed += (errorMsg) =>
{
    Console.WriteLine($"✗ 心跳失败: {errorMsg}");
};

// 订阅连续失败告警事件
heartbeatService.OnHeartbeatAlert += (failedCount) =>
{
    Console.WriteLine($"⚠ 警告：心跳连续失败{failedCount}次，请检查网络连接");
    // 可以在这里显示UI告警、发送通知等
};

// 启动心跳服务
heartbeatService.Start();
Console.WriteLine("心跳服务已启动");
```

### 2. 获取心跳状态

```csharp
// 获取心跳状态
var status = heartbeatService.GetStatus();

Console.WriteLine($"心跳服务状态:");
Console.WriteLine($"  运行状态: {(status.IsRunning ? "运行中" : "未启动")}");
Console.WriteLine($"  健康状态: {(status.IsHealthy ? "健康" : "异常")}");
Console.WriteLine($"  失败次数: {status.FailedCount}");
Console.WriteLine($"  最后成功时间: {status.LastSuccessTime}");
Console.WriteLine($"  状态描述: {status.GetStatusDescription()}");
```

### 3. 手动触发心跳

```csharp
// 手动触发一次心跳（用于测试）
bool success = await heartbeatService.TriggerHeartbeatAsync();
if (success)
{
    Console.WriteLine("手动心跳成功");
}
else
{
    Console.WriteLine("手动心跳失败");
}
```

### 4. 停止心跳服务

```csharp
// 停止心跳服务
heartbeatService.Stop();
Console.WriteLine("心跳服务已停止");

// 重置失败计数
heartbeatService.ResetFailedCount();
```

---

## 日志管理

### 1. 记录不同级别的日志

```csharp
// DEBUG级别（仅在开发阶段使用）
logService.Debug("模块名", "这是调试信息，记录详细的执行流程");

// INFO级别（正常业务操作）
logService.Info("模块名", "用户登录成功");
logService.Info("上传服务", "文件上传成功: photo001.jpg");

// WARN级别（警告，但不影响运行）
logService.Warn("心跳服务", "心跳发送失败，将在下次重试");
logService.Warn("文件监控", "检测到临时文件，已跳过");

// ERROR级别（严重错误）
logService.Error("设备服务", "设备注册失败: Token验证失败");

// ERROR级别（含异常信息）
try
{
    // 可能抛出异常的代码
    throw new Exception("测试异常");
}
catch (Exception ex)
{
    logService.Error("模块名", "操作失败", ex);
}
```

### 2. 日志文件管理

```csharp
// 日志文件位置
// - runtime_yyyyMMdd.log（运行日志）
// - error_yyyyMMdd.log（错误日志）
// 位于：应用程序根目录/logs/

// 清理过期日志（保留最近N天）
logService.CleanOldLogs(keepDays: 30);  // 保留30天
logService.CleanOldLogs(keepDays: 7);   // 保留7天

// 日志文件自动切分：
// - 单个文件超过50MB时自动切分
// - 切分后的文件命名：runtime_yyyyMMdd_HHmmss.log
```

### 3. 实时日志显示（用于UI）

```csharp
// 在WPF界面中实时显示日志
logService.OnLogReceived += (timestamp, level, message) =>
{
    // 在UI线程中更新日志显示
    Dispatcher.Invoke(() =>
    {
        // 添加到日志列表
        LogListBox.Items.Add($"{timestamp} [{level}] {message}");
        
        // 自动滚动到最新日志
        LogListBox.ScrollIntoView(LogListBox.Items[LogListBox.Items.Count - 1]);
        
        // 根据日志级别设置颜色
        var item = LogListBox.Items[LogListBox.Items.Count - 1] as ListBoxItem;
        if (level == LogLevel.ERROR)
        {
            item.Foreground = Brushes.Red;
        }
        else if (level == LogLevel.WARN)
        {
            item.Foreground = Brushes.Orange;
        }
    });
};
```

---

## 配置管理

### 1. 修改配置

```csharp
// 获取当前配置
var config = configService.GetConfig();

// 修改服务器配置
config.Server.ApiBaseUrl = "https://new-domain.com";
config.Server.Timeout = 180;
config.Server.RetryTimes = 5;

// 修改监控配置
config.Watcher.WatchPaths.Add(@"D:\Photos");
config.Watcher.ScanInterval = 15;
config.Watcher.FileStableTime = 3;
config.Watcher.MinFileSize = 50;  // KB
config.Watcher.MaxFileSize = 20;  // MB

// 修改上传配置
config.Upload.ConcurrentUploads = 5;
config.Upload.MaxQueueSize = 2000;
config.Upload.MaxRetry = 10;

// 修改心跳配置
config.Heartbeat.Interval = 30;  // 30秒一次心跳

// 保存配置
bool saved = configService.SaveConfig(config);
if (saved)
{
    Console.WriteLine("配置保存成功");
}
```

### 2. 分类更新配置

```csharp
// 仅更新服务器配置
var serverConfig = new ServerConfig
{
    ApiBaseUrl = "https://api.example.com",
    Timeout = 120,
    RetryTimes = 3
};
configService.UpdateServerConfig(serverConfig);

// 仅更新设备配置
var deviceConfig = new DeviceConfig
{
    DeviceId = "AA:BB:CC:DD:EE:FF",
    DeviceToken = "encrypted_token_here",
    DeviceName = "摄影门店设备001",
    Bid = 100001,
    Mdid = 1
};
configService.UpdateDeviceConfig(deviceConfig);

// 仅更新监控配置
var watcherConfig = new WatcherConfig
{
    WatchPaths = new List<string> { @"D:\Photos", @"E:\Pictures" },
    ScanInterval = 10,
    FileStableTime = 2,
    AllowedExtensions = new List<string> { ".jpg", ".jpeg", ".png", ".bmp" },
    MinFileSize = 10,
    MaxFileSize = 50
};
configService.UpdateWatcherConfig(watcherConfig);

// 仅更新上传配置
var uploadConfig = new UploadConfig
{
    ConcurrentUploads = 3,
    ChunkSize = 5,
    MaxQueueSize = 1000,
    AutoUpload = true,
    MaxRetry = 5
};
configService.UpdateUploadConfig(uploadConfig);
```

### 3. 敏感信息加密

```csharp
using AiTravelClient.Utils;

// DeviceToken自动加密存储
// 保存配置时自动加密，加载配置时自动解密
// 使用AES-256加密算法

// 手动加密/解密示例
string originalToken = "my_device_token_12345";
string encryptedToken = EncryptHelper.AesEncrypt(originalToken);
string decryptedToken = EncryptHelper.AesDecrypt(encryptedToken);

Console.WriteLine($"原始Token: {originalToken}");
Console.WriteLine($"加密后: {encryptedToken}");
Console.WriteLine($"解密后: {decryptedToken}");
```

---

## 错误处理

### 1. API调用错误处理

```csharp
// 所有API调用都返回ApiResponse<T>，包含Code和Msg
var response = await deviceService.RegisterDeviceAsync(
    deviceCode, deviceName, bid, mdid
);

if (response.IsSuccess)  // Code == 200
{
    // 成功处理
    var data = response.Data;
}
else
{
    // 失败处理
    Console.WriteLine($"错误码: {response.Code}");
    Console.WriteLine($"错误信息: {response.Msg}");
    
    // 根据错误码进行不同处理
    switch (response.Code)
    {
        case -1:
            Console.WriteLine("网络请求失败，请检查网络连接");
            break;
        case 400:
            Console.WriteLine("参数错误，请检查输入");
            break;
        case 401:
            Console.WriteLine("Token已失效，请重新注册");
            break;
        case 500:
            Console.WriteLine("服务器内部错误，请稍后重试");
            break;
        default:
            Console.WriteLine($"未知错误: {response.Msg}");
            break;
    }
}
```

### 2. 异常捕获

```csharp
// 所有服务方法内部都已捕获异常，不会向上抛出
// 但建议在调用时仍然进行try-catch

try
{
    var result = await deviceService.InitializeAsync();
    if (!result)
    {
        // 初始化失败的处理
    }
}
catch (Exception ex)
{
    // 理论上不会到这里，但保险起见
    logService.Error("主程序", "意外错误", ex);
}
```

### 3. 网络超时处理

```csharp
// API客户端已设置超时时间（默认120秒）
// 超时会自动返回错误响应，不会阻塞程序

// 可以在配置中调整超时时间
config.Server.Timeout = 180;  // 改为180秒
configService.SaveConfig(config);

// 心跳服务有独立的超时设置
config.Heartbeat.Timeout = 10;  // 心跳超时10秒
```

---

## 完整示例

### 主程序示例

```csharp
using System;
using System.Threading.Tasks;
using AiTravelClient.Services;
using AiTravelClient.Models;

namespace AiTravelClient
{
    class Program
    {
        static async Task Main(string[] args)
        {
            Console.WriteLine("=== AI旅拍客户端启动 ===\n");

            // 1. 初始化日志服务
            var logService = new LogService();
            logService.SetMinLevel(LogLevel.INFO);
            logService.OnLogReceived += (timestamp, level, message) =>
            {
                Console.WriteLine($"{timestamp} [{level}] {message}");
            };

            // 2. 初始化配置服务
            var configService = new ConfigService();
            var config = configService.GetConfig();

            // 3. 初始化API客户端
            var apiClient = new ApiClient(
                config.Server.ApiBaseUrl,
                config.Server.Timeout
            );

            // 4. 初始化设备服务
            var deviceService = new DeviceService(
                configService,
                apiClient,
                logService
            );

            // 5. 初始化设备（验证或注册）
            bool initialized = await deviceService.InitializeAsync();
            if (!initialized)
            {
                Console.WriteLine("\n请输入设备注册信息:");
                Console.Write("设备码: ");
                string deviceCode = Console.ReadLine();
                Console.Write("设备名称: ");
                string deviceName = Console.ReadLine();
                Console.Write("商家ID: ");
                int bid = int.Parse(Console.ReadLine());

                var registerResult = await deviceService.RegisterDeviceAsync(
                    deviceCode, deviceName, bid, 0
                );

                if (!registerResult.IsSuccess)
                {
                    Console.WriteLine($"注册失败: {registerResult.Msg}");
                    return;
                }

                Console.WriteLine("设备注册成功！\n");
            }

            // 6. 启动心跳服务
            var heartbeatService = new HeartbeatService(
                apiClient,
                configService,
                logService
            );
            heartbeatService.OnHeartbeatAlert += (failedCount) =>
            {
                Console.WriteLine($"\n⚠ 警告：心跳连续失败{failedCount}次！\n");
            };
            heartbeatService.Start();

            // 7. 启动上传服务
            var uploadService = new UploadService(
                apiClient,
                configService,
                logService
            );
            uploadService.OnUploadSuccess += (task) =>
            {
                Console.WriteLine($"✓ 上传成功: {task.FileName}");
            };
            uploadService.OnUploadFailed += (task) =>
            {
                Console.WriteLine($"✗ 上传失败: {task.FileName} - {task.ErrorMessage}");
            };
            uploadService.Start();

            // 8. 启动文件监控
            var fileWatcherService = new FileWatcherService(
                configService,
                logService
            );
            fileWatcherService.OnFileDetected += (filePath) =>
            {
                uploadService.AddToQueue(filePath);
            };

            if (config.Watcher.WatchPaths.Count > 0)
            {
                fileWatcherService.Start();
            }
            else
            {
                Console.WriteLine("未配置监控路径，文件监控未启动");
            }

            // 9. 等待用户退出
            Console.WriteLine("\n=== 客户端运行中，按任意键退出 ===\n");
            Console.ReadKey();

            // 10. 停止所有服务
            Console.WriteLine("\n正在停止服务...");
            fileWatcherService.Stop();
            await uploadService.StopAsync();
            heartbeatService.Stop();
            apiClient.Dispose();
            logService.CleanOldLogs(30);

            Console.WriteLine("客户端已退出");
        }
    }
}
```

---

## 注意事项

1. **服务启动顺序**：建议按照 日志→配置→API→设备→心跳→上传→监控 的顺序初始化
2. **服务停止顺序**：建议按照 监控→上传→心跳→API 的顺序停止（与启动顺序相反）
3. **Token管理**：Token失效后需要重新注册，客户端会自动检测并提示
4. **配置修改**：修改配置后需要重启相关服务才能生效
5. **并发控制**：默认3个并发上传，根据网络情况可适当调整
6. **内存管理**：长时间运行建议定期重启，或者实现内存监控
7. **日志清理**：定期清理过期日志，避免占用过多磁盘空间

---

**文档版本**：1.0.0  
**更新时间**：2024-01-22
