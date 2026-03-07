# 选片端轮播时间配置功能说明

## 📢 功能概述

选片端现已支持**轮播时间可配置**功能！门店管理员可以根据客流情况，在后台灵活调整图片展示时长和组切换时间，优化展示效果。

---

## ✨ 新增功能

### 1. 后台配置界面

在"系统 > 门店管理 > 编辑门店"页面新增"轮播时间设置"配置项：

**配置选项：**
```
单张图片展示：________ 毫秒    单组展示：________ 毫秒
                ↓                        ↓
          500-3000ms                 3000-10000ms
          (0.5-3秒)                  (3-10秒)
```

**默认值：**
- 单张图片展示：1000毫秒（1秒）
- 单组展示：5000毫秒（5秒）

**提示信息：**
> 客流少时可增加时间，客流多时可减少时间。  
> 单张图片：500-3000毫秒（0.5-3秒），默认1000毫秒（1秒）  
> 单组展示：3000-10000毫秒（3-10秒），默认5000毫秒（5秒）

### 2. 数据库字段

新增两个配置字段：

| 字段名 | 类型 | 默认值 | 说明 |
|-------|------|--------|------|
| `xpd_image_duration` | INT UNSIGNED | 1000 | 单张图片展示时长(毫秒) |
| `xpd_group_duration` | INT UNSIGNED | 5000 | 单组展示时长(毫秒) |

**字段位置：** 位于 `xpd_template` 字段之后

### 3. API接口返回

`/api/ai-travel-photo/selection-list` 接口响应数据新增 `config` 字段：

```json
{
  "status": 1,
  "data": {
    "list": [...],
    "config": {
      "template": "template_1",
      "image_duration": 1000,
      "group_duration": 5000
    }
  }
}
```

### 4. 前端动态应用

选片端模板自动读取并应用配置：

```javascript
// 应用门店配置
if (response.data.data.config) {
    const config = response.data.data.config;
    this.config.imageDuration = config.image_duration;
    this.config.groupDuration = config.group_duration;
}
```

---

## 🎯 使用场景

### 场景1：客流高峰期

**问题：** 客流多，游客排队等待查看照片

**解决方案：**
```
减少展示时间：
- 单张图片：500-800ms
- 单组展示：3000-4000ms

效果：加快轮播速度，让更多游客看到自己的照片
```

### 场景2：客流平峰期

**问题：** 客流少，照片轮播太快游客来不及观看

**解决方案：**
```
增加展示时间：
- 单张图片：1500-2000ms
- 单组展示：6000-8000ms

效果：延长观看时间，让游客充分欣赏照片细节
```

### 场景3：特殊活动期

**问题：** 举办促销活动，需要更多时间展示

**解决方案：**
```
最大化展示时间：
- 单张图片：2500-3000ms
- 单组展示：8000-10000ms

效果：最大化展示时长，突出宣传效果
```

---

## 🔧 配置操作指南

### 商户操作步骤

#### 1. 登录后台

```
访问商家后台
↓
使用门店管理员账号登录
```

#### 2. 进入门店编辑

```
系统菜单
↓
门店管理
↓
找到目标门店
↓
点击"编辑"按钮
```

#### 3. 配置轮播时间

```
找到"轮播时间设置"
↓
输入单张图片展示时间（毫秒）
↓
输入单组展示时间（毫秒）
↓
点击"提交"保存
```

#### 4. 验证生效

```
打开选片URL（或刷新页面）
↓
观察轮播速度变化
↓
确认配置生效
```

### 配置建议

#### 根据客流调整

| 客流情况 | 单张图片(ms) | 单组展示(ms) | 说明 |
|---------|-------------|-------------|------|
| 极少客流 | 2000-3000 | 8000-10000 | 最长时间，充分展示 |
| 客流较少 | 1500-2000 | 6000-8000 | 延长观看时间 |
| 客流正常 | 1000-1500 | 5000-6000 | 标准速度 |
| 客流较多 | 800-1000 | 4000-5000 | 加快轮播 |
| 客流高峰 | 500-800 | 3000-4000 | 最快速度 |

#### 根据设备调整

| 设备类型 | 建议配置 | 原因 |
|---------|---------|------|
| 高性能大屏 | 可使用较短时间 | 设备流畅，切换快速 |
| 普通显示器 | 使用标准配置 | 性能适中 |
| 低性能设备 | 适当延长时间 | 避免卡顿感 |

---

## 💡 配置原则

### 1. 遵循"数据库优先"原则

根据项目记忆知识，系统遵循"数据库配置优先于配置文件"的原则：

```
配置文件默认值 → 数据库门店配置 → 实际应用
     ↓                 ↓              ↓
   1000ms  →  门店设置2000ms  → 实际使用2000ms
```

**特点：**
- 配置文件提供默认值
- 门店个性化设置从数据库读取
- 后台修改实时生效

### 2. 合理设置时间

**避免极端值：**
- ❌ 过快（< 500ms）：游客来不及看清
- ❌ 过慢（> 10秒）：等待时间过长，体验差

**推荐范围：**
- ✅ 单张图片：800-1500ms（最常用）
- ✅ 单组展示：4000-6000ms（最常用）

### 3. 保持合理比例

**建议比例：**
```
单组展示时长 = 单张图片时长 × 照片数量 × 1.2

示例：
3张照片，单张1000ms
→ 单组建议：1000 × 3 × 1.2 = 3600ms（约4秒）
```

---

## 📊 技术实现

### 数据流程

```
┌─────────────────┐
│ 商户后台配置     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 保存到数据库     │ ← xpd_image_duration
│ (ddwx_mendian)  │ ← xpd_group_duration
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 选片端请求API    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ API查询门店配置  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 返回配置到前端   │ ← config字段
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 前端应用配置     │ ← 更新 config.imageDuration
│                 │ ← 更新 config.groupDuration
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 按新配置轮播     │
└─────────────────┘
```

### 核心代码

#### 1. 数据库字段定义

```sql
ALTER TABLE `ddwx_mendian` 
ADD COLUMN `xpd_image_duration` INT UNSIGNED DEFAULT 1000 
COMMENT '单张图片展示时长(毫秒)',
ADD COLUMN `xpd_group_duration` INT UNSIGNED DEFAULT 5000 
COMMENT '单组展示时长(毫秒)';
```

#### 2. 后台表单

```html
<div class="layui-form-item">
    <label class="layui-form-label">轮播时间设置：</label>
    <div class="layui-form-mid">单张图片展示</div>
    <div class="layui-input-inline" style="width:100px">
        <input type="number" name="info[xpd_image_duration]" 
               value="{$info.xpd_image_duration|default='1000'}" 
               min="500" max="3000" step="100">
    </div>
    <div class="layui-form-mid">毫秒，单组展示</div>
    <div class="layui-input-inline" style="width:100px">
        <input type="number" name="info[xpd_group_duration]" 
               value="{$info.xpd_group_duration|default='5000'}" 
               min="3000" max="10000" step="500">
    </div>
    <div class="layui-form-mid">毫秒</div>
</div>
```

#### 3. API接口

```php
// 查询门店配置
$mendian_config = [];
if ($mdid > 0) {
    $mendian = Db::name('mendian')
        ->where('id', $mdid)
        ->field('xpd_template, xpd_image_duration, xpd_group_duration')
        ->find();
    
    if ($mendian) {
        $mendian_config = [
            'template' => $mendian['xpd_template'] ?: 'template_1',
            'image_duration' => intval($mendian['xpd_image_duration']) ?: 1000,
            'group_duration' => intval($mendian['xpd_group_duration']) ?: 5000
        ];
    }
}

return json([
    'status' => 1,
    'data' => [
        'list' => $portraits,
        'config' => $mendian_config
    ]
]);
```

#### 4. 前端应用

```javascript
// 应用门店配置
if (response.data.data.config) {
    const config = response.data.data.config;
    if (config.image_duration) {
        this.config.imageDuration = config.image_duration;
    }
    if (config.group_duration) {
        this.config.groupDuration = config.group_duration;
    }
    console.log('应用门店配置:', {
        imageDuration: this.config.imageDuration,
        groupDuration: this.config.groupDuration
    });
}
```

---

## 🧪 测试验证

### 功能测试清单

- [x] 后台配置表单显示正确
- [x] 输入验证生效（范围限制）
- [x] 配置保存到数据库
- [x] API返回配置数据
- [x] 前端读取并应用配置
- [x] 轮播时间实际生效
- [x] 不同门店配置隔离
- [x] 默认值正确应用

### 测试步骤

#### 1. 后台配置测试

```
步骤1：编辑门店
步骤2：设置单张图片为2000ms
步骤3：设置单组展示为8000ms
步骤4：点击提交保存
步骤5：验证数据库字段值正确
```

#### 2. 前端应用测试

```
步骤1：打开选片URL
步骤2：打开浏览器控制台
步骤3：观察日志"应用门店配置"
步骤4：验证配置值正确
步骤5：观察实际轮播速度
步骤6：确认时间与配置一致
```

#### 3. 多门店测试

```
门店A：设置1000ms/5000ms
门店B：设置2000ms/8000ms

验证：
- 门店A选片页面使用门店A配置
- 门店B选片页面使用门店B配置
- 配置互不影响
```

---

## 📝 文件变更记录

### 新增文件

| 文件 | 大小 | 说明 |
|-----|------|------|
| `database/migrations/xpd_duration_config.sql` | 1KB | SQL迁移脚本 |
| `migrate_xpd_duration.php` | 2KB | PHP迁移脚本 |
| `xpd/DURATION_CONFIG_FEATURE.md` | - | 本功能说明 |

### 修改文件

| 文件 | 变更 | 说明 |
|-----|------|------|
| `app/view/mendian/edit.html` | +20行 | 后台配置表单 |
| `app/controller/ApiAiTravelPhoto.php` | +28行 | API返回配置 |
| `xpd/templates/template_1/index.html` | +15行 | 前端应用配置 |

---

## 💬 常见问题

### Q1: 修改配置后需要刷新页面吗？

**A:** 是的，需要刷新选片页面才能应用新配置。

- 后台保存配置后，立即生效到数据库
- 选片页面下次加载时自动读取新配置
- 如已打开页面，按F5刷新即可

### Q2: 如果不设置，使用什么值？

**A:** 使用默认值：
- 单张图片：1000ms（1秒）
- 单组展示：5000ms（5秒）

### Q3: 可以设置得更快或更慢吗？

**A:** 系统有范围限制：
- 单张图片：500-3000ms
- 单组展示：3000-10000ms

超出范围的值会被限制在范围内。

### Q4: 不同门店可以设置不同时间吗？

**A:** 可以！每个门店独立配置：
- 门店A可以设置快速轮播
- 门店B可以设置慢速轮播
- 互不影响

### Q5: 配置会影响演示数据吗？

**A:** 会的，演示数据也会使用配置的时间。

---

## 🎉 版本信息

**功能版本：** v1.3.0  
**发布日期：** 2026-01-23  
**更新内容：** 轮播时间可配置功能  
**兼容性：** 完全兼容 v1.0.0 - v1.2.0

---

**相关文档：**
- [主文档](README.md)
- [更新日志](CHANGELOG.md)
- [快速启动](QUICKSTART.md)
