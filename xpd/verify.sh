#!/bin/bash

# AI旅拍选片端 - 快速验证和演示脚本
# 日期: 2026-01-22

echo "========================================="
echo "  AI旅拍选片端 - 系统验证"
echo "========================================="
echo ""

# 颜色定义
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}1. 系统文件检查${NC}"
echo "-----------------------------------"

if [ -f "/www/wwwroot/eivie/xpd/index.html" ] && \
   [ -f "/www/wwwroot/eivie/xpd/templates/template_1/index.html" ] && \
   [ -f "/www/wwwroot/eivie/xpd/README.md" ]; then
    echo -e "${GREEN}✓ 所有核心文件已就绪${NC}"
else
    echo -e "${RED}✗ 部分文件缺失${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}2. 数据库字段验证${NC}"
echo "-----------------------------------"

cd /www/wwwroot/eivie
FIELD_EXISTS=$(php -r "\$c = include('config.php'); \$pdo = new PDO('mysql:host='.\$c['hostname'].';dbname='.\$c['database'], \$c['username'], \$c['password']); \$stmt = \$pdo->query('SHOW COLUMNS FROM '.\$c['prefix'].'mendian LIKE \"xpd_template\"'); echo \$stmt->rowCount();")

if [ "$FIELD_EXISTS" = "1" ]; then
    echo -e "${GREEN}✓ 数据库字段 xpd_template 已存在${NC}"
else
    echo -e "${RED}✗ 数据库字段不存在，正在创建...${NC}"
    php migrate_xpd.php
fi

echo ""
echo -e "${BLUE}3. 功能说明${NC}"
echo "-----------------------------------"
echo ""
echo "✅ 前端展示系统"
echo "   - 访问路径: /xpd/index.html"
echo "   - 模板系统: 支持多种展示风格"
echo "   - 自动轮播: 图片1秒/张，组5秒/组"
echo "   - 自动刷新: 30秒刷新数据"
echo ""
echo "✅ 后台管理集成"
echo "   - 路径: 系统 > 门店管理 > 编辑门店"
echo "   - 功能: URL生成、复制、预览、二维码"
echo "   - 模板: 5种展示模板可选"
echo ""
echo "✅ API接口"
echo "   - 接口: /api/ai-travel-photo/selection-list"
echo "   - 参数: aid, bid, mdid, limit"
echo "   - 返回: 人像列表 + 生成结果 + 二维码"
echo ""

echo -e "${BLUE}4. 使用示例${NC}"
echo "-----------------------------------"
echo ""
echo "【商户操作】"
echo "1. 登录后台 > 系统 > 门店管理"
echo "2. 编辑门店 > 查看\"选片URL\"字段"
echo "3. 选择展示模板 > 保存"
echo "4. 复制URL或二维码"
echo "5. 在大屏浏览器中打开URL"
echo ""
echo "【访问URL格式】"
echo "https://域名/xpd/index.html?aid=1&bid=商家ID&mdid=门店ID"
echo ""
echo "【演示URL示例】"

# 获取域名
DOMAIN=$(php -r "echo \$_SERVER['HTTP_HOST'] ?? 'localhost';")
if [ "$DOMAIN" = "localhost" ] || [ -z "$DOMAIN" ]; then
    DOMAIN="your-domain.com"
fi

echo "https://$DOMAIN/xpd/index.html?aid=1&bid=1&mdid=1"
echo ""

echo -e "${BLUE}5. 快速测试${NC}"
echo "-----------------------------------"
echo ""
echo "您可以通过以下方式测试系统："
echo ""
echo "A. 后台测试（推荐）："
echo "   1. 登录后台"
echo "   2. 进入门店管理"
echo "   3. 编辑任意门店"
echo "   4. 查看\"选片URL\"和\"展示模板\"字段"
echo "   5. 点击\"预览\"按钮直接查看效果"
echo ""
echo "B. 直接访问："
echo "   在浏览器中打开上述URL示例（需替换参数）"
echo ""
echo "C. API测试："
echo "   curl 'http://$DOMAIN/api/ai-travel-photo/selection-list?aid=1&bid=1&mdid=1'"
echo ""

echo -e "${BLUE}6. 文档资源${NC}"
echo "-----------------------------------"
echo ""
echo "📄 使用文档: /www/wwwroot/eivie/xpd/README.md"
echo "📄 完成报告: /www/wwwroot/eivie/XPD_COMPLETION_REPORT.md"
echo "🧪 测试脚本: /www/wwwroot/eivie/xpd/test.sh"
echo ""

echo "========================================="
echo -e "  ${GREEN}系统已就绪，可以开始使用！${NC}"
echo "========================================="
echo ""
echo "建议步骤："
echo "1. 先在后台编辑门店，查看选片URL"
echo "2. 点击预览按钮验证功能"
echo "3. 确认无误后部署到门店大屏"
echo ""
echo "如有问题，请查看文档或联系技术支持。"
echo ""
