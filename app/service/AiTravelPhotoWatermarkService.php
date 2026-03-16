<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoResult;
use app\common\OssHelper;
use think\facade\Cache;

/**
 * 水印处理服务类
 * 为AI生成的图片添加水印
 */
class AiTravelPhotoWatermarkService
{
    protected $ossHelper;
    protected $config;

    public function __construct()
    {
        $this->ossHelper = new OssHelper();
        $this->config = config('ai_travel_photo.watermark');
    }

    /**
     * 为结果图片添加水印
     * 
     * @param int $resultId 结果ID
     * @param array $options 水印选项
     * @return array
     * @throws \Exception
     */
    public function addWatermark(int $resultId, array $options = []): array
    {
        $result = AiTravelPhotoResult::find($resultId);
        
        if (!$result) {
            throw new \Exception('结果记录不存在');
        }
        
        if ($result->content_type != AiTravelPhotoResult::CONTENT_TYPE_IMAGE) {
            throw new \Exception('仅支持为图片添加水印');
        }
        
        // 如果已有水印版本，直接返回
        if (!empty($result->result_url_watermark)) {
            return [
                'result_id' => $resultId,
                'watermark_url' => $result->result_url_watermark,
                'original_url' => $result->result_url,
            ];
        }
        
        try {
            // 下载原图
            $tmpFile = $this->downloadImage($result->result_url);
            
            // 添加水印
            $watermarkedFile = $this->processWatermark($tmpFile, $options);
            
            // 上传水印图
            $watermarkUrl = $this->uploadWatermarkedImage($watermarkedFile, $result);
            
            // 更新记录
            $result->result_url_watermark = $watermarkUrl;
            $result->save();
            
            // 清理临时文件
            @unlink($tmpFile);
            @unlink($watermarkedFile);
            
            return [
                'result_id' => $resultId,
                'watermark_url' => $watermarkUrl,
                'original_url' => $result->result_url,
            ];
            
        } catch (\Exception $e) {
            throw new \Exception('水印处理失败：' . $e->getMessage());
        }
    }

    /**
     * 批量添加水印
     * 
     * @param array $resultIds 结果ID数组
     * @param array $options 水印选项
     * @return array
     */
    public function batchAddWatermark(array $resultIds, array $options = []): array
    {
        $successCount = 0;
        $failedCount = 0;
        $results = [];
        
        foreach ($resultIds as $resultId) {
            try {
                $result = $this->addWatermark($resultId, $options);
                $results[] = $result;
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                trace('批量水印处理失败：ID=' . $resultId . ', ' . $e->getMessage(), 'error');
            }
        }
        
        return [
            'total' => count($resultIds),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'results' => $results,
        ];
    }

    /**
     * 处理水印
     * 
     * @param string $sourceFile 源文件路径
     * @param array $options 水印选项
     * @return string 处理后文件路径
     * @throws \Exception
     */
    private function processWatermark(string $sourceFile, array $options): string
    {
        // 加载图片
        $imageInfo = getimagesize($sourceFile);
        if (!$imageInfo) {
            throw new \Exception('无法读取图片信息');
        }
        
        $imageType = $imageInfo[2];
        
        // 根据图片类型创建图片资源
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourceFile);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourceFile);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourceFile);
                break;
            default:
                throw new \Exception('不支持的图片格式');
        }
        
        if (!$sourceImage) {
            throw new \Exception('图片资源创建失败');
        }
        
        $imageWidth = imagesx($sourceImage);
        $imageHeight = imagesy($sourceImage);
        
        // 获取水印类型
        $watermarkType = $options['type'] ?? $this->config['type'] ?? 'text';
        
        if ($watermarkType == 'image') {
            // 图片水印
            $this->addImageWatermark($sourceImage, $imageWidth, $imageHeight, $options);
        } else {
            // 文字水印
            $this->addTextWatermark($sourceImage, $imageWidth, $imageHeight, $options);
        }
        
        // 保存处理后的图片
        $outputFile = runtime_path() . 'temp/watermark_' . uniqid() . '.jpg';
        imagejpeg($sourceImage, $outputFile, $this->config['quality'] ?? 90);
        
        // 释放资源
        imagedestroy($sourceImage);
        
        return $outputFile;
    }

    /**
     * 添加文字水印
     * 
     * @param resource $image 图片资源
     * @param int $imageWidth 图片宽度
     * @param int $imageHeight 图片高度
     * @param array $options 选项
     * @return void
     */
    private function addTextWatermark($image, int $imageWidth, int $imageHeight, array $options): void
    {
        $text = $options['text'] ?? $this->config['text'] ?? 'AI Travel Photo';
        $fontSize = $options['font_size'] ?? $this->config['font_size'] ?? 20;
        $fontFile = $options['font_file'] ?? $this->config['font_file'] ?? null;
        $position = $options['position'] ?? $this->config['position'] ?? 'bottom_right';
        $opacity = $options['opacity'] ?? $this->config['opacity'] ?? 50;
        
        // 如果没有字体文件，使用内置字体
        if (!$fontFile || !file_exists($fontFile)) {
            $fontFile = root_path() . 'public/static/fonts/simhei.ttf';
        }
        
        // 计算文字边界
        if (file_exists($fontFile)) {
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);
        } else {
            // 使用内置字体的近似值
            $textWidth = strlen($text) * $fontSize * 0.6;
            $textHeight = $fontSize;
        }
        
        // 计算水印位置
        list($x, $y) = $this->calculatePosition($position, $imageWidth, $imageHeight, $textWidth, $textHeight);
        
        // 创建颜色（带透明度）
        $color = imagecolorallocatealpha($image, 255, 255, 255, 127 - ($opacity * 1.27));
        
        // 绘制文字
        if (file_exists($fontFile)) {
            imagettftext($image, $fontSize, 0, $x, $y, $color, $fontFile, $text);
        } else {
            // 使用内置字体
            imagestring($image, 5, $x, $y, $text, $color);
        }
    }

    /**
     * 添加图片水印
     * 
     * @param resource $image 图片资源
     * @param int $imageWidth 图片宽度
     * @param int $imageHeight 图片高度
     * @param array $options 选项
     * @return void
     * @throws \Exception
     */
    private function addImageWatermark($image, int $imageWidth, int $imageHeight, array $options): void
    {
        $watermarkFile = $options['watermark_image'] ?? $this->config['watermark_image'] ?? null;
        
        if (!$watermarkFile || !file_exists($watermarkFile)) {
            throw new \Exception('水印图片文件不存在');
        }
        
        // 加载水印图片
        $watermarkInfo = getimagesize($watermarkFile);
        $watermarkType = $watermarkInfo[2];
        
        switch ($watermarkType) {
            case IMAGETYPE_PNG:
                $watermarkImage = imagecreatefrompng($watermarkFile);
                break;
            case IMAGETYPE_JPEG:
                $watermarkImage = imagecreatefromjpeg($watermarkFile);
                break;
            case IMAGETYPE_GIF:
                $watermarkImage = imagecreatefromgif($watermarkFile);
                break;
            default:
                throw new \Exception('不支持的水印图片格式');
        }
        
        $watermarkWidth = imagesx($watermarkImage);
        $watermarkHeight = imagesy($watermarkImage);
        
        // 计算缩放比例（如果水印太大）
        $maxWatermarkWidth = $imageWidth * 0.3;
        $maxWatermarkHeight = $imageHeight * 0.3;
        
        if ($watermarkWidth > $maxWatermarkWidth || $watermarkHeight > $maxWatermarkHeight) {
            $scale = min($maxWatermarkWidth / $watermarkWidth, $maxWatermarkHeight / $watermarkHeight);
            $newWatermarkWidth = (int)($watermarkWidth * $scale);
            $newWatermarkHeight = (int)($watermarkHeight * $scale);
            
            // 创建缩放后的水印
            $scaledWatermark = imagecreatetruecolor($newWatermarkWidth, $newWatermarkHeight);
            imagealphablending($scaledWatermark, false);
            imagesavealpha($scaledWatermark, true);
            imagecopyresampled($scaledWatermark, $watermarkImage, 0, 0, 0, 0, 
                $newWatermarkWidth, $newWatermarkHeight, $watermarkWidth, $watermarkHeight);
            
            imagedestroy($watermarkImage);
            $watermarkImage = $scaledWatermark;
            $watermarkWidth = $newWatermarkWidth;
            $watermarkHeight = $newWatermarkHeight;
        }
        
        $position = $options['position'] ?? $this->config['position'] ?? 'bottom_right';
        $opacity = $options['opacity'] ?? $this->config['opacity'] ?? 50;
        
        // 计算位置
        list($x, $y) = $this->calculatePosition($position, $imageWidth, $imageHeight, $watermarkWidth, $watermarkHeight);
        
        // 合并图片
        imagecopymerge($image, $watermarkImage, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight, $opacity);
        
        imagedestroy($watermarkImage);
    }

    /**
     * 计算水印位置
     * 
     * @param string $position 位置标识
     * @param int $imageWidth 图片宽度
     * @param int $imageHeight 图片高度
     * @param int $watermarkWidth 水印宽度
     * @param int $watermarkHeight 水印高度
     * @return array [x, y]
     */
    private function calculatePosition(string $position, int $imageWidth, int $imageHeight, 
        int $watermarkWidth, int $watermarkHeight): array
    {
        $padding = $this->config['padding'] ?? 10;
        
        switch ($position) {
            case 'top_left':
                $x = $padding;
                $y = $padding + $watermarkHeight;
                break;
            case 'top_center':
                $x = ($imageWidth - $watermarkWidth) / 2;
                $y = $padding + $watermarkHeight;
                break;
            case 'top_right':
                $x = $imageWidth - $watermarkWidth - $padding;
                $y = $padding + $watermarkHeight;
                break;
            case 'center':
                $x = ($imageWidth - $watermarkWidth) / 2;
                $y = ($imageHeight - $watermarkHeight) / 2 + $watermarkHeight;
                break;
            case 'bottom_left':
                $x = $padding;
                $y = $imageHeight - $padding;
                break;
            case 'bottom_center':
                $x = ($imageWidth - $watermarkWidth) / 2;
                $y = $imageHeight - $padding;
                break;
            case 'bottom_right':
            default:
                $x = $imageWidth - $watermarkWidth - $padding;
                $y = $imageHeight - $padding;
                break;
        }
        
        return [(int)$x, (int)$y];
    }

    /**
     * 下载图片到本地临时文件
     * 
     * @param string $imageUrl 图片URL
     * @return string 本地文件路径
     * @throws \Exception
     */
    private function downloadImage(string $imageUrl): string
    {
        $tmpFile = runtime_path() . 'temp/download_' . uniqid() . '.jpg';
        
        $content = file_get_contents($imageUrl);
        if ($content === false) {
            throw new \Exception('图片下载失败');
        }
        
        file_put_contents($tmpFile, $content);
        
        return $tmpFile;
    }

    /**
     * 上传水印图片到OSS
     * 
     * @param string $localFile 本地文件路径
     * @param AiTravelPhotoResult $result 结果对象
     * @return string OSS URL
     */
    private function uploadWatermarkedImage(string $localFile, $result): string
    {
        $ossPath = config('ai_travel_photo.oss.ai_travel_photo_path', 'ai_travel_photo/') .
            "watermark/{$result->portrait->md5}_{$result->scene_id}_" . time() . "_wm.jpg";
        
        return $this->ossHelper->uploadFile($localFile, $ossPath);
    }

    /**
     * 移除水印版本（恢复为无水印）
     * 
     * @param int $resultId 结果ID
     * @return bool
     * @throws \Exception
     */
    public function removeWatermark(int $resultId): bool
    {
        $result = AiTravelPhotoResult::find($resultId);
        
        if (!$result) {
            throw new \Exception('结果记录不存在');
        }
        
        if (empty($result->result_url_watermark)) {
            return true;
        }
        
        // 删除OSS上的水印图片（可选）
        // $this->ossHelper->deleteFile($result->result_url_watermark);
        
        // 清空水印URL
        $result->result_url_watermark = '';
        return $result->save();
    }

    /**
     * 预览水印效果（不保存）
     * 
     * @param int $resultId 结果ID
     * @param array $options 水印选项
     * @return string Base64编码的图片
     * @throws \Exception
     */
    public function previewWatermark(int $resultId, array $options = []): string
    {
        $result = AiTravelPhotoResult::find($resultId);
        
        if (!$result || $result->content_type != AiTravelPhotoResult::CONTENT_TYPE_IMAGE) {
            throw new \Exception('结果记录不存在或不是图片');
        }
        
        // 下载原图
        $tmpFile = $this->downloadImage($result->result_url);
        
        // 添加水印
        $watermarkedFile = $this->processWatermark($tmpFile, $options);
        
        // 读取为Base64
        $imageData = file_get_contents($watermarkedFile);
        $base64 = 'data:image/jpeg;base64,' . base64_encode($imageData);
        
        // 清理临时文件
        @unlink($tmpFile);
        @unlink($watermarkedFile);
        
        return $base64;
    }
}
