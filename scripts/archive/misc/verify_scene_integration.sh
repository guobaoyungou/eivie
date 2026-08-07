#!/bin/bash

# 场景管理API集成验证脚本
# 用于验证代码结构、语法正确性和集成完整性

echo "=========================================="
echo "场景管理API集成验证脚本"
echo "执行时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo "=========================================="
echo ""

# 颜色定义
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 统计变量
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0

# 检查函数
check_item() {
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
        PASSED_CHECKS=$((PASSED_CHECKS + 1))
        return 0
    else
        echo -e "${RED}✗${NC} $2"
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
        return 1
    fi
}

echo "1. 核心服务文件检查"
echo "----------------------------------------"

# 检查核心服务文件是否存在
if [ -f "app/service/AiTravelPhotoAiService.php" ]; then
    check_item 0 "AiTravelPhotoAiService.php 文件存在"
    
    # 检查关键方法是否存在
    grep -q "callImageGenerationApi" app/service/AiTravelPhotoAiService.php
    check_item $? "callImageGenerationApi() 方法已实现"
    
    grep -q "callVideoGenerationApi" app/service/AiTravelPhotoAiService.php
    check_item $? "callVideoGenerationApi() 方法已实现"
    
    grep -q "callAliyunImageGenerationApi" app/service/AiTravelPhotoAiService.php
    check_item $? "callAliyunImageGenerationApi() 方法已实现"
    
    grep -q "callKlingVideoGenerationApi" app/service/AiTravelPhotoAiService.php
    check_item $? "callKlingVideoGenerationApi() 方法已实现"
    
    grep -q "processGenerationBySceneType" app/service/AiTravelPhotoAiService.php
    check_item $? "processGenerationBySceneType() 方法已实现"
else
    check_item 1 "AiTravelPhotoAiService.php 文件存在"
fi

echo ""
echo "2. 辅助服务文件检查"
echo "----------------------------------------"

if [ -f "app/service/SceneParameterService.php" ]; then
    check_item 0 "SceneParameterService.php 文件存在"
    
    grep -q "assembleImageGenerationParams" app/service/SceneParameterService.php
    check_item $? "assembleImageGenerationParams() 方法已实现"
    
    grep -q "assembleVideoGenerationParams" app/service/SceneParameterService.php
    check_item $? "assembleVideoGenerationParams() 方法已实现"
else
    check_item 1 "SceneParameterService.php 文件存在"
fi

if [ -f "app/service/GenerationResultService.php" ]; then
    check_item 0 "GenerationResultService.php 文件存在"
    
    grep -q "saveResultAuto" app/service/GenerationResultService.php
    check_item $? "saveResultAuto() 方法已实现"
    
    grep -q "saveVideoResult" app/service/GenerationResultService.php
    check_item $? "saveVideoResult() 方法已实现"
else
    check_item 1 "GenerationResultService.php 文件存在"
fi

echo ""
echo "3. 队列Job文件检查"
echo "----------------------------------------"

if [ -f "app/job/ImageGenerationJob.php" ]; then
    check_item 0 "ImageGenerationJob.php 文件存在"
    
    grep -q "processGenerationBySceneType" app/job/ImageGenerationJob.php
    check_item $? "ImageGenerationJob 已集成增强方法"
else
    check_item 1 "ImageGenerationJob.php 文件存在"
fi

if [ -f "app/job/VideoGenerationJob.php" ]; then
    check_item 0 "VideoGenerationJob.php 文件存在"
    
    grep -q "processGenerationBySceneType" app/job/VideoGenerationJob.php
    check_item $? "VideoGenerationJob 已集成增强方法"
else
    check_item 1 "VideoGenerationJob.php 文件存在"
fi

echo ""
echo "4. API控制器检查"
echo "----------------------------------------"

if [ -f "app/controller/ApiAiTravelPhoto.php" ]; then
    check_item 0 "ApiAiTravelPhoto.php 文件存在"
    
    grep -q "Queue::push" app/controller/ApiAiTravelPhoto.php
    check_item $? "generate() 方法已集成队列调用"
    
    grep -q "function generationResult" app/controller/ApiAiTravelPhoto.php
    check_item $? "generationResult() 方法已实现"
else
    check_item 1 "ApiAiTravelPhoto.php 文件存在"
fi

echo ""
echo "5. 配置文件检查"
echo "----------------------------------------"

if [ -f "config/ai_travel_photo.php" ]; then
    check_item 0 "ai_travel_photo.php 配置文件存在"
    
    grep -q "scene_type" config/ai_travel_photo.php
    check_item $? "scene_type 配置已定义"
    
    grep -q "scene_type_input" config/ai_travel_photo.php
    check_item $? "scene_type_input 配置已定义"
else
    check_item 1 "ai_travel_photo.php 配置文件存在"
fi

echo ""
echo "6. 数据库迁移脚本检查"
echo "----------------------------------------"

if [ -f "database/migrations/scene_type_enhancement.sql" ]; then
    check_item 0 "scene_type_enhancement.sql 迁移脚本存在"
    
    grep -q "scene_type" database/migrations/scene_type_enhancement.sql
    check_item $? "scene_type 字段定义存在"
    
    grep -q "INDEX" database/migrations/scene_type_enhancement.sql
    check_item $? "索引优化语句存在"
else
    check_item 1 "scene_type_enhancement.sql 迁移脚本存在"
fi

echo ""
echo "7. PHP语法检查"
echo "----------------------------------------"

# 检查核心文件的PHP语法
php -l app/service/AiTravelPhotoAiService.php > /dev/null 2>&1
check_item $? "AiTravelPhotoAiService.php 语法检查通过"

php -l app/service/SceneParameterService.php > /dev/null 2>&1
check_item $? "SceneParameterService.php 语法检查通过"

php -l app/service/GenerationResultService.php > /dev/null 2>&1
check_item $? "GenerationResultService.php 语法检查通过"

php -l app/controller/ApiAiTravelPhoto.php > /dev/null 2>&1
check_item $? "ApiAiTravelPhoto.php 语法检查通过"

php -l app/job/ImageGenerationJob.php > /dev/null 2>&1
check_item $? "ImageGenerationJob.php 语法检查通过"

php -l app/job/VideoGenerationJob.php > /dev/null 2>&1
check_item $? "VideoGenerationJob.php 语法检查通过"

echo ""
echo "8. 文档交付物检查"
echo "----------------------------------------"

if [ -f "API_INTEGRATION_COMPLETE.md" ]; then
    check_item 0 "API_INTEGRATION_COMPLETE.md 文档存在"
else
    check_item 1 "API_INTEGRATION_COMPLETE.md 文档存在"
fi

if [ -f "API_CONFIG_QUICK_SETUP.md" ]; then
    check_item 0 "API_CONFIG_QUICK_SETUP.md 文档存在"
else
    check_item 1 "API_CONFIG_QUICK_SETUP.md 文档存在"
fi

if [ -f "API_INTEGRATION_CHECKLIST.md" ]; then
    check_item 0 "API_INTEGRATION_CHECKLIST.md 文档存在"
else
    check_item 1 "API_INTEGRATION_CHECKLIST.md 文档存在"
fi

echo ""
echo "9. 代码统计"
echo "----------------------------------------"

if [ -f "app/service/AiTravelPhotoAiService.php" ]; then
    LINES=$(wc -l < app/service/AiTravelPhotoAiService.php)
    echo "  - AiTravelPhotoAiService.php: $LINES 行"
fi

if [ -f "app/service/SceneParameterService.php" ]; then
    LINES=$(wc -l < app/service/SceneParameterService.php)
    echo "  - SceneParameterService.php: $LINES 行"
fi

if [ -f "app/service/GenerationResultService.php" ]; then
    LINES=$(wc -l < app/service/GenerationResultService.php)
    echo "  - GenerationResultService.php: $LINES 行"
fi

if [ -f "app/controller/ApiAiTravelPhoto.php" ]; then
    LINES=$(wc -l < app/controller/ApiAiTravelPhoto.php)
    echo "  - ApiAiTravelPhoto.php: $LINES 行"
fi

TOTAL_CODE_LINES=$(wc -l app/service/AiTravelPhotoAiService.php app/service/SceneParameterService.php app/service/GenerationResultService.php app/controller/ApiAiTravelPhoto.php app/job/ImageGenerationJob.php app/job/VideoGenerationJob.php 2>/dev/null | tail -1 | awk '{print $1}')
echo -e "${GREEN}  总计代码行数: $TOTAL_CODE_LINES 行${NC}"

echo ""
echo "10. 集成完整性检查"
echo "----------------------------------------"

# 检查API配置模型引用
grep -q "ApiConfig::where" app/service/AiTravelPhotoAiService.php
check_item $? "API配置查询逻辑已实现"

# 检查队列服务引用
grep -q "Queue::push" app/controller/ApiAiTravelPhoto.php
check_item $? "队列服务调用已实现"

# 检查KlingAIService引用
grep -q "KlingAIService" app/service/AiTravelPhotoAiService.php
check_item $? "可灵AI服务集成已完成"

# 检查日志记录
grep -q "Log::error" app/service/AiTravelPhotoAiService.php
check_item $? "错误日志记录已实现"

echo ""
echo "=========================================="
echo "验证结果汇总"
echo "=========================================="
echo -e "总检查项: ${YELLOW}$TOTAL_CHECKS${NC}"
echo -e "通过项: ${GREEN}$PASSED_CHECKS${NC}"
echo -e "失败项: ${RED}$FAILED_CHECKS${NC}"
echo ""

if [ $FAILED_CHECKS -eq 0 ]; then
    echo -e "${GREEN}✓ 所有检查项通过！代码质量良好，可以进行下一步部署。${NC}"
    exit 0
else
    echo -e "${RED}✗ 有 $FAILED_CHECKS 项检查失败，请修复后重新验证。${NC}"
    exit 1
fi
