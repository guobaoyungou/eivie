<?php
ini_set("display_errors","On");
error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/../common/db.class.php');
require_once(dirname(__FILE__) . '/../common/function.php');
require_once(dirname(__FILE__) . '/../common/http_helper.php');
// require_once(dirname(__FILE__) . '/../common/function.php');

// include('biaoqing.php');
$action = $_GET['action'];

switch($action){
	case 'start':
		gamestart();
		break;
	case 'end':
		gameover();
		break;
	case 'redpacket_activity_screen_record':
		redpacket_activity_screen_record();
		break;
	case 'redpacket_users':
		redpacket_users();
		break;
	case 'redpacke_zjlist':
		redpacke_zjlist();
		break;
	case 'sendingredpacket':
		ajax_act_sending_redpacket();
		break;
}
//开始游戏
function gamestart(){
	$roundid=isset($_POST['roundid'])?intval($_POST['roundid']):0;
	$load=Loader::getInstance();
	$load->model('Redpacket_model');
	$redpacket_round=$load->redpacket_model->getRoundById($roundid);
	if(empty($redpacket_round)){
		$data=array('errno'=>-1,"message"=>'没有可以进行的活动了');
		echo json_encode($data);
		return;
	}

	if($redpacket_round['status']==1){
		//活动未开始
		$newdata=array('id'=>$roundid,'started_at'=>time(),'status'=>2);
		$result=$load->redpacket_model->updateRound($newdata);
		$data=array('errno'=>1,"message"=>"活动未开始","lefttime"=>$redpacket_round['lefttime']);
		echo json_encode($data);
		return;
	}
	if($redpacket_round['status']==2){
		//活动进行中
		$lefttime=time()-$redpacket_round['started_at'];
		$lefttime= $redpacket_round['lefttime']-$lefttime;
		$lefttime=$lefttime<=0?0:$lefttime;
		$data=array('errno'=>2,"message"=>"活动进行中","lefttime"=>$lefttime);
		echo json_encode($data);
		return;
	}
	if($redpacket_round['status']==3){
		//活动已经结束
		$data=array('errno'=>-2,"message"=>'本轮活动已经结束');
		echo json_encode($data);
		return;
	}
}
//游戏结束
function gameover(){
	$roundid=isset($_POST['roundid'])?intval($_POST['roundid']):0;
	$load=Loader::getInstance();
	$load->model('Redpacket_model');
	$redpacket_round=$load->redpacket_model->getRoundById($roundid);
	if(empty($redpacket_round)){
		$data=array('errno'=>-1,"message"=>'没有可以进行的活动了');
		echo json_encode($data);
		return;
	}
	if($redpacket_round['status']==1){
		$data=array('errno'=>-2,"message"=>'活动还未开始，不能结束');
		echo json_encode($data);
		return;
	}
	if($redpacket_round['status']==2){
		//活动进行中
		//检查时间
		$lefttime=time()-$redpacket_round['started_at'];
		if($lefttime>=$redpacket_round['lefttime']){
			$newdata=array('id'=>$roundid,'status'=>3);
			$result=$load->redpacket_model->updateRound($newdata);
			$data=array('errno'=>1,"message"=>'活动结束');
			echo json_encode($data);
			return;
		}else{
			$data=array('errno'=>-3,"message"=>'活动时间没到，不能结束');
			echo json_encode($data);
			return;
		}
	}
}

//本轮活动的中奖名单
function redpacket_activity_screen_record(){
	$maxid=isset($_POST['max_record_id'])?intval($_POST['max_record_id']):0;
	$roundid=isset($_POST['roundid'])?intval($_POST['roundid']):0;
	$load=Loader::getInstance();
	$load->model('Redpacket_model');
	$redpacket_users=$load->redpacket_model->getWinner($roundid,$maxid);
	$redpacket_users=processzjlist($redpacket_users);
	if($redpacket_users){
		$data=array('errno'=>1,"message"=>'中奖记录','data'=>$redpacket_users);
		echo json_encode($data);
		return;
	}else{
		$data=array('errno'=>-1,"message"=>'暂时没有中奖记录');
		echo json_encode($data);
		return;
	}
}

//修改为本轮中奖名单
function redpacke_zjlist(){
	$roundid=isset($_POST['roundid'])?intval($_POST['roundid']):0;
	if($roundid<=0){
		$data=array('errno'=>-1,"message"=>'活动信息错误');
		echo json_encode($data);
		return;
	}
	$load=Loader::getInstance();
	$load->model('Redpacket_model');
	$redpacket_users=$load->redpacket_model->getWinners($roundid);
	$redpacket_users=processzjlist($redpacket_users);
	if($redpacket_users){
		$data=array('errno'=>1,"message"=>'中奖记录','data'=>$redpacket_users);
		echo json_encode($data);
		return;
	}else{
		$data=array('errno'=>-1,"message"=>'暂时没有中奖记录');
		echo json_encode($data);
		return;
	}
}

//获取参与用户列表
function redpacket_users(){
	$maxuserid=isset($_POST['maxuserid'])?intval($_POST['maxuserid']):0;
	$load=Loader::getInstance();
	$load->model('Flag_model');
	//获取最后签到的6个人
	$flags=$load->flag_model->getRecentSignedUsers(6,$maxuserid);
	$redpacket_users=processuserlist($flags);
	if($redpacket_users){
		$data=array('errno'=>1,"message"=>'参数记录','data'=>$redpacket_users);
		echo json_encode($data);
		return;
	}else{
		$data=array('errno'=>-1,"message"=>'暂时没有签到记录');
		echo json_encode($data);
		return;
	}
}

//处理中奖名单
function processzjlist($redpacket_users){
	$newredpacket_users=array();
	foreach($redpacket_users as $k=>$v){
		$row=array();
		$row['id']=$v['id'];
		$row['avatar']=$v['avatar'];
		$row['nick_name']=pack('H*', $v['nickname']);
		$row['money']=$v['amount']/100;
		$newredpacket_users[]=$row;
	}
	return $newredpacket_users;
}

//处理参与用户名单
function processuserlist($redpacket_users){
	$newredpacket_users=array();
	foreach($redpacket_users as $k=>$v){
		$row=array();
		$row['id']=$v['signorder'];
		$row['avatar']=$v['avatar'];
		$row['nick_name']=pack('H*', $v['nickname']);
		$newredpacket_users[]=$row;
	}
	return $newredpacket_users;
}

//发送红包
function ajax_act_sending_redpacket(){
	$roundid=isset($_POST['roundid'])?intval($_POST['roundid']):0;
	if($roundid<=0){
		$resultdata=array('errno'=>-1,"message"=>'轮次信息错误');
		echo json_encode($resultdata);
		return;
	}
	$load=Loader::getInstance();
	$load->model('Redpacket_model');
	//获取当前轮次中已经被人抢到，但是没有发放的红包
	$redpacket_users=$load->redpacket_model->getRedpacketUserinfoByStatus(1,$roundid);
	$redpacket_config=$load->redpacket_model->getRedpacketConfig();
	$sendname=$redpacket_config['sendname'];
	$wishing=$redpacket_config['wishing'];

	foreach($redpacket_users as $user){
		$sendingstatus=$load->redpacket_model->getRedpacketSendingStatusByUserid($user['userid'],$roundid);
		//如果已经发放完毕就跳过
		if($sendingstatus>=2){
			continue;
		}
		//修改红包发放状态，标记为正在发送中
		$load->redpacket_model->setRedpacketSendingStatusByUserid($user['userid'],2,$roundid);
		//发放红包
		$returndata=$load->redpacket_model->sendingRedpacket($user['openid'],$user['totalmoney'],$sendname,$wishing);
		$redpacket_order_return_data=array(
				'return_code'=>$returndata['return_code'],
				'return_msg'=>$returndata['return_msg'],
				'result_code'=>$returndata['result_code'],
				'err_code'=>$returndata['err_code'],
				'err_code_des'=>$returndata['err_code_des'],
				'mch_billno'=>$returndata['mch_billno'],
				'mch_id'=>$returndata['mch_id'],
				'wxappid'=>$returndata['wxappid'],
				're_openid'=>$returndata['re_openid'],
				'total_amount'=>$returndata['total_amount'],
				'send_listid'=>isset($returndata['send_listid'])?$returndata['send_listid']:''
		);
		$load->redpacket_model->addRedpacketSendingOrderReturn($redpacket_order_return_data);
		if($returndata['return_code']=='SUCCESS' && $returndata['return_msg']='发放成功'){
			$result=$load->redpacket_model->setRedpacketSendingStatusByUserid($user['userid'],3,$roundid);
		}else{
			$result=$load->redpacket_model->setRedpacketSendingStatusByUserid($user['userid'],4,$roundid);
		}
	}
	$resultdata=array('errno'=>1,"message"=>'红包发放完成');
	echo json_encode($resultdata);
	return;
}









