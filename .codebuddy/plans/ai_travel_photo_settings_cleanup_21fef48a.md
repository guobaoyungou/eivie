---
name: ai_travel_photo_settings_cleanup
overview: 删除AI旅拍系统设置中的OSS配置、API密钥管理、队列配置、监控告警功能
todos:
  - id: modify-settings-html
    content: 修改settings.html前端页面，删除Tab2-5的HTML内容和JS代码
    status: completed
  - id: modify-aitravelphoto-controller
    content: 修改AiTravelPhoto.php控制器，删除api_key相关方法和saveOssSettings/saveQueueSettings/saveMonitorSettings方法
    status: completed
  - id: modify-backup-controller
    content: 清理AiTravelPhoto.php.backup备份文件中相同的方法
    status: completed
  - id: delete-apikeyservice
    content: 删除AiTravelPhotoApiKeyService.php服务类文件
    status: completed
  - id: create-sql-migration
    content: 创建数据库迁移SQL，删除business表的ai_oss_/ai_queue_/ai_monitor_字段
    status: completed
---

## 用户需求

删除旅拍-AI旅拍-系统设置tab页的以下功能：

1. OSS配置（Tab2）
2. API密钥管理（Tab3）
3. 队列配置（Tab4）
4. 监控告警（Tab5）

## 保留内容（通用模块）

- SystemApiKey、MerchantApiKey 控制器及相关服务
- system_api_key、merchant_api_key 数据表

## 涉及范围

- 前端页面：删除Tab页及对应JS代码
- 后端控制器：删除API密钥相关方法
- 服务类：删除AiTravelPhotoApiKeyService
- 数据库：删除business表的冗余字段

## 技术方案

采用代码删除方式，移除以下内容：

### 1. 前端页面修改

- 文件：`app/view/ai_travel_photo/settings.html`
- 删除Tab2-5的HTML内容（OSS配置、API密钥管理、队列配置、监控告警）
- 删除对应的JavaScript代码（api_key_list、api_key_save、api_key_delete、api_key_test相关逻辑）

### 2. 后端控制器修改

- 文件：`app/controller/AiTravelPhoto.php`
- 删除 api_key_list() 方法
- 删除 api_key_save() 方法  
- 删除 api_key_delete() 方法
- 删除 api_key_test() 方法
- 删除 saveOssSettings() 私有方法
- 删除 saveQueueSettings() 私有方法
- 删除 saveMonitorSettings() 私有方法

### 3. 备份文件清理

- 文件：`app/controller/AiTravelPhoto.php.backup`
- 删除备份中相同的API密钥相关方法

### 4. 服务类删除

- 文件：`app/service/AiTravelPhotoApiKeyService.php`
- 整个文件删除

### 5. 数据库清理

- 新建SQL迁移文件删除business表字段
- 删除字段：ai_oss_access_key_id, ai_oss_access_key_secret, ai_oss_bucket, ai_oss_endpoint, ai_oss_domain, ai_queue_cutout_concurrent, ai_queue_image_concurrent, ai_queue_video_concurrent, ai_queue_cutout_timeout, ai_queue_image_timeout, ai_queue_video_timeout, ai_monitor_queue_threshold, ai_monitor_fail_rate, ai_monitor_response_time, ai_monitor_alert_emails