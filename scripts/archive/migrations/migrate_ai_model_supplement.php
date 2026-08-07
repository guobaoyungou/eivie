<?php
/**
 * AI模型管理系统 - 补充迁移脚本
 * 执行方式: php migrate_ai_model_supplement.php
 */

// 引入配置
$config = include(__DIR__ . '/config.php');

// 数据库连接信息
$hostname = $config['hostname'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];
$hostport = $config['hostport'];

echo "========================================\n";
echo "AI模型管理系统 - 补充迁移\n";
echo "========================================\n\n";

try {
    $mysqli = new mysqli($hostname, $username, $password, $database, $hostport);
    
    if ($mysqli->connect_error) {
        throw new Exception("数据库连接失败: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    echo "✓ 数据库连接成功\n\n";
    
    // 1. 创建使用记录表
    echo "1. 创建使用记录表...\n";
    $sql1 = "CREATE TABLE IF NOT EXISTS `ddwx_ai_model_usage_log` (
      `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
      `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
      `bid` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID',
      `mdid` int(11) NOT NULL DEFAULT '0' COMMENT '门店ID',
      `model_id` int(11) NOT NULL COMMENT '模型配置ID',
      `category_code` varchar(50) DEFAULT NULL COMMENT '模型分类代码',
      `business_type` varchar(50) DEFAULT NULL COMMENT '业务类型 cutout/image_gen/video_gen',
      `request_params` text COMMENT '请求参数(JSON)',
      `response_data` text COMMENT '响应数据(JSON)',
      `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '调用状态 1=成功 0=失败',
      `error_msg` varchar(500) DEFAULT NULL COMMENT '错误信息',
      `cost_amount` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT '本次消耗金额',
      `response_time` int(11) DEFAULT NULL COMMENT '响应时长(毫秒)',
      `retry_count` tinyint(1) NOT NULL DEFAULT '0' COMMENT '重试次数',
      `create_time` int(11) NOT NULL COMMENT '创建时间戳',
      PRIMARY KEY (`id`),
      KEY `idx_aid_bid_mdid` (`aid`,`bid`,`mdid`),
      KEY `idx_model_id` (`model_id`),
      KEY `idx_category_code` (`category_code`),
      KEY `idx_business_type` (`business_type`),
      KEY `idx_create_time` (`create_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI模型使用记录表'";
    
    if ($mysqli->query($sql1)) {
        echo "   ✓ 创建成功\n";
    } else {
        echo "   ⚠ " . $mysqli->error . "\n";
    }
    
    // 2. 添加缺失的字段
    echo "\n2. 添加模型配置表的扩展字段...\n";
    
    $fields = [
        "ADD COLUMN `mdid` int(11) NOT NULL DEFAULT '0' COMMENT '门店ID，0=商家通用' AFTER `bid`",
        "ADD COLUMN `category_code` varchar(50) DEFAULT NULL COMMENT '模型分类代码' AFTER `model_type`",
        "ADD COLUMN `provider` varchar(50) DEFAULT NULL COMMENT '服务提供商' AFTER `category_code`",
        "ADD COLUMN `image_price` decimal(10,4) NOT NULL DEFAULT '0.0500' COMMENT '图片单价(元)' AFTER `total_cost`",
        "ADD COLUMN `video_price` decimal(10,4) NOT NULL DEFAULT '0.5000' COMMENT '视频单价(元)' AFTER `image_price`",
        "ADD COLUMN `token_price` decimal(10,6) NOT NULL DEFAULT '0.000001' COMMENT 'Token单价(元)' AFTER `video_price`",
        "ADD COLUMN `timeout` int(11) NOT NULL DEFAULT '180' COMMENT '请求超时(秒)' AFTER `token_price`",
        "ADD COLUMN `max_retry` tinyint(1) NOT NULL DEFAULT '3' COMMENT '最大重试次数' AFTER `timeout`",
        "ADD COLUMN `current_concurrent` int(11) NOT NULL DEFAULT '0' COMMENT '当前并发数' AFTER `max_concurrent`",
        "ADD COLUMN `priority` int(11) NOT NULL DEFAULT '100' COMMENT '优先级，值越大优先级越高' AFTER `current_concurrent`",
        "ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否激活 1=激活 0=未激活' AFTER `is_default`",
        "ADD COLUMN `test_passed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '连通性测试 1=通过 0=未通过' AFTER `is_active`",
        "ADD COLUMN `last_test_time` int(11) DEFAULT NULL COMMENT '最后测试时间' AFTER `test_passed`",
        "ADD COLUMN `last_error` varchar(500) DEFAULT NULL COMMENT '最后错误信息' AFTER `last_test_time`"
    ];
    
    foreach ($fields as $field) {
        $sql = "ALTER TABLE `ddwx_ai_travel_photo_model` " . $field;
        if ($mysqli->query($sql)) {
            // 提取字段名
            preg_match('/ADD COLUMN `(\w+)`/', $field, $matches);
            $fieldName = $matches[1] ?? '字段';
            echo "   ✓ 添加 {$fieldName}\n";
        } else {
            // 忽略已存在的错误
            if (strpos($mysqli->error, 'Duplicate column') === false) {
                echo "   ⚠ " . $mysqli->error . "\n";
            }
        }
    }
    
    // 3. 添加索引
    echo "\n3. 添加索引...\n";
    $indexes = [
        "ADD KEY `idx_mdid` (`mdid`)",
        "ADD KEY `idx_category_code` (`category_code`)",
        "ADD KEY `idx_status_active` (`status`,`is_active`)"
    ];
    
    foreach ($indexes as $index) {
        $sql = "ALTER TABLE `ddwx_ai_travel_photo_model` " . $index;
        if ($mysqli->query($sql)) {
            echo "   ✓ 添加索引成功\n";
        } else {
            if (strpos($mysqli->error, 'Duplicate key') === false) {
                echo "   ⚠ " . $mysqli->error . "\n";
            }
        }
    }
    
    // 4. 插入系统预置分类数据
    echo "\n4. 插入系统预置分类...\n";
    $categories = [
        ['千问', 'qianwen', '阿里云通义千问大模型', '🤖', 100],
        ['豆包', 'doubao', '字节跳动豆包大模型', '🎨', 90],
        ['可灵', 'kling', '快手可灵AI视频生成', '🎬', 80],
        ['即梦', 'jimeng', '即梦AI图像生成', '⚡', 70],
        ['OpenAI', 'openai', 'OpenAI GPT系列模型', '🔧', 60],
        ['Ollama', 'ollama', 'Ollama本地大模型', '🦙', 50],
        ['通义万相', 'tongyi_wanxiang', '阿里云通义万相图像生成', '✨', 40],
        ['其他', 'other', '其他自定义AI模型', '🔮', 10]
    ];
    
    $time = time();
    foreach ($categories as $cat) {
        $sql = "INSERT IGNORE INTO `ddwx_ai_model_category` 
                (`aid`, `name`, `code`, `description`, `icon`, `is_system`, `sort`, `status`, `create_time`) 
                VALUES (0, '{$cat[0]}', '{$cat[1]}', '{$cat[2]}', '{$cat[3]}', 1, {$cat[4]}, 1, {$time})";
        if ($mysqli->query($sql)) {
            echo "   ✓ {$cat[0]}\n";
        }
    }
    
    // 5. 更新现有数据的category_code
    echo "\n5. 更新现有数据的category_code...\n";
    $updateSql = "UPDATE `ddwx_ai_travel_photo_model` 
                  SET `category_code` = CASE 
                    WHEN `model_type` LIKE '%tongyi%' OR `model_type` LIKE '%通义%' THEN 'tongyi_wanxiang'
                    WHEN `model_type` LIKE '%kling%' OR `model_type` LIKE '%可灵%' THEN 'kling'
                    WHEN `model_type` LIKE '%qianwen%' OR `model_type` LIKE '%千问%' THEN 'qianwen'
                    WHEN `model_type` LIKE '%doubao%' OR `model_type` LIKE '%豆包%' THEN 'doubao'
                    WHEN `model_type` LIKE '%openai%' THEN 'openai'
                    ELSE 'other'
                  END
                  WHERE `category_code` IS NULL OR `category_code` = ''";
    
    if ($mysqli->query($updateSql)) {
        echo "   ✓ 更新成功，影响行数: " . $mysqli->affected_rows . "\n";
    } else {
        echo "   ⚠ " . $mysqli->error . "\n";
    }
    
    echo "\n========================================\n";
    echo "✅ 补充迁移完成！\n";
    echo "========================================\n\n";
    
    echo "现在可以访问：AI旅拍 > 模型设置\n\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "\n❌ 迁移失败：" . $e->getMessage() . "\n\n";
    exit(1);
}
