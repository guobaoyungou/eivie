using System;
using System.IO;
using System.Security.Cryptography;
using System.Text;

namespace AiTravelClient.Utils
{
    /// <summary>
    /// MD5计算工具类
    /// </summary>
    public static class Md5Helper
    {
        /// <summary>
        /// 计算文件的MD5值
        /// </summary>
        public static string ComputeFileMd5(string filePath)
        {
            try
            {
                if (!File.Exists(filePath))
                    return "";

                using (var md5 = MD5.Create())
                {
                    using (var stream = File.OpenRead(filePath))
                    {
                        byte[] hash = md5.ComputeHash(stream);
                        return BitConverter.ToString(hash).Replace("-", "").ToLower();
                    }
                }
            }
            catch
            {
                return "";
            }
        }

        /// <summary>
        /// 计算字节数组的MD5值
        /// </summary>
        public static string ComputeBytesMd5(byte[] bytes)
        {
            try
            {
                using (var md5 = MD5.Create())
                {
                    byte[] hash = md5.ComputeHash(bytes);
                    return BitConverter.ToString(hash).Replace("-", "").ToLower();
                }
            }
            catch
            {
                return "";
            }
        }

        /// <summary>
        /// 计算字符串的MD5值
        /// </summary>
        public static string ComputeStringMd5(string input)
        {
            try
            {
                using (var md5 = MD5.Create())
                {
                    byte[] inputBytes = Encoding.UTF8.GetBytes(input);
                    byte[] hash = md5.ComputeHash(inputBytes);
                    return BitConverter.ToString(hash).Replace("-", "").ToLower();
                }
            }
            catch
            {
                return "";
            }
        }

        /// <summary>
        /// 验证文件MD5值
        /// </summary>
        public static bool VerifyFileMd5(string filePath, string expectedMd5)
        {
            try
            {
                string actualMd5 = ComputeFileMd5(filePath);
                return actualMd5.Equals(expectedMd5, StringComparison.OrdinalIgnoreCase);
            }
            catch
            {
                return false;
            }
        }
    }
}
