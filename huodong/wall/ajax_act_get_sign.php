<?php
require_once('../common/session_helper.php');
include(dirname(__FILE__) . '/../common/db.class.php');
include(dirname(__FILE__) . '/../common/function.php');

// if (!isset($_SESSION['views']) || $_SESSION['views'] != true) {
// 	return false;
// }
$omid=isset($_GET['mid'])?intval($_GET['mid']):0;
$num=isset($_GET['num'])?intval($_GET['num']):1;
if($num<=0){
	echo returnmsg(-1,'每次取的数据必须大于1');
	return;
}

// 优先从新系统 hd_activity.screen_config 读取显示设置
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
$displayConfig = getActivityDisplayConfig($activity_id);
if ($displayConfig && $displayConfig['sign_show_style'] !== null) {
	$showtype_value = $displayConfig['sign_show_style'];
	$use_wx_avatar = $displayConfig['use_wx_avatar'];
} else {
	// 回退到旧的 weixin_system_config 表
	$load->model('System_Config_model');
	$showtype = $load->system_config_model->get("signnameshowstyle");
	$showtype_value = isset($showtype['configvalue']) ? $showtype['configvalue'] : 1;
	$use_wx_avatar = 1;
}

$load->model('Flag_model');
$flags=$load->flag_model->getShenheUsers($omid,$num);
if(empty($flags)){
	$returndata=array(
		'omid'=>$omid,
		'mid'=>$omid,
		'users'=>array()
	);

	echo returnmsg(1,'',$returndata);
	return;
}else{
	$returndata=array();
	$returndata['users']=array();
	$returndata['omid']=$omid;
	$returndata['mid']=$omid;
	for($i=0,$l=count($flags);$i<$l;$i++){
		$returndata['mid']=$flags[$i]['signorder']>$returndata['mid']?$flags[$i]['signorder']:$returndata['mid'];
		$flag=processuserlist($flags[$i],$showtype_value,$use_wx_avatar);
		array_push($returndata['users'],$flag);
	}
	echo returnmsg(1,'',$returndata);
	return;
}

function processuserlist($user,$showtype,$use_wx_avatar=1){
	$newuser=array();
	$newuser['nickname']=processNickname($user,$showtype);
	$newuser['avatar']=processAvatar($user,$use_wx_avatar);
	return $newuser;
}

