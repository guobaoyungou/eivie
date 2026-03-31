#!/bin/bash
# ============================================
# 修复uniapp构建警告脚本
# ============================================

echo "🔧 修复uniapp构建警告..."
cd /home/www/ai.eivie.cn/uniapp

echo "📋 需要解决的问题："
echo "1. uni统计2.0 deviceId问题（HBuilderX版本问题）"
echo "2. pages.json文件过大（超过500KB）"

echo ""
echo "============================================"
echo "🔄 解决方案1：升级HBuilderX"
echo "============================================"

echo "📥 下载HBuilderX 3.6.7+："
echo "1. 访问 https://www.dcloud.io/hbuilderx.html"
echo "2. 下载最新版本（推荐3.6.7或更高）"
echo "3. 安装并替换旧版本"

echo ""
echo "📦 升级步骤："
echo "1. 备份当前项目"
echo "2. 使用新版本HBuilderX打开项目"
echo "3. 重新构建所有平台"
echo "4. 重新发布应用"

echo ""
echo "📝 uni统计2.0修复："
echo "1. 升级uniAdmin云函数"
echo "2. 重新打包Android应用"
echo "3. 验证统计数据准确性"
echo "详见：https://ask.dcloud.net.cn/article/40097"

echo ""
echo "============================================"
echo "🔄 解决方案2：优化pages.json"
echo "============================================"

echo "📊 当前pages.json大小：153KB"
echo "⚠️  警告：超过500KB会影响构建性能"

echo ""
echo "💡 优化建议："

echo "1. 分包优化："
echo "   - 检查分包结构，拆分大分包"
echo "   - 按功能模块重新组织分包"

echo "2. 移除无效配置："
echo "   - 检查并移除未使用的页面配置"
echo "   - 移除重复的页面配置"

echo "3. 简化配置："
echo "   - 移除不必要的style配置"
echo "   - 合并相似的页面配置"

echo ""
echo "🛠️ 手动优化步骤："

echo "步骤1：备份pages.json"
cp pages.json pages.json.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ 备份创建：pages.json.backup"

echo ""
echo "步骤2：分析分包结构"
echo "分包数量：$(grep -c '"root":' pages.json)"
echo "页面总数：$(grep -c '"path":' pages.json)"

echo ""
echo "步骤3：识别大分包"
echo "使用以下命令查找大分包："
echo "grep -A5 '"root":' pages.json | grep -c '"path":'"

echo ""
echo "步骤4：创建优化后的pages.json"
echo "建议结构："
echo '{'
echo '  "globalStyle": {...},'
echo '  "pages": ['
echo '    // 仅保留核心页面'
echo '    {"path": "pages/index/index", "style": {"navigationBarTitleText": "首页"}}'
echo '  ],'
echo '  "subPackages": ['
echo '    {'
echo '      "root": "activity/commission",'
echo '      "pages": ['
echo '        {"path": "index", "style": {...}},'
echo '        {"path": "withdraw", "style": {...}}'
echo '        // 仅保留常用页面'
echo '      ]'
echo '    },'
echo '    {'
echo '      "root": "activity/seckill",'
echo '      "pages": ['
echo '        {"path": "index", "style": {...}}'
echo '      ]'
echo '    }'
echo '    // 其他分包...'
echo '  ]'
echo '}'

echo ""
echo "============================================"
echo "🔄 解决方案3：构建配置优化"
echo "============================================"

echo "📝 创建vue.config.js优化配置："

cat > vue.config.js << 'EOF'
module.exports = {
    // 分包配置优化
    configureWebpack: {
        optimization: {
            splitChunks: {
                chunks: 'all',
                minSize: 10000,
                maxSize: 50000,
                minChunks: 1,
                maxAsyncRequests: 30,
                maxInitialRequests: 30,
                automaticNameDelimiter: '~',
                cacheGroups: {
                    vendors: {
                        test: /[\\/]node_modules[\\/]/,
                        priority: -10
                    },
                    default: {
                        minChunks: 2,
                        priority: -20,
                        reuseExistingChunk: true
                    }
                }
            }
        }
    },
    
    // 性能优化
    productionSourceMap: false,
    
    // 分包预加载
    chainWebpack: config => {
        config.plugin('preload').tap(options => {
            options[0].as = 'script';
            options[0].include = 'all';
            return options;
        });
    }
}
EOF

echo "✅ vue.config.js已创建"

echo ""
echo "📝 修改manifest.json配置："

# 检查manifest.json中的h5配置
if grep -q '"h5"' manifest.json; then
    echo "✅ manifest.json已有h5配置"
else
    echo "⚠️  manifest.json缺少h5配置"
    echo "建议添加以下配置："
    echo '"h5": {'
    echo '  "optimization": {'
    echo '    "treeShaking": {'
    echo '      "enable": true'
    echo '    }'
    echo '  }'
    echo '}'
fi

echo ""
echo "============================================"
echo "✅ 修复完成！"
echo ""
echo "📋 下一步操作："
echo "1. 升级HBuilderX到3.6.7+版本"
echo "2. 手动优化pages.json文件"
echo "3. 重新构建测试"
echo ""
echo "⚠️  注意事项："
echo "- 升级HBuilderX前备份项目"
echo "- pages.json优化需谨慎，避免删除必要配置"
echo "- 测试优化后的构建效果"
echo ""
echo "📞 技术支持："
echo "- uni统计2.0问题：https://ask.dcloud.net.cn/article/40097"
echo "- 分包优化：https://uniapp.dcloud.net.cn/collocation/pages.html#subpackages"
echo "- 性能优化：https://uniapp.dcloud.net.cn/performance.html"
echo "============================================"