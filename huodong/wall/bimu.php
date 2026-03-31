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

$bimu_config_m=new M('bimu_config');
$bimu_config=$bimu_config_m->find('1');
$bimuimage='';
if(!isset($bimu_config) || empty($bimu_config['imagepath'])){
	$bimuimage='themes/meepo/assets/images/bimu.png';
}else{
	$load->model('Attachment_model');
	$img=$load->attachment_model->getById($bimu_config['imagepath']);
	$bimuimage=$img['filepath'];
}
$bimu_config['image']=$bimuimage;
$smarty = new Smarty;
$smarty->caching = false;
$apppath=str_replace(DIRECTORY_SEPARATOR.'wall', '', dirname(__FILE__));
$smarty->compile_dir = $apppath.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'templates_c'.DIRECTORY_SEPARATOR;
$smarty->assign('from','qiandao');
$smarty->assign('wall_config',$wall_config);
$smarty->assign('erweima',$weixin_config['erweima']);
$smarty->assign('bimuconfig',$bimu_config);
$smarty->display('themes/'.$style.'/header.html');
$smarty->display('themes/'.$style.'/bimu.html');
$smarty->display('themes/'.$style.'/footer.html');