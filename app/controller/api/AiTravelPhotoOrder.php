<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoOrderService;
use app\service\AiTravelPhotoPaymentService;
use think\App;
use think\Response;

/**
 * 订单管理API控制器
 */
class AiTravelPhotoOrder extends BaseController
{
    protected $orderService;
    protected $paymentService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->orderService = new AiTravelPhotoOrderService();
        $this->paymentService = new AiTravelPhotoPaymentService();
    }

    /**
     * 创建订单
     * POST /api/ai_travel_photo/order/create
     * 
     * @return Response
     */
    public function create(): Response
    {
        try {
            $params = $this->request->post();
            
            // 必填参数验证
            if (empty($params['goods'])) {
                return json(['code' => 400, 'msg' => '商品信息不能为空']);
            }
            
            if (empty($params['bid'])) {
                return json(['code' => 400, 'msg' => '商家ID不能为空']);
            }
            
            // 创建订单
            $result = $this->orderService->createOrder($params);
            
            return json([
                'code' => 200,
                'msg' => '订单创建成功',
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
     * 获取订单详情
     * GET /api/ai_travel_photo/order/detail
     * 
     * @return Response
     */
    public function detail(): Response
    {
        try {
            $orderNo = $this->request->get('order_no', '');
            $uid = (int)$this->request->get('uid', 0);
            
            if (empty($orderNo)) {
                return json(['code' => 400, 'msg' => '订单号不能为空']);
            }
            
            $result = $this->orderService->getOrderDetail($orderNo, $uid);
            
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
     * 获取订单列表
     * GET /api/ai_travel_photo/order/list
     * 
     * @return Response
     */
    public function getList(): Response
    {
        try {
            $params = $this->request->get();
            
            $result = $this->orderService->getOrderList($params);
            
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
     * 统一支付接口
     * POST /api/ai_travel_photo/order/pay
     * 
     * @return Response
     */
    public function pay(): Response
    {
        try {
            $orderNo = $this->request->post('order_no', '');
            $payType = $this->request->post('pay_type', '');
            $extra = $this->request->post('extra', []);
            
            if (empty($orderNo)) {
                return json(['code' => 400, 'msg' => '订单号不能为空']);
            }
            
            if (empty($payType)) {
                return json(['code' => 400, 'msg' => '支付方式不能为空']);
            }
            
            $result = $this->paymentService->unifiedOrder($orderNo, $payType, $extra);
            
            return json([
                'code' => 200,
                'msg' => '支付参数获取成功',
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
     * 查询支付状态
     * GET /api/ai_travel_photo/order/pay_status
     * 
     * @return Response
     */
    public function payStatus(): Response
    {
        try {
            $orderNo = $this->request->get('order_no', '');
            
            if (empty($orderNo)) {
                return json(['code' => 400, 'msg' => '订单号不能为空']);
            }
            
            $result = $this->paymentService->queryPayStatus($orderNo);
            
            return json([
                'code' => 200,
                'msg' => '查询成功',
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
     * 微信支付回调
     * POST /api/ai_travel_photo/notify/wechat
     * 
     * @return Response
     */
    public function wechatNotify(): Response
    {
        try {
            $data = file_get_contents('php://input');
            $params = json_decode($data, true);
            
            $result = $this->paymentService->wechatNotify($params);
            
            if ($result) {
                return json(['code' => 'SUCCESS', 'message' => '成功']);
            }
            
            return json(['code' => 'FAIL', 'message' => '失败']);
            
        } catch (\Exception $e) {
            trace('微信支付回调异常：' . $e->getMessage(), 'error');
            return json(['code' => 'FAIL', 'message' => $e->getMessage()]);
        }
    }

    /**
     * 支付宝支付回调
     * POST /api/ai_travel_photo/notify/alipay
     * 
     * @return Response
     */
    public function alipayNotify(): Response
    {
        try {
            $params = $this->request->post();
            
            $result = $this->paymentService->alipayNotify($params);
            
            if ($result) {
                return 'success';
            }
            
            return 'fail';
            
        } catch (\Exception $e) {
            trace('支付宝回调异常：' . $e->getMessage(), 'error');
            return 'fail';
        }
    }

    /**
     * 申请退款
     * POST /api/ai_travel_photo/order/refund
     * 
     * @return Response
     */
    public function refund(): Response
    {
        try {
            $orderNo = $this->request->post('order_no', '');
            $reason = $this->request->post('reason', '');
            $uid = (int)$this->request->post('uid', 0);
            
            if (empty($orderNo)) {
                return json(['code' => 400, 'msg' => '订单号不能为空']);
            }
            
            $result = $this->orderService->applyRefund($orderNo, $reason, $uid);
            
            return json([
                'code' => 200,
                'msg' => '退款申请成功',
                'data' => ['result' => $result]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => $e->getMessage()
            ]);
        }
    }
}
