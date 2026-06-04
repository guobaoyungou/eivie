<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoSynthesisQrService;
use think\App;
use think\facade\Db;
use think\facade\Log;
use think\facade\Session;
use think\Response;

/**
 * 合成活动H5 API控制器
 *
 * 提供合成活动微信OAuth授权、照片上传、生成进度查询、支付、下载等全流程接口
 */
class AiTravelPhotoSynthesis extends BaseController
{
    protected $qrService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->qrService = new AiTravelPhotoSynthesisQrService();
    }

    /**
     * 获取当前用户OpenID（从Session中读取）
     */
    protected function getOpenid(): string
    {
        return Session::get('synthesis_openid', '');
    }

    /**
     * 合成活动入口
     * GET /api/ai_travel_photo/synthesis/index?token=xxx
     *
     * 扫码后落地页，负责微信OAuth授权 + 获取活动信息
     */
    public function index(): Response
    {
        $token = $this->request->get('token', '');

        if (empty($token)) {
            return json(['code' => 400, 'msg' => '缺少活动参数']);
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
            return json([
                'code' => 302,
                'msg' => '需要微信授权',
                'data' => [
                    'need_auth' => true,
                    'token' => $token,
                ],
            ]);
        }

        try {
            // 获取活动信息
            $activityInfo = $this->qrService->getActivityByToken($token);

            // 通过openid反查member获取uid
            $member = Db::name('member')
                ->where('aid', $activityInfo['aid'])
                ->where('mpopenid', $openid)
                ->find();
            $uid = $member ? (int)$member['id'] : 0;

            // 获取商家信息
            $business = Db::name('business')->where('id', $activityInfo['bid'])->find();
            $businessName = $business ? ($business['name'] ?: '') : '';

            // 获取合成模板封面图
            $templateImages = [];
            if (!empty($activityInfo['template_images'])) {
                $images = json_decode($activityInfo['template_images'], true);
                $templateImages = is_array($images) ? $images : [];
            }

            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => [
                    'activity_id' => $activityInfo['activity_id'],
                    'aid' => $activityInfo['aid'],
                    'bid' => $activityInfo['bid'],
                    'activity_name' => $activityInfo['name'],
                    'price' => $activityInfo['price'],
                    'template_id' => $activityInfo['template_id'],
                    'template_name' => $activityInfo['template_name'],
                    'template_images' => $templateImages,
                    'openid' => $openid,
                    'uid' => $uid,
                    'business_name' => $businessName,
                ],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 启动微信OAuth授权
     * GET /api/ai_travel_photo/synthesis/start_oauth?token=xxx
     */
    public function start_oauth(): Response
    {
        $token = $this->request->get('token', '');

        if (empty($token)) {
            return json(['code' => 400, 'msg' => '缺少活动参数']);
        }

        try {
            $activityInfo = $this->qrService->getActivityByToken($token);
            $aid = $activityInfo['aid'];
            $bid = $activityInfo['bid'];

            $wxset = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
            $appid = $wxset['appid'] ?? '';

            if (empty($appid)) {
                return json(['code' => 500, 'msg' => '微信配置缺失']);
            }

            $callbackUrl = $this->request->domain() . '/index.php?s=/api/ai_travel_photo/synthesis/oauth_callback';
            $redirectUri = urlencode($callbackUrl);
            $state = 'synthesis_' . $token;

            $oauthUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirectUri}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";

            return redirect($oauthUrl);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 微信OAuth回调
     * GET /api/ai_travel_photo/synthesis/oauth_callback?code=xxx&state=synthesis_xxx
     */
    public function oauth_callback(): Response
    {
        $code = $this->request->get('code', '');
        $state = $this->request->get('state', '');

        if (empty($code)) {
            return json(['code' => 400, 'msg' => '授权失败，缺少code']);
        }

        // 解析token
        $token = '';
        if (strpos($state, 'synthesis_') === 0) {
            $token = substr($state, 10); // 'synthesis_' 共10个字符
        }

        if (empty($token)) {
            return json(['code' => 400, 'msg' => '授权状态无效']);
        }

        try {
            // 直接从活动表查 aid（不需要完整验证，只需要微信配置）
            $activity = Db::name('ai_travel_photo_synthesis_activity')
                ->where('qrcode_token', $token)
                ->find();

            if (!$activity) {
                // 活动不存在时直接跳回H5页面，由前端展示错误
                $redirectUrl = $this->request->domain() . '/public/synthesis/index.html?token=' . $token . '&error=notfound&_v=' . time();
                return redirect($redirectUrl);
            }

            $aid = (int)$activity['aid'];

            $wxset = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
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
            Session::set('synthesis_openid', $openid);

            // 重定向到H5页面（加版本号防止微信缓存）
            $redirectUrl = $this->request->domain() . '/public/synthesis/index.html?token=' . $token . '&_v=' . time();
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 上传照片并触发生成
     * POST /api/ai_travel_photo/synthesis/upload
     *
     * 参数: activity_id, photo (文件上传)
     */
    public function upload(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $activityId = (int)$this->request->post('activity_id', 0);

        if ($activityId <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        // 处理文件上传
        $file = $this->request->file('photo');
        if (!$file) {
            return json(['code' => 400, 'msg' => '请选择照片']);
        }

        // 验证文件类型和大小
        $ext = strtolower(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            return json(['code' => 400, 'msg' => '仅支持 JPG/PNG/WebP 格式']);
        }

        try {
            // 上传到服务器临时目录
            $uploadPath = 'upload/synthesis_photo/' . date('Ymd') . '/';
            $fullDir = app()->getRootPath() . 'public/' . $uploadPath;

            if (!is_dir($fullDir)) {
                mkdir($fullDir, 0755, true);
            }

            $fileName = $openid . '_' . time() . '.' . $ext;
            $fullLocalPath = $fullDir . $fileName;
            $file->move($fullDir, $fileName);

            // 上传到OSS
            try {
                $ossHelper = new \app\common\OssHelper();
                $ossPath = $uploadPath . $fileName;
                $photoUrl = $ossHelper->uploadFile($fullLocalPath, $ossPath);
            } catch (\Throwable $e) {
                Log::warning('合成活动照片OSS上传失败，使用本地路径', ['error' => $e->getMessage()]);
                $photoUrl = $this->request->domain() . '/' . $uploadPath . $fileName;
            }

            // 清理本地文件（OSS上传成功后清理）
            if (file_exists($fullLocalPath)) {
                @unlink($fullLocalPath);
            }

            // 获取uid
            $activity = Db::name('ai_travel_photo_synthesis_activity')->find($activityId);
            $uid = 0;
            if ($activity) {
                $member = Db::name('member')
                    ->where('aid', $activity['aid'])
                    ->where('mpopenid', $openid)
                    ->find();
                $uid = $member ? (int)$member['id'] : 0;
            }

            // 提交生成
            $result = $this->qrService->submitPhoto($activityId, $openid, $uid, $photoUrl);

            return json([
                'code' => 200,
                'msg' => '照片上传成功，开始生成',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('合成活动上传失败: ' . $e->getMessage());
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 查询生成进度
     * GET /api/ai_travel_photo/synthesis/status?user_photo_id=xxx
     */
    public function status(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $userPhotoId = (int)$this->request->get('user_photo_id', 0);
        if ($userPhotoId <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        try {
            $data = $this->qrService->getStatus($userPhotoId);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 创建支付订单
     * POST /api/ai_travel_photo/synthesis/create_order
     *
     * 参数: user_photo_id
     */
    public function create_order(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $userPhotoId = (int)$this->request->post('user_photo_id', 0);
        if ($userPhotoId <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        try {
            $data = $this->qrService->createPayOrder($userPhotoId, $openid);
            return json(['code' => 200, 'msg' => '订单创建成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 发起支付
     * POST /api/ai_travel_photo/synthesis/pay
     *
     * 参数: order_no
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
            $data = $this->qrService->createPayment($orderNo, $openid);
            return json(['code' => 200, 'msg' => '支付参数获取成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 查询支付状态
     * GET /api/ai_travel_photo/synthesis/pay_status?order_no=xxx
     */
    public function pay_status(): Response
    {
        $orderNo = $this->request->get('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '订单号不能为空']);
        }

        try {
            $data = $this->qrService->getPayStatus($orderNo);
            return json(['code' => 200, 'msg' => '查询成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取下载信息
     * GET /api/ai_travel_photo/synthesis/download?order_no=xxx
     */
    public function download(): Response
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
            $data = $this->qrService->getDownloadInfo($orderNo);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 微信支付异步回调
     * POST /api/ai_travel_photo/synthesis/notify
     */
    public function notify(): Response
    {
        $xml = file_get_contents('php://input');
        Log::info('合成活动支付回调原始XML', ['xml' => $xml]);

        // XML转数组
        libxml_disable_entity_loader(true);
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $data = $obj ? json_decode(json_encode($obj), true) : [];

        Log::info('合成活动支付回调解析数据', ['data' => $data]);

        if (empty($data) || ($data['return_code'] ?? '') !== 'SUCCESS') {
            Log::error('合成活动支付回调数据异常', ['data' => $data]);
            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[数据异常]]></return_msg></xml>');
        }

        $orderNo = $data['out_trade_no'] ?? '';
        $resultCode = $data['result_code'] ?? '';

        if ($resultCode !== 'SUCCESS' || empty($orderNo)) {
            Log::error('合成活动支付回调result_code非SUCCESS', ['order_no' => $orderNo, 'result_code' => $resultCode]);
            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[支付失败]]></return_msg></xml>');
        }

        try {
            // 获取订单以确定aid验签
            $order = \app\model\AiTravelPhotoOrder::where('order_no', $orderNo)->find();
            if (!$order) {
                Log::error('合成活动支付回调订单不存在', ['order_no' => $orderNo]);
                return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[订单不存在]]></return_msg></xml>');
            }

            $wxset = Db::name('admin_setapp_mp')->where('aid', $order->aid)->find();
            $mchKey = $wxset['wxpay_mchkey'] ?? '';

            // 验签
            if (!$this->qrService->verifyWxNotifySign($data, $mchKey)) {
                Log::error('合成活动支付回调签名验证失败', ['order_no' => $orderNo]);
                return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名验证失败]]></return_msg></xml>');
            }

            // 履约
            $result = $this->qrService->fulfillOrder($orderNo, [
                'transaction_id' => $data['transaction_id'] ?? '',
                'pay_type' => 'wechat',
            ]);
            Log::info('合成活动支付回调履约结果', ['order_no' => $orderNo, 'result' => $result]);

            return response('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
        } catch (\Exception $e) {
            Log::error('合成活动支付回调异常：' . $e->getMessage());
            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[处理异常]]></return_msg></xml>');
        }
    }

    /**
     * 收银台页面（服务端渲染）
     * GET /api/ai_travel_photo/synthesis/cashier?order_no=xxx&token=xxx
     */
    public function cashier(): Response
    {
        $orderNo = $this->request->get('order_no', '');
        $token = $this->request->get('token', '');

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
            $data = $this->qrService->createPayment($orderNo, $openid);
            $jsApiParams = json_encode($data['js_api_params'] ?? [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            $payError = $e->getMessage();
        }

        $synthesisUrl = $this->request->domain() . '/public/synthesis/index.html' . ($token ? '?token=' . $token : '');
        $downloadUrl = $this->request->domain() . '/public/synthesis/download.html?order_no=' . $orderNo;
        $payStatusUrl = $this->request->domain() . '/index.php?s=/api/ai_travel_photo/synthesis/pay_status&order_no=' . $orderNo;

        // 渲染收银台HTML
        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>支付中</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:linear-gradient(135deg,#f0f4f8 0%,#e8f4fd 100%);display:flex;align-items:center;justify-content:center;min-height:100vh;color:#333}
.card{background:rgba(255,255,255,.9);backdrop-filter:blur(10px);border-radius:16px;padding:40px 30px;text-align:center;box-shadow:0 8px 32px rgba(74,144,217,.12);max-width:320px;width:90%}
.spinner{width:40px;height:40px;border:3px solid #e8f4fd;border-top-color:#4a90d9;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 16px}
@keyframes spin{to{transform:rotate(360deg)}}
.title{font-size:18px;font-weight:600;margin-bottom:8px;color:#333}
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
    <a class="btn btn-ghost" href="{$synthesisUrl}">返回活动</a>
  </div>
</div>
<script>
var PAY_PARAMS = {$jsApiParams};
var PAY_ERROR = '{$payError}';
var DOWNLOAD_URL = '{$downloadUrl}';
var PAY_STATUS_URL = '{$payStatusUrl}';

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

invokePay();
</script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
