<?php
// Find view directory first
echo shell_exec("find /home/www/ai.eivie.cn -name 'portrait*' -type f 2>/dev/null | grep -E '\.(html|php)$' | head -30");
echo "\n---\n";
// Also find the admin controller for portrait
echo shell_exec("grep -rl '人像管理' /home/www/ai.eivie.cn/app/ 2>/dev/null | head -20");
