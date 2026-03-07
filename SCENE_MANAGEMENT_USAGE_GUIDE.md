# AI旅拍场景管理使用指南

## 快速开始

### 1️⃣ 数据库准备（必须）

执行SQL文件添加api_config_id字段：
```bash
cd /www/wwwroot/eivie
mysql -u root -p ddwx < database/migrations/scene_management_redesign_alter.sql
```

或手动执行以下SQL：
```sql
USE ddwx;

ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD COLUMN `api_config_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联API配置ID' AFTER `model_id`,
ADD INDEX `idx_api_config_id` (`api_config_id`);
```

### 2️⃣ 准备AI模型实例数据

确保 `ddwx_ai_model_instance` 表中有可用的模型记录：
```sql
-- 查询现有模型
SELECT id, model_name, provider, status FROM ddwx_ai_model_instance WHERE status=1;

-- 如果没有，需要先添加模型实例
```

### 3️⃣ 准备模型参数定义

为每个模型配置参数定义（在 `ddwx_ai_model_parameter` 表）：
```sql
-- 示例：为模型ID=1添加参数定义
INSERT INTO `ddwx_ai_model_parameter` VALUES
(NULL, 1, 'prompt', '正向提示词', 'textarea', '', 1, 100, 1, '描述想要生成的图像内容', 0, NOW(), NOW()),
(NULL, 1, 'negative_prompt', '负面提示词', 'textarea', '', 0, 90, 1, '排除不想要的元素', 0, NOW(), NOW()),
(NULL, 1, 'steps', '生成步数', 'number', '50', 0, 80, 1, '推荐20-50', 0, NOW(), NOW());
```

### 4️⃣ 准备API配置

确保 `ddwx_api_config` 表中有可用的API配置，并关联到模型：
```sql
-- 查询现有API配置
SELECT id, api_name, provider, model_id, is_active FROM ddwx_api_config WHERE is_active=1;
```

---

## 使用流程

### 新增场景

1. **访问场景管理** → 点击"添加场景"

2. **选择AI模型**（必选）
   - 下拉列表显示所有已启用的模型实例
   - 显示格式：`模型名称 (提供商)`

3. **选择API配置**（必选）
   - 自动加载该模型关联的API配置
   - 显示格式：`API名称 (提供商) [公开/私有]`

4. **填写模型参数**
   - 根据模型配置动态显示参数表单
   - 常见参数：
     - 正向提示词（必填）：描述想要生成的内容
     - 负面提示词（选填）：排除不想要的元素
     - 生成步数、宽高比等

5. **填写场景基础信息**
   - 场景名称（必填）
   - 场景分类：风景/人物/创意/节日/古风/现代
   - 所属门店：选择门店或"全商家可用"
   - 是否公共场景：开启后C端用户可见

6. **上传或生成封面图**
   - 方式1：手动上传封面图
   - 方式2：点击"一键生成封面图"（需先保存场景）

7. **提交保存**

---

## 编辑场景

1. **访问场景管理** → 点击"编辑"按钮

2. **自动加载现有配置**
   - 自动选中模型和API配置
   - 自动填充参数值
   - 自动填充基础信息

3. **修改参数或信息**

4. **重新生成封面图**（可选）
   - 修改参数后可重新生成

5. **保存修改**

---

## 一键生成封面图

### 前提条件
- ✅ 场景已保存（有scene_id）
- ✅ 已选择API配置
- ✅ 已填写必填参数（如prompt）

### 操作步骤
1. 填写完场景信息并保存
2. 点击"一键生成封面图"按钮
3. 确认提示
4. 等待生成（通常10-60秒）
5. 生成成功后自动更新封面图预览

### 注意事项
- 生成过程中请勿关闭页面
- 生成失败时会显示错误信息
- 可多次重新生成

---

## 参数配置说明

### 参数类型支持

| 类型 | 说明 | 示例 |
|------|------|------|
| text | 单行文本 | 场景标签 |
| textarea | 多行文本 | 提示词 |
| number | 数字 | 生成步数 |
| select | 下拉选择 | 风格选项 |
| switch | 开关 | 是否启用 |

### 常用参数配置

#### 1. 正向提示词（prompt）
- **类型**：textarea
- **必填**：是
- **说明**：描述想要生成的图像内容
- **示例**：`海边度假风景，蓝天白云，椰树摇曳，细腻的沙滩，4K高清，专业摄影`

#### 2. 负面提示词（negative_prompt）
- **类型**：textarea
- **必填**：否
- **说明**：排除不想要的元素
- **示例**：`低质量，模糊，变形，水印，文字，低分辨率`

#### 3. 生成步数（steps）
- **类型**：number
- **必填**：否
- **默认值**：50
- **范围**：1-150
- **说明**：步数越多质量越高，但速度越慢

#### 4. CFG Scale（cfg_scale）
- **类型**：number
- **必填**：否
- **默认值**：7.5
- **范围**：1-20
- **说明**：控制生成图像与提示词的契合度

---

## 故障排查

### 问题1：模型列表为空
**原因**：数据库中没有可用的模型实例  
**解决**：
```sql
-- 检查模型表
SELECT * FROM ddwx_ai_model_instance WHERE status=1;

-- 如果为空，需要先添加模型实例
```

### 问题2：API配置列表为空
**原因**：选择的模型没有关联的API配置  
**解决**：
```sql
-- 检查API配置表
SELECT * FROM ddwx_api_config WHERE model_id=? AND is_active=1;

-- 确保 model_id 字段正确关联
```

### 问题3：参数表单不显示
**原因**：该模型没有配置参数定义  
**解决**：
```sql
-- 检查参数定义表
SELECT * FROM ddwx_ai_model_parameter WHERE model_id=? AND is_active=1;

-- 需要为该模型添加参数定义
```

### 问题4：生成封面图失败
**可能原因**：
- API配置无效或已禁用
- 必填参数未填写（如prompt）
- API密钥错误或过期
- API接口尚未实现

**解决**：
1. 检查API配置是否正常
2. 检查必填参数是否完整
3. 查看浏览器控制台错误信息
4. 查看后端日志

### 问题5：字段 api_config_id 不存在
**原因**：数据库表结构未更新  
**解决**：执行第1步的SQL语句

---

## API实现指南（开发者）

### 阿里云通义万相API

实现位置：`app/controller/AiTravelPhoto.php` 的 `callAliyunApi` 方法

关键步骤：
1. 获取API密钥和端点地址
2. 构建请求参数
3. 发送HTTP POST请求
4. 处理异步任务（轮询查询）
5. 返回图片URL

参考代码见 `SCENE_MANAGEMENT_REDESIGN_REPORT.md` 第九章

### 百度文心一言API

实现位置：`app/controller/AiTravelPhoto.php` 的 `callBaiduApi` 方法

类似阿里云实现，需要根据百度API文档调整

### OpenAI DALL-E API

实现位置：`app/controller/AiTravelPhoto.php` 的 `callOpenAiApi` 方法

类似实现，需要根据OpenAI API文档调整

---

## 相关文档

- 📄 [设计方案](./SCENE_MANAGEMENT_REDESIGN_PLAN.md) - 详细的技术设计方案
- 📄 [实施报告](./SCENE_MANAGEMENT_REDESIGN_REPORT.md) - 完整的开发实施报告
- 📄 [数据库SQL](./database/migrations/scene_management_redesign_alter.sql) - 表结构调整SQL

---

**更新时间**：2026-02-04  
**版本**：v2.0  
**适用系统**：AI旅拍管理系统
