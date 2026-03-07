---
name: systematic-debugging
description: 使用四阶段根本原因分析过程系统化地调试问题：复现、隔离、分析、修复。遇到bug或异常时必须调用。
---

# 系统化调试（Systematic Debugging）

## 何时使用

此技能应该在以下情况下自动激活：
- 代码出现bug或异常
- 测试失败
- 用户报告问题
- 功能行为不符合预期
- 性能问题

## 核心原则

1. **系统化而非猜测**：遵循结构化的调试流程
2. **证据优先**：基于事实和数据，而非假设
3. **最小化变更**：一次只改变一个变量
4. **记录一切**：记录观察、假设和结果
5. **验证修复**：确保问题真正解决且没有引入新问题

## 四阶段调试流程

### 阶段1：复现问题（Reproduce）

**目标**：能够稳定地复现问题

**步骤**：

1. **收集信息**
   - 错误消息和堆栈跟踪
   - 复现步骤
   - 环境信息（操作系统、版本、配置）
   - 日志和输出

2. **创建最小复现**
   - 简化场景
   - 移除无关代码
   - 使用测试用例
   - 确保可重复

3. **验证复现**
   - 多次尝试
   - 不同环境
   - 记录成功/失败率

**示例**：

```python
# test_reproduce_bug.py

def test_bug_reproduction():
    """复现bug的最小测试用例"""
    # Arrange
    calculator = Calculator()
    input_value = 0

    # Act
    result = calculator.divide(10, input_value)

    # Assert - 应该失败
    assert result is not None  # 这里会失败：ZeroDivisionError
```

**验证清单**：
- [ ] 问题可以稳定复现
- [ ] 有清晰的复现步骤
- [ ] 错误消息已记录
- [ ] 最小复现已创建

### 阶段2：隔离问题（Isolate）

**目标**：确定问题的根本原因

**步骤**：

1. **二分法调试**
   - 注释掉一半代码
   - 测试是否仍然失败
   - 重复直到定位问题代码

2. **日志和断点**
   - 添加日志语句
   - 设置断点
   - 观察变量值
   - 跟踪执行流程

3. **控制变量**
   - 一次只改变一个变量
   - 测试不同输入
   - 测试不同配置
   - 测试不同环境

**示例**：

```python
def divide(a, b):
    print(f"DEBUG: divide({a}, {b})")  # 添加日志
    print(f"DEBUG: b = {b}, type = {type(b)}")

    if b == 0:
        print("DEBUG: Division by zero detected")
        raise ValueError("Cannot divide by zero")

    print(f"DEBUG: Performing division")
    result = a / b
    print(f"DEBUG: result = {result}")
    return result
```

**验证清单**：
- [ ] 问题代码已定位
- [ ] 根本原因已识别
- [ ] 相关变量已分析
- [ ] 执行流程已理解

### 阶段3：分析原因（Analyze）

**目标**：理解为什么问题发生

**步骤**：

1. **根本原因分析**
   - 使用"5个为什么"技术
   - 分析代码逻辑
   - 检查数据流
   - 审查依赖关系

2. **防御性分析**
   - 检查边界条件
   - 验证输入
   - 考虑并发问题
   - 检查资源限制

3. **条件等待分析**
   - 检查时序问题
   - 验证异步操作
   - 检查竞态条件
   - 分析超时设置

**示例**：

```python
# 根本原因分析
# 为什么会除以零？
# 1. 输入值b是0
# 2. 为什么b是0？
# 3. 因为用户输入了0
# 4. 为什么允许输入0？
# 5. 因为没有输入验证
# 根本原因：缺少输入验证

# 修复方案：添加输入验证
def divide(a, b):
    if not isinstance(a, (int, float)):
        raise TypeError("a must be a number")
    if not isinstance(b, (int, float)):
        raise TypeError("b must be a number")
    if b == 0:
        raise ValueError("Cannot divide by zero")

    return a / b
```

**验证清单**：
- [ ] 根本原因已确定
- [ ] 边界条件已检查
- [ ] 并发问题已考虑
- [ ] 时序问题已分析

### 阶段4：修复验证（Fix and Verify）

**目标**：修复问题并验证解决

**步骤**：

1. **实施修复**
   - 编写修复代码
   - 添加测试用例
   - 更新文档
   - 添加注释

2. **验证修复**
   - 运行复现测试
   - 运行所有测试
   - 测试边缘情况
   - 测试不同环境

3. **防止回归**
   - 添加回归测试
   - 更新测试套件
   - 代码审查
   - 文档更新

**示例**：

```python
# test_divide.py

def test_divide_normal():
    """测试正常除法"""
    result = divide(10, 2)
    assert result == 5

def test_divide_by_zero():
    """测试除以零"""
    with pytest.raises(ValueError, match="Cannot divide by zero"):
        divide(10, 0)

def test_divide_invalid_input():
    """测试无效输入"""
    with pytest.raises(TypeError, match="must be a number"):
        divide("10", 2)
```

**验证清单**：
- [ ] 修复已实施
- [ ] 复现测试通过
- [ ] 所有测试通过
- [ ] 边缘情况已测试
- [ ] 回归测试已添加

## 调试技术

### 1. 根本原因追踪（Root Cause Tracing）

**5个为什么技术**：

```
问题：页面加载很慢

为什么1？因为数据库查询很慢
为什么2？因为查询没有使用索引
为什么3？因为索引没有创建
为什么4？因为开发时没有考虑性能
为什么5？因为缺少性能测试流程

根本原因：缺少性能测试流程
```

### 2. 防御深度（Defense in Depth）

**多层验证**：

```python
def process_payment(amount, user_id):
    # 第1层：输入验证
    if not isinstance(amount, (int, float)) or amount <= 0:
        raise ValueError("Invalid amount")

    # 第2层：业务规则验证
    if amount > user.max_payment_limit:
        raise ValueError("Amount exceeds limit")

    # 第3层：数据库事务验证
    try:
        with db.transaction():
            user.balance -= amount
            db.commit()
    except Exception as e:
        db.rollback()
        raise

    # 第4层：外部服务验证
    if not payment_gateway.verify(amount, user_id):
        raise ValueError("Payment verification failed")

    return True
```

### 3. 条件等待（Condition-based Waiting）

**处理异步操作**：

```python
import time
from typing import Callable

def wait_for_condition(
    condition: Callable[[], bool],
    timeout: float = 10.0,
    interval: float = 0.1,
    error_message: str = "Condition not met"
):
    """等待条件满足"""
    start_time = time.time()
    while time.time() - start_time < timeout:
        if condition():
            return True
        time.sleep(interval)
    raise TimeoutError(error_message)

# 使用示例
def wait_for_element_visible(page, selector):
    """等待元素可见"""
    return wait_for_condition(
        lambda: page.is_visible(selector),
        timeout=5.0,
        error_message=f"Element {selector} not visible"
    )
```

## 调试工具

### Python调试

```python
# 使用pdb调试器
import pdb; pdb.set_trace()

# 使用ipdb（更好的界面）
import ipdb; ipdb.set_trace()

# 使用print调试
print(f"DEBUG: variable = {variable}")

# 使用logging
import logging
logging.basicConfig(level=logging.DEBUG)
logger = logging.getLogger(__name__)
logger.debug(f"variable = {variable}")
```

### JavaScript/TypeScript调试

```javascript
// 使用console.log
console.log('DEBUG:', variable);

// 使用debugger语句
debugger;

// 使用Chrome DevTools
// 1. 打开开发者工具
// 2. 设置断点
// 3. 检查变量和调用栈
```

### 日志分析

```bash
# 查看最近的错误
tail -f logs/error.log

# 搜索特定错误
grep "ERROR" logs/app.log

# 统计错误类型
grep "ERROR" logs/app.log | cut -d: -f3 | sort | uniq -c
```

## 常见问题模式

### 1. 空指针/None引用

**症状**：`AttributeError: 'NoneType' object has no attribute '...'`

**调试**：
```python
# 添加None检查
if obj is None:
    raise ValueError("Object cannot be None")

# 使用类型提示
def process(obj: Optional[MyClass]) -> Result:
    if obj is None:
        return Result.error("Object is None")
    return obj.process()
```

### 2. 竞态条件

**症状**：间歇性失败，难以复现

**调试**：
```python
import threading

# 使用锁
lock = threading.Lock()

def critical_section():
    with lock:
        # 临界区代码
        pass
```

### 3. 内存泄漏

**症状**：内存使用持续增长

**调试**：
```python
import tracemalloc

tracemalloc.start()

# ... 运行代码 ...

snapshot = tracemalloc.take_snapshot()
top_stats = snapshot.statistics('lineno')

for stat in top_stats[:10]:
    print(stat)
```

### 4. 性能问题

**症状**：响应慢，CPU使用率高

**调试**：
```python
import cProfile
import pstats

profiler = cProfile.Profile()
profiler.enable()

# ... 运行代码 ...

profiler.disable()
stats = pstats.Stats(profiler)
stats.sort_stats('cumulative')
stats.print_stats(10)
```

## 验证修复

### 1. 单元测试

```python
def test_bug_fix():
    """验证bug修复"""
    # 复现原始bug
    with pytest.raises(ValueError):
        buggy_function()

    # 验证修复
    result = fixed_function()
    assert result is not None
```

### 2. 集成测试

```python
def test_bug_fix_integration():
    """验证修复在集成环境中工作"""
    client = TestClient(app)
    response = client.post("/endpoint", json={...})
    assert response.status_code == 200
    assert response.json()["status"] == "success"
```

### 3. 回归测试

```python
def test_no_regression():
    """确保修复没有破坏其他功能"""
    # 测试相关功能
    test_related_feature_1()
    test_related_feature_2()
    test_related_feature_3()
```

## 最佳实践

1. **记录调试过程**：记录每一步的观察和结论
2. **使用版本控制**：在调试前提交代码，可以轻松回退
3. **编写测试**：为每个bug编写测试，防止回归
4. **代码审查**：让同事审查修复方案
5. **文档更新**：更新相关文档和注释
6. **根本原因分析**：不只是修复症状，要找到根本原因

## 输出检查清单

完成调试后，确保：
- [ ] 问题可以稳定复现
- [ ] 根本原因已确定
- [ ] 修复已实施
- [ ] 测试已添加
- [ ] 所有测试通过
- [ ] 修复已验证
- [ ] 文档已更新
- [ ] 没有引入新问题

## 下一步

调试完成后，应该调用 `requesting-code-review` 技能审查修复，或继续下一个任务。