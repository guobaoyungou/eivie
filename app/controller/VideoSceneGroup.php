<?php
/**
 * 视频生成场景分组控制器
 * 管理视频生成场景模板的分组（扁平结构，标签式管理）
 */
namespace app\controller;

use think\facade\View;
use think\facade\Db;

class VideoSceneGroup extends Common
{
    protected $generationType = 2; // 视频生成
    protected $tableName = 'generation_scene_group';
    protected $templateTable = 'generation_scene_template';
    
    /**
     * 分组列表
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = input('param.page', 1);
            $limit = input('param.limit', 20);
            
            if (input('param.field') && input('param.order')) {
                $order = input('param.field') . ' ' . input('param.order');
            } else {
                $order = 'sort desc, id desc';
            }
            
            $where = [
                ['aid', '=', aid],
                ['generation_type', '=', $this->generationType]
            ];
            
            // 商户只能看到自己的分组
            if (bid > 0) {
                $where[] = ['bid', '=', bid];
            }
            
            // 名称搜索
            if (input('param.name')) {
                $where[] = ['name', 'like', '%' . input('param.name') . '%'];
            }
            
            // 状态筛选
            if (input('?param.status') && input('param.status') !== '') {
                $where[] = ['status', '=', input('param.status')];
            }
            
            $count = Db::name($this->tableName)->where($where)->count();
            $data = Db::name($this->tableName)->where($where)->page($page, $limit)->order($order)->select()->toArray();
            
            // 统计每个分组关联的模板数量
            foreach ($data as &$item) {
                $item['template_count'] = Db::name($this->templateTable)
                    ->where('aid', aid)
                    ->where('generation_type', $this->generationType)
                    ->whereRaw("FIND_IN_SET('{$item['id']}', group_ids)")
                    ->count();
            }
            unset($item);
            
            return json(['code' => 0, 'msg' => '查询成功', 'count' => $count, 'data' => $data]);
        }
        
        return View::fetch();
    }
    
    /**
     * 编辑分组
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
            $info = ['id' => '', 'name' => '', 'pic' => '', 'description' => '', 'sort' => 0, 'status' => 1];
        }
        
        View::assign('info', $info);
        
        return View::fetch('video_scene_group/edit');
    }
    
    /**
     * 保存分组
     */
    public function save()
    {
        $info = input('post.info/a');
        
        // 验证名称
        if (empty($info['name'])) {
            return json(['status' => 0, 'msg' => '分组名称不能为空']);
        }
        
        if ($info['id']) {
            // 编辑模式 - 验证归属权限
            $exists = Db::name($this->tableName)->where('aid', aid)->where('id', $info['id'])->find();
            if (!$exists) {
                return json(['status' => 0, 'msg' => '分组不存在或无权限']);
            }
            if (bid > 0 && $exists['bid'] != bid) {
                return json(['status' => 0, 'msg' => '无权限编辑此分组']);
            }
            
            $info['update_time'] = time();
            Db::name($this->tableName)->where('aid', aid)->where('id', $info['id'])->update($info);
            \app\common\System::plog('编辑视频场景分组' . $info['id']);
        } else {
            // 新增
            $info['aid'] = aid;
            $info['bid'] = bid;
            $info['generation_type'] = $this->generationType;
            $info['create_time'] = time();
            $id = Db::name($this->tableName)->insertGetId($info);
            \app\common\System::plog('添加视频场景分组' . $id);
        }
        
        return json(['status' => 1, 'msg' => '操作成功', 'url' => (string)url('index')]);
    }
    
    /**
     * 删除分组（支持批量）
     */
    public function del()
    {
        $ids = input('post.ids/a');
        $force = input('post.force', 0);
        
        if (empty($ids)) {
            return json(['status' => 0, 'msg' => '请选择要删除的分组']);
        }
        
        // 检查是否有模板关联
        if (!$force) {
            foreach ($ids as $id) {
                $templateCount = Db::name($this->templateTable)
                    ->where('aid', aid)
                    ->where('generation_type', $this->generationType)
                    ->whereRaw("FIND_IN_SET('{$id}', group_ids)")
                    ->count();
                
                if ($templateCount > 0) {
                    return json(['status' => 0, 'msg' => '分组下存在关联模板（' . $templateCount . '个），请先取消关联或选择强制删除', 'has_template' => 1]);
                }
            }
        }
        
        // 如果强制删除，清除模板中的分组关联
        if ($force) {
            foreach ($ids as $id) {
                $templates = Db::name($this->templateTable)
                    ->where('aid', aid)
                    ->where('generation_type', $this->generationType)
                    ->whereRaw("FIND_IN_SET('{$id}', group_ids)")
                    ->select()->toArray();
                
                foreach ($templates as $tpl) {
                    $groupIds = array_filter(explode(',', $tpl['group_ids']));
                    $groupIds = array_diff($groupIds, [$id]);
                    Db::name($this->templateTable)->where('id', $tpl['id'])->update([
                        'group_ids' => implode(',', $groupIds),
                        'update_time' => time()
                    ]);
                }
            }
        }
        
        $where = [['aid', '=', aid], ['id', 'in', $ids]];
        if (bid > 0) {
            $where[] = ['bid', '=', bid];
        }
        
        Db::name($this->tableName)->where($where)->delete();
        \app\common\System::plog('删除视频场景分组' . implode(',', $ids));
        
        return json(['status' => 1, 'msg' => '删除成功']);
    }
    
    /**
     * 分组选择弹窗（供场景模板使用）
     */
    public function choosegroup()
    {
        if (request()->isAjax()) {
            if (input('param.field') && input('param.order')) {
                $order = input('param.field') . ' ' . input('param.order');
            } else {
                $order = 'sort desc, id desc';
            }
            
            $where = [
                ['aid', '=', aid],
                ['generation_type', '=', $this->generationType],
                ['status', '=', 1] // 只显示启用的分组
            ];
            
            if (bid > 0) {
                $where[] = ['bid', '=', bid];
            }
            
            $data = Db::name($this->tableName)->where($where)->order($order)->select()->toArray();
            
            return json(['code' => 0, 'msg' => '查询成功', 'count' => count($data), 'data' => $data]);
        }
        
        return View::fetch('video_scene_group/choosegroup');
    }
}
