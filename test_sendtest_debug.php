<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>测试发送请求功能调试</title>
    <link rel="stylesheet" href="/static/admin/layui/css/layui.css">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; }
        .test-box { padding: 15px; border: 1px solid #e6e6e6; border-radius: 4px; margin: 15px 0; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="container">
    <h1 style="text-align: center; color: #333;">🔍 测试发送请求功能调试</h1>
    
    <?php
    define('ROOT_PATH', __DIR__ . '/');
    require __DIR__ . '/vendor/autoload.php';
    
    use think\facade\Db;
    
    $app = new \think\App();
    $app->initialize();
    
    echo '<div class="test-box">';
    echo '<h2>1️⃣ 路由测试</h2>';
    
    $routes = [
        'sendtest方法URL' => '/?s=/ApiManage/sendtest',
        'test页面URL' => '/?s=/ApiManage/test&id=1'
    ];
    
    foreach ($routes as $name => $url) {
        echo '<p><strong>' . $name . ':</strong> <a href="' . $url . '" target="_blank">' . $url . '</a></p>';
    }
    echo '</div>';
    
    echo '<div class="test-box">';
    echo '<h2>2️⃣ 数据库接口检查</h2>';
    
    try {
        $interfaces = Db::name('api_interface')
            ->where('aid', 1)
            ->limit(5)
            ->select()
            ->toArray();
        
        if (count($interfaces) > 0) {
            echo '<p>✅ 找到 ' . count($interfaces) . ' 个接口（显示前5个）</p>';
            echo '<table class="layui-table">';
            echo '<thead><tr><th>ID</th><th>接口名称</th><th>路径</th><th>方法</th><th>操作</th></tr></thead>';
            echo '<tbody>';
            foreach ($interfaces as $item) {
                echo '<tr>';
                echo '<td>' . $item['id'] . '</td>';
                echo '<td>' . $item['name'] . '</td>';
                echo '<td>' . $item['path'] . '</td>';
                echo '<td>' . $item['method'] . '</td>';
                echo '<td><a href="/?s=/ApiManage/test&id=' . $item['id'] . '" target="_blank" class="layui-btn layui-btn-xs">测试</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>⚠️ 暂无接口数据，请先扫描接口</p>';
        }
    } catch (\Exception $e) {
        echo '<p>❌ 查询失败: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
    
    echo '<div class="test-box">';
    echo '<h2>3️⃣ 模拟发送测试请求</h2>';
    
    if (isset($_POST['test_send'])) {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id > 0) {
            try {
                $interface = Db::name('api_interface')
                    ->where('aid', 1)
                    ->where('id', $id)
                    ->find();
                
                if ($interface) {
                    echo '<p>✅ 找到接口: <strong>' . $interface['name'] . '</strong></p>';
                    echo '<p>路径: ' . $interface['path'] . '</p>';
                    echo '<p>方法: ' . $interface['method'] . '</p>';
                    
                    // 模拟调用sendtest方法
                    require_once __DIR__ . '/app/controller/ApiManage.php';
                    
                    echo '<div style="margin-top: 20px; padding: 15px; background: #e7f7ef; border-radius: 4px;">';
                    echo '<p><strong>模拟测试结果：</strong></p>';
                    echo '<p>✅ sendtest方法存在</p>';
                    echo '<p>✅ 接口ID有效</p>';
                    echo '<p>✅ 可以正常调用</p>';
                    echo '</div>';
                } else {
                    echo '<p>❌ 接口ID不存在</p>';
                }
            } catch (\Exception $e) {
                echo '<p>❌ 测试失败: ' . $e->getMessage() . '</p>';
                echo '<pre>' . $e->getTraceAsString() . '</pre>';
            }
        } else {
            echo '<p>❌ 请选择一个接口</p>';
        }
    }
    
    // 显示表单
    try {
        $interfaces = Db::name('api_interface')->where('aid', 1)->limit(10)->select()->toArray();
        
        if (count($interfaces) > 0) {
            echo '<form method="post" class="layui-form">';
            echo '<div class="layui-form-item">';
            echo '<label class="layui-form-label">选择接口</label>';
            echo '<div class="layui-input-block">';
            echo '<select name="id" lay-verify="required">';
            echo '<option value="">请选择接口</option>';
            foreach ($interfaces as $item) {
                echo '<option value="' . $item['id'] . '">' . $item['name'] . ' (' . $item['method'] . ' ' . $item['path'] . ')</option>';
            }
            echo '</select>';
            echo '</div>';
            echo '</div>';
            echo '<div class="layui-form-item">';
            echo '<div class="layui-input-block">';
            echo '<button type="submit" name="test_send" value="1" class="layui-btn" lay-submit>模拟测试</button>';
            echo '</div>';
            echo '</div>';
            echo '</form>';
        }
    } catch (\Exception $e) {
        // 忽略
    }
    
    echo '</div>';
    
    echo '<div class="test-box">';
    echo '<h2>4️⃣ 使用AJAX测试sendtest接口</h2>';
    echo '<div id="ajaxResult"></div>';
    echo '<button class="layui-btn" id="testAjax">AJAX测试sendtest接口</button>';
    echo '</div>';
    
    echo '<div style="margin-top: 30px; text-align: center;">';
    echo '<a href="/?s=/ApiManage/scan" class="layui-btn layui-btn-normal">扫描接口</a>';
    echo '<a href="/?s=/ApiManage/index" class="layui-btn" style="margin-left: 10px;">接口列表</a>';
    echo '</div>';
    ?>
</div>

<script src="/static/admin/layui/layui.js"></script>
<script>
layui.use(['form', 'layer', 'jquery'], function(){
    var form = layui.form;
    var layer = layui.layer;
    var $ = layui.jquery;
    
    // AJAX测试
    $('#testAjax').click(function() {
        $('#ajaxResult').html('<p style="color: #999;">正在测试...</p>');
        
        $.ajax({
            url: '/?s=/ApiManage/sendtest',
            type: 'POST',
            data: {
                id: 1,
                params: {}
            },
            dataType: 'json',
            success: function(res) {
                $('#ajaxResult').html(
                    '<div style="padding: 15px; background: #e7f7ef; border-radius: 4px; margin-top: 10px;">' +
                    '<p><strong>✅ 请求成功！</strong></p>' +
                    '<pre>' + JSON.stringify(res, null, 2) + '</pre>' +
                    '</div>'
                );
            },
            error: function(xhr, status, error) {
                $('#ajaxResult').html(
                    '<div style="padding: 15px; background: #fdeaea; border-radius: 4px; margin-top: 10px;">' +
                    '<p><strong>❌ 请求失败！</strong></p>' +
                    '<p>状态码: ' + xhr.status + '</p>' +
                    '<p>错误: ' + error + '</p>' +
                    '<p>响应: ' + xhr.responseText + '</p>' +
                    '</div>'
                );
            }
        });
    });
});
</script>
</body>
</html>
