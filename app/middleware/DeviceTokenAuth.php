<?php
declare(strict_types=1);

namespace app\middleware;

use app\service\AiTravelPhotoDeviceService;
use Closure;
use think\Request;
use think\Response;

/**
 * 设备Token认证中间件
 */
class DeviceTokenAuth
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Device-Token');
        
        if (empty($token)) {
            return json(['code' => 401, 'msg' => '缺少设备Token']);
        }
        
        // 验证Token
        $deviceService = new AiTravelPhotoDeviceService();
        $device = $deviceService->verifyToken($token);
        
        if (!$device) {
            return json(['code' => 401, 'msg' => '设备Token无效或已过期']);
        }
        
        // 将设备信息注入到请求中
        $request->device = $device;
        
        return $next($request);
    }
}
