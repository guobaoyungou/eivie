# 点大商城系统技能配置

基于系统架构文档创建的专业技能，用于提升开发效率和代码质量。

## 已创建的技能

### 1. ThinkPHP开发规范 (thinkphp-development)
**路径**: `.trae/skills/thinkphp-development/SKILL.md`

**用途**:
- ThinkPHP 6.0框架开发规范
- 控制器、模型、数据库操作
- MVC架构最佳实践
- 安全编码规范

**触发场景**:
- 创建新控制器
- 数据库查询操作
- 业务逻辑开发
- API接口开发

**核心内容**:
- 控制器继承关系
- 全局常量定义规范
- 数据库操作规范
- 命名规范
- 安全规范
- ID字段规范

---

### 2. 多平台API开发 (multi-platform-api-development)
**路径**: `.trae/skills/multi-platform-api-development/SKILL.md`

**用途**:
- 多平台API接口开发
- 平台识别和适配
- 会员认证机制
- 统一响应格式

**支持平台**:
- 微信公众号 (mp)
- 微信小程序 (wx)
- 支付宝小程序 (alipay)
- 百度小程序 (baidu)
- 头条小程序 (toutiao)
- QQ小程序 (qq)
- H5网页 (h5)
- APP应用 (app)

**核心内容**:
- 平台识别机制
- 请求参数规范
- 响应格式规范
- 会员认证
- 常见API模块示例
- 平台特殊处理

---

### 3. 支付系统集成 (payment-integration)
**路径**: `.trae/skills/payment-integration/SKILL.md`

**用途**:
- 支付系统开发
- 微信支付V2/V3集成
- 支付宝支付集成
- 余额支付
- 支付回调处理
- 退款处理

**支持支付方式**:
- 微信支付 (JSAPI、H5、APP、扫码)
- 支付宝 (当面付、手机网站、APP)
- 余额支付
- 货到付款

**核心内容**:
- 支付流程
- 支付订单设计
- 微信支付V3 API
- 支付宝 API
- 余额支付
- 支付回调处理
- 退款处理
- 安全验证

---

### 4. 数据库设计规范 (database-design)
**路径**: `.trae/skills/database-design/SKILL.md`

**用途**:
- 数据库表设计
- 字段类型选择
- 索引优化
- 性能优化

**核心规范**:
- ID字段规范 (aid/bid/mid/uid/mdid)
- 时间字段规范 (createtime/updatetime)
- 状态字段规范 (status)
- 字符集和引擎 (InnoDB/utf8mb4)
- 索引设计原则

**核心内容**:
- 表设计规范
- 字段类型选择
- 索引设计
- 性能优化
- 数据迁移
- 常见表设计模式

---

## 原有技能

### 5. 头脑风暴 (brainstorming)
**路径**: `.trae/skills/brainstorming/SKILL.md`

细化设计想法，探索替代方案，创建详细设计文档。

### 6. 创建技能 (creating-skills)
**路径**: `.trae/skills/creating-skills/SKILL.md`

创建新技能的指南和最佳实践。

### 7. 测试驱动开发 (test-driven-development)
**路径**: `.trae/skills/test-driven-development/SKILL.md`

强制执行RED-GREEN-REFACTOR循环。

### 8. Git提交 (git-commit)
**路径**: `.trae/skills/git-commit/SKILL.md`

规范化的Git提交流程。

### 9. GitHub PR创建 (github-pr-creation)
**路径**: `.trae/skills/github-pr-creation/SKILL.md`

创建GitHub Pull Request。

### 10. GitHub PR审查 (github-pr-review)
**路径**: `.trae/skills/github-pr-review/SKILL.md`

处理PR审查意见。

### 11. GitHub PR合并 (github-pr-merge)
**路径**: `.trae/skills/github-pr-merge/SKILL.md`

合并Pull Request。

### 12. 系统化调试 (systematic-debugging)
**路径**: `.trae/skills/systematic-debugging/SKILL.md`

系统化的问题排查和调试。

### 13. 请求代码审查 (requesting-code-review)
**路径**: `.trae/skills/requesting-code-review/SKILL.md`

提交代码审查请求。

### 14. UI/UX专业版 (ui-ux-pro-max)
**路径**: `.trae/skills/ui-ux-pro-max/SKILL.md`

用户界面和用户体验设计。

### 15. 编写计划 (writing-plans)
**路径**: `.trae/skills/writing-plans/SKILL.md`

创建详细的项目计划。

---

## 技能使用指南

### 自动激活
技能会根据开发场景自动激活，无需手动调用。

### 触发条件示例

**ThinkPHP开发**:
- "创建一个商品控制器"
- "查询订单列表"
- "实现用户登录功能"

**多平台API**:
- "创建商品列表API"
- "实现微信小程序登录"
- "开发H5商城接口"

**支付集成**:
- "集成微信支付"
- "实现支付宝支付"
- "处理支付回调"
- "实现退款功能"

**数据库设计**:
- "设计订单表"
- "创建会员表"
- "优化查询性能"
- "添加索引"

### 技能组合使用

**完整开发流程**:
1. `brainstorming` - 设计讨论
2. `writing-plans` - 编写计划
3. `database-design` - 数据库设计
4. `thinkphp-development` - 控制器开发
5. `multi-platform-api-development` - API开发
6. `payment-integration` - 支付集成
7. `test-driven-development` - 测试驱动
8. `git-commit` - 提交代码
9. `requesting-code-review` - 代码审查

---

## 项目特定配置

### 全局常量
- `aid` - 平台ID
- `mid` - 会员ID
- `bid` - 商家ID
- `platform` - 访问平台

### 目录结构
```
/www/wwwroot/eivie/
├── app/
│   ├── controller/      # 控制器
│   ├── common/          # 业务逻辑类
│   ├── model/           # 数据模型
│   └── view/            # 视图模板
├── config/              # 配置文件
├── extend/              # 扩展类库
└── .trae/
    └── skills/          # 技能配置
```

### 核心类库
- `app\common\Member` - 会员业务
- `app\common\Business` - 商家业务
- `app\common\Common` - 通用工具
- `app\common\Alipay` - 支付宝支付
- `pay\wechatpay\WxPayV3` - 微信支付V3

---

## 更新日志

### 2026-01-21
- ✅ 创建 `thinkphp-development` 技能
- ✅ 创建 `multi-platform-api-development` 技能
- ✅ 创建 `payment-integration` 技能
- ✅ 创建 `database-design` 技能
- ✅ 适配点大商城系统架构

---

## 维护说明

### 添加新技能
1. 在 `.trae/skills/` 创建新目录
2. 创建 `SKILL.md` 文件
3. 按照规范编写技能内容
4. 更新本文档

### 更新现有技能
1. 修改对应的 `SKILL.md` 文件
2. 确保符合项目最新架构
3. 更新本文档的更新日志

### 技能质量检查
- [ ] frontmatter完整（name + description）
- [ ] description包含触发场景
- [ ] 快速开始示例可用
- [ ] 核心工作流程清晰
- [ ] 重要规则明确
- [ ] 示例代码正确
- [ ] 检查清单完整

---

## 参考资料

- [系统架构文档](../文档/原系统文档/系统架构与功能开发文档.md)
- [数据库表单](../文档/原系统文档/数据库表单.md)
- [ThinkPHP 6.0官方文档](https://www.kancloud.cn/manual/thinkphp6_0/)

---

**文档版本**: V1.0
**创建日期**: 2026-01-21
**维护者**: AI助手
