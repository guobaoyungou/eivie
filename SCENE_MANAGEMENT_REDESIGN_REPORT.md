# AI旅拍场景管理重构实施报告

**项目名称**：AI旅拍场景管理功能重构  
**实施日期**：2026-02-04  
**状态**：✅ 开发完成，待测试验证

---

## 一、需求概述

### 原需求回顾
用户提出："因模型配置和API配置发生了变化，请重新设计并构建场景管理，我希望新增场景第一个是需选对应的模型，动态加载模型所需的参数。填写好场景信息后，可调用用API一键生成封面图（成果图）"

### 核心需求拆解
1. **模型优先选择**：新增场景时首先选择AI模型实例
2. **动态参数表单**：根据所选模型自动加载该模型的参数定义
3. **API配置关联**：选择模型后加载可用的API配置
4. **一键生成封面图**：填写完场景信息后，可调用API自动生成封面图

---

## 二、实施内容

### 2.1 数据库结构调整

#### 修改表：ddwx_ai_travel_photo_scene
```sql
-- 添加API配置关联字段
ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD COLUMN `api_config_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联API配置ID(ddwx_api_config.id)' AFTER `model_id`;

-- 添加索引
ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD INDEX `idx_api_config_id` (`api_config_id`);

-- 确保 model_params 字段类型正确
ALTER TABLE `ddwx_ai_travel_photo_scene` 
MODIFY COLUMN `model_params` text COMMENT '模型参数JSON';
```

**SQL文件位置**：`/www/wwwroot/eivie/database/migrations/scene_management_redesign_alter.sql`

**执行状态**：⚠️ SQL文件已创建，需手动执行（数据库连接需要密码）

---

### 2.2 后端接口开发

#### 文件：`/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`

#### 新增接口1：获取模型参数定义
```php
/**
 * 获取AI模型的参数定义（AJAX接口）
 * URL: /AiTravelPhoto/get_model_params
 * Method: GET
 * Params: model_id
 */
public function get_model_params()
```

**功能**：
- 查询 `ddwx_ai_model_parameter` 表
- 返回指定模型的所有参数定义
- 包含参数类型、默认值、验证规则等

**返回数据格式**：
```json
{
  "code": 0,
  "msg": "获取成功",
  "data": [
    {
      "id": 1,
      "param_name": "提示词",
      "param_code": "prompt",
      "param_type": "textarea",
      "default_value": "",
      "is_required": 1,
      "description": "正向提示词，描述想要生成的内容"
    }
  ]
}
```

#### 新增接口2：获取模型关联的API配置
```php
/**
 * 获取模型关联的API配置列表（AJAX接口）
 * URL: /AiTravelPhoto/get_model_api_configs
 * Method: GET
 * Params: model_id
 */
public function get_model_api_configs()
```

**功能**：
- 查询 `ddwx_api_config` 表
- 权限筛选：自己的配置 OR 公开的配置（scope_type=1）
- 返回可用的API配置列表

**返回数据格式**：
```json
{
  "code": 0,
  "msg": "获取成功",
  "data": [
    {
      "id": 1,
      "api_name": "阿里云通义万相API",
      "provider": "aliyun",
      "scope_type": 1
    }
  ]
}
```

#### 新增接口3：一键生成场景封面图
```php
/**
 * 一键生成场景封面图（AJAX接口）
 * URL: /AiTravelPhoto/generate_scene_cover
 * Method: POST
 * Params: scene_id
 */
public function generate_scene_cover()
```

**功能流程**：
1. 查询场景信息
2. 查询API配置
3. 解析模型参数（从 model_params JSON字段）
4. 调用AI API生成图片
5. 保存生成记录到 `ddwx_ai_travel_photo_generation`
6. 更新场景的 cover 字段
7. 返回生成结果

**返回数据格式**：
```json
{
  "code": 0,
  "msg": "生成成功",
  "data": {
    "image_url": "https://example.com/cover.jpg",
    "generation_id": 123
  }
}
```

#### 新增私有方法：AI API调用封装
```php
/**
 * 调用AI API生成图片（通用方法）
 */
private function callAiApi($apiConfig, $params, $scene = [])
private function callAliyunApi($apiConfig, $params, $scene = [])
private function callBaiduApi($apiConfig, $params, $scene = [])
private function callOpenAiApi($apiConfig, $params, $scene = [])
```

**说明**：目前为框架代码，返回待实现提示，需要根据实际API文档补充实现逻辑。

#### 修改接口：场景保存逻辑优化
```php
public function scene_edit()
```

**新增逻辑**：
1. 处理 `api_config_id` 字段
2. 提取所有 `param_` 开头的字段，合并为 `model_params` JSON
3. 支持 `generate_cover` 参数，标记是否需要生成封面图
4. 返回 `scene_id`，供前端调用生成封面图接口

**参数提取示例**：
```php
// 提取所有 param_ 开头的字段
$modelParams = [];
foreach ($data as $key => $value) {
    if (strpos($key, 'param_') === 0) {
        $paramCode = substr($key, 6); // 去掉 param_ 前缀
        $modelParams[$paramCode] = $value;
        unset($data[$key]); // 从主数据中移除
    }
}

// 保存为JSON
$data['model_params'] = json_encode($modelParams, JSON_UNESCAPED_UNICODE);
```

#### 修改：模型查询改为从 ai_model_instance 表
```php
// 查询可用的模型实例（自己的模型 或 系统公开的模型）
$models = Db::name('ai_model_instance')
    ->where('status', 1)
    ->where(function($query) use ($targetBid) {
        $query->where('bid', $targetBid)
              ->whereOr('is_public', 1);
    })
    ->field('id, model_code, model_name, provider, description')
    ->order('is_system DESC, sort ASC, id ASC')
    ->select();
```

---

### 2.3 前端界面改造

#### 文件：`/www/wwwroot/eivie/app/view/ai_travel_photo/scene_edit.html`

#### 界面结构重构

**原界面结构**：
- 所有字段平铺排列
- 提示词字段固定（prompt、negative_prompt等）
- AI模型选择在中间位置

**新界面结构（分步骤）**：

##### 第一步：选择AI模型
```html
<div class="layui-form-item">
  <label class="layui-form-label"><span style="color:red;">*</span> 选择模型：</label>
  <div class="layui-input-inline" style="width: 400px;">
    <select name="model_id" lay-filter="model_id" lay-verify="required">
      <option value="">请选择AI模型</option>
      {volist name="models" id="model"}
      <option value="{$model.id}">
        {$model.model_name} ({$model.provider})
      </option>
      {/volist}
    </select>
  </div>
  <div class="layui-form-mid layui-word-aux">首先选择要使用的AI模型</div>
</div>
```

##### 第二步：选择API配置
```html
<div class="layui-form-item" id="api_config_wrap" style="display:none;">
  <label class="layui-form-label"><span style="color:red;">*</span> API配置：</label>
  <div class="layui-input-inline" style="width: 400px;">
    <select name="api_config_id" lay-verify="required">
      <option value="">请选择API配置</option>
    </select>
  </div>
  <div class="layui-form-mid layui-word-aux">选择该模型对应的API配置</div>
</div>
```

##### 第三步：动态参数表单区域
```html
<div id="model_params_container" style="display:none;">
  <div class="layui-form-item">
    <div class="layui-col-md12">
      <fieldset class="layui-elem-field layui-field-title">
        <legend>模型参数配置</legend>
      </fieldset>
    </div>
  </div>
  <div id="dynamic_params">
    <!-- 动态生成的参数字段将插入此处 -->
  </div>
</div>
```

##### 第四步：场景基础信息
```html
<div id="scene_basic_info" style="display:none;">
  <fieldset class="layui-elem-field layui-field-title">
    <legend>场景基础信息</legend>
  </fieldset>
  
  <!-- 场景名称、分类、门店、是否公共 -->
  <!-- 封面图（带一键生成按钮）-->
  <!-- 背景图 -->
  <!-- 场景描述 -->
  <!-- 宽高比、排序、标签、状态、推荐 -->
</div>
```

#### 封面图区域优化
```html
<div class="layui-form-item">
  <label class="layui-form-label">封面图：</label>
  <button type="button" class="layui-btn layui-btn-primary" onclick="uploader(this)">上传图片</button>
  <button type="button" class="layui-btn layui-btn-normal" id="generateCoverBtn">一键生成封面图</button>
  <div class="layui-form-mid layui-word-aux">建议尺寸：400×400像素</div>
  <!-- 图片预览区域 -->
</div>
```

#### JavaScript核心逻辑

##### 1. 页面初始化（编辑模式）
```javascript
// 页面加载完成后，如果是编辑模式，加载现有数据
$(function(){
  if (isEditMode && currentModelId > 0) {
    loadApiConfigs(currentModelId, function(){
      loadModelParams(currentModelId, function(){
        $('#api_config_wrap').show();
        $('#model_params_container').show();
        $('#scene_basic_info').show();
        form.render();
      });
    });
  }
});
```

##### 2. 监听模型选择变化
```javascript
form.on('select(model_id)', function(data){
  var modelId = data.value;
  
  if (modelId) {
    // 1. 加载API配置列表
    loadApiConfigs(modelId, function(){
      // 2. 加载模型参数表单
      loadModelParams(modelId, function(){
        // 3. 显示后续区域
        $('#api_config_wrap').show();
        $('#model_params_container').show();
        $('#scene_basic_info').show();
        form.render();
      });
    });
  } else {
    // 隐藏后续区域
    $('#api_config_wrap').hide();
    $('#model_params_container').hide();
    $('#scene_basic_info').hide();
  }
});
```

##### 3. 加载API配置列表
```javascript
function loadApiConfigs(modelId, callback) {
  $.get('/AiTravelPhoto/get_model_api_configs', {model_id: modelId}, function(res){
    if (res.code == 0) {
      var html = '<option value="">请选择API配置</option>';
      $.each(res.data, function(i, item){
        var scopeText = item.scope_type == 1 ? '[公开]' : '[私有]';
        html += '<option value="'+ item.id +'">'+ item.api_name +' ('+ item.provider +') '+ scopeText +'</option>';
      });
      $('select[name="api_config_id"]').html(html);
      form.render('select');
      if (callback) callback();
    }
  });
}
```

##### 4. 加载模型参数表单
```javascript
function loadModelParams(modelId, callback) {
  $.get('/AiTravelPhoto/get_model_params', {model_id: modelId}, function(res){
    if (res.code == 0) {
      var html = '';
      // 解析现有参数值
      var existParams = JSON.parse(currentModelParams);
      
      $.each(res.data, function(i, param){
        html += renderParamField(param, existParams[param.param_code] || param.default_value);
      });
      
      $('#dynamic_params').html(html);
      form.render();
      if (callback) callback();
    }
  });
}
```

##### 5. 渲染参数字段（支持多种类型）
```javascript
function renderParamField(param, value) {
  var html = '<div class="layui-form-item">';
  var required = param.is_required == 1 ? '<span style="color:red;">*</span> ' : '';
  var verify = param.is_required == 1 ? ' lay-verify="required"' : '';
  
  html += '<label class="layui-form-label">'+ required + param.param_name +'：</label>';
  html += '<div class="layui-input-block" style="width: 600px;">';
  
  switch(param.param_type) {
    case 'text':
      html += '<input type="text" name="param_'+ param.param_code +'" value="'+ value +'" class="layui-input"'+ verify +'>';
      break;
    case 'textarea':
      html += '<textarea name="param_'+ param.param_code +'" class="layui-textarea"'+ verify +'>'+ value +'</textarea>';
      break;
    case 'number':
      html += '<input type="number" name="param_'+ param.param_code +'" value="'+ value +'" class="layui-input"'+ verify +'>';
      break;
    case 'select':
      var options = JSON.parse(param.options_json);
      html += '<select name="param_'+ param.param_code +'"'+ verify +'>';
      $.each(options, function(j, opt){
        html += '<option value="'+ opt.value +'">'+ opt.label +'</option>';
      });
      html += '</select>';
      break;
    case 'switch':
      var checked = (value == 1) ? 'checked' : '';
      html += '<input type="checkbox" name="param_'+ param.param_code +'" value="1" lay-skin="switch" '+ checked +'>';
      break;
  }
  
  if (param.description) {
    html += '<div class="layui-form-mid layui-word-aux">'+ param.description +'</div>';
  }
  
  html += '</div></div>';
  return html;
}
```

##### 6. 一键生成封面图
```javascript
$('#generateCoverBtn').click(function(){
  var sceneId = $('input[name="id"]').val();
  
  if (!sceneId || sceneId == 0) {
    layer.msg('请先保存场景，再生成封面图', {icon: 0});
    return;
  }
  
  layer.confirm('确定要使用当前参数生成封面图吗？', {icon: 3}, function(index){
    layer.close(index);
    var loadIndex = layer.load(1, {content: '正在生成中，请稍候...', time: 60000});
    
    $.post('/AiTravelPhoto/generate_scene_cover', {scene_id: sceneId}, function(res){
      layer.close(loadIndex);
      if (res.code == 0) {
        layer.msg('生成成功', {icon: 1});
        // 更新封面图预览
        $('#cover').val(res.data.image_url);
        $('#coverPreview img').attr('src', res.data.image_url).show();
        $('#coverPreview .layui-imgbox-close').show();
      } else {
        layer.msg(res.msg, {icon: 2, time: 3000});
      }
    });
  });
});
```

---

## 三、数据流程图

### 新增场景流程
```
用户操作                  前端交互                    后端处理                      数据库操作
┌─────────┐            ┌──────────┐              ┌──────────┐               ┌──────────┐
│ 1.打开新增│            │加载模型列表│  ─────────>   │查询ai_model│  ─────────>    │ai_model_ │
│ 场景页面 │            │            │              │_instance  │               │instance  │
└─────────┘            └──────────┘              └──────────┘               └──────────┘
     │                        │                         │
     v                        v                         v
┌─────────┐            ┌──────────┐              ┌──────────┐               ┌──────────┐
│ 2.选择  │            │触发change │  ─────────>   │get_model_│  ─────────>    │api_config│
│ AI模型  │   ───────> │事件       │              │api_configs│               │          │
└─────────┘            └──────────┘              └──────────┘               └──────────┘
     │                        │                         │
     v                        v                         v
     │                  ┌──────────┐              ┌──────────┐               ┌──────────┐
     │                  │动态生成API│              │get_model_│  ─────────>    │ai_model_ │
     │                  │配置下拉框 │  <────────   │params    │               │parameter │
     │                  └──────────┘              └──────────┘               └──────────┘
     v                        │                         │
┌─────────┐                   v                         v
│ 3.选择  │            ┌──────────┐              ┌──────────┐
│ API配置 │            │动态生成参│              │渲染参数表│
└─────────┘            │数表单字段│  <────────   │单到页面  │
     │                 └──────────┘              └──────────┘
     v                        │
┌─────────┐                   v
│ 4.填写  │            ┌──────────┐
│ 模型参数│            │用户填写各│
└─────────┘            │项参数值  │
     │                 └──────────┘
     v                        │
┌─────────┐                   v
│ 5.填写  │            ┌──────────┐
│ 基础信息│            │填写名称、│
└─────────┘            │分类等    │
     │                 └──────────┘
     v                        │
┌─────────┐                   v                         v
│ 6.提交  │            ┌──────────┐              ┌──────────┐               ┌──────────┐
│ 保存    │   ───────> │AJAX POST │  ─────────>   │scene_edit│  ─────────>    │ai_travel_│
└─────────┘            │表单数据  │              │保存场景  │               │photo_scene│
                       └──────────┘              └──────────┘               └──────────┘
                              │                         │
                              │                         v
                              │                   ┌──────────┐
                              │                   │提取param_│
                              │                   │字段合并为│
                              │                   │model_params
                              │                   └──────────┘
                              v                         │
                       ┌──────────┐                     v
                       │返回success│  <──────────  ┌──────────┐
                       │和scene_id │               │INSERT或  │
                       └──────────┘               │UPDATE    │
                                                  └──────────┘
```

### 生成封面图流程
```
用户操作                  前端交互                    后端处理                      AI API调用
┌─────────┐            ┌──────────┐              ┌──────────┐               ┌──────────┐
│点击生成 │   ───────> │发送AJAX  │  ─────────>   │generate_ │  ─────────>    │查询场景  │
│封面图按钮│            │请求      │              │scene_cover│               │信息      │
└─────────┘            └──────────┘              └──────────┘               └──────────┘
                              │                         │                         │
                              │                         v                         v
                              │                   ┌──────────┐               ┌──────────┐
                              │                   │查询API   │               │查询API   │
                              │                   │配置信息  │  ─────────>    │配置密钥  │
                              │                   └──────────┘               └──────────┘
                              │                         │                         │
                              │                         v                         v
                              │                   ┌──────────┐               ┌──────────┐
                              │                   │解析model_│               │根据provider│
                              │                   │params    │               │调用对应API│
                              │                   └──────────┘               └──────────┘
                              │                         │                         │
                              │                         v                         v
                              │                   ┌──────────┐               ┌──────────┐
                              │                   │callAiApi │  ─────────>    │阿里云/百度│
                              │                   │通用方法  │               │/OpenAI   │
                              │                   └──────────┘               └──────────┘
                              │                         │                         │
                              │                         │        成功              │
                              │                         v    <──────────          v
                              │                   ┌──────────┐               ┌──────────┐
                              │                   │保存生成  │               │返回图片  │
                              │                   │记录      │               │URL       │
                              │                   └──────────┘               └──────────┘
                              │                         │
                              │                         v
                              │                   ┌──────────┐
                              │                   │更新场景  │
                              │                   │cover字段 │
                              │                   └──────────┘
                              │                         │
                              v                         v
                       ┌──────────┐              ┌──────────┐
                       │接收响应  │  <────────   │返回JSON  │
                       │更新预览  │              │image_url │
                       └──────────┘              └──────────┘
```

---

## 四、字段映射规范

### model_params JSON格式示例

#### 格式规范
```json
{
  "prompt": "海边度假风景，蓝天白云，椰树摇曳，细腻的沙滩",
  "negative_prompt": "低质量，模糊，变形，水印",
  "steps": 50,
  "cfg_scale": 7.5,
  "width": 1024,
  "height": 1024,
  "seed": -1,
  "sampler": "DPM++ 2M Karras"
}
```

#### 存储逻辑
```php
// 1. 从表单提取所有 param_ 开头的字段
$modelParams = [];
foreach ($data as $key => $value) {
    if (strpos($key, 'param_') === 0) {
        $paramCode = substr($key, 6); // 去掉 param_ 前缀
        $modelParams[$paramCode] = $value;
        unset($data[$key]); // 从主数据中移除
    }
}

// 2. 保存为JSON
$data['model_params'] = json_encode($modelParams, JSON_UNESCAPED_UNICODE);
```

#### 读取逻辑
```php
// 从数据库读取
$scene = Db::name('ai_travel_photo_scene')->find($id);

// 解析JSON
$modelParams = !empty($scene['model_params']) ? json_decode($scene['model_params'], true) : [];

// 获取特定参数
$prompt = $modelParams['prompt'] ?? '';
$negativePrompt = $modelParams['negative_prompt'] ?? '';
```

---

## 五、文件清单

### 新增文件
1. `/www/wwwroot/eivie/SCENE_MANAGEMENT_REDESIGN_PLAN.md` - 重构设计方案文档
2. `/www/wwwroot/eivie/database/migrations/scene_management_redesign_alter.sql` - 数据库表结构调整SQL
3. `/www/wwwroot/eivie/SCENE_MANAGEMENT_REDESIGN_REPORT.md` - 本报告文件

### 修改文件
1. `/www/wwwroot/eivie/app/controller/AiTravelPhoto.php`
   - 新增3个AJAX接口方法
   - 新增4个私有方法（API调用）
   - 修改scene_edit保存逻辑
   - 修改模型查询逻辑
   - **代码行变化**：+225行

2. `/www/wwwroot/eivie/app/view/ai_travel_photo/scene_edit.html`
   - 重构表单结构（分4个步骤）
   - 新增动态参数容器
   - 新增一键生成封面图按钮
   - 重写JavaScript逻辑
   - **代码行变化**：+190行, -152行

---

## 六、技术亮点

### 6.1 动态表单渲染
- 根据数据库参数定义动态生成表单字段
- 支持多种参数类型：text、textarea、number、select、switch
- 参数验证规则可配置
- 编辑模式自动回填参数值

### 6.2 数据规范化
- 统一使用 `param_` 前缀标识动态参数
- 后端自动提取并合并为JSON存储
- 前端自动解析JSON并分发到各字段

### 6.3 分步骤交互
- 清晰的操作步骤引导
- 联动显示/隐藏区域
- 避免一次性展示过多字段

### 6.4 API调用抽象
- 提供商无关的统一接口
- 易于扩展新的AI服务提供商
- 错误处理统一管理

### 6.5 兼容旧数据
```php
// 兼容逻辑：如果model_params为空，使用旧的prompt字段
if (empty($modelParams['prompt']) && !empty($scene['prompt'])) {
    $modelParams['prompt'] = $scene['prompt'];
    $modelParams['negative_prompt'] = $scene['negative_prompt'] ?? '';
}
```

---

## 七、测试建议

### 7.1 数据库测试
- [ ] 执行SQL文件，验证字段添加成功
- [ ] 检查索引创建成功
- [ ] 验证 model_params 字段可存储大文本

### 7.2 功能测试

#### 新增场景流程
- [ ] 访问场景新增页面，检查模型列表是否正常显示
- [ ] 选择一个模型，检查API配置是否正确加载
- [ ] 检查动态参数表单是否正确生成
- [ ] 填写参数，提交保存
- [ ] 验证数据库 model_params 字段是否正确存储JSON

#### 编辑场景流程
- [ ] 编辑已有场景，检查模型、API配置、参数是否正确回填
- [ ] 修改参数值，保存
- [ ] 验证修改是否生效

#### 一键生成封面图
- [ ] 点击"一键生成封面图"按钮
- [ ] 检查是否调用后端接口
- [ ] 验证错误提示（未配置API、参数缺失等）
- [ ] （待API实现后）验证封面图生成成功并更新预览

### 7.3 兼容性测试
- [ ] 测试旧场景数据是否正常显示
- [ ] 测试旧场景编辑是否正常
- [ ] 测试新旧场景混合列表显示

### 7.4 权限测试
- [ ] 超级管理员查看场景
- [ ] 普通商家查看场景
- [ ] 门店用户查看场景
- [ ] 验证API配置权限筛选（公开/私有）

---

## 八、待完成事项

### 8.1 数据库操作
⚠️ **优先级：高**
- [ ] 手动执行 `scene_management_redesign_alter.sql`
- [ ] 验证表结构修改成功

### 8.2 AI API实现
⚠️ **优先级：高**
- [ ] 实现 `callAliyunApi` 方法（阿里云通义万相）
- [ ] 实现 `callBaiduApi` 方法（百度文心一言）
- [ ] 实现 `callOpenAiApi` 方法（OpenAI DALL-E）
- [ ] 补充API调用错误处理
- [ ] 添加API调用日志记录

### 8.3 参数定义数据
⚠️ **优先级：中**
- [ ] 在 `ddwx_ai_model_parameter` 表中添加常用参数定义
- [ ] 为每个模型实例配置对应的参数

示例参数定义：
```sql
INSERT INTO `ddwx_ai_model_parameter` VALUES
(1, 1, 'prompt', '正向提示词', 'textarea', 'Prompt', '', 1, '', 100, 1, '描述想要生成的图像内容', NULL, 1, 1726502400, 1726502400),
(2, 1, 'negative_prompt', '负面提示词', 'textarea', 'Negative Prompt', '', 0, '', 90, 1, '排除不想要的元素', NULL, 1, 1726502400, 1726502400),
(3, 1, 'steps', '生成步数', 'number', 'Steps', '50', 0, 'min:1|max:150', 80, 1, '步数越多，生成质量越高，但速度越慢', NULL, 1, 1726502400, 1726502400);
```

### 8.4 功能增强
⚠️ **优先级：低**
- [ ] 批量生成封面图功能
- [ ] 参数预设模板功能
- [ ] 生成历史记录查看
- [ ] AI推荐参数功能

---

## 九、API实现参考

### 阿里云通义万相API示例
```php
private function callAliyunApi($apiConfig, $params, $scene = [])
{
    $apiKey = $apiConfig['api_key'];
    $endpoint = $apiConfig['endpoint_url'];
    
    // 构建请求参数
    $requestData = [
        'model' => 'wanx-v1',
        'input' => [
            'prompt' => $params['prompt'] ?? ''
        ],
        'parameters' => [
            'style' => $params['style'] ?? '<auto>',
            'size' => $params['size'] ?? '1024*1024',
            'n' => 1
        ]
    ];
    
    // 发送HTTP请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'X-DashScope-Async: enable'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 解析响应
    $result = json_decode($response, true);
    
    if ($httpCode == 200 && isset($result['output']['task_id'])) {
        // 异步任务，需要轮询查询结果
        $taskId = $result['output']['task_id'];
        $imageUrl = $this->waitForAliyunTask($apiConfig, $taskId);
        
        if ($imageUrl) {
            return ['success' => true, 'image_url' => $imageUrl];
        } else {
            return ['success' => false, 'error' => '生成超时或失败'];
        }
    } else {
        $error = $result['message'] ?? '未知错误';
        return ['success' => false, 'error' => $error];
    }
}

private function waitForAliyunTask($apiConfig, $taskId, $maxWait = 60)
{
    $endpoint = $apiConfig['endpoint_url'] . '/' . $taskId;
    $startTime = time();
    
    while (time() - $startTime < $maxWait) {
        sleep(2); // 每2秒查询一次
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiConfig['api_key']
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result['output']['task_status'] == 'SUCCEEDED') {
            return $result['output']['results'][0]['url'];
        } elseif ($result['output']['task_status'] == 'FAILED') {
            return false;
        }
    }
    
    return false;
}
```

---

## 十、总结

### 已完成内容
✅ 数据库表结构设计和SQL文件创建  
✅ 后端3个AJAX接口开发  
✅ 后端场景保存逻辑优化  
✅ 前端界面完整重构  
✅ 动态参数表单渲染逻辑  
✅ 一键生成封面图前端交互  
✅ API调用框架代码  

### 待完成内容
⚠️ 数据库SQL手动执行  
⚠️ AI API具体实现  
⚠️ 参数定义数据录入  
⚠️ 完整功能测试  

### 预期效果
1. **用户体验提升**：分步骤引导，操作清晰流畅
2. **灵活性提升**：不同模型可配置不同参数，无需修改代码
3. **可扩展性强**：新增模型或参数仅需添加数据库配置
4. **自动化程度高**：一键生成封面图，减少人工操作

---

**报告生成时间**：2026-02-04  
**技术栈**：ThinkPHP 6.0.7 + Layui + MySQL  
**开发状态**：✅ 开发完成，⚠️ 待测试验证
