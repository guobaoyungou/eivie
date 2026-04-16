<?php
/**
 * 场景模板自动标签识别配置
 * 基于 InsightFace + FairFace 的人物属性自动识别
 * @date 2026-04-16
 */

return [
    // FairFace 识别服务地址（InsightFace + FairFace 一体化服务）
    'fairface_api_url' => 'http://127.0.0.1:8867',

    // 请求超时时间（秒）
    'fairface_timeout' => 30,

    // 是否启用自动标签功能
    'auto_tag_enabled' => true,

    // 置信度阈值：仅当单项属性置信度 >= 此值时才写入对应标签
    'auto_tag_confidence_threshold' => 0.7,

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

    // 性别标签映射
    'gender_map' => [
        'Male'   => '男性',
        'Female' => '女性',
    ],

    // 年龄分段标签映射
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
];
