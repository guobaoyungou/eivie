<?php
/**
 * AI旅拍系统 - 核心服务类
 * 
 * @package app\common
 * @author AI Assistant
 * @date 2026-01-21
 */

namespace app\common;

use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

class AiTravelPhotoService
{
    /**
     * 自动触发生成（人像上传后调用）
     * 
     * @param int $portrait_id 人像ID
     * @return bool
     */
    public function autoGeneration($portrait_id)
    {
        // 查询人像信息
        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('id', $portrait_id)
            ->find();

        if (!$portrait) {
            Log::error('AI旅拍：人像不存在 portrait_id=' . $portrait_id);
            return false;
        }

        // 查询商家配置
        $business = Db::name('business')
            ->where('id', $portrait['bid'])
            ->find();

        if ($business['ai_travel_photo_enabled'] != 1) {
            Log::info('AI旅拍：商家未开启功能 bid=' . $portrait['bid']);
            return false;
        }

        // 查询启用的场景
        $max_scenes = $business['ai_max_scenes'] ?? 10;
        $scenes = Db::name('ai_travel_photo_scene')
            ->where('bid', $portrait['bid'])
            ->where('status', 1)
            ->order('sort DESC')
            ->limit($max_scenes)
            ->select();

        if (empty($scenes)) {
            Log::warning('AI旅拍：商家无可用场景 bid=' . $portrait['bid']);
            return false;
        }

        // 将每个场景加入生成队列
        foreach ($scenes as $scene) {
            Queue::push('app\job\AiImageGeneration', [
                'portrait_id' => $portrait_id,
                'scene_id' => $scene['id']
            ], 'ai_generation');
        }

        // 如果开启自动生成视频
        if ($business['ai_auto_generate_video'] == 1) {
            Queue::later(180, 'app\job\AiVideoGeneration', [
                'portrait_id' => $portrait_id,
                'duration' => $business['ai_video_duration'] ?? 5
            ], 'ai_generation');
        }

        Log::info('AI旅拍：已加入生成队列 portrait_id=' . $portrait_id . ' scenes=' . count($scenes));
        return true;
    }

    /**
     * 图生图处理
     * 
     * @param int $portrait_id 人像ID
     * @param int $scene_id 场景ID
     * @return array
     */
    public function imageToImage($portrait_id, $scene_id)
    {
        // 查询人像
        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('id', $portrait_id)
            ->find();

        if (!$portrait) {
            return ['status' => 0, 'msg' => '人像不存在'];
        }

        // 查询场景
        $scene = Db::name('ai_travel_photo_scene')
            ->where('id', $scene_id)
            ->find();

        if (!$scene || $scene['status'] != 1) {
            return ['status' => 0, 'msg' => '场景不可用'];
        }

        // 创建生成记录
        $generation_id = Db::name('ai_travel_photo_generation')->insertGetId([
            'aid' => $portrait['aid'],
            'portrait_id' => $portrait_id,
            'scene_id' => $scene_id,
            'bid' => $portrait['bid'],
            'type' => 1,
            'generation_type' => 1,
            'prompt' => $scene['prompt'],
            'status' => 1,
            'create_time' => time()
        ]);

        $start_time = time();

        try {
            // 获取AI模型配置
            $model = $this->getModel($scene['model_id']);

            if (!$model) {
                throw new \Exception('AI模型配置不存在');
            }

            // 调用AI接口
            $ai_service = $this->getAiService($model['model_type']);
            $result = $ai_service->generateImage([
                'portrait_url' => $portrait['cutout_url'] ?: $portrait['original_url'],
                'scene_url' => $scene['background_url'],
                'prompt' => $scene['prompt'],
                'negative_prompt' => $scene['negative_prompt'],
                'model_name' => $model['model_name'],
                'api_key' => $model['api_key'],
                'api_base_url' => $model['api_base_url']
            ]);

            if ($result['status'] != 1) {
                throw new \Exception($result['msg']);
            }

            // 下载生成的图片到OSS
            $oss_url = $this->downloadToOss($result['data']['image_url'], $portrait['aid']);

            // 插入结果表
            $result_id = Db::name('ai_travel_photo_result')->insertGetId([
                'aid' => $portrait['aid'],
                'generation_id' => $generation_id,
                'portrait_id' => $portrait_id,
                'type' => 1,
                'url' => $oss_url,
                'create_time' => time()
            ]);

            // 更新生成记录
            $cost_time = time() - $start_time;
            Db::name('ai_travel_photo_generation')
                ->where('id', $generation_id)
                ->update([
                    'status' => 2,
                    'cost_time' => $cost_time,
                    'update_time' => time()
                ]);

            // 生成水印预览图和二维码
            $this->addWatermarkAndQrcode($result_id);

            // 更新场景使用次数
            Db::name('ai_travel_photo_scene')
                ->where('id', $scene_id)
                ->inc('use_count')
                ->update();

            Log::info('AI旅拍：图生图成功 portrait_id=' . $portrait_id . ' scene_id=' . $scene_id . ' cost_time=' . $cost_time . 's');

            return [
                'status' => 1,
                'msg' => '生成成功',
                'data' => [
                    'generation_id' => $generation_id,
                    'result_id' => $result_id,
                    'url' => $oss_url
                ]
            ];

        } catch (\Exception $e) {
            // 更新生成记录为失败
            Db::name('ai_travel_photo_generation')
                ->where('id', $generation_id)
                ->update([
                    'status' => 3,
                    'error_msg' => $e->getMessage(),
                    'update_time' => time()
                ]);

            Log::error('AI旅拍：图生图失败 portrait_id=' . $portrait_id . ' scene_id=' . $scene_id . ' error=' . $e->getMessage());

            return [
                'status' => 0,
                'msg' => '生成失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 图生视频处理
     * 
     * @param int $portrait_id 人像ID
     * @param int $duration 视频时长（5或10秒）
     * @return array
     */
    public function imageToVideo($portrait_id, $duration = 5)
    {
        // 查询第一张合成图作为首帧
        $first_result = Db::name('ai_travel_photo_result')
            ->where('portrait_id', $portrait_id)
            ->where('type', 1)
            ->order('id ASC')
            ->find();

        if (!$first_result) {
            return ['status' => 0, 'msg' => '未找到可用的图片'];
        }

        // 创建生成记录
        $generation_id = Db::name('ai_travel_photo_generation')->insertGetId([
            'aid' => $first_result['aid'],
            'portrait_id' => $portrait_id,
            'scene_id' => 0,
            'type' => 1,
            'generation_type' => 3,
            'prompt' => '镜头缓慢推进，背景虚化，人物清晰',
            'status' => 1,
            'create_time' => time()
        ]);

        try {
            // 从模型广场获取可灵AI配置
            $modelInfo = Db::name('model_info')
                ->where('model_code', 'kling_ai')
                ->where('is_active', 1)
                ->find();

            if (!$modelInfo) {
                throw new \Exception('视频生成模型未配置');
            }

            // 获取商户API Key配置（如果有）
            $merchantConfig = Db::name('merchant_model_config')
                ->where('model_id', $modelInfo['id'])
                ->where('bid', $portrait['bid'])
                ->where('is_active', 1)
                ->find();

            $apiKey = $merchantConfig ? $merchantConfig['api_key'] : '';

            // 调用可灵AI视频生成
            $ai_service = new AiKlingService();
            $result = $ai_service->imageToVideo([
                'image_url' => $first_result['url'],
                'prompt' => '镜头缓慢推进，背景虚化，人物清晰，光影自然变化',
                'duration' => $duration,
                'api_key' => $apiKey
            ]);

            if ($result['status'] != 1) {
                throw new \Exception($result['msg']);
            }

            // 下载视频到OSS
            $video_url = $this->downloadToOss($result['data']['video_url'], $first_result['aid'], 'video');

            // 插入结果表
            $result_id = Db::name('ai_travel_photo_result')->insertGetId([
                'aid' => $first_result['aid'],
                'generation_id' => $generation_id,
                'portrait_id' => $portrait_id,
                'type' => 19,
                'url' => $video_url,
                'video_duration' => $duration,
                'create_time' => time()
            ]);

            // 更新生成记录
            Db::name('ai_travel_photo_generation')
                ->where('id', $generation_id)
                ->update([
                    'status' => 2,
                    'update_time' => time()
                ]);

            // 生成视频封面作为预览图
            $this->generateVideoThumbnail($result_id, $video_url);

            Log::info('AI旅拍：图生视频成功 portrait_id=' . $portrait_id);

            return [
                'status' => 1,
                'msg' => '视频生成成功',
                'data' => [
                    'result_id' => $result_id,
                    'video_url' => $video_url
                ]
            ];

        } catch (\Exception $e) {
            Db::name('ai_travel_photo_generation')
                ->where('id', $generation_id)
                ->update([
                    'status' => 3,
                    'error_msg' => $e->getMessage(),
                    'update_time' => time()
                ]);

            Log::error('AI旅拍：图生视频失败 portrait_id=' . $portrait_id . ' error=' . $e->getMessage());

            return [
                'status' => 0,
                'msg' => '视频生成失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 添加水印和二维码
     * 
     * @param int $result_id 结果ID
     * @return bool
     */
    public function addWatermarkAndQrcode($result_id)
    {
        $result = Db::name('ai_travel_photo_result')
            ->where('id', $result_id)
            ->find();

        if (!$result) {
            return false;
        }

        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('id', $result['portrait_id'])
            ->find();

        $business = Db::name('business')
            ->where('id', $portrait['bid'])
            ->find();

        try {
            // 生成二维码内容
            $qrcode_content = $portrait['bid'] . '_' . $portrait['id'] . '_' . time() . '_' . Common::getRandomStr(8);

            // 生成二维码图片URL
            $qrcode_service = new Qrcode();
            $qrcode_image_url = $qrcode_service->create('https://' . $_SERVER['HTTP_HOST'] . '/h5/ai-travel-photo/view?qrid=' . $qrcode_content);

            // 叠加水印和二维码
            $image_service = new ImageProcess();
            $watermark_url = $image_service->addWatermark([
                'source' => $result['url'],
                'logo' => $business['ai_logo_watermark'] ?: $business['logo'],
                'business_name' => $business['name'],
                'qrcode' => $qrcode_image_url,
                'position' => $business['ai_watermark_position'] ?? 1
            ]);

            // 更新result表
            Db::name('ai_travel_photo_result')
                ->where('id', $result_id)
                ->update([
                    'watermark_url' => $watermark_url,
                    'thumbnail_url' => $watermark_url
                ]);

            // 插入或更新二维码表
            $qrcode_exists = Db::name('ai_travel_photo_qrcode')
                ->where('portrait_id', $portrait['id'])
                ->find();

            $expire_days = $business['ai_qrcode_expire_days'] ?? 30;

            if (!$qrcode_exists) {
                Db::name('ai_travel_photo_qrcode')->insert([
                    'aid' => $portrait['aid'],
                    'portrait_id' => $portrait['id'],
                    'qrcode' => $qrcode_content,
                    'qrcode_url' => $watermark_url,
                    'status' => 1,
                    'expire_time' => time() + $expire_days * 86400,
                    'create_time' => time()
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('AI旅拍：添加水印失败 result_id=' . $result_id . ' error=' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取AI模型配置（从模型广场）
     */
    private function getModel($model_id, $bid = 0)
    {
        // 从模型广场获取模型信息
        if ($model_id > 0) {
            $model = Db::name('model_info')
                ->where('id', $model_id)
                ->where('is_active', 1)
                ->find();
        } else {
            // 返回默认模型（取第一个激活的）
            $model = Db::name('model_info')
                ->where('is_active', 1)
                ->order('sort', 'asc')
                ->find();
        }

        if (!$model) {
            return null;
        }

        // 获取商户API Key配置（如果有）
        if ($bid > 0) {
            $merchantConfig = Db::name('merchant_model_config')
                ->where('model_id', $model['id'])
                ->where('bid', $bid)
                ->where('is_active', 1)
                ->find();

            if ($merchantConfig) {
                $model['api_key'] = $merchantConfig['api_key'];
                $model['api_secret'] = $merchantConfig['api_secret'];
            }
        }

        return $model;
    }

    /**
     * 获取AI服务实例
     */
    private function getAiService($model_type)
    {
        switch ($model_type) {
            case 'aliyun_tongyi':
                return new AiTongyiService();
            case 'kling_ai':
                return new AiKlingService();
            case 'midjourney':
                return new AiMidjourneyService();
            default:
                return new AiTongyiService();
        }
    }

    /**
     * 下载远程图片到OSS
     */
    private function downloadToOss($remote_url, $aid, $type = 'image')
    {
        $file_service = new File();
        return $file_service->downloadRemoteFile($remote_url, 'ai_travel_photo/' . $type);
    }

    /**
     * 生成视频缩略图
     */
    private function generateVideoThumbnail($result_id, $video_url)
    {
        try {
            // 使用FFmpeg提取第一帧
            $video_service = new VideoProcess();
            $thumbnail_url = $video_service->extractFirstFrame($video_url);

            Db::name('ai_travel_photo_result')
                ->where('id', $result_id)
                ->update(['thumbnail_url' => $thumbnail_url]);

        } catch (\Exception $e) {
            Log::error('AI旅拍：生成视频缩略图失败 result_id=' . $result_id . ' error=' . $e->getMessage());
        }
    }

    /**
     * 支付成功回调处理
     * 
     * @param string $order_no 订单号
     * @return bool
     */
    public function paymentCallback($order_no)
    {
        $order = Db::name('ai_travel_photo_order')
            ->where('order_no', $order_no)
            ->find();

        if (!$order || $order['status'] != 0) {
            return false;
        }

        Db::startTrans();
        try {
            // 更新订单状态
            Db::name('ai_travel_photo_order')
                ->where('id', $order['id'])
                ->update([
                    'status' => 1,
                    'pay_time' => time(),
                    'update_time' => time()
                ]);

            // 查询订单商品
            $goods = Db::name('ai_travel_photo_order_goods')
                ->where('order_id', $order['id'])
                ->select();

            // 将原图关联到用户相册
            foreach ($goods as $item) {
                $result = Db::name('ai_travel_photo_result')
                    ->where('id', $item['result_id'])
                    ->find();

                if ($result) {
                    Db::name('ai_travel_photo_user_album')->insert([
                        'aid' => $order['aid'],
                        'uid' => $order['uid'],
                        'bid' => $order['bid'],
                        'order_id' => $order['id'],
                        'portrait_id' => $result['portrait_id'],
                        'result_id' => $result['id'],
                        'type' => $item['type'],
                        'url' => $result['url'],
                        'thumbnail_url' => $result['thumbnail_url'],
                        'status' => 1,
                        'create_time' => time()
                    ]);

                    // 更新result购买次数
                    Db::name('ai_travel_photo_result')
                        ->where('id', $result['id'])
                        ->inc('buy_count')
                        ->update();
                }
            }

            // 更新统计
            Db::name('ai_travel_photo_statistics')
                ->where('aid', $order['aid'])
                ->where('bid', $order['bid'])
                ->where('stat_date', date('Y-m-d'))
                ->inc('order_count')
                ->inc('order_amount', $order['total_price'])
                ->update();

            Db::commit();

            // 发送支付成功通知
            $this->sendPaymentNotice($order);

            Log::info('AI旅拍：支付成功 order_no=' . $order_no);
            return true;

        } catch (\Exception $e) {
            Db::rollback();
            Log::error('AI旅拍：支付回调失败 order_no=' . $order_no . ' error=' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送支付成功通知
     */
    private function sendPaymentNotice($order)
    {
        // 这里可以发送模板消息、短信等通知
        // 暂时省略具体实现
    }
}
