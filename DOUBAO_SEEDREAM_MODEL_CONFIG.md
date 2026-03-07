# 豆包SeeDream 4.5图生图模型配置说明

## 📋 配置完成摘要

✅ **模型已成功添加到系统**
- 模型ID: 3
- 模型代码: `doubao-seedream-4-5-251128`
- 模型名称: 豆包SeeDream 4.5图生图
- 提供商: 火山引擎 (volcengine)
- 分类: 图生图 (image_to_image)

---

## 🔗 API信息

### 基础配置
- **API端点**: `https://ark.cn-beijing.volces.com/api/v3/images/generations`
- **认证方式**: Bearer Token
- **请求方法**: POST
- **Content-Type**: application/json

### 示例Token
```
Authorization: Bearer b8cd166f-c33e-4bb9-a52b-7f95f237b969
```

---

## 📝 参数配置详情

### 必填参数（2个）

#### 1. prompt - 提示词
- **类型**: string
- **格式**: text
- **必填**: ✅ 是
- **说明**: 描述期望生成的图像内容，支持中英文
- **示例**: "生成狗狗趴在草地上的近景画面"

#### 2. image - 参考图像
- **类型**: string
- **格式**: url
- **必填**: ✅ 是
- **说明**: 参考图像的URL地址，作为图生图的基础输入
- **示例**: "https://ark-project.tos-cn-beijing.volces.com/doc_image/seedream4_imageToimage.png"

### 可选参数（5个）

#### 3. sequential_image_generation - 连续生成模式
- **类型**: string
- **格式**: enum
- **必填**: ❌ 否
- **默认值**: "disabled"
- **可选值**: 
  - `disabled` - 禁用连续生成
  - `enabled` - 启用连续生成
- **说明**: 是否启用连续生成模式

#### 4. response_format - 响应格式
- **类型**: string
- **格式**: enum
- **必填**: ❌ 否
- **默认值**: "url"
- **可选值**:
  - `url` - 返回图片URL地址
  - `b64_json` - 返回Base64编码
- **说明**: 返回格式选择

#### 5. size - 输出尺寸
- **类型**: string
- **格式**: enum
- **必填**: ❌ 否
- **默认值**: "2K"
- **可选值**:
  - `2K` - 2K分辨率
  - `1K` - 1K分辨率
  - `512x512` - 512x512像素
  - `1024x1024` - 1024x1024像素
- **说明**: 输出图像尺寸规格

#### 6. stream - 流式输出
- **类型**: boolean
- **格式**: enum
- **必填**: ❌ 否
- **默认值**: false
- **可选值**: true / false
- **说明**: 是否使用流式输出

#### 7. watermark - 添加水印
- **类型**: boolean
- **格式**: enum
- **必填**: ❌ 否
- **默认值**: true
- **可选值**: true / false
- **说明**: 是否在生成的图像上添加水印

---

## 📤 响应字段配置

### 关键字段（1个）

#### 1. url - 图像URL
- **类型**: string
- **JSONPath**: `$.data[0].url`
- **关键字段**: ✅ 是
- **说明**: 生成的图像URL地址（当response_format=url时）

### 可选字段（2个）

#### 2. b64_json - 图像Base64
- **类型**: string
- **JSONPath**: `$.data[0].b64_json`
- **关键字段**: ❌ 否
- **说明**: 生成的图像Base64编码（当response_format=b64_json时）

#### 3. revised_prompt - 优化后的提示词
- **类型**: string
- **JSONPath**: `$.data[0].revised_prompt`
- **关键字段**: ❌ 否
- **说明**: 系统优化后的提示词

---

## 🔥 完整API调用示例

### cURL命令
```bash
curl -X POST https://ark.cn-beijing.volces.com/api/v3/images/generations \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer b8cd166f-c33e-4bb9-a52b-7f95f237b969" \
  -d '{
    "model": "doubao-seedream-4-5-251128",
    "prompt": "生成狗狗趴在草地上的近景画面",
    "image": "https://ark-project.tos-cn-beijing.volces.com/doc_image/seedream4_imageToimage.png",
    "sequential_image_generation": "disabled",
    "response_format": "url",
    "size": "2K",
    "stream": false,
    "watermark": true
}'
```

### JSON请求体
```json
{
  "model": "doubao-seedream-4-5-251128",
  "prompt": "生成狗狗趴在草地上的近景画面",
  "image": "https://ark-project.tos-cn-beijing.volces.com/doc_image/seedream4_imageToimage.png",
  "sequential_image_generation": "disabled",
  "response_format": "url",
  "size": "2K",
  "stream": false,
  "watermark": true
}
```

### 响应示例（URL格式）
```json
{
  "data": [
    {
      "url": "https://example.com/generated-image.png",
      "revised_prompt": "优化后的提示词"
    }
  ]
}
```

### 响应示例（Base64格式）
```json
{
  "data": [
    {
      "b64_json": "iVBORw0KGgoAAAANSUhEUgAA...",
      "revised_prompt": "优化后的提示词"
    }
  ]
}
```

---

## 💰 计费信息

- **计费模式**: 按次计费 (per_request)
- **计费单位**: 次 (times)
- **成本价格**: ¥0.05 / 次

---

## 🎯 使用场景

### 适用场景
1. **风格转换**: 将照片转换为不同艺术风格
2. **场景重建**: 基于参考图生成相似场景
3. **图像增强**: 提升图像质量和细节
4. **创意生成**: 在保持主体的基础上进行创意改编

### 典型应用
- 🎨 艺术风格迁移
- 🏞️ 场景再创作
- 📸 照片风格化处理
- 🎭 人像风格转换

---

## ⚙️ 管理界面访问

### 参数管理
```
http://192.168.11.222/?s=/ModelConfig/parameters/model_id/3
```

### 响应字段管理
```
http://192.168.11.222/?s=/ModelConfig/responses/model_id/3
```

### 模型列表
```
http://192.168.11.222/?s=/ModelConfig/index
```

---

## 🛠️ 使用建议

### 1. 图像质量优化
- 使用 `size: "2K"` 获得高清输出
- 适当调整提示词获得更好效果
- 选择合适的参考图像作为基础

### 2. 响应格式选择
- **URL格式**: 适合直接展示和分享
- **Base64格式**: 适合需要立即处理的场景

### 3. 水印控制
- 正式环境建议 `watermark: false`
- 测试环境可保持 `watermark: true`

### 4. 流式输出
- 大文件生成建议启用 `stream: true`
- 小图片可使用 `stream: false` 简化处理

---

## ⚠️ 注意事项

1. **参考图像要求**
   - 必须是可公开访问的URL
   - 建议图像质量清晰
   - 避免过小或过大的图像

2. **提示词建议**
   - 使用清晰、具体的描述
   - 支持中英文混合
   - 避免过于抽象的表达

3. **认证信息**
   - Bearer Token需要妥善保管
   - 定期更新认证凭证
   - 注意API调用配额

4. **错误处理**
   - 检查网络连接
   - 验证图像URL有效性
   - 处理超时和限流情况

---

## 📊 参数对比表

| 参数名 | 类型 | 必填 | 默认值 | 说明 |
|--------|------|------|--------|------|
| prompt | string | ✅ | - | 提示词 |
| image | string | ✅ | - | 参考图像URL |
| sequential_image_generation | string | ❌ | disabled | 连续生成模式 |
| response_format | string | ❌ | url | 响应格式 |
| size | string | ❌ | 2K | 输出尺寸 |
| stream | boolean | ❌ | false | 流式输出 |
| watermark | boolean | ❌ | true | 添加水印 |

---

## 🔍 验证步骤

### 1. 查看模型列表
```bash
curl "http://192.168.11.222/?s=/ModelConfig/index"
```

### 2. 查看参数配置
```bash
curl "http://192.168.11.222/?s=/ModelConfig/parameters/model_id/3"
```

### 3. 测试API调用
使用上面的cURL命令示例进行测试

---

## 📚 相关文档

- 火山引擎文档: [火山引擎AI服务](https://www.volcengine.com/)
- 模型配置管理: [MODEL_CONFIG_FIX_SUMMARY.md](./MODEL_CONFIG_FIX_SUMMARY.md)
- 系统配置: [config/ai_travel_photo.php](./config/ai_travel_photo.php)

---

## 📅 更新日志

**2026-02-06**
- ✅ 创建豆包SeeDream 4.5图生图模型配置
- ✅ 添加7个参数定义
- ✅ 添加3个响应字段定义
- ✅ 完成文档编写

---

## 🎉 配置状态

- **模型配置**: ✅ 完成
- **参数定义**: ✅ 完成 (7个)
- **响应字段**: ✅ 完成 (3个)
- **文档编写**: ✅ 完成
- **功能验证**: ⏳ 待测试

---

**如需修改配置或添加更多参数，请访问管理界面进行操作。**
