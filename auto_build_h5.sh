#!/bin/bash
# 自动化构建uniapp H5脚本

set -e

ROOT_DIR="/home/www/ai.eivie.cn"
UNIAPP_DIR="$ROOT_DIR/uniapp"
H5_DIR="$ROOT_DIR/h5"

echo "==================================="
echo "开始自动化构建 uniapp H5..."
echo "==================================="

cd "$UNIAPP_DIR"

# 1. 使用uvm安装依赖（自动选择npm）
echo "1. 安装项目依赖..."
echo "npm" | uvm

# 2. 等待依赖安装完成
sleep 5

# 3. 执行构建（使用 uni-app 专用构建命令）
echo "2. 开始构建H5（uni-build）..."
npm run build:h5 --legacy-peer-deps

# 4. 检查构建结果
if [ -d "dist/build/h5" ]; then
    echo "3. 构建成功！开始部署..."
    
    # 备份旧的H5目录
    BACKUP_DIR="$ROOT_DIR/h5_backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    cp -r "$H5_DIR"/* "$BACKUP_DIR/" 2>/dev/null || true
    echo "   旧H5已备份到: $BACKUP_DIR"
    
    # 保存 cashier.html
    CASHIER_BAK=""
    if [ -f "$H5_DIR/cashier.html" ]; then
        CASHIER_BAK=$(mktemp)
        cp "$H5_DIR/cashier.html" "$CASHIER_BAK"
        echo "   已保存 cashier.html 副本"
    fi
    
    # 清空并复制新文件
    rm -rf "$H5_DIR"/*
    cp -r dist/build/h5/* "$H5_DIR/"
    
    # 生成带 uniacid 的数字HTML文件
    echo "   生成带 uniacid 的数字HTML文件..."
    if [ -f "$H5_DIR/index.html" ]; then
        for i in $(seq 1 20); do
            if grep -q 'var coverSupport' "$H5_DIR/index.html"; then
                sed "s|<script>var coverSupport|<script>var uniacid=${i};var siteroot=\"https://\"+window.location.host;var coverSupport|" \
                    "$H5_DIR/index.html" > "$H5_DIR/${i}.html"
            else
                sed "s|</head>|<script>var uniacid=${i};var siteroot=\"https://\"+window.location.host;</script></head>|" \
                    "$H5_DIR/index.html" > "$H5_DIR/${i}.html"
            fi
            echo "     - 生成 ${i}.html (uniacid=${i})"
        done
    fi
    
    # 执行后处理补丁
    echo "   执行后处理补丁..."
    if [ -f "$ROOT_DIR/h5_patch_generation.py" ] && [ -d "$H5_DIR/static/js" ]; then
        cd "$ROOT_DIR"
        python3 h5_patch_generation.py scan "$H5_DIR/static/js" || echo "   ⚠️ h5_patch_generation.py 执行失败"
    fi
    
    if [ -f "$ROOT_DIR/h5_repatch_normalizer.py" ]; then
        cd "$ROOT_DIR"
        python3 h5_repatch_normalizer.py || echo "   ⚠️ h5_repatch_normalizer.py 执行失败"
    fi
    
    # 恢复 cashier.html
    if [ -n "$CASHIER_BAK" ] && [ -f "$CASHIER_BAK" ]; then
        cp "$CASHIER_BAK" "$H5_DIR/cashier.html"
        rm -f "$CASHIER_BAK"
        echo "   已恢复 cashier.html"
    fi
    
    # 修复权限
    chown -R www:www "$H5_DIR" 2>/dev/null || true
    chmod -R 755 "$H5_DIR"
    
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
cd "$ROOT_DIR"
php think clear
rm -rf runtime/temp/*.php 2>/dev/null || true

echo "==================================="
echo "所有操作完成！"
echo "==================================="
