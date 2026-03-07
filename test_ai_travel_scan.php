<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>AI旅拍API扫描测试</title>
    <link rel="stylesheet" href="/static/admin/layui/css/layui.css">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; }
        .result-item { padding: 15px; border: 1px solid #e6e6e6; border-radius: 4px; margin-bottom: 10px; background: #fafafa; }
        .controller-name { font-weight: bold; color: #1E9FFF; font-size: 16px; margin-bottom: 10px; }
        .api-item { padding: 8px; background: #fff; border-left: 3px solid #5FB878; margin: 5px 0; }
        .api-method { display: inline-block; padding: 2px 8px; background: #FFB800; color: #fff; border-radius: 3px; font-size: 12px; margin-right: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1 style="text-align: center; color: #333;">🔍 AI旅拍API扫描测试</h1>
    
    <?php
    define('ROOT_PATH', __DIR__ . '/');
    require __DIR__ . '/vendor/autoload.php';
    
    use app\service\ApiManageService;
    
    $app = new \think\App();
    $app->initialize();
    
    echo '<div style="margin: 30px 0;">';
    echo '<h2>1️⃣ 扫描控制器列表</h2>';
    
    try {
        $service = new ApiManageService();
        $controllers = $service->getControllersForScan();
        
        // 筛选出旅拍相关的控制器
        $aiTravelControllers = array_filter($controllers, function($item) {
            return strpos($item['name'], 'AiTravelPhoto') === 0;
        });
        
        echo '<p>✅ 总控制器数: ' . count($controllers) . '</p>';
        echo '<p>✅ AI旅拍控制器数: ' . count($aiTravelControllers) . '</p>';
        
        if (count($aiTravelControllers) > 0) {
            echo '<div style="margin-top: 20px;">';
            echo '<table class="layui-table">';
            echo '<thead><tr><th>序号</th><th>控制器名称</th><th>完整类名</th></tr></thead>';
            echo '<tbody>';
            $index = 1;
            foreach ($aiTravelControllers as $ctrl) {
                echo '<tr>';
                echo '<td>' . $index++ . '</td>';
                echo '<td><strong>' . $ctrl['name'] . '</strong></td>';
                echo '<td style="font-size: 12px; color: #666;">' . $ctrl['class'] . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        } else {
            echo '<div class="layui-alert layui-alert-warning">⚠️ 未找到AI旅拍相关控制器</div>';
        }
        
    } catch (\Exception $e) {
        echo '<div class="layui-alert layui-alert-danger">❌ 扫描失败: ' . $e->getMessage() . '</div>';
    }
    
    echo '</div>';
    
    echo '<div style="margin: 30px 0;">';
    echo '<h2>2️⃣ 扫描AI旅拍接口</h2>';
    
    try {
        $service = new ApiManageService();
        
        // 扫描所有AI旅拍控制器
        $aiTravelControllerNames = array_map(function($item) {
            return $item['name'];
        }, $aiTravelControllers);
        
        $result = $service->scanInterfaces(1, 'all', $aiTravelControllerNames);
        
        if ($result['status'] == 1) {
            echo '<p>✅ 扫描成功！</p>';
            echo '<p>新增接口: <strong style="color: #5FB878;">' . $result['data']['new_count'] . '</strong> 个</p>';
            echo '<p>更新接口: <strong style="color: #FFB800;">' . $result['data']['update_count'] . '</strong> 个</p>';
            
            // 按控制器分组显示
            if ($result['data']['new_count'] > 0) {
                echo '<div style="margin-top: 20px;">';
                echo '<h3>新增接口详情：</h3>';
                
                $groupedByController = [];
                foreach ($result['data']['new'] as $api) {
                    $controller = $api['controller'];
                    if (!isset($groupedByController[$controller])) {
                        $groupedByController[$controller] = [];
                    }
                    $groupedByController[$controller][] = $api;
                }
                
                foreach ($groupedByController as $controller => $apis) {
                    echo '<div class="result-item">';
                    echo '<div class="controller-name">📦 ' . $controller . ' (' . count($apis) . ' 个接口)</div>';
                    
                    foreach ($apis as $api) {
                        echo '<div class="api-item">';
                        echo '<span class="api-method">' . $api['method'] . '</span>';
                        echo '<strong>' . $api['name'] . '</strong>';
                        echo '<span style="margin-left: 15px; color: #999; font-size: 12px;">' . $api['path'] . '</span>';
                        if ($api['category']) {
                            echo '<span style="margin-left: 10px; padding: 2px 8px; background: #1E9FFF; color: #fff; border-radius: 3px; font-size: 11px;">' . $api['category'] . '</span>';
                        }
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                
                echo '</div>';
            }
            
        } else {
            echo '<div class="layui-alert layui-alert-danger">❌ 扫描失败: ' . $result['msg'] . '</div>';
        }
        
    } catch (\Exception $e) {
        echo '<div class="layui-alert layui-alert-danger">❌ 扫描异常: ' . $e->getMessage() . '</div>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    }
    
    echo '</div>';
    
    echo '<div style="margin: 30px 0; text-align: center;">';
    echo '<a href="/?s=/ApiManage/scan" class="layui-btn layui-btn-lg layui-btn-normal">前往扫描页面</a>';
    echo '<a href="/?s=/ApiManage/index" class="layui-btn layui-btn-lg" style="margin-left: 10px;">查看接口列表</a>';
    echo '<button onclick="location.reload()" class="layui-btn layui-btn-lg layui-btn-primary" style="margin-left: 10px;">刷新测试</button>';
    echo '</div>';
    ?>
</div>

<script src="/static/admin/layui/layui.js"></script>
</body>
</html>
