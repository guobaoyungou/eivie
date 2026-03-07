# 商户后台菜单显示问题修复报告

## 修复日期
2026-03-02

## 问题描述
商户后台管理员登录后，无法看到以下菜单：
- **照片生成** (PhotoGeneration)
- **视频生成** (VideoGeneration)  
- **旅拍** (AiTravelPhoto)

尽管平台管理员已为该商户授予了相关权限。

## 根本原因
菜单定义中仅有 `if($isadmin || $uid == -1)` 分支，缺少商户用户（`$bid > 0`）的定义分支。导致：
- 平台管理员编辑商户权限时（uid=-1）能看到这些菜单并授权
- 商户用户登录时（isadmin=false, uid>0）条件不满足，菜单不显示

## 修复内容

### 1. 数据库变更
在 `ddwx_business` 表中新增两个字段：

```sql
ALTER TABLE ddwx_business ADD COLUMN photo_generation_enabled tinyint(1) NOT NULL DEFAULT 0 COMMENT '照片生成功能开关 0=关闭 1=开启' AFTER ai_travel_photo_enabled;
ALTER TABLE ddwx_business ADD COLUMN video_generation_enabled tinyint(1) NOT NULL DEFAULT 0 COMMENT '视频生成功能开关 0=关闭 1=开启' AFTER photo_generation_enabled;
```

### 2. Menu.php 修改
文件路径：`app/common/Menu.php`

为照片生成和视频生成菜单添加了商户分支：

```php
// 照片生成菜单
if($isadmin || $uid == -1){
    // 平台管理员/编辑商户权限时的菜单
    $menudata['photo_generation'] = [...];
}elseif($bid > 0){
    // 商户用户登录时，检查商户是否开通照片生成功能
    $business_photo = Db::name('business')->where('id', $bid)->find();
    if($business_photo && isset($business_photo['photo_generation_enabled']) && $business_photo['photo_generation_enabled'] == 1){
        $menudata['photo_generation'] = [...];
    }
}

// 视频生成菜单 - 同样的逻辑
if($isadmin || $uid == -1){
    $menudata['video_generation'] = [...];
}elseif($bid > 0){
    $business_video = Db::name('business')->where('id', $bid)->find();
    if($business_video && isset($business_video['video_generation_enabled']) && $business_video['video_generation_enabled'] == 1){
        $menudata['video_generation'] = [...];
    }
}
```

### 3. 商户编辑界面修改
文件路径：`app/view/business/edit.html`

在商户编辑页面添加了"AI功能开关"区块，包含三个开关：
- 照片生成开关 (`photo_generation_enabled`)
- 视频生成开关 (`video_generation_enabled`)
- AI旅拍开关 (`ai_travel_photo_enabled`)

## 使用方法

### 平台管理员操作步骤

1. **开启功能开关**
   - 登录平台后台
   - 进入【商户】→【商户列表】
   - 编辑目标商户
   - 在"AI功能开关"区块中开启所需功能
   - 保存

2. **授予菜单权限**
   - 在同一编辑页面的"权限设置"区块
   - 勾选相应的菜单权限（照片生成、视频生成、旅拍）
   - 保存

3. **商户登录验证**
   - 商户管理员登录后应能看到已开启并授权的菜单

## 权限验证链路

```
商户登录
    ↓
检查功能开关 (business.xxx_enabled = 1?)
    ↓ 是
生成菜单进入待显示列表
    ↓
检查权限类型 (auth_type = 1?)
    ↓ 否
检查权限列表 (菜单路径在 auth_data 中?)
    ↓ 是
显示菜单
```

## 测试验证

| 测试场景 | 前置条件 | 预期结果 |
|----------|----------|----------|
| 照片生成菜单显示 | `photo_generation_enabled=1` 且已授权 | 菜单正常显示 ✓ |
| 照片生成菜单隐藏 | `photo_generation_enabled=0` | 菜单不显示 ✓ |
| 视频生成菜单显示 | `video_generation_enabled=1` 且已授权 | 菜单正常显示 ✓ |
| 视频生成菜单隐藏 | `video_generation_enabled=0` | 菜单不显示 ✓ |
| 旅拍菜单显示 | `ai_travel_photo_enabled=1` 且已授权 | 菜单正常显示 ✓ |
| 旅拍菜单隐藏 | `ai_travel_photo_enabled=0` | 菜单不显示 ✓ |

## 修改的文件清单

1. `app/common/Menu.php` - 菜单生成逻辑
2. `app/view/business/edit.html` - 商户编辑界面

## 数据库字段状态

```
Field                      | Type          | Default
---------------------------|---------------|--------
ai_travel_photo_enabled    | tinyint(1)    | 0
photo_generation_enabled   | tinyint(1)    | 0
video_generation_enabled   | tinyint(1)    | 0
```

## 备注

- Business控制器的save方法已自动处理info数组中的所有字段，无需额外修改
- 功能开关与菜单权限是两个独立的检查层，都需要满足才能显示菜单
