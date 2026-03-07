using System;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Threading.Tasks;
using AiTravelClient.Models;
using Newtonsoft.Json;

namespace AiTravelClient.Services
{
    /// <summary>
    /// API客户端服务
    /// 封装所有与服务器的HTTP通信
    /// </summary>
    public class ApiClient
    {
        private readonly HttpClient _httpClient;
        private readonly string _baseUrl;
        private string _deviceToken;

        public ApiClient(string baseUrl, int timeout = 120)
        {
            _baseUrl = baseUrl.TrimEnd('/');
            _httpClient = new HttpClient
            {
                Timeout = TimeSpan.FromSeconds(timeout)
            };
            _httpClient.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
        }

        /// <summary>
        /// 设置设备Token
        /// </summary>
        public void SetDeviceToken(string token)
        {
            _deviceToken = token;
            if (!string.IsNullOrEmpty(token))
            {
                if (_httpClient.DefaultRequestHeaders.Contains("Device-Token"))
                {
                    _httpClient.DefaultRequestHeaders.Remove("Device-Token");
                }
                _httpClient.DefaultRequestHeaders.Add("Device-Token", token);
            }
        }

        /// <summary>
        /// 设备注册
        /// </summary>
        public async Task<ApiResponse<DeviceRegisterResponse>> RegisterDeviceAsync(DeviceRegisterRequest request)
        {
            try
            {
                string url = $"{_baseUrl}/api/ai_travel_photo/device/register";
                string json = JsonConvert.SerializeObject(request);
                var content = new StringContent(json, Encoding.UTF8, "application/json");

                var response = await _httpClient.PostAsync(url, content);
                string responseBody = await response.Content.ReadAsStringAsync();

                return JsonConvert.DeserializeObject<ApiResponse<DeviceRegisterResponse>>(responseBody);
            }
            catch (Exception ex)
            {
                return new ApiResponse<DeviceRegisterResponse>
                {
                    Code = -1,
                    Msg = $"请求失败: {ex.Message}",
                    Data = null
                };
            }
        }

        /// <summary>
        /// 发送心跳
        /// </summary>
        public async Task<ApiResponse<object>> HeartbeatAsync()
        {
            try
            {
                string url = $"{_baseUrl}/api/ai_travel_photo/device/heartbeat";
                var data = new
                {
                    device_token = _deviceToken,
                    timestamp = DateTimeOffset.Now.ToUnixTimeSeconds(),
                    client_version = "1.0.0",
                    ip = Utils.SystemInfoHelper.GetLocalIpAddress()
                };

                string json = JsonConvert.SerializeObject(data);
                var content = new StringContent(json, Encoding.UTF8, "application/json");

                var response = await _httpClient.PostAsync(url, content);
                string responseBody = await response.Content.ReadAsStringAsync();

                return JsonConvert.DeserializeObject<ApiResponse<object>>(responseBody);
            }
            catch (Exception ex)
            {
                return new ApiResponse<object>
                {
                    Code = -1,
                    Msg = $"心跳失败: {ex.Message}",
                    Data = null
                };
            }
        }

        /// <summary>
        /// 获取商家AI配置
        /// </summary>
        public async Task<ApiResponse<object>> GetConfigAsync()
        {
            try
            {
                string url = $"{_baseUrl}/api/ai_travel_photo/device/config";
                var response = await _httpClient.GetAsync(url);
                string responseBody = await response.Content.ReadAsStringAsync();

                return JsonConvert.DeserializeObject<ApiResponse<object>>(responseBody);
            }
            catch (Exception ex)
            {
                return new ApiResponse<object>
                {
                    Code = -1,
                    Msg = $"获取配置失败: {ex.Message}",
                    Data = null
                };
            }
        }

        /// <summary>
        /// 上传文件
        /// </summary>
        public async Task<FileUploadResponse> UploadFileAsync(string filePath, string md5, long fileSize)
        {
            try
            {
                string url = $"{_baseUrl}/api/ai_travel_photo/device/upload";

                using (var formData = new MultipartFormDataContent())
                {
                    // 添加文件
                    byte[] fileBytes = System.IO.File.ReadAllBytes(filePath);
                    var fileContent = new ByteArrayContent(fileBytes);
                    fileContent.Headers.ContentType = MediaTypeHeaderValue.Parse("image/jpeg");
                    formData.Add(fileContent, "file", System.IO.Path.GetFileName(filePath));

                    // 添加其他参数
                    formData.Add(new StringContent(md5), "md5");
                    formData.Add(new StringContent(fileSize.ToString()), "file_size");

                    var response = await _httpClient.PostAsync(url, formData);
                    string responseBody = await response.Content.ReadAsStringAsync();

                    return JsonConvert.DeserializeObject<FileUploadResponse>(responseBody);
                }
            }
            catch (Exception ex)
            {
                return new FileUploadResponse
                {
                    Code = -1,
                    Msg = $"上传失败: {ex.Message}",
                    Data = null
                };
            }
        }

        /// <summary>
        /// 获取设备详细信息
        /// </summary>
        public async Task<ApiResponse<DeviceInfo>> GetDeviceInfoAsync()
        {
            try
            {
                string url = $"{_baseUrl}/api/ai_travel_photo/device/info";
                var response = await _httpClient.GetAsync(url);
                string responseBody = await response.Content.ReadAsStringAsync();

                return JsonConvert.DeserializeObject<ApiResponse<DeviceInfo>>(responseBody);
            }
            catch (Exception ex)
            {
                return new ApiResponse<DeviceInfo>
                {
                    Code = -1,
                    Msg = $"获取设备信息失败: {ex.Message}",
                    Data = null
                };
            }
        }

        /// <summary>
        /// 测试连接
        /// </summary>
        public async Task<bool> TestConnectionAsync()
        {
            try
            {
                string url = $"{_baseUrl}/api/ai_travel_photo/device/ping";
                var response = await _httpClient.GetAsync(url);
                return response.IsSuccessStatusCode;
            }
            catch
            {
                return false;
            }
        }

        /// <summary>
        /// 释放资源
        /// </summary>
        public void Dispose()
        {
            _httpClient?.Dispose();
        }
    }
}
