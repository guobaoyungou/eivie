# 模型能力调用示例 — 生成任务创建流程改造实施报告

## 实施概述

**实施时间**: 2026-02-27  
**功能**: 为照片生成任务创建页面增加模型能力选择功能

---

## 实施内容

### 1. 数据库设计

#### 新增表: ddwx_model_capability_example
存储模型能力调用示例，用于前端展示和参数预填。

**迁移文件**: `/database/migrations/model_capability_example.sql`

| 字段 | 类型 | 说明 |
|------|------|------|
| id | int | 主键ID |
| aid | int | 平台ID |
| model_id | int | 关联模型ID |
| capability_type | tinyint | 能力类型(1-6) |
| example_name | varchar | 示例名称 |
| request_params | json | 请求参数示例 |
| response_example | json | 响应示例 |
| is_default | tinyint | 是否默认示例 |

#### 表扩展: ddwx_generation_record
新增字段 `capability_type` 记录任务使用的能力类型。

### 2. 后端API实现

#### PhotoGeneration 控制器新增方法

| 方法 | 路径 | 说明 |
|------|------|------|
| `get_model_capabilities()` | GET /PhotoGeneration/get_model_capabilities | 获取模型能力列表 |
| `get_capability_form_schema()` | GET /PhotoGeneration/get_capability_form_schema | 获取能力表单结构 |
| `applyAutoParams()` | (私有方法) | 自动补充系统参数 |

#### 6种能力类型定义

| 类型 | 名称 | 代码 | 输入要求 | 输出类型 |
|------|------|------|---------|---------|
| 1 | 文生图-单张 | text2image_single | 提示词 | 单张图片 |
| 2 | 文生图-组图 | text2image_batch | 提示词+数量 | 多张图片(1-6) |
| 3 | 图生图-单入单出 | image2image_single | 单图+提示词 | 单张图片 |
| 4 | 图生图-单入多出 | image2image_batch | 单图+提示词+数量 | 多张图片(1-10) |
| 5 | 多图入-单出 | multi_image2image_single | 多图+提示词 | 单张图片 |
| 6 | 多图入-多出 | multi_image2image_batch | 多图+提示词+数量 | 多张图片(1-10) |

### 3. 前端页面改造

#### task_create.html 三步式布局

**文件**: `/app/view/photo_generation/task_create.html`

```
步骤1: 选择模型 → 步骤2: 选择能力类型 → 步骤3: 填写参数
```

**主要功能**:
- 模型卡片选择（检测API Key配置状态）
- 能力卡片渲染（根据模型capability_tags动态显示）
- 动态表单生成（根据能力类型显示不同字段）
- 单图/多图上传组件
- 调用示例预填功能
- 任务状态轮询（2秒间隔/60秒超时）

### 4. 预置数据

#### 模型配置
**文件**: `/database/migrations/preset_doubao_seedream_5_0.sql`

预置 `doubao-seedream-5-0-260128` 模型，capability_tags 包含:
- text2image (文生图)
- image2image (图生图)
- batch_generation (批量生成)
- multi_input (多图输入)

#### 能力示例数据
**文件**: `/database/migrations/model_capability_example_data.sql`

为6种能力类型预置调用示例：
1. 橘猫图片生成（文生图-单张）
2. 橘猫系列图生成（文生图-组图）
3. 照片风格转换（图生图-单入单出）
4. 照片多风格生成（图生图-单入多出）
5. 多图融合创作（多图入-单出）
6. 多图融合系列创作（多图入-多出）

---

## 文件清单

### 新增文件
```
database/migrations/
├── model_capability_example.sql          # 能力示例表DDL
├── model_capability_example_data.sql     # 能力示例数据
└── preset_doubao_seedream_5_0.sql        # 预置模型配置
```

### 修改文件
```
app/controller/PhotoGeneration.php        # +452行 新增能力API方法
app/model/GenerationRecord.php            # +27行 新增能力类型常量
app/service/GenerationService.php         # +1行 支持capability_type
app/view/photo_generation/task_create.html # 重写三步式布局
```

---

## 部署步骤

### 1. 执行数据库迁移
```bash
cd /www/wwwroot/eivie

# 创建能力示例表
mysql -uroot -p ddwx < database/migrations/model_capability_example.sql

# 预置模型配置
mysql -uroot -p ddwx < database/migrations/preset_doubao_seedream_5_0.sql

# 插入能力示例数据
mysql -uroot -p ddwx < database/migrations/model_capability_example_data.sql
```

### 2. 清除缓存
```bash
rm -rf /www/wwwroot/eivie/runtime/cache/*
```

### 3. 验证安装
```sql
-- 检查能力示例表
SELECT COUNT(*) FROM ddwx_model_capability_example;
-- 预期: 6条记录

-- 检查模型能力标签
SELECT model_code, capability_tags FROM ddwx_model_info WHERE model_code LIKE 'doubao%';
```

---

## 使用说明

### 访问页面
```
http://域名/?s=/PhotoGeneration/task_create
```

### 操作流程
1. **选择模型**: 点击左侧模型卡片
2. **选择能力**: 在能力卡片区域选择所需能力类型
3. **填写参数**: 
   - 根据能力类型上传图片（图生图/多图生成）
   - 填写提示词
   - 设置输出尺寸、响应格式等可选参数
4. **使用示例**: 点击"使用此示例"按钮预填参数
5. **提交任务**: 点击提交，等待生成结果

### 能力匹配规则

| capability_tag | 激活的能力 |
|----------------|-----------|
| text2image | 能力1（文生图-单张） |
| text2image + batch_generation | 能力2（文生图-组图） |
| image2image | 能力3（图生图-单入单出） |
| image2image + batch_generation | 能力4（图生图-单入多出） |
| multi_input | 能力5（多图入-单出） |
| multi_input + batch_generation | 能力6（多图入-多出） |

---

## 测试验证

### 核心功能测试清单

- [ ] 选择模型后加载能力列表
- [ ] 选择能力1(文生图-单张)表单正确
- [ ] 选择能力3(图生图-单入单出)显示单图上传
- [ ] 选择能力5(多图入单出)显示多图上传
- [ ] 点击"使用此示例"预填参数
- [ ] 提交任务返回record_id
- [ ] 轮询状态正常显示
- [ ] 生成成功弹出结果提示

---

## 版本信息

- **版本号**: v1.0.0
- **创建时间**: 2026-02-27
- **依赖版本**: ThinkPHP 6.0.7+, PHP 7.4+, MySQL 5.7+
