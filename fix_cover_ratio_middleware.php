<?php
/**
 * 封面比例修复中间件
 * 通过修改页面输出，动态修复封面比例参数
 */

// 获取当前请求的页面ID
function getCurrentPageId() {
    // 根据URL或参数获取页面ID
    // 这里假设页面ID通过参数传递，实际情况可能不同
    $pageId = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;
    
    // 如果没有参数，尝试从URL解析
    if (!$pageId) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('/\/h5\/[0-9]+\.html/', $requestUri)) {
            $pageId = intval(basename($requestUri, '.html'));
        }
    }
    
    return $pageId;
}

// 修复JSON数据中的封面比例参数
function fixCoverRatioInJson($jsonData) {
    // 如果是字符串，解析为数组
    if (is_string($jsonData)) {
        $data = json_decode($jsonData, true);
        if ($data === null) {
            return $jsonData; // 解析失败，返回原数据
        }
    } else {
        $data = $jsonData;
    }
    
    // 递归修复数组中的数据
    function recursiveFix(&$item) {
        if (is_array($item)) {
            // 检查是否为photo_generation或video_generation组件
            if (isset($item['temp']) && in_array($item['temp'], ['photo_generation', 'video_generation'])) {
                // 确保有params数组
                if (!isset($item['params']) || !is_array($item['params'])) {
                    $item['params'] = [];
                }
                
                // 如果cover_ratio存在但不是3:4，则修复
                if (isset($item['params']['cover_ratio']) && $item['params']['cover_ratio'] !== '3:4') {
                    $item['params']['cover_ratio'] = '3:4';
                } elseif (!isset($item['params']['cover_ratio'])) {
                    // 如果不存在，则添加
                    $item['params']['cover_ratio'] = '3:4';
                }
                
                // 添加其他相关参数
                if (!isset($item['params']['cover_radius'])) {
                    $item['params']['cover_radius'] = 8;
                }
                if (!isset($item['params']['card_radius'])) {
                    $item['params']['card_radius'] = 8;
                }
                if (!isset($item['params']['card_gap'])) {
                    $item['params']['card_gap'] = 12;
                }
            }
            
            // 递归处理子元素
            foreach ($item as &$value) {
                recursiveFix($value);
            }
        }
    }
    
    recursiveFix($data);
    
    // 如果是字符串输入，返回JSON字符串
    if (is_string($jsonData)) {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    return $data;
}

// 修复设计器页面中的组件默认值
function fixDesignerPageContent($content) {
    // 修复photo_generation组件默认值
    $content = str_replace(
        'cover_ratio:\'1:1\'',
        'cover_ratio:\'3:4\'',
        $content
    );
    
    // 修复video_generation组件默认值
    $content = str_replace(
        'coverRatio() { return this.params.cover_ratio || \'1:1\'; }',
        'coverRatio() { return this.params.cover_ratio || \'3:4\'; }',
        $content
    );
    
    return $content;
}

// 主修复函数
function applyCoverRatioFix($output) {
    // 只在H5页面中应用修复
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/h5/') !== false) {
        // 查找并修复JSON格式的组件数据
        $output = preg_replace_callback(
            '/"components"\s*:\s*\[(.*?)\]/s',
            function($matches) {
                $componentsData = $matches[1];
                $fixedData = fixCoverRatioInJson('[' . $componentsData . ']');
                return '"components":' . $fixedData;
            },
            $output
        );
        
        // 修复内联的组件参数
        $output = preg_replace_callback(
            '/cover_ratio\s*:\s*["\']1:1["\']/i',
            function($matches) {
                return 'cover_ratio:\'3:4\'';
            },
            $output
        );
    }
    
    return $output;
}

// 如果是直接访问，输出使用方法
if (php_sapi_name() === 'cli') {
    echo "封面比例修复中间件\n";
    echo "使用方法：\n";
    echo "1. 在输出缓冲回调中调用 applyCoverRatioFix() 函数\n";
    echo "2. 或在页面渲染前调用 fixDesignerPageContent() 函数\n";
    echo "\n示例：\n";
    echo "ob_start(function($buffer) {\n";
    echo "    return applyCoverRatioFix($buffer);\n";
    echo "});\n";
} else {
    // 自动应用修复（如果需要）
    if (isset($_GET['auto_fix_cover_ratio'])) {
        ob_start('applyCoverRatioFix');
    }
}

?>