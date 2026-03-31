# 选片订单API快速参考

## 📌 基础信息

**接口基础URL**：`https://ai.eivie.cn/app/index.php?i=1&c=entry&m=ewei_shopv2&do=mobile`

**认证方式**：需要登录（自动携带session）

---

## 🔗 接口列表

### 1. 订单列表
```
POST ApiUnifiedOrder/orderlist
```

**请求参数**：
```json
{
  "st": "0",              // 状态：0待付款, 1待发货, 2待收货, 3已完成, all全部, 10退款
  "order_type": "ai_pick", // 类型：ai_pick选片, all全部
  "keyword": "",          // 搜索关键词
  "pagenum": 1,           // 页码
  "pernum": 10            // 每页数量
}
```

**响应示例**：
```json
{
  "datalist": [
    {
      "id": 123,
      "order_type": "ai_pick",
      "ordernum": "AIPICK202603271430001",
      "status": 0,
      "status_text": "待付款",
      "cover_image": "https://xxx.jpg?x-oss-process=image/resize,w_50,h_50",
      "total_price": "9.90",
      "create_time": "2026-03-27 14:30",
      "detail_url": "/pagesExt/order/ai_pick_detail?id=123",
      "payorderid": 456,
      "result_status": "normal",
      "prolist": [...]
    }
  ]
}
```

---

### 2. 订单数量
```
POST ApiUnifiedOrder/ordercount
```

**请求参数**：无

**响应示例**：
```json
{
  "count0": 2,        // 待付款
  "count1": 0,        // 待发货
  "count2": 0,        // 待收货
  "count3": 15,       // 已完成
  "count_refund": 1   // 退款
}
```

---

### 3. 订单详情
```
POST ApiUnifiedOrder/detail
```

**请求参数**：
```json
{
  "id": 123  // 订单ID
}
```

**响应示例**：
```json
{
  "status": 1,
  "data": {
    "id": 123,
    "ordernum": "AIPICK202603271430001",
    "status": 0,
    "totalprice": "9.90",
    "createtime": "2026-03-27 14:30:15",
    "prolist": [...],
    "payorderid": 456
  }
}
```

---

### 4. 关闭订单
```
POST ApiUnifiedOrder/closeOrder
```

**请求参数**：
```json
{
  "id": 123  // 订单ID
}
```

**响应示例**：
```json
{
  "status": 1,
  "msg": "订单已关闭"
}
```

---

## 📊 状态码

### 订单状态（status）

| 值 | 说明 | 显示 | 按钮 |
|----|------|------|------|
| 0 | 待付款 | 待付款 | 去付款、关闭订单 |
| 1 | 已付款 | 已完成 | 去下载/已过期 |
| 3 | 已关闭 | 已关闭 | 查看详情 |
| 4 | 已退款 | 已退款 | 查看详情 |

### 成片状态（result_status）

| 值 | 说明 | 按钮 |
|----|------|------|
| normal | 成片正常 | 去下载 |
| expired | 成片过期 | 已过期（置灰） |

---

## 💻 前端示例代码

### 获取订单列表
```javascript
app.post('ApiUnifiedOrder/orderlist', {
  st: '0',           // 待付款
  order_type: 'ai_pick',
  pagenum: 1
}, function(res) {
  console.log(res.datalist);
});
```

### 获取订单详情
```javascript
app.post('ApiUnifiedOrder/detail', {
  id: 123
}, function(res) {
  if (res.status === 1) {
    var order = res.data;
    console.log(order);
  }
});
```

### 关闭订单
```javascript
app.post('ApiUnifiedOrder/closeOrder', {
  id: 123
}, function(res) {
  if (res.status === 1) {
    uni.showToast({
      title: '订单已关闭',
      icon: 'success'
    });
  }
});
```

### 去付款
```javascript
function goPay(payorderId) {
  if (payorderId && payorderId > 0) {
    app.goto('/pagesExt/pay/pay?id=' + payorderId);
  }
}
```

---

## 🎯 关键字段说明

**订单对象主要字段**：
- `id` - 订单ID
- `ordernum` - 订单号
- `status` - 订单状态（0/1/3/4）
- `status_text` - 状态文本
- `total_price` - 订单总价
- `create_time` - 下单时间
- `payorderid` - 支付订单ID（用于去付款）
- `result_status` - 成片状态（normal/expired）
- `detail_url` - 详情页路由
- `prolist` - 商品列表
- `cover_image` - 封面图（已包含50x50缩略图参数）

---

## ⚠️ 注意事项

1. **缩略图自动处理**：所有图片URL已自动添加 `?x-oss-process=image/resize,w_50,h_50` 参数
2. **权限校验**：API自动处理注册用户和H5扫码用户的权限验证
3. **支付订单ID**：只有待付款订单（status=0）才有有效的payorderid
4. **成片状态**：只有已付款订单（status=1）才有result_status字段
5. **关闭限制**：只有待付款订单可以关闭

---

## 📖 完整文档

详细文档请参阅：`/home/www/ai.eivie.cn/选片订单API接口文档.md`

---

**更新时间**：2026-03-27
