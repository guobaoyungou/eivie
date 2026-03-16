---
name: add_kling_models
overview: 在模型广场中添加11个可灵视频模型
todos:
  - id: add-kling-provider
    content: 在 model_provider 表中添加可灵供应商记录
    status: completed
  - id: add-kling-models
    content: 在 model_info 表中添加11个可灵视频模型
    status: completed
    dependencies:
      - add-kling-provider
---

## 用户需求

在模型广场中添加以下11个可灵视频模型：

1. kling-video-o1
2. kling-v3-omni
3. kling-v1
4. kling-v1-5
5. kling-v1-6
6. kling-v2-master
7. kling-v2-1
8. kling-v2-1-master
9. kling-v2-5-turbo
10. kling-v2-6
11. kling-v3

## 现有代码分析

- 模型广场使用 ddwx_model_provider（供应商表）、ddwx_model_type（类型表）、ddwx_model_info（模型信息表）
- 可灵已有 KlingAIService.php 服务，使用 JWT 认证（access_key, secret_key）
- 模型类型 "video_generation"（视频生成）已存在于预置数据中
- 现有6个预置供应商：volcengine, aliyun, tencent, baidu, openai, zhipu
- 现有模型类型：deep_thinking, text_generation, video_generation, image_generation, speech_model, embedding

## 核心功能

- 添加可灵（Kling）供应商到模型供应商表
- 添加11个可灵视频模型到模型信息表，关联到视频生成类型

## 技术方案

使用数据库插入方式添加供应商和模型数据。

## 实现步骤

1. 在 ddwx_model_provider 表中添加可灵供应商记录

- provider_code: kling
- provider_name: 可灵AI
- auth_config: 包含 access_key 和 secret_key 字段的JSON配置

2. 在 ddwx_model_info 表中添加11个可灵视频模型

- 关联可灵供应商ID和视频生成类型ID
- 每个模型设置 model_code、model_name、description、task_type 等字段

## 认证配置

可灵使用 JWT 认证，需要在 auth_config 中配置：

```
{"fields":[{"name":"access_key","label":"AccessKey","type":"text","required":true},{"name":"secret_key","label":"SecretKey","type":"password","required":true}]}
```

## 目录结构

此任务为数据库操作，无需修改代码文件。