<?php
/**
 * 背景图管理重构 - 数据库迁移脚本
 * 创建 weixin_background 和 weixin_attachments 表
 * 
 * 运行: php migrate_bg_refactor.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('ROOT_PATH', __DIR__ . '/');

require __DIR__ . '/vendor/autoload.php';
$http = (new \think\App())->http;
$http->run();

use think\facade\Db;

echo "=== 背景图管理重构 - 数据库迁移 ===\n\n";

// 1. 创建 weixin_attachments 表
echo "1. 创建 weixin_attachments 表...\n";
try {
    $exists = Db::query("SHOW TABLES LIKE 'weixin_attachments'");
    if (empty($exists)) {
        Db::execute("
            CREATE TABLE `weixin_attachments` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `filepath` varchar(255) DEFAULT NULL COMMENT '文件路径',
                `extension` varchar(10) DEFAULT NULL COMMENT '扩展名',
                `type` tinyint(1) DEFAULT NULL COMMENT '1本地文件2阿里云3新浪云',
                `filemd5` varchar(32) DEFAULT NULL COMMENT '文件名和文件大小组合的md5值',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='附件表'
        ");
        echo "   ✅ 表创建成功\n";
    } else {
        echo "   ⏩ 表已存在，跳过\n";
    }
} catch (\Exception $e) {
    echo "   ❌ 失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. 创建 weixin_background 表
echo "2. 创建 weixin_background 表...\n";
try {
    $exists = Db::query("SHOW TABLES LIKE 'weixin_background'");
    if (empty($exists)) {
        Db::execute("
            CREATE TABLE `weixin_background` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `attachmentid` int(11) DEFAULT NULL COMMENT '背景图id',
                `name` varchar(32) DEFAULT NULL COMMENT '名称',
                `plugname` varchar(32) DEFAULT NULL COMMENT '关联的组件名',
                `bgtype` tinyint(1) DEFAULT 1 COMMENT '1图片2视频',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='背景图表'
        ");
        echo "   ✅ 表创建成功\n";
    } else {
        echo "   ⏩ 表已存在，跳过\n";
    }
} catch (\Exception $e) {
    echo "   ❌ 失败: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. 初始化预置功能模块数据
echo "3. 初始化预置功能模块数据...\n";
$modules = [
    ['name' => '签到墙背景',     'plugname' => 'qdq',                   'bgtype' => 1],
    ['name' => '微信上墙背景',   'plugname' => 'wall',                  'bgtype' => 1],
    ['name' => '对对碰背景',     'plugname' => 'ddp',                   'bgtype' => 1],
    ['name' => '投票背景',       'plugname' => 'vote',                  'bgtype' => 1],
    ['name' => '幸运手机号背景', 'plugname' => 'xysjh',                 'bgtype' => 1],
    ['name' => '幸运号码背景',   'plugname' => 'xyh',                   'bgtype' => 1],
    ['name' => '相册背景',       'plugname' => 'xiangce',               'bgtype' => 1],
    ['name' => '开幕墙背景',     'plugname' => 'kaimu',                 'bgtype' => 1],
    ['name' => '闭幕墙背景',     'plugname' => 'bimu',                  'bgtype' => 1],
    ['name' => '红包雨背景',     'plugname' => 'redpacket',             'bgtype' => 1],
    ['name' => '摇大奖背景',     'plugname' => 'ydj',                   'bgtype' => 1],
    ['name' => '导入抽奖背景图', 'plugname' => 'importlottery',         'bgtype' => 1],
    ['name' => '3D签到背景图',   'plugname' => 'threedimensionalsign',  'bgtype' => 1],
];

$insertCount = 0;
foreach ($modules as $mod) {
    $exists = Db::table('weixin_background')->where('plugname', $mod['plugname'])->find();
    if (!$exists) {
        Db::table('weixin_background')->insert([
            'attachmentid' => null,
            'name'         => $mod['name'],
            'plugname'     => $mod['plugname'],
            'bgtype'       => $mod['bgtype'],
        ]);
        $insertCount++;
    }
}
echo "   ✅ 初始化完成，新增 {$insertCount} 条记录（共 " . count($modules) . " 个模块）\n";

// 4. 验证
echo "\n4. 验证...\n";
$count = Db::table('weixin_background')->count();
echo "   weixin_background 表共 {$count} 条记录\n";
$list = Db::table('weixin_background')->select()->toArray();
foreach ($list as $item) {
    echo "   [{$item['id']}] {$item['plugname']} => {$item['name']} (bgtype={$item['bgtype']})\n";
}

echo "\n=== 迁移完成 ===\n";
