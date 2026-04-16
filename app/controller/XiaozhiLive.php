<?php

namespace app\controller;

use app\BaseController;
use think\facade\Cache;
use think\facade\Db;

/**
 * 小智直播管理控制器
 * 对接 Go 服务 (xiaozhi-cloud) REST API
 */
class XiaozhiLive extends BaseController
{
    // Go 服务地址配置
    protected $apiBase = 'http://127.0.0.1:9503/api/v1';
    protected $wsBase = 'ws://127.0.0.1:9502';
    
    /**
     * 小智直播管理主页
     */
    public function index()
    {
        $this->checkAuth();
        return view('xiaozhi_live/index', [
            'aid' => session('aid'),
            'title' => '小智直播管理中心',
        ]);
    }
    
    /**
     * 直播间列表 (API代理)
     * 前端 -> PHP -> Go API
     */
    public function rooms()
    {
        $this->checkAuth();
        $page = input('page', 1);
        $result = $this->goApiGet("/rooms?page={$page}&page_size=20");
        return json($result);
    }
    
    /**
     * 创建直播间
     */
    public function createRoom()
    {
        $this->checkAuth();
        $data = request()->post();
        $data['aid'] = session('aid');
        $result = $this->goApiPost('/rooms', $data);
        return json($result);
    }
    
    /**
     * 启动直播间弹幕抓取
     */
    public function startLive($id)
    {
        $this->checkAuth();
        $result = $this->goApiPost("/rooms/{$id}/start", []);
        return json($result);
    }
    
    /**
     * 停止直播间弹幕抓取
     */
    public function stopLive($id)
    {
        $this->checkAuth();
        $result = $this->goApiPost("/rooms/{$id}/stop", []);
        return json($result);
    }
    
    /**
     * 设备列表
     */
    public function devices()
    {
        $this->checkAuth();
        $onlineOnly = input('online', 0);
        $page = input('page', 1);
        $result = $this->goApiGet("/devices?page={$page}&page_size=20&online={$onlineOnly}");
        return json($result);
    }
    
    /**
     * 注册设备
     */
    public function registerDevice()
    {
        $this->checkAuth();
        $data = request()->post();
        $data['aid'] = session('aid');
        $result = $this->goApiPost('/devices', $data);
        return json($result);
    }
    
    /**
     * 模型广场列表
     */
    public function models()
    {
        $this->checkAuth();
        $result = $this->goApiGet('/models');
        return json($result);
    }
    
    /**
     * 知识库列表
     */
    public function knowledgeBases()
    {
        $this->checkAuth();
        $result = $this->goApiGet('/knowledge-bases');
        return json($result);
    }
    
    /**
     * 上传文档到知识库
     */
    public function uploadDocument($kbId)
    {
        $this->checkAuth();
        $file = request()->file('file');
        if (!$file) {
            return json(['code' => 400, 'message' => '请上传文件']);
        }
        
        $ch = curl_init("{$this->apiBase}/knowledge-bases/{$kbId}/documents");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => new \CURLFile(
                $file->getRealPath(),
                $file->getOriginalMime(),
                $file->getOriginalName()
            )],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->generateToken()],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json(json_decode($response, true));
    }
    
    /**
     * 弹幕设置
     */
    public function danmakuSettings($roomId)
    {
        $this->checkAuth();
        $method = request()->isPost() || request()->isPut() ? 'PUT' : 'GET';
        $result = $method === 'PUT'
            ? $this->goApiPut("/danmaku-settings/room/{$roomId}", request()->post())
            : $this->goApiGet("/danmaku-settings/room/{$roomId}");
        return json($result);
    }
    
    /**
     * 统计面板数据
     */
    public function stats()
    {
        $this->checkAuth();
        $result = $this->goApiGet('/stats/dashboard');
        return json($result);
    }
    
    /**
     * 网关状态
     */
    public function gatewayStats()
    {
        $this->checkAuth();
        $result = $this->goApiGet('/stats/gateway');
        return json($result);
    }
    
    /**
     * 门店管理
     */
    public function stores()
    {
        $this->checkAuth();
        if (request()->isPost()) {
            $data = request()->post();
            $data['aid'] = session('aid');
            $result = $this->goApiPost('/stores', $data);
        } else {
            $result = $this->goApiGet('/stores');
        }
        return json($result);
    }
    
    /**
     * WebSocket 连接配置
     */
    public function wsConfig()
    {
        $this->checkAuth();
        $wsProto = request()->isSsl() ? 'wss://' : 'ws://';
        $apiProto = request()->isSsl() ? 'https://' : 'http://';
        
        return json([
            'code' => 0,
            'data' => [
                'ws_xiaozhi' => $wsProto . request()->host() . ':9502/xiaozhi/v1/',
                'ws_live'    => $wsProto . request()->host() . ':9502/live/v1/',
                'api_base'   => $apiProto . request()->host() . ':9503/api/v1',
                'token'      => $this->generateToken(),
            ],
        ]);
    }

    protected function checkAuth()
    {
        if (!session('?ADMIN_LOGIN')) {
            json(['code' => 401, 'message' => '请先登录'])->send();
            exit;
        }
    }
    
    protected function generateToken()
    {
        $payload = [
            'sub' => session('ADMIN_UID'),
            'aid' => session('ADMIN_AID'),
            'iat' => time(),
            'exp' => time() + 86400,
        ];
        
        $secret = config('authkey') ?: 'xiaozhi-cloud-jwt-secret';
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload_b64 = base64_encode(json_encode($payload));
        $sig = base64_encode(hash_hmac('sha256', "$header.$payload_b64", $secret, true));
        
        return "$header.$payload_b64.$sig";
    }
    
    protected function goApiGet($path, $timeout = 10)
    {
        return $this->goApiRequest('GET', $path, null, $timeout);
    }
    
    protected function goApiPost($path, $data, $timeout = 30)
    {
        return $this->goApiRequest('POST', $path, $data, $timeout);
    }
    
    protected function goApiPut($path, $data, $timeout = 30)
    {
        return $this->goApiRequest('PUT', $path, $data, $timeout);
    }
    
    protected function goApiDelete($path, $timeout = 10)
    {
        return $this->goApiRequest('DELETE', $path, null, $timeout);
    }
    
    protected function goApiRequest($method, $path, $data, $timeout = 30)
    {
        $url = $this->apiBase . $path;
        $ch = curl_init($url);
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $this->generateToken(),
            'X-Forwarded-For: ' . request()->ip(),
        ];
        
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];
        
        if ($data !== null && in_array($method, ['POST', 'PUT'])) {
            $opts[CURLOPT_POSTFIELDS] = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            return ['code' => 503, 'message' => 'Go服务连接失败: ' . $err];
        }
        
        $decoded = json_decode($response, true);
        return $decoded ?: ['code' => 502, 'message' => '无效响应'];
    }
}
