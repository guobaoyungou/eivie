---
name: multi-platform-api-development
description: 多平台API开发规范（微信、支付宝、H5、APP等）。用于创建前端API接口、处理多端适配、平台识别、会员认证。适用于ApiShop、ApiOrder、ApiMy等控制器开发。
---

# 多平台API开发规范

点大商城支持多个前端平台：微信公众号、小程序（微信/支付宝/百度/头条/QQ）、H5、APP。

## 快速开始

### 创建API控制器
```php
<?php
namespace app\controller;

use think\facade\Db;

class ApiProduct extends ApiBase
{
    public function list() {
        // 平台参数自动识别（在ApiBase中处理）
        // aid, platform 已定义
        
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 10);
        
        // 查询商品列表
        $list = Db::name('shop_product')
            ->where('aid', aid)
            ->where('status', 1)
            ->page($page, $limit)
            ->select()
            ->toArray();
        
        $count = Db::name('shop_product')
            ->where('aid', aid)
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

## 核心工作流程

### 1. 平台识别机制

**支持的平台**：
- `mp` - 微信公众号
- `wx` - 微信小程序
- `alipay` - 支付宝小程序
- `baidu` - 百度小程序
- `toutiao` - 头条小程序
- `qq` - QQ小程序
- `h5` - H5网页
- `app` - APP应用

**识别流程**（在ApiBase.php中自动处理）：
```php
// 1. 获取platform参数
$platform = input('param.platform');

// 2. 验证平台合法性
if ($platform && !in_array($platform, ['mp','wx','alipay','baidu','toutiao','qq','h5','app'])) {
    die(jsonEncode(['status'=>0,'msg'=>'参数错误']));
}

// 3. 自动识别（如果未传platform）
if (!$platform) {
    if (is_weixin()) {
        $platform = 'mp';
    } else {
        $platform = 'h5';
    }
}

// 4. 定义全局常量
define('platform', $platform);
```

### 2. 请求参数规范

**必传参数**：
```
aid - 平台ID（整数）
```

**可选参数**：
```
platform - 平台类型（mp/wx/alipay/baidu/toutiao/qq/h5/app）
mid - 会员ID（需要登录的接口必传）
```

**请求示例**：
```
GET /api/product/list?aid=1&platform=wx&page=1&limit=10
POST /api/order/create?aid=1&mid=123&platform=wx
```

### 3. 响应格式规范

**成功响应**：
```json
{
  "status": 1,
  "msg": "操作成功",
  "data": {
    // 业务数据
  }
}
```

**失败响应**：
```json
{
  "status": 0,
  "msg": "错误信息"
}
```

**需要登录**：
```json
{
  "status": -5,
  "msg": "请重新登录",
  "url": "登录页面URL"
}
```

**列表数据**：
```json
{
  "status": 1,
  "msg": "获取成功",
  "data": [
    // 数据数组
  ],
  "count": 100  // 总数
}
```

### 4. 会员认证机制

**获取会员信息**：
```php
public function getMemberInfo() {
    $mid = input('param.mid/d');
    
    if (!$mid) {
        return json(['status' => 0, 'msg' => '请先登录']);
    }
    
    $member = Db::name('member')
        ->where('id', $mid)
        ->where('aid', aid)
        ->find();
    
    if (!$member) {
        return json(['status' => -5, 'msg' => '会员不存在']);
    }
    
    return json([
        'status' => 1,
        'data' => $member
    ]);
}
```

**需要登录的接口**：
```php
public function myOrders() {
    $mid = input('param.mid/d');
    
    if (!$mid) {
        return json(['status' => -5, 'msg' => '请先登录']);
    }
    
    // 查询订单
    $orders = Db::name('shop_order')
        ->where('aid', aid)
        ->where('mid', $mid)
        ->select()
        ->toArray();
    
    return json([
        'status' => 1,
        'data' => $orders
    ]);
}
```

## 常见API模块

### 商品API (ApiShop.php)

```php
// 商品列表
public function index() {
    $cid = input('param.cid/d', 0);
    $keyword = input('param.keyword', '');
    $page = input('param.page/d', 1);
    $limit = input('param.limit/d', 10);
    
    $where = [
        ['aid', '=', aid],
        ['status', '=', 1]
    ];
    
    if ($cid > 0) {
        $where[] = ['cid', '=', $cid];
    }
    
    if ($keyword) {
        $where[] = ['name', 'like', '%'.$keyword.'%'];
    }
    
    $list = Db::name('shop_product')
        ->where($where)
        ->order('sort desc, id desc')
        ->page($page, $limit)
        ->select()
        ->toArray();
    
    $count = Db::name('shop_product')->where($where)->count();
    
    return json([
        'status' => 1,
        'data' => $list,
        'count' => $count
    ]);
}

// 商品详情
public function detail() {
    $id = input('param.id/d');
    
    if (!$id) {
        return json(['status' => 0, 'msg' => '参数错误']);
    }
    
    $product = Db::name('shop_product')
        ->where('aid', aid)
        ->where('id', $id)
        ->find();
    
    if (!$product) {
        return json(['status' => 0, 'msg' => '商品不存在']);
    }
    
    // 增加浏览量
    Db::name('shop_product')
        ->where('id', $id)
        ->inc('views')
        ->update();
    
    return json([
        'status' => 1,
        'data' => $product
    ]);
}
```

### 订单API (ApiOrder.php)

```php
// 创建订单
public function create() {
    $mid = input('param.mid/d');
    $goods = input('param.goods/a'); // 商品数组
    
    if (!$mid) {
        return json(['status' => -5, 'msg' => '请先登录']);
    }
    
    if (!$goods) {
        return json(['status' => 0, 'msg' => '请选择商品']);
    }
    
    Db::startTrans();
    try {
        // 创建订单
        $orderData = [
            'aid' => aid,
            'mid' => $mid,
            'ordernum' => date('YmdHis') . rand(1000, 9999),
            'totalprice' => 0,
            'status' => 0,
            'createtime' => time()
        ];
        
        $orderId = Db::name('shop_order')->insertGetId($orderData);
        
        // 添加订单商品
        $totalPrice = 0;
        foreach ($goods as $item) {
            Db::name('shop_order_goods')->insert([
                'orderid' => $orderId,
                'productid' => $item['id'],
                'num' => $item['num'],
                'price' => $item['price']
            ]);
            
            $totalPrice += $item['price'] * $item['num'];
        }
        
        // 更新订单总价
        Db::name('shop_order')
            ->where('id', $orderId)
            ->update(['totalprice' => $totalPrice]);
        
        Db::commit();
        
        return json([
            'status' => 1,
            'msg' => '订单创建成功',
            'data' => ['orderid' => $orderId]
        ]);
        
    } catch (\Exception $e) {
        Db::rollback();
        return json(['status' => 0, 'msg' => '订单创建失败：' . $e->getMessage()]);
    }
}

// 订单列表
public function list() {
    $mid = input('param.mid/d');
    $status = input('param.status', '');
    $page = input('param.page/d', 1);
    $limit = input('param.limit/d', 10);
    
    if (!$mid) {
        return json(['status' => -5, 'msg' => '请先登录']);
    }
    
    $where = [
        ['aid', '=', aid],
        ['mid', '=', $mid]
    ];
    
    if ($status !== '') {
        $where[] = ['status', '=', $status];
    }
    
    $list = Db::name('shop_order')
        ->where($where)
        ->order('id desc')
        ->page($page, $limit)
        ->select()
        ->toArray();
    
    // 查询订单商品
    foreach ($list as &$order) {
        $order['goods'] = Db::name('shop_order_goods')
            ->where('orderid', $order['id'])
            ->select()
            ->toArray();
    }
    
    $count = Db::name('shop_order')->where($where)->count();
    
    return json([
        'status' => 1,
        'data' => $list,
        'count' => $count
    ]);
}
```

### 个人中心API (ApiMy.php)

```php
// 个人信息
public function index() {
    $mid = input('param.mid/d');
    
    if (!$mid) {
        return json(['status' => -5, 'msg' => '请先登录']);
    }
    
    $member = Db::name('member')
        ->where('aid', aid)
        ->where('id', $mid)
        ->find();
    
    if (!$member) {
        return json(['status' => 0, 'msg' => '会员不存在']);
    }
    
    // 统计数据
    $stats = [
        'orderCount' => Db::name('shop_order')
            ->where('aid', aid)
            ->where('mid', $mid)
            ->count(),
        'couponCount' => Db::name('coupon_member')
            ->where('aid', aid)
            ->where('mid', $mid)
            ->where('status', 0)
            ->count()
    ];
    
    return json([
        'status' => 1,
        'data' => [
            'member' => $member,
            'stats' => $stats
        ]
    ]);
}
```

## 重要规则

### ✅ 必须做到

1. **继承ApiBase类**
```php
class ApiProduct extends ApiBase
{
    // 所有API控制器必须继承ApiBase
}
```

2. **使用全局常量**
```php
// 使用aid常量（在ApiBase中已定义）
Db::name('member')->where('aid', aid)->find();

// 使用platform常量
if (platform == 'wx') {
    // 微信小程序特殊处理
}
```

3. **参数验证**
```php
// 整数参数使用/d强制转换
$id = input('param.id/d');

// 字符串参数使用/s
$name = input('param.name/s');

// 数组参数使用/a
$goods = input('param.goods/a');
```

4. **统一返回格式**
```php
// 所有接口统一使用json()返回
return json([
    'status' => 1,
    'msg' => '操作成功',
    'data' => $data
]);
```

5. **数据隔离**
```php
// 所有查询必须带aid过滤
Db::name('shop_order')
    ->where('aid', aid)  // ✅ 必须
    ->where('id', $id)
    ->find();
```

### ❌ 禁止行为

1. **不要跳过平台验证**
```php
// ❌ 错误：直接使用未验证的platform
$platform = input('param.platform');
define('platform', $platform);

// ✅ 正确：在ApiBase中已经验证
// 直接使用platform常量即可
```

2. **不要暴露敏感信息**
```php
// ❌ 错误：返回密码等敏感字段
return json([
    'status' => 1,
    'data' => $member  // 包含password字段
]);

// ✅ 正确：移除敏感字段
unset($member['password']);
return json([
    'status' => 1,
    'data' => $member
]);
```

3. **不要忽略事务**
```php
// ❌ 错误：多个数据库操作不使用事务
Db::name('table1')->insert($data1);
Db::name('table2')->update($data2);

// ✅ 正确：使用事务
Db::startTrans();
try {
    Db::name('table1')->insert($data1);
    Db::name('table2')->update($data2);
    Db::commit();
} catch (\Exception $e) {
    Db::rollback();
}
```

## 平台特殊处理

### 微信小程序
```php
if (platform == 'wx') {
    // 微信小程序特殊逻辑
    // 例如：获取手机号、支付等
}
```

### H5页面
```php
if (platform == 'h5') {
    // H5特殊逻辑
    // 例如：分享配置等
}
```

### 支付宝小程序
```php
if (platform == 'alipay') {
    // 支付宝小程序特殊逻辑
}
```

## 检查清单

开发API接口前：
- [ ] 控制器继承ApiBase
- [ ] 参数验证完整
- [ ] 使用全局常量（aid、platform、mid）
- [ ] 统一返回格式（status、msg、data）
- [ ] 数据查询包含aid过滤
- [ ] 敏感操作使用事务
- [ ] 移除敏感字段
- [ ] 错误处理完整

## 测试命令

```bash
# 使用curl测试API
curl "http://localhost/api/product/list?aid=1&platform=wx&page=1&limit=10"

# POST请求
curl -X POST "http://localhost/api/order/create?aid=1&mid=123" \
  -d "goods=[{\"id\":1,\"num\":1,\"price\":100}]"
```

## 相关文件

- API基类：`app/controller/ApiBase.php`
- 商品API：`app/controller/ApiShop.php`
- 订单API：`app/controller/ApiOrder.php`
- 个人中心API：`app/controller/ApiMy.php`
- 支付API：`app/controller/ApiPay.php`

## 下一步

API开发完成后，应该：
1. 在前端项目中调用测试
2. 检查接口性能
3. 编写接口文档
4. 进行安全测试
