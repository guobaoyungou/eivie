<?php
/**
 * 手机端页面公共调用的文件
 * PHP version 5.4+
 * 
 * @category Mobile
 * 
 * @package Common
 * 
 * */
define(
    'COMPILEPATH', 
    str_replace(DIRECTORY_SEPARATOR.'mobile', '', dirname(__FILE__)).
    DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'templates_c'.DIRECTORY_SEPARATOR
);
require_once dirname(__FILE__) . '/../common/db.class.php';
require_once dirname(__FILE__) . '/../common/http_helper.php';
require_once dirname(__FILE__) . '/../common/weixin_helper.php';
require_once dirname(__FILE__) . '/../common/url_helper.php';
$currenturl = request_scheme().'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].
    ($_SERVER['QUERY_STRING']==''?'':'?'.$_SERVER['QUERY_STRING']);
if(strpos(strtolower($_SERVER['SERVER_SOFTWARE']),'apache')===false){
    $currenturl
        =request_scheme().'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}

$load->model('Wall_model');
$wall_config=$load->wall_model->getConfig();
$load->model('Weixin_model');
$weixin_config=$load->weixin_model->getConfig();

// 从 hd_activity.screen_config 读取手机签到页配置（强制关注公众号授权登录开关）
$_commonLink = MysqliConnection::getlink();
$_commonActResult = mysqli_query($_commonLink, "SELECT screen_config FROM hd_activity ORDER BY id DESC LIMIT 1");
$_commonActRow = $_commonActResult ? mysqli_fetch_assoc($_commonActResult) : null;
$_commonMobileConfig = ($_commonActRow && !empty($_commonActRow['screen_config'])) ? json_decode($_commonActRow['screen_config'], true) : [];
$_forceWxAuth = isset($_commonMobileConfig['mobile_force_wx_auth']) ? intval($_commonMobileConfig['mobile_force_wx_auth']) : 1;

// $wall_config['rentweixin']1借用其他微信服务号获取用户信息2表示使用微赢的现场活动公众号授权，默认值为2，选2可以不要对接任何东西直接使用
if ($wall_config['rentweixin']==1 && $weixin_config['appid']!='') {//使用用户自己的公众号
    if (!isset($_GET['rentopenid'])) {//如果还没有获取到openid
        if (empty($_GET['vcode']) || $_GET['vcode']!=$wall_config['verifycode']) {
            echo '找不到活动';
            exit();
        }
        if (empty($_GET['code'])) {//还没有获取到code
            $fromurl=$currenturl;
            // 根据「强制关注公众号授权登录」配置选择授权方式
            // snsapi_userinfo：弹出授权页面，获取用户完整信息（需关注公众号）
            // snsapi_base：静默授权，仅获取openid（无需关注公众号）
            $_authScope = $_forceWxAuth ? 'snsapi_userinfo' : 'snsapi_base';
            $url=getauthorizeurl($fromurl, $_authScope, $weixin_config['appid']);
            header('location:' . $url);
            exit();
        } else {//获取到code之后获取用户信息
            $tokeninfo = getaccess_token($_GET['code'], $weixin_config['appid'], $weixin_config['appsecret']);
            $tokeninfo = json_decode($tokeninfo, true);
            if ($_forceWxAuth) {
                // 强制关注模式：通过 snsapi_userinfo 获取完整用户信息
                $userinfo = getsnsuserinfo($tokeninfo['access_token'], $tokeninfo['openid']);
                $userinfo = json_decode($userinfo);
                if (is_string($userinfo)) {
                    $userinfo = json_decode($userinfo, true);
                }
                $userinfo['nickname']=bin2hex($userinfo['nickname']);
            } else {
                // 非强制关注模式：snsapi_base 静默授权，仅获取 openid
                $userinfo = array();
                $userinfo['nickname'] = bin2hex('微信用户');
                $userinfo['headimgurl'] = '';
                $userinfo['sex'] = 0;
            }
            $userinfo['openid']=$tokeninfo['openid'];
            $userinfo['rentopenid']=$tokeninfo['openid'];
            $load->model('Flag_model');
            $load->flag_model->saveRemoteUserinfo($userinfo);

            $url_arr=parse_url($currenturl);
            $baseurl=$url_arr['scheme'].'://'.$url_arr['host'].$url_arr['path'];

            //刚获取到用户信息还没有签到
            header('location:'.$baseurl.'?rentopenid='.$userinfo['openid']);
            exit();
        }
    } else {//获取到用户信息之后
        $openid=$_GET['rentopenid'];
        $flag_m=new M('flag');
        $userinfo=$flag_m->find('openid="'.$openid.'"');
        if ($userinfo['flag']==1) {//如果检查用户还没有签到
            if (strpos($currenturl, 'qiandao.php')===false) {
                header(
                    'location:/mobile/qiandao.php?rentopenid='.
                    $userinfo['rentopenid'].'&fromurl='.urlencode($currenturl)
                );
                exit();
            }
        }
    }
} else {

    //使用默认公众号授权
    if (!isset($_GET['rentopenid'])) {
        if (empty($_GET['vcode']) || $_GET['vcode']!=$wall_config['verifycode']) {
            echo '找不到活动';
            exit();
        }
        //先去获取用户信息
        $url='http://api.vdcom.cn/wxgate/index?url='.urlencode($currenturl);
        header('location:'.$url);
        exit();
    } else {
        $openid=$_GET['rentopenid'];
        $flag_m=new M('flag');
        $userinfo=$flag_m->find('openid="'.$openid.'"');
        if (!$userinfo) {
            $url='http://api.vdcom.cn/wxgate/getuserinfobyrentopenid?rentopenid='.$_GET['rentopenid'];
            $json=http_get($url);
            $userinfo_arr=json_decode($json, true);
            if ($userinfo_arr['error']>0) {
                $userinfo=array();
                $userinfo['openid']=$userinfo_arr['userinfo']['openid'];
                $userinfo['rentopenid']=$userinfo_arr['userinfo']['openid'];
                $userinfo['nickname']=$userinfo_arr['userinfo']['nickname'];
                $userinfo['headimgurl']=$userinfo_arr['userinfo']['headimgurl'];
                $userinfo['sex']=$userinfo_arr['userinfo']['sex'];
                $load->model('Flag_model');
                $return=$load->flag_model->saveRemoteUserinfo($userinfo);
            }
            if (strpos($currenturl, 'qiandao.php')===false) {
                header('location:/mobile/qiandao.php?rentopenid='.$userinfo['rentopenid'].'&fromurl='.urlencode($currenturl));
                exit();
            }
            
        } else {
            if ($userinfo['flag']==1 || $userinfo['status']==2) {
                if (strpos($currenturl,'qiandao.php')===false) {
                    if ($userinfo['status']==2) {
                        header('location:/mobile/qiandao.php?rentopenid='.$userinfo['rentopenid']);
                        exit();
                    } else {
                        header('location:/mobile/qiandao.php?rentopenid='.$userinfo['rentopenid'].'&fromurl='.urlencode($currenturl));
                        exit();
                    }
                }
            }
        }
        
    }
}
