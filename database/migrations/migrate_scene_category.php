<?php
/**
 * 场景分类功能数据库迁移脚本
 * 
 * 执行方式: php database/migrations/migrate_scene_category.php
 * 
 * 功能说明:
 * 1. 创建 ddwx_generation_scene_category 表 - 存储场景分类数据
 * 2. 修改 ddwx_generation_scene_template 表 - 添加 category_id 字段
 */

// 引入ThinkPHP框架
require_once __DIR__ . '/../../vendor/autoload.php';

// 初始化应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;
use think\facade\Config;

echo "==============================================\n";
echo "场景分类功能数据库迁移\n";
echo "==============================================\n\n";

// 获取数据库表前缀
$prefix = Config::get('database.connections.mysql.prefix') ?: 'ddwx_';

try {
    // ============================================================
    // 1. 创建 generation_scene_category 表
    // ============================================================
    echo "步骤 1: 创建场景分类表 ({$prefix}generation_scene_category)...\n";
    
    $tableName = $prefix . 'generation_scene_category';
    
    // 检查表是否存在
    $tableExists = Db::query("SHOW TABLES LIKE '{$tableName}'");
    
    if (empty($tableExists)) {
        $createTableSQL = "CREATE TABLE `{$tableName}` (
            `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
            `aid` int(11) NOT NULL COMMENT '账户ID',
            `bid` int(11) NOT NULL DEFAULT 0 COMMENT '商户ID，0表示平台',
            `generation_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '生成类型：1=照片 2=视频',
            `pid` int(11) NOT NULL DEFAULT 0 COMMENT '上级分类ID，0表示顶级',
            `name` varchar(100) NOT NULL COMMENT '分类名称',
            `pic` varchar(255) DEFAULT NULL COMMENT '分类图标/图片',
            `description` varchar(500) DEFAULT NULL COMMENT '分类描述',
            `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序值，越大越靠前',
            `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：0=隐藏 1=显示',
            `create_time` int(11) NOT NULL COMMENT '创建时间戳',
            `update_time` int(11) DEFAULT NULL COMMENT '更新时间戳',
            PRIMARY KEY (`id`),
            KEY `idx_aid_bid_type` (`aid`, `bid`, `generation_type`),
            KEY `idx_pid` (`pid`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='场景分类表'";
        
        Db::execute($createTableSQL);
        echo "  ✓ 表 {$tableName} 创建成功\n";
    } else {
        echo "  ⚠ 表 {$tableName} 已存在，跳过创建\n";
    }
    
    // ============================================================
    // 2. 修改 generation_scene_template 表，添加 category_id 字段
    // ============================================================
    echo "\n步骤 2: 修改场景模板表，添加 category_id 字段...\n";
    
    $templateTable = $prefix . 'generation_scene_template';
    
    // 检查表是否存在
    $templateTableExists = Db::query("SHOW TABLES LIKE '{$templateTable}'");
    
    if (!empty($templateTableExists)) {
        // 检查字段是否存在
        $columns = Db::query("SHOW COLUMNS FROM `{$templateTable}` LIKE 'category_id'");
        
        if (empty($columns)) {
            $alterSQL = "ALTER TABLE `{$templateTable}` 
                ADD COLUMN `category_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联分类ID' AFTER `category`,
                ADD INDEX `idx_category_id` (`category_id`)";
            
            Db::execute($alterSQL);
            echo "  ✓ 字段 category_id 添加成功\n";
        } else {
            echo "  ⚠ 字段 category_id 已存在，跳过添加\n";
        }
    } else {
        echo "  ⚠ 表 {$templateTable} 不存在，跳过修改\n";
    }
    
    // ============================================================
    // 3. 初始化默认分类数据（可选）
    // ============================================================
    echo "\n步骤 3: 初始化默认分类数据...\n";
    
    // 查询是否已有数据
    $existingCount = Db::table($tableName)->count();
    
    if ($existingCount == 0) {
        // 获取所有aid（平台账户）
        $admins = Db::name('admin')->where('id', '>', 0)->field('id')->select()->toArray();
        
        $defaultCategories = [
            // 照片生成默认分类
            ['generation_type' => 1, 'name' => '人像写真', 'sort' => 100],
            ['generation_type' => 1, 'name' => '风景摄影', 'sort' => 90],
            ['generation_type' => 1, 'name' => '艺术创作', 'sort' => 80],
            ['generation_type' => 1, 'name' => '商业广告', 'sort' => 70],
            // 视频生成默认分类
            ['generation_type' => 2, 'name' => '人物动态', 'sort' => 100],
            ['generation_type' => 2, 'name' => '场景动画', 'sort' => 90],
            ['generation_type' => 2, 'name' => '创意特效', 'sort' => 80],
        ];
        
        $insertCount = 0;
        foreach ($admins as $admin) {
            foreach ($defaultCategories as $category) {
                Db::table($tableName)->insert([
                    'aid' => $admin['id'],
                    'bid' => 0,
                    'generation_type' => $category['generation_type'],
                    'pid' => 0,
                    'name' => $category['name'],
                    'sort' => $category['sort'],
                    'status' => 1,
                    'create_time' => time(),
                ]);
                $insertCount++;
            }
        }
        echo "  ✓ 已插入 {$insertCount} 条默认分类数据\n";
    } else {
        echo "  ⚠ 已存在 {$existingCount} 条分类数据，跳过初始化\n";
    }
    
    echo "\n==============================================\n";
    echo "✅ 场景分类功能数据库迁移完成！\n";
    echo "==============================================\n";
    
} catch (\Exception $e) {
    echo "\n❌ 迁移失败: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
    exit(1);
}
