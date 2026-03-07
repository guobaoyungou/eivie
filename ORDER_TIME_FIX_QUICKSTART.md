# 订单创建时间修复 - 快速指南

## 问题
照片生成和视频生成的订单列表显示的创建时间与记录详情页不一致。

## 原因
- 订单创建时间 = 用户下单时间
- 记录创建时间 = 支付成功后生成任务创建时间
- 两者存在时间差（通常几秒到几十秒）

## 解决方案
修改订单列表和详情查询，优先显示生成记录的创建时间。

## 已修改文件
- `/app/service/GenerationOrderService.php`
  - `getOrderList()` - 订单列表查询
  - `getOrderDetail()` - 订单详情查询

## 修改逻辑
```php
// 优先使用生成记录的创建时间，如果没有则使用订单创建时间
$displayTime = $record_create_time ?: $order_createtime;
```

## 验证方法

### 方法1：运行测试脚本
```bash
php test_order_time_fix.php
```

### 方法2：执行SQL验证
```bash
mysql -u用户名 -p密码 数据库名 < verify_order_time_fix.sql
```

### 方法3：界面验证
1. 访问：照片生成订单列表
   - 路径：`PhotoGeneration/order_list`
2. 访问：视频生成订单列表
   - 路径：`VideoGeneration/order_list`
3. 点击订单详情，对比创建时间
4. **预期结果**：列表时间 = 详情时间

## 部署
无需额外操作，代码修改后立即生效。

## 影响范围
- ✅ 照片生成订单管理
- ✅ 视频生成订单管理
- ✅ 前端无需修改
- ✅ 向下兼容（未支付订单仍显示订单时间）

## 注意事项
1. 已支付订单：显示记录创建时间
2. 待支付订单：显示订单创建时间
3. 数据库结构无变化
4. 性能影响可忽略（LEFT JOIN已有索引）

## 相关文档
- 详细报告：`FIX_ORDER_CREATE_TIME.md`
- SQL验证：`verify_order_time_fix.sql`
- 测试脚本：`test_order_time_fix.php`

---
修复时间：2026-03-03
