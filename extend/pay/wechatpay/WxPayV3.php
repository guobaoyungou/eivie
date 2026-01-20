<?php
namespace pay\wechatpay;

use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use WeChatPay\Formatter;
use think\facade\Db;
use WeChatPay\Crypto\AesGcm;
use think\exception\HttpException;
class WxPayV3
{
    public $merchantId = '';//商户ID
    // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
    public $merchantPrivateKeyFilePath = '';//私钥文件路径;
    public $merchantPrivateKeyInstance = '';//从私钥中获取的私钥实例
    // 「商户API证书」的「证书序列号」
    public $merchantCertificateSerial = '';
    // 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名
    /******************支付平台证书与支付公钥二选一使用 start****************************/
    public $platformCertificateFilePath = '';//证书路径
    public $platformPublicKeyInstance = '';//微信支付平台证书内容
    // 从「微信支付平台证书」中获取「证书序列号」
    public $platformCertificateSerial = '';
    public $platformPublicKeyId = '';//微信支付公钥ID
    public $platformPublicKeyFilePath = '';//微信支付公钥文件路径
    /******************支付平台证书与支付公钥二选一使用 end****************************/
    public $instance;//构造的V3实例
//    public $notify_url =  'https://v2d.diandashop.de/notify_v3_transfer.php';
    public $notify_url =   PRE_URL.'/notify_v3_transfer.php';
    public $appid = '';
    public $mchkey = '';//apiv3密钥
    public $openid = '';//用户openid
    public $appinfo = [];//支付相关配置内容
    public $member = [];//会员信息
    public $aid = 0;
    public $sign_type = 0;//签名方式0平台证书 1支付公钥
    public function __construct($aid=1,$mid=0,$platform='wx',$is_curl=0){
        $member = Db::name('member')->where('id',$mid)->field('id,realname,wxopenid,mpopenid,appopenid')->find();
        writeLog('请求平台'.$platform,'wxpay_app');
        writeLog('会员信息'.json_encode($member),'wxpay_app');
        if(!$platform || $platform=='pc'){
            $openid = $member['mpopenid'];
            if(!$openid){
                $platform = 'wx';
            }else{
                $platform = 'mp';
            }
        }
        if($platform == 'wx'){ //小程序
            $openid = $member['wxopenid'];
            $appinfo = \app\common\System::appinfo($aid,'wx');
            if(empty($appinfo) || $appinfo['wxpay'] == 0) {
                if(!$is_curl){
                    showmsg('请先配置并开启微信小程序支付');exit;
                }else{
                    throw new HttpException(500, '请先配置并开启微信小程序支付');
                }

//                $openid = $member['mpopenid'];
//                $appinfo = \app\common\System::appinfo($aid,'mp');
            }
        }elseif($platform == 'app'){
            $openid = $member['appopenid'];
            $appinfo = \app\common\System::appinfo($aid,'app');
            if(empty($appinfo) || $appinfo['wxpay'] == 0) {
                if(!$is_curl){
                    showmsg('请先配置并开启APP端微信支付');exit;
                }else{
                    throw new HttpException(500, '请先配置并开启微信小程序支付');
                }
            }
        }else{ //公众号网页
            $openid = $member['mpopenid'];
            $appinfo = \app\common\System::appinfo($aid,'mp');
            if(empty($appinfo) || $appinfo['wxpay'] == 0) {
                if(!$is_curl) {
                    showmsg('请先配置并开启微信公众号支付');
                    exit;
                }else{
                    throw new HttpException(500, '请先配置并开启微信公众号支付');
                }
//                $openid = $member['wxopenid'];
//                $appinfo = \app\common\System::appinfo($aid,'wx');
            }
        }
        writeLog('会员$openid'.$openid,'wxpay_app');
        writeLog('$appinfo'.json_encode($appinfo),'wxpay_app');
        if(!$openid) {
            if(!$is_curl) {
                showmsg('未查找到' . t('会员') . 'openid');
            }else{
                throw new HttpException(500, '未查找到' . t('会员') .$mid. 'openid');
            }
        }
        if(empty($appinfo)) {
            if(!$is_curl) {
                showmsg('请先配置微信公众号或者微信小程序支付');
            }else{
                throw new HttpException(500, '请先配置微信公众号或者微信小程序支付');
            }
        }

        $this->merchantId =  $appinfo['wxpay_mchid'];;
        // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
        $private_key = file_get_contents(ROOT_PATH.$appinfo['wxpay_apiclient_key']);
        $this->merchantPrivateKeyFilePath = $private_key;
        $this->merchantPrivateKeyInstance = Rsa::from($this->merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
        // 「商户证书」的「证书序列号」
        $this->merchantCertificateSerial = $appinfo['wxpay_serial_no'];
        /******************支付平台证书与支付公钥二选一使用 start****************************/
        $certs = [];
        if($appinfo['sign_type']==0){
            //使用平台证书方式
            if(empty($appinfo['wxpay_wechatpay_pem'])){
                if(!$is_curl) {
                    showmsg('请先配置微信支付平台证书');
                }else{
                    throw new HttpException(500, '请先配置微信支付平台证书');
                }
            }
            // 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名
            $this->platformCertificateFilePath = ROOT_PATH.$appinfo['wxpay_wechatpay_pem'];
            $plate_pem = file_get_contents( $this->platformCertificateFilePath);
            if(empty($plate_pem)){
                if(!$is_curl) {
                    showmsg('请先配置微信支付平台证书');
                }else{
                    throw new HttpException(500, '请先配置微信支付平台证书');
                }
            }
            $this->platformCertificateFilePath = $plate_pem;
            $this->platformPublicKeyInstance = Rsa::from($this->platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
            // 从「微信支付平台证书」中获取「证书序列号」
            if($appinfo['wxpay_plate_serialno']){
                //后台填写了证书序列号
                $this->platformCertificateSerial = $appinfo['wxpay_plate_serialno'];
            }else{
                //后台未填写的主动查询证书序列号
                $this->platformCertificateSerial = PemUtil::parseCertificateSerialNo($this->platformCertificateFilePath);
            }

            $certs[$this->platformCertificateSerial] = $this->platformPublicKeyInstance;
            $serial = $this->platformCertificateSerial;
        }
       if($appinfo['sign_type']==1){
           // 从本地文件中加载「微信支付公钥」，用来验证微信支付应答的签名
           $this->platformPublicKeyId = $appinfo['public_key_id'];
           $platformPublicKeyFilePath = $appinfo['public_key_pem'];
           if(empty($appinfo['public_key_pem']) || empty($platformPublicKeyFilePath)){
               if(!$is_curl) {
                   showmsg('请先配置微信支付公钥信息');
               }else{
                   throw new HttpException(500, '请先配置微信支付公钥信息');
               }
           }
           $platformPublicKeyFilePath = file_get_contents($platformPublicKeyFilePath);
           if(empty($platformPublicKeyFilePath)){
               if(!$is_curl) {
                showmsg('请上传微信支付公钥');
               }else{
                   throw new HttpException(500, '请上传微信支付公钥');
               }
           }
           $this->platformPublicKeyFilePath = $platformPublicKeyFilePath;
           $twoPlatformPublicKeyInstance = Rsa::from($platformPublicKeyFilePath, Rsa::KEY_TYPE_PUBLIC);
           $certs[$this->platformPublicKeyId] = $twoPlatformPublicKeyInstance;
           $serial = $this->platformPublicKeyId;
       }
       $this->sign_type = $appinfo['sign_type'];


        /******************支付平台证书与支付公钥二选一使用 end****************************/
        //php -f ./CertificateDownloader.php -- -k Aa270356888Aa270356888Aa27035688 -m 1704379590 -f E:\_web\www.diandashop.com\ddshop2\extend\pay\wx\apiclient_key.pem -s 14E6FAA7DF2395031A8C2D26AC7457106AB50A47 -o E:\_web\www.diandashop.com\ddshop2\extend\pay\wx\
        // 构造一个 APIv3 客户端实例
        $this->instance = Builder::factory([
            'mchid'      => $this->merchantId,
            'serial'     => $this->merchantCertificateSerial,
            'privateKey' => $this->merchantPrivateKeyInstance,
            'Wechatpay-Serial' => $serial,
            'certs'      => $certs,
        ]);

        $this->appid = $appinfo['appid']??'';
        $this->mchkey = $config['wxpay_mchkey']??'';
        $this->appinfo = $appinfo;
        $this->openid = $openid;
        $this->member = $member;
        $this->aid = $aid;
    }

    /**
     * 发起转账
     * @param $ordernum 订单编号
     * @param $money 转账金额
     * @param string $realname 转账人姓名
     * @param string $desc 转账备注
     * @param string $data_tbl 对应的记录表名
     * @param int $data_id 对应的记录表ID
     * @return array
     */
    public function transfer($ordernum,$money,$realname='',$desc='打款',$data_tbl='',$data_id=0){
        try {
            $info = Db::name($data_tbl)->where('id',$data_id)->find();
            if(!$info){
                return ['status'=>0,'msg'=>'未找到对应的记录'];
            }
            if($info['paytype']!='微信钱包' && $info['paytype']!='微信'){
                return ['status'=>0,'msg'=>'仅支持提现到微信钱包方式'];
            }
            $realname = $realname?:$this->member['realname'];
            $appinfo = $this->appinfo;
            $params = [
                'appid'        =>  $this->appid,
                'out_bill_no' => $ordernum,
                'transfer_scene_id' => $appinfo['transfer_scene_id']?:'1005',//转账场景ID 该笔转账使用的转账场景，可前往“商户平台-产品中心-商家转账”中申请。如：1001-现金营销
                'openid'  => $this->openid,
                'transfer_amount' => (int)($money*100),
                'transfer_remark' => $desc,
                'notify_url'   =>  $this->notify_url,
                'transfer_scene_report_infos'=> [
                    [
                        'info_type'    => '岗位类型',
                        'info_content' => $appinfo['transfer_scene_type']?:'员工'
                    ],
                    [
                        'info_type'    => '报酬说明',
                        'info_content' => $appinfo['transfer_scene_content']?:'员工工资'
                    ]
                ],
            ];
            if($realname && $money>=2000){
                $realname = $this->getEncrypt($realname);
                $params['user_name'] = $realname;
            }
            writeLog('请求参数'.json_encode($params),'wx_pay');
            if($this->sign_type==0){
                //平台证书方式
                $headers = ['Wechatpay-Serial' => $this->platformCertificateSerial];
            }else{
                //支付公钥方式
                $headers = ['Wechatpay-Serial' => $this->platformPublicKeyId];
            }
            $resp = $this->instance
                ->chain('/v3/fund-app/mch-transfer/transfer-bills')
                ->post(['json' => $params, 'headers' => $headers]);

            $status_code =  $resp->getStatusCode();
            $body = $resp->getBody();
            $return_data = json_decode($body,true);
            writeLog('请求成功','wx_pay');
            writeLog(json_encode($resp),'wx_pay');
            writeLog($body,'wx_pay');

            $res_log = [
                'status_code' => $status_code,
                'package_info' => $return_data['package_info']??'',
                'state' => $return_data['state']??'',
                'transfer_bill_no' => $return_data['transfer_bill_no']??'',
                'message' => $return_data['message']??'',
            ];
            $this->log($ordernum, $money, $realname, $desc, $data_tbl, $data_id, $res_log);
            if($status_code==200){
                return ['status'=>1,'msg'=>'操作成功！','data'=>$return_data];
            }else{
                return ['status'=>0,'msg'=>'转账失败'];
            }
        } catch (\Exception $e) {
            // 进行错误处理
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $r = $e->getResponse();
                $res = $r->getBody();
                writeLog('请求失败','wx_pay');
                writeLog($e,'wx_pay');
                writeLog($e->getMessage(),'wx_pay');
                writeLog(json_encode($r),'wx_pay');
                writeLog($res,'wx_pay');
                $result = json_decode($res,true);
                $res_log = [
                    'status_code' => $result['code']??'',
                    'package_info' => $result['package_info']??'',
                    'state' => $result['state']??'',
                    'transfer_bill_no' => $result['transfer_bill_no']??'',
                    'message' => $result['message']??'',
                ];
                $this->log($ordernum, $money, $realname, $desc, $data_tbl, $data_id, $res_log);
                return ['status'=>0,'msg'=>$result['message']?:$e->getMessage()];
            }
//            dump($e);exit;
//            echo $e->getTraceAsString(), PHP_EOL;
        }
    }

    //查询转账结果
    public function transfer_query($ordernum,$data_tbl='',$data_id=0){
        try {
            writeLog('查询'.$ordernum.'转账结果','wx_pay_query');
            if($this->sign_type==0){
                //平台证书方式
                $headers = ['Wechatpay-Serial' => $this->platformCertificateSerial];
            }else{
                //支付公钥方式
                $headers = ['Wechatpay-Serial' => $this->platformPublicKeyId];
            }
            $resp = $this->instance
                ->chain('/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/'.$ordernum)
                ->get([ 'headers' => $headers]);

            $status_code =  $resp->getStatusCode();
            $body = $resp->getBody();
            $return_data = json_decode($body,true);
            writeLog('请求成功','wx_pay_query');
            writeLog(json_encode($resp),'wx_pay_query');
            writeLog($body,'wx_pay_query');

            $res_log = [
                'status_code' => $status_code,
                'package_info' => $return_data['package_info']??'',
                'state' => $return_data['state']??'',
                'transfer_bill_no' => $return_data['transfer_bill_no']??'',
                'message' => $return_data['message']??'',
            ];
            $this->log($ordernum, 0, '', '', $data_tbl, $data_id, $res_log);
            if($status_code==200){
                //更新提现记录状态
                $this->update_withdraw($data_tbl, $data_id,$return_data);
                return ['status'=>1,'msg'=>'操作成功！','data'=>$return_data];
            }else{
                return ['status'=>0,'msg'=>'转账失败'];
            }
        } catch (\Exception $e) {
            // 进行错误处理
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $r = $e->getResponse();
                $res = $r->getBody();
                writeLog('请求失败','wx_pay_query');
                writeLog($e,'wx_pay_query');
                writeLog(json_encode($r),'wx_pay_query');
                writeLog($res,'wx_pay_query');
                $result = json_decode($res,true);
                $res_log = [
                    'status_code' => $result['code'],
                    'package_info' => $result['package_info']??'',
                    'state' => $result['state']??'',
                    'transfer_bill_no' => $result['transfer_bill_no']??'',
                    'message' => $result['message']??'',
                ];
                $this->log($ordernum, 0, '', '', $data_tbl, $data_id, $res_log);
                return ['status'=>0,'msg'=>$result['message']];
            }
//            dump($e);exit;
//            echo $e->getTraceAsString(), PHP_EOL;
        }
    }
    //更新提现记录状态
    public function update_withdraw( $data_tbl, $data_id,$return_data){
        $info = Db::name($data_tbl)->where('id',$data_id)->find();
        if(!$info){
            return false;
        }
        $is_back = 0;
        if($return_data['state']=='CANCELLED' && $info['status']==4){
            //商家主动撤销成功的需要退款
            $is_back = 1;
            $remark = '提现返还';
        }
        if($return_data['state']=='FAIL' && $info['status']==4){
            //转账失败的（用户长时间未确认收款、商家账户没钱等等）
            $is_back = 1;
            $remark = '打款失败-'.$return_data['fail_reason']??'';
        }
        if($is_back==1){
            //撤销成功的退还提现金额
            if($data_tbl=='member_withdrawlog'){
                \app\common\Member::addmoney($info['aid'],$info['mid'],$info['txmoney'],$remark);
            }
            if($data_tbl=='member_commission_withdrawlog'){
                \app\common\Member::addcommission($info['aid'],$info['mid'],0,$info['txmoney'],$remark,0,'withdraw_back');
            }
            if($data_tbl=='business_withdrawlog'){
                \app\common\Business::addmoney($info['aid'],$info['bid'],$info['txmoney'],$remark);
            }
            if($data_tbl=='yuyue_worker_withdrawlog'){
                \app\common\YuyueWorker::addmoney($info['aid'],$info['bid'],$info['uid'],$info['txmoney'],'提现驳回返还',0);
            }
            if($data_tbl=='peisong_withdrawlog'){
                \app\common\PeisongUser::addmoney($info['aid'],$info['uid'],$info['txmoney'],'提现驳回返还');
            }
            if($data_tbl=='member_xianjin_withdrawlog'){
                \app\custom\MemberCustom::addXianjin($info['aid'],$info['mid'],$info['txmoney'],$remark,'withdraw_back');
            }
        }
        //更新提现记录状态
        if ($return_data['state'] == 'SUCCESS') {
            Db::name($data_tbl)->where('aid', $info['aid'])->where('id', $info['id'])->update(['status' => 3, 'wx_state' => $return_data['state']]);
        }elseif($return_data['state']=='CANCELING' || $return_data['state']=='CANCELLED'){//已撤销
            Db::name($data_tbl)->where('aid',$info['aid'])->where('id',$info['id'])->update(['status'=>2,'wx_state'=>$return_data['state'],'reason'=>'商家撤销']);
        }elseif($return_data['state']=='FAIL'){//转账失败
            $reason_msg = \app\common\Wxpay::transfer_fail_reason_msg($return_data['fail_reason']);
            Db::name($data_tbl)->where('aid',$info['aid'])->where('id',$info['id'])->update(['status'=>2,'wx_state'=>$return_data['state'],'reason'=>'转账失败：'.$reason_msg]);
        }
        return true;
    }
    //微信回调
    public function notify_wx($header, $inBody)
    {
        $inWechatpaySignature = $header['wechatpay-signature'];
        $inWechatpayTimestamp = $header['wechatpay-timestamp'];
        $inWechatpaySerial = $header['wechatpay-serial'];
        $inWechatpayNonce = $header['wechatpay-nonce'];
        if (!$inWechatpaySignature or !$inWechatpayTimestamp or !$inWechatpaySerial or !$inWechatpayNonce) {
            header("Location:/404.html");
            exit;
        }
        $merchantId = $this->merchantId ?? '';     // 商户号
        $apiv3Key = $this->mchkey ?? '';// 在商户平台上设置的APIv3密钥
        // 根据通知的平台证书序列号，查询本地平台证书文件，这里是自己生成的证书
        if($this->sign_type==0){
            //平台证书文件
            $public_key = Rsa::from($this->platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
        }else{
            //支付公钥文件
            $public_key = Rsa::from($this->platformPublicKeyFilePath, Rsa::KEY_TYPE_PUBLIC);
        }
        // 检查通知时间偏移量，允许5分钟之内的偏移
        $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
        writeLog('验签inbody'.$inBody,'notify_v3');
        writeLog('签名字符串'.Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),'notify_v3');
        writeLog('签名'.$inWechatpaySignature,'notify_v3');
        $verifiedStatus = Rsa::verify(
        // 构造验签名串
            Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
            $inWechatpaySignature,
            $public_key
        );
        if ($timeOffsetStatus && $verifiedStatus) {
            // 转换通知的JSON文本消息为PHP Array数组
            $inBodyArray = (array)json_decode($inBody, true);
            // 使用PHP7的数据解构语法，从Array中解构并赋值变量
            ['resource' => [
                'ciphertext' => $ciphertext,
                'nonce' => $nonce,
                'associated_data' => $aad
            ]] = $inBodyArray;
            // 加密文本消息解密
            $inBodyResource = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
            // 把解密后的文本转换为PHP Array数组
            return (array)json_decode($inBodyResource, true);
        } else {
            return ['trade_state' => 'FAIL'];
        }
    }
    //敏感数据使用微信公钥加密
    public function getEncrypt($str) {
        //$str是待加密字符串
        if($this->sign_type==0){
            //平台证书文件
            $public_key = $this->platformCertificateFilePath;
        }else{
            //支付公钥文件
            $public_key = $this->platformPublicKeyFilePath;
        }
        $encrypted = '';
        if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64编码
            $sign = base64_encode($encrypted);
        } else {
            throw new Exception('encrypt failed');
        }
        return $sign;
    }

    //添加转账记录
    public function log($ordernum, $money, $realname, $desc, $data_tbl, $data_id, $res){
        $exit = Db::name('wx_transfer_log')->where('aid',$this->aid)->where('ordernum',$ordernum)->where('data_tbl',$data_tbl)->find();
        if($exit){
            $log = [
                'package_info' => $res['package_info']??'',//用户确认页面的信息
                'state' => $res['state']??'',//转账状态
                'transfer_bill_no' => $res['transfer_bill_no']??'',//微信单号
                'message' => $res['message']??'',//失败原因
            ];
            Db::name('wx_transfer_log')->where('id',$exit['id'])->update($log);
        }else{
            $log = [
                'aid'=> $this->aid,
                'mid'=> $this->member['id'],
                'ordernum'=>$ordernum,
                'money'=>$money,
                'realname'=>$realname,
                'desc'=>$desc,
                'data_tbl'=>$data_tbl,
                'data_id'=>$data_id,
                'status_code'=>$res['status_code'],
                'createtime'=>time(),
                'package_info' => $res['package_info']??'',//用户确认页面的信息
                'state' => $res['state']??'',//转账状态
                'transfer_bill_no' => $res['transfer_bill_no']??'',//微信单号
                'message' => $res['message']??'',//失败原因
            ];
            Db::name('wx_transfer_log')->insert($log);
        }
        return true;
    }
    //下载平台证书
    public function download_wechatkey($aid){
        //此方法可以生成平台证书，但没法确认生成的文件名称，暂不使用
        $info = Db::name('admin_setapp_mp')->where('aid',$aid)->find();
        $apiV3key = $info['wxpay_mchkey_v3'];
        $wxpay_mchid = $info['wxpay_mchid'];
        $certificate = ROOT_PATH.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'CertificateDownloader.php';//生成证书的工具路径
        $apiclient_key = ROOT_PATH.$info['wxpay_apiclient_key'];//商户私钥
        $mchSerialNo = $info['wxpay_serial_no'];//商户私钥证书序列号
        $out_path = ROOT_PATH.'extend'.DIRECTORY_SEPARATOR.'pay'.DIRECTORY_SEPARATOR.'wx'.DIRECTORY_SEPARATOR.'wechatkey'.DIRECTORY_SEPARATOR.$aid.DIRECTORY_SEPARATOR;
        if(!file_exists($out_path)){
            mk_dir($out_path, 0775);
        }
        //dump('php -f '.$certificate.' -- -k '.$apiV3key.' -m '.$wxpay_mchid.' -f '.$apiclient_key.' -s '.$mchSerialNo.' '.$out_path);
        $res = shell_exec('php -f '.$certificate.' -- -k '.$apiV3key.' -m '.$wxpay_mchid.' -f '.$apiclient_key.' -s '.$mchSerialNo.' -o '.$out_path);
        if($res===null){
            return json(['status'=>0,'msg'=>'失败']);
        }
        $files = scandir($out_path);
        $key_file = '';
        foreach($files as $file){
            if(strpos($file,'.pem')!==false){
                $key_file = $file;
                continue;
            }
        }
        $new_file_name = 'wechatkey_'.$aid.'.pem';
        if (rename($out_path.$key_file, $out_path.$new_file_name)) {
            return json(['status'=>1,'msg'=>'下载成功','data'=>$out_path.$new_file_name]);
        } else {
            return json(['status'=>0,'msg'=>'失败']);
        }
    }

    //撤销微信转账
    public function cancel_transfer($ordernum, $data_tbl='member_withdrawlog', $data_id=0){
        try {
            $resp = $this->instance
                ->chain('/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/'.$ordernum.'/cancel')
                ->post();
            $status_code =  $resp->getStatusCode();
            $body = $resp->getBody();
            $return_data = json_decode($body,true);
            writeLog('请求成功','wx_pay');
            writeLog(json_encode($resp),'wx_pay');
            writeLog($body,'wx_pay');
            $res_log = [
                'state' => $return_data['state']??'',
            ];
            $log = Db::name('wx_transfer_log')->where('aid',$this->aid)->where('ordernum',$ordernum)->where('data_tbl',$data_tbl)->find();
            Db::name('wx_transfer_log')->where('id',$log['id'])->update($res_log);
            if($status_code==200){
                return ['status'=>1,'msg'=>'操作成功！','data'=>$return_data];
            }else{
                return ['status'=>0,'msg'=>'转账失败'];
            }
        } catch (\Exception $e) {
            // 进行错误处理
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $r = $e->getResponse();
                $res = $r->getBody();
                writeLog('请求失败','wx_pay');
                writeLog(json_encode($r),'wx_pay');
                writeLog($res,'wx_pay');
                $result = json_decode($res,true);
                $res_log = [
                    'state' => $result['state']??'',
                ];
                $log = Db::name('wx_transfer_log')->where('aid',$this->aid)->where('ordernum',$ordernum)->where('data_tbl',$data_tbl)->find();
                Db::name('wx_transfer_log')->where('id',$log['id'])->update($res_log);
                return ['status'=>0,'msg'=>$result['message']];
            }
        }
    }
}
