<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;

/**
 * 合成模板自动标签匹配服务
 *
 * 根据人像属性标签（性别/年龄段/多人）与模板标签进行匹配，
 * 返回匹配到的模板列表。若未匹配到，返回空数组。
 */
class TemplateMatchService
{
    /**
     * 匹配合成模板（从 ai_travel_photo_synthesis_template 表）
     *
     * @param string $gender     Male / Female
     * @param string $ageGroup   0-2, 3-9, 10-19, 20-29, ...
     * @param int    $isMultiFace 0=单人 1=多人
     * @param int    $aid
     * @param int    $bid        商家ID（0=全局模板也会被检索）
     * @return array             匹配的模板列表
     */
    public function matchSynthesisTemplates(
        string $gender,
        string $ageGroup,
        int $isMultiFace,
        int $aid,
        int $bid
    ): array {
        $fields = ['id', 'name', 'model_id', 'model_name', 'cover_image',
                   'images', 'prompt', 'default_params', 'description',
                   'scene_template_id', 'status', 'sort'];

        // 获取该商户+全局的全部可用合成模板
        $allTemplates = Db::name('ai_travel_photo_synthesis_template')
            ->where('aid', $aid)
            ->whereIn('bid', [0, $bid])
            ->where('status', 1)
            ->field($fields)
            ->order('sort ASC, id DESC')
            ->select()
            ->toArray();

        Log::info('TemplateMatch: 候选模板总数', [
            'aid' => $aid, 'bid' => $bid,
            'total' => count($allTemplates),
            'gender' => $gender, 'age_group' => $ageGroup, 'is_multi' => $isMultiFace,
        ]);

        if (empty($allTemplates)) {
            Log::warning('TemplateMatch: 无可用合成模板', ['aid' => $aid, 'bid' => $bid]);
            return [];
        }

        // 过滤：只保留标签匹配的模板
        $matched = [];
        foreach ($allTemplates as $tpl) {
            if ($this->isTemplateMatch($tpl, $gender, $ageGroup, $isMultiFace)) {
                $matched[] = $tpl;
            }
        }

        Log::info('TemplateMatch: 匹配结果', [
            'total' => count($allTemplates), 'matched' => count($matched),
            'gender' => $gender, 'age_group' => $ageGroup, 'is_multi' => $isMultiFace,
        ]);

        return $matched;
    }

    /**
     * 判断单个人像是否匹配单个模板
     *
     * 匹配规则（AND）：
     * 1. gender_tag: 人像==模板 或 模板为空/Both -> 通过
     * 2. age_tag:    人像==模板 或 模板为空 -> 通过
     * 3. is_multi:   人像==模板 -> 通过
     */
    public function isTemplateMatch(
        array $tpl,
        string $gender,
        string $ageGroup,
        int $isMultiFace
    ): bool {
        // 1. 性别匹配
        $tplGender = trim($tpl['gender_tag'] ?? '');
        if (!empty($tplGender) && strtolower($tplGender) !== 'both') {
            if (strcasecmp($tplGender, $gender) !== 0) {
                return false;
            }
        }

        // 2. 年龄段匹配
        $tplAge = trim($tpl['age_tag'] ?? '');
        if (!empty($tplAge) && $tplAge !== $ageGroup) {
            return false;
        }

        // 3. 多人/单人匹配
        $tplIsMulti = (int)($tpl['is_multi_template'] ?? 0);
        if ($tplIsMulti !== $isMultiFace) {
            return false;
        }

        return true;
    }

    /**
     * 对匹配到的模板列表按 generate_count 和 generate_mode 进行选择
     */
    public function selectTemplatesForGeneration(
        array $matchedTemplates,
        int $generateCount,
        int $generateMode
    ): array {
        if (empty($matchedTemplates)) {
            return [];
        }

        $result = [];
        $total = count($matchedTemplates);

        for ($i = 0; $i < $generateCount; $i++) {
            if ($generateMode == 2) {
                // 随机模式
                $idx = array_rand($matchedTemplates);
                $result[] = $matchedTemplates[$idx];
            } else {
                // 顺序模式：循环取
                $result[] = $matchedTemplates[$i % $total];
            }
        }

        return $result;
    }
}
