<?php

@header("Content-type: text/html; charset=utf-8");
require_once(dirname(__FILE__) . '/../common/db.class.php');
;
require_once("../wall/biaoqing.php");
require_once(dirname(__FILE__) . '/../common/session_helper.php');
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != true) {
    $_SESSION['admin'] = false;
    $returndata = array('code' => -100, "message" => "您的登录已经过期，请重新登录");
    echo json_encode($returndata);
    exit();
}
$action = $_GET['action'];
switch ($action) {
    case 'savedanye':
        savedanye();
        break;
    case 'deldanye';
        deldanye();
        break;
    case 'edit';
        edit();
        break;
}

function savedanye() {
    if (!empty($_FILES['photo']['type']) && !empty($_POST['danyetitle'])) {
        $load = Loader::getInstance();
        $load->model('Attachment_model');
        $file = $load->attachment_model->saveFormFile($_FILES['photo']);
        $danye = new M('danye');
        $data = [
            'title' => $_POST['danyetitle'],
            'img' => $file['filepath'],
            'sort' => $_POST['sort'],
            'createtime' => date('Y-m-d H:i:s')
        ];
        $res = $danye->add($data);
        if ($res) {
            $resultdata = array('code' => 1, 'message' => '保存成功', 'filepath' => $file['filepath']);
            echo json_encode($resultdata);
            return;
        } else {
            $resultdata = array('code' => -2, 'message' => '保存失败');
            echo json_encode($resultdata);
            return;
        }
    }
}

function deldanye(){
    $id = $_POST['id'];
    if(empty($id) || $id <= 0){
        $resultdata = array('code' => -1, 'message' => '删除失败');
        echo json_encode($resultdata);
        return;
    }
    $danye = new M('danye');
    $res = $danye->delete('id='.$id);
    if($res){
        $resultdata = array('code' => 1, 'message' => '删除成功');
        echo json_encode($resultdata);
        return ;
    }else{
        $resultdata = array('code' => -1, 'message' => '删除失败');
        echo json_encode($resultdata);
        return ;
    }
}