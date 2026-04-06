<?php
/**
 * 白名单管理API
 * 处理白名单的增删改查操作
 */
header("Content-type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once(dirname(__FILE__) . '/../../common/db.class.php');
require_once(dirname(__FILE__) . '/../../common/session_helper.php');
require_once(dirname(__FILE__) . '/../../common/function.php');

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

// 检查是否存在白名单表，如果不存在则创建
$db = new db();
$table_exists = $db->find("SHOW TABLES LIKE 'ddwx_hd_whitelist'");
if (!$table_exists) {
    $create_table_sql = "
        CREATE TABLE `ddwx_hd_whitelist` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `aid` int(11) NOT NULL,
            `bid` int(11) NOT NULL,
            `activity_id` int(11) NOT NULL,
            `name` varchar(50) NOT NULL,
            `phone` varchar(20) NOT NULL,
            `company` varchar(100) DEFAULT '',
            `position` varchar(50) DEFAULT '',
            `added_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_activity_phone` (`activity_id`, `phone`),
            INDEX `idx_activity` (`activity_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $db->query($create_table_sql);
}

// 处理不同的请求方法
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // 获取白名单列表
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        $offset = ($page - 1) * $limit;
        $where = "WHERE activity_id={$activity_id}";
        
        if ($search) {
            $where .= " AND (name LIKE '%" . addslashes($search) . "%' OR phone LIKE '%" . addslashes($search) . "%')";
        }
        
        $total = $db->findOne("SELECT COUNT(*) as total FROM ddwx_hd_whitelist {$where}");
        $total = isset($total['total']) ? $total['total'] : 0;
        
        $list = $db->select("SELECT * FROM ddwx_hd_whitelist {$where} ORDER BY id DESC LIMIT {$offset}, {$limit}");
        
        echo json_encode([
            'code' => 0,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]
        ]);
        break;
        
    case 'POST':
        // 添加或编辑白名单
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $company = isset($_POST['company']) ? trim($_POST['company']) : '';
        $position = isset($_POST['position']) ? trim($_POST['position']) : '';
        
        if (!$name || !$phone) {
            echo json_encode(['code' => -2, 'msg' => '姓名和手机号不能为空']);
            exit();
        }
        
        // 检查手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            echo json_encode(['code' => -3, 'msg' => '手机号格式不正确']);
            exit();
        }
        
        if ($id > 0) {
            // 编辑
            $data = [
                'name' => $name,
                'phone' => $phone,
                'company' => $company,
                'position' => $position
            ];
            $where = "id={$id} AND activity_id={$activity_id}";
            $result = $db->update('ddwx_hd_whitelist', $data, $where);
            
            if ($result) {
                echo json_encode(['code' => 0, 'msg' => '编辑成功']);
            } else {
                echo json_encode(['code' => -4, 'msg' => '编辑失败']);
            }
        } else {
            // 新增
            // 检查是否已存在
            $exists = $db->find("SELECT id FROM ddwx_hd_whitelist WHERE activity_id={$activity_id} AND phone='" . addslashes($phone) . "'");
            if ($exists) {
                echo json_encode(['code' => -5, 'msg' => '该手机号已存在']);
                exit();
            }
            
            $data = [
                'aid' => $aid,
                'bid' => $bid,
                'activity_id' => $activity_id,
                'name' => $name,
                'phone' => $phone,
                'company' => $company,
                'position' => $position
            ];
            
            $result = $db->insert('ddwx_hd_whitelist', $data);
            
            if ($result) {
                echo json_encode(['code' => 0, 'msg' => '添加成功']);
            } else {
                echo json_encode(['code' => -6, 'msg' => '添加失败']);
            }
        }
        break;
        
    case 'DELETE':
        // 删除白名单
        parse_str(file_get_contents('php://input'), $delete_data);
        $id = isset($delete_data['id']) ? intval($delete_data['id']) : 0;
        
        if ($id > 0) {
            $where = "id={$id} AND activity_id={$activity_id}";
            $result = $db->delete('ddwx_hd_whitelist', $where);
            
            if ($result) {
                echo json_encode(['code' => 0, 'msg' => '删除成功']);
            } else {
                echo json_encode(['code' => -7, 'msg' => '删除失败']);
            }
        } else {
            // 清空白名单
            $result = $db->delete('ddwx_hd_whitelist', "activity_id={$activity_id}");
            
            if ($result) {
                echo json_encode(['code' => 0, 'msg' => '清空成功']);
            } else {
                echo json_encode(['code' => -8, 'msg' => '清空失败']);
            }
        }
        break;
        
    default:
        echo json_encode(['code' => -9, 'msg' => '不支持的请求方法']);
        break;
}
