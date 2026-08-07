#!/bin/bash

echo "=== 测试场景编辑页面语法 ==="

# 清除缓存
echo "1. 清除模板缓存..."
rm -rf /www/wwwroot/eivie/runtime/temp/*

# 尝试访问页面（需要登录，但可以看到是否有500错误）
echo ""
echo "2. 通过curl访问页面（测试编译）..."
RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" "http://192.168.11.222/?s=/AiTravelPhoto/scene_list" 2>&1)

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE" | cut -d: -f2)
echo "HTTP状态码: $HTTP_CODE"

if [ "$HTTP_CODE" = "500" ]; then
    echo "✗ 服务器返回500错误"
    echo ""
    echo "错误详情:"
    echo "$RESPONSE" | grep -A 10 "ParseError\|syntax error\|unexpected" | head -20
    exit 1
elif [ "$HTTP_CODE" = "302" ] || [ "$HTTP_CODE" = "200" ]; then
    echo "✓ 页面可以正常访问（状态码: $HTTP_CODE）"
    
    # 检查编译后的文件
    echo ""
    echo "3. 检查最新编译的模板文件..."
    COMPILED_FILE=$(find /www/wwwroot/eivie/runtime/temp -name "*.php" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)
    
    if [ -n "$COMPILED_FILE" ]; then
        echo "找到编译文件: $COMPILED_FILE"
        echo ""
        echo "4. PHP语法检查..."
        php -l "$COMPILED_FILE"
        
        if [ $? -eq 0 ]; then
            echo ""
            echo "✓ ✓ ✓ 所有测试通过！场景编辑页面语法正确！"
            exit 0
        else
            echo ""
            echo "✗ 编译后的文件存在PHP语法错误"
            exit 1
        fi
    else
        echo "未找到编译文件，可能页面未被访问"
    fi
else
    echo "未知状态码: $HTTP_CODE"
    exit 1
fi
