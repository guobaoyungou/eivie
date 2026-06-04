<?php
/**
 * 算八字 - 营销活动控制器
 * 支持微信OAuth授权、异步分析队列、公众号模板消息推送结果
 * 付费模式: 免费 / 先付费后预测 / 预测后按百分比预览+付费解锁全文
 */
namespace app\controller;

use app\service\BaziService;
use think\facade\Db;
use think\facade\Session;

class BaziController
{
    /**
     * 算八字活动首页
     * GET /bazi
     * 微信浏览器访问时自动进行静默OAuth授权获取openid
     */
    public function index()
    {
        $aid = $this->getAid();
        $code = input('param.code', '');
        $openid = Session::get('bazi_openid', '');

        // 微信浏览器 + 未授权 → 302静默授权
        if ($this->isWechatBrowser() && !$openid && empty($code)) {
            $redirectUrl = request()->domain() . '/?s=/bazi';
            $oauthUrl = \app\common\Wechat::getOauth2AuthorizeUrl($aid, $redirectUrl, 'snsapi_base', 'baziauth');
            return redirect($oauthUrl);
        }

        // OAuth回调：用code换openid
        if ($this->isWechatBrowser() && !$openid && !empty($code)) {
            $tokenInfo = \app\common\Wechat::getAccessTokenByCode($aid, $code, 'mp');
            if (!empty($tokenInfo['openid'])) {
                Session::set('bazi_openid', $tokenInfo['openid']);
                // 查找或创建member记录
                $member = Db::name('member')->where('aid', $aid)->where('mpopenid', $tokenInfo['openid'])->find();
                if ($member) {
                    Session::set('bazi_mid', $member['id']);
                } else {
                    // 新用户自动创建member记录，确保bazi_mid有值
                    $defaultLv = Db::name('member_level')->where('aid', $aid)->where('isdefault', 1)->find();
                    $mid = Db::name('member')->insertGetId([
                        'aid'       => $aid,
                        'mpopenid'  => $tokenInfo['openid'],
                        'nickname'  => '用户' . substr($tokenInfo['openid'], -6),
                        'headimg'   => PRE_URL . '/static/img/touxiang.png',
                        'sex'       => 3,
                        'platform'  => 'mp',
                        'levelid'   => $defaultLv['id'] ?? 0,
                        'createtime'=> time(),
                    ]);
                    if ($mid) {
                        Session::set('bazi_mid', $mid);
                    }
                    \think\facade\Log::info('BaziController: 新用户自动注册 member#' . $mid . ' openid=' . $tokenInfo['openid']);
                }
                // 清除code参数重定向回干净URL
                return redirect(request()->domain() . '/?s=/bazi');
            } else {
                \think\facade\Log::warning('BaziController: OAuth获取openid失败', ['tokenInfo' => $tokenInfo]);
            }
        }

        $html = file_get_contents(__DIR__ . '/../../public/bazi/index.html');
        if ($html === false) {
            return '<h1>页面加载失败</h1>';
        }
        return response($html)
            ->contentType('text/html; charset=utf-8')
            ->header([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'        => 'no-cache',
                'Expires'       => 'Thu, 01 Jan 1970 00:00:00 GMT',
            ]);
    }

    /**
     * 获取当前配置（供H5前端读取付费模式和价格）
     * GET /api/bazi/config
     */
    public function getConfig()
    {
        $service = new BaziService();
        $config = $service->getConfig($this->getAid());
        $memberInfo = $this->getMemberInfo();
        return json([
            'status' => 1,
            'data' => [
                'pay_mode'        => $config['pay_mode'],
                'price'           => floatval($config['price']),
                'preview_percent' => intval($config['preview_percent']),
                'has_openid'      => !empty(Session::get('bazi_openid')),
                'member_nickname' => $memberInfo['nickname'] ?? '',
                'member_headimg'  => $memberInfo['headimg'] ?? '',
            ],
        ]);
    }

    /**
     * 异步提交分析请求（新流程：提交即返回，后台队列处理）
     * POST /api/bazi/submit
     */
    public function submit()
    {
        $params = request()->post();
        $service = new BaziService();
        $aid = $this->getAid();

        // 参数校验
        if (empty($params['birth_date'])) return json(['status' => 0, 'msg' => '请填写出生日期']);
        if (empty($params['birth_time'])) return json(['status' => 0, 'msg' => '请填写出生时间']);
        if (empty($params['birth_place'])) return json(['status' => 0, 'msg' => '请填写出生地点']);

        $config = $service->getConfig($aid);
        $payMode = $config['pay_mode'];
        $price = floatval($config['price']);

        // 获取微信openid - 需要支付时必须先获取
        $openid = Session::get('bazi_openid', '');
        $mid = Session::get('bazi_mid', 0);

        // 需要支付的模式 + 微信浏览器 + 未授权 → 返回特定状态码让前端跳转OAuth
        if ($payMode !== 'free' && $price > 0 && $this->isWechatBrowser() && empty($openid)) {
            return json(['status' => 2, 'msg' => '需要微信授权', 'data' => ['need_oauth' => true]]);
        }

        // 先付费后预测模式：仍然走同步支付流程
        if ($payMode === 'pay_then_predict' && $price > 0) {
            // 创建订单，前端跳支付
            $ordernum = 'BAZI' . date('YmdHis') . rand(1000, 9999);
            try {
                Db::startTrans();
                $recordId = Db::name('bazi_order')->insertGetId([
                    'aid'          => $aid,
                    'mid'          => $mid,
                    'ordernum'     => $ordernum,
                    'payorderid'   => 0,
                    'pay_status'   => 0,
                    'pay_mode'     => 'pay_then_predict',
                    'price'        => $price,
                    'pay_time'     => 0,
                    'transaction_id' => '',
                    'input_json'   => json_encode($params, JSON_UNESCAPED_UNICODE),
                    'result_json'  => '',
                    'latency_ms'   => 0,
                    'total_tokens' => 0,
                    'create_time'  => time(),
                    'update_time'  => time(),
                    'ip'           => request()->ip(),
                ]);
                $payorderId = $service->createPayOrder($recordId, $ordernum, $aid, $mid, $price);
                Db::commit();
                return json(['status' => 1, 'data' => [
                    'ordernum' => $ordernum,
                    'payorder_id' => $payorderId,
                    'price' => $price,
                    'need_pay' => true,
                ]]);
            } catch (\Exception $e) {
                Db::rollback();
                return json(['status' => 0, 'msg' => '订单创建失败: ' . $e->getMessage()]);
            }
        }

        // 免费模式 / 预测后付费模式：异步提交
        try {
            $ordernum = 'BAZI' . date('YmdHis') . rand(1000, 9999);
            $inputJson = json_encode($params, JSON_UNESCAPED_UNICODE);
            $payorderId = 0;

            // predict_then_pay 模式：同步创建 payorder，支付时直接用 ApiPay/pay
            if ($payMode === 'predict_then_pay' && $price > 0) {
                Db::startTrans();
            }

            $recordId = Db::name('bazi_order')->insertGetId([
                'aid'          => $aid,
                'mid'          => $mid,
                'ordernum'     => $ordernum,
                'payorderid'   => 0,
                'pay_status'   => ($payMode === 'free') ? 1 : 0,
                'pay_mode'     => $payMode,
                'price'        => $price,
                'pay_time'     => ($payMode === 'free') ? time() : 0,
                'transaction_id' => '',
                'input_json'   => $inputJson,
                'result_json'  => '',
                'preview_text'  => '',
                'latency_ms'   => 0,
                'total_tokens' => 0,
                'create_time'  => time(),
                'update_time'  => time(),
                'ip'           => request()->ip(),
            ]);

            // predict_then_pay 模式：创建 payorder
            if ($payMode === 'predict_then_pay' && $price > 0) {
                $payorderId = $service->createPayOrder(intval($recordId), $ordernum, $aid, $mid, $price);
                if ($payorderId) {
                    Db::name('bazi_order')->where('id', $recordId)->update(['payorderid' => $payorderId, 'update_time' => time()]);
                }
                Db::commit();
            }

            // 记录openid用于后续推送
            if (!empty($openid)) {
                Db::name('bazi_order')->where('id', $recordId)->update([
                    'transaction_id' => $openid, // 暂用transaction_id字段存储openid
                ]);
            }

            // 推入异步队列
            $queueData = [
                'record_id' => $recordId,
                'aid'       => $aid,
                'openid'    => $openid,
            ];
            try {
                \think\facade\Queue::push('app\job\BaziAnalysisJob', $queueData, 'bazi_analysis');
                \think\facade\Log::info('BaziController: 分析任务已入队', $queueData);
            } catch (\Exception $e) {
                \think\facade\Log::error('BaziController: 队列推送失败 - ' . $e->getMessage());
                // 队列不可用时直接同步执行（降级方案）
                // 这里只记录错误，不阻塞用户
            }

            return json([
                'status' => 1,
                'msg'    => '分析已提交',
                'data'   => [
                    'ordernum'     => $ordernum,
                    'payorder_id'  => $payorderId,
                    'pay_mode'     => $payMode,
                    'price'        => $price,
                    'submitted'    => true,
                ],
            ]);
        } catch (\Exception $e) {
            \think\facade\Log::error('BaziController: 提交异常 - ' . $e->getMessage());
            return json(['status' => 0, 'msg' => '提交失败，请稍后重试']);
        }
    }

    /**
     * 查看分析结果
     * GET /bazi/result?ordernum=xxx
     */
    public function resultView()
    {
        $ordernum = input('ordernum', '');
        if (empty($ordernum)) {
            $html = $this->loadResultTemplate(false, null, '缺少订单号', []);
            return response($html)->contentType('text/html; charset=utf-8');
        }

        $order = Db::name('bazi_order')->where('ordernum', $ordernum)->find();
        if (empty($order)) {
            $html = $this->loadResultTemplate(false, null, '订单不存在', []);
            return response($html)->contentType('text/html; charset=utf-8');
        }

        // 获取配置（用于 predict_then_pay 模式的预览百分比）
        $service = new BaziService();
        $config = $service->getConfig($this->getAid());
        $memberInfo = $this->getMemberInfo();
        $config['member_nickname'] = $memberInfo['nickname'] ?? '';
        $config['member_headimg']  = $memberInfo['headimg'] ?? '';

        // 同步支付状态：如果 payorder 已支付但 bazi_order.pay_status 未更新，同步之
        if ($order['pay_status'] == 0 && $order['payorderid'] > 0) {
            $payorder = Db::name('payorder')->where('id', $order['payorderid'])->find();
            if ($payorder && $payorder['status'] == 1) {
                Db::name('bazi_order')->where('id', $order['id'])->update([
                    'pay_status' => 1,
                    'pay_time'   => $payorder['paytime'] ?? time(),
                    'update_time'=> time(),
                ]);
                $order['pay_status'] = 1;
            }
        }

        if (empty($order['result_json'])) {
            $html = $this->loadResultTemplate(true, $order, null, $config);
            return response($html)->contentType('text/html; charset=utf-8');
        }

        $html = $this->loadResultTemplate(true, $order, null, $config);
        return response($html)->contentType('text/html; charset=utf-8');
    }

    /**
     * 查询分析状态（前端轮询/记录卡点击用）
     * GET /api/bazi/result-status?ordernum=xxx
     */
    public function resultStatus()
    {
        $ordernum = input('ordernum', '');
        if (empty($ordernum)) {
            return json(['status' => 0, 'msg' => '缺少订单号']);
        }

        $order = Db::name('bazi_order')->where('ordernum', $ordernum)->find();
        if (empty($order)) {
            return json(['status' => 0, 'msg' => '订单不存在']);
        }

        // 同步支付状态
        if ($order['pay_status'] == 0 && $order['payorderid'] > 0) {
            $payorder = Db::name('payorder')->where('id', $order['payorderid'])->find();
            if ($payorder && $payorder['status'] == 1) {
                Db::name('bazi_order')->where('id', $order['id'])->update([
                    'pay_status' => 1, 'pay_time' => $payorder['paytime'] ?? time(), 'update_time' => time(),
                ]);
                $order['pay_status'] = 1;
            }
        }

        $completed = !empty($order['result_json']);
        $resultData = $completed ? json_decode($order['result_json'], true) : null;
        $payMode = $order['pay_mode'] ?? '';

        // predict_then_pay 且未支付 → 按百分比截断预览
        $previewPercent = 100;
        $needPay = false;
        $fullResultLen = 0;
        if ($payMode === 'predict_then_pay' && $order['pay_status'] == 0 && $completed) {
            $service = new BaziService();
            $config = $service->getConfig($this->getAid());
            $previewPercent = intval($config['preview_percent'] ?? 50);
            $resultText = $resultData['result'] ?? '';
            $fullResultLen = mb_strlen($resultText);
            $previewLen = intval($fullResultLen * $previewPercent / 100);
            $needPay = $previewLen < $fullResultLen;
            if ($needPay) {
                $resultData['result'] = mb_substr($resultText, 0, $previewLen);
            }
        }

        return json([
            'status' => 1,
            'data' => [
                'completed'    => $completed,
                'ordernum'     => $ordernum,
                'payorder_id'  => intval($order['payorderid'] ?? 0),
                'result'       => $resultData ? ($resultData['result'] ?? '') : '',
                'usage'        => $resultData ? ($resultData['usage'] ?? []) : [],
                'latency_ms'   => $order['latency_ms'] ?? 0,
                'pay_mode'     => $payMode,
                'pay_status'   => intval($order['pay_status']),
                'price'        => floatval($order['price']),
                'need_pay'     => $needPay,
                'preview_percent' => $previewPercent,
            ],
        ]);
    }

    /**
     * 获取当前用户的测算记录列表
     * GET /api/bazi/my-records
     * 根据 session 中的 bazi_openid / bazi_mid 查询
     */
    public function myRecords()
    {
        $aid = $this->getAid();
        $openid = Session::get('bazi_openid', '');
        $mid = Session::get('bazi_mid', 0);

        if (empty($openid) && empty($mid)) {
            return json(['status' => 0, 'msg' => '请先授权登录', 'data' => ['list' => []]]);
        }

        $query = Db::name('bazi_order')->where('aid', $aid);

        // 按 openid 或 mid 筛选
        if (!empty($openid)) {
            $query->where(function ($q) use ($openid, $mid) {
                $q->where('transaction_id', $openid);
                if ($mid > 0) {
                    $q->whereOr('mid', $mid);
                }
            });
        } elseif ($mid > 0) {
            $query->where('mid', $mid);
        }

        $list = $query->field('ordernum,mid,input_json,result_json,pay_status,pay_mode,price,latency_ms,total_tokens,create_time')
            ->order('create_time', 'desc')
            ->limit(20)
            ->select()
            ->toArray();

        // 格式化每条记录
        $records = [];
        foreach ($list as $row) {
            $inputData = json_decode($row['input_json'] ?? '{}', true) ?: [];
            $completed = !empty($row['result_json']);

            $records[] = [
                'ordernum'     => $row['ordernum'],
                'name'         => $inputData['name'] ?? '匿名用户',
                'birth_date'   => $inputData['birth_date'] ?? '',
                'birth_time'   => $inputData['birth_time'] ?? '',
                'birth_place'  => $inputData['birth_place'] ?? '',
                'gender'       => $inputData['gender'] ?? '',
                'completed'    => $completed,
                'pay_status'   => intval($row['pay_status']),
                'pay_mode'     => $row['pay_mode'],
                'price'        => floatval($row['price']),
                'latency_ms'   => intval($row['latency_ms']),
                'total_tokens' => intval($row['total_tokens']),
                'create_time'  => date('Y-m-d H:i', $row['create_time']),
                'timestamp'    => intval($row['create_time']),
            ];
        }

        return json([
            'status' => 1,
            'data' => ['list' => $records, 'total' => count($records)],
        ]);
    }

    /**
     * 同步计算API（保留旧接口兼容）
     * POST /api/bazi/calculate
     */
    public function calculate()
    {
        $params = request()->post();
        $service = new BaziService();
        $aid = $this->getAid();
        $config = $service->getConfig($aid);
        $payMode = $config['pay_mode'];

        if ($payMode === 'free') {
            $result = $service->calculate($params, $aid);
            if ($result['status'] === 1) {
                $service->saveRecord($params, $result, $aid, 0, 'free', 0);
            }
            return json($result);
        }

        if ($payMode === 'pay_then_predict') {
            $ordernum = input('post.ordernum', '');
            if (empty($ordernum)) return json(['status' => 0, 'msg' => '请先完成支付']);
            $checkResult = $service->checkPayStatus($ordernum);
            if (!$checkResult['paid']) return json(['status' => 0, 'msg' => '订单未支付']);

            // 如果前端传来的参数为空（页面刷新后表单已清空），从数据库读取
            if (empty($params['birth_date'])) {
                $savedOrder = Db::name('bazi_order')->where('ordernum', $ordernum)->find();
                if (!empty($savedOrder) && !empty($savedOrder['input_json'])) {
                    $params = json_decode($savedOrder['input_json'], true) ?: $params;
                }
            }
            $result = $service->calculate($params, $aid);
            if ($result['status'] === 1) {
                $data = $result['data'] ?? [];
                $usage = $data['usage'] ?? [];
                $resultJson = json_encode([
                    'result' => $data['result'] ?? '', 'reasoning' => $data['reasoning'] ?? '',
                    'usage' => $usage, 'latency_ms' => $data['latency_ms'] ?? 0,
                    'finish_reason' => $data['finish_reason'] ?? '',
                ], JSON_UNESCAPED_UNICODE);
                $inputJson = json_encode([
                    'name' => $params['name'] ?? '', 'birth_date' => $params['birth_date'] ?? '',
                    'birth_time' => $params['birth_time'] ?? '', 'birth_place' => $params['birth_place'] ?? '',
                    'gender' => $params['gender'] ?? '',
                ], JSON_UNESCAPED_UNICODE);
                Db::name('bazi_order')->where('ordernum', $ordernum)->update([
                    'input_json' => $inputJson, 'result_json' => $resultJson,
                    'latency_ms' => $data['latency_ms'] ?? 0,
                    'total_tokens' => $usage['total_tokens'] ?? 0,
                    'update_time' => time(), 'ip' => request()->ip(),
                ]);
            }
            return json($result);
        }

        if ($payMode === 'predict_then_pay') {
            $result = $service->calculate($params, $aid);
            if ($result['status'] !== 1) return json($result);
            $recordId = $service->saveRecord($params, $result, $aid, 0, 'predict_then_pay', floatval($config['price']));
            $fullResult = $result['data']['result'] ?? '';
            $previewPercent = intval($config['preview_percent']);
            $fullLen = mb_strlen($fullResult);
            $previewLen = intval($fullLen * $previewPercent / 100);
            $previewText = mb_substr($fullResult, 0, $previewLen);
            $hasMore = $previewLen < $fullLen;
            $order = Db::name('bazi_order')->where('id', $recordId)->find();
            $ordernum = $order['ordernum'] ?? '';
            return json([
                'status' => 1,
                'data' => [
                    'result' => $previewText . ($hasMore ? "\n\n--- 以下内容需付费后查看（已展示{$previewPercent}%） ---" : ''),
                    'reasoning' => '', 'usage' => $result['data']['usage'] ?? [],
                    'latency_ms' => $result['data']['latency_ms'] ?? 0,
                    'is_preview' => $hasMore, 'preview_percent' => $previewPercent,
                    'ordernum' => $ordernum, 'order_id' => $recordId,
                    'need_pay' => $hasMore, 'price' => floatval($config['price']),
                ],
            ]);
        }
        return json(['status' => 0, 'msg' => '未知的付费模式']);
    }

    /** 创建订单（先付费后预测） - 保留旧接口 */
    public function createOrder()
    {
        $params = request()->post();
        $service = new BaziService();
        $aid = $this->getAid();
        $mid = Session::get('bazi_mid', 0);
        $openid = Session::get('bazi_openid', '');
        $config = $service->getConfig($aid);
        if ($config['pay_mode'] !== 'pay_then_predict') return json(['status' => 0, 'msg' => '当前不是先付费模式']);
        $price = floatval($config['price']);
        if ($price <= 0) return json(['status' => 0, 'msg' => '价格未配置']);

        // 微信浏览器 + 未授权 → 返回need_oauth让前端跳转OAuth
        if ($this->isWechatBrowser() && empty($openid)) {
            return json(['status' => 2, 'msg' => '需要微信授权', 'data' => ['need_oauth' => true]]);
        }
        if (empty($params['birth_date']) || empty($params['birth_time']) || empty($params['birth_place']))
            return json(['status' => 0, 'msg' => '请完整填写出生信息']);
        $ordernum = 'BAZI' . date('YmdHis') . rand(1000, 9999);
        try {
            Db::startTrans();
            $recordId = Db::name('bazi_order')->insertGetId([
                'aid' => $aid, 'mid' => $mid, 'ordernum' => $ordernum, 'payorderid' => 0,
                'pay_status' => 0, 'pay_mode' => 'pay_then_predict', 'price' => $price,
                'pay_time' => 0, 'transaction_id' => '', 'input_json' => json_encode($params, JSON_UNESCAPED_UNICODE), 'result_json' => '',
                'latency_ms' => 0, 'total_tokens' => 0, 'create_time' => time(), 'update_time' => time(), 'ip' => request()->ip(),
            ]);
            $payorderId = $service->createPayOrder($recordId, $ordernum, $aid, $mid, $price);
            Db::commit();
            return json(['status' => 1, 'data' => ['order_id' => $recordId, 'ordernum' => $ordernum, 'payorder_id' => $payorderId, 'price' => $price, 'need_pay' => true]]);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['status' => 0, 'msg' => '订单创建失败: ' . $e->getMessage()]);
        }
    }

    /** 查询支付状态 */
    public function orderStatus()
    {
        $ordernum = input('ordernum', '');
        if (empty($ordernum)) return json(['status' => 0, 'msg' => '缺少订单号']);
        $service = new BaziService();
        $result = $service->checkPayStatus($ordernum);

        // 查询订单详情，返回完整信息供前端判断
        $order = $result['order'] ?? [];
        if (empty($order)) {
            // 订单不存在
            return json(['status' => 0, 'msg' => $result['msg'] ?? '订单不存在', 'data' => ['ordernum' => $ordernum]]);
        }

        $completed = !empty($order['result_json']);
        $payMode = $order['pay_mode'] ?? '';

        return json([
            'status' => 1,
            'data' => [
                'paid'      => $result['paid'],
                'ordernum'  => $ordernum,
                'pay_mode'  => $payMode,
                'completed' => $completed,
            ],
        ]);
    }

    /**
     * 付费解锁支付页面 (GET)
     * 对于 predict_then_pay 订单，创建 payorder 并渲染微信 JSAPI 支付页面
     * GET /bazi/go-pay?ordernum=xxx
     */
    public function goPay()
    {
        $ordernum = input('ordernum', '');
        if (empty($ordernum)) {
            return redirect('/?s=/bazi')->send();
        }
        $aid = $this->getAid();
        $code = input('param.code', '');
        $openid = Session::get('bazi_openid', '');

        // 微信浏览器 + 未授权 → 302静默授权
        if ($this->isWechatBrowser() && !$openid && empty($code)) {
            $redirectUrl = request()->domain() . '/?s=/bazi/go-pay&ordernum=' . urlencode($ordernum);
            $oauthUrl = \app\common\Wechat::getOauth2AuthorizeUrl($aid, $redirectUrl, 'snsapi_base', 'baziauth');
            return redirect($oauthUrl);
        }

        // OAuth回调：用code换openid
        if ($this->isWechatBrowser() && !$openid && !empty($code)) {
            $tokenInfo = \app\common\Wechat::getAccessTokenByCode($aid, $code, 'mp');
            if (!empty($tokenInfo['openid'])) {
                Session::set('bazi_openid', $tokenInfo['openid']);
                $member = Db::name('member')->where('aid', $aid)->where('mpopenid', $tokenInfo['openid'])->find();
                if ($member) {
                    Session::set('bazi_mid', $member['id']);
                } else {
                    // 新用户自动创建member
                    $defaultLv = Db::name('member_level')->where('aid', $aid)->where('isdefault', 1)->find();
                    $mid = Db::name('member')->insertGetId([
                        'aid'       => $aid,
                        'mpopenid'  => $tokenInfo['openid'],
                        'nickname'  => '用户' . substr($tokenInfo['openid'], -6),
                        'headimg'   => PRE_URL . '/static/img/touxiang.png',
                        'sex'       => 3,
                        'platform'  => 'mp',
                        'levelid'   => $defaultLv['id'] ?? 0,
                        'createtime'=> time(),
                    ]);
                    if ($mid) {
                        Session::set('bazi_mid', $mid);
                    }
                }
                return redirect(request()->domain() . '/?s=/bazi/go-pay&ordernum=' . urlencode($ordernum));
            } else {
                \think\facade\Log::warning('BaziController goPay: OAuth获取openid失败', ['tokenInfo' => $tokenInfo]);
            }
        }

        $service = new BaziService();
        $order = Db::name('bazi_order')->where('ordernum', $ordernum)->find();
        if (empty($order)) {
            return redirect('/?s=/bazi')->send();
        }
        if ($order['pay_status'] == 1) {
            return redirect('/?s=/bazi/result&ordernum=' . urlencode($ordernum))->send();
        }

        // 微信浏览器但OAuth未成功 → 显示错误页，引导返回首页重试
        if ($this->isWechatBrowser() && empty($openid)) {
            $resultUrl = request()->domain() . '/?s=/bazi';
            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>授权失败</title>'
                  . '<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh}'
                  . '.card{background:#fff;border-radius:16px;padding:40px 30px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:320px;width:90%}'
                  . '.err{font-size:52px;margin-bottom:12px}.title{font-size:18px;font-weight:600;margin-bottom:8px}.desc{font-size:14px;color:#999;margin-bottom:20px;line-height:1.6}'
                  . '.btn{display:inline-block;background:#07c160;color:#fff;border:none;padding:12px 28px;border-radius:24px;font-size:15px;cursor:pointer;text-decoration:none}</style></head>'
                  . '<body><div class="card"><div class="err">⚠️</div><div class="title">微信授权失败</div>'
                  . '<div class="desc">请确保在微信内打开链接，并允许授权后重试</div>'
                  . '<a class="btn" href="' . $resultUrl . '">返回首页重新授权</a></div></body></html>';
            return response($html)->contentType('text/html; charset=utf-8');
        }

        // 获取或创建 payorder
        $price = floatval($order['price']);
        if ($price <= 0) {
            $config = $service->getConfig($aid);
            $price = floatval($config['price']);
        }
        $mid = intval($order['mid']) ?: (intval(Session::get('bazi_mid', 0)));
        $openid = Session::get('bazi_openid', '');

        if ($order['payorderid'] > 0) {
            $existPay = Db::name('payorder')->where('id', $order['payorderid'])->find();
            if ($existPay && $existPay['status'] == 1) {
                Db::name('bazi_order')->where('id', $order['id'])->update([
                    'pay_status' => 1, 'pay_time' => $existPay['paytime'] ?? time(), 'update_time' => time(),
                ]);
                return redirect('/?s=/bazi/result&ordernum=' . urlencode($ordernum))->send();
            }
            if (!$existPay) {
                // payorderid 有值但记录不存在，创建新的
                $payorderId = $service->createPayOrder($order['id'], $ordernum, $aid, $mid, $price);
            } else {
                $payorderId = $existPay['id'];
                // 复用已有 payorder 的 ordernum 用于统一下单
                $payOrderNum = $existPay['ordernum'];
            }
        } else {
            $payorderId = $service->createPayOrder($order['id'], $ordernum, $aid, $mid, $price);
        }

        if (empty($payorderId)) {
            return redirect('/?s=/bazi/result&ordernum=' . urlencode($ordernum))->send();
        }

        // 获取 payorder 的 ordernum（用于微信统一下单）
        if (empty($payOrderNum)) {
            $payOrder = Db::name('payorder')->where('id', $payorderId)->find();
            $payOrderNum = $payOrder['ordernum'] ?? $ordernum;
        }

        // 调用微信统一下单获取 JSAPI 支付参数
        $jsApiParams = '{}';
        $payError = '';
        try {
            $rs = \app\common\Wxpay::build_mp($aid, 0, $mid, '八字命理分析', $payOrderNum, $price, 'bazi', '', $openid);
            if (!empty($rs['data']) && $rs['status'] == 1) {
                $jsApiParams = json_encode($rs['data'], JSON_UNESCAPED_UNICODE);
            } else {
                $payError = $rs['msg'] ?? '获取支付参数失败';
            }
        } catch (\Exception $e) {
            $payError = $e->getMessage();
        }

        $resultUrl = request()->domain() . '/?s=/bazi/result&ordernum=' . urlencode($ordernum);
        $statusUrl = request()->domain() . '/api/bazi/order-status?ordernum=' . urlencode($ordernum);

        // 渲染微信 JSAPI 支付页面
        $payErrorEscaped = addslashes($payError);
        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>支付</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#333}
.card{background:#fff;border-radius:16px;padding:40px 30px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:320px;width:90%}
.spinner{width:40px;height:40px;border:3px solid #eee;border-top-color:#07c160;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 16px}
@keyframes spin{to{transform:rotate(360deg)}}
.title{font-size:18px;font-weight:600;margin-bottom:8px}
.desc{font-size:14px;color:#999;margin-bottom:20px}
.price{font-size:28px;font-weight:700;color:#e64340;margin-bottom:16px}
.btn{display:inline-block;background:#07c160;color:#fff;border:none;padding:12px 32px;border-radius:24px;font-size:15px;cursor:pointer;text-decoration:none}
.btn:active{opacity:.8}
.btn-ghost{background:#fff;color:#666;border:1px solid #ddd;margin-top:12px}
.error{color:#e64340}
.gap{height:8px}
</style>
</head>
<body>
<div class="card" id="payCard">
  <div class="spinner" id="spinner"></div>
  <div class="title" id="title">正在发起支付...</div>
  <div class="price">¥{$price}</div>
  <div class="desc" id="desc">请在弹出的微信支付窗口中完成支付</div>
  <div style="display:none" id="btnArea">
    <a class="btn" id="retryBtn" href="javascript:void(0)" onclick="retryPay()">重新支付</a>
    <div class="gap"></div>
    <a class="btn btn-ghost" href="{$resultUrl}">查看八字分析</a>
  </div>
</div>
<script>
var PAY_PARAMS = {$jsApiParams};
var PAY_ERROR = '{$payErrorEscaped}';
var RESULT_URL = '{$resultUrl}';
var STATUS_URL = '{$statusUrl}';

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
  document.getElementById('desc').textContent = '您可以重新支付或直接查看分析预览';
  document.getElementById('btnArea').style.display = 'block';
}

function pollAndRedirect() {
  var count = 0;
  var timer = setInterval(function() {
    var x = new XMLHttpRequest();
    x.open('GET', STATUS_URL, true);
    x.timeout = 5000;
    x.onload = function() {
      if (x.status === 200) {
        try {
          var r = JSON.parse(x.responseText);
          if (r.data && r.data.paid) { clearInterval(timer); location.href = RESULT_URL; }
        } catch(e) {}
      }
    };
    x.onerror = function() {};
    x.send();
    count++;
    if (count >= 20) { clearInterval(timer); location.href = RESULT_URL; }
  }, 2000);
}

function retryPay() {
  document.getElementById('spinner').style.display = 'block';
  document.getElementById('title').textContent = '正在发起支付...';
  document.getElementById('title').className = 'title';
  document.getElementById('desc').textContent = '请在弹出的微信支付窗口中完成支付';
  document.getElementById('btnArea').style.display = 'none';
  invokePay();
}

invokePay();
</script>
</body>
</html>
HTML;
        return response($html)->contentType('text/html; charset=utf-8');
    }

    /** 付费解锁 */
    public function payToUnlock()
    {
        $ordernum = input('post.ordernum', '');
        if (empty($ordernum)) return json(['status' => 0, 'msg' => '缺少订单号']);
        $service = new BaziService();
        $aid = $this->getAid();
        $order = Db::name('bazi_order')->where('ordernum', $ordernum)->find();
        if (empty($order)) return json(['status' => 0, 'msg' => '订单不存在']);
        if ($order['pay_status'] == 1) return json(['status' => 0, 'msg' => '已完成支付，请直接查看结果']);
        // 已有未支付的 payorder → 复用之
        if ($order['payorderid'] > 0) {
            $existPay = Db::name('payorder')->where('id', $order['payorderid'])->find();
            if ($existPay && $existPay['status'] == 1) {
                Db::name('bazi_order')->where('id', $order['id'])->update([
                    'pay_status' => 1, 'pay_time' => $existPay['paytime'] ?? time(), 'update_time' => time(),
                ]);
                return json(['status' => 0, 'msg' => '已完成支付，请直接查看结果']);
            }
            if ($existPay) {
                return json(['status' => 1, 'data' => ['ordernum' => $ordernum, 'payorder_id' => $existPay['id'], 'price' => floatval($order['price']), 'need_pay' => true]]);
            }
        }
        $config = $service->getConfig($aid);
        $price = floatval($config['price']);
        $mid = intval($order['mid'] ?? 0);
        $payorderId = $service->createPayOrder(intval($order['id']), $ordernum, $aid, $mid, $price);
        if ($payorderId === false) return json(['status' => 0, 'msg' => '创建支付订单失败']);
        return json(['status' => 1, 'data' => ['ordernum' => $ordernum, 'payorder_id' => $payorderId, 'price' => $price, 'need_pay' => true]]);
    }

    /** 解锁完整结果 */
    public function unlockResult()
    {
        $ordernum = input('post.ordernum', '');
        if (empty($ordernum)) return json(['status' => 0, 'msg' => '缺少订单号']);
        $service = new BaziService();
        $checkResult = $service->checkPayStatus($ordernum);
        if (!$checkResult['paid']) return json(['status' => 0, 'msg' => '未完成支付']);
        $order = Db::name('bazi_order')->where('ordernum', $ordernum)->find();
        if (empty($order) || empty($order['result_json'])) return json(['status' => 0, 'msg' => '结果不存在']);
        $resultData = json_decode($order['result_json'], true);
        $inputData = json_decode($order['input_json'], true);
        return json(['status' => 1, 'data' => [
            'result' => $resultData['result'] ?? '', 'reasoning' => $resultData['reasoning'] ?? '',
            'usage' => $resultData['usage'] ?? [], 'latency_ms' => $resultData['latency_ms'] ?? 0,
            'is_preview' => false, 'input' => $inputData, 'pay_mode' => $order['pay_mode'], 'price' => floatval($order['price']),
        ]]);
    }

    // ==================== 辅助方法 ====================

    /**
     * 判断是否微信浏览器
     */
    protected function isWechatBrowser(): bool
    {
        $ua = request()->server('HTTP_USER_AGENT', '');
        return stripos($ua, 'MicroMessenger') !== false;
    }

    /**
     * 获取平台ID
     */
    protected function getAid(): int
    {
        $aid = session('aid') ?: cookie('aid');
        return intval($aid) ?: 1;
    }

    /**
     * 获取当前登录会员信息（昵称、头像）
     */
    protected function getMemberInfo(): array
    {
        $mid = Session::get('bazi_mid', 0);
        if ($mid > 0) {
            $member = Db::name('member')->where('id', $mid)->find();
            if ($member) {
                return [
                    'nickname' => $member['nickname'] ?? '',
                    'headimg'  => $member['headimg'] ?? '',
                ];
            }
        }
        // 回退: 通过openid查
        $openid = Session::get('bazi_openid', '');
        if (!empty($openid)) {
            $member = Db::name('member')->where('mpopenid', $openid)->find();
            if ($member) {
                return [
                    'nickname' => $member['nickname'] ?? '',
                    'headimg'  => $member['headimg'] ?? '',
                ];
            }
        }
        return ['nickname' => '', 'headimg' => ''];
    }

    /**
     * Markdown 渲染器
     * 支持：表格、引用块、列表、分隔线、标题、段落、行内格式化
     * 不依赖第三方库，仅用正则分段解析
     */
    protected function renderMarkdown(string $text): string
    {
        // ---- 预处理：统一换行符 ----
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // ---- 第1轮：保护行内代码（占位符替换，避免内部特殊字符被后续正则误伤） ----
        $codeBlocks = [];
        $text = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$codeBlocks) {
            $id = count($codeBlocks);
            $codeBlocks[$id] = '<code>' . htmlspecialchars($m[1]) . '</code>';
            return "\x00CODE{$id}\x00";
        }, $text);

        // ---- 第2轮：保护 HTML 实体（&amp; &lt; &gt; 在非代码区域先转义） ----
        // 注意：split 之前文本中可能已有 HTML，先不做 htmlspecialchars
        // 后续步骤中在需要的地方局部转义

        // ---- 第3轮：表格（连续的 |...| 行） ----
        $lines = explode("\n", $text);
        $out = [];
        $i = 0;
        $n = count($lines);
        while ($i < $n) {
            $line = $lines[$i];
            // 检测表格：当前行含 | 且非代码块
            if (preg_match('/^\|.+\|$/', trim($line)) && ($i + 1 < $n) && preg_match('/^\|[\s\-:|]+\|$/', trim($lines[$i + 1]))) {
                // 收集所有表格行
                $tableLines = [];
                $tableLines[] = $line;
                $i++;
                $sepLine = $lines[$i]; // 分隔行 |---|---|
                $i++;
                // 确定对齐方式
                $aligns = [];
                $cells = explode('|', trim($sepLine, '|'));
                foreach ($cells as $c) {
                    $c = trim($c);
                    $left = $c[0] === ':';
                    $right = substr($c, -1) === ':';
                    if ($left && $right) $aligns[] = 'center';
                    elseif ($right) $aligns[] = 'right';
                    else $aligns[] = 'left';
                }
                // 收集数据行
                while ($i < $n) {
                    if (preg_match('/^\|.+\|$/', trim($lines[$i]))) {
                        $tableLines[] = $lines[$i];
                        $i++;
                    } else {
                        break;
                    }
                }
                // 构建 HTML table
                $html = '<div class="md-table-wrap"><table>';
                foreach ($tableLines as $idx => $tl) {
                    $rawCells = explode('|', trim(trim($tl), '|'));
                    $tag = ($idx === 0 && !preg_match('/^[\s\-:|]+$/', trim($tl))) ? 'th' : 'td';
                    // 跳过分隔行
                    if (preg_match('/^[\s\-:|]+$/', str_replace('|', '', trim($tl)))) continue;
                    $html .= '<tr>';
                    foreach ($rawCells as $ci => $cell) {
                        $cell = trim($cell);
                        $align = isset($aligns[$ci]) ? ' style="text-align:' . $aligns[$ci] . '"' : '';
                        $html .= "<{$tag}{$align}>" . htmlspecialchars($cell) . "</{$tag}>";
                    }
                    $html .= '</tr>';
                }
                $html .= '</table></div>';
                $out[] = $html;
                continue;
            }
            $out[] = $line;
            $i++;
        }
        $text = implode("\n", $out);

        // 重新分行
        $lines = explode("\n", $text);
        $out = [];
        $i = 0;
        $n = count($lines);

        // ---- 第4轮：水平分隔线（单独的 --- 或 *** 行） ----
        $tmp = [];
        foreach ($lines as $line) {
            if (preg_match('/^(-{3,}|\*{3,})\s*$/', trim($line))) {
                $tmp[] = "\x00HR\x00";
            } else {
                $tmp[] = $line;
            }
        }
        $text = implode("\n", $tmp);
        $lines = explode("\n", $text);

        // ---- 第5轮：引用块（连续 > 开头的行） ----
        $out = [];
        $i = 0;
        while ($i < $n) {
            $line = $lines[$i];
            if (preg_match('/^>\s?(.*)$/', $line, $m)) {
                $quoteLines = [];
                $quoteLines[] = $m[1];
                $i++;
                while ($i < $n && preg_match('/^>\s?(.*)$/', $lines[$i], $qm)) {
                    $quoteLines[] = $qm[1];
                    $i++;
                }
                $out[] = '<blockquote><p>' . implode('<br>', array_map('htmlspecialchars', $quoteLines)) . '</p></blockquote>';
                continue;
            }
            $out[] = $line;
            $i++;
        }
        $text = implode("\n", $out);
        $lines = explode("\n", $text);

        // ---- 第6轮：列表解析（预处理，收集连续列表项） ----
        // 将文本按"段落"分组（空行分隔）
        $groups = [];
        $current = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                if (!empty($current)) { $groups[] = $current; $current = []; }
                $groups[] = ['']; // 空行作为分隔
            } else {
                $current[] = $line;
            }
        }
        if (!empty($current)) $groups[] = $current;

        $result = [];
        $inList = false;
        $listType = '';
        $listItems = [];

        foreach ($groups as $group) {
            if (count($group) === 1 && $group[0] === '') {
                // 空行分隔
                if ($inList) {
                    $result[] = ($listType === 'ol' ? '<ol>' : '<ul>') . implode('', $listItems) . ($listType === 'ol' ? '</ol>' : '</ul>');
                    $inList = false;
                    $listItems = [];
                }
                continue;
            }

            // 检查整组是否是同类列表
            $allList = true;
            $groupType = '';
            foreach ($group as $gl) {
                $tgl = trim($gl);
                if (preg_match('/^[-*+]\s/', $tgl)) {
                    if ($groupType === '') $groupType = 'ul';
                    elseif ($groupType !== 'ul') { $allList = false; break; }
                } elseif (preg_match('/^\d+[.)]\s/', $tgl)) {
                    if ($groupType === '') $groupType = 'ol';
                    elseif ($groupType !== 'ol') { $allList = false; break; }
                } else {
                    $allList = false; break;
                }
            }

            if ($allList && $groupType !== '') {
                if ($inList && $listType === $groupType) {
                    // 继续同类型列表
                } elseif ($inList && $listType !== $groupType) {
                    // 切换类型：先关闭
                    $result[] = ($listType === 'ol' ? '<ol>' : '<ul>') . implode('', $listItems) . ($listType === 'ol' ? '</ol>' : '</ul>');
                    $listItems = [];
                }
                $inList = true;
                $listType = $groupType;
                foreach ($group as $gl) {
                    if ($groupType === 'ul') {
                        $content = preg_replace('/^[-*+]\s/', '', trim($gl));
                    } else {
                        $content = preg_replace('/^\d+[.)]\s/', '', trim($gl));
                    }
                    $listItems[] = '<li>' . htmlspecialchars($content) . '</li>';
                }
                continue;
            }

            // 不是列表 → 关闭列表
            if ($inList) {
                $result[] = ($listType === 'ol' ? '<ol>' : '<ul>') . implode('', $listItems) . ($listType === 'ol' ? '</ol>' : '</ul>');
                $inList = false;
                $listItems = [];
            }
            $result[] = implode("\n", $group);
        }
        // 收尾列表
        if ($inList) {
            $result[] = ($listType === 'ol' ? '<ol>' : '<ul>') . implode('', $listItems) . ($listType === 'ol' ? '</ol>' : '</ul>');
        }
        $text = implode("\n\n", $result);
        $lines = explode("\n", $text);

        // ---- 第7轮：标题（# ## ###）----
        $out = [];
        foreach ($lines as $line) {
            if (preg_match('/^### (.+)$/', $line, $m)) {
                $out[] = '<h3>' . htmlspecialchars($m[1]) . '</h3>';
            } elseif (preg_match('/^## (.+)$/', $line, $m)) {
                $out[] = '<h2 class="md-h2">' . htmlspecialchars($m[1]) . '</h2>';
            } else {
                $out[] = $line;
            }
        }
        $text = implode("\n", $out);

        // ---- 第8轮：段落包裹（非标签行包裹在 <p> 中） ----
        $lines = explode("\n", $text);
        $out = [];
        $para = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if (!empty($para)) {
                    $out[] = '<p>' . implode('<br>', $para) . '</p>';
                    $para = [];
                }
            } elseif (preg_match('/^<(h[2-3]|table|blockquote|ul|ol|div|tr|td|th|thead|tbody|li|p|hr)[ >]/i', $line)) {
                // HTML 标签行，直接输出
                if (!empty($para)) {
                    $out[] = '<p>' . implode('<br>', $para) . '</p>';
                    $para = [];
                }
                $out[] = $line;
            } else {
                $para[] = htmlspecialchars($line);
            }
        }
        if (!empty($para)) {
            $out[] = '<p>' . implode('<br>', $para) . '</p>';
        }
        $text = implode("\n", $out);

        // ---- 第9轮：恢复水平线占位符 ----
        $text = str_replace("\x00HR\x00", '<hr>', $text);

        // ---- 第10轮：行内格式化（**加粗**、*斜体*） ----
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $text);

        // ---- 第11轮：恢复行内代码占位符 ----
        $text = preg_replace_callback('/\x00CODE(\d+)\x00/', function ($m) use ($codeBlocks) {
            return $codeBlocks[intval($m[1])] ?? $m[0];
        }, $text);

        return $text;
    }

    /**
     * 加载结果查看页模板（内联HTML）
     */
    protected function loadResultTemplate(bool $ok, ?array $order, ?string $error, array $config = []): string
    {
        $title = '八字命理分析结果';
        $bodyClass = '';
        $content = '';
        $infoHtml = '';
        $inputHtml = '';

        // 会员信息
        $memberNickname = $config['member_nickname'] ?? '';
        $memberHeadimg  = $config['member_headimg'] ?? '';
        $memberBadgeHtml = '';
        if (!empty($memberNickname) || !empty($memberHeadimg)) {
            $memberBadgeHtml = '<div class="member-badge show">';
            if (!empty($memberHeadimg)) {
                $memberBadgeHtml .= '<img class="avatar" src="' . htmlspecialchars($memberHeadimg) . '" alt="">';
            } else {
                $memberBadgeHtml .= '<span class="avatar avatar-fallback">👤</span>';
            }
            $memberBadgeHtml .= '<span>' . htmlspecialchars($memberNickname ?: '已登录') . '</span></div>';
        }

        // ---------- 状态判断 ----------
        if ($error) {
            $bodyClass = 'state-error';
            $content = '<div class="status-msg error">' . htmlspecialchars($error) . '</div>';
        } elseif (!$ok || empty($order)) {
            $bodyClass = 'state-error';
            $content = '<div class="status-msg error">记录不存在</div>';
        } elseif (empty($order['result_json'])) {
            // 分析中
            $bodyClass = 'state-pending';
            $inputData = json_decode($order['input_json'] ?? '{}', true) ?: [];
            $content = '<div class="pending-view">
                <div class="pending-icon">⏳</div>
                <h2 class="pending-title">分析进行中</h2>
                <p class="pending-desc">AI 正在为您排盘分析，预计 5 分钟完成</p>
                <p class="pending-sub">完成后将通过公众号推送结果通知</p>
                <div class="order-tag">订单号: ' . htmlspecialchars($order['ordernum']) . '</div>
            </div>';

            if ($inputData) {
                $inputHtml = '<div class="input-card">
                    <div class="input-card-title">📋 您提交的信息</div>';
                if (!empty($inputData['name'])) $inputHtml .= '<div class="input-row"><span class="lbl">姓名</span><span>' . htmlspecialchars($inputData['name']) . '</span></div>';
                $inputHtml .= '<div class="input-row"><span class="lbl">出生</span><span>' . htmlspecialchars($inputData['birth_date'] ?? '') . ' ' . htmlspecialchars($inputData['birth_time'] ?? '') . '</span></div>';
                $inputHtml .= '<div class="input-row"><span class="lbl">地点</span><span>' . htmlspecialchars($inputData['birth_place'] ?? '') . '</span></div>';
                $inputHtml .= '<div class="input-row"><span class="lbl">性别</span><span>' . htmlspecialchars($inputData['gender'] ?? '') . '</span></div>';
                $inputHtml .= '</div>';
            }
        } else {
            // 已完成
            $bodyClass = 'state-done';
            $resultData = json_decode($order['result_json'], true) ?: [];
            $inputData = json_decode($order['input_json'], true) ?: [];

            $isPreview = false;
            $hasMore = false;
            $previewPercent = intval($config['preview_percent'] ?? 50);
            $price = floatval($order['price'] ?: ($config['price'] ?? 0));
            $ordernum = $order['ordernum'] ?? '';

            // predict_then_pay 且未支付 → 按百分比截断预览
            if ($order['pay_mode'] === 'predict_then_pay' && $order['pay_status'] == 0) {
                $isPreview = true;
                $fullText = $resultData['result'] ?? '';
                $fullLen = mb_strlen($fullText);
                $previewLen = intval($fullLen * $previewPercent / 100);
                $hasMore = $previewLen < $fullLen;
                if ($hasMore) {
                    $resultData['result'] = mb_substr($fullText, 0, $previewLen);
                }
            }

            // 结果文本（使用增强 Markdown 渲染器）
            $rendered = $this->renderMarkdown($resultData['result'] ?? '');

            // 生成章节目录导航（只从 h2/h3 提取）
            $tocHtml = '';
            if (preg_match_all('/<h([23])(?:[^>]*)>(.*?)<\/h[23]>/', $rendered, $tocMatches, PREG_SET_ORDER)) {
                $tocItems = [];
                foreach ($tocMatches as $tm) {
                    $level = $tm[1];
                    $title = strip_tags($tm[2]);
                    $anchor = 'section-' . md5($title);
                    $tocItems[] = ['level' => $level, 'title' => $title, 'anchor' => $anchor];
                    // 给对应标题加 id 锚点
                    $rendered = preg_replace(
                        '/<h' . $level . '>/' . preg_quote($tm[2], '/') . '<\/h' . $level . '>/',
                        '<h' . $level . ' id="' . $anchor . '">' . $tm[2] . '</h' . $level . '>',
                        $rendered,
                        1
                    );
                }
                if (count($tocItems) > 1) {
                    $tocHtml = '<details class="toc-nav"><summary>📑 目录导航</summary><ul>';
                    foreach ($tocItems as $item) {
                        $pad = $item['level'] == 3 ? ' style="padding-left:16px"' : '';
                        $tocHtml .= '<li' . $pad . '><a href="#' . $item['anchor'] . '">' . htmlspecialchars($item['title']) . '</a></li>';
                    }
                    $tocHtml .= '</ul></details>';
                }
            }

            // 用 section-card 包裹每个 h2 章节
            $rendered = preg_replace_callback(
                '/(<h2[^>]*>.*?<\/h2>)((?:(?!<h2[^>]*>).)*)/s',
                function ($matches) {
                    return '<div class="section-card">' . $matches[0] . '</div>';
                },
                $rendered
            );

            // 预览遮罩提示
            $previewOverlayHtml = '';
            if ($isPreview && $hasMore) {
                $payOrderId = intval($order['payorderid'] ?? 0);
                if ($payOrderId > 0) {
                    $returnUrl = request()->domain() . '/?s=/bazi&ordernum=' . urlencode($ordernum);
                    $payUrl = '/?s=/ApiPay/pay&orderid=' . $payOrderId . '&return_url=' . urlencode($returnUrl);
                } else {
                    // 旧订单无 payorder → 回退到 goPay 创建
                    $payUrl = '/?s=/bazi/go-pay&ordernum=' . urlencode($ordernum);
                }
                $previewOverlayHtml = '<div style="position:relative;margin-top:24px;">
                    <div style="position:absolute;inset:0;background:linear-gradient(180deg,transparent 0%,rgba(8,6,20,.85) 60%,rgba(8,6,20,.95) 100%);pointer-events:none;border-radius:var(--r-sm);"></div>
                    <div style="position:relative;text-align:center;padding:40px 20px 16px;z-index:1;">
                        <div style="font-size:28px;margin-bottom:8px;">🔒</div>
                        <p style="font-size:15px;color:var(--gold-glow);font-weight:600;margin-bottom:6px;">已展示 ' . $previewPercent . '% 内容</p>
                        <p style="font-size:13px;color:var(--text-dim);margin-bottom:18px;">剩余内容需付费后查看</p>
                        <a href="' . $payUrl . '" class="btn-pay-unlock">💰 付费查看完整报告 ¥' . number_format($price, 2) . '</a>
                    </div>
                </div>';
            }

            $latency = round(($order['latency_ms'] ?? 0) / 1000, 1);
            $infoHtml = '<div class="meta-bar">
                <span>🕐 ' . $latency . 's</span>
                <span>🔢 ' . ($order['total_tokens'] ?? '-') . ' tokens</span>
                <span>📅 ' . date('m-d H:i', $order['create_time'] ?? 0) . '</span>
                ' . ($isPreview ? '<span style="color:var(--gold-muted);">📋 预览 ' . $previewPercent . '%</span>' : '') . '
            </div>';

            // 输入信息卡片
            if ($inputData) {
                $inputHtml = '<details class="input-details"><summary>📋 出生信息</summary>';
                if (!empty($inputData['name'])) $inputHtml .= '<div class="input-row"><span class="lbl">姓名</span><span>' . htmlspecialchars($inputData['name']) . '</span></div>';
                $inputHtml .= '<div class="input-row"><span class="lbl">出生</span><span>' . htmlspecialchars($inputData['birth_date'] ?? '') . ' ' . htmlspecialchars($inputData['birth_time'] ?? '') . '</span></div>';
                $inputHtml .= '<div class="input-row"><span class="lbl">地点</span><span>' . htmlspecialchars($inputData['birth_place'] ?? '') . '</span></div>';
                $inputHtml .= '<div class="input-row"><span class="lbl">性别</span><span>' . htmlspecialchars($inputData['gender'] ?? '') . '</span></div>';
                $inputHtml .= '</details>';
            }

            $content = $tocHtml . '<div class="result-body">' . $rendered . '</div>' . $previewOverlayHtml;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>{$title}</title>
<style>
:root{
  --bg:#080614;--card-bg:rgba(18,14,38,.88);--border:rgba(198,165,90,.12);
  --gold:#c6a55a;--gold-glow:#e8d390;--gold-muted:#8a7340;
  --text:#e4ddcc;--text-dim:#8a8070;--text-bright:#faf6ea;
  --danger:#e0706a;--r-xs:8px;--r-sm:12px;--r-md:18px;--r-lg:24px;--ease:cubic-bezier(.4,0,.2,1);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{
  font-family:-apple-system,BlinkMacSystemFont,'SF Pro Display','PingFang SC','Noto Sans SC','Microsoft YaHei',sans-serif;
  background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;
  -webkit-font-smoothing:antialiased;-webkit-tap-highlight-color:transparent;
}
.bg-layer{position:fixed;inset:0;pointer-events:none;z-index:0}
.bg-layer .orb{position:absolute;border-radius:50%;filter:blur(80px)}
.bg-layer .orb-1{width:380px;height:380px;background:radial-gradient(circle,rgba(198,165,90,.06),transparent 70%);top:-100px;left:-120px}
.bg-layer .orb-2{width:260px;height:260px;background:radial-gradient(circle,rgba(120,100,180,.04),transparent 70%);bottom:-60px;right:-60px}
.bg-layer .grid{position:absolute;inset:0;
  background-image:linear-gradient(rgba(198,165,90,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(198,165,90,.02) 1px,transparent 1px);
  background-size:48px 48px;-webkit-mask-image:radial-gradient(ellipse at center,black 30%,transparent 70%);mask-image:radial-gradient(ellipse at center,black 30%,transparent 70%)}

.container{position:relative;z-index:1;width:100%;margin:0 auto;padding:20px 10px 48px}

/* Header */
.page-header{text-align:center;padding:20px 0 12px}
.page-header .brand-mark{display:inline-flex;width:40px;height:40px;align-items:center;justify-content:center;
  background:radial-gradient(circle at 30% 30%,rgba(198,165,90,.2),transparent 70%);border-radius:50%;font-size:24px;color:var(--gold-glow)}
.page-header h1{font-size:22px;font-weight:800;letter-spacing:3px;margin-top:6px;
  background:linear-gradient(180deg,var(--gold-glow),var(--gold),var(--gold-muted));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.page-header .sub{font-size:11px;color:var(--text-dim);letter-spacing:1px;margin-top:4px}

/* Member badge */
.member-badge{
  position:absolute;top:16px;right:14px;display:none;align-items:center;gap:6px;
  font-size:11px;color:var(--text-dim);letter-spacing:.5px;
  padding:4px 10px 4px 4px;border-radius:20px;
  background:rgba(198,165,90,.04);
}
.member-badge.show{display:flex;}
.member-badge .avatar{
  width:28px;height:28px;border-radius:50%;object-fit:cover;
  border:1.5px solid rgba(198,165,90,.25);
  background:rgba(198,165,90,.1);
}
.member-badge .avatar-fallback{
  width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  background:rgba(198,165,90,.1);border:1.5px solid rgba(198,165,90,.25);
  font-size:14px;color:var(--gold-glow);
}
.page-header{position:relative}

/* Card */
.card{background:var(--card-bg);border-radius:var(--r-lg);
  padding:24px 12px;backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
  box-shadow:0 1px 2px rgba(0,0,0,.18),0 8px 40px rgba(0,0,0,.35),inset 0 1px 0 rgba(255,255,255,.015)}

/* Status messages */
.status-msg{padding:40px 20px;text-align:center;font-size:15px;line-height:1.7}
.status-msg.error{color:var(--danger)}

/* Pending view */
.pending-view{text-align:center;padding:28px 0}
.pending-icon{font-size:52px;margin-bottom:12px;animation:pulse 2s ease-in-out infinite}
.pending-title{font-size:20px;color:var(--gold-glow);font-weight:700;letter-spacing:1px;margin-bottom:8px}
.pending-desc{font-size:14px;color:var(--text-dim);line-height:1.6}
.pending-sub{font-size:12px;color:rgba(138,128,112,.6);margin-top:8px}
.order-tag{display:inline-block;margin-top:16px;padding:6px 16px;background:rgba(198,165,90,.08);border-radius:16px;font-size:11px;color:var(--text-dim);letter-spacing:.5px}

/* Input card */
.input-card{background:rgba(198,165,90,.05);border-radius:var(--r-sm);padding:14px 16px;margin-bottom:16px}
.input-card-title{font-size:13px;color:var(--gold);font-weight:600;margin-bottom:10px;letter-spacing:.5px}
.input-row{display:flex;justify-content:space-between;font-size:13px;color:var(--text-dim);padding:4px 0}
.input-row .lbl{color:rgba(138,128,112,.5);font-size:11px;min-width:40px}

/* Collapsible (done state) */
.input-details{margin-bottom:16px;font-size:13px;color:var(--text-dim)}
.input-details summary{cursor:pointer;color:var(--gold);font-weight:600;padding:8px 0;letter-spacing:.5px;outline:none;list-style:none}
.input-details summary::-webkit-details-marker{display:none}
.input-details[open] summary{margin-bottom:8px}

/* Meta bar */
.meta-bar{display:flex;justify-content:center;gap:16px;flex-wrap:wrap;font-size:11px;color:var(--text-dim);padding-bottom:16px;margin-bottom:16px;border-bottom:1px solid var(--border)}

/* Result body */
.result-body{font-size:15px;line-height:1.85;color:var(--text)}
.result-body p{margin:6px 0}
.result-body h2.md-h2{color:var(--gold);font-size:17px;margin:28px 0 12px;padding-bottom:8px;border-bottom:2px solid rgba(198,165,90,.2);font-weight:700;letter-spacing:1px}
.result-body h3{color:var(--gold-glow);font-size:15px;margin:18px 0 8px;font-weight:600}
.result-body strong{color:var(--gold-glow);font-weight:600}
.result-body em{color:var(--text-bright);font-style:italic}

/* ===== Enhanced Markdown Elements ===== */

/* Section Card */
.section-card{background:rgba(198,165,90,.025);border-radius:var(--r-sm);padding:14px 10px;margin-bottom:14px;transition:background .3s var(--ease)}
.section-card:hover{background:rgba(198,165,90,.04)}

/* Table */
.md-table-wrap{overflow-x:auto;margin:14px 0;border-radius:var(--r-xs);border:1px solid rgba(198,165,90,.1)}
.result-body table{width:100%;border-collapse:collapse;font-size:13px}
.result-body table td,.result-body table th{padding:8px 10px;border-bottom:1px solid rgba(198,165,90,.08);text-align:left;vertical-align:top}
.result-body table th{background:rgba(198,165,90,.08);color:var(--gold);font-weight:600;font-size:12px;letter-spacing:.5px;white-space:nowrap}
.result-body table tr:nth-child(even) td{background:rgba(198,165,90,.02)}
.result-body table tr:hover td{background:rgba(198,165,90,.05)}

/* Blockquote */
.result-body blockquote{margin:14px 0;padding:12px 16px;border-left:3px solid var(--gold);background:rgba(198,165,90,.04);border-radius:0 var(--r-xs) var(--r-xs) 0;font-size:14px;color:var(--text-dim);font-style:italic}
.result-body blockquote p{margin:4px 0}

/* Lists */
.result-body ul,.result-body ol{margin:10px 0;padding-left:22px}
.result-body li{padding:3px 0;color:var(--text);line-height:1.7}
.result-body li::marker{color:var(--gold-muted)}

/* Code */
.result-body code{background:rgba(0,0,0,.35);color:#d4b870;padding:2px 6px;border-radius:3px;font-size:13px;font-family:'SF Mono','Fira Code','Consolas',monospace;word-break:break-all}

/* Horizontal Rule */
.result-body hr{border:none;height:1px;background:linear-gradient(90deg,transparent,var(--border),transparent);margin:20px 0}

/* ===== TOC Navigation ===== */
.toc-nav{margin-bottom:16px;background:rgba(198,165,90,.04);border-radius:var(--r-sm);padding:12px 16px}
.toc-nav summary{cursor:pointer;color:var(--gold);font-weight:600;font-size:14px;outline:none;letter-spacing:.5px;list-style:none}
.toc-nav summary::-webkit-details-marker{display:none}
.toc-nav[open] summary{margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid rgba(198,165,90,.08)}
.toc-nav ul{list-style:none;padding:0;margin:0}
.toc-nav li{padding:4px 0}
.toc-nav li a{color:var(--text-dim);text-decoration:none;font-size:13px;transition:color .2s var(--ease);display:block;padding:2px 0}
.toc-nav li a:hover{color:var(--gold-glow)}

/* Buttons */
.btn-back{display:block;width:70%;margin:24px auto 0;padding:13px;background:transparent;border:1px solid var(--border);border-radius:var(--r-sm);
  color:var(--text-dim);font-size:14px;text-align:center;cursor:pointer;letter-spacing:1px;transition:all .25s var(--ease);text-decoration:none}
.btn-back:hover{border-color:var(--gold-muted);color:var(--gold)}
.btn-refresh{display:block;width:70%;margin:8px auto 0;padding:13px;background:var(--card-bg);border:1px solid var(--border);border-radius:var(--r-sm);
  color:var(--text-dim);font-size:14px;text-align:center;cursor:pointer;text-decoration:none;transition:all .25s var(--ease)}
.btn-refresh:hover{border-color:var(--gold-muted);color:var(--gold)}

/* Pay unlock button */
.btn-pay-unlock{display:inline-block;padding:14px 32px;font-size:15px;font-weight:700;letter-spacing:1px;
  border:none;border-radius:var(--r-sm);cursor:pointer;text-decoration:none;
  background:linear-gradient(135deg,#a07828 0%,#c6a55a 30%,#d4b870 60%,#c6a55a 100%);
  color:#1a1008;box-shadow:0 4px 20px rgba(198,165,90,.25),inset 0 1px 0 rgba(255,255,255,.15);
  transition:all .3s var(--ease)}
.btn-pay-unlock:active{transform:scale(.97)}

/* Footer */
.page-footer{text-align:center;padding:16px 0;font-size:11px;color:rgba(138,128,112,.35);letter-spacing:.5px;line-height:1.8}

@keyframes pulse{0%,100%{opacity:.6;transform:scale(1)}50%{opacity:1;transform:scale(1.05)}}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.{$bodyClass} .card{animation:fadeIn .5s var(--ease)}
</style>
</head>
<body class="{$bodyClass}">
<div class="bg-layer"><div class="orb orb-1"></div><div class="orb orb-2"></div><div class="grid"></div></div>
<div class="container">
  <div class="page-header">
    <div class="brand-mark">☯</div>
    <h1>{$title}</h1>
    <p class="sub">AI 命理分析 · 豆包大模型</p>
    {$memberBadgeHtml}
  </div>
  <div class="card">
    {$inputHtml}
    {$infoHtml}
    {$content}
  </div>
  <a class="btn-back" href="javascript:window.close();">关闭页面</a>
  <a class="btn-refresh" href="javascript:location.reload();">刷新状态</a>
  <div class="page-footer">
    <p>命理学为概率性倾向描述，仅供参考</p>
    <p>Powered by 豆包 Seed 2.0 Pro</p>
  </div>
</div>
</body>
</html>
HTML;
    }
}
