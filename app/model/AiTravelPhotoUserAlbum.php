<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-用户相册模型
 * Class AiTravelPhotoUserAlbum
 * @package app\model
 */
class AiTravelPhotoUserAlbum extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_user_album';
    
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
        'uid' => 'integer',
        'bid' => 'integer',
        'mdid' => 'integer',
        'order_id' => 'integer',
        'portrait_id' => 'integer',
        'result_id' => 'integer',
        'type' => 'integer',
        'folder_id' => 'integer',
        'is_favorite' => 'integer',
        'status' => 'integer',
        'download_count' => 'integer',
        'share_count' => 'integer',
        'view_count' => 'integer',
        'last_view_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // 状态常量
    const STATUS_DELETED = 0;   // 已删除
    const STATUS_NORMAL = 1;    // 正常
    
    // 类型常量
    const TYPE_IMAGE = 1;       // 图片
    const TYPE_VIDEO = 2;       // 视频
    
    /**
     * 关联订单
     */
    public function order()
    {
        return $this->belongsTo(AiTravelPhotoOrder::class, 'order_id', 'id');
    }
    
    /**
     * 关联人像
     */
    public function portrait()
    {
        return $this->belongsTo(AiTravelPhotoPortrait::class, 'portrait_id', 'id');
    }
    
    /**
     * 关联结果
     */
    public function result()
    {
        return $this->belongsTo(AiTravelPhotoResult::class, 'result_id', 'id');
    }
    
    /**
     * 获取类型文本
     */
    public function getTypeTextAttr($value, $data)
    {
        $type = [
            self::TYPE_IMAGE => '图片',
            self::TYPE_VIDEO => '视频',
        ];
        return $type[$data['type']] ?? '未知';
    }
    
    /**
     * 搜索器：按用户ID搜索
     */
    public function searchUidAttr($query, $value)
    {
        if ($value) {
            $query->where('uid', $value);
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
     * 搜索器：按是否收藏搜索
     */
    public function searchIsFavoriteAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_favorite', $value);
        }
    }
}
