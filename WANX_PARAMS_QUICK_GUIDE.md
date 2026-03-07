# 通义千问图像编辑增强版 - 参数配置完成

## 📋 完成摘要

✅ **成功添加4个parameters参数**
- n - 输出图像数量
- size - 输出图像分辨率  
- prompt_extend - 提示词智能改写
- watermark - 添加水印

✅ **清理测试数据**
- 删除 test_param 参数
- 删除 verify_test 参数和响应字段

✅ **当前状态**
- 模型：通义千问图像编辑增强版 (qwen-image-edit-max)
- 参数总数：**15个**（3个必填 + 12个可选）
- 文档：已创建完整说明文档

---

## 🎯 快速验证

### 查看参数列表
```bash
php /www/wwwroot/eivie/list_parameters.php
```

### 在管理界面查看
访问：http://192.168.11.222/?s=/ModelConfig/parameters/model_id/1

---

## 📝 新增参数详细说明

### 1️⃣ n - 输出图像数量
```json
{
  "n": 3  // 一次生成3张图片（范围：1-6）
}
```
- **用途**：同时生成多张不同效果的图片供选择
- **限制**：qwen-image-edit-max支持1-6张，qwen-image-edit仅支持1张
- **建议**：生成多张图片时注意计费和性能

### 2️⃣ size - 输出图像分辨率
```json
{
  "size": "1024*1536"  // 2:3比例
}
```
- **格式**：宽*高（使用星号分隔）
- **范围**：宽和高均为 512-2048 像素
- **常用**：
  - 正方形：1024*1024
  - 竖版：1024*1536（2:3）
  - 横版：1920*1080（16:9）
- **默认**：不设置时自动保持输入图像宽高比

### 3️⃣ prompt_extend - 提示词智能改写
```json
{
  "prompt_extend": true  // 开启智能改写
}
```
- **作用**：自动优化简单的提示词
- **建议**：默认开启，提升生成效果
- **适用**：当提示词描述较简单时效果明显

### 4️⃣ watermark - 添加水印
```json
{
  "watermark": false  // 不添加水印
}
```
- **位置**：图像右下角
- **内容**："Qwen-Image"文字水印
- **默认**：false（不添加）

---

## 🔥 完整API调用示例

```json
{
  "model": "qwen-image-edit-max",
  "input": {
    "reference_image": "https://example.com/beach.jpg",
    "prompt": "将天空改为日落时分的橙红色"
  },
  "parameters": {
    // 新增的参数
    "n": 2,                      // 生成2张
    "size": "1920*1080",         // 16:9横版
    "prompt_extend": true,       // 智能优化提示词
    "watermark": false,          // 不加水印
    
    // 原有的参数
    "negative_prompt": "低质量、模糊、失真",
    "seed": 42,                  // 固定随机种子
    "strength": 0.7,             // 编辑强度
    "guidance_scale": 7.5        // 引导系数
  }
}
```

---

## 📊 参数组合建议

### 场景1：高质量单图生成
```json
{
  "n": 1,
  "size": "1536*1536",
  "prompt_extend": true,
  "guidance_scale": 9.0,
  "num_inference_steps": 80
}
```

### 场景2：批量快速生成
```json
{
  "n": 6,
  "size": "1024*1024",
  "prompt_extend": true,
  "num_inference_steps": 30
}
```

### 场景3：精确可复现
```json
{
  "n": 1,
  "size": "1024*1536",
  "seed": 12345,
  "prompt_extend": false
}
```

---

## ⚠️ 注意事项

1. **模型兼容性**
   - 部分参数仅在qwen-image-edit-max/plus中可用
   - qwen-image-edit仅支持n=1

2. **性能考虑**
   - n越大，生成时间越长
   - size分辨率越高，处理越慢
   - 合理权衡质量和速度

3. **计费影响**
   - 多张输出(n>1)会按张数计费
   - 高分辨率可能产生额外费用

4. **参数验证**
   - size格式必须为"宽*高"
   - n必须在1-6范围内
   - boolean类型使用true/false

---

## 🛠️ 前端集成示例

### JavaScript调用
```javascript
const params = {
  n: parseInt(document.getElementById('image-count').value),
  size: document.getElementById('resolution').value,
  prompt_extend: document.getElementById('ai-optimize').checked,
  watermark: document.getElementById('add-watermark').checked,
  seed: document.getElementById('seed').value || undefined
};

// 发送请求
fetch('/api/image-edit', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    model: 'qwen-image-edit-max',
    input: { reference_image, prompt },
    parameters: params
  })
});
```

### HTML表单
```html
<select id="image-count">
  <option value="1">生成1张</option>
  <option value="2">生成2张</option>
  <option value="3">生成3张</option>
  <option value="6">生成6张</option>
</select>

<select id="resolution">
  <option value="1024*1024">正方形 (1:1)</option>
  <option value="1024*1536">竖版 (2:3)</option>
  <option value="1920*1080">横版 (16:9)</option>
</select>

<label>
  <input type="checkbox" id="ai-optimize" checked>
  智能优化提示词
</label>

<label>
  <input type="checkbox" id="add-watermark">
  添加水印
</label>
```

---

## 📚 相关文档

- 详细说明：[WANX_PARAMETERS_COMPLETION.md](./WANX_PARAMETERS_COMPLETION.md)
- 参数查询工具：`php list_parameters.php`
- 管理界面：http://192.168.11.222/?s=/ModelConfig/parameters/model_id/1

---

## ✨ 更新日志

**2026-02-04**
- ✅ 根据官方API文档添加4个parameters参数
- ✅ 清理测试数据
- ✅ 创建完整文档
- ✅ 验证参数配置正确性

**参数配置状态**：🟢 已完成并验证

---

**如有问题，请查看详细文档或联系开发团队。**
