using System.Windows;
using AiTravelClient.Services;
using AiTravelClient.ViewModels;
using AiTravelClient.Views;

namespace AiTravelClient
{
    /// <summary>
    /// App.xaml 的交互逻辑
    /// </summary>
    public partial class App : Application
    {
        protected override void OnStartup(StartupEventArgs e)
        {
            base.OnStartup(e);

            // 初始化服务
            var configService = new ConfigService();
            var logService = new LogService();
            var apiClient = new ApiClient(configService, logService);
            var deviceService = new DeviceService(configService, apiClient, logService);
            var fileWatcherService = new FileWatcherService(configService, logService);
            var uploadService = new UploadService(configService, apiClient, logService);
            var heartbeatService = new HeartbeatService(configService, apiClient, deviceService, logService);

            // 创建MainViewModel
            var mainViewModel = new MainViewModel(
                configService,
                deviceService,
                fileWatcherService,
                uploadService,
                heartbeatService,
                logService
            );

            // 创建并显示主窗口
            var mainWindow = new MainWindow
            {
                DataContext = mainViewModel
            };

            mainWindow.Show();
        }
    }
}
