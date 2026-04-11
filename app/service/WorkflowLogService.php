<?php
/**
 * 工作流执行日志服务
 * 记录工作流执行过程中的关键事件，用于排查问题和审计
 */
namespace app\service;

use think\facade\Db;

class WorkflowLogService
{
    /**
     * 记录日志
     */
    public static function log($projectId, $nodeId, $level, $logType, $message, $context = [])
    {
        try {
            Db::name('workflow_exec_log')->insert([
                'project_id'  => intval($projectId),
                'node_id'     => intval($nodeId),
                'aid'         => intval($context['aid'] ?? 0),
                'bid'         => intval($context['bid'] ?? 0),
                'log_level'   => $level,
                'log_type'    => $logType,
                'message'     => mb_substr($message, 0, 5000),
                'context'     => json_encode($context, JSON_UNESCAPED_UNICODE),
                'create_time' => time(),
            ]);
        } catch (\Exception $e) {
            // 日志写入失败不影响主流程
        }
    }

    /**
     * 记录info级别日志
     */
    public static function info($projectId, $nodeId, $logType, $message, $context = [])
    {
        self::log($projectId, $nodeId, 'info', $logType, $message, $context);
    }

    /**
     * 记录warn级别日志
     */
    public static function warn($projectId, $nodeId, $logType, $message, $context = [])
    {
        self::log($projectId, $nodeId, 'warn', $logType, $message, $context);
    }

    /**
     * 记录error级别日志
     */
    public static function error($projectId, $nodeId, $logType, $message, $context = [])
    {
        self::log($projectId, $nodeId, 'error', $logType, $message, $context);
    }

    /**
     * 获取项目执行日志列表
     */
    public static function getProjectLogs($projectId, $page = 1, $limit = 50, $filters = [])
    {
        $query = Db::name('workflow_exec_log')
            ->where('project_id', $projectId);

        if (!empty($filters['log_level'])) {
            $query->where('log_level', $filters['log_level']);
        }
        if (!empty($filters['node_id'])) {
            $query->where('node_id', $filters['node_id']);
        }
        if (!empty($filters['log_type'])) {
            $query->where('log_type', $filters['log_type']);
        }

        $count = $query->count();
        $data = Db::name('workflow_exec_log')
            ->where('project_id', $projectId)
            ->when(!empty($filters['log_level']), function($q) use ($filters) {
                $q->where('log_level', $filters['log_level']);
            })
            ->when(!empty($filters['node_id']), function($q) use ($filters) {
                $q->where('node_id', $filters['node_id']);
            })
            ->when(!empty($filters['log_type']), function($q) use ($filters) {
                $q->where('log_type', $filters['log_type']);
            })
            ->order('id desc')
            ->page($page, $limit)
            ->select()->toArray();

        foreach ($data as &$item) {
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';
            if (is_string($item['context'])) {
                $item['context'] = json_decode($item['context'], true);
            }
        }

        return ['count' => $count, 'data' => $data];
    }

    /**
     * 清理指定天数前的旧日志
     */
    public static function cleanOldLogs($days = 30)
    {
        $cutoff = time() - ($days * 86400);
        return Db::name('workflow_exec_log')
            ->where('create_time', '<', $cutoff)
            ->delete();
    }
}
