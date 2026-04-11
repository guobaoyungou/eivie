<?php
/**
 * 工作流系统种子数据
 * 插入预设模板 + 系统预置资源
 */
$db = require dirname(__DIR__, 2) . '/config.php';
$pdo = new PDO(
    'mysql:host=' . $db['hostname'] . ';dbname=' . $db['database'] . ';charset=utf8mb4',
    $db['username'],
    $db['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$now = time();

// ================================================================
// 1. 预设短剧模板（4大题材）
// ================================================================
$templates = [
    [
        'template_name' => '甜宠恋爱·霸道总裁',
        'genre'         => '甜宠',
        'description'   => '经典甜宠剧模板：霸道总裁与灰姑娘的浪漫爱情故事，包含相遇→误解→相知→告白→HE完整剧情线',
        'cover_image'   => '',
        'canvas_template' => json_encode([
            'nodes' => [
                ['id' => 'tpl_n1', 'node_type' => 'script',     'node_label' => '甜宠剧本生成',   'position_x' => 80,  'position_y' => 200, 'config_params' => ['genre' => '甜宠', 'style' => '都市言情']],
                ['id' => 'tpl_n2', 'node_type' => 'character',   'node_label' => '角色形象设计',   'position_x' => 350, 'position_y' => 80],
                ['id' => 'tpl_n3', 'node_type' => 'storyboard',  'node_label' => '分镜绘制',       'position_x' => 620, 'position_y' => 200],
                ['id' => 'tpl_n4', 'node_type' => 'video',       'node_label' => '视频生成',       'position_x' => 890, 'position_y' => 200],
                ['id' => 'tpl_n5', 'node_type' => 'voice',       'node_label' => '配音合成',       'position_x' => 620, 'position_y' => 400],
                ['id' => 'tpl_n6', 'node_type' => 'compose',     'node_label' => '成片合成',       'position_x' => 1100, 'position_y' => 300],
            ],
            'edges' => [
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n2', 'source_port' => 'characters',      'target_port' => 'characters'],
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n3', 'source_port' => 'scenes',           'target_port' => 'scenes'],
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n5', 'source_port' => 'dialogue',         'target_port' => 'dialogue'],
                ['source_node_id' => 'tpl_n2', 'target_node_id' => 'tpl_n3', 'source_port' => 'character_assets', 'target_port' => 'character_assets'],
                ['source_node_id' => 'tpl_n3', 'target_node_id' => 'tpl_n4', 'source_port' => 'frames',           'target_port' => 'frames'],
                ['source_node_id' => 'tpl_n4', 'target_node_id' => 'tpl_n6', 'source_port' => 'clips',            'target_port' => 'clips'],
                ['source_node_id' => 'tpl_n5', 'target_node_id' => 'tpl_n6', 'source_port' => 'audio_clips',      'target_port' => 'audio_clips'],
            ],
        ], JSON_UNESCAPED_UNICODE),
        'default_models'    => json_encode(['script' => 0, 'character' => 0, 'storyboard' => 0, 'video' => 0, 'voice' => 0]),
        'default_voice_ids' => json_encode(['male_lead' => 'voice_male_warm', 'female_lead' => 'voice_female_sweet']),
        'sort'   => 100,
        'status' => 1,
    ],
    [
        'template_name' => '逆袭爽文·屌丝翻身',
        'genre'         => '逆袭',
        'description'   => '逆袭爽剧模板：主角从底层出发，获得奇遇/金手指，一路打脸反派，最终走上人生巅峰',
        'cover_image'   => '',
        'canvas_template' => json_encode([
            'nodes' => [
                ['id' => 'tpl_n1', 'node_type' => 'script',     'node_label' => '逆袭剧本生成',   'position_x' => 80,  'position_y' => 200, 'config_params' => ['genre' => '逆袭', 'style' => '都市爽文']],
                ['id' => 'tpl_n2', 'node_type' => 'character',   'node_label' => '角色形象设计',   'position_x' => 350, 'position_y' => 80],
                ['id' => 'tpl_n3', 'node_type' => 'storyboard',  'node_label' => '分镜绘制',       'position_x' => 620, 'position_y' => 200],
                ['id' => 'tpl_n4', 'node_type' => 'video',       'node_label' => '视频生成',       'position_x' => 890, 'position_y' => 200],
                ['id' => 'tpl_n5', 'node_type' => 'voice',       'node_label' => '配音合成',       'position_x' => 620, 'position_y' => 400],
                ['id' => 'tpl_n6', 'node_type' => 'compose',     'node_label' => '成片合成',       'position_x' => 1100, 'position_y' => 300],
            ],
            'edges' => [
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n2', 'source_port' => 'characters',      'target_port' => 'characters'],
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n3', 'source_port' => 'scenes',           'target_port' => 'scenes'],
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n5', 'source_port' => 'dialogue',         'target_port' => 'dialogue'],
                ['source_node_id' => 'tpl_n2', 'target_node_id' => 'tpl_n3', 'source_port' => 'character_assets', 'target_port' => 'character_assets'],
                ['source_node_id' => 'tpl_n3', 'target_node_id' => 'tpl_n4', 'source_port' => 'frames',           'target_port' => 'frames'],
                ['source_node_id' => 'tpl_n4', 'target_node_id' => 'tpl_n6', 'source_port' => 'clips',            'target_port' => 'clips'],
                ['source_node_id' => 'tpl_n5', 'target_node_id' => 'tpl_n6', 'source_port' => 'audio_clips',      'target_port' => 'audio_clips'],
            ],
        ], JSON_UNESCAPED_UNICODE),
        'default_models'    => json_encode(['script' => 0, 'character' => 0, 'storyboard' => 0, 'video' => 0, 'voice' => 0]),
        'default_voice_ids' => json_encode(['male_lead' => 'voice_male_power', 'narrator' => 'voice_male_deep']),
        'sort'   => 90,
        'status' => 1,
    ],
    [
        'template_name' => '悬疑烧脑·密室迷踪',
        'genre'         => '悬疑',
        'description'   => '悬疑推理模板：暗黑色调+紧凑节奏，层层反转的剧情设计，适合悬疑/恐怖/推理类短剧',
        'cover_image'   => '',
        'canvas_template' => json_encode([
            'nodes' => [
                ['id' => 'tpl_n1', 'node_type' => 'script',     'node_label' => '悬疑剧本生成',   'position_x' => 80,  'position_y' => 200, 'config_params' => ['genre' => '悬疑', 'style' => '暗黑推理']],
                ['id' => 'tpl_n2', 'node_type' => 'character',   'node_label' => '角色形象设计',   'position_x' => 350, 'position_y' => 80],
                ['id' => 'tpl_n3', 'node_type' => 'storyboard',  'node_label' => '分镜绘制',       'position_x' => 620, 'position_y' => 200],
                ['id' => 'tpl_n4', 'node_type' => 'video',       'node_label' => '视频生成',       'position_x' => 890, 'position_y' => 200],
                ['id' => 'tpl_n5', 'node_type' => 'voice',       'node_label' => '配音合成',       'position_x' => 620, 'position_y' => 400],
                ['id' => 'tpl_n6', 'node_type' => 'compose',     'node_label' => '成片合成',       'position_x' => 1100, 'position_y' => 300],
            ],
            'edges' => [
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n2', 'source_port' => 'characters',      'target_port' => 'characters'],
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n3', 'source_port' => 'scenes',           'target_port' => 'scenes'],
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n5', 'source_port' => 'dialogue',         'target_port' => 'dialogue'],
                ['source_node_id' => 'tpl_n2', 'target_node_id' => 'tpl_n3', 'source_port' => 'character_assets', 'target_port' => 'character_assets'],
                ['source_node_id' => 'tpl_n3', 'target_node_id' => 'tpl_n4', 'source_port' => 'frames',           'target_port' => 'frames'],
                ['source_node_id' => 'tpl_n4', 'target_node_id' => 'tpl_n6', 'source_port' => 'clips',            'target_port' => 'clips'],
                ['source_node_id' => 'tpl_n5', 'target_node_id' => 'tpl_n6', 'source_port' => 'audio_clips',      'target_port' => 'audio_clips'],
            ],
        ], JSON_UNESCAPED_UNICODE),
        'default_models'    => json_encode(['script' => 0, 'character' => 0, 'storyboard' => 0, 'video' => 0, 'voice' => 0]),
        'default_voice_ids' => json_encode(['narrator' => 'voice_male_deep', 'detective' => 'voice_male_calm']),
        'sort'   => 80,
        'status' => 1,
    ],
    [
        'template_name' => '搞笑日常·沙雕欢乐',
        'genre'         => '搞笑',
        'description'   => '搞笑喜剧模板：轻松幽默的日常喜剧，适合搞笑段子/沙雕日常/反转喜剧类短剧',
        'cover_image'   => '',
        'canvas_template' => json_encode([
            'nodes' => [
                ['id' => 'tpl_n1', 'node_type' => 'script',     'node_label' => '搞笑剧本生成',   'position_x' => 80,  'position_y' => 200, 'config_params' => ['genre' => '搞笑', 'style' => '日常喜剧']],
                ['id' => 'tpl_n2', 'node_type' => 'character',   'node_label' => '角色形象设计',   'position_x' => 350, 'position_y' => 80],
                ['id' => 'tpl_n3', 'node_type' => 'storyboard',  'node_label' => '分镜绘制',       'position_x' => 620, 'position_y' => 200],
                ['id' => 'tpl_n4', 'node_type' => 'video',       'node_label' => '视频生成',       'position_x' => 890, 'position_y' => 200],
                ['id' => 'tpl_n5', 'node_type' => 'voice',       'node_label' => '配音合成',       'position_x' => 620, 'position_y' => 400],
                ['id' => 'tpl_n6', 'node_type' => 'compose',     'node_label' => '成片合成',       'position_x' => 1100, 'position_y' => 300],
            ],
            'edges' => [
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n2', 'source_port' => 'characters',      'target_port' => 'characters'],
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n3', 'source_port' => 'scenes',           'target_port' => 'scenes'],
                ['source_node_id' => 'tpl_n1', 'target_node_id' => 'tpl_n5', 'source_port' => 'dialogue',         'target_port' => 'dialogue'],
                ['source_node_id' => 'tpl_n2', 'target_node_id' => 'tpl_n3', 'source_port' => 'character_assets', 'target_port' => 'character_assets'],
                ['source_node_id' => 'tpl_n3', 'target_node_id' => 'tpl_n4', 'source_port' => 'frames',           'target_port' => 'frames'],
                ['source_node_id' => 'tpl_n4', 'target_node_id' => 'tpl_n6', 'source_port' => 'clips',            'target_port' => 'clips'],
                ['source_node_id' => 'tpl_n5', 'target_node_id' => 'tpl_n6', 'source_port' => 'audio_clips',      'target_port' => 'audio_clips'],
            ],
        ], JSON_UNESCAPED_UNICODE),
        'default_models'    => json_encode(['script' => 0, 'character' => 0, 'storyboard' => 0, 'video' => 0, 'voice' => 0]),
        'default_voice_ids' => json_encode(['male_lead' => 'voice_male_funny', 'female_lead' => 'voice_female_cute']),
        'sort'   => 70,
        'status' => 1,
    ],
];

$tplInserted = 0;
$stmt = $pdo->prepare("INSERT INTO ddwx_workflow_preset_template (aid, bid, template_name, genre, description, cover_image, canvas_template, default_models, default_voice_ids, sort, status, create_time, update_time) VALUES (0, 0, :template_name, :genre, :description, :cover_image, :canvas_template, :default_models, :default_voice_ids, :sort, :status, :create_time, :update_time)");

foreach ($templates as $tpl) {
    $stmt->execute([
        ':template_name'    => $tpl['template_name'],
        ':genre'            => $tpl['genre'],
        ':description'      => $tpl['description'],
        ':cover_image'      => $tpl['cover_image'],
        ':canvas_template'  => $tpl['canvas_template'],
        ':default_models'   => $tpl['default_models'],
        ':default_voice_ids'=> $tpl['default_voice_ids'],
        ':sort'             => $tpl['sort'],
        ':status'           => $tpl['status'],
        ':create_time'      => $now,
        ':update_time'      => $now,
    ]);
    $tplInserted++;
}

echo "预设模板插入: {$tplInserted} 条\n";

// ================================================================
// 2. 系统预置资源
// ================================================================
$resources = [
    // 风格资源
    ['name' => '写实风格', 'resource_type' => 'style', 'thumbnail' => '', 'content_data' => json_encode(['style_code' => 'realistic', 'prompt_prefix' => 'photorealistic, ultra-detailed, 8k uhd, masterpiece', 'negative_prompt' => 'cartoon, anime, illustration, painting, drawing', 'cfg_scale' => 7.5, 'sampler' => 'DPM++ 2M Karras'], JSON_UNESCAPED_UNICODE), 'sort' => 100],
    ['name' => '动漫二次元', 'resource_type' => 'style', 'thumbnail' => '', 'content_data' => json_encode(['style_code' => 'anime', 'prompt_prefix' => 'anime style, vibrant colors, detailed illustration, anime key visual', 'negative_prompt' => 'photorealistic, photo, 3d render', 'cfg_scale' => 8.0, 'sampler' => 'Euler a'], JSON_UNESCAPED_UNICODE), 'sort' => 90],
    ['name' => '国风水墨', 'resource_type' => 'style', 'thumbnail' => '', 'content_data' => json_encode(['style_code' => 'chinese_ink', 'prompt_prefix' => 'traditional chinese ink painting, watercolor, elegant, chinese brush style', 'negative_prompt' => 'modern, western, 3d, cartoon', 'cfg_scale' => 7.0, 'sampler' => 'DPM++ SDE Karras'], JSON_UNESCAPED_UNICODE), 'sort' => 80],
    ['name' => '3D渲染', 'resource_type' => 'style', 'thumbnail' => '', 'content_data' => json_encode(['style_code' => '3d_render', 'prompt_prefix' => '3d render, octane render, cinema 4d, unreal engine 5, highly detailed', 'negative_prompt' => '2d, flat, painting, sketch, hand-drawn', 'cfg_scale' => 7.5, 'sampler' => 'DPM++ 2M Karras'], JSON_UNESCAPED_UNICODE), 'sort' => 70],
    ['name' => '赛博朋克', 'resource_type' => 'style', 'thumbnail' => '', 'content_data' => json_encode(['style_code' => 'cyberpunk', 'prompt_prefix' => 'cyberpunk style, neon lights, futuristic city, sci-fi, dark atmosphere', 'negative_prompt' => 'nature, pastoral, historical, ancient', 'cfg_scale' => 8.0, 'sampler' => 'Euler a'], JSON_UNESCAPED_UNICODE), 'sort' => 60],

    // 音色资源
    ['name' => '温柔男声', 'resource_type' => 'voice', 'thumbnail' => '', 'content_data' => json_encode(['voice_id' => 'voice_male_warm', 'voice_type' => 'male', 'voice_style' => 'warm', 'speed' => 1.0, 'pitch' => 0, 'sample_rate' => 48000, 'description' => '适合甜宠剧男主角，温暖磁性的男声'], JSON_UNESCAPED_UNICODE), 'sort' => 100],
    ['name' => '霸气男声', 'resource_type' => 'voice', 'thumbnail' => '', 'content_data' => json_encode(['voice_id' => 'voice_male_power', 'voice_type' => 'male', 'voice_style' => 'powerful', 'speed' => 1.0, 'pitch' => -2, 'sample_rate' => 48000, 'description' => '适合逆袭剧/动作剧男主角，低沉有力的男声'], JSON_UNESCAPED_UNICODE), 'sort' => 90],
    ['name' => '深沉旁白男声', 'resource_type' => 'voice', 'thumbnail' => '', 'content_data' => json_encode(['voice_id' => 'voice_male_deep', 'voice_type' => 'male', 'voice_style' => 'deep_narrator', 'speed' => 0.95, 'pitch' => -3, 'sample_rate' => 48000, 'description' => '适合悬疑/纪录片风格旁白，深沉严肃的男声'], JSON_UNESCAPED_UNICODE), 'sort' => 85],
    ['name' => '沉稳男声', 'resource_type' => 'voice', 'thumbnail' => '', 'content_data' => json_encode(['voice_id' => 'voice_male_calm', 'voice_type' => 'male', 'voice_style' => 'calm', 'speed' => 0.98, 'pitch' => -1, 'sample_rate' => 48000, 'description' => '适合推理/侦探角色，冷静理性的男声'], JSON_UNESCAPED_UNICODE), 'sort' => 80],
    ['name' => '搞笑男声', 'resource_type' => 'voice', 'thumbnail' => '', 'content_data' => json_encode(['voice_id' => 'voice_male_funny', 'voice_type' => 'male', 'voice_style' => 'humorous', 'speed' => 1.05, 'pitch' => 2, 'sample_rate' => 48000, 'description' => '适合搞笑/喜剧角色，活泼幽默的男声'], JSON_UNESCAPED_UNICODE), 'sort' => 75],
    ['name' => '甜美女声', 'resource_type' => 'voice', 'thumbnail' => '', 'content_data' => json_encode(['voice_id' => 'voice_female_sweet', 'voice_type' => 'female', 'voice_style' => 'sweet', 'speed' => 1.0, 'pitch' => 2, 'sample_rate' => 48000, 'description' => '适合甜宠剧女主角，甜美温柔的女声'], JSON_UNESCAPED_UNICODE), 'sort' => 70],
    ['name' => '可爱女声', 'resource_type' => 'voice', 'thumbnail' => '', 'content_data' => json_encode(['voice_id' => 'voice_female_cute', 'voice_type' => 'female', 'voice_style' => 'cute', 'speed' => 1.05, 'pitch' => 3, 'sample_rate' => 48000, 'description' => '适合搞笑/日常类女角色，元气可爱的女声'], JSON_UNESCAPED_UNICODE), 'sort' => 65],
    ['name' => '御姐女声', 'resource_type' => 'voice', 'thumbnail' => '', 'content_data' => json_encode(['voice_id' => 'voice_female_mature', 'voice_type' => 'female', 'voice_style' => 'mature', 'speed' => 0.98, 'pitch' => 0, 'sample_rate' => 48000, 'description' => '适合职场/悬疑类女角色，成熟优雅的女声'], JSON_UNESCAPED_UNICODE), 'sort' => 60],

    // 素材资源
    ['name' => '都市场景包', 'resource_type' => 'material', 'thumbnail' => '', 'content_data' => json_encode(['material_type' => 'scene_pack', 'scenes' => ['办公室', 'CBD街景', '豪华公寓', '咖啡厅', '商场', '夜景天台'], 'prompt_tags' => 'modern city, urban, skyscrapers, office building'], JSON_UNESCAPED_UNICODE), 'sort' => 50],
    ['name' => '古风场景包', 'resource_type' => 'material', 'thumbnail' => '', 'content_data' => json_encode(['material_type' => 'scene_pack', 'scenes' => ['古代宫殿', '竹林小道', '江南水乡', '书房', '后花园', '战场'], 'prompt_tags' => 'ancient chinese, palace, traditional architecture, hanfu'], JSON_UNESCAPED_UNICODE), 'sort' => 45],
    ['name' => '转场特效包', 'resource_type' => 'material', 'thumbnail' => '', 'content_data' => json_encode(['material_type' => 'transition_pack', 'transitions' => [['name' => '淡入淡出', 'ffmpeg_filter' => 'xfade=transition=fade:duration=0.5'], ['name' => '左推', 'ffmpeg_filter' => 'xfade=transition=slideleft:duration=0.5'], ['name' => '圆形擦除', 'ffmpeg_filter' => 'xfade=transition=circleopen:duration=0.5'], ['name' => '模糊过渡', 'ffmpeg_filter' => 'xfade=transition=smoothleft:duration=0.5']]], JSON_UNESCAPED_UNICODE), 'sort' => 40],
    ['name' => '背景音乐·浪漫', 'resource_type' => 'material', 'thumbnail' => '', 'content_data' => json_encode(['material_type' => 'bgm', 'mood' => 'romantic', 'bpm' => 80, 'duration' => 120, 'description' => '轻柔钢琴曲，适合甜宠/爱情场景'], JSON_UNESCAPED_UNICODE), 'sort' => 35],
    ['name' => '背景音乐·紧张', 'resource_type' => 'material', 'thumbnail' => '', 'content_data' => json_encode(['material_type' => 'bgm', 'mood' => 'suspense', 'bpm' => 120, 'duration' => 120, 'description' => '紧张悬疑配乐，适合悬疑/恐怖场景'], JSON_UNESCAPED_UNICODE), 'sort' => 30],
    ['name' => '背景音乐·热血', 'resource_type' => 'material', 'thumbnail' => '', 'content_data' => json_encode(['material_type' => 'bgm', 'mood' => 'epic', 'bpm' => 140, 'duration' => 120, 'description' => '史诗热血配乐，适合逆袭/战斗场景'], JSON_UNESCAPED_UNICODE), 'sort' => 25],
];

$resInserted = 0;
$stmtRes = $pdo->prepare("INSERT INTO ddwx_workflow_resource (aid, bid, mdid, uid, name, resource_type, thumbnail, content_data, is_system, usage_count, sort, status, create_time, update_time) VALUES (0, 0, 0, 0, :name, :resource_type, :thumbnail, :content_data, 1, 0, :sort, 1, :create_time, :update_time)");

foreach ($resources as $res) {
    $stmtRes->execute([
        ':name'          => $res['name'],
        ':resource_type' => $res['resource_type'],
        ':thumbnail'     => $res['thumbnail'],
        ':content_data'  => $res['content_data'],
        ':sort'          => $res['sort'],
        ':create_time'   => $now,
        ':update_time'   => $now,
    ]);
    $resInserted++;
}

echo "系统预置资源插入: {$resInserted} 条\n";
echo "完成！\n";
