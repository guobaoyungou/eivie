# AI旅拍任务列表视图修复说明

## 问题描述

在首次部署时遇到以下错误：
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'guobaoyungou_cn.ddwx_view_ai_travel_task_summary' doesn't exist
```

## 问题原因

1. **表结构差异**: 原设计假设 `ddwx_ai_travel_photo_portrait` 表有 `cutout_status` 字段，但实际表结构中该字段不存在
2. **抠图状态获取方式**: 实际业务中，抠图作为一种特殊的生成任务存储在 `ddwx_ai_travel_photo_generation` 表中（`generation_type = 0`）

## 解决方案

### 1. 修复视图创建SQL

**修改点**:
- 移除不存在的 `p.cutout_status` 字段引用
- 通过子查询从 `generation` 表获取抠图状态：
  ```sql
  COALESCE(
      (SELECT status FROM ddwx_ai_travel_photo_generation 
       WHERE portrait_id = p.id AND generation_type = 0 
       ORDER BY id DESC LIMIT 1), 
      0
  ) AS cutout_status
  ```
- 任务统计时排除抠图任务（`generation_type = 0`），仅统计图生图和视频生成任务

### 2. 执行修复脚本

```bash
# 执行快速修复脚本
mysql -uguobaoyungou_cn -p5ArfhRr9xzyScrF5 guobaoyungou_cn < /www/wwwroot/eivie/database/migrations/fix_task_view.sql
```

### 3. 验证视图

```bash
# 验证视图是否创建成功
mysql -uguobaoyungou_cn -p5ArfhRr9xzyScrF5 guobaoyungou_cn -e "SELECT portrait_id, file_name, total_tasks, success_tasks, failed_tasks, task_status_summary FROM view_ai_travel_task_summary LIMIT 5"
```

**验证结果**:
```
+-------------+--------------+-------------+---------------+--------------+---------------------+
| portrait_id | file_name    | total_tasks | success_tasks | failed_tasks | task_status_summary |
+-------------+--------------+-------------+---------------+--------------+---------------------+
|           7 | test (2).jpg |           5 |             0 |            0 | processing          |
|           8 | test (3).jpg |           5 |             0 |            0 | processing          |
+-------------+--------------+-------------+---------------+--------------+---------------------+
```

## 任务类型说明

根据实际表结构，`generation_type` 字段定义：
- `0`: 抠图任务（特殊类型，不计入任务列表统计）
- `1`: 图生图（单场景）
- `2`: 多镜头批量生成
- `3`: 图生视频

## 已修复的文件

1. `/database/migrations/ai_travel_photo_task_view.sql` - 主SQL脚本（已更新）
2. `/database/migrations/fix_task_view.sql` - 快速修复脚本（新建）

## 部署检查清单

部署任务列表功能时，请按以下顺序检查：

- [x] 确认数据库表结构与实际一致
- [x] 执行视图创建SQL脚本
- [x] 验证视图查询成功
- [ ] 清除ThinkPHP缓存
- [ ] 访问任务列表页面验证功能

## 注意事项

1. **数据库表前缀**: ThinkPHP会自动为表名添加 `ddwx_` 前缀，因此视图名应为 `view_ai_travel_task_summary`（不带前缀）
2. **抠图状态**: 从子查询获取，可能影响查询性能，后续可考虑优化
3. **任务统计**: 仅统计 `generation_type > 0` 的任务，抠图任务单独展示

## 性能优化建议

如果后续发现查询性能问题，可考虑：
1. 在 `portrait` 表添加 `cutout_status` 冗余字段
2. 使用触发器同步更新抠图状态
3. 定期物化视图数据到临时表

---

**修复时间**: 2026-02-03  
**修复状态**: ✅ 已完成
