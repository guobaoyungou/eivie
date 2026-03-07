<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoOrder;
use app\model\AiTravelPhotoOrderGoods;
use app\model\AiTravelPhotoResult;
use app\model\AiTravelPhotoPackage;
use app\model\AiTravelPhotoUserAlbum;
use app\model\AiTravelPhotoQrcode;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * AI旅拍-订单管理服务
 * Class AiTravelPhotoOrderService
 * @package app\service
 */
class AiTravelPhotoOrderService
{
    /**
     * 创建订单
     * @param array $data 订单数据
     * @return array
     */
    public function createOrder(array $data): array
    {
        // 开启事务
        Db::startTrans();
        try {
            // 生成订单号
            $orderNo = AiTravelPhotoOrder::generateOrderNo();
            
            // 计算订单金额
            $priceData = $this->calculateOrderPrice($data);
            
            // 创建订单
            $order = AiTravelPhotoOrder::create([
                'aid' => $data['aid'],
                'order_no' => $orderNo,
                'qrcode_id' => $data['qrcode_id'] ?? 0,
                'portrait_id' => $data['portrait_id'] ?? 0,
                'uid' => $data['uid'],
                'bid' => $data['bid'],
                'mdid' => $data['mdid'] ?? 0,
                'buy_type' => $data['buy_type'],
                'package_id' => $data['package_id'] ?? 0,
                'total_price' => $priceData['total_price'],
                'discount_amount' => $priceData['discount_amount'],
                'actual_amount' => $priceData['actual_amount'],
                'status' => AiTravelPhotoOrder::STATUS_UNPAID,
                'ip' => $data['ip'] ?? '',
                'user_agent' => $data['user_agent'] ?? '',
            ]);
            
            // 创建订单商品
            foreach ($data['goods'] as $goods) {
                $result = AiTravelPhotoResult::find($goods['result_id']);
                if (!$result) {
                    throw new ValidateException('商品不存在：' . $goods['result_id']);
                }
                
                AiTravelPhotoOrderGoods::create([
                    'aid' => $data['aid'],
                    'order_id' => $order->id,
                    'order_no' => $orderNo,
                    'result_id' => $goods['result_id'],
                    'type' => $result->type == 19 ? 2 : 1, // 19为视频
                    'goods_name' => $result->scene->name ?? '旅拍照片',
                    'goods_image' => $result->watermark_url,
                    'price' => $goods['price'],
                    'num' => $goods['num'] ?? 1,
                    'total_price' => $goods['price'] * ($goods['num'] ?? 1),
                    'status' => 1,
                ]);
            }
            
            // 如果是套餐购买，扣减库存
            if ($data['buy_type'] == AiTravelPhotoOrder::BUY_TYPE_PACKAGE && !empty($data['package_id'])) {
                $package = AiTravelPhotoPackage::find($data['package_id']);
                if ($package && $package->stock > 0) {
                    $package->stock -= 1;
                    $package->save();
                }
            }
            
            Db::commit();
            
            return [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'actual_amount' => $order->actual_amount,
                'status' => 'created',
            ];
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
    
    /**
     * 计算订单价格
     * @param array $data 订单数据
     * @return array
     */
    private function calculateOrderPrice(array $data): array
    {
        $totalPrice = 0;
        $discountAmount = 0;
        
        // 获取商家配置
        $business = \app\model\Business::find($data['bid']);
        $photoPrice = $business->ai_photo_price ?? 9.90;
        $videoPrice = $business->ai_video_price ?? 29.90;
        
        if ($data['buy_type'] == AiTravelPhotoOrder::BUY_TYPE_SINGLE) {
            // 单张购买
            foreach ($data['goods'] as &$goods) {
                $result = AiTravelPhotoResult::find($goods['result_id']);
                $price = $result->type == 19 ? $videoPrice : $photoPrice;
                $goods['price'] = $price;
                $totalPrice += $price * ($goods['num'] ?? 1);
            }
        } else {
            // 套餐购买
            $package = AiTravelPhotoPackage::find($data['package_id']);
            if (!$package) {
                throw new ValidateException('套餐不存在');
            }
            
            // 检查库存
            if ($package->stock > 0 && $package->stock < 1) {
                throw new ValidateException('套餐库存不足');
            }
            
            // 检查有效期
            if (!$package->isValid()) {
                throw new ValidateException('套餐已过期');
            }
            
            $totalPrice = $package->price;
            $discountAmount = $package->original_price - $package->price;
            
            // 设置商品价格
            $avgPrice = $totalPrice / count($data['goods']);
            foreach ($data['goods'] as &$goods) {
                $goods['price'] = $avgPrice;
            }
        }
        
        $actualAmount = $totalPrice - $discountAmount;
        
        return [
            'total_price' => $totalPrice,
            'discount_amount' => $discountAmount,
            'actual_amount' => $actualAmount,
        ];
    }
    
    /**
     * 获取订单详情
     * @param string $orderNo 订单号
     * @param int $uid 用户ID（验证所属）
     * @return array
     */
    public function getOrderDetail(string $orderNo, int $uid = 0): array
    {
        $order = AiTravelPhotoOrder::with(['goods.result', 'package'])
            ->where('order_no', $orderNo)
            ->find();
        
        if (!$order) {
            throw new ValidateException('订单不存在');
        }
        
        // 验证用户权限
        if ($uid > 0 && $order->uid != $uid) {
            throw new ValidateException('无权查看此订单');
        }
        
        return $order->toArray();
    }
    
    /**
     * 订单列表
     * @param array $params 查询参数
     * @return array
     */
    public function getOrderList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        
        $query = AiTravelPhotoOrder::withSearch(['order_no', 'uid', 'bid', 'status', 'pay_time'], $params);
        
        // 关联商品信息
        if (!empty($params['with_goods'])) {
            $query->with(['goods']);
        }
        
        // 排序
        $query->order('create_time', 'desc');
        
        $list = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);
        
        return [
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'page_size' => $pageSize,
        ];
    }
    
    /**
     * 支付成功回调处理
     * @param string $orderNo 订单号
     * @param array $payData 支付数据
     * @return bool
     */
    public function paySuccessCallback(string $orderNo, array $payData): bool
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();
        
        if (!$order) {
            throw new ValidateException('订单不存在');
        }
        
        // 幂等性检查
        if ($order->status != AiTravelPhotoOrder::STATUS_UNPAID) {
            return true; // 已处理，直接返回成功
        }
        
        // 开启事务
        Db::startTrans();
        try {
            // 更新订单状态
            $order->pay_type = $payData['pay_type'] ?? '';
            $order->pay_no = $payData['pay_no'] ?? '';
            $order->transaction_id = $payData['transaction_id'] ?? '';
            $order->status = AiTravelPhotoOrder::STATUS_PAID;
            $order->pay_time = time();
            $order->save();
            
            // 更新二维码统计
            if ($order->qrcode_id > 0) {
                $qrcode = AiTravelPhotoQrcode::find($order->qrcode_id);
                if ($qrcode) {
                    $qrcode->order_count += 1;
                    $qrcode->order_amount += $order->actual_amount;
                    $qrcode->save();
                }
            }
            
            // 将商品添加到用户相册
            $goods = AiTravelPhotoOrderGoods::where('order_id', $order->id)->select();
            foreach ($goods as $item) {
                $result = AiTravelPhotoResult::find($item->result_id);
                if ($result) {
                    AiTravelPhotoUserAlbum::create([
                        'aid' => $order->aid,
                        'uid' => $order->uid,
                        'bid' => $order->bid,
                        'mdid' => $order->mdid,
                        'order_id' => $order->id,
                        'portrait_id' => $result->portrait_id,
                        'result_id' => $result->id,
                        'type' => $result->type == 19 ? 2 : 1,
                        'url' => $result->url, // 无水印原图
                        'thumbnail_url' => $result->thumbnail_url,
                        'status' => 1,
                    ]);
                    
                    // 更新结果购买次数
                    $result->buy_count += 1;
                    $result->save();
                }
            }
            
            // 更新商家统计
            $business = \app\model\Business::find($order->bid);
            if ($business) {
                $business->ai_total_sold += count($goods);
                $business->ai_total_income += $order->actual_amount;
                $business->save();
            }
            
            // 更新套餐销量
            if ($order->package_id > 0) {
                $package = AiTravelPhotoPackage::find($order->package_id);
                if ($package) {
                    $package->sale_count += 1;
                    $package->save();
                }
            }
            
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
    
    /**
     * 关闭超时订单
     * @return int 关闭的订单数
     */
    public function closeTimeoutOrders(): int
    {
        $timeout = config('ai_travel_photo.order.timeout', 1800); // 30分钟
        $timeoutTime = time() - $timeout;
        
        $orders = AiTravelPhotoOrder::where('status', AiTravelPhotoOrder::STATUS_UNPAID)
            ->where('create_time', '<', $timeoutTime)
            ->select();
        
        $count = 0;
        foreach ($orders as $order) {
            $order->status = AiTravelPhotoOrder::STATUS_CLOSED;
            $order->close_time = time();
            
            if ($order->save()) {
                // 如果是套餐购买，恢复库存
                if ($order->buy_type == AiTravelPhotoOrder::BUY_TYPE_PACKAGE && $order->package_id > 0) {
                    $package = AiTravelPhotoPackage::find($order->package_id);
                    if ($package && $package->stock >= 0) {
                        $package->stock += 1;
                        $package->save();
                    }
                }
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * 申请退款
     * @param string $orderNo 订单号
     * @param string $reason 退款原因
     * @param int $uid 用户ID
     * @return bool
     */
    public function applyRefund(string $orderNo, string $reason, int $uid): bool
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();
        
        if (!$order) {
            throw new ValidateException('订单不存在');
        }
        
        // 验证用户权限
        if ($order->uid != $uid) {
            throw new ValidateException('无权操作此订单');
        }
        
        // 检查订单状态
        if ($order->status != AiTravelPhotoOrder::STATUS_PAID) {
            throw new ValidateException('订单状态不允许退款');
        }
        
        // 检查退款状态
        if ($order->refund_status != AiTravelPhotoOrder::REFUND_STATUS_NONE) {
            throw new ValidateException('订单已申请退款');
        }
        
        $order->refund_status = AiTravelPhotoOrder::REFUND_STATUS_APPLYING;
        $order->refund_reason = $reason;
        $order->save();
        
        return true;
    }
}
