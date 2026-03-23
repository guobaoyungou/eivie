<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoOrder;
use app\model\AiTravelPhotoOrderGoods;
use app\model\AiTravelPhotoPackage;
use app\model\AiTravelPhotoPortrait;
use app\model\AiTravelPhotoQrcode;
use app\model\AiTravelPhotoResult;
use app\model\AiTravelPhotoUserAlbum;
use app\model\Payorder;
use app\service\MilvusService;
use think\facade\Db;
use think\facade\Log;

/**
 * 选片交付服务类
 * 
 * 核心业务：成片选片 → 套餐推荐 → 付费下载
 */
class AiTravelPhotoPickService
{
    /**
     * 通过二维码标识获取人像信息
     *
     * @param string $qrCode 二维码标识
     * @return array
     * @throws \Exception
     */
    public function getPortraitByQrcode(string $qrCode): array
    {
        $qrcode = AiTravelPhotoQrcode::where('qrcode', $qrCode)
            ->where('status', AiTravelPhotoQrcode::STATUS_VALID)
            ->find();

        if (!$qrcode) {
            throw new \Exception('二维码无效');
        }

        // 检查过期
        if ($qrcode->isExpired()) {
            throw new \Exception('二维码已过期，请联系商家重新生成');
        }

        $portrait = AiTravelPhotoPortrait::where('id', $qrcode->portrait_id)
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
            ->find();

        if (!$portrait) {
            throw new \Exception('人像不存在或已被删除');
        }

        return [
            'portrait_id' => $portrait->id,
            'aid' => $portrait->aid,
            'bid' => $portrait->bid,
            'qrcode_id' => $qrcode->id,
        ];
    }

    /**
     * 获取成片列表（缩略图 + 水印）
     * 包含当前人像的成片 + 相似人像（≥95%）的成片
     *
     * @param int $portraitId 人像ID
     * @param int $bid 商家ID（用于限定相似搜索范围）
     * @param int $aid 应用ID
     * @return array
     */
    public function getResultList(int $portraitId, int $bid = 0, int $aid = 0): array
    {
        // === 1. 当前人像的成片 ===
        $results = AiTravelPhotoResult::where('portrait_id', $portraitId)
            ->where('status', AiTravelPhotoResult::STATUS_NORMAL)
            ->field('id, type, url, thumbnail_url, watermark_url, width, height, create_time')
            ->order('type ASC, id ASC')
            ->select()
            ->toArray();

        $list = $this->formatResultItems($results);

        // === 2. 相似人像的成片（≥95%相似度） ===
        $similarList = [];
        $similarPortraitCount = 0;
        if ($bid > 0 || $aid > 0) {
            try {
                $similarPortraitIds = $this->findSimilarPortraitIds($portraitId, $bid, $aid, 0.95);
                $similarPortraitCount = count($similarPortraitIds);
                if (!empty($similarPortraitIds)) {
                    $similarResults = AiTravelPhotoResult::whereIn('portrait_id', $similarPortraitIds)
                        ->where('status', AiTravelPhotoResult::STATUS_NORMAL)
                        ->field('id, type, url, thumbnail_url, watermark_url, width, height, create_time')
                        ->order('create_time DESC, id DESC')
                        ->select()
                        ->toArray();
                    $similarList = $this->formatResultItems($similarResults);
                }
            } catch (\Exception $e) {
                Log::warning('相似人像搜索失败', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
            }
        }

        return [
            'portrait_id' => $portraitId,
            'results' => $list,
            'total' => count($list),
            'similar_results' => $similarList,
            'similar_total' => count($similarList),
            'similar_portrait_count' => $similarPortraitCount,
        ];
    }

    /**
     * 格式化成片列表项（生成缩略图URL等）
     *
     * @param array $results 数据库查询结果
     * @return array
     */
    private function formatResultItems(array $results): array
    {
        $list = [];
        foreach ($results as $item) {
            // 优先使用水印图 > 缩略图 > 原图
            $thumbUrl = $item['watermark_url'] ?: ($item['thumbnail_url'] ?: $item['url']);

            // 如果是COS原图且无缩略图/水印图，追加COS数据万象参数生成缩略图
            if ($thumbUrl && $thumbUrl === $item['url'] && strpos($thumbUrl, 'myqcloud.com') !== false) {
                $thumbUrl .= '?imageMogr2/thumbnail/600x/quality/80';
            }

            $list[] = [
                'id' => $item['id'],
                'thumbnail_url' => $thumbUrl ?: '',
                'type' => $item['type'],
                'width' => $item['width'],
                'height' => $item['height'],
            ];
        }
        return $list;
    }

    /**
     * 查找与指定人像相似度达标的其他人像ID
     *
     * 相似度计算：L2归一化128维向量的欧氏距离
     * - 98%相似度 → distance ≤ 0.20
     * - 95%相似度 → distance ≤ 0.316
     * 公式：cos_sim = 1 - distance²/2
     *
     * @param int $portraitId 当前人像ID
     * @param int $bid 商家ID
     * @param int $aid 应用ID
     * @param float $similarity 相似度阈值（0-1），默认0.95
     * @return array 匹配的人像ID数组（不含自身）
     */
    public function findSimilarPortraitIds(int $portraitId, int $bid, int $aid, float $similarity = 0.95): array
    {
        // 获取当前人像的人脸特征
        $currentPortrait = Db::name('ai_travel_photo_portrait')
            ->where('id', $portraitId)
            ->field('id, aid, bid, face_embedding, face_embedding_id')
            ->find();

        if (!$currentPortrait) {
            return [];
        }

        // 解析当前人像的特征向量
        $currentEmbedding = null;
        if (!empty($currentPortrait['face_embedding'])) {
            $decoded = json_decode($currentPortrait['face_embedding'], true);
            if (is_array($decoded) && count($decoded) >= 64) {
                $currentEmbedding = $decoded;
            }
        }

        if (empty($currentEmbedding)) {
            return []; // 当前人像没有人脸特征，无法匹配
        }

        // 将相似度转换为L2距离阈值: cos_sim = 1 - d²/2 → d = sqrt(2*(1-cos_sim))
        $distanceThreshold = sqrt(2 * (1 - $similarity));

        $matchedPortraitIds = [];
        $milvusUsed = false;

        // 优先尝试Milvus向量搜索
        try {
            $milvusService = new MilvusService();
            if ($milvusService->isHealthy()) {
                $searchResults = $milvusService->search($currentEmbedding, 50);
                if (!empty($searchResults)) {
                    foreach ($searchResults as $result) {
                        $dist = floatval($result['distance'] ?? 999);
                        $pid = intval($result['portrait_id'] ?? 0);
                        if ($dist <= $distanceThreshold && $pid > 0 && $pid !== $portraitId) {
                            $matchedPortraitIds[] = $pid;
                        }
                    }
                    $milvusUsed = true;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Milvus相似搜索失败，使用MySQL备用', ['error' => $e->getMessage()]);
        }

        // MySQL备用：遍历同商家/同应用下有face_embedding的人像
        if (!$milvusUsed) {
            $query = Db::name('ai_travel_photo_portrait')
                ->where('id', '<>', $portraitId)
                ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
                ->where('face_embedding', '<>', '')
                ->whereNotNull('face_embedding');

            // 限定同商家范围
            if ($bid > 0) {
                $query->where('bid', $bid);
            } elseif ($aid > 0) {
                $query->where('aid', $aid);
            }

            $portraits = $query->field('id, face_embedding')
                ->order('id DESC')
                ->limit(500) // 性能保护
                ->select()
                ->toArray();

            foreach ($portraits as $p) {
                $storedEmbedding = json_decode($p['face_embedding'], true);
                if (!is_array($storedEmbedding) || count($storedEmbedding) < 64) {
                    continue;
                }

                $distance = $this->vectorEuclideanDistance($currentEmbedding, $storedEmbedding);
                if ($distance <= $distanceThreshold) {
                    $matchedPortraitIds[] = intval($p['id']);
                }
            }
        }

        // 确保匹配到的人像确实有成片
        if (!empty($matchedPortraitIds)) {
            $matchedPortraitIds = AiTravelPhotoResult::whereIn('portrait_id', $matchedPortraitIds)
                ->where('status', AiTravelPhotoResult::STATUS_NORMAL)
                ->group('portrait_id')
                ->column('portrait_id');
        }

        return $matchedPortraitIds;
    }

    /**
     * 计算两个向量的欧氏距离
     *
     * @param array $v1 向量1
     * @param array $v2 向量2
     * @return float 欧氏距离
     */
    private function vectorEuclideanDistance(array $v1, array $v2): float
    {
        $len = min(count($v1), count($v2));
        $sum = 0.0;
        for ($i = 0; $i < $len; $i++) {
            $diff = floatval($v1[$i]) - floatval($v2[$i]);
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    /**
     * 获取商家启用的套餐列表
     *
     * @param int $bid 商家ID
     * @return array
     */
    public function getPackageList(int $bid): array
    {
        $packages = AiTravelPhotoPackage::where('bid', $bid)
            ->where('status', AiTravelPhotoPackage::STATUS_ENABLED)
            ->where(function ($query) {
                $query->where('start_time', 0)->whereOr('start_time', '<=', time());
            })
            ->where(function ($query) {
                $query->where('end_time', 0)->whereOr('end_time', '>=', time());
            })
            ->order('sort DESC, num ASC')
            ->select()
            ->toArray();

        $list = [];
        foreach ($packages as $pkg) {
            $unitPrice = $pkg['num'] > 0 ? round($pkg['price'] / $pkg['num'], 2) : $pkg['price'];
            $list[] = [
                'id' => $pkg['id'],
                'name' => $pkg['name'],
                'desc' => $pkg['desc'] ?? '',
                'num' => $pkg['num'],
                'price' => $pkg['price'],
                'original_price' => $pkg['original_price'],
                'unit_price' => $unitPrice,
                'tag' => $pkg['tag'] ?? '',
                'tag_color' => $pkg['tag_color'] ?? '',
                'label' => $pkg['label'] ?? '',
                'is_default' => $pkg['is_default'] ?? 0,
            ];
        }

        return $list;
    }

    /**
     * 获取商家单张购买单价
     * 从价格档位表中取 num=1 的记录作为单张价格
     *
     * @param int $bid 商家ID
     * @return float
     */
    public function getSinglePrice(int $bid): float
    {
        // 优先从价格档位中查找 num=1 的记录
        $singlePkg = AiTravelPhotoPackage::where('bid', $bid)
            ->where('status', AiTravelPhotoPackage::STATUS_ENABLED)
            ->where('num', 1)
            ->order('price ASC')
            ->find();

        if ($singlePkg) {
            return (float)$singlePkg->price;
        }

        // 如果没有 num=1 的档位，用最小档的单价
        $minPkg = AiTravelPhotoPackage::where('bid', $bid)
            ->where('status', AiTravelPhotoPackage::STATUS_ENABLED)
            ->where('num', '>', 0)
            ->order('num ASC')
            ->find();

        if ($minPkg && $minPkg->num > 0) {
            return round((float)$minPkg->price / $minPkg->num, 2);
        }

        // 无任何价格档位，默认单张价格
        return 9.90;
    }

    /**
     * 智能价格匹配算法
     * 根据用户选片数量，自动匹配总价最优的价格档位
     *
     * 算法逻辑：
     * - 为每个价格档位计算“用户实际需要支付的总价”
     * - 若选片数 ≤ 档位张数，总价 = 档位价格
     * - 若选片数 > 档位张数，总价 = 档位价格 + 超出部分×单张价
     * - 选择总价最低的档位推荐给用户
     *
     * @param int $selectedCount 用户选中的成片数量
     * @param int $bid 商家ID
     * @return array
     */
    public function recommendPackage(int $selectedCount, int $bid): array
    {
        $packages = $this->getPackageList($bid);
        $singlePrice = $this->getSinglePrice($bid);
        $singleTotal = round($singlePrice * $selectedCount, 2);

        if (empty($packages)) {
            return [
                'recommended' => null,
                'all_packages' => [],
                'single_price' => $singlePrice,
                'single_total' => $singleTotal,
                'guide_text' => '',
            ];
        }

        // 按张数升序排列
        usort($packages, function ($a, $b) {
            return $a['num'] - $b['num'];
        });

        // 为每个档位计算用户实际支付总价，选择最优
        $bestTotal = $singleTotal; // 无档位时按单张价
        $bestPackage = null;

        foreach ($packages as $pkg) {
            $pkgPrice = (float)$pkg['price'];
            $pkgNum = (int)$pkg['num'];

            if ($selectedCount <= $pkgNum) {
                // 选片数 ≤ 档位张数，直接按档位价
                $total = $pkgPrice;
            } else {
                // 选片数 > 档位张数，档位价 + 超出部分按单张价
                $total = round($pkgPrice + ($selectedCount - $pkgNum) * $singlePrice, 2);
            }

            // 选择总价最低的档位
            if ($total < $bestTotal || ($total <= $bestTotal && $bestPackage === null)) {
                $bestTotal = $total;
                $bestPackage = $pkg;
            }
        }

        // 生成引导文案
        $guideText = '';
        $saveAmount = round($singleTotal - $bestTotal, 2);

        if ($bestPackage !== null) {
            $bestPackage['save_amount'] = max(0, $saveAmount);

            // 基础文案：显示节省金额
            if ($saveAmount > 0) {
                $guideText = '比单张购买省¥' . $saveAmount;
            }

            // 查找下一个更高档位，提供升级建议
            $nextTier = null;
            foreach ($packages as $pkg) {
                if ((int)$pkg['num'] > $selectedCount) {
                    $nextTier = $pkg;
                    break; // 已按num升序，第一个即为最近的更高档
                }
            }

            if ($nextTier) {
                $diff = (int)$nextTier['num'] - $selectedCount;
                $nextUnitPrice = round((float)$nextTier['price'] / (int)$nextTier['num'], 2);
                $currentUnitPrice = round($bestTotal / $selectedCount, 2);

                if ($nextUnitPrice < $currentUnitPrice && $diff > 0) {
                    $guideText = '再选' . $diff . '张，享¥' . $nextUnitPrice . '/张优惠';
                }
            }
        }

        return [
            'recommended' => $bestPackage,
            'all_packages' => $packages,
            'single_price' => $singlePrice,
            'single_total' => $singleTotal,
            'guide_text' => $guideText,
        ];
    }

    /**
     * 创建选片订单
     *
     * @param array $data 订单数据
     * @return array
     * @throws \Exception
     */
    public function createPickOrder(array $data): array
    {
        $portraitId = (int)($data['portrait_id'] ?? 0);
        $resultIds = $data['result_ids'] ?? [];
        $packageId = (int)($data['package_id'] ?? 0);
        $openid = $data['openid'] ?? '';
        $aid = (int)($data['aid'] ?? 0);
        $bid = (int)($data['bid'] ?? 0);
        $qrcodeId = (int)($data['qrcode_id'] ?? 0);

        if ($portraitId <= 0) {
            throw new \Exception('人像ID不能为空');
        }
        if (empty($resultIds)) {
            throw new \Exception('请至少选择一张成片');
        }

        // 查找当前人像 + 相似人像的所有合法portrait_id
        $allowedPortraitIds = [$portraitId];
        if ($bid > 0 || $aid > 0) {
            try {
                $similarIds = $this->findSimilarPortraitIds($portraitId, $bid, $aid, 0.95);
                $allowedPortraitIds = array_merge($allowedPortraitIds, $similarIds);
            } catch (\Exception $e) {
                // 相似搜索失败不影响主流程
            }
        }

        // 验证成片归属（允许当前人像 + 相似人像的成片）
        $validResults = AiTravelPhotoResult::whereIn('portrait_id', $allowedPortraitIds)
            ->whereIn('id', $resultIds)
            ->where('status', AiTravelPhotoResult::STATUS_NORMAL)
            ->select();

        if (count($validResults) !== count($resultIds)) {
            throw new \Exception('部分成片不存在或无权访问');
        }

        $selectedCount = count($resultIds);
        $singlePrice = $this->getSinglePrice($bid);

        // 计算金额
        $buyType = AiTravelPhotoOrder::BUY_TYPE_SINGLE;
        $totalPrice = round($singlePrice * $selectedCount, 2);
        $actualAmount = $totalPrice;
        $packageSnapshot = null;
        $downloadLimit = $selectedCount;
        $packageName = '单张购买';

        if ($packageId > 0) {
            $package = AiTravelPhotoPackage::where('id', $packageId)
                ->where('bid', $bid)
                ->where('status', AiTravelPhotoPackage::STATUS_ENABLED)
                ->find();

            if (!$package) {
                throw new \Exception('套餐不存在或已下架');
            }

            if (!$package->isValid()) {
                throw new \Exception('套餐已过期');
            }

            if (!$package->hasStock()) {
                throw new \Exception('套餐库存不足');
            }

            $buyType = AiTravelPhotoOrder::BUY_TYPE_PACKAGE;
            $packageName = $package->name;

            if ($selectedCount <= $package->num) {
                // 选片数 <= 套餐包含数，按套餐价
                $totalPrice = (float)$package->price;
                $actualAmount = $totalPrice;
                $downloadLimit = $package->num;
            } else {
                // 选片数 > 套餐包含数，套餐价 + 超出部分按单价
                $extraCount = $selectedCount - $package->num;
                $totalPrice = round($package->price + $singlePrice * $extraCount, 2);
                $actualAmount = $totalPrice;
                $downloadLimit = $selectedCount;
            }

            $packageSnapshot = json_encode([
                'id' => $package->id,
                'name' => $package->name,
                'num' => $package->num,
                'price' => $package->price,
            ], JSON_UNESCAPED_UNICODE);
        }

        // 开启事务
        Db::startTrans();
        try {
            $orderNo = AiTravelPhotoOrder::generateOrderNo();

            // 创建订单
            $order = AiTravelPhotoOrder::create([
                'aid' => $aid,
                'order_no' => $orderNo,
                'qrcode_id' => $qrcodeId,
                'portrait_id' => $portraitId,
                'uid' => (int)($data['uid'] ?? 0),
                'bid' => $bid,
                'buy_type' => $buyType,
                'package_id' => $packageId,
                'selected_count' => $selectedCount,
                'package_snapshot' => $packageSnapshot,
                'download_count' => 0,
                'download_limit' => $downloadLimit,
                'openid' => $openid,
                'total_price' => $totalPrice,
                'discount_amount' => 0,
                'actual_amount' => $actualAmount,
                'status' => AiTravelPhotoOrder::STATUS_UNPAID,
            ]);

            // 创建订单商品
            foreach ($validResults as $result) {
                AiTravelPhotoOrderGoods::create([
                    'aid' => $aid,
                    'order_id' => $order->id,
                    'result_id' => $result->id,
                    'type' => ($result->type == 19) ? 2 : 1,
                    'goods_image' => $result->watermark_url ?: $result->thumbnail_url,
                    'price' => ($packageId > 0) ? round($actualAmount / $selectedCount, 2) : $singlePrice,
                    'num' => 1,
                    'total_price' => ($packageId > 0) ? round($actualAmount / $selectedCount, 2) : $singlePrice,
                    'status' => AiTravelPhotoOrderGoods::STATUS_NORMAL,
                    'is_downloaded' => 0,
                    'download_url' => null,
                    'download_time' => 0,
                ]);
            }

            // 套餐购买扣库存
            if ($packageId > 0 && isset($package) && $package->stock > 0) {
                Db::name('ai_travel_photo_package')
                    ->where('id', $packageId)
                    ->where('stock', '>', 0)
                    ->dec('stock', 1)
                    ->update();
            }

            Db::commit();

            return [
                'order_no' => $orderNo,
                'total_price' => $totalPrice,
                'actual_amount' => $actualAmount,
                'package_name' => $packageName,
                'selected_count' => $selectedCount,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 发起微信JSAPI支付
     *
     * @param string $orderNo 订单号
     * @param string $openid  微信OpenID
     * @return array 微信JSAPI支付参数
     * @throws \Exception
     */
    public function createPayment(string $orderNo, string $openid): array
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();

        if (!$order) {
            throw new \Exception('订单不存在');
        }

        if ($order->status != AiTravelPhotoOrder::STATUS_UNPAID) {
            throw new \Exception('订单状态异常，不可支付');
        }

        if ($order->isTimeout()) {
            throw new \Exception('订单已超时，请重新下单');
        }

        $actualAmount = (float)$order->actual_amount;
        if ($actualAmount <= 0) {
            throw new \Exception('订单金额异常');
        }

        // 获取商家的微信支付配置
        $aid = $order->aid;
        $bid = $order->bid;

        // 通过 Payorder 模型创建通用支付单
        $mid = 0; // H5扫码用户无会员ID，用0
        $payorderId = Payorder::createorder(
            $aid,
            $bid,
            $mid,
            'ai_travel_photo', // 订单类型
            $order->id,
            $orderNo,
            '笑脸抓拍照片-' . $orderNo,
            $actualAmount
        );

        if (!$payorderId) {
            throw new \Exception('创建支付单失败');
        }

        // 获取微信H5支付配置
        $wxConfig = $this->getWxPayConfig($aid, $bid);

        // 调用微信统一下单 (JSAPI)
        $unifiedResult = $this->wxUnifiedOrder($wxConfig, $orderNo, $actualAmount, $openid);

        if (empty($unifiedResult['prepay_id'])) {
            throw new \Exception('微信下单失败：' . ($unifiedResult['err_code_des'] ?? $unifiedResult['return_msg'] ?? '未知错误'));
        }

        // 构建JSAPI支付参数
        $jsApiParams = $this->buildJsApiParams($wxConfig, $unifiedResult['prepay_id']);

        return [
            'payorder_id' => $payorderId,
            'js_api_params' => $jsApiParams,
            'order_no' => $orderNo,
        ];
    }

    /**
     * 查询订单支付状态
     * 如果数据库状态为未支付，会主动查询微信支付API作为回退机制
     *
     * @param string $orderNo 订单号
     * @return array
     */
    public function getPayStatus(string $orderNo): array
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();

        if (!$order) {
            return ['status' => 'not_found'];
        }

        if ($order->status == AiTravelPhotoOrder::STATUS_PAID || $order->status == AiTravelPhotoOrder::STATUS_COMPLETED) {
            return [
                'status' => 'paid',
                'pay_time' => $order->pay_time,
                'order_no' => $orderNo,
            ];
        }

        // 数据库显示未支付，主动查询微信支付状态作为回退（处理notify回调失败的情况）
        try {
            $wxConfig = $this->getWxPayConfig($order->aid, $order->bid);
            $queryResult = $this->wxOrderQuery($wxConfig, $orderNo);

            Log::info('主动查询微信支付状态', ['order_no' => $orderNo, 'trade_state' => $queryResult['trade_state'] ?? 'unknown']);

            if (($queryResult['trade_state'] ?? '') === 'SUCCESS') {
                // 微信确认已支付，触发履约
                $this->paySuccessfulfilment($orderNo, [
                    'transaction_id' => $queryResult['transaction_id'] ?? '',
                    'pay_type' => 'wechat',
                ]);

                return [
                    'status' => 'paid',
                    'pay_time' => time(),
                    'order_no' => $orderNo,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('主动查询微信支付状态失败', ['order_no' => $orderNo, 'error' => $e->getMessage()]);
        }

        return [
            'status' => 'unpaid',
            'order_status' => $order->status,
        ];
    }

    /**
     * 支付成功履约
     * 
     * @param string $orderNo 订单号
     * @param array $payData 支付数据
     * @return bool
     */
    public function paySuccessfulfilment(string $orderNo, array $payData = []): bool
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();

        if (!$order) {
            Log::error('选片订单履约失败：订单不存在 ' . $orderNo);
            return false;
        }

        // 幂等性检查
        if ($order->status != AiTravelPhotoOrder::STATUS_UNPAID) {
            return true;
        }

        Db::startTrans();
        try {
            // 1. 更新订单状态为已支付
            $order->status = AiTravelPhotoOrder::STATUS_PAID;
            $order->pay_time = time();
            if (!empty($payData['transaction_id'])) {
                $order->transaction_id = $payData['transaction_id'];
            }
            $order->save();

            // 2. 写入 order_goods.download_url（无水印原图URL）
            $goods = AiTravelPhotoOrderGoods::where('order_id', $order->id)->select();
            foreach ($goods as $item) {
                $result = AiTravelPhotoResult::find($item->result_id);
                if ($result) {
                    $item->download_url = $result->url; // 无水印原图
                    $item->save();

                    // 3. 写入 user_album
                    AiTravelPhotoUserAlbum::create([
                        'aid' => $order->aid,
                        'uid' => $order->uid,
                        'bid' => $order->bid,
                        'order_id' => $order->id,
                        'portrait_id' => $order->portrait_id,
                        'result_id' => $result->id,
                        'type' => ($result->type == 19) ? 2 : 1,
                        'url' => $result->url,
                        'thumbnail_url' => $result->thumbnail_url,
                        'status' => 1,
                    ]);

                    // 更新结果购买次数
                    $result->buy_count += 1;
                    $result->save();
                }
            }

            // 4. 更新 qrcode 统计
            if ($order->qrcode_id > 0) {
                Db::name('ai_travel_photo_qrcode')
                    ->where('id', $order->qrcode_id)
                    ->inc('order_count', 1)
                    ->inc('order_amount', $order->actual_amount)
                    ->update();
            }

            // 5. 更新 package 销量统计
            if ($order->package_id > 0) {
                Db::name('ai_travel_photo_package')
                    ->where('id', $order->package_id)
                    ->inc('sale_count', 1)
                    ->update();
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('选片订单履约异常：' . $e->getMessage() . ' 订单号：' . $orderNo);
            return false;
        }
    }

    /**
     * 获取下载列表（支付成功后）
     *
     * @param string $orderNo 订单号
     * @param string $openid 微信OpenID
     * @return array
     * @throws \Exception
     */
    public function getDownloadList(string $orderNo, string $openid): array
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();

        if (!$order) {
            throw new \Exception('订单不存在');
        }

        // 验证身份
        if ($order->openid && $order->openid !== $openid) {
            throw new \Exception('无权访问此订单');
        }

        // 验证支付状态
        if ($order->status != AiTravelPhotoOrder::STATUS_PAID && $order->status != AiTravelPhotoOrder::STATUS_COMPLETED) {
            throw new \Exception('订单未支付');
        }

        $goods = AiTravelPhotoOrderGoods::where('order_id', $order->id)
            ->select()
            ->toArray();

        $downloads = [];
        foreach ($goods as $item) {
            $result = AiTravelPhotoResult::find($item['result_id']);
            $downloads[] = [
                'id' => $item['id'],
                'result_id' => $item['result_id'],
                'type' => $item['type'],
                'download_url' => $item['download_url'] ?: ($result ? $result->url : ''),
                'thumbnail_url' => $result ? ($result->thumbnail_url ?: $result->watermark_url) : '',
                'is_downloaded' => $item['is_downloaded'],
            ];
        }

        return [
            'order_no' => $orderNo,
            'package_name' => $order->package_snapshot ? json_decode($order->package_snapshot, true)['name'] ?? '' : '单张购买',
            'downloads' => $downloads,
            'total' => count($downloads),
            'aid' => $order->aid,
            'bid' => $order->bid,
            'portrait_id' => $order->portrait_id,
        ];
    }

    /**
     * 记录下载
     *
     * @param int $goodsId 订单商品ID
     * @param string $openid 微信OpenID
     * @return bool
     */
    public function recordDownload(int $goodsId, string $openid): bool
    {
        $goods = AiTravelPhotoOrderGoods::find($goodsId);
        if (!$goods) return false;

        $order = AiTravelPhotoOrder::find($goods->order_id);
        if (!$order || ($order->openid && $order->openid !== $openid)) return false;

        if (!$goods->is_downloaded) {
            $goods->is_downloaded = 1;
            $goods->download_time = time();
            $goods->save();

            // 更新订单下载次数
            Db::name('ai_travel_photo_order')
                ->where('id', $order->id)
                ->inc('download_count', 1)
                ->update();
        }

        return true;
    }

    /**
     * 记录二维码扫码
     *
     * @param int $qrcodeId 二维码ID
     * @param string $openid 微信OpenID
     * @return void
     */
    public function recordScan(int $qrcodeId, string $openid): void
    {
        $cacheKey = 'pick_scan:' . $qrcodeId . ':' . $openid;

        // 10秒内不重复计数
        if (cache($cacheKey)) {
            return;
        }

        cache($cacheKey, 1, 10);

        Db::name('ai_travel_photo_qrcode')
            ->where('id', $qrcodeId)
            ->inc('scan_count', 1)
            ->update();

        // 首次扫码/唯一扫码计数
        $firstScan = Db::name('ai_travel_photo_qrcode')
            ->where('id', $qrcodeId)
            ->value('first_scan_time');

        $updateData = ['last_scan_time' => time()];
        if (!$firstScan) {
            $updateData['first_scan_time'] = time();
        }

        Db::name('ai_travel_photo_qrcode')
            ->where('id', $qrcodeId)
            ->update($updateData);
    }

    // ========== 我的订单 ==========

    /**
     * 获取当前用户的已支付订单列表
     *
     * @param string $openid 微信OpenID
     * @return array 订单列表
     */
    public function getMyOrders(string $openid): array
    {
        if (empty($openid)) {
            return [];
        }

        $orders = AiTravelPhotoOrder::where('openid', $openid)
            ->whereIn('status', [
                AiTravelPhotoOrder::STATUS_PAID,
                AiTravelPhotoOrder::STATUS_COMPLETED,
            ])
            ->order('pay_time', 'desc')
            ->limit(50)
            ->select();

        $list = [];
        foreach ($orders as $order) {
            // 获取订单的第一张商品图作为封面
            $firstGoods = AiTravelPhotoOrderGoods::where('order_id', $order->id)
                ->order('id', 'asc')
                ->find();
            $coverImage = '';
            if ($firstGoods && $firstGoods->goods_image) {
                $coverImage = $firstGoods->goods_image;
            }

            // 获取套餐名称
            $packageName = '单张购买';
            if ($order->package_snapshot) {
                $snapshot = json_decode($order->package_snapshot, true);
                if ($snapshot && !empty($snapshot['name'])) {
                    $packageName = $snapshot['name'];
                }
            }

            $statusText = '已支付';
            if ($order->status == AiTravelPhotoOrder::STATUS_COMPLETED) {
                $statusText = '已完成';
            }

            $list[] = [
                'order_no' => $order->order_no,
                'cover_image' => $coverImage,
                'package_name' => $packageName,
                'selected_count' => $order->selected_count,
                'actual_amount' => $order->actual_amount,
                'status' => $order->status,
                'status_text' => $statusText,
                'pay_time' => $order->pay_time ? date('Y-m-d H:i', (int)$order->pay_time) : '',
                'download_url' => '/public/pick/download.html?order_no=' . $order->order_no,
            ];
        }

        return $list;
    }

    // ========== 公众号信息与视频生成 ==========

    /**
     * 获取公众号信息（关注引导）
     */
    public function getMpInfo(int $aid): array
    {
        $mpset = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
        return [
            'nickname' => $mpset['nickname'] ?? '',
            'headimg' => $mpset['headimg'] ?? '',
            'qrcode' => $mpset['qrcode'] ?? '',
        ];
    }

    /**
     * 一键生成视频幻灯片（FFmpeg）
     *
     * @param string $orderNo 订单号
     * @return array ['video_url' => string, 'cached' => bool]
     */
    public function generateSlideshow(string $orderNo): array
    {
        $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();
        if (!$order) throw new \Exception('订单不存在');
        if ($order->status != AiTravelPhotoOrder::STATUS_PAID && $order->status != AiTravelPhotoOrder::STATUS_COMPLETED) {
            throw new \Exception('订单未支付');
        }

        // 检查缓存
        $outputDir = app()->getRootPath() . 'upload/' . $order->aid . '/video/';
        if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);
        $outputFilename = 'slideshow_' . $orderNo . '.mp4';
        $outputPath = $outputDir . $outputFilename;

        if (file_exists($outputPath) && filesize($outputPath) > 0) {
            return [
                'video_url' => request()->domain() . '/upload/' . $order->aid . '/video/' . $outputFilename,
                'cached' => true,
            ];
        }

        // 获取照片URL
        $goods = AiTravelPhotoOrderGoods::where('order_id', $order->id)->select();
        $imageUrls = [];
        foreach ($goods as $item) {
            $result = AiTravelPhotoResult::find($item->result_id);
            if ($result && $result->url) {
                $imageUrls[] = $result->url;
            }
        }
        if (empty($imageUrls)) throw new \Exception('没有可用的照片');

        // 下载图片到临时目录
        $tempDir = sys_get_temp_dir() . '/pick_video_' . $orderNo . '_' . time() . '/';
        mkdir($tempDir, 0777, true);

        try {
            $localFiles = [];
            foreach ($imageUrls as $i => $url) {
                $localPath = $tempDir . 'img_' . str_pad((string)$i, 3, '0', STR_PAD_LEFT) . '.jpg';
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT => 30,
                ]);
                $data = curl_exec($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($data && $httpCode === 200) {
                    file_put_contents($localPath, $data);
                    $localFiles[] = $localPath;
                }
            }
            if (empty($localFiles)) throw new \Exception('下载照片失败');

            // 创建 FFmpeg concat 文件
            $listFile = $tempDir . 'filelist.txt';
            $listContent = '';
            foreach ($localFiles as $file) {
                $escaped = str_replace("'", "'\\''", $file);
                $listContent .= "file '{$escaped}'\nduration 3\n";
            }
            $lastEscaped = str_replace("'", "'\\''", end($localFiles));
            $listContent .= "file '{$lastEscaped}'\n";
            file_put_contents($listFile, $listContent);

            // 运行 FFmpeg
            $escapedList = escapeshellarg($listFile);
            $escapedOutput = escapeshellarg($outputPath);
            $cmd = "ffmpeg -y -f concat -safe 0 -i {$escapedList} " .
                   "-vf 'scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2:black,format=yuv420p' " .
                   "-c:v libx264 -preset fast -crf 23 -r 25 " .
                   "-pix_fmt yuv420p -movflags +faststart " .
                   "{$escapedOutput} 2>&1";

            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($outputPath)) {
                Log::error('FFmpeg视频生成失败', ['cmd' => $cmd, 'output' => implode("\n", $output)]);
                throw new \Exception('视频生成失败，请稍后重试');
            }

            $videoUrl = request()->domain() . '/upload/' . $order->aid . '/video/' . $outputFilename;
            return ['video_url' => $videoUrl, 'cached' => false];
        } finally {
            // 清理临时文件
            $files = glob($tempDir . '*');
            if ($files) array_map('unlink', $files);
            if (is_dir($tempDir)) @rmdir($tempDir);
        }
    }

    // ========== 微信支付辅助方法 ==========

    /**
     * 获取微信支付配置
     */
    protected function getWxPayConfig(int $aid, int $bid): array
    {
        // 获取H5公众号的支付配置
        $wxset = Db::name('admin_setapp_mp')->where('aid', $aid)->find();

        return [
            'appid' => $wxset['appid'] ?? '',
            'appsecret' => $wxset['appsecret'] ?? '',
            'mchid' => $wxset['wxpay_mchid'] ?? '',
            'mchkey' => $wxset['wxpay_mchkey'] ?? '',
            'notify_url' => request()->domain() . '/index.php?s=/api/ai_travel_photo/pick/notify',
        ];
    }

    /**
     * 微信统一下单 (JSAPI)
     */
    protected function wxUnifiedOrder(array $config, string $orderNo, float $amount, string $openid): array
    {
        $params = [
            'appid' => $config['appid'],
            'mch_id' => $config['mchid'],
            'nonce_str' => md5(uniqid((string)mt_rand(), true)),
            'body' => '笑脸抓拍照片',
            'out_trade_no' => $orderNo,
            'total_fee' => (int)round($amount * 100),
            'spbill_create_ip' => request()->ip(),
            'notify_url' => $config['notify_url'],
            'trade_type' => 'JSAPI',
            'openid' => $openid,
        ];

        // 生成签名
        ksort($params);
        $stringA = '';
        foreach ($params as $k => $v) {
            if ($v != '' && $k != 'sign') {
                $stringA .= $k . '=' . $v . '&';
            }
        }
        $stringSignTemp = $stringA . 'key=' . $config['mchkey'];
        $params['sign'] = strtoupper(md5($stringSignTemp));

        // 转XML
        $xml = '<xml>';
        foreach ($params as $k => $v) {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
        $xml .= '</xml>';

        // 发送请求
        $ch = curl_init('https://api.mch.weixin.qq.com/pay/unifiedorder');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        curl_close($ch);

        // 解析XML
        $result = $this->xmlToArray($response);
        return $result;
    }

    /**
     * 主动查询微信支付订单状态
     * 用于notify回调失败时的回退机制
     * 
     * @see https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_2
     */
    protected function wxOrderQuery(array $config, string $orderNo): array
    {
        $params = [
            'appid' => $config['appid'],
            'mch_id' => $config['mchid'],
            'out_trade_no' => $orderNo,
            'nonce_str' => md5(uniqid((string)mt_rand(), true)),
        ];

        // 生成签名
        ksort($params);
        $stringA = '';
        foreach ($params as $k => $v) {
            if ($v != '' && $k != 'sign') {
                $stringA .= $k . '=' . $v . '&';
            }
        }
        $params['sign'] = strtoupper(md5($stringA . 'key=' . $config['mchkey']));

        // 转XML
        $xml = '<xml>';
        foreach ($params as $k => $v) {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
        $xml .= '</xml>';

        // 发送请求
        $ch = curl_init('https://api.mch.weixin.qq.com/pay/orderquery');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        return $this->xmlToArray($response);
    }

    /**
     * 构建JSAPI支付参数
     */
    protected function buildJsApiParams(array $config, string $prepayId): array
    {
        $params = [
            'appId' => $config['appid'],
            'timeStamp' => (string)time(),
            'nonceStr' => md5(uniqid((string)mt_rand(), true)),
            'package' => 'prepay_id=' . $prepayId,
            'signType' => 'MD5',
        ];

        // 签名
        ksort($params);
        $stringA = '';
        foreach ($params as $k => $v) {
            $stringA .= $k . '=' . $v . '&';
        }
        $params['paySign'] = strtoupper(md5($stringA . 'key=' . $config['mchkey']));

        return $params;
    }

    /**
     * 验证微信支付回调签名
     * 注意：XML解析后空元素可能变成数组，必须过滤非标量值
     */
    public function verifyWxNotifySign(array $data, string $mchKey): bool
    {
        $sign = $data['sign'] ?? '';
        unset($data['sign']);
        ksort($data);

        $stringA = '';
        foreach ($data as $k => $v) {
            // 过滤非标量值（空XML元素解析后会变成空数组）和空值
            if (!is_scalar($v) || (string)$v === '' || $k === 'sign') {
                continue;
            }
            $stringA .= $k . '=' . $v . '&';
        }
        $expectedSign = strtoupper(md5($stringA . 'key=' . $mchKey));

        Log::info('选片支付回调验签', [
            'sign_match' => ($sign === $expectedSign),
            'received_sign' => $sign,
            'expected_sign' => $expectedSign,
        ]);

        return $sign === $expectedSign;
    }

    /**
     * XML转数组
     * 增强版：确保所有值都是字符串（处理空元素和嵌套元素）
     */
    protected function xmlToArray($xml): array
    {
        if (!$xml) return [];
        libxml_disable_entity_loader(true);
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($obj === false) return [];
        $arr = json_decode(json_encode($obj), true) ?: [];
        // 确保所有值都是字符串（空XML元素会变成空数组）
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = '';
            }
        }
        return $arr;
    }
}
