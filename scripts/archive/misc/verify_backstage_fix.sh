#!/bin/bash
# 后台错误修复验证脚本

echo "=========================================="
echo "后台错误修复验证"
echo "=========================================="
echo ""

# 检查修复的文件是否存在
echo "1. 检查修复文件..."
if [ -f "/home/www/ai.eivie.cn/app/view/backstage/index.html" ]; then
    echo "✅ index.html 存在"
else
    echo "❌ index.html 不存在"
fi

if [ -f "/home/www/ai.eivie.cn/app/view/backstage/welcome.html" ]; then
    echo "✅ welcome.html 存在"
else
    echo "❌ welcome.html 不存在"
fi

echo ""
echo "2. 检查关键修复内容..."

# 检查 adjustShortcutMenu 的空值检查
if grep -q "if (!container.length || !container\[0\])" /home/www/ai.eivie.cn/app/view/backstage/index.html; then
    echo "✅ adjustShortcutMenu 空值检查已添加"
else
    echo "❌ adjustShortcutMenu 空值检查未找到"
fi

# 检查 WebSocket try-catch
if grep -q "try {" /home/www/ai.eivie.cn/app/view/backstage/index.html | grep -A 5 "websocket = new WebSocket"; then
    echo "✅ WebSocket try-catch 已添加"
else
    echo "⚠️  WebSocket try-catch 检查需要手动验证"
fi

# 检查 ECharts 延迟初始化
if grep -q "function initDataChart()" /home/www/ai.eivie.cn/app/view/backstage/welcome.html; then
    echo "✅ dataChart 延迟初始化已添加"
else
    echo "❌ dataChart 延迟初始化未找到"
fi

if grep -q "function initMonthOrder()" /home/www/ai.eivie.cn/app/view/backstage/welcome.html; then
    echo "✅ getMonthOrder 延迟初始化已添加"
else
    echo "❌ getMonthOrder 延迟初始化未找到"
fi

if grep -q "function initMemberGailan()" /home/www/ai.eivie.cn/app/view/backstage/welcome.html; then
    echo "✅ getMemberGailan 延迟初始化已添加"
else
    echo "❌ getMemberGailan 延迟初始化未找到"
fi

echo ""
echo "3. WebSocket 服务检查..."

# 检查常见的 WebSocket 端口
for port in 9501 9502 2346 2347; do
    if ss -tlnp 2>/dev/null | grep -q ":$port"; then
        echo "✅ 检测到端口 $port 正在监听（可能是 WebSocket 服务）"
    fi
done

# 检查 Workerman/Swoole 进程
if ps aux | grep -v grep | grep -q "workerman\|swoole"; then
    echo "✅ 检测到 WebSocket 相关进程运行中"
else
    echo "⚠️  未检测到 WebSocket 相关进程，请手动检查"
fi

echo ""
echo "4. Nginx 配置检查..."

# 检查 Nginx 是否运行
if pgrep nginx > /dev/null; then
    echo "✅ Nginx 正在运行"
    
    # 检查 Nginx 配置
    if nginx -t 2>&1 | grep -q "successful"; then
        echo "✅ Nginx 配置验证通过"
    else
        echo "⚠️  Nginx 配置可能有问题，请运行: nginx -t"
    fi
else
    echo "⚠️  Nginx 未运行"
fi

echo ""
echo "=========================================="
echo "验证完成！"
echo "=========================================="
echo ""
echo "下一步操作："
echo "1. 访问后台页面: https://ai.eivie.cn/?s=/Backstage/index"
echo "2. 打开浏览器控制台，检查是否还有错误"
echo "3. 检查以下功能："
echo "   - 快捷菜单是否正常显示"
echo "   - 数据趋势图是否正常渲染"
echo "   - 订单统计图是否正常显示"
echo "   - 会员概览图是否正常显示"
echo "4. 如果 WebSocket 仍然报错，请查看: BACKSTAGE_ERROR_FIX.md"
echo ""
