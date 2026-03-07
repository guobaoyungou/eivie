#!/bin/bash

# AI旅拍商家Windows客户端 - 项目验证脚本
# 用于在Linux环境中验证项目结构和文件完整性

echo "=========================================="
echo "AI旅拍商家Windows客户端 - 项目验证"
echo "=========================================="
echo ""

# 颜色定义
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 计数器
total_checks=0
passed_checks=0
failed_checks=0

# 检查函数
check_file() {
    local file=$1
    local description=$2
    total_checks=$((total_checks + 1))
    
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓${NC} $description"
        passed_checks=$((passed_checks + 1))
        return 0
    else
        echo -e "${RED}✗${NC} $description - 文件不存在: $file"
        failed_checks=$((failed_checks + 1))
        return 1
    fi
}

check_dir() {
    local dir=$1
    local description=$2
    total_checks=$((total_checks + 1))
    
    if [ -d "$dir" ]; then
        echo -e "${GREEN}✓${NC} $description"
        passed_checks=$((passed_checks + 1))
        return 0
    else
        echo -e "${RED}✗${NC} $description - 目录不存在: $dir"
        failed_checks=$((failed_checks + 1))
        return 1
    fi
}

# 1. 检查目录结构
echo "1. 检查目录结构..."
echo "-----------------------------------"
check_dir "ViewModels" "ViewModels目录"
check_dir "Views" "Views目录"
check_dir "Models" "Models目录"
check_dir "Services" "Services目录"
check_dir "Converters" "Converters目录"
check_dir "Resources" "Resources目录"
check_dir "Resources/Styles" "样式资源目录"
check_dir "Utils" "Utils目录"
echo ""

# 2. 检查ViewModels文件
echo "2. 检查ViewModels文件..."
echo "-----------------------------------"
check_file "ViewModels/BaseViewModel.cs" "BaseViewModel"
check_file "ViewModels/MainViewModel.cs" "MainViewModel"
check_file "ViewModels/HomeViewModel.cs" "HomeViewModel"
check_file "ViewModels/SettingsViewModel.cs" "SettingsViewModel"
check_file "ViewModels/LogViewModel.cs" "LogViewModel"
check_file "ViewModels/AboutViewModel.cs" "AboutViewModel"
echo ""

# 3. 检查Views文件
echo "3. 检查Views文件..."
echo "-----------------------------------"
check_file "Views/MainWindow.xaml" "MainWindow XAML"
check_file "Views/MainWindow.xaml.cs" "MainWindow Code-behind"
check_file "Views/HomeView.xaml" "HomeView XAML"
check_file "Views/HomeView.xaml.cs" "HomeView Code-behind"
check_file "Views/SettingsView.xaml" "SettingsView XAML"
check_file "Views/SettingsView.xaml.cs" "SettingsView Code-behind"
check_file "Views/LogView.xaml" "LogView XAML"
check_file "Views/LogView.xaml.cs" "LogView Code-behind"
check_file "Views/AboutView.xaml" "AboutView XAML"
check_file "Views/AboutView.xaml.cs" "AboutView Code-behind"
echo ""

# 4. 检查Converters文件
echo "4. 检查Converters文件..."
echo "-----------------------------------"
check_file "Converters/ValueConverters.cs" "值转换器"
echo ""

# 5. 检查样式资源文件
echo "5. 检查样式资源文件..."
echo "-----------------------------------"
check_file "Resources/Styles/Colors.xaml" "颜色资源"
check_file "Resources/Styles/Buttons.xaml" "按钮样式"
check_file "Resources/Styles/Controls.xaml" "控件样式"
echo ""

# 6. 检查Models文件
echo "6. 检查Models文件..."
echo "-----------------------------------"
check_file "Models/ConfigModel.cs" "配置模型"
check_file "Models/DeviceInfo.cs" "设备信息模型"
check_file "Models/StatisticsInfo.cs" "统计信息模型"
check_file "Models/UploadTask.cs" "上传任务模型"
echo ""

# 7. 检查Services文件
echo "7. 检查Services文件..."
echo "-----------------------------------"
check_file "Services/ApiClient.cs" "API客户端"
check_file "Services/ConfigService.cs" "配置服务"
check_file "Services/DeviceService.cs" "设备服务"
check_file "Services/FileWatcherService.cs" "文件监控服务"
check_file "Services/HeartbeatService.cs" "心跳服务"
check_file "Services/LogService.cs" "日志服务"
check_file "Services/UploadService.cs" "上传服务"
echo ""

# 8. 检查应用配置文件
echo "8. 检查应用配置文件..."
echo "-----------------------------------"
check_file "App.xaml" "App.xaml"
check_file "App.xaml.cs" "App.xaml.cs"
check_file "AiTravelClient.csproj" "项目文件"
check_file "config.json" "配置文件"
echo ""

# 9. 检查XAML文件格式
echo "9. 检查XAML文件格式..."
echo "-----------------------------------"
xaml_files=$(find Views Resources -name "*.xaml" 2>/dev/null)
xaml_error=0

for xaml in $xaml_files App.xaml; do
    if [ -f "$xaml" ]; then
        # 检查基本的XML格式
        if grep -q "<?xml" "$xaml" || grep -q "<.*xmlns=" "$xaml"; then
            echo -e "${GREEN}✓${NC} XAML格式检查: $xaml"
            passed_checks=$((passed_checks + 1))
        else
            echo -e "${YELLOW}⚠${NC} XAML格式警告: $xaml"
        fi
        total_checks=$((total_checks + 1))
    fi
done
echo ""

# 10. 检查C#文件语法（基本检查）
echo "10. 检查C#文件基本语法..."
echo "-----------------------------------"
cs_files=$(find ViewModels Views Converters Services Models Utils -name "*.cs" 2>/dev/null)
cs_error=0

for cs in $cs_files App.xaml.cs; do
    if [ -f "$cs" ]; then
        # 检查namespace声明
        if grep -q "namespace AiTravelClient" "$cs"; then
            echo -e "${GREEN}✓${NC} 命名空间检查: $cs"
            passed_checks=$((passed_checks + 1))
        else
            echo -e "${YELLOW}⚠${NC} 命名空间警告: $cs"
        fi
        total_checks=$((total_checks + 1))
    fi
done
echo ""

# 11. 统计代码行数
echo "11. 代码统计..."
echo "-----------------------------------"
total_cs_lines=$(find . -name "*.cs" -exec wc -l {} + 2>/dev/null | tail -1 | awk '{print $1}')
total_xaml_lines=$(find . -name "*.xaml" -exec wc -l {} + 2>/dev/null | tail -1 | awk '{print $1}')
echo "C# 代码行数: $total_cs_lines"
echo "XAML 代码行数: $total_xaml_lines"
echo "总代码行数: $((total_cs_lines + total_xaml_lines))"
echo ""

# 12. 文件数量统计
echo "12. 文件统计..."
echo "-----------------------------------"
cs_count=$(find . -name "*.cs" | wc -l)
xaml_count=$(find . -name "*.xaml" | wc -l)
echo "C# 文件数: $cs_count"
echo "XAML 文件数: $xaml_count"
echo "总文件数: $((cs_count + xaml_count))"
echo ""

# 最终结果
echo "=========================================="
echo "验证结果汇总"
echo "=========================================="
echo "总检查项: $total_checks"
echo -e "${GREEN}通过: $passed_checks${NC}"
echo -e "${RED}失败: $failed_checks${NC}"
echo ""

if [ $failed_checks -eq 0 ]; then
    echo -e "${GREEN}✓ 项目结构完整，所有检查通过！${NC}"
    echo ""
    echo "下一步操作："
    echo "1. 将项目复制到Windows开发环境"
    echo "2. 使用Visual Studio 2019/2022打开项目"
    echo "3. 还原NuGet包（右键解决方案 -> 还原NuGet包）"
    echo "4. 构建项目（Ctrl+Shift+B）"
    echo "5. 运行项目（F5）"
    exit 0
else
    echo -e "${RED}✗ 发现 $failed_checks 个问题，请检查！${NC}"
    exit 1
fi
