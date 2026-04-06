<?php
require_once(dirname(__FILE__) . '/../common/db.class.php');
require_once(dirname(__FILE__) . '/../common/function.php');
require_once("../wall/biaoqing.php");
$maxid=isset($_GET['mid'])?intval($_GET['mid']):0;

// 优先从新系统 hd_activity.screen_config 读取显示设置
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
$displayConfig = getActivityDisplayConfig($activity_id);
if ($displayConfig && $displayConfig['sign_show_style'] !== null) {
	$showtype_value = $displayConfig['sign_show_style'];
	$use_wx_avatar = $displayConfig['use_wx_avatar'];
} else {
	// 回退到旧的 weixin_system_config 表
	$load->model('System_Config_model');
	$showtype=$load->system_config_model->get('signnameshowstyle');
	$showtype_value = isset($showtype['configvalue']) ? $showtype['configvalue'] : 1;
	$use_wx_avatar = 1;
}

//签到名单
$load->model("Flag_model");
$signpeople=$load->flag_model->getRecentSignedUsers(1,$maxid);

if(!empty($signpeople)){
	$signpeople=$signpeople[0];
	$signpeople['nickname'] = processNickname($signpeople, $showtype_value);
	$signpeople['avatar'] = processAvatar($signpeople, $use_wx_avatar);
	$returndata=$signpeople;
	$returndata['error']=1;
	$returndata['mid']=$signpeople['signorder'];
	$returndata['omid']=$maxid;
	echo json_encode($returndata);
}else{
	echo '{"error":-1}';
}