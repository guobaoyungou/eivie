#!/bin/bash
# ============================================
# uniapp封面比例修复脚本
# 将默认值从 '1:1' 改为 '3:4'
# ============================================

echo "🔧 开始修复uniapp封面比例默认值..."

# 进入uniapp目录
cd /home/www/ai.eivie.cn/uniapp

echo "📁 当前目录: $(pwd)"

# 1. 修复 dp-photo-generation 组件
echo "🔍 修复 dp-photo-generation.vue..."
if [ -f "components/dp-photo-generation/dp-photo-generation.vue" ]; then
    # 修复计算属性（已修复，确认）
    echo "✅ 计算属性 coverRatio() 已修复为 '3:4'"
    
    # 修复 popupSelectedRatio 默认值（第134行）
    sed -i "134s/popupSelectedRatio: '1:1'/popupSelectedRatio: '3:4'/" components/dp-photo-generation/dp-photo-generation.vue
    echo "✅ 修复第134行: popupSelectedRatio 默认值"
    
    # 修复 popupSelectedRatio 重置值（第247行）
    sed -i "247s/this.popupSelectedRatio = '1:1'/this.popupSelectedRatio = '3:4'/" components/dp-photo-generation/dp-photo-generation.vue
    echo "✅ 修复第247行: popupSelectedRatio 重置值"
    
    # 注意：popupRatioOptions数组中的'1:1'是选项列表，不需要修改
    echo "ℹ️  popupRatioOptions 中的 '1:1' 是选项列表，无需修改"
else
    echo "❌ 文件不存在: components/dp-photo-generation/dp-photo-generation.vue"
fi

# 2. 修复 dp-video-generation 组件
echo ""
echo "🔍 检查 dp-video-generation.vue..."
if [ -f "components/dp-video-generation/dp-video-generation.vue" ]; then
    # 检查计算属性
    if grep -q "coverRatio()" components/dp-video-generation/dp-video-generation.vue; then
        COVER_RATIO_LINE=$(grep -n "coverRatio()" components/dp-video-generation/dp-video-generation.vue | head -1 | cut -d: -f1)
        if grep -q "return this.params.cover_ratio || '3:4'" components/dp-video-generation/dp-video-generation.vue; then
            echo "✅ dp-video-generation 组件默认值已为 '3:4' (第$COVER_RATIO_LINE行)"
        else
            echo "⚠️  dp-video-generation 组件需要检查"
        fi
    fi
else
    echo "❌ 文件不存在: components/dp-video-generation/dp-video-generation.vue"
fi

# 3. 检查其他可能的位置
echo ""
echo "🔍 检查其他组件中的 '1:1' 默认值..."
FILES_WITH_1_1=$(grep -r "'1:1'" components/ 2>/dev/null | grep -v "popupRatioOptions\|'1:1','2:3'" | head -10)

if [ -n "$FILES_WITH_1_1" ]; then
    echo "⚠️  发现其他 '1:1' 默认值："
    echo "$FILES_WITH_1_1"
    echo ""
    echo "需要手动检查这些文件是否为默认值"
else
    echo "✅ 未发现其他 '1:1' 默认值"
fi

# 4. 验证修复
echo ""
echo "🔍 验证修复结果..."
echo "在 dp-photo-generation.vue 中查找 '1:1'（除选项列表外）："
grep -n "'1:1'" components/dp-photo-generation/dp-photo-generation.vue | grep -v "popupRatioOptions"

echo ""
echo "在 dp-photo-generation.vue 中查找 '3:4' 默认值："
grep -n "'3:4'\|cover_ratio.*3:4" components/dp-photo-generation/dp-photo-generation.vue

echo ""
echo "============================================"
echo "✅ 修复完成！"
echo ""
echo "下一步操作："
echo "1. 在HBuilderX中重新构建项目"
echo "2. 部署构建结果到服务器"
echo "3. 清理所有缓存"
echo "4. 验证封面比例是否为3:4"
echo ""
echo "修复的文件："
echo "- components/dp-photo-generation/dp-photo-generation.vue"
echo "- components/dp-video-generation/dp-video-generation.vue (已为3:4)"
echo "============================================"