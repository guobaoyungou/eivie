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

//custom_file(wx_channels)
//视频号小店
namespace app\common;

use think\facade\Db;
use think\facade\Log;
use think\helper\Str;

class WxChannels
{
    //计费类型
    const valuation_type = [
        'PIECE' => '按件数',
        'WEIGHT'=>'按重量'
    ];
    //发货时间期限
    const send_time = [
        'SendTime_TWENTYFOUR_HOUR' => '24小时内发货',
        'SendTime_FOUTYEIGHT_HOUR'=>'48小时内发货',
        'SendTime_THREE_DAY'=>'3天内发货',
    ];
    //运输方式
    const delivery_type = [
        'EXPRESS' => '快递'
    ];
    //计费方式
    const shipping_method = [
        'FREE' => '包邮',
        'CONDITION_FREE' => '条件包邮',
        'NO_FREE' => '不包邮',
    ];
    //分类资质字段
    const category_file_type = [
        'certificate' => ['name'=>'资质材料','maxsize'=>'2*1024*1024','maxnum'=>10,'desc'=>'图片上传大小限制：2MB,最大数量：10张，必填'],
        'baobeihan' => ['name'=>'报备函','maxsize'=>'2*1024*1024','maxnum'=>10,'desc'=>'图片上传大小限制：2MB,最大数量：10张'],
        'jingyingzhengming' => ['name'=>'经营证明','maxsize'=>'2*1024*1024','maxnum'=>10,'desc'=>'图片上传大小限制：2MB,最大数量：10张'],
        'daihuokoubei' => ['name'=>'带货口碑','maxsize'=>'2*1024*1024','maxnum'=>10,'desc'=>'图片上传大小限制：2MB,最大数量：10张'],
        'ruzhuzhizhi' => ['name'=>'入住资质','maxsize'=>'2*1024*1024','maxnum'=>10,'desc'=>'图片上传大小限制：2MB,最大数量：10张'],
        'jingyingliushui' => ['name'=>'经营流水','maxsize'=>'2*1024*1024','maxnum'=>10,'desc'=>'图片上传大小限制：2MB,最大数量：10张'],
        'buchongcailiao' => ['name'=>'补充材料','maxsize'=>'2*1024*1024','maxnum'=>10,'desc'=>'图片上传大小限制：2MB,最大数量：10张'],
    ];
    //分类经营平台
    const category_plate = [
        'taobao' => '淘宝',
        'jd' => '京东',
        'douyin' => '抖音',
        'kuaishou' => '快手',
        'pdd' => '拼多多',
        'other' => '其他',
    ];
    //分类状态
    const cat_status = [
        1 => '审核中',
        2 => '审核拒绝',
        3 => '审核通过',
        12 => '主动取消申请单',
    ];
    //品牌资质字段
    const brand_file_type = [
        'register_certifications' => ['name'=>'商标注册证','maxsize'=>'2*1024*1024','maxnum'=>1,'desc'=>'图片上传大小限制：2MB,最大数量：1张,R标时必填'],
        'renew_certifications' => ['name'=>'变更/续展证明','maxsize'=>'2*1024*1024','maxnum'=>1,'desc'=>'图片上传大小限制：2MB,最大数量：1张,R标时必填'],
        'acceptance_certification' => ['name'=>'商标注册申请受理书','maxsize'=>'2*1024*1024','maxnum'=>1,'desc'=>'图片上传大小限制：2MB,最大数量：1张,TM标时必填'],
        'grant_certifications' => ['name'=>'品牌销售授权书','maxsize'=>'2*1024*1024','maxnum'=>9,'desc'=>'图片上传大小限制：2MB,最大数量：9张,授权品牌必填'],
        'brand_owner_id_photos' => ['name'=>'品牌权利人证件照','maxsize'=>'2*1024*1024','maxnum'=>2,'desc'=>'图片上传大小限制：2MB,最大数量：2张'],
    ];
    //品牌资质状态
    const brand_status = [
        1 => '新增品牌',
        2 => '更新品牌',
        3 => '撤回品牌审核',
        4 => '审核成功',
        5 => '审核失败',
        6 => '删除品牌',
        7 => '品牌资质被系统撤销',
        8 => '品牌资质过期',
    ];
    //商品状态
    const product_status = [
        0 => '未上架',
        5 => '上架',
        6 => '回收站',
        11 => '自主下架',
        13 => '违规下架/风控系统下架',
        14 => '保证金不足下架',
        15 => '品牌过期下架',
        20 => '商品被封禁',
    ];
    //商品草稿状态
    const product_edit_status = [
        0 => '未上架',
        1 => '编辑中',
        2 => '发布审核中',
        3 => '发布审核失败',
        4 => '发布审核成功',
        7 => '商品异步提交，上传中',
        8 => '商品异步提交，上传失败',
    ];
    const product_audit_status = [
        2 => '审核不通过',
        3 => '审核通过',
        4 => '撤销审核',
    ];
    //商品库存流水类型
    const stock_op_type = [
        1 => "设置库存",
        2 => "增加库存",
        3 => "减少库存",
        4 => "下单扣除库存",
        5 => "取消订单释放库存",
        6 => "分配库存",
        7 => "归还库存"
    ];
    //订单状态
    const order_status = [
        10 => '待付款',
        20 => '待发货',
        21 => '部分发货',
        30 => '待收货',
        100 => '完成',
        200 => '全部商品售后，订单取消',
        250 => '未付款订单取消',
    ];
    //分享员类型
    const sharer_type = [
        0 => '普通分享员',
        1 => '店铺分享员',
    ];
    //分享类型
    const share_scene = [
        1 => '直播间',
        2 => '橱窗',
        3 => '短视频',
        4 => '视频号主页',
        5 => '商品详情页',
        6 => '公众号文章',
    ];
    //订单支付方式
    const payment_method = [
        1 => '微信支付',
        2 => '先用后付',
        3 => '抽奖商品0元订单',
        4 => '会员积分兑换订单',
    ];
    //售后单状态
    const after_sale_status = [
        "USER_CANCELD" =>"用户取消申请",
        "MERCHANT_PROCESSING" => "商家受理中",
        "MERCHANT_REJECT_REFUND" => "商家拒绝退款",
        "MERCHANT_REJECT_RETURN" => "商家拒绝退货退款",
        "USER_WAIT_RETURN" => "待买家退货",
        "RETURN_CLOSED" => "退货退款关闭",
        "MERCHANT_WAIT_RECEIPT" => "待商家收货",
        "MERCHANT_OVERDUE_REFUND" => "商家逾期未退款",
        "MERCHANT_REFUND_SUCCESS" => "退款完成",
        "MERCHANT_RETURN_SUCCESS" => "退货退款完成",
        "PLATFORM_REFUNDING" => "平台退款中",
        "PLATFORM_REFUND_FAIL" => "平台退款失败",
        "USER_WAIT_CONFIRM" => "待用户确认",
        "MERCHANT_REFUND_RETRY_FAIL" => "商家打款失败，客服关闭售后",
        "MERCHANT_FAIL" => "售后关闭",
        "USER_WAIT_CONFIRM_UPDATE" => "待用户处理商家协商",
        "USER_WAIT_HANDLE_MERCHANT_AFTER_SALE" => "待用户处理商家代发起的售后申请",
    ];
    //售后原因
    const after_sale_reason = [
        "NCORRECT_SELECTION" => "拍错/多拍",
        "NO_LONGER_WANT" => "不想要了",
        "NO_EXPRESS_INFO" => "无快递信息",
        "EMPTY_PACKAGE" => "包裹为空",
        "REJECT_RECEIVE_PACKAGE" => "已拒签包裹",
        "NOT_DELIVERED_TOO_LONG" => "快递长时间未送达",
        "NOT_MATCH_PRODUCT_DESC" => "与商品描述不符",
        "QUALITY_ISSUE" => "质量问题",
        "SEND_WRONG_GOODS" => "卖家发错货",
        "THREE_NO_PRODUCT" => "三无产品",
        "FAKE_PRODUCT" => "假冒产品",
        "NO_REASON_7_DAYS" => "七天无理由",
        "INITIATE_BY_PLATFORM" => "平台代发起",
        "OTHERS" => "其它",
    ];
    //售后类型
    const after_sale_type = [
        'REFUND' => '退款',
        'RETURN' => '退货退款',
    ];
    //退款原因
    const after_refund_reason = [
        "1" => "商家通过店铺管理页或者小助手发起退款",
        "2" => "退货退款场景，商家同意买家未上传物流单号情况下确认收货并退款，该场景限于订单无运费险",
        "3" => "商家通过后台api发起退款",
        "4" => "未发货售后平台自动同意",
        "5" => "平台介入纠纷退款",
        "6" => "特殊场景下平台强制退款",
        "7" => "退货退款场景，买家同意没有上传物流单号情况下，商家确认收货并退款，该场景限于订单包含运费险，并无法理赔",
        "8" => "商家发货超时，平台退款",
        "9" => "商家处理买家售后申请超时，平台自动同意退款",
        "10" => "用户确认收货超时，平台退款",
        "11" => "商家确认收货超时，平台退款",
    ];
    //优惠券类型
    const coupon_type = [
        1 => '商品条件折扣券',
        2 => '商品满减券',
        3 => '商品统一折扣券',
        4 => '商品直减券',
        101 => '店铺条件折扣券',
        102 => '店铺满减券',
        103 => '店铺统一折扣券',
        104 => '店铺直减券',
    ];
    //优惠券推广类型
    const coupon_promote_type = [
        1 => '店铺内推广',
        9 => '会员券',
        10 => '会员开卡礼券',
    ];
    //优惠券有效期类型 https://developers.weixin.qq.com/doc/channels/API/coupon/get.html#valid_type
    const coupon_valid_type = [
        1 => '生效时间',//商品指定时间区间
        2 => '领取后'//生效天数
    ];
    //优惠券状态 https://developers.weixin.qq.com/doc/channels/API/coupon/get_user_coupon.html#status
    const user_coupon_status = [
        100 => '生效中',
        101 => '已过期',
        102 => '已使用',
    ];
    //优惠券状态 https://developers.weixin.qq.com/doc/channels/API/coupon/update_status.html
    const coupon_status = [
        1 => '未生效',
        2 => '生效中',
        3 => '已过期',
        4 => '已作废',
        5 => '删除',
    ];
    //优惠券自动生效类型
    const auto_valid_type = [
        0 => '不启用自动生效',
        1 => '启用自动生效',
    ];
    //结算账户银行卡类型
    const bank_account_type = [
        'ACCOUNT_TYPE_BUSINESS' => '公户',
        'ACCOUNT_TYPE_PRIVATE' => '个人',
    ];
    //资金类型
    const funds_type = [
        1 => "订单支付收入",
        2 => "订单手续费",
        3 => "退款",
        4 => "提现",
        5 => "提现失败退票",
        10 => "联盟抽佣",
        11 => "平台抽佣",
        12 => "团长抽佣",
        13 => "返佣人气卡",
        16 => "运费险"
    ];
    const flow_type = [
        1 => '收入',
        2 => '支出'
    ];
    //提现状态
    const withdraw_status = [
        'MP_WITHDRAW_START' => '提现撤回',
        'CREATE_SUCCESS' => '受理成功',
        'SUCCESS' => '提现成功',
        'FAIL' => '提现失败',
        'REFUND' => '提现退票',
        'CLOSE' => '关单',
        'INIT' => '业务单已创建'
    ];
    //电子面单模板信息类型
    const option_ids = [
        0 => '商品总数量',
        1 => '商品名称+规格+编码+数量',
        2 => '商品名称+规格+数量',
        3 => '商品名称+数量',
        4 => '店铺名称',
        5 => '订单号',
        6 => '买家留言',
        7 => '卖家备注'
    ];
    //面单账号状态
    const ewaybill_account_status = [
        1 => "绑定审核中",
        2 => "取消绑定审核中",
        3 => "已绑定",
        4 => "已解除绑定",
        5 => "绑定未通过",
        6 => "取消绑定未通过"
    ];


    //全部品牌信息 https://developers.weixin.qq.com/doc/channels/API/brand/all_get.html
    public static function brandAll($aid,$bid,$appid, $page_size = 10, $next_key = '')
    {
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/brand/all?access_token={$access_token}";
        $params = [
            "page_size" => $page_size,
        ];
        if (!empty($next_key)) {
            $params['next_key'] = $next_key;
        }
        $res = json_decode(curl_post($url, jsonEncode($params)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return $res['cats'];
        }
    }


    //获取类目详细信息 https://developers.weixin.qq.com/doc/channels/API/category/getcategorydetail.html
    public static function catDetail($aid,$bid,$appid, $cat_id)
    {
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/category/detail?access_token={$access_token}";
        $data = [
            "cat_id" => $cat_id
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'info' => $res['info'],
                'attr' => $res['attr'],
                'product_qua_list' => $res['product_qua_list'] ?? []
            ];
        }


    }


    //可用子类目详情 https://developers.weixin.qq.com/doc/channels/API/category/getavailablesoncategories.html
    public static function availableSonCategories($aid,$bid,$appid, $is_save=true)
    {

//        return ['status'=>1,'data'=>'同步成功'];
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/category/all?access_token={$access_token}";
        $res = json_decode(request_get($url), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            $data = $res['cats_v2'];
            if ($is_save) {
                $already_ids = [];
                foreach ($data as $cate) {
                    $cat_and_qua = $cate['cat_and_qua'];

                    foreach ($cat_and_qua as $item) {
                        if (!in_array($item['cat']['cat_id'], $already_ids)) {
                            $product_qua_list = $item['product_qua_list'];
                            $need_apply = 0;
                            if(($product_qua_list && $product_qua_list[0]['need_to_apply']==1) || $item['qua']['need_to_apply']==1 ){
                                $need_apply = 1;
                            }
                            if( $item['cat']['cat_id']=='1083'){
                               // dump($cate);
                              //  dump($item);exit;
                            }
                            $insert_data = [
                                "aid" => $aid,
                                "bid" => $bid,
                                "appid" => $appid,
                                "cat_id" => $item['cat']['cat_id'],
                                "name" => $item['cat']['name'],
                                "f_cat_id" => $item['cat']['f_cat_id'],
                                "level" => $item['cat']['level'],
                                "qua" => isset($item['qua']) ? jsonEncode($item['qua']) : [],
                                "product_qua_list" => isset($item['product_qua_list']) ? jsonEncode($item['product_qua_list']) : [],
                                "brand_qua" => isset($item['brand_qua']) ? jsonEncode($item['brand_qua']) : [],
                                'need_to_apply' => $item['cat']['leaf']?1:0,
                            ];
                            $already_ids[] = $item['cat']['cat_id'];
                            $e = Db::name("channels_category")->where('cat_id', $item['cat']['cat_id'])->where('appid',$appid)->find();

                            if ($e) {
                                Db::name("channels_category")->where('id', $e['id'])->update($insert_data);
                            } else {
                                $insert_data['appid'] = $appid;
                                Db::name("channels_category")->insert($insert_data);
                            }
                        }
                    }
                }
                //获取生效中的类目，更新申请状态
                $url = "https://api.weixin.qq.com/shop/ec/category/get_category_relation_list?access_token={$access_token}";
                $data = [
                    "is_filter_status" => true,
                    'status' => 1
                ];
                $res = json_decode(curl_post($url, jsonEncode($data)),true);
                if ($res['errcode'] == 0) {
                    $list = $res['list'];
                    if($list){
                        $cat_ids = array_column($list,'id');
                        Db::name("channels_category")->where('appid',$appid)->where('cat_id','in',$cat_ids)->update(['need_to_apply'=>0]);
                    }
                }
            }

        }
        return ['status'=>1,'data'=>'同步成功'];
    }


    //验证图片地址是否可用
    public static function checkImgValid($img_url)
    {
        if(Str::contains($img_url,'mmecimage.cn')){
            return $img_url;
        }else{
            return false;
        }
    }

    //上传图片 https://developers.weixin.qq.com/doc/channels/API/basics/img_upload.html
    public static function uploadImage($aid,$bid,$appid, $media_url,$resp_type=1)
    {
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/basics/img/upload?access_token={$access_token}&upload_type=1&resp_type=".$resp_type;
        $data = [
            "img_url" => $media_url,
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return [
                "status" => 1,
                "img_url" => $res['pic_file']['img_url']?:$res['pic_file']['media_id']
            ];
        }
    }

    //上传资质 https://developers.weixin.qq.com/doc/channels/API/basics/qualificationupload.html
    public static function uploadQualification($aid,$bid,$appid, $media_url='',$media='',$PRE_URL)
    {
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/basics/qualification/upload?access_token={$access_token}";
        if(!$media && $media_url){
            $path = Pic::tolocal($media_url,$aid);
            $mediapath = ROOT_PATH . str_replace($PRE_URL . '/', '', $path);
            $media = new \CURLFile($mediapath);
        }
        $data = [
            "media" => $media
        ];
        $res = json_decode(curl_post($url, $data), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'file_id' => $res['data']['file_id']
            ];
        }

    }

    //通过mediaid获取图片
    public static function getmedia($aid,$bid,$appid,$media_id){
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/basics/media/get?access_token={$access_token}&media_id=".$media_id;
        $res = request_get($url);
        $res_arr = json_decode($res,true);
        if(isset($res_arr['errcode'])){
            return [
                'status' => 0,
                'msg' => $res_arr['errmsg']
            ];
        }else{
            return [
                'status' => 1,
                'data' => $res
            ];
        }
    }

    //全部类目信息 https://developers.weixin.qq.com/doc/channels/API/category/getallcategory.html
    public static function categoryAll($aid,$bid,$appid, $is_save = false)
    {
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/category/all?access_token={$access_token}";
        $res = json_decode(request_get($url), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            $data = $res['cats_v2'];
            if ($is_save) {
                $already_ids = [];
                foreach ($data as $cate) {
                    $cat_and_qua = $cate['cat_and_qua'];
                    foreach ($cat_and_qua as $item) {
                        if (!in_array($item['cat']['cat_id'], $already_ids)) {
                            $insert_data = [
                                "aid" => $aid,
                                "bid" => $bid,
                                "cat_id" => $item['cat']['cat_id'],
                                "name" => $item['cat']['name'],
                                "f_cat_id" => $item['cat']['f_cat_id'],
                                "level" => $item['cat']['level'],
                                "qua" => isset($item['qua']) ? jsonEncode($item['qua']) : [],
                                "product_qua_list" => isset($item['product_qua_list']) ? jsonEncode($item['product_qua_list']) : [],
                                "brand_qua" => isset($item['brand_qua']) ? jsonEncode($item['brand_qua']) : [],
                                'need_to_apply' => $item['qua']['need_to_apply']?:0,
                            ];
                            $already_ids[] = $item['cat']['cat_id'];
                            $e = Db::name("channels_category_basic")->where('cat_id', $item['cat']['cat_id'])->where('appid',$appid)->find();
                            if ($e) {
                                Db::name("channels_category_basic")->where('id', $e['id'])->update($insert_data);
                            } else {
                                $insert_data['appid'] = $appid;
                                Db::name("channels_category_basic")->insert($insert_data);
                            }
                        }
                    }
                }
            }
            return ['status'=>1,'msg'=>$data];
        }
    }

    //上传类目资质
    public static function applyCategory($aid,$bid,$appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/category/add?access_token={$access_token}";
        $data = [
            'category_info' => $data?:[],
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        //dump($res);exit;
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['audit_id']
            ];
        }
    }
    //撤销类目资质审核
    public static function cancelCategory($aid,$bid,$appid,$audit_id)
    {
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/category/audit/cancel?access_token={$access_token}";
        $data = [
            'audit_id' => $audit_id,
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }

    //店铺基础信息 https://developers.weixin.qq.com/doc/channels/API/basics/getbasicinfo.html
    public static function baseInfo($aid,$bid,$appid)
    {
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/basics/info/get?access_token={$access_token}";
        $res = json_decode(request_get($url), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'data' => $res['info']
            ];
        }
    }

    //获取运费模板列表
    public static function getfreighttemplatelist($aid,$bid,$appid,$offset,$limit)
    {
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/merchant/getfreighttemplatelist?access_token={$access_token}";
        $data = [
            'offset' => $offset?:0,
            'limit' => $limit?:10
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['template_id_list']
            ];
        }
    }
    //查询运费模板信息
    public static function getfreighttemplatedetail($aid,$bid,$appid,$template_id)
    {
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/merchant/getfreighttemplatedetail?access_token={$access_token}";
        $data = [
            'template_id' => $template_id?:'',
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['freight_template']
            ];
        }
    }
    //添加运费模板
    public static function addfreighttemplate($aid,$bid,$appid,$freight_template){
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/merchant/addfreighttemplate?access_token={$access_token}";
        $freight_template['is_default'] = $freight_template['is_default']==1?true:false;
        unset($freight_template['id']);
        unset($freight_template['template_id']);
        $data = [
            'freight_template' => $freight_template?:[],
        ];
//        dump($data);exit;
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['template_id']
            ];
        }
    }
    //更新运费模板
    public static function updatefreighttemplate($aid,$bid,$appid,$freight_template){
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/merchant/updatefreighttemplate?access_token={$access_token}";
        $freight_template['is_default'] = $freight_template['is_default']==1?true:false;
        unset($freight_template['id']);
        $data = [
            'freight_template' => $freight_template?:[],
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['template_id']
            ];
        }
    }
    //获取区域地址数据
    public static function getarea($aid,$bid,$appid,$addr_code=0){
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/basics/addresscode/get?access_token={$access_token}";
        $data = [
            'addr_code' => intval($addr_code?:0),
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'addrs_msg' => $res['addrs_msg'],
                'next_level_addrs' => $res['next_level_addrs']
            ];
        }
    }
    //小店状态
    public static function statusText($status)
    {
        if ($status == "opening" || $status == "open_finished") {
            return 1;
        }
        if ($status == "closing" || $status == "close_finished") {
            return 0;
        }
        return 3;
    }

    //获取品牌库列表
    public static function asyncBrandAll($aid,$bid,$appid,$next_key){
        $access_token = self::getAccessToken($aid,$bid,$appid);
        $url = "https://api.weixin.qq.com/channels/ec/brand/all?access_token={$access_token}";
        $data = [
            'page_size' => 50,
        ];
        if($next_key){
            $data['next_key'] = (string)$next_key;
        }
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['brands'],
                'next_key' => $res['next_key'],
                'continue_flag' => $res['continue_flag'],
            ];
        }
    }
    //获取生效中的品牌资质列表
    public static function asyncBrandValid($aid,$bid,$appid,$next_key){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/brand/valid/list/get?access_token={$access_token}";
        $data = [
            'page_size' => 50,
        ];
        if($next_key){
            $data['next_key'] = $next_key;
        }
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['brands'],
                'next_key' => $res['next_key'],
                'continue_flag' => $res['continue_flag'],
            ];
        }
    }

    //新增品牌资质
    public static function applyBrand($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/brand/add?access_token={$access_token}";
        $data = [
            'brand' => $data?:[],
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['audit_id']
            ];
        }
    }
    //更新品牌资质
    public static function updateBrand($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/brand/update?access_token={$access_token}";
        $data = [
            'brand' => $data?:[],
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['audit_id']
            ];
        }
    }
    //删除品牌资质 https://developers.weixin.qq.com/doc/channels/API/brand/delete.html
    public static function deleteBrand($aid,$bid, $appid, $brand_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/brand/delete?access_token={$access_token}";
        $data = [
            'brand_id' => $brand_id,
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['audit_id']
            ];
        }
    }

    //获取商品列表
    public static function asyncProductAll($aid,$bid,$appid,$next_key,$status=0){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/product/list/get?access_token={$access_token}";
        $data = [
            'page_size' => 30,
        ];
        if($status){
            $data['status'] = $status;
        }
        if($next_key){
            $data['next_key'] = $next_key;
        }
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['product_ids'],
                'next_key' => $res['next_key'],
                'continue_flag' => $res['continue_flag'],
            ];
        }
    }
    //获取商品 https://developers.weixin.qq.com/doc/channels/API/product/get.html
    public static function productDetail($aid,$bid, $appid, $product_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/product/get?access_token={$access_token}";
        $data = [
            "product_id" => (string)$product_id,
            "data_type" => 3
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'data' => $res['product']?:$res['edit_product'],
            ];
        }
    }

    //添加商品
    public static function addProduct($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/product/add?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['data']['product_id']
            ];
        }
    }
    //编辑商品
    public static function updateProduct($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/product/update?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['data']['product_id']
            ];
        }
    }
    //删除商品
    public static function deleteProduct($aid,$bid, $appid,$product_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/product/delete?access_token={$access_token}";
        $data = [
            'product_id' => $product_id?:'',
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => $res['errmsg']?:self::geterror($res),
            ];
        }
    }
    //上架商品
    public static function listingProduct($aid,$bid, $appid,$product_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/product/listing?access_token={$access_token}";
        $data = [
            'product_id' => $product_id?:'',
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => $res['errmsg']?:self::geterror($res),
            ];
        }
    }
    //下架商品
    public static function delistingProduct($aid,$bid, $appid,$product_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/product/delisting?access_token={$access_token}";
        $data = [
            'product_id' => $product_id?:'',
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => $res['errmsg']?:self::geterror($res),
            ];
        }
    }
    //撤回商品审核
    public static function cancelProduct($aid,$bid, $appid,$product_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/product/audit/cancel?access_token={$access_token}";
        $data = [
            'product_id' => $product_id?:'',
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => $res['errmsg']?:self::geterror($res),
            ];
        }
    }
    //获取商品库存
    public static function getProductStock($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/product/stock/get?access_token={$access_token}";

        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'data' => $res['data']?:[],
            ];
        }
    }
    //获取商品库存流水
    public static function getProductStockFlow($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/product/stock/getflow?access_token={$access_token}";

        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'data' => $res['data']['stock_flow_info_list']?:[],
                'next_key' => $res['data']['next_key']?:'',
            ];
        }
    }
    //获取商品H5短链
    public static function getProductH5url($aid,$bid,$appid,$product_id){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $data = [
            'product_id' => $product_id,
        ];
        $url = "https://api.weixin.qq.com/channels/ec/product/h5url/get?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['product_h5url'],
            ];
        }
    }
    //获取商品口令
    public static function getProductTaglink($aid,$bid,$appid,$product_id){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $data = [
            'product_id' => $product_id,
        ];
        $url = "https://api.weixin.qq.com/channels/ec/product/taglink/get?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['product_taglink'],
            ];
        }
    }
    //获取商品二维码
    public static function getProductQrcode($aid,$bid,$appid,$product_id){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $data = [
            'product_id' => $product_id,
        ];
        $url = "https://api.weixin.qq.com/channels/ec/product/qrcode/get?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['product_qrcode'],
            ];
        }
    }
    //获取订单列表
    public static function asyncOrderAll($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/order/list/get?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['order_id_list'],
                'next_key' => $res['next_key'],
                'has_more' => $res['has_more'],
            ];
        }
    }
    //获取订单详情
    public static function orderDetail($aid,$bid, $appid, $order_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/order/get?access_token={$access_token}";
        $data = [
            "order_id" => $order_id
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'data' => $res['order'],
            ];
        }
    }
    //修改订单价格
    public static function updataOrderPrice($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/order/price/update?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }
    //修改订单备注
    public static function updataOrderNotice($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/order/merchantnotes/update?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }
    //修改订单地址
    public static function updataOrderAdr($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/order/address/update?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }
    //获取快递公司列表
    public static function getDeliveryLists($aid,$bid, $appid)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        //$url = "https://api.weixin.qq.com/channels/ec/order/deliverycompanylist/get?access_token={$access_token}";
        //20250710 切换新接口
        $url = "https://api.weixin.qq.com/channels/ec/order/deliverycompanylist/new/get?access_token={$access_token}";
        $data = new \stdClass();
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['company_list']
            ];
        }
    }
    //订单发货
    public static function sendOrder($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/order/delivery/send?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }
    //修改订单物流信息
    public static function updataDeliveryinfo($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/order/deliveryinfo/update?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }

    //获取售后单列表
    public static function asyncAfterSaleAll($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/aftersale/getaftersalelist?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['after_sale_order_id_list'],
                'next_key' => $res['next_key'],
                'has_more' => $res['has_more'],
            ];
        }
    }
    //获取售后单详情
    public static function afterSaleDetail($aid,$bid, $appid, $after_sale_order_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/aftersale/getaftersaleorder?access_token={$access_token}";
        $data = [
            "after_sale_order_id" => $after_sale_order_id
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'data' => $res['after_sale_order'],
            ];
        }
    }
    //拒绝售后
    public static function acceptAfterSale($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/aftersale/acceptapply?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }
    //拒绝售后
    public static function rejectAfterSale($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/aftersale/rejectapply?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }
    //获取拒绝售后原因
    public static function rejectReasonList($aid,$bid, $appid)
    {
        if(cache('reject_reason_list')){
            return [
                'status' => 1,
                'data' => cache('reject_reason_list'),
            ];
        }
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/aftersale/rejectreason/get?access_token={$access_token}";
        $data = new \stdClass();
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            cache('reject_reason_list',$res['reject_reason_list'],3600);
            return [
                'status' => 1,
                'data' => $res['reject_reason_list'],
            ];
        }
    }
    //上传退款凭证
    public static function uploadRefund($aid,$bid, $appid,$data)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/aftersale/uploadrefundcertificate?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'data' => '',
            ];
        }
    }

    //获取地址列表
    public static function getAddressList($aid,$bid, $appid,$offset,$limit)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/merchant/address/list?access_token={$access_token}";
        $data = [
            'offset' => $offset?:0,
            'limit' => $limit?:10
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['address_id_list']
            ];
        }
    }
    //获取地址详情
    public static function getAddressDetail($aid,$bid, $appid,$address_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/merchant/address/get?access_token={$access_token}";
        $data = [
            'address_id' => $address_id?:0,
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['address_detail']
            ];
        }
    }
    //添加地址
    public static function addAddress($aid,$bid, $appid,$params)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/merchant/address/add?access_token={$access_token}";
        $data = [
            'address_detail' => $params
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['address_id']
            ];
        }
    }
    //更新地址
    public static function updateAddress($aid,$bid, $appid,$params)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/merchant/address/update?access_token={$access_token}";
        $data = [
            'address_detail' => $params
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['address_detail']
            ];
        }
    }
    //删除小店地址
    public static function delAddress($aid,$bid, $appid,$address_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/merchant/address/delete?access_token={$access_token}";
        $data = [
            'address_id' => $address_id
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }

    //获取优惠券列表
    public static function asyncCouponAll($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/coupon/get_list?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['coupons'],
                'next_key' => $res['page_ctx'],
            ];
        }
    }
    //获取订单详情
    public static function couponDetail($aid,$bid, $appid, $coupon_id)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/coupon/get?access_token={$access_token}";
        $data = [
            "coupon_id" => $coupon_id
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'data' => $res['coupon'],
            ];
        }
    }

    /**
     * 获取优惠券金额描述
     * @param $conponInfo
     * @param $couponType
     * @return void
     */
    public static function getCouponMoneyStr($conponInfo,$couponType)
    {
        $conponInfo['discount_num'] = $conponInfo['discount_num'] / 1000;
        $moneystr = '';
        if($couponType == 1){
            //商品条件折扣券
            $moneystr = $conponInfo['discount_num'].'折';
        }elseif($couponType == 2){
            //商品满减券
            $moneystr = '满'.$conponInfo['product_price'].'-'.$conponInfo['discount_fee'];
        }elseif($couponType == 3){
            //商品统一折扣券
            $moneystr = $conponInfo['discount_num'].'折';
        }elseif($couponType == 4){
            //商品直减券
            $moneystr = '立减'.$conponInfo['discount_fee'];
        }elseif($couponType == 101){
            //店铺条件折扣券
            $moneystr = '满'.$conponInfo['product_cnt'].'件'.$conponInfo['discount_num'].'折';
        }elseif($couponType == 102){
            //店铺满减券
            $moneystr = '全店通用 满'.$conponInfo['product_price'].'-'.$conponInfo['discount_fee'];
        }elseif($couponType == 103){
            //店铺统一折扣券
            $moneystr = '全店通用 '.$conponInfo['discount_num'].'折';
        }elseif($couponType == 104){
            //店铺直减券
            $moneystr = '全店通用 减'.$conponInfo['discount_fee'];
        }

        return $moneystr;

    }
    /**
     * 获取优惠券有效期描述
     * @param $conponInfo
     * @param $couponType
     * @return void
     */
    public static function getCouponValidStr($conponInfo,$validType)
    {
        $str = '';
        if($validType == 1){
            //1 => '商品指定时间区间'
            $str = date('Y-m-d H:i:s',$conponInfo['valid_start_time']).'-'.date('Y-m-d H:i:s',$conponInfo['valid_end_time']);
        }elseif($validType == 2){
            //生效天数
            $str = '领取后'.$conponInfo['valid_day_num'].'天';
        }

        return $str;

    }

    //添加优惠券
    public static function addCoupon($aid,$bid, $appid,$params)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/coupon/create?access_token={$access_token}";

        $res = json_decode(curl_post($url, json_encode($params)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['data']['coupon_id']
            ];
        }
    }
    //编辑优惠券
    public static function editCoupon($aid,$bid, $appid,$params)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/coupon/update?access_token={$access_token}";

        $res = json_decode(curl_post($url, jsonEncode($params)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }
    //修改优惠券状态
    public static function editCouponStaus($aid,$bid, $appid,$params)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/coupon/update_status?access_token={$access_token}";

        $res = json_decode(curl_post($url, jsonEncode($params)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }

    //获取优惠券列表
    public static function asyncUserCouponAll($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/coupon/get_user_coupon_list?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['user_coupon_list'],
                'next_key' => $res['page_ctx'],
            ];
        }
    }
    //获取订单详情
    public static function userCouponDetail($aid,$bid, $appid, $user_coupon_id,$openid)
    {
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/coupon/get_user_coupon?access_token={$access_token}";
        $data = [
            "openid" => $openid,
            "user_coupon_id" => $user_coupon_id
        ];
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'data' => $res['user_coupon'],
            ];
        }
    }

    //获取绑定的分享员
    public static function getSharer($aid,$bid,$appid,$openid,$username=''){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/sharer/search_sharer?access_token={$access_token}";
        $data = [];
        if($openid){
            $data['openid'] = $openid;
        }
        if($username){
            $data['username'] = $username;
        }
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res,
            ];
        }
    }
    //获取分享员列表
    public static function asyncSharerAll($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/sharer/get_sharer_list?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['sharer_info_list'],
            ];
        }
    }
    //解绑分享员
    public static function unbindSharer($aid,$bid,$appid,$openid_list){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $data = [
            'openid_list' => $openid_list
        ];
        $url = "https://api.weixin.qq.com/channels/ec/sharer/unbind?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res,
            ];
        }
    }
    //获取分享员专属的商品H5短链
    public static function getSharerH5url($aid,$bid,$appid,$product_id,$openid){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $data = [
            'product_id' => $product_id,
            'openid' => $openid
        ];
        $url = "https://api.weixin.qq.com/channels/ec/sharer/get_sharer_product_h5url?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['product_h5url'],
            ];
        }
    }
    //获取分享员专属的商品口令
    public static function getSharerTaglink($aid,$bid,$appid,$product_id,$openid){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $data = [
            'product_id' => $product_id,
            'openid' => $openid
        ];
        $url = "https://api.weixin.qq.com/channels/ec/sharer/get_sharer_product_taglink?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['product_taglink'],
            ];
        }
    }
    //获取分享员专属的商品二维码
    public static function getSharerQrcode($aid,$bid,$appid,$product_id,$openid){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $data = [
            'product_id' => $product_id,
            'openid' => $openid
        ];
        $url = "https://api.weixin.qq.com/channels/ec/sharer/get_sharer_product_qrcode?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['product_qrcode'],
            ];
        }
    }
    //邀请分享员
    public static function getBindUrl($aid,$bid,$appid,$username,$params=[]){
        $access_token = self::getAccessToken($aid,$bid, $appid,$params);
        $data = [
            'username' => $username?:'',
        ];
        $url = "https://api.weixin.qq.com/channels/ec/sharer/bind?access_token={$access_token}";
        $res = curl_post($url, json_encode($data));
        //这个数据里面有二进制数据，json_decode解析不出来，使用正则匹配
        preg_match_all('/"(?<key>[^"]+)"\s*:\s*"(?<value>[^"]+)"/', $res, $matches);
        $keys = $matches['key'];
        $values = $matches['value'];
        $arr = [];
        foreach ($keys as $index => $key) {
            $arr[$key] = $values[$index];
        }
        if ($arr['errmsg']!='ok') {
            return [
                'status' => 0,
                'msg' => $arr['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'qrcode_img' => $arr['qrcode_img'],//二进制
                'qrcode_img_base64' => $arr['qrcode_img_base64'],//base64
            ];
        }
    }
    //获取分享员订单
    public static function asyncSharerOrders($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/sharer/get_sharer_order_list?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['order_list'],
            ];
        }
    }
    //获取结算账户
    public static function getBankAcct($aid,$bid,$appid,$data=[]){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/funds/getbankacct?access_token={$access_token}";
        $data = new \stdClass();
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['account_info'],
            ];
        }
    }

    //获取银行省份列表
    public static function getBankProvince($aid,$bid,$appid){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/shop/funds/getprovince?access_token={$access_token}";
        $data = new \stdClass();
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if (empty($res['data']) ) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['data'],
            ];
        }
    }
    //获取银行市列表
    public static function getBankCity($aid,$bid,$appid,$province_code){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/shop/funds/getcity?access_token={$access_token}";
        $data = [
            'province_code' => (int)$province_code
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['data'],
            ];
        }
    }
    //根据银行卡号查询银行信息
    public static function getBankByAccount($aid,$bid,$appid,$account_number){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/shop/funds/getbankbynum?access_token={$access_token}";
        $data = [
            'account_number' => $account_number
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['data'],
            ];
        }
    }
    //获取支行列表
    public static function getBankBranch($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/shop/funds/getsubbranch?access_token={$access_token}";

        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['data'],
                'count' => $res['count'],
                'total_count' => $res['total_count'],
            ];
        }
    }
    //获取银行列表
    public static function getBankLists($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/shop/funds/getbanklist?access_token={$access_token}";
        $res = json_decode(curl_post($url, jsonEncode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['data']
            ];
        }
    }
    //修改结算账户
    public static function setBankAcct($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/funds/setbankacct?access_token={$access_token}";
        $data = [
            'account_info' => $data
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }
    //获取资金流水
    public static function getFundsflowlist($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/funds/getfundsflowlist?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['flow_ids'],
                'has_more' => $res['has_more'],
                'next_key' => $res['next_key']
            ];
        }
    }
    //获取资金详情
    public static function getFundsflowdetail($aid,$bid,$appid,$flow_id){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $data = [
            'flow_id' => $flow_id
        ];
        $url = "https://api.weixin.qq.com/channels/ec/funds/getfundsflowdetail?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['funds_flow']
            ];
        }
    }

    //获取提现记录列表
    public static function getWithdrawlist($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/funds/getwithdrawlist?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['withdraw_ids'],
            ];
        }
    }
    //获取提现记录详情
    public static function getWithdrawdetail($aid,$bid,$appid,$withdraw_id){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/funds/getwithdrawdetail?access_token={$access_token}";
        $data = [
            'withdraw_id' => $withdraw_id
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res,
            ];
        }
    }
    //提现
    public static function withdraw($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/funds/submitwithdraw?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['withdraw_id'],
            ];
        }
    }

    //获取电子面单快递公司
    public static function getEwabillDelivery($aid,$bid,$appid,$status=0){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/delivery/get?access_token={$access_token}";
        $data = [
            'status' => $status
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['list'],
                'shop_id' => $res['shop_id'],
            ];
        }
    }
    //获取标准电子面单模板
    public static function getEwabillTemplateConfig($aid,$bid,$appid){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/template/config?access_token={$access_token}";
        $data = new \stdClass();
        $headerarray = array('Content-Type: application/json');
        $res = json_decode(curl_post($url,json_encode($data),false,$headerarray),true );
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['config'],
            ];
        }
    }
    //获取电子面单模板
    public static function getEwabillTemplate($aid,$bid,$appid,$delivery_id=0){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/template/get?access_token={$access_token}";
        if($delivery_id){
            $data = [
                'delivery_id' => $delivery_id
            ];
        }else{
            $data = new \stdClass();
        }
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['total_template'],
            ];
        }
    }
    //新增电子面单模板
    public static function addEwabillTemplate($aid,$bid,$appid,$delivery_id,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/template/create?access_token={$access_token}";
        $data = [
            'delivery_id' => $delivery_id,
            'info' => $data
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['template_id'],
            ];
        }
    }
    //修改电子面单模板
    public static function editEwabillTemplate($aid,$bid,$appid,$delivery_id,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/template/update?access_token={$access_token}";
        $data = [
            'delivery_id' => $delivery_id,
            'info' => $data
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['template_id'],
            ];
        }
    }
    //删除电子面单
    public static function delTemplate($aid,$bid,$appid,$delivery_id,$template_id){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/template/delete?access_token={$access_token}";
        $data = [
            'delivery_id' => $delivery_id,
            'template_id' => $template_id
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
            ];
        }
    }
    //电子面单网点账号信息
    public static function ewaybillAccount($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/account/get?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['account_list']
            ];
        }
    }
    //电子面单预取号
    public static function ewaybillPrecreateOrder($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/order/precreate?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['ewaybill_order_id']
            ];
        }
    }
    //电子面单取号
    public static function ewaybillCreateOrder($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/order/create?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['delivery_error_msg']?:($res['errmsg']?:self::geterror($res))
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res
            ];
        }
    }
    //电子面单取消下单
    public static function ewaybillCancelOrder($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/order/cancel?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res
            ];
        }
    }
    public static function ewaybillPrintInfo($aid,$bid,$appid,$data){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/logistics/ewaybill/biz/print/get?access_token={$access_token}";
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['print_info']
            ];
        }
    }

    //获取账户余额
    public static function getbalance($aid,$bid,$appid){
        $access_token = self::getAccessToken($aid,$bid, $appid);
        $url = "https://api.weixin.qq.com/channels/ec/funds/getbalance?access_token={$access_token}";
        $data = new \stdClass();
        $res = json_decode(curl_post($url, json_encode($data)), true);
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res
            ];
        }
    }


    public static function getAccessToken($aid,$bid=0,$appid,$params=['appsecret'=>'','iscache' => true, 'ischeck' => false, 'ischeck_returntype'=>0])
    {

        $appsecret = '';
        if($params && isset($params['appsecret'])){
            $appsecret = $params['appsecret'];
        }
        $iscache = true;
        if($params && isset($params['iscache'])){
            $iscache = $params['iscache'];
        }
        $ischeck = false;
        if($params && isset($params['ischeck'])){
            $ischeck = $params['ischeck'];
        }
        $ischeck_returntype = 0;//检查返回类型 0 默认header方式 1：返回数组
        if($params && isset($params['ischeck_returntype'])){
            $ischeck_returntype = $params['ischeck_returntype'];
        }

        $tokendata = Db::name('access_token')->where('appid', $appid)->find();
        if (!$ischeck) {
            $appinfo = self::appInfo($aid, $bid, $appid);
            if (!$appinfo) {
                if(!$ischeck_returntype){
                    header('location:'.PRE_URL.'/?s=WxChannels/detail');exit;
                }else{
                    echojson(array('status' => 0, 'msg' => '应用不存在'));
                }
            }
            $appsecret = $appinfo['appsecret'];
            if ($iscache && $tokendata && $tokendata['access_token'] && $tokendata['expires_time'] > time()) {
                return $tokendata['access_token'];
            }
        }
        if (!$appsecret) echojson(array('status' => 0, 'msg' => 'appsecret为空'));;
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
        $res = request_get($url);
        $res = json_decode($res);

        $access_token = $res->access_token;
        if ($access_token) {
            if ($tokendata) {
                Db::name('access_token')->where('appid', $appid)->update(['access_token' => $access_token, 'expires_time' => time() + $res->expires_in - 100]);
            } else {
                Db::name('access_token')->insert(['appid' => $appid, 'access_token' => $access_token, 'expires_time' => time() + $res->expires_in - 100]);
            }
            return $access_token;
        } else {
            echojson(array('status' => 0, 'msg' => self::geterror($res)));
        }
    }

    public static function geterror($rs)
    {
        if (is_object($rs)) $rs = (array)$rs;
        $err = [
            '-1' => '系统繁忙，请稍候再试',
            '40001' => 'AppSecret错误或access_token失效',
            '40003' => '请检查 openid 的正确性',
            '40013' => '请检查 appid 的正确性',
            '40066' => '接口调用地址错误',
            '41001' => '缺少 access_token 参数',
            '41002' => '参数缺少 appid',
            '41018' => '请求数据缺少component_ appid',
            '42001' => 'access_token失效，请重新获取',
            '43001' => '请使用GET请求',
            '43002' => '请求方式请使用POST',
            '43003' => '请使用HTTPS方式清求，不要使用HTTP方式',
            '44002' => 'POST 的数据包为空',
            '45002' => '请求数据包过大，请对数据进行压缩',
            '45009' => '接口调用次数已达上限',
            '45011' => '接口调用频率过快',
            '45035' => '请配置白名单',
            '47001' => '数据解析错误',
            '48001' => '接口无权限',
            '48004' => '接口已禁用',
            '50001' => '暂无api授权',
            '50002' => '用户被封禁，请检查封禁原因',
            '61004' => '请配置IP白名单',
            '61007' => '请检查服务商授权集',
            '10080000' => '账号发起注销，进入注销公示期',
            '10080001' => '账号已注销',
            '10080002' => '小店的视频号带货身份为达人号，不允许使用该功能，如需使用，请将带货身份修改为商家',
            '10020059' => '图片为空',
            '10020060' => '图片大小超出限制（2MB）',
        ];
        if (is_array($rs)) {
            return $err[$rs['errcode']] ? $err[$rs['errcode']] : $rs['errcode'] . ': ' . $rs['errmsg'];
        } else {
            return $err[$rs] ? $err[$rs] : $rs;
        }
    }

    public static function defaultApp($aid,$bid)
    {
        $appid = Db::name("admin_setapp_channels")->where("aid", $aid)->where('bid', $bid)->value('appid');
        if(!$appid){
            header('location:'.PRE_URL.'/?s=WxChannels/detail');exit;
        }
        return $appid;
    }


    public static function appInfo($aid, $bid, $appid)
    {
        return Db::name("admin_setapp_channels")->where('aid', $aid)->where('bid', $bid)->where('appid', $appid)->find();
    }

    //回调事件处理
    public static function callback()
    {
        $appid = input('param.appid');
        $appinfo = Db::name('admin_setapp_channels')->where('appid', $appid)->find();
        $componentinfo = ['token' => $appinfo['token'], 'key' => $appinfo['key'], 'appid' => $appid];
        $aid = $appinfo['aid'];
        $bid = $appinfo['bid']??0;
        define('aid', $aid);
        define('bid', $bid);
        $pc = new \app\common\WxBizMsgCrypt($componentinfo['token'], $componentinfo['key'], $componentinfo['appid']);
        $postStr = file_get_contents('php://input');
        writeLog('input数据=>'.json_encode(input()),'wx_channels');
        writeLog('$postStr数据=>'.json_encode($postStr),'wx_channels');
        if ($postStr) {
            $msg_sign = $_GET['msg_signature'];
            $timeStamp = $_GET['timestamp'];
            $nonce = $_GET['nonce'];
            $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $postStr, $msg);
            if ($errCode != 0) {
                Log::write($postStr);
                Log::write('视频号小店解析推送消息失败: ' . $errCode);
                die;
            }
            $postObj = json_decode($msg,true);
            //$postObj = json_decode($postStr,true);
            writeLog('$postObj数据=>'.json_encode($postObj),'wx_channels');
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_close_store') {
                //小店注销事件
                Db::name('admin_setapp_channels')->where('appid',$postStr['bind_appid'])->update(['status'=>2]);

            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'product_spu_audit') {
                //商品审核回调
                $event_info = $postObj['ProductSpuAudit'];
                $product_info = Db::name('channels_product')->where('product_id',$event_info['product_id'])->find();
                if($product_info){
                    Db::name('channels_product')->where('product_id',$event_info['product_id'])->update(['audit_status'=>$event_info['status'],'reason'=>$event_info['reason']]);
                }else{
                    self::productToShop(aid,bid,$appid,$event_info['product_id']);
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'product_spu_listing') {
                //商品上下架
                $event_info = $postObj['ProductSpuListing'];
                $product_info = Db::name('channels_product')->where('product_id',$event_info['product_id'])->find();
                if($product_info) {
                    Db::name('channels_product')->where('product_id', $event_info['product_id'])->update(['status' => $event_info['status'], 'reason' => $event_info['reason']]);
                }else{
                    self::productToShop(aid,bid,$appid,$event_info['product_id']);
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'product_spu_update') {
                //商品更新
                $event_info = $postObj['ProductSpuUpdate'];
                self::productToShop(aid,bid,$appid,$event_info['product_id']);

            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'product_category_audit') {
                //类目审核结果
                $event_info = $postObj['ProductCategoryAudit'];
                $exit = Db::name('channels_category_apply')->where('aid',aid)->where('bid',bid)->where('appid',$appid)->where('audit_id',$event_info['audit_id'])->find();
                if($exit){
                    $data = [
                        'status' => $event_info['status'],
                        'reason' => $event_info['reason']
                    ];
                    Db::name('channels_category_apply')->where('id',$exit['id'])->update($data);
                    if($event_info['status']==3){
                        Db::name('channels_category')->where('appid',$appid)->where('cat_id',$exit['cat_id'])->update(['need_to_apply'=>0]);
                    }
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_order_new') {
                //订单下单
                $event_info = $postObj['order_info'];
                $order_id = $event_info['order_id'];
                self::updateOrder(aid,bid,$appid,$order_id,$postObj['Event']);
                //创建订单分销佣金数据(新下单回调解析不到分享员数据，改到支付后再创建分销佣金)
                //self::order_commission(aid,bid,$appid,$order_id,0);
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_order_cancel') {
                //订单取消
                $event_info = $postObj['order_info'];
                $order_id = $event_info['order_id'];
                $exit = Db::name('channels_order')->where('order_id',$order_id)->find();
                if($exit){
                    if($event_info['cancel_type']==3){
                        Db::name('channels_order')->where('order_id',$order_id)->update(['status'=>200]);
                    }else{
                        Db::name('channels_order')->where('order_id',$order_id)->update(['status'=>250]);
                    }
                }else{
                    self::updateOrder(aid,bid,$appid,$order_id,$postObj['Event']);
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_order_pay') {
                //订单支付成功
                $event_info = $postObj['order_info'];
                $order_id = $event_info['order_id'];
                self::updateOrder(aid,bid,$appid,$order_id,$postObj['Event']);
                //创建订单分销佣金数据
                self::order_commission(aid,bid,$appid,$order_id,0);
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_order_deliver') {
                //订单发货
                $event_info = $postObj['order_info'];
                $order_id = $event_info['order_id'];
                $finish_delivery = $event_info['finish_delivery'];
                if($finish_delivery==0){
                    $status = 21;
                }else{
                    $status = 30;
                }
                $exit = Db::name('channels_order')->where('order_id',$order_id)->find();
                if($exit) {
                    Db::name('channels_order')->where('order_id', $order_id)->update(['status' => $status]);
                }else{
                    self::updateOrder(aid,bid,$appid,$order_id,$postObj['Event']);
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_order_confirm') {
                //订单确认收获
                $event_info = $postObj['order_info'];
                $order_id = $event_info['order_id'];
                $exit = Db::name('channels_order')->where('order_id',$order_id)->find();
                if($exit) {
                    Db::name('channels_order')->where('order_id', $order_id)->update(['status' => 100]);
                }else{
                    self::updateOrder(aid,bid,$appid,$order_id,$postObj['Event']);
                }
                //收货后发放佣金，测试用，正式屏蔽掉  todo
                if(strpos(PRE_URL,'diandashop')!==false){
                    self::order_commission(aid,bid,$appid,$order_id,1);
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_order_settle') {
                //订单结算成功
                $event_info = $postObj['order_info'];
                $order_id = $event_info['order_id'];
                $settle_time = $event_info['settle_time'];
                $exit = Db::name('channels_order')->where('order_id',$order_id)->find();
                if($exit){
                    Db::name('channels_order')->where('order_id',$order_id)->update(['settle_time'=>$settle_time]);
                }else{
                    self::updateOrder(aid,bid,$appid,$order_id,$postObj['Event']);
                }
                //订单结算成功发放平台分销佣金
                self::order_commission(aid,bid,$appid,$order_id,1);
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_order_ext_info_update') {
                //订单其他信息更新
                $event_info = json_decode($postObj->order_info,true);
                $order_id = $event_info['order_id'];
                $type = $event_info['type'];
                //1：联盟佣金信息
                //2：商家主动地址修改或通过用户修改地址申请
                //3：商家备注修改
                //4：用户发起申请修改收货地址，特殊条件下需要商家审批
                //5：订单虚拟号码信息更新
                //6：分享员信息更新
                //7：用户催发货
                self::updateOrder(aid,bid,$appid,$order_id,$postObj['Event']);

            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_acct_notify') {
                //结算账户变更
                $event_info = $postObj['account_info'];
                if($event_info['event']==1){
                    $res = \app\common\WxChannels::getBankAcct(aid,bid,$appid);
                    $bankacct_info = Db::name('channels_bankacct')->where('aid',aid)->where('bid',bid)->where('appid',$appid)->find();
                    //获取结算账户
                    if($res['status']){
                        $account_info = $res['data'];
                        $info = [];
                        $info['aid'] = aid;
                        $info['bid'] = bid;
                        $info['appid'] = $appid;
                        $info['bank_account_type'] = $account_info['bank_account_type'];
                        $info['account_bank'] = $account_info['account_bank'];
                        $info['bank_address_code'] = $account_info['bank_address_code'];
                        $info['bank_branch_id'] = $account_info['bank_branch_id'];
                        $info['bank_name'] = $account_info['bank_name'];
                        $info['account_number'] = $account_info['account_number'];
                        $info['account_name'] = $account_info['account_name'];
                        if(!$bankacct_info){
                            Db::name('channels_bankacct')->insertGetId($info);
                        }else{
                            Db::name('channels_bankacct')->where('id',$bankacct_info['id'])->update($info);
                        }
                    }

                }

            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_withdraw_notify') {
                //提现回调
                $event_info = $postObj['withdraw_info'];
                if($event_info['event']==1){
                    $withdraw_id = $event_info['withdraw_id'];
                    //提现详情
                    $res = \app\common\WxChannels::getWithdrawdetail(aid,bid,$appid, $withdraw_id);
                    if($res['status'] == 0 ){
                        return json($res);
                    }
                    $funds_flow = $res['data'];
                    writeLog('提现详情数据=>'.json_encode($funds_flow),'wx_channels');
                    $data = [
                        "aid" => aid,
                        "bid" => bid,
                        "appid" => $appid,
                        "withdraw_id" => $withdraw_id,
                        "amount" => $funds_flow['amount']/100,
                        "create_time" => $funds_flow['create_time'],
                        "update_time" => $funds_flow['update_time'],
                        "reason" => $funds_flow['reason'],
                        "remark" => $funds_flow['remark'],
                        "bank_memo" => $funds_flow['bank_memo'],
                        "bank_name" => $funds_flow['bank_name'],
                        "bank_num" => $funds_flow['bank_num'],
                        "status" => $funds_flow['status'],
                    ];
                    $exit = Db::name('channels_withdrawlog')->where('withdraw_id',$withdraw_id)->find();
                    if($exit){
                        Db::name('channels_withdrawlog')->where('withdraw_id',$withdraw_id)->update($data);
                    }else{
                        Db::name('channels_withdrawlog')->insert($data);
                    }
                }

            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'qrcode_status') {
                //提现二维码回调


            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_coupon_receive') {
                //领取优惠券
                $event_info = $postObj['receive_info'];
                $openid = $postObj->FromUserName;
                self::updateUserCoupon(aid,bid,$appid,$event_info['user_coupon_id'],$openid);
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_coupon_create') {
                //创建优惠券
                $event_info = $postObj['coupon_info'];
                self::updateCoupon(aid,bid,$appid,$event_info['coupon_id']);
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_coupon_delete') {
                //删除优惠券
                $event_info = $postObj['coupon_info'];
                $exit = Db::name('channels_coupon')->where('coupon_id',$event_info['coupon_id'])->find();
                if($exit){
                    Db::name('channels_coupon')->where('coupon_id',$event_info['coupon_id'])->delete();
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_coupon_expire') {
                //优惠券过期
                $event_info = $postObj['coupon_info'];
                $exit = Db::name('channels_coupon')->where('coupon_id',$event_info['coupon_id'])->find();
                if($exit) {
                    Db::name('channels_coupon')->where('coupon_id', $event_info['coupon_id'])->update(['status' => 101]);
                }else{
                    self::updateCoupon(aid,bid,$appid,$event_info['coupon_id']);
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_coupon_info_change') {
                //优惠券更新
                $event_info = $postObj['coupon_info'];
                self::updateCoupon(aid,bid,$appid,$event_info['coupon_id']);
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_coupon_invalid') {
                //优惠券作废
                $event_info = $postObj['coupon_info'];
                $exit = Db::name('channels_coupon')->where('coupon_id',$event_info['coupon_id'])->find();
                if($exit) {
                    Db::name('channels_coupon')->where('coupon_id', $event_info['coupon_id'])->update(['status' => 4]);
                }else{
                    self::updateCoupon(aid,bid,$appid,$event_info['coupon_id']);
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_user_coupon_expire') {
                //优惠券过期
                $event_info = $postObj['user_coupon_info'];
                $exit = Db::name('channels_user_coupon')->where('user_coupon_id',$event_info['user_coupon_id'])->find();
                if($exit) {
                    Db::name('channels_user_coupon')->where('user_coupon_id', $event_info['user_coupon_id'])->update(['status' => 102]);
                }else{
                    $openid = $postObj['FromUserName'];
                    self::updateUserCoupon(aid,bid,$appid,$event_info['coupon_id'],$openid);
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_user_coupon_unuse') {
                //优惠券返还
                $event_info = $postObj['use_info'];
                $exit = Db::name('channels_user_coupon')->where('user_coupon_id',$event_info['user_coupon_id'])->find();
                if($exit) {
                    Db::name('channels_user_coupon')->where('user_coupon_id', $event_info['user_coupon_id'])->update(['status' => 100]);
                }else{
                    $openid = $postObj['FromUserName'];
                    self::updateUserCoupon(aid,bid,$appid,$event_info['coupon_id'],$openid);
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_user_coupon_use') {
                //优惠券核销
                $event_info = $postObj['use_info'];
                $exit = Db::name('channels_user_coupon')->where('user_coupon_id',$event_info['user_coupon_id'])->find();
                if($exit) {
                    Db::name('channels_user_coupon')->where('user_coupon_id', $event_info['user_coupon_id'])->update(['status' => 102]);
                }else{
                    $openid = $postObj['FromUserName'];
                    self::updateUserCoupon(aid,bid,$appid,$event_info['coupon_id'],$openid);
                }
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_brand') {
                //品牌资质审核
                $brand_event = $postObj['BrandEvent'];
                $brand_id = $brand_event['brand_id'];
                $exit = Db::name('channels_brand')->where('aid',aid)->where('bid',bid)->where('appid',$appid)->where('brand_id',$brand_id)->find();
                if($exit){
                    $data = [
                        'status' => $brand_event['status'],
                        'reject_reason' => $brand_event['reason']
                    ];
                    Db::name('channels_brand')->where('id',$exit['id'])->update($data);
                }else{
                    $brand_info = Db::name('channels_brand_basic')->where('brand_id',$brand_id)->find();
                    $data = [
                        'aid' => aid,
                        'bid' => bid,
                        'appid' => $appid,
                        'ch_name' => $brand_info['ch_name'],
                        'en_name' => $brand_info['en_name'],
                        'brand_id' => $brand_event['brand_id'],
                        'status' => $brand_event['status'],
                        'reject_reason' => $brand_event['reason'],
                        'createtime' => time()
                    ];
                    Db::name('channels_brand')->insert($data);
                }

            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_aftersale_update') {
                //售后单更新
                $event_info = $postObj['finder_shop_aftersale_status_update'];
                $after_sale_order_id = $event_info['after_sale_order_id'];
                self::updateAfterSale(aid,bid, $appid, $after_sale_order_id);
            }
            if ($postObj['MsgType'] == 'event' && $postObj['Event'] == 'channels_ec_sharer_change') {
                //绑定分享员
                $bind_status = $postObj['bind_status'];
                $exit = Db::name('channels_sharer')->where('aid',aid)->where('bid',bid)->where('appid',$appid)->where('openid',$postObj['FromUserName'])->find();
                if($bind_status==1){
                    $res_share = self::getSharer(aid,bid,$appid,$postObj['FromUserName']);
                    writeLog('分享员信息=》'.json_encode($res_share),'wx_channels');
                    $share_info = $res_share['data'];
                    //绑定
                    if(!$exit){
                        $mid = 0;
                        if(!empty($share_info['unionid'])){
                            $member = Db::name('member')->where('unionid',$share_info['unionid'])->where('aid',aid)->find();
                            if(!$member){
                                //注册会员 创建的此会员可能为空会员，在wx_channels_sharer_apply定制里会有删除操作，请谨慎增加此会员事件
                                $data = [];
                                $data['aid'] = aid;
                                $data['unionid'] = $share_info['unionid'];
                                $data['sex'] = 3;
                                $data['createtime'] = time();
                                $data['last_visittime'] = time();
                                $data['nickname'] = $share_info['nickname']??'';
                                $data['channels_openid'] = $share_info['openid']??'';
                                $data['platform'] = 'wx_channels';
                                $mid = \app\model\Member::add(aid,$data);
                            }else{
                                $mid = $member['id'];
                            }
                        }
                        $data = [];
                        $data['aid'] = aid;
                        $data['bid'] = bid;
                        $data['appid'] = $appid;
                        $data['openid'] = $postObj['FromUserName'];
                        $data['unionid'] = $share_info['unionid']??'';
                        $data['nickname'] = $share_info['nickname']??'';
                        $data['bind_time'] = $postObj['CreateTime'];
                        $data['sharer_type'] = $postObj['sharer_type'];
                        $data['mid'] = $mid;
                        $sharerid = Db::name('channels_sharer')->insertGetId($data);
                        if(bid && bid>0 && $sharerid && $data['mid']){
                            //查询此账号是否存在
                            $sharer_commission = Db::name('channels_sharer_commission')->where('sharerid',$sharerid)->where('mid',$data['mid'])->where('bid',bid)->where('aid',aid)->field('id')->find();
                            $data2 = [];
                            $data2['appid']   = $data['appid'];
                            $data2['openid']  = $data['openid'];
                            $data2['unionid'] = $data['unionid'];
                            if(!$sharer_commission){
                                //增加多商户佣金账号表
                                $data2['aid']     = aid;
                                $data2['bid']     = bid;
                                $data2['mid']     = $data['mid'];
                                $data2['sharerid']= $sharerid;
                                $data2['commission'] = 0;
                                $data2['createtime'] = time();
                                $sharerid = Db::name('channels_sharer_commission')->insertGetId($data2);
                            }else{
                                $data2['updatetime'] = time();
                                Db::name('channels_sharer_commission')->where('id',$sharer_commission['id'])->update($data2);
                            }
                        }
                    }else{
                        if($exit['bid'] && !empty($exit['bid'])){
                            Db::name('channels_sharer')->where('id',$exit['id'])->update(['isbind'=>1]);
                        }
                    }
                }else{
                    if($exit){
                        if($exit['bid'] && !empty($exit['bid'])){
                            //查询此分享员佣金账号、及佣金提现是否有佣金未结算完，未结算完，则只能软解绑
                            $commission = 0;
                            $commission += Db::name('channels_sharer_commission')->where('sharerid',$exit['id'])->sum('commission');
                            $commission += Db::name('channels_sharer_commission_withdrawlog')->where('sharerid',$exit['id'])->where('status','in','0,1')->sum('txmoney');
                            if($commission>0){
                                Db::name('channels_sharer')->where('id',$exit['id'])->update(['isbind'=>0]);
                            }else{
                                Db::name('channels_sharer')->where('id',$exit['id'])->delete();
                            }
                        }else{
                            Db::name('channels_sharer')->where('id',$exit['id'])->delete();
                        }
                    }
                }
            }
            die('success');
        } else {
            $signature = $_GET["signature"];
            $timestamp = $_GET["timestamp"];
            $nonce = $_GET["nonce"];
            $tmpArr = array($componentinfo['token'], $timestamp, $nonce);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr);
            $tmpStr = sha1($tmpStr);
            if ($tmpStr == $signature) {
                echo $_GET["echostr"];
            } else {
                echo '';
            }
            die;
        }


    }

    public static function getridinfo($aid, $bid, $appid,$rid=''){
        $rid = '664455ba-5b3af2fc-274099a1';
        $access_token = self::getAccessToken($aid, $bid, $appid);
        $url = "https://api.weixin.qq.com/cgi-bin/openapi/rid/get?access_token={$access_token}";
        $data = [
            'rid' => $rid,
        ];
        $res = json_decode(curl_post($url, json_encode($data)), true);
        dump($res);exit;
        if ($res['errcode'] != 0) {
            return [
                'status' => 0,
                'msg' => $res['errmsg']?:self::geterror($res)
            ];
        } else {
            return [
                'status' => 1,
                'msg' => self::geterror($res),
                'data' => $res['template_id']
            ];
        }
    }
    //商品同步到商城
    public static function productToShop($aid,$bid,$appid,$product_id){
        $res = \app\common\WxChannels::productDetail($aid,$bid,$appid, $product_id);
        if($res['status'] == 0){
            return $res;
        }
        $product = $res['data']?:[];
        $data = [];
        $data['aid'] = $aid;
        $data['bid'] = $bid;
        $data['appid'] = $appid;
        $data['product_id'] = $product_id;
        if($product){
            $data['name'] = $product['title'];
            $data['sub_title'] = $product['short_title'];
            $data['pic'] = $product['head_imgs'][0];
            $data['pics'] = implode(',',$product['head_imgs']);
            $data['desc_pics'] = implode(',',$product['desc_info']['imgs']);
            $data['desc_text'] = $product['desc_info']['desc'];
            $data['cat1'] = $product['cats'][0]['cat_id'];
            $data['cat2'] = $product['cats'][1]['cat_id'];
            $data['cat3'] = $product['cats'][2]['cat_id'];
            $data['attrs'] = jsonEncode($product['attrs']);
            $data['template_id'] = $product['express_info']['template_id'];
            $data['weight'] = $product['express_info']['weight'];
            $data['seven_day_return'] = $product['extra_service']['seven_day_return'];
            $data['pay_after_use'] = $product['extra_service']['pay_after_use'];
            $data['freight_insurance'] = $product['extra_service']['freight_insurance'];
            $data['status'] = $product['status'];
            $data['edit_status'] = $product['edit_status']?:0;
            $data['product_qua_infos'] = jsonEncode($product['product_qua_infos']);
            $skus_arr = $product['skus'];
            //拼装商城适用的规格数据
            $res = self::sku_to_guige($skus_arr);
            $skus_arr = $res['skus_arr'];
            $stock = 0;
            foreach($skus_arr as $k=>$v){
                if($k==0){
                    $sale_price = $v['sale_price']/100;
                    $data['market_price'] = $sale_price;
                    $data['sell_price'] = $sale_price;
                }
                $stock = bcadd($stock,$v['stock_num']);
            }
            $data['stock'] = $stock;
            $guigedata = json_encode($res['guige_data']);
            $data['guigedata'] = $guigedata;
            $exit = Db::name('channels_product')->where('product_id',$product_id)->find();
            if($exit){
                Db::name('channels_product')->where('product_id',$product_id)->update($data);
                $proid = $exit['id'];
            }else{
                $proid = Db::name('channels_product')->insertGetId($data);
            }

            $newggids = array();
            foreach($skus_arr as $sku){
                $ggdata = array();
                $ggdata['product_id'] = $product_id;
                $ggdata['proid'] = $proid;
                $ggdata['sku_id'] = $sku['sku_id'];
                $ggdata['ks'] = $sku['ks'];
                $ggdata['name'] = $sku['name'];
                $ggdata['pic'] = $sku['thumb_img'];
                $sale_price = $sku['sale_price']/100;
                $ggdata['market_price'] = $sale_price;
                $ggdata['sell_price'] = $sale_price;
                $ggdata['weight'] = $data['weight'];
                $ggdata['procode'] = $sku['sku_code'];
                $ggdata['stock'] = $sku['stock_num']>0 ;
                $ggdata['sku_attrs'] = json_encode($sku['sku_attrs']);

                $guige = Db::name('channels_product_guige')->where('aid',$aid)->where('proid',$proid)->where('bid',$bid)->where('ks',$sku['ks'])->find();
                if($guige){
                    Db::name('channels_product_guige')->where('aid',$aid)->where('id',$guige['id'])->where('bid',$bid)->update($ggdata);
                    $ggid = $guige['id'];
                }else{
                    $ggdata['aid'] = $aid;
                    $ggdata['bid'] = $bid;
                    $ggid = Db::name('channels_product_guige')->insertGetId($ggdata);
                }
                $newggids[] = $ggid;
            }
            Db::name('channels_product_guige')->where('aid',$aid)->where('proid',$proid)->where('bid',$bid)->where('id','not in',$newggids)->delete();
        }
        return ['status'=>1,'msg'=>''];
    }
    //微信sku转换为商城guigedata
    public static function sku_to_guige($skus_arr){
        $guigedata = [];
        $title_arr = [];
        foreach($skus_arr as $sku){
            if(empty($sku['sku_attrs'])){
                $title_arr['规格'] = ['默认规格'];
                continue;
            }
            foreach($sku['sku_attrs'] as $k=>$sku_attr ){
                if(!in_array( $sku_attr['attr_value'],$title_arr[$sku_attr['attr_key']])){
                    $title_arr[$sku_attr['attr_key']][] = $sku_attr['attr_value'];
                }
            }
        }
        $i = 0;
        foreach($title_arr as $key=>$item){
            $arr = [
                'k' => $i,
                'title' => $key,
            ];
            $items = [];
            foreach($item as $i_k=>$i_title){
                $items[] = [
                    'k' => $i_k,
                    'title' => $i_title
                ];
            }
            $arr['items'] = $items;
            $i++;
            $guigedata[] = $arr;
        }
        foreach($skus_arr as $k=>$sku) {
            $name = '';
            $ks = '';
            if(empty($sku['sku_attrs'])){
                $name .= '默认规格,';
                $ks_key = array_search('默认规格', $title_arr['规格']);
                $ks .= $ks_key . ',';
            }else{
                foreach ($sku['sku_attrs'] as $sku_attrs) {
                    $name .= $sku_attrs['attr_value'] . ',';
                    $ks_key = array_search($sku_attrs['attr_value'], $title_arr[$sku_attrs['attr_key']]);
                    $ks .= $ks_key . ',';
                }
            }
            $skus_arr[$k]['name'] = rtrim($name,',');
            $skus_arr[$k]['ks'] = rtrim($ks,',');
        }
        return [
            'skus_arr' => $skus_arr,
            'guige_data' => $guigedata
        ];
    }
    //更新订单，后台主动拉取或下单回调
    public static function updateOrder($aid,$bid,$appid,$order_id,$event=''){
        $res2 = \app\common\WxChannels::orderDetail($aid,$bid,$appid, $order_id);
        if(!$res2['status']){
            return $res2;
        }
        $order = $res2['data']?:[];
        $order_detail = $order['order_detail'];
        $data = [];
        $data['order_id'] = $order_id;
        writeLog('订单详情数据=>'.json_encode($order_detail),'wx_channels');
        if($order){
            $data['aid'] = $aid;
            $data['bid'] = $bid;
            $data['appid'] =$appid;
            $data['openid'] = $order['openid'];
            $data['unionid'] = $order['unionid'];
            $data['status'] = $order['status'];
            $data['create_time'] = $order['create_time'];
            $data['update_time'] = $order['update_time'];
            $data['prepay_id'] = $order_detail['pay_info']['prepay_id'];
            $data['transaction_id'] = $order_detail['pay_info']['transaction_id'];
            $data['prepay_time'] = $order_detail['pay_info']['prepay_time'];
            $data['pay_time'] = $order_detail['pay_info']['pay_time'];
            $data['payment_method'] = $order_detail['pay_info']['payment_method'];
            $data['product_price'] = $order_detail['price_info']['product_price']/100;
            $data['order_price'] = $order_detail['price_info']['order_price']/100;
            $data['freight'] = $order_detail['price_info']['freight']/100;
            $data['discounted_price'] = $order_detail['price_info']['discounted_price']/100;
            $data['is_discounted'] = $order_detail['price_info']['is_discounted'];
            $data['address_info'] = json_encode($order_detail['delivery_info']['address_info']);
            $data['delivery_product_info'] = json_encode($order_detail['delivery_info']['delivery_product_info']);
            $data['ship_done_time'] = json_encode($order_detail['delivery_info']['ship_done_time']);
            $data['deliver_method'] = json_encode($order_detail['delivery_info']['deliver_method']);
            $data['user_coupon_id'] = $order_detail['coupon_info']['user_coupon_id'];
            $data['customer_notes'] = $order_detail['ext_info']['customer_notes'];
            $data['merchant_notes'] = $order_detail['ext_info']['merchant_notes'];
            $data['sharer_openid'] = $order_detail['sharer_info']['sharer_openid'];
            $data['sharer_unionid'] = $order_detail['sharer_info']['sharer_unionid'];
            $data['sharer_type'] = $order_detail['sharer_info']['sharer_type'];
            $data['share_scene'] = $order_detail['sharer_info']['share_scene'];
            $data['commission_fee'] = $order_detail['settle_info']['commission_fee'];
            $data['predict_commission_fee'] = $order_detail['settle_info']['predict_commission_fee'];
            $data['sku_sharer_infos'] = json_encode($order_detail['sku_sharer_infos']);
            $data['aftersale_detail'] = json_encode($order_detail['aftersale_detail']);
            $data['commission_infos'] = json_encode($order_detail['commission_infos']);

            //后加的会员mid和分享员ID
            //会员mid
            if(!empty($data['unionid'])){
                $member =  Db::name('member')->where('unionid',$data['unionid'])->where('aid',$aid)->field('id,nickname,levelid,pid,pid_origin')->find();
            }
            if(!$member && !empty($data['openid'])){
                $member =  Db::name('member')->where('channels_openid',$data['openid'])->where('aid',$aid)->field('id,nickname,levelid,pid,pid_origin')->find();
            }
            if(!$member){
                if(!empty($data['unionid'])){
                    $sharer = Db::name('channels_sharer')->where('appid',$data['appid'])->where('unionid',$data['unionid'])->where('bid',$bid)->where('aid',$data['aid'])->field('id,mid')->find();
                }
                if(!$sharer && !empty($data['openid'])){
                    $sharer = Db::name('channels_sharer')->where('appid',$data['appid'])->where('openid',$data['openid'])->where('bid',$bid)->where('aid',$data['aid'])->field('id,mid')->find();
                }
                if($sharer && $sharer['mid']){
                    $member =  Db::name('member')->where('id',$sharer['mid'])->where('aid',$aid)->field('id,nickname,levelid,pid,pid_origin')->find();
                }
            }

            $sysset = Db::name('admin_set')->where('aid',$aid)->find();
            if($member){
                $data['mid'] = $member['id'];
            }else{
                //订单下单人自动创建
                if($sysset && $sysset['channels_membmer_register']){
                    //注册会员 创建的此会员可能为空会员，在wx_channels_sharer_apply定制里会有删除操作，请谨慎增加此会员事件
                    $memberdata = [];
                    $memberdata['aid'] = $aid;
                    $memberdata['unionid'] = $order['unionid']??'';
                    $memberdata['pid']     = 0;
                    $memberdata['nickname']= $order['openid']??'';
                    $memberdata['sex']     = 3;
                    $memberdata['channels_openid'] = $order['openid']??'';
                    $memberdata['platform']= 'wx_channels';
                    $memberdata['createtime'] = time();
                    $memberdata['last_visittime'] = time();
                    $mid = \app\model\Member::add($aid,$memberdata);
                    $default_levelid = Db::name('member_level')->where('aid',$aid)->where('isdefault',1)->value('id');
                    $member = Db::name('member')->where('id',$mid)->field('id,nickname,levelid,pid,pid_origin')->find();

                    $data['mid'] = $mid;
                }
            }

            //分享员ID
            if(!empty($data['sharer_unionid'])){
                $share_info = Db::name('channels_sharer')->where('appid',$data['appid'])->where('unionid',$data['sharer_unionid'])->where('bid',$bid)->where('aid',$data['aid'])->field('id,mid')->find();
            }
            if(!$share_info && !empty($data['sharer_openid'])){
                $share_info = Db::name('channels_sharer')->where('appid',$data['appid'])->where('openid',$data['sharer_openid'])->where('bid',$bid)->where('aid',$data['aid'])->field('id,mid')->find();
            }
            if($share_info){
                $data['sharerid'] = $share_info['id'];
            }

            if(getcustom('wx_channels_firstbuy_agentrule')){
                //首次消费后绑定推荐关系
                if(
                    $order['status']>=20 && $order['status']<=100 
                    && $share_info && $share_info['mid']
                    && (!$member || ($member && $share_info['mid'] != $member['id'] && $share_info['mid'] != $member['pid'])) 
                ){
                    if($sysset && $sysset['channels_firstbuy_agentrule'] == 1){
                        //查询分享者信息
                        $sharer_member = Db::name('member')->where('id',$share_info['mid'])->where('aid',$aid)->field('id,aid,levelid,pid,pid_origin')->find();
                        if(
                            $sharer_member 
                            && (
                                !$member 
                                || ( 
                                    $member
                                    && $sharer_member['pid'] != $member['id'] 
                                    && (!$member['pid_origin'] || empty($member['pid_origin']) )
                                   )
                               ) 
                        ){
                            $sharer_level = Db::name('member_level')->where('id',$sharer_member['levelid'])->where('aid',$aid)->field('id,can_agent,agent_rule')->find();
                            if($sharer_level && $sharer_level['can_agent']!=0 && $sharer_level['agent_rule']>0){
                                //首次消费后绑定推荐关系
                                if($sharer_level['agent_rule']==3){
                                    if(!$member){
                                        //注册会员 创建的此会员可能为空会员，在wx_channels_sharer_apply定制里会有删除操作，请谨慎增加此会员事件
                                        $memberdata = [];
                                        $memberdata['aid'] = $aid;
                                        $memberdata['unionid'] = $order['unionid']??'';
                                        $memberdata['pid']     = $sharer_member['id'];
                                        $memberdata['nickname']= $order['openid']??'';
                                        $memberdata['sex']     = 3;
                                        $memberdata['channels_openid'] = $order['openid']??'';
                                        $memberdata['platform']= 'wx_channels';
                                        $memberdata['createtime'] = time();
                                        $memberdata['last_visittime'] = time();
                                        $mid = \app\model\Member::add($aid,$memberdata);
                                        $default_levelid = Db::name('member_level')->where('aid',$aid)->where('isdefault',1)->value('id');
                                        $member = ['id' => $mid,'levelid' => $default_levelid,'pid' => $memberdata['pid']];

                                        $data['mid'] = $mid;
                                    }else{
                                        $haspayorder = 0+Db::name('payorder')->where('mid',$member['id'])->where('money','>',0)->where('status',1)->count();
                                        $haspayorder += Db::name('channels_order')->where('mid',$member['id'])->where('status','>=',20)->where('status','<=',100)->count();
                                        if(!$haspayorder){
                                            \app\model\Member::edit($aid,['id'=>$member['id'],'pid'=>$sharer_member['id']]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $exit = Db::name('channels_order')->where('order_id',$order_id)->find();
            if($exit){
                $resU = Db::name('channels_order')->where('order_id',$order_id)->update($data);
                $oid = $exit['id'];
            }else{
                //除了订单下单或者订单支付，其他事件不立马创建
                if($event == 'channels_ec_order_new' ||  $event == 'channels_ec_order_pay'){
                    $oid = Db::name('channels_order')->insertGetId($data);
                }else{
                    writeLog('事件'.$event.',订单'.$order_id.'不存在','wx_channels');
                    sleep(1);
                    $exit = Db::name('channels_order')->where('order_id',$order_id)->find();
                    if(!$exit){
                        writeLog('事件'.$event.',订单'.$order_id.'不存在，创建订单','wx_channels');
                        $oid = Db::name('channels_order')->insertGetId($data);
                    }
                }
            }

            $product_infos = $order_detail['product_infos'];
            $delivery_product_info = $order_detail['delivery_info']['delivery_product_info'];
            foreach($product_infos as $product){
                $data_g = [];
                $data_g['aid'] = $aid;
                $data_g['bid'] = $bid;
                $data_g['oid'] = $oid;
                $data_g['order_id'] = $order_id;
                $data_g['product_id'] = $product['product_id'];
                $data_g['sku_id'] = $product['sku_id'];
                $data_g['sku_cnt'] = $product['sku_cnt'];
                $data_g['on_aftersale_sku_cnt'] = $product['on_aftersale_sku_cnt'];
                $data_g['finish_aftersale_sku_cnt'] = $product['finish_aftersale_sku_cnt'];
                $data_g['title'] = $product['title'];
                $data_g['thumb_img'] = $product['thumb_img'];
                $data_g['sale_price'] = $product['sale_price']/100;
                $data_g['market_price'] = $product['market_price']/100;
                $real_price = $product['real_price']?:$product['sale_price'];
                $data_g['real_price'] = $real_price/100;
                $data_g['is_change_price'] = $product['is_change_price']?1:0;
                foreach($delivery_product_info as $delivery_info){
                    foreach($delivery_info['product_infos'] as $pv){
                        if($pv['product_id']==$product['product_id'] && $pv['sku_id']==$product['sku_id']){
                            $data_g['product_cnt'] = $pv['product_cnt'];
                        }
                    }
                }
                $map = [];
                $map[] = ['order_id','=',$order_id];
                $map[] = ['product_id','=',$product['product_id']];
                $map[] = ['sku_id','=',$product['sku_id']];
                $exit = Db::name('channels_order_goods')->where($map)->find();
                if($exit){
                    Db::name('channels_order_goods')->where('id',$exit['id'])->update($data_g);
                }else{
                    Db::name('channels_order_goods')->insert($data_g);
                }
            }

            //升级
            if($member && $order['status']>=20 && $order['status']<=100){
                \app\common\Member::uplv($aid,$member['id']);
            }
        }
        //创建订单分销佣金数据
        if(!in_array($order['status'],[10,200,250])){
            self::order_commission($aid,$bid,$appid,$order_id,0);
        }
        return ['status'=>1,'msg'=>'同步订单成功'];
    }

    //更新售后订单，后台主动拉取或售后单回调
    public static function updateAfterSale($aid,$bid,$appid,$after_sale_order_id){
        $res2 = \app\common\WxChannels::afterSaleDetail($aid,$bid,$appid, $after_sale_order_id);
        if(!$res2['status']){
            return $res2;
        }
        $order_detail = $res2['data']?:[];
        $data = [];
        $data['after_sale_order_id'] = $after_sale_order_id;
        if($order_detail){
            $data['aid'] = $aid;
            $data['bid'] = $bid;
            $data['appid'] =$appid;
            $data['openid'] = $order_detail['openid'];
            $data['status'] = $order_detail['status'];
            $data['create_time'] = $order_detail['create_time'];
            $data['update_time'] = $order_detail['update_time'];
            $data['unionid'] = $order_detail['unionid'];
            $data['order_id'] = $order_detail['order_id'];
            $data['product_id'] = $order_detail['product_info']['product_id'];
            $data['sku_id'] = $order_detail['product_info']['sku_id'];
            $data['count'] = $order_detail['product_info']['count'];

            $data['desc'] = $order_detail['details']['desc'];
            $data['receive_product'] = $order_detail['details']['receive_product'];
            $data['cancel_time'] = $order_detail['details']['cancel_time'];
            $data['media_id_list'] = implode(',',$order_detail['details']['media_id_list']);
            $data['tel_number'] = $order_detail['details']['tel_number'];
            $data['amount'] = $order_detail['refund_info']['amount']/100;
            $data['refund_reason'] = $order_detail['refund_info']['refund_reason'];

            $data['waybill_id'] = $order_detail['return_info']['waybill_id'];
            $data['delivery_id'] = $order_detail['return_info']['delivery_id'];
            $data['delivery_name'] = $order_detail['return_info']['delivery_name'];

            $data['reject_reason'] = $order_detail['merchant_upload_info']['reject_reason'];
            $data['refund_certificates'] = $order_detail['merchant_upload_info']['refund_certificates'];
            $data['reason'] = $order_detail['reason'];
            $data['reason_text'] = $order_detail['reason_text'];
            $data['type'] = $order_detail['type'];
            $data['refund_resp'] = json_encode($order_detail['refund_resp']);

            $exit = Db::name('channels_after_sales')->where('after_sale_order_id',$after_sale_order_id)->find();
            if($exit){
                $oid = Db::name('channels_after_sales')->where('after_sale_order_id',$after_sale_order_id)->update($data);
            }else{
                $oid = Db::name('channels_after_sales')->insertGetId($data);
            }
        }
        return ['status'=>1,'msg'=>'同步售后单成功'];
    }

    //更新优惠券，后台主动拉取或优惠券回调
    public static function updateCoupon($aid,$bid,$appid,$coupon_id){
        $res2 = \app\common\WxChannels::couponDetail($aid,$bid,$appid, $coupon_id);
        if(!$res2['status']){
            return $res2;
        }
        $coupon = $res2['data']?:[];
        $coupon_detail = $coupon['coupon_info'];
        $data = [];
        $data['coupon_id'] = $coupon_id;
        if($coupon){
            $data['aid'] = $aid;
            $data['bid'] = $bid;
            $data['appid'] =$appid;
            $data['type'] = $coupon['type'];
            $data['status'] = $coupon['status'];
            $data['create_time'] = $coupon['create_time'];
            $data['update_time'] = $coupon['update_time'];
            $data['name'] = $coupon_detail['name'];
            $data['promote_type'] = $coupon_detail['promote_info']['promote_type'];
            $data['discount_num'] = $coupon_detail['discount_info']['discount_num'];
            $data['discount_fee'] = $coupon_detail['discount_info']['discount_fee']/100;
            $data['end_time'] = $coupon_detail['receive_info']['end_time'];//优惠券领用结束时间
            $data['start_time'] = $coupon_detail['receive_info']['start_time'];//优惠券领用开始时间
            $data['limit_num_one_person'] = $coupon_detail['receive_info']['limit_num_one_person'];//单人限领张数
            $data['total_num'] = $coupon_detail['receive_info']['total_num'];//优惠券领用总数
            $data['valid_type'] = $coupon_detail['valid_info']['valid_type'];
            $data['valid_day_num'] = $coupon_detail['valid_info']['valid_day_num'];
            $data['valid_start_time'] = $coupon_detail['valid_info']['start_time'];//优惠券有效期开始时间，valid_type=1时才有意义
            $data['valid_end_time'] = $coupon_detail['valid_info']['end_time'];//优惠券有效期结束时间，valid_type=1时才有意义
            $data['issued_num'] = $coupon['stock_info']['issued_num'];//优惠券剩余量
            $data['receive_num'] = $coupon['stock_info']['receive_num'];//优惠券领用但未使用量
            $data['used_num'] = $coupon['stock_info']['used_num'];//优惠券已用量
            $data['product_cnt'] = $coupon_detail['discount_info']['discount_condition']['product_cnt'];
            $data['product_price'] = $coupon_detail['discount_info']['discount_condition']['product_price']/100;
            $data['product_ids'] = implode(',',$coupon_detail['discount_info']['discount_condition']['product_ids']);
            $data['jump_product_id'] = $coupon_detail['ext_info']['jump_product_id'];
            $data['notes'] = $coupon_detail['ext_info']['notes'];
            $data['valid_time'] = $coupon_detail['ext_info']['valid_time'];//优惠券有效时间
            $data['invalid_time'] = $coupon_detail['ext_info']['invalid_time'];//优惠券失效时间
            $exit = Db::name('channels_coupon')->where('coupon_id',$coupon_id)->find();
            if($exit){
                $oid = Db::name('channels_coupon')->where('coupon_id',$coupon_id)->update($data);
            }else{
                $oid = Db::name('channels_coupon')->insertGetId($data);
            }
        }
        return ['status'=>1,'msg'=>'同步优惠券成功'];
    }

    //更新用户优惠券，后台主动拉取或优惠券回调
    public static function updateUserCoupon($aid,$bid,$appid,$user_coupon_id,$openid){
        $res2 = \app\common\WxChannels::userCouponDetail($aid,$bid,$appid, $user_coupon_id,$openid);
        if(!$res2['status']){
            return $res2;
        }
        $coupon = $res2['data']?:[];
        if($coupon){
            $data = [];
            $data['aid'] = $aid;
            $data['bid'] = $bid;
            $data['appid'] =$appid;
            $data['openid'] = $openid;
            $data['user_coupon_id'] = $coupon['user_coupon_id'];
            $data['coupon_id'] = $coupon['coupon_id'];
            $data['status'] = $coupon['status'];
            $data['create_time'] = $coupon['create_time'];
            $data['update_time'] = $coupon['update_time'];
            $data['start_time'] = $coupon['start_time'];
            $data['end_time'] = $coupon['end_time'];
            $data['use_time'] = $coupon['ext_info']['end_time'];
            $data['order_id'] = $coupon['order_id'];
            $data['discount_fee'] = $coupon['discount_fee'];

            $exit = Db::name('channels_user_coupon')->where('user_coupon_id',$coupon['user_coupon_id'])->find();
            if($exit){
                $oid = Db::name('channels_user_coupon')->where('user_coupon_id',$coupon['user_coupon_id'])->update($data);
            }else{
                $oid = Db::name('channels_user_coupon')->insertGetId($data);
            }
        }
        return ['status'=>1,'msg'=>'同步优惠券成功'];
    }

    //结算订单佣金
    public static function order_commission($aid,$bid=0,$appid,$order_id,$is_send=0){
        $sysset = Db::name('admin_set')->where('aid',$aid)->find();
        if($sysset['channels_order_fenxiao']==-1){
            //系统设置不参与分销
            return true;
        }

        //获取订单详情
        $res = \app\common\WxChannels::orderDetail($aid,$bid,$appid, $order_id);
        if(!$res['status']){
            return true;
        }
        $order_detail = $res['data']?$res['data']['order_detail']:[];

        $order = Db::name('channels_order')->where('order_id',$order_id)->find();
        //查询未计算分销的订单数据
        $order_goods = Db::name('channels_order_goods')->where('order_id',$order_id)->where('cacl_commission',0)->select()->toArray();
        if(!$order_goods && $is_send==0){
            return true;
        }

        //达人订单是否参与分销，若不参与分销，则停止进程
        if(!$order['bid']){
            $channels_finder_fenxiao = $sysset['channels_finder_fenxiao']??1;
        }else{
            $channels_finder_fenxiao = Db::name('business')->where('id',$order['bid'])->value('channels_finder_fenxiao');
        }
        if(!$channels_finder_fenxiao && $order_detail){
            //订单成交来源信息
            $source_infos = $order_detail['source_infos']??[];
            if($source_infos && !empty($source_infos)){
                $stop = false;//是否停止进程
                foreach($source_infos as $source_info){
                    //sale_channel 账号关联类型， 0：关联账号，1：合作账号，2：授权号，100：达人带货，101：带货机构推广
                    if($source_info['sale_channel'] && $source_info['sale_channel'] == 100){
                        $stop = true;
                        break;
                    } 
                    //account_type 带货账号类型，1：视频号，2：公众号，3：小程序，4：企业微信，5：带货达人，1000：带货机构
                    if($source_info['account_type'] && $source_info['account_type'] == 5){
                        $stop = true;
                        break;
                    } 
                }
                if($stop) return true;
            }
        }

        //查询下单人信息
        if($order['mid']){
            $member =  Db::name('member')->where('id',$order['mid'])->where('aid',$order['aid'])->find();
        }
        if(!$member && !empty($order['unionid'])){
            $member =  Db::name('member')->where('aid',$order['aid'])->where('unionid',$order['unionid'])->find();
        }
        if(!$member && !empty($order['openid'])){
            $member =  Db::name('member')->where('aid',$order['aid'])->where('channels_openid',$order['openid'])->find();
        }
        if(!$member){
            if(!empty($order['unionid'])){
                $sharer = Db::name('channels_sharer')->where('appid',$order['appid'])->where('unionid',$order['unionid'])->where('bid',$order['bid'])->where('isbind',1)->where('aid',$order['aid'])->find();
            }
            if(!$sharer &&!empty($order['openid'])){
                $sharer = Db::name('channels_sharer')->where('appid',$order['appid'])->where('openid',$order['openid'])->where('bid',$order['bid'])->where('isbind',1)->where('aid',$order['aid'])->find();
            }
            if($sharer && $sharer['mid']){
                $member = Db::name('member')->where('id',$sharer['mid'])->where('aid',$order['aid'])->find();
            }
        }
        if($member){
            //后加更新mid和sharerid参数
            if($order['mid'] != $member['id']){
                Db::name('channels_order')->where('id',$order['id'])->update(['mid'=>$member['id']]);
            }
        }

        //查询分享者信息
        //$sharer_member = Db::name('member')->where('aid',$order['aid'])->where('unionid',$order['sharer_unionid'])->find();
        if($order['sharerid']){
            $share_info = Db::name('channels_sharer')->where('id',$order['sharerid'])->where('appid',$order['appid'])->where('bid',$order['bid'])->where('isbind',1)->where('aid',$order['aid'])->find();
        }
        if(!$share_info && !empty($order['sharer_unionid'])){
            $share_info = Db::name('channels_sharer')->where('appid',$order['appid'])->where('unionid',$order['sharer_unionid'])->where('bid',$order['bid'])->where('isbind',1)->where('aid',$order['aid'])->find();
        }
        if(!$share_info && !empty($order['sharer_openid'])){
            $share_info = Db::name('channels_sharer')->where('appid',$order['appid'])->where('openid',$order['sharer_openid'])->where('bid',$order['bid'])->where('isbind',1)->where('aid',$order['aid'])->find();
        }
        if($share_info){
            //后加更新mid和sharerid参数
            if($order['sharerid'] != $share_info['id']){
                Db::name('channels_order')->where('id',$order['id'])->update(['sharerid'=>$share_info['id']]);
            }
            $sharer_member = Db::name('member')->where('aid',$order['aid'])->where('id',$share_info['mid'])->find();
        }

        if(empty($sharer_member) && empty($member['pid'])){
            //会员和分享人都不存在
            return true;
        }

        if(!$member){
            //注册会员 创建的此会员可能为空会员，在wx_channels_sharer_apply定制里会有删除操作，请谨慎增加此会员事件
            $data = [];
            $data['aid'] = $aid;
            $data['unionid'] = $order['unionid'];
            $data['pid'] = $sharer_member['id']??0;
            $data['sex'] = 3;
            $data['createtime'] = time();
            $data['last_visittime'] = time();
            $data['nickname'] = $order['openid'];
            $data['channels_openid'] = $order['openid'];
            $data['platform'] = 'wx_channels';
            $mid = \app\model\Member::add($aid,$data);
            //下单用户在商城不存在
            $default_levelid = Db::name('member_level')->where('aid',$aid)->where('isdefault',1)->value('id');
            $member = [
                'id' => $mid,
                'levelid' => $default_levelid,
                'pid' => $data['pid'],
            ];
            //后加更新mid和sharerid参数
            if($member && $member['id'] && $order['mid'] != $member['id']){
                Db::name('channels_order')->where('id',$order['id'])->update(['mid'=>$member['id']]);
            }
        }

        $agleveldata = Db::name('member_level')->where('aid',$aid)->where('id',$member['levelid'])->find();
        if($agleveldata['can_agent'] > 0 && $agleveldata['commission1own']==1){
            $member['pid'] = $member['id'];
        }
        if(empty($sharer_member) && empty($member['pid'])){
            //会员和分享人都不存在
            return true;
        }
        if($member && empty($member['pid']) && $sharer_member && $sharer_member['id'] != $member['id']){
            //下单人是商城会员，并且未绑定推荐人时，绑定分享员为推荐人
            $edit_data = [
                'id' => $member['id'],
                'pid' => $sharer_member['id']
            ];
            \app\model\Member::edit($order['aid'],$edit_data);
            $pid = $sharer_member['id'];
        }else{
            $pid = $member['pid'];
        }
        if($sysset['channels_order_fenxiao']==1){
            //分享员作为推荐人发放
            $parent = $sharer_member;
        }else{
            //按商城推荐网发放
            $parent = Db::name('member')->where('aid',$aid)->where('id',$pid)->find();
        }
        if(!$parent){
            //未找到推荐人
            return true;
        }
        $path = ltrim($parent['path'].','.$parent['id'],',');
        $member_data = [
            'id' => $member['id']??0,
            'pid' => $parent['id'],
            'path' => $path,
            'levelid' => $member['levelid']??0,
            'pid_origin' => 0
        ];
        foreach($order_goods as $og){
            $product_info = Db::name('channels_product')->where('product_id',$og['product_id'])->find();
            //发放分销佣金
            $product = [
                'commissionset' => $product_info['commissionset'],
                'commissiondata1' => $product_info['commissiondata1'],
                'commissiondata2' => $product_info['commissiondata2'],
                'commissiondata3' => $product_info['commissiondata3'],
                'commissiondata4' => $product_info['commissiondata4'],
                'fx_differential' => -1
            ];
            $commission_data = \app\common\Fenxiao::fenxiao($sysset,$member_data,$product,$og['sku_cnt'],bcmul($og['sale_price'],$og['sku_cnt'],2));
            if($commission_data['parent1'] && ($commission_data['parent1commission']>0 || $commission_data['parent1score']>0)){
                $data_c = [
                    'aid'=>$order['aid'],
                    'mid'=>$commission_data['parent1'],
                    'frommid'=>$member_data['id'],
                    'orderid'=>$order['id'],
                    'ogid'=>$og['id'],
                    'type'=>'channels',
                    'commission'=>$commission_data['parent1commission']??0,
                    'score'=>$commission_data['parent1score']??0,
                    'remark'=>t('下级').'复购奖励',
                    'createtime'=>time()
                ];

                //多商户
                if($order['bid'] && $order['bid']>0){
                    $data_c['bid'] = $order['bid'];
                    //查询上级是否是该商户分享员
                    $sharer = Db::name('channels_sharer')->where('appid',$order['appid'])->where('mid',$commission_data['parent1'])->where('bid',$order['bid'])->where('aid',$order['aid'])->where('isbind',1)->field('id')->find();
                    if($sharer){
                        $data_c['sharerid'] = $sharer['id'];
                    }
                    Db::name('channels_sharer_commission_record')->insert($data_c);
                }else{
                    Db::name('member_commission_record')->insert($data_c);
                }
            }
            if($commission_data['parent2'] && ($commission_data['parent2commission']>0 || $commission_data['parent2score']>0)){
                $data_c = [
                    'aid'=>$order['aid'],
                    'mid'=>$commission_data['parent2'],
                    'frommid'=>$member_data['id'],
                    'orderid'=>$order['id'],
                    'ogid'=>$og['id'],
                    'type'=>'channels',
                    'commission'=>$commission_data['parent2commission']??0,
                    'score'=>$commission_data['parent2score']??0,
                    'remark'=>t('下二级').'复购奖励',
                    'createtime'=>time()
                ];

                //多商户
                if($order['bid'] && $order['bid']>0){
                    $data_c['bid'] = $order['bid'];
                    //查询上级是否是该商户分享员
                    $sharer = Db::name('channels_sharer')->where('appid',$order['appid'])->where('mid',$commission_data['parent2'])->where('bid',$order['bid'])->where('aid',$order['aid'])->where('isbind',1)->field('id')->find();
                    if($sharer){
                        $data_c['sharerid'] = $sharer['id'];
                    }
                    Db::name('channels_sharer_commission_record')->insert($data_c);
                }else{
                    Db::name('member_commission_record')->insert($data_c);
                }
            }
            if($commission_data['parent3'] && ($commission_data['parent3commission']>0 || $commission_data['parent3score']>0)){
                $data_c = [
                    'aid'=>$order['aid'],
                    'mid'=>$commission_data['parent3'],
                    'frommid'=>$member_data['id'],
                    'orderid'=>$order['id'],
                    'ogid'=>$og['id'],
                    'type'=>'channels',
                    'commission'=>$commission_data['parent3commission']??0,
                    'score'=>$commission_data['parent3score']??0,
                    'remark'=>t('下三级').'复购奖励',
                    'createtime'=>time()
                ];

                //多商户
                if($order['bid'] && $order['bid']>0){
                    $data_c['bid'] = $order['bid'];
                    //查询上级是否是该商户分享员
                    $sharer = Db::name('channels_sharer')->where('appid',$order['appid'])->where('mid',$commission_data['parent3'])->where('bid',$order['bid'])->where('aid',$order['aid'])->where('isbind',1)->field('id')->find();
                    if($sharer){
                        $data_c['sharerid'] = $sharer['id'];
                    }
                    Db::name('channels_sharer_commission_record')->insert($data_c);
                }else{
                    Db::name('member_commission_record')->insert($data_c);
                }
            }
            $ogupdate = [];
            $ogupdate['parent1'] = $commission_data['parent1']??0;
            $ogupdate['parent2'] = $commission_data['parent2']??0;
            $ogupdate['parent3'] = $commission_data['parent3']??0;
            $ogupdate['parent4'] = $commission_data['parent4']??0;
            $ogupdate['parent1commission'] = $commission_data['parent1commission']??0;
            $ogupdate['parent2commission'] = $commission_data['parent2commission']??0;
            $ogupdate['parent3commission'] = $commission_data['parent3commission']??0;
            $ogupdate['parent4commission'] = $commission_data['parent4commission']??0;
            $ogupdate['parent1score'] = $commission_data['parent1score']??0;
            $ogupdate['parent2score'] = $commission_data['parent2score']??0;
            $ogupdate['parent3score'] = $commission_data['parent3score']??0;
            $ogupdate['commission_data'] = json_encode($commission_data);
            $ogupdate['cacl_commission'] = 1;
            Db::name('channels_order_goods')->where('id',$og['id'])->update($ogupdate);
        }
        if($is_send==1){
            \app\common\Order::giveCommission($order,'channels');
        }
        return true;
    }

    //加分享者佣金
    // $aid $bid $mid $frommid $commission $remark
    // $addtotal
    // $fhtype 类型 枚举值 'unfrozen'解冻,'withdraw_back'提现退回,admin管理员修改
    // $fhid 其他记录id
    // $commissionid 分享者佣金账号id
    // $sharerid 分享者id
    public static function addsharercommission($params = ['aid'=>0,'bid'=>0,'mid'=>0,'frommid'=>0,'commission'=>0,'remark'=>'','addtotal'=>1,'fhtype'=>'','fhid'=>0,'commissionid'=>0,'sharerid'=>0]){
        if(!$params) return ['status'=>0,'msg'=>'参数不存在'];

        $aid = $params['aid']??0;
        $bid = $params['bid']??0;
        $mid = $params['mid']??0;
        $frommid    = $params['frommid']??0;
        $commission = $params['commission']??0;
        $remark     = $params['remark']??'';
        $addtotal   = $params['addtotal']??1;
        $fhtype     = $params['fhtype']??'';
        $fhid       = $params['fhid']??0;
        $commissionid = $params['commissionid']??0;
        $sharerid     = $params['sharerid']??0;

        if($commission==0) return ['status'=>0,'msg'=>''];
        $countmember = Db::name('member')->where('id',$mid)->where('aid',$aid)->count('id');
        if(!$countmember) return ['status'=>0,'msg'=>t('会员').'不存在'];

        //查询此分享者佣金账号
        if($commissionid){
            $sharerCommission = Db::name('channels_sharer_commission')->where('id',$commissionid)->where('mid',$mid)->where('bid',$bid)->where('aid',$aid)->lock(true)->find();
        }else{
            $sharerCommission = Db::name('channels_sharer_commission')->where('sharerid',$sharerid)->where('mid',$mid)->where('bid',$bid)->where('aid',$aid)->lock(true)->find();
        }
        if(!$sharerCommission){
            return ['status'=>0,'msg'=>t('会员').'该商家佣金账号不存在'];
        }

        $totalcommission = $sharerCommission['totalcommission'];
        if($commission > 0 && $addtotal==1){
            $totalcommission += $commission;
        }
        $after = $sharerCommission['commission'] + $commission;
        if($after<0){
            return ['status'=>0,'msg'=>'该商家佣金账号余额不足'];
        }
        $update_member = ['totalcommission'=>$totalcommission,'commission'=>$after];
        $up = Db::name('channels_sharer_commission')->where('id',$sharerCommission['id'])->update($update_member);
        if($up){
            $data = [];
            $data['aid'] = $aid;
            $data['bid'] = $bid;
            $data['mid'] = $mid;
            $data['frommid']    = $frommid;
            $data['commission'] = $commission;
            $data['after']      = $after;
            $data['remark']     = $remark;
            $data['fhtype']     = $fhtype;
            $data['fhid']       = $fhid;//其他记录ID
            $data['uid']        = defined('uid') && !empty(uid)?uid:0;//记录操作员ID
            $data['createtime'] = time();
            $data['commissionid'] = $sharerCommission['id'];
            $data['sharerid']     = $sharerCommission['sharerid'];
            Db::name('channels_sharer_commission_log')->insert($data);
            return ['status'=>1,'msg'=>''];
        }else{
            return ['status'=>0,'msg'=>'操作失败'];
        }
    }

    public static function deal_applybind(){
        if(getcustom('wx_channels_sharer_apply')){
            $admins = Db::name('admin')->where('status',1)->field('id')->select()->toArray();
            if($admins){
                foreach($admins as $admin){
                    $aid = $admin['id'];
                    //查询发送次数小于10的申请记录列表
                    $logs = Db::name('channels_sharer_applylog')->where('aid',$aid)->where('sendnum','<',10)->where('status',0)->select()->toArray();
                    if($logs){
                        foreach($logs as $log){
                            //处理申请记录
                            self::deal_applybindlog($aid,$log);
                        }
                    }
                }
            }
        }
    }

    public static function deal_applybindlog($aid,$log){
        if(getcustom('wx_channels_sharer_apply')){
            $data = [];
            $data['mid']      = $log['mid'];
            $data['sendnum']  = $log['sendnum']+1;
            $data['sendtime'] = time();
            $data['status']   = $log['status'];
            if($log['status'] == 0){
                //查询会员是否存在
                $count = Db::name('member')->where('id',$log['mid'])->where('aid',$aid)->count('id');
                if(!$count){
                    //取消
                    $data['status'] = -1;
                }else{
                    //查询分享员信息
                    $res_share = self::getSharer($log['aid'],$log['bid'],$log['appid'],'',$log['weixin']);
                    if($res_share && $res_share['status'] == 1){
                        $share_info = $res_share['data'];
                        if($share_info['openid']){
                            //查询此分享员是否存在
                            $sharer = Db::name('channels_sharer')->where('openid',$share_info['openid'])->find();
                            if($sharer){
                                $data['status'] = 1;
                                if(empty($sharer['mid']) || ($sharer['mid'] && $sharer['mid'] != $log['mid'])){
                                    $up = Db::name('channels_sharer')->where('id',$sharer['id'])->update(['mid'=>$log['mid']]);
                                    if($up && $sharer['mid'] && $sharer['mid'] != $log['mid']){
                                        //查询是否是创建的空会员，是则删除
                                        $member = Db::name('member')->where('id',$sharer['mid'])->where('platform','wx_channels')->where('commission',0)->where('money',0)->where('score',0)->find();
                                        if($member && empty($member['wxopenid']) && empty($member['mpopenid']) && empty($member['tel'])){
                                            Db::name('member')->where('id',$sharer['mid'])->delete();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $sql = Db::name('channels_sharer_applylog')->where('id',$log['id'])->update($data);
            }
            return ['status'=>$data['status']];
        }
    }
}