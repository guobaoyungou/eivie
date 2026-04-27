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
                $post = input('post.');
                
                // 白名单字段，避免传入数据库不存在的字段
                $allowFields = ['name', 'desc', 'num', 'video_num', 'price', 'original_price', 'unit_price', 'sort', 'status', 'label', 'is_default', 'is_recommend', 'tag', 'tag_color', 'valid_days', 'stock', 'min_num', 'max_num', 'min_video_num', 'max_video_num', 'commissionset', 'commissiondata1', 'commissiondata2', 'commissiondata3'];
                $data = [];
                foreach ($allowFields as $field) {
                    if (isset($post[$field])) {
                        $data[$field] = $post[$field];
                    }
                }

                // === 分销设置处理 ===
                // 从 info[commissionset] 读取分销模式
                $commissionset = isset($post['info']['commissionset']) ? intval($post['info']['commissionset']) : -1;
                $data['commissionset'] = $commissionset;

                // 根据分销模式序列化对应的commissiondata，清空非当前模式的数据
                if ($commissionset == 1 && isset($post['commissiondata1'])) {
                    $data['commissiondata1'] = json_encode($post['commissiondata1'], JSON_UNESCAPED_UNICODE);
                } else {
                    $data['commissiondata1'] = null;
                }
                if ($commissionset == 2 && isset($post['commissiondata2'])) {
                    $data['commissiondata2'] = json_encode($post['commissiondata2'], JSON_UNESCAPED_UNICODE);
                } else {
                    $data['commissiondata2'] = null;
                }
                if ($commissionset == 3 && isset($post['commissiondata3'])) {
                    $data['commissiondata3'] = json_encode($post['commissiondata3'], JSON_UNESCAPED_UNICODE);
                } else {
                    $data['commissiondata3'] = null;
                }

                // === 独立收款分账比例校验 ===
                if ($this->bid > 0 && $commissionset >= 0 && $commissionset <= 2) {
                    $business = Db::name('business')->where('id', $this->bid)->find();
                    $set = Db::name('admin_set')->where('aid', $this->aid)->find();
                    // 检查是否开启独立收款
                    $isIndependentPay = (isset($set['business_cashdesk_wxpay_type']) && $set['business_cashdesk_wxpay_type'] == 3);
                    if ($isIndependentPay) {
                        $platformRate = floatval($business['feepercent'] ?? $set['choucheng_rate'] ?? 0);
                        $maxCommissionRate = 0;
                        // 计算最大提成比例
                        if ($commissionset == 1 && isset($post['commissiondata1'])) {
                            foreach ($post['commissiondata1'] as $lv) {
                                $maxCommissionRate += floatval($lv['commission1'] ?? 0) + floatval($lv['commission2'] ?? 0) + floatval($lv['commission3'] ?? 0);
                            }
                        } elseif ($commissionset == 0) {
                            // 按会员等级：取最大的等级比例之和
                            $levels = Db::name('member_level')->where('aid', $this->aid)->where('can_agent', '>', 0)->select();
                            foreach ($levels as $lv) {
                                if ($lv['commissiontype'] == 0) {
                                    $lvRate = floatval($lv['commission1']) + floatval($lv['commission2']) + floatval($lv['commission3']);
                                    if ($lvRate > $maxCommissionRate) $maxCommissionRate = $lvRate;
                                }
                            }
                        }
                        if (($maxCommissionRate + $platformRate) > 30) {
                            return json(['status' => 0, 'msg' => '提成比例(' . $maxCommissionRate . '%) + 平台抽成(' . $platformRate . '%) = ' . ($maxCommissionRate + $platformRate) . '%，超过30%微信支付分账限制']);
                        }
                    }
                }

                // 阶梯档位：自动回填 num 字段以兼容旧逻辑
                if (isset($data['min_num'])) {
                    $data['num'] = (int)$data['min_num'];
                }
                if (isset($data['min_video_num'])) {
                    $data['video_num'] = (int)$data['min_video_num'];
                }

                // 区间校验
                $minNum = (int)($data['min_num'] ?? 0);
                $maxNum = (int)($data['max_num'] ?? 0);
                $minVideoNum = (int)($data['min_video_num'] ?? 0);
                $maxVideoNum = (int)($data['max_video_num'] ?? 0);

                if ($minNum > 0) {
                    $pickService = new \app\service\AiTravelPhotoPickService();
                    $validation = $pickService->validateTierInterval($this->bid, $minNum, $maxNum, $minVideoNum, $maxVideoNum, $id);
                    if (!$validation['valid']) {
                        return json(['status' => 0, 'msg' => $validation['msg']]);
                    }
                }
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
            // 反序列化 commissiondata
            if (!empty($info['commissiondata1'])) {
                $info['commissiondata1'] = json_decode($info['commissiondata1'], true) ?: [];
            } else {
                $info['commissiondata1'] = [];
            }
            if (!empty($info['commissiondata2'])) {
                $info['commissiondata2'] = json_decode($info['commissiondata2'], true) ?: [];
            } else {
                $info['commissiondata2'] = [];
            }
            if (!empty($info['commissiondata3'])) {
                $info['commissiondata3'] = json_decode($info['commissiondata3'], true) ?: [];
            } else {
                $info['commissiondata3'] = [];
            }
        } else {
            $info['commissiondata1'] = [];
            $info['commissiondata2'] = [];
            $info['commissiondata3'] = [];
        }

        // 获取具有分销权限的会员等级列表
        $aglevellist = Db::name('member_level')
            ->where('aid', $this->aid)
            ->where('can_agent', '>', 0)
            ->field('id, name, can_agent, commissiontype, commission1, commission2, commission3')
            ->order('id ASC')
            ->select()
            ->toArray();

        View::assign('info', $info);
        View::assign('aglevellist', $aglevellist);
        View::assign('commissiondata1', $info['commissiondata1']);
        View::assign('commissiondata2', $info['commissiondata2']);
        View::assign('commissiondata3', $info['commissiondata3']);
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

            // 来源筛选（source_type: 1=设备拍摄, 3=用户自拍）
            $source_type = input('param.source_type');
            if ($source_type !== '' && $source_type !== null) {
                $where[] = ['source_type', '=', intval($source_type)];
            }

            // 特征库状态筛选（embedding_status: 1=已入库, 0=未入库）
            $embedding_status = input('param.embedding_status');

            // 用户关联筛选（user_openid 关键字搜索）
            $user_openid_search = input('param.user_openid', '');
            if ($user_openid_search !== '') {
                $where[] = ['user_openid', 'like', '%' . $user_openid_search . '%'];
            }

            $page = input('page/d', 1);
            $limit = input('limit/d', 20);

            // 显式指定字段，排除 face_embedding（512维向量JSON过大）
            // 通过 SQL 表达式计算特征入库状态，避免加载完整向量数据
            $fields = 'id,aid,uid,bid,mdid,device_id,type,source_type,user_openid,'
                . 'original_url,cutout_url,thumbnail_url,file_name,file_size,width,height,md5,'
                . 'shoot_time,`desc`,tags,face_embedding_id,status,create_time,update_time,'
                . 'synthesis_status,synthesis_count,synthesis_time,synthesis_error,'
                . '(CASE WHEN face_embedding IS NOT NULL AND face_embedding != \'\' AND face_embedding != \'[]\' AND LENGTH(face_embedding) > 10 THEN 1 ELSE 0 END) as has_mysql_embedding';

            $query = Db::name('ai_travel_photo_portrait')
                ->field($fields)
                ->where($where);

            // 特征库状态筛选需要特殊处理：同时考虑 face_embedding 和 face_embedding_id
            if ($embedding_status !== '' && $embedding_status !== null) {
                if (intval($embedding_status) === 1) {
                    // 已入库：face_embedding_id > 0 或 face_embedding 有有效数据
                    $query->where(function($q) {
                        $q->where('face_embedding_id', '>', 0)
                          ->whereOr(function($q2) {
                              $q2->where('face_embedding', '<>', '')
                                 ->where('face_embedding', '<>', '[]')
                                 ->whereRaw('LENGTH(face_embedding) > 10');
                          });
                    });
                } else {
                    // 未入库：face_embedding 为空且 face_embedding_id 无效
                    $query->where(function($q) {
                        $q->where(function($q2) {
                            $q2->whereNull('face_embedding_id')->whereOr('face_embedding_id', '=', 0);
                        })->where(function($q3) {
                            $q3->whereNull('face_embedding')
                               ->whereOr('face_embedding', '')
                               ->whereOr('face_embedding', '[]')
                               ->whereOrRaw('LENGTH(face_embedding) <= 10');
                        });
                    });
                }
            }

            $list = $query->order('id DESC')
                ->page($page, $limit)
                ->select()
                ->toArray();

            // 关联查询生成结果数量 & 计算特征库状态标记
            foreach ($list as &$item) {
                // 合成结果数
                $item['result_count'] = Db::name('ai_travel_photo_result')
                    ->where('portrait_id', $item['id'])
                    ->count();
                // 特征库入库状态：同时检查 face_embedding_id 和 face_embedding 字段
                // has_embedding: 0=未入库, 1=已入库(MySQL), 2=已入库(MySQL+Milvus)
                $hasMilvus = (!empty($item['face_embedding_id']) && $item['face_embedding_id'] > 0);
                $hasMysql = (!empty($item['has_mysql_embedding']) && $item['has_mysql_embedding'] > 0);
                if ($hasMilvus) {
                    $item['has_embedding'] = 2; // MySQL + Milvus 全入库
                } elseif ($hasMysql) {
                    $item['has_embedding'] = 1; // 仅 MySQL 入库（Milvus 不可用时的正常状态）
                } else {
                    $item['has_embedding'] = 0; // 未入库
                }
                // 人像来源文字映射
                $sourceMap = [1 => '设备拍摄', 2 => '其他', 3 => '用户自拍'];
                $item['source_type_text'] = $sourceMap[$item['source_type'] ?? 1] ?? '未知';
                // 上传方式文字映射
                $item['type_text'] = ($item['type'] == 1) ? '商家上传' : '用户上传';
                // 用户关联显示
                $item['user_openid_short'] = '';
                if (!empty($item['user_openid'])) {
                    $openid = $item['user_openid'];
                    $item['user_openid_short'] = mb_substr($openid, 0, 6) . '...' . mb_substr($openid, -4);
                }
            }
            unset($item);

            // 总数查询（需与筛选条件一致）
            $countQuery = Db::name('ai_travel_photo_portrait')->where($where);
            if ($embedding_status !== '' && $embedding_status !== null) {
                if (intval($embedding_status) === 1) {
                    $countQuery->where(function($q) {
                        $q->where('face_embedding_id', '>', 0)
                          ->whereOr(function($q2) {
                              $q2->where('face_embedding', '<>', '')
                                 ->where('face_embedding', '<>', '[]')
                                 ->whereRaw('LENGTH(face_embedding) > 10');
                          });
                    });
                } else {
                    $countQuery->where(function($q) {
                        $q->where(function($q2) {
                            $q2->whereNull('face_embedding_id')->whereOr('face_embedding_id', '=', 0);
                        })->where(function($q3) {
                            $q3->whereNull('face_embedding')
                               ->whereOr('face_embedding', '')
                               ->whereOr('face_embedding', '[]')
                               ->whereOrRaw('LENGTH(face_embedding) <= 10');
                        });
                    });
                }
            }
            $count = $countQuery->count();

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
        
        // 实时计算云空间已用量（从 portrait + result 表聚合）
        $spaceService = new \app\service\SpaceCheckService();
        $spaceInfo = $spaceService->getUsageInfo($targetBid);
        $cloudSpaceUsed = $spaceInfo['usedMB'];
        $cloudSpaceTotal = $spaceInfo['totalMB'];
        $cloudSpacePercent = $spaceInfo['percent'];
        
        $businessInfo = [
            'id' => $targetBid,
            'cloud_space' => $cloudSpaceTotal,
            'cloud_space_used' => $cloudSpaceUsed,
            'cloud_space_percent' => $cloudSpacePercent,
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

        // 判断是否为平台管理员（bid==0），控制“充值余额”卡片可见性
        $isAdmin = intval($this->user['bid'] ?? -1) === 0;
        View::assign('is_admin', $isAdmin);

        // Milvus 向量数据库服务状态检测
        $milvusStatus = $this->getMilvusStatusInfo();
        View::assign('milvus_status', $milvusStatus);

        return View::fetch();
    }

    /**
     * 获取 Milvus 服务状态详情（供页面渲染和 AJAX 轮询使用）
     * @return array
     */
    private function getMilvusStatusInfo(): array
    {
        $milvusService = new \app\service\MilvusService();
        $healthy = $milvusService->isHealthy();

        $config = \think\facade\Config::get('milvus');
        $host = $config['host'] ?? '127.0.0.1';
        $restPort = $config['rest_port'] ?? '9091';

        // 统计待同步到 Milvus 的记录数（MySQL有特征但Milvus无ID）
        $pendingSyncCount = 0;
        try {
            $pendingSyncCount = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('status', 1)
                ->where(function($q) {
                    $q->whereNull('face_embedding_id')
                      ->whereOr('face_embedding_id', '=', 0);
                })
                ->whereRaw('face_embedding IS NOT NULL AND face_embedding != \'\' AND face_embedding != \'[]\'  AND LENGTH(face_embedding) > 10')
                ->count();
        } catch (\Exception $e) {
            // 统计失败不影响主流程
        }

        return [
            'healthy' => $healthy,
            'host' => $host,
            'port' => $restPort,
            'pending_sync' => $pendingSyncCount,
            'check_time' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * AJAX 接口：获取 Milvus 服务实时状态
     */
    public function milvus_status_check()
    {
        $status = $this->getMilvusStatusInfo();
        return json([
            'status' => 1,
            'data' => $status,
        ]);
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

            // ===== 后端提取人脸特征（InsightFace 512维，统一替代前端 face-api.js 128维） =====
            try {
                $faceEmbeddingService = new \app\service\FaceEmbeddingService();
                $faceResult = $faceEmbeddingService->extractFromUrl($originalUrl);
                if ($faceResult && !empty($faceResult['embedding'])) {
                    $embedding = $faceResult['embedding'];
                    // 存储到 MySQL
                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                        'face_embedding' => json_encode($embedding),
                    ]);
                    // 存储到 Milvus
                    try {
                        $milvusService = new \app\service\MilvusService();
                        if ($milvusService->isHealthy()) {
                            $vectorIds = $milvusService->insert([$embedding], ['portrait_id' => $portraitId]);
                            if (!empty($vectorIds)) {
                                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)
                                    ->update(['face_embedding_id' => $vectorIds[0] ?? 0]);
                            }
                        }
                    } catch (\Exception $e) {
                        \think\facade\Log::warning('商家上传Milvus存储失败，MySQL已备份', [
                            'portrait_id' => $portraitId, 'error' => $e->getMessage()
                        ]);
                    }
                    \think\facade\Log::info('商家上传人脸特征提取成功', [
                        'portrait_id' => $portraitId, 'dim' => $faceResult['dim'],
                        'det_score' => $faceResult['det_score'],
                    ]);
                } else {
                    \think\facade\Log::info('商家上传图片未检测到人脸，跳过特征入库', ['portrait_id' => $portraitId]);
                }
            } catch (\Exception $e) {
                \think\facade\Log::warning('商家上传人脸特征提取异常，不影响上传流程', [
                    'portrait_id' => $portraitId, 'error' => $e->getMessage()
                ]);
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
     * 人像管理 - 批量特征入库（补提）
     *
     * 将 face_embedding 为空的人像记录批量投递至 face_embedding_backfill 队列，
     * 由队列消费者逐条调用 InsightFace 提取并写入 MySQL + Milvus。
     */
    public function portrait_backfill_embedding()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }

        try {
            $limit = input('post.limit/d', 100);
            if ($limit <= 0) $limit = 100;
            if ($limit > 500) $limit = 500;

            // 超级管理员 bid 为 0 时，使用 aid 对应的第一个商家
            $targetBid = $this->bid;
            if ($targetBid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
            }

            $service = new \app\service\AiTravelPhotoPortraitService();
            $result = $service->batchQueueBackfill($this->aid, $targetBid, $limit);

            if ($result['total'] == 0) {
                return json(['status' => 1, 'msg' => '所有人像均已具备人脸特征，无需补提']);
            }

            return json([
                'status' => 1,
                'msg' => '已投递 ' . $result['queued'] . ' 条补提任务（共 ' . $result['total'] . ' 条无特征记录）',
                'data' => [
                    'total' => $result['total'],
                    'queued' => $result['queued'],
                ],
            ]);
        } catch (\Exception $e) {
            \think\facade\Log::error('批量特征补提失败', [
                'error' => $e->getMessage(), 'aid' => $this->aid,
            ]);
            return json(['status' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
    }

    /**
     * 人像管理 - MySQL→Milvus 向量同步
     *
     * 将 MySQL 中已有 face_embedding 但 face_embedding_id 为空的记录
     * 批量同步插入到 Milvus，无需重新提取特征。
     */
    public function portrait_sync_milvus()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }

        try {
            $limit = input('post.limit/d', 200);
            if ($limit <= 0) $limit = 200;
            if ($limit > 500) $limit = 500;

            // 超级管理员 bid 为 0 时，使用 aid 对应的第一个商家
            $targetBid = $this->bid;
            if ($targetBid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
            }

            $service = new \app\service\AiTravelPhotoPortraitService();
            $result = $service->syncEmbeddingsToMilvus($this->aid, $targetBid, $limit);

            if ($result['total'] == 0) {
                return json(['status' => 1, 'msg' => '所有特征均已同步到 Milvus，无需操作']);
            }

            $msg = '同步完成：成功 ' . $result['synced'] . ' 条';
            if ($result['failed'] > 0) {
                $msg .= '，失败 ' . $result['failed'] . ' 条';
            }
            $msg .= '（共 ' . $result['total'] . ' 条待同步）';

            return json([
                'status' => 1,
                'msg' => $msg,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Milvus同步失败', [
                'error' => $e->getMessage(), 'aid' => $this->aid,
            ]);
            return json(['status' => 0, 'msg' => '同步失败：' . $e->getMessage()]);
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

            // 获取抓拍尺寸与比例参数
            $captureSize = input('post.capture_size', '1K');
            $aspectRatio = input('post.aspect_ratio', '3:4');

            // 校验参数合法性
            $validSizes = ['1K', '2K'];
            $validRatios = ['1:1', '2:3', '3:4', '4:3', '9:16', '16:9'];
            if (!in_array($captureSize, $validSizes)) $captureSize = '1K';
            if (!in_array($aspectRatio, $validRatios)) $aspectRatio = '3:4';

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

            // 根据capture_size和aspect_ratio计算目标尺寸并裁剪缩放
            $basePx = ($captureSize === '2K') ? 2048 : 1024;
            $ratioParts = explode(':', $aspectRatio);
            $rw = intval($ratioParts[0]);
            $rh = intval($ratioParts[1]);
            if ($rw >= $rh) {
                $targetWidth = $basePx;
                $targetHeight = intval($basePx * $rh / $rw);
            } else {
                $targetHeight = $basePx;
                $targetWidth = intval($basePx * $rw / $rh);
            }

            // 对原始抓拍图进行裁剪缩放到目标尺寸
            $resized = $this->resizeCapture($tempFile, $width, $height, $targetWidth, $targetHeight, $extension);
            if ($resized) {
                $imageContent = file_get_contents($tempFile);
                $width = $targetWidth;
                $height = $targetHeight;
                $fileSize = strlen($imageContent);
                $fileMd5 = md5($imageContent);
            }

            // 计算MD5（如未经裁剪缩放才在此计算）
            if (!isset($fileMd5)) {
                $fileMd5 = md5_file($tempFile);
                $fileSize = filesize($tempFile);
            }

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

            // 不再依赖前端 face-api.js 传入的 face_embedding
            // 改为下方存储后调用后端 InsightFace 提取 512 维特征

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

            // ===== 后端提取人脸特征（InsightFace 512维，统一替代前端 face-api.js 128维） =====
            try {
                $faceEmbeddingService = new \app\service\FaceEmbeddingService();
                $faceResult = $faceEmbeddingService->extractFromUrl($originalUrl);
                if ($faceResult && !empty($faceResult['embedding'])) {
                    $embedding = $faceResult['embedding'];
                    // 存储到 MySQL
                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                        'face_embedding' => json_encode($embedding),
                    ]);
                    // 存储到 Milvus
                    try {
                        $milvusService = new \app\service\MilvusService();
                        if ($milvusService->isHealthy()) {
                            $vectorIds = $milvusService->insert([$embedding], ['portrait_id' => $portraitId]);
                            if (!empty($vectorIds)) {
                                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)
                                    ->update(['face_embedding_id' => $vectorIds[0] ?? 0]);
                            }
                        }
                    } catch (\Exception $e) {
                        \think\facade\Log::warning('笑脸抓拍Milvus存储失败，MySQL已备份', [
                            'portrait_id' => $portraitId, 'error' => $e->getMessage()
                        ]);
                    }
                    \think\facade\Log::info('笑脸抓拍人脸特征提取成功', [
                        'portrait_id' => $portraitId, 'dim' => $faceResult['dim'],
                        'det_score' => $faceResult['det_score'],
                    ]);
                } else {
                    \think\facade\Log::info('笑脸抓拍图片未检测到人脸，跳过特征入库', ['portrait_id' => $portraitId]);
                }
            } catch (\Exception $e) {
                \think\facade\Log::warning('笑脸抓拍人脸特征提取异常，不影响主流程', [
                    'portrait_id' => $portraitId, 'error' => $e->getMessage()
                ]);
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

            // 获取模板列表（从商户合成模板表查询）
            $templateIds = explode(',', $setting['template_ids']);
            $generateCount = $setting['generate_count'] ?? 4;
            
            $templates = Db::name('ai_travel_photo_synthesis_template')
                ->whereIn('id', $templateIds)
                ->where('status', 1)
                ->field('id, name as template_name, model_id')
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
     * 笑脸抓拍独立页面（后台内嵌入口）
     * 
     * 路径: AiTravelPhoto/smile_capture_page
     * 方法: GET
     * 参数: mdid(可选，门店ID预选)
     */
    public function smile_capture_page()
    {
        // 复用Common控制器的认证体系，已登录
        $mdid = input('param.mdid/d', 0);

        // 超级管理员bid为0时，使用aid对应的第一个商家
        $targetBid = $this->bid;
        if ($targetBid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
        }

        // 获取门店列表
        $bid = $this->bid;
        $mendian_list = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where(function($query) use ($bid) {
                $query->whereOr([
                    ['bid', '=', $bid],
                    ['bid', '=', 0]
                ]);
            })
            ->select()
            ->toArray();

        $business = Db::name('business')->where('id', $targetBid)->find();
        $business_info = [
            'id' => $targetBid,
            'name' => $business['name'] ?? '',
        ];

        View::assign('is_logged_in', true);
        View::assign('page_aid', $this->aid);
        View::assign('page_bid', $targetBid);
        View::assign('preselect_mdid', $mdid);
        View::assign('admin_name', session('ADMIN_NAME') ?: '');
        View::assign('mendian_list', $mendian_list);
        View::assign('business_info', $business_info);

        return View::fetch('ai_travel_photo/smile_capture');
    }

    /**
     * 查询抓拍处理状态
     * 
     * 路径: AiTravelPhoto/smile_capture_status
     * 方法: GET
     * 参数: portrait_id(必填)
     */
    public function smile_capture_status()
    {
        $portraitId = input('param.portrait_id/d', 0);
        if (!$portraitId) {
            return json(['status' => 0, 'msg' => '缺少portrait_id参数']);
        }

        try {
            // 查询人像记录
            $portrait = Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->where('aid', $this->aid)
                ->field('id, synthesis_status, synthesis_count')
                ->find();

            if (!$portrait) {
                return json(['status' => 0, 'msg' => '人像记录不存在']);
            }

            $synthesisStatus = intval($portrait['synthesis_status'] ?? 0);
            $progress = 0;
            $resultImages = [];

            // 根据合成状态计算进度
            switch ($synthesisStatus) {
                case 0: $progress = 0; break;
                case 1: $progress = 20; break;
                case 2: $progress = 60; break;
                case 3:
                    $progress = 100;
                    // 查询成片图片
                    $results = Db::name('ai_travel_photo_result')
                        ->where('portrait_id', $portraitId)
                        ->where('status', 1)
                        ->field('url, thumbnail_url')
                        ->select();
                    foreach ($results as $r) {
                        $resultImages[] = $r['url'] ?: $r['thumbnail_url'];
                    }
                    break;
                case 4: $progress = 100; break;
            }

            return json([
                'status' => 1,
                'data' => [
                    'synthesis_status' => $synthesisStatus,
                    'progress' => $progress,
                    'result_images' => $resultImages
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('查询抓拍状态失败', [
                'portrait_id' => $portraitId,
                'error' => $e->getMessage()
            ]);
            return json(['status' => 0, 'msg' => '查询失败：' . $e->getMessage()]);
        }
    }

    /**
     * 裁剪缩放抓拍图片到目标尺寸
     * @param string $tempFile 临时文件路径
     * @param int $srcW 原图宽度
     * @param int $srcH 原图高度
     * @param int $dstW 目标宽度
     * @param int $dstH 目标高度
     * @param string $ext 文件扩展名
     * @return bool
     */
    private function resizeCapture($tempFile, $srcW, $srcH, $dstW, $dstH, $ext)
    {
        try {
            // 创建原始图像资源
            if ($ext == 'jpg' || $ext == 'jpeg') {
                $srcImg = imagecreatefromjpeg($tempFile);
            } elseif ($ext == 'png') {
                $srcImg = imagecreatefrompng($tempFile);
            } else {
                return false;
            }
            if (!$srcImg) return false;

            // 计算裁剪区域（居中裁剪）
            $targetAR = $dstW / $dstH;
            $srcAR = $srcW / $srcH;
            if ($srcAR > $targetAR) {
                $cropH = $srcH;
                $cropW = intval($srcH * $targetAR);
            } else {
                $cropW = $srcW;
                $cropH = intval($srcW / $targetAR);
            }
            $cropX = intval(($srcW - $cropW) / 2);
            $cropY = intval(($srcH - $cropH) / 2);

            // 创建目标图像
            $dstImg = imagecreatetruecolor($dstW, $dstH);
            if ($ext == 'png') {
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);
            }

            imagecopyresampled($dstImg, $srcImg, 0, 0, $cropX, $cropY, $dstW, $dstH, $cropW, $cropH);

            // 保存回临时文件
            if ($ext == 'png') {
                imagepng($dstImg, $tempFile, 8);
            } else {
                imagejpeg($dstImg, $tempFile, 92);
            }

            imagedestroy($srcImg);
            imagedestroy($dstImg);
            return true;
        } catch (\Exception $e) {
            Log::error('抓拍图片裁剪缩放失败', [
                'error' => $e->getMessage()
            ]);
            return false;
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
                // 使用 getTargetBid 统一处理 admin(bid=0) 的情况
                $targetBid = $this->getTargetBid();

                $where = [
                    ['o.aid', '=', $this->aid],
                    ['o.bid', '=', $targetBid]
                ];

                // 门店筛选
                $mdid = input('param.mdid/d', 0);
                if ($this->mdid > 0) {
                    $where[] = ['o.mdid', '=', $this->mdid];
                } elseif ($mdid > 0) {
                    $where[] = ['o.mdid', '=', $mdid];
                }

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

                // 关键词搜索
                $keyword = input('param.keyword', '');
                if ($keyword) {
                    $where[] = ['o.order_no|m.nickname|m.tel', 'like', '%' . $keyword . '%'];
                }

                $page = input('page/d', 1);
                $limit = input('limit/d', 20);

                $list = Db::name('ai_travel_photo_order')
                    ->alias('o')
                    ->leftJoin('ddwx_member m', 'o.uid = m.id')
                    ->leftJoin('ddwx_payorder p', 'o.payorderid = p.id')
                    ->leftJoin('mendian md', 'o.mdid = md.id')
                    ->where($where)
                    ->field('o.*, m.nickname, m.tel as mobile, p.paytype, p.paynum, p.paytime, md.name as mendian_name')
                    ->order('o.id DESC')
                    ->page($page, $limit)
                    ->select();

                // 转换为数组
                $list = $list ? $list->toArray() : [];

                // 查询订单商品数量 + 格式化支付时间 + 分销信息
                // 批量获取分销人昵称
                $parentIds = [];
                foreach ($list as $row) {
                    if (!empty($row['parent1'])) $parentIds[] = $row['parent1'];
                    if (!empty($row['parent2'])) $parentIds[] = $row['parent2'];
                    if (!empty($row['parent3'])) $parentIds[] = $row['parent3'];
                }
                $parentNames = [];
                if (!empty($parentIds)) {
                    $parentIds = array_unique($parentIds);
                    $parentMembers = Db::name('member')->whereIn('id', $parentIds)->column('nickname', 'id');
                    $parentNames = $parentMembers;
                }

                foreach ($list as &$item) {
                    $item['goods_count'] = Db::name('ai_travel_photo_order_goods')
                        ->where('order_id', $item['id'])
                        ->count();
                    $item['pay_time_text'] = !empty($item['paytime']) ? date('Y-m-d H:i:s', $item['paytime']) : '-';
                    // 支付方式显示
                    $item['paytype_text'] = $item['paytype'] ?: '-';
                    // 分销信息
                    $item['parent1_name'] = !empty($item['parent1']) ? ($parentNames[$item['parent1']] ?? '-') : '';
                    $item['parent2_name'] = !empty($item['parent2']) ? ($parentNames[$item['parent2']] ?? '-') : '';
                    $item['parent3_name'] = !empty($item['parent3']) ? ($parentNames[$item['parent3']] ?? '-') : '';
                    $item['commission_total'] = round(($item['parent1commission'] ?? 0) + ($item['parent2commission'] ?? 0) + ($item['parent3commission'] ?? 0), 2);
                }

                $count = Db::name('ai_travel_photo_order')
                    ->alias('o')
                    ->leftJoin('ddwx_member m', 'o.uid = m.id')
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

        // 获取门店列表供筛选框使用
        $targetBid = $this->getTargetBid();
        $mendian_list = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->select()->toArray();
        View::assign('mendian_list', $mendian_list);

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
            ->field('o.*, o.openid, m.nickname, m.tel as mobile, m.headimg')
            ->find();

        if (!$order) {
            $this->error('订单不存在');
        }

        // 查询订单商品
        $goods = Db::name('ai_travel_photo_order_goods')
            ->alias('g')
            ->leftJoin('ai_travel_photo_result r', 'g.result_id = r.id')
            ->where('g.order_id', $id)
            ->field('g.*, g.goods_image, g.type, g.num, r.url, r.thumbnail_url, r.type as result_type')
            ->select();

        // 分销信息
        $commissionInfo = [];
        if (!empty($order['iscommission']) || !empty($order['parent1']) || !empty($order['parent2']) || !empty($order['parent3'])) {
            $parentIds = array_filter([$order['parent1'] ?? 0, $order['parent2'] ?? 0, $order['parent3'] ?? 0]);
            $parentMembers = [];
            if (!empty($parentIds)) {
                $parentMembers = Db::name('member')->whereIn('id', $parentIds)->column('nickname,headimg,tel', 'id');
            }
            for ($i = 1; $i <= 3; $i++) {
                $pid = $order['parent' . $i] ?? 0;
                $pcommission = $order['parent' . $i . 'commission'] ?? 0;
                if ($pid > 0) {
                    $pm = $parentMembers[$pid] ?? [];
                    $commissionInfo[] = [
                        'level' => $i,
                        'level_text' => ['', '一级分销', '二级分销', '三级分销'][$i],
                        'mid' => $pid,
                        'nickname' => $pm['nickname'] ?? '-',
                        'headimg' => $pm['headimg'] ?? '',
                        'tel' => $pm['tel'] ?? '',
                        'commission' => $pcommission,
                    ];
                }
            }
        }

        View::assign('order', $order);
        View::assign('goods', $goods);
        View::assign('commissionInfo', $commissionInfo);
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
        // 使用 getTargetBid 统一处理 admin(bid=0) 的情况
        $targetBid = $this->getTargetBid();

        // 门店筛选
        $mdid = input('param.mdid/d', 0);
        if ($this->mdid > 0) {
            $mdid = $this->mdid;
        }

        // 今日数据
        $today = date('Y-m-d');
        $today_stat = Db::name('ai_travel_photo_statistics')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->where('stat_date', $today);
        if ($mdid > 0) {
            $today_stat = $today_stat->where('mdid', $mdid);
        }
        $today_stat = $today_stat->find();

        // 实时兜底：当预聚合表无数据时从原始表实时聚合
        if (!$today_stat || ($today_stat['upload_count'] == 0 && $today_stat['generation_count'] == 0 && $today_stat['video_count'] == 0)) {
            $todayStart = strtotime($today);
            $todayEnd = $todayStart + 86400;

            $portraitWhere = [['aid', '=', $this->aid], ['bid', '=', $targetBid], ['create_time', '>=', $todayStart], ['create_time', '<', $todayEnd]];
            $resultWhere = [['aid', '=', $this->aid], ['bid', '=', $targetBid], ['create_time', '>=', $todayStart], ['create_time', '<', $todayEnd]];
            $orderWhere = [['aid', '=', $this->aid], ['bid', '=', $targetBid], ['create_time', '>=', $todayStart], ['create_time', '<', $todayEnd], ['status', '=', 1]];
            if ($mdid > 0) {
                $portraitWhere[] = ['mdid', '=', $mdid];
            }

            $today_stat = [
                'upload_count' => Db::name('ai_travel_photo_portrait')->where($portraitWhere)->count(),
                'generation_count' => Db::name('ai_travel_photo_result')->where($resultWhere)->where('type', 1)->count(),
                'video_count' => Db::name('ai_travel_photo_result')->where($resultWhere)->where('type', 2)->count(),
                'order_count' => Db::name('ai_travel_photo_order')->where($orderWhere)->count(),
                'order_amount' => Db::name('ai_travel_photo_order')->where($orderWhere)->sum('total_price') ?: 0.00,
                'scan_count' => 0
            ];
        }

        // 本月数据
        $month_start = date('Y-m-01');
        $month_end = date('Y-m-d');
        $monthQuery = Db::name('ai_travel_photo_statistics')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->where('stat_date', 'between', [$month_start, $month_end]);
        if ($mdid > 0) {
            $monthQuery = $monthQuery->where('mdid', $mdid);
        }
        $month_stat = $monthQuery
            ->field('SUM(upload_count) as upload_count, SUM(generation_count) as generation_count, SUM(video_count) as video_count, SUM(order_count) as order_count, SUM(order_amount) as order_amount, SUM(scan_count) as scan_count')
            ->find();

        // 本月兜底
        if (!$month_stat || ($month_stat['upload_count'] == 0 && $month_stat['generation_count'] == 0)) {
            $monthStartTs = strtotime($month_start);
            $monthEndTs = strtotime($month_end . ' 23:59:59');
            $pWhere = [['aid', '=', $this->aid], ['bid', '=', $targetBid], ['create_time', '>=', $monthStartTs], ['create_time', '<=', $monthEndTs]];
            $rWhere = [['aid', '=', $this->aid], ['bid', '=', $targetBid], ['create_time', '>=', $monthStartTs], ['create_time', '<=', $monthEndTs]];
            $oWhere = [['aid', '=', $this->aid], ['bid', '=', $targetBid], ['create_time', '>=', $monthStartTs], ['create_time', '<=', $monthEndTs], ['status', '=', 1]];
            if ($mdid > 0) {
                $pWhere[] = ['mdid', '=', $mdid];
            }

            $month_stat = [
                'upload_count' => Db::name('ai_travel_photo_portrait')->where($pWhere)->count(),
                'generation_count' => Db::name('ai_travel_photo_result')->where($rWhere)->where('type', 1)->count(),
                'video_count' => Db::name('ai_travel_photo_result')->where($rWhere)->where('type', 2)->count(),
                'order_count' => Db::name('ai_travel_photo_order')->where($oWhere)->count(),
                'order_amount' => Db::name('ai_travel_photo_order')->where($oWhere)->sum('total_price') ?: 0.00,
                'scan_count' => 0
            ];
        }

        // 趋势图数据（最近7天）
        $trendQuery = Db::name('ai_travel_photo_statistics')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->where('stat_date', '>=', date('Y-m-d', strtotime('-7 days')));
        if ($mdid > 0) {
            $trendQuery = $trendQuery->where('mdid', $mdid);
        }
        $trend_data = $trendQuery->order('stat_date ASC')->select();

        // 趋势图补充订单金额数据
        $orderTrend = Db::name('ai_travel_photo_order')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->where('status', 1)
            ->where('create_time', '>=', strtotime('-7 days'))
            ->field("FROM_UNIXTIME(create_time, '%Y-%m-%d') as stat_date, SUM(total_price) as order_amount")
            ->group('stat_date')
            ->select()->toArray();
        $orderTrendMap = array_column($orderTrend, 'order_amount', 'stat_date');

        // 将订单金额合并到趋势数据
        $trendArray = $trend_data ? $trend_data->toArray() : [];
        foreach ($trendArray as &$item) {
            $item['order_amount'] = floatval($orderTrendMap[$item['stat_date']] ?? ($item['order_amount'] ?? 0));
        }

        // 热门场景TOP10
        $hot_scenes = Db::name('ai_travel_photo_scene')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->order('use_count DESC')
            ->limit(10)
            ->select();

        // 获取门店列表供筛选
        $mendian_list = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->select()->toArray();

        View::assign('today_stat', $today_stat);
        View::assign('month_stat', $month_stat);
        View::assign('trend_data', json_encode($trendArray));
        View::assign('hot_scenes', $hot_scenes);
        View::assign('mendian_list', $mendian_list);
        View::assign('current_mdid', $mdid);
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

                // 状态筛选（综合status字段和expire_time判断）
                $status = input('param.status', '');
                if ($status === '1') {
                    // 有效：status=1 且 未过期（expire_time=0表示永不过期，或expire_time>当前时间）
                    $where[] = ['q.status', '=', 1];
                    $now = time();
                    // expire_time=0 或 expire_time>当前时间
                    $where[] = ['', 'exp', Db::raw("(q.expire_time = 0 OR q.expire_time > {$now})")];
                } elseif ($status === '0') {
                    // 已过期：status=0 或 (expire_time>0 且 expire_time<=当前时间)
                    $now = time();
                    $where[] = ['', 'exp', Db::raw("(q.status = 0 OR (q.expire_time > 0 AND q.expire_time <= {$now}))")];
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
     * 生成公众号带参二维码（选片入口）
     * POST (AJAX)
     * 参数：qrcode - 二维码标识字符串
     * 返回：{status: 1, url: '公众号二维码图片URL'} 或错误信息
     *
     * 使用微信临时带参二维码（QR_STR_SCENE），有效期30天
     * scene格式：pick_{qrcode标识}
     * 用户扫码后关注公众号 → 自动注册会员(获得uid) → 回复选片链接
     */
    public function generate_mp_qrcode()
    {
        $qrcode = input('post.qrcode', '');
        if (empty($qrcode)) {
            return json(['status' => 0, 'msg' => '缺少二维码参数']);
        }

        Log::info('generate_mp_qrcode 开始', ['aid' => $this->aid, 'bid' => $this->bid, 'qrcode' => $qrcode]);

        // 验证qrcode属于当前商家
        $targetBid = $this->bid;
        if ($targetBid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
        }

        $record = Db::name('ai_travel_photo_qrcode')
            ->where('qrcode', $qrcode)
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->find();

        if (!$record) {
            Log::error('generate_mp_qrcode 二维码记录不存在', ['qrcode' => $qrcode, 'aid' => $this->aid, 'bid' => $targetBid]);
            return json(['status' => 0, 'msg' => '二维码记录不存在']);
        }

        // 检查公众号配置
        $mpappinfo = Db::name('admin_setapp_mp')->where('aid', $this->aid)->find();
        if (!$mpappinfo || empty($mpappinfo['appid'])) {
            Log::error('generate_mp_qrcode 公众号未绑定', ['aid' => $this->aid]);
            return json(['status' => 0, 'msg' => '请先绑定微信公众号']);
        }

        try {
            // 生成带参数临时二维码（有效期30天）
            $scene = 'pick_' . $qrcode;

            // 使用输出缓冲区捕获access_token中可能的echojson输出
            ob_start();
            $access_token = \app\common\Wechat::access_token($this->aid, 'mp');
            $ob_output = ob_get_clean();

            if (!$access_token) {
                // 如果access_token为空，检查是否有被echojson输出的错误信息
                if ($ob_output) {
                    $ob_data = json_decode($ob_output, true);
                    $errMsg = $ob_data['msg'] ?? 'access_token获取失败';
                    Log::error('generate_mp_qrcode access_token失败(echojson)', ['output' => $ob_output]);
                    return json(['status' => 0, 'msg' => $errMsg]);
                }
                Log::error('generate_mp_qrcode access_token为空', ['aid' => $this->aid]);
                return json(['status' => 0, 'msg' => 'access_token获取失败，请检查公众号配置']);
            }

            $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token;
            $postData = [
                'expire_seconds' => 2592000, // 30天
                'action_name' => 'QR_STR_SCENE',
                'action_info' => ['scene' => ['scene_str' => $scene]]
            ];

            $rs = request_post($url, jsonEncode($postData));
            $result = json_decode($rs, true);

            Log::info('generate_mp_qrcode 微信API响应', ['scene' => $scene, 'result' => $result]);

            if (!$result || empty($result['ticket'])) {
                $errMsg = isset($result['errmsg']) ? $result['errmsg'] : '未知错误';
                Log::error('generate_mp_qrcode 生成失败', ['result' => $result]);
                return json(['status' => 0, 'msg' => '生成二维码失败：' . $errMsg]);
            }

            // 获取二维码图片URL
            $qrcodeUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($result['ticket']);

            return json([
                'status' => 1,
                'url' => $qrcodeUrl,
                'expire_seconds' => $result['expire_seconds'] ?? 2592000
            ]);
        } catch (\Exception $e) {
            Log::error('generate_mp_qrcode 异常', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return json(['status' => 0, 'msg' => '生成失败：' . $e->getMessage()]);
        }
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

        // 判断是否为平台管理员（admin_user.bid==0 表示平台级用户，bid>0 为商户用户）
        // 注意：商户管理员(如gzq)的isadmin=1且groupid=0，不能用这两个字段判断
        $isAdmin = intval($this->user['bid'] ?? -1) === 0;

        // 获取Tab类型
        $tab = input('tab', 'basic');

        if (request()->isPost()) {
            if ($tab == 'package') {
                // POST提交安全校验：非管理员拒绝访问
                if (!$isAdmin) {
                    return json(['status' => 0, 'msg' => '无权操作，仅管理员可修改套餐定价']);
                }
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
                            'original_price' => floatval($item['original_price'] ?? 0),
                            'desc' => $item['desc'] ?? '',
                            'is_recommend' => intval($item['is_recommend'] ?? 0),
                            'sort' => intval($item['sort'] ?? 99),
                            'status' => intval($item['status'] ?? 1)
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
                            'desc' => $item['desc'] ?? '',
                            'is_recommend' => intval($item['is_recommend'] ?? 0),
                            'sort' => intval($item['sort'] ?? 99),
                            'status' => intval($item['status'] ?? 1)
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
                            'original_price' => floatval($item['original_price'] ?? 0),
                            'desc' => $item['desc'] ?? '',
                            'is_recommend' => intval($item['is_recommend'] ?? 0),
                            'sort' => intval($item['sort'] ?? 99),
                            'status' => intval($item['status'] ?? 1)
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
     * 获取合成单价
     * 优先级：模板 base_price > 商家 ai_photo_price > 平台默认 0
     *
     * @param int $bid 商家ID
     * @param array $templates 模板列表
     * @return float 单价
     */
    private function getSynthesisUnitCost(int $bid, array $templates): float
    {
        // 统一使用场景模板的商采价格(business_price)作为单价
        // 取第一个模板的 business_price 作为统一单价
        if (!empty($templates) && isset($templates[0]['business_price'])) {
            $price = floatval($templates[0]['business_price']);
            if ($price > 0) {
                return $price;
            }
            // business_price 为 0 时视为免费合成
            return 0;
        }

        // 模板中无 business_price 字段时，使用默认值 0.50
        return 0.50;
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
                'ai_max_scenes' => intval($data['ai_max_scenes'] ?? 10),
                'ai_pick_article_title' => mb_substr(trim($data['ai_pick_article_title'] ?? ''), 0, 100),
                'ai_pick_article_desc' => mb_substr(trim($data['ai_pick_article_desc'] ?? ''), 0, 255),
                'ai_pick_face_watermark_enabled' => isset($data['ai_pick_face_watermark_enabled']) ? 1 : 0,
                'ai_show_store_info' => isset($data['ai_show_store_info']) ? 1 : 0,
                'ai_show_commission' => isset($data['ai_show_commission']) ? 1 : 0,
                'ai_show_upgrade_discount' => isset($data['ai_show_upgrade_discount']) ? 1 : 0,
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
     * 获取场景模板分类列表（仅照片生成类型，带层级结构）
     * GET /AiTravelPhoto/get_scene_categories
     */
    public function get_scene_categories()
    {
        try {
            $categories = Db::name('generation_scene_category')
                ->where('generation_type', 1) // 仅照片生成
                ->where('status', 1)
                ->field('id, name, pid, sort')
                ->order('sort ASC, id ASC')
                ->select()
                ->toArray();

            // 构建层级结构（一级 + 二级）
            $tree = [];
            $childMap = [];
            foreach ($categories as $cat) {
                if ($cat['pid'] == 0) {
                    $cat['children'] = [];
                    $tree[$cat['id']] = $cat;
                } else {
                    $childMap[$cat['pid']][] = $cat;
                }
            }
            foreach ($childMap as $pid => $children) {
                if (isset($tree[$pid])) {
                    $tree[$pid]['children'] = $children;
                }
            }

            return json([
                'code' => 0,
                'msg' => '获取成功',
                'data' => array_values($tree)
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
     * 获取可选场景模板列表（供合成模板新增时选择）
     * GET /AiTravelPhoto/get_available_scene_templates
     */
    public function get_available_scene_templates()
    {
        try {
            $keyword = input('param.keyword/s', '');
            $categoryId = input('param.category_id/d', 0);
            $page = input('param.page/d', 1);
            $limit = input('param.limit/d', 50);

            // 处理 bid = 0 的情况（后台管理员），查询默认商户
            $targetBid = $this->bid;
            if ($targetBid == 0) {
                $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
            }

            // 构建基础查询条件闭包
            $buildQuery = function () use ($targetBid, $keyword, $categoryId) {
                $q = Db::name('generation_scene_template')
                    ->alias('t')
                    ->leftJoin('model_info m', 't.model_id = m.id')
                    ->leftJoin('model_provider p', 'm.provider_id = p.id')
                    ->where('t.aid', $this->aid)
                    ->where(function ($sub) use ($targetBid) {
                        $sub->where('t.bid', 0)->whereOr('t.bid', $targetBid);
                    })
                    ->where('t.generation_type', 1)
                    ->where('t.status', 1)
                    ->whereRaw("JSON_CONTAINS(m.capability_tags, '\"multi_input\"')");

                if ($keyword) {
                    $q->where('t.template_name', 'like', '%' . $keyword . '%');
                }

                // 按分类筛选（category_ids 是逗号分隔的ID）
                if ($categoryId > 0) {
                    // 获取该分类及其子分类的所有ID
                    $childIds = Db::name('generation_scene_category')
                        ->where('pid', $categoryId)
                        ->where('status', 1)
                        ->column('id');
                    $allCatIds = array_merge([$categoryId], $childIds);

                    // 用 FIND_IN_SET 构建OR条件
                    $catConditions = [];
                    foreach ($allCatIds as $cid) {
                        $catConditions[] = "FIND_IN_SET({$cid}, t.category_ids)";
                    }
                    $q->whereRaw('(' . implode(' OR ', $catConditions) . ')');
                }

                return $q;
            };

            $count = $buildQuery()->count();

            // 注意：select() 返回 Collection 对象，foreach引用修改不会生效
            // 必须先 toArray() 转为普通数组才能正确修改 default_params
            $list = $buildQuery()
                ->field('t.id, t.template_name, t.cover_image, t.category, t.category_ids, t.model_id, t.default_params, t.description, t.output_quantity, t.auto_tags, t.auto_tag_status, t.base_price, t.business_price, t.price_unit, t.lvprice, t.lvprice_data, m.model_name, p.provider_name')
                ->order('t.sort ASC, t.id DESC')
                ->page($page, $limit)
                ->select()
                ->toArray();

            // 处理 default_params 和 auto_tags JSON 字段（在普通数组上引用修改才能生效）
            foreach ($list as &$item) {
                if (!empty($item['default_params']) && is_string($item['default_params'])) {
                    $item['default_params'] = json_decode($item['default_params'], true) ?: [];
                }
                if (empty($item['default_params'])) {
                    $item['default_params'] = [];
                }
                // 解析自动标签JSON
                if (!empty($item['auto_tags']) && is_string($item['auto_tags'])) {
                    $item['auto_tags'] = json_decode($item['auto_tags'], true) ?: [];
                }
                if (empty($item['auto_tags'])) {
                    $item['auto_tags'] = [];
                }
                // 解析会员价格数据JSON
                if (!empty($item['lvprice_data']) && is_string($item['lvprice_data'])) {
                    $item['lvprice_data'] = json_decode($item['lvprice_data'], true) ?: [];
                }
                if (empty($item['lvprice_data']) || !is_array($item['lvprice_data'])) {
                    $item['lvprice_data'] = [];
                }
            }
            unset($item);

            return json([
                'code' => 0,
                'msg' => '获取成功',
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

    /**
     * 合成模板 - 列表
     * GET /AiTravelPhoto/synthesis_template_list
     */
    public function synthesis_template_list()
    {
        // 如果是AJAX请求，返回JSON数据
        if (request()->isAjax()) {
            $where = [
                ['st.aid', '=', $this->aid],
                ['st.bid', '=', $this->bid]
            ];

            // 关键词搜索
            $keyword = input('param.keyword');
            if ($keyword) {
                $where[] = ['st.name', 'like', '%' . $keyword . '%'];
            }

            // 状态筛选
            $status = input('param.status', '');
            if ($status !== '' && $status !== 'all') {
                $where[] = ['st.status', '=', $status];
            }

            $page = input('page/d', 1);
            $limit = input('limit/d', 20);

            $list = Db::name('ai_travel_photo_synthesis_template')
                ->alias('st')
                ->leftJoin('generation_scene_template gst', 'st.scene_template_id = gst.id')
                ->where($where)
                ->field('st.*, gst.template_name as scene_template_name')
                ->order('st.sort ASC, st.id DESC')
                ->page($page, $limit)
                ->select()
                ->toArray();

            // 获取所有涉及的门店ID，批量查询名称
            $allStoreIds = [];
            foreach ($list as $item) {
                if ($item['store_scope'] == 1 && !empty($item['store_ids'])) {
                    $ids = array_filter(explode(',', $item['store_ids']));
                    $allStoreIds = array_merge($allStoreIds, $ids);
                }
            }
            $storeNameMap = [];
            if (!empty($allStoreIds)) {
                $allStoreIds = array_unique($allStoreIds);
                $storeNames = Db::name('mendian')
                    ->field('id, name')
                    ->whereIn('id', $allStoreIds)
                    ->select()
                    ->toArray();
                foreach ($storeNames as $s) {
                    $storeNameMap[$s['id']] = $s['name'];
                }
            }

            // 处理来源模板已删除的情况 + 门店名称解析
            foreach ($list as &$item) {
                if ($item['scene_template_id'] > 0 && empty($item['scene_template_name'])) {
                    $item['scene_template_name'] = '已删除';
                }
                // 解析门店名称列表
                $item['store_names'] = [];
                if ($item['store_scope'] == 1 && !empty($item['store_ids'])) {
                    $ids = array_filter(explode(',', $item['store_ids']));
                    foreach ($ids as $sid) {
                        $item['store_names'][] = $storeNameMap[$sid] ?? ('门店#' . $sid);
                    }
                }
            }
            unset($item);

            $count = Db::name('ai_travel_photo_synthesis_template')
                ->alias('st')
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
        $sceneTemplateName = '';
        $autoTagString = '';
        $sceneCategoryNames = '';
        $sceneBasePrice = '0.00';
        $sceneBusinessPrice = '0.50';
        $sceneLvprice = 0;
        $sceneLvpriceList = [];
        // 默认值（新建模式 $id==0 时不会进入下方if块，需要预设）
        View::assign('auto_tags', []);
        View::assign('auto_tag_status', 0);

        if ($id > 0) {
            $info = Db::name('ai_travel_photo_synthesis_template')
                ->where('id', $id)
                ->find();
            
            // 将images字段从JSON字符串转换为数组
            if (!empty($info['images'])) {
                $info['images'] = json_decode($info['images'], true) ?: [];
            }

            // 查询来源场景模板名称和自动标签
            if (!empty($info['scene_template_id'])) {
                $sceneTemplateData = Db::name('generation_scene_template')
                    ->field('template_name, auto_tags, auto_tag_status, category_ids, base_price, business_price, price_unit, lvprice, lvprice_data')
                    ->where('id', $info['scene_template_id'])
                    ->find();
                if ($sceneTemplateData) {
                    $sceneTemplateName = $sceneTemplateData['template_name'];
                    // 解析自动标签
                    $autoTags = $sceneTemplateData['auto_tags'];
                    if (!empty($autoTags) && is_string($autoTags)) {
                        $autoTags = json_decode($autoTags, true);
                    }
                    View::assign('auto_tags', $autoTags ?: []);
                    View::assign('auto_tag_status', $sceneTemplateData['auto_tag_status'] ?? 0);
                    // 准备自动标签展示字符串
                    if (!empty($autoTags) && is_array($autoTags)) {
                        if (!empty($autoTags['tag_string'])) {
                            $autoTagString = $autoTags['tag_string'];
                        } elseif (!empty($autoTags['primary_tags']) && is_array($autoTags['primary_tags'])) {
                            $autoTagString = implode(',', $autoTags['primary_tags']);
                        }
                    }
                    // 解析所属分类名称
                    if (!empty($sceneTemplateData['category_ids'])) {
                        $catIds = array_filter(explode(',', $sceneTemplateData['category_ids']));
                        if (!empty($catIds)) {
                            $catNames = Db::name('generation_scene_category')
                                ->whereIn('id', $catIds)
                                ->column('name');
                            $sceneCategoryNames = implode('、', $catNames);
                        }
                    }
                    // 解析价格设置（商采价格）
                    $sceneBasePrice = $sceneTemplateData['base_price'] ?? '0.00';
                    $sceneBusinessPrice = $sceneTemplateData['business_price'] ?? '0.50';
                    $sceneLvprice = intval($sceneTemplateData['lvprice'] ?? 0);
                    if ($sceneLvprice == 1 && !empty($sceneTemplateData['lvprice_data'])) {
                        $lvpriceRaw = $sceneTemplateData['lvprice_data'];
                        if (is_string($lvpriceRaw)) {
                            $lvpriceRaw = json_decode($lvpriceRaw, true) ?: [];
                        }
                        if (!empty($lvpriceRaw) && is_array($lvpriceRaw)) {
                            // 获取会员等级列表（show_business=1的商户等级）
                            $default_cid = Db::name('member_level_category')->where('aid', $this->aid)->where('isdefault', 1)->value('id');
                            $default_cid = $default_cid ? $default_cid : 0;
                            $levellist = Db::name('member_level')
                                ->where('aid', $this->aid)
                                ->where('cid', $default_cid)
                                ->where('show_business', 1)
                                ->order('sort asc, id asc')
                                ->select()->toArray();
                            foreach ($levellist as $lv) {
                                if (isset($lvpriceRaw[$lv['id']])) {
                                    $sceneLvpriceList[] = [
                                        'level_id' => $lv['id'],
                                        'level_name' => $lv['name'],
                                        'price' => $lvpriceRaw[$lv['id']]
                                    ];
                                }
                            }
                        }
                    }
                } else {
                    $sceneTemplateName = '已删除';
                    View::assign('auto_tags', []);
                    View::assign('auto_tag_status', 0);
                }
            } else {
                View::assign('auto_tags', []);
                View::assign('auto_tag_status', 0);
            }
        }

        View::assign('info', $info);
        View::assign('scene_template_name', $sceneTemplateName);
        View::assign('auto_tag_string', $autoTagString);
        View::assign('scene_category_names', $sceneCategoryNames);
        View::assign('scene_base_price', $sceneBasePrice);
        View::assign('scene_business_price', $sceneBusinessPrice);

        // 获取当前商户下的门店列表（供门店范围选择）
        $mendianQuery = Db::name('mendian')
            ->field('id, name')
            ->where('status', 1);
        if ($this->bid > 0) {
            // 商户：只查该商户下的门店
            $mendianQuery->where('bid', $this->bid);
        } else {
            // 平台管理员编辑已有模板时，按模板所属商户过滤；新建时展示该平台下所有门店
            if ($id > 0 && !empty($info['bid'])) {
                $mendianQuery->where('bid', $info['bid']);
            } else {
                $mendianQuery->where('aid', $this->aid);
            }
        }
        $mendianList = $mendianQuery->order('id asc')->select()->toArray();
        View::assign('mendian_list', $mendianList);
        View::assign('mendian_list_json', json_encode($mendianList, JSON_UNESCAPED_UNICODE));

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
            $sceneTemplateId = input('post.scene_template_id/d', 0);
            $defaultParams = input('post.default_params/s', '');
            $description = input('post.description/s', '');
            $coverImage = input('post.cover_image/s', '');
            $storeScope = input('post.store_scope/d', 0);
            $storeIds = input('post.store_ids/s', '');

            // 调试日志：记录门店范围接收数据
            \think\facade\Log::info('synthesis_template_save store data', [
                'store_scope' => $storeScope,
                'store_ids_raw' => $storeIds,
                'post_store_scope' => $_POST['store_scope'] ?? 'NOT_SET',
                'post_store_ids' => $_POST['store_ids'] ?? 'NOT_SET',
            ]);

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
                'default_params' => $defaultParams,
                'description' => $description,
                'cover_image' => $coverImage,
                'store_scope' => $storeScope,
                'store_ids' => ($storeScope == 1) ? $storeIds : '',
                'status' => $status,
                'sort' => $sort,
                'update_time' => time()
            ];

            if ($id > 0) {
                // 更新：scene_template_id 不可变更，从 data 中排除
                Db::name('ai_travel_photo_synthesis_template')
                    ->where('id', $id)
                    ->where('aid', $this->aid)
                    ->where('bid', $this->bid)
                    ->update($data);

                return json(['code' => 0, 'msg' => '保存成功']);
            } else {
                // 新增：验证 scene_template_id
                if ($sceneTemplateId <= 0) {
                    return json(['code' => 1, 'msg' => '请选择来源场景模板']);
                }

                // 验证场景模板存在
                $sceneTemplate = Db::name('generation_scene_template')
                    ->where('id', $sceneTemplateId)
                    ->where('status', 1)
                    ->find();
                if (!$sceneTemplate) {
                    return json(['code' => 1, 'msg' => '所选场景模板不存在或已禁用']);
                }

                $data['scene_template_id'] = $sceneTemplateId;
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
     * 合成模板 - 获取门店列表（供门店范围选择）
     * GET /AiTravelPhoto/get_synthesis_stores
     */
    public function get_synthesis_stores()
    {
        try {
            $query = Db::name('mendian')
                ->field('id, name')
                ->where('status', 1);
            if ($this->bid > 0) {
                // 商户：只查该商户下的门店
                $query->where('bid', $this->bid);
            } else {
                // 平台管理员：查平台下所有门店
                $query->where('aid', $this->aid);
            }
            $list = $query->order('id asc')->select()->toArray();

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
            'target_bid' => $targetBid,
            'setting_found' => !empty($setting),
            'template_ids' => $setting['template_ids'] ?? 'none'
        ]);

        // 获取商户合成模板列表（从ai_travel_photo_synthesis_template表查询）
        try {
            $templates = Db::name('ai_travel_photo_synthesis_template')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('status', 1)
                ->field('id, name, model_id, model_name, images, cover_image, prompt, status, sort')
                ->order('sort ASC, id DESC')
                ->select();

            // 处理images字段和封面图
            foreach ($templates as &$tpl) {
                $tpl['images_arr'] = [];
                if (!empty($tpl['images'])) {
                    $decoded = json_decode($tpl['images'], true);
                    if (is_array($decoded)) {
                        $tpl['images_arr'] = $decoded;
                    }
                }
                // 若无cover_image，取images数组第一张
                if (empty($tpl['cover_image']) && !empty($tpl['images_arr'])) {
                    $tpl['cover_image'] = $tpl['images_arr'][0];
                }
            }
            unset($tpl);
        } catch (\Exception $e) {
            // 如果查询失败，返回空数组
            $templates = [];
            \think\facade\Log::error('获取合成模板失败: ' . $e->getMessage());
        }

        View::assign('portrait_id', 0);
        View::assign('setting', $setting);
        View::assign('templates', $templates);
        
        \think\facade\Log::info('Synthesis settings view data', [
            'setting_found' => !empty($setting),
            'template_ids' => $setting['template_ids'] ?? 'none'
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
                return json(['code' => 1, 'msg' => '请关联至少一个合成模板']);
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
                return json(['code' => 1, 'msg' => '请关联至少一个合成模板']);
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
                'template_ids' => implode(',', $templateIds)
            ]);

            // 检查是否已存在设置
            $exists = Db::name('ai_travel_photo_synthesis_setting')
                ->where('portrait_id', $portraitId)
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)  // 修复：使用targetBid而不是$this->bid
                ->find();

            \think\facade\Log::info('Checking existing setting', [
                'target_bid' => $targetBid,
                'exists' => !empty($exists)
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

            // 获取模板信息（从商户合成模板表查询）
            if (empty($templateIds)) {
                return json(['code' => 1, 'msg' => '请先关联合成模板']);
            }
            $templates = Db::name('ai_travel_photo_synthesis_template')
                ->whereIn('id', $templateIds)
                ->where('status', 1)
                ->field('id, aid, bid, name as template_name, model_id, model_name, cover_image, images, prompt, default_params, description, scene_template_id, sort')
                ->orderRaw('field(id, ' . $setting['template_ids'] . ')')
                ->select();

            // 补充 business_price：通过 scene_template_id 回查，或使用默认值
            foreach ($templates as &$_tpl) {
                $_tpl['output_quantity'] = 1; // 合成模板固定输出1张
                $_tpl['business_price'] = 0.50; // 默认值
                if (!empty($_tpl['scene_template_id'])) {
                    $scenePrice = Db::name('generation_scene_template')->where('id', $_tpl['scene_template_id'])->value('business_price');
                    if ($scenePrice !== null && $scenePrice !== false) {
                        $_tpl['business_price'] = floatval($scenePrice);
                    }
                }
            }
            unset($_tpl);

            if (count($templates) < count($templateIds)) {
                return json(['code' => 1, 'msg' => '部分合成模板已失效，请重新设置']);
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

            // ======== 扣费逻辑（与批量合成/重试合成对齐） ========
            $unitCost = $this->getSynthesisUnitCost($targetBid, $selectedTemplates);
            $templateCount = count($selectedTemplates);
            $totalCost = round($unitCost * $templateCount, 2);
            $deductId = 0;

            if ($totalCost > 0) {
                $balanceService = new \app\service\BalanceDeductService();
                $balanceCheck = $balanceService->checkBalance($targetBid, $totalCost);
                if (!$balanceCheck['allowed']) {
                    Db::name('ai_travel_photo_portrait')->where('id', $portrait['id'])->update([
                        'synthesis_status' => 5,
                        'synthesis_error' => '余额不足，需要¥' . $totalCost . '，当前¥' . $balanceCheck['balance'],
                        'update_time' => time()
                    ]);
                    return json(['code' => 2, 'msg' => '账户余额不足', 'type' => 'balance_insufficient', 'data' => [
                        'required' => $totalCost,
                        'balance' => $balanceCheck['balance'],
                        'shortfall' => $balanceCheck['shortfall']
                    ]]);
                }

                $deductResult = $balanceService->preDeduct($targetBid, $totalCost, $portrait['id'], '合成预扣费 人像ID:' . $portrait['id'] . ' 商采价¥' . $unitCost . '×' . $templateCount . '张=¥' . $totalCost);
                if (!$deductResult['status']) {
                    Db::name('ai_travel_photo_portrait')->where('id', $portrait['id'])->update([
                        'synthesis_status' => 5,
                        'synthesis_error' => '预扣费失败（并发冲突）',
                        'update_time' => time()
                    ]);
                    return json(['code' => 2, 'msg' => '余额扣费失败，请重试', 'type' => 'balance_insufficient']);
                }
                $deductId = $deductResult['deductId'];
            }

            // 空间预检
            $spaceService = new \app\service\SpaceCheckService();
            $estimatedSpace = $spaceService->estimateRequired($templateCount, 'image');
            $spaceCheck = $spaceService->checkSpace($targetBid, $estimatedSpace);
            if (!$spaceCheck['allowed']) {
                // 空间不足，退还已扣余额
                if ($deductId > 0 && $totalCost > 0) {
                    $balanceService->refundSingle($targetBid, $totalCost, $deductId, '空间不足退款');
                }
                Db::name('ai_travel_photo_portrait')->where('id', $portrait['id'])->update([
                    'synthesis_status' => 6,
                    'synthesis_error' => '云空间不足，需要' . $estimatedSpace . 'MB，剩余' . $spaceCheck['remainingMB'] . 'MB',
                    'update_time' => time()
                ]);
                return json(['code' => 3, 'msg' => '云空间不足', 'type' => 'space_insufficient', 'data' => [
                    'required' => $estimatedSpace,
                    'remaining' => $spaceCheck['remainingMB'],
                    'shortfall' => $spaceCheck['shortfallMB']
                ]]);
            }
            // ======== 扣费逻辑结束 ========

            // 调用合成服务执行生成
            $synthesisService = new \app\service\AiTravelPhotoSynthesisService();
            $operatorName = $this->user['un'] ?? '';
            $result = $synthesisService->generate($portrait, $selectedTemplates, $operatorName, $deductId, $unitCost);

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

            // 2. 获取未处理(0)、已提交(1)、余额不足暂停(5)、空间不足暂停(6)的人像
            $portraits = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('status', 1)
                ->whereIn('synthesis_status', [0, 1, 5, 6])
                ->field('id, aid, bid, original_url, cutout_url, thumbnail_url, synthesis_status, synthesis_count, synthesis_error, create_time')
                ->select();

            // 统计暂停数量
            $pausedBalance = 0;
            $pausedSpace = 0;
            foreach ($portraits as $p) {
                if ($p['synthesis_status'] == 5) $pausedBalance++;
                if ($p['synthesis_status'] == 6) $pausedSpace++;
            }

            return json([
                'code' => 0,
                'data' => $portraits,
                'paused_balance' => $pausedBalance,
                'paused_space' => $pausedSpace
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

            // 获取模板信息（从商户合成模板表查询）
            if (empty($templateIds)) {
                return json(['code' => 1, 'msg' => '请先关联合成模板']);
            }
            $templates = Db::name('ai_travel_photo_synthesis_template')
                ->whereIn('id', $templateIds)
                ->where('status', 1)
                ->field('id, aid, bid, name as template_name, model_id, model_name, cover_image, images, prompt, default_params, description, scene_template_id, sort')
                ->orderRaw('field(id, ' . $setting['template_ids'] . ')')
                ->select();

            // 补充 business_price：通过 scene_template_id 回查，或使用默认值
            foreach ($templates as &$_tpl) {
                $_tpl['output_quantity'] = 1;
                $_tpl['business_price'] = 0.50;
                if (!empty($_tpl['scene_template_id'])) {
                    $scenePrice = Db::name('generation_scene_template')->where('id', $_tpl['scene_template_id'])->value('business_price');
                    if ($scenePrice !== null && $scenePrice !== false) {
                        $_tpl['business_price'] = floatval($scenePrice);
                    }
                }
            }
            unset($_tpl);

            if (count($templates) === 0) {
                return json(['code' => 1, 'msg' => '没有可用的合成模板']);
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

            // 获取当前操作者账号名
            $operatorName = $this->user['un'] ?? '';

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

            // M6: 初始化扣费服务和空间服务
            $balanceService = new \app\service\BalanceDeductService();
            $spaceService = new \app\service\SpaceCheckService();
            $pausedCount = 0;
            $insufficientType = '';

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

                // ======== M6: 每个人像合成前预检余额和空间 ========
                $bid = $portrait['bid'] ?? $targetBid;
                $templateCount = count($selectedTemplates);
                $unitCost = $this->getSynthesisUnitCost($bid, $selectedTemplates);
                $totalCost = round($unitCost * $templateCount, 2);
                $deductId = 0;

                if ($totalCost > 0) {
                    $balanceCheck = $balanceService->checkBalance($bid, $totalCost);
                    if (!$balanceCheck['allowed']) {
                        // 余额不足，设 status=5 并中断后续遍历
                        Db::name('ai_travel_photo_portrait')->where('id', $portrait['id'])->update([
                            'synthesis_status' => 5,
                            'synthesis_error' => '余额不足',
                            'update_time' => time()
                        ]);
                        $pausedCount++;
                        $insufficientType = 'balance_insufficient';
                        // 将剩余人像也标记为余额不足暂停
                        break;
                    }

                    $deductResult = $balanceService->preDeduct($bid, $totalCost, $portrait['id'], '批量合成预扣费 人像ID:' . $portrait['id'] . ' 商采价¥' . $unitCost . '×' . $templateCount . '张=¥' . $totalCost);
                    if (!$deductResult['status']) {
                        Db::name('ai_travel_photo_portrait')->where('id', $portrait['id'])->update([
                            'synthesis_status' => 5,
                            'synthesis_error' => '预扣费失败',
                            'update_time' => time()
                        ]);
                        $pausedCount++;
                        $insufficientType = 'balance_insufficient';
                        break;
                    }
                    $deductId = $deductResult['deductId'];
                }

                // 空间预检
                $estimatedSpace = $spaceService->estimateRequired($templateCount, 'image');
                $spaceCheck = $spaceService->checkSpace($bid, $estimatedSpace);
                if (!$spaceCheck['allowed']) {
                    // 空间不足，退还已扣余额
                    if ($deductId > 0 && $totalCost > 0) {
                        $balanceService->refundSingle($bid, $totalCost, $deductId, '空间不足退款');
                    }
                    Db::name('ai_travel_photo_portrait')->where('id', $portrait['id'])->update([
                        'synthesis_status' => 6,
                        'synthesis_error' => '云空间不足',
                        'update_time' => time()
                    ]);
                    $pausedCount++;
                    $insufficientType = 'space_insufficient';
                    break;
                }
                // ======== M6: 预检结束 ========

                try {
                    $result = $synthesisService->generate($portrait, $selectedTemplates, $operatorName, $deductId, $unitCost);
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

            // 若因余额/空间不足中断，将剩余未处理人像标记为相应暂停状态
            if ($insufficientType) {
                $pauseStatus = ($insufficientType === 'balance_insufficient') ? 5 : 6;
                // 将仍为“处理中”状态的剩余人像改为暂停
                Db::name('ai_travel_photo_portrait')
                    ->where('aid', $this->aid)
                    ->where('bid', $targetBid)
                    ->where('synthesis_status', 2)
                    ->update([
                        'synthesis_status' => $pauseStatus,
                        'synthesis_error' => $insufficientType === 'balance_insufficient' ? '余额不足暂停' : '空间不足暂停',
                        'update_time' => time()
                    ]);
            }

            $responseData = [
                'total' => $total,
                'success' => $successCount,
                'fail' => $failCount
            ];
            if ($insufficientType) {
                $responseData['insufficient_type'] = $insufficientType;
                $responseData['paused_count'] = $pausedCount;
            }

            $msg = "批量合成完成，成功：{$successCount}，失败：{$failCount}";
            if ($insufficientType) {
                $msg .= ($insufficientType === 'balance_insufficient') ? '，余额不足已暂停' : '，空间不足已暂停';
            }

            return json([
                'code' => 0,
                'msg' => $msg,
                'data' => $responseData
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

            // 支持多种状态重试：未处理(0)、已提交(1)、失败(4)、余额不足暂停(5)、空间不足暂停(6)
            if (!in_array($portrait['synthesis_status'], [0, 1, 4, 5, 6])) {
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
                return json(['code' => 1, 'msg' => '请先关联合成模板']);
            }

            // 获取模板信息（从商户合成模板表查询）
            $templates = Db::name('ai_travel_photo_synthesis_template')
                ->whereIn('id', $templateIds)
                ->where('status', 1)
                ->field('id, aid, bid, name as template_name, model_id, model_name, cover_image, images, prompt, default_params, description, scene_template_id, sort')
                ->orderRaw('field(id, ' . $setting['template_ids'] . ')')
                ->select();

            // 补充 business_price：通过 scene_template_id 回查，或使用默认值
            foreach ($templates as &$_tpl) {
                $_tpl['output_quantity'] = 1;
                $_tpl['business_price'] = 0.50;
                if (!empty($_tpl['scene_template_id'])) {
                    $scenePrice = Db::name('generation_scene_template')->where('id', $_tpl['scene_template_id'])->value('business_price');
                    if ($scenePrice !== null && $scenePrice !== false) {
                        $_tpl['business_price'] = floatval($scenePrice);
                    }
                }
            }
            unset($_tpl);

            if (count($templates) === 0) {
                return json(['code' => 1, 'msg' => '没有可用的合成模板']);
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

            // ======== M6: 余额预检与空间预检 ========
            $bid = $portrait['bid'] ?? 0;
            $templateCount = count($selectedTemplates);

            // 获取合成单价
            $unitCost = $this->getSynthesisUnitCost($bid, $selectedTemplates);
            $totalCost = round($unitCost * $templateCount, 2);

            $deductId = 0;
            if ($totalCost > 0) {
                // 余额预检
                $balanceService = new \app\service\BalanceDeductService();
                $balanceCheck = $balanceService->checkBalance($bid, $totalCost);
                if (!$balanceCheck['allowed']) {
                    // 余额不足 → 设 status=5
                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                        'synthesis_status' => 5,
                        'synthesis_error' => '余额不足，需要¥' . $totalCost . '，当前¥' . $balanceCheck['balance'],
                        'update_time' => time()
                    ]);
                    return json(['code' => 2, 'msg' => '账户余额不足', 'type' => 'balance_insufficient', 'data' => [
                        'required' => $totalCost,
                        'balance' => $balanceCheck['balance'],
                        'shortfall' => $balanceCheck['shortfall']
                    ]]);
                }

                // 预扣费
                $deductResult = $balanceService->preDeduct($bid, $totalCost, $portraitId, '合成预扣费 人像ID:' . $portraitId . ' 商采价¥' . $unitCost . '×' . $templateCount . '张=¥' . $totalCost);
                if (!$deductResult['status']) {
                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                        'synthesis_status' => 5,
                        'synthesis_error' => '预扣费失败（并发冲突）',
                        'update_time' => time()
                    ]);
                    return json(['code' => 2, 'msg' => '余额扣费失败，请重试', 'type' => 'balance_insufficient']);
                }
                $deductId = $deductResult['deductId'];
            }

            // 空间预检
            $spaceService = new \app\service\SpaceCheckService();
            $estimatedSpace = $spaceService->estimateRequired($templateCount, 'image');
            $spaceCheck = $spaceService->checkSpace($bid, $estimatedSpace);
            if (!$spaceCheck['allowed']) {
                // 空间不足，退还已扣余额
                if ($deductId > 0 && $totalCost > 0) {
                    $balanceService->refundSingle($bid, $totalCost, $deductId, '空间不足退款');
                }
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'synthesis_status' => 6,
                    'synthesis_error' => '云空间不足，需要' . $estimatedSpace . 'MB，剩余' . $spaceCheck['remainingMB'] . 'MB',
                    'update_time' => time()
                ]);
                return json(['code' => 3, 'msg' => '云空间不足', 'type' => 'space_insufficient', 'data' => [
                    'required' => $estimatedSpace,
                    'remaining' => $spaceCheck['remainingMB'],
                    'shortfall' => $spaceCheck['shortfallMB']
                ]]);
            }
            // ======== M6: 预检结束 ========

            $operatorName = $this->user['un'] ?? '';
            $result = $synthesisService->generate($portrait, $selectedTemplates, $operatorName, $deductId, $unitCost);

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
            // 异常时重置portrait状态为失败，避免永久卡在“处理中”
            if (!empty($portraitId)) {
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'synthesis_status' => 4,
                    'synthesis_error' => '重试异常: ' . $e->getMessage(),
                    'update_time' => time()
                ]);
            }
            return json(['code' => 1, 'msg' => '重试异常: ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            // 异常时重置portrait状态为失败，避免永久卡在“处理中”
            if (!empty($portraitId)) {
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'synthesis_status' => 4,
                    'synthesis_error' => '重试失败: ' . $e->getMessage(),
                    'update_time' => time()
                ]);
            }
            return json(['code' => 1, 'msg' => '重试失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取合成失败详情（人像级 + 各任务级错误信息）
     * POST /AiTravelPhoto/synthesis_error_detail
     */
    public function synthesis_error_detail()
    {
        try {
            $portraitId = input('post.portrait_id/d', 0);
            if ($portraitId <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            // 获取人像基本信息
            $portrait = Db::name('ai_travel_photo_portrait')
                ->field('id,synthesis_status,synthesis_error,synthesis_count,synthesis_time,create_time,file_name')
                ->where('id', $portraitId)
                ->where('aid', $this->aid)
                ->find();

            if (!$portrait) {
                return json(['code' => 1, 'msg' => '人像不存在']);
            }

            // 状态文字映射
            $statusTextMap = [
                0 => '未处理', 1 => '已提交', 2 => '处理中',
                3 => '成功', 4 => '失败', 5 => '余额不足暂停', 6 => '空间不足暂停'
            ];

            // 获取关联的合成任务记录
            $generations = Db::name('ai_travel_photo_generation')
                ->alias('g')
                ->leftJoin('ai_travel_photo_synthesis_template t', 'g.template_id = t.id')
                ->field('g.id, g.template_id, g.status, g.error_msg, g.error_code, g.model_name, g.cost_time, g.create_time, g.finish_time, t.name as template_name')
                ->where('g.portrait_id', $portraitId)
                ->order('g.id DESC')
                ->limit(50)
                ->select()
                ->toArray();

            // 格式化时间
            foreach ($generations as &$gen) {
                $gen['create_time_text'] = $gen['create_time'] ? date('m-d H:i:s', $gen['create_time']) : '-';
                $gen['finish_time_text'] = $gen['finish_time'] ? date('m-d H:i:s', $gen['finish_time']) : '-';
                $gen['template_name'] = $gen['template_name'] ?: ('模板ID:' . ($gen['template_id'] ?? '-'));
            }
            unset($gen);

            return json([
                'code' => 0,
                'data' => [
                    'portrait_id' => $portrait['id'],
                    'file_name' => $portrait['file_name'] ?? '',
                    'status_text' => $statusTextMap[$portrait['synthesis_status']] ?? '未知',
                    'synthesis_status' => $portrait['synthesis_status'],
                    'synthesis_error' => $portrait['synthesis_error'] ?: '',
                    'synthesis_count' => $portrait['synthesis_count'],
                    'synthesis_time' => $portrait['synthesis_time'] ? date('Y-m-d H:i:s', $portrait['synthesis_time']) : '',
                    'create_time' => $portrait['create_time'] ? date('Y-m-d H:i:s', $portrait['create_time']) : '',
                    'generations' => $generations
                ]
            ]);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '获取失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 恢复暂停任务（充值/扩容后调用）
     * POST /AiTravelPhoto/resume_paused_tasks
     */
    public function resume_paused_tasks()
    {
        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            $pauseType = input('post.pause_type', ''); // balance 或 space
            if (!in_array($pauseType, ['balance', 'space'])) {
                return json(['code' => 1, 'msg' => '参数错误，pause_type 必须为 balance 或 space']);
            }

            $targetBid = $this->getTargetBid();
            $pauseStatus = ($pauseType === 'balance') ? 5 : 6;

            // 查询暂停的人像数量
            $pausedCount = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('synthesis_status', $pauseStatus)
                ->count();

            if ($pausedCount === 0) {
                return json(['code' => 1, 'msg' => '没有需要恢复的暂停任务']);
            }

            if ($pauseType === 'balance') {
                // 重新校验余额是否充足
                // 获取合成设置来估算每张费用
                $setting = Db::name('ai_travel_photo_synthesis_setting')
                    ->where('aid', $this->aid)
                    ->where('bid', $targetBid)
                    ->where('portrait_id', 0)
                    ->find();
                $generateCount = $setting ? intval($setting['generate_count']) : 1;

                // 从商户合成模板获取 business_price 作为单价
                $unitCost = 0.50; // 默认值
                if ($setting && !empty($setting['template_ids'])) {
                    $firstTemplateId = explode(',', $setting['template_ids'])[0];
                    // 先查合成模板的 scene_template_id，再回查场景模板的 business_price
                    $synthTemplate = Db::name('ai_travel_photo_synthesis_template')
                        ->where('id', $firstTemplateId)
                        ->where('status', 1)
                        ->field('scene_template_id')
                        ->find();
                    if ($synthTemplate && !empty($synthTemplate['scene_template_id'])) {
                        $businessPrice = Db::name('generation_scene_template')
                            ->where('id', $synthTemplate['scene_template_id'])
                            ->value('business_price');
                        if ($businessPrice !== null && $businessPrice !== false) {
                            $unitCost = floatval($businessPrice);
                        }
                    }
                }
                $totalEstimate = round($unitCost * $generateCount * $pausedCount, 2);

                $balanceService = new \app\service\BalanceDeductService();
                $balanceCheck = $balanceService->checkBalance($targetBid, $unitCost * $generateCount); // 至少够处理一个人像
                if (!$balanceCheck['allowed']) {
                    return json(['code' => 0, 'msg' => '余额仍不足，还差¥' . $balanceCheck['shortfall'], 'data' => [
                        'still_insufficient' => true,
                        'shortfall' => $balanceCheck['shortfall'],
                        'balance' => $balanceCheck['balance']
                    ]]);
                }
            } else {
                // 重新校验空间是否充足
                $spaceService = new \app\service\SpaceCheckService();
                $estimatedSpace = $spaceService->estimateRequired(1, 'image'); // 至少够处理一张
                $spaceCheck = $spaceService->checkSpace($targetBid, $estimatedSpace);
                if (!$spaceCheck['allowed']) {
                    return json(['code' => 0, 'msg' => '空间仍不足，还差' . $spaceCheck['shortfallMB'] . 'MB', 'data' => [
                        'still_insufficient' => true,
                        'shortfall' => $spaceCheck['shortfallMB'],
                        'remaining' => $spaceCheck['remainingMB']
                    ]]);
                }
            }

            // 校验通过，批量重置暂停人像为“未处理”
            $affected = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('synthesis_status', $pauseStatus)
                ->update([
                    'synthesis_status' => 0,
                    'synthesis_error' => '',
                    'update_time' => time()
                ]);

            return json(['code' => 0, 'msg' => '已恢复' . $affected . '个暂停任务', 'data' => [
                'resumed_count' => $affected
            ]]);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '恢复失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 创建支付订单（集成标准 payorder 支付流程）
     * 微信走 build_native_v3（PC扫码），支付宝走 build_page_pay（PC跳转）
     * 支付回调由 notify.php → Notify → Payorder::payorder() 统一处理
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

            // 'recharge' 映射到 'balance' (充值余额统一走 balance 套餐)
            if ($type === 'recharge') {
                $type = 'balance';
            }

            $targetBid = $this->getTargetBid();
            $orderNo = 'AITP' . date('YmdHis') . rand(1000, 9999);
            $title = 'AI旅拍-' . $packageName;

            // 1. 创建 ai_travel_photo_pay_order 业务订单
            $bizOrderData = [
                'aid' => $this->aid,
                'bid' => $targetBid,
                'order_no' => $orderNo,
                'type' => $type,
                'package_name' => $packageName,
                'amount' => $amount,
                'pay_method' => $payMethod,
                'status' => 0,
                'createtime' => time()
            ];
            $bizOrderId = Db::name('ai_travel_photo_pay_order')->insertGetId($bizOrderData);

            // 2. 创建标准 payorder 记录（走标准支付回调流程: notify.php → Payorder::payorder）
            $payorderData = [
                'aid'        => $this->aid,
                'bid'        => $targetBid,
                'mid'        => 0, // 商户后台购买，非会员
                'ordernum'   => $orderNo,
                'orderid'    => $bizOrderId, // 关联业务订单ID
                'type'       => 'ai_travel_photo_pay',
                'title'      => $title,
                'money'      => $amount,
                'score'      => 0,
                'status'     => 0,
                'createtime' => time(),
                'platform'   => 'pc',
            ];
            $payorderId = Db::name('payorder')->insertGetId($payorderData);

            // 关闭同商户同类型旧的未支付订单
            Db::name('payorder')
                ->where('id', '<>', $payorderId)
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('type', 'ai_travel_photo_pay')
                ->where('status', 0)
                ->update(['status' => 2]);

            // 3. 调用支付方法
            if ($payMethod == 'wxpay') {
                // 微信Native V3支付（PC端扫码支付）
                $result = \app\common\Wxpay::build_native_v3(
                    $this->aid, $targetBid, 0, $title,
                    $orderNo, $amount, 'ai_travel_photo_pay'
                );

                if ($result['status'] == 1) {
                    return json([
                        'status' => 1,
                        'msg'    => '创建订单成功',
                        'data'   => [
                            'order_id'    => $bizOrderId,
                            'payorder_id' => $payorderId,
                            'pay_method'  => 'qrcode',
                            'qrcode_url'  => $result['data']['pay_wx_qrcode_url'] ?? ''
                        ]
                    ]);
                } else {
                    return json(['status' => 0, 'msg' => $result['msg'] ?? '微信支付创建失败']);
                }
            } else {
                // 支付宝电脑网站支付
                $returnUrl = request()->domain() . (string)url('AiTravelPhoto/payReturn');
                $result = \app\common\Alipay::build_page_pay(
                    $this->aid, $targetBid, 0, $title,
                    $orderNo, $amount, 'ai_travel_photo_pay', '', $returnUrl
                );

                if ($result['status'] == 1) {
                    $responseData = [
                        'order_id'    => $bizOrderId,
                        'payorder_id' => $payorderId,
                    ];

                    // build_page_pay 返回 form_html（自动提交表单）或降级到 qrcode_url（当面付二维码）
                    if (!empty($result['data']['form_html'])) {
                        $responseData['form_html']  = $result['data']['form_html'];
                        $responseData['pay_method'] = 'form';
                    } elseif (!empty($result['data']['qrcode_url'])) {
                        $responseData['qrcode_url'] = $result['data']['qrcode_url'];
                        $responseData['pay_method'] = 'qrcode';
                    }

                    return json([
                        'status' => 1,
                        'msg'    => '创建订单成功',
                        'data'   => $responseData
                    ]);
                } else {
                    return json(['status' => 0, 'msg' => $result['msg'] ?? '支付宝支付创建失败']);
                }
            }
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '创建订单失败：' . $e->getMessage()]);
        }
    }

    /**
     * 检查支付状态（通过标准 payorder 记录查询）
     */
    public function checkPayStatus()
    {
        $orderId = input('post.order_id', 0);
        if (!$orderId) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        // 通过业务订单ID查找关联的标准payorder
        $payorder = Db::name('payorder')
            ->where('type', 'ai_travel_photo_pay')
            ->where('orderid', $orderId)
            ->field('id,status,paytime')
            ->find();

        if ($payorder) {
            $paid = ($payorder['status'] == 1);
            // 如果payorder已支付，同步更新业务订单状态
            if ($paid) {
                Db::name('ai_travel_photo_pay_order')
                    ->where('id', $orderId)
                    ->where('status', 0)
                    ->update(['status' => 1, 'paytime' => $payorder['paytime'] ?: time()]);
            }
            return json(['status' => 1, 'data' => ['paid' => $paid, 'order_status' => $payorder['status']]]);
        }

        // 兜底：直接查业务订单表
        $bizOrder = Db::name('ai_travel_photo_pay_order')->where('id', $orderId)->find();
        if (!$bizOrder) {
            return json(['status' => 0, 'msg' => '订单不存在']);
        }
        return json(['status' => 1, 'data' => ['paid' => $bizOrder['status'] == 1, 'order_status' => $bizOrder['status']]]);
    }

    /**
     * 支付回调处理
     * 注意：实际支付回调由 notify.php → Notify → Payorder::payorder() 统一处理
     * 此方法仅作为兼容保留，不再直接处理支付回调
     */
    public function payNotify()
    {
        // 标准支付回调已由 notify.php 统一处理
        // Payorder::payorder() 中 ai_travel_photo_pay 类型会触发 handlePaymentSuccess
        return 'success';
    }

    /**
     * 获取可用套餐列表（商户端调用，仅返回status=1的套餐）
     */
    public function getPackages()
    {
        $type = input('param.type', '');

        // 'recharge' 映射到 'balance'（充值余额统一走 balance 套餐）
        if ($type === 'recharge') {
            $type = 'balance';
        }

        $packages = $this->getPackageSettings();
        
        $result = [];
        if ($type && isset($packages[$type])) {
            // 返回指定类型的套餐，仅返回启用的
            foreach ($packages[$type] as $item) {
                if (($item['status'] ?? 1) == 1) {
                    $result[] = $item;
                }
            }
            // 按sort排序
            usort($result, function($a, $b) {
                return ($a['sort'] ?? 99) - ($b['sort'] ?? 99);
            });
        } else {
            // 返回所有类型的套餐
            foreach ($packages as $t => $items) {
                $result[$t] = [];
                foreach ($items as $item) {
                    if (($item['status'] ?? 1) == 1) {
                        $result[$t][] = $item;
                    }
                }
                usort($result[$t], function($a, $b) {
                    return ($a['sort'] ?? 99) - ($b['sort'] ?? 99);
                });
            }
        }
        
        return json(['status' => 1, 'data' => $result]);
    }

    /**
     * AI消费明细 - 余额变动流水页面 & 数据接口
     */
    public function balance_log()
    {
        // 超级管理员bid为0时，使用aid对应的第一个商家
        $targetBid = $this->bid;
        if ($targetBid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
        }

        // AJAX请求：返回分页JSON数据
        if (request()->isAjax()) {
            try {
                $where = [
                    ['aid', '=', $this->aid],
                    ['bid', '=', $targetBid]
                ];

                // 变动类型筛选
                $type = input('param.type', '');
                if ($type !== '' && in_array($type, ['deduct', 'refund', 'recharge'])) {
                    $where[] = ['type', '=', $type];
                }

                // 日期范围筛选
                $startDate = input('param.start_date', '');
                $endDate = input('param.end_date', '');
                if ($startDate) {
                    $where[] = ['createtime', '>=', strtotime($startDate)];
                }
                if ($endDate) {
                    $where[] = ['createtime', '<=', strtotime($endDate . ' 23:59:59')];
                }

                $page = input('page/d', 1);
                $limit = input('limit/d', 20);

                $typeMap = [
                    'deduct'   => '合成扣费',
                    'refund'   => '退款',
                    'recharge' => '充值',
                ];

                $list = Db::name('business_balance_log')
                    ->where($where)
                    ->order('createtime DESC, id DESC')
                    ->page($page, $limit)
                    ->select()
                    ->each(function ($item) use ($typeMap) {
                        $item['type_text'] = $typeMap[$item['type']] ?? $item['type'];
                        $item['createtime'] = $item['createtime'] ? date('Y-m-d H:i:s', $item['createtime']) : '-';
                        $item['amount'] = number_format(floatval($item['amount']), 2, '.', '');
                        $item['balance_before'] = number_format(floatval($item['balance_before']), 2, '.', '');
                        $item['balance_after'] = number_format(floatval($item['balance_after']), 2, '.', '');
                        return $item;
                    });

                $count = Db::name('business_balance_log')
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

        // 非AJAX请求：渲染页面
        $business = Db::name('business')->where('id', $targetBid)->find();
        $businessInfo = [
            'id' => $targetBid,
            'account_balance' => $business['account_balance'] ?? 0,
        ];
        View::assign('business_info', $businessInfo);

        return View::fetch('ai_travel_photo/balance_log');
    }

    /**
     * 支付成功返回页面（支付宝同步回跳）
     */
    public function payReturn()
    {
        // 重定向到人像管理页面
        return redirect((string)url('AiTravelPhoto/portrait_list'));
    }

    /**
     * 支付成功后更新商户账户（静态方法，供 Payorder::payorder() 回调调用）
     * @param int $bizOrderId  ai_travel_photo_pay_order 表的ID
     * @param int $aid  应用ID
     */
    public static function handlePaymentSuccess($bizOrderId, $aid = 0)
    {
        $order = Db::name('ai_travel_photo_pay_order')->where('id', $bizOrderId)->find();
        if (!$order) return;

        // 已处理则跳过
        if ($order['status'] == 1) return;

        // 更新业务订单状态
        Db::name('ai_travel_photo_pay_order')->where('id', $bizOrderId)->update([
            'status' => 1,
            'paytime' => time()
        ]);

        $business = Db::name('business')->where('id', $order['bid'])->find();
        if (!$business) return;

        // 获取套餐配置
        $useAid = $aid ?: $order['aid'];
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
                ['name' => '季卡', 'days' => 90, 'price' => 799, 'desc' => '90天有效期'],
                ['name' => '年卡', 'days' => 365, 'price' => 2499, 'desc' => '365天有效期'],
                ['name' => '永久', 'days' => 99999, 'price' => 9999, 'desc' => '一次购买永久使用']
            ]
        ];
        $savedSettings = Db::name('admin_set')->where('aid', $useAid)->value('ai_travel_photo_packages');
        $packages = $defaultSettings;
        if ($savedSettings) {
            $saved = json_decode($savedSettings, true);
            if ($saved) $packages = array_merge($defaultSettings, $saved);
        }

        switch ($order['type']) {
            case 'space':
                $addSpace = 0;
                if (isset($packages['space'])) {
                    foreach ($packages['space'] as $pkg) {
                        if ($pkg['name'] == $order['package_name'] || floatval($pkg['price']) == floatval($order['amount'])) {
                            $addSpace = intval($pkg['size']);
                            break;
                        }
                    }
                }
                if ($addSpace > 0) {
                    Db::name('business')->where('id', $order['bid'])->update([
                        'cloud_space' => ($business['cloud_space'] ?? 5120) + $addSpace
                    ]);
                }
                break;

            case 'balance':
                $giftAmount = 0;
                $matchedPkgName = $order['package_name'] ?? '';
                if (isset($packages['balance'])) {
                    foreach ($packages['balance'] as $pkg) {
                        if ($pkg['name'] == $order['package_name'] || floatval($pkg['amount'] ?? $pkg['price'] ?? 0) == floatval($order['amount'])) {
                            $giftAmount = floatval($pkg['gift'] ?? 0);
                            $matchedPkgName = $pkg['name'] ?? $matchedPkgName;
                            break;
                        }
                    }
                }
                $balanceBefore = floatval($business['account_balance'] ?? 0);
                $totalRecharge = floatval($order['amount']) + $giftAmount;
                $balanceAfter = round($balanceBefore + $totalRecharge, 2);
                Db::name('business')->where('id', $order['bid'])->update([
                    'account_balance' => $balanceAfter
                ]);
                // 写入充值流水记录
                $rechargeRemark = $matchedPkgName;
                if ($giftAmount > 0) {
                    $rechargeRemark .= '（含赠送¥' . $giftAmount . '）';
                }
                Db::name('business_balance_log')->insert([
                    'aid' => $order['aid'],
                    'bid' => $order['bid'],
                    'type' => 'recharge',
                    'amount' => $totalRecharge,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'portrait_id' => 0,
                    'order_id' => $bizOrderId,
                    'remark' => $rechargeRemark,
                    'createtime' => time(),
                ]);
                break;

            case 'renew':
                $addDays = 0;
                if (isset($packages['renew'])) {
                    foreach ($packages['renew'] as $pkg) {
                        if ($pkg['name'] == $order['package_name'] || floatval($pkg['price']) == floatval($order['amount'])) {
                            $addDays = intval($pkg['days']);
                            break;
                        }
                    }
                }
                if ($addDays > 0) {
                    $currentEndtime = $business['endtime'] ?? 0;
                    $newEndtime = ($currentEndtime > time()) ? $currentEndtime + ($addDays * 86400) : time() + ($addDays * 86400);
                    if ($addDays >= 99999) {
                        $newEndtime = 0; // 永久
                    }
                    Db::name('business')->where('id', $order['bid'])->update([
                        'endtime' => $newEndtime
                    ]);
                }
                break;
        }
    }

    /**
     * 回填成片文件大小（管理员接口）
     * 通过 HTTP HEAD 请求获取 COS 文件实际大小，回填到 result 表的 file_size 字段
     */
    public function backfill_result_sizes()
    {
        // 仅平台管理员可操作
        $isAdmin = intval($this->user['bid'] ?? -1) === 0;
        if (!$isAdmin) {
            return json(['status' => 0, 'msg' => '无权限操作']);
        }

        $bid = input('bid/d', 0);
        $limit = input('limit/d', 200);

        try {
            $result = \app\service\SpaceCheckService::backfillResultFileSizes($bid, $limit);
            return json([
                'status' => 1,
                'msg' => "回填完成：共{$result['total']}条，成功{$result['updated']}条，失败{$result['failed']}条",
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '回填失败: ' . $e->getMessage()]);
        }
    }

    // ====================自拍端管理====================

    /**
     * 自拍端管理首页
     */
    public function selfie_manage()
    {
        if (request()->isAjax()) {
            return $this->selfie_stats();
        }

        $bid = $this->bid;
        $targetBid = $bid;
        if ($targetBid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id') ?: 0;
        }

        // 获取门店列表 - 严格按商家隔离，商家间互不可见
        $mendianQuery = Db::name('mendian')
            ->where('aid', $this->aid)
            ->where('status', 1);

        if ($bid == 0) {
            // 超级管理员：查看目标商家门店 + bid=0的公共门店
            $mendianQuery->where('bid', 'in', [$targetBid, 0]);
        } else {
            // 普通商家：仅查看自己的门店，不可见其他商家和公共门店
            $mendianQuery->where('bid', $bid);
        }

        $mendianList = $mendianQuery
            ->field('id, name, bid, selfie_enabled')
            ->order('id ASC')
            ->select()
            ->toArray();

        // 获取二维码列表 - 匹配所有相关门店的二维码
        $mendianIds = array_column($mendianList, 'id');
        $qrcodeList = [];
        if (!empty($mendianIds)) {
            $qrcodeList = Db::name('ai_travel_photo_selfie_qrcode')
                ->where('aid', $this->aid)
                ->where('mdid', 'in', $mendianIds)
                ->select()
                ->toArray();
        }

        // 按门店ID索引
        $qrcodeMap = [];
        foreach ($qrcodeList as $qr) {
            $qrcodeMap[$qr['mdid']] = $qr;
        }

        View::assign('mendian_list', $mendianList);
        View::assign('qrcode_map', $qrcodeMap);
        View::assign('target_bid', $targetBid);

        return View::fetch('ai_travel_photo/selfie_manage');
    }

    /**
     * 生成/获取门店自拍二维码
     */
    public function selfie_qrcode()
    {
        $mdid = input('mdid/d', 0);
        if (!$mdid) {
            return json(['status' => 0, 'msg' => '请选择门店']);
        }

        // 校验门店归属权限：商家只能操作自己的门店
        $mendian = Db::name('mendian')->where('id', $mdid)->where('aid', $this->aid)->find();
        if (!$mendian) {
            return json(['status' => 0, 'msg' => '门店不存在']);
        }
        if ($this->bid > 0 && $mendian['bid'] != $this->bid) {
            return json(['status' => 0, 'msg' => '无权操作此门店']);
        }

        // 确定业务归属的bid
        $targetBid = $this->bid;
        if ($targetBid == 0) {
            $targetBid = $mendian['bid'] ?: (Db::name('business')->where('aid', $this->aid)->value('id') ?: 0);
        }

        try {
            $selfieService = new \app\service\AiTravelPhotoSelfieService();
            $result = $selfieService->getOrCreateQrcode($this->aid, $targetBid, $mdid);
            return json(['status' => 1, 'msg' => '操作成功', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 推文配置 (GET获取/POST保存)
     */
    public function selfie_push_config()
    {
        $mdid = input('mdid/d', 0);
        if (!$mdid) {
            return json(['status' => 0, 'msg' => '请选择门店']);
        }

        // 校验门店归属权限：商家只能操作自己的门店
        $mendian = Db::name('mendian')->where('id', $mdid)->where('aid', $this->aid)->find();
        if (!$mendian) {
            return json(['status' => 0, 'msg' => '门店不存在']);
        }
        if ($this->bid > 0 && $mendian['bid'] != $this->bid) {
            return json(['status' => 0, 'msg' => '无权操作此门店']);
        }

        $targetBid = $this->bid;
        if ($targetBid == 0) {
            $targetBid = $mendian['bid'] ?: (Db::name('business')->where('aid', $this->aid)->value('id') ?: 0);
        }

        $selfieService = new \app\service\AiTravelPhotoSelfieService();

        if (request()->isPost()) {
            $config = [
                'push_title' => input('push_title', ''),
                'push_desc' => input('push_desc', ''),
                'push_cover' => input('push_cover', ''),
            ];
            try {
                $selfieService->savePushConfig($this->aid, $targetBid, $mdid, $config);
                return json(['status' => 1, 'msg' => '保存成功']);
            } catch (\Exception $e) {
                return json(['status' => 0, 'msg' => $e->getMessage()]);
            }
        }

        // GET请求
        $config = $selfieService->getPushConfig($this->aid, $targetBid, $mdid);
        return json(['status' => 1, 'data' => $config]);
    }

    /**
     * 自拍数据统计
     */
    public function selfie_stats()
    {
        $mdid = input('mdid/d', 0);

        $bid = $this->bid;
        $targetBid = $bid;
        if ($targetBid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id') ?: 0;
        }

        // 如果指定了门店，校验门店归属权限
        if ($mdid > 0 && $bid > 0) {
            $mendian = Db::name('mendian')->where('id', $mdid)->where('aid', $this->aid)->where('bid', $bid)->find();
            if (!$mendian) {
                return json(['status' => 0, 'msg' => '无权查看此门店统计']);
            }
        }

        try {
            $selfieService = new \app\service\AiTravelPhotoSelfieService();
            // 传入 isAdmin 标记，让 Service 层根据角色做数据隔离
            $isAdmin = ($bid == 0);
            $stats = $selfieService->getStats($this->aid, $targetBid, $mdid, $isAdmin);
            return json(['status' => 1, 'data' => $stats]);
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 切换门店自拍端启用状态
     */
    public function selfie_toggle_enable()
    {
        $mdid = input('mdid/d', 0);
        $enabled = input('enabled/d', 1);

        if (!$mdid) {
            return json(['status' => 0, 'msg' => '请选择门店']);
        }

        // 校验门店归属：必须属于当前平台，且商家只能操作自己的门店
        $mendian = Db::name('mendian')->where('id', $mdid)->where('aid', $this->aid)->find();
        if (!$mendian) {
            return json(['status' => 0, 'msg' => '门店不存在']);
        }
        if ($this->bid > 0 && $mendian['bid'] != $this->bid) {
            return json(['status' => 0, 'msg' => '无权操作此门店']);
        }

        Db::name('mendian')->where('id', $mdid)->update(['selfie_enabled' => $enabled]);

        return json(['status' => 1, 'msg' => ($enabled ? '已启用' : '已禁用')]);
    }
}
