# AI旅拍功能 - 添加数据库字段

## 问题分析

经检查代码发现，`ddwx_business`表缺失`ai_travel_photo_enabled`字段，该字段在多处被使用：

1. **AiTravelPhotoService.php** - 检查商家是否启用旅拍功能
2. **Menu.php** - 根据字段决定是否显示旅拍菜单
3. **AiTravelPhoto.php** - 后台设置中控制功能开关

## 执行的操作

### 1. 添加字段到 ddwx_business 表

```sql
ALTER TABLE ddwx_business 
ADD COLUMN ai_travel_photo_enabled TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 
COMMENT 'AI旅拍功能是否启用:0=关闭,1=开启' 
AFTER status;
```

**字段说明：**
- 类型：`TINYINT(1) UNSIGNED`
- 默认值：`0`（关闭）
- 可选值：`0`=关闭，`1`=开启
- 位置：在`status`字段之后

### 2. 启用旅拍功能

为了测试功能，已为所有商家启用旅拍功能：

```sql
UPDATE ddwx_business SET ai_travel_photo_enabled = 1;
```

### 3. 验证数据库表

确认所有AI旅拍相关的表已存在：

```
✅ ddwx_ai_travel_photo_device          - 设备管理
✅ ddwx_ai_travel_photo_generation      - 生成记录
✅ ddwx_ai_travel_photo_model           - AI模型
✅ ddwx_ai_travel_photo_order           - 订单管理
✅ ddwx_ai_travel_photo_order_goods     - 订单商品
✅ ddwx_ai_travel_photo_package         - 套餐管理
✅ ddwx_ai_travel_photo_portrait        - 人像管理
✅ ddwx_ai_travel_photo_qrcode          - 二维码管理
✅ ddwx_ai_travel_photo_result          - 生成结果
✅ ddwx_ai_travel_photo_scene           - 场景管理
✅ ddwx_ai_travel_photo_statistics      - 数据统计
✅ ddwx_ai_travel_photo_user_album      - 用户相册
```

## 后续建议

### 管理员操作

后台可以通过 **系统设置 > 旅拍配置** 来控制各商家的旅拍功能开关。

### 商家操作

商家登录后即可在菜单中看到"旅拍"菜单项，点击后进入数据统计首页。

## 现在可以测试

请重新访问以下页面，应该不会再出现500错误：

- ✅ **旅拍首页（数据统计）**: `http://192.168.11.222/?s=/AiTravelPhoto/index`
- ✅ **场景列表**: `http://192.168.11.222/?s=/AiTravelPhoto/scene_list`
- ✅ **套餐列表**: `http://192.168.11.222/?s=/AiTravelPhoto/package_list`
- ✅ **人像列表**: `http://192.168.11.222/?s=/AiTravelPhoto/portrait_list`

---

**更新时间**: 2026-01-21  
**状态**: ✅ 已完成
