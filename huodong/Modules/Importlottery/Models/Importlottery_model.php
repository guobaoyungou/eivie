<?php
namespace Modules\Importlottery\Models;
class Importlottery_model{
    var $_importlotterycolumns_m=null;
    var $_importlottery_m=null;
    var $_cache=null;
    var $load=null;
    public function __construct(){
        $this->_importlottery_m=new \M('importlottery');
        $this->_importlotterycolumns_m=new \M('importlotterycolumns');
        $this->_view_importlottery_m=new \M('view_importlottery');
        $this->_cache=new \CacheFactory(CACHEMODE);
        $this->load=\Loader::getInstance();
    }
    //保存导入的数据
    public function saveData($data){
        $this->_importlottery_m->query('TRUNCATE TABLE weixin_importlottery');
        $data_arr=[];
        foreach($data as $item){
            $newitem=[
                'col1'=>(isset($item[0]) && !empty($item[0]))?strval($item[0]):'',
                'col2'=>(isset($item[1]) && !empty($item[1]))?strval($item[1]):'',
                'col3'=>(isset($item[2]) && !empty($item[2]))?strval($item[2]):''
            ];
            $this->_importlottery_m->add($newitem);
        }
        return true;
    }
    /**
     * 获取导入的数据
     */
    public function getAllData($searchtxt='',$page,$pagesize){
        $page=$page<=0?1:$page;
        $pagesize=$pagesize<=0?30:$pagesize;
        $where='';
        if(!empty($searchtxt)){
            $where=' and (col1 like "%'.$searchtxt.'%" or col2 like "%'.$searchtxt.'%" or col3 like "%'.$searchtxt.'%")';
        }
        $limit=' limit '.(($page-1)*$pagesize).','.$pagesize;
        $data['data']=$this->_importlottery_m->select(' 1=1 '.$where.' order by id desc'.$limit);
        foreach($data['data'] as $k=>$v){
            $v=$this->_processItem($v);
            $data['data'][$k]=$v;
        }
        $data['count']=$this->_importlottery_m->select(' 1=1 '.$where,'*','count');
        return $data;
    }
    /**
     * 获取所有数据
     */
    public function getAll($hashtable=false,$searchtxt=''){
        $where='1 = 1 ';
        if(!empty($searchtxt)){
            $where.=' and (col1 like "%'.$searchtxt.'%" or col2 like "%'.$searchtxt.'%" or col3 like "%'.$searchtxt.'%")  order by id desc limit 20';
        }
        $data=$this->_importlottery_m->select($where);
        if($hashtable==true){
            $newdata=[];
            foreach($data as $k=>$v){
                $newdata[$v['id']]=$v;
            }
            return $newdata;
        }
        return $data;
    }
    /**
     * 按id获取数据
     */
    public function getById($id){
        $data=$this->_importlottery_m->find('id='.$id);
        $data=$this->_processItem($data);
        return $data;
    }
    /**
     * 保存一条数据
     */
    public function saveDataItem($data){
        $id=isset($data['id'])?intval($data['id']):0;
        unset($data['id']);
        if($id>0){
            return $this->_importlottery_m->update('id='.$id,$data);
        }else{
            return $this->_importlottery_m->add($data);
        }
    }

    private function _processItem($item){
        
        $this->load->model('Attachment_model');
        $item['imgid']=isset($item['imgid'])?intval($item['imgid']):0;
        if($item['imgid']>0){
            $attachmentinfo=$this->load->attachment_model->getById($item['imgid']);
            $item['imagepath']=$attachmentinfo['filepath'];
        }else{
            $item['imagepath']='';
        }
        return $item;
    }
    /**
     * 更新列信息
     */
    public function saveColumnName($data){
        $newdata=[
            'col_name1'=>$data[0],
            'col_name2'=>$data[1],
            'col_name3'=>$data[2],
        ];
        $returndata=$this->_importlotterycolumns_m->update(' id=1 ',$newdata);
        return $returndata;
    }
    /**
     * 获取列的信息
     * 
     */
    public function getColumnname(){
        $data=$this->_importlotterycolumns_m->find(' id=1 limit 1');
        $data['col_name1']=empty($data['col_name1'])?'无':$data['col_name1'];
        $data['col_name2']=empty($data['col_name2'])?'无':$data['col_name2'];
        $data['col_name3']=empty($data['col_name3'])?'无':$data['col_name3'];
        return $data;
    }
    /**
     * 获取随机数据
     * 
     */
    public function getRandData($num=20){
        $data=$this->_importlottery_m->select('1=1 order by rand() limit 0,'.$num);
        foreach($data as $k=>$v){
            $data[$k]=$this->_processItem($v);
        }
        return $data;
    }
    /**
     * 删除一条记录
     */
    public function deleteById($id){
        return $this->_importlottery_m->delete('id='.$id);
    }

    /**
     * 获取随机的中奖名单 
     */
    public function getRandZjlist($num,$prizeid){
        $result=[];
        $sql=' designated=2 and status=1 and prizeid='.$prizeid.' limit '.$num;
        $data=$this->_view_importlottery_m->select($sql,'id,col1,col2,col3,imgid,designated,status');
        $left=$num;
        if(is_array($data)){
            foreach($data as $v){
                $item=$this->_processItem($v);
                $result[]=$item;
            }
            $left=$num-count($data);
        }
        $sql='select * from weixin_importlottery where (select count(1) from weixin_view_importlottery where weixin_view_importlottery.id=weixin_importlottery.id and (designated=3 and prizeid='.$prizeid.'  or (status=2 or status=3) or (prizeid!='.$prizeid.' and designated=2)))=0 order by rand() limit 0,'.$left;
        $r=$this->_view_importlottery_m->query($sql);
        $data2=$this->_view_importlottery_m->fetch_array($r);
        foreach($data2 as $v){
            $item=$this->_processItem($v);
            $result[]=$item;
        }
        return $result;
    }

    /**
     * 获取当前奖项的中奖名单
     * 
     * @param integer $prizeid 奖品id
     * 
     * @return array 中奖的名单
     */
    public function getWinners($prizeid){
        $data=$this->_view_importlottery_m->select(' 1=1 and (status=2 or status=3) and prizeid='.$prizeid);
        $result=[];
        foreach($data as $v){
            $item=$this->_processItem($v);
            $result[]=$item;
        }
        return $result;
        // return $data;
    }
    public function getAllWinners(){
        $data=$this->_view_importlottery_m->select(' 1=1 and (status=2 or status=3)');
        return $data;
    }

    /**
     * 统计数据
     * 
     */
    public function countData(){
        $data=$this->_importlottery_m->find('1=1 ','*','count');
        return $data;
    }

    public function resetLottery(){

    }
    
    public function getLeftDataCount($prizeid){
        $data=$this->_view_importlottery_m->select(' 1=1 and (status=2 or status=3)','id');
        $newdata=[];
        foreach($data as $v){
            $newdata[$v['id']]=1;
        }
        $data=$this->_view_importlottery_m->select(' 1=1 and ((designated=2 and prizeid!='.$prizeid.')  or (designated=3 and prizeid='.$prizeid.'))','id');
        foreach($data as $v){
            $newdata[$v['id']]=1;
        }
        $count=0;
        foreach($newdata as $v){
            $count++;
        }
        
        $total=$this->_importlottery_m->find('1=1 ','*','count');
        return intval($total)-intval($count);
    }
}