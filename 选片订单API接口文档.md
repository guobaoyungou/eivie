# 选片订单API接口文档

## 📌 文档说明

本文档提供AI旅拍选片订单相关的API接口，供前端开发对接使用。

**基础信息**：
- 接口基础URL：`https://ai.eivie.cn/app/index.php?i=1&c=entry&m=ewei_shopv2&do=mobile`
- 接口控制器：`ApiUnifiedOrder`
- 认证方式：需要登录（自动携带session）
- 响应格式：JSON

---

## 1. 统一订单列表

### 接口地址
```
GET/POST ApiUnifiedOrder/orderlist
```

### 接口说明
获取当前用户的订单列表，支持多订单类型聚合（包括选片订单）。

### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| st | string | 否 | 订单状态筛选 | `0` / `1` / `2` / `3` / `all` |
| order_type | string | 否 | 订单类型筛选 | `ai_pick` / `shop` / `all` |
| keyword | string | 否 | 搜索关键词（订单号） | `AIPICK20260327` |
| pagenum | int | 否 | 页码（默认1） | `1` |
| pernum | int | 否 | 每页数量（默认10） | `10` |

**状态码说明（st参数）**：
- `0` - 待付款
- `1` - 待发货（选片订单无此状态）
- `2` - 待收货（选片订单无此状态）
- `3` - 已完成（选片订单的已付款状态）
- `all` - 全部
- `10` - 退款/售后

**订单类型说明（order_type参数）**：
- `ai_pick` - 选片订单
- `shop` - 商城订单
- `collage` - 拼团订单
- `seckill` - 秒杀订单
- `all` - 全部（默认）

### 响应示例

#### 成功响应

```json
{
  "datalist": [
    {
      "id": 123,
      "order_type": "ai_pick",
      "order_type_name": "选片",
      "ordernum": "AIPICK202603271430001",
      "title": "AI旅拍选片套餐",
      "cover_image": "https://cdn.example.com/image.jpg?x-oss-process=image/resize,w_50,h_50",
      "totalprice": "9.90",
      "total_price": "9.90",
      "status": 0,
      "status_text": "待付款",
      "unified_status": 0,
      "item_count": 3,
      "procount": 3,
      "create_time": "2026-03-27 14:30",
      "create_timestamp": 1711523400,
      "detail_url": "/pagesExt/order/ai_pick_detail?id=123",
      "refund_status": 0,
      "payorderid": 456,
      "result_status": "normal",
      "download_url": "",
      "prolist": [
        {
          "id": 1,
          "orderid": 123,
          "proid": 789,
          "name": "AI旅拍成片",
          "pic": "https://cdn.example.com/photo.jpg?x-oss-process=image/resize,w_50,h_50",
          "ggname": "",
          "gg_group_title": "",
          "num": 1,
          "sell_price": "3.30",
          "real_sell_price": "3.30"
        }
      ],
      "binfo": {
        "name": "选片",
        "logo": "https://cdn.example.com/image.jpg?x-oss-process=image/resize,w_50,h_50"
      },
      "bid": 0,
      "extra_info": {
        "buy_type": 1,
        "package_snapshot": "{...}"
      }
    }
  ],
  "type_counts": [],
  "status_counts": []
}
```

### 响应字段说明

**订单主体字段**：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 订单ID |
| order_type | string | 订单类型标识（`ai_pick`） |
| order_type_name | string | 订单类型名称（`选片`） |
| ordernum | string | 订单号 |
| title | string | 订单标题/商品名称 |
| cover_image | string | 封面图（50x50px缩略图） |
| totalprice | string | 订单总价（格式化后） |
| total_price | string | 订单总价（同上） |
| status | int | 订单原始状态（0=待付款, 1=已付款, 3=已关闭, 4=已退款） |
| status_text | string | 状态文本（待付款/已完成/已关闭/已退款） |
| unified_status | int | 统一状态码（0=待付款, 3=已完成） |
| item_count | int | 商品数量 |
| procount | int | 商品数量（同上） |
| create_time | string | 下单时间（Y-m-d H:i格式） |
| create_timestamp | int | 下单时间戳 |
| detail_url | string | 详情页路由 |
| refund_status | int | 退款状态（0=无, 1=退款中, 2=已退款） |
| payorderid | int | 支付订单ID（用于去付款） |
| result_status | string | 成片状态（`normal`=正常, `expired`=已过期） |
| download_url | string | 下载链接 |

**商品列表字段（prolist）**：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 商品记录ID |
| orderid | int | 订单ID |
| proid | int | 成片ID（result_id） |
| name | string | 商品名称 |
| pic | string | 商品图片（50x50px缩略图） |
| num | int | 数量 |
| sell_price | string | 单价 |
| real_sell_price | string | 实际单价 |

**extra_info额外信息**：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| buy_type | int | 购买类型（1=标准套餐, 2=精修套餐） |
| package_snapshot | string | 套餐快照（JSON字符串） |

---

## 2. 订单数量统计

### 接口地址
```
GET/POST ApiUnifiedOrder/ordercount
```

### 接口说明
获取当前用户各状态订单的数量统计。

### 请求参数
无

### 响应示例

```json
{
  "count0": 2,
  "count1": 0,
  "count2": 0,
  "count3": 15,
  "count_refund": 1
}
```

### 响应字段说明

| 字段名 | 类型 | 说明 |
|--------|------|------|
| count0 | int | 待付款订单数 |
| count1 | int | 待发货订单数（选片订单不计入） |
| count2 | int | 待收货订单数（选片订单不计入） |
| count3 | int | 已完成订单数（选片订单的已付款订单计入） |
| count_refund | int | 退款/售后订单数 |

---

## 3. 订单详情

### 接口地址
```
GET/POST ApiUnifiedOrder/detail
```

### 接口说明
获取选片订单的详细信息。

### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 订单ID | `123` |

### 响应示例

#### 成功响应

```json
{
  "status": 1,
  "data": {
    "id": 123,
    "ordernum": "AIPICK202603271430001",
    "status": 0,
    "totalprice": "9.90",
    "createtime": "2026-03-27 14:30:15",
    "paytime": "",
    "prolist": [
      {
        "id": 1,
        "orderid": 123,
        "proid": 789,
        "name": "AI旅拍成片",
        "pic": "https://cdn.example.com/photo.jpg",
        "ggname": "",
        "gg_group_title": "",
        "num": 1,
        "sell_price": "3.30",
        "real_sell_price": "3.30"
      }
    ],
    "procount": 3,
    "binfo": {
      "name": "选片",
      "logo": ""
    },
    "bid": 0,
    "order_type": "ai_pick",
    "payorderid": 456,
    "freight_type": 0,
    "address": "",
    "linkman": "",
    "tel": "",
    "can_collect": false,
    "invoice": 0,
    "refundCount": 0,
    "refundnum": 0
  }
}
```

#### 失败响应

```json
{
  "status": 0,
  "msg": "订单不存在"
}
```

### 响应字段说明

| 字段名 | 类型 | 说明 |
|--------|------|------|
| status | int | 响应状态（1=成功, 0=失败） |
| msg | string | 错误消息（失败时） |
| data | object | 订单详情数据（成功时） |

**data字段详细说明**：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 订单ID |
| ordernum | string | 订单号 |
| status | int | 订单状态（0=待付款, 1=已付款, 3=已关闭, 4=已退款） |
| totalprice | string | 订单总价 |
| createtime | string | 下单时间（Y-m-d H:i:s格式） |
| paytime | string | 支付时间（为空表示未支付） |
| prolist | array | 商品列表 |
| procount | int | 商品数量 |
| binfo | object | 商家信息（选片订单为空） |
| order_type | string | 订单类型（`ai_pick`） |
| payorderid | int | 支付订单ID（0=无需支付） |
| freight_type | int | 配送方式（选片订单固定为0） |
| address | string | 收货地址（选片订单为空） |
| linkman | string | 收货人（选片订单为空） |
| tel | string | 联系电话（选片订单为空） |
| can_collect | bool | 是否可收藏（选片订单固定为false） |
| invoice | int | 发票状态（选片订单固定为0） |
| refundCount | int | 退款商品数 |
| refundnum | int | 退款数量 |

---

## 4. 关闭订单

### 接口地址
```
POST ApiUnifiedOrder/closeOrder
```

### 接口说明
关闭待付款状态的选片订单。

### 请求参数

| 参数名 | 类型 | 必填 | 说明 | 示例值 |
|--------|------|------|------|--------|
| id | int | 是 | 订单ID | `123` |

### 响应示例

#### 成功响应

```json
{
  "status": 1,
  "msg": "订单已关闭"
}
```

#### 失败响应

```json
{
  "status": 0,
  "msg": "只有待付款订单才能关闭"
}
```

或

```json
{
  "status": 0,
  "msg": "订单不存在"
}
```

### 业务逻辑说明

1. **权限校验**：只能关闭自己的订单（通过uid或openid匹配）
2. **状态校验**：只有 `status=0`（待付款）的订单可以关闭
3. **关联操作**：
   - 更新订单状态为 `status=3`（已关闭）
   - 设置 `close_time` 为当前时间
   - 更新 `update_time` 为当前时间
   - 关闭对应的支付订单（payorder表中的status改为-1）

---

## 5. 状态码说明

### 订单状态（status）

| 状态码 | 说明 | 前端显示 | 可用操作 |
|--------|------|----------|----------|
| 0 | 待付款 | 待付款 | 去付款、关闭订单、查看详情 |
| 1 | 已付款 | 已完成 | 查看详情、去下载（成片正常）、已过期（成片删除） |
| 2 | （保留） | 已完成 | 查看详情 |
| 3 | 已关闭 | 已关闭 | 查看详情 |
| 4 | 已退款 | 已退款 | 查看详情 |

### 统一状态码（unified_status）

用于统一订单列表的筛选，将不同订单类型的状态归一化：

| 统一状态码 | 说明 | 选片订单映射 |
|-----------|------|-------------|
| 0 | 待付款 | status=0 |
| 1 | 待发货 | 无（不显示） |
| 2 | 待收货 | 无（不显示） |
| 3 | 已完成 | status=1（已付款） |
| 4 | 已关闭 | status=3, 4 |

### 退款状态（refund_status）

| 状态码 | 说明 |
|--------|------|
| 0 | 无退款 |
| 1 | 退款中 |
| 2 | 已退款 |

### 成片状态（result_status）

仅用于已付款订单（status=1）：

| 状态值 | 说明 | 前端显示 |
|--------|------|----------|
| normal | 成片正常 | 显示"去下载"按钮 |
| expired | 成片已过期/删除 | 显示"已过期"文字（置灰） |

---

## 6. 前端开发建议

### 6.1 订单列表页面

**页面路由**：`/pagesExt/order/orderlist`

**主要功能**：
1. Tab切换：全部、待付款、待发货、待收货、已完成、退款/售后
2. 订单类型筛选（可选）
3. 搜索功能（订单号）
4. 分页加载

**关键逻辑**：
```javascript
// 获取订单列表
function getOrderList(st = 'all', pagenum = 1) {
  app.post('ApiUnifiedOrder/orderlist', {
    st: st,
    order_type: 'all',  // 或 'ai_pick' 仅显示选片订单
    pagenum: pagenum,
    pernum: 10
  }, function(res) {
    // 处理订单列表
    res.datalist.forEach(order => {
      // 根据 order.order_type 判断订单类型
      if (order.order_type === 'ai_pick') {
        // 选片订单特殊处理
      }
    });
  });
}

// 获取订单数量徽标
function getOrderCount() {
  app.post('ApiUnifiedOrder/ordercount', {}, function(res) {
    // 设置徽标数字
    // count0 => 待付款徽标
    // count3 => 已完成徽标
    // count_refund => 退款徽标
  });
}
```

### 6.2 订单卡片组件

**组件路径**：`/uniapp/components/unified-order-card/unified-order-card.vue`

**显示内容**：
```
┌──────────────────────────────────────┐
│ [选片] AIPICK20260327123456   待付款 │ ← 订单类型、订单号、状态
│        2026-03-27 14:30              │ ← 下单时间
├──────────────────────────────────────┤
│ [图] AI旅拍选片套餐                  │ ← 封面图、商品名称
│      共3件商品                       │
├──────────────────────────────────────┤
│ 片源清单(3)                     ▼   │ ← 可展开/折叠
├──────────────────────────────────────┤
│ 共计3件商品 实付:￥9.90              │
├──────────────────────────────────────┤
│                   [详情] [去付款]    │ ← 操作按钮
└──────────────────────────────────────┘
```

**按钮显示逻辑**：
```javascript
// 待付款订单（status=0）
if (order.status === 0 && order.payorderid) {
  // 显示：[关闭订单] [去付款]
}

// 已完成订单（status=1）
if (order.status === 1 && order.order_type === 'ai_pick') {
  if (order.result_status === 'expired') {
    // 显示：[已过期] [详情]
  } else {
    // 显示：[去下载] [详情]
  }
}
```

### 6.3 订单详情页面

**页面路由**：`/pagesExt/order/ai_pick_detail`

**参数**：`id=订单ID`

**主要功能**：
1. 订单信息展示
2. 商品列表展示
3. 操作按钮（去付款、关闭订单）

**关键逻辑**：
```javascript
// 获取订单详情
function getOrderDetail(orderId) {
  app.post('ApiUnifiedOrder/detail', {
    id: orderId
  }, function(res) {
    if (res.status === 1) {
      var order = res.data;
      // 渲染订单信息
      // order.status => 判断显示哪些按钮
      // order.prolist => 渲染商品列表
      // order.payorderid => 去付款功能
    } else {
      uni.showToast({
        title: res.msg || '订单不存在',
        icon: 'none'
      });
    }
  });
}

// 关闭订单
function closeOrder(orderId) {
  uni.showModal({
    title: '提示',
    content: '确定要关闭订单吗？',
    success: function(res) {
      if (res.confirm) {
        app.post('ApiUnifiedOrder/closeOrder', {
          id: orderId
        }, function(res) {
          if (res.status === 1) {
            uni.showToast({
              title: '订单已关闭',
              icon: 'success'
            });
            // 刷新页面或返回列表
          } else {
            uni.showToast({
              title: res.msg,
              icon: 'none'
            });
          }
        });
      }
    }
  });
}

// 去付款
function goPay(payorderId) {
  if (!payorderId || payorderId === 0) {
    uni.showToast({
      title: '支付信息异常',
      icon: 'none'
    });
    return;
  }
  app.goto('/pagesExt/pay/pay?id=' + payorderId);
}

// 去下载
function goDownload(orderId) {
  app.goto('/pagesExt/order/ai_pick_detail?id=' + orderId);
}
```

### 6.4 缩略图处理

所有图片URL已自动添加缩略图参数（50x50px）：

```javascript
// API返回的图片已经包含缩略图参数
// 例如：https://cdn.example.com/image.jpg?x-oss-process=image/resize,w_50,h_50

// 在image组件中直接使用
<image :src="order.cover_image" mode="aspectFill"></image>
```

### 6.5 时间格式化

API返回两种时间格式：

```javascript
// 格式化时间字符串（用于显示）
order.create_time // "2026-03-27 14:30"

// 时间戳（用于排序、对比）
order.create_timestamp // 1711523400
```

---

## 7. 错误码说明

### 常见错误

| 错误码 | 错误信息 | 原因 | 解决方案 |
|--------|----------|------|----------|
| 0 | 订单ID不能为空 | 未传id参数 | 检查请求参数 |
| 0 | 订单不存在 | 订单ID无效或无权限访问 | 确认订单ID正确且属于当前用户 |
| 0 | 只有待付款订单才能关闭 | 订单状态不是待付款 | 检查订单状态 |
| 0 | 关闭失败 | 数据库更新失败 | 稍后重试或联系技术支持 |
| 0 | 该订单类型不支持关闭操作 | 非选片订单 | 使用对应订单类型的关闭接口 |

---

## 8. 权限说明

### 用户匹配规则

选片订单支持两种用户标识方式：

1. **注册用户**：通过 `uid` 匹配
   - 订单表中 `uid` = 当前用户的会员ID

2. **H5扫码用户**：通过 `openid` 匹配
   - 订单表中 `uid=0`，`openid` = 当前用户的微信OpenID

3. **混合匹配**：
   - 注册用户同时匹配 `uid` 和 `openid`
   - SQL条件：`(uid=用户ID OR openid=用户OpenID)`

这样确保用户无论是通过小程序还是H5扫码，都能访问自己的订单。

---

## 9. 测试用例

### 9.1 订单列表测试

```javascript
// 测试1：获取全部订单
app.post('ApiUnifiedOrder/orderlist', {
  st: 'all',
  order_type: 'all',
  pagenum: 1
}, console.log);

// 测试2：获取待付款的选片订单
app.post('ApiUnifiedOrder/orderlist', {
  st: '0',
  order_type: 'ai_pick',
  pagenum: 1
}, console.log);

// 测试3：搜索订单号
app.post('ApiUnifiedOrder/orderlist', {
  st: 'all',
  keyword: 'AIPICK',
  pagenum: 1
}, console.log);
```

### 9.2 订单详情测试

```javascript
// 测试：获取订单详情
app.post('ApiUnifiedOrder/detail', {
  id: 123
}, console.log);
```

### 9.3 关闭订单测试

```javascript
// 测试：关闭订单
app.post('ApiUnifiedOrder/closeOrder', {
  id: 123
}, console.log);
```

---

## 10. 数据库表结构

### 选片订单表（ai_travel_photo_order）

主要字段：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 订单ID（主键） |
| aid | int | 平台ID |
| uid | int | 用户ID（0表示H5扫码用户） |
| openid | varchar | 微信OpenID |
| order_no | varchar | 订单号 |
| status | tinyint | 订单状态（0待付款, 1已付款, 3已关闭, 4已退款） |
| total_price | decimal | 订单原价 |
| actual_amount | decimal | 实付金额 |
| refund_status | tinyint | 退款状态 |
| create_time | int | 创建时间戳 |
| pay_time | int | 支付时间戳 |
| close_time | int | 关闭时间戳 |
| update_time | int | 更新时间戳 |
| package_snapshot | text | 套餐快照（JSON） |
| buy_type | tinyint | 购买类型（1标准, 2精修） |

### 订单商品表（ai_travel_photo_order_goods）

主要字段：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 商品记录ID |
| order_id | int | 订单ID |
| result_id | int | 成片ID |
| goods_name | varchar | 商品名称 |
| goods_image | varchar | 商品图片URL |
| price | decimal | 单价 |
| num | int | 数量 |
| status | tinyint | 状态（1正常, 2已退款） |

### 支付订单表（payorder）

主要字段：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | int | 支付订单ID |
| aid | int | 平台ID |
| type | varchar | 订单类型（`ai_pick`） |
| orderid | int | 业务订单ID |
| status | tinyint | 支付状态（0未支付, 1已支付, -1已关闭） |

---

## 11. 常见问题FAQ

### Q1: 为什么修改了订单数据，前端看不到变化？

**A**: 需要重新调用接口获取最新数据，前端不会自动更新。

### Q2: 选片订单为什么没有"待发货"和"待收货"状态？

**A**: 选片订单是虚拟商品，付款后直接可以下载，不需要物流配送，所以没有发货和收货状态。

### Q3: `payorderid` 为0是什么意思？

**A**: 表示订单已支付或已关闭，不需要支付。只有待付款订单（status=0）才有有效的payorderid。

### Q4: 如何判断订单是否可以下载成片？

**A**: 检查两个条件：
- `status === 1`（已付款）
- `result_status === 'normal'`（成片未过期）

### Q5: H5扫码用户和注册用户有什么区别？

**A**: 
- H5扫码用户：订单中 `uid=0`，通过 `openid` 标识
- 注册用户：订单中 `uid=会员ID`，同时记录 `openid`
- API会自动处理两种用户的权限校验

### Q6: 订单列表的分页如何处理？

**A**: 
- 使用 `pagenum` 参数控制页码
- 每页固定10条（`pernum=10`）
- 建议使用上拉加载更多的方式

### Q7: 如何刷新订单列表？

**A**: 
- 使用下拉刷新重新调用 `orderlist` 接口
- 设置 `pagenum=1` 重新获取第一页数据

---

## 12. 版本历史

| 版本号 | 日期 | 更新内容 |
|--------|------|----------|
| v1.0 | 2026-03-27 | 初始版本，包含订单列表、详情、关闭等基础功能 |
| v1.1 | 2026-03-27 | 优化whereOr语法，支持H5扫码用户权限校验 |
| v1.2 | 2026-03-27 | 优化缩略图尺寸为50x50px |
| v1.3 | 2026-03-27 | 添加订单号和下单日期显示 |

---

## 13. 联系方式

如有疑问或需要技术支持，请联系：

- 技术文档：`/home/www/ai.eivie.cn/选片订单API接口文档.md`
- 相关文档：
  - `/home/www/ai.eivie.cn/选片订单whereOr语法修复及缩略图优化说明.md`
  - `/home/www/ai.eivie.cn/订单卡片显示订单号和下单日期优化说明.md`
  - `/home/www/ai.eivie.cn/H5端订单卡片布局未更新问题说明.md`

---

**文档生成时间**：2026-03-27
**接口版本**：v1.3
**文档维护**：AI Assistant
