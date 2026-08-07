#!/bin/bash
# AI模型管理系统测试脚本
# 用于验证各个功能模块的正确性

echo "=========================================="
echo "AI模型管理系统功能测试"
echo "=========================================="
echo ""

# 数据库配置
DB_HOST="localhost"
DB_USER="root"
DB_PASS=$(grep -oP "(?<='password'   => ').*(?=')" /www/wwwroot/eivie/config/database.php)
DB_NAME=$(grep -oP "(?<='database'        => ').*(?=')" /www/wwwroot/eivie/config/database.php)

echo "1. 检查数据库表结构..."
echo "----------------------------------------"

# 检查模型分类表
if mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "SHOW TABLES LIKE 'ddwx_ai_model_category'" | grep -q "ddwx_ai_model_category"; then
    echo "✓ ddwx_ai_model_category 表存在"
    CATEGORY_COUNT=$(mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -se "SELECT COUNT(*) FROM ddwx_ai_model_category WHERE is_system=1")
    echo "  系统预置分类数量: $CATEGORY_COUNT"
else
    echo "✗ ddwx_ai_model_category 表不存在"
fi

# 检查模型配置表字段
if mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "SHOW COLUMNS FROM ddwx_ai_travel_photo_model LIKE 'category_code'" | grep -q "category_code"; then
    echo "✓ ddwx_ai_travel_photo_model 表已扩展"
else
    echo "✗ ddwx_ai_travel_photo_model 表未扩展"
fi

# 检查使用记录表
if mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "SHOW TABLES LIKE 'ddwx_ai_model_usage_log'" | grep -q "ddwx_ai_model_usage_log"; then
    echo "✓ ddwx_ai_model_usage_log 表存在"
else
    echo "✗ ddwx_ai_model_usage_log 表不存在"
fi

echo ""
echo "2. 检查服务文件..."
echo "----------------------------------------"

if [ -f "/www/wwwroot/eivie/app/service/AiModelService.php" ]; then
    echo "✓ AiModelService.php 服务文件存在"
    # 检查核心方法
    if grep -q "public static function call" /www/wwwroot/eivie/app/service/AiModelService.php; then
        echo "  ✓ call() 方法存在"
    fi
    if grep -q "private static function getAvailableApiConfig" /www/wwwroot/eivie/app/service/AiModelService.php; then
        echo "  ✓ getAvailableApiConfig() 方法存在"
    fi
    if grep -q "public static function testConnection" /www/wwwroot/eivie/app/service/AiModelService.php; then
        echo "  ✓ testConnection() 方法存在"
    fi
else
    echo "✗ AiModelService.php 服务文件不存在"
fi

echo ""
echo "3. 检查控制器方法..."
echo "----------------------------------------"

if grep -q "public function model_category_list" /www/wwwroot/eivie/app/controller/AiTravelPhoto.php; then
    echo "✓ model_category_list() 方法存在"
fi

if grep -q "public function model_config_list" /www/wwwroot/eivie/app/controller/AiTravelPhoto.php; then
    echo "✓ model_config_list() 方法存在"
fi

if grep -q "public function model_usage_stats" /www/wwwroot/eivie/app/controller/AiTravelPhoto.php; then
    echo "✓ model_usage_stats() 方法存在"
fi

if grep -q "public function model_config_test" /www/wwwroot/eivie/app/controller/AiTravelPhoto.php; then
    echo "✓ model_config_test() 方法存在"
fi

echo ""
echo "4. 检查视图文件..."
echo "----------------------------------------"

VIEW_DIR="/www/wwwroot/eivie/app/view/ai_travel_photo"
VIEW_FILES=("model_category_list.html" "model_category_edit.html" "model_config_list.html" "model_config_edit.html" "model_usage_stats.html")

for file in "${VIEW_FILES[@]}"; do
    if [ -f "$VIEW_DIR/$file" ]; then
        echo "✓ $file 存在"
    else
        echo "✗ $file 不存在"
    fi
done

echo ""
echo "5. 检查菜单配置..."
echo "----------------------------------------"

if grep -q "模型设置" /www/wwwroot/eivie/app/common/Menu.php; then
    echo "✓ 菜单配置已添加"
    if grep -q "model_category_list" /www/wwwroot/eivie/app/common/Menu.php; then
        echo "  ✓ 模型分类菜单项存在"
    fi
    if grep -q "model_config_list" /www/wwwroot/eivie/app/common/Menu.php; then
        echo "  ✓ API配置菜单项存在"
    fi
    if grep -q "model_usage_stats" /www/wwwroot/eivie/app/common/Menu.php; then
        echo "  ✓ 调用统计菜单项存在"
    fi
else
    echo "✗ 菜单配置未添加"
fi

echo ""
echo "6. 检查Redis连接..."
echo "----------------------------------------"

if command -v redis-cli &> /dev/null; then
    if redis-cli ping | grep -q "PONG"; then
        echo "✓ Redis服务正常运行"
    else
        echo "✗ Redis服务未响应"
    fi
else
    echo "⚠ redis-cli 未安装，无法测试Redis连接"
fi

echo ""
echo "=========================================="
echo "测试完成！"
echo "=========================================="
echo ""
echo "下一步操作建议："
echo "1. 访问后台菜单：AI旅拍 > 模型设置"
echo "2. 查看模型分类列表，确认系统预置分类已创建"
echo "3. 添加一个API配置进行测试"
echo "4. 使用'测试连通性'功能验证API配置"
echo "5. 查看调用统计页面"
echo ""
