---
name: test-driven-development
description: 强制执行RED-GREEN-REFACTOR循环：先写失败的测试，再写最少代码使其通过，然后重构。在实施新功能或修改代码时必须调用。
---

# 测试驱动开发（Test-Driven Development）

## 何时使用

此技能应该在以下情况下自动激活：
- 开始实施新功能
- 修改现有代码
- 修复bug
- 用户请求编写测试
- 任何代码编写工作开始前

## 核心原则

1. **测试先行**：永远在编写功能代码之前编写测试
2. **红-绿-重构**：严格遵循TDD三步循环
3. **最小实现**：只写刚好能让测试通过的代码
4. **持续重构**：在测试通过后立即重构
5. **删除未测试代码**：任何没有测试的代码都应该删除

## TDD循环：红-绿-重构

### 第1步：RED（红）- 编写失败的测试

**目标**：编写一个明确失败的测试，描述期望的行为

**步骤**：
1. 理解需求或要修复的bug
2. 编写测试用例，描述期望的行为
3. 运行测试，确认它失败（红色）
4. 确保失败原因符合预期

**示例**：

```python
# tests/test_calculator.py

def test_add_two_numbers():
    """测试两个数相加"""
    result = add(2, 3)
    assert result == 5
```

运行测试：
```bash
pytest tests/test_calculator.py::test_add_two_numbers -v
# 应该失败：NameError: name 'add' is not defined
```

**验证清单**：
- [ ] 测试描述清晰
- [ ] 测试失败（红色）
- [ ] 失败原因符合预期
- [ ] 测试可以独立运行

### 第2步：GREEN（绿）- 编写最少代码

**目标**：编写刚好能让测试通过的最少代码

**步骤**：
1. 编写最少量的功能代码
2. 运行测试，确认它通过（绿色）
3. 不要优化或重构
4. 不要添加额外功能

**示例**：

```python
# calculator.py

def add(a, b):
    """两个数相加"""
    return a + b
```

运行测试：
```bash
pytest tests/test_calculator.py::test_add_two_numbers -v
# 应该通过：PASSED
```

**验证清单**：
- [ ] 代码最少且简单
- [ ] 测试通过（绿色）
- [ ] 没有添加额外功能
- [ ] 代码可以运行

### 第3步：REFACTOR（重构）- 改进代码

**目标**：在保持测试通过的前提下改进代码质量

**步骤**：
1. 识别可以改进的地方
2. 进行重构（重命名、提取方法、简化逻辑）
3. 每次重构后运行测试
4. 确保测试仍然通过

**示例**：

```python
# calculator.py - 重构后

def add(*numbers):
    """多个数相加"""
    return sum(numbers)
```

运行测试：
```bash
pytest tests/test_calculator.py::test_add_two_numbers -v
# 仍然通过：PASSED
```

**验证清单**：
- [ ] 代码更清晰
- [ ] 测试仍然通过（绿色）
- [ ] 没有改变功能
- [ ] 没有引入新bug

## 测试编写指南

### 单元测试

**原则**：
- 测试单个函数或方法
- 隔离外部依赖（使用mock）
- 快速执行
- 可重复运行

**示例**：

```python
# tests/test_user_service.py

import pytest
from unittest.mock import Mock
from app.services.user_service import UserService
from app.models import User

def test_create_user_success():
    """测试成功创建用户"""
    # Arrange
    mock_db = Mock()
    user_service = UserService(mock_db)
    user_data = {"name": "John", "email": "john@example.com"}

    # Act
    result = user_service.create_user(user_data)

    # Assert
    assert result.name == "John"
    assert result.email == "john@example.com"
    mock_db.add.assert_called_once()
    mock_db.commit.assert_called_once()

def test_create_user_duplicate_email():
    """测试创建用户时邮箱重复"""
    # Arrange
    mock_db = Mock()
    mock_db.query.return_value.filter.return_value.first.return_value = User(email="john@example.com")
    user_service = UserService(mock_db)
    user_data = {"name": "John", "email": "john@example.com"}

    # Act & Assert
    with pytest.raises(ValueError, match="Email already exists"):
        user_service.create_user(user_data)
```

### 集成测试

**原则**：
- 测试多个组件的交互
- 使用真实数据库或测试数据库
- 测试完整的工作流
- 较慢但更真实

**示例**：

```python
# tests/integration/test_api.py

from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)

def test_create_and_retrieve_item():
    """测试创建和检索物品的完整流程"""
    # Create
    response = client.post(
        "/items/",
        json={"name": "Test Item", "description": "A test item"}
    )
    assert response.status_code == 200
    item_id = response.json()["id"]

    # Retrieve
    response = client.get(f"/items/{item_id}")
    assert response.status_code == 200
    assert response.json()["name"] == "Test Item"
```

### 端到端测试

**原则**：
- 测试完整的用户场景
- 模拟真实用户操作
- 使用真实浏览器或API客户端
- 最慢但最真实

**示例**：

```python
# tests/e2e/test_user_flow.py

from playwright.sync_api import Page

def test_user_registration_and_login(page: Page):
    """测试用户注册和登录流程"""
    # Navigate to registration page
    page.goto("http://localhost:5173/signup")

    # Fill registration form
    page.fill('input[name="email"]', "test@example.com")
    page.fill('input[name="password"]', "password123")
    page.click('button[type="submit"]')

    # Wait for success message
    page.wait_for_selector(".success-message")

    # Navigate to login
    page.goto("http://localhost:5173/login")

    # Fill login form
    page.fill('input[name="email"]', "test@example.com")
    page.fill('input[name="password"]', "password123")
    page.click('button[type="submit"]')

    # Verify logged in
    page.wait_for_selector(".user-dashboard")
    assert page.is_visible(".user-dashboard")
```

## 测试反模式（要避免）

### 反模式1：测试实现细节

**错误**：
```python
def test_calculate_uses_specific_algorithm():
    """错误：测试内部实现"""
    calculator = Calculator()
    assert calculator._internal_method_called() == True
```

**正确**：
```python
def test_calculate_returns_correct_result():
    """正确：测试行为而非实现"""
    calculator = Calculator()
    result = calculator.calculate(2, 3)
    assert result == 5
```

### 反模式2：测试多个行为

**错误**：
```python
def test_user_crud_operations():
    """错误：一个测试多个操作"""
    user = create_user(...)
    update_user(user.id, ...)
    delete_user(user.id)
    # 太多断言，难以定位问题
```

**正确**：
```python
def test_create_user():
    """正确：每个测试一个行为"""
    user = create_user(...)
    assert user.id is not None

def test_update_user():
    """正确：每个测试一个行为"""
    user = create_user(...)
    updated = update_user(user.id, ...)
    assert updated.name == "New Name"
```

### 反模式3：依赖测试顺序

**错误**：
```python
def test_1():
    global.user = create_user(...)

def test_2():
    """错误：依赖test_1的执行"""
    assert global.user is not None
```

**正确**：
```python
def test_1():
    user = create_user(...)
    assert user.id is not None

def test_2():
    """正确：独立设置"""
    user = create_user(...)
    assert user.id is not None
```

### 反模式4：忽略测试失败

**错误**：
```python
def test_something():
    try:
        result = do_something()
        assert result == expected
    except:
        pass  # 忽略错误
```

**正确**：
```python
def test_something():
    result = do_something()
    assert result == expected
```

## 项目特定的测试设置

### Python项目（FastAPI）

```bash
# 运行所有测试
pytest

# 运行特定测试文件
pytest tests/test_users.py

# 运行特定测试函数
pytest tests/test_users.py::test_create_user

# 显示详细输出
pytest -v

# 显示打印输出
pytest -s

# 停止在第一个失败
pytest -x

# 覆盖率报告
pytest --cov=app --cov-report=html
```

### TypeScript项目（React）

```bash
# 运行所有测试
npm test

# 运行特定测试文件
npm test user.test.ts

# 监视模式
npm test -- --watch

# 覆盖率报告
npm test -- --coverage
```

## 验证命令

### 运行测试

```bash
# Python
pytest tests/ -v

# TypeScript
npm test
```

### 检查覆盖率

```bash
# Python
pytest --cov=app --cov-report=term-missing

# TypeScript
npm test -- --coverage --watchAll=false
```

### 类型检查

```bash
# Python
mypy app/

# TypeScript
tsc --noEmit
```

## 最佳实践

1. **测试命名**：使用描述性的测试名称，说明测试的内容和期望
2. **AAA模式**：Arrange（准备）、Act（执行）、Assert（断言）
3. **独立性**：每个测试应该独立运行，不依赖其他测试
4. **快速反馈**：单元测试应该快速执行（<1秒）
5. **有意义**：测试应该验证重要的业务逻辑，而不是琐碎的细节
6. **维护性**：测试代码应该和生产代码一样清晰和可维护

## 输出检查清单

完成TDD循环后，确保：
- [ ] 测试在编写功能代码之前编写
- [ ] 测试先失败（红色）
- [ ] 功能代码最少且简单
- [ ] 测试通过（绿色）
- [ ] 代码已重构
- [ ] 测试仍然通过
- [ ] 没有未测试的代码
- [ ] 所有验证命令通过

## 下一步

完成TDD实施后，应该调用 `requesting-code-review` 技能进行代码审查，或继续下一个任务的TDD循环。