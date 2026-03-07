<?php
/**
 * API管理控制器
 * 提供API接口列表、扫描、测试等管理功能
 */

namespace app\controller;

use think\facade\View;
use think\facade\Request;
use app\service\ApiManageService;

class ApiManage extends Common
{
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        
        // 检查是否为平台管理员
        if (!isset($this->user['isadmin']) || $this->user['isadmin'] < 2) {
            $this->error('无权限访问，仅平台管理员可访问');
        }
    }

    /**
     * 接口列表页面
     */
    public function index()
    {
        if (Request::isAjax()) {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $category = input('category', '');
            $keyword = input('keyword', '');
            $method = input('method', '');
            $auth_required = input('auth_required', '');
            
            $service = new ApiManageService();
            $result = $service->getInterfaceList($this->aid, $page, $limit, [
                'category' => $category,
                'keyword' => $keyword,
                'method' => $method,
                'auth_required' => $auth_required
            ]);
            
            return json($result);
        }
        
        // 获取分类列表
        $service = new ApiManageService();
        $categories = $service->getCategories($this->aid);
        
        View::assign('categories', $categories);
        return View::fetch();
    }

    /**
     * 接口详情
     */
    public function detail()
    {
        $id = input('id', 0);
        
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        $service = new ApiManageService();
        $detail = $service->getInterfaceDetail($this->aid, $id);
        
        if (!$detail) {
            return json(['status' => 0, 'msg' => '接口不存在']);
        }
        
        return json(['status' => 1, 'data' => $detail]);
    }

    /**
     * 编辑接口
     */
    public function edit()
    {
        if (Request::isPost()) {
            $id = input('id', 0);
            $data = [
                'name' => input('name', ''),
                'category' => input('category', ''),
                'description' => input('description', ''),
                'request_params' => input('request_params', ''),
                'response_example' => input('response_example', ''),
                'tags' => input('tags', ''),
                'remark' => input('remark', ''),
                'status' => input('status', 1),
                'auth_required' => input('auth_required', 0),
                'sort' => input('sort', 0)
            ];
            
            $service = new ApiManageService();
            $result = $service->updateInterface($this->aid, $id, $data);
            
            return json($result);
        }
        
        $id = input('id', 0);
        if (!$id) {
            $this->error('参数错误');
        }
        
        $service = new ApiManageService();
        $detail = $service->getInterfaceDetail($this->aid, $id);
        
        if (!$detail) {
            $this->error('接口不存在');
        }
        
        View::assign('detail', $detail);
        return View::fetch();
    }

    /**
     * 接口扫描页面
     */
    public function scan()
    {
        if (Request::isAjax()) {
            // 记录请求日志
            \think\facade\Log::info('API扫描请求', [
                'type' => input('type'),
                'controllers' => input('controllers'),
                'method' => request()->method(),
                'is_ajax' => Request::isAjax()
            ]);
            
            $type = input('type', 'all'); // all=全量扫描, increment=增量扫描
            $controllers = input('controllers', []); // 指定控制器
            
            try {
                $service = new ApiManageService();
                $result = $service->scanInterfaces($this->aid, $type, $controllers);
                
                \think\facade\Log::info('API扫描结果', $result);
                
                return json($result);
            } catch (\Exception $e) {
                \think\facade\Log::error('API扫描异常', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return json([
                    'status' => 0,
                    'msg' => '扫描失败：' . $e->getMessage()
                ]);
            }
        }
        
        // 获取可扫描的控制器列表
        $service = new ApiManageService();
        $controllers = $service->getControllersForScan();
        
        View::assign('controllers', $controllers);
        return View::fetch();
    }

    /**
     * 保存扫描结果
     */
    public function savescan()
    {
        try {
            $interfacesJson = input('interfaces', '');
            
            \think\facade\Log::info('保存扫描结果请求', [
                'interfaces_json' => $interfacesJson,
                'aid' => $this->aid,
                'method' => request()->method()
            ]);
            
            // 解析JSON字符串
            if (is_string($interfacesJson)) {
                $interfaces = json_decode($interfacesJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return json([
                        'status' => 0, 
                        'msg' => 'JSON解析失败: ' . json_last_error_msg()
                    ]);
                }
            } else {
                $interfaces = $interfacesJson;
            }
            
            if (empty($interfaces) || !is_array($interfaces)) {
                return json(['status' => 0, 'msg' => '没有要保存的数据']);
            }
            
            \think\facade\Log::info('保存扫描结果解析后', [
                'interfaces_count' => count($interfaces),
                'first_item' => isset($interfaces[0]) ? $interfaces[0] : null
            ]);
            
            $service = new ApiManageService();
            $result = $service->saveScanResults($this->aid, $interfaces);
            
            \think\facade\Log::info('保存扫描结果完成', $result);
            
            return json($result);
        } catch (\Exception $e) {
            \think\facade\Log::error('保存扫描结果异常', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return json([
                'status' => 0,
                'msg' => '保存失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 在线测试页面
     */
    public function test()
    {
        $id = input('id', 0);
        
        if (!$id) {
            $this->error('参数错误');
        }
        
        $service = new ApiManageService();
        $detail = $service->getInterfaceDetail($this->aid, $id);
        
        if (!$detail) {
            $this->error('接口不存在');
        }
        
        View::assign('detail', $detail);
        return View::fetch();
    }

    /**
     * 发送测试请求
     */
    public function sendtest()
    {
        $id = input('id', 0);
        $params = input('params', []);
        
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        $service = new ApiManageService();
        $result = $service->testInterface($this->aid, $this->uid, $id, $params);
        
        return json($result);
    }

    /**
     * 测试历史
     */
    public function testlog()
    {
        if (Request::isAjax()) {
            $page = input('page', 1);
            $limit = input('limit', 20);
            $interface_id = input('interface_id', 0);
            
            $service = new ApiManageService();
            $result = $service->getTestLogs($this->aid, $this->uid, $page, $limit, $interface_id);
            
            return json($result);
        }
        
        return View::fetch();
    }

    /**
     * 测试日志详情
     */
    public function testlogdetail()
    {
        $id = input('id', 0);
        
        if (!$id) {
            return json(['status' => 0, 'msg' => '参数错误']);
        }
        
        $service = new ApiManageService();
        $detail = $service->getTestLogDetail($this->aid, $id);
        
        if (!$detail) {
            return json(['status' => 0, 'msg' => '日志不存在']);
        }
        
        return json(['status' => 1, 'data' => $detail]);
    }

    /**
     * 导出文档
     */
    public function export()
    {
        $ids = input('ids', []);
        $format = input('format', 'markdown'); // markdown, json
        
        if (empty($ids)) {
            return json(['status' => 0, 'msg' => '请选择要导出的接口']);
        }
        
        $service = new ApiManageService();
        $result = $service->exportDocuments($this->aid, $ids, $format);
        
        if ($result['status'] == 1) {
            // 返回文件下载
            return download($result['file'], $result['filename']);
        }
        
        return json($result);
    }
}
