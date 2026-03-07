using System;
using System.Collections.ObjectModel;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Windows;
using System.Windows.Input;
using AiTravelClient.Services;
using Microsoft.Win32;

namespace AiTravelClient.ViewModels
{
    /// <summary>
    /// 日志项模型
    /// </summary>
    public class LogItem
    {
        /// <summary>
        /// 时间戳
        /// </summary>
        public DateTime Timestamp { get; set; }

        /// <summary>
        /// 日志级别
        /// </summary>
        public LogLevel Level { get; set; }

        /// <summary>
        /// 日志内容
        /// </summary>
        public string Message { get; set; }

        /// <summary>
        /// 级别对应颜色
        /// </summary>
        public string LevelColor { get; set; }

        /// <summary>
        /// 格式化的时间字符串
        /// </summary>
        public string FormattedTime { get; set; }

        /// <summary>
        /// 级别图标
        /// </summary>
        public string LevelIcon { get; set; }

        /// <summary>
        /// 级别文本
        /// </summary>
        public string LevelText { get; set; }
    }

    /// <summary>
    /// 日志页视图模型
    /// </summary>
    public class LogViewModel : BaseViewModel
    {
        private readonly LogService _logService;
        private const int MaxDisplayItems = 1000;

        #region Properties

        private ObservableCollection<LogItem> _logItems;
        /// <summary>
        /// 日志项集合
        /// </summary>
        public ObservableCollection<LogItem> LogItems
        {
            get => _logItems;
            set => SetProperty(ref _logItems, value);
        }

        private ObservableCollection<LogItem> _filteredLogItems;
        /// <summary>
        /// 过滤后的日志项
        /// </summary>
        public ObservableCollection<LogItem> FilteredLogItems
        {
            get => _filteredLogItems;
            set => SetProperty(ref _filteredLogItems, value);
        }

        private LogLevel? _selectedLogLevel;
        /// <summary>
        /// 选中的日志级别（null表示全部）
        /// </summary>
        public LogLevel? SelectedLogLevel
        {
            get => _selectedLogLevel;
            set
            {
                if (SetProperty(ref _selectedLogLevel, value))
                {
                    ApplyFilter();
                }
            }
        }

        private string _searchKeyword;
        /// <summary>
        /// 搜索关键词
        /// </summary>
        public string SearchKeyword
        {
            get => _searchKeyword;
            set
            {
                if (SetProperty(ref _searchKeyword, value))
                {
                    ApplyFilter();
                }
            }
        }

        private bool _autoScroll = true;
        /// <summary>
        /// 是否自动滚动到底部
        /// </summary>
        public bool AutoScroll
        {
            get => _autoScroll;
            set => SetProperty(ref _autoScroll, value);
        }

        /// <summary>
        /// 可选日志级别列表
        /// </summary>
        public LogLevel?[] LogLevels { get; } = new LogLevel?[]
        {
            null,
            LogLevel.DEBUG,
            LogLevel.INFO,
            LogLevel.WARN,
            LogLevel.ERROR
        };

        #endregion

        #region Commands

        public ICommand ClearLogsCommand { get; }
        public ICommand ExportLogsCommand { get; }
        public ICommand SearchLogsCommand { get; }
        public ICommand OpenLogFolderCommand { get; }
        public ICommand RefreshLogsCommand { get; }

        #endregion

        public LogViewModel(LogService logService)
        {
            _logService = logService;

            // 初始化集合
            LogItems = new ObservableCollection<LogItem>();
            FilteredLogItems = new ObservableCollection<LogItem>();

            // 初始化命令
            ClearLogsCommand = new RelayCommand(ClearLogs);
            ExportLogsCommand = new AsyncRelayCommand(ExportLogsAsync);
            SearchLogsCommand = new RelayCommand(ApplyFilter);
            OpenLogFolderCommand = new RelayCommand(OpenLogFolder);
            RefreshLogsCommand = new AsyncRelayCommand(RefreshLogsAsync);

            // 订阅日志事件
            _logService.OnLogReceived += OnLogReceived;

            // 加载现有日志
            LoadExistingLogs();
        }

        #region Command Handlers

        /// <summary>
        /// 清空日志
        /// </summary>
        private void ClearLogs()
        {
            var result = MessageBox.Show(
                "确定要清空所有日志吗？",
                "确认清空",
                MessageBoxButton.YesNo,
                MessageBoxImage.Question);

            if (result == MessageBoxResult.Yes)
            {
                LogItems.Clear();
                FilteredLogItems.Clear();
                _logService.Info("LogViewModel", "已清空日志显示");
            }
        }

        /// <summary>
        /// 导出日志
        /// </summary>
        private async System.Threading.Tasks.Task ExportLogsAsync()
        {
            await SafeExecuteAsync(async () =>
            {
                var dialog = new SaveFileDialog
                {
                    Filter = "文本文件 (*.txt)|*.txt|所有文件 (*.*)|*.*",
                    FileName = $"logs_{DateTime.Now:yyyyMMddHHmmss}.txt",
                    DefaultExt = "txt"
                };

                if (dialog.ShowDialog() == true)
                {
                    using (var writer = new StreamWriter(dialog.FileName, false, System.Text.Encoding.UTF8))
                    {
                        foreach (var item in FilteredLogItems)
                        {
                            await writer.WriteLineAsync($"[{item.FormattedTime}] [{item.LevelText}] {item.Message}");
                        }
                    }

                    _logService.Info("LogViewModel", $"日志已导出到: {dialog.FileName}");
                    MessageBox.Show("日志导出成功", "成功", MessageBoxButton.OK, MessageBoxImage.Information);
                }
            }, "导出日志失败");
        }

        /// <summary>
        /// 打开日志文件夹
        /// </summary>
        private void OpenLogFolder()
        {
            try
            {
                string logPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "logs");
                if (!Directory.Exists(logPath))
                {
                    Directory.CreateDirectory(logPath);
                }
                Process.Start("explorer.exe", logPath);
            }
            catch (Exception ex)
            {
                _logService.Error("LogViewModel", "打开日志文件夹失败", ex);
                MessageBox.Show($"打开日志文件夹失败: {ex.Message}", "错误", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        /// <summary>
        /// 刷新日志
        /// </summary>
        private async System.Threading.Tasks.Task RefreshLogsAsync()
        {
            await SafeExecuteAsync(async () =>
            {
                LogItems.Clear();
                FilteredLogItems.Clear();
                LoadExistingLogs();
                await System.Threading.Tasks.Task.CompletedTask;
            }, "刷新日志失败");
        }

        #endregion

        #region Event Handlers

        /// <summary>
        /// 接收新日志
        /// </summary>
        private void OnLogReceived(string timestamp, LogLevel level, string message)
        {
            Application.Current.Dispatcher.Invoke(() =>
            {
                var logItem = new LogItem
                {
                    Timestamp = DateTime.Parse(timestamp),
                    Level = level,
                    Message = message,
                    FormattedTime = timestamp,
                    LevelColor = GetLevelColor(level),
                    LevelIcon = GetLevelIcon(level),
                    LevelText = GetLevelText(level)
                };

                LogItems.Add(logItem);

                // 限制最大显示数量
                while (LogItems.Count > MaxDisplayItems)
                {
                    LogItems.RemoveAt(0);
                }

                // 应用过滤
                if (ShouldShowLogItem(logItem))
                {
                    FilteredLogItems.Add(logItem);

                    // 限制过滤后的数量
                    while (FilteredLogItems.Count > MaxDisplayItems)
                    {
                        FilteredLogItems.RemoveAt(0);
                    }
                }
            });
        }

        #endregion

        #region Helper Methods

        /// <summary>
        /// 加载现有日志
        /// </summary>
        private void LoadExistingLogs()
        {
            try
            {
                string logPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "logs");
                if (!Directory.Exists(logPath))
                {
                    return;
                }

                string todayLog = Path.Combine(logPath, $"runtime_{DateTime.Now:yyyyMMdd}.log");
                if (File.Exists(todayLog))
                {
                    var lines = File.ReadAllLines(todayLog, System.Text.Encoding.UTF8);
                    int startIndex = Math.Max(0, lines.Length - MaxDisplayItems);

                    for (int i = startIndex; i < lines.Length; i++)
                    {
                        ParseLogLine(lines[i]);
                    }
                }

                ApplyFilter();
            }
            catch (Exception ex)
            {
                _logService.Error("LogViewModel", "加载现有日志失败", ex);
            }
        }

        /// <summary>
        /// 解析日志行
        /// </summary>
        private void ParseLogLine(string line)
        {
            try
            {
                // 格式: [2024-01-01 12:00:00] [INFO] [Module] Message
                if (string.IsNullOrWhiteSpace(line))
                    return;

                int firstBracket = line.IndexOf('[');
                int secondBracket = line.IndexOf(']');
                if (firstBracket < 0 || secondBracket < 0)
                    return;

                string timestamp = line.Substring(firstBracket + 1, secondBracket - firstBracket - 1);

                int thirdBracket = line.IndexOf('[', secondBracket + 1);
                int fourthBracket = line.IndexOf(']', thirdBracket + 1);
                if (thirdBracket < 0 || fourthBracket < 0)
                    return;

                string levelStr = line.Substring(thirdBracket + 1, fourthBracket - thirdBracket - 1).Trim();
                LogLevel level = (LogLevel)Enum.Parse(typeof(LogLevel), levelStr, true);

                string message = line.Substring(fourthBracket + 1).Trim();

                var logItem = new LogItem
                {
                    Timestamp = DateTime.Parse(timestamp),
                    Level = level,
                    Message = message,
                    FormattedTime = timestamp,
                    LevelColor = GetLevelColor(level),
                    LevelIcon = GetLevelIcon(level),
                    LevelText = GetLevelText(level)
                };

                LogItems.Add(logItem);
            }
            catch
            {
                // 忽略解析失败的行
            }
        }

        /// <summary>
        /// 应用过滤
        /// </summary>
        private void ApplyFilter()
        {
            FilteredLogItems.Clear();

            foreach (var item in LogItems)
            {
                if (ShouldShowLogItem(item))
                {
                    FilteredLogItems.Add(item);
                }
            }
        }

        /// <summary>
        /// 判断是否应该显示日志项
        /// </summary>
        private bool ShouldShowLogItem(LogItem item)
        {
            // 级别过滤
            if (SelectedLogLevel.HasValue && item.Level != SelectedLogLevel.Value)
            {
                return false;
            }

            // 关键词过滤
            if (!string.IsNullOrWhiteSpace(SearchKeyword))
            {
                if (!item.Message.Contains(SearchKeyword, StringComparison.OrdinalIgnoreCase))
                {
                    return false;
                }
            }

            return true;
        }

        /// <summary>
        /// 获取日志级别颜色
        /// </summary>
        private string GetLevelColor(LogLevel level)
        {
            return level switch
            {
                LogLevel.DEBUG => "#808080",
                LogLevel.INFO => "#2196F3",
                LogLevel.WARN => "#FF9800",
                LogLevel.ERROR => "#F44336",
                _ => "#000000"
            };
        }

        /// <summary>
        /// 获取日志级别图标
        /// </summary>
        private string GetLevelIcon(LogLevel level)
        {
            return level switch
            {
                LogLevel.DEBUG => "🔍",
                LogLevel.INFO => "ℹ️",
                LogLevel.WARN => "⚠️",
                LogLevel.ERROR => "❌",
                _ => "•"
            };
        }

        /// <summary>
        /// 获取日志级别文本
        /// </summary>
        private string GetLevelText(LogLevel level)
        {
            return level switch
            {
                LogLevel.DEBUG => "调试",
                LogLevel.INFO => "信息",
                LogLevel.WARN => "警告",
                LogLevel.ERROR => "错误",
                _ => "未知"
            };
        }

        #endregion
    }
}
