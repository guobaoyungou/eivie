# 场景编辑页面空白问题修复总结

## 问题描述
- 点击"新增场景"或"编辑场景"后弹窗打开
- 但弹窗内容空白，没有显示表单
- Console显示：`[场景管理] 弹窗打开成功`

## 根本原因分析

### 1. ai_model_instance表不存在
控制器在查询模型列表时，如果`ddwx_ai_model_instance`表不存在会抛出异常，导致页面渲染失败。

### 2. 视图文件缺少"是否公共场景"字段
在之前的重构中，"是否公共场景"(is_public)字段被意外删除，但控制器仍在初始化这个字段。

## 修复内容

### 1. 控制器增强错误处理 (AiTravelPhoto.php)

#### 添加详细的trace日志
```php
trace('[场景编辑] 开始加载页面, ID=' . $id, 'info');
trace('[场景编辑] 模型列表查询成功，数量：' . count($models), 'info');
```

#### 增加表存在性检查
```php
// 先检查表是否存在
$tableExists = false;
try {
    Db::query('SHOW TABLES LIKE "ddwx_ai_model_instance"');
    $tableExists = true;
} catch (\Exception $e) {
    trace('[场景编辑] ai_model_instance表不存在', 'error');
}

$models = [];
if ($tableExists) {
    // 查询模型...
} else {
    // 使用空数组
}
```

#### 完善异常捕获
```php
} catch (\Exception $e) {
    trace('[场景编辑] 页面加载失败: ' . $e->getMessage(), 'error');
    trace('[场景编辑] 异常堆栈: ' . $e->getTraceAsString(), 'error');
    $this->error('页面加载失败：' . $e->getMessage());
}
```

### 2. 视图文件添加缺失字段 (scene_edit.html)

在"所属门店"字段后添加：
```html
<div class="layui-form-item">
  <label class="layui-form-label">是否公共场景：</label>
  <div class="layui-input-inline">
    <input type="checkbox" name="is_public" value="1" lay-skin="switch" 
           lay-text="是|否" {if !empty($info.is_public) && $info.is_public==1}checked{/if}>
  </div>
  <div class="layui-form-mid layui-word-aux">
    开启后，C端用户可见并调用此场景；关闭则仅商家后台可管理
  </div>
</div>
```

### 3. 控制器初始化数组完善

确保所有必需字段都有默认值：
```php
$info = [
    'id' => 0,
    'name' => '',
    'category' => '',
    'cover' => '',
    'background_url' => '',
    'desc' => '',
    'model_id' => 0,
    'api_config_id' => 0,
    'model_params' => '{}',
    'aspect_ratio' => '1:1',
    'sort' => 0,
    'tags' => '',
    'status' => 1,
    'is_public' => 0,
    'is_recommend' => 0
];
```

## 如何验证修复

### 1. 查看PHP日志
```bash
tail -f runtime/log/$(date +%Y%m)/$(date +%d).log
```

应该看到：
```
[场景编辑] 开始加载页面, ID=1
[场景编辑] 场景数据查询成功
[场景编辑] 开始查询模型列表, targetBid=1
[场景编辑] 模型列表查询成功，数量：0
[场景编辑] 开始查询门店列表
[场景编辑] 门店列表查询成功，数量：X
[场景编辑] 开始渲染视图
[场景编辑] 视图渲染成功
```

### 2. 检查浏览器Console
打开弹窗后应该看到：
```
[场景管理] 打开弹窗: /?s=/AiTravelPhoto/scene_edit/id/1
[场景管理] 弹窗打开成功
[场景编辑] 页面开始加载
[场景编辑] 场景ID: 1
[场景编辑] 模型ID: 0
[场景编辑] JavaScript开始执行
[场景编辑] DOM加载完成
```

### 3. 检查页面内容
- 应该显示完整的表单
- 包含所有字段
- 表单可以正常提交

## 后续处理

### 必需操作：创建ai_model_instance表

如果表不存在，需要执行SQL：
```bash
cd /www/wwwroot/eivie
mysql -u root -p ddwx < database/migrations/scene_management_complete_setup.sql
```

或手动执行：
```sql
-- 参考 scene_management_complete_setup.sql 中的建表语句
```

### 可选：添加示例数据

执行初始化数据SQL：
```bash
mysql -u root -p ddwx < database/migrations/ai_model_config_init_data.sql
```

## 测试清单

### 新增场景
- [ ] 点击"新增场景"按钮
- [ ] 弹窗正常打开
- [ ] 显示完整表单
- [ ] 模型下拉列表显示（可能为空）
- [ ] 门店下拉列表显示
- [ ] 所有字段可编辑
- [ ] 提交保存成功

### 编辑场景  
- [ ] 点击某个场景的"编辑"按钮
- [ ] 弹窗正常打开
- [ ] 显示完整表单
- [ ] 数据正确回填
- [ ] 修改后保存成功

## 文件修改清单

1. `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`
   - 添加详细日志
   - 添加表存在性检查
   - 完善异常处理
   - 修改行数：约+60行

2. `/www/wwwroot/eivie/app/view/ai_travel_photo/scene_edit.html`
   - 添加"是否公共场景"字段
   - 修改行数：+8行

3. `/www/wwwroot/eivie/SCENE_EDIT_FIX_SUMMARY.md`
   - 新建本文档

## 常见问题

### Q1: 仍然显示空白页面
**A**: 检查PHP错误日志，查看具体错误信息：
```bash
tail -50 /www/server/php/7.4/var/log/php-fpm.log
```

### Q2: 模型下拉列表为空
**A**: 这是正常的，因为`ai_model_instance`表可能还没有数据。需要：
1. 执行建表SQL
2. 执行初始化数据SQL
3. 或手动添加模型数据

### Q3: 提交保存失败
**A**: 检查：
1. 是否所有必填字段都已填写
2. 浏览器Console是否有错误
3. Network标签中请求是否成功

---

**修复日期**: 2026-02-04  
**修复人员**: AI Assistant  
**状态**: ✅ 修复完成
