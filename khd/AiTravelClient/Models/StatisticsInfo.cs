using System;

namespace AiTravelClient.Models
{
    /// <summary>
    /// 统计信息模型
    /// </summary>
    public class StatisticsInfo
    {
        /// <summary>
        /// 今日上传成功数
        /// </summary>
        public int TodaySuccessCount { get; set; }

        /// <summary>
        /// 今日上传失败数
        /// </summary>
        public int TodayFailedCount { get; set; }

        /// <summary>
        /// 累计上传成功数
        /// </summary>
        public int TotalSuccessCount { get; set; }

        /// <summary>
        /// 累计上传失败数
        /// </summary>
        public int TotalFailedCount { get; set; }

        /// <summary>
        /// 队列待上传数
        /// </summary>
        public int QueuePendingCount { get; set; }

        /// <summary>
        /// 正在上传数
        /// </summary>
        public int UploadingCount { get; set; }

        /// <summary>
        /// 获取今日总数
        /// </summary>
        public int GetTodayTotalCount()
        {
            return TodaySuccessCount + TodayFailedCount;
        }

        /// <summary>
        /// 获取今日成功率（百分比）
        /// </summary>
        public double GetTodaySuccessRate()
        {
            int total = GetTodayTotalCount();
            if (total == 0) return 0;
            return (double)TodaySuccessCount / total * 100;
        }

        /// <summary>
        /// 获取累计总数
        /// </summary>
        public int GetTotalCount()
        {
            return TotalSuccessCount + TotalFailedCount;
        }

        /// <summary>
        /// 获取累计成功率（百分比）
        /// </summary>
        public double GetTotalSuccessRate()
        {
            int total = GetTotalCount();
            if (total == 0) return 0;
            return (double)TotalSuccessCount / total * 100;
        }
    }

    /// <summary>
    /// 监控状态枚举
    /// </summary>
    public enum WatcherStatus
    {
        /// <summary>
        /// 监听中
        /// </summary>
        Running = 1,

        /// <summary>
        /// 已暂停
        /// </summary>
        Paused = 2,

        /// <summary>
        /// 异常
        /// </summary>
        Error = 3
    }

    /// <summary>
    /// 网络连接状态枚举
    /// </summary>
    public enum NetworkStatus
    {
        /// <summary>
        /// 已连接
        /// </summary>
        Connected = 1,

        /// <summary>
        /// 未连接
        /// </summary>
        Disconnected = 2,

        /// <summary>
        /// 连接异常
        /// </summary>
        Error = 3
    }

    /// <summary>
    /// API响应基类
    /// </summary>
    /// <typeparam name="T"></typeparam>
    public class ApiResponse<T>
    {
        /// <summary>
        /// 响应码：200=成功
        /// </summary>
        public int Code { get; set; }

        /// <summary>
        /// 响应消息
        /// </summary>
        public string Msg { get; set; }

        /// <summary>
        /// 响应数据
        /// </summary>
        public T Data { get; set; }

        /// <summary>
        /// 是否成功
        /// </summary>
        public bool IsSuccess => Code == 200;
    }
}
