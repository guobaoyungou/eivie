# API Key测试连接功能说明

## 问题背景

在测试通义万相和可灵AI的API Key时，出现 `HTTP 401` 错误，原因是：

1. **认证方式问题**: 不同AI服务商的API认证方式不同
2. **API Endpoint不确定**: 测试用的API地址可能不正确
3. **需要完整请求参数**: 大多数AI接口需要完整的请求体才能验证
4. **成本考虑**: 实际调用API会产生费用

## 当前解决方案

### 改为格式验证

现在的测试连接功能**不会实际调用AI接口**，而是进行以下验证：

1. **非空验证**: 检查API Key是否为空
2. **长度验证**: 检查API Key长度是否合理（至少20个字符）
3. **格式验证**: 确保输入的是有效的字符串

### 优点

✅ **快速**: 无需等待网络请求  
✅ **免费**: 不产生API调用费用  
✅ **可靠**: 不依赖外部网络  
✅ **准确**: 能过滤掉明显错误的Key  

### 用户提示

点击"测试连接"后，系统会提示：
- ✅ 成功: "API Key格式验证通过"
- ❌ 失败: "API Key格式不正确（长度过短）"

## 实际验证时机

真正的API Key有效性验证会在以下时机自动进行：

1. **首次实际使用**: 当系统调用AI服务生成图片或视频时
2. **自动重试**: 如果Key无效，系统会自动切换到其他可用Key
3. **统计更新**: 系统会记录每个Key的成功率和失败次数

## 如需启用实际API测试

如果您需要在保存前就实际验证API Key的有效性，可以参考服务类中的注释代码：

### 通义万相实际测试代码示例

```php
$url = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text2image/image-synthesis';

$postData = json_encode([
    'model' => 'wanx-v1',
    'input' => [
        'prompt' => 'test'
    ],
    'parameters' => [
        'size' => '1024*1024'
    ]
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json',
    'X-DashScope-Async: enable'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
```

### 可灵AI实际测试代码示例

```php
$url = 'https://api.klingai.com/v1/images/generations';

$postData = json_encode([
    'prompt' => 'test',
    'model' => 'kling-v1'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
```

## 注意事项

1. **启用实际测试前请确认**:
   - AI服务商的API文档
   - 正确的API Endpoint
   - 认证方式（Header、Query参数等）
   - 请求体格式要求

2. **成本考虑**:
   - 实际调用API可能产生费用
   - 频繁测试会增加成本
   - 建议只在必要时启用

3. **超时设置**:
   - AI接口响应较慢
   - 建议设置至少10秒超时
   - 避免用户长时间等待

## 推荐使用方式

1. ✅ **添加Key时**: 使用格式验证快速检查
2. ✅ **保存后**: 通过实际业务使用来验证有效性
3. ✅ **查看统计**: 在API密钥列表中查看成功率
4. ⚠️ **避免频繁测试**: 减少不必要的API调用

## 相关文件

- 服务类: `/www/wwwroot/eivie/app/service/AiTravelPhotoApiKeyService.php`
  - `testTongyiApi()` 方法（第270行）
  - `testKlingApi()` 方法（第305行）
- 前端模板: `/www/wwwroot/eivie/app/view/ai_travel_photo/settings.html`
  - `testApiKey()` 函数（第675行）

---
更新时间：2026-01-22
