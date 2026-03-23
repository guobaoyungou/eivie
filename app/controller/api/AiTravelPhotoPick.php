<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoPickService;
use think\App;
use think\facade\Session;
use think\Response;

/**
 * 选片H5页面API控制器
 * 
 * 提供扫码选片、套餐推荐、下单支付、下载等全流程接口
 */
class AiTravelPhotoPick extends BaseController
{
    protected $pickService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->pickService = new AiTravelPhotoPickService();
    }

    /**
     * 获取当前用户OpenID（从Session中读取）
     */
    protected function getOpenid(): string
    {
        return Session::get('pick_openid', '');
    }

    /**
     * 选片页入口
     * GET /api/ai-travel-photo/pick/index?qr={qrcode标识}
     * 
     * 扫码后落地页，负责微信OAuth授权+定位人像
     */
    public function index(): Response
    {
        $qrCode = $this->request->get('qr', '');

        if (empty($qrCode)) {
            return json(['code' => 400, 'msg' => '缺少二维码参数']);
        }

        // 判断是否在微信浏览器中
        $userAgent = $this->request->header('user-agent', '');
        $isWechat = strpos($userAgent, 'MicroMessenger') !== false;

        if (!$isWechat) {
            return json(['code' => 403, 'msg' => '请使用微信扫码打开']);
        }

        // 检查是否已有OpenID
        $openid = $this->getOpenid();

        if (empty($openid)) {
            // 需要微信OAuth授权
            return json([
                'code' => 302,
                'msg' => '需要微信授权',
                'data' => [
                    'need_auth' => true,
                    'qr' => $qrCode,
                ],
            ]);
        }

        try {
            // 通过二维码获取人像信息
            $portraitInfo = $this->pickService->getPortraitByQrcode($qrCode);

            // 记录扫码
            $this->pickService->recordScan($portraitInfo['qrcode_id'], $openid);

            // 通过openid反查member获取uid（公众号关注后自动注册的会员）
            $member = \think\facade\Db::name('member')
                ->where('aid', $portraitInfo['aid'])
                ->where('mpopenid', $openid)
                ->find();
            $uid = $member ? (int)$member['id'] : 0;

            // 查询商家名称
            $businessName = '';
            $faceWatermarkEnabled = 0;
            if ($portraitInfo['bid'] > 0) {
                $businessRow = \think\facade\Db::name('business')
                    ->where('id', $portraitInfo['bid'])
                    ->field('name, ai_pick_face_watermark_enabled')
                    ->find();
                $businessName = $businessRow ? ($businessRow['name'] ?: '') : '';
                $faceWatermarkEnabled = $businessRow ? intval($businessRow['ai_pick_face_watermark_enabled'] ?? 0) : 0;
            }

            // 查询公众号昵称（用于水印文字）
            $mpNickname = '';
            if ($faceWatermarkEnabled && $portraitInfo['aid'] > 0) {
                $mpNickname = \think\facade\Db::name('admin_setapp_mp')
                    ->where('aid', $portraitInfo['aid'])
                    ->value('nickname') ?: '';
            }

            // 查询门店名称
            $storeName = '';
            $mdid = $portraitInfo['mdid'] ?? 0;
            if ($mdid > 0) {
                $storeName = \think\facade\Db::name('mendian')
                    ->where('id', $mdid)
                    ->value('name') ?: '';
            }

            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => [
                    'portrait_id' => $portraitInfo['portrait_id'],
                    'aid' => $portraitInfo['aid'],
                    'bid' => $portraitInfo['bid'],
                    'qrcode_id' => $portraitInfo['qrcode_id'],
                    'openid' => $openid,
                    'uid' => $uid,
                    'business_name' => $businessName,
                    'store_name' => $storeName,
                    'face_watermark_enabled' => $faceWatermarkEnabled,
                    'mp_nickname' => $mpNickname,
                ],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 启动微信OAuth授权（前端重定向用）
     * GET /api/ai-travel-photo/pick/start_oauth?qr=xxx
     */
    public function start_oauth(): Response
    {
        $qr = $this->request->get('qr', '');
        if (empty($qr)) {
            return json(['code' => 400, 'msg' => '缺少二维码参数']);
        }

        try {
            $portraitInfo = $this->pickService->getPortraitByQrcode($qr);
            $aid = $portraitInfo['aid'];

            $wxset = \think\facade\Db::name('admin_setapp_mp')->where('aid', $aid)->find();
            $appid = $wxset['appid'] ?? '';

            if (empty($appid)) {
                return json(['code' => 500, 'msg' => '微信配置缺失']);
            }

            $callbackUrl = $this->request->domain() . '/index.php?s=/api/ai_travel_photo/pick/oauth_callback';
            $redirectUri = urlencode($callbackUrl);
            $state = 'qr_' . $qr;

            $oauthUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirectUri}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";

            return redirect($oauthUrl);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 微信OAuth回调
     * GET /api/ai-travel-photo/pick/oauth_callback?code=xxx&state=qr_xxx
     */
    public function oauth_callback(): Response
    {
        $code = $this->request->get('code', '');
        $state = $this->request->get('state', '');

        if (empty($code)) {
            return json(['code' => 400, 'msg' => '授权失败，缺少code']);
        }

        // 从state中恢复qr码
        $qrCode = '';
        if (strpos($state, 'qr_') === 0) {
            $qrCode = substr($state, 3);
        }

        try {
            // 获取人像信息以确定aid，从而获取正确的微信配置
            $portraitInfo = $this->pickService->getPortraitByQrcode($qrCode);
            $aid = $portraitInfo['aid'];

            // 获取微信配置
            $wxset = \think\facade\Db::name('admin_setapp_mp')->where('aid', $aid)->find();
            $appid = $wxset['appid'] ?? '';
            $appsecret = $wxset['appsecret'] ?? '';

            // 用code换取openid
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecret}&code={$code}&grant_type=authorization_code";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            $openid = $result['openid'] ?? '';

            if (empty($openid)) {
                return json(['code' => 500, 'msg' => '获取OpenID失败']);
            }

            // 存入Session
            Session::set('pick_openid', $openid);

            // 重定向回选片页面
            $pickUrl = $this->request->domain() . '/public/pick/index.html?qr=' . $qrCode;
            return redirect($pickUrl);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取成片列表
     * GET /api/ai-travel-photo/pick/results?portrait_id=xxx
     */
    public function results(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $portraitId = (int)$this->request->get('portrait_id', 0);
        $bid = (int)$this->request->get('bid', 0);
        $aid = (int)$this->request->get('aid', 0);
        if ($portraitId <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        try {
            $data = $this->pickService->getResultList($portraitId, $bid, $aid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取套餐列表
     * GET /api/ai-travel-photo/pick/packages?bid=xxx
     */
    public function packages(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $bid = (int)$this->request->get('bid', 0);
        if ($bid <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        try {
            $data = $this->pickService->getPackageList($bid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 套餐推荐
     * POST /api/ai-travel-photo/pick/recommend
     */
    public function recommend(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $selectedCount = (int)$this->request->post('selected_count', 0);
        $bid = (int)$this->request->post('bid', 0);

        if ($selectedCount <= 0) {
            return json(['code' => 400, 'msg' => '请至少选择一张']);
        }
        if ($bid <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        try {
            $data = $this->pickService->recommendPackage($selectedCount, $bid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 创建选片订单
     * POST /api/ai-travel-photo/pick/order
     */
    public function order(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $params = $this->request->post();
        $params['openid'] = $openid;

        // 通过openid反查member获取uid
        $aid = (int)($params['aid'] ?? 0);
        if ($aid > 0) {
            $member = \think\facade\Db::name('member')
                ->where('aid', $aid)
                ->where('mpopenid', $openid)
                ->find();
            if ($member) {
                $params['uid'] = (int)$member['id'];
            }
        }

        // 参数验证
        if (empty($params['portrait_id'])) {
            return json(['code' => 400, 'msg' => '人像ID不能为空']);
        }
        if (empty($params['result_ids']) || !is_array($params['result_ids'])) {
            return json(['code' => 400, 'msg' => '请选择成片']);
        }

        try {
            $data = $this->pickService->createPickOrder($params);
            return json(['code' => 200, 'msg' => '订单创建成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 发起支付
     * POST /api/ai-travel-photo/pick/pay
     */
    public function pay(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $orderNo = $this->request->post('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '订单号不能为空']);
        }

        try {
            $data = $this->pickService->createPayment($orderNo, $openid);
            return json(['code' => 200, 'msg' => '支付参数获取成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 查询支付状态
     * GET /api/ai-travel-photo/pick/pay_status?order_no=xxx
     * 
     * 注意：此接口不要求session鉴权，因为从/h5/cashier.html跳转后session可能丢失
     * order_no本身具有足够的唯一性和不可猜测性
     */
    public function pay_status(): Response
    {
        $orderNo = $this->request->get('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '订单号不能为空']);
        }

        try {
            $data = $this->pickService->getPayStatus($orderNo);
            return json(['code' => 200, 'msg' => '查询成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取下载列表
     * GET /api/ai-travel-photo/pick/downloads?order_no=xxx
     */
    public function downloads(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $orderNo = $this->request->get('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '订单号不能为空']);
        }

        try {
            $data = $this->pickService->getDownloadList($orderNo, $openid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 记录下载
     * POST /api/ai-travel-photo/pick/record_download
     */
    public function record_download(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $goodsId = (int)$this->request->post('goods_id', 0);
        if ($goodsId <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $result = $this->pickService->recordDownload($goodsId, $openid);
        return json(['code' => 200, 'msg' => '成功', 'data' => ['result' => $result]]);
    }

    /**
     * 收银台页面（服务端渲染）
     * GET /api/ai-travel-photo/pick/cashier?order_no=xxx&qr=xxx
     *
     * 此页面URL在 /index.php 路由下，属于已注册的微信JSAPI支付授权目录
     * 解决 /public/pick/index.html 不在授权目录中导致的"URL未注册"问题
     */
    public function cashier(): Response
    {
        $orderNo = $this->request->get('order_no', '');
        $qr = $this->request->get('qr', '');

        if (empty($orderNo)) {
            return response('参数错误', 400);
        }

        $openid = $this->getOpenid();
        if (empty($openid)) {
            return response('未授权，请重新扫码', 401);
        }

        // 获取支付参数
        $payError = '';
        $jsApiParams = '{}';
        try {
            $data = $this->pickService->createPayment($orderNo, $openid);
            $jsApiParams = json_encode($data['js_api_params'] ?? [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            $payError = $e->getMessage();
        }

        $pickUrl = $this->request->domain() . '/public/pick/index.html' . ($qr ? '?qr=' . $qr : '');
        $downloadUrl = $this->request->domain() . '/public/pick/download.html?order_no=' . $orderNo;
        $payStatusUrl = $this->request->domain() . '/index.php?s=/api/ai_travel_photo/pick/pay_status&order_no=' . $orderNo;

        // 渲染自包含的收银台HTML
        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>支付中</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#333}
.card{background:#fff;border-radius:16px;padding:40px 30px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:320px;width:90%}
.spinner{width:40px;height:40px;border:3px solid #eee;border-top-color:#07c160;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 16px}
@keyframes spin{to{transform:rotate(360deg)}}
.title{font-size:18px;font-weight:600;margin-bottom:8px}
.desc{font-size:14px;color:#999;margin-bottom:20px}
.btn{display:inline-block;background:#07c160;color:#fff;border:none;padding:12px 32px;border-radius:24px;font-size:15px;cursor:pointer;text-decoration:none}
.btn:active{opacity:.8}
.btn-ghost{background:#fff;color:#666;border:1px solid #ddd}
.error{color:#e64340}
.gap{height:12px}
</style>
</head>
<body>
<div class="card" id="payCard">
  <div class="spinner" id="spinner"></div>
  <div class="title" id="title">正在发起支付...</div>
  <div class="desc" id="desc">请在弹出的微信支付窗口中完成支付</div>
  <div style="display:none" id="btnArea">
    <a class="btn" id="retryBtn" href="javascript:void(0)" onclick="retryPay()">重新支付</a>
    <div class="gap"></div>
    <a class="btn btn-ghost" href="{$pickUrl}">返回选片</a>
  </div>
</div>
<script>
var PAY_PARAMS = {$jsApiParams};
var PAY_ERROR = '{$payError}';
var DOWNLOAD_URL = '{$downloadUrl}';
var PAY_STATUS_URL = '{$payStatusUrl}';
var PICK_URL = '{$pickUrl}';

function invokePay() {
  if (PAY_ERROR) {
    showError(PAY_ERROR);
    return;
  }
  if (typeof WeixinJSBridge === 'undefined') {
    if (document.addEventListener) {
      document.addEventListener('WeixinJSBridgeReady', function(){ invokePay(); }, false);
    }
    return;
  }
  WeixinJSBridge.invoke('getBrandWCPayRequest', PAY_PARAMS, function(res) {
    if (res.err_msg === 'get_brand_wcpay_request:ok') {
      document.getElementById('spinner').style.display = 'block';
      document.getElementById('title').textContent = '支付成功';
      document.getElementById('title').className = '';
      document.getElementById('desc').textContent = '正在跳转...';
      document.getElementById('btnArea').style.display = 'none';
      pollAndRedirect();
    } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
      showCancelled();
    } else {
      showError('支付失败，请重试');
    }
  });
}

function showError(msg) {
  document.getElementById('spinner').style.display = 'none';
  document.getElementById('title').textContent = msg;
  document.getElementById('title').className = 'title error';
  document.getElementById('desc').textContent = '';
  document.getElementById('btnArea').style.display = 'block';
}

function showCancelled() {
  document.getElementById('spinner').style.display = 'none';
  document.getElementById('title').textContent = '支付已取消';
  document.getElementById('title').className = 'title';
  document.getElementById('desc').textContent = '您可以重新发起支付';
  document.getElementById('btnArea').style.display = 'block';
}

function retryPay() {
  document.getElementById('spinner').style.display = 'block';
  document.getElementById('title').textContent = '正在发起支付...';
  document.getElementById('title').className = 'title';
  document.getElementById('desc').textContent = '请在弹出的微信支付窗口中完成支付';
  document.getElementById('btnArea').style.display = 'none';
  setTimeout(function(){ invokePay(); }, 300);
}

function pollAndRedirect() {
  var attempts = 0;
  var timer = setInterval(function() {
    attempts++;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', PAY_STATUS_URL, true);
    xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.onload = function() {
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.code === 200 && res.data && res.data.status === 'paid') {
          clearInterval(timer);
          window.location.href = DOWNLOAD_URL;
        }
      } catch(e) {}
    };
    xhr.send();
    if (attempts >= 15) {
      clearInterval(timer);
      window.location.href = DOWNLOAD_URL;
    }
  }, 2000);
}

// 页面加载后自动发起支付
invokePay();
</script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 我的订单列表
     * GET /api/ai-travel-photo/pick/my_orders
     * 根据当前session中的openid查询已支付订单
     */
    public function my_orders(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        try {
            $list = $this->pickService->getMyOrders($openid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $list]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取公众号信息（关注引导）
     * GET /api/ai-travel-photo/pick/mp_info?order_no=xxx
     */
    public function mp_info(): Response
    {
        $orderNo = $this->request->get('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '缺少订单号']);
        }
        try {
            $order = \app\model\AiTravelPhotoOrder::where('order_no', $orderNo)->find();
            if (!$order) {
                return json(['code' => 404, 'msg' => '订单不存在']);
            }
            $data = $this->pickService->getMpInfo($order->aid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 一键生成视频（FFmpeg幻灯片）
     * POST /api/ai-travel-photo/pick/gen_video
     */
    public function gen_video(): Response
    {
        $orderNo = $this->request->post('order_no', '') ?: $this->request->get('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '缺少订单号']);
        }
        try {
            $data = $this->pickService->generateSlideshow($orderNo);
            return json(['code' => 200, 'msg' => '视频生成成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 微信支付异步回调
     * POST /api/ai-travel-photo/pick/notify
     */
    public function notify(): Response
    {
        $xml = file_get_contents('php://input');
        \think\facade\Log::info('选片支付回调原始XML', ['xml' => $xml]);

        $data = $this->xmlToArray($xml);
        \think\facade\Log::info('选片支付回调解析数据', ['data' => $data]);

        if (empty($data) || ($data['return_code'] ?? '') !== 'SUCCESS') {
            \think\facade\Log::error('选片支付回调数据异常', ['data' => $data]);
            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[数据异常]]></return_msg></xml>');
        }

        $orderNo = $data['out_trade_no'] ?? '';
        $resultCode = $data['result_code'] ?? '';

        if ($resultCode !== 'SUCCESS' || empty($orderNo)) {
            \think\facade\Log::error('选片支付回调result_code非SUCCESS', ['order_no' => $orderNo, 'result_code' => $resultCode]);
            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[支付失败]]></return_msg></xml>');
        }

        try {
            // 获取订单以确定aid，从而验签
            $order = \app\model\AiTravelPhotoOrder::where('order_no', $orderNo)->find();
            if (!$order) {
                \think\facade\Log::error('选片支付回调订单不存在', ['order_no' => $orderNo]);
                return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[订单不存在]]></return_msg></xml>');
            }

            $wxset = \think\facade\Db::name('admin_setapp_mp')->where('aid', $order->aid)->find();
            $mchKey = $wxset['wxpay_mchkey'] ?? '';

            // 验签
            if (!$this->pickService->verifyWxNotifySign($data, $mchKey)) {
                \think\facade\Log::error('选片支付回调签名验证失败', ['order_no' => $orderNo]);
                return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名验证失败]]></return_msg></xml>');
            }

            // 履约
            $result = $this->pickService->paySuccessfulfilment($orderNo, [
                'transaction_id' => $data['transaction_id'] ?? '',
                'pay_type' => 'wechat',
            ]);
            \think\facade\Log::info('选片支付回调履约结果', ['order_no' => $orderNo, 'result' => $result]);

            return response('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
        } catch (\Exception $e) {
            \think\facade\Log::error('选片支付回调异常：' . $e->getMessage() . ' trace:' . $e->getTraceAsString());
            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[处理异常]]></return_msg></xml>');
        }
    }

    /**
     * XML转数组
     * 增强版：空XML元素转为空字符串而非空数组
     */
    protected function xmlToArray($xml): array
    {
        if (!$xml) return [];
        libxml_disable_entity_loader(true);
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($obj === false) return [];
        $arr = json_decode(json_encode($obj), true) ?: [];
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = '';
            }
        }
        return $arr;
    }
}
