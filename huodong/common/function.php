<?php
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'CacheFactory.php');

if (!function_exists('processNickname')) {
    function processNickname($userinfo,$showtype,$maskmobile=true){
        $nickname=pack('H*', $userinfo['nickname']);
        if($showtype==2 && !empty($userinfo['signname'])){
            //显示姓名
            $nickname=$userinfo['signname'];
        }
        if($showtype==3 && !empty($userinfo['phone'])){
            //显示电话
            if($maskmobile){
                $nickname=substr_replace($userinfo['phone'],'****',3,4);
            }else{
                $nickname=$userinfo['phone'];
            }
        }
        return $nickname;
    }
}


if (!function_exists('writecert')) {
	function writecert($url,$certname){
		if(empty($url))return '';
		$path=str_replace('common'.DIRECTORY_SEPARATOR.'function.php', 'data'.DIRECTORY_SEPARATOR, __FILE__);
		$filepath=$path.$certname;
		if(!file_exists($filepath)){
			$myfile = fopen($filepath, "w") or die("Unable to open file!");
            //如果文件存在阿里云上
            if(SAVEFILEMODE=='aliyunoss'){
                //从阿里云oss中获得pem文件内容
                require_once ('../library/aliyunosssdk/sdk.class.php');
                $oss_sdk_service = new ALIOSS ();
                $path_arr=explode(OBJECT_PATH,$url);
                $oss_sdk_service->set_host_name(defined('ENDPOINT')?ENDPOINT:'oss-cn-hangzhou-internal.aliyuncs.com');
                $object_obj=$oss_sdk_service->get_object(BUCKET_NAME,OBJECT_PATH.$path_arr[1]);
                $certcontent=$object_obj->body;
                fwrite($myfile, $certcontent);
                fclose($myfile);
            }
            
		}
		return $filepath;
	}
}

//取指定长度的随机数字字符串
if (!function_exists('randStr')) {
    function randStr($len = 10)
    {
        $rand='';
        for ($i = 0; $i < $len; $i++) {
            $rand .= mt_rand(0, 9);
        }
        return $rand;
    }
}
if (!function_exists('returnmsg')) {
    function returnmsg($code,$msg='',$data=array(),$type='json'){
        $returndata=array('code'=>$code,'message'=>$msg);
        if(!empty($data)){
            $returndata['data']=$data;
        }
        if($type=='json'){
            return json_encode($returndata);
        }
        return $returndata;
    }
}

if (!function_exists('getActivityDisplayConfig')) {
    /**
     * 从新系统 hd_activity.screen_config 读取显示设置
     * @param int $activity_id 活动ID，0则取最新活动
     * @return array|null 返回 ['sign_show_style'=>..., 'use_wx_avatar'=>...] 或 null
     */
    function getActivityDisplayConfig($activity_id = 0) {
        try {
            $newDbConfig = @include(dirname(__FILE__) . '/../../config.php');
            if (!$newDbConfig || empty($newDbConfig['hostname'])) {
                return null;
            }
            $newConn = @new mysqli($newDbConfig['hostname'], $newDbConfig['username'], $newDbConfig['password'], $newDbConfig['database'], (int)$newDbConfig['hostport']);
            if ($newConn->connect_error) {
                return null;
            }
            $newConn->set_charset('utf8mb4');
            $prefix = isset($newDbConfig['prefix']) ? $newDbConfig['prefix'] : 'ddwx_';
            $activity_id = intval($activity_id);

            if ($activity_id > 0) {
                $result = $newConn->query("SELECT screen_config FROM {$prefix}hd_activity WHERE id = {$activity_id}");
                $row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
            } else {
                $result = $newConn->query("SELECT screen_config FROM {$prefix}hd_activity ORDER BY id DESC LIMIT 1");
                $row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
            }

            $newConn->close();

            if (!$row || empty($row['screen_config'])) {
                return null;
            }

            $scfg = json_decode($row['screen_config'], true);
            if (!is_array($scfg)) {
                return null;
            }

            $config = array();
            $config['sign_show_style'] = isset($scfg['sign_show_style']) ? $scfg['sign_show_style'] : null;
            $config['use_wx_avatar'] = isset($scfg['use_wx_avatar']) ? intval($scfg['use_wx_avatar']) : 1;

            return $config;
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('processAvatar')) {
    /**
     * 根据 use_wx_avatar 配置处理头像
     * @param array $userinfo 用户信息数组，需包含 avatar 字段
     * @param int $use_wx_avatar 头像来源：1=微信头像, 0=使用签到照片
     * @return string 头像URL
     */
    function processAvatar($userinfo, $use_wx_avatar = 1) {
        // use_wx_avatar=0 且存在 sign_photo 时使用签到照片
        if ($use_wx_avatar == 0 && !empty($userinfo['sign_photo'])) {
            return $userinfo['sign_photo'];
        }
        // 默认使用微信头像
        return isset($userinfo['avatar']) ? $userinfo['avatar'] : '';
    }
}

if (!function_exists('blackword')) {
    //屏蔽字
    function blackword($content, $blackword) {
        if (! empty ( $blackword )) {
            $blackword=str_replace('，',',',$blackword);
            $blackarr = explode ( ",", $blackword);
            foreach ( $blackarr as $v ) {
                if (strstr( $content, $v )) {
                    return 1;
                }
            }
            return 0;
        }
    }
}