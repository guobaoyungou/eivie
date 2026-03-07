# AI模型三级分类体系使用指南

## ✅ 已完成功能

### 1. 数据库结构升级

**已添加字段：**
- `ddwx_ai_model_category`表：
  - `level` - 分类层级（1=一级，2=二级）
  - `parent_code` - 父级分类代码
  
- `ddwx_ai_travel_photo_model`表：
  - `category_level2_code` - 二级分类代码
  - `model_version` - 模型版本号（如：qwen-image-max-2025-12-30）

### 2. 三级分类数据

**一级分类（10个）：**
| 代码 | 名称 | 说明 | 图标 |
|------|------|------|------|
| dialogue | 对话模型 | 大语言模型、聊天机器人、智能问答 | 💬 |
| image_generation | 图像生成 | 文生图、图像编辑、图像增强 | 🎨 |
| video_generation | 视频生成 | 文生视频、视频编辑、视频合成 | 🎬 |
| specialized | 专项模型 | 特定领域专用模型 | 🔧 |
| realtime_multimodal | 实时多模态 | 实时语音视频交互模型 | ⚡ |
| tts | 语音合成 | TTS文字转语音 | 🔊 |
| asr | 语音识别 | ASR语音转文字 | 🎤 |
| translation | 语言翻译 | 机器翻译、多语言互译 | 🌐 |
| text_embedding | 通用文本向量 | 文本嵌入、语义检索 | 📊 |
| multimodal_embedding | 多模态向量 | 图文向量、多模态检索 | 🔮 |

**二级分类（16个）：**

**图像生成子分类：**
- `qwen_text_to_image` - 通义千问文生图 🖼️
- `qwen_image_edit` - 通义千问图像编辑 ✏️
- `dalle` - DALL-E系列 🎭
- `stable_diffusion` - Stable Diffusion 🌈
- `midjourney` - Midjourney 🎨

**对话模型子分类：**
- `qwen_turbo` - 通义千问Turbo ⚡
- `gpt` - GPT系列 🤖
- `claude` - Claude系列 🧠
- `gemini` - Gemini系列 ✨

**视频生成子分类：**
- `keling` - 可灵视频 🎥
- `runway` - Runway系列 🎬
- `pika` - Pika系列 📹

**语音合成子分类：**
- `cosyvoice` - CosyVoice 🔊
- `azure_tts` - Azure TTS 🎙️

**语音识别子分类：**
- `paraformer` - Paraformer 🎤
- `whisper` - Whisper系列 🎧

## 📝 使用示例

### 1. 添加通义千问文生图模型配置

访问：后台 -> AI旅拍 -> 模型设置 -> API配置管理 -> 添加API配置

**基础信息：**
- 配置名称：`Qwen Image Max 2025`
- 一级分类：选择 `图像生成` 
- 二级分类：选择 `通义千问文生图`（需前端支持，后续更新）
- 服务提供商：`aliyun`
- 模型版本：`qwen-image-max-2025-12-30`

**API配置：**
- API密钥：`sk-xxxxxxxxxxxxxxxx`
- API基础URL：`https://dashscope.aliyuncs.com/api/v1`
- API版本：`v1`

### 2. 通过代码使用三级分类

**查询一级分类：**
```php
$level1Categories = Db::name('ai_model_category')
    ->where('level', 1)
    ->where('status', 1)
    ->order('sort DESC')
    ->select();
```

**查询某一级分类的子分类：**
```php
$level2Categories = Db::name('ai_model_category')
    ->where('level', 2)
    ->where('parent_code', 'image_generation')
    ->where('status', 1)
    ->order('sort DESC')
    ->select();
```

**查询完整分类树：**
```php
// 一级分类
$tree = Db::name('ai_model_category')
    ->where('level', 1)
    ->where('status', 1)
    ->order('sort DESC')
    ->select()
    ->toArray();

// 为每个一级分类获取子分类
foreach ($tree as &$cat1) {
    $cat1['children'] = Db::name('ai_model_category')
        ->where('level', 2)
        ->where('parent_code', $cat1['code'])
        ->where('status', 1)
        ->order('sort DESC')
        ->select()
        ->toArray();
}
```

**添加模型配置（三级）：**
```php
Db::name('ai_travel_photo_model')->insert([
    'model_name' => 'Qwen Image Max 2025',
    'category_code' => 'image_generation',        // 一级分类
    'category_level2_code' => 'qwen_text_to_image', // 二级分类
    'model_version' => 'qwen-image-max-2025-12-30',  // 三级：具体模型
    'provider' => 'aliyun',
    'api_key' => 'sk-xxxxxxxx',
    'api_base_url' => 'https://dashscope.aliyuncs.com/api/v1',
    'aid' => $aid,
    'bid' => $bid,
    'create_time' => time(),
]);
```

## 🔄 前端界面更新需求

### 需要更新的页面：

1. **model_config_edit.html** - API配置编辑页
   - 添加二级分类下拉框
   - 一级分类变化时，联动加载对应的二级分类
   - 添加模型版本输入框

2. **model_config_list.html** - API配置列表页
   - 显示二级分类名称
   - 显示模型版本号
   - 支持按二级分类筛选

3. **model_category_list.html** - 分类管理页
   - 树形结构显示一级和二级分类
   - 支持添加/编辑二级分类

## 📊 数据统计示例

**查看某二级分类下的模型数量：**
```sql
SELECT 
    c.name as category_name,
    COUNT(m.id) as model_count
FROM ddwx_ai_model_category c
LEFT JOIN ddwx_ai_travel_photo_model m ON c.code = m.category_level2_code
WHERE c.level = 2
GROUP BY c.code
ORDER BY model_count DESC;
```

**查看各一级分类的使用情况：**
```sql
SELECT 
    c1.name as level1_category,
    COUNT(DISTINCT c2.code) as level2_count,
    COUNT(m.id) as model_count
FROM ddwx_ai_model_category c1
LEFT JOIN ddwx_ai_model_category c2 ON c2.parent_code = c1.code
LEFT JOIN ddwx_ai_travel_photo_model m ON m.category_code = c1.code
WHERE c1.level = 1
GROUP BY c1.code
ORDER BY model_count DESC;
```

## 🎯 下一步计划

1. ✅ 数据库结构升级完成
2. ✅ 三级分类数据导入完成
3. ⏳ 前端界面更新（待开发）
4. ⏳ API接口调整（待开发）
5. ⏳ 服务层支持二级分类调用（待开发）

## 📝 迁移记录

- **迁移脚本**：`/www/wwwroot/eivie/insert_three_level_categories.sql`
- **执行时间**：2026-02-04
- **数据统计**：
  - 一级分类：10个
  - 二级分类：16个
  - 数据库表：2个（分类表+模型配置表）
  - 新增字段：4个

---

**当前状态**：✅ 三级分类体系基础架构完成，可开始使用
