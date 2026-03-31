<?php
require_once 'data_store.php';
session_start();
if (!isset($_SESSION['sysadmin_admin_id'])) {
    header('Location: /index.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /index.php');
    exit;
}

$store = new DataStore();
$licenses = $store->getLicenses();
$blacklist = $store->getBlacklist();
$settings = $store->getSettings();

// 统计数据
$totalLicenses = count($licenses);
$activeLicenses = 0;
$expiredLicenses = 0;
$expiringSoon = 0;

foreach ($licenses as $license) {
    if ($license['status'] == 1) {
        $expireTime = strtotime($license['expire_time']);
        if ($expireTime < time()) {
            $expiredLicenses++;
        } else {
            $activeLicenses++;
            if ($expireTime < strtotime('+30 days')) {
                $expiringSoon++;
            }
        }
    }
}

$totalBlacklist = count($blacklist);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>授权管理后台</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f2f2f2; font-family: Arial, sans-serif; }
        .header { background-color: #1E9FFF; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header .user-info { display: flex; align-items: center; }
        .header .user-info span { margin-right: 15px; }
        .header .logout-btn { background-color: rgba(255,255,255,0.2); color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        .container { display: flex; height: calc(100vh - 70px); }
        .sidebar { width: 200px; background-color: #333; color: white; padding: 20px 0; }
        .sidebar ul { list-style: none; }
        .sidebar li { padding: 10px 20px; }
        .sidebar li:hover, .sidebar li.active { background-color: #444; }
        .sidebar a { color: white; text-decoration: none; display: block; }
        .content { flex: 1; padding: 20px; overflow-y: auto; }
        .card { background-color: white; border-radius: 5px; padding: 20px; margin-bottom: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .card h2 { margin-top: 0; color: #333; margin-bottom: 20px; }
        .stats { display: flex; gap: 20px; flex-wrap: wrap; }
        .stat-item { flex: 1; min-width: 200px; background-color: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center; border-left: 4px solid #1E9FFF; }
        .stat-item.warning { border-left-color: #ffc107; }
        .stat-item.danger { border-left-color: #dc3545; }
        .stat-item.success { border-left-color: #28a745; }
        .stat-value { font-size: 32px; font-weight: bold; color: #1E9FFF; }
        .stat-item.warning .stat-value { color: #ffc107; }
        .stat-item.danger .stat-value { color: #dc3545; }
        .stat-item.success .stat-value { color: #28a745; }
        .stat-label { color: #666; margin-top: 5px; }
        .recent-list { margin-top: 10px; }
        .recent-item { padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        .recent-item:last-child { border-bottom: none; }
        .recent-item .time { color: #999; font-size: 12px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #1E9FFF; color: white; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($settings['site_name'] ?? '授权管理后台'); ?></h1>
        <div class="user-info">
            <span>欢迎，<?php echo $_SESSION['sysadmin_admin_name']; ?></span>
            <a href="/dashboard.php?logout=1"><button class="logout-btn">退出登录</button></a>
        </div>
    </div>
    <div class="container">
        <div class="sidebar">
            <ul>
                <li class="active"><a href="/dashboard.php">仪表盘</a></li>
                <li><a href="/license.php">授权管理</a></li>
                <li><a href="/blacklist.php">黑名单管理</a></li>
                <li><a href="/settings.php">系统设置</a></li>
            </ul>
        </div>
        <div class="content">
            <div class="card">
                <h2>数据概览</h2>
                <div class="stats">
                    <div class="stat-item success">
                        <div class="stat-value"><?php echo $activeLicenses; ?></div>
                        <div class="stat-label">活跃授权</div>
                    </div>
                    <div class="stat-item danger">
                        <div class="stat-value"><?php echo $expiredLicenses; ?></div>
                        <div class="stat-label">已过期授权</div>
                    </div>
                    <div class="stat-item warning">
                        <div class="stat-value"><?php echo $expiringSoon; ?></div>
                        <div class="stat-label">即将到期（30天内）</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $totalBlacklist; ?></div>
                        <div class="stat-label">黑名单域名</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>最近添加的授权 <a href="/license.php?action=create" class="btn btn-primary" style="float: right;">添加授权</a></h2>
                <div class="recent-list">
                    <?php 
                    $recentLicenses = array_slice(array_reverse($licenses), 0, 5);
                    if (empty($recentLicenses)): 
                    ?>
                    <div class="recent-item">
                        <span>暂无授权数据</span>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentLicenses as $license): ?>
                    <div class="recent-item">
                        <div>
                            <strong><?php echo htmlspecialchars($license['license_code']); ?></strong>
                            <span style="color: #666; margin-left: 10px;"><?php echo htmlspecialchars($license['customer_name'] ?: $license['domain'] ?: '未命名'); ?></span>
                        </div>
                        <div>
                            <span class="time"><?php echo htmlspecialchars($license['create_time']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h2>快速操作</h2>
                <div style="display: flex; gap: 10px;">
                    <a href="/license.php?action=create" class="btn btn-primary">添加授权</a>
                    <a href="/blacklist.php?action=add" class="btn btn-primary" style="background-color: #dc3545;">添加黑名单</a>
                    <a href="/settings.php" class="btn btn-primary" style="background-color: #6c757d;">系统设置</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>