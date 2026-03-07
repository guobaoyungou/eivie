# 豆包SeeDream 4.5 - 多图生成组图功能配置

## 📋 功能概述

豆包SeeDream 4.5模型现已支持**多张参考图生成组图**功能，可以基于多张输入图像生成一系列相关的图片。

### 新增能力
- ✅ **单图生图**: 传入单个参考图像URL
- ✅ **多图生成组图**: 传入多个参考图像URL数组
- ✅ **自动连续生成**: 智能生成指定数量的相关图片
- ✅ **流式输出**: 支持实时返回生成结果

---

## 🆕 新增/更新参数

### 1. image - 参考图像（已更新）
```json
{
  "param_name": "image",
  "param_label": "参考图像",
  "param_type": "array",  // 从string改为array
  "data_format": "url",
  "is_required": true,
  "description": "支持单张图片（字符串）或多张图片（数组）"
}
```

**使用方式**:
```json
// 单图模式
{
  "image": "https://example.com/image1.png"
}

// 多图模式
{
  "image": [
    "https://example.com/image1.png",
    "https://example.com/image2.png"
  ]
}
```

### 2. sequential_image_generation - 连续生成模式（已更新）
```json
{
  "param_name": "sequential_image_generation",
  "param_label": "连续生成模式",
  "param_type": "string",
  "data_format": "enum",
  "is_required": false,
  "default_value": "auto",
  "enum_options": ["disabled", "enabled", "auto"]
}
```

**选项说明**:
- `disabled` - 禁用连续生成（仅生成1张）
- `enabled` - 启用连续生成（手动控制）
- `auto` - 自动模式（**推荐用于多图生成**）

### 3. sequential_image_generation_options - 组图生成配置（新增）⭐
```json
{
  "param_name": "sequential_image_generation_options",
  "param_label": "组图生成配置",
  "param_type": "object",
  "data_format": "json",
  "is_required": false,
  "description": "连续生成模式的配置选项"
}
```

**配置结构**:
```json
{
  "max_images": 3  // 指定生成图片的数量（1-10）
}
```

---

## 🔥 完整API调用示例

### 示例1: 多图生成组图（早中晚三个时段）
```bash
curl -X POST https://ark.cn-beijing.volces.com/api/v3/images/generations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "model": "doubao-seedream-4-5-251128",
    "prompt": "生成3张女孩和奶牛玩偶在游乐园开心地坐过山车的图片，涵盖早晨、中午、晚上",
    "image": [
      "https://ark-project.tos-cn-beijing.volces.com/doc_image/seedream4_imagesToimages_1.png",
      "https://ark-project.tos-cn-beijing.volces.com/doc_image/seedream4_imagesToimages_2.png"
    ],
    "sequential_image_generation": "auto",
    "sequential_image_generation_options": {
      "max_images": 3
    },
    "response_format": "url",
    "size": "2K",
    "stream": true,
    "watermark": true
}'
```

### 示例2: 单图生图（简单模式）
```json
{
  "model": "doubao-seedream-4-5-251128",
  "prompt": "生成狗狗趴在草地上的近景画面",
  "image": "https://example.com/reference.png",
  "sequential_image_generation": "disabled",
  "response_format": "url",
  "size": "2K",
  "watermark": false
}
```

### 示例3: 多图生成（不同风格）
```json
{
  "model": "doubao-seedream-4-5-251128",
  "prompt": "生成5张不同风格的城市夜景：赛博朋克、复古、梦幻、写实、抽象",
  "image": [
    "https://example.com/city1.jpg",
    "https://example.com/city2.jpg",
    "https://example.com/city3.jpg"
  ],
  "sequential_image_generation": "auto",
  "sequential_image_generation_options": {
    "max_images": 5
  },
  "size": "4096x4096",
  "stream": true,
  "response_format": "url",
  "watermark": false
}
```

---

## 📊 参数对比表

| 参数名 | 单图模式 | 多图模式 | 说明 |
|--------|---------|---------|------|
| **prompt** | 必填 | 必填 | 描述期望的生成内容 |
| **image** | 字符串 | 数组 | 单个URL或多个URL数组 |
| **sequential_image_generation** | "disabled" | "auto" | 单图用disabled，多图用auto |
| **sequential_image_generation_options** | 不需要 | 必需 | 指定max_images数量 |
| **stream** | false | true | 多图建议开启流式 |

---

## 💡 使用场景

### 1. 时间序列生成
```json
{
  "prompt": "同一场景在春夏秋冬四季的变化",
  "image": ["base_scene.jpg"],
  "sequential_image_generation_options": {
    "max_images": 4
  }
}
```

### 2. 风格探索
```json
{
  "prompt": "将这张照片生成水彩、油画、素描、卡通4种艺术风格",
  "image": ["original_photo.jpg"],
  "sequential_image_generation_options": {
    "max_images": 4
  }
}
```

### 3. 故事板生成
```json
{
  "prompt": "根据这些角色设定，生成故事的5个关键场景",
  "image": [
    "character1.png",
    "character2.png",
    "environment.jpg"
  ],
  "sequential_image_generation_options": {
    "max_images": 5
  }
}
```

### 4. 变化序列
```json
{
  "prompt": "展示从白天到黑夜的渐变过程，共6张图",
  "image": ["daytime.jpg"],
  "sequential_image_generation_options": {
    "max_images": 6
  }
}
```

---

## 💰 计费说明

### 基础定价
- **单张图片**: ¥0.25/张
- **多图生成**: ¥0.25/张 × 生成数量

### 示例
```
生成3张图片 = ¥0.25 × 3 = ¥0.75
生成5张图片 = ¥0.25 × 5 = ¥1.25
```

### 成本优化建议
1. 合理设置`max_images`数量
2. 使用精确的prompt减少重试
3. 测试时使用较小的`size`配置
4. 考虑批量生成的性价比

---

## ⚙️ 系统集成配置

### 前端UI设计

#### 参考图像上传组件
```html
<!-- 单图模式 -->
<div class="image-upload single">
  <input type="file" accept="image/*" />
  <span>上传参考图像</span>
</div>

<!-- 多图模式 -->
<div class="image-upload multiple">
  <input type="file" accept="image/*" multiple max="10" />
  <span>上传多张参考图像（最多10张）</span>
  <div class="image-list">
    <!-- 显示已上传的图片列表 -->
  </div>
</div>
```

#### 生成数量控制
```html
<div class="generation-options">
  <label>生成图片数量：</label>
  <input type="number" 
         name="max_images" 
         min="1" 
         max="10" 
         value="3" />
  <span class="tip">每张 ¥0.25</span>
  <div class="cost-preview">
    预计费用：¥<span id="estimated-cost">0.75</span>
  </div>
</div>
```

### 后端参数构建

```php
/**
 * 构建豆包多图生成参数
 */
function buildDoubaoMultiImageParams($data) {
    $params = [
        'model' => 'doubao-seedream-4-5-251128',
        'prompt' => $data['prompt'],
        'response_format' => $data['response_format'] ?? 'url',
        'size' => $data['size'] ?? '2K',
        'watermark' => $data['watermark'] ?? false
    ];
    
    // 处理参考图像
    if (is_array($data['images']) && count($data['images']) > 1) {
        // 多图模式
        $params['image'] = $data['images'];
        $params['sequential_image_generation'] = 'auto';
        $params['sequential_image_generation_options'] = [
            'max_images' => $data['max_images'] ?? 3
        ];
        $params['stream'] = true; // 多图建议开启流式
    } else {
        // 单图模式
        $params['image'] = is_array($data['images']) 
            ? $data['images'][0] 
            : $data['images'];
        $params['sequential_image_generation'] = 'disabled';
        $params['stream'] = false;
    }
    
    return $params;
}
```

### 成本计算
```php
/**
 * 计算多图生成成本
 */
function calculateMultiImageCost($maxImages) {
    $pricePerImage = 0.25; // ¥0.25/张
    $totalCost = $pricePerImage * $maxImages;
    
    return [
        'unit_price' => $pricePerImage,
        'quantity' => $maxImages,
        'total_cost' => $totalCost,
        'currency' => 'CNY'
    ];
}
```

---

## 🔍 响应处理

### 流式响应（stream: true）
```javascript
const eventSource = new EventSource(apiUrl);

eventSource.onmessage = (event) => {
  const data = JSON.parse(event.data);
  
  if (data.type === 'image_generated') {
    // 每生成一张图片会触发一次
    displayImage(data.url);
    updateProgress(data.index, data.total);
  }
  
  if (data.type === 'completed') {
    // 全部生成完成
    eventSource.close();
    showComplete(data.images);
  }
};
```

### 非流式响应（stream: false）
```javascript
const response = await fetch(apiUrl, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(params)
});

const result = await response.json();
// result.data 包含所有生成的图片
result.data.forEach((item, index) => {
  displayImage(item.url, index);
});
```

---

## ⚠️ 注意事项

### 1. 参考图像要求
- ✅ 所有图片必须是可公开访问的URL
- ✅ 支持的格式：JPG、PNG、WebP
- ✅ 建议每张图片大小不超过5MB
- ⚠️ 数组长度建议不超过10张

### 2. 生成数量限制
- **单次请求**: 1-10张
- **推荐范围**: 3-5张（性价比最佳）
- **IPM限制**: 注意500次/分钟的总限制

### 3. 提示词优化
```
推荐格式：
"生成[数量]张[主体]在[场景]中[动作]的图片，[时间/风格/状态]"

示例：
✅ "生成3张女孩在游乐园玩耍的图片，分别是早晨、中午、晚上"
✅ "生成4张城市街景，风格为水彩、油画、素描、摄影"
❌ "生成多张图片"（太笼统）
```

### 4. 性能优化
- 多图生成建议开启`stream: true`
- 大尺寸（如4K）会增加生成时间
- 合理设置超时时间（建议60秒+）

---

## 📈 能力对比

### 豆包 vs 通义千问

| 特性 | 豆包SeeDream 4.5 | 通义千问 |
|------|-----------------|---------|
| **多图输出** | ✅ 支持（组图模式） | ✅ 支持（n参数） |
| **参考图数量** | 1-10张 | 1张 |
| **连续生成** | ✅ 自动模式 | ❌ 不支持 |
| **最大输出** | 10张/次 | 6张/次 |
| **流式输出** | ✅ 支持 | ❌ 不支持 |
| **单张定价** | ¥0.25 | ¥0.05-0.10 |

### 使用建议
- **豆包优势**: 多参考图、连续生成、流式输出、高分辨率
- **通义优势**: 价格便宜、提示词优化、生成速度快
- **选择策略**: 
  - 需要多参考图 → 豆包
  - 需要成本优先 → 通义
  - 需要4K输出 → 豆包

---

## 🧪 测试用例

### 测试1: 基础多图生成
```json
{
  "model": "doubao-seedream-4-5-251128",
  "prompt": "生成3张测试图片",
  "image": ["https://example.com/test1.jpg", "https://example.com/test2.jpg"],
  "sequential_image_generation": "auto",
  "sequential_image_generation_options": {"max_images": 3},
  "size": "1K",
  "watermark": true
}
```
**预期**: 返回3张图片URL

### 测试2: 兼容性（单图模式）
```json
{
  "model": "doubao-seedream-4-5-251128",
  "prompt": "生成1张测试图片",
  "image": "https://example.com/test.jpg",
  "sequential_image_generation": "disabled",
  "size": "2K"
}
```
**预期**: 返回1张图片URL，与原单图模式兼容

---

## 📚 相关文档

- [豆包SeeDream完整配置](./DOUBAO_SEEDREAM_COMPLETE_CONFIG.md)
- [豆包快速参考卡](./DOUBAO_QUICK_REF.md)
- [模型配置管理总结](./MODEL_CONFIG_FIX_SUMMARY.md)

---

## 🎉 更新日志

**2026-02-06 - 多图生成功能**
- ✅ image参数支持数组格式
- ✅ sequential_image_generation添加auto模式
- ✅ 新增sequential_image_generation_options参数
- ✅ 更新模型能力标签
- ✅ 完成文档编写

---

**配置状态**: 🟢 已完成  
**测试状态**: ⏳ 待验证  
**管理页面**: http://192.168.11.222/?s=/ModelConfig/parameters/model_id/3
