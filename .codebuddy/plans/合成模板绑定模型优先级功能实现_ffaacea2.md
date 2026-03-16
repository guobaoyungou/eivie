---
name: 合成模板绑定模型优先级功能实现
overview: 实现合成模板编辑页模型选择优先级功能：优先显示商户自己配置的API Key关联的图生图模型，其次显示平台级模型
todos:
  - id: modify-scene-config-service
    content: 修改 SceneConfigService.php 的 getEnabledModelList 方法，添加图生图能力过滤和优先级排序
    status: completed
  - id: update-frontend-model-select
    content: 修改 synthesis_template_edit.html 前端，在模型下拉框显示配置类型标签
    status: completed
    dependencies:
      - modify-scene-config-service
  - id: test-model-priority
    content: 测试验证：分别用商户有API Key和无API Key的场景测试模型列表排序和过滤
    status: completed
    dependencies:
      - update-frontend-model-select
---

## 用户需求

新增合成模板-绑定模型优先关联商户API KEY关联的模型广场里具有图生图能力的模型。否则关联平台的API KEY对应的图生图能力的模型，使用平台的APIKEY时，执行该模板的任务时，需扣除该商户的账户余额

## 核心功能

- 合成模板绑定模型时，只显示具有图生图能力的模型
- 模型选择优先级：商户自己配置的API Key模型 > 平台级配置模型
- 使用平台API KEY时，执行任务需扣除商户账户余额

## 图生图模型识别规则

- model_code 包含 "i2i"（如 wan2.5-i2i-preview）
- model_code 包含 "imageedit"（如 wanx2.1-imageedit）
- model_code 等于 "tongyi_wanxiang"

## 技术方案

### 技术选型

- 后端：PHP + ThinkPHP 6
- 前端：Layui + HTML
- 数据层：MySQL

### 实现方式

#### 1. 后端修改 (SceneConfigService.php)

修改 `/home/www/ai.eivie.cn/app/service/SceneConfigService.php` 的 `getEnabledModelList` 方法：

- 添加图生图能力过滤：遍历模型时，根据 model_code 判断是否具有图生图能力
- 按优先级排序：商户自配置模型(is_merchant_config=true)排在前面，平台级排在后面
- 保持现有的 is_merchant_config、is_platform_config、config_type_label 字段

#### 2. 前端修改 (synthesis_template_edit.html)

修改 `/home/www/ai.eivie.cn/app/view/ai_travel_photo/synthesis_template_edit.html`：

- 在模型下拉选项中显示配置类型标签（商户自配置 / 平台预充值）

### 关键代码逻辑

```
// 图生图模型判断逻辑
$isImageToImage = (
    strpos($model['model_code'], 'i2i') !== false || 
    strpos($model['model_code'], 'imageedit') !== false || 
    $model['model_code'] === 'tongyi_wanxiang'
);

// 排序：商户配置在前，平台配置在后
usort($result, function($a, $b) {
    return $b['is_merchant_config'] - $a['is_merchant_config'];
});
```

### 性能考虑

- 模型列表数据量较小，无需额外缓存
- 使用现有数据库索引 (model_info.is_active)