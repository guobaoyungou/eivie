<?php
/**
 * 临时缓存清除脚本
 * 使用后请立即删除此文件
 */

// 清除 OpCache
if (function_exists('opcache_reset')) {
    opcache_reset();
    $opcache_status = "✅ OpCache 已清除";
} else {
    $opcache_status = "⚠️ OpCache 未启用";
}

// 清除 PHP 文件状态缓存
clearstatcache();

// 显示结果
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>缓存清除工具</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
        }
        .warning {
            background: #fff3e0;
            border-left-color: #ff9800;
        }
        .info {
            padding: 10px;
            background: #e3f2fd;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #4caf50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .button:hover {
            background: #45a049;
        }
        .delete-btn {
            background: #f44336;
        }
        .delete-btn:hover {
            background: #da190b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 缓存清除工具</h1>
        
        <div class="status <?php echo function_exists('opcache_reset') ? '' : 'warning'; ?>">
            <strong><?php echo $opcache_status; ?></strong>
        </div>
        
        <div class="status">
            <strong>✅ 文件状态缓存已清除</strong>
        </div>
        
        <div class="status">
            <strong>⏰ 当前时间：</strong><?php echo date('Y-m-d H:i:s'); ?>
        </div>
        
        <div class="info">
            <strong>📌 下一步操作：</strong><br>
            1. 清空浏览器缓存（Ctrl+Shift+Delete）<br>
            2. 刷新订单列表页面<br>
            3. 检查状态文本是否显示为"已完成"<br>
            4. <strong style="color: red;">立即删除此文件（安全起见）</strong>
        </div>
        
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="button">🔄 再次清除缓存</a>
        
        <div style="margin-top: 20px; color: #999; font-size: 12px;">
            <strong>⚠️ 安全提示：</strong>此文件仅供临时使用，清除缓存后请立即删除！
        </div>
    </div>
</body>
</html>
<?php
// 记录日志
error_log("[" . date('Y-m-d H:i:s') . "] OpCache cleared by: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
?>
