<?php
// Find the backend method that serves portrait list data
$file = '/home/www/ai.eivie.cn/app/controller/AiTravelPhoto.php';
$content = file_get_contents($file);

// Find portrait_list method
$pos = strpos($content, 'function portrait_list');
if ($pos !== false) {
    echo "=== portrait_list method (pos=$pos) ===\n";
    echo substr($content, $pos, 3000) . "\n";
} else {
    echo "portrait_list not found, searching...\n";
    // Try other patterns
    foreach (['portrait', 'gender_text', 'age_text'] as $kw) {
        $p = 0;
        while (($p = strpos($content, $kw, $p)) !== false) {
            $line = substr_count(substr($content, 0, $p), "\n") + 1;
            echo "Found '$kw' at line $line, pos $p:\n";
            echo "  " . trim(substr($content, max(0,$p-30), 120)) . "\n\n";
            $p++;
        }
    }
}
