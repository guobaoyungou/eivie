<?php
/**
 * 点大商城（www.diandashop.com） - 微信公众号小程序商城系统!
 * Copyright © 2020 山东点大网络科技有限公司 保留所有权利
 * =========================================================
 * 版本：V2
 * 授权主体：shop.guobaoyungou.cn
 * 授权域名：guobaoyungou.cn
 * 授权码：TZJcxBSGGdtDBIxFerKVJo
 * ----------------------------------------------
 * 您只能在商业授权范围内使用，不可二次转售、分发、分享、传播
 * 任何企业和个人不得对代码以任何目的任何形式的再发布
 * =========================================================
 */



namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class PlanTask extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->addArgument('method', Argument::OPTIONAL);
        $this->setName('plantask')
            ->setDescription('计划任务');
    }
    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
		ini_set('memory_limit', -1);
        error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^E_STRICT ^E_WARNING);
        $t1 = microtime(true);
        //获取cli参数，若参数不为空则单独执行该方法
        $args = $input->getArguments();
        if (!empty($args['method'])) {
            $method = $args['method'];
            $this->$method();
        } else {
            die('无效方法');
        }
        echo sprintf("执行成功，耗时： %f秒<br>", round(microtime(true)-$t1,3));
        echo 'success';exit;
    }

    public static function jiesuanall(){
        $info = Db::name('sysset')->where('name','webinfo')->find();
        $webinfo = json_decode($info['value'],true);
        if($webinfo['jiesuan_fenhong_type']==0){
            return true;
        }
        //文件锁，防止并发执行
        $file_name = ROOT_PATH.'runtime/task_lock.log';
        if(file_exists($file_name)){
            file_put_contents($file_name,date('Y-m-d H:i:s').'任务重复'."\r\n",FILE_APPEND);
            return true;
        }else{
            file_put_contents($file_name,date('Y-m-d H:i:s').'任务开始'."\r\n",FILE_APPEND);
        }
        //开始处理分红
       try {
            //执行分红的结算
            \app\common\Fenhong::jiesuanAll();
            //执行分红的发放
            $syssetlist = Db::name('admin_set')->where('1=1')->select()->toArray();

            foreach($syssetlist as $sysset) {
                $aid = $sysset['aid'];
                $map = [];
                $map[] = ['aid','=',$aid];
                $map[] = ['status','=',0];
                $lists = Db::name('member_fenhonglog')
                    ->where($map)
                    ->where('sendtime_yj>0 && sendtime_yj<'.time())
                    ->select()->toArray();
                if($lists){
                    \app\common\Fenhong::send_now($aid,$lists);
                    //将数据的sendtime_yj改为0，防止异步任务中一直查询未收货状态的数据
                    $ids = array_column($lists,'id');
                    Db::name('member_fenhonglog')->where('id','in',$ids)->where('status',0)->update(['sendtime_yj'=>0]);
                }
            }
        } catch (\Throwable $e) {
            // 请求失败
            writeLog($e, 'plantask');
            unlink($file_name);
            return true;
        }
        //执行完成删除锁文件
        unlink($file_name);
        return true;
    }

    public function day_release_greenscore(){
        if(getcustom('green_score_new')){
            //绿色积分按天自动释放，余锦州定制
            Db::startTrans();
            $syssetlist = Db::name('consumer_set')->where('1=1')->select()->toArray();
            foreach($syssetlist as $sysset) {
                dump('开始释放'.$sysset['aid']);
                $res = \app\custom\GreenScore::auto_day_release($sysset['aid'],$sysset);
            }
            Db::commit();
            dump('释放成功');
        }

    }

    //倍增返现释放
    public function cashbackMultiply(){
        if(getcustom('yx_cashback_multiply')){
            //文件锁，防止并发执行
            $file_name = ROOT_PATH.'runtime/plan_cashback_multiply.log';
            if(file_exists($file_name)){
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务重复'."\r\n",FILE_APPEND);
                return json_encode(['status'=>1,'msg'=>'重复执行']);
            }else{
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务开始'."\r\n",FILE_APPEND);
            }
            //倍增返现，每分钟执行一次
            try {
                \app\custom\OrderCustom::deal_autocashback_multiply();
            }catch (\Exception $e) {
                // 请求失败
                unlink($file_name);
            }
            //执行完成删除锁文件
            unlink($file_name);
            return json_encode(['status'=>1,'msg'=>'执行成功']);
        }
    }
    //消费补贴积分释放
    public function dealSubsidy(){
        if(getcustom('yx_buyer_subsidy')){
            set_time_limit(0);
            ini_set('memory_limit','1024M');
            $admin = Db::name('subsidy_set')->where('status',1)->field('aid')->select()->toArray();
            if($admin){
                foreach($admin as $v){
                    $aid = $v['aid'];
                    Db::startTrans();
                    $res = \app\custom\Subsidy::caclBonus($aid,0);
                    Db::commit();
                }
            }
        }
    }
    //新积分释放
    public function dealNewScore(){
        if(getcustom('yx_new_score')){
            set_time_limit(0);
            ini_set('memory_limit','1024M');
            $admin = Db::name('newscore_set')->where('status',1)->select()->toArray();
            if($admin){
                foreach($admin as $v){
                    $aid = $v['aid'];
                    Db::startTrans();
                    $res = \app\custom\NewScore::releaseScore($aid,$v);

                    if(getcustom('yx_new_score_active_batch')){
                        //发放账期记录
                        if($v['send_type']==0){
                            \app\custom\NewScore::send_batch_log($aid);
                        }
                    }
                    Db::commit();
                }
            }
        }

    }

    //异步处理会员升级
    public static function memberLevelUp(){
        if(getcustom('member_levelup_async')){
            //文件锁，防止并发执行
            $file_name = ROOT_PATH.'runtime/task_lock_level.log';
            if(file_exists($file_name)){
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务重复'."\r\n",FILE_APPEND);
                return true;
            }else{
                file_put_contents($file_name,date('Y-m-d H:i:s').'任务开始'."\r\n",FILE_APPEND);
            }
            try {
                //执行升级
                $list = Db::name('member_levelup_async_log')->where('status',0)->limit(15)->order('id desc')->select()->toArray();

                foreach($list as $v) {
                    $params = json_decode($v['params'],true);
                    $params['from_async'] = 1;
                    \app\common\Member::uplv($v['aid'],$v['mid'],$v['type'],$params);
                    Db::name('member_levelup_async_log')->where('id',$v['id'])->update(['status'=>1,'handle_time'=>time()]);

                    //相同会员前面没执行的记录改掉状态，计划任务只让执行最新的记录就可以了
                    Db::name('member_levelup_async_log')
                        ->where('aid',$v['aid'])
                        ->where('mid',$v['mid'])
                        ->where('status',0)
                        ->where('id','<',$v['id'])
                        ->update(['status'=>2]);
                }
            } catch (\Throwable $e) {
                // 请求失败
                writeLog($e, 'plantask_level');
                unlink($file_name);
                return true;
            }
            //执行完成删除锁文件
            unlink($file_name);
            return true;
        }
    }

    /**
     * 处理会员年龄标签赠送积分
     * @author: liud
     * @time: 2025/11/3 10:30
     */
    public static function memberTagAgeGiveScore(){

        if(getcustom('member_tag_age')) {
            //定时每月初1点执行-会员年龄标签每月发放积分
            $time = (int)date("H", time());
            if ($time == 1) {
                $can = true;
                //确保一个月执行一次
                $member_tag_age_time = cache('member_tag_age');
                $n_time = strtotime(date("Y-m", time()));
                if (!$member_tag_age_time) {
                    cache('member_tag_age', $n_time);
                } else {
                    if ($member_tag_age_time == $n_time) {
                        $can = false;
                    } else {
                        cache('member_tag_age', $n_time);
                    }
                }
                if ($can) {
                    set_time_limit(0);
                    ini_set('memory_limit', -1);
                    $admin = Db::name('admin')->where('status', 1)->field('id')->select()->toArray();
                    if ($admin) {
                        foreach ($admin as $v) {
                            $aid = $v['id'];

                            //查询所有有标签的会员
                            if($m_arr = Db::name('member')->where('aid',$aid)->whereNotNull('tags')->field('id,score,tags,tag_age_score')->select()->toArray()){
                                foreach ($m_arr as $v){
                                    if($v['tags']){
                                        $tags = explode(',',$v['tags']);
                                        if($tags){

                                            //排序大的先处理
                                            $sort_tags = Db::name('member_tag')->where('aid',$aid)->where('id','in',$tags)->order('sort desc')->select()->toArray();

                                            foreach ($sort_tags as $tag_info){
                                                //获取每个月赠送的积分
                                                //$tag_info = Db::name('member_tag')->where('aid',$aid)->where('id',$vv)->where('give_score','>',0)->field('id,name,give_score,give_score_cover')->find();
                                                if($tag_info){
                                                    if($tag_info['give_score_cover'] == 0){//每月积分不覆盖

                                                        //如果赠送的积分大于0
                                                        if($tag_info['give_score'] > 0){
                                                            \app\common\Member::addscore($aid,$v['id'],$tag_info['give_score'],'会员标签['.$tag_info['name'].']每月赠送','',0,$tag_info['id']);

                                                            //增加年龄标签赠送的积分字段
                                                            $tag_age_score = $v['tag_age_score'] + $tag_info['give_score'];
                                                            Db::name('member')->where('aid',$aid)->where('id',$v['id'])->update(['tag_age_score'=>$tag_age_score]);
                                                        }

                                                    }else{//每月积分覆盖
                                                        //先减剩余积分
                                                        if($v['score'] > 0){
                                                            \app\common\Member::addscore($aid,$v['id'],-$v['score'],'会员标签['.$tag_info['name'].']每月赠送前清空上月积分余量','',0,$tag_info['id']);
                                                        }

                                                        if($tag_info['give_score'] > 0){
                                                            //增加积分记录
                                                            \app\common\Member::addscore($aid,$v['id'],$tag_info['give_score'],'会员标签['.$tag_info['name'].']每月赠送','',0,$tag_info['id']);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                        }
                        unset($v);
                    }
                }
            }
        }
    }

}