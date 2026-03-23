<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoQrcode;
use app\model\AiTravelPhotoPortrait;
use app\common\OssHelper;
use app\common\Wechat;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Log;

/**
 * AI旅拍-二维码管理服务
 * Class AiTravelPhotoQrcodeService
 * @package app\service
 */
class AiTravelPhotoQrcodeService
{
    private $ossHelper;
    
    public function __construct()
    {
        // OssHelper 采用延迟初始化，避免不需要 OSS 的方法触发 SDK 加载
    }
    
    /**
     * 获取 OssHelper 实例（延迟初始化）
     * @return OssHelper
     */
    private function getOssHelper(): OssHelper
    {
        if ($this->ossHelper === null) {
            $this->ossHelper = new OssHelper();
        }
        return $this->ossHelper;
    }
    
    /**
     * 生成二维码
     * @param int $portraitId 人像ID
     * @return array
     */
    public function generateQrcode(int $portraitId): array
    {
        $portrait = AiTravelPhotoPortrait::find($portraitId);
        if (!$portrait) {
            throw new ValidateException('人像不存在');
        }
        
        // 检查是否已有二维码
        $existQrcode = AiTravelPhotoQrcode::where('portrait_id', $portraitId)
            ->where('status', AiTravelPhotoQrcode::STATUS_VALID)
            ->find();
        
        if ($existQrcode) {
            return [
                'qrcode_id' => $existQrcode->id,
                'qrcode' => $existQrcode->qrcode,
                'qrcode_url' => $existQrcode->qrcode_url,
                'status' => 'exists'
            ];
        }
        
        // 生成唯一二维码标识
        $qrcodeStr = $this->generateUniqueQrcode();
        
        // 获取商家配置
        $business = \app\model\Business::find($portrait->bid);
        $expireDays = $business->ai_qrcode_expire_days ?? config('ai_travel_photo.qrcode.expire_days', 30);
        $expireTime = $expireDays > 0 ? time() + ($expireDays * 86400) : 0;
        
        // 创建二维码记录
        $qrcode = AiTravelPhotoQrcode::create([
            'aid' => $portrait->aid,
            'portrait_id' => $portraitId,
            'bid' => $portrait->bid,
            'qrcode' => $qrcodeStr,
            'status' => AiTravelPhotoQrcode::STATUS_VALID,
            'expire_time' => $expireTime,
        ]);
        
        // 生成二维码图片
        $qrcodeUrl = $this->generateQrcodeImage($qrcodeStr);
        
        // 更新二维码图片URL
        $qrcode->qrcode_url = $qrcodeUrl;
        $qrcode->save();
        
        return [
            'qrcode_id' => $qrcode->id,
            'qrcode' => $qrcode->qrcode,
            'qrcode_url' => $qrcode->qrcode_url,
            'expire_time' => $qrcode->expire_time,
            'status' => 'created'
        ];
    }
    
    /**
     * 生成唯一二维码标识
     * @return string
     */
    private function generateUniqueQrcode(): string
    {
        do {
            // 使用时间戳+随机数+唯一ID生成
            $qrcode = 'AITP' . date('YmdHis') . mt_rand(1000, 9999) . uniqid();
            
            // 检查是否已存在
            $exists = AiTravelPhotoQrcode::where('qrcode', $qrcode)->find();
        } while ($exists);
        
        return $qrcode;
    }
    
    /**
     * 生成二维码图片
     * @param string $qrcodeStr 二维码内容
     * @return string 二维码图片URL
     */
    private function generateQrcodeImage(string $qrcodeStr): string
    {
        // 获取配置
        $baseUrl = config('ai_travel_photo.qrcode.base_url');
        $size = config('ai_travel_photo.qrcode.size', 300);
        $margin = config('ai_travel_photo.qrcode.margin', 10);
        
        // 二维码跳转地址
        $url = $baseUrl . '?qrcode=' . $qrcodeStr;
        
        // 使用第三方库生成二维码（这里使用endroid/qr-code或phpqrcode）
        // 示例使用简单的API方式
        require_once app()->getRootPath() . 'extend/phpqrcode/phpqrcode.php';
        
        // 创建临时文件
        $tempFile = sys_get_temp_dir() . '/' . $qrcodeStr . '.png';
        
        // 生成二维码
        \QRcode::png($url, $tempFile, 'L', $size / 30, $margin);
        
        // 上传到OSS
        try {
            $ossPath = 'ai_travel_photo/qrcode/' . date('Ymd') . '/' . $qrcodeStr . '.png';
            $qrcodeUrl = $this->getOssHelper()->uploadFile($tempFile, $ossPath);
            
            // 删除临时文件
            @unlink($tempFile);
            
            return $qrcodeUrl;
        } catch (\Exception $e) {
            @unlink($tempFile);
            throw new \Exception('二维码图片上传失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取二维码详情（扫码查看）
     * @param string $qrcodeStr 二维码字符串
     * @param int $uid 用户ID（可选）
     * @return array
     */
    public function getQrcodeDetail(string $qrcodeStr, int $uid = 0): array
    {
        // 先从缓存获取
        $cacheKey = 'qrcode_detail:' . $qrcodeStr;
        $cachedData = Cache::get($cacheKey);
        
        if ($cachedData) {
            // 异步更新扫码统计
            $this->updateScanStats($cachedData['qrcode_id'], $uid);
            return $cachedData;
        }
        
        // 查询二维码
        $qrcode = AiTravelPhotoQrcode::where('qrcode', $qrcodeStr)->find();
        
        if (!$qrcode) {
            throw new ValidateException('二维码不存在');
        }
        
        // 检查状态
        if ($qrcode->status == AiTravelPhotoQrcode::STATUS_INVALID) {
            throw new ValidateException('二维码已失效');
        }
        
        // 检查是否过期
        if ($qrcode->isExpired()) {
            $qrcode->status = AiTravelPhotoQrcode::STATUS_INVALID;
            $qrcode->save();
            throw new ValidateException('二维码已过期');
        }
        
        // 获取人像信息
        $portrait = AiTravelPhotoPortrait::with(['results' => function($query) {
            $query->where('status', \app\model\AiTravelPhotoResult::STATUS_NORMAL)
                  ->order('type', 'asc');
        }])->find($qrcode->portrait_id);
        
        if (!$portrait) {
            throw new ValidateException('人像不存在');
        }
        
        // 获取商家配置
        $business = \app\model\Business::find($portrait->bid);
        
        // 获取套餐列表
        $packages = \app\model\AiTravelPhotoPackage::where('bid', $portrait->bid)
            ->where('status', \app\model\AiTravelPhotoPackage::STATUS_ENABLED)
            ->where(function($query) {
                $query->where('start_time', '=', 0)
                      ->whereOr('start_time', '<=', time());
            })
            ->where(function($query) {
                $query->where('end_time', '=', 0)
                      ->whereOr('end_time', '>=', time());
            })
            ->order('sort', 'desc')
            ->order('id', 'asc')
            ->select();
        
        // 构建返回数据
        $data = [
            'qrcode_id' => $qrcode->id,
            'portrait_info' => [
                'portrait_id' => $portrait->id,
                'shoot_time' => $portrait->shoot_time,
                'create_time' => $portrait->create_time,
            ],
            'results' => [],
            'packages' => [],
            'price_config' => [
                'photo_price' => $business->ai_photo_price ?? 9.90,
                'video_price' => $business->ai_video_price ?? 29.90,
            ],
        ];
        
        // 处理结果列表
        foreach ($portrait->results as $result) {
            $data['results'][] = [
                'result_id' => $result->id,
                'type' => $result->type,
                'type_text' => $result->type_text,
                'watermark_url' => $result->watermark_url, // 带水印预览图
                'thumbnail_url' => $result->thumbnail_url,
                'is_video' => $result->is_video,
                'video_duration' => $result->video_duration,
                'video_cover' => $result->video_cover,
                'scene_name' => $result->scene->name ?? '',
            ];
        }
        
        // 处理套餐列表
        foreach ($packages as $package) {
            $data['packages'][] = [
                'package_id' => $package->id,
                'name' => $package->name,
                'desc' => $package->desc,
                'icon' => $package->icon,
                'price' => $package->price,
                'original_price' => $package->original_price,
                'num' => $package->num,
                'video_num' => $package->video_num,
                'tag' => $package->tag,
                'tag_color' => $package->tag_color,
                'is_recommend' => $package->is_recommend,
                'discount_rate' => $package->discount_rate,
            ];
        }
        
        // 缓存数据（5分钟）
        Cache::set($cacheKey, $data, 300);
        
        // 更新扫码统计
        $this->updateScanStats($qrcode->id, $uid);
        
        return $data;
    }
    
    /**
     * 更新扫码统计
     * @param int $qrcodeId 二维码ID
     * @param int $uid 用户ID
     * @return void
     */
    private function updateScanStats(int $qrcodeId, int $uid = 0): void
    {
        // 使用队列异步更新统计
        $qrcode = AiTravelPhotoQrcode::find($qrcodeId);
        if (!$qrcode) {
            return;
        }
        
        // 更新扫码次数
        $qrcode->scan_count += 1;
        $qrcode->last_scan_time = time();
        
        if ($qrcode->first_scan_time == 0) {
            $qrcode->first_scan_time = time();
        }
        
        // 更新独立用户扫码数（使用缓存去重）
        if ($uid > 0) {
            $cacheKey = 'qrcode_scan_user:' . $qrcodeId . ':' . $uid;
            if (!Cache::has($cacheKey)) {
                $qrcode->unique_scan_count += 1;
                Cache::set($cacheKey, 1, 86400); // 缓存1天
            }
        }
        
        $qrcode->save();
    }
    
    /**
     * 检查过期二维码
     * @return int 标记为失效的二维码数
     */
    public function checkExpiredQrcodes(): int
    {
        $count = AiTravelPhotoQrcode::where('status', AiTravelPhotoQrcode::STATUS_VALID)
            ->where('expire_time', '>', 0)
            ->where('expire_time', '<', time())
            ->update(['status' => AiTravelPhotoQrcode::STATUS_INVALID]);
        
        return $count;
    }
    
    /**
     * 获取二维码列表
     * @param array $params 查询参数
     * @return array
     */
    public function getQrcodeList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        
        $query = AiTravelPhotoQrcode::withSearch(['portrait_id', 'status'], $params);
        
        // 关联人像信息
        if (!empty($params['with_portrait'])) {
            $query->with(['portrait']);
        }
        
        // 排序
        $query->order('create_time', 'desc');
        
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
     * 批量生成二维码
     * @param array $portraitIds 人像ID数组
     * @return array
     */
    public function batchGenerateQrcode(array $portraitIds): array
    {
        $result = [
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];
        
        foreach ($portraitIds as $portraitId) {
            try {
                $qrcodeData = $this->generateQrcode($portraitId);
                $result['success']++;
                $result['details'][] = [
                    'portrait_id' => $portraitId,
                    'status' => 'success',
                    'data' => $qrcodeData,
                ];
            } catch (\Exception $e) {
                $result['failed']++;
                $result['details'][] = [
                    'portrait_id' => $portraitId,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * 生成微信公众号带参数永久二维码
     * @param int $portraitId 人像ID
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @return string 公众号二维码图片URL
     */
    public function generateMpQrcode(int $portraitId, int $aid, int $bid): string
    {
        // 1. 检查是否已有 qrcode_type=2 的公众号二维码记录
        $existQrcode = AiTravelPhotoQrcode::where('portrait_id', $portraitId)
            ->where('qrcode_type', AiTravelPhotoQrcode::QRCODE_TYPE_MP)
            ->where('status', AiTravelPhotoQrcode::STATUS_VALID)
            ->find();
        
        if ($existQrcode && !empty($existQrcode->mp_qrcode_url)) {
            return $existQrcode->mp_qrcode_url;
        }
        
        // 2. 构建 scene_str，格式为 portraitId_{ID}-bid_{bid}
        $sceneStr = 'portraitId_' . $portraitId . '-bid_' . $bid;
        
        // 3. 调用 Wechat::getQRCode 生成公众号永久二维码
        try {
            $result = Wechat::getQRCode($aid, 'mp', '', $sceneStr, $bid);
            
            if (!$result || $result['status'] != 1 || empty($result['url'])) {
                Log::write('[公众号二维码生成失败] portraitId=' . $portraitId . ' result=' . json_encode($result), 'error');
                return '';
            }
            
            $mpQrcodeUrl = $result['url'];
            
            // 4. 保存公众号二维码记录
            if ($existQrcode) {
                // 更新已有记录
                $existQrcode->mp_qrcode_url = $mpQrcodeUrl;
                $existQrcode->save();
            } else {
                // 创建新记录
                AiTravelPhotoQrcode::create([
                    'aid' => $aid,
                    'portrait_id' => $portraitId,
                    'bid' => $bid,
                    'qrcode' => 'MP_' . $sceneStr,
                    'qrcode_url' => '',
                    'mp_qrcode_url' => $mpQrcodeUrl,
                    'qrcode_type' => AiTravelPhotoQrcode::QRCODE_TYPE_MP,
                    'status' => AiTravelPhotoQrcode::STATUS_VALID,
                    'expire_time' => 0, // 永久二维码
                ]);
            }
            
            return $mpQrcodeUrl;
            
        } catch (\Exception $e) {
            Log::write('[公众号二维码生成异常] portraitId=' . $portraitId . ' error=' . $e->getMessage(), 'error');
            return '';
        }
    }
    
    /**
     * 获取选片列表数据（供XPD大屏使用）
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param int $limit 返回数量上限
     * @return array
     */
    public function getSelectionList(int $aid, int $bid, int $mdid = 0, int $limit = 15): array
    {
        // 查询人像列表，按 create_time 倒序
        $query = AiTravelPhotoPortrait::where('aid', $aid)
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL);
        
        // bid>0 时按商家筛选，否则仅按门店筛选
        if ($bid > 0) {
            $query->where('bid', $bid);
        }
        if ($mdid > 0) {
            $query->where('mdid', $mdid);
        }
        
        $portraits = $query->order('create_time', 'desc')
            ->limit($limit)
            ->select();
        
        if ($portraits->isEmpty()) {
            return ['list' => [], 'config' => $this->getXpdConfig($bid, $mdid)];
        }
        
        $list = [];
        foreach ($portraits as $portrait) {
            // 查询该人像的成片
            $results = \app\model\AiTravelPhotoResult::where('portrait_id', $portrait->id)
                ->where('status', \app\model\AiTravelPhotoResult::STATUS_NORMAL)
                ->order('type', 'asc')
                ->select();
            
            if ($results->isEmpty()) {
                continue; // 跳过没有成片的人像
            }
            
            // 获取或生成选片页二维码
            $qrcodeRecord = AiTravelPhotoQrcode::where('portrait_id', $portrait->id)
                ->where('qrcode_type', AiTravelPhotoQrcode::QRCODE_TYPE_PICK)
                ->where('status', AiTravelPhotoQrcode::STATUS_VALID)
                ->find();
            $qrcodeStr = $qrcodeRecord ? $qrcodeRecord->qrcode : '';
            
            // 获取或生成公众号二维码
            $mpQrcodeUrl = $this->generateMpQrcode($portrait->id, $aid, $bid);
            
            $resultList = [];
            foreach ($results as $result) {
                $resultList[] = [
                    'id' => $result->id,
                    'type' => $result->type,
                    'watermark_url' => $result->watermark_url ?: $result->url,
                    'thumbnail_url' => $result->thumbnail_url ?: '',
                    'video_duration' => $result->video_duration ?: 0,
                ];
            }
            
            $list[] = [
                'id' => $portrait->id,
                'original_url' => $portrait->original_url,
                'thumbnail_url' => $portrait->thumbnail_url ?: $portrait->original_url,
                'create_time' => $portrait->create_time,
                'results' => $resultList,
                'mp_qrcode_url' => $mpQrcodeUrl,
                'qrcode' => $qrcodeStr,
            ];
        }
        
        return [
            'list' => $list,
            'config' => $this->getXpdConfig($bid, $mdid),
        ];
    }
    
    /**
     * 获取XPD配置
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return array
     */
    private function getXpdConfig(int $bid, int $mdid = 0): array
    {
        $mendian = null;
        // 优先按 mdid 查询门店配置
        if ($mdid > 0) {
            $mendian = \think\facade\Db::name('mendian')->where('id', $mdid)->find();
        }
        // 降级按 bid 查询第一个门店
        if (!$mendian && $bid > 0) {
            $mendian = \think\facade\Db::name('mendian')->where('bid', $bid)->order('id asc')->find();
        }
        return [
            'image_duration' => $mendian['xpd_image_duration'] ?? 1000,
            'group_duration' => $mendian['xpd_group_duration'] ?? 5000,
        ];
    }
}
