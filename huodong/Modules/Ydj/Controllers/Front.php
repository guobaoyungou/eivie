<?php
/**
 * 模块前台页面
 * PHP version 5.4+
 * 
 * @category Modules
 * 
 * @package Ydj
 * 
 * */
require_once MODULE_PATH.DIRECTORY_SEPARATOR.'Frontbase.php';
require_once BASEPATH.DIRECTORY_SEPARATOR."common".DIRECTORY_SEPARATOR."function.php";
/**
 *  模块前台页面
 *  PHP version 5.4+
 * 
 * @category Modules
 * 
 * @package Ydj
 * 
 * */
require_once BASEPATH .DIRECTORY_SEPARATOR. 'common'.DIRECTORY_SEPARATOR.'function.php';
use \Modules\Ydj\Models\Ydj_model;
use \Modules\Prize\Controllers\Api;

class Front extends Frontbase
{
    var $_ydj_model=null;
    public function __construct(){
        parent::__construct();
        $this->_ydj_model=new Ydj_model();
    }
    /**
     * 摇大奖界面
     * 
     * @return void
     */
    public function index()
    {
        $id=isset($_GET['id'])?intval($_GET['id']):0;
        $config=$this->_ydj_model->getCurrentConfig($id);
        $prizenum=$this->_ydj_model->getPrizesnum('ydj',$config['id']);
        $prize_api=new Api();
        $resultdata=$prize_api->getprizes('ydj',$config['id']);
        $prizesjson='{}';
        if($resultdata['code']>0){
            $prizesjson=json_encode($resultdata['data']);
        }
        $winners=$this->_ydj_model->getWinners();
        // echo var_export($winners);
        if(!empty($winners)){
            foreach($winners as $k=>$v){
                $winners[$k]['userinfo']=$this->_formatuserinfo($k,$config['showstyle']);
            }
        }
        
        $this->assign('winners', empty($winners)?'{}':json_encode($winners));
        $this->assign('prizesjson', $prizesjson);
        $this->assign('configjson', json_encode($config));
        $this->assign('PrizeNum',json_encode($prizenum));
        $this->show('index.html');
    }

    private function _formatuserinfo($userid,$showstyle){
        $userinfo=$this->_ydj_model->getJoinUser($userid,true);
        $userinfo['nickname']=processNickname($userinfo,$showstyle);
        return $userinfo;
    }
    /**
     * 开始游戏
     * 
     * @return void
     */

    public function ajax_act_start()
    {
        $result=$this->_ydj_model->startGame(0);
        $returndata=array('code'=>-1,'message'=>"游戏状态不对");
        if($result>0){
            $returndata=array('code'=>1,'message'=>"游戏开始");
        }
        echo json_encode($returndata);
        return;
    }

    public function ajax_act_resetgame(){
        $result=$this->_ydj_model->resetGame(0);
        $returndata=array('code'=>-1,'message'=>"游戏状态不对");
        if($result>0){
            $returndata=array('code'=>1,'message'=>"游戏重置完成");
        }
        echo json_encode($returndata);
        return;
    }
    /**
     * 结束游戏
     * 
     * @return void
     */
    public function ajax_act_endgame()
    {
        $result=$this->_ydj_model->endGame(0);
        $returndata=array('code'=>-1,'message'=>"游戏状态不对");
        if($result>0){
            $returndata=array('code'=>1,'message'=>"游戏结束");
        }
        echo json_encode($returndata);
        return;
    }

    /**
     * 定期发奖
     * todo:去重复中奖
     * @return void
     */
    public function ajax_act_get_winners(){
        $data=$this->_ydj_model->flushRecentWinners();
        $config=$this->_ydj_model->getCurrentConfig();
        foreach($data['winners'] as $k=>$v){
            $data['winners'][$k]['userinfo']=$this->_formatuserinfo($v['userid'],$config['showstyle']);
        }
        // ,'debug'=>$data['test']
        $returndata=array('code'=>1,'message'=>'中奖信息','data'=>$data['winners']);
        echo json_encode($returndata);
        return;
    }
    /**
     * 获取参与游戏的人数
     * 
     * @return void
     */
    public function ajax_act_get_joinuserlist()
    {
        $data=$this->_ydj_model->recentJoinUsers();
        $returndata=['code'=>1,'message'=>"参与名单",'data'=>$data];
        echo json_encode($returndata);
        return;
    }
}