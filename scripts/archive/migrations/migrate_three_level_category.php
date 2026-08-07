<?php
/**
 * AI模型三级分类体系迁移脚本
 * 执行方式：php migrate_three_level_category.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = new think\App();
$app->initialize();

use think\facade\Db;

echo "=== AI模型三级分类体系迁移 ===\n\n";

try {
    Db::startTrans();
    
    // 1. 检查并添加分类层级字段
    echo "【步骤1】添加分类层级字段...\n";
    
    $columns = Db::query("SHOW COLUMNS FROM ddwx_ai_model_category LIKE 'level'");
    if (empty($columns)) {
        Db::execute("ALTER TABLE `ddwx_ai_model_category` 
            ADD COLUMN `level` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '分类层级：1=一级分类，2=二级分类' AFTER `code`,
            ADD COLUMN `parent_code` VARCHAR(50) DEFAULT NULL COMMENT '父级分类代码' AFTER `level`,
            ADD INDEX `idx_parent_code` (`parent_code`),
            ADD INDEX `idx_level` (`level`)");
        echo "✓ 字段添加成功\n";
    } else {
        echo "✓ 字段已存在，跳过\n";
    }
    
    // 2. 清空现有分类，插入新的三级体系
    echo "\n【步骤2】插入三级分类数据...\n";
    
    Db::execute("TRUNCATE TABLE `ddwx_ai_model_category`");
    
    // 一级分类
    $level1Categories = [
        ['dialogue', '对话模型', '大语言模型、聊天机器人、智能问答', '💬', 100],
        ['image_generation', '图像生成', '文生图、图像编辑、图像增强', '🎨', 90],
        ['video_generation', '视频生成', '文生视频、视频编辑、视频合成', '🎬', 80],
        ['specialized', '专项模型', '特定领域专用模型', '🔧', 70],
        ['realtime_multimodal', '实时多模态', '实时语音视频交互模型', '⚡', 60],
        ['tts', '语音合成', 'TTS文字转语音', '🔊', 50],
        ['asr', '语音识别', 'ASR语音转文字', '🎤', 40],
        ['translation', '语言翻译', '机器翻译、多语言互译', '🌐', 30],
        ['text_embedding', '通用文本向量', '文本嵌入、语义检索', '📊', 20],
        ['multimodal_embedding', '多模态向量', '图文向量、多模态检索', '🔮', 10],
    ];
    
    foreach ($level1Categories as $cat) {
        Db::name('ai_model_category')->insert([
            'code' => $cat[0],
            'level' => 1,
            'parent_code' => null,
            'name' => $cat[1],
            'description' => $cat[2],
            'icon' => $cat[3],
            'sort' => $cat[4],
            'status' => 1,
            'is_system' => 1
        ]);
    }
    echo "✓ 一级分类插入完成（{$level1Count}个）\n";
    
    // 二级分类
    $level2Categories = [
        // 图像生成子分类
        ['qwen_text_to_image', 'image_generation', '通义千问文生图', '阿里云通义千问文生图系列模型', '🖼️', 100],
        ['qwen_image_edit', 'image_generation', '通义千问图像编辑', '阿里云通义千问图像编辑系列模型', '✏️', 90],
        ['dalle', 'image_generation', 'DALL-E系列', 'OpenAI DALL-E图像生成模型', '🎭', 80],
        ['stable_diffusion', 'image_generation', 'Stable Diffusion', 'Stability AI开源图像生成模型', '🌈', 70],
        ['midjourney', 'image_generation', 'Midjourney', 'Midjourney艺术风格图像生成', '🎨', 60],
        
        // 对话模型子分类
        ['qwen_turbo', 'dialogue', '通义千问Turbo', '阿里云通义千问对话模型', '⚡', 100],
        ['gpt', 'dialogue', 'GPT系列', 'OpenAI GPT对话模型', '🤖', 90],
        ['claude', 'dialogue', 'Claude系列', 'Anthropic Claude对话模型', '🧠', 80],
        ['gemini', 'dialogue', 'Gemini系列', 'Google Gemini对话模型', '✨', 70],
        
        // 视频生成子分类
        ['keling', 'video_generation', '可灵视频', '快手可灵文生视频模型', '🎥', 100],
        ['runway', 'video_generation', 'Runway系列', 'Runway AI视频生成模型', '🎬', 90],
        ['pika', 'video_generation', 'Pika系列', 'Pika Labs视频生成模型', '📹', 80],
        
        // 语音合成子分类
        ['cosyvoice', 'tts', 'CosyVoice', '阿里云语音合成模型', '🔊', 100],
        ['azure_tts', 'tts', 'Azure TTS', '微软Azure语音合成', '🎙️', 90],
        
        // 语音识别子分类
        ['paraformer', 'asr', 'Paraformer', '阿里云语音识别模型', '🎤', 100],
        ['whisper', 'asr', 'Whisper系列', 'OpenAI Whisper语音识别', '🎧', 90],
    ];
    
    foreach ($level2Categories as $cat) {
        Db::name('ai_model_category')->insert([
            'code' => $cat[0],
            'level' => 2,
            'parent_code' => $cat[1],
            'name' => $cat[2],
            'description' => $cat[3],
            'icon' => $cat[4],
            'sort' => $cat[5],
            'status' => 1,
            'is_system' => 1
        ]);
    }
    echo "✓ 二级分类插入完成（" . count($level2Categories) . "个）\n";
    
    // 3. 为模型配置表添加二级分类字段
    echo "\n【步骤3】添加模型配置表二级分类字段...\n";
    
    $columns = Db::query("SHOW COLUMNS FROM ddwx_ai_travel_photo_model LIKE 'category_level2_code'");
    if (empty($columns)) {
        Db::execute("ALTER TABLE `ddwx_ai_travel_photo_model`
            ADD COLUMN `category_level2_code` VARCHAR(50) DEFAULT NULL COMMENT '二级分类代码' AFTER `category_code`,
            ADD COLUMN `model_version` VARCHAR(100) DEFAULT NULL COMMENT '模型版本号' AFTER `provider`,
            ADD INDEX `idx_category_level2` (`category_level2_code`)");
        echo "✓ 字段添加成功\n";
    } else {
        echo "✓ 字段已存在，跳过\n";
    }
    
    // 4. 更新字段注释
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_model` 
        MODIFY COLUMN `category_code` VARCHAR(50) NOT NULL COMMENT '一级分类代码',
        MODIFY COLUMN `model_type` VARCHAR(50) DEFAULT NULL COMMENT '【已废弃】旧字段，保留兼容'");
    
    // 5. 插入示例模型配置（通义千问文生图）
    echo "\n【步骤4】插入示例模型配置...\n";
    
    $exampleModels = [
        [
            'model_name' => 'Qwen通义万相-Max',
            'category_code' => 'image_generation',
            'category_level2_code' => 'qwen_text_to_image',
            'provider' => 'aliyun',
            'model_version' => 'wanx-v1',
            'api_key' => 'sk-xxxxxxxxxxxxxxxxxx',
            'api_base_url' => 'https://dashscope.aliyuncs.com/api/v1',
            'description' => '通义万相文生图最新版本',
        ],
        [
            'model_name' => 'Qwen Image Max',
            'category_code' => 'image_generation',
            'category_level2_code' => 'qwen_text_to_image',
            'provider' => 'aliyun',
            'model_version' => 'qwen-image-max',
            'api_key' => 'sk-xxxxxxxxxxxxxxxxxx',
            'api_base_url' => 'https://dashscope.aliyuncs.com/api/v1',
            'description' => 'Qwen Image Max基础版',
        ],
        [
            'model_name' => 'Qwen Image Max 2025',
            'category_code' => 'image_generation',
            'category_level2_code' => 'qwen_text_to_image',
            'provider' => 'aliyun',
            'model_version' => 'qwen-image-max-2025-12-30',
            'api_key' => 'sk-xxxxxxxxxxxxxxxxxx',
            'api_base_url' => 'https://dashscope.aliyuncs.com/api/v1',
            'description' => 'Qwen Image Max 2025年12月版本',
        ],
    ];
    
    // 注意：这里只是示例，实际不插入，避免干扰现有数据
    echo "✓ 示例数据准备完成（需手动添加）\n";
    
    Db::commit();
    
    echo "\n=== 迁移完成 ===\n";
    echo "✓ 一级分类：10个\n";
    echo "✓ 二级分类：" . count($level2Categories) . "个\n";
    echo "✓ 数据库结构升级完成\n\n";
    
    echo "【下一步操作】\n";
    echo "1. 访问后台 -> AI旅拍 -> 模型设置 -> 模型分类管理\n";
    echo "2. 查看新的三级分类体系\n";
    echo "3. 在API配置管理中添加具体模型（如qwen-image-max）\n";
    
} catch (\Exception $e) {
    Db::rollback();
    echo "\n✗ 迁移失败: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
    exit(1);
}
