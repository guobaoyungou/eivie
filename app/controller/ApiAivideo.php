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
    
    /**
     * 获取场景模板列表（含价格信息）
     * 根据用户会员等级返回对应价格
     * @return string
     */
    public function scene_template_list()
    {
        $bid = input('param.bid/d', 0);
        $generationType = input('param.generation_type/d', 1); // 1=图片 2=视频
        $categoryId = input('param.category_id/d', 0);
        $groupId = input('param.group_id/d', 0);
        
        if (!$bid) {
            return jsonEncode(['status' => 0, 'msg' => '缺少商户ID']);
        }
        
        // 获取用户会员等级
        $memberLevelId = 0;
        if ($this->mid > 0) {
            $member = Db::name('member')
                ->where('aid', $this->aid)
                ->where('id', $this->mid)
                ->field('id,levelid')
                ->find();
            if ($member) {
                $memberLevelId = intval($member['levelid']);
            }
        }
        
        // 新增关键词搜索参数
        $keyword = input('param.keyword', '');
        
        // 构建额外查询条件
        $extraWhere = [];
        if ($categoryId > 0) {
            $extraWhere[] = ['', 'exp', Db::raw("FIND_IN_SET({$categoryId}, category_ids)")];
        }
        if ($groupId > 0) {
            $extraWhere[] = ['', 'exp', Db::raw("FIND_IN_SET({$groupId}, group_ids)")];
        }
        if (!empty($keyword)) {
            $extraWhere[] = ['template_name', 'like', '%' . $keyword . '%'];
        }
        
        $service = new \app\service\GenerationService();
        $list = $service->getTemplateListWithPrice($this->aid, $bid, $generationType, $memberLevelId, $extraWhere);
        
        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'member_level_id' => $memberLevelId
            ]
        ]);
    }
    
    /**
     * 获取场景模板详情（含价格信息）
     * @return string
     */
    public function scene_template_detail()
    {
        $templateId = input('param.template_id/d', 0);
        
        if (!$templateId) {
            return jsonEncode(['status' => 0, 'msg' => '缺少模板ID']);
        }
        
        $template = Db::name('generation_scene_template')
            ->where('id', $templateId)
            ->where('status', 1)
            ->find();
        
        if (!$template) {
            return jsonEncode(['status' => 0, 'msg' => '模板不存在或已下架']);
        }
        
        // 获取用户会员等级
        $memberLevelId = 0;
        if ($this->mid > 0) {
            $member = Db::name('member')
                ->where('aid', $this->aid)
                ->where('id', $this->mid)
                ->field('id,levelid')
                ->find();
            if ($member) {
                $memberLevelId = intval($member['levelid']);
            }
        }
        
        $service = new \app\service\GenerationService();
        $priceInfo = $service->calculateTemplatePrice($template, $memberLevelId);
        
        // 获取所有等级价格用于展示对比
        $allPrices = [];
        if ($template['lvprice'] == 1) {
            $lvpriceData = is_string($template['lvprice_data']) 
                ? json_decode($template['lvprice_data'], true) 
                : ($template['lvprice_data'] ?: []);
            
            // 查询等级名称
            if (!empty($lvpriceData)) {
                $levelIds = array_keys($lvpriceData);
                $levels = Db::name('member_level')
                    ->where('id', 'in', $levelIds)
                    ->column('name', 'id');
                foreach ($lvpriceData as $lid => $lprice) {
                    $allPrices[] = [
                        'level_id' => $lid,
                        'level_name' => $levels[$lid] ?? '未知等级',
                        'price' => floatval($lprice)
                    ];
                }
            }
        }
        
        // 解析默认参数
        $defaultParams = is_string($template['default_params']) 
            ? json_decode($template['default_params'], true) 
            : ($template['default_params'] ?: []);
        
        // 获取参考图（原图）
        $refImage = '';
        if (!empty($defaultParams['image'])) {
            $refImage = $defaultParams['image'];
        } elseif (!empty($defaultParams['first_frame_image'])) {
            $refImage = $defaultParams['first_frame_image'];
        }
        
        // 获取模型能力信息
        $modelCapability = [
            'max_images' => 1,
            'supported_ratios' => ['1:1'],
            'supported_sizes' => []
        ];
        if (!empty($template['model_id'])) {
            $modelInfo = Db::name('model_info')
                ->where('id', $template['model_id'])
                ->field('id, model_code, model_name, input_schema')
                ->find();
            if ($modelInfo) {
                $inputSchema = is_string($modelInfo['input_schema']) 
                    ? json_decode($modelInfo['input_schema'], true) 
                    : ($modelInfo['input_schema'] ?: []);
                
                // 检查是否支持n参数（多张生成）
                $props = $inputSchema['properties'] ?? $inputSchema;
                if (isset($props['n'])) {
                    $maxN = intval($props['n']['maximum'] ?? $props['n']['max'] ?? 9);
                    $modelCapability['max_images'] = $maxN > 0 ? $maxN : 9;
                }
                // 检查支持的尺寸/比例
                if (isset($props['size'])) {
                    $sizeEnum = $props['size']['enum'] ?? $props['size']['options'] ?? [];
                    $modelCapability['supported_sizes'] = $sizeEnum;
                }
                
                // 从模型 size 枚举解析支持的比例
                if (!empty($sizeEnum)) {
                    $parsedRatios = $this->parseSupportedRatiosFromSizes($sizeEnum);
                    if (!empty($parsedRatios)) {
                        $modelCapability['supported_ratios'] = $parsedRatios;
                    }
                }
                // 如果模型支持图生图，标记
                $modelCapability['model_name'] = $modelInfo['model_name'] ?? '';
                $modelCapability['model_code'] = $modelInfo['model_code'] ?? '';
            }
        }
        
        // 若模型 supported_ratios 仍为默认值（仅 1:1），则补全为完整默认列表
        if (count($modelCapability['supported_ratios']) <= 1) {
            $modelCapability['supported_ratios'] = ['1:1','2:3','3:2','3:4','4:3','9:16','16:9','4:5','5:4','21:9'];
        }
        
        // 从源生成记录获取结果示例图
        $sampleImages = [];
        if (!empty($template['source_record_id'])) {
            $outputs = Db::name('generation_output')
                ->where('record_id', $template['source_record_id'])
                ->field('output_url, thumbnail_url, output_type')
                ->limit(4)
                ->select()->toArray();
            foreach ($outputs as $out) {
                $sampleImages[] = $out['thumbnail_url'] ?: $out['output_url'];
            }
        }
        
        $result = [
            'id' => $template['id'],
            'template_name' => $template['template_name'],
            'cover_image' => $template['cover_image'],
            'ref_image' => $refImage,
            'description' => $template['description'],
            'prompt' => $defaultParams['prompt'] ?? '',
            'generation_type' => intval($template['generation_type'] ?? 1),
            'price' => $priceInfo['price'],
            'base_price' => $priceInfo['base_price'],
            'price_unit' => $priceInfo['price_unit'],
            'price_unit_text' => $priceInfo['price_unit_text'],
            'is_member_price' => $priceInfo['is_member_price'],
            'use_count' => intval($template['use_count'] ?? 0),
            'output_quantity' => intval($template['output_quantity'] ?? 1),
            'prompt_visible' => intval($template['prompt_visible'] ?? 1),
            'is_id_photo' => intval($template['is_id_photo'] ?? 0),
            'id_photo_type' => intval($template['id_photo_type'] ?? 0),
            'id_photo_type_name' => $this->getIdPhotoTypeName(intval($template['id_photo_type'] ?? 0), intval($template['is_id_photo'] ?? 0)),
            'all_prices' => $allPrices,
            'sample_images' => $sampleImages,
            'model_capability' => $modelCapability,
            'default_params' => $defaultParams
        ];
        
        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $result
        ]);
    }

    // =====================================================
    // 生成订单与退款申请 API
    // =====================================================

    /**
     * 创建生成订单
     * POST: scene_id, generation_type, bid
     */
    public function generation_order_create()
    {
        if (!$this->mid) {
            return jsonEncode(['status' => 0, 'msg' => '请先登录']);
        }

        $sceneId = input('post.scene_id/d', 0);
        $generationType = input('post.generation_type/d', 1);
        $bid = input('post.bid/d', 0);

        if (!$sceneId) {
            return jsonEncode(['status' => 0, 'msg' => '请选择场景模板']);
        }

        // 获取用户会员等级
        $memberLevelId = 0;
        $member = Db::name('member')
            ->where('aid', $this->aid)
            ->where('id', $this->mid)
            ->field('id,levelid')
            ->find();
        if ($member) {
            $memberLevelId = intval($member['levelid']);
        }

        $orderService = new \app\service\GenerationOrderService();
        $result = $orderService->createOrder([
            'aid' => $this->aid,
            'bid' => $bid,
            'mid' => $this->mid,
            'scene_id' => $sceneId,
            'generation_type' => $generationType,
            'member_level_id' => $memberLevelId
        ]);

        return jsonEncode($result);
    }

    /**
     * 获取用户生成订单列表
     * GET: generation_type(0=全部), status(-1=全部/0=待支付/1=生成中/2=已完成/3=退款相关)
     */
    public function generation_order_list()
    {
        if (!$this->mid) {
            return jsonEncode(['status' => 0, 'msg' => '请先登录']);
        }

        $generationType = input('param.generation_type/d', 0);
        $status = input('param.status/d', -1);
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $orderService = new \app\service\GenerationOrderService();
        $result = $orderService->getUserOrderList(
            $this->aid,
            $this->mid,
            $generationType,
            $status,
            $page,
            $limit
        );

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $result['data'],
                'total' => $result['count']
            ]
        ]);
    }

    /**
     * 获取生成订单详情
     * GET: order_id
     */
    public function generation_order_detail()
    {
        if (!$this->mid) {
            return jsonEncode(['status' => 0, 'msg' => '请先登录']);
        }

        $orderId = input('param.order_id/d', 0);
        if (!$orderId) {
            return jsonEncode(['status' => 0, 'msg' => '参数错误']);
        }

        // 查询订单并验证归属
        $order = Db::name('generation_order')
            ->alias('o')
            ->leftJoin('generation_scene_template t', 'o.scene_id = t.id')
            ->field('o.*, t.template_name, t.cover_image, t.description as scene_description')
            ->where('o.id', $orderId)
            ->where('o.aid', $this->aid)
            ->where('o.mid', $this->mid)
            ->where('o.status', 1)
            ->find();

        if (!$order) {
            return jsonEncode(['status' => 0, 'msg' => '订单不存在']);
        }

        // 格式化
        $order['createtime_text'] = $order['createtime'] ? date('Y-m-d H:i:s', $order['createtime']) : '';
        $order['pay_time_text'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '';
        $order['generation_type_text'] = $order['generation_type'] == 1 ? '照片生成' : '视频生成';
        
        // 是否可以退款：已支付 && 任务失败 && 未退款/已驳回
        $order['can_refund'] = ($order['pay_status'] == 1 && $order['task_status'] == 3 && in_array($order['refund_status'], [0, 3]));
        // 是否可以撤销退款申请
        $order['can_cancel_refund'] = ($order['refund_status'] == 1);

        // 获取生成记录和输出
        if ($order['record_id'] > 0) {
            $record = Db::name('generation_record')
                ->where('id', $order['record_id'])
                ->find();
            if ($record) {
                $record['status_text'] = $this->getTaskStatusText($record['status']);
                $record['outputs'] = Db::name('generation_output')
                    ->where('record_id', $record['id'])
                    ->select()
                    ->toArray();
            }
            $order['record'] = $record;
        }

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $order
        ]);
    }

    /**
     * 用户提交退款申请
     * POST: order_id, refund_reason
     */
    public function generation_refund_apply()
    {
        if (!$this->mid) {
            return jsonEncode(['status' => 0, 'msg' => '请先登录']);
        }

        $orderId = input('post.order_id/d', 0);
        $refundReason = input('post.refund_reason', '');

        if (!$orderId) {
            return jsonEncode(['status' => 0, 'msg' => '参数错误']);
        }
        if (!$refundReason) {
            return jsonEncode(['status' => 0, 'msg' => '请填写退款原因']);
        }

        $orderService = new \app\service\GenerationOrderService();
        $result = $orderService->applyRefund($orderId, $this->mid, $refundReason);

        return jsonEncode($result);
    }

    /**
     * 用户撤销退款申请
     * POST: order_id
     */
    public function generation_refund_cancel()
    {
        if (!$this->mid) {
            return jsonEncode(['status' => 0, 'msg' => '请先登录']);
        }

        $orderId = input('post.order_id/d', 0);
        if (!$orderId) {
            return jsonEncode(['status' => 0, 'msg' => '参数错误']);
        }

        $orderService = new \app\service\GenerationOrderService();
        $result = $orderService->cancelRefund($orderId, $this->mid);

        return jsonEncode($result);
    }

    /**
     * 获取任务状态文本
     */
    private function getTaskStatusText($status)
    {
        $map = [
            0 => '待处理',
            1 => '处理中',
            2 => '成功',
            3 => '失败',
            4 => '已取消'
        ];
        return $map[$status] ?? '未知';
    }
    
    /**
     * 获取证件照类型名称
     * @param int $idPhotoType 证件照类型编号
     * @param int $isIdPhoto 是否为证件照模式
     * @return string 类型名称
     */
    private function getIdPhotoTypeName($idPhotoType, $isIdPhoto = 1)
    {
        if ($isIdPhoto != 1) {
            return '';
        }
        $map = [
            0 => '',
            1 => '身份证照',
            2 => '护照/港澳通行证',
            3 => '驾驶证',
            4 => '一寸照',
            5 => '二寸照'
        ];
        return $map[$idPhotoType] ?? '';
    }
    
    /**
     * 从模型size枚举解析支持的比例列表
     * @param array $sizeEnum size枚举值列表，如 ['1024x1024','1024x1536',...]
     * @return array 比例列表，如 ['1:1','2:3',...]
     */
    private function parseSupportedRatiosFromSizes($sizeEnum)
    {
        $knownSizeRatioMap = [
            '512x512' => '1:1', '1024x1024' => '1:1', '2048x2048' => '1:1',
            '512x768' => '2:3', '1024x1536' => '2:3', '2048x3072' => '2:3',
            '768x512' => '3:2', '1536x1024' => '3:2', '3072x2048' => '3:2',
            '384x512' => '3:4', '768x1024' => '3:4', '1536x2048' => '3:4',
            '512x384' => '4:3', '1024x768' => '4:3', '2048x1536' => '4:3',
            '360x640' => '9:16', '720x1280' => '9:16', '1440x2560' => '9:16',
            '640x360' => '16:9', '1280x720' => '16:9', '2560x1440' => '16:9',
            '512x640' => '4:5', '1024x1280' => '4:5', '2048x2560' => '4:5',
            '640x512' => '5:4', '1280x1024' => '5:4', '2560x2048' => '5:4',
            '1260x540' => '21:9', '2520x1080' => '21:9', '3780x1620' => '21:9',
        ];
        $ratios = [];
        foreach ($sizeEnum as $size) {
            $size = str_replace('*', 'x', strtolower(trim($size)));
            if (isset($knownSizeRatioMap[$size]) && !in_array($knownSizeRatioMap[$size], $ratios)) {
                $ratios[] = $knownSizeRatioMap[$size];
            }
        }
        return $ratios;
    }

    // =====================================================
    // 小程序端生成任务相关接口
    // =====================================================

    /**
     * 创建生成订单（支持自定义参数）
     * POST: template_id, generation_type, prompt, ref_images[], quantity
     */
    public function create_generation_order()
    {
        if (!$this->mid) {
            return jsonEncode(['status' => 0, 'msg' => '请先登录']);
        }

        $templateId = input('post.template_id/d', 0);
        $generationType = input('post.generation_type/d', 1);
        $prompt = input('post.prompt', '');
        $refImages = input('post.ref_images/a', []);
        $quantity = input('post.quantity/d', 0);
        $ratio = input('post.ratio', '');
        $quality = input('post.quality', '');
        $bid = input('post.bid/d', 0);

        if (!$templateId) {
            return jsonEncode(['status' => 0, 'msg' => '请选择场景模板']);
        }

        // 验证提示词
        $prompt = trim($prompt);
        if (mb_strlen($prompt) < 2) {
            return jsonEncode(['status' => 0, 'msg' => '请填写提示词（至少2个字符）']);
        }
        if (mb_strlen($prompt) > 2000) {
            return jsonEncode(['status' => 0, 'msg' => '提示词不能超过2000个字符']);
        }

        // 获取用户会员等级
        $memberLevelId = 0;
        $member = Db::name('member')
            ->where('aid', $this->aid)
            ->where('id', $this->mid)
            ->field('id,levelid')
            ->find();
        if ($member) {
            $memberLevelId = intval($member['levelid']);
        }

        $orderService = new \app\service\GenerationOrderService();
        $result = $orderService->createOrderWithParams([
            'aid' => $this->aid,
            'bid' => $bid,
            'mid' => $this->mid,
            'scene_id' => $templateId,
            'generation_type' => $generationType,
            'member_level_id' => $memberLevelId,
            'user_prompt' => $prompt,
            'ref_images' => $refImages,
            'quantity' => $quantity,
            'ratio' => $ratio,
            'quality' => $quality
        ]);

        return jsonEncode($result);
    }

    /**
     * 提交生成任务（支付后调用）
     * POST: order_id
     */
    public function submit_generation_task()
    {
        if (!$this->mid) {
            return jsonEncode(['status' => 0, 'msg' => '请先登录']);
        }

        $orderId = input('post.order_id/d', 0);
        if (!$orderId) {
            return jsonEncode(['status' => 0, 'msg' => '参数错误']);
        }

        $orderService = new \app\service\GenerationOrderService();
        $result = $orderService->submitTask($orderId, $this->mid);

        return jsonEncode($result);
    }

    /**
     * 查询生成任务状态
     * GET: order_id 或 record_id
     */
    public function generation_task_status()
    {
        if (!$this->mid) {
            return jsonEncode(['status' => 0, 'msg' => '请先登录']);
        }

        $orderId = input('param.order_id/d', 0);
        $recordId = input('param.record_id/d', 0);

        if (!$orderId && !$recordId) {
            return jsonEncode(['status' => 0, 'msg' => '参数错误']);
        }

        // 通过订单查找记录ID
        if ($orderId > 0) {
            $order = Db::name('generation_order')
                ->where('id', $orderId)
                ->where('mid', $this->mid)
                ->field('id,record_id,task_status')
                ->find();
            if (!$order) {
                return jsonEncode(['status' => 0, 'msg' => '订单不存在']);
            }
            $recordId = $order['record_id'];
        }

        if (!$recordId) {
            return jsonEncode(['status' => 1, 'data' => ['task_status' => 0, 'status_text' => '待处理', 'finished' => false]]);
        }

        // 查询生成记录状态
        $generationService = new \app\service\GenerationService();
        $result = $generationService->getRecordStatus($recordId);

        return jsonEncode($result);
    }

    /**
     * 获取生成结果
     * GET: order_id 或 record_id
     */
    public function generation_task_result()
    {
        if (!$this->mid) {
            return jsonEncode(['status' => 0, 'msg' => '请先登录']);
        }

        $orderId = input('param.order_id/d', 0);
        $recordId = input('param.record_id/d', 0);

        if (!$orderId && !$recordId) {
            return jsonEncode(['status' => 0, 'msg' => '参数错误']);
        }

        // 通过订单查找记录ID
        if ($orderId > 0) {
            $order = Db::name('generation_order')
                ->where('id', $orderId)
                ->where('mid', $this->mid)
                ->field('id,record_id,scene_id,scene_name,generation_type')
                ->find();
            if (!$order) {
                return jsonEncode(['status' => 0, 'msg' => '订单不存在']);
            }
            $recordId = $order['record_id'];
        }

        if (!$recordId) {
            return jsonEncode(['status' => 0, 'msg' => '任务尚未开始']);
        }

        // 获取生成记录详情
        $generationService = new \app\service\GenerationService();
        $record = $generationService->getRecordDetail($recordId);

        if (!$record) {
            return jsonEncode(['status' => 0, 'msg' => '记录不存在']);
        }

        // 返回结果
        $result = [
            'record_id' => $record['id'],
            'status' => $record['status'],
            'status_text' => $this->getTaskStatusText($record['status']),
            'generation_type' => $record['generation_type'],
            'create_time' => $record['create_time_text'],
            'finish_time' => $record['finish_time_text'],
            'outputs' => []
        ];

        // 格式化输出
        if (!empty($record['outputs'])) {
            foreach ($record['outputs'] as $output) {
                $result['outputs'][] = [
                    'type' => $output['output_type'] ?? 'image',
                    'url' => $output['output_url'],
                    'thumbnail' => $output['thumbnail_url'] ?? $output['output_url'],
                    'width' => $output['width'] ?? 0,
                    'height' => $output['height'] ?? 0,
                    'duration' => $output['duration'] ?? 0
                ];
            }
        }

        return jsonEncode(['status' => 1, 'data' => $result]);
    }
}
