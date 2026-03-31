#!/bin/bash
# H5构建脚本

echo "开始构建H5页面..."

# 1. 进入uniapp目录
cd /home/www/ai.eivie.cn/uniapp

# 2. 检查node环境
echo "检查Node.js环境..."
node --version || { echo "Node.js未安装，请先安装Node.js"; exit 1; }
npm --version || { echo "npm未安装，请先安装npm"; exit 1; }

# 3. 安装依赖
echo "安装依赖包..."
npm install

# 4. 构建H5
echo "构建H5页面..."
npm run build:h5

# 5. 检查构建结果
if [ -d "dist/build/h5" ]; then
    echo "H5构建成功！"
    
    # 6. 备份旧的H5目录
    echo "备份旧的H5目录..."
    BACKUP_DIR="/home/www/ai.eivie.cn/h5_backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    cp -r /home/www/ai.eivie.cn/h5/* "$BACKUP_DIR/" 2>/dev/null || true
    echo "旧H5已备份到: $BACKUP_DIR"
    
    # 7. 复制新的H5文件
    echo "复制新的H5文件..."
    rm -rf /home/www/ai.eivie.cn/h5/*
    cp -r dist/build/h5/* /home/www/ai.eivie.cn/h5/
    
    # 8. 修复权限
    echo "修复文件权限..."
    chown -R www:www /home/www/ai.eivie.cn/h5
    chmod -R 755 /home/www/ai.eivie.cn/h5
    
    echo "H5构建和部署完成！"
    echo "访问地址: https://ai.eivie.cn/h5/"
else
    echo "H5构建失败，请检查错误信息"
    exit 1
fi

# 9. 清理缓存
echo "清理服务器缓存..."
rm -rf /home/www/ai.eivie.cn/runtime/cache/* /home/www/ai.eivie.cn/runtime/temp/* 2>/dev/null || true

echo "所有操作完成！"