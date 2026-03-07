# 🚀 场景管理重构快速启动指南

## 一、一键安装（推荐）

### 方式1：使用完整安装脚本（最简单）

```bash
cd /www/wwwroot/eivie
mysql -u root -p ddwx < database/migrations/scene_management_complete_setup.sql
```

**这个脚本会自动完成：**
- ✅ 添加 api_config_id 字段
- ✅ 创建所需的表（如果不存在）
- ✅ 插入示例模型和参数定义
- ✅ 插入示例API配置（需要后续更新密钥）
- ✅ 验证安装结果

### 方式2：分步执行（适合需要控制的场景）

```bash
cd /www/wwwroot/eivie

# 1. 修改场景表结构
mysql -u root -p ddwx < database/migrations/scene_management_redesign_alter.sql

# 2. 创建模型配置表（如果不存在）
mysql -u root -p ddwx < database/migrations/ai_model_config_tables.sql

# 3. 插入初始化数据
mysql -u root -p ddwx < database/migrations/ai_model_config_init_data.sql

# 4. 创建API配置表（如果不存在）
mysql -u root -p ddwx < database/migrations/api_config_tables.sql
```

---

## 二、配置API密钥（必须）

### 查询需要配置的API

```sql
USE ddwx;
SELECT id, api_name, api_code, provider, api_key, is_active 
FROM ddwx_api_config 
WHERE api_key LIKE '%YOUR_API_KEY_HERE%';
```

### 更新阿里云API密钥

```sql
-- 替换为你的真实API Key
UPDATE ddwx_api_config 
SET api_key = 'sk-xxxxxxxxxxxxxxxxxxxxx',
    is_active = 1,
    update_time = UNIX_TIMESTAMP()
WHERE api_code = 'aliyun_dashscope_default';
```

### 如何获取阿里云API Key

1. 访问阿里云百炼平台：https://bailian.console.aliyun.com/
2. 进入「API-KEY管理」
3. 创建新的API-KEY
4. 复制并更新到数据库

---

## 三、验证安装

### 3.1 检查数据库表结构

```sql
-- 检查场景表字段
DESC ddwx_ai_travel_photo_scene;

-- 应该看到 api_config_id 字段
```

### 3.2 检查数据完整性

```sql
-- 检查模型实例
SELECT id, model_name, provider, is_active FROM ddwx_ai_model_instance;

-- 检查参数定义
SELECT id, model_id, param_name, param_code, is_required 
FROM ddwx_ai_model_parameter 
ORDER BY model_id, sort;

-- 检查API配置
SELECT id, api_name, provider, is_active 
FROM ddwx_api_config;
```

### 3.3 访问测试

1. 登录商家后台
2. 进入「AI旅拍」→「场景管理」
3. 点击「添加场景」
4. 检查是否显示以下内容：
   - ✅ 模型下拉列表有选项
   - ✅ 选择模型后显示API配置下拉
   - ✅ 选择API后显示动态参数表单
   - ✅ 显示场景基础信息表单

---

## 四、功能测试

### 测试1：新增场景

1. **选择模型**：通义千问图像生成
2. **选择API配置**：阿里云通义千问默认配置
3. **填写参数**：
   - 正向提示词：`海边度假风景，蓝天白云，椰树摇曳，细腻的沙滩，4K高清`
   - 负面提示词：`低质量，模糊，变形`
   - 生成步数：`50`
   - 图像尺寸：`1024x1024`
   - 图像风格：`摄影`
4. **填写基础信息**：
   - 场景名称：`海边度假`
   - 场景分类：`风景`
   - 是否公共：`开启`
5. **提交保存**

### 测试2：编辑场景

1. 在场景列表点击「编辑」
2. 检查是否正确回填所有数据
3. 修改参数值
4. 保存

### 测试3：生成封面图（待API实现后）

1. 编辑已保存的场景
2. 点击「一键生成封面图」按钮
3. 等待生成完成
4. 检查封面图是否更新

---

## 五、常见问题

### Q1：模型列表为空

**原因**：数据库中没有模型实例记录

**解决**：
```sql
-- 检查模型表
SELECT * FROM ddwx_ai_model_instance;

-- 如果为空，重新执行初始化数据SQL
mysql -u root -p ddwx < database/migrations/ai_model_config_init_data.sql
```

### Q2：API配置列表为空

**原因**：模型未关联API配置

**解决**：
```sql
-- 检查API配置的model_id是否正确
SELECT id, api_name, model_id FROM ddwx_api_config;

-- 如果model_id为0或错误，更新为正确的模型ID
UPDATE ddwx_api_config 
SET model_id = (SELECT id FROM ddwx_ai_model_instance WHERE model_code='qwen-turbo-image' LIMIT 1)
WHERE api_code = 'aliyun_dashscope_default';
```

### Q3：参数表单不显示

**原因**：该模型没有参数定义

**解决**：
```sql
-- 检查参数定义
SELECT * FROM ddwx_ai_model_parameter WHERE model_id = 1;

-- 如果为空，需要添加参数定义（参考ai_model_config_init_data.sql）
```

### Q4：生成封面图报错

**可能原因**：
1. API密钥未配置或错误
2. API配置未启用（is_active=0）
3. 必填参数未填写
4. API接口尚未实现

**排查步骤**：
```sql
-- 1. 检查API配置
SELECT api_key, is_active FROM ddwx_api_config WHERE id = ?;

-- 2. 检查场景参数
SELECT model_params FROM ddwx_ai_travel_photo_scene WHERE id = ?;
```

### Q5：字段 api_config_id 不存在

**原因**：SQL脚本未执行

**解决**：
```bash
# 执行表结构调整SQL
mysql -u root -p ddwx < database/migrations/scene_management_redesign_alter.sql
```

---

## 六、下一步开发

### 1. 实现API调用（必需）

编辑文件：`/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`

找到以下方法并实现：
- `callAliyunApi()` - 阿里云API调用
- `callBaiduApi()` - 百度API调用
- `callOpenAiApi()` - OpenAI API调用

参考代码见：`SCENE_MANAGEMENT_REDESIGN_REPORT.md` 第九章

### 2. 添加更多模型（可选）

```sql
-- 添加新的模型实例
INSERT INTO ddwx_ai_model_instance (...) VALUES (...);

-- 为新模型添加参数定义
INSERT INTO ddwx_ai_model_parameter (...) VALUES (...);

-- 添加对应的API配置
INSERT INTO ddwx_api_config (...) VALUES (...);
```

### 3. 批量生成功能（可选）

在场景列表页面添加批量生成封面图功能

### 4. 参数模板功能（可选）

为常用参数组合创建快速模板

---

## 七、技术支持

### 相关文档

- 📘 [设计方案](./SCENE_MANAGEMENT_REDESIGN_PLAN.md)
- 📙 [实施报告](./SCENE_MANAGEMENT_REDESIGN_REPORT.md)
- 📗 [使用指南](./SCENE_MANAGEMENT_USAGE_GUIDE.md)

### 数据库脚本

- `scene_management_complete_setup.sql` - 完整安装脚本（推荐）
- `scene_management_redesign_alter.sql` - 表结构调整
- `ai_model_config_tables.sql` - 模型配置表创建
- `ai_model_config_init_data.sql` - 示例数据初始化
- `api_config_tables.sql` - API配置表创建

### 代码文件

- `app/controller/AiTravelPhoto.php` - 后端控制器（+225行）
- `app/view/ai_travel_photo/scene_edit.html` - 前端页面（+190行）

---

## 八、检查清单

安装完成后，请逐项检查：

- [ ] 数据库SQL执行成功
- [ ] api_config_id 字段已添加
- [ ] 模型实例表有数据
- [ ] 参数定义表有数据
- [ ] API配置表有数据
- [ ] API密钥已更新为真实值
- [ ] API配置已启用（is_active=1）
- [ ] 场景管理页面可以访问
- [ ] 模型下拉列表有选项
- [ ] API配置下拉列表有选项
- [ ] 参数表单动态显示正常
- [ ] 场景保存功能正常
- [ ] model_params字段正确存储JSON

---

**安装时间**：预计 5-10 分钟  
**难度等级**：⭐⭐ (中等)  
**最后更新**：2026-02-04

🎉 祝您使用愉快！如有问题请查看详细文档或联系技术支持。
