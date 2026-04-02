<?php
/**
 * 迁移脚本：创建 weixin_music 表并插入 13 条预置功能模块数据
 * 用于背景音乐按模块管理功能
 * 
 * 执行方式：php migrate_weixin_music.php
 */

$config = include(__DIR__ . '/config.php');

// 使用 huodong 数据库连接（与默认库相同）
$hdConfig = isset($config['huodong']) ? $config['huodong'] : [
    'hostname' => $config['hostname'],
    'username' => $config['username'],
    'password' => $config['password'],
    'database' => $config['database'],
    'hostport' => $config['hostport'],
];

$conn = new mysqli($hdConfig['hostname'], $hdConfig['username'], $hdConfig['password'], $hdConfig['database'], (int)$hdConfig['hostport']);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8');

echo "=== weixin_music 表迁移脚本 ===\n";

// 检查表是否已存在
$result = $conn->query("SHOW TABLES LIKE 'weixin_music'");
if ($result->num_rows > 0) {
    echo "[跳过] weixin_music 表已存在\n";
    $countResult = $conn->query("SELECT COUNT(*) as cnt FROM weixin_music");
    $row = $countResult->fetch_assoc();
    echo "[信息] 当前表中有 {$row['cnt']} 条记录\n";
} else {
    // 创建表
    $createSql = "CREATE TABLE `weixin_music` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bgmusic` int(11) DEFAULT NULL COMMENT '背景音乐附件ID(关联weixin_attachments.id)',
  `bgmusicstatus` tinyint(1) DEFAULT 2 COMMENT '状态：1=开，2=关',
  `name` varchar(32) DEFAULT NULL COMMENT '模块显示名称',
  `plugname` varchar(32) DEFAULT NULL COMMENT '功能模块标识',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='背景音乐配置（按功能模块）'";

    if (!$conn->query($createSql)) {
        die("[错误] 创建表失败: " . $conn->error . "\n");
    }
    echo "[成功] weixin_music 表创建完成\n";

    // 插入 13 条预置数据
    $insertSql = "INSERT INTO `weixin_music` (`id`, `bgmusic`, `bgmusicstatus`, `name`, `plugname`) VALUES
(1, NULL, 2, '签到墙背景乐', 'qdq'),
(2, NULL, 2, '对对碰背景乐', 'ddp'),
(3, NULL, 2, '投票背景乐', 'vote'),
(4, NULL, 2, '幸运手机号背景乐', 'xysjh'),
(5, NULL, 2, '幸运号码背景乐', 'xyh'),
(6, NULL, 2, '3D签到背景乐', 'threedimensionalsign'),
(7, NULL, 2, '微信上墙背景乐', 'wall'),
(8, NULL, 2, '相册背景乐', 'xiangce'),
(9, NULL, 2, '开幕墙背景乐', 'kaimu'),
(10, NULL, 2, '闭幕墙背景乐', 'bimu'),
(11, NULL, 2, '红包雨背景乐', 'redpacket'),
(12, NULL, 2, '摇大奖树背景乐', 'ydj'),
(13, NULL, 2, '导入抽奖', 'importlottery')";

    if (!$conn->query($insertSql)) {
        die("[错误] 插入数据失败: " . $conn->error . "\n");
    }
    echo "[成功] 已插入 13 条预置模块数据\n";
}

// 验证
$result = $conn->query("SELECT * FROM weixin_music ORDER BY id");
echo "\n=== 验证结果 ===\n";
echo sprintf("%-4s %-26s %-26s %-6s %-8s", 'ID', '名称', '模块标识', '状态', '音乐ID') . "\n";
echo str_repeat('-', 72) . "\n";
while ($row = $result->fetch_assoc()) {
    echo sprintf("%-4s %-26s %-26s %-6s %-8s",
        $row['id'],
        $row['name'],
        $row['plugname'],
        $row['bgmusicstatus'] == 1 ? '开' : '关',
        $row['bgmusic'] ?: '默认'
    ) . "\n";
}

echo "\n[完成] 迁移成功\n";
$conn->close();
