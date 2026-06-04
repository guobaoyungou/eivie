<?php
// 查特定portrait的generation和qrcode
$pdo = new PDO('mysql:host=localhost;dbname=guobaoyungou_cn', 'guobaoyungou_cn', '5ArfhRr9xzyScrF5');

$ids = [258, 259, 256, 255, 254];
foreach ($ids as $pid) {
    echo "\n=== portrait_id=$pid ===\n";
    // generation记录
    $r = $pdo->query("SELECT id, template_id, scene_id, status, create_time FROM ddwx_ai_travel_photo_generation WHERE portrait_id=$pid");
    $gens = $r->fetchAll(PDO::FETCH_ASSOC);
    foreach ($gens as $g) {
        echo "  gen_id={$g['id']} tpl={$g['template_id']} scene={$g['scene_id']} status={$g['status']} " . date('m-d H:i', $g['create_time']) . "\n";
    }
    // result记录
    $rc = $pdo->query("SELECT COUNT(*) as c FROM ddwx_ai_travel_photo_result WHERE portrait_id=$pid AND status=1")->fetch(PDO::FETCH_ASSOC);
    echo "  results: {$rc['c']}\n";
    // qrcode
    $qr = $pdo->query("SELECT id, qrcode, status FROM ddwx_ai_travel_photo_qrcode WHERE portrait_id=$pid")->fetch(PDO::FETCH_ASSOC);
    echo "  qrcode: " . ($qr ? "{$qr['id']}/{$qr['qrcode']}/status={$qr['status']}" : '无') . "\n";
}
