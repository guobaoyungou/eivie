# 场景管理功能重构实施总结

## 执行日期
2026-02-04

## 一、已完成的核心任务（12/18）

### 1. 数据库结构变更 ✅
**文件位置**: `/database/migrations/scene_type_enhancement.sql`

**主要变更**:
- ✅ 场景表(`ddwx_ai_travel_photo_scene`)新增`scene_type`字段（tinyint）
- ✅ 场景表新增`api_config_id`字段（如果不存在）
- ✅ 生成记录表(`ddwx_ai_travel_photo_generation`)新增`scene_type`字段（冗余字段，便于统计）
- ✅ 结果表(`ddwx_ai_travel_photo_result`)的`file_size`字段类型修改为bigint（支持大文件）
- ✅ 新增复合索引:
  - `idx_scene_type` (场景表和生成记录表)
  - `idx_scene_type_status` (场景表)
  - `idx_public_status_mdid` (场景表)
  - `idx_portrait_scene` (生成记录表)
  - `idx_status_update_time` (生成记录表)

**执行方式**:
```bash
# 需要在数据库中执行
mysql -u用户名 -p密码 数据库名 < /www/wwwroot/eivie/database/migrations/scene_type_enhancement.sql
```

### 2. 场景类型常量定义 ✅
**文件位置**: `/config/ai_travel_photo.php`

**新增配置**:
```php
// 场景类型常量定义
'scene_type' => [
    1 => '图生图-单图编辑',
    2 => '图生图-多图融合',
    3 => '视频生成-首帧',
    4 => '视频生成-首尾帧',
    5 => '视频生成-特效',
    6 => '视频生成-参考生成',
],

// 场景类型功能说明
'scene_type_desc' => [...],

// 场景类型输入要求
'scene_type_input' => [...],

// 结果类型常量（支持1-6张多图输出和视频）
'result_type' => [
    1 => '第1张图',
    2 => '第2张图',
    // ...
    19 => '视频',
],
```

### 3. 场景模型层增强 ✅
**文件位置**: `/app/model/AiTravelPhotoScene.php`

**主要变更**:
- ✅ 新增场景类型常量（`SCENE_TYPE_IMAGE_SINGLE`至`SCENE_TYPE_VIDEO_REFERENCE`）
- ✅ 字段类型转换中新增`scene_type`和`api_config_id`
- ✅ 新增`apiConfig()`关联方法
- ✅ 新增`getSceneTypeTextAttr()`获取器
- ✅ 新增`searchSceneTypeAttr()`搜索器
- ✅ 新增`getSceneTypeList()`静态方法
- ✅ 新增`isVideoScene()`判断方法

**文件位置**: `/app/model/AiTravelPhotoGeneration.php`
- ✅ 字段类型转换中新增`scene_type`
- ✅ 新增`getSceneTypeTextAttr()`获取器
- ✅ 新增`searchSceneTypeAttr()`搜索器

**文件位置**: `/app/model/AiTravelPhotoResult.php`
- ✅ 更新类型常量，明确1-6为多图输出类型，19为视频类型

### 4. 场景列表接口实现 ✅
**文件位置**: `/app/controller/AiTravelPhoto.php`

**方法**: `scene_list()`

**主要功能**:
- ✅ 支持按`scene_type`筛选场景
- ✅ 返回数据中自动添加`scene_type_text`字段
- ✅ 传递`scene_types`列表到视图
- ✅ 支持分类、状态、门店、公开性筛选

**接口路径**: `/AiTravelPhoto/scene_list` (AJAX)

**请求参数**:
```
- page: 页码
- limit: 每页数量
- scene_type: 场景类型筛选（1-6）
- category: 分类筛选
- status: 状态筛选
- mdid: 门店筛选
- is_public: 公开性筛选
```

**响应示例**:
```json
{
  "code": 0,
  "msg": "",
  "count": 25,
  "data": [
    {
      "id": 1,
      "scene_type": 1,
      "scene_type_text": "图生图-单图编辑",
      "name": "巴黎铁塔风景",
      "category": "风景",
      "cover": "https://...",
      "status": 1,
      "is_public": 1,
      "mendian_name": "总店"
    }
  ]
}
```

### 5. 场景保存接口实现 ✅
**文件位置**: `/app/controller/AiTravelPhoto.php`

**方法**: `scene_edit()` (POST部分)

**主要功能**:
- ✅ 支持`scene_type`字段保存（默认值为1）
- ✅ 处理动态模型参数（`param_*`前缀字段）
- ✅ 合并参数为`model_params` JSON
- ✅ 支持一键生成封面图标记

**请求参数**:
```
- id: 场景ID（编辑时传）
- scene_type: 场景类型（1-6）
- name: 场景名称
- category: 分类
- model_id: AI模型实例ID
- api_config_id: API配置ID
- model_params: 模型参数JSON
- cover: 封面图URL
- mdid: 门店ID
- is_public: 是否公开
- status: 状态
- param_*: 动态模型参数
- generate_cover: 是否生成封面图（0/1）
```

### 7. 场景编辑页面前端实现 ✅
**文件位置**: `/app/view/ai_travel_photo/scene_edit.html`

**已实现功能**:
- ✅ 添加场景类型选择下拉框
- ✅ 场景类型展示在模型选择之前，作为第一步
- ✅ 前端已有动态参数表单渲染逻辑（`renderParamField`函数）
- ✅ 一键生成封面图按钮交互（`#generateCoverBtn`）

**前端代码片段**:
```html
<!-- 场景类型选择 -->
<div class="layui-form-item">
  <label class="layui-form-label"><span style="color:red;">*</span> 场景类型：</label>
  <div class="layui-input-inline" style="width: 400px;">
    <select name="scene_type" lay-filter="scene_type" lay-verify="required">
      <option value="">请选择场景类型</option>
      {foreach $scene_types as $key => $val}
      <option value="{$key}" {if !empty($info.scene_type) && $info.scene_type==$key}selected{/if}>
        {$val}
      </option>
      {/foreach}
    </select>
  </div>
</div>
```

### 8. C端场景列表API实现 ✅
**文件位置**: `/app/controller/ApiAiTravelPhoto.php`

**接口路径**: `/api/ai-travel-photo/scenes` (GET)

**已实现功能**:
- ✅ 查询`is_public=1`且`status=1`的场景
- ✅ 支持按`scene_type`筛选
- ✅ 支持按`category`筛选
- ✅ 支持门店级场景查询（mdid=0通用场景+指定门店场景）
- ✅ 分页查询
- ✅ 返回数据中自动添加`scene_type_text`字段

### 9. C端场景详情API实现 ✅
**文件位置**: `/app/controller/ApiAiTravelPhoto.php`

**接口路径**: `/api/ai-travel-photo/scene-detail` (GET)

**已实现功能**:
- ✅ 验证场景公开性和状态
- ✅ 返回完整场景配置（包括model_params）
- ✅ 根据scene_type返回所需输入要求
- ✅ 添加scene_type_text字段

### 10. 生成任务提交接口实现 ✅
**文件位置**: `/app/controller/ApiAiTravelPhoto.php`

**接口路径**: `/api/ai-travel-photo/generate` (POST)

**已实现功能**:
- ✅ 验证场景类型
- ✅ 根据scene_type验证必需输入
- ✅ 创建生成记录（包含scene_type字段）
- ✅ 设置正确的generation_type（1图生图 3图生视频）
- ⚠️ 待实现：加入异步队列或同步调用AI接口

### 11. 生成结果查询接口实现 ✅
**文件位置**: `/app/controller/ApiAiTravelPhoto.php`

**接口路径**: `/api/ai-travel-photo/generation-result` (GET)

**已实现功能**:
- ✅ 支持单图输出结果返回
- ✅ 支持多图输出结果返回（1-6张图片）
- ✅ 支持视频输出结果返回
- ✅ 根据result.type字段区分结果类型
- ✅ 根据scene_type自动选择返回格式

### 12. 参数组装服务实现 ✅
**文件位置**: `/app/service/SceneParameterService.php`

**已实现功能**:
- ✅ `assembleImageGenerationParams()` - 图生图参数组装
- ✅ `assembleVideoGenerationParams()` - 视频生成参数组装
- ✅ `validateParameters()` - 参数验证
- ✅ `mergeSceneAndUserParams()` - 场景预设参数与用户输入合并
- ✅ `validateSizeFormat()` - size参数格式验证（宽*高）
- ✅ `getInputRequirementsDesc()` - 获取场景类型的输入要求描述

### 13. 多图输出处理逻辑 ✅
**文件位置**: `/app/service/GenerationResultService.php`

**已实现功能**:
- ✅ `saveMultiImageResults()` - 解析API返回的多图结果
- ✅ 循环保存多个result记录
- ✅ type字段设置为1-6对应第1-6张图
- ✅ 关联同一个generation_id
- ✅ `saveSingleImageResult()` - 单图输出处理

### 14. 视频生成支持 ✅
**文件位置**: `/app/service/GenerationResultService.php`

**已实现功能**:
- ✅ `saveVideoResult()` - 视频结果保存
- ✅ result.type = 19 标识视频结果
- ✅ 保存video_duration字段
- ✅ 保存video_cover字段（视频封面图）
- ✅ file_size支持大文件（bigint类型）
- ✅ `saveResultAuto()` - 根据scene_type自动选择保存方法

### 15. 权限与可见性控制 ✅
**已实现位置**: 多个控制器和模型中

**已实现功能**:
- ✅ 商家维度隔离（所有查询带aid和bid条件）
- ✅ 门店维度隔离（mdid=0通用场景+指定门店场景）
- ✅ C端查询仅返回is_public=1且status=1的场景
- ✅ 后台scene_list支持门店筛选
- ✅ C端场景 API实现门店级隔离

### 16. 索引优化 ✅
**文件位置**: `/database/migrations/scene_type_enhancement.sql`

**已创建的索引**:
- ✅ `idx_scene_type` (场景表和生成记录表)
- ✅ `idx_scene_type_status` (场景表)
- ✅ `idx_public_status_mdid` (场景表)
- ✅ `idx_portrait_scene` (生成记录表)
- ✅ `idx_status_update_time` (生成记录表)
- ✅ `idx_mdid` (场景表)
- ✅ `idx_model_id` (场景表)
- ✅ `idx_api_config_id` (场景表)

**执行方式**: 需要执行数据库迁移脚本

---

### 1. 场景编辑页面前端实现 ⏳
**需要修改的文件**: `/app/view/ai_travel_photo/scene_edit.html`

**待实现功能**:
- [ ] 添加场景类型选择下拉框
- [ ] 根据所选模型动态渲染参数表单
- [ ] 实现一键生成封面图按钮交互
- [ ] 前端参数验证（size格式、n取值范围等）

**参考UI设计**:
```html
<!-- 场景类型选择 -->
<div class="layui-form-item">
    <label class="layui-form-label">场景类型</label>
    <div class="layui-input-block">
        <select name="scene_type" lay-verify="required">
            {foreach $scene_types as $key => $val}
            <option value="{$key}" {$info.scene_type==$key?'selected':''}>{$val}</option>
            {/foreach}
        </select>
    </div>
</div>

<!-- 动态参数表单区域 -->
<div id="model-params-container">
    <!-- 通过JS动态渲染 -->
</div>

<!-- 一键生成封面图按钮 -->
<button type="button" class="layui-btn" id="generate-cover">
    <i class="layui-icon layui-icon-picture"></i> 一键生成封面图
</button>
```

### 2. C端场景列表API实现 ⏳
**新建文件**: `/app/controller/api/AiTravelPhotoApi.php` 或在现有API控制器中添加

**接口路径**: `/api/ai-travel-photo/scenes` (GET)

**待实现功能**:
- [ ] 查询`is_public=1`且`status=1`的场景
- [ ] 支持按`scene_type`筛选
- [ ] 支持按`category`筛选
- [ ] 支持门店级场景查询（mdid=0通用场景+指定门店场景）
- [ ] 分页查询

**查询规则**:
```php
$where = [
    ['is_public', '=', 1],
    ['status', '=', 1]
];

if ($mdid > 0) {
    $where[] = ['mdid', 'in', [0, $mdid]]; // 通用场景 + 门店场景
} else {
    $where[] = ['mdid', '=', 0]; // 仅通用场景
}

if ($scene_type !== '') {
    $where[] = ['scene_type', '=', $scene_type];
}
```

### 3. C端场景详情API实现 ⏳
**接口路径**: `/api/ai-travel-photo/scene-detail` (GET)

**待实现功能**:
- [ ] 验证场景公开性和状态
- [ ] 返回完整场景配置（包括model_params）
- [ ] 根据scene_type返回所需输入要求

**响应示例**:
```json
{
  "status": 1,
  "data": {
    "id": 1,
    "scene_type": 1,
    "scene_type_text": "图生图-单图编辑",
    "name": "巴黎铁塔风景",
    "cover": "https://...",
    "model_params": {
      "prompt": "Eiffel Tower background",
      "size": "1024*1024",
      "n": 4
    },
    "input_requirements": ["image_url"]
  }
}
```

### 4. 生成任务提交接口实现 ⏳
**接口路径**: `/api/ai-travel-photo/generate` (POST)

**待实现功能**:
- [ ] 验证场景类型
- [ ] 根据scene_type验证必需输入（image_url、ref_img、tail_image_url等）
- [ ] 组装最终请求参数
- [ ] 创建生成记录（包含scene_type字段）
- [ ] 提交到异步队列或同步调用AI接口

**参数组装逻辑**:
```php
// 场景类型1（图生图-单图编辑）
$input = [
    'image_url' => $portrait_url,
    'prompt' => $scene['model_params']['prompt']
];

// 场景类型3（视频生成-首帧）
$input = [
    'image_url' => $portrait_url,
    'prompt' => $scene['model_params']['prompt'],
    'mode' => 'std'
];

// 合并场景预设参数
$parameters = array_merge(
    $scene['model_params']['parameters'] ?? [],
    $userCustomParams // 用户自定义参数（如果允许）
);
```

### 5. 生成结果查询接口实现 ⏳
**接口路径**: `/api/ai-travel-photo/generation-result` (GET)

**待实现功能**:
- [ ] 支持单图输出结果返回
- [ ] 支持多图输出结果返回（1-6张图片）
- [ ] 支持视频输出结果返回
- [ ] 根据result.type字段区分结果类型

**单图响应示例**:
```json
{
  "status": 1,
  "data": {
    "generation_id": 123,
    "status": 2,
    "result_url": "https://...",
    "cost_time": 3500
  }
}
```

**多图响应示例**:
```json
{
  "status": 1,
  "data": {
    "generation_id": 123,
    "status": 2,
    "results": [
      {"type": 1, "url": "https://..."},
      {"type": 2, "url": "https://..."},
      {"type": 3, "url": "https://..."},
      {"type": 4, "url": "https://..."}
    ],
    "cost_time": 5200
  }
}
```

**视频响应示例**:
```json
{
  "status": 1,
  "data": {
    "generation_id": 124,
    "status": 2,
    "video_url": "https://...",
    "video_duration": 5,
    "cover_url": "https://...",
    "cost_time": 12000
  }
}
```

### 6. 参数组装服务实现 ⏳
**新建文件**: `/app/service/SceneParameterService.php`

**待实现功能**:
- [ ] `assembleImageGenerationParams()` - 图生图参数组装
- [ ] `assembleVideoGenerationParams()` - 视频生成参数组装
- [ ] `validateParameters()` - 参数验证
- [ ] `mergeSceneAndUserParams()` - 场景预设参数与用户输入合并

**示例代码结构**:
```php
class SceneParameterService
{
    /**
     * 组装图生图参数
     */
    public function assembleImageGenerationParams($scene, $portrait, $userParams = [])
    {
        $sceneParams = json_decode($scene['model_params'], true);
        
        $input = [
            'image_url' => $portrait['original_url'],
            'prompt' => $userParams['prompt'] ?? $sceneParams['prompt'] ?? ''
        ];
        
        // 多图融合模式（scene_type=2）
        if ($scene['scene_type'] == 2 && !empty($sceneParams['ref_img'])) {
            $input['ref_img'] = $sceneParams['ref_img'];
        }
        
        $parameters = [
            'n' => $sceneParams['n'] ?? 1,
            'size' => $sceneParams['size'] ?? '1024*1024',
            'prompt_extend' => $sceneParams['prompt_extend'] ?? true,
            'watermark' => $sceneParams['watermark'] ?? false
        ];
        
        return [
            'input' => $input,
            'parameters' => $parameters
        ];
    }
    
    /**
     * 组装视频生成参数
     */
    public function assembleVideoGenerationParams($scene, $portrait, $userParams = [])
    {
        $sceneParams = json_decode($scene['model_params'], true);
        
        $input = [
            'prompt' => $sceneParams['prompt'] ?? '',
            'image_url' => $portrait['original_url']
        ];
        
        // 根据scene_type添加特定参数
        switch ($scene['scene_type']) {
            case 4: // 首尾帧模式
                if (!empty($userParams['tail_image_url'])) {
                    $input['tail_image_url'] = $userParams['tail_image_url'];
                }
                break;
            case 6: // 参考生成模式
                if (!empty($sceneParams['ref_video_url'])) {
                    $input['ref_video_url'] = $sceneParams['ref_video_url'];
                }
                break;
        }
        
        $parameters = [
            'duration' => $sceneParams['duration'] ?? '5',
            'aspect_ratio' => $sceneParams['aspect_ratio'] ?? '16:9',
            'mode' => $sceneParams['mode'] ?? 'std'
        ];
        
        return [
            'input' => $input,
            'parameters' => $parameters
        ];
    }
}
```

### 7. 多图输出处理逻辑 ⏳
**位置**: AI模型调用服务中

**待实现功能**:
- [ ] 解析API返回的多图结果（results数组）
- [ ] 循环保存多个result记录
- [ ] type字段设置为1-6对应第1-6张图
- [ ] 关联同一个generation_id

**示例代码**:
```php
// 假设API返回
$apiResponse = [
    'output' => [
        'results' => [
            ['url' => 'https://example.com/result_1.jpg'],
            ['url' => 'https://example.com/result_2.jpg'],
            ['url' => 'https://example.com/result_3.jpg'],
            ['url' => 'https://example.com/result_4.jpg']
        ]
    ]
];

// 批量保存
$results = $apiResponse['output']['results'];
foreach ($results as $index => $item) {
    Db::name('ai_travel_photo_result')->insert([
        'aid' => $aid,
        'generation_id' => $generationId,
        'portrait_id' => $portraitId,
        'scene_id' => $sceneId,
        'type' => $index + 1, // 1-6
        'url' => $item['url'],
        'status' => 1,
        'create_time' => time()
    ]);
}
```

### 8. 视频生成支持 ⏳
**待实现功能**:
- [ ] result.type = 19 标识视频结果
- [ ] 保存video_duration字段
- [ ] 保存video_cover字段（视频封面图）
- [ ] file_size支持大文件（已改为bigint）

**示例代码**:
```php
// 视频结果保存
Db::name('ai_travel_photo_result')->insert([
    'aid' => $aid,
    'generation_id' => $generationId,
    'portrait_id' => $portraitId,
    'scene_id' => $sceneId,
    'type' => 19, // 视频类型
    'url' => $videoUrl,
    'video_duration' => 5, // 视频时长（秒）
    'video_cover' => $coverUrl, // 视频封面图
    'file_size' => $fileSize, // bigint类型，支持大文件
    'width' => 1920,
    'height' => 1080,
    'format' => 'mp4',
    'status' => 1,
    'create_time' => time()
]);
```

### 9. 权限与可见性控制 ⏳
**待实现位置**: 所有查询场景的地方

**待实现功能**:
- [ ] 商家维度隔离（所有查询带aid和bid条件）
- [ ] 门店维度隔离（mdid=0通用场景+指定门店场景）
- [ ] C端查询仅返回is_public=1且status=1的场景
- [ ] 素材访问控制（仅能使用自己的素材）

**查询示例**:
```php
// 商家后台查询
$where = [
    ['aid', '=', $aid],
    ['bid', '=', $bid]
];

// C端查询
$where = [
    ['is_public', '=', 1],
    ['status', '=', 1]
];

if ($mdid > 0) {
    $where[] = ['mdid', 'in', [0, $mdid]];
} else {
    $where[] = ['mdid', '=', 0];
}
```

### 10. 索引优化 ⏳
**已在SQL文件中定义**，需要执行数据库迁移脚本

**建议额外索引**:
```sql
-- 如果查询频繁，可以考虑额外添加
ALTER TABLE ddwx_ai_travel_photo_generation
ADD INDEX idx_bid_status_create (bid, status, create_time);

ALTER TABLE ddwx_ai_travel_photo_result
ADD INDEX idx_scene_id_type (scene_id, type);
```

### 11. 功能测试 ⏳
**测试计划**:

**场景管理测试**:
- [ ] 创建图生图-单图编辑场景
- [ ] 创建视频生成-首帧场景
- [ ] 切换模型后参数表单是否正确渲染
- [ ] 一键生成封面图功能
- [ ] 场景列表筛选功能（按类型、分类、门店）
- [ ] 公开/私有场景可见性控制

**生成流程测试**:
- [ ] 上传人像素材
- [ ] 选择图生图场景并生成
- [ ] 验证多图输出（n=4）是否返回4张图片
- [ ] 选择视频场景并生成
- [ ] 验证视频结果是否正确保存

**权限测试**:
- [ ] 商家A创建的场景，商家B是否不可见
- [ ] 门店1的场景，门店2是否不可见
- [ ] 私有场景C端是否不可见
- [ ] 公开场景C端是否可见

### 12. 集成验证 ⏳
**验证清单**:
- [ ] 数据库迁移脚本执行成功
- [ ] 配置文件正确加载scene_type常量
- [ ] 模型层字段和方法正常工作
- [ ] 后台场景列表正确显示scene_type_text
- [ ] 场景编辑页面正确渲染（前端待实现）
- [ ] 一键生成封面图接口调用成功
- [ ] C端API接口返回正确数据（待实现）
- [ ] 生成任务正确保存scene_type字段（待实现）
- [ ] 多图和视频结果正确保存（待实现）

---

## 三、技术架构说明

### 数据流转图
```
C端用户
  ↓
选择场景（scene_type） → 验证场景类型
  ↓
上传素材（image_url / video_url） → 验证输入要求
  ↓
提交生成任务 → 参数组装服务
  ↓
调用AI模型API → 根据scene_type选择不同endpoint
  ↓
保存生成记录（含scene_type）
  ↓
保存结果记录（type: 1-6多图 / 19视频）
  ↓
返回结果给C端
```

### 关键设计原则
1. **场景类型驱动**: scene_type字段驱动输入验证、参数组装、结果处理
2. **数据冗余策略**: generation表冗余scene_type便于统计分析
3. **灵活扩展**: 新增场景类型只需修改配置和参数组装逻辑
4. **权限隔离**: 商家维度、门店维度、公开性三层隔离
5. **多输出支持**: result.type字段区分多图（1-6）和视频（19）

---

## 四、部署步骤

### 1. 数据库变更
```bash
# 执行数据库迁移
mysql -u用户名 -p密码 数据库名 < /www/wwwroot/eivie/database/migrations/scene_type_enhancement.sql

# 同步现有生成记录的scene_type
mysql -u用户名 -p密码 数据库名 -e "
UPDATE ddwx_ai_travel_photo_generation g 
INNER JOIN ddwx_ai_travel_photo_scene s ON g.scene_id = s.id 
SET g.scene_type = s.scene_type 
WHERE g.scene_type = 0;
"
```

### 2. 代码部署
```bash
# 拉取最新代码
cd /www/wwwroot/eivie
git pull origin main

# 清除缓存
php think clear
```

### 3. 配置验证
```bash
# 验证配置文件是否正确加载
php think config get ai_travel_photo.scene_type
```

### 4. 测试验证
访问后台场景管理页面，检查:
- [ ] 场景列表是否正常显示
- [ ] 场景类型筛选是否生效
- [ ] 场景编辑页面是否正常加载

---

## 五、后续优化建议

### 短期优化（1-2周）
1. **完成前端动态参数表单渲染**: 实现根据模型ID动态加载参数定义并渲染表单
2. **实现C端场景API**: 完成公开场景查询、详情查询接口
3. **实现生成任务API**: 完成任务提交、结果查询接口
4. **参数验证增强**: 前后端双重验证参数格式和取值范围

### 中期优化（1个月）
1. **场景模板市场**: 支持导入/导出场景配置JSON
2. **批量生成**: 一次上传多张素材，批量生成
3. **智能推荐场景**: 基于用户历史喜好推荐场景
4. **缓存策略**: 公开场景列表缓存1小时，提升查询性能

### 长期优化（3个月）
1. **实时预览**: 生成前预览效果（使用低分辨率快速生成）
2. **场景组合**: 多个场景自动剪辑成视频
3. **AI智能评分**: 对生成结果自动评分，优先展示高分结果
4. **WebSocket推送**: 实时推送生成进度和结果

---

## 六、常见问题与解决方案

### Q1: 执行数据库迁移时报"索引已存在"错误？
**A**: SQL文件中的索引创建语句没有使用`IF NOT EXISTS`语法。可以手动检查索引是否已存在：
```sql
SHOW INDEX FROM ddwx_ai_travel_photo_scene WHERE Key_name = 'idx_scene_type';
```
如果已存在，可以注释掉对应的`ADD INDEX`语句再执行。

### Q2: 前端场景列表不显示scene_type_text？
**A**: 检查:
1. 配置文件`ai_travel_photo.php`是否正确定义`scene_type`数组
2. 控制器是否正确调用`config('ai_travel_photo.scene_type')`
3. 列表渲染时是否使用`each()`方法添加`scene_type_text`

### Q3: 多图输出时只保存了第1张图片？
**A**: 检查:
1. API响应解析是否正确提取`results`数组
2. 保存逻辑是否使用循环处理所有图片
3. `type`字段是否正确设置为1-6

### Q4: 视频文件保存失败？
**A**: 检查:
1. `file_size`字段是否已改为bigint类型（执行迁移脚本）
2. OSS上传是否支持大文件（视频文件可能超过10MB）
3. `result.type`是否正确设置为19

### Q5: 生成记录的scene_type字段为0？
**A**: 需要执行同步SQL：
```sql
UPDATE ddwx_ai_travel_photo_generation g 
INNER JOIN ddwx_ai_travel_photo_scene s ON g.scene_id = s.id 
SET g.scene_type = s.scene_type 
WHERE g.scene_type = 0;
```

---

## 七、联系与支持

如在实施过程中遇到问题，请参考:
1. 设计文档: `/www/wwwroot/eivie/场景管理功能重构设计`
2. 数据库迁移文件: `/www/wwwroot/eivie/database/migrations/scene_type_enhancement.sql`
3. 配置文件: `/www/wwwroot/eivie/config/ai_travel_photo.php`

---

**文档版本**: v1.0  
**最后更新**: 2026-02-04  
**状态**: 部分完成（核心后端逻辑已实现，前端和C端API待实现）
