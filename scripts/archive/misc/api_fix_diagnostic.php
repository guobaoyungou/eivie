<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>API管理 - 问题诊断和修复</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #1E9FFF; padding-bottom: 10px; }
        h2 { color: #1E9FFF; margin-top: 30px; }
        .status { padding: 10px 15px; margin: 10px 0; border-radius: 4px; font-weight: bold; }
        .status.ok { background: #e7f7ef; color: #27ae60; border-left: 4px solid #27ae60; }
        .status.error { background: #fdeaea; color: #e74c3c; border-left: 4px solid #e74c3c; }
        .status.warning { background: #fff3cd; color: #f39c12; border-left: 4px solid #f39c12; }
        .code-block { background: #f8f8f8; padding: 15px; border-radius: 4px; border: 1px solid #ddd; overflow-x: auto; margin: 10px 0; }
        .code-block code { font-family: 'Courier New', monospace; font-size: 13px; }
        .btn { display: inline-block; padding: 12px 24px; background: #1E9FFF; color: #fff; text-decoration: none; border-radius: 4px; margin: 10px 5px 10px 0; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #0c7cd5; }
        .btn.success { background: #27ae60; }
        .btn.success:hover { background: #229954; }
        .section { margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 4px; }
        pre { margin: 0; white-space: pre-wrap; word-wrap: break-word; }
        .test-result { margin: 15px 0; padding: 15px; border-radius: 4px; border: 1px solid #ddd; }
        .test-result h3 { margin-top: 0; color: #555; }
        ul { list-style: none; padding-left: 0; }
        ul li { padding: 8px 0; border-bottom: 1px solid #eee; }
        ul li:last-child { border-bottom: none; }
        .icon-ok::before { content: "✓ "; color: #27ae60; font-weight: bold; }
        .icon-error::before { content: "✗ "; color: #e74c3c; font-weight: bold; }
        .icon-warning::before { content: "⚠ "; color: #f39c12; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 API管理功能 - 问题诊断和修复</h1>
    
    <div class="section">
        <h2>📋 当前问题清单</h2>
        <ul>
            <li class="icon-ok">扫描功能正常 - 成功扫描110个接口</li>
            <li class="icon-ok">jQuery加载正常</li>
            <li class="icon-ok">单个控制器选择正常</li>
            <li class="icon-warning">扫描类型Radio - 功能正常但显示可能不清晰</li>
            <li class="icon-warning">全选Checkbox - 功能正常但可能不响应</li>
            <li class="icon-error">保存接口返回500错误 - <strong>数据库表可能不存在</strong></li>
        </ul>
    </div>

    <div class="section">
        <h2>🗃️ 数据库表检查</h2>
        
        <?php
        try {
            // 引入ThinkPHP
            require __DIR__ . '/vendor/autoload.php';
            $app = new \think\App();
            $app->initialize();
            
            use think\facade\Db;
            use think\facade\Config;
            
            echo '<div class="status ok">✓ ThinkPHP框架加载成功</div>';
            
            // 获取数据库配置
            $dbConfig = Config::get('database');
            $prefix = $dbConfig['connections']['mysql']['prefix'] ?? 'ddwx_';
            
            echo '<div class="status ok">✓ 数据库表前缀: ' . $prefix . '</div>';
            
            // 检查表是否存在
            $apiInterfaceTable = $prefix . 'api_interface';
            $apiTestLogTable = $prefix . 'api_test_log';
            
            $tables = Db::query("SHOW TABLES");
            $existingTables = array_column($tables, array_keys($tables[0])[0]);
            
            $interfaceExists = in_array($apiInterfaceTable, $existingTables);
            $testLogExists = in_array($apiTestLogTable, $existingTables);
            
            if ($interfaceExists) {
                $count = Db::name('api_interface')->count();
                echo '<div class="status ok icon-ok">接口表(' . $apiInterfaceTable . ')存在，当前有 ' . $count . ' 条记录</div>';
            } else {
                echo '<div class="status error icon-error">接口表(' . $apiInterfaceTable . ')不存在！这是导致500错误的原因！</div>';
            }
            
            if ($testLogExists) {
                $count = Db::name('api_test_log')->count();
                echo '<div class="status ok icon-ok">测试日志表(' . $apiTestLogTable . ')存在，当前有 ' . $count . ' 条记录</div>';
            } else {
                echo '<div class="status error icon-error">测试日志表(' . $apiTestLogTable . ')不存在！</div>';
            }
            
            // 如果表不存在，显示创建按钮
            if (!$interfaceExists || !$testLogExists) {
                echo '<div style="margin-top: 20px;">';
                echo '<h3>❌ 需要创建数据库表</h3>';
                echo '<p>请使用以下方法之一创建数据库表：</p>';
                echo '<div class="code-block">';
                echo '<strong>方法1：下载SQL文件手动导入</strong><br>';
                echo '<a href="api_tables.sql" download class="btn success">下载 SQL 建表脚本</a>';
                echo '<p style="margin-top: 10px; color: #666;">下载后在phpMyAdmin或命令行中导入该SQL文件</p>';
                echo '</div>';
                
                echo '<div class="code-block" style="margin-top: 15px;">';
                echo '<strong>方法2：命令行导入</strong><br>';
                echo '<pre>mysql -u用户名 -p密码 数据库名 < /www/wwwroot/eivie/api_tables.sql</pre>';
                echo '</div>';
                
                echo '<div class="code-block" style="margin-top: 15px;">';
                echo '<strong>方法3：在线执行SQL</strong><br>';
                echo '<form method="post" action="?action=create_tables" style="margin-top: 10px;">';
                echo '<button type="submit" class="btn success" onclick="return confirm(\'确定要创建数据库表吗？\')">🚀 立即创建数据库表</button>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
            }
            
        } catch (\Exception $e) {
            echo '<div class="status error">✗ 数据库连接失败: ' . $e->getMessage() . '</div>';
            echo '<div class="code-block"><pre>' . $e->getTraceAsString() . '</pre></div>';
        }
        
        // 处理创建表的请求
        if (isset($_GET['action']) && $_GET['action'] == 'create_tables') {
            try {
                $sql = file_get_contents(__DIR__ . '/api_tables.sql');
                
                // 移除注释和空行
                $sql = preg_replace('/--.*$/m', '', $sql);
                $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
                
                // 分割SQL语句
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        Db::execute($statement);
                    }
                }
                
                echo '<div class="status ok">✓ 数据库表创建成功！请刷新页面查看。</div>';
                echo '<script>setTimeout(function(){ location.href = "?"; }, 2000);</script>';
                
            } catch (\Exception $e) {
                echo '<div class="status error">✗ 创建表失败: ' . $e->getMessage() . '</div>';
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>🎨 前端问题修复</h2>
        
        <div class="test-result">
            <h3>1. Radio单选框显示问题</h3>
            <p><strong>状态：</strong> <span style="color: #27ae60;">✓ 已修复</span></p>
            <p><strong>修复方式：</strong> 增强CSS样式优先级，使用 !important 确保原生样式生效</p>
            <div class="code-block">
                <code>
.custom-radio input[type="radio"] {<br>
&nbsp;&nbsp;margin-right: 8px !important;<br>
&nbsp;&nbsp;opacity: 1 !important;<br>
&nbsp;&nbsp;position: relative !important;<br>
}
                </code>
            </div>
            <p><strong>操作建议：</strong> 请按 <kbd>Ctrl + F5</kbd> 强制刷新浏览器清除缓存</p>
        </div>

        <div class="test-result">
            <h3>2. Checkbox全选功能</h3>
            <p><strong>状态：</strong> <span style="color: #27ae60;">✓ 已修复</span></p>
            <p><strong>修复方式：</strong> 改用 <code>click</code> 事件替代 <code>change</code> 事件，提高兼容性</p>
            <div class="code-block">
                <code>
$('#selectAll').on('click', function() {<br>
&nbsp;&nbsp;var checked = this.checked;<br>
&nbsp;&nbsp;$('.controller-checkbox').prop('checked', checked);<br>
});
                </code>
            </div>
            <p><strong>操作建议：</strong> 请按 <kbd>Ctrl + F5</kbd> 强制刷新浏览器清除缓存</p>
        </div>
    </div>

    <div class="section">
        <h2>📝 完整测试步骤</h2>
        <ol style="list-style: decimal; padding-left: 20px;">
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>创建数据库表</strong><br>
                <span style="color: #666;">按照上方"数据库表检查"部分的指引创建表</span>
            </li>
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>清除浏览器缓存</strong><br>
                <span style="color: #666;">按 Ctrl+F5 或 Cmd+Shift+R 强制刷新</span>
            </li>
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>访问扫描页面</strong><br>
                <a href="?s=/ApiManage/scan" class="btn" target="_blank">打开接口扫描页面</a>
            </li>
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>测试Radio选择</strong><br>
                <span style="color: #666;">点击"全量扫描"和"增量扫描"，确认可以切换</span>
            </li>
            <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                <strong>测试全选功能</strong><br>
                <span style="color: #666;">点击"全选"复选框，确认所有控制器被选中</span>
            </li>
            <li style="padding: 10px 0;">
                <strong>测试扫描和保存</strong><br>
                <span style="color: #666;">选择控制器 → 开始扫描 → 保存接口到数据库</span>
            </li>
        </ol>
    </div>

    <div class="section">
        <h2>🔍 调试工具</h2>
        <p>如果问题仍然存在，请打开浏览器开发者工具（F12）查看：</p>
        <ul>
            <li class="icon-ok"><strong>Console标签</strong> - 查看JavaScript错误和日志</li>
            <li class="icon-ok"><strong>Network标签</strong> - 查看AJAX请求和响应</li>
            <li class="icon-ok"><strong>Elements标签</strong> - 检查HTML元素和CSS样式</li>
        </ul>
        
        <div style="margin-top: 20px;">
            <a href="?s=/ApiManage/index" class="btn">返回接口列表</a>
            <a href="?s=/ApiManage/scan" class="btn">前往扫描页面</a>
            <button class="btn" onclick="location.reload()">刷新本页</button>
        </div>
    </div>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e6e6e6; color: #999; font-size: 13px; text-align: center;">
        <p>API管理功能诊断工具 v1.0</p>
        <p>最后更新：2026-02-02</p>
    </div>
</div>
</body>
</html>
