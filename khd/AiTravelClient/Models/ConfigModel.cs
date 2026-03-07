using System;
using System.Collections.Generic;

namespace AiTravelClient.Models
{
    /// <summary>
    /// 配置模型
    /// </summary>
    public class ConfigModel
    {
        /// <summary>
        /// 服务器配置
        /// </summary>
        public ServerConfig Server { get; set; } = new ServerConfig();

        /// <summary>
        /// 设备配置
        /// </summary>
        public DeviceConfig Device { get; set; } = new DeviceConfig();

        /// <summary>
        /// 监控配置
        /// </summary>
        public WatcherConfig Watcher { get; set; } = new WatcherConfig();

        /// <summary>
        /// 上传配置
        /// </summary>
        public UploadConfig Upload { get; set; } = new UploadConfig();

        /// <summary>
        /// 心跳配置
        /// </summary>
        public HeartbeatConfig Heartbeat { get; set; } = new HeartbeatConfig();
    }

    /// <summary>
    /// 服务器配置
    /// </summary>
    public class ServerConfig
    {
        /// <summary>
        /// API服务器地址
        /// </summary>
        public string ApiBaseUrl { get; set; } = "https://domain.com";

        /// <summary>
        /// 请求超时时间（秒）
        /// </summary>
        public int Timeout { get; set; } = 120;

        /// <summary>
        /// 网络请求重试次数
        /// </summary>
        public int RetryTimes { get; set; } = 3;
    }

    /// <summary>
    /// 设备配置
    /// </summary>
    public class DeviceConfig
    {
        /// <summary>
        /// 设备唯一标识（MAC地址）
        /// </summary>
        public string DeviceId { get; set; } = "";

        /// <summary>
        /// 设备认证令牌（加密存储）
        /// </summary>
        public string DeviceToken { get; set; } = "";

        /// <summary>
        /// 设备名称
        /// </summary>
        public string DeviceName { get; set; } = "";

        /// <summary>
        /// 应用ID
        /// </summary>
        public int Aid { get; set; } = 0;

        /// <summary>
        /// 商家ID
        /// </summary>
        public int Bid { get; set; } = 0;

        /// <summary>
        /// 门店ID
        /// </summary>
        public int Mdid { get; set; } = 0;
    }

    /// <summary>
    /// 监控配置
    /// </summary>
    public class WatcherConfig
    {
        /// <summary>
        /// 监控文件夹路径列表
        /// </summary>
        public List<string> WatchPaths { get; set; } = new List<string>();

        /// <summary>
        /// 轮询间隔（秒）
        /// </summary>
        public int ScanInterval { get; set; } = 10;

        /// <summary>
        /// 文件稳定等待时间（秒）
        /// </summary>
        public int FileStableTime { get; set; } = 2;

        /// <summary>
        /// 允许的文件扩展名
        /// </summary>
        public List<string> AllowedExtensions { get; set; } = new List<string> { ".jpg", ".jpeg", ".png" };

        /// <summary>
        /// 最小文件大小（KB）
        /// </summary>
        public int MinFileSize { get; set; } = 10;

        /// <summary>
        /// 最大文件大小（MB）
        /// </summary>
        public int MaxFileSize { get; set; } = 10;
    }

    /// <summary>
    /// 上传配置
    /// </summary>
    public class UploadConfig
    {
        /// <summary>
        /// 并发上传数
        /// </summary>
        public int ConcurrentUploads { get; set; } = 3;

        /// <summary>
        /// 分片上传大小（MB）
        /// </summary>
        public int ChunkSize { get; set; } = 5;

        /// <summary>
        /// 队列最大长度
        /// </summary>
        public int MaxQueueSize { get; set; } = 1000;

        /// <summary>
        /// 是否自动上传
        /// </summary>
        public bool AutoUpload { get; set; } = true;

        /// <summary>
        /// 最大重试次数
        /// </summary>
        public int MaxRetry { get; set; } = 5;
    }

    /// <summary>
    /// 心跳配置
    /// </summary>
    public class HeartbeatConfig
    {
        /// <summary>
        /// 心跳间隔（秒）
        /// </summary>
        public int Interval { get; set; } = 60;

        /// <summary>
        /// 心跳超时（秒）
        /// </summary>
        public int Timeout { get; set; } = 10;
    }
}
