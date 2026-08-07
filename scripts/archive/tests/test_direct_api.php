<?php
/**
 * 直接测试ApiUnifiedOrder控制器（绕过登录检查）
 */

require_once __DIR__ . '/vendor/autoload.php';

define('ROOT_PATH', __DIR__ . '/');

// 加载配置
$config = require __DIR__ . '/config.php';

// 模拟ThinkPHP环境变量
if (!defined('aid')) define('aid', 1);
if (!defined('mid')) define('mid', 1);

// 连接数据库
$mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_error) {
    die("连接失败: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

echo "直接测试选片订单查询\n";
echo "====================\n\n";

echo "当前用户:\n";
echo "  aid: " . aid . "\n";
echo "  mid: " . mid . "\n\n";

// 查询用户信息
$mid_value = mid;
$stmt = $mysqli->prepare("SELECT id, wxopenid, mpopenid, nickname FROM ddwx_member WHERE id = ?");
$stmt->bind_param("i", $mid_value);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

if ($member) {
    echo "用户信息:\n";
    echo "  ID: " . $member['id'] . "\n";
    echo "  昵称: " . $member['nickname'] . "\n";
    echo "  wxopenid: " . ($member['wxopenid'] ?: 'NULL') . "\n";
    echo "  mpopenid: " . ($member['mpopenid'] ?: 'NULL') . "\n\n";
}

// 优先匹配 wxopenid (小程序)，其次 mpopenid (公众号)
$openid = $member['wxopenid'] ?? $member['mpopenid'] ?? '';
echo "使用的 openid: " . ($openid ?: '无') . "\n\n";

// 查询选片订单
$sql = "SELECT id, order_no, uid, openid, status, total_price, create_time
        FROM ddwx_ai_travel_photo_order
        WHERE aid = ?";

$params = [aid];
$types = "i";

// 添加 uid 或 openid 条件
if ($openid) {
    $sql .= " AND (uid = ? OR openid = ?)";
    $params = [aid, mid, $openid];
    $types = "iis";
} else {
    $sql .= " AND uid = ?";
    $params[] = mid;
    $types .= "i";
}

$sql .= " ORDER BY create_time DESC LIMIT 10";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, $params[0], $params[1], $params[2]);
$stmt->execute();
$result = $stmt->get_result();

echo "选片订单查询结果:\n";
$count = 0;
$orders = [];
while ($row = $result->fetch_assoc()) {
    $count++;
    $orders[] = $row;
    echo "  {$count}. ID: {$row['id']}, 订单号: {$row['order_no']}, 金额: ¥{$row['total_price']}, 状态: {$row['status']}\n";
}

if ($count === 0) {
    echo "  没有找到选片订单\n";
}

echo "\n总计: {$count} 条选片订单\n\n";

// 查询商城订单
echo "商城订单查询结果:\n";
$sql = "SELECT id, ordernum, totalprice, status, createtime
        FROM ddwx_shop_order
        WHERE aid = ? AND mid = ?
        ORDER BY createtime DESC LIMIT 10";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", aid, mid);
$stmt->execute();
$result = $stmt->get_result();

$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "  {$count}. ID: {$row['id']}, 订单号: {$row['ordernum']}, 金额: ¥{$row['totalprice']}, 状态: {$row['status']}\n";
}

if ($count === 0) {
    echo "  没有找到商城订单\n";
}

echo "\n总计: {$count} 条商城订单\n";

$stmt->close();
$mysqli->close();
