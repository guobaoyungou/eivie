<?php
/**
 * 一次性数据修复脚本：修复卡在"处理中"的生成记录
 * 
 * 修复规则：
 * 1. generation_record: status=1 且 start_time < 当前时间-3600 → status=3(失败)
 * 2. generation_order: task_status=1 且关联的 generation_record.status=3 → 同步 task_status=3
 * 3. ai_travel_photo_generation: status=1 且 start_time < 当前时间-3600 → status=3(失败)
 * 4. ai_travel_photo_portrait: 所有generation已完成但synthesis_status仍为2 → 重新计算状态
 * 
 * 使用方式：php think run fix_stuck_generations.php
 * 或直接: php fix_stuck_generations.php
 */

// 加载ThinkPHP框架
define('ROOT_PATH', __DIR__ . '/');

// 检测是否能直接使用框架
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

if (file_exists(__DIR__ . '/think')) {
    // 通过ThinkPHP Console执行
    echo "请使用以下命令执行此脚本:\n";
    echo "cd " . __DIR__ . " && php think clean_stuck_generations\n";
    echo "\n或者在MySQL中直接执行以下SQL:\n\n";
}

$now = time();
$cutoff = $now - 3600; // 1小时前
$cutoffDate = date('Y-m-d H:i:s', $cutoff);
$nowDate = date('Y-m-d H:i:s', $now);

echo "====================================================\n";
echo "一次性数据修复SQL（修复卡住的生成记录）\n";
echo "生成时间: {$nowDate}\n";
echo "超时阈值: 1小时（start_time < {$cutoffDate}）\n";
echo "====================================================\n\n";

// SQL 1: 修复 generation_record
$sql1 = <<<SQL
-- 步骤1: 修复 generation_record 表中卡住的记录
UPDATE generation_record
SET status = 3,
    error_code = 'TIMEOUT',
    error_msg = '任务超时自动修复',
    finish_time = {$now}
WHERE status = 1
  AND start_time > 0
  AND start_time < {$cutoff};
SQL;

echo $sql1 . "\n\n";

// SQL 2: 同步修复 generation_order
$sql2 = <<<SQL
-- 步骤2: 同步更新 generation_order 的 task_status
-- 查找 task_status=1（处理中）但关联的 generation_record 已变为失败(3)的订单
UPDATE generation_order o
INNER JOIN generation_record r ON o.record_id = r.id
SET o.task_status = r.status,
    o.updatetime = {$now}
WHERE o.task_status = 1
  AND r.status IN (2, 3);
SQL;

echo $sql2 . "\n\n";

// SQL 3: 修复 ai_travel_photo_generation
$sql3 = <<<SQL
-- 步骤3: 修复 ai_travel_photo_generation 表中卡住的记录
UPDATE ai_travel_photo_generation
SET status = 3,
    error_msg = '任务超时自动修复',
    finish_time = {$now},
    update_time = {$now}
WHERE status = 1
  AND start_time > 0
  AND start_time < {$cutoff};
SQL;

echo $sql3 . "\n\n";

// SQL 4: 修复 ai_travel_photo_portrait synthesis_status
$sql4 = <<<SQL
-- 步骤4: 修复 ai_travel_photo_portrait 的 synthesis_status
-- 找到所有generation已完成但portrait仍为处理中(2)的记录
-- 4a: 有成功generation的portrait → synthesis_status=3（成功）
UPDATE ai_travel_photo_portrait p
SET p.synthesis_status = 3,
    p.synthesis_error = '',
    p.update_time = {$now}
WHERE p.synthesis_status = 2
  AND NOT EXISTS (
    SELECT 1 FROM ai_travel_photo_generation g
    WHERE g.portrait_id = p.id AND g.status IN (0, 1)
  )
  AND EXISTS (
    SELECT 1 FROM ai_travel_photo_generation g
    WHERE g.portrait_id = p.id AND g.status = 2
  );

-- 4b: 全部generation失败的portrait → synthesis_status=4（失败）
UPDATE ai_travel_photo_portrait p
SET p.synthesis_status = 4,
    p.synthesis_error = '所有合成任务均超时失败',
    p.update_time = {$now}
WHERE p.synthesis_status = 2
  AND NOT EXISTS (
    SELECT 1 FROM ai_travel_photo_generation g
    WHERE g.portrait_id = p.id AND g.status IN (0, 1)
  )
  AND NOT EXISTS (
    SELECT 1 FROM ai_travel_photo_generation g
    WHERE g.portrait_id = p.id AND g.status = 2
  );
SQL;

echo $sql4 . "\n\n";

// 查询验证SQL
$verifySql = <<<SQL
-- 验证：检查是否还有卡住的记录
SELECT '卡住的generation_record' AS type, COUNT(*) AS cnt
FROM generation_record WHERE status = 1 AND start_time > 0 AND start_time < {$cutoff}
UNION ALL
SELECT '卡住的ai_travel_photo_generation', COUNT(*)
FROM ai_travel_photo_generation WHERE status = 1 AND start_time > 0 AND start_time < {$cutoff}
UNION ALL
SELECT '需同步的generation_order', COUNT(*)
FROM generation_order o INNER JOIN generation_record r ON o.record_id = r.id
WHERE o.task_status = 1 AND r.status IN (2, 3)
UNION ALL
SELECT '卡住的portrait(synthesis_status=2)', COUNT(*)
FROM ai_travel_photo_portrait p
WHERE p.synthesis_status = 2
  AND NOT EXISTS (SELECT 1 FROM ai_travel_photo_generation g WHERE g.portrait_id = p.id AND g.status IN (0, 1));
SQL;

echo "====================================================\n";
echo "验证SQL（执行修复后运行以确认结果）\n";
echo "====================================================\n\n";
echo $verifySql . "\n";
