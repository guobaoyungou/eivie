<?php
/**
 * 套餐管理阶梯档位规则重构 - 数据库迁移脚本
 * 
 * 变更：
 * 1. 新增字段 min_num, max_num, min_video_num, max_video_num
 * 2. 存量数据转换：num=N → min_num=N, max_num=N+1; video_num=V → min_video_num=V, max_video_num=V+1
 * 
 * 执行方式：php migrate_package_tier.php
 */

// 定义必要常量
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}
if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__ . '/app');
}
if (!defined('MN')) {
    define('MN', 'index');
}

// 引入ThinkPHP框架
require __DIR__ . '/vendor/autoload.php';

// 初始化应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;

echo "========== 套餐阶梯档位迁移开始 ==========\n\n";

// 获取表前缀
$prefix = config('database.connections.mysql.prefix') ?: 'ddwx_';
$tableName = $prefix . 'ai_travel_photo_package';

// Step 1: 检查字段是否已存在
echo "Step 1: 检查字段是否已存在...\n";
$columns = Db::query("SHOW COLUMNS FROM `{$tableName}`");
$columnNames = array_column($columns, 'Field');

$newFields = ['min_num', 'max_num', 'min_video_num', 'max_video_num'];
$fieldsToAdd = [];
foreach ($newFields as $field) {
    if (!in_array($field, $columnNames)) {
        $fieldsToAdd[] = $field;
        echo "  - 字段 {$field} 不存在，需要添加\n";
    } else {
        echo "  - 字段 {$field} 已存在，跳过\n";
    }
}

// Step 2: 添加新字段
if (!empty($fieldsToAdd)) {
    echo "\nStep 2: 添加新字段...\n";
    
    $alterSql = "ALTER TABLE `{$tableName}` ";
    $alterParts = [];
    
    if (in_array('min_num', $fieldsToAdd)) {
        $alterParts[] = "ADD COLUMN `min_num` int(11) NOT NULL DEFAULT 0 COMMENT '图片档位下限（含），选片数≥此值' AFTER `video_num`";
    }
    if (in_array('max_num', $fieldsToAdd)) {
        $alterParts[] = "ADD COLUMN `max_num` int(11) NOT NULL DEFAULT 0 COMMENT '图片档位上限（不含），选片数<此值；0=不限上限' AFTER `min_num`";
    }
    if (in_array('min_video_num', $fieldsToAdd)) {
        $alterParts[] = "ADD COLUMN `min_video_num` int(11) NOT NULL DEFAULT 0 COMMENT '视频档位下限（含）' AFTER `max_num`";
    }
    if (in_array('max_video_num', $fieldsToAdd)) {
        $alterParts[] = "ADD COLUMN `max_video_num` int(11) NOT NULL DEFAULT 0 COMMENT '视频档位上限（不含）；0=不限上限' AFTER `min_video_num`";
    }
    
    if (!empty($alterParts)) {
        $alterSql .= implode(', ', $alterParts);
        try {
            Db::execute($alterSql);
            echo "  ✓ 字段添加成功\n";
        } catch (\Exception $e) {
            echo "  ✗ 字段添加失败：" . $e->getMessage() . "\n";
            exit(1);
        }
    }
} else {
    echo "\nStep 2: 所有字段已存在，跳过添加\n";
}

// Step 3: 迁移存量数据
echo "\nStep 3: 迁移存量数据...\n";

// 查询所有 min_num=0 且 num>0 的记录（未迁移的存量数据）
$records = Db::name('ai_travel_photo_package')
    ->where('min_num', 0)
    ->where('num', '>', 0)
    ->select()
    ->toArray();

$migratedCount = 0;
foreach ($records as $record) {
    $num = (int)$record['num'];
    $videoNum = (int)($record['video_num'] ?? 0);
    
    $updateData = [
        'min_num' => $num,
        'max_num' => $num + 1,
        'min_video_num' => $videoNum,
        'max_video_num' => $videoNum > 0 ? $videoNum + 1 : 0,
    ];
    
    Db::name('ai_travel_photo_package')
        ->where('id', $record['id'])
        ->update($updateData);
    
    $migratedCount++;
    echo "  - 迁移记录 ID={$record['id']}: num={$num} → min_num={$updateData['min_num']}, max_num={$updateData['max_num']}, "
        . "video_num={$videoNum} → min_video_num={$updateData['min_video_num']}, max_video_num={$updateData['max_video_num']}\n";
}

if ($migratedCount === 0) {
    echo "  无需迁移的存量记录\n";
} else {
    echo "  ✓ 共迁移 {$migratedCount} 条记录\n";
}

// Step 4: 验证
echo "\nStep 4: 验证迁移结果...\n";
$total = Db::name('ai_travel_photo_package')->count();
$migrated = Db::name('ai_travel_photo_package')->where('min_num', '>', 0)->count();
$pending = Db::name('ai_travel_photo_package')->where('min_num', 0)->where('num', '>', 0)->count();

echo "  总记录数: {$total}\n";
echo "  已有阶梯配置: {$migrated}\n";
echo "  待迁移: {$pending}\n";

if ($pending > 0) {
    echo "  ⚠️ 仍有 {$pending} 条记录未迁移\n";
} else {
    echo "  ✓ 所有记录已完成迁移\n";
}

echo "\n========== 迁移完成 ==========\n";
