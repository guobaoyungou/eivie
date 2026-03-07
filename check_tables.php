<?php
// 检查数据库表是否存在
require __DIR__ . '/vendor/autoload.php';

$app = new \think\App();
$app->initialize();

use think\facade\Db;

try {
    // 检查 api_interface 表
    $tables = Db::query("SHOW TABLES LIKE '%api%'");
    
    echo "=== 数据库中与API相关的表 ===\n\n";
    
    if (empty($tables)) {
        echo "❌ 没有找到任何与API相关的表！\n\n";
        echo "需要创建以下表：\n";
        echo "1. ddwx_api_interface (接口表)\n";
        echo "2. ddwx_api_test_log (测试日志表)\n\n";
        
        echo "是否需要创建SQL建表语句？\n";
    } else {
        echo "✓ 找到以下表：\n\n";
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            echo "  - {$tableName}\n";
            
            // 显示表结构
            $columns = Db::query("SHOW COLUMNS FROM {$tableName}");
            echo "    字段列表：\n";
            foreach ($columns as $col) {
                echo "      • {$col['Field']} ({$col['Type']}) " . 
                     ($col['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . 
                     ($col['Key'] == 'PRI' ? ' [主键]' : '') . "\n";
            }
            echo "\n";
        }
    }
    
    // 尝试查询 api_interface 表
    echo "\n=== 测试查询 api_interface 表 ===\n\n";
    try {
        $count = Db::name('api_interface')->count();
        echo "✓ 表存在，当前记录数：{$count}\n";
    } catch (\Exception $e) {
        echo "❌ 查询失败：" . $e->getMessage() . "\n";
        echo "\n需要创建表！\n";
    }
    
} catch (\Exception $e) {
    echo "错误：" . $e->getMessage() . "\n";
    echo "堆栈：\n" . $e->getTraceAsString() . "\n";
}
