<?php
@header("Content-type: text/html; charset=utf-8");
require_once(dirname(__FILE__) . '/../smarty/Smarty.class.php');
require_once(dirname(__FILE__) . '/../common/db.class.php');
require_once(dirname(__FILE__) . '/../common/session_helper.php');
require_once(dirname(__FILE__) . '/../common/function.php');

$style='meepo';

$load->model('Wall_model');
$wall_config=$load->wall_model->getConfig();
$load->model('Weixin_model');
$weixin_config=$load->weixin_model->getConfig();
$kaimu_config_m=new M('kaimu_config');
$kaimu_config=$kaimu_config_m->find('1');
$bimuimage='';
if(!isset($kaimu_config) || empty($kaimu_config['imagepath'])){
	$kaimuimage='themes/meepo/assets/images/kaimu.png';
}else{
	$load->model('Attachment_model');
	$img=$load->attachment_model->getById($kaimu_config['imagepath']);
	$kaimuimage=$img['filepath'];
}
$kaimu_config['image']=$kaimuimage;

$smarty = new Smarty;
$smarty->caching = false;
$apppath=str_replace(DIRECTORY_SEPARATOR.'wall', '', dirname(__FILE__));
$smarty->compile_dir = $apppath.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'templates_c'.DIRECTORY_SEPARATOR;
$smarty->assign('from','qiandao');
$smarty->assign('wall_config',$wall_config);

$smarty->assign('erweima',$weixin_config['erweima']);
$smarty->assign('kaimuconfig',$kaimu_config);
$smarty->display('themes/'.$style.'/header.html');
$smarty->display('themes/'.$style.'/kaimu.html');
$smarty->display('themes/'.$style.'/footer.html');