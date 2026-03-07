<?php
/**
 * 商家API Key配置控制器
 * 商家用户管理本商户的API Key配置
 */
namespace app\controller;

use think\facade\View;
use think\facade\Db;
use app\service\MerchantApiKeyService;

class MerchantApiKey extends Common
{
    protected $service;

    public function initialize()
    {
        parent::initialize();
        
        // 仅商户用户可访问
        if ($this->bid <= 0) {
            if (request()->isAjax()) {
                echojson(['status' => 0, 'msg' => '此功能仅限商户用户使用']);
                exit;
            }
            showmsg('此功能仅限商户用户使用');
        }
        
        $this->service = new MerchantApiKeyService();
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
            
            $result = $this->service->getList($this->bid, $where, $page, $limit, $order);
            
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
            $info = $this->service->getDetail($id, $this->bid);
            if (!$info) {
                return $this->error('配置不存在');
            }
        }
        
        // 获取可选供应商列表（商户可配置同一供应商的多个Key）
        $providers = $this->service->getAvailableProviders();
        
        // 获取商家门店列表
        $mendianList = $this->service->getMendianList($this->bid);
        
        View::assign('info', $info);
        View::assign('providers', $providers);
        View::assign('providers_json', json_encode($providers, JSON_UNESCAPED_UNICODE));
        View::assign('mendian_list', $mendianList);
        View::assign('mendian_list_json', json_encode($mendianList, JSON_UNESCAPED_UNICODE));
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
        
        $result = $this->service->save($postInfo, $this->bid, $this->aid);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('商户API Key配置' . ($postInfo['id'] ? '编辑' : '新增'), 1);
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
        
        $result = $this->service->delete($id, $this->bid);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('商户API Key配置删除 ID:' . $id, 1);
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
        
        $result = $this->service->updateStatus($id, $status, $this->bid);
        
        if ($result['status'] == 1) {
            \app\common\System::plog('商户API Key配置状态变更 ID:' . $id . ' 状态:' . $status, 1);
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
        
        $result = $this->service->testConnection($id, $this->bid);
        
        return json($result);
    }

    /**
     * 检查配置完整性
     * @param string capability_type 能力类型：image/video
     */
    public function check_config()
    {
        $capabilityType = input('param.capability_type', '');
        
        if ($capabilityType == 'image') {
            $result = $this->service->checkImageApiConfig($this->bid, $this->mdid);
        } elseif ($capabilityType == 'video') {
            $result = $this->service->checkVideoApiConfig($this->bid, $this->mdid);
        } else {
            // 检查是否有任何API Key配置
            $count = Db::name('merchant_api_key')
                ->where('bid', $this->bid)
                ->where('is_active', 1)
                ->count();
            
            $result = [
                'status' => $count > 0 ? 1 : 0,
                'msg' => $count > 0 ? '已配置' : '请先配置API Key',
                'has_config' => $count > 0,
                'redirect_url' => '/MerchantApiKey/index'
            ];
        }
        
        return json($result);
    }

    /**
     * 获取Key池状态
     */
    public function get_pool_status()
    {
        $providerCode = input('param.provider_code', '');
        
        $result = $this->service->getPoolStatus($this->bid, $providerCode);
        
        return json([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $result
        ]);
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
        
        $provider = Db::name('model_provider')
            ->field('id, provider_code, provider_name, auth_config')
            ->where('id', $providerId)
            ->find();
        
        if (!$provider) {
            return json(['status' => 0, 'msg' => '供应商不存在']);
        }
        
        if (isset($provider['auth_config']) && is_string($provider['auth_config'])) {
            $provider['auth_config'] = json_decode($provider['auth_config'], true) ?: [];
        }
        
        return json([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $provider
        ]);
    }

    /**
     * 获取门店列表（用于门店范围选择）
     */
    public function get_mendian_list()
    {
        $mendianList = $this->service->getMendianList($this->bid);
        
        return json([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $mendianList
        ]);
    }
}
