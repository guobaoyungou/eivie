# AI旅拍商户权限配置 - 完成总结

## 一、功能概述

已成功在商户权限管理系统中集成AI旅拍模块权限配置，商户管理员可以通过"商户-商户列表-编辑商户-权限设置"为不同商户灵活授权AI旅拍功能。

## 二、菜单结构

AI旅拍菜单已正确配置在 `app/common/Menu.php` 中，位于**商户模块和会员模块之间**，完整菜单包含：

### 核心功能模块
1. **场景管理** (`AiTravelPhoto/scene_list`)
   - 查看、编辑、删除、批量操作场景

2. **套餐管理** (`AiTravelPhoto/package_list`)
   - 查看、编辑、删除、批量操作套餐

3. **人像管理** (`AiTravelPhoto/portrait_list`)
   - 查看、删除、批量操作人像
   - 生成结果详情（隐藏菜单）

4. **订单管理** (`AiTravelPhoto/order_list`)
   - 订单列表、详情查看、退款处理

5. **设备管理** (`AiTravelPhoto/device_list`)
   - 设备列表、生成Token、删除设备

6. **选片列表** (`AiTravelPhoto/qrcode_list`)
   - 查看选片二维码记录（对应数据表：ddwx_ai_travel_photo_qrcode）

7. **成品列表** (`AiTravelPhoto/result_list`)
   - 查看生成的成品（对应数据表：ddwx_ai_travel_photo_result）

8. **数据统计** (`AiTravelPhoto/statistics`)
   - 业务数据统计分析

9. **系统设置** (`AiTravelPhoto/settings`)
   - AI旅拍系统配置

10. **AI视频** (二级分组，条件显示)
    - 商家配置、提示词模板、素材管理等

## 三、权限配置方式

### 自动生成
权限配置界面通过ThinkPHP模板循环自动生成：
```html
{foreach $menudata as $k=>$v}
    <!-- 自动渲染所有菜单模块，包括AI旅拍 -->
{/foreach}
```

### 涉及文件
- **模板文件**：
  - `/www/wwwroot/eivie/app/view/public/user_auth.html`
  - `/www/wwwroot/eivie/app/view/web_user/edit.html`
  
- **菜单配置**：
  - `/www/wwwroot/eivie/app/common/Menu.php`（第143-216行）

- **权限保存**：
  - `/www/wwwroot/eivie/app/controller/Business.php::save()`方法

## 四、权限数据格式

### 存储格式
权限数据存储在 `ddwx_admin_user` 表的 `auth_data` 字段（JSON格式）：

```json
[
  "ShopProduct/index,ShopProduct/*",
  "AiTravelPhoto/scene_list,AiTravelPhoto/scene_list,AiTravelPhoto/scene_edit,AiTravelPhoto/scene_delete,AiTravelPhoto/scene_batch*",
  "AiTravelPhoto/device_list,AiTravelPhoto/device_list,AiTravelPhoto/device_generate_token,AiTravelPhoto/device_delete*",
  "AiTravelPhoto/qrcode_list,AiTravelPhoto/qrcode_list*",
  "AiTravelPhoto/result_list,AiTravelPhoto/result_list*"
]
```

### 特殊处理
- 保存时：`/*` 替换为 `^_^`（避免JSON转义问题）
- 读取时：`^_^` 还原为 `/*`

## 五、显示逻辑

### 平台管理员（bid=0, isadmin=2）
- 始终显示完整的AI旅拍菜单
- 可以为任何商户配置权限

### 商户管理员（bid>0, isadmin=1）
- 仅当商户开通AI旅拍功能时显示（`business.ai_travel_photo_enabled = 1`）
- 根据 `auth_data` 配置显示被授权的菜单

### 权限验证
- 后端统一验证：`app/controller/Common.php::initialize()`
- 前端菜单过滤：`app/common/Menu.php::getdata()`

## 六、使用流程

### 管理员操作
1. 进入"商户管理" → "商户列表"
2. 点击"编辑"按钮
3. 在"权限设置"区域找到"旅拍"模块
4. 勾选需要授权的功能（场景管理、设备管理、选片列表、成品列表等）
5. 点击"保存"

### 商户登录后
1. 商户管理员登录后台
2. 左侧菜单自动显示被授权的"旅拍"模块
3. 展开查看可访问的具体功能
4. 访问未授权功能时显示"无操作权限"

## 七、技术规范

### 菜单命名规范
- 模块名称：中文描述（如"旅拍"）
- 路径格式：`控制器/方法`（如`AiTravelPhoto/device_list`）
- authdata格式：`控制器/方法1,控制器/方法2*`

### 数据库字段
- 关联表：`ddwx_business`、`ddwx_admin_user`
- 权限字段：`auth_data`（TEXT类型，JSON格式）
- 功能开关：`ai_travel_photo_enabled`（商户级别）

### 特殊说明
1. **选片列表**和**成品列表**按照规范顺序配置在设备管理之后
2. 所有配置完全符合系统现有的商城、财务等模块的权限管理方式
3. 支持权限组功能（如启用）
4. 支持细粒度的方法级别权限控制

## 八、验证要点

### 功能验证
- [x] 平台管理员可以看到AI旅拍菜单
- [x] 可以为商户勾选/取消AI旅拍权限
- [x] 权限变更后立即生效
- [x] 商户管理员只能看到被授权的菜单
- [x] 访问未授权页面时正确拦截

### 数据验证
- [x] auth_data字段JSON格式正确
- [x] `/*` 与 `^_^` 正确转换
- [x] 权限标识与菜单authdata一致
- [x] 支持部分授权和全部授权

## 九、注意事项

1. **不要手动添加权限配置HTML**：所有权限通过 `{foreach $menudata}` 自动生成
2. **保持authdata一致性**：Menu.php中的authdata必须与checkbox的value值匹配
3. **遵循菜单顺序规范**：设备管理 → 选片列表 → 成品列表
4. **数据隔离**：所有查询必须加 `where('bid', bid)` 条件

## 十、相关文档

- 选片模板演示数据：`/www/wwwroot/eivie/xpd/templates/template_1/index.html`
- 数据表说明：
  - 选片数据：`ddwx_ai_travel_photo_qrcode`
  - 成品数据：`ddwx_ai_travel_photo_result`
- 门店配置项：
  - 图片展示时长：`xpd_image_duration`
  - 分组展示时长：`xpd_group_duration`

---

**完成时间**：2026-01-23  
**版本**：V1.0  
**状态**：✅ 已完成并测试通过
