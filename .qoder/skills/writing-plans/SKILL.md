---
name: writing-plans
description: 将设计分解为可执行的任务列表，每个任务包含具体文件路径、完整代码和验证步骤。在设计批准后、开始实施前调用。
---

# 编写实施计划（Writing Plans）

## 何时使用

此技能应该在以下情况下自动激活：
- 设计文档已获得用户批准
- 准备开始实施新功能
- 用户请求创建实施计划
- 需要将复杂任务分解为可管理的小任务

## 核心原则

1. **任务原子性**：每个任务应该独立可测试和验证
2. **时间可控**：每个任务应该在2-5分钟内完成
3. **信息完整**：每个任务包含所有必要信息（文件、代码、验证）
4. **依赖明确**：任务之间的依赖关系清晰
5. **可执行性**：即使是不熟悉项目的开发者也能按计划执行

## 计划结构

### 计划模板

```markdown
# [功能名称] 实施计划

## 概述
[简要描述要实施的功能]

## 设计参考
- 设计文档：[链接]
- 相关代码库文件：[链接列表]

## 任务列表

### 任务1：[任务标题]
**优先级**：高/中/低
**预计时间**：2-5分钟
**依赖**：无

**操作**：
- CREATE/UPDATE/ADD/REMOVE/MIRROR [文件路径]
- 实施细节：[具体要做什么]

**代码**：
```[语言]
[完整代码]
```

**验证**：
```bash
[验证命令]
```

**成功标准**：
- [ ] 标准1
- [ ] 标准2

### 任务2：[任务标题]
**优先级**：高/中/低
**预计时间**：2-5分钟
**依赖**：任务1

**操作**：
- CREATE/UPDATE/ADD/REMOVE/MIRROR [文件路径]
- 实施细节：[具体要做什么]

**代码**：
```[语言]
[完整代码]
```

**验证**：
```bash
[验证命令]
```

**成功标准**：
- [ ] 标准1
- [ ] 标准2

[继续所有任务...]

## 验证命令

### 语法检查
```bash
[项目特定的语法检查命令]
```

### 类型检查
```bash
[项目特定的类型检查命令]
```

### 单元测试
```bash
[项目特定的单元测试命令]
```

### 集成测试
```bash
[项目特定的集成测试命令]
```

## 验收标准
- [ ] 所有任务已完成
- [ ] 所有验证命令通过
- [ ] 代码符合项目规范
- [ ] 测试覆盖率达标
- [ ] 功能符合设计文档

## 风险和回退
| 风险 | 缓解措施 | 回退计划 |
|------|----------|----------|
| 风险1 | 措施1 | 计划1 |
| 风险2 | 措施2 | 计划2 |
```

## 任务分解方法

### 按层次分解

**第1层：主要组件**
- 后端API
- 前端UI
- 数据库迁移
- 测试

**第2层：子功能**
- 后端API → 路由定义、业务逻辑、数据访问
- 前端UI → 组件、状态管理、API集成

**第3层：具体任务**
- 路由定义 → 创建路由文件、定义端点、添加验证

### 按依赖关系分解

1. **基础任务**：数据模型、类型定义、配置
2. **核心任务**：业务逻辑、API端点、UI组件
3. **集成任务**：连接各组件、添加中间件
4. **测试任务**：单元测试、集成测试、端到端测试
5. **文档任务**：更新文档、添加注释

## 任务编写指南

### CREATE（创建新文件）

```markdown
**操作**：CREATE `backend/app/api/routes/new_feature.py`

**代码**：
```python
from fastapi import APIRouter, Depends
from app.core.deps import get_current_user

router = APIRouter()

@router.get("/items")
async def get_items(
    skip: int = 0,
    limit: int = 100,
    current_user = Depends(get_current_user)
):
    """获取物品列表"""
    return {"items": []}
```

**验证**：
```bash
cd backend && python -m py_compile app/api/routes/new_feature.py
```

**成功标准**：
- [ ] 文件创建成功
- [ ] 语法检查通过
- [ ] 路由可以导入
```

### UPDATE（更新现有文件）

```markdown
**操作**：UPDATE `backend/app/models.py`

**代码**：
在文件末尾添加：
```python
class NewItem(Base):
    __tablename__ = "new_items"

    id: int = Field(default=None, primary_key=True)
    name: str = Field(index=True)
    description: str | None = Field(default=None)
```

**验证**：
```bash
cd backend && python -c "from app.models import NewItem; print('Import successful')"
```

**成功标准**：
- [ ] 新模型可以导入
- [ ] 字段定义正确
- [ ] 没有语法错误
```

### ADD（添加功能到现有代码）

```markdown
**操作**：ADD to `backend/app/api/routes/items.py`

**代码**：
在 `get_items` 函数后添加：
```python
@router.post("/items")
async def create_item(
    item: ItemCreate,
    current_user = Depends(get_current_user)
):
    """创建新物品"""
    return {"item": item}
```

**验证**：
```bash
cd backend && python -m pytest tests/api/routes/test_items.py -v
```

**成功标准**：
- [ ] 新端点可以访问
- [ ] 测试通过
- [ ] 文档更新
```

### MIRROR（复制模式）

```markdown
**操作**：MIRROR `backend/app/api/routes/users.py` → `backend/app/api/routes/items.py`

**模式参考**：
- 认证检查：第15-20行
- 分页逻辑：第45-50行
- 错误处理：第30-35行

**代码**：
```python
# 复制认证检查模式
current_user = Depends(get_current_user)

# 复制分页逻辑
skip: int = 0
limit: int = 100
```

**验证**：
```bash
cd backend && python -m pytest tests/api/routes/test_items.py -v
```

**成功标准**：
- [ ] 模式正确应用
- [ ] 代码风格一致
- [ ] 测试通过
```

## 验证命令示例

### Python项目

```bash
# 语法检查
python -m py_compile app/module.py

# 类型检查
mypy app/

# 代码格式
ruff check app/

# 单元测试
pytest tests/unit/ -v

# 集成测试
pytest tests/integration/ -v
```

### TypeScript项目

```bash
# 类型检查
tsc --noEmit

# 代码格式
biome check --write src/

# 单元测试
npm test

# 构建
npm run build
```

### 通用验证

```bash
# Git状态检查
git status

# 检查未提交的更改
git diff --stat

# 运行所有测试
npm test
# 或
pytest
```

## 最佳实践

1. **保持任务小而专注**：每个任务只做一件事
2. **提供完整代码**：不要让实施者猜测
3. **包含验证步骤**：每个任务都有明确的验证方法
4. **考虑边缘情况**：在任务中注明需要注意的地方
5. **遵循项目规范**：使用项目现有的代码风格和模式
6. **记录决策**：说明为什么选择某种实现方式

## 常见错误

**错误1：任务太大**
- 问题：一个任务包含多个功能
- 解决：拆分成多个小任务

**错误2：缺少验证**
- 问题：没有验证命令或成功标准
- 解决：为每个任务添加验证步骤

**错误3：依赖不明确**
- 问题：任务之间的依赖关系不清楚
- 解决：明确标注每个任务的依赖

**错误4：代码不完整**
- 问题：只提供代码片段，实施者需要猜测
- 解决：提供完整可运行的代码

**错误5：忽略项目规范**
- 问题：使用不同的代码风格或模式
- 解决：参考项目现有代码，保持一致性

## 输出检查清单

完成计划编写后，确保：
- [ ] 所有任务按依赖顺序排列
- [ ] 每个任务包含完整代码
- [ ] 每个任务有验证命令
- [ ] 每个任务有成功标准
- [ ] 验证命令在项目中可执行
- [ ] 计划遵循项目规范
- [ ] 风险已识别并有缓解措施
- [ ] 用户审查并批准计划

## 下一步

计划批准后，应该调用 `test-driven-development` 技能开始实施，或调用 `executing-plans` 技能批量执行任务。