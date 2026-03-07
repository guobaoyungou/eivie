# 系统API Key配置功能实现总结

## 功能概述
在后台控制台的"系统设置"菜单之后新增"API Key配置"功能模块，管理员可从模型广场的供应商列表中选择供应商，填写对应的API Key，启用该供应商下所有大模型的调用能力。

## 实现内容

### 1. 数据库表
- **表名**: `ddwx_system_api_key`
- **位置**: `/database/migrations/system_api_key_tables.sql`
- **核心字段**:
  - `provider_id`: 关联供应商ID（唯一约束，一个供应商只能配置一次）
  - `api_key`: AES-256-CBC加密存储的API密钥
  - `api_secret`: 加密存储的API Secret（部分供应商需要）
  - `extra_config`: JSON格式的扩展配置
  - `is_active`: 启用状态

### 2. 服务层
- **文件**: `/app/service/SystemApiKeyService.php`
- **核心功能**:
  - `encryptApiKey()` / `decryptApiKey()`: API Key加密/解密
  - `maskApiKey()`: API Key脱敏展示（显示前4位+****+后4位）
  - `getList()`: 获取配置列表
  - `save()`: 保存配置（含验证和加密）
  - `testConnection()`: 测试API连接（本地格式验证）
  - `getActiveConfigByProvider()`: 获取供应商的有效配置

### 3. 控制器
- **文件**: `/app/controller/SystemApiKey.php`
- **API接口**:
  - `index`: 配置列表页面与数据
  - `edit`: 新增/编辑页面
  - `save`: 保存配置
  - `delete`: 删除配置
  - `setst`: 切换启用状态
  - `test`: 测试API连接
  - `get_provider_fields`: 获取供应商认证字段
  - `get_providers`: 获取可选供应商列表

### 4. 视图文件
- **目录**: `/app/view/system_api_key/`
- **文件**:
  - `index.html`: 配置列表页（支持搜索、筛选、状态切换）
  - `edit.html`: 新增/编辑表单页（动态加载供应商认证字段）

### 5. 菜单配置

#### 商家后台菜单（主要入口）
- **位置**: `/app/common/Menu.php` 的 `getdata()` 方法中
- **菜单顺序**: 系统设置 → **API Key配置** → 门店管理
- **权限控制**: 仅平台管理员（`$isadmin`）可见

#### 控制台菜单
- **位置**: `/app/controller/WebSystem.php` 的 `index()` 方法中
- **菜单顺序**: 系统设置 → **API Key配置** → 模型广场

### 6. 路由配置
- **文件**: `/route/app.php`
- **路由**: `Route::any('SystemApiKey/:function', 'SystemApiKey/:function')`

## 功能特点

### 安全设计
- API Key使用AES-256-CBC加密存储
- 列表页展示脱敏后的API Key
- 编辑页不回显原始API Key

### 用户体验
- 供应商选择器直观展示Logo和名称
- 动态加载供应商的认证字段模板
- 一键测试API连接
- 状态开关即时切换

### 数据约束
- 一个供应商只能配置一次（唯一约束）
- API Key长度验证（≥20字符）
- 已配置的供应商不会出现在可选列表中

## 访问路径
登录控制台后，在左侧菜单找到：**系统设置** → **API Key配置**

## 依赖关系
- 依赖模型广场的 `model_provider` 表获取供应商信息
- 使用系统配置文件 `config.php` 中的 `authkey` 作为加密密钥
