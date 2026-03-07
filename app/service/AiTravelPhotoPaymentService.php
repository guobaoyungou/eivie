<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoOrder;
use think\facade\Cache;
use think\facade\Db;

/**
 * 支付服务类
 * 集成微信支付V3、支付宝支付、余额支付
 */
class AiTravelPhotoPaymentService
{
    protected $config;
    protected $orderService;

    public function __construct()
    {
        $this->config = config('ai_travel_photo');
        $this->orderService = new AiTravelPhotoOrderService();
    }

    /**
     * 统一下单接口
     * 
     * @param string $orderNo 订单号
     * @param string $payType 支付类型（wechat/alipay/balance）
     * @param array $extra 额外参数
     * @return array
     * @throws \Exception
     */
    public function unifiedOrder(string $orderNo, string $payType, array $extra = []): array
    {
        // 获取订单信息
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();
        
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        
        if ($order->status != AiTravelPhotoOrder::STATUS_UNPAID) {
            throw new \Exception('订单状态异常');
        }
        
        // 检查订单是否超时
        if (time() > $order->add_time + 1800) {
            throw new \Exception('订单已超时');
        }
        
        // 根据支付类型调用不同的支付接口
        switch ($payType) {
            case 'wechat':
                return $this->wechatPay($order, $extra);
            case 'alipay':
                return $this->alipay($order, $extra);
            case 'balance':
                return $this->balancePay($order, $extra);
            default:
                throw new \Exception('不支持的支付方式');
        }
    }

    /**
     * 微信支付V3
     * 
     * @param AiTravelPhotoOrder $order 订单对象
     * @param array $extra 额外参数
     * @return array
     * @throws \Exception
     */
    private function wechatPay($order, array $extra): array
    {
        $wechatConfig = $this->config['payment']['wechat'];
        
        // 判断支付场景（JSAPI/APP/H5/Native）
        $tradeType = $extra['trade_type'] ?? 'JSAPI';
        
        $params = [
            'appid' => $wechatConfig['appid'],
            'mchid' => $wechatConfig['mchid'],
            'description' => '旅拍照片-' . $order->order_no,
            'out_trade_no' => $order->order_no,
            'notify_url' => request()->domain() . '/api/pay/wechat/notify',
            'amount' => [
                'total' => (int)($order->order_amount * 100), // 分为单位
                'currency' => 'CNY',
            ],
        ];
        
        // JSAPI需要openid
        if ($tradeType == 'JSAPI') {
            if (empty($extra['openid'])) {
                throw new \Exception('缺少openid参数');
            }
            $params['payer'] = [
                'openid' => $extra['openid'],
            ];
        }
        
        // 调用微信支付API
        $apiUrl = $wechatConfig['api_url'] . '/v3/pay/transactions/' . strtolower($tradeType);
        $result = $this->wechatApiRequest('POST', $apiUrl, $params);
        
        if (!isset($result['prepay_id'])) {
            throw new \Exception('微信支付下单失败：' . ($result['message'] ?? '未知错误'));
        }
        
        // 更新订单支付方式
        $order->pay_type = 'wechat';
        $order->save();
        
        // 根据不同场景返回不同的支付参数
        return $this->buildWechatPayParams($result, $tradeType);
    }

    /**
     * 构建微信支付参数
     * 
     * @param array $result 微信返回结果
     * @param string $tradeType 交易类型
     * @return array
     */
    private function buildWechatPayParams(array $result, string $tradeType): array
    {
        $wechatConfig = $this->config['payment']['wechat'];
        $prepayId = $result['prepay_id'];
        
        if ($tradeType == 'JSAPI') {
            $timestamp = (string)time();
            $nonceStr = $this->generateNonceStr();
            $package = 'prepay_id=' . $prepayId;
            
            // 生成签名
            $message = $wechatConfig['appid'] . "\n" . $timestamp . "\n" . $nonceStr . "\n" . $package . "\n";
            $signature = $this->wechatSign($message);
            
            return [
                'appId' => $wechatConfig['appid'],
                'timeStamp' => $timestamp,
                'nonceStr' => $nonceStr,
                'package' => $package,
                'signType' => 'RSA',
                'paySign' => $signature,
            ];
        } elseif ($tradeType == 'Native') {
            return [
                'code_url' => $result['code_url'],
            ];
        } elseif ($tradeType == 'H5') {
            return [
                'h5_url' => $result['h5_url'],
            ];
        }
        
        return $result;
    }

    /**
     * 支付宝支付
     * 
     * @param AiTravelPhotoOrder $order 订单对象
     * @param array $extra 额外参数
     * @return array
     * @throws \Exception
     */
    private function alipay($order, array $extra): array
    {
        $alipayConfig = $this->config['payment']['alipay'];
        
        $params = [
            'app_id' => $alipayConfig['app_id'],
            'method' => 'alipay.trade.page.pay',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => request()->domain() . '/api/pay/alipay/notify',
            'biz_content' => json_encode([
                'out_trade_no' => $order->order_no,
                'product_code' => 'FAST_INSTANT_TRADE_PAY',
                'total_amount' => $order->order_amount,
                'subject' => '旅拍照片-' . $order->order_no,
            ]),
        ];
        
        // 生成签名
        $params['sign'] = $this->alipaySign($params);
        
        // 构建支付链接
        $payUrl = $alipayConfig['gateway'] . '?' . http_build_query($params);
        
        // 更新订单支付方式
        $order->pay_type = 'alipay';
        $order->save();
        
        return [
            'pay_url' => $payUrl,
            'order_no' => $order->order_no,
        ];
    }

    /**
     * 余额支付
     * 
     * @param AiTravelPhotoOrder $order 订单对象
     * @param array $extra 额外参数
     * @return array
     * @throws \Exception
     */
    private function balancePay($order, array $extra): array
    {
        if ($order->uid <= 0) {
            throw new \Exception('余额支付需要登录');
        }
        
        Db::startTrans();
        try {
            // 获取用户余额（假设在business表中）
            $business = Db::name('business')->where('id', $order->bid)->find();
            
            if (!$business || $business['balance'] < $order->order_amount) {
                throw new \Exception('余额不足');
            }
            
            // 扣减余额
            Db::name('business')
                ->where('id', $order->bid)
                ->where('balance', '>=', $order->order_amount)
                ->dec('balance', $order->order_amount)
                ->update();
            
            // 更新订单状态
            $order->pay_type = 'balance';
            $order->status = AiTravelPhotoOrder::STATUS_PAID;
            $order->pay_time = time();
            $order->save();
            
            // 调用支付回调处理
            $this->orderService->paySuccessCallback($order->order_no, [
                'pay_type' => 'balance',
                'transaction_id' => 'balance_' . time(),
                'pay_amount' => $order->order_amount,
            ]);
            
            Db::commit();
            
            return [
                'status' => 'success',
                'order_no' => $order->order_no,
                'message' => '支付成功',
            ];
            
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 微信支付回调处理
     * 
     * @param array $data 回调数据
     * @return bool
     * @throws \Exception
     */
    public function wechatNotify(array $data): bool
    {
        // 验签
        if (!$this->verifyWechatSign($data)) {
            throw new \Exception('签名验证失败');
        }
        
        // 解密数据
        $resource = $data['resource'] ?? [];
        $ciphertext = $resource['ciphertext'] ?? '';
        $associatedData = $resource['associated_data'] ?? '';
        $nonce = $resource['nonce'] ?? '';
        
        $plaintext = $this->wechatDecrypt($ciphertext, $associatedData, $nonce);
        $result = json_decode($plaintext, true);
        
        $orderNo = $result['out_trade_no'] ?? '';
        $transactionId = $result['transaction_id'] ?? '';
        $tradeState = $result['trade_state'] ?? '';
        
        if ($tradeState != 'SUCCESS') {
            return false;
        }
        
        // 调用订单支付成功回调
        return $this->orderService->paySuccessCallback($orderNo, [
            'pay_type' => 'wechat',
            'transaction_id' => $transactionId,
            'pay_amount' => $result['amount']['total'] / 100,
        ]);
    }

    /**
     * 支付宝支付回调处理
     * 
     * @param array $data 回调数据
     * @return bool
     * @throws \Exception
     */
    public function alipayNotify(array $data): bool
    {
        // 验签
        if (!$this->verifyAlipaySign($data)) {
            throw new \Exception('签名验证失败');
        }
        
        $orderNo = $data['out_trade_no'] ?? '';
        $tradeNo = $data['trade_no'] ?? '';
        $tradeStatus = $data['trade_status'] ?? '';
        
        if (!in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return false;
        }
        
        // 调用订单支付成功回调
        return $this->orderService->paySuccessCallback($orderNo, [
            'pay_type' => 'alipay',
            'transaction_id' => $tradeNo,
            'pay_amount' => $data['total_amount'],
        ]);
    }

    /**
     * 查询支付状态
     * 
     * @param string $orderNo 订单号
     * @return array
     * @throws \Exception
     */
    public function queryPayStatus(string $orderNo): array
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();
        
        if (!$order) {
            throw new \Exception('订单不存在');
        }
        
        if ($order->status == AiTravelPhotoOrder::STATUS_PAID) {
            return [
                'status' => 'paid',
                'pay_time' => $order->pay_time,
            ];
        }
        
        // 如果是第三方支付，主动查询支付状态
        if ($order->pay_type == 'wechat') {
            return $this->queryWechatPayStatus($orderNo);
        } elseif ($order->pay_type == 'alipay') {
            return $this->queryAlipayStatus($orderNo);
        }
        
        return [
            'status' => 'unpaid',
            'order_status' => $order->status,
        ];
    }

    /**
     * 查询微信支付状态
     * 
     * @param string $orderNo 订单号
     * @return array
     */
    private function queryWechatPayStatus(string $orderNo): array
    {
        $wechatConfig = $this->config['payment']['wechat'];
        $apiUrl = $wechatConfig['api_url'] . '/v3/pay/transactions/out-trade-no/' . $orderNo;
        
        $params = ['mchid' => $wechatConfig['mchid']];
        $result = $this->wechatApiRequest('GET', $apiUrl . '?' . http_build_query($params));
        
        $tradeState = $result['trade_state'] ?? '';
        
        if ($tradeState == 'SUCCESS') {
            // 如果查询到已支付，但订单状态未更新，则更新订单
            $this->orderService->paySuccessCallback($orderNo, [
                'pay_type' => 'wechat',
                'transaction_id' => $result['transaction_id'],
                'pay_amount' => $result['amount']['total'] / 100,
            ]);
            
            return ['status' => 'paid'];
        }
        
        return ['status' => 'unpaid', 'trade_state' => $tradeState];
    }

    /**
     * 查询支付宝支付状态
     * 
     * @param string $orderNo 订单号
     * @return array
     */
    private function queryAlipayStatus(string $orderNo): array
    {
        $alipayConfig = $this->config['payment']['alipay'];
        
        $params = [
            'app_id' => $alipayConfig['app_id'],
            'method' => 'alipay.trade.query',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode([
                'out_trade_no' => $orderNo,
            ]),
        ];
        
        $params['sign'] = $this->alipaySign($params);
        
        $response = file_get_contents($alipayConfig['gateway'] . '?' . http_build_query($params));
        $result = json_decode($response, true);
        
        $tradeStatus = $result['alipay_trade_query_response']['trade_status'] ?? '';
        
        if (in_array($tradeStatus, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return ['status' => 'paid'];
        }
        
        return ['status' => 'unpaid', 'trade_status' => $tradeStatus];
    }

    /**
     * 微信API请求
     */
    private function wechatApiRequest(string $method, string $url, array $data = []): array
    {
        $wechatConfig = $this->config['payment']['wechat'];
        
        $timestamp = time();
        $nonceStr = $this->generateNonceStr();
        $body = $method == 'GET' ? '' : json_encode($data);
        
        // 构建签名串
        $message = "$method\n" . parse_url($url, PHP_URL_PATH) . "\n$timestamp\n$nonceStr\n$body\n";
        $signature = $this->wechatSign($message);
        
        $headers = [
            'Authorization: WECHATPAY2-SHA256-RSA2048 mchid="' . $wechatConfig['mchid'] . '",nonce_str="' . $nonceStr . '",timestamp="' . $timestamp . '",serial_no="' . $wechatConfig['serial_no'] . '",signature="' . $signature . '"',
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true) ?: [];
    }

    /**
     * 微信签名
     */
    private function wechatSign(string $message): string
    {
        $wechatConfig = $this->config['payment']['wechat'];
        $privateKey = openssl_pkey_get_private(file_get_contents($wechatConfig['private_key_path']));
        
        openssl_sign($message, $signature, $privateKey, 'sha256WithRSAEncryption');
        
        return base64_encode($signature);
    }

    /**
     * 验证微信签名
     */
    private function verifyWechatSign(array $data): bool
    {
        // 实际项目中需要实现完整的验签逻辑
        return true;
    }

    /**
     * 微信数据解密
     */
    private function wechatDecrypt(string $ciphertext, string $associatedData, string $nonce): string
    {
        $wechatConfig = $this->config['payment']['wechat'];
        $apiKey = $wechatConfig['api_v3_key'];
        
        $decrypted = openssl_decrypt(
            base64_decode($ciphertext),
            'aes-256-gcm',
            $apiKey,
            OPENSSL_RAW_DATA,
            $nonce,
            base64_decode($associatedData)
        );
        
        return $decrypted;
    }

    /**
     * 支付宝签名
     */
    private function alipaySign(array $params): string
    {
        $alipayConfig = $this->config['payment']['alipay'];
        
        ksort($params);
        $stringToBeSigned = '';
        foreach ($params as $k => $v) {
            if ($k != 'sign' && $v != '') {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
        }
        $stringToBeSigned = rtrim($stringToBeSigned, '&');
        
        $privateKey = openssl_pkey_get_private(file_get_contents($alipayConfig['private_key_path']));
        openssl_sign($stringToBeSigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        
        return base64_encode($signature);
    }

    /**
     * 验证支付宝签名
     */
    private function verifyAlipaySign(array $data): bool
    {
        // 实际项目中需要实现完整的验签逻辑
        return true;
    }

    /**
     * 生成随机字符串
     */
    private function generateNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
}
