<?php
@header("Content-type: text/html; charset=utf-8");
require_once dirname(__FILE__) . '/../common/db.class.php';
require_once dirname(__FILE__) . '/../common/function.php';
require_once dirname(__FILE__) . '/../common/session_helper.php';
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != true) {
    $_SESSION['admin'] = false;
    $returndata = array('code' => -100, "message" => "您的登录已经过期，请重新登录");
    echo json_encode($returndata);
    exit();
}
$action = $_GET['action'];
switch ($action) {
    case 'seterweima':
        seterweima();
        break;
    case 'qrcodetoptext':
        setqrcodetoptext();
        break;
    case 'menucolor':
        setmenucolor();
        break;
    case 'showcountsign':
        showcountsign();
        break;
    case 'show_company_name':
        setShowConfig('show_company_name');
        break;
    case 'show_logo':
        setShowConfig('show_logo');
        break;
    case 'show_activity_name':
        setShowConfig('show_activity_name');
        break;
    case 'show_copyright':
        setShowConfig('show_copyright');
        break;
    case 'setmobilemenufontcolor':
        setmobilemenufontcolor();
        break;
    case 'setlogo':
        setlogo();
        break;
    case 'setactivityname':
        setactivityname();
        break;
}

function showcountsign()
{
    $load = Loader::getInstance();
    $val = isset($_GET['showcountsign']) ? intval($_GET['showcountsign']) : 1;
    $load->model('System_Config_model');
    $return = $load->system_config_model->set('showcountsign', $val);
    // echo 'test';
    if ($return) {
        echo '{"code":1,"message":"修改成功"}';
        return;
    } else {
        echo '{"code":-2,"message":"修改失败"}';
        return;
    }
}
//调整页面底部菜单颜色
function setmenucolor()
{
    $load = Loader::getInstance();
    $val = isset($_GET['menucolor']) ? $_GET['menucolor'] : '#fff';
    $load->model('System_Config_model');
    $return = $load->system_config_model->set('menucolor', $val);
    if ($return) {
        echo '{"code":1,"message":"修改成功"}';
        return;
    } else {
        echo '{"code":-2,"message":"修改失败"}';
        return;
    }

}
//设置手机端签到页面的菜单文字颜色
function setmobilemenufontcolor()
{
    $load = Loader::getInstance();
    $val = isset($_GET['mobilemenufontcolor']) ? $_GET['mobilemenufontcolor'] : '#fff';
    $load->model('System_Config_model');
    $return = $load->system_config_model->set('mobilemenufontcolor', $val);
    if ($return) {
        echo '{"code":1,"message":"修改成功"}';
        return;
    } else {
        echo '{"code":-2,"message":"修改失败"}';
        return;
    }
}

//设置活动二维码
function seterweima()
{
    $load = Loader::getInstance();
    $load->model('Weixin_model');
    $data = array('erweima' => 0);
    if (!empty($_FILES['erweima']['type'])) {
        $load->model('Attachment_model');
        $file = $load->attachment_model->saveFormFile($_FILES['erweima']);

        $data = array('erweima' => $file['id']);
    }
    $result = $load->weixin_model->setConfig($data);
    echo "<script>alert('二维码已经配置成功！');history.go(-1);</script>";
}

//设置二维码上面的文字
function setqrcodetoptext()
{
    $text = isset($_POST['qrcodetoptext']) ? strval($_POST['qrcodetoptext']) : '';
    $load = Loader::getInstance();
    $load->model('Wall_model');
    $data = array('qrcodetoptext' => $text);
    $result = $load->wall_model->setConfig($data);
    if ($result) {
        $resultdata = array('code' => 1, 'message' => '修改成功');
        echo json_encode($resultdata);
        return;
    } else {
        $resultdata = array('code' => -1, 'message' => '修改失败');
        echo json_encode($resultdata);
        return;
    }
}

// 设置显示开关（公司名称、活动名称、版权信息、活动LOGO）
function setShowConfig($key)
{
    $load = Loader::getInstance();
    $val = isset($_GET[$key]) ? intval($_GET[$key]) : 1;
    $load->model('System_Config_model');
    $return = $load->system_config_model->set($key, $val);
    if ($return) {
        echo '{"code":1,"message":"修改成功"}';
        return;
    } else {
        echo '{"code":-2,"message":"修改失败"}';
        return;
    }
}

// 上传活动LOGO图片
function setlogo()
{
    $load = Loader::getInstance();
    $load->model('Wall_model');
    $data = array('logoimg' => 0);
    if (!empty($_FILES['logo']['type'])) {
        $load->model('Attachment_model');
        $file = $load->attachment_model->saveFormFile($_FILES['logo']);
        if ($file) {
            $data = array('logoimg' => $file['id']);
        }
    }
    $result = $load->wall_model->setConfig($data);
    echo "<script>alert('活动LOGO已保存成功！');history.go(-1);</script>";
}

// 保存活动名称
function setactivityname()
{
    $text = isset($_POST['activity_name']) ? strval($_POST['activity_name']) : '';
    $load = Loader::getInstance();
    $load->model('Wall_model');
    $data = array('activity_name' => $text);
    $result = $load->wall_model->setConfig($data);
    if ($result) {
        $resultdata = array('code' => 1, 'message' => '修改成功');
        echo json_encode($resultdata);
        return;
    } else {
        $resultdata = array('code' => -1, 'message' => '修改失败');
        echo json_encode($resultdata);
        return;
    }
}
