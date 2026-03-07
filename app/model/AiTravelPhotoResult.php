<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-结果模型
 * Class AiTravelPhotoResult
 * @package app\model
 */
class AiTravelPhotoResult extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_result';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段取出后的默认时间格式
    protected $dateFormat = false;
    
    // 创建时间字段
    protected $createTime = 'create_time';
    
    // 更新时间字段
    protected $updateTime = 'update_time';
    
    // 字段类型转换
    protected $type = [
        'id' => 'integer',
        'aid' => 'integer',
        'generation_id' => 'integer',
        'portrait_id' => 'integer',
        'scene_id' => 'integer',
        'type' => 'integer',
        'video_duration' => 'integer',
        'file_size' => 'integer', // 将在数据库层面改为bigint
        'width' => 'integer',
        'height' => 'integer',
        'quality_score' => 'float',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'share_count' => 'integer',
        'buy_count' => 'integer',
        'download_count' => 'integer',
        'is_selected' => 'integer',
        'status' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_NORMAL = 1;    // 正常
    const STATUS_DELETED = 2;   // 已删除
    
    // 类型常量 - 多图输出类型（1-6）和视频类型（19）
    const TYPE_IMAGE_1 = 1;         // 第1张图
    const TYPE_IMAGE_2 = 2;         // 第2张图
    const TYPE_IMAGE_3 = 3;         // 第3张图
    const TYPE_IMAGE_4 = 4;         // 第4张图
    const TYPE_IMAGE_5 = 5;         // 第5张图
    const TYPE_IMAGE_6 = 6;         // 第6张图
    const TYPE_VIDEO = 19;          // 视频
    
    // 保留原有的镜头类型常量（为了兼容性）
    const TYPE_STANDARD = 1;        // 标准打卡照
    const TYPE_CLOSEUP = 2;         // 特写镜头
    const TYPE_WIDE_ANGLE = 3;      // 广角镜头
    const TYPE_OVERHEAD = 4;        // 俯拍镜头
    const TYPE_LOW_ANGLE = 5;       // 仰拍镜头
    const TYPE_EYE_LEVEL = 6;       // 平拍镜头
    const TYPE_FOLLOW = 7;          // 跟拍镜头
    const TYPE_ORBIT = 8;           // 环绕镜头
    const TYPE_SIDE = 9;            // 侧拍镜头
    const TYPE_BACKLIGHT = 10;      // 逆光镜头
    const TYPE_TOP_LIGHT = 11;      // 顶光镜头
    const TYPE_SIDE_BACKLIGHT = 12; // 侧逆光镜头
    const TYPE_FRONT_SIDE = 13;     // 前侧光镜头
    const TYPE_DIFFUSED = 14;       // 散射光镜头
    const TYPE_RING_LIGHT = 15;     // 环形光镜头
    const TYPE_BUTTERFLY = 16;      // 蝴蝶光镜头
    const TYPE_REMBRANDT = 17;      // 伦勃朗光镜头
    const TYPE_SPLIT = 18;          // 分割光镜头
    const TYPE_VIDEO = 19;          // 视频
    
    /**
     * 关联生成记录
     */
    public function generation()
    {
        return $this->belongsTo(AiTravelPhotoGeneration::class, 'generation_id', 'id');
    }
    
    /**
     * 关联人像
     */
    public function portrait()
    {
        return $this->belongsTo(AiTravelPhotoPortrait::class, 'portrait_id', 'id');
    }
    
    /**
     * 关联场景
     */
    public function scene()
    {
        return $this->belongsTo(AiTravelPhotoScene::class, 'scene_id', 'id');
    }
    
    /**
     * 关联订单商品
     */
    public function orderGoods()
    {
        return $this->hasMany(AiTravelPhotoOrderGoods::class, 'result_id', 'id');
    }
    
    /**
     * 关联用户相册
     */
    public function albums()
    {
        return $this->hasMany(AiTravelPhotoUserAlbum::class, 'result_id', 'id');
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_NORMAL => '正常',
            self::STATUS_DELETED => '已删除',
        ];
        return $status[$data['status']] ?? '未知';
    }
    
    /**
     * 获取类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        return self::getTypeNameByValue($data['type']);
    }
    
    /**
     * 获取是否为视频
     */
    public function getIsVideoAttr($value, $data)
    {
        return $data['type'] == self::TYPE_VIDEO;
    }
    
    /**
     * 搜索器：按生成记录ID搜索
     */
    public function searchGenerationIdAttr($query, $value)
    {
        if ($value) {
            $query->where('generation_id', $value);
        }
    }
    
    /**
     * 搜索器：按人像ID搜索
     */
    public function searchPortraitIdAttr($query, $value)
    {
        if ($value) {
            $query->where('portrait_id', $value);
        }
    }
    
    /**
     * 搜索器：按类型搜索
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('type', $value);
        }
    }
    
    /**
     * 搜索器：按状态搜索
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }
    
    /**
     * 搜索器：按是否精选搜索
     */
    public function searchIsSelectedAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_selected', $value);
        }
    }
    
    /**
     * 根据类型值获取类型名称
     */
    public static function getTypeNameByValue($type)
    {
        $typeNames = [
            self::TYPE_STANDARD => '标准打卡照',
            self::TYPE_CLOSEUP => '特写镜头',
            self::TYPE_WIDE_ANGLE => '广角镜头',
            self::TYPE_OVERHEAD => '俯拍镜头',
            self::TYPE_LOW_ANGLE => '仰拍镜头',
            self::TYPE_EYE_LEVEL => '平拍镜头',
            self::TYPE_FOLLOW => '跟拍镜头',
            self::TYPE_ORBIT => '环绕镜头',
            self::TYPE_SIDE => '侧拍镜头',
            self::TYPE_BACKLIGHT => '逆光镜头',
            self::TYPE_TOP_LIGHT => '顶光镜头',
            self::TYPE_SIDE_BACKLIGHT => '侧逆光镜头',
            self::TYPE_FRONT_SIDE => '前侧光镜头',
            self::TYPE_DIFFUSED => '散射光镜头',
            self::TYPE_RING_LIGHT => '环形光镜头',
            self::TYPE_BUTTERFLY => '蝴蝶光镜头',
            self::TYPE_REMBRANDT => '伦勃朗光镜头',
            self::TYPE_SPLIT => '分割光镜头',
            self::TYPE_VIDEO => '视频',
        ];
        return $typeNames[$type] ?? '未知';
    }
    
    /**
     * 获取所有类型列表
     */
    public static function getTypeList()
    {
        return [
            self::TYPE_STANDARD => '标准打卡照',
            self::TYPE_CLOSEUP => '特写镜头',
            self::TYPE_WIDE_ANGLE => '广角镜头',
            self::TYPE_OVERHEAD => '俯拍镜头',
            self::TYPE_LOW_ANGLE => '仰拍镜头',
            self::TYPE_EYE_LEVEL => '平拍镜头',
            self::TYPE_FOLLOW => '跟拍镜头',
            self::TYPE_ORBIT => '环绕镜头',
            self::TYPE_SIDE => '侧拍镜头',
            self::TYPE_BACKLIGHT => '逆光镜头',
            self::TYPE_TOP_LIGHT => '顶光镜头',
            self::TYPE_SIDE_BACKLIGHT => '侧逆光镜头',
            self::TYPE_FRONT_SIDE => '前侧光镜头',
            self::TYPE_DIFFUSED => '散射光镜头',
            self::TYPE_RING_LIGHT => '环形光镜头',
            self::TYPE_BUTTERFLY => '蝴蝶光镜头',
            self::TYPE_REMBRANDT => '伦勃朗光镜头',
            self::TYPE_SPLIT => '分割光镜头',
            self::TYPE_VIDEO => '视频',
        ];
    }
}
