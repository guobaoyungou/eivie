<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>API管理功能完整测试</title>
    <link rel="stylesheet" href="/static/admin/layui/css/layui.css">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .test-section { background: #fff; padding: 20px; margin-bottom: 15px; border-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .test-item { padding: 10px; border-bottom: 1px solid #f0f0f0; }
        .test-item:last-child { border-bottom: none; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; }
        .status.ok { background: #e7f7ef; color: #27ae60; }
        .status.error { background: #fdeaea; color: #e74c3c; }
        .status.pending { background: #f0f7ff; color: #1E9FFF; }
    </style>
</head>
<body>
<div class="container">
    <h1 style="text-align: center; color: #333;">🔧 API管理功能完整测试</h1>
    
    <?php
    define('ROOT_PATH', __DIR__ . '/');
    require __DIR__ . '/vendor/autoload.php';
    
    use think\facade\Db;
    use app\service\ApiManageService;
    
    $app = new \think\App();
    $app->initialize();
    
    $results = [];
    
    // 测试1: 数据库表检查
    echo '<div class="test-section">';
    echo '<h2>1️⃣ 数据库表检查</h2>';
    try {
        $tableCount = Db::name('api_interface')->count();
        $logCount = Db::name('api_test_log')->count();
        echo '<div class="test-item">✅ ddwx_api_interface 表存在，记录数: ' . $tableCount . '</div>';
        echo '<div class="test-item">✅ ddwx_api_test_log 表存在，记录数: ' . $logCount . '</div>';
        $results['database'] = true;
    } catch (\Exception $e) {
        echo '<div class="test-item">❌ 数据库表检查失败: ' . $e->getMessage() . '</div>';
        $results['database'] = false;
    }
    echo '</div>';
    
    // 测试2: 控制器扫描功能
    echo '<div class="test-section">';
    echo '<h2>2️⃣ 控制器扫描功能</h2>';
    try {
        $service = new ApiManageService();
        $controllers = $service->getControllersForScan();
        echo '<div class="test-item">✅ 成功扫描到 ' . count($controllers) . ' 个控制器</div>';
        if (count($controllers) > 0) {
            echo '<div class="test-item">示例: ' . $controllers[0]['name'] . '</div>';
        }
        $results['scan_controllers'] = true;
    } catch (\Exception $e) {
        echo '<div class="test-item">❌ 控制器扫描失败: ' . $e->getMessage() . '</div>';
        $results['scan_controllers'] = false;
    }
    echo '</div>';
    
    // 测试3: 接口扫描功能
    echo '<div class="test-section">';
    echo '<h2>3️⃣ 接口扫描功能</h2>';
    try {
        $service = new ApiManageService();
        $result = $service->scanInterfaces(1, 'all', ['ApiManage']);
        if ($result['status'] == 1) {
            echo '<div class="test-item">✅ 接口扫描成功</div>';
            echo '<div class="test-item">新增接口: ' . $result['data']['new_count'] . ' 个</div>';
            echo '<div class="test-item">更新接口: ' . $result['data']['update_count'] . ' 个</div>';
            $results['scan_interfaces'] = true;
        } else {
            echo '<div class="test-item">❌ 接口扫描失败: ' . $result['msg'] . '</div>';
            $results['scan_interfaces'] = false;
        }
    } catch (\Exception $e) {
        echo '<div class="test-item">❌ 接口扫描异常: ' . $e->getMessage() . '</div>';
        $results['scan_interfaces'] = false;
    }
    echo '</div>';
    
    // 测试4: 接口保存功能
    echo '<div class="test-section">';
    echo '<h2>4️⃣ 接口保存功能</h2>';
    try {
        $testData = [
            [
                'controller' => 'TestController',
                'action' => 'testMethod_' . time(),
                'name' => '测试接口_' . date('H:i:s'),
                'category' => '自动化测试',
                'method' => 'POST',
                'path' => '/test/method_' . time(),
                'description' => '这是一个自动化测试接口',
                'auth_required' => 0,
                'status' => 1,
                'tags' => 'test',
                'sort' => 0
            ]
        ];
        
        $service = new ApiManageService();
        $result = $service->saveScanResults(1, $testData);
        
        if ($result['status'] == 1) {
            echo '<div class="test-item">✅ 接口保存成功: ' . $result['msg'] . '</div>';
            $results['save'] = true;
        } else {
            echo '<div class="test-item">❌ 接口保存失败: ' . $result['msg'] . '</div>';
            $results['save'] = false;
        }
    } catch (\Exception $e) {
        echo '<div class="test-item">❌ 接口保存异常: ' . $e->getMessage() . '</div>';
        $results['save'] = false;
    }
    echo '</div>';
    
    // 测试5: 接口列表查询
    echo '<div class="test-section">';
    echo '<h2>5️⃣ 接口列表查询</h2>';
    try {
        $service = new ApiManageService();
        $result = $service->getInterfaceList(1, 1, 10, []);
        
        if ($result['status'] == 1) {
            echo '<div class="test-item">✅ 接口列表查询成功</div>';
            echo '<div class="test-item">总记录数: ' . $result['count'] . '</div>';
            echo '<div class="test-item">当前页记录: ' . count($result['data']) . ' 个</div>';
            $results['list'] = true;
        } else {
            echo '<div class="test-item">❌ 接口列表查询失败: ' . $result['msg'] . '</div>';
            $results['list'] = false;
        }
    } catch (\Exception $e) {
        echo '<div class="test-item">❌ 接口列表查询异常: ' . $e->getMessage() . '</div>';
        $results['list'] = false;
    }
    echo '</div>';
    
    // 测试6: 分类列表
    echo '<div class="test-section">';
    echo '<h2>6️⃣ 分类列表</h2>';
    try {
        $service = new ApiManageService();
        $categories = $service->getCategories(1);
        echo '<div class="test-item">✅ 获取到 ' . count($categories) . ' 个分类</div>';
        if (count($categories) > 0) {
            echo '<div class="test-item">分类: ' . implode(', ', $categories) . '</div>';
        }
        $results['categories'] = true;
    } catch (\Exception $e) {
        echo '<div class="test-item">❌ 分类列表获取失败: ' . $e->getMessage() . '</div>';
        $results['categories'] = false;
    }
    echo '</div>';
    
    // 测试7: 视图文件检查
    echo '<div class="test-section">';
    echo '<h2>7️⃣ 视图文件检查</h2>';
    $viewFiles = [
        'scan.html' => '/www/wwwroot/eivie/app/view/api_manage/scan.html',
        'index.html' => '/www/wwwroot/eivie/app/view/api_manage/index.html',
        'detail.html' => '/www/wwwroot/eivie/app/view/api_manage/detail.html',
        'test.html' => '/www/wwwroot/eivie/app/view/api_manage/test.html',
        'testlog.html' => '/www/wwwroot/eivie/app/view/api_manage/testlog.html'
    ];
    
    $allViewsOk = true;
    foreach ($viewFiles as $name => $path) {
        if (file_exists($path)) {
            echo '<div class="test-item">✅ ' . $name . ' 存在</div>';
        } else {
            echo '<div class="test-item">❌ ' . $name . ' 不存在</div>';
            $allViewsOk = false;
        }
    }
    $results['views'] = $allViewsOk;
    echo '</div>';
    
    // 测试8: 路由检查
    echo '<div class="test-section">';
    echo '<h2>8️⃣ 路由访问测试</h2>';
    $routes = [
        '接口列表' => '/?s=/ApiManage/index',
        '接口扫描' => '/?s=/ApiManage/scan',
        '测试日志' => '/?s=/ApiManage/testlog'
    ];
    
    foreach ($routes as $name => $url) {
        echo '<div class="test-item">📍 ' . $name . ': <a href="' . $url . '" target="_blank">' . $url . '</a></div>';
    }
    echo '</div>';
    
    // 总结
    echo '<div class="test-section">';
    echo '<h2>📊 测试总结</h2>';
    $totalTests = count($results);
    $passedTests = count(array_filter($results));
    $failedTests = $totalTests - $passedTests;
    
    echo '<div class="layui-progress layui-progress-big" lay-showpercent="true">';
    $percent = round(($passedTests / $totalTests) * 100);
    echo '<div class="layui-progress-bar" lay-percent="' . $percent . '%"></div>';
    echo '</div>';
    
    echo '<div style="margin-top: 20px; text-align: center; font-size: 16px;">';
    echo '<p><strong>总测试项: ' . $totalTests . '</strong></p>';
    echo '<p style="color: #27ae60;"><strong>通过: ' . $passedTests . '</strong></p>';
    if ($failedTests > 0) {
        echo '<p style="color: #e74c3c;"><strong>失败: ' . $failedTests . '</strong></p>';
    }
    
    if ($passedTests == $totalTests) {
        echo '<p style="color: #27ae60; font-size: 20px; margin-top: 20px;">🎉 所有测试通过！API管理功能正常！</p>';
    } else {
        echo '<p style="color: #f39c12; font-size: 18px; margin-top: 20px;">⚠️ 部分功能需要修复</p>';
    }
    echo '</div>';
    
    echo '<div style="margin-top: 30px; text-align: center;">';
    echo '<a href="/?s=/ApiManage/index" class="layui-btn layui-btn-lg">访问接口列表</a>';
    echo '<a href="/?s=/ApiManage/scan" class="layui-btn layui-btn-normal layui-btn-lg" style="margin-left: 10px;">访问扫描页面</a>';
    echo '<button onclick="location.reload()" class="layui-btn layui-btn-primary layui-btn-lg" style="margin-left: 10px;">重新测试</button>';
    echo '</div>';
    echo '</div>';
    ?>
</div>

<script src="/static/admin/layui/layui.js"></script>
<script>
layui.use(['element'], function(){
    var element = layui.element;
});
</script>
</body>
</html>
