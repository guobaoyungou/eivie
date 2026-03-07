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
    
    // 火山引擎方舟配置
    'volcengine' => [
        'api_url' => 'https://ark.cn-beijing.volces.com',
        'videos_endpoint' => '/api/v3/contents/generations/tasks',  // Seedance视频生成端点
        'timeout' => 30, // 请求超时时间(秒)
        'poll_interval' => 10, // 轮询间隔(秒)
        'max_poll_time' => 600, // 最大轮询时间(秒)
        'max_retry' => 3, // 最大重试次数
        
        // Seedance模型能力注册
        'models' => [
            'doubao-seedance-1-5-pro' => [
                'name' => '豆包SeeDance 1.5 Pro',
                'text_to_video' => true,
                'first_frame' => true,
                'first_last_frame' => true,
                'reference_images' => false,
                'with_audio' => true,
                'max_duration' => 10,
                'resolutions' => ['720p', '1080p'],
                'price_per_second' => 0.80,
            ],
            'doubao-seedance-1-0-pro' => [
                'name' => '豆包SeeDance 1.0 Pro',
                'text_to_video' => true,
                'first_frame' => true,
                'first_last_frame' => true,
                'reference_images' => false,
                'with_audio' => false,
                'max_duration' => 10,
                'resolutions' => ['720p', '1080p'],
                'price_per_second' => 0.60,
            ],
            'doubao-seedance-1-0-pro-fast' => [
                'name' => '豆包SeeDance 1.0 Pro Fast',
                'text_to_video' => true,
                'first_frame' => true,
                'first_last_frame' => false,
                'reference_images' => false,
                'with_audio' => false,
                'max_duration' => 5,
                'resolutions' => ['720p'],
                'price_per_second' => 0.40,
            ],
            'doubao-seedance-1-0-lite-t2v' => [
                'name' => '豆包SeeDance 1.0 Lite (文生)',
                'text_to_video' => true,
                'first_frame' => false,
                'first_last_frame' => false,
                'reference_images' => false,
                'with_audio' => false,
                'max_duration' => 5,
                'resolutions' => ['720p'],
                'price_per_second' => 0.20,
            ],
            'doubao-seedance-1-0-lite-i2v' => [
                'name' => '豆包SeeDance 1.0 Lite (图生)',
                'text_to_video' => false,
                'first_frame' => true,
                'first_last_frame' => true,
                'reference_images' => true,
                'with_audio' => false,
                'max_duration' => 5,
                'max_reference_images' => 4,
                'resolutions' => ['720p'],
                'price_per_second' => 0.25,
            ],
            // 兼容旧的model_code
            'doubao-seedance-2-0' => [
                'name' => '豆包SeeDance 2.0',
                'text_to_video' => true,
                'first_frame' => true,
                'first_last_frame' => true,
                'reference_images' => false,
                'with_audio' => true,
                'max_duration' => 10,
                'resolutions' => ['720p', '1080p'],
                'price_per_second' => 0.50,
            ],
        ],
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
    
    // 视频生成模式配置
    'video_modes' => [
        'text_to_video' => [
            'name' => '文生视频',
            'description' => '纯文本描述生成视频',
            'required_params' => ['prompt'],
        ],
        'first_frame' => [
            'name' => '首帧图生视频',
            'description' => '首帧图片驱动生成',
            'required_params' => ['first_frame_image'],
        ],
        'first_last_frame' => [
            'name' => '首尾帧图生视频',
            'description' => '首帧+尾帧过渡视频',
            'required_params' => ['first_frame_image', 'last_frame_image'],
        ],
        'reference_images' => [
            'name' => '参考图生视频',
            'description' => '1-4张参考图片驱动生成',
            'required_params' => ['reference_images'],
            'max_images' => 4,
        ],
    ],
];
