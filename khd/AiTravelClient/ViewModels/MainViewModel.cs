using System;
using System.ComponentModel;
using System.Windows;
using System.Windows.Input;
using AiTravelClient.Models;
using AiTravelClient.Services;

namespace AiTravelClient.ViewModels
{
    /// <summary>
    /// 网络连接状态枚举
    /// </summary>
    public enum NetworkStatus
    {
        Connected,
        Disconnected,
        Error
    }

    /// <summary>
    /// 主窗口视图模型
    /// </summary>
    public class MainViewModel : BaseViewModel
    {
        private readonly ConfigService _configService;
        private readonly DeviceService _deviceService;
        private readonly FileWatcherService _fileWatcherService;
        private readonly UploadService _uploadService;
        private readonly HeartbeatService _heartbeatService;
        private readonly LogService _logService;

        #region Properties

        private object _currentView;
        /// <summary>
        /// 当前显示的视图内容
        /// </summary>
        public object CurrentView
        {
            get => _currentView;
            set => SetProperty(ref _currentView, value);
        }

        private string _windowTitle = "AI旅拍商家客户端";
        /// <summary>
        /// 窗口标题
        /// </summary>
        public string WindowTitle
        {
            get => _windowTitle;
            set => SetProperty(ref _windowTitle, value);
        }

        private bool _isLoggedIn;
        /// <summary>
        /// 设备是否已注册
        /// </summary>
        public bool IsLoggedIn
        {
            get => _isLoggedIn;
            set => SetProperty(ref _isLoggedIn, value);
        }

        private string _deviceName = "未注册";
        /// <summary>
        /// 设备名称
        /// </summary>
        public string DeviceName
        {
            get => _deviceName;
            set => SetProperty(ref _deviceName, value);
        }

        private NetworkStatus _connectionStatus = NetworkStatus.Disconnected;
        /// <summary>
        /// 网络连接状态
        /// </summary>
        public NetworkStatus ConnectionStatus
        {
            get => _connectionStatus;
            set => SetProperty(ref _connectionStatus, value);
        }

        private string _statusText = "就绪";
        /// <summary>
        /// 状态栏文本
        /// </summary>
        public string StatusText
        {
            get => _statusText;
            set => SetProperty(ref _statusText, value);
        }

        private string _statusColor = "#808080";
        /// <summary>
        /// 状态栏颜色
        /// </summary>
        public string StatusColor
        {
            get => _statusColor;
            set => SetProperty(ref _statusColor, value);
        }

        #endregion

        #region Commands

        public ICommand NavigateToHomeCommand { get; }
        public ICommand NavigateToSettingsCommand { get; }
        public ICommand NavigateToLogCommand { get; }
        public ICommand NavigateToAboutCommand { get; }
        public ICommand WindowClosingCommand { get; }
        public ICommand MinimizeToTrayCommand { get; }

        #endregion

        #region ViewModels

        public HomeViewModel HomeViewModel { get; }
        public SettingsViewModel SettingsViewModel { get; }
        public LogViewModel LogViewModel { get; }
        public AboutViewModel AboutViewModel { get; }

        #endregion

        public MainViewModel(
            ConfigService configService,
            DeviceService deviceService,
            FileWatcherService fileWatcherService,
            UploadService uploadService,
            HeartbeatService heartbeatService,
            LogService logService)
        {
            _configService = configService;
            _deviceService = deviceService;
            _fileWatcherService = fileWatcherService;
            _uploadService = uploadService;
            _heartbeatService = heartbeatService;
            _logService = logService;

            // 初始化子ViewModels
            HomeViewModel = new HomeViewModel(
                _fileWatcherService,
                _uploadService,
                _heartbeatService,
                _configService,
                _logService);

            SettingsViewModel = new SettingsViewModel(
                _configService,
                _deviceService,
                _logService);

            LogViewModel = new LogViewModel(_logService);

            AboutViewModel = new AboutViewModel(_configService, _logService);

            // 初始化命令
            NavigateToHomeCommand = new RelayCommand(NavigateToHome);
            NavigateToSettingsCommand = new RelayCommand(NavigateToSettings);
            NavigateToLogCommand = new RelayCommand(NavigateToLog);
            NavigateToAboutCommand = new RelayCommand(NavigateToAbout);
            WindowClosingCommand = new RelayCommand<CancelEventArgs>(OnWindowClosing);
            MinimizeToTrayCommand = new RelayCommand(MinimizeToTray);

            // 订阅事件
            SubscribeEvents();

            // 执行初始化
            InitializeAsync();
        }

        /// <summary>
        /// 异步初始化
        /// </summary>
        private async void InitializeAsync()
        {
            try
            {
                _logService.Info("MainViewModel", "开始初始化应用程序");

                // 加载配置
                var config = _configService.GetConfig();
                
                // 检查设备是否已注册
                if (_configService.IsRegistered())
                {
                    IsLoggedIn = true;
                    DeviceName = config.Device.DeviceName;
                    
                    // 测试连接
                    await TestConnectionAsync();
                    
                    // 根据配置决定是否自动启动
                    if (config.Upload.AutoUpload)
                    {
                        _logService.Info("MainViewModel", "配置了自动上传，准备自动启动服务");
                        // 可以在这里添加自动启动逻辑
                    }
                }
                else
                {
                    _logService.Info("MainViewModel", "设备未注册，请先进行设备注册");
                    // 导航到设置页
                    NavigateToSettings();
                }

                // 默认显示首页
                if (CurrentView == null)
                {
                    NavigateToHome();
                }

                UpdateStatus("就绪", "#4CAF50");
            }
            catch (Exception ex)
            {
                _logService.Error("MainViewModel", "初始化失败", ex);
                UpdateStatus("初始化失败", "#F44336");
            }
        }

        /// <summary>
        /// 订阅事件
        /// </summary>
        private void SubscribeEvents()
        {
            // 订阅心跳事件
            _heartbeatService.HeartbeatSuccess += OnHeartbeatSuccess;
            _heartbeatService.HeartbeatFailed += OnHeartbeatFailed;

            // 订阅监控服务状态变化
            _fileWatcherService.StatusChanged += OnWatcherStatusChanged;

            // 订阅上传服务状态变化
            _uploadService.StatusChanged += OnUploadStatusChanged;

            // 订阅SettingsViewModel的注册完成事件
            SettingsViewModel.RegistrationCompleted += OnRegistrationCompleted;
        }

        #region Navigation Methods

        private void NavigateToHome()
        {
            CurrentView = HomeViewModel;
            _logService.Debug("MainViewModel", "导航到首页");
        }

        private void NavigateToSettings()
        {
            CurrentView = SettingsViewModel;
            _logService.Debug("MainViewModel", "导航到设置页");
        }

        private void NavigateToLog()
        {
            CurrentView = LogViewModel;
            _logService.Debug("MainViewModel", "导航到日志页");
        }

        private void NavigateToAbout()
        {
            CurrentView = AboutViewModel;
            _logService.Debug("MainViewModel", "导航到关于页");
        }

        #endregion

        #region Event Handlers

        private void OnHeartbeatSuccess(object sender, EventArgs e)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                ConnectionStatus = NetworkStatus.Connected;
                UpdateStatus("已连接", "#4CAF50");
            });
        }

        private void OnHeartbeatFailed(object sender, string errorMessage)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                ConnectionStatus = NetworkStatus.Error;
                UpdateStatus($"连接异常: {errorMessage}", "#F44336");
            });
        }

        private void OnWatcherStatusChanged(object sender, bool isRunning)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                if (isRunning)
                {
                    UpdateStatus("监控中", "#2196F3");
                }
            });
        }

        private void OnUploadStatusChanged(object sender, bool isRunning)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                if (isRunning)
                {
                    UpdateStatus("上传中", "#2196F3");
                }
            });
        }

        private void OnRegistrationCompleted(object sender, bool success)
        {
            if (success)
            {
                var config = _configService.GetConfig();
                IsLoggedIn = true;
                DeviceName = config.Device.DeviceName;
                NavigateToHome();
            }
        }

        #endregion

        #region Window Management

        private void OnWindowClosing(CancelEventArgs e)
        {
            try
            {
                // 检查是否有服务正在运行
                bool hasRunningService = _fileWatcherService.IsRunning || _uploadService.IsRunning;

                if (hasRunningService)
                {
                    var result = MessageBox.Show(
                        "服务正在运行，确定要退出吗？",
                        "确认退出",
                        MessageBoxButton.YesNo,
                        MessageBoxImage.Question);

                    if (result == MessageBoxResult.No)
                    {
                        e.Cancel = true;
                        return;
                    }
                }

                // 停止所有服务
                StopAllServices();

                // 保存配置
                _configService.SaveConfig(_configService.GetConfig());

                _logService.Info("MainViewModel", "应用程序正常退出");
            }
            catch (Exception ex)
            {
                _logService.Error("MainViewModel", "退出时发生错误", ex);
            }
        }

        private void MinimizeToTray()
        {
            // 最小化到系统托盘的逻辑
            // 需要在MainWindow中配合实现
            _logService.Info("MainViewModel", "最小化到系统托盘");
        }

        #endregion

        #region Service Management

        /// <summary>
        /// 停止所有服务
        /// </summary>
        private void StopAllServices()
        {
            try
            {
                if (_fileWatcherService.IsRunning)
                {
                    _fileWatcherService.Stop();
                }

                if (_uploadService.IsRunning)
                {
                    _uploadService.Stop();
                }

                if (_heartbeatService.IsRunning)
                {
                    _heartbeatService.Stop();
                }

                _logService.Info("MainViewModel", "所有服务已停止");
            }
            catch (Exception ex)
            {
                _logService.Error("MainViewModel", "停止服务时发生错误", ex);
            }
        }

        /// <summary>
        /// 测试服务器连接
        /// </summary>
        private async System.Threading.Tasks.Task TestConnectionAsync()
        {
            try
            {
                ConnectionStatus = NetworkStatus.Disconnected;
                UpdateStatus("测试连接中...", "#FF9800");

                bool isConnected = await _deviceService.VerifyTokenAsync();
                
                if (isConnected)
                {
                    ConnectionStatus = NetworkStatus.Connected;
                    UpdateStatus("已连接", "#4CAF50");
                }
                else
                {
                    ConnectionStatus = NetworkStatus.Error;
                    UpdateStatus("连接失败", "#F44336");
                }
            }
            catch (Exception ex)
            {
                _logService.Error("MainViewModel", "测试连接失败", ex);
                ConnectionStatus = NetworkStatus.Error;
                UpdateStatus("连接异常", "#F44336");
            }
        }

        #endregion

        #region Helper Methods

        /// <summary>
        /// 更新状态栏
        /// </summary>
        private void UpdateStatus(string text, string color)
        {
            StatusText = text;
            StatusColor = color;
        }

        #endregion
    }
}
