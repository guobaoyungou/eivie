<?php
declare(strict_types=1);

namespace app\controller;

use think\facade\Db;
use think\facade\View;

/**
 * 系统变量管理
 * 管理提示词改写模板中的系统变量（如{自动标签性别}{自动标签年龄}{模板绑定模型}等）
 */
class WebSystemVariable extends WebCommon
{
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 系统变量列表页
     */
    public function index()
    {
        if (request()->isAjax()) {
            return $this->getVariableList();
        }

        return View::fetch();
    }

    /**
     * AJAX获取变量列表
     */
    protected function getVariableList()
    {
        $record = Db::name('sysset')->where('name', 'system_variables')->find();
        $variables = [];

        if ($record && !empty($record['value'])) {
            $variables = json_decode($record['value'], true) ?: [];
        }

        // 转换为数据表格需要的格式
        $list = [];
        foreach ($variables as $key => $desc) {
            $list[] = [
                'var_key' => '{' . $key . '}',
                'var_name' => $key,
                'description' => $desc,
            ];
        }

        return json([
            'code' => 0,
            'msg' => '',
            'count' => count($list),
            'data' => $list,
        ]);
    }

    /**
     * 编辑系统变量
     */
    public function edit()
    {
        if (request()->isPost()) {
            return $this->saveVariables();
        }

        $record = Db::name('sysset')->where('name', 'system_variables')->find();
        $variables = [];
        if ($record && !empty($record['value'])) {
            $variables = json_decode($record['value'], true) ?: [];
        }

        View::assign('variables', $variables);
        return View::fetch();
    }

    /**
     * 保存系统变量
     */
    protected function saveVariables()
    {
        $data = input('post.variables/a', []);
        
        // 过滤空值
        $variables = [];
        foreach ($data as $item) {
            $name = trim($item['name'] ?? '');
            $desc = trim($item['description'] ?? '');
            if (!empty($name)) {
                $variables[$name] = $desc;
            }
        }

        $value = json_encode($variables, JSON_UNESCAPED_UNICODE);

        $exists = Db::name('sysset')->where('name', 'system_variables')->find();
        if ($exists) {
            Db::name('sysset')->where('name', 'system_variables')->update(['value' => $value]);
        } else {
            Db::name('sysset')->insert(['name' => 'system_variables', 'value' => $value]);
        }

        \app\common\System::plog('系统变量管理-编辑系统变量', 1);
        return json(['status' => 1, 'msg' => '保存成功', 'url' => true]);
    }
}
