#!/bin/bash
# 生成页面功能测试脚本

echo "================================"
echo "  AI生成页面功能测试"
echo "================================"
echo ""

# 定义颜色
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 检查文件是否存在
echo "1. 检查文件完整性..."
echo ""

files=(
    "app/view/index3/photo_generation.html"
    "app/view/index3/video_generation.html"
    "static/index3/css/generation.css"
    "static/index3/js/generation.js"
)

all_files_exist=true
for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file ${RED}(不存在)${NC}"
        all_files_exist=false
    fi
done

echo ""
echo "2. 检查控制器方法..."
echo ""

# 检查Index控制器中是否有新增的方法
if grep -q "public function photo_generation()" app/controller/Index.php; then
    echo -e "${GREEN}✓${NC} photo_generation() 方法已添加"
else
    echo -e "${RED}✗${NC} photo_generation() 方法未找到"
fi

if grep -q "public function video_generation()" app/controller/Index.php; then
    echo -e "${GREEN}✓${NC} video_generation() 方法已添加"
else
    echo -e "${RED}✗${NC} video_generation() 方法未找到"
fi

echo ""
echo "3. 检查侧边栏链接..."
echo ""

# 检查sidebar.html中的链接
if grep -q "Index/photo_generation" app/view/index3/public/sidebar.html; then
    echo -e "${GREEN}✓${NC} 照片生成链接已更新"
else
    echo -e "${RED}✗${NC} 照片生成链接未更新"
fi

if grep -q "Index/video_generation" app/view/index3/public/sidebar.html; then
    echo -e "${GREEN}✓${NC} 视频生成链接已更新"
else
    echo -e "${RED}✗${NC} 视频生成链接未更新"
fi

echo ""
echo "4. 检查CSS样式定义..."
echo ""

# 检查关键样式类
css_classes=(
    ".generation-container"
    ".generation-form-card"
    ".gf-tabs"
    ".generation-template-card"
)

for class in "${css_classes[@]}"; do
    if grep -q "$class" static/index3/css/generation.css; then
        echo -e "${GREEN}✓${NC} $class 样式已定义"
    else
        echo -e "${RED}✗${NC} $class 样式未找到"
    fi
done

echo ""
echo "5. 检查JavaScript功能..."
echo ""

# 检查关键函数
if grep -q "function initTabs()" static/index3/js/generation.js; then
    echo -e "${GREEN}✓${NC} Tab切换功能已实现"
else
    echo -e "${RED}✗${NC} Tab切换功能未找到"
fi

if grep -q "function initTemplateClick()" static/index3/js/generation.js; then
    echo -e "${GREEN}✓${NC} 模板点击功能已实现"
else
    echo -e "${RED}✗${NC} 模板点击功能未找到"
fi

echo ""
echo "================================"
echo "  测试完成"
echo "================================"
echo ""
echo -e "${YELLOW}访问路径：${NC}"
echo "  • 照片生成页: /?s=/Index/photo_generation"
echo "  • 视频生成页: /?s=/Index/video_generation"
echo ""
echo -e "${YELLOW}提示：${NC}"
echo "  1. 确保数据库中有 generation_scene_template 表数据"
echo "  2. 确保 webinfo['showweb'] = 3 (模板三)"
echo "  3. 清除浏览器缓存以查看最新样式"
echo ""
