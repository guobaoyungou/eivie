<?php
declare(strict_types=1);

namespace app\controller\hd;

use app\BaseController;

/**
 * 大屏互动控制器基类
 */
class HdBaseController extends BaseController
{
    /**
     * 返回JSON响应
     */
    protected function success($data = [], string $msg = 'success')
    {
        return json(['code' => 0, 'msg' => $msg, 'data' => $data]);
    }

    protected function error(string $msg = 'error', int $code = 1)
    {
        return json(['code' => $code, 'msg' => $msg]);
    }

    /**
     * 获取租户上下文
     */
    protected function getAid(): int
    {
        return (int)($this->request->hd_aid ?? 0);
    }

    protected function getBid(): int
    {
        return (int)($this->request->hd_bid ?? 0);
    }

    protected function getMdid(): int
    {
        return (int)($this->request->hd_mdid ?? 0);
    }

    protected function getUserId(): int
    {
        return (int)($this->request->hd_user_id ?? 0);
    }
}
