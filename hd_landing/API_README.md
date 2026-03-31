# 大屏互动系统多租户 API 文档

> 部署域名: `hd.eivie.cn`  
> API 基础路径: `https://hd.eivie.cn/api/hd/`  
> 认证方式: Session 或 Header `Hd-Token`

---

## 目录
1. [认证 API](#1-认证-api)
2. [门店管理 API](#2-门店管理-api)
3. [活动管理 API](#3-活动管理-api)
4. [大屏互动 API](#4-大屏互动-api)
5. [文件上传 API](#5-文件上传-api)
6. [微信 JS-SDK API](#6-微信-js-sdk-api)
7. [密码重置 API](#7-密码重置-api)
8. [数据导出 API](#8-数据导出-api)
9. [平台超管 API](#9-平台超管-api)

---

## 通用说明

### 请求头
| 名称 | 说明 |
|------|------|
| `Hd-Token` | 登录后返回的 Token，用于 API 认证 |
| `Content-Type` | `application/x-www-form-urlencoded` 或 `multipart/form-data` |

### 响应格式
```json
{
    "code": 0,       // 0=成功, 1=业务错误, 401=未登录, 403=无权限
    "msg": "success",
    "data": {}
}
```

---

## 1. 认证 API

### 1.1 商家注册
```
POST /api/hd/auth/register
```
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | ✅ | 商家名称 |
| phone | string | ✅ | 手机号（11位） |
| password | string | ✅ | 密码（≥6位） |
| contact_name | string | | 联系人姓名 |

**响应**:
```json
{
    "code": 0,
    "msg": "注册成功",
    "data": {
        "token": "xxx",
        "user_id": 1,
        "bid": 1,
        "name": "商家名"
    }
}
```

### 1.2 商家登录
```
POST /api/hd/auth/login
```
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| username | string | ✅ | 用户名或手机号 |
| password | string | ✅ | 密码 |

### 1.3 退出登录
```
POST /api/hd/auth/logout
```

### 1.4 获取账户信息
```
GET /api/hd/auth/profile
```
**需要认证**: ✅

### 1.5 更新账户信息
```
PUT /api/hd/auth/profile
```
| 参数 | 类型 | 说明 |
|------|------|------|
| name | string | 商家名称 |
| tel | string | 手机号 |
| password | string | 新密码 |
| logo | string | Logo URL |
| contact_name | string | 联系人 |
| wxfw_appid | string | 公众号 AppID |
| wxfw_appsecret | string | 公众号 AppSecret |

---

## 2. 门店管理 API

**需要认证**: ✅ | **需要套餐**: ✅

### 2.1 门店列表
```
GET /api/hd/stores?keyword=xxx&page=1&limit=20
```

### 2.2 创建门店
```
POST /api/hd/stores
```
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| name | string | ✅ | 门店名称 |
| address | string | | 地址 |
| tel | string | | 电话 |
| lng | string | | 经度 |
| lat | string | | 纬度 |

### 2.3 门店详情
```
GET /api/hd/stores/:id
```

### 2.4 更新门店
```
PUT /api/hd/stores/:id
```

### 2.5 删除门店
```
DELETE /api/hd/stores/:id
```

---

## 3. 活动管理 API

**需要认证**: ✅ | **需要套餐**: ✅

### 3.1 活动列表
```
GET /api/hd/activities?keyword=xxx&status=1&mdid=1&page=1&limit=20
```
| 参数 | 说明 |
|------|------|
| status | 1=未开始 2=进行中 3=已结束 |

### 3.2 创建活动
```
POST /api/hd/activities
```
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| title | string | ✅ | 活动标题 |
| mdid | int | | 门店ID |
| started_at | string/int | | 开始时间 |
| ended_at | string/int | | 结束时间 |
| verifycode | string | | 签到验证码 |
| screen_config | object | | 大屏配置 |

### 3.3 活动详情
```
GET /api/hd/activities/:id
```

### 3.4 更新活动
```
PUT /api/hd/activities/:id
```

### 3.5 删除活动
```
DELETE /api/hd/activities/:id
```

### 3.6 切换活动状态
```
PUT /api/hd/activities/:id/status
```
| 参数 | 说明 |
|------|------|
| status | 1=未开始 2=进行中 3=已结束 |

### 3.7 功能配置列表
```
GET /api/hd/activities/:id/features
```

### 3.8 更新功能配置
```
PUT /api/hd/activities/:id/features/:code
```
| 参数 | 类型 | 说明 |
|------|------|------|
| enabled | int | 0=禁用 1=启用 |
| config | string/json | 功能配置 |
| sort | int | 排序 |

**功能代码(code)**: `qdq` 签到墙, `threedimensionalsign` 3D签到, `wall` 微信上墙, `danmu` 弹幕, `vote` 投票, `lottery` 大屏抽奖, `choujiang` 手机抽奖, `ydj` 摇大奖, `shake` 摇一摇竞技, `game` 互动游戏, `redpacket` 红包雨, `importlottery` 导入抽奖, `kaimu` 开幕墙, `bimu` 闭幕墙, `xiangce` 相册, `xyh` 幸运号码, `xysjh` 幸运手机号

### 3.9 参与者列表
```
GET /api/hd/activities/:id/participants?flag=2&page=1&limit=50
```
| 参数 | 说明 |
|------|------|
| flag | 1=未签到 2=已签到 |

### 3.10 活动统计
```
GET /api/hd/activities/:id/stats
```

### 3.11 克隆活动
```
POST /api/hd/activities/:id/clone
```

### 3.12 全部功能列表
```
GET /api/hd/features
```

---

## 4. 大屏互动 API

**无需商家登录**, 通过 `access_code` 访问

### 4.1 大屏配置
```
GET /api/hd/screen/:access_code/config
```

### 4.2 签到列表
```
GET /api/hd/screen/:access_code/sign-list?last_id=0
```

### 4.3 用户签到
```
POST /api/hd/screen/:access_code/sign
```
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| openid | string | ✅ | 用户标识 |
| nickname | string | | 昵称 |
| avatar | string | | 头像 |
| signname | string | | 签名 |

### 4.4 获取上墙消息
```
GET /api/hd/screen/:access_code/wall?last_id=0
```

### 4.5 发送上墙消息
```
POST /api/hd/screen/:access_code/wall
```
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| openid | string | ✅ | 用户标识 |
| nickname | string | | 昵称 |
| content | string | | 文字内容 |
| imgurl | string | | 图片URL |

### 4.6 执行抽奖
```
POST /api/hd/screen/:access_code/lottery/draw
```
| 参数 | 说明 |
|------|------|
| round_id | 抽奖轮次ID |

### 4.7 摇一摇状态
```
GET /api/hd/screen/:access_code/shake/status
```

### 4.8 提交摇一摇分数
```
POST /api/hd/screen/:access_code/shake/score
```

### 4.9 抢红包
```
POST /api/hd/screen/:access_code/redpacket/grab
```

### 4.10 投票
```
POST /api/hd/screen/:access_code/vote
```

### 4.11 获取弹幕
```
GET /api/hd/screen/:access_code/danmu?last_id=0
```

### 4.12 发送弹幕
```
POST /api/hd/screen/:access_code/danmu
```
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| content | string | ✅ | 弹幕内容（≤50字） |
| color | string | | 颜色（默认#ffffff） |
| openid | string | | 用户标识 |

### 4.13 活动入口页
```
GET /s/:access_code
```
自适应大屏/手机端，微信端自动触发 OAuth 授权

---

## 5. 文件上传 API

**需要认证**: ✅ | **需要套餐**: ✅

### 5.1 上传图片
```
POST /api/hd/upload/image
Content-Type: multipart/form-data
```
| 参数 | 说明 |
|------|------|
| file | 图片文件（≤5MB，jpg/png/gif/webp） |

### 5.2 上传背景图
```
POST /api/hd/upload/background
```
| 参数 | 说明 |
|------|------|
| file | 图片文件（≤10MB） |
| activity_id | 活动ID |
| scene | 场景（screen/mobile/sign/lottery） |

### 5.3 上传音乐
```
POST /api/hd/upload/music
```
| 参数 | 说明 |
|------|------|
| file | 音频文件（≤20MB，mp3/wav/ogg/m4a） |
| activity_id | 活动ID |
| title | 音乐标题 |

### 5.4 背景图列表
```
GET /api/hd/upload/backgrounds?activity_id=1&scene=screen
```

### 5.5 音乐列表
```
GET /api/hd/upload/musics?activity_id=1
```

### 5.6 删除背景图
```
DELETE /api/hd/upload/background/:id
```

### 5.7 删除音乐
```
DELETE /api/hd/upload/music/:id
```

---

## 6. 微信 JS-SDK API

### 6.1 获取 JS-SDK 签名配置
```
GET /api/hd/wx/jssdk?access_code=xxx&url=xxx
```
**响应**:
```json
{
    "code": 0,
    "data": {
        "appId": "wx...",
        "timestamp": 1234567890,
        "nonceStr": "xxx",
        "signature": "xxx"
    }
}
```
前端使用:
```javascript
wx.config({
    appId: data.appId,
    timestamp: data.timestamp,
    nonceStr: data.nonceStr,
    signature: data.signature,
    jsApiList: ['onMenuShareTimeline', 'onMenuShareAppMessage', 'scanQRCode']
});
```

---

## 7. 密码重置 API

### 7.1 发送重置验证码
```
POST /api/hd/password/send-code
```
| 参数 | 说明 |
|------|------|
| phone | 注册手机号 |

### 7.2 重置密码
```
POST /api/hd/password/reset
```
| 参数 | 说明 |
|------|------|
| phone | 手机号 |
| code | 验证码 |
| password | 新密码（≥6位） |

---

## 8. 数据导出 API

**需要认证**: ✅

### 8.1 导出参与者
```
GET /api/hd/export/participants/:activity_id
```
返回 CSV 文件下载

### 8.2 导出上墙消息
```
GET /api/hd/export/messages/:activity_id
```
返回 CSV 文件下载

### 8.3 导出中奖记录
```
GET /api/hd/export/lottery/:activity_id
```
返回 CSV 文件下载

---

## 9. 平台超管 API

**需要超管权限**: ✅

### 9.1 租户列表
```
GET /api/hd/admin/tenants?keyword=xxx&page=1&limit=20
```

### 9.2 启用/禁用租户
```
PUT /api/hd/admin/tenants/:id/status
```
| 参数 | 说明 |
|------|------|
| status | 1=启用 2=禁用 |

### 9.3 套餐列表
```
GET /api/hd/admin/plans
```

### 9.4 创建套餐
```
POST /api/hd/admin/plans
```

### 9.5 更新套餐
```
PUT /api/hd/admin/plans/:id
```

### 9.6 平台统计
```
GET /api/hd/admin/stats
```

---

## 系统架构

### 中间件链
```
请求 → HdCors → TenantResolver → HdAuthMiddleware → PlanPermission → 控制器
                                   ↕                    ↕
                              WeChatOAuth          ActivityStatus
                           (手机端OAuth)          (活动状态检查)
```

### 多租户数据隔离
- 平台(aid) → 商家(bid) → 门店(mdid) → 活动(activity_id)
- 所有业务表通过 `aid` + `bid` 组合实现租户级数据隔离

### 文件结构
```
app/
├── controller/hd/    # 控制器 (10个)
├── middleware/hd/    # 中间件 (7个)
├── model/hd/         # 模型 (36个)
├── service/hd/       # 服务 (5个)
route/hd.php          # 路由配置
hd_landing/           # 前端落地页
├── index.html        # 官网首页
├── register.html     # 注册页
├── login.html        # 登录页
├── admin/            # 管理后台
└── nginx_hd.conf     # Nginx配置
```
