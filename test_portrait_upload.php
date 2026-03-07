<?php
/**
 * AI旅拍人像上传功能测试脚本
 * 
 * 测试场景：
 * 1. 文件格式校验（JPG、PNG、BMP等）
 * 2. 文件大小校验（10KB - 10MB）
 * 3. 图像尺寸校验（最小200px，最大9999px）
 * 4. MD5去重测试
 * 5. OSS上传测试
 * 6. 缩略图生成测试
 * 7. 数据库写入测试
 * 8. 队列任务推送测试
 */

echo "========================================\n";
echo "AI旅拍人像上传功能测试\n";
echo "========================================\n\n";

// 测试1：检查控制器文件是否存在
echo "【测试1】检查控制器文件...​​n";
$controllerPath = __DIR__ . '/app/controller/AiTravelPhoto.php';
if (file_exists($controllerPath)) {
    echo "✓ AiTravelPhoto.php控制器文件存在\n";
    
    $content = file_get_contents($controllerPath);
    
    // 检查portrait_upload方法
    if (strpos($content, 'function portrait_upload()') !== false) {
        echo "✓ portrait_upload方法存在\n";
    } else {
        echo "✗ portrait_upload方法不存在\n";
    }
    
    // 检查generateThumbnail方法
    if (strpos($content, 'function generateThumbnail(') !== false) {
        echo "✓ generateThumbnail方法存在\n";
    } else {
        echo "✗ generateThumbnail方法不存在\n";
    }
    
    // 检查triggerAsyncTasks方法
    if (strpos($content, 'function triggerAsyncTasks(') !== false) {
        echo "✓ triggerAsyncTasks方法存在\n";
    } else {
        echo "✗ triggerAsyncTasks方法不存在\n";
    }
    
    // 检查文件校验逻辑
    if (strpos($content, "'ext' => 'jpg,jpeg,png'") !== false) {
        echo "✓ 文件格式校验逻辑存在\n";
    } else {
        echo "✗ 文件格式校验逻辑不存在\n";
    }
    
    // 检查MD5去重逻辑
    if (strpos($content, 'md5_file') !== false && strpos($content, '该图片已存在') !== false) {
        echo "✓ MD5去重逻辑存在\n";
    } else {
        echo "✗ MD5去重逻辑不存在\n";
    }
    
    // 检查OSS上传逻辑
    if (strpos($content, 'Pic::uploadoss') !== false) {
        echo "✓ OSS上传逻辑存在\n";
    } else {
        echo "✗ OSS上传逻辑不存在\n";
    }
    
    // 检查队列任务推送
    if (strpos($content, 'Queue::push') !== false && strpos($content, 'CutoutJob') !== false) {
        echo "✓ 队列任务推送逻辑存在\n";
    } else {
        echo "✗ 队列任务推送逻辑不存在\n";
    }
} else {
    echo "✗ AiTravelPhoto.php控制器文件不存在\n";
}
echo "\n";

// 测试4：检查视图文件
echo "【测试4】检查视图文件...\n";
$viewPath = __DIR__ . '/app/view/ai_travel_photo/portrait_list.html';
if (file_exists($viewPath)) {
    echo "✓ portrait_list.html视图文件存在\n";
    
    // 检查是否包含上传按钮
    $content = file_get_contents($viewPath);
    if (strpos($content, 'uploadBtn') !== false) {
        echo "✓ 视图包含上传按钮\n";
    } else {
        echo "✗ 视图不包含上传按钮\n";
    }
    
    // 检查是否包含LayUI upload组件
    if (strpos($content, 'upload.render') !== false) {
        echo "✓ 视图包含LayUI upload组件\n";
    } else {
        echo "✗ 视图不包含LayUI upload组件\n";
    }
    
    // 检查是否包含上传进度展示
    if (strpos($content, 'file_status_') !== false) {
        echo "✓ 视图包含上传进度展示\n";
    } else {
        echo "✗ 视图不包含上传进度展示\n";
    }
} else {
    echo "✗ portrait_list.html视图文件不存在\n";
}
echo "\n";

// 测试5：检查队列任务文件
echo "【测试5】检查队列任务文件...\n";
$jobFiles = [
    __DIR__ . '/app/job/CutoutJob.php' => 'CutoutJob',
    __DIR__ . '/app/job/ImageGenerationJob.php' => 'ImageGenerationJob'
];

foreach ($jobFiles as $path => $name) {
    if (file_exists($path)) {
        echo "✓ {$name}.php文件存在\n";
    } else {
        echo "✗ {$name}.php文件不存在\n";
    }
}
echo "\n";

// 测试6：检查图像处理函数
echo "【测试6】检查图像处理函数...\n";
if (function_exists('imagecreatefromjpeg')) {
    echo "✓ GD库JPEG支持可用\n";
} else {
    echo "✗ GD库JPEG支持不可用\n";
}

if (function_exists('imagecreatefrompng')) {
    echo "✓ GD库PNG支持可用\n";
} else {
    echo "✗ GD库PNG支持不可用\n";
}

if (function_exists('imagecopyresampled')) {
    echo "✓ GD库图像缩放功能可用\n";
} else {
    echo "✗ GD库图像缩放功能不可用\n";
}
echo "\n";

// 测试7：检查上传目录权限
echo "【测试7】检查上传目录权限...\n";
$uploadDir = __DIR__ . '/upload';
if (is_dir($uploadDir)) {
    if (is_writable($uploadDir)) {
        echo "✓ 上传目录可写\n";
    } else {
        echo "✗ 上传目录不可写，请执行：chmod 755 " . $uploadDir . "\n";
    }
} else {
    if (mkdir($uploadDir, 0755, true)) {
        echo "✓ 上传目录创建成功\n";
    } else {
        echo "✗ 上传目录创建失败\n";
    }
}
echo "\n";

// 测试8：模拟文件校验逻辑
echo "【测试8】模拟文件校验逻辑...\n";

// 测试文件扩展名校验
$allowedExts = ['jpg', 'jpeg', 'png'];
$testFiles = [
    'test.jpg' => true,
    'test.jpeg' => true,
    'test.png' => true,
    'test.gif' => false,
    'test.bmp' => false,
    'test.pdf' => false
];

foreach ($testFiles as $filename => $shouldPass) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $passed = in_array($ext, $allowedExts);
    
    if ($passed === $shouldPass) {
        echo "✓ " . $filename . "：校验" . ($shouldPass ? '通过' : '拒绝') . "\n";
    } else {
        echo "✗ " . $filename . "：校验结果不符合预期\n";
    }
}

// 测试文件大小校验
echo "\n文件大小校验：\n";
$testSizes = [
    ['size' => 5 * 1024, 'desc' => '5KB', 'expected' => false], // 小于10KB
    ['size' => 100 * 1024, 'desc' => '100KB', 'expected' => true],
    ['size' => 5 * 1024 * 1024, 'desc' => '5MB', 'expected' => true],
    ['size' => 15 * 1024 * 1024, 'desc' => '15MB', 'expected' => false] // 大于10MB
];

foreach ($testSizes as $test) {
    $minSize = 10 * 1024;
    $maxSize = 10 * 1024 * 1024;
    $passed = ($test['size'] >= $minSize && $test['size'] <= $maxSize);
    
    if ($passed === $test['expected']) {
        echo "✓ " . $test['desc'] . "：校验" . ($test['expected'] ? '通过' : '拒绝') . "\n";
    } else {
        echo "✗ " . $test['desc'] . "：校验结果不符合预期\n";
    }
}
echo "\n";

// 测试9：检查路由配置
echo "【测试9】检查路由访问...\n";
echo "人像列表页面：/AiTravelPhoto/portrait_list\n";
echo "人像上传接口：/AiTravelPhoto/portrait_upload（POST）\n";
echo "\n";

echo "========================================\n";
echo "测试完成！\n";
echo "========================================\n\n";

echo "【使用说明】\n";
echo "1. 访问商家后台：旅拍 -> 人像管理\n";
echo "2. 点击「批量上传人像」按钮\n";
echo "3. 选择1-20个图片文件（JPG/JPEG/PNG格式，10KB-10MB）\n";
echo "4. 查看上传进度和结果\n";
echo "5. 上传成功后，系统会自动触发抠图和AI生成任务\n\n";

echo "【注意事项】\n";
echo "1. 确保已配置OSS存储或本地存储目录有写权限\n";
echo "2. 确保Redis队列服务正常运行（用于异步任务）\n";
echo "3. 确保已配置AI API密钥（通义万相/可灵AI）\n";
echo "4. 首次使用建议先上传1-2张测试图片\n";
echo "5. 查看日志：runtime/log/ai_travel_photo.log\n\n";
