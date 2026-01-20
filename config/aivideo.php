<?php
/**
 * AI旅拍功能配置文件
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

return [
    // 可灵AI配置
    'kling' => [
        'api_url' => 'https://api-beijing.klingai.com',
        'token_expire' => 1800, // Token过期时间(秒)
        'max_retry' => 5, // 最大重试次数
        'default_model' => 'kling-v1',
        'default_mode' => 'std',
        'default_aspect_ratio' => '16:9',
        'default_duration' => '5',
    ],

    // 队列配置
    'queue' => [
        'prefix' => 'aivideo:',
        'task_queue' => 'task',
        'max_concurrent' => 10, // 最大并发数
    ],

    // 订单配置
    'order' => [
        'expire_time' => 1800, // 订单过期时间(秒),30分钟
    ],

    // 浏览记录配置
    'browse' => [
        'expire_days' => 30, // 浏览记录保留天数
    ],

    // 文件上传配置
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'mp4'],
        'upload_path' => ROOT_PATH . 'upload/aivideo/',
        'material_path' => ROOT_PATH . 'upload/aivideo/material/',
        'work_path' => ROOT_PATH . 'upload/aivideo/work/',
        'thumbnail_path' => ROOT_PATH . 'upload/aivideo/thumbnail/',
        'qrcode_path' => ROOT_PATH . 'upload/aivideo/qrcode/',
    ],

    // 监控程序配置
    'monitor' => [
        'max_retry' => 5, // 上传失败重试次数
        'check_interval' => 5, // 文件检查间隔(秒)
    ],
];
