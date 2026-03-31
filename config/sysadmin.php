<?php
// +----------------------------------------------------------------------
// | 授权管控系统配置
// +----------------------------------------------------------------------
return [
    // 系统版本
    'version' => '1.0.0',

    // 授权码前缀映射（套餐等级 => 前缀）
    'license_prefix' => [
        'basic'   => 'BAS',
        'pro'     => 'PRO',
        'premium' => 'PRE',
    ],

    // 加密配置
    'encrypt' => [
        'method'          => 'AES-256-CBC',
        // 全局主密钥（用于初始化，每实例有独立密钥）
        'master_key'      => env('SYSADMIN_MASTER_KEY', 'GuoBao@SysAdmin2026!MasterKey#Secure'),
        // 字符位置打乱映射表种子
        'shuffle_seed'    => env('SYSADMIN_SHUFFLE_SEED', 'GbSa2026ShuffleSeed'),
    ],

    // 签名配置
    'signature' => [
        'algorithm'       => 'sha256',
        // 时间戳有效窗口（秒）
        'timestamp_window' => 300,
        // nonce缓存时间（秒）
        'nonce_ttl'       => 600,
    ],

    // 心跳配置
    'heartbeat' => [
        // 心跳周期（秒），默认6小时
        'interval'        => 21600,
        // 本地缓存有效期倍数
        'cache_multiplier' => 3,
        // 完全锁定天数
        'lockout_days'    => 7,
    ],

    // 盗版检测评分权重
    'piracy_score' => [
        'domain_mismatch'     => 30,
        'mac_mismatch'        => 35,
        'multi_instance'      => 40,
        'file_tamper'         => 25,
        'heartbeat_anomaly'   => 20,
        'client_report'       => 50,
        // 高风险阈值
        'high_threshold'      => 80,
        // 中风险阈值
        'medium_threshold'    => 40,
    ],

    // 限流配置
    'rate_limit' => [
        // 每分钟最大请求数
        'max_requests'    => 10,
        // 限流窗口（秒）
        'window'          => 60,
    ],

    // 升级配置
    'upgrade' => [
        // 升级包存储路径
        'storage_path'    => runtime_path() . 'sysadmin' . DIRECTORY_SEPARATOR . 'upgrades' . DIRECTORY_SEPARATOR,
        // 下载令牌有效期（秒）
        'token_ttl'       => 600,
    ],

    // 过期管理
    'expiry' => [
        // 提前预警天数
        'warn_days'       => [7, 3, 1],
        // 过期后自动吊销天数
        'auto_revoke_days' => 30,
        // 心跳日志保留天数
        'log_retain_days' => 90,
    ],

    // Session 配置（独立于主系统）
    'session' => [
        'prefix'          => 'sysadmin_',
    ],
];
