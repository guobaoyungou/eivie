<?php
require_once('../common/session_helper.php');
include(dirname(__FILE__) . '/../common/db.class.php');
require_once(dirname(__FILE__) . '/../common/function.php');
// if (!isset($_SESSION['views']) || $_SESSION['views'] != true) {
// 	return false;
// }
$omid=isset($_GET['mid'])?intval($_GET['mid']):0;

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

$flag_m=new M('flag');
$flag=$flag_m->find('flag=2 and signorder >'.$omid.' order by id asc');
if(!empty($flag)){
	require_once 'biaoqing.php';

	$flag['nickname'] = processNickname($flag, $showtype_value);

	$flag['content']=pack('H*', $flag['content']);
	$flag= emoji_unified_to_html(emoji_softbank_to_unified($flag));
	$flag['content']=biaoqing($flag['content']);
	$avatar = processAvatar($flag, $use_wx_avatar);
	$result=array(
			'omid'=>$omid,
			'mid'=>$flag['signorder'],
			'avatar'=>$avatar,
			'qdnums'=>$flag['signorder'],
			'nick_name'=>$flag['nickname']
	);
	$json=json_encode($result);
	echo $json;
}else{
	$result=array(
			'omid'=>$omid,
			'mid'=>$omid,
			'avatar'=>'',
			'qdnums'=>'',
			'nick_name'=>''
	);
	$json=json_encode($result);
	echo $json;
}
return;
