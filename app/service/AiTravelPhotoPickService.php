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
            'mdid' => $portrait->mdid ?? 0,
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
     * 获取门店内某用户(openid)所有已合成成片（聚合多个portrait）
     *
     * 门店模式：用户重复扫码时，直接展示该openid在该门店的所有成片
     *
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param string $openid 用户openid
     * @return array
     */
    public function getStoreResultListByOpenid(int $bid, int $mdid, string $openid): array
    {
        // 查找该openid在该门店所有已合成完成的人像
        $portraits = Db::name('ai_travel_photo_portrait')
            ->where('user_openid', $openid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
            ->where('synthesis_status', 3) // 合成已完成
            ->field('id, aid')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        if (empty($portraits)) {
            return [
                'portrait_ids' => [],
                'results' => [],
                'total' => 0,
                'similar_results' => [],
                'similar_total' => 0,
            ];
        }

        $portraitIds = array_column($portraits, 'id');
        $aid = (int)$portraits[0]['aid'];

        // 聚合所有portrait的成片
        $results = AiTravelPhotoResult::whereIn('portrait_id', $portraitIds)
            ->where('status', AiTravelPhotoResult::STATUS_NORMAL)
            ->field('id, type, url, thumbnail_url, watermark_url, width, height, create_time')
            ->order('create_time DESC, id DESC')
            ->select()
            ->toArray();

        $list = $this->formatResultItems($results);

        return [
            'portrait_ids' => array_map('intval', $portraitIds),
            'aid' => $aid,
            'results' => $list,
            'total' => count($list),
            'similar_results' => [],  // 门店模式不需要相似推荐
            'similar_total' => 0,
        ];
    }

    /**
     * 检查用户在门店是否有已合成完成的成片
     *
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param string $openid 用户openid
     * @return bool
     */
    public function hasCompletedResultsInStore(int $bid, int $mdid, string $openid): bool
    {
        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('user_openid', $openid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
            ->where('synthesis_status', 3)
            ->field('id')
            ->find();

        if (!$portrait) {
            return false;
        }

        $resultCount = AiTravelPhotoResult::where('portrait_id', (int)$portrait['id'])
            ->where('status', AiTravelPhotoResult::STATUS_NORMAL)
            ->count();

        return $resultCount > 0;
    }

    /**
     * 格式化成片列表项（生成缩略图URL等）
     *
     * @param array $results 数据库查询结果
     * @return array
     */
    private function formatResultItems(array $results): array
    {
        return $this->formatResultItemsPublic($results);
    }

    /**
     * 格式化成片列表项（公共方法，供控制器调用）
     */
    public function formatResultItemsPublic(array $results): array
    {
        $list = [];
        foreach ($results as $item) {
            // 缩略图：优先使用水印图 > 缩略图 > 原图
            $thumbUrl = $item['watermark_url'] ?: ($item['thumbnail_url'] ?: $item['url']);

            // 如果是COS原图且无缩略图/水印图，追加COS数据万象参数生成缩略图
            if ($thumbUrl && $thumbUrl === $item['url'] && strpos($thumbUrl, 'myqcloud.com') !== false) {
                $thumbUrl .= '?imageMogr2/thumbnail/600x/quality/80';
            }

            // 预览大图：优先使用水印图 > 原图 > 缩略图
            $previewUrl = $item['watermark_url'] ?: ($item['url'] ?: ($item['thumbnail_url'] ?: ''));

            $list[] = [
                'id' => $item['id'],
                'thumbnail_url' => $thumbUrl ?: '',
                'preview_url' => $previewUrl ?: '',
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
     * 获取商家启用的套餐列表（阶梯档位模式）
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
            ->order('sort DESC, min_num ASC')
            ->select();

        $list = [];
        foreach ($packages as $pkg) {
            // 使用min_num计算折合单价
            $minNum = (int)$pkg->min_num;
            $unitPrice = $minNum > 0 ? round((float)$pkg->price / $minNum, 2) : (float)$pkg->price;
            
            // 生成档位展示文本
            $tierDisplay = $pkg->tier_display;
            $videoTierDisplay = $pkg->video_tier_display;

            $list[] = [
                'id' => $pkg->id,
                'name' => $pkg->name,
                'desc' => $pkg->desc ?? '',
                'num' => $pkg->num, // 兼容旧字段
                'min_num' => (int)$pkg->min_num,
                'max_num' => (int)$pkg->max_num,
                'min_video_num' => (int)$pkg->min_video_num,
                'max_video_num' => (int)$pkg->max_video_num,
                'tier_display' => $tierDisplay,
                'video_tier_display' => $videoTierDisplay,
                'price' => (float)$pkg->price,
                'original_price' => (float)$pkg->original_price,
                'unit_price' => $unitPrice,
                'tag' => $pkg->tag ?? '',
                'tag_color' => $pkg->tag_color ?? '',
                'label' => $pkg->label ?? '',
                'is_default' => $pkg->is_default ?? 0,
            ];
        }

        return $list;
    }

    /**
     * 获取商家单张购买单价
     * 优先从 min_num=1 的档位取价格，否则用最小档的单价
     *
     * @param int $bid 商家ID
     * @return float
     */
    public function getSinglePrice(int $bid): float
    {
        // 优先查找 min_num=1 的档位（单张档位）
        $singlePkg = AiTravelPhotoPackage::where('bid', $bid)
            ->where('status', AiTravelPhotoPackage::STATUS_ENABLED)
            ->where('min_num', 1)
            ->order('price ASC')
            ->find();

        if ($singlePkg) {
            return (float)$singlePkg->price;
        }

        // 如果没有，用最小档的折合单价
        $minPkg = AiTravelPhotoPackage::where('bid', $bid)
            ->where('status', AiTravelPhotoPackage::STATUS_ENABLED)
            ->where('min_num', '>', 0)
            ->order('min_num ASC')
            ->find();

        if ($minPkg && $minPkg->min_num > 0) {
            return round((float)$minPkg->price / $minPkg->min_num, 2);
        }

        // 无任何价格档位，默认单张价格
        return 9.90;
    }

    /**
     * 阶梯档位匹配算法
     * 根据用户选片数量，匹配落入的阶梯档位区间
     *
     * 匹配规则：
     * 1. 查询商家所有启用状态的套餐档位，按 min_num 升序排列
     * 2. 遍历档位列表，找到满足 min_num ≤ selected_count < max_num 的档位
     * 3. max_num=0 视为不限上限（∞）
     * 4. 若选片数低于所有档位最低下限 → 按单张价格计算
     * 5. 若选片数超过最高档位上限（不含∞档）→ 匹配 max_num=0 的无上限档位
     *
     * @param int $selectedCount 用户选中的成片数量
     * @param int $bid 商家ID
     * @param int $videoCount 用户选中的视频数量（默认0）
     * @return array
     */
    public function recommendPackage(int $selectedCount, int $bid, int $videoCount = 0): array
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

        // 按 min_num 升序排列
        usort($packages, function ($a, $b) {
            return $a['min_num'] - $b['min_num'];
        });

        // 阶梯匹配：找到 min_num ≤ selectedCount < max_num 的档位
        $matchedPackage = null;
        $unlimitedPackage = null; // max_num=0 的无上限档位备用

        foreach ($packages as $pkg) {
            $minNum = (int)$pkg['min_num'];
            $maxNum = (int)$pkg['max_num'];

            // 记录无上限档位
            if ($maxNum == 0) {
                $unlimitedPackage = $pkg;
            }

            // 检查是否在区间内: min_num ≤ selectedCount < max_num
            if ($selectedCount >= $minNum) {
                if ($maxNum == 0 || $selectedCount < $maxNum) {
                    $matchedPackage = $pkg;
                    break; // 找到匹配档位，跳出
                }
            }
        }

        // 如果没匹配到，尝试使用无上限档位
        if ($matchedPackage === null && $unlimitedPackage !== null && $selectedCount >= (int)$unlimitedPackage['min_num']) {
            $matchedPackage = $unlimitedPackage;
        }

        // 计算价格
        $finalPrice = $singleTotal; // 默认按单张价格
        if ($matchedPackage !== null) {
            if ((int)$matchedPackage['max_num'] === 0) {
                // 无上限档位：按单价 × 数量计费
                $tierUnitPrice = (float)$matchedPackage['unit_price'];
                $finalPrice = round($tierUnitPrice * $selectedCount, 2);
            } else {
                // 有上限档位：直接使用档位固定价格
                $finalPrice = (float)$matchedPackage['price'];
            }
            $matchedPackage['final_price'] = $finalPrice;
            $matchedPackage['save_amount'] = max(0, round($singleTotal - $finalPrice, 2));
            $matchedPackage['tier_name'] = $matchedPackage['name'];
            $matchedPackage['is_unlimited'] = (int)$matchedPackage['max_num'] === 0 ? 1 : 0;
        }

        // 生成升档引导文案
        $guideText = $this->generateTierGuideText($selectedCount, $matchedPackage, $packages, $singlePrice);

        return [
            'recommended' => $matchedPackage,
            'all_packages' => $packages,
            'single_price' => $singlePrice,
            'single_total' => $singleTotal,
            'guide_text' => $guideText,
        ];
    }

    /**
     * 生成升档引导文案
     * 
     * 策略：
     * - 当前选片数距下一档位 min_num 差 1~2 张 → 引导升档
     * - 差 3 张以上 → 不显示引导
     * - 已在最高档位 → 显示"当前已享最优套餐价"
     *
     * @param int $selectedCount 选片数
     * @param array|null $matched 当前匹配的档位
     * @param array $packages 所有档位（已按 min_num 升序）
     * @param float $singlePrice 单张价格
     * @return string
     */
    private function generateTierGuideText(int $selectedCount, ?array $matched, array $packages, float $singlePrice): string
    {
        if (empty($packages)) return '';

        // 找到下一个更高档位（min_num > selectedCount 的第一个档位）
        $nextTier = null;
        foreach ($packages as $pkg) {
            if ((int)$pkg['min_num'] > $selectedCount) {
                $nextTier = $pkg;
                break;
            }
        }

        // 如果当前已匹配到 max_num=0 的无上限档位，说明已在最高档
        if ($matched !== null && (int)$matched['max_num'] === 0) {
            return '当前已享最优套餐价';
        }

        if ($nextTier !== null) {
            $diff = (int)$nextTier['min_num'] - $selectedCount;
            if ($diff >= 1 && $diff <= 2) {
                $nextMinNum = (int)$nextTier['min_num'];
                $nextUnitPrice = $nextMinNum > 0 ? round((float)$nextTier['price'] / $nextMinNum, 2) : 0;
                return '再选' . $diff . '张，升级到' . $nextTier['name'] . '，享¥' . $nextUnitPrice . '/张';
            }
        }

        // 当前有匹配档位，显示节省金额
        if ($matched !== null) {
            $saveAmount = $matched['save_amount'] ?? 0;
            if ($saveAmount > 0) {
                return '比单张购买省¥' . $saveAmount;
            }
        }

        return '';
    }

    /**
     * 校验套餐档位区间合法性和重叠检测
     *
     * @param int $bid 商家ID
     * @param int $minNum 图片档位下限
     * @param int $maxNum 图片档位上限（0=不限）
     * @param int $minVideoNum 视频档位下限
     * @param int $maxVideoNum 视频档位上限（0=不限）
     * @param int $excludeId 排除的套餐ID（编辑时排除自身）
     * @return array ['valid' => bool, 'msg' => string, 'warnings' => array]
     */
    public function validateTierInterval(int $bid, int $minNum, int $maxNum, int $minVideoNum, int $maxVideoNum, int $excludeId = 0): array
    {
        $warnings = [];

        // 1. 区间合法性校验
        if ($minNum < 1) {
            return ['valid' => false, 'msg' => '图片档位下限必须≥1', 'warnings' => []];
        }
        if ($maxNum != 0 && $maxNum <= $minNum) {
            return ['valid' => false, 'msg' => '图片档位上限必须大于下限，或填0表示不限上限', 'warnings' => []];
        }

        // 2. 查询同商家所有其他启用档位
        $query = AiTravelPhotoPackage::where('bid', $bid)
            ->where('status', AiTravelPhotoPackage::STATUS_ENABLED);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        $existingTiers = $query->select();

        // 3. 区间重叠检测
        $newMin = $minNum;
        $newMax = $maxNum == 0 ? PHP_INT_MAX : $maxNum;

        foreach ($existingTiers as $tier) {
            $existMin = (int)$tier->min_num;
            $existMax = (int)$tier->max_num == 0 ? PHP_INT_MAX : (int)$tier->max_num;

            // 重叠条件: A.min < B.max 且 B.min < A.max
            if ($newMin < $existMax && $existMin < $newMax) {
                $existDisplay = $tier->tier_display;
                return [
                    'valid' => false,
                    'msg' => '图片档位区间与已有档位"' . $tier->name . '"(' . $existDisplay . ')存在重叠',
                    'warnings' => [],
                ];
            }
        }

        // 4. 无上限档位唯一性
        if ($maxNum == 0) {
            foreach ($existingTiers as $tier) {
                if ((int)$tier->max_num == 0) {
                    return [
                        'valid' => false,
                        'msg' => '已存在无上限档位"' . $tier->name . '"，同一商家最多只能有一个不限上限的档位',
                        'warnings' => [],
                    ];
                }
            }
        }

        // 5. 区间断裂检测（建议级警告，不阻止保存）
        $allTiers = $existingTiers->toArray();
        $allTiers[] = ['min_num' => $minNum, 'max_num' => $maxNum, 'name' => '(当前)'];
        usort($allTiers, function ($a, $b) {
            return ((int)($a['min_num'] ?? 0)) - ((int)($b['min_num'] ?? 0));
        });

        for ($i = 0; $i < count($allTiers) - 1; $i++) {
            $curMax = (int)($allTiers[$i]['max_num'] ?? 0);
            $nextMin = (int)($allTiers[$i + 1]['min_num'] ?? 0);
            if ($curMax != 0 && $curMax < $nextMin) {
                $warnings[] = '选片数' . $curMax . '~' . ($nextMin - 1) . '张未被任何档位覆盖';
            }
        }

        return ['valid' => true, 'msg' => '', 'warnings' => $warnings];
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
        $portraitIds = $data['portrait_ids'] ?? [];
        $resultIds = $data['result_ids'] ?? [];
        $packageId = (int)($data['package_id'] ?? 0);
        $openid = $data['openid'] ?? '';
        $aid = (int)($data['aid'] ?? 0);
        $bid = (int)($data['bid'] ?? 0);
        $qrcodeId = (int)($data['qrcode_id'] ?? 0);

        if ($portraitId <= 0 && empty($portraitIds)) {
            throw new \Exception('人像ID不能为空');
        }
        if (empty($resultIds)) {
            throw new \Exception('请至少选择一张成片');
        }

        // 构建allowedPortraitIds
        $allowedPortraitIds = [];
        if (!empty($portraitIds) && is_array($portraitIds)) {
            // 门店模式：直接使用前端传入的所有portrait_ids
            $allowedPortraitIds = array_map('intval', $portraitIds);
            if ($portraitId <= 0 && !empty($allowedPortraitIds)) {
                $portraitId = $allowedPortraitIds[0];
            }
        } else {
            // 原有模式：单portrait_id + 相似人像
            $allowedPortraitIds = [$portraitId];
            if ($bid > 0 || $aid > 0) {
                try {
                    $similarIds = $this->findSimilarPortraitIds($portraitId, $bid, $aid, 0.95);
                    $allowedPortraitIds = array_merge($allowedPortraitIds, $similarIds);
                } catch (\Exception $e) {
                    // 相似搜索失败不影响主流程
                }
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

            // 阶梯档位模式：验证选片数是否在档位区间内
            if ($package->matchesTier($selectedCount)) {
                if ((int)$package->max_num === 0) {
                    // 无上限档位：按单价 × 数量计费
                    $tierUnitPrice = (int)$package->min_num > 0
                        ? round((float)$package->price / (int)$package->min_num, 2)
                        : (float)$package->price;
                    $totalPrice = round($tierUnitPrice * $selectedCount, 2);
                } else {
                    // 有上限档位：直接使用档位固定价格
                    $totalPrice = (float)$package->price;
                }
                $actualAmount = $totalPrice;
                $downloadLimit = $selectedCount;
            } else {
                // 选片数不在档位区间内，按单张价格计算（降级处理）
                $totalPrice = round($singlePrice * $selectedCount, 2);
                $actualAmount = $totalPrice;
                $downloadLimit = $selectedCount;
            }

            $packageSnapshot = json_encode([
                'id' => $package->id,
                'name' => $package->name,
                'num' => $package->num,
                'min_num' => (int)$package->min_num,
                'max_num' => (int)$package->max_num,
                'tier_name' => $package->name,
                'price' => $package->price,
                'unit_price' => (int)$package->min_num > 0 ? round((float)$package->price / (int)$package->min_num, 2) : (float)$package->price,
                'is_unlimited' => (int)$package->max_num === 0 ? 1 : 0,
                'actual_amount' => $actualAmount,
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

            // 6. 分销提成计算（事务外执行，避免影响主流程）
            try {
                $this->calculateCommission($order);
            } catch (\Exception $e) {
                Log::error('选片订单分销计算异常：' . $e->getMessage() . ' 订单号：' . $orderNo);
            }

            return true;
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('选片订单履约异常：' . $e->getMessage() . ' 订单号：' . $orderNo);
            return false;
        }
    }

    /**
     * 分销提成计算
     * 
     * 支持四种模式：
     * 0=按会员等级 1=单独设置比例 2=单独设置金额 3=分销送积分 -1=不参与分销
     *
     * @param AiTravelPhotoOrder $order 订单对象
     * @return void
     */
    private function calculateCommission($order): void
    {
        $aid = (int)$order->aid;
        $uid = (int)$order->uid;
        $bid = (int)$order->bid;

        // 仅注册会员(uid>0)才参与分销链路
        if ($uid <= 0) {
            Log::info('选片订单分销跳过：非注册会员', ['order_no' => $order->order_no]);
            return;
        }

        // 查询套餐分销设置
        $packageId = (int)$order->package_id;
        if ($packageId <= 0) {
            return; // 单张购买无套餐，不参与分销
        }

        $package = Db::name('ai_travel_photo_package')->where('id', $packageId)->find();
        if (!$package) {
            return;
        }

        $commissionset = isset($package['commissionset']) ? intval($package['commissionset']) : -1;
        if ($commissionset == -1) {
            Log::info('选片订单分销跳过：套餐不参与分销', ['order_no' => $order->order_no, 'package_id' => $packageId]);
            return;
        }

        // 反序列化commissiondata
        $commissiondata1 = !empty($package['commissiondata1']) ? json_decode($package['commissiondata1'], true) : [];
        $commissiondata2 = !empty($package['commissiondata2']) ? json_decode($package['commissiondata2'], true) : [];
        $commissiondata3 = !empty($package['commissiondata3']) ? json_decode($package['commissiondata3'], true) : [];

        // 实付金额和数量
        $actualAmount = floatval($order->actual_amount);
        $quantity = 1; // 每笔订单固定1次

        // 查询下单会员的上级关系链
        $member = Db::name('member')->where('aid', $aid)->where('id', $uid)->find();
        if (!$member || !$member['pid']) {
            return; // 无上级关系，不参与分销
        }

        // 逐级查找有效分销上级（最多3级）
        $parentData = ['parent1' => 0, 'parent2' => 0, 'parent3' => 0];
        $commissionData = ['parent1commission' => 0, 'parent2commission' => 0, 'parent3commission' => 0];
        $isScore = false; // 是否为积分模式
        $scoreData = ['parent1score' => 0, 'parent2score' => 0, 'parent3score' => 0];

        // 一级上级
        $parent1 = null;
        $aglevel1 = null;
        if ($member['pid'] > 0) {
            $parent1 = Db::name('member')->where('aid', $aid)->where('id', $member['pid'])->find();
            if ($parent1) {
                $aglevel1 = Db::name('member_level')->where('id', $parent1['levelid'])->find();
                if ($aglevel1 && $aglevel1['can_agent'] >= 1) {
                    $parentData['parent1'] = (int)$parent1['id'];
                }
            }
        }

        // 二级上级
        $parent2 = null;
        $aglevel2 = null;
        if ($parent1 && $parent1['pid'] > 0) {
            $parent2 = Db::name('member')->where('aid', $aid)->where('id', $parent1['pid'])->find();
            if ($parent2) {
                $aglevel2 = Db::name('member_level')->where('id', $parent2['levelid'])->find();
                if ($aglevel2 && $aglevel2['can_agent'] >= 2) {
                    $parentData['parent2'] = (int)$parent2['id'];
                }
            }
        }

        // 三级上级
        $parent3 = null;
        $aglevel3 = null;
        if ($parent2 && $parent2['pid'] > 0) {
            $parent3 = Db::name('member')->where('aid', $aid)->where('id', $parent2['pid'])->find();
            if ($parent3) {
                $aglevel3 = Db::name('member_level')->where('id', $parent3['levelid'])->find();
                if ($aglevel3 && $aglevel3['can_agent'] >= 3) {
                    $parentData['parent3'] = (int)$parent3['id'];
                }
            }
        }

        // 按模式计算提成
        switch ($commissionset) {
            case 0: // 按会员等级
                if ($aglevel1 && $parentData['parent1']) {
                    if ($aglevel1['commissiontype'] == 1) {
                        $commissionData['parent1commission'] = round(floatval($aglevel1['commission1']) * $quantity, 2);
                    } else {
                        $commissionData['parent1commission'] = round(floatval($aglevel1['commission1']) * $actualAmount * $quantity / 100, 2);
                    }
                }
                if ($aglevel2 && $parentData['parent2']) {
                    if ($aglevel2['commissiontype'] == 1) {
                        $commissionData['parent2commission'] = round(floatval($aglevel2['commission2']) * $quantity, 2);
                    } else {
                        $commissionData['parent2commission'] = round(floatval($aglevel2['commission2']) * $actualAmount * $quantity / 100, 2);
                    }
                }
                if ($aglevel3 && $parentData['parent3']) {
                    if ($aglevel3['commissiontype'] == 1) {
                        $commissionData['parent3commission'] = round(floatval($aglevel3['commission3']) * $quantity, 2);
                    } else {
                        $commissionData['parent3commission'] = round(floatval($aglevel3['commission3']) * $actualAmount * $quantity / 100, 2);
                    }
                }
                break;

            case 1: // 单独设置提成比例
                if ($aglevel1 && $parentData['parent1'] && isset($commissiondata1[$aglevel1['id']])) {
                    $commissionData['parent1commission'] = round(floatval($commissiondata1[$aglevel1['id']]['commission1'] ?? 0) * $actualAmount * $quantity / 100, 2);
                }
                if ($aglevel2 && $parentData['parent2'] && isset($commissiondata1[$aglevel2['id']])) {
                    $commissionData['parent2commission'] = round(floatval($commissiondata1[$aglevel2['id']]['commission2'] ?? 0) * $actualAmount * $quantity / 100, 2);
                }
                if ($aglevel3 && $parentData['parent3'] && isset($commissiondata1[$aglevel3['id']])) {
                    $commissionData['parent3commission'] = round(floatval($commissiondata1[$aglevel3['id']]['commission3'] ?? 0) * $actualAmount * $quantity / 100, 2);
                }
                break;

            case 2: // 单独设置提成金额
                if ($aglevel1 && $parentData['parent1'] && isset($commissiondata2[$aglevel1['id']])) {
                    $commissionData['parent1commission'] = round(floatval($commissiondata2[$aglevel1['id']]['commission1'] ?? 0) * $quantity, 2);
                }
                if ($aglevel2 && $parentData['parent2'] && isset($commissiondata2[$aglevel2['id']])) {
                    $commissionData['parent2commission'] = round(floatval($commissiondata2[$aglevel2['id']]['commission2'] ?? 0) * $quantity, 2);
                }
                if ($aglevel3 && $parentData['parent3'] && isset($commissiondata2[$aglevel3['id']])) {
                    $commissionData['parent3commission'] = round(floatval($commissiondata2[$aglevel3['id']]['commission3'] ?? 0) * $quantity, 2);
                }
                break;

            case 3: // 分销送积分
                $isScore = true;
                if ($aglevel1 && $parentData['parent1'] && isset($commissiondata3[$aglevel1['id']])) {
                    $scoreData['parent1score'] = intval(floatval($commissiondata3[$aglevel1['id']]['commission1'] ?? 0) * $quantity);
                }
                if ($aglevel2 && $parentData['parent2'] && isset($commissiondata3[$aglevel2['id']])) {
                    $scoreData['parent2score'] = intval(floatval($commissiondata3[$aglevel2['id']]['commission2'] ?? 0) * $quantity);
                }
                if ($aglevel3 && $parentData['parent3'] && isset($commissiondata3[$aglevel3['id']])) {
                    $scoreData['parent3score'] = intval(floatval($commissiondata3[$aglevel3['id']]['commission3'] ?? 0) * $quantity);
                }
                break;
        }

        // 检查是否有任何提成需要发放
        $hasCommission = ($commissionData['parent1commission'] > 0 || $commissionData['parent2commission'] > 0 || $commissionData['parent3commission'] > 0);
        $hasScore = ($scoreData['parent1score'] > 0 || $scoreData['parent2score'] > 0 || $scoreData['parent3score'] > 0);

        if (!$hasCommission && !$hasScore) {
            return;
        }

        // 分销分红发放钱包控制
        $fxfh_send_wallet = 0;
        $fxfh_send_wallet_levelids = [];
        if (function_exists('getcustom') && getcustom('commission_send_wallet', $aid)) {
            $admin_set = Db::name('admin_set')->where('aid', $aid)->field('commission_send_wallet,commission_send_wallet_levelids')->find();
            $fxfh_send_wallet = $admin_set['commission_send_wallet'] ?? 0;
            $fxfh_send_wallet_levelids = !empty($admin_set['commission_send_wallet_levelids']) ? explode(',', $admin_set['commission_send_wallet_levelids']) : ['-1'];
        }

        // 发放提成
        $levels = [1, 2, 3];
        foreach ($levels as $n) {
            $parentKey = 'parent' . $n;
            $parentMid = $parentData[$parentKey];
            if ($parentMid <= 0) continue;

            if ($isScore) {
                // 模式3：发放积分
                $scoreKey = 'parent' . $n . 'score';
                $scoreVal = $scoreData[$scoreKey] ?? 0;
                if ($scoreVal > 0) {
                    \app\common\Member::addscore($aid, $parentMid, $scoreVal, '下级选片订单积分奖励');
                    Log::info('选片分销积分发放', ['order_no' => $order->order_no, 'parent' . $n => $parentMid, 'score' => $scoreVal]);
                }
            } else {
                // 模式0/1/2：发放佣金
                $commKey = 'parent' . $n . 'commission';
                $commVal = $commissionData[$commKey] ?? 0;
                if ($commVal > 0) {
                    $remark = '下级选片订单佣金奖励';

                    // 检查是否发放到余额钱包
                    $fxfh_send_money = 0;
                    if ($fxfh_send_wallet == 1) {
                        $member_levelid = Db::name('member')->where('id', $parentMid)->value('levelid');
                        if (empty($fxfh_send_wallet_levelids) || in_array('-1', $fxfh_send_wallet_levelids) || in_array($member_levelid, $fxfh_send_wallet_levelids)) {
                            $fxfh_send_money = 1;
                        }
                    }

                    if ($fxfh_send_money == 1) {
                        \app\common\Member::addmoney($aid, $parentMid, $commVal, $remark);
                    } else {
                        \app\common\Member::addcommission($aid, $parentMid, $uid, $commVal, $remark, 1, 'fenxiao');
                    }

                    // 写入佣金记录
                    Db::name('member_commission_record')->insert([
                        'aid' => $aid,
                        'mid' => $parentMid,
                        'frommid' => $uid,
                        'orderid' => $order->id,
                        'type' => 'ai_pick',
                        'commission' => $commVal,
                        'remark' => $remark,
                        'createtime' => time(),
                        'status' => 1,
                        'endtime' => time(),
                    ]);

                    Log::info('选片分销佣金发放', ['order_no' => $order->order_no, 'parent' . $n => $parentMid, 'commission' => $commVal]);
                }
            }
        }

        // 回写订单分销字段
        $updateData = [
            'parent1' => $parentData['parent1'],
            'parent2' => $parentData['parent2'],
            'parent3' => $parentData['parent3'],
            'parent1commission' => $commissionData['parent1commission'],
            'parent2commission' => $commissionData['parent2commission'],
            'parent3commission' => $commissionData['parent3commission'],
            'iscommission' => 1,
        ];

        Db::name('ai_travel_photo_order')->where('id', $order->id)->update($updateData);

        Log::info('选片订单分销完成', [
            'order_no' => $order->order_no,
            'commissionset' => $commissionset,
            'parent1' => $parentData['parent1'],
            'parent1commission' => $commissionData['parent1commission'],
            'parent2' => $parentData['parent2'],
            'parent2commission' => $commissionData['parent2commission'],
            'parent3' => $parentData['parent3'],
            'parent3commission' => $commissionData['parent3commission'],
        ]);
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
