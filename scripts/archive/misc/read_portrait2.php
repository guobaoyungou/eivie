<?php
$file = '/home/www/ai.eivie.cn/app/controller/AiTravelPhoto.php';
$content = file_get_contents($file);
$pos = strpos($content, 'function portrait_list');
// Continue from where we left off - find gender_text/age_text construction
$searchFrom = $pos;
foreach (['gender_text', 'age_text', 'is_multi_text', 'face_count_text'] as $kw) {
    $p = strpos($content, $kw, $searchFrom);
    if ($p !== false && ($p - $pos) < 5000) {
        echo "Found '$kw' at offset " . ($p - $pos) . ":\n";
        echo substr($content, max(0,$p-80), 200) . "\n---\n";
    }
}
