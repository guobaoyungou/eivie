#!/bin/bash

# AI旅拍选片端功能测试脚本
# 日期: 2026-01-22

echo "========================================="
echo "  AI旅拍选片端功能测试"
echo "========================================="
echo ""

# 颜色定义
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 测试计数器
TOTAL=0
PASSED=0
FAILED=0

# 测试函数
test_item() {
    TOTAL=$((TOTAL + 1))
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}✗${NC} $2"
        FAILED=$((FAILED + 1))
    fi
}

echo "1. 检查目录结构..."
echo "-----------------------------------"

# 检查主目录
if [ -d "/www/wwwroot/eivie/xpd" ]; then
    test_item 0 "主目录存在: /www/wwwroot/eivie/xpd"
else
    test_item 1 "主目录不存在: /www/wwwroot/eivie/xpd"
fi

# 检查模板目录
if [ -d "/www/wwwroot/eivie/xpd/templates/template_1" ]; then
    test_item 0 "模板目录存在: templates/template_1"
else
    test_item 1 "模板目录不存在: templates/template_1"
fi

# 检查关键文件
if [ -f "/www/wwwroot/eivie/xpd/index.html" ]; then
    test_item 0 "路由入口文件存在: index.html"
else
    test_item 1 "路由入口文件不存在: index.html"
fi

if [ -f "/www/wwwroot/eivie/xpd/templates/template_1/index.html" ]; then
    test_item 0 "模板文件存在: template_1/index.html"
else
    test_item 1 "模板文件不存在: template_1/index.html"
fi

if [ -f "/www/wwwroot/eivie/xpd/README.md" ]; then
    test_item 0 "文档文件存在: README.md"
else
    test_item 1 "文档文件不存在: README.md"
fi

echo ""
echo "2. 检查数据库字段..."
echo "-----------------------------------"

# 检查xpd_template字段
DB_NAME=$(grep "'database'" /www/wwwroot/eivie/config.php | awk -F"'" '{print $4}')
DB_USER=$(grep "'username'" /www/wwwroot/eivie/config.php | awk -F"'" '{print $4}')
DB_PASS=$(grep "'password'" /www/wwwroot/eivie/config.php | awk -F"'" '{print $4}')
DB_PREFIX=$(grep "'prefix'" /www/wwwroot/eivie/config.php | awk -F"'" '{print $4}')

FIELD_CHECK=$(mysql -h localhost -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -se "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_NAME='${DB_PREFIX}mendian' AND COLUMN_NAME='xpd_template'" 2>/dev/null)

if [ "$FIELD_CHECK" = "1" ]; then
    test_item 0 "数据库字段存在: xpd_template"
else
    test_item 1 "数据库字段不存在: xpd_template"
fi

echo ""
echo "3. 检查后台集成..."
echo "-----------------------------------"

# 检查门店编辑页面是否包含选片URL
if grep -q "xpd_url" /www/wwwroot/eivie/app/view/mendian/edit.html; then
    test_item 0 "后台门店编辑页面已集成选片URL字段"
else
    test_item 1 "后台门店编辑页面未集成选片URL字段"
fi

# 检查是否包含模板选择
if grep -q "xpd_template" /www/wwwroot/eivie/app/view/mendian/edit.html; then
    test_item 0 "后台门店编辑页面已集成模板选择"
else
    test_item 1 "后台门店编辑页面未集成模板选择"
fi

# 检查JavaScript函数
if grep -q "generateXpdUrl" /www/wwwroot/eivie/app/view/mendian/edit.html; then
    test_item 0 "URL生成函数已实现"
else
    test_item 1 "URL生成函数未实现"
fi

if grep -q "copyXpdUrl" /www/wwwroot/eivie/app/view/mendian/edit.html; then
    test_item 0 "复制功能已实现"
else
    test_item 1 "复制功能未实现"
fi

if grep -q "previewXpdUrl" /www/wwwroot/eivie/app/view/mendian/edit.html; then
    test_item 0 "预览功能已实现"
else
    test_item 1 "预览功能未实现"
fi

if grep -q "showXpdQrcode" /www/wwwroot/eivie/app/view/mendian/edit.html; then
    test_item 0 "二维码功能已实现"
else
    test_item 1 "二维码功能未实现"
fi

echo ""
echo "4. 检查前端代码..."
echo "-----------------------------------"

# 检查Vue实例
if grep -q "createApp" /www/wwwroot/eivie/xpd/templates/template_1/index.html; then
    test_item 0 "Vue 3 实例已创建"
else
    test_item 1 "Vue 3 实例未创建"
fi

# 检查Swiper集成
if grep -q "Swiper" /www/wwwroot/eivie/xpd/templates/template_1/index.html; then
    test_item 0 "Swiper轮播组件已集成"
else
    test_item 1 "Swiper轮播组件未集成"
fi

# 检查数据获取方法
if grep -q "fetchData" /www/wwwroot/eivie/xpd/templates/template_1/index.html; then
    test_item 0 "数据获取方法已实现"
else
    test_item 1 "数据获取方法未实现"
fi

# 检查自动刷新
if grep -q "scheduleRefresh" /www/wwwroot/eivie/xpd/templates/template_1/index.html; then
    test_item 0 "自动刷新机制已实现"
else
    test_item 1 "自动刷新机制未实现"
fi

# 检查二维码生成
if grep -q "QRCode" /www/wwwroot/eivie/xpd/templates/template_1/index.html; then
    test_item 0 "二维码生成功能已实现"
else
    test_item 1 "二维码生成功能未实现"
fi

echo ""
echo "5. 检查API接口..."
echo "-----------------------------------"

# 检查API控制器
if [ -f "/www/wwwroot/eivie/app/controller/ApiAiTravelPhoto.php" ]; then
    test_item 0 "API控制器文件存在"
    
    # 检查selection_list方法
    if grep -q "selection_list" /www/wwwroot/eivie/app/controller/ApiAiTravelPhoto.php; then
        test_item 0 "selection_list接口已实现"
    else
        test_item 1 "selection_list接口未实现"
    fi
else
    test_item 1 "API控制器文件不存在"
fi

echo ""
echo "========================================="
echo "  测试结果汇总"
echo "========================================="
echo ""
echo "总计: $TOTAL 项"
echo -e "${GREEN}通过: $PASSED 项${NC}"
echo -e "${RED}失败: $FAILED 项${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ 所有测试通过！${NC}"
    echo ""
    echo "系统已就绪，可以进行以下操作："
    echo "1. 登录后台 > 系统 > 门店管理"
    echo "2. 编辑门店，查看选片URL"
    echo "3. 在浏览器中访问选片URL测试"
    exit 0
else
    echo -e "${RED}✗ 部分测试失败，请检查上述错误项${NC}"
    exit 1
fi
