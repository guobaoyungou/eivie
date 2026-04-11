<?php
/**
 * 工作流资源服务
 * 处理资源CRUD、模型选项查询等
 */
namespace app\service;

use think\facade\Db;
use app\model\WorkflowResource;
use app\model\WorkflowNode;

class WorkflowResourceService
{
    /**
     * 获取资源列表（支持按类型筛选）
     */
    public function getResourceList($aid, $bid, $filters = [], $page = 1, $limit = 20)
    {
        $where = [];

        // 系统预置资源（is_system=1）+ 当前商家资源
        $where[] = ['status', '=', 1];

        if (!empty($filters['resource_type'])) {
            $where[] = ['resource_type', '=', $filters['resource_type']];
        }
        if (!empty($filters['keyword'])) {
            $where[] = ['name', 'like', '%' . $filters['keyword'] . '%'];
        }

        $query = WorkflowResource::where(function($q) use ($aid, $bid) {
            $q->where('is_system', 1)
              ->whereOr(function($q2) use ($aid, $bid) {
                  $q2->where('aid', $aid)->where('bid', $bid);
              });
        })->where($where);

        $count = $query->count();

        $data = WorkflowResource::where(function($q) use ($aid, $bid) {
            $q->where('is_system', 1)
              ->whereOr(function($q2) use ($aid, $bid) {
                  $q2->where('aid', $aid)->where('bid', $bid);
              });
        })->where($where)
          ->page($page, $limit)
          ->order('is_system desc, sort desc, id desc')
          ->select()->toArray();

        foreach ($data as &$item) {
            $item['resource_type_text'] = WorkflowResource::typeTextMap()[$item['resource_type'] ?? ''] ?? '';
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i', $item['create_time']) : '';
            if (is_string($item['content_data'])) {
                $item['content_data'] = json_decode($item['content_data'], true);
            }
        }

        return ['status' => 1, 'count' => $count, 'data' => $data];
    }

    /**
     * 创建或更新资源
     */
    public function saveResource($data)
    {
        $id = intval($data['id'] ?? 0);

        if (empty($data['name'])) {
            return ['status' => 0, 'msg' => '资源名称不能为空'];
        }
        if (empty($data['resource_type'])) {
            return ['status' => 0, 'msg' => '资源类型不能为空'];
        }

        $validTypes = array_keys(WorkflowResource::typeTextMap());
        if (!in_array($data['resource_type'], $validTypes)) {
            return ['status' => 0, 'msg' => '无效的资源类型'];
        }

        $saveData = [
            'name'          => $data['name'],
            'resource_type' => $data['resource_type'],
            'thumbnail'     => $data['thumbnail'] ?? '',
            'content_data'  => isset($data['content_data'])
                ? (is_string($data['content_data']) ? $data['content_data'] : json_encode($data['content_data'], JSON_UNESCAPED_UNICODE))
                : '{}',
            'status' => intval($data['status'] ?? 1),
        ];

        if ($id > 0) {
            $resource = WorkflowResource::find($id);
            if (!$resource) {
                return ['status' => 0, 'msg' => '资源不存在'];
            }
            // 系统预置资源不可修改
            if ($resource->is_system == 1) {
                return ['status' => 0, 'msg' => '系统预置资源不可修改'];
            }
            $resource->save($saveData);
        } else {
            $saveData['aid']       = intval($data['aid'] ?? 0);
            $saveData['bid']       = intval($data['bid'] ?? 0);
            $saveData['mdid']      = intval($data['mdid'] ?? 0);
            $saveData['uid']       = intval($data['uid'] ?? 0);
            $saveData['is_system'] = 0;

            $resource = new WorkflowResource();
            $resource->save($saveData);
            $id = $resource->id;
        }

        return ['status' => 1, 'msg' => '保存成功', 'id' => $id];
    }

    /**
     * 删除资源
     */
    public function deleteResource($id, $aid, $bid)
    {
        $resource = WorkflowResource::where(['id' => $id, 'aid' => $aid, 'bid' => $bid])->find();
        if (!$resource) {
            return ['status' => 0, 'msg' => '资源不存在'];
        }
        if ($resource->is_system == 1) {
            return ['status' => 0, 'msg' => '系统预置资源不可删除'];
        }

        $resource->delete();
        return ['status' => 1, 'msg' => '删除成功'];
    }

    /**
     * 获取节点可用模型列表（按节点类型筛选）
     */
    public function getModelOptions($nodeType)
    {
        // 节点类型到模型类型的映射（支持多类型）
        $nodeModelTypeMap = [
            WorkflowNode::TYPE_SCRIPT     => ['text_generation', 'deep_thinking'],
            WorkflowNode::TYPE_CHARACTER  => ['image_generation'],
            WorkflowNode::TYPE_STORYBOARD => ['image_generation'],
            WorkflowNode::TYPE_VIDEO      => ['video_generation', 'image_to_video'],
            WorkflowNode::TYPE_VOICE      => ['speech_model'],
            WorkflowNode::TYPE_COMPOSE    => null,
        ];

        $modelTypeCodes = $nodeModelTypeMap[$nodeType] ?? null;
        if ($modelTypeCodes === null) {
            return ['status' => 1, 'data' => [], 'msg' => '该节点类型无需选择模型'];
        }

        // 查找模型类型ID列表
        $typeIds = Db::name('model_type')
            ->where('type_code', 'in', $modelTypeCodes)
            ->where('status', 1)
            ->column('id');

        if (empty($typeIds)) {
            return ['status' => 1, 'data' => []];
        }

        // 获取这些类型下的可用模型
        $models = Db::name('model_info')->alias('m')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->field('m.id, m.model_code, m.model_name, m.description, p.provider_name, p.provider_code, t.type_name')
            ->where('m.type_id', 'in', $typeIds)
            ->where('m.is_active', 1)
            ->where('p.status', 1)
            ->order('t.id asc, m.sort asc, m.id desc')
            ->select()->toArray();

        return ['status' => 1, 'data' => $models];
    }

    /**
     * 增加资源使用次数
     */
    public function incrementUsage($resourceId)
    {
        WorkflowResource::where('id', $resourceId)->inc('usage_count')->update();
    }
}
