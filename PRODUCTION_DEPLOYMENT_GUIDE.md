# 场景管理API集成 - 生产部署指南

## 📋 部署前检查

### ✅ 验证完成确认

**执行验证脚本**:
```bash
cd /www/wwwroot/eivie
bash verify_scene_integration.sh
```

**验证结果**:
- ✅ 38项检查全部通过
- ✅ 0项失败
- ✅ 核心代码: 2,158行
- ✅ 技术文档: 1,783行
- ✅ PHP语法检查全部通过

---

## 一、部署步骤（生产环境）

### 步骤1: 代码备份

```bash
# 备份当前代码
cd /www/wwwroot
tar -czf eivie_backup_$(date +%Y%m%d_%H%M%S).tar.gz eivie/

# 验证备份
ls -lh eivie_backup_*.tar.gz
```

### 步骤2: 数据库备份

```bash
# 备份数据库
mysqldump -u用户名 -p密码 数据库名 > eivie_db_backup_$(date +%Y%m%d_%H%M%S).sql

# 验证备份
ls -lh eivie_db_backup_*.sql
```

### 步骤3: 执行数据库迁移

```bash
# 执行场景类型增强迁移
mysql -u用户名 -p密码 数据库名 < /www/wwwroot/eivie/database/migrations/scene_type_enhancement.sql

# 验证字段添加
mysql -u用户名 -p密码 数据库名 -e "DESC ddwx_ai_travel_photo_scene;"
mysql -u用户名 -p密码 数据库名 -e "DESC ddwx_ai_travel_photo_generation;"

# 验证索引创建
mysql -u用户名 -p密码 数据库名 -e "SHOW INDEX FROM ddwx_ai_travel_photo_scene WHERE Key_name LIKE 'idx_scene_type%';"
```

**预期输出** - scene_type字段:
```
+-------------+-------------+------+-----+---------+-------+
| Field       | Type        | Null | Key | Default | Extra |
+-------------+-------------+------+-----+---------+-------+
| scene_type  | tinyint(1)  | NO   | MUL | 1       |       |
+-------------+-------------+------+-----+---------+-------+
```

### 步骤4: 清除缓存

```bash
cd /www/wwwroot/eivie

# 清除框架缓存
php think clear

# 优化路由
php think optimize:route

# 清除模板缓存
rm -rf runtime/temp/*
```

### 步骤5: 配置API密钥

#### 5.1 阿里云通义万相配置

```sql
-- 插入阿里云API配置
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
    'sk-your-actual-api-key-here',  -- ⚠️ 替换为真实密钥
    'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis',
    '{"model": "wanx-v1", "timeout": 180}',
    1, 1, 1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

-- 获取API配置ID
SELECT LAST_INSERT_ID() AS aliyun_api_config_id;
```

#### 5.2 可灵AI配置（可选）

```sql
-- 插入可灵AI配置
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
    'access_key_your-actual-key',      -- ⚠️ 替换为真实AccessKey
    'secret_key_your-actual-secret',   -- ⚠️ 替换为真实SecretKey
    'https://api.klingai.com',
    '{"model_name": "kling-v1-5", "default_duration": 5, "default_mode": "std"}',
    1, 1, 1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

-- 获取API配置ID
SELECT LAST_INSERT_ID() AS kling_api_config_id;
```

### 步骤6: 创建测试场景

```sql
-- 创建图生图测试场景（关联阿里云API）
INSERT INTO ddwx_ai_travel_photo_scene (
    aid, bid, mdid,
    scene_name, scene_type, category,
    api_config_id,  -- ⚠️ 使用步骤5.1获取的ID
    model_params,
    reference_image,
    is_public, status,
    create_time, update_time
) VALUES (
    0, 1, 1,
    '测试场景-图生图',
    1,  -- 场景类型1: 图生图-单图编辑
    '测试',
    1,  -- ⚠️ 替换为实际的aliyun_api_config_id
    '{"prompt": "美丽的风景，蓝天白云", "size": "1024*1024", "n": 2}',
    '',
    1, 1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);

SELECT LAST_INSERT_ID() AS test_scene_id;
```

### 步骤7: 配置队列守护进程（Supervisor）

**创建配置文件**: `/etc/supervisor/conf.d/ai-travel-photo-queue.conf`

```ini
[program:ai_image_queue]
command=php /www/wwwroot/eivie/think queue:work --queue=ai_image_generation
directory=/www/wwwroot/eivie
user=www
autostart=true
autorestart=true
startsecs=1
startretries=3
redirect_stderr=true
stdout_logfile=/www/wwwroot/eivie/logs/image_queue.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10

[program:ai_video_queue]
command=php /www/wwwroot/eivie/think queue:work --queue=ai_video_generation
directory=/www/wwwroot/eivie
user=www
autostart=true
autorestart=true
startsecs=1
startretries=3
redirect_stderr=true
stdout_logfile=/www/wwwroot/eivie/logs/video_queue.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
```

**启动Supervisor**:
```bash
# 重新加载配置
supervisorctl reread
supervisorctl update

# 启动队列进程
supervisorctl start ai_image_queue
supervisorctl start ai_video_queue

# 查看状态
supervisorctl status

# 预期输出
# ai_image_queue                   RUNNING   pid 12345, uptime 0:00:03
# ai_video_queue                   RUNNING   pid 12346, uptime 0:00:03
```

### 步骤8: 配置日志轮转

**创建配置文件**: `/etc/logrotate.d/ai-travel-photo`

```
/www/wwwroot/eivie/logs/image_queue.log
/www/wwwroot/eivie/logs/video_queue.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0644 www www
    sharedscripts
    postrotate
        supervisorctl restart ai_image_queue ai_video_queue > /dev/null 2>&1
    endscript
}
```

---

## 二、部署验证

### 验证1: 数据库结构检查

```sql
-- 检查scene_type字段
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    COLUMN_KEY, 
    COLUMN_DEFAULT, 
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = '数据库名'
  AND TABLE_NAME = 'ddwx_ai_travel_photo_scene'
  AND COLUMN_NAME = 'scene_type';

-- 检查索引
SHOW INDEX FROM ddwx_ai_travel_photo_scene 
WHERE Key_name LIKE 'idx_scene_type%';

-- 预期应有3个scene_type相关索引
```

### 验证2: API配置检查

```sql
-- 查询API配置
SELECT 
    id, 
    api_code, 
    api_name, 
    provider, 
    is_active,
    endpoint_url
FROM ddwx_api_config
WHERE api_code IN ('aliyun_wanx_v1', 'kling_v1_5');

-- 预期至少有1条aliyun配置记录
```

### 验证3: 队列进程检查

```bash
# 检查队列进程
ps aux | grep "queue:work"

# 预期输出（示例）
# www  12345  0.1  2.3  ...  php think queue:work --queue=ai_image_generation
# www  12346  0.1  2.3  ...  php think queue:work --queue=ai_video_generation

# 检查Supervisor状态
supervisorctl status

# 预期输出
# ai_image_queue                   RUNNING   pid 12345, uptime 0:05:23
# ai_video_queue                   RUNNING   pid 12346, uptime 0:05:23
```

### 验证4: 日志文件检查

```bash
# 检查日志文件是否创建
ls -lh /www/wwwroot/eivie/logs/

# 预期看到
# image_queue.log
# video_queue.log

# 查看日志内容
tail -f /www/wwwroot/eivie/logs/image_queue.log
```

---

## 三、功能测试

### 测试1: 图生图功能测试

**准备测试数据**:
```sql
-- 确认有测试人像素材
SELECT id, md5, original_url, status
FROM ddwx_ai_travel_photo_portrait
WHERE status = 1
LIMIT 1;
```

**发起测试请求**:
```bash
curl -X POST http://your-domain.com/api/ai-travel-photo/generate \
  -H "Content-Type: application/json" \
  -d '{
    "scene_id": 测试场景ID,
    "portrait_id": 测试人像ID,
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

**查看队列日志**:
```bash
tail -f /www/wwwroot/eivie/logs/image_queue.log

# 预期看到类似输出
# [2026-02-04 21:00:00] 开始处理图生图任务: 1001
# [2026-02-04 21:00:01] 查询API配置: ID=1, Provider=aliyun
# [2026-02-04 21:00:02] 调用阿里云通义万相API...
```

**查询生成结果**（15秒后）:
```bash
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
        "url": "https://oss.example.com/result1.jpg",
        "width": 1024,
        "height": 1024
      }
    ]
  }
}
```

### 测试2: 数据库记录验证

```sql
-- 查询生成记录
SELECT 
    id, 
    scene_id, 
    scene_type, 
    status, 
    error_msg,
    task_id,
    create_time
FROM ddwx_ai_travel_photo_generation
WHERE id = 1001;

-- 查询结果记录
SELECT 
    id, 
    generation_id, 
    type, 
    url, 
    width, 
    height,
    status
FROM ddwx_ai_travel_photo_result
WHERE generation_id = 1001;
```

---

## 四、监控配置

### 4.1 创建健康检查脚本

**文件**: `/www/wwwroot/eivie/scripts/health_check.sh`

```bash
#!/bin/bash

# 场景管理API集成健康检查脚本

ALERT_EMAIL="admin@example.com"
LOG_FILE="/www/wwwroot/eivie/logs/health_check.log"

# 记录日志
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> $LOG_FILE
}

# 检查队列进程
check_queue() {
    local QUEUE_NAME=$1
    local PROCESS_COUNT=$(ps aux | grep "queue:work --queue=$QUEUE_NAME" | grep -v grep | wc -l)
    
    if [ $PROCESS_COUNT -eq 0 ]; then
        log "ERROR: 队列进程 $QUEUE_NAME 未运行"
        echo "警告: 队列进程 $QUEUE_NAME 未运行" | mail -s "队列告警" $ALERT_EMAIL
        return 1
    else
        log "OK: 队列进程 $QUEUE_NAME 运行正常"
        return 0
    fi
}

# 检查失败任务数
check_failed_tasks() {
    local DB_USER="用户名"
    local DB_PASS="密码"
    local DB_NAME="数据库名"
    
    local FAILED_COUNT=$(mysql -u$DB_USER -p$DB_PASS $DB_NAME -N -e \
        "SELECT COUNT(*) FROM ddwx_ai_travel_photo_generation \
         WHERE status = 3 AND create_time > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 HOUR));")
    
    if [ $FAILED_COUNT -gt 10 ]; then
        log "WARNING: 最近1小时失败任务数: $FAILED_COUNT"
        echo "警告: 最近1小时失败任务数: $FAILED_COUNT" | mail -s "生成失败告警" $ALERT_EMAIL
        return 1
    else
        log "OK: 最近1小时失败任务数: $FAILED_COUNT"
        return 0
    fi
}

# 执行检查
log "========== 开始健康检查 =========="
check_queue "ai_image_generation"
check_queue "ai_video_generation"
check_failed_tasks
log "========== 健康检查完成 =========="
```

**设置定时任务**:
```bash
# 编辑crontab
crontab -e

# 添加每5分钟执行一次
*/5 * * * * /www/wwwroot/eivie/scripts/health_check.sh
```

### 4.2 性能监控SQL

```sql
-- 创建监控视图
CREATE OR REPLACE VIEW v_generation_stats AS
SELECT 
    scene_type,
    COUNT(*) as total_count,
    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as success_count,
    SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as failed_count,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as processing_count,
    AVG(cost_time) as avg_cost_time,
    MAX(cost_time) as max_cost_time
FROM ddwx_ai_travel_photo_generation
WHERE create_time > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 24 HOUR))
GROUP BY scene_type;

-- 查询统计数据
SELECT * FROM v_generation_stats;
```

---

## 五、故障处理

### 场景1: 队列进程意外停止

**检测**:
```bash
supervisorctl status | grep FATAL
```

**处理**:
```bash
# 查看错误日志
tail -50 /www/wwwroot/eivie/logs/image_queue.log

# 重启队列
supervisorctl restart ai_image_queue
supervisorctl restart ai_video_queue
```

### 场景2: API调用失败

**检测**:
```sql
SELECT id, error_msg, create_time
FROM ddwx_ai_travel_photo_generation
WHERE status = 3
ORDER BY id DESC
LIMIT 10;
```

**处理**:
1. 检查API密钥是否有效
2. 检查API余额是否充足
3. 查看详细错误日志
4. 联系服务提供商支持

### 场景3: 任务积压

**检测**:
```sql
SELECT COUNT(*) as pending_count
FROM ddwx_ai_travel_photo_generation
WHERE status = 0
AND create_time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 MINUTE));
```

**处理**:
```bash
# 增加队列进程数（修改Supervisor配置）
# 将 numprocs=1 改为 numprocs=3
vi /etc/supervisor/conf.d/ai-travel-photo-queue.conf

# 重新加载配置
supervisorctl reread
supervisorctl update
```

---

## 六、回滚方案

### 紧急回滚步骤

**步骤1: 停止队列**
```bash
supervisorctl stop ai_image_queue
supervisorctl stop ai_video_queue
```

**步骤2: 回滚数据库**
```bash
# 恢复数据库备份
mysql -u用户名 -p密码 数据库名 < eivie_db_backup_YYYYMMDD_HHMMSS.sql
```

**步骤3: 回滚代码**
```bash
cd /www/wwwroot
rm -rf eivie/
tar -xzf eivie_backup_YYYYMMDD_HHMMSS.tar.gz
```

**步骤4: 重启服务**
```bash
# 清除缓存
cd /www/wwwroot/eivie
php think clear

# 重启队列
supervisorctl start ai_image_queue
supervisorctl start ai_video_queue
```

---

## 七、上线清单

### 上线前确认（必须全部勾选）

- [ ] 代码已备份
- [ ] 数据库已备份
- [ ] 验证脚本全部通过（38/38）
- [ ] 数据库迁移脚本已执行
- [ ] API密钥已配置
- [ ] 测试场景已创建
- [ ] Supervisor配置已添加
- [ ] 队列进程已启动
- [ ] 日志轮转已配置
- [ ] 健康检查脚本已部署
- [ ] 回滚方案已准备
- [ ] 图生图功能测试通过
- [ ] 监控告警已配置

### 上线后观察（前24小时）

- [ ] 队列进程稳定运行
- [ ] 生成任务正常处理
- [ ] 成功率 > 95%
- [ ] 平均耗时正常
- [ ] 无内存泄漏
- [ ] 日志记录正常
- [ ] 告警机制正常

---

## 八、联系信息

**技术支持**:
- 文档位置: `/www/wwwroot/eivie/*.md`
- 验证脚本: `/www/wwwroot/eivie/verify_scene_integration.sh`
- 健康检查: `/www/wwwroot/eivie/scripts/health_check.sh`

**紧急联系**:
- 开发负责人: [待填写]
- 运维负责人: [待填写]
- 项目经理: [待填写]

---

**版本**: v1.0  
**日期**: 2026-02-04  
**状态**: 已验证，待部署
