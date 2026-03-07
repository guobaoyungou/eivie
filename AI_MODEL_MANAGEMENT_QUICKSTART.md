# AI模型管理系统 - 快速入门指南

## 一、系统部署

### 1. 执行数据库迁移

```bash
# 进入项目目录
cd /www/wwwroot/eivie

# 执行迁移脚本（请先备份数据库）
mysql -h localhost -u 数据库用户名 -p 数据库名 < database/migrations/ai_model_management_tables.sql
```

### 2. 验证表结构

执行完迁移后，检查以下表是否创建成功：

- `ddwx_ai_model_category` - 模型分类表（应包含8条系统预置数据）
- `ddwx_ai_travel_photo_model` - 模型配置表（应已扩展新字段）
- `ddwx_ai_model_usage_log` - 使用记录表

### 3. 确保Redis服务运行

```bash
# 检查Redis服务状态
systemctl status redis

# 如未运行，启动Redis
systemctl start redis
```

## 二、功能访问

### 后台菜单路径

登录商家后台后，按以下路径访问：

```
AI旅拍 > 模型设置
  ├── 模型分类    （管理AI模型分类）
  ├── API配置     （配置第三方API）
  └── 调用统计    （查看调用数据）
```

### 权限说明

- **平台管理员**：可查看和管理所有商家的配置
- **商家管理员**：仅可管理自己商家的配置
- **门店管理员**：仅可查看自己门店的配置

## 三、快速配置指南

### 步骤1：查看模型分类

1. 进入 `AI旅拍 > 模型设置 > 模型分类`
2. 查看系统预置的8个分类：
   - 千问 (qianwen)
   - 豆包 (doubao)
   - 可灵 (kling)
   - 即梦 (jimeng)
   - OpenAI (openai)
   - Ollama (ollama)
   - 通义万相 (tongyi_wanxiang)
   - 其他 (other)

3. 如需自定义分类，点击"新增分类"按钮

### 步骤2：添加API配置

1. 进入 `AI旅拍 > 模型设置 > API配置`
2. 点击"添加API配置"按钮
3. 填写配置信息：

**基础信息页：**
- 配置名称：如"千问API-1"
- 模型分类：选择对应的分类
- 服务提供商：如"aliyun"
- 适用门店：选择"通用配置"或指定门店
- 状态：启用

**API配置页：**
- API密钥：填写从服务商获取的密钥
- API秘钥：部分模型需要（如阿里云的AccessKeySecret）
- API基础URL：如 `https://dashscope.aliyuncs.com/api/v1/services/aigc/image-generation`
- API版本：如 v1
- 请求超时：180秒（默认）
- 最大重试次数：3次（默认）

**并发控制页：**
- 最大并发数：5（默认，根据实际需求调整）
- 优先级：100（默认，数值越大优先级越高）
- 是否默认：同类型仅一个默认
- 是否激活：是

**成本配置页：**
- 图片单价：0.05元（默认，根据实际计费调整）
- 视频单价：0.50元（默认）
- Token单价：0.000001元（默认）

4. 点击"提交"保存配置

### 步骤3：测试API连通性

1. 在API配置列表中，找到刚添加的配置
2. 点击"测试"按钮
3. 系统会发送测试请求验证配置是否正确
4. 测试成功会显示响应时间
5. 测试失败会显示错误信息

### 步骤4：查看调用统计

1. 进入 `AI旅拍 > 模型设置 > 调用统计`
2. 查看统计概览：
   - 总调用次数
   - 成功调用次数和成功率
   - 失败调用次数和失败率
   - 总消耗成本和今日成本

3. 查看调用明细：
   - 可按模型分类、业务类型、状态筛选
   - 查看每次调用的详细信息
   - 包括时间、模型、耗时、成本、错误信息等

## 四、业务集成示例

### 在AI旅拍业务中使用

```php
use app\service\AiModelService;

// 示例1：人像抠图
public function cutoutPortrait($imageUrl)
{
    $result = AiModelService::call(
        'tongyi_wanxiang',  // 使用通义万相
        'cutout',           // 抠图业务
        [
            'image_url' => $imageUrl,
            'mode' => 'person'
        ],
        $this->mdid,        // 当前门店ID
        $this->bid,         // 当前商家ID
        $this->aid          // 当前平台ID
    );
    
    if ($result['success']) {
        // 抠图成功，处理结果
        $cutoutImageUrl = $result['data']['output_url'];
        return $cutoutImageUrl;
    } else {
        // 抠图失败，记录错误
        Log::error('抠图失败：' . $result['error']);
        return false;
    }
}

// 示例2：AI生图
public function generateImage($prompt)
{
    $result = AiModelService::call(
        'qianwen',          // 使用千问
        'image_gen',        // 生图业务
        [
            'prompt' => $prompt,
            'size' => '1024x1024',
            'n' => 1
        ],
        $this->mdid,
        $this->bid,
        $this->aid
    );
    
    if ($result['success']) {
        return $result['data']['images'][0]['url'];
    } else {
        return false;
    }
}

// 示例3：AI生视频
public function generateVideo($imageUrl)
{
    $result = AiModelService::call(
        'kling',            // 使用可灵
        'video_gen',        // 生视频业务
        [
            'image_url' => $imageUrl,
            'duration' => 5,
            'mode' => 'std'
        ],
        $this->mdid,
        $this->bid,
        $this->aid
    );
    
    if ($result['success']) {
        return $result['data']['video_url'];
    } else {
        return false;
    }
}
```

## 五、调度策略说明

### 配置选择优先级

系统会按以下顺序选择API配置：

1. **门店专属配置**（mdid > 0）
2. **商家通用配置**（mdid = 0, bid > 0）
3. **平台默认配置**（mdid = 0, bid = 0）

### 负载均衡规则

在同一优先级的配置中，系统按以下规则排序：

1. **优先级降序**（priority值越大越优先）
2. **当前并发数升序**（并发数少的优先）
3. **成功率降序**（成功率高的优先）

### 并发控制

- 每个API配置有独立的并发限制
- 使用Redis实时计数，原子操作
- 并发已满的配置会自动跳过
- 所有配置并发已满时返回错误

### 失败重试

- **网络超时**：自动重试，最多3次
- **API限流(429)**：不重试，切换其他配置
- **参数错误(400)**：不重试，记录错误
- **服务器错误(500)**：重试，最多2次

重试延迟：立即 → 2秒 → 5秒

## 六、常见问题

### Q1: 如何添加新的AI模型？

A: 有两种方式：
1. 使用系统预置分类（如果模型属于已有分类）
2. 创建自定义分类：`模型设置 > 模型分类 > 新增分类`

### Q2: 同一模型可以配置多个API吗？

A: 可以。这正是并发优化的核心功能，配置多个API可以：
- 提高并发处理能力
- 避免单点故障
- 实现自动负载均衡

### Q3: 如何查看API调用失败的原因？

A: 进入`调用统计`页面，筛选状态为"失败"的记录，查看错误信息列。

### Q4: 成本统计不准确怎么办？

A: 在API配置中调整成本设置：
- 图片单价
- 视频单价
- Token单价

根据实际服务商的计费规则填写。

### Q5: 可以为不同门店配置不同的API吗？

A: 可以。在添加API配置时，选择"适用门店"为具体门店即可。

### Q6: 如何禁用某个API配置？

A: 在API配置列表中，点击"编辑"，将状态改为"禁用"即可。

## 七、性能优化建议

### 1. 合理设置并发数

根据服务商的限制和业务需求设置：
- 高并发场景：增加最大并发数或添加多个API
- 低并发场景：使用默认值即可

### 2. 配置缓存

系统已自动缓存：
- API配置列表（5分钟）
- 并发计数（Redis实时）
- 统计数据（10分钟）

### 3. 日志清理

建议定期清理过期日志：
```sql
-- 清理30天前的成功日志
DELETE FROM ddwx_ai_model_usage_log 
WHERE status = 1 AND create_time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY));

-- 清理90天前的失败日志
DELETE FROM ddwx_ai_model_usage_log 
WHERE status = 0 AND create_time < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 90 DAY));
```

### 4. 监控告警

建议监控以下指标：
- 成功率（低于90%告警）
- 响应时间（超过10秒告警）
- 并发数（接近上限告警）
- 成本（超过预算告警）

## 八、安全注意事项

### 1. API密钥保护

- 密钥在数据库中加密存储
- 前端仅显示脱敏信息（前4位+后4位）
- 不要在日志中记录完整密钥

### 2. 访问权限

- 严格控制菜单和操作权限
- 不同角色仅能访问授权数据
- 定期审计操作日志

### 3. 数据备份

- 定期备份数据库
- 重要配置变更前先备份
- 保留历史版本以便回滚

## 九、技术支持

如遇到问题，请检查：

1. **日志文件**
   - 应用日志：`runtime/log/`
   - 错误日志：查看服务器error_log

2. **Redis状态**
   ```bash
   redis-cli info
   redis-cli dbsize
   ```

3. **数据库连接**
   ```bash
   mysql -u用户名 -p密码 -e "SELECT 1"
   ```

4. **文件权限**
   ```bash
   ls -la /www/wwwroot/eivie/app/service/
   ls -la /www/wwwroot/eivie/app/view/ai_travel_photo/
   ```

## 十、更新日志

**版本 1.0.0** (2026-02-03)
- ✅ 初始版本发布
- ✅ 实现模型分类管理
- ✅ 实现API配置管理
- ✅ 实现调用统计
- ✅ 实现负载均衡和并发控制
- ✅ 实现失败重试机制
- ✅ 集成菜单和权限系统

---

**祝您使用愉快！如有问题欢迎反馈。**
