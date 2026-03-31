<?php
/**
 * 测试统一订单API查询逻辑
 */

require_once __DIR__ . '/vendor/autoload.php';

// 模拟ThinkPHP环境
define('ROOT_PATH', __DIR__ . '/');

// 加载配置
$config = require __DIR__ . '/config.php';

// 连接数据库
$mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_error) {
    die("连接失败: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

// 测试用户ID
$mid = 1;
$aid = 1;

echo "测试查询选片订单\n";
echo "==================\n\n";

// 查询用户信息
$stmt = $mysqli->prepare("SELECT id, wxopenid, mpopenid FROM ddwx_member WHERE id = ?");
$stmt->bind_param("i", $mid);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

if ($member) {
    echo "用户信息:\n";
    echo "  ID: " . $member['id'] . "\n";
    echo "  wxopenid: " . ($member['wxopenid'] ?: 'NULL') . "\n";
    echo "  mpopenid: " . ($member['mpopenid'] ?: 'NULL') . "\n\n";
}

// 优先匹配 wxopenid (小程序)，其次 mpopenid (公众号)
$openid = $member['wxopenid'] ?? $member['mpopenid'] ?? '';
echo "使用的 openid: " . ($openid ?: '无') . "\n\n";

// 构建查询条件
$sql = "SELECT id, order_no, uid, openid, status, create_time 
        FROM ddwx_ai_travel_photo_order 
        WHERE aid = ?";

$params = [$aid];
$types = "i";

// 添加 uid 或 openid 条件
if ($openid) {
    $sql .= " AND (uid = ? OR openid = ?)";
    $params[] = $mid;
    $params[] = $openid;
    $types .= "is";
} else {
    $sql .= " AND uid = ?";
    $params[] = $mid;
    $types .= "i";
}

$sql .= " ORDER BY create_time DESC LIMIT 10";

echo "SQL查询:\n";
echo $sql . "\n\n";

echo "参数: " . implode(', ', $params) . "\n\n";

// 执行查询
$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

echo "查询结果:\n";
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "  {$count}. ID: {$row['id']}, 订单号: {$row['order_no']}, UID: {$row['uid']}, OpenID: " . substr($row['openid'], 0, 20) . "..., 状态: {$row['status']}\n";
}

if ($count === 0) {
    echo "  没有找到订单\n";
}

echo "\n总计: {$count} 条订单\n";

$stmt->close();
$mysqli->close();
