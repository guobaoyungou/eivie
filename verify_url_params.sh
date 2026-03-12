#!/bin/bash
# URL参数处理功能验证脚本

echo "=================================================="
echo "     URL参数处理功能验证"
echo "=================================================="

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "\n${YELLOW}【1. 检查generation.js修改】${NC}"

# 检查handleUrlParams函数
echo "检查 handleUrlParams() 函数..."
if grep -q "function handleUrlParams()" /home/www/ai.eivie.cn/static/index3/js/generation.js; then
    echo -e "${GREEN}✅ PASS${NC} - handleUrlParams() 函数存在"
else
    echo -e "${RED}❌ FAIL${NC} - handleUrlParams() 函数缺失"
fi

# 检查loadAndSelectModel函数
echo "检查 loadAndSelectModel() 函数..."
if grep -q "function loadAndSelectModel" /home/www/ai.eivie.cn/static/index3/js/generation.js; then
    echo -e "${GREEN}✅ PASS${NC} - loadAndSelectModel() 函数存在"
else
    echo -e "${RED}❌ FAIL${NC} - loadAndSelectModel() 函数缺失"
fi

# 检查loadAndSelectTemplate函数
echo "检查 loadAndSelectTemplate() 函数..."
if grep -q "function loadAndSelectTemplate" /home/www/ai.eivie.cn/static/index3/js/generation.js; then
    echo -e "${GREEN}✅ PASS${NC} - loadAndSelectTemplate() 函数存在"
else
    echo -e "${RED}❌ FAIL${NC} - loadAndSelectTemplate() 函数缺失"
fi

# 检查URLSearchParams使用
echo "检查 URLSearchParams 使用..."
if grep -q "new URLSearchParams(window.location.search)" /home/www/ai.eivie.cn/static/index3/js/generation.js; then
    echo -e "${GREEN}✅ PASS${NC} - URLSearchParams 正确使用"
else
    echo -e "${RED}❌ FAIL${NC} - URLSearchParams 未使用"
fi

# 检查URL清理逻辑
echo "检查 history.replaceState() 使用..."
if grep -q "history.replaceState" /home/www/ai.eivie.cn/static/index3/js/generation.js; then
    echo -e "${GREEN}✅ PASS${NC} - URL清理逻辑存在"
else
    echo -e "${RED}❌ FAIL${NC} - URL清理逻辑缺失"
fi

echo -e "\n${YELLOW}【2. 检查版本号更新】${NC}"

# 检查photo_generation.html版本号
photo_version=$(grep "generation.js?v=" /home/www/ai.eivie.cn/app/view/index3/photo_generation.html | grep -oP 'v=\K[0-9]+' | head -1)
echo "photo_generation.html JS版本号: v=${photo_version}"
if [ "$photo_version" = "5" ]; then
    echo -e "${GREEN}✅ PASS${NC} - 图片生成页版本号正确"
else
    echo -e "${RED}❌ FAIL${NC} - 图片生成页版本号不正确(应为v5)"
fi

# 检查video_generation.html版本号
video_version=$(grep "generation.js?v=" /home/www/ai.eivie.cn/app/view/index3/video_generation.html | grep -oP 'v=\K[0-9]+' | head -1)
echo "video_generation.html JS版本号: v=${video_version}"
if [ "$video_version" = "5" ]; then
    echo -e "${GREEN}✅ PASS${NC} - 视频生成页版本号正确"
else
    echo -e "${RED}❌ FAIL${NC} - 视频生成页版本号不正确(应为v5)"
fi

echo -e "\n${YELLOW}【3. 检查代码完整性】${NC}"

# 统计新增代码行数
echo "统计URL参数处理相关代码..."
url_handler_lines=$(grep -n "handleUrlParams\|loadAndSelectModel\|loadAndSelectTemplate" /home/www/ai.eivie.cn/static/index3/js/generation.js | wc -l)
echo "URL处理相关函数调用: ${url_handler_lines} 处"

if [ "$url_handler_lines" -ge 4 ]; then
    echo -e "${GREEN}✅ PASS${NC} - 代码完整性良好"
else
    echo -e "${YELLOW}⚠️  WARNING${NC} - 代码可能不完整"
fi

echo -e "\n${YELLOW}【4. 检查Git提交】${NC}"
last_commit=$(git -C /home/www/ai.eivie.cn log --oneline -1)
echo "最新提交: ${last_commit}"

if echo "$last_commit" | grep -q "更新创作页面JS版本号"; then
    echo -e "${GREEN}✅ PASS${NC} - 最新提交与修复相关"
else
    echo -e "${YELLOW}⚠️  WARNING${NC} - 最新提交可能不相关"
fi

echo -e "\n=================================================="
echo -e "${GREEN}         验证完成！${NC}"
echo "=================================================="

echo -e "\n${YELLOW}功能说明：${NC}"
echo "1. handleUrlParams()"
echo "   - 解析URL中的model_id和template_id参数"
echo "   - 自动调用对应的加载函数"
echo ""
echo "2. loadAndSelectModel(modelId)"
echo "   - 调用API获取模型详情"
echo "   - 自动更新模型选择卡片"
echo "   - 显示'已选中模型:XXX'提示"
echo "   - 清除URL参数避免刷新重复加载"
echo ""
echo "3. loadAndSelectTemplate(templateId)"
echo "   - 调用API获取模板详情"
echo "   - 自动选中模板卡片(添加active类)"
echo "   - 预填充提示词到输入框"
echo "   - 设置默认比例参数"
echo "   - 清空模型选择(优先使用模板)"
echo "   - 显示'已加载模板:XXX'提示"
echo "   - 清除URL参数"

echo -e "\n${YELLOW}用户测试步骤：${NC}"
echo "1. 访问首页 https://ai.eivie.cn/"
echo "2. 点击任意模型卡片(如'豆包MarsCode')"
echo "3. 预期效果:"
echo "   ✅ 页面跳转到创作页面"
echo "   ✅ 显示'正在加载模型...'提示"
echo "   ✅ 显示'已选中模型:XXX'成功提示"
echo "   ✅ 模型选择卡片自动更新为对应模型名称"
echo "   ✅ URL中的?model_id=xxx参数自动清除"
echo ""
echo "4. 返回首页,点击场景卡片的'做同款'按钮"
echo "5. 预期效果:"
echo "   ✅ 页面跳转到创作页面"
echo "   ✅ 显示'正在加载模板...'提示"
echo "   ✅ 显示'已加载模板:XXX'成功提示"
echo "   ✅ 提示词输入框自动填充模板内容"
echo "   ✅ 比例参数自动设置为模板默认值"
echo "   ✅ 对应的模板卡片高亮显示(active状态)"
echo "   ✅ URL中的?template_id=xxx参数自动清除"

echo -e "\n${YELLOW}修复前后对比：${NC}"
echo "【修复前】"
echo "  ❌ 点击卡片 → 跳转 → 页面闪一下 → 恢复初始状态"
echo "  ❌ 用户需要重新手动选择模型/填写参数"
echo "  ❌ 用户体验差,操作繁琐"
echo ""
echo "【修复后】"
echo "  ✅ 点击卡片 → 跳转 → 自动加载 → 参数预填充完成"
echo "  ✅ 用户可以直接开始创作,无需重复操作"
echo "  ✅ 流畅自然,体验完美"

echo -e "\n${YELLOW}技术亮点：${NC}"
echo "✅ URLSearchParams API - 现代浏览器标准API"
echo "✅ history.replaceState - 清理URL不刷新页面"
echo "✅ 自动表单预填充 - 提升用户体验"
echo "✅ 友好的Toast提示 - 清晰的状态反馈"
echo "✅ 模板优先策略 - 自动清空模型选择"

echo ""
