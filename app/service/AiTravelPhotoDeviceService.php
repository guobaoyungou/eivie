<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoDevice;
use think\exception\ValidateException;
use think\facade\Cache;

/**
 * AI旅拍-设备管理服务
 * Class AiTravelPhotoDeviceService
 * @package app\service
 */
class AiTravelPhotoDeviceService
{
    /**
     * 设备注册
     * @param array $data 注册数据
     * @return array
     * @throws \Exception
     */
    public function register(array $data): array
    {
        // 检查设备是否已注册
        $device = AiTravelPhotoDevice::where('device_id', $data['device_id'])->find();
        if ($device) {
            // 已注册，返回现有Token
            return [
                'device_token' => $device->device_token,
                'device_id' => $device->device_id,
                'status' => 'exists'
            ];
        }
        
        // 生成设备Token
        $deviceToken = AiTravelPhotoDevice::generateDeviceToken();
        
        // 创建设备记录
        $device = AiTravelPhotoDevice::create([
            'aid' => $data['aid'],
            'bid' => $data['bid'],
            'mdid' => $data['mdid'] ?? 0,
            'device_name' => $data['device_name'] ?? '',
            'device_id' => $data['device_id'],
            'device_token' => $deviceToken,
            'os_version' => $data['os_version'] ?? '',
            'client_version' => $data['client_version'] ?? '',
            'pc_name' => $data['pc_name'] ?? '',
            'cpu_info' => $data['cpu_info'] ?? '',
            'memory_size' => $data['memory_size'] ?? '',
            'disk_info' => $data['disk_info'] ?? '',
            'ip' => $data['ip'] ?? '',
            'status' => AiTravelPhotoDevice::STATUS_ONLINE,
            'last_online_time' => time(),
        ]);
        
        return [
            'device_token' => $device->device_token,
            'device_id' => $device->device_id,
            'status' => 'registered'
        ];
    }
    
    /**
     * 设备心跳
     * @param string $deviceToken 设备Token
     * @param array $data 心跳数据
     * @return bool
     */
    public function heartbeat(string $deviceToken, array $data = []): bool
    {
        $device = $this->getDeviceByToken($deviceToken);
        if (!$device) {
            return false;
        }
        
        // 更新设备信息
        $updateData = [
            'last_online_time' => time(),
            'status' => AiTravelPhotoDevice::STATUS_ONLINE,
        ];
        
        // 更新IP地址
        if (!empty($data['ip'])) {
            $updateData['ip'] = $data['ip'];
        }
        
        // 更新客户端版本
        if (!empty($data['client_version'])) {
            $updateData['client_version'] = $data['client_version'];
        }
        
        $device->save($updateData);
        
        // 更新缓存
        $this->cacheDeviceInfo($device);
        
        return true;
    }
    
    /**
     * 验证设备Token
     * @param string $deviceToken 设备Token
     * @return array|null
     */
    public function verifyToken(string $deviceToken): ?array
    {
        // 先从缓存获取
        $cacheKey = 'device_token:' . $deviceToken;
        $deviceInfo = Cache::get($cacheKey);
        
        if ($deviceInfo) {
            return $deviceInfo;
        }
        
        // 从数据库查询
        $device = $this->getDeviceByToken($deviceToken);
        if (!$device) {
            return null;
        }
        
        // 检查设备状态
        if ($device->status == AiTravelPhotoDevice::STATUS_ABNORMAL) {
            return null;
        }
        
        $deviceInfo = [
            'id' => $device->id,
            'aid' => $device->aid,
            'bid' => $device->bid,
            'mdid' => $device->mdid,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
        ];
        
        // 缓存设备信息（5分钟）
        Cache::set($cacheKey, $deviceInfo, 300);
        
        return $deviceInfo;
    }
    
    /**
     * 获取设备配置
     * @param string $deviceToken 设备Token
     * @return array
     */
    public function getConfig(string $deviceToken): array
    {
        $device = $this->getDeviceByToken($deviceToken);
        if (!$device) {
            throw new ValidateException('设备不存在');
        }
        
        // 获取商家AI配置
        $business = \app\model\Business::find($device->bid);
        if (!$business) {
            throw new ValidateException('商家不存在');
        }
        
        return [
            'upload' => [
                'api_url' => '/api/portrait/upload',
                'max_size' => config('ai_travel_photo.image.max_size'),
                'allowed_extensions' => config('ai_travel_photo.image.allowed_extensions'),
                'concurrent' => 3, // 上传并发数
            ],
            'auto_cutout' => $business->ai_auto_cutout ?? 1,
            'auto_generate_video' => $business->ai_auto_generate_video ?? 1,
            'max_scenes' => $business->ai_max_scenes ?? 10,
            'video_duration' => $business->ai_video_duration ?? 5,
            'heartbeat_interval' => 60, // 心跳间隔（秒）
        ];
    }
    
    /**
     * 更新上传统计
     * @param int $deviceId 设备ID
     * @param bool $success 是否成功
     * @return void
     */
    public function updateUploadStats(int $deviceId, bool $success = true): void
    {
        $device = AiTravelPhotoDevice::find($deviceId);
        if (!$device) {
            return;
        }
        
        if ($success) {
            $device->success_count += 1;
        } else {
            $device->fail_count += 1;
        }
        
        $device->upload_count += 1;
        $device->last_upload_time = time();
        $device->save();
    }
    
    /**
     * 检查设备离线状态
     * @return int 标记为离线的设备数
     */
    public function checkOfflineDevices(): int
    {
        $offlineThreshold = time() - 300; // 5分钟未心跳视为离线
        
        $count = AiTravelPhotoDevice::where('status', AiTravelPhotoDevice::STATUS_ONLINE)
            ->where('last_online_time', '<', $offlineThreshold)
            ->update(['status' => AiTravelPhotoDevice::STATUS_OFFLINE]);
        
        return $count;
    }
    
    /**
     * 获取设备列表
     * @param array $params 查询参数
     * @return array
     */
    public function getDeviceList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        
        $query = AiTravelPhotoDevice::withSearch(['bid', 'status', 'device_id'], $params);
        
        // 排序
        $query->order('last_online_time', 'desc');
        
        $list = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
        
        return [
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'page_size' => $pageSize,
        ];
    }
    
    /**
     * 获取设备详情
     * @param int $deviceId 设备ID
     * @return array
     */
    public function getDeviceDetail(int $deviceId): array
    {
        $device = AiTravelPhotoDevice::find($deviceId);
        if (!$device) {
            throw new ValidateException('设备不存在');
        }
        
        // 计算成功率
        $successRate = 0;
        if ($device->upload_count > 0) {
            $successRate = round($device->success_count / $device->upload_count * 100, 2);
        }
        
        return [
            'id' => $device->id,
            'device_name' => $device->device_name,
            'device_id' => $device->device_id,
            'bid' => $device->bid,
            'mdid' => $device->mdid,
            'os_version' => $device->os_version,
            'client_version' => $device->client_version,
            'pc_name' => $device->pc_name,
            'cpu_info' => $device->cpu_info,
            'memory_size' => $device->memory_size,
            'disk_info' => $device->disk_info,
            'ip' => $device->ip,
            'status' => $device->status,
            'status_text' => $device->status_text,
            'upload_count' => $device->upload_count,
            'success_count' => $device->success_count,
            'fail_count' => $device->fail_count,
            'success_rate' => $successRate,
            'last_upload_time' => $device->last_upload_time,
            'last_online_time' => $device->last_online_time,
            'is_online' => $device->isOnline(),
            'create_time' => $device->create_time,
        ];
    }
    
    /**
     * 根据Token获取设备
     * @param string $deviceToken
     * @return AiTravelPhotoDevice|null
     */
    private function getDeviceByToken(string $deviceToken): ?AiTravelPhotoDevice
    {
        return AiTravelPhotoDevice::where('device_token', $deviceToken)->find();
    }
    
    /**
     * 缓存设备信息
     * @param AiTravelPhotoDevice $device
     * @return void
     */
    private function cacheDeviceInfo(AiTravelPhotoDevice $device): void
    {
        $cacheKey = 'device_token:' . $device->device_token;
        $deviceInfo = [
            'id' => $device->id,
            'aid' => $device->aid,
            'bid' => $device->bid,
            'mdid' => $device->mdid,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
        ];
        Cache::set($cacheKey, $deviceInfo, 300);
    }
    
    /**
     * 删除设备
     * @param int $deviceId 设备ID
     * @return bool
     */
    public function deleteDevice(int $deviceId): bool
    {
        $device = AiTravelPhotoDevice::find($deviceId);
        if (!$device) {
            throw new ValidateException('设备不存在');
        }
        
        // 删除缓存
        $cacheKey = 'device_token:' . $device->device_token;
        Cache::delete($cacheKey);
        
        // 删除设备
        return $device->delete();
    }
}
