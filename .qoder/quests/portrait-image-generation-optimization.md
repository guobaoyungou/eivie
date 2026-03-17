# 旅拍人像监控合成与详情反馈优化设计

## 1. 概述

旅拍人像管理中，开启监控后，人像传入已选模板进行图生图功能存在多处缺陷，导致合成失败且结果无法反馈到人像详情页。本设计文档分析根因并提出修复方案。

### 1.1 问题分析

经过代码分析，识别出以下核心问题：

| 序号 | 问题 | 影响 | 涉及位置 |
|------|------|------|----------|
| P1 | "查看详情"按钮链接到 `portrait_to_order`，该方法要求必须存在已支付订单 | 合成完成后无法查看生成结果 | portrait_list.html 操作列 |
| P2 | 控制器缺失 `portrait_detail` 方法 | portrait_detail.html 视图存在但无法被访问 | AiTravelPhoto 控制器 |
| P3 | `synthesis_retry` 查询模板时字段不足，缺少 `prompt`、`reference_image` 等关键字段 | AI模型调用缺少必要参数导致图生图失败 | AiTravelPhoto 控制器 synthesis_retry 方法 |
| P4 | `SynthesisService::saveResult` 未保存 `bid`、`scene_id`、场景名称等关键信息，且 `generation_id` 固定为 0 | 结果与生成任务无法关联，详情页无法显示场景名 | AiTravelPhotoSynthesisService |
| P5 | 合成流程未在 `ai_travel_photo_generation` 表创建生成记录 | 任务统计视图无法追踪合成任务，无法关联到结果 | AiTravelPhotoSynthesisService::generate |
| P6 | `synthesis_batch_generate` 同样存在模板查询字段缺失问题 | 批量合成功能同样失败 | AiTravelPhoto 控制器 |

### 1.2 业务流程现状

```mermaid
sequenceDiagram
    participant 前端 as portrait_list.html
    participant 监控 as 监控轮询
    participant 控制器 as AiTravelPhoto
    participant 服务 as SynthesisService
    participant AI as AI模型API
    participant DB as 数据库

    前端->>前端: 点击"开启监控"
    loop 每3秒轮询
        监控->>控制器: POST synthesis_get_pending
        控制器->>DB: 查询 synthesis_status=0/1 的人像
        DB-->>控制器: 返回待处理人像列表
        控制器-->>监控: 返回人像列表
        监控->>控制器: POST synthesis_retry(portrait_id)
        控制器->>DB: 查询合成设置(template_ids)
        控制器->>DB: 查询模板(字段不全❌)
        控制器->>服务: generate(portrait, templates)
        服务->>AI: callAiModel(缺少prompt/reference_image❌)
        AI-->>服务: 调用失败❌
        服务-->>控制器: 返回失败
        控制器->>DB: 更新 synthesis_status=4
    end
    前端->>前端: 点击"查看详情"
    前端->>控制器: portrait_to_order(id)
    控制器->>DB: 查询订单(不存在)
    控制器-->>前端: 错误：暂无订单信息❌
```

## 2. 架构设计

### 2.1 修复后的目标流程

```mermaid
sequenceDiagram
    participant 前端 as portrait_list.html
    participant 监控 as 监控轮询
    participant 控制器 as AiTravelPhoto
    participant 服务 as SynthesisService
    participant AI as AI模型API
    participant DB as 数据库

    前端->>前端: 点击"开启监控"
    loop 每3秒轮询
        监控->>控制器: POST synthesis_get_pending
        控制器->>DB: 查询 synthesis_status=0/1 的人像
        DB-->>控制器: 返回待处理人像列表
        控制器-->>监控: 返回人像列表
        监控->>控制器: POST synthesis_retry(portrait_id)
        控制器->>DB: 查询合成设置
        控制器->>DB: 查询模板(完整字段✅)
        控制器->>服务: generate(portrait, templates)
        服务->>DB: 创建generation记录✅
        服务->>AI: callAiModel(完整参数✅)
        AI-->>服务: 返回生成结果URL
        服务->>DB: 保存result(关联generation_id/bid/scene_name✅)
        服务-->>控制器: 返回成功
        控制器->>DB: 更新synthesis_status=3
    end
    前端->>前端: 点击"查看详情"
    前端->>控制器: portrait_detail(id)✅
    控制器->>DB: 查询人像+关联结果
    控制器-->>前端: 渲染portrait_detail视图✅
```

### 2.2 涉及修改的模块关系

```mermaid
graph TD
    A[portrait_list.html<br>人像列表页] -->|查看详情| B[portrait_detail 控制器方法<br>新增]
    B --> C[portrait_detail.html<br>人像详情视图]
    
    A -->|开启监控| D[synthesis_get_pending]
    D --> E[synthesis_retry]
    E --> F[synthesis_batch_generate]
    
    E --> G[SynthesisService::generate<br>修复]
    F --> G
    
    G --> H[createGenerationRecord<br>新增]
    G --> I[callAiModel<br>参数修复]
    G --> J[saveResult<br>字段修复]
    
    H --> K[(generation表)]
    I --> L[AI模型API]
    J --> M[(result表)]
    
    B --> M
    B --> K

    style B fill:#90EE90
    style H fill:#90EE90
    style I fill:#FFD700
    style J fill:#FFD700
    style E fill:#FFD700
    style F fill:#FFD700
```

## 3. API 端点参考

### 3.1 新增接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | AiTravelPhoto/portrait_detail?id={portrait_id} | 人像详情页（含生成结果列表） |

#### portrait_detail 请求/响应

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | 是 | 人像ID |

该接口为页面渲染接口，返回 HTML 视图，需向视图传递以下变量：

| 视图变量 | 类型 | 来源 | 说明 |
|----------|------|------|------|
| portrait | array | ai_travel_photo_portrait 表 | 人像基本信息 |
| results | array | ai_travel_photo_result 表 LEFT JOIN generation 表 | 关联的所有生成结果，附带场景名/模板名 |

### 3.2 已有接口修复

| 方法 | 路径 | 修复内容 |
|------|------|----------|
| POST | AiTravelPhoto/synthesis_retry | 补全模板查询字段 |
| POST | AiTravelPhoto/synthesis_batch_generate | 补全模板查询字段 |

#### synthesis_retry / synthesis_batch_generate 模板查询字段对比

| 字段 | 当前 | 修复后 | callAiModel 使用方式 |
|------|------|--------|---------------------|
| id | ✅ | ✅ | 模板标识 |
| aid | ✅ | ✅ | 商户ID |
| bid | ✅ | ✅ | 门店ID |
| template_name | ✅ | ✅ | 模板名称 |
| model_id | ✅ | ✅ | 绑定AI模型ID |
| cover_image | ✅ | ✅ | 作为参考图传入AI模型 |
| default_params | ✅ | ✅ | 提取prompt等参数 |
| output_quantity | ✅ | ✅ | 输出数量 |
| description | ❌ | ✅ | 场景描述用于补充提示词 |
| category | ❌ | ✅ | 分类标签 |

## 4. 数据模型

### 4.1 现有表结构（无需变更）

本次修复不涉及表结构变更，只涉及数据写入逻辑的修正。涉及的表关系如下：

```mermaid
erDiagram
    PORTRAIT ||--o{ GENERATION : "1:N"
    PORTRAIT ||--o{ RESULT : "1:N"
    GENERATION ||--o{ RESULT : "1:N"
    TEMPLATE ||--o{ GENERATION : "模板关联"
    PORTRAIT ||--o| QRCODE : "1:1"

    PORTRAIT {
        int id PK
        int aid
        int bid
        string original_url
        string cutout_url
        string thumbnail_url
        int synthesis_status
        int synthesis_count
        string synthesis_error
    }

    GENERATION {
        int id PK
        int portrait_id FK
        int scene_id
        int template_id
        int generation_type
        int status
        string prompt
        string error_msg
    }

    RESULT {
        int id PK
        int generation_id FK
        int portrait_id FK
        int scene_id
        int aid
        string url
        string thumbnail_url
        int type
        int status
    }

    TEMPLATE {
        int id PK
        int aid
        int bid
        string template_name
        int model_id
        string cover_image
        json default_params
        string description
    }

    QRCODE {
        int id PK
        int portrait_id FK
        int bid
    }
```

### 4.2 saveResult 字段修正

| 字段 | 当前值 | 修正值 | 说明 |
|------|--------|--------|------|
| aid | portrait.aid | portrait.aid | 不变 |
| bid | 未写入 | portrait.bid | 补充门店ID |
| portrait_id | 正确 | 正确 | 不变 |
| generation_id | 固定为 0 | 实际 generation 记录ID | 关联到具体生成记录 |
| scene_id | 未写入 | template.id | 使用模板ID作为场景标识 |
| url | 正确 | 正确 | 不变 |
| type | 1 | 1 | 不变 |
| status | 1 | 1 | 不变 |
| desc | 未写入 | template.template_name | 记录来源模板名称，供详情页展示 |

## 5. 业务逻辑层

### 5.1 新增：portrait_detail 控制器方法

**功能**：根据人像ID加载人像信息和关联的所有生成结果，渲染 portrait_detail.html 视图。

**处理流程**：

```mermaid
flowchart TD
    A[接收 portrait_id 参数] --> B{参数校验}
    B -->|无效| C[返回错误提示]
    B -->|有效| D[查询人像记录]
    D --> E{人像是否存在}
    E -->|否| F[返回人像不存在错误]
    E -->|是| G[查询关联的生成结果]
    G --> H[result表 LEFT JOIN generation表<br>获取场景名/模板名]
    H --> I[按类型分组：照片/视频]
    I --> J[传递 portrait + results 到视图]
    J --> K[渲染 portrait_detail.html]
```

**查询结果的关联逻辑**：
- 从 result 表按 portrait_id 查询所有 status=1 的记录
- LEFT JOIN generation 表获取模板信息（若 generation_id > 0）
- 对每条结果，尝试从 generation 关联的 template 获取场景名，回退使用 result.desc 字段

### 5.2 修复：SynthesisService::generate 合成流程

**修复目标**：在调用 AI 模型前创建 generation 记录，并在保存结果时关联该记录。

**修复后处理流程**：

```mermaid
flowchart TD
    A[接收 portrait + templates] --> B[遍历模板列表]
    B --> C[为每个模板创建 generation 记录<br>状态=处理中]
    C --> D[调用 callAiModel<br>传入完整参数]
    D --> E{调用是否成功}
    E -->|成功| F[保存 result 记录<br>关联 generation_id]
    E -->|失败| G[更新 generation 状态=失败<br>记录 error_msg]
    F --> H[更新 generation 状态=成功]
    H --> I[继续下一个模板]
    G --> I
    I --> J{所有模板处理完毕}
    J --> K[返回汇总结果]
```

**generation 记录创建参数**：

| 字段 | 值来源 |
|------|--------|
| aid | portrait.aid |
| portrait_id | portrait.id |
| scene_id | 0（合成模式不使用旧场景） |
| template_id | template.id |
| bid | portrait.bid |
| type | 1（商家自动生成） |
| generation_type | 1（图生图） |
| prompt | 从 template.default_params 提取，或 template.description |
| status | 1（处理中） |
| create_time | 当前时间戳 |

### 5.3 修复：synthesis_retry 模板查询

**修复目标**：补全查询字段，确保 callAiModel 能获取到完整参数。

**当前查询字段**（不完整）：
`id, aid, bid, template_name, model_id, cover_image, default_params, output_quantity`

**修复后查询字段**（补全）：
`id, aid, bid, template_name, model_id, cover_image, default_params, output_quantity, description, category`

同样的修复应用于 `synthesis_batch_generate` 方法。

### 5.4 修复：portrait_list.html 查看详情跳转

**当前行为**：点击"查看详情"跳转到 `portrait_to_order`（需要已支付订单）

**修复后行为**：点击"查看详情"跳转到 `portrait_detail`（直接查看人像及生成结果）

**操作列模板修改**：

| 元素 | 当前目标 | 修复后目标 |
|------|----------|-----------|
| 查看详情按钮 | portrait_to_order（需要订单） | portrait_detail（直接查看结果） |
| 重试按钮 | synthesis_retry | 不变 |
| 删除按钮 | deletePortrait | 不变 |

### 5.5 修复：callAiModel 参数提取

`callAiModel` 方法从模板数据中提取关键参数的优先级逻辑需要确保对 `generation_scene_template` 表的数据兼容：

**参考图提取优先级**：
1. `template.cover_image` — 场景模板的封面图作为风格参考
2. `template.reference_image` — 场景表的参考图（旧方式兼容）
3. `template.images` — 合成模板的图片列表（JSON）

**提示词提取优先级**：
1. `template.prompt` — 直接prompt字段
2. `template.default_params.prompt` — 从JSON参数中提取
3. `template.description` — 回退使用模板描述
4. 默认提示词 — "生成一张高质量旅拍照片"

## 6. 测试策略

### 6.1 单元测试

| 测试场景 | 验证点 | 预期结果 |
|----------|--------|----------|
| 模板查询字段完整性 | synthesis_retry 查询的模板包含 description、category 字段 | 查询结果中字段非空 |
| generation 记录创建 | 合成时在 generation 表插入记录 | generation 表有对应 portrait_id + template_id 的记录 |
| result 记录关联 | saveResult 写入正确的 generation_id 和 bid | result.generation_id > 0 且 result.bid = portrait.bid |
| callAiModel 参数提取 | default_params 中的 prompt 能被正确解析 | 调用 AI API 时 prompt 参数非空 |
| portrait_detail 查询 | 根据 portrait_id 查询到关联 results | 返回的 results 数组包含所有生成结果 |
| 监控轮询完整流程 | 人像状态从 0→2→3 正常流转 | synthesis_status 最终为 3，result 表有记录 |
| 合成失败重试 | 状态为 4 的人像可以重新触发合成 | 重试后状态更新为 3 或 4 |
| 批量合成一致性 | synthesis_batch_generate 使用修复后的模板查询 | 批量合成与单个重试行为一致 |
| 查看详情跳转 | 点击"查看详情"访问 portrait_detail | 页面正常渲染，显示人像信息和结果列表 |

### 6.2 集成测试流程

```mermaid
flowchart TD
    A[准备测试数据] --> B[创建合成设置<br>关联2个照片场景模板]
    B --> C[上传1个测试人像]
    C --> D[开启监控]
    D --> E[等待监控轮询触发合成]
    E --> F{合成是否成功}
    F -->|成功| G[验证 generation 表有记录]
    F -->|失败| H[检查日志中的错误信息<br>验证 error_msg 正确记录]
    G --> I[验证 result 表有记录且关联正确]
    I --> J[点击查看详情]
    J --> K[验证 portrait_detail 页面<br>显示人像和结果]
    K --> L[验证照片/视频分组正确]
    H --> M[点击重试按钮]
    M --> N[验证重试流程正常]
```

























































































































