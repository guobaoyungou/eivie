# 需求设计文档：重复扫码直接跳转选片页

## 1. 需求描述

**现状问题**：用户重复扫同一门店二维码时，每次都会进入自拍拍照页面，体验不友好。

**目标**：当用户重复扫同一门店二维码时，若该openid在该门店已有合成完成的成片，直接跳转到付费选片列表，选片列表展示该openid关联的**所有人像的成片**。

---

## 2. 现有流程分析

### 2.1 扫码完整链路

```
用户扫门店QR码
    │
    ▼
微信事件回调 (ApiWechat.php)
    ├── 解析 selfie_bid_{bid}_mdid_{mdid}
    ├── 注册/更新会员
    └── 调用 handleScanEvent() → 被动回复图文消息
                                    │
                                    ▼ 推文链接始终指向 ↓
                          /public/selfie/index.html?aid=X&bid=Y&mdid=Z
                                    │
                                    ▼ 用户点击推文
                          selfie 前端 init() → 调用 selfie/index API
                                    │
                          ┌─────────┴─────────┐
                          │                   │
                    无 session             有 session
                    → OAuth 授权         → 返回门店信息
                    → oauth_callback    → 启动摄像头
                    → 重定向到自拍页      → 开始拍照
                          │                   │
                          └─────────┬─────────┘
                                    ▼
                          用户拍照 → capture API → 比对/合成
```

### 2.2 关键发现

| 环节 | 文件 | 问题 |
|------|------|------|
| 微信扫码推文 | `AiTravelPhotoSelfieService::handleScanEvent()` | URL 始终指向自拍页，不区分新老用户 |
| OAuth回调 | `AiTravelPhotoSelfie::oauth_callback()` | 始终重定向到自拍页 |
| 自拍页初始化 | `selfie/index.html init()` | 始终启动摄像头，无跳转检测 |
| selfie/index API | `AiTravelPhotoSelfie::index()` | 不返回用户已有成片信息 |

### 2.3 Session隔离情况

- 自拍页使用 `selfie_openid` (Session)
- 选片页使用 `pick_openid` (Session)
- 两者在同一域名 `ai.eivie.cn`，**共享同一PHP Session**

### 2.4 选片页(pick)数据流

```
pick/index.html?qr=XXXX
    │
    ├── 调用 pick/index API（传 qr）→ 获取单个 portrait_id
    ├── 调用 pick/results API（传 portrait_id）→ 获取该人像成片 + 相似人像成片
    ├── 用户选片 → 调用 pick/recommend → 套餐推荐
    └── 下单 → pick/order（传 portrait_id + result_ids）→ 创建订单
```

**限制**：现有 pick 页面基于**单 portrait_id** 运作。需扩展支持**多 portrait 聚合**模式。

---

## 3. 技术方案

### 3.1 总体设计

```
用户扫门店QR码（重复）
    │
    ▼
微信事件回调 → handleScanEvent()
    ├── 查询 openid 在该门店是否有已合成成片
    ├── ✅ 有成片 → 推文链接指向 pick 页（门店模式）
    └── ❌ 无成片 → 推文链接指向 selfie 页（原逻辑）
    │
    ▼ 用户点击推文
    │
    ├── 有成片 → pick/index.html?mode=store&bid=X&mdid=Y
    │       └── OAuth → pick/store_index API → pick/store_results API → 展示所有成片
    │
    └── 无成片 → selfie/index.html?aid=X&bid=Y&mdid=Z
            └── OAuth → 有成片？ → 重定向 pick / 无成片 → 启动摄像头
```

### 3.2 改动清单（6个触控点）

#### ✏️ 触控点1：后端 Service - 新增门店成片聚合方法

**文件**：`app/service/AiTravelPhotoPickService.php`

**新增方法**：`getStoreResultListByOpenid(int $bid, int $mdid, string $openid): array`

```php
功能：
- 查询 openid 在 bid+mdid 下所有 synthesis_status=3 的 portrait
- 聚合所有 portrait 的 AiTravelPhotoResult
- 返回格式与 getResultList() 一致：
  {
    portrait_ids: [id1, id2, ...],   // 新增：多portrait
    results: [...],
    total: int,
    similar_results: [],              // 门店模式不需要相似推荐
    similar_total: 0
  }
```

#### ✏️ 触控点2：后端 Pick 控制器 - 新增门店模式 API

**文件**：`app/controller/api/AiTravelPhotoPick.php`

**新增方法**：

| 方法 | 路径 | 功能 |
|------|------|------|
| `store_index()` | `GET /api/ai_travel_photo/pick/store_index?bid=X&mdid=Y` | 门店选片入口，返回门店信息+portrait_ids |
| `store_results()` | `GET /api/ai_travel_photo/pick/store_results?bid=X&mdid=Y` | 返回所有成片列表 |

**修改方法**：

| 方法 | 改动 |
|------|------|
| `order()` | 兼容 `portrait_ids[]` 参数（门店模式传多个portrait） |

Session：使用 `pick_openid`，门店模式走 pick 自己的 OAuth。

#### ✏️ 触控点3：后端 Selfie OAuth回调 - 增加重定向检测

**文件**：`app/controller/api/AiTravelPhotoSelfie.php`

**修改方法**：`oauth_callback()`

```php
获取 openid 后 → 检查门店已有成片：
  ✅ 有 → Session::set('pick_openid', $openid) → redirect pick 门店模式
  ❌ 无 → redirect selfie 页（原逻辑）
```

#### ✏️ 触控点4：后端 Selfie index API - 增加重定向标识

**文件**：`app/controller/api/AiTravelPhotoSelfie.php`

**修改方法**：`index()`

在返回 200 响应前检查门店已有成片，若有则在 data 中增加：
```json
{
  "has_completed": true,
  "store_pick_url": "/public/pick/index.html?mode=store&bid=X&mdid=Y"
}
```

#### ✏️ 触控点5：后端 微信扫码事件 - 智能推文URL

**文件**：`app/service/AiTravelPhotoSelfieService.php`

**修改方法**：`handleScanEvent()`

```php
检查 openid 在门店是否已有成片：
  ✅ 有 → 推文 URL 指向 pick 门店模式 + 修改推文标题/描述
  ❌ 无 → 推文 URL 指向 selfie 页（原逻辑）
```

#### ✏️ 触控点6：前端修改

**文件 A**：`public/selfie/index.html`

在 `init()` 中，收到 index API 200 响应后：
```javascript
if (res.data.has_completed && res.data.store_pick_url) {
  window.location.href = res.data.store_pick_url;
  return;
}
// 原有逻辑：启动摄像头
```

**文件 B**：`public/pick/index.html`

支持 `mode=store` URL 参数：
```javascript
// 初始化时检查 mode
var mode = params.get('mode');
if (mode === 'store') {
  // 门店模式：调用 store_index 和 store_results API
  APP.storeMode = true;
  APP.bid = parseInt(params.get('bid')) || 0;
  APP.mdid = parseInt(params.get('mdid')) || 0;
  fetchApi('store_index', {bid: APP.bid, mdid: APP.mdid}, 'GET', callback);
} else {
  // 原有逻辑：qr 模式
}
```

下单时：
```javascript
// 门店模式传 portrait_ids 而非 portrait_id
if (APP.storeMode) {
  orderData.portrait_ids = APP.portraitIds;
} else {
  orderData.portrait_id = APP.portraitId;
}
```

---

## 4. Session 处理策略

| 场景 | 处理 |
|------|------|
| 用户从自拍页重定向到选片页 | selfie `oauth_callback` 中同时设置 `pick_openid` |
| 用户直接从推文进入选片页 | pick 页自己的 OAuth 流程处理 |
| 用户已有 pick_openid session | 直接使用，无需再次 OAuth |

关键：在 selfie `oauth_callback` 中增加一行：
```php
Session::set('pick_openid', $openid);  // 桥接 selfie → pick session
```

---

## 5. pick/index.html OAuth 扩展（门店模式）

现有 pick OAuth 使用 `state=qr_XXXX` 格式，重定向回 `?qr=XXXX`。

门店模式需要扩展：
- `start_oauth` 接受 `mode=store&bid=X&mdid=Y` 参数
- `state` 格式：`store_{bid}_{mdid}`
- `oauth_callback` 解析 store state，重定向到 `?mode=store&bid=X&mdid=Y`

---

## 6. 订单兼容方案

`createPickOrder` 验证 result 归属时的 `allowedPortraitIds` 构建逻辑：

```php
// 门店模式：portrait_ids 由前端传入（该 openid 在门店的所有 portrait）
if (!empty($data['portrait_ids'])) {
    $allowedPortraitIds = array_map('intval', $data['portrait_ids']);
} else {
    // 原有逻辑：单 portrait_id + 相似人像
    $allowedPortraitIds = [$portraitId];
    $similarIds = $this->findSimilarPortraitIds(...);
    $allowedPortraitIds = array_merge($allowedPortraitIds, $similarIds);
}
```

---

## 7. 涉及文件汇总

| 文件 | 操作 | 说明 |
|------|------|------|
| `app/service/AiTravelPhotoPickService.php` | 新增方法 | `getStoreResultListByOpenid()` |
| `app/controller/api/AiTravelPhotoPick.php` | 新增+修改 | `store_index()`, `store_results()`, `start_oauth()`, `oauth_callback()`, `order()` |
| `app/controller/api/AiTravelPhotoSelfie.php` | 修改 | `index()`, `oauth_callback()` |
| `app/service/AiTravelPhotoSelfieService.php` | 修改 | `handleScanEvent()` |
| `public/selfie/index.html` | 修改 | init 自动重定向 |
| `public/pick/index.html` | 修改 | 门店模式支持 |

---

## 8. 新老用户流程对照

### 新用户（首次扫码）

```
扫码 → handleScanEvent → 无成片 → 推文指向 selfie
→ selfie 页 → OAuth → 拍照 → capture → 合成 → 推送选片链接
```

### 老用户（重复扫码）

```
扫码 → handleScanEvent → 有成片 → 推文指向 pick (mode=store)
→ pick 页 → OAuth(如需) → 展示所有成片 → 选片/购买
```

### 老用户（通过旧链接进入 selfie）

```
selfie 页 → init → selfie/index API → 检测有成片 → 自动跳转 pick
```

---

## 9. 边界情况

| 场景 | 处理 |
|------|------|
| 用户有人像但合成全部失败 (synthesis_status=4) | 不算有成片，走自拍流程 |
| 用户有人像正在合成中 (synthesis_status=0/2) | 不算有成片，走自拍流程（capture 防重兜底） |
| 用户有多个 portrait，部分有成片部分无 | 只展示有成片的 portrait 的结果 |
| 门店被禁用自拍端 | handleScanEvent 返回 false，不推送任何消息 |
