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

// 应用公共文件

use think\facade\Log;

function jsonEncode($arr){
	return json_encode($arr, JSON_UNESCAPED_UNICODE);
}
function request_post($url, $keysArr=array(), $timeout = 60){
	$client = new \GuzzleHttp\Client(['timeout'=>$timeout,'verify'=>false]);
	try {
		if(is_array($keysArr)){
			$response = $client->request('POST',$url,['form_params'=>$keysArr]);
		}else{
			$response = $client->request('POST',$url,['body'=>$keysArr]);
		}
		$rs = $response->getBody()->getContents();
	} catch(\Throwable $e) {
		\think\facade\Log::write($e->getMessage());
		//var_dump($e->getMessage());
	}
	return $rs;
}
function request_get($url, $keysArr=array(), $timeout = 60){
	$client = new \GuzzleHttp\Client(['timeout'=>$timeout,'verify'=>false]);
	try {
		if($keysArr){
			$response = $client->request('GET',$url,['query'=>$keysArr]);
		}else{
			$response = $client->request('GET',$url);
		}
		$rs = $response->getBody()->getContents();
	} catch(\Throwable $e){
		\think\facade\Log::write($e->getMessage());
		//var_dump($e->getMessage());
	}
	return $rs;
}
//curl post请求
function curl_post($url, $keysArr=array(), $flag = 0,$headers=array(),$timeout = 60){
	$ch = curl_init();
	if(! $flag) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $keysArr);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
    // 设置请求的最长执行时间为 30 秒
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	if($headers){
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	try {
		$response = curl_exec($ch);
	} catch (\Exception $e) {
       return null;
    }
	$curlError = curl_error($ch);
    if(!empty($curlError)) {
       return null;
    }
	curl_close($ch);
	//-------请求为空
	if(empty($response)){
		return null;
	}
	return $response;
}
function curl_form_post($url, $keysArr,$withFile = false){
    if($withFile)
	    $post_data = ($keysArr);//重点
    else
        $post_data = http_build_query($keysArr);//重点
	$ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_URL, $url);
    if($withFile)
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));//重点
    else
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));//重点
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $ret = curl_exec($ch);
    curl_close($ch);
    return $ret;
}

//curl get请求
function curl_get($url,$keysArr=array(),$headers=array(),$timeout=60){
	if(!empty($keysArr)){
		$url = strpos($url,'?')===false ? ($url."?") : ($url."&");
		$valueArr = array();
		foreach($keysArr as $key => $val){
			$valueArr[] = "$key=$val";
		}
		$keyStr = implode("&",$valueArr);
		$url .= ($keyStr);
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    // 设置请求的最长执行时间为 30 秒
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	if($headers){
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	try {
		$response = curl_exec($ch);
	} catch (\Exception $e) {
       return null;
    }
	$curlError = curl_error($ch);
    if(!empty($curlError)) {
       return null;
    }
	curl_close($ch);
	//-------请求为空
	if(empty($response)){
		return null;
	}
	return $response;
}


/**
 * 使用fsocketopen()方式发送异步请求,put方式
 */
function syncRequest($url,$post_data="",$cookie=[], $debug=false){
	$url_array = parse_url($url);
	$hostname = $url_array['host'];
	$port = isset($url_array['port'])? $url_array['port'] : 443;
	$requestPath = $url_array['path'] ."?". $url_array['query'];
	$fp = fsockopen('ssl://'.$hostname, $port, $errno, $errstr, 10);
//	var_dump($fp);
	if (!$fp) {
	  //echo "$errstr ($errno)";
	  return false;
	}
	$cookie_str = '';
	foreach ($cookie as $k => $v) {
		$cookie_str .= urlencode($k) .'='. urlencode($v) .'; ';
	}

	$method = "GET";

	if(!empty($post_data)){
	  $method = "POST";
	}
	$header = "$method $requestPath HTTP/1.1\r\n";
	$header.="Host: $hostname\r\n";

	$crlf = "\r\n";
	if (!empty($cookie_str)) {
		$header .= 'Cookie: '. substr($cookie_str, 0, -2) . $crlf;
	}

	if(!empty($post_data)){
		$_post = $post_data;
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";//POST数据
		$header .= "Content-Length: ". strlen($_post) ."\r\n";//POST数据的长度
		$header.="Connection: Close\r\n\r\n";//长连接关闭
		$header .= $_post . "\r\n\r\n"; //传递POST数据
	}else{
		$header.="Connection: Close\r\n\r\n";//长连接关闭
	}
	//var_dump($header);
	fwrite($fp, $header);
	//usleep(1000);
	//-----------------调试代码区间-----------------
	//注如果开启下面的注释,异步将不生效可是方便调试
	if($debug){
		$html = '';
		while (!feof($fp)) {
			$html.=fgets($fp);
		}
        Log::write([
            'file' => __FILE__ . ' L' . __LINE__,
            'function' => __FUNCTION__,
            '$post_data'=>$post_data,
            '$html' => $html,
        ]);
//		var_dump($html);
	}
	//-----------------调试代码区间-----------------
	fclose($fp);
}


//循环创建目录
if (!function_exists('mk_dir')) {
function mk_dir($dir, $mode = 0777) {
    if (is_dir($dir) || @mkdir($dir, $mode))
        return true;
    if (!mk_dir(dirname($dir), $mode))
        return false;
    return @mkdir($dir, $mode);
}
}
/**
 * 数字转字母 （类似于Excel列标）
 * @param Int $index 索引值
 * @param Int $start 字母起始值
 * @return String 返回字母
 */
if (!function_exists('IntToChr')) {
function IntToChr($index, $start = 65) {
    $str = '';
    if (floor($index / 26) > 0) {
        $str .= IntToChr(floor($index / 26)-1);
    }
    return $str . chr($index % 26 + $start);
}
}

if (!function_exists('random')) {
function random($length, $numeric = false) {
	$seed = base_convert(md5(microtime() . $_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
	if ($numeric) {
		$hash = '';
	} else {
		$hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
		--$length;
	}
	$max = strlen($seed) - 1;
	for ($i = 0; $i < $length; ++$i) {
		$hash .= $seed[mt_rand(0, $max)];
	}

	return $hash;
}
}

//文件大小格式转换
if (!function_exists('sizecount')) {
function sizecount($size) {
	if($size >= 1073741824) {
		$size = round($size / 1073741824 * 100) / 100 . ' GB';
	} elseif($size >= 1048576) {
		$size = round($size / 1048576 * 100) / 100 . ' MB';
	} elseif($size >= 1024) {
		$size = round($size / 1024 * 100) / 100 . ' KB';
	} else {
		$size = $size . ' Bytes';
		$size = round($size / 1024 * 100) / 100 . ' KB';
	}
	return $size;
}
}

//数组转XML
if (!function_exists('array2xml')) {
    function array2xml($arr, $level = 1) {
        $s = $level == 1 ? "<xml>" : '';
        foreach ($arr as $tagname => $value) {
            if (is_numeric($tagname)) {
                $tagname = $value['TagName'];
                unset($value['TagName']);
            }
            if (!is_array($value)) {
                $s .= "<{$tagname}>" . (!is_numeric($value) ? '<![CDATA[' : '') . $value . (!is_numeric($value) ? ']]>' : '') . "</{$tagname}>";
            } else {
                $s .= "<{$tagname}>" . array2xml($value, $level + 1) . "</{$tagname}>";
            }
        }
        $s = preg_replace("/([\x01-\x08\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
        return $level == 1 ? $s . "</xml>" : $s;
    }
}

//多维数组去重
function array_unique_map($arr) {
    $t = array_map('serialize', $arr);
    //利用serialize()方法将数组转换为以字符串形式的一维数组
    $t = array_unique($t);
    //去掉重复值
    $new_arr = array_map('unserialize', $t);
    //然后将刚组建的一维数组转回为php值
    return $new_arr;
}

//查找数组的下一个元素
function getNextArrayValue($array, $targetKey) {
    $found = false;
    foreach ($array as $key => $value) {
        if ($found && is_array($value)) {
            return $value;
        }
        if ($key === $targetKey) {
            $found = true;
        }
    }
    return null; // 未找到返回null
}

//生成毫秒数
if (!function_exists('msectime')) {
function msectime() {
	list($msec, $sec) = explode(' ', microtime());
	$msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
	return $msectime;
}
}

if (!function_exists('echojson')) {
function echojson($data){
	header('Content-type: application/json');
	echo jsonEncode($data);die;
}
}


//快递100 查快递
function kuaidi100_getwuliu($key,$customer,$param=array()){
	//请求参数
	$post_data = array();
	$post_data["customer"] = $customer;
	$post_data["param"] = json_encode($param);
	$sign = md5($post_data["param"].$key.$customer);
	$post_data["sign"] = strtoupper($sign);
	$url = 'http://poll.kuaidi100.com/poll/query.do';    //实时查询请求地址
	$params = "";
	foreach ($post_data as $k=>$v) {
		$params .= "$k=".urlencode($v)."&";              //默认UTF-8编码格式
	}
	$post_data = substr($params, 0, -1);
	//发送post请求
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	return curl_exec($ch);
}
//rgb转换
if (!function_exists('hex2rgb')) {
function hex2rgb($colour) {
	if ($colour[0] == '#') {
		$colour = substr($colour, 1);
	}
	if (strlen($colour) == 6) {
		list($r, $g, $b) = array($colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]);
	} elseif (strlen($colour) == 3) {
		list($r, $g, $b) = array($colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]);
	} else {
		return false;
	}
	$r = hexdec($r);
	$g = hexdec($g);
	$b = hexdec($b);
	return ['red' => $r, 'green' => $g, 'blue' => $b];
}
}
//计算两组经纬度坐标 之间的距离  lat1 纬度1； lng1 经度1； lat2 纬度2； lng2 经度2； len_type （1:m or 2:km);  return m or km
if (!function_exists('getdistance')) {
function getdistance($lng1, $lat1, $lng2, $lat2, $len_type = 1, $decimal = 2){
	$radLat1 = $lat1 * PI()/ 180.0;   //PI()圆周率
	$radLat2 = $lat2 * PI() / 180.0;
	$a = $radLat1 - $radLat2;
	$b = ($lng1 * PI() / 180.0) - ($lng2 * PI() / 180.0);
	$s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
	$s = $s * 6378.137;
	$s = round($s * 1000);
	if ($len_type --> 1) {
	   $s /= 1000;
	}
	return round($s, $decimal);
}
}

//显示时间
function getshowtime($time){
	//if(time() - $time < 60){
	//	return '刚刚';
	//}elseif(time() - $time < 3600){
	//	$minite = ceil((time() - $time)/60);
	//	return $minite.'分钟前';
	//}else
	if(date('Ymd')==date('Ymd',$time)){
		return date('H:i',$time);
	}elseif(time()-$time<86400){
		return '昨天 '.date('H:i',$time);
	}elseif(date('Y')==date('Y',$time)){
		return date('m-d H:i',$time);
	}else{
		return date('Y-m-d H:i',$time);
	}
}
//评论内容显示
function getshowcontent($content){
	$icontxtArr = ["[微笑]", "[撇嘴]", "[色]", "[发呆]", "[得意]", "[流泪]", "[害羞]", "[闭嘴]", "[睡]", "[大哭]", "[尴尬]", "[发怒]", "[调皮]", "[呲牙]", "[惊讶]", "[难过]", "[酷]", "[囧]", "[抓狂]", "[吐]", "[偷笑]", "[愉快]", "[白眼]", "[傲慢]", "[饥饿]", "[困]", "[惊恐]", "[流汗]", "[憨笑]", "[悠闲]", "[奋斗]", "[咒骂]", "[疑问]", "[嘘]", "[晕]", "[折磨]", "[衰]", "[骷髅]", "[敲打]", "[再见]", "[擦汗]", "[抠鼻]", "[鼓掌]","[糗大了]", "[坏笑]", "[左哼哼]", "[右哼哼]", "[哈欠]", "[鄙视]", "[委屈]", "[快哭了]", "[阴险]", "[亲亲]","[吓]", "[可怜]", "[菜刀]", "[西瓜]", "[啤酒]", "[篮球]", "[乒乓]", "[咖啡]", "[饭]", "[猪头]", "[玫瑰]", "[凋谢]", "[嘴唇]", "[爱心]", "[心碎]", "[蛋糕]","[闪电]", "[炸弹]", "[刀]" ,"[足球]", "[瓢虫]", "[便便]", "[月亮]", "[太阳]","[礼物]", "[拥抱]", "[强]", "[弱]", "[握手]", "[胜利]", "[抱拳]", "[勾引]", "[拳头]", "[差劲]","[爱你]","[NO]","[OK]", "[跳跳]", "[发抖]", "[怄火]", "[转圈]"];
	$imgArr = [];
	for($i=0;$i<94;$i++){
		$imgArr[] = '<img src="'.PRE_URL.'/static/chat/wxface/'.$i.'.png" class="wxfaceimg" style="width:50rpx;display:inline-block">';
	}
	$content = str_replace($icontxtArr,$imgArr,$content);
	return $content;
}

//苹果手机上传图片出现歪倒的情况解决函数 需要安装exif扩展
function iphoneimgrotate($path){
	if(function_exists('exif_read_data')){
		$exif = exif_read_data($path);
		//\think\facade\Log::write($exif);
		if(!empty($exif['Orientation'])) {
			$source = imagecreatefromjpeg($path);
			switch($exif['Orientation']) {
				case 8:
					$source = imagerotate($source,90,0);
					break;
				case 3:
					$source = imagerotate($source,180,0);
					break;
				case 6:
					$source = imagerotate($source,-90,0);
					break;
			}
			imagejpeg($source,$path);  //存储图片
		}
	}
}
//是否微信浏览器
if (!function_exists('is_weixin')) {
function is_weixin() {
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
		return true;
    }
	return false;
}
}
//是否是移动端
if (!function_exists('isMobile')) {
function isMobile(){
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
        return true;
    }
    if (isset ($_SERVER['HTTP_VIA'])){
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    if (isset ($_SERVER['HTTP_USER_AGENT'])){
        $clientkeywords = array ('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile');
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
            return true;
        }
    }
    if (isset ($_SERVER['HTTP_ACCEPT'])){
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))){
            return true;
        }
    }
    return false;
}
}

function getaddressfromip($ip){
	$opts = array('http'=>array('method'=>"GET",'timeout'=>1));
	$context = stream_context_create($opts);
	$mip = file_get_contents("http://whois.pconline.com.cn/ip.jsp?ip=".$ip,false,$context); //太平洋电脑网接口
	$mip = iconv("gbk","utf-8",$mip);
	$str = str_replace("\n",'',str_replace("\r",'',$mip));
	return $str;
}

function getaddressfromtel($tel){
	$host = "https://jisusjhmcx.market.alicloudapi.com";//api访问链接
	$path = "/shouji/query";//API访问后缀
	$method = "GET";
	$appcode = "609ee6436aaf444a8d17feebe6feb8f0";//替换成自己的阿里云appcode
	$headers = array();
	array_push($headers, "Authorization:APPCODE " . $appcode);
	$querys = "shouji={$tel}";  //参数写在这里
	$bodys = "";
	$url = $host . $path . "?" . $querys;//url拼接

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_FAILONERROR, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	//curl_setopt($curl, CURLOPT_HEADER, true); 如不输出json, 请打开这行代码，打印调试头部状态码。
	//状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
	if (1 == strpos("$".$host, "https://"))
	{
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	}
	$rs = curl_exec($curl);
	$rs = json_decode($rs,true);
	$result = $rs['result'];
	if($result && $result['province']){
		$provinceArr = [
			'北京'=>'北京市',
			'天津'=>'天津市',
			'上海'=>'上海市',
			'重庆'=>'重庆市',
			'内蒙古'=>'内蒙古自治区',
			'广西'=>'广西壮族自治区',
			'西藏'=>'西藏自治区',
			'宁夏'=>'宁夏回族自治区',
			'新疆'=>'新疆维吾尔自治区',
			'香港'=>'香港特别行政区',
			'澳门'=>'澳门特别行政区',
		];
		if($provinceArr[$result['province']]){
			$result['province'] = $provinceArr[$result['province']];
		}else{
			$result['province'] = $result['province'].'省';
		}
		$cityArr = [
			'昌吉'=>'昌吉回族自治区',
			'博尔塔拉'=>'博尔塔拉蒙古自治区',
			'巴音郭楞'=>'巴音郭楞蒙古自治区',
			'阿克苏'=>'阿克苏地区',
			'克孜勒苏'=>'克孜勒苏柯尔克孜自治州',
			'喀什'=>'喀什地区',
			'和田'=>'和田地区',
			'伊犁'=>'伊犁哈萨克自治州',
			'塔城'=>'塔城地区',
			'阿勒泰'=>'阿勒泰地区',
		];
		if($cityArr[$result['city']]){
			$result['city'] = $cityArr[$result['city']];
		}else{
			$result['city'] = $result['city'].'市';
		}
	}
	return $result;
}


//信息提示
if (!function_exists('showmsg')) {
function showmsg($msg,$status=0,$url='',$msg2='确定'){
	if(request()->isAjax() || platform=='wx' || platform=='app'){
		echojson(['status'=>$status,'msg'=>$msg,'url'=>$url]);
	}
	$gotohtml = '';
	if($url){
		$gotohtml = '<a class="err_fh" href="'.$url.'">'.$msg2.'</a>';
	}else{
		if($status==0 && $_SERVER['HTTP_REFERER']){
			$gotohtml = '<a class="err_fh" href="'.$_SERVER['HTTP_REFERER'].'">返回</a>';
		}
	}

	echo '<!DOCTYPE html><html><head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width,minimum-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,initial-scale=1.0,user-scalable=no">
	<meta name="robots" content="index, follow" />
	<style>
	* {margin: 0px;padding: 0px;font-family: "微软雅黑";font-size: 14px;}
	body {background: #e31a2c;}
	img {display: block;}
	ul,ol,li {list-style-type: none;}
	a {text-decoration: none;display: block;color: #7e7e7e;}
	.bg_f5{background: #f5f5f5;max-width: 640px;margin: 0 auto;}
	.txt_error{text-align: center;color: #999;font-size: 16px;margin-top: 20px;padding:0 5px}
	.txt_success{text-align: center;color: #333;font-size: 18px;margin-top: 20px;padding:0 5px}
	.error-img{width: 30%;margin: 0 auto;margin-top: 35%;}
	.err_fh{width:110px;height: 28px;line-height: 28px;border: 1px solid #f60;color: #f60;margin: 0 auto;text-align: center;margin-top: 50px;border-radius: 15px;}
	</style>
	<title>信息提示</title>
	</head>
	<body class="bg_f5">
		<img src="'.PRE_URL.'/static/img/'.($status==0?'error':'success').'.png" class="error-img"/>  
		<p class="txt_'.($status==0?'error':'success').'">'.$msg.'</p>
		'.$gotohtml.'
	</body></html>';
	die;
}
}

if (!function_exists('unescape')) {
function unescape($str){
	$ret = '';
	$len = strlen($str);
	for ($i = 0;$i < $len;$i ++) {
		if ($str[$i] == '%' && $str[$i + 1] == 'u') {
			$val = hexdec(substr($str, $i + 2, 4));
			if ($val < 0x7f)$ret .= chr($val);
			else if ($val < 0x800)$ret .= chr(0xc0 | ($val >> 6)) . chr(0x80 | ($val &0x3f));
			else $ret .= chr(0xe0 | ($val >> 12)) . chr(0x80 | (($val >> 6)&0x3f)) . chr(0x80 | ($val &0x3f));
			$i += 5;
		} else if ($str[$i] == '%') {
			if(substr($str, $i, 3) == '%A1'){
				$ret.='¡';
			}elseif(substr($str, $i, 3) == '%A2'){
				$ret.='¢';
			}elseif(substr($str, $i, 3) == '%A3'){
				$ret.='£';
			}elseif(substr($str, $i, 3) == '%A4'){
				$ret.='¤';
			}elseif(substr($str, $i, 3) == '%A5'){
				$ret.='¥';
			}elseif(substr($str, $i, 3) == '%A6'){
				$ret.='¦';
			}elseif(substr($str, $i, 3) == '%A7'){
				$ret.='§';
			}elseif(substr($str, $i, 3) == '%A8'){
				$ret.='¨';
			}elseif(substr($str, $i, 3) == '%A9'){
				$ret.='©';
			}elseif(substr($str, $i, 3) == '%AA'){
				$ret.='ª';
			}elseif(substr($str, $i, 3) == '%AE'){
				$ret.='®';
			}elseif(substr($str, $i, 3) == '%B0'){
				$ret.='°';
			}elseif(substr($str, $i, 3) == '%B1'){
				$ret.='±';
			}elseif(substr($str, $i, 3) == '%B2'){
				$ret.='²';
			}elseif(substr($str, $i, 3) == '%B3'){
				$ret.='³';
			}elseif(substr($str, $i, 3) == '%B4'){
				$ret.='´';
			}elseif(substr($str, $i, 3) == '%B5'){
				$ret.='µ';
			}elseif(substr($str, $i, 3) == '%B6'){
				$ret.='¶';
			}elseif(substr($str, $i, 3) == '%B7'){
				$ret.='·';
			}elseif(substr($str, $i, 3) == '%B8'){
				$ret.='¸';
			}elseif(substr($str, $i, 3) == '%B9'){
				$ret.='¹';
			}elseif(substr($str, $i, 3) == '%BC'){
				$ret.='¼';
			}elseif(substr($str, $i, 3) == '%BD'){
				$ret.='½';
			}elseif(substr($str, $i, 3) == '%BE'){
				$ret.='¾';
			}elseif(substr($str, $i, 3) == '%D7'){
				$ret.='×';
			}elseif(substr($str, $i, 3) == '%F7'){
				$ret.='÷';
			}else{
				$ret .= urldecode(substr($str, $i, 3));
			}
			$i += 2;
		} else $ret .= $str[$i];
	}
	return $ret;
}
}

/**
 * 生成二维码
 * @param $text 二维码内容 文本或者链接
 * @param $logo 二维码中心图案
 * @param $aid
 * @param $filename 指定文件名
 * @return string
 * @throws \Endroid\QrCode\Exception\InvalidPathException
 */
 
function createqrcode($text,$logo='',$aid = 0,$filename = '',$size=70,$bg_image=''){
    if($aid == 0) $aid = aid;
	$qrCode=new \Endroid\QrCode\QrCode();
	$qrCode->setText($text);	 
    $qrCode->setSize(300);
    $qrCode->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0));
    $qrCode->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0));
    $qrCode->setLabelFontSize(16);
	if($logo && file_exists($logo)){
		$qrCode->setLogoPath($logo);
		$qrCode->setLogoSize($size);
	}
    if($bg_image){
        //当有背景图的时候改变二维码大小
        $qrCode->setSize(440);
    }
    if($filename){
        $url = $filename;
    }else{
        $path = 'upload';
        if($aid) $path .= '/'.$aid;
        $url = $path.'/qrcode/'.date('Ym/d_His').rand(10000000,99999999).'.jpg';
    }
    $file = ROOT_PATH.ltrim($url,'/');
	if(!is_dir(dirname($file))){
		@mkdir(dirname($file),0777,true);
	}else{
        if(file_exists($file)){
            $file = ROOT_PATH.ltrim($url,'/');
        }
    }
        $qrCode->writeFile($file);
        $qrCode = $url;
     // 读取二维码图片
    if($bg_image){
        roundImg($qrCode,$qrCode,30);
        $qrCode = imagecreatefromstring(file_get_contents($qrCode));
        // 读取背景图片
        $background = imagecreatefrompng($bg_image);
        $bg_width = imagesx($background);
        $bg_height = imagesy($background);
        $qr_width = imagesx($qrCode);
        $qr_height = imagesy($qrCode);
        // 计算二维码在背景图中的中间位置
        $dst_x = ($bg_width - $qr_width) / 2;
        $dst_y = ($bg_height - $qr_height) / 2;
        // 合并二维码和背景图片
        imagecopy($background, $qrCode, $dst_x, $dst_y, 0, 0, $qr_width, $qr_height);
        // 输出合并后的图片     
        imagepng($background, $url);
        imagedestroy($background);
    }
    return PRE_URL.'/'.$url;    
}
 /**
     * 图片圆角处理函数
     * @param source  $srcFile 本地图片路径
     * @param source  $img_path 保存路径
     * @param string $size 圆角尺寸   0 则为圆形图
     */
    function roundImg($srcFile, $img_path, $size=0) {
        //得到图片后缀
        $ext   = pathinfo($srcFile);
        //设定圆形图片名称为“当前图片名称_cir”,并组装成本地圆形图片路径
        //$img_path = $ext['dirname']."/".$ext['filename']."_cir.".$ext['extension'];
        //检测圆形图片存在的时候直接当前图片返回

        $data = getimagesize($srcFile);
        switch ($data['2']) {
            case 1:
                $im = imagecreatefromgif($srcFile);
                break;
            case 2:
                $im = imagecreatefromjpeg($srcFile);
                break;
            case 3:
                $im = imagecreatefrompng($srcFile);
                break;
            case 6:
                $im = imagecreatefromwbmp($srcFile);
                break;
        }
        $srcImg = $im;
        $w = imagesx($srcImg);
        $h = imagesy($srcImg);
        if (empty($size)){
            $radius = min($w, $h) / 2;
        }else{
            $radius = $size / 2;
        }
        $img = imagecreatetruecolor($w, $h);
        imagesavealpha($img, true); // 设置透明通道
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127); // 拾取一个完全透明的颜色, 最后一个参数127为全透明
        imagefill($img, 0, 0, $bg);
        $r = $radius; // 圆 角半径
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($srcImg, $x, $y);
                if (($x >= $radius && $x <= ($w - $radius)) || ($y >= $radius && $y <= ($h - $radius))) {
                    imagesetpixel($img, $x, $y, $rgbColor); // 不在四角的范围内,直接画
                } else { // 在四角的范围内选择画
                    // 上左
                    $yx = $r; // 圆心X坐标
                    $yy = $r; // 圆心Y坐标
                    if (((($x - $yx) * ($x - $yx) + ($y - $yy) * ($y - $yy)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    // 上右
                    $yx = $w - $r; // 圆心X坐标
                    $yy = $r; // 圆心Y坐标
                    if (((($x - $yx) * ($x - $yx) + ($y - $yy) * ($y - $yy)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    // 下左
                    $yx = $r; // 圆心X坐标
                    $yy = $h - $r; // 圆心Y坐标
                    if (((($x - $yx) * ($x - $yx) + ($y - $yy) * ($y - $yy)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    // 下右
                    $yx = $w - $r; // 圆心X坐标
                    $yy = $h - $r; // 圆心Y坐标
                    if (((($x - $yx) * ($x - $yx) + ($y - $yy) * ($y - $yy)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
        }

        switch ($data['2']) {
            case 1:
                imagegif($img,$img_path);
                break;
            case 2:
                imagejpeg($img,$img_path);
                break;
            case 3:
                imagepng($img,$img_path);
                break;
            case 6:
                image2wbmp($img,$img_path);
                break;
        }

        imagedestroy($img);
        return $img_path;
    }

//生成条形码
function createbarcode($codetext,$returnAll = false,$aid=0){
    if($aid == 0) $aid = aid;
    $codetext = strval($codetext);
    $path = '/upload';
    if($aid) $path .= '/'.$aid;
    $url = $path.'/qrcode/'.date('Ym/d_His').rand(10000000,99999999).'.jpg';
    $file = ROOT_PATH.$url;
    if(!is_dir(dirname($file))){
        @mkdir(dirname($file),0777,true);
    }
    // 引用class文件夹对应的类
    require_once(ROOT_PATH.'extend/barcodegen/class/BCGFontFile.php');
    require_once(ROOT_PATH.'extend/barcodegen/class/BCGColor.php');
    require_once(ROOT_PATH.'extend/barcodegen/class/BCGDrawing.php');

    // 条形码的编码格式
    require_once(ROOT_PATH.'extend/barcodegen/class/BCGcode128.barcode.php');

    // 加载字体大小
    //$font = new BCGFontFile('./class/font/Arial.ttf', 18);

    //颜色条形码
    $color_black = new BCGColor(0, 0, 0);
    $color_white = new BCGColor(255, 255, 255);

    $drawException = null;
    try {
        $code = new BCGcode128();
        $code->setScale(2);
        $code->setThickness(30); // 条形码的厚度
        $code->setForegroundColor($color_black); // 条形码颜色
        $code->setBackgroundColor($color_white); // 空白间隙颜色
        //$code->setFont($font);
        $code->setFont(0);
        $code->parse($codetext); // 条形码需要的数据内容
    } catch(Exception $exception) {
        $drawException = $exception;
    }

    //根据以上条件绘制条形码
    $drawing = new BCGDrawing($file, $color_white);
    if($drawException) {
        $drawing->drawException($drawException);
    } else {
        $drawing->setBarcode($code);
        $drawing->draw();
    }
    $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    if($returnAll){
        return [
            'url'=>$url,
            'doamin'=>request()->domain(),
            'filepath'=>$file
        ];
    }
    return request()->domain().$url;
}

// 图片转化为二进制数据流
function binaryEncodeImage($img_file) {
    $p_size = filesize($img_file);
    $img_binary = fread(fopen($img_file, "r"), $p_size);
    return $img_binary;
}

/*
 * 文档【全国快递物流查询-快递查询接口】：https://market.aliyun.com/detail/cmapi021863#sku=yuncode1586300006?source=5176.29345612&userCode=v0rzgh25
 * $params 数组
 * 参数 aid bid 系统和商家id
 * 需同步修改\app\common\Wxvideo::get_delivery_id()
 */
function express_data($params = ['aid'=>0,'bid'=>0]){
	$rdata = array(
        '申通快递' => 'STO',
        'EMS'    => 'EMS',
        '顺丰速运' => 'SFEXPRESS',
        '圆通速递' => 'YTO',
        '中通快递' => 'ZTO',
        '韵达快递' => 'YUNDA',
        '天天快递' => 'TTKDEX',
        '百世快递' => 'HTKY',
        '全峰快递' => 'QFKD',
        '德邦快递' => 'DEPPON',
        '宅急送'   => 'ZJS',
        '如风达'   => 'RFD',
        '安信达'   => 'ANXINDA',
        '邦送物流' => '',
        'DHL快递'  => 'DHL',
        '大田物流' => 'DTW',
        'EMS国际'  => '',
        '国通快递' => 'GTO',
        '共速达'   => 'GSD',
        '华宇物流' => 'HOAU',
        '佳吉快运' => 'JIAJI',
        '佳怡物流' => 'JIAYI',
        '快捷快递' => 'FASTEXPRESS',
        '龙邦速递' => 'LBEX',
        '联邦快递' => 'FEDEX',
        '联昊通'   => 'LTS',
        '能达速递' => 'ND56',
        '全一快递' => 'APEX',
        '全日通'   => 'QRT',
        '速尔快递' => 'SURE',
        'TNT快递'  => 'TNT',
        '天地华宇' => 'HOAU',
        '新邦物流' => 'XBWL',
        '新蛋物流' => '',
        '优速快递' => 'UC56',
        '中邮物流' => 'ZYWL',
        '安能物流' => 'ANE',
        '安能快递' => 'ANEEX',
        '品骏快递' => 'PJKD',
        '极兔快递' => 'JITU',
        '京东'     => 'JD',
        '丹鸟快递' => 'DANNIAO',
        '信丰'     => 'XFEXPRESS',
        '壹米滴答' => 'YIMIDIDA',
        '百世快运' => 'BSKY',
        '中通快运' => 'ZTO56',
        '日日顺物流' => 'RRS',
        '商家配送' => '',
        "其他"     => ''
    );
    if($params && $params['aid'] >0 ){
        $data = db('express_data')->where('aid',$params['aid'])->where('bid',$params['bid'])->find();
        if($data && !empty($data['express_data'])){
            $data['express_data'] = json_decode($data['express_data'],true);
            if($data['express_data']){
                foreach($data['express_data'] as $k=>&$v){
                    $v = $rdata[$k]??'';
                }
                $rdata = $data['express_data'];
            }
        }
    }
	return $rdata;
}

//阿里系物流编码[不存在的和express_data保持一致]
function express_ali(){
    return array(
        '顺丰速运'=>'SF',//
        '德邦快递'=>'DBKD',//
        '如风达'=>'RFDSM',//
        '佳吉快运'=>'CNEX',
        '快捷速递'=>'FAST',
        '快捷快递'=>'FAST',
        '龙邦速递'=>'LB',
        '龙邦快递'=>'LB',
        '港中能达'=>'NEDA',
        '全一快递'=>'UAPEX',
        '天地华宇'=>'HY',
        '新邦物流'=>'XB',
        '优速快递'=>'UC',
        '中邮物流'=>'zhongyouwuliu',
        '安能物流'=>'ANE56',
        '品骏快递'=>'PJ',
        '品骏物流'=>'PJ',
        '极兔快递'=>'JT'
    );
}

//获取快递公司的标识
function getExpressTag($express_com='',$platform=''){
    if(empty($express_com)){
        return '';
    }

    if($platform=='ali') {
        $targetData = express_ali();
    }elseif ($platform=='100'){
        $targetData = express_data100();
    }
    if(isset($targetData[$express_com])){
        return $targetData[$express_com];
    }
    $allData = express_data();
    if(isset($allData[$express_com])){
        return $allData[$express_com];
    }
    return '';
}

//快递100
function express_data100(){
	return array('圆通速递'=>'yuantong','韵达快递'=>'yunda','中通快递'=>'zhongtong','申通快递'=>'shentong','百世快递'=>'huitongkuaidi','邮政快递包裹'=>'youzhengguonei','顺丰速运'=>'shunfeng','极兔速递'=>'jtexpress','EMS'=>'ems','京东物流'=>'jd','邮政标准快递'=>'youzhengbk','德邦快递'=>'debangkuaidi','德邦'=>'debangwuliu','圆通快运'=>'yuantongkuaiyun','宅急送'=>'zhaijisong','百世快运'=>'baishiwuliu','丰网速运'=>'fengwang','中通快运'=>'zhongtongkuaiyun','中通国际'=>'zhongtongguoji','安能快运'=>'annengwuliu');
}

function db($tablename){
	return \think\facade\Db::name($tablename);
}
function table_name($tablename){
	return config('database.connections.mysql.prefix').$tablename;
}

function table_exists($tablename){
    $table = \think\facade\Db::query("SHOW TABLES LIKE '". table_name($tablename)."'");
    if(empty($table)){
        return false;
    }else{
        return true;
    }
}

function m_url($url,$aid=0,$type=''){
	if($aid == 0) $aid = aid;

	$pre_url = PRE_URL;
	if(!$type){
		$admin = db('admin')->where('id',$aid)->find();
		if($admin['domain']){
			$pre_url = 'https://'.$admin['domain'];
		}
	}
	return $pre_url.'/h5/'.$aid.'.html#/'.ltrim($url,'/');
}

function send_socket($data, $localUrl = null){
	require_once ROOT_PATH.'extend/WebsocketClient.php';
	$config = include(ROOT_PATH.'config.php');
	$socket = new \WebsocketClient('127.0.0.1',$config['kfport'], $localUrl);
	$socket->send(json_encode($data));
}

function getplatformname($platform=null){
	$platformArr = [
		'mp'=>'微信公众号',
		'wx'=>'微信小程序',
		'alipay'=>'支付宝小程序',
		'baidu'=>'百度小程序',
		'toutiao'=>'抖音小程序',
		'qq'=>'QQ小程序',
		'h5'=>'手机H5',
		'app'=>'手机APP',
	];
	if($platform) return $platformArr[$platform];
	return $platformArr;
}

function getcustom($name=null,$aid=0){
	$custom = \think\facade\Config::get('database.custom');
	if(!$custom) $custom = [];
	$custom[] = 'restaurant';
	if(file_exists(ROOT_PATH.'custom.php')){
		$custom2 = include(ROOT_PATH.'custom.php');
		if(is_array($custom2) && !empty($custom2)) $custom = array_merge($custom,$custom2);
	}
	if(!$name) return $custom;
	$exit = in_array($name,$custom);
	if($exit && in_array('custom_control',$custom)){
	    //标记存在时检测定制标记独立控制开关
	    if(!$aid){
            $aid = session('ADMIN_AID')?:input('aid');
        }
        $is_dianda = in_array('custom_control_dianda',$custom);
        return checkcustom($name,$aid,$is_dianda);
    }
    return $exit;
}
function checkcustom($name,$aid=0,$is_dianda=false){
    if(!$aid){
        //没传aid根据系统custom文件来
        return true;
    }
    if($name=='restaurant'){
        return true;
    }
    if(request()->controller()=='WebUpgrade'){
        //执行升级操作的根据系统custom文件来
        return true;
    }
    //独立控制开关检测
    $cache_value = cache($name.'_'.$aid.'_disabled');
    if($cache_value!==null){
        return $cache_value?false:true;
    }
    try {
        $set = \think\facade\Db::name('custom_set')->where('aid',$aid)->where('custom',$name)->find();
        if($set){
            cache($name.'_'.$aid.'_disabled',$set['disabled']);
            return $set['disabled']?false:true;
        }else{
            //后台没有设置项，默认关闭
            if($is_dianda){
                return true;
            }else{
                return false;
            }
        }
    }catch (\Exception $e){
        return true;
    }


}

//获取自定义文本
function t($text, $aid=0){
    static $_textset = [];
    static $_aid = 0;
    if($aid && $_aid!=$aid){
        $_textset = [];
        $_aid = $aid;
    }
    if(!$_textset){
        if(defined('aid') && aid > 0) {
            $sysset = db('admin_set')->where('aid', aid)->find();
            if (!$sysset) return $text;
            $_textset = json_decode($sysset['textset'], true);
            $_textset['color1'] = $sysset['color1'];
            $_textset['color2'] = $sysset['color2'];
            $color1rgb = hex2rgb($sysset['color1']);
            $color2rgb = hex2rgb($sysset['color2']);
            $_textset['color1rgb'] = $color1rgb['red'] . ',' . $color1rgb['green'] . ',' . $color1rgb['blue'];
            $_textset['color2rgb'] = $color2rgb['red'] . ',' . $color2rgb['green'] . ',' . $color2rgb['blue'];
        }elseif($aid){
            $sysset = db('admin_set')->where('aid', $aid)->find();
            if (!$sysset) return $text;
            $_textset = json_decode($sysset['textset'], true);
            $_textset['color1'] = $sysset['color1'];
            $_textset['color2'] = $sysset['color2'];
            $color1rgb = hex2rgb($sysset['color1']);
            $color2rgb = hex2rgb($sysset['color2']);
            $_textset['color1rgb'] = $color1rgb['red'] . ',' . $color1rgb['green'] . ',' . $color1rgb['blue'];
            $_textset['color2rgb'] = $color2rgb['red'] . ',' . $color2rgb['green'] . ',' . $color2rgb['blue'];
        }else{
            $_textset = [];
        }
		if(isset($_textset[$text])){
            return $_textset[$text];
        }
        return $text;
    }else{
        if(isset($_textset[$text])){
            return $_textset[$text];
        }
        return $text;
    }
}

/**
 *数字金额转换成中文大写金额的函数
 *String Int $num 要转换的小写数字或小写字符串
 *return 大写字母
 *小数位为两位
 **/
function num_to_rmb($num) {
    // 验证输入是否为有效数字
    if (!is_numeric($num)) {
        return "无效金额";
    }
    // 处理负数
    $negative = false;
    if ($num < 0) {
        $negative = true;
        $num = abs($num);
    }
    $c1 = "零壹贰叁肆伍陆柒捌玖";
    $c2 = "分角元拾佰仟万拾佰仟亿";
    $num = round($num, 2);
    // 检查金额范围
    if ($num >= 1000000000) { // 10亿
        return "金额太大，请检查";
    }
    $num = $num * 100;
    // 确保转换为整数后不会溢出
    if ($num > PHP_INT_MAX) {
        return "金额太大，请检查";
    }
    $num = (int)$num;
    $i = 0;
    $c = "";
    // 使用字符串长度控制循环次数
    $strNum = (string)$num;
    $len = strlen($strNum);
    for ($i = 0; $i < $len; $i++) {
        $n = $strNum[$len - $i - 1]; // 从最后一位取数字
        $p1 = substr($c1, 3 * (int)$n, 3);
        $p2 = substr($c2, 3 * $i, 3);
        if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
            $c = $p1 . $p2 . $c;
        } else {
            $c = $p1 . $c;
        }
    }
    // 处理多余的零
    $c = str_replace(['零元', '零万', '零亿', '零零'], ['元', '万', '亿', '零'], $c);
    // 去除末尾零
    if (substr($c, -3) == '零') {
        $c = substr($c, 0, -3);
    }
    if (empty($c)) {
        $c = "零元整";
    } else {
        $c .= "整";
    }
    // 添加负号
    if ($negative) {
        $c = "负" . $c;
    }
    return $c;
}

function make_rand_code($codetype, $codelength)
{
    if($codetype == 1){
        $str = '0123456789';
    }
    if($codetype == 2){
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    if($codetype == 3){
        $str = 'abcdefghijklmnopqrstuvwxyz';
    }
    if($codetype == 4){
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    if($codetype == 5){
        $str = 'abcdefghijklmnpqrstuvwxyz0123456789';
    }
    if($codetype == 6){
        $str = 'ABCDEFGHIJKLMNPQRSTUVWXYZ0123456789';
    }
    $len = strlen($str)-1;
    $randstr = '';
    for($j=0;$j<$codelength;$j++) {
        $num=mt_rand(0,$len);
        $randstr .= $str[$num];
    }

    return $randstr;
}

/**
 * 钱数 保留位数     
 * money_format 为系统函数，使用dd区分
 */
function dd_money_format($money=0,$ws=2){
    return sprintf( "%01.".$ws."f" ,(string)round($money,$ws));
}

/**
 * 积分 保留位数     
 * score_format 为系统函数，使用dd区分
 */
function dd_score_format($score=0,$ws=0){
    $score *= pow(10, $ws);
    $score = bcdiv(ceil($score), pow(10, $ws), $ws);
    return sprintf( "%01.".$ws."f" ,$score);
}

/**
 * 格式化金额
 * @param float $amount 要格式化的金额
 * @param int $mode 显示模式：1-智能显示小数，2-固定两位小数
 * @param string $thousands_sep 千位分隔符
 * @param string $decimal_point 小数点符号
 * @return string 格式化后的金额字符串
 */
function formatMoney($amount, $mode = 1, $thousands_sep = '', $decimal_point = '.') {
    // 确保输入是数字
    if (!is_numeric($amount)) {
        return '0';
    }

    // 转换为浮点数
    $amount = (float)$amount;

    // 模式2：固定显示两位小数
    if ($mode === 2) {
        return number_format($amount, 2, $decimal_point, $thousands_sep);
    }

    elseif ($mode === 1) {
        // 模式1：智能显示小数
        // 先四舍五入到两位小数，避免浮点数精度问题
        $rounded = round($amount, 2);

        // 检查是否有小数部分
        if (floor($rounded) == $rounded) {
            // 没有小数部分，返回整数格式
            return number_format($rounded, 0, $decimal_point, $thousands_sep);
        } else {
            // 有小数部分，确定小数位数
            $decimal_places = 2; // 默认两位

            // 检查是否只有一位小数
            $parts = explode('.', (string)$rounded);
            if (isset($parts[1]) && substr($parts[1], -1) === '0') {
                $decimal_places = 1;
            }

            return number_format($rounded, $decimal_places, $decimal_point, $thousands_sep);
        }
    }
}

/**
 * @param int $type 1生成昵称，2生成姓名
 * //汉语 - 给用户自动生成昵称
 */
function getRandomNickname($type = 1){

    /**
     * 随机昵称 形容词
     */
    $nicheng_tou=['迷你的','鲜艳的','飞快的','真实的','清新的','幸福的','可耐的','快乐的','冷静的','醉熏的','潇洒的','糊涂的','积极的','冷酷的','深情的','粗暴的',
        '温柔的','可爱的','愉快的','义气的','认真的','威武的','帅气的','传统的','潇洒的','漂亮的','自然的','专一的','听话的','昏睡的','狂野的','等待的','搞怪的',
        '幽默的','魁梧的','活泼的','开心的','高兴的','超帅的','留胡子的','坦率的','直率的','轻松的','痴情的','完美的','精明的','无聊的','有魅力的','丰富的','繁荣的',
        '饱满的','炙热的','暴躁的','碧蓝的','俊逸的','英勇的','健忘的','故意的','无心的','土豪的','朴实的','兴奋的','幸福的','淡定的','不安的','阔达的','孤独的',
        '独特的','疯狂的','时尚的','落后的','风趣的','忧伤的','大胆的','爱笑的','矮小的','健康的','合适的','玩命的','沉默的','斯文的','香蕉','苹果','鲤鱼','鳗鱼',
        '任性的','细心的','粗心的','大意的','甜甜的','酷酷的','健壮的','英俊的','霸气的','阳光的','默默的','大力的','孝顺的','忧虑的','着急的','紧张的','善良的',
        '凶狠的','害怕的','重要的','危机的','欢喜的','欣慰的','满意的','跳跃的','诚心的','称心的','如意的','怡然的','娇气的','无奈的','无语的','激动的','愤怒的',
        '美好的','感动的','激情的','激昂的','震动的','虚拟的','超级的','寒冷的','精明的','明理的','犹豫的','忧郁的','寂寞的','奋斗的','勤奋的','现代的','过时的',
        '稳重的','热情的','含蓄的','开放的','无辜的','多情的','纯真的','拉长的','热心的','从容的','体贴的','风中的','曾经的','追寻的','儒雅的','优雅的','开朗的',
        '外向的','内向的','清爽的','文艺的','长情的','平常的','单身的','伶俐的','高大的','懦弱的','柔弱的','爱笑的','乐观的','耍酷的','酷炫的','神勇的','年轻的',
        '唠叨的','瘦瘦的','无情的','包容的','顺心的','畅快的','舒适的','靓丽的','负责的','背后的','简单的','谦让的','彩色的','缥缈的','欢呼的','生动的','复杂的',
        '慈祥的','仁爱的','魔幻的','虚幻的','淡然的','受伤的','雪白的','高高的','糟糕的','顺利的','闪闪的','羞涩的','缓慢的','迅速的','优秀的','聪明的','含糊的',
        '俏皮的','淡淡的','坚强的','平淡的','欣喜的','能干的','灵巧的','友好的','机智的','机灵的','正直的','谨慎的','俭朴的','殷勤的','虚心的','辛勤的','自觉的',
        '无私的','无限的','踏实的','老实的','现实的','可靠的','务实的','拼搏的','个性的','粗犷的','活力的','成就的','勤劳的','单纯的','落寞的','朴素的','悲凉的',
        '忧心的','洁净的','清秀的','自由的','小巧的','单薄的','贪玩的','刻苦的','干净的','壮观的','和谐的','文静的','调皮的','害羞的','安详的','自信的','端庄的',
        '坚定的','美满的','舒心的','温暖的','专注的','勤恳的','美丽的','腼腆的','优美的','甜美的','甜蜜的','整齐的','动人的','典雅的','尊敬的','舒服的','妩媚的',
        '秀丽的','喜悦的','甜美的','彪壮的','强健的','大方的','俊秀的','聪慧的','迷人的','陶醉的','悦耳的','动听的','明亮的','结实的','魁梧的','标致的','清脆的',
        '敏感的','光亮的','大气的','老迟到的','知性的','冷傲的','呆萌的','野性的','隐形的','笑点低的','微笑的','笨笨的','难过的','沉静的','火星上的','失眠的',
        '安静的','纯情的','要减肥的','迷路的','烂漫的','哭泣的','贤惠的','苗条的','温婉的','发嗲的','会撒娇的','贪玩的','执着的','眯眯眼的','花痴的','想人陪的',
        '眼睛大的','高贵的','傲娇的','心灵美的','爱撒娇的','细腻的','天真的','怕黑的','感性的','飘逸的','怕孤独的','忐忑的','高挑的','傻傻的','冷艳的','爱听歌的',
        '还单身的','怕孤单的','懵懂的'];
    $nicheng_wei=['嚓茶','皮皮虾','皮卡丘','马里奥','小霸王','凉面','便当','毛豆','花生','可乐','灯泡','哈密瓜','野狼','背包','眼神','缘分','雪碧','人生','牛排',
        '蚂蚁','飞鸟','灰狼','斑马','汉堡','悟空','巨人','绿茶','自行车','保温杯','大碗','墨镜','魔镜','煎饼','月饼','月亮','星星','芝麻','啤酒','玫瑰',
        '大叔','小伙','哈密瓜，数据线','太阳','树叶','芹菜','黄蜂','蜜粉','蜜蜂','信封','西装','外套','裙子','大象','猫咪','母鸡','路灯','蓝天','白云',
        '星月','彩虹','微笑','摩托','板栗','高山','大地','大树','电灯胆','砖头','楼房','水池','鸡翅','蜻蜓','红牛','咖啡','机器猫','枕头','大船','诺言',
        '钢笔','刺猬','天空','飞机','大炮','冬天','洋葱','春天','夏天','秋天','冬日','航空','毛衣','豌豆','黑米','玉米','眼睛','老鼠','白羊','帅哥','美女',
        '季节','鲜花','服饰','裙子','白开水','秀发','大山','火车','汽车','歌曲','舞蹈','老师','导师','方盒','大米','麦片','水杯','水壶','手套','鞋子','自行车',
        '鼠标','手机','电脑','书本','奇迹','身影','香烟','夕阳','台灯','宝贝','未来','皮带','钥匙','心锁','故事','花瓣','滑板','画笔','画板','学姐','店员',
        '电源','饼干','宝马','过客','大白','时光','石头','钻石','河马','犀牛','西牛','绿草','抽屉','柜子','往事','寒风','路人','橘子','耳机','鸵鸟','朋友',
        '苗条','铅笔','钢笔','硬币','热狗','大侠','御姐','萝莉','毛巾','期待','盼望','白昼','黑夜','大门','黑裤','钢铁侠','哑铃','板凳','枫叶','荷花','乌龟',
        '仙人掌','衬衫','大神','草丛','早晨','心情','茉莉','流沙','蜗牛','战斗机','冥王星','猎豹','棒球','篮球','乐曲','电话','网络','世界','中心','鱼','鸡','狗',
        '老虎','鸭子','雨','羽毛','翅膀','外套','火','丝袜','书包','钢笔','冷风','八宝粥','烤鸡','大雁','音响','招牌','胡萝卜','冰棍','帽子','菠萝','蛋挞','香水',
        '泥猴桃','吐司','溪流','黄豆','樱桃','小鸽子','小蝴蝶','爆米花','花卷','小鸭子','小海豚','日记本','小熊猫','小懒猪','小懒虫','荔枝','镜子','曲奇','金针菇',
        '小松鼠','小虾米','酒窝','紫菜','金鱼','柚子','果汁','百褶裙','项链','帆布鞋','火龙果','奇异果','煎蛋','唇彩','小土豆','高跟鞋','戒指','雪糕','睫毛','铃铛',
        '手链','香氛','红酒','月光','酸奶','银耳汤','咖啡豆','小蜜蜂','小蚂蚁','蜡烛','棉花糖','向日葵','水蜜桃','小蝴蝶','小刺猬','小丸子','指甲油','康乃馨','糖豆',
        '薯片','口红','超短裙','乌冬面','冰淇淋','棒棒糖','长颈鹿','豆芽','发箍','发卡','发夹','发带','铃铛','小馒头','小笼包','小甜瓜','冬瓜','香菇','小兔子',
        '含羞草','短靴','睫毛膏','小蘑菇','跳跳糖','小白菜','草莓','柠檬','月饼','百合','纸鹤','小天鹅','云朵','芒果','面包','海燕','小猫咪','龙猫','唇膏','鞋垫',
        '羊','黑猫','白猫','万宝路','金毛','山水','音响','纸飞机','烧鹅'];
    /**
     * 百家姓
     */
    $arrXing=['赵','钱','孙','李','周','吴','郑','王','冯','陈','褚','卫','蒋','沈','韩','杨','朱','秦','尤','许','何','吕','施','张','孔','曹','严','华','金','魏','陶','姜','戚','谢','邹',
        '喻','柏','水','窦','章','云','苏','潘','葛','奚','范','彭','郎','鲁','韦','昌','马','苗','凤','花','方','任','袁','柳','鲍','史','唐','费','薛','雷','贺','倪','汤','滕','殷','罗',
        '毕','郝','安','常','傅','卞','齐','元','顾','孟','平','黄','穆','萧','尹','姚','邵','湛','汪','祁','毛','狄','米','伏','成','戴','谈','宋','茅','庞','熊','纪','舒','屈','项','祝',
        '董','梁','杜','阮','蓝','闵','季','贾','路','娄','江','童','颜','郭','梅','盛','林','钟','徐','邱','骆','高','夏','蔡','田','樊','胡','凌','霍','虞','万','支','柯','管','卢','莫',
        '柯','房','裘','缪','解','应','宗','丁','宣','邓','单','杭','洪','包','诸','左','石','崔','吉','龚','程','嵇','邢','裴','陆','荣','翁','荀','于','惠','甄','曲','封','储','仲','伊',
        '宁','仇','甘','武','符','刘','景','詹','龙','叶','幸','司','黎','溥','印','怀','蒲','邰','从','索','赖','卓','屠','池','乔','胥','闻','莘','党','翟','谭','贡','劳','逄','姬','申',
        '扶','堵','冉','宰','雍','桑','寿','通','燕','浦','尚','农','温','别','庄','晏','柴','瞿','阎','连','习','容','向','古','易','廖','庾','终','步','都','耿','满','弘','匡','国','文',
        '寇','广','禄','阙','东','欧','利','师','巩','聂','关','荆','司马','上官','欧阳','夏侯','诸葛','闻人','东方','赫连','皇甫','尉迟','公羊','澹台','公冶','宗政','濮阳','淳于','单于','太叔',
        '申屠','公孙','仲孙','轩辕','令狐','徐离','宇文','长孙','慕容','司徒','司空','皮'];
    /**
     * 名
     */
    $arrMing=['伟','刚','勇','毅','俊','峰','强','军','平','保','东','文','辉','力','明','永','健','世','广','志','义','兴','良','海','山','仁','波','宁','贵','福','生','龙','元','全'
        ,'国','胜','学','祥','才','发','武','新','利','清','飞','彬','富','顺','信','子','杰','涛','昌','成','康','星','光','天','达','安','岩','中','茂','进','林','有','坚','和','彪','博','诚'
        ,'先','敬','震','振','壮','会','思','群','豪','心','邦','承','乐','绍','功','松','善','厚','庆','磊','民','友','裕','河','哲','江','超','浩','亮','政','谦','亨','奇','固','之','轮','翰'
        ,'朗','伯','宏','言','若','鸣','朋','斌','梁','栋','维','启','克','伦','翔','旭','鹏','泽','晨','辰','士','以','建','家','致','树','炎','德','行','时','泰','盛','雄','琛','钧','冠','策'
        ,'腾','楠','榕','风','航','弘','秀','娟','英','华','慧','巧','美','娜','静','淑','惠','珠','翠','雅','芝','玉','萍','红','娥','玲','芬','芳','燕','彩','春','菊','兰','凤','洁','梅','琳'
        ,'素','云','莲','真','环','雪','荣','爱','妹','霞','香','月','莺','媛','艳','瑞','凡','佳','嘉','琼','勤','珍','贞','莉','桂','娣','叶','璧','璐','娅','琦','晶','妍','茜','秋','珊','莎'
        ,'锦','黛','青','倩','婷','姣','婉','娴','瑾','颖','露','瑶','怡','婵','雁','蓓','纨','仪','荷','丹','蓉','眉','君','琴','蕊','薇','菁','梦','岚','苑','婕','馨','瑗','琰','韵','融','园'
        ,'艺','咏','卿','聪','澜','纯','毓','悦','昭','冰','爽','琬','茗','羽','希','欣','飘','育','滢','馥','筠','柔','竹','霭','凝','晓','欢','霄','枫','芸','菲','寒','伊','亚','宜','可','姬'
        ,'舒','影','荔','枝','丽','阳','妮','宝','贝','初','程','梵','罡','恒','鸿','桦','骅','剑','娇','纪','宽','苛','灵','玛','媚','琪','晴','容','睿','烁','堂','唯','威','韦','雯','苇','萱'
        ,'阅','彦','宇','雨','洋','忠','宗','曼','紫','逸','贤','蝶','菡','绿','蓝','儿','翠','烟'];
    $nicheng='';
    switch ($type){
        case 1:
            $tou_num=rand(0,count($nicheng_tou)-1);
            $wei_num=rand(0,count($nicheng_wei)-1);
            $nicheng=$nicheng_tou[$tou_num].$nicheng_wei[$wei_num];
            break;
        case 2:
            $nicheng=$arrXing[mt_rand(0,count($arrXing)-1)];
            for($i=1;$i<=3;$i++)
            {
                $nicheng .=(mt_rand(0,1)?$arrMing[mt_rand(0,count($arrMing)-1)]:$arrMing[mt_rand(0,count($arrMing)-1)]);
            }
            break;
    }
    return $nicheng;
}

//写日志
/**
 * @param $content
 * @param $log_name
 * @param $timeCell 日志文件时间间隔
 * @return true
 */
function writeLog($content='',$log_name='',$timeCell='day'){
    if(empty($content)){
        return true;
    }
    if(empty($log_name)){
        $log_name = date('Y-m-d').'log.log';
    }else{
        if($timeCell == 'day')
            $log_name = $log_name.date('d').'.log';
        elseif($timeCell == 'month')
            $log_name = $log_name.date('m').'.log';
    }
    $log_path = ROOT_PATH.'runtime/log/'.date('Ym').'/';
    if(!file_exists($log_path)){
        mk_dir($log_path);
    }
    file_put_contents($log_path.$log_name,date('Y-m-d H:i:s').'::'.$content."\r\n",FILE_APPEND);
    return true;
}

/**
 * amr转mp3
 * @param $amrfile  /root/demo.amr
 * @return void
 * 依赖ffmpeg
 * sudo apt-get update
 * sudo apt-get install ffmpeg
 */
function amr2mp3($amrfile)
{
    $file = str_replace('.amr','',$amrfile);
    $mp3file = $file.'.mp3';
    if (file_exists($mp3file) == true) {
        return $mp3file;
    } else {
        $params = "-ab 256 -ar 16000 -ac 1 -vol 200";//16000 高质量
        $command = "ffmpeg -i $amrfile $params $mp3file";// /usr/local/bin/ffmpeg
        system($command, $error);
        exec($command, $output, $result);
        if($result == 0){
            return $mp3file;
        }
        return '';
    }
}

/**
 * 短连接转为长链接
 * @param $shortUrl  需解析的短连接
 * @return string
 */
function shortUrlToLongUrl($shortUrl) {

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $shortUrl);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:70.0) Gecko/20100101 Firefox/70.0');

    curl_setopt($curl, CURLOPT_HEADER, true);

    curl_setopt($curl, CURLOPT_NOBODY, false);

    curl_setopt($curl, CURLOPT_TIMEOUT, 15);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);

    curl_setopt($curl, CURLOPT_ENCODING, 'gzip');

    $data = curl_exec($curl);

    $curlInfo = curl_getinfo($curl);

    curl_close($curl);

    if($curlInfo['http_code'] == 301 || $curlInfo['http_code'] == 302) {
        return $curlInfo['redirect_url'];
    }
    return '';
}
/**
 * 获取链接中的参数
 * @param $url  需要获取参数的链接（&链接） 
 * @return array
 */
function getPathParams($url='',$param=''){
   
    $url_content = parse_url($url);
    if($url==''){
        $url_content['query'] =  $param;
    }
    $queryData = explode('&', $url_content['query']);
    $qqData  = [];
    foreach ($queryData as $k => $v) {
        //将参数再次分割
        $str = explode('=',$v);
        //参数赋值
        $qqData[$str[0]] = $str[1];
    }
    return $qqData;
}

/**
 * 获取链接中小程序路径
 * @param $url
 * @param $trim 是否去除前面斜线
 * @return string
 */
if (!function_exists('getWxAppPath')) {
    function getWxAppPath($url,$trim = true){
        $parsedUrl = parse_url($url);
        $path = '';
        $query = '';
        if (isset($parsedUrl['fragment'])) {
            $fragment = $parsedUrl['fragment'];
            // 是否包含参数
            if (strpos($fragment, '?') !== false) {
                $parts = explode('?',$fragment);
                $path = $parts[0];
                if (count($parts) > 1) {
                    $query = '?' .$parts[1];
                }
            } else {
                $path = $fragment;
            }
        }
        if ($trim) {
            $path = ltrim($path, '/');
        }
        if ($query) {
            $path .= $query;
        }
        return $path;
    }
}
//隐藏部分姓名
if (!function_exists('hideMiddleName')) {
    function hideMiddleName($name) {
        // 检查姓名是否为空
        if (empty($name)) {
            return $name;
        }

        // 获取姓名的长度
        $length = mb_strlen($name);

        // 如果姓名只有一个字，直接返回
        if ($length <= 1) {
            return $name;
        }

        // 如果姓名有两个字，只显示第一个字
        if ($length == 2) {
            return mb_substr($name, 0, 1) . '*';
        }

        // 如果姓名有三个字或以上，显示首尾字符，中间用星号代替
        return mb_substr($name, 0, 1) . str_repeat('*',$length - 2) . mb_substr($name, -1, 1);
    }
}
// 隐藏手机号中间四位
if (!function_exists('hidePhoneNumber')) {
    function hidePhoneNumber($phoneNumber)
    {
        return substr($phoneNumber, 0, 3) . '****' . substr($phoneNumber, -4);
    }
}
//判断是否含有中文
function haveChinese($str) {
    $pattern = '/[\x{4e00}-\x{9fa5}]/u'; // Unicode编码范围内的汉字
    return preg_match($pattern, $str);
}
//判断是否全是中文
function isAllChinese($str) {
    $pattern = '/^[\x{4e00}-\x{9fa5}]+$/u'; // Unicode编码范围内的汉字
    return preg_match($pattern, $str);
}
//过滤字符串的emoji 过滤表情
function removeEmoj($text)
{
    // 正则表达式匹配Emoji字符
    $regex = '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';

    // 使用正则表达式进行替换
    $text = preg_replace($regex, '', $text);

    return $text;
}

/**
 * 验证联系方式是否正常， 包含座机及手机号验证, 默认验证手机号
 * @param $tel string 待验证手机号
 * @param $type string|array 验证类型 指定多种验证格式传入数组，如[1,2]
 *                                  1 指定手机号验证
 *                                  2 指定座机验证
 *                                  3 400客服电话
 * @return false|int
 */
function checkTel($aid=0,$tel, $type=1)
{
	$patterns = [
        1 => "(^(86|(\+86))?1[3-9]\d{9}$)", //手机号
        2 => "(^(\d{3,4}-)?\d{7,8}$)", //座机
        3 => "(^400[16789]?-?\d{3}-?\d{4}$)", //400电话
    ];

    if(is_array($type)){
        $regx_arr = array_filter($patterns, function ($k)use($type){
            return in_array($k, $type);
        }, ARRAY_FILTER_USE_KEY);
    }else{
        if(!array_key_exists($type, $patterns)){
            //验证方式不存在
            return false;
        }
        $regx_arr = [$patterns[$type]];
    }
    if(empty($regx_arr)){
        return false;
    }
    $regx = "/".implode("|", $regx_arr)."/";
    return preg_match($regx, $tel);

}

//身份号验证
function checkIdCard($idCardNumber) {
    // 身份证号长度为 15 位或 18 位
    $pattern = '/^([1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$)/';
    if (!preg_match($pattern, $idCardNumber)) {
        return false;
    }

    // 15 位身份证号转换为 18 位
    if (strlen($idCardNumber) === 15) {
        return true;
    }

    // 计算校验位
    $weights = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
    $checkCodes = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
    $sum = 0;
    for ($i = 0; $i < 17; $i++) {
        $sum += $weights[$i] * (int) $idCardNumber[$i];
    }
    $checkCodeIndex = $sum % 11;

    // 验证校验位
    $lastChar = strtoupper($idCardNumber[17]);
    return $lastChar === $checkCodes[$checkCodeIndex];
}
//是否整除
function isMulInt($num, $div, $precision = 2)
{
  $base_mul = pow(10, $precision);
  return fmod($num*$base_mul, $div*$base_mul) == 0;
}

// 二进制数据流转图片
function binaryDEcodeImage($aid,$binaryData) {
    $name = date('Ym/d_His').rand(1000,9999).'.jpg';
    $path = 'upload/'.$aid.'/';
    if (!file_exists(dirname(ROOT_PATH.$path.$name))) {
        mk_dir(dirname(ROOT_PATH.$path.$name));
    }
    $result = file_put_contents(ROOT_PATH.$path.$name, $binaryData);
    if ($result !== false) {
        return  PRE_URL.'/'.$path.$name;
    } else {
        echo "";
    }
}

// 文本utf-8转换
function strToUtf8 ($str = '') {
	$current_encode = mb_detect_encoding($str, array("ASCII","GB2312","GBK",'BIG5','UTF-8'));
	if($current_encode != 'UTF-8'){
		$encoded_str = mb_convert_encoding($str, 'UTF-8', $current_encode);
	}else{
		$encoded_str =$str;
	}
	return $encoded_str;
}

//计算两时间相差几天/几小时/几分钟
function getTimeDiff($time1 = '', $time2 = '',$is_hour = 1, $is_minutes = 1)
{
    $timediff = $time2 - $time1;
    if (empty($timediff) || $timediff <= 0) {
        return '';
    }
    $day = floor($timediff / (3600 * 24));
    $day = $day > 0 ? $day . '天' : '';
    if ($day) return $day;
    $hour = floor(($timediff % (3600 * 24)) / 3600);
    $hour = $hour > 0 ? $hour . '小时' : '';
    if ($hour) return $hour;
    if ($is_hour && $is_minutes) {
        $minutes = floor((($timediff % (3600 * 24)) % 3600) / 60);
        $minutes = $minutes > 0 ? $minutes . '分钟' : '1分钟';
        return $day . $hour . $minutes;
    }
    return $day;
}
//概率算法
//$proArr = array(10,20);10，20为概率，最终返回对应key,根据key获得对应中奖数据
if (!function_exists('get_rand')) {
    function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }
}

//获取核销码
if (!function_exists('gethexiaoqr')) {
    function gethexiaoqr($aid,$type,$hexiao_code){
        $hexiao_url= 'admin/hexiao/hexiao?type='.$type.'&co='.$hexiao_code;
        $hexiao_qr = m_url($hexiao_url,$aid);
        return $hexiao_qr;
    }
 }
 // 验证图片有效性
function validateImagePath($imagePath) {
    static $checkedPaths = [];
    
    // 如果已经检查过，直接返回缓存结果
    if (isset($checkedPaths[$imagePath])) {
        return $checkedPaths[$imagePath];
    }
    
    $isValid = false;
    
    try {
        // 检查远程图片
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            $context = stream_context_create([
                'http' => ['method' => 'HEAD', 'timeout' => 1]
            ]);
            $headers = @get_headers($imagePath, 1, $context);
            $isValid = $headers && strpos($headers[0], '200') !== false;
        } 
        // 检查本地图片
        else {
            $absolutePath = realpath($_SERVER['DOCUMENT_ROOT'] . ltrim($imagePath, '/'));
            $isValid = $absolutePath && file_exists($absolutePath);
        }
    } catch (Exception $e) {
        $isValid = false;
    }
    
    $checkedPaths[$imagePath] = $isValid;
    return $isValid;
}

if (!function_exists('getAreaCodeByName')) {
    /**
     * 根据名称查找编码（支持省、市、区）
     * @param string $name 省、市或区的名称
     * @param int $type 查找类型：1（省）、2（市）、3（区）、0（全部）
     * @return string 返回对应的编码，未找到时返回空字符串
     */
    function getAreaCodeByName($name, $type = 0) {
        $filePath = ROOT_PATH . 'static/area_wechat.json';
        if (!file_exists($filePath)) {
            return '';
        }

        $jsonData = file_get_contents($filePath);
        $data = json_decode($jsonData, true);

        $findCode = function ($data, $targetName, $targetType) use (&$findCode) {
            foreach ($data as $item) {
                if (($targetType === 0 || $item['type'] == $targetType) && $item['text'] === $targetName) {
                    return $item['value']; //返回编码
                }
                if (isset($item['sub_list']) && is_array($item['sub_list'])) {
                    $result = $findCode($item['sub_list'], $targetName, $targetType);
                    if ($result) {
                        return $result;
                    }
                }
            }
            return '';
        };

        return $findCode($data, $name, $type);
    }
}

//10进制数字转为8字节16进制
function dechexUpFormat($number)
{
    $binaryString = pack('J', $number); // 将数字打包为8字节（64位）
    $hexString = bin2hex($binaryString); // 将二进制字符串转换为十六进制字符串
    return strtoupper($hexString);
}

//生成设计的收款码
function getDesignQrcode($aid, $bid, $posterdata, $page, $textReplaceArr){
	set_time_limit(0);
	$posterdata  = json_decode($posterdata, true);
	$poster_bg   = $posterdata['poster_bg'];
	$poster_data = $posterdata['poster_data'];
	@ini_set('memory_limit', -1);

	if (strpos($poster_bg, 'http') === false) {
		$poster_bg = PRE_URL . $poster_bg;
	}
	$bg = imagecreatefromstring(request_get($poster_bg));
	if ($bg) {
		$bgwidth  = imagesx($bg);
		$bgheight = imagesy($bg);
		if ($bgheight / $bgwidth > 1.92) $bgheight = floor($bgwidth * 1.92);
		$target = imagecreatetruecolor($bgwidth, $bgheight);
		imagecopy($target, $bg, 0, 0, 0, 0, $bgwidth, $bgheight);
		imagedestroy($bg);
	} else {
		$bgwidth  = 680;
		$bgheight = 1080;
		$target   = imagecreatetruecolor(680, 1080);
		imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
	}
	$huansuan = $bgwidth / 340;
	$font     = ROOT_PATH . "static/fonts/msyh.ttf";
	foreach ($poster_data as $d) {
		$d['left']   = intval(str_replace('px', '', $d['left'])) * $huansuan;
		$d['top']    = intval(str_replace('px', '', $d['top'])) * $huansuan;
		$d['width']  = intval(str_replace('px', '', $d['width'])) * $huansuan;
		$d['height'] = intval(str_replace('px', '', $d['height'])) * $huansuan;
		$d['size']   = intval(str_replace('px', '', $d['size'])) * $huansuan / 2 * 1.5;
		if ($d['type'] == 'qrwx') {
			$data          = array();
			$data['page']  = ltrim($page, '/');
			$data['width'] = 430;//默认430，二维码的宽度，单位 px，最小 280px，最大 1280px
			$errmsg        = \app\common\Wechat::getQRCode($aid, 'wx', $data['page'], [], $bid, false);
			$res           = $errmsg['buffer'];//图片 Buffer
			if ($errmsg['status'] != 1) {
				if ($errmsg['errcode'] == 41030) {
					echojson(array('status' => 0, 'msg' => '小程序发布后才能生成分享海报'));
				} else {
					echojson(['status' => 0, 'msg' => $errmsg['msg'], 'rs' => $errmsg['rs'], 'data' => $data]);
				}
			} else {
				$img = imagecreatefromstring($res);
				imagecopyresampled($target, $img, $d['left'], $d['top'], 0, 0, $d['width'], $d['height'], imagesx($img), imagesy($img));
			}
		} else if ($d['type'] == 'qrcode') {
			$qrcode = createqrcode(PRE_URL . '/h5/' . $aid . '.html#' . $page);
			$img    = imagecreatefromstring(request_get($qrcode));
			imagecopyresampled($target, $img, $d['left'], $d['top'], 0, 0, $d['width'], $d['height'], imagesx($img), imagesy($img));
		} else if ($d['type'] == 'img') {
			if ($d['src'][0] == '/') $d['src'] = PRE_URL . $d['src'];
			$img = imagecreatefromstring(request_get($d['src']));
			if ($img)
				imagecopyresampled($target, $img, $d['left'], $d['top'], 0, 0, $d['width'], $d['height'], imagesx($img), imagesy($img));
		} else if ($d['type'] == 'text' || $d['type'] == 'business_name' || $d['type'] == 'score_radio' || $d['type'] == 'newscore_radio') {
			$d['content'] = str_replace(array_keys($textReplaceArr), array_values($textReplaceArr), $d['content']);
			$colors       = hex2rgb($d['color']);
			$color        = imagecolorallocate($target, $colors['red'], $colors['green'], $colors['blue']);
			if ($d['type'] == 'business_name') {
				//计算文字的宽度
				$textBox = imagettfbbox($d['size'], 0, $font, $d['content']);
				$textWidth = $textBox[2] - $textBox[0];
				//计算居中位置
				$d['left'] = ($bgwidth - $textWidth) / 2;
			}
			imagettftext($target, $d['size'], 0, $d['left'], $d['top'] + $d['size'], $color, $font, $d['content']);
		}
	}
	$url      = "/upload/{$aid}/" . date('Ym/d_His') . rand(1000, 9999) . '.jpg';
	$filepath = ROOT_PATH . ltrim($url, '/');
	mk_dir(dirname($filepath));
	imagejpeg($target, $filepath, 100);
	return PRE_URL . $url;
}

/**
 * 在二维码基础上添加背景图、调整背景图大小、添加文字并调整文字位置
 * @param string $qrcodeUrl 二维码图片路径
 * @param string $bgImage 背景图片路径（支持本地和远程地址）
 * @param array $options 配置选项，包括以下键值（可选）：
 *   - 'bgImageSize' => 背景图大小 [宽度, 高度]（可选）
 *   - 'outputImageSize' => 最终输出图片大小 [宽度, 高度]（可选）
 *   - 'textToAdd' => 要添加到图片上的文字（可选）
 *   - 'textOptions' => 文字选项，包括以下键值（可选）：
 *     - 'fontSize' => 字体大小（默认 20）
 *     - 'fontColor' => 字体颜色（默认黑色）
 *     - 'fontPath' => 字体文件路径（可选，支持 TrueType 字体）
 *     - 'textX' => 文字左上角 X 坐标（默认居中）
 *     - 'textY' => 文字左上角 Y 坐标（可选，默认在二维码下方）
 *   - 'qrCodeSize' => 二维码大小 [宽度, 高度]（可选）
 *   - 'qrCodePosition' => 二维码位置 [x, y]（可选，默认居中）
 * @return string 返回最终生成的图片 URL
 */
function createCustomQrcode($qrcodeUrl, $bgImage, $options = []) {
	// 设置脚本执行时间和内存限制
	set_time_limit(0);
	@ini_set('memory_limit', '-1');

	// 处理远程背景图地址
	if (strpos($bgImage, 'http') === false) {
		$bgImage = PRE_URL . $bgImage; // 本地地址加上前缀
	}

	// 读取背景图片
	$background = imagecreatefromstring(request_get($bgImage));
	if (!$background) {
		throw new \Exception("无法创建背景图资源：{$bgImage}");
	}

	// 调整背景图大小
	if (!empty($options['bgImageSize'])) {
		$background = imagescale($background, $options['bgImageSize'][0], $options['bgImageSize'][1]);
	}

	// 读取二维码图片
	$qrcodeImage = imagecreatefromstring(request_get($qrcodeUrl));
	if (!$qrcodeImage) {
		throw new \Exception("无法创建二维码图片资源：{$qrcodeUrl}");
	}

	// 调整二维码大小
	if (!empty($options['qrCodeSize'])) {
		$qrcodeImage = imagescale($qrcodeImage, $options['qrCodeSize'][0], $options['qrCodeSize'][1]);
	}

	// 获取背景图和二维码的尺寸
	$bgWidth  = imagesx($background);
	$bgHeight = imagesy($background);
	$qrWidth  = imagesx($qrcodeImage);
	$qrHeight = imagesy($qrcodeImage);

	// 计算二维码在背景图中的位置（默认居中）
	$dstX = isset($options['qrCodePosition'][0]) ? $options['qrCodePosition'][0] : ($bgWidth - $qrWidth) / 2;
	$dstY = isset($options['qrCodePosition'][1]) ? $options['qrCodePosition'][1] : ($bgHeight - $qrHeight) / 2;

	// 合并二维码和背景图片
	imagecopy($background, $qrcodeImage, $dstX, $dstY, 0, 0, $qrWidth, $qrHeight);

	// 释放二维码图片资源
	imagedestroy($qrcodeImage);

	// 调整最终输出图片的大小
	if (!empty($options['outputImageSize'])) {
		$background = imagescale($background, $options['outputImageSize'][0], $options['outputImageSize'][1]);
	}

	// 添加文字
	if (!empty($options['textToAdd'])) {
		$textOptions = $options['textOptions'] ?? [];
		$fontSize    = $textOptions['fontSize'] ?? 20;
		$fontColor   = $textOptions['fontColor'] ?? imagecolorallocate($background, 0, 0, 0);
		$fontPath    = $textOptions['fontPath'] ?? '';

		// 计算位置
		$text    = $options['textToAdd'];
		$padding = 20; // 固定间距

		if ($fontPath && file_exists($fontPath)) {
			// 获取文字的实际尺寸
			$textBox   = imagettfbbox($fontSize, 0, $fontPath, $text);
			$textWidth = $textBox[2] - $textBox[0];

			// 水平居中，垂直在二维码下方20px
			$textX = ($bgWidth - $textWidth) / 2;
			$textY = $dstY + $qrHeight + $padding + $fontSize;

			imagettftext($background, $fontSize, 0, (int)$textX, (int)$textY, $fontColor, $fontPath, $text);
		} else {
			// 默认字体处理
			$textX = ($bgWidth - $fontSize * strlen($text) * 0.6) / 2;
			$textY = $dstY + $qrHeight + $padding;
			imagestring($background, $fontSize, (int)$textX, (int)$textY, $text, $fontColor);
		}
	}

	$url  = '/upload/' . aid . '/qrcode/' . date('Ym/d_His') . rand(10000000, 99999999) . '.jpg';
	$file = ROOT_PATH . $url;

	if (!is_dir(dirname($file))) {
		@mkdir(dirname($file), 0777, true);
	}
	imagejpeg($background, $file, 90);

	// 删除原文件
	$localPath = str_replace(PRE_URL, '', $qrcodeUrl);
	if (file_exists(ROOT_PATH . $localPath)) {
		@unlink(ROOT_PATH . $localPath);
	}

	// 释放背景图资源
	imagedestroy($background);
	return PRE_URL . $url;
}
// 将数字（如 1, 2, 3 ……）转换为中文数字（如 一, 二, 三 ……）
function numberToChinese($number) {
    $chineseNumbers = [
        '零', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十'
    ];
    
    if ($number >= 0 && $number <= 10) {
        return $chineseNumbers[$number];
    } else {
        // 可以扩展更大的数字，这里仅处理 0-10
        return $number;
    }
}

function isAltCodeString($text) {
    // 使用正则表达式匹配整个字符串是否由Alt+4位数字组成
    $pattern = '/^(Alt\d{4})+$/';
    return preg_match($pattern, $text) === 1;
}
function convertAltCodes($text) {
    // 使用正则表达式匹配所有Alt后面的数字
    $pattern = '/Alt(\d+)/';
    preg_match_all($pattern, $text, $matches);

    $result = '';
    if (!empty($matches[1])) {
        foreach ($matches[1] as $code) {
            // 将匹配到的数字转换为整数
            $codePoint = intval($code);
            // 将数字转换为对应的字符
            $result .= chr($codePoint);
        }
    }

    return $result;
}