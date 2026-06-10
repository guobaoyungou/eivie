<?php
declare(strict_types=1);

namespace app\controller\api;

use app\BaseController;
use app\service\AiTravelPhotoPickService;
use app\service\AiTravelPhotoPortraitService;
use app\model\AiTravelPhotoPortrait;
use think\App;
use think\facade\Session;
use think\Response;

/**
 * 选片H5页面API控制器
 * 
 * 提供扫码选片、套餐推荐、下单支付、下载等全流程接口
 */
class AiTravelPhotoPick extends BaseController
{
    protected $pickService;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->pickService = new AiTravelPhotoPickService();
    }

    /**
     * 获取当前用户OpenID（从Session中读取）
     */
    protected function getOpenid(): string
    {
        return Session::get('pick_openid', '');
    }

    /**
     * 选片页入口
     * GET /api/ai-travel-photo/pick/index?qr={qrcode标识}
     * 
     * 扫码后落地页，负责微信OAuth授权+定位人像
     */
    public function index(): Response
    {
        $qrCode = $this->request->get('qr', '');

        if (empty($qrCode)) {
            return json(['code' => 400, 'msg' => '缺少二维码参数']);
        }

        // 判断是否在微信浏览器中
        $userAgent = $this->request->header('user-agent', '');
        $isWechat = strpos($userAgent, 'MicroMessenger') !== false;

        if (!$isWechat) {
            return json(['code' => 403, 'msg' => '请使用微信扫码打开']);
        }

        // 检查是否已有OpenID
        $openid = $this->getOpenid();

        if (empty($openid)) {
            // 需要微信OAuth授权
            return json([
                'code' => 302,
                'msg' => '需要微信授权',
                'data' => [
                    'need_auth' => true,
                    'qr' => $qrCode,
                ],
            ]);
        }

        try {
            // 通过二维码获取人像信息
            $portraitInfo = $this->pickService->getPortraitByQrcode($qrCode);

            // 记录扫码
            $this->pickService->recordScan($portraitInfo['qrcode_id'], $openid);

            // 自动将人像关联到当前微信用户
            $this->pickService->associateUserToPortrait($portraitInfo['portrait_id'], $openid);

            // 通过openid反查member获取uid（公众号关注后自动注册的会员）
            $member = \think\facade\Db::name('member')
                ->where('aid', $portraitInfo['aid'])
                ->where('mpopenid', $openid)
                ->find();
            $uid = $member ? (int)$member['id'] : 0;

            // 关注状态检测：二级策略
            $isSubscribed = $this->checkSubscribeStatus($portraitInfo['aid'], $openid, $member);

            // 获取公众号信息（用于未关注引导弹层）
            $mpInfo = $this->getMpSubscribeInfo($portraitInfo['aid']);

            // 查询商家名称
            $businessName = '';
            $faceWatermarkEnabled = 0;
            if ($portraitInfo['bid'] > 0) {
                $businessRow = \think\facade\Db::name('business')
                    ->where('id', $portraitInfo['bid'])
                    ->field('name, ai_pick_face_watermark_enabled')
                    ->find();
                $businessName = $businessRow ? ($businessRow['name'] ?: '') : '';
                $faceWatermarkEnabled = $businessRow ? intval($businessRow['ai_pick_face_watermark_enabled'] ?? 0) : 0;
            }

            // 查询公众号昵称（用于水印文字）
            $mpNickname = '';
            if ($faceWatermarkEnabled && $portraitInfo['aid'] > 0) {
                $mpNickname = \think\facade\Db::name('admin_setapp_mp')
                    ->where('aid', $portraitInfo['aid'])
                    ->value('nickname') ?: '';
            }

            // 查询门店名称
            $storeName = '';
            $mdid = $portraitInfo['mdid'] ?? 0;
            if ($mdid > 0) {
                $storeName = \think\facade\Db::name('mendian')
                    ->where('id', $mdid)
                    ->value('name') ?: '';
            }

            // 查找该用户在同一门店下的所有关联人像（多用户关联：通过关联表查询）
            $portraitIds = [$portraitInfo['portrait_id']];
            if ($mdid > 0 && !empty($openid)) {
                $relatedPortraits = \think\facade\Db::name('ai_travel_photo_portrait')
                    ->whereIn('id', function ($query) use ($openid) {
                        $query->name('ai_travel_photo_portrait_user')
                            ->where('user_openid', $openid)
                            ->field('portrait_id');
                    })
                    ->where('bid', $portraitInfo['bid'])
                    ->where('mdid', $mdid)
                    ->where('id', '<>', $portraitInfo['portrait_id'])
                    ->where('status', 1)
                    ->where('synthesis_status', 3)
                    ->column('id');

                if (!empty($relatedPortraits)) {
                    foreach ($relatedPortraits as $rid) {
                        $portraitIds[] = (int)$rid;
                    }
                }
            }

            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => [
                    'portrait_id' => $portraitInfo['portrait_id'],
                    'portrait_ids' => $portraitIds,
                    'aid' => $portraitInfo['aid'],
                    'bid' => $portraitInfo['bid'],
                    'mdid' => $mdid,
                    'qrcode_id' => $portraitInfo['qrcode_id'],
                    'openid' => $openid,
                    'uid' => $uid,
                    'business_name' => $businessName,
                    'store_name' => $storeName,
                    'face_watermark_enabled' => $faceWatermarkEnabled,
                    'mp_nickname' => $mpNickname,
                    'is_free_pick' => $portraitInfo['is_free_pick'] ?? 0,
                    'is_subscribed' => $isSubscribed,
                    'mp_qrcode' => $mpInfo['qrcode'] ?? '',
                    'mp_nickname_for_subscribe' => $mpInfo['nickname'] ?? '',
                ],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * "不是我的照片" - 解除人像与当前微信用户的关联
     * POST /api/ai-travel-photo/pick/not_my_photos
     * 
     * 用户扫码后发现照片不是自己的，点击后可解除绑定并重新扫码
     */
    public function not_my_photos(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $portraitId = (int)$this->request->post('portrait_id', 0);
        $qr = $this->request->post('qr', '');

        // 如果未传 portrait_id，通过 qr 参数反查
        if ($portraitId <= 0 && !empty($qr)) {
            try {
                $portraitInfo = $this->pickService->getPortraitByQrcode($qr);
                $portraitId = $portraitInfo['portrait_id'];
            } catch (\Exception $e) {
                return json(['code' => 500, 'msg' => $e->getMessage()]);
            }
        }

        if ($portraitId <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        // 解除人像与用户的关联
        $this->pickService->disassociateUserFromPortrait($portraitId, $openid);

        // 清除选片session，下次扫码将重新授权
        Session::delete('pick_openid');

        return json(['code' => 200, 'msg' => '已解除关联，请重新扫描您的专属二维码']);
    }

    /**
     * "我要重拍" - 用户上传新人像并自动关联合成
     * POST /api/ai-travel-photo/pick/retake_upload
     * 
     * 接收 multipart/form-data 上传的图片文件
     * 自动创建人像记录、触发合成队列、关联当前用户
     */
    public function retake_upload(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        // 接收参数
        $aid = (int)$this->request->post('aid', 0);
        $bid = (int)$this->request->post('bid', 0);
        $mdid = (int)$this->request->post('mdid', 0);
        $oldPortraitId = (int)$this->request->post('portrait_id', 0);

        if ($aid <= 0 || $bid <= 0) {
            return json(['code' => 400, 'msg' => '缺少平台或商家参数']);
        }

        // 使用原生 $_FILES 验证上传文件（避免 ThinkPHP file() 方法依赖 finfo 扩展）
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE   => '图片大小超过服务器限制',
                UPLOAD_ERR_FORM_SIZE  => '图片大小超过表单限制',
                UPLOAD_ERR_PARTIAL    => '图片上传不完整',
                UPLOAD_ERR_NO_FILE    => '请上传图片文件',
                UPLOAD_ERR_NO_TMP_DIR => '服务器临时目录不存在',
                UPLOAD_ERR_CANT_WRITE => '服务器写入失败',
            ];
            $errCode = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
            $msg = $uploadErrors[$errCode] ?? '文件上传失败';
            return json(['code' => 400, 'msg' => $msg]);
        }

        $tmpName  = $_FILES['image']['tmp_name'];
        $origName = $_FILES['image']['name'];
        $fileSize = $_FILES['image']['size'];

        // 验证文件扩展名
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            return json(['code' => 400, 'msg' => '仅支持JPG、PNG格式图片']);
        }

        // 验证文件大小（最大10MB）
        if ($fileSize > 10 * 1024 * 1024) {
            return json(['code' => 400, 'msg' => '图片大小不能超过10MB']);
        }

        // 验证是否为有效图片文件
        $imageInfo = @getimagesize($tmpName);
        if (!$imageInfo) {
            return json(['code' => 400, 'msg' => '不是有效的图片文件']);
        }

        try {
            // 查询上传者信息
            $member = \think\facade\Db::name('member')
                ->where('aid', $aid)
                ->where('mpopenid', $openid)
                ->find();
            $memberId   = $member ? (int)$member['id'] : 0;
            $memberName = $member ? ($member['nickname'] ?: '微信用户') : '微信用户';

            // 构建文件信息
            $fileInfo = [
                'name'     => $origName,
                'tmp_name' => $tmpName,
                'type'     => $imageInfo['mime'],
                'size'     => $fileSize,
            ];

            // 构建上传参数：用户上传类型
            $params = [
                'aid'              => $aid,
                'uid'              => $memberId,
                'bid'              => $bid,
                'mdid'             => $mdid,
                'device_id'        => 0,
                'type'             => AiTravelPhotoPortrait::TYPE_USER, // 2=用户上传
                'uploader_account' => $memberName,
                'desc'             => '用户H5选片重拍',
                'tags'             => '',
            ];

            // 调用服务层上传（自动触发完整异步链：抠图→特征→标签→合成→二维码）
            $portraitService = new AiTravelPhotoPortraitService();
            $result = $portraitService->uploadPortrait($fileInfo, $params);

            $newPortraitId = (int)$result['portrait_id'];

            // 解除旧人像关联
            if ($oldPortraitId > 0) {
                $this->pickService->disassociateUserFromPortrait($oldPortraitId, $openid);
            }

            // 关联新人像到当前用户
            $this->pickService->associateUserToPortrait($newPortraitId, $openid);

            \think\facade\Log::info('选片重拍上传成功', [
                'old_portrait_id' => $oldPortraitId,
                'new_portrait_id' => $newPortraitId,
                'openid' => substr($openid, 0, 8) . '***',
                'status' => $result['status'] ?? 'unknown',
            ]);

            return json([
                'code' => 200,
                'msg' => $result['status'] === 'exists' ? '图片已存在，已关联到您的账号' : '上传成功，正在为您合成新照片...',
                'data' => [
                    'portrait_id' => $newPortraitId,
                    'status' => $result['status'] ?? 'success',
                ],
            ]);

        } catch (\Exception $e) {
            \think\facade\Log::error('选片重拍上传异常：' . $e->getMessage());
            return json([
                'code' => 500,
                'msg' => '上传失败：' . $e->getMessage(),
            ]);
        }
    }

    /**
     * 启动微信OAuth授权（前端重定向用）
     * GET /api/ai-travel-photo/pick/start_oauth?qr=xxx
     * 门店模式: GET /api/ai-travel-photo/pick/start_oauth?mode=store&bid=X&mdid=Y
     */
    public function start_oauth(): Response
    {
        $mode = $this->request->get('mode', '');
        $qr = $this->request->get('qr', '');
        $bid = $this->request->get('bid/d', 0);
        $mdid = $this->request->get('mdid/d', 0);

        if ($mode !== 'store' && empty($qr)) {
            return json(['code' => 400, 'msg' => '缺少参数']);
        }

        try {
            $aid = 0;
            if ($mode === 'store' && $bid > 0) {
                // 门店模式：从bid反查aid
                $business = \think\facade\Db::name('business')->where('id', $bid)->find();
                $aid = $business ? (int)$business['aid'] : 0;
            } else {
                $portraitInfo = $this->pickService->getPortraitByQrcode($qr);
                $aid = $portraitInfo['aid'];
            }

            if (!$aid) {
                return json(['code' => 400, 'msg' => '无法确定平台信息']);
            }

            $wxset = \think\facade\Db::name('admin_setapp_mp')->where('aid', $aid)->find();
            $appid = $wxset['appid'] ?? '';

            if (empty($appid)) {
                return json(['code' => 500, 'msg' => '微信配置缺失']);
            }

            $callbackUrl = $this->request->domain() . '/index.php?s=/api/ai_travel_photo/pick/oauth_callback';
            $redirectUri = urlencode($callbackUrl);

            // state 区分模式：qr_xxx 或 store_{aid}_{bid}_{mdid}
            $state = ($mode === 'store') ? "store_{$aid}_{$bid}_{$mdid}" : 'qr_' . $qr;

            $oauthUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirectUri}&response_type=code&scope=snsapi_base&state={$state}#wechat_redirect";

            return redirect($oauthUrl);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 微信OAuth回调
     * GET /api/ai-travel-photo/pick/oauth_callback?code=xxx&state=qr_xxx
     * 门店模式state: store_{aid}_{bid}_{mdid}
     */
    public function oauth_callback(): Response
    {
        $code = $this->request->get('code', '');
        $state = $this->request->get('state', '');

        if (empty($code)) {
            return json(['code' => 400, 'msg' => '授权失败，缺少code']);
        }

        // 解析state：区分qr模式和store模式
        $isStoreMode = false;
        $qrCode = '';
        $storeAid = 0;
        $storeBid = 0;
        $storeMdid = 0;

        if (strpos($state, 'store_') === 0) {
            $isStoreMode = true;
            if (preg_match('/^store_(\d+)_(\d+)_(\d+)$/', $state, $m)) {
                $storeAid = (int)$m[1];
                $storeBid = (int)$m[2];
                $storeMdid = (int)$m[3];
            }
        } elseif (strpos($state, 'qr_') === 0) {
            $qrCode = substr($state, 3);
        }

        try {
            // 获取aid以确定正确的微信配置
            $aid = 0;
            if ($isStoreMode) {
                $aid = $storeAid;
            } else {
                $portraitInfo = $this->pickService->getPortraitByQrcode($qrCode);
                $aid = $portraitInfo['aid'];
            }

            // 获取微信配置
            $wxset = \think\facade\Db::name('admin_setapp_mp')->where('aid', $aid)->find();
            $appid = $wxset['appid'] ?? '';
            $appsecret = $wxset['appsecret'] ?? '';

            // 用code换取openid
            $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecret}&code={$code}&grant_type=authorization_code";

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);
            $openid = $result['openid'] ?? '';

            if (empty($openid)) {
                return json(['code' => 500, 'msg' => '获取OpenID失败']);
            }

            // 存入Session
            Session::set('pick_openid', $openid);

            // 重定向
            if ($isStoreMode) {
                $pickUrl = $this->request->domain() . '/public/pick/index.html?mode=store&bid=' . $storeBid . '&mdid=' . $storeMdid;
            } else {
                $pickUrl = $this->request->domain() . '/public/pick/index.html?qr=' . $qrCode;
            }
            return redirect($pickUrl);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 门店模式选片入口
     * GET /api/ai_travel_photo/pick/store_index?bid=X&mdid=Y
     * 用户重复扫码时，直接进入选片页展示所有成片
     */
    public function store_index(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 302, 'msg' => '需要微信授权', 'data' => ['need_auth' => true]]);
        }

        $bid = (int)$this->request->get('bid', 0);
        $mdid = (int)$this->request->get('mdid', 0);

        if (!$bid || !$mdid) {
            return json(['code' => 400, 'msg' => '缺少门店参数']);
        }

        try {
            // 获取商家信息
            $business = \think\facade\Db::name('business')->where('id', $bid)->find();
            $businessName = $business ? ($business['name'] ?: '') : '';
            $aid = $business ? (int)$business['aid'] : 0;
            $faceWatermarkEnabled = $business ? intval($business['ai_pick_face_watermark_enabled'] ?? 0) : 0;

            // 获取门店名称和免费选片设置
            $storeName = '';
            $isFreePick = 0;
            if ($mdid > 0) {
                $store = \think\facade\Db::name('mendian')->where('id', $mdid)->field('name,is_free_pick')->find();
                if ($store) {
                    $storeName = $store['name'] ?: '';
                    $isFreePick = (int)($store['is_free_pick'] ?? 0);
                }
            }

            // 获取公众号昵称
            $mpNickname = '';
            if ($faceWatermarkEnabled && $aid > 0) {
                $mpNickname = \think\facade\Db::name('admin_setapp_mp')->where('aid', $aid)->value('nickname') ?: '';
            }

            // 查找该用户在该门店的所有已合成人像
            $storeResults = $this->pickService->getStoreResultListByOpenid($bid, $mdid, $openid);

            // 通过openid反查member获取uid
            $member = \think\facade\Db::name('member')
                ->where('aid', $aid)
                ->where('mpopenid', $openid)
                ->find();
            $uid = $member ? (int)$member['id'] : 0;

            // 关注状态检测：二级策略
            $isSubscribed = $this->checkSubscribeStatus($aid, $openid, $member);

            // 获取公众号信息（用于未关注引导弹层）
            $mpInfo = $this->getMpSubscribeInfo($aid);

            return json([
                'code' => 200,
                'msg' => '成功',
                'data' => [
                    'portrait_ids' => $storeResults['portrait_ids'],
                    'aid' => $aid,
                    'bid' => $bid,
                    'mdid' => $mdid,
                    'openid' => $openid,
                    'uid' => $uid,
                    'business_name' => $businessName,
                    'store_name' => $storeName,
                    'face_watermark_enabled' => $faceWatermarkEnabled,
                    'mp_nickname' => $mpNickname,
                    'is_free_pick' => $isFreePick,
                    'is_subscribed' => $isSubscribed,
                    'mp_qrcode' => $mpInfo['qrcode'] ?? '',
                    'mp_nickname_for_subscribe' => $mpInfo['nickname'] ?? '',
                    'total_results' => $storeResults['total'],
                ],
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 门店模式成片列表
     * GET /api/ai_travel_photo/pick/store_results?bid=X&mdid=Y
     * 返回该openid在该门店的所有成片
     */
    public function store_results(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $bid = (int)$this->request->get('bid', 0);
        $mdid = (int)$this->request->get('mdid', 0);

        if (!$bid || !$mdid) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        try {
            $data = $this->pickService->getStoreResultListByOpenid($bid, $mdid, $openid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }


    /**
     * B端管理：门店所有待选片列表（含批量上传）
     * GET /api/ai_travel_photo/pick/store_all_results?bid=X&mdid=Y
     */
    public function store_all_results(): Response
    {
        // B端管理权限验证
        if (!session("?ADMIN_LOGIN")) {
            return json(['code' => 401, 'msg' => '请重新登录']);
        }
        $bid = (int)$this->request->get('bid', 0);
        $mdid = (int)$this->request->get('mdid', 0);

        if (!$bid || !$mdid) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        try {
            $data = $this->pickService->getStoreAllPortraits($bid, $mdid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }
    /**
     * 获取成片列表
     * GET /api/ai-travel-photo/pick/results?portrait_id=xxx
     */
    public function results(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $portraitId = (int)$this->request->get('portrait_id', 0);
        $bid = (int)$this->request->get('bid', 0);
        $aid = (int)$this->request->get('aid', 0);
        if ($portraitId <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        // 门店模式：传入portrait_ids时，使用所有portrait的结果
        $portraitIdsJson = $this->request->get('portrait_ids', '');
        $portraitIds = [];
        if (!empty($portraitIdsJson)) {
            $portraitIds = json_decode($portraitIdsJson, true);
            if (!is_array($portraitIds)) $portraitIds = [];
        }

        try {
            if (!empty($portraitIds)) {
                // 门店模式：聚合多个portrait的成片
                $results = \app\model\AiTravelPhotoResult::whereIn('portrait_id', array_map('intval', $portraitIds))
                    ->where('status', \app\model\AiTravelPhotoResult::STATUS_NORMAL)
                    ->field('id, type, url, thumbnail_url, watermark_url, width, height, create_time')
                    ->order('create_time DESC, id DESC')
                    ->select()
                    ->toArray();
                $data = [
                    'portrait_ids' => array_map('intval', $portraitIds),
                    'results' => $this->pickService->formatResultItemsPublic($results),
                    'total' => count($results),
                    'similar_results' => [],
                    'similar_total' => 0,
                ];
            } else {
                $data = $this->pickService->getResultList($portraitId, $bid, $aid);
            }
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取套餐列表
     * GET /api/ai-travel-photo/pick/packages?bid=xxx
     */
    public function packages(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $bid = (int)$this->request->get('bid', 0);
        if ($bid <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        try {
            $data = $this->pickService->getPackageList($bid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 套餐推荐
     * POST /api/ai-travel-photo/pick/recommend
     */
    public function recommend(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $selectedCount = (int)$this->request->post('selected_count', 0);
        $bid = (int)$this->request->post('bid', 0);
        $videoCount = (int)$this->request->post('video_count', 0);

        if ($selectedCount <= 0) {
            return json(['code' => 400, 'msg' => '请至少选择一张']);
        }
        if ($bid <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        try {
            $data = $this->pickService->recommendPackage($selectedCount, $bid, $videoCount);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 创建选片订单
     * POST /api/ai-travel-photo/pick/order
     */
    public function order(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $params = $this->request->post();
        $params['openid'] = $openid;

        // 通过openid反查member获取uid
        $aid = (int)($params['aid'] ?? 0);
        if ($aid > 0) {
            $member = \think\facade\Db::name('member')
                ->where('aid', $aid)
                ->where('mpopenid', $openid)
                ->find();
            if ($member) {
                $params['uid'] = (int)$member['id'];
            }
        }

        // 参数验证
        if (empty($params['portrait_id']) && empty($params['portrait_ids'])) {
            return json(['code' => 400, 'msg' => '人像ID不能为空']);
        }
        if (empty($params['result_ids']) || !is_array($params['result_ids'])) {
            return json(['code' => 400, 'msg' => '请选择成片']);
        }

        try {
            $data = $this->pickService->createPickOrder($params);
            return json(['code' => 200, 'msg' => '订单创建成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 发起支付
     * POST /api/ai-travel-photo/pick/pay
     */
    public function pay(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $orderNo = $this->request->post('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '订单号不能为空']);
        }

        try {
            $data = $this->pickService->createPayment($orderNo, $openid);
            return json(['code' => 200, 'msg' => '支付参数获取成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 查询支付状态
     * GET /api/ai-travel-photo/pick/pay_status?order_no=xxx
     * 
     * 注意：此接口不要求session鉴权，因为从/h5/cashier.html跳转后session可能丢失
     * order_no本身具有足够的唯一性和不可猜测性
     */
    public function pay_status(): Response
    {
        $orderNo = $this->request->get('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '订单号不能为空']);
        }

        try {
            $data = $this->pickService->getPayStatus($orderNo);
            return json(['code' => 200, 'msg' => '查询成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取下载列表
     * GET /api/ai-travel-photo/pick/downloads?order_no=xxx
     */
    public function downloads(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $orderNo = $this->request->get('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '订单号不能为空']);
        }

        try {
            $data = $this->pickService->getDownloadList($orderNo, $openid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 记录下载
     * POST /api/ai-travel-photo/pick/record_download
     */
    public function record_download(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $goodsId = (int)$this->request->post('goods_id', 0);
        if ($goodsId <= 0) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $result = $this->pickService->recordDownload($goodsId, $openid);
        return json(['code' => 200, 'msg' => '成功', 'data' => ['result' => $result]]);
    }

    /**
     * 收银台页面（服务端渲染）
     * GET /api/ai-travel-photo/pick/cashier?order_no=xxx&qr=xxx
     *
     * 此页面URL在 /index.php 路由下，属于已注册的微信JSAPI支付授权目录
     * 解决 /public/pick/index.html 不在授权目录中导致的"URL未注册"问题
     */
    public function cashier(): Response
    {
        $orderNo = $this->request->get('order_no', '');
        $qr = $this->request->get('qr', '');
        $mode = $this->request->get('mode', '');
        $bid = $this->request->get('bid/d', 0);
        $mdid = $this->request->get('mdid/d', 0);

        if (empty($orderNo)) {
            return response('参数错误', 400);
        }

        $openid = $this->getOpenid();
        if (empty($openid)) {
            return response('未授权，请重新扫码', 401);
        }

        // 获取支付参数
        $payError = '';
        $jsApiParams = '{}';
        try {
            $data = $this->pickService->createPayment($orderNo, $openid);
            $jsApiParams = json_encode($data['js_api_params'] ?? [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            $payError = $e->getMessage();
        }

        // 构建返回选片页URL：支持门店模式
        if ($mode === 'store' && $bid > 0 && $mdid > 0) {
            $pickUrl = $this->request->domain() . '/public/pick/index.html?mode=store&bid=' . $bid . '&mdid=' . $mdid;
        } else {
            $pickUrl = $this->request->domain() . '/public/pick/index.html' . ($qr ? '?qr=' . $qr : '');
        }
        $downloadUrl = $this->request->domain() . '/public/pick/download.html?order_no=' . $orderNo;
        $payStatusUrl = $this->request->domain() . '/index.php?s=/api/ai_travel_photo/pick/pay_status&order_no=' . $orderNo;

        // 渲染自包含的收银台HTML
        $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>支付中</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f5f5f5;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#333}
.card{background:#fff;border-radius:16px;padding:40px 30px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:320px;width:90%}
.spinner{width:40px;height:40px;border:3px solid #eee;border-top-color:#07c160;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 16px}
@keyframes spin{to{transform:rotate(360deg)}}
.title{font-size:18px;font-weight:600;margin-bottom:8px}
.desc{font-size:14px;color:#999;margin-bottom:20px}
.btn{display:inline-block;background:#07c160;color:#fff;border:none;padding:12px 32px;border-radius:24px;font-size:15px;cursor:pointer;text-decoration:none}
.btn:active{opacity:.8}
.btn-ghost{background:#fff;color:#666;border:1px solid #ddd}
.error{color:#e64340}
.gap{height:12px}
</style>
</head>
<body>
<div class="card" id="payCard">
  <div class="spinner" id="spinner"></div>
  <div class="title" id="title">正在发起支付...</div>
  <div class="desc" id="desc">请在弹出的微信支付窗口中完成支付</div>
  <div style="display:none" id="btnArea">
    <a class="btn" id="retryBtn" href="javascript:void(0)" onclick="retryPay()">重新支付</a>
    <div class="gap"></div>
    <a class="btn btn-ghost" href="{$pickUrl}">返回选片</a>
  </div>
</div>
<script>
var PAY_PARAMS = {$jsApiParams};
var PAY_ERROR = '{$payError}';
var DOWNLOAD_URL = '{$downloadUrl}';
var PAY_STATUS_URL = '{$payStatusUrl}';
var PICK_URL = '{$pickUrl}';

function invokePay() {
  if (PAY_ERROR) {
    showError(PAY_ERROR);
    return;
  }
  if (typeof WeixinJSBridge === 'undefined') {
    if (document.addEventListener) {
      document.addEventListener('WeixinJSBridgeReady', function(){ invokePay(); }, false);
    }
    return;
  }
  WeixinJSBridge.invoke('getBrandWCPayRequest', PAY_PARAMS, function(res) {
    if (res.err_msg === 'get_brand_wcpay_request:ok') {
      document.getElementById('spinner').style.display = 'block';
      document.getElementById('title').textContent = '支付成功';
      document.getElementById('title').className = '';
      document.getElementById('desc').textContent = '正在跳转...';
      document.getElementById('btnArea').style.display = 'none';
      pollAndRedirect();
    } else if (res.err_msg === 'get_brand_wcpay_request:cancel') {
      showCancelled();
    } else {
      showError('支付失败，请重试');
    }
  });
}

function showError(msg) {
  document.getElementById('spinner').style.display = 'none';
  document.getElementById('title').textContent = msg;
  document.getElementById('title').className = 'title error';
  document.getElementById('desc').textContent = '';
  document.getElementById('btnArea').style.display = 'block';
}

function showCancelled() {
  document.getElementById('spinner').style.display = 'none';
  document.getElementById('title').textContent = '支付已取消';
  document.getElementById('title').className = 'title';
  document.getElementById('desc').textContent = '您可以重新发起支付';
  document.getElementById('btnArea').style.display = 'block';
}

function retryPay() {
  document.getElementById('spinner').style.display = 'block';
  document.getElementById('title').textContent = '正在发起支付...';
  document.getElementById('title').className = 'title';
  document.getElementById('desc').textContent = '请在弹出的微信支付窗口中完成支付';
  document.getElementById('btnArea').style.display = 'none';
  setTimeout(function(){ invokePay(); }, 300);
}

function pollAndRedirect() {
  var attempts = 0;
  var timer = setInterval(function() {
    attempts++;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', PAY_STATUS_URL, true);
    xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
    xhr.onload = function() {
      try {
        var res = JSON.parse(xhr.responseText);
        if (res.code === 200 && res.data && res.data.status === 'paid') {
          clearInterval(timer);
          window.location.href = DOWNLOAD_URL;
        }
      } catch(e) {}
    };
    xhr.send();
    if (attempts >= 15) {
      clearInterval(timer);
      window.location.href = DOWNLOAD_URL;
    }
  }, 2000);
}

// 页面加载后自动发起支付
invokePay();
</script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * 我的订单列表
     * GET /api/ai-travel-photo/pick/my_orders
     * 根据当前session中的openid查询已支付订单
     */
    public function my_orders(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        try {
            $list = $this->pickService->getMyOrders($openid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $list]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取公众号信息（关注引导）
     * GET /api/ai-travel-photo/pick/mp_info?order_no=xxx
     */
    public function mp_info(): Response
    {
        $orderNo = $this->request->get('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '缺少订单号']);
        }
        try {
            $order = \app\model\AiTravelPhotoOrder::where('order_no', $orderNo)->find();
            if (!$order) {
                return json(['code' => 404, 'msg' => '订单不存在']);
            }
            $data = $this->pickService->getMpInfo($order->aid);
            return json(['code' => 200, 'msg' => '成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 一键生成视频（FFmpeg幻灯片）
     * POST /api/ai-travel-photo/pick/gen_video
     */
    public function gen_video(): Response
    {
        $orderNo = $this->request->post('order_no', '') ?: $this->request->get('order_no', '');
        if (empty($orderNo)) {
            return json(['code' => 400, 'msg' => '缺少订单号']);
        }
        try {
            $data = $this->pickService->generateSlideshow($orderNo);
            return json(['code' => 200, 'msg' => '视频生成成功', 'data' => $data]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 微信支付异步回调
     * POST /api/ai-travel-photo/pick/notify
     */
    public function notify(): Response
    {
        $xml = file_get_contents('php://input');
        \think\facade\Log::info('选片支付回调原始XML', ['xml' => $xml]);

        $data = $this->xmlToArray($xml);
        \think\facade\Log::info('选片支付回调解析数据', ['data' => $data]);

        if (empty($data) || ($data['return_code'] ?? '') !== 'SUCCESS') {
            \think\facade\Log::error('选片支付回调数据异常', ['data' => $data]);
            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[数据异常]]></return_msg></xml>');
        }

        $orderNo = $data['out_trade_no'] ?? '';
        $resultCode = $data['result_code'] ?? '';

        if ($resultCode !== 'SUCCESS' || empty($orderNo)) {
            \think\facade\Log::error('选片支付回调result_code非SUCCESS', ['order_no' => $orderNo, 'result_code' => $resultCode]);
            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[支付失败]]></return_msg></xml>');
        }

        try {
            // 获取订单以确定aid，从而验签
            $order = \app\model\AiTravelPhotoOrder::where('order_no', $orderNo)->find();
            if (!$order) {
                \think\facade\Log::error('选片支付回调订单不存在', ['order_no' => $orderNo]);
                return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[订单不存在]]></return_msg></xml>');
            }

            $wxset = \think\facade\Db::name('admin_setapp_mp')->where('aid', $order->aid)->find();
            $mchKey = $wxset['wxpay_mchkey'] ?? '';

            // 验签
            if (!$this->pickService->verifyWxNotifySign($data, $mchKey)) {
                \think\facade\Log::error('选片支付回调签名验证失败', ['order_no' => $orderNo]);
                return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名验证失败]]></return_msg></xml>');
            }

            // 履约
            $result = $this->pickService->paySuccessfulfilment($orderNo, [
                'transaction_id' => $data['transaction_id'] ?? '',
                'pay_type' => 'wechat',
            ]);
            \think\facade\Log::info('选片支付回调履约结果', ['order_no' => $orderNo, 'result' => $result]);

            return response('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');
        } catch (\Exception $e) {
            \think\facade\Log::error('选片支付回调异常：' . $e->getMessage() . ' trace:' . $e->getTraceAsString());
            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[处理异常]]></return_msg></xml>');
        }
    }

    /**
     * 检查用户是否关注公众号
     * POST /api/ai_travel_photo/pick/check_subscribe
     * 
     * 前端「我已关注」按钮调用，重新检测关注状态
     */
    public function check_subscribe(): Response
    {
        $openid = $this->getOpenid();
        if (empty($openid)) {
            return json(['code' => 401, 'msg' => '未授权']);
        }

        $aid = (int)$this->request->post('aid', 0);
        if (!$aid) {
            return json(['code' => 400, 'msg' => '缺少平台参数']);
        }

        try {
            // 强制实时检测，不使用缓存
            $status = \app\common\Wechat::getUserSubscribeStatus($aid, $openid);

            if ($status['subscribe'] == 1) {
                // 更新 member 表和 Session 缓存
                \think\facade\Db::name('member')
                    ->where('aid', $aid)
                    ->where('mpopenid', $openid)
                    ->update(['subscribe' => 1, 'subscribe_time' => time()]);

                \think\facade\Session::set('pick_is_subscribed', 1);
                \think\facade\Session::set('pick_subscribe_checked', 1);

                return json(['code' => 200, 'msg' => '已关注', 'data' => ['is_subscribed' => 1]]);
            }

            \think\facade\Session::set('pick_is_subscribed', 0);
            \think\facade\Session::set('pick_subscribe_checked', 1);

            return json(['code' => 200, 'msg' => '暂未检测到关注', 'data' => ['is_subscribed' => 0]]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 关注状态二级检测
     * 
     * 第一级：读取 member.subscribe 字段（快速路径）
     * 第二级：调用微信 user/info API（实时准确）
     * 缓存：Session 存储检测结果，同一次会话复用
     *
     * @param int        $aid    公众号ID
     * @param string     $openid 用户openid
     * @param array|null $member 会员记录
     * @return int 1=已关注, 0=未关注
     */
    protected function checkSubscribeStatus(int $aid, string $openid, ?array $member): int
    {
        // Session 缓存优先（同一次会话复用）
        $cached = \think\facade\Session::get('pick_subscribe_checked');
        if ($cached) {
            return (int)\think\facade\Session::get('pick_is_subscribed', 0);
        }

        // 第一级：数据库 member.subscribe 字段
        if ($member && isset($member['subscribe']) && (int)$member['subscribe'] === 1) {
            \think\facade\Session::set('pick_is_subscribed', 1);
            \think\facade\Session::set('pick_subscribe_checked', 1);
            return 1;
        }

        // 第二级：调用微信 user/info 接口
        $status = \app\common\Wechat::getUserSubscribeStatus($aid, $openid);

        if ($status['subscribe'] == 1) {
            // 更新 member 表
            if ($member) {
                \think\facade\Db::name('member')
                    ->where('id', $member['id'])
                    ->update(['subscribe' => 1, 'subscribe_time' => time()]);
            }
            \think\facade\Session::set('pick_is_subscribed', 1);
            \think\facade\Session::set('pick_subscribe_checked', 1);
            return 1;
        }

        \think\facade\Session::set('pick_is_subscribed', 0);
        \think\facade\Session::set('pick_subscribe_checked', 1);
        return 0;
    }

    /**
     * 获取公众号关注引导信息（二维码 + 昵称）
     */
    protected function getMpSubscribeInfo(int $aid): array
    {
        return $this->pickService->getMpInfo($aid);
    }

    /**
     * XML转数组
     * 增强版：空XML元素转为空字符串而非空数组
     */
    protected function xmlToArray($xml): array
    {
        if (!$xml) return [];
        libxml_disable_entity_loader(true);
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($obj === false) return [];
        $arr = json_decode(json_encode($obj), true) ?: [];
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = '';
            }
        }
        return $arr;
    }
}
