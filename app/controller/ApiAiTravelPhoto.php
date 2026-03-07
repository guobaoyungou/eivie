<?php
/**
 * AI旅拍系统 - C端API接口
 * 
 * @package app\controller
 * @author AI Assistant
 * @date 2026-02-04
 */

namespace app\controller;

use think\facade\Db;
use app\model\AiTravelPhotoScene;
use app\model\AiTravelPhotoGeneration;
use app\model\AiTravelPhotoResult;
use app\model\AiTravelPhotoPortrait;

class ApiAiTravelPhoto extends ApiCommon
{
    /**
     * 获取公开场景列表
     * 
     * @return \think\response\Json
     */
    public function scenes()
    {
        try {
            // 获取请求参数
            $mdid = input('mdid/d', 0);
            $sceneType = input('scene_type', '');
            $category = input('category', '');
            $page = input('page/d', 1);
            $limit = input('limit/d', 20);
            
            // 构建查询条件
            $where = [
                ['is_public', '=', 1],
                ['status', '=', 1]
            ];
            
            // 门店筛选：返回通用场景(mdid=0) + 指定门店场景
            if ($mdid > 0) {
                $where[] = ['mdid', 'in', [0, $mdid]];
            } else {
                $where[] = ['mdid', '=', 0];
            }
            
            // 场景类型筛选
            if ($sceneType !== '') {
                $where[] = ['scene_type', '=', $sceneType];
            }
            
            // 分类筛选
            if ($category) {
                $where[] = ['category', '=', $category];
            }
            
            // 查询场景列表
            $list = AiTravelPhotoScene::where($where)
                ->field('id, scene_type, name, category, cover, desc, tags, sort')
                ->order('sort DESC, id DESC')
                ->page($page, $limit)
                ->select()
                ->each(function($item) {
                    // 添加场景类型文本
                    $sceneTypes = config('ai_travel_photo.scene_type');
                    $item['scene_type_text'] = $sceneTypes[$item['scene_type']] ?? '未知类型';
                    return $item;
                });
            
            $total = AiTravelPhotoScene::where($where)->count();
            
            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
            
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * 获取场景详情
     * 
     * @return \think\response\Json
     */
    public function sceneDetail()
    {
        try {
            $sceneId = input('scene_id/d', 0);
            
            if ($sceneId <= 0) {
                return $this->error('参数错误');
            }
            
            // 查询场景信息（必须是公开且启用的）
            $scene = AiTravelPhotoScene::where('id', $sceneId)
                ->where('is_public', 1)
                ->where('status', 1)
                ->find();
            
            if (!$scene) {
                return $this->error('场景不存在或无权限访问');
            }
            
            // 获取场景类型文本
            $sceneTypes = config('ai_travel_photo.scene_type');
            $scene['scene_type_text'] = $sceneTypes[$scene['scene_type']] ?? '未知类型';
            
            // 获取场景类型对应的输入要求
            $sceneTypeInput = config('ai_travel_photo.scene_type_input');
            $scene['input_requirements'] = $sceneTypeInput[$scene['scene_type']] ?? [];
            
            // 解析模型参数
            $scene['model_params'] = $scene['model_params'] ? json_decode($scene['model_params'], true) : [];
            
            // 只返回必要字段
            $data = [
                'id' => $scene['id'],
                'scene_type' => $scene['scene_type'],
                'scene_type_text' => $scene['scene_type_text'],
                'name' => $scene['name'],
                'category' => $scene['category'],
                'cover' => $scene['cover'],
                'desc' => $scene['desc'],
                'tags' => $scene['tags'],
                'model_params' => $scene['model_params'],
                'input_requirements' => $scene['input_requirements']
            ];
            
            return $this->success($data);
            
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * 提交生成任务
     * 
     * @return \think\response\Json
     */
    public function generate()
    {
        try {
            // 获取请求参数
            $sceneId = input('scene_id/d', 0);
            $portraitId = input('portrait_id/d', 0);
            $bid = input('bid/d', 0);
            $mdid = input('mdid/d', 0);
            $uid = $this->uid ?? 0;
            
            // 参数验证
            if ($sceneId <= 0 || $portraitId <= 0) {
                return $this->error('参数错误');
            }
            
            // 查询场景信息
            $scene = AiTravelPhotoScene::where('id', $sceneId)
                ->where('is_public', 1)
                ->where('status', 1)
                ->find();
            
            if (!$scene) {
                return $this->error('场景不存在或不可用');
            }
            
            // 查询人像素材
            $portrait = AiTravelPhotoPortrait::where('id', $portraitId)
                ->where('status', 1)
                ->find();
            
            if (!$portrait) {
                return $this->error('人像素材不存在');
            }
            
            // 验证场景类型的输入要求
            $sceneTypeInput = config('ai_travel_photo.scene_type_input');
            $requiredInputs = $sceneTypeInput[$scene['scene_type']] ?? [];
            
            // TODO: 根据场景类型验证必需的输入参数
            // 例如：scene_type=4（首尾帧）需要tail_image_url
            
            // 解析模型参数
            $modelParams = $scene['model_params'] ? json_decode($scene['model_params'], true) : [];
            
            // 创建生成记录
            $generationData = [
                'aid' => $scene['aid'],
                'portrait_id' => $portraitId,
                'scene_id' => $sceneId,
                'uid' => $uid,
                'bid' => $bid,
                'mdid' => $mdid,
                'type' => 2, // 用户手动
                'generation_type' => $scene['scene_type'] <= 2 ? 1 : 3, // 1图生图 3图生视频
                'scene_type' => $scene['scene_type'], // 记录场景类型
                'model_type' => 'aliyun_tongyi', // TODO: 从API配置读取
                'model_name' => '', // TODO: 从模型实例读取
                'model_params' => json_encode($modelParams, JSON_UNESCAPED_UNICODE),
                'status' => 0, // 待处理
                'create_time' => time(),
                'update_time' => time()
            ];
            
            $generationId = AiTravelPhotoGeneration::insertGetId($generationData);
            
            // 加入异步队列
            $queueName = $scene['scene_type'] >= 3 && $scene['scene_type'] <= 6 
                ? 'ai_video_generation'  // 视频生成队列
                : 'ai_image_generation'; // 图生图队列
            
            $jobClass = $scene['scene_type'] >= 3 && $scene['scene_type'] <= 6
                ? 'app\\job\\VideoGenerationJob'
                : 'app\\job\\ImageGenerationJob';
            
            \think\facade\Queue::push($jobClass, [
                'generation_id' => $generationId
            ], $queueName);
            
            return $this->success([
                'generation_id' => $generationId,
                'task_status' => 0,
                'message' => '任务已提交，正在处理中...'
            ]);
            
        } catch (\Exception $e) {
            return $this->error('提交失败：' . $e->getMessage());
        }
    }
    
    /**
     * 查询生成结果
     * 
     * @return \think\response\Json
     */
    public function generationResult()
    {
        try {
            $generationId = input('generation_id/d', 0);
            
            if ($generationId <= 0) {
                return $this->error('参数错误');
            }
            
            // 查询生成记录
            $generation = AiTravelPhotoGeneration::where('id', $generationId)->find();
            
            if (!$generation) {
                return $this->error('生成记录不存在');
            }
            
            // 基础返回数据
            $data = [
                'generation_id' => $generation['id'],
                'status' => $generation['status'],
                'status_text' => $generation->status_text ?? '',
                'scene_type' => $generation['scene_type'],
                'cost_time' => $generation['cost_time'],
                'error_msg' => $generation['error_msg']
            ];
            
            // 如果生成成功，查询结果
            if ($generation['status'] == 2) {
                $results = AiTravelPhotoResult::where('generation_id', $generationId)
                    ->where('status', 1)
                    ->order('type ASC, id ASC')
                    ->select();
                
                // 根据场景类型判断输出类型
                if ($generation['scene_type'] >= 3 && $generation['scene_type'] <= 6) {
                    // 视频类型场景（type=19）
                    $videoResult = $results->where('type', 19)->first();
                    if ($videoResult) {
                        $data['video_url'] = $videoResult['url'];
                        $data['video_duration'] = $videoResult['video_duration'];
                        $data['cover_url'] = $videoResult['video_cover'];
                        $data['file_size'] = $videoResult['file_size'];
                    }
                } else {
                    // 图片类型场景
                    // 检查是否有多图输出（type 1-6）
                    $imageResults = $results->where('type', 'between', [1, 6])->toArray();
                    
                    if (count($imageResults) > 1) {
                        // 多图输出
                        $data['results'] = array_map(function($item) {
                            return [
                                'type' => $item['type'],
                                'url' => $item['url'],
                                'watermark_url' => $item['watermark_url'] ?? '',
                                'width' => $item['width'],
                                'height' => $item['height']
                            ];
                        }, $imageResults);
                    } else {
                        // 单图输出
                        $firstResult = $results->first();
                        if ($firstResult) {
                            $data['result_url'] = $firstResult['url'];
                            $data['watermark_url'] = $firstResult['watermark_url'] ?? '';
                            $data['width'] = $firstResult['width'];
                            $data['height'] = $firstResult['height'];
                        }
                    }
                }
            }
            
            return $this->success($data);
            
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
