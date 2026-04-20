<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoPickService;
use app\service\AiTravelPhotoSelfieService;
use think\App;
use think\facade\Db;
use think\facade\Log;
use think\facade\Session;
use think\Response;

/**
 * 用户自拍端API控制器
 * 
 * 提供自拍页入口（OAuth授权）、自拍照片上传与比对、合成进度查询、注册通知等接口
 * 
 * @package app\controller\api
 * @date 2026-04-10
 */
class AiTravelPhotoSelfie extends BaseController
{
    protected $selfieService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->selfieService = new AiTravelPhotoSelfieService();
    }

    /**
     * 获取当前用户OpenID（从Session中读取）
     */
    protected function getOpenid(): string
    {
        return Session::get('selfie_openid', '');
    }

    /**
     * 自拍页入口（OAuth授权）
     * GET /api/ai_travel_photo/selfie/index
     * 
     * 参数: bid(必填), mdid(必填)
     */
    public function index(): Response
    {
        $bid = $this->request->get('bid/d', 0);
        $mdid = $this->request->get('mdid/d', 0);
        $aid = $this->request->get('aid/d', 0);

        if (!$bid || !$mdid) {
            return json(['code' => 400, 'msg' => '缺少必要参数']);
        }

        // 如果没有aid，从bid反查
        if (!$aid) {
            $business = Db::name('business')->where('id', $bid)->find();
            $aid = $business ? (int)$business['aid'] : 0;
        }

        if (!$aid) {
            return json(['code' => 400, 'msg' => '无法确定平台信息']);
        }

        // 判断是否在微信浏览器中
        $userAgent = $this->request->header('user-agent', '');
        $isWechat = strpos($userAgent, 'MicroMessenger') !== false;

        if (!$isWechat) {
            return json(['code' => 403, 'msg' => '请使用微信打开']);
        }

        // 检查是否已有OpenID
        $openid = $this->getOpenid();

        if (empty($openid)) {
            return json([
                'code' => 302,
                'msg' => '需要微信授权',
                'data' => [
                    'need_auth' => true,
                    'aid' => $aid,
                    'bid' => $bid,
                    'mdid' => $mdid,
                ],
            ]);
        }

        try {
            // 获取门店信息
            $mendian = Db::name('mendian')->where('id', $mdid)->find();
            $storeName = $mendian ? ($mendian['name'] ?: '') : '';

            // 获取商家信息
            $business = Db::name('business')->where('id', $bid)->find();
            $businessName = $business ? ($business['name'] ?: '') : '';

            // 检查门店是否启用自拍端
            $selfieEnabled = $mendian ? (int)($mendian['selfie_enabled'] ?? 1) : 1;
            if (!$selfieEnabled) {
                return json(['code' => 403, 'msg' => '该门店暂未开放自拍功能']);
            }

            // 检查该用户在该门店是否已有合成完成的成片
            $hasCompleted = false;
            $storePickUrl = '';
            try {
                $pickService = new AiTravelPhotoPickService();
                $hasCompleted = $pickService->hasCompletedResultsInStore($bid, $mdid, $openid);
                if ($hasCompleted) {
                    $storePickUrl = '/public/pick/index.html?mode=store&bid=' . $bid . '&mdid=' . $mdid;
                }
            } catch (\Exception $e) {
                Log::warning('selfie index检查已有成片异常', ['error' => $e->getMessage()]);
            }

            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => [
                    'aid' => $aid,
                    'bid' => $bid,
                    'mdid' => $mdid,
                    'openid' => $openid,
                    'store_name' => $storeName,
                    'business_name' => $businessName,
                    'has_completed' => $hasCompleted,
                    'store_pick_url' => $storePickUrl,
                ],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 启动微信OAuth授权
     * GET /api/ai_travel_photo/selfie/start_oauth
     */
    public function start_oauth(): Response
    {
        $bid = $this->request->get('bid/d', 0);
        $mdid = $this->request->get('mdid/d', 0);
        $aid = $this->request->get('aid/d', 0);

        if (!$bid || !$mdid || !$aid) {
            return json(['code' => 400, 'msg' => '缺少必要参数']);
        }

        try {
            $wxset = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
            $appid = $wxset['appid'] ?? '';

            if (empty($appid)) {
                return json(['code' => 500, 'msg' => '微信配置缺失']);
            }

            $callbackUrl = $this->request->domain() . '/index.php?s=/api/ai_travel_photo/selfie/oauth_callback';
            $redirectUri = urlencode($callbackUrl);
            $state = "selfie_{$aid}_{$bid}_{$mdid}";

            $oauthUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirectUri}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";

            return redirect($oauthUrl);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 微信OAuth回调
     * GET /api/ai_travel_photo/selfie/oauth_callback
     */
    public function oauth_callback(): Response
    {
        $code = $this->request->get('code', '');
        $state = $this->request->get('state', '');

        if (empty($code)) {
            return json(['code' => 400, 'msg' => '授权失败，缺少code']);
        }

        // 解析state: selfie_{aid}_{bid}_{mdid}
        $aid = 0;
        $bid = 0;
        $mdid = 0;
        if (preg_match('/^selfie_(\d+)_(\d+)_(\d+)$/', $state, $m)) {
            $aid = (int)$m[1];
            $bid = (int)$m[2];
            $mdid = (int)$m[3];
        }

        if (!$aid) {
            return json(['code' => 400, 'msg' => '授权状态无效']);
        }

        try {
            $wxset = Db::name('admin_setapp_mp')->where('aid', $aid)->find();
            $appid = $wxset['appid'] ?? '';
            $appsecret = $wxset['appsecret'] ?? '';

            // 用code换取openid
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecret}&code={$code}&grant_type=authorization_code";
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 10,
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            $tokenData = json_decode($result, true);
            $openid = $tokenData['openid'] ?? '';

            if (empty($openid)) {
                Log::error('自拍端OAuth获取openid失败', ['result' => $result]);
                return json(['code' => 500, 'msg' => '获取用户信息失败']);
            }

            // 存入Session
            Session::set('selfie_openid', $openid);
            Session::set('selfie_aid', $aid);
            Session::set('selfie_bid', $bid);
            Session::set('selfie_mdid', $mdid);

            // 自动注册/更新会员
            $member = Db::name('member')->where('aid', $aid)->where('mpopenid', $openid)->find();
            if (!$member) {
                // 获取用户信息
                $accessToken = \app\common\Wechat::access_token($aid, 'mp');
                $fansinfo = [];
                if ($accessToken) {
                    $infoUrl = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$accessToken}&openid={$openid}&lang=zh_CN";
                    $infoResult = file_get_contents($infoUrl);
                    $fansinfo = json_decode($infoResult, true) ?: [];
                }

                // 通过unionid查找
                if (!empty($fansinfo['unionid'])) {
                    $member = Db::name('member')->where('aid', $aid)->where('unionid', $fansinfo['unionid'])->find();
                    if ($member) {
                        Db::name('member')->where('id', $member['id'])->update(['mpopenid' => $openid, 'subscribe' => 1]);
                    }
                }

                if (!$member) {
                    $data = [
                        'aid' => $aid,
                        'mpopenid' => $openid,
                        'nickname' => ($fansinfo['nickname'] ?? '') ?: '微信用户',
                        'sex' => $fansinfo['sex'] ?? 3,
                        'headimg' => ($fansinfo['headimgurl'] ?? '') ?: (defined('PRE_URL') ? PRE_URL : '') . '/static/img/touxiang.png',
                        'unionid' => $fansinfo['unionid'] ?? '',
                        'subscribe' => 1,
                        'subscribe_time' => time(),
                        'createtime' => time(),
                        'last_visittime' => time(),
                        'platform' => 'mp'
                    ];
                    \app\model\Member::add($aid, $data);
                }
            }

            // 检查该用户在该门店是否已有合成完成的成片
            // 如有 → 桥接pick_openid，直接重定向到选片门店模式
            try {
                $pickService = new AiTravelPhotoPickService();
                if ($pickService->hasCompletedResultsInStore($bid, $mdid, $openid)) {
                    Session::set('pick_openid', $openid); // 桥接 selfie → pick session
                    $pickUrl = $this->request->domain() . '/public/pick/index.html?mode=store&bid=' . $bid . '&mdid=' . $mdid;
                    Log::info('自拍端OAuth回调：用户已有成片，重定向到选片页', ['openid' => $openid, 'bid' => $bid, 'mdid' => $mdid]);
                    return redirect($pickUrl);
                }
            } catch (\Exception $e) {
                Log::warning('自拍端OAuth检查已有成片异常', ['error' => $e->getMessage()]);
            }

            // 无成片 → 重定向回自拍页（原逻辑）
            $redirectUrl = $this->request->domain() . '/public/selfie/index.html?aid=' . $aid . '&bid=' . $bid . '&mdid=' . $mdid;
            return redirect($redirectUrl);
        } catch (\Exception $e) {
            Log::error('自拍端OAuth回调异常', ['error' => $e->getMessage()]);
            return json(['code' => 500, 'msg' => '授权处理失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 自拍照片上传与比对
     * POST /api/ai_travel_photo/selfie/capture
     */
    public function capture(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 302, 'msg' => '请先完成微信授权']);
        }

        // 限流：同一openid每分钟最多5次
        $rateLimitKey = 'selfie_capture_' . $openid;
        $currentCount = (int)\think\facade\Cache::get($rateLimitKey, 0);
        if ($currentCount >= 5) {
            return json(['code' => 429, 'msg' => '操作过于频繁，请稍后再试']);
        }
        \think\facade\Cache::set($rateLimitKey, $currentCount + 1, 60);

        $image = $this->request->post('image', '');
        $faceEmbeddingJson = $this->request->post('face_embedding', ''); // 可选：模型未加载时前端不传
        $bid = $this->request->post('bid/d', 0);
        $mdid = $this->request->post('mdid/d', 0);
        $aid = Session::get('selfie_aid', 0);

        if (empty($image)) {
            return json(['code' => 400, 'msg' => '缺少图片数据']);
        }

        if (!$bid) {
            $bid = Session::get('selfie_bid', 0);
        }
        if (!$mdid) {
            $mdid = Session::get('selfie_mdid', 0);
        }
        if (!$aid) {
            $business = Db::name('business')->where('id', $bid)->find();
            $aid = $business ? (int)$business['aid'] : 0;
        }

        if (!$bid || !$mdid || !$aid) {
            return json(['code' => 400, 'msg' => '门店参数无效']);
        }

        try {
            // face_embedding 可选：手动拍摄时模型可能尚未加载
            $faceEmbedding = [];
            $hasEmbedding = false;
            if (!empty($faceEmbeddingJson)) {
                $faceEmbedding = json_decode($faceEmbeddingJson, true);
                if (is_array($faceEmbedding) && count($faceEmbedding) >= 64) {
                    $hasEmbedding = true;
                } else {
                    $faceEmbedding = [];
                }
            }

            // ===== 第一步：与数据库人像数据比对 =====
            // 有人脸特征时：通过人脸相似度在门店成片库中精确匹配
            // 无人脸特征时：通过openid查找该用户在该门店已有的已合成人像
            $matchResult = null;
            if ($hasEmbedding) {
                $matchResult = $this->selfieService->matchFaceInStore($faceEmbedding, $aid, $bid, $mdid);
            } else {
                $matchResult = $this->selfieService->matchByOpenidInStore($bid, $mdid, $openid);
            }

            if ($matchResult) {
                // ===== OpenID 关联：将当前用户 openid 回写至匹配的设备拍摄人像 =====
                // 规则：仅更新 user_openid 为空的记录，已有值的保留不覆盖，冲突记录日志
                if (!empty($matchResult['portrait_ids'])) {
                    try {
                        $this->selfieService->associateOpenidToPortraits(
                            $matchResult['portrait_ids'], $openid, $bid, $mdid
                        );
                    } catch (\Exception $e) {
                        Log::warning('OpenID关联异常，不影响匹配结果返回', [
                            'openid' => $openid, 'error' => $e->getMessage()
                        ]);
                    }
                }

                // 比对成功：返回付费选片链接
                \app\model\AiTravelPhotoSelfieQrcode::where('aid', $aid)
                    ->where('bid', $bid)
                    ->where('mdid', $mdid)
                    ->inc('match_count')
                    ->update([]);

                return json([
                    'code' => 200,
                    'msg' => '找到匹配的旅拍照片',
                    'data' => [
                        'matched' => true,
                        'pick_url' => $matchResult['pick_url'],
                        'result_count' => $matchResult['result_count'],
                    ],
                ]);
            }

            // ===== 第二步：当天防重检测（同一用户同一门店当天仅首次合成） =====
            $dedupResult = null;
            if ($hasEmbedding) {
                $dedupResult = $this->selfieService->checkSelfieDedup($faceEmbedding, $bid, $mdid, $openid);
            } else {
                $dedupResult = $this->selfieService->checkSelfieDedupByOpenid($bid, $mdid, $openid);
            }

            if ($dedupResult) {
                $dedupPortraitId = $dedupResult['portrait_id'];
                $dedupStatus = $dedupResult['synthesis_status'];
                $dedupPickUrl = $dedupResult['pick_url'];

                if ($dedupStatus == 3 && !empty($dedupPickUrl)) {
                    return json([
                        'code' => 200,
                        'msg' => '您今天已在本门店拍摄过，请前往选片',
                        'data' => [
                            'matched' => true,
                            'pick_url' => $dedupPickUrl,
                            'result_count' => 0,
                            'dedup' => true,
                        ],
                    ]);
                } elseif (in_array($dedupStatus, [0, 2])) {
                    return json([
                        'code' => 200,
                        'msg' => '您今天已在本门店拍摄过，照片正在生成中，请耐心等待',
                        'data' => [
                            'matched' => false,
                            'portrait_id' => $dedupPortraitId,
                            'estimated_seconds' => 60,
                            'template_count' => 0,
                            'dedup' => true,
                        ],
                    ]);
                }
                // dedupStatus == 4（全部失败）：不拦截，允许重新提交
            }

            // ===== 第三步：未命中 → 将人像数据存入数据库并进行合成 =====
            // 去掉base64头部
            $imageData = $image;
            if (strpos($imageData, 'base64,') !== false) {
                $imageData = substr($imageData, strpos($imageData, 'base64,') + 7);
            }

            $result = $this->selfieService->createSelfiePortraitAndSynthesize(
                $imageData, $faceEmbedding, $aid, $bid, $mdid, $openid
            );

            return json([
                'code' => 200,
                'msg' => '照片已接收，正在为您生成旅拍照片',
                'data' => [
                    'matched' => false,
                    'portrait_id' => $result['portrait_id'],
                    'estimated_seconds' => $result['estimated_seconds'],
                    'template_count' => $result['template_count'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('自拍上传比对异常', ['error' => $e->getMessage(), 'openid' => $openid]);
            return json(['code' => 500, 'msg' => '处理失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 合成进度查询
     * GET /api/ai_travel_photo/selfie/progress
     */
    public function progress(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 302, 'msg' => '请先完成微信授权']);
        }

        $portraitId = $this->request->get('portrait_id/d', 0);
        if (!$portraitId) {
            return json(['code' => 400, 'msg' => '缺少人像ID']);
        }

        // 限流：同一portrait_id每分钟最多20次
        $rateLimitKey = 'selfie_progress_' . $portraitId;
        $currentCount = (int)\think\facade\Cache::get($rateLimitKey, 0);
        if ($currentCount >= 20) {
            return json(['code' => 429, 'msg' => '查询过于频繁']);
        }
        \think\facade\Cache::set($rateLimitKey, $currentCount + 1, 60);

        try {
            $progress = $this->selfieService->getProgress($portraitId);

            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => $progress,
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 注册通知请求
     * POST /api/ai_travel_photo/selfie/notify_me
     */
    public function notify_me(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 302, 'msg' => '请先完成微信授权']);
        }

        $portraitId = $this->request->post('portrait_id/d', 0);
        if (!$portraitId) {
            return json(['code' => 400, 'msg' => '缺少人像ID']);
        }

        $aid = Session::get('selfie_aid', 0);
        $bid = Session::get('selfie_bid', 0);
        $mdid = Session::get('selfie_mdid', 0);

        try {
            $notifyId = $this->selfieService->registerNotify($portraitId, $openid, $aid, $bid, $mdid);

            return json([
                'code' => 200,
                'msg' => '通知请求已注册，照片准备好后将通过公众号通知您',
                'data' => [
                    'notify_id' => $notifyId,
                ],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }
}
