# 场景模板会员价格设置功能 - 实现完成报告

## 概述

本次实现为图片生成和视频生成的场景模板增加了会员等级差异化定价功能，允许管理员为不同会员等级设置不同的价格。

## 实现内容

### 1. 数据库变更

在 `ddwx_generation_scene_template` 表中新增以下字段：

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| `base_price` | decimal(10,2) | 0.00 | 基础价格（游客/未登录用户价格） |
| `price_unit` | varchar(20) | 'per_image' | 计价单位：per_image=按张，per_second=按秒 |
| `lvprice` | tinyint(1) | 0 | 会员价开关：0=关闭，1=开启 |
| `lvprice_data` | text | NULL | 会员价格数据（JSON格式） |

**迁移脚本**: `migrate_scene_lvprice.php`, `migrate_scene_lvprice.sql`

### 2. 后台管理功能

#### 2.1 控制器更新

**PhotoGeneration.php** 和 **VideoGeneration.php**:
- `scene_edit()` 方法：新增获取会员等级列表（levellist），解析 lvprice_data
- `scene_save()` 方法：新增处理会员价格数据逻辑，将前端提交的价格数组转为JSON存储

#### 2.2 服务层更新

**GenerationService.php**:
- `saveTemplate()` 方法：新增保存 base_price、price_unit、lvprice、lvprice_data 字段
- 新增 `calculateTemplatePrice($template, $memberLevelId)` 方法：根据会员等级计算实际价格
- 新增 `getTemplateListWithPrice(...)` 方法：获取场景模板列表，包含计算后的价格信息

#### 2.3 视图更新

**photo_generation/scene_edit.html** 和 **video_generation/scene_edit.html**:
- 新增"价格设置"区域，包含：
  - 计价单位选择（按张/按秒）
  - 基础价格输入
  - 会员价开关
  - 会员等级价格表（动态显示各等级价格输入框）

### 3. 前端API接口

**ApiAivideo.php** 新增两个API方法：

#### 3.1 获取场景模板列表

```
GET/POST ApiAivideo/scene_template_list
参数：
- aid: 账户ID（通过初始化获取）
- bid: 商户ID
- generation_type: 生成类型（1=图片，2=视频）
- category_id: 分类ID（可选）
- group_id: 分组ID（可选）
- mid: 会员ID（可选，用于计算会员价）

返回：
{
  "status": 1,
  "msg": "获取成功",
  "data": {
    "list": [{
      "id": 1,
      "template_name": "模板名称",
      "cover_image": "封面URL",
      "price": 3.00,           // 当前用户实际价格
      "base_price": 5.00,      // 基础价格
      "price_unit": "per_image",
      "price_unit_text": "元/张",
      "is_member_price": true, // 是否享受会员价
      "all_prices": {"1": 3.00, "2": 2.00}  // 所有等级价格
    }],
    "member_level_id": 1
  }
}
```

#### 3.2 获取场景模板详情

```
GET/POST ApiAivideo/scene_template_detail
参数：
- aid: 账户ID
- template_id: 模板ID
- mid: 会员ID（可选）

返回：
{
  "status": 1,
  "msg": "获取成功",
  "data": {
    "id": 1,
    "template_name": "模板名称",
    "cover_image": "封面URL",
    "description": "描述",
    "price": 3.00,
    "base_price": 5.00,
    "price_unit": "per_image",
    "price_unit_text": "元/张",
    "is_member_price": true,
    "all_prices": [{
      "level_id": 1,
      "level_name": "普通会员",
      "price": 3.00
    }]
  }
}
```

### 4. 价格计算逻辑

```
1. 模板是否开启会员价（lvprice）？
   - 否 → 返回基础价格（base_price）
   - 是 → 继续判断

2. 用户是否登录且有会员等级（memberLevelId > 0）？
   - 否 → 返回基础价格
   - 是 → 继续判断

3. lvprice_data 中是否有该等级的价格配置？
   - 是 → 返回对应等级价格
   - 否 → 返回基础价格
```

## 使用说明

### 后台配置

1. 进入"照片生成/视频生成" → "场景模板"
2. 编辑或新增场景模板
3. 在"价格设置"区域配置：
   - 选择计价单位（图片默认按张，视频默认按秒）
   - 填写基础价格（游客价）
   - 开启会员价后，为各会员等级填写对应价格

### 前端集成

1. 调用 `ApiAivideo/scene_template_list` 获取模板列表
2. 用户登录后传递 `mid` 参数，系统自动返回对应等级价格
3. 在模板卡片上展示价格信息（可展示会员价标签和原价对比）

## 注意事项

1. 会员等级基于 `ddwx_member_level` 表，需要先在会员管理中配置等级
2. 价格支持两位小数精度
3. 各等级价格留空则自动回退到基础价格
4. 订单金额计算需在订单创建时实现（本次未涉及订单模块）

## 修改文件清单

- `app/controller/PhotoGeneration.php` - 场景模板编辑和保存
- `app/controller/VideoGeneration.php` - 场景模板编辑和保存
- `app/service/GenerationService.php` - 模板保存和价格计算服务
- `app/view/photo_generation/scene_edit.html` - 图片模板编辑页面
- `app/view/video_generation/scene_edit.html` - 视频模板编辑页面
- `app/controller/ApiAivideo.php` - 前端API接口
- `migrate_scene_lvprice.php` - 数据库迁移脚本
- `migrate_scene_lvprice.sql` - SQL迁移脚本
