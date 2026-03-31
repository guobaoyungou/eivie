<?php
/**
 * 摇大奖模块model
 * PHP version 5.4+
 * 
 * @category Modules
 * 
 * @package Ydj
 * 
 * */
namespace Modules\Ydj\Models;
defined('BASEPATH') or define('BASEPATH', str_replace(DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR.'Ydj'.DIRECTORY_SEPARATOR.'Models', '', dirname(__FILE__)));
require_once BASEPATH.DIRECTORY_SEPARATOR."common".DIRECTORY_SEPARATOR."http_helper.php";//.DIRECTORY_SEPARATOR."autoload.php";
require_once BASEPATH.DIRECTORY_SEPARATOR."common".DIRECTORY_SEPARATOR."url_helper.php";
/**
 * 摇大奖模块model
 * PHP version 5.4+
 * 
 * @category Modules
 * 
 * @package Ydj
 * 
 * */
class Ydj_model
{
    var $_ydj_config_m=null;
    var $_ydj_record_m=null;
    var $_ydj_themes_m=null;
    var $_ydj_cache=null;
    var $_ydj_currentconfig_cachename='ydj_currentconfig_cache';
    var $_ydj_joinusers_cachename='ydj_joinusers';
    var $_cacheprefix=null;
    var $_redis_cache=null;
    /**
     * 构造函数，初始化参数
     * 
     * @return void
     */
    public function __construct()
    {
        $this->_ydj_config_m=new \M('ydj_config');
        $this->_ydj_record_m=new \M('ydj_record');
        $this->_ydj_themes_m=new \M('ydj_themes');
        $this->_ydj_cache=new \CacheFactory(CACHEMODE);
        if (CACHEMODE=="Redis") {
            $this->_redis_cache=new \Predis\Client(
                array(
                    'scheme' => 'tcp',
                    'host'   => REDIS_HOST,
                    'port'   => REDIS_PORT,
                    'password'=> REDIS_PASSWORD
                )
            );
            $this->_cacheprefix=CACHEPREFIX;
        }
    }
    /**
     * 从数据库中选择一个配置，作为当前活动配置
     * 
     * @param int $id 配置的id 如果配置为0，则按游戏状态和编号优先程度取一个配置
     * 
     * @return array 配置信息
     */
    public function selectCurrentConfig($id=0)
    {
        if ($id>0) {
            return $this->getConfigById($id);
        } else {
            $select='id,duration,winningagain,joinagain,status,maxplayers,showstyle,themeid';
            $where=' 1 order by status asc, id asc limit 1';
            return $this->_ydj_config_m->find($where);
        }
    }

    /**
     * 获取当前轮次的配置信息
     * 
     * @param int $id 配置id
     * 
     * @return array 当前的配置信息
     */
    public function getCurrentConfig($id=0)
    {
        
        $data=$this->_ydj_cache->get($this->_ydj_currentconfig_cachename);
        //是否需要更新，缓存不存在或者缓存不对应的时候需要更新
        $needupdatecache=!$data || ($id>0 && $data['id']!=$id);
        if ($needupdatecache) {
            $data=$this->selectCurrentConfig($id);
            if(!$data){
                return false;
            }
            $previdandnextid=$this->getPrevidAndNextid($data['id']);
            $data['nextid']=$previdandnextid['nextid'];
            $data['previd']=$previdandnextid['previd'];
            $this->_ydj_cache->set($this->_ydj_currentconfig_cachename, $data, 3*3600);
            $this->_clearcache();
        }
        return $data;
    }

    /**
     * 获取上一轮活动的id和下一轮活动的id
     * 
     * @param int $id 指定的活动id
     * 
     * @return array 上一轮活动的id和下一轮活动的id 组成的数组
     */
    public function getPrevidAndNextid($id)
    {
        $previd=$this->_ydj_config_m->find(
            'id<'.$id.' order by id desc limit 0,1', 'id'
        );
        $nextid=$this->_ydj_config_m->find(
            'id>'.$id.' order by id asc limit 0,1', 'id'
        );
        $data['previd']=$previd['id'];
        $data['nextid']=$nextid['id'];
        return $data;
    }

    /**
     * 获取所有活动配置
     * 
     * @return array 所有配置信息的数组
     */
    public function getAllConfig()
    {
        $configs=$this->_ydj_config_m->select(
            "1 order by id asc", 
            'weixin_ydj_config.*,weixin_ydj_themes.themename', '', 'assoc',
            'left join weixin_ydj_themes on weixin_ydj_config.themeid=weixin_ydj_themes.id'
        );
        return $configs;
    }
    /**
     * 保存配置信息
     * 
     * @param array $config 配置信息
     * 
     * @return int config的id
     */
    public function saveConfig($config)
    {
        $columnname=array(
            'id','duration',
            'winningagain','status','joinagain',
            'showstyle','themeid'
        );
        foreach ($config as $k=>$v) {
            if (!in_array($k, $columnname)) {
                unset($config[$k]);
            }
        }
        if ($config['id']>0) {
            $id=$config['id'];
            unset($config['id']);
            $result=$this->_ydj_config_m->update("id=".$id, $config);
            $currentconfig=$this->getCurrentConfig();
            if($currentconfig['id']==$id){
                $this->_ydj_cache->delete($this->_ydj_currentconfig_cachename);
            }
            return $result?$id:-1;
        } else {
            $config['status']=1;
            $result=$this->_ydj_config_m->add($config);
            return $result?$result:-1;
        }
    }

    /**
     * 删除指定id的config
     * 
     * @param int $id 配置id
     * 
     * @return bool 删除成功true 失败是false
     */
    public function deleteConfig($id)
    {
        if ($id<=0) {
            return false;
        }
        $result=$this->_ydj_config_m->delete("id=".$id);
        return $result;
    }
    /**
     * 删除游戏记录
     * 
     * @param int $configid 轮次配置configid
     * 
     * @return bool 删除失败就是false ，成功就是true
     */
    public function deleteRecords($configid)
    {
        if ($id<=0) {
            return false;
        }
        $result=$this->_ydj_record_m->delete("configid=".$configid);
        return $result;
    }

    /**
     * 按传入的id获取活动配置信息
     * 
     * @param int $id 配置id
     * 
     * @return array 活动配置信息的数组
     */
    public function getConfigById($id)
    {
        return $this->_ydj_config_m->find('id='.$id);
    }
    /**
     * 获取所有主题信息
     * 
     * @return array 所有主题信息的数组
     */
    public function getThemes()
    {
        return $this->_ydj_themes_m->select('1');
    }
    /**
     * 按传入的id获取主题信息
     * 
     * @param int $id 主题id
     * 
     * @return array 所有主题信息的数组
     */
    public function getThemeById($id)
    {
        $data=$this->_ydj_themes_m->find('id='.$id);
        return $this->_formatdata($data);
    }
    /**
     * 格式化主题的数据
     */
    private function _formatdata($data){
        $themedata=isset($data['themedata'])?$data['themedata']:'';
        if(!is_array($data['themedata'])){
            $data['themedata']=unserialize($data['themedata']);
            $defaultimage='/mobile/template/app/images/shake/shake2.png';
            $data['themedata']['mobileshakeimage']=empty($data['themedata']['mobileshakeimageid'])?$defaultimage:$this->_getfilepath($data['themedata']['mobileshakeimageid']);
        }
        return $data;
    }

    /**
     * 返回图片路径
     * 
     * @param int $attachmentid 附件的id
     * 
     * @return text 返回图片路径
     */
    private function _getfilepath($attachmentid)
    {
        $load=\Loader::getInstance();
        $load->model('Attachment_model');
        $attachmentinfo=$load->attachment_model->getById($attachmentid);
        return $attachmentinfo['filepath'];
    }
    /**
     * 保存一个主题
     * 
     * @return mixed false表示失败 正整数表示保存数据的id
     */
    public function saveTheme($data){
        $id=isset($data['id'])?intval($data['id']):0;
        unset($data['id']);
        if($id>0){
            $result=$this->_ydj_themes_m->update('id='.$id,$data);
            return $result?$id:false;
        }else{
            $result=$this->_ydj_themes_m->add($data);
            return $result;
        }
    }
    /**
     * 重置摇大奖的所有数据
     * 
     * @return void
     */
    public function clearData()
    {
        $this->_ydj_config_m->update('1', array('status=1,currentshow=1'));
        $this->_ydj_record_m->query('truncate table weixin_ydj_record');
    }
    
    
   
    /**
     * 开始游戏
     * 
     * @param int $id 活动id 0的话是当前活动
     * 
     * @return int 大于0保存成功小于0保存失败
     */
    public function startGame($id){
        $config=$this->getCurrentConfig($id);
        $config['status']=2;
        $newdata=array('id'=>$config['id'],'status'=>$config['status']);
        $result=$this->saveConfig($newdata);
        if($result>0){
            //游戏开始前先要清空活跃用户信息，保证之前的
            $this->_ydj_cache->set($this->_ydj_currentconfig_cachename, $config, 3*3600);
        }
        return $result;
    }

    public function resetGame($id){
        $config=$this->getCurrentConfig($id);
        $result1=$this->deleteRecords($id);
        $config['status']=1;
        $newdata=['id'=>$config['id'],'status'=>$config['status']];
        $result=$this->saveConfig($newdata);
        $prize_api=new \Modules\Prize\Controllers\Api();
        $result=$prize_api->resetprizes('ydj',$config['id']);
        if($result>0){
            $this->_ydj_cache->delete($this->_ydj_currentconfig_cachename);
            $this->_clearcache();
        }
        return $result;
    }
    /**
     * 加入游戏
     * 
     * @param array $userinfo 用户信息
     * @param int   $configid 摇大奖配置id
     * 
     * @return mixed -1没有通过审核不能参与 -2数据错误 1加入成功 2人已经满了
     */
    public function joinGame($userinfo,$configid)
    {
        $this->_addrecentjoinuser($userinfo['id']);
        if ($this->_redis_cache==null) {
            return $this->_joingame_common($userinfo);
        } else {
            return $this->_joingame_redis($userinfo);
        }
    }
    /**
     * 结束游戏
     * 
     * @param int $id 活动id 0的话是当前活动
     * 
     * @return int 大于0保存成功小于0保存失败
     */
    public function endGame($id){
        $config=$this->getCurrentConfig($id);
        $config['status']=3;
        $result=$this->saveConfig($config);
        if($result>0){
            $this->_ydj_cache->set($this->_ydj_currentconfig_cachename, $config, 3*3600);
        }
        return $result;
    }
    
    var $_recentjoinusers_cachename='recentjoinusers';
    /**
     * 添加一个用户到最近加入用户的信息名单中
     * 
     * @param int $userid 用户id
     * 
     * @return void
     */
    private function _addrecentjoinuser($userid)
    {
        if ($userid<=0) {
            return ;
        }
        $data=$this->_ydj_cache->get($this->_recentjoinusers_cachename);
        if (!$data) {
            $data=[$userid];
        } else {
            $data=unserialize($data);
            array_push($data, $userid);
        }
        $this->_ydj_cache->set($this->_recentjoinusers_cachename,serialize($data) , 3600);
    }
    /**
     * 获取最近加入的用户信息清单
     * 
     * @return array 用户清单
     */
    public function flushRecentJoinUsers()
    {
        $data=$this->_ydj_cache->get($this->_recentjoinusers_cachename);
        if (!$data) {
            return [];
        }
        $this->_ydj_cache->delete($this->_recentjoinusers_cachename);
        return unserialize($data) ;
    }
    /**
     * 获取最近加入的人的名单
     * 
     * @return array 用户清单
     */
    public function recentJoinUsers()
    {
        $userids=$this->flushRecentJoinUsers();
        $sum=0;
        $result=[];
        if ($this->_redis_cache==null) {
            $data=$this->_getuserlist_common();
            $userlist=$data['userlist'];
            for ($i=0,$l=count($userids);$i<$l;$i++) {
                array_push($result, $userlist[$userids[$i]]);
            }
            $sum=$data['sum'];
        } else {
            $resultdata=[];
            $sum=$this->_redis_cache->hlen($this->_cacheprefix.$this->_ydj_joinusers_cachename);
            if(!empty($userids)){
                $result=$this->_redis_cache->hmget($this->_cacheprefix.$this->_ydj_joinusers_cachename, $userids);
                $resultdata=[];
                foreach($result as $item){
                    $resultdata[]=unserialize($item);
                }
            }
            $result=$resultdata;
        }
        return ['sum'=>intval($sum), 'userlist'=>$result];
    }

    //获取用户信息
    public function getJoinUser($userid,$force=false){
        $userinfo=null;
        if ($this->_redis_cache==null) {
            $userinfo=$this->_getjoinuser_common($userid);
        } else {
            $userinfo=$this->_getjoinuser_redis($userid);
        }
        // return $userinfo;
        if($force==false){
            return $userinfo;
        }
        //这里逻辑有个错误，如果这个地方返回false的时候写了，那加入游戏会有问题，如果不写内定的得奖人信息会有问题
        if($userinfo==false || empty($userinfo)){
            $load=\Loader::getInstance();
            $load->model('Flag_model');
            $flag=$load->flag_model->getUserinfoById($userid);
            $userinfo=[
                'id'=>$flag['id'],
                'avatar'=>$flag['avatar'],
                'nickname'=>$flag['nickname'],
                'signname'=>$flag['signname'],
                'phone'=>$flag['phone'],
                // 'seed'=>1,//1表示可以参与，2表示不能参与
                'lasttime'=>time()
            ];
        }
        return $userinfo;
    }

    private function _getjoinuser_common($userid){
        $joindata=$this->_getuserlist_common();
        // echo var_export($data);

        // $joindata=unserialize($joindata);
        $userlist=$joindata['userlist'];
        if(isset($userlist[$userid])){
            return $userlist[$userid];
        }
        return null;
    }
    
    private function _getjoinuser_redis($userid){
        $data=$this->_redis_cache->hget($this->_cacheprefix.$this->_ydj_joinusers_cachename,$userid);
        return unserialize($data);
    }

    

    public function winPrize($activityid,$userid){
        $prize_api=new \Modules\Prize\Controllers\Api();
        $data=$prize_api->winprize('ydj',$activityid,$userid);
        if($data['code']>0){
            if($data['data']['prize']!=null){
                $this->addPrizedUsers($userid,$data['data']['prize']['id']);
            }
            return array('userid'=>$userid,'prize'=>$data['data']['prize']);
        }
        return null;
    }
    
    private $_prized_user_cachename='_prizedusers';
    private $_recent_winner_cachename='_recentwinners';

    //获取中奖信息
    public function getWinners($cache=false){
        $data=$this->_ydj_cache->get($this->_prized_user_cachename);
        $data=unserialize($data);
        if($cache==true){
            return $data;
        }
        if(!$data || empty($data)){
            $prize_api=new \Modules\Prize\Controllers\Api();
            $config=$this->getCurrentConfig();
            $data=$prize_api->getwinners('ydj',$config['id'],false);
            if($data['code']<=0){
                return [];
            }
            $returndata=[];
            foreach($data['data'] as $v){
                $returndata=$this->_addWinners($returndata,$v['userid'],$v['prizeid']);
            }
            if(count($returndata)<=0){
                return false;
            }
            $this->_ydj_cache->set($this->_prized_user_cachename,serialize($returndata));
            return $returndata;
        }
        return $data;
    }
    
    public function getWinnerByUserId($userid){
        $prizedusers=$this->getWinners();
        if(!isset($prizedusers[$userid])){
            return false;
        }
        return $prizedusers[$userid];
    }

    public function flushRecentWinners(){
        $data=$this->getRecentWinners();
        $lasttime=isset($data['lasttime'])?intval($data['lasttime']):0;
        $l=count($data['winners']);
        $index=0;
        $newlasttime=$lasttime;
        for($i=0,$l=count($data['winners']);$i<$l;$i++){
            if($data['winners'][$i]['time']<=$lasttime){
                $index=$i+1;
            }
            if($data['winners'][$i]['time']>$newlasttime){
                $newlasttime=$data['winners'][$i]['time'];
            }
        }
        $lasttime=$newlasttime;

        // $data['test'][0]=$data['winners'];
        $data['winners']=array_slice($data['winners'],$index);
        $data['lasttime']=$lasttime;
        // $data['test'][1]=$data['winners'];
        // $data['test'][2]=$index;
        // $data['test'][3]=$data['lasttime'];
        // echo var_export($data);
        $this->_ydj_cache->set($this->_recent_winner_cachename,serialize($data));
        return $data;
    }

    public function getRecentWinners(){
        $data=$this->_ydj_cache->get($this->_recent_winner_cachename);
        if(!$data){
            return ['winners'=>[],'lasttime'=>0];
        }
        return unserialize($data);
    }
    /**
     * 把中奖的用户添加到中奖名单里面
     * 
     * @return void
     */
    public function addPrizedUsers($userid,$prizeid){
        $prizedusers=$this->getWinners(true);
        if(!$prizedusers || empty($prizedusers)){
            $prizedusers=[];
        }
        $prizedusers= $this->_addWinners($prizedusers,$userid,$prizeid);

        $recentwinners=$this->getRecentWinners();
        $recentwinners=$this->_addrecentwinners($recentwinners,$userid,$prizeid);

        $data=serialize($prizedusers);
        $data2=serialize($recentwinners);
        $this->_ydj_cache->set($this->_prized_user_cachename,$data);
        $this->_ydj_cache->set($this->_recent_winner_cachename,$data2);
    }

    private function _addrecentwinners($olddata,$userid,$prizeid){
        $newitem=array('time'=>time(),'userid'=>$userid,'prizeid'=>$prizeid);
        array_push($olddata['winners'],$newitem);
        return $olddata;
    }

    private function _addWinners($olddata,$userid,$prizeid){
        if(!isset($olddata[$userid])){
            $olddata[$userid]['prizes'][$prizeid]=1;
            $olddata[$userid]['count']=1;
        }else{
            $olddata[$userid]['count']++;
            if(!isset($olddata[$userid]['prizes'][$prizeid])){
                $olddata[$userid]['prizes'][$prizeid]=1; 
            }else{
                $olddata[$userid]['prizes'][$prizeid]++;
            }
        }
        return $olddata;
    }

    public function getWinHistory($userid,$activityid){
        $prize_api=new \Modules\Prize\Controllers\Api();
        $data=$prize_api->getmyprizeshistory($userid,'ydj',$activityid);
        return $data;
    }
    /**
     * 获取参与的用户列表 通用缓存
     * 
     * @return array 用户列表及用户数
     */
    private function _getuserlist_common()
    {
        $data=$this->_ydj_cache->get($this->_ydj_joinusers_cachename);
        if (!$data) {
            $data=array('sum'=>0,'userlist'=>array());
        }else{
            $data=unserialize($data);
        }
        return $data;
    }

    
    /**
     * 加入游戏用户 通用缓存
     * 
     * @param array $userinfo 用户信息
     * @param int $configid 摇大奖配置id
     * @param int $maxplayers 最大加入人数
     * 
     * @return int 1加入成功 2人已经满了
     */
    private function _joingame_common($userinfo)
    {
        $data=$this->_ydj_cache->get($this->_ydj_joinusers_cachename);
        $data=unserialize($data);
        $currentuser=[
            'id'=>$userinfo['id'],
            'avatar'=>$userinfo['avatar'],
            'nickname'=>$userinfo['nickname'],
            'signname'=>$userinfo['signname'],
            'phone'=>$userinfo['phone'],
            // 'seed'=>1,//1表示可以参与，2表示不能参与
            'lasttime'=>time()
        ];
        
        if($data){
            if(!isset($data['userlist'][$userinfo['id']])){
                $sum=$data['sum'];
                $data['sum']=$data['sum']+1;
                $data['userlist'][$userinfo['id']]=$currentuser;
                $this->_ydj_cache->set($this->_ydj_joinusers_cachename,serialize($data));
                return $currentuser;
            }else{
                return $currentuser;
            }
        }else{
            $userlist=array();
            $userlist[$userinfo['id']]=$currentuser;
            $data=array('sum'=>1,'userlist'=>$userlist);
            $this->_ydj_cache->set($this->_ydj_joinusers_cachename,serialize($data));
            return $currentuser;
        }
    }


    /**
     * 加入游戏 redis缓存
     * 
     * @param array $userinfo 用户信息
     * @param int $configid 摇大奖配置id
     * @param int $maxplayers 最大加入人数
     * 
     * @return int 1加入成功 2人已经满了
     */
    private function _joingame_redis($userinfo)
    {
        $olduserinfo=$this->_redis_cache->hget($this->_cacheprefix.$this->_ydj_joinusers_cachename,$userinfo['id']);
        if(!$olduserinfo){
            $sum=$this->_redis_cache->hlen($this->_cacheprefix.$this->_ydj_joinusers_cachename);
            // $seed=$sum>$maxplayers?2:1;
            $currentuser=[
                'id'=>$userinfo['id'],
                'avatar'=>$userinfo['avatar'],
                'nickname'=>$userinfo['nickname'],
                'signname'=>$userinfo['signname'],
                'phone'=>$userinfo['phone'],
                // 'seed'=>$seed,//1表示可以参与，2表示不能参与
                'lasttime'=>time()
            ];
            $this->_redis_cache->hset($this->_cacheprefix.$this->_ydj_joinusers_cachename,$userinfo['id'],serialize($currentuser));
            return $currentuser;
        }else{
            // $olduserinfo=unserialize($olduserinfo);
            return $olduserinfo;
        }
        
        
    }

    var $_prizesnum_cache='_prizesnum';
    /**
     * 获取奖品数量
     */
    public function getPrizesnum($plugname,$activityid)
    {
        $data=$this->_ydj_cache->get($this->_prizesnum_cache);
        if (!$data || intval($data['totalnum'])==0) {
            $prize_api=new \Modules\Prize\Controllers\Api();
            $resultdata=$prize_api->getprizesnum('ydj',$activityid);
            if ($resultdata['code']>0) {
                $data=$resultdata['data'];
                $data['totalnum']=intval($data['totalnum']);
                $data['freezenum']=intval($data['freezenum']);
                $data['leftnum']=intval($data['leftnum']);
                $return = $this->_ydj_cache->set($this->_prizesnum_cache, $data, 5*60);
            } else {
                return false;
            }
        }
        return $data;
    }

    /**
     * 切换轮次时需要清空一些缓存
     * 
     * @return void
     */
    private function _clearcache()
    {
        $return=$this->_ydj_cache->delete($this->_prizesnum_cache);
        $this->_ydj_cache->delete($this->_prized_user_cachename);
        $this->_ydj_cache->delete($this->_recentjoinusers_cachename);
        $this->_ydj_cache->delete($this->_recent_winner_cachename);
        if ($this->_redis_cache==null) {
            $this->_ydj_cache->delete($this->_ydj_joinusers_cachename);
        }else{
            $this->_redis_cache->del($this->_cacheprefix.$this->_ydj_joinusers_cachename);
        }
    }

    //重置所有游戏
    public function resetAllGames(){
        $this->_clearcache();
        $sql="truncate table weixin_ydj_record";
        $this->_ydj_config_m->query($sql);
        $sql="update weixin_ydj_config set status=1 where 1";
        $this->_ydj_config_m->query($sql);
    }
}