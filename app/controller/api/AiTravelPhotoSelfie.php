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
        // face_embedding 不再使用：前端128维与特征库512维不兼容，统一由后端InsightFace提取
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
            // ===== 第一步：准备图片数据 =====
            $imageData = $image;
            if (strpos($imageData, 'base64,') !== false) {
                $imageData = substr($imageData, strpos($imageData, 'base64,') + 7);
            }

            // ===== 第二步：上传图片获取URL（用于后续展示） =====
            $capturedImageUrl = $this->selfieService->uploadSelfieImage($imageData, $aid);

            // 获取门店名称
            $mendian = Db::name('mendian')->where('id', $mdid)->find();
            $storeName = $mendian ? ($mendian['name'] ?: '门店') : '门店';

            // ===== 第三步：后端 InsightFace 提取 512 维特征 + 人物属性分析（基于上传照片，非用户存档） =====
            // 使用 Base64 直传，避免 URL 依赖（OSS 上传失败时 InsightFace 无法访问本地路径）
            $hasBackendEmbedding = false;
            $backendEmbedding = [];
            $photoGender = 3;  // 基于照片分析的结果: 1=男 2=女 3=未知
            $photoAgeGroup = 'Unknown';

            // 并行分析：特征提取 + 人物属性分析（基于同一张照片）
            $faceEmbeddingService = new \app\service\FaceEmbeddingService();
            $imageAnalysisService = new \app\service\ImageAnalysisService();

            // 特征提取（512维 InsightFace，与特征库维度一致）
            try {
                $faceResult = $faceEmbeddingService->extractFromBase64($image, 1);
                if ($faceResult && !empty($faceResult['embedding']) && count($faceResult['embedding']) >= 64) {
                    $backendEmbedding = $faceResult['embedding'];
                    $hasBackendEmbedding = true;
                    Log::info('自拍端capture: 后端InsightFace特征提取成功', [
                        'dim' => $faceResult['dim'] ?? count($backendEmbedding),
                        'det_score' => $faceResult['det_score'] ?? 0,
                        'openid' => $openid,
                    ]);
                } else {
                    Log::info('自拍端capture: 后端未检测到人脸', ['openid' => $openid]);
                }
            } catch (\Exception $e) {
                Log::warning('自拍端capture: 后端特征提取异常', [
                    'openid' => $openid, 'error' => $e->getMessage()
                ]);
            }

            // 人物属性分析（性别、年龄，基于上传照片而非用户存档）
            try {
                $analysisResult = $imageAnalysisService->analyzeFromBase64($image, false);
                if ($analysisResult) {
                    $attr = \app\service\ImageAnalysisService::extractMainSubject($analysisResult);
                    $genderStr = $attr['gender'] ?? 'Unknown';
                    if (strcasecmp($genderStr, 'Male') === 0) {
                        $photoGender = 1;
                    } elseif (strcasecmp($genderStr, 'Female') === 0) {
                        $photoGender = 2;
                    }
                    $photoAgeGroup = $attr['age_group'] ?? 'Unknown';
                    Log::info('自拍端capture: 照片人物属性分析完成', [
                        'openid' => $openid,
                        'photo_gender' => $photoGender,
                        'photo_age_group' => $photoAgeGroup,
                        'gender_confidence' => $attr['gender_confidence'] ?? 0,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('自拍端capture: 照片属性分析异常', [
                    'openid' => $openid, 'error' => $e->getMessage()
                ]);
            }

            // ===== 第四步：人脸匹配（仅使用后端512维 + openid兜底） =====
            // 前端128维 face-api.js 只用作笑脸检测，不参与匹配（维度不兼容导致误判）
            $matchResult = null;
            $matchEmbeddingSource = 'none';

            if ($hasBackendEmbedding) {
                $matchResult = $this->selfieService->matchFaceInStore($backendEmbedding, $aid, $bid, $mdid);
                if ($matchResult) {
                    $matchEmbeddingSource = 'backend_512';
                    Log::info('自拍端capture: 后端512维匹配成功', [
                        'openid' => $openid,
                        'best_distance' => $matchResult['best_distance'] ?? 0,
                        'matched_count' => count($matchResult['portrait_ids'] ?? []),
                    ]);
                } else {
                    Log::info('自拍端capture: 后端512维未匹配到任何人像', [
                        'openid' => $openid, 'bid' => $bid, 'mdid' => $mdid,
                        'embedding_dim' => count($backendEmbedding),
                    ]);
                }
            }

            // openid 兜底匹配（无后端特征或后端特征也未匹配到时）
            if (!$matchResult) {
                $matchResult = $this->selfieService->matchByOpenidInStore($bid, $mdid, $openid);
                if ($matchResult) {
                    $matchEmbeddingSource = 'openid_fallback';
                    Log::info('自拍端capture: openid兜底匹配成功', [
                        'openid' => $openid,
                        'portrait_ids' => $matchResult['portrait_ids'] ?? [],
                        'result_count' => $matchResult['result_count'] ?? 0,
                    ]);
                } else {
                    Log::info('自拍端capture: openid兜底也未能匹配', [
                        'openid' => $openid, 'bid' => $bid, 'mdid' => $mdid,
                    ]);
                }
            }

            // ===== 第五步：匹配成功后的处理 =====
            $hasResults = false;
            $resultPickUrl = '';
            $resultCount = 0;
            $matchedPortraitIds = [];
            $faceMatchedButNoResults = false; // 区分：人脸匹配到但无成片 vs 完全未匹配

            if ($matchResult) {
                $matchedPortraitIds = $matchResult['portrait_ids'] ?? [];

                // OpenID 关联：将当前用户 openid 回写至匹配的设备拍摄人像
                if (!empty($matchedPortraitIds)) {
                    try {
                        $this->selfieService->associateOpenidToPortraits(
                            $matchedPortraitIds, $openid, $bid, $mdid
                        );
                    } catch (\Exception $e) {
                        Log::warning('OpenID关联异常，不影响匹配结果返回', [
                            'openid' => $openid, 'error' => $e->getMessage()
                        ]);
                    }
                }

                // 增量计数
                \app\model\AiTravelPhotoSelfieQrcode::where('aid', $aid)
                    ->where('bid', $bid)
                    ->where('mdid', $mdid)
                    ->inc('match_count')
                    ->update([]);

                // 检查匹配到的人像是否有已完成的成片
                $resultsCheck = $this->selfieService->checkMatchedPortraitHasResults($matchedPortraitIds);
                if ($resultsCheck) {
                    $hasResults = true;
                    $resultPickUrl = $resultsCheck['pick_url'];
                    $resultCount = $resultsCheck['result_count'];
                } else {
                    $faceMatchedButNoResults = true;
                }

                // 匹配成功且有成片 → 直接返回选片链接
                if ($hasResults) {
                    return json([
                        'code' => 200,
                        'msg' => '找到匹配的旅拍照片',
                        'data' => [
                            'matched' => true,
                            'pick_url' => $resultPickUrl,
                            'result_count' => $resultCount,
                            'match_source' => $matchEmbeddingSource,
                            'photo_gender' => $photoGender,
                        ],
                    ]);
                }

                // 匹配成功但无成片 → 记录日志，引导用户选模板生成
                // 查询匹配人像的合成状态用于诊断
                $portraitStatuses = [];
                if (!empty($matchedPortraitIds)) {
                    try {
                        $portraitStatuses = Db::name('ai_travel_photo_portrait')
                            ->whereIn('id', $matchedPortraitIds)
                            ->column('synthesis_status', 'id');
                    } catch (\Exception $e) {}
                }
                Log::info('自拍端人脸匹配成功但无成片，引导用户选模板', [
                    'openid' => $openid,
                    'matched_portrait_ids' => $matchedPortraitIds,
                    'match_source' => $matchEmbeddingSource,
                    'bid' => $bid,
                    'mdid' => $mdid,
                    'portrait_synthesis_statuses' => $portraitStatuses,
                ]);
            } else {
                // 完全未匹配 → 记录详细日志以排查
                Log::warning('自拍端capture: 人脸未匹配到任何特征库人像', [
                    'openid' => $openid,
                    'bid' => $bid,
                    'mdid' => $mdid,
                    'has_backend_embedding' => $hasBackendEmbedding,
                    'embedding_dim' => $hasBackendEmbedding ? count($backendEmbedding) : 0,
                ]);
            }

            // ===== 第六步：当天防重检测 =====
            // 使用后端512维特征做防重（准确度最高）
            $dedupResult = null;
            if ($hasBackendEmbedding && count($backendEmbedding) >= 64) {
                $dedupResult = $this->selfieService->checkSelfieDedup($backendEmbedding, $bid, $mdid, $openid);
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
                            'photo_gender' => $photoGender,
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
                            'photo_gender' => $photoGender,
                        ],
                    ]);
                }
                // dedupStatus == 4（全部失败）：不拦截，允许重新提交
            }

            // ===== 第七步：未匹配或无成片 → 返回 no_photo_found（携带照片分析性别） =====
            // photo_gender 基于上传照片分析，每次上传都可能不同，与用户存档性别独立
            return json([
                'code' => 200,
                'msg' => '未查找到您在' . $storeName . '的旅拍照片',
                'data' => [
                    'matched' => false,
                    'action' => 'no_photo_found',
                    'store_name' => $storeName,
                    'photo_gender' => $photoGender,
                    'photo_age_group' => $photoAgeGroup,
                    'captured_image_url' => $capturedImageUrl,
                    'match_source' => $matchEmbeddingSource,
                    'face_matched_but_no_results' => $faceMatchedButNoResults,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('自拍上传比对异常', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'openid' => $openid]);
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

    /**
     * 保存用户性别
     * POST /api/ai_travel_photo/selfie/save_gender
     */
    public function save_gender(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 302, 'msg' => '请先完成微信授权']);
        }

        $gender = $this->request->post('gender/d', 0);
        if (!in_array($gender, [1, 2])) {
            return json(['code' => 400, 'msg' => '性别参数无效，请选择男或女']);
        }

        $aid = Session::get('selfie_aid', 0);

        try {
            $member = Db::name('member')->where('mpopenid', $openid)->where('aid', $aid)->find();
            if (!$member) {
                return json(['code' => 404, 'msg' => '用户信息不存在']);
            }

            Db::name('member')->where('id', $member['id'])->update(['sex' => $gender]);

            return json([
                'code' => 200,
                'msg' => '性别保存成功',
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取推荐模板列表（基于性别筛选）
     * GET /api/ai_travel_photo/selfie/recommend_templates
     */
    public function recommend_templates(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 302, 'msg' => '请先完成微信授权']);
        }

        $bid = $this->request->get('bid/d', 0) ?: (int)Session::get('selfie_bid', 0);
        $mdid = $this->request->get('mdid/d', 0) ?: (int)Session::get('selfie_mdid', 0);
        $gender = $this->request->get('gender/d', 0);
        $aid = Session::get('selfie_aid', 0);

        if (!$bid || !$mdid) {
            return json(['code' => 400, 'msg' => '缺少门店参数']);
        }

        // 未传gender则从member表读取
        if (!$gender) {
            $member = Db::name('member')->where('mpopenid', $openid)->where('aid', $aid)->find();
            $gender = $member ? (int)($member['sex'] ?? 3) : 3;
        }

        try {
            $result = $this->selfieService->getRecommendTemplates($bid, $mdid, $gender);

            $genderLabel = '';
            if ($gender == 1) $genderLabel = '男性';
            elseif ($gender == 2) $genderLabel = '女性';

            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => [
                    'templates' => $result,
                    'total' => count($result),
                    'gender_label' => $genderLabel,
                ],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 自拍端选模板合成
     * POST /api/ai_travel_photo/selfie/generate_with_template
     */
    public function generate_with_template(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 302, 'msg' => '请先完成微信授权']);
        }

        $templateId = $this->request->post('template_id/d', 0);
        $bid = $this->request->post('bid/d', 0) ?: (int)Session::get('selfie_bid', 0);
        $mdid = $this->request->post('mdid/d', 0) ?: (int)Session::get('selfie_mdid', 0);
        $imageUrl = $this->request->post('image_url', '');
        $aid = Session::get('selfie_aid', 0);

        if (!$templateId) {
            return json(['code' => 400, 'msg' => '请选择合成模板']);
        }
        if (!$bid || !$mdid || !$aid) {
            return json(['code' => 400, 'msg' => '门店参数无效']);
        }
        if (empty($imageUrl)) {
            return json(['code' => 400, 'msg' => '缺少自拍照片']);
        }

        try {
            $result = $this->selfieService->generateWithTemplate(
                $templateId, $imageUrl, $aid, $bid, $mdid, $openid
            );

            if (isset($result['dedup']) && $result['dedup']) {
                return json([
                    'code' => 200,
                    'msg' => '您今天已提交过合成',
                    'data' => $result,
                ]);
            }

            return json([
                'code' => 200,
                'msg' => '合成任务已提交',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('自拍端选模板合成异常', ['error' => $e->getMessage(), 'openid' => $openid]);
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 创建支付订单（自拍端合成完成后支付）
     * POST /api/ai_travel_photo/selfie/create_order
     * 
     * 参数: portrait_id, bid, mdid
     * 返回: jsapi_params (WeixinJSBridge 参数) 或 pay_url (H5降级)
     */
    public function create_order(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 302, 'msg' => '请先完成微信授权']);
        }

        $portraitId = $this->request->post('portrait_id/d', 0);
        $bid = $this->request->post('bid/d', 0) ?: (int)Session::get('selfie_bid', 0);
        $mdid = $this->request->post('mdid/d', 0) ?: (int)Session::get('selfie_mdid', 0);
        $aid = Session::get('selfie_aid', 0);

        if (!$portraitId) {
            return json(['code' => 400, 'msg' => '缺少人像ID']);
        }

        try {
            $result = $this->selfieService->createSelfiePaymentOrder($portraitId, $openid, $aid, $bid, $mdid);

            return json([
                'code' => 200,
                'msg' => '订单创建成功',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('自拍端创建支付订单异常', ['error' => $e->getMessage(), 'portrait_id' => $portraitId]);
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 查询支付状态（轮询用）
     * GET /api/ai_travel_photo/selfie/payment_status
     *
     * 参数: portrait_id 或 order_id
     * 返回: paid (bool), no_watermark_url (支付成功后无水印照片URL)
     */
    public function payment_status(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 302, 'msg' => '请先完成微信授权']);
        }

        $portraitId = $this->request->get('portrait_id/d', 0);
        $orderId = $this->request->get('order_id', '');

        if (!$portraitId && !$orderId) {
            return json(['code' => 400, 'msg' => '缺少参数']);
        }

        try {
            $result = $this->selfieService->getSelfiePaymentStatus($portraitId, $orderId);

            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }
}
