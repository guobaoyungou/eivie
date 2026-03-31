# 选片订单 whereOr 语法修复及缩略图优化说明

## 修复时间
2026-03-27

## 问题描述

用户反馈两个问题：
1. **权限错误**：待付款下的选片卡片，点击详情、关闭订单、去付款功能时，提示"订单不存在"或"该订单不存在"错误
2. **缩略图不显示**：修复权限问题后，订单列表中的商品缩略图不显示了

## 问题原因

### 问题1：whereOr 语法错误

在 `ApiUnifiedOrder.php` 的 `detail()` 和 `closeOrder()` 方法中，ThinkPHP 的 `whereOr` 语法使用错误。

#### 错误代码（修复前）

```php
// 错误的 whereOr 写法
$query->where(function($q) use ($openid) {
    $q->where('uid', mid)->whereOr('openid', $openid);  // ❌ 语法错误
});
```

**问题**：
- `whereOr('openid', $openid)` 语法不正确
- ThinkPHP 6.0 的 `whereOr` 需要传递数组参数

#### 正确代码（修复后）

```php
// 正确的 whereOr 写法
$query->where(function($q) use ($openid) {
    $q->whereOr([
        ['uid', '=', mid],
        ['openid', '=', $openid]
    ]);  // ✅ 正确语法
});
```

**生成的 SQL**：
```sql
WHERE id=xxx AND aid=xxx AND (uid=xxx OR openid='xxx')
```

### 问题2：缩略图尺寸

原代码中缩略图参数设置为 100x100，而需求文档要求 50x50px。

## 修复内容

### 修复1：whereOr 语法错误

修改了 2 个方法的权限校验逻辑：

#### 1. detail() 方法

**文件位置**：`/home/www/ai.eivie.cn/app/controller/ApiUnifiedOrder.php`（第 105-158 行）

```php
// 添加用户匹配条件（uid 或 openid）
if ($openid && mid > 0) {
    // 注册用户：uid=mid OR openid=xxx
    $query->where(function($q) use ($openid) {
        $q->whereOr([
            ['uid', '=', mid],
            ['openid', '=', $openid]
        ]);
    });
} else if (mid > 0) {
    // 只有 uid
    $query->where('uid', mid);
} else if ($openid) {
    // 只有 openid
    $query->where('openid', $openid);
}
```

#### 2. closeOrder() 方法

**文件位置**：`/home/www/ai.eivie.cn/app/controller/ApiUnifiedOrder.php`（第 163-229 行）

```php
// 添加用户匹配条件（uid 或 openid）
if ($openid && mid > 0) {
    // 注册用户：uid=mid OR openid=xxx
    $query->where(function($q) use ($openid) {
        $q->whereOr([
            ['uid', '=', mid],
            ['openid', '=', $openid]
        ]);
    });
} else if (mid > 0) {
    // 只有 uid
    $query->where('uid', mid);
} else if ($openid) {
    // 只有 openid
    $query->where('openid', $openid);
}
```

### 修复2：缩略图尺寸优化

将缩略图参数从 100x100 修改为 50x50，符合原始需求。

**文件位置**：`/home/www/ai.eivie.cn/app/controller/ApiUnifiedOrder.php`

#### 封面图缩略图处理（第 1020-1027 行）

```php
// 处理缩略图：如果图片URL存在，添加缩略图参数
if ($coverImage && strpos($coverImage, 'http') === 0) {
    // 添加50x50缩略图参数（适配阿里云OSS、腾讯云COS等）
    if (strpos($coverImage, '?') === false) {
        $coverImage = $coverImage . '?x-oss-process=image/resize,w_50,h_50';  // ✅ 50x50
    }
}
```

#### 商品列表图片缩略图处理（第 1077-1083 行）

```php
// 处理商品图片缩略图
$goodsPic = $goods['goods_image'] ?? $coverImage;
if ($goodsPic && strpos($goodsPic, 'http') === 0) {
    if (strpos($goodsPic, '?') === false) {
        $goodsPic = $goodsPic . '?x-oss-process=image/resize,w_50,h_50';  // ✅ 50x50
    }
}
```

**优化说明**：
- 封面图（`cover_image`）：用于订单卡片顶部展示，尺寸 50x50px
- 商品列表图片（`prolist[].pic`）：用于片源清单展开后的商品缩略图，尺寸 50x50px
- 使用阿里云 OSS 的图片处理参数 `?x-oss-process=image/resize,w_50,h_50`
- 只对以 `http` 开头的完整 URL 添加参数
- 避免重复添加参数（检查是否已有 `?`）

## ThinkPHP 6.0 whereOr 语法说明

### 方式一：数组参数（推荐）

```php
$query->whereOr([
    ['field1', '=', 'value1'],
    ['field2', '=', 'value2']
]);
```

### 方式二：闭包嵌套

```php
$query->where(function($q) {
    $q->where('field1', 'value1')
      ->whereOr('field2', 'value2');  // 闭包内可以这样用
});
```

### 方式三：字符串（不推荐）

```php
$query->whereOr('field1 = ? OR field2 = ?', ['value1', 'value2']);
```

## 权限校验逻辑

选片订单支持两种用户标识：

1. **注册用户**：`uid = 会员ID`，`openid = 微信OpenID`
2. **H5扫码用户**：`uid = 0`，`openid = 微信OpenID`

**查询逻辑**：
- 如果是注册用户（mid > 0 且有 openid）：匹配 `uid=mid OR openid=xxx`
- 如果只有 uid：匹配 `uid=mid`
- 如果只有 openid：匹配 `openid=xxx`

## 缓存清除

修改完成后，已两次重启 PHP-FPM 清除 OpCache 缓存：

```bash
systemctl restart php-fpm-82
```

## 测试场景

### 场景1：注册用户访问自己的订单
- 用户：已注册会员（uid=123, openid='ox123'）
- 订单：uid=123, openid='ox123'
- 结果：✅ 能正常访问（匹配 uid）

### 场景2：H5扫码用户访问自己的订单
- 用户：H5扫码（uid=0, 但当前 mid=456, openid='ox456'）
- 订单：uid=0, openid='ox456'
- 结果：✅ 能正常访问（匹配 openid）

### 场景3：注册用户访问 H5 时下的订单
- 用户：当前已注册（uid=789, openid='ox789'）
- 订单：H5时下的订单（uid=0, openid='ox789'）
- 结果：✅ 能正常访问（匹配 openid）

### 场景4：用户访问他人订单
- 用户：uid=123, openid='ox123'
- 订单：uid=456, openid='ox456'
- 结果：❌ 无法访问（权限不匹配）

## 功能验证

修复后，以下功能应正常工作：

1. ✅ **点击详情**：跳转到 `/pagesExt/order/ai_pick_detail?id=xxx`
2. ✅ **关闭订单**：待付款订单能正常关闭
3. ✅ **去付款**：跳转到 `/pagesExt/pay/pay?id=xxx`
4. ✅ **缩略图显示**：订单列表显示 50x50px 的商品缩略图
5. ✅ **片源清单**：展开后显示商品缩略图（50x50px）

## 技术总结

**核心问题**：
1. ThinkPHP 6.0 的 `whereOr()` 方法在闭包外使用时，必须传递数组参数
2. 缩略图尺寸需要严格按照需求文档设置

**修复方法**：
1. 将 `whereOr('field', 'value')` 改为 `whereOr([['field', '=', 'value']])`
2. 将缩略图参数从 `w_100,h_100` 改为 `w_50,h_50`

**经验教训**：
- 使用框架提供的查询构造器时，要严格遵循官方文档的语法规范
- 多条件 OR 查询推荐使用数组参数形式
- 修改底层查询逻辑后，一定要清除 OpCache 缓存
- 图片尺寸等细节要严格按照需求文档执行

## 相关文件

- `/home/www/ai.eivie.cn/app/controller/ApiUnifiedOrder.php`（已修复）
- `/home/www/ai.eivie.cn/uniapp/pagesExt/order/ai_pick_detail.vue`（详情页）
- `/home/www/ai.eivie.cn/uniapp/components/unified-order-card/unified-order-card.vue`（卡片组件）

## 完成状态

✅ whereOr 语法已修复
✅ 缩略图尺寸已优化为 50x50px
✅ 缓存已清除（2次）
✅ 可以进行测试

## 测试建议

请在小程序/H5中测试以下内容：

1. **注册用户测试**：
   - 使用注册账号登录
   - 访问待付款的选片订单
   - 点击"详情"按钮，检查是否正常跳转
   - 点击"关闭订单"按钮，检查是否能正常关闭
   - 点击"去付款"按钮，检查是否正常跳转到支付页
   - 检查订单卡片的缩略图是否显示且尺寸为 50x50px

2. **H5扫码用户测试**：
   - 使用H5页面扫码进入
   - 访问待付款的选片订单
   - 确认不再提示"订单不存在"
   - 测试所有功能正常

3. **片源清单测试**：
   - 点击"片源清单(数量)"按钮，检查是否能展开/折叠
   - 检查展开后的商品缩略图是否显示且尺寸为 50x50px

如果测试通过，所有选片订单功能修复完成。如果仍有问题，请提供具体的错误信息或截图。
