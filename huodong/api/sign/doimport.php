<?php
/**
 * 签到名单导入处理入口
 * 将CSV/Excel数据导入到ddwx_hd_participant表
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once dirname(__FILE__) . '/../../common/db.class.php';
require_once dirname(__FILE__) . '/../../common/function.php';
require_once dirname(__FILE__) . '/../../common/session_helper.php';

// 检查登录状态
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != true) {
    echo json_encode(['code' => -100, 'msg' => '您的登录已经过期，请重新登录']);
    exit();
}

// 获取活动ID
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
if ($activity_id <= 0) {
    echo json_encode(['code' => -1, 'msg' => '活动ID不能为空']);
    exit();
}

// 获取当前用户的aid和bid
$aid = isset($_SESSION['aid']) ? intval($_SESSION['aid']) : 0;
$bid = isset($_SESSION['bid']) ? intval($_SESSION['bid']) : 0;

// 如果session中没有aid/bid，从活动信息中获取
if ($aid == 0 || $bid == 0) {
    $db = new db();
    $activity = $db->find("SELECT aid, bid FROM ddwx_hd_activity WHERE id={$activity_id}");
    if ($activity) {
        $aid = intval($activity['aid']);
        $bid = intval($activity['bid']);
    }
}

// 检查是否有文件上传
if (!isset($_FILES['file']) || $_FILES['file']['error'] != UPLOAD_ERR_OK) {
    $error_msg = '请上传文件';
    if (isset($_FILES['file'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_msg = '文件大小超过服务器限制';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_msg = '文件大小超过表单限制';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_msg = '文件上传不完整';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_msg = '请选择要上传的文件';
                break;
        }
    }
    echo json_encode(['code' => -2, 'msg' => $error_msg]);
    exit();
}

$file = $_FILES['file'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// 检查文件类型
if (!in_array($file_ext, ['csv', 'xlsx', 'xls'])) {
    echo json_encode(['code' => -3, 'msg' => '只支持CSV、XLSX、XLS格式的文件']);
    exit();
}

// 读取文件内容
$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    echo json_encode(['code' => -4, 'msg' => '无法读取文件']);
    exit();
}

// 读取表头
$headers = fgetcsv($handle);
if (!$headers) {
    echo json_encode(['code' => -5, 'msg' => '文件格式不正确']);
    exit();
}

// 标准化表头
$headers = array_map(function($h) {
    return trim(strtolower($h));
}, $headers);

// 映射字段（支持中英文）
$field_map = [
    '姓名' => 'signname',
    'name' => 'signname',
    '手机号' => 'phone',
    'phone' => 'phone',
    '公司' => 'company',
    'company' => 'company',
    '职位' => 'position',
    'position' => 'position',
    '员工号' => 'employee_no',
    'employee_no' => 'employee_no'
];

// 统计
$success_count = 0;
$fail_count = 0;
$errors = [];

// 准备数据库连接
$db = new db();
$time = time();

// 逐行读取数据
$row_num = 1; // 从第2行开始（第1行是表头）
while (($data = fgetcsv($handle)) !== false) {
    $row_num++;
    
    // 跳过空行
    if (empty(array_filter($data))) {
        continue;
    }
    
    // 构建数据数组
    $row_data = [];
    foreach ($headers as $index => $header) {
        $field_name = isset($field_map[$header]) ? $field_map[$header] : $header;
        $row_data[$field_name] = isset($data[$index]) ? trim($data[$index]) : '';
    }
    
    // 验证必填字段
    if (empty($row_data['phone'])) {
        $fail_count++;
        $errors[] = "第{$row_num}行：手机号不能为空";
        continue;
    }
    
    // 检查手机号是否已存在
    $existing = $db->find("SELECT id FROM ddwx_hd_participant WHERE activity_id={$activity_id} AND phone='" . addslashes($row_data['phone']) . "'");
    if ($existing) {
        // 更新现有记录
        $update_data = [
            'signname' => addslashes($row_data['signname'] ?? ''),
            'phone' => addslashes($row_data['phone']),
            'updatetime' => $time
        ];
        
        // 可选字段
        if (!empty($row_data['company'])) {
            $update_data['company'] = addslashes($row_data['company']);
        }
        if (!empty($row_data['position'])) {
            $update_data['position'] = addslashes($row_data['position']);
        }
        if (!empty($row_data['employee_no'])) {
            $update_data['employee_no'] = addslashes($row_data['employee_no']);
        }
        
        $where = "id=" . intval($existing['id']);
        $result = $db->update('ddwx_hd_participant', $update_data, $where);
        
        if ($result) {
            $success_count++;
        } else {
            $fail_count++;
            $errors[] = "第{$row_num}行：更新失败";
        }
    } else {
        // 插入新记录 - 同时写入aid、bid、activity_id
        $insert_data = [
            'aid' => $aid,
            'bid' => $bid,
            'activity_id' => $activity_id,
            'signname' => addslashes($row_data['signname'] ?? ''),
            'phone' => addslashes($row_data['phone']),
            'flag' => 1, // 未签到
            'status' => 1, // 正常
            'createtime' => $time
        ];
        
        // 可选字段
        if (!empty($row_data['company'])) {
            $insert_data['company'] = addslashes($row_data['company']);
        }
        if (!empty($row_data['position'])) {
            $insert_data['position'] = addslashes($row_data['position']);
        }
        if (!empty($row_data['employee_no'])) {
            $insert_data['employee_no'] = addslashes($row_data['employee_no']);
        }
        
        $result = $db->insert('ddwx_hd_participant', $insert_data);
        
        if ($result) {
            $success_count++;
        } else {
            $fail_count++;
            $errors[] = "第{$row_num}行：插入失败";
        }
    }
}

fclose($handle);

// 返回结果
echo json_encode([
    'code' => 0,
    'msg' => '导入完成',
    'data' => [
        'success' => $success_count,
        'fail' => $fail_count,
        'errors' => $errors,
        'aid' => $aid,
        'bid' => $bid,
        'activity_id' => $activity_id
    ]
]);
