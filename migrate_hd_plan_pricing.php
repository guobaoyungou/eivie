<?php
/**
 * 套餐表字段扩展迁移脚本
 * 添加 code, period, is_recommended 字段
 * 插入定制版套餐
 */
define('ROOT_PATH', '/home/www/ai.eivie.cn/');
$c = include(ROOT_PATH . 'config.php');
$pdo = new PDO(
    'mysql:host=' . $c['hostname'] . ';dbname=' . $c['database'] . ';port=' . $c['hostport'],
    $c['username'],
    $c['password']
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('SET NAMES utf8mb4');
$prefix = $c['prefix'];
$table = $prefix . 'hd_plan';

// 1. Check existing columns
$cols = [];
$st = $pdo->query("DESCRIBE {$table}");
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = $r['Field'];
}

if (!in_array('code', $cols)) {
    $pdo->exec("ALTER TABLE {$table} ADD COLUMN `code` VARCHAR(32) NOT NULL DEFAULT '' AFTER `name`");
    echo "Added code column\n";
} else {
    echo "code column already exists\n";
}

if (!in_array('period', $cols)) {
    $pdo->exec("ALTER TABLE {$table} ADD COLUMN `period` VARCHAR(16) NOT NULL DEFAULT '' AFTER `price`");
    echo "Added period column\n";
} else {
    echo "period column already exists\n";
}

if (!in_array('is_recommended', $cols)) {
    $pdo->exec("ALTER TABLE {$table} ADD COLUMN `is_recommended` TINYINT(1) NOT NULL DEFAULT 0 AFTER `features`");
    echo "Added is_recommended column\n";
} else {
    echo "is_recommended column already exists\n";
}

// 2. Update existing plans
$pdo->exec("UPDATE {$table} SET `code`='trial', `period`='7天', `is_recommended`=0 WHERE id=1");
$pdo->exec("UPDATE {$table} SET `code`='basic', `period`='年', `is_recommended`=1 WHERE id=2");
$pdo->exec("UPDATE {$table} SET `code`='pro', `period`='年', `is_recommended`=0 WHERE id=3");
$pdo->exec("UPDATE {$table} SET `code`='enterprise', `period`='年', `is_recommended`=0 WHERE id=4");
echo "Updated existing plans with code/period/is_recommended\n";

// 3. Insert custom plan if not exists
$st2 = $pdo->query("SELECT id FROM {$table} WHERE `code`='custom'");
if (!$st2->fetch()) {
    $now = time();
    $pdo->exec("INSERT INTO {$table} (`aid`, `name`, `code`, `price`, `period`, `duration_days`, `max_stores`, `max_activities`, `max_participants`, `features`, `is_recommended`, `status`, `sort`, `createtime`) VALUES (0, '定制版', 'custom', 0.00, '定制', 0, 0, 0, 0, 'qdq,threedimensionalsign,wall,danmu,vote,lottery,choujiang,ydj,shake,game,redpacket,importlottery,kaimu,bimu,xiangce,xyh,xysjh', 0, 1, 60, {$now})");
    echo "Inserted custom plan\n";
} else {
    echo "custom plan already exists\n";
}

// 4. Update sort order
$pdo->exec("UPDATE {$table} SET `sort`=100 WHERE `code`='trial'");
$pdo->exec("UPDATE {$table} SET `sort`=90  WHERE `code`='basic'");
$pdo->exec("UPDATE {$table} SET `sort`=80  WHERE `code`='pro'");
$pdo->exec("UPDATE {$table} SET `sort`=70  WHERE `code`='enterprise'");
$pdo->exec("UPDATE {$table} SET `sort`=60  WHERE `code`='custom'");
echo "Updated sort order\n";

// 5. Verify
echo "\n--- Final plan data ---\n";
$st3 = $pdo->query("SELECT id, name, code, price, period, duration_days, max_stores, max_activities, max_participants, is_recommended, sort FROM {$table} ORDER BY sort DESC");
while ($r = $st3->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}
echo "\nDone!\n";
