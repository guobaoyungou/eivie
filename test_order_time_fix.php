<?php
/**
 * 订单创建时间修复验证脚本
 * 验证订单列表显示的创建时间是否与记录详情页一致
 */

require_once __DIR__ . '/vendor/autoload.php';

// 初始化应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;
use app\service\GenerationOrderService;

echo "======================================\n";
echo "订单创建时间修复验证\n";
echo "======================================\n\n";

$orderService = new GenerationOrderService();

// 测试1：查询最近的照片生成订单
echo "【测试1】照片生成订单 - 最近5条\n";
echo str_repeat('-', 80) . "\n";

$where = [
    ['o.generation_type', '=', 1],
    ['o.status', '=', 1]
];

$result = $orderService->getOrderList($where, 1, 5, 'o.id desc');

if ($result['count'] > 0) {
    printf("%-8s %-20s %-20s %-20s %-10s\n", 'ID', '订单号', '显示时间', '原订单时间', '支付状态');
    echo str_repeat('-', 80) . "\n";
    
    foreach ($result['data'] as $order) {
        // 查询原始订单时间
        $originalOrder = Db::name('generation_order')->where('id', $order['id'])->find();
        $originalTime = $originalOrder['createtime'] ? date('Y-m-d H:i:s', $originalOrder['createtime']) : '-';
        
        printf("%-8s %-20s %-20s %-20s %-10s\n", 
            $order['id'], 
            substr($order['ordernum'], 0, 18) . '...',
            $order['createtime_text'],
            $originalTime,
            $order['pay_status_text']
        );
    }
    echo "\n✅ 已支付订单应显示记录创建时间（与原订单时间可能不同）\n";
    echo "✅ 待支付订单应显示订单创建时间（与原订单时间相同）\n\n";
} else {
    echo "❌ 没有找到照片生成订单\n\n";
}

// 测试2：查询最近的视频生成订单
echo "【测试2】视频生成订单 - 最近5条\n";
echo str_repeat('-', 80) . "\n";

$where = [
    ['o.generation_type', '=', 2],
    ['o.status', '=', 1]
];

$result = $orderService->getOrderList($where, 1, 5, 'o.id desc');

if ($result['count'] > 0) {
    printf("%-8s %-20s %-20s %-20s %-10s\n", 'ID', '订单号', '显示时间', '原订单时间', '支付状态');
    echo str_repeat('-', 80) . "\n";
    
    foreach ($result['data'] as $order) {
        // 查询原始订单时间
        $originalOrder = Db::name('generation_order')->where('id', $order['id'])->find();
        $originalTime = $originalOrder['createtime'] ? date('Y-m-d H:i:s', $originalOrder['createtime']) : '-';
        
        printf("%-8s %-20s %-20s %-20s %-10s\n", 
            $order['id'], 
            substr($order['ordernum'], 0, 18) . '...',
            $order['createtime_text'],
            $originalTime,
            $order['pay_status_text']
        );
    }
    echo "\n✅ 已支付订单应显示记录创建时间（与原订单时间可能不同）\n";
    echo "✅ 待支付订单应显示订单创建时间（与原订单时间相同）\n\n";
} else {
    echo "❌ 没有找到视频生成订单\n\n";
}

// 测试3：检查订单详情时间一致性
echo "【测试3】订单详情时间一致性验证\n";
echo str_repeat('-', 80) . "\n";

// 随机抽取一个已支付的订单
$testOrder = Db::name('generation_order')
    ->where('status', 1)
    ->where('pay_status', 1)
    ->where('record_id', '>', 0)
    ->order('id desc')
    ->find();

if ($testOrder) {
    $detail = $orderService->getOrderDetail($testOrder['id'], $testOrder['aid'], $testOrder['bid']);
    
    if ($detail) {
        echo "订单ID: {$detail['id']}\n";
        echo "订单号: {$detail['ordernum']}\n";
        echo "生成类型: {$detail['generation_type_text']}\n";
        echo "详情页显示时间: {$detail['createtime_text']}\n";
        
        // 查询记录的实际创建时间
        if ($detail['record_id'] > 0) {
            $record = Db::name('generation_record')->where('id', $detail['record_id'])->find();
            if ($record) {
                $recordTime = date('Y-m-d H:i:s', $record['create_time']);
                echo "记录实际创建时间: {$recordTime}\n";
                
                if ($detail['createtime_text'] === $recordTime) {
                    echo "✅ 时间一致！修复成功\n\n";
                } else {
                    echo "❌ 时间不一致！需要检查\n\n";
                }
            }
        }
    }
} else {
    echo "ℹ️  未找到已支付的测试订单\n\n";
}

// 测试4：统计时间差异
echo "【测试4】订单与记录时间差异统计\n";
echo str_repeat('-', 80) . "\n";

$stats = Db::query("
    SELECT 
        CASE o.generation_type 
            WHEN 1 THEN '照片生成' 
            WHEN 2 THEN '视频生成' 
        END AS type_name,
        COUNT(*) AS total_orders,
        COUNT(r.id) AS has_record,
        COUNT(*) - COUNT(r.id) AS no_record,
        ROUND(AVG(CASE WHEN r.create_time IS NOT NULL 
            THEN r.create_time - o.createtime END), 2) AS avg_diff_seconds
    FROM ddwx_generation_order o
    LEFT JOIN ddwx_generation_record r ON o.record_id = r.id
    WHERE o.status = 1 AND o.pay_status = 1
    GROUP BY o.generation_type
");

if ($stats) {
    printf("%-15s %-12s %-12s %-12s %-18s\n", 
        '类型', '总订单数', '有记录', '无记录', '平均时间差(秒)');
    echo str_repeat('-', 80) . "\n";
    
    foreach ($stats as $stat) {
        printf("%-15s %-12s %-12s %-12s %-18s\n", 
            $stat['type_name'],
            $stat['total_orders'],
            $stat['has_record'],
            $stat['no_record'],
            $stat['avg_diff_seconds'] ? round($stat['avg_diff_seconds'], 2) : '-'
        );
    }
    echo "\n";
}

echo "======================================\n";
echo "验证完成\n";
echo "======================================\n\n";

echo "📋 检查要点：\n";
echo "1. 已支付订单的显示时间应该与记录创建时间一致\n";
echo "2. 待支付订单显示订单创建时间（作为兜底）\n";
echo "3. 订单详情页的创建时间与列表页应该一致\n";
echo "4. 时间差异通常为几秒到几十秒（支付成功到创建记录的时间）\n\n";
