<?php
$file = '/home/www/ai.eivie.cn/app/view/ai_travel_photo/synthesis_template_list.html';
$content = file_get_contents($file);

// 1. Replace age display line - add precise Chinese mapping
$content = str_replace(
    "if(a) html += '<span style=\"display:inline-block;padding:1px 6px;margin:1px 2px;background:#e8f4f8;color:#1E9FFF;border-radius:2px;font-size:11px\">' + a + '岁</span>';",
    "if(a){var am={'0-2':'婴幼儿','3-9':'学龄儿童','10-19':'青少年','20-29':'职场青年','30-39':'而立青年','40-49':'壮年期','50-59':'中年主力','60-69':'准老年','70+':'高龄老人'};html+='<span style=\"display:inline-block;padding:1px 6px;margin:1px 2px;background:#e8f4f8;color:#1E9FFF;border-radius:2px;font-size:11px\">'+(am[a]||a)+'</span>';}",
    $content
);

// 2. Update width
$content = str_replace(
    "'auto_tags', title: '自动标签', width: 220, templet",
    "'auto_tags', title: '自动标签', width: 240, templet",
    $content
);

file_put_contents($file, $content);
echo "OK\n";

// Verify
$check = strpos($content, "am={");
echo ($check !== false ? "OK: Age map found at pos $check\n" : "ERROR: Age map not found\n");
$check2 = strpos($content, "width: 240");
echo ($check2 !== false ? "OK: Width updated to 240\n" : "ERROR: Width not changed\n");

// Show the actual age line for verification
$pos = strpos($content, "am={");
echo "\nActual content:\n";
echo substr($content, $pos, 300) . "\n";
