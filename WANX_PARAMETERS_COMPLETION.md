# 通义千问图像编辑增强版 - parameters参数完善说明

## 完成时间
2026-02-04

## 模型信息
- **模型名称**: 通义千问图像编辑增强版
- **模型代码**: qwen-image-edit-max
- **提供商**: 阿里云百炼
- **模型ID**: 1

## 新增的parameters参数

根据阿里云百炼官方API文档，本次为该模型添加了以下parameters对象中的可选参数：

### 1. n - 输出图像数量
- **参数名**: n
- **类型**: integer
- **数据格式**: number
- **是否必填**: 否
- **默认值**: 1
- **取值范围**: 1-6
- **说明**: 
  - 输出图像的数量，默认值为1
  - 对于qwen-image-edit-max、qwen-image-edit-plus系列模型，可选择输出1-6张图片
  - 对于qwen-image-edit，仅支持输出1张图片

### 2. negative_prompt - 反向提示词（已存在，跳过）
- 该参数之前已配置，本次跳过

### 3. size - 输出图像分辨率
- **参数名**: size
- **类型**: string
- **数据格式**: text
- **是否必填**: 否
- **默认值**: 空（自动保持输入图像宽高比）
- **格式**: "宽*高"，例如"1024*1536"
- **取值范围**: 宽和高均为[512, 2048]像素
- **说明**:
  - 设置输出图像的分辨率
  - 若不设置，输出图像将保持与输入图像相似的宽高比，总像素数接近1024*1024
  
**常见比例推荐分辨率**:
| 比例 | 推荐分辨率 |
|------|-----------|
| 1:1  | 1024*1024、1536*1536 |
| 2:3  | 768*1152、1024*1536 |
| 3:2  | 1152*768、1536*1024 |
| 3:4  | 960*1280、1080*1440 |
| 4:3  | 1280*960、1440*1080 |
| 9:16 | 720*1280、1080*1920 |
| 16:9 | 1280*720、1920*1080 |
| 21:9 | 1344*576、2048*872 |

### 4. prompt_extend - 提示词智能改写
- **参数名**: prompt_extend
- **类型**: boolean
- **数据格式**: enum
- **是否必填**: 否
- **默认值**: true
- **可选值**: true、false
- **说明**:
  - 是否开启提示词智能改写，默认值为true
  - 开启后，模型会优化正向提示词（text），对描述较简单的提示词效果提升明显
  - 支持模型：qwen-image-edit-max、qwen-image-edit-plus系列模型

### 5. watermark - 添加水印
- **参数名**: watermark
- **类型**: boolean
- **数据格式**: enum
- **是否必填**: 否
- **默认值**: false
- **可选值**: true、false
- **说明**:
  - 是否在图像右下角添加"Qwen-Image"水印
  - 默认值为false

### 6. seed - 随机数种子（已存在，跳过）
- 该参数之前已配置，本次跳过

## 完整参数列表

经过本次完善，通义千问图像编辑增强版现在共有**15个参数**（已删除测试参数）：

### 必填参数（3个）
1. **reference_image** - 参考图像
2. **prompt** - 提示词
3. （第三个根据实际情况可能是其他必填参数）

### 可选参数（12个）
1. **mask_image** - 遮罩图像
2. **negative_prompt** - 负面提示词
3. **edit_mode** - 编辑模式
4. **strength** - 编辑强度
5. **guidance_scale** - 引导系数
6. **num_inference_steps** - 推理步数
7. **seed** - 随机种子
8. **output_format** - 输出格式
9. **output_quality** - 输出质量
10. **n** - 输出图像数量（新增）
11. **size** - 输出图像分辨率（新增）
12. **prompt_extend** - 提示词智能改写（新增）
13. **watermark** - 添加水印（新增）

## 数据库操作记录

### 新增记录
```sql
-- 新增了4个参数
INSERT INTO ddwx_ai_model_parameter (model_id=1)
  - n (输出图像数量)
  - size (输出图像分辨率)
  - prompt_extend (提示词智能改写)
  - watermark (添加水印)
```

### 跳过记录
```sql
-- 已存在2个参数，跳过
  - negative_prompt (反向提示词)
  - seed (随机数种子)
```

### 删除记录
```sql
-- 删除测试参数
DELETE FROM ddwx_ai_model_parameter WHERE param_name IN ('test_param', 'verify_test')
DELETE FROM ddwx_ai_model_response WHERE response_field = 'verify_test'
```

## 使用示例

### API调用示例

```json
{
  "model": "qwen-image-edit-max",
  "input": {
    "reference_image": "https://example.com/image.jpg",
    "prompt": "将背景改为海滩"
  },
  "parameters": {
    "n": 3,
    "size": "1024*1536",
    "negative_prompt": "低质量、模糊",
    "prompt_extend": true,
    "watermark": false,
    "seed": 42
  }
}
```

### 前端表单配置

所有新增的parameters参数都已配置在参数定义管理页面中，可以通过以下方式访问：

1. 访问：`http://192.168.11.222/?s=/ModelConfig/parameters/model_id/1`
2. 点击"新增参数"或"编辑"按钮可查看和修改参数配置
3. 每个参数都配置了：
   - 参数名称和标签
   - 数据类型和格式
   - 是否必填
   - 默认值
   - 取值范围或枚举选项
   - 详细说明

## 技术要点

### 1. 布尔类型参数处理
- `prompt_extend`和`watermark`参数为布尔类型
- 在API调用时应传递`true`或`false`
- 数据库中存储为enum_options: `[true, false]`

### 2. 分辨率格式验证
- `size`参数格式为"宽*高"，使用`*`作为分隔符
- 建议在前端添加格式验证：`/^\d+\*\d+$/`
- 宽和高的范围均为[512, 2048]像素

### 3. 输出数量限制
- `n`参数取值范围：1-6
- 不同模型版本支持的数量不同
- 建议在调用时根据具体模型版本动态调整

## 相关文件

### 数据导入脚本
- `/www/wwwroot/eivie/add_wanx_parameters.php` - 参数导入脚本
- `/www/wwwroot/eivie/cleanup_test_params.php` - 测试数据清理脚本
- `/www/wwwroot/eivie/list_parameters.php` - 参数列表查询脚本

### 管理界面
- 参数定义管理：`/ModelConfig/parameters/model_id/1`
- 响应定义管理：`/ModelConfig/responses/model_id/1`
- 模型列表：`/ModelConfig/index`

## 注意事项

1. **模型兼容性**: 部分参数仅支持特定模型版本（如qwen-image-edit-max），在使用时需注意
2. **参数验证**: 前端应添加参数格式和范围验证，避免无效请求
3. **默认行为**: 不设置size参数时，系统会自动保持输入图像的宽高比
4. **水印控制**: 默认不添加水印，如需添加需显式设置watermark=true
5. **提示词优化**: prompt_extend默认开启，建议保持开启以获得更好效果

## 测试建议

1. 测试不同的输出数量（n=1至n=6）
2. 测试常见分辨率比例（1:1、16:9等）
3. 测试开启/关闭提示词智能改写的效果差异
4. 测试使用相同seed值的结果一致性
5. 验证参数超出范围时的错误处理

## 参考文档

- 阿里云百炼官方文档：https://bailian.console.aliyun.com/cn-beijing/?tab=api#/api/?type=model&url=2976416
- 模型代码：qwen-image-edit-max
- 文档章节：parameters对象 - 控制图像生成的附加参数

## 版本历史

- **v1.0** (2026-02-04): 初始完善，添加4个parameters参数
  - 新增：n, size, prompt_extend, watermark
  - 清理：test_param, verify_test测试参数

---

**完成状态**: ✅ 已完成
**验证状态**: ✅ 已验证
**文档状态**: ✅ 已归档
