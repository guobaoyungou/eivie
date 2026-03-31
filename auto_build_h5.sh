#!/bin/bash
# 自动化构建uniapp H5脚本

echo "==================================="
echo "开始自动化构建 uniapp H5..."
echo "==================================="

cd /home/www/ai.eivie.cn/uniapp

# 1. 使用uvm安装依赖（自动选择npm）
echo "1. 安装项目依赖..."
echo "npm" | uvm

# 2. 等待依赖安装完成
sleep 5

# 3. 执行构建
echo "2. 开始构建H5..."
npm run build:h5 --legacy-peer-deps

# 4. 检查构建结果
if [ -d "dist/build/h5" ]; then
    echo "3. 构建成功！开始部署..."
    
    # 备份旧的H5目录
    BACKUP_DIR="/home/www/ai.eivie.cn/h5_backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    cp -r /home/www/ai.eivie.cn/h5/* "$BACKUP_DIR/" 2>/dev/null || true
    echo "   旧H5已备份到: $BACKUP_DIR"
    
    # 清空并复制新文件
    rm -rf /home/www/ai.eivie.cn/h5/*
    cp -r dist/build/h5/* /home/www/ai.eivie.cn/h5/
    
    # 修复权限
    chown -R www:www /home/www/ai.eivie.cn/h5 2>/dev/null || true
    chmod -R 755 /home/www/ai.eivie.cn/h5
    
    echo "==================================="
    echo "✅ H5构建和部署完成！"
    echo "访问地址: https://ai.eivie.cn/h5/"
    echo "==================================="
else
    echo "==================================="
    echo "❌ H5构建失败"
    echo "==================================="
    exit 1
fi

# 5. 清理服务器缓存
echo "4. 清理服务器缓存..."
cd /home/www/ai.eivie.cn
php think clear
rm -rf runtime/temp/*.php 2>/dev/null || true

echo "==================================="
echo "所有操作完成！"
echo "==================================="
