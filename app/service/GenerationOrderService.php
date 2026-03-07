<?php
/**
 * 生成订单服务类
 * 处理照片/视频生成的订单管理和退款逻辑
 */
namespace app\service;

use think\facade\Db;
use think\facade\Log;
use app\model\GenerationRecord;

class GenerationOrderService
{
    /**
     * 支付状态常量
     */
    const PAY_STATUS_PENDING = 0;   // 待支付
    const PAY_STATUS_PAID = 1;      // 已支付
    const PAY_STATUS_CANCELLED = 2; // 已取消
    
    /**
     * 退款状态常量
     */
    const REFUND_STATUS_NONE = 0;     // 无退款
    const REFUND_STATUS_PENDING = 1;  // 待审核
    const REFUND_STATUS_SUCCESS = 2;  // 已退款
    const REFUND_STATUS_REJECTED = 3; // 已驳回
    
    /**
     * 任务状态常量（与 GenerationRecord 保持一致）
     */
    const TASK_STATUS_PENDING = 0;    // 待处理
    const TASK_STATUS_PROCESSING = 1; // 处理中
    const TASK_STATUS_SUCCESS = 2;    // 成功
    const TASK_STATUS_FAILED = 3;     // 失败
    const TASK_STATUS_CANCELLED = 4;  // 已取消
    
    /**
     * 生成类型常量
     */
    const TYPE_PHOTO = 1; // 照片生成
    const TYPE_VIDEO = 2; // 视频生成
    
    /**
     * 创建生成订单
     * @param array $data [aid, bid, mid, scene_id, generation_type, member_level_id]
     * @return array
     */
    public function createOrder($data)
    {
        $aid = $data['aid'] ?? 0;
        $bid = $data['bid'] ?? 0;
        $mid = $data['mid'] ?? 0;
        $sceneId = $data['scene_id'] ?? 0;
        $generationType = $data['generation_type'] ?? self::TYPE_PHOTO;
        $memberLevelId = $data['member_level_id'] ?? 0;
        
        if (!$sceneId) {
            return ['status' => 0, 'msg' => '请选择场景模板'];
        }
        
        // 查询场景模板
        $template = Db::name('generation_scene_template')
            ->where('id', $sceneId)
            ->where('status', 1)
            ->find();
        
        if (!$template) {
            return ['status' => 0, 'msg' => '场景模板不存在或已下架'];
        }
        
        // 计算价格
        $generationService = new GenerationService();
        $priceInfo = $generationService->calculateTemplatePrice($template, $memberLevelId);
        $payPrice = floatval($priceInfo['price']);
        $basePrice = floatval($priceInfo['base_price']);
        
        // 生成唯一订单号
        $ordernum = $this->generateOrdernum($generationType);
        
        Db::startTrans();
        try {
            // 插入订单记录
            $orderData = [
                'aid' => $aid,
                'bid' => $bid,
                'mid' => $mid,
                'ordernum' => $ordernum,
                'generation_type' => $generationType,
                'scene_id' => $sceneId,
                'scene_name' => $template['template_name'],
                'total_price' => $basePrice,
                'pay_price' => $payPrice,
                'pay_status' => self::PAY_STATUS_PENDING,
                'refund_status' => self::REFUND_STATUS_NONE,
                'task_status' => self::TASK_STATUS_PENDING,
                'status' => 1,
                'createtime' => time(),
                'updatetime' => time()
            ];
            
            $orderId = Db::name('generation_order')->insertGetId($orderData);
            
            // 如果是免费模板（pay_price = 0），直接标记为已支付并触发生成任务
            if ($payPrice <= 0) {
                Db::name('generation_order')->where('id', $orderId)->update([
                    'pay_status' => self::PAY_STATUS_PAID,
                    'pay_time' => time(),
                    'paytype' => '免费',
                    'updatetime' => time()
                ]);
                
                Db::commit();
                
                // 触发生成任务
                $this->triggerGenerationTask($orderId);
                
                return [
                    'status' => 1,
                    'msg' => '订单创建成功',
                    'data' => [
                        'order_id' => $orderId,
                        'ordernum' => $ordernum,
                        'pay_price' => $payPrice,
                        'is_free' => true
                    ]
                ];
            }
            
            // 创建支付订单
            $payorderData = [
                'aid' => $aid,
                'bid' => $bid,
                'mid' => $mid,
                'ordernum' => $ordernum,
                'orderid' => $orderId,
                'tablename' => 'generation',
                'title' => ($generationType == self::TYPE_PHOTO ? '照片生成' : '视频生成') . '-' . $template['template_name'],
                'money' => $payPrice,
                'status' => 0,
                'createtime' => time()
            ];
            
            $payorderId = Db::name('payorder')->insertGetId($payorderData);
            
            // 更新订单的 payorderid
            Db::name('generation_order')->where('id', $orderId)->update([
                'payorderid' => $payorderId,
                'updatetime' => time()
            ]);
            
            Db::commit();
            
            return [
                'status' => 1,
                'msg' => '订单创建成功',
                'data' => [
                    'order_id' => $orderId,
                    'ordernum' => $ordernum,
                    'pay_price' => $payPrice,
                    'payorder_id' => $payorderId,
                    'is_free' => false
                ]
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创建生成订单失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '订单创建失败'];
        }
    }
    
    /**
     * 支付成功回调处理
     * @param string $ordernum 订单编号
     * @param array $payInfo 支付信息
     * @return array
     */
    public function onPaid($ordernum, $payInfo = [])
    {
        $order = Db::name('generation_order')
            ->where('ordernum', $ordernum)
            ->where('pay_status', self::PAY_STATUS_PENDING)
            ->find();
        
        if (!$order) {
            return ['status' => 0, 'msg' => '订单不存在或已支付'];
        }
        
        Db::startTrans();
        try {
            // 更新订单支付状态
            $updateData = [
                'pay_status' => self::PAY_STATUS_PAID,
                'pay_time' => time(),
                'paytypeid' => $payInfo['paytypeid'] ?? 0,
                'paytype' => $payInfo['paytype'] ?? '',
                'paynum' => $payInfo['paynum'] ?? '',
                'transaction_id' => $payInfo['transaction_id'] ?? '',
                'updatetime' => time()
            ];
            
            Db::name('generation_order')->where('id', $order['id'])->update($updateData);
            
            Db::commit();
            
            // 触发生成任务
            $this->triggerGenerationTask($order['id']);
            
            return ['status' => 1, 'msg' => '支付成功'];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('处理支付回调失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '处理失败'];
        }
    }
    
    /**
     * 触发生成任务
     * @param int $orderId 订单ID
     * @return array
     */
    public function triggerGenerationTask($orderId)
    {
        $order = Db::name('generation_order')->where('id', $orderId)->find();
        if (!$order || $order['pay_status'] != self::PAY_STATUS_PAID) {
            return ['status' => 0, 'msg' => '订单状态异常'];
        }
        
        // 获取场景模板
        $template = Db::name('generation_scene_template')
            ->where('id', $order['scene_id'])
            ->find();
        
        if (!$template) {
            return ['status' => 0, 'msg' => '场景模板不存在'];
        }
        
        // 准备生成参数
        $defaultParams = is_string($template['default_params']) 
            ? json_decode($template['default_params'], true) 
            : ($template['default_params'] ?: []);
        
        // 调用 GenerationService 创建任务
        $generationService = new GenerationService();
        $result = $generationService->createTask([
            'aid' => $order['aid'],
            'bid' => $order['bid'],
            'uid' => 0, // 用户端创建，uid 为 0
            'mid' => $order['mid'],
            'generation_type' => $order['generation_type'],
            'model_id' => $template['model_id'],
            'scene_id' => $order['scene_id'],
            'input_params' => $defaultParams,
            'order_id' => $orderId
        ]);
        
        if ($result['status'] == 1) {
            // 更新订单的生成记录ID和任务状态
            Db::name('generation_order')->where('id', $orderId)->update([
                'record_id' => $result['record_id'],
                'task_status' => self::TASK_STATUS_PROCESSING,
                'updatetime' => time()
            ]);
        }
        
        return $result;
    }
    
    /**
     * 同步任务状态到订单
     * @param int $recordId 生成记录ID
     * @param int $status 任务状态
     * @return bool
     */
    public function syncTaskStatus($recordId, $status)
    {
        // 查询关联的订单
        $order = Db::name('generation_order')
            ->where('record_id', $recordId)
            ->find();
        
        if (!$order) {
            // 也尝试通过 generation_record 的 order_id 查找
            $record = Db::name('generation_record')->where('id', $recordId)->find();
            if ($record && $record['order_id'] > 0) {
                $order = Db::name('generation_order')
                    ->where('id', $record['order_id'])
                    ->find();
            }
        }
        
        if (!$order) {
            return false;
        }
        
        // 更新订单的任务状态
        Db::name('generation_order')->where('id', $order['id'])->update([
            'task_status' => $status,
            'updatetime' => time()
        ]);
        
        return true;
    }
    
    /**
     * 申请退款
     * @param int $orderId 订单ID
     * @param int $mid 会员ID
     * @param string $reason 退款原因
     * @return array
     */
    public function applyRefund($orderId, $mid, $reason)
    {
        $order = Db::name('generation_order')
            ->where('id', $orderId)
            ->where('mid', $mid)
            ->where('status', 1)
            ->find();
        
        if (!$order) {
            return ['status' => 0, 'msg' => '订单不存在'];
        }
        
        // 校验订单已支付
        if ($order['pay_status'] != self::PAY_STATUS_PAID) {
            return ['status' => 0, 'msg' => '订单未支付，无需退款'];
        }
        
        // 校验生成任务状态为失败
        if ($order['task_status'] != self::TASK_STATUS_FAILED) {
            return ['status' => 0, 'msg' => '生成成功的订单不支持退款'];
        }
        
        // 校验退款状态
        if ($order['refund_status'] == self::REFUND_STATUS_PENDING) {
            return ['status' => 0, 'msg' => '退款申请已提交，请等待审核'];
        }
        if ($order['refund_status'] == self::REFUND_STATUS_SUCCESS) {
            return ['status' => 0, 'msg' => '订单已退款，请勿重复申请'];
        }
        
        // 提交退款申请
        Db::name('generation_order')->where('id', $orderId)->update([
            'refund_status' => self::REFUND_STATUS_PENDING,
            'refund_reason' => $reason,
            'refund_money' => $order['pay_price'], // 全额退款
            'updatetime' => time()
        ]);
        
        return ['status' => 1, 'msg' => '退款申请已提交'];
    }
    
    /**
     * 撤销退款申请
     * @param int $orderId 订单ID
     * @param int $mid 会员ID
     * @return array
     */
    public function cancelRefund($orderId, $mid)
    {
        $order = Db::name('generation_order')
            ->where('id', $orderId)
            ->where('mid', $mid)
            ->where('status', 1)
            ->find();
        
        if (!$order) {
            return ['status' => 0, 'msg' => '订单不存在'];
        }
        
        if ($order['refund_status'] != self::REFUND_STATUS_PENDING) {
            return ['status' => 0, 'msg' => '当前状态不可撤销'];
        }
        
        Db::name('generation_order')->where('id', $orderId)->update([
            'refund_status' => self::REFUND_STATUS_NONE,
            'refund_reason' => null,
            'refund_money' => 0,
            'updatetime' => time()
        ]);
        
        return ['status' => 1, 'msg' => '撤销成功'];
    }
    
    /**
     * 审核退款
     * @param int $orderId 订单ID
     * @param int $st 审核结果：1=同意，2=驳回
     * @param string $remark 审核备注
     * @param int $aid 平台ID
     * @param int $bid 商户ID
     * @return array
     */
    public function checkRefund($orderId, $st, $remark, $aid = 0, $bid = 0)
    {
        $where = [['id', '=', $orderId]];
        if ($aid > 0) {
            $where[] = ['aid', '=', $aid];
        }
        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }
        
        $order = Db::name('generation_order')->where($where)->find();
        
        if (!$order) {
            return ['status' => 0, 'msg' => '订单不存在'];
        }
        
        if ($order['refund_status'] != self::REFUND_STATUS_PENDING) {
            return ['status' => 0, 'msg' => '该订单不在待审核状态'];
        }
        
        if ($st == 2) {
            // 驳回退款
            Db::name('generation_order')->where('id', $orderId)->update([
                'refund_status' => self::REFUND_STATUS_REJECTED,
                'refund_checkremark' => $remark,
                'updatetime' => time()
            ]);
            
            // 发送驳回通知
            $this->sendRefundRejectNotification($order, $remark);
            
            return ['status' => 1, 'msg' => '退款已驳回'];
        }
        
        // 同意退款
        $result = $this->executeRefund($order);
        
        if ($result['status'] == 1) {
            Db::name('generation_order')->where('id', $orderId)->update([
                'refund_status' => self::REFUND_STATUS_SUCCESS,
                'refund_time' => time(),
                'refund_checkremark' => $remark,
                'updatetime' => time()
            ]);
            
            // 发送退款成功通知
            $this->sendRefundSuccessNotification($order);
            
            return ['status' => 1, 'msg' => '退款成功'];
        }
        
        return $result;
    }
    
    /**
     * 执行退款
     * @param array $order 订单信息
     * @return array
     */
    protected function executeRefund($order)
    {
        $refundMoney = floatval($order['refund_money']);
        if ($refundMoney <= 0) {
            return ['status' => 0, 'msg' => '退款金额错误'];
        }
        
        // 查询支付订单
        $payorder = Db::name('payorder')
            ->where('ordernum', $order['ordernum'])
            ->find();
        
        if (!$payorder) {
            // 如果是免费订单或余额支付
            if ($order['paytypeid'] == 1 || $order['paytype'] == '余额') {
                // 退回余额
                $result = \app\common\Member::addmoney(
                    $order['aid'],
                    $order['mid'],
                    $refundMoney,
                    ($order['generation_type'] == self::TYPE_PHOTO ? '照片生成' : '视频生成') . '订单退款',
                    0
                );
                return ['status' => 1, 'msg' => '余额退款成功'];
            }
            return ['status' => 0, 'msg' => '支付记录不存在'];
        }
        
        // 构建退款所需的订单数据
        $refundOrderData = [
            'aid' => $order['aid'],
            'bid' => $order['bid'],
            'mid' => $order['mid'],
            'ordernum' => $order['ordernum'],
            'totalprice' => $payorder['money'],
            'paytypeid' => $order['paytypeid'],
            'platform' => $payorder['platform'] ?? 'mp'
        ];
        
        // 调用通用退款方法
        $reason = ($order['generation_type'] == self::TYPE_PHOTO ? '照片生成' : '视频生成') . '订单退款';
        $result = \app\common\Order::refund($refundOrderData, $refundMoney, $reason);
        
        return $result;
    }
    
    /**
     * 获取订单列表
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param string $order 排序
     * @return array
     */
    public function getOrderList($where, $page = 1, $limit = 20, $order = 'o.id desc')
    {
        $count = Db::name('generation_order')
            ->alias('o')
            ->leftJoin('member m', 'o.mid = m.id')
            ->leftJoin('generation_scene_template t', 'o.scene_id = t.id')
            ->where($where)
            ->count();
        
        $list = Db::name('generation_order')
            ->alias('o')
            ->leftJoin('member m', 'o.mid = m.id')
            ->leftJoin('generation_scene_template t', 'o.scene_id = t.id')
            ->leftJoin('generation_record r', 'o.record_id = r.id')
            ->field('o.*, m.nickname, m.headimg, m.tel as member_tel, t.template_name, t.cover_image, r.create_time as record_create_time')
            ->where($where)
            ->page($page, $limit)
            ->order($order)
            ->select()
            ->toArray();
        
        // 格式化数据
        foreach ($list as &$item) {
            // 优先使用生成记录的创建时间，如果没有则使用订单创建时间
            $displayTime = $item['record_create_time'] ?: $item['createtime'];
            $item['createtime_text'] = $displayTime ? date('Y-m-d H:i:s', $displayTime) : '-';
            $item['pay_time_text'] = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : '-';
            $item['refund_time_text'] = $item['refund_time'] ? date('Y-m-d H:i:s', $item['refund_time']) : '-';
            $item['pay_status_text'] = $this->getPayStatusText($item['pay_status']);
            $item['refund_status_text'] = $this->getRefundStatusText($item['refund_status']);
            $item['task_status_text'] = $this->getTaskStatusText($item['task_status']);
            $item['generation_type_text'] = $item['generation_type'] == self::TYPE_PHOTO ? '照片生成' : '视频生成';
        }
        
        return ['count' => $count, 'data' => $list];
    }
    
    /**
     * 获取订单详情
     * @param int $orderId 订单ID
     * @param int $aid 平台ID
     * @param int $bid 商户ID
     * @return array|null
     */
    public function getOrderDetail($orderId, $aid = 0, $bid = 0)
    {
        $where = [['o.id', '=', $orderId]];
        if ($aid > 0) {
            $where[] = ['o.aid', '=', $aid];
        }
        if ($bid > 0) {
            $where[] = ['o.bid', '=', $bid];
        }
        
        $order = Db::name('generation_order')
            ->alias('o')
            ->leftJoin('member m', 'o.mid = m.id')
            ->leftJoin('generation_scene_template t', 'o.scene_id = t.id')
            ->leftJoin('generation_record r', 'o.record_id = r.id')
            ->field('o.*, m.nickname, m.headimg, m.tel as member_tel, t.template_name, t.cover_image, t.description as scene_description, r.create_time as record_create_time')
            ->where($where)
            ->find();
        
        if (!$order) {
            return null;
        }
        
        // 格式化，优先使用生成记录的创建时间
        $displayTime = $order['record_create_time'] ?: $order['createtime'];
        $order['createtime_text'] = $displayTime ? date('Y-m-d H:i:s', $displayTime) : '-';
        $order['pay_time_text'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '-';
        $order['refund_time_text'] = $order['refund_time'] ? date('Y-m-d H:i:s', $order['refund_time']) : '-';
        $order['pay_status_text'] = $this->getPayStatusText($order['pay_status']);
        $order['refund_status_text'] = $this->getRefundStatusText($order['refund_status']);
        $order['task_status_text'] = $this->getTaskStatusText($order['task_status']);
        $order['generation_type_text'] = $order['generation_type'] == self::TYPE_PHOTO ? '照片生成' : '视频生成';
        
        // 获取关联的生成记录
        if ($order['record_id'] > 0) {
            $record = Db::name('generation_record')
                ->alias('r')
                ->leftJoin('model_info mi', 'r.model_id = mi.id')
                ->field('r.*, mi.model_name')
                ->where('r.id', $order['record_id'])
                ->find();
            if ($record) {
                $record['status_text'] = $this->getTaskStatusText($record['status']);
                $record['create_time_text'] = $record['create_time'] ? date('Y-m-d H:i:s', $record['create_time']) : '-';
                $record['finish_time_text'] = $record['finish_time'] ? date('Y-m-d H:i:s', $record['finish_time']) : '-';
                
                // 获取输出结果
                $outputs = Db::name('generation_output')
                    ->where('record_id', $record['id'])
                    ->select()
                    ->toArray();
                $record['outputs'] = $outputs;
            }
            $order['record'] = $record;
        }
        
        return $order;
    }
    
    /**
     * 用户端获取订单列表
     * @param int $aid 平台ID
     * @param int $mid 会员ID
     * @param int $generationType 生成类型（0=全部）
     * @param int $status 状态筛选
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getUserOrderList($aid, $mid, $generationType = 0, $status = -1, $page = 1, $limit = 20)
    {
        $where = [
            ['o.aid', '=', $aid],
            ['o.mid', '=', $mid],
            ['o.status', '=', 1]
        ];
        
        if ($generationType > 0) {
            $where[] = ['o.generation_type', '=', $generationType];
        }
        
        // 状态筛选
        if ($status >= 0) {
            switch ($status) {
                case 0: // 待支付
                    $where[] = ['o.pay_status', '=', self::PAY_STATUS_PENDING];
                    break;
                case 1: // 生成中
                    $where[] = ['o.pay_status', '=', self::PAY_STATUS_PAID];
                    $where[] = ['o.task_status', 'in', [self::TASK_STATUS_PENDING, self::TASK_STATUS_PROCESSING]];
                    break;
                case 2: // 已完成
                    $where[] = ['o.pay_status', '=', self::PAY_STATUS_PAID];
                    $where[] = ['o.task_status', '=', self::TASK_STATUS_SUCCESS];
                    break;
                case 3: // 退款相关
                    $where[] = ['o.refund_status', '>', 0];
                    break;
            }
        }
        
        return $this->getOrderList($where, $page, $limit, 'o.id desc');
    }
    
    /**
     * 生成唯一订单号
     * @param int $generationType 生成类型
     * @return string
     */
    protected function generateOrdernum($generationType)
    {
        $prefix = $generationType == self::TYPE_PHOTO ? 'PG' : 'VG';
        return $prefix . date('YmdHis') . rand(1000, 9999);
    }
    
    /**
     * 获取支付状态文本
     */
    protected function getPayStatusText($status)
    {
        $map = [
            self::PAY_STATUS_PENDING => '待支付',
            self::PAY_STATUS_PAID => '已支付',
            self::PAY_STATUS_CANCELLED => '已取消'
        ];
        return $map[$status] ?? '未知';
    }
    
    /**
     * 获取退款状态文本
     */
    protected function getRefundStatusText($status)
    {
        $map = [
            self::REFUND_STATUS_NONE => '无退款',
            self::REFUND_STATUS_PENDING => '待审核',
            self::REFUND_STATUS_SUCCESS => '已退款',
            self::REFUND_STATUS_REJECTED => '已驳回'
        ];
        return $map[$status] ?? '未知';
    }
    
    /**
     * 获取任务状态文本
     */
    protected function getTaskStatusText($status)
    {
        $map = [
            self::TASK_STATUS_PENDING => '待处理',
            self::TASK_STATUS_PROCESSING => '处理中',
            self::TASK_STATUS_SUCCESS => '成功',
            self::TASK_STATUS_FAILED => '失败',
            self::TASK_STATUS_CANCELLED => '已取消'
        ];
        return $map[$status] ?? '未知';
    }
    
    /**
     * 发送退款驳回通知
     */
    protected function sendRefundRejectNotification($order, $remark)
    {
        $member = Db::name('member')->where('id', $order['mid'])->find();
        if (!$member) return;
        
        $typeName = $order['generation_type'] == self::TYPE_PHOTO ? '照片生成' : '视频生成';
        
        // 微信模板消息
        $tmplcontent = [];
        $tmplcontent['first'] = '您的' . $typeName . '订单退款申请被驳回';
        $tmplcontent['keyword1'] = $order['ordernum'];
        $tmplcontent['keyword2'] = $order['pay_price'] . '元';
        $tmplcontent['remark'] = $remark ?: '请联系客服了解详情';
        
        try {
            \app\common\Wechat::sendtmpl($order['aid'], $order['mid'], 'tmpl_tuierror', $tmplcontent);
        } catch (\Exception $e) {
            Log::error('发送退款驳回通知失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 发送退款成功通知
     */
    protected function sendRefundSuccessNotification($order)
    {
        $member = Db::name('member')->where('id', $order['mid'])->find();
        if (!$member) return;
        
        $typeName = $order['generation_type'] == self::TYPE_PHOTO ? '照片生成' : '视频生成';
        
        // 微信模板消息
        $tmplcontent = [];
        $tmplcontent['first'] = '您的' . $typeName . '订单退款成功';
        $tmplcontent['keyword1'] = $order['ordernum'];
        $tmplcontent['keyword2'] = $order['refund_money'] . '元';
        $tmplcontent['remark'] = '退款金额将原路退回，请注意查收';
        
        try {
            \app\common\Wechat::sendtmpl($order['aid'], $order['mid'], 'tmpl_tuisuccess', $tmplcontent);
        } catch (\Exception $e) {
            Log::error('发送退款成功通知失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 创建生成订单（支持自定义参数 - 用户提示词、参考图等）
     * @param array $data [aid, bid, mid, scene_id, generation_type, member_level_id, user_prompt, ref_images, quantity]
     * @return array
     */
    public function createOrderWithParams($data)
    {
        $aid = $data['aid'] ?? 0;
        $bid = $data['bid'] ?? 0;
        $mid = $data['mid'] ?? 0;
        $sceneId = $data['scene_id'] ?? 0;
        $generationType = $data['generation_type'] ?? self::TYPE_PHOTO;
        $memberLevelId = $data['member_level_id'] ?? 0;
        $userPrompt = $data['user_prompt'] ?? '';
        $refImages = $data['ref_images'] ?? [];
        $quantity = $data['quantity'] ?? 0;
        $ratio = $data['ratio'] ?? '';
        $quality = $data['quality'] ?? '';
        
        if (!$sceneId) {
            return ['status' => 0, 'msg' => '请选择场景模板'];
        }
        
        // 查询场景模板
        $template = Db::name('generation_scene_template')
            ->where('id', $sceneId)
            ->where('status', 1)
            ->find();
        
        if (!$template) {
            return ['status' => 0, 'msg' => '场景模板不存在或已下架'];
        }
        
        // 计算价格
        $generationService = new GenerationService();
        $priceInfo = $generationService->calculateTemplatePrice($template, $memberLevelId);
        $payPrice = floatval($priceInfo['price']);
        $basePrice = floatval($priceInfo['base_price']);
        
        // 生成唯一订单号
        $ordernum = $this->generateOrdernum($generationType);
        
        // 构建模板快照
        $templateSnapshot = json_encode([
            'id' => $template['id'],
            'template_name' => $template['template_name'],
            'model_id' => $template['model_id'],
            'capability_type' => $template['capability_type'] ?? 0,
            'default_params' => $template['default_params'],
            'base_price' => $template['base_price'],
        ], JSON_UNESCAPED_UNICODE);
        
        Db::startTrans();
        try {
            // 插入订单记录
            $orderData = [
                'aid' => $aid,
                'bid' => $bid,
                'mid' => $mid,
                'ordernum' => $ordernum,
                'generation_type' => $generationType,
                'scene_id' => $sceneId,
                'scene_name' => $template['template_name'],
                'total_price' => $basePrice,
                'pay_price' => $payPrice,
                'pay_status' => self::PAY_STATUS_PENDING,
                'refund_status' => self::REFUND_STATUS_NONE,
                'task_status' => self::TASK_STATUS_PENDING,
                'user_prompt' => $userPrompt,
                'ref_images' => !empty($refImages) ? json_encode($refImages, JSON_UNESCAPED_UNICODE) : '',
                'template_snapshot' => $templateSnapshot,
                'status' => 1,
                'createtime' => time(),
                'updatetime' => time()
            ];
            
            $orderId = Db::name('generation_order')->insertGetId($orderData);
            
            // 免费模板直接标记已支付
            if ($payPrice <= 0) {
                Db::name('generation_order')->where('id', $orderId)->update([
                    'pay_status' => self::PAY_STATUS_PAID,
                    'pay_time' => time(),
                    'paytype' => '免费',
                    'updatetime' => time()
                ]);
                
                Db::commit();
                
                // 触发生成任务（带用户参数）
                $this->triggerGenerationTaskWithParams($orderId, $userPrompt, $refImages, $quantity, $ratio, $quality);
                
                return [
                    'status' => 1,
                    'msg' => '订单创建成功',
                    'data' => [
                        'order_id' => $orderId,
                        'ordernum' => $ordernum,
                        'total_price' => $payPrice,
                        'need_pay' => false
                    ]
                ];
            }
            
            // 创建支付订单
            $payorderData = [
                'aid' => $aid,
                'bid' => $bid,
                'mid' => $mid,
                'ordernum' => $ordernum,
                'orderid' => $orderId,
                'tablename' => 'generation',
                'title' => ($generationType == self::TYPE_PHOTO ? '照片生成' : '视频生成') . '-' . $template['template_name'],
                'money' => $payPrice,
                'status' => 0,
                'createtime' => time()
            ];
            
            $payorderId = Db::name('payorder')->insertGetId($payorderData);
            
            Db::name('generation_order')->where('id', $orderId)->update([
                'payorderid' => $payorderId,
                'updatetime' => time()
            ]);
            
            Db::commit();
            
            return [
                'status' => 1,
                'msg' => '订单创建成功',
                'data' => [
                    'order_id' => $orderId,
                    'ordernum' => $ordernum,
                    'total_price' => $payPrice,
                    'payorder_id' => $payorderId,
                    'need_pay' => true
                ]
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创建生成订单失败: ' . $e->getMessage());
            return ['status' => 0, 'msg' => '订单创建失败'];
        }
    }
    
    /**
     * 触发生成任务（带用户自定义参数）
     * @param int $orderId 订单ID
     * @param string $userPrompt 用户提示词
     * @param array $refImages 参考图
     * @param int $quantity 生成数量
     * @return array
     */
    public function triggerGenerationTaskWithParams($orderId, $userPrompt = '', $refImages = [], $quantity = 0, $ratio = '', $quality = '')
    {
        $order = Db::name('generation_order')->where('id', $orderId)->find();
        if (!$order || $order['pay_status'] != self::PAY_STATUS_PAID) {
            return ['status' => 0, 'msg' => '订单状态异常'];
        }
        
        // 获取场景模板
        $template = Db::name('generation_scene_template')
            ->where('id', $order['scene_id'])
            ->find();
        
        if (!$template) {
            return ['status' => 0, 'msg' => '场景模板不存在'];
        }
        
        // 准备生成参数
        $defaultParams = is_string($template['default_params']) 
            ? json_decode($template['default_params'], true) 
            : ($template['default_params'] ?: []);
        
        $inputParams = $defaultParams;
        
        // 使用用户提示词覆盖默认提示词
        if (!empty($userPrompt)) {
            $inputParams['prompt'] = $userPrompt;
        } elseif (!empty($order['user_prompt'])) {
            $inputParams['prompt'] = $order['user_prompt'];
        }
        
        // 添加参考图
        if (empty($refImages) && !empty($order['ref_images'])) {
            $refImages = json_decode($order['ref_images'], true) ?: [];
        }
        if (!empty($refImages)) {
            if (count($refImages) == 1) {
                $inputParams['image'] = $refImages[0];
                $inputParams['first_frame_image'] = $refImages[0];
            } else {
                $inputParams['images'] = $refImages;
            }
        }
        
        // 设置生成数量
        if ($quantity > 0) {
            $inputParams['max_images'] = $quantity;
        }
        
        // 设置图片比例/尺寸（二维映射：ratio × quality → size）
        if (!empty($ratio)) {
            // 默认使用 hd 档位（向后兼容）
            if (empty($quality) || !in_array($quality, ['standard', 'hd', 'ultra'])) {
                $quality = 'hd';
            }
            $ratioSizeMap = [
                '1:1' => ['standard' => '512x512', 'hd' => '1024x1024', 'ultra' => '2048x2048'],
                '2:3' => ['standard' => '512x768', 'hd' => '1024x1536', 'ultra' => '2048x3072'],
                '3:2' => ['standard' => '768x512', 'hd' => '1536x1024', 'ultra' => '3072x2048'],
                '3:4' => ['standard' => '384x512', 'hd' => '768x1024', 'ultra' => '1536x2048'],
                '4:3' => ['standard' => '512x384', 'hd' => '1024x768', 'ultra' => '2048x1536'],
                '9:16' => ['standard' => '360x640', 'hd' => '720x1280', 'ultra' => '1440x2560'],
                '16:9' => ['standard' => '640x360', 'hd' => '1280x720', 'ultra' => '2560x1440'],
                '4:5' => ['standard' => '512x640', 'hd' => '1024x1280', 'ultra' => '2048x2560'],
                '5:4' => ['standard' => '640x512', 'hd' => '1280x1024', 'ultra' => '2560x2048'],
                '21:9' => ['standard' => '1260x540', 'hd' => '2520x1080', 'ultra' => '3780x1620'],
            ];
            if (isset($ratioSizeMap[$ratio][$quality])) {
                $inputParams['size'] = $ratioSizeMap[$ratio][$quality];
            } elseif (isset($ratioSizeMap[$ratio]['hd'])) {
                $inputParams['size'] = $ratioSizeMap[$ratio]['hd'];
            } else {
                $inputParams['size'] = $ratio;
            }
        }
        
        // 调用 GenerationService 创建任务
        $generationService = new GenerationService();
        $result = $generationService->createTask([
            'aid' => $order['aid'],
            'bid' => $order['bid'],
            'uid' => 0,
            'mid' => $order['mid'],
            'generation_type' => $order['generation_type'],
            'model_id' => $template['model_id'],
            'scene_id' => $order['scene_id'],
            'input_params' => $inputParams,
            'order_id' => $orderId
        ]);
        
        if ($result['status'] == 1) {
            Db::name('generation_order')->where('id', $orderId)->update([
                'record_id' => $result['record_id'],
                'task_status' => self::TASK_STATUS_PROCESSING,
                'updatetime' => time()
            ]);
        }
        
        return $result;
    }
    
    /**
     * 提交任务（支付后调用）
     * @param int $orderId 订单ID
     * @param int $mid 会员ID
     * @return array
     */
    public function submitTask($orderId, $mid)
    {
        $order = Db::name('generation_order')
            ->where('id', $orderId)
            ->where('mid', $mid)
            ->where('status', 1)
            ->find();
        
        if (!$order) {
            return ['status' => 0, 'msg' => '订单不存在'];
        }
        
        if ($order['pay_status'] != self::PAY_STATUS_PAID) {
            return ['status' => 0, 'msg' => '订单未支付'];
        }
        
        if ($order['task_status'] > self::TASK_STATUS_PENDING) {
            return ['status' => 0, 'msg' => '任务已提交'];
        }
        
        return $this->triggerGenerationTaskWithParams($orderId);
    }
}
