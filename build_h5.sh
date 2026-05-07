#!/bin/bash
# H5构建脚本

set -e

ROOT_DIR="/home/www/ai.eivie.cn"
UNIAPP_DIR="$ROOT_DIR/uniapp"
H5_DIR="$ROOT_DIR/h5"

echo "============================================"
echo "开始构建H5页面..."
echo "============================================"

# 1. 进入uniapp目录
cd "$UNIAPP_DIR"

# 2. 检查node环境
echo "检查Node.js环境..."
node --version || { echo "Node.js未安装，请先安装Node.js"; exit 1; }
npm --version || { echo "npm未安装，请先安装npm"; exit 1; }

# 3. 安装依赖
echo "安装依赖包..."
npm install

# 4. 构建H5（使用 uni-app 专用构建命令）
echo "构建H5页面（uni-build）..."
npm run build:h5

# 5. 检查构建结果
if [ -d "dist/build/h5" ]; then
    echo "H5构建成功！"
    
    # 6. 备份旧的H5目录
    echo "备份旧的H5目录..."
    BACKUP_DIR="$ROOT_DIR/h5_backup_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    cp -r "$H5_DIR"/* "$BACKUP_DIR/" 2>/dev/null || true
    echo "旧H5已备份到: $BACKUP_DIR"
    
    # 6.1 保存 cashier.html（如果存在）
    CASHIER_BAK=""
    if [ -f "$H5_DIR/cashier.html" ]; then
        CASHIER_BAK=$(mktemp)
        cp "$H5_DIR/cashier.html" "$CASHIER_BAK"
        echo "已保存 cashier.html 副本"
    fi
    
    # 7. 复制新的H5文件
    echo "复制新的H5文件..."
    rm -rf "$H5_DIR"/*
    cp -r dist/build/h5/* "$H5_DIR/"
    
    # 8. 生成带 uniacid 的数字HTML文件
    echo "生成带 uniacid 的数字HTML文件..."
    if [ -f "$H5_DIR/index.html" ]; then
        for i in $(seq 1 20); do
            if grep -q 'var coverSupport' "$H5_DIR/index.html"; then
                # 在 coverSupport 所在的 <script> 标签后注入 uniacid
                sed "s|<script>var coverSupport|<script>var uniacid=${i};var siteroot=\"https://\"+window.location.host;var coverSupport|" \
                    "$H5_DIR/index.html" > "$H5_DIR/${i}.html"
            else
                # 在 </head> 前插入独立的 script 标签
                sed "s|</head>|<script>var uniacid=${i};var siteroot=\"https://\"+window.location.host;</script></head>|" \
                    "$H5_DIR/index.html" > "$H5_DIR/${i}.html"
            fi
            echo "  - 生成 ${i}.html (uniacid=${i})"
        done
    else
        echo "  ⚠️  index.html 不存在，跳过数字HTML生成"
    fi
    
    # 9. 执行后处理补丁
    echo "执行后处理补丁..."
    if [ -f "$ROOT_DIR/h5_patch_generation.py" ] && [ -d "$H5_DIR/static/js" ]; then
        echo "  - 执行 h5_patch_generation.py scan ..."
        cd "$ROOT_DIR"
        python3 h5_patch_generation.py scan "$H5_DIR/static/js" || echo "  ⚠️ h5_patch_generation.py 执行失败"
    else
        echo "  ⚠️ 跳过 h5_patch_generation.py"
    fi
    
    if [ -f "$ROOT_DIR/h5_repatch_normalizer.py" ]; then
        echo "  - 执行 h5_repatch_normalizer.py ..."
        cd "$ROOT_DIR"
        python3 h5_repatch_normalizer.py || echo "  ⚠️ h5_repatch_normalizer.py 执行失败"
    else
        echo "  ⚠️ 跳过 h5_repatch_normalizer.py"
    fi
    
    # 10. 恢复 cashier.html
    if [ -n "$CASHIER_BAK" ] && [ -f "$CASHIER_BAK" ]; then
        cp "$CASHIER_BAK" "$H5_DIR/cashier.html"
        rm -f "$CASHIER_BAK"
        echo "已恢复 cashier.html"
    fi
    
    # 11. 修复权限
    echo "修复文件权限..."
    chown -R www:www "$H5_DIR"
    chmod -R 755 "$H5_DIR"
    
    echo "H5构建和部署完成！"
    echo "访问地址: https://ai.eivie.cn/h5/"
else
    echo "H5构建失败，请检查错误信息"
    exit 1
fi

# 12. 清理缓存
echo "清理服务器缓存..."
rm -rf "$ROOT_DIR/runtime/cache"/* "$ROOT_DIR/runtime/temp"/* 2>/dev/null || true

echo "============================================"
echo "所有操作完成！"
echo "============================================"