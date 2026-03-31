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

    // 爱诗科技（PixVerse）配置 - 通过阿里云百炼DashScope平台接入
    'aishi' => [
        'api_url' => 'https://dashscope.aliyuncs.com',
        'generation_endpoint' => '/api/v1/services/aigc/video-generation/video-synthesis',
        'task_query_endpoint' => '/api/v1/tasks/',
        'timeout' => 30,
        'poll_interval' => 10,
        'max_poll_time' => 600,
        'max_retry' => 3,
        
        // PixVerse模型能力注册
        'models' => [
            // 图生视频-基于首帧：1张图片 + 可选prompt → 视频
            'pixverse/pixverse-v5.6-it2v' => [
                'name' => '爱诗PixVerse V5.6 图生视频（首帧）',
                'text_to_video' => false,
                'first_frame' => true,
                'first_last_frame' => false,
                'reference_images' => false,
                'with_audio' => true,
                'max_duration' => 10,
                'resolutions' => ['360P', '540P', '720P', '1080P'],
                'durations' => [5, 8, 10],  // 1080P仅支持5、8
                'price_per_second' => 0.50,
            ],
            // 图生视频-基于首尾帧：首帧+尾帧 + 必选prompt → 视频
            'pixverse/pixverse-v5.6-kf2v' => [
                'name' => '爱诗PixVerse V5.6 首尾帧生视频',
                'text_to_video' => false,
                'first_frame' => true,
                'first_last_frame' => true,
                'reference_images' => false,
                'with_audio' => true,
                'max_duration' => 10,
                'resolutions' => ['360P', '540P', '720P', '1080P'],
                'durations' => [5, 8, 10],  // 1080P仅支持5、8
                'price_per_second' => 0.50,
            ],
            // 参考生视频：1-7张参考图 + 必选prompt → 视频
            'pixverse/pixverse-v5.6-r2v' => [
                'name' => '爱诗PixVerse V5.6 参考生视频',
                'text_to_video' => false,
                'first_frame' => false,
                'first_last_frame' => false,
                'reference_images' => true,
                'with_audio' => true,
                'max_duration' => 10,
                'max_reference_images' => 7,
                'resolutions' => ['360P', '540P', '720P', '1080P'],
                'durations' => [5, 8, 10],  // 1080P仅支持5、8
                'price_per_second' => 0.50,
                // r2v使用size参数(宽*高)而非resolution
                'use_size_param' => true,
                'size_options' => [
                    '360P'  => ['640*360', '640*480', '640*640', '480*640', '360*640'],
                    '540P'  => ['1024*576', '1024*768', '1024*1024', '768*1024', '576*1024'],
                    '720P'  => ['1280*720', '1108*830', '960*960', '830*1108', '720*1280'],
                    '1080P' => ['1920*1080', '1662*1246', '1440*1440', '1246*1662', '1080*1920'],
                ],
            ],
        ],
    ],

    // 即梦AI（火山引擎CV）配置
    'jimeng' => [
        'api_url' => 'https://visual.volcengineapi.com',
        'submit_action' => 'CVSync2AsyncSubmitTask',
        'query_action' => 'CVSync2AsyncGetResult',
        'api_version' => '2022-08-31',
        'region' => 'cn-north-1',
        'service' => 'cv',
        'timeout' => 300,
        'poll_interval' => 5,
        'max_poll_time' => 600,
        'max_retry' => 3,
        
        // 即梦模型能力注册
        'models' => [
            // ===== 图片生成 =====
            'jimeng_t2i_v40' => [
                'name' => '即梦AI-图片生成4.0',
                'type' => 'image',
                'req_key' => 'jimeng_t2i_v40',
                'text_to_image' => true,
                'image_to_image' => true,
                'max_input_images' => 10,
                'max_output_images' => 15,
                'supported_resolutions' => [
                    '1K' => [
                        '1024x1024',
                    ],
                    '2K' => [
                        '2048x2048', '2304x1728', '2496x1664', '2560x1440', '3024x1296',
                    ],
                    '4K' => [
                        '4096x4096', '4694x3520', '4992x3328', '5404x3040', '6198x2656',
                    ],
                ],
                'default_size' => 4194304,  // 2048*2048
                'price_per_image' => 0.06,  // 每张图0.06元
            ],
            
            // ===== 视频生成3.0 720P =====
            'jimeng_video_v30_720p' => [
                'name' => '即梦AI-视频生成3.0 720P',
                'type' => 'video',
                'resolution' => '720P',
                // 不同模式使用不同的req_key（已从火山引擎文档确认）
                'modes' => [
                    'text_to_video' => [
                        'req_key' => 'jimeng_t2v_v30',
                        'description' => '输入文本提示词，生成720P视频',
                    ],
                    'first_frame' => [
                        'req_key' => 'jimeng_i2v_first_v30',
                        'description' => '输入首帧图片和文本提示词，生成720P视频',
                    ],
                    'first_last_frame' => [
                        'req_key' => 'jimeng_i2v_first_tail_v30',
                        'description' => '输入首尾帧图片和文本提示词，生成720P视频',
                    ],
                    'camera_motion' => [
                        'req_key' => 'jimeng_i2v_recamera_v30',
                        'description' => '输入首帧图片、运镜模板和强度，生成720P运镜视频',
                    ],
                ],
                'text_to_video' => true,
                'first_frame' => true,
                'first_last_frame' => true,
                'camera_motion' => true,
                'with_audio' => false,
                'max_duration' => 10,
                'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4', '21:9', '9:21'],
                // 运镜模板ID（template_id），来自火山引擎API文档
                'camera_types' => [
                    'hitchcock_dolly_in',        // 希区柯克推进
                    'hitchcock_dolly_out',       // 希区柯克拉远
                    'robo_arm',                  // 机械臂
                    'dynamic_orbit',             // 动感环绕
                    'central_orbit',             // 中心环绕
                    'crane_push',                // 起重机
                    'quick_pull_back',           // 超级拉远
                    'counterclockwise_swivel',   // 逆时针回旋
                    'clockwise_swivel',          // 顺时针回旋
                    'handheld',                  // 手持运镜
                    'rapid_push_pull',           // 快速推拉
                ],
                // 运镜强度（camera_strength）
                'camera_strengths' => ['weak', 'medium', 'strong'],
                'price_per_video' => 0.40,  // 每条视频0.40元
            ],
            
            // ===== 视频生成3.0 1080P =====
            'jimeng_video_v30_1080p' => [
                'name' => '即梦AI-视频生成3.0 1080P',
                'type' => 'video',
                'resolution' => '1080P',
                // 不同模式使用不同的req_key（已从火山引擎文档确认）
                'modes' => [
                    'text_to_video' => [
                        'req_key' => 'jimeng_t2v_v30_1080p',
                        'description' => '输入文本提示词，生成1080P视频',
                    ],
                    'first_frame' => [
                        'req_key' => 'jimeng_i2v_first_v30_1080',
                        'description' => '输入首帧图片和文本提示词，生成1080P视频',
                    ],
                    'first_last_frame' => [
                        'req_key' => 'jimeng_i2v_first_tail_v30_1080',
                        'description' => '输入首尾帧图片和文本提示词，生成1080P视频',
                    ],
                ],
                'text_to_video' => true,
                'first_frame' => true,
                'first_last_frame' => true,
                'camera_motion' => false,  // 1080P不支持运镜
                'with_audio' => false,
                'max_duration' => 10,
                'aspect_ratios' => ['16:9', '9:16', '1:1', '4:3', '3:4', '21:9', '9:21'],
                'price_per_video' => 0.80,  // 每条视频0.80元
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
        'camera_motion' => [
            'name' => '运镜图生视频',
            'description' => '首帧图片+运镜控制生成视频',
            'required_params' => ['first_frame_image', 'camera_type'],
        ],
    ],
];
