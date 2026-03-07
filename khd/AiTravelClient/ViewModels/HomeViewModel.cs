using System;
using System.Windows;
using System.Windows.Input;
using System.Windows.Threading;
using AiTravelClient.Services;

namespace AiTravelClient.ViewModels
{
    /// <summary>
    /// 首页视图模型
    /// </summary>
    public class HomeViewModel : BaseViewModel
    {
        private readonly FileWatcherService _fileWatcherService;
        private readonly UploadService _uploadService;
        private readonly HeartbeatService _heartbeatService;
        private readonly ConfigService _configService;
        private readonly LogService _logService;
        private readonly DispatcherTimer _timer;
        private DateTime _startTime;

        #region Properties

        private bool _isMonitoring;
        /// <summary>
        /// 是否正在监控
        /// </summary>
        public bool IsMonitoring
        {
            get => _isMonitoring;
            set => SetProperty(ref _isMonitoring, value);
        }

        private bool _isUploading;
        /// <summary>
        /// 是否正在上传
        /// </summary>
        public bool IsUploading
        {
            get => _isUploading;
            set => SetProperty(ref _isUploading, value);
        }

        private string _deviceStatus = "未知";
        /// <summary>
        /// 设备状态文本
        /// </summary>
        public string DeviceStatus
        {
            get => _deviceStatus;
            set => SetProperty(ref _deviceStatus, value);
        }

        private DateTime? _lastHeartbeatTime;
        /// <summary>
        /// 最后心跳时间
        /// </summary>
        public DateTime? LastHeartbeatTime
        {
            get => _lastHeartbeatTime;
            set
            {
                if (SetProperty(ref _lastHeartbeatTime, value))
                {
                    OnPropertyChanged(nameof(LastHeartbeatTimeText));
                }
            }
        }

        /// <summary>
        /// 最后心跳时间文本
        /// </summary>
        public string LastHeartbeatTimeText => LastHeartbeatTime?.ToString("yyyy-MM-dd HH:mm:ss") ?? "从未";

        private int _todaySuccessCount;
        /// <summary>
        /// 今日上传成功数
        /// </summary>
        public int TodaySuccessCount
        {
            get => _todaySuccessCount;
            set
            {
                if (SetProperty(ref _todaySuccessCount, value))
                {
                    OnPropertyChanged(nameof(TodaySuccessRate));
                }
            }
        }

        private int _todayFailedCount;
        /// <summary>
        /// 今日上传失败数
        /// </summary>
        public int TodayFailedCount
        {
            get => _todayFailedCount;
            set
            {
                if (SetProperty(ref _todayFailedCount, value))
                {
                    OnPropertyChanged(nameof(TodaySuccessRate));
                }
            }
        }

        private int _totalSuccessCount;
        /// <summary>
        /// 累计上传成功数
        /// </summary>
        public int TotalSuccessCount
        {
            get => _totalSuccessCount;
            set => SetProperty(ref _totalSuccessCount, value);
        }

        private int _totalFailedCount;
        /// <summary>
        /// 累计上传失败数
        /// </summary>
        public int TotalFailedCount
        {
            get => _totalFailedCount;
            set => SetProperty(ref _totalFailedCount, value);
        }

        private int _queuePendingCount;
        /// <summary>
        /// 队列待上传数
        /// </summary>
        public int QueuePendingCount
        {
            get => _queuePendingCount;
            set => SetProperty(ref _queuePendingCount, value);
        }

        private int _uploadingCount;
        /// <summary>
        /// 正在上传数
        /// </summary>
        public int UploadingCount
        {
            get => _uploadingCount;
            set => SetProperty(ref _uploadingCount, value);
        }

        /// <summary>
        /// 今日成功率（百分比）
        /// </summary>
        public double TodaySuccessRate
        {
            get
            {
                int total = TodaySuccessCount + TodayFailedCount;
                if (total == 0) return 0;
                return Math.Round((double)TodaySuccessCount / total * 100, 2);
            }
        }

        private string _uploadSpeed = "0 文件/分";
        /// <summary>
        /// 上传速度
        /// </summary>
        public string UploadSpeed
        {
            get => _uploadSpeed;
            set => SetProperty(ref _uploadSpeed, value);
        }

        private string _runningTime = "00:00:00";
        /// <summary>
        /// 运行时长
        /// </summary>
        public string RunningTime
        {
            get => _runningTime;
            set => SetProperty(ref _runningTime, value);
        }

        #endregion

        #region Commands

        public ICommand StartMonitoringCommand { get; }
        public ICommand StopMonitoringCommand { get; }
        public ICommand PauseUploadCommand { get; }
        public ICommand ResumeUploadCommand { get; }
        public ICommand ClearStatisticsCommand { get; }
        public ICommand RefreshStatusCommand { get; }

        #endregion

        public HomeViewModel(
            FileWatcherService fileWatcherService,
            UploadService uploadService,
            HeartbeatService heartbeatService,
            ConfigService configService,
            LogService logService)
        {
            _fileWatcherService = fileWatcherService;
            _uploadService = uploadService;
            _heartbeatService = heartbeatService;
            _configService = configService;
            _logService = logService;

            // 初始化命令
            StartMonitoringCommand = new AsyncRelayCommand(StartMonitoringAsync, CanStartMonitoring);
            StopMonitoringCommand = new RelayCommand(StopMonitoring, () => IsMonitoring);
            PauseUploadCommand = new RelayCommand(PauseUpload, () => IsUploading);
            ResumeUploadCommand = new RelayCommand(ResumeUpload, () => !IsUploading && QueuePendingCount > 0);
            ClearStatisticsCommand = new RelayCommand(ClearStatistics);
            RefreshStatusCommand = new AsyncRelayCommand(RefreshStatusAsync);

            // 订阅事件
            SubscribeEvents();

            // 初始化定时器
            _timer = new DispatcherTimer
            {
                Interval = TimeSpan.FromSeconds(1)
            };
            _timer.Tick += Timer_Tick;
            _timer.Start();

            _startTime = DateTime.Now;

            // 加载统计数据
            LoadStatistics();
        }

        /// <summary>
        /// 订阅服务事件
        /// </summary>
        private void SubscribeEvents()
        {
            // 订阅文件监控服务事件
            _fileWatcherService.StatusChanged += OnWatcherStatusChanged;

            // 订阅上传服务事件
            _uploadService.TaskCompleted += OnUploadTaskCompleted;
            _uploadService.QueueChanged += OnUploadQueueChanged;
            _uploadService.StatusChanged += OnUploadStatusChanged;

            // 订阅心跳服务事件
            _heartbeatService.HeartbeatSuccess += OnHeartbeatSuccess;
            _heartbeatService.HeartbeatFailed += OnHeartbeatFailed;
        }

        #region Command Handlers

        /// <summary>
        /// 开始监控
        /// </summary>
        private async System.Threading.Tasks.Task StartMonitoringAsync()
        {
            await SafeExecuteAsync(async () =>
            {
                _logService.Info("HomeViewModel", "开始监控");

                // 启动文件监控
                bool watcherStarted = _fileWatcherService.Start();
                if (!watcherStarted)
                {
                    SetError("启动文件监控失败");
                    return;
                }

                // 如果配置了自动上传，启动上传服务
                var config = _configService.GetConfig();
                if (config.Upload.AutoUpload)
                {
                    bool uploadStarted = _uploadService.Start();
                    if (!uploadStarted)
                    {
                        _logService.Warn("HomeViewModel", "启动上传服务失败");
                    }
                }

                // 启动心跳服务
                bool heartbeatStarted = _heartbeatService.Start();
                if (!heartbeatStarted)
                {
                    _logService.Warn("HomeViewModel", "启动心跳服务失败");
                }

                IsMonitoring = true;
                _startTime = DateTime.Now;

                await System.Threading.Tasks.Task.CompletedTask;
            }, "启动监控失败");
        }

        /// <summary>
        /// 停止监控
        /// </summary>
        private void StopMonitoring()
        {
            SafeExecute(() =>
            {
                _logService.Info("HomeViewModel", "停止监控");

                // 停止文件监控
                _fileWatcherService.Stop();

                // 停止上传服务
                _uploadService.Stop();

                // 停止心跳服务
                _heartbeatService.Stop();

                IsMonitoring = false;
                IsUploading = false;
            }, "停止监控失败");
        }

        /// <summary>
        /// 暂停上传
        /// </summary>
        private void PauseUpload()
        {
            SafeExecute(() =>
            {
                _uploadService.Pause();
                _logService.Info("HomeViewModel", "暂停上传");
            }, "暂停上传失败");
        }

        /// <summary>
        /// 恢复上传
        /// </summary>
        private void ResumeUpload()
        {
            SafeExecute(() =>
            {
                _uploadService.Resume();
                _logService.Info("HomeViewModel", "恢复上传");
            }, "恢复上传失败");
        }

        /// <summary>
        /// 清除统计
        /// </summary>
        private void ClearStatistics()
        {
            var result = MessageBox.Show(
                "确定要清除今日统计数据吗？",
                "确认清除",
                MessageBoxButton.YesNo,
                MessageBoxImage.Question);

            if (result == MessageBoxResult.Yes)
            {
                TodaySuccessCount = 0;
                TodayFailedCount = 0;
                _logService.Info("HomeViewModel", "已清除今日统计");
            }
        }

        /// <summary>
        /// 刷新状态
        /// </summary>
        private async System.Threading.Tasks.Task RefreshStatusAsync()
        {
            await SafeExecuteAsync(async () =>
            {
                _logService.Info("HomeViewModel", "刷新状态");

                // 更新监控状态
                IsMonitoring = _fileWatcherService.IsRunning;

                // 更新上传状态
                IsUploading = _uploadService.IsRunning;

                // 更新队列信息
                QueuePendingCount = _uploadService.GetPendingCount();
                UploadingCount = _uploadService.GetUploadingCount();

                // 计算上传速度
                UpdateUploadSpeed();

                await System.Threading.Tasks.Task.CompletedTask;
            }, "刷新状态失败");
        }

        /// <summary>
        /// 判断是否可以开始监控
        /// </summary>
        private bool CanStartMonitoring()
        {
            return !IsMonitoring && !IsBusy && _configService.IsRegistered();
        }

        #endregion

        #region Event Handlers

        private void OnWatcherStatusChanged(object sender, bool isRunning)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                IsMonitoring = isRunning;
            });
        }

        private void OnUploadTaskCompleted(object sender, Models.UploadTask task)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                if (task.Status == Models.TaskStatus.Success)
                {
                    TodaySuccessCount++;
                    TotalSuccessCount++;
                }
                else if (task.Status == Models.TaskStatus.Failed)
                {
                    TodayFailedCount++;
                    TotalFailedCount++;
                }

                SaveStatistics();
            });
        }

        private void OnUploadQueueChanged(object sender, int count)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                QueuePendingCount = count;
                UploadingCount = _uploadService.GetUploadingCount();
            });
        }

        private void OnUploadStatusChanged(object sender, bool isRunning)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                IsUploading = isRunning;
            });
        }

        private void OnHeartbeatSuccess(object sender, EventArgs e)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                LastHeartbeatTime = DateTime.Now;
                DeviceStatus = "在线";
            });
        }

        private void OnHeartbeatFailed(object sender, string errorMessage)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                DeviceStatus = $"离线: {errorMessage}";
            });
        }

        private void Timer_Tick(object sender, EventArgs e)
        {
            // 更新运行时长
            if (IsMonitoring)
            {
                var elapsed = DateTime.Now - _startTime;
                RunningTime = $"{elapsed.Hours:D2}:{elapsed.Minutes:D2}:{elapsed.Seconds:D2}";
            }

            // 更新上传速度
            if (IsUploading)
            {
                UpdateUploadSpeed();
            }
        }

        #endregion

        #region Helper Methods

        /// <summary>
        /// 加载统计数据
        /// </summary>
        private void LoadStatistics()
        {
            try
            {
                var config = _configService.GetConfig();
                // 这里可以从配置或数据库加载历史统计
                // 暂时使用默认值
                TotalSuccessCount = 0;
                TotalFailedCount = 0;
            }
            catch (Exception ex)
            {
                _logService.Error("HomeViewModel", "加载统计数据失败", ex);
            }
        }

        /// <summary>
        /// 保存统计数据
        /// </summary>
        private void SaveStatistics()
        {
            try
            {
                // 这里可以将统计数据保存到配置或数据库
                // 暂时不实现持久化
            }
            catch (Exception ex)
            {
                _logService.Error("HomeViewModel", "保存统计数据失败", ex);
            }
        }

        /// <summary>
        /// 更新上传速度
        /// </summary>
        private void UpdateUploadSpeed()
        {
            try
            {
                // 简单计算：基于最近一分钟的成功数
                // 实际应用中可以使用更精确的算法
                if (IsMonitoring && _startTime != null)
                {
                    var elapsed = DateTime.Now - _startTime;
                    if (elapsed.TotalMinutes > 0)
                    {
                        double speed = TodaySuccessCount / elapsed.TotalMinutes;
                        UploadSpeed = $"{speed:F1} 文件/分";
                    }
                }
                else
                {
                    UploadSpeed = "0 文件/分";
                }
            }
            catch (Exception ex)
            {
                _logService.Error("HomeViewModel", "更新上传速度失败", ex);
            }
        }

        #endregion
    }
}
