using System;
using System.Diagnostics;
using System.Reflection;
using System.Windows;
using System.Windows.Input;
using AiTravelClient.Services;
using AiTravelClient.Utils;

namespace AiTravelClient.ViewModels
{
    /// <summary>
    /// 关于页视图模型
    /// </summary>
    public class AboutViewModel : BaseViewModel
    {
        private readonly ConfigService _configService;
        private readonly LogService _logService;
        private readonly System.Windows.Threading.DispatcherTimer _timer;
        private readonly DateTime _startTime;

        #region Properties

        private string _appName = "AI旅拍商家客户端";
        /// <summary>
        /// 应用程序名称
        /// </summary>
        public string AppName
        {
            get => _appName;
            set => SetProperty(ref _appName, value);
        }

        private string _appVersion;
        /// <summary>
        /// 应用程序版本
        /// </summary>
        public string AppVersion
        {
            get => _appVersion;
            set => SetProperty(ref _appVersion, value);
        }

        private string _copyright;
        /// <summary>
        /// 版权信息
        /// </summary>
        public string Copyright
        {
            get => _copyright;
            set => SetProperty(ref _copyright, value);
        }

        private string _companyName = "公司名称";
        /// <summary>
        /// 公司名称
        /// </summary>
        public string CompanyName
        {
            get => _companyName;
            set => SetProperty(ref _companyName, value);
        }

        private string _supportUrl = "https://www.example.com/support";
        /// <summary>
        /// 技术支持链接
        /// </summary>
        public string SupportUrl
        {
            get => _supportUrl;
            set => SetProperty(ref _supportUrl, value);
        }

        private string _deviceId;
        /// <summary>
        /// 设备ID
        /// </summary>
        public string DeviceId
        {
            get => _deviceId;
            set => SetProperty(ref _deviceId, value);
        }

        private string _osVersion;
        /// <summary>
        /// 操作系统版本
        /// </summary>
        public string OSVersion
        {
            get => _osVersion;
            set => SetProperty(ref _osVersion, value);
        }

        private string _dotNetVersion;
        /// <summary>
        /// .NET版本
        /// </summary>
        public string DotNetVersion
        {
            get => _dotNetVersion;
            set => SetProperty(ref _dotNetVersion, value);
        }

        private DateTime _startTimeValue;
        /// <summary>
        /// 启动时间
        /// </summary>
        public DateTime StartTimeValue
        {
            get => _startTimeValue;
            set => SetProperty(ref _startTimeValue, value);
        }

        private string _runningTime;
        /// <summary>
        /// 运行时长
        /// </summary>
        public string RunningTime
        {
            get => _runningTime;
            set => SetProperty(ref _runningTime, value);
        }

        private string _cpuInfo;
        /// <summary>
        /// CPU信息
        /// </summary>
        public string CpuInfo
        {
            get => _cpuInfo;
            set => SetProperty(ref _cpuInfo, value);
        }

        private string _memorySize;
        /// <summary>
        /// 内存大小
        /// </summary>
        public string MemorySize
        {
            get => _memorySize;
            set => SetProperty(ref _memorySize, value);
        }

        #endregion

        #region Commands

        public ICommand OpenSupportUrlCommand { get; }
        public ICommand CheckUpdateCommand { get; }
        public ICommand CopyDeviceIdCommand { get; }
        public ICommand ViewLicenseCommand { get; }

        #endregion

        public AboutViewModel(ConfigService configService, LogService logService)
        {
            _configService = configService;
            _logService = logService;
            _startTime = DateTime.Now;

            // 初始化命令
            OpenSupportUrlCommand = new RelayCommand(OpenSupportUrl);
            CheckUpdateCommand = new AsyncRelayCommand(CheckUpdateAsync);
            CopyDeviceIdCommand = new RelayCommand(CopyDeviceId);
            ViewLicenseCommand = new RelayCommand(ViewLicense);

            // 初始化定时器
            _timer = new System.Windows.Threading.DispatcherTimer
            {
                Interval = TimeSpan.FromSeconds(1)
            };
            _timer.Tick += Timer_Tick;
            _timer.Start();

            // 加载系统信息
            LoadSystemInfo();
        }

        #region Command Handlers

        /// <summary>
        /// 打开技术支持页面
        /// </summary>
        private void OpenSupportUrl()
        {
            try
            {
                Process.Start(new ProcessStartInfo
                {
                    FileName = SupportUrl,
                    UseShellExecute = true
                });
                _logService.Info("AboutViewModel", $"打开技术支持页面: {SupportUrl}");
            }
            catch (Exception ex)
            {
                _logService.Error("AboutViewModel", "打开技术支持页面失败", ex);
                MessageBox.Show($"打开页面失败: {ex.Message}", "错误", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        /// <summary>
        /// 检查更新
        /// </summary>
        private async System.Threading.Tasks.Task CheckUpdateAsync()
        {
            await SafeExecuteAsync(async () =>
            {
                _logService.Info("AboutViewModel", "检查更新");

                // TODO: 实现实际的更新检查逻辑
                await System.Threading.Tasks.Task.Delay(1000);

                MessageBox.Show(
                    $"当前版本: {AppVersion}\n已是最新版本",
                    "检查更新",
                    MessageBoxButton.OK,
                    MessageBoxImage.Information);
            }, "检查更新失败");
        }

        /// <summary>
        /// 复制设备ID
        /// </summary>
        private void CopyDeviceId()
        {
            try
            {
                if (!string.IsNullOrEmpty(DeviceId))
                {
                    Clipboard.SetText(DeviceId);
                    _logService.Info("AboutViewModel", "已复制设备ID到剪贴板");
                    MessageBox.Show("设备ID已复制到剪贴板", "成功", MessageBoxButton.OK, MessageBoxImage.Information);
                }
            }
            catch (Exception ex)
            {
                _logService.Error("AboutViewModel", "复制设备ID失败", ex);
                MessageBox.Show($"复制失败: {ex.Message}", "错误", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        /// <summary>
        /// 查看许可协议
        /// </summary>
        private void ViewLicense()
        {
            try
            {
                string license = @"AI旅拍商家客户端软件许可协议

版权所有 © 2024 公司名称

1. 许可授予
   本软件授权用户在遵守本协议条款的前提下使用本软件。

2. 使用限制
   - 不得对本软件进行反向工程、反编译或反汇编
   - 不得删除或修改本软件的版权声明
   - 不得将本软件用于非法目的

3. 免责声明
   本软件按"原样"提供，不提供任何明示或暗示的担保。

4. 责任限制
   在任何情况下，软件提供方不对使用本软件造成的任何损失承担责任。

5. 协议终止
   如违反本协议任何条款，许可将自动终止。";

                MessageBox.Show(license, "软件许可协议", MessageBoxButton.OK, MessageBoxImage.Information);
            }
            catch (Exception ex)
            {
                _logService.Error("AboutViewModel", "查看许可协议失败", ex);
            }
        }

        #endregion

        #region Helper Methods

        /// <summary>
        /// 加载系统信息
        /// </summary>
        private void LoadSystemInfo()
        {
            try
            {
                // 获取版本信息
                var assembly = Assembly.GetExecutingAssembly();
                var version = assembly.GetName().Version;
                AppVersion = $"v{version.Major}.{version.Minor}.{version.Build}";

                // 获取版权信息
                var copyrightAttr = assembly.GetCustomAttribute<AssemblyCopyrightAttribute>();
                Copyright = copyrightAttr?.Copyright ?? $"© {DateTime.Now.Year} {CompanyName}";

                // 获取设备ID
                DeviceId = SystemInfoHelper.GetMacAddress();

                // 获取操作系统版本
                OSVersion = SystemInfoHelper.GetOsVersion();

                // 获取.NET版本
                DotNetVersion = Environment.Version.ToString();

                // 获取CPU信息
                CpuInfo = SystemInfoHelper.GetCpuInfo();

                // 获取内存大小
                MemorySize = SystemInfoHelper.GetMemorySize();

                // 设置启动时间
                StartTimeValue = _startTime;

                _logService.Info("AboutViewModel", "系统信息加载完成");
            }
            catch (Exception ex)
            {
                _logService.Error("AboutViewModel", "加载系统信息失败", ex);
            }
        }

        /// <summary>
        /// 定时器事件
        /// </summary>
        private void Timer_Tick(object sender, EventArgs e)
        {
            var elapsed = DateTime.Now - _startTime;
            if (elapsed.TotalDays >= 1)
            {
                RunningTime = $"{(int)elapsed.TotalDays}天{elapsed.Hours}小时{elapsed.Minutes}分钟";
            }
            else if (elapsed.TotalHours >= 1)
            {
                RunningTime = $"{(int)elapsed.TotalHours}小时{elapsed.Minutes}分钟";
            }
            else
            {
                RunningTime = $"{elapsed.Minutes}分钟{elapsed.Seconds}秒";
            }
        }

        #endregion
    }
}
