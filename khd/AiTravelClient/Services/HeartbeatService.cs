using System;
using System.Threading;
using System.Threading.Tasks;
using AiTravelClient.Models;

namespace AiTravelClient.Services
{
    /// <summary>
    /// 心跳服务
    /// 定期向服务器发送心跳包，保持设备在线状态
    /// </summary>
    public class HeartbeatService
    {
        private readonly ApiClient _apiClient;
        private readonly ConfigService _configService;
        private readonly LogService _logService;
        private Timer _heartbeatTimer;
        private bool _isRunning;
        private int _failedCount;
        private DateTime _lastSuccessTime;
        private readonly object _lockObj = new object();

        /// <summary>
        /// 心跳成功事件
        /// </summary>
        public event Action OnHeartbeatSuccess;

        /// <summary>
        /// 心跳失败事件
        /// </summary>
        public event Action<string> OnHeartbeatFailed;

        /// <summary>
        /// 连续失败超过阈值事件
        /// </summary>
        public event Action<int> OnHeartbeatAlert;

        public HeartbeatService(ApiClient apiClient, ConfigService configService, LogService logService)
        {
            _apiClient = apiClient;
            _configService = configService;
            _logService = logService;
            _isRunning = false;
            _failedCount = 0;
            _lastSuccessTime = DateTime.MinValue;
        }

        /// <summary>
        /// 启动心跳服务
        /// </summary>
        public void Start()
        {
            lock (_lockObj)
            {
                if (_isRunning)
                {
                    _logService.Warn("HeartbeatService", "心跳服务已在运行中");
                    return;
                }

                var config = _configService.GetConfig();
                int interval = config.Heartbeat.Interval * 1000; // 转换为毫秒

                _heartbeatTimer = new Timer(HeartbeatCallback, null, 0, interval);
                _isRunning = true;
                _failedCount = 0;

                _logService.Info("HeartbeatService", $"心跳服务已启动，间隔: {config.Heartbeat.Interval}秒");
            }
        }

        /// <summary>
        /// 停止心跳服务
        /// </summary>
        public void Stop()
        {
            lock (_lockObj)
            {
                if (!_isRunning)
                {
                    return;
                }

                _heartbeatTimer?.Dispose();
                _heartbeatTimer = null;
                _isRunning = false;

                _logService.Info("HeartbeatService", "心跳服务已停止");
            }
        }

        /// <summary>
        /// 心跳定时回调
        /// </summary>
        private async void HeartbeatCallback(object state)
        {
            await SendHeartbeatAsync();
        }

        /// <summary>
        /// 发送心跳
        /// </summary>
        public async Task<bool> SendHeartbeatAsync()
        {
            try
            {
                _logService.Debug("HeartbeatService", "发送心跳...");

                var response = await _apiClient.HeartbeatAsync();

                if (response.IsSuccess)
                {
                    // 心跳成功
                    _failedCount = 0;
                    _lastSuccessTime = DateTime.Now;

                    _logService.Info("HeartbeatService", "心跳发送成功");
                    OnHeartbeatSuccess?.Invoke();
                    return true;
                }
                else
                {
                    // 心跳失败
                    _failedCount++;
                    string errorMsg = $"心跳发送失败: {response.Msg}，失败次数: {_failedCount}";
                    _logService.Warn("HeartbeatService", errorMsg);
                    OnHeartbeatFailed?.Invoke(errorMsg);

                    // 检查失败次数
                    CheckFailedCount();
                    return false;
                }
            }
            catch (Exception ex)
            {
                _failedCount++;
                string errorMsg = $"心跳发送异常: {ex.Message}，失败次数: {_failedCount}";
                _logService.Error("HeartbeatService", errorMsg, ex);
                OnHeartbeatFailed?.Invoke(errorMsg);

                // 检查失败次数
                CheckFailedCount();
                return false;
            }
        }

        /// <summary>
        /// 检查失败次数并触发告警
        /// </summary>
        private void CheckFailedCount()
        {
            const int AlertThreshold = 3; // 连续失败3次触发告警

            if (_failedCount >= AlertThreshold)
            {
                _logService.Error("HeartbeatService", $"心跳连续失败{_failedCount}次，请检查网络连接");
                OnHeartbeatAlert?.Invoke(_failedCount);
            }
        }

        /// <summary>
        /// 获取心跳状态
        /// </summary>
        public HeartbeatStatus GetStatus()
        {
            return new HeartbeatStatus
            {
                IsRunning = _isRunning,
                FailedCount = _failedCount,
                LastSuccessTime = _lastSuccessTime,
                IsHealthy = _failedCount < 3
            };
        }

        /// <summary>
        /// 手动触发一次心跳（用于测试）
        /// </summary>
        public async Task<bool> TriggerHeartbeatAsync()
        {
            _logService.Info("HeartbeatService", "手动触发心跳");
            return await SendHeartbeatAsync();
        }

        /// <summary>
        /// 重置失败计数
        /// </summary>
        public void ResetFailedCount()
        {
            lock (_lockObj)
            {
                _failedCount = 0;
                _logService.Info("HeartbeatService", "失败计数已重置");
            }
        }

        /// <summary>
        /// 检查是否运行中
        /// </summary>
        public bool IsRunning()
        {
            return _isRunning;
        }

        /// <summary>
        /// 获取上次成功时间
        /// </summary>
        public DateTime GetLastSuccessTime()
        {
            return _lastSuccessTime;
        }

        /// <summary>
        /// 获取失败次数
        /// </summary>
        public int GetFailedCount()
        {
            return _failedCount;
        }
    }

    /// <summary>
    /// 心跳状态信息
    /// </summary>
    public class HeartbeatStatus
    {
        /// <summary>
        /// 是否运行中
        /// </summary>
        public bool IsRunning { get; set; }

        /// <summary>
        /// 失败次数
        /// </summary>
        public int FailedCount { get; set; }

        /// <summary>
        /// 最后成功时间
        /// </summary>
        public DateTime LastSuccessTime { get; set; }

        /// <summary>
        /// 是否健康
        /// </summary>
        public bool IsHealthy { get; set; }

        /// <summary>
        /// 获取状态描述
        /// </summary>
        public string GetStatusDescription()
        {
            if (!IsRunning)
                return "未启动";

            if (IsHealthy)
                return "正常";

            return $"异常(连续失败{FailedCount}次)";
        }
    }
}
