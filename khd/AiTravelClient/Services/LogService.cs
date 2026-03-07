using System;
using System.IO;
using System.Text;

namespace AiTravelClient.Services
{
    /// <summary>
    /// 日志级别
    /// </summary>
    public enum LogLevel
    {
        DEBUG = 0,
        INFO = 1,
        WARN = 2,
        ERROR = 3
    }

    /// <summary>
    /// 日志服务
    /// </summary>
    public class LogService
    {
        private static readonly string LogDirectory = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "logs");
        private static readonly object _lockObj = new object();
        private static LogLevel _minLevel = LogLevel.INFO;

        /// <summary>
        /// 日志记录事件
        /// </summary>
        public event Action<string, LogLevel, string> OnLogReceived;

        static LogService()
        {
            if (!Directory.Exists(LogDirectory))
            {
                Directory.CreateDirectory(LogDirectory);
            }
        }

        /// <summary>
        /// 设置最小日志级别
        /// </summary>
        public void SetMinLevel(LogLevel level)
        {
            _minLevel = level;
        }

        /// <summary>
        /// 记录DEBUG日志
        /// </summary>
        public void Debug(string module, string message)
        {
            Log(LogLevel.DEBUG, module, message);
        }

        /// <summary>
        /// 记录INFO日志
        /// </summary>
        public void Info(string module, string message)
        {
            Log(LogLevel.INFO, module, message);
        }

        /// <summary>
        /// 记录WARN日志
        /// </summary>
        public void Warn(string module, string message)
        {
            Log(LogLevel.WARN, module, message);
        }

        /// <summary>
        /// 记录ERROR日志
        /// </summary>
        public void Error(string module, string message)
        {
            Log(LogLevel.ERROR, module, message);
        }

        /// <summary>
        /// 记录ERROR日志（带异常）
        /// </summary>
        public void Error(string module, string message, Exception ex)
        {
            string fullMessage = $"{message}\n异常信息: {ex.Message}\n堆栈跟踪: {ex.StackTrace}";
            Log(LogLevel.ERROR, module, fullMessage);
        }

        /// <summary>
        /// 记录日志
        /// </summary>
        private void Log(LogLevel level, string module, string message)
        {
            if (level < _minLevel)
                return;

            try
            {
                string timestamp = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss");
                string logMessage = $"[{timestamp}] [{level}] [{module}] {message}";

                // 触发事件（用于界面显示）
                OnLogReceived?.Invoke(timestamp, level, $"[{module}] {message}");

                // 写入文件
                WriteToFile(level, logMessage);
            }
            catch
            {
                // 忽略日志记录失败
            }
        }

        /// <summary>
        /// 写入日志文件
        /// </summary>
        private void WriteToFile(LogLevel level, string logMessage)
        {
            lock (_lockObj)
            {
                try
                {
                    string date = DateTime.Now.ToString("yyyyMMdd");
                    string fileName = level == LogLevel.ERROR 
                        ? $"error_{date}.log" 
                        : $"runtime_{date}.log";

                    string filePath = Path.Combine(LogDirectory, fileName);

                    File.AppendAllText(filePath, logMessage + Environment.NewLine, Encoding.UTF8);

                    // 检查文件大小,超过50MB则切分
                    FileInfo fi = new FileInfo(filePath);
                    if (fi.Length > 50 * 1024 * 1024)
                    {
                        SplitLogFile(filePath);
                    }
                }
                catch
                {
                    // 忽略写入失败
                }
            }
        }

        /// <summary>
        /// 切分日志文件
        /// </summary>
        private void SplitLogFile(string filePath)
        {
            try
            {
                string directory = Path.GetDirectoryName(filePath);
                string fileNameWithoutExt = Path.GetFileNameWithoutExtension(filePath);
                string extension = Path.GetExtension(filePath);
                string timestamp = DateTime.Now.ToString("HHmmss");
                string newFileName = $"{fileNameWithoutExt}_{timestamp}{extension}";
                string newFilePath = Path.Combine(directory, newFileName);

                File.Move(filePath, newFilePath);
            }
            catch
            {
                // 忽略切分失败
            }
        }

        /// <summary>
        /// 清理过期日志（保留最近N天）
        /// </summary>
        public void CleanOldLogs(int keepDays = 30)
        {
            try
            {
                if (!Directory.Exists(LogDirectory))
                    return;

                DateTime cutoffDate = DateTime.Now.AddDays(-keepDays);
                var files = Directory.GetFiles(LogDirectory, "*.log");

                foreach (var file in files)
                {
                    FileInfo fi = new FileInfo(file);
                    if (fi.CreationTime < cutoffDate)
                    {
                        File.Delete(file);
                    }
                }
            }
            catch
            {
                // 忽略清理失败
            }
        }
    }
}
