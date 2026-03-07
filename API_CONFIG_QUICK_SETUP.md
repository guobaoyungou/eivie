# API配置快速上手指南

## 📋 目录
1. [前置准备](#前置准备)
2. [配置阿里云通义万相](#配置阿里云通义万相)
3. [配置可灵AI](#配置可灵ai)
4. [创建测试场景](#创建测试场景)
5. [测试完整流程](#测试完整流程)

---

## 一、前置准备

### 1.1 数据库迁移

```bash
# 执行场景管理数据库迁移脚本
mysql -u用户名 -p密码 数据库名 < database/migrations/scene_type_enhancement.sql
```

### 1.2 确认队列服务运行

```bash
# 启动图生图队列
nohup php think queue:work --queue=ai_image_generation > logs/image_queue.log 2>&1 &

# 启动视频生成队列
nohup php think queue:work --queue=ai_video_generation > logs/video_queue.log 2>&1 &

# 检查队列状态
php think queue:status
```

---

## 二、配置阿里云通义万相

### 2.1 获取API密钥

1. 访问 https://bailian.console.aliyun.com/
2. 点击「API-KEY管理」
3. 创建新的API-KEY
4. 复制API-KEY（格式：sk-xxxxxxxxxxxx）

### 2.2 后台添加API配置

**方式1: 通过Web后台**

```
访问路径: 系统设置 → API配置管理 → 新增配置

基础信息:
- API代码: aliyun_wanx_v1
- API名称: 阿里云通义万相
- API类型: image_generation
- 服务提供商: aliyun

认证信息:
- API密钥: sk-xxxxxxxxxxxx（从步骤2.1获取）
- API Secret: （留空）

接口配置:
- 端点URL: https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis
- 配置JSON:
  {
    "model": "wanx-v1",
    "timeout": 180
  }

权限设置:
- 作用域类型: 全局公开
- 状态: 启用
```

**方式2: SQL直接插入**

```sql
INSERT INTO ddwx_api_config (
    aid, bid, mdid,
    api_code, api_name, api_type, provider,
    api_key, endpoint_url, config_json,
    is_active, is_system, scope_type,
    create_time, update_time
) VALUES (
    0, 0, 0,
    'aliyun_wanx_v1',
    '阿里云通义万相',
    'image_generation',
    'aliyun',
    'sk-xxxxxxxxxxxx',  -- 替换为真实的API密钥
    'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis',
    '{"model": "wanx-v1", "timeout": 180}',
    1,
    1,
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

-- 记录插入后的ID（用于场景配置）
SELECT LAST_INSERT_ID() AS api_config_id;
```

### 2.3 验证配置

```bash
# 查询刚创建的配置
SELECT id, api_code, api_name, provider, is_active, endpoint_url
FROM ddwx_api_config
WHERE api_code = 'aliyun_wanx_v1';

# 预期输出
+----+------------------+-----------------------+----------+-----------+-------------------------------------------------------+
| id | api_code         | api_name              | provider | is_active | endpoint_url                                          |
+----+------------------+-----------------------+----------+-----------+-------------------------------------------------------+
|  1 | aliyun_wanx_v1   | 阿里云通义万相         | aliyun   |         1 | https://dashscope.aliyuncs.com/api/v1/services/...    |
+----+------------------+-----------------------+----------+-----------+-------------------------------------------------------+
```

---

## 三、配置可灵AI

### 3.1 获取API密钥

1. 访问 https://klingai.kuaishou.com/
2. 注册并登录账号
3. 进入「API管理」
4. 创建API密钥
5. 记录 AccessKey 和 SecretKey

### 3.2 后台添加API配置

**方式1: 通过Web后台**

```
访问路径: 系统设置 → API配置管理 → 新增配置

基础信息:
- API代码: kling_v1_5
- API名称: 可灵AI视频生成
- API类型: video_generation
- 服务提供商: kling

认证信息:
- API密钥(AccessKey): access_key_xxxxxxxxxxxx
- API Secret(SecretKey): secret_key_xxxxxxxxxxxx

接口配置:
- 端点URL: https://api.klingai.com
- 配置JSON:
  {
    "model_name": "kling-v1-5",
    "default_duration": 5,
    "default_mode": "std"
  }

权限设置:
- 作用域类型: 全局公开
- 状态: 启用
```

**方式2: SQL直接插入**

```sql
INSERT INTO ddwx_api_config (
    aid, bid, mdid,
    api_code, api_name, api_type, provider,
    api_key, api_secret, endpoint_url, config_json,
    is_active, is_system, scope_type,
    create_time, update_time
) VALUES (
    0, 0, 0,
    'kling_v1_5',
    '可灵AI视频生成',
    'video_generation',
    'kling',
    'access_key_xxxxxxxxxxxx',  -- 替换为真实的AccessKey
    'secret_key_xxxxxxxxxxxx',  -- 替换为真实的SecretKey
    'https://api.klingai.com',
    '{"model_name": "kling-v1-5", "default_duration": 5, "default_mode": "std"}',
    1,
    1,
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

-- 记录插入后的ID
SELECT LAST_INSERT_ID() AS api_config_id;
```

### 3.3 验证配置

```bash
# 查询刚创建的配置
SELECT id, api_code, api_name, provider, is_active
FROM ddwx_api_config
WHERE api_code = 'kling_v1_5';

# 预期输出
+----+-------------+--------------------+----------+-----------+
| id | api_code    | api_name           | provider | is_active |
+----+-------------+--------------------+----------+-----------+
|  2 | kling_v1_5  | 可灵AI视频生成      | kling    |         1 |
+----+-------------+--------------------+----------+-----------+
```

---

## 四、创建测试场景

### 4.1 创建图生图场景

```sql
-- 假设上面阿里云配置的ID为1
INSERT INTO ddwx_ai_travel_photo_scene (
    aid, bid, mdid,
    scene_name, scene_type, category,
    api_config_id,
    model_params,
    reference_image,
    is_public, status,
    create_time, update_time
) VALUES (
    0, 1, 1,
    '巴黎铁塔风景-测试',
    1,  -- 场景类型1: 图生图-单图编辑
    '风景',
    1,  -- 关联阿里云API配置ID
    '{"prompt": "巴黎铁塔风景，蓝天白云，阳光明媚", "size": "1024*1024", "n": 2}',
    'https://example.com/eiffel_tower_reference.jpg',
    1,  -- 公开
    1,  -- 启用
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

-- 获取场景ID
SELECT LAST_INSERT_ID() AS scene_id;
```

### 4.2 创建视频生成场景

```sql
-- 假设上面可灵AI配置的ID为2
INSERT INTO ddwx_ai_travel_photo_scene (
    aid, bid, mdid,
    scene_name, scene_type, category,
    api_config_id,
    model_params,
    reference_image,
    is_public, status,
    create_time, update_time
) VALUES (
    0, 1, 1,
    '动感视频-镜头推进-测试',
    3,  -- 场景类型3: 视频生成-首帧
    '视频',
    2,  -- 关联可灵AI配置ID
    '{"prompt": "镜头缓慢推进，人物自然微笑", "mode": "std", "duration": 5}',
    'https://example.com/video_reference.jpg',
    1,  -- 公开
    1,  -- 启用
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

-- 获取场景ID
SELECT LAST_INSERT_ID() AS scene_id;
```

---

## 五、测试完整流程

### 5.1 准备测试数据

```sql
-- 确保有测试用的人像素材
SELECT id, md5, original_url, status
FROM ddwx_ai_travel_photo_portrait
WHERE status = 1
LIMIT 1;

-- 假设返回的portrait_id为100
```

### 5.2 测试图生图（场景类型1）

**步骤1: 提交生成任务**

```bash
curl -X POST http://your-domain.com/api/ai-travel-photo/generate \
  -H "Content-Type: application/json" \
  -d '{
    "scene_id": 1,
    "portrait_id": 100,
    "bid": 1,
    "mdid": 1
  }'
```

**预期响应**:
```json
{
  "code": 0,
  "msg": "success",
  "data": {
    "generation_id": 1001,
    "task_status": 0,
    "message": "任务已提交，正在处理中..."
  }
}
```

**步骤2: 查看队列日志**

```bash
tail -f logs/image_queue.log

# 预期看到类似输出
[2026-02-04 10:30:15] 开始处理图生图任务: 1001
[2026-02-04 10:30:16] 查询API配置: ID=1, Provider=aliyun
[2026-02-04 10:30:17] 调用阿里云通义万相API...
[2026-02-04 10:30:18] 获得异步任务ID: task_123456
[2026-02-04 10:30:25] 图生图任务成功: 1001
```

**步骤3: 查询生成结果**

```bash
# 等待10秒后查询
sleep 10

curl http://your-domain.com/api/ai-travel-photo/generation-result?generation_id=1001
```

**预期响应（成功）**:
```json
{
  "code": 0,
  "msg": "success",
  "data": {
    "generation_id": 1001,
    "status": 2,
    "status_text": "已完成",
    "scene_type": 1,
    "results": [
      {
        "type": 1,
        "url": "https://oss.example.com/ai-travel-photo/results/abc123_1.jpg",
        "width": 1024,
        "height": 1024
      },
      {
        "type": 2,
        "url": "https://oss.example.com/ai-travel-photo/results/abc123_2.jpg",
        "width": 1024,
        "height": 1024
      }
    ]
  }
}
```

### 5.3 测试视频生成（场景类型3）

**步骤1: 提交生成任务**

```bash
curl -X POST http://your-domain.com/api/ai-travel-photo/generate \
  -H "Content-Type: application/json" \
  -d '{
    "scene_id": 2,
    "portrait_id": 100,
    "bid": 1,
    "mdid": 1
  }'
```

**步骤2: 查看队列日志**

```bash
tail -f logs/video_queue.log

# 预期看到类似输出
[2026-02-04 10:35:00] 开始处理视频生成任务: 1002
[2026-02-04 10:35:01] 查询API配置: ID=2, Provider=kling
[2026-02-04 10:35:02] 调用可灵AI image2video API...
[2026-02-04 10:35:03] 获得异步任务ID: kling_task_789
[2026-02-04 10:35:45] 视频生成任务成功: 1002
```

**步骤3: 查询生成结果**

```bash
# 等待60秒后查询（视频生成较慢）
sleep 60

curl http://your-domain.com/api/ai-travel-photo/generation-result?generation_id=1002
```

**预期响应（成功）**:
```json
{
  "code": 0,
  "msg": "success",
  "data": {
    "generation_id": 1002,
    "status": 2,
    "status_text": "已完成",
    "scene_type": 3,
    "video_url": "https://oss.example.com/ai-travel-photo/videos/def456.mp4",
    "video_duration": 5,
    "cover_url": "https://oss.example.com/ai-travel-photo/videos/def456_cover.jpg",
    "file_size": 8388608
  }
}
```

---

## 六、常见问题排查

### 6.1 任务状态一直是"处理中"

**检查1: 队列是否运行**
```bash
ps aux | grep queue:work

# 如果没有进程，启动队列
php think queue:work --queue=ai_image_generation &
php think queue:work --queue=ai_video_generation &
```

**检查2: 查看队列错误日志**
```bash
tail -n 50 logs/image_queue.log
tail -n 50 logs/video_queue.log
```

**检查3: 查询生成记录状态**
```sql
SELECT id, status, error_msg, task_id
FROM ddwx_ai_travel_photo_generation
WHERE id = 1001;
```

### 6.2 提示"API配置不存在或未启用"

**检查场景配置**:
```sql
SELECT id, scene_name, scene_type, api_config_id
FROM ddwx_ai_travel_photo_scene
WHERE id = 1;

-- 检查api_config_id是否有效
SELECT id, api_code, api_name, is_active
FROM ddwx_api_config
WHERE id = (
    SELECT api_config_id 
    FROM ddwx_ai_travel_photo_scene 
    WHERE id = 1
);
```

**解决方案**:
```sql
-- 更新场景的api_config_id
UPDATE ddwx_ai_travel_photo_scene
SET api_config_id = 1  -- 替换为有效的API配置ID
WHERE id = 1;
```

### 6.3 提示"不支持的服务提供商"

**检查provider字段**:
```sql
SELECT id, api_code, provider
FROM ddwx_api_config
WHERE id = 1;
```

**确保provider值为以下之一**:
- `aliyun` - 阿里云
- `kling` / `keling` - 可灵AI
- `openai` - OpenAI
- `baidu` - 百度（预留）

### 6.4 阿里云API返回401错误

**原因**: API密钥无效或过期

**解决方案**:
1. 重新获取API密钥
2. 更新配置：
```sql
UPDATE ddwx_api_config
SET api_key = 'sk-新的密钥'
WHERE api_code = 'aliyun_wanx_v1';
```

### 6.5 可灵AI返回401错误

**原因**: AccessKey或SecretKey错误

**解决方案**:
1. 检查可灵AI后台的密钥是否正确
2. 更新配置：
```sql
UPDATE ddwx_api_config
SET 
    api_key = 'access_key_新的密钥',
    api_secret = 'secret_key_新的密钥'
WHERE api_code = 'kling_v1_5';
```

---

## 七、监控命令

### 7.1 实时监控队列

```bash
# 开启终端1 - 监控图生图队列
watch -n 2 "php think queue:status | grep ai_image_generation"

# 开启终端2 - 监控视频生成队列
watch -n 2 "php think queue:status | grep ai_video_generation"

# 开启终端3 - 监控生成记录
watch -n 2 "mysql -u用户名 -p密码 数据库名 -e 'SELECT id, status, scene_type, create_time FROM ddwx_ai_travel_photo_generation ORDER BY id DESC LIMIT 5;'"
```

### 7.2 查询统计信息

```sql
-- 各场景类型的生成统计
SELECT 
    scene_type,
    COUNT(*) as total,
    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as success,
    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as processing
FROM ddwx_ai_travel_photo_generation
GROUP BY scene_type;

-- API配置使用统计
SELECT 
    s.api_config_id,
    ac.api_name,
    COUNT(g.id) as generation_count,
    SUM(CASE WHEN g.status = 2 THEN 1 ELSE 0 END) as success_count
FROM ddwx_ai_travel_photo_generation g
LEFT JOIN ddwx_ai_travel_photo_scene s ON g.scene_id = s.id
LEFT JOIN ddwx_api_config ac ON s.api_config_id = ac.id
GROUP BY s.api_config_id, ac.api_name;
```

---

## 八、生产环境建议

### 8.1 队列守护进程

使用Supervisor守护队列进程：

**/etc/supervisor/conf.d/ai-queue.conf**:
```ini
[program:ai_image_queue]
command=php /www/wwwroot/eivie/think queue:work --queue=ai_image_generation
directory=/www/wwwroot/eivie
user=www
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/www/wwwroot/eivie/logs/image_queue.log

[program:ai_video_queue]
command=php /www/wwwroot/eivie/think queue:work --queue=ai_video_generation
directory=/www/wwwroot/eivie
user=www
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/www/wwwroot/eivie/logs/video_queue.log
```

启动Supervisor：
```bash
supervisorctl reread
supervisorctl update
supervisorctl start ai_image_queue ai_video_queue
supervisorctl status
```

### 8.2 日志轮转

**/etc/logrotate.d/ai-travel-photo**:
```
/www/wwwroot/eivie/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0644 www www
    sharedscripts
    postrotate
        supervisorctl restart ai_image_queue ai_video_queue
    endscript
}
```

### 8.3 告警配置

创建监控脚本检查队列状态：

**/www/wwwroot/eivie/scripts/check_queue_health.sh**:
```bash
#!/bin/bash

# 检查队列进程
IMAGE_QUEUE=$(ps aux | grep "ai_image_generation" | grep -v grep | wc -l)
VIDEO_QUEUE=$(ps aux | grep "ai_video_generation" | grep -v grep | wc -l)

if [ $IMAGE_QUEUE -eq 0 ]; then
    echo "警告: 图生图队列未运行" | mail -s "队列告警" admin@example.com
fi

if [ $VIDEO_QUEUE -eq 0 ]; then
    echo "警告: 视频生成队列未运行" | mail -s "队列告警" admin@example.com
fi

# 检查失败任务数
FAILED_COUNT=$(mysql -u用户名 -p密码 数据库名 -N -e "SELECT COUNT(*) FROM ddwx_ai_travel_photo_generation WHERE status = 3 AND create_time > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR));")

if [ $FAILED_COUNT -gt 10 ]; then
    echo "警告: 最近1小时失败任务数: $FAILED_COUNT" | mail -s "生成失败告警" admin@example.com
fi
```

添加到crontab：
```bash
# 每5分钟检查一次
*/5 * * * * /www/wwwroot/eivie/scripts/check_queue_health.sh
```

---

## 九、下一步

1. ✅ 完成基础配置
2. ✅ 测试核心功能
3. 🔄 配置监控告警
4. 🔄 优化性能参数
5. 🔄 准备生产部署

---

**文档版本**: v1.0  
**最后更新**: 2026-02-04  
**联系支持**: dev@example.com
