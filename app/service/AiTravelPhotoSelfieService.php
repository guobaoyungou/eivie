<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoSelfieQrcode;
use app\model\AiTravelPhotoSelfieNotify;
use app\model\AiTravelPhotoPortrait;
use app\model\AiTravelPhotoQrcode;
use app\model\AiTravelPhotoResult;
use app\common\Wechat;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * AI旅拍-用户自拍端服务
 * 
 * 提供二维码生成、推文配置、人脸比对路由、合成进度查询、通知推送等功能
 * 
 * Class AiTravelPhotoSelfieService
 * @package app\service
 */
class AiTravelPhotoSelfieService
{
    /**
     * 生成或获取门店永久带参二维码
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return array
     */
    public function getOrCreateQrcode(int $aid, int $bid, int $mdid): array
    {
        // 检查是否已有二维码
        $existing = AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->find();

        if ($existing && $existing->status == AiTravelPhotoSelfieQrcode::STATUS_NORMAL) {
            return [
                'id' => $existing->id,
                'qrcode_url' => $existing->qrcode_url,
                'scene_str' => $existing->scene_str,
                'create_time' => date('Y-m-d H:i:s', $existing->create_time),
                'status' => 'exists',
            ];
        }

        // 构建场景值
        $sceneStr = "selfie_bid_{$bid}_mdid_{$mdid}";

        // 调用微信公众号永久二维码API
        $accessToken = Wechat::access_token($aid, 'mp');
        if (!$accessToken) {
            throw new \Exception('公众号access_token获取失败，请检查公众号配置');
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $accessToken;
        $postData = json_encode([
            'action_name' => 'QR_LIMIT_STR_SCENE',
            'action_info' => [
                'scene' => ['scene_str' => $sceneStr]
            ]
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($result, true);
        if (!$res || empty($res['url'])) {
            $errMsg = $res['errmsg'] ?? '未知错误';
            Log::error('自拍端二维码生成失败', ['error' => $errMsg, 'aid' => $aid, 'bid' => $bid, 'mdid' => $mdid]);
            throw new \Exception('生成二维码失败: ' . $errMsg);
        }

        // 用二维码URL生成图片（复用现有createqrcode函数）
        $qrcodeUrl = createqrcode($res['url'], '', $aid);

        // 保存记录
        if ($existing) {
            $existing->scene_str = $sceneStr;
            $existing->qrcode_url = $qrcodeUrl;
            $existing->status = AiTravelPhotoSelfieQrcode::STATUS_NORMAL;
            $existing->save();
            $record = $existing;
        } else {
            $record = AiTravelPhotoSelfieQrcode::create([
                'aid' => $aid,
                'bid' => $bid,
                'mdid' => $mdid,
                'scene_str' => $sceneStr,
                'qrcode_url' => $qrcodeUrl,
                'status' => AiTravelPhotoSelfieQrcode::STATUS_NORMAL,
            ]);
        }

        return [
            'id' => $record->id,
            'qrcode_url' => $qrcodeUrl,
            'scene_str' => $sceneStr,
            'create_time' => date('Y-m-d H:i:s', $record->create_time),
            'status' => 'created',
        ];
    }

    /**
     * 获取推文配置
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return array
     */
    public function getPushConfig(int $aid, int $bid, int $mdid): array
    {
        // 优先从selfie_qrcode表获取配置
        $qrRecord = AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->find();

        // 其次从门店表获取
        $mendian = Db::name('mendian')->where('id', $mdid)->find();

        $title = '';
        $desc = '';
        $cover = '';

        if ($qrRecord) {
            $title = $qrRecord->push_title;
            $desc = $qrRecord->push_desc;
            $cover = $qrRecord->push_cover;
        }

        if (empty($title) && $mendian) {
            $title = $mendian['selfie_push_title'] ?? '';
        }
        if (empty($desc) && $mendian) {
            $desc = $mendian['selfie_push_desc'] ?? '';
        }
        if (empty($cover) && $mendian) {
            $cover = $mendian['selfie_push_cover'] ?? '';
        }

        return [
            'push_title' => $title ?: AiTravelPhotoSelfieQrcode::DEFAULT_PUSH_TITLE,
            'push_desc' => $desc ?: AiTravelPhotoSelfieQrcode::DEFAULT_PUSH_DESC,
            'push_cover' => $cover,
            'mdid' => $mdid,
        ];
    }

    /**
     * 保存推文配置
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param array $config 配置数据
     * @return bool
     */
    public function savePushConfig(int $aid, int $bid, int $mdid, array $config): bool
    {
        // 更新selfie_qrcode表
        $qrRecord = AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->find();

        if ($qrRecord) {
            $qrRecord->push_title = $config['push_title'] ?? '';
            $qrRecord->push_desc = $config['push_desc'] ?? '';
            $qrRecord->push_cover = $config['push_cover'] ?? '';
            $qrRecord->save();
        }

        // 同步更新门店表
        Db::name('mendian')->where('id', $mdid)->update([
            'selfie_push_title' => $config['push_title'] ?? '',
            'selfie_push_desc' => $config['push_desc'] ?? '',
            'selfie_push_cover' => $config['push_cover'] ?? '',
        ]);

        return true;
    }

    /**
     * 解析selfie_场景值参数
     *
     * @param string $eventKey 场景值字符串，如 selfie_bid_123_mdid_456
     * @return array ['bid' => int, 'mdid' => int]
     */
    public static function parseSelfieSceneStr(string $eventKey): array
    {
        $bid = 0;
        $mdid = 0;

        // 去掉qrscene_前缀（subscribe事件带此前缀）
        $key = str_replace('qrscene_', '', $eventKey);

        if (preg_match('/selfie_bid_(\d+)_mdid_(\d+)/', $key, $matches)) {
            $bid = (int)$matches[1];
            $mdid = (int)$matches[2];
        }

        return ['bid' => $bid, 'mdid' => $mdid];
    }

    /**
     * 处理扫码事件：推送自拍端推文给用户
     *
     * @param int $aid 平台ID
     * @param string $openid 用户openid
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param bool $isSubscribe 是否为关注事件
     * @return bool
     */
    public function handleScanEvent(int $aid, string $openid, int $bid, int $mdid, bool $isSubscribe = false): bool
    {
        // 检查门店是否启用自拍端
        $mendian = Db::name('mendian')->where('id', $mdid)->find();
        if (!$mendian || empty($mendian['selfie_enabled'])) {
            Log::info("自拍端：门店{$mdid}未启用自拍端，静默处理");
            return false;
        }

        // 更新二维码统计
        $qrRecord = AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->find();

        if ($qrRecord) {
            $qrRecord->scan_count = $qrRecord->scan_count + 1;
            if ($isSubscribe) {
                $qrRecord->follow_count = $qrRecord->follow_count + 1;
            }
            $qrRecord->save();
        }

        // 获取推文配置
        $pushConfig = $this->getPushConfig($aid, $bid, $mdid);

        // 构建自拍页URL
        $selfieUrl = request()->domain() . '/public/selfie/index.html?aid=' . $aid . '&bid=' . $bid . '&mdid=' . $mdid;

        // 获取封面图
        $picUrl = $pushConfig['push_cover'];
        if (empty($picUrl)) {
            $picUrl = request()->domain() . '/static/img/ai_travel_photo_selfie_cover.png';
        }

        // 通过客服消息接口推送图文链接
        $this->sendKefuNewsMessage($aid, $openid, [
            'title' => $pushConfig['push_title'],
            'description' => $pushConfig['push_desc'],
            'url' => $selfieUrl,
            'picurl' => $picUrl,
        ]);

        return true;
    }

    /**
     * 发送客服图文消息
     *
     * @param int $aid 平台ID
     * @param string $openid 用户openid
     * @param array $article 图文内容 ['title', 'description', 'url', 'picurl']
     * @return bool
     */
    public function sendKefuNewsMessage(int $aid, string $openid, array $article): bool
    {
        try {
            $accessToken = Wechat::access_token($aid, 'mp');
            if (!$accessToken) {
                Log::error('自拍端推文推送失败：access_token获取失败', ['aid' => $aid]);
                return false;
            }

            $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $accessToken;
            $data = [
                'touser' => $openid,
                'msgtype' => 'news',
                'news' => [
                    'articles' => [$article]
                ]
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            $res = json_decode($result, true);
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                Log::error('自拍端推文推送失败', ['error' => $res, 'openid' => $openid]);
                return false;
            }

            Log::info('自拍端推文推送成功', ['openid' => $openid, 'aid' => $aid]);
            return true;
        } catch (\Exception $e) {
            Log::error('自拍端推文推送异常', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 人脸特征比对：搜索门店成片库
     *
     * @param array $faceEmbedding 128维人脸特征向量
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return array|null 匹配到则返回 ['portrait_ids' => [...], 'pick_url' => '...']
     */
    public function matchFaceInStore(array $faceEmbedding, int $aid, int $bid, int $mdid): ?array
    {
        $matchedPortraitIds = [];
        $distanceThreshold = 0.20; // L2距离<=0.2，对应余弦相似度>=98%

        // 优先尝试Milvus
        try {
            $milvusService = new MilvusService();
            if ($milvusService->isHealthy()) {
                $searchResults = $milvusService->search($faceEmbedding, 50);
                if (!empty($searchResults)) {
                    foreach ($searchResults as $result) {
                        $distance = $result['distance'] ?? 999;
                        if ($distance <= $distanceThreshold) {
                            $portraitId = $result['portrait_id'] ?? ($result['id'] ?? 0);
                            if ($portraitId > 0) {
                                // 验证该人像属于当前门店
                                $portrait = Db::name('ai_travel_photo_portrait')
                                    ->where('id', $portraitId)
                                    ->where('bid', $bid)
                                    ->where('status', 1)
                                    ->find();
                                if ($portrait) {
                                    $matchedPortraitIds[] = $portraitId;
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Milvus搜索失败，使用MySQL备用', ['error' => $e->getMessage()]);
        }

        // MySQL备用方案
        if (empty($matchedPortraitIds)) {
            $matchedPortraitIds = $this->mysqlFaceMatch($faceEmbedding, $bid, $distanceThreshold);
        }

        if (empty($matchedPortraitIds)) {
            return null;
        }

        // 检查匹配的人像是否有已完成的成片
        $resultCount = AiTravelPhotoResult::whereIn('portrait_id', $matchedPortraitIds)
            ->where('status', AiTravelPhotoResult::STATUS_NORMAL)
            ->count();

        if ($resultCount == 0) {
            return null;
        }

        // 获取选片二维码（取第一个匹配的人像）
        $mainPortraitId = $matchedPortraitIds[0];
        $qrcode = AiTravelPhotoQrcode::where('portrait_id', $mainPortraitId)
            ->where('status', 1)
            ->find();

        if (!$qrcode) {
            // 自动为匹配的人像生成选片二维码
            try {
                $qrcodeService = new AiTravelPhotoQrcodeService();
                $qrResult = $qrcodeService->generateQrcode($mainPortraitId);
                $qrcodeStr = $qrResult['qrcode'] ?? '';
            } catch (\Exception $e) {
                Log::error('自动生成选片二维码失败', ['portrait_id' => $mainPortraitId, 'error' => $e->getMessage()]);
                return null;
            }
        } else {
            $qrcodeStr = $qrcode->qrcode;
        }

        $pickUrl = request()->domain() . '/public/pick/index.html?qr=' . urlencode($qrcodeStr);

        return [
            'portrait_ids' => $matchedPortraitIds,
            'pick_url' => $pickUrl,
            'result_count' => $resultCount,
        ];
    }

    /**
     * MySQL备用人脸匹配（遍历门店人像计算欧氏距离）
     *
     * @param array $embedding 查询向量
     * @param int $bid 商家ID
     * @param float $threshold L2距离阈值
     * @return array 匹配的人像ID列表
     */
    private function mysqlFaceMatch(array $embedding, int $bid, float $threshold): array
    {
        $portraits = Db::name('ai_travel_photo_portrait')
            ->where('bid', $bid)
            ->where('status', 1)
            ->where('face_embedding', '<>', '')
            ->field('id, face_embedding')
            ->select()
            ->toArray();

        $matched = [];
        foreach ($portraits as $portrait) {
            $storedEmbedding = json_decode($portrait['face_embedding'], true);
            if (!is_array($storedEmbedding) || count($storedEmbedding) < 64) {
                continue;
            }

            $distance = $this->euclideanDistance($embedding, $storedEmbedding);
            if ($distance <= $threshold) {
                $matched[] = $portrait['id'];
            }
        }

        return $matched;
    }

    /**
     * 计算欧氏距离
     */
    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0;
        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $diff = $a[$i] - $b[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    /**
     * 创建自拍人像记录并投递合成任务
     *
     * @param string $imageBase64 图片base64
     * @param array $faceEmbedding 人脸特征向量
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param string $openid 用户openid
     * @return array ['portrait_id' => int, 'estimated_seconds' => int, 'template_count' => int]
     */
    public function createSelfiePortraitAndSynthesize(
        string $imageBase64, array $faceEmbedding, int $aid, int $bid, int $mdid, string $openid
    ): array {
        // 保存图片到临时目录
        $imageData = base64_decode($imageBase64);
        if (!$imageData) {
            throw new \Exception('图片数据无效');
        }

        $dateDir = date('Ymd');
        $dir = app()->getRootPath() . 'upload/selfie/' . $dateDir . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filename = 'selfie_' . time() . '_' . mt_rand(1000, 9999) . '.jpg';
        $filepath = $dir . $filename;
        file_put_contents($filepath, $imageData);

        // 上传到OSS（如果配置了的话）
        $originalUrl = request()->domain() . '/upload/selfie/' . $dateDir . '/' . $filename;
        try {
            $ossUrl = \app\common\Pic::uploadoss($originalUrl, false, false);
            if ($ossUrl) {
                $originalUrl = $ossUrl;
            }
        } catch (\Exception $e) {
            // OSS上传失败，使用本地路径
        }

        // 创建人像记录
        $portraitId = Db::name('ai_travel_photo_portrait')->insertGetId([
            'aid' => $aid,
            'bid' => $bid,
            'mdid' => $mdid,
            'uid' => 0,
            'device_id' => 0,
            'original_url' => $originalUrl,
            'cutout_url' => $originalUrl, // 自拍不需要抠图
            'face_embedding' => json_encode($faceEmbedding),
            'type' => AiTravelPhotoPortrait::TYPE_USER,
            'source_type' => 3, // 用户自拍
            'user_openid' => $openid,
            'status' => AiTravelPhotoPortrait::STATUS_NORMAL,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        // 保存到Milvus
        try {
            $milvusService = new MilvusService();
            if ($milvusService->isHealthy()) {
                $milvusService->insert([$faceEmbedding], ['portrait_id' => $portraitId]);
            }
        } catch (\Exception $e) {
            Log::warning('Milvus插入失败', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
        }

        // 获取门店合成模板
        $synthesisService = new AiTravelPhotoSynthesisService();
        $templates = $synthesisService->getTemplateList($aid, $bid);
        $templateCount = count($templates);

        // 计算预估等待时间
        $queuePending = $this->getQueuePendingCount();
        $estimatedSeconds = $templateCount * 15 + $queuePending * 5;

        // 投递合成任务到队列
        if (!empty($templates)) {
            $portrait = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();
            try {
                \think\facade\Queue::push('app\job\ImageGenerationJob', [
                    'portrait' => $portrait,
                    'templates' => $templates,
                    'operator' => 'selfie_auto',
                ], 'ai_travel_photo_synthesis');
            } catch (\Exception $e) {
                Log::error('自拍合成任务投递失败', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
                // 降级：直接同步生成
                try {
                    $synthesisService->generate($portrait, $templates, 'selfie_auto');
                } catch (\Exception $ex) {
                    Log::error('自拍同步合成也失败', ['error' => $ex->getMessage()]);
                }
            }
        }

        // 更新二维码统计
        AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->inc('selfie_count')
            ->update([]);

        return [
            'portrait_id' => $portraitId,
            'estimated_seconds' => $estimatedSeconds,
            'template_count' => $templateCount,
        ];
    }

    /**
     * 获取队列中待处理任务数
     */
    private function getQueuePendingCount(): int
    {
        try {
            return (int)Db::name('jobs')->where('queue', 'ai_travel_photo_synthesis')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 查询合成进度
     *
     * @param int $portraitId 人像ID
     * @return array
     */
    public function getProgress(int $portraitId): array
    {
        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('id', $portraitId)
            ->find();

        if (!$portrait) {
            throw new \Exception('人像不存在');
        }

        // 查询该人像的generation记录
        $generations = Db::name('ai_travel_photo_generation')
            ->where('portrait_id', $portraitId)
            ->select()
            ->toArray();

        if (empty($generations)) {
            // 还没有generation记录，说明任务还在队列中未开始
            return [
                'status' => 0,
                'progress' => 0,
                'remaining_seconds' => 60,
                'pick_url' => '',
            ];
        }

        $total = count($generations);
        $completed = 0;
        $failed = 0;

        foreach ($generations as $gen) {
            if ($gen['status'] == 2) $completed++;
            if ($gen['status'] == 3) $failed++;
        }

        $inProgress = $total - $completed - $failed;

        if ($completed + $failed >= $total) {
            // 所有任务已完成（成功或失败）
            if ($completed > 0) {
                // 有成功的结果，查找选片URL
                $qrcode = Db::name('ai_travel_photo_qrcode')
                    ->where('portrait_id', $portraitId)
                    ->where('status', 1)
                    ->find();

                $pickUrl = '';
                if ($qrcode) {
                    $pickUrl = request()->domain() . '/public/pick/index.html?qr=' . urlencode($qrcode['qrcode']);
                }

                return [
                    'status' => 3, // 已完成
                    'progress' => 100,
                    'remaining_seconds' => 0,
                    'pick_url' => $pickUrl,
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                ];
            } else {
                return [
                    'status' => 4, // 全部失败
                    'progress' => 100,
                    'remaining_seconds' => 0,
                    'pick_url' => '',
                ];
            }
        }

        // 进行中
        $progress = (int)(($completed + $failed) / $total * 100);
        $remainingSeconds = $inProgress * 15;

        // 判断状态
        $status = 1; // 处理中
        if ($completed > 0 || $failed > 0) {
            $status = 2; // 进行中（有部分结果）
        }

        return [
            'status' => $status,
            'progress' => $progress,
            'remaining_seconds' => $remainingSeconds,
            'pick_url' => '',
            'completed' => $completed,
            'failed' => $failed,
            'total' => $total,
        ];
    }

    /**
     * 注册通知请求
     *
     * @param int $portraitId 人像ID
     * @param string $openid 用户openid
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return int 通知记录ID
     */
    public function registerNotify(int $portraitId, string $openid, int $aid, int $bid, int $mdid): int
    {
        // 去重检查
        $existing = AiTravelPhotoSelfieNotify::where('openid', $openid)
            ->where('portrait_id', $portraitId)
            ->find();

        if ($existing) {
            return $existing->id;
        }

        $record = AiTravelPhotoSelfieNotify::create([
            'aid' => $aid,
            'bid' => $bid,
            'mdid' => $mdid,
            'portrait_id' => $portraitId,
            'openid' => $openid,
            'uid' => 0,
            'notify_type' => AiTravelPhotoSelfieNotify::NOTIFY_TYPE_KEFU,
            'notify_status' => AiTravelPhotoSelfieNotify::STATUS_PENDING,
            'create_time' => time(),
        ]);

        return (int)$record->id;
    }

    /**
     * 合成完成后推送通知给注册了"找到后通知我"的用户
     *
     * @param int $portraitId 人像ID
     * @return int 成功推送数
     */
    public function sendSynthesisCompleteNotify(int $portraitId): int
    {
        $notifyRecords = AiTravelPhotoSelfieNotify::where('portrait_id', $portraitId)
            ->where('notify_status', AiTravelPhotoSelfieNotify::STATUS_PENDING)
            ->select();

        if ($notifyRecords->isEmpty()) {
            return 0;
        }

        // 获取选片页URL
        $qrcode = Db::name('ai_travel_photo_qrcode')
            ->where('portrait_id', $portraitId)
            ->where('status', 1)
            ->find();

        if (!$qrcode) {
            Log::warning('合成完成通知：未找到选片二维码', ['portrait_id' => $portraitId]);
            return 0;
        }

        $pickUrl = request()->domain() . '/public/pick/index.html?qr=' . urlencode($qrcode['qrcode']);

        // 获取门店名称
        $portrait = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();
        $storeName = '';
        if ($portrait && $portrait['mdid'] > 0) {
            $storeName = Db::name('mendian')->where('id', $portrait['mdid'])->value('name') ?: '';
        }

        $sentCount = 0;
        foreach ($notifyRecords as $record) {
            try {
                $description = '点击查看您' . ($storeName ? '在' . $storeName : '') . '的精美旅拍照片，快来选片吧！';
                
                $success = $this->sendKefuNewsMessage($record->aid, $record->openid, [
                    'title' => '您的旅拍照片已准备就绪',
                    'description' => $description,
                    'url' => $pickUrl,
                    'picurl' => request()->domain() . '/static/img/ai_travel_photo_selfie_cover.png',
                ]);

                $record->notify_status = $success ? AiTravelPhotoSelfieNotify::STATUS_SENT : AiTravelPhotoSelfieNotify::STATUS_FAILED;
                $record->notify_time = time();
                $record->pick_url = $pickUrl;
                $record->save();

                if ($success) {
                    $sentCount++;
                }
            } catch (\Exception $e) {
                Log::error('自拍通知推送失败', ['notify_id' => $record->id, 'error' => $e->getMessage()]);
                $record->notify_status = AiTravelPhotoSelfieNotify::STATUS_FAILED;
                $record->notify_time = time();
                $record->save();
            }
        }

        return $sentCount;
    }

    /**
     * 获取自拍端数据统计
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID（0=全部门店）
     * @return array
     */
    public function getStats(int $aid, int $bid, int $mdid = 0): array
    {
        // 兼容bid=0公共门店场景：查询当前商家的二维码和公共二维码
        $query = AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where(function($q) use ($bid) {
                $q->whereOr([
                    ['bid', '=', $bid],
                    ['bid', '=', 0]
                ]);
            });
        if ($mdid > 0) {
            $query->where('mdid', $mdid);
        }

        $qrStats = $query
            ->field('SUM(scan_count) as total_scan, SUM(follow_count) as total_follow, SUM(selfie_count) as total_selfie, SUM(match_count) as total_match')
            ->find();

        $totalScan = (int)($qrStats['total_scan'] ?? 0);
        $totalFollow = (int)($qrStats['total_follow'] ?? 0);
        $totalSelfie = (int)($qrStats['total_selfie'] ?? 0);
        $totalMatch = (int)($qrStats['total_match'] ?? 0);

        // 合成触发数 = 自拍数 - 命中数
        $synthesisTrigger = max(0, $totalSelfie - $totalMatch);

        // 通知统计
        $notifyQuery = AiTravelPhotoSelfieNotify::where('aid', $aid)
            ->where(function($q) use ($bid) {
                $q->whereOr([
                    ['bid', '=', $bid],
                    ['bid', '=', 0]
                ]);
            });
        if ($mdid > 0) {
            $notifyQuery->where('mdid', $mdid);
        }
        $totalNotifyRequests = (clone $notifyQuery)->count();
        $totalNotifySent = (clone $notifyQuery)
            ->where('notify_status', AiTravelPhotoSelfieNotify::STATUS_SENT)
            ->count();

        return [
            'total_scan' => $totalScan,
            'total_follow' => $totalFollow,
            'total_selfie' => $totalSelfie,
            'match_count' => $totalMatch,
            'match_rate' => $totalSelfie > 0 ? round($totalMatch / $totalSelfie * 100, 1) : 0,
            'synthesis_trigger' => $synthesisTrigger,
            'total_notify_requests' => $totalNotifyRequests,
            'total_notify_sent' => $totalNotifySent,
        ];
    }
}
