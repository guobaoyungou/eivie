---
name: synthesis_auto_ai_generation
overview: 实现自动合成功能：根据模板绑定的模型，使用平台API Key调用AI进行图生图
todos:
  - id: modify_call_ai_model_method
    content: 修改AiTravelPhotoSynthesisService的callAiModel方法，实现实际的AI图生图调用
    status: completed
  - id: add_model_config_lookup
    content: 添加根据model_id获取模型配置的逻辑
    status: completed
    dependencies:
      - modify_call_ai_model_method
  - id: add_api_config_lookup
    content: 添加根据模型获取API配置的逻辑
    status: completed
    dependencies:
      - add_model_config_lookup
---

## 用户需求

实现AI旅拍合成模块的自动图生图功能：

1. 监控当前列表的人像
2. 有新人像后自动传入合成设置里设置的模板
3. 根据模板绑定的模型(model_id)及提示词(prompt)，使用平台的API Key进行图生图
4. 将结果反馈给人像管理

## 当前问题

- `AiTravelPhotoSynthesisService::callAiModel()` 方法只返回原图URL（第141-152行），未实际调用AI生成
- 模板表 `ai_travel_photo_synthesis_template` 有 `model_id` 和 `prompt` 字段
- 系统已有完整的AI调用机制（AiTravelPhotoAiService）

## 核心功能

- 根据模板的model_id获取AI模型配置
- 根据模型获取对应的API配置（api_config表）
- 调用AI图生图API生成图片
- 返回生成的图片URL并保存到result表

## 技术方案

### 技术栈

- PHP + ThinkPHP
- 现有服务：AiTravelPhotoAiService（已有完整的AI调用逻辑）

### 实现思路

参考现有的场景生成流程（AiTravelPhotoAiService::callImageGenerationApi），在合成服务中实现类似的调用逻辑：

1. **获取模型配置**：根据模板的model_id查询ai_model_instance获取模型信息
2. **获取API配置**：根据模型ID查询api_config表获取可用的API配置
3. **构建请求参数**：组装人像URL、模板参考图、提示词等参数
4. **调用AI API**：根据provider类型（aliyun/baidu/openai）调用对应API
5. **处理响应**：解析API返回的生成结果URL，下载并上传到OSS

### 关键代码修改

修改 `/home/www/ai.eivie.cn/app/service/AiTravelPhotoSynthesisService.php` 中的 `callAiModel` 方法：

- 添加获取模型配置的逻辑
- 添加获取API配置的逻辑
- 调用AI图生图API
- 处理返回结果

### 涉及数据表

- `ai_travel_photo_synthesis_template` - 合成模板（已有model_id, prompt, images字段）
- `ai_model_instance` - AI模型实例
- `api_config` - API配置（包含api_key, api_secret, provider等）
- `ai_travel_photo_result` - 生成结果记录