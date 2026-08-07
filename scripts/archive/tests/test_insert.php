<?php
// 测试 saveToQrcode 逻辑：直接尝试插入 qrcode 记录
// 纯PDO测试，不需要框架

// 手动测试插入 qrcode 记录（模拟 saveToQrcode 的逻辑）
$pdo = new PDO('mysql:host=localhost;dbname=guobaoyungou_cn', 'guobaoyungou_cn', '5ArfhRr9xzyScrF5');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$portraitId = 258;
$bid = 2;   // 从之前诊断可知 mdid=2 对应 bid=?
$aid = 0;

// 先查 portrait 的 aid 和 bid
$r = $pdo->query("SELECT aid, bid, mdid FROM ddwx_ai_travel_photo_portrait WHERE id=$portraitId")->fetch(PDO::FETCH_ASSOC);
echo "portrait $portraitId: aid={$r['aid']} bid={$r['bid']} mdid={$r['mdid']}\n";

// 尝试插入（模拟 saveToQrcode）
try {
    $qrcodeValue = 'synth_' . $portraitId . '_' . time();
    $stmt = $pdo->prepare("INSERT INTO ddwx_ai_travel_photo_qrcode (aid, bid, portrait_id, qrcode, status, create_time, update_time) VALUES (?, ?, ?, ?, 1, ?, ?)");
    $now = time();
    $stmt->execute([$r['aid'], $r['bid'], $portraitId, $qrcodeValue, $now, $now]);
    echo "插入成功！qrcode_id=" . $pdo->lastInsertId() . " qrcode=$qrcodeValue\n";
} catch (\Exception $e) {
    echo "插入失败: " . $e->getMessage() . "\n";
}

// 检查表现在有没有
$c = $pdo->query("SELECT COUNT(*) as c FROM ddwx_ai_travel_photo_qrcode WHERE portrait_id=$portraitId")->fetch(PDO::FETCH_ASSOC);
echo "portrait_id=$portraitId 的 qrcode 记录数: {$c['c']}\n";
