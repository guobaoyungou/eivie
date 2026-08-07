<?php
/**
 * 创作会员套餐初始化数据脚本
 * 5个版本 × 3种购买模式 = 15条记录
 */
$config = include __DIR__ . '/config.php';

$prefix = $config['prefix'] ?? 'ddwx_';
$charset = 'utf8mb4';
$dsn = "mysql:host={$config['hostname']};dbname={$config['database']};charset={$charset}";
$pdo = new PDO($dsn, $config['username'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$table = $prefix . 'creative_member_plan';
$now = time();

// 查找aid（取第一个admin_set记录）
$aidRow = $pdo->query("SELECT aid FROM {$prefix}admin_set LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$aid = $aidRow ? (int)$aidRow['aid'] : 1;

// 先清空现有数据
$pdo->exec("DELETE FROM {$table}");
echo "Cleared existing plan data.\n";

// 版本定义
$versions = [
    [
        'code' => 'basic', 'name' => '基础版',
        'monthly_score' => 800, 'max_concurrency' => 3, 'cloud_storage_gb' => 20,
        'features' => json_encode(['登录每日赠送20积分','会员专属可商用模型','会员专享无限次加速','去除品牌水印','训练专属权益'], JSON_UNESCAPED_UNICODE),
        'yearly' => ['price' => 399, 'original' => 588, 'discount' => '68折',
            'model_rights' => json_encode([], JSON_UNESCAPED_UNICODE)],
        'monthly_auto' => ['price' => 39, 'original' => 49, 'discount' => '8折',
            'model_rights' => json_encode([], JSON_UNESCAPED_UNICODE)],
        'monthly' => ['price' => 49, 'original' => 49, 'discount' => '',
            'model_rights' => json_encode([], JSON_UNESCAPED_UNICODE)],
    ],
    [
        'code' => 'pro', 'name' => '专业版',
        'monthly_score' => 1800, 'max_concurrency' => 6, 'cloud_storage_gb' => 50,
        'features' => json_encode(['登录每日赠送20积分','会员专属可商用模型','会员专享无限次加速','去除品牌水印','训练专属权益'], JSON_UNESCAPED_UNICODE),
        'yearly' => ['price' => 639, 'original' => 948, 'discount' => '68折',
            'model_rights' => json_encode([], JSON_UNESCAPED_UNICODE)],
        'monthly_auto' => ['price' => 66, 'original' => 79, 'discount' => '84折',
            'model_rights' => json_encode([], JSON_UNESCAPED_UNICODE)],
        'monthly' => ['price' => 79, 'original' => 79, 'discount' => '',
            'model_rights' => json_encode([], JSON_UNESCAPED_UNICODE)],
    ],
    [
        'code' => 'master', 'name' => '大师版',
        'monthly_score' => 5800, 'max_concurrency' => 10, 'cloud_storage_gb' => 130,
        'features' => json_encode(['登录每日赠送20积分','会员专属可商用模型','会员专享无限次加速','去除品牌水印','训练专属权益'], JSON_UNESCAPED_UNICODE),
        'yearly' => ['price' => 1599, 'original' => 2748, 'discount' => '59折',
            'model_rights' => json_encode([
                ['model_code'=>'basic_f2','free_days'=>365,'free_type'=>'free_score'],
                ['model_code'=>'seedream_5','free_days'=>15,'free_type'=>'free_score'],
                ['model_code'=>'seedream_4_5','free_days'=>15,'free_type'=>'free_score'],
                ['model_code'=>'z_image_base','free_days'=>365,'free_type'=>'free_score'],
                ['model_code'=>'z_image','free_days'=>365,'free_type'=>'free_score']
            ], JSON_UNESCAPED_UNICODE)],
        'monthly_auto' => ['price' => 179, 'original' => 229, 'discount' => '79折',
            'model_rights' => json_encode([
                ['model_code'=>'basic_f2','free_days'=>31,'free_type'=>'free_score'],
                ['model_code'=>'z_image_base','free_days'=>31,'free_type'=>'free_score'],
                ['model_code'=>'z_image','free_days'=>31,'free_type'=>'free_score']
            ], JSON_UNESCAPED_UNICODE)],
        'monthly' => ['price' => 229, 'original' => 229, 'discount' => '',
            'model_rights' => json_encode([], JSON_UNESCAPED_UNICODE)],
    ],
    [
        'code' => 'flagship', 'name' => '旗舰版',
        'monthly_score' => 15800, 'max_concurrency' => 20, 'cloud_storage_gb' => 200,
        'features' => json_encode(['登录每日赠送20积分','会员专属可商用模型','会员专享无限次加速','去除品牌水印','训练专属权益'], JSON_UNESCAPED_UNICODE),
        'yearly' => ['price' => 3999, 'original' => 7548, 'discount' => '53折',
            'model_rights' => json_encode([
                ['model_code'=>'basic_f2','free_days'=>365,'free_type'=>'free_score'],
                ['model_code'=>'seedream_5','free_days'=>31,'free_type'=>'free_score'],
                ['model_code'=>'seedream_4_5','free_days'=>31,'free_type'=>'free_score'],
                ['model_code'=>'z_image_base','free_days'=>365,'free_type'=>'free_score'],
                ['model_code'=>'z_image','free_days'=>365,'free_type'=>'free_score']
            ], JSON_UNESCAPED_UNICODE)],
        'monthly_auto' => ['price' => 469, 'original' => 629, 'discount' => '75折',
            'model_rights' => json_encode([
                ['model_code'=>'basic_f2','free_days'=>31,'free_type'=>'free_score'],
                ['model_code'=>'seedream_5','free_days'=>7,'free_type'=>'free_score'],
                ['model_code'=>'seedream_4_5','free_days'=>7,'free_type'=>'free_score'],
                ['model_code'=>'z_image_base','free_days'=>31,'free_type'=>'free_score'],
                ['model_code'=>'z_image','free_days'=>31,'free_type'=>'free_score']
            ], JSON_UNESCAPED_UNICODE)],
        'monthly' => ['price' => 629, 'original' => 629, 'discount' => '',
            'model_rights' => json_encode([], JSON_UNESCAPED_UNICODE)],
    ],
    [
        'code' => 'premium', 'name' => '尊享版',
        'monthly_score' => 34000, 'max_concurrency' => 9999, 'cloud_storage_gb' => 500,
        'features' => json_encode(['登录每日赠送20积分','会员专属可商用模型','会员专享无限次加速','去除品牌水印','训练专属权益'], JSON_UNESCAPED_UNICODE),
        'yearly' => ['price' => 7399, 'original' => 16788, 'discount' => '44折',
            'model_rights' => json_encode([
                ['model_code'=>'basic_f2','free_days'=>365,'free_type'=>'free_score'],
                ['model_code'=>'seedream_5','free_days'=>93,'free_type'=>'free_score'],
                ['model_code'=>'seedream_4_5','free_days'=>93,'free_type'=>'free_score'],
                ['model_code'=>'z_image_base','free_days'=>365,'free_type'=>'free_score'],
                ['model_code'=>'z_image','free_days'=>365,'free_type'=>'free_score']
            ], JSON_UNESCAPED_UNICODE)],
        'monthly_auto' => ['price' => 999, 'original' => 1399, 'discount' => '72折',
            'model_rights' => json_encode([
                ['model_code'=>'basic_f2','free_days'=>31,'free_type'=>'free_score'],
                ['model_code'=>'seedream_5','free_days'=>15,'free_type'=>'free_score'],
                ['model_code'=>'seedream_4_5','free_days'=>15,'free_type'=>'free_score'],
                ['model_code'=>'z_image_base','free_days'=>31,'free_type'=>'free_score'],
                ['model_code'=>'z_image','free_days'=>31,'free_type'=>'free_score']
            ], JSON_UNESCAPED_UNICODE)],
        'monthly' => ['price' => 1399, 'original' => 1399, 'discount' => '',
            'model_rights' => json_encode([], JSON_UNESCAPED_UNICODE)],
    ],
];

$modes = ['yearly', 'monthly_auto', 'monthly'];
$sort = 0;

$sql = "INSERT INTO {$table} (aid, version_code, version_name, purchase_mode, price, original_price, discount_text, monthly_score, daily_login_score, max_concurrency, cloud_storage_gb, model_rights, features, sort, status, createtime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
$stmt = $pdo->prepare($sql);

$inserted = 0;
foreach ($versions as $v) {
    foreach ($modes as $mode) {
        $modeData = $v[$mode];
        $sort++;
        $stmt->execute([
            $aid,
            $v['code'],
            $v['name'],
            $mode,
            $modeData['price'],
            $modeData['original'],
            $modeData['discount'],
            $v['monthly_score'],
            20, // daily_login_score
            $v['max_concurrency'],
            $v['cloud_storage_gb'],
            $modeData['model_rights'],
            $v['features'],
            $sort,
            $now
        ]);
        $inserted++;
        echo "Inserted: {$v['name']} - {$mode} (price: {$modeData['price']})\n";
    }
}

echo "\nDone! Inserted {$inserted} plan records.\n";
