<?php
// 独立域名专用的登录页面
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username == 'admin' && $password == 'admin123456') {
        session_start();
        $_SESSION['sysadmin_admin_id'] = 1;
        $_SESSION['sysadmin_admin_name'] = 'admin';
        
        header('Content-Type: application/json');
        echo json_encode(['code' => 1, 'msg' => '登录成功', 'url' => '/lp_dashboard.php']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['code' => 0, 'msg' => '用户名或密码错误']);
    }
    exit;
}

session_start();
if (isset($_SESSION['sysadmin_admin_id'])) {
    header('Location: /lp_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>授权管理后台登录</title>
    <style>
        body {
            background-color: #f2f2f2;
            font-family: Arial, sans-serif;
        }
        .login-container {
            width: 400px;
            margin: 100px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 30px;
            color: #333;
        }
        .form-item {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #666;
        }
        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-input:focus {
            outline: none;
            border-color: #1E9FFF;
            box-shadow: 0 0 0 2px rgba(30, 159, 255, 0.2);
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: #1E9FFF;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .login-btn:hover {
            background-color: #0C84FF;
        }
        .error-message {
            color: #FF5722;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">授权管理后台</h2>
        <form method="post" onsubmit="return submitForm();">
            <div class="form-item">
                <label class="form-label">用户名</label>
                <input type="text" name="username" class="form-input" placeholder="请输入用户名" required>
            </div>
            <div class="form-item">
                <label class="form-label">密码</label>
                <input type="password" name="password" class="form-input" placeholder="请输入密码" required>
            </div>
            <div class="form-item">
                <button type="submit" class="login-btn">登录</button>
            </div>
        </form>
        <div id="error-message" class="error-message" style="display: none;"></div>
    </div>
    <script>
        function submitForm() {
            var form = document.querySelector('form');
            var formData = new FormData(form);
            
            fetch('/lp_login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.code == 1) {
                    window.location.href = data.url;
                } else {
                    var errorElement = document.getElementById('error-message');
                    errorElement.textContent = data.msg;
                    errorElement.style.display = 'block';
                }
            })
            .catch(error => {
                var errorElement = document.getElementById('error-message');
                errorElement.textContent = '登录失败，请稍后重试';
                errorElement.style.display = 'block';
            });
            
            return false;
        }
    </script>
</body>
</html>