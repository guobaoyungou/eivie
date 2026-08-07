<?php
/**
 * AI短剧工作流系统 - 数据库迁移脚本
 * 创建 workflow_project, workflow_node, workflow_edge, workflow_resource,
 *       workflow_character_id_card, workflow_preset_template 等表
 */
define('ROOT_PATH', __DIR__ . '/');
$config = include(ROOT_PATH . 'config.php');

try {
    $pdo = new PDO(
        "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 读取SQL文件
    $sqlFile = ROOT_PATH . 'database/migrations/workflow_tables.sql';
    if (!file_exists($sqlFile)) {
        echo "SQL文件不存在: {$sqlFile}\n";
        exit(1);
    }
    $sql = file_get_contents($sqlFile);

    // 分割多条SQL语句
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            $stmt = trim($stmt);
            return !empty($stmt) && strpos($stmt, '--') !== 0;
        }
    );

    $success = 0;
    $skip = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || preg_match('/^--/', $statement)) {
            continue;
        }
        try {
            $pdo->exec($statement);
            // 提取表名用于显示
            if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $m)) {
                echo "✅ 表 {$m[1]} 创建成功\n";
            } else {
                echo "✅ 执行成功: " . substr($statement, 0, 60) . "...\n";
            }
            $success++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $m)) {
                    echo "⏭️  表 {$m[1]} 已存在，跳过\n";
                }
                $skip++;
            } else {
                echo "❌ 执行失败: " . $e->getMessage() . "\n";
                echo "   SQL: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }

    echo "\n========================================\n";
    echo "迁移完成！成功: {$success}, 跳过: {$skip}\n";
    echo "========================================\n";

} catch (PDOException $e) {
    echo "数据库连接错误: " . $e->getMessage() . "\n";
    exit(1);
}
