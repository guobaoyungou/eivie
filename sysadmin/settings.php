<?php
require_once 'data_store.php';
session_start();
if (!isset($_SESSION['sysadmin_admin_id'])) {
    header('Location: /index.php');
    exit;
}

$store = new DataStore();
$message = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // 验证密码
        $user = $store->getUser($_SESSION['sysadmin_admin_id']);
        if (!$user || $current_password != $user['password']) {
            $message = '当前密码错误！';
        } elseif (empty($new_password)) {
            $message = '新密码不能为空！';
        } elseif (strlen($new_password) < 6) {
            $message = '新密码长度不能少于6位！';
        } elseif ($new_password != $confirm_password) {
            $message = '两次输入的密码不一致！';
        } else {
            $store->updateUserPassword($_SESSION['sysadmin_admin_id'], $new_password);
            $message = '密码修改成功！';
        }
    } else {
        $settings = [
            'site_name' => $_POST['site_name'] ?? '授权管理后台',
            'admin_email' => $_POST['admin_email'] ?? '',
            'license_expire_days' => (int)($_POST['license_expire_days'] ?? 365),
            'auto_renew' => isset($_POST['auto_renew']),
            'notify_before_expire' => (int)($_POST['notify_before_expire'] ?? 7),
            'api_key' => $_POST['api_key'] ?? '',
            'secret_key' => $_POST['secret_key'] ?? '',
        ];
        $success = $store->saveSettings($settings);
        if ($success) {
            $message = '设置保存成功！';
        } else {
            $message = '设置保存失败，请检查文件权限！';
        }
    }
}

$settings = $store->getSettings();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>系统设置 - <?php echo htmlspecialchars($settings['site_name'] ?? '授权管理后台'); ?></title>
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
        .card h2 { margin-top: 0; color: #333; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-primary { background-color: #1E9FFF; color: white; }
        .btn:hover { opacity: 0.9; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="number"], .form-group input[type="password"] { width: 100%; max-width: 500px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group input[type="checkbox"] { margin-right: 8px; }
        .form-group .hint { color: #666; font-size: 12px; margin-top: 5px; }
        .form-actions { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 16px; color: #1E9FFF; margin-bottom: 15px; font-weight: bold; }
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
                <li><a href="/blacklist.php">黑名单管理</a></li>
                <li class="active"><a href="/settings.php">系统设置</a></li>
            </ul>
        </div>
        <div class="content">
            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>系统设置</h2>
                <form method="post">
                    <div class="section">
                        <div class="section-title">基本设置</div>
                        <div class="form-group">
                            <label>站点名称</label>
                            <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? '授权管理后台'); ?>">
                            <div class="hint">显示在页面标题和头部</div>
                        </div>
                        <div class="form-group">
                            <label>管理员邮箱</label>
                            <input type="email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>">
                            <div class="hint">用于接收系统通知</div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">授权设置</div>
                        <div class="form-group">
                            <label>默认授权天数</label>
                            <input type="number" name="license_expire_days" value="<?php echo htmlspecialchars($settings['license_expire_days'] ?? 365); ?>">
                            <div class="hint">新创建授权的默认有效天数</div>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="auto_renew" <?php echo ($settings['auto_renew'] ?? false) ? 'checked' : ''; ?>>
                                启用自动续期
                            </label>
                            <div class="hint">授权到期前自动续期</div>
                        </div>
                        <div class="form-group">
                            <label>到期提醒天数</label>
                            <input type="number" name="notify_before_expire" value="<?php echo htmlspecialchars($settings['notify_before_expire'] ?? 7); ?>">
                            <div class="hint">授权到期前多少天发送提醒</div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">API设置</div>
                        <div class="form-group">
                            <label>API Key</label>
                            <input type="text" name="api_key" value="<?php echo htmlspecialchars($settings['api_key'] ?? ''); ?>" placeholder="用于客户端API验证">
                            <div class="hint">客户端调用API时需要提供的密钥</div>
                        </div>
                        <div class="form-group">
                            <label>Secret Key</label>
                            <input type="password" name="secret_key" value="<?php echo htmlspecialchars($settings['secret_key'] ?? ''); ?>" placeholder="用于签名验证">
                            <div class="hint">用于生成和验证签名的密钥</div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">密码管理</div>
                        <div style="margin-top: 15px;">
                            <input type="hidden" name="action" value="change_password" id="password_action">
                            <div class="form-group">
                                <label>当前密码</label>
                                <input type="password" name="current_password" placeholder="请输入当前密码">
                            </div>
                            <div class="form-group">
                                <label>新密码</label>
                                <input type="password" name="new_password" placeholder="请输入新密码（至少6位）">
                                <div class="hint">密码长度不能少于6位</div>
                            </div>
                            <div class="form-group">
                                <label>确认新密码</label>
                                <input type="password" name="confirm_password" placeholder="请再次输入新密码">
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-primary" onclick="submitPasswordForm()">修改密码</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">保存设置</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function submitPasswordForm() {
            var currentPassword = document.querySelector('input[name="current_password"]').value;
            var newPassword = document.querySelector('input[name="new_password"]').value;
            var confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            // 验证输入
            if (!currentPassword) {
                alert('请输入当前密码');
                return;
            }
            if (!newPassword) {
                alert('请输入新密码');
                return;
            }
            if (newPassword.length < 6) {
                alert('新密码长度不能少于6位');
                return;
            }
            if (newPassword != confirmPassword) {
                alert('两次输入的密码不一致');
                return;
            }
            
            // 设置action并提交表单
            document.getElementById('password_action').value = 'change_password';
            document.querySelector('form').submit();
        }
        
        // 保存设置时清除password_action
        document.querySelector('button[type="submit"]').onclick = function() {
            document.getElementById('password_action').value = '';
        };
    </script>
</body>
</html>