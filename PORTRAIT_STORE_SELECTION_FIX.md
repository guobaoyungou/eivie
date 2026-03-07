# 人像上传门店选择功能修复

## 问题描述

在AI旅拍人像管理模块中，上传人像时没有门店选择功能，导致所有上传的人像的门店字段（mdid）为空值（0）。

## 解决方案

### 1. 前端修改

**文件**: `/www/wwwroot/eivie/app/view/ai_travel_photo/portrait_list.html`

#### 修改内容：

1. **在上传弹窗中添加门店选择器**
   - 在文件列表上方添加了门店下拉选择框
   - 默认选项为"无门店"（值为0）
   - 动态加载当前商家的所有门店

2. **上传时传递门店ID**
   - 在批量上传函数`startBatchUpload`中获取选择的门店ID
   - 通过FormData将`mdid`参数传递给后端

3. **禁用上传中的门店选择**
   - 上传开始后禁用门店选择器，防止上传过程中修改

#### 关键代码片段：

```javascript
// 门店选择器HTML
html += '<div style="margin-bottom: 15px;">';
html += '<label style="display: inline-block; width: 80px; text-align: right; margin-right: 10px;">选择门店：</label>';
html += '<select id="upload_mdid" style="width: 200px; padding: 5px; border: 1px solid #ddd; border-radius: 3px;">';
html += '<option value="0">无门店</option>';
{foreach $mendian_list as $md}
html += '<option value="{$md.id}">{$md.name}</option>';
{/foreach}
html += '</select>';
html += '<span style="margin-left: 10px; color: #999; font-size: 12px;">（可选）为上传的人像关联门店</span>';
html += '</div>';

// 获取选择的门店ID并传递
var mdid = $('#upload_mdid').val() || 0;
var formData = new FormData();
formData.append('file', file);
formData.append('mdid', mdid);
```

### 2. 后端已支持（无需修改）

**文件**: `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`

后端`portrait_upload`方法已经支持接收`mdid`参数：

```php
// 获取门店ID（可选）
$mdid = input('post.mdid/d', 0);

// ...

// 插入人像记录时保存门店ID
$portraitData = [
    'aid' => $this->aid,
    'uid' => 0,
    'bid' => $targetBid,
    'mdid' => $mdid,  // 门店ID
    // ...
];
```

## 功能特性

1. **灵活性**
   - 门店选择为可选项，可以选择"无门店"
   - 支持为批量上传的所有图片设置相同的门店

2. **用户体验**
   - 在上传弹窗顶部显著位置显示门店选择器
   - 提供清晰的说明文字"（可选）为上传的人像关联门店"
   - 上传过程中锁定门店选择，避免误操作

3. **数据一致性**
   - 一次批量上传的所有图片使用相同的门店
   - 门店ID正确保存到数据库
   - 列表页面能正常显示门店信息

## 测试步骤

1. **访问人像管理页面**
   - 登录商家后台
   - 进入"旅拍" -> "人像管理"

2. **测试上传功能**
   - 点击"批量上传人像"按钮
   - 选择1-20个图片文件
   - 在弹窗顶部选择门店（或选择"无门店"）
   - 点击"开始上传"

3. **验证结果**
   - 上传完成后刷新列表
   - 检查"门店"列是否正确显示门店名称
   - 使用门店筛选功能验证数据正确性

## 注意事项

1. 如果商家还没有门店，下拉框只显示"无门店"选项
2. 门店列表从`mendian`表中读取，需要确保表中有数据
3. 超级管理员（bid=0）会自动获取第一个商家的门店列表

## 相关文件

- `/www/wwwroot/eivie/app/view/ai_travel_photo/portrait_list.html` - 前端页面（已修改）
- `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php` - 后端控制器（无需修改）

## 版本信息

- 修复时间: 2026-02-03
- 影响范围: AI旅拍人像上传功能
- 向后兼容: 是（mdid默认为0，与旧数据一致）
