<?php
$file = '/home/www/ai.eivie.cn/app/view/ai_travel_photo/portrait_list.html';
$content = file_get_contents($file);

// Find the auto-tag column rendering
$pos = strpos($content, '自动标签');
if ($pos !== false) {
    // Show 500 chars around it
    $start = max(0, $pos - 200);
    echo "=== Around '自动标签' (pos=$pos) ===\n";
    echo substr($content, $start, 800) . "\n";
}

// Also look for templet function or tag rendering
echo "\n=== Looking for templet/tag/age_tag/gender_tag ===\n";
$keywords = ['templet', 'age_tag', 'gender_tag', 'auto_tags', 'is_multi'];
foreach ($keywords as $kw) {
    if (strpos($content, $kw) !== false) {
        $p = strpos($content, $kw);
        echo "Found '$kw' at pos $p:\n";
        echo "  " . trim(substr($content, max(0,$p-50), 150)) . "\n\n";
    }
}
