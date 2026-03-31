#!/bin/bash

# HBuilderX启动脚本
HBUILDERX_DIR="/home/www/ai.eivie.cn/hbuilderx"

# 设置库路径
export LD_LIBRARY_PATH="$HBUILDERX_DIR:$LD_LIBRARY_PATH"

# 设置Qt平台插件路径
export QT_QPA_PLATFORM_PLUGIN_PATH="$HBUILDERX_DIR/platforms"

# 设置Qt库路径
export QT_PLUGIN_PATH="$HBUILDERX_DIR"

# 设置HBuilderX CLI路径（供npm包使用）
export HBUILDERX_CLI_PATH="$HBUILDERX_DIR/cli"

# 设置显示（如果需要GUI）
export DISPLAY=${DISPLAY:-":0"}

echo "启动HBuilderX..."
echo "库路径: $LD_LIBRARY_PATH"
echo "Qt插件路径: $QT_QPA_PLATFORM_PLUGIN_PATH"

# 启动HBuilderX
cd "$HBUILDERX_DIR/HBuilderX"
./HBuilderX "$@"