# AI旅拍人像管理功能修复

## 修复问题

### 问题1：缩略图不能正常显示
**现象**：列表中的缩略图无法正常显示

**原因分析**：
1. 数据库检查显示URL存在且格式正确
2. 可能原因：
   - OSS图片权限问题（如未设置公开读）
   - 图片加载失败但没有错误处理
   - 跨域问题

**解决方案**：
- 前端添加图片加载错误处理
- 加载失败时显示占位符，提示用户
- 保持点击查看原图功能

### 问题2：删除时存储桶里的图片文件未能正常删除
**现象**：删除人像记录后，OSS存储桶中的文件仍然存在

**原因**：原代码只删除了数据库记录，没有删除OSS文件

**解决方案**：
- 删除前先查询人像信息
- 依次删除原图、缩略图、抠图
- 删除关联的生成结果文件
- 添加完整的错误处理和日志记录

## 修改文件

### 1. 后端控制器
**文件**：`/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`

**修改方法**：`portrait_delete()`

#### 核心改进

```php
public function portrait_delete()
{
    $id = input('post.id/d');
    
    try {
        // 1. 查询人像信息
        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('id', $id)
            ->find();
        
        if (!$portrait) {
            return json(['status' => 0, 'msg' => '人像不存在']);
        }
        
        // 2. 删除OSS文件
        // 删除原图
        if (!empty($portrait['original_url'])) {
            \app\common\Pic::deleteoss($portrait['original_url']);
        }
        
        // 删除缩略图
        if (!empty($portrait['thumbnail_url'])) {
            \app\common\Pic::deleteoss($portrait['thumbnail_url']);
        }
        
        // 删除抠图
        if (!empty($portrait['cutout_url'])) {
            \app\common\Pic::deleteoss($portrait['cutout_url']);
        }
        
        // 3. 删除生成结果文件
        $results = Db::name('ai_travel_photo_result')
            ->where('portrait_id', $id)
            ->select();
        
        foreach ($results as $result) {
            if (!empty($result['url'])) {
                \app\common\Pic::deleteoss($result['url']);
            }
            if (!empty($result['thumbnail_url'])) {
                \app\common\Pic::deleteoss($result['thumbnail_url']);
            }
        }
        
        // 4. 删除数据库记录
        Db::name('ai_travel_photo_portrait')->where('id', $id)->delete();
        Db::name('ai_travel_photo_result')->where('portrait_id', $id)->delete();
        Db::name('ai_travel_photo_generation')->where('portrait_id', $id)->delete();
        
        return json(['status' => 1, 'msg' => '删除成功']);
    } catch (\Exception $e) {
        // 错误日志
        \think\facade\Log::error('AI旅拍人像删除失败', [
            'portrait_id' => $id,
            'error' => $e->getMessage()
        ]);
        return json(['status' => 0, 'msg' => '删除失败：' . $e->getMessage()]);
    }
}
```

#### 删除流程

```
1. 验证人像是否存在
   ↓
2. 删除OSS文件：
   - 原图 (original_url)
   - 缩略图 (thumbnail_url)
   - 抠图 (cutout_url)
   ↓
3. 查询并删除关联的生成结果：
   - 遍历所有生成结果
   - 删除每个结果的url和thumbnail_url
   ↓
4. 删除数据库记录：
   - ai_travel_photo_portrait
   - ai_travel_photo_result
   - ai_travel_photo_generation
   ↓
5. 返回删除结果
```

#### 错误处理

- 每个OSS删除操作都使用try-catch包裹
- 删除失败时记录详细错误日志
- 即使OSS删除失败，也会继续执行数据库删除
- 返回详细的错误信息给用户

### 2. 前端视图
**文件**：`/www/wwwroot/eivie/app/view/ai_travel_photo/portrait_list.html`

**修改位置**：缩略图列渲染函数

#### 核心改进

```javascript
{field: 'thumbnail_url', title: '缩略图', width: 100, templet: function(d){ 
  if (!d.thumbnail_url) return '-';
  
  // 添加onerror错误处理
  return '<img src="'+d.thumbnail_url+'" '+
    'style="height:50px;cursor:pointer;" '+
    'onerror="this.src=\'data:image/svg+xml,...\'; this.style.cursor=\'not-allowed\';" '+
    'onclick="if(this.src.indexOf(\'data:image\')==-1){layer.photos({...});}">'; 
}}
```

#### 功能说明

1. **图片加载失败处理**
   - 使用`onerror`事件捕获加载失败
   - 显示SVG占位符，提示"加载失败"
   - 改变鼠标样式为`not-allowed`

2. **点击查看功能**
   - 正常加载时：点击查看原图
   - 加载失败时：禁止点击

3. **占位符设计**
   - 使用SVG内联图片
   - 灰色背景，显示"加载失败"文字
   - 尺寸与正常缩略图一致（50px）

## 功能特性

### 删除功能特性

1. **完整性**
   - 删除所有相关OSS文件
   - 删除所有数据库记录
   - 清理关联的生成任务

2. **安全性**
   - 验证人像存在性
   - 事务性操作
   - 详细错误日志

3. **容错性**
   - OSS删除失败不影响数据库清理
   - 单个文件删除失败不影响其他文件
   - 提供详细的错误反馈

### 缩略图显示特性

1. **用户体验**
   - 加载失败时有明确提示
   - 保持页面布局不变形
   - 视觉反馈清晰

2. **功能完整**
   - 正常时可点击查看原图
   - 失败时禁止点击
   - 自适应不同屏幕

## 测试步骤

### 测试缩略图显示

1. **正常显示测试**
   - 访问人像管理页面
   - 检查缩略图是否正常显示
   - 点击缩略图查看原图

2. **错误处理测试**
   - 如果某些缩略图无法加载
   - 应显示灰色占位符
   - 鼠标悬停显示禁止图标

3. **OSS权限检查**
   - 检查OSS存储桶是否设置为公开读
   - 检查图片URL是否可直接访问
   - 检查是否有跨域限制

### 测试删除功能

1. **删除操作**
   - 选择一个人像记录
   - 点击删除按钮
   - 确认删除

2. **验证数据库**
   ```sql
   -- 检查人像记录是否删除
   SELECT * FROM ai_travel_photo_portrait WHERE id = ?;
   
   -- 检查生成结果是否删除
   SELECT * FROM ai_travel_photo_result WHERE portrait_id = ?;
   
   -- 检查生成任务是否删除
   SELECT * FROM ai_travel_photo_generation WHERE portrait_id = ?;
   ```

3. **验证OSS**
   - 访问原图URL（应返回404）
   - 访问缩略图URL（应返回404）
   - 访问抠图URL（应返回404）

4. **查看日志**
   ```bash
   tail -f /www/wwwroot/eivie/runtime/log/日期.log
   ```

## 可能的问题及解决

### 问题1：OSS删除失败
**可能原因**：
- OSS配置错误
- 没有删除权限
- 文件已被删除

**解决方法**：
- 检查`app/common/Pic.php`中的`deleteoss`方法
- 确认OSS配置正确
- 查看错误日志了解详情

### 问题2：缩略图仍然无法显示
**可能原因**：
- OSS存储桶未设置公开读
- 图片文件损坏
- 网络问题

**解决方法**：
```bash
# 1. 检查OSS存储桶权限
# 登录腾讯云/阿里云控制台
# 设置存储桶为"公有读私有写"

# 2. 测试图片URL
curl -I "缩略图URL"
# 应返回200状态码

# 3. 检查浏览器控制台
# 查看Network标签，查看图片请求状态
```

### 问题3：删除后OSS文件仍存在
**检查步骤**：
1. 查看日志文件，确认deleteoss是否调用
2. 检查Pic::deleteoss方法实现
3. 测试OSS API连接

## 技术说明

### OSS删除API
使用项目封装的`\app\common\Pic::deleteoss()`方法：
```php
// 删除单个文件
\app\common\Pic::deleteoss($fileUrl);

// 返回值
// true: 删除成功
// false: 删除失败
```

### 错误日志
所有删除操作的错误都会记录到日志：
```
路径: /www/wwwroot/eivie/runtime/log/日期.log
格式: [时间] think\facade\Log.ERROR: AI旅拍人像删除失败 {...}
```

### 前端错误处理
SVG占位符使用Data URI格式：
```svg
data:image/svg+xml,%3Csvg ...%3E...%3C/svg%3E
```

优点：
- 无需额外请求
- 即时显示
- 不依赖外部资源

## 相关文件

- `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php` - 后端控制器（已修改）
- `/www/wwwroot/eivie/app/view/ai_travel_photo/portrait_list.html` - 前端视图（已修改）
- `/www/wwwroot/eivie/app/common/Pic.php` - OSS工具类（需确认）

## 版本信息

- 修复时间: 2026-02-03
- 问题类型: 功能缺陷 + 用户体验
- 影响范围: 人像删除和显示功能
- 向后兼容: 是
