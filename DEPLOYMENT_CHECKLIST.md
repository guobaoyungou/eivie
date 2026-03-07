# 🚀 场景管理重构部署检查清单

**项目**: AI旅拍场景管理功能重构  
**版本**: v2.0  
**日期**: 2026-02-04

---

## 📋 部署前准备

### 1. 环境检查

- [ ] PHP版本 ≥ 7.2
- [ ] MySQL版本 ≥ 5.7
- [ ] ThinkPHP版本 = 6.0.7
- [ ] 确认有数据库操作权限
- [ ] 确认有文件写入权限

### 2. 备份数据

```bash
# 备份数据库
mysqldump -u root -p ddwx > backup_ddwx_$(date +%Y%m%d_%H%M%S).sql

# 备份相关文件
cp app/controller/AiTravelPhoto.php app/controller/AiTravelPhoto.php.bak
cp app/view/ai_travel_photo/scene_edit.html app/view/ai_travel_photo/scene_edit.html.bak
```

---

## 🔧 安装步骤

### 第一步：执行数据库脚本

- [ ] **方式1（推荐）**: 执行完整安装脚本
  ```bash
  cd /www/wwwroot/eivie
  mysql -u root -p ddwx < database/migrations/scene_management_complete_setup.sql
  ```

- [ ] **方式2**: 分步执行
  ```bash
  mysql -u root -p ddwx < database/migrations/scene_management_redesign_alter.sql
  mysql -u root -p ddwx < database/migrations/ai_model_config_tables.sql
  mysql -u root -p ddwx < database/migrations/ai_model_config_init_data.sql
  mysql -u root -p ddwx < database/migrations/api_config_tables.sql
  ```

- [ ] 验证表结构
  ```sql
  DESC ddwx_ai_travel_photo_scene;
  -- 检查是否有 api_config_id 字段
  ```

### 第二步：验证数据完整性

- [ ] 检查模型实例
  ```sql
  SELECT COUNT(*) FROM ddwx_ai_model_instance;
  -- 应该 ≥ 1
  ```

- [ ] 检查参数定义
  ```sql
  SELECT COUNT(*) FROM ddwx_ai_model_parameter;
  -- 应该 ≥ 5
  ```

- [ ] 检查API配置
  ```sql
  SELECT COUNT(*) FROM ddwx_api_config;
  -- 应该 ≥ 1
  ```

### 第三步：配置API密钥

- [ ] 查询需要配置的API
  ```sql
  SELECT id, api_name, api_code, api_key 
  FROM ddwx_api_config 
  WHERE api_key LIKE '%YOUR_API_KEY_HERE%';
  ```

- [ ] 更新真实API密钥
  ```sql
  UPDATE ddwx_api_config 
  SET api_key = 'sk-xxxxxxxxxxxxxxxxxxxxx',
      is_active = 1
  WHERE api_code = 'aliyun_dashscope_default';
  ```

- [ ] 验证API配置已启用
  ```sql
  SELECT api_name, is_active FROM ddwx_api_config WHERE is_active = 1;
  ```

### 第四步：验证代码文件

- [ ] 检查控制器文件
  ```bash
  ls -lh app/controller/AiTravelPhoto.php
  # 检查文件大小和修改时间
  ```

- [ ] 检查视图文件
  ```bash
  ls -lh app/view/ai_travel_photo/scene_edit.html
  # 检查文件大小和修改时间
  ```

- [ ] 检查语法错误
  ```bash
  php -l app/controller/AiTravelPhoto.php
  # 应该显示：No syntax errors detected
  ```

---

## ✅ 功能测试

### 测试1：访问场景列表

- [ ] 登录商家后台
- [ ] 进入「AI旅拍」→「场景管理」
- [ ] 列表页正常显示
- [ ] 没有500错误

### 测试2：新增场景

- [ ] 点击「添加场景」按钮
- [ ] 页面正常打开，没有500错误
- [ ] 检查模型下拉列表
  - [ ] 有选项显示
  - [ ] 显示格式：模型名称 (提供商)

### 测试3：选择模型联动

- [ ] 选择一个模型
- [ ] API配置区域自动显示
- [ ] API配置下拉列表有选项
- [ ] 参数表单区域自动显示
- [ ] 动态参数字段正确渲染

### 测试4：填写并保存场景

- [ ] 选择模型：通义千问图像生成
- [ ] 选择API配置：阿里云通义千问默认配置
- [ ] 填写正向提示词
- [ ] 填写场景名称和分类
- [ ] 点击提交
- [ ] 保存成功，弹出提示
- [ ] 返回列表，新场景已显示

### 测试5：编辑场景

- [ ] 点击某个场景的「编辑」
- [ ] 模型自动选中
- [ ] API配置自动选中
- [ ] 参数值正确回填
- [ ] 基础信息正确显示
- [ ] 修改参数后保存
- [ ] 保存成功

### 测试6：数据完整性

- [ ] 查询保存的场景数据
  ```sql
  SELECT id, name, model_id, api_config_id, model_params 
  FROM ddwx_ai_travel_photo_scene 
  ORDER BY id DESC LIMIT 1;
  ```

- [ ] 验证字段值
  - [ ] model_id > 0
  - [ ] api_config_id > 0
  - [ ] model_params 是有效的JSON

- [ ] 解析JSON参数
  ```sql
  SELECT JSON_EXTRACT(model_params, '$.prompt') AS prompt
  FROM ddwx_ai_travel_photo_scene 
  WHERE id = ?;
  ```

### 测试7：一键生成封面图（待API实现后）

- [ ] 编辑已保存的场景
- [ ] 点击「一键生成封面图」
- [ ] 显示确认对话框
- [ ] 点击确认
- [ ] 显示加载动画
- [ ] 生成成功或显示错误提示

---

## 🐛 常见问题排查

### 问题1：模型列表为空

**检查步骤**:
```sql
-- 1. 检查模型表
SELECT * FROM ddwx_ai_model_instance WHERE is_active = 1;

-- 2. 如果为空，重新导入数据
-- mysql -u root -p ddwx < database/migrations/ai_model_config_init_data.sql
```

**预期结果**: 至少有1条记录

---

### 问题2：API配置列表为空

**检查步骤**:
```sql
-- 1. 检查API配置
SELECT id, api_name, model_id, is_active FROM ddwx_api_config;

-- 2. 检查model_id是否正确关联
SELECT 
  a.id AS api_id,
  a.api_name,
  a.model_id,
  m.model_name
FROM ddwx_api_config a
LEFT JOIN ddwx_ai_model_instance m ON a.model_id = m.id;

-- 3. 如果model_id为0或NULL，更新关联
UPDATE ddwx_api_config 
SET model_id = (SELECT id FROM ddwx_ai_model_instance LIMIT 1)
WHERE model_id = 0;
```

**预期结果**: api_config.model_id 关联到有效的模型实例

---

### 问题3：参数表单不显示

**检查步骤**:
```sql
-- 1. 检查参数定义
SELECT * FROM ddwx_ai_model_parameter WHERE model_id = ? AND is_active = 1;

-- 2. 检查浏览器控制台
-- 打开F12开发者工具，查看Console是否有JavaScript错误

-- 3. 检查AJAX请求
-- Network标签中查看 get_model_params 请求是否成功
```

**预期结果**: 
- 数据库有参数记录
- AJAX请求返回code=0
- 前端成功渲染表单

---

### 问题4：保存后model_params为空

**检查步骤**:
```javascript
// 1. 在浏览器控制台执行
console.log($('form').serialize());
// 检查是否有 param_ 开头的字段

// 2. 查看提交的数据
// Network标签中查看 scene_edit POST请求的Form Data
```

**预期结果**: 提交数据中包含 param_prompt、param_negative_prompt 等字段

---

### 问题5：500错误

**排查步骤**:
```bash
# 1. 查看PHP错误日志
tail -f /www/server/php/7.4/var/log/php-fpm.log

# 2. 查看ThinkPHP日志
tail -f runtime/log/202402/04.log

# 3. 开启调试模式（如果未开启）
# 编辑 .env 文件
# APP_DEBUG = true
```

**常见原因**:
- 语法错误
- 类或方法不存在
- 数据库连接失败
- 权限问题

---

## 📊 性能检查

### 数据库性能

- [ ] 检查索引
  ```sql
  SHOW INDEX FROM ddwx_ai_travel_photo_scene;
  -- 应该包含 idx_api_config_id
  ```

- [ ] 检查慢查询
  ```sql
  SHOW VARIABLES LIKE 'slow_query_log';
  -- 确认慢查询日志已开启
  ```

### 前端性能

- [ ] 检查页面加载时间（< 2秒）
- [ ] 检查AJAX请求响应时间（< 500ms）
- [ ] 检查是否有重复请求

---

## 🔒 安全检查

### API密钥安全

- [ ] API密钥未硬编码在代码中
- [ ] API密钥存储在数据库中
- [ ] 生产环境使用独立的API密钥
- [ ] 定期轮换API密钥

### 数据验证

- [ ] 前端有必填验证
- [ ] 后端有参数验证
- [ ] SQL查询使用参数绑定
- [ ] 没有SQL注入风险

### 权限控制

- [ ] 商家只能查看自己的场景
- [ ] 超级管理员可以查看所有场景
- [ ] API配置有权限筛选

---

## 📝 部署记录

### 部署信息

- **部署人员**: _____________
- **部署时间**: _____________
- **服务器环境**: _____________
- **PHP版本**: _____________
- **MySQL版本**: _____________

### 部署结果

- [ ] 数据库脚本执行成功
- [ ] 所有表结构正确
- [ ] 示例数据导入成功
- [ ] API密钥配置完成
- [ ] 功能测试通过
- [ ] 性能测试通过
- [ ] 安全检查通过

### 遗留问题

1. _____________
2. _____________
3. _____________

### 后续计划

1. [ ] 实现真实的API调用（参考 AiApiImplementationExample.php）
2. [ ] 添加更多模型支持
3. [ ] 优化生成速度
4. [ ] 添加批量生成功能
5. [ ] 完善错误处理
6. [ ] 添加日志记录

---

## 📞 技术支持

### 文档资源

- 📘 设计方案: SCENE_MANAGEMENT_REDESIGN_PLAN.md
- 📙 实施报告: SCENE_MANAGEMENT_REDESIGN_REPORT.md
- 📗 使用指南: SCENE_MANAGEMENT_USAGE_GUIDE.md
- 📕 快速启动: QUICK_START.md
- 📓 API示例: app/controller/AiApiImplementationExample.php

### 数据库脚本

- scene_management_complete_setup.sql - 完整安装（推荐）
- scene_management_redesign_alter.sql - 表结构调整
- ai_model_config_tables.sql - 模型配置表
- ai_model_config_init_data.sql - 示例数据
- api_config_tables.sql - API配置表

---

## ✅ 最终确认

部署完成后，请确认以下所有项目：

- [ ] 数据库表结构正确
- [ ] 示例数据完整
- [ ] API密钥已配置
- [ ] 场景列表正常访问
- [ ] 新增场景功能正常
- [ ] 编辑场景功能正常
- [ ] 参数动态加载正常
- [ ] 数据保存正确
- [ ] 没有明显错误
- [ ] 性能表现良好

**签字确认**: _____________ 日期: _____________

---

**部署状态**: ⬜ 未开始 | ⬜ 进行中 | ⬜ 已完成 | ⬜ 有问题

**最后更新**: 2026-02-04
