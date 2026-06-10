<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Config;
use think\facade\Db;
use think\facade\Log;
use think\facade\Queue;

/**
 * 场景模板自动标签识别服务
 * 基于 InsightFace + FairFace 的人物属性自动识别
 * 
 * @date 2026-04-16
 */
class AutoTaggingService
{
    /**
     * 配置缓存
     * @var array
     */
    protected $config = [];

    /**
     * 标签映射配置（固定，不需后台编辑）
     * @var array
     */
    protected $tagMaps = [];

    public function __construct()
    {
        // 从 sysset 表读取配置，优先于 config 文件
        $dbConfig = [];
        try {
            $row = Db::name('sysset')->where('name', 'auto_tagging')->value('value');
            if ($row) {
                $dbConfig = json_decode($row, true) ?: [];
            }
        } catch (\Exception $e) {
            Log::warning('AutoTaggingService: 读取sysset配置失败, 回退到config文件', ['error' => $e->getMessage()]);
        }

        // config 文件作为 fallback
        $fileConfig = Config::get('auto_tagging', []);

        // DB 配置优先，缺失的字段用 config 文件补全
        $defaults = [
            'fairface_api_url' => 'http://127.0.0.1:8867',
            'fairface_timeout' => 30,
            'extended_analyze_timeout' => 45,
            'auto_tag_enabled' => false,
            'extended_tagging_enabled' => false,
            'auto_tag_confidence_threshold' => 0.55,
            'gender_flip_confidence_threshold' => 0,
            'preferred_model' => 'insightface_buffalo_l',
            'auto_tag_queue' => 'auto_image_tagging',
            'auto_tag_max_retry' => 2,
            'auto_tag_retry_delay' => 60,
            'batch_limit' => 50,
            'detect_body_type' => true,
        ];

        $this->config = array_merge($defaults, $fileConfig, $dbConfig);

        // 标签映射始终从 config 文件读取（非运行时可编辑）
        $this->tagMaps = [
            'gender_map'    => $fileConfig['gender_map'] ?? [
                'Male'   => '男性',
                'Female' => '女性',
            ],
            'age_group_map' => $fileConfig['age_group_map'] ?? [
                '0-2'   => '新生儿婴儿',
                '3-9'   => '学龄儿童',
                '10-19' => '青少年',
                '20-29' => '职场青年',
                '30-39' => '而立青年',
                '40-49' => '壮年期',
                '50-59' => '中年主力',
                '60-69' => '准老年前期',
                '70+'   => '高龄',
            ],
            'precise_age_ranges' => $fileConfig['precise_age_ranges'] ?? [],
            'race_map'      => $fileConfig['race_map'] ?? [
                'East Asian'      => '东亚',
                'Southeast Asian' => '东南亚',
                'Indian'          => '南亚',
                'Black'           => '非裔',
                'White'           => '欧美',
                'Middle Eastern'  => '中东',
                'Latino_Hispanic' => '拉丁裔',
            ],
            'body_type_map' => $fileConfig['body_type_map'] ?? [
                'slim'     => '纤细',
                'average'  => '匀称',
                'muscular' => '健壮',
                'heavy'    => '丰满',
            ],
            'emotion_map'   => $fileConfig['emotion_map'] ?? [],
        ];
    }

    /**
     * 触发单个模板的自动标签识别（推入队列）
     *
     * @param int    $templateId 模板ID
     * @param string $imageUrl   用于识别的图片URL（可选，为空时自动获取）
     * @return array ['status' => 0|1, 'msg' => '...']
     */
    public function triggerAutoTagging(int $templateId, string $imageUrl = ''): array
    {
        // 检查功能是否启用
        if (empty($this->config['auto_tag_enabled'])) {
            return ['status' => 0, 'msg' => '自动标签功能未启用'];
        }

        // 获取模板数据
        $template = Db::name('generation_scene_template')
            ->where('id', $templateId)
            ->field('id, cover_image, default_params, auto_tag_status')
            ->find();

        if (!$template) {
            return ['status' => 0, 'msg' => '模板不存在'];
        }

        // 获取源图片URL
        if (empty($imageUrl)) {
            $imageUrl = $this->getSourceImageUrl($template);
        }

        if (empty($imageUrl)) {
            return ['status' => 0, 'msg' => '无可用图片进行识别'];
        }

        // 更新状态为识别中
        Db::name('generation_scene_template')->where('id', $templateId)->update([
            'auto_tag_status'     => 1,
            'auto_tag_source_url' => $imageUrl,
            'update_time'         => time(),
        ]);

        // 推入队列
        $queueName = $this->config['auto_tag_queue'] ?? 'auto_image_tagging';
        $jobData = [
            'template_id' => $templateId,
            'image_url'   => $imageUrl,
            'timestamp'   => time(),
        ];

        $pushed = Queue::push('app\\job\\AutoTaggingJob', $jobData, $queueName);

        if ($pushed !== false) {
            Log::info('AutoTagging: 模板 ' . $templateId . ' 已推入识别队列', $jobData);
            return ['status' => 1, 'msg' => '已提交识别任务'];
        } else {
            // 推送失败，恢复状态
            Db::name('generation_scene_template')->where('id', $templateId)->update([
                'auto_tag_status' => 0,
                'update_time'     => time(),
            ]);
            return ['status' => 0, 'msg' => '队列推送失败'];
        }
    }

    /**
     * 批量触发标签识别
     *
     * @param array $extraWhere 额外查询条件
     * @param int   $limit      每批次限制
     * @return array ['status' => 1, 'msg' => '...', 'data' => ['total' => x, 'pushed' => x]]
     */
    public function batchTrigger(array $extraWhere = [], int $limit = 0): array
    {
        if (empty($this->config['auto_tag_enabled'])) {
            return ['status' => 0, 'msg' => '自动标签功能未启用'];
        }

        $limit = $limit > 0 ? $limit : intval($this->config['batch_limit'] ?? 50);
        if ($limit > 200) {
            $limit = 200;
        }

        // 查询未识别且启用的模板
        $where = [
            ['auto_tag_status', '=', 0],
            ['status', '=', 1],
        ];
        if (!empty($extraWhere)) {
            $where = array_merge($where, $extraWhere);
        }

        $templates = Db::name('generation_scene_template')
            ->where($where)
            ->field('id, cover_image, default_params')
            ->limit($limit)
            ->select()
            ->toArray();

        $total = count($templates);
        $pushed = 0;

        foreach ($templates as $tpl) {
            $imageUrl = $this->getSourceImageUrl($tpl);
            if (empty($imageUrl)) {
                continue;
            }

            $result = $this->triggerAutoTagging(intval($tpl['id']), $imageUrl);
            if ($result['status'] == 1) {
                $pushed++;
            }
        }

        return [
            'status' => 1,
            'msg'    => "批量识别已提交：共 {$total} 条，成功推入 {$pushed} 条",
            'data'   => [
                'total'  => $total,
                'pushed' => $pushed,
            ],
        ];
    }

    /**
     * 调用 InsightFace + FairFace 识别服务
     *
     * @param string $imageUrl 图片URL
     * @return array API 响应数组
     * @throws \Exception
     */
    public function callFairFaceApi(string $imageUrl): array
    {
        $apiUrl  = rtrim($this->config['fairface_api_url'] ?? 'http://127.0.0.1:8867', '/');
        $timeout = intval($this->config['fairface_timeout'] ?? 30);
        $detectBodyType = !empty($this->config['detect_body_type']);

        $postData = json_encode([
            'image_url'        => $imageUrl,
            'detect_body_type' => $detectBodyType,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl . '/api/analyze',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($error)) {
            throw new \Exception('FairFace 服务请求失败: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new \Exception('FairFace 服务返回异常 HTTP ' . $httpCode . ': ' . mb_substr($response, 0, 200));
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new \Exception('FairFace 服务返回非法 JSON');
        }

        if (($result['status'] ?? '') === 'error') {
            throw new \Exception('FairFace 服务错误: ' . ($result['detail'] ?? '未知错误'));
        }

        return $result;
    }

    /**
     * 解析 API 响应并映射为中文标签
     *
     * @param array $apiResponse /api/analyze 的响应
     * @return array 处理后的标签数据结构（auto_tags JSON 格式）
     */
    public function parseAndMapTags(array $apiResponse): array
    {
        $faces     = $apiResponse['faces'] ?? [];
        $faceCount = intval($apiResponse['face_count'] ?? count($faces));
        $threshold = floatval($this->config['auto_tag_confidence_threshold'] ?? 0.7);

        $genderMap   = $this->tagMaps['gender_map'] ?? [];
        $ageGroupMap = $this->tagMaps['age_group_map'] ?? [];
        $raceMap     = $this->tagMaps['race_map'] ?? [];
        $bodyTypeMap = $this->tagMaps['body_type_map'] ?? [];

        $parsedFaces  = [];
        $primaryTags  = [];
        $confidence   = [];

        foreach ($faces as $idx => $face) {
            $entry = [];

            // 性别：永远 Male/Female，映射为中文
            $entry['gender'] = $face['gender'] ?? '';
            $entry['gender_label'] = $genderMap[$entry['gender']] ?? $entry['gender'];
            $genderConf = floatval($face['gender_confidence'] ?? 0);

            // 年龄分段（向后兼容）
            $entry['age_group'] = $face['age_group'] ?? '';
            $entry['age_label'] = $ageGroupMap[$entry['age_group']] ?? $entry['age_group'];
            $ageConf = floatval($face['age_confidence'] ?? 0);

            // --- 精确浮点年龄 → 25段精细化区间映射 ---
            $entry['age'] = isset($face['age']) ? floatval($face['age']) : null;
            $entry['age_lower'] = isset($face['age_lower']) ? floatval($face['age_lower']) : null;
            $entry['age_upper'] = isset($face['age_upper']) ? floatval($face['age_upper']) : null;
            $entry['gender_model'] = $face['gender_model'] ?? ($face['gender'] ?? '');

            // 精确年龄标签：优先使用 ImageAnalysisService 的映射方法
            $preciseAgeLabel = '';
            if ($entry['age'] !== null) {
                $preciseAgeLabel = ImageAnalysisService::mapAgeToPreciseRange($entry['age']);
            }
            $entry['precise_age_label'] = $preciseAgeLabel;

            // 人种
            $entry['race'] = $face['race'] ?? '';
            $entry['race_label'] = $raceMap[$entry['race']] ?? $entry['race'];
            $raceConf = floatval($face['race_confidence'] ?? 0);

            // 体型
            $entry['body_type'] = $face['body_type'] ?? null;
            $entry['body_type_label'] = !empty($entry['body_type'])
                ? ($bodyTypeMap[$entry['body_type']] ?? $entry['body_type'])
                : null;
            $bodyConf = floatval($face['body_type_confidence'] ?? 0);

            $parsedFaces[] = $entry;

            // 仅对第一张脸（主体人物，已按 bbox_area 降序）提取主标签
            if ($idx === 0) {
                // 性别标签：永远有值（Male/Female），置信度过滤仅用于 primary_tags
                if ($genderConf >= $threshold && !empty($entry['gender_label'])) {
                    $primaryTags[] = $entry['gender_label'];
                }
                // 年龄标签：优先用精确区间标签
                if ($ageConf >= $threshold) {
                    if (!empty($preciseAgeLabel)) {
                        $primaryTags[] = $preciseAgeLabel;
                    } elseif (!empty($entry['age_label']) && $entry['age_label'] !== $entry['age_group']) {
                        $primaryTags[] = $entry['age_label'];
                    }
                }
                if ($raceConf >= $threshold && !empty($entry['race_label'])) {
                    $primaryTags[] = $entry['race_label'];
                }
                if ($bodyConf >= $threshold && !empty($entry['body_type_label'])) {
                    $primaryTags[] = $entry['body_type_label'];
                }

                $confidence = [
                    'gender'    => $genderConf,
                    'age_group' => $ageConf,
                    'race'      => $raceConf,
                    'body_type' => $bodyConf,
                ];
            }
        }

        return [
            'faces'        => $parsedFaces,
            'primary_tags' => $primaryTags,
            'tag_string'   => implode(',', $primaryTags),
            'face_count'   => $faceCount,
            'is_multi_face' => $faceCount > 1,
            'confidence'   => $confidence,
        ];
    }

    /**
     * 将标签合并写入模板
     * - 结构化数据写入 auto_tags (JSON)
     * - 中文标签追加到 category（去重不覆盖）
     *
     * @param int   $templateId 模板ID
     * @param array $tagsData   parseAndMapTags 的返回值
     * @return bool
     */
    public function mergeTagsToTemplate(int $templateId, array $tagsData): bool
    {
        // 获取现有 category
        $existing = Db::name('generation_scene_template')
            ->where('id', $templateId)
            ->field('id, category')
            ->find();

        if (!$existing) {
            return false;
        }

        $existingCategory = trim($existing['category'] ?? '');
        $existingTags = [];
        if (!empty($existingCategory)) {
            $existingTags = array_map('trim', explode(',', $existingCategory));
            $existingTags = array_filter($existingTags);
        }

        // 待追加的自动标签（去重）
        $newTags = $tagsData['primary_tags'] ?? [];
        $appendTags = [];
        foreach ($newTags as $tag) {
            $tag = trim($tag);
            if (!empty($tag) && !in_array($tag, $existingTags)) {
                $appendTags[] = $tag;
                $existingTags[] = $tag; // 防止本次追加内部重复
            }
        }

        // 合并 category
        $mergedCategory = $existingCategory;
        if (!empty($appendTags)) {
            if (!empty($mergedCategory)) {
                $mergedCategory .= ',' . implode(',', $appendTags);
            } else {
                $mergedCategory = implode(',', $appendTags);
            }
        }

        // 写入数据库
        // 从标签数据提取独立字段
        $faceCount = intval($tagsData['face_count'] ?? 0);
        $isMultiFace = !empty($tagsData['is_multi_face']);

        // 提取主标签的 gender 和 age_group
        $genderTag = '';
        $ageTag = '';
        $raceTag = '';
        $preciseAge = null;
        $ageLower = null;
        $ageUpper = null;
        $genderModel = '';
        if (!empty($tagsData['faces']) && is_array($tagsData['faces'])) {
            $mainFace = $tagsData['faces'][0];
            $genderTag = $mainFace['gender'] ?? '';
            $ageTag = $mainFace['precise_age_label'] ?? $mainFace['age_label'] ?? $mainFace['age_group'] ?? '';
            $raceTag = $mainFace['race'] ?? '';
            $preciseAge = $mainFace['age'] ?? null;
            $ageLower = $mainFace['age_lower'] ?? null;
            $ageUpper = $mainFace['age_upper'] ?? null;
            $genderModel = $mainFace['gender_model'] ?? '';
        }

        $updateData = [
            'auto_tags'          => json_encode($tagsData, JSON_UNESCAPED_UNICODE),
            'auto_tag_status'    => 2,
            'auto_tag_time'      => time(),
            'category'           => $mergedCategory,
            'gender_tag'         => $genderTag,
            'age_tag'            => $ageTag,
            'race_tag'           => $raceTag,
            'is_multi_template'  => $isMultiFace ? 1 : 0,
            'face_count'         => $faceCount,
            'update_time'        => time(),
        ];

        // 精确年龄字段（如果表中有对应列则更新）
        if ($preciseAge !== null) {
            $updateData['precise_age'] = $preciseAge;
            $updateData['precise_age_lower'] = $ageLower;
            $updateData['precise_age_upper'] = $ageUpper;
            $updateData['gender_model'] = $genderModel;
        }

        Db::name('generation_scene_template')->where('id', $templateId)->update($updateData);

        Log::info('AutoTagging: 模板 ' . $templateId . ' 标签写入成功', [
            'primary_tags'  => $newTags,
            'appended_tags' => $appendTags,
            'category'      => $mergedCategory,
        ]);

        return true;
    }

    /**
     * 将扩展标签写入人像表（ai_travel_photo_portrait）
     *
     * @param int   $portraitId 人像ID
     * @param array $extendedData ImageAnalysisService::parseExtendedAttributes() 返回值
     * @return bool
     */
    public function mergeExtendedTagsToPortrait(int $portraitId, array $extendedData): bool
    {
        try {
            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update(array_merge(
                $extendedData,
                ['update_time' => time()]
            ));

            Log::info('AutoTagging: 人像 ' . $portraitId . ' 扩展标签写入成功', [
                'emotion_primary' => $extendedData['emotion_primary'] ?? '',
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('AutoTagging: 人像 ' . $portraitId . ' 扩展标签写入失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 标记识别失败
     *
     * @param int    $templateId 模板ID
     * @param string $errorMsg   错误信息
     * @return void
     */
    public function markFailed(int $templateId, string $errorMsg = ''): void
    {
        Db::name('generation_scene_template')->where('id', $templateId)->update([
            'auto_tag_status' => 3,
            'auto_tag_time'   => time(),
            'auto_tags'       => json_encode([
                'error' => mb_substr($errorMsg, 0, 500),
                'faces' => [],
                'primary_tags' => [],
                'tag_string'   => '',
                'face_count'   => 0,
                'confidence'   => [],
            ], JSON_UNESCAPED_UNICODE),
            'update_time'     => time(),
        ]);

        Log::warning('AutoTagging: 模板 ' . $templateId . ' 识别失败: ' . $errorMsg);
    }

    /**
     * 获取模板的自动标签状态和数据
     *
     * @param int $templateId 模板ID
     * @return array|null
     */
    public function getTaggingStatus(int $templateId): ?array
    {
        $data = Db::name('generation_scene_template')
            ->where('id', $templateId)
            ->field('id, auto_tags, auto_tag_status, auto_tag_time, auto_tag_source_url, category')
            ->find();

        if (!$data) {
            return null;
        }

        // 解析 auto_tags JSON
        if (!empty($data['auto_tags']) && is_string($data['auto_tags'])) {
            $data['auto_tags'] = json_decode($data['auto_tags'], true) ?: [];
        }

        // 状态文本
        $statusMap = [
            0 => '未识别',
            1 => '识别中',
            2 => '已完成',
            3 => '识别失败',
        ];
        $data['auto_tag_status_text'] = $statusMap[$data['auto_tag_status']] ?? '未知';

        // 时间格式化
        $data['auto_tag_time_text'] = $data['auto_tag_time'] > 0
            ? date('Y-m-d H:i:s', $data['auto_tag_time'])
            : '-';

        return $data;
    }

    /**
     * 按优先级获取用于识别的源图片URL
     *
     * 优先级：original_images > default_params.ref_image/image > cover_image
     *
     * @param array $templateData 模板数据（需包含 cover_image, default_params）
     * @return string 图片URL，为空表示无可用图片
     */
    public function getSourceImageUrl(array $templateData): string
    {
        // 优先级1：original_images 字段（若存在）
        $originalImages = $templateData['original_images'] ?? '';
        if (!empty($originalImages)) {
            if (is_string($originalImages)) {
                $images = json_decode($originalImages, true);
                if (is_array($images) && !empty($images)) {
                    $first = is_array($images[0]) ? ($images[0]['url'] ?? $images[0]['src'] ?? '') : $images[0];
                    if (!empty($first) && $this->isValidImageUrl($first)) {
                        return $first;
                    }
                }
            }
        }

        // 优先级2：default_params 中的 ref_image / image
        $defaultParams = $templateData['default_params'] ?? '';
        if (!empty($defaultParams)) {
            if (is_string($defaultParams)) {
                $defaultParams = json_decode($defaultParams, true) ?: [];
            }
            if (is_array($defaultParams)) {
                // 检查 ref_image / image（可能是字符串URL或数组）
                $refImage = $defaultParams['ref_image'] ?? $defaultParams['image'] ?? '';
                // 如果是数组（多张参考图），取第一张
                if (is_array($refImage)) {
                    $refImage = !empty($refImage) ? (is_array($refImage[0]) ? ($refImage[0]['url'] ?? $refImage[0]['src'] ?? '') : $refImage[0]) : '';
                }
                if (is_string($refImage) && !empty($refImage) && $this->isValidImageUrl($refImage)) {
                    return $refImage;
                }
            }
        }

        // 优先级3：cover_image（可能是字符串或数组）
        $coverImage = $templateData['cover_image'] ?? '';
        if (is_array($coverImage)) {
            $coverImage = !empty($coverImage) ? (is_string($coverImage[0]) ? $coverImage[0] : '') : '';
        }
        if (is_string($coverImage) && !empty($coverImage) && $this->isValidImageUrl($coverImage)) {
            return $coverImage;
        }

        return '';
    }

    /**
     * 检查 URL 是否为有效的图片地址
     *
     * @param string $url
     * @return bool
     */
    protected function isValidImageUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        // 必须以 http:// 或 https:// 开头
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            return false;
        }

        // 排除明显的非图片URL
        $lower = strtolower($url);
        $imageExtensions = ['.jpg', '.jpeg', '.png', '.webp', '.bmp', '.gif'];
        $hasImageExt = false;
        foreach ($imageExtensions as $ext) {
            if (strpos($lower, $ext) !== false) {
                $hasImageExt = true;
                break;
            }
        }

        // 即使没有明确扩展名（如 CDN 地址），只要是 http(s) 也放行
        // 让 FairFace 服务去判断实际内容
        return true;
    }
}
