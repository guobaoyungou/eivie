<?php
/**
 * 视频生成场景分类控制器
 * 管理视频生成场景模板的分类
 */
namespace app\controller;

use think\facade\View;
use think\facade\Db;

class VideoSceneCategory extends Common
{
    protected $generationType = 2; // 视频生成
    protected $tableName = 'generation_scene_category';
    protected $templateTable = 'generation_scene_template';
    
    /**
     * 分类列表（树形结构）
     */
    public function index()
    {
        if (request()->isAjax()) {
            if (input('param.field') && input('param.order')) {
                $order = input('param.field') . ' ' . input('param.order');
            } else {
                $order = 'sort desc, id';
            }
            
            $where = [
                ['aid', '=', aid],
                ['generation_type', '=', $this->generationType]
            ];
            
            // 商户只能看到自己的分类
            if (bid > 0) {
                $where[] = ['bid', '=', bid];
            }
            
            $data = [];
            
            // 顶级分类
            $cate0 = Db::name($this->tableName)
                ->where($where)
                ->where('pid', 0)
                ->order($order)
                ->select()->toArray();
            
            foreach ($cate0 as $c0) {
                $c0['deep'] = 0;
                $data[] = $c0;
                
                // 二级分类
                $cate1 = Db::name($this->tableName)
                    ->where($where)
                    ->where('pid', $c0['id'])
                    ->order($order)
                    ->select()->toArray();
                
                foreach ($cate1 as $k1 => $c1) {
                    $c1['deep'] = 1;
                    $data[] = $c1;
                    
                    // 三级分类
                    $cate2 = Db::name($this->tableName)
                        ->where($where)
                        ->where('pid', $c1['id'])
                        ->order($order)
                        ->select()->toArray();
                    
                    foreach ($cate2 as $k2 => $c2) {
                        $c2['deep'] = 2;
                        $data[] = $c2;
                    }
                }
            }
            
            return json(['code' => 0, 'msg' => '查询成功', 'count' => count($cate0), 'data' => $data]);
        }
        
        return View::fetch();
    }
    
    /**
     * 编辑分类
     */
    public function edit()
    {
        if (input('param.id')) {
            $where = [
                ['aid', '=', aid],
                ['id', '=', input('param.id/d')]
            ];
            if (bid > 0) {
                $where[] = ['bid', '=', bid];
            }
            $info = Db::name($this->tableName)->where($where)->find();
        } else {
            $info = ['id' => '', 'pid' => 0, 'status' => 1, 'sort' => 0];
        }
        
        if (input('param.pid')) {
            $info['pid'] = input('param.pid');
        }
        
        // 获取上级分类列表（只显示前两级，因为最多支持3级）
        $where = [
            ['aid', '=', aid],
            ['generation_type', '=', $this->generationType]
        ];
        if (bid > 0) {
            $where[] = ['bid', '=', bid];
        }
        
        $pcatelist = Db::name($this->tableName)
            ->where($where)
            ->where('pid', 0)
            ->where('id', '<>', $info['id'] ?: 0)
            ->order('sort desc, id')
            ->select()->toArray();
        
        foreach ($pcatelist as $k => $v) {
            $pcatelist[$k]['child'] = Db::name($this->tableName)
                ->field('id, name')
                ->where($where)
                ->where('pid', $v['id'])
                ->where('id', '<>', $info['id'] ?: 0)
                ->order('sort desc, id')
                ->select()->toArray();
        }
        
        View::assign('info', $info);
        View::assign('pcatelist', $pcatelist);
        
        return View::fetch('video_scene_category/edit');
    }
    
    /**
     * 保存分类
     */
    public function save()
    {
        $info = input('post.info/a');
        
        // 验证名称
        if (empty($info['name'])) {
            return json(['status' => 0, 'msg' => '分类名称不能为空']);
        }
        
        // 检查层级限制（最多3级）
        if (!empty($info['pid'])) {
            $parent = Db::name($this->tableName)->where('id', $info['pid'])->find();
            if ($parent) {
                // 如果父级是二级分类，则当前为三级
                if ($parent['pid'] > 0) {
                    $grandParent = Db::name($this->tableName)->where('id', $parent['pid'])->find();
                    if ($grandParent && $grandParent['pid'] > 0) {
                        return json(['status' => 0, 'msg' => '分类层级最多支持3级']);
                    }
                }
            }
        }
        
        if ($info['id']) {
            // 更新
            $info['update_time'] = time();
            Db::name($this->tableName)->where('aid', aid)->where('id', $info['id'])->update($info);
            \app\common\System::plog('编辑视频场景分类' . $info['id']);
        } else {
            // 新增
            $info['aid'] = aid;
            $info['bid'] = bid;
            $info['generation_type'] = $this->generationType;
            $info['create_time'] = time();
            $id = Db::name($this->tableName)->insertGetId($info);
            \app\common\System::plog('添加视频场景分类' . $id);
        }
        
        return json(['status' => 1, 'msg' => '操作成功', 'url' => (string)url('index')]);
    }
    
    /**
     * 删除分类
     */
    public function del()
    {
        $ids = input('post.ids/a');
        
        if (empty($ids)) {
            return json(['status' => 0, 'msg' => '请选择要删除的分类']);
        }
        
        foreach ($ids as $id) {
            // 检查是否有子分类
            $childCount = Db::name($this->tableName)
                ->where('aid', aid)
                ->where('pid', $id)
                ->count();
            
            if ($childCount > 0) {
                return json(['status' => 0, 'msg' => '分类下存在子分类，无法删除']);
            }
            
            // 检查是否有关联模板（需检查 category_ids 逗号分隔字段）
            $templateCount = Db::name($this->templateTable)
                ->where('aid', aid)
                ->where(function($query) use ($id) {
                    $query->whereRaw("FIND_IN_SET('{$id}', category_ids)");
                })
                ->count();
            
            if ($templateCount > 0) {
                return json(['status' => 0, 'msg' => '分类下存在关联模板，无法删除']);
            }
        }
        
        $where = [['aid', '=', aid], ['id', 'in', $ids]];
        if (bid > 0) {
            $where[] = ['bid', '=', bid];
        }
        
        Db::name($this->tableName)->where($where)->delete();
        \app\common\System::plog('删除视频场景分类' . implode(',', $ids));
        
        return json(['status' => 1, 'msg' => '删除成功']);
    }
    
    /**
     * 选择分类弹窗（供场景模板使用）
     */
    public function choosecategory()
    {
        $selmore = true; // 默认启用多选模式
        $maxselect = input('maxselect', 10); // 最多可选数量
        
        if (request()->isAjax()) {
            if (input('param.field') && input('param.order')) {
                $order = input('param.field') . ' ' . input('param.order');
            } else {
                $order = 'sort desc, id';
            }
            
            $where = [
                ['aid', '=', aid],
                ['generation_type', '=', $this->generationType],
                ['status', '=', 1] // 只显示启用的分类
            ];
            
            if (bid > 0) {
                $where[] = ['bid', '=', bid];
            }
            
            $data = [];
            
            // 顶级分类
            $cate0 = Db::name($this->tableName)
                ->where($where)
                ->where('pid', 0)
                ->order($order)
                ->select()->toArray();
            
            foreach ($cate0 as $c0) {
                $c0['showname'] = $c0['name'];
                $c0['deep'] = 0;
                $data[] = $c0;
                
                // 二级分类
                $cate1 = Db::name($this->tableName)
                    ->where($where)
                    ->where('pid', $c0['id'])
                    ->order($order)
                    ->select()->toArray();
                
                foreach ($cate1 as $k1 => $c1) {
                    if ($k1 < count($cate1) - 1) {
                        $c1['showname'] = '<span style="color:#aaa">&nbsp;&nbsp;&nbsp;&nbsp;├ </span>' . $c1['name'];
                    } else {
                        $c1['showname'] = '<span style="color:#aaa">&nbsp;&nbsp;&nbsp;&nbsp;└ </span>' . $c1['name'];
                    }
                    $c1['deep'] = 1;
                    $data[] = $c1;
                    
                    // 三级分类
                    $cate2 = Db::name($this->tableName)
                        ->where($where)
                        ->where('pid', $c1['id'])
                        ->order($order)
                        ->select()->toArray();
                    
                    foreach ($cate2 as $k2 => $c2) {
                        if ($k2 < count($cate2) - 1) {
                            $c2['showname'] = '<span style="color:#aaa">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;├ </span>' . $c2['name'];
                        } else {
                            $c2['showname'] = '<span style="color:#aaa">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└ </span>' . $c2['name'];
                        }
                        $c2['deep'] = 2;
                        $data[] = $c2;
                    }
                }
            }
            
            return json(['code' => 0, 'msg' => '查询成功', 'count' => count($cate0), 'data' => $data]);
        }
        
        View::assign('selmore', $selmore);
        View::assign('maxselect', $maxselect);
        return View::fetch('video_scene_category/choosecategory');
    }
}
