using System;
using System.Collections.ObjectModel;
using System.Linq;
using System.Windows;
using System.Windows.Input;
using AiTravelClient.Models;
using AiTravelClient.Services;
using Microsoft.Win32;

namespace AiTravelClient.ViewModels
{
    /// <summary>
    /// 设置页视图模型
    /// </summary>
    public class SettingsViewModel : BaseViewModel
    {
        private readonly ConfigService _configService;
        private readonly DeviceService _deviceService;
        private readonly LogService _logService;

        /// <summary>
        /// 注册完成事件
        /// </summary>
        public event EventHandler<bool> RegistrationCompleted;

        #region Server Config Properties

        private string _apiBaseUrl;
        /// <summary>
        /// API服务器地址
        /// </summary>
        public string ApiBaseUrl
        {
            get => _apiBaseUrl;
            set => SetProperty(ref _apiBaseUrl, value);
        }

        private int _timeout = 120;
        /// <summary>
        /// 请求超时时间（秒）
        /// </summary>
        public int Timeout
        {
            get => _timeout;
            set => SetProperty(ref _timeout, value);
        }

        private int _retryTimes = 3;
        /// <summary>
        /// 重试次数
        /// </summary>
        public int RetryTimes
        {
            get => _retryTimes;
            set => SetProperty(ref _retryTimes, value);
        }

        #endregion

        #region Device Config Properties

        private string _deviceId;
        /// <summary>
        /// 设备ID（只读）
        /// </summary>
        public string DeviceId
        {
            get => _deviceId;
            set => SetProperty(ref _deviceId, value);
        }

        private string _deviceName;
        /// <summary>
        /// 设备名称
        /// </summary>
        public string DeviceName
        {
            get => _deviceName;
            set => SetProperty(ref _deviceName, value);
        }

        private bool _isRegistered;
        /// <summary>
        /// 是否已注册
        /// </summary>
        public bool IsRegistered
        {
            get => _isRegistered;
            set => SetProperty(ref _isRegistered, value);
        }

        private int _aid;
        /// <summary>
        /// 应用ID
        /// </summary>
        public int Aid
        {
            get => _aid;
            set => SetProperty(ref _aid, value);
        }

        private int _bid;
        /// <summary>
        /// 商家ID
        /// </summary>
        public int Bid
        {
            get => _bid;
            set => SetProperty(ref _bid, value);
        }

        private int _mdid;
        /// <summary>
        /// 门店ID
        /// </summary>
        public int Mdid
        {
            get => _mdid;
            set => SetProperty(ref _mdid, value);
        }

        private string _deviceCode;
        /// <summary>
        /// 设备编码（从后台获取）
        /// </summary>
        public string DeviceCode
        {
            get => _deviceCode;
            set => SetProperty(ref _deviceCode, value);
        }

        #endregion

        #region Watcher Config Properties

        private ObservableCollection<string> _watchPaths;
        /// <summary>
        /// 监控路径列表
        /// </summary>
        public ObservableCollection<string> WatchPaths
        {
            get => _watchPaths;
            set => SetProperty(ref _watchPaths, value);
        }

        private int _scanInterval = 10;
        /// <summary>
        /// 轮询间隔（秒）
        /// </summary>
        public int ScanInterval
        {
            get => _scanInterval;
            set => SetProperty(ref _scanInterval, value);
        }

        private int _fileStableTime = 2;
        /// <summary>
        /// 文件稳定等待时间（秒）
        /// </summary>
        public int FileStableTime
        {
            get => _fileStableTime;
            set => SetProperty(ref _fileStableTime, value);
        }

        private string _allowedExtensions = ".jpg,.jpeg,.png";
        /// <summary>
        /// 允许的扩展名（逗号分隔）
        /// </summary>
        public string AllowedExtensions
        {
            get => _allowedExtensions;
            set => SetProperty(ref _allowedExtensions, value);
        }

        private int _minFileSize = 10;
        /// <summary>
        /// 最小文件大小（KB）
        /// </summary>
        public int MinFileSize
        {
            get => _minFileSize;
            set => SetProperty(ref _minFileSize, value);
        }

        private int _maxFileSize = 10;
        /// <summary>
        /// 最大文件大小（MB）
        /// </summary>
        public int MaxFileSize
        {
            get => _maxFileSize;
            set => SetProperty(ref _maxFileSize, value);
        }

        #endregion

        #region Upload Config Properties

        private int _concurrentUploads = 3;
        /// <summary>
        /// 并发上传数
        /// </summary>
        public int ConcurrentUploads
        {
            get => _concurrentUploads;
            set => SetProperty(ref _concurrentUploads, value);
        }

        private int _maxQueueSize = 1000;
        /// <summary>
        /// 最大队列长度
        /// </summary>
        public int MaxQueueSize
        {
            get => _maxQueueSize;
            set => SetProperty(ref _maxQueueSize, value);
        }

        private bool _autoUpload = true;
        /// <summary>
        /// 自动上传
        /// </summary>
        public bool AutoUpload
        {
            get => _autoUpload;
            set => SetProperty(ref _autoUpload, value);
        }

        private int _maxRetry = 5;
        /// <summary>
        /// 最大重试次数
        /// </summary>
        public int MaxRetry
        {
            get => _maxRetry;
            set => SetProperty(ref _maxRetry, value);
        }

        #endregion

        #region Heartbeat Config Properties

        private int _heartbeatInterval = 60;
        /// <summary>
        /// 心跳间隔（秒）
        /// </summary>
        public int HeartbeatInterval
        {
            get => _heartbeatInterval;
            set => SetProperty(ref _heartbeatInterval, value);
        }

        private int _heartbeatTimeout = 10;
        /// <summary>
        /// 心跳超时（秒）
        /// </summary>
        public int HeartbeatTimeout
        {
            get => _heartbeatTimeout;
            set => SetProperty(ref _heartbeatTimeout, value);
        }

        #endregion

        #region Commands

        public ICommand SaveConfigCommand { get; }
        public ICommand ResetConfigCommand { get; }
        public ICommand TestConnectionCommand { get; }
        public ICommand RegisterDeviceCommand { get; }
        public ICommand UnregisterDeviceCommand { get; }
        public ICommand AddWatchPathCommand { get; }
        public ICommand RemoveWatchPathCommand { get; }

        #endregion

        public SettingsViewModel(
            ConfigService configService,
            DeviceService deviceService,
            LogService logService)
        {
            _configService = configService;
            _deviceService = deviceService;
            _logService = logService;

            // 初始化集合
            WatchPaths = new ObservableCollection<string>();

            // 初始化命令
            SaveConfigCommand = new AsyncRelayCommand(SaveConfigAsync, CanSaveConfig);
            ResetConfigCommand = new RelayCommand(ResetConfig);
            TestConnectionCommand = new AsyncRelayCommand(TestConnectionAsync);
            RegisterDeviceCommand = new AsyncRelayCommand(RegisterDeviceAsync, CanRegisterDevice);
            UnregisterDeviceCommand = new RelayCommand(UnregisterDevice, () => IsRegistered);
            AddWatchPathCommand = new RelayCommand(AddWatchPath);
            RemoveWatchPathCommand = new RelayCommand<string>(RemoveWatchPath);

            // 加载配置
            LoadConfig();
        }

        #region Command Handlers

        /// <summary>
        /// 保存配置
        /// </summary>
        private async System.Threading.Tasks.Task SaveConfigAsync()
        {
            await SafeExecuteAsync(async () =>
            {
                // 验证配置
                if (!ValidateConfig())
                {
                    return;
                }

                var result = MessageBox.Show(
                    "保存配置后需要重启服务才能生效，是否继续？",
                    "确认保存",
                    MessageBoxButton.YesNo,
                    MessageBoxImage.Question);

                if (result != MessageBoxResult.Yes)
                {
                    return;
                }

                // 创建配置对象
                var config = new ConfigModel
                {
                    Server = new ServerConfig
                    {
                        ApiBaseUrl = ApiBaseUrl,
                        Timeout = Timeout,
                        RetryTimes = RetryTimes
                    },
                    Device = new DeviceConfig
                    {
                        DeviceId = DeviceId,
                        DeviceName = DeviceName,
                        Aid = Aid,
                        Bid = Bid,
                        Mdid = Mdid,
                        DeviceToken = _configService.GetConfig().Device.DeviceToken
                    },
                    Watcher = new WatcherConfig
                    {
                        WatchPaths = WatchPaths.ToList(),
                        ScanInterval = ScanInterval,
                        FileStableTime = FileStableTime,
                        AllowedExtensions = AllowedExtensions.Split(',').Select(x => x.Trim()).ToList(),
                        MinFileSize = MinFileSize,
                        MaxFileSize = MaxFileSize
                    },
                    Upload = new UploadConfig
                    {
                        ConcurrentUploads = ConcurrentUploads,
                        MaxQueueSize = MaxQueueSize,
                        AutoUpload = AutoUpload,
                        MaxRetry = MaxRetry
                    },
                    Heartbeat = new HeartbeatConfig
                    {
                        Interval = HeartbeatInterval,
                        Timeout = HeartbeatTimeout
                    }
                };

                // 保存配置
                _configService.SaveConfig(config);

                _logService.Info("SettingsViewModel", "配置已保存");
                MessageBox.Show("配置已保存", "成功", MessageBoxButton.OK, MessageBoxImage.Information);

                await System.Threading.Tasks.Task.CompletedTask;
            }, "保存配置失败");
        }

        /// <summary>
        /// 重置配置
        /// </summary>
        private void ResetConfig()
        {
            var result = MessageBox.Show(
                "确定要恢复默认配置吗？当前配置将丢失",
                "确认重置",
                MessageBoxButton.YesNo,
                MessageBoxImage.Warning);

            if (result == MessageBoxResult.Yes)
            {
                var defaultConfig = new ConfigModel();
                LoadConfigFromModel(defaultConfig);
                _logService.Info("SettingsViewModel", "已恢复默认配置");
            }
        }

        /// <summary>
        /// 测试连接
        /// </summary>
        private async System.Threading.Tasks.Task TestConnectionAsync()
        {
            await SafeExecuteAsync(async () =>
            {
                _logService.Info("SettingsViewModel", "测试服务器连接");

                bool isConnected = await _deviceService.VerifyTokenAsync();

                if (isConnected)
                {
                    MessageBox.Show("连接成功", "测试结果", MessageBoxButton.OK, MessageBoxImage.Information);
                }
                else
                {
                    MessageBox.Show("连接失败，请检查服务器地址和网络连接", "测试结果", MessageBoxButton.OK, MessageBoxImage.Error);
                }
            }, "测试连接失败");
        }

        /// <summary>
        /// 注册设备
        /// </summary>
        private async System.Threading.Tasks.Task RegisterDeviceAsync()
        {
            await SafeExecuteAsync(async () =>
            {
                _logService.Info("SettingsViewModel", "开始注册设备");

                // 验证必填项
                if (string.IsNullOrWhiteSpace(DeviceName))
                {
                    SetError("请输入设备名称");
                    return;
                }

                if (string.IsNullOrWhiteSpace(DeviceCode))
                {
                    SetError("请输入设备编码");
                    return;
                }

                if (Bid <= 0)
                {
                    SetError("请输入有效的商家ID");
                    return;
                }

                // 调用注册接口
                var response = await _deviceService.RegisterDeviceAsync(
                    DeviceCode, DeviceName, Bid, Mdid);

                if (response.IsSuccess)
                {
                    IsRegistered = true;
                    Aid = response.Data.Aid;
                    DeviceId = _deviceService.GetDeviceId();

                    _logService.Info("SettingsViewModel", "设备注册成功");
                    MessageBox.Show("设备注册成功！", "成功", MessageBoxButton.OK, MessageBoxImage.Information);

                    // 触发注册完成事件
                    RegistrationCompleted?.Invoke(this, true);
                }
                else
                {
                    SetError($"注册失败: {response.Msg}");
                }
            }, "设备注册失败");
        }

        /// <summary>
        /// 注销设备
        /// </summary>
        private void UnregisterDevice()
        {
            var result = MessageBox.Show(
                "确定要注销设备吗？注销后需要重新注册",
                "确认注销",
                MessageBoxButton.YesNo,
                MessageBoxImage.Warning);

            if (result == MessageBoxResult.Yes)
            {
                var config = _configService.GetConfig();
                config.Device.DeviceToken = "";
                config.Device.DeviceName = "";
                config.Device.Aid = 0;
                _configService.SaveConfig(config);

                IsRegistered = false;
                DeviceName = "";
                Aid = 0;

                _logService.Info("SettingsViewModel", "设备已注销");
                MessageBox.Show("设备已注销", "成功", MessageBoxButton.OK, MessageBoxImage.Information);
            }
        }

        /// <summary>
        /// 添加监控路径
        /// </summary>
        private void AddWatchPath()
        {
            var dialog = new System.Windows.Forms.FolderBrowserDialog
            {
                Description = "选择要监控的文件夹",
                ShowNewFolderButton = false
            };

            if (dialog.ShowDialog() == System.Windows.Forms.DialogResult.OK)
            {
                string path = dialog.SelectedPath;
                if (!WatchPaths.Contains(path))
                {
                    WatchPaths.Add(path);
                    _logService.Info("SettingsViewModel", $"添加监控路径: {path}");
                }
                else
                {
                    MessageBox.Show("该路径已存在", "提示", MessageBoxButton.OK, MessageBoxImage.Information);
                }
            }
        }

        /// <summary>
        /// 移除监控路径
        /// </summary>
        private void RemoveWatchPath(string path)
        {
            if (WatchPaths.Contains(path))
            {
                WatchPaths.Remove(path);
                _logService.Info("SettingsViewModel", $"移除监控路径: {path}");
            }
        }

        /// <summary>
        /// 判断是否可以保存配置
        /// </summary>
        private bool CanSaveConfig()
        {
            return !IsBusy;
        }

        /// <summary>
        /// 判断是否可以注册设备
        /// </summary>
        private bool CanRegisterDevice()
        {
            return !IsRegistered && !IsBusy;
        }

        #endregion

        #region Helper Methods

        /// <summary>
        /// 加载配置
        /// </summary>
        private void LoadConfig()
        {
            try
            {
                var config = _configService.GetConfig();
                LoadConfigFromModel(config);

                IsRegistered = _configService.IsRegistered();
                DeviceId = _deviceService.GetDeviceId();

                _logService.Info("SettingsViewModel", "配置加载完成");
            }
            catch (Exception ex)
            {
                _logService.Error("SettingsViewModel", "加载配置失败", ex);
            }
        }

        /// <summary>
        /// 从配置模型加载
        /// </summary>
        private void LoadConfigFromModel(ConfigModel config)
        {
            // Server
            ApiBaseUrl = config.Server.ApiBaseUrl;
            Timeout = config.Server.Timeout;
            RetryTimes = config.Server.RetryTimes;

            // Device
            DeviceName = config.Device.DeviceName;
            Aid = config.Device.Aid;
            Bid = config.Device.Bid;
            Mdid = config.Device.Mdid;

            // Watcher
            WatchPaths.Clear();
            foreach (var path in config.Watcher.WatchPaths)
            {
                WatchPaths.Add(path);
            }
            ScanInterval = config.Watcher.ScanInterval;
            FileStableTime = config.Watcher.FileStableTime;
            AllowedExtensions = string.Join(",", config.Watcher.AllowedExtensions);
            MinFileSize = config.Watcher.MinFileSize;
            MaxFileSize = config.Watcher.MaxFileSize;

            // Upload
            ConcurrentUploads = config.Upload.ConcurrentUploads;
            MaxQueueSize = config.Upload.MaxQueueSize;
            AutoUpload = config.Upload.AutoUpload;
            MaxRetry = config.Upload.MaxRetry;

            // Heartbeat
            HeartbeatInterval = config.Heartbeat.Interval;
            HeartbeatTimeout = config.Heartbeat.Timeout;
        }

        /// <summary>
        /// 验证配置
        /// </summary>
        private bool ValidateConfig()
        {
            // 验证URL
            if (string.IsNullOrWhiteSpace(ApiBaseUrl))
            {
                SetError("请输入API服务器地址");
                return false;
            }

            if (!Uri.TryCreate(ApiBaseUrl, UriKind.Absolute, out _))
            {
                SetError("API服务器地址格式不正确");
                return false;
            }

            // 验证超时时间
            if (Timeout < 30 || Timeout > 300)
            {
                SetError("请求超时时间应在30-300秒之间");
                return false;
            }

            // 验证监控路径
            if (WatchPaths.Count == 0)
            {
                SetError("请至少添加一个监控路径");
                return false;
            }

            // 验证扩展名
            if (string.IsNullOrWhiteSpace(AllowedExtensions))
            {
                SetError("请输入允许的文件扩展名");
                return false;
            }

            ClearError();
            return true;
        }

        #endregion
    }
}
