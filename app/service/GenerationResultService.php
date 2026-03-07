<?php
/**
 * 生成结果处理服务
 * 
 * @package app\service
 * @author AI Assistant
 * @date 2026-02-04
 */

namespace app\service;

use think\facade\Db;
use app\model\AiTravelPhotoResult;
use app\model\AiTravelPhotoGeneration;

class GenerationResultService
{
    /**
     * 保存多图输出结果
     * 
     * @param int $generationId 生成记录ID
     * @param array $apiResponse API返回的结果数据
     * @param array $sceneInfo 场景信息
     * @return array
     */
    public function saveMultiImageResults($generationId, $apiResponse, $sceneInfo)
    {
        try {
            // 查询生成记录
            $generation = AiTravelPhotoGeneration::find($generationId);
            if (!$generation) {
                throw new \Exception('生成记录不存在');
            }
            
            // 解析API响应中的多图结果
            // 假设API返回格式：
            // {
            //   "output": {
            //     "results": [
            //       {"url": "https://example.com/result_1.jpg"},
            //       {"url": "https://example.com/result_2.jpg"},
            //       ...
            //     ]
            //   }
            // }
            $results = $apiResponse['output']['results'] ?? [];
            
            if (empty($results)) {
                throw new \Exception('API返回结果为空');
            }
            
            $savedResults = [];
            
            // 批量保存结果
            foreach ($results as $index => $item) {
                $resultData = [
                    'aid' => $generation['aid'],
                    'generation_id' => $generationId,
                    'portrait_id' => $generation['portrait_id'],
                    'scene_id' => $generation['scene_id'],
                    'type' => $index + 1, // type字段：1-6对应第1-6张图
                    'url' => $item['url'] ?? '',
                    'watermark_url' => $item['watermark_url'] ?? '',
                    'file_size' => $item['file_size'] ?? 0,
                    'width' => $item['width'] ?? 0,
                    'height' => $item['height'] ?? 0,
                    'format' => $item['format'] ?? 'jpg',
                    'status' => 1,
                    'create_time' => time()
                ];
                
                $resultId = AiTravelPhotoResult::insertGetId($resultData);
                $savedResults[] = array_merge($resultData, ['id' => $resultId]);
            }
            
            return [
                'success' => true,
                'count' => count($savedResults),
                'results' => $savedResults
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 保存单图输出结果
     * 
     * @param int $generationId 生成记录ID
     * @param array $apiResponse API返回的结果数据
     * @param array $sceneInfo 场景信息
     * @return array
     */
    public function saveSingleImageResult($generationId, $apiResponse, $sceneInfo)
    {
        try {
            // 查询生成记录
            $generation = AiTravelPhotoGeneration::find($generationId);
            if (!$generation) {
                throw new \Exception('生成记录不存在');
            }
            
            // 解析API响应
            $imageUrl = $apiResponse['output']['image_url'] ?? $apiResponse['output']['url'] ?? '';
            
            if (empty($imageUrl)) {
                throw new \Exception('API返回的图片URL为空');
            }
            
            $resultData = [
                'aid' => $generation['aid'],
                'generation_id' => $generationId,
                'portrait_id' => $generation['portrait_id'],
                'scene_id' => $generation['scene_id'],
                'type' => 1, // 单图默认type=1
                'url' => $imageUrl,
                'watermark_url' => $apiResponse['output']['watermark_url'] ?? '',
                'file_size' => $apiResponse['output']['file_size'] ?? 0,
                'width' => $apiResponse['output']['width'] ?? 0,
                'height' => $apiResponse['output']['height'] ?? 0,
                'format' => $apiResponse['output']['format'] ?? 'jpg',
                'status' => 1,
                'create_time' => time()
            ];
            
            $resultId = AiTravelPhotoResult::insertGetId($resultData);
            
            return [
                'success' => true,
                'result_id' => $resultId,
                'result' => $resultData
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 保存视频生成结果
     * 
     * @param int $generationId 生成记录ID
     * @param array $apiResponse API返回的结果数据
     * @param array $sceneInfo 场景信息
     * @return array
     */
    public function saveVideoResult($generationId, $apiResponse, $sceneInfo)
    {
        try {
            // 查询生成记录
            $generation = AiTravelPhotoGeneration::find($generationId);
            if (!$generation) {
                throw new \Exception('生成记录不存在');
            }
            
            // 解析API响应
            // 假设视频生成API返回格式：
            // {
            //   "output": {
            //     "video_url": "https://example.com/video.mp4",
            //     "cover_url": "https://example.com/cover.jpg",
            //     "duration": 5,
            //     "file_size": 5242880,
            //     "width": 1920,
            //     "height": 1080
            //   }
            // }
            $videoUrl = $apiResponse['output']['video_url'] ?? '';
            
            if (empty($videoUrl)) {
                throw new \Exception('API返回的视频URL为空');
            }
            
            $resultData = [
                'aid' => $generation['aid'],
                'generation_id' => $generationId,
                'portrait_id' => $generation['portrait_id'],
                'scene_id' => $generation['scene_id'],
                'type' => 19, // 视频类型固定为19
                'url' => $videoUrl,
                'video_cover' => $apiResponse['output']['cover_url'] ?? '',
                'video_duration' => intval($apiResponse['output']['duration'] ?? 0),
                'file_size' => intval($apiResponse['output']['file_size'] ?? 0),
                'width' => intval($apiResponse['output']['width'] ?? 1920),
                'height' => intval($apiResponse['output']['height'] ?? 1080),
                'format' => 'mp4',
                'status' => 1,
                'create_time' => time()
            ];
            
            $resultId = AiTravelPhotoResult::insertGetId($resultData);
            
            return [
                'success' => true,
                'result_id' => $resultId,
                'result' => $resultData
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 根据生成记录的scene_type自动选择保存方法
     * 
     * @param int $generationId 生成记录ID
     * @param array $apiResponse API返回的结果数据
     * @return array
     */
    public function saveResultAuto($generationId, $apiResponse)
    {
        try {
            // 查询生成记录
            $generation = AiTravelPhotoGeneration::with('scene')->find($generationId);
            if (!$generation) {
                throw new \Exception('生成记录不存在');
            }
            
            $sceneInfo = $generation->scene ? $generation->scene->toArray() : [];
            $sceneType = $generation['scene_type'];
            
            // 根据场景类型判断输出类型
            if ($sceneType >= 3 && $sceneType <= 6) {
                // 视频类型场景（3-6）
                return $this->saveVideoResult($generationId, $apiResponse, $sceneInfo);
            } else {
                // 图片类型场景（1-2）
                // 检查是否有多图输出
                $results = $apiResponse['output']['results'] ?? [];
                if (is_array($results) && count($results) > 1) {
                    // 多图输出
                    return $this->saveMultiImageResults($generationId, $apiResponse, $sceneInfo);
                } else {
                    // 单图输出
                    return $this->saveSingleImageResult($generationId, $apiResponse, $sceneInfo);
                }
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 更新生成记录状态
     * 
     * @param int $generationId 生成记录ID
     * @param int $status 状态（0待处理 1处理中 2成功 3失败）
     * @param array $extraData 额外数据（如cost_time、error_msg等）
     * @return bool
     */
    public function updateGenerationStatus($generationId, $status, $extraData = [])
    {
        try {
            $updateData = [
                'status' => $status,
                'update_time' => time()
            ];
            
            // 合并额外数据
            if (!empty($extraData)) {
                $updateData = array_merge($updateData, $extraData);
            }
            
            // 如果是成功状态，记录完成时间
            if ($status == 2) {
                $updateData['finish_time'] = time();
            }
            
            return AiTravelPhotoGeneration::where('id', $generationId)->update($updateData);
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取生成记录的所有结果
     * 
     * @param int $generationId 生成记录ID
     * @return array
     */
    public function getGenerationResults($generationId)
    {
        try {
            $results = AiTravelPhotoResult::where('generation_id', $generationId)
                ->where('status', 1)
                ->order('type ASC, id ASC')
                ->select()
                ->toArray();
            
            return [
                'success' => true,
                'results' => $results
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'results' => []
            ];
        }
    }
}
