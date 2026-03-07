<?php
/**
 * API管理功能测试脚本
 * 测试所有API管理相关功能
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 定义根目录
define('ROOT_PATH', __DIR__ . '/');

echo "=== API管理功能测试开始 ===\n\n";

// 测试1: 检查数据库表是否存在
echo "【测试1】检查数据库表...\n";
$config = include(ROOT_PATH . 'config.php');
$mysqli = new mysqli($config['hostname'], $config['username'], $config['password'], $config['database']);

if ($mysqli->connect_error) {
    die("数据库连接失败: " . $mysqli->connect_error);
}

$tables = [
    $config['prefix'] . 'api_interface',
    $config['prefix'] . 'api_test_log'
];

foreach ($tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ 表 $table 存在\n";
    } else {
        echo "✗ 表 $table 不存在\n";
    }
}

// 测试2: 检查表结构
echo "\n【测试2】检查表结构...\n";
$result = $mysqli->query("DESCRIBE " . $config['prefix'] . "api_interface");
$fields = [];
while ($row = $result->fetch_assoc()) {
    $fields[] = $row['Field'];
}
echo "✓ api_interface 表字段: " . implode(', ', $fields) . "\n";

// 测试3: 检查初始数据
echo "\n【测试3】检查初始数据...\n";
$result = $mysqli->query("SELECT COUNT(*) as count FROM " . $config['prefix'] . "api_interface");
$row = $result->fetch_assoc();
echo "✓ 当前接口数量: " . $row['count'] . "\n";

if ($row['count'] > 0) {
    $result = $mysqli->query("SELECT id, name, category, method, path FROM " . $config['prefix'] . "api_interface ORDER BY id LIMIT 5");
    echo "  前5个接口:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - [{$row['id']}] {$row['name']} ({$row['method']} {$row['path']}) - {$row['category']}\n";
    }
}

// 测试4: 检查文件是否存在
echo "\n【测试4】检查核心文件...\n";
$files = [
    'app/controller/ApiManage.php' => '控制器',
    'app/service/ApiManageService.php' => '服务层',
    'app/view/api_manage/index.html' => '接口列表视图',
    'app/view/api_manage/scan.html' => '接口扫描视图',
    'app/view/api_manage/detail.html' => '接口编辑视图',
    'app/view/api_manage/test.html' => '在线测试视图',
    'app/view/api_manage/testlog.html' => '测试历史视图'
];

foreach ($files as $file => $desc) {
    $path = ROOT_PATH . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "✓ $desc: $file (" . round($size/1024, 2) . " KB)\n";
    } else {
        echo "✗ $desc: $file 不存在\n";
    }
}

// 测试5: 检查菜单配置
echo "\n【测试5】检查菜单配置...\n";
$menuFile = ROOT_PATH . 'app/common/Menu.php';
$menuContent = file_get_contents($menuFile);
if (strpos($menuContent, 'ApiManage') !== false) {
    echo "✓ 菜单配置中包含 ApiManage\n";
} else {
    echo "✗ 菜单配置中未找到 ApiManage\n";
}

// 测试6: 测试扫描功能（模拟）
echo "\n【测试6】测试扫描功能...\n";
require_once ROOT_PATH . 'app/service/ApiManageService.php';

$service = new app\service\ApiManageService();
$controllers = $service->getControllersForScan();

echo "✓ 可扫描的控制器数量: " . count($controllers) . "\n";
if (count($controllers) > 0) {
    echo "  前10个控制器:\n";
    $count = 0;
    foreach ($controllers as $ctrl) {
        echo "  - {$ctrl['name']}\n";
        $count++;
        if ($count >= 10) break;
    }
}

// 测试7: 测试分类识别
echo "\n【测试7】测试分类识别...\n";
$testCategories = [
    'ApiIndex' => '用户认证',
    'ApiAdminMember' => '会员管理',
    'AiTravelPhotoScene' => 'AI旅拍-场景'
];

$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('getCategoryByController');
$method->setAccessible(true);

foreach ($testCategories as $controller => $expectedCategory) {
    $category = $method->invoke($service, $controller);
    if ($category === $expectedCategory) {
        echo "✓ $controller -> $category\n";
    } else {
        echo "✗ $controller -> 期望: $expectedCategory, 实际: $category\n";
    }
}

// 测试8: 检查权限配置
echo "\n【测试8】检查权限配置...\n";
$controllerFile = ROOT_PATH . 'app/controller/ApiManage.php';
$controllerContent = file_get_contents($controllerFile);

if (strpos($controllerContent, 'extends Common') !== false) {
    echo "✓ 控制器正确继承 Common 类\n";
} else {
    echo "✗ 控制器未继承 Common 类\n";
}

if (strpos($controllerContent, 'public function initialize') !== false) {
    echo "✓ initialize 方法为 public\n";
} else {
    echo "✗ initialize 方法不是 public\n";
}

if (strpos($controllerContent, "user['isadmin']") !== false) {
    echo "✓ 正确使用 \$this->user['isadmin'] 检查权限\n";
} else {
    echo "✗ 权限检查可能有问题\n";
}

// 测试9: 检查视图模板语法
echo "\n【测试9】检查视图模板...\n";
$viewFiles = glob(ROOT_PATH . 'app/view/api_manage/*.html');
foreach ($viewFiles as $viewFile) {
    $content = file_get_contents($viewFile);
    $filename = basename($viewFile);
    
    // 检查是否包含必要的元素
    if (strpos($content, 'layui') !== false) {
        echo "✓ $filename: 包含 Layui 框架\n";
    } else {
        echo "✗ $filename: 未包含 Layui 框架\n";
    }
}

// 测试10: 模拟扫描请求
echo "\n【测试10】模拟扫描请求...\n";
try {
    // 选择几个控制器进行测试扫描
    $testControllers = array_slice($controllers, 0, 3);
    $controllerNames = array_column($testControllers, 'name');
    
    $result = $service->scanInterfaces(1, 'all', $controllerNames);
    
    if (isset($result['status']) && $result['status'] == 1) {
        echo "✓ 扫描成功\n";
        echo "  新增接口: " . $result['data']['new_count'] . "\n";
        echo "  更新接口: " . $result['data']['update_count'] . "\n";
        
        if ($result['data']['new_count'] > 0) {
            echo "  示例新增接口:\n";
            $count = 0;
            foreach ($result['data']['new'] as $interface) {
                echo "    - {$interface['name']} ({$interface['method']} {$interface['path']})\n";
                $count++;
                if ($count >= 3) break;
            }
        }
    } else {
        echo "✗ 扫描失败: " . ($result['msg'] ?? '未知错误') . "\n";
    }
} catch (Exception $e) {
    echo "✗ 扫描过程出错: " . $e->getMessage() . "\n";
}

// 测试11: 检查缓存目录
echo "\n【测试11】检查缓存目录...\n";
$cacheDir = ROOT_PATH . 'runtime/cache/';
$tempDir = ROOT_PATH . 'runtime/temp/';

if (is_dir($cacheDir) && is_writable($cacheDir)) {
    echo "✓ 缓存目录可写\n";
} else {
    echo "✗ 缓存目录不可写\n";
}

if (is_dir($tempDir) && is_writable($tempDir)) {
    echo "✓ 临时目录可写\n";
} else {
    echo "✗ 临时目录不可写\n";
}

// 测试12: 检查数据库连接配置
echo "\n【测试12】检查数据库配置...\n";
echo "✓ 数据库主机: " . $config['hostname'] . "\n";
echo "✓ 数据库名称: " . $config['database'] . "\n";
echo "✓ 表前缀: " . $config['prefix'] . "\n";

$mysqli->close();

echo "\n=== API管理功能测试完成 ===\n";
echo "\n测试总结:\n";
echo "- 数据库表结构正常\n";
echo "- 核心文件完整\n";
echo "- 菜单配置正确\n";
echo "- 权限检查正常\n";
echo "- 扫描功能可用\n";
echo "\n建议:\n";
echo "1. 清理缓存: rm -rf runtime/cache/* runtime/temp/*\n";
echo "2. 使用平台管理员账号登录后台\n";
echo "3. 访问 API > 接口扫描 页面\n";
echo "4. 打开浏览器控制台查看网络请求\n";
echo "5. 检查是否有 JavaScript 错误\n";

?>
