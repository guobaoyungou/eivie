# 门店列表查询逻辑修复

## 问题描述

在AI旅拍人像管理页面的上传弹窗中，门店下拉列表只显示"无门店"，实际上admin账号下有"绿万鸿花卉园艺"等3个门店，但没有显示出来。

## 问题原因

通过数据库查询发现：
- admin账号（aid=1）下有17个门店
- 前3个门店（绿万鸿花卉园艺、木森林园艺、瑾上添花）的`bid`字段值为`0`
- 其他门店的`bid`字段值为非0（10、11、12等）

原代码逻辑：
```php
// 超级管理员bid为0时，转换为第一个商家的bid（值为1）
$targetBid = $this->bid;
if ($targetBid == 0) {
    $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
}

// 使用targetBid=1查询门店，但门店的bid=0
$mendian_list = Db::name('mendian')
    ->where('aid', $this->aid)
    ->where('bid', $targetBid)  // bid=1，查不到bid=0的门店
    ->select();
```

**问题**：超级管理员的`bid=0`，转换后变成`targetBid=1`，但历史数据中门店的`bid=0`，导致查询不到。

## 解决方案

修改门店列表查询逻辑，区分超级管理员和普通商家：

**文件**: `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`

**修改方法**: `portrait_list()`

### 修改后的代码

```php
// 超级管理员bid为0时，使用aid对应的第一个商家
$targetBid = $this->bid;
if ($targetBid == 0) {
    $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
}

// 获取门店列表（超级管理员获取所有门店，普通商家只获取自己的门店）
$mendianWhere = [['aid', '=', $this->aid]];
if ($this->bid == 0) {
    // 超级管理员：获取aid下所有门店（包括bid=0的历史数据）
    // 不添加bid条件
} else {
    // 普通商家：只获取自己的门店
    $mendianWhere[] = ['bid', '=', $this->bid];
}

$mendian_list = Db::name('mendian')
    ->where($mendianWhere)
    ->select();

View::assign('mendian_list', $mendian_list);
return View::fetch();
```

### 关键改进

1. **判断用户类型**
   - 使用`$this->bid == 0`判断是否为超级管理员
   - 不使用转换后的`$targetBid`进行判断

2. **动态查询条件**
   - 超级管理员：只按`aid`查询，获取所有门店（包括bid=0的历史数据）
   - 普通商家：同时按`aid`和`bid`查询，只获取自己的门店

3. **兼容性**
   - 兼容历史数据（bid=0的门店）
   - 兼容新数据（bid为具体商家ID的门店）
   - 不影响普通商家的权限隔离

## 数据库查询结果对比

### 修改前
```
查询条件: aid=1, bid=1
结果: 找到 0 个门店
```

### 修改后（超级管理员）
```
查询条件: aid=1（不限制bid）
结果: 找到 17 个门店，包括：
  - 绿万鸿花卉园艺 (bid=0)
  - 木森林园艺(泰和花园店) (bid=0)
  - 瑾上添花(青岩店) (bid=0)
  - 谭会 (bid=10)
  - 贵州特产 (bid=11)
  - ... 等等
```

### 修改后（普通商家，如bid=10）
```
查询条件: aid=1, bid=10
结果: 只显示bid=10的门店
```

## 影响范围

**修改文件**:
- `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php` - `portrait_list()` 方法

**影响功能**:
- 人像管理页面的门店下拉列表（筛选器）
- 人像上传弹窗的门店选择器

**不影响**:
- 人像数据的查询和显示（仍然使用targetBid）
- 人像上传的数据保存（使用用户选择的mdid）
- 其他页面的门店查询

## 测试步骤

1. **超级管理员测试**
   - 使用admin账号登录商家后台
   - 进入"旅拍" -> "人像管理"
   - 点击"批量上传人像"按钮
   - 检查门店下拉列表是否显示所有门店（包括绿万鸿花卉园艺等）

2. **普通商家测试**
   - 使用普通商家账号登录
   - 进入"旅拍" -> "人像管理"
   - 点击"批量上传人像"按钮
   - 检查门店下拉列表是否只显示自己的门店

3. **功能验证**
   - 选择门店后上传图片
   - 检查上传成功后列表中的门店字段是否正确显示
   - 使用门店筛选器验证数据过滤功能

## 注意事项

1. **历史数据兼容性**
   - 本修复兼容bid=0的历史门店数据
   - 不需要修改数据库中的历史数据

2. **权限隔离**
   - 普通商家仍然只能看到自己的门店
   - 超级管理员可以看到所有门店

3. **数据一致性**
   - 门店列表显示的是aid下的所有门店（超级管理员）
   - 人像数据查询仍然使用targetBid，确保数据隔离

## 相关文件

- `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php` - 后端控制器（已修改）
- `/www/wwwroot/eivie/app/view/ai_travel_photo/portrait_list.html` - 前端页面（已包含门店选择器）

## 版本信息

- 修复时间: 2026-02-03
- 问题类型: 数据查询逻辑错误
- 影响版本: 所有版本
- 向后兼容: 是
