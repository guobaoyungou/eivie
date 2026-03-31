<?php
declare(strict_types=1);

namespace app\middleware\hd;

use Closure;
use think\Request;
use think\Response;

/**
 * CORS 跨域中间件（大屏互动专用）
 */
class HdCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin', '*');

        $response = $next($request);

        $response->header([
            'Access-Control-Allow-Origin'      => $origin,
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers'     => 'Content-Type, Hd-Token, X-Requested-With',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '3600',
        ]);

        // OPTIONS 预检请求直接返回
        if ($request->isOptions()) {
            $response->code(204);
        }

        return $response;
    }
}
