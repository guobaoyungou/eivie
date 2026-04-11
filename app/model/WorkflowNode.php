<?php
/**
 * 工作流节点实例模型
 */
namespace app\model;

use think\Model;
use think\facade\Db;

class WorkflowNode extends Model
{
    protected $name = 'workflow_node';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $json = ['config_params', 'input_data', 'output_data'];
    protected $jsonAssoc = true;

    // 节点类型常量
    const TYPE_SCRIPT     = 'script';
    const TYPE_CHARACTER  = 'character';
    const TYPE_STORYBOARD = 'storyboard';
    const TYPE_VIDEO      = 'video';
    const TYPE_VOICE      = 'voice';
    const TYPE_COMPOSE    = 'compose';

    // 节点状态常量
    const STATUS_IDLE       = 'idle';
    const STATUS_CONFIGURED = 'configured';
    const STATUS_WAITING    = 'waiting';
    const STATUS_READY      = 'ready';
    const STATUS_RUNNING    = 'running';
    const STATUS_POLLING    = 'polling';
    const STATUS_SUCCEEDED  = 'succeeded';
    const STATUS_FAILED     = 'failed';

    /**
     * 节点类型文本映射
     */
    public static function typeTextMap()
    {
        return [
            self::TYPE_SCRIPT     => '📝 剧本节点',
            self::TYPE_CHARACTER  => '🎭 角色节点',
            self::TYPE_STORYBOARD => '🎬 分镜节点',
            self::TYPE_VIDEO      => '📹 视频节点',
            self::TYPE_VOICE      => '🔊 配音节点',
            self::TYPE_COMPOSE    => '🎞️ 合成节点',
        ];
    }

    /**
     * 节点状态文本映射
     */
    public static function statusTextMap()
    {
        return [
            self::STATUS_IDLE       => '空闲',
            self::STATUS_CONFIGURED => '已配置',
            self::STATUS_WAITING    => '等待上游',
            self::STATUS_READY      => '就绪',
            self::STATUS_RUNNING    => '运行中',
            self::STATUS_POLLING    => '轮询中',
            self::STATUS_SUCCEEDED  => '成功',
            self::STATUS_FAILED     => '失败',
        ];
    }

    /**
     * 节点输出端口定义
     */
    public static function outputPorts()
    {
        return [
            self::TYPE_SCRIPT     => ['characters', 'scenes', 'dialogue'],
            self::TYPE_CHARACTER  => ['character_assets'],
            self::TYPE_STORYBOARD => ['frames'],
            self::TYPE_VIDEO      => ['clips'],
            self::TYPE_VOICE      => ['audio_clips'],
            self::TYPE_COMPOSE    => ['final_video'],
        ];
    }

    /**
     * 节点输入端口定义
     */
    public static function inputPorts()
    {
        return [
            self::TYPE_SCRIPT     => [],
            self::TYPE_CHARACTER  => ['characters'],
            self::TYPE_STORYBOARD => ['scenes', 'character_assets'],
            self::TYPE_VIDEO      => ['frames'],
            self::TYPE_VOICE      => ['dialogue'],
            self::TYPE_COMPOSE    => ['clips', 'audio_clips'],
        ];
    }

    /**
     * 连线兼容性校验
     */
    public static function isConnectionValid($sourceType, $sourcePort, $targetType, $targetPort)
    {
        $compatibilityMatrix = [
            self::TYPE_SCRIPT => [
                'characters' => [self::TYPE_CHARACTER => 'characters'],
                'scenes'     => [self::TYPE_STORYBOARD => 'scenes'],
                'dialogue'   => [self::TYPE_VOICE => 'dialogue'],
            ],
            self::TYPE_CHARACTER => [
                'character_assets' => [self::TYPE_STORYBOARD => 'character_assets'],
            ],
            self::TYPE_STORYBOARD => [
                'frames' => [self::TYPE_VIDEO => 'frames'],
            ],
            self::TYPE_VIDEO => [
                'clips' => [self::TYPE_COMPOSE => 'clips'],
            ],
            self::TYPE_VOICE => [
                'audio_clips' => [self::TYPE_COMPOSE => 'audio_clips'],
            ],
        ];

        if (!isset($compatibilityMatrix[$sourceType][$sourcePort][$targetType])) {
            return false;
        }

        return $compatibilityMatrix[$sourceType][$sourcePort][$targetType] === $targetPort;
    }

    /**
     * 关联项目
     */
    public function project()
    {
        return $this->belongsTo(WorkflowProject::class, 'project_id', 'id');
    }

    /**
     * 获取节点类型文本
     */
    public function getNodeTypeTextAttr($value, $data)
    {
        $map = self::typeTextMap();
        return $map[$data['node_type'] ?? ''] ?? '未知';
    }

    /**
     * 获取节点状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $map = self::statusTextMap();
        return $map[$data['status'] ?? ''] ?? '未知';
    }

    /**
     * 判断节点是否终态
     */
    public function isTerminal()
    {
        return in_array($this->status, [self::STATUS_SUCCEEDED, self::STATUS_FAILED]);
    }

    /**
     * 判断节点是否成功
     */
    public function isSucceeded()
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }
}
