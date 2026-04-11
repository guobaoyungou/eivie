<?php
/**
 * 端到端工作流测试脚本（CLI）
 * 用法: php test_workflow_e2e.php [step]
 * step: check_llm | create | run | poll | status
 */
define('ROOT_PATH', __DIR__ . '/');

// 引导 ThinkPHP
require __DIR__ . '/vendor/autoload.php';

// 启动ThinkPHP应用
$app = new \think\App();
$app->initialize();

use think\facade\Db;

// 模拟管理员身份
$aid  = 1;
$bid  = 0;
$uid  = 1;
$mdid = 0;

$step = $argv[1] ?? 'check_llm';
$projectId = $argv[2] ?? 0;

echo "=== AI短剧工作流 端到端测试 ===\n";
echo "Step: {$step}\n\n";

try {
    switch ($step) {

    case 'check_llm':
        echo "--- 检查LLM服务可用性 ---\n";
        $cloudLLM = new \app\service\CloudLLMService();
        $availability = $cloudLLM->checkAvailability();
        $ollamaAvailable = \app\service\CloudLLMService::isOllamaAvailable();
        echo "Ollama可用: " . ($ollamaAvailable ? '是' : '否') . "\n";
        echo "云端LLM: " . json_encode($availability, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

        // 快速测试LLM调用
        echo "\n--- 测试LLM调用 ---\n";
        $testResult = $cloudLLM->sendMessage([
            ['role' => 'user', 'content' => '用一句话描述一个都市甜宠短剧的开场场景']
        ], ['max_tokens' => 200]);
        echo "LLM返回: " . json_encode($testResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        break;

    case 'check_models':
        echo "--- 检查可用的图像/视频生成模型 ---\n";
        // 检查图像生成模型
        $imageModels = Db::name('model_info')->alias('m')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->where('m.is_active', 1)
            ->where('t.type_code', 'image_generation')
            ->field('m.id, m.model_name, m.model_code, p.provider_name, p.provider_code')
            ->select()->toArray();
        echo "图像生成模型:\n";
        foreach ($imageModels as $m) {
            echo "  [{$m['id']}] {$m['model_name']} ({$m['provider_name']})\n";
        }

        // 检查是否有对应的API Key
        echo "\n--- 检查API Key配置 ---\n";
        $keys = Db::name('system_api_key')->where('is_active', 1)
            ->field('id, provider_id, key_name, provider_code, usage_count, concurrent_count, max_concurrent')
            ->select()->toArray();
        foreach ($keys as $k) {
            echo "  [{$k['id']}] {$k['key_name']} provider={$k['provider_code']} usage={$k['usage_count']}\n";
        }

        // 检查视频生成模型
        $videoModels = Db::name('model_info')->alias('m')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->where('m.is_active', 1)
            ->where('t.type_code', 'video_generation')
            ->field('m.id, m.model_name, m.model_code, p.provider_name, p.provider_code')
            ->select()->toArray();
        echo "\n视频生成模型:\n";
        foreach ($videoModels as $m) {
            echo "  [{$m['id']}] {$m['model_name']} ({$m['provider_name']})\n";
        }
        break;

    case 'create':
        echo "--- 创建测试项目 ---\n";
        $now = time();

        $projectId = Db::name('workflow_project')->insertGetId([
            'aid'            => $aid,
            'bid'            => $bid,
            'mdid'           => $mdid,
            'uid'            => $uid,
            'title'          => '测试短剧-' . date('mdHis'),
            'description'    => '甜宠题材自动化测试',
            'creation_mode'  => 'oneclick',
            'status'         => 'draft',
            'create_time'    => $now,
            'update_time'    => $now,
        ]);
        echo "项目ID: {$projectId}\n";

        // 创建6个节点
        $nodeIds = [];
        $nodeConfigs = [
            ['type' => 'script', 'label' => '剧本生成', 'sort' => 1, 'config' => [
                'creativity' => '一个都市女孩在咖啡店意外遇见大学时的暧昧对象，两人从尴尬到重新心动的甜蜜故事',
                'episodes' => 1, 'duration' => 30, 'genre' => '甜宠',
            ]],
            ['type' => 'character', 'label' => '角色生成', 'sort' => 2, 'config' => [
                'style' => 'realistic',
            ]],
            ['type' => 'storyboard', 'label' => '分镜生成', 'sort' => 3, 'config' => [
                'resolution' => '720P',
            ]],
            ['type' => 'video', 'label' => '视频生成', 'sort' => 4, 'config' => [
                'duration' => 5, 'generation_mode' => 'first_frame',
            ]],
            ['type' => 'voice', 'label' => '配音生成', 'sort' => 5, 'config' => [
                'speed' => 1.0, 'synth_mode' => 'sound_design',
            ]],
            ['type' => 'compose', 'label' => '成片合成', 'sort' => 6, 'config' => [
                'transition' => 'fade', 'output_format' => 'mp4',
            ]],
        ];

        foreach ($nodeConfigs as $nc) {
            $nodeId = Db::name('workflow_node')->insertGetId([
                'project_id'    => $projectId,
                'aid'           => $aid,
                'bid'           => $bid,
                'mdid'          => $mdid,
                'uid'           => $uid,
                'node_type'     => $nc['type'],
                'node_label'    => $nc['label'],
                'status'        => $nc['type'] === 'script' ? 'configured' : 'waiting',
                'config_params' => json_encode($nc['config'], JSON_UNESCAPED_UNICODE),
                'position_x'    => $nc['sort'] * 200,
                'position_y'    => 300,
                'create_time'   => $now,
                'update_time'   => $now,
            ]);
            $nodeIds[$nc['type']] = $nodeId;
            echo "  节点 [{$nc['type']}] ID: {$nodeId}\n";
        }

        // 创建连线
        $edges = [
            ['source' => 'script', 'target' => 'character', 'sp' => 'characters', 'tp' => 'characters'],
            ['source' => 'script', 'target' => 'storyboard', 'sp' => 'scenes', 'tp' => 'scenes'],
            ['source' => 'script', 'target' => 'voice', 'sp' => 'dialogue', 'tp' => 'dialogue'],
            ['source' => 'character', 'target' => 'storyboard', 'sp' => 'character_assets', 'tp' => 'character_assets'],
            ['source' => 'storyboard', 'target' => 'video', 'sp' => 'frames', 'tp' => 'frames'],
            ['source' => 'video', 'target' => 'compose', 'sp' => 'clips', 'tp' => 'clips'],
            ['source' => 'voice', 'target' => 'compose', 'sp' => 'audio_clips', 'tp' => 'audio_clips'],
        ];
        foreach ($edges as $e) {
            Db::name('workflow_edge')->insert([
                'project_id'      => $projectId,
                'source_node_id'  => $nodeIds[$e['source']],
                'target_node_id'  => $nodeIds[$e['target']],
                'source_port'     => $e['sp'],
                'target_port'     => $e['tp'],
                'create_time'     => $now,
            ]);
        }
        echo "\n连线创建完成（7条）\n";
        echo "\n下一步: php test_workflow_e2e.php run {$projectId}\n";
        break;

    case 'run':
        if ($projectId <= 0) die("需要project_id参数\n");
        echo "--- 运行工作流 (project_id={$projectId}) ---\n";
        $engine = new \app\service\WorkflowEngineService();
        $result = $engine->runWorkflow($projectId, $aid, $bid);
        echo "结果: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        echo "\n下一步: php test_workflow_e2e.php status {$projectId}\n";
        break;

    case 'poll':
        if ($projectId <= 0) die("需要project_id参数\n");
        echo "--- 轮询异步节点 (project_id={$projectId}) ---\n";
        $engine = new \app\service\WorkflowEngineService();
        $result = $engine->pollAsyncNodes($projectId, $aid, $bid);
        echo "结果: " . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        break;

    case 'status':
        if ($projectId <= 0) die("需要project_id参数\n");
        echo "--- 项目状态 (project_id={$projectId}) ---\n";
        $project = Db::name('workflow_project')->where('id', $projectId)->find();
        echo "项目状态: {$project['status']}\n\n";

        $nodes = Db::name('workflow_node')->where('project_id', $projectId)
            ->field('id, node_type, node_label, status, error_message, execute_time')
            ->order('id asc')
            ->select()->toArray();
        foreach ($nodes as $n) {
            $execTime = $n['execute_time'] ? round($n['execute_time'], 1) . 's' : '-';
            $err = $n['error_message'] ? " ERR: " . mb_substr($n['error_message'], 0, 80) : '';
            echo "  [{$n['id']}] {$n['node_label']} ({$n['node_type']}) => {$n['status']} [{$execTime}]{$err}\n";
        }

        // 查看有输出数据的节点
        echo "\n--- 节点输出概要 ---\n";
        $nodesWithOutput = Db::name('workflow_node')->where('project_id', $projectId)
            ->where('output_data', '<>', '')
            ->whereNotNull('output_data')
            ->field('id, node_type, output_data')
            ->select()->toArray();
        foreach ($nodesWithOutput as $n) {
            $data = json_decode($n['output_data'], true);
            $summary = '';
            if (isset($data['characters'])) $summary .= count($data['characters']) . '个角色 ';
            if (isset($data['scenes'])) $summary .= count($data['scenes']) . '个分镜 ';
            if (isset($data['dialogue'])) $summary .= count($data['dialogue']) . '段对话 ';
            if (isset($data['character_assets'])) $summary .= count($data['character_assets']) . '个角色形象 ';
            if (isset($data['frames'])) $summary .= count($data['frames']) . '帧画面 ';
            if (isset($data['clips'])) $summary .= count($data['clips']) . '个视频片段 ';
            if (isset($data['audio_clips'])) $summary .= count($data['audio_clips']) . '段音频 ';
            if (isset($data['final_video_url'])) $summary .= '最终视频: ' . $data['final_video_url'];
            echo "  [{$n['id']}] {$n['node_type']}: {$summary}\n";
        }
        break;

    case 'output':
        if ($projectId <= 0) die("需要node_id参数\n");
        $nodeId = $projectId; // 复用参数
        echo "--- 节点输出详情 (node_id={$nodeId}) ---\n";
        $node = Db::name('workflow_node')->where('id', $nodeId)->find();
        if ($node) {
            $data = json_decode($node['output_data'], true);
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "节点不存在\n";
        }
        break;

    default:
        echo "用法: php test_workflow_e2e.php [check_llm|check_models|create|run|poll|status|output] [project_id|node_id]\n";
    }
} catch (\Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
