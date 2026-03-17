---
name: fix_detail_button
overview: 修复查看详情按钮跳转问题
todos:
  - id: add_portrait_to_order_method
    content: 在AiTravelPhoto.php添加portrait_to_order方法，根据portrait_id查询关联订单ID
    status: completed
  - id: update_view_detail_button
    content: 修改portrait_list.html中的查看详情按钮，链接到订单详情
    status: completed
    dependencies:
      - add_portrait_to_order_method
---

## 用户需求

点击人像管理列表的"查看详情"按钮时，当前链接到不存在的portrait_detail页面（404错误），需要改为显示订单详情。

## 解决方案

1. 在AiTravelPhoto.php添加portrait_to_order方法，根据portrait_id查询关联的订单ID
2. 修改portrait_list.html中"查看详情"按钮链接，使用新方法或直接跳转订单列表

## 技术说明

- ai_travel_photo_order表有portrait_id字段可关联人像
- 合成生成成功后记录保存到ai_travel_photo_result表（通过portrait_id关联）
- order_detail方法存在于AiTravelPhoto.php第2178行

## 技术方案

1. 添加新方法portrait_to_order：根据portrait_id查询对应订单，返回订单ID或跳转
2. 修改前端按钮：使用该方法获取订单ID后跳转，或直接在按钮显示时查询订单数量

## 实现步骤

1. 在AiTravelPhoto.php添加portrait_to_order方法
2. 修改portrait_list.html中查看详情按钮逻辑