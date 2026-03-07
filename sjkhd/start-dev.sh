#!/bin/bash

echo "========================================"
echo "  AI旅拍商家客户端 - 开发环境启动"
echo "========================================"
echo ""

# 检查 Node.js
if ! command -v node &> /dev/null; then
    echo "[错误] 未检测到 Node.js，请先安装 Node.js"
    echo "下载地址: https://nodejs.org/"
    exit 1
fi

echo "[✓] Node.js 已安装: $(node --version)"
echo ""

# 检查 node_modules
if [ ! -d "node_modules" ]; then
    echo "[!] 检测到依赖未安装，开始安装..."
    echo ""
    npm install
    if [ $? -ne 0 ]; then
        echo "[错误] 依赖安装失败"
        exit 1
    fi
    echo ""
    echo "[✓] 依赖安装成功"
fi

echo "[✓] 依赖已安装"
echo ""

echo "========================================"
echo "  启动开发服务器..."
echo "========================================"
echo ""
echo "提示："
echo "- 按 Ctrl+C 可以停止服务器"
echo "- Electron 窗口会自动打开"
echo "- 修改代码会自动热重载"
echo ""

npm run dev
