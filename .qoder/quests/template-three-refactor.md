# 模板三 PC端页面重构设计

## 1. 概述

模板三官网的「空间管理」「余额充值」「积分充值」「个人中心」四个页面当前采用移动端优先的布局策略，在PC端（≥1024px）仍呈现单列窄布局、卡片密度低、屏幕利用率不足等H5风格问题。本次重构旨在为这四个页面提供原生PC级的视觉与交互体验，同时保持移动端样式不受影响。

### 1.1 涉及文件

| 类型 | 文件路径 |
|------|----------|
| 页面模板 | `app/view/index3/user_storage.html` |
| 页面模板 | `app/view/index3/recharge.html` |
| 页面模板 | `app/view/index3/score_shop.html` |
| 页面模板 | `app/view/index3/user_center.html` |
| 样式文件 | `static/index3/css/pay.css` |
| 响应式样式 | `static/index3/css/responsive.css` |
| 公共组件 | `app/view/index3/public/header.html` |
| 公共组件 | `app/view/index3/public/sidebar.html` |
| JS模块 | `static/index3/js/pay.js` |
| JS模块 | `static/index3/js/api.js` |
| 后端控制器 | `app/controller/Index.php`（h5_pay / _pcPay / pay_config / alipay_return） |

### 1.2 设计约束

- 模板三PC端资源（`/app/view/index3/` 和 `/static/index3/`）必须独立演进，不受其他端影响
- 前端所有价格展示使用「{price}积分」格式，价格为0时显示「免费」
- 个人中心PC端必须采用双栏仪表盘布局（左60%/右40%）
- header登录态须显示用户头像、余额积分摘要、用户信息下拉弹窗
- 所有PC端样式变更必须通过媒体查询 `@media (min-width: 1024px)` 隔离，不影响移动端
- 充值/积分购买/创作会员订阅必须通过 `Pay.startPay()` 弹出统一支付弹窗，禁止跳转后台页面
- PC端支付采用前后端协同模式：微信走 V3 Native（二维码扫码），支付宝走 page.pay（新窗口表单提交）
- 订阅弹窗（`showSubscriptionPopup`）必须完全依赖 `pay.css` 定义的统一样式，禁止内联样式

## 2. 架构

### 2.1 页面整体布局结构

``mermaid
graph LR
    A[layout-wrapper] --> B[sidebar 侧边栏]
    A --> C[main-content]
    C --> D[header 顶部栏]
    C --> E[page-content 内容区]
    E --> F[各页面特有组件]
```

### 2.2 响应式断点策略

| 断点 | 范围 | sidebar 状态 | 内容区布局 |
|------|------|-------------|-----------|
| 移动端 | <768px | 抽屉式隐藏 | 单列，全宽 |
| 平板端 | 768px–1023px | 抽屉式隐藏 | 单列，居中 |
| PC端 | 1024px–1439px | 折叠为60px图标栏 | 多列/双栏布局 |
| 大屏端 | ≥1440px | 展开为侧边导航 | 多列/双栏布局，间距更大 |

### 2.3 样式组织

所有PC端重构样式统一写入 `pay.css` 文件的 `@media (min-width: 1024px)` 媒体查询块内，与现有PC端样式规则合并。空间管理页面的内联样式提取到 `pay.css` 中统一管理。

## 3. 各页面PC端重构方案

### 3.1 个人中心（user_center.html）

#### 当前问题
- 双栏布局已存在（`uc-dashboard-grid` 3fr:2fr），但左栏菜单仍为移动端列表风格
- 用户资料卡高度不够突出
- 资产概览区未充分利用空间

#### PC端目标布局

``mermaid
graph TD
    subgraph "个人中心 PC布局（max-width: 1100px）"
        A[用户资料卡 — 全宽横幅]
        subgraph "双栏仪表盘（左60% 右40%）"
            direction LR
            subgraph "左栏"
                B[资产概览 3列网格]
                C[创作会员卡片]
                D[资产管理菜单组 — 网格化卡片]
                E[帮助支持菜单组 — 网格化卡片]
                F[退出登录按钮]
            end
            subgraph "右栏"
                G[快捷入口卡片列表]
            end
        end
    end
```

#### PC端样式调整要点

| 组件 | 当前状态 | PC端目标 |
|------|---------|---------|
| `uc-profile-card` | flex横排，padding 32px | 增大内边距至 36px 48px，头像升至 96px，增加背景装饰面积 |
| `uc-asset-grid` | 3列 grid | 保持3列，增大卡片内边距至 24px，数值字号 30px |
| `uc-menu-list` | 上下堆叠列表项 | 改为 2列 grid 布局，每个菜单项以独立卡片呈现，带图标底色圆形 |
| `uc-dashboard-grid` | 已有 3fr:2fr | 保持不变，增加 gap 至 28px |
| `uc-quick-entries` | 纵向卡片列表 | 保持纵向排列，增加悬浮阴影效果和图标尺寸 |
| 退出登录按钮 | 全宽按钮 | 缩小宽度为 auto，右对齐 |

#### 菜单网格化方案

当前菜单项为列表形式（上下排列带分隔线），PC端改为2列网格独立卡片：

| 属性 | 值 |
|------|-----|
| 容器布局 | `display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px` |
| 卡片样式 | 独立圆角卡片（border-radius: 14px），带边框和轻微阴影 |
| 图标 | 彩色圆形底色（40×40px），居左显示 |
| 悬浮效果 | translateY(-2px) + 阴影增强 |
| 列表分隔线 | PC端隐藏 `border-bottom: none` |

### 3.2 余额充值（recharge.html）

#### 当前问题
- 页面 max-width: 960px 单列布局
- Hero卡片 + 档位选择 + 按钮纵向堆叠，PC端空间浪费大
- 充值按钮全宽，不像PC端操作

#### PC端目标布局

``mermaid
graph TD
    subgraph "余额充值 PC布局（max-width: 1000px）"
        A["页面标题 💰 余额充值"]
        B["左右双栏布局"]
        subgraph "左侧主栏（65%）"
            C["充值档位选择（page-card）— 4列网格"]
            D["自定义金额输入"]
        end
        subgraph "右侧信息栏（35%）"
            E["当前余额 Hero 卡片"]
            F["立即充值按钮"]
            G["安全支付提示"]
        end
    end
```

#### PC端样式调整要点

| 组件 | 当前状态 | PC端目标 |
|------|---------|---------|
| 整体布局 | 单列纵向 | 双栏：左侧65%放档位选择，右侧35%放余额信息+支付按钮 |
| `hero-card` | 全宽横幅 | 缩窄至右侧栏内，紧凑布局，hero-value字号缩至 36px |
| `amount-grid` | 已有PC端4列 | 保持4列，档位卡片增加 hover 缩放效果 |
| 充值按钮 | 全宽 `width:100%` | 在右侧栏内全宽，配合右栏 `position: sticky` 实现跟随滚动 |
| 自定义金额 | flex横排 | 保持不变，放在档位网格下方 |

#### 双栏包裹结构

需要在 recharge.html 模板中增加一个 PC端双栏容器：

| 容器 | 属性描述 |
|------|---------|
| `.recharge-pc-layout` | PC端 display:grid, grid-template-columns: 1fr 340px, gap: 24px; 移动端 display:block |
| 左栏 `.recharge-main` | 包含充值档位 page-card 和自定义金额 |
| 右栏 `.recharge-aside` | 包含 hero-card + 充值按钮，PC端 position:sticky; top:90px |

### 3.3 积分充值（score_shop.html）

#### 当前问题
与余额充值页面结构几乎一致，同样存在单列布局问题。

#### PC端目标布局
复用与余额充值相同的双栏布局策略：

| 组件 | PC端目标 |
|------|---------|
| 整体布局 | 双栏：左65%档位选择 + 右35%当前积分+购买按钮 |
| `hero-card--green` | 移至右侧栏，紧凑显示当前积分 |
| `amount-grid--green` | 保持4列 grid |
| 购买按钮 | 在右侧栏内，sticky 跟随 |

需要在 score_shop.html 模板中同样增加双栏容器 `.score-pc-layout`，结构与余额充值保持一致。

### 3.4 空间管理（user_storage.html）

#### 当前问题
- 所有样式以内联 `<style>` 标签写在 HTML 内，未提取到 CSS 文件
- 文件网格 `file-grid` 已有 auto-fill 但卡片过小
- 存储概览区统计信息密度低
- Lightbox 预览缺少PC端增强
- 批量操作栏（batch-bar）为底部全宽浮动条，PC端可改为工具栏

#### PC端目标布局

``mermaid
graph TD
    subgraph "空间管理 PC布局（max-width: 1100px）"
        A["存储概览（storage-overview）— 全宽横幅"]
        B["告警/过期 Banner"]
        C["操作工具栏：筛选标签 + 批量操作按钮 + 排序 + 视图切换"]
        D["文件网格 — 5~6列 grid，卡片 180px min"]
        E["加载更多 / 分页"]
    end
```

#### PC端样式调整要点

| 组件 | 当前状态 | PC端目标 |
|------|---------|---------|
| 内联样式 | 约180行 `<style>` 内联 | 提取到 `pay.css` 的PC端媒体查询块中 |
| `storage-overview` | 单列纵排统计 | 增大内边距，统计区域（so-stats）改为4列，显示更多维度 |
| `storage-filter` | flex 横排 wrap | 增加右侧排序下拉和视图切换（网格/列表）按钮 |
| `file-grid` | auto-fill minmax(150px,1fr) | PC端 minmax(200px,1fr)，确保5~6列 |
| `file-card` | 150px 最小宽度 | PC端增至 200px，缩略图 aspect-ratio 保持1:1，info区增加文件名显示 |
| `batch-bar` | sticky 底部全宽 | PC端改为筛选栏右侧内联按钮组，选中时高亮显示 |
| Lightbox | 基础全屏 | PC端增加左右间距，工具栏增加「删除」「分享」操作 |

#### 内联样式提取计划

将 user_storage.html 的 `<style>` 标签内约180行样式迁移到 `pay.css` 中，按以下分类组织：

| 分类 | 包含样式 |
|------|---------|
| 存储概览区 | `.storage-overview` 及其子元素 |
| 告警横幅 | `.storage-warning-banner`、`.expiry-banner` |
| 筛选工具栏 | `.storage-filter`、`.sf-btn` |
| 文件网格 | `.file-grid`、`.file-card` 及所有子元素 |
| 文件倒计时 | `.fc-countdown` 系列 |
| 批量操作栏 | `.batch-bar` 系列 |
| Lightbox | `.lightbox-overlay` 及所有子元素 |
| 空状态/加载 | `.empty-state`、`.load-more-btn` |

### 3.5 PC端支付流程集成

#### 现有支付基础设施

PC端支付链路已建立，本次重构需确保四个页面正确接入此链路：

| 层级 | 组件 | 职责 |
|------|------|------|
| 前端入口 | `Pay.startPay(options)` | 统一支付发起入口，自动检测浏览器环境 |
| API层 | `Api.h5Pay(params)` | 发送请求到后端，默认携带 `platform: 'pc'` |
| API层 | `Api.getPayConfig()` | 获取PC端可用支付方式（校验V3配置字段） |
| 后端路由 | `Index::h5_pay()` | 统一入口，按 platform 参数分流 |
| 后端逻辑 | `Index::_pcPay()` | PC专属支付：微信V3 Native / 支付宝page.pay |
| 后端回调 | `Index::alipay_return()` | 支付宝同步回跳端点 |
| 前端轮询 | `Api.checkPayStatus()` | 每2秒轮询，最大120次（4分钟超时） |

#### PC端支付方式对照

| 支付方式 | 后端实现 | 前端 pay_method | 前端行为 |
|---------|---------|----------------|----------|
| 微信支付 | V3 Native 下单 | `qrcode` | 弹窗内展示二维码图片，用户手机扫码 |
| 支付宝 | 电脑网站支付（page.pay） | `form` | 新窗口渲染表单HTML并自动提交，跳转支付宝收银台 |

#### 支付弹窗交互流程

``mermaid
sequenceDiagram
    participant U as 用户
    participant Page as 充值/积分页面
    participant PayJS as Pay.startPay()
    participant ApiJS as Api 模块
    participant Backend as Index 控制器
    participant PayModal as 支付弹窗

    U->>Page: 点击「立即充值/购买」
    Page->>ApiJS: 创建订单（createRechargeOrder / createScoreOrder）
    ApiJS->>Backend: POST 创建订单
    Backend-->>ApiJS: 返回 ordernum
    ApiJS-->>Page: 回调返回订单号
    Page->>PayJS: Pay.startPay({ordernum, order_type, amount})
    PayJS->>ApiJS: Api.getPayConfig({platform:'pc'})
    ApiJS->>Backend: GET pay_config
    Backend-->>ApiJS: 返回可用支付方式列表（校验V3配置）
    ApiJS-->>PayJS: 支付方式列表

    alt 仅一种支付方式
        PayJS->>ApiJS: Api.h5Pay({ordernum, pay_type, platform:'pc'})
    else 多种支付方式
        PayJS->>PayModal: 渲染支付方式选择弹窗
        U->>PayModal: 选择支付方式
        PayModal->>ApiJS: Api.h5Pay({ordernum, pay_type, platform:'pc'})
    end

    ApiJS->>Backend: POST h5_pay (platform=pc)
    Backend->>Backend: _pcPay() 分流处理

    alt 微信 V3 Native
        Backend-->>ApiJS: {pay_method:'qrcode', qrcode_url}
        ApiJS-->>PayModal: 显示微信二维码
        U->>U: 手机扫码支付
    else 支付宝 page.pay
        Backend-->>ApiJS: {pay_method:'form', form_html}
        ApiJS-->>PayJS: 新窗口渲染表单并自动提交
        U->>U: 在支付宝页面完成支付
    end

    loop 每2秒轮询（最多120次）
        PayJS->>ApiJS: Api.checkPayStatus({ordernum})
        ApiJS->>Backend: GET check_pay_status
        Backend-->>ApiJS: {paid: true/false}
    end

    PayJS->>PayModal: 显示支付成功
    PayJS->>Page: onSuccess 回调
    Page->>Page: Auth.checkLogin() + 刷新页面
```

#### 各页面支付集成点

| 页面 | 触发入口 | 订单创建接口 | order_type | 支付成功后行为 |
|------|---------|------------|------------|---------------|
| 余额充值 | 「立即充值」按钮 | `Api.createRechargeOrder` | `recharge` | 刷新余额，重载页面 |
| 积分购买 | 「立即购买」按钮 | `Api.createScoreOrder` | `score` | 刷新积分，重载页面 |
| 创作会员（个人中心/header） | 订阅弹窗内按钮 | `Api.buyCreativeMember` | `creative_member` | 刷新登录态，重载页面 |
| 会员等级 | 等级卡片内购买按钮 | `Api.applyLevel` | `level` | 刷新等级信息 |

#### 支付宝同步回跳处理

支付宝 page.pay 完成后，浏览器会跳转到 `alipay_return` 端点：

| 步骤 | 说明 |
|------|------|
| 1. 接收参数 | 从 URL 参数中获取 `out_trade_no`、`trade_no` |
| 2. 查询订单 | 检查 payorder 表中订单状态 |
| 3. 重定向 | 跳转回充值页面，带 `pay_result=success/pending` 参数 |
| 4. 页面展示 | 充值页面根据 URL 参数展示支付结果提示 |

> 注意：同步回跳仅用于用户引导，不作为支付成功判断依据。实际支付状态以异步通知（notify）为准。

## 4. 组件层级关系

``mermaid
graph TD
    subgraph "公共组件"
        Sidebar[sidebar.html]
        Header[header.html]
        Tabbar[tabbar.html]
    end

    subgraph "页面组件"
        UC[user_center.html]
        RC[recharge.html]
        SS[score_shop.html]
        US[user_storage.html]
    end

    subgraph "样式文件"
        PayCSS[pay.css]
        RespCSS[responsive.css]
        IndexCSS[index.css]
        SideCSS[sidebar.css]
    end

    subgraph "JS模块"
        ApiJS[api.js]
        AuthJS[auth.js]
        PayJS[pay.js]
        SideJS[sidebar.js]
    end

    UC --> Sidebar & Header & Tabbar
    RC --> Sidebar & Header & Tabbar
    SS --> Sidebar & Header & Tabbar
    US --> Sidebar & Header & Tabbar

    UC --> PayCSS & RespCSS & IndexCSS
    RC --> PayCSS & RespCSS & IndexCSS
    SS --> PayCSS & RespCSS & IndexCSS
    US --> PayCSS & RespCSS & IndexCSS

    UC --> ApiJS & AuthJS & PayJS & SideJS
    RC --> ApiJS & AuthJS & PayJS & SideJS
    SS --> ApiJS & AuthJS & PayJS & SideJS
    US --> ApiJS & AuthJS & SideJS
```

## 5. 交互流程

### 5.1 充值页面PC端完整交互流程

``mermaid
sequenceDiagram
    participant U as 用户
    participant P as 充值页面
    participant API as 后端API
    participant PayJS as Pay模块
    participant PayModal as 支付弹窗

    U->>P: 进入充值页面
    P->>API: Auth.checkLogin()
    API-->>P: 返回用户信息（余额/积分）
    P->>P: 渲染双栏布局（左侧档位 + 右侧信息栏）
    P->>API: Api.getRechargeConfig()
    API-->>P: 返回档位列表
    P->>P: 渲染4列档位网格
    U->>P: 点击选择档位
    P->>P: 右侧栏按钮高亮，显示金额
    U->>P: 点击「立即充值」
    P->>API: Api.createRechargeOrder({money})
    API-->>P: 返回 {ordernum}
    P->>PayJS: Pay.startPay({ordernum, order_type:'recharge', amount})
    PayJS->>API: Api.getPayConfig({platform:'pc'})
    API-->>PayJS: 返回可用支付方式
    PayJS->>PayModal: 渲染支付方式选择弹窗
    U->>PayModal: 选择微信/支付宝
    PayModal->>API: Api.h5Pay({ordernum, pay_type, platform:'pc'})
    API-->>PayModal: 返回 qrcode / form 数据

    alt 微信（qrcode）
        PayModal->>PayModal: 展示二维码
        U->>U: 手机扫码
    else 支付宝（form）
        PayModal->>PayModal: 新窗口表单提交
        U->>U: 支付宝页面完成支付
    end

    loop 轮询
        PayJS->>API: Api.checkPayStatus()
        API-->>PayJS: paid=true
    end

    PayJS->>P: onSuccess回调
    P->>P: Auth.checkLogin() + location.reload()
```

### 5.2 空间管理PC端交互流程

``mermaid
sequenceDiagram
    participant U as 用户
    participant S as 空间管理页面
    participant API as 后端API

    U->>S: 进入空间管理
    S->>API: 获取文件列表+存储信息
    API-->>S: 返回文件列表、存储概览
    S->>S: 渲染5-6列文件网格
    U->>S: 筛选（类型/来源）
    S->>API: 重新请求过滤后数据
    API-->>S: 返回过滤结果
    S->>S: 重新渲染网格
    U->>S: 点击文件缩略图
    S->>S: 打开 Lightbox 全屏预览
    U->>S: 框选多个文件
    S->>S: 工具栏显示已选数量 + 批量操作按钮
    U->>S: 点击批量下载
    S->>S: 逐个触发下载（最多20个）
```

## 6. PC端视觉规范

### 6.1 间距与尺寸

| 属性 | 移动端 | PC端（≥1024px） | 大屏端（≥1440px） |
|------|--------|-----------------|-------------------|
| page-content 内边距 | 16px 12px | 32px 24px | 40px 32px |
| 卡片圆角 | 14px-16px | 18px-20px | 20px |
| 卡片内边距 | 18px-24px | 28px-32px | 32px-36px |
| 网格间距 | 10px-12px | 16px-20px | 20px-24px |
| 标题字号 | 18px-20px | 22px-24px | 24px-26px |

### 6.2 配色方案（继承现有设计）

| 用途 | CSS 变量 | 值 |
|------|---------|-----|
| 主色（按钮/强调） | `--color-primary` | #6366f1 |
| 成功/积分色 | `--color-green` | #10b981 |
| 警告/会员色 | `--color-amber` | #f59e0b |
| 卡片背景 | `--bg-primary` | #fff（浅色）/ 主题变量 |
| 边框 | `--border-color` | #eee |
| 卡片阴影 | `--card-shadow` | 0 1px 3px rgba(0,0,0,.06) |
| 卡片hover阴影 | `--card-shadow-hover` | 0 10px 25px rgba(0,0,0,.08) |

## 7. 测试策略

### 7.1 视觉回归测试

| 测试场景 | 验证点 |
|---------|--------|
| 移动端（375px）查看4个页面 | 确认布局未受PC重构影响，保持原有H5样式 |
| 平板端（768px）查看4个页面 | 确认过渡布局正常 |
| PC端（1024px）查看4个页面 | 验证双栏布局、网格列数、间距尺寸符合设计 |
| 大屏端（1440px）查看4个页面 | 验证大屏适配，间距增大 |
| 深色主题下查看4个页面 | 确认深色模式变量正确应用 |

### 7.2 功能测试

| 测试场景 | 验证点 |
|---------|--------|
| 余额充值 — 选择档位 | 选中态视觉反馈正确，右侧栏按钮更新金额 |
| 积分购买 — 选择套餐 | 选中态视觉反馈正确，右侧栏按钮更新价格 |
| 空间管理 — 筛选文件 | 类型/来源筛选后网格正确重渲染 |
| 空间管理 — 批量选择 | 勾选多个文件，工具栏显示已选数 |
| 空间管理 — Lightbox预览 | 键盘左右切换、关闭、下载正常 |
| 个人中心 — 菜单点击 | 网格化菜单项点击跳转正确 |
| 个人中心 — 登出 | 退出登录后正确跳转 |
| 余额充值 — 微信支付 | Pay.startPay 被真实调用，弹窗展示二维码，轮询检测到支付成功后自动关闭 |
| 余额充值 — 支付宝支付 | 新窗口成功渲染表单并提交到支付宝，轮询检测支付结果 |
| 积分购买 — 支付全流程 | 同余额充值，验证 order_type 为 score |
| 创作会员 — 订阅支付 | showSubscriptionPopup 弹窗内点击购买后调用 Pay.startPay()，禁止跳转后台 |
| 支付宝回跳 — alipay_return | 支付宝完成后正确重定向至充值页面，URL带 pay_result 参数 |
| 弹窗拦截 — 新窗口 | 支付宝 form 方式需新窗口，验证浏览器弹窗拦截时的友好提示 |

### 7.3 浏览器兼容性

| 浏览器 | 最低版本 |
|--------|--------|
| Chrome | 80+ |
| Firefox | 78+ |
| Safari | 13+ |
| Edge | 80+ |
