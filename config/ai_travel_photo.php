<?php
/**
 * AI旅拍系统配置文件
 * 注意：OSS配置已移除，使用系统附件设置中的OSS配置
 */

return [
    // 场景类型配置
    'scene_type' => [
        'portrait' => '人像抠图',
        'head_photo' => '头像照',
        'half_body' => '半身照',
        'full_body' => '全身照',
    ],

    // 场景类型输入模式（前台选择）
    'scene_type_input' => [
        'portrait' => '人像抠图',
        'head_photo' => '头像照',
        'half_body' => '半身照',
        'full_body' => '全身照',
    ],

    // 图像处理配置
    'image' => [
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_size' => 10 * 1024 * 1024, // 10MB
        'thumbnail_width' => 800,
        'quality' => 90,
    ],

    // 水印配置
    'watermark' => [
        'enabled' => true,
        'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left, center
        'opacity' => 0.3,
        'margin' => 10,
    ],

    // 二维码配置
    'qrcode' => [
        'size' => 300,
        'margin' => 10,
        'expire_days' => 30,
        'base_url' => '', // 需在后台设置
    ],

    // 订单配置
    'order' => [
        'timeout' => 1800, // 30分钟
    ],

    // 限流配置
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 100,
        'window' => 60, // 60秒
    ],

    // 安全配置
    'security' => [
        'api_key' => '', // 需在后台设置
    ],

    // 队列配置
    'queue' => [
        'cutout' => [
            'enabled' => true,
            'max_workers' => 5,
        ],
    ],

    // OSS存储路径配置（使用系统附件设置，此处仅作路径前缀）
    'oss' => [
        'ai_travel_photo_path' => 'ai_travel_photo/',
    ],
];
