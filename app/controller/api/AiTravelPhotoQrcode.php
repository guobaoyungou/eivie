<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoQrcodeService;
use think\App;
use think\Response;

/**
 * 二维码API控制器
 */
class AiTravelPhotoQrcode extends BaseController
{
    protected $qrcodeService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->qrcodeService = new AiTravelPhotoQrcodeService();
    }

    /**
     * 扫码查看详情
     * GET /api/ai_travel_photo/qrcode/detail
     * 
     * @return Response
     */
    public function detail(): Response
    {
        try {
            $qrcodeStr = $this->request->get('qrcode', '');
            $uid = (int)$this->request->get('uid', 0);
            
            if (empty($qrcodeStr)) {
                return json(['code' => 400, 'msg' => '二维码参数不能为空']);
            }
            
            $result = $this->qrcodeService->getQrcodeDetail($qrcodeStr, $uid);
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }

    /**
     * 生成二维码
     * POST /api/ai_travel_photo/qrcode/generate
     * 
     * @return Response
     */
    public function generate(): Response
    {
        try {
            $portraitId = (int)$this->request->post('portrait_id', 0);
            
            if ($portraitId <= 0) {
                return json(['code' => 400, 'msg' => '人像ID不能为空']);
            }
            
            $result = $this->qrcodeService->generateQrcode($portraitId);
            
            return json([
                'code' => 200,
                'msg' => '生成成功',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }
}
