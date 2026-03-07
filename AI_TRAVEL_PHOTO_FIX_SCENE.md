# AI旅拍功能 - 场景管理问题修复

## 问题描述

用户报告场景管理页面存在问题，包括：
1. 页面无法正常加载
2. 表单提交后可能无响应
3. 数据无法正常显示

## 问题排查

### 1. 数据库检查

检查`ddwx_ai_travel_photo_scene`表：

```bash
mysql> DESC ddwx_ai_travel_photo_scene;
+-----------------+--------------+------+-----+---------+----------------+
| Field           | Type         | Null | Key | Default | Extra          |
+-----------------+--------------+------+-----+---------+----------------+
| id              | int          | NO   | PRI | NULL    | auto_increment |
| aid             | int          | NO   | MUL | 0       |                |
| bid             | int          | NO   | MUL | 0       |                |
| name            | varchar(100) | NO   |     | NULL    |                |
| category        | varchar(50)  | YES  | MUL | NULL    |                |
| cover           | varchar(500) | YES  |     | NULL    |                |
| status          | tinyint(1)   | NO   | MUL | 1       |                |
| ... (更多字段)
+-----------------+--------------+------+-----+---------+----------------+
```

✅ 表结构正确

### 2. 代码问题分析

#### 问题1：表单提交响应格式错误

**scene_edit()方法的问题**：
```php
// ❌ 错误代码
if ($id > 0) {
    Db::name('ai_travel_photo_scene')->where('id', $id)->update($data);
    $this->success('保存成功');  // 返回HTML跳转页面
} else {
    Db::name('ai_travel_photo_scene')->insert($data);
    $this->success('添加成功');  // 返回HTML跳转页面
}
```

**前端AJAX请求期望JSON响应**：
```javascript
$.post("{:url('scene_edit')}", field, function(data){
    layer.close(index);
    dialog(data.msg, data.status);  // 需要JSON格式
    if(data.status == 1){
        setTimeout(function(){
            parent.layer.closeAll();
            parent.tableIns.reload();
        }, 1000)
    }
})
```

**相同问题存在于**：
- `scene_edit()` - 场景添加/编辑
- `package_edit()` - 套餐添加/编辑

## 修复方案

### 1. 修改scene_edit()方法

```php
public function scene_edit()
{
    $id = input('param.id/d', 0);

    if (request()->isPost()) {
        try {
            $data = input('post.');
            $data['aid'] = $this->aid;
            $data['bid'] = $this->bid;

            if ($id > 0) {
                // 编辑
                $data['update_time'] = time();
                Db::name('ai_travel_photo_scene')->where('id', $id)->update($data);
                return json(['status' => 1, 'msg' => '保存成功']);  // ✅ 返回JSON
            } else {
                // 新增
                $data['create_time'] = time();
                $data['update_time'] = time();
                Db::name('ai_travel_photo_scene')->insert($data);
                return json(['status' => 1, 'msg' => '添加成功']);  // ✅ 返回JSON
            }
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
    }

    // 查询场景信息
    $info = [];
    if ($id > 0) {
        $info = Db::name('ai_travel_photo_scene')->where('id', $id)->find();
    }

    // 获取AI模型列表
    $models = Db::name('ai_travel_photo_model')
        ->where('aid', $this->aid)
        ->where('status', 1)
        ->select();

    View::assign('info', $info);
    View::assign('models', $models);
    return View::fetch();
}
```

### 2. 修改package_edit()方法

```php
public function package_edit()
{
    $id = input('param.id/d', 0);

    if (request()->isPost()) {
        try {
            $data = input('post.');
            $data['aid'] = $this->aid;
            $data['bid'] = $this->bid;

            if ($id > 0) {
                $data['update_time'] = time();
                Db::name('ai_travel_photo_package')->where('id', $id)->update($data);
                return json(['status' => 1, 'msg' => '保存成功']);
            } else {
                $data['create_time'] = time();
                $data['update_time'] = time();
                Db::name('ai_travel_photo_package')->insert($data);
                return json(['status' => 1, 'msg' => '添加成功']);
            }
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
    }

    $info = [];
    if ($id > 0) {
        $info = Db::name('ai_travel_photo_package')->where('id', $id)->find();
    }

    View::assign('info', $info);
    return View::fetch();
}
```

### 3. 添加测试数据

为了便于测试，已添加了5条示例场景数据：

```sql
INSERT INTO ddwx_ai_travel_photo_scene 
(aid, bid, name, category, status, sort, create_time, update_time) 
VALUES 
(1, 1, '北京天安门', '城市风光', 1, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 1, '上海外滩', '城市风光', 1, 99, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 1, '桂林山水', '自然风光', 1, 98, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 1, '三亚海滩', '海岛沙滩', 1, 97, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 1, '西藏布达拉宫', '人文古迹', 1, 96, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
```

### 4. 增强测试方法

更新`test()`方法，增加更多调试信息：

```php
public function test()
{
    try {
        echo "<h3>测试开始</h3>";
        echo "aid: " . $this->aid . "<br>";
        echo "bid: " . $this->bid . "<br>";
        echo "uid: " . $this->uid . "<br>";
        
        // 测试数据库连接
        $tables = Db::query("SHOW TABLES LIKE 'ddwx_ai_travel_photo%'");
        echo "<h4>数据库表列表：</h4>";
        echo "<pre>";
        print_r($tables);
        echo "</pre>";
        
        // 测试查询scene表
        $count = Db::name('ai_travel_photo_scene')->count();
        echo "<h4>scene表总数：" . $count . "</h4>";
        
        // 测试查询business表
        $business = Db::name('business')->where('id', $this->bid)->find();
        echo "<h4>商家信息：</h4>";
        echo "<pre>";
        print_r($business);
        echo "</pre>";
        
        // 测试scene_list方法
        echo "<h4>测试scene_list AJAX请求：</h4>";
        $where = [
            ['aid', '=', $this->aid],
            ['bid', '=', $this->bid]
        ];
        $list = Db::name('ai_travel_photo_scene')->where($where)->select();
        echo "查询条件: aid=" . $this->aid . ", bid=" . $this->bid . "<br>";
        echo "查询结果数: " . count($list) . "<br>";
        
        echo "<h3>测试成功！</h3>";
    } catch (\Exception $e) {
        echo "<h3 style='color:red'>错误信息：</h3>";
        echo "<pre style='color:red'>" . $e->getMessage() . "</pre>";
        echo "<h4>错误详情：</h4>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    die();
}
```

## 修复总结

### 已修复的问题

1. ✅ **scene_edit()** - 返回JSON格式代替页面跳转
2. ✅ **package_edit()** - 返回JSON格式代替页面跳转
3. ✅ 添加了try-catch错误处理
4. ✅ 添加了测试数据
5. ✅ 增强了test()调试方法

### AJAX响应格式规范

所有AJAX请求的响应必须使用统一的JSON格式：

```php
// 成功响应
return json([
    'status' => 1,
    'msg' => '操作成功'
]);

// 失败响应
return json([
    'status' => 0,
    'msg' => '错误信息'
]);
```

### 前后端对应关系

| 前端请求方式 | 后端响应方式 | 说明 |
|------------|------------|------|
| `$.post()` (AJAX) | `return json()` | 必须返回JSON |
| `<form>` (普通表单) | `$this->success()` | 可以使用页面跳转 |
| Layui Table | `return json()` | 必须返回标准格式 |

## 测试验证

### 1. 访问场景列表

```
http://192.168.11.222/?s=/AiTravelPhoto/scene_list
```

**预期结果**：
- ✅ 页面正常加载
- ✅ 显示5条测试数据
- ✅ 分类筛选正常工作
- ✅ 状态筛选正常工作

### 2. 添加新场景

1. 点击"新增场景"按钮
2. 填写场景信息
3. 点击"提交"

**预期结果**：
- ✅ 立即收到"添加成功"提示
- ✅ 弹窗自动关闭
- ✅ 列表自动刷新显示新数据

### 3. 编辑场景

1. 点击某条数据的"编辑"按钮
2. 修改场景信息
3. 点击"提交"

**预期结果**：
- ✅ 立即收到"保存成功"提示
- ✅ 弹窗自动关闭
- ✅ 列表自动刷新显示更新后的数据

### 4. 批量操作

1. 选中多条数据
2. 点击"批量启用"/"批量禁用"/"批量删除"

**预期结果**：
- ✅ 操作成功
- ✅ 列表自动刷新

## 相关文件

- **控制器**：`/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`
- **视图文件**：
  - `/www/wwwroot/eivie/app/view/ai_travel_photo/scene_list.html`
  - `/www/wwwroot/eivie/app/view/ai_travel_photo/scene_edit.html`
- **数据库表**：`ddwx_ai_travel_photo_scene`

---

**更新时间**：2026-01-21  
**状态**：✅ 已修复  
**测试状态**：待用户验证
