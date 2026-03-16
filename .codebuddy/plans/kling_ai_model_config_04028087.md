---
name: kling_ai_model_config
overview: 通过数据库脚本配置可灵AI供应商及模型完整信息
todos:
  - id: create-kling-sql
    content: 创建可灵AI供应商和模型配置的SQL脚本
    status: completed
  - id: insert-provider
    content: 执行SQL脚本插入供应商数据
    status: completed
    dependencies:
      - create-kling-sql
  - id: verify-config
    content: 验证配置数据是否正确
    status: completed
    dependencies:
      - insert-provider
---

## 用户需求

为可灵AI(KLING)创建完整的模型配置，包括API端点、输入参数、输出格式、价格配置、限制配置，通过数据库脚本方式进行配置。

## 技术背景

- 系统已有模型广场功能(model_provider/model_info表)
- 可灵AI使用JWT认证(access_key + secret_key)
- API基础URL: https://api.klingai.com
- 支持视频生成、图像生成、虚拟试穿等功能

## 核心功能

1. 添加可灵AI供应商配置(认证方式: access_key + secret_key)
2. 配置视频生成模型(Kling-V1/V1.5/V1.6/V2.1/V2.5-turbo/V2.6/V3/O1)
3. 配置图像生成模型(kling-v1/kling-v1.5/kling-v2/kling-v2-1/kling-image-o1)
4. 配置各模型的输入参数规范、输出格式、价格配置、限制配置

## 技术方案

采用数据库迁移脚本方式，将可灵AI供应商和模型配置直接写入数据库。

## 实现方式

1. 创建SQL迁移脚本，插入可灵AI供应商记录
2. 配置供应商认证字段(auth_config): access_key + secret_key
3. 批量插入视频生成模型配置(包含input_schema/output_schema/pricing_config/limits_config)
4. 批量插入图像生成模型配置

## 视频生成模型配置(部分)

- Kling-V3-Omni: 0.6-1.2 点/秒, std/pro模式
- Kling-Video-O1: 0.6-1.2 点/秒
- Kling-V2-6: 1.5-12 点/5-10秒
- Kling-V2-5-Turbo: 1.5-7 点/5-10秒
- Kling-V1-6: 1-10 点/5-10秒, 含Multi-Elements

## 图像生成模型配置

- kling-image-o1: 8 点/张
- kling-v2-1: 4 点/张
- kling-v1.5: 8 点/张
- kling-v1: 1 点/张