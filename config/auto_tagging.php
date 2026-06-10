<?php
/**
 * 场景模板自动标签识别配置
 * 基于 InsightFace buffalo_l (Primary) + FairFace (Race Only) 的人物属性自动识别
 * @date 2026-04-16
 * @updated 2026-06-04 — 新增扩展标签系统（表情识别 + 五官/外貌标签列）
 */

return [
    // 识别服务地址（InsightFace buffalo_l + FairFace 一体化服务）
    'fairface_api_url' => 'http://127.0.0.1:8867',

    // 请求超时时间（秒）
    'fairface_timeout' => 30,

    // 扩展分析请求超时（秒）— 表情识别 + 关键点推断额外耗时
    'extended_analyze_timeout' => 45,

    // 是否启用自动标签功能
    'auto_tag_enabled' => true,

    // ========== 扩展标签开关 ==========
    // 是否启用扩展标签识别（表情识别 + 五官/外貌特征）
    // 关闭时仅进行基础的性别、年龄、人种识别，向后兼容
    'extended_tagging_enabled' => true,

    // 置信度阈值：仅当单项属性置信度 >= 此值时才写入对应标签
    // InsightFace buffalo_l genderage.onnx 的 gender 使用 softmax 真实置信度（0.5-0.99），
    // age 置信度使用 det_score（0.7-0.99），两者均低于旧 FairFace 硬编码的 0.85/0.75。
    // 因此将阈值从 0.7 下调至 0.55，避免真实但适中的 softmax 置信度被误过滤。
    'auto_tag_confidence_threshold' => 0.55,

    // 性别翻转阈值：已禁用（设为 0）
    // 二分类 softmax 下置信度 0.5+ 即表示模型倾向该性别
    // 翻转取反意味着故意选择更不可能的答案，会造成性别标签错误
    'gender_flip_confidence_threshold' => 0,

    // 首选识别模型: insightface_buffalo_l | fairface
    // insightface_buffalo_l: 精确浮点年龄 + Softmax 性别置信度 + 年龄置信区间
    // fairface: 旧模型（仅用于人种分类补充）
    'preferred_model' => 'insightface_buffalo_l',

    // 队列名称
    'auto_tag_queue' => 'auto_image_tagging',

    // 最大重试次数
    'auto_tag_max_retry' => 2,

    // 重试延迟（秒）
    'auto_tag_retry_delay' => 60,

    // 批量补全每批次限制
    'batch_limit' => 50,

    // 是否启用体型检测（需全身图，仅人脸时返回空）
    'detect_body_type' => true,

    // ========== 标签映射配置 ==========

    // 性别标签映射（兼容数据库中英文和中文两种格式）
    'gender_map' => [
        'Male'   => '男',
        'Female' => '女',
        '男性'   => '男',
        '女性'   => '女',
        '未知'   => '未知',
    ],

    // 年龄分段标签映射（已废弃，保留用于向后兼容旧数据）
    // 新标签使用 precise_age_ranges 精确区间
    'age_group_map' => [
        '0-2'   => '婴幼儿',
        '3-9'   => '儿童',
        '10-19' => '少年',
        '20-29' => '青年',
        '30-39' => '中青年',
        '40-49' => '中年',
        '50-59' => '中老年',
        '60-69' => '老年',
        '70+'   => '高龄',
    ],

    // 精准年龄区间映射（25段，基于浮点年龄直接映射）
    // 区间边界：左闭右开 [min, max)，最后一段闭合 [86, 100]
    'precise_age_ranges' => [
        ['min' => 0.0,  'max' => 0.99,  'label' => '新生儿婴儿'],
        ['min' => 1.0,  'max' => 1.99,  'label' => '学步期'],
        ['min' => 2.0,  'max' => 2.99,  'label' => '低龄幼童'],
        ['min' => 3.0,  'max' => 3.99,  'label' => '小班幼儿'],
        ['min' => 4.0,  'max' => 4.99,  'label' => '中班幼儿'],
        ['min' => 5.0,  'max' => 5.99,  'label' => '大班幼儿'],
        ['min' => 6.0,  'max' => 6.99,  'label' => '学前儿童'],
        ['min' => 7.0,  'max' => 9.99,   'label' => '小学低龄'],
        ['min' => 10.0, 'max' => 12.99,  'label' => '小学高龄'],
        ['min' => 13.0, 'max' => 15.99,  'label' => '初中少年'],
        ['min' => 16.0, 'max' => 17.99,  'label' => '高中青年'],
        ['min' => 18.0, 'max' => 22.99,  'label' => '校园青年'],
        ['min' => 23.0, 'max' => 25.99,  'label' => '初入职场'],
        ['min' => 26.0, 'max' => 29.99,  'label' => '职场青年'],
        ['min' => 30.0, 'max' => 35.99,  'label' => '而立青年'],
        ['min' => 36.0, 'max' => 40.99,  'label' => '青中年'],
        ['min' => 41.0, 'max' => 45.99,  'label' => '壮年期'],
        ['min' => 46.0, 'max' => 50.99,  'label' => '中年主力'],
        ['min' => 51.0, 'max' => 55.99,  'label' => '中老年前期'],
        ['min' => 56.0, 'max' => 60.99,  'label' => '准老年前期'],
        ['min' => 61.0, 'max' => 65.99,  'label' => '低龄活力老人'],
        ['min' => 66.0, 'max' => 70.99,  'label' => '健康老人'],
        ['min' => 71.0, 'max' => 75.99,  'label' => '中年老人'],
        ['min' => 76.0, 'max' => 80.99,  'label' => '高龄老人'],
        ['min' => 81.0, 'max' => 85.99,  'label' => '超高龄老人'],
        ['min' => 86.0, 'max' => 100.0,  'label' => '长寿老人'],
    ],

    // 人种标签映射
    'race_map' => [
        'East Asian'      => '东亚',
        'Southeast Asian' => '东南亚',
        'Indian'          => '南亚',
        'Black'           => '非裔',
        'White'           => '欧美',
        'Middle Eastern'  => '中东',
        'Latino_Hispanic' => '拉丁裔',
    ],

    // 体型标签映射
    'body_type_map' => [
        'slim'     => '纤细',
        'average'  => '匀称',
        'muscular' => '健壮',
        'heavy'    => '丰满',
    ],

    // ========== 扩展标签映射 ==========

    // 表情标签中文映射（EmotiEffLib 8类 → 6类中文）
    'emotion_map' => [
        '平静' => '平静',
        '微笑' => '微笑',
        '伤心' => '伤心',
        '惊讶' => '惊讶',
        '生气' => '生气',
        '恐惧' => '恐惧',
    ],

    // 扩展标签英文值→中文显示映射（自动标签栏展示用）
    'extended_label_map' => [
        'glasses_type'      => ['sunglasses' => '墨镜', 'eyeglasses' => '眼镜'],
        'eyelid_type'       => ['single' => '单眼皮', 'double' => '双眼皮'],
        'eyebrow_shape'     => ['curved' => '弯眉', 'flat' => '平眉'],
        'eyebrow_thickness' => ['thick' => '浓眉', 'thin' => '淡眉'],
        'lip_type'          => ['thick' => '厚唇', 'thin' => '薄唇'],
        'skin_tone'         => ['white' => '白肤', 'yellow' => '黄肤', 'brown' => '棕肤', 'black' => '黑肤'],
        'hair_length'       => ['long' => '长发', 'short' => '短发'],
        'has_beard'         => [1 => '有胡子'],
        'has_bangs'         => [1 => '有刘海'],
        'has_mask'          => [1 => '戴口罩'],
        'has_accessory'     => [1 => '有饰物'],
    ],

    // 人脸属性标签定义（Phase 2 专用模型填充，当前列值为空）
    // 眼镜类型
    'glasses_type_options' => ['none', 'sunglasses', 'eyeglasses'],
    // 眼皮类型
    'eyelid_type_options' => ['single', 'double'],
    // 眉形
    'eyebrow_shape_options' => ['curved', 'flat'],
    // 眉浓淡
    'eyebrow_thickness_options' => ['thick', 'thin'],
    // 唇厚薄
    'lip_type_options' => ['thick', 'thin'],
    // 肤色
    'skin_tone_options' => ['white', 'yellow', 'brown', 'black'],
    // 发长
    'hair_length_options' => ['long', 'short'],
];
