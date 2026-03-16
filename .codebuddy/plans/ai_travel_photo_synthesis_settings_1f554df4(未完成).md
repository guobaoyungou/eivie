---
name: ai_travel_photo_synthesis_settings
overview: 在AI旅拍人像管理页面显示"合成设置"按钮，实现多选模板、合成数量设置、顺序/随机模式选择，生成时添加水印并存入选片表
todos:
  - id: show-synthesis-btn
    content: 修改 portrait_list.html 移除按钮隐藏样式 display:none
    status: pending
---

## 用户需求

在人像管理页面"笑脸抓拍"按钮旁边显示"合成设置"功能按钮。

## 功能说明

- 多选合成模板
- 人像合成数量设置（1-10张）
- 顺序/随机模式选择
- 生成的图片添加AI旅拍系统设置的Logo水印图片存入选片表

## 代码现状

功能已完整实现，按钮被隐藏（display:none;）

基于现有ThinkPHP+Layui框架，使用已实现的完整功能代码：

- 前端：Layui按钮组件 + iframe弹窗
- 后端：ThinkPHP控制器 + 合成服务类
- 数据表：ai_travel_photo_synthesis_setting、ai_travel_photo_result、ai_travel_photo_qrcode