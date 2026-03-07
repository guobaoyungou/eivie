# AI旅拍商家Windows客户端

## 项目简介

AI旅拍商家Windows客户端是部署在商家摄影门店的桌面应用程序，用于实现旅拍照片的自动化采集、上传和管理。

### 核心功能

- **自动监控**: 实时监控指定文件夹,检测新增照片
- **智能上传**: 自动上传检测到的照片到服务器
- **设备管理**: 设备注册认证,心跳保活
- **失败重试**: 上传失败自动重试,支持断网续传
- **文件去重**: 基于MD5的文件去重机制
- **实时统计**: 显示上传统计、队列状态、运行日志

## 技术栈

- **开发语言**: C# (.NET Framework 4.7.2)
- **界面框架**: WPF (Windows Presentation Foundation)
- **文件监控**: FileSystemWatcher
- **HTTP通信**: HttpClient
- **JSON处理**: Newtonsoft.Json
- **日志管理**: 自定义日志服务
- **配置管理**: JSON文件存储

## 项目结构

```
AiTravelClient/
├── Models/                    # 数据模型层
│   ├── ConfigModel.cs         # 配置模型
│   ├── DeviceInfo.cs          # 设备信息模型
│   ├── UploadTask.cs          # 上传任务模型
│   └── StatisticsInfo.cs      # 统计信息模型
├── Services/                  # 业务服务层
│   ├── ApiClient.cs           # API通信客户端
│   ├── ConfigService.cs       # 配置管理服务
│   └── LogService.cs          # 日志服务
├── Utils/                     # 工具类
│   ├── FileHelper.cs          # 文件操作辅助
│   ├── Md5Helper.cs           # MD5计算工具
│   ├── EncryptHelper.cs       # 加密解密工具
│   └── SystemInfoHelper.cs    # 系统信息获取工具
├── config.json                # 配置文件
└── AiTravelClient.csproj      # 项目文件
```

## 系统要求

### 最低配置
- 操作系统: Windows 7 SP1 64位
- .NET Framework: 4.7.2
- CPU: 双核 2.0GHz
- 内存: 2GB
- 磁盘空间: 100MB
- 网络: 宽带连接

### 推荐配置
- 操作系统: Windows 10/11 64位
- .NET Framework: 4.8
- CPU: 四核 2.5GHz
- 内存: 4GB及以上
- 磁盘空间: 500MB
- 网络: 有线网络

## 快速开始

### 1. 开发环境搭建

```bash
# 克隆项目
cd /www/wwwroot/eivie/khd

# 使用 Visual Studio 2019/2022 打开项目
# 打开 AiTravelClient.sln
```

### 2. 配置文件说明

编辑 `config.json` 文件:

```json
{
  "server": {
    "apiBaseUrl": "https://your-domain.com",  // API服务器地址
    "timeout": 120,                            // 请求超时(秒)
    "retryTimes": 3                            // 重试次数
  },
  "device": {
    "deviceId": "",                            // 设备ID(自动生成)
    "deviceToken": "",                         // 设备Token(注册后获得)
    "deviceName": "摄影门店设备",               // 设备名称
    "bid": 0,                                  // 商家ID
    "mdid": 0                                  // 门店ID
  },
  "watcher": {
    "watchPaths": [],                          // 监控文件夹路径列表
    "scanInterval": 10,                        // 扫描间隔(秒)
    "fileStableTime": 2,                       // 文件稳定时间(秒)
    "allowedExtensions": [".jpg", ".jpeg", ".png"],  // 允许的文件类型
    "minFileSize": 10,                         // 最小文件大小(KB)
    "maxFileSize": 10                          // 最大文件大小(MB)
  },
  "upload": {
    "concurrentUploads": 3,                    // 并发上传数
    "maxQueueSize": 1000,                      // 队列最大长度
    "autoUpload": true,                        // 是否自动上传
    "maxRetry": 5                              // 最大重试次数
  },
  "heartbeat": {
    "interval": 60,                            // 心跳间隔(秒)
    "timeout": 10                              // 心跳超时(秒)
  }
}
```

### 3. 编译项目

使用 Visual Studio:
1. 打开解决方案
2. 选择 Release 配置
3. 生成 → 生成解决方案
4. 编译输出位于 `bin/Release/` 目录

使用命令行:
```bash
# 使用 MSBuild 编译
msbuild AiTravelClient.sln /p:Configuration=Release
```

### 4. 运行客户端

1. 首次运行需要进行设备注册
2. 输入服务器地址和设备编码
3. 添加需要监控的文件夹路径
4. 点击"开始监控"启动自动上传

## API接口说明

### 设备注册
```
POST /api/ai_travel_photo/device/register
```

### 发送心跳
```
POST /api/ai_travel_photo/device/heartbeat
Header: Device-Token
```

### 文件上传
```
POST /api/ai_travel_photo/device/upload
Header: Device-Token
Content-Type: multipart/form-data
参数:
  - file: 图片文件
  - md5: 文件MD5值
  - file_size: 文件大小
```

### 获取配置
```
GET /api/ai_travel_photo/device/config
Header: Device-Token
```

### 获取设备信息
```
GET /api/ai_travel_photo/device/info
Header: Device-Token
```

## 功能特性

### 文件监控
- 实时监听文件系统变化
- 支持多个文件夹同时监控
- 文件类型过滤(.jpg, .jpeg, .png)
- 文件大小过滤(10KB ~ 10MB)
- 临时文件自动排除
- 文件稳定性检测(避免上传未完成的文件)

### 上传管理
- 并发上传控制(默认3个并发)
- 上传队列管理(最大1000个任务)
- 失败自动重试(最多5次)
- 指数退避策略(5s, 10s, 20s, 40s, 60s)
- 基于MD5的文件去重
- 上传进度实时显示

### 设备管理
- 设备唯一标识(基于MAC地址)
- Token认证机制
- 心跳保活(每60秒)
- 设备信息上报
- 在线状态监控

### 日志管理
- 分级日志(DEBUG/INFO/WARN/ERROR)
- 日志文件按日期分割
- 日志文件自动切分(超过50MB)
- 过期日志自动清理(保留30天)
- 实时日志界面显示

### 安全特性
- Token加密存储(AES)
- HTTPS通信
- 文件完整性校验(MD5)
- 日志脱敏处理

## 开发指南

### 添加新功能

1. 在 `Models/` 目录添加数据模型
2. 在 `Services/` 目录添加业务服务
3. 在 `Utils/` 目录添加工具类
4. 在界面层调用服务接口

### 代码规范

- 使用C#命名约定
- 添加XML文档注释
- 异常处理要完善
- 日志记录要详细

### 测试建议

- 功能测试: 测试所有核心功能
- 性能测试: 测试并发上传性能
- 稳定性测试: 长时间运行测试
- 兼容性测试: 多Windows版本测试

## 部署指南

### 打包发布

1. 编译Release版本
2. 收集所有依赖文件
3. 创建安装程序(可使用Inno Setup)
4. 测试安装程序

### 安装步骤

1. 运行安装程序
2. 选择安装路径
3. 安装.NET Framework依赖(如需要)
4. 完成安装

### 首次配置

1. 启动客户端
2. 输入服务器地址
3. 输入设备编码和商家信息
4. 完成设备注册
5. 添加监控文件夹
6. 开始自动监控上传

## 常见问题

### Q: 上传失败怎么办?
A: 检查网络连接、服务器地址、设备Token是否有效。查看日志文件获取详细错误信息。

### Q: 文件没有被检测到?
A: 检查文件类型和大小是否符合配置要求,检查监控路径是否正确。

### Q: 如何查看日志?
A: 日志文件位于程序目录的 `logs/` 文件夹下,可以使用文本编辑器打开查看。

### Q: 如何重新注册设备?
A: 删除 `config.json` 中的 deviceToken,重启客户端重新注册。

## 版本历史

### v1.0.0 (2024-01-XX)
- 首次发布
- 实现基础功能:设备注册、文件监控、自动上传、心跳保活
- 支持失败重试和文件去重
- 完整的日志记录和配置管理

## 许可证

Copyright © 2024 Your Company. All rights reserved.

## 联系方式

- 技术支持: support@your-company.com
- 项目地址: /www/wwwroot/eivie/khd/
- 文档位置: /www/wwwroot/eivie/khd/docs/

## 致谢

感谢所有参与项目开发和测试的团队成员。
