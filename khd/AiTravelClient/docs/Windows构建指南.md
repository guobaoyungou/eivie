# AI旅拍商家Windows客户端 - Windows环境构建指南

## 📋 系统要求

### 开发环境
- **操作系统**: Windows 10 1809 或更高版本 / Windows 11
- **Visual Studio**: 2019 (16.8+) 或 2022
- **.NET Framework**: 4.7.2 或更高版本
- **内存**: 至少 4GB RAM（推荐 8GB+）
- **磁盘空间**: 至少 10GB 可用空间

### 运行环境
- **操作系统**: Windows 10 1809+ / Windows 11
- **.NET Framework**: 4.7.2 运行时
- **内存**: 至少 2GB RAM
- **磁盘空间**: 至少 500MB 可用空间

## 🚀 快速开始

### 方法1：使用Visual Studio（推荐）

#### 1. 安装Visual Studio

下载并安装 Visual Studio 2019/2022：
- 官方下载地址: https://visualstudio.microsoft.com/zh-hans/downloads/
- 选择"Community"版本（免费）

**必需工作负载**：
- ✅ .NET桌面开发
- ✅ Windows Presentation Foundation (WPF)

**可选组件**：
- Git for Windows
- NuGet包管理器

#### 2. 获取项目代码

```powershell
# 方式A：从服务器复制
# 将整个 /www/wwwroot/eivie/khd/AiTravelClient 目录复制到本地

# 方式B：使用Git（如果项目在Git仓库中）
git clone <repository-url>
cd AiTravelClient
```

#### 3. 打开项目

1. 启动 Visual Studio
2. 点击"打开项目或解决方案"
3. 选择 `AiTravelClient.csproj` 文件
4. 等待项目加载完成

#### 4. 还原NuGet包

项目依赖的NuGet包：
- Newtonsoft.Json (13.0.3)
- System.Management (7.0.0)

还原方式：
```
方式1: Visual Studio会自动提示还原，点击"还原"按钮
方式2: 右键解决方案 -> 还原NuGet包
方式3: 工具 -> NuGet包管理器 -> 包管理器控制台，执行：
       dotnet restore
```

#### 5. 构建项目

```
方式1: 按 Ctrl + Shift + B
方式2: 菜单栏 -> 生成 -> 生成解决方案
方式3: 右键解决方案 -> 生成
```

**构建输出**：
- Debug版本: `bin\Debug\net472\AiTravelClient.exe`
- Release版本: `bin\Release\net472\AiTravelClient.exe`

#### 6. 运行项目

```
方式1: 按 F5（调试模式）
方式2: 按 Ctrl + F5（非调试模式）
方式3: 菜单栏 -> 调试 -> 开始调试
```

### 方法2：使用命令行

#### 1. 安装.NET Framework SDK

确保已安装.NET Framework 4.7.2 开发者包：
- 下载地址: https://dotnet.microsoft.com/download/dotnet-framework/net472

#### 2. 安装MSBuild

MSBuild通常随Visual Studio安装，独立安装：
- 下载Build Tools: https://visualstudio.microsoft.com/downloads/#build-tools-for-visual-studio-2022

#### 3. 命令行构建

```powershell
# 导航到项目目录
cd C:\path\to\AiTravelClient

# 还原NuGet包
nuget restore AiTravelClient.csproj

# 构建Debug版本
msbuild AiTravelClient.csproj /p:Configuration=Debug

# 构建Release版本
msbuild AiTravelClient.csproj /p:Configuration=Release

# 运行程序
.\bin\Debug\net472\AiTravelClient.exe
```

## 🔧 配置说明

### 配置文件：config.json

首次运行时，请编辑 `config.json` 文件：

```json
{
  "Server": {
    "ApiBaseUrl": "https://your-api-domain.com",
    "Timeout": 120,
    "RetryTimes": 3
  },
  "Device": {
    "DeviceId": "",
    "DeviceToken": "",
    "DeviceName": "商家电脑-001",
    "Aid": 1,
    "Bid": 1,
    "Mdid": 1
  },
  "Watcher": {
    "WatchPaths": [
      "C:\\Photos\\ToUpload"
    ],
    "ScanInterval": 10,
    "FileStableTime": 2,
    "AllowedExtensions": [".jpg", ".jpeg", ".png"],
    "MinFileSize": 10,
    "MaxFileSize": 10
  },
  "Upload": {
    "ConcurrentUploads": 3,
    "ChunkSize": 5,
    "MaxQueueSize": 1000,
    "AutoUpload": true,
    "MaxRetry": 5
  },
  "Heartbeat": {
    "Interval": 60,
    "Timeout": 10
  }
}
```

**关键配置项**：
1. **ApiBaseUrl**: 修改为您的实际API服务器地址
2. **Aid/Bid/Mdid**: 从后台获取的应用ID、商家ID、门店ID
3. **WatchPaths**: 设置要监控的文件夹路径
4. **DeviceCode**: 从后台获取的设备编码（首次注册时需要）

## 📦 发布部署

### 创建Release版本

#### 1. 使用Visual Studio发布

```
步骤1: 右键项目 -> 发布
步骤2: 选择目标 -> 文件夹
步骤3: 配置发布配置文件：
       - 目标框架: net472
       - 部署模式: 框架依赖
       - 目标运行时: win-x64 或 win-x86
步骤4: 点击"发布"按钮
```

#### 2. 手动构建Release版本

```powershell
# 清理之前的构建
msbuild /t:Clean

# 构建Release版本
msbuild /t:Build /p:Configuration=Release

# 输出目录
# bin\Release\net472\
```

### 创建安装包

#### 方法1：使用WiX Toolset

```powershell
# 1. 安装WiX Toolset
# 下载: https://wixtoolset.org/releases/

# 2. 添加WiX项目到解决方案
# 3. 配置Product.wxs文件
# 4. 构建安装包
```

#### 方法2：使用Inno Setup

```powershell
# 1. 安装Inno Setup
# 下载: https://jrsoftware.org/isdl.php

# 2. 创建安装脚本（setup.iss）
# 3. 编译安装包
```

#### 方法3：绿色部署包

```powershell
# 将以下文件打包为ZIP：
Release/
├── AiTravelClient.exe
├── config.json
├── Newtonsoft.Json.dll
├── System.Management.dll
└── README.txt（使用说明）
```

## 🐛 常见问题

### 问题1：无法还原NuGet包

**解决方案**：
```powershell
# 清除NuGet缓存
nuget locals all -clear

# 配置NuGet源
nuget sources add -Name "nuget.org" -Source "https://api.nuget.org/v3/index.json"

# 重新还原
nuget restore
```

### 问题2：缺少.NET Framework

**解决方案**：
- 安装.NET Framework 4.7.2 Runtime
- 下载地址: https://dotnet.microsoft.com/download/dotnet-framework/net472

### 问题3：XAML设计器无法加载

**解决方案**：
```
1. 工具 -> 选项 -> XAML设计器
2. 勾选"启用XAML设计器"
3. 重启Visual Studio
```

### 问题4：无法找到SystemInfoHelper

**解决方案**：
```
检查 Utils/SystemInfoHelper.cs 是否存在
确保项目中包含了System.Management包引用
```

### 问题5：App.xaml.cs编译错误

**解决方案**：
```csharp
// 确保App.xaml中的x:Class与代码文件中的namespace一致
// App.xaml:
x:Class="AiTravelClient.App"

// App.xaml.cs:
namespace AiTravelClient
{
    public partial class App : Application
    {
        // ...
    }
}
```

## 🎯 调试技巧

### 1. 启用调试日志

在 `App.xaml.cs` 中添加：
```csharp
protected override void OnStartup(StartupEventArgs e)
{
    base.OnStartup(e);
    
    #if DEBUG
    logService.SetMinLevel(LogLevel.DEBUG);
    #endif
    
    // ...
}
```

### 2. 查看日志文件

日志位置：
```
程序目录/logs/runtime_YYYYMMDD.log
程序目录/logs/error_YYYYMMDD.log
```

### 3. 使用断点调试

```
F9: 设置/取消断点
F5: 开始调试
F10: 单步跳过
F11: 单步进入
Shift+F5: 停止调试
```

### 4. 查看输出窗口

```
视图 -> 输出 (Ctrl+Alt+O)
```

## 📝 版本信息

### 当前版本：1.0.0

**版本历史**：
- v1.0.0 (2024-01-23)
  - 初始版本发布
  - 实现文件监控功能
  - 实现自动上传功能
  - 实现设备注册管理
  - 实现日志系统

### 升级说明

```
1. 备份现有config.json配置文件
2. 停止运行中的旧版本程序
3. 覆盖新版本文件
4. 恢复config.json配置
5. 启动新版本程序
```

## 🔐 安全注意事项

1. **配置文件加密**
   - DeviceToken在config.json中是加密存储的
   - 不要随意修改DeviceToken字段

2. **API通信安全**
   - 使用HTTPS协议通信
   - 验证服务器证书

3. **文件访问权限**
   - 确保程序对监控文件夹有读取权限
   - 确保程序对日志目录有写入权限

## 📞 技术支持

如遇到其他问题，请联系技术支持：
- 邮箱: support@example.com
- 电话: 400-xxx-xxxx
- 技术文档: https://docs.example.com

## 📄 相关文档

- [用户手册](./用户手册.md)
- [开发文档](./开发文档.md)
- [API文档](./API文档.md)
- [部署指南](./部署指南.md)

---

**最后更新**: 2024-01-23  
**维护者**: AI Travel Photo Team
