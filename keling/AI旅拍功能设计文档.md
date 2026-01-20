# AI旅拍功能设计文档

## 概述

AI旅拍是一款面向旅游景区和摄影商家的智能化AI视频生成平台。系统通过监控商家本地文件夹中的主体图片,自动调用可灵AI大模型生成多样化的创意图片和视频,并通过二维码支付的方式为游客提供个性化的旅拍作品。

### 核心价值
- 为传统旅拍业务注入AI技术,实现从单一拍照到多元化创意视频的升级
- 降低商家运营成本,提升游客体验和满意度
- 全流程自动化,商家无需人工干预

### MVP范围
- 素材自动上传(本地监控)
- AI视频/图片生成
- 游客扫码选片
- 支付获取作品
- 作品自动保存到相册

---

## 需求分析

### 用户故事

**作为商家**
- 我想要配置可灵AI账号和参数,以便控制生成质量和成本
- 我想要监控本地文件夹自动上传素材,以便省去手动上传的麻烦
- 我想要自定义提示词模板,以便生成符合景区特色的视频
- 我想要查看订单和收益统计,以便了解业务情况

**作为游客**
- 我想要扫码查看AI生成的作品,以便快速了解效果
- 我想要选择喜欢的作品并支付,以便获得高质量旅拍视频
- 我想要支付后自动保存到相册,以便方便查看和分享

### 功能需求

#### 商家端
- [ ] 可灵AI账号管理(支持多账号负载均衡)
- [ ] 本地文件夹监控(支持多目录,仅Windows)
- [ ] 素材上传管理(自动上传,失败重试5次)
- [ ] 提示词模板管理(自定义+系统预设)
- [ ] 作品价格设置
- [ ] 订单管理和统计
- [ ] 浏览记录查看(30天)

#### 游客端(H5)
- [ ] 微信扫码获取游客ID
- [ ] 授权微信信息
- [ ] 查看AI生成的作品预览图(带二维码)
- [ ] 选择作品并支付(微信/支付宝/余额)
- [ ] 支付后自动绑定到相册
- [ ] 浏览记录查询(30天)

#### 系统端
- [ ] 可灵AI接口封装
- [ ] 任务队列管理(Redis)
- [ ] 定时任务轮询任务状态
- [ ] 预览图生成(视频第一帧+二维码)
- [ ] 支付回调处理
- [ ] 订单自动取消(30分钟未支付)
- [ ] 支付后通知发送

### 非功能需求

**性能要求**
- 日处理量: 每个商家1000个视频/天
- 响应时间: 游客扫码到获得作品 < 5秒
- 并发处理: 支持多商家同时操作

**安全要求**
- 可灵AI密钥加密存储
- 游客信息授权获取
- 支付安全验证
- 防止恶意调用

**可维护性要求**
- 代码遵循系统规范
- 完整的日志记录
- 错误处理和重试机制
- 易于扩展新功能

---

## 设计方案

### 架构设计

```
┌─────────────────────────────────────────────────────────────────┐
│                        商家端                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │监控程序      │  │后台管理界面  │  │素材上传模块  │ │
│  │(Windows CLI) │  │(H5/Web)     │  │(PHP)        │ │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘ │
│         │                  │                  │           │
└─────────┼──────────────────┼──────────────────┼───────────┘
          │                  │                  │
          ▼                  ▼                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                      后端服务层                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │任务队列      │  │定时任务      │  │API接口层    │ │
│  │(Redis)      │  │(Cron)       │  │(ThinkPHP)   │ │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘ │
│         │                  │                  │           │
│         └──────────────────┼──────────────────┘           │
│                            ▼                            │
│  ┌──────────────────────────────────────────────┐          │
│  │      可灵AI接口封装类                   │          │
│  │  (KlingAIService.php)                 │          │
│  └──────────────┬───────────────────────────┘          │
└─────────────────┼──────────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                   可灵AI服务                              │
│  https://api-beijing.klingai.com                         │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      游客端(H5)                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐ │
│  │扫码授权      │  │选片支付      │  │相册查看      │ │
│  │(微信OAuth)   │  │(支付接口)    │  │(作品列表)    │ │
│  └──────────────┘  └──────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### 组件划分和职责

#### 1. 商家本地监控程序 (AivideoMonitor.php)
**职责**:
- 监控本地文件夹变化(inotify或轮询)
- 检测新文件并上传到服务器
- 上传失败重试(最多5次)
- 记录上传日志

**技术**:
- PHP CLI脚本
- 文件系统监控(Windows)
- HTTP上传到服务器API

#### 2. 可灵AI接口封装类 (KlingAIService.php)
**职责**:
- JWT Token生成和刷新
- 图生视频接口调用
- 文生视频接口调用
- 视频特效接口调用
- 任务状态查询
- 多账号负载均衡

**技术**:
- JWT鉴权
- cURL HTTP请求
- 错误处理和重试

#### 3. 任务队列管理 (AivideoQueue.php)
**职责**:
- 将AI生成任务加入Redis队列
- 从队列获取任务并执行
- 控制并发数量
- 任务失败重试

**技术**:
- Redis队列
- 异步任务处理

#### 4. 定时任务 (AivideoCron.php)
**职责**:
- 轮询可灵AI任务状态
- 任务完成后生成预览图和二维码
- 自动取消超时未支付订单(30分钟)
- 清理过期浏览记录(30天)

**技术**:
- ThinkPHP定时任务
- 定时轮询

#### 5. 游客端API (ApiAivideo.php)
**职责**:
- 微信扫码授权
- 作品列表查询
- 作品详情查看
- 创建订单
- 支付回调处理
- 相册管理
- 浏览记录查询

**技术**:
- ThinkPHP控制器
- 微信OAuth授权
- 支付接口集成

#### 6. 商家后台管理 (AdminAivideo.php)
**职责**:
- 可灵AI账号管理
- 提示词模板管理
- 素材管理
- 作品管理
- 订单管理
- 统计报表

**技术**:
- ThinkPHP控制器
- 管理界面

### 数据流

#### 素材上传流程
```
商家监控程序 → 检测新文件 → 上传到服务器 → 创建素材记录
                                                        ↓
                                              加入AI生成任务队列
                                                        ↓
                                              调用可灵AI接口
                                                        ↓
                                              轮询任务状态
                                                        ↓
                                              生成预览图+二维码
                                                        ↓
                                              创建作品记录
```

#### 游客选片支付流程
```
游客扫码 → 微信授权 → 获取游客ID → 查看作品列表
                                        ↓
                              选择作品 → 创建订单 → 发起支付
                                        ↓
                              支付成功 → 绑定到相册 → 发送通知
```

### 接口设计

#### 商家监控程序接口

**上传素材**
```
POST /api/aivideo/upload_material
参数:
  - bid: 商家ID
  - file: 文件
  - upload_type: manual/auto
  - upload_source: 来源
返回:
  - code: 状态码
  - msg: 消息
  - data: {
      material_id: 素材ID,
      material_url: 素材URL
    }
```

#### 游客端API接口

**微信授权**
```
GET /api/aivideo/wechat_auth
参数:
  - aid: 应用ID
  - code: 微信授权码
返回:
  - code: 状态码
  - msg: 消息
  - data: {
      mid: 会员ID,
      openid: 微信OpenID,
      access_token: 访问令牌
    }
```

**获取作品列表**
```
GET /api/aivideo/work_list
参数:
  - aid: 应用ID
  - mid: 会员ID
  - page: 页码
  - limit: 每页数量
返回:
  - code: 状态码
  - msg: 消息
  - data: {
      list: [
        {
          work_id: 作品ID,
          work_name: 作品名称,
          work_url: 作品URL,
          thumbnail_url: 预览图URL(带二维码),
          price: 价格,
          is_paid: 是否已支付
        }
      ],
      total: 总数
    }
```

**创建订单**
```
POST /api/aivideo/create_order
参数:
  - aid: 应用ID
  - mid: 会员ID
  - work_ids: 作品ID列表(逗号分隔)
返回:
  - code: 状态码
  - msg: 消息
  - data: {
      order_id: 订单ID,
      ordernum: 订单号,
      total_price: 总金额,
      pay_params: 支付参数
    }
```

**支付回调**
```
POST /api/aivideo/pay_callback
参数:
  - aid: 应用ID
  - ordernum: 订单号
  - pay_type: 支付方式
  - transaction_id: 第三方订单号
返回:
  - code: 状态码
  - msg: 消息
```

---

## 技术选型

### 后端框架
- **ThinkPHP 6.x**: 系统现有框架,保持一致性

### 数据库
- **MySQL 8.0.36**: 系统现有数据库

### 缓存和队列
- **Redis 8.0**: 任务队列、缓存、会话管理

### 文件监控
- **PHP inotify扩展**: Linux环境文件监控
- **PHP轮询**: Windows环境文件监控(兼容性更好)

### 二维码生成
- **endroid/qrcode**: 系统已安装,生成二维码图片

### 视频处理
- **FFmpeg**: 提取视频第一帧作为预览图

### HTTP请求
- **cURL**: 调用可灵AI接口

### JWT鉴权
- **firebase/php-jwt**: 生成JWT Token

---

## 实现细节

### 关键算法和逻辑

#### 1. 可灵AI多账号负载均衡
```php
// 轮询策略
function getNextKlingAccount($bid) {
    $accounts = getKlingAccounts($bid);
    $lastUsed = getLastUsedAccount($bid);
    $nextIndex = ($lastUsed['index'] + 1) % count($accounts);
    return $accounts[$nextIndex];
}
```

#### 2. 任务状态轮询
```php
// 定时任务轮询
function pollTaskStatus() {
    $tasks = getProcessingTasks();
    foreach ($tasks as $task) {
        $status = queryKlingTaskStatus($task['kling_task_id']);
        if ($status == 'succeed') {
            handleTaskSuccess($task);
        } elseif ($status == 'failed') {
            handleTaskFailed($task);
        }
    }
}
```

#### 3. 预览图生成
```php
// 提取视频第一帧
function generateThumbnail($videoUrl) {
    $ffmpeg = new FFMpeg($videoUrl);
    $frame = $ffmpeg->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(0));
    $thumbnailPath = $frame->save($outputPath);
    return $thumbnailPath;
}

// 添加二维码
function addQrcodeToThumbnail($thumbnailPath, $qrcodeUrl) {
    $image = imagecreatefromjpeg($thumbnailPath);
    $qrcode = imagecreatefrompng($qrcodeUrl);
    imagecopy($image, $qrcode, $x, $y, 0, 0, $width, $height);
    imagejpeg($image, $thumbnailPath);
    return $thumbnailPath;
}
```

#### 4. 订单自动取消
```php
// 定时任务取消超时订单
function cancelExpiredOrders() {
    $expiredTime = time() - 1800; // 30分钟
    $orders = getUnpaidOrders($expiredTime);
    foreach ($orders as $order) {
        updateOrderStatus($order['id'], 'cancelled');
        releaseWorks($order['work_ids']);
    }
}
```

### 数据模型

已设计8个核心表:
1. `ddwx_aivideo_merchant_config` - 商家配置表
2. `ddwx_aivideo_material` - 素材表
3. `ddwx_aivideo_task` - 任务表
4. `ddwx_aivideo_work` - 作品表
5. `ddwx_aivideo_order` - 订单表
6. `ddwx_aivideo_selection` - 选片记录表
7. `ddwx_aivideo_prompt_template` - 提示词模板表

详见 `/www/wwwroot/eivie/keling/aivideo_tables.sql`

---

## 实施计划

### 阶段1: 基础设施搭建 (预计2天)
- [ ] 创建数据库表
- [ ] 安装依赖包(firebase/php-jwt, php-ffmpeg等)
- [ ] 创建目录结构
- [ ] 配置Redis队列

### 阶段2: 可灵AI接口封装 (预计2天)
- [ ] 创建KlingAIService类
- [ ] 实现JWT Token生成
- [ ] 实现图生视频接口
- [ ] 实现文生视频接口
- [ ] 实现视频特效接口
- [ ] 实现任务状态查询
- [ ] 实现多账号负载均衡

### 阶段3: 商家监控程序 (预计3天)
- [ ] 开发Windows监控程序
- [ ] 实现文件上传接口
- [ ] 实现失败重试机制
- [ ] 测试和优化

### 阶段4: 任务队列和定时任务 (预计2天)
- [ ] 创建任务队列
- [ ] 实现任务消费逻辑
- [ ] 实现定时任务轮询
- [ ] 实现预览图生成
- [ ] 实现订单自动取消

### 阶段5: 游客端API (预计3天)
- [ ] 实现微信授权
- [ ] 实现作品列表查询
- [ ] 实现订单创建
- [ ] 集成支付接口
- [ ] 实现支付回调
- [ ] 实现相册管理
- [ ] 实现浏览记录查询

### 阶段6: 商家后台管理 (预计2天)
- [ ] 实现账号管理
- [ ] 实现提示词模板管理
- [ ] 实现素材管理
- [ ] 实现作品管理
- [ ] 实现订单管理
- [ ] 实现统计报表

### 阶段7: 测试和优化 (预计2天)
- [ ] 单元测试
- [ ] 集成测试
- [ ] 性能测试
- [ ] 压力测试
- [ ] Bug修复

**总计**: 约16个工作日

---

## 验收标准

- [ ] 商家可以配置多个可灵AI账号并正常调用
- [ ] 监控程序可以自动上传新文件(支持多目录)
- [ ] 上传失败可以重试5次
- [ ] AI任务可以正常创建和查询状态
- [ ] 预览图可以正常生成(视频第一帧+二维码)
- [ ] 游客可以扫码授权并查看作品
- [ ] 游客可以选择作品并支付(微信/支付宝/余额)
- [ ] 支付后作品自动绑定到相册
- [ ] 支付后立即发送通知
- [ ] 未支付订单30分钟自动取消
- [ ] 浏览记录可以正常查询(30天)
- [ ] 日处理量达到1000个视频/商家
- [ ] 响应时间<5秒
- [ ] 系统稳定运行无崩溃

---

## 风险评估

| 风险 | 影响 | 概率 | 缓解措施 |
|------|------|------|----------|
| 可灵AI接口不稳定 | 高 | 中 | 1. 多账号负载均衡<br>2. 失败重试机制<br>3. 队列缓冲 |
| 可灵AI并发限制 | 高 | 高 | 1. 控制队列并发数<br>2. 监控并发使用量<br>3. 提前预警 |
| 监控程序兼容性问题 | 中 | 中 | 1. 充分测试Windows环境<br>2. 提供详细安装文档<br>3. 技术支持 |
| 支付回调失败 | 高 | 低 | 1. 定时任务主动查询<br>2. 重复回调处理<br>3. 日志记录 |
| 视频文件过大 | 中 | 中 | 1. 文件大小限制<br>2. 压缩处理<br>3. CDN加速 |
| 游客授权失败 | 中 | 低 | 1. 友好错误提示<br>2. 重试机制<br>3. 备用方案 |
| 数据库性能瓶颈 | 中 | 低 | 1. 索引优化<br>2. 读写分离<br>3. 缓存优化 |
| Redis队列积压 | 高 | 中 | 1. 监控队列长度<br>2. 动态扩容<br>3. 优先级队列 |

---

## 参考资料

- [可灵AI使用指南](/www/wwwroot/eivie/keling/可灵 AI 使用指南.md)
- [可灵API接口鉴权及限速说明](/www/wwwroot/eivie/keling/通用信息：可灵 API 接口鉴权及限速说明.md)
- [ThinkPHP 6.x文档](https://www.kancloud.cn/manual/thinkphp6_0)
- [endroid/qrcode文档](https://github.com/endroid/qr-code)
- [Redis文档](https://redis.io/documentation)
- [JWT RFC 7519](https://tools.ietf.org/html/rfc7519)

---

## 附录

### 文件目录结构

```
/www/wwwroot/eivie/
├── app/
│   ├── common/
│   │   └── Aivideo.php              # AI旅拍公共类
│   ├── controller/
│   │   ├── ApiAivideo.php          # 游客端API控制器
│   │   └── AdminAivideo.php        # 商家后台控制器
│   ├── model/
│   │   ├── AivideoMerchantConfig.php
│   │   ├── AivideoMaterial.php
│   │   ├── AivideoTask.php
│   │   ├── AivideoWork.php
│   │   ├── AivideoOrder.php
│   │   ├── AivideoSelection.php
│   │   └── AivideoPromptTemplate.php
│   ├── service/
│   │   ├── KlingAIService.php       # 可灵AI服务类
│   │   └── AivideoQueue.php        # 队列服务类
│   ├── command/
│   │   └── AivideoCron.php        # 定时任务
│   └── monitor/
│       └── AivideoMonitor.php      # 商家监控程序
├── keling/
│   └── aivideo_tables.sql         # 数据库表结构
└── upload/
    └── aivideo/                  # AI旅拍文件目录
        ├── material/               # 素材文件
        ├── work/                  # 作品文件
        ├── thumbnail/             # 预览图
        └── qrcode/               # 二维码
```

### 配置项

```php
// config/aivideo.php
return [
    // 可灵AI配置
    'kling' => [
        'api_url' => 'https://api-beijing.klingai.com',
        'token_expire' => 1800, // Token过期时间(秒)
        'max_retry' => 5, // 最大重试次数
    ],

    // 队列配置
    'queue' => [
        'prefix' => 'aivideo:',
        'max_concurrent' => 10, // 最大并发数
    ],

    // 订单配置
    'order' => [
        'expire_time' => 1800, // 订单过期时间(秒)
    ],

    // 浏览记录配置
    'browse' => [
        'expire_days' => 30, // 浏览记录保留天数
    ],

    // 文件上传配置
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'mp4'],
    ],
];
```

---

**文档版本**: v1.0
**创建日期**: 2026-01-19
**最后更新**: 2026-01-19
**作者**: AI旅拍开发团队
