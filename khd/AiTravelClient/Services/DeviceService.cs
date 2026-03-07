using System;
using System.Threading.Tasks;
using AiTravelClient.Models;
using AiTravelClient.Utils;

namespace AiTravelClient.Services
{
    /// <summary>
    /// 设备注册状态枚举
    /// </summary>
    public enum DeviceRegisterStatus
    {
        /// <summary>
        /// 未注册
        /// </summary>
        NotRegistered = 0,

        /// <summary>
        /// 已注册
        /// </summary>
        Registered = 1,

        /// <summary>
        /// 注册中
        /// </summary>
        Registering = 2
    }

    /// <summary>
    /// 设备服务
    /// 处理设备注册、Token管理、设备信息收集等功能
    /// </summary>
    public class DeviceService
    {
        private readonly ConfigService _configService;
        private readonly ApiClient _apiClient;
        private readonly LogService _logService;
        private DeviceRegisterStatus _registerStatus;

        public DeviceService(ConfigService configService, ApiClient apiClient, LogService logService)
        {
            _configService = configService;
            _apiClient = apiClient;
            _logService = logService;
            _registerStatus = DeviceRegisterStatus.NotRegistered;
        }

        /// <summary>
        /// 获取当前注册状态
        /// </summary>
        public DeviceRegisterStatus GetRegisterStatus()
        {
            if (_configService.IsRegistered())
            {
                return DeviceRegisterStatus.Registered;
            }
            return _registerStatus;
        }

        /// <summary>
        /// 获取设备唯一标识（MAC地址）
        /// </summary>
        public string GetDeviceId()
        {
            try
            {
                string deviceId = SystemInfoHelper.GetMacAddress();
                if (string.IsNullOrEmpty(deviceId))
                {
                    _logService.Warn("DeviceService", "无法获取MAC地址，使用计算机名称作为设备ID");
                    deviceId = SystemInfoHelper.GetComputerName();
                }
                return deviceId;
            }
            catch (Exception ex)
            {
                _logService.Error("DeviceService", "获取设备ID失败", ex);
                return "UNKNOWN";
            }
        }

        /// <summary>
        /// 收集系统信息
        /// </summary>
        public DeviceInfo CollectSystemInfo()
        {
            try
            {
                var deviceInfo = new DeviceInfo
                {
                    DeviceId = GetDeviceId(),
                    PcName = SystemInfoHelper.GetComputerName(),
                    OsVersion = SystemInfoHelper.GetOsVersion(),
                    CpuInfo = SystemInfoHelper.GetCpuInfo(),
                    MemorySize = SystemInfoHelper.GetMemorySize(),
                    DiskInfo = SystemInfoHelper.GetDiskInfo(),
                    ClientVersion = "1.0.0",
                    CreateTime = DateTime.Now
                };

                _logService.Info("DeviceService", $"系统信息收集完成: {deviceInfo.PcName}");
                return deviceInfo;
            }
            catch (Exception ex)
            {
                _logService.Error("DeviceService", "收集系统信息失败", ex);
                return null;
            }
        }

        /// <summary>
        /// 注册设备
        /// </summary>
        /// <param name="deviceCode">设备编码（从后台获取）</param>
        /// <param name="deviceName">设备名称</param>
        /// <param name="bid">商家ID</param>
        /// <param name="mdid">门店ID</param>
        public async Task<ApiResponse<DeviceRegisterResponse>> RegisterDeviceAsync(
            string deviceCode, string deviceName, int bid, int mdid = 0)
        {
            try
            {
                _registerStatus = DeviceRegisterStatus.Registering;
                _logService.Info("DeviceService", $"开始注册设备: {deviceName}");

                // 收集系统信息
                var systemInfo = CollectSystemInfo();
                if (systemInfo == null)
                {
                    _registerStatus = DeviceRegisterStatus.NotRegistered;
                    return new ApiResponse<DeviceRegisterResponse>
                    {
                        Code = -1,
                        Msg = "收集系统信息失败"
                    };
                }

                // 构造注册请求
                var request = new DeviceRegisterRequest
                {
                    DeviceId = systemInfo.DeviceId,
                    DeviceName = deviceName,
                    DeviceCode = deviceCode,
                    Bid = bid,
                    Mdid = mdid,
                    OsVersion = systemInfo.OsVersion,
                    ClientVersion = systemInfo.ClientVersion,
                    PcName = systemInfo.PcName,
                    CpuInfo = systemInfo.CpuInfo,
                    MemorySize = systemInfo.MemorySize,
                    DiskInfo = systemInfo.DiskInfo
                };

                // 发送注册请求
                var response = await _apiClient.RegisterDeviceAsync(request);

                if (response.IsSuccess)
                {
                    // 保存设备配置
                    var config = _configService.GetConfig();
                    config.Device.DeviceId = systemInfo.DeviceId;
                    config.Device.DeviceToken = response.Data.DeviceToken;
                    config.Device.DeviceName = deviceName;
                    config.Device.Aid = response.Data.Aid;
                    config.Device.Bid = bid;
                    config.Device.Mdid = mdid;

                    _configService.SaveConfig(config);

                    // 设置API客户端Token
                    _apiClient.SetDeviceToken(response.Data.DeviceToken);

                    _registerStatus = DeviceRegisterStatus.Registered;
                    _logService.Info("DeviceService", $"设备注册成功: {deviceName}");
                }
                else
                {
                    _registerStatus = DeviceRegisterStatus.NotRegistered;
                    _logService.Error("DeviceService", $"设备注册失败: {response.Msg}");
                }

                return response;
            }
            catch (Exception ex)
            {
                _registerStatus = DeviceRegisterStatus.NotRegistered;
                _logService.Error("DeviceService", "设备注册异常", ex);
                return new ApiResponse<DeviceRegisterResponse>
                {
                    Code = -1,
                    Msg = $"注册异常: {ex.Message}"
                };
            }
        }

        /// <summary>
        /// 验证Token有效性
        /// </summary>
        public async Task<bool> VerifyTokenAsync()
        {
            try
            {
                var config = _configService.GetConfig();
                if (string.IsNullOrEmpty(config.Device.DeviceToken))
                {
                    return false;
                }

                _apiClient.SetDeviceToken(config.Device.DeviceToken);
                var response = await _apiClient.GetDeviceInfoAsync();

                if (response.IsSuccess)
                {
                    _logService.Info("DeviceService", "Token验证成功");
                    return true;
                }
                else
                {
                    _logService.Warn("DeviceService", $"Token验证失败: {response.Msg}");
                    return false;
                }
            }
            catch (Exception ex)
            {
                _logService.Error("DeviceService", "Token验证异常", ex);
                return false;
            }
        }

        /// <summary>
        /// 获取设备详细信息
        /// </summary>
        public async Task<DeviceInfo> GetDeviceInfoAsync()
        {
            try
            {
                var response = await _apiClient.GetDeviceInfoAsync();
                if (response.IsSuccess)
                {
                    return response.Data;
                }
                return null;
            }
            catch (Exception ex)
            {
                _logService.Error("DeviceService", "获取设备信息失败", ex);
                return null;
            }
        }

        /// <summary>
        /// 初始化设备服务
        /// </summary>
        public async Task<bool> InitializeAsync()
        {
            try
            {
                _logService.Info("DeviceService", "初始化设备服务");

                var config = _configService.GetConfig();
                if (_configService.IsRegistered())
                {
                    // 设置Token
                    _apiClient.SetDeviceToken(config.Device.DeviceToken);

                    // 验证Token
                    bool isValid = await VerifyTokenAsync();
                    if (isValid)
                    {
                        _registerStatus = DeviceRegisterStatus.Registered;
                        _logService.Info("DeviceService", "设备服务初始化成功");
                        return true;
                    }
                    else
                    {
                        _logService.Warn("DeviceService", "Token已失效，需要重新注册");
                        _registerStatus = DeviceRegisterStatus.NotRegistered;
                        return false;
                    }
                }
                else
                {
                    _logService.Info("DeviceService", "设备未注册");
                    _registerStatus = DeviceRegisterStatus.NotRegistered;
                    return false;
                }
            }
            catch (Exception ex)
            {
                _logService.Error("DeviceService", "初始化设备服务失败", ex);
                return false;
            }
        }
    }
}
