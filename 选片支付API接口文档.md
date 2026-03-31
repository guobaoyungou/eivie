# AI旅拍选片支付API接口文档

> **版本**: v1.0  
> **更新时间**: 2026-03-27  
> **适用场景**: 微信H5选片支付 + 成片交付  
> **技术栈**: ThinkPHP 6.0 + 微信JSAPI支付

---

## 📋 目录

1. [业务流程](#业务流程)
2. [API接口列表](#API接口列表)
3. [接口详情](#接口详情)
4. [支付履约流程](#支付履约流程)
5. [成片交付机制](#成片交付机制)
6. [前端调用示例](#前端调用示例)
7. [常见问题](#常见问题)

---

## 业务流程

### 完整支付流程图

```
用户扫码 → 选片 → 创建订单 → 发起支付 → 支付成功 → 自动履约 → 下载成片
   ↓         ↓        ↓          ↓          ↓         ↓          ↓
 scan    pick.html  order      pay      微信收银台  notify    downloads
```

### 核心步骤

1. **用户扫码** - 扫描商家二维码，获取人像ID
2. **选片** - 浏览成片列表（缩略图+水印），勾选心仪照片
3. **创建订单** - 选择套餐或单张购买，生成订单号
4. **发起支付** - 调用微信JSAPI支付，拉起收银台
5. **支付成功** - 微信异步回调notify接口
6. **自动履约** - 更新订单状态，写入下载URL（无水印原图）
7. **下载成片** - 用户获取无水印原图，永久保存

---

## API接口列表

| 接口名称 | 接口地址 | HTTP方法 | 说明 | 调用方 |
|---------|---------|---------|------|--------|
| 创建订单 | `/api/ai-travel-photo/pick/order` | POST | 生成选片订单，返回订单号 | 前端H5 |
| 发起支付 | `/api/ai-travel-photo/pick/pay` | POST | 获取微信JSAPI支付参数 | 前端H5 |
| 支付状态查询 | `/api/ai-travel-photo/pick/pay_status` | GET | 轮询支付是否成功 | 前端H5 |
| 支付回调 | `/api/ai-travel-photo/pick/notify` | POST | 微信支付异步通知 | 微信服务器 |
| 下载列表 | `/api/ai-travel-photo/pick/downloads` | GET | 获取可下载的成片列表 | 前端H5 |
| 记录下载 | `/api/ai-travel-photo/pick/record_download` | POST | 记录用户下载行为 | 前端H5 |

---

## 接口详情

### 1. 创建订单

**接口地址**: `POST /api/ai-travel-photo/pick/order`

**功能说明**: 用户选片后创建订单，支持单张购买和套餐购买，返回订单号和应付金额。

**请求头**:
```http
Content-Type: application/json
Cookie: PHPSESSID=xxx (H5 Session，包含openid)
```

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| portrait_id | int | 是 | 人像ID | 123 |
| result_ids | array | 是 | 选中的成片ID数组 | [456, 457, 458] |
| package_id | int | 否 | 套餐ID（0表示单张购买） | 10 |
| aid | int | 是 | 应用ID | 1 |
| bid | int | 是 | 商家ID | 5 |
| qrcode_id | int | 是 | 二维码ID | 100 |

**请求示例**:
```json
{
  "portrait_id": 123,
  "result_ids": [456, 457, 458, 459, 460],
  "package_id": 10,
  "aid": 1,
  "bid": 5,
  "qrcode_id": 100
}
```

**响应示例（成功）**:
```json
{
  "code": 200,
  "msg": "订单创建成功",
  "data": {
    "order_no": "AI20260327153045123456",
    "total_price": 49.50,
    "actual_amount": 49.50,
    "package_name": "5张套餐",
    "selected_count": 5
  }
}
```

**响应示例（失败）**:
```json
{
  "code": 401,
  "msg": "未授权"
}
```

```json
{
  "code": 400,
  "msg": "部分成片不存在或无权访问"
}
```

**业务规则**:

1. **成片归属验证** - 支持当前人像 + 相似人像（≥95%相似度）的成片
2. **价格计算逻辑**:
   - 单张购买：`总价 = 单价 × 选片数`
   - 套餐购买（选片数≤套餐张数）：`总价 = 套餐价`
   - 套餐购买（选片数>套餐张数）：`总价 = 套餐价 + (选片数-套餐张数)×单价`
3. **库存扣减** - 套餐购买时扣除库存（如果设置了库存限制）

---

### 2. 发起支付

**接口地址**: `POST /api/ai-travel-photo/pick/pay`

**功能说明**: 创建微信JSAPI支付订单，返回前端调起支付所需的参数。

**请求头**:
```http
Content-Type: application/json
Cookie: PHPSESSID=xxx
```

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| order_no | string | 是 | 订单号 | AI20260327153045123456 |

**请求示例**:
```json
{
  "order_no": "AI20260327153045123456"
}
```

**响应示例（成功）**:
```json
{
  "code": 200,
  "msg": "支付参数获取成功",
  "data": {
    "payorder_id": 789,
    "order_no": "AI20260327153045123456",
    "js_api_params": {
      "appId": "wx1234567890abcdef",
      "timeStamp": "1711523456",
      "nonceStr": "5K8264ILTKCH16CQ2502SI8ZNMTM67VS",
      "package": "prepay_id=wx271534567890abcdef1234567890",
      "signType": "MD5",
      "paySign": "A380BEC7B4D2EB7E5F5E2C6D3B6E0C4A"
    }
  }
}
```

**响应示例（失败）**:
```json
{
  "code": 400,
  "msg": "订单号不能为空"
}
```

```json
{
  "code": 500,
  "msg": "订单状态异常，不可支付"
}
```

**前端调起支付代码**:
```javascript
// 获取支付参数
const response = await fetch('/api/ai-travel-photo/pick/pay', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ order_no: 'AI20260327153045123456' })
});
const result = await response.json();

if (result.code === 200) {
  const params = result.data.js_api_params;
  
  // 调起微信支付
  WeixinJSBridge.invoke('getBrandWCPayRequest', params, function(res) {
    if (res.err_msg === 'get_brand_wcpay_request:ok') {
      // 支付成功，轮询支付状态
      checkPayStatus(result.data.order_no);
    } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
      alert('支付已取消');
    } else {
      alert('支付失败，请重试');
    }
  });
}
```

---

### 3. 支付状态查询

**接口地址**: `GET /api/ai-travel-photo/pick/pay_status`

**功能说明**: 前端轮询查询订单支付状态，用于处理微信回调延迟的情况。

**注意**: 此接口不要求Session鉴权，因为从微信收银台返回后Session可能丢失，order_no本身具有足够的唯一性。

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| order_no | string | 是 | 订单号 | AI20260327153045123456 |

**请求示例**:
```http
GET /api/ai-travel-photo/pick/pay_status?order_no=AI20260327153045123456
```

**响应示例（已支付）**:
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "status": "paid",
    "pay_time": 1711523500,
    "order_no": "AI20260327153045123456"
  }
}
```

**响应示例（未支付）**:
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "status": "unpaid",
    "order_status": 1
  }
}
```

**响应示例（订单不存在）**:
```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "status": "not_found"
  }
}
```

**前端轮询示例**:
```javascript
function checkPayStatus(orderNo) {
  let count = 0;
  const maxCount = 20; // 最多轮询20次
  
  const timer = setInterval(async () => {
    count++;
    
    const response = await fetch(`/api/ai-travel-photo/pick/pay_status?order_no=${orderNo}`);
    const result = await response.json();
    
    if (result.code === 200 && result.data.status === 'paid') {
      clearInterval(timer);
      // 支付成功，跳转下载页
      location.href = `/public/pick/download.html?order_no=${orderNo}`;
    } else if (count >= maxCount) {
      clearInterval(timer);
      alert('支付状态查询超时，请稍后在"我的订单"中查看');
    }
  }, 2000); // 每2秒查询一次
}
```

**回退机制**: 如果数据库状态为未支付，接口会主动调用微信支付查询API，确保数据准确性（处理notify回调失败的情况）。

---

### 4. 支付回调（微信服务器调用）

**接口地址**: `POST /api/ai-travel-photo/pick/notify`

**功能说明**: 微信支付成功后的异步回调接口，触发订单履约（更新状态、写入下载URL、更新统计）。

**调用方**: 微信支付服务器

**请求格式**: XML

**请求示例**:
```xml
<xml>
  <return_code><![CDATA[SUCCESS]]></return_code>
  <result_code><![CDATA[SUCCESS]]></result_code>
  <appid><![CDATA[wx1234567890abcdef]]></appid>
  <mch_id><![CDATA[1234567890]]></mch_id>
  <nonce_str><![CDATA[5K8264ILTKCH16CQ2502SI8ZNMTM67VS]]></nonce_str>
  <out_trade_no><![CDATA[AI20260327153045123456]]></out_trade_no>
  <transaction_id><![CDATA[4200001234567890123456789012]]></transaction_id>
  <total_fee>4950</total_fee>
  <sign><![CDATA[A380BEC7B4D2EB7E5F5E2C6D3B6E0C4A]]></sign>
</xml>
```

**响应示例（成功）**:
```xml
<xml>
  <return_code><![CDATA[SUCCESS]]></return_code>
  <return_msg><![CDATA[OK]]></return_msg>
</xml>
```

**响应示例（失败）**:
```xml
<xml>
  <return_code><![CDATA[FAIL]]></return_code>
  <return_msg><![CDATA[签名验证失败]]></return_msg>
</xml>
```

**履约流程** (详见[支付履约流程](#支付履约流程)):

1. **验签** - 验证微信签名，防止伪造回调
2. **更新订单状态** - `status`: 1(未支付) → 2(已支付)
3. **写入下载URL** - 将无水印原图URL写入 `order_goods.download_url`
4. **写入用户相册** - 复制成片到 `user_album` 表
5. **更新统计数据** - 二维码统计、套餐销量统计等
6. **返回成功** - 告知微信服务器处理完成

---

### 5. 下载列表（成片交付核心接口）

**接口地址**: `GET /api/ai-travel-photo/pick/downloads`

**功能说明**: 支付成功后，获取可下载的成片列表（无水印原图URL）。

**请求头**:
```http
Cookie: PHPSESSID=xxx
```

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| order_no | string | 是 | 订单号 | AI20260327153045123456 |

**请求示例**:
```http
GET /api/ai-travel-photo/pick/downloads?order_no=AI20260327153045123456
```

**响应示例（成功）**:
```json
{
  "code": 200,
  "msg": "成功",
  "data": {
    "order_no": "AI20260327153045123456",
    "package_name": "5张套餐",
    "total": 5,
    "aid": 1,
    "bid": 5,
    "portrait_id": 123,
    "downloads": [
      {
        "id": 1,
        "result_id": 456,
        "type": 1,
        "download_url": "https://example.cos.ap-guangzhou.myqcloud.com/ai/result/456_original.jpg",
        "thumbnail_url": "https://example.cos.ap-guangzhou.myqcloud.com/ai/result/456_thumb.jpg",
        "is_downloaded": 0
      },
      {
        "id": 2,
        "result_id": 457,
        "type": 1,
        "download_url": "https://example.cos.ap-guangzhou.myqcloud.com/ai/result/457_original.jpg",
        "thumbnail_url": "https://example.cos.ap-guangzhou.myqcloud.com/ai/result/457_thumb.jpg",
        "is_downloaded": 1
      }
    ]
  }
}
```

**响应示例（失败）**:
```json
{
  "code": 401,
  "msg": "未授权"
}
```

```json
{
  "code": 500,
  "msg": "订单未支付"
}
```

**字段说明**:

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 订单商品ID |
| result_id | int | 成片ID |
| type | int | 成片类型（1=写真照, 2=视频） |
| download_url | string | 无水印原图下载地址（**核心字段**） |
| thumbnail_url | string | 缩略图预览地址 |
| is_downloaded | int | 是否已下载（0=否, 1=是） |

**前端下载代码**:
```javascript
// 获取下载列表
const response = await fetch(`/api/ai-travel-photo/pick/downloads?order_no=${orderNo}`);
const result = await response.json();

if (result.code === 200) {
  const downloads = result.data.downloads;
  
  // 渲染下载列表
  downloads.forEach(item => {
    const link = document.createElement('a');
    link.href = item.download_url;
    link.download = `成片_${item.result_id}.jpg`;
    link.textContent = '下载';
    document.body.appendChild(link);
    
    // 点击下载时记录
    link.addEventListener('click', () => {
      recordDownload(item.id);
    });
  });
}
```

---

### 6. 记录下载

**接口地址**: `POST /api/ai-travel-photo/pick/record_download`

**功能说明**: 记录用户的下载行为，更新 `is_downloaded` 状态和下载时间。

**请求头**:
```http
Content-Type: application/json
Cookie: PHPSESSID=xxx
```

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 | 示例 |
|--------|------|------|------|------|
| goods_id | int | 是 | 订单商品ID | 1 |

**请求示例**:
```json
{
  "goods_id": 1
}
```

**响应示例（成功）**:
```json
{
  "code": 200,
  "msg": "已记录"
}
```

**响应示例（失败）**:
```json
{
  "code": 401,
  "msg": "未授权"
}
```

---

## 支付履约流程

### 履约触发时机

- **主要途径**: 微信支付异步回调 `notify` 接口
- **回退机制**: 前端轮询 `pay_status` 接口时，如果数据库未更新但微信已支付，会主动触发履约

### 履约步骤详解

```php
public function paySuccessfulfilment(string $orderNo, array $payData = []): bool
{
    // 1. 查找订单
    $order = AiTravelPhotoOrder::where('order_no', $orderNo)->find();
    if (!$order) return false;

    // 2. 幂等性检查（防止重复履约）
    if ($order->status != AiTravelPhotoOrder::STATUS_UNPAID) {
        return true; // 已履约过，直接返回成功
    }

    Db::startTrans();
    try {
        // 3. 更新订单状态为已支付
        $order->status = AiTravelPhotoOrder::STATUS_PAID; // 1 → 2
        $order->pay_time = time();
        $order->transaction_id = $payData['transaction_id'] ?? ''; // 微信流水号
        $order->save();

        // 4. 写入 order_goods.download_url（无水印原图URL）
        $goods = AiTravelPhotoOrderGoods::where('order_id', $order->id)->select();
        foreach ($goods as $item) {
            $result = AiTravelPhotoResult::find($item->result_id);
            if ($result) {
                $item->download_url = $result->url; // ⭐ 关键：写入无水印原图
                $item->save();

                // 5. 写入 user_album（用户相册）
                AiTravelPhotoUserAlbum::create([
                    'aid' => $order->aid,
                    'uid' => $order->uid,
                    'bid' => $order->bid,
                    'order_id' => $order->id,
                    'portrait_id' => $order->portrait_id,
                    'result_id' => $result->id,
                    'type' => ($result->type == 19) ? 2 : 1,
                    'url' => $result->url,
                    'thumbnail_url' => $result->thumbnail_url,
                    'status' => 1,
                ]);

                // 6. 更新成片购买次数
                $result->buy_count += 1;
                $result->save();
            }
        }

        // 7. 更新二维码统计
        if ($order->qrcode_id > 0) {
            Db::name('ai_travel_photo_qrcode')
                ->where('id', $order->qrcode_id)
                ->inc('order_count', 1)
                ->inc('order_amount', $order->actual_amount)
                ->update();
        }

        // 8. 更新套餐销量统计
        if ($order->package_id > 0) {
            Db::name('ai_travel_photo_package')
                ->where('id', $order->package_id)
                ->inc('sale_count', 1)
                ->update();
        }

        Db::commit();
        return true;
    } catch (\Exception $e) {
        Db::rollback();
        Log::error('选片订单履约异常：' . $e->getMessage());
        return false;
    }
}
```

### 履约流程图

```
微信支付回调
    ↓
验证签名
    ↓
查找订单
    ↓
幂等性检查（防止重复）
    ↓
更新订单状态 (1→2)
    ↓
写入下载URL（无水印原图）
    ↓
写入用户相册
    ↓
更新统计数据
    ↓
提交事务
    ↓
返回SUCCESS给微信
```

---

## 成片交付机制

### 水印 vs 原图对比

| 场景 | URL字段 | 是否有水印 | 用途 |
|------|---------|-----------|------|
| 选片预览 | `result.watermark_url` | ✅ 有水印 | 防止盗图 |
| 缩略图 | `result.thumbnail_url` | ✅ 有水印 | 列表展示 |
| 支付前预览 | `result.watermark_url` | ✅ 有水印 | 大图预览 |
| **支付后下载** | `result.url` | ❌ 无水印 | **用户下载原图** |

### 交付时序

```
用户支付成功
    ↓
微信回调notify接口
    ↓
履约：写入download_url = result.url（无水印原图）
    ↓
前端调用downloads接口
    ↓
获取无水印原图URL
    ↓
用户下载/保存
```

### 核心数据流

**选片阶段** (浏览成片):
```json
{
  "thumbnail_url": "https://xxx.com/456_thumb.jpg?watermark",
  "preview_url": "https://xxx.com/456_watermark.jpg"
}
```

**支付后** (下载成片):
```json
{
  "download_url": "https://xxx.com/456_original.jpg",  // ⭐ 无水印原图
  "thumbnail_url": "https://xxx.com/456_thumb.jpg"
}
```

---

## 前端调用示例

### 完整支付流程 (Vue 3)

```vue
<template>
  <div class="pay-page">
    <h2>订单详情</h2>
    <p>订单号: {{ orderNo }}</p>
    <p>金额: ¥{{ amount }}</p>
    <button @click="handlePay" :disabled="paying">{{ paying ? '支付中...' : '立即支付' }}</button>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const orderNo = ref('AI20260327153045123456');
const amount = ref(49.50);
const paying = ref(false);

// 1. 发起支付
async function handlePay() {
  paying.value = true;
  
  try {
    // 调用后端获取支付参数
    const response = await fetch('/api/ai-travel-photo/pick/pay', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order_no: orderNo.value })
    });
    const result = await response.json();
    
    if (result.code !== 200) {
      alert(result.msg);
      paying.value = false;
      return;
    }
    
    // 调起微信支付
    const params = result.data.js_api_params;
    WeixinJSBridge.invoke('getBrandWCPayRequest', params, function(res) {
      if (res.err_msg === 'get_brand_wcpay_request:ok') {
        // 支付成功，轮询支付状态
        checkPayStatus();
      } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
        alert('支付已取消');
        paying.value = false;
      } else {
        alert('支付失败，请重试');
        paying.value = false;
      }
    });
  } catch (error) {
    console.error('支付失败', error);
    alert('网络异常，请重试');
    paying.value = false;
  }
}

// 2. 轮询支付状态
function checkPayStatus() {
  let count = 0;
  const maxCount = 20;
  
  const timer = setInterval(async () => {
    count++;
    
    try {
      const response = await fetch(`/api/ai-travel-photo/pick/pay_status?order_no=${orderNo.value}`);
      const result = await response.json();
      
      if (result.code === 200 && result.data.status === 'paid') {
        clearInterval(timer);
        paying.value = false;
        
        // 跳转下载页
        location.href = `/public/pick/download.html?order_no=${orderNo.value}`;
      } else if (count >= maxCount) {
        clearInterval(timer);
        paying.value = false;
        alert('支付状态查询超时，请稍后在"我的订单"中查看');
      }
    } catch (error) {
      console.error('查询支付状态失败', error);
    }
  }, 2000);
}
</script>
```

### 下载页面 (原生JS)

```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>下载成片</title>
</head>
<body>
  <div id="app">
    <h2>下载成片</h2>
    <div id="downloadList"></div>
  </div>

  <script>
    // 获取URL参数
    const urlParams = new URLSearchParams(window.location.search);
    const orderNo = urlParams.get('order_no');

    // 加载下载列表
    async function loadDownloads() {
      const response = await fetch(`/api/ai-travel-photo/pick/downloads?order_no=${orderNo}`);
      const result = await response.json();

      if (result.code !== 200) {
        alert(result.msg);
        return;
      }

      const downloads = result.data.downloads;
      const listEl = document.getElementById('downloadList');

      downloads.forEach(item => {
        const div = document.createElement('div');
        div.className = 'download-item';
        div.innerHTML = `
          <img src="${item.thumbnail_url}" alt="成片预览" style="width: 200px;">
          <a href="${item.download_url}" download="成片_${item.result_id}.jpg" 
             onclick="recordDownload(${item.id})">
            ${item.is_downloaded ? '重新下载' : '立即下载'}
          </a>
        `;
        listEl.appendChild(div);
      });
    }

    // 记录下载
    async function recordDownload(goodsId) {
      await fetch('/api/ai-travel-photo/pick/record_download', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ goods_id: goodsId })
      });
    }

    // 页面加载时执行
    loadDownloads();
  </script>
</body>
</html>
```

---

## 常见问题

### Q1: 支付成功但下载列表为空？

**原因**: notify回调失败，订单状态未更新

**解决方案**:
1. 前端调用 `pay_status` 接口会主动查询微信支付状态
2. 如果微信确认已支付，会自动触发履约
3. 履约成功后，`downloads` 接口即可返回下载URL

### Q2: 如何区分水印图和原图？

| 字段名 | 是否有水印 | 使用场景 |
|--------|-----------|---------|
| `result.watermark_url` | ✅ 有 | 支付前预览 |
| `result.thumbnail_url` | ✅ 有 | 列表缩略图 |
| `result.url` | ❌ 无 | **支付后下载** |
| `order_goods.download_url` | ❌ 无 | **履约后写入** |

### Q3: 为什么 `pay_status` 不需要Session鉴权？

**原因**: 微信收银台返回后，浏览器Session可能丢失（跨域、微信内置浏览器限制）

**安全保障**:
- order_no具有足够的唯一性和随机性（AI + 时间戳 + 随机数）
- 仅返回支付状态，不泄露敏感信息
- 真正的下载接口(`downloads`)仍需Session鉴权

### Q4: 如何处理支付回调延迟？

**双重保障机制**:

1. **主要途径**: 微信异步回调 `notify` 接口（通常1-5秒）
2. **回退机制**: 前端轮询 `pay_status` 接口（主动查询微信支付API）

**流程**:
```
用户支付成功
    ↓
【正常流程】微信回调notify → 履约 → 前端轮询检测到已支付
    ↓
【回退流程】notify失败 → 前端轮询 → 主动查询微信API → 触发履约
```

### Q5: 套餐购买超出张数如何计费？

**示例**: 用户选8张，购买"5张套餐"（¥39.9），单张价¥9.9

**计算**:
```
总价 = 套餐价 + (选片数 - 套餐张数) × 单价
     = 39.9 + (8 - 5) × 9.9
     = 39.9 + 29.7
     = 69.6元
```

**下载权益**:
```
download_limit = 选片数 = 8张
```

### Q6: 如何防止重复履约？

**幂等性检查**:
```php
if ($order->status != AiTravelPhotoOrder::STATUS_UNPAID) {
    return true; // 已履约过，直接返回成功
}
```

**事务保护**:
```php
Db::startTrans();
try {
    // ... 履约逻辑
    Db::commit();
} catch (\Exception $e) {
    Db::rollback();
}
```

### Q7: 成片交付的完整路径？

```
成片生成
    ↓
写入 result 表 (url=无水印, watermark_url=有水印)
    ↓
用户选片 (看到 watermark_url)
    ↓
创建订单
    ↓
支付成功
    ↓
履约：order_goods.download_url = result.url (无水印)
    ↓
用户调用 downloads 接口
    ↓
获取 download_url (无水印原图)
    ↓
下载保存
```

---

## 附录

### 订单状态码

| 状态值 | 常量名 | 说明 |
|--------|--------|------|
| 1 | STATUS_UNPAID | 未支付 |
| 2 | STATUS_PAID | 已支付 |
| 3 | STATUS_COMPLETED | 已完成 |
| 4 | STATUS_CLOSED | 已关闭 |
| 5 | STATUS_REFUNDING | 退款中 |
| 6 | STATUS_REFUNDED | 已退款 |

### 数据库表结构

**ai_travel_photo_order** (选片订单表)
```sql
CREATE TABLE `ai_travel_photo_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL COMMENT '应用ID',
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `qrcode_id` int(11) NOT NULL DEFAULT 0 COMMENT '二维码ID',
  `portrait_id` int(11) NOT NULL COMMENT '人像ID',
  `uid` int(11) NOT NULL DEFAULT 0 COMMENT '用户ID',
  `bid` int(11) NOT NULL COMMENT '商家ID',
  `buy_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '购买类型 1=单张 2=套餐',
  `package_id` int(11) NOT NULL DEFAULT 0 COMMENT '套餐ID',
  `selected_count` int(11) NOT NULL DEFAULT 0 COMMENT '选片数量',
  `package_snapshot` text COMMENT '套餐快照JSON',
  `download_count` int(11) NOT NULL DEFAULT 0 COMMENT '下载次数',
  `download_limit` int(11) NOT NULL DEFAULT 0 COMMENT '下载上限',
  `openid` varchar(64) DEFAULT NULL COMMENT '微信OpenID',
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '总价',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额',
  `actual_amount` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '订单状态',
  `pay_time` int(11) NOT NULL DEFAULT 0 COMMENT '支付时间',
  `transaction_id` varchar(64) DEFAULT NULL COMMENT '微信流水号',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `uid` (`uid`),
  KEY `openid` (`openid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**ai_travel_photo_order_goods** (订单商品表)
```sql
CREATE TABLE `ai_travel_photo_order_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL COMMENT '应用ID',
  `order_id` int(11) NOT NULL COMMENT '订单ID',
  `result_id` int(11) NOT NULL COMMENT '成片ID',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '类型 1=照片 2=视频',
  `goods_image` varchar(255) DEFAULT NULL COMMENT '商品图片',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '单价',
  `num` int(11) NOT NULL DEFAULT 1 COMMENT '数量',
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '小计',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `is_downloaded` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已下载',
  `download_url` varchar(500) DEFAULT NULL COMMENT '下载URL(无水印原图)',
  `download_time` int(11) NOT NULL DEFAULT 0 COMMENT '下载时间',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `result_id` (`result_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

**文档结束**  
如有疑问，请联系后端开发团队。
