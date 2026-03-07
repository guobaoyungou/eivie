<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoScene;
use think\exception\ValidateException;
use think\facade\Cache;

/**
 * AI旅拍-场景管理服务
 * Class AiTravelPhotoSceneService
 * @package app\service
 */
class AiTravelPhotoSceneService
{
    /**
     * 获取场景列表
     * @param array $params 查询参数
     * @return array
     */
    public function getSceneList(array $params): array
    {
        // 尝试从缓存获取
        if (empty($params['page']) && empty($params['keyword'])) {
            $cacheKey = 'scene_list:' . md5(json_encode($params));
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                return $cachedData;
            }
        }
        
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 20;
        
        $query = AiTravelPhotoScene::withSearch(['bid', 'category', 'status', 'is_public', 'is_recommend', 'name'], $params);
        
        // 只查询启用的场景（前端用户）
        if (!empty($params['only_enabled'])) {
            $query->where('status', AiTravelPhotoScene::STATUS_ENABLED);
        }
        
        // C端用户：仅返回公共场景
        if (!empty($params['is_client'])) {
            $query->where('is_public', 1);
            $query->where('status', 1);
        }
        
        // 门店筛选：C端用户传入mdid时，返回通用公共场景 + 该门店的公共场景
        if (!empty($params['mdid'])) {
            $mdid = intval($params['mdid']);
            if ($mdid > 0 && !empty($params['is_client'])) {
                // C端：返回 mdid=0 或 mdid=指定门店 的公共场景
                $query->where(function($q) use ($mdid) {
                    $q->where('mdid', 0)->whereOr('mdid', $mdid);
                });
            } else {
                // 后台：精确筛选
                $query->where('mdid', $mdid);
            }
        } else if (!empty($params['is_client'])) {
            // C端且未传mdid：仅返回通用公共场景
            $query->where('mdid', 0);
        }
        
        // 排序
        $query->order('sort', 'desc')->order('id', 'desc');
        
        if ($page) {
            $list = $query->paginate([
                'list_rows' => $pageSize,
                'page' => $page,
            ]);
            
            $result = [
                'list' => $list->items(),
                'total' => $list->total(),
                'page' => $list->currentPage(),
                'page_size' => $pageSize,
            ];
        } else {
            $result = [
                'list' => $query->select()->toArray(),
            ];
        }
        
        // 缓存场景列表（1小时）
        if (empty($params['page']) && empty($params['keyword'])) {
            Cache::set($cacheKey, $result, 3600);
        }
        
        return $result;
    }
    
    /**
     * 获取场景详情
     * @param int $sceneId 场景ID
     * @param bool $isClient 是否C端用户（需要校验is_public）
     * @return array
     */
    public function getSceneDetail(int $sceneId, bool $isClient = false): array
    {
        $scene = AiTravelPhotoScene::with(['aiModel'])->find($sceneId);
        
        if (!$scene) {
            throw new ValidateException('场景不存在');
        }
        
        // C端用户需要校验权限
        if ($isClient) {
            if ($scene->is_public != 1 || $scene->status != 1) {
                throw new ValidateException('场景不可用或无权限访问');
            }
        }
        
        return $scene->toArray();
    }
    
    /**
     * 保存场景（新增或编辑）
     * @param array $data 场景数据
     * @return array
     */
    public function saveScene(array $data): array
    {
        $sceneId = $data['id'] ?? 0;
        
        if ($sceneId > 0) {
            // 编辑
            $scene = AiTravelPhotoScene::find($sceneId);
            if (!$scene) {
                throw new ValidateException('场景不存在');
            }
        } else {
            // 新增
            $scene = new AiTravelPhotoScene();
            $scene->aid = $data['aid'];
            $scene->create_time = time();
        }
        
        // 更新字段
        $scene->bid = $data['bid'] ?? 0;
        $scene->mdid = $data['mdid'] ?? 0;
        $scene->name = $data['name'];
        $scene->name_en = $data['name_en'] ?? '';
        $scene->province = $data['province'] ?? '';
        $scene->city = $data['city'] ?? '';
        $scene->district = $data['district'] ?? '';
        $scene->category = $data['category'] ?? '';
        $scene->desc = $data['desc'] ?? '';
        $scene->cover = $data['cover'] ?? '';
        $scene->background_url = $data['background_url'] ?? '';
        $scene->prompt = $data['prompt'] ?? '';
        $scene->prompt_en = $data['prompt_en'] ?? '';
        $scene->negative_prompt = $data['negative_prompt'] ?? '';
        $scene->video_prompt = $data['video_prompt'] ?? '';
        $scene->model_id = $data['model_id'] ?? 0;
        $scene->model_params = $data['model_params'] ?? [];
        $scene->aspect_ratio = $data['aspect_ratio'] ?? '1:1';
        $scene->sort = $data['sort'] ?? 0;
        $scene->status = $data['status'] ?? AiTravelPhotoScene::STATUS_ENABLED;
        $scene->is_public = $data['is_public'] ?? 0;
        $scene->is_recommend = $data['is_recommend'] ?? 0;
        $scene->tags = $data['tags'] ?? '';
        $scene->update_time = time();
        
        $scene->save();
        
        // 清除缓存
        $this->clearSceneCache();
        
        return [
            'scene_id' => $scene->id,
            'status' => $sceneId > 0 ? 'updated' : 'created',
        ];
    }
    
    /**
     * 删除场景
     * @param int $sceneId 场景ID
     * @return bool
     */
    public function deleteScene(int $sceneId): bool
    {
        $scene = AiTravelPhotoScene::find($sceneId);
        
        if (!$scene) {
            throw new ValidateException('场景不存在');
        }
        
        // 检查是否有生成记录
        $generationCount = \app\model\AiTravelPhotoGeneration::where('scene_id', $sceneId)->count();
        if ($generationCount > 0) {
            throw new ValidateException('该场景已有生成记录，无法删除');
        }
        
        // 删除场景
        $result = $scene->delete();
        
        // 清除缓存
        $this->clearSceneCache();
        
        return $result;
    }
    
    /**
     * 更新场景状态
     * @param int $sceneId 场景ID
     * @param int $status 状态
     * @return bool
     */
    public function updateSceneStatus(int $sceneId, int $status): bool
    {
        $scene = AiTravelPhotoScene::find($sceneId);
        
        if (!$scene) {
            throw new ValidateException('场景不存在');
        }
        
        $scene->status = $status;
        $scene->save();
        
        // 清除缓存
        $this->clearSceneCache();
        
        return true;
    }
    
    /**
     * 更新场景统计
     * @param int $sceneId 场景ID
     * @param string $type 类型：use/success/fail
     * @param int $costTime 耗时（秒）
     * @return void
     */
    public function updateSceneStats(int $sceneId, string $type, int $costTime = 0): void
    {
        $scene = AiTravelPhotoScene::find($sceneId);
        if (!$scene) {
            return;
        }
        
        switch ($type) {
            case 'use':
                $scene->use_count += 1;
                break;
            case 'success':
                $scene->success_count += 1;
                break;
            case 'fail':
                $scene->fail_count += 1;
                break;
        }
        
        // 更新平均耗时
        if ($costTime > 0 && $type == 'success') {
            if ($scene->avg_time == 0) {
                $scene->avg_time = $costTime;
            } else {
                $scene->avg_time = intval(($scene->avg_time + $costTime) / 2);
            }
        }
        
        $scene->save();
    }
    
    /**
     * 获取场景分类列表
     * @return array
     */
    public function getCategoryList(): array
    {
        return AiTravelPhotoScene::getCategoryList();
    }
    
    /**
     * 批量更新场景排序
     * @param array $sortData 排序数据 [['id' => 1, 'sort' => 10], ...]
     * @return bool
     */
    public function batchUpdateSort(array $sortData): bool
    {
        foreach ($sortData as $item) {
            AiTravelPhotoScene::where('id', $item['id'])->update(['sort' => $item['sort']]);
        }
        
        // 清除缓存
        $this->clearSceneCache();
        
        return true;
    }
    
    /**
     * 复制场景
     * @param int $sceneId 场景ID
     * @param array $overrideData 覆盖数据
     * @return array
     */
    public function copyScene(int $sceneId, array $overrideData = []): array
    {
        $sourceScene = AiTravelPhotoScene::find($sceneId);
        
        if (!$sourceScene) {
            throw new ValidateException('场景不存在');
        }
        
        // 复制场景
        $newScene = $sourceScene->toArray();
        unset($newScene['id']);
        unset($newScene['create_time']);
        unset($newScene['update_time']);
        
        // 重置统计数据
        $newScene['use_count'] = 0;
        $newScene['success_count'] = 0;
        $newScene['fail_count'] = 0;
        $newScene['avg_time'] = 0;
        
        // 覆盖数据
        $newScene = array_merge($newScene, $overrideData);
        $newScene['name'] = $newScene['name'] . ' - 副本';
        
        $scene = AiTravelPhotoScene::create($newScene);
        
        return [
            'scene_id' => $scene->id,
            'status' => 'copied',
        ];
    }
    
    /**
     * 清除场景缓存
     * @return void
     */
    private function clearSceneCache(): void
    {
        Cache::tag('scene_list')->clear();
    }
}
