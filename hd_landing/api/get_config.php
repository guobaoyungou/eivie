<?php
// API接口：获取系统配置信息
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // 引入配置文件
    require_once dirname(__FILE__) . '/../common/settings.php';
    require_once dirname(__FILE__) . '/../models/system_config_model.php';
    
    // 创建系统配置模型
    $systemConfigModel = new System_Config_model();
    
    // 获取所有配置
    $allConfig = $systemConfigModel->getAll();
    
    // 提取需要的配置项
    $config = array(
        'company_name' => isset($allConfig['company_name']) ? $allConfig['company_name']['configvalue'] : '艺为互动科技有限公司',
        'company_phone' => isset($allConfig['company_phone']) ? $allConfig['company_phone']['configvalue'] : '400-123-4567',
        'company_wechat' => isset($allConfig['company_wechat']) ? $allConfig['company_wechat']['configvalue'] : 'eivie_hd',
        'company_email' => isset($allConfig['company_email']) ? $allConfig['company_email']['configvalue'] : 'support@eivie.cn',
        'icp_record' => isset($allConfig['icp_record']) ? $allConfig['icp_record']['configvalue'] : '京ICP备12345678号-1',
        'police_record' => isset($allConfig['police_record']) ? $allConfig['police_record']['configvalue'] : '京公网安备11010502030123号',
        'copyright' => isset($allConfig['copyright']) ? $allConfig['copyright']['configvalue'] : '2025-2026 艺为互动科技有限公司',
        'domain' => isset($allConfig['domain']) ? $allConfig['domain']['configvalue'] : 'wxhd.eivie.cn'
    );
    
    // 返回配置信息
    echo json_encode(array(
        'code' => 1,
        'data' => $config,
        'msg' => '获取配置成功'
    ));
    
} catch (Exception $e) {
    // 返回错误信息
    echo json_encode(array(
        'code' => 0,
        'data' => array(),
        'msg' => '获取配置失败: ' . $e->getMessage()
    ));
}