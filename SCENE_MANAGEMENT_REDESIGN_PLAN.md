# AI旅拍场景管理重构设计方案

## 一、需求背景

### 原有问题
- 场景管理与模型配置、API配置分离，数据关联不明确
- 场景新增时无法直观选择AI模型
- 模型参数填写不灵活，无法根据不同模型动态调整
- 缺少封面图一键生成功能

### 新需求
1. **新增场景时先选择AI模型**：下拉选择或搜索已配置的模型实例
2. **动态加载模型参数表单**：根据所选模型自动加载该模型要求的参数字段
3. **一键生成封面图**：填写完场景信息后，调用API生成场景封面

---

## 二、数据库表结构分析

### 核心关联表

#### 1. ddwx_ai_model_instance（AI模型实例表）
```sql
- id: 模型实例ID
- model_code: 模型唯一标识（flux-dev、sd-xl等）
- model_name: 模型显示名称
- provider: 服务提供商（aliyun、baidu等）
- api_endpoint: API端点地址
```

#### 2. ddwx_ai_model_parameter（模型参数定义表）
```sql
- id: 参数ID
- model_id: 关联模型实例ID
- param_name: 参数名称（如prompt、negative_prompt、steps）
- param_code: 参数代码（用于API传参）
- param_type: 参数类型（text/number/select/switch）
- default_value: 默认值
- is_required: 是否必填
- validation_rule: 验证规则
- sort: 排序
```

#### 3. ddwx_api_config（API配置表）
```sql
- id: API配置ID
- model_id: 关联模型实例ID
- api_key: API密钥
- api_secret: API Secret
- endpoint_url: API端点地址
- config_json: 其他配置参数
```

#### 4. ddwx_ai_travel_photo_scene（场景表）
```sql
-- 现有字段
- id, aid, bid, mdid
- name, category, cover, desc
- model_id: 关联模型实例ID
- status, is_public, is_recommend

-- 需要新增/调整的字段
- api_config_id: 关联API配置ID（新增）
- model_params: 模型参数JSON（已有，需要规范化使用）
```

---

## 三、数据流程设计

### 3.1 场景新增流程

```
步骤1：选择AI模型
  ↓
  - 下拉列表显示所有已启用的模型实例
  - 显示格式：模型名称 (提供商)
  - 数据来源：ddwx_ai_model_instance WHERE status=1
  
步骤2：加载模型参数表单
  ↓
  - AJAX请求获取模型参数定义
  - 接口：/AiTravelPhoto/get_model_params?model_id=xxx
  - 数据来源：ddwx_ai_model_parameter WHERE model_id=xxx ORDER BY sort ASC
  - 动态生成表单字段（根据param_type渲染不同输入框）
  
步骤3：填写场景基础信息
  ↓
  - 场景名称、分类、描述等
  - 门店选择、公共/私有开关
  
步骤4：填写模型参数
  ↓
  - 根据步骤2加载的参数字段填写
  - 正向提示词（prompt）
  - 反向提示词（negative_prompt）
  - 其他动态参数（steps、cfg_scale等）
  
步骤5：保存并生成封面图（可选）
  ↓
  - 点击"保存"：仅保存场景信息
  - 点击"保存并生成封面图"：保存后调用API生成封面
```

### 3.2 封面图生成流程

```
前端触发
  ↓
  - 提交表单时设置标记：generate_cover=1
  
后端处理
  ↓
  1. 保存场景信息到 ddwx_ai_travel_photo_scene
  2. 获取场景关联的 api_config_id
  3. 查询 ddwx_api_config 获取API配置
  4. 组装API请求参数（从 model_params JSON中提取）
  5. 调用API生成图片
  6. 保存生成记录到 ddwx_ai_travel_photo_generation
  7. 更新场景的 cover 字段
  8. 返回生成结果和封面图URL
```

---

## 四、技术实现方案

### 4.1 数据库修改

#### 修改 ddwx_ai_travel_photo_scene 表
```sql
-- 添加API配置关联字段
ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD COLUMN `api_config_id` int(11) DEFAULT '0' COMMENT '关联API配置ID' AFTER `model_id`;

-- 添加索引
ALTER TABLE `ddwx_ai_travel_photo_scene` 
ADD INDEX `idx_api_config_id` (`api_config_id`);

-- 确保 model_params 字段存在且为 TEXT 类型
-- （已存在则跳过）
ALTER TABLE `ddwx_ai_travel_photo_scene` 
MODIFY COLUMN `model_params` text COMMENT '模型参数JSON';
```

### 4.2 后端接口开发

#### 接口1：获取模型参数定义
```php
/**
 * 获取AI模型的参数定义
 * URL: /AiTravelPhoto/get_model_params
 * Method: GET
 * Params: model_id
 */
public function get_model_params()
{
    $modelId = input('model_id/d', 0);
    
    // 查询模型参数定义
    $params = Db::name('ai_model_parameter')
        ->where('model_id', $modelId)
        ->where('is_active', 1)
        ->order('sort ASC, id ASC')
        ->select();
    
    return json(['code' => 0, 'data' => $params]);
}
```

#### 接口2：获取模型关联的API配置
```php
/**
 * 获取模型关联的API配置列表
 * URL: /AiTravelPhoto/get_model_api_configs
 * Method: GET
 * Params: model_id
 */
public function get_model_api_configs()
{
    $modelId = input('model_id/d', 0);
    
    // 查询该模型的API配置
    $configs = Db::name('api_config')
        ->where('model_id', $modelId)
        ->where('is_active', 1)
        ->where(function($query) {
            // 权限筛选：自己的配置 或 公开的配置
            $query->where('bid', $this->bid)
                  ->whereOr('scope_type', 1);
        })
        ->field('id, api_name, provider, scope_type')
        ->select();
    
    return json(['code' => 0, 'data' => $configs]);
}
```

#### 接口3：一键生成封面图
```php
/**
 * 一键生成场景封面图
 * URL: /AiTravelPhoto/generate_scene_cover
 * Method: POST
 * Params: scene_id
 */
public function generate_scene_cover()
{
    $sceneId = input('scene_id/d', 0);
    
    // 1. 查询场景信息
    $scene = Db::name('ai_travel_photo_scene')
        ->where('id', $sceneId)
        ->where('aid', $this->aid)
        ->find();
    
    if (!$scene) {
        return json(['code' => 1, 'msg' => '场景不存在']);
    }
    
    // 2. 查询API配置
    $apiConfig = Db::name('api_config')
        ->where('id', $scene['api_config_id'])
        ->find();
    
    if (!$apiConfig || $apiConfig['is_active'] != 1) {
        return json(['code' => 1, 'msg' => 'API配置不可用']);
    }
    
    // 3. 解析模型参数
    $modelParams = json_decode($scene['model_params'], true);
    
    // 4. 调用AI API生成图片
    try {
        $result = $this->callAiApi($apiConfig, $modelParams);
        
        // 5. 保存生成记录
        $generationId = Db::name('ai_travel_photo_generation')->insertGetId([
            'aid' => $this->aid,
            'bid' => $this->bid,
            'scene_id' => $sceneId,
            'model_id' => $scene['model_id'],
            'api_config_id' => $scene['api_config_id'],
            'params' => json_encode($modelParams),
            'result_image' => $result['image_url'],
            'status' => 1,
            'create_time' => time()
        ]);
        
        // 6. 更新场景封面
        Db::name('ai_travel_photo_scene')
            ->where('id', $sceneId)
            ->update([
                'cover' => $result['image_url'],
                'update_time' => time()
            ]);
        
        return json([
            'code' => 0,
            'msg' => '生成成功',
            'data' => [
                'image_url' => $result['image_url'],
                'generation_id' => $generationId
            ]
        ]);
        
    } catch (\Exception $e) {
        return json(['code' => 1, 'msg' => '生成失败：' . $e->getMessage()]);
    }
}

/**
 * 调用AI API（通用方法）
 */
private function callAiApi($apiConfig, $params)
{
    // 根据不同提供商调用对应API
    $provider = $apiConfig['provider'];
    
    switch ($provider) {
        case 'aliyun':
            return $this->callAliyunApi($apiConfig, $params);
        case 'baidu':
            return $this->callBaiduApi($apiConfig, $params);
        default:
            throw new \Exception('不支持的服务提供商');
    }
}
```

### 4.3 前端界面改造

#### 场景编辑页面结构
```html
<!-- 第一步：选择AI模型 -->
<div class="layui-form-item">
    <label class="layui-form-label">选择模型</label>
    <div class="layui-input-block">
        <select name="model_id" lay-filter="model_id" lay-verify="required">
            <option value="">请选择AI模型</option>
            {volist name="models" id="model"}
            <option value="{$model.id}" {$info.model_id==$model.id ? 'selected' : ''}>
                {$model.model_name} ({$model.provider})
            </option>
            {/volist}
        </select>
    </div>
</div>

<!-- 第二步：选择API配置 -->
<div class="layui-form-item" id="api_config_wrap" style="display:none;">
    <label class="layui-form-label">API配置</label>
    <div class="layui-input-block">
        <select name="api_config_id" lay-verify="required">
            <option value="">请选择API配置</option>
        </select>
    </div>
</div>

<!-- 第三步：场景基础信息 -->
<div class="layui-form-item">
    <label class="layui-form-label">场景名称</label>
    <div class="layui-input-block">
        <input type="text" name="name" value="{$info.name}" required lay-verify="required" class="layui-input">
    </div>
</div>

<!-- 第四步：动态参数表单区域 -->
<div id="model_params_container">
    <!-- 动态加载的参数字段将插入此处 -->
</div>

<!-- 保存按钮组 -->
<div class="layui-form-item">
    <div class="layui-input-block">
        <button class="layui-btn" lay-submit lay-filter="saveForm">保存</button>
        <button type="button" class="layui-btn layui-btn-normal" id="saveAndGenerate">保存并生成封面图</button>
    </div>
</div>
```

#### 前端JavaScript逻辑
```javascript
// 监听模型选择变化
form.on('select(model_id)', function(data){
    var modelId = data.value;
    
    if (modelId) {
        // 1. 加载API配置列表
        loadApiConfigs(modelId);
        
        // 2. 加载模型参数表单
        loadModelParams(modelId);
    } else {
        $('#api_config_wrap').hide();
        $('#model_params_container').html('');
    }
});

// 加载API配置列表
function loadApiConfigs(modelId) {
    $.get('/AiTravelPhoto/get_model_api_configs', {model_id: modelId}, function(res){
        if (res.code == 0) {
            var html = '<option value="">请选择API配置</option>';
            $.each(res.data, function(i, item){
                html += '<option value="'+ item.id +'">'+ item.api_name +' ('+ item.provider +')</option>';
            });
            $('select[name="api_config_id"]').html(html);
            form.render('select');
            $('#api_config_wrap').show();
        }
    });
}

// 加载模型参数表单
function loadModelParams(modelId) {
    $.get('/AiTravelPhoto/get_model_params', {model_id: modelId}, function(res){
        if (res.code == 0) {
            var html = '';
            $.each(res.data, function(i, param){
                html += renderParamField(param);
            });
            $('#model_params_container').html(html);
            form.render();
        }
    });
}

// 渲染参数字段
function renderParamField(param) {
    var html = '<div class="layui-form-item">';
    html += '<label class="layui-form-label">'+ param.param_name +'</label>';
    html += '<div class="layui-input-block">';
    
    switch(param.param_type) {
        case 'text':
            html += '<input type="text" name="param_'+ param.param_code +'" value="'+ (param.default_value || '') +'" class="layui-input">';
            break;
        case 'textarea':
            html += '<textarea name="param_'+ param.param_code +'" class="layui-textarea">'+ (param.default_value || '') +'</textarea>';
            break;
        case 'number':
            html += '<input type="number" name="param_'+ param.param_code +'" value="'+ (param.default_value || '') +'" class="layui-input">';
            break;
        case 'select':
            var options = JSON.parse(param.options_json || '[]');
            html += '<select name="param_'+ param.param_code +'">';
            $.each(options, function(j, opt){
                html += '<option value="'+ opt.value +'">'+ opt.label +'</option>';
            });
            html += '</select>';
            break;
    }
    
    html += '</div></div>';
    return html;
}

// 保存并生成封面图
$('#saveAndGenerate').click(function(){
    var formData = $('#editForm').serialize() + '&generate_cover=1';
    
    layer.load(1);
    $.post('/AiTravelPhoto/scene_edit', formData, function(res){
        layer.closeAll('loading');
        if (res.status == 1) {
            layer.msg('保存成功，正在生成封面图...', {icon: 16, time: 2000});
            
            // 调用生成封面图接口
            if (res.scene_id) {
                generateCover(res.scene_id);
            }
        } else {
            layer.msg(res.msg, {icon: 2});
        }
    });
});

// 生成封面图
function generateCover(sceneId) {
    layer.load(1);
    $.post('/AiTravelPhoto/generate_scene_cover', {scene_id: sceneId}, function(res){
        layer.closeAll('loading');
        if (res.code == 0) {
            layer.msg('封面图生成成功', {icon: 1});
            // 刷新页面或更新封面图显示
            location.reload();
        } else {
            layer.msg(res.msg, {icon: 2});
        }
    });
}
```

---

## 五、字段映射规范

### model_params JSON格式
```json
{
  "prompt": "海边度假风景，蓝天白云...",
  "negative_prompt": "低质量，模糊...",
  "steps": 50,
  "cfg_scale": 7.5,
  "width": 1024,
  "height": 1024,
  "seed": -1
}
```

### 保存逻辑
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

---

## 六、实施步骤

### Phase 1：数据库改造（15分钟）
- [x] 分析现有表结构
- [ ] 添加 api_config_id 字段和索引
- [ ] 验证表结构完整性

### Phase 2：后端接口开发（30分钟）
- [ ] 开发 get_model_params 接口
- [ ] 开发 get_model_api_configs 接口
- [ ] 改造 scene_edit 保存逻辑（支持 model_params）
- [ ] 开发 generate_scene_cover 接口

### Phase 3：前端界面改造（45分钟）
- [ ] 重构场景编辑表单HTML结构
- [ ] 实现模型选择联动逻辑
- [ ] 实现动态参数表单渲染
- [ ] 实现保存并生成封面图功能

### Phase 4：测试验证（30分钟）
- [ ] 测试模型选择和参数加载
- [ ] 测试场景保存和参数存储
- [ ] 测试封面图生成功能
- [ ] 测试权限隔离（门店/公共）

---

## 七、注意事项

1. **向后兼容**：保留现有场景数据，逐步迁移到新结构
2. **权限控制**：API配置需要检查 scope_type 权限
3. **错误处理**：API调用失败时记录日志，不影响场景保存
4. **性能优化**：参数定义表建议添加缓存
5. **安全性**：API密钥需要加密存储和传输

---

## 八、后续优化方向

1. **批量生成**：支持批量为多个场景生成封面图
2. **参数模板**：支持保存常用参数组合为模板
3. **AI推荐**：根据场景描述自动推荐合适的模型和参数
4. **效果预览**：生成前提供效果预览功能
