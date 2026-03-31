<?php
/**
 * 迁移脚本：添加信息显示开关配置
 * 在 weixin_system_config 表中插入 show_company_name, show_activity_name, show_copyright 三条记录
 * 访问此文件即可执行迁移
 */
@header("Content-type: text/html; charset=utf-8");

require_once dirname(__FILE__) . '/common/db.class.php';

$m = new M('system_config');

$configs = array(
    array('configkey' => 'show_company_name', 'configvalue' => '1', 'configname' => '显示公司名称', 'configcomment' => '1显示2隐藏'),
    array('configkey' => 'show_activity_name', 'configvalue' => '1', 'configname' => '显示活动名称', 'configcomment' => '1显示2隐藏'),
    array('configkey' => 'show_copyright', 'configvalue' => '1', 'configname' => '显示版权信息', 'configcomment' => '1显示2隐藏'),
);

$results = array();
foreach ($configs as $config) {
    $existing = $m->find('configkey="' . $config['configkey'] . '"');
    if (empty($existing)) {
        $result = $m->add($config);
        $results[] = $config['configkey'] . ': ' . ($result ? '插入成功' : '插入失败');
    } else {
        $results[] = $config['configkey'] . ': 已存在，跳过';
    }
}

// 清除缓存
require_once dirname(__FILE__) . '/common/CacheFactory.php';
$cache = new CacheFactory(CACHEMODE);
$cache->delete('system_config');

echo "<h3>信息显示开关配置迁移结果</h3>";
echo "<ul>";
foreach ($results as $r) {
    echo "<li>" . $r . "</li>";
}
echo "</ul>";
echo "<p>缓存已清除。</p>";
echo "<p><a href='frame.php'>返回大屏</a> | <a href='myadmin/systemsettings.php'>系统设置</a></p>";
