<?php
/**
 * 工作流系统端到端测试脚本
 * 通过ThinkPHP框架上下文执行
 */
namespace think;

require __DIR__ . '/vendor/autoload.php';

$app = new App();
$app->initialize();

use think\facade\Db;
use think\facade\Log;

echo "=== 工作流系统端到端测试 ===\n\n";

// 1. 测试Ollama连接
echo "--- 1. 测试Ollama连接 ---\n";
try {
    $ollamaService = new \app\service\OllamaChatService();
    $apiUrl = $ollamaService->getOllamaApiUrl();
    echo "Ollama API URL: {$apiUrl}\n";

    $modelsResult = $ollamaService->getAvailableModels();
    if ($modelsResult['status'] == 1) {
        echo "可用模型数量: " . count($modelsResult['models']) . "\n";
        foreach ($modelsResult['models'] as $m) {
            echo "  - {$m['model_code']} (installed: " . ($m['installed'] ? 'yes' : 'no') . ")\n";
        }
    } else {
        echo "Ollama连接失败: " . ($modelsResult['msg'] ?? '未知错误') . "\n";
    }
} catch (\Exception $e) {
    echo "Ollama服务异常: " . $e->getMessage() . "\n";
}

echo "\n--- 2. 测试数据库表 ---\n";
$tables = ['workflow_project', 'workflow_node', 'workflow_edge', 'workflow_resource', 'workflow_character_id_card', 'workflow_preset_template'];
foreach ($tables as $t) {
    $count = Db::name($t)->count();
    echo "  {$t}: {$count} rows\n";
}

echo "\n--- 3. 测试GenerationService ---\n";
try {
    $genService = new \app\service\GenerationService();
    echo "  GenerationService 实例化成功\n";

    // 检查createTask方法是否存在
    if (method_exists($genService, 'createTask')) {
        echo "  createTask 方法存在\n";
    } else {
        echo "  createTask 方法不存在!\n";
        // 列出可用方法
        $methods = get_class_methods($genService);
        echo "  可用方法: " . implode(', ', array_slice($methods, 0, 20)) . "\n";
    }
} catch (\Exception $e) {
    echo "  GenerationService 异常: " . $e->getMessage() . "\n";
}

echo "\n--- 4. 测试VoiceChatService ---\n";
try {
    $voiceService = new \app\service\VoiceChatService();
    echo "  VoiceChatService 实例化成功\n";
    if (method_exists($voiceService, 'getVoxCPMApiUrl')) {
        $voxUrl = $voiceService->getVoxCPMApiUrl();
        echo "  VoxCPM URL: {$voxUrl}\n";
    }
} catch (\Exception $e) {
    echo "  VoiceChatService 异常: " . $e->getMessage() . "\n";
}

echo "\n--- 5. FFmpeg可用性 ---\n";
$ffmpegVersion = shell_exec('ffmpeg -version 2>&1 | head -1');
echo "  FFmpeg: " . trim($ffmpegVersion) . "\n";

echo "\n=== 测试完成 ===\n";
