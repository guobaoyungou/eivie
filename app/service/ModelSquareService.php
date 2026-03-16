<?php
/**
 * 模型广场服务类
 * 提供供应商管理、模型类型管理、模型管理的核心业务逻辑
 */
namespace app\service;

use think\facade\Db;

class ModelSquareService
{
    // ============================================================
    // 供应商管理
    // ============================================================

    /**
     * 获取供应商列表
     */
    public function getProviderList($where = [], $page = 1, $limit = 20, $order = 'sort asc, id desc')
    {
        $query = Db::name('model_provider')->where($where);
        $count = $query->count();
        $data = Db::name('model_provider')->where($where)
            ->page($page, $limit)
            ->order($order)
            ->select()->toArray();
        
        // 附加每个供应商关联的模型数量
        foreach ($data as &$item) {
            $item['model_count'] = Db::name('model_info')->where('provider_id', $item['id'])->count();
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i', $item['create_time']) : '';
        }
        
        return ['count' => $count, 'data' => $data];
    }

    /**
     * 获取供应商详情
     */
    public function getProviderDetail($id)
    {
        $info = Db::name('model_provider')->where('id', $id)->find();
        if ($info && $info['auth_config']) {
            if (is_string($info['auth_config'])) {
                $info['auth_config'] = json_decode($info['auth_config'], true);
            }
        }
        return $info;
    }

    /**
     * 保存供应商
     */
    public function saveProvider($data)
    {
        // 检查数据是否为空
        if (empty($data) || !is_array($data)) {
            return ['status' => 0, 'msg' => '提交数据为空'];
        }

        $id = isset($data['id']) ? intval($data['id']) : 0;

        // 验证必填字段
        if (empty($data['provider_code'])) {
            return ['status' => 0, 'msg' => '供应商标识不能为空'];
        }
        if (empty($data['provider_name'])) {
            return ['status' => 0, 'msg' => '供应商名称不能为空'];
        }

        // 验证provider_code唯一性
        $exists = Db::name('model_provider')
            ->where('provider_code', $data['provider_code'])
            ->where('id', '<>', $id)
            ->find();
        if ($exists) {
            return ['status' => 0, 'msg' => '供应商标识已存在'];
        }
        
        // 处理 auth_config，确保是有效的 JSON
        $authConfig = '{}';
        if (isset($data['auth_config']) && trim($data['auth_config']) !== '') {
            $authConfig = trim($data['auth_config']);
            // 验证 JSON 是否有效
            json_decode($authConfig);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['status' => 0, 'msg' => '认证配置格式错误：' . json_last_error_msg()];
            }
        }

        $saveData = [
            'provider_code' => $data['provider_code'],
            'provider_name' => $data['provider_name'],
            'logo' => $data['logo'] ?? '',
            'website' => $data['website'] ?? '',
            'api_doc_url' => $data['api_doc_url'] ?? '',
            'description' => $data['description'] ?? '',
            'auth_config' => $authConfig,
            'status' => intval($data['status'] ?? 1),
            'sort' => intval($data['sort'] ?? 0),
            'update_time' => time(),
        ];
        
        if ($id > 0) {
            Db::name('model_provider')->where('id', $id)->update($saveData);
        } else {
            $saveData['aid'] = 0;
            $saveData['is_system'] = 0;
            $saveData['create_time'] = time();
            $id = Db::name('model_provider')->insertGetId($saveData);
        }
        
        return ['status' => 1, 'msg' => '保存成功', 'id' => $id];
    }

    /**
     * 删除供应商
     */
    public function deleteProvider($id)
    {
        // 检查是否有关联模型
        $modelCount = Db::name('model_info')->where('provider_id', $id)->count();
        if ($modelCount > 0) {
            return ['status' => 0, 'msg' => '该供应商下有' . $modelCount . '个关联模型，无法删除，请先删除关联模型'];
        }
        
        $info = Db::name('model_provider')->where('id', $id)->find();
        if ($info && $info['is_system'] == 1) {
            return ['status' => 0, 'msg' => '系统预置供应商不可删除'];
        }
        
        Db::name('model_provider')->where('id', $id)->delete();
        return ['status' => 1, 'msg' => '删除成功'];
    }

    /**
     * 更新供应商状态
     */
    public function updateProviderStatus($id, $status)
    {
        Db::name('model_provider')->where('id', $id)->update([
            'status' => intval($status),
            'update_time' => time()
        ]);
        return ['status' => 1, 'msg' => '操作成功'];
    }

    /**
     * 获取所有启用的供应商（用于下拉选择）
     */
    public function getActiveProviders()
    {
        return Db::name('model_provider')
            ->where('status', 1)
            ->order('sort asc, id asc')
            ->column('id, provider_code, provider_name', 'id');
    }

    // ============================================================
    // 模型类型管理
    // ============================================================

    /**
     * 获取类型列表
     */
    public function getTypeList($where = [], $page = 1, $limit = 20, $order = 'sort asc, id desc')
    {
        $query = Db::name('model_type')->where($where);
        $count = $query->count();
        $data = Db::name('model_type')->where($where)
            ->page($page, $limit)
            ->order($order)
            ->select()->toArray();
        
        foreach ($data as &$item) {
            $item['model_count'] = Db::name('model_info')->where('type_id', $item['id'])->count();
            if (is_string($item['input_types'])) {
                $item['input_types_arr'] = json_decode($item['input_types'], true) ?: [];
            } else {
                $item['input_types_arr'] = $item['input_types'] ?: [];
            }
            if (is_string($item['output_types'])) {
                $item['output_types_arr'] = json_decode($item['output_types'], true) ?: [];
            } else {
                $item['output_types_arr'] = $item['output_types'] ?: [];
            }
            $item['input_types_text'] = implode('/', $item['input_types_arr']);
            $item['output_types_text'] = implode('/', $item['output_types_arr']);
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i', $item['create_time']) : '';
        }
        
        return ['count' => $count, 'data' => $data];
    }

    /**
     * 获取类型详情
     */
    public function getTypeDetail($id)
    {
        $info = Db::name('model_type')->where('id', $id)->find();
        if ($info) {
            if (is_string($info['input_types'])) {
                $info['input_types'] = json_decode($info['input_types'], true) ?: [];
            }
            if (is_string($info['output_types'])) {
                $info['output_types'] = json_decode($info['output_types'], true) ?: [];
            }
        }
        return $info;
    }

    /**
     * 保存类型
     */
    public function saveType($data)
    {
        // 检查数据是否为空
        if (empty($data) || !is_array($data)) {
            return ['status' => 0, 'msg' => '提交数据为空'];
        }

        $id = isset($data['id']) ? intval($data['id']) : 0;

        // 验证必填字段
        if (empty($data['type_code'])) {
            return ['status' => 0, 'msg' => '类型标识不能为空'];
        }
        if (empty($data['type_name'])) {
            return ['status' => 0, 'msg' => '类型名称不能为空'];
        }

        // 验证type_code唯一性
        $exists = Db::name('model_type')
            ->where('type_code', $data['type_code'])
            ->where('id', '<>', $id)
            ->find();
        if ($exists) {
            return ['status' => 0, 'msg' => '类型标识已存在'];
        }
        
        $inputTypes = isset($data['input_types']) ? (is_array($data['input_types']) ? $data['input_types'] : explode(',', $data['input_types'])) : [];
        $outputTypes = isset($data['output_types']) ? (is_array($data['output_types']) ? $data['output_types'] : explode(',', $data['output_types'])) : [];
        
        $saveData = [
            'type_code' => $data['type_code'],
            'type_name' => $data['type_name'],
            'icon' => $data['icon'] ?? '',
            'description' => $data['description'] ?? '',
            'input_types' => json_encode(array_values(array_filter($inputTypes)), JSON_UNESCAPED_UNICODE),
            'output_types' => json_encode(array_values(array_filter($outputTypes)), JSON_UNESCAPED_UNICODE),
            'status' => intval($data['status'] ?? 1),
            'sort' => intval($data['sort'] ?? 0),
            'update_time' => time(),
        ];
        
        if ($id > 0) {
            Db::name('model_type')->where('id', $id)->update($saveData);
        } else {
            $saveData['aid'] = 0;
            $saveData['is_system'] = 0;
            $saveData['create_time'] = time();
            $id = Db::name('model_type')->insertGetId($saveData);
        }
        
        return ['status' => 1, 'msg' => '保存成功', 'id' => $id];
    }

    /**
     * 删除类型
     */
    public function deleteType($id)
    {
        $modelCount = Db::name('model_info')->where('type_id', $id)->count();
        if ($modelCount > 0) {
            return ['status' => 0, 'msg' => '该类型下有' . $modelCount . '个关联模型，无法删除'];
        }
        
        $info = Db::name('model_type')->where('id', $id)->find();
        if ($info && $info['is_system'] == 1) {
            return ['status' => 0, 'msg' => '系统预置类型不可删除'];
        }
        
        Db::name('model_type')->where('id', $id)->delete();
        return ['status' => 1, 'msg' => '删除成功'];
    }

    /**
     * 更新类型状态
     */
    public function updateTypeStatus($id, $status)
    {
        Db::name('model_type')->where('id', $id)->update([
            'status' => intval($status),
            'update_time' => time()
        ]);
        return ['status' => 1, 'msg' => '操作成功'];
    }

    /**
     * 获取所有启用的类型（用于下拉选择）
     */
    public function getActiveTypes()
    {
        return Db::name('model_type')
            ->where('status', 1)
            ->order('sort asc, id asc')
            ->column('id, type_code, type_name', 'id');
    }

    // ============================================================
    // 模型管理
    // ============================================================

    /**
     * 获取模型列表
     */
    public function getModelList($where = [], $page = 1, $limit = 20, $order = 'model.sort asc, model.id desc')
    {
        $query = Db::name('model_info')->alias('model')
            ->leftJoin('model_provider provider', 'provider.id = model.provider_id')
            ->leftJoin('model_type type', 'type.id = model.type_id')
            ->where($where);
        
        $count = $query->count();
        $data = Db::name('model_info')->alias('model')
            ->field('model.*, provider.provider_name, provider.provider_code, provider.logo as provider_logo, type.type_name, type.type_code')
            ->leftJoin('model_provider provider', 'provider.id = model.provider_id')
            ->leftJoin('model_type type', 'type.id = model.type_id')
            ->where($where)
            ->page($page, $limit)
            ->order($order)
            ->select()->toArray();
        
        foreach ($data as &$item) {
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i', $item['create_time']) : '';
            $item['task_type_text'] = $item['task_type'] == 'async' ? '异步' : '同步';
            // 商家配置数量
            $item['config_count'] = Db::name('merchant_model_config')->where('model_id', $item['id'])->count();
        }
        
        return ['count' => $count, 'data' => $data];
    }

    /**
     * 获取模型详情
     */
    public function getModelDetail($id)
    {
        $info = Db::name('model_info')->alias('model')
            ->field('model.*, provider.provider_name, provider.provider_code, type.type_name, type.type_code')
            ->leftJoin('model_provider provider', 'provider.id = model.provider_id')
            ->leftJoin('model_type type', 'type.id = model.type_id')
            ->where('model.id', $id)
            ->find();
        
        if ($info) {
            $jsonFields = ['input_schema', 'output_schema', 'pricing_config', 'limits_config', 'capability_tags'];
            foreach ($jsonFields as $field) {
                if (isset($info[$field]) && is_string($info[$field])) {
                    $info[$field] = json_decode($info[$field], true);
                }
            }
        }
        
        return $info;
    }

    /**
     * 保存模型
     */
    public function saveModel($data)
    {
        // 检查数据是否为空
        if (empty($data) || !is_array($data)) {
            return ['status' => 0, 'msg' => '提交数据为空'];
        }

        $id = isset($data['id']) ? intval($data['id']) : 0;

        // 验证必填字段
        if (empty($data['model_code'])) {
            return ['status' => 0, 'msg' => '模型标识不能为空'];
        }
        if (empty($data['model_name'])) {
            return ['status' => 0, 'msg' => '模型名称不能为空'];
        }
        if (empty($data['provider_id'])) {
            return ['status' => 0, 'msg' => '请选择供应商'];
        }
        if (empty($data['type_id'])) {
            return ['status' => 0, 'msg' => '请选择模型类型'];
        }

        // 验证model_code唯一性
        $exists = Db::name('model_info')
            ->where('model_code', $data['model_code'])
            ->where('id', '<>', $id)
            ->find();
        if ($exists) {
            return ['status' => 0, 'msg' => '模型标识已存在'];
        }

        // 验证供应商存在
        $provider = Db::name('model_provider')->where('id', $data['provider_id'])->find();
        if (!$provider) {
            return ['status' => 0, 'msg' => '所选供应商不存在'];
        }

        // 验证类型存在
        $type = Db::name('model_type')->where('id', $data['type_id'])->find();
        if (!$type) {
            return ['status' => 0, 'msg' => '所选模型类型不存在'];
        }
        
        $saveData = [
            'provider_id' => intval($data['provider_id']),
            'type_id' => intval($data['type_id']),
            'model_code' => $data['model_code'],
            'model_name' => $data['model_name'],
            'model_version' => $data['model_version'] ?? '',
            'description' => $data['description'] ?? '',
            'endpoint_url' => $data['endpoint_url'] ?? '',
            'task_type' => $data['task_type'] ?? 'sync',
            'is_active' => intval($data['is_active'] ?? 1),
            'is_recommend' => intval($data['is_recommend'] ?? 0),
            'sort' => intval($data['sort'] ?? 0),
            'update_time' => time(),
        ];
        
        // JSON字段处理
        $jsonFields = ['input_schema', 'output_schema', 'pricing_config', 'limits_config', 'capability_tags'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                $val = $data[$field];
                if (is_string($val)) {
                    // 验证是合法JSON
                    $decoded = json_decode($val, true);
                    $saveData[$field] = ($decoded !== null) ? $val : json_encode([], JSON_UNESCAPED_UNICODE);
                } else {
                    $saveData[$field] = json_encode($val, JSON_UNESCAPED_UNICODE);
                }
            }
        }
        
        if ($id > 0) {
            Db::name('model_info')->where('id', $id)->update($saveData);
        } else {
            $saveData['aid'] = 0;
            $saveData['is_system'] = 0;
            $saveData['create_time'] = time();
            $id = Db::name('model_info')->insertGetId($saveData);
        }
        
        return ['status' => 1, 'msg' => '保存成功', 'id' => $id];
    }

    /**
     * 删除模型
     */
    public function deleteModel($id)
    {
        // 检查是否有商家配置
        $configCount = Db::name('merchant_model_config')->where('model_id', $id)->count();
        if ($configCount > 0) {
            return ['status' => 0, 'msg' => '该模型有' . $configCount . '个商家配置，无法删除'];
        }
        
        $info = Db::name('model_info')->where('id', $id)->find();
        if ($info && $info['is_system'] == 1) {
            return ['status' => 0, 'msg' => '系统预置模型不可删除'];
        }
        
        Db::name('model_info')->where('id', $id)->delete();
        return ['status' => 1, 'msg' => '删除成功'];
    }

    /**
     * 更新模型状态
     */
    public function updateModelStatus($id, $status)
    {
        Db::name('model_info')->where('id', $id)->update([
            'is_active' => intval($status),
            'update_time' => time()
        ]);
        return ['status' => 1, 'msg' => '操作成功'];
    }

    /**
     * 更新模型推荐状态
     */
    public function updateModelRecommend($id, $isRecommend)
    {
        Db::name('model_info')->where('id', $id)->update([
            'is_recommend' => intval($isRecommend),
            'update_time' => time()
        ]);
        return ['status' => 1, 'msg' => '操作成功'];
    }

    // ============================================================
    // 前端模型广场查询方法
    // ============================================================

    /**
     * 获取推荐模型列表（前端首页热门模型Tab用）
     */
    public function getRecommendModels()
    {
        return Db::name('model_info')
            ->alias('m')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->field('m.id, m.model_name, m.model_code, m.description, m.is_recommend, m.capability_tags, p.provider_name, p.logo as provider_logo, t.type_name')
            ->where('m.is_active', 1)
            ->where('m.is_recommend', 1)
            ->order('m.sort asc, m.id desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取指定供应商下的模型列表（前端供应商Tab懒加载用）
     */
    public function getModelsByProvider($providerId, $page = 1, $limit = 20)
    {
        return Db::name('model_info')
            ->alias('m')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->field('m.id, m.model_name, m.model_code, m.description, m.is_recommend, m.capability_tags, p.provider_name, p.logo as provider_logo, t.type_name')
            ->where('m.is_active', 1)
            ->where('m.provider_id', intval($providerId))
            ->order('m.is_recommend desc, m.sort asc, m.id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取前端展示用模型详情（含input_schema等完整信息）
     */
    public function getModelFrontDetail($id)
    {
        $info = Db::name('model_info')
            ->alias('m')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->leftJoin('model_type t', 'm.type_id = t.id')
            ->field('m.id, m.model_name, m.model_code, m.description, m.task_type, m.is_recommend, m.capability_tags, m.input_schema, m.output_schema, p.provider_name, p.logo as provider_logo, t.type_name, t.type_code, t.icon as type_icon, t.input_types as type_input_types, t.output_types as type_output_types')
            ->where('m.id', intval($id))
            ->where('m.is_active', 1)
            ->find();

        if ($info) {
            $jsonFields = ['input_schema', 'output_schema', 'capability_tags', 'type_input_types', 'type_output_types'];
            foreach ($jsonFields as $field) {
                if (isset($info[$field]) && is_string($info[$field])) {
                    $decoded = json_decode($info[$field], true);
                    $info[$field] = $decoded !== null ? $decoded : [];
                }
            }
        }

        return $info;
    }

    /**
     * 获取模型关联的推荐场景模板（弹窗第四排展示用）
     */
    public function getModelSceneTemplates($modelId, $limit = 8)
    {
        return Db::name('generation_scene_template')
            ->field('id, template_name, cover_image, description, base_price, use_count')
            ->where('model_id', intval($modelId))
            ->where('status', 1)
            ->order('sort desc, use_count desc, id desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取所有启用的供应商列表（前端Tab渲染用）
     */
    public function getActiveProviderList()
    {
        return Db::name('model_provider')
            ->field('id, provider_code, provider_name, logo')
            ->where('status', 1)
            ->order('sort asc, id asc')
            ->select()
            ->toArray();
    }
}
