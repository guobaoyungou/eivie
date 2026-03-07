# 照片/视频生成订单创建时间修复报告

## 问题描述

照片生成记录和视频生成记录的订单列表显示的创建时间与记录详情页的创建时间不一致。

### 问题根源

1. **订单表** (`ddwx_generation_order`) 的 `createtime` 字段记录的是订单创建时间
2. **生成记录表** (`ddwx_generation_record`) 的 `create_time` 字段记录的是生成任务创建时间
3. 由于订单创建后需要支付，支付成功后才会创建生成记录，所以：
   - 订单的 `createtime` = 订单创建时间（支付前）
   - 记录的 `create_time` = 生成任务创建时间（支付后）
   - **两者存在时间差**

### 业务流程

```
用户选择场景 → 创建订单(createtime) → 支付 → 创建生成记录(create_time) → 生成任务
```

## 修复方案

根据用户需求"以记录详情页的创建时间为准"，修改订单列表和订单详情，优先显示生成记录的创建时间。

### 修改文件

**文件：** `/app/service/GenerationOrderService.php`

### 修改内容

#### 1. `getOrderList()` 方法

**修改前：**
```php
$list = Db::name('generation_order')
    ->alias('o')
    ->leftJoin('member m', 'o.mid = m.id')
    ->leftJoin('generation_scene_template t', 'o.scene_id = t.id')
    ->field('o.*, m.nickname, m.headimg, m.tel as member_tel, t.template_name, t.cover_image')
    ->where($where)
    ->page($page, $limit)
    ->order($order)
    ->select()
    ->toArray();

// 格式化数据
foreach ($list as &$item) {
    $item['createtime_text'] = $item['createtime'] ? date('Y-m-d H:i:s', $item['createtime']) : '-';
    // ...
}
```

**修改后：**
```php
$list = Db::name('generation_order')
    ->alias('o')
    ->leftJoin('member m', 'o.mid = m.id')
    ->leftJoin('generation_scene_template t', 'o.scene_id = t.id')
    ->leftJoin('generation_record r', 'o.record_id = r.id')  // ✅ 新增：关联生成记录表
    ->field('o.*, m.nickname, m.headimg, m.tel as member_tel, t.template_name, t.cover_image, r.create_time as record_create_time')  // ✅ 新增：查询记录创建时间
    ->where($where)
    ->page($page, $limit)
    ->order($order)
    ->select()
    ->toArray();

// 格式化数据
foreach ($list as &$item) {
    // ✅ 优先使用生成记录的创建时间，如果没有则使用订单创建时间
    $displayTime = $item['record_create_time'] ?: $item['createtime'];
    $item['createtime_text'] = $displayTime ? date('Y-m-d H:i:s', $displayTime) : '-';
    // ...
}
```

#### 2. `getOrderDetail()` 方法

**修改前：**
```php
$order = Db::name('generation_order')
    ->alias('o')
    ->leftJoin('member m', 'o.mid = m.id')
    ->leftJoin('generation_scene_template t', 'o.scene_id = t.id')
    ->field('o.*, m.nickname, m.headimg, m.tel as member_tel, t.template_name, t.cover_image, t.description as scene_description')
    ->where($where)
    ->find();

// 格式化
$order['createtime_text'] = $order['createtime'] ? date('Y-m-d H:i:s', $order['createtime']) : '-';
```

**修改后：**
```php
$order = Db::name('generation_order')
    ->alias('o')
    ->leftJoin('member m', 'o.mid = m.id')
    ->leftJoin('generation_scene_template t', 'o.scene_id = t.id')
    ->leftJoin('generation_record r', 'o.record_id = r.id')  // ✅ 新增：关联生成记录表
    ->field('o.*, m.nickname, m.headimg, m.tel as member_tel, t.template_name, t.cover_image, t.description as scene_description, r.create_time as record_create_time')  // ✅ 新增：查询记录创建时间
    ->where($where)
    ->find();

// ✅ 格式化，优先使用生成记录的创建时间
$displayTime = $order['record_create_time'] ?: $order['createtime'];
$order['createtime_text'] = $displayTime ? date('Y-m-d H:i:s', $displayTime) : '-';
```

## 修复逻辑

1. **LEFT JOIN 关联生成记录表**：通过 `o.record_id = r.id` 关联
2. **查询记录的 create_time**：作为 `record_create_time` 字段返回
3. **优先使用记录创建时间**：
   - 如果有生成记录（`record_create_time` 存在），显示记录的创建时间
   - 如果没有生成记录（订单未支付或支付失败），显示订单的创建时间
4. **向下兼容**：确保没有生成记录的订单也能正常显示时间

## 影响范围

### 涉及控制器

1. **PhotoGeneration.php**
   - `order_list()` - 照片生成订单列表
   - `order_detail()` - 照片生成订单详情

2. **VideoGeneration.php**
   - `order_list()` - 视频生成订单列表
   - `order_detail()` - 视频生成订单详情

### 涉及视图

1. `/app/view/photo_generation/order_list.html` - 照片生成订单列表页
2. `/app/view/video_generation/order_list.html` - 视频生成订单列表页

两个视图都使用 `createtime_text` 字段显示创建时间，无需修改前端代码。

## 验证方法

### SQL 验证查询

```sql
-- 查看订单创建时间 vs 记录创建时间的差异
SELECT 
    o.id AS order_id,
    o.ordernum,
    o.generation_type,
    FROM_UNIXTIME(o.createtime) AS order_create_time,
    FROM_UNIXTIME(r.create_time) AS record_create_time,
    TIMESTAMPDIFF(SECOND, FROM_UNIXTIME(o.createtime), FROM_UNIXTIME(r.create_time)) AS time_diff_seconds,
    o.pay_status,
    o.task_status
FROM ddwx_generation_order o
LEFT JOIN ddwx_generation_record r ON o.record_id = r.id
WHERE o.status = 1
ORDER BY o.id DESC
LIMIT 20;
```

### 预期结果

- **订单列表**：显示的创建时间应该与记录详情页的创建时间一致
- **待支付订单**：如果没有生成记录，显示订单创建时间（作为兜底）
- **已支付订单**：显示生成记录的创建时间

## 兼容性说明

- ✅ **向下兼容**：未支付订单仍然显示订单创建时间
- ✅ **数据完整性**：不修改数据库结构，只调整查询逻辑
- ✅ **性能影响**：LEFT JOIN 不会显著影响性能（已有索引）
- ✅ **前端兼容**：视图无需修改，继续使用 `createtime_text`

## 部署说明

1. **无需数据库迁移**：只修改了 PHP 代码
2. **无需清缓存**：不涉及模板缓存
3. **即时生效**：代码部署后立即生效

## 测试检查清单

- [ ] 照片生成订单列表 - 创建时间显示正确
- [ ] 视频生成订单列表 - 创建时间显示正确
- [ ] 照片生成订单详情 - 创建时间与列表一致
- [ ] 视频生成订单详情 - 创建时间与列表一致
- [ ] 待支付订单（无记录）- 显示订单创建时间
- [ ] 已支付订单（有记录）- 显示记录创建时间
- [ ] 时间排序功能正常
- [ ] 时间筛选功能正常

## 完成时间

2026-03-03

## 备注

此修复确保了订单管理界面和记录详情页显示的创建时间一致，提升了用户体验和数据的一致性。对于已支付并生成的订单，显示的是实际生成任务创建的时间；对于未支付或未生成的订单，则显示订单本身的创建时间作为兜底。
