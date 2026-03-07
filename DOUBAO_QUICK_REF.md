# 豆包SeeDream 4.5 - 快速参考卡

## 基本信息
- **模型ID**: 3
- **代码**: `doubao-seedream-4-5-251128`
- **定价**: ¥0.25/张（图生图、文生图、多图统一）
- **API**: `https://ark.cn-beijing.volces.com/api/v3/images/generations`

## 🎯 三种模式

### 1. 单图生图（基础模式）
```json
{
  "prompt": "描述期望的图像内容",
  "image": "https://参考图像URL",
  "sequential_image_generation": "disabled"
}
```

### 2. 多图生成组图（新功能）⭐
```json
{
  "prompt": "生成3张早中晚的场景",
  "image": ["https://url1", "https://url2"],
  "sequential_image_generation": "auto",
  "sequential_image_generation_options": {
    "max_images": 3
  },
  "stream": true
}
```

### 3. 文生图模式
```json
{
  "prompt": "描述期望的图像内容",
  "sequential_image_generation": "disabled"
}
```
注：文生图时image参数可不传或传空

## 常用配置
```json
{
  "size": "2K",              // 或 "2560x1440", "4096x4096"
  "response_format": "url",  // 或 "b64_json"
  "watermark": false,        // 生产环境建议false
  "stream": false            // 大文件可启用true
}
```

## 分辨率选项
- `"2K"` - 约2000像素长边
- `"1K"` - 约1000像素长边
- `"2560x1440"` - 2.5K
- `"3840x2160"` - 4K UHD
- `"4096x4096"` - 4K方形

## 限制
- 分辨率范围: 2560×1440 - 4096×4096
- IPM: 500次/分钟
- 输出: 单张图片

## ⚠️ 注意
1. size格式：`"2K"` 或 `"2560x1440"` (用`x`，不是`*`)
2. image参数必填（图生图模式）
3. 与通义千问参数格式不同，需单独处理

## 管理页面
```
http://192.168.11.222/?s=/ModelConfig/parameters/model_id/3
```

## 完整文档
详见: [DOUBAO_SEEDREAM_COMPLETE_CONFIG.md](./DOUBAO_SEEDREAM_COMPLETE_CONFIG.md)
