<?php

/**
 * AI旅拍系统配置文件
 */

return [
    // 阿里云OSS配置
    'oss' => [
        'access_key_id' => env('oss.access_key_id', ''),
        'access_key_secret' => env('oss.access_key_secret', ''),
        'endpoint' => env('oss.endpoint', 'oss-cn-hangzhou.aliyuncs.com'),
        'bucket' => env('oss.bucket', ''),
        'domain' => env('oss.domain', ''), // CDN域名
        'ai_travel_photo_path' => 'ai_travel_photo/', // 上传目录
    ],
    
    // 阿里百炼通义万相配置
    'aliyun_tongyi' => [
        'api_key' => env('aliyun_tongyi.api_key', ''),
        'api_base_url' => 'https://dashscope.aliyuncs.com',
        'model' => 'wanx-v1',
        'timeout' => 180, // 超时时间（秒）
        'max_retry' => 3, // 最大重试次数
        'cost_per_image' => 0.05, // 每张图片成本（元）
    ],
    
    // 可灵AI配置
    'kling_ai' => [
        'api_key' => env('kling_ai.api_key', ''),
        'api_base_url' => 'https://api.klingai.com',
        'model' => 'kling-v1-5',
        'timeout' => 600, // 超时时间（秒）
        'max_retry' => 3, // 最大重试次数
        'cost_per_video_5s' => 0.50, // 5秒视频成本（元）
        'cost_per_video_10s' => 1.00, // 10秒视频成本（元）
    ],
    
    // Redis队列配置
    'queue' => [
        // 抠图队列
        'cutout' => [
            'name' => 'ai_cutout',
            'priority' => 'high',
            'concurrent' => 10,
            'timeout' => 120,
        ],
        // 图生图队列
        'image_generation' => [
            'name' => 'ai_image_generation',
            'priority' => 'normal',
            'concurrent' => 5,
            'timeout' => 180,
        ],
        // 图生视频队列
        'video_generation' => [
            'name' => 'ai_video_generation',
            'priority' => 'low',
            'concurrent' => 3,
            'timeout' => 600,
        ],
        // 图片处理队列
        'image_process' => [
            'name' => 'image_process',
            'priority' => 'normal',
            'concurrent' => 10,
            'timeout' => 60,
        ],
    ],
    
    // 水印配置
    'watermark' => [
        'default_text' => 'AI旅拍',
        'font_size' => 24,
        'font_color' => '#FFFFFF',
        'opacity' => 80, // 透明度（0-100）
        'position' => 1, // 1右下 2左下 3右上 4左上
        'margin' => 20, // 边距（px）
    ],
    
    // 二维码配置
    'qrcode' => [
        'size' => 300, // 尺寸（px）
        'margin' => 10, // 边距
        'expire_days' => 30, // 默认有效期（天）
        'base_url' => env('app.url', '') . '/h5/ai_travel_photo.html', // 扫码跳转地址
    ],
    
    // 价格配置
    'price' => [
        'default_photo_price' => 9.90, // 默认单张图片价格
        'default_video_price' => 29.90, // 默认单个视频价格
    ],
    
    // 订单配置
    'order' => [
        'timeout' => 1800, // 订单超时时间（秒），30分钟
        'auto_complete_days' => 7, // 自动完成天数
    ],
    
    // 限流配置
    'rate_limit' => [
        'upload' => [
            'max' => 20, // 最大上传次数
            'period' => 60, // 时间周期（秒）
        ],
        'scan' => [
            'max' => 30, // 最大扫码次数
            'period' => 60, // 时间周期（秒）
        ],
        'api' => [
            'max' => 100, // 最大API调用次数
            'period' => 60, // 时间周期（秒）
        ],
    ],
    
    // 并发控制
    'concurrent' => [
        'per_business' => 5, // 单商家最大并发任务数
        'global' => 50, // 全局最大并发任务数
    ],
    
    // 限额配置
    'limit' => [
        'daily_generation' => 1000, // 单商家每日最大生成数
        'max_scenes' => 10, // 默认最多生成场景数
    ],
    
    // 图片处理配置
    'image' => [
        'thumbnail_width' => 800, // 缩略图宽度
        'preview_width' => 400, // 预览图宽度
        'quality' => 90, // 图片质量（1-100）
        'allowed_extensions' => ['jpg', 'jpeg', 'png'], // 允许的文件扩展名
        'max_size' => 10 * 1024 * 1024, // 最大文件大小（10MB）
    ],
    
    // 场景类型常量定义
    'scene_type' => [
        1 => '图生图-单图编辑',
        2 => '图生图-多图融合',
        3 => '视频生成-首帧',
        4 => '视频生成-首尾帧',
        5 => '视频生成-特效',
        6 => '视频生成-参考生成',
    ],
    
    // 场景类型对应的功能说明
    'scene_type_desc' => [
        1 => '对单张人像进行风格化处理、背景替换',
        2 => '将多张素材融合到同一场景中',
        3 => '基于首帧图像生成5秒动态视频',
        4 => '基于首尾两帧生成平滑过渡视频',
        5 => '对现有视频添加特效（慢动作、时间倒流等）',
        6 => '基于参考视频的运动轨迹生成新视频',
    ],
    
    // 场景类型输入要求
    'scene_type_input' => [
        1 => ['image_url'], // 单图编辑：需要人像图片
        2 => ['image_url', 'ref_img'], // 多图融合：需要人像+参考图
        3 => ['image_url'], // 首帧生成：需要首帧图片
        4 => ['image_url', 'tail_image_url'], // 首尾帧：需要首帧+尾帧
        5 => ['video_url'], // 视频特效：需要视频文件
        6 => ['image_url', 'ref_video_url'], // 参考生成：需要图片+参考视频
    ],
    
    // 结果类型常量
    'result_type' => [
        1 => '第1张图',
        2 => '第2张图',
        3 => '第3张图',
        4 => '第4张图',
        5 => '第5张图',
        6 => '第6张图',
        19 => '视频',
    ],
    
    // 视频配置
    'video' => [
        'duration_options' => [5, 10], // 可选视频时长（秒）
        'default_duration' => 5, // 默认视频时长（秒）
        'aspect_ratio' => '16:9', // 默认宽高比
        'allowed_extensions' => ['mp4', 'mov', 'avi'], // 允许的视频扩展名
        'max_size' => 100 * 1024 * 1024, // 最大视频文件大小（100MB）
    ],
    
    // 缓存配置
    'cache' => [
        'scene_list_ttl' => 3600, // 场景列表缓存时间（秒）
        'business_config_ttl' => 1800, // 商家配置缓存时间（秒）
        'qrcode_detail_ttl' => 300, // 二维码详情缓存时间（秒）
    ],
    
    // 日志配置
    'log' => [
        'upload' => true, // 记录上传日志
        'generation' => true, // 记录生成日志
        'payment' => true, // 记录支付日志
        'queue' => true, // 记录队列日志
        'retention_days' => 30, // 日志保留天数
    ],
    
    // 定时任务配置
    'schedule' => [
        // 数据统计（每日凌晨1点）
        'daily_statistics' => [
            'enabled' => true,
            'time' => '01:00',
        ],
        // 二维码过期检查（每小时）
        'qrcode_expire_check' => [
            'enabled' => true,
            'interval' => 3600,
        ],
        // 订单自动关闭（每5分钟）
        'order_auto_close' => [
            'enabled' => true,
            'interval' => 300,
        ],
        // 队列监控（每1分钟）
        'queue_monitor' => [
            'enabled' => true,
            'interval' => 60,
        ],
    ],
    
    // 监控告警配置
    'monitor' => [
        'queue_backlog_threshold' => 1000, // 队列积压阈值
        'generation_fail_rate_threshold' => 5, // 生成失败率阈值（%）
        'api_response_time_threshold' => 90, // API响应时间阈值（秒）
        'alert_emails' => [], // 告警邮箱列表
        'alert_phones' => [], // 告警手机号列表
    ],
    
    // 安全配置
    'security' => [
        'api_key_encryption' => true, // API密钥加密存储
        'url_signature' => true, // URL签名验证
        'signature_expire' => 3600, // 签名过期时间（秒）
        'ip_whitelist' => [], // IP白名单
        'ip_blacklist' => [], // IP黑名单
    ],
];
