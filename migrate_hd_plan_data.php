<?php
/**
 * 套餐数据迁移脚本（幂等 — 可重复执行）
 * 
 * 功能：
 *   1. 确保 hd_plan 表包含 code、period、is_recommended 字段
 *   2. 写入/更新 5 档标准套餐数据
 * 
 * 用法：php migrate_hd_plan_data.php
 */

define('ROOT_PATH', __DIR__ . '/');
$c = include(ROOT_PATH . 'config.php');
$pdo = new PDO(
    'mysql:host=' . $c['hostname'] . ';dbname=' . $c['database'] . ';port=' . $c['hostport'],
    $c['username'],
    $c['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('SET NAMES utf8mb4');
$prefix = $c['prefix'];
$table  = $prefix . 'hd_plan';

echo "=== 套餐数据迁移脚本 ===\n\n";

// ============================================================
// 第 1 步：确保表结构包含必需字段
// ============================================================
echo "[1] 检查表结构...\n";

$cols = [];
$st = $pdo->query("DESCRIBE {$table}");
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = $r['Field'];
}

$schemaChanges = 0;
if (!in_array('code', $cols)) {
    $pdo->exec("ALTER TABLE {$table} ADD COLUMN `code` VARCHAR(32) NOT NULL DEFAULT '' AFTER `name`");
    echo "    + 新增字段 code\n";
    $schemaChanges++;
}
if (!in_array('period', $cols)) {
    $pdo->exec("ALTER TABLE {$table} ADD COLUMN `period` VARCHAR(16) NOT NULL DEFAULT '' AFTER `price`");
    echo "    + 新增字段 period\n";
    $schemaChanges++;
}
if (!in_array('is_recommended', $cols)) {
    $pdo->exec("ALTER TABLE {$table} ADD COLUMN `is_recommended` TINYINT(1) NOT NULL DEFAULT 0 AFTER `features`");
    echo "    + 新增字段 is_recommended\n";
    $schemaChanges++;
}
echo $schemaChanges > 0 ? "    表结构已更新\n" : "    表结构已是最新\n";

// ============================================================
// 第 2 步：定义 5 档标准套餐
// ============================================================
$plans = [
    [
        'code'             => 'trial',
        'name'             => '试用版',
        'price'            => '0.00',
        'period'           => '7天',
        'duration_days'    => 7,
        'max_stores'       => 1,
        'max_activities'   => 1,
        'max_participants' => 50,
        'features'         => 'qdq,wall,lottery,vote',
        'is_recommended'   => 0,
        'sort'             => 100,
    ],
    [
        'code'             => 'basic',
        'name'             => '基础版',
        'price'            => '299.00',
        'period'           => '年',
        'duration_days'    => 365,
        'max_stores'       => 3,
        'max_activities'   => 5,
        'max_participants' => 200,
        'features'         => 'qdq,threedimensionalsign,wall,danmu,vote,lottery,choujiang',
        'is_recommended'   => 1,
        'sort'             => 90,
    ],
    [
        'code'             => 'pro',
        'name'             => '专业版',
        'price'            => '599.00',
        'period'           => '年',
        'duration_days'    => 365,
        'max_stores'       => 10,
        'max_activities'   => 20,
        'max_participants' => 500,
        'features'         => 'qdq,threedimensionalsign,wall,danmu,vote,lottery,choujiang,ydj,shake,game,redpacket,importlottery,kaimu,bimu,xiangce,xyh,xysjh',
        'is_recommended'   => 0,
        'sort'             => 80,
    ],
    [
        'code'             => 'enterprise',
        'name'             => '企业版',
        'price'            => '1299.00',
        'period'           => '年',
        'duration_days'    => 365,
        'max_stores'       => 50,
        'max_activities'   => 100,
        'max_participants' => 2000,
        'features'         => 'qdq,threedimensionalsign,wall,danmu,vote,lottery,choujiang,ydj,shake,game,redpacket,importlottery,kaimu,bimu,xiangce,xyh,xysjh',
        'is_recommended'   => 0,
        'sort'             => 70,
    ],
    [
        'code'             => 'custom',
        'name'             => '定制版',
        'price'            => '0.00',
        'period'           => '定制',
        'duration_days'    => 0,
        'max_stores'       => 0,
        'max_activities'   => 0,
        'max_participants' => 0,
        'features'         => 'qdq,threedimensionalsign,wall,danmu,vote,lottery,choujiang,ydj,shake,game,redpacket,importlottery,kaimu,bimu,xiangce,xyh,xysjh',
        'is_recommended'   => 0,
        'sort'             => 60,
    ],
];

// ============================================================
// 第 3 步：写入/更新套餐数据（UPSERT 逻辑）
// ============================================================
echo "\n[2] 写入套餐数据...\n";

$inserted = 0;
$updated  = 0;
$now = time();

foreach ($plans as $plan) {
    // 按 code 查找是否已存在
    $st2 = $pdo->prepare("SELECT id FROM {$table} WHERE `code` = :code LIMIT 1");
    $st2->execute([':code' => $plan['code']]);
    $existing = $st2->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // 更新已有记录
        $sql = "UPDATE {$table} SET 
            `name` = :name, `price` = :price, `period` = :period, 
            `duration_days` = :duration_days, `max_stores` = :max_stores, 
            `max_activities` = :max_activities, `max_participants` = :max_participants, 
            `features` = :features, `is_recommended` = :is_recommended, 
            `sort` = :sort, `status` = 1 
            WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'             => $plan['name'],
            ':price'            => $plan['price'],
            ':period'           => $plan['period'],
            ':duration_days'    => $plan['duration_days'],
            ':max_stores'       => $plan['max_stores'],
            ':max_activities'   => $plan['max_activities'],
            ':max_participants' => $plan['max_participants'],
            ':features'         => $plan['features'],
            ':is_recommended'   => $plan['is_recommended'],
            ':sort'             => $plan['sort'],
            ':id'               => $existing['id'],
        ]);
        echo "    ↻ 更新: {$plan['name']} (code={$plan['code']}, id={$existing['id']})\n";
        $updated++;
    } else {
        // 插入新记录
        $sql = "INSERT INTO {$table} 
            (`aid`, `name`, `code`, `price`, `period`, `duration_days`, `max_stores`, 
             `max_activities`, `max_participants`, `features`, `is_recommended`, `status`, `sort`, `createtime`) 
            VALUES (0, :name, :code, :price, :period, :duration_days, :max_stores, 
                    :max_activities, :max_participants, :features, :is_recommended, 1, :sort, :createtime)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'             => $plan['name'],
            ':code'             => $plan['code'],
            ':price'            => $plan['price'],
            ':period'           => $plan['period'],
            ':duration_days'    => $plan['duration_days'],
            ':max_stores'       => $plan['max_stores'],
            ':max_activities'   => $plan['max_activities'],
            ':max_participants' => $plan['max_participants'],
            ':features'         => $plan['features'],
            ':is_recommended'   => $plan['is_recommended'],
            ':sort'             => $plan['sort'],
            ':createtime'       => $now,
        ]);
        $newId = $pdo->lastInsertId();
        echo "    + 新增: {$plan['name']} (code={$plan['code']}, id={$newId})\n";
        $inserted++;
    }
}

echo "\n    结果: 新增 {$inserted} 条, 更新 {$updated} 条\n";

// ============================================================
// 第 4 步：验证最终数据
// ============================================================
echo "\n[3] 验证最终数据...\n";
$st3 = $pdo->query("SELECT id, name, code, price, period, duration_days, max_stores, max_activities, max_participants, is_recommended, sort, status FROM {$table} WHERE status=1 ORDER BY sort DESC");
$count = 0;
echo str_pad('ID', 4) . str_pad('名称', 12) . str_pad('Code', 14) . str_pad('价格', 12) . str_pad('周期', 8) . str_pad('门店', 6) . str_pad('活动', 6) . str_pad('人数', 8) . str_pad('推荐', 6) . "\n";
echo str_repeat('-', 76) . "\n";
while ($r = $st3->fetch(PDO::FETCH_ASSOC)) {
    echo str_pad($r['id'], 4) 
        . str_pad($r['name'], 12) 
        . str_pad($r['code'], 14) 
        . str_pad('¥' . $r['price'], 12) 
        . str_pad($r['period'], 8) 
        . str_pad($r['max_stores'], 6) 
        . str_pad($r['max_activities'], 6) 
        . str_pad($r['max_participants'], 8) 
        . str_pad($r['is_recommended'] ? '✓' : '-', 6) 
        . "\n";
    $count++;
}

echo "\n共 {$count} 条有效套餐记录\n";
echo "\n=== 迁移完成 ===\n";
