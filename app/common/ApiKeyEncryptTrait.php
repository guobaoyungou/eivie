<?php
/**
 * API Key 加密/解密/脱敏 公共 Trait
 * 
 * 用于 SystemApiKeyService 和 MerchantApiKeyService
 */
namespace app\common;

trait ApiKeyEncryptTrait
{
    /**
     * 获取加密密钥
     */
    private function getEncryptKey(): string
    {
        $config = include(ROOT_PATH . 'config.php');
        return $config['authkey'] ?? 'default_secret_key_32bytes!!';
    }

    /**
     * 获取加密 IV
     */
    private function getEncryptIv(): string
    {
        return substr(md5($this->getEncryptKey()), 0, 16);
    }

    /**
     * 加密 API Key
     * @param string $plainText 明文
     * @return string 加密后的密文（base64编码）
     */
    public function encryptApiKey(string $plainText): string
    {
        if (empty($plainText)) {
            return '';
        }
        $key = $this->getEncryptKey();
        $iv  = $this->getEncryptIv();
        $encrypted = openssl_encrypt($plainText, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($encrypted);
    }

    /**
     * 解密 API Key
     * @param string $cipherText 密文（base64编码）
     * @return string 解密后的明文
     */
    public function decryptApiKey(string $cipherText): string
    {
        if (empty($cipherText)) {
            return '';
        }
        $key     = $this->getEncryptKey();
        $iv      = $this->getEncryptIv();
        $decoded = base64_decode($cipherText);
        return (string) openssl_decrypt($decoded, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * 脱敏 API Key 展示
     * @param string $apiKey 原始 API Key
     * @return string 脱敏后的 API Key（前4后4，中间****）
     */
    public function maskApiKey(string $apiKey): string
    {
        if (empty($apiKey)) {
            return '';
        }
        $length = strlen($apiKey);
        if ($length <= 8) {
            return '****';
        }
        return substr($apiKey, 0, 4) . '****' . substr($apiKey, -4);
    }
}
