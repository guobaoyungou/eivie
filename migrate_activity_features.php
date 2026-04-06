<?php
/**
 * 大屏互动 - 为现有活动补充缺失的功能配置
 *
 * 问题：早期创建的活动只初始化了部分功能（如 qdq/wall/lottery），
 *       缺少如 3D签到 等其他功能。此脚本为所有现有活动补充缺失的功能。
 *
 * 执行方式: php migrate_activity_features.php
 */

$config = include(__DIR__ . '/config.php');

$conn = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database'], (int)$config['hostport']);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');

echo "========================================\n";
echo "大屏互动 - 补充活动缺失功能配置\n";
echo "========================================\n\n";

// 全部功能列表
$allFeatures = [
    'qdq'                  => '签到墙',
    'threedimensionalsign' => '3D签到',
    'wall'                 => '微信上墙',
    'danmu'                => '弹幕',
    'vote'                 => '投票',
    'lottery'              => '大屏抽奖',
    'choujiang'            => '手机抽奖',
    'ydj'                  => '摇大奖',
    'shake'                => '摇一摇竞技',
    'game'                 => '互动游戏',
    'redpacket'            => '红包雨',
    'importlottery'        => '导入抽奖',
    'kaimu'                => '开幕墙',
    'bimu'                 => '闭幕墙',
    'xiangce'              => '相册',
    'xyh'                  => '幸运号码',
    'xysjh'                => '幸运手机号',
    'lvpai'                => '旅拍大屏',
    'scan_lottery'         => '扫码抽奖',
];

try {
    // 获取所有活动
    $result = $conn->query("SELECT id, aid, bid, title FROM ddwx_hd_activity ORDER BY id");
    $activities = $result->fetch_all(MYSQLI_ASSOC);
    $totalActivities = count($activities);

    echo "[1/2] 找到 {$totalActivities} 个活动\n\n";

    $totalAdded = 0;
    $totalSkipped = 0;

    foreach ($activities as $activity) {
        $activityId = $activity['id'];
        $aid = $activity['aid'];
        $bid = $activity['bid'];
        $title = $activity['title'];

        // 获取该活动已有的功能
        $stmt = $conn->prepare("SELECT feature_code FROM ddwx_hd_activity_feature WHERE activity_id = ?");
        $stmt->bind_param("i", $activityId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existingFeatures = [];
        while ($row = $result->fetch_assoc()) {
            $existingFeatures[] = $row['feature_code'];
        }
        $stmt->close();

        // 获取当前最大 sort 值
        $stmt = $conn->prepare("SELECT MAX(sort) as max_sort FROM ddwx_hd_activity_feature WHERE activity_id = ?");
        $stmt->bind_param("i", $activityId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $maxSort = (int)($row['max_sort'] ?? -1);
        $stmt->close();

        // 找出缺失的功能
        $missingFeatures = array_diff(array_keys($allFeatures), $existingFeatures);

        if (empty($missingFeatures)) {
            echo "[活动 #{$activityId}] {$title} - ✅ 已是完整配置，跳过\n";
            $totalSkipped++;
            continue;
        }

        // 插入缺失的功能
        $stmt = $conn->prepare("INSERT INTO ddwx_hd_activity_feature (aid, bid, activity_id, feature_code, enabled, config, sort) VALUES (?, ?, ?, ?, 1, '{}', ?)");
        $sort = $maxSort + 1;
        $newFeatures = [];

        foreach ($missingFeatures as $code) {
            $stmt->bind_param("iiisi", $aid, $bid, $activityId, $code, $sort);
            $stmt->execute();
            $newFeatures[] = $allFeatures[$code] . "({$code})";
            $sort++;
            $totalAdded++;
        }
        $stmt->close();

        echo "[活动 #{$activityId}] {$title}\n";
        echo "    ➕ 新增功能: " . implode(', ', $newFeatures) . "\n";
    }

    echo "\n========================================\n";
    echo "✅ 迁移完成！\n";
    echo "   总活动数: {$totalActivities}\n";
    echo "   已完整: {$totalSkipped}\n";
    echo "   已补充: " . ($totalActivities - $totalSkipped) . "\n";
    echo "   新增记录: {$totalAdded}\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "\n❌ 迁移失败: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
