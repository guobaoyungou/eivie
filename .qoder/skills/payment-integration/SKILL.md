---
name: payment-integration
description: 支付系统集成开发（微信支付V2/V3、支付宝、余额支付）。用于创建支付订单、处理支付回调、退款处理、分账管理。适用于ApiPay控制器和支付相关功能开发。
---

# 支付系统集成开发

点大商城支持多种支付方式：微信支付（V2/V3）、支付宝、余额支付、货到付款等。

## 快速开始

### 创建支付订单
```php
// 1. 创建支付订单记录
$payData = [
    'aid' => aid,
    'mid' => $mid,
    'ordernum' => $ordernum,  // 业务订单号
    'paynum' => date('YmdHis').rand(1000,9999),  // 支付单号
    'money' => $money,
    'type' => 'shop',  // 订单类型
    'paytypeid' => 2,  // 1余额 2微信 3支付宝
    'status' => 0,
    'createtime' => time()
];

$payorderId = Db::name('payorder')->insertGetId($payData);

// 2. 调用支付接口
if ($paytypeid == 2) {
    // 微信支付
    $wxpay = new \pay\wechatpay\WxPayV3();
    $result = $wxpay->jsapiPay($paynum, $money, '商品订单');
} elseif ($paytypeid == 3) {
    // 支付宝支付
    $alipay = new \app\common\Alipay();
    $result = $alipay->build($paynum, $money, '商品订单');
}

return json([
    'status' => 1,
    'data' => $result
]);
```

## 核心工作流程

### 1. 支付流程

```
创建业务订单 → 创建支付订单 → 调起支付 → 支付回调 → 更新订单 → 执行后续逻辑
```

### 2. 支付订单表(payorder)

**关键字段**：
```sql
aid - 平台ID
mid - 会员ID
ordernum - 业务订单号
paynum - 支付单号（唯一）
money - 支付金额
type - 订单类型（shop/recharge/...）
paytypeid - 支付方式（1余额 2微信 3支付宝）
status - 支付状态（0未支付 1已支付 2已退款）
paytime - 支付时间
transaction_id - 第三方交易号
```

### 3. 微信支付V3集成

**初始化配置**：
```php
use pay\wechatpay\WxPayV3;

$wxpay = new WxPayV3();
$wxpay->setAppid($appid);
$wxpay->setMchid($mchid);
$wxpay->setApiKey($apikey);
$wxpay->setCertPath($certPath);  // 证书路径
$wxpay->setKeyPath($keyPath);    // 私钥路径
```

**JSAPI支付（小程序/公众号）**：
```php
public function wxpayJsapi() {
    $paynum = input('param.paynum');
    $openid = input('param.openid');
    
    $payorder = Db::name('payorder')
        ->where('aid', aid)
        ->where('paynum', $paynum)
        ->find();
    
    if (!$payorder) {
        return json(['status' => 0, 'msg' => '支付订单不存在']);
    }
    
    if ($payorder['status'] == 1) {
        return json(['status' => 0, 'msg' => '订单已支付']);
    }
    
    $wxpay = new WxPayV3();
    $result = $wxpay->jsapiPay(
        $paynum,
        $payorder['money'],
        '商品订单',
        $openid
    );
    
    if ($result['code'] == 1) {
        return json([
            'status' => 1,
            'data' => $result['data']  // 返回给前端调起支付
        ]);
    } else {
        return json(['status' => 0, 'msg' => $result['msg']]);
    }
}
```

**H5支付**：
```php
public function wxpayH5() {
    $paynum = input('param.paynum');
    
    $payorder = Db::name('payorder')
        ->where('aid', aid)
        ->where('paynum', $paynum)
        ->find();
    
    $wxpay = new WxPayV3();
    $result = $wxpay->h5Pay(
        $paynum,
        $payorder['money'],
        '商品订单'
    );
    
    if ($result['code'] == 1) {
        // 返回H5支付URL，前端跳转
        return json([
            'status' => 1,
            'url' => $result['data']['h5_url']
        ]);
    } else {
        return json(['status' => 0, 'msg' => $result['msg']]);
    }
}
```

**APP支付**：
```php
public function wxpayApp() {
    $paynum = input('param.paynum');
    
    $payorder = Db::name('payorder')
        ->where('aid', aid)
        ->where('paynum', $paynum)
        ->find();
    
    $wxpay = new WxPayV3();
    $result = $wxpay->appPay(
        $paynum,
        $payorder['money'],
        '商品订单'
    );
    
    if ($result['code'] == 1) {
        return json([
            'status' => 1,
            'data' => $result['data']  // APP调起支付参数
        ]);
    } else {
        return json(['status' => 0, 'msg' => $result['msg']]);
    }
}
```

### 4. 支付宝集成

**初始化配置**：
```php
use app\common\Alipay;

$alipay = new Alipay();
$alipay->setAppId($appid);
$alipay->setPrivateKey($privateKey);
$alipay->setPublicKey($publicKey);
```

**手机网站支付**：
```php
public function alipayWap() {
    $paynum = input('param.paynum');
    
    $payorder = Db::name('payorder')
        ->where('aid', aid)
        ->where('paynum', $paynum)
        ->find();
    
    $alipay = new Alipay();
    $result = $alipay->build_wap(
        $paynum,
        $payorder['money'],
        '商品订单',
        url('api/pay/alipay_return', [], true, true),  // 同步回调
        url('api/pay/alipay_notify', [], true, true)   // 异步回调
    );
    
    // 返回支付表单，前端自动提交
    return $result;
}
```

**APP支付**：
```php
public function alipayApp() {
    $paynum = input('param.paynum');
    
    $payorder = Db::name('payorder')
        ->where('aid', aid)
        ->where('paynum', $paynum)
        ->find();
    
    $alipay = new Alipay();
    $result = $alipay->build_app(
        $paynum,
        $payorder['money'],
        '商品订单',
        url('api/pay/alipay_notify', [], true, true)
    );
    
    return json([
        'status' => 1,
        'orderString' => $result  // APP调起支付参数
    ]);
}
```

**当面付（扫码）**：
```php
public function alipayScan() {
    $paynum = input('param.paynum');
    
    $payorder = Db::name('payorder')
        ->where('aid', aid)
        ->where('paynum', $paynum)
        ->find();
    
    $alipay = new Alipay();
    $result = $alipay->build_scan(
        $paynum,
        $payorder['money'],
        '商品订单'
    );
    
    return json([
        'status' => 1,
        'qr_code' => $result['qr_code']  // 二维码内容
    ]);
}
```

### 5. 余额支付

```php
public function balancePay() {
    $paynum = input('param.paynum');
    $mid = input('param.mid/d');
    $password = input('param.password');
    
    if (!$mid) {
        return json(['status' => -5, 'msg' => '请先登录']);
    }
    
    // 查询支付订单
    $payorder = Db::name('payorder')
        ->where('aid', aid)
        ->where('paynum', $paynum)
        ->find();
    
    if (!$payorder) {
        return json(['status' => 0, 'msg' => '支付订单不存在']);
    }
    
    if ($payorder['status'] == 1) {
        return json(['status' => 0, 'msg' => '订单已支付']);
    }
    
    // 查询会员
    $member = Db::name('member')
        ->where('aid', aid)
        ->where('id', $mid)
        ->find();
    
    // 验证支付密码（如果设置了）
    if ($member['paypassword'] && !password_verify($password, $member['paypassword'])) {
        return json(['status' => 0, 'msg' => '支付密码错误']);
    }
    
    // 检查余额
    if ($member['money'] < $payorder['money']) {
        return json(['status' => 0, 'msg' => '余额不足']);
    }
    
    Db::startTrans();
    try {
        // 扣除余额
        \app\common\Member::addmoney(
            aid,
            $mid,
            -$payorder['money'],
            '余额支付',
            ['type' => 'pay', 'ordernum' => $payorder['ordernum']]
        );
        
        // 更新支付订单
        Db::name('payorder')
            ->where('id', $payorder['id'])
            ->update([
                'status' => 1,
                'paytime' => time()
            ]);
        
        // 执行支付后续逻辑
        $this->afterPay($payorder);
        
        Db::commit();
        
        return json(['status' => 1, 'msg' => '支付成功']);
        
    } catch (\Exception $e) {
        Db::rollback();
        return json(['status' => 0, 'msg' => '支付失败：' . $e->getMessage()]);
    }
}
```

### 6. 支付回调处理

**微信支付回调**：
```php
public function wxpayNotify() {
    $wxpay = new WxPayV3();
    
    // 验证签名
    $result = $wxpay->notify();
    
    if ($result['code'] != 1) {
        // 签名验证失败
        echo json_encode(['code' => 'FAIL', 'message' => '验证失败']);
        exit;
    }
    
    $data = $result['data'];
    $paynum = $data['out_trade_no'];  // 商户订单号
    $transaction_id = $data['transaction_id'];  // 微信交易号
    
    // 查询支付订单
    $payorder = Db::name('payorder')
        ->where('paynum', $paynum)
        ->find();
    
    if (!$payorder) {
        echo json_encode(['code' => 'FAIL', 'message' => '订单不存在']);
        exit;
    }
    
    if ($payorder['status'] == 1) {
        // 已处理
        echo json_encode(['code' => 'SUCCESS', 'message' => '成功']);
        exit;
    }
    
    Db::startTrans();
    try {
        // 更新支付订单
        Db::name('payorder')
            ->where('id', $payorder['id'])
            ->update([
                'status' => 1,
                'paytime' => time(),
                'transaction_id' => $transaction_id
            ]);
        
        // 执行支付后续逻辑
        $this->afterPay($payorder);
        
        Db::commit();
        
        // 返回成功
        echo json_encode(['code' => 'SUCCESS', 'message' => '成功']);
        
    } catch (\Exception $e) {
        Db::rollback();
        echo json_encode(['code' => 'FAIL', 'message' => $e->getMessage()]);
    }
    
    exit;
}
```

**支付宝回调**：
```php
public function alipayNotify() {
    $alipay = new Alipay();
    
    // 验证签名
    if (!$alipay->check()) {
        echo 'fail';
        exit;
    }
    
    $paynum = $_POST['out_trade_no'];
    $trade_no = $_POST['trade_no'];
    $trade_status = $_POST['trade_status'];
    
    if ($trade_status != 'TRADE_SUCCESS') {
        echo 'success';
        exit;
    }
    
    // 查询支付订单
    $payorder = Db::name('payorder')
        ->where('paynum', $paynum)
        ->find();
    
    if (!$payorder) {
        echo 'fail';
        exit;
    }
    
    if ($payorder['status'] == 1) {
        echo 'success';
        exit;
    }
    
    Db::startTrans();
    try {
        // 更新支付订单
        Db::name('payorder')
            ->where('id', $payorder['id'])
            ->update([
                'status' => 1,
                'paytime' => time(),
                'transaction_id' => $trade_no
            ]);
        
        // 执行支付后续逻辑
        $this->afterPay($payorder);
        
        Db::commit();
        
        echo 'success';
        
    } catch (\Exception $e) {
        Db::rollback();
        echo 'fail';
    }
    
    exit;
}
```

### 7. 支付后续处理

```php
protected function afterPay($payorder) {
    $type = $payorder['type'];
    $ordernum = $payorder['ordernum'];
    
    // 根据订单类型执行不同逻辑
    switch ($type) {
        case 'shop':  // 商城订单
            // 更新订单状态
            Db::name('shop_order')
                ->where('ordernum', $ordernum)
                ->update([
                    'status' => 1,
                    'paytime' => time(),
                    'paytype' => $payorder['paytypeid']
                ]);
            
            // 发送支付成功通知
            // 分销佣金计算
            // 积分赠送
            break;
        
        case 'recharge':  // 余额充值
            // 增加会员余额
            \app\common\Member::addmoney(
                $payorder['aid'],
                $payorder['mid'],
                $payorder['money'],
                '余额充值'
            );
            break;
        
        case 'membercard':  // 会员卡购买
            // 开通会员卡
            break;
        
        // 其他订单类型...
    }
}
```

### 8. 退款处理

**微信退款**：
```php
public function wxpayRefund() {
    $orderId = input('param.orderid/d');
    
    $order = Db::name('shop_order')
        ->where('aid', aid)
        ->where('id', $orderId)
        ->find();
    
    $payorder = Db::name('payorder')
        ->where('ordernum', $order['ordernum'])
        ->where('status', 1)
        ->find();
    
    $wxpay = new WxPayV3();
    $result = $wxpay->refund(
        $payorder['transaction_id'],  // 微信交易号
        $payorder['paynum'],           // 商户订单号
        $payorder['money'],            // 退款金额
        $payorder['money']             // 原订单金额
    );
    
    if ($result['code'] == 1) {
        // 更新支付订单状态
        Db::name('payorder')
            ->where('id', $payorder['id'])
            ->update(['status' => 2]);
        
        // 更新业务订单
        Db::name('shop_order')
            ->where('id', $orderId)
            ->update(['refund_status' => 2]);
        
        return json(['status' => 1, 'msg' => '退款成功']);
    } else {
        return json(['status' => 0, 'msg' => '退款失败：' . $result['msg']]);
    }
}
```

**支付宝退款**：
```php
public function alipayRefund() {
    $orderId = input('param.orderid/d');
    
    $order = Db::name('shop_order')
        ->where('aid', aid)
        ->where('id', $orderId)
        ->find();
    
    $payorder = Db::name('payorder')
        ->where('ordernum', $order['ordernum'])
        ->where('status', 1)
        ->find();
    
    $alipay = new Alipay();
    $result = $alipay->refund(
        $payorder['transaction_id'],  // 支付宝交易号
        $payorder['money']             // 退款金额
    );
    
    if ($result['code'] == 1) {
        // 更新支付订单状态
        Db::name('payorder')
            ->where('id', $payorder['id'])
            ->update(['status' => 2]);
        
        // 更新业务订单
        Db::name('shop_order')
            ->where('id', $orderId)
            ->update(['refund_status' => 2]);
        
        return json(['status' => 1, 'msg' => '退款成功']);
    } else {
        return json(['status' => 0, 'msg' => '退款失败：' . $result['msg']]);
    }
}
```

## 重要规则

### ✅ 必须做到

1. **幂等性保证**
```php
// 回调处理前检查订单状态
if ($payorder['status'] == 1) {
    echo 'success';  // 已处理，直接返回成功
    exit;
}
```

2. **事务保证**
```php
// 支付回调和后续处理必须在事务中
Db::startTrans();
try {
    // 更新订单
    // 执行业务逻辑
    Db::commit();
} catch (\Exception $e) {
    Db::rollback();
}
```

3. **日志记录**
```php
// 记录支付日志
Db::name('wxpay_log')->insert([
    'aid' => aid,
    'paynum' => $paynum,
    'data' => json_encode($data),
    'createtime' => time()
]);
```

4. **签名验证**
```php
// 回调必须验证签名
if (!$wxpay->verifySign()) {
    echo 'FAIL';
    exit;
}
```

### ❌ 禁止行为

1. **不要跳过回调验证**
2. **不要在回调中抛出异常**
3. **不要重复处理已支付订单**
4. **不要在回调中返回错误响应（除非真的失败）**

## 检查清单

支付功能开发前：
- [ ] 支付配置已设置（AppID、密钥、证书）
- [ ] 回调URL已配置且可访问
- [ ] 支付订单表设计完整
- [ ] 幂等性处理已实现
- [ ] 事务处理已实现
- [ ] 日志记录已实现
- [ ] 退款功能已实现
- [ ] 安全验证已实现

## 相关文件

- 支付API：`app/controller/ApiPay.php`
- 微信支付V3：`extend/pay/wechatpay/WxPayV3.php`
- 支付宝支付：`app/common/Alipay.php`
- 支付回调：`app/common/Notify.php`

## 下一步

支付功能开发完成后：
1. 在沙箱环境测试
2. 验证回调处理正确性
3. 测试退款功能
4. 压力测试
5. 上线生产环境
