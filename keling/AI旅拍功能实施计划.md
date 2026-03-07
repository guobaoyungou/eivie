# AI旅拍功能实施计划

## 概述
本计划用于实施AI旅拍功能,包括商家监控、AI视频生成、游客选片支付等核心功能。

## 设计参考
- 设计文档：[AI旅拍功能设计文档.md](/www/wwwroot/eivie/keling/AI旅拍功能设计文档.md)
- 数据库表结构：[aivideo_tables.sql](/www/wwwroot/eivie/keling/aivideo_tables.sql)
- 可灵AI文档：[可灵 AI 使用指南.md](/www/wwwroot/eivie/keling/可灵 AI 使用指南.md)

---

## 任务列表

### 阶段1: 基础设施搭建

#### 任务1: 创建数据库表
**优先级**: 高
**预计时间**: 5分钟
**依赖**: 无

**操作**:
- EXECUTE SQL file `/www/wwwroot/eivie/keling/aivideo_tables.sql`

**代码**:
```sql
-- 执行数据库表创建脚本
-- 文件路径: /www/wwwroot/eivie/keling/aivideo_tables.sql
```

**验证**:
```bash
mysql -h localhost -u guobaoyungou_cn -p5ArfhRr9xzyScrF5 guobaoyungou_cn -e "SHOW TABLES LIKE 'ddwx_aivideo_%';"
```

**成功标准**:
- [ ] 7个数据库表创建成功
- [ ] 所有表字段正确
- [ ] 索引创建成功

---

#### 任务2: 安装JWT依赖包
**优先级**: 高
**预计时间**: 3分钟
**依赖**: 无

**操作**:
- EXECUTE composer command

**代码**:
```bash
cd /www/wwwroot/eivie
composer require firebase/php-jwt
```

**验证**:
```bash
grep -r "firebase/php-jwt" /www/wwwroot/eivie/composer.json
```

**成功标准**:
- [ ] firebase/php-jwt包安装成功
- [ ] composer.json中包含依赖
- [ ] vendor目录中有相关文件

---

#### 任务3: 创建AI旅拍配置文件
**优先级**: 高
**预计时间**: 2分钟
**依赖**: 任务1

**操作**:
- CREATE `/www/wwwroot/eivie/config/aivideo.php`

**代码**:
```php
<?php
/**
 * AI旅拍功能配置文件
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

return [
    // 可灵AI配置
    'kling' => [
        'api_url' => 'https://api-beijing.klingai.com',
        'token_expire' => 1800, // Token过期时间(秒)
        'max_retry' => 5, // 最大重试次数
        'default_model' => 'kling-v1',
        'default_mode' => 'std',
        'default_aspect_ratio' => '16:9',
        'default_duration' => '5',
    ],

    // 队列配置
    'queue' => [
        'prefix' => 'aivideo:',
        'task_queue' => 'task',
        'max_concurrent' => 10, // 最大并发数
    ],

    // 订单配置
    'order' => [
        'expire_time' => 1800, // 订单过期时间(秒),30分钟
    ],

    // 浏览记录配置
    'browse' => [
        'expire_days' => 30, // 浏览记录保留天数
    ],

    // 文件上传配置
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'mp4'],
        'upload_path' => ROOT_PATH . 'upload/aivideo/',
        'material_path' => ROOT_PATH . 'upload/aivideo/material/',
        'work_path' => ROOT_PATH . 'upload/aivideo/work/',
        'thumbnail_path' => ROOT_PATH . 'upload/aivideo/thumbnail/',
        'qrcode_path' => ROOT_PATH . 'upload/aivideo/qrcode/',
    ],

    // 监控程序配置
    'monitor' => [
        'max_retry' => 5, // 上传失败重试次数
        'check_interval' => 5, // 文件检查间隔(秒)
    ],
];
```

**验证**:
```bash
php -l /www/wwwroot/eivie/config/aivideo.php
```

**成功标准**:
- [ ] 配置文件创建成功
- [ ] 语法检查通过
- [ ] 所有目录路径正确

---

### 阶段2: 可灵AI接口封装

#### 任务4: 创建可灵AI服务类
**优先级**: 高
**预计时间**: 10分钟
**依赖**: 任务2, 任务3

**操作**:
- CREATE `/www/wwwroot/eivie/app/service/KlingAIService.php`

**代码**:
```php
<?php
/**
 * 可灵AI服务类
 * 封装可灵AI接口调用,包括JWT鉴权、图生视频、文生视频等功能
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

namespace app\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Log;
use think\facade\Db;

class KlingAIService
{
    private $config;
    private $apiUrl;
    private $accounts = [];
    private $currentAccountIndex = 0;

    /**
     * 构造函数
     * @param int $bid 商家ID
     */
    public function __construct($bid = 0)
    {
        $this->config = config('aivideo');
        $this->apiUrl = $this->config['kling']['api_url'];

        if ($bid > 0) {
            $this->loadAccounts($bid);
        }
    }

    /**
     * 加载商家的可灵AI账号
     * @param int $bid 商家ID
     */
    private function loadAccounts($bid)
    {
        $configs = Db::name('aivideo_merchant_config')
            ->where('bid', $bid)
            ->where('status', 1)
            ->select()
            ->toArray();

        foreach ($configs as $config) {
            $this->accounts[] = [
                'id' => $config['id'],
                'access_key' => $config['access_key'],
                'secret_key' => $config['secret_key'],
                'model_name' => $config['model_name'],
                'mode' => $config['mode'],
                'aspect_ratio' => $config['aspect_ratio'],
                'duration' => $config['duration'],
            ];
        }
    }

    /**
     * 获取下一个可用的账号(轮询)
     * @return array|null
     */
    private function getNextAccount()
    {
        if (empty($this->accounts)) {
            return null;
        }

        $account = $this->accounts[$this->currentAccountIndex];
        $this->currentAccountIndex = ($this->currentAccountIndex + 1) % count($this->accounts);

        return $account;
    }

    /**
     * 生成JWT Token
     * @param string $accessKey AccessKey
     * @param string $secretKey SecretKey
     * @return string
     */
    public function generateToken($accessKey, $secretKey)
    {
        $headers = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $payload = [
            'iss' => $accessKey,
            'exp' => time() + $this->config['kling']['token_expire'],
            'nbf' => time() - 5
        ];

        return JWT::encode($payload, $secretKey, 'HS256', null, $headers);
    }

    /**
     * 发送HTTP请求
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @param string $token JWT Token
     * @param string $method 请求方法
     * @return array
     */
    private function sendRequest($url, $data, $token, $method = 'POST')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, $method === 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('KlingAI请求失败: ' . $error);
            return ['success' => false, 'message' => $error];
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200) {
            Log::error('KlingAI请求失败: HTTP ' . $httpCode . ', Response: ' . $response);
            return ['success' => false, 'message' => $result['message'] ?? '请求失败'];
        }

        return ['success' => true, 'data' => $result];
    }

    /**
     * 图生视频
     * @param array $params 参数
     * @return array
     */
    public function image2video($params)
    {
        $account = $this->getNextAccount();
        if (!$account) {
            return ['success' => false, 'message' => '没有可用的可灵AI账号'];
        }

        $token = $this->generateToken($account['access_key'], $account['secret_key']);
        $url = $this->apiUrl . '/v1/videos/image2video';

        $data = [
            'model_name' => $params['model_name'] ?? $account['model_name'],
            'image' => $params['image'],
            'prompt' => $params['prompt'] ?? '',
            'negative_prompt' => $params['negative_prompt'] ?? '',
            'mode' => $params['mode'] ?? $account['mode'],
            'duration' => $params['duration'] ?? $account['duration'],
            'callback_url' => $params['callback_url'] ?? '',
            'external_task_id' => $params['external_task_id'] ?? '',
        ];

        return $this->sendRequest($url, $data, $token);
    }

    /**
     * 文生视频
     * @param array $params 参数
     * @return array
     */
    public function text2video($params)
    {
        $account = $this->getNextAccount();
        if (!$account) {
            return ['success' => false, 'message' => '没有可用的可灵AI账号'];
        }

        $token = $this->generateToken($account['access_key'], $account['secret_key']);
        $url = $this->apiUrl . '/v1/videos/omni-video';

        $data = [
            'model_name' => $params['model_name'] ?? $account['model_name'],
            'prompt' => $params['prompt'] ?? '',
            'mode' => $params['mode'] ?? $account['mode'],
            'aspect_ratio' => $params['aspect_ratio'] ?? $account['aspect_ratio'],
            'duration' => $params['duration'] ?? $account['duration'],
            'callback_url' => $params['callback_url'] ?? '',
            'external_task_id' => $params['external_task_id'] ?? '',
        ];

        return $this->sendRequest($url, $data, $token);
    }

    /**
     * 视频特效
     * @param array $params 参数
     * @return array
     */
    public function effects($params)
    {
        $account = $this->getNextAccount();
        if (!$account) {
            return ['success' => false, 'message' => '没有可用的可灵AI账号'];
        }

        $token = $this->generateToken($account['access_key'], $account['secret_key']);
        $url = $this->apiUrl . '/v1/videos/effects';

        $data = [
            'effect_scene' => $params['effect_scene'],
            'input' => $params['input'],
            'callback_url' => $params['callback_url'] ?? '',
            'external_task_id' => $params['external_task_id'] ?? '',
        ];

        return $this->sendRequest($url, $data, $token);
    }

    /**
     * 查询任务状态
     * @param string $taskId 任务ID
     * @return array
     */
    public function queryTask($taskId)
    {
        $account = $this->getNextAccount();
        if (!$account) {
            return ['success' => false, 'message' => '没有可用的可灵AI账号'];
        }

        $token = $this->generateToken($account['access_key'], $account['secret_key']);
        $url = $this->apiUrl . '/v1/videos/image2video/' . $taskId;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode !== 200) {
            return ['success' => false, 'message' => $result['message'] ?? '查询失败'];
        }

        return ['success' => true, 'data' => $result];
    }
}
```

**验证**:
```bash
php -l /www/wwwroot/eivie/app/service/KlingAIService.php
```

**成功标准**:
- [ ] 类文件创建成功
- [ ] 语法检查通过
- [ ] 所有方法定义完整

---

#### 任务5: 创建AI旅拍公共类
**优先级**: 高
**预计时间**: 8分钟
**依赖**: 任务4

**操作**:
- CREATE `/www/wwwroot/eivie/app/common/Aivideo.php`

**代码**:
```php
<?php
/**
 * AI旅拍公共类
 * 提供AI旅拍功能的公共方法
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

namespace app\common;

use think\facade\Db;
use think\facade\Log;
use app\service\KlingAIService;

class Aivideo
{
    /**
     * 创建AI生成任务
     * @param int $aid 应用ID
     * @param int $bid 商家ID
     * @param int $mid 会员ID
     * @param array $params 任务参数
     * @return array
     */
    public static function createTask($aid, $bid, $mid, $params)
    {
        Db::startTrans();
        try {
            // 创建任务记录
            $taskData = [
                'aid' => $aid,
                'bid' => $bid,
                'mid' => $mid,
                'task_type' => $params['task_type'],
                'task_name' => $params['task_name'] ?? '',
                'material_id' => $params['material_id'] ?? 0,
                'material_url' => $params['material_url'] ?? '',
                'prompt' => $params['prompt'] ?? '',
                'negative_prompt' => $params['negative_prompt'] ?? '',
                'model_name' => $params['model_name'] ?? '',
                'mode' => $params['mode'] ?? '',
                'aspect_ratio' => $params['aspect_ratio'] ?? '',
                'duration' => $params['duration'] ?? '',
                'effect_scene' => $params['effect_scene'] ?? '',
                'external_task_id' => $params['external_task_id'] ?? '',
                'task_status' => 'pending',
                'request_data' => json_encode($params),
                'createtime' => time(),
                'updatetime' => time(),
            ];

            $taskId = Db::name('aivideo_task')->insertGetId($taskData);

            // 加入队列
            $queueData = [
                'task_id' => $taskId,
                'aid' => $aid,
                'bid' => $bid,
                'mid' => $mid,
                'params' => $params,
            ];

            $redis = \think\facade\Cache::store('redis')->handler();
            $redis->lpush(config('aivideo.queue.prefix') . 'task', json_encode($queueData));

            Db::commit();

            return ['success' => true, 'task_id' => $taskId];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创建AI任务失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '创建任务失败'];
        }
    }

    /**
     * 处理AI任务
     * @param array $taskData 任务数据
     * @return array
     */
    public static function processTask($taskData)
    {
        $taskId = $taskData['task_id'];
        $aid = $taskData['aid'];
        $bid = $taskData['bid'];
        $params = $taskData['params'];

        // 更新任务状态为已提交
        Db::name('aivideo_task')->where('id', $taskId)->update([
            'task_status' => 'submitted',
            'updatetime' => time(),
        ]);

        // 调用可灵AI接口
        $klingService = new KlingAIService($bid);
        $result = [];

        switch ($params['task_type']) {
            case 'image2video':
                $result = $klingService->image2video($params);
                break;
            case 'text2video':
                $result = $klingService->text2video($params);
                break;
            case 'effects':
                $result = $klingService->effects($params);
                break;
            default:
                return ['success' => false, 'message' => '不支持的任务类型'];
        }

        if (!$result['success']) {
            // 更新任务状态为失败
            Db::name('aivideo_task')->where('id', $taskId)->update([
                'task_status' => 'failed',
                'task_status_msg' => $result['message'],
                'response_data' => json_encode($result),
                'updatetime' => time(),
            ]);
            return ['success' => false, 'message' => $result['message']];
        }

        // 更新任务状态为处理中
        Db::name('aivideo_task')->where('id', $taskId)->update([
            'task_status' => 'processing',
            'kling_task_id' => $result['data']['data']['task_id'] ?? '',
            'response_data' => json_encode($result['data']),
            'updatetime' => time(),
        ]);

        return ['success' => true];
    }

    /**
     * 查询任务状态并处理结果
     * @param int $taskId 任务ID
     * @return array
     */
    public static function checkTaskStatus($taskId)
    {
        $task = Db::name('aivideo_task')->where('id', $taskId)->find();
        if (!$task) {
            return ['success' => false, 'message' => '任务不存在'];
        }

        if ($task['task_status'] != 'processing') {
            return ['success' => false, 'message' => '任务状态不正确'];
        }

        // 查询可灵AI任务状态
        $klingService = new KlingAIService($task['bid']);
        $result = $klingService->queryTask($task['kling_task_id']);

        if (!$result['success']) {
            return ['success' => false, 'message' => $result['message']];
        }

        $data = $result['data']['data'];
        $taskStatus = $data['task_status'] ?? '';

        if ($taskStatus == 'succeed') {
            // 任务成功,创建作品记录
            return self::handleTaskSuccess($task, $data);
        } elseif ($taskStatus == 'failed') {
            // 任务失败
            Db::name('aivideo_task')->where('id', $taskId)->update([
                'task_status' => 'failed',
                'task_status_msg' => $data['task_status_msg'] ?? '生成失败',
                'response_data' => json_encode($data),
                'updatetime' => time(),
            ]);
            return ['success' => false, 'message' => '任务失败'];
        }

        // 任务仍在处理中
        return ['success' => false, 'message' => '任务处理中'];
    }

    /**
     * 处理任务成功
     * @param array $task 任务数据
     * @param array $klingData 可灵AI返回数据
     * @return array
     */
    private static function handleTaskSuccess($task, $klingData)
    {
        Db::startTrans();
        try {
            // 更新任务状态
            Db::name('aivideo_task')->where('id', $task['id'])->update([
                'task_status' => 'succeed',
                'response_data' => json_encode($klingData),
                'updatetime' => time(),
                'completetime' => time(),
            ]);

            // 创建作品记录
            $videos = $klingData['task_result']['videos'] ?? [];
            foreach ($videos as $video) {
                $workData = [
                    'aid' => $task['aid'],
                    'bid' => $task['bid'],
                    'mid' => $task['mid'],
                    'task_id' => $task['id'],
                    'work_name' => $task['task_name'],
                    'work_type' => 'video',
                    'work_url' => $video['url'] ?? '',
                    'video_id' => $video['id'] ?? '',
                    'duration' => $video['duration'] ?? '',
                    'price' => 0, // 默认价格,商家可修改
                    'createtime' => time(),
                ];

                $workId = Db::name('aivideo_work')->insertGetId($workData);

                // 生成预览图和二维码
                self::generateThumbnailAndQrcode($workId, $video['url'] ?? '');
            }

            Db::commit();
            return ['success' => true];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('处理任务成功失败: ' . $e->getMessage());
            return ['success' => false, 'message' => '处理失败'];
        }
    }

    /**
     * 生成预览图和二维码
     * @param int $workId 作品ID
     * @param string $videoUrl 视频URL
     * @return bool
     */
    private static function generateThumbnailAndQrcode($workId, $videoUrl)
    {
        try {
            $config = config('aivideo');

            // 提取视频第一帧
            $thumbnailPath = self::extractVideoFrame($videoUrl, $workId);
            if (!$thumbnailPath) {
                return false;
            }

            // 生成二维码
            $qrcodeUrl = self::generateQrcode($workId);
            if (!$qrcodeUrl) {
                return false;
            }

            // 合并预览图和二维码
            $finalThumbnailPath = self::mergeThumbnailAndQrcode($thumbnailPath, $qrcodeUrl, $workId);
            if (!$finalThumbnailPath) {
                return false;
            }

            // 更新作品记录
            $thumbnailUrl = str_replace(ROOT_PATH, '/', $finalThumbnailPath);
            Db::name('aivideo_work')->where('id', $workId)->update([
                'thumbnail_url' => $thumbnailUrl,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('生成预览图和二维码失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 提取视频第一帧
     * @param string $videoUrl 视频URL
     * @param int $workId 作品ID
     * @return string|false
     */
    private static function extractVideoFrame($videoUrl, $workId)
    {
        $config = config('aivideo');
        $thumbnailPath = $config['upload']['thumbnail_path'] . $workId . '.jpg';

        // 使用FFmpeg提取第一帧
        $command = "ffmpeg -i {$videoUrl} -ss 00:00:00.000 -vframes 1 {$thumbnailPath} 2>&1";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('提取视频帧失败: ' . implode("\n", $output));
            return false;
        }

        return $thumbnailPath;
    }

    /**
     * 生成二维码
     * @param int $workId 作品ID
     * @return string|false
     */
    private static function generateQrcode($workId)
    {
        $config = config('aivideo');
        $qrcodePath = $config['upload']['qrcode_path'] . $workId . '.png';

        // 生成作品访问URL
        $workUrl = request()->domain() . '/api/aivideo/work_detail?id=' . $workId;

        // 使用endroid/qrcode生成二维码
        $qrCode = new \Endroid\QrCode\QrCode($workUrl);
        $qrCode->setSize(150);
        $qrCode->setMargin(10);

        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);

        file_put_contents($qrcodePath, $result->getString());

        return $qrcodePath;
    }

    /**
     * 合并预览图和二维码
     * @param string $thumbnailPath 预览图路径
     * @param string $qrcodePath 二维码路径
     * @param int $workId 作品ID
     * @return string|false
     */
    private static function mergeThumbnailAndQrcode($thumbnailPath, $qrcodePath, $workId)
    {
        $config = config('aivideo');
        $finalPath = $config['upload']['thumbnail_path'] . $workId . '_final.jpg';

        // 加载预览图
        $thumbnail = imagecreatefromjpeg($thumbnailPath);
        if (!$thumbnail) {
            return false;
        }

        // 加载二维码
        $qrcode = imagecreatefrompng($qrcodePath);
        if (!$qrcode) {
            return false;
        }

        // 获取尺寸
        $thumbWidth = imagesx($thumbnail);
        $thumbHeight = imagesy($thumbnail);
        $qrWidth = imagesx($qrcode);
        $qrHeight = imagesy($qrcode);

        // 将二维码放在右下角
        $x = $thumbWidth - $qrWidth - 20;
        $y = $thumbHeight - $qrHeight - 20;

        // 合并图片
        imagecopy($thumbnail, $qrcode, $x, $y, 0, 0, $qrWidth, $qrHeight);

        // 保存最终图片
        imagejpeg($thumbnail, $finalPath, 90);

        // 释放资源
        imagedestroy($thumbnail);
        imagedestroy($qrcode);

        return $finalPath;
    }
}
```

**验证**:
```bash
php -l /www/wwwroot/eivie/app/common/Aivideo.php
```

**成功标准**:
- [ ] 类文件创建成功
- [ ] 语法检查通过
- [ ] 所有方法定义完整

---

### 阶段3: 商家监控程序

#### 任务6: 创建商家监控程序
**优先级**: 高
**预计时间**: 15分钟
**依赖**: 任务5

**操作**:
- CREATE `/www/wwwroot/eivie/app/monitor/AivideoMonitor.php`

**代码**:
```php
<?php
/**
 * AI旅拍商家监控程序
 * 监控本地文件夹,自动上传新文件到服务器
 * @author AI旅拍开发团队
 * @date 2026-01-19
 * 仅支持Windows平台
 */

namespace app\monitor;

use think\facade\Log;
use think\facade\Db;

class AivideoMonitor
{
    private $config;
    private $bid;
    private $monitorPaths = [];
    private $uploadedFiles = [];

    /**
     * 构造函数
     * @param int $bid 商家ID
     */
    public function __construct($bid)
    {
        $this->config = config('aivideo');
        $this->bid = $bid;

        // 加载商家配置
        $merchantConfig = Db::name('aivideo_merchant_config')
            ->where('bid', $bid)
            ->where('status', 1)
            ->find();

        if (!$merchantConfig) {
            die('商家配置不存在或未启用');
        }

        // 解析监控路径(支持多目录,用分号分隔)
        $paths = explode(';', $merchantConfig['monitor_path']);
        foreach ($paths as $path) {
            $path = trim($path);
            if (!empty($path) && is_dir($path)) {
                $this->monitorPaths[] = $path;
            }
        }

        if (empty($this->monitorPaths)) {
            die('监控路径不存在或无效');
        }

        echo "商家ID: {$bid}\n";
        echo "监控路径: " . implode('; ', $this->monitorPaths) . "\n";
        echo "开始监控...\n";
    }

    /**
     * 开始监控
     */
    public function start()
    {
        // 加载已上传文件列表
        $this->loadUploadedFiles();

        // 持续监控
        while (true) {
            $this->checkNewFiles();
            sleep($this->config['monitor']['check_interval']);
        }
    }

    /**
     * 加载已上传文件列表
     */
    private function loadUploadedFiles()
    {
        $materials = Db::name('aivideo_material')
            ->where('bid', $this->bid)
            ->where('upload_type', 'auto')
            ->column('material_path');

        $this->uploadedFiles = array_flip($materials);
    }

    /**
     * 检查新文件
     */
    private function checkNewFiles()
    {
        foreach ($this->monitorPaths as $path) {
            $this->scanDirectory($path);
        }
    }

    /**
     * 扫描目录
     * @param string $directory 目录路径
     */
    private function scanDirectory($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $filePath = $directory . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                $this->scanDirectory($filePath);
            } else {
                $this->processFile($filePath);
            }
        }
    }

    /**
     * 处理文件
     * @param string $filePath 文件路径
     */
    private function processFile($filePath)
    {
        // 检查是否已上传
        if (isset($this->uploadedFiles[$filePath])) {
            return;
        }

        // 检查文件类型
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->config['upload']['allowed_types'])) {
            return;
        }

        // 检查文件大小
        $fileSize = filesize($filePath);
        if ($fileSize > $this->config['upload']['max_size']) {
            echo "文件过大: {$filePath}\n";
            return;
        }

        echo "发现新文件: {$filePath}\n";

        // 上传文件
        $result = $this->uploadFile($filePath, 0);

        if ($result['success']) {
            $this->uploadedFiles[$filePath] = true;
            echo "上传成功: {$filePath}\n";
        } else {
            echo "上传失败: {$filePath}, 错误: {$result['message']}\n";
        }
    }

    /**
     * 上传文件
     * @param string $filePath 文件路径
     * @param int $retryCount 重试次数
     * @return array
     */
    private function uploadFile($filePath, $retryCount = 0)
    {
        if ($retryCount >= $this->config['monitor']['max_retry']) {
            return ['success' => false, 'message' => '超过最大重试次数'];
        }

        $url = 'http://localhost/api/aivideo/upload_material';
        $postFields = [
            'bid' => $this->bid,
            'file' => new \CURLFile($filePath),
            'upload_type' => 'auto',
            'upload_source' => 'monitor',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('上传文件失败: ' . $error);
            return $this->uploadFile($filePath, $retryCount + 1);
        }

        if ($httpCode !== 200) {
            Log::error('上传文件失败: HTTP ' . $httpCode);
            return $this->uploadFile($filePath, $retryCount + 1);
        }

        $result = json_decode($response, true);
        if ($result['code'] != 0) {
            Log::error('上传文件失败: ' . $result['msg']);
            return $this->uploadFile($filePath, $retryCount + 1);
        }

        return ['success' => true, 'data' => $result['data']];
    }
}

// 命令行入口
if (php_sapi_name() === 'cli') {
    $bid = $argv[1] ?? 0;
    if (!$bid) {
        die("用法: php AivideoMonitor.php <商家ID>\n");
    }

    $monitor = new AivideoMonitor($bid);
    $monitor->start();
}
```

**验证**:
```bash
php -l /www/wwwroot/eivie/app/monitor/AivideoMonitor.php
```

**成功标准**:
- [ ] 监控程序创建成功
- [ ] 语法检查通过
- [ ] 支持多目录监控
- [ ] 支持失败重试

---

### 阶段4: 游客端API

#### 任务7: 创建游客端API控制器
**优先级**: 高
**预计时间**: 20分钟
**依赖**: 任务5

**操作**:
- CREATE `/www/wwwroot/eivie/app/controller/ApiAivideo.php`

**代码**:
```php
<?php
/**
 * AI旅拍游客端API控制器
 * 提供游客扫码、选片、支付等功能
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Log;
use app\common\Aivideo;
use app\common\Member;
use app\common\Wechat;

class ApiAivideo extends BaseController
{
    public $aid;
    public $mid;

    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();

        $aid = input('param.aid/d');
        if (!$aid) {
            echo jsonEncode(['status' => 0, 'msg' => '参数错误']);
            exit;
        }

        $this->aid = $aid;
        define('aid', $aid);

        // 获取游客ID
        $this->mid = input('param.mid/d');
    }

    /**
     * 微信授权
     * @return string
     */
    public function wechat_auth()
    {
        $code = input('param.code');
        if (!$code) {
            return jsonEncode(['status' => 0, 'msg' => '授权码不能为空']);
        }

        // 调用微信OAuth获取用户信息
        $wechat = new Wechat($this->aid);
        $userInfo = $wechat->getOauthUserInfo($code);

        if (!$userInfo) {
            return jsonEncode(['status' => 0, 'msg' => '授权失败']);
        }

        // 查找或创建会员
        $member = Db::name('member')
            ->where('aid', $this->aid)
            ->where('wxopenid', $userInfo['openid'])
            ->find();

        if (!$member) {
            // 创建新会员
            $memberData = [
                'aid' => $this->aid,
                'wxopenid' => $userInfo['openid'],
                'nickname' => $userInfo['nickname'] ?? '',
                'headimg' => $userInfo['headimgurl'] ?? '',
                'platform' => 'mp',
                'createtime' => time(),
            ];

            $memberModel = new \app\model\Member();
            $mid = $memberModel->add($this->aid, $memberData);
        } else {
            $mid = $member['id'];
        }

        // 生成访问令牌
        $token = md5($mid . time() . rand(1000, 9999));

        // 返回结果
        return jsonEncode([
            'status' => 1,
            'msg' => '授权成功',
            'data' => [
                'mid' => $mid,
                'openid' => $userInfo['openid'],
                'access_token' => $token,
                'nickname' => $userInfo['nickname'] ?? '',
                'headimg' => $userInfo['headimgurl'] ?? '',
            ]
        ]);
    }

    /**
     * 获取作品列表
     * @return string
     */
    public function work_list()
    {
        $mid = input('param.mid/d');
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        if (!$mid) {
            return jsonEncode(['status' => 0, 'msg' => '会员ID不能为空']);
        }

        // 查询作品列表
        $where = [
            ['aid', '=', $this->aid],
            ['mid', '=', $mid],
            ['status', '=', 1],
        ];

        $list = Db::name('aivideo_work')
            ->where($where)
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        // 查询总数
        $total = Db::name('aivideo_work')
            ->where($where)
            ->count();

        // 检查是否已支付
        foreach ($list as &$item) {
            $order = Db::name('aivideo_order')
                ->where('work_id', $item['id'])
                ->where('mid', $mid)
                ->where('pay_status', 1)
                ->find();

            $item['is_paid'] = $order ? true : false;
        }

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 获取作品详情
     * @return string
     */
    public function work_detail()
    {
        $workId = input('param.id/d');
        $mid = input('param.mid/d');

        if (!$workId) {
            return jsonEncode(['status' => 0, 'msg' => '作品ID不能为空']);
        }

        // 查询作品详情
        $work = Db::name('aivideo_work')
            ->where('id', $workId)
            ->where('aid', $this->aid)
            ->find();

        if (!$work) {
            return jsonEncode(['status' => 0, 'msg' => '作品不存在']);
        }

        // 记录浏览记录
        if ($mid) {
            Db::name('aivideo_selection')->insert([
                'aid' => $this->aid,
                'bid' => $work['bid'],
                'mid' => $mid,
                'work_id' => $workId,
                'selection_type' => 'select',
                'device_info' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => request()->ip(),
                'createtime' => time(),
            ]);
        }

        // 检查是否已支付
        $order = Db::name('aivideo_order')
            ->where('work_id', $workId)
            ->where('mid', $mid)
            ->where('pay_status', 1)
            ->find();

        $work['is_paid'] = $order ? true : false;

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => $work
        ]);
    }

    /**
     * 创建订单
     * @return string
     */
    public function create_order()
    {
        $mid = input('param.mid/d');
        $workIds = input('param.work_ids');

        if (!$mid) {
            return jsonEncode(['status' => 0, 'msg' => '会员ID不能为空']);
        }

        if (!$workIds) {
            return jsonEncode(['status' => 0, 'msg' => '请选择作品']);
        }

        $workIdArray = explode(',', $workIds);

        // 查询作品信息
        $works = Db::name('aivideo_work')
            ->whereIn('id', $workIdArray)
            ->where('aid', $this->aid)
            ->select()
            ->toArray();

        if (count($works) != count($workIdArray)) {
            return jsonEncode(['status' => 0, 'msg' => '部分作品不存在']);
        }

        // 计算总价
        $totalPrice = 0;
        foreach ($works as $work) {
            $totalPrice += $work['price'];
        }

        // 生成订单号
        $ordernum = 'AV' . date('YmdHis') . rand(1000, 9999);

        // 创建订单
        Db::startTrans();
        try {
            $orderData = [
                'aid' => $this->aid,
                'bid' => $works[0]['bid'],
                'mid' => $mid,
                'ordernum' => $ordernum,
                'work_id' => $workIdArray[0],
                'work_ids' => $workIds,
                'work_count' => count($works),
                'total_price' => $totalPrice,
                'pay_price' => $totalPrice,
                'pay_status' => 0,
                'status' => 1,
                'createtime' => time(),
                'updatetime' => time(),
            ];

            $orderId = Db::name('aivideo_order')->insertGetId($orderData);

            Db::commit();

            // 返回订单信息
            return jsonEncode([
                'status' => 1,
                'msg' => '创建成功',
                'data' => [
                    'order_id' => $orderId,
                    'ordernum' => $ordernum,
                    'total_price' => $totalPrice,
                    'work_list' => $works,
                ]
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创建订单失败: ' . $e->getMessage());
            return jsonEncode(['status' => 0, 'msg' => '创建订单失败']);
        }
    }

    /**
     * 支付回调
     * @return string
     */
    public function pay_callback()
    {
        $ordernum = input('param.ordernum');
        $payType = input('param.pay_type');
        $transactionId = input('param.transaction_id');

        if (!$ordernum || !$payType) {
            return jsonEncode(['status' => 0, 'msg' => '参数错误']);
        }

        // 查询订单
        $order = Db::name('aivideo_order')
            ->where('ordernum', $ordernum)
            ->where('pay_status', 0)
            ->find();

        if (!$order) {
            return jsonEncode(['status' => 0, 'msg' => '订单不存在或已支付']);
        }

        Db::startTrans();
        try {
            // 更新订单状态
            Db::name('aivideo_order')->where('id', $order['id'])->update([
                'pay_type' => $payType,
                'pay_status' => 1,
                'pay_time' => time(),
                'transaction_id' => $transactionId,
                'updatetime' => time(),
            ]);

            // 更新作品为已支付
            $workIds = explode(',', $order['work_ids']);
            Db::name('aivideo_work')
                ->whereIn('id', $workIds)
                ->where('mid', $order['mid'])
                ->update([
                    'mid' => $order['mid'],
                    'is_free' => 0,
                ]);

            // 发送通知
            $this->sendPayNotification($order);

            Db::commit();

            return jsonEncode(['status' => 1, 'msg' => '支付成功']);
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('支付回调失败: ' . $e->getMessage());
            return jsonEncode(['status' => 0, 'msg' => '处理失败']);
        }
    }

    /**
     * 发送支付成功通知
     * @param array $order 订单信息
     */
    private function sendPayNotification($order)
    {
        // 查询会员信息
        $member = Db::name('member')->where('id', $order['mid'])->find();
        if (!$member) {
            return;
        }

        // 发送微信模板消息
        $wechat = new Wechat($this->aid);
        $templateData = [
            'first' => '您的AI旅拍作品已支付成功',
            'keyword1' => $order['ordernum'],
            'keyword2' => $order['total_price'] . '元',
            'keyword3' => $order['work_count'] . '个作品',
            'remark' => '作品已自动保存到您的相册',
        ];

        $wechat->sendTemplateMessage($member['wxopenid'], '支付成功通知', $templateData);
    }

    /**
     * 获取浏览记录
     * @return string
     */
    public function browse_history()
    {
        $mid = input('param.mid/d');
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        if (!$mid) {
            return jsonEncode(['status' => 0, 'msg' => '会员ID不能为空']);
        }

        // 查询浏览记录
        $where = [
            ['aid', '=', $this->aid],
            ['mid', '=', $mid],
        ];

        $list = Db::name('aivideo_selection')
            ->alias('s')
            ->leftJoin('aivideo_work w', 's.work_id = w.id')
            ->where($where)
            ->order('s.id desc')
            ->page($page, $limit)
            ->field('s.*,w.work_name,w.thumbnail_url,w.price')
            ->select()
            ->toArray();

        // 查询总数
        $total = Db::name('aivideo_selection')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }
}
```

**验证**:
```bash
php -l /www/wwwroot/eivie/app/controller/ApiAivideo.php
```

**成功标准**:
- [ ] 控制器创建成功
- [ ] 语法检查通过
- [ ] 所有方法定义完整

---

由于篇幅限制,我将创建一个包含所有任务的完整实施计划文档。让我继续创建剩余的任务。</think>由于任务较多,让我将完整的实施计划保存到文档中:<tool_call>Write<arg_key>content</arg_key><arg_value># AI旅拍功能实施计划(完整版)

## 概述
本计划用于实施AI旅拍功能,包括商家监控、AI视频生成、游客选片支付等核心功能。

## 设计参考
- 设计文档：[AI旅拍功能设计文档.md](/www/wwwroot/eivie/keling/AI旅拍功能设计文档.md)
- 数据库表结构：[aivideo_tables.sql](/www/wwwroot/eivie/keling/aivideo_tables.sql)
- 可灵AI文档：[可灵 AI 使用指南.md](/www/wwwroot/eivie/keling/可灵 AI 使用指南.md)

---

## 阶段1: 基础设施搭建 (预计15分钟)

### 任务1: 创建数据库表
**优先级**: 高
**预计时间**: 5分钟
**依赖**: 无

**操作**: EXECUTE SQL file

**验证**:
```bash
mysql -h localhost -u guobaoyungou_cn -p guobaoyungou_cn -e "SHOW TABLES LIKE 'ddwx_aivideo_%';"
```

**成功标准**:
- [ ] 7个数据库表创建成功

### 任务2: 安装JWT依赖包
**优先级**: 高
**预计时间**: 3分钟
**依赖**: 无

**操作**: EXECUTE composer command

**验证**:
```bash
grep -r "firebase/php-jwt" /www/wwwroot/eivie/composer.json
```

**成功标准**:
- [ ] firebase/php-jwt包安装成功

### 任务3: 创建AI旅拍配置文件
**优先级**: 高
**预计时间**: 2分钟
**依赖**: 任务1

**操作**: CREATE `/www/wwwroot/eivie/config/aivideo.php`

**验证**:
```bash
php -l /www/wwwroot/eivie/config/aivideo.php
```

**成功标准**:
- [ ] 配置文件创建成功

### 任务4: 创建目录结构
**优先级**: 高
**预计时间**: 5分钟
**依赖**: 任务3

**操作**: CREATE directories

**验证**:
```bash
ls -la /www/wwwroot/eivie/upload/aivideo/
```

**成功标准**:
- [ ] 所有目录创建成功

---

## 阶段2: 可灵AI接口封装 (预计20分钟)

### 任务5: 创建可灵AI服务类
**优先级**: 高
**预计时间**: 10分钟
**依赖**: 任务2, 任务3

**操作**: CREATE `/www/wwwroot/eivie/app/service/KlingAIService.php`

**代码**: 见上文任务4的完整代码

**验证**:
```bash
php -l /www/wwwroot/eivie/app/service/KlingAIService.php
```

**成功标准**:
- [ ] 类文件创建成功
- [ ] 语法检查通过

### 任务6: 创建AI旅拍公共类
**优先级**: 高
**预计时间**: 10分钟
**依赖**: 任务5

**操作**: CREATE `/www/wwwroot/eivie/app/common/Aivideo.php`

**代码**: 见上文任务5的完整代码

**验证**:
```bash
php -l /www/wwwroot/eivie/app/common/Aivideo.php
```

**成功标准**:
- [ ] 类文件创建成功
- [ ] 语法检查通过

---

## 阶段3: 商家监控程序 (预计20分钟)

### 任务7: 创建商家监控程序
**优先级**: 高
**预计时间**: 20分钟
**依赖**: 任务6

**操作**: CREATE `/www/wwwroot/eivie/app/monitor/AivideoMonitor.php`

**代码**: 见上文任务6的完整代码

**验证**:
```bash
php -l /www/wwwroot/eivie/app/monitor/AivideoMonitor.php
```

**成功标准**:
- [ ] 监控程序创建成功
- [ ] 支持多目录监控

---

## 阶段4: 游客端API (预计30分钟)

### 任务8: 创建游客端API控制器
**优先级**: 高
**预计时间**: 30分钟
**依赖**: 任务6

**操作**: CREATE `/www/wwwroot/eivie/app/controller/ApiAivideo.php`

**代码**: 见上文任务7的完整代码

**验证**:
```bash
php -l /www/wwwroot/eivie/app/controller/ApiAivideo.php
```

**成功标准**:
- [ ] 控制器创建成功
- [ ] 语法检查通过

---

## 阶段5: 商家后台管理 (预计40分钟)

### 任务9: 创建商家后台控制器
**优先级**: 中
**预计时间**: 40分钟
**依赖**: 任务6

**操作**: CREATE `/www/wwwroot/eivie/app/controller/AdminAivideo.php`

**代码**:
```php
<?php
/**
 * AI旅拍商家后台控制器
 * 提供商家管理AI旅拍功能的后台界面
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Log;

class AdminAivideo extends BaseController
{
    public $aid;

    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        $this->aid = input('param.aid/d');
        define('aid', $this->aid);
    }

    /**
     * 商家配置列表
     * @return string
     */
    public function config_list()
    {
        $bid = input('param.bid/d', 0);
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $where = [
            ['aid', '=', $this->aid],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        $list = Db::name('aivideo_merchant_config')
            ->where($where)
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $total = Db::name('aivideo_merchant_config')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 保存商家配置
     * @return string
     */
    public function save_config()
    {
        $id = input('param.id/d', 0);
        $bid = input('param.bid/d');
        $merchantName = input('param.merchant_name');
        $accessKey = input('param.access_key');
        $secretKey = input('param.secret_key');
        $monitorPath = input('param.monitor_path');
        $modelName = input('param.model_name', 'kling-v1');
        $mode = input('param.mode', 'std');
        $aspectRatio = input('param.aspect_ratio', '16:9');
        $duration = input('param.duration', '5');
        $autoUpload = input('param.auto_upload/d', 1);

        if (!$bid || !$merchantName || !$accessKey || !$secretKey) {
            return jsonEncode(['status' => 0, 'msg' => '参数不完整']);
        }

        $data = [
            'aid' => $this->aid,
            'bid' => $bid,
            'merchant_name' => $merchantName,
            'access_key' => $accessKey,
            'secret_key' => $secretKey,
            'monitor_path' => $monitorPath,
            'model_name' => $modelName,
            'mode' => $mode,
            'aspect_ratio' => $aspectRatio,
            'duration' => $duration,
            'auto_upload' => $autoUpload,
            'status' => 1,
            'updatetime' => time(),
        ];

        if ($id > 0) {
            // 更新
            $data['id'] = $id;
            Db::name('aivideo_merchant_config')->where('id', $id)->update($data);
        } else {
            // 新增
            $data['createtime'] = time();
            Db::name('aivideo_merchant_config')->insert($data);
        }

        return jsonEncode(['status' => 1, 'msg' => '保存成功']);
    }

    /**
     * 提示词模板列表
     * @return string
     */
    public function template_list()
    {
        $bid = input('param.bid/d', 0);
        $templateType = input('param.template_type', '');
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $where = [
            ['aid', '=', $this->aid],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        if ($templateType) {
            $where[] = ['template_type', '=', $templateType];
        }

        $list = Db::name('aivideo_prompt_template')
            ->where($where)
            ->order('sort asc, id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $total = Db::name('aivideo_prompt_template')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 保存提示词模板
     * @return string
     */
    public function save_template()
    {
        $id = input('param.id/d', 0);
        $bid = input('param.bid/d', 0);
        $templateName = input('param.template_name');
        $templateType = input('param.template_type');
        $prompt = input('param.prompt');
        $negativePrompt = input('param.negative_prompt', '');
        $modelName = input('param.model_name', '');
        $mode = input('param.mode', '');
        $aspectRatio = input('param.aspect_ratio', '');
        $duration = input('param.duration', '');
        $effectScene = input('param.effect_scene', '');
        $sort = input('param.sort/d', 0);

        if (!$templateName || !$prompt || !$templateType) {
            return jsonEncode(['status' => 0, 'msg' => '参数不完整']);
        }

        $data = [
            'aid' => $this->aid,
            'bid' => $bid,
            'template_name' => $templateName,
            'template_type' => $templateType,
            'prompt' => $prompt,
            'negative_prompt' => $negativePrompt,
            'model_name' => $modelName,
            'mode' => $mode,
            'aspect_ratio' => $aspectRatio,
            'duration' => $duration,
            'effect_scene' => $effectScene,
            'sort' => $sort,
            'status' => 1,
            'updatetime' => time(),
        ];

        if ($id > 0) {
            $data['id'] = $id;
            Db::name('aivideo_prompt_template')->where('id', $id)->update($data);
        } else {
            $data['createtime'] = time();
            Db::name('aivideo_prompt_template')->insert($data);
        }

        return jsonEncode(['status' => 1, 'msg' => '保存成功']);
    }

    /**
     * 素材列表
     * @return string
     */
    public function material_list()
    {
        $bid = input('param.bid/d', 0);
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $where = [
            ['aid', '=', $this->aid],
            ['status', '=', 1],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        $list = Db::name('aivideo_material')
            ->where($where)
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $total = Db::name('aivideo_material')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 作品列表
     * @return string
     */
    public function work_list()
    {
        $bid = input('param.bid/d', 0);
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $where = [
            ['aid', '=', $this->aid],
            ['status', '=', 1],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        $list = Db::name('aivideo_work')
            ->where($where)
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $total = Db::name('aivideo_work')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 订单列表
     * @return string
     */
    public function order_list()
    {
        $bid = input('param.bid/d', 0);
        $payStatus = input('param.pay_status/d', -1);
        $page = input('param.page/d', 1);
        $limit = input('param.limit/d', 20);

        $where = [
            ['aid', '=', $this->aid],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        if ($payStatus >= 0) {
            $where[] = ['pay_status', '=', $payStatus];
        }

        $list = Db::name('aivideo_order')
            ->where($where)
            ->order('id desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $total = Db::name('aivideo_order')
            ->where($where)
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'list' => $list,
                'total' => $total,
            ]
        ]);
    }

    /**
     * 统计数据
     * @return string
     */
    public function statistics()
    {
        $bid = input('param.bid/d', 0);
        $startDate = input('param.start_date');
        $endDate = input('param.end_date');

        $where = [
            ['aid', '=', $this->aid],
        ];

        if ($bid > 0) {
            $where[] = ['bid', '=', $bid];
        }

        if ($startDate) {
            $where[] = ['createtime', '>=', strtotime($startDate)];
        }

        if ($endDate) {
            $where[] = ['createtime', '<=', strtotime($endDate . ' 23:59:59')];
        }

        // 订单统计
        $orderCount = Db::name('aivideo_order')
            ->where($where)
            ->count();

        $paidCount = Db::name('aivideo_order')
            ->where($where)
            ->where('pay_status', 1)
            ->count();

        $totalAmount = Db::name('aivideo_order')
            ->where($where)
            ->where('pay_status', 1)
            ->sum('pay_price');

        // 作品统计
        $workCount = Db::name('aivideo_work')
            ->where($where)
            ->count();

        // 任务统计
        $taskCount = Db::name('aivideo_task')
            ->where($where)
            ->count();

        $successTaskCount = Db::name('aivideo_task')
            ->where($where)
            ->where('task_status', 'succeed')
            ->count();

        return jsonEncode([
            'status' => 1,
            'msg' => '获取成功',
            'data' => [
                'order_count' => $orderCount,
                'paid_count' => $paidCount,
                'total_amount' => $totalAmount ?: 0,
                'work_count' => $workCount,
                'task_count' => $taskCount,
                'success_task_count' => $successTaskCount,
            ]
        ]);
    }
}
```

**验证**:
```bash
php -l /www/wwwroot/eivie/app/controller/AdminAivideo.php
```

**成功标准**:
- [ ] 控制器创建成功
- [ ] 语法检查通过

---

## 阶段6: 定时任务 (预计20分钟)

### 任务10: 创建定时任务
**优先级**: 高
**预计时间**: 20分钟
**依赖**: 任务6

**操作**: CREATE `/www/wwwroot/eivie/app/command/AivideoCron.php`

**代码**:
```php
<?php
/**
 * AI旅拍定时任务
 * 处理任务状态轮询、订单自动取消等定时任务
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use app\common\Aivideo;

class AivideoCron extends Command
{
    protected function configure()
    {
        $this->setName('aivideo:cron')
            ->setDescription('AI旅拍定时任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始执行AI旅拍定时任务...');

        // 轮询任务状态
        $this->pollTaskStatus();

        // 取消超时订单
        $this->cancelExpiredOrders();

        // 清理过期浏览记录
        $this->cleanExpiredBrowseHistory();

        $output->writeln('AI旅拍定时任务执行完成');
    }

    /**
     * 轮询任务状态
     */
    private function pollTaskStatus()
    {
        // 查询处理中的任务
        $tasks = Db::name('aivideo_task')
            ->where('task_status', 'processing')
            ->where('updatetime', '>', time() - 600) // 10分钟前更新的任务
            ->limit(100)
            ->select()
            ->toArray();

        foreach ($tasks as $task) {
            $result = Aivideo::checkTaskStatus($task['id']);
            if ($result['success']) {
                echo "任务 {$task['id']} 处理成功\n";
            }
        }
    }

    /**
     * 取消超时订单
     */
    private function cancelExpiredOrders()
    {
        $config = config('aivideo');
        $expireTime = time() - $config['order']['expire_time'];

        // 查询超时未支付订单
        $orders = Db::name('aivideo_order')
            ->where('pay_status', 0)
            ->where('createtime', '<', $expireTime)
            ->select()
            ->toArray();

        foreach ($orders as $order) {
            Db::startTrans();
            try {
                // 更新订单状态
                Db::name('aivideo_order')->where('id', $order['id'])->update([
                    'pay_status' => 2, // 已取消
                    'updatetime' => time(),
                ]);

                // 释放作品
                $workIds = explode(',', $order['work_ids']);
                Db::name('aivideo_work')
                    ->whereIn('id', $workIds)
                    ->where('mid', 0)
                    ->update([
                        'mid' => 0,
                        'is_free' => 1,
                    ]);

                Db::commit();
                echo "订单 {$order['ordernum']} 已取消\n";
            } catch (\Exception $e) {
                Db::rollback();
                Log::error('取消订单失败: ' . $e->getMessage());
            }
        }
    }

    /**
     * 清理过期浏览记录
     */
    private function cleanExpiredBrowseHistory()
    {
        $config = config('aivideo');
        $expireTime = time() - ($config['browse']['expire_days'] * 86400);

        // 删除过期浏览记录
        $count = Db::name('aivideo_selection')
            ->where('createtime', '<', $expireTime)
            ->delete();

        echo "清理了 {$count} 条过期浏览记录\n";
    }
}
```

**验证**:
```bash
php -l /www/wwwroot/eivie/app/command/AivideoCron.php
```

**成功标准**:
- [ ] 定时任务创建成功
- [ ] 语法检查通过

---

## 验证命令

### 语法检查
```bash
# 检查所有PHP文件语法
find /www/wwwroot/eivie/app/service /www/wwwroot/eivie/app/common /www/wwwroot/eivie/app/controller /www/wwwroot/eivie/app/command /www/wwwroot/eivie/app/monitor -name "*.php" -exec php -l {} \;
```

### 数据库验证
```bash
# 检查数据库表
mysql -h localhost -u guobaoyungou_cn -p guobaoyungou_cn -e "SHOW TABLES LIKE 'ddwx_aivideo_%';"
```

### 配置验证
```bash
# 检查配置文件
php -l /www/wwwroot/eivie/config/aivideo.php
```

---

## 验收标准

- [ ] 所有数据库表创建成功
- [ ] 所有PHP文件语法检查通过
- [ ] 可灵AI服务类可以正常调用接口
- [ ] 监控程序可以监控本地文件夹
- [ ] 游客端API可以正常响应
- [ ] 商家后台可以正常管理
- [ ] 定时任务可以正常执行
- [ ] 所有代码符合项目规范

---

## 风险和回退

| 风险 | 缓解措施 | 回退计划 |
|------|----------|----------|
| 可灵AI接口不稳定 | 多账号负载均衡、失败重试 | 使用备用AI服务 |
| 监控程序兼容性问题 | 充分测试、提供详细文档 | 提供手动上传功能 |
| 支付回调失败 | 定时任务主动查询、重复回调处理 | 手动确认支付 |
| 视频文件过大 | 文件大小限制、压缩处理 | 降低视频质量 |
| 数据库性能瓶颈 | 索引优化、读写分离 | 分库分表 |

---

## 实施顺序

1. **阶段1**: 基础设施搭建 (15分钟)
2. **阶段2**: 可灵AI接口封装 (20分钟)
3. **阶段3**: 商家监控程序 (20分钟)
4. **阶段4**: 游客端API (30分钟)
5. **阶段5**: 商家后台管理 (40分钟)
6. **阶段6**: 定时任务 (20分钟)

**总计**: 约145分钟 (2.5小时)

---

## 下一步

计划批准后,建议按以下顺序实施:
1. 先完成阶段1和阶段2,建立基础
2. 然后完成阶段4和阶段5,实现核心功能
3. 最后完成阶段3和阶段6,完善自动化

建议使用test-driven-development技能进行测试驱动开发。
