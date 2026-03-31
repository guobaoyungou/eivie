<?php
// 独立域名专用的仪表盘页面
session_start();
if (!isset($_SESSION['sysadmin_admin_id'])) {
    header('Location: /lp_login.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /lp_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>授权管理后台</title>
    <style>
        body {
            background-color: #f2f2f2;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .header {
            background-color: #1E9FFF;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header .user-info {
            display: flex;
            align-items: center;
        }
        .header .user-info span {
            margin-right: 15px;
        }
        .header .logout-btn {
            background-color: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .header .logout-btn:hover {
            background-color: rgba(255,255,255,0.3);
        }
        .container {
            display: flex;
            height: calc(100vh - 70px);
        }
        .sidebar {
            width: 200px;
            background-color: #333;
            color: white;
            padding: 20px 0;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar li {
            padding: 10px 20px;
        }
        .sidebar li:hover {
            background-color: #444;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
        }
        .content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        .dashboard-card {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .dashboard-card h2 {
            margin-top: 0;
            color: #333;
        }
        .dashboard-stats {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .stat-item {
            flex: 1;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .stat-item .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #1E9FFF;
        }
        .stat-item .stat-label {
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>授权管理后台</h1>
        <div class="user-info">
            <span>欢迎，<?php echo $_SESSION['sysadmin_admin_name']; ?></span>
            <a href="/lp_dashboard.php?logout=1"><button class="logout-btn">退出登录</button></a>
        </div>
    </div>
    <div class="container">
        <div class="sidebar">
            <ul>
                <li><a href="/lp_dashboard.php">仪表盘</a></li>
                <li><a href="#">授权管理</a></li>
                <li><a href="#">黑名单管理</a></li>
                <li><a href="#">系统设置</a></li>
            </ul>
        </div>
        <div class="content">
            <div class="dashboard-card">
                <h2>仪表盘</h2>
                <div class="dashboard-stats">
                    <div class="stat-item">
                        <div class="stat-value">128</div>
                        <div class="stat-label">活跃授权</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">32</div>
                        <div class="stat-label">黑名单域名</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">16</div>
                        <div class="stat-label">待续期授权</div>
                    </div>
                </div>
            </div>
            <div class="dashboard-card">
                <h2>最近操作</h2>
                <p>这里显示最近的操作记录...</p>
            </div>
        </div>
    </div>
</body>
</html>