# 场景管理调试指南

## 问题：点击新增场景及编辑都没有反应

### 已修复的问题

#### 1. 缺少openmax函数定义

**问题描述**：  
scene_list.html中使用了`openmax()`函数来打开弹窗，但该函数未在公共JS中定义，导致点击按钮无反应。

**修复方案**：  
在scene_list.html中添加了openmax函数定义：

```javascript
function openmax(url){
  console.log('[场景管理] 打开弹窗:', url);
  var index = layer.open({
    type: 2,
    title: '场景编辑',
    area: ['90%', '90%'],
    maxmin: true,
    content: url,
    success: function(layero, index){
      console.log('[场景管理] 弹窗打开成功');
    },
    end: function(){
      console.log('[场景管理] 弹窗关闭，刷新列表');
      if(typeof tableIns !== 'undefined'){
        tableIns.reload();
      }
    }
  });
  layer.full(index);
}
```

#### 2. scene_edit.html缺少DOCTYPE声明

**问题描述**：  
HTML文件缺少`<!DOCTYPE html>`声明，可能导致浏览器以怪异模式渲染。

**修复方案**：  
在文件开头添加了完整的HTML5声明。

#### 3. 缺少调试日志

**问题描述**：  
缺少JavaScript调试日志，无法快速定位问题。

**修复方案**：  
在关键位置添加了详细的console.log日志：
- 页面加载时
- DOM加载完成时
- 模型选择变化时
- AJAX请求前后
- 函数回调执行时

---

## 调试步骤

### 1. 打开浏览器开发者工具

按 F12 或右键 → 检查元素，打开开发者工具。

### 2. 查看Console标签

点击"新增场景"按钮后，在Console中应该看到以下日志：

```
[场景管理] 打开弹窗: /AiTravelPhoto/scene_edit
[场景管理] 弹窗打开成功
[场景编辑] 页面开始加载
[场景编辑] 场景ID: 0
[场景编辑] 模型ID: 0
[场景编辑] JavaScript开始执行
[场景编辑] 初始化变量: {isEditMode: false, currentModelId: 0, ...}
[场景编辑] DOM加载完成
[场景编辑] 新增模式或未选择模型
```

### 3. 如果看到错误信息

#### 错误1：openmax is not defined
**原因**：openmax函数未定义  
**解决**：已修复，清除浏览器缓存后重试

#### 错误2：jQuery is not defined
**原因**：jQuery未加载  
**解决**：检查public/js.html是否正确引入jQuery

#### 错误3：layui is not defined
**原因**：Layui未加载  
**解决**：检查public/js.html是否正确引入Layui

#### 错误4：404 Not Found
**原因**：控制器方法或路由不存在  
**解决**：检查AiTravelPhoto.php中是否有scene_edit方法

### 4. 查看Network标签

点击"新增场景"后，查看Network标签：

**正常情况**：
- 应该看到一个对`/AiTravelPhoto/scene_edit`的请求
- 状态码应该是200
- 响应类型应该是HTML

**异常情况**：
- 如果状态码是404：控制器方法不存在或路由错误
- 如果状态码是500：服务器内部错误，查看PHP日志
- 如果没有请求：openmax函数未执行，检查JavaScript错误

---

## 常见问题排查

### 问题1：点击按钮完全没反应

**排查步骤**：
1. 打开Console查看是否有JavaScript错误
2. 检查openmax函数是否定义：在Console输入`typeof openmax`，应该返回`function`
3. 检查jQuery是否加载：在Console输入`typeof $`，应该返回`function`
4. 检查按钮的onclick事件：右键按钮 → 检查元素，查看是否有onclick属性

**解决方案**：
- 清除浏览器缓存：Ctrl + F5 强制刷新
- 检查scene_list.html是否正确include了public/js.html
- 检查openmax函数是否在正确的位置定义

### 问题2：弹窗打开但是空白

**排查步骤**：
1. 查看Network标签，找到scene_edit请求
2. 查看响应内容是否正常
3. 查看是否返回了HTML内容
4. 查看响应状态码是否为200

**解决方案**：
- 如果响应为空：检查控制器是否正确返回视图
- 如果状态码500：查看PHP错误日志
- 如果内容不对：检查scene_edit方法的逻辑

### 问题3：弹窗打开后报JavaScript错误

**排查步骤**：
1. 在弹窗中按F12打开开发者工具
2. 查看Console中的错误信息
3. 根据错误信息定位问题

**常见错误**：
- `form is not defined`：Layui未正确加载，检查public/js.html
- `$ is not defined`：jQuery未加载
- `AJAX请求失败`：后端接口问题，查看Network标签

### 问题4：编辑场景时没反应

**排查步骤**：
1. 检查编辑按钮的URL是否正确
2. 查看URL中是否包含id参数
3. 查看Console中的调试日志

**解决方案**：
- 确保编辑按钮的onclick包含正确的ID：`openmax('{:url('scene_edit')}/id/{{d.id}}')`
- 检查控制器是否正确接收id参数
- 查看是否能正确加载场景数据

---

## 测试清单

### 新增场景测试

- [ ] 点击"新增场景"按钮
- [ ] 弹窗是否正常打开
- [ ] 是否显示"添加场景"标题
- [ ] 模型下拉列表是否有选项
- [ ] 选择模型后是否显示API配置和参数表单
- [ ] 填写表单后点击提交是否成功
- [ ] 提交成功后弹窗是否关闭
- [ ] 列表是否自动刷新

### 编辑场景测试

- [ ] 点击某个场景的"编辑"按钮
- [ ] 弹窗是否正常打开
- [ ] 是否显示"编辑场景"标题
- [ ] 是否自动选中原来的模型
- [ ] 是否自动显示API配置和参数
- [ ] 参数值是否正确回填
- [ ] 修改后点击提交是否成功
- [ ] 提交成功后列表是否更新

### 控制台日志检查

新增场景应该看到的日志：
```
[场景管理] 打开弹窗: /AiTravelPhoto/scene_edit
[场景管理] 弹窗打开成功
[场景编辑] 页面开始加载
[场景编辑] JavaScript开始执行
[场景编辑] DOM加载完成
[场景编辑] 新增模式或未选择模型
```

编辑场景应该看到的日志：
```
[场景管理] 打开弹窗: /AiTravelPhoto/scene_edit/id/1
[场景管理] 弹窗打开成功
[场景编辑] 页面开始加载
[场景编辑] 场景ID: 1
[场景编辑] 模型ID: 1
[场景编辑] JavaScript开始执行
[场景编辑] 初始化变量: {isEditMode: true, currentModelId: 1, ...}
[场景编辑] DOM加载完成
[场景编辑] 编辑模式，加载API配置和参数
[场景编辑] 开始加载API配置, modelId: 1
[场景编辑] API配置响应: {code: 0, data: [...]}
[场景编辑] 加载了 X 个API配置
[场景编辑] API配置加载完成
[场景编辑] 所有区域显示完成
```

---

## 文件清单

### 已修改的文件

1. **/www/wwwroot/eivie/app/view/ai_travel_photo/scene_list.html**
   - 添加了openmax函数定义
   - 添加了调试日志
   - 添加了错误处理

2. **/www/wwwroot/eivie/app/view/ai_travel_photo/scene_edit.html**
   - 添加了DOCTYPE声明
   - 添加了详细的调试日志
   - 添加了AJAX错误处理

### 需要检查的文件

1. **/www/wwwroot/eivie/app/controller/AiTravelPhoto.php**
   - 确认scene_edit方法存在
   - 确认get_model_api_configs方法存在
   - 确认get_model_params方法存在

2. **/www/wwwroot/eivie/app/view/public/js.html**
   - 确认jQuery已引入
   - 确认Layui已引入

---

## 快速验证命令

```bash
# 检查文件是否存在
ls -lh /www/wwwroot/eivie/app/view/ai_travel_photo/scene_list.html
ls -lh /www/wwwroot/eivie/app/view/ai_travel_photo/scene_edit.html

# 检查控制器方法
grep -n "function scene_edit" /www/wwwroot/eivie/app/controller/AiTravelPhoto.php

# 检查PHP语法
php -l /www/wwwroot/eivie/app/controller/AiTravelPhoto.php

# 查看PHP错误日志
tail -50 /www/server/php/7.4/var/log/php-fpm.log
```

---

## 联系支持

如果按照以上步骤仍无法解决问题，请提供以下信息：

1. 浏览器Console中的完整错误信息
2. Network标签中的请求/响应详情
3. PHP错误日志（如果有）
4. 操作系统和浏览器版本

---

**最后更新**: 2026-02-04  
**文档版本**: 1.0
