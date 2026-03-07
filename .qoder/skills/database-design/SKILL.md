---
name: database-design
description: 数据库设计规范和最佳实践。用于创建数据表、设计字段、建立索引、优化查询。遵循ID规范、时间字段规范、状态字段规范。适用于点大商城系统数据库开发。
---

# 数据库设计规范

点大商城使用MySQL数据库，遵循标准化设计原则和性能优化最佳实践。

## 快速开始

### 创建数据表
```sql
CREATE TABLE `shop_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
  `bid` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID',
  `cid` int(11) NOT NULL DEFAULT '0' COMMENT '分类ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `pic` varchar(500) DEFAULT NULL COMMENT '主图',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `stock` int(11) NOT NULL DEFAULT '0' COMMENT '库存',
  `sales` int(11) NOT NULL DEFAULT '0' COMMENT '销量',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0下架 1上架',
  `createtime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatetime` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `bid` (`bid`),
  KEY `cid` (`cid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表';
```

## 核心规范

### 1. ID字段规范

系统统一使用的ID字段：

| 字段名 | 含义 | 说明 |
|--------|------|------|
| `aid` | 平台ID | Admin ID，所有数据必须关联 |
| `bid` | 商家ID | Business ID，商家相关数据 |
| `mid` | 会员ID | Member ID，会员相关数据 |
| `uid` | 管理员ID | User ID，后台管理员 |
| `mdid` | 门店ID | Mendian ID，门店相关数据 |

**重要原则**：
- 所有业务数据表必须包含`aid`字段
- `aid`字段必须建立索引
- 所有查询必须带`aid`过滤条件

```sql
-- ✅ 正确：所有表都有aid
CREATE TABLE `member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT '0',
  -- 其他字段...
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ❌ 错误：缺少aid字段
CREATE TABLE `member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  -- 缺少aid
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 2. 时间字段规范

统一使用Unix时间戳（整型）：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| `createtime` | int(11) | 创建时间 |
| `updatetime` | int(11) | 更新时间 |
| `paytime` | int(11) | 支付时间 |
| `endtime` | int(11) | 结束时间 |

```sql
-- ✅ 正确：使用int存储时间戳
`createtime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
`updatetime` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',

-- ❌ 错误：不使用datetime类型
-- `createtime` datetime DEFAULT NULL,
```

**PHP中使用**：
```php
// 插入数据
$data = [
    'createtime' => time(),
    'updatetime' => time()
];

// 查询时间范围
$startTime = strtotime('2024-01-01');
$endTime = strtotime('2024-12-31');

$list = Db::name('shop_order')
    ->where('createtime', '>=', $startTime)
    ->where('createtime', '<=', $endTime)
    ->select();
```

### 3. 状态字段规范

统一使用`status`字段，类型为`tinyint(1)`：

**订单状态**：
```sql
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单状态 0待付款 1待发货 2待收货 3已完成 4已关闭',
```

**审核状态**：
```sql
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '审核状态 0待审核 1已通过 2已驳回 3已关闭',
```

**启用状态**：
```sql
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态 0禁用 1启用',
```

**支付状态**：
```sql
`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '支付状态 0未支付 1已支付 2已退款',
```

### 4. 字符集和引擎

**统一使用**：
- 引擎：`InnoDB`
- 字符集：`utf8mb4`
- 排序规则：`utf8mb4_general_ci`

```sql
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='表注释';
```

### 5. 字段类型选择

| 数据类型 | 字段类型 | 示例 |
|----------|----------|------|
| 整数 | int(11) | id, aid, mid |
| 小整数 | tinyint(1) | status, type |
| 金额 | decimal(10,2) | price, money |
| 短文本 | varchar(255) | name, mobile |
| 长文本 | text | content, remark |
| 超长文本 | longtext | detail |
| JSON数据 | text | data, params |

**示例**：
```sql
`id` int(11) NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
`mobile` varchar(20) DEFAULT NULL COMMENT '手机号',
`money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
`status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
`content` text COMMENT '内容',
`data` text COMMENT 'JSON数据',
```

### 6. 索引设计

**单字段索引**：
```sql
KEY `aid` (`aid`),
KEY `mid` (`mid`),
KEY `status` (`status`),
KEY `createtime` (`createtime`)
```

**组合索引**（遵循最左前缀原则）：
```sql
-- 查询条件：WHERE aid=? AND mid=? AND status=?
KEY `idx_aid_mid_status` (`aid`, `mid`, `status`)

-- 可以使用此索引的查询：
-- WHERE aid=?
-- WHERE aid=? AND mid=?
-- WHERE aid=? AND mid=? AND status=?
```

**唯一索引**：
```sql
UNIQUE KEY `uk_ordernum` (`ordernum`),
UNIQUE KEY `uk_mobile` (`aid`, `mobile`)
```

## 常见表设计模式

### 1. 主表设计

```sql
CREATE TABLE `shop_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '订单ID',
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
  `bid` int(11) NOT NULL DEFAULT '0' COMMENT '商家ID',
  `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `ordernum` varchar(50) NOT NULL DEFAULT '' COMMENT '订单号',
  `totalprice` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单状态',
  `createtime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `paytime` int(11) NOT NULL DEFAULT '0' COMMENT '支付时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ordernum` (`ordernum`),
  KEY `aid` (`aid`),
  KEY `mid` (`mid`),
  KEY `status` (`status`),
  KEY `createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表';
```

### 2. 关联表设计

```sql
CREATE TABLE `shop_order_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` int(11) NOT NULL DEFAULT '0' COMMENT '订单ID',
  `productid` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID',
  `num` int(11) NOT NULL DEFAULT '1' COMMENT '数量',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '单价',
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`),
  KEY `productid` (`productid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单商品表';
```

### 3. 日志表设计

```sql
CREATE TABLE `member_moneylog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
  `mid` int(11) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `oldmoney` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变动前金额',
  `newmoney` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '变动后金额',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  `createtime` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `mid` (`mid`),
  KEY `createtime` (`createtime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员余额日志';
```

### 4. 设置表设计

```sql
CREATE TABLE `admin_set` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
  `name` varchar(255) DEFAULT NULL COMMENT '网站名称',
  `logo` varchar(500) DEFAULT NULL COMMENT 'LOGO',
  `wxappid` varchar(100) DEFAULT NULL COMMENT '微信AppID',
  `wxsecret` varchar(100) DEFAULT NULL COMMENT '微信Secret',
  `data` text COMMENT '其他配置(JSON)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';
```

## 重要规则

### ✅ 必须做到

1. **所有表必须有主键**
```sql
PRIMARY KEY (`id`)
```

2. **所有业务表必须有aid字段**
```sql
`aid` int(11) NOT NULL DEFAULT '0' COMMENT '平台ID',
KEY `aid` (`aid`)
```

3. **金额字段使用decimal**
```sql
`price` decimal(10,2) NOT NULL DEFAULT '0.00'
```

4. **所有字段必须有注释**
```sql
`name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
```

5. **表必须有注释**
```sql
ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='商品表';
```

6. **常用查询字段必须建索引**
```sql
KEY `aid` (`aid`),
KEY `status` (`status`)
```

### ❌ 禁止行为

1. **不要使用外键约束**
```sql
-- ❌ 错误
FOREIGN KEY (`mid`) REFERENCES `member`(`id`)

-- ✅ 正确：应用层控制关联
```

2. **不要使用datetime类型**
```sql
-- ❌ 错误
`createtime` datetime DEFAULT NULL

-- ✅ 正确
`createtime` int(11) NOT NULL DEFAULT '0'
```

3. **不要使用NULL作为默认值**
```sql
-- ❌ 错误
`name` varchar(255) DEFAULT NULL

-- ✅ 正确
`name` varchar(255) NOT NULL DEFAULT ''
```

4. **不要使用保留字作为字段名**
```sql
-- ❌ 错误
`order`, `desc`, `group`

-- ✅ 正确
`ordernum`, `description`, `groupid`
```

## 性能优化

### 1. 索引优化

**查询分析**：
```sql
-- 查看执行计划
EXPLAIN SELECT * FROM shop_order 
WHERE aid=1 AND mid=123 AND status=1;

-- 查看索引使用情况
SHOW INDEX FROM shop_order;
```

**索引选择**：
```sql
-- 场景1：单字段高频查询
KEY `aid` (`aid`)

-- 场景2：多字段组合查询
KEY `idx_aid_mid` (`aid`, `mid`)

-- 场景3：范围查询
KEY `createtime` (`createtime`)
```

### 2. 分表策略

**按aid分表**（多平台数据）：
```
shop_order_1
shop_order_2
shop_order_3
```

**按时间分表**（大数据量）：
```
shop_order_202401
shop_order_202402
shop_order_202403
```

### 3. 查询优化

**避免全表扫描**：
```sql
-- ❌ 错误：没有索引字段
SELECT * FROM shop_order WHERE remark LIKE '%关键词%';

-- ✅ 正确：使用索引字段
SELECT * FROM shop_order WHERE aid=1 AND status=1;
```

**避免SELECT ***：
```sql
-- ❌ 错误
SELECT * FROM shop_order WHERE id=1;

-- ✅ 正确：只查需要的字段
SELECT id,ordernum,totalprice,status FROM shop_order WHERE id=1;
```

## 数据迁移

### 创建表
```sql
-- 1. 编写SQL文件
CREATE TABLE `new_table` (
  -- 字段定义
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. 执行SQL
mysql -u root -p database_name < create_table.sql
```

### 修改表结构
```sql
-- 添加字段
ALTER TABLE `shop_product` 
ADD COLUMN `views` int(11) NOT NULL DEFAULT '0' COMMENT '浏览量' AFTER `sales`;

-- 修改字段
ALTER TABLE `shop_product` 
MODIFY COLUMN `name` varchar(500) NOT NULL DEFAULT '' COMMENT '商品名称';

-- 添加索引
ALTER TABLE `shop_product` 
ADD KEY `idx_views` (`views`);

-- 删除索引
ALTER TABLE `shop_product` 
DROP KEY `idx_views`;
```

## 检查清单

创建新表前：
- [ ] 表名符合命名规范（小写+下划线）
- [ ] 包含主键字段`id`
- [ ] 包含平台ID字段`aid`
- [ ] 包含时间字段`createtime`
- [ ] 所有字段有注释
- [ ] 表有注释
- [ ] 常用查询字段建立索引
- [ ] 使用InnoDB引擎
- [ ] 使用utf8mb4字符集
- [ ] 金额字段使用decimal
- [ ] 状态字段使用tinyint

## 相关文件

- 数据库配置：`config/database.php`
- 数据库文档：`文档/原系统文档/数据库表单.md`
- 迁移脚本：`database/migrations/`

## 下一步

数据表设计完成后：
1. 编写数据访问代码
2. 创建对应的Model类
3. 编写单元测试
4. 更新数据库文档
5. 性能测试和优化
