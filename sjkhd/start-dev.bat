@echo off
echo ========================================
echo   AI旅拍商家客户端 - 开发环境启动
echo ========================================
echo.

REM 检查 Node.js
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [错误] 未检测到 Node.js，请先安装 Node.js
    echo 下载地址: https://nodejs.org/
    pause
    exit /b 1
)

echo [✓] Node.js 已安装
echo.

REM 检查 node_modules
if not exist "node_modules" (
    echo [!] 检测到依赖未安装，开始安装...
    echo.
    npm install
    if %errorlevel% neq 0 (
        echo [错误] 依赖安装失败
        pause
        exit /b 1
    )
    echo.
    echo [✓] 依赖安装成功
)

echo [✓] 依赖已安装
echo.

echo ========================================
echo   启动开发服务器...
echo ========================================
echo.
echo 提示：
echo - 按 Ctrl+C 可以停止服务器
echo - Electron 窗口会自动打开
echo - 修改代码会自动热重载
echo.

npm run dev

pause
