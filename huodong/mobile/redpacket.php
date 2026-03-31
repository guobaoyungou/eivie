<?php
require_once('common.php');
require_once('../Modules/Menu/Controllers/Api.php');
require_once('../Modules/Menu/Models/Menu_model.php');
use Modules\Menu\Controllers\Api;
$load->model('Plugs_model');
$plugs=$load->plugs_model->getPlugs(1);

$openid=$_GET['rentopenid'];
$isopen=false;

foreach($plugs as $item){
	if($item[name]=='redpacket'){
		$isopen=true;
	}
}

if(!$isopen){
	header('location:qiandao.php?rentopenid='.$openid);
}
$redpacket_config_m=new M('redpacket_config');
$redpacket_config=$redpacket_config_m->find('1');

$redpacket_config['rule']=str_replace("\n", '<br/>', $redpacket_config['rule']);
$redpacket_config['tips']=str_replace("\n", '<br/>', $redpacket_config['tips']);
$redpacket_round_m=new M('redpacket_round');
$currentredpacket_round=$redpacket_round_m->find('status<3 order by status desc,id asc limit 1');
$flag_m=new M('flag');
$myinfo=$flag_m->find('openid="'.$openid.'"');
//模版页面相关内容
require_once('../smarty/Smarty.class.php');
$smarty = new Smarty;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->compile_dir = COMPILEPATH;//APPPATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'templates_c'.DIRECTORY_SEPARATOR;

// $controls=array('qd','wall','vote','lottory','shake','redpack');
$smarty->assign('title','红包雨');
$smarty->assign('openid',$openid);
$smarty->assign('user',$myinfo);
$menu_api=new Api();
$custommenu=$menu_api->getAll(array('rentopenid'=>$openid));
$smarty->assign('custommenu',$custommenu);
// $weixin_config=getWeixinConf();
$smarty->assign('erweima',$weixin_config['erweima']);

$smarty->assign('redpacket_config',$redpacket_config);
$smarty->assign('currentredpacket_round',$currentredpacket_round);
$smarty->assign('plugs',$plugs);
$smarty->display('template/app_header.html');
$smarty->display('template/app_redpacket.html');
$smarty->display('template/app_footer.html');