using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using AiTravelClient.Models;
using AiTravelClient.Utils;

namespace AiTravelClient.Services
{
    /// <summary>
    /// 文件监控服务
    /// 持续监控指定文件夹的文件变化，实时检测新增的图片文件
    /// </summary>
    public class FileWatcherService
    {
        private readonly ConfigService _configService;
        private readonly LogService _logService;
        private readonly Dictionary<string, FileSystemWatcher> _watchers;
        private readonly HashSet<string> _processedFiles; // 已处理的文件MD5集合
        private Timer _scanTimer;
        private bool _isRunning;
        private WatcherStatus _status;
        private readonly object _lockObj = new object();

        /// <summary>
        /// 文件检测事件
        /// </summary>
        public event Action<string> OnFileDetected;

        /// <summary>
        /// 监控状态变化事件
        /// </summary>
        public event Action<WatcherStatus> OnStatusChanged;

        /// <summary>
        /// 监控错误事件
        /// </summary>
        public event Action<string> OnWatcherError;

        public FileWatcherService(ConfigService configService, LogService logService)
        {
            _configService = configService;
            _logService = logService;
            _watchers = new Dictionary<string, FileSystemWatcher>();
            _processedFiles = new HashSet<string>();
            _isRunning = false;
            _status = WatcherStatus.Paused;
        }

        /// <summary>
        /// 启动文件监控
        /// </summary>
        public bool Start()
        {
            lock (_lockObj)
            {
                if (_isRunning)
                {
                    _logService.Warn("FileWatcherService", "文件监控已在运行中");
                    return false;
                }

                try
                {
                    var config = _configService.GetConfig();

                    if (config.Watcher.WatchPaths == null || config.Watcher.WatchPaths.Count == 0)
                    {
                        _logService.Error("FileWatcherService", "未配置监控路径");
                        OnWatcherError?.Invoke("未配置监控路径");
                        return false;
                    }

                    // 启动FileSystemWatcher监听
                    foreach (var path in config.Watcher.WatchPaths)
                    {
                        if (!AddWatchPath(path))
                        {
                            _logService.Warn("FileWatcherService", $"添加监控路径失败: {path}");
                        }
                    }

                    // 启动定时轮询（作为补充）
                    int scanInterval = config.Watcher.ScanInterval * 1000;
                    _scanTimer = new Timer(ScanCallback, null, scanInterval, scanInterval);

                    _isRunning = true;
                    SetStatus(WatcherStatus.Running);
                    _logService.Info("FileWatcherService", $"文件监控已启动，监控{_watchers.Count}个路径");
                    return true;
                }
                catch (Exception ex)
                {
                    _logService.Error("FileWatcherService", "启动文件监控失败", ex);
                    SetStatus(WatcherStatus.Error);
                    OnWatcherError?.Invoke($"启动失败: {ex.Message}");
                    return false;
                }
            }
        }

        /// <summary>
        /// 停止文件监控
        /// </summary>
        public void Stop()
        {
            lock (_lockObj)
            {
                if (!_isRunning)
                {
                    return;
                }

                // 停止定时器
                _scanTimer?.Dispose();
                _scanTimer = null;

                // 停止所有FileSystemWatcher
                foreach (var watcher in _watchers.Values)
                {
                    watcher.EnableRaisingEvents = false;
                    watcher.Dispose();
                }
                _watchers.Clear();

                _isRunning = false;
                SetStatus(WatcherStatus.Paused);
                _logService.Info("FileWatcherService", "文件监控已停止");
            }
        }

        /// <summary>
        /// 添加监控路径
        /// </summary>
        public bool AddWatchPath(string path)
        {
            try
            {
                if (!Directory.Exists(path))
                {
                    _logService.Error("FileWatcherService", $"目录不存在: {path}");
                    return false;
                }

                if (_watchers.ContainsKey(path))
                {
                    _logService.Warn("FileWatcherService", $"路径已在监控中: {path}");
                    return false;
                }

                var watcher = new FileSystemWatcher(path)
                {
                    NotifyFilter = NotifyFilters.FileName | NotifyFilters.LastWrite | NotifyFilters.Size,
                    Filter = "*.*",
                    IncludeSubdirectories = false
                };

                watcher.Created += OnFileCreated;
                watcher.Error += OnFileSystemWatcherError;
                watcher.EnableRaisingEvents = true;

                _watchers[path] = watcher;
                _logService.Info("FileWatcherService", $"添加监控路径: {path}");
                return true;
            }
            catch (Exception ex)
            {
                _logService.Error("FileWatcherService", $"添加监控路径失败: {path}", ex);
                return false;
            }
        }

        /// <summary>
        /// 移除监控路径
        /// </summary>
        public bool RemoveWatchPath(string path)
        {
            lock (_lockObj)
            {
                if (_watchers.TryGetValue(path, out var watcher))
                {
                    watcher.EnableRaisingEvents = false;
                    watcher.Dispose();
                    _watchers.Remove(path);
                    _logService.Info("FileWatcherService", $"移除监控路径: {path}");
                    return true;
                }
                return false;
            }
        }

        /// <summary>
        /// FileSystemWatcher文件创建事件处理
        /// </summary>
        private void OnFileCreated(object sender, FileSystemEventArgs e)
        {
            Task.Run(() => ProcessFile(e.FullPath));
        }

        /// <summary>
        /// FileSystemWatcher错误事件处理
        /// </summary>
        private void OnFileSystemWatcherError(object sender, ErrorEventArgs e)
        {
            string errorMsg = $"文件监控错误: {e.GetException()?.Message}";
            _logService.Error("FileWatcherService", errorMsg, e.GetException());
            SetStatus(WatcherStatus.Error);
            OnWatcherError?.Invoke(errorMsg);
        }

        /// <summary>
        /// 定时扫描回调
        /// </summary>
        private void ScanCallback(object state)
        {
            Task.Run(() => ScanAllDirectories());
        }

        /// <summary>
        /// 扫描所有监控目录
        /// </summary>
        private void ScanAllDirectories()
        {
            try
            {
                var config = _configService.GetConfig();
                foreach (var path in config.Watcher.WatchPaths)
                {
                    if (!Directory.Exists(path))
                        continue;

                    var files = Directory.GetFiles(path);
                    foreach (var file in files)
                    {
                        ProcessFile(file);
                    }
                }
            }
            catch (Exception ex)
            {
                _logService.Error("FileWatcherService", "扫描目录失败", ex);
            }
        }

        /// <summary>
        /// 处理文件
        /// </summary>
        private void ProcessFile(string filePath)
        {
            try
            {
                var config = _configService.GetConfig();

                // 检查文件是否存在
                if (!FileHelper.FileExists(filePath))
                    return;

                // 检查是否为临时文件
                if (FileHelper.IsTempFile(filePath))
                {
                    _logService.Debug("FileWatcherService", $"跳过临时文件: {filePath}");
                    return;
                }

                // 检查文件扩展名
                if (!FileHelper.IsAllowedExtension(filePath, config.Watcher.AllowedExtensions.ToArray()))
                {
                    _logService.Debug("FileWatcherService", $"文件类型不支持: {filePath}");
                    return;
                }

                // 检查文件大小
                if (!FileHelper.IsFileSizeValid(filePath, config.Watcher.MinFileSize, config.Watcher.MaxFileSize))
                {
                    _logService.Debug("FileWatcherService", $"文件大小不符合要求: {filePath}");
                    return;
                }

                // 等待文件稳定
                if (!FileHelper.IsFileStable(filePath, config.Watcher.FileStableTime))
                {
                    _logService.Debug("FileWatcherService", $"文件未稳定: {filePath}");
                    return;
                }

                // 计算MD5
                string md5 = Md5Helper.ComputeFileMd5(filePath);
                if (string.IsNullOrEmpty(md5))
                {
                    _logService.Error("FileWatcherService", $"计算MD5失败: {filePath}");
                    return;
                }

                // 检查是否已处理过
                lock (_processedFiles)
                {
                    if (_processedFiles.Contains(md5))
                    {
                        _logService.Debug("FileWatcherService", $"文件已处理过(MD5重复): {filePath}");
                        return;
                    }

                    // 添加到已处理集合
                    _processedFiles.Add(md5);

                    // 限制集合大小，避免内存溢出
                    if (_processedFiles.Count > 10000)
                    {
                        // 清理一半旧记录
                        var toRemove = _processedFiles.Take(5000).ToList();
                        foreach (var item in toRemove)
                        {
                            _processedFiles.Remove(item);
                        }
                    }
                }

                // 触发文件检测事件
                _logService.Info("FileWatcherService", $"检测到新文件: {Path.GetFileName(filePath)} (MD5: {md5})");
                OnFileDetected?.Invoke(filePath);
            }
            catch (Exception ex)
            {
                _logService.Error("FileWatcherService", $"处理文件失败: {filePath}", ex);
            }
        }

        /// <summary>
        /// 设置监控状态
        /// </summary>
        private void SetStatus(WatcherStatus status)
        {
            if (_status != status)
            {
                _status = status;
                OnStatusChanged?.Invoke(status);
            }
        }

        /// <summary>
        /// 获取当前状态
        /// </summary>
        public WatcherStatus GetStatus()
        {
            return _status;
        }

        /// <summary>
        /// 检查是否运行中
        /// </summary>
        public bool IsRunning()
        {
            return _isRunning;
        }

        /// <summary>
        /// 获取监控路径列表
        /// </summary>
        public List<string> GetWatchPaths()
        {
            lock (_lockObj)
            {
                return new List<string>(_watchers.Keys);
            }
        }

        /// <summary>
        /// 清空已处理文件记录
        /// </summary>
        public void ClearProcessedFiles()
        {
            lock (_processedFiles)
            {
                _processedFiles.Clear();
                _logService.Info("FileWatcherService", "已清空已处理文件记录");
            }
        }
    }
}
