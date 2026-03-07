# 场景模板商品化设置功能扩展实施摘要

## 完成日期
2026-03-03

## 实施概述
根据设计文档，为场景模板（ddwx_generation_scene_template）添加了完整的分销、分红、积分抵扣、购买条件等商业化运营功能。

## 已完成的更改

### 1. 数据库迁移
创建了以下迁移脚本：
- `migrate_scene_commercialization.php` - PHP迁移脚本
- `migrate_scene_commercialization.sql` - SQL迁移脚本

**新增字段（21个）：**

| 字段类别 | 字段名 | 类型 | 说明 |
|---------|-------|------|------|
| **分销设置** | commissionset | tinyint(2) | 分销模式：0按等级 1比例 2金额 3送积分 -1不参与 |
| | commissiondata1 | text | 按比例分销参数 |
| | commissiondata2 | text | 按固定金额参数 |
| | commissiondata3 | text | 分销送积分参数 |
| | commissionset4 | tinyint(1) | 极差分销开关 |
| **分红设置** | fenhongset | tinyint(1) | 分红总开关 |
| | teamfenhongset | tinyint(2) | 团队分红模式 |
| | teamfenhongdata1 | text | 团队分红比例参数 |
| | teamfenhongdata2 | text | 团队分红金额参数 |
| | gdfenhongset | tinyint(2) | 股东分红模式 |
| | gdfenhongdata1 | text | 股东分红比例参数 |
| | gdfenhongdata2 | text | 股东分红金额参数 |
| | areafenhongset | tinyint(2) | 区域分红模式 |
| | areafenhongdata1 | text | 区域分红比例参数 |
| | areafenhongdata2 | text | 区域分红金额参数 |
| **积分抵扣** | scoredkmaxset | tinyint(1) | 积分抵扣设置 |
| | scoredkmaxval | decimal(11,2) | 积分抵扣最大值 |
| **权限控制** | showtj | varchar(255) | 显示条件 |
| | gettj | varchar(255) | 购买条件 |
| | gettjurl | varchar(255) | 不满足条件跳转链接 |
| | gettjtip | varchar(255) | 不满足条件提示文案 |

### 2. 后端控制器更新

#### PhotoGeneration.php
- `scene_edit()` 方法：扩展了数据加载，增加团队/股东/区域等级列表，解析各类JSON数据字段
- `scene_save()` 方法：扩展了数据保存，处理分销/分红/积分/权限等全部新字段

#### VideoGeneration.php
- 与PhotoGeneration.php相同的更新

### 3. 前端视图更新

#### photo_generation/scene_edit.html
新增的UI组件：
- **分销设置区**：分销模式选择（按等级/比例/金额/送积分/不参与），各模式的参数配置表格，极差分销开关
- **分红设置区**：分红总开关，团队/股东/区域代理分红的模式选择和参数配置
- **积分抵扣区**：抵扣模式选择（按系统/单独比例/单独金额/不可抵扣），抵扣上限输入
- **显示/购买条件区**：显示条件多选，购买条件多选，不满足条件时的跳转/提示配置

#### video_generation/scene_edit.html
- 与photo_generation/scene_edit.html相同的UI组件

### 4. JavaScript交互
为两个编辑页面添加了完整的表单交互逻辑：
- 分销模式切换显示对应配置区域
- 分红开关控制子配置显示
- 团队/股东/区域分红模式切换
- 积分抵扣模式切换及提示更新
- 显示/购买条件的多选框互斥逻辑

## 使用方法

### 1. 执行数据库迁移
```bash
# 方法1：直接执行SQL文件
mysql -u用户名 -p密码 数据库名 < migrate_scene_commercialization.sql

# 方法2：通过PHP执行（需要正确配置数据库连接）
php migrate_scene_commercialization.php
```

### 2. 功能使用
1. 进入后台 → 照片生成/视频生成 → 场景模板管理
2. 新增或编辑场景模板
3. 在"价格设置"后可看到新增的设置区域：
   - 分销设置
   - 分红设置
   - 积分抵扣
   - 显示与购买条件

## 后续开发建议

1. **佣金计算服务**：需要在订单支付成功后实现佣金计算逻辑，参考已有的商品分销逻辑
2. **分红发放服务**：需要实现团队/股东/区域代理的分红发放流程
3. **积分抵扣计算**：需要在创建订单时根据scoredkmaxset配置计算可抵扣积分
4. **权限校验服务**：需要在前端API返回模板列表/详情时根据showtj/gettj过滤

## 文件清单
- `/home/www/ai.eivie.cn/migrate_scene_commercialization.php` - PHP迁移脚本
- `/home/www/ai.eivie.cn/migrate_scene_commercialization.sql` - SQL迁移脚本
- `/home/www/ai.eivie.cn/app/controller/PhotoGeneration.php` - 照片生成控制器（已更新）
- `/home/www/ai.eivie.cn/app/controller/VideoGeneration.php` - 视频生成控制器（已更新）
- `/home/www/ai.eivie.cn/app/view/photo_generation/scene_edit.html` - 照片模板编辑页（已更新）
- `/home/www/ai.eivie.cn/app/view/video_generation/scene_edit.html` - 视频模板编辑页（已更新）
