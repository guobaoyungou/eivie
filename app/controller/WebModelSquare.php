<?php
/**
 * 模型广场控制器
 * 系统管理员控制台 - 管理供应商、模型类型、模型列表
 */
namespace app\controller;

use think\facade\View;
use think\facade\Db;
use app\service\ModelSquareService;

class WebModelSquare extends WebCommon
{
    protected $service;

    public function initialize()
    {
        parent::initialize();
        $this->service = new ModelSquareService();
    }

    // ============================================================
    // 供应商管理
    // ============================================================

    /**
     * 供应商列表
     */
    public function provider_list()
    {
        if (request()->isAjax()) {
            $page = input('param.page', 1);
            $limit = input('param.limit', 20);

            $where = [];
            if (input('param.keyword')) {
                $keyword = input('param.keyword');
                $where[] = ['provider_name|provider_code', 'like', '%' . $keyword . '%'];
            }
            if (input('?param.status') && input('param.status') !== '') {
                $where[] = ['status', '=', input('param.status')];
            }

            $order = 'sort asc, id desc';
            if (input('param.field') && input('param.order')) {
                $order = input('param.field') . ' ' . input('param.order');
            }

            $result = $this->service->getProviderList($where, $page, $limit, $order);
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $result['count'], 'data' => $result['data']]);
        }
        return View::fetch();
    }

    /**
     * 供应商编辑页面
     */
    public function provider_edit()
    {
        $id = input('param.id', 0);
        $info = [];
        if ($id > 0) {
            $info = $this->service->getProviderDetail($id);
        }
        View::assign('info', $info);
        return View::fetch();
    }

    /**
     * 供应商保存
     */
    public function provider_save()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        $data = input('post.info/a');
        $result = $this->service->saveProvider($data);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('保存供应商:' . $data['provider_name'], 1);
        }
        
        return json($result);
    }

    /**
     * 供应商删除
     */
    public function provider_delete()
    {
        $id = input('post.id', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $result = $this->service->deleteProvider($id);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('删除供应商ID:' . $id, 1);
        }
        
        return json($result);
    }

    /**
     * 供应商状态更新
     */
    public function provider_status()
    {
        $id = input('post.id', 0);
        $status = input('post.status', 0);

        $result = $this->service->updateProviderStatus($id, $status);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('更新供应商状态ID:' . $id . ' 状态:' . $status, 1);
        }
        
        return json($result);
    }

    // ============================================================
    // 模型类型管理
    // ============================================================

    /**
     * 类型列表
     */
    public function type_list()
    {
        if (request()->isAjax()) {
            $page = input('param.page', 1);
            $limit = input('param.limit', 20);

            $where = [];
            if (input('param.keyword')) {
                $keyword = input('param.keyword');
                $where[] = ['type_name|type_code', 'like', '%' . $keyword . '%'];
            }
            if (input('?param.status') && input('param.status') !== '') {
                $where[] = ['status', '=', input('param.status')];
            }

            $order = 'sort asc, id desc';
            if (input('param.field') && input('param.order')) {
                $order = input('param.field') . ' ' . input('param.order');
            }

            $result = $this->service->getTypeList($where, $page, $limit, $order);
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $result['count'], 'data' => $result['data']]);
        }

        // 可选输入输出类型
        $ioTypes = [
            'text' => '文本',
            'image' => '图片',
            'audio' => '音频',
            'video' => '视频',
            'vector' => '向量',
        ];
        View::assign('ioTypes', $ioTypes);
        return View::fetch();
    }

    /**
     * 类型编辑页面
     */
    public function type_edit()
    {
        $id = input('param.id', 0);
        $info = [];
        if ($id > 0) {
            $info = $this->service->getTypeDetail($id);
        }

        $ioTypes = [
            'text' => '文本',
            'image' => '图片',
            'audio' => '音频',
            'video' => '视频',
            'vector' => '向量',
        ];

        View::assign('info', $info);
        View::assign('ioTypes', $ioTypes);
        return View::fetch();
    }

    /**
     * 类型保存
     */
    public function type_save()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        $data = input('post.info/a');
        $result = $this->service->saveType($data);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('保存模型类型:' . $data['type_name'], 1);
        }
        
        return json($result);
    }

    /**
     * 类型删除
     */
    public function type_delete()
    {
        $id = input('post.id', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $result = $this->service->deleteType($id);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('删除模型类型ID:' . $id, 1);
        }
        
        return json($result);
    }

    /**
     * 类型状态更新
     */
    public function type_status()
    {
        $id = input('post.id', 0);
        $status = input('post.status', 0);

        $result = $this->service->updateTypeStatus($id, $status);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('更新模型类型状态ID:' . $id . ' 状态:' . $status, 1);
        }
        
        return json($result);
    }

    // ============================================================
    // 模型管理
    // ============================================================

    /**
     * 模型列表
     */
    public function model_list()
    {
        if (request()->isAjax()) {
            $page = input('param.page', 1);
            $limit = input('param.limit', 20);

            $where = [];
            if (input('param.keyword')) {
                $keyword = input('param.keyword');
                $where[] = ['model.model_name|model.model_code', 'like', '%' . $keyword . '%'];
            }
            if (input('param.provider_id')) {
                $where[] = ['model.provider_id', '=', input('param.provider_id')];
            }
            if (input('param.type_id')) {
                $where[] = ['model.type_id', '=', input('param.type_id')];
            }
            if (input('?param.is_active') && input('param.is_active') !== '') {
                $where[] = ['model.is_active', '=', input('param.is_active')];
            }

            $order = 'model.sort asc, model.id desc';
            if (input('param.field') && input('param.order')) {
                $order = 'model.' . input('param.field') . ' ' . input('param.order');
            }

            $result = $this->service->getModelList($where, $page, $limit, $order);
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $result['count'], 'data' => $result['data']]);
        }

        $providers = $this->service->getActiveProviders();
        $types = $this->service->getActiveTypes();

        View::assign('providers', $providers);
        View::assign('types', $types);
        return View::fetch();
    }

    /**
     * 模型编辑页面
     */
    public function model_edit()
    {
        $id = input('param.id', 0);
        $info = [];
        if ($id > 0) {
            $info = $this->service->getModelDetail($id);
        }

        $providers = $this->service->getActiveProviders();
        $types = $this->service->getActiveTypes();

        View::assign('info', $info);
        View::assign('providers', $providers);
        View::assign('types', $types);
        return View::fetch();
    }

    /**
     * 模型保存
     */
    public function model_save()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }

        $data = input('post.info/a');
        $result = $this->service->saveModel($data);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('保存模型:' . $data['model_name'], 1);
        }
        
        return json($result);
    }

    /**
     * 模型删除
     */
    public function model_delete()
    {
        $id = input('post.id', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }

        $result = $this->service->deleteModel($id);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('删除模型ID:' . $id, 1);
        }
        
        return json($result);
    }

    /**
     * 模型状态更新
     */
    public function model_status()
    {
        $id = input('post.id', 0);
        $status = input('post.status', 0);

        $result = $this->service->updateModelStatus($id, $status);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('更新模型状态ID:' . $id . ' 状态:' . $status, 1);
        }
        
        return json($result);
    }

    /**
     * 模型推荐状态更新
     */
    public function model_recommend()
    {
        $id = input('post.id', 0);
        $is_recommend = input('post.is_recommend', 0);

        $result = $this->service->updateModelRecommend($id, $is_recommend);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('更新模型推荐状态ID:' . $id . ' 推荐:' . $is_recommend, 1);
        }
        
        return json($result);
    }

    /**
     * 模型详情
     */
    public function model_detail()
    {
        $id = input('param.id', 0);
        $info = $this->service->getModelDetail($id);
        View::assign('info', $info);
        return View::fetch();
    }
}
