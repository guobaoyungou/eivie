# 模型场景配置功能 - 前端实现指南

## 概述

本文档说明如何使用已实现的场景配置前端页面。前端实现包括步骤向导、动态表单渲染和级联选择等功能。

## 文件列表

### 新增文件
- `/www/wwwroot/eivie/app/view/ai_travel_photo/scene_config_new.html` - 新版场景配置页面

### 需要更新的控制器方法
在 `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php` 中添加：

```php
/**
 * 场景配置页面（新版）
 */
public function scene_config_new()
{
    $id = input('id/d', 0);
    
    $info = [];
    if ($id > 0) {
        $info = Db::name('ai_travel_photo_scene')->find($id);
    }
    
    View::assign('info', $info);
    View::assign('aid', $this->aid);
    View::assign('bid', $this->bid);
    return View::fetch('scene_config_new');
}
```

## 功能特性

### 1. 四步向导流程

**步骤1：选择模型**
- 自动加载可用的AI模型列表
- 显示模型名称和服务提供商
- 支持模型能力标签识别

**步骤2：选择场景类型**
- 根据模型能力自动筛选支持的场景类型
- 卡片式展示，直观清晰
- 显示输入输出要求

**步骤3：配置参数**
- 动态渲染参数表单
- 区分必填参数和可选参数
- 支持6种场景类型的不同参数组合

**步骤4：完成配置**
- 提交保存场景配置
- 自动刷新父页面列表

### 2. 动态参数表单

根据场景类型自动渲染的参数字段：

#### 场景1：文生图-单张
```
必填: 提示词(textarea)
可选: 负面提示词, 输出尺寸, 水印
```

#### 场景2：文生图-多张
```
必填: 提示词, 生成数量(1-6)
可选: 负面提示词, 输出尺寸, 水印
```

#### 场景3：图生图-单张生成单张
```
必填: 参考图(上传), 提示词
可选: 负面提示词, 输出尺寸, 水印
```

#### 场景4：图生图-单张生成多张
```
必填: 参考图(上传), 提示词, 生成数量(1-10)
可选: 负面提示词, 输出尺寸, 水印
```

#### 场景5：图生图-多张生成单张
```
必填: 参考图数组(上传1-10张), 提示词
可选: 负面提示词, 输出尺寸, 水印
```

#### 场景6：图生图-多张生成多张
```
必填: 参考图数组(上传1-10张), 提示词, 生成数量(1-10)
可选: 负面提示词, 输出尺寸, 水印
```

### 3. API接口调用

前端页面调用以下后端API接口：

| 接口 | 用途 | 触发时机 |
|------|------|---------|
| `/AiTravelPhoto/get_model_list` | 获取模型列表 | 页面加载时 |
| `/AiTravelPhoto/get_scene_types` | 获取场景类型 | 选择模型后 |
| `/AiTravelPhoto/get_api_config_list` | 获取API配置 | 进入步骤3时 |
| `/AiTravelPhoto/scene_save_new` | 保存场景配置 | 提交表单时 |

### 4. UI/UX特性

- **步骤向导**：清晰的4步流程引导
- **卡片式选择**：场景类型采用卡片展示，支持hover和选中效果
- **参数分组**：必填参数和可选参数分组显示
- **表单验证**：前端和后端双重验证
- **友好提示**：每个字段都有说明文字

## 使用方法

### 方法1：在场景列表页添加按钮

在 `scene_list.html` 中添加新建按钮：

```html
<button class="layui-btn layui-btn-sm" onclick="openSceneConfig(0)">
  <i class="layui-icon layui-icon-add-1"></i> 新建场景（新版）
</button>

<script>
function openSceneConfig(id) {
  layer.open({
    type: 2,
    title: id > 0 ? '编辑场景配置' : '新建场景配置',
    area: ['90%', '90%'],
    content: '{:url("AiTravelPhoto/scene_config_new")}?id=' + id,
    end: function() {
      layui.table.reload('sceneList');
    }
  });
}
</script>
```

### 方法2：直接访问URL

```
http://域名/AiTravelPhoto/scene_config_new
```

### 方法3：编辑现有场景

```javascript
// 在场景列表的操作列添加
function editScene(id) {
  layer.open({
    type: 2,
    title: '编辑场景配置',
    area: ['90%', '90%'],
    content: '{:url("AiTravelPhoto/scene_config_new")}?id=' + id
  });
}
```

## 扩展开发

### 添加自定义参数类型

在 `renderParamField` 函数中添加新的 case：

```javascript
case 'custom_param':
  html = '<div class="layui-form-item">' +
         '<label class="layui-form-label">自定义参数：</label>' +
         '<div class="layui-input-inline">' +
         '<input type="text" name="model_params[custom_param]" class="layui-input">' +
         '</div>' +
         '</div>';
  break;
```

### 集成现有上传组件

将现有的 `uploader()` 函数集成到参数表单中：

```javascript
// 在renderParamField中修改image参数的渲染
case 'image':
  html = '<div class="layui-form-item">' +
         '<label class="layui-form-label">' + requiredStar + '参考图：</label>' +
         '<div class="layui-input-block">' +
         '<button type="button" class="layui-btn layui-btn-primary" ' +
         'upload-input="reference_image" upload-preview="refImagePreview" ' +
         'onclick="uploader(this)">上传图片</button>' +
         '<div id="refImagePreview" class="picsList-class-padding">' +
         // 图片预览区域
         '</div>' +
         '</div>' +
         '</div>';
  break;
```

### 添加表单验证规则

```javascript
form.verify({
  imageCount: function(value, item) {
    var images = JSON.parse(value || '[]');
    if (images.length < 1 || images.length > 10) {
      return '参考图数量必须为1-10张';
    }
  },
  promptLength: function(value) {
    if (value.length > 2000) {
      return '提示词不能超过2000字符';
    }
  }
});
```

## 样式定制

### 修改步骤向导样式

```css
/* 修改激活状态的颜色 */
.step-item.active {
  color: #1E9FFF; /* 改为蓝色 */
}

.step-item.active .step-number {
  background: #1E9FFF;
}
```

### 修改场景类型卡片样式

```css
/* 修改卡片hover效果 */
.scene-type-card:hover {
  border-color: #1E9FFF;
  box-shadow: 0 4px 12px rgba(30,159,255,0.3);
  transform: translateY(-2px);
}
```

## 常见问题

### 1. 模型列表为空

**问题**：步骤1显示"暂无可用模型"

**原因**：数据库中没有启用的AI模型

**解决**：
```sql
-- 检查模型状态
SELECT id, model_name, is_active FROM ddwx_ai_model_instance;

-- 启用模型
UPDATE ddwx_ai_model_instance SET is_active = 1 WHERE id = 3;
```

### 2. 场景类型不显示

**问题**：步骤2显示"该模型暂无支持的场景类型"

**原因**：模型的 `capability_tags` 未配置

**解决**：
```sql
UPDATE ddwx_ai_model_instance 
SET capability_tags = '["text2image", "image2image", "batch_generation", "multi_input"]'
WHERE id = 3;
```

### 3. 参数表单未渲染

**问题**：步骤3的动态参数区域为空

**原因**：场景类型元数据表无数据

**解决**：
```bash
# 重新执行场景类型元数据表迁移
mysql -u用户名 -p数据库名 < database/migrations/scene_type_metadata.sql
```

### 4. 提交失败

**问题**：点击"保存配置"后提示失败

**可能原因**：
- API配置未选择
- 必填参数未填写
- 后端验证不通过

**调试方法**：
```javascript
// 在form.on('submit')中添加调试日志
console.log('提交数据:', data.field);

// 查看后端返回的具体错误
success: function(res) {
  console.log('后端响应:', res);
  // ...
}
```

## 完整示例

### 配置场景4（图生图-单张生成多张）

1. **选择模型**
   - 选择"豆包SeeDream 4.5图生图"
   - 点击"下一步"

2. **选择场景类型**
   - 点击"图生图-单张图生成一组图"卡片
   - 点击"下一步"

3. **配置参数**
   - API配置：选择"豆包API配置1"
   - 场景名称：填写"专业人像批量生成"
   - 场景分类：选择"人物"
   - 提示词：填写"专业摄影风格的人像照片"
   - 负面提示词：填写"模糊,低质量"
   - 参考图：上传一张人像图片
   - 生成数量：设置为6
   - 输出尺寸：选择"2K"
   - 水印：关闭
   - 是否公开：开启
   - 点击"保存配置"

4. **完成**
   - 页面自动关闭
   - 场景列表刷新，显示新创建的场景

## 后续优化建议

1. **图片上传增强**
   - 集成拖拽上传
   - 支持多图批量上传
   - 图片裁剪和预处理

2. **参数预设**
   - 提供参数模板
   - 快速应用常用配置
   - 参数历史记录

3. **实时预览**
   - 在配置时生成示例图
   - 参数调整实时预览效果

4. **批量操作**
   - 批量创建相似场景
   - 场景参数批量修改
   - 场景复制和克隆

5. **数据统计**
   - 场景使用次数统计
   - 成功率监控
   - 热门场景推荐

---
**版本**: v1.0  
**最后更新**: 2026-02-06
