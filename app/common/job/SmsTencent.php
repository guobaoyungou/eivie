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



namespace app\common\job;

use think\facade\Db;
use think\queue\job;
use think\facade\Log;

class SmsTencent
{
    public $delay = 3;
    public function fire(Job $job, $data){

        if ($job->attempts() > 3) {
            //通过这个方法可以检查这个任务已经重试了几次了
            $this->failed($job, $data);
            $job->delete();
        }

        //....这里执行具体的任务
        Log::write("tencentsms");
        Log::write($data);
        $rs = \app\common\Sms::tencentsms($data['aid'],$data['smsset']['accesskey'],$data['smsset']['accesssecret'],$data['smsset']['sdkappid'],$data['smsset']['sign_name'],$data['templateCode'],$data['tel'],$data['params']);
        if($rs['status'] == 1) {
            //如果任务执行成功后 记得删除任务，不然这个任务会重复执行，直到达到最大重试次数后失败后，执行failed方法
            $job->delete();
            return ;
        }

        // 也可以重新发布这个任务
//        $job->release($this->delay); //$delay为延迟时间

    }

    public function failed($job, $data){
        // ...任务达到最大重试次数后，失败了
        $jobInfo = Db::name('jobs')->where('id', $job->getJobId())->find();
        if($jobInfo)
            Db::name('jobs_failed')->insert($jobInfo);
        Log::write("dismiss job has been retried more that 3 times");
        Log::write($data);
    }
}