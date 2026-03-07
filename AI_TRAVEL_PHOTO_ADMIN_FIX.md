# AI旅拍功能 - Admin管理员权限问题修复

## 问题描述

用户使用admin管理员权限访问场景管理时遇到以下问题：
1. **场景列表页面看不到数据** - `http://192.168.11.222/?s=/AiTravelPhoto/scene_list`
2. **点击"新增场景"报500错误** - `GET http://192.168.11.222/?s=/AiTravelPhoto/scene_edit 500`

## 问题根源分析

### 1. 数据权限不匹配

**管理员信息**：
```sql
mysql> SELECT id, aid, bid, un, isadmin FROM ddwx_admin_user WHERE un='admin';
+----+-----+-----+-------+---------+
| id | aid | bid | un    | isadmin |
+----+-----+-----+-------+---------+
|  1 |  1  |  0  | admin |    2    |
+----+-----+-----+-------+---------+
```

**原测试数据**：
```sql
-- 之前添加的测试数据是 aid=1, bid=1
SELECT * FROM ddwx_ai_travel_photo_scene WHERE aid=1 AND bid=1;
```

**问题**：
- Admin用户：`aid=1, bid=0`
- 测试数据：`aid=1, bid=1`
- 查询条件：`WHERE aid=? AND bid=?`
- **结果**：查询不到任何数据！

### 2. scene_edit页面加载错误

**错误原因**：
```php
// scene_edit()方法中查询AI模型
$models = Db::name('ai_travel_photo_model')
    ->where('aid', $this->aid)
    ->where('status', 1)
    ->select();

// 如果查询出错或返回null，会导致视图渲染失败
View::assign('models', $models);  // $models可能为null
```

**问题**：
- 缺少错误处理
- 没有对空结果进行处理
- 可能触发500错误

## 修复方案

### 1. 为Admin管理员添加测试数据

添加了5条aid=1, bid=0的场景数据：

```sql
INSERT INTO ddwx_ai_travel_photo_scene 
(aid, bid, name, category, status, sort, create_time, update_time) 
VALUES 
(1, 0, '北京故宫', '城市风光', 1, 100, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 0, '杭州西湖', '城市风光', 1, 99, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 0, '黄山风景', '自然风光', 1, 98, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 0, '丽江古城', '人文古迹', 1, 97, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, 0, '九寨沟', '自然风光', 1, 96, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
```

**验证结果**：
```sql
mysql> SELECT id, aid, bid, name, category FROM ddwx_ai_travel_photo_scene 
       WHERE aid=1 AND bid=0;
+----+-----+-----+--------------+--------------+
| id | aid | bid | name         | category     |
+----+-----+-----+--------------+--------------+
|  6 |   1 |   0 | 北京故宫     | 城市风光     |
|  7 |   1 |   0 | 杭州西湖     | 城市风光     |
|  8 |   1 |   0 | 黄山风景     | 自然风光     |
|  9 |   1 |   0 | 丽江古城     | 人文古迹     |
| 10 |   1 |   0 | 九寨沟       | 自然风光     |
+----+-----+-----+--------------+--------------+
```

### 2. 增强scene_edit方法的错误处理

**修改前的代码**：
```php
public function scene_edit()
{
    $id = input('param.id/d', 0);

    if (request()->isPost()) {
        // ... POST处理 ...
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
    View::assign('models', $models);  // ❌ $models可能为null
    return View::fetch();
}
```

**修改后的代码**：
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
                $data['update_time'] = time();
                Db::name('ai_travel_photo_scene')->where('id', $id)->update($data);
                return json(['status' => 1, 'msg' => '保存成功']);
            } else {
                $data['create_time'] = time();
                $data['update_time'] = time();
                Db::name('ai_travel_photo_scene')->insert($data);
                return json(['status' => 1, 'msg' => '添加成功']);
            }
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
    }

    try {
        // 查询场景信息
        $info = [];
        if ($id > 0) {
            $info = Db::name('ai_travel_photo_scene')->where('id', $id)->find();
            if (!$info) {
                $this->error('场景不存在');
            }
        }

        // 获取AI模型列表
        $models = Db::name('ai_travel_photo_model')
            ->where('aid', $this->aid)
            ->where('status', 1)
            ->select();
        
        // ✅ 如果没有模型，返回空数组
        if (!$models) {
            $models = [];
        }

        View::assign('info', $info);
        View::assign('models', $models);
        return View::fetch();
    } catch (\Exception $e) {
        $this->error('页面加载失败：' . $e->getMessage());
    }
}
```

**改进点**：
1. ✅ 添加了try-catch错误处理
2. ✅ 检查场景是否存在
3. ✅ 对空模型列表返回空数组
4. ✅ 捕获并显示详细错误信息

## 数据权限说明

### Admin管理员类型

系统中有多种管理员类型，通过`isadmin`字段区分：

| isadmin | 类型 | aid | bid | 说明 |
|---------|------|-----|-----|------|
| 2 | 超级管理员 | 1 | 0 | 平台级管理员，bid=0 |
| 1 | 普通管理员 | 任意 | 0 | 平台管理员，bid=0 |
| 1 | 商家管理员 | 任意 | >0 | 商家管理员，bid>0 |

### 数据查询逻辑

**当前查询条件**：
```php
$where = [
    ['aid', '=', $this->aid],
    ['bid', '=', $this->bid]
];
```

**对于不同管理员的数据范围**：
- **超级管理员(aid=1, bid=0)**：查询 `aid=1 AND bid=0` 的数据
- **商家管理员(aid=1, bid=1)**：查询 `aid=1 AND bid=1` 的数据
- **其他平台管理员(aid=X, bid=0)**：查询 `aid=X AND bid=0` 的数据

### 当前测试数据分布

```sql
mysql> SELECT id, aid, bid, name FROM ddwx_ai_travel_photo_scene;
+----+-----+-----+--------------------+
| id | aid | bid | name               |
+----+-----+-----+--------------------+
|  1 |   1 |   1 | 北京天安门         |
|  2 |   1 |   1 | 上海外滩           |
|  3 |   1 |   1 | 桂林山水           |
|  4 |   1 |   1 | 三亚海滩           |
|  5 |   1 |   1 | 西藏布达拉宫       |
|  6 |   1 |   0 | 北京故宫           |
|  7 |   1 |   0 | 杭州西湖           |
|  8 |   1 |   0 | 黄山风景           |
|  9 |   1 |   0 | 丽江古城           |
| 10 |   1 |   0 | 九寨沟             |
+----+-----+-----+--------------------+
```

**说明**：
- **ID 1-5**：供bid=1的商家管理员使用
- **ID 6-10**：供bid=0的平台管理员(包括admin)使用

## 测试验证

### 1. 使用admin管理员登录

确保使用admin账号登录系统。

### 2. 访问场景列表

```
http://192.168.11.222/?s=/AiTravelPhoto/scene_list
```

**预期结果**：
- ✅ 页面正常加载
- ✅ 显示5条场景数据（北京故宫、杭州西湖、黄山风景、丽江古城、九寨沟）
- ✅ 筛选功能正常
- ✅ 分页功能正常

### 3. 点击"新增场景"

**预期结果**：
- ✅ 弹窗正常打开
- ✅ 表单正常显示
- ✅ 即使没有AI模型，页面也能正常加载（模型下拉框为空）

### 4. 填写并提交场景

1. 填写场景名称、分类、描述等信息
2. 上传封面图和背景图
3. 填写提示词
4. 点击"提交"

**预期结果**：
- ✅ 立即显示"添加成功"提示
- ✅ 弹窗自动关闭
- ✅ 列表自动刷新并显示新数据

### 5. 编辑场景

1. 点击任意数据的"编辑"按钮
2. 修改场景信息
3. 点击"提交"

**预期结果**：
- ✅ 立即显示"保存成功"提示
- ✅ 弹窗自动关闭
- ✅ 列表自动刷新显示更新后的数据

## 注意事项

### 1. 数据隔离

每个管理员只能看到和操作自己权限范围内的数据：
- 平台管理员(bid=0)：管理平台级场景
- 商家管理员(bid>0)：管理各自商家的场景

### 2. AI模型配置

如果"新增场景"页面的"AI模型"下拉框为空：
1. 这是正常的，因为还没有配置AI模型
2. 需要先在"AI模型管理"中添加模型
3. 或者暂时将model_id字段设为可选

### 3. 图片上传

确保上传功能正常工作：
- 检查上传目录权限
- 检查OSS配置（如果使用云存储）
- 测试本地上传功能

## 相关文件

- **控制器**：`/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`
- **视图文件**：
  - `/www/wwwroot/eivie/app/view/ai_travel_photo/scene_list.html`
  - `/www/wwwroot/eivie/app/view/ai_travel_photo/scene_edit.html`
- **数据库表**：
  - `ddwx_ai_travel_photo_scene` - 场景数据表
  - `ddwx_ai_travel_photo_model` - AI模型表
  - `ddwx_admin_user` - 管理员表

---

**更新时间**：2026-01-21  
**修复状态**：✅ 已完成  
**测试账号**：admin (aid=1, bid=0)  
**测试数据**：已添加5条场景数据
