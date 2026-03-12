#!/bin/bash
# "做同款"和"开始创作"按钮修复验证脚本

echo "=================================================="
echo "  \"做同款\"和\"开始创作\"按钮功能验证"
echo "=================================================="

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "\n${YELLOW}【1. 检查state.selectedTemplateId字段】${NC}"

# 检查state中是否新增了selectedTemplateId
if grep -q "selectedTemplateId:" /home/www/ai.eivie.cn/static/index3/js/generation.js; then
    echo -e "${GREEN}✅ PASS${NC} - state.selectedTemplateId 字段已添加"
else
    echo -e "${RED}❌ FAIL${NC} - state.selectedTemplateId 字段缺失"
fi

echo -e "\n${YELLOW}【2. 检查handleGenerate()优先使用state】${NC}"

# 检查是否优先使用state.selectedTemplateId
if grep -q "state.selectedTemplateId || 0" /home/www/ai.eivie.cn/static/index3/js/generation.js; then
    echo -e "${GREEN}✅ PASS${NC} - handleGenerate()优先使用state.selectedTemplateId"
else
    echo -e "${RED}❌ FAIL${NC} - handleGenerate()未优先使用state"
fi

echo -e "\n${YELLOW}【3. 检查loadTemplateData()保存templateId】${NC}"

# 检查loadTemplateData是否保存到state
if grep -A 5 "function loadTemplateData" /home/www/ai.eivie.cn/static/index3/js/generation.js | grep -q "state.selectedTemplateId"; then
    echo -e "${GREEN}✅ PASS${NC} - loadTemplateData()保存templateId到state"
else
    echo -e "${RED}❌ FAIL${NC} - loadTemplateData()未保存templateId"
fi

echo -e "\n${YELLOW}【4. 检查loadAndSelectTemplate()保存templateId】${NC}"

# 检查loadAndSelectTemplate是否保存到state
if grep -A 10 "function loadAndSelectTemplate" /home/www/ai.eivie.cn/static/index3/js/generation.js | grep -q "state.selectedTemplateId = parseInt"; then
    echo -e "${GREEN}✅ PASS${NC} - loadAndSelectTemplate()保存templateId到state"
else
    echo -e "${RED}❌ FAIL${NC} - loadAndSelectTemplate()未保存templateId"
fi

echo -e "\n${YELLOW}【5. 检查版本号更新】${NC}"

# 检查photo_generation.html版本号
photo_version=$(grep "generation.js?v=" /home/www/ai.eivie.cn/app/view/index3/photo_generation.html | grep -oP 'v=\K[0-9]+' | head -1)
echo "photo_generation.html JS版本号: v=${photo_version}"
if [ "$photo_version" = "6" ]; then
    echo -e "${GREEN}✅ PASS${NC} - 图片生成页版本号正确"
else
    echo -e "${RED}❌ FAIL${NC} - 图片生成页版本号不正确(应为v6)"
fi

# 检查video_generation.html版本号
video_version=$(grep "generation.js?v=" /home/www/ai.eivie.cn/app/view/index3/video_generation.html | grep -oP 'v=\K[0-9]+' | head -1)
echo "video_generation.html JS版本号: v=${video_version}"
if [ "$video_version" = "6" ]; then
    echo -e "${GREEN}✅ PASS${NC} - 视频生成页版本号正确"
else
    echo -e "${RED}❌ FAIL${NC} - 视频生成页版本号不正确(应为v6)"
fi

echo -e "\n${YELLOW}【6. 检查Git提交】${NC}"
last_commit=$(git -C /home/www/ai.eivie.cn log --oneline -1)
echo "最新提交: ${last_commit}"

if echo "$last_commit" | grep -q "创作页面JS版本号"; then
    echo -e "${GREEN}✅ PASS${NC} - 最新提交与修复相关"
else
    echo -e "${YELLOW}⚠️  WARNING${NC} - 最新提交可能不相关"
fi

echo -e "\n=================================================="
echo -e "${GREEN}         验证完成！${NC}"
echo "=================================================="

echo -e "\n${YELLOW}问题回顾：${NC}"
echo "【症状】"
echo "  点击\"做同款\"按钮后跳转到创作页面，参数预填充成功，"
echo "  但点击\"开始创作\"按钮时提示\"请选择模型或模板\""
echo ""
echo "【根本原因】"
echo "  loadAndSelectTemplate()虽然选中了模板卡片并预填充参数，"
echo "  但没有将template_id保存到state中。"
echo "  handleGenerate()仅通过查找DOM元素获取templateId，"
echo "  当DOM不存在或未正确选中时，无法获取template_id。"

echo -e "\n${YELLOW}解决方案：${NC}"
echo "【状态优先，DOM兜底】"
echo "1. state新增selectedTemplateId字段"
echo "2. loadTemplateData()保存templateId到state"
echo "3. loadAndSelectTemplate()保存templateId到state"
echo "4. handleGenerate()优先使用state.selectedTemplateId"
echo "   - 如果state有值，直接使用"
echo "   - 如果state无值，从DOM获取(兜底)"

echo -e "\n${YELLOW}用户测试步骤：${NC}"
echo "【测试1: 点击\"做同款\"按钮】"
echo "1. 访问首页 https://ai.eivie.cn/"
echo "2. 点击任意场景卡片的\"做同款\"按钮"
echo "3. 预期效果:"
echo "   ✅ 跳转到创作页面"
echo "   ✅ 提示词自动填充"
echo "   ✅ 比例参数自动设置"
echo "   ✅ 模板卡片高亮(如果存在)"
echo "   ✅ state.selectedTemplateId已保存"
echo ""
echo "4. 直接点击\"✨ 立即生成\"按钮"
echo "5. 预期效果:"
echo "   ✅ 不会提示\"请选择模型或模板\""
echo "   ✅ 显示\"生成中...\"加载状态"
echo "   ✅ 成功提交生成任务"
echo "   ✅ 显示\"生成任务已提交！\"成功提示"

echo -e "\n${YELLOW}【测试2: 手动点击模板卡片】${NC}"
echo "1. 访问创作页面 /Index/photo_generation"
echo "2. 滚动到底部模板栏"
echo "3. 点击任意模板卡片"
echo "4. 预期效果:"
echo "   ✅ 模板卡片高亮(active状态)"
echo "   ✅ 显示\"已选择模板\"提示"
echo "   ✅ state.selectedTemplateId已保存"
echo ""
echo "5. 点击\"✨ 立即生成\"按钮"
echo "6. 预期效果:"
echo "   ✅ 成功提交生成任务"

echo -e "\n${YELLOW}修复前后对比：${NC}"
echo "【修复前】"
echo "  ❌ 点击\"做同款\" → 跳转 → 参数预填充 → 点击\"开始创作\""
echo "  ❌ 提示\"请选择模型或模板\" → 无法提交"
echo "  ❌ 用户体验差，需要重新选择模板"
echo ""
echo "【修复后】"
echo "  ✅ 点击\"做同款\" → 跳转 → 参数预填充 → 点击\"开始创作\""
echo "  ✅ 直接提交生成任务 → 成功"
echo "  ✅ 流畅自然，无需重复操作"

echo -e "\n${YELLOW}技术亮点：${NC}"
echo "✅ 状态驱动 - 优先使用内存状态，避免DOM依赖"
echo "✅ 双重保障 - state优先，DOM兜底，容错性强"
echo "✅ 一致性 - 所有模板选择路径统一保存到state"
echo "✅ 可维护性 - 代码逻辑清晰，易于理解和扩展"

echo ""
