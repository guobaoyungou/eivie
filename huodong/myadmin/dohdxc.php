<?php

@header("Content-type: text/html; charset=utf-8");
require_once(dirname(__FILE__) . '/../common/db.class.php');
require_once(dirname(__FILE__) . '/../common/session_helper.php');
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != true) {
    $_SESSION['admin'] = false;
    $returndata = array('code' => -100, "message" => "您的登录已经过期，请重新登录");
    echo json_encode($returndata);
    exit();
}
$action = $_GET['action'];
switch ($action) {
    case 'setimage':
        setimage();
        break;
}

function setimage() {
    $kaimu_config_m = new M('hdxc');
    //echo var_export($_FILES);
    if (!empty($_FILES['imagepath']['type'])) {
        //上传的文件
        // require_once('../common/FileUploadFactory.php');
        // $fuf=new FileUploadFactory(SAVEFILEMODE);
        // $file=$fuf->SaveFormFile($_FILES['imagepath']);

        $load = Loader::getInstance();
        $load->model('Attachment_model');
        $file = $load->attachment_model->saveFormFile($_FILES['imagepath']);
        $data = array('img' => $file['filepath']);
        $save = $kaimu_config_m->update('1', $data);
// 		echo var_export($save);
    } else {
        $data = array('imagepath' => '');
        $save = $kaimu_config_m->update('1', $data);
    }
    echo "<script>alert('活动行程图片已经更换成功！');history.go(-1);</script>";
}
