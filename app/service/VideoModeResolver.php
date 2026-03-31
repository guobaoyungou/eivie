<?php
/**
 * 视频生成模式推断与模型能力校验服务
 * 
 * 根据用户输入和模型代码自动推断视频生成模式，
 * 并验证模型是否支持该模式。
 * 
 * 视频生成模式：
 * - text_to_video: 文生视频（纯文本描述生成视频）
 * - first_frame: 首帧图生视频（首帧图片驱动生成）
 * - first_last_frame: 首尾帧图生视频（首帧+尾帧过渡视频）
 * - reference_images: 参考图生视频（1-4张参考图片驱动生成，仅1.0 Lite i2v支持）
 * 
 * @package app\service
 * @author AI旅拍开发团队
 * @date 2026-02-28
 */

namespace app\service;

class VideoModeResolver
{
    /**
     * 视频模式常量定义
     */
    const MODE_TEXT_TO_VIDEO = 'text_to_video';
    const MODE_FIRST_FRAME = 'first_frame';
    const MODE_FIRST_LAST_FRAME = 'first_last_frame';
    const MODE_REFERENCE_IMAGES = 'reference_images';
    const MODE_CAMERA_MOTION = 'camera_motion';
    
    /**
     * 模型能力矩阵
     * 定义每个模型支持的视频生成模式
     */
    private static $modelCapabilities = [
        'doubao-seedance-1-5-pro' => [
            self::MODE_TEXT_TO_VIDEO => true,
            self::MODE_FIRST_FRAME => true,
            self::MODE_FIRST_LAST_FRAME => true,
            self::MODE_REFERENCE_IMAGES => false,
            'with_audio' => true,
            'max_duration' => 10,
            'resolutions' => ['720p', '1080p']
        ],
        'doubao-seedance-1-0-pro' => [
            self::MODE_TEXT_TO_VIDEO => true,
            self::MODE_FIRST_FRAME => true,
            self::MODE_FIRST_LAST_FRAME => true,
            self::MODE_REFERENCE_IMAGES => false,
            'with_audio' => false,
            'max_duration' => 10,
            'resolutions' => ['720p', '1080p']
        ],
        'doubao-seedance-1-0-pro-fast' => [
            self::MODE_TEXT_TO_VIDEO => true,
            self::MODE_FIRST_FRAME => true,
            self::MODE_FIRST_LAST_FRAME => false,
            self::MODE_REFERENCE_IMAGES => false,
            'with_audio' => false,
            'max_duration' => 5,
            'resolutions' => ['720p']
        ],
        'doubao-seedance-1-0-lite-t2v' => [
            self::MODE_TEXT_TO_VIDEO => true,
            self::MODE_FIRST_FRAME => false,
            self::MODE_FIRST_LAST_FRAME => false,
            self::MODE_REFERENCE_IMAGES => false,
            'with_audio' => false,
            'max_duration' => 5,
            'resolutions' => ['720p']
        ],
        'doubao-seedance-1-0-lite-i2v' => [
            self::MODE_TEXT_TO_VIDEO => false,
            self::MODE_FIRST_FRAME => true,
            self::MODE_FIRST_LAST_FRAME => true,
            self::MODE_REFERENCE_IMAGES => true,
            'with_audio' => false,
            'max_duration' => 5,
            'max_reference_images' => 4,
            'resolutions' => ['720p']
        ],
        // 兼容旧的model_code
        'doubao-seedance-2-0' => [
            self::MODE_TEXT_TO_VIDEO => true,
            self::MODE_FIRST_FRAME => true,
            self::MODE_FIRST_LAST_FRAME => true,
            self::MODE_REFERENCE_IMAGES => false,
            'with_audio' => true,
            'max_duration' => 10,
            'resolutions' => ['720p', '1080p']
        ],
        // 爱诗科技 PixVerse V5.6 视频生成模型（通过阿里云百炼DashScope接入）
        // 图生视频-基于首帧：上传1张图片作为首帧，prompt可选
        'pixverse/pixverse-v5.6-it2v' => [
            self::MODE_TEXT_TO_VIDEO => false,
            self::MODE_FIRST_FRAME => true,
            self::MODE_FIRST_LAST_FRAME => false,
            self::MODE_REFERENCE_IMAGES => false,
            'with_audio' => true,
            'max_duration' => 10,
            'durations' => [5, 8, 10],
            'resolutions' => ['360P', '540P', '720P', '1080P']
        ],
        // 首尾帧生视频：上传首帧+尾帧图片，prompt必填
        'pixverse/pixverse-v5.6-kf2v' => [
            self::MODE_TEXT_TO_VIDEO => false,
            self::MODE_FIRST_FRAME => true,
            self::MODE_FIRST_LAST_FRAME => true,
            self::MODE_REFERENCE_IMAGES => false,
            'with_audio' => true,
            'max_duration' => 10,
            'durations' => [5, 8, 10],
            'resolutions' => ['360P', '540P', '720P', '1080P']
        ],
        // 参考生视频：上传1-7张参考图片，prompt必填
        'pixverse/pixverse-v5.6-r2v' => [
            self::MODE_TEXT_TO_VIDEO => false,
            self::MODE_FIRST_FRAME => false,
            self::MODE_FIRST_LAST_FRAME => false,
            self::MODE_REFERENCE_IMAGES => true,
            self::MODE_CAMERA_MOTION => false,
            'with_audio' => true,
            'max_duration' => 10,
            'max_reference_images' => 7,
            'durations' => [5, 8, 10],
            'resolutions' => ['360P', '540P', '720P', '1080P']
        ],
        // 即梦AI 视频生成3.0 720P（火山引擎CV API）
        'jimeng_video_v30_720p' => [
            self::MODE_TEXT_TO_VIDEO => true,
            self::MODE_FIRST_FRAME => true,
            self::MODE_FIRST_LAST_FRAME => true,
            self::MODE_REFERENCE_IMAGES => false,
            self::MODE_CAMERA_MOTION => true,
            'with_audio' => false,
            'max_duration' => 10,
            'resolutions' => ['720P']
        ],
        // 即梦AI 视频生成3.0 1080P（火山引擎CV API）
        'jimeng_video_v30_1080p' => [
            self::MODE_TEXT_TO_VIDEO => true,
            self::MODE_FIRST_FRAME => true,
            self::MODE_FIRST_LAST_FRAME => true,
            self::MODE_REFERENCE_IMAGES => false,
            self::MODE_CAMERA_MOTION => false,
            'with_audio' => false,
            'max_duration' => 10,
            'resolutions' => ['1080P']
        ]
    ];
    
    /**
     * 根据用户输入推断视频生成模式
     * 
     * 推断优先级：
     * 1. 有reference_images参数 → reference_images模式
     * 2. 有first_frame_image参数：
     *    - 同时有last_frame_image → first_last_frame模式
     *    - 没有last_frame_image → first_frame模式
     * 3. 其他情况 → text_to_video模式
     * 
     * @param string $modelCode 模型代码
     * @param array $inputParams 用户输入参数
     * @return array ['mode' => string, 'valid' => bool, 'message' => string]
     */
    public function resolve($modelCode, array $inputParams)
    {
        // 将完整模型标识归一化为基础model_code（去掉日期后缀）
        $baseModelCode = VolcengineVideoService::normalizeModelCode($modelCode);
        
        // 检查模型是否存在
        if (!isset(self::$modelCapabilities[$baseModelCode])) {
            return [
                'mode' => null,
                'valid' => false,
                'message' => '不支持的模型代码: ' . $modelCode
            ];
        }
        
        // 推断模式
        $mode = $this->inferMode($inputParams);
        
        // 验证模式是否受支持
        $validation = $this->validateCapability($baseModelCode, $mode);
        
        if (!$validation['valid']) {
            return [
                'mode' => $mode,
                'valid' => false,
                'message' => $validation['message']
            ];
        }
        
        // 特殊验证：有声视频
        if (!empty($inputParams['with_audio'])) {
            if (!$this->supportsAudio($baseModelCode)) {
                return [
                    'mode' => $mode,
                    'valid' => false,
                    'message' => '模型 ' . $modelCode . ' 不支持有声视频，仅 doubao-seedance-1-5-pro 支持此功能'
                ];
            }
        }
        
        // 特殊验证：参考图数量
        if ($mode === self::MODE_REFERENCE_IMAGES) {
            $refImages = $inputParams['reference_images'] ?? [];
            $maxImages = self::$modelCapabilities[$baseModelCode]['max_reference_images'] ?? 4;
            
            if (count($refImages) < 1) {
                return [
                    'mode' => $mode,
                    'valid' => false,
                    'message' => '参考图生成模式至少需要1张参考图'
                ];
            }
            
            if (count($refImages) > $maxImages) {
                return [
                    'mode' => $mode,
                    'valid' => false,
                    'message' => '参考图数量不能超过' . $maxImages . '张'
                ];
            }
        }
        
        return [
            'mode' => $mode,
            'valid' => true,
            'message' => 'OK'
        ];
    }
    
    /**
     * 根据输入参数推断视频生成模式
     * 
     * @param array $inputParams 输入参数
     * @return string 视频模式
     */
    private function inferMode(array $inputParams)
    {
        // 1. 检查参考图模式
        if (!empty($inputParams['reference_images']) && is_array($inputParams['reference_images'])) {
            return self::MODE_REFERENCE_IMAGES;
        }
        
        // 2. 检查运镜模式（有首帧图 + camera_type参数）
        $hasCameraType = !empty($inputParams['camera_type']);
        
        // 3. 检查首帧图模式
        $hasFirstFrame = !empty($inputParams['first_frame_image']) || 
                         !empty($inputParams['image_url']) ||
                         !empty($inputParams['image']);
        
        if ($hasFirstFrame) {
            // 3a. 检查是否为运镜模式（首帧图 + camera_type）
            if ($hasCameraType) {
                return self::MODE_CAMERA_MOTION;
            }
            
            // 3b. 检查是否有尾帧图
            $hasLastFrame = !empty($inputParams['last_frame_image']) ||
                           !empty($inputParams['tail_image_url']) ||
                           !empty($inputParams['last_frame']);
            
            if ($hasLastFrame) {
                return self::MODE_FIRST_LAST_FRAME;
            }
            
            return self::MODE_FIRST_FRAME;
        }
        
        // 4. 默认为文生视频
        return self::MODE_TEXT_TO_VIDEO;
    }
    
    /**
     * 验证模型是否支持指定的视频生成模式
     * 
     * @param string $modelCode 模型代码
     * @param string $videoMode 视频模式
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateCapability($modelCode, $videoMode)
    {
        $modelCode = VolcengineVideoService::normalizeModelCode($modelCode);
        if (!isset(self::$modelCapabilities[$modelCode])) {
            return [
                'valid' => false,
                'message' => '不支持的模型代码: ' . $modelCode
            ];
        }
        
        $capabilities = self::$modelCapabilities[$modelCode];
        
        if (!isset($capabilities[$videoMode]) || !$capabilities[$videoMode]) {
            $modeNames = [
                self::MODE_TEXT_TO_VIDEO => '文生视频',
                self::MODE_FIRST_FRAME => '首帧图生视频',
                self::MODE_FIRST_LAST_FRAME => '首尾帧图生视频',
                self::MODE_REFERENCE_IMAGES => '参考图生视频'
            ];
            
            $modeName = $modeNames[$videoMode] ?? $videoMode;
            
            return [
                'valid' => false,
                'message' => '模型 ' . $modelCode . ' 不支持' . $modeName . '模式'
            ];
        }
        
        return ['valid' => true, 'message' => 'OK'];
    }
    
    /**
     * 获取模型的所有能力
     * 
     * @param string $modelCode 模型代码
     * @return array
     */
    public function getModelCapabilities($modelCode)
    {
        $modelCode = VolcengineVideoService::normalizeModelCode($modelCode);
        return self::$modelCapabilities[$modelCode] ?? [];
    }
    
    /**
     * 获取模型支持的所有视频模式
     * 
     * @param string $modelCode 模型代码
     * @return array
     */
    public function getSupportedModes($modelCode)
    {
        $modelCode = VolcengineVideoService::normalizeModelCode($modelCode);
        if (!isset(self::$modelCapabilities[$modelCode])) {
            return [];
        }
        
        $modes = [];
        $capabilities = self::$modelCapabilities[$modelCode];
        
        $allModes = [
            self::MODE_TEXT_TO_VIDEO,
            self::MODE_FIRST_FRAME,
            self::MODE_FIRST_LAST_FRAME,
            self::MODE_REFERENCE_IMAGES,
            self::MODE_CAMERA_MOTION
        ];
        
        foreach ($allModes as $mode) {
            if (!empty($capabilities[$mode])) {
                $modes[] = $mode;
            }
        }
        
        return $modes;
    }
    
    /**
     * 检查模型是否支持有声视频
     * 
     * @param string $modelCode 模型代码
     * @return bool
     */
    public function supportsAudio($modelCode)
    {
        $modelCode = VolcengineVideoService::normalizeModelCode($modelCode);
        $capabilities = self::$modelCapabilities[$modelCode] ?? [];
        return !empty($capabilities['with_audio']);
    }
    
    /**
     * 获取模型最大视频时长
     * 
     * @param string $modelCode 模型代码
     * @return int 秒数
     */
    public function getMaxDuration($modelCode)
    {
        $modelCode = VolcengineVideoService::normalizeModelCode($modelCode);
        $capabilities = self::$modelCapabilities[$modelCode] ?? [];
        return $capabilities['max_duration'] ?? 5;
    }
    
    /**
     * 获取模型支持的分辨率
     * 
     * @param string $modelCode 模型代码
     * @return array
     */
    public function getSupportedResolutions($modelCode)
    {
        $modelCode = VolcengineVideoService::normalizeModelCode($modelCode);
        $capabilities = self::$modelCapabilities[$modelCode] ?? [];
        return $capabilities['resolutions'] ?? ['720p'];
    }
    
    /**
     * 获取所有支持的模型列表
     * 
     * @return array
     */
    public static function getAllSupportedModels()
    {
        return array_keys(self::$modelCapabilities);
    }
    
    /**
     * 根据模式获取可用的模型列表
     * 
     * @param string $videoMode 视频模式
     * @return array
     */
    public function getModelsForMode($videoMode)
    {
        $models = [];
        
        foreach (self::$modelCapabilities as $modelCode => $capabilities) {
            if (!empty($capabilities[$videoMode])) {
                $models[] = $modelCode;
            }
        }
        
        return $models;
    }
    
    /**
     * 验证时长参数
     * 
     * @param string $modelCode 模型代码
     * @param int $duration 时长（秒）
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateDuration($modelCode, $duration)
    {
        $maxDuration = $this->getMaxDuration($modelCode);
        
        if ($duration < 1) {
            return ['valid' => false, 'message' => '视频时长不能小于1秒'];
        }
        
        if ($duration > $maxDuration) {
            return [
                'valid' => false, 
                'message' => '模型 ' . $modelCode . ' 最大支持' . $maxDuration . '秒视频'
            ];
        }
        
        return ['valid' => true, 'message' => 'OK'];
    }
    
    /**
     * 验证分辨率参数
     * 
     * @param string $modelCode 模型代码
     * @param string $resolution 分辨率
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateResolution($modelCode, $resolution)
    {
        $supportedResolutions = $this->getSupportedResolutions($modelCode);
        
        if (!in_array($resolution, $supportedResolutions)) {
            return [
                'valid' => false,
                'message' => '模型 ' . $modelCode . ' 不支持 ' . $resolution . ' 分辨率，支持的分辨率: ' . implode(', ', $supportedResolutions)
            ];
        }
        
        return ['valid' => true, 'message' => 'OK'];
    }
    
    /**
     * 获取视频模式的中文描述
     * 
     * @param string $videoMode 视频模式
     * @return string
     */
    public static function getModeDescription($videoMode)
    {
        $descriptions = [
            self::MODE_TEXT_TO_VIDEO => '文生视频（纯文本描述生成视频）',
            self::MODE_FIRST_FRAME => '首帧图生视频（首帧图片驱动生成）',
            self::MODE_FIRST_LAST_FRAME => '首尾帧图生视频（首帧+尾帧过渡视频）',
            self::MODE_REFERENCE_IMAGES => '参考图生视频（1-4张参考图片驱动生成）',
            self::MODE_CAMERA_MOTION => '运镜图生视频（首帧图片+运镜控制生成视频）'
        ];
        
        return $descriptions[$videoMode] ?? $videoMode;
    }
    
    /**
     * 获取模式所需的必填参数
     * 
     * @param string $videoMode 视频模式
     * @return array
     */
    public static function getRequiredParams($videoMode)
    {
        $required = [
            self::MODE_TEXT_TO_VIDEO => ['prompt'],
            self::MODE_FIRST_FRAME => ['first_frame_image'],
            self::MODE_FIRST_LAST_FRAME => ['first_frame_image', 'last_frame_image'],
            self::MODE_REFERENCE_IMAGES => ['reference_images'],
            self::MODE_CAMERA_MOTION => ['first_frame_image', 'camera_type']
        ];
        
        return $required[$videoMode] ?? [];
    }
    
    /**
     * 验证必填参数
     * 
     * @param string $videoMode 视频模式
     * @param array $inputParams 输入参数
     * @return array ['valid' => bool, 'message' => string, 'missing' => array]
     */
    public function validateRequiredParams($videoMode, array $inputParams)
    {
        $required = self::getRequiredParams($videoMode);
        $missing = [];
        
        foreach ($required as $param) {
            if (empty($inputParams[$param])) {
                // 检查别名参数
                $aliases = $this->getParamAliases($param);
                $found = false;
                foreach ($aliases as $alias) {
                    if (!empty($inputParams[$alias])) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $missing[] = $param;
                }
            }
        }
        
        if (!empty($missing)) {
            return [
                'valid' => false,
                'message' => '缺少必填参数: ' . implode(', ', $missing),
                'missing' => $missing
            ];
        }
        
        return ['valid' => true, 'message' => 'OK', 'missing' => []];
    }
    
    /**
     * 获取参数别名
     * 
     * @param string $param 参数名
     * @return array
     */
    private function getParamAliases($param)
    {
        $aliases = [
            'first_frame_image' => ['image_url', 'image', 'input_image'],
            'last_frame_image' => ['tail_image_url', 'last_frame', 'end_image'],
            'reference_images' => ['ref_images', 'ref_imgs'],
            'prompt' => ['text', 'description'],
            'camera_type' => ['camera_motion_type', 'motion_type']
        ];
        
        return $aliases[$param] ?? [];
    }
}
