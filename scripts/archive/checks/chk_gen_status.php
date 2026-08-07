<?php
$pdo = new PDO('mysql:host=localhost;dbname=guobaoyungou_cn', 'guobaoyungou_cn', '5ArfhRr9xzyScrF5');

// 查没有qrcode但synth=3的portrait的generation状态分布
$ids = [252, 253, 254, 255, 256, 259];
foreach ($ids as $pid) {
    $r = $pdo->query("SELECT status, COUNT(*) as c FROM ddwx_ai_travel_photo_generation WHERE portrait_id=$pid GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    $statuses = [];
    foreach ($r as $row) {
        $statuses[] = "s{$row['status']}={$row['c']}";
    }
    echo "portrait $pid: " . implode(', ', $statuses) . "\n";
}

// 也检查：有qrcode的 250,247,200,199
$ok_ids = [250, 247, 200, 199];
echo "\n--- 有qrcode的 ---\n";
foreach ($ok_ids as $pid) {
    $r = $pdo->query("SELECT status, COUNT(*) as c FROM ddwx_ai_travel_photo_generation WHERE portrait_id=$pid GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    $statuses = [];
    foreach ($r as $row) {
        $statuses[] = "s{$row['status']}={$row['c']}";
    }
    echo "portrait $pid: " . implode(', ', $statuses) . "\n";
}
