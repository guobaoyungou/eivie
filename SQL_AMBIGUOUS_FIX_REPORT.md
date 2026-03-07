# SQL字段歧义错误修复报告

## 问题描述
```
SQLSTATE[23000]: Integrity constraint violation: 1052 Column 'aid' in where clause is ambiguous
```

## 问题原因
在多表LEFT JOIN查询中，多个表都包含相同的字段名（aid、bid），WHERE条件中未指定表别名前缀，导致数据库无法确定使用哪个表的字段。

## 涉及文件
- `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`

## 修复内容

### 1. model_config_list方法（第2819行）

**问题SQL：**
```php
$where = [
    ['aid', '=', $this->aid],      // ❌ 无表前缀
    ['bid', '=', $this->bid]       // ❌ 无表前缀
];

$list = Db::name('ai_travel_photo_model')
    ->alias('m')
    ->leftJoin('ai_model_category c', 'm.category_code = c.code')
    ->leftJoin('mendian d', 'm.mdid = d.id')  // mendian表也有aid、bid字段
    ->where($where)  // ❌ 导致字段歧义
```

**修复后：**
```php
$where = [
    ['m.aid', '=', $this->aid],    // ✓ 添加表别名前缀
    ['m.bid', '=', $this->bid]     // ✓ 添加表别名前缀
];

// 所有筛选条件都加上表别名
if ($category_code) {
    $where[] = ['m.category_code', '=', $category_code];
}
if ($mdid !== '') {
    $where[] = ['m.mdid', '=', $mdid];
}
if ($status !== '') {
    $where[] = ['m.status', '=', $status];
}

// count查询也需要加表别名
$count = Db::name('ai_travel_photo_model')
    ->alias('m')
    ->where($where)
    ->count();
```

### 2. model_usage_stats方法（第3116行）

**问题SQL：**
```php
$where = [
    ['aid', '=', $this->aid],      // ❌ 无表前缀
    ['bid', '=', $this->aid]       // ❌ 无表前缀
];

// 在type='list'时使用了LEFT JOIN
$list = Db::name('ai_model_usage_log')
    ->alias('l')
    ->leftJoin('ai_travel_photo_model m', 'l.model_id = m.id')  // m表也有aid、bid
    ->leftJoin('ai_model_category c', 'l.category_code = c.code')
    ->where($where)  // ❌ 导致字段歧义
```

**修复后：**
```php
// 根据查询类型决定表别名前缀
$prefix = ($type == 'list') ? 'l.' : '';

$where = [
    [$prefix . 'aid', '=', $this->aid],    // ✓ 动态添加前缀
    [$prefix . 'bid', '=', $this->bid]     // ✓ 动态添加前缀
];

// 筛选条件也加上表别名
if ($category_code) {
    $where[] = ['l.category_code', '=', $category_code];
}
if ($business_type) {
    $where[] = ['l.business_type', '=', $business_type];
}
if ($status !== '') {
    $where[] = ['l.status', '=', $status];
}

// count查询也需要使用表别名
$count = Db::name('ai_model_usage_log')
    ->alias('l')
    ->where($where)
    ->count();
```

## 技术规范

根据记忆知识：**多表JOIN查询字段命名规范**
> 在多表LEFT JOIN查询中，若多个关联表存在同名字段（如aid、bid），WHERE条件中必须为该字段显式添加表别名前缀（如s.aid），禁止直接使用无前缀的字段名，否则将触发SQLSTATE[23000]: Integrity constraint violation: 1052列歧义错误。

## 修复验证

✅ **测试结果：**
```
=== 模型管理页面访问测试 ===

测试: 模型分类列表      ✓ 状态: 正常 (HTTP 200)
测试: API配置列表        ✓ 状态: 正常 (HTTP 200)
测试: 调用统计          ✓ 状态: 正常 (HTTP 200)
测试: 模型分类编辑      ✓ 状态: 正常 (HTTP 200)
测试: API配置编辑        ✓ 状态: 正常 (HTTP 200)

=== 测试结果 ===
成功: 5个页面
失败: 0个页面

✓ 所有页面访问正常！
```

## 影响范围
- API配置列表数据加载
- 调用统计明细列表
- 所有涉及多表联查的筛选功能

## 预防措施
在编写多表JOIN查询时：
1. **始终使用表别名**（如`->alias('m')`）
2. **WHERE条件必须加表前缀**（如`['m.aid', '=', $value]`）
3. **字段选择也要加前缀**（如`field('m.*, c.name as category_name')`）
4. **避免同名字段歧义**

---
修复时间：2026-02-03
修复状态：✅ 完成
