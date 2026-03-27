# SQL 字段错误修复记录

## 错误信息
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'goods_id' in 'field list'
```

## 错误原因

在 `app/controller/ApiUnifiedOrder.php` 文件中，处理 AI 旅拍选片订单时，代码错误地使用了 `goods_id` 字段，但该字段在 `ai_travel_photo_order_goods` 数据表中**不存在**。

### 数据表实际字段

`ai_travel_photo_order_goods` 表的实际字段：

```sql
CREATE TABLE IF NOT EXISTS `ddwx_ai_travel_photo_order_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT 0,
  `order_id` int(11) NOT NULL DEFAULT 0,
  `order_no` varchar(32) DEFAULT NULL,
  `result_id` int(11) NOT NULL DEFAULT 0,     -- ✅ 正确字段
  `type` tinyint(1) DEFAULT 1,
  `goods_name` varchar(255) DEFAULT NULL,
  `goods_image` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `num` int(11) DEFAULT 1,                    -- ✅ 正确字段
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**注意**：
- ❌ 表中**没有** `goods_id` 字段
- ❌ 表中**没有** `quantity` 字段
- ✅ 应该使用 `result_id` 字段（AI 生成结果ID）
- ✅ 应该使用 `num` 字段（数量）

## 修复内容

### 文件：`app/controller/ApiUnifiedOrder.php`

**修复位置 1：第 172 行 - `getAiPickOrderDetail()` 方法**

```php
// 修改前 ❌
'proid' => $goods['goods_id'],
'num' => $goods['quantity'] ?? 1,

// 修改后 ✅
'proid' => $goods['result_id'] ?? $goods['id'],
'num' => $goods['num'] ?? 1,
```

**修复位置 2：第 877 行 - `normalizeAiPickOrder()` 方法的查询字段**

```php
// 修改前 ❌
->field('id,order_id,goods_id,goods_name,goods_image,quantity,price')

// 修改后 ✅
->field('id,order_id,result_id,goods_name,goods_image,num,price')
```

**修复位置 3：第 912 行 - `normalizeAiPickOrder()` 方法的数据映射**

```php
// 修改前 ❌
'proid' => $goods['goods_id'],
'num' => $goods['quantity'] ?? 1,

// 修改后 ✅
'proid' => $goods['result_id'] ?? $goods['id'],
'num' => $goods['num'] ?? 1,
```

## 修复效果

修复后，统一订单接口能够正确查询 AI 旅拍选片订单数据，不再出现 SQL 字段不存在错误。

## 技术说明

### 为什么使用 `result_id`？

在 AI 旅拍选片业务中：
- `result_id` 关联到 `ai_travel_photo_result` 表，表示 AI 生成的旅拍结果
- 每个订单商品对应一个 AI 生成的结果
- `result_id` 是商品的实际标识符，符合业务逻辑

### 兜底处理

代码中使用了 `$goods['result_id'] ?? $goods['id']` 的兜底逻辑：
- 优先使用 `result_id`（正常情况）
- 如果 `result_id` 为空，使用商品记录自身的 `id`（异常情况兜底）

## 测试建议

修复后请测试以下场景：

1. **统一订单列表**
   - 访问：`/pagesExt/order/unifiedOrderlist`
   - 筛选选片订单类型
   - 验证是否正常显示

2. **选片订单详情**
   - 点击选片订单进入详情页
   - 验证商品信息是否正确显示

3. **订单数量统计**
   - 检查各状态下的选片订单计数是否准确

## 修复日期
2026-03-27

## 相关文档
- [统一订单管理实现说明.md](/home/www/ai.eivie.cn/统一订单管理实现说明.md)
- [商城订单列表页问题排查记录.md](/home/www/ai.eivie.cn/商城订单列表页问题排查记录.md)
