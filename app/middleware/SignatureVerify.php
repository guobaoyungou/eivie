<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 签名验证中间件
 * 用于验证API请求的签名，防止篡改
 */
class SignatureVerify
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
        $timestamp = $request->header('X-Timestamp', '');
        $nonce = $request->header('X-Nonce', '');
        $signature = $request->header('X-Signature', '');
        
        // 检查必要参数
        if (empty($timestamp) || empty($nonce) || empty($signature)) {
            return json(['code' => 400, 'msg' => '缺少签名参数']);
        }
        
        // 检查时间戳（5分钟内有效）
        if (abs(time() - intval($timestamp)) > 300) {
            return json(['code' => 400, 'msg' => '请求已过期']);
        }
        
        // 构建签名字符串
        $params = $request->param();
        ksort($params);
        
        $signStr = '';
        foreach ($params as $key => $value) {
            if ($key != 'signature' && $value !== '') {
                $signStr .= $key . '=' . $value . '&';
            }
        }
        $signStr .= 'timestamp=' . $timestamp . '&';
        $signStr .= 'nonce=' . $nonce . '&';
        $signStr .= 'key=' . config('ai_travel_photo.security.api_key');
        
        // 计算签名
        $calcSignature = md5($signStr);
        
        // 验证签名
        if ($signature !== $calcSignature) {
            return json(['code' => 400, 'msg' => '签名验证失败']);
        }
        
        return $next($request);
    }
}
