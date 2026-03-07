# AI模型管理功能实现总结

## 实施概述

本次实现基于设计文档完成了完整的AI模型管理系统，为平台、商家、门店管理员提供了统一的第三方AI大模型API配置管理能力。

## 已完成的任务

### 1. 数据库迁移 ✅

**创建的表结构：**

- **ddwx_ai_model_category** - 模型分类表
  - 支持系统预置分类（千问、豆包、可灵、即梦、OpenAI、Ollama、通义万相、其他）
  - 支持自定义分类扩展
  - 包含分类图标、排序、状态等字段

- **ddwx_ai_travel_photo_model** - 模型配置表（扩展）
  - 新增字段：mdid（门店ID）、category_code（分类代码）、provider（服务提供商）
  - 新增字段：max_concurrent（最大并发数）、current_concurrent（当前并发数）、priority（优先级）
  - 新增字段：is_active（是否激活）、test_passed（测试状态）、last_test_time（最后测试时间）
  - 新增字段：image_price、video_price、token_price（成本配置）
  - 新增字段：timeout、max_retry（请求配置）

- **ddwx_ai_model_usage_log** - 使用记录表
  - 记录每次API调用的详细信息
  - 包含请求参数、响应数据、状态、耗时、成本等字段
  - 支持按时间、模型、业务类型等维度查询统计

**迁移文件位置：**
`/www/wwwroot/eivie/database/migrations/ai_model_management_tables.sql`

### 2. 核心服务层 ✅

**AiModelService.php** - API调度服务

**核心功能：**

1. **负载均衡策略**
   - 按优先级降序排序
   - 按当前并发数升序排序
   - 按成功率降序排序
   - 自动选择最优API配置

2. **并发控制**
   - 使用Redis缓存实时并发计数
   - 原子性增减操作
   - 自动过滤已满载的配置
   - 支持最大并发数限制

3. **失败重试机制**
   - 网络超时自动重试（最多3次）
   - API限流自动切换配置
   - 参数错误不重试
   - 重试间隔递增（0s、2s、5s）

4. **统计监控**
   - 自动记录每次调用日志
   - 更新模型统计数据
   - 计算调用成本
   - 记录响应时间

**服务文件位置：**
`/www/wwwroot/eivie/app/service/AiModelService.php`

### 3. 控制器层 ✅

**AiTravelPhoto.php** - 模型管理控制器

**新增方法：**

**模型分类管理：**
- `model_category_list()` - 分类列表（支持搜索、筛选、分页）
- `model_category_edit()` - 新增/编辑分类
- `model_category_delete()` - 删除分类（检查引用）

**API配置管理：**
- `model_config_list()` - 配置列表（支持多维度筛选）
- `model_config_edit()` - 新增/编辑配置（Tab页表单）
- `model_config_delete()` - 删除配置
- `model_config_test()` - 测试API连通性

**调用统计：**
- `model_usage_stats()` - 统计概览和调用明细
  - 支持overview（概览数据）
  - 支持trend（趋势图数据）
  - 支持category（分类统计）
  - 支持list（调用明细列表）

### 4. 视图层 ✅

**创建的视图文件：**

1. **model_category_list.html** - 模型分类列表页
   - 搜索栏（关键词、类型筛选）
   - 数据表格（Layui Table）
   - 操作按钮（新增、编辑、删除）
   - 系统分类只读保护

2. **model_category_edit.html** - 分类编辑页
   - 表单验证（必填、格式校验）
   - 系统分类代码不可修改
   - Emoji图标选择提示

3. **model_config_list.html** - API配置列表页
   - 多条件筛选（模型分类、门店、状态）
   - 实时显示并发数、成功率
   - API密钥脱敏显示
   - 操作按钮（编辑、测试、删除）

4. **model_config_edit.html** - API配置编辑页
   - Tab页布局（基础信息、API配置、并发控制、成本配置）
   - 表单验证（URL格式、必填项）
   - 默认配置互斥处理

5. **model_usage_stats.html** - 调用统计页
   - 统计概览卡片（总调用、成功、失败、成本）
   - 调用明细表格
   - 多维度筛选（模型、业务、状态）

**视图文件位置：**
`/www/wwwroot/eivie/app/view/ai_travel_photo/`

### 5. 菜单与权限 ✅

**菜单结构调整：**

在AI旅拍模块中，按设计文档要求，在"数据统计"与"系统设置"之间新增"模型设置"子菜单：

```
AI旅拍
├── 场景管理
├── 套餐管理
├── 人像管理
├── 任务列表
├── 订单管理
├── 设备管理
├── 选片列表
├── 成品列表
├── 数据统计
├── 模型设置（新增）
│   ├── 模型分类
│   ├── API配置
│   └── 调用统计
└── 系统设置
```

**权限节点：**

- `AiTravelPhoto/model_category_list` - 模型分类列表
- `AiTravelPhoto/model_category_edit` - 编辑模型分类
- `AiTravelPhoto/model_category_delete` - 删除模型分类
- `AiTravelPhoto/model_config_list` - API配置列表
- `AiTravelPhoto/model_config_edit` - 编辑API配置
- `AiTravelPhoto/model_config_test` - 测试API
- `AiTravelPhoto/model_config_delete` - 删除API配置
- `AiTravelPhoto/model_usage_stats` - 调用统计

**菜单文件修改：**
`/www/wwwroot/eivie/app/common/Menu.php`

## 核心特性

### 1. 多模型支持
- 预置8种主流AI模型分类
- 支持自定义模型分类扩展
- 每个分类可配置多个API

### 2. 并发优化
- Redis实时并发计数
- 自动负载均衡
- 智能分配API资源
- 防止单API过载

### 3. 灵活管理
- 平台级、商家级、门店级三层配置
- 配置优先级：门店专属 > 商家通用 > 平台默认
- 支持启用/禁用控制
- 支持优先级排序

### 4. 成本可控
- 记录每次调用成本
- 支持图片、视频、Token计费
- 统计总消耗和今日消耗
- 可按时间、模型、业务类型分析

### 5. 可靠性保障
- 自动失败重试
- 网络超时处理
- 限流自动切换
- 连通性测试

## 使用示例

### 业务代码调用示例

```php
use app\service\AiModelService;

// 调用抠图服务
$result = AiModelService::call(
    'tongyi_wanxiang',  // 模型分类代码
    'cutout',           // 业务类型
    [                   // 请求参数
        'image_url' => 'https://example.com/image.jpg',
        'mode' => 'person'
    ],
    5,                  // 门店ID（可选）
    10,                 // 商家ID（可选）
    1                   // 平台ID（可选）
);

if ($result['success']) {
    // 处理成功结果
    $data = $result['data'];
} else {
    // 处理错误
    $error = $result['error'];
}
```

### 调度流程

1. 根据模型分类代码查询可用API配置
2. 按门店ID、商家ID、平台ID筛选
3. 过滤已禁用和并发已满的配置
4. 按优先级、并发数、成功率排序
5. 选择最优配置
6. 增加并发计数
7. 调用第三方API
8. 减少并发计数
9. 记录使用日志
10. 更新统计数据
11. 返回结果

## 数据安全

1. **API密钥加密存储**
   - 数据库中加密保存
   - 前端仅显示前4位和后4位

2. **数据权限隔离**
   - 平台管理员可查看所有配置
   - 商家管理员仅可查看自己的配置
   - 门店管理员仅可查看自己门店的配置

3. **日志脱敏**
   - 敏感参数脱敏处理
   - 定期清理过期日志
   - 成功日志保留30天
   - 失败日志保留90天

## 性能优化

1. **缓存策略**
   - API配置列表缓存5分钟
   - 并发计数使用Redis实时缓存
   - 统计数据缓存10分钟

2. **数据库优化**
   - 为常用查询字段添加索引
   - 使用日志表分区（建议按月）
   - 定期清理过期日志

3. **并发控制**
   - Redis原子操作
   - 避免数据库频繁写入
   - 每5分钟同步一次统计数据

## 后续集成建议

### 将AI旅拍模块接入新服务

**抠图场景集成：**

```php
// 原代码（直接调用通义万相）
$result = $this->callTongyiWanxiangApi($imageUrl);

// 新代码（使用调度服务）
$result = AiModelService::call(
    'tongyi_wanxiang',
    'cutout',
    ['image_url' => $imageUrl],
    $this->mdid,
    $this->bid,
    $this->aid
);
```

**生图场景集成：**

```php
// 使用千问生图
$result = AiModelService::call(
    'qianwen',
    'image_gen',
    [
        'prompt' => $prompt,
        'size' => '1024x1024'
    ],
    $this->mdid,
    $this->bid,
    $this->aid
);
```

**生视频场景集成：**

```php
// 使用可灵生视频
$result = AiModelService::call(
    'kling',
    'video_gen',
    [
        'image_url' => $imageUrl,
        'duration' => 5
    ],
    $this->mdid,
    $this->bid,
    $this->aid
);
```

## 测试建议

1. **功能测试**
   - 测试模型分类的增删改查
   - 测试API配置的增删改查
   - 测试API连通性测试功能
   - 测试调用统计数据展示

2. **并发测试**
   - 模拟高并发调用
   - 验证负载均衡效果
   - 验证并发限制功能
   - 验证失败重试机制

3. **权限测试**
   - 测试不同角色的数据权限
   - 测试菜单显示权限
   - 测试操作权限控制

4. **性能测试**
   - 测试大量日志下的查询性能
   - 测试缓存命中率
   - 测试并发压力

## 注意事项

1. **数据迁移**
   - 执行SQL迁移脚本前请备份数据库
   - 检查现有`ddwx_ai_travel_photo_model`表结构
   - 验证系统预置分类数据是否正确插入

2. **Redis依赖**
   - 确保Redis服务正常运行
   - 配置正确的Redis连接信息
   - 监控Redis内存使用

3. **第三方API**
   - 需要根据实际API文档调整`callThirdPartyApi()`方法
   - 不同模型的请求格式可能不同
   - 需要实现具体的请求参数构建逻辑

4. **成本统计**
   - 成本计算逻辑需要根据实际计费规则调整
   - 建议定期核对成本数据
   - 可以增加成本预警功能

## 文件清单

### 数据库迁移
- `/www/wwwroot/eivie/database/migrations/ai_model_management_tables.sql`

### 服务层
- `/www/wwwroot/eivie/app/service/AiModelService.php`

### 控制器
- `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php` (扩展)

### 视图
- `/www/wwwroot/eivie/app/view/ai_travel_photo/model_category_list.html`
- `/www/wwwroot/eivie/app/view/ai_travel_photo/model_category_edit.html`
- `/www/wwwroot/eivie/app/view/ai_travel_photo/model_config_list.html`
- `/www/wwwroot/eivie/app/view/ai_travel_photo/model_config_edit.html`
- `/www/wwwroot/eivie/app/view/ai_travel_photo/model_usage_stats.html`

### 菜单配置
- `/www/wwwroot/eivie/app/common/Menu.php` (修改)

## 总结

本次实现完成了AI模型管理功能的所有核心模块，包括：

✅ 数据库表结构设计与迁移  
✅ API调度服务（负载均衡、并发控制、失败重试）  
✅ 模型分类管理（列表、新增、编辑、删除）  
✅ API配置管理（列表、新增、编辑、删除、测试）  
✅ 调用统计（概览、趋势、明细）  
✅ 菜单与权限配置  
✅ 视图页面实现  

系统已具备投入使用的基础条件，后续需要：

1. 执行数据库迁移脚本
2. 根据实际使用的AI模型API调整接口调用代码
3. 进行功能测试和性能测试
4. 将现有AI旅拍业务逐步接入新的调度服务
5. 监控运行状态并优化

建议采用渐进式迁移策略，先在测试环境验证功能，再逐步切换生产环境的流量。
