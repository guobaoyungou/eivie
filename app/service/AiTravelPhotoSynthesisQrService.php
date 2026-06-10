<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\AiTravelPhotoSynthesisActivity;
use app\model\AiTravelPhotoSynthesisUserPhoto;
use app\model\AiTravelPhotoSynthesisTemplate;
use app\model\AiTravelPhotoResult;
use app\model\AiTravelPhotoOrder;
use app\model\AiTravelPhotoOrderGoods;
use app\model\Payorder;

/**
 * AI旅拍合成活动二维码服务
 *
 * 核心编排服务：管理合成活动完整的「扫码 → 上传 → 标签 → 改写 → 生成 → 水印 → 支付 → 下载」流程
 */
class AiTravelPhotoSynthesisQrService
{
    /**
     * 创建合成活动并生成二维码
     *
     * @param int    $aid        商户ID
     * @param int    $bid        门店ID
     * @param int    $templateId 合成模板ID
     * @param string $name       活动名称
     * @param float  $price      下载单价
     * @return array
     */
    public function createActivity(int $aid, int $bid, int $templateId, string $name, float $price = 9.90): array
    {
        // 验证模板
        $template = AiTravelPhotoSynthesisTemplate::where('id', $templateId)
            ->where('aid', $aid)
            ->whereIn('bid', [0, $bid])
            ->where('status', AiTravelPhotoSynthesisTemplate::STATUS_NORMAL)
            ->find();

        if (!$template) {
            throw new \Exception('合成模板不存在或已禁用');
        }

        // 生成唯一token
        $qrcodeToken = md5(uniqid('synthesis_activity_' . $aid . '_' . $templateId . '_', true));

        // 生成二维码图片URL（使用系统级二维码生成服务）
        $qrcodeUrl = $this->generateQrcodeImage($qrcodeToken, $aid, $bid);

        // 创建活动记录
        $activity = AiTravelPhotoSynthesisActivity::create([
            'aid' => $aid,
            'bid' => $bid,
            'template_id' => $templateId,
            'qrcode_token' => $qrcodeToken,
            'qrcode_url' => $qrcodeUrl,
            'name' => $name,
            'price' => $price,
            'status' => AiTravelPhotoSynthesisActivity::STATUS_ENABLED,
        ]);

        Log::info('合成活动创建成功', [
            'activity_id' => $activity->id,
            'aid' => $aid,
            'bid' => $bid,
            'template_id' => $templateId,
        ]);

        return [
            'activity_id' => $activity->id,
            'qrcode_token' => $qrcodeToken,
            'qrcode_url' => $qrcodeUrl,
            'name' => $name,
            'price' => $price,
        ];
    }

    /**
     * 生成二维码图片
     */
    protected function generateQrcodeImage(string $token, int $aid = 0, int $bid = 0): string
    {
        $domain = request()->domain();
        $url = $domain . '/public/synthesis/index.html?token=' . $token;

        // 使用系统已有的 phpqrcode 库生成二维码
        try {
            require_once app()->getRootPath() . 'extend/phpqrcode/phpqrcode.php';

            // 创建临时文件
            $tempFile = sys_get_temp_dir() . '/synthesis_activity_' . $token . '.png';

            // 生成二维码
            \QRcode::png($url, $tempFile, 'L', 10, 2);

            // 上传到OSS
            try {
                $ossHelper = new \app\common\OssHelper();
                $ossPath = 'ai_travel_photo/synthesis_qrcode/' . date('Ymd') . '/' . $token . '.png';
                $qrcodeUrl = $ossHelper->uploadFile($tempFile, $ossPath);

                // 清理临时文件
                @unlink($tempFile);

                return $qrcodeUrl;
            } catch (\Throwable $e) {
                // OSS不可用，保存到本地
                Log::warning('合成活动二维码OSS上传失败，保存到本地', ['error' => $e->getMessage()]);
                $localPath = 'upload/synthesis_qrcode/' . date('Ymd') . '/';
                $fullDir = app()->getRootPath() . 'public/' . $localPath;
                if (!is_dir($fullDir)) {
                    mkdir($fullDir, 0755, true);
                }
                $fullLocalPath = $fullDir . $token . '.png';
                rename($tempFile, $fullLocalPath);
                return $domain . '/' . $localPath . $token . '.png';
            }
        } catch (\Throwable $e) {
            Log::warning('二维码图片生成失败，使用文本URL', ['error' => $e->getMessage()]);
            return $url;
        }
    }

    /**
     * 根据二维码token获取活动信息
     *
     * @param string $qrcodeToken 二维码token
     * @return array
     */
    public function getActivityByToken(string $qrcodeToken): array
    {
        Log::info('合成活动：查找活动 by token=' . $qrcodeToken);
        
        // 使用 Db::name 避免模型严格字段检查
        $activity = Db::name('ai_travel_photo_synthesis_activity')
            ->where('qrcode_token', $qrcodeToken)
            ->find();

        if (!$activity) {
            Log::warning('合成活动：活动不存在 for token=' . $qrcodeToken);
            throw new \Exception('活动不存在');
        }

        if ((int)$activity['status'] !== 1) { // STATUS_ENABLED
            throw new \Exception('活动已结束');
        }

        if (!empty($activity['expire_time']) && time() > (int)$activity['expire_time']) {
            throw new \Exception('活动已过期');
        }

        // 获取关联的合成模板信息
        $template = Db::name('ai_travel_photo_synthesis_template')
            ->find($activity['template_id']);
        if (!$template || (int)$template['status'] !== 1) {
            throw new \Exception('合成模板已下架');
        }

        Log::info('合成活动：活动信息获取成功', ['activity_id' => $activity['id'], 'template_id' => $activity['template_id']]);

        return [
            'activity_id' => $activity['id'],
            'aid' => $activity['aid'],
            'bid' => $activity['bid'],
            'name' => $activity['name'],
            'price' => $activity['price'],
            'template_id' => $activity['template_id'],
            'template_name' => $template['name'],
            'template_images' => $template['images'],
        ];
    }

    /**
     * 提交用户上传照片，触发生成流程
     * 返回 user_photo_id 后，异步执行生成
     *
     * @param int    $activityId 活动ID
     * @param string $openid     用户OpenID
     * @param int    $uid        会员ID
     * @param string $photoUrl   用户上传照片URL
     * @return array
     */
    public function submitPhoto(int $activityId, string $openid, int $uid, string $photoUrl): array
    {
        if (empty($photoUrl)) {
            throw new \Exception('请上传照片');
        }

        // 检查活动是否有效
        $activity = Db::name('ai_travel_photo_synthesis_activity')->find($activityId);
        if (!$activity || (int)$activity['status'] !== 1) {
            throw new \Exception('活动不存在或已结束');
        }

        // 更新活动扫码统计
        $scanData = ['scan_count' => (int)$activity['scan_count'] + 1];
        // 首次扫码检查
        $existRecord = Db::name('ai_travel_photo_synthesis_user_photo')
            ->where('activity_id', $activityId)
            ->where('openid', $openid)->find();
        if (!$existRecord) {
            $scanData['unique_scan_count'] = (int)$activity['unique_scan_count'] + 1;
        }
        Db::name('ai_travel_photo_synthesis_activity')->where('id', $activityId)->update($scanData);

        // 创建用户照片记录
        $userPhotoId = Db::name('ai_travel_photo_synthesis_user_photo')->insertGetId([
            'activity_id' => $activityId,
            'openid' => $openid,
            'uid' => $uid,
            'photo_url' => $photoUrl,
            'tag_status' => 0, // TAG_STATUS_PENDING
            'gen_status' => 0, // GEN_STATUS_PENDING
            'create_time' => time(),
            'update_time' => time(),
        ]);
        $userPhoto = Db::name('ai_travel_photo_synthesis_user_photo')->find($userPhotoId);

        // 直接同步执行生成（不依赖队列worker）
        $this->executeGeneration($userPhoto['id']);

        return [
            'user_photo_id' => $userPhoto['id'],
            'gen_status' => $userPhoto['gen_status'],
        ];
    }

    /**
     * 执行完整生成流程（标签识别 → 提示词改写 → AI生成 → 水印添加）
     * 由队列任务或同步调用
     *
     * @param int $userPhotoId 用户照片记录ID
     */
    public function executeGeneration(int $userPhotoId): void
    {
        // 使用 Db::name 避免 Model 严格字段检查
        $userPhoto = Db::name('ai_travel_photo_synthesis_user_photo')->find($userPhotoId);
        if (!$userPhoto) {
            Log::error('合成活动：user_photo记录不存在', ['id' => $userPhotoId]);
            return;
        }

        $startTime = microtime(true);
        Log::info('合成活动：开始执行生成流程', ['user_photo_id' => $userPhotoId]);

        try {
            // 更新状态为处理中
            Db::name('ai_travel_photo_synthesis_user_photo')
                ->where('id', $userPhotoId)
                ->update(['gen_status' => 2, 'update_time' => time()]); // GEN_STATUS_PROCESSING=2

            // 获取活动信息
            $activity = Db::name('ai_travel_photo_synthesis_activity')->find($userPhoto['activity_id']);
            if (!$activity) {
                throw new \Exception('活动不存在');
            }

            $aid = (int)$activity['aid'];
            $bid = (int)$activity['bid'];

            // 获取合成模板
            $template = Db::name('ai_travel_photo_synthesis_template')->find($activity['template_id']);
            if (!$template) {
                throw new \Exception('合成模板不存在');
            }

            // 获取商家合成活动配置（bid=0时无商家配置，使用默认值）
            $business = $bid > 0 ? Db::name('business')->where('id', $bid)->find() : null;
            $promptRewriteEnabled = $business ? (int)($business['synthesis_qr_prompt_rewrite_enabled'] ?? 1) : 1;
            $promptRewriteProvider = $business ? ($business['synthesis_qr_prompt_rewrite_provider'] ?? 'aliyun') : 'aliyun';

            // ===== Step 1: 自动标签识别 =====
            Log::info('合成活动：Step1 开始标签识别', ['user_photo_id' => $userPhotoId]);
            Db::name('ai_travel_photo_synthesis_user_photo')
                ->where('id', $userPhotoId)
                ->update(['tag_status' => 1, 'update_time' => time()]); // TAG_STATUS_PROCESSING=1

            $tags = $this->analyzePortraitTags($userPhoto['photo_url']);
            $tagGender = $tags['gender'] ?? 'Unknown';
            $tagAgeGroup = $tags['age_group'] ?? '';
            $tagIsMulti = (int)($tags['is_multi_face'] ?? 0);

            Db::name('ai_travel_photo_synthesis_user_photo')
                ->where('id', $userPhotoId)
                ->update([
                    'tag_status' => 2, // TAG_STATUS_COMPLETED=2
                    'tag_gender' => $tagGender,
                    'tag_age_group' => $tagAgeGroup,
                    'tag_is_multi' => $tagIsMulti,
                    'tag_raw' => json_encode($tags, JSON_UNESCAPED_UNICODE),
                    'update_time' => time(),
                ]);
            Log::info('合成活动：标签识别完成', ['tags' => $tags]);

            // ===== Step 2: 提示词改写（如果启用） =====
            $originalPrompt = $template['prompt'] ?: '生成一张高质量旅拍照片';
            $finalPrompt = $originalPrompt;

            if ($promptRewriteEnabled && $tagGender !== 'Unknown') {
                Log::info('合成活动：Step2 开始提示词改写', ['user_photo_id' => $userPhotoId]);
                try {
                    $rewriteService = new PromptRewriteService();
                    $rewrittenPrompt = $rewriteService->rewrite(
                        $originalPrompt,
                        [
                            'gender' => $tagGender,
                            'age' => $tagAgeGroup,
                            'is_multi' => $tagIsMulti,
                            'face_count' => $tags['face_count'] ?? 1,
                        ],
                        $promptRewriteProvider
                    );

                    if (!empty($rewrittenPrompt) && $rewrittenPrompt !== $originalPrompt) {
                        $finalPrompt = $rewrittenPrompt;
                        Db::name('ai_travel_photo_synthesis_user_photo')
                            ->where('id', $userPhotoId)
                            ->update([
                                'rewritten_prompt' => $finalPrompt,
                                'update_time' => time(),
                            ]);
                        Log::info('合成活动：提示词改写完成', [
                            'original' => mb_substr($originalPrompt, 0, 50),
                            'rewritten' => mb_substr($finalPrompt, 0, 50),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('合成活动：提示词改写失败，使用原提示词', ['error' => $e->getMessage()]);
                }
            }

            // ===== Step 3: AI图片生成 =====
            Log::info('合成活动：Step3 开始AI生成', ['user_photo_id' => $userPhotoId]);
            $synthesisService = new AiTravelPhotoSynthesisService();

            // 准备模板数据供生成使用（临时替换 prompt 为改写后的）
            $templateData = $template;
            if ($finalPrompt !== $originalPrompt) {
                $templateData['prompt'] = $finalPrompt;
            }

            $generatedUrl = $synthesisService->generateWithPhotoAndTemplate(
                $userPhoto['photo_url'],
                $templateData
            );

            if (empty($generatedUrl)) {
                throw new \Exception('AI生成图片失败：返回结果为空');
            }
            Log::info('合成活动：AI生成完成', ['url' => $generatedUrl]);

            // ===== Step 4: 保存结果并添加水印 =====
            Log::info('合成活动：Step4 添加水印', ['user_photo_id' => $userPhotoId]);
            $watermarkedUrl = $generatedUrl;

            // 创建 result 记录
            $resultId = (int)Db::name('ai_travel_photo_result')->insertGetId([
                'aid' => $aid,
                'bid' => $bid,
                'portrait_id' => 0,
                'generation_id' => 0,
                'scene_id' => $activity['template_id'],
                'url' => $generatedUrl,
                'file_size' => 0,
                'type' => 1,
                'status' => 1,
                'desc' => $template['name'] ?: '合成活动生成',
                'create_time' => time(),
                'update_time' => time(),
            ]);

            // 尝试添加水印
            if ($business && !empty($business['ai_logo_watermark'])) {
                try {
                    $watermarkService = new AiTravelPhotoWatermarkService();
                    $watermarkResult = $watermarkService->addWatermark($resultId);
                    $watermarkedUrl = $watermarkResult['watermark_url'] ?? $generatedUrl;

                    // 更新 result 表的水印URL
                    Db::name('ai_travel_photo_result')
                        ->where('id', $resultId)
                        ->update(['result_url_watermark' => $watermarkedUrl]);
                } catch (\Throwable $e) {
                    Log::warning('合成活动：水印添加失败，使用原图', [
                        'error' => $e->getMessage(),
                        'result_id' => $resultId,
                    ]);
                }
            }

            // ===== 更新最终状态 =====
            Db::name('ai_travel_photo_synthesis_user_photo')
                ->where('id', $userPhotoId)
                ->update([
                    'result_id' => $resultId,
                    'result_url' => $generatedUrl,
                    'result_watermark_url' => $watermarkedUrl,
                    'gen_status' => 3, // GEN_STATUS_COMPLETED=3
                    'update_time' => time(),
                ]);

            // 更新活动统计
            Db::name('ai_travel_photo_synthesis_activity')
                ->where('id', $userPhoto['activity_id'])
                ->inc('gen_count', 1)
                ->update(['update_time' => time()]);

            $elapsed = round(microtime(true) - $startTime, 2);
            Log::info('合成活动：生成流程完成', [
                'user_photo_id' => $userPhotoId,
                'elapsed' => $elapsed . 's',
                'result_id' => $resultId,
            ]);

        } catch (\Throwable $e) {
            Log::error('合成活动：生成流程失败', [
                'user_photo_id' => $userPhotoId,
                'error' => $e->getMessage(),
            ]);
            $failData = [
                'gen_status' => 4, // GEN_STATUS_FAILED=4
                'gen_error' => $e->getMessage(),
                'update_time' => time(),
            ];
            
            // 重新读取当前状态以判断 tag_status
            $current = Db::name('ai_travel_photo_synthesis_user_photo')->find($userPhotoId);
            if ($current && (int)$current['tag_status'] === 1) { // 标签处理中
                $failData['tag_status'] = 3; // TAG_STATUS_FAILED=3
            }
            
            Db::name('ai_travel_photo_synthesis_user_photo')
                ->where('id', $userPhotoId)
                ->update($failData);
        }
    }

    /**
     * 分析人像标签
     */
    protected function analyzePortraitTags(string $photoUrl): array
    {
        try {
            $analysisService = new ImageAnalysisService();
            $result = $analysisService->analyzeFromUrl($photoUrl);

            if (empty($result)) {
                Log::warning('合成活动：人物分析返回空');
                return ['gender' => '', 'age_group' => '', 'race' => '', 'is_multi_face' => false, 'face_count' => 0];
            }

            $tags = ImageAnalysisService::extractMainSubject($result);
            Log::info('合成活动：人物分析结果', $tags);
            return $tags;
        } catch (\Throwable $e) {
            Log::error('合成活动：人物分析异常: ' . $e->getMessage());
            return ['gender' => '', 'age_group' => '', 'race' => '', 'is_multi_face' => false, 'face_count' => 0];
        }
    }

    /**
     * 查询生成进度
     *
     * @param int $userPhotoId 用户照片记录ID
     * @return array
     */
    public function getStatus(int $userPhotoId): array
    {
        $userPhoto = Db::name('ai_travel_photo_synthesis_user_photo')->find($userPhotoId);

        if (!$userPhoto) {
            return ['gen_status' => -1, 'message' => '记录不存在'];
        }

        $data = [
            'user_photo_id' => (int)$userPhoto['id'],
            'gen_status' => (int)$userPhoto['gen_status'],
            'gen_status_text' => $this->genStatusText((int)$userPhoto['gen_status']),
            'gen_error' => $userPhoto['gen_error'],
            'tag_status' => (int)$userPhoto['tag_status'],
            'tag_status_text' => $this->tagStatusText((int)$userPhoto['tag_status']),
            'tag_gender' => $userPhoto['tag_gender'],
            'tag_age_group' => $userPhoto['tag_age_group'],
            'tag_is_multi' => (int)$userPhoto['tag_is_multi'],
            'watermark_url' => $userPhoto['result_watermark_url'],
            'paid' => (int)$userPhoto['paid'],
        ];

        // 如果已完成，附加标签和改写提示词信息
        if ((int)$userPhoto['gen_status'] === 3) { // GEN_STATUS_COMPLETED
            $data['tags'] = $userPhoto['tag_raw'];
            $data['original_prompt'] = '';
            $data['rewritten_prompt'] = $userPhoto['rewritten_prompt'] ?: '';
        }

        return $data;
    }

    private function genStatusText(int $status): string
    {
        $map = [0 => '待生成', 1 => '标签识别中', 2 => '生成中', 3 => '已完成', 4 => '失败'];
        return $map[$status] ?? '未知';
    }

    private function tagStatusText(int $status): string
    {
        $map = [0 => '待识别', 1 => '识别中', 2 => '已完成', 3 => '失败'];
        return $map[$status] ?? '未知';
    }

    /**
     * 创建支付订单
     *
     * @param int    $userPhotoId 用户照片记录ID
     * @param string $openid      用户OpenID
     * @return array
     */
    public function createPayOrder(int $userPhotoId, string $openid): array
    {
        $userPhoto = Db::name('ai_travel_photo_synthesis_user_photo')->find($userPhotoId);
        if (!$userPhoto) {
            throw new \Exception('记录不存在');
        }

        if ((int)$userPhoto['gen_status'] !== 3) { // GEN_STATUS_COMPLETED
            throw new \Exception('图片还未生成完成');
        }

        if ((int)$userPhoto['paid'] === 1) { // PAID_YES
            throw new \Exception('您已支付过该照片');
        }

        // 检查是否已有未支付的订单
        if ((int)$userPhoto['order_id'] > 0) {
            $existOrder = AiTravelPhotoOrder::find((int)$userPhoto['order_id']);
            if ($existOrder && $existOrder->status == AiTravelPhotoOrder::STATUS_UNPAID) {
                if ($existOrder->isTimeout()) {
                    $existOrder->status = AiTravelPhotoOrder::STATUS_CLOSED;
                    $existOrder->save();
                } else {
                    return [
                        'order_no' => $existOrder->order_no,
                        'amount' => $existOrder->actual_amount,
                        'is_existing' => true,
                    ];
                }
            }
        }

        $activity = Db::name('ai_travel_photo_synthesis_activity')->find((int)$userPhoto['activity_id']);
        if (!$activity) {
            throw new \Exception('活动不存在');
        }

        $aid = (int)$activity['aid'];
        $bid = (int)$activity['bid'];
        $amount = (float)$activity['price'];
        $uid = (int)$userPhoto['uid'];

        // 生成订单号
        $orderNo = 'SA' . date('YmdHis') . mt_rand(1000, 9999);

        Db::startTrans();
        try {
            // 创建旅拍订单
            $order = AiTravelPhotoOrder::create([
                'aid' => $aid,
                'bid' => $bid,
                'uid' => $uid,
                'portrait_id' => 0,
                'order_no' => $orderNo,
                'status' => AiTravelPhotoOrder::STATUS_UNPAID,
                'buy_type' => AiTravelPhotoOrder::BUY_TYPE_SINGLE,
                'total_price' => $amount,
                'actual_amount' => $amount,
                'download_limit' => 1,
                'openid' => $openid,
                'remark' => '合成活动: ' . ($activity['name'] ?: ''),
            ]);

            // 创建订单商品
            AiTravelPhotoOrderGoods::create([
                'order_id' => $order->id,
                'result_id' => (int)$userPhoto['result_id'],
                'single_price' => $amount,
            ]);

            // 关联订单到用户记录
            Db::name('ai_travel_photo_synthesis_user_photo')
                ->where('id', $userPhotoId)
                ->update(['order_id' => $order->id, 'update_time' => time()]);

            Db::commit();

            Log::info('合成活动：订单创建成功', [
                'order_no' => $orderNo,
                'user_photo_id' => $userPhotoId,
                'amount' => $amount,
            ]);

            return [
                'order_no' => $orderNo,
                'amount' => $amount,
                'is_existing' => false,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('合成活动：订单创建失败', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 发起微信支付
     *
     * @param string $orderNo 订单号
     * @param string $openid  用户OpenID
     * @return array
     */
    public function createPayment(string $orderNo, string $openid): array
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }

        if ($order->status != AiTravelPhotoOrder::STATUS_UNPAID) {
            throw new \Exception('订单状态异常');
        }

        if ($order->isTimeout()) {
            throw new \Exception('订单已超时，请重新下单');
        }

        $actualAmount = (float)$order->actual_amount;
        if ($actualAmount <= 0) {
            throw new \Exception('订单金额异常');
        }

        $aid = $order->aid;
        $bid = $order->bid;

        // 创建通用支付单
        $payorderId = Payorder::createorder(
            $aid,
            $bid,
            0,
            'synthesis_activity',
            $order->id,
            $orderNo,
            '合成活动照片-' . $orderNo,
            $actualAmount
        );

        if (!$payorderId) {
            throw new \Exception('创建支付单失败');
        }

        // 获取微信支付配置
        $wxConfig = $this->getWxPayConfig($aid, $bid);

        // 调用微信统一下单
        $unifiedResult = $this->wxUnifiedOrder($wxConfig, $orderNo, $actualAmount, $openid);

        if (empty($unifiedResult['prepay_id'])) {
            throw new \Exception('微信下单失败：' . ($unifiedResult['err_code_des'] ?? $unifiedResult['return_msg'] ?? '未知错误'));
        }

        // 构建JSAPI支付参数
        $jsApiParams = $this->buildJsApiParams($wxConfig, $unifiedResult['prepay_id']);

        return [
            'payorder_id' => $payorderId,
            'js_api_params' => $jsApiParams,
            'order_no' => $orderNo,
        ];
    }

    /**
     * 查询支付状态
     */
    public function getPayStatus(string $orderNo): array
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();
        if (!$order) {
            return ['status' => 'not_found'];
        }

        if ($order->status == AiTravelPhotoOrder::STATUS_PAID || $order->status == AiTravelPhotoOrder::STATUS_COMPLETED) {
            return ['status' => 'paid', 'pay_time' => $order->pay_time, 'order_no' => $orderNo];
        }

        return ['status' => 'unpaid', 'order_status' => $order->status];
    }

    /**
     * 支付成功履约
     *
     * @param string $orderNo 订单号
     * @param array  $payData 支付数据
     * @return bool
     */
    public function fulfillOrder(string $orderNo, array $payData = []): bool
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();
        if (!$order) {
            Log::error('合成活动履约失败：订单不存在 ' . $orderNo);
            return false;
        }

        // 幂等性检查
        if ($order->status != AiTravelPhotoOrder::STATUS_UNPAID) {
            return true;
        }

        Db::startTrans();
        try {
            // 更新订单状态
            $order->status = AiTravelPhotoOrder::STATUS_PAID;
            $order->pay_time = time();
            if (!empty($payData['transaction_id'])) {
                $order->transaction_id = $payData['transaction_id'];
            }
            $order->save();

            // 更新订单商品下载链接（无水印原图）
            $goods = AiTravelPhotoOrderGoods::where('order_id', $order->id)->select();
            foreach ($goods as $item) {
                $result = AiTravelPhotoResult::find($item->result_id);
                if ($result) {
                    $item->download_url = $result->url;
                    $item->save();
                }
            }

            // 更新用户照片记录的支付状态
            $userPhoto = Db::name('ai_travel_photo_synthesis_user_photo')
                ->where('order_id', $order->id)->find();
            if ($userPhoto) {
                Db::name('ai_travel_photo_synthesis_user_photo')
                    ->where('id', $userPhoto['id'])
                    ->update(['paid' => 1, 'update_time' => time()]); // PAID_YES

                // 更新活动统计
                $activity = Db::name('ai_travel_photo_synthesis_activity')
                    ->find($userPhoto['activity_id']);
                if ($activity) {
                    Db::name('ai_travel_photo_synthesis_activity')
                        ->where('id', $activity['id'])
                        ->inc('order_count', 1)
                        ->update([
                            'total_amount' => bcadd((string)$activity['total_amount'], (string)$order->actual_amount, 2),
                            'update_time' => time(),
                        ]);
                }
            }

            Db::commit();
            Log::info('合成活动：支付履约完成', ['order_no' => $orderNo]);
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('合成活动：支付履约失败', ['error' => $e->getMessage(), 'order_no' => $orderNo]);
            return false;
        }
    }

    /**
     * 获取下载信息
     */
    public function getDownloadInfo(string $orderNo): array
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();
        if (!$order) {
            throw new \Exception('订单不存在');
        }

        if ($order->status != AiTravelPhotoOrder::STATUS_PAID
            && $order->status != AiTravelPhotoOrder::STATUS_COMPLETED) {
            throw new \Exception('订单未支付');
        }

        $goods = AiTravelPhotoOrderGoods::where('order_id', $order->id)->select();

        $items = [];
        foreach ($goods as $item) {
            $result = AiTravelPhotoResult::find($item->result_id);
            if ($result) {
                $items[] = [
                    'goods_id' => $item->id,
                    'result_id' => $item->result_id,
                    'download_url' => $result->url, // 无水印原图
                    'thumbnail_url' => $result->thumbnail_url ?: $result->url,
                ];
            }
        }

        $userPhoto = Db::name('ai_travel_photo_synthesis_user_photo')
            ->where('order_id', $order->id)->find();

        return [
            'order_no' => $orderNo,
            'status' => $order->status == AiTravelPhotoOrder::STATUS_PAID ? 'paid' : 'completed',
            'items' => $items,
            'user_photo_id' => $userPhoto ? (int)$userPhoto['id'] : 0,
        ];
    }

    /**
     * 验证微信支付回调签名
     */
    public function verifyWxNotifySign(array $data, string $mchKey): bool
    {
        if (empty($data['sign']) || empty($mchKey)) {
            return false;
        }

        $sign = $data['sign'];
        unset($data['sign']);

        // 按字典序排序
        ksort($data);
        $signStr = '';
        foreach ($data as $k => $v) {
            if (!is_array($v) && $v !== '' && $k !== 'sign_type') {
                $signStr .= $k . '=' . $v . '&';
            }
        }
        $signStr .= 'key=' . $mchKey;

        $calculatedSign = strtoupper(md5($signStr));
        return $calculatedSign === $sign;
    }

    // ===== 微信支付辅助方法 =====

    protected function getWxPayConfig(int $aid, int $bid): array
    {
        $wxset = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
        return [
            'appid' => $wxset['appid'] ?? '',
            'mch_id' => $wxset['wxpay_mchid'] ?? '',
            'mch_key' => $wxset['wxpay_mchkey'] ?? '',
            'notify_url' => request()->domain() . '/index.php?s=/api/ai_travel_photo/synthesis/notify',
        ];
    }

    protected function wxUnifiedOrder(array $config, string $orderNo, float $amount, string $openid): array
    {
        $params = [
            'appid' => $config['appid'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => $this->getNonceStr(),
            'body' => '合成活动照片',
            'out_trade_no' => $orderNo,
            'total_fee' => intval($amount * 100),
            'spbill_create_ip' => request()->ip(),
            'notify_url' => $config['notify_url'],
            'trade_type' => 'JSAPI',
            'openid' => $openid,
        ];

        $params['sign'] = $this->makeSign($params, $config['mch_key']);

        $xml = $this->toXml($params);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->fromXml($response);
    }

    protected function buildJsApiParams(array $config, string $prepayId): array
    {
        $params = [
            'appId' => $config['appid'],
            'timeStamp' => (string)time(),
            'nonceStr' => $this->getNonceStr(),
            'package' => 'prepay_id=' . $prepayId,
            'signType' => 'MD5',
        ];
        $params['paySign'] = $this->makeSign($params, $config['mch_key']);
        return $params;
    }

    protected function makeSign(array $data, string $key): string
    {
        ksort($data);
        $signStr = '';
        foreach ($data as $k => $v) {
            if ($v !== '' && $k !== 'sign') {
                $signStr .= $k . '=' . $v . '&';
            }
        }
        $signStr .= 'key=' . $key;
        return strtoupper(md5($signStr));
    }

    protected function getNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    protected function toXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $k => $v) {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
        $xml .= '</xml>';
        return $xml;
    }

    protected function fromXml(string $xml): array
    {
        libxml_disable_entity_loader(true);
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($obj === false) return [];
        $arr = json_decode(json_encode($obj), true) ?: [];
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = '';
            }
        }
        return $arr;
    }
}
