using System;
using System.Management;
using System.Net;
using System.Net.NetworkInformation;
using System.Linq;

namespace AiTravelClient.Utils
{
    /// <summary>
    /// 系统信息获取工具类
    /// </summary>
    public static class SystemInfoHelper
    {
        /// <summary>
        /// 获取MAC地址（作为设备唯一标识）
        /// </summary>
        public static string GetMacAddress()
        {
            try
            {
                var nics = NetworkInterface.GetAllNetworkInterfaces()
                    .Where(n => n.NetworkInterfaceType != NetworkInterfaceType.Loopback 
                             && n.OperationalStatus == OperationalStatus.Up)
                    .OrderBy(n => n.Speed)
                    .ToList();

                if (nics.Count > 0)
                {
                    var mac = nics[0].GetPhysicalAddress();
                    return string.Join(":", mac.GetAddressBytes().Select(b => b.ToString("X2")));
                }

                return "";
            }
            catch
            {
                return "";
            }
        }

        /// <summary>
        /// 获取本机IP地址
        /// </summary>
        public static string GetLocalIpAddress()
        {
            try
            {
                var host = Dns.GetHostEntry(Dns.GetHostName());
                foreach (var ip in host.AddressList)
                {
                    if (ip.AddressFamily == System.Net.Sockets.AddressFamily.InterNetwork)
                    {
                        return ip.ToString();
                    }
                }
                return "127.0.0.1";
            }
            catch
            {
                return "127.0.0.1";
            }
        }

        /// <summary>
        /// 获取计算机名称
        /// </summary>
        public static string GetComputerName()
        {
            try
            {
                return Environment.MachineName;
            }
            catch
            {
                return "Unknown";
            }
        }

        /// <summary>
        /// 获取操作系统版本
        /// </summary>
        public static string GetOsVersion()
        {
            try
            {
                return $"Windows {Environment.OSVersion.Version.Major}.{Environment.OSVersion.Version.Minor}";
            }
            catch
            {
                return "Unknown";
            }
        }

        /// <summary>
        /// 获取CPU信息
        /// </summary>
        public static string GetCpuInfo()
        {
            try
            {
                using (ManagementObjectSearcher searcher = new ManagementObjectSearcher("SELECT Name FROM Win32_Processor"))
                {
                    foreach (ManagementObject obj in searcher.Get())
                    {
                        return obj["Name"]?.ToString() ?? "Unknown";
                    }
                }
                return "Unknown";
            }
            catch
            {
                return "Unknown";
            }
        }

        /// <summary>
        /// 获取内存大小
        /// </summary>
        public static string GetMemorySize()
        {
            try
            {
                using (ManagementObjectSearcher searcher = new ManagementObjectSearcher("SELECT TotalPhysicalMemory FROM Win32_ComputerSystem"))
                {
                    foreach (ManagementObject obj in searcher.Get())
                    {
                        long totalMemory = Convert.ToInt64(obj["TotalPhysicalMemory"]);
                        double gb = totalMemory / (1024.0 * 1024.0 * 1024.0);
                        return $"{gb:F2} GB";
                    }
                }
                return "Unknown";
            }
            catch
            {
                return "Unknown";
            }
        }

        /// <summary>
        /// 获取磁盘信息
        /// </summary>
        public static string GetDiskInfo()
        {
            try
            {
                var drives = System.IO.DriveInfo.GetDrives()
                    .Where(d => d.IsReady && d.DriveType == System.IO.DriveType.Fixed)
                    .Select(d => $"{d.Name}: {d.TotalSize / (1024.0 * 1024.0 * 1024.0):F2} GB")
                    .ToArray();

                return string.Join(", ", drives);
            }
            catch
            {
                return "Unknown";
            }
        }

        /// <summary>
        /// 获取系统运行时长（毫秒）
        /// </summary>
        public static long GetSystemUptime()
        {
            try
            {
                return Environment.TickCount;
            }
            catch
            {
                return 0;
            }
        }

        /// <summary>
        /// 格式化运行时长
        /// </summary>
        public static string FormatUptime(long milliseconds)
        {
            try
            {
                TimeSpan ts = TimeSpan.FromMilliseconds(milliseconds);
                if (ts.TotalDays >= 1)
                    return $"{(int)ts.TotalDays}天{ts.Hours}小时{ts.Minutes}分钟";
                else if (ts.TotalHours >= 1)
                    return $"{(int)ts.TotalHours}小时{ts.Minutes}分钟";
                else
                    return $"{ts.Minutes}分钟{ts.Seconds}秒";
            }
            catch
            {
                return "未知";
            }
        }
    }
}
