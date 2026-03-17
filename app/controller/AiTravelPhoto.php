<?php
/**
 * AI旅拍系统 - 商家后台管理
 * 
 * @package app\controller
 * @author AI Assistant
 * @date 2026-01-21
 */

namespace app\controller;

use think\facade\Db;
use think\facade\Log;
use think\facade\View;
use app\common\AiTravelPhotoService;
use app\service\AiModelService;

class AiTravelPhoto extends Common
{
    /**
     * 首页 - 数据统计
     */
    public function index()
    {
        return $this->statistics();
    }

    /**
     * 测试页面 - 调试用
     */
    public function test()
    {
        try {
            echo "<h3>测试开始</h3>";
            echo "aid: " . $this->aid . "<br>";
            echo "bid: " . $this->bid . "<br>";
            echo "uid: " . $this->uid . "<br>";
            
            // 测试数据库连接
            $tables = Db::query("SHOW TABLES LIKE 'ddwx_ai_travel_photo%'");
            echo "<h4>数据库表列表：</h4>";
            echo "<pre>";
            print_r($tables);
            echo "</pre>";
            
            // 测试查询scene表
            $count = Db::name('ai_travel_photo_scene')->count();
            echo "<h4>scene表总数：" . $count . "</h4>";
            
            // 测试查询business表
            $business = Db::name('business')->where('id', $this->bid)->find();
            echo "<h4>商家信息：</h4>";
            echo "<pre>";
            print_r($business);
            echo "</pre>";
            
            // 测试scene_list方法
            echo "<h4>测试scene_list AJAX请求：</h4>";
            $where = [
                ['aid', '=', $this->aid],
                ['bid', '=', $this->bid]
            ];
            $list = Db::name('ai_travel_photo_scene')->where($where)->select();
            echo "查询条件: aid=" . $this->aid . ", bid=" . $this->bid . "<br>";
            echo "查询结果数: " . count($list) . "<br>";
            
            // 测试scene_edit方法
            echo "<h4>测试scene_edit加载：</h4>";
            echo "开始查询AI模型...<br>";
            $models = Db::name('ai_travel_photo_model')
                ->where('aid', $this->aid)
                ->where('status', 1)
                ->select();
            if (!$models) {
                $models = [];
            }
            echo "AI模型数量: " . count($models) . "<br>";
            
            // 测试视图文件是否存在
            echo "<h4>检查视图文件：</h4>";
            $viewPath = app()->getRootPath() . 'app/view/ai_travel_photo/scene_edit.html';
            if (file_exists($viewPath)) {
                echo "视图文件存在: " . $viewPath . "<br>";
                echo "文件大小: " . filesize($viewPath) . " 字节<br>";
            } else {
                echo "<span style='color:red'>视图文件不存在: " . $viewPath . "</span><br>";
            }
            
            echo "<h3>测试成功！</h3>";
        } catch (\Exception $e) {
            echo "<h3 style='color:red'>错误信息：</h3>";
            echo "<pre style='color:red'>" . $e->getMessage() . "</pre>";
            echo "<h4>错误详情：</h4>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        die();
    }

    /**
     * 场景管理 - 列表
     */
    public function scene_list()
    {
        // 如果是AJAX请求，返回JSON数据
        if (request()->isAjax()) {
            try {
                // 超级管理员bid为0时，使用aid对应的第一个商家
                $targetBid = $this->bid;
                if ($targetBid == 0) {
                    $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
                }
                
                // 获取场景列表
                $where = [
                    ['s.aid', '=', $this->aid],
                    ['s.bid', '=', $targetBid]
                ];

                // 场景类型筛选
                $scene_type = input('param.scene_type', '');
                if ($scene_type !== '') {
                    $where[] = ['s.scene_type', '=', $scene_type];
                }

                // 分类筛选
                $category = input('param.category', '');
                if ($category) {
                    $where[] = ['s.category', '=', $category];
                }

                // 状态筛选
                $status = input('param.status', '');
                if ($status !== '') {
                    $where[] = ['s.status', '=', $status];
                }
                
                // 门店筛选
                $mdid = input('param.mdid', '');
                if ($mdid !== '') {
                    $where[] = ['s.mdid', '=', $mdid];
                }
                
                // 公共/私有筛选
                $is_public = input('param.is_public', '');
                if ($is_public !== '') {
                    $where[] = ['s.is_public', '=', $is_public];
                }

                $page = input('page/d', 1);
                $limit = input('limit/d', 20);

                $list = Db::name('ai_travel_photo_scene')
                    ->alias('s')
                    ->leftJoin('mendian m', 's.mdid = m.id')
                    ->where($where)
                    ->field('s.*, m.name as mendian_name')
                    ->order('s.sort DESC, s.id DESC')
                    ->page($page, $limit)
                    ->select()
                    ->each(function($item) {
                        // 添加场景类型文本
                        $sceneTypes = config('ai_travel_photo.scene_type');
                        $item['scene_type_text'] = $sceneTypes[$item['scene_type']] ?? '未知类型';
                        return $item;
                    });

                $count = Db::name('ai_travel_photo_scene')
                    ->alias('s')
                    ->leftJoin('mendian m', 's.mdid = m.id')
                    ->where($where)
                    ->count();

                return json([
                    'code' => 0,
                    'msg' => '',
                    'count' => $count,
                    'data' => $list
                ]);
            } catch (\Exception $e) {
                return json([
                    'code' => 1,
                    'msg' => $e->getMessage(),
                    'count' => 0,
                    'data' => []
                ]);
            }
        }

        try {
            // 超级管理员bid为0时，使用aid对应的第一个商家
            $targetBid = $this->bid;
            if ($targetBid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
            }
            
            // 获取分类列表
            $categories = Db::name('ai_travel_photo_scene')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->group('category')
                ->column('category');
            
            // 获取场景类型列表
            $scene_types = config('ai_travel_photo.scene_type');
            
            // 获取门店列表（兼容超级管理员和公共门店）
            $mendianWhere = [['aid', '=', $this->aid]];
            if ($this->bid == 0) {
                // 超级管理员：查看商家门店 + bid=0的公共门店
                $mendianWhere[] = ['bid', 'in', [$targetBid, 0]];
            } else {
                // 普通商家：仅查看自己的门店
                $mendianWhere[] = ['bid', '=', $this->bid];
            }
            $mendian_list = Db::name('mendian')
                ->where($mendianWhere)
                ->field('id, name')
                ->order('id ASC')
                ->select();

            View::assign('categories', $categories);
            View::assign('scene_types', $scene_types);
            View::assign('mendian_list', $mendian_list);
            return View::fetch();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 场景管理 - 添加/编辑 (简化测试版)
     */
    public function scene_edit_simple()
    {
        $id = input('param.id/d', 0);
        
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>测试</title></head><body>";
        echo "<h1>场景编辑测试页面</h1>";
        echo "<p>aid: " . $this->aid . "</p>";
        echo "<p>bid: " . $this->bid . "</p>";
        echo "<p>id: " . $id . "</p>";
        
        $info = [
            'id' => 0,
            'name' => '测试场景',
            'category' => '风景'
        ];
        
        echo "<p>info: " . json_encode($info, JSON_UNESCAPED_UNICODE) . "</p>";
        echo "<p>如果你看到这个页面，说明控制器工作正常，问题在于模板渲染</p>";
        echo "</body></html>";
        die();
    }

    /**
     * 场景管理 - 添加/编辑
     */
    public function scene_edit()
    {
        $id = input('param.id/d', 0);

        if (request()->isPost()) {
            try {
                $data = input('post.');
                
                // 超级管理员bid为0时，使用aid对应的第一个商家
                $targetBid = $this->bid;
                if ($targetBid == 0) {
                    $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
                }
                
                $data['aid'] = $this->aid;
                $data['bid'] = $targetBid;
                
                // 处理scene_type字段（默认1）
                $data['scene_type'] = isset($data['scene_type']) ? intval($data['scene_type']) : 1;
                
                // 处理mdid字段（默认0）
                $data['mdid'] = isset($data['mdid']) ? intval($data['mdid']) : 0;
                
                // 处理is_public字段（checkbox未选中时不传值）
                $data['is_public'] = isset($data['is_public']) ? 1 : 0;
                
                // 处理is_recommend字段
                $data['is_recommend'] = isset($data['is_recommend']) ? 1 : 0;
                
                // 处理api_config_id字段
                $data['api_config_id'] = isset($data['api_config_id']) ? intval($data['api_config_id']) : 0;
                
                // 提取所有 param_ 开头的字段，合并为model_params
                $modelParams = [];
                foreach ($data as $key => $value) {
                    if (strpos($key, 'param_') === 0) {
                        $paramCode = substr($key, 6); // 去掉 param_ 前缀
                        $modelParams[$paramCode] = $value;
                        unset($data[$key]); // 从主数据中移除
                    }
                }
                
                // 查询模型参数类型定义，将array类型参数的逗号分隔值转换为数组
                if (!empty($modelParams) && !empty($data['model_id'])) {
                    $paramTypes = Db::name('ai_model_parameter')
                        ->where('model_id', intval($data['model_id']))
                        ->column('param_type', 'param_name');
                    
                    foreach ($modelParams as $pCode => &$pValue) {
                        if (isset($paramTypes[$pCode]) && $paramTypes[$pCode] === 'array' && is_string($pValue) && !empty($pValue)) {
                            // 将逗号分隔的URL字符串转换为数组
                            $urls = array_filter(array_map('trim', explode(',', $pValue)));
                            $pValue = array_values($urls);
                        }
                    }
                    unset($pValue);
                }
                
                // 保存为JSON
                if (!empty($modelParams)) {
                    $data['model_params'] = json_encode($modelParams, JSON_UNESCAPED_UNICODE);
                }
                
                // 检查是否需要生成封面图
                $generateCover = input('generate_cover/d', 0);

                if ($id > 0) {
                    $data['update_time'] = time();
                    Db::name('ai_travel_photo_scene')->where('id', $id)->update($data);
                    $sceneId = $id;
                    $msg = '保存成功';
                } else {
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    $sceneId = Db::name('ai_travel_photo_scene')->insertGetId($data);
                    $msg = '添加成功';
                }
                
                // 如果需要生成封面图
                if ($generateCover == 1 && $sceneId > 0) {
                    // 返回场景ID，前端在收到响应后调用生成封面图接口
                    return json(['status' => 1, 'msg' => $msg, 'scene_id' => $sceneId, 'need_generate' => true]);
                }
                
                return json(['status' => 1, 'msg' => $msg, 'scene_id' => $sceneId]);
            } catch (\Exception $e) {
                return json(['status' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
            }
        }

        try {
            // 记录调试日志
            trace('[场景编辑] 开始加载页面, ID=' . $id, 'info');
            
            // 初始化场景信息，确保所有字段都有默认值
            $info = [
                'id' => 0,
                'scene_type' => 1,
                'name' => '',
                'category' => '',
                'cover' => '',
                'background_url' => '',
                'desc' => '',
                'prompt' => '',
                'prompt_en' => '',
                'negative_prompt' => '',
                'video_prompt' => '',
                'model_id' => 0,
                'api_config_id' => 0,
                'model_params' => '{}',
                'aspect_ratio' => '1:1',
                'sort' => 0,
                'tags' => '',
                'status' => 1,
                'is_public' => 0,
                'is_recommend' => 0
            ];
            
            if ($id > 0) {
                // 超级管理员bid为0时，使用aid对应的第一个商家
                $targetBid = $this->bid;
                if ($targetBid == 0) {
                    $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
                }
                
                trace('[场景编辑] 查询场景数据, targetBid=' . $targetBid, 'info');
                
                $sceneData = Db::name('ai_travel_photo_scene')
                    ->where('id', $id)
                    ->where('aid', $this->aid)
                    ->where('bid', $targetBid)
                    ->find();
                if (!$sceneData) {
                    trace('[场景编辑] 场景不存在', 'error');
                    $this->error('场景不存在');
                }
                trace('[场景编辑] 场景数据查询成功', 'info');
                $info = array_merge($info, $sceneData);
            }

            // 获取AI模型列表（从ai_model_instance表查询）
            trace('[场景编辑] 开始查询模型列表', 'info');
            
            $models = [];
            try {
                // 查询启用的AI模型实例
                $models = Db::name('ai_model_instance')
                    ->where('is_active', 1)
                    ->field('id, model_code, model_name, category_code, provider')
                    ->order('sort ASC, id DESC')
                    ->select();
                
                if (!$models) {
                    $models = [];
                }
                
                // 转换为数组
                $models = $models ? $models->toArray() : [];
                trace('[场景编辑] 模型列表查询成功，数量：' . count($models), 'info');
            } catch (\Exception $e) {
                trace('[场景编辑] 模型查询失败: ' . $e->getMessage(), 'error');
                $models = [];
            }
            
            // 获取门店列表（兼容超级管理员和公共门店）
            // 超级管理员bid为0时，使用aid对应的第一个商家
            $targetBid = $this->bid;
            if ($targetBid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
            }
            
            $mendianWhere = [['aid', '=', $this->aid]];
            if ($this->bid == 0) {
                // 超级管理员：查看商家门店 + bid=0的公共门店
                $mendianWhere[] = ['bid', 'in', [$targetBid, 0]];
            } else {
                // 普通商家：仅查看自己的门店
                $mendianWhere[] = ['bid', '=', $this->bid];
            }
            
            trace('[场景编辑] 开始查询门店列表', 'info');
            $mendian_list = Db::name('mendian')
                ->where($mendianWhere)
                ->field('id, name')
                ->order('id ASC')
                ->select();
            trace('[场景编辑] 门店列表查询成功，数量：' . count($mendian_list), 'info');

            trace('[场景编辑] 开始渲染视图', 'info');
            
            // 获取场景类型列表
            $scene_types = config('ai_travel_photo.scene_type');
            
            View::assign('info', $info);
            View::assign('models', $models);
            View::assign('mendian_list', $mendian_list);
            View::assign('scene_types', $scene_types);
            
            trace('[场景编辑] 视图渲染成功', 'info');
            return View::fetch();
        } catch (\Exception $e) {
            trace('[场景编辑] 页面加载失败: ' . $e->getMessage(), 'error');
            trace('[场景编辑] 异常堆栈: ' . $e->getTraceAsString(), 'error');
            $this->error('页面加载失败：' . $e->getMessage());
        }
    }

    /**
     * 场景管理 - 删除
     */
    public function scene_delete()
    {
        $id = input('post.id/d');

        // 检查是否被使用
        $used = Db::name('ai_travel_photo_generation')
            ->where('scene_id', $id)
            ->count();

        if ($used > 0) {
            return json(['status' => 0, 'msg' => '该场景已被使用，不能删除']);
        }

        Db::name('ai_travel_photo_scene')->where('id', $id)->delete();
        return json(['status' => 1, 'msg' => '删除成功']);
    }

    /**
     * 场景管理 - 批量操作
     */
    public function scene_batch()
    {
        $action = input('post.action');
        $ids = input('post.ids/a', []);

        if (empty($ids)) {
            return json(['status' => 0, 'msg' => '请选择要操作的场景']);
        }

        $where = [
            ['id', 'in', $ids],
            ['aid', '=', $this->aid],
            ['bid', '=', $this->bid]
        ];

        switch ($action) {
            case 'enable':
                Db::name('ai_travel_photo_scene')->where($where)->update(['status' => 1]);
                return json(['status' => 1, 'msg' => '批量启用成功']);

            case 'disable':
                Db::name('ai_travel_photo_scene')->where($where)->update(['status' => 0]);
                return json(['status' => 1, 'msg' => '批量禁用成功']);

            case 'delete':
                Db::name('ai_travel_photo_scene')->where($where)->delete();
                return json(['status' => 1, 'msg' => '批量删除成功']);

            default:
                return json(['status' => 0, 'msg' => '未知操作']);
        }
    }

    /**
     * 获取AI模型的参数定义（AJAX接口）
     * 用于场景编辑时动态加载模型所需参数
     */
    public function get_model_params()
    {
        $modelId = input('model_id/d', 0);
        
        if ($modelId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误', 'data' => []]);
        }
        
        try {
            // 查询模型参数定义
            $params = Db::name('ai_model_parameter')
                ->where('model_id', $modelId)
                ->order('sort ASC, id ASC')
                ->select();
            
            if (!$params) {
                $params = [];
            }
            
            return json(['code' => 0, 'msg' => '获取成功', 'data' => $params]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '查询失败：' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * 获取模型关联的API配置列表（AJAX接口）
     * 用于场景编辑时选择API配置
     */
    public function get_model_api_configs()
    {
        $modelId = input('model_id/d', 0);
        
        if ($modelId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误', 'data' => []]);
        }
        
        try {
            // 查询该模型的API配置
            // 权限筛选：自己的配置 或 公开的配置（scope_type=1）
            $bid = $this->bid;
            $configs = Db::name('api_config')
                ->where('model_id', $modelId)
                ->where('is_active', 1)
                ->where(function($query) use ($bid) {
                    // 自己的配置
                    $query->where('bid', $bid)
                          // 或者是公开的配置
                          ->whereOr('scope_type', 1);
                })
                ->field('id, api_name, provider, scope_type')
                ->order('is_system DESC, sort ASC, id ASC')
                ->select();
            
            if (!$configs) {
                $configs = [];
            }
            
            return json(['code' => 0, 'msg' => '获取成功', 'data' => $configs]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '查询失败：' . $e->getMessage(), 'data' => []]);
        }
    }

    /**
     * ==================================================================
     * 以下为场景配置新接口（支持6种场景类型）
     * ==================================================================
     */
    
    /**
     * 1. 获取模型列表
     * GET /AiTravelPhoto/get_model_list?aid=1
     */
    public function get_model_list()
    {
        try {
            $sceneConfigService = new \app\service\SceneConfigService();
            $models = $sceneConfigService->getEnabledModelList($this->aid, $this->bid);
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'data' => $models
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 2. 获取场景类型
     * GET /AiTravelPhoto/get_scene_types?model_id=3
     */
    public function get_scene_types()
    {
        $modelId = input('model_id/d', 0);
        
        if ($modelId <= 0) {
            return json(['code' => 1, 'msg' => '请选择模型', 'data' => []]);
        }
        
        try {
            $sceneConfigService = new \app\service\SceneConfigService();
            $sceneTypes = $sceneConfigService->getModelSupportedSceneTypes($modelId);
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'data' => $sceneTypes
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 3. 获取模型参数列表（新版）
     * GET /AiTravelPhoto/get_model_parameters?model_id=3&scene_type=4
     */
    public function get_model_parameters()
    {
        $modelId = input('model_id/d', 0);
        $sceneType = input('scene_type/d', 0);
        
        if ($modelId <= 0) {
            return json(['code' => 1, 'msg' => '请选择模型', 'data' => []]);
        }
        
        try {
            $sceneConfigService = new \app\service\SceneConfigService();
            $params = $sceneConfigService->getModelParameters($modelId, $sceneType);
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'data' => $params
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => ['required_params' => [], 'optional_params' => []]
            ]);
        }
    }
    
    /**
     * 4. 获取场景参数模板
     * GET /AiTravelPhoto/get_scene_template?scene_type=4
     */
    public function get_scene_template()
    {
        $sceneType = input('scene_type/d', 0);
        
        if ($sceneType <= 0) {
            return json(['code' => 1, 'msg' => '请选择场景类型', 'data' => []]);
        }
        
        try {
            $sceneConfigService = new \app\service\SceneConfigService();
            $template = $sceneConfigService->getSceneTemplate($sceneType);
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'data' => $template
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 5. 保存场景配置（新版）
     * POST /AiTravelPhoto/scene_save_new
     */
    public function scene_save_new()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }
        
        try {
            $data = input('post.');
            
            // 设置平台和商家ID
            $data['aid'] = $this->aid;
            $data['bid'] = $this->bid;
            
            $sceneConfigService = new \app\service\SceneConfigService();
            $sceneId = $sceneConfigService->saveSceneConfig($data);
            
            return json([
                'code' => 0,
                'msg' => '保存成功',
                'data' => ['scene_id' => $sceneId]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 6. 获取场景详情
     * GET /AiTravelPhoto/scene_detail?id=123
     */
    public function scene_detail()
    {
        $sceneId = input('id/d', 0);
        
        if ($sceneId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误', 'data' => []]);
        }
        
        try {
            $sceneConfigService = new \app\service\SceneConfigService();
            $detail = $sceneConfigService->getSceneDetail($sceneId);
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'data' => $detail
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 7. 获取API配置列表（按模型筛选）
     * GET /AiTravelPhoto/get_api_config_list?model_id=3
     */
    public function get_api_config_list()
    {
        $modelId = input('model_id/d', 0);
        
        try {
            $sceneConfigService = new \app\service\SceneConfigService();
            $configs = $sceneConfigService->getApiConfigListByModel($this->aid, $this->bid, $modelId);
            
            return json([
                'code' => 0,
                'msg' => '获取成功',
                'data' => $configs
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * 一键生成场景封面图（AJAX接口）
     */
    public function generate_scene_cover()
    {
        $sceneId = input('scene_id/d', 0);
        
        if ($sceneId <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        
        try {
            // 1. 查询场景信息
            $scene = Db::name('ai_travel_photo_scene')
                ->where('id', $sceneId)
                ->where('aid', $this->aid)
                ->find();
            
            if (!$scene) {
                return json(['code' => 1, 'msg' => '场景不存在']);
            }
            
            // 2. 查询API配置
            if (empty($scene['api_config_id'])) {
                return json(['code' => 1, 'msg' => '场景未配置API，请先编辑场景选择API配置']);
            }
            
            $apiConfig = Db::name('api_config')
                ->where('id', $scene['api_config_id'])
                ->find();
            
            if (!$apiConfig || $apiConfig['is_active'] != 1) {
                return json(['code' => 1, 'msg' => 'API配置不可用或已禁用']);
            }
            
            // 3. 解析模型参数
            $modelParams = !empty($scene['model_params']) ? json_decode($scene['model_params'], true) : [];
            
            if (empty($modelParams['prompt']) && !empty($scene['prompt'])) {
                // 兼容旧数据：如果model_params为空，使用场景的prompt字段
                $modelParams['prompt'] = $scene['prompt'];
                $modelParams['negative_prompt'] = $scene['negative_prompt'] ?? '';
            }
            
            if (empty($modelParams['prompt'])) {
                return json(['code' => 1, 'msg' => '场景未配置提示词，无法生成封面图']);
            }
            
            // 4. 调用AI API生成图片（这里需要根据实际API实现）
            // TODO: 实现真实的API调用逻辑
            $result = $this->callAiApi($apiConfig, $modelParams, $scene);
            
            if (!$result['success']) {
                return json(['code' => 1, 'msg' => '生成失败：' . ($result['error'] ?? '未知错误')]);
            }
            
            // 5. 保存生成记录
            $generationId = Db::name('ai_travel_photo_generation')->insertGetId([
                'aid' => $this->aid,
                'bid' => $this->bid,
                'scene_id' => $sceneId,
                'model_id' => $scene['model_id'],
                'api_config_id' => $scene['api_config_id'],
                'params' => json_encode($modelParams, JSON_UNESCAPED_UNICODE),
                'result_image' => $result['image_url'],
                'status' => 1,
                'create_time' => time(),
                'update_time' => time()
            ]);
            
            // 6. 更新场景封面
            Db::name('ai_travel_photo_scene')
                ->where('id', $sceneId)
                ->update([
                    'cover' => $result['image_url'],
                    'update_time' => time()
                ]);
            
            return json([
                'code' => 0,
                'msg' => '生成成功',
                'data' => [
                    'image_url' => $result['image_url'],
                    'generation_id' => $generationId
                ]
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '生成失败：' . $e->getMessage()]);
        }
    }

    /**
     * 调用AI API生成图片（通用方法）
     * @param array $apiConfig API配置
     * @param array $params 模型参数
     * @param array $scene 场景信息
     * @return array ['success' => bool, 'image_url' => string, 'error' => string]
     */
    private function callAiApi($apiConfig, $params, $scene = [])
    {
        // 根据不同提供商调用对应API
        $provider = $apiConfig['provider'];
        
        try {
            switch ($provider) {
                case 'aliyun':
                    return $this->callAliyunApi($apiConfig, $params, $scene);
                case 'baidu':
                    return $this->callBaiduApi($apiConfig, $params, $scene);
                case 'openai':
                    return $this->callOpenAiApi($apiConfig, $params, $scene);
                default:
                    return ['success' => false, 'error' => '不支持的服务提供商：' . $provider];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 调用阿里云通义万相API（完整实现）
     * 支持文生图和图像编辑两种模式
     * 
     * 文档：https://help.aliyun.com/zh/dashscope/developer-reference/api-details-9
     */
    private function callAliyunApi($apiConfig, $params, $scene = [])
    {
        $apiKey = $apiConfig['api_key'];
        
        // 根据模型类型选择不同endpoint
        $modelCode = $scene['model_id'] ?? 0;
        $modelInfo = Db::name('ai_model_instance')->where('id', $modelCode)->find();
        $isImageEdit = $modelInfo && strpos($modelInfo['model_code'], 'image-edit') !== false;
        
        if ($isImageEdit) {
            // 图像编辑API
            $endpoint = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/image-generation/generation';
            $model = 'qwen-vl-max-latest'; // 或从模型配置中读取
        } else {
            // 文生图API
            $endpoint = $apiConfig['endpoint_url'] ?: 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis';
            $model = 'wanx-v1';
        }
        
        // 构建请求参数
        $requestData = [
            'model' => $model,
            'input' => [
                'prompt' => $params['prompt'] ?? ''
            ],
            'parameters' => [
                'style' => $params['style'] ?? '<auto>',
                'size' => $params['size'] ?? '1024*1024',
                'n' => 1
            ]
        ];
        
        // 如果是图像编辑，需要添加reference_image
        if ($isImageEdit && !empty($params['reference_image'])) {
            $requestData['input']['reference_image'] = $params['reference_image'];
        }
        
        // 添加负面提示词（如果支持）
        if (!empty($params['negative_prompt'])) {
            $requestData['input']['negative_prompt'] = $params['negative_prompt'];
        }
        
        // 添加seed参数
        if (isset($params['seed']) && $params['seed'] > 0) {
            $requestData['parameters']['seed'] = intval($params['seed']);
        }
        
        try {
            // 发送HTTP POST请求
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'X-DashScope-Async: enable'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return ['success' => false, 'error' => 'CURL错误: ' . $curlError];
            }
            
            // 解析响应
            $result = json_decode($response, true);
            
            if ($httpCode == 200 && isset($result['output']['task_id'])) {
                // 异步任务，需要轮询查询结果
                $taskId = $result['output']['task_id'];
                $imageUrl = $this->waitForAliyunTask($apiConfig, $taskId);
                
                if ($imageUrl) {
                    return ['success' => true, 'image_url' => $imageUrl];
                } else {
                    return ['success' => false, 'error' => '生成超时或失败，请稍后重试'];
                }
            } else {
                $error = $result['message'] ?? $result['error'] ?? '未知错误';
                $errorDetail = isset($result['code']) ? ' (错误代码: ' . $result['code'] . ')' : '';
                return ['success' => false, 'error' => '阿里云API返回错误: ' . $error . $errorDetail];
            }
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => '请求异常: ' . $e->getMessage()];
        }
    }
    
    /**
     * 轮询查询阿里云异步任务结果
     */
    private function waitForAliyunTask($apiConfig, $taskId, $maxWait = 60)
    {
        $queryEndpoint = 'https://dashscope.aliyuncs.com/api/v1/tasks/' . $taskId;
        $startTime = time();
        
        while (time() - $startTime < $maxWait) {
            sleep(2);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $queryEndpoint);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiConfig['api_key']
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if (isset($result['output']['task_status'])) {
                $status = $result['output']['task_status'];
                
                if ($status == 'SUCCEEDED') {
                    if (isset($result['output']['results'][0]['url'])) {
                        return $result['output']['results'][0]['url'];
                    }
                    return false;
                } elseif ($status == 'FAILED') {
                    return false;
                }
            }
        }
        
        return false;
    }

    /**
     * 调用百度API（示例实现）
     */
    private function callBaiduApi($apiConfig, $params, $scene = [])
    {
        // TODO: 实现百度API调用逻辑
        return [
            'success' => false,
            'error' => '百度API调用功能待实现，请联系技术人员配置'
        ];
    }

    /**
     * 调用OpenAI API（示例实现）
     */
    private function callOpenAiApi($apiConfig, $params, $scene = [])
    {
        // TODO: 实现OpenAI API调用逻辑
        return [
            'success' => false,
            'error' => 'OpenAI API调用功能待实现，请联系技术人员配置'
        ];
    }

    /**
     * 套餐管理 - 列表
     */
    public function package_list()
    {
        // 如果是AJAX请求，返回JSON数据
        if (request()->isAjax()) {
            $page = input('page/d', 1);
            $limit = input('limit/d', 20);

            $where = [
                ['aid', '=', $this->aid],
                ['bid', '=', $this->bid]
            ];

            $list = Db::name('ai_travel_photo_package')
                ->where($where)
                ->order('sort DESC, id DESC')
                ->page($page, $limit)
                ->select();

            $count = Db::name('ai_travel_photo_package')
                ->where($where)
                ->count();

            return json([
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list
            ]);
        }

        return View::fetch();
    }

    /**
     * 套餐管理 - 添加/编辑
     */
    public function package_edit()
    {
        $id = input('param.id/d', 0);

        if (request()->isPost()) {
            try {
                $data = input('post.');
                $data['aid'] = $this->aid;
                $data['bid'] = $this->bid;

                if ($id > 0) {
                    $data['update_time'] = time();
                    Db::name('ai_travel_photo_package')->where('id', $id)->update($data);
                    return json(['status' => 1, 'msg' => '保存成功']);
                } else {
                    $data['create_time'] = time();
                    $data['update_time'] = time();
                    Db::name('ai_travel_photo_package')->insert($data);
                    return json(['status' => 1, 'msg' => '添加成功']);
                }
            } catch (\Exception $e) {
                return json(['status' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
            }
        }

        $info = [];
        if ($id > 0) {
            $info = Db::name('ai_travel_photo_package')->where('id', $id)->find();
        }

        View::assign('info', $info);
        return View::fetch();
    }

    /**
     * 套餐管理 - 删除
     */
    public function package_delete()
    {
        $id = input('post.id/d');

        Db::name('ai_travel_photo_package')->where('id', $id)->delete();
        return json(['status' => 1, 'msg' => '删除成功']);
    }

    /**
     * 人像管理 - 列表
     */
    public function portrait_list()
    {
        // 如果是AJAX请求，返回JSON数据
        if (request()->isAjax()) {
            // 超级管理员bid为0时，使用aid对应的第一个商家
            $targetBid = $this->bid;
            if ($targetBid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
            }
            
            $where = [
                ['aid', '=', $this->aid],
                ['bid', '=', $targetBid]
            ];

            // 门店筛选
            $mdid = input('param.mdid/d', 0);
            if ($mdid > 0) {
                $where[] = ['mdid', '=', $mdid];
            }

            // 日期筛选
            $start_date = input('param.start_date');
            $end_date = input('param.end_date');
            if ($start_date) {
                $where[] = ['create_time', '>=', strtotime($start_date)];
            }
            if ($end_date) {
                $where[] = ['create_time', '<=', strtotime($end_date . ' 23:59:59')];
            }

            // 合成状态筛选
            $synthesis_status = input('param.synthesis_status');
            if ($synthesis_status !== '' && $synthesis_status !== null) {
                $where[] = ['synthesis_status', '=', intval($synthesis_status)];
            }

            $page = input('page/d', 1);
            $limit = input('limit/d', 20);

            $list = Db::name('ai_travel_photo_portrait')
                ->where($where)
                ->order('id DESC')
                ->page($page, $limit)
                ->select();

            // 关联查询生成结果数量
            foreach ($list as &$item) {
                $item['result_count'] = Db::name('ai_travel_photo_result')
                    ->where('portrait_id', $item['id'])
                    ->count();
            }

            $count = Db::name('ai_travel_photo_portrait')
                ->where($where)
                ->count();

            return json([
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list
            ]);
        }

        // 超级管理员bid为0时，使用aid对应的第一个商家
        $targetBid = $this->bid;
        if ($targetBid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
        }

        // 获取商户信息（云空间、余额、到期时间）
        $business = Db::name('business')->where('id', $targetBid)->find();
        $businessInfo = [
            'id' => $targetBid,
            'cloud_space' => $business['cloud_space'] ?? 5120,
            'account_balance' => $business['account_balance'] ?? 0,
            'money' => $business['money'] ?? 0,
            'endtime' => $business['endtime'] ?? 0
        ];
        View::assign('business_info', $businessInfo);

        // 获取门店列表（获取当前商家的门店 + bid=0的公共门店）
        $bid = $this->bid;
        $mendian_list = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where(function($query) use ($bid) {
                // 获取当前商家的门店（bid匹配）或bid=0的公共门店
                $query->whereOr([
                    ['bid', '=', $bid],
                    ['bid', '=', 0]
                ]);
            })
            ->select();

        View::assign('mendian_list', $mendian_list);
        return View::fetch();
    }

    /**
     * 人像管理 - 上传
     */
    public function portrait_upload()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }

        try {
            // 获取上传文件
            $file = request()->file('file');
            if (!$file) {
                return json(['status' => 0, 'msg' => '请选择要上传的图片']);
            }

            // 获取门店ID（可选）
            $mdid = input('post.mdid/d', 0);
            $desc = input('post.desc', '');
            $tags = input('post.tags', '');

            // 获取文件信息（使用ThinkPHP正确的方法）
            $fileName = $file->getOriginalName(); // 原始文件名
            $fileSize = $file->getSize(); // 文件大小
            $fileExt = strtolower($file->extension()); // 文件扩展名

            // 手动校验文件格式
            $allowedExts = ['jpg', 'jpeg', 'png'];
            if (!in_array($fileExt, $allowedExts)) {
                return json(['status' => 0, 'msg' => '仅支持JPG、JPEG、PNG格式']);
            }

            // 文件大小校验（10KB - 10MB）
            if ($fileSize < 10 * 1024) {
                return json(['status' => 0, 'msg' => '图片大小不能小于10KB']);
            }
            if ($fileSize > 10 * 1024 * 1024) {
                return json(['status' => 0, 'msg' => '图片大小不能超过10MB']);
            }

            // 计算MD5值
            $fileMd5 = md5_file($file->getPathname());

            // 超级管理员bid为0时，使用aid对应的第一个商家
            $targetBid = $this->bid;
            if ($targetBid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
            }

            // 检查MD5是否重复（同商家下）
            $existPortrait = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('md5', $fileMd5)
                ->find();

            if ($existPortrait) {
                return json(['status' => 0, 'msg' => '该图片已存在']);
            }

            // 获取图像尺寸
            $imageInfo = getimagesize($file->getPathname());
            if (!$imageInfo) {
                return json(['status' => 0, 'msg' => '图片文件损坏或格式不正确']);
            }
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // 尺寸校验（最小200px，最大10000px）
            if ($width < 200 || $height < 200) {
                return json(['status' => 0, 'msg' => '图片尺寸过小，宽高不能小于200px']);
            }
            if ($width > 10000 || $height > 10000) {
                return json(['status' => 0, 'msg' => '图片尺寸过大，宽高不能超过10000px']);
            }

            // 生成存储路径
            $date = date('Ymd');
            $uniqueName = md5(uniqid((string)mt_rand(), true)) . '.' . $fileExt;
            $savePath = 'upload/' . $this->aid . '/' . $date . '/';
            
            // 确保目录存在
            if (!is_dir(ROOT_PATH . $savePath)) {
                mk_dir(ROOT_PATH . $savePath);
            }

            // 保存原图到本地
            $originalPath = $savePath . 'original_' . $uniqueName;
            $file->move(ROOT_PATH . $savePath, 'original_' . $uniqueName);

            // 上传原图到OSS
            $originalUrl = \app\common\Pic::uploadoss(PRE_URL . '/' . $originalPath, false, false);
            if (!$originalUrl) {
                return json(['status' => 0, 'msg' => '上传失败，请检查OSS配置']);
            }

            // 生成缩略图（800px宽度）
            $thumbnailPath = $this->generateThumbnail(ROOT_PATH . $originalPath, $width, $height, $savePath, $uniqueName);
            if (!$thumbnailPath) {
                return json(['status' => 0, 'msg' => '缩略图生成失败']);
            }

            // 上传缩略图到OSS
            $thumbnailUrl = \app\common\Pic::uploadoss(PRE_URL . '/' . $thumbnailPath, false, false);

            // 插入人像记录
            $portraitData = [
                'aid' => $this->aid,
                'uid' => 0, // 后台上传无关联用户
                'bid' => $targetBid,
                'mdid' => $mdid,
                'device_id' => 0, // 后台上传无设备来源
                'type' => 1, // 商家上传
                'original_url' => $originalUrl,
                'cutout_url' => null, // 初始为空，抠图任务完成后填充
                'thumbnail_url' => $thumbnailUrl,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'width' => $width,
                'height' => $height,
                'md5' => $fileMd5,
                'desc' => $desc,
                'tags' => $tags,
                'status' => 1,
                'create_time' => time(),
                'update_time' => time()
            ];

            $portraitId = Db::name('ai_travel_photo_portrait')->insertGetId($portraitData);

            if (!$portraitId) {
                return json(['status' => 0, 'msg' => '数据保存失败']);
            }

            // 记录日志
            \think\facade\Log::info('AI旅拍人像上传成功', [
                'aid' => $this->aid,
                'bid' => $targetBid,
                'portrait_id' => $portraitId,
                'file_name' => $fileName,
                'file_size' => $fileSize
            ]);

            // 触发异步任务
            $this->triggerAsyncTasks($portraitId, $targetBid);

            return json([
                'status' => 1,
                'msg' => '上传成功',
                'data' => [
                    'portrait_id' => $portraitId,
                    'original_url' => $originalUrl,
                    'thumbnail_url' => $thumbnailUrl
                ]
            ]);
        } catch (\Exception $e) {
            \think\facade\Log::error('AI旅拍人像上传失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return json(['status' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
        }
    }

    /**
     * 笑脸抓拍上传
     * 接收前端摄像头抓拍的图片，提取人脸特征并存入Milvus
     */
    public function smile_capture_upload()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }

        try {
            // 获取Base64图片数据
            $imageData = input('post.image', '');
            if (empty($imageData)) {
                return json(['status' => 0, 'msg' => '请提供图片数据']);
            }

            // 解析Base64数据
            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $imageData, $matches)) {
                $extension = strtolower($matches[1]);
                $imageContent = base64_decode($matches[2]);
            } else {
                return json(['status' => 0, 'msg' => '图片格式不正确']);
            }

            // 验证图片格式
            $allowedExts = ['jpg', 'jpeg', 'png'];
            if (!in_array($extension, $allowedExts)) {
                return json(['status' => 0, 'msg' => '仅支持JPG、JPEG、PNG格式']);
            }

            // 获取门店ID
            $mdid = input('post.mdid/d', 0);
            $isManual = input('post.is_manual/d', 0);

            // 创建临时文件
            $tempFile = tempnam(sys_get_temp_dir(), 'smile_');
            file_put_contents($tempFile, $imageContent);

            // 获取图片尺寸
            $imageInfo = getimagesize($tempFile);
            if (!$imageInfo) {
                @unlink($tempFile);
                return json(['status' => 0, 'msg' => '图片文件损坏或格式不正确']);
            }
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            // 尺寸校验
            if ($width < 200 || $height < 200) {
                @unlink($tempFile);
                return json(['status' => 0, 'msg' => '图片尺寸过小']);
            }

            // 计算MD5
            $fileMd5 = md5_file($tempFile);
            $fileSize = filesize($tempFile);

            // 超级管理员bid为0时，使用aid对应的第一个商家
            $targetBid = $this->bid;
            if ($targetBid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
            }

            // 记录调试信息
            \think\facade\Log::info('笑脸抓拍调试', [
                'aid' => $this->aid,
                'bid' => $this->bid,
                'targetBid' => $targetBid,
                'mdid' => $mdid
            ]);

            // 检查MD5是否重复
            $existPortrait = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('md5', $fileMd5)
                ->find();

            if ($existPortrait) {
                @unlink($tempFile);
                return json(['status' => 0, 'msg' => '该图片已存在']);
            }

            // 生成存储路径
            $date = date('Ymd');
            $uniqueName = md5(uniqid((string)mt_rand(), true)) . '.' . $extension;
            $savePath = 'upload/' . $this->aid . '/' . $date . '/';

            // 确保目录存在
            if (!is_dir(ROOT_PATH . $savePath)) {
                mk_dir(ROOT_PATH . $savePath);
            }

            // 保存原图
            $originalPath = $savePath . 'original_' . $uniqueName;
            file_put_contents(ROOT_PATH . $originalPath, $imageContent);

            // 上传到OSS（如果配置了OSS）
            $uploadPath = PRE_URL . '/' . $originalPath;
            \think\facade\Log::info('笑脸抓拍OSS上传', [
                'uploadPath' => $uploadPath,
                'savePath' => $savePath,
                'originalPath' => $originalPath
            ]);

            $originalUrl = \app\common\Pic::uploadoss($uploadPath, false, false);

            // 如果OSS上传失败，使用本地存储作为备用
            if (!$originalUrl) {
                \think\facade\Log::warning('OSS上传失败，使用本地存储备用', [
                    'uploadPath' => $uploadPath,
                    'aid' => $this->aid,
                    'bid' => $this->bid
                ]);
                // 使用本地路径作为URL
                $originalUrl = '/' . $originalPath;
            }

            // 生成缩略图
            $thumbnailPath = $this->generateThumbnail(ROOT_PATH . $originalPath, $width, $height, $savePath, $uniqueName);
            $thumbnailUrl = '';
            if ($thumbnailPath) {
                $thumbnailUrl = \app\common\Pic::uploadoss(PRE_URL . '/' . $thumbnailPath, false, false);
                // 如果OSS上传失败，使用本地存储
                if (!$thumbnailUrl) {
                    $thumbnailUrl = '/' . $thumbnailPath;
                }
            }

            // 清理临时文件（仅当OSS上传成功后才删除本地文件，本地存储时保留）
            // 判断是否是本地存储：URL以/开头表示本地路径
            if (strpos($originalUrl, 'http') === 0) {
                // OSS上传成功，删除本地文件
                @unlink(ROOT_PATH . $originalPath);
            }
            @unlink($tempFile);

            // 获取人脸特征向量（从前端传入）
            $faceEmbedding = input('post.face_embedding', '');

            // 插入人像记录
            $portraitData = [
                'aid' => $this->aid,
                'uid' => 0,
                'bid' => $targetBid,
                'mdid' => $mdid,
                'device_id' => 0,
                'type' => 1, // 商家上传
                'original_url' => $originalUrl,
                'cutout_url' => null,
                'thumbnail_url' => $thumbnailUrl,
                'file_name' => 'smile_capture_' . date('YmdHis') . '.' . $extension,
                'file_size' => $fileSize,
                'width' => $width,
                'height' => $height,
                'md5' => $fileMd5,
                'desc' => $isManual ? '手动抓拍' : '笑脸自动抓拍',
                'tags' => '笑脸抓拍',
                'status' => 1,
                'create_time' => time(),
                'update_time' => time()
            ];

            $portraitId = Db::name('ai_travel_photo_portrait')->insertGetId($portraitData);

            if (!$portraitId) {
                return json(['status' => 0, 'msg' => '数据保存失败']);
            }

            // 如果有前端传入的人脸特征向量
            if (!empty($faceEmbedding)) {
                try {
                    $embeddingData = json_decode($faceEmbedding, true);
                    if (is_array($embeddingData) && !empty($embeddingData)) {
                        // 优先尝试存入Milvus（如果REST API可用）
                        $milvusAvailable = false;
                        try {
                            $milvusService = new \app\service\MilvusService();
                            if ($milvusService->isHealthy()) {
                                $vectorIds = $milvusService->insert($embeddingData, [
                                    'portrait_id' => $portraitId
                                ]);
                                if (!empty($vectorIds)) {
                                    Db::name('ai_travel_photo_portrait')
                                        ->where('id', $portraitId)
                                        ->update(['face_embedding_id' => $vectorIds[0] ?? 0]);
                                    $milvusAvailable = true;
                                }
                            }
                        } catch (\Exception $e) {
                            \think\facade\Log::warning('Milvus存储失败，使用MySQL备用', [
                                'portrait_id' => $portraitId,
                                'error' => $e->getMessage()
                            ]);
                        }

                        // 如果Milvus不可用，存入MySQL的JSON字段
                        if (!$milvusAvailable) {
                            Db::name('ai_travel_photo_portrait')
                                ->where('id', $portraitId)
                                ->update(['face_embedding' => json_encode($embeddingData)]);
                        }
                    }
                } catch (\Exception $e) {
                    // 存储失败不影响主流程
                    \think\facade\Log::warning('人脸特征存储失败', [
                        'portrait_id' => $portraitId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 触发异步任务
            $this->triggerAsyncTasks($portraitId, $targetBid);

            // 记录日志
            \think\facade\Log::info('AI旅拍笑脸抓拍成功', [
                'aid' => $this->aid,
                'bid' => $targetBid,
                'portrait_id' => $portraitId,
                'is_manual' => $isManual,
                'file_size' => $fileSize
            ]);

            return json([
                'status' => 1,
                'msg' => '抓拍成功',
                'data' => [
                    'portrait_id' => $portraitId,
                    'original_url' => $originalUrl,
                    'thumbnail_url' => $thumbnailUrl
                ]
            ]);
        } catch (\Exception $e) {
            \think\facade\Log::error('AI旅拍笑脸抓拍失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return json(['status' => 0, 'msg' => '抓拍失败：' . $e->getMessage()]);
        }
    }

    /**
     * 触发异步任务（抠图 + AI生成）
     * @param int $portraitId 人像ID
     * @param int $targetBid 商家ID
     */
    private function triggerAsyncTasks($portraitId, $targetBid)
    {
        try {
            // 推送抠图任务
            \think\facade\Queue::push(
                'app\\job\\CutoutJob',
                ['portrait_id' => $portraitId],
                'ai_cutout'
            );

            \think\facade\Log::info('抠图任务已推送', ['portrait_id' => $portraitId]);

            // 推送AI自动生成任务
            // 从合成设置获取已关联的照片场景模板
            $setting = Db::name('ai_travel_photo_synthesis_setting')
                ->where('portrait_id', 0)
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->find();

            if (!$setting || empty($setting['template_ids'])) {
                \think\facade\Log::info('未配置合成模板，跳过自动生成', ['portrait_id' => $portraitId]);
                return;
            }

            // 获取模板列表
            $templateIds = explode(',', $setting['template_ids']);
            $generateCount = $setting['generate_count'] ?? 4;
            
            $templates = Db::name('generation_scene_template')
                ->whereIn('id', $templateIds)
                ->where('generation_type', 1) // 照片生成
                ->where('status', 1)
                ->field('id, template_name, model_id')
                ->limit($generateCount)
                ->select();

            foreach ($templates as $template) {
                // 创建生成记录（使用template_id而非scene_id）
                $generationId = Db::name('ai_travel_photo_generation')->insertGetId([
                    'aid' => $this->aid,
                    'portrait_id' => $portraitId,
                    'scene_id' => 0, // 不再使用scene_id
                    'template_id' => $template['id'], // 使用template_id
                    'uid' => 0,
                    'bid' => $targetBid,
                    'mdid' => 0,
                    'type' => 1, // 商家自动生成
                    'generation_type' => 1, // 图生图
                    'status' => 0, // 待处理
                    'create_time' => time(),
                    'update_time' => time(),
                    'queue_time' => time()
                ]);

                // 推送图生图任务
                \think\facade\Queue::push(
                    'app\\job\\ImageGenerationJob',
                    ['generation_id' => $generationId],
                    'ai_image_generation'
                );

                \think\facade\Log::info('图生图任务已推送', [
                    'portrait_id' => $portraitId,
                    'template_id' => $template['id'],
                    'template_name' => $template['template_name'],
                    'generation_id' => $generationId
                ]);
            }
        } catch (\Exception $e) {
            // 队列推送失败不影响上传成功，只记录日志
            \think\facade\Log::error('异步任务推送失败', [
                'portrait_id' => $portraitId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 生成缩略图
     * @param string $sourcePath 源文件路径
     * @param int $sourceWidth 源图宽度
     * @param int $sourceHeight 源图高度
     * @param string $savePath 保存目录
     * @param string $uniqueName 唯一文件名
     * @return string|false 缩略图相对路径
     */
    private function generateThumbnail($sourcePath, $sourceWidth, $sourceHeight, $savePath, $uniqueName)
    {
        try {
            $targetWidth = 800;
            $targetHeight = intval($sourceHeight * ($targetWidth / $sourceWidth));

            // 如果原图宽度小于等于800px，直接复制
            if ($sourceWidth <= 800) {
                $thumbnailPath = $savePath . 'thumbnail_' . $uniqueName;
                copy($sourcePath, ROOT_PATH . $thumbnailPath);
                return $thumbnailPath;
            }

            // 创建图像资源
            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
            if ($ext == 'jpg' || $ext == 'jpeg') {
                $sourceImage = imagecreatefromjpeg($sourcePath);
            } elseif ($ext == 'png') {
                $sourceImage = imagecreatefrompng($sourcePath);
            } else {
                return false;
            }

            if (!$sourceImage) {
                return false;
            }

            // 创建缩略图
            $thumbnailImage = imagecreatetruecolor($targetWidth, $targetHeight);
            
            // 保持PNG透明度
            if ($ext == 'png') {
                imagealphablending($thumbnailImage, false);
                imagesavealpha($thumbnailImage, true);
            }

            imagecopyresampled(
                $thumbnailImage,
                $sourceImage,
                0, 0, 0, 0,
                $targetWidth, $targetHeight,
                $sourceWidth, $sourceHeight
            );

            // 保存缩略图
            $thumbnailPath = $savePath . 'thumbnail_' . $uniqueName;
            $thumbnailFullPath = ROOT_PATH . $thumbnailPath;
            
            // 统一输出为JPG格式，质量85%
            $result = imagejpeg($thumbnailImage, $thumbnailFullPath, 85);

            // 释放资源
            imagedestroy($sourceImage);
            imagedestroy($thumbnailImage);

            return $result ? $thumbnailPath : false;
        } catch (\Exception $e) {
            \think\facade\Log::error('缩略图生成失败', [
                'source' => $sourcePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 人像管理 - 删除
     */
    public function portrait_delete()
    {
        $id = input('post.id/d');
        
        try {
            // 查询人像信息
            $portrait = Db::name('ai_travel_photo_portrait')
                ->where('id', $id)
                ->find();
            
            if (!$portrait) {
                return json(['status' => 0, 'msg' => '人像不存在']);
            }
            
            // 删除OSS文件
            $deleteSuccess = true;
            $deleteErrors = [];
            
            // 删除原图
            if (!empty($portrait['original_url'])) {
                try {
                    $result = \app\common\Pic::deleteoss($portrait['original_url']);
                    if (!$result) {
                        $deleteErrors[] = '原图删除失败';
                        $deleteSuccess = false;
                    }
                } catch (\Exception $e) {
                    $deleteErrors[] = '原图删除异常: ' . $e->getMessage();
                    $deleteSuccess = false;
                    \think\facade\Log::error('删除OSS原图失败', [
                        'portrait_id' => $id,
                        'url' => $portrait['original_url'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // 删除缩略图
            if (!empty($portrait['thumbnail_url'])) {
                try {
                    $result = \app\common\Pic::deleteoss($portrait['thumbnail_url']);
                    if (!$result) {
                        $deleteErrors[] = '缩略图删除失败';
                        $deleteSuccess = false;
                    }
                } catch (\Exception $e) {
                    $deleteErrors[] = '缩略图删除异常: ' . $e->getMessage();
                    $deleteSuccess = false;
                    \think\facade\Log::error('删除OSS缩略图失败', [
                        'portrait_id' => $id,
                        'url' => $portrait['thumbnail_url'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // 删除抠图（如果存在）
            if (!empty($portrait['cutout_url'])) {
                try {
                    $result = \app\common\Pic::deleteoss($portrait['cutout_url']);
                    if (!$result) {
                        $deleteErrors[] = '抠图删除失败';
                        $deleteSuccess = false;
                    }
                } catch (\Exception $e) {
                    \think\facade\Log::error('删除OSS抠图失败', [
                        'portrait_id' => $id,
                        'url' => $portrait['cutout_url'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // 查询并删除相关的生成结果文件
            $results = Db::name('ai_travel_photo_result')
                ->where('portrait_id', $id)
                ->select();
            
            foreach ($results as $result) {
                // 删除生成结果文件
                if (!empty($result['url'])) {
                    try {
                        \app\common\Pic::deleteoss($result['url']);
                    } catch (\Exception $e) {
                        \think\facade\Log::error('删除生成结果OSS文件失败', [
                            'result_id' => $result['id'],
                            'url' => $result['url'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // 删除生成结果缩略图
                if (!empty($result['thumbnail_url'])) {
                    try {
                        \app\common\Pic::deleteoss($result['thumbnail_url']);
                    } catch (\Exception $e) {
                        \think\facade\Log::error('删除生成结果缩略图OSS文件失败', [
                            'result_id' => $result['id'],
                            'url' => $result['thumbnail_url'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // 删除数据库记录
            Db::name('ai_travel_photo_portrait')->where('id', $id)->delete();
            Db::name('ai_travel_photo_result')->where('portrait_id', $id)->delete();
            Db::name('ai_travel_photo_generation')->where('portrait_id', $id)->delete();
            
            // 返回结果
            if ($deleteSuccess) {
                return json(['status' => 1, 'msg' => '删除成功']);
            } else {
                return json([
                    'status' => 1, 
                    'msg' => '数据库记录已删除，但部分OSS文件删除失败: ' . implode(', ', $deleteErrors)
                ]);
            }
        } catch (\Exception $e) {
            \think\facade\Log::error('AI旅拍人像删除失败', [
                'portrait_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return json(['status' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
        }
    }

    /**
     * 人像管理 - 批量删除
     */
    public function portrait_batch_delete()
    {
        $ids = input('post.ids/a', []);
        
        if (empty($ids)) {
            return json(['status' => 0, 'msg' => '请选择要删除的人像']);
        }
        
        // 限制一次最多删除100条
        if (count($ids) > 100) {
            return json(['status' => 0, 'msg' => '一次最多删除100条记录']);
        }
        
        $successCount = 0;
        $failCount = 0;
        $failMessages = [];
        
        foreach ($ids as $id) {
            $id = intval($id);
            if ($id <= 0) continue;
            
            try {
                // 查询人像信息
                $portrait = Db::name('ai_travel_photo_portrait')
                    ->where('id', $id)
                    ->find();
                
                if (!$portrait) {
                    $failCount++;
                    $failMessages[] = 'ID:'.$id.' 不存在';
                    continue;
                }
                
                // 删除OSS文件
                // 删除原图
                if (!empty($portrait['original_url'])) {
                    try {
                        \app\common\Pic::deleteoss($portrait['original_url']);
                    } catch (\Exception $e) {
                        \think\facade\Log::error('批量删除OSS原图失败', [
                            'portrait_id' => $id,
                            'url' => $portrait['original_url'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // 删除缩略图
                if (!empty($portrait['thumbnail_url'])) {
                    try {
                        \app\common\Pic::deleteoss($portrait['thumbnail_url']);
                    } catch (\Exception $e) {
                        \think\facade\Log::error('批量删除OSS缩略图失败', [
                            'portrait_id' => $id,
                            'url' => $portrait['thumbnail_url'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // 删除抠图（如果存在）
                if (!empty($portrait['cutout_url'])) {
                    try {
                        \app\common\Pic::deleteoss($portrait['cutout_url']);
                    } catch (\Exception $e) {
                        \think\facade\Log::error('批量删除OSS抠图失败', [
                            'portrait_id' => $id,
                            'url' => $portrait['cutout_url'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // 查询并删除相关的生成结果文件
                $results = Db::name('ai_travel_photo_result')
                    ->where('portrait_id', $id)
                    ->select();
                
                foreach ($results as $result) {
                    // 删除生成结果文件
                    if (!empty($result['url'])) {
                        try {
                            \app\common\Pic::deleteoss($result['url']);
                        } catch (\Exception $e) {
                            \think\facade\Log::error('批量删除生成结果OSS文件失败', [
                                'result_id' => $result['id'],
                                'url' => $result['url'],
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    // 删除生成结果缩略图
                    if (!empty($result['thumbnail_url'])) {
                        try {
                            \app\common\Pic::deleteoss($result['thumbnail_url']);
                        } catch (\Exception $e) {
                            \think\facade\Log::error('批量删除生成结果缩略图OSS文件失败', [
                                'result_id' => $result['id'],
                                'url' => $result['thumbnail_url'],
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                
                // 删除数据库记录
                Db::name('ai_travel_photo_portrait')->where('id', $id)->delete();
                Db::name('ai_travel_photo_result')->where('portrait_id', $id)->delete();
                Db::name('ai_travel_photo_generation')->where('portrait_id', $id)->delete();
                
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
                $failMessages[] = 'ID:'.$id.' 删除失败：' . $e->getMessage();
                \think\facade\Log::error('AI旅拍人像批量删除失败', [
                    'portrait_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $msg = '成功删除 ' . $successCount . ' 条记录';
        if ($failCount > 0) {
            $msg .= '，失败 ' . $failCount . ' 条';
        }
        
        return json([
            'status' => $successCount > 0 ? 1 : 0,
            'msg' => $msg,
            'data' => [
                'success' => $successCount,
                'fail' => $failCount
            ]
        ]);
    }

    /**
     * 订单管理 - 列表
     */
    public function order_list()
    {
        // 如果是AJAX请求，返回JSON数据
        if (request()->isAjax()) {
            try {
                $where = [
                    ['o.aid', '=', $this->aid],
                    ['o.bid', '=', $this->bid]
                ];

                // 状态筛选
                $status = input('param.status', '');
                if ($status !== '') {
                    $where[] = ['o.status', '=', $status];
                }

                // 日期筛选
                $start_date = input('param.start_date');
                $end_date = input('param.end_date');
                if ($start_date) {
                    $where[] = ['o.create_time', '>=', strtotime($start_date)];
                }
                if ($end_date) {
                    $where[] = ['o.create_time', '<=', strtotime($end_date . ' 23:59:59')];
                }

                $page = input('page/d', 1);
                $limit = input('limit/d', 20);

                $list = Db::name('ai_travel_photo_order')
                    ->alias('o')
                    ->leftJoin('ddwx_member m', 'o.uid = m.id')
                    ->where($where)
                    ->field('o.*, m.nickname, m.tel as mobile')
                    ->order('o.id DESC')
                    ->page($page, $limit)
                    ->select();

                // 转换为数组
                $list = $list ? $list->toArray() : [];

                // 查询订单商品数量
                foreach ($list as &$item) {
                    $item['goods_count'] = Db::name('ai_travel_photo_order_goods')
                        ->where('order_id', $item['id'])
                        ->count();
                }

                $count = Db::name('ai_travel_photo_order')
                    ->alias('o')
                    ->where($where)
                    ->count();

                return json([
                    'code' => 0,
                    'msg' => '',
                    'count' => $count,
                    'data' => $list
                ]);
            } catch (\Exception $e) {
                \think\facade\Log::error('订单列表查询失败', [
                    'aid' => $this->aid,
                    'bid' => $this->bid,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return json([
                    'code' => 1,
                    'msg' => '查询失败：' . $e->getMessage(),
                    'count' => 0,
                    'data' => []
                ]);
            }
        }

        return View::fetch();
    }

    /**
     * 订单详情
     */
    public function order_detail()
    {
        $id = input('param.id/d');

        $order = Db::name('ai_travel_photo_order')
            ->alias('o')
            ->leftJoin('member m', 'o.uid = m.id')
            ->where('o.id', $id)
            ->field('o.*, m.nickname, m.tel as mobile, m.headimg')
            ->find();

        if (!$order) {
            $this->error('订单不存在');
        }

        // 查询订单商品
        $goods = Db::name('ai_travel_photo_order_goods')
            ->alias('g')
            ->leftJoin('ai_travel_photo_result r', 'g.result_id = r.id')
            ->where('g.order_id', $id)
            ->field('g.*, r.url, r.thumbnail_url, r.type as result_type')
            ->select();

        View::assign('order', $order);
        View::assign('goods', $goods);
        return View::fetch();
    }

    /**
     * 人像详情 - 查看人像信息及关联的生成结果
     */
    public function portrait_detail()
    {
        $portrait_id = input('param.id/d', 0);

        if ($portrait_id <= 0) {
            $this->error('参数错误');
        }

        // 查询人像记录
        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('id', $portrait_id)
            ->where('aid', $this->aid)
            ->find();

        if (!$portrait) {
            $this->error('人像不存在');
        }

        // 查询关联的生成结果
        // LEFT JOIN generation 表获取模板/场景名称
        $results = Db::name('ai_travel_photo_result')
            ->alias('r')
            ->leftJoin('ai_travel_photo_generation g', 'r.generation_id = g.id')
            ->where('r.portrait_id', $portrait_id)
            ->where('r.status', 1)
            ->field('r.*, g.template_id, g.prompt as generation_prompt, g.scene_id as gen_scene_id')
            ->order('r.id DESC')
            ->select()
            ->each(function ($item) {
                // 尝试获取场景/模板名称
                $sceneName = '';
                if (!empty($item['generation_id']) && $item['generation_id'] > 0 && !empty($item['template_id'])) {
                    $tpl = Db::name('generation_scene_template')
                        ->where('id', $item['template_id'])
                        ->value('template_name');
                    if ($tpl) {
                        $sceneName = $tpl;
                    }
                }
                // 回退使用 result.desc 字段
                if (empty($sceneName) && !empty($item['desc'])) {
                    $sceneName = $item['desc'];
                }
                $item['scene_name'] = $sceneName;
                return $item;
            })
            ->toArray(); // 关键修复：转换为数组，否则模板中array_filter()在PHP 7.4下接收Collection会返回NULL

        View::assign('portrait', $portrait);
        View::assign('results', $results);
        return View::fetch();
    }

    /**
     * 人像转订单详情 - 根据人像ID查询关联订单并跳转
     */
    public function portrait_to_order()
    {
        $portrait_id = input('param.id/d');

        if (!$portrait_id) {
            $this->error('参数错误');
        }

        // 根据人像ID查询关联的订单
        $order = Db::name('ai_travel_photo_order')
            ->where('portrait_id', $portrait_id)
            ->where('status', '>=', 1) // 只查询已支付的订单
            ->order('id DESC')
            ->find();

        if (!$order) {
            $this->error('暂无订单信息，该人像尚未产生订单');
        }

        // 跳转到订单详情
        return redirect((string)url('order_detail', ['id' => $order['id']]));
    }

    /**
     * 数据统计
     */
    public function statistics()
    {
        // 今日数据
        $today = date('Y-m-d');
        $today_stat = Db::name('ai_travel_photo_statistics')
            ->where('aid', $this->aid)
            ->where('bid', $this->bid)
            ->where('stat_date', $today)
            ->find();

        if (!$today_stat) {
            $today_stat = [
                'upload_count' => 0,
                'generation_count' => 0,
                'video_count' => 0,
                'order_count' => 0,
                'order_amount' => 0.00,
                'scan_count' => 0
            ];
        }

        // 本月数据
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-d');
        $month_stat = Db::name('ai_travel_photo_statistics')
            ->where('aid', $this->aid)
            ->where('bid', $this->bid)
            ->where('stat_date', 'between', [$month_start, $month_end])
            ->field('SUM(upload_count) as upload_count, SUM(generation_count) as generation_count, SUM(video_count) as video_count, SUM(order_count) as order_count, SUM(order_amount) as order_amount, SUM(scan_count) as scan_count')
            ->find();

        // 趋势图数据（最近7天）
        $trend_data = Db::name('ai_travel_photo_statistics')
            ->where('aid', $this->aid)
            ->where('bid', $this->bid)
            ->where('stat_date', '>=', date('Y-m-d', strtotime('-7 days')))
            ->order('stat_date ASC')
            ->select();

        // 热门场景TOP10
        $hot_scenes = Db::name('ai_travel_photo_scene')
            ->where('aid', $this->aid)
            ->where('bid', $this->bid)
            ->order('use_count DESC')
            ->limit(10)
            ->select();

        View::assign('today_stat', $today_stat);
        View::assign('month_stat', $month_stat);
        View::assign('trend_data', $trend_data);
        View::assign('hot_scenes', $hot_scenes);
        return View::fetch();
    }

    /**
     * 设备管理 - 列表
     */
    public function device_list()
    {
        // 如果是AJAX请求，返回JSON数据
        if (request()->isAjax()) {
            $list = Db::name('ai_travel_photo_device')
                ->where('aid', $this->aid)
                ->where('bid', $this->bid)
                ->order('id DESC')
                ->select();

            return json([
                'code' => 0,
                'msg' => '',
                'count' => count($list),
                'data' => $list
            ]);
        }
        
        $list = Db::name('ai_travel_photo_device')
            ->where('aid', $this->aid)
            ->where('bid', $this->bid)
            ->order('id DESC')
            ->select();

        // 获取门店列表
        $mendian_list = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where('bid', $this->bid)
            ->select();

        View::assign('list', $list);
        View::assign('mendian_list', $mendian_list);
        return View::fetch();
    }

    /**
     * 选片列表 - 二维码管理
     */
    public function qrcode_list()
    {
        // 如果是AJAX请求，返回JSON数据
        if (request()->isAjax()) {
            try {
                // 超级管理员bid为0时，使用aid对应的第一个商家
                $targetBid = $this->bid;
                if ($targetBid == 0) {
                    $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
                }
                
                $where = [
                    ['q.aid', '=', $this->aid],
                    ['q.bid', '=', $targetBid]
                ];

                // 门店筛选 (通过portrait表的mdid字段)
                $mdid = input('param.mdid', '');
                if ($mdid !== '') {
                    $where[] = ['p.mdid', '=', $mdid];
                }

                // 状态筛选
                $status = input('param.status', '');
                if ($status !== '') {
                    $where[] = ['q.status', '=', $status];
                }

                // 日期范围筛选
                $start_date = input('param.start_date', '');
                $end_date = input('param.end_date', '');
                if ($start_date && $end_date) {
                    $start_time = strtotime($start_date . ' 00:00:00');
                    $end_time = strtotime($end_date . ' 23:59:59');
                    $where[] = ['q.create_time', 'between', [$start_time, $end_time]];
                }

                $page = input('page/d', 1);
                $limit = input('limit/d', 20);

                $list = Db::name('ai_travel_photo_qrcode')
                    ->alias('q')
                    ->leftJoin('ai_travel_photo_portrait p', 'q.portrait_id = p.id')
                    ->leftJoin('mendian m', 'p.mdid = m.id')
                    ->where($where)
                    ->field('q.*, p.original_url, p.thumbnail_url, p.mdid, m.name as mendian_name')
                    ->order('q.id DESC')
                    ->page($page, $limit)
                    ->select();

                $count = Db::name('ai_travel_photo_qrcode')
                    ->alias('q')
                    ->leftJoin('ai_travel_photo_portrait p', 'q.portrait_id = p.id')
                    ->where($where)
                    ->count();

                return json([
                    'code' => 0,
                    'msg' => '',
                    'count' => $count,
                    'data' => $list
                ]);
            } catch (\Exception $e) {
                return json([
                    'code' => 1,
                    'msg' => $e->getMessage(),
                    'count' => 0,
                    'data' => []
                ]);
            }
        }

        // 超级管理员bid为0时，使用aid对应的第一个商家
        $targetBid = $this->bid;
        if ($targetBid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
        }
        
        // 获取门店列表
        $mendian_list = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->select();

        View::assign('mendian_list', $mendian_list);
        return View::fetch();
    }

    /**
     * 成品列表 - 生成结果管理
     */
    public function result_list()
    {
        // 如果是AJAX请求，返回JSON数据
        if (request()->isAjax()) {
            try {
                // 超级管理员bid为0时，使用aid对应的第一个商家
                $targetBid = $this->bid;
                if ($targetBid == 0) {
                    $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
                }
                
                // result表只有aid，通过portrait表关联bid
                $where = [
                    ['r.aid', '=', $this->aid],
                    ['p.bid', '=', $targetBid]
                ];

                // 门店筛选
                $mdid = input('param.mdid', '');
                if ($mdid !== '') {
                    $where[] = ['p.mdid', '=', $mdid];
                }

                // 类型筛选 (1:图片 19:视频)
                $type = input('param.type', '');
                if ($type !== '') {
                    $where[] = ['r.type', '=', $type];
                }

                // 日期范围筛选
                $start_date = input('param.start_date', '');
                $end_date = input('param.end_date', '');
                if ($start_date && $end_date) {
                    $start_time = strtotime($start_date . ' 00:00:00');
                    $end_time = strtotime($end_date . ' 23:59:59');
                    $where[] = ['r.create_time', 'between', [$start_time, $end_time]];
                }

                $page = input('page/d', 1);
                $limit = input('limit/d', 20);

                $list = Db::name('ai_travel_photo_result')
                    ->alias('r')
                    ->leftJoin('ai_travel_photo_portrait p', 'r.portrait_id = p.id')
                    ->leftJoin('ai_travel_photo_scene s', 'r.scene_id = s.id')
                    ->leftJoin('mendian m', 'p.mdid = m.id')
                    ->where($where)
                    ->field('r.*, p.original_url as portrait_url, p.mdid, s.name as scene_name, m.name as mendian_name')
                    ->order('r.id DESC')
                    ->page($page, $limit)
                    ->select();

                $count = Db::name('ai_travel_photo_result')
                    ->alias('r')
                    ->leftJoin('ai_travel_photo_portrait p', 'r.portrait_id = p.id')
                    ->where($where)
                    ->count();

                return json([
                    'code' => 0,
                    'msg' => '',
                    'count' => $count,
                    'data' => $list
                ]);
            } catch (\Exception $e) {
                return json([
                    'code' => 1,
                    'msg' => $e->getMessage(),
                    'count' => 0,
                    'data' => []
                ]);
            }
        }

        // 超级管理员bid为0时，使用aid对应的第一个商家
        $targetBid = $this->bid;
        if ($targetBid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
        }
        
        // 获取门店列表
        $mendian_list = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->select();

        View::assign('mendian_list', $mendian_list);
        return View::fetch();
    }

    /**
     * 设备管理 - 更新状态
     */
    public function device_update_status()
    {
        $id = input('post.id/d');
        $status = input('post.status/d');

        Db::name('ai_travel_photo_device')->where('id', $id)->update(['status' => $status]);
        return json(['status' => 1, 'msg' => '操作成功']);
    }

    /**
     * 设备管理 - 删除
     */
    public function device_delete()
    {
        $id = input('post.id/d');

        Db::name('ai_travel_photo_device')->where('id', $id)->delete();
        return json(['status' => 1, 'msg' => '删除成功']);
    }

    /**
     * 设备管理 - 生成令牌
     */
    public function device_generate_token()
    {
        try {
            // 添加调试日志
            \think\facade\Log::info('device_generate_token 被调用', [
                'aid' => $this->aid,
                'bid' => $this->bid,
                'post_data' => input('post.'),
                'method' => request()->method()
            ]);
            
            $device_name = input('post.device_name');
            $mdid = input('post.mdid/d', 0);

            // 参数验证
            if (empty($device_name)) {
                \think\facade\Log::error('device_generate_token: 设备名称为空');
                return json(['status' => 0, 'msg' => '请输入设备名称']);
            }

            // 生成设备唯一标识（device_id）
            // 格式：DEVICE_{aid}_{bid}_{时间戳}_{随机数}
            $device_id = 'DEVICE_' . $this->aid . '_' . $this->bid . '_' . time() . '_' . rand(100000, 999999);
            
            // 生成设备令牌（64位随机字符串）
            $device_token = md5($this->aid . $this->bid . $mdid . time() . rand(1000, 9999));

            $insertData = [
                'aid' => $this->aid,
                'bid' => $this->bid,
                'mdid' => $mdid,
                'device_name' => $device_name,
                'device_id' => $device_id,
                'device_token' => $device_token,
                'status' => 1,
                'create_time' => time()
            ];
            
            \think\facade\Log::info('device_generate_token: 准备插入数据', $insertData);

            $result = Db::name('ai_travel_photo_device')->insert($insertData);
            
            if ($result) {
                \think\facade\Log::info('device_generate_token: 插入成功', [
                    'device_id' => $device_id,
                    'device_token' => $device_token
                ]);
                return json([
                    'status' => 1, 
                    'msg' => '生成成功', 
                    'data' => [
                        'device_id' => $device_id,
                        'device_token' => $device_token
                    ]
                ]);
            } else {
                \think\facade\Log::error('device_generate_token: 插入失败');
                return json(['status' => 0, 'msg' => '生成失败，请重试']);
            }
        } catch (\Exception $e) {
            \think\facade\Log::error('device_generate_token 异常', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return json(['status' => 0, 'msg' => '系统错误：' . $e->getMessage()]);
        }
    }

    /**
     * 设置 - AI旅拍配置（扩展为Tab页支持）
     */
    public function settings()
    {
        // 超级管理员bid为0时，使用aid对应的第一个商家bid
        $targetBid = $this->getTargetBid();

        // 判断是否为管理员（groupid为0或isadmin>0）
        $isAdmin = $this->user['groupid'] === 0 || $this->user['isadmin'] > 0;

        // 获取Tab类型
        $tab = input('tab', 'basic');

        if (request()->isPost()) {
            if ($tab == 'package' && $isAdmin) {
                return $this->savePackageSettings();
            }
            return $this->saveBasicSettings($targetBid);
        }

        // GET请求，加载页面
        $business = Db::name('business')->where('id', $targetBid)->find();

        // 获取套餐设置
        $packageSettings = $this->getPackageSettings();

        View::assign('business', $business);
        View::assign('targetBid', $targetBid);
        View::assign('is_admin', $isAdmin);
        View::assign('package_settings', $packageSettings);
        return View::fetch();
    }

    /**
     * 获取套餐设置
     */
    private function getPackageSettings()
    {
        $defaultSettings = [
            'space' => [
                ['name' => '10GB 云空间', 'size' => 10240, 'price' => 99, 'desc' => '适合小型商户'],
                ['name' => '50GB 云空间', 'size' => 51200, 'price' => 399, 'desc' => '适合中型商户'],
                ['name' => '200GB 云空间', 'size' => 204800, 'price' => 999, 'desc' => '适合大型商户'],
                ['name' => '1TB 云空间', 'size' => 1048576, 'price' => 2999, 'desc' => '适合旗舰店']
            ],
            'balance' => [
                ['name' => '充值100元', 'amount' => 100, 'gift' => 10, 'desc' => '赠送10元'],
                ['name' => '充值500元', 'amount' => 500, 'gift' => 80, 'desc' => '赠送80元'],
                ['name' => '充值1000元', 'amount' => 1000, 'gift' => 200, 'desc' => '赠送200元'],
                ['name' => '充值5000元', 'amount' => 5000, 'gift' => 1500, 'desc' => '赠送1500元']
            ],
            'renew' => [
                ['name' => '月卡', 'days' => 30, 'price' => 299, 'desc' => '30天有效期'],
                ['name' => '季卡', 'days' => 90, 'price' => 799, 'desc' => '90天有效期 省98元'],
                ['name' => '年卡', 'days' => 365, 'price' => 2499, 'desc' => '365天有效期 省1099元'],
                ['name' => '永久', 'days' => 99999, 'price' => 9999, 'desc' => '一次购买永久使用']
            ]
        ];

        $savedSettings = Db::name('admin_set')->where('aid', $this->aid)->value('ai_travel_photo_packages');
        if ($savedSettings) {
            $savedSettings = json_decode($savedSettings, true);
            return array_merge($defaultSettings, $savedSettings);
        }

        return $defaultSettings;
    }

    /**
     * 保存套餐设置
     */
    private function savePackageSettings()
    {
        try {
            $data = input('post.');

            $packageSettings = [
                'space' => [],
                'balance' => [],
                'renew' => []
            ];

            // 处理云空间套餐
            if (isset($data['space']) && is_array($data['space'])) {
                foreach ($data['space'] as $item) {
                    if (!empty($item['name']) && $item['size'] > 0 && $item['price'] > 0) {
                        $packageSettings['space'][] = [
                            'name' => $item['name'],
                            'size' => intval($item['size']),
                            'price' => floatval($item['price']),
                            'desc' => $item['desc'] ?? ''
                        ];
                    }
                }
            }

            // 处理余额充值套餐
            if (isset($data['balance']) && is_array($data['balance'])) {
                foreach ($data['balance'] as $item) {
                    if (!empty($item['name']) && $item['amount'] > 0) {
                        $packageSettings['balance'][] = [
                            'name' => $item['name'],
                            'amount' => floatval($item['amount']),
                            'gift' => floatval($item['gift'] ?? 0),
                            'desc' => $item['desc'] ?? ''
                        ];
                    }
                }
            }

            // 处理续费套餐
            if (isset($data['renew']) && is_array($data['renew'])) {
                foreach ($data['renew'] as $item) {
                    if (!empty($item['name']) && $item['days'] > 0 && $item['price'] > 0) {
                        $packageSettings['renew'][] = [
                            'name' => $item['name'],
                            'days' => intval($item['days']),
                            'price' => floatval($item['price']),
                            'desc' => $item['desc'] ?? ''
                        ];
                    }
                }
            }

            // 保存到数据库
            Db::name('admin_set')->where('aid', $this->aid)->update([
                'ai_travel_photo_packages' => json_encode($packageSettings)
            ]);

            return json(['status' => 1, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 获取目标商家ID（处理超级管理员继承）
     */
    private function getTargetBid()
    {
        if ($this->bid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
            if (!$targetBid) {
                $this->error('未找到对应的商家');
            }
            return $targetBid;
        }
        return $this->bid;
    }
    
    /**
     * 保存基础设置
     */
    private function saveBasicSettings($targetBid)
    {
        $data = input('post.');

        try {
            // 处理checkbox类型字段（未选中时不会提交）
            $updateData = [
                'ai_travel_photo_enabled' => isset($data['ai_travel_photo_enabled']) ? 1 : 0,
                'ai_photo_price' => floatval($data['ai_photo_price'] ?? 9.9),
                'ai_video_price' => floatval($data['ai_video_price'] ?? 29.9),
                'ai_logo_watermark' => $data['ai_logo_watermark'] ?? '',
                'ai_watermark_position' => intval($data['ai_watermark_position'] ?? 1),
                'ai_qrcode_expire_days' => intval($data['ai_qrcode_expire_days'] ?? 30),
                'ai_auto_generate_video' => isset($data['ai_auto_generate_video']) ? 1 : 0,
                'ai_video_duration' => intval($data['ai_video_duration'] ?? 5),
                'ai_max_scenes' => intval($data['ai_max_scenes'] ?? 10)
            ];
            
            // 数据验证
            if ($updateData['ai_photo_price'] <= 0) {
                return json(['status' => 0, 'msg' => '图片价格必须大于0']);
            }
            if ($updateData['ai_video_price'] <= 0) {
                return json(['status' => 0, 'msg' => '视频价格必须大于0']);
            }
            
            Db::name('business')->where('id', $targetBid)->update($updateData);

            return json(['status' => 1, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 检查AI旅拍功能是否开启
     */
    private function checkAiEnabled()
    {
        $business = Db::name('business')
            ->where('id', $this->bid)
            ->value('ai_travel_photo_enabled');

        return $business == 1;
    }

    /**
     * 任务列表 - 全流程任务追踪
     */
    public function task_list()
    {
        // AJAX请求返回数据
        if (request()->isAjax()) {
            try {
                // 超级管理员bid为0时，使用aid对应的第一个商家
                $targetBid = $this->getTargetBid();
                
                $where = [
                    ['aid', '=', $this->aid],
                    ['bid', '=', $targetBid]
                ];

                // 门店筛选
                $mdid = input('param.mdid', '');
                if ($mdid !== '') {
                    $where[] = ['mdid', '=', $mdid];
                }

                // 设备筛选
                $device_id = input('param.device_id', '');
                if ($device_id !== '') {
                    $where[] = ['device_id', '=', $device_id];
                }

                // 任务状态筛选
                $task_status = input('param.task_status', '');
                if ($task_status !== '') {
                    $where[] = ['task_status_summary', '=', $task_status];
                }

                // 日期范围筛选
                $start_date = input('param.start_date', '');
                $end_date = input('param.end_date', '');
                if ($start_date) {
                    $where[] = ['create_time', '>=', strtotime($start_date . ' 00:00:00')];
                }
                if ($end_date) {
                    $where[] = ['create_time', '<=', strtotime($end_date . ' 23:59:59')];
                }

                // 关键词搜索 (文件名或MD5)
                $keyword = input('param.keyword', '');
                if ($keyword) {
                    $where[] = Db::raw("(file_name LIKE '%{$keyword}%' OR portrait_md5 LIKE '%{$keyword}%')");
                }

                // 排序方式
                $orderField = input('param.order_field', 'create_time');
                $orderType = input('param.order_type', 'desc');
                $orderBy = $orderField . ' ' . strtoupper($orderType);

                $page = input('page/d', 1);
                $limit = input('limit/d', 20);

                // 使用视图查询（禁用表前缀）
                $list = Db::table('view_ai_travel_task_summary')
                    ->where($where)
                    ->order($orderBy)
                    ->page($page, $limit)
                    ->select()
                    ->toArray();

                // 处理数据显示
                foreach ($list as &$item) {
                    // 格式化时间
                    $item['create_time_text'] = date('Y-m-d H:i:s', $item['create_time']);
                    $item['latest_update_time_text'] = $item['latest_update_time'] ? date('Y-m-d H:i:s', $item['latest_update_time']) : '-';
                    
                    // 格式化文件大小
                    $item['file_size_text'] = $this->formatFileSize($item['file_size']);
                    
                    // 抠图状态文本
                    $cutoutStatus = [
                        0 => '<span class="layui-badge layui-bg-gray">待处理</span>',
                        1 => '<span class="layui-badge layui-bg-blue">处理中</span>',
                        2 => '<span class="layui-badge layui-bg-green">成功</span>',
                        3 => '<span class="layui-badge layui-bg-red">失败</span>'
                    ];
                    $item['cutout_status_text'] = $cutoutStatus[$item['cutout_status']] ?? '-';
                    
                    // 任务状态摘要文本
                    $statusSummary = [
                        'pending' => '<span class="layui-badge layui-bg-gray">待处理</span>',
                        'processing' => '<span class="layui-badge layui-bg-blue">进行中</span>',
                        'completed' => '<span class="layui-badge layui-bg-green">已完成</span>',
                        'partial_failed' => '<span class="layui-badge layui-bg-orange">部分失败</span>',
                        'all_failed' => '<span class="layui-badge layui-bg-red">全部失败</span>'
                    ];
                    $item['task_status_summary_text'] = $statusSummary[$item['task_status_summary']] ?? '-';
                    
                    // 计算完成进度
                    if ($item['total_tasks'] > 0) {
                        $item['progress'] = round($item['success_tasks'] / $item['total_tasks'] * 100);
                    } else {
                        $item['progress'] = 0;
                    }
                    
                    // 平均耗时
                    if ($item['success_tasks'] > 0) {
                        $avgCostTime = $item['total_cost_time'] / $item['success_tasks'];
                        $item['avg_cost_time_text'] = round($avgCostTime / 1000, 2) . 's';
                    } else {
                        $item['avg_cost_time_text'] = '-';
                    }
                }
                unset($item);

                $count = Db::table('view_ai_travel_task_summary')
                    ->where($where)
                    ->count();

                return json([
                    'code' => 0,
                    'msg' => '',
                    'count' => $count,
                    'data' => $list
                ]);
            } catch (\Exception $e) {
                \think\facade\Log::error('任务列表查询失败', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return json([
                    'code' => 1,
                    'msg' => '查询失败：' . $e->getMessage(),
                    'count' => 0,
                    'data' => []
                ]);
            }
        }

        // 页面渲染
        $targetBid = $this->getTargetBid();
        
        // 获取门店列表
        $mendian_list = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->select();

        // 获取设备列表
        $device_list = Db::name('ai_travel_photo_device')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->where('status', 1)
            ->select();

        View::assign('mendian_list', $mendian_list);
        View::assign('device_list', $device_list);
        return View::fetch();
    }

    /**
     * 任务详情 - 查看完整任务链路
     */
    public function task_detail()
    {
        $portrait_id = input('param.portrait_id/d', 0);
        
        if (!$portrait_id) {
            $this->error('参数错误');
        }

        try {
            $targetBid = $this->getTargetBid();
            
            // 查询人像基础信息
            $portrait = Db::name('ai_travel_photo_portrait')
                ->alias('p')
                ->leftJoin('ai_travel_photo_device d', 'p.device_id = d.id')
                ->leftJoin('mendian m', 'p.mdid = m.id')
                ->where('p.id', $portrait_id)
                ->where('p.aid', $this->aid)
                ->where('p.bid', $targetBid)
                ->field('p.*, d.device_name, m.name as mendian_name')
                ->find();

            if (!$portrait) {
                $this->error('人像不存在');
            }

            // 格式化人像信息
            $portraitInfo = [
                'portrait_id' => $portrait['id'],
                'file_name' => $portrait['file_name'],
                'file_size' => $this->formatFileSize($portrait['file_size']),
                'width' => $portrait['width'],
                'height' => $portrait['height'],
                'md5' => $portrait['md5'],
                'original_url' => $portrait['original_url'],
                'thumbnail_url' => $portrait['thumbnail_url'],
                'cutout_url' => $portrait['cutout_url'],
                'cutout_status' => $portrait['cutout_status'],
                'cutout_status_text' => $this->getCutoutStatusText($portrait['cutout_status']),
                'device_name' => $portrait['device_name'] ?? '-',
                'mdid' => $portrait['mdid'],
                'mendian_name' => $portrait['mendian_name'] ?? '-',
                'create_time' => $portrait['create_time'],
                'create_time_text' => date('Y-m-d H:i:s', $portrait['create_time'])
            ];

            // 查询所有任务生成记录
            $generations = Db::name('ai_travel_photo_generation')
                ->alias('g')
                ->leftJoin('ai_travel_photo_scene s', 'g.scene_id = s.id')
                ->where('g.portrait_id', $portrait_id)
                ->field('g.*, s.name as scene_name')
                ->order('g.id ASC')
                ->select()
                ->toArray();

            // 组装任务链路数据
            $taskChain = $this->buildTaskChain($portrait, $generations);

            // 任务统计
            $taskSummary = [
                'total_tasks' => count($generations),
                'success_tasks' => 0,
                'failed_tasks' => 0,
                'processing_tasks' => 0,
                'pending_tasks' => 0,
                'cancelled_tasks' => 0,
                'total_cost_time' => 0,
                'avg_cost_time' => 0
            ];

            foreach ($generations as $gen) {
                switch ($gen['status']) {
                    case 2:
                        $taskSummary['success_tasks']++;
                        $taskSummary['total_cost_time'] += $gen['cost_time'];
                        break;
                    case 3:
                        $taskSummary['failed_tasks']++;
                        break;
                    case 1:
                        $taskSummary['processing_tasks']++;
                        break;
                    case 0:
                        $taskSummary['pending_tasks']++;
                        break;
                    case 4:
                        $taskSummary['cancelled_tasks']++;
                        break;
                }
            }

            if ($taskSummary['success_tasks'] > 0) {
                $taskSummary['avg_cost_time'] = round($taskSummary['total_cost_time'] / $taskSummary['success_tasks'] / 1000, 2);
            }

            View::assign('portrait_info', $portraitInfo);
            View::assign('task_summary', $taskSummary);
            View::assign('task_chain', $taskChain);
            return View::fetch();
        } catch (\Exception $e) {
            \think\facade\Log::error('任务详情查询失败', [
                'portrait_id' => $portrait_id,
                'error' => $e->getMessage()
            ]);
            $this->error('查询失败：' . $e->getMessage());
        }
    }

    /**
     * 任务重试 - 重新推送失败任务到队列
     */
    public function task_retry()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }

        try {
            $generation_id = input('post.generation_id/d', 0);
            
            if (!$generation_id) {
                return json(['status' => 0, 'msg' => '参数错误']);
            }

            $targetBid = $this->getTargetBid();

            // 查询任务信息
            $generation = Db::name('ai_travel_photo_generation')
                ->where('id', $generation_id)
                ->where('bid', $targetBid)
                ->find();

            if (!$generation) {
                return json(['status' => 0, 'msg' => '任务不存在']);
            }

            // 检查任务状态
            if ($generation['status'] != 3) {
                return json(['status' => 0, 'msg' => '只能重试失败的任务']);
            }

            // 检查重试次数
            if ($generation['retry_count'] >= 3) {
                return json(['status' => 0, 'msg' => '已达到最大重试次数']);
            }

            // 更新任务状态
            Db::name('ai_travel_photo_generation')
                ->where('id', $generation_id)
                ->update([
                    'status' => 0,
                    'retry_count' => $generation['retry_count'] + 1,
                    'error_msg' => '',
                    'update_time' => time()
                ]);

            // 重新推送到队列
            $queueName = $this->getQueueNameByType($generation['generation_type']);
            $jobClass = $this->getJobClassByType($generation['generation_type']);
            
            \think\facade\Queue::push(
                $jobClass,
                ['generation_id' => $generation_id],
                $queueName
            );

            \think\facade\Log::info('任务重试成功', [
                'generation_id' => $generation_id,
                'retry_count' => $generation['retry_count'] + 1
            ]);

            return json(['status' => 1, 'msg' => '重试成功']);
        } catch (\Exception $e) {
            \think\facade\Log::error('任务重试失败', [
                'error' => $e->getMessage()
            ]);
            return json(['status' => 0, 'msg' => '重试失败：' . $e->getMessage()]);
        }
    }

    /**
     * 任务取消 - 取消待处理/排队中的任务
     */
    public function task_cancel()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }

        try {
            $generation_id = input('post.generation_id/d', 0);
            
            if (!$generation_id) {
                return json(['status' => 0, 'msg' => '参数错误']);
            }

            $targetBid = $this->getTargetBid();

            // 查询任务信息
            $generation = Db::name('ai_travel_photo_generation')
                ->where('id', $generation_id)
                ->where('bid', $targetBid)
                ->find();

            if (!$generation) {
                return json(['status' => 0, 'msg' => '任务不存在']);
            }

            // 检查任务状态
            if (!in_array($generation['status'], [0, 1])) {
                return json(['status' => 0, 'msg' => '只能取消待处理或处理中的任务']);
            }

            // 更新任务状态为已取消
            Db::name('ai_travel_photo_generation')
                ->where('id', $generation_id)
                ->update([
                    'status' => 4,
                    'finish_time' => time(),
                    'update_time' => time()
                ]);

            \think\facade\Log::info('任务取消成功', [
                'generation_id' => $generation_id
            ]);

            return json(['status' => 1, 'msg' => '取消成功']);
        } catch (\Exception $e) {
            \think\facade\Log::error('任务取消失败', [
                'error' => $e->getMessage()
            ]);
            return json(['status' => 0, 'msg' => '取消失败：' . $e->getMessage()]);
        }
    }

    /**
     * 批量操作任务
     */
    public function task_batch()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }

        try {
            $action = input('post.action', '');
            $generation_ids = input('post.generation_ids/a', []);
            
            if (empty($action) || empty($generation_ids)) {
                return json(['status' => 0, 'msg' => '参数错误']);
            }

            $targetBid = $this->getTargetBid();
            $successCount = 0;
            $failCount = 0;

            foreach ($generation_ids as $generation_id) {
                // 查询任务
                $generation = Db::name('ai_travel_photo_generation')
                    ->where('id', $generation_id)
                    ->where('bid', $targetBid)
                    ->find();

                if (!$generation) {
                    $failCount++;
                    continue;
                }

                if ($action == 'retry') {
                    // 批量重试
                    if ($generation['status'] == 3 && $generation['retry_count'] < 3) {
                        Db::name('ai_travel_photo_generation')
                            ->where('id', $generation_id)
                            ->update([
                                'status' => 0,
                                'retry_count' => $generation['retry_count'] + 1,
                                'error_msg' => '',
                                'update_time' => time()
                            ]);

                        $queueName = $this->getQueueNameByType($generation['generation_type']);
                        $jobClass = $this->getJobClassByType($generation['generation_type']);
                        
                        \think\facade\Queue::push(
                            $jobClass,
                            ['generation_id' => $generation_id],
                            $queueName
                        );

                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } elseif ($action == 'cancel') {
                    // 批量取消
                    if (in_array($generation['status'], [0, 1])) {
                        Db::name('ai_travel_photo_generation')
                            ->where('id', $generation_id)
                            ->update([
                                'status' => 4,
                                'finish_time' => time(),
                                'update_time' => time()
                            ]);
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                }
            }

            $msg = "成功{$successCount}个";
            if ($failCount > 0) {
                $msg .= "，失败{$failCount}个";
            }

            return json(['status' => 1, 'msg' => $msg]);
        } catch (\Exception $e) {
            \think\facade\Log::error('批量操作失败', [
                'error' => $e->getMessage()
            ]);
            return json(['status' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
    }

    /**
     * 构建任务链路数据
     */
    private function buildTaskChain($portrait, $generations)
    {
        $chain = [];

        // 1. 抠图阶段
        $chain[] = [
            'stage' => 'cutout',
            'stage_name' => '智能抠图',
            'status' => $portrait['cutout_status'],
            'status_text' => $this->getCutoutStatusText($portrait['cutout_status']),
            'result_url' => $portrait['cutout_url'] ?? '',
            'is_collapsed' => true
        ];

        // 2. 图生图阶段（按场景分组）
        $imageGenerations = [];
        foreach ($generations as $gen) {
            if ($gen['generation_type'] == 1) {
                $sceneId = $gen['scene_id'];
                if (!isset($imageGenerations[$sceneId])) {
                    $imageGenerations[$sceneId] = [
                        'scene_id' => $sceneId,
                        'scene_name' => $gen['scene_name'],
                        'tasks' => []
                    ];
                }
                $imageGenerations[$sceneId]['tasks'][] = $this->formatGenerationItem($gen);
            }
        }

        if (!empty($imageGenerations)) {
            $chain[] = [
                'stage' => 'image_generation',
                'stage_name' => '图生图',
                'children' => array_values($imageGenerations),
                'is_collapsed' => false
            ];
        }

        // 3. 图生视频阶段（按场景分组）
        $videoGenerations = [];
        foreach ($generations as $gen) {
            if ($gen['generation_type'] == 3) {
                $sceneId = $gen['scene_id'];
                if (!isset($videoGenerations[$sceneId])) {
                    $videoGenerations[$sceneId] = [
                        'scene_id' => $sceneId,
                        'scene_name' => $gen['scene_name'],
                        'tasks' => []
                    ];
                }
                $videoGenerations[$sceneId]['tasks'][] = $this->formatGenerationItem($gen);
            }
        }

        if (!empty($videoGenerations)) {
            $chain[] = [
                'stage' => 'video_generation',
                'stage_name' => '图生视频',
                'children' => array_values($videoGenerations),
                'is_collapsed' => false
            ];
        }

        return $chain;
    }

    /**
     * 格式化生成记录项
     */
    private function formatGenerationItem($generation)
    {
        $statusTexts = [
            0 => '待处理',
            1 => '处理中',
            2 => '成功',
            3 => '失败',
            4 => '已取消'
        ];

        $item = [
            'task_id' => $generation['id'],
            'generation_type' => $generation['generation_type'],
            'generation_type_text' => $this->getGenerationTypeText($generation['generation_type']),
            'status' => $generation['status'],
            'status_text' => $statusTexts[$generation['status']] ?? '未知',
            'start_time' => $generation['start_time'] ?? 0,
            'start_time_text' => $generation['start_time'] ? date('Y-m-d H:i:s', $generation['start_time']) : '-',
            'finish_time' => $generation['finish_time'] ?? 0,
            'finish_time_text' => $generation['finish_time'] ? date('Y-m-d H:i:s', $generation['finish_time']) : '-',
            'cost_time' => $generation['cost_time'] ?? 0,
            'cost_time_text' => $generation['cost_time'] ? round($generation['cost_time'] / 1000, 2) . 's' : '-',
            'retry_count' => $generation['retry_count'] ?? 0,
            'error_msg' => $generation['error_msg'] ?? '',
            'result_url' => '',
            'thumbnail_url' => ''
        ];

        // 查询结果
        if ($generation['status'] == 2) {
            $result = Db::name('ai_travel_photo_result')
                ->where('generation_id', $generation['id'])
                ->find();
            if ($result) {
                $item['result_id'] = $result['id'];
                $item['result_url'] = $result['url'] ?? '';
                $item['thumbnail_url'] = $result['thumbnail_url'] ?? '';
            }
        }

        return $item;
    }

    /**
     * 获取抠图状态文本
     */
    private function getCutoutStatusText($status)
    {
        $texts = [
            0 => '待处理',
            1 => '处理中',
            2 => '成功',
            3 => '失败'
        ];
        return $texts[$status] ?? '未知';
    }

    /**
     * 获取生成类型文本
     */
    private function getGenerationTypeText($type)
    {
        $texts = [
            1 => '图生图',
            2 => '多镜头',
            3 => '图生视频'
        ];
        return $texts[$type] ?? '未知';
    }

    /**
     * 根据生成类型获取队列名称
     */
    private function getQueueNameByType($type)
    {
        $queues = [
            1 => 'ai_image_generation',
            2 => 'ai_image_generation',
            3 => 'ai_video_generation'
        ];
        return $queues[$type] ?? 'ai_image_generation';
    }

    /**
     * 根据生成类型获取Job类名
     */
    private function getJobClassByType($type)
    {
        $jobs = [
            1 => 'app\\job\\ImageGenerationJob',
            2 => 'app\\job\\ImageGenerationJob',
            3 => 'app\\job\\VideoGenerationJob'
        ];
        return $jobs[$type] ?? 'app\\job\\ImageGenerationJob';
    }

    /**
     * 格式化文件大小
     */
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    // ==================== 模型分类管理 ====================

    /**
     * 模型分类列表
     */
    public function model_category_list()
    {
        if (request()->isAjax()) {
            try {
                $keyword = input('param.keyword', '');
                $is_system = input('param.is_system', '');
                $level = input('param.level', ''); // 新增：层级筛选
                $parent_code = input('param.parent_code', ''); // 新增：父级分类
                
                $where = [];
                
                if ($keyword) {
                    $where[] = ['c.name|c.code', 'like', '%' . $keyword . '%'];
                }
                
                if ($is_system !== '') {
                    $where[] = ['c.is_system', '=', $is_system]; // 添加表别名前缀
                }
                
                if ($level !== '') {
                    $where[] = ['c.level', '=', $level]; // 添加表别名前缀
                }
                
                if ($parent_code !== '') {
                    $where[] = ['c.parent_code', '=', $parent_code]; // 添加表别名前缀
                }
                
                $page = input('page/d', 1);
                $limit = input('limit/d', 20);
                
                $list = Db::name('ai_model_category')
                    ->alias('c')
                    ->leftJoin('ai_model_category p', 'c.parent_code = p.code')
                    ->where($where)
                    ->field('c.*, p.name as parent_name')
                    ->order('c.level ASC, c.sort DESC, c.id ASC')
                    ->page($page, $limit)
                    ->select()
                    ->toArray();
                
                // count查询也需要使用表别名
                $count = Db::name('ai_model_category')
                    ->alias('c')
                    ->where($where)
                    ->count();
                
                return json([
                    'code' => 0,
                    'msg' => '',
                    'count' => $count,
                    'data' => $list
                ]);
            } catch (\Exception $e) {
                return json([
                    'code' => 1,
                    'msg' => $e->getMessage(),
                    'count' => 0,
                    'data' => []
                ]);
            }
        }
        
        // 获取一级分类列表（用于筛选）
        $level1Categories = Db::name('ai_model_category')
            ->where('level', 1)
            ->where('status', 1)
            ->order('sort DESC')
            ->select()
            ->toArray();
        
        View::assign('level1Categories', $level1Categories);
        return View::fetch();
    }

    /**
     * 编辑模型分类
     */
    public function model_category_edit()
    {
        $id = input('param.id/d', 0);
        
        if (request()->isPost()) {
            try {
                $data = [
                    'name' => input('post.name'),
                    'code' => input('post.code'),
                    'level' => input('post.level/d', 1), // 新增：层级
                    'parent_code' => input('post.parent_code', ''), // 新增：父级代码
                    'description' => input('post.description', ''),
                    'icon' => input('post.icon', ''),
                    'sort' => input('post.sort/d', 0),
                    'status' => input('post.status/d', 1),
                ];
                
                // 如果是一级分类，parent_code设置为NULL
                if ($data['level'] == 1) {
                    $data['parent_code'] = null;
                } else {
                    // 二级分类必须有父级
                    if (empty($data['parent_code'])) {
                        return json(['code' => 1, 'msg' => '二级分类必须选择父级分类']);
                    }
                }
                
                // 验证必填字段
                if (empty($data['name'])) {
                    return json(['code' => 1, 'msg' => '请输入分类名称']);
                }
                
                if (empty($data['code'])) {
                    return json(['code' => 1, 'msg' => '请输入分类代码']);
                }
                
                // 验证代码格式（字母、数字、下划线）
                if (!preg_match('/^[a-z0-9_]+$/', $data['code'])) {
                    return json(['code' => 1, 'msg' => '分类代码只能包含小写字母、数字和下划线']);
                }
                
                if ($id > 0) {
                    // 编辑
                    $category = Db::name('ai_model_category')->where('id', $id)->find();
                    
                    if (!$category) {
                        return json(['code' => 1, 'msg' => '分类不存在']);
                    }
                    
                    // 系统分类不允许修改代码和层级
                    if ($category['is_system'] == 1) {
                        if ($category['code'] != $data['code']) {
                            return json(['code' => 1, 'msg' => '系统分类代码不允许修改']);
                        }
                        if ($category['level'] != $data['level']) {
                            return json(['code' => 1, 'msg' => '系统分类层级不允许修改']);
                        }
                    }
                    
                    // 检查代码唯一性（排除自己）
                    $exists = Db::name('ai_model_category')
                        ->where('code', $data['code'])
                        ->where('id', '<>', $id)
                        ->count();
                    
                    if ($exists > 0) {
                        return json(['code' => 1, 'msg' => '分类代码已存在']);
                    }
                    
                    $data['update_time'] = time();
                    
                    Db::name('ai_model_category')->where('id', $id)->update($data);
                    
                    return json(['code' => 0, 'msg' => '修改成功']);
                } else {
                    // 新增
                    // 检查代码唯一性
                    $exists = Db::name('ai_model_category')
                        ->where('code', $data['code'])
                        ->count();
                    
                    if ($exists > 0) {
                        return json(['code' => 1, 'msg' => '分类代码已存在']);
                    }
                    
                    $data['is_system'] = 0; // 自定义分类
                    $data['create_time'] = time();
                    
                    Db::name('ai_model_category')->insert($data);
                    
                    return json(['code' => 0, 'msg' => '添加成功']);
                }
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => $e->getMessage()]);
            }
        }
        
        // 获取分类信息
        $category = [];
        if ($id > 0) {
            $category = Db::name('ai_model_category')->where('id', $id)->find();
        }
        
        // 获取一级分类列表（用于选择父级）
        $level1Categories = Db::name('ai_model_category')
            ->where('level', 1)
            ->where('status', 1)
            ->order('sort DESC')
            ->select()
            ->toArray();
        
        View::assign('category', $category);
        View::assign('level1Categories', $level1Categories);
        return View::fetch();
    }

    /**
     * 删除模型分类
     */
    public function model_category_delete()
    {
        $id = input('param.id/d', 0);
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        
        try {
            $category = Db::name('ai_model_category')->where('id', $id)->find();
            
            if (!$category) {
                return json(['code' => 1, 'msg' => '分类不存在']);
            }
            
            // 系统分类不允许删除
            if ($category['is_system'] == 1) {
                return json(['code' => 1, 'msg' => '系统分类不允许删除']);
            }
            
            // 检查是否有API配置引用
            $count = Db::name('ai_travel_photo_model')
                ->where('category_code', $category['code'])
                ->count();
            
            if ($count > 0) {
                return json(['code' => 1, 'msg' => '该分类下存在API配置，无法删除']);
            }
            
            Db::name('ai_model_category')->where('id', $id)->delete();
            
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    // ==================== API配置管理 ====================

    /**
     * API配置列表
     */
    public function model_config_list()
    {
        if (request()->isAjax()) {
            try {
                $category_code = input('param.category_code', '');
                $mdid = input('param.mdid', '');
                $status = input('param.status', '');
                
                $where = [
                    ['m.aid', '=', $this->aid],
                    ['m.bid', '=', $this->bid]
                ];
                
                if ($category_code) {
                    $where[] = ['m.category_code', '=', $category_code];
                }
                
                if ($mdid !== '') {
                    $where[] = ['m.mdid', '=', $mdid];
                }
                
                if ($status !== '') {
                    $where[] = ['m.status', '=', $status];
                }
                
                $page = input('page/d', 1);
                $limit = input('limit/d', 20);
                
                $list = Db::name('ai_travel_photo_model')
                    ->alias('m')
                    ->leftJoin('ai_model_category c', 'm.category_code = c.code')
                    ->leftJoin('mendian d', 'm.mdid = d.id')
                    ->where($where)
                    ->field('m.*, c.name as category_name, d.name as mendian_name')
                    ->order('m.priority DESC, m.id DESC')
                    ->page($page, $limit)
                    ->select()
                    ->toArray();
                
                // 计算成功率和当前并发
                foreach ($list as &$item) {
                    $item['success_rate'] = $item['use_count'] > 0 
                        ? round($item['success_count'] / $item['use_count'] * 100, 2) 
                        : 0;
                    $item['current_concurrent_display'] = AiModelService::getCurrentConcurrent($item['id']) ?? 0;
                    
                    // 脱敏显示API密钥
                    if (!empty($item['api_key']) && strlen($item['api_key']) > 8) {
                        $item['api_key_display'] = substr($item['api_key'], 0, 4) . '****' . substr($item['api_key'], -4);
                    } else {
                        $item['api_key_display'] = '****';
                    }
                }
                
                $count = Db::name('ai_travel_photo_model')
                    ->alias('m')
                    ->where($where)
                    ->count();
                
                return json([
                    'code' => 0,
                    'msg' => '',
                    'count' => $count,
                    'data' => $list
                ]);
            } catch (\Exception $e) {
                return json([
                    'code' => 1,
                    'msg' => $e->getMessage(),
                    'count' => 0,
                    'data' => []
                ]);
            }
        }
        
        // 获取模型分类列表
        $categories = Db::name('ai_model_category')
            ->where('status', 1)
            ->order('sort DESC, id ASC')
            ->select()
            ->toArray();
        
        // 获取门店列表
        $mendian_list = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where('bid', $this->bid)
            ->field('id, name')
            ->select()
            ->toArray();
        
        View::assign('categories', $categories);
        View::assign('mendian_list', $mendian_list);
        return View::fetch();
    }

    /**
     * 编辑API配置
     */
    public function model_config_edit()
    {
        $id = input('param.id/d', 0);
        
        if (request()->isPost()) {
            try {
                $data = [
                    'model_name' => input('post.model_name'),
                    'short_name' => input('post.short_name', ''), // 新增：模型名称简写
                    'category_code' => input('post.category_code'),
                    'provider' => input('post.provider', ''),
                    'mdid' => input('post.mdid/d', 0),
                    'api_key' => input('post.api_key'),
                    'api_secret' => input('post.api_secret', ''),
                    'api_base_url' => input('post.api_base_url'),
                    'api_version' => input('post.api_version', ''),
                    'max_concurrent' => input('post.max_concurrent/d', 5),
                    'priority' => input('post.priority/d', 100),
                    'is_default' => input('post.is_default/d', 0),
                    'is_active' => input('post.is_active/d', 1),
                    'status' => input('post.status/d', 1),
                    'image_price' => input('post.image_price/f', 0.05),
                    'video_price' => input('post.video_price/f', 0.50),
                    'token_price' => input('post.token_price/f', 0.000001),
                    'timeout' => input('post.timeout/d', 180),
                    'max_retry' => input('post.max_retry/d', 3),
                ];
                
                // 验证必填字段
                if (empty($data['model_name'])) {
                    return json(['code' => 1, 'msg' => '请输入配置名称']);
                }
                
                if (empty($data['category_code'])) {
                    return json(['code' => 1, 'msg' => '请选择模型分类']);
                }
                
                if (empty($data['api_key'])) {
                    return json(['code' => 1, 'msg' => '请输入API密钥']);
                }
                
                if (empty($data['api_base_url'])) {
                    return json(['code' => 1, 'msg' => '请输入API基础URL']);
                }
                
                // 验证URL格式
                if (!filter_var($data['api_base_url'], FILTER_VALIDATE_URL)) {
                    return json(['code' => 1, 'msg' => 'API基础URL格式不正确']);
                }
                
                if ($id > 0) {
                    // 编辑
                    $config = Db::name('ai_travel_photo_model')->where('id', $id)->find();
                    
                    if (!$config) {
                        return json(['code' => 1, 'msg' => '配置不存在']);
                    }
                    
                    // 如果设置为默认，取消其他默认配置
                    if ($data['is_default'] == 1) {
                        Db::name('ai_travel_photo_model')
                            ->where('category_code', $data['category_code'])
                            ->where('aid', $this->aid)
                            ->where('bid', $this->bid)
                            ->where('id', '<>', $id)
                            ->update(['is_default' => 0]);
                    }
                    
                    $data['update_time'] = time();
                    
                    Db::name('ai_travel_photo_model')->where('id', $id)->update($data);
                    
                    return json(['code' => 0, 'msg' => '修改成功']);
                } else {
                    // 新增
                    // 如果设置为默认，取消其他默认配置
                    if ($data['is_default'] == 1) {
                        Db::name('ai_travel_photo_model')
                            ->where('category_code', $data['category_code'])
                            ->where('aid', $this->aid)
                            ->where('bid', $this->bid)
                            ->update(['is_default' => 0]);
                    }
                    
                    $data['aid'] = $this->aid;
                    $data['bid'] = $this->bid;
                    $data['model_type'] = $data['category_code']; // 兼容旧字段
                    $data['create_time'] = time();
                    
                    Db::name('ai_travel_photo_model')->insert($data);
                    
                    return json(['code' => 0, 'msg' => '添加成功']);
                }
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => $e->getMessage()]);
            }
        }
        
        // 获取配置信息
        $config = [];
        if ($id > 0) {
            $config = Db::name('ai_travel_photo_model')->where('id', $id)->find();
        }
        
        // 获取模型分类列表
        $categories = Db::name('ai_model_category')
            ->where('status', 1)
            ->order('sort DESC, id ASC')
            ->select()
            ->toArray();
        
        // 获取门店列表
        $mendian_list = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where('bid', $this->bid)
            ->field('id, name')
            ->select()
            ->toArray();
        
        View::assign('config', $config);
        View::assign('categories', $categories);
        View::assign('mendian_list', $mendian_list);
        return View::fetch();
    }

    /**
     * 删除API配置
     */
    public function model_config_delete()
    {
        $id = input('param.id/d', 0);
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        
        try {
            $config = Db::name('ai_travel_photo_model')->where('id', $id)->find();
            
            if (!$config) {
                return json(['code' => 1, 'msg' => '配置不存在']);
            }
            
            // 检查权限
            if ($config['aid'] != $this->aid || $config['bid'] != $this->bid) {
                return json(['code' => 1, 'msg' => '无权限删除']);
            }
            
            Db::name('ai_travel_photo_model')->where('id', $id)->delete();
            
            return json(['code' => 0, 'msg' => '删除成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 测试API连通性
     */
    public function model_config_test()
    {
        $id = input('param.id/d', 0);
        
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }
        
        try {
            $config = Db::name('ai_travel_photo_model')->where('id', $id)->find();
            
            if (!$config) {
                return json(['code' => 1, 'msg' => '配置不存在']);
            }
            
            // 调用测试服务
            $result = AiModelService::testConnection($config, 'cutout');
            
            if ($result['success']) {
                return json([
                    'code' => 0,
                    'msg' => '测试成功',
                    'data' => [
                        'response_time' => $result['response_time'] . 'ms',
                    ]
                ]);
            } else {
                return json([
                    'code' => 1,
                    'msg' => '测试失败：' . $result['error']
                ]);
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    // ==================== 调用统计 ====================

    /**
     * 调用统计 - 概览
     */
    public function model_usage_stats()
    {
        if (request()->isAjax()) {
            $type = input('param.type', 'overview');
            
            try {
                // 根据查询类型决定表别名前缀
                $prefix = ($type == 'list') ? 'l.' : '';
                
                $where = [
                    [$prefix . 'aid', '=', $this->aid],
                    [$prefix . 'bid', '=', $this->bid]
                ];
                
                // 时间范围筛选
                $start_time = input('param.start_time', '');
                $end_time = input('param.end_time', '');
                
                if ($start_time) {
                    $where[] = [$prefix . 'create_time', '>=', strtotime($start_time)];
                }
                
                if ($end_time) {
                    $where[] = [$prefix . 'create_time', '<=', strtotime($end_time . ' 23:59:59')];
                }
                
                if ($type == 'overview') {
                    // 统计概览
                    $stats = [
                        'total_count' => Db::name('ai_model_usage_log')->where($where)->count(),
                        'success_count' => Db::name('ai_model_usage_log')->where($where)->where('status', 1)->count(),
                        'fail_count' => Db::name('ai_model_usage_log')->where($where)->where('status', 0)->count(),
                        'total_cost' => Db::name('ai_model_usage_log')->where($where)->sum('cost_amount'),
                    ];
                    
                    $stats['success_rate'] = $stats['total_count'] > 0 
                        ? round($stats['success_count'] / $stats['total_count'] * 100, 2) 
                        : 0;
                    $stats['fail_rate'] = $stats['total_count'] > 0 
                        ? round($stats['fail_count'] / $stats['total_count'] * 100, 2) 
                        : 0;
                    
                    // 今日数据
                    $todayWhere = $where;
                    $todayWhere[] = ['create_time', '>=', strtotime(date('Y-m-d'))];
                    
                    $stats['today_count'] = Db::name('ai_model_usage_log')->where($todayWhere)->count();
                    $stats['today_cost'] = Db::name('ai_model_usage_log')->where($todayWhere)->sum('cost_amount');
                    
                    return json(['code' => 0, 'data' => $stats]);
                    
                } elseif ($type == 'trend') {
                    // 趋势图数据（近7天）
                    $days = input('param.days/d', 7);
                    $trendData = [];
                    
                    for ($i = $days - 1; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-{$i} days"));
                        $dayStart = strtotime($date);
                        $dayEnd = strtotime($date . ' 23:59:59');
                        
                        $dayWhere = $where;
                        $dayWhere[] = ['create_time', '>=', $dayStart];
                        $dayWhere[] = ['create_time', '<=', $dayEnd];
                        
                        $trendData[] = [
                            'date' => $date,
                            'count' => Db::name('ai_model_usage_log')->where($dayWhere)->count(),
                            'success_count' => Db::name('ai_model_usage_log')->where($dayWhere)->where('status', 1)->count(),
                            'fail_count' => Db::name('ai_model_usage_log')->where($dayWhere)->where('status', 0)->count(),
                        ];
                    }
                    
                    return json(['code' => 0, 'data' => $trendData]);
                    
                } elseif ($type == 'category') {
                    // 按模型分类统计
                    $categoryStats = Db::name('ai_model_usage_log')
                        ->where($where)
                        ->field('category_code, COUNT(*) as count')
                        ->group('category_code')
                        ->select()
                        ->toArray();
                    
                    // 关联分类名称
                    foreach ($categoryStats as &$item) {
                        $category = Db::name('ai_model_category')
                            ->where('code', $item['category_code'])
                            ->find();
                        $item['category_name'] = $category['name'] ?? $item['category_code'];
                    }
                    
                    return json(['code' => 0, 'data' => $categoryStats]);
                    
                } elseif ($type == 'list') {
                    // 调用明细列表
                    $category_code = input('param.category_code', '');
                    $business_type = input('param.business_type', '');
                    $status = input('param.status', '');
                    
                    if ($category_code) {
                        $where[] = ['l.category_code', '=', $category_code];
                    }
                    
                    if ($business_type) {
                        $where[] = ['l.business_type', '=', $business_type];
                    }
                    
                    if ($status !== '') {
                        $where[] = ['l.status', '=', $status];
                    }
                    
                    $page = input('page/d', 1);
                    $limit = input('limit/d', 20);
                    
                    $list = Db::name('ai_model_usage_log')
                        ->alias('l')
                        ->leftJoin('ai_travel_photo_model m', 'l.model_id = m.id')
                        ->leftJoin('ai_model_category c', 'l.category_code = c.code')
                        ->where($where)
                        ->field('l.*, m.model_name, c.name as category_name')
                        ->order('l.id DESC')
                        ->page($page, $limit)
                        ->select()
                        ->toArray();
                    
                    // 格式化时间和成本
                    foreach ($list as &$item) {
                        $item['create_time_text'] = date('Y-m-d H:i:s', $item['create_time']);
                        $item['cost_amount_text'] = '¥' . number_format($item['cost_amount'], 4);
                        $item['response_time_text'] = $item['response_time'] . 'ms';
                        $item['status_text'] = $item['status'] == 1 ? '成功' : '失败';
                    }
                    
                    $count = Db::name('ai_model_usage_log')
                        ->alias('l')
                        ->where($where)
                        ->count();
                    
                    return json([
                        'code' => 0,
                        'msg' => '',
                        'count' => $count,
                        'data' => $list
                    ]);
                }
                
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => $e->getMessage()]);
            }
        }
        
        // 获取模型分类列表
        $categories = Db::name('ai_model_category')
            ->where('status', 1)
            ->order('sort DESC, id ASC')
            ->select()
            ->toArray();
        
        View::assign('categories', $categories);
        return View::fetch();
    }
    
    /**
     * 模型场景配置 - 新版配置页面
     * GET /AiTravelPhoto/scene_config_new
     */
    public function scene_config_new()
    {
        $id = input('id/d', 0);
        
        try {
            // 获取场景信息
            $info = [];
            if ($id > 0) {
                $info = Db::name('ai_travel_photo_scene')->find($id);
                if (!$info) {
                    $this->error('场景不存在');
                }
            }
            
            // 获取场景类型配置
            $sceneTypes = config('ai_travel_photo.scene_type') ?: [];
            
            // 获取AI模型实例（从ddwx_ai_model_instance表）
            $models = Db::name('ai_model_instance')
                ->where('status', 1)
                ->field('id, model_name, model_code, provider')
                ->order('sort DESC, id ASC')
                ->select()
                ->toArray();
            
            // 获取门店列表
            $mendianWhere = [['aid', '=', $this->aid]];
            if ($this->bid > 0) {
                $mendianWhere[] = ['bid', '=', $this->bid];
            }
            $mendian_list = Db::name('mendian')
                ->where($mendianWhere)
                ->field('id, name')
                ->order('id ASC')
                ->select();
            
            View::assign('info', $info);
            View::assign('scene_types', $sceneTypes);
            View::assign('models', $models);
            View::assign('mendian_list', $mendian_list);
            View::assign('aid', $this->aid);
            View::assign('bid', $this->bid);
            
            return View::fetch();
            
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * 模型场景配置 - 保存
     * POST /AiTravelPhoto/scene_config_save
     */
    public function scene_config_save()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }
        
        try {
            $id = input('id/d', 0);
            $data = [
                'name' => input('name', ''),
                'scene_type' => input('scene_type/d', 0),
                'model_id' => input('model_id/d', 0),
                'category' => input('category', ''),
                'cover_image' => input('cover_image', ''),
                'description' => input('description', ''),
                'prompt' => input('prompt', ''),
                'negative_prompt' => input('negative_prompt', ''),
                'is_public' => input('is_public/d', 0),
                'status' => input('status/d', 1),
                'sort' => input('sort/d', 50),
                'mdid' => input('mdid/d', 0),
                'aid' => $this->aid,
                'bid' => $this->bid
            ];
            
            // 验证必填字段
            if (empty($data['name'])) {
                return json(['code' => 1, 'msg' => '场景名称不能为空']);
            }
            
            if (empty($data['scene_type'])) {
                return json(['code' => 1, 'msg' => '请选择场景类型']);
            }
            
            if (empty($data['model_id'])) {
                return json(['code' => 1, 'msg' => '请选择AI模型']);
            }
            
            if (empty($data['category'])) {
                return json(['code' => 1, 'msg' => '请选择分类']);
            }
            
            // 保存数据
            if ($id > 0) {
                // 更新
                $data['update_time'] = time();
                Db::name('ai_travel_photo_scene')->where('id', $id)->update($data);
                return json(['code' => 0, 'msg' => '更新成功']);
            } else {
                // 新增
                $data['create_time'] = time();
                $data['update_time'] = time();
                $sceneId = Db::name('ai_travel_photo_scene')->insertGetId($data);
                return json(['code' => 0, 'msg' => '添加成功', 'data' => ['id' => $sceneId]]);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    /**
     * 模型场景配置 - 列表
     * GET /AiTravelPhoto/scene_config_list
     */
    public function scene_config_list()
    {
        if (request()->isAjax()) {
            try {
                $where = [
                    ['aid', '=', $this->aid],
                    ['bid', '=', $this->bid]
                ];
                
                // 场景类型筛选
                $scene_type = input('param.scene_type', '');
                if ($scene_type !== '') {
                    $where[] = ['scene_type', '=', $scene_type];
                }
                
                // 分类筛选
                $category = input('param.category', '');
                if ($category) {
                    $where[] = ['category', '=', $category];
                }
                
                // 状态筛选
                $status = input('param.status', '');
                if ($status !== '') {
                    $where[] = ['status', '=', $status];
                }
                
                $page = input('page/d', 1);
                $limit = input('limit/d', 20);
                
                $list = Db::name('ai_travel_photo_scene')
                    ->where($where)
                    ->order('sort DESC, id DESC')
                    ->page($page, $limit)
                    ->select()
                    ->each(function($item) {
                        // 添加场景类型文本
                        $sceneTypes = config('ai_travel_photo.scene_type');
                        $item['scene_type_text'] = $sceneTypes[$item['scene_type']] ?? '未知类型';
                        return $item;
                    });
                
                $count = Db::name('ai_travel_photo_scene')
                    ->where($where)
                    ->count();
                
                return json([
                    'code' => 0,
                    'msg' => '',
                    'count' => $count,
                    'data' => $list
                ]);
                
            } catch (\Exception $e) {
                return json([
                    'code' => 1,
                    'msg' => $e->getMessage(),
                    'count' => 0,
                    'data' => []
                ]);
            }
        }
        
        // 获取场景类型和分类用于筛选
        $sceneTypes = config('ai_travel_photo.scene_type') ?: [];
        $categories = Db::name('ai_travel_photo_scene')
            ->where('aid', $this->aid)
            ->where('bid', $this->bid)
            ->group('category')
            ->column('category');
            
        View::assign('scene_types', $sceneTypes);
        View::assign('categories', $categories);
        return View::fetch();
    }
    
    /**
     * 模型场景配置 - 编辑
     * GET /AiTravelPhoto/scene_config_edit?id=1
     */
    public function scene_config_edit()
    {
        return $this->scene_config_new();
    }
    
    /**
     * 模型场景配置 - 删除
     * POST /AiTravelPhoto/scene_config_delete
     */
    public function scene_config_delete()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }
        
        try {
            $id = input('id/d', 0);
            if ($id <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }
            
            $result = Db::name('ai_travel_photo_scene')
                ->where('id', $id)
                ->where('aid', $this->aid)
                ->where('bid', $this->bid)
                ->delete();
                
            if ($result) {
                return json(['code' => 0, 'msg' => '删除成功']);
            } else {
                return json(['code' => 1, 'msg' => '删除失败']);
            }
            
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    /**
     * 模型场景配置 - 状态切换
     * POST /AiTravelPhoto/scene_config_status
     */
    public function scene_config_status()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }
        
        try {
            $id = input('id/d', 0);
            $status = input('status/d', 0);
            
            if ($id <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }
            
            $result = Db::name('ai_travel_photo_scene')
                ->where('id', $id)
                ->where('aid', $this->aid)
                ->where('bid', $this->bid)
                ->update(['status' => $status, 'update_time' => time()]);
            
            return json(['code' => 0, 'msg' => '更新成功']);
            
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
    
    /**
     * 模型场景配置 - 复制
     * POST /AiTravelPhoto/scene_config_copy
     */
    public function scene_config_copy()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }
        
        try {
            $id = input('id/d', 0);
            
            if ($id <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }
            
            // 获取原场景数据
            $scene = Db::name('ai_travel_photo_scene')
                ->where('id', $id)
                ->where('aid', $this->aid)
                ->where('bid', $this->bid)
                ->find();
            
            if (!$scene) {
                return json(['code' => 1, 'msg' => '场景不存在']);
            }
            
            // 复制数据
            unset($scene['id']);
            $scene['name'] = $scene['name'] . ' (复制)';
            $scene['create_time'] = time();
            $scene['update_time'] = time();
            $scene['status'] = 0; // 复制后默认禁用
            
            $newId = Db::name('ai_travel_photo_scene')->insertGetId($scene);
            
            return json(['code' => 0, 'msg' => '复制成功', 'data' => ['id' => $newId]]);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    // ============================================================
    // 合成模板管理
    // ============================================================

    /**
     * 合成模板 - 列表
     * GET /AiTravelPhoto/synthesis_template_list
     */
    public function synthesis_template_list()
    {
        // 如果是AJAX请求，返回JSON数据
        if (request()->isAjax()) {
            $where = [
                ['aid', '=', $this->aid],
                ['bid', '=', $this->bid]
            ];

            // 关键词搜索
            $keyword = input('param.keyword');
            if ($keyword) {
                $where[] = ['name', 'like', '%' . $keyword . '%'];
            }

            // 状态筛选
            $status = input('param.status', '');
            if ($status !== '' && $status !== 'all') {
                $where[] = ['status', '=', $status];
            }

            $page = input('page/d', 1);
            $limit = input('limit/d', 20);

            $list = Db::name('ai_travel_photo_synthesis_template')
                ->where($where)
                ->order('sort ASC, id DESC')
                ->page($page, $limit)
                ->select();

            $count = Db::name('ai_travel_photo_synthesis_template')
                ->where($where)
                ->count();

            return json([
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list
            ]);
        }

        return View::fetch();
    }

    /**
     * 合成模板 - 编辑页面
     * GET /AiTravelPhoto/synthesis_template_edit?id=xxx
     */
    public function synthesis_template_edit()
    {
        // 尝试多种方式获取id参数
        $id = 0;
        
        // 方法1: 从 $_REQUEST['s'] 中解析（pathinfo模式）
        if (isset($_REQUEST['s']) && strpos($_REQUEST['s'], '?id=') !== false) {
            $s = $_REQUEST['s'];
            $parts = parse_url($s);
            if (isset($parts['query'])) {
                parse_str($parts['query'], $query);
                $id = isset($query['id']) ? intval($query['id']) : 0;
            }
        }
        
        // 方法2: 常规方式
        if ($id == 0) {
            $id = input('id/d', 0);
        }
        if ($id == 0) {
            $id = input('get.id/d', 0);
        }
        if ($id == 0 && isset($_GET['id'])) {
            $id = intval($_GET['id']);
        }
        
        $info = [];
        if ($id > 0) {
            $info = Db::name('ai_travel_photo_synthesis_template')
                ->where('id', $id)
                ->find();
            
            // 将images字段从JSON字符串转换为数组
            if (!empty($info['images'])) {
                $info['images'] = json_decode($info['images'], true) ?: [];
            }
        }

        View::assign('info', $info);
        return View::fetch();
    }

    /**
     * 合成模板 - 保存
     * POST /AiTravelPhoto/synthesis_template_save
     */
    public function synthesis_template_save()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            $id = input('post.id/d', 0);
            $name = input('post.name/s', '');
            $modelId = input('post.model_id/d', 0);
            $modelName = input('post.model_name/s', '');
            $imagesInput = input('post.images/s', '');
            // 支持数组格式和逗号分隔字符串格式
            if (!empty($imagesInput)) {
                if (is_array($imagesInput)) {
                    $images = $imagesInput;
                } else {
                    $images = explode(',', $imagesInput);
                }
            } else {
                $images = [];
            }
            $prompt = input('post.prompt/s', '');
            $status = input('post.status/d', 1);
            $sort = input('post.sort/d', 0);

            if (empty($name)) {
                return json(['code' => 1, 'msg' => '请输入模板名称']);
            }

            if ($modelId <= 0) {
                return json(['code' => 1, 'msg' => '请选择AI模型']);
            }

            if (empty($images)) {
                return json(['code' => 1, 'msg' => '请上传模板图片']);
            }

            $data = [
                'aid' => $this->aid,
                'bid' => $this->bid,
                'name' => $name,
                'model_id' => $modelId,
                'model_name' => $modelName,
                'images' => json_encode($images, JSON_UNESCAPED_UNICODE),
                'prompt' => $prompt,
                'status' => $status,
                'sort' => $sort,
                'update_time' => time()
            ];

            if ($id > 0) {
                // 更新
                Db::name('ai_travel_photo_synthesis_template')
                    ->where('id', $id)
                    ->where('aid', $this->aid)
                    ->where('bid', $this->bid)
                    ->update($data);

                return json(['code' => 0, 'msg' => '保存成功']);
            } else {
                // 新增
                $data['create_time'] = time();
                Db::name('ai_travel_photo_synthesis_template')->insert($data);

                return json(['code' => 0, 'msg' => '添加成功']);
            }

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 合成模板 - 删除
     * POST /AiTravelPhoto/synthesis_template_delete
     */
    public function synthesis_template_delete()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            $id = input('post.id/d', 0);

            if ($id <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            Db::name('ai_travel_photo_synthesis_template')
                ->where('id', $id)
                ->where('aid', $this->aid)
                ->where('bid', $this->bid)
                ->delete();

            return json(['code' => 0, 'msg' => '删除成功']);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 合成模板 - 获取列表（用于下拉选择）
     * GET /AiTravelPhoto/get_synthesis_template_list
     */
    public function get_synthesis_template_list()
    {
        try {
            $where = [
                ['aid', '=', $this->aid],
                ['bid', '=', $this->bid],
                ['status', '=', 1]
            ];

            $list = Db::name('ai_travel_photo_synthesis_template')
                ->where($where)
                ->order('sort ASC, id DESC')
                ->select();

            return json([
                'code' => 0,
                'msg' => '获取成功',
                'data' => $list
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    // ============================================================
    // 合成设置与生成
    // ============================================================

    /**
     * 合成设置 - 弹窗页面
     * GET /AiTravelPhoto/synthesis_settings
     */
    public function synthesis_settings()
    {
        $portraitId = input('param.portrait_id/d', 0);

        // 处理 bid = 0 的情况（后台管理员），查询默认商户
        $targetBid = $this->bid;
        if ($this->bid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
            \think\facade\Log::info('Admin using default bid', ['original_bid' => $this->bid, 'target_bid' => $targetBid]);
        }

        // 获取已保存的全局合成设置（portrait_id=0 表示全局设置）
        // 按更新时间倒序，获取最新的记录
        $setting = Db::name('ai_travel_photo_synthesis_setting')
            ->where('portrait_id', 0)
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->order('update_time DESC')  // 修复：按更新时间倒序，获取最新记录
            ->find();
        
        \think\facade\Log::info('Synthesis setting loaded', [
            'current_bid' => $this->bid,
            'target_bid' => $targetBid,
            'aid' => $this->aid,
            'portrait_id' => 0,
            'setting_found' => !empty($setting),
            'setting_id' => $setting['id'] ?? 'none',
            'template_ids' => $setting['template_ids'] ?? 'none',
            'sql' => Db::name('ai_travel_photo_synthesis_setting')->getLastSql()
        ]);

        // 获取可用的照片场景模板列表（从generation_scene_template表查询，generation_type=1为照片生成）
        // 仅加载模型能力包含「多张参考图生成」(multi_input)的场景模板
        try {
            $templates = Db::name('generation_scene_template')
                ->alias('t')
                ->leftJoin('model_info m', 't.model_id = m.id')
                ->leftJoin('model_provider p', 'm.provider_id = p.id')
                ->where('t.aid', $this->aid)
                ->where(function ($query) use ($targetBid) {
                    $query->where('t.bid', 0)
                          ->whereOr('t.bid', $targetBid);
                })
                ->where('t.generation_type', 1) // 照片生成
                ->where('t.status', 1)
                ->whereRaw("JSON_CONTAINS(m.capability_tags, '\"multi_input\"')")
                ->field('t.id, t.template_name as scene_name, t.category, t.cover_image, t.model_id, t.output_quantity, t.use_count, t.sort, m.model_name, p.provider_name')
                ->order('t.sort ASC, t.id DESC')
                ->select();

            // 处理分类标签
            foreach ($templates as &$tpl) {
                $tpl['scene_type_label'] = !empty($tpl['category']) ? $tpl['category'] : '未分类';
            }
            unset($tpl);
        } catch (\Exception $e) {
            // 如果查询失败，返回空数组
            $templates = [];
            \think\facade\Log::error('获取照片场景模板失败: ' . $e->getMessage());
        }

        View::assign('portrait_id', 0);
        View::assign('setting', $setting);
        View::assign('templates', $templates);
        
        \think\facade\Log::info('Synthesis settings view data', [
            'portrait_id' => 0,
            'setting_assigned' => !empty($setting),
            'template_ids' => $setting['template_ids'] ?? 'none',
            'templates_count' => count($templates),
            'template_ids_in_view' => !empty($templates) ? array_column($templates->toArray(), 'id') : [],
            'first_few_templates' => !empty($templates) ? array_slice($templates->toArray(), 0, 3) : []
        ]);
        
        return View::fetch();
    }

    /**
     * 合成设置 - 保存设置
     * POST /AiTravelPhoto/synthesis_settings_save
     */
    public function synthesis_settings_save()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            $portraitId = input('post.portrait_id/d', 0);
            $templateIds = input('post.template_ids/a', []);
            $generateCount = input('post.generate_count/d', 4);
            $generateMode = input('post.generate_mode/d', 1); // 1顺序 2随机

            if (empty($templateIds)) {
                return json(['code' => 1, 'msg' => '请关联至少一个照片场景模板']);
            }

            if ($generateCount < 1 || $generateCount > 10) {
                return json(['code' => 1, 'msg' => '合成数量应在1-10之间']);
            }

            // 允许合成数量 > 模板数量，系统将循环使用模板
            // 例如：2个模板 + 合成数量3 → 模板1、模板2、模板1

            // 处理 bid = 0 的情况（后台管理员），查询默认商户
            $targetBid = $this->bid;
            if ($this->bid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
                if (!$targetBid) {
                    return json(['code' => 1, 'msg' => '未找到默认商户']);
                }
            }

            // 确保 templateIds 是数组并过滤空值
            $templateIds = is_array($templateIds) ? array_filter($templateIds) : [];
            if (empty($templateIds)) {
                return json(['code' => 1, 'msg' => '请关联至少一个照片场景模板']);
            }
            
            $data = [
                'portrait_id' => $portraitId, // 0表示全局设置
                'aid' => $this->aid,
                'bid' => $targetBid,
                'template_ids' => implode(',', $templateIds),
                'generate_count' => $generateCount,
                'generate_mode' => $generateMode,
                'status' => 1,
                'update_time' => time()
            ];
            
            \think\facade\Log::info('Synthesis settings saving', [
                'template_ids_array' => $templateIds,
                'template_ids_imploded' => implode(',', $templateIds)
            ]);

            // 检查是否已存在设置
            $exists = Db::name('ai_travel_photo_synthesis_setting')
                ->where('portrait_id', $portraitId)
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)  // 修复：使用targetBid而不是$this->bid
                ->find();
            
            \think\facade\Log::info('Checking existing setting', [
                'portrait_id' => $portraitId,
                'aid' => $this->aid,
                'target_bid' => $targetBid,
                'exists' => !empty($exists),
                'existing_id' => $exists['id'] ?? 'none'
            ]);

            if ($exists) {
                Db::name('ai_travel_photo_synthesis_setting')
                    ->where('id', $exists['id'])
                    ->update($data);
            } else {
                $data['create_time'] = time();
                Db::name('ai_travel_photo_synthesis_setting')->insert($data);
            }

            return json(['code' => 0, 'msg' => '保存成功']);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 合成生成 - 执行合成
     * POST /AiTravelPhoto/synthesis_generate
     */
    public function synthesis_generate()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            $portraitId = input('post.portrait_id/d', 0);

            if ($portraitId <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            // 获取人像信息
            $portrait = Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->where('aid', $this->aid)
                ->find();

            if (!$portrait) {
                return json(['code' => 1, 'msg' => '人像不存在']);
            }

            // 处理 bid = 0 的情况（后台管理员），使用人像的 bid
            $targetBid = $this->bid;
            if ($this->bid == 0) {
                $targetBid = $portrait['bid'];
            }

            // 获取合成设置
            $setting = Db::name('ai_travel_photo_synthesis_setting')
                ->where('portrait_id', $portraitId)
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->find();

            if (!$setting) {
                return json(['code' => 1, 'msg' => '请先设置合成参数']);
            }

            $templateIds = explode(',', $setting['template_ids']);
            $generateCount = $setting['generate_count'];
            $generateMode = $setting['generate_mode']; // 1顺序 2随机

            // 获取模板信息（从照片场景模板表查询）
            if (empty($templateIds)) {
                return json(['code' => 1, 'msg' => '请先关联照片场景模板']);
            }
            $templates = Db::name('generation_scene_template')
                ->whereIn('id', $templateIds)
                ->where('generation_type', 1) // 照片生成
                ->where('status', 1)
                ->field('id, aid, bid, template_name, model_id, cover_image, default_params, output_quantity, description, category')
                ->orderRaw('field(id, ' . $setting['template_ids'] . ')')
                ->select();

            if (count($templates) < count($templateIds)) {
                return json(['code' => 1, 'msg' => '部分场景模板已失效，请重新设置']);
            }

            // 根据模式选择模板（支持循环：当N>模板数时重复使用模板，1个模板生成1张）
            $templatesArray = $templates->toArray();
            $selectedTemplates = [];
            if ($generateMode == 1) {
                // 顺序模式：按顺序循环取模板
                for ($i = 0; $i < $generateCount; $i++) {
                    $selectedTemplates[] = $templatesArray[$i % count($templatesArray)];
                }
            } else {
                // 随机模式：每轮打乱后依次取，用完再重新打乱，直到凑够N个
                $pool = [];
                for ($i = 0; $i < $generateCount; $i++) {
                    if (empty($pool)) {
                        $pool = $templatesArray;
                        shuffle($pool);
                    }
                    $selectedTemplates[] = array_shift($pool);
                }
            }

            // 调用合成服务执行生成
            $synthesisService = new \app\service\AiTravelPhotoSynthesisService();
            $result = $synthesisService->generate($portrait, $selectedTemplates);

            if ($result['code'] === 0) {
                return json(['code' => 0, 'msg' => '生成成功', 'data' => $result['data']]);
            } else {
                return json(['code' => 1, 'msg' => $result['msg']]);
            }

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取待处理的未合成人像列表
     * POST /AiTravelPhoto/synthesis_get_pending
     */
    public function synthesis_get_pending()
    {
        try {
            // 处理 bid = 0 的情况
            $targetBid = $this->bid;
            if ($this->bid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
                if (!$targetBid) {
                    return json(['code' => 1, 'msg' => '未找到默认商户']);
                }
            }

            // 1. 先处理超时的"处理中"记录（超过10分钟的）
            $timeoutThreshold = time() - 600; // 10分钟前
            Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('synthesis_status', 2) // 处理中
                ->where('update_time', '<', $timeoutThreshold)
                ->update([
                    'synthesis_status' => 4, // 标记为失败
                    'synthesis_error' => '合成超时，请重试',
                    'update_time' => time()
                ]);

            // 2. 获取未处理(0)或已提交(1)的人像
            $portraits = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('status', 1)
                ->whereIn('synthesis_status', [0, 1])
                ->field('id, aid, bid, original_url, cutout_url, thumbnail_url, synthesis_status, synthesis_count, synthesis_error, create_time')
                ->select();

            return json([
                'code' => 0,
                'data' => $portraits
            ]);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 批量合成生成 - 处理所有人像
     * POST /AiTravelPhoto/synthesis_batch_generate
     */
    public function synthesis_batch_generate()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            // 处理 bid = 0 的情况（后台管理员），查询默认商户
            $targetBid = $this->bid;
            if ($this->bid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
                if (!$targetBid) {
                    return json(['code' => 1, 'msg' => '未找到默认商户']);
                }
            }

            // 获取全局合成设置
            $setting = Db::name('ai_travel_photo_synthesis_setting')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('portrait_id', 0)
                ->find();

            if (!$setting) {
                return json(['code' => 1, 'msg' => '请先保存合成设置']);
            }

            $templateIds = explode(',', $setting['template_ids']);
            $generateCount = $setting['generate_count'];
            $generateMode = $setting['generate_mode'];

            // 获取所有未处理的人像
            // 查询未处理(0)或已提交(1)的人像
            $portraits = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('status', 1)
                ->whereIn('synthesis_status', [0, 1])  // 未处理或已提交
                ->select();

            $total = count($portraits);
            if ($total === 0) {
                return json(['code' => 1, 'msg' => '没有需要处理的人像']);
            }

            // 获取模板信息（从generation_scene_template表查询）
            if (empty($templateIds)) {
                return json(['code' => 1, 'msg' => '请先关联照片场景模板']);
            }
            $templates = Db::name('generation_scene_template')
                ->whereIn('id', $templateIds)
                ->where('generation_type', 1) // 照片生成
                ->where('status', 1)
                ->field('id, aid, bid, template_name, model_id, cover_image, default_params, output_quantity, description, category')
                ->orderRaw('field(id, ' . $setting['template_ids'] . ')')
                ->select();

            if (count($templates) === 0) {
                return json(['code' => 1, 'msg' => '没有可用的照片场景模板']);
            }

            // 调用合成服务批量生成
            try {
                \think\facade\Log::write('synthesis: before new service', 'info');
                $synthesisService = new \app\service\AiTravelPhotoSynthesisService();
                \think\facade\Log::write('synthesis: service created', 'info');
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => '服务初始化失败: ' . $e->getMessage()]);
            } catch (\Error $e) {
                return json(['code' => 1, 'msg' => '服务初始化错误: ' . $e->getMessage()]);
            }
            $successCount = 0;
            $failCount = 0;

            // 先将所有人像状态更新为"处理中"
            $portraitIds = array_column($portraits->toArray(), 'id');
            if (!empty($portraitIds)) {
                Db::name('ai_travel_photo_portrait')
                    ->whereIn('id', $portraitIds)
                    ->update([
                        'synthesis_status' => 2,  // 处理中
                        'update_time' => time()
                    ]);
            }

            // 统一转为数组，避免Collection索引访问问题
            $templatesArray = $templates->toArray();

            foreach ($portraits as $portrait) {
                // 根据模式选择模板（支持循环：当N>模板数时重复使用模板）
                $selectedTemplates = [];
                if ($generateMode == 1) {
                    // 顺序模式：按顺序循环取模板，1个模板生成1张
                    for ($i = 0; $i < $generateCount; $i++) {
                        $selectedTemplates[] = $templatesArray[$i % count($templatesArray)];
                    }
                } else {
                    // 随机模式：每轮打乱后依次取，用完再重新打乱，直到凑够N个
                    $pool = [];
                    for ($i = 0; $i < $generateCount; $i++) {
                        if (empty($pool)) {
                            $pool = $templatesArray;
                            shuffle($pool);
                        }
                        $selectedTemplates[] = array_shift($pool);
                    }
                }

                try {
                    $result = $synthesisService->generate($portrait, $selectedTemplates);
                    $resultCount = $result['data']['count'] ?? 0;
                    if ($result['code'] === 0 && $resultCount > 0) {
                        // 更新人像合成状态为成功(3)
                        Db::name('ai_travel_photo_portrait')
                            ->where('id', $portrait['id'])
                            ->update([
                                'synthesis_status' => 3,  // 成功
                                'synthesis_count' => $resultCount,
                                'synthesis_time' => time(),
                                'update_time' => time()
                            ]);
                        $successCount++;
                    } else {
                        // 更新人像合成状态为失败(4)
                        $errorMsg = $result['msg'] ?? '生成失败';
                        if ($result['code'] === 0 && $resultCount === 0) {
                            $errorMsg = '生成完成但无结果输出';
                        }
                        Db::name('ai_travel_photo_portrait')
                            ->where('id', $portrait['id'])
                            ->update([
                                'synthesis_status' => 4,  // 失败
                                'synthesis_error' => $errorMsg,
                                'update_time' => time()
                            ]);
                        $failCount++;
                    }
                } catch (\Exception $e) {
                    // 更新人像合成状态为失败(4)
                    Db::name('ai_travel_photo_portrait')
                        ->where('id', $portrait['id'])
                        ->update([
                            'synthesis_status' => 4,  // 失败
                            'synthesis_error' => $e->getMessage(),
                            'update_time' => time()
                        ]);
                    $failCount++;
                }
            }

            return json([
                'code' => 0,
                'msg' => "批量合成完成，成功：{$successCount}，失败：{$failCount}",
                'data' => [
                    'total' => $total,
                    'success' => $successCount,
                    'fail' => $failCount
                ]
            ]);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '合成异常: ' . $e->getMessage()]);
        } catch (\Error $e) {
            return json(['code' => 1, 'msg' => '系统错误: ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '未知错误: ' . $e->getMessage()]);
        }
    }

    /**
     * 重试合成（单个失败的人像）
     * POST /AiTravelPhoto/synthesis_retry
     */
    public function synthesis_retry()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            $portraitId = input('post.portrait_id/d', 0);

            if ($portraitId <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            // 获取人像信息（确保包含所有字段）
            $portrait = Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->find();

            // 调试日志
            \think\facade\Log::write('synthesis_retry: portrait=' . json_encode($portrait), 'info');

            if (!$portrait) {
                return json(['code' => 1, 'msg' => '人像不存在']);
            }

            // 支持多种状态重试：未处理(0)、已提交(1)、失败(4)
            if (!in_array($portrait['synthesis_status'], [0, 1, 4])) {
                return json(['code' => 1, 'msg' => '该状态不能重试，当前状态: ' . $portrait['synthesis_status']]);
            }

            // 获取合成设置
            $setting = Db::name('ai_travel_photo_synthesis_setting')
                ->where('aid', $portrait['aid'])
                ->where('bid', $portrait['bid'])
                ->where('portrait_id', 0)
                ->find();

            if (!$setting) {
                return json(['code' => 1, 'msg' => '请先保存合成设置']);
            }

            $templateIds = explode(',', $setting['template_ids']);
            $generateCount = $setting['generate_count'];
            $generateMode = $setting['generate_mode'];

            if (empty($templateIds)) {
                return json(['code' => 1, 'msg' => '请先关联照片场景模板']);
            }

            // 获取模板信息（从generation_scene_template表查询）
            $templates = Db::name('generation_scene_template')
                ->whereIn('id', $templateIds)
                ->where('generation_type', 1) // 照片生成
                ->where('status', 1)
                ->field('id, aid, bid, template_name, model_id, cover_image, default_params, output_quantity, description, category')
                ->orderRaw('field(id, ' . $setting['template_ids'] . ')')
                ->select();

            if (count($templates) === 0) {
                return json(['code' => 1, 'msg' => '没有可用的照片场景模板']);
            }

            // 更新状态为处理中
            Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->update([
                    'synthesis_status' => 2,
                    'synthesis_error' => '',
                    'update_time' => time()
                ]);

            // 调用合成服务
            try {
                $synthesisService = new \app\service\AiTravelPhotoSynthesisService();
            } catch (\Exception $e) {
                return json(['code' => 1, 'msg' => '服务初始化失败: ' . $e->getMessage()]);
            }

            // 根据模式选择模板（支持循环：当N>模板数时重复使用模板，1个模板生成1张）
            $templatesArray = $templates->toArray();
            $selectedTemplates = [];
            if ($generateMode == 1) {
                // 顺序模式：按顺序循环取模板
                for ($i = 0; $i < $generateCount; $i++) {
                    $selectedTemplates[] = $templatesArray[$i % count($templatesArray)];
                }
            } else {
                // 随机模式：每轮打乱后依次取，用完再重新打乱，直到凑够N个
                $pool = [];
                for ($i = 0; $i < $generateCount; $i++) {
                    if (empty($pool)) {
                        $pool = $templatesArray;
                        shuffle($pool);
                    }
                    $selectedTemplates[] = array_shift($pool);
                }
            }

            // 调试日志：记录选中的模板
            \think\facade\Log::info('synthesis_retry 选中的模板: ' . json_encode($selectedTemplates, JSON_UNESCAPED_UNICODE));

            $result = $synthesisService->generate($portrait, $selectedTemplates);

            $resultCount = $result['data']['count'] ?? 0;
            if ($result['code'] === 0 && $resultCount > 0) {
                Db::name('ai_travel_photo_portrait')
                    ->where('id', $portraitId)
                    ->update([
                        'synthesis_status' => 3,
                        'synthesis_count' => $resultCount,
                        'synthesis_time' => time(),
                        'update_time' => time()
                    ]);
                return json(['code' => 0, 'msg' => '重试成功，生成' . $resultCount . '张图片']);
            } else {
                $errorMsg = $result['msg'] ?? '生成失败';
                if ($result['code'] === 0 && $resultCount === 0) {
                    $errorMsg = '生成完成但无结果输出';
                }
                Db::name('ai_travel_photo_portrait')
                    ->where('id', $portraitId)
                    ->update([
                        'synthesis_status' => 4,
                        'synthesis_error' => $errorMsg,
                        'update_time' => time()
                    ]);
                return json(['code' => 1, 'msg' => $errorMsg]);
            }

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '重试异常: ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '重试失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 创建支付订单
     */
    public function createPayOrder()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }

        try {
            $type = input('post.type', '');
            $amount = floatval(input('post.amount', 0));
            $packageName = input('post.package_name', '');
            $payMethod = input('post.pay_method', 'wxpay');

            if (empty($type) || $amount <= 0) {
                return json(['status' => 0, 'msg' => '参数错误']);
            }

            $targetBid = $this->getTargetBid();

            // 创建支付订单
            $orderData = [
                'aid' => $this->aid,
                'bid' => $targetBid,
                'order_no' => 'AITP' . date('YmdHis') . rand(1000, 9999),
                'type' => $type,
                'package_name' => $packageName,
                'amount' => $amount,
                'pay_method' => $payMethod,
                'status' => 0,
                'createtime' => time()
            ];

            $orderId = Db::name('ai_travel_photo_pay_order')->insertGetId($orderData);

            // 获取PC支付配置
            $pcPayConfig = Db::name('admin_setapp_pc')->where('aid', $this->aid)->find();

            if ($payMethod == 'wxpay') {
                // 微信支付 - Native支付
                if (!$pcPayConfig || $pcPayConfig['wxpay'] != 1) {
                    return json(['status' => 0, 'msg' => '微信支付未配置']);
                }

                // 调用微信Native支付
                $wxpayService = new \app\common\Wxpay();
                $result = $wxpayService->nativePay($this->aid, $orderData['order_no'], $amount, 'AI旅拍-' . $packageName);

                if ($result['status'] == 1) {
                    return json([
                        'status' => 1,
                        'msg' => '创建订单成功',
                        'data' => [
                            'order_id' => $orderId,
                            'qrcode_url' => $result['qrcode_url']
                        ]
                    ]);
                } else {
                    return json(['status' => 0, 'msg' => $result['msg']]);
                }
            } else {
                // 支付宝支付
                if (!$pcPayConfig || empty($pcPayConfig['alipay'])) {
                    return json(['status' => 0, 'msg' => '支付宝支付未配置']);
                }

                // 调用支付宝电脑网站支付
                $alipayService = new \app\common\Alipay();
                $payUrl = $alipayService->pagePay($this->aid, $orderData['order_no'], $amount, 'AI旅拍-' . $packageName, url('AiTravelPhoto/payReturn'));

                return json([
                    'status' => 1,
                    'msg' => '创建订单成功',
                    'data' => [
                        'order_id' => $orderId,
                        'pay_url' => $payUrl
                    ]
                ]);
            }
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '创建订单失败：' . $e->getMessage()]);
        }
    }

    /**
     * 检查支付状态
     */
    public function checkPayStatus()
    {
        $orderId = input('post.order_id', 0);
        if (!$orderId) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $order = Db::name('ai_travel_photo_pay_order')->where('id', $orderId)->find();
        if (!$order) {
            return json(['status' => 0, 'msg' => '订单不存在']);
        }

        return json([
            'status' => 1,
            'data' => [
                'paid' => $order['status'] == 1,
                'order_status' => $order['status']
            ]
        ]);
    }

    /**
     * 支付回调处理
     */
    public function payNotify()
    {
        // 获取回调数据
        $data = input('post.');
        $type = input('param.type', 'wxpay');

        try {
            if ($type == 'wxpay') {
                // 微信支付回调验证
                $wxpayService = new \app\common\Wxpay();
                $result = $wxpayService->verifyNotify($this->aid, $data);
            } else {
                // 支付宝回调验证
                $alipayService = new \app\common\Alipay();
                $result = $alipayService->verifyNotify($this->aid, $data);
            }

            if ($result['status'] == 1) {
                $orderNo = $result['order_no'];
                $order = Db::name('ai_travel_photo_pay_order')
                    ->where('order_no', $orderNo)
                    ->where('status', 0)
                    ->find();

                if ($order) {
                    // 更新订单状态
                    Db::name('ai_travel_photo_pay_order')->where('id', $order['id'])->update([
                        'status' => 1,
                        'paytime' => time(),
                        'pay_data' => json_encode($data)
                    ]);

                    // 更新商户账户
                    $this->updateBusinessAccount($order);
                }

                return $type == 'wxpay' ? '<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>' : 'success';
            }
        } catch (\Exception $e) {
            Log::error('AI旅拍支付回调处理失败：' . $e->getMessage());
        }

        return $type == 'wxpay' ? '<xml><return_code><![CDATA[FAIL]]></return_code></xml>' : 'fail';
    }

    /**
     * 支付成功返回页面
     */
    public function payReturn()
    {
        $this->success('支付成功', url('portrait_list'));
    }

    /**
     * 更新商户账户
     */
    private function updateBusinessAccount($order)
    {
        $business = Db::name('business')->where('id', $order['bid'])->find();
        if (!$business) return;

        switch ($order['type']) {
            case 'space':
                // 云空间扩容
                $addSpace = 0;
                if (strpos($order['package_name'], '10GB') !== false) $addSpace = 10240;
                elseif (strpos($order['package_name'], '50GB') !== false) $addSpace = 51200;
                elseif (strpos($order['package_name'], '200GB') !== false) $addSpace = 204800;
                elseif (strpos($order['package_name'], '1TB') !== false) $addSpace = 1048576;

                Db::name('business')->where('id', $order['bid'])->update([
                    'cloud_space' => ($business['cloud_space'] ?? 5120) + $addSpace
                ]);
                break;

            case 'balance':
                // 账户余额充值
                $giftAmount = 0;
                if ($order['amount'] == 100) $giftAmount = 10;
                elseif ($order['amount'] == 500) $giftAmount = 80;
                elseif ($order['amount'] == 1000) $giftAmount = 200;
                elseif ($order['amount'] == 5000) $giftAmount = 1500;

                Db::name('business')->where('id', $order['bid'])->update([
                    'account_balance' => ($business['account_balance'] ?? 0) + $order['amount'] + $giftAmount
                ]);
                break;

            case 'recharge':
                // 充值余额
                Db::name('business')->where('id', $order['bid'])->update([
                    'money' => ($business['money'] ?? 0) + $order['amount']
                ]);
                break;

            case 'renew':
                // 旅拍续费
                $addDays = 0;
                if (strpos($order['package_name'], '月卡') !== false) $addDays = 30;
                elseif (strpos($order['package_name'], '季卡') !== false) $addDays = 90;
                elseif (strpos($order['package_name'], '年卡') !== false) $addDays = 365;
                elseif (strpos($order['package_name'], '永久') !== false) $addDays = 99999;

                $currentEndtime = $business['endtime'] ?? 0;
                $newEndtime = ($currentEndtime > time()) ? $currentEndtime + ($addDays * 86400) : time() + ($addDays * 86400);

                if ($addDays == 99999) {
                    $newEndtime = 0; // 永久
                }

                Db::name('business')->where('id', $order['bid'])->update([
                    'endtime' => $newEndtime
                ]);
                break;
        }
    }
}
