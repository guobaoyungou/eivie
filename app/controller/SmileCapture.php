<?php
/**
 * 笑脸抓拍独立URL入口控制器
 * 
 * 继承Base控制器，自行管理认证（不强制重定向登录页面）
 * 支持两种访问方式：
 * 1. 独立URL直接访问（未登录显示毛玻璃登录弹窗）
 * 2. 后台管理框架内跳转（复用ADMIN_LOGIN session）
 * 
 * @package app\controller
 * @date 2026-03-19
 */

namespace app\controller;

use think\facade\Db;
use think\facade\Log;
use think\facade\View;

class SmileCapture extends Base
{
    protected $is_logged_in = false;
    protected $login_uid = 0;
    protected $login_aid = 0;
    protected $login_bid = 0;

    public function initialize()
    {
        // 不调用parent::initialize()，避免Base中aid必填检查导致error
        // 手动处理初始化逻辑
        $this->request = request();
        error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT ^ E_WARNING);

        // 从session中读取登录状态
        $adminLogin = session('ADMIN_LOGIN');
        $this->login_aid = session('ADMIN_AID') ?: 0;
        $this->login_bid = session('ADMIN_BID') ?: 0;
        $this->login_uid = session('ADMIN_UID') ?: 0;

        if ($adminLogin && $this->login_aid > 0) {
            $this->is_logged_in = true;
            $this->aid = $this->login_aid;
            $this->bid = $this->login_bid;
        } else {
            $this->is_logged_in = false;
            // 从URL参数中获取aid用于生成登录二维码
            $this->aid = input('param.aid/d', 0);
            $this->bid = input('param.bid/d', 0);
        }

        // 传递常用变量到视图
        View::assign('aid', $this->aid ?: 0);
        View::assign('bid', $this->bid ?: 0);
        View::assign('is_logged_in', $this->is_logged_in);
    }

    /**
     * 独立URL入口 - 渲染笑脸抓拍页面
     * 
     * 路径: SmileCapture/index
     * 方法: GET
     * 参数: aid(必填), bid(可选), mdid(可选)
     */
    public function index()
    {
        $aid = $this->aid;
        if (!$aid) {
            $aid = input('param.aid/d', 0);
        }

        if (!$aid) {
            return '<h3 style="text-align:center;margin-top:100px;color:#999;">参数错误：缺少aid参数</h3>';
        }

        $bid = $this->bid;
        if (!$bid) {
            $bid = input('param.bid/d', 0);
        }

        $mdid = input('param.mdid/d', 0);

        // 注入视图变量
        View::assign('page_aid', $aid);
        View::assign('page_bid', $bid);
        View::assign('preselect_mdid', $mdid);
        View::assign('admin_name', session('ADMIN_NAME') ?: '');

        $mendian_list = [];
        $business_info = [];

        if ($this->is_logged_in) {
            // 已登录：查询门店列表、商户信息等
            $targetBid = $bid;
            if ($targetBid == 0) {
                $targetBid = Db::name('business')->where('aid', $aid)->value('id');
            }

            $mendian_list = Db::name('mendian')
                ->where('aid', $aid)
                ->where(function($query) use ($targetBid) {
                    $query->whereOr([
                        ['bid', '=', $targetBid],
                        ['bid', '=', 0]
                    ]);
                })
                ->select()
                ->toArray();

            $business = Db::name('business')->where('id', $targetBid)->find();
            $business_info = [
                'id' => $targetBid,
                'name' => $business['name'] ?? '',
            ];
        }

        View::assign('mendian_list', $mendian_list);
        View::assign('business_info', $business_info);

        return View::fetch('ai_travel_photo/smile_capture');
    }

    /**
     * 获取登录二维码
     * 
     * 路径: SmileCapture/get_login_qrcode
     * 方法: GET
     * 参数: aid(必填)
     */
    public function get_login_qrcode()
    {
        $aid = input('param.aid/d', 0);
        if (!$aid) {
            return json(['status' => 0, 'msg' => '缺少aid参数']);
        }

        try {
            // 获取公众号access_token
            $accessToken = \app\common\Wechat::access_token($aid, 'mp');
            if (!$accessToken) {
                return json(['status' => 0, 'msg' => '公众号未配置或access_token获取失败']);
            }

            // 生成唯一场景值
            $sceneStr = 'smile_login_' . bin2hex(random_bytes(16));
            $expireSeconds = 300; // 5分钟过期

            // 调用微信API创建临时带参数二维码
            $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $accessToken;
            $postData = json_encode([
                'expire_seconds' => $expireSeconds,
                'action_name' => 'QR_STR_SCENE',
                'action_info' => [
                    'scene' => ['scene_str' => $sceneStr]
                ]
            ], JSON_UNESCAPED_UNICODE);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            $res = json_decode($result, true);
            if (!$res || !isset($res['ticket'])) {
                $errMsg = isset($res['errmsg']) ? $res['errmsg'] : '未知错误';
                Log::error('笑脸抓拍登录二维码生成失败', ['error' => $errMsg, 'aid' => $aid]);
                return json(['status' => 0, 'msg' => '生成二维码失败: ' . $errMsg]);
            }

            // 将场景值存入缓存，状态为pending，关联aid
            cache($sceneStr, [
                'status' => 'pending',
                'aid' => $aid,
                'openid' => '',
                'uid' => 0,
                'bid' => 0,
                'username' => '',
                'create_time' => time()
            ], $expireSeconds + 60);

            // 返回二维码图片URL和场景值
            $qrUrl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($res['ticket']);
            return json([
                'status' => 1,
                'data' => [
                    'qrcode_url' => $qrUrl,
                    'scene' => $sceneStr,
                    'expire_seconds' => $expireSeconds
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('笑脸抓拍登录二维码异常', [
                'error' => $e->getMessage(),
                'aid' => $aid
            ]);
            return json(['status' => 0, 'msg' => '生成二维码失败：' . $e->getMessage()]);
        }
    }

    /**
     * 登录状态轮询
     * 
     * 路径: SmileCapture/check_login
     * 方法: GET
     * 参数: scene(必填)
     */
    public function check_login()
    {
        $scene = input('param.scene', '');
        if (empty($scene) || strpos($scene, 'smile_login_') !== 0) {
            return json(['status' => 0, 'msg' => '无效的scene参数']);
        }

        $data = cache($scene);
        if (!$data) {
            return json(['status' => 0, 'msg' => '二维码已过期', 'data' => ['expired' => true]]);
        }

        if ($data['status'] === 'confirmed' && $data['uid'] > 0) {
            // 登录成功，建立管理员session
            session('ADMIN_LOGIN', 1);
            session('ADMIN_UID', $data['uid']);
            session('ADMIN_AID', $data['aid']);
            session('ADMIN_BID', $data['bid']);
            session('ADMIN_NAME', $data['username']);

            // 清理场景缓存
            cache($scene, null);

            return json([
                'status' => 1,
                'data' => [
                    'aid' => $data['aid'],
                    'bid' => $data['bid'],
                    'username' => $data['username']
                ]
            ]);
        }

        // 未登录，继续等待
        return json(['status' => 0, 'msg' => '等待扫码', 'data' => ['expired' => false]]);
    }

    /**
     * 商家账号密码登录
     * POST /SmileCapture/do_login
     */
    public function do_login()
    {
        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }

        $username = trim(input('post.username', ''));
        $password = trim(input('post.password', ''));
        $aid = input('post.aid/d', 0) ?: $this->aid;
        $bid = input('post.bid/d', 0) ?: $this->bid;

        if ($username === '' || $password === '') {
            return json(['status' => 0, 'msg' => '用户名和密码不能为空']);
        }

        if (!$aid) {
            return json(['status' => 0, 'msg' => '参数错误：缺少aid']);
        }

        // 查询用户
        $user = Db::name('admin_user')
            ->where('un', $username)
            ->where('pwd', md5($password))
            ->where('aid', $aid)
            ->find();

        if (!$user) {
            return json(['status' => 0, 'msg' => '用户名或密码错误']);
        }

        if ($user['status'] != 1) {
            return json(['status' => 0, 'msg' => '该账号已禁用']);
        }

        // 验证商户状态
        $userBid = $user['bid'];
        if ($userBid > 0) {
            $binfo = Db::name('business')->where('id', $userBid)->find();
            if (!$binfo || $binfo['status'] != 1) {
                $msg = ($binfo && $binfo['status'] == -1) ? '商户已过期，请续费' : '商户未审核通过';
                return json(['status' => 0, 'msg' => $msg]);
            }
        }

        // 如果URL指定了bid，验证用户是否属于该商户
        if ($bid > 0 && $userBid > 0 && $userBid != $bid) {
            return json(['status' => 0, 'msg' => '该账号不属于此商户']);
        }

        // 设置session
        session('ADMIN_LOGIN', 1);
        session('ADMIN_UID', $user['id']);
        session('ADMIN_AID', $user['aid']);
        session('ADMIN_BID', $userBid);
        session('ADMIN_NAME', $user['un'] ?: $user['nickname']);
        session('IS_ADMIN', $user['isadmin']);

        if ($user['isadmin'] == 2) {
            session('BST_ID', $user['id']);
        } else {
            session('BST_ID', null);
        }

        // 更新登录信息
        Db::name('admin_user')->where('id', $user['id'])->update([
            'ip' => request()->ip(),
            'logintime' => time()
        ]);

        // 记录登录日志
        Db::name('admin_loginlog')->insert([
            'aid' => $user['aid'],
            'uid' => $user['id'],
            'logintime' => time(),
            'loginip' => request()->ip(),
            'logintype' => '笑脸抓拍登录'
        ]);

        return json([
            'status' => 1,
            'msg' => '登录成功',
            'data' => [
                'aid' => $user['aid'],
                'bid' => $userBid,
                'username' => $user['un'] ?: $user['nickname']
            ]
        ]);
    }

    /**
     * 检查是否已登录（代理方法前置检查）
     */
    private function requireLogin()
    {
        if (!$this->is_logged_in) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => -1, 'status' => -1, 'msg' => '请先登录']);
            exit;
        }
    }

    /**
     * 获取实际的bid（处理bid=0的情况）
     */
    private function getTargetBid()
    {
        $targetBid = $this->bid;
        if ($targetBid == 0) {
            $targetBid = Db::name('business')->where('aid', $this->aid)->value('id');
        }
        return $targetBid;
    }

    // ========== 抓拍上传/状态代理方法（绕过Common权限校验） ==========

    /**
     * 笑脸抓拍上传（代理AiTravelPhoto/smile_capture_upload）
     * POST /SmileCapture/smile_capture_upload
     */
    public function smile_capture_upload()
    {
        $this->requireLogin();

        if (!request()->isPost()) {
            return json(['status' => 0, 'msg' => '非法请求']);
        }

        try {
            $imageData = input('post.image', '');
            if (empty($imageData)) {
                return json(['status' => 0, 'msg' => '请提供图片数据']);
            }

            if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $imageData, $matches)) {
                $extension = strtolower($matches[1]);
                $imageContent = base64_decode($matches[2]);
            } else {
                return json(['status' => 0, 'msg' => '图片格式不正确']);
            }

            $allowedExts = ['jpg', 'jpeg', 'png'];
            if (!in_array($extension, $allowedExts)) {
                return json(['status' => 0, 'msg' => '仅支持JPG、JPEG、PNG格式']);
            }

            $mdid = input('post.mdid/d', 0);
            $isManual = input('post.is_manual/d', 0);
            $captureSize = input('post.capture_size', '1K');
            $aspectRatio = input('post.aspect_ratio', '3:4');

            $validSizes = ['1K', '2K'];
            $validRatios = ['1:1', '2:3', '3:4', '4:3', '9:16', '16:9'];
            if (!in_array($captureSize, $validSizes)) $captureSize = '1K';
            if (!in_array($aspectRatio, $validRatios)) $aspectRatio = '3:4';

            $tempFile = tempnam(sys_get_temp_dir(), 'smile_');
            file_put_contents($tempFile, $imageContent);

            $imageInfo = getimagesize($tempFile);
            if (!$imageInfo) {
                @unlink($tempFile);
                return json(['status' => 0, 'msg' => '图片文件损坏或格式不正确']);
            }
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            if ($width < 200 || $height < 200) {
                @unlink($tempFile);
                return json(['status' => 0, 'msg' => '图片尺寸过小']);
            }

            $basePx = ($captureSize === '2K') ? 2048 : 1024;
            $ratioParts = explode(':', $aspectRatio);
            $rw = intval($ratioParts[0]);
            $rh = intval($ratioParts[1]);
            if ($rw >= $rh) {
                $targetWidth = $basePx;
                $targetHeight = intval($basePx * $rh / $rw);
            } else {
                $targetHeight = $basePx;
                $targetWidth = intval($basePx * $rw / $rh);
            }

            $resized = $this->resizeCapture($tempFile, $width, $height, $targetWidth, $targetHeight, $extension);
            if ($resized) {
                $imageContent = file_get_contents($tempFile);
                $width = $targetWidth;
                $height = $targetHeight;
                $fileSize = strlen($imageContent);
                $fileMd5 = md5($imageContent);
            }

            if (!isset($fileMd5)) {
                $fileMd5 = md5_file($tempFile);
                $fileSize = filesize($tempFile);
            }

            $targetBid = $this->getTargetBid();

            Log::info('笑脸抓拍调试(SmileCapture)', [
                'aid' => $this->aid, 'bid' => $this->bid, 'targetBid' => $targetBid, 'mdid' => $mdid
            ]);

            $existPortrait = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('md5', $fileMd5)
                ->find();

            if ($existPortrait) {
                @unlink($tempFile);
                return json(['status' => 0, 'msg' => '该图片已存在']);
            }

            $date = date('Ymd');
            $uniqueName = md5(uniqid((string)mt_rand(), true)) . '.' . $extension;
            $savePath = 'upload/' . $this->aid . '/' . $date . '/';

            if (!is_dir(ROOT_PATH . $savePath)) {
                mk_dir(ROOT_PATH . $savePath);
            }

            $originalPath = $savePath . 'original_' . $uniqueName;
            file_put_contents(ROOT_PATH . $originalPath, $imageContent);

            $uploadPath = PRE_URL . '/' . $originalPath;
            $originalUrl = \app\common\Pic::uploadoss($uploadPath, false, false);

            if (!$originalUrl) {
                Log::warning('OSS上传失败，使用本地存储备用(SmileCapture)', [
                    'uploadPath' => $uploadPath, 'aid' => $this->aid
                ]);
                $originalUrl = '/' . $originalPath;
            }

            $thumbnailPath = $this->generateThumbnail(ROOT_PATH . $originalPath, $width, $height, $savePath, $uniqueName);
            $thumbnailUrl = '';
            if ($thumbnailPath) {
                $thumbnailUrl = \app\common\Pic::uploadoss(PRE_URL . '/' . $thumbnailPath, false, false);
                if (!$thumbnailUrl) {
                    $thumbnailUrl = '/' . $thumbnailPath;
                }
            }

            if (strpos($originalUrl, 'http') === 0) {
                @unlink(ROOT_PATH . $originalPath);
            }
            @unlink($tempFile);

            $faceEmbedding = input('post.face_embedding', '');

            $portraitData = [
                'aid' => $this->aid,
                'uid' => 0,
                'bid' => $targetBid,
                'mdid' => $mdid,
                'device_id' => 0,
                'type' => 1,
                'original_url' => $originalUrl,
                'cutout_url' => null,
                'thumbnail_url' => $thumbnailUrl,
                'file_name' => 'smile_capture_' . date('YmdHis') . '.' . $extension,
                'file_size' => $fileSize,
                'width' => $width,
                'height' => $height,
                'md5' => $fileMd5,
                'desc' => $isManual ? '手动抓拍' : '笑脸自动抓拍',
                'tags' => '笑脸抓拍',
                'status' => 1,
                'create_time' => time(),
                'update_time' => time()
            ];

            $portraitId = Db::name('ai_travel_photo_portrait')->insertGetId($portraitData);

            if (!$portraitId) {
                return json(['status' => 0, 'msg' => '数据保存失败']);
            }

            // 存储人脸特征
            if (!empty($faceEmbedding)) {
                try {
                    $embeddingData = json_decode($faceEmbedding, true);
                    if (is_array($embeddingData) && !empty($embeddingData)) {
                        $milvusAvailable = false;
                        try {
                            $milvusService = new \app\service\MilvusService();
                            if ($milvusService->isHealthy()) {
                                $vectorIds = $milvusService->insert($embeddingData, ['portrait_id' => $portraitId]);
                                if (!empty($vectorIds)) {
                                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)
                                        ->update(['face_embedding_id' => $vectorIds[0] ?? 0]);
                                    $milvusAvailable = true;
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning('Milvus存储失败，使用MySQL备用', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
                        }
                        if (!$milvusAvailable) {
                            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)
                                ->update(['face_embedding' => json_encode($embeddingData)]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('人脸特征存储失败', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
                }
            }

            // 触发异步任务
            $this->triggerAsyncTasks($portraitId, $targetBid);

            Log::info('笑脸抓拍成功(SmileCapture)', [
                'aid' => $this->aid, 'bid' => $targetBid, 'portrait_id' => $portraitId, 'is_manual' => $isManual
            ]);

            return json([
                'status' => 1,
                'msg' => '抓拍成功',
                'data' => [
                    'portrait_id' => $portraitId,
                    'original_url' => $originalUrl,
                    'thumbnail_url' => $thumbnailUrl
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('笑脸抓拍失败(SmileCapture)', ['error' => $e->getMessage()]);
            return json(['status' => 0, 'msg' => '抓拍失败：' . $e->getMessage()]);
        }
    }

    /**
     * 查询抓拍处理状态（代理AiTravelPhoto/smile_capture_status）
     * GET /SmileCapture/smile_capture_status
     */
    public function smile_capture_status()
    {
        $this->requireLogin();

        $portraitId = input('param.portrait_id/d', 0);
        if (!$portraitId) {
            return json(['status' => 0, 'msg' => '缺少portrait_id参数']);
        }

        try {
            $portrait = Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->where('aid', $this->aid)
                ->field('id, synthesis_status, synthesis_count')
                ->find();

            if (!$portrait) {
                return json(['status' => 0, 'msg' => '人像记录不存在']);
            }

            $synthesisStatus = intval($portrait['synthesis_status'] ?? 0);
            $progress = 0;
            $resultImages = [];

            switch ($synthesisStatus) {
                case 0: $progress = 0; break;
                case 1: $progress = 20; break;
                case 2: $progress = 60; break;
                case 3:
                    $progress = 100;
                    $results = Db::name('ai_travel_photo_result')
                        ->where('portrait_id', $portraitId)
                        ->where('status', 1)
                        ->field('url, thumbnail_url')
                        ->select();
                    foreach ($results as $r) {
                        $resultImages[] = $r['url'] ?: $r['thumbnail_url'];
                    }
                    break;
                case 4: $progress = 100; break;
            }

            return json([
                'status' => 1,
                'data' => [
                    'synthesis_status' => $synthesisStatus,
                    'progress' => $progress,
                    'result_images' => $resultImages
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('查询抓拍状态失败(SmileCapture)', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
            return json(['status' => 0, 'msg' => '查询失败：' . $e->getMessage()]);
        }
    }

    // ========== 图片处理辅助方法 ==========

    /**
     * 裁剪缩放抓拍图片到目标尺寸
     */
    private function resizeCapture($tempFile, $srcW, $srcH, $dstW, $dstH, $ext)
    {
        try {
            if ($ext == 'jpg' || $ext == 'jpeg') {
                $srcImg = imagecreatefromjpeg($tempFile);
            } elseif ($ext == 'png') {
                $srcImg = imagecreatefrompng($tempFile);
            } else {
                return false;
            }
            if (!$srcImg) return false;

            $targetAR = $dstW / $dstH;
            $srcAR = $srcW / $srcH;
            if ($srcAR > $targetAR) {
                $cropH = $srcH;
                $cropW = intval($srcH * $targetAR);
            } else {
                $cropW = $srcW;
                $cropH = intval($srcW / $targetAR);
            }
            $cropX = intval(($srcW - $cropW) / 2);
            $cropY = intval(($srcH - $cropH) / 2);

            $dstImg = imagecreatetruecolor($dstW, $dstH);
            if ($ext == 'png') {
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);
            }

            imagecopyresampled($dstImg, $srcImg, 0, 0, $cropX, $cropY, $dstW, $dstH, $cropW, $cropH);

            if ($ext == 'png') {
                imagepng($dstImg, $tempFile, 8);
            } else {
                imagejpeg($dstImg, $tempFile, 92);
            }

            imagedestroy($srcImg);
            imagedestroy($dstImg);
            return true;
        } catch (\Exception $e) {
            Log::error('抓拍图片裁剪缩放失败', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 生成缩略图
     */
    private function generateThumbnail($sourcePath, $sourceWidth, $sourceHeight, $savePath, $uniqueName)
    {
        try {
            $targetWidth = 800;
            $targetHeight = intval($sourceHeight * ($targetWidth / $sourceWidth));

            if ($sourceWidth <= 800) {
                $thumbnailPath = $savePath . 'thumbnail_' . $uniqueName;
                copy($sourcePath, ROOT_PATH . $thumbnailPath);
                return $thumbnailPath;
            }

            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
            if ($ext == 'jpg' || $ext == 'jpeg') {
                $sourceImage = imagecreatefromjpeg($sourcePath);
            } elseif ($ext == 'png') {
                $sourceImage = imagecreatefrompng($sourcePath);
            } else {
                return false;
            }

            if (!$sourceImage) return false;

            $thumbnailImage = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($ext == 'png') {
                imagealphablending($thumbnailImage, false);
                imagesavealpha($thumbnailImage, true);
            }

            imagecopyresampled($thumbnailImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

            $thumbnailPath = $savePath . 'thumbnail_' . $uniqueName;
            $result = imagejpeg($thumbnailImage, ROOT_PATH . $thumbnailPath, 85);

            imagedestroy($sourceImage);
            imagedestroy($thumbnailImage);

            return $result ? $thumbnailPath : false;
        } catch (\Exception $e) {
            Log::error('缩略图生成失败', ['source' => $sourcePath, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 触发异步任务（抠图 + AI生成）
     */
    private function triggerAsyncTasks($portraitId, $targetBid)
    {
        try {
            \think\facade\Queue::push(
                'app\\job\\CutoutJob',
                ['portrait_id' => $portraitId],
                'ai_cutout'
            );

            Log::info('抠图任务已推送(SmileCapture)', ['portrait_id' => $portraitId]);

            $setting = Db::name('ai_travel_photo_synthesis_setting')
                ->where('portrait_id', 0)
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->find();

            if (!$setting || empty($setting['template_ids'])) {
                Log::info('未配置合成模板，跳过自动生成', ['portrait_id' => $portraitId]);
                return;
            }

            $templateIds = explode(',', $setting['template_ids']);
            $generateCount = $setting['generate_count'] ?? 4;

            $templates = Db::name('generation_scene_template')
                ->whereIn('id', $templateIds)
                ->where('generation_type', 1)
                ->where('status', 1)
                ->field('id, template_name, model_id')
                ->limit($generateCount)
                ->select();

            foreach ($templates as $template) {
                $generationId = Db::name('ai_travel_photo_generation')->insertGetId([
                    'aid' => $this->aid,
                    'portrait_id' => $portraitId,
                    'scene_id' => 0,
                    'template_id' => $template['id'],
                    'uid' => 0,
                    'bid' => $targetBid,
                    'mdid' => 0,
                    'type' => 1,
                    'generation_type' => 1,
                    'status' => 0,
                    'create_time' => time(),
                    'update_time' => time(),
                    'queue_time' => time()
                ]);

                \think\facade\Queue::push(
                    'app\\job\\ImageGenerationJob',
                    ['generation_id' => $generationId],
                    'ai_image_generation'
                );

                Log::info('图生图任务已推送(SmileCapture)', [
                    'portrait_id' => $portraitId,
                    'template_id' => $template['id'],
                    'generation_id' => $generationId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('异步任务推送失败(SmileCapture)', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
        }
    }

    // ========== 合成相关代理方法（绕过Common权限校验） ==========

    /**
     * 合成设置页面（代理AiTravelPhoto/synthesis_settings）
     */
    public function synthesis_settings()
    {
        $this->requireLogin();

        $targetBid = $this->getTargetBid();

        $setting = Db::name('ai_travel_photo_synthesis_setting')
            ->where('portrait_id', 0)
            ->where('aid', $this->aid)
            ->where('bid', $targetBid)
            ->order('update_time DESC')
            ->find();

        try {
            $templates = Db::name('generation_scene_template')
                ->alias('t')
                ->leftJoin('model_info m', 't.model_id = m.id')
                ->leftJoin('model_provider p', 'm.provider_id = p.id')
                ->where('t.aid', $this->aid)
                ->where(function ($query) use ($targetBid) {
                    $query->where('t.bid', 0)->whereOr('t.bid', $targetBid);
                })
                ->where('t.generation_type', 1)
                ->where('t.status', 1)
                ->whereRaw("JSON_CONTAINS(m.capability_tags, '\"multi_input\"')")
                ->field('t.id, t.template_name as scene_name, t.category, t.cover_image, t.model_id, t.output_quantity, t.use_count, t.sort, m.model_name, p.provider_name')
                ->order('t.sort ASC, t.id DESC')
                ->select();

            foreach ($templates as &$tpl) {
                $tpl['scene_type_label'] = !empty($tpl['category']) ? $tpl['category'] : '未分类';
            }
            unset($tpl);
        } catch (\Exception $e) {
            $templates = [];
        }

        View::assign('portrait_id', 0);
        View::assign('setting', $setting);
        View::assign('templates', $templates);

        return View::fetch('ai_travel_photo/synthesis_settings');
    }

    /**
     * 合成设置保存（代理）
     */
    public function synthesis_settings_save()
    {
        $this->requireLogin();

        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            $portraitId = input('post.portrait_id/d', 0);
            $templateIds = input('post.template_ids/a', []);
            $generateCount = input('post.generate_count/d', 4);
            $generateMode = input('post.generate_mode/d', 1);

            $templateIds = is_array($templateIds) ? array_filter($templateIds) : [];
            if (empty($templateIds)) {
                return json(['code' => 1, 'msg' => '请关联至少一个照片场景模板']);
            }
            if ($generateCount < 1 || $generateCount > 10) {
                return json(['code' => 1, 'msg' => '合成数量应在1-10之间']);
            }

            $targetBid = $this->getTargetBid();
            if (!$targetBid) {
                return json(['code' => 1, 'msg' => '未找到默认商户']);
            }

            $data = [
                'portrait_id' => $portraitId,
                'aid' => $this->aid,
                'bid' => $targetBid,
                'template_ids' => implode(',', $templateIds),
                'generate_count' => $generateCount,
                'generate_mode' => $generateMode,
                'status' => 1,
                'update_time' => time()
            ];

            $exists = Db::name('ai_travel_photo_synthesis_setting')
                ->where('portrait_id', $portraitId)
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->find();

            if ($exists) {
                Db::name('ai_travel_photo_synthesis_setting')->where('id', $exists['id'])->update($data);
            } else {
                $data['create_time'] = time();
                Db::name('ai_travel_photo_synthesis_setting')->insert($data);
            }

            return json(['code' => 0, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取待处理人像（代理）
     */
    public function synthesis_get_pending()
    {
        $this->requireLogin();

        try {
            $targetBid = $this->getTargetBid();
            if (!$targetBid) {
                return json(['code' => 1, 'msg' => '未找到默认商户']);
            }

            // 处理超时的处理中记录
            $timeoutThreshold = time() - 600;
            Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('synthesis_status', 2)
                ->where('update_time', '<', $timeoutThreshold)
                ->update([
                    'synthesis_status' => 4,
                    'synthesis_error' => '合成超时，请重试',
                    'update_time' => time()
                ]);

            $portraits = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('status', 1)
                ->whereIn('synthesis_status', [0, 1])
                ->field('id, aid, bid, original_url, cutout_url, thumbnail_url, synthesis_status, synthesis_count, synthesis_error, create_time')
                ->select();

            return json(['code' => 0, 'data' => $portraits]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 重试合成（代理）
     */
    public function synthesis_retry()
    {
        $this->requireLogin();

        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            $portraitId = input('post.portrait_id/d', 0);
            if ($portraitId <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            $portrait = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();
            if (!$portrait) {
                return json(['code' => 1, 'msg' => '人像不存在']);
            }

            if (!in_array($portrait['synthesis_status'], [0, 1, 4])) {
                return json(['code' => 1, 'msg' => '该状态不能重试']);
            }

            $setting = Db::name('ai_travel_photo_synthesis_setting')
                ->where('aid', $portrait['aid'])
                ->where('bid', $portrait['bid'])
                ->where('portrait_id', 0)
                ->find();

            if (!$setting) {
                return json(['code' => 1, 'msg' => '请先保存合成设置']);
            }

            $templateIds = explode(',', $setting['template_ids']);
            $generateCount = $setting['generate_count'];
            $generateMode = $setting['generate_mode'];

            if (empty($templateIds)) {
                return json(['code' => 1, 'msg' => '请先关联照片场景模板']);
            }

            $templates = Db::name('generation_scene_template')
                ->whereIn('id', $templateIds)
                ->where('generation_type', 1)
                ->where('status', 1)
                ->field('id, aid, bid, template_name, model_id, cover_image, default_params, output_quantity, description, category')
                ->orderRaw('field(id, ' . $setting['template_ids'] . ')')
                ->select();

            if (count($templates) === 0) {
                return json(['code' => 1, 'msg' => '没有可用的照片场景模板']);
            }

            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                'synthesis_status' => 2,
                'synthesis_error' => '',
                'update_time' => time()
            ]);

            $synthesisService = new \app\service\AiTravelPhotoSynthesisService();
            $templatesArray = $templates->toArray();
            $selectedTemplates = [];

            if ($generateMode == 1) {
                for ($i = 0; $i < $generateCount; $i++) {
                    $selectedTemplates[] = $templatesArray[$i % count($templatesArray)];
                }
            } else {
                $pool = [];
                for ($i = 0; $i < $generateCount; $i++) {
                    if (empty($pool)) { $pool = $templatesArray; shuffle($pool); }
                    $selectedTemplates[] = array_shift($pool);
                }
            }

            $operatorName = session('ADMIN_NAME') ?: '';
            $result = $synthesisService->generate($portrait, $selectedTemplates, $operatorName);

            $resultCount = $result['data']['count'] ?? 0;
            if ($result['code'] === 0 && $resultCount > 0) {
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'synthesis_status' => 3,
                    'synthesis_count' => $resultCount,
                    'synthesis_time' => time(),
                    'update_time' => time()
                ]);
                return json(['code' => 0, 'msg' => '重试成功，生成' . $resultCount . '张图片']);
            } else {
                $errorMsg = $result['msg'] ?? '生成失败';
                if ($result['code'] === 0 && $resultCount === 0) $errorMsg = '生成完成但无结果输出';
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'synthesis_status' => 4,
                    'synthesis_error' => $errorMsg,
                    'update_time' => time()
                ]);
                return json(['code' => 1, 'msg' => $errorMsg]);
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '重试异常: ' . $e->getMessage()]);
        }
    }

    /**
     * 批量合成生成（代理）
     */
    public function synthesis_batch_generate()
    {
        $this->requireLogin();

        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            $targetBid = $this->getTargetBid();
            if (!$targetBid) {
                return json(['code' => 1, 'msg' => '未找到默认商户']);
            }

            $setting = Db::name('ai_travel_photo_synthesis_setting')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('portrait_id', 0)
                ->find();

            if (!$setting) {
                return json(['code' => 1, 'msg' => '请先保存合成设置']);
            }

            $templateIds = explode(',', $setting['template_ids']);
            $generateCount = $setting['generate_count'];
            $generateMode = $setting['generate_mode'];

            $portraits = Db::name('ai_travel_photo_portrait')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('status', 1)
                ->whereIn('synthesis_status', [0, 1])
                ->select();

            $total = count($portraits);
            if ($total === 0) {
                return json(['code' => 1, 'msg' => '没有需要处理的人像']);
            }

            $templates = Db::name('generation_scene_template')
                ->whereIn('id', $templateIds)
                ->where('generation_type', 1)
                ->where('status', 1)
                ->field('id, aid, bid, template_name, model_id, cover_image, default_params, output_quantity, description, category')
                ->orderRaw('field(id, ' . $setting['template_ids'] . ')')
                ->select();

            if (count($templates) === 0) {
                return json(['code' => 1, 'msg' => '没有可用的照片场景模板']);
            }

            $synthesisService = new \app\service\AiTravelPhotoSynthesisService();
            $successCount = 0;
            $failCount = 0;
            $operatorName = session('ADMIN_NAME') ?: '';

            $portraitIds = array_column($portraits->toArray(), 'id');
            if (!empty($portraitIds)) {
                Db::name('ai_travel_photo_portrait')
                    ->whereIn('id', $portraitIds)
                    ->update(['synthesis_status' => 2, 'update_time' => time()]);
            }

            $templatesArray = $templates->toArray();

            foreach ($portraits as $portrait) {
                $selectedTemplates = [];
                if ($generateMode == 1) {
                    for ($i = 0; $i < $generateCount; $i++) {
                        $selectedTemplates[] = $templatesArray[$i % count($templatesArray)];
                    }
                } else {
                    $pool = [];
                    for ($i = 0; $i < $generateCount; $i++) {
                        if (empty($pool)) { $pool = $templatesArray; shuffle($pool); }
                        $selectedTemplates[] = array_shift($pool);
                    }
                }

                try {
                    $result = $synthesisService->generate($portrait, $selectedTemplates, $operatorName);
                    $resultCount = $result['data']['count'] ?? 0;
                    if ($result['code'] === 0 && $resultCount > 0) {
                        Db::name('ai_travel_photo_portrait')->where('id', $portrait['id'])->update([
                            'synthesis_status' => 3,
                            'synthesis_count' => $resultCount,
                            'synthesis_time' => time(),
                            'update_time' => time()
                        ]);
                        $successCount++;
                    } else {
                        $errorMsg = $result['msg'] ?? '生成失败';
                        Db::name('ai_travel_photo_portrait')->where('id', $portrait['id'])->update([
                            'synthesis_status' => 4,
                            'synthesis_error' => $errorMsg,
                            'update_time' => time()
                        ]);
                        $failCount++;
                    }
                } catch (\Exception $e) {
                    Db::name('ai_travel_photo_portrait')->where('id', $portrait['id'])->update([
                        'synthesis_status' => 4,
                        'synthesis_error' => $e->getMessage(),
                        'update_time' => time()
                    ]);
                    $failCount++;
                }
            }

            return json([
                'code' => 0,
                'msg' => "批量合成完成，成功：{$successCount}，失败：{$failCount}",
                'data' => ['total' => $total, 'success' => $successCount, 'fail' => $failCount]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '合成异常: ' . $e->getMessage()]);
        }
    }

    /**
     * 合成生成 - 执行单个人像合成（代理）
     */
    public function synthesis_generate()
    {
        $this->requireLogin();

        if (!request()->isPost()) {
            return json(['code' => 1, 'msg' => '非法请求']);
        }

        try {
            $portraitId = input('post.portrait_id/d', 0);
            if ($portraitId <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            }

            $portrait = Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->where('aid', $this->aid)
                ->find();

            if (!$portrait) {
                return json(['code' => 1, 'msg' => '人像不存在']);
            }

            $targetBid = $this->getTargetBid() ?: $portrait['bid'];

            $setting = Db::name('ai_travel_photo_synthesis_setting')
                ->where('aid', $this->aid)
                ->where('bid', $targetBid)
                ->where('portrait_id', 0)
                ->find();

            if (!$setting) {
                return json(['code' => 1, 'msg' => '请先保存合成设置']);
            }

            $templateIds = explode(',', $setting['template_ids']);
            $generateCount = $setting['generate_count'];
            $generateMode = $setting['generate_mode'];

            $templates = Db::name('generation_scene_template')
                ->whereIn('id', $templateIds)
                ->where('generation_type', 1)
                ->where('status', 1)
                ->field('id, aid, bid, template_name, model_id, cover_image, default_params, output_quantity, description, category')
                ->orderRaw('field(id, ' . $setting['template_ids'] . ')')
                ->select();

            if (count($templates) === 0) {
                return json(['code' => 1, 'msg' => '没有可用的照片场景模板']);
            }

            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                'synthesis_status' => 2,
                'synthesis_error' => '',
                'update_time' => time()
            ]);

            $synthesisService = new \app\service\AiTravelPhotoSynthesisService();
            $templatesArray = $templates->toArray();
            $selectedTemplates = [];

            if ($generateMode == 1) {
                for ($i = 0; $i < $generateCount; $i++) {
                    $selectedTemplates[] = $templatesArray[$i % count($templatesArray)];
                }
            } else {
                $pool = [];
                for ($i = 0; $i < $generateCount; $i++) {
                    if (empty($pool)) { $pool = $templatesArray; shuffle($pool); }
                    $selectedTemplates[] = array_shift($pool);
                }
            }

            $operatorName = session('ADMIN_NAME') ?: '';
            $result = $synthesisService->generate($portrait, $selectedTemplates, $operatorName);

            $resultCount = $result['data']['count'] ?? 0;
            if ($result['code'] === 0 && $resultCount > 0) {
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'synthesis_status' => 3,
                    'synthesis_count' => $resultCount,
                    'synthesis_time' => time(),
                    'update_time' => time()
                ]);
                return json(['code' => 0, 'msg' => '生成成功，共' . $resultCount . '张图片', 'data' => ['count' => $resultCount]]);
            } else {
                $errorMsg = $result['msg'] ?? '生成失败';
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'synthesis_status' => 4,
                    'synthesis_error' => $errorMsg,
                    'update_time' => time()
                ]);
                return json(['code' => 1, 'msg' => $errorMsg]);
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '合成异常: ' . $e->getMessage()]);
        }
    }
}
