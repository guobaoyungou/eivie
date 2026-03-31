<?php
require_once 'data_store.php';
session_start();
if (!isset($_SESSION['sysadmin_admin_id'])) {
    header('Location: /index.php');
    exit;
}

$store = new DataStore();
$action = $_GET['action'] ?? 'list';
$message = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'add') {
        $data = [
            'domain' => $_POST['domain'] ?? '',
            'reason' => $_POST['reason'] ?? '',
            'type' => $_POST['type'] ?? 'domain',
        ];
        $store->addBlacklist($data);
        $message = '黑名单添加成功！';
        $action = 'list';
    } elseif ($action == 'delete') {
        $id = $_POST['id'];
        $store->deleteBlacklist($id);
        $message = '黑名单删除成功！';
        $action = 'list';
    }
}

$blacklist = $store->getBlacklist();
$settings = $store->getSettings();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>黑名单管理 - <?php echo htmlspecialchars($settings['site_name'] ?? '授权管理后台'); ?></title>
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
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #1E9FFF; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-secondary { background-color: #6c757d; color: white; }
        .btn:hover { opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; max-width: 500px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .form-actions { margin-top: 20px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .type-domain { color: #dc3545; font-weight: bold; }
        .type-ip { color: #fd7e14; font-weight: bold; }
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
                <li><a href="/dashboard.php">仪表盘</a></li>
                <li><a href="/license.php">授权管理</a></li>
                <li class="active"><a href="/blacklist.php">黑名单管理</a></li>
                <li><a href="/settings.php">系统设置</a></li>
            </ul>
        </div>
        <div class="content">
            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($action == 'list'): ?>
            <div class="card">
                <h2>黑名单列表 <a href="?action=add" class="btn btn-danger" style="float: right;">添加黑名单</a></h2>
                <table>
                    <thead>
                        <tr>
                            <th>类型</th>
                            <th>域名/IP</th>
                            <th>原因</th>
                            <th>添加时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blacklist as $item): ?>
                        <tr>
                            <td class="<?php echo $item['type'] == 'domain' ? 'type-domain' : 'type-ip'; ?>">
                                <?php echo $item['type'] == 'domain' ? '域名' : 'IP'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['domain']); ?></td>
                            <td><?php echo htmlspecialchars($item['reason'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['create_time']); ?></td>
                            <td>
                                <form method="post" action="?action=delete" style="display: inline;" onsubmit="return confirm('确定要删除吗？');">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger">删除</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($blacklist)): ?>
                        <tr><td colspan="5" style="text-align: center;">暂无黑名单数据</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php elseif ($action == 'add'): ?>
            <div class="card">
                <h2>添加黑名单</h2>
                <form method="post" action="?action=add">
                    <div class="form-group">
                        <label>类型</label>
                        <select name="type">
                            <option value="domain">域名</option>
                            <option value="ip">IP地址</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>域名/IP</label>
                        <input type="text" name="domain" required placeholder="例如：example.com 或 192.168.1.1">
                    </div>
                    <div class="form-group">
                        <label>原因</label>
                        <textarea name="reason" rows="3" placeholder="请输入拉黑原因"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-danger">添加</button>
                        <a href="?action=list" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>