<?php
//custom_file(pay_ysepay)
require_once dirname(__FILE__) . '/CacheUtil.php';

class RSA256Util
{
    /**
     * @param $certPath string
     * @return string
     */
    public static function parseCertFileToPem($certPath)
    {
        return CacheUtil::getCacheValue($certPath, function () use ($certPath) {
            $certificateCAcerContent = file_get_contents($certPath);
            return '-----BEGIN CERTIFICATE-----' . PHP_EOL . chunk_split(base64_encode($certificateCAcerContent), 64, PHP_EOL) . '-----END CERTIFICATE-----' . PHP_EOL;
        });
    }

    /**
     * 公钥加密
     */
    public static function encryptByPub($pubCertPath, $data)
    {
        //获取公钥
        $pu_key = openssl_pkey_get_public(self::parseCertFileToPem($pubCertPath));
        if (!$pu_key) {
            throw new RuntimeException("公钥不正确");
        }
        //公钥加密
        openssl_public_encrypt($data, $encrypted, $pu_key);
        openssl_free_key($pu_key);
        return base64_encode($encrypted);
    }

    public static function verifySign($pubCertPath, $sign, $srcData)
    {
        //获取公钥
        $pu_key = openssl_pkey_get_public(self::parseCertFileToPem($pubCertPath));
        if (!$pu_key) {
            throw new RuntimeException("公钥不正确");
        }
        $success = openssl_verify($srcData, base64_decode($sign), $pu_key, OPENSSL_ALGO_SHA256);
        // Free the key from memory
        openssl_free_key($pu_key);
        if ($success === 1) {
            return true;
        }
        return false;
    }


    /**
     * @param $pfxPath string
     * @param $pfxPwd string
     * @return array
     */
    private static function readPfxCertInfo($pfxPath, $pfxPwd)
    {
        return CacheUtil::getCacheValue($pfxPath, function () use ($pfxPwd, $pfxPath) {
            $cert_store = file_get_contents($pfxPath);
            $status = openssl_pkcs12_read($cert_store, $cert_info, $pfxPwd);
            if (!$status) {
                throw new RuntimeException('Invalid pfxPwd');
            }
            return $cert_info;
        });
    }

    /**
     * pfx私钥文件签名
     */
    public static function signWithPfx($data, $pfxPath, $pfxPwd)
    {
        $cert_info = self::readPfxCertInfo($pfxPath, $pfxPwd);
        $private_key = $cert_info['pkey'];
        $pri_key = openssl_get_privatekey($private_key);
        if (!$pri_key) {
            throw new RuntimeException('Invalid private key from Pfx file ' . $pfxPath);
        }
        $status = openssl_sign($data, $signature, $pri_key, OPENSSL_ALGO_SHA256);
        // Free the key from memory
        openssl_free_key($pri_key);
        if (!$status) {
            throw new RuntimeException('Computing of the signature failed');
        }
        return base64_encode($signature);
    }
}