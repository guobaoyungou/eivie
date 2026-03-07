# AI旅拍功能 - 修复设置保存问题

## 问题描述

用户点击"AI旅拍系统设置"页面的"保存"按钮后，页面一直转圈圈，没有任何响应。

## 问题原因

### 1. 后端返回格式错误

**错误代码**：
```php
public function settings()
{
    if (request()->isPost()) {
        // ...更新数据库...
        $this->success('保存成功');  // ❌ 返回跳转页面
    }
}
```

**问题**：
- 前端使用`$.post()`发送AJAX请求
- 前端期望返回JSON格式：`{status: 1, msg: '保存成功'}`
- 后端使用`$this->success()`返回HTML跳转页面
- 导致前端无法正确解析响应

### 2. 数据库字段缺失

`ddwx_business`表缺少以下8个AI旅拍配置字段：
- `ai_photo_price` - 单张图片价格
- `ai_video_price` - 单个视频价格
- `ai_logo_watermark` - Logo水印图片
- `ai_watermark_position` - 水印位置
- `ai_qrcode_expire_days` - 二维码有效期
- `ai_auto_generate_video` - 自动生成视频
- `ai_video_duration` - 视频时长
- `ai_max_scenes` - 最大生成场景数

## 修复方案

### 1. 修改后端返回格式

**正确代码**：
```php
public function settings()
{
    if (request()->isPost()) {
        $data = input('post.');

        try {
            Db::name('business')->where('id', $this->bid)->update([
                'ai_travel_photo_enabled' => $data['ai_travel_photo_enabled'] ?? 0,
                'ai_photo_price' => $data['ai_photo_price'] ?? 9.9,
                'ai_video_price' => $data['ai_video_price'] ?? 29.9,
                'ai_logo_watermark' => $data['ai_logo_watermark'] ?? '',
                'ai_watermark_position' => $data['ai_watermark_position'] ?? 1,
                'ai_qrcode_expire_days' => $data['ai_qrcode_expire_days'] ?? 30,
                'ai_auto_generate_video' => $data['ai_auto_generate_video'] ?? 1,
                'ai_video_duration' => $data['ai_video_duration'] ?? 5,
                'ai_max_scenes' => $data['ai_max_scenes'] ?? 10
            ]);

            return json(['status' => 1, 'msg' => '保存成功']);  // ✅ 返回JSON
        } catch (\Exception $e) {
            return json(['status' => 0, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }
    
    $business = Db::name('business')->where('id', $this->bid)->find();
    View::assign('business', $business);
    return View::fetch();
}
```

**改进点**：
- ✅ 使用`return json()`返回JSON格式数据
- ✅ 添加try-catch错误处理
- ✅ 统一返回格式：`{status: 1/0, msg: '消息'}`

### 2. 添加数据库字段

```sql
ALTER TABLE ddwx_business 
ADD COLUMN ai_photo_price DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 9.90 
  COMMENT '单张图片价格' AFTER ai_travel_photo_enabled,
ADD COLUMN ai_video_price DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 29.90 
  COMMENT '单个视频价格' AFTER ai_photo_price,
ADD COLUMN ai_logo_watermark VARCHAR(255) NOT NULL DEFAULT '' 
  COMMENT 'Logo水印图片' AFTER ai_video_price,
ADD COLUMN ai_watermark_position TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 
  COMMENT '水印位置:1=右下角,2=左下角,3=右上角,4=左上角' AFTER ai_logo_watermark,
ADD COLUMN ai_qrcode_expire_days INT(10) UNSIGNED NOT NULL DEFAULT 30 
  COMMENT '二维码有效期(天)' AFTER ai_watermark_position,
ADD COLUMN ai_auto_generate_video TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 
  COMMENT '自动生成视频:0=否,1=是' AFTER ai_qrcode_expire_days,
ADD COLUMN ai_video_duration TINYINT(3) UNSIGNED NOT NULL DEFAULT 5 
  COMMENT '视频时长(秒)' AFTER ai_auto_generate_video,
ADD COLUMN ai_max_scenes INT(10) UNSIGNED NOT NULL DEFAULT 10 
  COMMENT '最大生成场景数' AFTER ai_video_duration;
```

## 字段说明

| 字段名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| ai_travel_photo_enabled | TINYINT(1) | 0 | 功能开关：0=关闭，1=开启 |
| ai_photo_price | DECIMAL(10,2) | 9.90 | 单张图片价格（元） |
| ai_video_price | DECIMAL(10,2) | 29.90 | 单个视频价格（元） |
| ai_logo_watermark | VARCHAR(255) | '' | Logo水印图片URL |
| ai_watermark_position | TINYINT(1) | 1 | 水印位置：1=右下角，2=左下角，3=右上角，4=左上角 |
| ai_qrcode_expire_days | INT(10) | 30 | 二维码有效期（天） |
| ai_auto_generate_video | TINYINT(1) | 1 | 自动生成视频：0=否，1=是 |
| ai_video_duration | TINYINT(3) | 5 | 视频时长（秒）：5或10 |
| ai_max_scenes | INT(10) | 10 | 用户一次最多可选择的场景数 |

## 验证结果

### 数据库字段验证

```bash
mysql> DESC ddwx_business;
+-------------------------+----------------------+------+-----+---------+
| Field                   | Type                 | Null | Key | Default |
+-------------------------+----------------------+------+-----+---------+
| ai_travel_photo_enabled | tinyint unsigned     | NO   |     | 0       |
| ai_photo_price          | decimal(10,2)        | NO   |     | 9.90    |
| ai_video_price          | decimal(10,2)        | NO   |     | 29.90   |
| ai_logo_watermark       | varchar(255)         | NO   |     |         |
| ai_watermark_position   | tinyint unsigned     | NO   |     | 1       |
| ai_qrcode_expire_days   | int unsigned         | NO   |     | 30      |
| ai_auto_generate_video  | tinyint unsigned     | NO   |     | 1       |
| ai_video_duration       | tinyint unsigned     | NO   |     | 5       |
| ai_max_scenes           | int unsigned         | NO   |     | 10      |
+-------------------------+----------------------+------+-----+---------+
```

✅ 所有字段已成功添加

## 测试步骤

1. **访问设置页面**：
   ```
   http://192.168.11.222/?s=/AiTravelPhoto/settings
   ```

2. **修改配置项**：
   - 开启/关闭旅拍功能
   - 修改价格设置
   - 上传水印图片
   - 修改其他配置

3. **点击保存按钮**

4. **预期结果**：
   - ✅ 立即收到"保存成功"的提示
   - ✅ 不会出现转圈现象
   - ✅ 配置成功保存到数据库

## 前端处理逻辑

```javascript
layui.form.on('submit(formsubmit)', function(obj){
    var field = obj.field;
    var index = layer.load();  // 显示加载动画
    $.post("{:url('settings')}", field, function(data){
        layer.close(index);  // 关闭加载动画
        dialog(data.msg, data.status);  // 显示结果消息
    })
    return false;
})
```

**期望后端返回**：
```json
{
    "status": 1,
    "msg": "保存成功"
}
```

## 相关文件

- **控制器**：`/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`
- **视图文件**：`/www/wwwroot/eivie/app/view/ai_travel_photo/settings.html`
- **数据库表**：`ddwx_business`

---

**更新时间**：2026-01-21  
**状态**：✅ 已修复  
**测试状态**：待用户验证
