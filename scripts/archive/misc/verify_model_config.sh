#!/bin/bash
# AI模型配置管理功能验证脚本

echo "=========================================="
echo "AI模型配置管理功能验证"
echo "=========================================="
echo ""

BASE_URL="http://192.168.11.222"

echo "1. 验证参数定义管理页面访问..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/?s=/ModelConfig/parameters/model_id/1")
if [ "$STATUS" = "200" ]; then
    echo "   ✓ 参数定义管理页面访问正常 (HTTP $STATUS)"
else
    echo "   ✗ 参数定义管理页面访问失败 (HTTP $STATUS)"
fi

echo ""
echo "2. 验证响应定义管理页面访问..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/?s=/ModelConfig/responses/model_id/1")
if [ "$STATUS" = "200" ]; then
    echo "   ✓ 响应定义管理页面访问正常 (HTTP $STATUS)"
else
    echo "   ✗ 响应定义管理页面访问失败 (HTTP $STATUS)"
fi

echo ""
echo "3. 验证参数保存API..."
RESPONSE=$(curl -s -X POST "${BASE_URL}/?s=/ModelConfig/save_parameter" \
  -d "model_id=1&param_name=verify_test&param_label=验证测试&param_type=string&data_format=text&is_required=1&description=验证脚本测试&sort=100")
if echo "$RESPONSE" | grep -q '"success":true'; then
    echo "   ✓ 参数保存API正常"
    echo "   响应: $RESPONSE"
else
    echo "   ✗ 参数保存API异常"
    echo "   响应: $RESPONSE"
fi

echo ""
echo "4. 验证响应字段保存API..."
RESPONSE=$(curl -s -X POST "${BASE_URL}/?s=/ModelConfig/save_response" \
  -d "model_id=1&response_field=verify_test&field_label=验证测试&field_type=string&field_path=$.output.verify&is_critical=0&description=验证脚本测试")
if echo "$RESPONSE" | grep -q '"success":true'; then
    echo "   ✓ 响应字段保存API正常"
    echo "   响应: $RESPONSE"
else
    echo "   ✗ 响应字段保存API异常"
    echo "   响应: $RESPONSE"
fi

echo ""
echo "5. 验证模板数据传递..."
PARAM_TYPES=$(curl -s "${BASE_URL}/?s=/ModelConfig/parameters/model_id/1" | grep -o 'var paramTypes = {[^}]*}')
if [ -n "$PARAM_TYPES" ]; then
    echo "   ✓ 参数定义页面数据传递正常"
    echo "   $PARAM_TYPES"
else
    echo "   ✗ 参数定义页面数据传递异常"
fi

echo ""
FIELD_TYPES=$(curl -s "${BASE_URL}/?s=/ModelConfig/responses/model_id/1" | grep -o 'var fieldTypes = {[^}]*}')
if [ -n "$FIELD_TYPES" ]; then
    echo "   ✓ 响应定义页面数据传递正常"
    echo "   $FIELD_TYPES"
else
    echo "   ✗ 响应定义页面数据传递异常"
fi

echo ""
echo "=========================================="
echo "验证完成"
echo "=========================================="
echo ""
echo "提示："
echo "- 如果所有项都显示 ✓，说明功能正常"
echo "- 如果有 ✗，请查看错误信息并检查配置"
echo "- 在浏览器中测试时，请打开开发者工具查看Console日志"
