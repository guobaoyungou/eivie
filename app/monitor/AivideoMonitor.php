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
