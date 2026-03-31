<?php
namespace app\controller;

use think\facade\Db;

/**
 * 统一订单聚合控制器
 * 将所有订单类型聚合到统一接口，供会员中心"我的订单"页面调用
 */
class ApiUnifiedOrder extends ApiCommon
{
    // 订单类型定义：[标识 => [中文名, 数据表名, 时间字段]]
    protected $orderTypes = [
        'shop'          => ['商城', 'shop_order', 'createtime'],
        'collage'       => ['拼团', 'collage_order', 'createtime'],
        'seckill'       => ['秒杀', 'seckill_order', 'createtime'],
        'tuangou'       => ['团购', 'tuangou_order', 'createtime'],
        'kanjia'        => ['砍价', 'kanjia_order', 'createtime'],
        'lucky_collage' => ['幸运拼团', 'lucky_collage_order', 'createtime'],
        'scoreshop'     => ['积分兑换', 'scoreshop_order', 'createtime'],
        'yuyue'         => ['预约', 'yuyue_order', 'createtime'],
        'kecheng'       => ['课程', 'kecheng_order', 'createtime'],
        'cycle'         => ['周期购', 'cycle_order', 'createtime'],
        'ai_pick'       => ['选片', 'ai_travel_photo_order', 'create_time'],
        'ai_image'      => ['AI写真', 'generation_order', 'createtime'],
        'ai_video'      => ['AI视频', 'generation_order', 'createtime'],
    ];

    // 详情页路由映射
    protected $detailRoutes = [
        'shop'          => '/pagesExt/order/detail?id=',
        'collage'       => '/activity/collage/orderdetail?id=',
        'seckill'       => '/activity/seckill/orderdetail?id=',
        'tuangou'       => '/activity/tuangou/orderdetail?id=',
        'kanjia'        => '/activity/kanjia/orderdetail?id=',
        'lucky_collage' => '/activity/luckycollage/orderdetail?id=',
        'scoreshop'     => '/activity/scoreshop/orderdetail?id=',
        'yuyue'         => '/activity/yuyue/orderdetail?id=',
        'kecheng'       => '/activity/kecheng/product?id=',
        'cycle'         => '/pagesExt/cycle/orderDetail?id=',
        'ai_pick'       => '/pagesExt/order/ai_pick_detail?id=',  // 选片订单专用详情页
        'ai_image'      => '/pagesZ/generation/orderdetail?id=',
        'ai_video'      => '/pagesZ/generation/orderdetail?id=',
    ];

    public function initialize()
    {
        parent::initialize();
        $this->checklogin();
    }

    /**
     * 统一订单列表
     */
    public function orderlist()
    {
        $st = input('param.st', 'all');
        $orderType = input('param.order_type', 'all');
        $keyword = input('param.keyword', '');
        $keyword = input('param.keyword', '');
        $pagenum = max(1, (int)input('param.pagenum', 1));
        $pernum = 10;

        // 指定了具体类型，直通单表查询
        if ($orderType !== 'all' && isset($this->orderTypes[$orderType])) {
            $result = $this->querySingleType($orderType, $st, $keyword, $pagenum, $pernum);
            return $this->json($result);
        }

        // 全类型聚合查询
        $result = $this->aggregateAllTypes($st, $keyword, $pagenum, $pernum);
        return $this->json($result);
    }

    /**
     * 统一订单数量统计
     */
    public function ordercount()
    {
        $counts = [
            'count0' => 0,  // 待付款
            'count1' => 0,  // 待发货
            'count2' => 0,  // 待收货
            'count3' => 0,  // 已完成
            'count_refund' => 0, // 退款/售后
        ];

        foreach ($this->orderTypes as $type => $config) {
            list($name, $table, $timeField) = $config;
            if (!$this->isModuleEnabled($type)) {
                continue;
            }

            $baseWhere = $this->getBaseWhere($type);

            // 待付款
            $c0 = $this->countWithStatus($type, $table, $baseWhere, '0');
            $counts['count0'] += $c0;

            // 待发货
            $c1 = $this->countWithStatus($type, $table, $baseWhere, '1');
            $counts['count1'] += $c1;

            // 待收货
            $c2 = $this->countWithStatus($type, $table, $baseWhere, '2');
            $counts['count2'] += $c2;

            // 已完成
            $c3 = $this->countWithStatus($type, $table, $baseWhere, '3');
            $counts['count3'] += $c3;

            // 退款/售后
            $cr = $this->countRefund($type, $table, $baseWhere);
            $counts['count_refund'] += $cr;
        }

        return $this->json($counts);
    }

    /**
     * 统一订单详情
     * 兼容选片订单等特殊订单类型的详情展示
     */
    public function detail()
    {
        $orderId = input('param.id', 0);

        if (!$orderId) {
            return $this->json(['status' => 0, 'msg' => '订单ID不能为空']);
        }

        // 先尝试从选片订单表查询
        // 选片订单用 uid 标识用户，且支持 openid 匹配
        $member = Db::name('member')->where('id', mid)->find();
        $openid = $member['wxopenid'] ?? $member['mpopenid'] ?? '';
        
        $query = Db::name('ai_travel_photo_order')
            ->where('id', $orderId)
            ->where('aid', aid);
        
        // 添加用户匹配条件（uid 或 openid）
        if ($openid && mid > 0) {
            // 注册用户：uid=mid OR openid=xxx
            $query->where(function($q) use ($openid) {
                $q->whereOr([
                    ['uid', '=', mid],
                    ['openid', '=', $openid]
                ]);
            });
        } else if (mid > 0) {
            // 只有 uid
            $query->where('uid', mid);
        } else if ($openid) {
            // 只有 openid
            $query->where('openid', $openid);
        }
        
        $order = $query->find();

        if ($order) {
            // 是选片订单，返回选片订单详情
            $detail = $this->getAiPickOrderDetail($order);
            return $this->json(['status' => 1, 'data' => $detail]);
        }

        // 尝试从商城订单表查询
        $order = Db::name('shop_order')
            ->where('id', $orderId)
            ->where('aid', aid)
            ->where('mid', mid)
            ->find();

        if (!$order) {
            return $this->json(['status' => 0, 'msg' => '订单不存在']);
        }

        // 调用商城订单详情方法
        $apiOrder = new \app\controller\ApiOrder($this->app);
        return $apiOrder->detail();
    }

    /**
     * 关闭订单
     */
    public function closeOrder()
    {
        $orderId = input('param.id', 0);

        if (!$orderId) {
            return $this->json(['status' => 0, 'msg' => '订单ID不能为空']);
        }

        // 先尝试从选片订单表查询
        // 选片订单用 uid 标识用户，且支持 openid 匹配
        $member = Db::name('member')->where('id', mid)->find();
        $openid = $member['wxopenid'] ?? $member['mpopenid'] ?? '';
        
        $query = Db::name('ai_travel_photo_order')
            ->where('id', $orderId)
            ->where('aid', aid);
        
        // 添加用户匹配条件（uid 或 openid）
        if ($openid && mid > 0) {
            // 注册用户：uid=mid OR openid=xxx
            $query->where(function($q) use ($openid) {
                $q->whereOr([
                    ['uid', '=', mid],
                    ['openid', '=', $openid]
                ]);
            });
        } else if (mid > 0) {
            // 只有 uid
            $query->where('uid', mid);
        } else if ($openid) {
            // 只有 openid
            $query->where('openid', $openid);
        }
        
        $order = $query->find();

        if ($order) {
            // 是选片订单
            if ($order['status'] != 0) {
                return $this->json(['status' => 0, 'msg' => '只有待付款订单才能关闭']);
            }

            // 关闭订单
            $result = Db::name('ai_travel_photo_order')
                ->where('id', $orderId)
                ->update([
                    'status' => 3,  // 3=已关闭
                    'close_time' => time(),
                    'update_time' => time()
                ]);

            if ($result) {
                // 关闭对应的支付订单
                Db::name('payorder')
                    ->where('aid', aid)
                    ->where('type', 'ai_pick')
                    ->where('orderid', $orderId)
                    ->where('status', 0)
                    ->update(['status' => -1]);

                return $this->json(['status' => 1, 'msg' => '订单已关闭']);
            } else {
                return $this->json(['status' => 0, 'msg' => '关闭失败']);
            }
        }

        // 其他订单类型，调用对应的关闭方法
        // 这里可以添加其他订单类型的关闭逻辑
        return $this->json(['status' => 0, 'msg' => '该订单类型不支持关闭操作']);
    }

    /**
     * 获取选片订单详情
     */
    protected function getAiPickOrderDetail($order)
    {
        // 获取商品列表
        $goodsList = Db::name('ai_travel_photo_order_goods')
            ->where('order_id', $order['id'])
            ->select()
            ->toArray();

        // 构造商品列表（兼容格式）
        $prolist = [];
        foreach ($goodsList as $goods) {
            $prolist[] = [
                'id' => $goods['id'],
                'orderid' => $goods['order_id'],
                'proid' => $goods['result_id'] ?? $goods['id'],
                'name' => $goods['goods_name'] ?? 'AI旅拍选片',
                'pic' => $goods['goods_image'] ?? '',
                'ggname' => '',
                'gg_group_title' => '',
                'num' => $goods['num'] ?? 1,
                'sell_price' => $goods['price'] ?? 0,
                'real_sell_price' => $goods['price'] ?? 0,
            ];
        }

        // 如果没有商品数据，构造一个默认项
        if (empty($prolist)) {
            $prolist = [[
                'id' => $order['id'],
                'orderid' => $order['id'],
                'proid' => $order['id'],
                'name' => 'AI旅拍选片',
                'pic' => '',
                'ggname' => '',
                'gg_group_title' => '',
                'num' => 1,
                'sell_price' => $order['actual_amount'] ?? $order['total_price'] ?? 0,
                'real_sell_price' => $order['actual_amount'] ?? $order['total_price'] ?? 0,
            ]];
        }

        // 构造详情数据
        $detail = [
            'id' => $order['id'],
            'ordernum' => $order['order_no'] ?? '',
            'status' => $order['status'] ?? 0,
            'totalprice' => $order['actual_amount'] ?? $order['total_price'] ?? 0,
            'createtime' => date('Y-m-d H:i:s', $order['create_time'] ?? time()),
            'paytime' => isset($order['pay_time']) && $order['pay_time'] > 0 ? date('Y-m-d H:i:s', $order['pay_time']) : '',
            'prolist' => $prolist,
            'procount' => count($prolist),
            'binfo' => ['name' => '选片', 'logo' => ''],
            'bid' => 0,
            'order_type' => 'ai_pick',
            'payorderid' => $this->getPayOrderId($order['id'], $order['status'] ?? 0),  // 支付订单ID
            'freight_type' => 0,
            'address' => '',
            'linkman' => '',
            'tel' => '',
            'can_collect' => false,
            'invoice' => 0,
            'refundCount' => 0,
            'refundnum' => 0,
        ];

        return $detail;
    }

    /**
     * 获取支付订单ID
     */
    protected function getPayOrderId($orderId, $status)
    {
        if ($status != 0) {
            return 0;
        }

        $payorder = Db::name('payorder')
            ->where('aid', aid)
            ->where('type', 'ai_pick')
            ->where('orderid', $orderId)
            ->where('status', 0)
            ->value('id');

        return $payorder ? (int)$payorder : 0;
    }

    /**
     * 查询单一类型订单（直通模式）
     */
    protected function querySingleType($type, $st, $keyword, $pagenum, $pernum)
    {
        list($name, $table, $timeField) = $this->orderTypes[$type];
        if (!$this->isModuleEnabled($type)) {
            return ['datalist' => [], 'type_counts' => [], 'status_counts' => []];
        }

        $where = $this->buildWhere($type, $table, $st, $keyword);
        $query = $this->buildQuery($type, $table, $where);
        $list = $query->page($pagenum, $pernum)
            ->order($timeField . ' desc')
            ->select()
            ->toArray();

        $datalist = [];
        foreach ($list as $row) {
            $datalist[] = $this->normalizeOrder($type, $row);
        }

        return [
            'datalist' => $datalist,
            'type_counts' => [],
            'status_counts' => [],
        ];
    }

    /**
     * 聚合所有类型订单
     */
    protected function aggregateAllTypes($st, $keyword, $pagenum, $pernum)
    {
        // 预取数量：每表取 pagenum * pernum 条，确保合并后有足够数据
        $fetchLimit = $pagenum * $pernum;
        $allOrders = [];

        foreach ($this->orderTypes as $type => $config) {
            list($name, $table, $timeField) = $config;
            if (!$this->isModuleEnabled($type)) {
                continue;
            }

            $where = $this->buildWhere($type, $table, $st, $keyword);

            $query = $this->buildQuery($type, $table, $where);
            $list = $query->limit($fetchLimit)
                ->order($timeField . ' desc')
                ->select()
                ->toArray();

            foreach ($list as $row) {
                $allOrders[] = $this->normalizeOrder($type, $row);
            }
        }

        // 按时间戳倒序排序
        usort($allOrders, function ($a, $b) {
            return $b['create_timestamp'] - $a['create_timestamp'];
        });

        // 应用层分页
        $offset = ($pagenum - 1) * $pernum;
        $pageData = array_slice($allOrders, $offset, $pernum);

        return [
            'datalist' => $pageData ? array_values($pageData) : [],
            'type_counts' => [],
            'status_counts' => [],
        ];
    }

    /**
     * 构建查询条件
     */
    protected function buildWhere($type, $table, $st, $keyword)
    {
        $where = $this->getBaseWhere($type);

        // 状态筛选
        if ($st === 'all') {
            // 不过滤
        } elseif ($st == '10') {
            // 退款/售后
            $where[] = ['refund_status', '>', 0];
        } elseif (in_array($type, ['ai_image', 'ai_video'])) {
            // 生成订单使用 pay_status + task_status 组合状态
            $this->applyGenerationStatusFilter($where, (int)$st);
        } else {
            // 统一状态码映射
            $mappedStatus = $this->mapStatus($type, (int)$st);
            if ($mappedStatus !== null) {
                if (is_array($mappedStatus)) {
                    $where[] = ['status', 'in', $mappedStatus];
                } else {
                    $where[] = ['status', '=', $mappedStatus];
                }
            } else {
                // 返回 null 表示该类型订单不存在该状态，添加一个永远为 false 的条件
                $where[] = ['status', '=', -999];
            }
        }

        // 关键字搜索
        if ($keyword !== '') {
            if (in_array($type, ['ai_image', 'ai_video'])) {
                $where[] = ['ordernum|scene_name', 'like', '%' . $keyword . '%'];
            } elseif ($type === 'ai_pick') {
                $where[] = ['order_no', 'like', '%' . $keyword . '%'];
            } elseif ($type === 'kecheng') {
                $where[] = ['title', 'like', '%' . $keyword . '%'];
            } elseif (in_array($type, ['shop', 'scoreshop', 'cycle', 'yuyue'])) {
                $orderids = Db::name($table . '_goods')->where('aid', aid)->where('mid', mid)
                    ->where('name', 'like', '%' . $keyword . '%')->column('orderid');
                if (!$orderids) {
                    $where[] = [$type === 'cycle' || $type === 'yuyue' ? 'ordernum' : 'ordernum', 'like', '%' . $keyword . '%'];
                } else {
                    // 标记有关键字商品ID，后续在查询中追加
                    $this->_keywordOrderIds = $orderids;
                }
            } else {
                $where[] = ['ordernum|title', 'like', '%' . $keyword . '%'];
            }
        }

        return $where;
    }

    protected $_keywordOrderIds = null;

    /**
     * 获取基础查询条件（aid + mid/uid + delete）
     */
    protected function getBaseWhere($type)
    {
        $where = [];
        $where[] = ['aid', '=', aid];

        // 生成订单表用 mid 标识用户，并添加 generation_type 条件
        if (in_array($type, ['ai_image', 'ai_video'])) {
            $where[] = ['mid', '=', mid];
            $where[] = ['generation_type', '=', $type === 'ai_image' ? 1 : 2];
            $where[] = ['status', '=', 1]; // 只查询有效订单
        } elseif ($type === 'ai_pick') {
            // ai_pick 表用 uid 标识用户
            $where[] = ['uid', '=', mid];
            // H5扫码用户 uid 可能为0，通过 openid 兜底匹配
            $member = Db::name('member')->where('id', mid)->find();
            if ($member) {
                // 优先匹配 wxopenid (小程序)，其次 mpopenid (公众号)
                $openid = $member['wxopenid'] ?? $member['mpopenid'] ?? '';
                if ($openid) {
                    $where[] = ['openid', '=', $openid];
                }
            }
            // 由于需要 whereOr 逻辑，标记为需要特殊处理
            $where['_aipick_or'] = true;
        } else {
            $where[] = ['mid', '=', mid];
        }

        // 有 delete 字段的表（选片、课程、生成订单表没有 delete 字段）
        if (!in_array($type, ['ai_pick', 'kecheng', 'ai_image', 'ai_video'])) {
            $where[] = ['delete', '=', 0];
        }

        // 课程订单默认只查已支付
        if ($type === 'kecheng') {
            $where[] = ['status', '=', 1];
        }

        return $where;
    }

    /**
     * 构建查询对象（处理 ai_pick 的 whereOr 逻辑）
     */
    protected function buildQuery($type, $table, $where)
    {
        if (!empty($where['_aipick_or'])) {
            // 移除标记
            unset($where['_aipick_or']);
            $openid = '';
            $aidVal = 0;
            $uidVal = 0;
            $otherConditions = []; // 其他条件（如 status）

            // 分解 where 条件
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    $field = $v[0] ?? '';
                    $operator = $v[1] ?? '';
                    $value = $v[2] ?? '';

                    if ($field === 'aid') {
                        $aidVal = $value;
                        unset($where[$k]);
                    } elseif ($field === 'uid') {
                        $uidVal = $value;
                        unset($where[$k]);
                    } elseif ($field === 'openid') {
                        $openid = $value;
                        unset($where[$k]);
                    } else {
                        // 其他条件
                        $otherConditions[] = $v;
                        unset($where[$k]);
                    }
                }
            }

            // 构建 (aid=1 AND uid=mid) OR (aid=1 AND openid=xxx)
            $query = Db::name($table);
            if ($openid && $uidVal > 0) {
                $query->where(function($q) use ($aidVal, $uidVal, $openid, $otherConditions) {
                    // 分支1: aid=1 AND uid=mid AND (其他条件)
                    $q->where('aid', $aidVal)->where('uid', $uidVal);
                    foreach ($otherConditions as $cond) {
                        $q->where($cond[0], $cond[2]);
                    }

                    // 分支2: aid=1 AND openid=xxx AND (其他条件)
                    $q->whereOr(function($q2) use ($aidVal, $openid, $otherConditions) {
                        $q2->where('aid', $aidVal)->where('openid', $openid);
                        foreach ($otherConditions as $cond) {
                            $q2->where($cond[0], $cond[2]);
                        }
                    });
                });
            } elseif ($uidVal > 0) {
                // 只有 uid
                $query->where('aid', $aidVal)->where('uid', $uidVal);
                foreach ($otherConditions as $cond) {
                    $query->where($cond[0], $cond[2]);
                }
            } elseif ($openid) {
                // 只有 openid
                $query->where('aid', $aidVal)->where('openid', $openid);
                foreach ($otherConditions as $cond) {
                    $query->where($cond[0], $cond[2]);
                }
            } else {
                // 都没有，回退到普通查询
                $query->where($where);
            }
            return $query;
        }

        return Db::name($table)->where($where);
    }

    /**
     * 状态码映射：统一状态码 → 各类型实际状态码
     */
    protected function mapStatus($type, $unifiedStatus)
    {
        // 0=待付款 1=待发货 2=待收货 3=已完成 4=已关闭
        if ($type === 'ai_pick') {
            $map = [
                0 => 0,      // 待支付 → 待付款
                1 => null,   // 选片无待发货
                2 => null,   // 选片无待收货（已付款的归到已完成）
                3 => 1,      // 已付款 → 已完成
                4 => [3, 4], // 已关闭/已退款 → 已关闭
            ];
        } elseif ($type === 'kecheng') {
            // 课程只有已支付状态(1)
            $map = [
                0 => null,
                1 => null,
                2 => null,
                3 => 1,
                4 => null,
            ];
        } else {
            $map = [
                0 => 0,
                1 => 1,
                2 => 2,
                3 => 3,
                4 => 4,
            ];
        }

        return $map[$unifiedStatus] ?? null;
    }

    /**
     * 将订单数据标准化为统一格式
     */
    protected function normalizeOrder($type, $row)
    {
        list($name, $table, $timeField) = $this->orderTypes[$type];
        $timestamp = (int)($row[$timeField] ?? 0);

        $data = null;
        if ($type === 'ai_pick') {
            $data = $this->normalizeAiPickOrder($row, $type, $name, $timestamp);
        } elseif (in_array($type, ['ai_image', 'ai_video'])) {
            $data = $this->normalizeGenerationOrder($row, $type, $name, $timestamp);
        } elseif ($type === 'kecheng') {
            $data = $this->normalizeKechengOrder($row, $type, $name, $timestamp);
        } elseif ($type === 'shop') {
            $data = $this->normalizeShopOrder($row, $type, $name, $timestamp);
        } elseif (in_array($type, ['collage', 'seckill', 'tuangou', 'kanjia', 'lucky_collage'])) {
            $data = $this->normalizeActivityOrder($row, $type, $name, $timestamp);
        } elseif ($type === 'scoreshop') {
            $data = $this->normalizeScoreshopOrder($row, $type, $name, $timestamp);
        } else {
            $data = $this->normalizeGenericOrder($row, $type, $name, $timestamp);
        }

        // 添加统一状态码（用于前端筛选）
        $actualStatus = $data['status'];
        $unifiedStatus = $this->toUnifiedStatus($type, $actualStatus);
        $data['unified_status'] = $unifiedStatus !== null ? $unifiedStatus : $actualStatus;

        return $data;
    }

    /**
     * 将实际订单状态转换为统一状态码
     * @param string $type 订单类型
     * @param int $actualStatus 实际状态码
     * @return int|null 统一状态码 (0=待付款 1=待发货 2=待收货 3=已完成 4=已关闭)
     */
    protected function toUnifiedStatus($type, $actualStatus)
    {
        if ($type === 'ai_pick') {
            // 选片: 0→0(待付款), 1→3(已完成), 其他→3(已完成)
            if ($actualStatus == 0) return 0;
            if ($actualStatus == 1) return 3;
            return 3;
        } elseif (in_array($type, ['ai_image', 'ai_video'])) {
            // 生成订单的 status 已经在 normalizeGenerationOrder 中计算好了
            return $actualStatus;
        } elseif ($type === 'kecheng') {
            // 课程: status=1(已支付) → 3(已完成)
            return 3;
        } elseif ($type === 'scoreshop') {
            // 积分商城: 状态直接映射
            if ($actualStatus == 0) return 0;
            if ($actualStatus == 1) return 1;
            if ($actualStatus == 2) return 2;
            if ($actualStatus == 3) return 3;
            return $actualStatus;
        } else {
            // 商城、拼团等: 直接使用原状态
            return $actualStatus;
        }
    }

    /**
     * 标准化商城订单
     */
    protected function normalizeShopOrder($row, $type, $typeName, $timestamp)
    {
        $prolist = Db::name('shop_order_goods')->where('orderid', $row['id'])
            ->field('id,orderid,proid,name,pic,ggname,sell_price,num')
            ->select()->toArray();

        $title = $prolist ? $prolist[0]['name'] : ($row['title'] ?? '');
        $coverImage = $prolist ? $prolist[0]['pic'] : '';
        $itemCount = Db::name('shop_order_goods')->where('orderid', $row['id'])->sum('num');

        $detailUrl = $this->detailRoutes[$type] . $row['id'];

        // 构造完整的兼容格式
        return [
            'id'               => $row['id'],
            'order_type'       => $type,
            'order_type_name'  => $typeName,
            'ordernum'         => $row['ordernum'] ?? '',
            'title'            => $title,
            'cover_image'      => $coverImage ?: '',
            'totalprice'       => dd_money_format($row['totalprice']),
            'total_price'      => dd_money_format($row['totalprice']),
            'status'           => (int)($row['status'] ?? 0),
            'status_text'      => $this->statusText($row['status'] ?? 0),
            'item_count'       => (int)$itemCount,
            'procount'         => (int)$itemCount,
            'create_time'      => $timestamp ? date('Y-m-d H:i', $timestamp) : '',
            'create_timestamp' => $timestamp,
            'detail_url'       => $detailUrl,
            'refund_status'    => (int)($row['refund_status'] ?? 0),
            'prolist'          => $prolist,
            'binfo'            => ['name' => $typeName, 'logo' => $coverImage],
            'bid'              => 0,
            // 添加商城订单需要的其他字段
            'refundnum'        => 0,
            'can_collect'      => true,
            'procanrefund'     => 0,
            'paytypeid'        => $row['paytypeid'] ?? 0,
            'transfer_check'   => 0,
            'is_fenqi'         => 0,
            'isdygroupbuy'     => 0,
            'freight_type'     => $row['freight_type'] ?? 0,
            'hexiao_qr'        => '',
            'is_pingce'        => 0,
            'pingce_status'    => 0,
            'invoice'          => 0,
            'order_can_refund' => 0,
            'transfer_order_parent_check' => false,
            'cancodpay'        => false,
            'crk_givenum'      => 0,
            'yuding_type'      => '',
            'wxtc'             => false,
            'wxtc_status_name' => '',
            'tips'             => '',
            'exchange_card_take_date' => '',
            'balance_price'    => 0,
            'balance_pay_status' => 0,
            'total_freezemoney_price' => 0,
            'refund_money'     => 0,
            'extra_info'       => [],
        ];
    }

    /**
     * 标准化活动订单（拼团/秒杀/团购/砍价/幸运拼团）
     */
    protected function normalizeActivityOrder($row, $type, $typeName, $timestamp)
    {
        $title = $row['proname'] ?? '';
        $coverImage = $row['propic'] ?? '';

        $detailUrl = $this->detailRoutes[$type] . $row['id'];

        // 构造商品列表（兼容格式）
        $prolist = [[
            'id' => $row['id'],
            'orderid' => $row['id'],
            'proid' => $row['proid'] ?? $row['id'],
            'name' => $title,
            'pic' => $coverImage,
            'ggname' => '',
            'gg_group_title' => '',
            'num' => $row['num'] ?? 1,
            'sell_price' => $row['totalprice'] ?? 0,
            'real_sell_price' => $row['totalprice'] ?? 0,
            'hexiao_code' => '',
            'hexiao_num' => 0,
            'hexiao_num_total' => 0,
            'hexiao_num_used' => 0,
            'is_hx' => 0,
            'is_quanyi' => 0,
        ]];

        return [
            'id'               => $row['id'],
            'order_type'       => $type,
            'order_type_name'  => $typeName,
            'ordernum'         => $row['ordernum'] ?? '',
            'title'            => $title,
            'cover_image'      => $coverImage ?: '',
            'totalprice'       => dd_money_format($row['totalprice'] ?? 0),
            'total_price'      => dd_money_format($row['totalprice'] ?? 0),
            'status'           => (int)($row['status'] ?? 0),
            'status_text'      => $this->statusText($row['status'] ?? 0),
            'item_count'       => (int)($row['num'] ?? 1),
            'procount'         => (int)($row['num'] ?? 1),
            'create_time'      => $timestamp ? date('Y-m-d H:i', $timestamp) : '',
            'create_timestamp' => $timestamp,
            'detail_url'       => $detailUrl,
            'refund_status'    => (int)($row['refund_status'] ?? 0),
            'prolist'          => $prolist,
            'binfo'            => ['name' => $typeName, 'logo' => $coverImage],
            'bid'              => 0,
            // 添加商城订单需要的其他字段
            'refundnum'        => 0,
            'can_collect'      => true,
            'procanrefund'     => 0,
            'paytypeid'        => 0,
            'transfer_check'   => 0,
            'is_fenqi'         => 0,
            'isdygroupbuy'     => 0,
            'freight_type'     => 0,
            'hexiao_qr'        => '',
            'is_pingce'        => 0,
            'pingce_status'    => 0,
            'invoice'          => 0,
            'order_can_refund' => 0,
            'transfer_order_parent_check' => false,
            'cancodpay'        => false,
            'crk_givenum'      => 0,
            'yuding_type'      => '',
            'wxtc'             => false,
            'wxtc_status_name' => '',
            'tips'             => '',
            'exchange_card_take_date' => '',
            'balance_price'    => 0,
            'balance_pay_status' => 0,
            'total_freezemoney_price' => 0,
            'refund_money'     => 0,
            'extra_info'       => [],
        ];
    }

    /**
     * 标准化积分兑换订单
     */
    protected function normalizeScoreshopOrder($row, $type, $typeName, $timestamp)
    {
        $prolist = Db::name('scoreshop_order_goods')->where('orderid', $row['id'])
            ->field('id,orderid,proid,name,pic,ggname,sell_price,num')
            ->select()->toArray();

        $title = $prolist ? $prolist[0]['name'] : '';
        $coverImage = $prolist ? $prolist[0]['pic'] : '';
        $itemCount = Db::name('scoreshop_order_goods')->where('orderid', $row['id'])->sum('num');

        $detailUrl = $this->detailRoutes[$type] . $row['id'];

        // 补充商品列表的字段
        foreach ($prolist as &$goods) {
            $goods['orderid'] = $row['id'];
            $goods['real_sell_price'] = $goods['sell_price'];
            $goods['gg_group_title'] = '';
            $goods['hexiao_code'] = '';
            $goods['hexiao_num'] = 0;
            $goods['hexiao_num_total'] = 0;
            $goods['hexiao_num_used'] = 0;
            $goods['is_hx'] = 0;
            $goods['is_quanyi'] = 0;
        }

        return [
            'id'               => $row['id'],
            'order_type'       => $type,
            'order_type_name'  => $typeName,
            'ordernum'         => $row['ordernum'] ?? '',
            'title'            => $title,
            'cover_image'      => $coverImage ?: '',
            'totalprice'       => dd_money_format($row['totalprice'] ?? 0),
            'total_price'      => dd_money_format($row['totalprice'] ?? 0),
            'status'           => (int)($row['status'] ?? 0),
            'status_text'      => $this->statusText($row['status'] ?? 0),
            'item_count'       => (int)$itemCount,
            'procount'         => (int)$itemCount,
            'create_time'      => $timestamp ? date('Y-m-d H:i', $timestamp) : '',
            'create_timestamp' => $timestamp,
            'detail_url'       => $detailUrl,
            'refund_status'    => (int)($row['refund_status'] ?? 0),
            'prolist'          => $prolist,
            'binfo'            => ['name' => $typeName, 'logo' => $coverImage],
            'bid'              => 0,
            // 添加商城订单需要的其他字段
            'refundnum'        => 0,
            'can_collect'      => true,
            'procanrefund'     => 0,
            'paytypeid'        => 0,
            'transfer_check'   => 0,
            'is_fenqi'         => 0,
            'isdygroupbuy'     => 0,
            'freight_type'     => 0,
            'hexiao_qr'        => '',
            'is_pingce'        => 0,
            'pingce_status'    => 0,
            'invoice'          => 0,
            'order_can_refund' => 0,
            'transfer_order_parent_check' => false,
            'cancodpay'        => false,
            'crk_givenum'      => 0,
            'yuding_type'      => '',
            'wxtc'             => false,
            'wxtc_status_name' => '',
            'tips'             => '',
            'exchange_card_take_date' => '',
            'balance_price'    => 0,
            'balance_pay_status' => 0,
            'total_freezemoney_price' => 0,
            'refund_money'     => 0,
            'extra_info'       => [],
        ];
    }

    /**
     * 标准化课程订单
     */
    protected function normalizeKechengOrder($row, $type, $typeName, $timestamp)
    {
        $detailUrl = $this->detailRoutes[$type] . ($row['kcid'] ?? $row['id']);

        // 构造商品列表（兼容格式）
        $prolist = [[
            'id' => $row['id'],
            'orderid' => $row['id'],
            'proid' => $row['kcid'] ?? $row['id'],
            'name' => $row['title'] ?? '',
            'pic' => $row['pic'] ?? '',
            'ggname' => '',
            'gg_group_title' => '',
            'num' => 1,
            'sell_price' => $row['price'] ?? 0,
            'real_sell_price' => $row['price'] ?? 0,
            'hexiao_code' => '',
            'hexiao_num' => 0,
            'hexiao_num_total' => 0,
            'hexiao_num_used' => 0,
            'is_hx' => 0,
            'is_quanyi' => 0,
        ]];

        return [
            'id'               => $row['id'],
            'order_type'       => $type,
            'order_type_name'  => $typeName,
            'ordernum'         => $row['ordernum'] ?? ('KC' . str_pad((string)$row['id'], 8, '0', STR_PAD_LEFT)),
            'title'            => $row['title'] ?? '',
            'cover_image'      => $row['pic'] ?? '',
            'totalprice'       => dd_money_format($row['price'] ?? 0),
            'total_price'      => dd_money_format($row['price'] ?? 0),
            'status'           => 3, // 课程已支付统一映射为已完成
            'status_text'      => '已完成',
            'item_count'       => 1,
            'procount'         => 1,
            'create_time'      => $timestamp ? date('Y-m-d H:i', $timestamp) : '',
            'create_timestamp' => $timestamp,
            'detail_url'       => $detailUrl,
            'refund_status'    => 0,
            'prolist'          => $prolist,
            'binfo'            => ['name' => $typeName, 'logo' => $row['pic'] ?? ''],
            'bid'              => 0,
            // 添加商城订单需要的其他字段
            'refundnum'        => 0,
            'can_collect'      => true,
            'procanrefund'     => 0,
            'paytypeid'        => 0,
            'transfer_check'   => 0,
            'is_fenqi'         => 0,
            'isdygroupbuy'     => 0,
            'freight_type'     => 0,
            'hexiao_qr'        => '',
            'is_pingce'        => 0,
            'pingce_status'    => 0,
            'invoice'          => 0,
            'order_can_refund' => 0,
            'transfer_order_parent_check' => false,
            'cancodpay'        => false,
            'crk_givenum'      => 0,
            'yuding_type'      => '',
            'wxtc'             => false,
            'wxtc_status_name' => '',
            'tips'             => '',
            'exchange_card_take_date' => '',
            'balance_price'    => 0,
            'balance_pay_status' => 0,
            'total_freezemoney_price' => 0,
            'refund_money'     => 0,
            'extra_info'       => [
                'kcid'     => $row['kcid'] ?? 0,
                'kccount'   => 0, // 简化处理
                'learned'   => 0,
            ],
        ];
    }

    /**
     * 标准化选片订单
     */
    protected function normalizeAiPickOrder($row, $type, $typeName, $timestamp)
    {
        // 从订单商品表获取商品列表
        $goodsList = Db::name('ai_travel_photo_order_goods')
            ->where('order_id', $row['id'])
            ->field('id,order_id,result_id,goods_name,goods_image,num,price')
            ->select()->toArray();

        $coverImage = '';
        $title = 'AI旅拍选片';

        // 标题从 package_snapshot 解析套餐名称
        if (!empty($row['package_snapshot'])) {
            $snapshot = json_decode($row['package_snapshot'], true);
            if ($snapshot) {
                $title = $snapshot['name'] ?? $title;
                // 如果有商品数据，使用第一个商品的图片
                if (!empty($snapshot['products'])) {
                    $coverImage = $snapshot['products'][0]['goods_image'] ?? '';
                }
            }
        }

        // 如果没有封面图，从数据库查询
        if (!$coverImage && !empty($goodsList)) {
            $coverImage = $goodsList[0]['goods_image'] ?? '';
        }

        // 处理缩略图：如果图片URL存在，添加缩略图参数
        if ($coverImage && strpos($coverImage, 'http') === 0) {
            // 添加50x50缩略图参数（适配阿里云OSS、腾讯云COS等）
            if (strpos($coverImage, '?') === false) {
                $coverImage = $coverImage . '?x-oss-process=image/resize,w_50,h_50';
            }
        }

        $itemCount = count($goodsList);

        $detailUrl = $this->detailRoutes[$type] . $row['id'];

        $actualStatus = (int)($row['status'] ?? 0);

        // 查询支付订单ID（用于去付款按钮）
        $payorderid = 0;
        if ($actualStatus == 0) {
            $payorder = Db::name('payorder')
                ->where('aid', aid)
                ->where('type', 'ai_pick')
                ->where('orderid', $row['id'])
                ->where('status', 0)
                ->value('id');
            $payorderid = $payorder ? (int)$payorder : 0;
        }

        // 查询成片状态（用于显示“去下载”或“已过期”）
        $resultStatus = 'normal';  // normal=正常, expired=已过期
        $downloadUrl = '';
        if ($actualStatus == 1 && !empty($goodsList)) {
            // 已付款订单，检查成片是否存在且未被删除
            $resultIds = array_column($goodsList, 'result_id');
            $resultIds = array_filter($resultIds);  // 过滤掉空值
            
            if (!empty($resultIds)) {
                // 查询成片状态（status=1为正常，status=2为已删除）
                $validResultCount = Db::name('ai_travel_photo_result')
                    ->where('id', 'in', $resultIds)
                    ->where('status', 1)  // 只统计未被删除的成片
                    ->count();
                
                // 如果所有成片都被删除了，标记为已过期
                if ($validResultCount == 0) {
                    $resultStatus = 'expired';
                } else {
                    // 有成片存在，生成下载链接（指向订单详情页）
                    $resultStatus = 'normal';
                    $downloadUrl = '/pagesExt/order/detail?id=' . $row['id'];
                }
            } else {
                // 没有关联成片，标记为已过期
                $resultStatus = 'expired';
            }
        }

        // 构造商品列表（兼容格式）
        $prolist = [];
        foreach ($goodsList as $goods) {
            // 处理商品图片缩略图
            $goodsPic = $goods['goods_image'] ?? $coverImage;
            if ($goodsPic && strpos($goodsPic, 'http') === 0) {
                if (strpos($goodsPic, '?') === false) {
                    $goodsPic = $goodsPic . '?x-oss-process=image/resize,w_50,h_50';
                }
            }

            $prolist[] = [
                'id' => $goods['id'],
                'orderid' => $row['id'],
                'proid' => $goods['result_id'] ?? $goods['id'],
                'name' => $goods['goods_name'] ?? $title,
                'pic' => $goodsPic,
                'ggname' => '',
                'gg_group_title' => '',
                'num' => $goods['num'] ?? 1,
                'sell_price' => $goods['price'] ?? 0,
                'real_sell_price' => $goods['price'] ?? 0,
                'hexiao_code' => '',
                'hexiao_num' => 0,
                'hexiao_num_total' => 0,
                'hexiao_num_used' => 0,
                'is_hx' => 0,
                'is_quanyi' => 0,
            ];
        }

        // 如果没有商品数据，构造一个默认项
        if (empty($prolist)) {
            $prolist = [[
                'id' => $row['id'],
                'orderid' => $row['id'],
                'proid' => $row['id'],
                'name' => $title,
                'pic' => $coverImage,
                'ggname' => '',
                'gg_group_title' => '',
                'num' => 1,
                'sell_price' => $row['actual_amount'] ?? $row['total_price'] ?? 0,
                'real_sell_price' => $row['actual_amount'] ?? $row['total_price'] ?? 0,
                'hexiao_code' => '',
                'hexiao_num' => 0,
                'hexiao_num_total' => 0,
                'hexiao_num_used' => 0,
                'is_hx' => 0,
                'is_quanyi' => 0,
            ]];
        }

        return [
            'id'               => $row['id'],
            'order_type'       => $type,
            'order_type_name'  => $typeName,
            'ordernum'         => $row['order_no'] ?? '',
            'title'            => $title,
            'cover_image'      => $coverImage ?: '',
            'totalprice'       => dd_money_format($row['actual_amount'] ?? $row['total_price'] ?? 0),
            'total_price'      => dd_money_format($row['actual_amount'] ?? $row['total_price'] ?? 0),
            'status'           => $actualStatus,
            'status_text'      => $this->getAiPickStatusText($actualStatus),  // 选片订单专用状态文本
            'item_count'       => (int)$itemCount,
            'procount'         => (int)$itemCount,
            'create_time'      => $timestamp ? date('Y-m-d H:i', $timestamp) : '',
            'create_timestamp' => $timestamp,
            'detail_url'       => $detailUrl,
            'refund_status'    => (int)($row['refund_status'] ?? 0),
            'prolist'          => $prolist,
            'binfo'            => ['name' => $typeName, 'logo' => $coverImage],
            'bid'              => 0,
            'payorderid'       => $payorderid,  // 支付订单ID（用于去付款）
            'result_status'    => $resultStatus,  // 成片状态：normal=正常, expired=已过期
            'download_url'     => $downloadUrl,   // 下载链接
            // 添加商城订单需要的其他字段
            'refundnum'        => 0,
            'can_collect'      => true,
            'procanrefund'     => 0,
            'paytypeid'        => 0,
            'transfer_check'   => 0,
            'is_fenqi'         => 0,
            'isdygroupbuy'     => 0,
            'freight_type'     => 0,
            'hexiao_qr'        => '',
            'is_pingce'        => 0,
            'pingce_status'    => 0,
            'invoice'          => 0,
            'order_can_refund' => 0,
            'transfer_order_parent_check' => false,
            'cancodpay'        => false,
            'crk_givenum'      => 0,
            'yuding_type'      => '',
            'wxtc'             => false,
            'wxtc_status_name' => '',
            'tips'             => '',
            'exchange_card_take_date' => '',
            'balance_price'    => 0,
            'balance_pay_status' => 0,
            'total_freezemoney_price' => 0,
            'refund_money'     => 0,
            'extra_info'       => [
                'buy_type'        => $row['buy_type'] ?? 1,
                'package_snapshot' => $row['package_snapshot'] ?? '',
            ],
        ];
    }

    /**
     * 生成订单状态筛选条件
     * 生成订单使用 pay_status + task_status 组合而非单一 status 字段
     */
    protected function applyGenerationStatusFilter(&$where, $unifiedStatus)
    {
        switch ($unifiedStatus) {
            case 0: // 待付款
                $where[] = ['pay_status', '=', 0];
                break;
            case 1: // 生成中 (映射自待发货)
                $where[] = ['pay_status', '=', 1];
                $where[] = ['task_status', 'in', [0, 1]];
                break;
            case 3: // 已完成
                $where[] = ['pay_status', '=', 1];
                $where[] = ['task_status', 'in', [2, 3]];
                break;
            default: // 待收货/已关闭 不适用
                $where[] = ['id', '=', -1];
                break;
        }
    }

    /**
     * 标准化生成订单（AI写真/AI视频）
     */
    protected function normalizeGenerationOrder($row, $type, $typeName, $timestamp)
    {
        $title = $row['scene_name'] ?: ($type === 'ai_image' ? 'AI写真' : 'AI视频');

        // 获取封面图
        $coverImage = '';

        // 优先从 template_snapshot 解析
        if (!empty($row['template_snapshot'])) {
            $snapshot = json_decode($row['template_snapshot'], true);
            if ($snapshot) {
                $coverImage = $snapshot['cover_url'] ?? $snapshot['thumbnail'] ?? $snapshot['cover'] ?? '';
            }
        }

        // 从 ref_images 获取
        if (!$coverImage && !empty($row['ref_images'])) {
            $refImages = json_decode($row['ref_images'], true);
            if ($refImages && is_array($refImages) && !empty($refImages[0])) {
                $coverImage = is_string($refImages[0]) ? $refImages[0] : ($refImages[0]['url'] ?? '');
            }
        }

        // 从 generation_output 获取生成结果图
        if (!$coverImage && $row['record_id']) {
            $output = Db::name('generation_output')
                ->where('record_id', $row['record_id'])
                ->order('id asc')
                ->find();
            if ($output) {
                $coverImage = $output['thumbnail_url'] ?: $output['output_url'];
            }
        }

        // 处理缩略图
        if ($coverImage && strpos($coverImage, 'http') === 0 && strpos($coverImage, '?') === false) {
            $coverImage = $coverImage . '?x-oss-process=image/resize,w_100,h_100';
        }

        // 计算显示状态
        $payStatus = (int)($row['pay_status'] ?? 0);
        $taskStatus = (int)($row['task_status'] ?? 0);
        $displayStatus = 0;
        $statusText = '待付款';

        if ($payStatus == 0) {
            $displayStatus = 0;
            $statusText = '待付款';
        } elseif ($payStatus == 1) {
            if ($taskStatus == 0) {
                $displayStatus = 1;
                $statusText = '待生成';
            } elseif ($taskStatus == 1) {
                $displayStatus = 1;
                $statusText = '生成中';
            } elseif ($taskStatus == 2) {
                $displayStatus = 3;
                $statusText = '已完成';
            } elseif ($taskStatus == 3) {
                $displayStatus = 3;
                $statusText = '生成失败';
            }
        }

        // 退款状态覆盖
        $refundStatus = (int)($row['refund_status'] ?? 0);
        if ($refundStatus == 1) {
            $statusText = '退款审核中';
        } elseif ($refundStatus == 2) {
            $statusText = '已退款';
        } elseif ($refundStatus == 3) {
            $statusText = '退款已驳回';
        }

        $detailUrl = $this->detailRoutes[$type] . $row['id'];

        // 查询支付订单ID
        $payorderid = (int)($row['payorderid'] ?? 0);

        // 构造商品列表
        $prolist = [[
            'id' => $row['id'],
            'orderid' => $row['id'],
            'proid' => $row['id'],
            'name' => $title,
            'pic' => $coverImage,
            'ggname' => '',
            'gg_group_title' => '',
            'num' => 1,
            'sell_price' => $row['pay_price'] ?? $row['total_price'] ?? 0,
            'real_sell_price' => $row['pay_price'] ?? $row['total_price'] ?? 0,
            'hexiao_code' => '',
            'hexiao_num' => 0,
            'hexiao_num_total' => 0,
            'hexiao_num_used' => 0,
            'is_hx' => 0,
            'is_quanyi' => 0,
        ]];

        return [
            'id'               => $row['id'],
            'order_type'       => $type,
            'order_type_name'  => $typeName,
            'ordernum'         => $row['ordernum'] ?? '',
            'title'            => $title,
            'cover_image'      => $coverImage ?: '',
            'totalprice'       => dd_money_format($row['pay_price'] ?? $row['total_price'] ?? 0),
            'total_price'      => dd_money_format($row['pay_price'] ?? $row['total_price'] ?? 0),
            'status'           => $displayStatus,
            'status_text'      => $statusText,
            'item_count'       => 1,
            'procount'         => 1,
            'create_time'      => $timestamp ? date('Y-m-d H:i', $timestamp) : '',
            'create_timestamp' => $timestamp,
            'detail_url'       => $detailUrl,
            'refund_status'    => $refundStatus,
            'prolist'          => $prolist,
            'binfo'            => ['name' => $typeName, 'logo' => $coverImage],
            'bid'              => 0,
            'payorderid'       => $payorderid,
            'generation_type'  => (int)($row['generation_type'] ?? 1),
            'pay_status'       => $payStatus,
            'task_status'      => $taskStatus,
            // 兼容字段
            'refundnum'        => 0,
            'can_collect'      => false,
            'procanrefund'     => 0,
            'paytypeid'        => (int)($row['paytypeid'] ?? 0),
            'transfer_check'   => 0,
            'is_fenqi'         => 0,
            'isdygroupbuy'     => 0,
            'freight_type'     => 0,
            'hexiao_qr'        => '',
            'is_pingce'        => 0,
            'pingce_status'    => 0,
            'invoice'          => 0,
            'order_can_refund' => 0,
            'transfer_order_parent_check' => false,
            'cancodpay'        => false,
            'crk_givenum'      => 0,
            'yuding_type'      => '',
            'wxtc'             => false,
            'wxtc_status_name' => '',
            'tips'             => '',
            'exchange_card_take_date' => '',
            'balance_price'    => 0,
            'balance_pay_status' => 0,
            'total_freezemoney_price' => 0,
            'refund_money'     => 0,
            'extra_info'       => [
                'generation_type' => (int)($row['generation_type'] ?? 1),
                'task_status'     => $taskStatus,
            ],
        ];
    }

    /**
     * 标准化通用订单（预约/周期购等）
     */
    protected function normalizeGenericOrder($row, $type, $typeName, $timestamp)
    {
        $detailUrl = $this->detailRoutes[$type] . $row['id'];

        return [
            'id'               => $row['id'],
            'order_type'       => $type,
            'order_type_name'  => $typeName,
            'ordernum'         => $row['ordernum'] ?? '',
            'title'            => $row['title'] ?? '',
            'cover_image'      => '',
            'total_price'      => dd_money_format($row['totalprice'] ?? 0),
            'status'           => (int)($row['status'] ?? 0),
            'status_text'      => $this->statusText($row['status'] ?? 0),
            'item_count'       => 1,
            'create_time'      => $timestamp ? date('Y-m-d H:i', $timestamp) : '',
            'create_timestamp' => $timestamp,
            'detail_url'       => $detailUrl,
            'refund_status'    => (int)($row['refund_status'] ?? 0),
            'extra_info'       => [],
        ];
    }

    /**
     * 统一状态文本
     */
    protected function statusText($status)
    {
        $map = [
            0 => '待付款',
            1 => '待发货',
            2 => '待收货',
            3 => '已完成',
            4 => '已关闭',
        ];
        return $map[(int)$status] ?? '未知';
    }

    /**
     * 选片订单状态文本（专用）
     */
    protected function getAiPickStatusText($status)
    {
        $map = [
            0 => '待付款',
            1 => '已完成',  // 选片status=1表示已付款，显示为已完成
            2 => '已完成',
            3 => '已关闭',
            4 => '已退款',
        ];
        return $map[(int)$status] ?? '未知';
    }

    /**
     * 按状态计数
     */
    protected function countWithStatus($type, $table, $baseWhere, $st)
    {
        $where = $baseWhere;
        if ($type === 'ai_pick') {
            // 选片: 0→0(待付款), 3→1(已完成)
            if ($st == '0') {
                $where[] = ['status', '=', 0];
            } elseif ($st == '3') {
                $where[] = ['status', '=', 1];
            } else {
                return 0;
            }
        } elseif (in_array($type, ['ai_image', 'ai_video'])) {
            // 生成订单使用 pay_status + task_status 组合
            if ($st == '0') {
                $where[] = ['pay_status', '=', 0];
            } elseif ($st == '1') {
                $where[] = ['pay_status', '=', 1];
                $where[] = ['task_status', 'in', [0, 1]];
            } elseif ($st == '3') {
                $where[] = ['pay_status', '=', 1];
                $where[] = ['task_status', 'in', [2, 3]];
            } else {
                return 0;
            }
        } elseif ($type === 'kecheng') {
            // 课程只有status=1（已支付），映射到统一3（已完成）
            if ($st == '3') {
                $where[] = ['status', '=', 1];
            } else {
                return 0;
            }
        } else {
            $where[] = ['status', '=', (int)$st];
        }
        $query = $this->buildQuery($type, $table, $where);
        return $query->count();
    }

    /**
     * 退款订单计数
     */
    protected function countRefund($type, $baseWhere)
    {
        $where = $baseWhere;
        $where[] = ['refund_status', '>', 0];
        list(, $table) = $this->orderTypes[$type];
        $query = $this->buildQuery($type, $table, $where);
        return $query->count();
    }

    /**
     * 判断模块是否启用
     */
    protected function isModuleEnabled($type)
    {
        // 选片模块和生成订单模块始终可用
        if ($type === 'ai_pick') return true;
        if (in_array($type, ['ai_image', 'ai_video'])) return true;

        // 通过 getcustom 检查模块是否开通
        $customMap = [
            'collage'       => 'collage',
            'seckill'       => 'seckill',
            'tuangou'       => 'tuangou',
            'kanjia'        => 'kanjia',
            'lucky_collage' => 'yx_lucky_collage',
            'scoreshop'     => 'scoreshop',
            'yuyue'         => 'yuyue',
            'kecheng'       => 'kecheng',
            'cycle'         => 'cycle',
        ];

        $customKey = $customMap[$type] ?? null;
        if ($customKey) {
            return (bool)getcustom($customKey);
        }

        // 商城订单始终可用
        return true;
    }
}
