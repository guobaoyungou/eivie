#!/bin/bash
# 菜单重复问题最终验证脚本

echo "=================================================="
echo "       菜单重复问题修复验证（v6）"
echo "=================================================="

# 颜色定义
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. 检查 CSS 默认隐藏规则
echo -e "\n${YELLOW}【1. CSS 规则检查】${NC}"
echo "检查 .nav-float-sub 是否有默认 display:none 规则..."

if grep -q "^\.nav-float-sub {" /home/www/ai.eivie.cn/static/index3/css/sidebar.css; then
    echo -e "${GREEN}✅ PASS${NC} - 找到 .nav-float-sub 基础类定义"
    grep -A 2 "^\.nav-float-sub {" /home/www/ai.eivie.cn/static/index3/css/sidebar.css | head -3
    
    if grep -A 2 "^\.nav-float-sub {" /home/www/ai.eivie.cn/static/index3/css/sidebar.css | grep -q "display: none"; then
        echo -e "${GREEN}✅ PASS${NC} - 默认隐藏规则存在"
    else
        echo -e "${RED}❌ FAIL${NC} - 缺少 display:none 规则"
    fi
else
    echo -e "${RED}❌ FAIL${NC} - 未找到 .nav-float-sub 基础类定义"
fi

# 2. 检查版本号
echo -e "\n${YELLOW}【2. 版本号检查】${NC}"
version=$(grep "sidebar.css?v=" /home/www/ai.eivie.cn/app/view/index3/index.html | head -1 | grep -oP 'v=\K[0-9]+')
echo "当前版本号: v=${version}"

if [ "$version" = "6" ]; then
    echo -e "${GREEN}✅ PASS${NC} - 版本号已更新到 v6"
else
    echo -e "${RED}❌ FAIL${NC} - 版本号不正确（应为 v6）"
fi

# 3. 检查 HTML 结构
echo -e "\n${YELLOW}【3. HTML 结构检查】${NC}"
nav_float_in_html=$(grep -c "nav-float-sub" /home/www/ai.eivie.cn/app/view/index3/public/sidebar.html)
echo "sidebar.html 中 nav-float-sub 出现次数: ${nav_float_in_html}"

if [ "$nav_float_in_html" = "0" ]; then
    echo -e "${GREEN}✅ PASS${NC} - HTML 中无 nav-float-sub（仅由 JS 动态生成）"
else
    echo -e "${RED}❌ FAIL${NC} - HTML 中不应包含 nav-float-sub"
fi

# 4. 检查菜单项数量
echo -e "\n${YELLOW}【4. 菜单项检查】${NC}"
ai_painting=$(grep -c "AI绘画" /home/www/ai.eivie.cn/app/view/index3/public/sidebar.html)
image_edit=$(grep -c "图像编辑" /home/www/ai.eivie.cn/app/view/index3/public/sidebar.html)
ai_video=$(grep -c "AI视频" /home/www/ai.eivie.cn/app/view/index3/public/sidebar.html)

echo "【AI绘画】出现次数: ${ai_painting}"
echo "【图像编辑】出现次数: ${image_edit}"
echo "【AI视频】出现次数: ${ai_video}"

if [ "$ai_painting" = "1" ] && [ "$image_edit" = "1" ] && [ "$ai_video" = "1" ]; then
    echo -e "${GREEN}✅ PASS${NC} - 每个菜单项只出现 1 次"
else
    echo -e "${RED}❌ FAIL${NC} - 菜单项有重复"
fi

# 5. 检查 JavaScript 逻辑
echo -e "\n${YELLOW}【5. JavaScript 检查】${NC}"
if grep -q "initFloatSubMenus" /home/www/ai.eivie.cn/static/index3/js/sidebar.js; then
    echo -e "${GREEN}✅ PASS${NC} - initFloatSubMenus 函数存在"
    
    if grep -q "floatSub.innerHTML = navSub.innerHTML" /home/www/ai.eivie.cn/static/index3/js/sidebar.js; then
        echo -e "${GREEN}✅ PASS${NC} - 动态复制逻辑正确"
    else
        echo -e "${YELLOW}⚠️  WARNING${NC} - 复制逻辑可能有变化"
    fi
else
    echo -e "${RED}❌ FAIL${NC} - 缺少 initFloatSubMenus 函数"
fi

# 6. 检查模板缓存
echo -e "\n${YELLOW}【6. 模板缓存检查】${NC}"
cache_count=$(find /home/www/ai.eivie.cn/runtime/temp -type f 2>/dev/null | wc -l)
echo "runtime/temp/ 中文件数: ${cache_count}"

if [ "$cache_count" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  WARNING${NC} - 模板缓存已重新生成（正常现象）"
else
    echo -e "${GREEN}✅ PASS${NC} - 缓存目录为空"
fi

# 7. 检查 Git 提交
echo -e "\n${YELLOW}【7. Git 提交检查】${NC}"
last_commit=$(git log --oneline -1)
echo "最新提交: ${last_commit}"

if echo "$last_commit" | grep -q "nav-float-sub"; then
    echo -e "${GREEN}✅ PASS${NC} - 最新提交与修复相关"
else
    echo -e "${YELLOW}⚠️  INFO${NC} - 最新提交可能不相关"
fi

# 总结
echo -e "\n=================================================="
echo -e "${GREEN}         修复验证完成！${NC}"
echo "=================================================="

echo -e "\n${YELLOW}用户操作步骤：${NC}"
echo "1. 访问 https://ai.eivie.cn/"
echo "2. 强制刷新浏览器："
echo "   - Windows/Linux: Ctrl + Shift + R"
echo "   - Mac: Cmd + Shift + R"
echo "3. 展开'图像创作'菜单，确认无重复项"
echo "4. 点击折叠按钮，hover 测试浮动菜单"

echo -e "\n${YELLOW}如果仍有问题：${NC}"
echo "- 完全清空浏览器缓存（F12 → Network → Disable cache）"
echo "- 使用无痕模式测试"
echo "- 查看文档: MENU_DUPLICATION_FIX.md"

echo ""
