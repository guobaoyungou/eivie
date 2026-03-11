<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Db;
use app\service\StorageService;

/**
 * 存储空间预检中间件
 * 在上传图片和创建生成订单之前自动检查用户存储空间
 */
class StorageQuotaCheck
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
        try {
            // 获取用户身份
            $mid = $this->getMid($request);
            if (!$mid) {
                // 未登录的用户跳过预检（后续业务逻辑会拦截）
                return $next($request);
            }

            $aid = $this->getAid($request);
            if (!$aid) $aid = 1;

            $storageService = new StorageService();

            // 估算所需空间
            $requiredBytes = $this->estimateRequiredBytes($request);

            $check = $storageService->checkQuota($aid, $mid, $requiredBytes);

            if (!$check['allowed']) {
                return $this->denyResponse($request, $check);
            }

            // 将存储信息注入请求，供后续使用
            $request->storageQuotaCheck = $check;

        } catch (\Exception $e) {
            // 预检失败不阻断请求，记录日志
            \think\facade\Log::warning('StorageQuotaCheck middleware error: ' . $e->getMessage());
        }

        return $next($request);
    }

    /**
     * 获取会员ID
     * @param Request $request
     * @return int
     */
    protected function getMid(Request $request): int
    {
        // ApiAivideo 控制器
        $mid = input('param.mid/d', 0);
        if ($mid > 0) return $mid;

        // Index 控制器（session）
        $sessionId = \think\facade\Session::getId();
        if ($sessionId) {
            $mid = intval(cache($sessionId . '_mid'));
            if ($mid > 0) return $mid;
        }

        return 0;
    }

    /**
     * 获取账户ID
     * @param Request $request
     * @return int
     */
    protected function getAid(Request $request): int
    {
        $aid = input('param.aid/d', 0);
        if ($aid > 0) return $aid;
        return 1; // 默认aid
    }

    /**
     * 估算所需空间
     * @param Request $request
     * @return int
     */
    protected function estimateRequiredBytes(Request $request): int
    {
        $action = strtolower($request->action());

        // 上传场景：根据 Content-Length 估算
        if (strpos($action, 'upload') !== false) {
            $contentLength = $request->header('Content-Length');
            return intval($contentLength ?: 0);
        }

        // 生成场景：根据生成类型估算平均输出大小
        if (strpos($action, 'generation_order') !== false || strpos($action, 'create_generation') !== false) {
            $generationType = input('post.generation_type/d', 1);
            $quantity = input('post.quantity/d', 1);
            if ($quantity < 1) $quantity = 1;

            if ($generationType == 2) {
                // 视频：平均每个 50MB
                return $quantity * 50 * 1024 * 1024;
            } else {
                // 图片：平均每张 5MB
                return $quantity * 5 * 1024 * 1024;
            }
        }

        return 0;
    }

    /**
     * 返回拒绝响应
     * @param Request $request
     * @param array $check
     * @return Response
     */
    protected function denyResponse(Request $request, array $check)
    {
        $data = [
            'status' => 0,
            'msg' => $check['upgrade_tip'] ?: '云端存储空间不足',
            'data' => [
                'storage_full' => true,
                'warning_level' => $check['warning_level'],
                'remaining_bytes' => $check['remaining_bytes'],
                'used_percent' => $check['used_percent'],
            ]
        ];

        // 判断是API调用还是H5调用
        if ($request->isAjax() || $request->header('X-Requested-With')) {
            return json($data);
        }

        // 对于非AJAX请求，也返回JSON
        return json($data);
    }
}
