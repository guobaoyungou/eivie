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

namespace app\common;
use think\facade\Db;
use think\facade\Log;
class PayReturn
{
   
    public function index(){
        $request = $_REQUEST;
        $type =  $request['type'];
        if($type =='quickpay'){
            $this-> quickpay($request);
        }
        if($type == 'shangfutong'){
            $this->shangfutong($request);
        }
    }
    public function quickpay($request=[]){
        //汇付快捷支付页面支付完成点击返回后，请求的链接是post,造成405不允许，加此页面进行中转跳转
        $aid = $request['aid'];
        if(getcustom('pay_huifu_quickpay',$aid)){
            if($request['url']){
                if(in_array($request['p'],['h5','mp'])){

                    echo '<script type="text/javascript">window.location.href="'.m_url(urldecode($request['url']),$aid).'";</script>';
                    exit; // 确保脚本停止执行
                }elseif ($request['p'] =='app'){
                    echo '<!DOCTYPE html><html>'."\r\n";
                    echo '<head>'."\r\n";
                    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\r\n";
                    echo '<meta name="viewport" content="width=device-width,minimum-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,initial-scale=1.0,user-scalable=no" />'."\r\n";
                    echo '<meta name="robots" content="index, follow" />'."\r\n";
                    echo '<title>信息提示</title>'."\r\n";
                    echo '<script type="text/javascript" src="/static/js/uni.webview.1.5.4.js"></script>'."\r\n";
                    echo '</head>'."\r\n";
                    echo '<body>'."\r\n";
                    echo '<h2 style="text-align:center;padding-top:100px">跳转中...</h2>'."\r\n";
                    echo '<script>'."\r\n";
                    echo 'document.addEventListener(\'UniAppJSBridgeReady\', function() {'."\r\n";
                    echo '	uni.redirectTo({'."\r\n";
                    echo '	  url:"'.$request['url'].'"'."\r\n";
                    echo '	});'."\r\n";
                    echo '});'."\r\n";
                    echo '</script>'."\r\n";
                    echo '</body></html>';
                    die;
                }else{

                }
            }
        }
    }

    public function shangfutong($request=[]){
        //商福通代付回调
        $aid = $request['aid'];
        if(getcustom('shangfutong_daifu',$aid)){
            $input  = file_get_contents('php://input');
            if (empty($input)) exit('fail');

            writeLog('================== 商福通无忧付回调 =========================','shangfutong');
            writeLog($input,'shangfutong');

            $params = json_decode($input, true);
            if (!is_array($params) || empty($params['bizData'])) exit('fail');

            $bizData  = json_decode($params['bizData'], true);
            if (empty($bizData)) exit('fail');

            $record = Db::name('shangfutong_log')->where('ordernum',$bizData['mchOrderNo'])->find();
            if(empty($record)) exit('fail');
            $aid = $record['aid'];

            //验签
            $sft = new \app\custom\Shangfutong($record['aid']);
            $checkSign = $sft->requestDecode($params);
            if (!$checkSign) {
                writeLog('回调验签失败','shangfutong');
                exit('fail');
            }

            $wid = $record['wid'];
            $type = $record['withdraw_type'];
            $tableName = '';
            $updateData = [];

            switch ($type) {
                case 1:
                    //余额提现
                    $tableName = 'member_withdrawlog';
                    break;
                case 2:
                    //佣金提现
                    $tableName = 'member_commission_withdrawlog';
                    break;
                case 3:
                    //商家余额提现
                    $tableName = 'business_withdrawlog';
                    break;
                default:
                    exit('fail');
            }
            if ($bizData['state'] == 2) {
                //转账中
                $updateData = ['status' => 3, 'paytime' => time()];
            } elseif ($bizData['state'] == 3) {
                //转账失败
                $updateData = ['status' => 2, 'reason' => $bizData['errMsg'] ?? '商福通代付失败'];
                //返回扣款金额
                $withdrawlog = Db::name($tableName)->where('aid', $aid)->where('id', $wid)->find();
                if ($withdrawlog){
                    if($type == 3){
                        \app\common\Business::addmoney($aid,$withdrawlog['bid'],$withdrawlog['txmoney'],t('余额').'提现返还');
                    }elseif($type == 1){
                        \app\common\Member::addmoney($aid,$withdrawlog['mid'],$withdrawlog['txmoney'],t('余额').'提现返还');
                    }elseif($type == 2){
                        \app\common\Member::addcommission($aid,$withdrawlog['mid'],0,$withdrawlog['txmoney'],t('佣金').'提现返还');
                    }
                }
            } else {
                //0-订单生成
                //1-转账中,
                exit('SUCCESS');
            }

            Db::name($tableName)->where('aid', $aid)->where('id', $wid)->update($updateData);
            Db::name('shangfutong_log')->where('aid', $aid)->where('id', $record['id'])->update(['status' => $bizData['state'],'notify' => json_encode($bizData, JSON_UNESCAPED_UNICODE)]);
            exit('SUCCESS');
        }
    }

}