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
$error = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'create' || $action == 'edit') {
        $data = [
            'domain' => $_POST['domain'] ?? '',
            'customer_name' => $_POST['customer_name'] ?? '',
            'customer_email' => $_POST['customer_email'] ?? '',
            'customer_phone' => $_POST['customer_phone'] ?? '',
            'expire_time' => $_POST['expire_time'] ?? '',
            'status' => $_POST['status'] ?? 1,
            'notes' => $_POST['notes'] ?? '',
        ];
        
        if ($action == 'create') {
            $result = $store->addLicense($data);
            if ($result) {
                $message = '授权创建成功！授权码：' . $result['license_code'];
            } else {
                $message = '授权创建失败，请检查文件权限！';
            }
        } else {
            $id = $_POST['id'];
            $result = $store->updateLicense($id, $data);
            if ($result) {
                $message = '授权更新成功！';
            } else {
                $message = '授权更新失败，请检查文件权限！';
            }
        }
        $action = 'list';
    } elseif ($action == 'delete') {
        $id = $_POST['id'];
        $success = $store->deleteLicense($id);
        if ($success) {
            $message = '授权删除成功！';
        } else {
            $message = '授权删除失败，请检查文件权限！';
        }
        $action = 'list';
    } elseif ($action == 'renew') {
        $id = $_POST['id'];
        $days = $_POST['days'] ?? 365;
        $license = $store->getLicense($id);
        if ($license) {
            $expireTime = strtotime($license['expire_time']);
            if ($expireTime < time()) {
                $expireTime = time();
            }
            $newExpireTime = date('Y-m-d', strtotime("+{$days} days", $expireTime));
            $result = $store->updateLicense($id, ['expire_time' => $newExpireTime]);
            if ($result) {
                $message = "授权续期成功！新到期时间：{$newExpireTime}";
            } else {
                $message = '授权续期失败，请检查文件权限！';
            }
        }
        $action = 'list';
    }
}

$licenses = $store->getLicenses();
$license = null;
if ($action == 'edit' || $action == 'renew') {
    $id = $_GET['id'] ?? 0;
    $license = $store->getLicense($id);
}

$settings = $store->getSettings();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>授权管理 - <?php echo htmlspecialchars($settings['site_name'] ?? '授权管理后台'); ?></title>
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
        .btn-success { background-color: #28a745; color: white; }
        .btn-warning { background-color: #ffc107; color: #333; }
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
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .status-expired { color: #ffc107; }
        .actions { display: flex; gap: 5px; }
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
                <li class="active"><a href="/license.php">授权管理</a></li>
                <li><a href="/blacklist.php">黑名单管理</a></li>
                <li><a href="/settings.php">系统设置</a></li>
            </ul>
        </div>
        <div class="content">
            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($action == 'list'): ?>
            <div class="card">
                <h2>授权列表 <a href="?action=create" class="btn btn-primary" style="float: right;">添加授权</a></h2>
                <table>
                    <thead>
                        <tr>
                            <th>授权码</th>
                            <th>域名</th>
                            <th>客户名称</th>
                            <th>到期时间</th>
                            <th>状态</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licenses as $lic): ?>
                        <?php 
                        $isExpired = strtotime($lic['expire_time']) < time();
                        $statusClass = $lic['status'] == 1 ? ($isExpired ? 'status-expired' : 'status-active') : 'status-inactive';
                        $statusText = $lic['status'] == 1 ? ($isExpired ? '已过期' : '正常') : '已禁用';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lic['license_code']); ?></td>
                            <td><?php echo htmlspecialchars($lic['domain'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($lic['customer_name'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($lic['expire_time']); ?></td>
                            <td class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></td>
                            <td>
                                <div class="actions">
                                    <a href="?action=edit&id=<?php echo $lic['id']; ?>" class="btn btn-primary">编辑</a>
                                    <a href="?action=renew&id=<?php echo $lic['id']; ?>" class="btn btn-success">续期</a>
                                    <form method="post" action="?action=delete" style="display: inline;" onsubmit="return confirm('确定要删除吗？');">
                                        <input type="hidden" name="id" value="<?php echo $lic['id']; ?>">
                                        <button type="submit" class="btn btn-danger">删除</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($licenses)): ?>
                        <tr><td colspan="6" style="text-align: center;">暂无授权数据</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php elseif ($action == 'create' || $action == 'edit'): ?>
            <div class="card">
                <h2><?php echo $action == 'create' ? '添加授权' : '编辑授权'; ?></h2>
                <form method="post" action="?action=<?php echo $action; ?>">
                    <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                    <div class="form-group">
                        <label>授权码</label>
                        <input type="text" value="<?php echo htmlspecialchars($license['license_code'] ?? ''); ?>" readonly style="background-color: #f5f5f5;">
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>域名</label>
                        <input type="text" name="domain" value="<?php echo htmlspecialchars($license['domain'] ?? ''); ?>" placeholder="例如：example.com">
                    </div>
                    <div class="form-group">
                        <label>客户名称</label>
                        <input type="text" name="customer_name" value="<?php echo htmlspecialchars($license['customer_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>客户邮箱</label>
                        <input type="email" name="customer_email" value="<?php echo htmlspecialchars($license['customer_email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>客户电话</label>
                        <input type="text" name="customer_phone" value="<?php echo htmlspecialchars($license['customer_phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>到期时间</label>
                        <input type="date" name="expire_time" value="<?php echo htmlspecialchars($license['expire_time'] ?? date('Y-m-d', strtotime('+1 year'))); ?>">
                    </div>
                    <div class="form-group">
                        <label>状态</label>
                        <select name="status">
                            <option value="1" <?php echo ($license['status'] ?? 1) == 1 ? 'selected' : ''; ?>>正常</option>
                            <option value="0" <?php echo ($license['status'] ?? 1) == 0 ? 'selected' : ''; ?>>禁用</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>备注</label>
                        <textarea name="notes" rows="3"><?php echo htmlspecialchars($license['notes'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">保存</button>
                        <a href="?action=list" class="btn btn-secondary">取消</a>
                    </div>
                </form>
            </div>
            
            <?php elseif ($action == 'renew'): ?>
            <div class="card">
                <h2>授权续期</h2>
                <?php if ($license): ?>
                <p>授权码：<strong><?php echo htmlspecialchars($license['license_code']); ?></strong></p>
                <p>当前到期时间：<strong><?php echo htmlspecialchars($license['expire_time']); ?></strong></p>
                <form method="post" action="?action=renew">
                    <input type="hidden" name="id" value="<?php echo $license['id']; ?>">
                    <div class="form-group">
                        <label>续期天数</label>
                        <select name="days">
                            <option value="30">30天</option>
                            <option value="90">90天</option>
                            <option value="180">180天</option>
                            <option value="365" selected>365天</option>
                            <option value="730">730天（2年）</option>
                            <option value="1095">1095天（3年）</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">确认续期</button>
                        <a href="?action=list" class="btn btn-secondary">取消</a>
                    </div>
                </form>
                <?php else: ?>
                <p>授权不存在</p>
                <a href="?action=list" class="btn btn-primary">返回列表</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>