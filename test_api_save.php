<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>API保存功能调试</title>
    <link rel="stylesheet" href="/static/admin/layui/css/layui.css">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .test-card { background: #fff; padding: 20px; margin-bottom: 15px; border-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .result-box { margin-top: 15px; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; white-space: pre-wrap; word-wrap: break-word; }
        .result-box.success { background: #e7f7ef; color: #27ae60; border: 1px solid #27ae60; }
        .result-box.error { background: #fdeaea; color: #e74c3c; border: 1px solid #e74c3c; }
        .result-box.info { background: #f0f7ff; color: #1E9FFF; border: 1px solid #d9ecff; }
    </style>
</head>
<body>
<div class="container">
    <div class="test-card">
        <h2 style="margin: 0 0 20px 0; color: #333;">🔧 API保存功能调试工具</h2>
        
        <?php
        // 定义必要的常量
        define('ROOT_PATH', __DIR__ . '/');
        
        // 在文件顶部引入命名空间
        require __DIR__ . '/vendor/autoload.php';
        
        use think\facade\Db;
        use app\service\ApiManageService;
        
        // 检查是否是测试保存请求
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'test_save') {
            $app = new \think\App();
            $app->initialize();
            
            echo '<div class="result-box info">📝 开始测试保存功能...</div>';
            
            // 测试数据
            $testInterfaces = [
                [
                    'controller' => 'TestController',
                    'action' => 'testAction_' . time(),
                    'name' => '测试接口_' . date('Y-m-d H:i:s'),
                    'category' => '测试分类',
                    'method' => 'POST',
                    'path' => '/test/action_' . time(),
                    'description' => '这是一个自动化测试接口',
                    'auth_required' => 0,
                    'request_params' => '',
                    'response_example' => '',
                    'tags' => 'test',
                    'sort' => 0,
                    'status' => 1
                ]
            ];
            
            $aid = 1; // 默认测试aid
            
            echo '<div class="result-box info">测试数据：<br>' . json_encode($testInterfaces[0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</div>';
            
            try {
                $service = new ApiManageService();
                $result = $service->saveScanResults($aid, $testInterfaces);
                
                if ($result['status'] == 1) {
                    echo '<div class="result-box success">✅ 保存成功！<br>' . $result['msg'] . '</div>';
                    
                    // 查询刚保存的数据
                    $saved = Db::name('api_interface')
                        ->where('controller', 'TestController')
                        ->order('id desc')
                        ->find();
                    
                    if ($saved) {
                        echo '<div class="result-box success">✅ 验证成功，已查询到保存的数据：<br>' . 
                             json_encode($saved, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</div>';
                    }
                } else {
                    echo '<div class="result-box error">❌ 保存失败！<br>' . $result['msg'] . '</div>';
                }
                
            } catch (\Exception $e) {
                echo '<div class="result-box error">❌ 发生异常：<br>';
                echo '错误信息: ' . $e->getMessage() . '<br>';
                echo '错误文件: ' . $e->getFile() . '<br>';
                echo '错误行号: ' . $e->getLine() . '<br>';
                echo '错误堆栈:<br>' . str_replace("\n", '<br>', $e->getTraceAsString());
                echo '</div>';
            }
            
            // 查看最新日志
            $logFile = __DIR__ . '/runtime/log/' . date('Ym') . '/' . date('d') . '.log';
            if (file_exists($logFile)) {
                $logs = file_get_contents($logFile);
                $recentLogs = array_slice(explode("\n", $logs), -30);
                echo '<div class="result-box info">📋 最近30条日志：<br>' . implode('<br>', $recentLogs) . '</div>';
            }
            
            echo '<div style="margin-top: 20px;"><a href="?" class="layui-btn">返回测试页</a></div>';
            exit;
        }
        ?>
        
        <div class="layui-form">
            <div class="layui-form-item">
                <label class="layui-form-label">功能说明</label>
                <div class="layui-input-block">
                    <div class="layui-card">
                        <div class="layui-card-body">
                            <p style="line-height: 2; color: #666;">
                                ✓ 此工具用于诊断API接口保存功能<br>
                                ✓ 点击下方按钮将测试保存一个接口到数据库<br>
                                ✓ 会显示详细的执行过程和错误信息<br>
                                ✓ 包含数据库连接、SQL执行、日志查看等完整流程
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="test_save">
                        <button type="submit" class="layui-btn layui-btn-normal layui-btn-lg">
                            <i class="layui-icon layui-icon-release"></i> 开始测试保存功能
                        </button>
                    </form>
                    
                    <a href="?s=/ApiManage/scan" class="layui-btn layui-btn-primary layui-btn-lg" style="margin-left: 10px;">
                        <i class="layui-icon layui-icon-return"></i> 返回扫描页面
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="test-card">
        <h3 style="margin: 0 0 15px 0; color: #666;">📊 数据库状态检查</h3>
        
        <?php
        try {
            // 定义常量（如果还没定义）
            if (!defined('ROOT_PATH')) {
                define('ROOT_PATH', __DIR__ . '/');
            }
            
            if (!class_exists('\\think\\App')) {
                require __DIR__ . '/vendor/autoload.php';
                $app = new \think\App();
                $app->initialize();
            }
            
            echo '<table class="layui-table">';
            echo '<thead><tr><th>检查项</th><th>状态</th><th>详情</th></tr></thead>';
            echo '<tbody>';
            
            // 检查 api_interface 表
            try {
                $count = Db::name('api_interface')->count();
                echo '<tr><td>api_interface 表</td><td><span style="color: #27ae60;">✓ 存在</span></td><td>当前记录数: ' . $count . '</td></tr>';
            } catch (\Exception $e) {
                echo '<tr><td>api_interface 表</td><td><span style="color: #e74c3c;">✗ 异常</span></td><td>' . $e->getMessage() . '</td></tr>';
            }
            
            // 检查 api_test_log 表
            try {
                $count = Db::name('api_test_log')->count();
                echo '<tr><td>api_test_log 表</td><td><span style="color: #27ae60;">✓ 存在</span></td><td>当前记录数: ' . $count . '</td></tr>';
            } catch (\Exception $e) {
                echo '<tr><td>api_test_log 表</td><td><span style="color: #e74c3c;">✗ 异常</span></td><td>' . $e->getMessage() . '</td></tr>';
            }
            
            // 检查日志文件
            $logFile = __DIR__ . '/runtime/log/' . date('Ym') . '/' . date('d') . '.log';
            if (file_exists($logFile)) {
                echo '<tr><td>日志文件</td><td><span style="color: #27ae60;">✓ 存在</span></td><td>' . $logFile . '</td></tr>';
            } else {
                echo '<tr><td>日志文件</td><td><span style="color: #f39c12;">⚠ 不存在</span></td><td>将自动创建</td></tr>';
            }
            
            echo '</tbody></table>';
            
        } catch (\Exception $e) {
            echo '<div class="result-box error">数据库连接失败: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</div>

<script src="/static/admin/layui/layui.js"></script>
</body>
</html>
