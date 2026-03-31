<?php

@header("Content-type: text/html; charset=utf-8");
require_once(dirname(__FILE__) . '/../smarty/Smarty.class.php');
require_once(dirname(__FILE__) . '/../common/db.class.php');
require_once(dirname(__FILE__) . '/../common/session_helper.php');
require_once(dirname(__FILE__) . '/../common/function.php');


$style = 'meepo';

$load->model('Wall_model');
$wall_config = $load->wall_model->getConfig();
$load->model('Weixin_model');
$weixin_config = $load->weixin_model->getConfig();
$danye = new M('danye');
$id = $_GET['id'];
if (empty($id)) {
    $danyedata = $danye->find(1);
} else {
    $danyedata = $danye->find('id=' . $id);
}
$config = $danye->select('1 order by sort asc');
$configdata = [];
foreach ($config as $k => $v) {
    $id = empty($id) ? $v['id'] : $id;
    if ($id == $v['id']) {
        $data = [
            'action' => '/wall/danye.php?id=' . $v['id'],
            'cureent' => '1',
            'icon' => 'icondanye',
            'name' => $v['title']
        ];
    }else{
        $data = [
            'action' => '/wall/danye.php?id='.$v['id'],
            'cureent' => '2',
            'icon' => 'icondanye',
            'name' => $v['title']
        ];
    }


    $configdata[] = $data;
}

$smarty = new Smarty;
$smarty->caching = false;
$apppath = str_replace(DIRECTORY_SEPARATOR . 'wall', '', dirname(__FILE__));
$smarty->compile_dir = $apppath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'templates_c' . DIRECTORY_SEPARATOR;
$smarty->assign('from', 'qiandao');
$smarty->assign('wall_config', $wall_config);
$smarty->assign('config', json_encode($configdata));
$smarty->assign('erweima', $weixin_config['erweima']);
$smarty->assign('danyedata', $danyedata);
$smarty->display('themes/' . $style . '/header.html');
$smarty->display('themes/' . $style . '/danye.html');
$smarty->display('themes/' . $style . '/footer.html');
