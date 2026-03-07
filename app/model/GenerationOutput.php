<?php
/**
 * 生成输出模型
 * 存储生成的图片/视频等输出文件信息
 */
namespace app\model;

use think\Model;

class GenerationOutput extends Model
{
    protected $name = 'generation_output';
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    // JSON字段自动转换
    protected $json = ['metadata'];
    protected $jsonAssoc = true;
    
    /**
     * 输出类型常量
     */
    const TYPE_IMAGE = 'image';
    const TYPE_VIDEO = 'video';
    const TYPE_AUDIO = 'audio';
    
    /**
     * 关联生成记录
     */
    public function record()
    {
        return $this->belongsTo(GenerationRecord::class, 'record_id', 'id');
    }
    
    /**
     * 获取文件大小格式化文本
     */
    public function getFileSizeTextAttr($value, $data)
    {
        $size = $data['file_size'] ?? 0;
        if ($size < 1024) {
            return $size . 'B';
        } elseif ($size < 1024 * 1024) {
            return round($size / 1024, 2) . 'KB';
        } elseif ($size < 1024 * 1024 * 1024) {
            return round($size / (1024 * 1024), 2) . 'MB';
        } else {
            return round($size / (1024 * 1024 * 1024), 2) . 'GB';
        }
    }
    
    /**
     * 获取时长格式化文本（视频用）
     */
    public function getDurationTextAttr($value, $data)
    {
        $duration = $data['duration'] ?? 0;
        if ($duration <= 0) {
            return '-';
        }
        $seconds = round($duration / 1000);
        if ($seconds < 60) {
            return $seconds . '秒';
        }
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return $minutes . '分' . $remainingSeconds . '秒';
    }
    
    /**
     * 批量创建输出记录
     */
    public static function createOutputs($recordId, $outputs)
    {
        $data = [];
        $sort = 0;
        foreach ($outputs as $output) {
            $data[] = [
                'record_id' => $recordId,
                'output_type' => $output['type'] ?? self::TYPE_IMAGE,
                'output_url' => $output['url'] ?? '',
                'thumbnail_url' => $output['thumbnail'] ?? '',
                'width' => $output['width'] ?? 0,
                'height' => $output['height'] ?? 0,
                'duration' => $output['duration'] ?? 0,
                'file_size' => $output['file_size'] ?? 0,
                'file_format' => $output['format'] ?? '',
                'metadata' => json_encode($output['metadata'] ?? [], JSON_UNESCAPED_UNICODE),
                'sort' => $sort++,
                'create_time' => time()
            ];
        }
        
        if (!empty($data)) {
            self::insertAll($data);
        }
        
        return count($data);
    }
    
    /**
     * 获取记录的所有输出
     */
    public static function getByRecordId($recordId)
    {
        return self::where('record_id', $recordId)
            ->order('sort asc')
            ->select()->toArray();
    }
    
    /**
     * 删除记录的所有输出
     */
    public static function deleteByRecordId($recordId)
    {
        return self::where('record_id', $recordId)->delete();
    }
}
