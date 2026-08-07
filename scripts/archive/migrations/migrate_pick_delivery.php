<?php
/**
 * 笑脸抓拍成片交付功能 - 数据库迁移脚本
 * 
 * 补充 package / order / order_goods 表字段
 * 执行方式：php migrate_pick_delivery.php
 */

define('ROOT_PATH', __DIR__ . '/');

require __DIR__ . '/vendor/autoload.php';

// 初始化框架
$app = new \think\App();
$app->initialize();

use think\facade\Db;

// 检查字段是否存在
if (!function_exists('pdo_fieldexists2')) {
    function pdo_fieldexists2($tablename, $fieldname) {
        $fields = Db::query("SHOW COLUMNS FROM " . $tablename);
        if (empty($fields)) {
            return false;
        }
        foreach ($fields as $field) {
            if ($fieldname == $field['Field']) {
                return true;
            }
        }
        return false;
    }
}

echo "===== 笑脸抓拍成片交付功能 - 数据库迁移 =====\n\n";

// ---- 1. 套餐表 (ddwx_ai_travel_photo_package) 补充字段 ----
echo "[1/3] 补充套餐表字段...\n";

if (!pdo_fieldexists2('ddwx_ai_travel_photo_package', 'unit_price')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_package` ADD COLUMN `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '折合单价（冗余）' AFTER `price`;");
    echo "  + unit_price 字段已添加\n";
} else {
    echo "  - unit_price 字段已存在，跳过\n";
}

if (!pdo_fieldexists2('ddwx_ai_travel_photo_package', 'is_default')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_package` ADD COLUMN `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否为默认推荐套餐' AFTER `is_recommend`;");
    echo "  + is_default 字段已添加\n";
} else {
    echo "  - is_default 字段已存在，跳过\n";
}

if (!pdo_fieldexists2('ddwx_ai_travel_photo_package', 'label')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_package` ADD COLUMN `label` varchar(50) DEFAULT NULL COMMENT '套餐标签文案，如最划算、热门、推荐' AFTER `tag_color`;");
    echo "  + label 字段已添加\n";
} else {
    echo "  - label 字段已存在，跳过\n";
}

// ---- 2. 订单表 (ddwx_ai_travel_photo_order) 补充字段 ----
echo "\n[2/3] 补充订单表字段...\n";

if (!pdo_fieldexists2('ddwx_ai_travel_photo_order', 'selected_count')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `selected_count` int NOT NULL DEFAULT 0 COMMENT '用户实际选择的成片数量' AFTER `package_id`;");
    echo "  + selected_count 字段已添加\n";
} else {
    echo "  - selected_count 字段已存在，跳过\n";
}

if (!pdo_fieldexists2('ddwx_ai_travel_photo_order', 'package_snapshot')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `package_snapshot` text DEFAULT NULL COMMENT '下单时的套餐快照（JSON）' AFTER `selected_count`;");
    echo "  + package_snapshot 字段已添加\n";
} else {
    echo "  - package_snapshot 字段已存在，跳过\n";
}

if (!pdo_fieldexists2('ddwx_ai_travel_photo_order', 'download_count')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `download_count` int NOT NULL DEFAULT 0 COMMENT '已下载次数' AFTER `package_snapshot`;");
    echo "  + download_count 字段已添加\n";
} else {
    echo "  - download_count 字段已存在，跳过\n";
}

if (!pdo_fieldexists2('ddwx_ai_travel_photo_order', 'download_limit')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `download_limit` int NOT NULL DEFAULT 0 COMMENT '允许下载次数上限' AFTER `download_count`;");
    echo "  + download_limit 字段已添加\n";
} else {
    echo "  - download_limit 字段已存在，跳过\n";
}

if (!pdo_fieldexists2('ddwx_ai_travel_photo_order', 'openid')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order` ADD COLUMN `openid` varchar(64) DEFAULT NULL COMMENT '微信OpenID（标识扫码用户）' AFTER `download_limit`;");
    echo "  + openid 字段已添加\n";
} else {
    echo "  - openid 字段已存在，跳过\n";
}

// ---- 3. 订单商品表 (ddwx_ai_travel_photo_order_goods) 补充字段 ----
echo "\n[3/3] 补充订单商品表字段...\n";

if (!pdo_fieldexists2('ddwx_ai_travel_photo_order_goods', 'is_downloaded')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order_goods` ADD COLUMN `is_downloaded` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已下载：0否 1是' AFTER `status`;");
    echo "  + is_downloaded 字段已添加\n";
} else {
    echo "  - is_downloaded 字段已存在，跳过\n";
}

if (!pdo_fieldexists2('ddwx_ai_travel_photo_order_goods', 'download_url')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order_goods` ADD COLUMN `download_url` varchar(500) DEFAULT NULL COMMENT '付费后的无水印下载URL' AFTER `is_downloaded`;");
    echo "  + download_url 字段已添加\n";
} else {
    echo "  - download_url 字段已存在，跳过\n";
}

if (!pdo_fieldexists2('ddwx_ai_travel_photo_order_goods', 'download_time')) {
    Db::execute("ALTER TABLE `ddwx_ai_travel_photo_order_goods` ADD COLUMN `download_time` int NOT NULL DEFAULT 0 COMMENT '首次下载时间戳' AFTER `download_url`;");
    echo "  + download_time 字段已添加\n";
} else {
    echo "  - download_time 字段已存在，跳过\n";
}

echo "\n===== 迁移完成 =====\n";
