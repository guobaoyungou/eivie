<?php
/**
 * 测试session和member信息
 */

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';

$mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);
if ($mysqli->connect_error) {
    die("连接失败: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8mb4");

$session_id = 'f8fc9caf821fa8d480379d9506a80c5e';

echo "Session信息测试\n";
echo "===============\n\n";

// 查询session
$result = $mysqli->query("SELECT * FROM ddwx_session WHERE session_id = '{$session_id}'");
$session = $result->fetch_assoc();

if ($session) {
    echo "Session:\n";
    echo "  ID: {$session['session_id']}\n";
    echo "  AID: {$session['aid']}\n";
    echo "  MID: " . ($session['mid'] ?: 'NULL') . "\n";
    echo "  Platform: {$session['platform']}\n\n";

    if ($session['mid']) {
        // 查询member
        $result = $mysqli->query("SELECT * FROM ddwx_member WHERE id = {$session['mid']}");
        $member = $result->fetch_assoc();

        if ($member) {
            echo "Member信息:\n";
            echo "  ID: {$member['id']}\n";
            echo "  Nickname: {$member['nickname']}\n";
            echo "  wxopenid: " . ($member['wxopenid'] ?: 'NULL') . "\n";
            echo "  mpopenid: " . ($member['mpopenid'] ?: 'NULL') . "\n";
            echo "  checkst: {$member['checkst']}\n";
            echo "  isfreeze: {$member['isfreeze']}\n\n";
        }
    }
}

$mysqli->close();
