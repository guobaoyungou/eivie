#!/bin/bash
# ============================================
# 修复uniapp路径错误脚本
# 解决H5平台文件查找失败的问题
# ============================================

echo "🔧 开始修复uniapp路径错误..."
cd /home/www/ai.eivie.cn/uniapp

echo "📊 检查所有缺失的文件..."

# 1. 检查 tki-tree.vue 文件
echo ""
echo "1. 检查 tki-tree/tki-tree.vue:"
if [ -f "adminExt/adminuser/tki-tree/tki-tree.vue" ]; then
    echo "✅ 文件存在: adminExt/adminuser/tki-tree/tki-tree.vue"
    
    # 检查 edit.vue 中的 import 语句
    if grep -q "@/adminExt/adminuser/tki-tree/tki-tree.vue" adminExt/adminuser/edit.vue; then
        echo "✅ import 语句正确"
    else
        echo "⚠️  import 语句可能有问题"
    fi
else
    echo "❌ 文件不存在"
    # 尝试查找该文件
    find . -name "tki-tree.vue" 2>/dev/null | head -5
fi

# 2. 检查 order.vue 文件
echo ""
echo "2. 检查 huodongbaoming/order.vue:"
if [ -f "adminExt/huodongbaoming/order.vue" ]; then
    echo "✅ 文件存在: adminExt/huodongbaoming/order.vue"
else
    echo "❌ 文件不存在"
    # 检查是否有类似的文件
    echo "🔍 搜索 order.vue 文件:"
    find . -name "order.vue" 2>/dev/null
fi

# 3. 检查 echarts 相关文件
echo ""
echo "3. 检查 echarts 文件:"
if [ -d "echarts" ]; then
    echo "✅ echarts 目录存在"
    
    # 检查 l-echart.vue
    if [ -f "echarts/l-echart/l-echart.vue" ]; then
        echo "✅ l-echart.vue 文件存在"
    else
        echo "❌ l-echart.vue 文件不存在"
        echo "🔍 搜索 l-echart 相关文件:"
        find . -name "*l-echart*" -o -name "*echart*" 2>/dev/null | head -10
    fi
    
    # 检查 echarts.min.js
    if [ -f "echarts/static/echarts.min.js" ]; then
        echo "✅ echarts.min.js 文件存在"
    else
        echo "❌ echarts.min.js 文件不存在"
        echo "🔍 搜索 echarts.min.js 文件:"
        find . -name "echarts.min.js" 2>/dev/null
    fi
else
    echo "❌ echarts 目录不存在"
fi

# 4. 检查 h5zb 相关文件
echo ""
echo "4. 检查 h5zb 文件:"
if [ -d "h5zb" ]; then
    echo "✅ h5zb 目录存在"
    
    # 检查 tcplayer 文件
    if [ -f "h5zb/client/tcplayer.v5.1.0.min.js" ]; then
        echo "✅ tcplayer.v5.1.0.min.js 文件存在"
    else
        echo "❌ tcplayer.v5.1.0.min.js 文件不存在"
        echo "🔍 搜索 tcplayer 文件:"
        find . -name "*tcplayer*" 2>/dev/null
    fi
    
    # 检查 TXLivePusher 文件
    if [ -f "h5zb/manage/txlive/TXLivePusher-2.1.1.min.js" ]; then
        echo "✅ TXLivePusher-2.1.1.min.js 文件存在"
    else
        echo "❌ TXLivePusher-2.1.1.min.js 文件不存在"
        echo "🔍 搜索 TXLivePusher 文件:"
        find . -name "*TXLivePusher*" 2>/dev/null
    fi
else
    echo "❌ h5zb 目录不存在"
fi

# 5. 检查 uni-datetime-picker 文件
echo ""
echo "5. 检查 uni-datetime-picker:"
if [ -d "pagesB/admin/uni-datetime-picker" ]; then
    echo "✅ uni-datetime-picker 目录存在"
else
    echo "❌ uni-datetime-picker 目录不存在"
    echo "🔍 搜索 uni-datetime-picker 相关文件:"
    find . -name "*datetime*" -o -name "*picker*" 2>/dev/null | grep -i datetime | head -10
fi

echo ""
echo "============================================"
echo "🔍 分析结果："
echo ""
echo "问题原因："
echo "1. 文件存在但路径引用可能有问题（尤其是H5平台）"
echo "2. uni-app x 项目在 H5 平台路径解析可能不同"
echo "3. 可能需要修复 import 语句或创建缺失文件"
echo ""
echo "修复方案："
echo "1. 检查所有 import 语句的路径是否正确"
echo "2. 确保使用正确的相对路径或绝对路径"
echo "3. 对于 H5 平台，可能需要调整路径解析方式"
echo ""
echo "下一步操作："
echo "1. 修复 import 语句中的路径"
echo "2. 创建缺失的文件（如果确实缺失）"
echo "3. 测试 H5 平台构建"
echo "============================================"