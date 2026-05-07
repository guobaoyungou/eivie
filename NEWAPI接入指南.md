# NEWAPI中转平台接入指南

## 概述

本文档说明如何在模型广场中接入 **NEWAPI (hfsyapi.cn)** 中转平台的图片生成服务。

NEWAPI 是新一代大模型网关，兼容 OpenAI API 格式，支持 GPT-Image 系列图像生成模型。

---

## 一、执行数据库脚本

### 1.1 执行SQL脚本

在数据库中执行 `database/migrations/add_newapi_provider.sql` 脚本，该脚本将：

1. **添加 NEWAPI 供应商** (`provider_code`: `newapi`)
2. **添加图片生成模型**：
   - `gpt-image` - 通用GPT-Image模型（动态路由）
   - `gpt-image-2` - GPT-Image-2（最新，支持4K）
   - `gpt-image-1.5` - GPT-Image-1.5
   - `dall-e-3` - DALL-E 3

### 1.2 验证添加结果

```sql
SELECT p.provider_name, m.model_code, m.model_name 
FROM ddwx_model_provider p 
LEFT JOIN ddwx_model_info m ON m.provider_id = p.id 
WHERE p.provider_code = 'newapi';
```

---

## 二、配置API Key

### 2.1 进入API Key管理

路径：**系统管理 → API Key管理 → 添加配置**

### 2.2 填写配置信息

| 字段 | 说明 | 示例值 |
|------|------|--------|
| **供应商** | 选择 "NEWAPI中转" | NEWAPI中转 |
| **配置名称** | 自定义名称 | hfsyapi-图片Key |
| **API Key** | hfsyapi.cn平台获取的API Key | sk-xxxxx... |
| **自定义接口地址** | hfsyapi.cn提供的中转地址 | https://api.hfsyapi.cn |
| **认证方式** | 选择认证模式 | bearer / key_only |

### 2.3 认证方式说明

NEWAPI 中转平台支持两种认证方式：

1. **Bearer Token** (`bearer`) - 默认，推荐
   - Header: `Authorization: Bearer <API_KEY>`

2. **Key Only** (`key_only`) - 部分中转平台使用
   - Header: `Authorization: <API_KEY>` (无Bearer前缀)

### 2.4 测试连接

保存后点击"测试连接"验证API Key有效性。

---

## 三、使用图片生成功能

### 3.1 在场景模板中选择模型

1. 进入 **场景管理 → 场景模板**
2. 创建或编辑模板
3. 在模型选择中选择：
   - 供应商：`NEWAPI中转`
   - 模型：`GPT-Image-2` / `GPT-Image-1.5` / `DALL-E-3`

### 3.2 API调用说明

接入后，系统将自动使用以下接口格式：

#### 文生图接口
```
POST {custom_endpoint}/v1/images/generations
Headers:
  Authorization: Bearer {api_key}
  Content-Type: application/json
Body:
{
  "model": "gpt-image-2",
  "prompt": "生成图片的描述",
  "n": 1,
  "size": "1024x1024",
  "quality": "auto"
}
Response:
{
  "data": [{
    "url": "https://...",
    "revised_prompt": "..."
  }]
}
```

#### 图生图接口
```
POST {custom_endpoint}/v1/images/edits
Headers:
  Authorization: Bearer {api_key}
Body: multipart/form-data
- model: gpt-image-2
- prompt: 图片编辑描述
- image: 参考图片文件
Response:
{
  "data": [{
    "url": "https://...",
    "revised_prompt": "..."
  }]
}
```

---

## 四、支持的模型参数

| 参数 | 类型 | 说明 | 示例 |
|------|------|------|------|
| `prompt` | string | 图片描述（必填） | "一只可爱的猫咪" |
| `n` | integer | 生成数量 | 1-10 |
| `size` | string | 图片尺寸 | "1024x1024", "1536x1024", "1024x1536", "auto" |
| `quality` | string | 图片质量 | "auto", "low", "medium", "high" |
| `response_format` | string | 返回格式 | "url", "b64_json" |
| `image` | file | 参考图（图生图时） | 图片文件 |

---

## 五、NEWAPI平台注册

如尚未注册 NEWAPI 平台，请访问：

- **官网**：https://www.hfsyapi.cn
- **文档**：https://www.hfsyapi.cn/docs/api
- **注册账号** → **获取API Key** → **充值额度**

---

## 六、常见问题

### Q1: API Key测试通过但调用失败？

1. 检查 `custom_endpoint` 是否正确（需包含完整URL，如 `https://www.hfsyapi.cn`）
2. 确认账号余额充足
3. 检查模型是否在API Key权限范围内
4. 确认 hfsyapi.cn 后台已开通对应模型

### Q2: 图像生成超时？

1. GPT-Image 系列模型生成可能需要30-120秒
2. 可在场景配置中增加超时时间
3. 确认网络连接稳定

### Q3: 如何切换不同图像生成模型？

在场景模板的模型选择中重新选择即可，系统会根据选择的 `model_code` 自动调用对应模型。

### Q4: 调用时提示 "No available channel for model"？

这是 hfsyapi.cn 平台返回的错误，表示该平台未开通或不支持该模型：
1. 登录 hfsyapi.cn 后台，确认已开通对应模型
2. hfsyapi.cn 平台会将 `dall-e-3` 映射为 `dall-e`，`gpt-image-2-4k` 映射为 `gpt-image-2`
3. 请确认 hfsyapi.cn 后台显示的可用模型名称

---

## 七、技术支持

如有问题，请联系：

- NEWAPI官方文档：https://www.hfsyapi.cn/docs/api
- 模型广场支持团队
