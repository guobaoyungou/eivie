#!/bin/bash
# 验证侧边栏菜单修复

echo "=========================================="
echo "模板三侧边栏菜单验证"
echo "=========================================="
echo ""

# 1. 检查 sidebar.html 是否包含重复的菜单项
echo "1. 检查菜单结构..."
echo ""

# 检查是否有重复的一级菜单
echo "【AI绘画】出现次数："
grep -c "AI绘画" /home/www/ai.eivie.cn/app/view/index3/public/sidebar.html

echo "【图像编辑】出现次数："
grep -c "图像编辑" /home/www/ai.eivie.cn/app/view/index3/public/sidebar.html

echo "【AI视频】出现次数："
grep -c "AI视频" /home/www/ai.eivie.cn/app/view/index3/public/sidebar.html

echo ""
echo "✅ 每个菜单项应该只出现1次（在nav-sub内）"
echo ""

# 2. 检查版本号
echo "2. 检查资源版本号..."
echo ""
grep "sidebar.css" /home/www/ai.eivie.cn/app/view/index3/index.html | head -1
grep "sidebar.js" /home/www/ai.eivie.cn/app/view/index3/index.html | head -1
echo ""
echo "✅ 应该显示 ?v=5"
echo ""

# 3. 检查分组标题
echo "3. 检查分组标题..."
echo ""
grep -o "nav-section-title.*</div>" /home/www/ai.eivie.cn/app/view/index3/public/sidebar.html | head -4
echo ""
echo "✅ 应该有4个分组：核心功能、AI创作、营销推广、我的空间"
echo ""

# 4. 检查nav-sub结构
echo "4. 检查二级菜单结构..."
echo ""
nav_sub_count=$(grep -c '<div class="nav-sub">' /home/www/ai.eivie.cn/app/view/index3/public/sidebar.html)
echo "nav-sub 数量: $nav_sub_count"
echo "✅ 应该有4个nav-sub（图像创作、视频创作、内容创作、营销活动）"
echo ""

# 5. 检查是否有nav-float-sub在HTML中
echo "5. 检查HTML中是否有nav-float-sub..."
echo ""
if grep -q "nav-float-sub" /home/www/ai.eivie.cn/app/view/index3/public/sidebar.html; then
    echo "❌ 发现nav-float-sub，应该只由JavaScript动态生成"
else
    echo "✅ HTML中没有nav-float-sub，符合规范"
fi
echo ""

# 6. 检查模板缓存
echo "6. 检查模板缓存..."
echo ""
if [ -d "/home/www/ai.eivie.cn/runtime/temp" ]; then
    temp_count=$(find /home/www/ai.eivie.cn/runtime/temp -type f 2>/dev/null | wc -l)
    echo "模板缓存文件数: $temp_count"
    if [ $temp_count -eq 0 ]; then
        echo "✅ 模板缓存已清空"
    else
        echo "⚠️  建议清空模板缓存: rm -rf /home/www/ai.eivie.cn/runtime/temp/*"
    fi
else
    echo "⚠️  未找到temp目录"
fi
echo ""

echo "=========================================="
echo "验证完成"
echo "=========================================="
echo ""
echo "下一步:"
echo "1. 访问: https://ai.eivie.cn/"
echo "2. 强制刷新: Ctrl+Shift+R (Windows) 或 Cmd+Shift+R (Mac)"
echo "3. 检查菜单结构是否正确"
echo ""
echo "如果仍有问题，清空浏览器缓存后重试"
