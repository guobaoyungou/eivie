<?php

namespace app\model;

use think\Model;

/**
 * AI模型分类模型
 * Class AiModelCategory
 * @package app\model
 */
class AiModelCategory extends Model
{
    // 设置表名
    protected $name = 'ai_model_category';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'level' => 'integer',
        'is_system' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];
    
    /**
     * 关联父级分类
     */
    public function parent()
    {
        return $this->hasOne(AiModelCategory::class, 'code', 'parent_code');
    }
    
    /**
     * 关联子级分类
     */
    public function children()
    {
        return $this->hasMany(AiModelCategory::class, 'parent_code', 'code');
    }
    
    /**
     * 获取层级文本
     */
    public function getLevelTextAttr($value, $data)
    {
        $levels = [1 => '一级分类', 2 => '二级分类'];
        return $levels[$data['level']] ?? '未知';
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] == 1 ? '启用' : '禁用';
    }
    
    /**
     * 获取系统预置文本
     */
    public function getIsSystemTextAttr($value, $data)
    {
        return $data['is_system'] == 1 ? '系统分类' : '自定义';
    }
    
    /**
     * 搜索器：关键词
     */
    public function searchKeywordAttr($query, $value)
    {
        if ($value) {
            $query->where(function($q) use ($value) {
                $q->where('name', 'like', "%{$value}%")
                  ->whereOr('code', 'like', "%{$value}%");
            });
        }
    }
    
    /**
     * 搜索器：层级
     */
    public function searchLevelAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('level', $value);
        }
    }
    
    /**
     * 搜索器：是否系统分类
     */
    public function searchIsSystemAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('is_system', $value);
        }
    }
    
    /**
     * 搜索器：状态
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('status', $value);
        }
    }
    
    /**
     * 获取一级分类列表
     */
    public static function getLevel1List()
    {
        return self::where('level', 1)
            ->where('status', 1)
            ->order('sort', 'desc')
            ->order('id', 'asc')
            ->select();
    }
    
    /**
     * 获取二级分类列表
     * @param string $parentCode 父级分类代码
     */
    public static function getLevel2List($parentCode = '')
    {
        $query = self::where('level', 2)->where('status', 1);
        
        if ($parentCode) {
            $query->where('parent_code', $parentCode);
        }
        
        return $query->order('sort', 'desc')
            ->order('id', 'asc')
            ->select();
    }
    
    /**
     * 获取分类树
     */
    public static function getCategoryTree()
    {
        // 获取所有一级分类
        $level1 = self::where('level', 1)
            ->where('status', 1)
            ->order('sort', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        
        // 获取所有二级分类
        $level2 = self::where('level', 2)
            ->where('status', 1)
            ->order('sort', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        
        // 组装树形结构
        foreach ($level1 as &$item) {
            $item['children'] = [];
            foreach ($level2 as $child) {
                if ($child['parent_code'] == $item['code']) {
                    $item['children'][] = $child;
                }
            }
        }
        
        return $level1;
    }
    
    /**
     * 验证分类代码唯一性
     */
    public static function checkCodeUnique($code, $excludeId = 0)
    {
        $query = self::where('code', $code);
        
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        
        return $query->count() == 0;
    }
}
