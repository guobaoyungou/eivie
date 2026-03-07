<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Cache;

/**
 * 限流中间件
 * 基于Redis实现的API限流
 */
class RateLimiter
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
        $config = config('ai_travel_photo.rate_limit');
        
        // 获取限流标识（IP或用户ID）
        $identifier = $this->getIdentifier($request);
        
        // 获取当前路由
        $route = $request->rule()->getRule();
        
        // 检查是否需要限流
        $limitConfig = $this->getLimitConfig($route, $config);
        if (!$limitConfig) {
            return $next($request);
        }
        
        $maxRequests = $limitConfig['max_requests'];
        $period = $limitConfig['period'];
        
        // 限流键
        $cacheKey = "rate_limit:{$route}:{$identifier}";
        
        // 获取当前请求次数
        $requests = (int)Cache::get($cacheKey, 0);
        
        if ($requests >= $maxRequests) {
            return json([
                'code' => 429,
                'msg' => '请求过于频繁，请稍后再试',
                'data' => [
                    'retry_after' => $period
                ]
            ]);
        }
        
        // 增加请求次数
        if ($requests == 0) {
            Cache::set($cacheKey, 1, $period);
        } else {
            Cache::inc($cacheKey);
        }
        
        // 添加限流响应头
        $response = $next($request);
        $response->header([
            'X-RateLimit-Limit' => $maxRequests,
            'X-RateLimit-Remaining' => max(0, $maxRequests - $requests - 1),
            'X-RateLimit-Reset' => time() + Cache::ttl($cacheKey)
        ]);
        
        return $response;
    }

    /**
     * 获取限流标识
     *
     * @param Request $request
     * @return string
     */
    private function getIdentifier(Request $request): string
    {
        // 优先使用用户ID
        if (isset($request->uid) && $request->uid > 0) {
            return 'user:' . $request->uid;
        }
        
        // 其次使用设备ID
        if (isset($request->device['device_id'])) {
            return 'device:' . $request->device['device_id'];
        }
        
        // 最后使用IP地址
        return 'ip:' . $request->ip();
    }

    /**
     * 获取路由的限流配置
     *
     * @param string $route
     * @param array $config
     * @return array|null
     */
    private function getLimitConfig(string $route, array $config): ?array
    {
        // 检查是否有针对该路由的特殊配置
        foreach ($config['routes'] ?? [] as $pattern => $limit) {
            if (strpos($route, $pattern) !== false) {
                return $limit;
            }
        }
        
        // 使用默认配置
        return $config['default'] ?? null;
    }
}
