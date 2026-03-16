<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use app\model\AiTravelPhotoResult;
use app\model\AiTravelPhotoQrcode;

/**
 * AI旅拍合成服务
 * 处理图像合成、模板选择、水印添加等功能
 */
class AiTravelPhotoSynthesisService
{
    protected $watermarkService = null;
    protected $watermarkEnabled = false;

    public function __construct()
    {
        // 延迟初始化水印服务，避免OSS未配置时报错
        try {
            $businessSet = Db::name('business')->where('id', '>', 0)->find();
            if ($businessSet && !empty($businessSet['ai_logo_watermark'])) {
                $this->watermarkService = new AiTravelPhotoWatermarkService();
                $this->watermarkEnabled = true;
            }
        } catch (\Exception $e) {
            // 水印服务初始化失败，跳过水印功能
            $this->watermarkEnabled = false;
        }
    }

    /**
     * 执行合成生成
     *
     * @param array $portrait 人像信息
     * @param array $templates 模板列表
     * @return array
     */
    public function generate(array $portrait, array $templates): array
    {
        try {
            $portraitId = $portrait['id'];
            $portraitUrl = $portrait['original_url'] ?? $portrait['cutout_url'] ?? '';

            // 获取人像的bid
            $bid = $portrait['bid'] ?? 0;

            // 获取商家设置的水印配置
            $business = Db::name('business')
                ->where('id', $bid)
                ->find();

            $generatedResults = [];

            // 遍历模板生成图片
            foreach ($templates as $template) {
                // 调用AI模型生成图片
                $generatedUrl = $this->callAiModel($portraitUrl, $template);

                if (empty($generatedUrl)) {
                    continue;
                }

                // 添加水印
                $watermarkedUrl = $generatedUrl;
                // 只有水印服务初始化成功且商家开启了水印时才添加水印
                if ($this->watermarkEnabled && !empty($business['ai_logo_watermark'])) {
                    try {
                        // 先将生成的结果存入result表获取ID
                        $resultId = $this->saveResult($portraitId, $bid, $template, $generatedUrl);

                        // 添加水印
                        $watermarkResult = $this->watermarkService->addWatermark($resultId);
                        $watermarkedUrl = $watermarkResult['watermark_url'] ?? $generatedUrl;

                        // 更新水印URL
                        Db::name('ai_travel_photo_result')
                            ->where('id', $resultId)
                            ->update(['result_url_watermark' => $watermarkedUrl]);
                    } catch (\Exception $e) {
                        // 水印添加失败，使用原图
                        \think\facade\Log::error('合成图片水印添加失败: ' . $e->getMessage());
                    }
                } else {
                    // 没有设置水印，直接保存结果
                    $this->saveResult($portraitId, $bid, $template, $generatedUrl);
                }

                $generatedResults[] = [
                    'template_id' => $template['id'],
                    'template_name' => $template['name'],
                    'result_url' => $generatedUrl,
                    'watermarked_url' => $watermarkedUrl
                ];
            }

            // 存入选片表（qrcode表）
            if (!empty($generatedResults)) {
                // 获取人像信息中的aid
                $portraitInfo = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();
                $this->saveToQrcode($portraitId, $bid, $generatedResults, $portraitInfo['aid'] ?? 0);
            }

            return [
                'code' => 0,
                'msg' => '生成成功',
                'data' => [
                    'count' => count($generatedResults),
                    'results' => $generatedResults
                ]
            ];

        } catch (\Exception $e) {
            return [
                'code' => 1,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * 调用AI模型生成图片
     *
     * @param string $portraitUrl 人像图片URL
     * @param array $template 模板信息
     * @return string|null
     */
    protected function callAiModel(string $portraitUrl, array $template): ?string
    {
        // 检查人像URL是否有效
        if (empty($portraitUrl)) {
            throw new \Exception('人像图片URL为空');
        }

        // 直接返回原图URL（测试用，实际需要调用AI生成）
        // TODO: 后续接入AI图像生成服务时再查询ai_model_config表
        // TODO: 后续接入AI图像生成服务
        return $portraitUrl;
    }

    /**
     * 模拟AI生成（实际应替换为真实API调用）
     */
    protected function simulateAiGeneration(string $portraitUrl, string $templateImage, string $prompt, array $modelConfig): string
    {
        // 这里应该调用实际的AI API
        // 例如：调用通义万相、Midjourney、Stable Diffusion等

        // 暂时返回原图URL，实际需要替换为AI生成的结果
        // 后续需要接入真实的AI生成服务
        return $portraitUrl;
    }

    /**
     * 保存生成结果到result表
     *
     * @param int $portraitId 人像ID
     * @param int $bid 门店ID
     * @param array $template 模板信息
     * @param string $resultUrl 生成结果URL
     * @return int
     */
    protected function saveResult(int $portraitId, int $bid, array $template, string $resultUrl): int
    {
        // 获取人像信息中的aid
        $portrait = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();

        $data = [
            'aid' => $portrait['aid'] ?? 0,
            'bid' => $bid,
            'portrait_id' => $portraitId,
            'template_id' => $template['id'],
            'template_name' => $template['name'],
            'model_id' => $template['model_id'] ?? 0,
            'model_name' => $template['model_name'] ?? '',
            'prompt' => $template['prompt'] ?? '',
            'result_url' => $resultUrl,
            'content_type' => 1, // 图片类型
            'status' => 1,
            'create_time' => time(),
            'update_time' => time()
        ];

        return Db::name('ai_travel_photo_result')->insertGetId($data);
    }

    /**
     * 存入选片表（qrcode表）
     * 将生成的图片关联到选片记录
     *
     * @param int $portraitId 人像ID
     * @param int $bid 门店ID
     * @param array $results 生成结果
     */
    protected function saveToQrcode(int $portraitId, int $bid, array $results, int $aid = 0): void
    {
        // 检查是否已有选片记录
        $qrcode = Db::name('ai_travel_photo_qrcode')
            ->where('portrait_id', $portraitId)
            ->find();

        if (!$qrcode) {
            // 创建新的选片记录
            $qrcodeId = Db::name('ai_travel_photo_qrcode')->insertGetId([
                'aid' => $aid,
                'bid' => $bid,
                'portrait_id' => $portraitId,
                'status' => 1, // 有效状态
                'create_time' => time(),
                'update_time' => time()
            ]);
        } else {
            $qrcodeId = $qrcode['id'];
        }

        // 更新选片记录中的生成结果
        // 将生成的水印图URL保存到result表，并在qrcode中记录
        $resultUrls = array_column($results, 'watermarked_url');

        Db::name('ai_travel_photo_qrcode')
            ->where('id', $qrcodeId)
            ->update([
                'update_time' => time()
            ]);
    }

    /**
     * 获取合成模板列表
     *
     * @param int $aid 商户ID
     * @param int $bid 门店ID
     * @return array
     */
    public function getTemplateList(int $aid, int $bid): array
    {
        $where = [
            ['aid', '=', $aid],
            ['bid', '=', $bid],
            ['status', '=', 1]
        ];

        $templates = Db::name('ai_travel_photo_synthesis_template')
            ->where($where)
            ->order('sort ASC, id DESC')
            ->select();

        return $templates->toArray();
    }

    /**
     * 获取合成设置
     *
     * @param int $portraitId 人像ID
     * @return array|null
     */
    public function getSetting(int $portraitId): ?array
    {
        $setting = Db::name('ai_travel_photo_synthesis_setting')
            ->where('portrait_id', $portraitId)
            ->find();

        return $setting ?: null;
    }
}
