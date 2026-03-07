# 模型场景配置功能 - 快速开始指南

## 概述

本指南帮助您快速部署和使用AI旅拍系统的模型场景配置功能。该功能支持6种场景类型的灵活配置，实现一个模型对应多个应用场景。

## 前置条件

- PHP 7.4+
- MySQL 5.7+
- ThinkPHP 6.0+
- 已安装AI旅拍系统基础模块

## 部署步骤

### 步骤1: 执行数据库迁移

```bash
cd /www/wwwroot/eivie

# 1. 创建场景类型元数据表
mysql -u用户名 -p数据库名 < database/migrations/scene_type_metadata.sql

# 2. 更新场景配置表
mysql -u用户名 -p数据库名 < database/migrations/update_scene_table_for_new_types.sql
```

**验证安装**:
```sql
USE ddwx;

-- 检查场景类型表
SELECT scene_type, scene_name, output_type FROM ddwx_ai_travel_photo_scene_type;

-- 预期结果：6条记录
-- 场景1-6的定义应全部存在
```

### 步骤2: 确认模型配置

确保 `ddwx_ai_model_instance` 表中有可用的AI模型，并正确设置 `capability_tags`：

```sql
-- 查看已配置的模型
SELECT id, model_code, model_name, capability_tags, is_active 
FROM ddwx_ai_model_instance 
WHERE is_active = 1;

-- 豆包SeeDream 4.5模型示例
-- capability_tags 应为: ["text2image", "image2image", "batch_generation", "multi_input"]
```

### 步骤3: 配置API密钥

确保 `ddwx_api_config` 表中有可用的API配置：

```sql
-- 查看API配置
SELECT id, api_name, provider, model_id, is_active 
FROM ddwx_api_config 
WHERE is_active = 1;

-- 如果没有，需要添加API配置：
INSERT INTO ddwx_api_config (aid, bid, model_id, api_code, api_name, provider, api_key, endpoint_url, is_active, create_time, update_time)
VALUES (0, 0, 3, 'doubao_api_1', '豆包API配置1', 'doubao', 'YOUR_API_KEY', 'https://ark.cn-beijing.volces.com/api/v3/images/generations', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
```

### 步骤4: 清除缓存

```bash
cd /www/wwwroot/eivie
php think clear:cache
```

## API接口使用

### 1. 获取模型列表

**请求**:
```
GET /AiTravelPhoto/get_model_list
```

**响应示例**:
```json
{
  "code": 0,
  "msg": "获取成功",
  "data": [
    {
      "id": 3,
      "model_code": "doubao-seedream-4-5-251128",
      "model_name": "豆包SeeDream 4.5图生图",
      "provider": "doubao",
      "capability_tags": ["text2image", "image2image", "batch_generation", "multi_input"]
    }
  ]
}
```

### 2. 获取场景类型

**请求**:
```
GET /AiTravelPhoto/get_scene_types?model_id=3
```

**响应示例**:
```json
{
  "code": 0,
  "msg": "获取成功",
  "data": [
    {
      "scene_type": 1,
      "scene_name": "文生图-生成单张图",
      "description": "根据文本提示词生成单张图片",
      "input_requirements": ["prompt"],
      "output_type": "single_image",
      "is_supported": true
    },
    {
      "scene_type": 4,
      "scene_name": "图生图-单张图生成一组图",
      "description": "参考单张图片生成多张新图片（1-10张）",
      "input_requirements": ["image", "prompt", "sequential_image_generation_options"],
      "output_type": "multiple_images",
      "is_supported": true
    }
  ]
}
```

### 3. 获取模型参数

**请求**:
```
GET /AiTravelPhoto/get_model_parameters?model_id=3&scene_type=4
```

**响应示例**:
```json
{
  "code": 0,
  "msg": "获取成功",
  "data": {
    "required_params": [
      {
        "param_name": "prompt",
        "param_label": "提示词",
        "param_type": "textarea",
        "description": "描述想要生成的图像"
      }
    ],
    "optional_params": [
      {
        "param_name": "size",
        "param_label": "输出尺寸",
        "param_type": "select",
        "enum_options": ["2K", "4K", "2048*2048"],
        "default_value": "2K"
      }
    ]
  }
}
```

### 4. 保存场景配置

**请求**:
```
POST /AiTravelPhoto/scene_save_new
Content-Type: application/json

{
  "model_id": 3,
  "scene_type": 4,
  "scene_name": "专业人像-批量生成",
  "category": "人物",
  "api_config_id": 5,
  "model_params": {
    "prompt": "专业摄影风格的人像照片，高清细节",
    "sequential_image_generation_options": {
      "max_images": 6
    },
    "size": "2K",
    "watermark": false
  },
  "sort": 100,
  "status": 1,
  "is_public": 1
}
```

**响应示例**:
```json
{
  "code": 0,
  "msg": "保存成功",
  "data": {
    "scene_id": 123
  }
}
```

### 5. 获取场景详情

**请求**:
```
GET /AiTravelPhoto/scene_detail?id=123
```

**响应示例**:
```json
{
  "code": 0,
  "msg": "获取成功",
  "data": {
    "id": 123,
    "scene_type": 4,
    "scene_name": "专业人像-批量生成",
    "model_id": 3,
    "model_name": "豆包SeeDream 4.5图生图",
    "api_config_id": 5,
    "api_name": "豆包API配置1",
    "model_params": {
      "prompt": "专业摄影风格的人像照片，高清细节",
      "sequential_image_generation_options": {
        "max_images": 6
      },
      "size": "2K"
    }
  }
}
```

## 场景类型配置说明

### 场景1：文生图-生成单张图

**适用场景**: 根据文本描述生成一张图片

**必填参数**:
- `prompt`: 提示词

**可选参数**:
- `negative_prompt`: 负面提示词
- `size`: 输出尺寸（默认：2K）
- `style`: 图像风格
- `watermark`: 是否添加水印

**配置示例**:
```json
{
  "model_id": 3,
  "scene_type": 1,
  "scene_name": "创意海报生成",
  "model_params": {
    "prompt": "科技感海报，未来城市背景",
    "size": "4K",
    "watermark": false
  }
}
```

### 场景4：图生图-单张图生成一组图

**适用场景**: 参考一张图片生成多张相似图片（如证件照批量生成）

**必填参数**:
- `image`: 参考图URL
- `prompt`: 提示词
- `sequential_image_generation_options.max_images`: 生成数量（1-10）

**可选参数**:
- `negative_prompt`: 负面提示词
- `size`: 输出尺寸
- `watermark`: 是否添加水印

**配置示例**:
```json
{
  "model_id": 3,
  "scene_type": 4,
  "scene_name": "证件照批量生成",
  "reference_image": "https://example.com/portrait.jpg",
  "model_params": {
    "prompt": "标准证件照，白色背景",
    "sequential_image_generation_options": {
      "max_images": 6
    },
    "size": "2K"
  }
}
```

### 场景6：图生图-多张参考图生成一组图

**适用场景**: 融合多张参考图生成多张新图片

**必填参数**:
- `image[]`: 参考图URL数组（1-10张）
- `prompt`: 提示词
- `sequential_image_generation_options.max_images`: 生成数量（1-10）

**可选参数**:
- `negative_prompt`: 负面提示词
- `size`: 输出尺寸
- `watermark`: 是否添加水印

**配置示例**:
```json
{
  "model_id": 3,
  "scene_type": 6,
  "scene_name": "多人合照生成",
  "model_params": {
    "image": [
      "https://example.com/person1.jpg",
      "https://example.com/person2.jpg",
      "https://example.com/person3.jpg"
    ],
    "prompt": "专业集体照，正式场合",
    "sequential_image_generation_options": {
      "max_images": 3
    },
    "size": "4K"
  }
}
```

## 常见问题

### 1. 场景类型表数据为空

**问题**: 查询 `ddwx_ai_travel_photo_scene_type` 表无数据

**解决方案**:
```bash
# 重新执行场景类型元数据表迁移脚本
mysql -u用户名 -p数据库名 < database/migrations/scene_type_metadata.sql
```

### 2. 获取场景类型接口返回空数组

**原因**: 模型的 `capability_tags` 未配置或配置错误

**解决方案**:
```sql
-- 更新模型能力标签
UPDATE ddwx_ai_model_instance 
SET capability_tags = '["text2image", "image2image", "batch_generation", "multi_input"]'
WHERE id = 3;
```

### 3. 保存场景配置提示"模型不存在或已禁用"

**解决方案**:
```sql
-- 检查模型状态
SELECT id, model_name, is_active FROM ddwx_ai_model_instance WHERE id = 3;

-- 启用模型
UPDATE ddwx_ai_model_instance SET is_active = 1 WHERE id = 3;
```

### 4. 参数验证失败

**常见错误**:
- `n参数取值范围为1-6`: 场景2的n参数超出范围
- `sequential_image_generation_options.max_images必须为1-10之间的整数`: 场景4/6的max_images参数错误
- `image（参考图数组）数量必须为1-10张`: 场景5/6的image数组长度不符合要求

**解决方案**: 检查参数格式，确保符合场景类型要求

## 扩展开发

### 添加新的场景类型

1. **插入场景类型元数据**:
```sql
INSERT INTO ddwx_ai_travel_photo_scene_type 
(scene_type, scene_name, scene_code, description, input_requirements, output_type, form_template, sort, is_active, create_time, update_time)
VALUES 
(7, '视频生成-首帧', 'image2video', '基于首帧图片生成动态视频', 
 '["image", "prompt"]', 'video', 
 '{"required_params": ["image", "prompt"], "optional_params": ["duration", "aspect_ratio"]}', 
 70, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
```

2. **更新模型能力标签**:
```sql
UPDATE ddwx_ai_model_instance 
SET capability_tags = JSON_ARRAY_APPEND(capability_tags, '$', 'image2video')
WHERE model_code = 'doubao-seedream-4-5-251128';
```

3. **无需修改代码**: 系统会自动识别新场景类型

## 后续功能

以下功能已设计但未实现，可在后续版本中完成：

- [ ] 前端表单动态渲染
- [ ] 模型、场景、API配置的级联选择UI
- [ ] 单图和多图上传组件
- [ ] 5个常用场景模板预设
- [ ] 基于角色的权限控制
- [ ] 端到端集成测试

## 技术支持

如有问题，请参考以下文档：

- **完整实现报告**: `/www/wwwroot/eivie/SCENE_CONFIG_IMPLEMENTATION_REPORT.md`
- **设计文档**: 查看项目根目录的设计文档
- **日志位置**: `/www/wwwroot/eivie/runtime/log/`

---
**版本**: v1.0  
**最后更新**: 2026-02-06
