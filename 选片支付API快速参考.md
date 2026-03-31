# AI旅拍选片支付API - 快速参考

> **前端开发必读** | 5分钟上手 | 最后更新: 2026-03-27

---

## 📌 核心流程（3步完成支付）

```
1️⃣ 创建订单  →  2️⃣ 发起支付  →  3️⃣ 下载成片
   order          pay            downloads
```

---

## 🚀 接口速查表

| 接口 | 地址 | 方法 | 用途 |
|------|------|------|------|
| ① 创建订单 | `/api/ai-travel-photo/pick/order` | POST | 生成订单号 |
| ② 发起支付 | `/api/ai-travel-photo/pick/pay` | POST | 获取微信支付参数 |
| ③ 支付状态 | `/api/ai-travel-photo/pick/pay_status` | GET | 轮询支付结果 |
| ④ 下载列表 | `/api/ai-travel-photo/pick/downloads` | GET | **获取无水印原图** |
| ⑤ 记录下载 | `/api/ai-travel-photo/pick/record_download` | POST | 统计下载次数 |

---

## 💻 代码示例

### 1. 创建订单

```javascript
const response = await fetch('/api/ai-travel-photo/pick/order', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    portrait_id: 123,
    result_ids: [456, 457, 458],  // 选中的成片ID数组
    package_id: 10,               // 套餐ID，0=单张购买
    aid: 1,
    bid: 5,
    qrcode_id: 100
  })
});

const result = await response.json();
// 成功：result.data.order_no = "AI20260327153045123456"
```

### 2. 发起支付

```javascript
const response = await fetch('/api/ai-travel-photo/pick/pay', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    order_no: 'AI20260327153045123456'
  })
});

const result = await response.json();
const params = result.data.js_api_params;

// 调起微信支付
WeixinJSBridge.invoke('getBrandWCPayRequest', params, function(res) {
  if (res.err_msg === 'get_brand_wcpay_request:ok') {
    // 支付成功，轮询支付状态
    checkPayStatus(orderNo);
  }
});
```

### 3. 轮询支付状态

```javascript
function checkPayStatus(orderNo) {
  const timer = setInterval(async () => {
    const response = await fetch(`/api/ai-travel-photo/pick/pay_status?order_no=${orderNo}`);
    const result = await response.json();
    
    if (result.data.status === 'paid') {
      clearInterval(timer);
      // 跳转下载页
      location.href = `/public/pick/download.html?order_no=${orderNo}`;
    }
  }, 2000); // 每2秒查询一次
}
```

### 4. 获取下载列表（⭐ 核心）

```javascript
const response = await fetch(`/api/ai-travel-photo/pick/downloads?order_no=${orderNo}`);
const result = await response.json();

// result.data.downloads 数组示例：
[
  {
    id: 1,
    result_id: 456,
    download_url: "https://xxx.com/456_original.jpg",  // ⭐ 无水印原图
    thumbnail_url: "https://xxx.com/456_thumb.jpg",
    is_downloaded: 0
  }
]
```

### 5. 下载成片

```javascript
downloads.forEach(item => {
  const link = document.createElement('a');
  link.href = item.download_url;  // 无水印原图
  link.download = `成片_${item.result_id}.jpg`;
  link.textContent = '下载';
  
  // 点击时记录
  link.addEventListener('click', () => {
    fetch('/api/ai-travel-photo/pick/record_download', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ goods_id: item.id })
    });
  });
  
  document.body.appendChild(link);
});
```

---

## 🎯 关键要点

### ① 水印 vs 原图

| 场景 | URL字段 | 是否有水印 |
|------|---------|-----------|
| 选片预览 | `result.watermark_url` | ✅ 有 |
| **支付后下载** | `order_goods.download_url` | ❌ **无水印原图** |

**结论**: 只有支付成功后，`downloads` 接口才返回无水印原图URL！

### ② 支付成功判断

```javascript
// ❌ 错误：直接跳转下载页
WeixinJSBridge.invoke('getBrandWCPayRequest', params, function(res) {
  if (res.err_msg === 'get_brand_wcpay_request:ok') {
    location.href = '/download.html';  // 此时后端可能还没收到微信回调！
  }
});

// ✅ 正确：轮询支付状态
WeixinJSBridge.invoke('getBrandWCPayRequest', params, function(res) {
  if (res.err_msg === 'get_brand_wcpay_request:ok') {
    checkPayStatus(orderNo);  // 轮询直到后端确认支付成功
  }
});
```

### ③ Session丢失问题

**现象**: 从微信收银台返回后，Session失效，接口报401

**解决**:
- `pay_status` 接口不需要Session鉴权（仅返回支付状态）
- `downloads` 接口需要Session鉴权（返回下载URL）
- 如果Session丢失，引导用户从"我的订单"入口进入

### ④ 套餐超额计费

**示例**: 选8张，购买"5张套餐"（¥39.9），单张¥9.9

```javascript
总价 = 套餐价 + (选片数 - 套餐张数) × 单价
     = 39.9 + (8 - 5) × 9.9
     = ¥69.6
```

---

## 🔧 完整示例（Vue 3）

```vue
<template>
  <div>
    <!-- 1. 选片 -->
    <div v-for="item in results" :key="item.id">
      <img :src="item.thumbnail_url" />
      <input type="checkbox" v-model="selected" :value="item.id" />
    </div>
    
    <!-- 2. 创建订单并支付 -->
    <button @click="createAndPay">立即支付</button>
    
    <!-- 3. 下载成片 -->
    <div v-if="paid">
      <a v-for="item in downloads" :key="item.id"
         :href="item.download_url"
         :download="`成片_${item.result_id}.jpg`"
         @click="recordDownload(item.id)">
        下载
      </a>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const selected = ref([]);  // 选中的成片ID
const paid = ref(false);
const downloads = ref([]);

// 创建订单并支付
async function createAndPay() {
  // 1. 创建订单
  const orderRes = await fetch('/api/ai-travel-photo/pick/order', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      portrait_id: 123,
      result_ids: selected.value,
      package_id: 10,
      aid: 1,
      bid: 5,
      qrcode_id: 100
    })
  });
  const orderData = await orderRes.json();
  const orderNo = orderData.data.order_no;
  
  // 2. 发起支付
  const payRes = await fetch('/api/ai-travel-photo/pick/pay', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ order_no: orderNo })
  });
  const payData = await payRes.json();
  const params = payData.data.js_api_params;
  
  // 3. 调起微信支付
  WeixinJSBridge.invoke('getBrandWCPayRequest', params, function(res) {
    if (res.err_msg === 'get_brand_wcpay_request:ok') {
      checkPayStatus(orderNo);
    }
  });
}

// 轮询支付状态
function checkPayStatus(orderNo) {
  const timer = setInterval(async () => {
    const res = await fetch(`/api/ai-travel-photo/pick/pay_status?order_no=${orderNo}`);
    const data = await res.json();
    
    if (data.data.status === 'paid') {
      clearInterval(timer);
      await loadDownloads(orderNo);
    }
  }, 2000);
}

// 加载下载列表
async function loadDownloads(orderNo) {
  const res = await fetch(`/api/ai-travel-photo/pick/downloads?order_no=${orderNo}`);
  const data = await res.json();
  
  downloads.value = data.data.downloads;
  paid.value = true;
}

// 记录下载
async function recordDownload(goodsId) {
  await fetch('/api/ai-travel-photo/pick/record_download', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ goods_id: goodsId })
  });
}
</script>
```

---

## 📊 支付流程图

```
用户选片
    ↓
创建订单 (order)
    ↓
发起支付 (pay)
    ↓
调起微信收银台
    ↓
用户支付成功
    ↓
微信回调后端 (notify)
    ↓
后端履约：写入download_url
    ↓
前端轮询 (pay_status)
    ↓
检测到已支付
    ↓
获取下载列表 (downloads)
    ↓
用户下载无水印原图
```

---

## ❓ 常见问题

### Q1: 支付成功但下载列表为空？

**答**: notify回调延迟，前端轮询 `pay_status` 会自动触发履约

### Q2: 如何区分水印图和原图？

**答**: 
- 选片阶段：`result.watermark_url`（有水印）
- 下载阶段：`order_goods.download_url`（无水印）

### Q3: 为什么需要轮询支付状态？

**答**: 微信回调有延迟（1-5秒），直接跳转可能导致下载列表为空

### Q4: Session丢失怎么办？

**答**: 
- `pay_status` 不需要Session，可以轮询
- `downloads` 需要Session，引导用户从"我的订单"入口进入

---

## 🎁 额外功能

### 一键生成视频幻灯片

```javascript
// 调用接口
const res = await fetch(`/api/ai-travel-photo/pick/generate_slideshow?order_no=${orderNo}`);
const data = await res.json();

// 播放视频
const video = document.createElement('video');
video.src = data.data.video_url;
video.controls = true;
document.body.appendChild(video);
```

### 我的订单列表

```javascript
// 获取当前用户的已支付订单
const res = await fetch('/api/ai-travel-photo/pick/my_orders');
const data = await res.json();

// data.data 数组示例：
[
  {
    order_no: "AI20260327153045123456",
    cover_image: "https://xxx.com/456_thumb.jpg",
    package_name: "5张套餐",
    selected_count: 5,
    actual_amount: 49.50,
    status_text: "已支付",
    pay_time: "2026-03-27 15:30",
    download_url: "/public/pick/download.html?order_no=xxx"
  }
]
```

---

## 📞 技术支持

- **详细文档**: 《[AI旅拍选片支付API接口文档.md](./选片支付API接口文档.md)》
- **订单管理API**: 《[选片订单API接口文档.md](./选片订单API接口文档.md)》

**Happy Coding! 🎉**
