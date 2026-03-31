#!/bin/bash
# ============================================
# H5构建结果部署脚本
# 将uniapp构建的H5文件部署到服务器
# ============================================

echo "🚀 H5构建结果部署脚本"
echo "============================================"

# 项目根目录
PROJECT_ROOT="/home/www/ai.eivie.cn"
H5_DIR="$PROJECT_ROOT/h5"
UNIAPP_DIR="$PROJECT_ROOT/uniapp"
BUILD_SOURCE="$UNIAPP_DIR/dist/build/h5"

# 检查目录
if [ ! -d "$H5_DIR" ]; then
    echo "❌ H5目录不存在: $H5_DIR"
    exit 1
fi

if [ ! -d "$BUILD_SOURCE" ]; then
    echo "❌ 构建源目录不存在: $BUILD_SOURCE"
    echo ""
    echo "请先构建uniapp项目："
    echo "1. 使用HBuilderX构建H5"
    echo "2. 确保构建文件在: $BUILD_SOURCE"
    echo ""
    echo "如果使用HBuilderX构建，构建文件默认在："
    echo "$UNIAPP_DIR/unpackage/dist/build/h5"
    exit 1
fi

# 备份当前H5
echo "📊 备份当前H5..."
BACKUP_DIR="${H5_DIR}_backup_$(date +%Y%m%d_%H%M%S)"
if cp -r "$H5_DIR" "$BACKUP_DIR"; then
    echo "✅ 备份成功: $BACKUP_DIR"
else
    echo "⚠️  备份失败，继续部署..."
fi

# 统计构建文件
echo ""
echo "📦 构建文件统计："
echo "源目录: $BUILD_SOURCE"
find "$BUILD_SOURCE" -type f | wc -l | xargs echo "文件数量: "
du -sh "$BUILD_SOURCE" | awk '{print "总大小: "$1}'

# 确认部署
echo ""
read -p "是否继续部署？(y/n): " CONFIRM
if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
    echo "部署已取消"
    exit 0
fi

# 部署步骤
echo ""
echo "🔧 开始部署..."

# 1. 清理旧文件（保留目录结构）
echo "1. 清理旧文件..."
cd "$H5_DIR"
find . -type f \( -name "*.html" -o -name "*.js" -o -name "*.css" -o -name "*.map" \) -delete 2>/dev/null || true

# 2. 复制新文件
echo "2. 复制新构建文件..."
cp -r "$BUILD_SOURCE"/* "$H5_DIR"/

# 3. 更新版本号（强制浏览器刷新）
echo "3. 更新版本号..."
cd "$H5_DIR"
find . -name "*.html" -type f -exec sed -i 's/v=[0-9]*/v=4/g' {} \; 2>/dev/null || true

# 4. 设置文件权限
echo "4. 设置文件权限..."
chown -R www:www "$H5_DIR" 2>/dev/null || true
chmod -R 755 "$H5_DIR" 2>/dev/null || true

# 5. 验证部署
echo ""
echo "✅ 部署完成！"
echo ""
echo "📊 部署结果统计："
cd "$H5_DIR"
find . -type f | wc -l | xargs echo "文件数量: "
du -sh . | awk '{print "总大小: "$1}'

# 6. 清理缓存建议
echo ""
echo "🧹 清理缓存建议："
echo "1. 服务器缓存："
echo "   cd $PROJECT_ROOT && rm -rf runtime/cache/* runtime/temp/*"
echo ""
echo "2. H5浏览器缓存："
echo "   - 按 Ctrl+Shift+Delete 清除浏览器缓存"
echo "   - 或访问 https://ai.eivie.cn/h5/ 按 Ctrl+F5"
echo ""
echo "3. 验证：访问 https://ai.eivie.cn/h5/ 检查封面比例"

# 7. 紧急回滚说明
echo ""
echo "🔄 紧急回滚："
echo "如果需要回滚到备份："
echo "cp -r $BACKUP_DIR/* $H5_DIR/"

echo ""
echo "============================================"
echo "🎉 部署完成！请验证封面比例是否为3:4"
echo "============================================"