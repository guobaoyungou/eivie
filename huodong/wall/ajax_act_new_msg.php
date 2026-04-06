<?php 
include(dirname(__FILE__) . '/../common/db.class.php');
require_once(dirname(__FILE__) . '/../common/function.php');
require_once('../common/session_helper.php');
// if (!isset($_SESSION['views']) || $_SESSION['views'] != true) {
// 	echo '{"ret":-1}';
// 	return ;
// }
//最后审核时间
$lastshenhetime=isset($_GET['shenhetime'])?intval($_GET['shenhetime']):0;
$load->model('Wall_model');
$wall_config=$load->wall_model->getConfig();

// 优先从新系统 hd_activity.screen_config 读取显示设置
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
$displayConfig = getActivityDisplayConfig($activity_id);
if ($displayConfig && $displayConfig['sign_show_style'] !== null) {
	$showtype_value = $displayConfig['sign_show_style'];
} else {
	// 回退到旧的 weixin_system_config 表（消息墙原来读 wallnameshowstyle，现统一使用 sign_show_style）
	$load->model('System_Config_model');
	$showtype=$load->system_config_model->get('wallnameshowstyle');
	$showtype_value = isset($showtype['configvalue']) ? $showtype['configvalue'] : 1;
}
$num=intval($wall_config['msg_historynum']);
$num=$num<=0?3:$num;
$messagelist=$load->wall_model->getWallMessage($lastshenhetime,$num);
include("../wall/biaoqing.php");
$load->model("Attachment_model");
foreach($messagelist as $k=>$message){
	$message['nick_name']=pack('H*', trim($message['nickname']));
	if($showtype_value==2 && !empty($message['signname'])){
		//显示姓名
		$message['nick_name']=$message['signname'];
	}
	if($showtype_value==3 && !empty($message['phone'])){
		//显示电话
		$message['nick_name']=substr_replace($message['phone'],'****',3,4);
	}
	unset($message['signname']);
	unset($message['phone']);
	unset($message['nickname']);
	if($message['image']==0){
		$message['type']=1;
		$message['content']=pack('H*', $message['content']);
		$message = emoji_unified_to_html(emoji_softbank_to_unified($message));
		$message['content']=biaoqing($message['content']);
	}else{
		$message['type']=2;
		$image=$load->attachment_model->getById($message['image']);
		$message['content']=$image['filepath'];
	}
	$messagelist[$k]=$message;
}

$returndata=array();
$returndata['data']=$messagelist;
$returndata['ret']=0;
echo json_encode($returndata);
return;