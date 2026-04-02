<?php
/**
 * 背景图管理功能重构 - 后端测试脚本
 * 测试 HdThemeService 对 weixin_background 表的读写
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', __DIR__ . '/');

// 引导 ThinkPHP
require __DIR__ . '/vendor/autoload.php';
$http = (new \think\App())->http;
$http->run();

use think\facade\Db;

echo "=== 背景图管理重构测试 ===\n\n";

// 1. 测试 huodong 数据库连接
echo "1. 测试数据库连接...\n";
try {
    $count = Db::connect('huodong')->table('weixin_background')->count();
    echo "   ✅ 连接成功，weixin_background 表共 {$count} 条记录\n";
} catch (\Exception $e) {
    echo "   ❌ 连接失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. 测试获取背景列表（检查 has_material 和 attachmentpath 字段）
echo "\n2. 测试获取背景列表...\n";
try {
    $service = new \app\service\hd\HdThemeService();
    $result = $service->getBackgrounds(0, 0, 0);
    
    if ($result['code'] === 0) {
        $list = $result['data'];
        echo "   ✅ 获取成功，共 " . count($list) . " 个功能模块\n";
        foreach ($list as $item) {
            $hasMat = $item['has_material'] ? '有素材' : '无素材(纯色)';
            $type = intval($item['bgtype']) === 2 ? '视频' : '图片';
            $path = $item['attachmentpath'] ?: '(空)';
            echo "   - [{$item['plugname']}] {$item['name']} | {$hasMat} | {$type} | 路径: {$path}\n";
        }
        
        // 验证过滤
        $plugnames = array_column($list, 'plugname');
        if (!in_array('shuqian', $plugnames) && !in_array('pashu', $plugnames)) {
            echo "   ✅ shuqian/pashu 已正确过滤\n";
        }
        
        // 验证无素材时 attachmentpath 是空字符串
        $noMaterial = array_filter($list, function($i) { return $i['has_material'] === 0; });
        $allEmpty = true;
        foreach ($noMaterial as $nm) {
            if (!empty($nm['attachmentpath'])) { $allEmpty = false; break; }
        }
        echo $allEmpty ? "   ✅ 无素材模块的 attachmentpath 均为空（前端将显示纯色）\n" : "   ❌ 无素材模块的 attachmentpath 未清空\n";
    } else {
        echo "   ❌ 获取失败: " . ($result['msg'] ?? 'unknown') . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ 异常: " . $e->getMessage() . "\n";
}

echo "\n=== 测试完成 ===\n";
