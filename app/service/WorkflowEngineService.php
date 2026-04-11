<?php
/**
 * 工作流编排引擎服务
 * DAG解析、拓扑排序、节点调度、上下文传播、断点续跑
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\WorkflowProject;
use app\model\WorkflowNode;
use app\model\WorkflowEdge;
use app\service\WorkflowLogService;

class WorkflowEngineService
{
    /** @var WorkflowNodeService */
    protected $nodeService;

    public function __construct()
    {
        $this->nodeService = new WorkflowNodeService();
    }

    // ================================================================
    // DAG 解析与拓扑排序
    // ================================================================

    /**
     * 解析项目的DAG结构
     * @param int $projectId
     * @return array ['nodes' => [...], 'edges' => [...], 'adjacency' => [...], 'in_degree' => [...]]
     */
    public function parseDAG($projectId)
    {
        $nodes = WorkflowNode::where('project_id', $projectId)->select()->toArray();
        $edges = WorkflowEdge::where('project_id', $projectId)->select()->toArray();

        $adjacency = []; // nodeId => [downstream nodeIds]
        $inDegree  = []; // nodeId => count of upstream edges

        foreach ($nodes as $node) {
            $adjacency[$node['id']] = [];
            $inDegree[$node['id']]  = 0;
        }

        foreach ($edges as $edge) {
            $adjacency[$edge['source_node_id']][] = $edge['target_node_id'];
            $inDegree[$edge['target_node_id']]++;
        }

        return [
            'nodes'     => $nodes,
            'edges'     => $edges,
            'adjacency' => $adjacency,
            'in_degree' => $inDegree,
        ];
    }

    /**
     * 拓扑排序（Kahn算法）
     * @param array $dag parseDAG的返回结果
     * @return array|false 排序后的nodeId列表，有环则返回false
     */
    public function topologicalSort($dag)
    {
        $adjacency = $dag['adjacency'];
        $inDegree  = $dag['in_degree'];

        $queue  = [];
        $sorted = [];

        // 入度为0的节点入队
        foreach ($inDegree as $nodeId => $degree) {
            if ($degree === 0) {
                $queue[] = $nodeId;
            }
        }

        while (!empty($queue)) {
            $current = array_shift($queue);
            $sorted[] = $current;

            foreach ($adjacency[$current] as $downstream) {
                $inDegree[$downstream]--;
                if ($inDegree[$downstream] === 0) {
                    $queue[] = $downstream;
                }
            }
        }

        // 如果排序结果不包含所有节点，说明存在环
        if (count($sorted) !== count($dag['nodes'])) {
            return false;
        }

        return $sorted;
    }

    /**
     * 检测DAG是否有环
     */
    public function hasCycle($projectId)
    {
        $dag = $this->parseDAG($projectId);
        return $this->topologicalSort($dag) === false;
    }

    // ================================================================
    // 调度与执行
    // ================================================================

    /**
     * 获取当前就绪的节点列表（所有上游都已成功）
     */
    public function getReadyNodes($projectId)
    {
        $dag   = $this->parseDAG($projectId);
        $edges = $dag['edges'];
        $nodes = $dag['nodes'];

        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeMap[$node['id']] = $node;
        }

        // 构建每个节点的上游列表
        $upstreamMap = [];
        foreach ($nodes as $node) {
            $upstreamMap[$node['id']] = [];
        }
        foreach ($edges as $edge) {
            $upstreamMap[$edge['target_node_id']][] = $edge['source_node_id'];
        }

        $readyNodes = [];
        foreach ($nodes as $node) {
            // 跳过已完成、运行中、轮询中的节点
            if (in_array($node['status'], [
                WorkflowNode::STATUS_RUNNING,
                WorkflowNode::STATUS_POLLING,
                WorkflowNode::STATUS_SUCCEEDED,
            ])) {
                continue;
            }

            // 检查所有上游是否都已成功
            $upstreamIds = $upstreamMap[$node['id']];
            if (empty($upstreamIds)) {
                // 无上游依赖，如果已配置则就绪
                if (in_array($node['status'], [WorkflowNode::STATUS_CONFIGURED, WorkflowNode::STATUS_READY, WorkflowNode::STATUS_FAILED])) {
                    $readyNodes[] = $node;
                }
            } else {
                $allUpstreamDone = true;
                foreach ($upstreamIds as $upId) {
                    if (($nodeMap[$upId]['status'] ?? '') !== WorkflowNode::STATUS_SUCCEEDED) {
                        $allUpstreamDone = false;
                        break;
                    }
                }
                if ($allUpstreamDone && in_array($node['status'], [
                    WorkflowNode::STATUS_CONFIGURED,
                    WorkflowNode::STATUS_WAITING,
                    WorkflowNode::STATUS_READY,
                    WorkflowNode::STATUS_FAILED,
                ])) {
                    $readyNodes[] = $node;
                }
            }
        }

        return $readyNodes;
    }

    /**
     * 从起始节点执行整个工作流
     */
    public function runWorkflow($projectId, $aid, $bid)
    {
        // 验证DAG无环
        if ($this->hasCycle($projectId)) {
            return ['status' => 0, 'msg' => '工作流存在循环依赖'];
        }

        // 更新项目状态为运行中
        WorkflowProject::where('id', $projectId)->update(['status' => WorkflowProject::STATUS_RUNNING]);
        WorkflowLogService::info($projectId, 0, 'workflow_start', '工作流开始执行', ['aid' => $aid, 'bid' => $bid]);

        // 获取就绪节点并调度
        return $this->scheduleReadyNodes($projectId, $aid, $bid);
    }

    /**
     * 从指定节点开始执行（断点续跑）
     */
    public function runFromNode($projectId, $nodeId, $aid, $bid)
    {
        $node = WorkflowNode::find($nodeId);
        if (!$node || $node->project_id != $projectId) {
            return ['status' => 0, 'msg' => '节点不存在'];
        }

        // 重置该节点及其下游节点的状态
        $this->resetNodeAndDownstream($nodeId, $projectId);

        // 将该节点标记为就绪
        $node->save(['status' => WorkflowNode::STATUS_READY]);

        // 更新项目状态
        WorkflowProject::where('id', $projectId)->update(['status' => WorkflowProject::STATUS_RUNNING]);

        // 执行该节点
        return $this->executeNode($nodeId, $aid, $bid);
    }

    /**
     * 调度就绪节点执行
     */
    public function scheduleReadyNodes($projectId, $aid, $bid)
    {
        $readyNodes = $this->getReadyNodes($projectId);

        if (empty($readyNodes)) {
            // 检查是否全部完成
            $allNodes = WorkflowNode::where('project_id', $projectId)->select()->toArray();
            $allSucceeded = true;
            foreach ($allNodes as $n) {
                if ($n['status'] !== WorkflowNode::STATUS_SUCCEEDED) {
                    $allSucceeded = false;
                    break;
                }
            }
            if ($allSucceeded && !empty($allNodes)) {
                WorkflowProject::where('id', $projectId)->update(['status' => WorkflowProject::STATUS_COMPLETED]);
                return ['status' => 1, 'msg' => '工作流已全部完成'];
            }
            return ['status' => 1, 'msg' => '当前无可执行的就绪节点', 'ready_count' => 0];
        }

        $results = [];
        foreach ($readyNodes as $node) {
            $result = $this->executeNode($node['id'], $aid, $bid);
            $results[] = [
                'node_id' => $node['id'],
                'node_type' => $node['node_type'],
                'result' => $result,
            ];
        }

        return [
            'status'      => 1,
            'msg'         => '已调度 ' . count($readyNodes) . ' 个节点执行',
            'ready_count' => count($readyNodes),
            'results'     => $results,
        ];
    }

    /**
     * 执行单个节点
     */
    public function executeNode($nodeId, $aid, $bid)
    {
        $node = WorkflowNode::find($nodeId);
        if (!$node) {
            return ['status' => 0, 'msg' => '节点不存在'];
        }

        // 1. 收集上游数据注入 input_data
        $inputData = $this->collectInputData($nodeId);
        $node->save([
            'input_data'   => json_encode($inputData, JSON_UNESCAPED_UNICODE),
            'status'       => WorkflowNode::STATUS_RUNNING,
            'execute_time' => time(),
            'error_message' => '',
        ]);

        // 2. 分派给对应的节点执行器
        try {
            $executor = $this->getNodeExecutor($node->node_type);
            if (!$executor) {
                throw new \Exception('未找到节点类型 [' . $node->node_type . '] 的执行器');
            }

            $result = $executor->execute($node, $inputData);

            if ($result['status'] == 1) {
                // 同步完成
                $outputData = $result['data'] ?? [];
                $node->save([
                    'output_data' => json_encode($outputData, JSON_UNESCAPED_UNICODE),
                    'status'      => WorkflowNode::STATUS_SUCCEEDED,
                    'task_id'     => $result['task_id'] ?? '',
                    'finish_time' => time(),
                ]);
                WorkflowLogService::info($node->project_id, $nodeId, 'node_success', '节点执行成功: ' . $node->node_label . ' (' . $node->node_type . ')');

                // 继续调度下游节点
                $this->scheduleReadyNodes($node->project_id, $aid, $bid);
            } elseif ($result['status'] == 2) {
                // 异步任务，进入轮询状态，先保存已有数据
                $outputData = $result['data'] ?? [];
                $node->save([
                    'status'      => WorkflowNode::STATUS_POLLING,
                    'task_id'     => $result['task_id'] ?? '',
                    'output_data' => json_encode($outputData, JSON_UNESCAPED_UNICODE),
                ]);
                WorkflowLogService::info($node->project_id, $nodeId, 'node_polling', '节点进入轮询: ' . $node->node_label . ', task_id=' . ($result['task_id'] ?? ''));
            } else {
                // 执行失败
                $node->save([
                    'status'        => WorkflowNode::STATUS_FAILED,
                    'error_message' => $result['msg'] ?? '执行失败',
                ]);
                WorkflowLogService::error($node->project_id, $nodeId, 'node_fail', '节点执行失败: ' . ($result['msg'] ?? ''));
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('工作流节点执行异常: nodeId=' . $nodeId . ', error=' . $e->getMessage());
            WorkflowLogService::error($node->project_id, $nodeId, 'node_exception', '节点执行异常: ' . $e->getMessage());
            $node->save([
                'status'        => WorkflowNode::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
            return ['status' => 0, 'msg' => $e->getMessage()];
        }
    }

    // ================================================================
    // 上下文传播
    // ================================================================

    /**
     * 收集节点的输入数据（从所有上游节点的output_data按端口映射拼装）
     * 增强版：支持端口映射 + 智能合并，确保多上游数据正确传播
     */
    public function collectInputData($nodeId)
    {
        $edges = WorkflowEdge::where('target_node_id', $nodeId)->select()->toArray();
        $inputData = [];

        foreach ($edges as $edge) {
            $sourceNode = WorkflowNode::find($edge['source_node_id']);
            if (!$sourceNode || $sourceNode->status !== WorkflowNode::STATUS_SUCCEEDED) {
                continue;
            }

            $outputData = $sourceNode->output_data;
            if (is_string($outputData)) {
                $outputData = json_decode($outputData, true);
            }
            if (empty($outputData)) {
                continue;
            }

            $sourcePort = $edge['source_port'] ?? '';
            $targetPort = $edge['target_port'] ?? '';

            if (!empty($sourcePort) && !empty($targetPort)) {
                // 标准端口映射
                if (isset($outputData[$sourcePort])) {
                    $inputData[$targetPort] = $outputData[$sourcePort];
                } else {
                    // 端口名不匹配，尝试传递整个输出
                    $inputData[$targetPort] = $outputData;
                }
            } elseif (!empty($targetPort)) {
                // 没有源端口，直接传递整个输出到目标端口
                $inputData[$targetPort] = $outputData;
            } else {
                // 无端口信息，将整个输出合并到inputData
                $inputData = array_merge($inputData, $outputData);
            }
        }

        return $inputData;
    }

    // ================================================================
    // 辅助方法
    // ================================================================

    /**
     * 重置节点及其所有下游节点状态
     */
    protected function resetNodeAndDownstream($nodeId, $projectId)
    {
        $dag = $this->parseDAG($projectId);
        $visited = [];
        $queue = [$nodeId];

        while (!empty($queue)) {
            $current = array_shift($queue);
            if (in_array($current, $visited)) continue;
            $visited[] = $current;

            foreach ($dag['adjacency'][$current] ?? [] as $downstream) {
                $queue[] = $downstream;
            }
        }

        // 重置（排除起始节点自身，只重置下游）
        $downstreamIds = array_filter($visited, function($id) use ($nodeId) {
            return $id !== $nodeId;
        });

        if (!empty($downstreamIds)) {
            WorkflowNode::whereIn('id', $downstreamIds)->update([
                'status'        => WorkflowNode::STATUS_WAITING,
                'output_data'   => null,
                'input_data'    => null,
                'error_message' => '',
                'task_id'       => '',
            ]);
        }
    }

    /**
     * 获取节点执行器实例
     */
    protected function getNodeExecutor($nodeType)
    {
        $executorMap = [
            WorkflowNode::TYPE_SCRIPT     => \app\service\workflow\ScriptNodeExecutor::class,
            WorkflowNode::TYPE_CHARACTER  => \app\service\workflow\CharacterNodeExecutor::class,
            WorkflowNode::TYPE_STORYBOARD => \app\service\workflow\StoryboardNodeExecutor::class,
            WorkflowNode::TYPE_VIDEO      => \app\service\workflow\VideoNodeExecutor::class,
            WorkflowNode::TYPE_VOICE      => \app\service\workflow\VoiceNodeExecutor::class,
            WorkflowNode::TYPE_COMPOSE    => \app\service\workflow\ComposeNodeExecutor::class,
        ];

        $className = $executorMap[$nodeType] ?? null;
        if (!$className || !class_exists($className)) {
            return null;
        }

        return new $className();
    }

    /**
     * 停止工作流执行
     */
    public function stopWorkflow($projectId)
    {
        // 将运行中/轮询中的节点标记为失败
        WorkflowNode::where('project_id', $projectId)
            ->whereIn('status', [WorkflowNode::STATUS_RUNNING, WorkflowNode::STATUS_POLLING])
            ->update([
                'status'        => WorkflowNode::STATUS_FAILED,
                'error_message' => '用户手动停止',
            ]);

        WorkflowProject::where('id', $projectId)->update(['status' => WorkflowProject::STATUS_FAILED]);
        WorkflowLogService::warn($projectId, 0, 'workflow_stop', '工作流被用户手动停止');

        return ['status' => 1, 'msg' => '工作流已停止'];
    }

    /**
     * 获取工作流整体执行进度
     */
    public function getProgress($projectId)
    {
        $nodes = WorkflowNode::where('project_id', $projectId)->select()->toArray();
        $total     = count($nodes);
        $succeeded = 0;
        $running   = 0;
        $failed    = 0;
        $waiting   = 0;

        foreach ($nodes as $node) {
            switch ($node['status']) {
                case WorkflowNode::STATUS_SUCCEEDED:
                    $succeeded++;
                    break;
                case WorkflowNode::STATUS_RUNNING:
                case WorkflowNode::STATUS_POLLING:
                    $running++;
                    break;
                case WorkflowNode::STATUS_FAILED:
                    $failed++;
                    break;
                default:
                    $waiting++;
            }
        }

        $percent = $total > 0 ? round($succeeded / $total * 100, 1) : 0;

        return [
            'status'    => 1,
            'total'     => $total,
            'succeeded' => $succeeded,
            'running'   => $running,
            'failed'    => $failed,
            'waiting'   => $waiting,
            'percent'   => $percent,
            'nodes'     => array_map(function($n) {
                return [
                    'id'        => $n['id'],
                    'node_type' => $n['node_type'],
                    'status'    => $n['status'],
                    'node_label' => $n['node_label'],
                ];
            }, $nodes),
        ];
    }

    /**
     * 轮询异步节点状态（视频生成等耗时任务）
     */
    public function pollAsyncNodes($projectId, $aid, $bid)
    {
        $pollingNodes = WorkflowNode::where('project_id', $projectId)
            ->where('status', WorkflowNode::STATUS_POLLING)
            ->select();

        $updated = 0;
        foreach ($pollingNodes as $node) {
            $executor = $this->getNodeExecutor($node->node_type);
            if (!$executor || !method_exists($executor, 'pollStatus')) {
                continue;
            }

            $pollResult = $executor->pollStatus($node);
            if ($pollResult['completed'] ?? false) {
                if ($pollResult['success'] ?? false) {
                    $node->save([
                        'output_data' => json_encode($pollResult['data'] ?? [], JSON_UNESCAPED_UNICODE),
                        'status'      => WorkflowNode::STATUS_SUCCEEDED,
                        'finish_time' => time(),
                    ]);
                    WorkflowLogService::info($projectId, $node->id, 'poll_success', '异步节点轮询完成: ' . $node->node_label);
                    $updated++;
                    // 继续调度下游
                    $this->scheduleReadyNodes($projectId, $aid, $bid);
                } else {
                    $node->save([
                        'status'        => WorkflowNode::STATUS_FAILED,
                        'error_message' => $pollResult['msg'] ?? '异步任务失败',
                    ]);
                    WorkflowLogService::error($projectId, $node->id, 'poll_fail', '异步节点失败: ' . ($pollResult['msg'] ?? ''));
                    $updated++;
                }
            }
        }

        return ['status' => 1, 'polled' => count($pollingNodes), 'updated' => $updated];
    }
}
