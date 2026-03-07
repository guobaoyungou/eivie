#!/bin/bash

# AI旅拍功能部署脚本
# @author AI旅拍开发团队
# @date 2026-01-19

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 日志函数
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 检查函数
check_command() {
    if ! command -v $1 >/dev/null 2>&1; then
        log_error "$1 未安装"
        exit 1
    fi
}

# 检查PHP版本
check_php_version() {
    log_info "检查PHP版本..."
    PHP_VERSION=$(php -v | head -n 1 | awk '{print $2}')
    REQUIRED_VERSION="7.4.0"
    
    if [ "$(printf '%s\n' "$PHP_VERSION" "$REQUIRED_VERSION")" | sort -V | head -n 1)" != "$REQUIRED_VERSION" ]; then
        log_warning "PHP版本: $PHP_VERSION (要求: >= $REQUIRED_VERSION)"
    else
        log_success "PHP版本: $PHP_VERSION ✓"
    fi
}

# 检查MySQL连接
check_mysql() {
    log_info "检查MySQL连接..."
    DB_HOST="localhost"
    DB_USER="guobaoyungou_cn"
    DB_PASS="5ArfhRr9xzyScrF5"
    DB_NAME="guobaoyungou_cn"
    
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT 1;" 2>/dev/null; then
        log_success "MySQL连接成功 ✓"
    else
        log_error "MySQL连接失败"
        exit 1
    fi
}

# 检查Redis连接
check_redis() {
    log_info "检查Redis连接..."
    if redis-cli ping 2>/dev/null; then
        log_success "Redis连接成功 ✓"
    else
        log_error "Redis连接失败"
        exit 1
    fi
}

# 检查FFmpeg
check_ffmpeg() {
    log_info "检查FFmpeg..."
    if command -v ffmpeg >/dev/null 2>&1; then
        FFMPEG_VERSION=$(ffmpeg -version | head -n 1 | awk '{print $3}')
        log_success "FFmpeg已安装: $FFMPEG_VERSION ✓"
    else
        log_warning "FFmpeg未安装，视频处理功能将不可用"
    fi
}

# 检查目录权限
check_permissions() {
    log_info "检查目录权限..."
    
    UPLOAD_DIR="/www/wwwroot/eivie/upload/aivideo"
    
    if [ ! -d "$UPLOAD_DIR" ]; then
        log_warning "上传目录不存在: $UPLOAD_DIR"
        mkdir -p "$UPLOAD_DIR"
        log_info "创建上传目录: $UPLOAD_DIR"
    fi
    
    # 检查权限
    PERMS=$(stat -c "%a" "$UPLOAD_DIR")
    if [ "$PERMS" != "755" ]; then
        log_warning "目录权限不正确: $PERMS (建议: 755)"
        chmod -R 755 "$UPLOAD_DIR"
        log_info "修复目录权限为755"
    fi
    
    # 检查所有者
    OWNER=$(stat -c "%U" "$UPLOAD_DIR")
    if [ "$OWNER" != "www-data" ]; then
        log_warning "目录所有者不正确: $OWNER (建议: www-data:www-data)"
        chown -R www-data:www-data "$UPLOAD_DIR"
        log_info "修复目录所有者为www-data:www-data"
    fi
    
    log_success "目录权限检查完成 ✓"
}

# 创建数据库表
create_tables() {
    log_info "创建数据库表..."
    
    SQL_FILE="/www/wwwroot/eivie/keling/aivideo_tables.sql"
    
    if [ ! -f "$SQL_FILE" ]; then
        log_error "SQL文件不存在: $SQL_FILE"
        exit 1
    fi
    
    mysql -h localhost -u guobaoyungou_cn -p5ArfhRr9xzyScrF5 guobaoyungou_cn < "$SQL_FILE" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        log_success "数据库表创建成功 ✓"
    else
        log_error "数据库表创建失败"
        exit 1
    fi
}

# 清理缓存
clear_cache() {
    log_info "清理缓存..."
    
    # 清理ThinkPHP缓存
    php think clear
    
    # 清理Redis缓存
    redis-cli FLUSHDB
    
    log_success "缓存清理完成 ✓"
}

# 安装Composer依赖
install_dependencies() {
    log_info "安装Composer依赖..."
    
    cd /www/wwwroot/eivie
    
    # 检查composer.json是否存在
    if [ ! -f "composer.json" ]; then
        log_error "composer.json不存在"
        exit 1
    fi
    
    # 安装依赖
    composer install --no-dev --optimize-autoloader
    
    if [ $? -eq 0 ]; then
        log_success "Composer依赖安装成功 ✓"
    else
        log_error "Composer依赖安装失败"
        exit 1
    fi
}

# 配置定时任务
setup_cron() {
    log_info "配置定时任务..."
    
    CRON_FILE="/tmp/aivideo_cron"
    CRON_COMMAND="*/5 * * * * php /www/wwwroot/eivie/think aivideo:cron"
    
    # 添加定时任务
    (crontab -l 2>/dev/null; echo "$CRON_COMMAND"; crontab - 2>/dev/null) | crontab -
    
    if [ $? -eq 0 ]; then
        log_success "定时任务配置成功 ✓"
        log_info "定时任务: $CRON_COMMAND"
    else
        log_error "定时任务配置失败"
        exit 1
    fi
}

# 配置Nginx
setup_nginx() {
    log_info "配置Nginx..."
    
    NGINX_CONF="/etc/nginx/conf.d/aivideo.conf"
    
    # 创建Nginx配置
    cat > "$NGINX_CONF" << 'EOF'
server {
    listen 80;
    server_name localhost;
    root /www/wwwroot/eivie/public;
    
    location / {
        index index.php index.html;
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location /api/aivideo/ {
        proxy_pass http://127.0.0.1:9000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
    
    location /admin_aivideo/ {
        proxy_pass http://127.0.0.1:9000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF
    
    # 测试Nginx配置
    nginx -t 2>/dev/null
    
    if [ $? -eq 0 ]; then
        log_success "Nginx配置成功 ✓"
    else
        log_error "Nginx配置失败"
        exit 1
    fi
    
    # 重启Nginx
    systemctl reload nginx
    
    if [ $? -eq 0 ]; then
        log_success "Nginx重启成功 ✓"
    else
        log_error "Nginx重启失败"
        exit 1
    fi
}

# 验证部署
verify_deployment() {
    log_info "验证部署..."
    
    # 检查数据库表
    TABLE_COUNT=$(mysql -h localhost -u guobaoyungou_cn -p5ArfhRr9xzyScrF5 guobaoyungou_cn -e "SHOW TABLES LIKE 'ddwx_aivideo_%';" 2>/dev/null | grep -c "ddwx_aivideo_")
    
    if [ "$TABLE_COUNT" -eq 7 ]; then
        log_success "数据库表验证: 7个表 ✓"
    else
        log_warning "数据库表验证: $TABLE_COUNT 个表 (期望: 7个表)"
    fi
    
    # 检查API接口
    log_info "测试API接口..."
    API_URL="http://localhost/api/aivideo/config_list"
    RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" "$API_URL")
    
    if [ "$RESPONSE" == "200" ]; then
        log_success "API接口响应正常 ✓"
    else
        log_warning "API接口响应异常: HTTP $RESPONSE"
    fi
}

# 主函数
main() {
    echo "======================================"
    echo "   AI旅拍功能部署脚本"
    echo "======================================"
    echo ""
    
    # 检查环境
    check_command "php"
    check_command "mysql"
    check_command "redis-cli"
    
    check_php_version
    check_mysql
    check_redis
    check_ffmpeg
    check_permissions
    
    echo ""
    
    # 询问是否继续
    read -p "是否继续部署? (y/n): " -n -r
    if [ "$REPLY" != "y" ]; then
        log_info "部署已取消"
        exit 0
    fi
    
    echo ""
    
    # 执行部署步骤
    create_tables
    clear_cache
    install_dependencies
    setup_cron
    setup_nginx
    
    echo ""
    
    # 验证部署
    verify_deployment
    
    echo ""
    echo "======================================"
    log_success "部署完成!"
    echo "======================================"
    echo ""
    log_info "后续步骤:"
    echo "1. 配置微信支付和支付宝支付"
    echo "2. 配置可灵AI账号"
    echo "3. 配置监控路径"
    echo "4. 启动商家监控程序"
    echo "5. 执行测试用例"
    echo "6. 配置监控和日志系统"
    echo ""
}

# 执行主函数
main
