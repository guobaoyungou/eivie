<?php
/**
 * 生成记录模型
 * 用于照片生成和视频生成的记录管理
 */
namespace app\model;

use think\Model;
use think\facade\Db;

class GenerationRecord extends Model
{
    protected $name = 'generation_record';
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // JSON字段自动转换
    protected $json = ['input_params'];
    protected $jsonAssoc = true;
    
    /**
     * 生成类型常量
     */
    const TYPE_PHOTO = 1;  // 照片生成
    const TYPE_VIDEO = 2;  // 视频生成
    
    /**
     * 状态常量
     */
    const STATUS_PENDING = 0;     // 待处理
    const STATUS_PROCESSING = 1;  // 处理中
    const STATUS_SUCCESS = 2;     // 成功
    const STATUS_FAILED = 3;      // 失败
    const STATUS_CANCELLED = 4;   // 已取消
    
    /**
     * 能力类型常量
     */
    const CAPABILITY_TEXT2IMAGE_SINGLE = 1;       // 文生图-单张
    const CAPABILITY_TEXT2IMAGE_BATCH = 2;        // 文生图-组图
    const CAPABILITY_IMAGE2IMAGE_SINGLE = 3;      // 图生图-单入单出
    const CAPABILITY_IMAGE2IMAGE_BATCH = 4;       // 图生图-单入多出
    const CAPABILITY_MULTI_IMAGE2IMAGE_SINGLE = 5; // 多图入-单出
    const CAPABILITY_MULTI_IMAGE2IMAGE_BATCH = 6;  // 多图入-多出
    
    /**
     * 获取能力类型文本
     */
    public function getCapabilityTypeTextAttr($value, $data)
    {
        $typeMap = [
            self::CAPABILITY_TEXT2IMAGE_SINGLE => '文生图-单张',
            self::CAPABILITY_TEXT2IMAGE_BATCH => '文生图-组图',
            self::CAPABILITY_IMAGE2IMAGE_SINGLE => '图生图-单入单出',
            self::CAPABILITY_IMAGE2IMAGE_BATCH => '图生图-单入多出',
            self::CAPABILITY_MULTI_IMAGE2IMAGE_SINGLE => '多图入-单出',
            self::CAPABILITY_MULTI_IMAGE2IMAGE_BATCH => '多图入-多出'
        ];
        return $typeMap[$data['capability_type'] ?? 0] ?? '未指定';
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $statusMap = [
            self::STATUS_PENDING => '待处理',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED => '失败',
            self::STATUS_CANCELLED => '已取消'
        ];
        return $statusMap[$data['status']] ?? '未知';
    }
    
    /**
     * 获取生成类型文本
     */
    public function getGenerationTypeTextAttr($value, $data)
    {
        $typeMap = [
            self::TYPE_PHOTO => '照片生成',
            self::TYPE_VIDEO => '视频生成'
        ];
        return $typeMap[$data['generation_type']] ?? '未知';
    }
    
    /**
     * 关联生成输出
     */
    public function outputs()
    {
        return $this->hasMany(GenerationOutput::class, 'record_id', 'id');
    }
    
    /**
     * 关联模型信息
     */
    public function modelInfo()
    {
        return $this->belongsTo('\\think\\Model', 'model_id', 'id')
            ->setTable('model_info');
    }
    
    /**
     * 关联场景模板
     */
    public function sceneTemplate()
    {
        return $this->belongsTo(GenerationSceneTemplate::class, 'scene_id', 'id');
    }
    
    /**
     * 获取记录列表（带模型信息）
     */
    public static function getListWithModel($where, $page = 1, $limit = 20, $order = 'id desc')
    {
        $query = self::alias('r')
            ->leftJoin('model_info m', 'r.model_id = m.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->field('r.*, m.model_name, m.model_code as model_code_ref, p.provider_name')
            ->where($where);
        
        $count = $query->count();
        
        $data = self::alias('r')
            ->leftJoin('model_info m', 'r.model_id = m.id')
            ->leftJoin('model_provider p', 'm.provider_id = p.id')
            ->field('r.*, m.model_name, m.model_code as model_code_ref, p.provider_name')
            ->where($where)
            ->page($page, $limit)
            ->order($order)
            ->select()->toArray();
        
        foreach ($data as &$item) {
            // create_time 可能已被 Model::toArray() 自动格式化为字符串，需兼容处理
            $item['create_time_text'] = $item['create_time']
                ? (is_numeric($item['create_time']) ? date('Y-m-d H:i:s', $item['create_time']) : $item['create_time'])
                : '';
            $item['finish_time_text'] = $item['finish_time']
                ? (is_numeric($item['finish_time']) ? date('Y-m-d H:i:s', $item['finish_time']) : $item['finish_time'])
                : '';
            $item['cost_time_text'] = $item['cost_time'] > 0 ? round($item['cost_time'] / 1000, 2) . 's' : '-';
            
            // 获取输出数量
            $item['output_count'] = Db::name('generation_output')->where('record_id', $item['id'])->count();
            
            // 检查是否已转换过模板
            $item['is_template_converted'] = Db::name('generation_scene_template')
                ->where('source_record_id', $item['id'])
                ->count() > 0 ? 1 : 0;
            
            // 获取输出的图片/视频URL列表（用于生成视频等操作）
            $item['output_urls'] = Db::name('generation_output')
                ->where('record_id', $item['id'])
                ->column('output_url');
        }
        
        return ['count' => $count, 'data' => $data];
    }
    
    /**
     * 创建生成记录
     */
    public static function createRecord($data)
    {
        $record = new self();
        $record->aid = $data['aid'] ?? 0;
        $record->bid = $data['bid'] ?? 0;
        $record->uid = $data['uid'] ?? 0;
        $record->order_id = $data['order_id'] ?? 0;
        $record->generation_type = $data['generation_type'];
        $record->model_id = $data['model_id'];
        $record->model_code = $data['model_code'] ?? '';
        $record->scene_id = $data['scene_id'] ?? 0;
        $record->capability_type = $data['capability_type'] ?? 0;
        $record->input_params = $data['input_params'];
        $record->output_type = $data['output_type'] ?? ($data['generation_type'] == self::TYPE_PHOTO ? 'image' : 'video');
        $record->status = self::STATUS_PENDING;
        $record->queue_time = time();
        $record->save();
        
        return $record;
    }
    
    /**
     * 更新为处理中
     */
    public function markProcessing($taskId = '')
    {
        $this->status = self::STATUS_PROCESSING;
        $this->task_id = $taskId;
        $this->start_time = time();
        $this->save();
    }
    
    /**
     * 更新为成功
     */
    public function markSuccess($costTime = 0, $costTokens = 0, $costAmount = 0)
    {
        $this->status = self::STATUS_SUCCESS;
        $this->finish_time = time();
        $this->cost_time = $costTime;
        $this->cost_tokens = $costTokens;
        $this->cost_amount = $costAmount;
        $this->save();
        
        // 同步更新关联订单的任务状态
        $this->syncOrderTaskStatus(self::STATUS_SUCCESS);
        
        // 将生成的输出文件记入用户云端存储空间
        $this->recordOutputsToStorage();
    }

    /**
     * 将生成输出记录到用户云端存储空间
     */
    protected function recordOutputsToStorage()
    {
        try {
            // 获取用户身份：优先从关联订单获取 mid
            $aid = $this->aid;
            $mid = 0;
            if ($this->order_id > 0) {
                $order = Db::name('generation_order')->where('id', $this->order_id)->field('aid,mid')->find();
                if ($order) {
                    $aid = $order['aid'];
                    $mid = intval($order['mid']);
                }
            }
            // 兜底：使用 record 的 uid
            if ($mid <= 0 && $this->uid > 0) {
                $mid = $this->uid;
            }
            if ($mid <= 0 || $aid <= 0) return;

            // 获取该记录的所有输出
            $outputs = GenerationOutput::getByRecordId($this->id);
            if (empty($outputs)) return;

            $storageService = new \app\service\StorageService();
            foreach ($outputs as $output) {
                // 避免重复入库
                $existing = \app\model\UserStorageFile::getBySource('generated', $output['id'], $mid);
                if ($existing) continue;

                $fileSize = intval($output['file_size'] ?? 0);
                // 如果 file_size 为 0，尝试通过 HEAD 请求获取
                if ($fileSize <= 0 && !empty($output['output_url'])) {
                    $fileSize = \app\service\StorageService::getRemoteFileSize($output['output_url']);
                }

                $fileType = ($output['output_type'] ?? 'image') === 'video' ? 'video' : 'image';

                $storageService->addFile($aid, $mid, [
                    'file_url' => $output['output_url'] ?? '',
                    'thumbnail_url' => $output['thumbnail_url'] ?? '',
                    'file_type' => $fileType,
                    'source_type' => 'generated',
                    'source_id' => $output['id'],
                    'file_size' => $fileSize,
                    'width' => intval($output['width'] ?? 0),
                    'height' => intval($output['height'] ?? 0),
                    'duration' => intval($output['duration'] ?? 0),
                ]);
            }
        } catch (\Exception $e) {
            \think\facade\Log::warning('recordOutputsToStorage error: ' . $e->getMessage());
        }
    }
    
    /**
     * 更新为失败
     */
    public function markFailed($errorCode = '', $errorMsg = '')
    {
        $this->status = self::STATUS_FAILED;
        $this->finish_time = time();
        $this->error_code = $errorCode;
        $this->error_msg = $errorMsg;
        $this->save();
        
        // 同步更新关联订单的任务状态
        $this->syncOrderTaskStatus(self::STATUS_FAILED);
    }
    
    /**
     * 同步任务状态到关联订单
     */
    protected function syncOrderTaskStatus($status)
    {
        try {
            $orderService = new \app\service\GenerationOrderService();
            $orderService->syncTaskStatus($this->id, $status);
        } catch (\Exception $e) {
            \think\facade\Log::error('同步订单任务状态失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 增加重试次数
     */
    public function incrementRetry()
    {
        $this->retry_count += 1;
        $this->status = self::STATUS_PENDING;
        $this->save();
    }
}
