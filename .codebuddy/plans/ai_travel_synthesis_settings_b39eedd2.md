---
name: ai_travel_synthesis_settings
overview: 在AI旅拍人像管理页面显示"合成设置"按钮，实现多选模板、数量设置、顺序/随机模式，生成时添加水印存入选片表
todos:
  - id: show-synthesis-button
    content: 修改 portrait_list.html，显示"合成设置"按钮
    status: completed
---

## 用户需求

在人像管理－笑脸抓拍的旁边增加"合成设置"功能：

1. 多选合成模板里的模板
2. 人像合成数量设置功能（1-10张）
3. 单选顺序功能／随机功能
4. 顺序模式：按选中模板顺序循环生成，如设置4张则按顺序生成4张
5. 随机模式：随机挑选模板生成指定数量图片
6. 将生成的图片添加AI旅拍系统设置的Logo水印图片存入选片表

## 功能说明

- 点击"合成设置"按钮后，弹出设置弹窗
- 用户可多选合成模板
- 设置合成数量（1-10张）
- 选择顺序或随机生成模式
- 点击"开始生成"后执行合成，添加水印并存入选片表

## 技术方案

基于现有代码实现，无需新技术栈。

### 现状分析

1. **后端控制器** (`/app/controller/AiTravelPhoto.php`):

- `synthesis_settings()` - 弹窗页面
- `synthesis_settings_save()` - 保存设置
- `synthesis_generate()` - 执行合成

2. **合成服务** (`/app/service/AiTravelPhotoSynthesisService.php`):

- 已实现模板选择、顺序/随机模式、调用AI模型、添加水印、存入选片表

3. **前端页面** (`/app/view/ai_travel_photo/synthesis_settings.html`):

- 已完整实现多选模板、数量设置、模式选择UI

4. **主列表页** (`/app/view/ai_travel_photo/portrait_list.html`):

- 合成设置按钮已存在，但被隐藏 (`style="display:none;"`)
- JS事件处理逻辑已完整实现

### 修改方案

只需修改 `portrait_list.html` 第158行，移除按钮的隐藏样式 `style="display:none;"`，使按钮默认显示。