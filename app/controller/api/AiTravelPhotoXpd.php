<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoQrcodeService;
use think\App;
use think\Response;
use think\facade\Db;

/**
 * XPD选片端API控制器
 * 提供大屏选片端数据接口
 */
class AiTravelPhotoXpd extends BaseController
{
    protected $qrcodeService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->qrcodeService = new AiTravelPhotoQrcodeService();
    }

    /**
     * 获取选片列表
     * GET /api/ai-travel-photo/selection-list
     * 
     * @return Response
     */
    public function selection_list(): Response
    {
        try {
            $aid = (int)$this->request->get('aid', 0);
            $bid = (int)$this->request->get('bid', 0);
            $mdid = (int)$this->request->get('mdid', 0);
            $limit = (int)$this->request->get('limit', 15);

            if (!$aid) {
                return json(['status' => 0, 'msg' => '缺少必要参数 aid']);
            }

            // 当 bid=0 但 mdid>0 时，从门店表反查 bid
            if (!$bid && $mdid > 0) {
                $mendian = Db::name('mendian')->where('id', $mdid)->where('aid', $aid)->find();
                if ($mendian) {
                    $bid = (int)($mendian['bid'] ?? 0);
                }
            }

            // bid 和 mdid 至少需要一个有效值来定位数据
            if (!$bid && !$mdid) {
                return json(['status' => 0, 'msg' => '缺少必要参数 bid 或 mdid']);
            }

            // 限制 limit 范围
            $limit = max(1, min($limit, 50));

            $data = $this->qrcodeService->getSelectionList($aid, $bid, $mdid, $limit);

            return json([
                'status' => 1,
                'msg' => '获取成功',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return json([
                'status' => 0,
                'msg' => $e->getMessage()
            ]);
        }
    }
}
