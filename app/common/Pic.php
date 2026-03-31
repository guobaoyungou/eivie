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
use app\common\File;
class Pic
{
	/**
	 * 将图片文件转换为 WebP 格式
	 * 支持 JPEG、PNG、GIF、BMP 来源格式，保留透明通道
	 * 
	 * @param string $filePath 本地图片文件绝对路径
	 * @param int $quality 输出质量（1-100），默认82
	 * @return string|false 成功返回新的 .webp 文件路径，失败返回 false
	 */
	public static function convertToWebp($filePath, $quality = 82)
	{
		// 检测 PHP 环境是否支持 webp 输出
		if (!function_exists('imagewebp')) {
			\think\facade\Log::warning('convertToWebp: PHP环境不支持imagewebp函数，跳过转换');
			return false;
		}

		if (!file_exists($filePath) || filesize($filePath) === 0) {
			\think\facade\Log::warning('convertToWebp: 文件不存在或为空', ['path' => $filePath]);
			return false;
		}

		// 获取图片信息
		$imageInfo = @getimagesize($filePath);
		if (!$imageInfo) {
			\think\facade\Log::warning('convertToWebp: 无法读取图片信息', ['path' => $filePath]);
			return false;
		}

		$mimeType = $imageInfo['mime'];

		// 已经是 webp 格式，无需转换
		if ($mimeType === 'image/webp') {
			return $filePath;
		}

		// 根据来源格式创建图像资源
		$srcImage = null;
		switch ($mimeType) {
			case 'image/jpeg':
				$srcImage = @imagecreatefromjpeg($filePath);
				break;
			case 'image/png':
				$srcImage = @imagecreatefrompng($filePath);
				break;
			case 'image/gif':
				$srcImage = @imagecreatefromgif($filePath);
				break;
			case 'image/bmp':
			case 'image/x-ms-bmp':
				if (function_exists('imagecreatefrombmp')) {
					$srcImage = @imagecreatefrombmp($filePath);
				}
				break;
			default:
				\think\facade\Log::info('convertToWebp: 不支持的图片格式', ['mime' => $mimeType]);
				return false;
		}

		if (!$srcImage) {
			\think\facade\Log::warning('convertToWebp: 无法创建图像资源', ['mime' => $mimeType, 'path' => $filePath]);
			return false;
		}

		// 处理透明通道（PNG/GIF 含透明时）
		if (in_array($mimeType, ['image/png', 'image/gif'])) {
			imagealphablending($srcImage, true);
			imagesavealpha($srcImage, true);
		}

		// 生成 webp 文件路径：替换原扩展名为 .webp
		$pathInfo = pathinfo($filePath);
		$webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';

		// 输出为 webp
		$result = @imagewebp($srcImage, $webpPath, $quality);
		imagedestroy($srcImage);

		if (!$result || !file_exists($webpPath) || filesize($webpPath) === 0) {
			\think\facade\Log::warning('convertToWebp: WebP输出失败', ['path' => $filePath]);
			@unlink($webpPath);
			return false;
		}

		// 转换成功后删除原文件（仅在路径不同时）
		if ($webpPath !== $filePath) {
			@unlink($filePath);
		}

		\think\facade\Log::info('convertToWebp: 转换成功', [
			'original' => basename($filePath),
			'webp' => basename($webpPath),
			'originalSize' => filesize($filePath) ?: 'deleted',
			'webpSize' => filesize($webpPath)
		]);

		return $webpPath;
	}

	//远程图片保存到本地
    /**
     * @param $picurl 图片路径 http://xxx.com/1.png
     * @param $aid
     * @param $store 是否保存数据表
     * @param $fixed_dir 指定目录
     * @param $filename 指定文件名
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
	public static function tolocal($picurl,$aid='',$store=false,$fixed_dir='',$filename=''){
		$PRE_URL = PRE_URL;//本地域名
        if($aid == '') $aid = aid ?? 0;
		if(strpos($picurl,$PRE_URL) !== 0){ //非本地
			if(!$picurl) return '';
			if($store){
				$info = db('pictolocal')->where('pic',$picurl)->find();
				if($info){
					return $info['url'];
				}
			}

			$picurlArr = explode('.',$picurl);
			$ext = end($picurlArr);
			if(!$ext || strlen($ext) > 6) $ext = 'jpg';
			if(!in_array($ext,explode(',',config('app.upload_type')))){
				return '';
			}

            //todo 判断占用空间

			if($fixed_dir){
                $dir = $fixed_dir;
            }else{
                $dir = "upload/{$aid}/".date('Ym');
            }
			if(!is_dir(ROOT_PATH.$dir)) mk_dir(ROOT_PATH.$dir);
			if(!$filename){
				$filename = date('d_His').rand(1000,9999).'.'.$ext;
			}
			$mediapath = $dir.'/'.$filename;
			$piccontent = request_get($picurl);
			file_put_contents(ROOT_PATH.$mediapath,$piccontent);
			$url = $PRE_URL.'/'.$mediapath;
			if($store){
				db('pictolocal')->insert(['aid'=>$aid,'pic'=>$picurl,'url'=>$url,'createtime'=>time()]);
			}
		}else{
            //本地文件 不指定目录则直接返回原文件
		    if($fixed_dir){
		        $arr = parse_url($picurl);
		        $path = $arr['path'];
				if(!$filename){
				    $file_name = basename($picurl);
				}else{
					$file_name = $filename;
				}
                $mediapath = $fixed_dir.'/'.$file_name;
                File::all_copy(ROOT_PATH.$path,ROOT_PATH.$mediapath);
                $url = $PRE_URL.'/'.$mediapath;
            }else{
                $url = $picurl;
            }
		}
		return $url;
	}
	//上传图片，未开启oss上传本地
	public static function uploadoss($picurl,$store=false,$transcode=true){
		$oldpicurl = $picurl;
		if(!$oldpicurl) return '';
		if($store){
			$info = db('pictolocal')->where('pic',$oldpicurl)->find();
			if($info){
				return $info['url'];
			}
		}

		$PRE_URL = PRE_URL;//本地域名
        if(defined('aid') && aid > 0){
			$remoteset = db('admin')->where('id',aid)->value('remote');
			$remoteset = json_decode($remoteset,true);
			if(!$remoteset || $remoteset['type']==0){
				$remoteset = db('sysset')->where('name','remote')->value('value');
				$remoteset = json_decode($remoteset,true);
			}else{
                //云存储上传后删除服务器文件
                $sysset =  db('sysset')->where('name','remote')->value('value');
                $sysset = json_decode($sysset,true);
                $remoteset['delete_local'] = $sysset['delete_local']??0;
            }

            }else{
			$remoteset = db('sysset')->where('name','remote')->value('value');
			$remoteset = json_decode($remoteset,true);
		}
		//dump($remoteset);
		if($remoteset['type']==2){ //阿里云
			$alyunossConf = $remoteset['alioss'];
            if($alyunossConf['key'] == '' || $alyunossConf['secret'] == '' || $alyunossConf['bucket'] == '' ||  $alyunossConf['ossurl'] == ''){
                return false;
            }
			if(strpos($picurl,$alyunossConf['url']) === 0) return $picurl;
			$picurl = \app\common\Pic::tolocal($picurl);
			$accessKeyId = $alyunossConf['key'];
			$accessKeySecret = $alyunossConf['secret'];
			$endpoint = 'http://'.$alyunossConf['ossurl'];
			$bucket= $alyunossConf['bucket'];
			// 文件名称
			$object = ltrim(str_replace($PRE_URL,'',$picurl),'/');
			// <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt
			$filePath = ROOT_PATH.$object;
			if(!file_exists($filePath)) return '';
			try{
				$ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
				$ossClient->uploadFile($bucket, $object, $filePath);
				if($remoteset['delete_local'] == 1) @unlink($filePath);//云存储上传后删除服务器文件
				$picurl = $alyunossConf['url'].'/'.$object;
				if($store){
					db('pictolocal')->insert(['pic'=>$oldpicurl,'url'=>$picurl,'createtime'=>time()]);
				}
				return $picurl;
			} catch(OssException $e) {
                @unlink($filePath);
				return $picurl;
				//return $e->getMessage();
			}
		}
        elseif($remoteset['type']==3){ //七牛云
			$qiniuConf = $remoteset['qiniu'];
            if($qiniuConf['accesskey'] == '' || $qiniuConf['secretkey'] == '' || $qiniuConf['bucket'] == '' || $qiniuConf['url'] == ''){
                return false;
            }
			if(strpos($picurl,$qiniuConf['url']) === 0) return $picurl;
			$picurl = \app\common\Pic::tolocal($picurl);

            $object = ltrim(str_replace($PRE_URL,'',$picurl),'/');
            $filePath = ROOT_PATH.$object;
            if(!file_exists($filePath)) return '';

			$auth = new \Qiniu\Auth($qiniuConf['accesskey'], $qiniuConf['secretkey']);
            $policy = null;
            if($qiniuConf['transcode'] == 'webp' && $transcode){
                $picurlPath = pathinfo($object);
                $objectWebp = $picurlPath['dirname'].'/'.$picurlPath['filename'].'.webp';
                $policy = array(
                    'persistentOps' => "imageMogr2/format/webp|saveas/" . \Qiniu\base64_urlSafeEncode($qiniuConf['bucket'] .':'.$objectWebp)
                );
            }
			$token = $auth->uploadToken($qiniuConf['bucket'],null,3600,$policy);
			$uploadMgr = new \Qiniu\Storage\UploadManager();
			// 调用 UploadManager 的 putFile 方法进行文件的上传。
			list($ret, $err) = $uploadMgr->putFile($token, $object, $filePath);
			//echo "\n====> putFile result: \n";
			if ($err !== null) {
				@unlink($filePath);
                \think\facade\Log::error([
                    'file line' => __FILE__.__LINE__,
                    '$err'=>$err,
                    '$ret'=>$ret
                ]);
                @unlink($filePath);
                return false;
//				return $picurl;
				//var_dump($err);
			} else {
				if($remoteset['delete_local'] == 1) @unlink($filePath);//云存储上传后删除服务器文件
				$picurl = $qiniuConf['url'].'/'.$object;

                if($qiniuConf['transcode'] == 'webp' && $transcode){
//                    $config = new \Qiniu\Config();
//                    $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
//                    $err = $bucketManager->delete($qiniuConf['bucket'],$object);

                    $picurl = $qiniuConf['url'].'/'.$objectWebp;
                    request_get($picurl.'?1');
                }
				if($store){
					db('pictolocal')->insert(['pic'=>$oldpicurl,'url'=>$picurl,'createtime'=>time()]);
				}
				return $picurl;
			}
		}
        elseif($remoteset['type']==4){ //腾讯云
			$cosConf = $remoteset['cos'];
            if($cosConf['appid'] =='' || $cosConf['secretid'] =='' || $cosConf['secretkey'] =='' || $cosConf['bucket'] =='' || $cosConf['url'] == ''){
                return false;
            }
			if(strpos($picurl,$cosConf['url']) === 0) return $picurl;
			$picurl = \app\common\Pic::tolocal($picurl);
			$secretId = $cosConf['secretid']; //"云 API 密钥 SecretId";
			$secretKey = $cosConf['secretkey']; //"云 API 密钥 SecretKey";
			$region = $cosConf['local']; //设置一个默认的存储桶地域
			$bucket = str_replace("-".$cosConf['appid'],'',$cosConf['bucket'])."-".$cosConf['appid'];
			$object = ltrim(str_replace($PRE_URL,'',$picurl),'/');
			$filePath = ROOT_PATH.$object;
			if(!file_exists($filePath)) return '';
			try {
				$cosClient = new \Qcloud\Cos\Client(array(
					'region' => $region,
					//'schema' => 'https', //协议头部，默认为http
					'credentials'=> array('secretId'  => $secretId ,'secretKey' => $secretKey)
				));
				$result = $cosClient->upload($bucket,$object,fopen($filePath, "rb"));
	 
				if($remoteset['delete_local'] == 1) @unlink($filePath);		//云存储上传后删除服务器文件
				 
				$picurl = $cosConf['url'].'/'.$object;
				if($store){
					db('pictolocal')->insert(['pic'=>$oldpicurl,'url'=>$picurl,'createtime'=>time()]);
				}
				return $picurl;
			} catch (\Exception $e){
                @unlink($filePath);
				echojson(['status'=>0,'msg'=>$e->getMessage()]);
				return $picurl;
			}
		}
        else{
			if(defined('aid') && aid > 0){
				$aid = aid;
			}else{
				$aid = 0;
			}
			$picurl = \app\common\Pic::tolocal($picurl,$aid);
			if($store){
				db('pictolocal')->insert(['pic'=>$oldpicurl,'url'=>$picurl,'createtime'=>time()]);
			}
			return $picurl;
		}
	}
	//删除图片
	public static function deletepic($picurl){
        \think\facade\Log::write('deletepic:'.$picurl);
        $PRE_URL = PRE_URL;//本地域名
		if(defined('aid') && aid > 0){
			$remoteset = db('admin')->where('id',aid)->value('remote');
			$remoteset = json_decode($remoteset,true);
			if(!$remoteset || $remoteset['type']==0){
				$remoteset = db('sysset')->where('name','remote')->value('value');
				$remoteset = json_decode($remoteset,true);
			}
            }else{
			$remoteset = db('sysset')->where('name','remote')->value('value');
			$remoteset = json_decode($remoteset,true);
		}
		if(strpos($picurl,$PRE_URL) !== 0 && !getcustom('retain_osspic')){ //非本地
			if($remoteset['type']==2){ //阿里云
				$alyunossConf = $remoteset['alioss'];
				if(strpos($picurl,$alyunossConf['url']) !== 0) return ['status'=>0,'msg'=>''];
				$accessKeyId = $alyunossConf['key'];
				$accessKeySecret = $alyunossConf['secret'];
				$endpoint = 'http://'.$alyunossConf['ossurl'];
				$bucket= $alyunossConf['bucket'];
				// 文件名称
				$object = ltrim(str_replace($alyunossConf['url'],'',$picurl),'/');
				try{
					$ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
					$ossClient->deleteObject($bucket, $object);
					return ['status'=>1,'msg'=>''];
				} catch(OssException $e) {
					return ['status'=>0,'msg'=>''];
				}
			}elseif($remoteset['type']==3){ //七牛云
				$qiniuConf = $remoteset['qiniu'];
				if(strpos($picurl,$qiniuConf['url']) !== 0) return ['status'=>0,'msg'=>''];
				$auth = new \Qiniu\Auth($qiniuConf['accesskey'], $qiniuConf['secretkey']);
				$object = ltrim(str_replace($qiniuConf['url'],'',$picurl),'/');
				$config = new \Qiniu\Config();
				$bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
				$err = $bucketManager->delete($qiniuConf['bucket'],$object);
				return ['status'=>1,'msg'=>''];
			}elseif($remoteset['type']==4){ //腾讯云
				$cosConf = $remoteset['cos'];
				if(strpos($picurl,$cosConf['url']) !== 0) return ['status'=>0,'msg'=>''];
				$secretId = $cosConf['secretid'];
				$secretKey = $cosConf['secretkey'];
				$region = $cosConf['local'];
				$bucket = str_replace("-".$cosConf['appid'],'',$cosConf['bucket'])."-".$cosConf['appid'];
				$object = ltrim(str_replace($cosConf['url'],'',$picurl),'/');
				try {
					$cosClient = new \Qcloud\Cos\Client(array(
						'region' => $region,
						//'schema' => 'https', //协议头部，默认为http
						'credentials'=> array('secretId'  => $secretId ,'secretKey' => $secretKey)
					));
					$result = $cosClient->deleteObject(['Bucket'=>$bucket,'Key'=>$object]);
					return ['status'=>1,'msg'=>''];
				} catch (\Exception $e){
					echojson(['status'=>0,'msg'=>$e->getMessage()]);
					return $picurl;
				}
			}
		}else{
			$filePath = ROOT_PATH.ltrim(str_replace($PRE_URL,'',$picurl),'/');
			@unlink($filePath);
			return ['status'=>1,'msg'=>''];
		}
	}
	
	/**
	 * 删除OSS文件（别名方法，与deletepic功能相同）
	 * @param string $picurl 图片URL
	 * @return bool 删除成功返回true，失败返回false
	 */
	public static function deleteoss($picurl){
		$result = self::deletepic($picurl);
		return isset($result['status']) && $result['status'] == 1;
	}
}