#!/bin/bash
# API配置功能数据库表创建脚本
# 使用方法: bash migrate_api_config.sh

echo "=========================================="
echo "API配置功能数据库迁移脚本"
echo "=========================================="
echo ""

# 获取脚本所在目录
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# 检查SQL文件是否存在
if [ ! -f "database/migrations/api_config_tables.sql" ]; then
    echo "✗ 错误: 找不到SQL文件 database/migrations/api_config_tables.sql"
    exit 1
fi

echo "正在读取数据库配置..."
echo ""

# 使用think命令行工具执行迁移
echo "正在执行数据库迁移..."
php think sql:execute database/migrations/api_config_tables.sql

# 如果think命令不可用，尝试直接使用MySQL
if [ $? -ne 0 ]; then
    echo ""
    echo "尝试使用MySQL命令行工具..."
    echo "请输入数据库连接信息:"
    read -p "数据库主机 [127.0.0.1]: " DB_HOST
    DB_HOST=${DB_HOST:-127.0.0.1}
    
    read -p "数据库端口 [3306]: " DB_PORT
    DB_PORT=${DB_PORT:-3306}
    
    read -p "数据库名: " DB_NAME
    
    read -p "数据库用户名 [root]: " DB_USER
    DB_USER=${DB_USER:-root}
    
    read -sp "数据库密码: " DB_PASS
    echo ""
    
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/migrations/api_config_tables.sql
    
    if [ $? -eq 0 ]; then
        echo "✓ 数据库迁移成功!"
    else
        echo "✗ 数据库迁移失败!"
        exit 1
    fi
fi

echo ""
echo "=========================================="
echo "验证表创建结果..."
echo "=========================================="

# 验证表是否创建成功
php -r "
require 'vendor/autoload.php';
\$app = require 'app/AppService.php';

try {
    \$db = \think\facade\Db::connect();
    \$tables = ['ddwx_api_config', 'ddwx_api_pricing', 'ddwx_api_call_log', 'ddwx_api_authorization'];
    
    echo \"\n检查表创建状态:\n\n\";
    \$allSuccess = true;
    
    foreach (\$tables as \$table) {
        \$exists = \$db->query(\"SHOW TABLES LIKE '\$table'\");
        if (count(\$exists) > 0) {
            echo \"✓ \$table - 创建成功\n\";
        } else {
            echo \"✗ \$table - 创建失败\n\";
            \$allSuccess = false;
        }
    }
    
    echo \"\n\";
    
    if (\$allSuccess) {
        echo \"========================================\n\";
        echo \"✓ 所有表创建成功!\n\";
        echo \"========================================\n\";
        exit(0);
    } else {
        echo \"========================================\n\";
        echo \"✗ 部分表创建失败，请检查错误信息\n\";
        echo \"========================================\n\";
        exit(1);
    }
} catch (Exception \$e) {
    echo \"✗ 验证失败: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"

echo ""
echo "迁移完成!"
