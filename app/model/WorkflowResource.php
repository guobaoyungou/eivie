<?php
/**
 * 工作流资源模型
 */
namespace app\model;

use think\Model;

class WorkflowResource extends Model
{
    protected $name = 'workflow_resource';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    protected $json = ['content_data'];
    protected $jsonAssoc = true;

    // 资源类型常量
    const TYPE_CHARACTER = 'character';
    const TYPE_STYLE     = 'style';
    const TYPE_VOICE     = 'voice';
    const TYPE_MATERIAL  = 'material';

    /**
     * 资源类型文本映射
     */
    public static function typeTextMap()
    {
        return [
            self::TYPE_CHARACTER => '角色资源',
            self::TYPE_STYLE     => '风格资源',
            self::TYPE_VOICE     => '音色资源',
            self::TYPE_MATERIAL  => '素材资源',
        ];
    }

    /**
     * 获取类型文本
     */
    public function getResourceTypeTextAttr($value, $data)
    {
        $map = self::typeTextMap();
        return $map[$data['resource_type'] ?? ''] ?? '未知';
    }

    /**
     * 获取资源列表
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
            $item['resource_type_text'] = self::typeTextMap()[$item['resource_type'] ?? ''] ?? '';
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i', $item['create_time']) : '';
        }

        return ['count' => $count, 'data' => $data];
    }
}
