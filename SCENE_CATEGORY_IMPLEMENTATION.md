# 场景分类功能实现报告

## 实现日期
2026-03-03

## 功能概述
为照片生成和视频生成模块新增独立的"场景分类"功能，实现场景模板的层级化分类管理。

## 实现内容

### 1. 数据库变更

#### 新增表: `ddwx_generation_scene_category`
| 字段 | 类型 | 说明 |
|------|------|------|
| id | int(11) | 主键 |
| aid | int(11) | 账户ID |
| bid | int(11) | 商户ID |
| generation_type | tinyint(1) | 生成类型：1=照片 2=视频 |
| pid | int(11) | 上级分类ID |
| name | varchar(100) | 分类名称 |
| pic | varchar(255) | 分类图标 |
| description | varchar(500) | 分类描述 |
| sort | int(11) | 排序值 |
| status | tinyint(1) | 状态 |
| create_time | int(11) | 创建时间 |
| update_time | int(11) | 更新时间 |

#### 修改表: `ddwx_generation_scene_template`
- 新增字段 `category_id` int(11) 关联分类ID

### 2. 新增控制器

#### PhotoSceneCategory
- 文件: `/app/controller/PhotoSceneCategory.php`
- 功能: 照片场景分类管理
- 方法:
  - `index()` - 分类列表（树形结构）
  - `edit()` - 编辑分类表单
  - `save()` - 保存分类
  - `del()` - 删除分类
  - `choosecategory()` - 分类选择弹窗

#### VideoSceneCategory
- 文件: `/app/controller/VideoSceneCategory.php`
- 功能: 视频场景分类管理
- 方法: 与PhotoSceneCategory结构一致

### 3. 新增视图文件

#### 照片场景分类
- `/app/view/photo_scene_category/index.html` - 分类列表页
- `/app/view/photo_scene_category/edit.html` - 分类编辑页
- `/app/view/photo_scene_category/choosecategory.html` - 分类选择弹窗

#### 视频场景分类
- `/app/view/video_scene_category/index.html` - 分类列表页
- `/app/view/video_scene_category/edit.html` - 分类编辑页
- `/app/view/video_scene_category/choosecategory.html` - 分类选择弹窗

### 4. 修改的文件

#### Menu.php
- 文件: `/app/common/Menu.php`
- 变更: 在照片生成和视频生成菜单中添加"场景分类"子菜单

#### GenerationSceneTemplate.php
- 文件: `/app/model/GenerationSceneTemplate.php`
- 变更: 
  - `getListWithModel()` 添加分类名称关联查询
  - `getDetailWithModel()` 添加分类名称关联查询

#### GenerationService.php
- 文件: `/app/service/GenerationService.php`
- 变更: `saveTemplate()` 方法添加 category_id 字段保存

#### 场景模板编辑页
- `/app/view/photo_generation/scene_edit.html`
- `/app/view/video_generation/scene_edit.html`
- 变更: 添加分类选择器组件

### 5. 数据库迁移文件
- `/database/migrations/migrate_scene_category.php` - PHP迁移脚本
- `/database/migrations/scene_category_migration.sql` - SQL迁移脚本

## 菜单结构

### 照片生成
- 生成任务
- 生成记录
- **场景分类** (新增)
- 场景模板

### 视频生成
- 生成任务
- 生成记录
- **场景分类** (新增)
- 场景模板

## 使用说明

### 执行数据库迁移
```sql
-- 在MySQL客户端中执行
source /home/www/ai.eivie.cn/database/migrations/scene_category_migration.sql
```

或者手动执行SQL:
```sql
-- 创建分类表
CREATE TABLE `ddwx_generation_scene_category` ...

-- 修改模板表
ALTER TABLE `ddwx_generation_scene_template` 
    ADD COLUMN `category_id` int(11) NOT NULL DEFAULT 0 COMMENT '关联分类ID' AFTER `category`,
    ADD INDEX `idx_category_id` (`category_id`);
```

### 功能访问路径
- 照片场景分类: `/?s=/PhotoSceneCategory/index`
- 视频场景分类: `/?s=/VideoSceneCategory/index`

### 业务规则
1. 分类最多支持3级层级
2. 删除分类时需确保无子分类且无关联模板
3. 隐藏分类不影响已关联模板的正常使用
4. 排序值越大越靠前

## 权限配置
- PhotoSceneCategory/* - 照片场景分类管理
- VideoSceneCategory/* - 视频场景分类管理

## 测试建议
1. 测试分类的增删改查功能
2. 测试三级分类层级限制
3. 测试分类关联模板后的删除保护
4. 测试场景模板编辑页的分类选择器
5. 测试商户端的数据隔离
