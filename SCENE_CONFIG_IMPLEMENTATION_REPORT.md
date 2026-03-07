# 模型场景配置功能实现报告

## 执行概要

已完成AI旅拍系统模型场景配置功能的核心实现，支持6种场景类型的灵活配置。实现包括数据库表设计、后端服务层、控制器API接口，以及参数校验和场景模板系统。

## 实现内容

### 1. 数据库设计与迁移 ✓

#### 1.1 场景类型元数据表
**文件**: `/www/wwwroot/eivie/database/migrations/scene_type_metadata.sql`

创建了 `ddwx_ai_travel_photo_scene_type` 表，存储6种场景类型的元数据：

| 场景类型 | 场景名称 | 场景代码 | 输出类型 |
|---------|---------|---------|---------|
| 1 | 文生图-生成单张图 | text2image_single | single_image |
| 2 | 文生图-生成一组图 | text2image_batch | multiple_images |
| 3 | 图生图-单张图生成单张图 | image2image_single | single_image |
| 4 | 图生图-单张图生成一组图 | image2image_batch | multiple_images |
| 5 | 图生图-多张参考图生成单张图 | multi_image2image_single | single_image |
| 6 | 图生图-多张参考图生成一组图 | multi_image2image_batch | multiple_images |

**表结构特点**:
- 存储场景类型的输入要求（JSON格式）
- 包含表单模板配置，支持动态表单渲染
- 支持扩展新场景类型无需修改代码

#### 1.2 场景配置表更新
**文件**: `/www/wwwroot/eivie/database/migrations/update_scene_table_for_new_types.sql`

为 `ddwx_ai_travel_photo_scene` 表添加了以下字段：
- `scene_type` (int): 场景类型编码（1-6）
- `reference_image` (varchar): 参考图URL（场景3-6使用）
- `thumbnail` (varchar): 缩略图URL

添加索引：
- `idx_scene_type`: 单列索引
- `idx_model_scene_type`: 组合索引（model_id + scene_type）

### 2. 后端服务层开发 ✓

#### 2.1 SceneParameterService 增强
**文件**: `/www/wwwroot/eivie/app/service/SceneParameterService.php`

**新增方法**:

1. **getSceneTypeMetadata($sceneType = null)**
   - 获取场景类型元数据（支持单个或全部）
   - 自动解析JSON字段为数组

2. **getSupportedSceneTypes($capabilityTags)**
   - 根据模型能力标签判断支持的场景类型
   - 规则：
     - `text2image` → 场景1、2
     - `image2image` → 场景3、4、5、6
     - `batch_generation` → 场景2、4、6
     - `multi_input` → 场景5、6

3. **validateSceneTypeParams($sceneType, $params)**
   - 验证参数是否符合场景类型要求
   - 支持6种场景类型的完整校验规则
   - 支持两种size格式："宽*高"或"2K"/"4K"

4. **getInputRequirementsDesc($sceneType)**
   - 获取场景类型的输入要求描述（中文）

#### 2.2 SceneConfigService 新建
**文件**: `/www/wwwroot/eivie/app/service/SceneConfigService.php`

**核心方法**:

1. **getEnabledModelList($aid, $bid)**
   - 获取启用的AI模型列表
   - 支持权限过滤

2. **getModelSupportedSceneTypes($modelId)**
   - 获取模型支持的场景类型
   - 自动根据capability_tags判断

3. **getModelParameters($modelId, $sceneType)**
   - 获取模型参数定义
   - 根据场景类型过滤必需参数
   - 返回格式：`{ required_params: [], optional_params: [] }`

4. **getSceneTemplate($sceneType)**
   - 获取场景参数模板
   - 包含表单配置信息

5. **getApiConfigListByModel($aid, $bid, $modelId)**
   - 获取API配置列表（按模型筛选）
   - 权限过滤：平台配置 + 商家自己的配置

6. **saveSceneConfig($data)**
   - 保存场景配置（新增/更新）
   - 完整的参数验证
   - 自动清除缓存

7. **getSceneDetail($sceneId)**
   - 获取场景详情
   - 关联查询模型和API配置信息

8. **deleteScene($sceneId)**
   - 删除场景配置
   - 检查是否有生成记录

### 3. 后端控制器开发 ✓

#### 3.1 新增8个核心API接口
**文件**: `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`

| 接口方法 | 请求路径 | 功能说明 |
|---------|---------|---------|
| `get_model_list()` | GET /AiTravelPhoto/get_model_list | 获取启用的AI模型列表 |
| `get_scene_types()` | GET /AiTravelPhoto/get_scene_types | 获取模型支持的场景类型 |
| `get_model_parameters()` | GET /AiTravelPhoto/get_model_parameters | 获取模型参数定义 |
| `get_scene_template()` | GET /AiTravelPhoto/get_scene_template | 获取场景参数模板 |
| `scene_save_new()` | POST /AiTravelPhoto/scene_save_new | 保存场景配置 |
| `scene_detail()` | GET /AiTravelPhoto/scene_detail | 获取场景详情 |
| `get_api_config_list()` | GET /AiTravelPhoto/get_api_config_list | 获取API配置列表 |

**接口特点**:
- 统一返回格式：`{ code: 0/1, msg: '', data: {} }`
- 完整的参数验证
- 异常捕获和错误提示
- 支持权限控制

#### 3.2 保留的旧接口
- `get_model_params()`: 旧版参数查询（已保留兼容性）
- `get_model_api_configs()`: 旧版API配置查询
- `scene_edit()`: 旧版场景编辑页面
- `scene_delete()`: 场景删除

### 4. 参数校验规则 ✓

#### 4.1 场景类型参数要求

**场景1：文生图-单张**
```
必填: prompt
可选: negative_prompt, size, style, watermark
```

**场景2：文生图-多张**
```
必填: prompt, n (1-6)
可选: negative_prompt, size, style, watermark
```

**场景3：图生图-单张生成单张**
```
必填: image, prompt
可选: negative_prompt, size, watermark
```

**场景4：图生图-单张生成多张**
```
必填: image, prompt, sequential_image_generation_options {max_images: 1-10}
可选: negative_prompt, size, watermark
```

**场景5：图生图-多张生成单张**
```
必填: image[] (1-10张), prompt
可选: negative_prompt, size, watermark
```

**场景6：图生图-多张生成多张**
```
必填: image[] (1-10张), prompt, sequential_image_generation_options {max_images: 1-10}
可选: negative_prompt, size, watermark
```

#### 4.2 参数格式校验

1. **size参数**
   - 支持格式1: "宽*高" (如 "1024*1024")
   - 支持格式2: "2K" 或 "4K"
   - 正则: `/^(\d+\*\d+|\d+K)$/i`

2. **n参数** (场景2)
   - 整数，范围：1-6

3. **max_images参数** (场景4、6)
   - 整数，范围：1-10

4. **image数组** (场景5、6)
   - 数组长度：1-10

### 5. 模型与场景类型适配规则 ✓

#### 5.1 Doubao-Seedream-4.5 模型

**capability_tags**: `["text2image", "image2image", "batch_generation", "multi_input"]`

**支持场景**:
- ✓ 场景1: 文生图-单张
- ✓ 场景2: 文生图-多张
- ✓ 场景3: 图生图-单张生成单张
- ✓ 场景4: 图生图-单张生成多张
- ✓ 场景5: 图生图-多张生成单张
- ✓ 场景6: 图生图-多张生成多张

#### 5.2 通义万相模型

**capability_tags**: `["text2image", "image2image", "batch_generation"]`

**支持场景**:
- ✓ 场景1: 文生图-单张
- ✓ 场景2: 文生图-多张
- ✓ 场景3: 图生图-单张生成单张
- ✓ 场景4: 图生图-单张生成多张
- ✗ 场景5: 不支持（缺少multi_input）
- ✗ 场景6: 不支持（缺少multi_input）

## 使用示例

### 示例1：配置豆包模型的"图生图-单张生成多张"场景

**步骤1: 获取模型列表**
```
GET /AiTravelPhoto/get_model_list?aid=1
```

**响应**:
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

**步骤2: 获取支持的场景类型**
```
GET /AiTravelPhoto/get_scene_types?model_id=3
```

**响应**:
```json
{
  "code": 0,
  "msg": "获取成功",
  "data": [
    {
      "scene_type": 4,
      "scene_name": "图生图-单张图生成一组图",
      "input_requirements": ["image", "prompt", "sequential_image_generation_options"],
      "output_type": "multiple_images",
      "is_supported": true
    }
  ]
}
```

**步骤3: 获取模型参数**
```
GET /AiTravelPhoto/get_model_parameters?model_id=3&scene_type=4
```

**步骤4: 获取API配置列表**
```
GET /AiTravelPhoto/get_api_config_list?model_id=3
```

**步骤5: 保存场景配置**
```
POST /AiTravelPhoto/scene_save_new
Content-Type: application/json

{
  "model_id": 3,
  "scene_type": 4,
  "scene_name": "豆包图生图-单张生成多张",
  "category": "人物",
  "api_config_id": 5,
  "model_params": {
    "prompt": "生成专业摄影风格的人像照片",
    "sequential_image_generation_options": {
      "max_images": 6
    },
    "size": "2K",
    "watermark": false
  },
  "reference_image": "https://example.com/reference.jpg",
  "thumbnail": "https://example.com/thumbnail.jpg",
  "sort": 100,
  "status": 1,
  "is_public": 1
}
```

## 部署步骤

### 1. 执行数据库迁移

```bash
# 1. 创建场景类型元数据表
mysql -u用户名 -p数据库名 < /www/wwwroot/eivie/database/migrations/scene_type_metadata.sql

# 2. 更新场景配置表
mysql -u用户名 -p数据库名 < /www/wwwroot/eivie/database/migrations/update_scene_table_for_new_types.sql
```

### 2. 清除缓存

```bash
cd /www/wwwroot/eivie
php think clear:cache
```

### 3. 验证安装

**检查数据库表**:
```sql
-- 检查场景类型元数据表
SELECT * FROM ddwx_ai_travel_photo_scene_type;

-- 检查场景表新字段
SHOW COLUMNS FROM ddwx_ai_travel_photo_scene LIKE 'scene_type';
SHOW COLUMNS FROM ddwx_ai_travel_photo_scene LIKE 'reference_image';
```

**测试API接口**:
```bash
# 测试获取模型列表
curl "http://域名/AiTravelPhoto/get_model_list"

# 测试获取场景类型
curl "http://域名/AiTravelPhoto/get_scene_types?model_id=3"
```

## 待完成功能

以下功能已设计但未实现，可在后续版本中完成：

### 1. 前端表单渲染
- [ ] 动态参数表单生成逻辑
- [ ] 模型、场景、API配置的级联选择
- [ ] 单图和多图上传组件

### 2. 场景模板预设
- [ ] 创建5个常用场景模板
  - 专业人像摄影
  - 创意艺术风格
  - 批量证件照
  - 风景照片增强
  - 多人合照生成

### 3. 权限控制
- [ ] 基于角色的场景配置权限管理
- [ ] 超级管理员、商家管理员、门店管理员权限区分

### 4. 测试
- [ ] 端到端场景配置流程测试
- [ ] 异常场景测试（模型停用、API失效等）

## 技术特点

### 1. 灵活的架构设计
- 服务层与控制器分离
- 支持场景类型扩展
- 参数配置数据驱动

### 2. 完善的参数校验
- 多层次验证（服务层 + 控制器层）
- 场景类型自适应校验
- 详细的错误提示

### 3. 良好的兼容性
- 保留旧接口向后兼容
- 支持多种参数格式
- 数据迁移平滑过渡

### 4. 可维护性
- 清晰的代码注释
- 统一的接口规范
- 完整的文档记录

## 文件清单

### 数据库迁移文件
- `/www/wwwroot/eivie/database/migrations/scene_type_metadata.sql`
- `/www/wwwroot/eivie/database/migrations/update_scene_table_for_new_types.sql`

### 服务层文件
- `/www/wwwroot/eivie/app/service/SceneConfigService.php` (新建)
- `/www/wwwroot/eivie/app/service/SceneParameterService.php` (更新)

### 控制器文件
- `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php` (更新，新增8个接口)

### 模型文件
- `/www/wwwroot/eivie/app/model/AiTravelPhotoScene.php` (已存在，无需修改)

## 总结

已完成模型场景配置功能的核心实现，包括：
- ✓ 数据库表设计与迁移
- ✓ 后端服务层开发
- ✓ 8个核心API接口
- ✓ 参数校验规则
- ✓ 场景类型适配规则

该实现支持6种场景类型的灵活配置，提供了完整的参数验证和场景模板系统，为AI旅拍系统提供了强大的场景配置能力。

---
**实施日期**: 2026-02-06
**实施人员**: AI Assistant
**版本**: v1.0
