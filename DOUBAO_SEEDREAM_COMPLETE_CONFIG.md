# 豆包SeeDream 4.5模型完整配置

## ✅ 配置完成状态

**配置时间**: 2026-02-06  
**模型状态**: ✅ 已完成并更新定价

---

## 📋 模型基本信息

| 项目 | 详情 |
|------|------|
| **模型ID** | 3 |
| **模型代码** | doubao-seedream-4-5-251128 |
| **模型名称** | 豆包SeeDream 4.5图生图 |
| **提供商** | 火山引擎 (volcengine) |
| **分类** | 图生图 (image_to_image) |
| **API端点** | https://ark.cn-beijing.volces.com/api/v3/images/generations |

---

## 💰 定价信息

### 官方定价
- **文生图**: ¥0.25/张
- **图生图**: ¥0.25/张
- **统一定价**: ¥0.25/张

### 系统计费配置
- **成本价**: ¥0.25/次
- **计费模式**: 按次计费 (per_request)
- **计费单位**: 次 (times)

---

## 🎯 模型能力

### 支持的任务类型
1. ✅ **文生图** (Text-to-Image)
   - 根据文字描述生成图像
   
2. ✅ **图生图** (Image-to-Image)
   - 基于参考图像生成新图像
   - 风格转换
   - 场景重建

### 能力标签
- 图生图
- 文生图
- 高清输出
- 2K-4K分辨率
- IPM:500 (500次/分钟)

---

## 📐 技术规格

### 分辨率范围
- **最小**: 2560×1440
- **最大**: 4096×4096
- **推荐配置**:
  - 2K (约2000像素长边)
  - 1K (约1000像素长边)
  - 2560×1440 (2.5K)
  - 3840×2160 (4K UHD)
  - 4096×4096 (4K 方形)

### 性能限制
- **IPM**: 500次/分钟
- **响应模式**: 同步/流式
- **支持水印**: 是

---

## 📝 参数配置详情

### 必填参数 (2个)

#### 1. prompt - 提示词
```json
{
  "param_name": "prompt",
  "param_label": "提示词",
  "param_type": "string",
  "data_format": "text",
  "is_required": true,
  "description": "描述期望生成的图像内容，支持中英文"
}
```
**UI建议**: 多行文本框 (height: 120px)

#### 2. image - 参考图像
```json
{
  "param_name": "image",
  "param_label": "参考图像",
  "param_type": "string",
  "data_format": "url",
  "is_required": true,
  "description": "参考图像的URL地址，作为图生图的基础输入"
}
```
**注意**: 图像URL必须可公开访问

### 可选参数 (5个)

#### 3. sequential_image_generation - 连续生成模式
```json
{
  "param_name": "sequential_image_generation",
  "param_label": "连续生成模式",
  "param_type": "string",
  "data_format": "enum",
  "is_required": false,
  "default_value": "disabled",
  "enum_options": ["disabled", "enabled"]
}
```

#### 4. response_format - 响应格式
```json
{
  "param_name": "response_format",
  "param_label": "响应格式",
  "param_type": "string",
  "data_format": "enum",
  "is_required": false,
  "default_value": "url",
  "enum_options": ["url", "b64_json"]
}
```

#### 5. size - 输出尺寸 ⭐
```json
{
  "param_name": "size",
  "param_label": "输出尺寸",
  "param_type": "string",
  "data_format": "enum",
  "is_required": false,
  "default_value": "2K",
  "enum_options": [
    "2K",
    "1K",
    "2560x1440",
    "3840x2160",
    "4096x4096",
    "1024x1024",
    "1920x1080"
  ]
}
```
**重要**: 豆包模型使用 `"2K"` 格式，与通义千问的 `"1024*1536"` 格式不同！

#### 6. stream - 流式输出
```json
{
  "param_name": "stream",
  "param_label": "流式输出",
  "param_type": "boolean",
  "data_format": "enum",
  "is_required": false,
  "default_value": false,
  "enum_options": [true, false]
}
```

#### 7. watermark - 添加水印
```json
{
  "param_name": "watermark",
  "param_label": "添加水印",
  "param_type": "boolean",
  "data_format": "enum",
  "is_required": false,
  "default_value": true,
  "enum_options": [true, false]
}
```

---

## 🔄 响应字段配置

### 1. url - 图像URL (关键字段)
```json
{
  "response_field": "url",
  "field_label": "图像URL",
  "field_type": "string",
  "field_path": "$.data[0].url",
  "is_critical": true,
  "description": "生成的图像URL地址（当response_format=url时）"
}
```

### 2. b64_json - 图像Base64
```json
{
  "response_field": "b64_json",
  "field_label": "图像Base64",
  "field_type": "string",
  "field_path": "$.data[0].b64_json",
  "is_critical": false,
  "description": "生成的图像Base64编码（当response_format=b64_json时）"
}
```

### 3. revised_prompt - 优化后的提示词
```json
{
  "response_field": "revised_prompt",
  "field_label": "优化后的提示词",
  "field_type": "string",
  "field_path": "$.data[0].revised_prompt",
  "is_critical": false,
  "description": "系统优化后的提示词"
}
```

---

## 🚀 API调用示例

### 标准图生图请求
```bash
curl -X POST https://ark.cn-beijing.volces.com/api/v3/images/generations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "doubao-seedream-4-5-251128",
    "prompt": "生成狗狗趴在草地上的近景画面",
    "image": "https://example.com/reference.png",
    "sequential_image_generation": "disabled",
    "response_format": "url",
    "size": "2K",
    "stream": false,
    "watermark": false
}'
```

### 高分辨率输出
```json
{
  "model": "doubao-seedream-4-5-251128",
  "prompt": "专业摄影风格的城市夜景",
  "image": "https://example.com/city.jpg",
  "size": "4096x4096",
  "response_format": "url",
  "watermark": false
}
```

### 流式输出模式
```json
{
  "model": "doubao-seedream-4-5-251128",
  "prompt": "艺术风格的人像照片",
  "image": "https://example.com/portrait.jpg",
  "size": "2K",
  "stream": true,
  "response_format": "url"
}
```

---

## ⚠️ 重要注意事项

### 1. 分辨率格式差异
| 模型 | 格式示例 | 说明 |
|------|---------|------|
| 豆包SeeDream | `"2K"` 或 `"2560x1440"` | 使用 `x` 连接 |
| 通义千问 | `"1024*1536"` | 使用 `*` 连接 |

### 2. 参数兼容性
- `image` 参数在豆包中是**必填**的（图生图）
- 如果支持文生图，`image` 可能变为可选
- 需要根据实际任务类型动态调整

### 3. IPM限制
- 500次/分钟的调用限制
- 建议实现请求队列和限流机制
- 超出限制会返回 429 错误

### 4. 水印控制
- 默认 `watermark: true`
- 生产环境建议设置为 `false`
- 水印位置无法自定义

---

## 🛠️ 系统集成建议

### 1. 场景配置
在 AI 旅拍场景编辑中：
```php
// config/ai_travel_photo.php
'scene_types' => [
    // ... 其他类型
    'image_to_image_doubao' => [
        'name' => '豆包图生图',
        'model_code' => 'doubao-seedream-4-5-251128',
        'default_params' => [
            'size' => '2K',
            'watermark' => false,
            'response_format' => 'url'
        ]
    ]
]
```

### 2. 前端UI配置
```javascript
// size 参数渲染
if (modelCode === 'doubao-seedream-4-5-251128') {
    // 豆包格式：下拉选择
    renderSelect('size', ['2K', '1K', '2560x1440', '3840x2160', '4096x4096']);
} else if (modelCode === 'qwen-image-edit-max') {
    // 通义格式：文本输入
    renderInput('size', 'text', placeholder: '1024*1536');
}
```

### 3. 参数转换
```php
// 在调用 API 前转换参数
function prepareDoubaoParams($params) {
    // 确保必填参数存在
    if (empty($params['prompt'])) {
        throw new Exception('prompt 参数必填');
    }
    if (empty($params['image'])) {
        throw new Exception('image 参数必填');
    }
    
    // 设置默认值
    $params['size'] = $params['size'] ?? '2K';
    $params['watermark'] = $params['watermark'] ?? false;
    $params['response_format'] = $params['response_format'] ?? 'url';
    $params['stream'] = $params['stream'] ?? false;
    
    return $params;
}
```

---

## 📊 对比分析

### 豆包 vs 通义千问

| 特性 | 豆包SeeDream 4.5 | 通义千问图像编辑 |
|------|-----------------|----------------|
| **定价** | ¥0.25/张 | 约¥0.05-0.10/张 |
| **分辨率** | 2560×1440 - 4096×4096 | 512×512 - 2048×2048 |
| **多图输出** | ❌ 单张 | ✅ 1-6张 (n参数) |
| **size格式** | "2K" 或 "2560x1440" | "1024*1536" |
| **提示词优化** | ❌ 无 | ✅ prompt_extend |
| **IPM限制** | 500次/分钟 | 未公开 |
| **流式输出** | ✅ 支持 | ❌ 不支持 |

---

## 🔍 测试验证

### 管理界面访问
```
参数管理: http://192.168.11.222/?s=/ModelConfig/parameters/model_id/3
响应字段: http://192.168.11.222/?s=/ModelConfig/responses/model_id/3
模型列表: http://192.168.11.222/?s=/ModelConfig/index
```

### 快速测试脚本
```bash
# 查看模型配置
php /www/wwwroot/eivie/list_parameters.php

# 测试 API 调用
curl -X POST https://ark.cn-beijing.volces.com/api/v3/images/generations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_KEY" \
  -d '{"model":"doubao-seedream-4-5-251128","prompt":"测试","image":"URL","size":"2K"}'
```

---

## 📚 相关文档

- [豆包SeeDream模型配置](./DOUBAO_SEEDREAM_MODEL_CONFIG.md)
- [模型配置管理总结](./MODEL_CONFIG_FIX_SUMMARY.md)
- [AI旅拍系统文档](./AI_TRAVEL_PHOTO_EXECUTION_SUMMARY.md)

---

## ✨ 更新日志

**2026-02-06**
- ✅ 创建模型实例 (ID: 3)
- ✅ 添加7个参数定义
- ✅ 添加3个响应字段定义
- ✅ 更新定价信息 (¥0.25/张)
- ✅ 更新分辨率配置 (2560×1440 - 4096×4096)
- ✅ 添加完整文档

---

**配置状态**: 🟢 已完成  
**功能状态**: ⏳ 待集成到场景管理
