<?php
//custom_file(pay_ysepay)
use think\facade\Db;

require_once "util/DateUtil.php";
require_once "util/StrUtil.php";
require_once "util/RSA256Util.php";
require_once "util/AESUtil.php";
require_once "util/SignUtil.php";
require_once "util/HttpClient.php";
require_once "util/FileUtil.php";

/**
 * 银盛支付 接口服务类
 */
class YsfApiService
{
    /**
     * @param $baseUrl string 请求地址域名
     * @param $merchantId string 发起方商户号
     * @param $publicCertPath string 银盛端公钥证书文件路径
     * @param $privateCertPath string 商户端的私钥证书文件路径
     * @param $privateCertPwd string 私钥证书密码
     */
    private $baseUrl;
    private $merchantId;
    private $publicCertPath;
    private $privateCertPath;
    private $privateCertPwd;
    public $aid;
    public $bid;
    public $mid;
    private $mercId;
    public $platform;



    public function __construct($aid,$bid=0,$mid=0,$platform = 'h5')
    {
        $this->aid = $aid;
        $this->bid = $bid;
        $this->mid = $mid;
        $this->platform = $platform;

        $set = \app\common\System::appinfo($this->aid, $this->platform);
//        $this->baseUrl = 'https://appdev.ysepay.com';//测试
        $this->baseUrl = 'https://ysgate.ysepay.com';//生产

        //发起方商户号 服务商在银盛给自己开设的商户号，即可当作发起方商户号 生产环境需要使用自己的发起发商户号，并找相应的对接人员开通所需要的接口权限，并告知是国密还是RSA
        $this->merchantId = $set['ysepay_certId'];//'826121648160110';

        $this->mercId = $set['ysepay_merchantId'];//实际商户号

        //银盛端公钥证书 (RSA加密) 生产环境需要自行去开放平台上下载银盛公钥证书，也可以找对接人提供 $config['YS_PUBLIC_CER_PATH']
        $this->publicCertPath =  str_replace(PRE_URL.'/',ROOT_PATH,$set['ysepay_publicCertPath']);//dirname(__FILE__) . "/cert/businessgate.cer";

        //商户端的私钥证书 (RSA加密) 生产环境需要使用自己生产的私钥证书 $config['MERC_PRIVATE_FILE']
        $this->privateCertPath = str_replace(PRE_URL.'/',ROOT_PATH,$set['ysepay_privateCertPath']);//dirname(__FILE__) . "/cert/rsa.pfx";

        //商户端私钥证书密码 生产环境需要使用自己私钥的密码
        $this->privateCertPwd = $set['ysepay_privateCertPwd'];

//        \think\facade\Log::write(__FILE__.__LINE__);//debug使用
//        \think\facade\Log::write('privateCertPath:'.$this->privateCertPath);
//        \think\facade\Log::write('publicCertPath_:'.$this->publicCertPath);
    }

    private function checkSign($resMap)
    {
        /** 对结果进行解密，并使用银盛公钥验签*/
        if (empty($resMap['sign'])) {
            throw new RuntimeException('验签失败,未返回加签信息,可能是银盛未配置发起方或者发起方证书类型配置有误,返回结果提示为:' . $resMap['msg']);
        } else {
            if (!RSA256Util::verifySign($this->publicCertPath, $resMap['sign'], SignUtil::map2SortedSignStr($resMap))) {
                throw new RuntimeException("返回结果验签失败");
            }
        }
    }

    private function decryptBizData(&$resMap, $key)
    {
        /** 使用上面生成的加密密钥key，解密返回的业务参数*/
        if (!empty($resMap['businessData'])) {
            $resMap['businessData'] = json_decode(AESUtil::decrypt(base64_decode($resMap['businessData']), $key), true);
        }
    }

    private function jsonEncode($data)
    {
        LogUtil::debug('bizContent:');
        LogUtil::debug($data);
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function genCommonReqParam($method, $key, $reqId)
    {
        return [
            // 接口名称
            'method' => $method,
            // 发送请求的时间，格式"yyyy-MM-dd HH:mm:ss"
            'timeStamp' => DateUtil::nowTimeStr(),
            // 请求使用的编码格式，固定为utf-8
            'charset' => 'utf-8',
            // 请求唯一流水号,商户系统唯一，要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
            'reqId' => $reqId,
            //发起方商户号，服务商在银盛给自己开设的商户号，即可当作发起方商户号，由银盛生成并下发。 注意：不同于子商户号，服务商发展的商户即为子商户号
            'certId' => $this->merchantId,
            // 版本号，默认1.0，按接口文档来
            'version' => "1.0",
            // 银盛公钥加密随机生成的字符串（key）得到的加密值
            'check' => RSA256Util::encryptByPub($this->publicCertPath, $key)
        ];
    }

    private function doSign(array &$reqMap)
    {
        // 使用接入方私钥对排序的请求参数加签，并存放到请求参数里面.privateCertPath:私钥地址，privateCertPwd:私钥密钥
        $reqMap['sign'] = RSA256Util::signWithPfx(SignUtil::map2SortedSignStr($reqMap), $this->privateCertPath, $this->privateCertPwd);
    }

    private function checkConfig()
    {
        if(empty($this->merchantId) || empty($this->privateCertPath) || empty($this->publicCertPath)){
            return ['status'=> 0, 'msg'=> '请先配置支付信息'];
        }
        return ['status'=> 1, 'msg'=> 'ok'];
    }

    /**
     * 聚合收银台创建订单 https://gateway-doc.ysepay.com/gatewayDocs/summary/N0000384/N0000483/N0000488/I0000326.html
     * @param $signId string 客户签约流水号
     */
    public function createOrder($title,$ordernum,$price,$tablename)
    {
        $notify_url = PRE_URL.'/notify.php?paytype=ysepay';
        $rsc = $this->checkConfig();
        if($rsc['status'] != 1) return $rsc;

        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        //$reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
        $reqId = $ordernum.'T'.mt_rand(1000,9999);
        $reqMap = $this->genCommonReqParam('order.createOrder', $key, $reqId);
//        //版本号，按接口文档来
        $reqMap['version'] = '1.2';
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $params = [
            'orderId' => $ordernum,
            'msgCode' => 'S3001',//报文编号,S3001即时到账、S3002担保交易，为空时默认S3001
            'mercId' => $this->mercId,
            'busiCode' => '00510102',//业务代码,默认传00510103，如果有通过会拓客分润需求，则传00510102，具体请咨询客户经理
            'shopDate' => date('Ymd'),
            'amount' => bcmul($price,100,0),//交易金额,该笔订单的资金总额，单位：分。
            'paymentValidTime' => '30',//支付有效时间, 单位分钟，最小1分钟，最长不超过30分钟.。
            'currency' => 'CNY',
            'backUrl' => $notify_url,//回调地址,交易成功异步通知到商户的后台地址，支持多个url进行异步通知，多个url用分隔符“,”分开，格式如：url1,url2,url3。
            'payMode' => '29',//支付方式,26 支付宝生活号,28 微信公众号,29 微信小程序,30 银联行业码支付
            'h5Join' => '00',
            'note' => $title,//订单说明,可以用于商品备注，该字段会上送给渠道，建议必填，例如用户使用微信支付，则用户可以在微信账单看到订单说明
            'remark' => $this->aid.':'.$tablename.':'.$this->platform.':'.$this->bid//订单备注,该字段废弃（备注不可用时可使用ysepay_log表数据）
        ];
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($params), $key);
        // 签名
        $this->doSign($reqMap);
//        \think\facade\Log::write(__FILE__.__LINE__);
//        \think\facade\Log::write($params);//debug使用
        $resMap = HttpClient::post($this->baseUrl . '/openapi/order/createOrder', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
//        \think\facade\Log::write($resMap);//debug使用
        $this->doSign($reqMap);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        if($resMap['code'] == '00000' && $resMap['subCode'] == '0'){
            $rsdata = $resMap['businessData']['data'];
            //记录
            $log = [
                'aid'=>$this->aid,
                'bid'=>$this->bid,
                'mid'=>$this->mid,
                'tablename'=>$tablename,
                'ordernum'=>$ordernum,
                'reqId'=>$reqId,
                'reqMsgId'=>$rsdata['reqMsgId'],
                'merchantId'=>$rsdata['mercId'],
                'amount'=>$rsdata['amount'],
                'remark'=>'',
                'req_data'=>json_encode($params),
                'res_data'=>json_encode($resMap),
                'createtime'=>time(),
                'platform'=>$this->platform
            ];
            Db::name('ysepay_log')->insert($log);
            return ['status'=> 1, 'msg'=> 'ok', 'data'=>$rsdata];
        }else{
            return ['status'=> 0, 'msg'=> $resMap['subMsg'] ? $resMap['subMsg'] : '未知支付错误'.$resMap['subCode'],'subCode'=>$resMap['subCode']];
        }

        /*
         {
            "timeStamp": "2024-11-01 16:53:57",
            "norce": "a296329353d4425b8d70a1583a240adf",
            "code": "00000",
            "businessData": {
                "rpcError": false,
                "data": {
                    "mercId": "826121653310707",
                    "encryData": "3bbd9eaa9310884bca310d9cafa98634",
                    "amount": "20",
                    "orderEfficientTime": "20241101172357",
                    "orderId": "2024110139683516230395",
                    "orderCreateTime": "20241101165357",
                    "payUrl": "https:\/\/wxaurl.cn\/6KiIbMqRXeo",
                    "reqMsgId": "1730451237"
                },
                "suffixCode": 0
            },
            "subCode": "0",
            "sign": "mL7pdLWmsT\/iuNXXWCtqGq5F5zqaeb6I5oiTk5Fw2U\/0MVSUFilIObWNCONtei+2\/ylNkmkBbYq7NtQNV5AjIlcsBa1\/r90Bbb2HQ\/=",
            "subMsg": "下单成功"
        }
         */

        /*
        notify data
        {
            "timeStamp": "2024-11-02 17:26:25",
            "charset": "UTF-8",
            "src": "pregate",
            "bizContent": "{"mercId":"826121658120499","orderId":"241102172427367090","payTime":"20241102172443","fee":"0.00","isDiscount":"02","payerFee":"0.0","tradeSource":"47","totalDiscountAmt":"0.0","payerBankaccountType":"01","tradeNo":"113039560810241102602301431612","openid":"oeurG69QRpVxm_bVVgBr9ljvQGpg","notifyTime":"2024-11-02 17:26:25","wxpayCouponInfo":"{\"fee_type\":\"CNY\",\"total_fee\":0.01,\"cash_fee_type\":\"CNY\",\"cash_fee\":0.01,\"settlement_total_fee\":0.01,\"coupon_fee\":0.0,\"ext\":\"\"}","payAmt":"0.01","aliMerchantAmount":"0.00","totalAmount":"nu","srcFee":"0.0","notifyType":"directpay.status.sync","channelSendSn":"20241102Nf0000000006011600152810","tradeStatus":"TRADE_SUCCESS","tradeSn":"113039560810241102602301431612","partnerFee":"nu","preferentialAmount":"0.00","note":"小米12","openId":"oeurG69QRpVxm_bVVgBr9ljvQGpg","remark":"cashier","extraCommonParam":"cashier","channelRecvSn":"4200002454202411026744968241","preferentialFee":"0.00","srcUsercode":"826121648160110","sumFee":"0.00","merDiscountFee":"0.00","amount":"0.01","accountDate":"20241102","aliPlatformDisAmount":"0.00","dctGoodsInfo":"","payMode":"29","payeeFee":"0.0","busiCode":"00510102","cardType":"","settlementAmt":"0.01","totalDiscount":"0.00","currencyCode":"CNY","srcMercId":"826121648160110"}",
            "sign": "B2msFMMkSG/kZGFHXaQiJL6ZE17FoGo4C9s/8oFNe9iYTUJEToVZn8YD9v4XwA3Hv0m723DNO/CQIqVMOVsmY2yaIQ69vvy0G86Kw7fA1U2Z6SZoOBSq57DUIYRpCiMX5YF94uwUSM0t4dZYqqSqae/1JOG9Csl5D5BDl5QULuw=",
            "reqId": "20241102172625809"
         }
         */
    }

    public function notifyCheckSign($resdata)
    {
        $resMap = json_decode($resdata, true);
        $this->checkSign($resMap);
        return true;
    }

    /**
     * 交易退款 https://gateway-doc.ysepay.com/gatewayDocs/summary/N0000384/N0000483/N0000488/I0000293.html
     * @return array
     * 调用接口，可发起条码类交易退款。
     * 注意事项：
     * 1、最终退款成功的结果不是实时返回，可通过退款查询接口查询终态。
     * 2、微信一个订单最多退50次。
     */
    public function refundOrder($ordernum,$refund_desc,$refundmoney,$payorder)
    {
        $notify_url = PRE_URL.'/notify.php?paytype=ysepay&a=refund';
        $rsc = $this->checkConfig();
        if($rsc['status'] != 1) return $rsc;

        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        //$reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
        $reqId = $ordernum.'T'.mt_rand(1000,9999);
        $reqMap = $this->genCommonReqParam('unify.trade.refund', $key, $reqId);
//        //版本号，按接口文档来
        $reqMap['version'] = '1.0';
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $params = [
//            'origOrderId' => $payorder['ordernum'],//商户原交易订单号,原订单号、原交易流水号不能同时为空。如果同时存在优先取原订单号
            'origTradeSn' => $payorder['paynum'],//原交易流水号,银盛原交易流水号，原订单号、原交易流水号不能同时为空。如果同时存在优先取原订单号。 示例值：311160414497667096。
            'shopDate' => date('Ymd'),
            'refundAmount' => bcmul($refundmoney,100,0),//申请退款金额,需要退款的金额，该金额不能大于订单金额,单位为：分。
            'refundReason' => $refund_desc,//退款原因,退款的原因说明。
            'refundOrderId' => $reqId,//商户退款订单号,商户系统生成的退款订单号,示例值：RD2012061713107，要求如下：1、标识一次退款请求，同一笔交易多次退款需要保证唯一。2、该参数支持汉字，最大长度为16个。3、用同一订单号同一退款流水号继续退款时，则会返回第一次退款的结果不会继续退款 。
            'notifyUrl' => $notify_url,//回调地址,交易成功异步通知到商户的后台地址，支持多个url进行异步通知，多个url用分隔符“,”分开，格式如：url1,url2,url3。
        ];
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($params), $key);
        // 签名
        $this->doSign($reqMap);
//        \think\facade\Log::write(__FILE__.__LINE__);
//        \think\facade\Log::write($params);//debug使用
        $resMap = HttpClient::post($this->baseUrl . '/openapi/unify/trade/refund', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
//        \think\facade\Log::write($resMap);//debug使用
        $this->doSign($reqMap);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        if($resMap['code'] == '00000' && $resMap['subCode'] == '0000'){
            $rsdata = $resMap['businessData']['data'];
            //\think\facade\Log::write($rsdata);//debug使用
            //记录
            $log = [
                'aid'=>$this->aid,
                'bid'=>$this->bid,
                'mid'=>$this->mid,
                'ordernum'=>$ordernum,
                'origOrdernum'=>$payorder['ordernum'],
                'origTradeSn'=>$params['origTradeSn'],
                'refundOrderId'=>$params['refundOrderId'],
                'reqMsgId'=>$rsdata['reqMsgId'],
                'merchantId'=>$this->mercId,
                'refundAmount'=>$rsdata['refundAmount'],
                'remark'=>$refund_desc,
                'req_data'=>json_encode($params),
                'res_data'=>json_encode($resMap),
                'createtime'=>time(),
                'tablename'=>$payorder['type'],
                'platform'=>$payorder['platform']
            ];
            Db::name('ysepay_refund_log')->insert($log);
            return ['status'=> 1, 'msg'=> 'ok', 'data'=>$rsdata];
        }else{
            \think\facade\Log::write(__FILE__.__LINE__);
            \think\facade\Log::write($resMap,'error');//debug使用

            $rsdata = $resMap['businessData'];
            \think\facade\Log::write($rsdata);//debug使用

            //查询退款状态
            $rsquery = $this->refundQuery($payorder['ordernum'],$ordernum);
            if($rsquery['status'] == 1){
                $resMap['subMsg'] .= ' refundState:'.$rsquery['data']['refundState'].' fundsState:'.$rsquery['data']['fundsState'];
            }
            return ['status'=> 0, 'msg'=> $resMap['subMsg'] ? $resMap['subMsg'] : '未知支付错误'.$resMap['subCode'],'subCode'=>$resMap['subCode']];
        }
    }

    //退款查询 https://gateway-doc.ysepay.com/gatewayDocs/summary/N0000384/N0000483/N0000488/I0000292.html
    public function refundQuery($ordernum,$refundOrdernum)
    {
        $rsc = $this->checkConfig();
        if($rsc['status'] != 1) return $rsc;

        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        //$reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
        $reqId = $ordernum.'T'.mt_rand(1000,9999);
        $reqMap = $this->genCommonReqParam('unify.trade.refund.query', $key, $reqId);
//        //版本号，按接口文档来
        $reqMap['version'] = '1.0';
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $params = [
            'origOrderId' => $ordernum,//商户原交易订单号,原订单号、原交易流水号不能同时为空。如果同时存在优先取原订单号
//            'origTradeSn' => $payorder['paynum'],//原交易流水号,银盛原交易流水号，原订单号、原交易流水号不能同时为空。如果同时存在优先取原订单号。 示例值：311160414497667096。
            'refundOrderId' => $refundOrdernum,//商户退款订单号,商户系统生成的退款订单号,示例值：RD2012061713107，要求如下：1、标识一次退款请求，同一笔交易多次退款需要保证唯一。2、该参数支持汉字，最大长度为16个。3、用同一订单号同一退款流水号继续退款时，则会返回第一次退款的结果不会继续退款 。
        ];
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($params), $key);
        // 签名
        $this->doSign($reqMap);
        //\think\facade\Log::write(__FILE__.__LINE__);
        //\think\facade\Log::write($params);//debug使用
        $resMap = HttpClient::post($this->baseUrl . '/openapi/unify/trade/refund/query', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        //\think\facade\Log::write($resMap);//debug使用
        $this->doSign($reqMap);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        if($resMap['code'] == '00000' && $resMap['subCode'] == '0000'){
            $rsdata = $resMap['businessData']['data'];
            \think\facade\Log::write($rsdata);//debug使用

            return ['status'=> 1, 'msg'=> 'ok', 'data'=>$rsdata];
        }else{
            \think\facade\Log::write(__FILE__.__LINE__);
            \think\facade\Log::write($resMap,'error');//debug使用
            return ['status'=> 0, 'msg'=> $resMap['subMsg'] ? $resMap['subMsg'] : '未知错误'.$resMap['subCode'],'subCode'=>$resMap['subCode']];
        }
    }

    /**
     * 入网申请
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $custInfo array 基本信息
     * @param $crpInfo array 法人信息
     * @param $stlAccInfo array 结算信息
     * @param $busInfo array 营业信息
     * @return array
     */
    public function addCustInfoApply($reqId, $custInfo, $crpInfo, $stlAccInfo, $busInfo)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.addCustInfoApply', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            // 基本信息
            'custInfo' => $custInfo,
            // 法人信息
            'crpInfo' => $crpInfo,
            // 结算信息
            'stlAccInfo' => $stlAccInfo,
            // 营业信息
            'busInfo' => $busInfo,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/t1/smsc/addCustInfoApply', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 商户入网申请修改
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $sysFlowId string 入网申请流水
     * @param $custInfo array 基本信息
     * @param $crpInfo array 法人信息
     * @param $stlAccInfo array 结算信息
     * @param $busInfo array 营业信息
     * @return array
     */
    public function modifyCustInfoApply($reqId, $sysFlowId, $custInfo, $crpInfo, $stlAccInfo, $busInfo)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.modifyCustInfoApply', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            // 入网申请流水
            'sysFlowId' => $sysFlowId,
            // 基本信息
            'custInfo' => $custInfo,
            // 法人信息
            'crpInfo' => $crpInfo,
            // 结算信息
            'stlAccInfo' => $stlAccInfo,
            // 营业信息
            'busInfo' => $busInfo,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/modifyCustInfoApply', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }


    /**
     * 图片上传
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $sysFlowId string 入网申请流水号,调用商户入网申请接口成功会返回入网申请流水号。
     * @param $picType string 图片类型， A001-营业执照 A002-法人身份证正面(头像面) A003-法人身份证反面(国徽面) A004-结算账户正面(卡号面) A005-结算账户反面 A006-商户门头照片 A007-内景照片 A008-收银台照片 A009-手持身份证合影照片 A010-收单协议盖章页 A011-开户许可证 A012-收单协议首页 A013-非法人身份证头像面 A014-非法人身份证国徽面 B001-租赁合同 第一页 B002-租赁合同 第二页 B003-租赁合同 第三页 B004-法人/非法人手持授权书 B005-法人/非法人结算授权书 B006-租赁面积图片 B007-经营业务图片 B008-其他1 B009-其他2
     * @param $filePath string 文件路径
     * @return array
     */
    public function imageUpload($reqId, $sysFlowId, $picType, $filePath)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('file.smsc.upload', $key, $reqId);
        $sha256 = hash_file('sha256', $filePath);
        $picNm = basename($filePath);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'meta' => [
                // 入网申请流水号,调用商户入网申请接口成功会返回入网申请流水号。
                'sysFlowId' => $sysFlowId,
                //图片类型， A001-营业执照 A002-法人身份证正面(头像面) A003-法人身份证反面(国徽面) A004-结算账户正面(卡号面) A005-结算账户反面 A006-商户门头照片 A007-内景照片 A008-收银台照片 A009-手持身份证合影照片 A010-收单协议盖章页 A011-开户许可证 A012-收单协议首页 A013-非法人身份证头像面 A014-非法人身份证国徽面 B001-租赁合同 第一页 B002-租赁合同 第二页 B003-租赁合同 第三页 B004-法人/非法人手持授权书 B005-法人/非法人结算授权书 B006-租赁面积图片 B007-经营业务图片 B008-其他1 B009-其他2
                'picType' => $picType,
                // 图片名称
                'picNm' => $picNm,
                // sha256
                'sha256' => $sha256,
            ]
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        //图片文件,将媒体图片进行二进制转换，得到的媒体图片二进制内容，在请求body中上传此二进制内容。媒体图片只支持JPG、JPEG、BMP、PNG格式，文件大小不能超过2M
        $reqMap['file'] = new \CURLFile(realpath($filePath));
        $resMap = HttpClient::post($this->baseUrl . '/openapi/file/smsc/upload', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 商户入网审核
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $sysFlowId string 入网申请流水号,调用商户入网申请接口成功会返回入网申请流水号
     * @param $auditFlag string 审核标志 Y通过,N拒绝
     */
    public function auditCustInfoApply($reqId, $sysFlowId, $auditFlag)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.auditCustInfoApply', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            // 入网申请流水号,调用商户入网申请接口成功会返回入网申请流水号。
            'sysFlowId' => $sysFlowId,
            // 审核标志 Y通过,N拒绝
            'auditFlag' => $auditFlag,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/t1/smsc/auditCustInfoApply', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 查询商户状态
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $sysFlowId string 入网申请流水号,调用商户入网申请接口成功会返回入网申请流水号
     */
    public function queryCustApply($reqId, $sysFlowId)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.queryCustApply', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            // 入网申请流水号,调用商户入网申请接口成功会返回入网申请流水号。
            'sysFlowId' => $sysFlowId,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/queryCustApply', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 合同签约
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $signData array 签约数据
     */
    public function smscSign($reqId, $signData)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.sign', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($signData), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/t1/smsc/sign', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 重发签约短信或邮件
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $signId string 签约ID,发起签约时候返回
     * @param $isSendConMsg string 签约通知标识 contractType=2时必填 0(短信+邮件) 1(短信) 2(邮件) 3(不通知)
     */
    public function sendSmsOrEmailMsg($reqId, $signId, $isSendConMsg)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.sign.sendSmsOrEmailMsg', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'signId' => $signId,
            'isSendConMsg' => $isSendConMsg,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/sign/sendSmsOrEmailMsg', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 电子合同查询签约状态
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $signId string 客户签约流水号
     */
    public function queryContract($reqId, $signId)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.sign.queryContract', $key, $reqId);
//        //版本号，该接口升级到2.0，按接口文档来
//        $reqMap['version'] = '2.0';
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'signId' => $signId,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/sign/queryContract', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 电子合同下载
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $signId string 签约ID,发起签约时候返回
     * @param $savePath string 保存文件路径,为空就不保存
     * @return array
     */
    public function downloadContract($reqId, $signId, $savePath = '')
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.sign.downloadContract', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'signId' => $signId,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/sign/downloadContract', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        if ($resMap['code'] == '00000' && !empty($savePath) && !empty($resMap['businessData'])) {
            $businessData = $resMap['businessData'];
            if (!empty($businessData['contractFileString'])) {
                FileUtil::saveFile($savePath, base64_decode($businessData['contractFileString']));
            }
        }
        return $resMap;
    }

    /**
     * 商户基本信息变更申请
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $baseInfo array 基本信息
     * @return array
     */
    public function changeMercBaseInfo($reqId, array $baseInfo)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.changeMercBaseInfo', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($baseInfo), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/changeMercBaseInfo', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 基本信息变更审核
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $changeSysFlowId string 变更申请流水号,基本信息变更申请接口返回
     * @param $auditFlag string 审核标志Y审核通过，N审核拒绝
     * @param $auditNote string 审核备注
     * @return array
     */
    public function changeBaseAudit($reqId, $changeSysFlowId, $auditFlag, $auditNote)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.changeBaseAudit', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'changeSysFlowId' => $changeSysFlowId,
            'auditFlag' => $auditFlag,
            'auditNote' => $auditNote,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/changeBaseAudit', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 结算信息变更申请
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param array $accInfo 结算信息
     * @return array
     */
    public function changeMercStlAccInfo($reqId, array $accInfo)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.changeMercStlAccInfo', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($accInfo), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/t1/smsc/changeMercStlAccInfo', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 结算信息变更审核
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $changeSysFlowId string 变更流水号 结算信息变更申请成功后返回
     * @param $auditFlag string 审核标志Y审核通过，N审核拒绝
     * @param $auditNote string 审核备注
     * @return array
     */
    public function changeStlAudit($reqId, $changeSysFlowId, $auditFlag, $auditNote)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.changeStlAudit', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'changeSysFlowId' => $changeSysFlowId,
            'auditFlag' => $auditFlag,
            'auditNote' => $auditNote,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/changeStlAudit', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 费率信息变更申请
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param array $rateInfo 费率信息
     * @return array
     */
    public function changeRate($reqId, array $rateInfo)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.changeRate', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($rateInfo), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/changeRate', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 费率信息变更审核
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $changeSysFlowId string 变更流水号 费率信息变更申请成功后返回
     * @param $auditFlag string 审核标志Y审核通过，N审核拒绝
     * @param $auditNote string 审核备注
     * @return array
     */
    public function changeRateAudit($reqId, $changeSysFlowId, $auditFlag, $auditNote)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.changeRateAudit', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'changeSysFlowId' => $changeSysFlowId,
            'auditFlag' => $auditFlag,
            'auditNote' => $auditNote,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/changeRateAudit', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 上传变更图片
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $changeFlowId string 变更申请流水号 变更申请成功后返回
     * @param $picType string 图片类型， A001-营业执照 A002-法人身份证正面(头像面) A003-法人身份证反面(国徽面) A004-结算账户正面(卡号面) A005-结算账户反面 A006-商户门头照片 A007-内景照片 A008-收银台照片 A009-手持身份证合影照片 A010-收单协议盖章页 A011-开户许可证 A012-收单协议首页 A013-非法人身份证头像面 A014-非法人身份证国徽面 B001-租赁合同 第一页 B002-租赁合同 第二页 B003-租赁合同 第三页 B004-法人/非法人手持授权书 B005-法人/非法人结算授权书 B006-租赁面积图片 B007-经营业务图片 B008-其他1 B009-其他2
     * @param $filePath string 文件路径
     * @return array
     */
    public function uploadChangePic($reqId, $changeFlowId, $picType, $filePath)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('file.smsc.uploadChangePic', $key, $reqId);
        $sha256 = hash_file('sha256', $filePath);
        $picNm = basename($filePath);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'meta' => [
                //变更申请流水号 变更申请成功后返回
                'changeFlowId' => $changeFlowId,
                //图片类型， A001-营业执照 A002-法人身份证正面(头像面) A003-法人身份证反面(国徽面) A004-结算账户正面(卡号面) A005-结算账户反面 A006-商户门头照片 A007-内景照片 A008-收银台照片 A009-手持身份证合影照片 A010-收单协议盖章页 A011-开户许可证 A012-收单协议首页 A013-非法人身份证头像面 A014-非法人身份证国徽面 B001-租赁合同 第一页 B002-租赁合同 第二页 B003-租赁合同 第三页 B004-法人/非法人手持授权书 B005-法人/非法人结算授权书 B006-租赁面积图片 B007-经营业务图片 B008-其他1 B009-其他2
                'picType' => $picType,
                // 图片名称
                'picNm' => $picNm,
                // sha256
                'sha256' => $sha256,
            ]
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $reqMap['file'] = new \CURLFile(realpath($filePath));
        $resMap = HttpClient::post($this->baseUrl . '/openapi/file/smsc/uploadChangePic', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 查询变更状态
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $changeSysFlowId string 入网申请流水号,调用商户入网申请接口成功会返回入网申请流水号
     * @return array
     */
    public function queryCustChange($reqId, $changeSysFlowId)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.queryCustChange', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            // 入网申请流水号,调用商户入网申请接口成功会返回入网申请流水号。
            'changeSysFlowId' => $changeSysFlowId,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/queryCustChange', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 报备
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $sweepData array 报备数据
     * @return array
     */
    public function sweepreport($reqId, array $sweepData)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.saas.sweep.sweepreport', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($sweepData), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/saas/sweep/sweepreport', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 重新报备
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $sweepData array 重新报备数据
     * @return array
     */
    public function sweepreportAgain($reqId, array $sweepData)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.saas.sweep.reportAgain', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($sweepData), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/saas/sweep/reportAgain', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 码牌绑定
     * 商户进件成功后，商户需要用银盛的码牌进行交易，可以调用本接口， 将银盛预制码与商户进行绑定
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $mercId string 商户号
     * @param $qryCodeUrl string 二维码识别内容,识别预制码的内容
     * @return array
     */
    public function bindQry($reqId, $mercId, $qryCodeUrl)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.bindQry', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'mercId' => $mercId,
            'qryCodeUrl' => $qryCodeUrl,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/bindQry', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 码牌查询
     * 查询商户码牌列表
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $mercId string 商户号
     * @return array
     */
    public function queryBindQry($reqId, $mercId)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.queryBindQry', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'mercId' => $mercId,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/queryBindQry', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 终端绑定
     * 商户进件成功后，商户需要用银盛的 pos 机进行交易，可以调用本接口，将pos 机与商户进行绑定
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param array $bindData 绑定数据
     * @return array
     */
    public function termBind($reqId, array $bindData)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.term.bind', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($bindData), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/term/bind', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 终端解绑
     * 需要将pos 机进行解除，可以调用本接口，将pos 机与商户进行解除绑定
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param array $unBindData 解绑数据
     * @return array
     */
    public function termUnBind($reqId, array $unBindData)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.term.unbind', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($unBindData), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/term/unbind', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 终端号查询
     * 通过商户号查其绑定终端列表
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $mercId string 商户号
     */
    public function queryTermList($reqId, $mercId)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('smsc.term.queryList', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'mercId' => $mercId,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/smsc/term/queryList', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * MCC码查询
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $mccCd string mcc码,mcc码和商户类型不能全部为空。
     * @param $mercType string 商户类型,1-个体工商户、 2-企业、 3-小微，mcc码和商户类型不能全部为空。
     * @return array
     */
    public function queryMccList($reqId, $mccCd, $mercType)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('aggregation.scan.mccQuery', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'mccCd' => $mccCd,
            'mercType' => $mercType,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/aggregation/scan/mccQuery', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 卡bin查询
     * 根据银行卡号查询卡bin
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param $bankCardNo string 银行卡号
     * @return array
     */
    public function findBankBinByBankCardNo($reqId, $bankCardNo)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('trade.findBankBinByBankCardNo', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode([
            'bankCardNo' => $bankCardNo,
        ]), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/trade/findBankBinByBankCardNo', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 地区信息查询
     * 通过本接口，可以获取省市区的地区码
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param array $findParam 请求参数
     * @return array
     */
    public function findCmmtAreaInfoList($reqId, array $findParam)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('pregate.trade.findCmmtAreaInfoList', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($findParam), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/pregate/trade/findCmmtAreaInfoList', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

    /**
     * 行名行号查询
     * 根据地区、行别、银行名称获取行名行号
     * @param $reqId string 流水号  要求32个字符内（最少14个字符），只能是数字、大小写字母_-且在同一个商户号下唯一
     * @param array $findParam 请求参数
     * @return array
     */
    public function findBankNameAndBankCode($reqId, array $findParam)
    {
        /** 生成对业务参数加密的随机密钥 */
        $key = StrUtil::randomStr();
        $reqMap = $this->genCommonReqParam('pregate.trade.findBankNameAndBankCode', $key, $reqId);
        /** 封装业务参数,具体参数见文档*/
        /** 使用生成的密钥key对业务参数进行加密，并将加密后的业务参数放入请求参数bizContent中*/
        $reqMap['bizContent'] = AESUtil::encrypt($this->jsonEncode($findParam), $key);
        // 签名
        $this->doSign($reqMap);
        $resMap = HttpClient::post($this->baseUrl . '/openapi/pregate/trade/findBankNameAndBankCode', $reqMap);
        $resMap = json_decode(base64_decode($resMap), true);
        $this->checkSign($resMap);
        $this->decryptBizData($resMap, $key);
        return $resMap;
    }

}