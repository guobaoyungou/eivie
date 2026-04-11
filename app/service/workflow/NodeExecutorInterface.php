<?php
/**
 * 节点执行器接口
 */
namespace app\service\workflow;

use app\model\WorkflowNode;

interface NodeExecutorInterface
{
    /**
     * 执行节点
     * @param WorkflowNode $node 节点实例
     * @param array $inputData 上游注入的输入数据
     * @return array ['status' => 0|1|2, 'msg' => '', 'data' => [], 'task_id' => '']
     *   status=1: 同步完成; status=2: 异步任务已提交; status=0: 失败
     */
    public function execute(WorkflowNode $node, array $inputData): array;
}
