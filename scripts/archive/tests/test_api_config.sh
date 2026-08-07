#!/bin/bash
# API配置功能验证测试脚本
# 使用方法: bash test_api_config.sh

echo "=========================================="
echo "API配置功能验证测试"
echo "=========================================="
echo ""

# 颜色定义
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 测试计数器
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# 测试函数
test_item() {
    TOTAL_TESTS=$((TOTAL_TESTS + 1))
    local test_name="$1"
    local test_command="$2"
    
    echo -n "[$TOTAL_TESTS] 测试: $test_name ... "
    
    if eval "$test_command" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ 通过${NC}"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}✗ 失败${NC}"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

# 切换到项目目录
cd /www/wwwroot/eivie

echo "=========================================="
echo "第一部分: 数据库表验证"
echo "=========================================="
echo ""

# 测试数据库表是否存在
test_item "API配置表存在性" "php -r 'require \"vendor/autoload.php\"; \$app = require \"app/AppService.php\"; \$exists = think\facade\Db::query(\"SHOW TABLES LIKE \\\"ddwx_api_config\\\"\"); exit(count(\$exists) > 0 ? 0 : 1);'"

test_item "API计费规则表存在性" "php -r 'require \"vendor/autoload.php\"; \$app = require \"app/AppService.php\"; \$exists = think\facade\Db::query(\"SHOW TABLES LIKE \\\"ddwx_api_pricing\\\"\"); exit(count(\$exists) > 0 ? 0 : 1);'"

test_item "API调用日志表存在性" "php -r 'require \"vendor/autoload.php\"; \$app = require \"app/AppService.php\"; \$exists = think\facade\Db::query(\"SHOW TABLES LIKE \\\"ddwx_api_call_log\\\"\"); exit(count(\$exists) > 0 ? 0 : 1);'"

test_item "API使用授权表存在性" "php -r 'require \"vendor/autoload.php\"; \$app = require \"app/AppService.php\"; \$exists = think\facade\Db::query(\"SHOW TABLES LIKE \\\"ddwx_api_authorization\\\"\"); exit(count(\$exists) > 0 ? 0 : 1);'"

echo ""
echo "=========================================="
echo "第二部分: 模型文件验证"
echo "=========================================="
echo ""

test_item "ApiConfig模型文件" "[ -f app/model/ApiConfig.php ]"
test_item "ApiPricing模型文件" "[ -f app/model/ApiPricing.php ]"
test_item "ApiCallLog模型文件" "[ -f app/model/ApiCallLog.php ]"
test_item "ApiAuthorization模型文件" "[ -f app/model/ApiAuthorization.php ]"

echo ""
echo "=========================================="
echo "第三部分: 服务文件验证"
echo "=========================================="
echo ""

test_item "ApiConfigService服务文件" "[ -f app/service/ApiConfigService.php ]"
test_item "ApiPermissionService服务文件" "[ -f app/service/ApiPermissionService.php ]"
test_item "ApiPricingService服务文件" "[ -f app/service/ApiPricingService.php ]"
test_item "ApiCallService服务文件" "[ -f app/service/ApiCallService.php ]"
test_item "ApiBalanceService服务文件" "[ -f app/service/ApiBalanceService.php ]"

echo ""
echo "=========================================="
echo "第四部分: PHP语法检查"
echo "=========================================="
echo ""

test_item "ApiConfig模型语法" "php -l app/model/ApiConfig.php"
test_item "ApiPricing模型语法" "php -l app/model/ApiPricing.php"
test_item "ApiCallLog模型语法" "php -l app/model/ApiCallLog.php "
test_item "ApiAuthorization模型语法" "php -l app/model/ApiAuthorization.php"
test_item "ApiConfigService服务语法" "php -l app/service/ApiConfigService.php"
test_item "ApiPermissionService服务语法" "php -l app/service/ApiPermissionService.php"
test_item "ApiPricingService服务语法" "php -l app/service/ApiPricingService.php"
test_item "ApiCallService服务语法" "php -l app/service/ApiCallService.php"
test_item "ApiBalanceService服务语法" "php -l app/service/ApiBalanceService.php"

echo ""
echo "=========================================="
echo "第五部分: 类加载验证"
echo "=========================================="
echo ""

test_item "ApiConfig类可加载" "php -r 'require \"vendor/autoload.php\"; \$app = require \"app/AppService.php\"; class_exists(\"app\\\\model\\\\ApiConfig\") or exit(1);'"

test_item "ApiPricing类可加载" "php -r 'require \"vendor/autoload.php\"; \$app = require \"app/AppService.php\"; class_exists(\"app\\\\model\\\\ApiPricing\") or exit(1);'"

test_item "ApiCallLog类可加载" "php -r 'require \"vendor/autoload.php\"; \$app = require \"app/AppService.php\"; class_exists(\"app\\\\model\\\\ApiCallLog\") or exit(1);'"

test_item "ApiAuthorization类可加载" "php -r 'require \"vendor/autoload.php\"; \$app = require \"app/AppService.php\"; class_exists(\"app\\\\model\\\\ApiAuthorization\") or exit(1);'"

test_item "ApiConfigService类可加载" "php -r 'require \"vendor/autoload.php\"; \$app = require \"app/AppService.php\"; class_exists(\"app\\\\service\\\\ApiConfigService\") or exit(1);'"

echo ""
echo "=========================================="
echo "测试结果汇总"
echo "=========================================="
echo ""

echo "总测试数: $TOTAL_TESTS"
echo -e "${GREEN}通过: $PASSED_TESTS${NC}"
echo -e "${RED}失败: $FAILED_TESTS${NC}"

if [ $FAILED_TESTS -eq 0 ]; then
    echo ""
    echo -e "${GREEN}=========================================="
    echo "✓ 所有测试通过!"
    echo "==========================================${NC}"
    exit 0
else
    echo ""
    echo -e "${RED}=========================================="
    echo "✗ 有 $FAILED_TESTS 个测试失败"
    echo "==========================================${NC}"
    exit 1
fi
