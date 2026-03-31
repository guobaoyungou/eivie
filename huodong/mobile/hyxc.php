<?php

require_once('common.php');
require_once('../Modules/Menu/Controllers/Api.php');
require_once('../Modules/Menu/Models/Menu_model.php');
require_once('../Modules/Prize/Controllers/Api.php');

use Modules\Menu\Controllers\Api as Menu_Api;

require_once('../smarty/Smarty.class.php');
$load->model('Plugs_model');
$plugs = $load->plugs_model->getPlugs(1);
$openid = $_GET['rentopenid'];
$hdxc = new M('hdxc');
$hdxc = $hdxc->find();

$smarty = new Smarty;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->compile_dir = COMPILEPATH;
$menu_api = new Menu_Api();
$custommenu = $menu_api->getAll(array('rentopenid' => $openid));

if (!empty($hdxc) && isset($hdxc['img'])) {
    $smarty->assign('hdxcimg', $hdxc['img']);
} else {
    header('location:qiandao.php?rentopenid=' . $openid);
}
$smarty->assign('custommenu', $custommenu);
$smarty->assign('title', '活动行程');
$smarty->assign('openid', $openid);
$smarty->assign('plugs',$plugs);
$smarty->display('template/app_header.html');
$smarty->display('template/app_hyxc.html');
$smarty->display('template/app_footer.html');
