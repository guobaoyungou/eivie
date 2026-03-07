using System;
using System.ComponentModel;

namespace AiTravelClient.Models
{
    /// <summary>
    /// 上传任务状态枚举
    /// </summary>
    public enum UploadTaskStatus
    {
        /// <summary>
        /// 待上传
        /// </summary>
        [Description("待上传")]
        Pending = 0,

        /// <summary>
        /// 上传中
        /// </summary>
        [Description("上传中")]
        Uploading = 1,

        /// <summary>
        /// 上传成功
        /// </summary>
        [Description("上传成功")]
        Success = 2,

        /// <summary>
        /// 上传失败
        /// </summary>
        [Description("上传失败")]
        Failed = 3,

        /// <summary>
        /// 重试中
        /// </summary>
        [Description("重试中")]
        Retrying = 4,

        /// <summary>
        /// 最终失败
        /// </summary>
        [Description("最终失败")]
        FinalFailed = 5
    }

    /// <summary>
    /// 上传任务模型
    /// </summary>
    public class UploadTask
    {
        /// <summary>
        /// 任务ID
        /// </summary>
        public string TaskId { get; set; }

        /// <summary>
        /// 文件路径
        /// </summary>
        public string FilePath { get; set; }

        /// <summary>
        /// 文件名称
        /// </summary>
        public string FileName { get; set; }

        /// <summary>
        /// 文件大小（字节）
        /// </summary>
        public long FileSize { get; set; }

        /// <summary>
        /// 文件MD5值
        /// </summary>
        public string Md5 { get; set; }

        /// <summary>
        /// 任务状态
        /// </summary>
        public UploadTaskStatus Status { get; set; }

        /// <summary>
        /// 重试次数
        /// </summary>
        public int RetryCount { get; set; }

        /// <summary>
        /// 创建时间
        /// </summary>
        public DateTime CreateTime { get; set; }

        /// <summary>
        /// 开始上传时间
        /// </summary>
        public DateTime? StartTime { get; set; }

        /// <summary>
        /// 完成时间
        /// </summary>
        public DateTime? FinishTime { get; set; }

        /// <summary>
        /// 错误消息
        /// </summary>
        public string ErrorMessage { get; set; }

        /// <summary>
        /// 人像记录ID（上传成功后返回）
        /// </summary>
        public int? PortraitId { get; set; }

        /// <summary>
        /// 是否为重复文件
        /// </summary>
        public bool IsDuplicate { get; set; }

        /// <summary>
        /// 获取格式化的文件大小
        /// </summary>
        public string GetFormattedFileSize()
        {
            if (FileSize < 1024)
                return $"{FileSize} B";
            else if (FileSize < 1024 * 1024)
                return $"{FileSize / 1024.0:F2} KB";
            else
                return $"{FileSize / (1024.0 * 1024.0):F2} MB";
        }

        /// <summary>
        /// 获取状态描述
        /// </summary>
        public string GetStatusDescription()
        {
            var field = Status.GetType().GetField(Status.ToString());
            var attr = (DescriptionAttribute)Attribute.GetCustomAttribute(field, typeof(DescriptionAttribute));
            return attr?.Description ?? Status.ToString();
        }
    }

    /// <summary>
    /// 上传队列状态
    /// </summary>
    public class UploadQueueStatus
    {
        /// <summary>
        /// 待上传数量
        /// </summary>
        public int PendingCount { get; set; }

        /// <summary>
        /// 上传中数量
        /// </summary>
        public int UploadingCount { get; set; }

        /// <summary>
        /// 成功数量
        /// </summary>
        public int SuccessCount { get; set; }

        /// <summary>
        /// 失败数量
        /// </summary>
        public int FailedCount { get; set; }

        /// <summary>
        /// 总数量
        /// </summary>
        public int TotalCount { get; set; }
    }

    /// <summary>
    /// 文件上传响应模型
    /// </summary>
    public class FileUploadResponse
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
        public FileUploadData Data { get; set; }
    }

    /// <summary>
    /// 文件上传响应数据
    /// </summary>
    public class FileUploadData
    {
        /// <summary>
        /// 人像记录ID
        /// </summary>
        public int PortraitId { get; set; }

        /// <summary>
        /// 是否为重复文件
        /// </summary>
        public bool IsDuplicate { get; set; }
    }
}
