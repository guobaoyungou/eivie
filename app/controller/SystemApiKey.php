<?php
/**
 * 系统API Key配置控制器
 * 管理员可从模型广场的供应商列表中选择供应商，填写对应的API Key
 * 支持同一供应商配置多个API Key，实现Key池负载均衡
 */
namespace app\controller;

use think\facade\View;
use think\facade\Db;
use app\service\SystemApiKeyService;
use app\service\SystemApiKeyPoolService;

class SystemApiKey extends Common
{
    protected $service;
    protected $poolService;

    public function initialize()
    {
        parent::initialize();
        $this->service = new SystemApiKeyService();
        $this->poolService = new SystemApiKeyPoolService();
    }

    /**
     * 配置列表页面
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = input('param.page', 1);
            $limit = input('param.limit', 20);
            
            // 排序处理
            $order = 'config.sort asc, config.id desc';
            if (input('param.field') && input('param.order')) {
                $order = 'config.' . input('param.field') . ' ' . input('param.order');
            }
            
            // 搜索条件
            $where = [];
            if (input('param.keyword')) {
                $keyword = input('param.keyword');
                $where[] = ['config.config_name|provider.provider_name|config.provider_code', 'like', '%' . $keyword . '%'];
            }
            if (input('param.provider_code')) {
                $where[] = ['config.provider_code', '=', input('param.provider_code')];
            }
            if (input('param.is_active') !== '' && input('param.is_active') !== null) {
                $where[] = ['config.is_active', '=', intval(input('param.is_active'))];
            }
            
            $result = $this->service->getList($where, $page, $limit, $order);
            
            return json([
                'code' => 0,
                'msg' => '查询成功',
                'count' => $result['count'],
                'data' => $result['data']
            ]);
        }
        
        // 获取供应商列表用于筛选
        $providers = Db::name('model_provider')
            ->field('provider_code, provider_name')
            ->where('status', 1)
            ->order('sort asc, id asc')
            ->select()->toArray();
        
        View::assign('providers', $providers);
        return View::fetch();
    }

    /**
     * 编辑/新增页面
     */
    public function edit()
    {
        $id = input('param.id', 0);
        $info = [];
        
        if ($id > 0) {
            $info = $this->service->getDetail($id);
            if (!$info) {
                return $this->error('配置不存在');
            }
        }
        
        // 获取可选供应商列表（现在支持多Key，所有供应商都可选）
        $providers = $this->service->getAvailableProviders();
        
        View::assign('info', $info);
        View::assign('providers', $providers);
        View::assign('providers_json', json_encode($providers, JSON_UNESCAPED_UNICODE));
        return View::fetch();
    }

    /**
     * 保存配置
     */
    public function save()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '请求方式错误']);
        }
        
        $postInfo = input('post.info/a', []);
        
        $result = $this->service->save($postInfo);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('API Key配置' . ($postInfo['id'] ? '编辑' : '新增'), 1);
        }
        
        return json($result);
    }

    /**
     * 删除配置
     */
    public function delete()
    {
        $id = input('post.id', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        $result = $this->service->delete($id);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('API Key配置删除 ID:' . $id, 1);
        }
        
        return json($result);
    }

    /**
     * 切换启用状态
     */
    public function setst()
    {
        $id = input('param.id', 0);
        $status = input('param.status', 0);
        
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        $result = $this->service->updateStatus($id, $status);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('API Key配置状态变更 ID:' . $id . ' 状态:' . $status, 1);
        }
        
        return json($result);
    }

    /**
     * 测试API连接
     */
    public function test()
    {
        $id = input('post.id', 0);
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        $result = $this->service->testConnection($id);
        
        return json($result);
    }

    /**
     * 获取供应商认证字段
     */
    public function get_provider_fields()
    {
        $providerId = input('param.provider_id', 0);
        if (!$providerId) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        $provider = $this->service->getProviderAuthFields($providerId);
        if (!$provider) {
            return json(['status' => 0, 'msg' => '供应商不存在']);
        }
        
        return json([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $provider
        ]);
    }

    /**
     * 获取可选供应商列表
     */
    public function get_providers()
    {
        $excludeId = input('param.exclude_id', 0);
        $providers = $this->service->getAvailableProviders($excludeId);
        
        return json([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $providers
        ]);
    }

    /**
     * 获取Key池状态概览
     */
    public function get_pool_status()
    {
        $providerCode = input('param.provider_code', '');
        $result = $this->poolService->getPoolStatus($providerCode);
        
        return json([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $result
        ]);
    }

    /**
     * 重置并发计数（服务重启时调用）
     */
    public function reset_concurrency()
    {
        $providerCode = input('param.provider_code', '');
        $this->poolService->resetConcurrency($providerCode);
        
        \app\common\System::plog('API Key并发计数重置' . ($providerCode ? " 供应商:{$providerCode}" : ' 全部'), 1);
        
        return json([
            'status' => 1,
            'msg' => '重置成功'
        ]);
    }
}
