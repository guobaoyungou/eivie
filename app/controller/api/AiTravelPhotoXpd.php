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

            // 从门店配置读取加载模式，优先于前端传参
            if (!isset($mendian)) {
                $mendian = Db::name('mendian')->where('id', $mdid)->where('aid', $aid)->find();
            }
            $loadMode = $this->request->get('load_mode', '');
            if (empty($loadMode) && $mendian) {
                $loadMode = $mendian['xpd_load_mode'] ?? 'today';
            }
            if (empty($loadMode)) {
                $loadMode = 'today';
            }

            $configuredLimit = (int)($mendian['xpd_load_count'] ?? 0);
            if ($configuredLimit > 0) {
                $limit = $configuredLimit;
            }
            $limit = max(1, min($limit, 50));

            $data = $this->qrcodeService->getSelectionList($aid, $bid, $mdid, $limit, $loadMode);

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

    /**
     * 保存布局配置
     * POST /api/ai-travel-photo/layout-save
     * 参数: aid, mdid, layout(JSON字符串), bg_color, face_detect
     * 
     * @return Response
     */
    public function layout_save(): Response
    {
        try {
            $aid   = (int)$this->request->post('aid', 0);
            $mdid  = (int)$this->request->post('mdid', 0);
            $layoutJson = $this->request->post('layout', null);
            $bgColor    = $this->request->post('bg_color', null);
            $faceDetect = $this->request->post('face_detect', null);

            if (!$aid || !$mdid) {
                return json(['status' => 0, 'msg' => '缺少必要参数 aid 或 mdid']);
            }

            // face_detect 转换
            $faceDetectVal = null;
            if ($faceDetect !== null) {
                $faceDetectVal = (int)$faceDetect;
            }

            $this->qrcodeService->saveXpdLayout($aid, $mdid, $layoutJson, $bgColor, $faceDetectVal);

            return json([
                'status' => 1,
                'msg' => '保存成功'
            ]);

        } catch (\Exception $e) {
            return json([
                'status' => 0,
                'msg' => $e->getMessage()
            ]);
        }
    }
}
