<?php
require_once('common.php');
require_once('../smarty/Smarty.class.php');
require_once('../Modules/Menu/Controllers/Api.php');
require_once('../Modules/Menu/Models/Menu_model.php');
use Modules\Menu\Controllers\Api;

$load->model('Plugs_model');
$plugs=$load->plugs_model->getPlugs(1);

$openid=$_GET['rentopenid'];

$load->model('Flag_model');
$myinfo=$load->flag_model->getUserinfo($openid,false,true);

// 从 hd_activity 表的 screen_config 读取手机签到页配置
$_link = MysqliConnection::getlink();
$_actResult = mysqli_query($_link, "SELECT screen_config FROM hd_activity ORDER BY id DESC LIMIT 1");
$_actRow = $_actResult ? mysqli_fetch_assoc($_actResult) : null;
$_mobileConfig = ($_actRow && !empty($_actRow['screen_config'])) ? json_decode($_actRow['screen_config'], true) : [];

// 提取配置项（带默认值，模板变量统一使用 $mobile_ 前缀）
$mobile_bg           = !empty($_mobileConfig['mobile_bg_image']) ? $_mobileConfig['mobile_bg_image'] : '';
$mobile_hide_avatar  = isset($_mobileConfig['mobile_hide_avatar']) ? intval($_mobileConfig['mobile_hide_avatar']) : 0;
$mobile_activity_image = !empty($_mobileConfig['mobile_activity_image']) ? $_mobileConfig['mobile_activity_image'] : '';
$welcome_text        = !empty($_mobileConfig['mobile_welcome_text']) ? $_mobileConfig['mobile_welcome_text'] : '欢迎参与本次活动';
$btn_text            = !empty($_mobileConfig['mobile_btn_text']) ? $_mobileConfig['mobile_btn_text'] : '参 与 活 动';
$btn_image           = !empty($_mobileConfig['mobile_btn_image']) ? $_mobileConfig['mobile_btn_image'] : '';
$mobile_quick_message = isset($_mobileConfig['mobile_quick_message']) ? intval($_mobileConfig['mobile_quick_message']) : 0;
$mobile_force_wx_auth = isset($_mobileConfig['mobile_force_wx_auth']) ? intval($_mobileConfig['mobile_force_wx_auth']) : 1;
$mobile_force_wx_auth = isset($_mobileConfig['mobile_force_wx_auth']) ? intval($_mobileConfig['mobile_force_wx_auth']) : 1;

// 如果新配置无背景图，回退到旧配置
if (empty($mobile_bg)) {
    $load->model('System_Config_model');
    $data = $load->system_config_model->get("mobileqiandaobg");
    $load->model('Attachment_model');
    $_oldBg = $load->attachment_model->getById(intval($data['configvalue']));
    $mobile_bg = empty($_oldBg['filepath']) ? '' : $_oldBg['filepath'];
}

//模版页面相关内容
$smarty = new Smarty;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->compile_dir = COMPILEPATH;
$smarty->assign('title','签到');
$smarty->assign('wall_config',$wall_config);

if($myinfo['flag']==1){//没有签到
	$fromurl=isset($_GET['fromurl'])?strval($_GET['fromurl']):'';
		
	$smarty->assign('openid',$openid);
	$smarty->assign('user',$myinfo);

	// 背景图（无自定义时使用默认星空背景）
	$smarty->assign('mobile_bg', $mobile_bg ? $mobile_bg : 'template/app/images/bg.jpg');
	$smarty->assign('mobile_hide_avatar', $mobile_hide_avatar);
	$smarty->assign('btn_text', $btn_text);
	$smarty->assign('btn_image', $btn_image);
	$smarty->assign('mobile_quick_message', $mobile_quick_message);

	//签到姓名
	$load->model('System_Config_model');
	$data=$load->system_config_model->get("qiandaosignname");
	$qiandaosignname=intval($data['configvalue']);
	$smarty->assign('qiandaosignname',$qiandaosignname);
	//签到手机号
	$data=$load->system_config_model->get("qiandaophone");
	$qiandaophone=intval($data['configvalue']);
	$smarty->assign('qiandaophone',$qiandaophone);
	
	$load=Loader::getInstance();
	$load->model('Flag_model');
	$columns=$load->flag_model->getExtentionColumns();
	foreach($columns as $k=>$v){
		if($v['coltype']=='select'){
			$columns[$k]['options_arr']=unserialize($v['options']);
		}
	}
	$smarty->assign('diycolumns',$columns);
	$smarty->assign('erweima',$weixin_config['erweima']);
	$smarty->assign('plugs',$plugs);
	$smarty->assign('redirecturl',urldecode($fromurl));
	$smarty->display('template/app_header.html');
	$smarty->display('template/app_register.html');
	$smarty->display('template/app_footer.html');
}else{//完成签到
	$fromurl=isset($_GET['fromurl'])?strval($_GET['fromurl']):'';
	if(!empty($fromurl)){
		header('location:'.urldecode($fromurl));
		return;
	}
	$smarty->assign('erweima',$weixin_config['erweima']);
	$myinfo['nickname']=pack('H*', $myinfo['nickname']);
	$myinfo['datetime']=date('Y-m-d H:i:s',$myinfo['datetime']);
	$menu_api=new Api();
	$custommenu=$menu_api->getAll(array('rentopenid'=>$openid));

	// 背景图
	$smarty->assign('mobile_bg', $mobile_bg);
	$smarty->assign('mobile_hide_avatar', $mobile_hide_avatar);
	$smarty->assign('mobile_activity_image', $mobile_activity_image);
	$smarty->assign('welcome_text', $welcome_text);

	$load->model('System_Config_model');
	$menucolor=$load->system_config_model->get("mobilemenufontcolor");
	$menucolor['configvalue']=isset($menucolor['configvalue'])?$menucolor['configvalue']:'#000000';
	$smarty->assign('menucolor',$menucolor['configvalue']);
	$smarty->assign('custommenu',$custommenu);
	$smarty->assign('openid',$openid);
	$smarty->assign('user',$myinfo);
	$smarty->assign('plugs',$plugs);
	
	$smarty->display('template/app_header.html');
	$smarty->display('template/app_qd.html');
}
