<?php
/**
 * 检查模板数据
 */
require __DIR__ . '/vendor/autoload.php';

// 加载配置
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die("配置文件不存在: {$configFile}\n");
}
$config = include $configFile;

// 连接数据库
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['hostname'] ?? 'localhost',
        $config['hostport'] ?? 3306,
        $config['database'] ?? ''
    );
    
    $pdo = new PDO(
        $dsn,
        $config['username'] ?? 'root',
        $config['password'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== 数据库连接成功 ===\n\n";
    
    // 查询模板
    $stmt = $pdo->prepare("SELECT * FROM ddwx_generation_scene_template WHERE id = ? LIMIT 1");
    $stmt->execute([22]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($template) {
        echo "=== 找到模板 ID=22 ===\n";
        echo "模板名称: " . ($template['template_name'] ?? 'N/A') . "\n";
        echo "状态: " . ($template['status'] ?? 'N/A') . " (1=启用)\n";
        echo "生成类型: " . ($template['generation_type'] ?? 'N/A') . "\n";
        echo "基础价格: " . ($template['base_price'] ?? 'N/A') . "\n";
        echo "模型ID: " . ($template['model_id'] ?? 'N/A') . "\n";
        echo "分销设置: " . ($template['commissionset'] ?? 'N/A') . " (-1=不参与分销)\n";
        echo "\n";
    } else {
        echo "=== 未找到模板 ID=22 ===\n";
        echo "可能原因：\n";
        echo "1. 模板不存在\n";
        echo "2. 模板已被删除\n";
        echo "\n";
        
        // 查询其他可用模板
        $stmt = $pdo->query("SELECT id, template_name, status FROM ddwx_generation_scene_template WHERE status = 1 ORDER BY id ASC LIMIT 5");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($templates) {
            echo "=== 可用的模板列表 ===\n";
            foreach ($templates as $t) {
                echo "ID: {$t['id']}, 名称: {$t['template_name']}\n";
            }
        } else {
            echo "没有找到任何启用的模板\n";
        }
    }
    
    // 检查 admin_set 表结构
    echo "\n=== 检查 admin_set 表字段 ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM ddwx_admin_set LIKE '%fenxiao%'");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($columns) {
        echo "找到以下包含 'fenxiao' 的字段:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "没有找到包含 'fenxiao' 的字段\n";
    }
    
    // 检查 ai_score_unit_name 字段
    echo "\n=== 检查 ai_score_unit_name 字段 ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM ddwx_admin_set LIKE 'ai_score_unit_name'");
    $field = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($field) {
        echo "字段存在: {$field['Field']} ({$field['Type']})\n";
        
        // 查询值
        $stmt = $pdo->query("SELECT ai_score_unit_name FROM ddwx_admin_set WHERE aid = 1 LIMIT 1");
        $value = $stmt->fetchColumn();
        echo "当前值: " . ($value ?: '(空)') . "\n";
    } else {
        echo "字段不存在，需要执行迁移脚本\n";
    }
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
}
