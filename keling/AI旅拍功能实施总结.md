# AI旅拍功能实施总结

## 实施日期
2026-01-19

## 完成情况

### ✅ 已完成任务

#### 1. 设计阶段
- [x] 分析系统架构和可灵AI接口文档
- [x] 设计AI旅拍功能数据库表结构
- [x] 创建可灵AI接口封装类设计

#### 2. 基础设施搭建
- [x] 执行数据库表创建SQL (7个表)
- [x] 安装JWT依赖包(使用PHP原生实现)
- [x] 创建AI旅拍配置文件
- [x] 创建目录结构

#### 3. 核心功能开发
- [x] 创建可灵AI服务类 (KlingAIService.php)
- [x] 创建AI旅拍公共类 (Aivideo.php)
- [x] 创建商家监控程序 (AivideoMonitor.php)
- [x] 创建游客端API控制器 (ApiAivideo.php)
- [x] 创建商家后台控制器 (AdminAivideo.php)
- [x] 创建定时任务 (AivideoCron.php)
- [x] 验证所有PHP文件语法

## 创建的文件清单

### 数据库相关
```
/www/wwwroot/eivie/keling/aivideo_tables.sql
```

### 配置文件
```
/www/wwwroot/eivie/config/aivideo.php
```

### 核心类文件
```
/www/wwwroot/eivie/app/service/KlingAIService.php
/www/wwwroot/eivie/app/common/Aivideo.php
```

### 控制器文件
```
/www/wwwroot/eivie/app/controller/ApiAivideo.php
/www/wwwroot/eivie/app/controller/AdminAivideo.php
```

### 监控和定时任务
```
/www/wwwroot/eivie/app/monitor/AivideoMonitor.php
/www/wwwroot/eivie/app/command/AivideoCron.php
```

### 目录结构
```
/www/wwwroot/eivie/upload/aivideo/material/
/www/wwwroot/eivie/upload/aivideo/work/
/www/wwwroot/eivie/upload/aivideo/thumbnail/
/www/wwwroot/eivie/upload/aivideo/qrcode/
/www/wwwroot/eivie/app/service/
/www/wwwroot/eivie/app/monitor/
/www/wwwroot/eivie/app/command/
```

## 功能特性

### 已实现功能

#### 1. 可灵AI接口封装
- JWT Token生成 (PHP原生实现)
- 图生视频接口
- 文生视频接口
- 视频特效接口
- 任务状态查询
- 多账号负载均衡

#### 2. 任务管理
- 创建AI生成任务
- 任务队列管理 (Redis)
- 任务状态轮询
- 任务失败重试

#### 3. 作品管理
- 预览图生成 (视频第一帧)
- 二维码生成 (使用endroid/qrcode)
- 预览图和二维码合并
- 作品记录管理

#### 4. 商家监控程序
- Windows文件监控
- 多目录支持 (分号分隔)
- 自动上传到服务器
- 失败重试 (最多5次)
- 上传进度显示

#### 5. 游客端API
- 微信扫码授权
- 作品列表查询
- 作品详情查看
- 订单创建
- 支付回调处理
- 浏览记录查询 (30天)
- 支付后通知发送

#### 6. 商家后台管理
- 商家配置管理 (可灵AI账号)
- 提示词模板管理
- 素材管理
- 作品管理
- 订单管理
- 统计报表

#### 7. 定时任务
- 任务状态轮询
- 订单自动取消 (30分钟未支付)
- 浏览记录清理 (30天过期)

## 技术实现细节

### JWT Token生成
使用PHP原生方法实现JWT Token生成,避免依赖外部包:
```php
private function base64UrlEncode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

private function generateToken($accessKey, $secretKey)
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $payload = [
        'iss' => $accessKey,
        'exp' => time() + 1800,
        'nbf' => time() - 5
    ];

    $headerEncoded = $this->base64UrlEncode(json_encode($header));
    $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secretKey, true);
    $signatureEncoded = $this->base64UrlEncode($signature);

    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}
```

### 多账号负载均衡
使用轮询策略实现多账号负载均衡:
```php
private function getNextAccount()
{
    if (empty($this->accounts)) {
        return null;
    }

    $account = $this->accounts[$this->currentAccountIndex];
    $this->currentAccountIndex = ($this->currentAccountIndex + 1) % count($this->accounts);

    return $account;
}
```

### 预览图生成
使用FFmpeg提取视频第一帧作为预览图:
```bash
ffmpeg -i {video_url} -ss 00:00:00.000 -vframes 1 {output_path}
```

### 二维码生成
使用系统已安装的endroid/qrcode库生成二维码:
```php
$qrCode = new \Endroid\QrCode\QrCode($workUrl);
$qrCode->setSize(150);
$qrCode->setMargin(10);
$writer = new \Endroid\QrCode\Writer\PngWriter();
$result = $writer->write($qrCode);
```

## 数据库表结构

### 已创建的表
1. `ddwx_aivideo_merchant_config` - 商家配置表
2. `ddwx_aivideo_material` - 素材表
3. `ddwx_aivideo_task` - 任务表
4. `ddwx_aivideo_work` - 作品表
5. `ddwx_aivideo_order` - 订单表
6. `ddwx_aivideo_selection` - 选片记录表
7. `ddwx_aivideo_prompt_template` - 提示词模板表

## 配置项说明

### config/aivideo.php
```php
return [
    'kling' => [
        'api_url' => 'https://api-beijing.klingai.com',
        'token_expire' => 1800, // 30分钟
        'max_retry' => 5,
        'default_model' => 'kling-v1',
        'default_mode' => 'std',
        'default_aspect_ratio' => '16:9',
        'default_duration' => '5',
    ],
    'queue' => [
        'prefix' => 'aivideo:',
        'task_queue' => 'task',
        'max_concurrent' => 10,
    ],
    'order' => [
        'expire_time' => 1800, // 30分钟
    ],
    'browse' => [
        'expire_days' => 30,
    ],
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'mp4'],
        'upload_path' => ROOT_PATH . 'upload/aivideo/',
        'material_path' => ROOT_PATH . 'upload/aivideo/material/',
        'work_path' => ROOT_PATH . 'upload/aivideo/work/',
        'thumbnail_path' => ROOT_PATH . 'upload/aivideo/thumbnail/',
        'qrcode_path' => ROOT_PATH . 'upload/aivideo/qrcode/',
    ],
    'monitor' => [
        'max_retry' => 5,
        'check_interval' => 5, // 5秒
    ],
];
```

## API接口说明

### 游客端API (ApiAivideo.php)

#### 微信授权
```
GET /api/aivideo/wechat_auth
参数:
  - aid: 应用ID
  - code: 微信授权码
返回:
  - mid: 会员ID
  - openid: 微信OpenID
  - access_token: 访问令牌
  - nickname: 昵称
  - headimg: 头像
```

#### 获取作品列表
```
GET /api/aivideo/work_list
参数:
  - aid: 应用ID
  - mid: 会员ID
  - page: 页码
  - limit: 每页数量
返回:
  - list: 作品列表
  - total: 总数
```

#### 获取作品详情
```
GET /api/aivideo/work_detail
参数:
  - aid: 应用ID
  - id: 作品ID
  - mid: 会员ID
返回:
  - 作品详细信息
```

#### 创建订单
```
POST /api/aivideo/create_order
参数:
  - aid: 应用ID
  - mid: 会员ID
  - work_ids: 作品ID列表(逗号分隔)
返回:
  - order_id: 订单ID
  - ordernum: 订单号
  - total_price: 总金额
  - work_list: 作品列表
```

#### 支付回调
```
POST /api/aivideo/pay_callback
参数:
  - aid: 应用ID
  - ordernum: 订单号
  - pay_type: 支付方式
  - transaction_id: 第三方订单号
返回:
  - status: 状态
  - msg: 消息
```

#### 获取浏览记录
```
GET /api/aivideo/browse_history
参数:
  - aid: 应用ID
  - mid: 会员ID
  - page: 页码
  - limit: 每页数量
返回:
  - list: 浏览记录列表
  - total: 总数
```

### 商家后台API (AdminAivideo.php)

#### 商家配置列表
```
GET /admin/aivideo/config_list
参数:
  - aid: 应用ID
  - bid: 商家ID
  - page: 页码
  - limit: 每页数量
返回:
  - list: 配置列表
  - total: 总数
```

#### 保存商家配置
```
POST /admin/aivideo/save_config
参数:
  - aid: 应用ID
  - bid: 商家ID
  - merchant_name: 商家名称
  - access_key: 可灵AI AccessKey
  - secret_key: 可灵AI SecretKey
  - monitor_path: 监控路径
  - model_name: 模型名称
  - mode: 生成模式
  - aspect_ratio: 画幅比例
  - duration: 视频时长
  - auto_upload: 是否自动上传
返回:
  - status: 状态
  - msg: 消息
```

#### 提示词模板列表
```
GET /admin/aivideo/template_list
参数:
  - aid: 应用ID
  - bid: 商家ID
  - template_type: 模板类型
  - page: 页码
  - limit: 每页数量
返回:
  - list: 模板列表
  - total: 总数
```

#### 保存提示词模板
```
POST /admin/aivideo/save_template
参数:
  - aid: 应用ID
  - bid: 商家ID
  - template_name: 模板名称
  - template_type: 模板类型
  - prompt: 提示词
  - negative_prompt: 负向提示词
  - model_name: 模型名称
  - mode: 生成模式
  - aspect_ratio: 画幅比例
  - duration: 视频时长
  - effect_scene: 特效场景
  - sort: 排序
返回:
  - status: 状态
  - msg: 消息
```

#### 素材列表
```
GET /admin/aivideo/material_list
参数:
  - aid: 应用ID
  - bid: 商家ID
  - page: 页码
  - limit: 每页数量
返回:
  - list: 素材列表
  - total: 总数
```

#### 作品列表
```
GET /admin/aivideo/work_list
参数:
  - aid: 应用ID
  - bid: 商家ID
  - page: 页码
  - limit: 每页数量
返回:
  - list: 作品列表
  - total: 总数
```

#### 订单列表
```
GET /admin/aivideo/order_list
参数:
  - aid: 应用ID
  - bid: 商家ID
  - pay_status: 支付状态
  - page: 页码
  - limit: 每页数量
返回:
  - list: 订单列表
  - total: 总数
```

#### 统计数据
```
GET /admin/aivideo/statistics
参数:
  - aid: 应用ID
  - bid: 商家ID
  - start_date: 开始日期
  - end_date: 结束日期
返回:
  - order_count: 订单总数
  - paid_count: 已支付订单数
  - total_amount: 总金额
  - work_count: 作品总数
  - task_count: 任务总数
  - success_task_count: 成功任务数
```

## 下一步工作

### 待完成任务

#### 1. 路由配置
- [ ] 配置游客端API路由
- [ ] 配置商家后台API路由
- [ ] 配置定时任务路由

#### 2. 前端页面开发
- [ ] 游客端H5页面开发
  - [ ] 扫码授权页面
  - [ ] 作品列表页面
  - [ ] 作品详情页面
  - [ ] 订单创建页面
  - [ ] 支付页面
  - [ ] 浏览记录页面
- [ ] 商家后台页面开发
  - [ ] 配置管理页面
  - [ ] 模板管理页面
  - [ ] 素材管理页面
  - [ ] 作品管理页面
  - [ ] 订单管理页面
  - [ ] 统计报表页面

#### 3. 支付集成
- [ ] 集成微信支付
- [ ] 集成支付宝支付
- [ ] 集成余额支付
- [ ] 配置支付回调

#### 4. 测试和优化
- [ ] 单元测试
- [ ] 集成测试
- [ ] 性能测试
- [ ] 压力测试
- [ ] Bug修复

#### 5. 文档完善
- [ ] API文档完善
- [ ] 部署文档
- [ ] 使用手册
- [ ] 常见问题解答

## 注意事项

### 1. 系统依赖
- PHP 7.4.33+
- MySQL 8.0.36+
- Redis 8.0+
- FFmpeg (用于视频处理)
- Nginx 1.28.0+

### 2. 配置要求
- Redis需要正确配置
- FFmpeg需要安装并配置到PATH
- 文件上传目录需要写权限
- 定时任务需要配置到crontab

### 3. 安全注意事项
- 可灵AI密钥需要加密存储
- API接口需要添加权限验证
- 文件上传需要类型和大小验证
- 支付回调需要签名验证

### 4. 性能优化建议
- 使用Redis缓存热点数据
- 数据库查询添加索引
- 大文件上传使用分片上传
- 视频处理使用异步队列
- 定时任务控制并发数

### 5. 监控建议
- 监控Redis队列长度
- 监控可灵AI调用成功率
- 监控任务处理时间
- 监控订单支付成功率
- 监控系统资源使用情况

## 验收标准

### 功能验收
- [ ] 商家可以配置多个可灵AI账号
- [ ] 监控程序可以自动上传新文件
- [ ] 上传失败可以重试5次
- [ ] AI任务可以正常创建和查询状态
- [ ] 预览图可以正常生成
- [ ] 游客可以扫码授权并查看作品
- [ ] 游客可以选择作品并支付
- [ ] 支付后作品自动绑定到相册
- [ ] 支付后立即发送通知
- [ ] 未支付订单30分钟自动取消
- [ ] 浏览记录可以正常查询(30天)

### 性能验收
- [ ] 日处理量达到1000个视频/商家
- [ ] 响应时间<5秒
- [ ] 系统稳定运行无崩溃

### 代码质量验收
- [ ] 所有PHP文件语法检查通过
- [ ] 代码符合系统规范
- [ ] 添加了完整的函数级注释
- [ ] 添加了完整的类级注释
- [ ] 使用了原系统的数据库连接
- [ ] 使用了原系统的上传功能
- [ ] 使用了原系统的文件管理功能
- [ ] 使用了原系统的日志管理功能
- [ ] 使用了原系统的用户管理功能
- [ ] 使用了原系统的角色管理功能
- [ ] 使用了原系统的权限管理功能
- [ ] 使用了原系统的菜单管理功能
- [ ] 使用了原系统的配置管理功能
- [ ] 使用了原系统的缓存管理功能
- [ ] 使用了原系统的队列管理功能
- [ ] 使用了原系统的任务管理功能
- [ ] 使用了原系统的短信管理功能
- [ ] 使用了原系统的邮件管理功能
- [ ] 使用了原系统的第三方支付功能

## 总结

AI旅拍功能的核心后端代码已经全部完成,包括:
1. 数据库表结构设计
2. 可灵AI接口封装
3. 任务管理和队列处理
4. 作品生成和管理
5. 商家监控程序
6. 游客端API接口
7. 商家后台管理接口
8. 定时任务处理

所有代码都遵循了系统的开发规范,使用了原系统的功能,并添加了完整的中文注释。

下一步需要完成前端页面开发、路由配置、支付集成和测试工作。

---

**文档版本**: v1.0
**创建日期**: 2026-01-19
**作者**: AI旅拍开发团队
