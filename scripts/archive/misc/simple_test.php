<?php
/**
 * 简单测试：验证用户1的订单数据
 */

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';

$mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_error) {
    die("连接失败: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

$mid = 1;
$aid = 1;

echo "用户1的订单统计\n";
echo "================\n\n";

// 查询用户信息
$result = $mysqli->query("SELECT id, wxopenid, mpopenid, nickname FROM ddwx_member WHERE id = {$mid}");
$member = $result->fetch_assoc();

echo "用户: {$member['nickname']}\n";
echo "wxopenid: " . ($member['wxopenid'] ?: 'NULL') . "\n";
echo "mpopenid: " . ($member['mpopenid'] ?: 'NULL') . "\n\n";

$openid = $member['mpopenid'];

// 统计各类型订单
$types = [
    'shop' => 'ddwx_shop_order',
    'collage' => 'ddwx_collage_order',
    'ai_pick' => 'ddwx_ai_travel_photo_order',
];

foreach ($types as $type => $table) {
    if ($type == 'ai_pick') {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE aid = {$aid} AND (uid = {$mid} OR openid = '{$openid}')";
    } else {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE aid = {$aid} AND mid = {$mid}";
    }
    $result = $mysqli->query($sql);
    $count = $result->fetch_assoc()['count'];
    echo "{$type}: {$count} 条\n";
}

// 显示选片订单详情
echo "\n选片订单详情:\n";
$sql = "SELECT id, order_no, uid, openid, status, total_price, create_time
        FROM ddwx_ai_travel_photo_order
        WHERE aid = {$aid} AND (uid = {$mid} OR openid = '{$openid}')
        ORDER BY create_time DESC LIMIT 5";

$result = $mysqli->query($sql);
while ($row = $result->fetch_assoc()) {
    echo "  ID: {$row['id']}, 订单号: {$row['order_no']}, 金额: ¥{$row['total_price']}, 状态: {$row['status']}\n";
}

$mysqli->close();
