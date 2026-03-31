<?php
/**
 * 摇大奖手机端页面
 * PHP version 5.4+
 * 
 * @category Modules
 * 
 * @package Ydj
 * 
 * */
require_once MODULE_PATH.DIRECTORY_SEPARATOR.'Mobilebase.php';
/**
 * 摇大奖手机端页面
 * PHP version 5.4+
 * 
 * @category Modules
 * 
 * @package Ydj
 * 
 * */
use \Modules\Ydj\Models\Ydj_model;
use \Modules\Menu\Controllers\Api;
class Mobile extends Mobilebase
{
    /**
     * 手机端摇大奖界面
     * 
     * @return void 
     */
    public function index()
    {
        $userinfo=$this->getUserinfo();
        $ydj_model=new Ydj_model();
        $config=$ydj_model->getCurrentConfig();
        $themeinfo=$ydj_model->getThemeById($config['themeid']);
        
        $canjoin=1;
        if($config['joinagain']==2){
            $data=$ydj_model->getWinHistory($userinfo['id'],$config['id']);
            if(!empty($data)){
                $canjoin=2;
            }
        }
        
        if($config['status']!=3){
            $user=$ydj_model->getJoinUser($userid);
            if(!$user){
                $ydj_model->joinGame($userinfo, $config['id']);
            }
        }
        $this->assign('title','摇大奖');
        $menu_api=new Api();
        $custommenu=$menu_api->getAll(array('rentopenid'=>$userinfo['openid']));
        $this->assign('custommenu',$custommenu);
        $this->assign('canjoin',$canjoin);
        $this->assign('ydj_config',json_encode($config));
        $this->assign('themejson',json_encode($themeinfo));
        $this->assign('openid',$userinfo['openid']);
        $this->assign('userinfo',json_encode($userinfo));
        $this->show('index.html');
    }
    /**
     * 游戏状态
     * 
     * @return void
     */
    public function ajax_act_get_status(){
        $userid=isset($_GET['userid'])?intval($_GET['userid']):0;
        
        if($userid==0){
            $returndata=['code'=>-1,'message'=>'数据错误'];
            echo json_encode($returndata);
            return;
        }
        $ydj_model=new Ydj_model();
        $config=$ydj_model->getCurrentConfig();
        if($config['status']==1){
            $message="游戏还没有开始请耐心等待";
            
        }
        if($config['status']==3){
            $message="本轮游戏已经结束了，请等待下一轮游戏。";
        }
        $prize=null;
        
        if($config['status']==2){
            if($_GET['action']=='shaking'){
                $userinfo=$ydj_model->getWinnerByUserId($userid);
                if($config['winningagain']==0){
                    $prize=$ydj_model->winPrize($config['id'],$userid);
                }else{
                    if(!$userinfo || $userinfo['count']<$config['winningagain']){
                        $prize=$ydj_model->winPrize($config['id'],$userid);
                    }
                }
            }
            $message='加油哦！';
        }
        if($config['status']!=3){
            $userinfo=$ydj_model->getJoinUser($userid);
            if(!$userinfo){
                $this->_load->model('Flag_model');
                $userinfo=$this->_load->flag_model->getUserinfoById($userid);
                $ydj_model->joinGame($userinfo, $config['id']);
            }
        }
        $returndata=["code"=>1,"message"=>$message,"data"=>["configid"=>$config['id'],"status"=>$config['status'],'prize'=>$prize]];
        echo json_encode($returndata);
        return;
    }    
}