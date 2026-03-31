<?php
/**
 * 摇一摇模块
 * PHP version 5.4+
 *
 * @category Models
 *
 * @package Flag
 *
 * */
require_once dirname(__FILE__) .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'basemodel.php';
require_once dirname(__FILE__) .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR."common".DIRECTORY_SEPARATOR."CacheFactory.php";
/**
 * 摇一摇模块
 * PHP version 5.4+
 *
 * @category Models
 *
 * @package Flag
 *
 * */
class Flag_model extends Basemodel
{
    var $_flag_config_m=null;
    var $_flag_reserved_infomation_m=null;
    var $_flag_m=null;
    var $_flag_extention_column_type_m=null;
    var $_flag_extension_data_m=null;
    var $_flag_count_cache='flag_count';
    var $_flags_cachename='flags';
    var $_flags_cache=null;
    /**
     * 构造函数
     */
    function __construct()
    {
        $this->_flag_m=new M('flag');
        $this->_flag_config_m=new M('flag_config');
        $this->_flag_extention_column_type_m=new M('flag_extention_column_type');
        $this->_flag_extension_data_m=new M('flag_extention_data');
        $this->_flag_reserved_infomation_m=new M('flag_reserved_infomation');
        $this->_cache=new CacheFactory(CACHEMODE);
        if (CACHEMODE=="Redis") {
            $this->_flags_cache=new \Predis\Client(
                array(
                    'scheme' => 'tcp',
                    'host'   => REDIS_HOST,
                    'port'   => REDIS_PORT,
                    'password'=> REDIS_PASSWORD
                )
            );
            $this->_cacheprefix=CACHEPREFIX;
            // $this->_prizescachename='prizes';
        }
    }
    //保存提交的数据
    function saveExtentionData($data)
    {
        if ($this->getExtentionDataByUserId($data['userid'])) {
            $userid=$data['userid'];
            unset($data['userid']);
            return $this->_flag_extension_data_m->update('userid='.$userid, $data);
        }
        $data['created_at']=time();
        return $this->_flag_extension_data_m->add($data);
    }
    //按照
    function getExtentionDataByUserId($userid)
    {
        return $this->_flag_extension_data_m->find('userid=>'.$userid);
    }
    //删除1个字段
    function deleteExtentionColumnsById($id)
    {
        $data=$this->_flag_extention_column_type_m->delete('id='.$id);
        return $data;
    }

    //获取一个自定义扩展字段信息view_lottery
    function getExtentionColumnsById($id)
    {
        $data=$this->_flag_extention_column_type_m->find('id='.$id);
        return $data;
    }
    //获取自定义扩展字段信息
    function getExtentionColumns()
    {
        $data=$this->_flag_extention_column_type_m->select('1 order by ordernum asc,created_at asc');
        return $data;
    }
    //保存扩展字段
    function saveExtentionColumn($column)
    {
        $id=isset($column['id'])?intval($column['id']):0;
        if ($id>0) {
            unset($column['id']);
            $this->_flag_extention_column_type_m->update('id='.$id, $column);
            return $id;
        } else {
            $column['created_at']=time();
            return $this->_flag_extention_column_type_m->add($column);
        }
    }

    //清空用户信息
    function clearUserInfo()
    {
        $this->_cache->set($this->_flag_count_cache, 0, 6*3600);
        if ($this->_flags_cache) {
            $this->_flags_cache->del($this->_cacheprefix.$this->_flags_cachename);
        }
        $this->_flag_extension_data_m->query('truncate table weixin_flag_extention_data');
        return $this->_flag_m->query('truncate table weixin_flag');
    }
    /**
     * 增加一个签到数字
     *
     * @return void
     */
    function incrSignedCount()
    {
        $countsign=$this->getSignedCount();
        $countsign++;
        $this->_cache->set($this->_flag_count_cache, $countsign, 6*3600);
    }
    /**
     * 减少一个签到数字
     */
    function decrSignedCount()
    {
        $countsign=$this->getSignedCount();
        $countsign--;
        $countsign=$countsign<0?0:$countsign;
        $this->_cache->set($this->_flag_count_cache, $countsign, 6*3600);
    }
    /**
     * 获取已经签到的人的数量
     *
     * @return int 签到人数
     */
    function getSignedCount()
    {
        $countsign=$this->_cache->get($this->_flag_count_cache);
        if (empty($countsign)) {
            $countsign=$this->_getUsersCount('flag=2');
            $this->_cache->set($this->_flag_count_cache, $countsign, 6*3600);
        }
        return $countsign;
    }

    //获取通过审核的人数
    function getShenheCount()
    {
        return $this->_getUsersCount('flag=2 and status=1');
    }

    //按照openid获取用户相关的信息
    function getUserinfo($openid, $withextentions = false, $withreservedinfomation = false)
    {
        $where='openid="'.$openid.'"';
        $join='';
        $select='*';
        if ($withreservedinfomation) {
            $flagconfig=$this->getConfig();
            if ($flagconfig['reserved_infomation_match']==1) {
                $join='left join weixin_flag_reserved_infomation on (weixin_flag_reserved_infomation.realname=weixin_flag.signname or  weixin_flag_reserved_infomation.phone=weixin_flag.phone) ';
            } else {
                $join='left join weixin_flag_reserved_infomation on (weixin_flag_reserved_infomation.realname=weixin_flag.signname and  weixin_flag_reserved_infomation.phone=weixin_flag.phone) ';
            }
            $select='weixin_flag.*,weixin_flag_reserved_infomation.info,weixin_flag_reserved_infomation.realname as verify_realname,weixin_flag_reserved_infomation.phone as verify_phone';
        }
        return $this->_flag_m->find('openid="'.$openid.'"', $select, '', 'assoc', $join);
    }

    //按照id获取用户相关信息
    function getUserinfoById($id)
    {
        $data=null;
        if ($this->_flags_cache) {
            $data=unserialize($this->_flags_cache->hget($this->_cacheprefix.$this->_flags_cachename, $id)) ;
            if (!$data) {
                $data=$this->_flag_m->find('id="'.$id.'"', '*');
                $data['nickname']=pack('H*', $data['nickname']);
                $this->_flags_cache->hset($this->_cacheprefix.$this->_flags_cachename, $id, serialize($data));
            }
        } else {
            $data=$this->_flag_m->find('id="'.$id.'"', '*');
            if ($data) {
                $data['nickname']=pack('H*', $data['nickname']);
            }
        }
        return $data;
    }
    //获取当前最大的审核编号
    function getMaxSignorder()
    {
        $signorder=$this->_flag_m->find('1 limit 0,1', 'signorder', 'max');
        $signorder=isset($signorder)?intval($signorder):0;
        return $signorder;
    }
    /**
     * 保存用户信息，目前用于确认签到
     * 头像改成了https的路径
     * 
     */
    function saveUserinfo($info)
    {
        $openid=$info['openid'];
        if ($openid=='') {
            return false;
        }
        $data=$this->_flag_m->find("openid='" . $openid. "'");
        $result=false;
        $id=0;
        $newdata=[];
        if ($data) {
            $newdata=array_merge($data, $info);
            unset($newdata['openid']);
            unset($newdata['id']);
            if(isset($newdata['avatar'])){
                $newdata['avatar']=str_replace('http://','https://',$newdata['avatar']);
            }
            $result=$this->_flag_m->update("openid='" . $openid . "'", $newdata);
            if ($this->_flags_cache) {
                $this->_flags_cache->hdel($this->_cacheprefix.$this->_flags_cachename, [$id]);
            }
            $id=$newdata['id'];
        } else {
            if(isset($info['avatar'])){
                $info['avatar']=str_replace('http://','https://',$info['avatar']);
            }
            $newdata=$info;
            $result=$this->_flag_m->add($newdata);
            $id=$result;
        }
        if ($result) {
            // if ($id>0 && $this->_flags_cache) {
            //     $newdata['nickname']=pack('H*', $newdata['nickname']);
            //     $this->_flags_cache->hset($this->_cacheprefix.$this->_flags_cachename, $id, serialize($newdata));
            // }
            return true;
        } else {
            return false;
        }
    }

    //获取查询结果的分页数据
    function getPagedData($where, $page, $pagesize, $joinextention = false)
    {
        $data=$this->_getPagedUsers($where, $page, $pagesize, $joinextention);
        $count=$this->_getUsersCount($where);
        return array('data'=>$data,'count'=>$count);
    }
    //获取审核用户的数据
    function getShenheUsers($lastsignorder = 0, $num = 50)
    {
        $where='signorder>'.$lastsignorder.' and flag=2 and status=1 order by signorder asc';
        $data=$this->_getPagedUsers($where, 1, $num);
        return $data;
    }
    //获取最近签到的人信息
    function getRecentSignedUsers($num = 30, $lastsignorder = 0)
    {
        $where='flag=2 and status=1 order by signorder desc';
        if ($lastsignorder>0) {
            $where='signorder > '.$lastsignorder.' and '.$where;
        }
        $returndata=$this->_getPagedUsers($where, 1, $num);
        return $returndata;
    }

    //保存扫码后由微信提供的用户信息
    function saveRemoteUserinfo($info)
    {
        //解决转义符
        //json字符串转化成数组
        $infoarr=null;
        if (is_array($info)) {
            $infoarr=$info;
        } else {
            $infoarr = json_decode($infoarr, true);
        }
        $flag_info=$this->_flag_m->find("openid='" . $infoarr['openid'] . "'");
        if (intval($flag_info['flag'])>=2) {
            return true;
        }
        $flag_info_new = array(
            "openid"=>$infoarr['openid'],
            "rentopenid"=>$infoarr['rentopenid'],
            "nickname" => $infoarr['nickname'],
            "avatar" => $infoarr['headimgurl'],
            "sex" => $infoarr['sex'],
            "fromtype" => 'weixin',
            "datetime" => time(),
            "flag" => "1"
        );
        return $this->saveUserinfo($flag_info_new);
    }

    //添加预留信息
    public function addReservedInfomation($data)
    {
        $result=$this->_flag_reserved_infomation_m->add($data);
        return $result;
    }
    //获取所有预留信息
    public function getAllReservedInfomation()
    {
        $result=$this->_flag_reserved_infomation_m->select('1=1');
        return $result;
    }

    public function clearReservedInfomation()
    {
        $this->_flag_reserved_infomation_m->query('truncate table weixin_flag_reserved_infomation');
    }
    //获取全部的数据
    public function getAllUsers()
    {
            $where=' 1 order by weixin_flag.id asc';
            $join='left join weixin_flag_extention_data on weixin_flag_extention_data.userid=weixin_flag.id';
            $columns='weixin_flag.nickname,weixin_flag.avatar,weixin_flag.signname,weixin_flag.phone,weixin_flag.datetime,weixin_flag_extention_data.datastr';
            $data=$this->_flag_m->select($where, $columns, '', 'assoc', $join);
            foreach ($data as $k => $v) {
                $data[$k]['extentions']=unserialize($v['datastr']);
            }
            return $data;
    }
    //按照条件获取用户列表
    private function _getPagedUsers($where, $page, $pagesize, $joinextention = false)
    {
        $page=$page>0?$page:1;
        $wherecondition=$where. ' limit '.(($page-1)*$pagesize).','.$pagesize;
        if ($joinextention==true) {
            $join='left join weixin_flag_extention_data on weixin_flag_extention_data.userid=weixin_flag.id';
            $data=$this->_flag_m->select($wherecondition, 'weixin_flag.*,weixin_flag_extention_data.datastr', '', 'assoc', $join);
            foreach ($data as $k => $v) {
                $data[$k]['extentions']=unserialize($v['datastr']);
            }
        } else {
            $data=$this->_flag_m->select($wherecondition, '*');
        }
        
        return $data;
    }

    //保存配置
    public function setConfig($data)
    {
        return $this->_flag_config_m->update('1', $data);
    }

    //读取配置
    public function getConfig()
    {
        $data=$this->_flag_config_m->find('1');
        return $data;
    }

    //按照条件获取用户的数量
    private function _getUsersCount($where)
    {
        $count=$this->_flag_m->find($where, '*', 'count');
        return $count;
    }
    /**
     * 获取随机n个已经签到并通过审核的人的名单
     *
     * @param integer $num 选取的人数
     * @param array   $exceptuserids 不包含的用户id
     *
     * @return array 随机取出的用户列表
     */
    public function getRandUsers($num, $exceptuserids = [], $select = '*')
    {
        $where = ' flag=2 and  status = 1 ';
        if (!empty($exceptuserids)) {
            $where .= ' and id not in(' .join($exceptuserids, ',').') ';
        }
        $limit ='limit 0,'.$num;
        $order_by=' order by rand() ';
        $users=$this->_flag_m->select($where.$order_by.$limit, $select);
        return $users;
    }
}
