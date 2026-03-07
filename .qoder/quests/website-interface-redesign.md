# 官网模板三 — AI图生图/图生视频创作平台

## 1. 概述

### 1.1 功能定位
全新构建系统官网"模板三"，定位为 **AI图片生成 & 视频生成创作平台** 的展示与入口门户。PC端采用左右分栏布局（左侧导航栏 20% + 右侧内容区 80%），移动端采用响应式自适应布局（侧边栏转为底部TabBar + 顶部抽屉导航）。提供模型广场展示、场景模板浏览、主题切换（浅色/深色）等能力，全终端统一一套页面，通过CSS媒体查询和少量JS判断实现自适应。

### 1.2 与现有模板的关系

| 项目 | 模板一 | 模板二 | 模板三（本次新增） |
|------|--------|--------|-------------------|
| showweb 值 | 1 | 2 | 3 |
| 视图目录 | `app/view/index/` | `app/view/index2/` | `app/view/index3/` |
| 静态资源目录 | `static/index/` | `static/index2/` | `static/index3/` |
| 定位 | 传统商城官网 | 商城官网（改版） | AI创作平台官网 |

### 1.3 数据源依赖

| 数据 | 来源表 | 筛选条件 |
|------|--------|----------|
| 模型广场卡片 | `model_info` + `model_provider` + `model_type` | status=1 |
| 图片生成场景分类 | `generation_scene_category` | generation_type=1, status=1 |
| 视频生成场景分类 | `generation_scene_category` | generation_type=2, status=1 |
| 图片生成场景模板卡片 | `generation_scene_template` | generation_type=1, status=1 |
| 视频生成场景模板卡片 | `generation_scene_template` | generation_type=2, status=1 |
| 官网配置 | `sysset` (name='webinfo') | — |

---

## 2. 架构

### 2.1 整体页面结构

```
graph LR
    subgraph 页面框架["页面框架 (100vw × 100vh)"]
        subgraph 左侧栏["左侧导航栏 (20%)"]
            A1[LOGO + 系统名称 + 折叠按钮]
            A2[一级菜单: 首页]
            A3["创作 (含二级: 图片生成 / 视频生成)"]
            A4[资产]
            A5[--- 分割线 ---]
            A6[个人中心]
            A7[创作中心]
            A8[--- 分割线 ---]
            A9[公司信息 / 备案信息]
            A10["底部工具栏 (主题切换 / APP下载 / 更多菜单)"]
        end
        subgraph 右侧内容区["右侧内容区 (80%)"]
            B1["顶部固定栏: 搜索框 | 邀请有礼 | 会员中心 | 登录/注册"]
            B2[模型广场区域 — 模型卡片列表]
            B3["TAB页: 图片模型 | 视频特效"]
        end
    end
```

### 2.2 控制器路由策略

当前 `Index` 控制器根据 `showweb` 值选择模板视图。模板三需要在路由分发中新增 `showweb==3` 的判断分支，将请求导向 `index3/` 目录下的视图文件。

路由分发优先级（从高到低）：
1. `showweb==3` → 渲染 `index3/index.html`（同一页面，通过CSS响应式适配PC/平板/手机）
2. `showweb==2` → 渲染 `index2/` 视图
3. `showweb==1`（默认） → 渲染 `index/` 视图

> **注意**：模板三不再使用独立的 `wap/` 移动端视图，而是采用响应式单页方案，同一套HTML通过断点适配所有终端。

### 2.3 后台配置联动

在 `web_system/set.html` 的官网模板选择区域，新增 `showweb=3` 选项"模板三"，放置在"模板二"之后、"关闭"之前。

---

## 3. 目录结构

新建文件按照现有模板一、模板二的约定放置：

```
app/view/index3/
├── index.html              # 首页主框架（响应式单页，适配所有终端）
├── public/
│   ├── sidebar.html        # 左侧导航栏组件（PC侧边栏 / 移动端抽屉）
│   ├── header.html         # 顶部固定栏组件
│   └── tabbar.html         # 移动端底部TabBar组件
├── help.html               # 帮助中心
├── helpdetail.html         # 帮助详情
├── lianxi.html             # 联系我们
└── downloadapp.html        # APP下载

static/index3/
├── css/
│   ├── index.css           # 主样式（含响应式断点）
│   ├── responsive.css      # 响应式布局专用样式
│   ├── sidebar.css         # 侧边栏样式
│   ├── theme-light.css     # 浅色主题变量
│   └── theme-dark.css      # 深色主题变量
├── js/
│   ├── index.js            # 主逻辑
│   ├── sidebar.js          # 侧边栏折叠/展开/抽屉
│   ├── theme.js            # 主题切换
│   └── api.js              # 数据请求封装
└── img/                    # 图片资源
```

---

## 4. 组件架构

### 4.1 组件层级

```
graph TD
    Root[index3/index.html 主框架]
    Root --> Sidebar[sidebar.html 左侧导航栏]
    Root --> Main[右侧主内容区]
    
    Sidebar --> LogoBlock[Logo区块]
    Sidebar --> NavMenu[导航菜单组]
    Sidebar --> CompanyInfo[公司信息区块]
    Sidebar --> ToolBar[底部工具栏]
    
    LogoBlock --> Logo[系统LOGO图]
    LogoBlock --> SysName[系统名称]
    LogoBlock --> CollapseBtn[折叠按钮]
    
    NavMenu --> MenuItem_Home[首页]
    NavMenu --> MenuItem_Create["创作 (可展开)"]
    MenuItem_Create --> SubMenu_Photo[图片生成]
    MenuItem_Create --> SubMenu_Video[视频生成]
    NavMenu --> MenuItem_Assets[资产]
    NavMenu --> Divider1[分割线]
    NavMenu --> MenuItem_Profile[个人中心]
    NavMenu --> MenuItem_Studio[创作中心]
    
    CompanyInfo --> Beian[ICP备案信息]
    CompanyInfo --> GongAnBeian[公安备案信息]
    
    ToolBar --> ThemeToggle["浅色/深色切换"]
    ToolBar --> AppDownload["APP下载 (悬浮显示二维码)"]
    ToolBar --> MoreMenu["更多菜单 (悬浮弹出)"]
    
    MoreMenu --> UserAgreement[用户协议]
    MoreMenu --> Privacy[隐私协议]
    MoreMenu --> AboutUs[关于我们]
    MoreMenu --> JoinUs[加入我们]
    MoreMenu --> ContactUs[联系我们]
    MoreMenu --> ContactCS[联系客服]
    MoreMenu --> ShareDivider[分割线]
    MoreMenu --> ShareBtns["微信 / 小红书 / 抖音"]
    
    Main --> HeaderBar[顶部固定栏]
    Main --> ModelSquare[模型广场区域]
    Main --> TabSection[TAB切换区域]
    
    HeaderBar --> SearchBox[搜索框]
    HeaderBar --> InviteBtn[邀请有礼]
    HeaderBar --> VipBtn[会员中心]
    HeaderBar --> AuthBtn["登录/注册"]
    
    TabSection --> Tab_Photo["TAB: 图片模型"]
    TabSection --> Tab_Video["TAB: 视频特效"]
    
    Tab_Photo --> PhotoCategoryBar[图片场景分类行]
    Tab_Photo --> PhotoSceneCards[图片场景模板卡片网格]
    
    Tab_Video --> VideoCategoryBar[视频场景分类行]
    Tab_Video --> VideoSceneCards[视频场景模板卡片网格]
```

### 4.2 核心组件说明

#### 4.2.1 左侧导航栏 (Sidebar)

| 属性 | 说明 |
|------|------|
| 宽度 | 默认 20%（展开态），折叠后仅显示图标（约 60px） |
| 固定方式 | `position: fixed; left: 0; top: 0; height: 100vh` |
| LOGO来源 | 从 `webinfo.ico` / `webinfo.logo` 读取 |
| 系统名称 | 从 `webinfo.webname` 读取 |
| 折叠按钮 | 位于LOGO栏右侧，点击后左栏收缩为图标模式（约 60px），右栏自动扩展；折叠态下隐藏该按钮 |
| LOGO点击 | **折叠态下**，点击LOGO图标可展开侧边栏恢复全部显示（20%宽度）；**展开态下**，点击LOGO无特殊行为 |
| 菜单高亮 | 根据当前路由/锚点自动高亮对应菜单项 |
| 创作菜单展开 | 点击"创作"展开二级菜单（图片生成、视频生成），再次点击收起 |

#### 4.2.2 底部工具栏

| 元素 | 交互行为 |
|------|----------|
| 浅色/深色切换 | 并排双按钮，点击切换全局主题，状态持久化到 `localStorage` |
| APP下载按钮 | 鼠标 hover 时弹出浮层，显示APP下载二维码图片 |
| 更多菜单按钮 | 鼠标 hover 时向上弹出菜单面板，包含协议链接、社交分享按钮 |

#### 4.2.3 右侧顶部固定栏 (Header)

| 元素 | 位置 | 说明 |
|------|------|------|
| 搜索框 | 左起第一个 | 输入关键词可搜索模型/场景模板 |
| 登录/注册 | 右起第一个 | 未登录时显示，点击跳转登录/注册页 |
| 会员中心 | 右起第二个 | 跳转会员中心页面 |
| 邀请有礼 | 右起第三个 | 跳转邀请有礼活动页面 |

#### 4.2.4 模型广场卡片

每张模型卡片展示以下信息：

| 字段 | 来源 |
|------|------|
| 模型名称 | `model_info.model_name` |
| 供应商LOGO | `model_provider.logo` |
| 供应商名称 | `model_provider.provider_name` |
| 模型描述 | `model_info.description` |
| 模型封面 | `model_info.cover_image`（若有） |
| 模型类型标签 | `model_type.type_name` |

卡片采用横向滚动或网格布局，从 `model_info` 表动态查询 `status=1` 的模型列表。

#### 4.2.5 TAB切换区域

```
stateDiagram-v2
    [*] --> 图片模型Tab
    图片模型Tab --> 视频特效Tab: 点击"视频特效"
    视频特效Tab --> 图片模型Tab: 点击"图片模型"
    
    state 图片模型Tab {
        [*] --> 加载图片场景分类
        加载图片场景分类 --> 显示分类筛选栏
        显示分类筛选栏 --> 加载图片场景模板卡片
        加载图片场景模板卡片 --> 场景卡片网格展示
    }
    
    state 视频特效Tab {
        [*] --> 加载视频场景分类
        加载视频场景分类 --> 显示分类筛选栏2
        显示分类筛选栏2 --> 加载视频场景模板卡片
        加载视频场景模板卡片 --> 场景卡片网格展示2
    }
```

**图片模型 TAB**：
- 第一行：场景分类筛选标签栏，数据来自 `generation_scene_category`（generation_type=1），首个为"全部"
- 第二行起：场景模板卡片网格，数据来自 `generation_scene_template`（generation_type=1），点击分类标签时按 `category_ids` 过滤

**视频特效 TAB**：
- 第一行：场景分类筛选标签栏，数据来自 `generation_scene_category`（generation_type=2），首个为"全部"
- 第二行起：场景模板卡片网格，数据来自 `generation_scene_template`（generation_type=2），点击分类标签时按 `category_ids` 过滤

**场景模板卡片字段**：

| 字段 | 来源 |
|------|------|
| 模板名称 | `generation_scene_template.template_name` |
| 封面图 | `generation_scene_template.cover_image` |
| 基础价格 | `generation_scene_template.base_price` |
| 使用次数 | `generation_scene_template.use_count` |
| 模板描述 | `generation_scene_template.description` |

---

## 5. 主题切换策略

### 5.1 实现方式

采用 CSS 自定义属性（CSS Variables）方案，通过在 `<html>` 标签上切换 `data-theme` 属性实现浅色/深色主题。

### 5.2 主题变量表

| 变量名 | 浅色主题值 | 深色主题值 | 用途 |
|--------|-----------|-----------|------|
| --bg-primary | #FFFFFF | #1A1A2E | 主背景色 |
| --bg-sidebar | #F5F5F5 | #16213E | 侧边栏背景 |
| --bg-card | #FFFFFF | #0F3460 | 卡片背景 |
| --text-primary | #333333 | #E0E0E0 | 主文字色 |
| --text-secondary | #666666 | #A0A0A0 | 次文字色 |
| --border-color | #E8E8E8 | #2A2A4A | 边框色 |
| --accent-color | #4A90D9 | #53A8FF | 强调色 |
| --hover-bg | #F0F0F0 | #1E3A5F | 悬浮背景色 |

### 5.3 状态持久化

主题选择存储在 `localStorage`（key: `theme_mode`），页面加载时优先读取本地存储，无存储时默认浅色主题。

---

## 6. 数据流与 API 对接

### 6.1 数据加载流程

```
sequenceDiagram
    participant Browser as 浏览器
    participant Controller as Index控制器
    participant DB as 数据库
    
    Browser->>Controller: GET / (首页请求)
    Controller->>DB: 查询 webinfo 配置
    DB-->>Controller: 返回 showweb=3 配置
    Controller->>DB: 查询模型广场数据 (model_info + model_provider)
    DB-->>Controller: 返回模型列表
    Controller->>DB: 查询图片场景分类 (generation_type=1)
    DB-->>Controller: 返回图片分类列表
    Controller->>DB: 查询视频场景分类 (generation_type=2)
    DB-->>Controller: 返回视频分类列表
    Controller->>DB: 查询图片场景模板 (generation_type=1)
    DB-->>Controller: 返回图片场景模板
    Controller->>DB: 查询视频场景模板 (generation_type=2)
    DB-->>Controller: 返回视频场景模板
    Controller-->>Browser: 渲染 index3/index.html 并传入数据
    
    Note over Browser: 页面渲染完成
    
    Browser->>Controller: AJAX: 切换分类/搜索/分页
    Controller->>DB: 按条件查询模板列表
    DB-->>Controller: 返回过滤后数据
    Controller-->>Browser: 返回 JSON 数据
    Browser->>Browser: 动态刷新卡片区域
```

### 6.2 页面需要的后端数据接口

| 接口用途 | 请求方式 | 路由 | 参数 | 返回 |
|----------|---------|------|------|------|
| 首页数据(服务端渲染) | GET | Index/index | — | 页面HTML含注入数据 |
| 场景模板列表(分类筛选) | AJAX GET | Index/scene_list | generation_type, category_id, page, limit | JSON模板列表 |
| 搜索模型/模板 | AJAX GET | Index/search | keyword, type | JSON搜索结果 |
| 模型广场列表 | AJAX GET | Index/model_list | page, limit | JSON模型列表 |

> 以上接口需在 `Index` 控制器中新增对应方法，当 `showweb==3` 时生效。

---

## 7. 响应式自适应设计

### 7.1 断点定义

| 断点名称 | 屏幕宽度范围 | 目标设备 | 布局模式 |
|---------|-------------|---------|----------|
| Desktop-L | ≥ 1440px | 大屏桌面 | 侧边栏展开(20%) + 内容区(80%) |
| Desktop | 1024px – 1439px | 普通桌面/小笔记本 | 侧边栏折叠(60px) + 内容区自充 |
| Tablet | 768px – 1023px | 平板竖屏 | 侧边栏隐藏(抽屉覆盖) + 内容区全宽 |
| Mobile | < 768px | 手机 | 侧边栏隐藏(抽屉覆盖) + 内容区全宽 + 底部TabBar |

### 7.2 各断点布局对比

```
flowchart TD
    A["页面加载"] --> B{"屏幕宽度检测"}
    B -->|"≥ 1440px"| C["大屏桌面模式"]
    B -->|"1024-1439px"| D["普通桌面模式"]
    B -->|"768-1023px"| E["平板模式"]
    B -->|"< 768px"| F["手机模式"]
    
    C --> C1["侧边栏展开 20%"]
    C --> C2["卡片网格 4列"]
    C --> C3["模型广场横向滚动"]
    
    D --> D1["侧边栏折叠 60px"]
    D --> D2["卡片网格 3列"]
    D --> D3["模型广场横向滚动"]
    
    E --> E1["侧边栏隐藏/抽屉覆盖"]
    E --> E2["顶部汉堡菜单按钮"]
    E --> E3["卡片网格 3列"]
    
    F --> F1["侧边栏隐藏/抽屉覆盖"]
    F --> F2["顶部精简栏 + 汉堡菜单"]
    F --> F3["卡片网格 2列"]
    F --> F4["底部固定 TabBar"]
```

### 7.3 组件在各断点的变化

#### 7.3.1 侧边栏导航

| 组件属性 | Desktop-L (≥1440) | Desktop (1024-1439) | Tablet (768-1023) | Mobile (<768) |
|---------|------|---------|--------|--------|
| 可见性 | 始终可见，展开态 | 始终可见，折叠态 | 默认隐藏，抽屉触发 | 默认隐藏，抽屉触发 |
| 宽度 | 20% | 60px | 280px（覆盖式） | 80vw（覆盖式） |
| 展现形式 | 图标+文字+折叠按钮 | 仅图标 | 图标+文字（抽屉内） | 图标+文字（抽屉内） |
| 触发方式 | 折叠按钮 / LOGO | LOGO点击 | 汉堡菜单按钮 | 汉堡菜单按钮 |
| 关闭方式 | 折叠按钮 | 自动折叠 | 点击遮罩层/关闭按钮 | 点击遮罩层/关闭按钮 |
| 公司信息 | 显示 | 隐藏 | 抽屉底部显示 | 抽屉底部显示 |
| 底部工具栏 | 图标+文字 | 仅图标 | 抽屉底部图标+文字 | 抽屉底部图标+文字 |

#### 7.3.2 顶部固定栏 (Header)

| 组件属性 | Desktop-L / Desktop | Tablet | Mobile |
|---------|------|--------|--------|
| 搜索框 | 完整显示，左侧占位 | 缩省为图标，点击展开全屏搜索 | 缩省为图标，点击展开全屏搜索 |
| 邀请有礼 | 文字按钮 | 文字按钮 | 隐藏（移至抽屉菜单） |
| 会员中心 | 文字按钮 | 图标按钮 | 隐藏（移至抽屉菜单） |
| 登录/注册 | 文字按钮 | 文字按钮 | 图标按钮 |
| 汉堡菜单 | 不显示 | 显示（左侧） | 显示（左侧） |
| 布局 | 左搜索+右按钮组 | 左汉堡+中搜索图标+右按钮 | 左汉堡+中LOGO+右图标组 |

#### 7.3.3 模型广场卡片

| 组件属性 | Desktop-L | Desktop | Tablet | Mobile |
|---------|------|---------|--------|--------|
| 布局方式 | 横向滚动 | 横向滚动 | 横向滚动 | 横向滚动(触摸滑动) |
| 卡片尺寸 | 固定宽 220px | 固定宽 200px | 固定宽 180px | 固定宽 150px |
| hover效果 | 上浮+阴影 | 上浮+阴影 | 无 | 无 |

#### 7.3.4 场景模板卡片网格

| 组件属性 | Desktop-L | Desktop | Tablet | Mobile |
|---------|------|---------|--------|--------|
| 列数 | 4列 | 3列 | 3列 | 2列 |
| 卡片间距 | 16px | 16px | 12px | 10px |
| 卡片宽高比 | 3:4 | 3:4 | 3:4 | 3:4 |
| 加载方式 | 点击加载更多 | 点击加载更多 | 上拉加载 | 上拉加载 |

#### 7.3.5 场景分类标签栏

| 组件属性 | Desktop | Tablet / Mobile |
|---------|------|--------|
| 布局 | 水平排列，超出时显示左右箭头 | 横向触摸滚动(scroll-x)，不显示箭头 |
| 标签样式 | pill/chip 胶囊样式 | pill/chip 胶囊样式，略小 |

#### 7.3.6 底部 TabBar（仅移动端 < 768px）

移动端屏幕底部固定显示 TabBar，提供快捷导航：

| TAB项 | 图标 | 点击行为 |
|---------|------|----------|
| 首页 | 🏠 | 滚动到顶部/切换到首页 |
| 创作 | ✨ | 弹出选择面板（图片生成/视频生成） |
| 资产 | 📚 | 跳转资产页 |
| 我的 | 👤 | 跳转个人中心 |

### 7.4 移动端侧边栏抽屉交互

```
stateDiagram-v2
    [*] --> 抽屉隐藏
    抽屉隐藏 --> 抽屉打开: 点击汉堡菜单按钮 / 右滑手势
    抽屉打开 --> 抽屉隐藏: 点击遮罩层 / 左滑手势 / 点击关闭按钮
    
    state 抽屉隐藏 {
        侧边栏在屏幕左侧外
        主内容区全宽显示
        底部TabBar可见
    }
    
    state 抽屉打开 {
        侧边栏从左侧滑入_占屏80vw
        右侧显示半透明遮罩层
        抽屉内显示完整菜单和公司信息
        抽屉底部显示主题切换和更多菜单
    }
```

**抽屉内部布局**（从上到下）：
1. LOGO + 系统名称 + 关闭按钮（×）
2. 用户信息区（已登录时显示头像/昵称，未登录显示登录/注册按钮）
3. 菜单列表（首页、创作、资产、个人中心、创作中心、会员中心、邀请有礼）
4. 分割线
5. 公司信息、备案信息
6. 底部工具栏（主题切换、APP下载、更多菜单）

### 7.5 移动端触摸交互适配

| PC端交互 | 移动端替代方案 |
|----------|-------------|
| 鼠标 hover 卡片上浮 | 无hover效果，直接点击跳转 |
| hover 弹出APP二维码 | 点击打开弹窗显示二维码，点击遮罩关闭 |
| hover 弹出更多菜单 | 点击弹出底部面板(ActionSheet) |
| 横向滚动箭头按钮 | 触摸左右滑动 |
| 点击加载更多按钮 | 上拉触底自动加载下一页 |
| 右键菜单 | 长按弹出操作菜单（如需） |
| 侧边栏折叠按钮 | 汉堡菜单图标 + 抽屉覆盖 |

### 7.6 移动端页面布局视觉结构

```
┌──────────────────────┐
│ ☰  [LOGO]        🔍 👤 │  ← 顶部栏：汉堡+LOGO+搜索+登录
├──────────────────────┤
│                      │
│ ◆ 模型广场             │
│ ┌───┐ ┌───┐ ┌───┐  │
│ │ M1 │ │ M2 │ │ M3 │  │  ← 横向触摸滚动
│ └───┘ └───┘ └───┘  │
│                      │
│ [图片模型] [视频特效] │  ← TAB
│ 全部|风景|人物|创意  │  ← 分类横向滚动
│ ┌─────┐ ┌─────┐    │
│ │ 场暯1 │ │ 场暯2 │    │  ← 2列卡片
│ │     │ │     │    │
│ └─────┘ └─────┘    │
│ ┌─────┐ ┌─────┐    │
│ │ 场暯3 │ │ 场暯4 │    │
│ └─────┘ └─────┘    │
│      ↑ 上拉加载更多    │
├──────────────────────┤
│ 🏠首页  ✨创作  📚资产  👤我的 │  ← 底部固定TabBar
└──────────────────────┘
```

---

## 8. 交互行为（PC端）

### 8.1 侧边栏折叠/展开（PC端）

```
stateDiagram-v2
    [*] --> 展开态
    展开态 --> 折叠态: 点击折叠按钮 ≡
    折叠态 --> 展开态: 点击LOGO图标
    
    state 展开态 {
        侧边栏宽度_20%
        显示LOGO图标_系统名称_折叠按钮
        菜单项显示图标和文字
        显示公司信息文字
        底部工具栏显示图标和文字
        右侧内容区_80%
    }
    
    state 折叠态 {
        侧边栏宽度_60px
        仅显示LOGO图标_可点击展开
        隐藏系统名称和折叠按钮
        菜单项仅显示图标_悬浮提示文字
        隐藏公司信息文字
        底部工具栏仅显示图标
        右侧内容区_自动扩展
    }
```

**折叠态细节**：
- LOGO图标始终可见，作为唯一的展开触发点，鼠标悬浮时显示 cursor:pointer 提示可点击
- 各菜单项仅保留左侧图标，鼠标 hover 时通过 tooltip 浮层显示菜单文字
- 二级菜单（创作→图片生成/视频生成）折叠态下不展开子项，hover 时以浮动面板形式显示子菜单
- 底部工具栏三个按钮仅保留图标，hover 交互行为不变

### 8.2 悬浮交互（PC端）

| 触发元素 | 触发方式 | 弹出内容 | 弹出位置 |
|----------|---------|----------|---------|
| APP下载按钮 | 鼠标 hover | 二维码图片浮层 | 按钮上方 |
| 更多菜单按钮 | 鼠标 hover | 菜单面板 | 按钮上方 |
| 模型卡片 | 鼠标 hover | 卡片微上浮 + 阴影增强 | 原位 |
| 场景模板卡片 | 鼠标 hover | 卡片微上浮 + 阴影增强 | 原位 |

### 8.3 更多菜单面板布局

鼠标移入"更多菜单"按钮时弹出面板，内容按以下顺序垂直排列：

1. 用户协议（链接）
2. 隐私协议（链接）
3. 关于我们（链接）
4. 加入我们（链接）
5. 联系我们（链接）
6. 联系客服（链接）
7. 分割线
8. 社交分享按钮行（微信图标 | 小红书图标 | 抖音图标，水平并排）

---

## 9. 需要变更的现有文件

### 9.1 控制器变更

| 文件 | 变更内容 |
|------|---------|
| `app/controller/Index.php` | `index()` 方法新增 `showweb==3` 分支，渲染 `index3/index.html`；同理修改 `lianxi()`、`help()`、`helpdetail()`、`funshow()` 等方法 |
| `app/controller/Index.php` | 新增 `scene_list()`、`search()`、`model_list()` AJAX 接口方法 |

### 9.2 后台配置变更

| 文件 | 变更内容 |
|------|---------|
| `app/view/web_system/set.html` | 官网模板单选组新增 `value="3"` 选项"模板三"，插入在"模板二"之后 |

### 9.3 新建文件清单

| 文件路径 | 说明 |
|----------|------|
| `app/view/index3/index.html` | 主框架页面（响应式，适配所有终端） |
| `app/view/index3/public/sidebar.html` | 侧边栏模板片段（PC侧边栏 / 移动抽屉复用） |
| `app/view/index3/public/header.html` | 顶部栏模板片段（响应式） |
| `app/view/index3/public/tabbar.html` | 移动端底部TabBar模板片段 |
| `app/view/index3/help.html` | 帮助中心 |
| `app/view/index3/helpdetail.html` | 帮助详情 |
| `app/view/index3/lianxi.html` | 联系我们 |
| `app/view/index3/downloadapp.html` | APP下载 |
| `static/index3/css/index.css` | 主样式表 |
| `static/index3/css/responsive.css` | 响应式断点样式 |
| `static/index3/css/theme-light.css` | 浅色主题变量 |
| `static/index3/css/theme-dark.css` | 深色主题变量 |
| `static/index3/js/index.js` | 主逻辑脚本 |
| `static/index3/js/sidebar.js` | 侧边栏/抽屉交互 |
| `static/index3/js/theme.js` | 主题切换逻辑 |
| `static/index3/js/api.js` | 数据请求封装 |
| `static/index3/img/` | 图标与图片资源目录 |

---

## 10. 页面布局详细说明

### 10.1 左侧导航栏视觉结构（PC端，自上而下）

**展开态**：
```
┌─────────────────────┐
│  [LOGO]  系统名称  ≡ │  ← LOGO + 名称 + 折叠按钮（点击≡折叠）
├─────────────────────┤
│  🏠 首页              │
│  🎨 创作            ▼ │  ← 可展开
│     ├ 图片生成        │
│     └ 视频生成        │
│  📦 资产              │
├─────────────────────┤  ← 分割线
│  👤 个人中心          │
│  🖌 创作中心          │
├─────────────────────┤  ← 分割线
│  公司名称             │
│  ICP备案: xxxxx       │
│  公安备案: xxxxx      │
├─────────────────────┤
│  [☀/🌙] [📱] [⋯]    │  ← 底部工具栏并排
└─────────────────────┘
```

**折叠态**（点击LOGO可展开恢复）：
```
┌────┐
│LOGO│  ← 点击LOGO展开侧边栏
├────┤
│ 🏠 │
│ 🎨 │  ← hover弹出子菜单浮层
│ 📦 │
├────┤
│ 👤 │
│ 🖌 │
├────┤
│    │  ← 公司信息隐藏
├────┤
│☀📱⋯│  ← 图标竖排或横排
└────┘
```

### 10.2 右侧内容区视觉结构（PC端）

```
┌──────────────────────────────────────────────┐
│  [🔍 搜索...]          邀请有礼 | 会员中心 | 登录/注册 │  ← 固定顶栏
├──────────────────────────────────────────────┤
│                                              │
│  ◆ 模型广场                                  │
│  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐   │
│  │模型1│ │模型2│ │模型3│ │模型4│ │模型5│   │  ← 横向可滚动模型卡片
│  └─────┘ └─────┘ └─────┘ └─────┘ └─────┘   │
│                                              │
│  [图片模型]  [视频特效]                        │  ← TAB切换
│  ─────────────────────────────────────────── │
│  全部 | 风景 | 人物 | 创意 | 节日 | ...       │  ← 场景分类标签
│  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐            │
│  │场景1│ │场景2│ │场景3│ │场景4│            │
│  │     │ │     │ │     │ │     │            │  ← 场景模板卡片网格
│  └─────┘ └─────┘ └─────┘ └─────┘            │
│  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐            │
│  │场景5│ │场景6│ │场景7│ │场景8│            │
│  └─────┘ └─────┘ └─────┘ └─────┘            │
│                                              │
│  [ 加载更多 / 滚动加载 ]                      │
└──────────────────────────────────────────────┘
```

---

## 11. 测试策略

### 11.1 功能测试

| 测试场景 | 验证点 |
|----------|--------|
| 模板切换 | 后台设置 showweb=3 后，前台正确渲染模板三 |
| 侧边栏折叠 | 点击折叠按钮（≡），侧边栏收缩为图标模式（60px），右侧内容区自适应扩展 |
| 侧边栏展开 | 折叠态下点击LOGO图标，侧边栏恢复展开态（20%），右侧内容区恢复80% |
| 主题切换 | 点击浅色/深色按钮后全局样式正确切换，刷新后保持选择 |
| 模型广场数据 | 模型卡片正确显示 model_info 中 status=1 的模型 |
| TAB切换 | 图片模型/视频特效 TAB 切换后正确加载对应 generation_type 的数据 |
| 分类筛选 | 点击场景分类标签后，卡片区域正确过滤显示对应分类的场景模板 |
| 搜索功能 | 输入关键词后正确搜索并展示匹配的模型或场景模板 |
| APP下载悬浮 | 鼠标移入APP按钮显示二维码，移出后隐藏 |
| 更多菜单悬浮 | 鼠标移入更多按钮弹出菜单面板，包含所有链接和分享按钮 |
| 登录/注册 | 未登录时显示按钮，点击跳转至对应页面 |
| 备案信息显示 | 底部正确显示 ICP 备案和公安备案信息（来自 webinfo 配置） |

### 11.2 响应式测试

| 测试场景 | 测试内容 | 验证点 |
|----------|---------|--------|
| 大屏桌面 ≥1440px | 侧边栏展开态 | 侧边栏20%展开显示图标+文字，卡片网格4列 |
| 普通桌面 1024-1439px | 侧边栏自动折叠 | 侧边栏60px仅图标，卡片网格3列 |
| 平板竖屏 768-1023px | 侧边栏抽屉 | 无侧边栏，汉堡菜单点击弹出抽屉，卡片网格3列 |
| 手机竖屏 <768px | 全移动布局 | 抽屉导航+底部TabBar+2列卡片+上拉加载 |
| 手机横屏 | 自适应调整 | 侧边栏保持抽屉，卡片自动调整为3列 |
| 抽屉打开/关闭 | 汉堡菜单+滑动手势 | 右滑打开抽屉、左滑/点遮罩关闭抽屉 |
| 搜索框展开 | 点击搜索图标 | 展开全屏搜索输入框，支持取消按钮返回 |
| 触摸交互 | 横向滚动/上拉加载 | 模型卡片触摸滚动流畅，场景卡片上拉正常加载下一页 |
| 底部TabBar | 4个Tab切换 | 各Tab点击正确跳转，当前激活项高亮 |
| APP下载弹窗 | 点击触发 | 弹窗正确显示二维码，点击遮罩层正确关闭 |
| 更多菜单 | 点击触发 | 底部ActionSheet正确弹出菜单项 |

### 11.3 兼容性测试

| 测试维度 | 目标 |
|----------|------|
| 桌面浏览器 | Chrome、Firefox、Edge、Safari 最新两个版本 |
| 移动浏览器 | iOS Safari、Android Chrome、微信内置浏览器 |
| 分辨率范围 | 375px(小SE) 至 2560px(宽屏) 全覆盖 |
| 触摸屏 | 触摸事件、滑动手势、双指缩放无异常 |
