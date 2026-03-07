---
name: thinkphp-development
description: ThinkPHP 6.0框架开发规范和最佳实践。用于创建控制器、模型、数据库操作、API开发等。遵循MVC架构、命名规范、安全编码。适用于点大商城系统开发。
---

# ThinkPHP 6.0 开发规范

点大商城系统基于ThinkPHP 6.0框架开发，遵循MVC架构模式。

## 快速开始

### 控制器开发
```php
<?php
namespace app\controller;

use think\facade\Db;
use think\facade\View;

class ShopProduct extends Common
{
    public function index() {
        // 获取列表数据
        $list = Db::name('shop_product')
            ->where('aid', aid)
            ->where('status', 1)
            ->select()
            ->toArray();
        
        View::assign('list', $list);
        return View::fetch();
    }
}
```

### 数据库操作
```php
// 查询
$list = Db::name('table')->where('aid', aid)->select();

// 添加
Db::name('table')->insert($data);

// 更新
Db::name('table')->where('id', $id)->update($data);

// 删除
Db::name('table')->where('id', $id)->delete();
```

## 核心工作流程

### 1. 控制器继承关系
```
app\BaseController
    ↓
app\controller\Base (全局常量定义)
    ↓
app\controller\Common (权限验证)
    ↓
具体业务控制器
```

### 2. 全局常量定义
在Base.php中定义：
```php
if (!defined('aid')) {
    define('aid', $this->aid);
}
if (!defined('mid')) {
    define('mid', $this->mid);
}
if (!defined('bid')) {
    define('bid', $this->bid);
}
if (!defined('platform')) {
    define('platform', $this->platform);
}
```

### 3. 数据库查询规范
```php
// ✅ 正确：使用Db门面和参数绑定
$order = Db::name('shop_order')
    ->where('aid', aid)
    ->where('id', $id)
    ->find();

// ❌ 错误：SQL拼接（SQL注入风险）
// $order = Db::query("SELECT * FROM shop_order WHERE id={$id}");

// ✅ 使用全局常量
$list = Db::name('member')
    ->where('aid', aid)
    ->where('bid', bid)
    ->select();
```

### 4. API接口开发
```php
<?php
namespace app\controller;

class ApiShop extends ApiBase
{
    public function list() {
        $aid = input('param.aid/d');
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 10);
        
        $list = Db::name('shop_product')
            ->where('aid', $aid)
            ->where('status', 1)
            ->page($page, $limit)
            ->select()
            ->toArray();
        
        $count = Db::name('shop_product')
            ->where('aid', $aid)
            ->where('status', 1)
            ->count();
        
        return json([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $list,
            'count' => $count
        ]);
    }
}
```

## 重要规则

### 命名规范
- **类名**：大驼峰 `ShopOrder`, `MemberLevel`
- **方法名**：小驼峰 `createOrder()`, `getList()`
- **数据表**：小写+下划线 `shop_order`, `member_level`
- **字段名**：小写+下划线 `create_time`, `total_price`

### 安全规范

**❌ 禁止使用**：
```php
// 1. 禁止eval()函数
eval($code); // 代码注入风险

// 2. 禁止SQL拼接
Db::query("SELECT * FROM member WHERE id={$id}");

// 3. 禁止未定义变量直接使用
echo $undefined_var; // 可能导致错误
```

**✅ 必须做到**：
```php
// 1. 使用isset()检查数组键
if (isset($data['key'])) {
    $value = $data['key'];
}

// 2. 全局常量使用前检查
if (!defined('aid')) {
    define('aid', $this->aid);
}

// 3. 参数过滤和验证
$id = input('param.id/d'); // /d表示强制整型
$name = input('param.name/s'); // /s表示字符串
```

### 数据返回规范

**API接口返回**：
```php
// 成功
return json([
    'status' => 1,
    'msg' => '操作成功',
    'data' => $data
]);

// 失败
return json([
    'status' => 0,
    'msg' => '操作失败'
]);

// 需要登录
return json([
    'status' => -5,
    'msg' => '请重新登录'
]);
```

**后台页面返回**：
```php
// 成功
$this->success('操作成功', url('index'));

// 失败
$this->error('操作失败');

// 返回视图
View::assign('data', $data);
return View::fetch();
```

### ID字段规范

系统核心ID字段：
- `aid` - 平台ID (Admin ID)
- `bid` - 商家ID (Business ID)
- `mid` - 会员ID (Member ID)
- `uid` - 管理员ID (User ID)
- `mdid` - 门店ID (Mendian ID)

**所有业务数据必须关联aid**：
```php
// ✅ 正确
Db::name('shop_order')
    ->where('aid', aid)
    ->where('id', $id)
    ->find();

// ❌ 错误（缺少aid过滤，可能访问其他平台数据）
Db::name('shop_order')
    ->where('id', $id)
    ->find();
```

## 业务逻辑类使用

### Member类（会员业务）
```php
use app\common\Member;

// 会员升级
Member::uplv($aid, $mid, 'shop');

// 余额变动
Member::addmoney($aid, $mid, $money, $remark);

// 积分变动
Member::addscore($aid, $mid, $score, $remark);

// 佣金变动
Member::addcommission($aid, $mid, $commission, $remark);
```

### Business类（商家业务）
```php
use app\common\Business;

// 商家余额变动
Business::addmoney($aid, $bid, $money, $remark);

// 商家积分变动
Business::addscore($aid, $bid, $score, $remark);

// 上级推荐提成
Business::addparentcommission($aid, $bid, $money);
```

## 常见功能实现

### 分页查询
```php
public function index() {
    $page = input('param.page/d', 1);
    $limit = input('param.limit/d', 20);
    
    $list = Db::name('shop_order')
        ->where('aid', aid)
        ->order('id desc')
        ->page($page, $limit)
        ->select()
        ->toArray();
    
    $count = Db::name('shop_order')
        ->where('aid', aid)
        ->count();
    
    return json([
        'status' => 1,
        'data' => $list,
        'count' => $count
    ]);
}
```

### 事务处理
```php
Db::startTrans();
try {
    // 业务操作1
    Db::name('table1')->insert($data1);
    
    // 业务操作2
    Db::name('table2')->update($data2);
    
    Db::commit();
    return json(['status' => 1, 'msg' => '操作成功']);
} catch (\Exception $e) {
    Db::rollback();
    return json(['status' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
}
```

### 文件上传
```php
public function upload() {
    $file = request()->file('image');
    
    if (!$file) {
        return json(['status' => 0, 'msg' => '请选择文件']);
    }
    
    try {
        // 保存到本地
        $savename = \think\facade\Filesystem::disk('public')->putFile('images', $file);
        
        // 如果启用OSS，上传到云存储
        if ($this->admin['oss'] == 1) {
            $url = \app\common\Pic::uploadOSS($savename);
        } else {
            $url = '/upload/' . $savename;
        }
        
        return json([
            'status' => 1,
            'msg' => '上传成功',
            'url' => $url
        ]);
    } catch (\Exception $e) {
        return json(['status' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
    }
}
```

## 检查清单

开发新功能前：
- [ ] 确认控制器继承关系正确
- [ ] 全局常量已定义（aid、mid、bid、platform）
- [ ] 所有数据库查询包含aid过滤
- [ ] 使用参数绑定，避免SQL注入
- [ ] 数组访问前使用isset()检查
- [ ] API返回格式统一（status、msg、data）
- [ ] 敏感操作使用事务
- [ ] 错误处理完整

## 相关文件

- 控制器基类：`app/controller/Base.php`
- 公共控制器：`app/controller/Common.php`
- 会员业务类：`app/common/Member.php`
- 商家业务类：`app/common/Business.php`
- 公共函数库：`app/common.php`

## 下一步

开发完成后，应该：
1. 运行测试确保功能正常
2. 检查代码安全性
3. 提交代码评审
4. 更新相关文档
