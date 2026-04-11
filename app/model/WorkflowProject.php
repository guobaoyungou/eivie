<?php
/**
 * 工作流项目模型
 */
namespace app\model;

use think\Model;
use think\facade\Db;

class WorkflowProject extends Model
{
    protected $name = 'workflow_project';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $json = ['canvas_data'];
    protected $jsonAssoc = true;

    // 创作模式常量
    const MODE_ONECLICK  = 'oneclick';
    const MODE_FREESTYLE = 'freestyle';
    const MODE_ADVANCED  = 'advanced';

    // 状态常量
    const STATUS_DRAFT     = 'draft';
    const STATUS_RUNNING   = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';

    /**
     * 模式文本映射
     */
    public static function modeTextMap()
    {
        return [
            self::MODE_ONECLICK  => '小白一键生成',
            self::MODE_FREESTYLE => '自由编排',
            self::MODE_ADVANCED  => '高级微调',
        ];
    }

    /**
     * 状态文本映射
     */
    public static function statusTextMap()
    {
        return [
            self::STATUS_DRAFT     => '草稿',
            self::STATUS_RUNNING   => '运行中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_FAILED    => '失败',
        ];
    }

    /**
     * 获取创作模式文本
     */
    public function getCreationModeTextAttr($value, $data)
    {
        $map = self::modeTextMap();
        return $map[$data['creation_mode'] ?? ''] ?? '未知';
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $map = self::statusTextMap();
        return $map[$data['status'] ?? ''] ?? '未知';
    }

    /**
     * 关联节点
     */
    public function nodes()
    {
        return $this->hasMany(WorkflowNode::class, 'project_id', 'id');
    }

    /**
     * 关联连线
     */
    public function edges()
    {
        return $this->hasMany(WorkflowEdge::class, 'project_id', 'id');
    }

    /**
     * 关联角色身份卡
     */
    public function characterCards()
    {
        return $this->hasMany(WorkflowCharacterIdCard::class, 'project_id', 'id');
    }

    /**
     * 获取项目列表
     */
    public static function getList($where, $page = 1, $limit = 20, $order = 'id desc')
    {
        $query = self::where($where);
        $count = $query->count();

        $data = self::where($where)
            ->page($page, $limit)
            ->order($order)
            ->select()->toArray();

        foreach ($data as &$item) {
            $item['creation_mode_text'] = self::modeTextMap()[$item['creation_mode'] ?? ''] ?? '';
            $item['status_text'] = self::statusTextMap()[$item['status'] ?? ''] ?? '';
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i', $item['create_time']) : '';
            $item['update_time_text'] = $item['update_time'] ? date('Y-m-d H:i', $item['update_time']) : '';
            $item['node_count'] = Db::name('workflow_node')->where('project_id', $item['id'])->count();
        }

        return ['count' => $count, 'data' => $data];
    }
}
