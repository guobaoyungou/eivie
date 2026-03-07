using System;

namespace AiTravelClient.Models
{
    /// <summary>
    /// 设备信息模型
    /// </summary>
    public class DeviceInfo
    {
        /// <summary>
        /// 设备唯一标识（MAC地址）
        /// </summary>
        public string DeviceId { get; set; }

        /// <summary>
        /// 设备名称（商家自定义）
        /// </summary>
        public string DeviceName { get; set; }

        /// <summary>
        /// 设备认证令牌
        /// </summary>
        public string DeviceToken { get; set; }

        /// <summary>
        /// 应用ID
        /// </summary>
        public int Aid { get; set; }

        /// <summary>
        /// 商家ID
        /// </summary>
        public int Bid { get; set; }

        /// <summary>
        /// 门店ID
        /// </summary>
        public int Mdid { get; set; }

        /// <summary>
        /// 操作系统版本
        /// </summary>
        public string OsVersion { get; set; }

        /// <summary>
        /// 客户端版本号
        /// </summary>
        public string ClientVersion { get; set; }

        /// <summary>
        /// 计算机名称
        /// </summary>
        public string PcName { get; set; }

        /// <summary>
        /// CPU信息
        /// </summary>
        public string CpuInfo { get; set; }

        /// <summary>
        /// 内存大小
        /// </summary>
        public string MemorySize { get; set; }

        /// <summary>
        /// 磁盘信息
        /// </summary>
        public string DiskInfo { get; set; }

        /// <summary>
        /// 创建时间
        /// </summary>
        public DateTime CreateTime { get; set; }

        /// <summary>
        /// 最后在线时间
        /// </summary>
        public DateTime LastOnlineTime { get; set; }

        /// <summary>
        /// 设备状态：1=在线，0=离线
        /// </summary>
        public int Status { get; set; }
    }

    /// <summary>
    /// 设备注册请求模型
    /// </summary>
    public class DeviceRegisterRequest
    {
        /// <summary>
        /// 设备唯一标识
        /// </summary>
        public string DeviceId { get; set; }

        /// <summary>
        /// 设备名称
        /// </summary>
        public string DeviceName { get; set; }

        /// <summary>
        /// 设备编码（从后台获取）
        /// </summary>
        public string DeviceCode { get; set; }

        /// <summary>
        /// 商家ID
        /// </summary>
        public int Bid { get; set; }

        /// <summary>
        /// 门店ID
        /// </summary>
        public int Mdid { get; set; }

        /// <summary>
        /// 操作系统版本
        /// </summary>
        public string OsVersion { get; set; }

        /// <summary>
        /// 客户端版本号
        /// </summary>
        public string ClientVersion { get; set; }

        /// <summary>
        /// 计算机名称
        /// </summary>
        public string PcName { get; set; }

        /// <summary>
        /// CPU信息
        /// </summary>
        public string CpuInfo { get; set; }

        /// <summary>
        /// 内存大小
        /// </summary>
        public string MemorySize { get; set; }

        /// <summary>
        /// 磁盘信息
        /// </summary>
        public string DiskInfo { get; set; }
    }

    /// <summary>
    /// 设备注册响应模型
    /// </summary>
    public class DeviceRegisterResponse
    {
        /// <summary>
        /// 设备Token
        /// </summary>
        public string DeviceToken { get; set; }

        /// <summary>
        /// 应用ID
        /// </summary>
        public int Aid { get; set; }

        /// <summary>
        /// 设备信息
        /// </summary>
        public DeviceInfo DeviceInfo { get; set; }
    }
}
