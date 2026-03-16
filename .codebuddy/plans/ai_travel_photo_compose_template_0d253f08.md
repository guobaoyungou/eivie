---
name: ai_travel_photo_compose_template
overview: 在AI旅拍系统中新增"合成模板"和"合成设置"功能，用于人像图片的AI合成处理
todos:
  - id: create-synthesis-table
    content: 创建合成模板数据库表SQL脚本
    status: completed
  - id: add-menu-item
    content: 在Menu.php中添加"合成模板"菜单项
    status: completed
  - id: create-template-model
    content: 创建合成模板Model类
    status: completed
    dependencies:
      - create-synthesis-table
  - id: add-controller-methods
    content: 在AiTravelPhoto.php中添加合成模板CRUD方法
    status: completed
    dependencies:
      - create-template-model
  - id: create-template-list-view
    content: 创建合成模板列表视图
    status: completed
    dependencies:
      - add-controller-methods
  - id: create-template-edit-view
    content: 创建合成模板编辑视图
    status: completed
    dependencies:
      - add-controller-methods
  - id: add-synthesis-settings-btn
    content: 在portrait_list.html中添加合成设置按钮
    status: completed
    dependencies:
      - add-controller-methods
  - id: create-synthesis-service
    content: 创建合成服务处理顺序/随机生成逻辑
    status: completed
    dependencies:
      - create-template-model
  - id: implement-watermark-save
    content: 实现水印添加并存入选片表逻辑
    status: completed
    dependencies:
      - create-synthesis-service
---

## Product Overview

在AI旅拍系统中新增"合成模板"功能，用于管理和执行AI图像合成任务。

## Core Features

1. **合成模板管理**

- 在"旅拍"菜单的"人像管理"与"订单管理"之间增加"合成模板"子菜单
- 支持选择绑定模型广场里的模型（调用已有接口 AiTravelPhoto/get_model_list）
- 支持多图片上传（模板背景图）
- 支持填写提示词（Prompt）
- 模板列表、增删改查功能

2. **合成设置功能**

- 在人像管理页面的"笑脸抓拍"按钮旁边增加"合成设置"按钮
- 多选合成模板（从模板列表中选择）
- 合成数量设置（生成多少张图片）
- 模式选择：顺序模式或随机模式
    - 顺序模式：按模板顺序生成指定数量图片
    - 随机模式：随机挑选模板生成指定数量图片

3. **图片生成与存储**

- 调用AI模型生成合成图片（使用模板绑定的模型ID）
- 使用AI旅拍系统设置的Logo水印（business.ai_logo_watermark）
- 将生成的图片带水印存入选片表（ai_travel_photo_qrcode表关联的result）

## Tech Stack

- 后端框架: ThinkPHP 6.x (与现有项目一致)
- 前端: LayUI (与现有项目一致)
- 数据库: MySQL
- 图片处理: GD库 + 已有WatermarkService

## Implementation Approach

1. **数据库设计**: 新建合成模板表 `ddwx_ai_travel_photo_synthesis_template`

- id: 主键
- aid: 商户ID
- bid: 门店ID
- name: 模板名称
- model_id: 绑定的模型ID（关联模型广场的模型）
- model_name: 模型名称（冗余存储便于显示）
- images: 模板图片URL数组（JSON格式）
- prompt: 提示词
- status: 状态（0禁用1正常）
- sort: 排序
- create_time/create_time: 时间戳

2. **菜单配置**: 在Menu.php的ai_travel_photo_child数组中，在"人像管理"后"订单管理"前插入"合成模板"菜单项

3. **控制器方法**: 在AiTravelPhoto.php中添加：

- synthesis_template_list() - 模板列表
- synthesis_template_edit() - 模板编辑页面（包含模型选择器）
- synthesis_template_save() - 保存模板
- synthesis_template_delete() - 删除模板
- synthesis_settings() - 合成设置弹窗（AJAX）
- synthesis_generate() - 执行合成生成

4. **视图文件**:

- 新建 synthesis_template_list.html - 模板列表页
- 新建 synthesis_template_edit.html - 模板编辑页（含模型选择下拉框，调用get_model_list获取模型）
- 修改 portrait_list.html - 添加"合成设置"按钮

5. **服务层**:

- 新建 SynthesisService 处理合成逻辑（模板选择、顺序/随机算法）
- 复用 AiTravelPhotoWatermarkService 添加水印
- 复用 AiTravelPhotoQrcodeService 存入选片表

## Architecture Design

采用与现有AI旅拍系统一致的MVC架构：

- Model: 新建 AiTravelPhotoSynthesisTemplate 模型
- View: 使用LayUI组件构建表单和弹窗
- Controller: 在现有 AiTravelPhoto 控制器中扩展

## Directory Structure

```
app/
├── model/
│   └── AiTravelPhotoSynthesisTemplate.php     # [NEW] 合成模板模型
├── service/
│   └── AiTravelPhotoSynthesisService.php      # [NEW] 合成服务
├── controller/
│   └── AiTravelPhoto.php                       # [MODIFY] 添加合成相关方法
├── view/
│   └── ai_travel_photo/
│       ├── synthesis_template_list.html        # [NEW] 模板列表页
│       ├── synthesis_template_edit.html       # [NEW] 模板编辑页
│       └── portrait_list.html                # [MODIFY] 添加合成设置按钮
├── common/
│   └── Menu.php                               # [MODIFY] 添加菜单项
database/migrations/
│   └── ai_travel_photo_synthesis_template.sql # [NEW] 建表SQL
```