<?php
$pdo = new PDO('mysql:host=localhost;dbname=guobaoyungou_cn', 'guobaoyungou_cn', '5ArfhRr9xzyScrF5');
$r = $pdo->query("SELECT p.id, p.bid, p.aid, p.synthesis_status, (SELECT COUNT(*) FROM ddwx_ai_travel_photo_qrcode q WHERE q.portrait_id=p.id) as qr FROM ddwx_ai_travel_photo_portrait p WHERE p.type=1 AND p.uid=0 AND p.id>=247 ORDER BY p.id");
while ($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['id']} bid={$row['bid']} aid={$row['aid']} synth={$row['synthesis_status']} qr={$row['qr']}\n";
}
