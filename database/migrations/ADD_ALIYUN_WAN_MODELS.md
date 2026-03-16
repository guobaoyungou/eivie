# 阿里云万相Wan系列模型添加说明

## 概述

本迁移文件用于向模型广场添加6个阿里云万相Wan系列AI模型。

## 添加的模型列表

| 序号 | 模型代码 | 模型名称 | 类型 | API端点 |
|------|---------|---------|------|---------|
| 1 | `wan2.6-image` | 万相2.6图像生成 | image_generation | https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis |
| 2 | `wan2.5-i2i-preview` | 万相2.5图生图预览 | image_generation | https://dashscope.aliyuncs.com/api/v1/services/aigc/image-generation/image-edit |
| 3 | `wanx2.1-imageedit` | 万相2.1图像编辑 | image_generation | https://dashscope.aliyuncs.com/api/v1/services/aigc/image-generation/image-edit |
| 4 | `wan2.6-i2v-flash` | 万相2.6图生视频快速版 | video_generation | https://dashscope.aliyuncs.com/api/v1/services/aigc/video-generation/video-synthesis |
| 5 | `wan2.6-i2v` | 万相2.6图生视频 | video_generation | https://dashscope.aliyuncs.com/api/v1/services/aigc/video-generation/video-synthesis |
| 6 | `wan2.5-i2v-preview` | 万相2.5图生视频预览 | video_generation | https://dashscope.aliyuncs.com/api/v1/services/aigc/video-generation/video-synthesis |

## 执行方法

### 方法1：使用MySQL命令行

```bash
# 进入项目目录
cd /home/www/ai.eivie.cn

# 执行SQL迁移文件
mysql -u 用户名 -p 数据库名 < database/migrations/preset_aliyun_wan_models.sql
```

### 方法2：使用ThinkPHP命令行

```bash
# 进入项目目录
cd /home/www/ai.eivie.cn

# 使用PHP执行SQL文件
php think migrate:run database/migrations/preset_aliyun_wan_models.sql
```

### 方法3：通过数据库管理工具（如phpMyAdmin）

1. 登录phpMyAdmin
2. 选择目标数据库
3. 点击"SQL"选项卡
4. 复制 `preset_aliyun_wan_models.sql` 文件内容
5. 粘贴并执行

## 验证

执行完成后，可以运行以下SQL验证模型是否添加成功：

```sql
SELECT 
    m.id,
    m.model_code,
    m.model_name,
    m.model_version,
    p.provider_name,
    t.type_name,
    m.task_type,
    m.is_active
FROM ddwx_model_info m
LEFT JOIN ddwx_model_provider p ON m.provider_id = p.id
LEFT JOIN ddwx_model_type t ON m.type_id = t.id
WHERE m.model_code LIKE 'wan%'
ORDER BY m.sort ASC;
```

## 模型配置说明

### 图像生成模型 (wan2.6-image)

- **支持功能**: 文生图
- **输入参数**:
  - `prompt`: 图像内容描述（必填）
  - `negative_prompt`: 负面提示词
  - `size`: 图像尺寸（1024*1024, 1024*576, 576*1024等）
  - `n`: 生成数量（1-4）
  - `seed`: 随机种子

### 图生图/图像编辑模型 (wan2.5-i2i-preview, wanx2.1-imageedit)

- **支持功能**: 图生图、图像编辑、局部重绘
- **输入参数**:
  - `image`: 参考图像URL（必填）
  - `prompt`: 目标描述（必填）
  - `mask_image`: 遮罩图（局部编辑时）
  - `strength`: 重绘幅度（0.0-1.0）

### 视频生成模型 (wan2.6-i2v-flash, wan2.6-i2v, wan2.5-i2v-preview)

- **支持功能**: 图生视频、首尾帧图生视频
- **输入参数**:
  - `image`: 首帧图像URL（必填）
  - `last_frame_image`: 尾帧图像URL（首尾帧模式）
  - `prompt`: 视频内容描述
  - `duration`: 视频时长（秒）
  - `resolution`: 分辨率（480P, 720P, 1080P）
  - `fps`: 帧率（16, 24, 30）

## API调用说明

所有阿里云万相模型都使用DashScope API，请求格式如下：

**请求头**:
```
Authorization: Bearer {API_KEY}
Content-Type: application/json
X-DashScope-Async: enable
```

**请求体示例（图像生成）**:
```json
{
  "model": "wanx2.1-t2i-turbo",
  "input": {
    "prompt": "一只可爱的猫咪"
  },
  "parameters": {
    "size": "1024*1024",
    "n": 1
  }
}
```

**请求体示例（视频生成）**:
```json
{
  "model": "wan2.1-i2v-plus",
  "input": {
    "image": "https://example.com/image.jpg",
    "prompt": "让图片动起来"
  },
  "parameters": {
    "resolution": "720P",
    "duration": 5
  }
}
```

## 参考文档

- [阿里云万相图像生成API文档](https://help.aliyun.com/zh/model-studio/wan-image-generation-api-reference)
- [阿里云万相视频生成API文档](https://help.aliyun.com/zh/model-studio/wan-video-to-video-api-reference)
- [阿里云首尾帧图生视频API文档](https://help.aliyun.com/zh/model-studio/image-to-video-by-first-and-last-frame-api-reference)

## 注意事项

1. 执行前请确保数据库中已存在阿里云供应商记录（`provider_code = 'aliyun'`）
2. 执行前请确保数据库中已存在模型类型记录（`image_generation`, `video_generation`）
3. 如需使用这些模型，请在系统后台配置有效的阿里云DashScope API Key
4. 所有阿里云模型均为异步任务类型，需要在后台轮询任务状态
