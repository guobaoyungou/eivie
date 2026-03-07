<?php
require __DIR__ . '/vendor/autoload.php';

use think\facade\Db;

// 初始化ThinkPHP框架
$app = new \think\App();
$app->initialize();

// 查询最新的人像记录
$portraits = Db::name('ai_travel_photo_portrait')
    ->order('id', 'desc')
    ->limit(2)
    ->select()
    ->toArray();

foreach ($portraits as $p) {
    echo "ID: " . $p['id'] . "\n";
    echo "文件名: " . $p['file_name'] . "\n";
    echo "门店ID: " . $p['mdid'] . "\n";
    echo "原图URL: " . $p['original_url'] . "\n";
    echo "缩略图URL: " . $p['thumbnail_url'] . "\n";
    echo "---\n";
}
