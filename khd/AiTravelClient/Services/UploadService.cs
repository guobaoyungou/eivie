using System;
using System.Collections.Concurrent;
using System.Collections.Generic;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using AiTravelClient.Models;
using AiTravelClient.Utils;

namespace AiTravelClient.Services
{
    /// <summary>
    /// 上传管理服务
    /// 管理文件上传队列，执行并发上传，处理失败重试
    /// </summary>
    public class UploadService
    {
        private readonly ApiClient _apiClient;
        private readonly ConfigService _configService;
        private readonly LogService _logService;
        private readonly ConcurrentQueue<UploadTask> _uploadQueue;
        private readonly ConcurrentDictionary<string, UploadTask> _uploadingTasks;
        private readonly List<Task> _workerTasks;
        private CancellationTokenSource _cancellationTokenSource;
        private SemaphoreSlim _semaphore;
        private bool _isRunning;
        private readonly int[] _retryDelays = { 5, 10, 20, 40, 60 }; // 重试延迟（秒）

        /// <summary>
        /// 上传成功事件
        /// </summary>
        public event Action<UploadTask> OnUploadSuccess;

        /// <summary>
        /// 上传失败事件
        /// </summary>
        public event Action<UploadTask> OnUploadFailed;

        /// <summary>
        /// 队列状态变化事件
        /// </summary>
        public event Action<UploadQueueStatus> OnQueueStatusChanged;

        public UploadService(ApiClient apiClient, ConfigService configService, LogService logService)
        {
            _apiClient = apiClient;
            _configService = configService;
            _logService = logService;
            _uploadQueue = new ConcurrentQueue<UploadTask>();
            _uploadingTasks = new ConcurrentDictionary<string, UploadTask>();
            _workerTasks = new List<Task>();
            _isRunning = false;
        }

        /// <summary>
        /// 启动上传服务
        /// </summary>
        public void Start()
        {
            if (_isRunning)
            {
                _logService.Warn("UploadService", "上传服务已在运行中");
                return;
            }

            var config = _configService.GetConfig();
            int concurrentCount = config.Upload.ConcurrentUploads;

            _cancellationTokenSource = new CancellationTokenSource();
            _semaphore = new SemaphoreSlim(concurrentCount, concurrentCount);
            _isRunning = true;

            // 启动工作线程
            for (int i = 0; i < concurrentCount; i++)
            {
                var task = Task.Run(() => UploadWorker(_cancellationTokenSource.Token));
                _workerTasks.Add(task);
            }

            _logService.Info("UploadService", $"上传服务已启动，并发数: {concurrentCount}");
        }

        /// <summary>
        /// 停止上传服务
        /// </summary>
        public async Task StopAsync()
        {
            if (!_isRunning)
            {
                return;
            }

            _isRunning = false;
            _cancellationTokenSource?.Cancel();

            // 等待所有工作线程完成
            await Task.WhenAll(_workerTasks);

            _workerTasks.Clear();
            _semaphore?.Dispose();
            _cancellationTokenSource?.Dispose();

            _logService.Info("UploadService", "上传服务已停止");
        }

        /// <summary>
        /// 添加文件到上传队列
        /// </summary>
        public string AddToQueue(string filePath)
        {
            try
            {
                var config = _configService.GetConfig();

                // 检查队列是否已满
                if (_uploadQueue.Count >= config.Upload.MaxQueueSize)
                {
                    _logService.Warn("UploadService", $"上传队列已满，无法添加: {filePath}");
                    return null;
                }

                // 计算MD5和文件大小
                string md5 = Md5Helper.ComputeFileMd5(filePath);
                long fileSize = FileHelper.GetFileSize(filePath);

                if (string.IsNullOrEmpty(md5) || fileSize == 0)
                {
                    _logService.Error("UploadService", $"无法获取文件信息: {filePath}");
                    return null;
                }

                // 创建上传任务
                var task = new UploadTask
                {
                    TaskId = Guid.NewGuid().ToString("N"),
                    FilePath = filePath,
                    FileName = System.IO.Path.GetFileName(filePath),
                    FileSize = fileSize,
                    Md5 = md5,
                    Status = UploadTaskStatus.Pending,
                    RetryCount = 0,
                    CreateTime = DateTime.Now
                };

                // 加入队列
                _uploadQueue.Enqueue(task);

                _logService.Info("UploadService", $"添加到上传队列: {task.FileName}, 队列长度: {_uploadQueue.Count}");

                // 触发状态变化事件
                NotifyQueueStatusChanged();

                return task.TaskId;
            }
            catch (Exception ex)
            {
                _logService.Error("UploadService", $"添加到队列失败: {filePath}", ex);
                return null;
            }
        }

        /// <summary>
        /// 上传工作线程
        /// </summary>
        private async Task UploadWorker(CancellationToken cancellationToken)
        {
            while (!cancellationToken.IsCancellationRequested)
            {
                try
                {
                    // 从队列获取任务
                    if (!_uploadQueue.TryDequeue(out var task))
                    {
                        await Task.Delay(1000, cancellationToken);
                        continue;
                    }

                    // 等待信号量（控制并发）
                    await _semaphore.WaitAsync(cancellationToken);

                    try
                    {
                        // 添加到正在上传的任务列表
                        _uploadingTasks.TryAdd(task.TaskId, task);

                        // 执行上传
                        await UploadFileAsync(task);
                    }
                    finally
                    {
                        // 从正在上传列表移除
                        _uploadingTasks.TryRemove(task.TaskId, out _);

                        // 释放信号量
                        _semaphore.Release();

                        // 触发状态变化事件
                        NotifyQueueStatusChanged();
                    }
                }
                catch (OperationCanceledException)
                {
                    break;
                }
                catch (Exception ex)
                {
                    _logService.Error("UploadService", "上传工作线程异常", ex);
                }
            }
        }

        /// <summary>
        /// 上传文件（含重试逻辑）
        /// </summary>
        private async Task UploadFileAsync(UploadTask task)
        {
            var config = _configService.GetConfig();
            int maxRetry = config.Upload.MaxRetry;

            task.Status = UploadTaskStatus.Uploading;
            task.StartTime = DateTime.Now;

            _logService.Info("UploadService", $"开始上传: {task.FileName}");

            for (int attempt = 0; attempt <= maxRetry; attempt++)
            {
                try
                {
                    // 检查文件是否仍然存在
                    if (!FileHelper.FileExists(task.FilePath))
                    {
                        _logService.Warn("UploadService", $"文件不存在: {task.FilePath}");
                        task.Status = UploadTaskStatus.FinalFailed;
                        task.ErrorMessage = "文件不存在";
                        OnUploadFailed?.Invoke(task);
                        return;
                    }

                    // 调用API上传
                    var response = await _apiClient.UploadFileAsync(task.FilePath, task.Md5, task.FileSize);

                    if (response.Code == 200)
                    {
                        // 上传成功
                        task.Status = UploadTaskStatus.Success;
                        task.FinishTime = DateTime.Now;
                        task.PortraitId = response.Data?.PortraitId;
                        task.IsDuplicate = response.Data?.IsDuplicate ?? false;

                        _logService.Info("UploadService", $"上传成功: {task.FileName}" +
                            (task.IsDuplicate ? " (重复文件)" : ""));

                        OnUploadSuccess?.Invoke(task);
                        return;
                    }
                    else
                    {
                        // 上传失败
                        task.ErrorMessage = response.Msg;
                        _logService.Warn("UploadService", $"上传失败: {task.FileName}, {response.Msg}");
                    }
                }
                catch (Exception ex)
                {
                    task.ErrorMessage = ex.Message;
                    _logService.Error("UploadService", $"上传异常: {task.FileName}", ex);
                }

                // 检查是否需要重试
                if (attempt < maxRetry)
                {
                    task.RetryCount++;
                    task.Status = UploadTaskStatus.Retrying;

                    int delay = _retryDelays[Math.Min(attempt, _retryDelays.Length - 1)];
                    _logService.Info("UploadService", $"将在{delay}秒后重试: {task.FileName} (第{task.RetryCount}次)");

                    await Task.Delay(delay * 1000);
                }
            }

            // 最终失败
            task.Status = UploadTaskStatus.FinalFailed;
            task.FinishTime = DateTime.Now;
            _logService.Error("UploadService", $"上传最终失败: {task.FileName}, 已重试{maxRetry}次");
            OnUploadFailed?.Invoke(task);
        }

        /// <summary>
        /// 获取队列状态
        /// </summary>
        public UploadQueueStatus GetQueueStatus()
        {
            return new UploadQueueStatus
            {
                PendingCount = _uploadQueue.Count,
                UploadingCount = _uploadingTasks.Count,
                TotalCount = _uploadQueue.Count + _uploadingTasks.Count
            };
        }

        /// <summary>
        /// 重试失败的任务
        /// </summary>
        public int RetryFailed()
        {
            // 此方法可以从外部存储中加载失败任务并重新加入队列
            // 这里简化实现，返回0
            _logService.Info("UploadService", "重试失败任务功能待实现");
            return 0;
        }

        /// <summary>
        /// 清空队列
        /// </summary>
        public void ClearQueue()
        {
            while (_uploadQueue.TryDequeue(out _)) { }
            _logService.Info("UploadService", "上传队列已清空");
            NotifyQueueStatusChanged();
        }

        /// <summary>
        /// 通知队列状态变化
        /// </summary>
        private void NotifyQueueStatusChanged()
        {
            var status = GetQueueStatus();
            OnQueueStatusChanged?.Invoke(status);
        }

        /// <summary>
        /// 检查是否运行中
        /// </summary>
        public bool IsRunning()
        {
            return _isRunning;
        }

        /// <summary>
        /// 获取待上传任务列表
        /// </summary>
        public List<UploadTask> GetPendingTasks()
        {
            return _uploadQueue.ToList();
        }

        /// <summary>
        /// 获取正在上传的任务列表
        /// </summary>
        public List<UploadTask> GetUploadingTasks()
        {
            return _uploadingTasks.Values.ToList();
        }
    }
}
