<?php
/**
 * 角色一致性保障服务
 * 处理角色身份卡管理、一致性提示词注入、一致性评分、自动修复
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\WorkflowCharacterIdCard;
use app\model\WorkflowNode;

class CharacterConsistencyService
{
    // ================================================================
    // 角色身份卡管理
    // ================================================================

    /**
     * 获取角色身份卡详情
     */
    public function getIdCard($id)
    {
        $card = WorkflowCharacterIdCard::find($id);
        if (!$card) {
            return null;
        }
        $data = $card->toArray();
        if (is_string($data['reference_images'])) {
            $data['reference_images'] = json_decode($data['reference_images'], true);
        }
        return $data;
    }

    /**
     * 创建或更新角色身份卡
     */
    public function saveIdCard($data)
    {
        $id = intval($data['id'] ?? 0);

        if (empty($data['character_tag'])) {
            return ['status' => 0, 'msg' => '角色标签不能为空'];
        }
        if (empty($data['character_name'])) {
            return ['status' => 0, 'msg' => '角色名称不能为空'];
        }

        $saveData = [
            'character_tag'         => $data['character_tag'],
            'character_name'        => $data['character_name'],
            'appearance_prompt'     => $data['appearance_prompt'] ?? '',
            'negative_prompt'       => $data['negative_prompt'] ?? '',
            'reference_images'      => isset($data['reference_images'])
                ? (is_string($data['reference_images']) ? $data['reference_images'] : json_encode($data['reference_images'], JSON_UNESCAPED_UNICODE))
                : '[]',
            'style_seed'            => intval($data['style_seed'] ?? 0),
            'consistency_threshold' => floatval($data['consistency_threshold'] ?? 0.85),
        ];

        if ($id > 0) {
            $card = WorkflowCharacterIdCard::find($id);
            if (!$card) {
                return ['status' => 0, 'msg' => '身份卡不存在'];
            }
            $card->save($saveData);
        } else {
            $saveData['aid']        = intval($data['aid'] ?? 0);
            $saveData['bid']        = intval($data['bid'] ?? 0);
            $saveData['mdid']       = intval($data['mdid'] ?? 0);
            $saveData['uid']        = intval($data['uid'] ?? 0);
            $saveData['project_id'] = intval($data['project_id'] ?? 0);

            // 检查项目内唯一性
            $existing = WorkflowCharacterIdCard::where([
                'project_id'    => $saveData['project_id'],
                'character_tag' => $saveData['character_tag'],
            ])->find();
            if ($existing) {
                return ['status' => 0, 'msg' => '该项目中已存在相同标签的角色'];
            }

            $card = new WorkflowCharacterIdCard();
            $card->save($saveData);
            $id = $card->id;
        }

        return ['status' => 1, 'msg' => '保存成功', 'id' => $id];
    }

    /**
     * 从角色节点输出构建身份卡
     */
    public function buildIdCard($projectId, $characterData, $tenantData = [])
    {
        $tag  = $characterData['tag'] ?? '';
        $name = $characterData['name'] ?? '';

        if (empty($tag) || empty($name)) {
            return ['status' => 0, 'msg' => '角色数据不完整'];
        }

        // 检查是否已存在
        $existing = WorkflowCharacterIdCard::findByTag($projectId, $tag);
        $id = $existing ? $existing->id : 0;

        return $this->saveIdCard(array_merge([
            'id'                    => $id,
            'project_id'            => $projectId,
            'character_tag'         => $tag,
            'character_name'        => $name,
            'appearance_prompt'     => $characterData['appearance_prompt'] ?? '',
            'negative_prompt'       => $characterData['negative_prompt'] ?? '',
            'reference_images'      => $characterData['images'] ?? [],
            'style_seed'            => $characterData['style_seed'] ?? 0,
            'consistency_threshold' => 0.85,
        ], $tenantData));
    }

    // ================================================================
    // 一致性提示词注入
    // ================================================================

    /**
     * 为分镜生成注入一致性提示词
     * @param string $sceneDesc 原始分镜描述
     * @param array $characterTags 该分镜涉及的角色标签列表
     * @param int $projectId
     * @return array ['prompt' => 增强后的prompt, 'negative_prompt' => ..., 'reference_images' => [...]]
     */
    public function injectConsistencyPrompt($sceneDesc, $characterTags, $projectId)
    {
        $enhancedPrompt = $sceneDesc;
        $negativePrompts = [];
        $referenceImages = [];

        foreach ($characterTags as $tag) {
            $card = WorkflowCharacterIdCard::findByTag($projectId, $tag);
            if (!$card) continue;

            // 步骤1：Prompt拼接 - 将外貌描述注入分镜描述
            if (!empty($card->appearance_prompt)) {
                $enhancedPrompt .= ', ' . $card->character_name . ': ' . $card->appearance_prompt;
            }

            // 步骤2：参考图注入
            $refImages = $card->reference_images;
            if (is_string($refImages)) {
                $refImages = json_decode($refImages, true);
            }
            if (!empty($refImages)) {
                $referenceImages = array_merge($referenceImages, $refImages);
            }

            // 步骤4：Negative Prompt
            if (!empty($card->negative_prompt)) {
                $negativePrompts[] = $card->negative_prompt;
            }
        }

        return [
            'prompt'           => $enhancedPrompt,
            'negative_prompt'  => implode(', ', $negativePrompts),
            'reference_images' => $referenceImages,
            'style_seed'       => !empty($characterTags) ? ($this->getFirstCardSeed($characterTags, $projectId)) : 0,
        ];
    }

    /**
     * 获取第一个角色的风格种子
     */
    protected function getFirstCardSeed($characterTags, $projectId)
    {
        foreach ($characterTags as $tag) {
            $card = WorkflowCharacterIdCard::findByTag($projectId, $tag);
            if ($card && $card->style_seed > 0) {
                return $card->style_seed;
            }
        }
        return 0;
    }

    // ================================================================
    // 一致性评分
    // ================================================================

    /**
     * 对生成的图片/帧进行一致性评分
     * @param string $imageUrl 生成的图片URL
     * @param string $characterTag 角色标签
     * @param int $projectId
     * @return array ['score' => float, 'dimensions' => [...], 'passed' => bool]
     */
    public function scoreConsistency($imageUrl, $characterTag, $projectId)
    {
        $card = WorkflowCharacterIdCard::findByTag($projectId, $characterTag);
        if (!$card) {
            return ['score' => 0, 'passed' => false, 'msg' => '角色身份卡不存在'];
        }

        $threshold = $card->consistency_threshold ?: 0.85;

        // 评分维度：面部相似度40% + 外貌特征匹配30% + 风格一致性20% + 构图合理性10%
        // 注意：完整的评分需要集成面部识别模型和图像相似度模型
        // 此处先提供基于规则的简化评分框架

        $dimensions = [
            'face_similarity'     => 0.0, // 面部相似度 - 需要face embedding比对
            'appearance_matching' => 0.0, // 外貌特征匹配 - 需要LLM评估
            'style_consistency'   => 0.0, // 风格一致性 - 需要图像比对
            'composition'         => 0.0, // 构图合理性 - 需要视觉分析
        ];

        // 如果有面部embedding，计算余弦相似度
        if (!empty($card->face_embedding)) {
            // TODO: 通过面部检测模型提取生成图的face_embedding并比对
            $dimensions['face_similarity'] = 0.8; // 占位
        } else {
            $dimensions['face_similarity'] = 0.75; // 无基准时给予中性分
        }

        // 外貌特征匹配 - 检查appearance_prompt中的关键词
        if (!empty($card->appearance_prompt)) {
            $dimensions['appearance_matching'] = 0.8; // 占位，后续集成LLM评估
        } else {
            $dimensions['appearance_matching'] = 0.7;
        }

        // 风格一致性
        $dimensions['style_consistency'] = 0.85; // 占位

        // 构图合理性
        $dimensions['composition'] = 0.9; // 占位

        // 加权评分
        $score = $dimensions['face_similarity'] * 0.4
               + $dimensions['appearance_matching'] * 0.3
               + $dimensions['style_consistency'] * 0.2
               + $dimensions['composition'] * 0.1;

        $passed = $score >= $threshold;

        return [
            'score'      => round($score, 4),
            'threshold'  => $threshold,
            'passed'     => $passed,
            'dimensions' => $dimensions,
        ];
    }

    // ================================================================
    // 自动修复
    // ================================================================

    /**
     * 自动修复低分帧
     * @param float $score 当前评分
     * @param int $nodeId 节点ID
     * @param int $frameIndex 帧索引
     * @param int $retryCount 已重试次数
     * @return array
     */
    public function autoRepair($score, $nodeId, $frameIndex, $retryCount = 0)
    {
        if ($score >= 0.85) {
            return ['action' => 'pass', 'msg' => '一致性评分通过'];
        }

        if ($score >= 0.70 && $retryCount < 2) {
            // 增强prompt后重新生成
            return [
                'action'      => 'retry',
                'msg'         => '一致性评分偏低，使用增强prompt重新生成',
                'retry_count' => $retryCount + 1,
            ];
        }

        if ($score < 0.70 || $retryCount >= 2) {
            // 标记异常，通知用户
            return [
                'action' => 'manual_review',
                'msg'    => '一致性评分过低，需要人工审查',
                'score'  => $score,
            ];
        }

        return ['action' => 'pass'];
    }
}
