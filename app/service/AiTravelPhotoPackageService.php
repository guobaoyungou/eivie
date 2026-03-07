<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoPackage;
use app\model\AiTravelPhotoScene;
use think\facade\Cache;
use think\facade\Db;

/**
 * 套餐管理服务类
 * 管理商家创建的照片套餐，包括库存、销量、推荐等功能
 */
class AiTravelPhotoPackageService
{
    /**
     * 获取套餐列表
     * 
     * @param array $params 查询参数
     * @return array
     */
    public function getPackageList(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        
        $query = AiTravelPhotoPackage::where('status', '>', AiTravelPhotoPackage::STATUS_DELETED);
        
        // 商家筛选
        if (isset($params['bid'])) {
            $query->where('bid', $params['bid']);
        }
        
        // 状态筛选
        if (isset($params['status'])) {
            $query->where('status', $params['status']);
        }
        
        // 推荐筛选
        if (isset($params['is_recommend']) && $params['is_recommend'] == 1) {
            $query->where('is_recommend', 1);
        }
        
        // 关键词搜索
        if (!empty($params['keyword'])) {
            $query->where('package_name', 'like', '%' . $params['keyword'] . '%');
        }
        
        // 价格区间
        if (isset($params['min_price'])) {
            $query->where('price', '>=', $params['min_price']);
        }
        if (isset($params['max_price'])) {
            $query->where('price', '<=', $params['max_price']);
        }
        
        // 库存筛选（低库存预警）
        if (isset($params['low_stock']) && $params['low_stock'] == 1) {
            $query->where('stock', '<=', Db::raw('`low_stock_threshold`'));
        }
        
        $list = $query->with(['business'])
            ->order('sort', 'asc')
            ->order('add_time', 'desc')
            ->page($page, $pageSize)
            ->select();
        
        $total = $query->count();
        
        // 格式化数据
        $formattedList = [];
        foreach ($list as $item) {
            $formattedList[] = $this->formatPackageData($item);
        }
        
        return [
            'list' => $formattedList,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize),
        ];
    }

    /**
     * 获取套餐详情
     * 
     * @param int $packageId 套餐ID
     * @param bool $useCache 是否使用缓存
     * @return array
     * @throws \Exception
     */
    public function getPackageDetail(int $packageId, bool $useCache = true): array
    {
        $cacheKey = 'package_detail:' . $packageId;
        
        if ($useCache) {
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                return $cachedData;
            }
        }
        
        $package = AiTravelPhotoPackage::where('id', $packageId)
            ->where('status', '>', AiTravelPhotoPackage::STATUS_DELETED)
            ->with(['business', 'scenes'])
            ->find();
        
        if (!$package) {
            throw new \Exception('套餐不存在');
        }
        
        $data = $this->formatPackageData($package, true);
        
        // 缓存30分钟
        if ($useCache) {
            Cache::set($cacheKey, $data, 1800);
        }
        
        return $data;
    }

    /**
     * 创建套餐
     * 
     * @param array $data 套餐数据
     * @return array
     * @throws \Exception
     */
    public function createPackage(array $data): array
    {
        // 数据验证
        $this->validatePackageData($data);
        
        Db::startTrans();
        try {
            // 创建套餐
            $package = AiTravelPhotoPackage::create([
                'aid' => $data['aid'] ?? 0,
                'bid' => $data['bid'],
                'package_name' => $data['package_name'],
                'package_desc' => $data['package_desc'] ?? '',
                'package_cover' => $data['package_cover'] ?? '',
                'price' => $data['price'],
                'original_price' => $data['original_price'] ?? $data['price'],
                'photo_count' => $data['photo_count'],
                'valid_days' => $data['valid_days'] ?? 365,
                'stock' => $data['stock'] ?? 9999,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 10,
                'scene_ids' => !empty($data['scene_ids']) ? json_encode($data['scene_ids']) : '[]',
                'sort' => $data['sort'] ?? 100,
                'is_recommend' => $data['is_recommend'] ?? 0,
                'status' => $data['status'] ?? AiTravelPhotoPackage::STATUS_NORMAL,
                'add_time' => time(),
            ]);
            
            Db::commit();
            
            // 清除相关缓存
            $this->clearPackageCache($data['bid']);
            
            return $this->formatPackageData($package);
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 更新套餐
     * 
     * @param int $packageId 套餐ID
     * @param array $data 更新数据
     * @return bool
     * @throws \Exception
     */
    public function updatePackage(int $packageId, array $data): bool
    {
        $package = AiTravelPhotoPackage::find($packageId);
        
        if (!$package) {
            throw new \Exception('套餐不存在');
        }
        
        // 权限验证
        if (isset($data['bid']) && $package->bid != $data['bid']) {
            throw new \Exception('无权限修改此套餐');
        }
        
        Db::startTrans();
        try {
            // 允许更新的字段
            $allowFields = [
                'package_name', 'package_desc', 'package_cover',
                'price', 'original_price', 'photo_count', 'valid_days',
                'stock', 'low_stock_threshold', 'scene_ids',
                'sort', 'is_recommend', 'status'
            ];
            
            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    if ($field == 'scene_ids' && is_array($data[$field])) {
                        $package->$field = json_encode($data[$field]);
                    } else {
                        $package->$field = $data[$field];
                    }
                }
            }
            
            $result = $package->save();
            
            Db::commit();
            
            // 清除缓存
            $this->clearPackageCache($package->bid);
            Cache::delete('package_detail:' . $packageId);
            
            return $result;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 删除套餐（软删除）
     * 
     * @param int $packageId 套餐ID
     * @param int $bid 商家ID（权限验证）
     * @return bool
     * @throws \Exception
     */
    public function deletePackage(int $packageId, int $bid): bool
    {
        $package = AiTravelPhotoPackage::where('id', $packageId)
            ->where('bid', $bid)
            ->find();
        
        if (!$package) {
            throw new \Exception('套餐不存在或无权限删除');
        }
        
        // 检查是否有未完成的订单
        $hasActiveOrder = Db::name('ai_travel_photo_order_goods')
            ->where('package_id', $packageId)
            ->where('goods_type', 2) // 套餐类型
            ->whereIn('order_id', function($query) {
                $query->name('ai_travel_photo_order')
                    ->where('status', 'in', [1, 2]) // 待支付、已支付
                    ->field('id');
            })
            ->count();
        
        if ($hasActiveOrder > 0) {
            throw new \Exception('该套餐有未完成的订单，无法删除');
        }
        
        $package->status = AiTravelPhotoPackage::STATUS_DELETED;
        $result = $package->save();
        
        // 清除缓存
        $this->clearPackageCache($bid);
        Cache::delete('package_detail:' . $packageId);
        
        return $result;
    }

    /**
     * 扣减库存
     * 
     * @param int $packageId 套餐ID
     * @param int $quantity 数量
     * @return bool
     * @throws \Exception
     */
    public function decreaseStock(int $packageId, int $quantity = 1): bool
    {
        $package = AiTravelPhotoPackage::find($packageId);
        
        if (!$package) {
            throw new \Exception('套餐不存在');
        }
        
        if ($package->stock < $quantity) {
            throw new \Exception('套餐库存不足');
        }
        
        // 使用原生SQL保证原子性
        $affectedRows = Db::name('ai_travel_photo_package')
            ->where('id', $packageId)
            ->where('stock', '>=', $quantity)
            ->dec('stock', $quantity)
            ->update();
        
        if ($affectedRows === 0) {
            throw new \Exception('扣减库存失败，库存不足');
        }
        
        // 检查库存预警
        $package->refresh();
        if ($package->stock <= $package->low_stock_threshold) {
            $this->triggerLowStockAlert($package);
        }
        
        // 清除缓存
        Cache::delete('package_detail:' . $packageId);
        
        return true;
    }

    /**
     * 增加库存（退款等场景）
     * 
     * @param int $packageId 套餐ID
     * @param int $quantity 数量
     * @return bool
     * @throws \Exception
     */
    public function increaseStock(int $packageId, int $quantity = 1): bool
    {
        $package = AiTravelPhotoPackage::find($packageId);
        
        if (!$package) {
            throw new \Exception('套餐不存在');
        }
        
        Db::name('ai_travel_photo_package')
            ->where('id', $packageId)
            ->inc('stock', $quantity)
            ->update();
        
        // 清除缓存
        Cache::delete('package_detail:' . $packageId);
        
        return true;
    }

    /**
     * 更新销量统计
     * 
     * @param int $packageId 套餐ID
     * @param int $quantity 数量
     * @return void
     */
    public function updateSalesCount(int $packageId, int $quantity = 1): void
    {
        Db::name('ai_travel_photo_package')
            ->where('id', $packageId)
            ->inc('sales_count', $quantity)
            ->update();
        
        // 清除缓存
        Cache::delete('package_detail:' . $packageId);
    }

    /**
     * 获取推荐套餐列表
     * 
     * @param int $bid 商家ID
     * @param int $limit 数量限制
     * @return array
     */
    public function getRecommendPackages(int $bid, int $limit = 6): array
    {
        $cacheKey = 'recommend_packages:' . $bid;
        
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return $cachedData;
        }
        
        $list = AiTravelPhotoPackage::where('bid', $bid)
            ->where('status', AiTravelPhotoPackage::STATUS_NORMAL)
            ->where('is_recommend', 1)
            ->where('stock', '>', 0)
            ->order('sort', 'asc')
            ->order('sales_count', 'desc')
            ->limit($limit)
            ->select();
        
        $formattedList = [];
        foreach ($list as $item) {
            $formattedList[] = $this->formatPackageData($item);
        }
        
        // 缓存1小时
        Cache::set($cacheKey, $formattedList, 3600);
        
        return $formattedList;
    }

    /**
     * 获取热销套餐排行
     * 
     * @param int $bid 商家ID
     * @param int $limit 数量限制
     * @return array
     */
    public function getHotPackages(int $bid, int $limit = 10): array
    {
        $list = AiTravelPhotoPackage::where('bid', $bid)
            ->where('status', AiTravelPhotoPackage::STATUS_NORMAL)
            ->where('sales_count', '>', 0)
            ->order('sales_count', 'desc')
            ->limit($limit)
            ->select();
        
        $formattedList = [];
        foreach ($list as $item) {
            $formattedList[] = [
                'package_id' => $item->id,
                'package_name' => $item->package_name,
                'package_cover' => $item->package_cover,
                'price' => $item->price,
                'sales_count' => $item->sales_count,
            ];
        }
        
        return $formattedList;
    }

    /**
     * 复制套餐
     * 
     * @param int $packageId 原套餐ID
     * @param int $bid 商家ID
     * @return array
     * @throws \Exception
     */
    public function copyPackage(int $packageId, int $bid): array
    {
        $originalPackage = AiTravelPhotoPackage::where('id', $packageId)
            ->where('bid', $bid)
            ->find();
        
        if (!$originalPackage) {
            throw new \Exception('原套餐不存在或无权限复制');
        }
        
        $newPackageData = [
            'aid' => $originalPackage->aid,
            'bid' => $originalPackage->bid,
            'package_name' => $originalPackage->package_name . '（副本）',
            'package_desc' => $originalPackage->package_desc,
            'package_cover' => $originalPackage->package_cover,
            'price' => $originalPackage->price,
            'original_price' => $originalPackage->original_price,
            'photo_count' => $originalPackage->photo_count,
            'valid_days' => $originalPackage->valid_days,
            'stock' => $originalPackage->stock,
            'low_stock_threshold' => $originalPackage->low_stock_threshold,
            'scene_ids' => json_decode($originalPackage->scene_ids, true) ?: [],
            'sort' => $originalPackage->sort,
            'status' => AiTravelPhotoPackage::STATUS_DISABLED, // 默认禁用
        ];
        
        return $this->createPackage($newPackageData);
    }

    /**
     * 批量更新状态
     * 
     * @param array $packageIds 套餐ID数组
     * @param int $status 状态
     * @param int $bid 商家ID
     * @return int
     */
    public function batchUpdateStatus(array $packageIds, int $status, int $bid): int
    {
        $affectedRows = AiTravelPhotoPackage::where('bid', $bid)
            ->whereIn('id', $packageIds)
            ->update(['status' => $status]);
        
        // 清除缓存
        $this->clearPackageCache($bid);
        
        return $affectedRows;
    }

    /**
     * 格式化套餐数据
     * 
     * @param AiTravelPhotoPackage $package 套餐对象
     * @param bool $includeScenes 是否包含场景详情
     * @return array
     */
    private function formatPackageData($package, bool $includeScenes = false): array
    {
        $data = [
            'package_id' => $package->id,
            'bid' => $package->bid,
            'business_name' => $package->business->business_name ?? '',
            'package_name' => $package->package_name,
            'package_desc' => $package->package_desc,
            'package_cover' => $package->package_cover,
            'price' => $package->price,
            'original_price' => $package->original_price,
            'discount' => $package->original_price > 0 ? round($package->price / $package->original_price * 10, 1) : 10,
            'photo_count' => $package->photo_count,
            'valid_days' => $package->valid_days,
            'stock' => $package->stock,
            'is_low_stock' => $package->stock <= $package->low_stock_threshold,
            'sales_count' => $package->sales_count,
            'sort' => $package->sort,
            'is_recommend' => $package->is_recommend,
            'status' => $package->status,
            'status_text' => $this->getStatusText($package->status),
            'add_time' => $package->add_time,
            'add_time_text' => date('Y-m-d H:i:s', $package->add_time),
        ];
        
        // 包含场景详情
        if ($includeScenes) {
            $sceneIds = json_decode($package->scene_ids, true) ?: [];
            $scenes = [];
            
            if (!empty($sceneIds)) {
                $sceneList = AiTravelPhotoScene::whereIn('id', $sceneIds)
                    ->where('status', AiTravelPhotoScene::STATUS_NORMAL)
                    ->select();
                
                foreach ($sceneList as $scene) {
                    $scenes[] = [
                        'scene_id' => $scene->id,
                        'scene_name' => $scene->scene_name,
                        'scene_cover' => $scene->scene_cover,
                        'category_name' => $scene->category_name,
                    ];
                }
            }
            
            $data['scenes'] = $scenes;
            $data['scene_count'] = count($scenes);
        }
        
        return $data;
    }

    /**
     * 验证套餐数据
     * 
     * @param array $data 套餐数据
     * @throws \Exception
     */
    private function validatePackageData(array $data): void
    {
        if (empty($data['package_name'])) {
            throw new \Exception('套餐名称不能为空');
        }
        
        if (!isset($data['price']) || $data['price'] <= 0) {
            throw new \Exception('套餐价格必须大于0');
        }
        
        if (!isset($data['photo_count']) || $data['photo_count'] <= 0) {
            throw new \Exception('照片数量必须大于0');
        }
        
        if (!isset($data['bid']) || $data['bid'] <= 0) {
            throw new \Exception('商家ID不能为空');
        }
    }

    /**
     * 清除套餐缓存
     * 
     * @param int $bid 商家ID
     * @return void
     */
    private function clearPackageCache(int $bid): void
    {
        Cache::delete('recommend_packages:' . $bid);
        // 可以添加更多缓存清除逻辑
    }

    /**
     * 触发低库存预警
     * 
     * @param AiTravelPhotoPackage $package 套餐对象
     * @return void
     */
    private function triggerLowStockAlert($package): void
    {
        // 这里可以发送通知、记录日志等
        // 例如：发送短信、站内消息、邮件提醒商家补充库存
        
        // 记录日志
        trace('套餐库存预警：' . $package->package_name . '，当前库存：' . $package->stock, 'notice');
    }

    /**
     * 获取状态文本
     * 
     * @param int $status 状态值
     * @return string
     */
    private function getStatusText(int $status): string
    {
        $statusMap = [
            AiTravelPhotoPackage::STATUS_NORMAL => '正常',
            AiTravelPhotoPackage::STATUS_DISABLED => '已禁用',
            AiTravelPhotoPackage::STATUS_DELETED => '已删除',
        ];
        
        return $statusMap[$status] ?? '未知';
    }
}
