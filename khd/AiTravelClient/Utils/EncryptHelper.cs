using System;
using System.IO;
using System.Security.Cryptography;
using System.Text;

namespace AiTravelClient.Utils
{
    /// <summary>
    /// 加密解密工具类
    /// </summary>
    public static class EncryptHelper
    {
        // AES密钥（实际使用时应该从安全的地方获取）
        private static readonly string AesKey = "AiTravelClient2024@SecretKey!";
        private static readonly string AesIV = "1234567890ABCDEF";

        /// <summary>
        /// AES加密
        /// </summary>
        public static string AesEncrypt(string plainText)
        {
            try
            {
                if (string.IsNullOrEmpty(plainText))
                    return "";

                byte[] key = Encoding.UTF8.GetBytes(AesKey.Substring(0, 32));
                byte[] iv = Encoding.UTF8.GetBytes(AesIV.Substring(0, 16));

                using (Aes aes = Aes.Create())
                {
                    aes.Key = key;
                    aes.IV = iv;
                    aes.Mode = CipherMode.CBC;
                    aes.Padding = PaddingMode.PKCS7;

                    ICryptoTransform encryptor = aes.CreateEncryptor(aes.Key, aes.IV);

                    using (MemoryStream ms = new MemoryStream())
                    {
                        using (CryptoStream cs = new CryptoStream(ms, encryptor, CryptoStreamMode.Write))
                        {
                            byte[] plainBytes = Encoding.UTF8.GetBytes(plainText);
                            cs.Write(plainBytes, 0, plainBytes.Length);
                            cs.FlushFinalBlock();
                            byte[] encrypted = ms.ToArray();
                            return Convert.ToBase64String(encrypted);
                        }
                    }
                }
            }
            catch
            {
                return "";
            }
        }

        /// <summary>
        /// AES解密
        /// </summary>
        public static string AesDecrypt(string cipherText)
        {
            try
            {
                if (string.IsNullOrEmpty(cipherText))
                    return "";

                byte[] key = Encoding.UTF8.GetBytes(AesKey.Substring(0, 32));
                byte[] iv = Encoding.UTF8.GetBytes(AesIV.Substring(0, 16));
                byte[] cipherBytes = Convert.FromBase64String(cipherText);

                using (Aes aes = Aes.Create())
                {
                    aes.Key = key;
                    aes.IV = iv;
                    aes.Mode = CipherMode.CBC;
                    aes.Padding = PaddingMode.PKCS7;

                    ICryptoTransform decryptor = aes.CreateDecryptor(aes.Key, aes.IV);

                    using (MemoryStream ms = new MemoryStream(cipherBytes))
                    {
                        using (CryptoStream cs = new CryptoStream(ms, decryptor, CryptoStreamMode.Read))
                        {
                            using (StreamReader sr = new StreamReader(cs))
                            {
                                return sr.ReadToEnd();
                            }
                        }
                    }
                }
            }
            catch
            {
                return "";
            }
        }

        /// <summary>
        /// Base64编码
        /// </summary>
        public static string Base64Encode(string plainText)
        {
            try
            {
                byte[] bytes = Encoding.UTF8.GetBytes(plainText);
                return Convert.ToBase64String(bytes);
            }
            catch
            {
                return "";
            }
        }

        /// <summary>
        /// Base64解码
        /// </summary>
        public static string Base64Decode(string base64Text)
        {
            try
            {
                byte[] bytes = Convert.FromBase64String(base64Text);
                return Encoding.UTF8.GetString(bytes);
            }
            catch
            {
                return "";
            }
        }
    }
}
