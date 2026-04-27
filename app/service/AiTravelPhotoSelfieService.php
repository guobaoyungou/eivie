<?php
declare(strict_types=1);

namespace app\service;

use app\model\AiTravelPhotoSelfieQrcode;
use app\model\AiTravelPhotoSelfieNotify;
use app\model\AiTravelPhotoPortrait;
use app\model\AiTravelPhotoQrcode;
use app\model\AiTravelPhotoResult;
use app\common\Wechat;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;

/**
 * AI旅拍-用户自拍端服务
 * 
 * 提供二维码生成、推文配置、人脸比对路由、合成进度查询、通知推送等功能
 * 
 * Class AiTravelPhotoSelfieService
 * @package app\service
 */
class AiTravelPhotoSelfieService
{
    /**
     * 生成或获取门店永久带参二维码
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return array
     */
    public function getOrCreateQrcode(int $aid, int $bid, int $mdid): array
    {
        // 检查是否已有二维码
        $existing = AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->find();

        if ($existing && $existing->status == AiTravelPhotoSelfieQrcode::STATUS_NORMAL) {
            return [
                'id' => $existing->id,
                'qrcode_url' => $existing->qrcode_url,
                'scene_str' => $existing->scene_str,
                'create_time' => date('Y-m-d H:i:s', $existing->create_time),
                'status' => 'exists',
            ];
        }

        // 构建场景值
        $sceneStr = "selfie_bid_{$bid}_mdid_{$mdid}";

        // 调用微信公众号永久二维码API
        $accessToken = Wechat::access_token($aid, 'mp');
        if (!$accessToken) {
            throw new \Exception('公众号access_token获取失败，请检查公众号配置');
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $accessToken;
        $postData = json_encode([
            'action_name' => 'QR_LIMIT_STR_SCENE',
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
        if (!$res || empty($res['url'])) {
            $errMsg = $res['errmsg'] ?? '未知错误';
            Log::error('自拍端二维码生成失败', ['error' => $errMsg, 'aid' => $aid, 'bid' => $bid, 'mdid' => $mdid]);
            throw new \Exception('生成二维码失败: ' . $errMsg);
        }

        // 用二维码URL生成图片（复用现有createqrcode函数）
        $qrcodeUrl = createqrcode($res['url'], '', $aid);

        // 保存记录
        if ($existing) {
            $existing->scene_str = $sceneStr;
            $existing->qrcode_url = $qrcodeUrl;
            $existing->status = AiTravelPhotoSelfieQrcode::STATUS_NORMAL;
            $existing->save();
            $record = $existing;
        } else {
            $record = AiTravelPhotoSelfieQrcode::create([
                'aid' => $aid,
                'bid' => $bid,
                'mdid' => $mdid,
                'scene_str' => $sceneStr,
                'qrcode_url' => $qrcodeUrl,
                'status' => AiTravelPhotoSelfieQrcode::STATUS_NORMAL,
            ]);
        }

        return [
            'id' => $record->id,
            'qrcode_url' => $qrcodeUrl,
            'scene_str' => $sceneStr,
            'create_time' => date('Y-m-d H:i:s', $record->create_time),
            'status' => 'created',
        ];
    }

    /**
     * 获取推文配置
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return array
     */
    public function getPushConfig(int $aid, int $bid, int $mdid): array
    {
        // 优先从selfie_qrcode表获取配置
        $qrRecord = AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->find();

        // 其次从门店表获取
        $mendian = Db::name('mendian')->where('id', $mdid)->find();

        $title = '';
        $desc = '';
        $cover = '';

        if ($qrRecord) {
            $title = $qrRecord->push_title;
            $desc = $qrRecord->push_desc;
            $cover = $qrRecord->push_cover;
        }

        if (empty($title) && $mendian) {
            $title = $mendian['selfie_push_title'] ?? '';
        }
        if (empty($desc) && $mendian) {
            $desc = $mendian['selfie_push_desc'] ?? '';
        }
        if (empty($cover) && $mendian) {
            $cover = $mendian['selfie_push_cover'] ?? '';
        }

        return [
            'push_title' => $title ?: AiTravelPhotoSelfieQrcode::DEFAULT_PUSH_TITLE,
            'push_desc' => $desc ?: AiTravelPhotoSelfieQrcode::DEFAULT_PUSH_DESC,
            'push_cover' => $cover,
            'mdid' => $mdid,
        ];
    }

    /**
     * 保存推文配置
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param array $config 配置数据
     * @return bool
     */
    public function savePushConfig(int $aid, int $bid, int $mdid, array $config): bool
    {
        // 更新selfie_qrcode表
        $qrRecord = AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->find();

        if ($qrRecord) {
            $qrRecord->push_title = $config['push_title'] ?? '';
            $qrRecord->push_desc = $config['push_desc'] ?? '';
            $qrRecord->push_cover = $config['push_cover'] ?? '';
            $qrRecord->save();
        }

        // 同步更新门店表
        Db::name('mendian')->where('id', $mdid)->update([
            'selfie_push_title' => $config['push_title'] ?? '',
            'selfie_push_desc' => $config['push_desc'] ?? '',
            'selfie_push_cover' => $config['push_cover'] ?? '',
        ]);

        return true;
    }

    /**
     * 解析selfie_场景值参数
     *
     * @param string $eventKey 场景值字符串，如 selfie_bid_123_mdid_456
     * @return array ['bid' => int, 'mdid' => int]
     */
    public static function parseSelfieSceneStr(string $eventKey): array
    {
        $bid = 0;
        $mdid = 0;

        // 去掉qrscene_前缀（subscribe事件带此前缀）
        $key = str_replace('qrscene_', '', $eventKey);

        if (preg_match('/selfie_bid_(\d+)_mdid_(\d+)/', $key, $matches)) {
            $bid = (int)$matches[1];
            $mdid = (int)$matches[2];
        }

        return ['bid' => $bid, 'mdid' => $mdid];
    }

    /**
     * 处理扫码事件：更新统计并返回自拍端推文图文数据
     * 不再通过客服消息推送（易失败），改为返回图文数据由 ApiWechat 被动回复
     *
     * @param int $aid 平台ID
     * @param string $openid 用户openid
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param bool $isSubscribe 是否为关注事件
     * @return array|false  返回图文数据 ['title','description','pic','url'] 或 false
     */
    public function handleScanEvent(int $aid, string $openid, int $bid, int $mdid, bool $isSubscribe = false)
    {
        // 检查门店是否启用自拍端
        $mendian = Db::name('mendian')->where('id', $mdid)->find();
        if (!$mendian || empty($mendian['selfie_enabled'])) {
            Log::info("自拍端：门店{$mdid}未启用自拍端，静默处理");
            return false;
        }

        // 更新二维码统计
        $qrRecord = AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->find();

        if ($qrRecord) {
            $qrRecord->scan_count = $qrRecord->scan_count + 1;
            if ($isSubscribe) {
                $qrRecord->follow_count = $qrRecord->follow_count + 1;
            }
            $qrRecord->save();
        }

        // 获取推文配置
        $pushConfig = $this->getPushConfig($aid, $bid, $mdid);

        // 查询openid在该门店是否已有合成完成的成片
        $hasCompleted = false;
        try {
            $pickService = new AiTravelPhotoPickService();
            $hasCompleted = $pickService->hasCompletedResultsInStore($bid, $mdid, $openid);
        } catch (\Exception $e) {
            Log::warning('[Selfie] 扫码事件检查已有成片异常: ' . $e->getMessage());
        }

        // 构建目标URL：有成片 → pick 门店模式，无成片 → selfie 自拍页
        $domain = $this->getSiteDomain();
        if ($hasCompleted) {
            $targetUrl = $domain . '/public/pick/index.html?mode=store&bid=' . $bid . '&mdid=' . $mdid;
            $title = $pushConfig['push_title'];
            $description = '🎉 您的旅拍照片已就绪，点击浏览和选片';
            Log::info('[Selfie] 扫码事件：用户已有成片，推文指向pick门店模式', ['openid' => $openid, 'bid' => $bid, 'mdid' => $mdid]);
        } else {
            $targetUrl = $domain . '/public/selfie/index.html?aid=' . $aid . '&bid=' . $bid . '&mdid=' . $mdid;
            $title = $pushConfig['push_title'];
            $description = $pushConfig['push_desc'];
            Log::info('[Selfie] 扫码事件：用户无成片，推文指向selfie自拍页', ['openid' => $openid, 'bid' => $bid, 'mdid' => $mdid]);
        }

        // 获取封面图
        $picUrl = $pushConfig['push_cover'];
        if (empty($picUrl)) {
            $picUrl = $domain . '/static/img/ai_travel_photo_selfie_cover.png';
        }

        // 返回图文数据，由调用方使用被动回复发送
        return [
            'title' => $title,
            'description' => $description,
            'pic' => $picUrl,
            'url' => $targetUrl,
        ];
    }

    /**
     * 发送客服图文消息
     *
     * @param int $aid 平台ID
     * @param string $openid 用户openid
     * @param array $article 图文内容 ['title', 'description', 'url', 'picurl']
     * @return bool
     */
    public function sendKefuNewsMessage(int $aid, string $openid, array $article): array
    {
        try {
            $accessToken = Wechat::access_token($aid, 'mp');
            if (!$accessToken) {
                Log::error('自拍端推文推送失败：access_token获取失败 aid=' . $aid);
                return ['success' => false, 'errcode' => -1, 'errmsg' => 'access_token获取失败'];
            }

            $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $accessToken;
            $data = [
                'touser' => $openid,
                'msgtype' => 'news',
                'news' => [
                    'articles' => [$article]
                ]
            ];

            $postBody = json_encode($data, JSON_UNESCAPED_UNICODE);
            Log::info('自拍端推文推送请求: openid=' . $openid . ', url=' . ($article['url'] ?? '') . ', data=' . $postBody);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postBody,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            ]);
            $result = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($result === false) {
                Log::error('自拍端推文推送失败：curl错误 ' . $curlError);
                return ['success' => false, 'errcode' => -2, 'errmsg' => 'curl错误: ' . $curlError];
            }

            $res = json_decode($result, true);
            if (isset($res['errcode']) && $res['errcode'] != 0) {
                $errcode = (int)($res['errcode'] ?? 0);
                $errmsg = $res['errmsg'] ?? '';
                Log::error('自拍端推文推送失败：errcode=' . $errcode . ', errmsg=' . $errmsg . ', openid=' . $openid . ', response=' . $result);
                return ['success' => false, 'errcode' => $errcode, 'errmsg' => $errmsg];
            }

            Log::info('自拍端推文推送成功: openid=' . $openid . ', aid=' . $aid);
            return ['success' => true, 'errcode' => 0, 'errmsg' => 'ok'];
        } catch (\Exception $e) {
            Log::error('自拍端推文推送异常: ' . $e->getMessage());
            return ['success' => false, 'errcode' => -3, 'errmsg' => $e->getMessage()];
        }
    }

    /**
     * 发送模板消息通知（客服消息48小时窗口过期时的回退方案）
     * 优先用 tmpl_shenhe（审核结果通知），其次 tmpl_formsub（表单提交通知）
     *
     * @param int $aid 平台ID
     * @param string $openid 用户openid
     * @param string $pickUrl 选片链接
     * @param string $storeName 门店名称
     * @return bool
     */
    public function sendTemplateNotification(int $aid, string $openid, string $pickUrl, string $storeName = ''): bool
    {
        try {
            $accessToken = Wechat::access_token($aid, 'mp');
            if (!$accessToken) {
                Log::error('模板消息回退失败：access_token获取失败 aid=' . $aid);
                return false;
            }

            // 获取模板配置
            $tmplSet = Db::name('mp_tmplset')->where('aid', $aid)->find();
            if (!$tmplSet) {
                Log::warning('模板消息回退失败：未找到模板配置 aid=' . $aid);
                return false;
            }

            // 构建候选模板列表（按优先级），某个模板可能已从微信后台删除(40037)，依次尝试
            // 注意：微信模板标题由模板库固定，无法修改。通过优化内容字段弥补，让用户明确知道这是选片通知
            $storeText = $storeName ? '在「' . $storeName . '」' : '';
            $remarkText = '👆 点击此消息即可浏览您' . $storeText . '的精美旅拍照片，挑选心仪的照片购买收藏！';
            $candidates = [];

            // 候选1: tmpl_formsub（表单提交通知）- 字段：first, keyword1(表单来源), keyword2(提交时间), remark
            if (!empty($tmplSet['tmpl_formsub'])) {
                $candidates[] = [
                    'template_id' => trim($tmplSet['tmpl_formsub']),
                    'name' => 'tmpl_formsub',
                    'data' => [
                        'first' => ['value' => '🎉 您的旅拍照片已合成完成，快来选片吧！', 'color' => '#FF6B35'],
                        'keyword1' => ['value' => 'AI旅拍选片通知'],
                        'keyword2' => ['value' => date('Y-m-d H:i')],
                        'remark' => ['value' => $remarkText, 'color' => '#173177'],
                    ],
                ];
            }

            // 候选2: tmpl_shenhe（审核结果通知）- 字段：first, keyword1, keyword2, remark
            if (!empty($tmplSet['tmpl_shenhe'])) {
                $candidates[] = [
                    'template_id' => trim($tmplSet['tmpl_shenhe']),
                    'name' => 'tmpl_shenhe',
                    'data' => [
                        'first' => ['value' => '🎉 您的旅拍照片已合成完成，快来选片吧！', 'color' => '#FF6B35'],
                        'keyword1' => ['value' => 'AI旅拍选片通知'],
                        'keyword2' => ['value' => '已完成'],
                        'remark' => ['value' => $remarkText, 'color' => '#173177'],
                    ],
                ];
            }

            // 候选3: tmpl_collagesuccess（拼团成功通知）- 字段：first, keyword1(商品名称), keyword2(团长), keyword3(成团人数), remark
            if (!empty($tmplSet['tmpl_collagesuccess'])) {
                $candidates[] = [
                    'template_id' => trim($tmplSet['tmpl_collagesuccess']),
                    'name' => 'tmpl_collagesuccess',
                    'data' => [
                        'first' => ['value' => '🎉 您的旅拍照片已合成完成，快来选片吧！', 'color' => '#FF6B35'],
                        'keyword1' => ['value' => 'AI旅拍选片通知'],
                        'keyword2' => ['value' => $storeName ?: '旅拍门店'],
                        'keyword3' => ['value' => '1'],
                        'remark' => ['value' => $remarkText, 'color' => '#173177'],
                    ],
                ];
            }

            if (empty($candidates)) {
                Log::warning('模板消息回退失败：未配置可用的模板 ID');
                return false;
            }

            $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $accessToken;

            // 依次尝试每个候选模板，直到成功或全部失败
            foreach ($candidates as $candidate) {
                $templateId = $candidate['template_id'];
                $data = [
                    'touser' => $openid,
                    'template_id' => $templateId,
                    'url' => $pickUrl,
                    'data' => $candidate['data'],
                ];

                $postBody = json_encode($data, JSON_UNESCAPED_UNICODE);
                Log::info('模板消息回退请求: openid=' . $openid . ', template=' . $candidate['name'] . ', template_id=' . $templateId . ', url=' . $pickUrl);

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postBody,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                ]);
                $result = curl_exec($ch);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($result === false) {
                    Log::error('模板消息回退curl错误: ' . $curlError . ', template=' . $candidate['name']);
                    continue;
                }

                $res = json_decode($result, true);
                $errcode = (int)($res['errcode'] ?? 0);

                if ($errcode == 0) {
                    Log::info('模板消息回退成功: openid=' . $openid . ', template=' . $candidate['name'] . ', template_id=' . $templateId);
                    return true;
                }

                // 40037=invalid template_id, 40036=invalid template_id size - 模板已删除，尝试下一个
                if ($errcode == 40037 || $errcode == 40036) {
                    Log::warning('模板消息回退: 模板ID无效(已从微信后台删除), template=' . $candidate['name'] . ', errcode=' . $errcode . ', 尝试下一个模板');
                    continue;
                }

                // 其他错误（如47001格式错误等），也尝试下一个
                Log::error('模板消息回退失败: template=' . $candidate['name'] . ', errcode=' . $errcode . ', errmsg=' . ($res['errmsg'] ?? '') . ', response=' . $result);
            }

            Log::error('模板消息回退: 所有候选模板均失败, openid=' . $openid);
            return false;
        } catch (\Exception $e) {
            Log::error('模板消息回退异常: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 人脸特征比对：搜索门店成片库
     *
     * 优化④：多Gallery比对策略
     * 匹配时按 user_openid 聚合同用户的所有向量，取最小距离作为该用户的匹配分数。
     * 这样即使单个向量略微偏离，只要用户的任一向量匹配成功即可命中。
     *
     * @param array $faceEmbedding 人脸特征向量
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return array|null 匹配到则返回 ['portrait_ids' => [...], 'pick_url' => '...']
     */
    public function matchFaceInStore(array $faceEmbedding, int $aid, int $bid, int $mdid): ?array
    {
        $matchedPortraitIds = [];
        $distanceThreshold = 0.20; // L2距离<=0.2，对应余弦相似度>=98%

        // ===== 优化④: 多Gallery比对 =====
        // 收集所有候选匹配，按 user_openid 聚合取最小距离
        $candidateHits = []; // portrait_id => ['distance' => float, 'user_openid' => string]

        // 优先尝试Milvus
        try {
            $milvusService = new MilvusService();
            if ($milvusService->isHealthy()) {
                $searchResults = $milvusService->search($faceEmbedding, 50);
                if (!empty($searchResults)) {
                    foreach ($searchResults as $result) {
                        $distance = $result['distance'] ?? 999;
                        // 放宽初筛门槛至 0.35，后续按用户聚合取最小距离再判断
                        if ($distance <= 0.35) {
                            $portraitId = $result['portrait_id'] ?? ($result['id'] ?? 0);
                            if ($portraitId > 0) {
                                $portrait = Db::name('ai_travel_photo_portrait')
                                    ->where('id', $portraitId)
                                    ->where('bid', $bid)
                                    ->where('status', 1)
                                    ->field('id, user_openid')
                                    ->find();
                                if ($portrait) {
                                    $candidateHits[$portraitId] = [
                                        'distance' => (float)$distance,
                                        'user_openid' => $portrait['user_openid'] ?? '',
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Milvus搜索失败，使用MySQL备用', ['error' => $e->getMessage()]);
        }

        // MySQL备用方案
        if (empty($candidateHits)) {
            $candidateHits = $this->mysqlFaceMatchMultiGallery($faceEmbedding, $bid, 0.35);
        }

        if (empty($candidateHits)) {
            return null;
        }

        // 按 user_openid 聚合，取每个用户的最小距离
        $userBestDistance = []; // user_key => ['min_distance' => float, 'portrait_ids' => []]
        foreach ($candidateHits as $portraitId => $hit) {
            // 用 user_openid 作为聚合键，无openid的用 portrait_id 本身
            $userKey = !empty($hit['user_openid']) ? 'user_' . $hit['user_openid'] : 'portrait_' . $portraitId;
            if (!isset($userBestDistance[$userKey])) {
                $userBestDistance[$userKey] = [
                    'min_distance' => $hit['distance'],
                    'portrait_ids' => [$portraitId],
                ];
            } else {
                $userBestDistance[$userKey]['portrait_ids'][] = $portraitId;
                if ($hit['distance'] < $userBestDistance[$userKey]['min_distance']) {
                    $userBestDistance[$userKey]['min_distance'] = $hit['distance'];
                }
            }
        }

        // 筛选最小距离 <= 门槛的用户
        foreach ($userBestDistance as $userKey => $data) {
            if ($data['min_distance'] <= $distanceThreshold) {
                foreach ($data['portrait_ids'] as $pid) {
                    $matchedPortraitIds[] = $pid;
                }
            }
        }

        // 去重
        $matchedPortraitIds = array_unique($matchedPortraitIds);

        if (empty($matchedPortraitIds)) {
            return null;
        }

        // 检查匹配的人像是否有已完成的成片
        $resultCount = AiTravelPhotoResult::whereIn('portrait_id', $matchedPortraitIds)
            ->where('status', AiTravelPhotoResult::STATUS_NORMAL)
            ->count();

        if ($resultCount == 0) {
            return null;
        }

        // 获取选片二维码（取第一个匹配的人像）
        $mainPortraitId = $matchedPortraitIds[0];
        $qrcode = AiTravelPhotoQrcode::where('portrait_id', $mainPortraitId)
            ->where('status', 1)
            ->find();

        if (!$qrcode) {
            // 自动为匹配的人像生成选片二维码
            try {
                $qrcodeService = new AiTravelPhotoQrcodeService();
                $qrResult = $qrcodeService->generateQrcode($mainPortraitId);
                $qrcodeStr = $qrResult['qrcode'] ?? '';
            } catch (\Exception $e) {
                Log::error('自动生成选片二维码失败', ['portrait_id' => $mainPortraitId, 'error' => $e->getMessage()]);
                return null;
            }
        } else {
            $qrcodeStr = $qrcode->qrcode;
        }

        $domain = $this->getSiteDomain();
        $pickUrl = $domain . '/public/pick/index.html?qr=' . urlencode($qrcodeStr);

        return [
            'portrait_ids' => $matchedPortraitIds,
            'pick_url' => $pickUrl,
            'result_count' => $resultCount,
        ];
    }

    /**
     * 将用户 openid 关联到匹配的门店人像记录
     *
     * 关联规则：
     * - 仅更新 user_openid 为空的记录（首次关联）
     * - 同一用户再次匹配到同一人像，跳过（重复关联）
     * - 不同用户匹配到已关联的人像，保留首次关联，记录冲突日志
     *
     * @param array $portraitIds 匹配到的人像ID列表
     * @param string $openid 当前用户的 openid
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return array ['updated' => int, 'skipped' => int, 'conflict' => int]
     */
    public function associateOpenidToPortraits(array $portraitIds, string $openid, int $bid, int $mdid): array
    {
        $result = ['updated' => 0, 'skipped' => 0, 'conflict' => 0];

        if (empty($portraitIds) || empty($openid)) {
            return $result;
        }

        try {
            // 查询这些人像的 user_openid 状态
            $portraits = Db::name('ai_travel_photo_portrait')
                ->whereIn('id', $portraitIds)
                ->field('id, user_openid')
                ->select()
                ->toArray();

            $toUpdate = []; // 需要更新的 portrait_id
            foreach ($portraits as $portrait) {
                $existingOpenid = $portrait['user_openid'] ?? '';

                if (empty($existingOpenid)) {
                    // 首次关联：user_openid 为空，直接写入
                    $toUpdate[] = (int)$portrait['id'];
                } elseif ($existingOpenid === $openid) {
                    // 重复关联：同一用户，跳过
                    $result['skipped']++;
                } else {
                    // 冲突：不同用户匹配到同一人像，保留原 openid，记录冲突日志
                    $result['conflict']++;
                    Log::warning('OpenID关联冲突：不同用户匹配到同一人像，保留首次关联', [
                        'portrait_id' => $portrait['id'],
                        'existing_openid' => $existingOpenid,
                        'new_openid' => $openid,
                        'bid' => $bid,
                        'mdid' => $mdid,
                    ]);
                }
            }

            // 批量更新 user_openid 为空的匹配人像
            if (!empty($toUpdate)) {
                Db::name('ai_travel_photo_portrait')
                    ->whereIn('id', $toUpdate)
                    ->update(['user_openid' => $openid, 'update_time' => time()]);
                $result['updated'] = count($toUpdate);

                Log::info('OpenID关联成功：已回写user_openid至匹配人像', [
                    'openid' => $openid,
                    'portrait_ids' => $toUpdate,
                    'bid' => $bid,
                    'mdid' => $mdid,
                    'updated_count' => $result['updated'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('OpenID关联异常', [
                'openid' => $openid,
                'portrait_ids' => $portraitIds,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * MySQL备用人脸匹配（遍历门店人像计算欧氏距离）
     *
     * @param array $embedding 查询向量
     * @param int $bid 商家ID
     * @param float $threshold L2距离阈值
     * @return array 匹配的人像ID列表
     */
    private function mysqlFaceMatch(array $embedding, int $bid, float $threshold): array
    {
        $portraits = Db::name('ai_travel_photo_portrait')
            ->where('bid', $bid)
            ->where('status', 1)
            ->where('face_embedding', '<>', '')
            ->field('id, face_embedding')
            ->select()
            ->toArray();

        $matched = [];
        foreach ($portraits as $portrait) {
            $storedEmbedding = json_decode($portrait['face_embedding'], true);
            if (!is_array($storedEmbedding) || count($storedEmbedding) < 64) {
                continue;
            }

            $distance = $this->euclideanDistance($embedding, $storedEmbedding);
            if ($distance <= $threshold) {
                $matched[] = $portrait['id'];
            }
        }

        return $matched;
    }

    /**
     * MySQL备用人脸匹配（优化④：多Gallery比对策略）
     * 
     * 遍历门店人像计算欧氏距离，返回包含 user_openid 的候选匹配结果，
     * 由调用方按用户聚合取最小距离。
     *
     * @param array $embedding 查询向量
     * @param int $bid 商家ID
     * @param float $threshold L2距离初筛门槛
     * @return array portrait_id => ['distance' => float, 'user_openid' => string]
     */
    private function mysqlFaceMatchMultiGallery(array $embedding, int $bid, float $threshold): array
    {
        $portraits = Db::name('ai_travel_photo_portrait')
            ->where('bid', $bid)
            ->where('status', 1)
            ->where('face_embedding', '<>', '')
            ->where('face_embedding', '<>', '[]')
            ->field('id, face_embedding, user_openid')
            ->select()
            ->toArray();

        $candidates = [];
        foreach ($portraits as $portrait) {
            $storedEmbedding = json_decode($portrait['face_embedding'], true);
            if (!is_array($storedEmbedding) || count($storedEmbedding) < 64) {
                continue;
            }

            $distance = $this->euclideanDistance($embedding, $storedEmbedding);
            if ($distance <= $threshold) {
                $candidates[(int)$portrait['id']] = [
                    'distance' => $distance,
                    'user_openid' => $portrait['user_openid'] ?? '',
                ];
            }
        }

        return $candidates;
    }

    /**
     * 计算欧氏距离
     */
    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0;
        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $diff = $a[$i] - $b[$i];
            $sum += $diff * $diff;
        }
        return sqrt($sum);
    }

    /**
     * 通过openid匹配门店已有人像（任意日期，已合成完成的）
     * 无人脸特征时的备用匹配方案：按openid查找该用户在该门店是否已有合成完成的人像
     *
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param string $openid 用户openid
     * @return array|null 匹配到则返回 ['portrait_ids'=>[...], 'pick_url'=>'...', 'result_count'=>int]，否则返回null
     */
    public function matchByOpenidInStore(int $bid, int $mdid, string $openid): ?array
    {
        // 查找该openid在该门店已合成完成的人像（任意日期、任意来源）
        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('user_openid', $openid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
            ->where('synthesis_status', 3) // 合成已完成
            ->field('id, aid')
            ->order('id', 'desc')
            ->find();

        if (!$portrait) {
            return null;
        }

        $portraitId = (int)$portrait['id'];

        // 检查是否有已完成的成片
        $resultCount = AiTravelPhotoResult::where('portrait_id', $portraitId)
            ->where('status', AiTravelPhotoResult::STATUS_NORMAL)
            ->count();

        if ($resultCount == 0) {
            return null;
        }

        // 获取选片二维码
        $qrcode = AiTravelPhotoQrcode::where('portrait_id', $portraitId)
            ->where('status', 1)
            ->find();

        if (!$qrcode) {
            // 自动生成选片二维码
            try {
                $qrcodeService = new AiTravelPhotoQrcodeService();
                $qrResult = $qrcodeService->generateQrcode($portraitId);
                $qrcodeStr = $qrResult['qrcode'] ?? '';
            } catch (\Exception $e) {
                Log::error('openid匹配自动生成选片二维码失败', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
                return null;
            }
        } else {
            $qrcodeStr = $qrcode->qrcode;
        }

        $pickUrl = $this->getSiteDomain() . '/public/pick/index.html?qr=' . urlencode($qrcodeStr);

        Log::info('自拍端openid匹配命中', [
            'openid' => $openid,
            'bid' => $bid,
            'mdid' => $mdid,
            'portrait_id' => $portraitId,
            'result_count' => $resultCount,
        ]);

        return [
            'portrait_ids' => [$portraitId],
            'pick_url' => $pickUrl,
            'result_count' => $resultCount,
        ];
    }

    /**
     * 防重检测：同一门店同一用户同一人脸当天只能首次合成
     * 有人脸特征时通过人脸相似度精确匹配
     *
     * 优化③：放宽同日防重为“补充特征”
     * 当同用户当天第二次自拍时，不再完全阻断，而是：
     * 1. 用新的 embedding 聚合更新已有人像的特征向量（提升匹配准确率）
     * 2. 仍然返回防重结果（避免重复触发合成任务）
     *
     * @param array $faceEmbedding 人脸特征向量
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param string $openid 用户openid
     * @return array|null 存在重复则返回 ['portrait_id'=>int, 'pick_url'=>string, 'synthesis_status'=>int, 'embedding_updated'=>bool]，否则返回null
     */
    public function checkSelfieDedup(array $faceEmbedding, int $bid, int $mdid, string $openid): ?array
    {
        $distanceThreshold = 0.20; // L2距离阈值，与 matchFaceInStore 一致
        $sinceTime = strtotime('today'); // 当天0点开始

        // 查询该用户在该门店当天提交的自拍人像
        $recentPortraits = Db::name('ai_travel_photo_portrait')
            ->where('user_openid', $openid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->where('source_type', 3) // 用户自拍
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
            ->where('create_time', '>=', $sinceTime)
            ->field('id, face_embedding, face_embedding_id, synthesis_status, aid')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        if (empty($recentPortraits)) {
            return null;
        }

        foreach ($recentPortraits as $portrait) {
            $storedEmbedding = json_decode($portrait['face_embedding'] ?? '', true);
            if (!is_array($storedEmbedding) || count($storedEmbedding) < 64) {
                continue;
            }

            $distance = $this->euclideanDistance($faceEmbedding, $storedEmbedding);
            if ($distance <= $distanceThreshold) {
                // ===== 优化③：补充特征而非简单阻断 =====
                // 将新的 embedding 与已有的聚合，提升匹配准确率
                $embeddingUpdated = false;
                try {
                    $existingPortraitId = (int)$portrait['id'];
                    // 将新 embedding 与旧 embedding 取均值并L2归一化
                    $dim = min(count($faceEmbedding), count($storedEmbedding));
                    $merged = array_fill(0, $dim, 0.0);
                    for ($i = 0; $i < $dim; $i++) {
                        $merged[$i] = ((float)$storedEmbedding[$i] + (float)$faceEmbedding[$i]) / 2.0;
                    }
                    // L2 归一化
                    $norm = 0.0;
                    for ($i = 0; $i < $dim; $i++) {
                        $norm += $merged[$i] * $merged[$i];
                    }
                    $norm = sqrt($norm);
                    if ($norm > 0) {
                        for ($i = 0; $i < $dim; $i++) {
                            $merged[$i] /= $norm;
                        }
                    }
                    // 更新已有人像的 face_embedding
                    Db::name('ai_travel_photo_portrait')
                        ->where('id', $existingPortraitId)
                        ->update(['face_embedding' => json_encode($merged)]);
                    // 同步更新 Milvus
                    try {
                        $milvusService = new MilvusService();
                        if ($milvusService->isHealthy()) {
                            $oldMilvusId = $portrait['face_embedding_id'] ?? 0;
                            if ($oldMilvusId) {
                                $milvusService->delete($oldMilvusId);
                            }
                            $vectorIds = $milvusService->insert([$merged], ['portrait_id' => $existingPortraitId]);
                            if (!empty($vectorIds)) {
                                Db::name('ai_travel_photo_portrait')
                                    ->where('id', $existingPortraitId)
                                    ->update(['face_embedding_id' => $vectorIds[0] ?? 0]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('防重补充特征Milvus更新失败', ['portrait_id' => $existingPortraitId, 'error' => $e->getMessage()]);
                    }
                    $embeddingUpdated = true;
                    Log::info('同日防重: 补充特征完成，已聚合更新embedding', [
                        'openid' => $openid, 'portrait_id' => $existingPortraitId, 'distance' => $distance,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('同日防重补充特征异常', ['error' => $e->getMessage()]);
                }

                $result = $this->buildDedupResult($portrait, $openid, $bid, $mdid, $distance);
                $result['embedding_updated'] = $embeddingUpdated;
                return $result;
            }
        }

        return null;
    }

    /**
     * 防重检测（无人脸特征版本）：同一用户同一门店当天只能首次合成
     * 手动拍摄模式下无face_embedding时，基于openid+mdid+当天进行防重
     *
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param string $openid 用户openid
     * @return array|null 存在重复则返回 ['portrait_id'=>int, 'pick_url'=>string, 'synthesis_status'=>int]，否则返回null
     */
    public function checkSelfieDedupByOpenid(int $bid, int $mdid, string $openid): ?array
    {
        $sinceTime = strtotime('today'); // 当天0点开始

        // 查询该用户在该门店当天提交的自拍人像（不论是否有embedding）
        $existingPortrait = Db::name('ai_travel_photo_portrait')
            ->where('user_openid', $openid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->where('source_type', 3) // 用户自拍
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
            ->where('create_time', '>=', $sinceTime)
            ->field('id, face_embedding, synthesis_status, aid')
            ->order('id', 'desc')
            ->find();

        if (empty($existingPortrait)) {
            return null;
        }

        return $this->buildDedupResult($existingPortrait, $openid, $bid, $mdid, -1);
    }

    /**
     * 构造防重检测结果
     *
     * @param array $portrait 匹配到的人像记录
     * @param string $openid 用户openid
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param float $distance L2距离（-1表示基于openid的防重，无距离值）
     * @return array ['portrait_id'=>int, 'pick_url'=>string, 'synthesis_status'=>int]
     */
    private function buildDedupResult(array $portrait, string $openid, int $bid, int $mdid, float $distance): array
    {
        $portraitId = (int)$portrait['id'];
        $synthesisStatus = (int)($portrait['synthesis_status'] ?? 0);
        $pickUrl = '';

        if ($synthesisStatus == 3) {
            // 合成已完成，查找选片二维码
            $qrcode = Db::name('ai_travel_photo_qrcode')
                ->where('portrait_id', $portraitId)
                ->where('status', 1)
                ->find();
            if ($qrcode) {
                $pickUrl = $this->getSiteDomain() . '/public/pick/index.html?qr=' . urlencode($qrcode['qrcode']);
            }
        }

        $logData = [
            'openid' => $openid,
            'bid' => $bid,
            'mdid' => $mdid,
            'existing_portrait_id' => $portraitId,
            'synthesis_status' => $synthesisStatus,
            'dedup_mode' => $distance >= 0 ? 'face_similarity' : 'openid_only',
        ];
        if ($distance >= 0) {
            $logData['distance'] = $distance;
        }
        Log::info('自拍端防重检测命中（当天仅首次合成）', $logData);

        return [
            'portrait_id' => $portraitId,
            'pick_url' => $pickUrl,
            'synthesis_status' => $synthesisStatus,
        ];
    }

    /**
     * 创建自拍人像记录并投递合成任务
     *
     * @param string $imageBase64 图片base64
     * @param array $faceEmbedding 人脸特征向量
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @param string $openid 用户openid
     * @return array ['portrait_id' => int, 'estimated_seconds' => int, 'template_count' => int]
     */
    public function createSelfiePortraitAndSynthesize(
        string $imageBase64, array $faceEmbedding, int $aid, int $bid, int $mdid, string $openid
    ): array {
        // ===== 保存图片（与 SmileCapture 上传流程对齐） =====
        $imageData = base64_decode($imageBase64);
        if (!$imageData) {
            throw new \Exception('图片数据无效');
        }

        // ===== 提取图片元信息（修复缩略图/文件名/尺寸等字段缺失） =====
        $imageInfo = getimagesizefromstring($imageData);
        $imgWidth = $imageInfo ? (int)$imageInfo[0] : 0;
        $imgHeight = $imageInfo ? (int)$imageInfo[1] : 0;
        $fileSize = strlen($imageData);
        $fileMd5 = md5($imageData);
        $fileName = 'selfie_' . date('YmdHis') . '_' . sprintf('%04d', mt_rand(0, 9999)) . '.jpg';

        // 确保 aid 常量已定义，供 Pic::uploadoss 识别云存储配置
        if (!defined('aid')) {
            define('aid', $aid);
        }

        // 使用标准上传路径：upload/{aid}/{date}/（与 SmileCapture 一致）
        $dateDir = date('Ymd');
        $uniqueName = md5(uniqid((string)mt_rand(), true)) . '.jpg';
        $savePath = 'upload/' . $aid . '/' . $dateDir . '/';

        if (!is_dir(ROOT_PATH . $savePath)) {
            mk_dir(ROOT_PATH . $savePath);
        }

        $originalPath = $savePath . 'selfie_' . $uniqueName;
        $writeResult = file_put_contents(ROOT_PATH . $originalPath, $imageData);
        if ($writeResult === false) {
            throw new \Exception('图片保存失败，请检查上传目录权限');
        }

        // 通过系统云存储配置上传（OSS/七牛/腾讯云/本地）
        $uploadPath = PRE_URL . '/' . $originalPath;
        $originalUrl = \app\common\Pic::uploadoss($uploadPath, false, false);

        if (!$originalUrl) {
            // 云存储上传失败，使用本地路径作为备用
            Log::warning('自拍端OSS上传失败，使用本地存储', [
                'uploadPath' => $uploadPath, 'aid' => $aid
            ]);
            $originalUrl = '/' . $originalPath;
        }

        // ===== 生成缩略图（800px 宽度） =====
        $thumbnailUrl = '';
        try {
            $thumbnailUrl = $this->generateSelfieThumbnail($imageData, $imgWidth, $imgHeight, $savePath, $aid);
        } catch (\Exception $e) {
            Log::warning('自拍端缩略图生成失败，跳过', ['aid' => $aid, 'error' => $e->getMessage()]);
        }

        // OSS上传成功后删除本地文件（如果URL已是远程地址）
        if (strpos($originalUrl, 'http') === 0 && strpos($originalUrl, PRE_URL) !== 0) {
            @unlink(ROOT_PATH . $originalPath);
        }

        // ===== 后端提取人脸特征（InsightFace 512维，统一替代前端 face-api.js 128维） =====
        $backendEmbedding = [];
        try {
            $faceEmbeddingService = new FaceEmbeddingService();
            $faceResult = $faceEmbeddingService->extractFromUrl($originalUrl);
            if ($faceResult && !empty($faceResult['embedding'])) {
                $backendEmbedding = $faceResult['embedding'];
                Log::info('自拍端人脸特征后端提取成功', [
                    'dim' => $faceResult['dim'], 'det_score' => $faceResult['det_score'],
                ]);
            } else {
                Log::info('自拍端图片后端未检测到人脸，使用前端传入的embedding作为回退');
            }
        } catch (\Exception $e) {
            Log::warning('自拍端后端特征提取异常，使用前端传入的embedding作为回退', [
                'error' => $e->getMessage(),
            ]);
        }
        // 后端提取成功则优先使用，否则回退到前端传入的 embedding
        $effectiveEmbedding = !empty($backendEmbedding) ? $backendEmbedding : $faceEmbedding;

        // 创建人像记录
        $portraitId = (int) Db::name('ai_travel_photo_portrait')->insertGetId([
            'aid' => $aid,
            'bid' => $bid,
            'mdid' => $mdid,
            'uid' => 0,
            'device_id' => 0,
            'original_url' => $originalUrl,
            'cutout_url' => $originalUrl, // 自拍不需要抠图
            'thumbnail_url' => $thumbnailUrl,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'width' => $imgWidth,
            'height' => $imgHeight,
            'md5' => $fileMd5,
            'face_embedding' => !empty($effectiveEmbedding) ? json_encode($effectiveEmbedding) : '[]',
            'type' => AiTravelPhotoPortrait::TYPE_USER,
            'source_type' => 3, // 用户自拍
            'user_openid' => $openid,
            'status' => AiTravelPhotoPortrait::STATUS_NORMAL,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        // 保存到Milvus（仅在有有效embedding时）
        if (!empty($effectiveEmbedding)) {
            try {
                $milvusService = new MilvusService();
                if ($milvusService->isHealthy()) {
                    $milvusService->insert([$effectiveEmbedding], ['portrait_id' => $portraitId]);
                }
            } catch (\Throwable $e) {
                Log::warning('Milvus插入失败', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
            }

            // ===== 优化②：同人向量聚合 =====
            // 如果该用户已有多次入库embedding，计算质心向量提升匹配稳定性
            if (!empty($openid)) {
                try {
                    $this->aggregateUserEmbeddings($openid, $bid, $portraitId);
                } catch (\Throwable $e) {
                    Log::warning('向量聚合异常，不影响主流程', [
                        'openid' => $openid, 'error' => $e->getMessage()
                    ]);
                }
            }
        } else {
            Log::info('自拍端：手动拍摄模式无face_embedding，跳过Milvus插入', ['portrait_id' => $portraitId]);
        }

        // ===== 获取合成设置并触发合成（整体 try-catch 保障 synthesis_status 不会卡在 0） =====
        $templateCount = 0;
        $estimatedSeconds = 30;
        try {
        $setting = Db::name('ai_travel_photo_synthesis_setting')
            ->where('portrait_id', 0)
            ->where('aid', $aid)
            ->where('bid', $bid)
            ->find();

        $templates = [];
        $generateCount = 4; // 默认生成数量

        if ($setting && !empty($setting['template_ids'])) {
            $templateIds = explode(',', $setting['template_ids']);
            $generateCount = (int)($setting['generate_count'] ?? 4);
            $generateMode = (int)($setting['generate_mode'] ?? 1);

            // 从商户合成模板表查询模板
            $availableTemplates = Db::name('ai_travel_photo_synthesis_template')
                ->whereIn('id', $templateIds)
                ->where('status', 1)
                ->field('id, name as template_name, model_id, model_name, cover_image, images, prompt, default_params, description, scene_template_id')
                ->select()
                ->toArray();

            // 根据生成模式选择模板
            if (!empty($availableTemplates)) {
                if ($generateMode == 1) {
                    // 顺序模式
                    for ($i = 0; $i < $generateCount; $i++) {
                        $templates[] = $availableTemplates[$i % count($availableTemplates)];
                    }
                } else {
                    // 随机模式
                    $pool = [];
                    for ($i = 0; $i < $generateCount; $i++) {
                        if (empty($pool)) {
                            $pool = $availableTemplates;
                            shuffle($pool);
                        }
                        $templates[] = array_shift($pool);
                    }
                }
            }
        }

        $templateCount = count($templates);

        // 计算预估等待时间
        $queuePending = $this->getQueuePendingCount();
        $estimatedSeconds = $templateCount * 15 + $queuePending * 5;

        // ===== 投递合成任务到队列（与 SmileCapture triggerAsyncTasks 保持一致） =====
        if (!empty($templates)) {
            // 更新人像合成状态为「处理中」
            Db::name('ai_travel_photo_portrait')
                ->where('id', $portraitId)
                ->update([
                    'synthesis_status' => 2,
                    'synthesis_error' => '',
                    'update_time' => time()
                ]);

            $queuedCount = 0;
            foreach ($templates as $template) {
                try {
                    // 创建 generation 记录（与 SmileCapture 一致）
                    $generationId = Db::name('ai_travel_photo_generation')->insertGetId([
                        'aid' => $aid,
                        'portrait_id' => $portraitId,
                        'scene_id' => 0,
                        'template_id' => $template['id'],
                        'uid' => 0,
                        'bid' => $bid,
                        'mdid' => $mdid,
                        'type' => 1,
                        'generation_type' => 1,
                        'status' => 0, // 待处理
                        'create_time' => time(),
                        'update_time' => time(),
                        'queue_time' => time()
                    ]);

                    // 推送到正确的队列（ai_image_generation，格式: generation_id）
                    \think\facade\Queue::push(
                        'app\\job\\ImageGenerationJob',
                        ['generation_id' => $generationId],
                        'ai_image_generation'
                    );

                    $queuedCount++;
                    Log::info('自拍端合成任务已推送', [
                        'portrait_id' => $portraitId,
                        'template_id' => $template['id'],
                        'generation_id' => $generationId
                    ]);
                } catch (\Exception $e) {
                    Log::error('自拍端合成任务推送失败', [
                        'portrait_id' => $portraitId,
                        'template_id' => $template['id'] ?? 0,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 如果所有任务都推送失败，降级为同步合成
            if ($queuedCount === 0) {
                Log::warning('自拍端所有队列任务推送失败，尝试同步合成', ['portrait_id' => $portraitId]);
                try {
                    $portrait = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();
                    $synthesisService = new AiTravelPhotoSynthesisService();
                    $result = $synthesisService->generate($portrait, $templates, 'selfie_auto');
                    $resultCount = $result['data']['count'] ?? 0;
                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                        'synthesis_status' => $resultCount > 0 ? 3 : 4,
                        'synthesis_count' => $resultCount,
                        'synthesis_time' => time(),
                        'synthesis_error' => $resultCount > 0 ? '' : ($result['msg'] ?? '合成失败'),
                        'update_time' => time()
                    ]);
                } catch (\Exception $ex) {
                    Log::error('自拍端同步合成也失败', ['portrait_id' => $portraitId, 'error' => $ex->getMessage()]);
                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                        'synthesis_status' => 4,
                        'synthesis_error' => $ex->getMessage(),
                        'update_time' => time()
                    ]);
                }
            }
        } else {
            Log::warning('自拍端：未找到可用的合成模板', ['aid' => $aid, 'bid' => $bid, 'portrait_id' => $portraitId]);
            // 无模板可用，标记为失败
            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                'synthesis_status' => 4,
                'synthesis_error' => '未配置合成模板，请管理员先在合成设置中关联模板',
                'update_time' => time()
            ]);
        }

        } catch (\Throwable $e) {
            // 兜底：合成触发阶段异常，确保 portrait 不会永久卡在 status=0
            Log::error('自拍端合成触发异常', [
                'portrait_id' => $portraitId, 'error' => $e->getMessage()
            ]);
            Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                'synthesis_status' => 4,
                'synthesis_error' => '合成触发异常: ' . mb_substr($e->getMessage(), 0, 200),
                'update_time' => time()
            ]);
        }

        // 更新二维码统计
        AiTravelPhotoSelfieQrcode::where('aid', $aid)
            ->where('bid', $bid)
            ->where('mdid', $mdid)
            ->inc('selfie_count')
            ->update([]);

        return [
            'portrait_id' => $portraitId,
            'estimated_seconds' => $estimatedSeconds,
            'template_count' => $templateCount,
        ];
    }

    /**
     * 生成自拍图片缩略图（800px宽度），上传OSS并返回URL
     *
     * @param string $imageData 图片二进制内容
     * @param int $sourceWidth 原图宽度
     * @param int $sourceHeight 原图高度
     * @param string $savePath 保存目录相对路径 upload/aid/date/
     * @param int $aid 平台ID
     * @return string 缩略图URL
     */
    private function generateSelfieThumbnail(string $imageData, int $sourceWidth, int $sourceHeight, string $savePath, int $aid): string
    {
        $targetWidth = 800;
        $thumbName = 'thumb_selfie_' . md5(uniqid((string)mt_rand(), true)) . '.jpg';
        $thumbnailRelPath = $savePath . $thumbName;

        if ($sourceWidth <= $targetWidth || $sourceWidth <= 0 || $sourceHeight <= 0) {
            // 原图宽度不超过800px，直接复制作为缩略图
            $writeResult = file_put_contents(ROOT_PATH . $thumbnailRelPath, $imageData);
            if ($writeResult === false) {
                throw new \Exception('缩略图保存失败');
            }
        } else {
            // 按比例缩放至800px宽
            $targetHeight = intval($sourceHeight * ($targetWidth / $sourceWidth));

            $sourceImage = imagecreatefromstring($imageData);
            if (!$sourceImage) {
                throw new \Exception('无法解析图片数据');
            }

            $thumbnailImage = imagecreatetruecolor($targetWidth, $targetHeight);
            imagecopyresampled($thumbnailImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
            $result = imagejpeg($thumbnailImage, ROOT_PATH . $thumbnailRelPath, 85);
            imagedestroy($sourceImage);
            imagedestroy($thumbnailImage);

            if (!$result) {
                throw new \Exception('缩略图生成失败');
            }
        }

        // 上传缩略图到OSS
        $thumbnailUrl = \app\common\Pic::uploadoss(PRE_URL . '/' . $thumbnailRelPath, false, false);
        if (!$thumbnailUrl) {
            // OSS上传失败，使用本地相对路径作为fallback
            Log::warning('自拍端缩略图OSS上传失败，使用本地路径', ['path' => $thumbnailRelPath]);
            $thumbnailUrl = '/' . $thumbnailRelPath;
        } else {
            // OSS上传成功后删除本地缩略图文件
            if (strpos($thumbnailUrl, 'http') === 0) {
                @unlink(ROOT_PATH . $thumbnailRelPath);
            }
        }

        return $thumbnailUrl;
    }

    /**
     * 优化②：同人向量聚合
     * 
     * 当同一用户（openid）在同一商家下有多次人脸特征入库时，
     * 计算所有 embedding 的均值向量（质心）并 L2 归一化，
     * 作为更稳定的用户代表向量，提升匹配准确率。
     * 
     * 聚合后更新最新人像记录的 face_embedding 为质心向量，
     * 并同步更新 Milvus 中的向量。
     *
     * @param string $openid 用户openid
     * @param int $bid 商家ID
     * @param int $currentPortraitId 当前新插入的人像ID
     * @return array|null 聚合后的质心向量，向量不足两个时返回 null
     */
    public function aggregateUserEmbeddings(string $openid, int $bid, int $currentPortraitId = 0): ?array
    {
        if (empty($openid)) {
            return null;
        }

        // 查询该用户在该商家下所有有效人像的 face_embedding
        $portraits = Db::name('ai_travel_photo_portrait')
            ->where('user_openid', $openid)
            ->where('bid', $bid)
            ->where('status', AiTravelPhotoPortrait::STATUS_NORMAL)
            ->where('face_embedding', '<>', '')
            ->where('face_embedding', '<>', '[]')
            ->field('id, face_embedding, face_embedding_id')
            ->order('id', 'desc')
            ->select()
            ->toArray();

        if (count($portraits) < 2) {
            // 只有一个 embedding，无需聚合
            return null;
        }

        // 收集所有有效的 embedding 向量
        $allEmbeddings = [];
        foreach ($portraits as $p) {
            $emb = json_decode($p['face_embedding'], true);
            if (is_array($emb) && count($emb) >= 64) {
                $allEmbeddings[] = $emb;
            }
        }

        if (count($allEmbeddings) < 2) {
            return null;
        }

        // 计算质心（逐元素均值）
        $dim = count($allEmbeddings[0]);
        $centroid = array_fill(0, $dim, 0.0);
        $vectorCount = count($allEmbeddings);
        foreach ($allEmbeddings as $emb) {
            for ($i = 0; $i < $dim; $i++) {
                $centroid[$i] += (float)$emb[$i] / $vectorCount;
            }
        }

        // L2 归一化
        $norm = 0.0;
        for ($i = 0; $i < $dim; $i++) {
            $norm += $centroid[$i] * $centroid[$i];
        }
        $norm = sqrt($norm);
        if ($norm > 0) {
            for ($i = 0; $i < $dim; $i++) {
                $centroid[$i] /= $norm;
            }
        }

        // 更新最新人像记录的 face_embedding 为质心向量
        $latestPortraitId = $currentPortraitId > 0 ? $currentPortraitId : (int)$portraits[0]['id'];
        Db::name('ai_travel_photo_portrait')
            ->where('id', $latestPortraitId)
            ->update(['face_embedding' => json_encode($centroid)]);

        // 更新 Milvus 中的向量
        try {
            $milvusService = new MilvusService();
            if ($milvusService->isHealthy()) {
                // 删除该用户旧的 Milvus 向量，插入新质心
                $latestRecord = Db::name('ai_travel_photo_portrait')
                    ->where('id', $latestPortraitId)
                    ->field('face_embedding_id')
                    ->find();
                if (!empty($latestRecord['face_embedding_id'])) {
                    $milvusService->delete($latestRecord['face_embedding_id']);
                }
                $vectorIds = $milvusService->insert([$centroid], ['portrait_id' => $latestPortraitId]);
                if (!empty($vectorIds)) {
                    Db::name('ai_travel_photo_portrait')
                        ->where('id', $latestPortraitId)
                        ->update(['face_embedding_id' => $vectorIds[0] ?? 0]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('向量聚合: Milvus更新失败', [
                'openid' => $openid, 'error' => $e->getMessage()
            ]);
        }

        Log::info('同人向量聚合完成', [
            'openid' => $openid,
            'bid' => $bid,
            'vector_count' => $vectorCount,
            'latest_portrait_id' => $latestPortraitId,
        ]);

        return $centroid;
    }

    /**
     * 获取队列中待处理任务数
     */
    private function getQueuePendingCount(): int
    {
        try {
            return (int)Db::name('jobs')->where('queue', 'ai_image_generation')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 查询合成进度
     *
     * @param int $portraitId 人像ID
     * @return array
     */
    public function getProgress(int $portraitId): array
    {
        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('id', $portraitId)
            ->find();

        if (!$portrait) {
            throw new \Exception('人像不存在');
        }

        // ===== 超时恢复机制 =====
        // synthesis_status=2（处理中）超过10分钟未更新，自动检查并标记为失败
        if (($portrait['synthesis_status'] ?? 0) == 2) {
            $updateTime = (int)($portrait['update_time'] ?? 0);
            if ($updateTime > 0 && (time() - $updateTime) > 600) {
                // 检查是否有仍在队列中等待的 generation 记录
                $stillPending = Db::name('ai_travel_photo_generation')
                    ->where('portrait_id', $portraitId)
                    ->whereIn('status', [0, 1]) // 待处理 或 处理中
                    ->count();
                
                if ($stillPending > 0) {
                    // 将超时的 generation 记录标记为失败
                    Db::name('ai_travel_photo_generation')
                        ->where('portrait_id', $portraitId)
                        ->whereIn('status', [0, 1])
                        ->update([
                            'status' => 3,
                            'error_msg' => '处理超时（超过10分钟）',
                            'finish_time' => time(),
                            'update_time' => time(),
                        ]);
                    Log::warning('自拍端合成超时恢复', [
                        'portrait_id' => $portraitId,
                        'pending_count' => $stillPending,
                    ]);
                }
                
                // 重新检查是否有成功的
                $successCount = Db::name('ai_travel_photo_generation')
                    ->where('portrait_id', $portraitId)
                    ->where('status', 2) // 成功
                    ->count();
                
                $newStatus = $successCount > 0 ? 3 : 4;
                Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                    'synthesis_status' => $newStatus,
                    'synthesis_error' => $successCount > 0 ? '' : '合成超时，所有任务均失败',
                    'update_time' => time(),
                ]);
                
                // 重新加载 portrait 数据
                $portrait = Db::name('ai_travel_photo_portrait')
                    ->where('id', $portraitId)
                    ->find();
            }
        }

        // 查询该人像的generation记录
        $generations = Db::name('ai_travel_photo_generation')
            ->where('portrait_id', $portraitId)
            ->select()
            ->toArray();

        if (empty($generations)) {
            // 还没有generation记录，说明任务还在队列中未开始
            return [
                'status' => 0,
                'progress' => 0,
                'remaining_seconds' => 60,
                'pick_url' => '',
            ];
        }

        $total = count($generations);
        $completed = 0;
        $failed = 0;

        foreach ($generations as $gen) {
            if ($gen['status'] == 2) $completed++;
            if ($gen['status'] == 3) $failed++;
        }

        $inProgress = $total - $completed - $failed;

        if ($completed + $failed >= $total) {
            // 所有任务已完成（成功或失败）
            if ($completed > 0) {
                // 有成功的结果，查找或自动创建选片二维码
                $qrcode = Db::name('ai_travel_photo_qrcode')
                    ->where('portrait_id', $portraitId)
                    ->where('status', 1)
                    ->find();

                // 队列模式下可能还没有qrcode记录，自动创建
                if (!$qrcode) {
                    try {
                        $qrcodeValue = 'synth_' . $portraitId . '_' . time();
                        Db::name('ai_travel_photo_qrcode')->insert([
                            'aid' => $portrait['aid'] ?? 0,
                            'bid' => $portrait['bid'] ?? 0,
                            'portrait_id' => $portraitId,
                            'qrcode' => $qrcodeValue,
                            'status' => 1,
                            'create_time' => time(),
                            'update_time' => time()
                        ]);
                        $qrcode = ['qrcode' => $qrcodeValue];
                        Log::info('自拍端自动创建选片二维码', ['portrait_id' => $portraitId]);
                    } catch (\Exception $e) {
                        Log::error('自动创建选片二维码失败', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
                    }
                }

                $pickUrl = '';
                if ($qrcode) {
                    $pickUrl = $this->getSiteDomain() . '/public/pick/index.html?qr=' . urlencode($qrcode['qrcode']);
                }

                // 同步更新人像的synthesis_status为已完成
                if (($portrait['synthesis_status'] ?? 0) != 3) {
                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                        'synthesis_status' => 3,
                        'synthesis_count' => $completed,
                        'synthesis_time' => time(),
                        'update_time' => time()
                    ]);
                }

                // 触发自拍端通知（如果用户注册了"找到后通知我"）
                try {
                    if (($portrait['source_type'] ?? 0) == 3) {
                        $this->sendSynthesisCompleteNotify($portraitId);
                    }
                } catch (\Exception $e) {
                    Log::error('自拍端合成完成通知失败', ['portrait_id' => $portraitId, 'error' => $e->getMessage()]);
                }

                return [
                    'status' => 3, // 已完成
                    'progress' => 100,
                    'remaining_seconds' => 0,
                    'pick_url' => $pickUrl,
                    'completed' => $completed,
                    'failed' => $failed,
                    'total' => $total,
                ];
            } else {
                // 全部失败，更新portrait状态
                if (($portrait['synthesis_status'] ?? 0) != 4) {
                    Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->update([
                        'synthesis_status' => 4,
                        'synthesis_error' => '所有合成任务均失败',
                        'update_time' => time()
                    ]);
                }

                return [
                    'status' => 4, // 全部失败
                    'progress' => 100,
                    'remaining_seconds' => 0,
                    'pick_url' => '',
                ];
            }
        }

        // 进行中
        $progress = (int)(($completed + $failed) / $total * 100);
        $remainingSeconds = $inProgress * 15;

        // 判断状态
        $status = 1; // 处理中
        if ($completed > 0 || $failed > 0) {
            $status = 2; // 进行中（有部分结果）
        }

        return [
            'status' => $status,
            'progress' => $progress,
            'remaining_seconds' => $remainingSeconds,
            'pick_url' => '',
            'completed' => $completed,
            'failed' => $failed,
            'total' => $total,
        ];
    }

    /**
     * 注册通知请求
     *
     * @param int $portraitId 人像ID
     * @param string $openid 用户openid
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID
     * @return int 通知记录ID
     */
    public function registerNotify(int $portraitId, string $openid, int $aid, int $bid, int $mdid): int
    {
        // 去重检查
        $existing = AiTravelPhotoSelfieNotify::where('openid', $openid)
            ->where('portrait_id', $portraitId)
            ->find();

        if ($existing) {
            return $existing->id;
        }

        $record = AiTravelPhotoSelfieNotify::create([
            'aid' => $aid,
            'bid' => $bid,
            'mdid' => $mdid,
            'portrait_id' => $portraitId,
            'openid' => $openid,
            'uid' => 0,
            'notify_type' => AiTravelPhotoSelfieNotify::NOTIFY_TYPE_KEFU,
            'notify_status' => AiTravelPhotoSelfieNotify::STATUS_PENDING,
            'create_time' => time(),
        ]);

        return (int)$record->id;
    }

    /**
     * 获取站点域名（兼容 HTTP 请求和 CLI 队列上下文）
     *
     * @return string 如 https://ai.eivie.cn
     */
    public function getSiteDomain(): string
    {
        // 1. HTTP 上下文（排除 CLI 队列中返回的 localhost）
        try {
            $domain = request()->domain();
            if (!empty($domain) && $domain !== 'http://' && $domain !== 'https://'
                && stripos($domain, 'localhost') === false && stripos($domain, '127.0.0.1') === false) {
                return $domain;
            }
        } catch (\Throwable $e) {}

        // 2. PRE_URL 常量（processSingleGeneration 中已定义）
        if (defined('PRE_URL') && !empty(PRE_URL)) {
            return PRE_URL;
        }

        // 3. 从 admin 表获取域名
        try {
            $admin = Db::name('admin')->where('id', 1)->field('domain')->find();
            if ($admin && !empty($admin['domain'])) {
                return 'https://' . $admin['domain'];
            }
        } catch (\Throwable $e) {}

        // 4. 从项目目录推断
        if (defined('ROOT_PATH')) {
            return 'https://' . basename(ROOT_PATH);
        }

        return '';
    }

    /**
     * 合成完成后主动向自拍用户推送选片图文链接
     * 不依赖 notify_me 注册，所有自拍端用户(source_type=3)合成完成后都会自动收到
     *
     * @param int $portraitId 人像ID
     * @return bool 推送是否成功
     */
    public function pushPickUrlToSelfieUser(int $portraitId): bool
    {
        $portrait = Db::name('ai_travel_photo_portrait')
            ->where('id', $portraitId)
            ->find();

        if (!$portrait) {
            Log::warning('自拍端主动推送：人像不存在', ['portrait_id' => $portraitId]);
            return false;
        }

        $openid = $portrait['user_openid'] ?? '';
        if (empty($openid)) {
            Log::warning('自拍端主动推送：用户openid为空', ['portrait_id' => $portraitId]);
            return false;
        }

        $aid = (int)($portrait['aid'] ?? 0);
        if ($aid <= 0) {
            Log::warning('自拍端主动推送：aid为空', ['portrait_id' => $portraitId]);
            return false;
        }

        // 获取选片二维码
        $qrcode = Db::name('ai_travel_photo_qrcode')
            ->where('portrait_id', $portraitId)
            ->where('status', 1)
            ->find();

        if (!$qrcode) {
            Log::warning('自拍端主动推送：未找到选片二维码', ['portrait_id' => $portraitId]);
            return false;
        }

        $domain = $this->getSiteDomain();
        if (empty($domain)) {
            Log::error('自拍端主动推送：无法获取站点域名', ['portrait_id' => $portraitId]);
            return false;
        }

        $pickUrl = $domain . '/public/pick/index.html?qr=' . urlencode($qrcode['qrcode']);

        // 获取门店名称
        $storeName = '';
        if (($portrait['mdid'] ?? 0) > 0) {
            $storeName = Db::name('mendian')->where('id', $portrait['mdid'])->value('name') ?: '';
        }

        // 获取封面图
        $pushConfig = $this->getPushConfig($aid, (int)($portrait['bid'] ?? 0), (int)($portrait['mdid'] ?? 0));
        $picUrl = !empty($pushConfig['push_cover']) ? $pushConfig['push_cover'] : ($domain . '/static/img/ai_travel_photo_selfie_cover.png');

        $description = '您' . ($storeName ? '在' . $storeName : '') . '的旅拍照片已生成完成，点击查看并选片购买！';

        Log::info('自拍端主动推送选片链接', [
            'portrait_id' => $portraitId,
            'openid' => $openid,
            'pick_url' => $pickUrl,
        ]);

        // 策略：先尝试客服图文消息（富卡片形式，最佳UX），如果48小时窗口已过期(45015)则回退到模板消息
        $kefuResult = $this->sendKefuNewsMessage($aid, $openid, [
            'title' => '您的旅拍照片已准备就绪！',
            'description' => $description,
            'url' => $pickUrl,
            'picurl' => $picUrl,
        ]);

        if ($kefuResult['success']) {
            Log::info('自拍端主动推送成功(客服消息)', ['portrait_id' => $portraitId, 'openid' => $openid]);
            return true;
        }

        // 客服消息失败，检查是否为48小时窗口过期
        $errcode = $kefuResult['errcode'] ?? 0;
        if ($errcode == 45015) {
            Log::info('客服消息48小时窗口已过期，尝试模板消息回退', ['portrait_id' => $portraitId]);
            $tmplSuccess = $this->sendTemplateNotification($aid, $openid, $pickUrl, $storeName);
            if ($tmplSuccess) {
                Log::info('自拍端主动推送成功(模板消息回退)', ['portrait_id' => $portraitId, 'openid' => $openid]);
                return true;
            }
        }

        Log::error('自拍端主动推送失败: errcode=' . $errcode . ', portrait_id=' . $portraitId . ', openid=' . $openid);
        return false;
    }

    /**
     * 合成完成后推送通知给注册了"找到后通知我"的用户
     * （补充推送：处理额外注册了通知的其他用户，如通过人脸匹配找到的用户）
     *
     * @param int $portraitId 人像ID
     * @return int 成功推送数
     */
    public function sendSynthesisCompleteNotify(int $portraitId): int
    {
        $notifyRecords = AiTravelPhotoSelfieNotify::where('portrait_id', $portraitId)
            ->where('notify_status', AiTravelPhotoSelfieNotify::STATUS_PENDING)
            ->select();

        if ($notifyRecords->isEmpty()) {
            return 0;
        }

        // 获取选片页URL
        $qrcode = Db::name('ai_travel_photo_qrcode')
            ->where('portrait_id', $portraitId)
            ->where('status', 1)
            ->find();

        if (!$qrcode) {
            Log::warning('合成完成通知：未找到选片二维码', ['portrait_id' => $portraitId]);
            return 0;
        }

        $domain = $this->getSiteDomain();
        if (empty($domain)) {
            Log::error('合成完成通知：无法获取站点域名');
            return 0;
        }

        $pickUrl = $domain . '/public/pick/index.html?qr=' . urlencode($qrcode['qrcode']);

        // 获取门店名称
        $portrait = Db::name('ai_travel_photo_portrait')->where('id', $portraitId)->find();
        $storeName = '';
        if ($portrait && $portrait['mdid'] > 0) {
            $storeName = Db::name('mendian')->where('id', $portrait['mdid'])->value('name') ?: '';
        }

        $sentCount = 0;
        foreach ($notifyRecords as $record) {
            try {
                $description = '点击查看您' . ($storeName ? '在' . $storeName : '') . '的精美旅拍照片，快来选片吧！';
                
                $kefuResult = $this->sendKefuNewsMessage($record->aid, $record->openid, [
                    'title' => '您的旅拍照片已准备就绪',
                    'description' => $description,
                    'url' => $pickUrl,
                    'picurl' => $domain . '/static/img/ai_travel_photo_selfie_cover.png',
                ]);

                $success = $kefuResult['success'];
                // 客服消息48小时窗口过期，回退到模板消息
                if (!$success && ($kefuResult['errcode'] ?? 0) == 45015) {
                    $success = $this->sendTemplateNotification($record->aid, $record->openid, $pickUrl, $storeName);
                }

                $record->notify_status = $success ? AiTravelPhotoSelfieNotify::STATUS_SENT : AiTravelPhotoSelfieNotify::STATUS_FAILED;
                $record->notify_time = time();
                $record->pick_url = $pickUrl;
                $record->save();

                if ($success) {
                    $sentCount++;
                }
            } catch (\Exception $e) {
                Log::error('自拍通知推送失败', ['notify_id' => $record->id, 'error' => $e->getMessage()]);
                $record->notify_status = AiTravelPhotoSelfieNotify::STATUS_FAILED;
                $record->notify_time = time();
                $record->save();
            }
        }

        return $sentCount;
    }

    /**
     * 获取自拍端数据统计
     *
     * @param int $aid 平台ID
     * @param int $bid 商家ID
     * @param int $mdid 门店ID（0=全部门店）
     * @return array
     */
    public function getStats(int $aid, int $bid, int $mdid = 0, bool $isAdmin = false): array
    {
        // 数据隔离：商家只能看到自己bid的数据，超级管理员可以看到目标商家+公共数据
        $query = AiTravelPhotoSelfieQrcode::where('aid', $aid);
        if ($isAdmin) {
            // 超级管理员：查看目标商家 + 公共数据(bid=0)
            $query->where(function($q) use ($bid) {
                $q->whereOr([
                    ['bid', '=', $bid],
                    ['bid', '=', 0]
                ]);
            });
        } else {
            // 普通商家：仅查看自己的数据，不包含公共数据
            $query->where('bid', $bid);
        }
        if ($mdid > 0) {
            $query->where('mdid', $mdid);
        }

        $qrStats = $query
            ->field('SUM(scan_count) as total_scan, SUM(follow_count) as total_follow, SUM(selfie_count) as total_selfie, SUM(match_count) as total_match')
            ->find();

        $totalScan = (int)($qrStats['total_scan'] ?? 0);
        $totalFollow = (int)($qrStats['total_follow'] ?? 0);
        $totalSelfie = (int)($qrStats['total_selfie'] ?? 0);
        $totalMatch = (int)($qrStats['total_match'] ?? 0);

        // 合成触发数 = 自拍数 - 命中数
        $synthesisTrigger = max(0, $totalSelfie - $totalMatch);

        // 通知统计 - 同样按bid隔离
        $notifyQuery = AiTravelPhotoSelfieNotify::where('aid', $aid);
        if ($isAdmin) {
            $notifyQuery->where(function($q) use ($bid) {
                $q->whereOr([
                    ['bid', '=', $bid],
                    ['bid', '=', 0]
                ]);
            });
        } else {
            $notifyQuery->where('bid', $bid);
        }
        if ($mdid > 0) {
            $notifyQuery->where('mdid', $mdid);
        }
        $totalNotifyRequests = (clone $notifyQuery)->count();
        $totalNotifySent = (clone $notifyQuery)
            ->where('notify_status', AiTravelPhotoSelfieNotify::STATUS_SENT)
            ->count();

        return [
            'total_scan' => $totalScan,
            'total_follow' => $totalFollow,
            'total_selfie' => $totalSelfie,
            'match_count' => $totalMatch,
            'match_rate' => $totalSelfie > 0 ? round($totalMatch / $totalSelfie * 100, 1) : 0,
            'synthesis_trigger' => $synthesisTrigger,
            'total_notify_requests' => $totalNotifyRequests,
            'total_notify_sent' => $totalNotifySent,
        ];
    }
}
