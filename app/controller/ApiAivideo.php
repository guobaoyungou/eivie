<?php
/**
 * AI旅拍游客端API控制器
 * 提供游客扫码、选片、支付等功能
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

namespace app\controller;

use app\controller\ApiCommon;
use think\facade\Db;
use think\facade\Log;
use app\common\Aivideo;
use app\common\Member;
use app\common\Wechat;

class ApiAivideo extends ApiCommon
{
    public $aid;
    public $mid;

    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();

        $aid = input('param.aid/d');
        if (!$aid) {
            echo jsonEncode(['status' => 0, 'msg' => '参数错误']);
            exit;
        }

        $this->aid = $aid;

        // 获取游客ID
        $this->mid = input('param.mid/d');
    }

    /**
     * 微信授权
     * @return string
     */
    public function wechat_auth()
    {
        $code = input('param.code');
        if (!$code) {
            return jsonEncode(['status' => 0, 'msg' => '授权码不能为空']);
        }

        // 调用微信OAuth获取用户信息
        $wechat = new Wechat($this->aid);
        $userInfo = $wechat->getOauthUserInfo($code);

        if (!$userInfo) {
            return jsonEncode(['status' => 0, 'msg' => '授权失败']);
        }

        // 查找或创建会员
        $member = Db::name('member')
            ->where('aid', $this->aid)
            ->where('wxopenid', $userInfo['openid'])
            ->find();

        if (!$member) {
            // 创建新会员
            $memberData = [
                'aid' => $this->aid,
                'wxopenid' => $userInfo['openid'],
                'nickname' => $userInfo['nickname'] ?? '',
                'headimg' => $userInfo['headimgurl'] ?? '',
                'platform' => 'mp',
                'createtime' => time(),
            ];

            $memberModel = new \app\model\Member();
            $mid = $memberModel->add($this->aid, $memberData);
        } else {
            $mid = $member['id'];
        }

        // 生成访问令牌
        $token = md5($mid . time() . rand(1000, 9999));

        // 返回结果
        return jsonEncode([
            'status' => 1,
            'msg' => '授权成功',
            'data' => [
                'mid' => $mid,
                'openid' => $userInfo['openid'],
                'access_token' => $token,
                'nickname' => $userInfo['nickname'] ?? '',
                'headimg' => $userInfo['headimgurl'] ?? '',
            ]
        ]);
    }

    /**
     * 获取作品列表
     * @return string
     */
    public function work_list()
    {
        $mid = input('param.mid/d');
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        if (!$mid) {
            return jsonEncode(['status' => 0, 'msg' => '会员ID不能为空']);
        }

        // 查询作品列表
        $where = [
            ['aid', '=', $this->aid],
            ['mid', '=', $mid],
            ['status', '=', 1],
        ];

        $list = Db::name('aivideo_work')
            ->where($where)
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 查询总数
        $total = Db::name('aivideo_work')
            ->where($where)
            ->count();

        // 检查是否已支付
        foreach ($list as &$item) {
            $order = Db::name('aivideo_order')
                ->where('work_id', $item['id'])
                ->where('mid', $mid)
                ->where('pay_status', 1)
                ->find();

            $item['is_paid'] = $order ? true : false;
        }

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 获取作品详情
     * @return string
     */
    public function work_detail()
    {
        $workId = input('param.id/d');
        $mid = input('param.mid/d');

        if (!$workId) {
            return jsonEncode(['status' => 0, 'msg' => '作品ID不能为空']);
        }

        // 查询作品详情
        $work = Db::name('aivideo_work')
            ->where('id', $workId)
            ->where('aid', $this->aid)
            ->find();

        if (!$work) {
            return jsonEncode(['status' => 0, 'msg' => '作品不存在']);
        }

        // 记录浏览记录
        if ($mid) {
            Db::name('aivideo_selection')->insert([
                'aid' => $this->aid,
                'bid' => $work['bid'],
                'mid' => $mid,
                'work_id' => $workId,
                'selection_type' => 'select',
                'device_info' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => request()->ip(),
                'createtime' => time(),
            ]);
        }

        // 检查是否已支付
        $order = Db::name('aivideo_order')
            ->where('work_id', $workId)
            ->where('mid', $mid)
            ->where('pay_status', 1)
            ->find();

        $work['is_paid'] = $order ? true : false;

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $work
        ]);
    }

    /**
     * 创建订单
     * @return string
     */
    public function create_order()
    {
        $mid = input('param.mid/d');
        $workIds = input('param.work_ids');

        if (!$mid) {
            return jsonEncode(['status' => 0, 'msg' => '会员ID不能为空']);
        }

        if (!$workIds) {
            return jsonEncode(['status' => 0, 'msg' => '请选择作品']);
        }

        $workIdArray = explode(',', $workIds);

        // 查询作品信息
        $works = Db::name('aivideo_work')
            ->whereIn('id', $workIdArray)
            ->where('aid', $this->aid)
            ->select()
            ->toArray();

        if (count($works) != count($workIdArray)) {
            return jsonEncode(['status' => 0, 'msg' => '部分作品不存在']);
        }

        // 计算总价
        $totalPrice = 0;
        foreach ($works as $work) {
            $totalPrice += $work['price'];
        }

        // 生成订单号
        $ordernum = 'AV' . date('YmdHis') . rand(1000, 9999);

        // 创建订单
        Db::startTrans();
        try {
            $orderData = [
                'aid' => $this->aid,
                'bid' => $works[0]['bid'],
                'mid' => $mid,
                'ordernum' => $ordernum,
                'work_id' => $workIdArray[0],
                'work_ids' => $workIds,
                'work_count' => count($works),
                'total_price' => $totalPrice,
                'pay_price' => $totalPrice,
                'pay_status' => 0,
                'status' => 1,
                'createtime' => time(),
                'updatetime' => time(),
            ];

            $orderId = Db::name('aivideo_order')->insertGetId($orderData);

            Db::commit();

            // 返回订单信息
            return jsonEncode([
                'status' => 1,
                'msg' => '创建成功',
                'data' => [
                    'order_id' => $orderId,
                    'ordernum' => $ordernum,
                    'total_price' => $totalPrice,
                    'work_list' => $works,
                ]
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创建订单失败: ' . $e->getMessage());
            return jsonEncode(['status' => 0, 'msg' => '创建订单失败']);
        }
    }

    /**
     * 支付回调
     * @return string
     */
    public function pay_callback()
    {
        $ordernum = input('param.ordernum');
        $payType = input('param.pay_type');
        $transactionId = input('param.transaction_id');

        if (!$ordernum || !$payType) {
            return jsonEncode(['status' => 0, 'msg' => '参数错误']);
        }

        // 查询订单
        $order = Db::name('aivideo_order')
            ->where('ordernum', $ordernum)
            ->where('pay_status', 0)
            ->find();

        if (!$order) {
            return jsonEncode(['status' => 0, 'msg' => '订单不存在或已支付']);
        }

        Db::startTrans();
        try {
            // 更新订单状态
            Db::name('aivideo_order')->where('id', $order['id'])->update([
                'pay_type' => $payType,
                'pay_status' => 1,
                'pay_time' => time(),
                'transaction_id' => $transactionId,
                'updatetime' => time(),
            ]);

            // 更新作品为已支付
            $workIds = explode(',', $order['work_ids']);
            Db::name('aivideo_work')
                ->whereIn('id', $workIds)
                ->where('mid', $order['mid'])
                ->update([
                    'mid' => $order['mid'],
                    'is_free' => 0,
                ]);

            // 发送通知
            $this->sendPayNotification($order);

            Db::commit();

            return jsonEncode(['status' => 1, 'msg' => '支付成功']);
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('支付回调失败: ' . $e->getMessage());
            return jsonEncode(['status' => 0, 'msg' => '处理失败']);
        }
    }

    /**
     * 发送支付成功通知
     * @param array $order 订单信息
     */
    private function sendPayNotification($order)
    {
        // 查询会员信息
        $member = Db::name('member')->where('id', $order['mid'])->find();
        if (!$member) {
            return;
        }

        // 发送微信模板消息
        $wechat = new Wechat($this->aid);
        $templateData = [
            'first' => '您的AI旅拍作品已支付成功',
            'keyword1' => $order['ordernum'],
            'keyword2' => $order['total_price'] . '元',
            'keyword3' => $order['work_count'] . '个作品',
            'remark' => '作品已自动保存到您的相册',
        ];

        $wechat->sendTemplateMessage($member['wxopenid'], '支付成功通知', $templateData);
    }

    /**
     * 发起支付
     * @return string
     */
    public function pay()
    {
        $mid = input('param.mid/d');
        $ordernum = input('param.ordernum');
        $payType = input('param.pay_type');

        if (!$mid || !$ordernum || !$payType) {
            return jsonEncode(['status' => 0, 'msg' => '参数错误']);
        }

        // 查询订单信息
        $order = Db::name('aivideo_order')
            ->where('ordernum', $ordernum)
            ->where('mid', $mid)
            ->where('pay_status', 0)
            ->find();

        if (!$order) {
            return jsonEncode(['status' => 0, 'msg' => '订单不存在或已支付']);
        }

        // 根据支付方式发起支付
        $payResult = [];

        switch ($payType) {
            case 'weixin':
                // 微信支付
                $payResult = $this->wechatPay($order);
                break;
            case 'alipay':
                // 支付宝支付
                $payResult = $this->alipayPay($order);
                break;
            case 'balance':
                // 余额支付
                $payResult = $this->balancePay($order);
                break;
            default:
                return jsonEncode(['status' => 0, 'msg' => '不支持的支付方式']);
        }

        if ($payResult['success']) {
            return jsonEncode([
                'status' => 1,
                'msg' => '支付发起成功',
                'data' => $payResult['data']
            ]);
        } else {
            return jsonEncode([
                'status' => 0,
                'msg' => $payResult['message'] ?? '支付发起失败'
            ]);
        }
    }

    /**
     * 微信支付
     * @param array $order 订单信息
     * @return array
     */
    private function wechatPay($order)
    {
        try {
            // 获取微信支付配置
            $wxpayConfig = Db::name('admin_set')
                ->where('aid', $this->aid)
                ->find();

            if (!$wxpayConfig || !$wxpayConfig['wxpay']) {
                return ['success' => false, 'message' => '微信支付未配置'];
            }

            // 构建支付参数
            $params = [
                'appid' => $wxpayConfig['wxpay']['appid'],
                'mch_id' => $wxpayConfig['wxpay']['mch_id'],
                'nonce_str' => md5(uniqid()),
                'body' => 'AI旅拍作品购买',
                'out_trade_no' => $order['ordernum'],
                'total_fee' => $order['total_price'] * 100, // 单位:分
                'spbill_create_ip' => request()->ip(),
                'notify_url' => request()->domain() . '/api/aivideo/pay_callback',
                'trade_type' => 'NATIVE',
            ];

            // 生成签名
            $params['sign'] = $this->generateWechatSign($params, $wxpayConfig['wxpay']['key']);

            // 调用微信统一下单API
            $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
            $response = $this->sendWechatRequest($url, $params);

            if ($response['return_code'] == 'SUCCESS') {
                return [
                    'success' => true,
                    'data' => [
                        'pay_type' => 'weixin',
                        'code_url' => $response['code_url'],
                        'prepay_id' => $response['prepay_id']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['return_msg'] ?? '微信支付失败'
                ];
            }
        } catch (\Exception $e) {
            Log::error('微信支付失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '微信支付异常'];
        }
    }

    /**
     * 支付宝支付
     * @param array $order 订单信息
     * @return array
     */
    private function alipayPay($order)
    {
        try {
            // 获取支付宝配置
            $alipayConfig = Db::name('admin_set')
                ->where('aid', $this->aid)
                ->find();

            if (!$alipayConfig || !$alipayConfig['alipay']) {
                return ['success' => false, 'message' => '支付宝支付未配置'];
            }

            // 构建支付参数
            $params = [
                'app_id' => $alipayConfig['alipay']['appid'],
                'method' => 'alipay.trade.page.pay',
                'charset' => 'UTF-8',
                'sign_type' => 'RSA2',
                'timestamp' => time(),
                'version' => '1.0',
                'notify_url' => request()->domain() . '/api/aivideo/pay_callback',
                'return_url' => request()->domain() . '/aivideo/pay_result',
                'out_trade_no' => $order['ordernum'],
                'total_amount' => $order['total_price'],
                'subject' => 'AI旅拍作品购买',
            ];

            // 生成签名
            $params['sign'] = $this->generateAlipaySign($params, $alipayConfig['alipay']['private_key']);

            // 调用支付宝支付API
            $url = 'https://openapi.alipay.com/gateway.do?charset=UTF-8';
            $response = $this->sendAlipayRequest($url, $params);

            if (isset($response['alipay_trade_page_pay_response'])) {
                return [
                    'success' => true,
                    'data' => [
                        'pay_type' => 'alipay',
                        'pay_url' => $response['alipay_trade_page_pay_response']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '支付宝支付失败'
                ];
            }
        } catch (\Exception $e) {
            Log::error('支付宝支付失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '支付宝支付异常'];
        }
    }

    /**
     * 余额支付
     * @param array $order 订单信息
     * @return array
     */
    private function balancePay($order)
    {
        Db::startTrans();
        try {
            // 查询会员信息
            $member = Db::name('member')->where('id', $order['mid'])->find();
            if (!$member) {
                return ['success' => false, 'message' => '会员不存在'];
            }

            // 检查余额是否足够
            if ($member['money'] < $order['total_price']) {
                return ['success' => false, 'message' => '余额不足'];
            }

            // 扣除余额
            Db::name('member')
                ->where('id', $order['mid'])
                ->dec('money', $order['total_price'])
                ->update();

            // 记录余额变动
            Db::name('member_moneylog')->insert([
                'aid' => $this->aid,
                'mid' => $order['mid'],
                'money' => -$order['total_price'],
                'type' => 'consume',
                'remark' => 'AI旅拍作品购买',
                'createtime' => time(),
            ]);

            // 更新订单状态
            Db::name('aivideo_order')
                ->where('id', $order['id'])
                ->update([
                    'pay_type' => 'balance',
                    'pay_status' => 1,
                    'pay_time' => time(),
                    'updatetime' => time(),
                ]);

            // 更新作品为已支付
            $workIds = explode(',', $order['work_ids']);
            Db::name('aivideo_work')
                ->whereIn('id', $workIds)
                ->where('mid', $order['mid'])
                ->update([
                    'mid' => $order['mid'],
                    'is_free' => 0,
                ]);

            // 发送支付成功通知
            $this->sendPayNotification($order);

            Db::commit();

            return [
                'success' => true,
                'data' => [
                    'pay_type' => 'balance',
                    'message' => '余额支付成功'
                ]
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('余额支付失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '余额支付异常'];
        }
    }

    /**
     * 生成微信支付签名
     * @param array $params 支付参数
     * @param string $key 商户密钥
     * @return string
     */
    private function generateWechatSign($params, $key)
    {
        ksort($params);
        $string = '';
        foreach ($params as $k => $v) {
            if ($k != 'sign') {
                $string .= $k . '=' . $v . '&';
            }
        }
        $string = trim($string, '&');
        $string .= 'key=' . $key;
        return strtoupper(md5($string));
    }

    /**
     * 生成支付宝签名
     * @param array $params 支付参数
     * @param string $privateKey 商户私钥
     * @return string
     */
    private function generateAlipaySign($params, $privateKey)
    {
        ksort($params);
        $string = '';
        foreach ($params as $k => $v) {
            if ($k != 'sign') {
                $string .= $k . '=' . $v . '&';
            }
        }
        $string = trim($string, '&');
        
        // 使用RSA2签名
        openssl_sign($string, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);
        
        return $signature;
    }

    /**
     * 发送微信支付请求
     * @param string $url 请求URL
     * @param array $params 请求参数
     * @return array
     */
    private function sendWechatRequest($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('微信支付请求失败: ' . $error);
            return ['return_code' => 'FAIL', 'return_msg' => $error];
        }

        $result = json_decode($response, true);

        return $result ?: ['return_code' => 'FAIL', 'return_msg' => '请求失败'];
    }

    /**
     * 发送支付宝支付请求
     * @param string $url 请求URL
     * @param array $params 请求参数
     * @return array
     */
    private function sendAlipayRequest($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('支付宝支付请求失败: ' . $error);
            return ['error' => $error];
        }

        parse_str($response, $result);

        return $result ?: ['error' => '请求失败'];
    }

    /**
     * 获取浏览记录
     * @return string
     */
    public function browse_history()
    {
        $mid = input('param.mid/d');
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        if (!$mid) {
            return jsonEncode(['status' => 0, 'msg' => '会员ID不能为空']);
        }

        // 查询浏览记录
        $where = [
            ['aid', '=', $this->aid],
            ['mid', '=', $mid],
        ];

        $list = Db::name('aivideo_selection')
            ->alias('s')
            ->leftJoin('aivideo_work w', 's.work_id = w.id')
            ->where($where)
            ->order('s.id desc')
            ->page($page, $limit)
            ->field('s.*,w.work_name,w.thumbnail_url,w.price')
            ->select()
            ->toArray();

        // 查询总数
        $total = Db::name('aivideo_selection')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }
}
