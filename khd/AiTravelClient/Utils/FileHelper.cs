using System;
using System.IO;
using System.Linq;

namespace AiTravelClient.Utils
{
    /// <summary>
    /// 文件操作辅助类
    /// </summary>
    public static class FileHelper
    {
        /// <summary>
        /// 检查文件是否存在
        /// </summary>
        public static bool FileExists(string filePath)
        {
            try
            {
                return File.Exists(filePath);
            }
            catch
            {
                return false;
            }
        }

        /// <summary>
        /// 检查目录是否存在
        /// </summary>
        public static bool DirectoryExists(string dirPath)
        {
            try
            {
                return Directory.Exists(dirPath);
            }
            catch
            {
                return false;
            }
        }

        /// <summary>
        /// 获取文件大小（字节）
        /// </summary>
        public static long GetFileSize(string filePath)
        {
            try
            {
                if (!File.Exists(filePath))
                    return 0;

                FileInfo fi = new FileInfo(filePath);
                return fi.Length;
            }
            catch
            {
                return 0;
            }
        }

        /// <summary>
        /// 检查文件是否稳定（大小在指定时间内未变化）
        /// </summary>
        /// <param name="filePath">文件路径</param>
        /// <param name="stableSeconds">稳定时间（秒）</param>
        public static bool IsFileStable(string filePath, int stableSeconds = 2)
        {
            try
            {
                if (!File.Exists(filePath))
                    return false;

                long size1 = GetFileSize(filePath);
                System.Threading.Thread.Sleep(stableSeconds * 1000);
                long size2 = GetFileSize(filePath);

                return size1 == size2 && size1 > 0;
            }
            catch
            {
                return false;
            }
        }

        /// <summary>
        /// 检查文件扩展名是否允许
        /// </summary>
        public static bool IsAllowedExtension(string filePath, string[] allowedExtensions)
        {
            try
            {
                string ext = Path.GetExtension(filePath).ToLower();
                return allowedExtensions.Any(x => x.ToLower() == ext);
            }
            catch
            {
                return false;
            }
        }

        /// <summary>
        /// 检查文件大小是否在允许范围内
        /// </summary>
        /// <param name="filePath">文件路径</param>
        /// <param name="minSizeKB">最小大小（KB）</param>
        /// <param name="maxSizeMB">最大大小（MB）</param>
        public static bool IsFileSizeValid(string filePath, int minSizeKB, int maxSizeMB)
        {
            try
            {
                long size = GetFileSize(filePath);
                long minSize = minSizeKB * 1024L;
                long maxSize = maxSizeMB * 1024L * 1024L;

                return size >= minSize && size <= maxSize;
            }
            catch
            {
                return false;
            }
        }

        /// <summary>
        /// 检查文件名是否为临时文件
        /// </summary>
        public static bool IsTempFile(string filePath)
        {
            try
            {
                string fileName = Path.GetFileName(filePath);
                return fileName.StartsWith(".") || fileName.StartsWith("~");
            }
            catch
            {
                return true;
            }
        }

        /// <summary>
        /// 格式化文件大小
        /// </summary>
        public static string FormatFileSize(long bytes)
        {
            if (bytes < 1024)
                return $"{bytes} B";
            else if (bytes < 1024 * 1024)
                return $"{bytes / 1024.0:F2} KB";
            else if (bytes < 1024 * 1024 * 1024)
                return $"{bytes / (1024.0 * 1024.0):F2} MB";
            else
                return $"{bytes / (1024.0 * 1024.0 * 1024.0):F2} GB";
        }

        /// <summary>
        /// 确保目录存在，不存在则创建
        /// </summary>
        public static bool EnsureDirectory(string dirPath)
        {
            try
            {
                if (!Directory.Exists(dirPath))
                {
                    Directory.CreateDirectory(dirPath);
                }
                return true;
            }
            catch
            {
                return false;
            }
        }

        /// <summary>
        /// 读取文件字节数组
        /// </summary>
        public static byte[] ReadFileBytes(string filePath)
        {
            try
            {
                return File.ReadAllBytes(filePath);
            }
            catch
            {
                return null;
            }
        }

        /// <summary>
        /// 获取文件名（不含扩展名）
        /// </summary>
        public static string GetFileNameWithoutExtension(string filePath)
        {
            try
            {
                return Path.GetFileNameWithoutExtension(filePath);
            }
            catch
            {
                return "";
            }
        }

        /// <summary>
        /// 获取文件扩展名
        /// </summary>
        public static string GetExtension(string filePath)
        {
            try
            {
                return Path.GetExtension(filePath);
            }
            catch
            {
                return "";
            }
        }
    }
}
