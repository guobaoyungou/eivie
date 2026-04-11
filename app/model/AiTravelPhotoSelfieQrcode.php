<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * AI旅拍-门店自拍二维码模型
 * Class AiTravelPhotoSelfieQrcode
 * @package app\model
 */
class AiTravelPhotoSelfieQrcode extends Model
{
    // 表名
    protected $name = 'ai_travel_photo_selfie_qrcode';
    
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
        'bid' => 'integer',
        'mdid' => 'integer',
        'scan_count' => 'integer',
        'follow_count' => 'integer',
        'selfie_count' => 'integer',
        'match_count' => 'integer',
        'status' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_NORMAL = 1;
    
    /**
     * 默认推文标题
     */
    const DEFAULT_PUSH_TITLE = '点击查找您的在景区的旅拍留影';
    
    /**
     * 默认推文描述
     */
    const DEFAULT_PUSH_DESC = '你在该景区的游玩照片已为您准备，请打开链接查找。';
    
    /**
     * 获取推文标题（有自定义则用自定义，否则用默认）
     */
    public function getPushTitleTextAttr($value, $data)
    {
        return !empty($data['push_title']) ? $data['push_title'] : self::DEFAULT_PUSH_TITLE;
    }
    
    /**
     * 获取推文描述（有自定义则用自定义，否则用默认）
     */
    public function getPushDescTextAttr($value, $data)
    {
        return !empty($data['push_desc']) ? $data['push_desc'] : self::DEFAULT_PUSH_DESC;
    }
    
    /**
     * 搜索器：按商家ID搜索
     */
    public function searchBidAttr($query, $value)
    {
        if ($value) {
            $query->where('bid', $value);
        }
    }
    
    /**
     * 搜索器：按门店ID搜索
     */
    public function searchMdidAttr($query, $value)
    {
        if ($value) {
            $query->where('mdid', $value);
        }
    }
    
    /**
     * 搜索器：按场景值搜索
     */
    public function searchSceneStrAttr($query, $value)
    {
        if ($value) {
            $query->where('scene_str', $value);
        }
    }
}
