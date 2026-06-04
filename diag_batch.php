<?php
// 诊断：批量上传人像的合成状态和二维码情况
$pdo = new PDO('mysql:host=localhost;dbname=guobaoyungou_cn', 'guobaoyungou_cn', '5ArfhRr9xzyScrF5');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== 批量上传的人像(type=1, uid=0) 最近20条 ===\n";
$r = $pdo->query("SELECT id, type, source_type, user_openid, synthesis_status, auto_tag_status, mdid, create_time FROM ddwx_ai_travel_photo_portrait WHERE type=1 AND uid=0 ORDER BY id DESC LIMIT 20");
while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo "  id={$row['id']} src={$row['source_type']} openid=" . ($row['user_openid'] ?: 'NULL') . " synth={$row['synthesis_status']} tag={$row['auto_tag_status']} mdid={$row['mdid']} " . date('m-d H:i', $row['create_time']) . "\n";
}

echo "\n=== 这些人像的result成片数 ===\n";
$r = $pdo->query("SELECT p.id, p.synthesis_status, (SELECT COUNT(*) FROM ddwx_ai_travel_photo_result r WHERE r.portrait_id=p.id AND r.status=1) as result_cnt, (SELECT COUNT(*) FROM ddwx_ai_travel_photo_qrcode q WHERE q.portrait_id=p.id) as qrcode_cnt FROM ddwx_ai_travel_photo_portrait p WHERE p.type=1 AND p.uid=0 ORDER BY p.id DESC LIMIT 20");
while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo "  id={$row['id']} synth={$row['synthesis_status']} results={$row['result_cnt']} qrcodes={$row['qrcode_cnt']}\n";
}

echo "\n=== 二维码表最新10条 ===\n";
$r = $pdo->query("SELECT q.id, q.portrait_id, q.qrcode, q.status, p.type, p.user_openid FROM ddwx_ai_travel_photo_qrcode q LEFT JOIN ddwx_ai_travel_photo_portrait p ON q.portrait_id=p.id ORDER BY q.id DESC LIMIT 10");
while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo "  qr_id={$row['id']} portrait_id={$row['portrait_id']} qrcode={$row['qrcode']} status={$row['status']} type={$row['type']} openid=" . ($row['user_openid'] ?: 'NULL') . "\n";
}
