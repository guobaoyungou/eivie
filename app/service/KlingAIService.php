<?php
/**
 * 可灵AI服务类
 * 封装可灵AI接口调用,包括JWT鉴权、图生视频、文生视频等功能
 * @author AI旅拍开发团队
 * @date 2026-01-19
 */

namespace app\service;

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
     * 生成JWT Token (PHP原生实现)
     * @param string $accessKey AccessKey
     * @param string $secretKey SecretKey
     * @return string
     */
    public function generateToken($accessKey, $secretKey)
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $payload = [
            'iss' => $accessKey,
            'exp' => time() + $this->config['kling']['token_expire'],
            'nbf' => time() - 5
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secretKey, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Base64 URL安全编码
     * @param string $data 数据
     * @return string
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
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
