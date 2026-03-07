# AI旅拍Windows客户端 - 核心服务层

## 项目简介

AI旅拍Windows客户端是一款专为摄影门店设计的自动化照片采集与上传工具。客户端实时监控指定文件夹，自动将新生成的照片上传至云端服务器，由AI进行人像识别、美化处理，极大提升门店运营效率。

### 核心功能

- 🔐 **设备注册与认证** - 通过设备码完成设备激活，获取通信令牌
- 📁 **智能文件监控** - 实时监控指定文件夹，检测新增的图片文件
- ⬆️ **并发文件上传** - 支持多线程上传，自动重试失败任务
- 💓 **心跳保活机制** - 定期向服务器发送心跳，维持设备在线状态
- ⚙️ **灵活配置管理** - 可视化配置管理，敏感信息加密存储
- 📊 **完善日志系统** - 多级别日志记录，自动切分与过期清理

### 技术特点

- ✅ 完整的服务层架构（Models/Services/Utils）
- ✅ 线程安全的并发控制（锁、信号量、并发集合）
- ✅ 健壮的异常处理机制
- ✅ 智能的文件过滤与去重
- ✅ 指数退避的重试策略
- ✅ AES-256敏感信息加密

---

## 快速开始

### 系统要求

- **操作系统**: Windows 7 SP1 / Windows 10 / Windows 11
- **运行时**: .NET Framework 4.7.2 或更高版本
- **内存**: 至少 2GB RAM
- **磁盘**: 至少 100MB 可用空间（不含照片存储）
- **网络**: 稳定的互联网连接

### 项目结构

```
/khd/AiTravelClient/
├── Models/                     # 数据模型层
│   ├── ConfigModel.cs         # 配置模型
│   ├── DeviceInfo.cs          # 设备信息
│   ├── UploadTask.cs          # 上传任务
│   └── StatisticsInfo.cs      # 统计信息
├── Services/                   # 服务层
│   ├── ApiClient.cs           # API客户端
│   ├── ConfigService.cs       # 配置管理
│   ├── LogService.cs          # 日志服务
│   ├── DeviceService.cs       # 设备服务
│   ├── HeartbeatService.cs    # 心跳服务
│   ├── FileWatcherService.cs  # 文件监控
│   └── UploadService.cs       # 上传服务
├── Utils/                      # 工具类
│   ├── EncryptHelper.cs       # 加密工具
│   ├── FileHelper.cs          # 文件工具
│   ├── Md5Helper.cs           # MD5工具
│   └── SystemInfoHelper.cs    # 系统信息
├── ViewModels/                 # 视图模型（待实现）
├── Views/                      # 视图界面（待实现）
├── Resources/                  # 资源文件
├── config.json                 # 配置文件
└── AiTravelClient.csproj      # 项目文件
```

### 安装与配置

1. **克隆项目**
   ```bash
   cd /www/wwwroot/eivie/khd/AiTravelClient
   ```

2. **配置服务器地址**
   
   编辑 `config.json` 文件：
   ```json
   {
     "server": {
       "apiBaseUrl": "https://your-domain.com",
       "timeout": 120,
       "retryTimes": 3
     }
   }
   ```

3. **设备注册**
   
   首次运行需要注册设备：
   - 从后台管理系统获取设备码
   - 运行客户端，输入设备码和商家信息
   - 系统自动完成注册并保存Token

4. **配置监控路径**
   
   在配置文件中添加监控路径：
   ```json
   {
     "watcher": {
       "watchPaths": [
         "D:\\Photos",
         "E:\\Pictures"
       ]
     }
   }
   ```

---

## 核心服务说明

### 1. ConfigService - 配置管理服务

负责配置文件的加载、保存和敏感信息加密。

**主要功能：**
- 配置文件自动加载与保存
- DeviceToken AES-256加密存储
- 线程安全的配置读写
- 支持配置项分类更新

**使用示例：**
```csharp
var configService = new ConfigService();
var config = configService.GetConfig();
config.Server.ApiBaseUrl = "https://new-domain.com";
configService.SaveConfig(config);
```

### 2. ApiClient - API客户端服务

封装所有与服务器的HTTP通信。

**主要功能：**
- 统一的Token管理
- 超时控制与异常处理
- JSON序列化/反序列化
- 支持所有API接口调用

**API接口列表：**
- `POST /api/ai_travel_photo/device/register` - 设备注册
- `POST /api/ai_travel_photo/device/heartbeat` - 心跳
- `GET /api/ai_travel_photo/device/info` - 获取设备信息
- `POST /api/ai_travel_photo/device/upload` - 上传文件
- `GET /api/ai_travel_photo/device/ping` - 连接测试

### 3. DeviceService - 设备服务

管理设备注册、Token验证和系统信息收集。

**主要功能：**
- 设备注册流程管理
- Token有效性验证
- 系统信息自动收集（MAC、CPU、内存等）
- 设备状态维护

**使用示例：**
```csharp
var deviceService = new DeviceService(configService, apiClient, logService);
var response = await deviceService.RegisterDeviceAsync(
    deviceCode: "DEVICE_CODE",
    deviceName: "摄影门店设备001",
    bid: 100001,
    mdid: 1
);
```

### 4. FileWatcherService - 文件监控服务

实时监控指定文件夹，检测新增图片文件。

**主要功能：**
- 双重监控机制（FileSystemWatcher + 定时轮询）
- 智能文件过滤（扩展名、大小、临时文件）
- 文件稳定性检查（等待传输完成）
- MD5去重避免重复处理
- 内存优化（限制MD5集合大小）

**文件过滤规则：**
- 扩展名：仅处理 .jpg、.jpeg、.png
- 文件大小：10KB - 10MB（可配置）
- 临时文件：自动跳过以.或~开头的文件
- 文件稳定性：等待2秒确认大小不再变化

### 5. UploadService - 上传服务

管理上传队列，执行并发上传，处理失败重试。

**主要功能：**
- 并发上传控制（默认3个线程）
- 上传队列管理（最大1000个任务）
- 智能重试机制（最多5次，指数退避）
- 信号量并发控制
- 实时进度反馈

**重试策略：**
- 第1次重试：延迟5秒
- 第2次重试：延迟10秒
- 第3次重试：延迟20秒
- 第4次重试：延迟40秒
- 第5次重试：延迟60秒

### 6. HeartbeatService - 心跳服务

定期向服务器发送心跳包，保持设备在线状态。

**主要功能：**
- 定时心跳发送（默认60秒）
- 心跳失败计数与告警
- 健康状态监控
- 事件通知机制

**告警机制：**
- 连续失败3次触发告警
- 通过事件通知UI层显示警告
- 自动重置失败计数

### 7. LogService - 日志服务

多级别日志记录，支持文件切分和过期清理。

**主要功能：**
- 多级别日志（DEBUG/INFO/WARN/ERROR）
- 日志文件自动切分（超过50MB）
- 过期日志自动清理（保留30天）
- 实时日志事件通知
- 线程安全的日志写入

**日志文件：**
- `runtime_yyyyMMdd.log` - 运行日志
- `error_yyyyMMdd.log` - 错误日志
- 位于：`logs/` 目录

---

## 配置说明

### 配置文件结构

```json
{
  "server": {
    "apiBaseUrl": "https://your-domain.com",   // API服务器地址
    "timeout": 120,                             // 请求超时（秒）
    "retryTimes": 3                             // 请求重试次数
  },
  "device": {
    "deviceId": "",                             // 设备ID（MAC地址）
    "deviceToken": "",                          // 设备Token（加密存储）
    "deviceName": "摄影门店设备",               // 设备名称
    "aid": 0,                                   // 应用ID
    "bid": 0,                                   // 商家ID
    "mdid": 0                                   // 门店ID
  },
  "watcher": {
    "watchPaths": [],                           // 监控路径列表
    "scanInterval": 10,                         // 轮询间隔（秒）
    "fileStableTime": 2,                        // 文件稳定等待时间（秒）
    "allowedExtensions": [".jpg", ".jpeg", ".png"],  // 允许的文件扩展名
    "minFileSize": 10,                          // 最小文件大小（KB）
    "maxFileSize": 10                           // 最大文件大小（MB）
  },
  "upload": {
    "concurrentUploads": 3,                     // 并发上传数
    "chunkSize": 5,                             // 分片大小（MB）
    "maxQueueSize": 1000,                       // 队列最大长度
    "autoUpload": true,                         // 是否自动上传
    "maxRetry": 5                               // 最大重试次数
  },
  "heartbeat": {
    "interval": 60,                             // 心跳间隔（秒）
    "timeout": 10                               // 心跳超时（秒）
  }
}
```

### 常用配置调整

**提高上传速度：**
```json
{
  "upload": {
    "concurrentUploads": 5  // 增加并发数到5
  }
}
```

**增加文件大小限制：**
```json
{
  "watcher": {
    "maxFileSize": 50  // 增加到50MB
  }
}
```

**缩短心跳间隔：**
```json
{
  "heartbeat": {
    "interval": 30  // 改为30秒
  }
}
```

---

## 开发指南

### 依赖包

项目使用以下NuGet包：

```xml
<PackageReference Include="Newtonsoft.Json" Version="13.0.3" />
<PackageReference Include="System.Management" Version="7.0.0" />
```

### 编译项目

使用Visual Studio或命令行编译：

```bash
# 使用dotnet CLI
cd /www/wwwroot/eivie/khd/AiTravelClient
dotnet build

# 使用MSBuild
msbuild AiTravelClient.csproj /p:Configuration=Release
```

### 服务初始化示例

```csharp
// 1. 创建核心服务
var logService = new LogService();
var configService = new ConfigService();
var apiClient = new ApiClient(config.Server.ApiBaseUrl);
var deviceService = new DeviceService(configService, apiClient, logService);

// 2. 初始化设备
await deviceService.InitializeAsync();

// 3. 启动心跳
var heartbeatService = new HeartbeatService(apiClient, configService, logService);
heartbeatService.Start();

// 4. 启动上传
var uploadService = new UploadService(apiClient, configService, logService);
uploadService.Start();

// 5. 启动监控
var fileWatcherService = new FileWatcherService(configService, logService);
fileWatcherService.OnFileDetected += (filePath) => uploadService.AddToQueue(filePath);
fileWatcherService.Start();
```

### 事件订阅示例

```csharp
// 文件检测事件
fileWatcherService.OnFileDetected += (filePath) => {
    Console.WriteLine($"检测到新文件: {filePath}");
};

// 上传成功事件
uploadService.OnUploadSuccess += (task) => {
    Console.WriteLine($"上传成功: {task.FileName}");
};

// 心跳告警事件
heartbeatService.OnHeartbeatAlert += (failedCount) => {
    Console.WriteLine($"心跳失败{failedCount}次");
};
```

---

## 性能指标

### 预期性能

| 指标 | 目标值 |
|------|--------|
| 文件检测延迟 | ≤2秒 |
| 心跳间隔 | 60秒 |
| 上传并发数 | 3个文件 |
| 队列容量 | 1000个任务 |
| 内存占用（空闲） | ≤100MB |
| 内存占用（满负载） | ≤300MB |
| CPU占用（空闲） | ≤5% |
| CPU占用（上传中） | ≤30% |

### 优化建议

1. **根据网络带宽调整并发数**
   - 网络好：增加到5-10个并发
   - 网络差：减少到1-2个并发

2. **根据文件大小调整重试策略**
   - 小文件（<1MB）：快速重试
   - 大文件（>5MB）：延长重试间隔

3. **定期清理日志文件**
   - 保留时间：7-30天
   - 自动清理：每天凌晨执行

---

## 已知限制

1. **队列持久化**：当前版本未实现队列持久化，重启后队列清空
2. **断点续传**：暂不支持断点续传，上传失败需重新上传整个文件
3. **大文件支持**：默认限制10MB，大文件需调整配置
4. **单实例运行**：未实现单实例检测，可能导致多个实例同时运行

---

## 文档资源

- 📘 [实现总结文档](docs/IMPLEMENTATION_SUMMARY.md) - 完整的实现说明
- 📗 [API使用指南](docs/API_USAGE_GUIDE.md) - 详细的API调用示例
- 📕 [设计文档](../../文档/Windows客户端核心服务层设计文档.md) - 架构设计说明

---

## 待实现功能

### 高优先级
- [ ] WPF用户界面开发
- [ ] 本地数据库存储（上传历史）
- [ ] 失败任务持久化
- [ ] 单实例检测

### 中优先级
- [ ] 断点续传支持
- [ ] 分片上传支持
- [ ] 上传速度限制
- [ ] 系统托盘功能

### 低优先级
- [ ] 自动更新功能
- [ ] 崩溃日志上报
- [ ] 统计数据图表
- [ ] 多语言支持

---

## 常见问题

### Q1: 设备注册失败怎么办？

**A:** 检查以下几点：
1. 网络连接是否正常
2. 服务器地址是否正确
3. 设备码是否有效
4. 商家ID是否正确

### Q2: 文件监控不工作？

**A:** 检查以下几点：
1. 监控路径是否已配置
2. 监控路径是否存在
3. 文件扩展名是否在允许列表中
4. 文件大小是否符合要求

### Q3: 上传速度慢？

**A:** 可以尝试：
1. 增加并发上传数
2. 检查网络带宽
3. 减小文件大小限制
4. 检查服务器性能

### Q4: 心跳连续失败？

**A:** 检查以下几点：
1. 网络连接是否稳定
2. 服务器是否正常运行
3. Token是否已失效
4. 防火墙是否阻止连接

---

## 技术支持

- **邮箱**: support@example.com
- **电话**: 400-xxx-xxxx
- **工作时间**: 周一至周五 9:00-18:00

---

## 版本历史

### v1.0.0 (2024-01-22)
- ✅ 实现所有核心服务层
- ✅ 完成数据模型定义
- ✅ 实现工具类库
- ✅ 完成配置管理
- ✅ 实现API客户端
- ✅ 完成文档编写

---

## 许可证

Copyright © 2024 AI Travel Photo Team. All rights reserved.

---

**项目路径**: `/www/wwwroot/eivie/khd/AiTravelClient`  
**文档版本**: 1.0.0  
**最后更新**: 2024-01-22
