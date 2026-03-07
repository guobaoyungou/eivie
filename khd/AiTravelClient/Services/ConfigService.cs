using System;
using System.IO;
using AiTravelClient.Models;
using AiTravelClient.Utils;
using Newtonsoft.Json;

namespace AiTravelClient.Services
{
    /// <summary>
    /// 配置管理服务
    /// </summary>
    public class ConfigService
    {
        private static readonly string ConfigFilePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "config.json");
        private ConfigModel _config;
        private static readonly object _lockObj = new object();

        public ConfigService()
        {
            LoadConfig();
        }

        /// <summary>
        /// 加载配置
        /// </summary>
        public ConfigModel LoadConfig()
        {
            lock (_lockObj)
            {
                try
                {
                    if (File.Exists(ConfigFilePath))
                    {
                        string json = File.ReadAllText(ConfigFilePath);
                        _config = JsonConvert.DeserializeObject<ConfigModel>(json);

                        // 解密敏感信息
                        if (!string.IsNullOrEmpty(_config.Device.DeviceToken))
                        {
                            _config.Device.DeviceToken = EncryptHelper.AesDecrypt(_config.Device.DeviceToken);
                        }
                    }
                    else
                    {
                        _config = new ConfigModel();
                        SaveConfig(_config);
                    }

                    return _config;
                }
                catch
                {
                    _config = new ConfigModel();
                    return _config;
                }
            }
        }

        /// <summary>
        /// 保存配置
        /// </summary>
        public bool SaveConfig(ConfigModel config)
        {
            lock (_lockObj)
            {
                try
                {
                    // 克隆配置对象以避免修改原对象
                    var configToSave = JsonConvert.DeserializeObject<ConfigModel>(JsonConvert.SerializeObject(config));

                    // 加密敏感信息
                    if (!string.IsNullOrEmpty(configToSave.Device.DeviceToken))
                    {
                        configToSave.Device.DeviceToken = EncryptHelper.AesEncrypt(configToSave.Device.DeviceToken);
                    }

                    string json = JsonConvert.SerializeObject(configToSave, Formatting.Indented);
                    File.WriteAllText(ConfigFilePath, json);

                    _config = config;
                    return true;
                }
                catch
                {
                    return false;
                }
            }
        }

        /// <summary>
        /// 获取当前配置
        /// </summary>
        public ConfigModel GetConfig()
        {
            return _config ?? LoadConfig();
        }

        /// <summary>
        /// 更新服务器配置
        /// </summary>
        public bool UpdateServerConfig(ServerConfig serverConfig)
        {
            var config = GetConfig();
            config.Server = serverConfig;
            return SaveConfig(config);
        }

        /// <summary>
        /// 更新设备配置
        /// </summary>
        public bool UpdateDeviceConfig(DeviceConfig deviceConfig)
        {
            var config = GetConfig();
            config.Device = deviceConfig;
            return SaveConfig(config);
        }

        /// <summary>
        /// 更新监控配置
        /// </summary>
        public bool UpdateWatcherConfig(WatcherConfig watcherConfig)
        {
            var config = GetConfig();
            config.Watcher = watcherConfig;
            return SaveConfig(config);
        }

        /// <summary>
        /// 更新上传配置
        /// </summary>
        public bool UpdateUploadConfig(UploadConfig uploadConfig)
        {
            var config = GetConfig();
            config.Upload = uploadConfig;
            return SaveConfig(config);
        }

        /// <summary>
        /// 检查是否已注册
        /// </summary>
        public bool IsRegistered()
        {
            var config = GetConfig();
            return !string.IsNullOrEmpty(config.Device.DeviceToken) 
                && !string.IsNullOrEmpty(config.Device.DeviceId);
        }
    }
}
