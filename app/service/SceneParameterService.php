<?php
/**
 * 场景参数组装服务
 * 支持6种场景类型的参数组装、校验和模板生成
 * 
 * @package app\service
 * @author AI Assistant
 * @date 2026-02-06
 */

namespace app\service;

use app\model\AiTravelPhotoScene;
use think\facade\Db;
use think\exception\ValidateException;

class SceneParameterService
{
    /**
     * 组装图生图参数
     * 
     * @param array $scene 场景信息
     * @param array $portrait 人像信息
     * @param array $userParams 用户自定义参数（可选）
     * @return array
     */
    public function assembleImageGenerationParams($scene, $portrait, $userParams = [])
    {
        // 解析场景预设参数
        $sceneParams = is_string($scene['model_params']) 
            ? json_decode($scene['model_params'], true) 
            : $scene['model_params'];
        
        if (!is_array($sceneParams)) {
            $sceneParams = [];
        }
        
        // 构建input部分
        $input = [
            'image_url' => $portrait['original_url']
        ];
        
        // prompt优先级：用户输入 > 场景预设
        $input['prompt'] = $userParams['prompt'] ?? $sceneParams['prompt'] ?? '';
        
        // 多图融合模式（scene_type=2）
        if ($scene['scene_type'] == AiTravelPhotoScene::SCENE_TYPE_IMAGE_MULTI) {
            if (!empty($sceneParams['ref_img'])) {
                $input['ref_img'] = is_array($sceneParams['ref_img']) 
                    ? $sceneParams['ref_img'] 
                    : [$sceneParams['ref_img']];
            }
        }
        
        // 负面提示词
        if (!empty($sceneParams['negative_prompt'])) {
            $input['negative_prompt'] = $sceneParams['negative_prompt'];
        }
        
        // 构建parameters部分
        $parameters = [
            'n' => isset($sceneParams['n']) ? intval($sceneParams['n']) : 1,
            'size' => $sceneParams['size'] ?? '1024*1024',
            'prompt_extend' => isset($sceneParams['prompt_extend']) ? (bool)$sceneParams['prompt_extend'] : true,
            'watermark' => isset($sceneParams['watermark']) ? (bool)$sceneParams['watermark'] : false
        ];
        
        // 验证size格式
        if (!$this->validateSizeFormat($parameters['size'])) {
            $parameters['size'] = '1024*1024';
        }
        
        // 验证n的取值范围（1-6）
        if ($parameters['n'] < 1 || $parameters['n'] > 6) {
            $parameters['n'] = 1;
        }
        
        return [
            'input' => $input,
            'parameters' => $parameters
        ];
    }
    
    /**
     * 组装视频生成参数
     * 
     * @param array $scene 场景信息
     * @param array $portrait 人像信息
     * @param array $userParams 用户自定义参数（可选）
     * @return array
     */
    public function assembleVideoGenerationParams($scene, $portrait, $userParams = [])
    {
        // 解析场景预设参数
        $sceneParams = is_string($scene['model_params']) 
            ? json_decode($scene['model_params'], true) 
            : $scene['model_params'];
        
        if (!is_array($sceneParams)) {
            $sceneParams = [];
        }
        
        // 构建input部分
        $input = [
            'prompt' => $sceneParams['prompt'] ?? '',
            'image_url' => $portrait['original_url']
        ];
        
        // 根据scene_type添加特定参数
        switch ($scene['scene_type']) {
            case AiTravelPhotoScene::SCENE_TYPE_VIDEO_FIRST_LAST: // 首尾帧模式
                if (!empty($userParams['tail_image_url'])) {
                    $input['tail_image_url'] = $userParams['tail_image_url'];
                } elseif (!empty($sceneParams['tail_image_url'])) {
                    $input['tail_image_url'] = $sceneParams['tail_image_url'];
                }
                break;
                
            case AiTravelPhotoScene::SCENE_TYPE_VIDEO_EFFECT: // 特效模式
                if (!empty($userParams['video_url'])) {
                    $input['video_url'] = $userParams['video_url'];
                } elseif (!empty($sceneParams['video_url'])) {
                    $input['video_url'] = $sceneParams['video_url'];
                }
                
                if (!empty($sceneParams['effect_type'])) {
                    $input['effect_type'] = $sceneParams['effect_type'];
                }
                break;
                
            case AiTravelPhotoScene::SCENE_TYPE_VIDEO_REFERENCE: // 参考生成模式
                if (!empty($sceneParams['ref_video_url'])) {
                    $input['ref_video_url'] = $sceneParams['ref_video_url'];
                }
                break;
        }
        
        // 构建parameters部分
        $parameters = [
            'duration' => $sceneParams['duration'] ?? '5',
            'aspect_ratio' => $sceneParams['aspect_ratio'] ?? '16:9',
            'mode' => $sceneParams['mode'] ?? 'std'
        ];
        
        // 可灵AI特有参数
        if (!empty($sceneParams['cfg_scale'])) {
            $parameters['cfg_scale'] = floatval($sceneParams['cfg_scale']);
        }
        
        if (!empty($sceneParams['camera_control'])) {
            $parameters['camera_control'] = $sceneParams['camera_control'];
        }
        
        return [
            'input' => $input,
            'parameters' => $parameters
        ];
    }
    
    /**
     * 验证参数
     * 
     * @param array $scene 场景信息
     * @param array $userParams 用户输入参数
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateParameters($scene, $userParams = [])
    {
        $errors = [];
        
        // 获取场景类型的输入要求
        $sceneTypeInput = config('ai_travel_photo.scene_type_input');
        $requiredInputs = $sceneTypeInput[$scene['scene_type']] ?? [];
        
        // 检查必需的输入参数
        foreach ($requiredInputs as $requiredInput) {
            if ($requiredInput === 'image_url') {
                // image_url由portrait提供，不需要用户额外传
                continue;
            }
            
            if (empty($userParams[$requiredInput])) {
                $errors[] = "缺少必需参数：{$requiredInput}";
            }
        }
        
        // 场景类型特定验证
        switch ($scene['scene_type']) {
            case AiTravelPhotoScene::SCENE_TYPE_IMAGE_SINGLE:
            case AiTravelPhotoScene::SCENE_TYPE_IMAGE_MULTI:
                // 图生图场景的size参数验证
                if (!empty($userParams['size'])) {
                    if (!$this->validateSizeFormat($userParams['size'])) {
                        $errors[] = 'size参数格式错误，应为"宽*高"格式，如"1024*1024"';
                    }
                }
                
                // n参数验证
                if (isset($userParams['n'])) {
                    $n = intval($userParams['n']);
                    if ($n < 1 || $n > 6) {
                        $errors[] = 'n参数取值范围为1-6';
                    }
                }
                break;
                
            case AiTravelPhotoScene::SCENE_TYPE_VIDEO_FIRST_LAST:
                // 首尾帧模式必须有tail_image_url
                if (empty($userParams['tail_image_url'])) {
                    $errors[] = '首尾帧模式需要提供尾帧图片（tail_image_url）';
                }
                break;
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * 合并场景预设参数和用户自定义参数
     * 
     * @param array $sceneParams 场景预设参数
     * @param array $userParams 用户自定义参数
     * @return array
     */
    public function mergeSceneAndUserParams($sceneParams, $userParams = [])
    {
        // 用户参数优先级更高
        return array_merge($sceneParams, $userParams);
    }
    
    /**
     * 验证size参数格式（宽*高）
     * 
     * @param string $size
     * @return bool
     */
    private function validateSizeFormat($size)
    {
        if (!is_string($size)) {
            return false;
        }
        
        // 格式：宽*高，如"1024*1024"或"1024*1536"
        if (!preg_match('/^(\d+)\*(\d+)$/', $size, $matches)) {
            return false;
        }
        
        $width = intval($matches[1]);
        $height = intval($matches[2]);
        
        // 宽和高的取值范围均为[512, 2048]
        if ($width < 512 || $width > 2048 || $height < 512 || $height > 2048) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 获取场景类型的输入要求描述
     * 
     * @param int $sceneType
     * @return array
     */
    public function getInputRequirementsDesc($sceneType)
    {
        $descriptions = [
            1 => [
                'title' => '文生图-生成单张图',
                'inputs' => ['prompt（提示词）'],
                'optional' => ['negative_prompt', 'size', 'style', 'watermark']
            ],
            2 => [
                'title' => '文生图-生成一组图',
                'inputs' => ['prompt（提示词）', 'n（生成数量1-6）'],
                'optional' => ['negative_prompt', 'size', 'style', 'watermark']
            ],
            3 => [
                'title' => '图生图-单张图生成单张图',
                'inputs' => ['image（参考图）', 'prompt（提示词）'],
                'optional' => ['negative_prompt', 'size', 'watermark']
            ],
            4 => [
                'title' => '图生图-单张图生成一组图',
                'inputs' => ['image（参考图）', 'prompt（提示词）', 'sequential_image_generation_options（多图配置）'],
                'optional' => ['negative_prompt', 'size', 'watermark']
            ],
            5 => [
                'title' => '图生图-多张参考图生成单张图',
                'inputs' => ['image[]（参考图数组1-10张）', 'prompt（提示词）'],
                'optional' => ['negative_prompt', 'size', 'watermark']
            ],
            6 => [
                'title' => '图生图-多张参考图生成一组图',
                'inputs' => ['image[]（参考图数组1-10张）', 'prompt（提示词）', 'sequential_image_generation_options（多图配置）'],
                'optional' => ['negative_prompt', 'size', 'watermark']
            ]
        ];
        
        return $descriptions[$sceneType] ?? [];
    }
    
    /**
     * 获取场景类型元数据
     * 
     * @param int|null $sceneType 场景类型（不传则返回所有）
     * @return array
     */
    public function getSceneTypeMetadata($sceneType = null)
    {
        $query = Db::name('ai_travel_photo_scene_type')->where('is_active', 1);
        
        if ($sceneType !== null) {
            $result = $query->where('scene_type', $sceneType)->find();
            if ($result && !empty($result['input_requirements'])) {
                $result['input_requirements'] = json_decode($result['input_requirements'], true);
            }
            if ($result && !empty($result['form_template'])) {
                $result['form_template'] = json_decode($result['form_template'], true);
            }
            return $result;
        }
        
        $list = $query->order('sort', 'asc')->select()->toArray();
        foreach ($list as &$item) {
            if (!empty($item['input_requirements'])) {
                $item['input_requirements'] = json_decode($item['input_requirements'], true);
            }
            if (!empty($item['form_template'])) {
                $item['form_template'] = json_decode($item['form_template'], true);
            }
        }
        
        return $list;
    }
    
    /**
     * 根据模型能力标签判断支持的场景类型
     * 
     * @param array $capabilityTags 模型能力标签
     * @return array 支持的场景类型列表
     */
    public function getSupportedSceneTypes($capabilityTags)
    {
        if (!is_array($capabilityTags)) {
            $capabilityTags = json_decode($capabilityTags, true) ?? [];
        }

        // 辅助函数：检查是否含任一标签（支持中英文标签）
        $hasAny = function(array $tags) use ($capabilityTags) {
            foreach ($tags as $tag) {
                if (in_array($tag, $capabilityTags)) {
                    return true;
                }
            }
            return false;
        };

        $hasText2Image    = $hasAny(['text2image', '文生图']);
        $hasImage2Image   = $hasAny(['image2image', '图生图', '单图生图']);
        $hasBatchGen      = $hasAny(['batch_generation', '多图生成', '组图生成', '流式输出']);
        $hasMultiInput    = $hasAny(['multi_input', '多图融合', '多图生图', '多图输入']);
        
        $supportedTypes = [];
        
        // 场景1、2：文生图
        if ($hasText2Image) {
            $supportedTypes[] = 1;
            if ($hasBatchGen) {
                $supportedTypes[] = 2;
            }
        }
        
        // 场景3、4、5、6：图生图
        if ($hasImage2Image) {
            $supportedTypes[] = 3;
            
            // 场景4、6：批量生成
            if ($hasBatchGen) {
                $supportedTypes[] = 4;
            }
            
            // 场景5、6：多图输入
            if ($hasMultiInput) {
                $supportedTypes[] = 5;
                if ($hasBatchGen) {
                    $supportedTypes[] = 6;
                }
            }
        }
        
        return $supportedTypes;
    }
    
    /**
     * 验证参数是否符合场景类型要求（新版本）
     * 
     * @param int $sceneType 场景类型
     * @param array $params 参数
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateSceneTypeParams($sceneType, $params)
    {
        $errors = [];
        
        // 获取场景类型元数据
        $metadata = $this->getSceneTypeMetadata($sceneType);
        if (!$metadata) {
            return ['valid' => false, 'errors' => ['场景类型不存在']];
        }
        
        $requiredParams = $metadata['input_requirements'] ?? [];
        
        // 场景1：文生图-单张
        if ($sceneType == 1) {
            if (empty($params['prompt'])) {
                $errors[] = 'prompt（提示词）为必填参数';
            }
        }
        
        // 场景2：文生图-多张
        elseif ($sceneType == 2) {
            if (empty($params['prompt'])) {
                $errors[] = 'prompt（提示词）为必填参数';
            }
            if (!isset($params['n']) || $params['n'] < 1 || $params['n'] > 6) {
                $errors[] = 'n（生成数量）必须为1-6之间的整数';
            }
        }
        
        // 场景3：图生图-单张生成单张
        elseif ($sceneType == 3) {
            if (empty($params['image'])) {
                $errors[] = 'image（参考图）为必填参数';
            }
            if (empty($params['prompt'])) {
                $errors[] = 'prompt（提示词）为必填参数';
            }
        }
        
        // 场景4：图生图-单张生成多张
        elseif ($sceneType == 4) {
            if (empty($params['image'])) {
                $errors[] = 'image（参考图）为必填参数';
            }
            if (empty($params['prompt'])) {
                $errors[] = 'prompt（提示词）为必填参数';
            }
            if (empty($params['sequential_image_generation_options'])) {
                $errors[] = 'sequential_image_generation_options（多图生成配置）为必填参数';
            } else {
                $options = $params['sequential_image_generation_options'];
                if (!isset($options['max_images']) || $options['max_images'] < 1 || $options['max_images'] > 10) {
                    $errors[] = 'sequential_image_generation_options.max_images必须为1-10之间的整数';
                }
            }
        }
        
        // 场景5：图生图-多张生成单张
        elseif ($sceneType == 5) {
            if (empty($params['image']) || !is_array($params['image'])) {
                $errors[] = 'image（参考图数组）为必填参数，需为数组';
            } elseif (count($params['image']) < 1 || count($params['image']) > 10) {
                $errors[] = 'image（参考图数组）数量必须为1-10张';
            }
            if (empty($params['prompt'])) {
                $errors[] = 'prompt（提示词）为必填参数';
            }
        }
        
        // 场景6：图生图-多张生成多张
        elseif ($sceneType == 6) {
            if (empty($params['image']) || !is_array($params['image'])) {
                $errors[] = 'image（参考图数组）为必填参数，需为数组';
            } elseif (count($params['image']) < 1 || count($params['image']) > 10) {
                $errors[] = 'image（参考图数组）数量必须为1-10张';
            }
            if (empty($params['prompt'])) {
                $errors[] = 'prompt（提示词）为必填参数';
            }
            if (empty($params['sequential_image_generation_options'])) {
                $errors[] = 'sequential_image_generation_options（多图生成配置）为必填参数';
            } else {
                $options = $params['sequential_image_generation_options'];
                if (!isset($options['max_images']) || $options['max_images'] < 1 || $options['max_images'] > 10) {
                    $errors[] = 'sequential_image_generation_options.max_images必须为1-10之间的整数';
                }
            }
        }
        
        // 通用参数校验
        if (isset($params['size'])) {
            // 支持两种格式："宽*高"或"2K"/"4K"
            if (!preg_match('/^(\d+\*\d+|\d+K)$/i', $params['size'])) {
                $errors[] = 'size参数格式错误，应为"宽*高"格式（如"1024*1024"）或"2K"/"4K"格式';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * 构建content数组（用于火山引擎方舟视频生成API）
     * 
     * 根据视频生成模式组装content数组：
     * - text_to_video: [text元素]
     * - first_frame: [first_frame_image元素, text元素(可选)]
     * - first_last_frame: [first_frame_image元素, last_frame_image元素, text元素(可选)]
     * - reference_images: [image_url元素×1-4张, text元素(可选)]
     * 
     * @param string $videoMode 视频生成模式
     * @param array $inputParams 输入参数
     * @return array content数组
     */
    public function buildContentArray($videoMode, array $inputParams)
    {
        $content = [];
        
        // 构建text元素（包含提示词和参数标志）
        $textElement = $this->buildTextContentElementWithFlags($inputParams);
        
        switch ($videoMode) {
            case 'text_to_video':
                // 文生视频：只需要text元素
                $content[] = $textElement;
                break;
                
            case 'first_frame':
                // 首帧图生视频：text + 首帧图像
                $content[] = $textElement;
                $imageElement = $this->buildImageUrlElementForApi($inputParams, 'first_frame');
                if ($imageElement) {
                    $content[] = $imageElement;
                }
                break;
                
            case 'first_last_frame':
                // 首尾帧图生视频：text + 首帧 + 尾帧
                $content[] = $textElement;
                $firstFrameElement = $this->buildImageUrlElementForApi($inputParams, 'first_frame');
                if ($firstFrameElement) {
                    $content[] = $firstFrameElement;
                }
                $lastFrameElement = $this->buildImageUrlElementForApi($inputParams, 'last_frame');
                if ($lastFrameElement) {
                    $content[] = $lastFrameElement;
                }
                break;
                
            case 'reference_images':
                // 参考图生视频：text + 1-4张参考图
                $content[] = $textElement;
                $refImages = $inputParams['reference_images'] ?? $inputParams['ref_images'] ?? [];
                foreach ($refImages as $imageUrl) {
                    if (!empty($imageUrl)) {
                        $content[] = [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageUrl
                            ]
                        ];
                    }
                }
                break;
                
            default:
                // 默认作为文生视频处理
                $content[] = $textElement;
        }
        
        return $content;
    }
    
    /**
     * 构建text类型content元素
     * 
     * @param array $inputParams 输入参数
     * @return array
     */
    private function buildTextContentElement(array $inputParams)
    {
        $prompt = $inputParams['prompt'] ?? $inputParams['text'] ?? $inputParams['description'] ?? '';
        
        return [
            'type' => 'text',
            'text' => $prompt
        ];
    }
    
    /**
     * 构建text类型content元素（带参数标志，火山方舟API格式）
     * 参数通过命令行标志传递：--duration 5 --watermark true
     * 
     * @param array $inputParams 输入参数
     * @return array
     */
    private function buildTextContentElementWithFlags(array $inputParams)
    {
        $prompt = $inputParams['prompt'] ?? $inputParams['text'] ?? $inputParams['description'] ?? '';
        
        // 构建参数标志字符串
        $flags = [];
        
        // 时长参数
        if (!empty($inputParams['duration'])) {
            $flags[] = '--duration ' . intval($inputParams['duration']);
        }
        
        // 分辨率参数
        if (!empty($inputParams['resolution'])) {
            $resolution = $inputParams['resolution'];
            if (strtolower($resolution) === '1080p' || strtolower($resolution) === '720p') {
                $flags[] = '--resolution ' . strtoupper($resolution);
            }
        }
        
        // 水印参数
        if (isset($inputParams['watermark'])) {
            $flags[] = '--watermark ' . ($inputParams['watermark'] ? 'true' : 'false');
        }
        
        // 相机运动参数
        if (!empty($inputParams['camera_motion'])) {
            $flags[] = '--camerafixed ' . ($inputParams['camera_motion'] === 'static' ? 'true' : 'false');
        }
        
        // 拼接最终文本
        $text = trim($prompt);
        if (!empty($flags)) {
            $text .= '  ' . implode(' ', $flags);
        }
        
        return [
            'type' => 'text',
            'text' => $text
        ];
    }
    
    /**
     * 构建首帧图像content元素
     * 
     * @param array $inputParams 输入参数
     * @return array
     */
    private function buildFirstFrameImageElement(array $inputParams)
    {
        $imageUrl = $inputParams['first_frame_image'] 
            ?? $inputParams['image_url'] 
            ?? $inputParams['image'] 
            ?? $inputParams['input_image'] 
            ?? '';
        
        return [
            'type' => 'first_frame_image',
            'image_url' => $imageUrl
        ];
    }
    
    /**
     * 构建图片URL content元素（火山方舟API官方格式）
     * 格式: {"type": "image_url", "image_url": {"url": "..."}}
     * 
     * @param array $inputParams 输入参数
     * @param string $frameType 帧类型: first_frame, last_frame, image
     * @return array|null
     */
    private function buildImageUrlElementForApi(array $inputParams, $frameType = 'image')
    {
        $imageUrl = '';
        
        switch ($frameType) {
            case 'first_frame':
                $imageUrl = $inputParams['first_frame_image'] 
                    ?? $inputParams['image_url'] 
                    ?? $inputParams['image'] 
                    ?? $inputParams['input_image'] 
                    ?? '';
                break;
                
            case 'last_frame':
                $imageUrl = $inputParams['last_frame_image'] 
                    ?? $inputParams['tail_image_url'] 
                    ?? $inputParams['last_frame'] 
                    ?? $inputParams['end_image'] 
                    ?? '';
                break;
                
            default:
                $imageUrl = $inputParams['image_url'] 
                    ?? $inputParams['image'] 
                    ?? '';
        }
        
        if (empty($imageUrl)) {
            return null;
        }
        
        // 火山方舟API官方格式: {"type": "image_url", "image_url": {"url": "..."}}
        return [
            'type' => 'image_url',
            'image_url' => [
                'url' => $imageUrl
            ]
        ];
    }
    
    /**
     * 构建尾帧图像content元素
     * 
     * @param array $inputParams 输入参数
     * @return array
     */
    private function buildLastFrameImageElement(array $inputParams)
    {
        $imageUrl = $inputParams['last_frame_image'] 
            ?? $inputParams['tail_image_url'] 
            ?? $inputParams['last_frame'] 
            ?? $inputParams['end_image'] 
            ?? '';
        
        return [
            'type' => 'last_frame_image',
            'image_url' => $imageUrl
        ];
    }
    
    /**
     * 构建普通图片URLcontent元素（用于参考图）
     * 
     * @param string $imageUrl 图片URL
     * @return array
     */
    private function buildImageUrlElement($imageUrl)
    {
        // 火山方舟API官方格式
        return [
            'type' => 'image_url',
            'image_url' => [
                'url' => $imageUrl
            ]
        ];
    }
    
    /**
     * 获取视频生成模式的描述
     * 
     * @param string $videoMode 视频生成模式
     * @return string
     */
    public function getVideoModeDescription($videoMode)
    {
        $descriptions = [
            'text_to_video' => '文生视频（纯文本描述生成视频）',
            'first_frame' => '首帧图生视频（首帧图片驱动生成）',
            'first_last_frame' => '首尾帧图生视频（首帧+尾帧过渡视频）',
            'reference_images' => '参考图生视频（1-4张参考图片驱动生成）'
        ];
        
        return $descriptions[$videoMode] ?? $videoMode;
    }
    
    /**
     * 组装火山引擎视频生成参数
     * 
     * @param array $scene 场景信息
     * @param array $portrait 人像信息
     * @param array $userParams 用户自定义参数
     * @param string $videoMode 视频生成模式
     * @return array
     */
    public function assembleVolcengineVideoParams($scene, $portrait, $userParams = [], $videoMode = 'first_frame')
    {
        // 解析场景预设参数
        $sceneParams = is_string($scene['model_params']) 
            ? json_decode($scene['model_params'], true) 
            : $scene['model_params'];
        
        if (!is_array($sceneParams)) {
            $sceneParams = [];
        }
        
        // 构建input部分
        $input = [
            'prompt' => $userParams['prompt'] ?? $sceneParams['prompt'] ?? ''
        ];
        
        // 根据视频模式添加图像参数
        switch ($videoMode) {
            case 'first_frame':
                $input['first_frame_image'] = $portrait['original_url'];
                break;
                
            case 'first_last_frame':
                $input['first_frame_image'] = $portrait['original_url'];
                $input['last_frame_image'] = $userParams['tail_image_url'] 
                    ?? $sceneParams['tail_image_url'] 
                    ?? '';
                break;
                
            case 'reference_images':
                $input['reference_images'] = $userParams['reference_images'] 
                    ?? $sceneParams['reference_images'] 
                    ?? [$portrait['original_url']];
                break;
                
            case 'text_to_video':
            default:
                // 文生视频不需要图像
                break;
        }
        
        // 有声视频支持
        if (!empty($userParams['with_audio']) || !empty($sceneParams['with_audio'])) {
            $input['with_audio'] = true;
        }
        
        // 构建parameters部分
        $parameters = [
            'duration' => $userParams['duration'] ?? $sceneParams['duration'] ?? 5,
            'resolution' => $userParams['resolution'] ?? $sceneParams['resolution'] ?? '720p'
        ];
        
        // 可选参数
        if (isset($userParams['seed']) || isset($sceneParams['seed'])) {
            $parameters['seed'] = $userParams['seed'] ?? $sceneParams['seed'];
        }
        
        return [
            'input' => $input,
            'parameters' => $parameters
        ];
    }
}
