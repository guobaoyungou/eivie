# 场景模板使用次数与每单输出数量设置

## 1. 概述

### 1.1 功能目标
为场景模板（ddwx_generation_scene_template）增加两个新的设置项：
- **使用次数**：管理员设置初始值，每次调用模板系统自动累加1
- **每单输出数量**：设置单次生成任务默认产出的成果数量，上限受绑定模型能力限制

### 1.2 业务价值
| 价值点 | 说明 |
|--------|------|
| 数据统计 | 准确追踪每个模板的使用热度，支持运营决策 |
| 成本控制 | 通过每单输出数量控制资源消耗 |
| 用户体验 | 保持生成结果的一致性，用户对输出数量有明确预期 |

---

## 2. 架构设计

### 2.1 数据流概览

``mermaid
flowchart TB
    subgraph 后台管理
        A[管理员编辑模板] --> B[设置初始使用次数/输出数量]
        B --> C[保存至数据库]
    end
    
    subgraph 任务转模板
        D[生成任务完成] --> E[获取输出成果数量]
        E --> F[自动填充output_quantity]
        F --> G[use_count初始化为0]
    end
    
    subgraph 前端使用
        H[用户选择模板] --> I[校验output_quantity与模型能力]
        I --> J[创建生成任务]
        J --> K[按output_quantity生成]
        K --> L[use_count += 1]
    end
    
    C --> H
    G --> C
```

### 2.2 组件交互

``mermaid
sequenceDiagram
    participant Admin as 后台管理员
    participant UI as 场景编辑页面
    participant Controller as PhotoGeneration/VideoGeneration
    participant Service as GenerationService
    participant DB as 数据库
    participant API as 前端API

    Note over Admin,UI: 场景一：后台编辑模板
    Admin->>UI: 打开模板编辑
    UI->>Controller: 请求模板详情
    Controller->>DB: 查询模板+关联模型能力
    DB-->>Controller: 返回含use_count/output_quantity/模型max_output
    Controller-->>UI: 渲染表单（输出数量上限=模型能力）
    Admin->>UI: 设置初始使用次数/输出数量
    UI->>Controller: 提交保存(scene_save)
    Controller->>Service: saveTemplate()
    Service->>Service: 校验output_quantity≤模型max_output
    Service->>DB: 更新字段
    
    Note over Admin,API: 场景二：生成任务转模板
    Controller->>Service: createFromRecord()
    Service->>DB: 查询源记录输出数量
    DB-->>Service: output_count
    Service->>DB: 写入output_quantity=output_count, use_count=0
    
    Note over API,DB: 场景三：前端使用模板
    API->>DB: 查询模板
    API->>DB: 创建生成任务(按output_quantity)
    API->>DB: use_count += 1
```

---

## 3. 数据模型设计

### 3.1 新增字段定义

**表名**：ddwx_generation_scene_template

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| use_count | int(11) | 0 | 使用次数，管理员可设置初始值，每次调用模板自动+1 |
| output_quantity | int(11) | 1 | 每单输出数量，受绑定模型的max_output能力限制 |

### 3.2 字段约束规则

| 字段 | 约束 | 校验时机 |
|------|------|----------|
| use_count | ≥ 0，整数 | 保存时校验 |
| output_quantity | ≥ 1，≤ 绑定模型的max_output | 保存时联动校验 |

### 3.3 模型能力联动

``mermaid
flowchart LR
    A[模板绑定模型] --> B[查询model_info.capability_tags]
    B --> C{解析max_output能力}
    C --> D[output_quantity上限 = max_output]
    D --> E[前端表单动态限制]
    D --> F[后端保存时校验]
```

**模型能力解析规则**：
- 从model_info表的capability_tags或input_schema中提取n参数的max值
- 若模型支持batch_generation，取n.max作为output_quantity上限
- 若模型不支持批量生成，output_quantity固定为1

---

## 4. 后台管理界面

### 4.1 表单布局

在"显示与购买条件"区块之前，新增"生成设置"区块：

```
┌─────────────────────────────────────────────────────────┐
│  ◆ 生成设置                                              │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  使用次数        [_______]  次                           │
│                  ⓘ 初始值由管理员设置，每次调用自动+1     │
│                                                          │
│  每单输出数量    [_______]  张/个   (上限: 6)            │
│                  ⓘ 受绑定模型能力限制，当前模型最大支持6  │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### 4.2 交互规则

| 场景 | 交互行为 |
|------|----------|
| 新增模板 | use_count默认0，output_quantity默认1 |
| 编辑模板 | 显示当前累计使用次数，可手动修改（用于数据修正） |
| 任务转模板 | use_count初始化为0，output_quantity自动填充为源任务的输出成果数量 |
| 选择/切换模型 | 动态更新output_quantity的max属性，超出时自动调整 |
| 表单校验 | use_count≥0，output_quantity在1~模型上限范围内 |

### 4.3 列表展示

在模板列表中增加列显示：

| 字段 | 显示规则 |
|------|----------|
| 使用次数 | 显示累计数字，如"123次" |
| 每单输出 | 显示数字+单位，如"4张"（照片）或"4个"（视频） |

---

## 5. 业务逻辑设计

### 5.1 任务转模板时的默认值处理

当生成任务转换为场景模板时：

``mermaid
flowchart LR
    A[源生成记录] --> B[查询generation_output表]
    B --> C[统计output_count]
    C --> D[设置output_quantity=output_count]
    D --> E[use_count初始化为0]
    E --> F[创建模板记录]
```

### 5.2 模板使用时的计数逻辑

``mermaid
flowchart TD
    A[请求使用模板] --> B[创建生成任务]
    B --> C[生成数量=output_quantity]
    C --> D[任务提交成功]
    D --> E[use_count += 1]
```

### 5.3 output_quantity与模型能力联动校验

``mermaid
flowchart TD
    A[保存模板] --> B[获取绑定的model_id]
    B --> C[查询model_info]
    C --> D{解析capability_tags}
    D --> E[提取max_output值]
    E --> F{output_quantity ≤ max_output?}
    F -->|是| G[保存成功]
    F -->|否| H[返回错误：超出模型能力上限]
```

### 5.4 Service层方法扩展

**GenerationService** 需扩展以下方法：

| 方法 | 变更内容 |
|------|----------|
| saveTemplate() | 增加use_count、output_quantity字段保存，校验output_quantity与模型能力 |
| createFromRecord() | output_quantity默认取源记录的输出数量，use_count初始化为0 |

**新增方法**：

| 方法名 | 功能 | 参数 | 返回 |
|--------|------|------|------|
| getModelMaxOutput() | 获取模型的最大输出数量能力 | model_id | int |
| incrementTemplateUsage() | 增加模板使用计数 | template_id | bool |

---

## 6. 接口变更

### 6.1 scene_edit() 响应扩展

模板编辑接口返回数据增加字段：

| 字段 | 类型 | 说明 |
|------|------|------|
| use_count | int | 使用次数（累计值） |
| output_quantity | int | 每单输出数量 |
| model_max_output | int | 绑定模型的最大输出能力（用于前端限制） |

### 6.2 scene_save() 请求参数扩展

| 参数 | 类型 | 必填 | 校验规则 |
|------|------|------|----------|
| info[use_count] | int | 否 | ≥0，默认0 |
| info[output_quantity] | int | 否 | 1~模型max_output，默认1 |

### 6.3 前端API扩展

**scene_template_list / scene_template_detail** 响应增加字段：

| 字段 | 类型 | 说明 |
|------|------|------|
| use_count | int | 累计使用次数 |
| output_quantity | int | 每单输出数量 |

---

## 7. 涉及文件

### 7.1 数据库迁移

| 文件 | 说明 |
|------|------|
| migrate_scene_usage_settings.php | 新增use_count、output_quantity字段 |

### 7.2 后端代码

| 文件 | 变更类型 | 说明 |
|------|----------|------|
| app/model/GenerationSceneTemplate.php | 修改 | createFromRecord()增加use_count=0、output_quantity赋值 |
| app/controller/PhotoGeneration.php | 修改 | scene_edit()返回model_max_output，scene_save()处理新字段并校验 |
| app/controller/VideoGeneration.php | 修改 | scene_edit()返回model_max_output，scene_save()处理新字段并校验 |
| app/service/GenerationService.php | 修改 | saveTemplate()校验output_quantity、新增getModelMaxOutput()/incrementTemplateUsage()方法 |
| app/controller/ApiAivideo.php | 修改 | 模板接口返回新字段、使用后调用incrementTemplateUsage() |

### 7.3 前端视图

| 文件 | 变更类型 | 说明 |
|------|----------|------|
| app/view/photo_generation/scene_edit.html | 修改 | 增加"生成设置"区块 |
| app/view/video_generation/scene_edit.html | 修改 | 增加"生成设置"区块 |
| app/view/photo_generation/scene_list.html | 修改 | 列表增加使用次数/输出数量列 |
| app/view/video_generation/scene_list.html | 修改 | 列表增加使用次数/输出数量列 |

---

## 8. 校验与约束

### 8.1 数据校验规则

| 字段 | 校验规则 | 错误提示 |
|------|----------|----------|
| use_count | 整数，≥0 | 使用次数必须为非负整数 |
| output_quantity | 整数，≥1，≤模型max_output | 每单输出数量必须在1~{max}之间 |

### 8.2 业务约束

| 约束 | 说明 |
|------|------|
| 使用次数自动累加 | 每次模板被调用生成任务后，use_count自动+1 |
| 管理员可修正 | 管理员可在后台手动修改use_count（用于数据修正场景） |
| 输出数量受模型限制 | output_quantity不能超过绑定模型的最大输出能力 |
| 切换模型时校验 | 若切换模型导致output_quantity超出新模型上限，需自动调整或提示 |

---

## 9. 单元测试用例

| 测试场景 | 预期结果 |
|----------|----------|
| 新建模板，use_count默认值 | use_count=0 |
| 调用模板生成1次 | use_count从0变为1 |
| 连续调用模板3次 | use_count累加为3 |
| 管理员手动修改use_count为100 | 保存成功，use_count=100 |
| 任务转模板（源任务4张输出） | output_quantity=4，use_count=0 |
| 设置output_quantity=5，绑定模型max_output=6 | 保存成功 |
| 设置output_quantity=0 | 校验失败，提示"每单输出数量必须≥1" |
| 设置output_quantity=10，绑定模型max_output=6 | 校验失败，提示"超出模型能力上限(最大6)" |
| 切换模型（新模型max_output=4），当前output_quantity=6 | 提示需调整output_quantity或自动调整为4 |
