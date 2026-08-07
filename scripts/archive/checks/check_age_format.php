<?php
// check_age_format.php - 检查portrait表和scene_template表的年龄字段格式
require "/home/www/ai.eivie.cn/thinkphp/start.php";

use think\facade\Db;

echo "=== Portrait表年龄字段检查 ===\n";
$portraits = Db::table('ddwx_ai_travel_photo_portrait')
    ->field('id,age_tag,age_group,gender_tag')
    ->limit(20)
    ->select();

echo "样本数据（前20条）:\n";
foreach($portraits as $p) {
    echo "ID:{$p['id']} - age_tag: " . ($p['age_tag'] ?: 'NULL') . 
         " - age_group: " . ($p['age_group'] ?: 'NULL') . 
         " - gender_tag: " . ($p['gender_tag'] ?: 'NULL') . "\n";
}

echo "\n=== Scene Template表年龄字段检查 ===\n";
$templates = Db::table('ddwx_generation_scene_template')
    ->alias('gst')
    ->join('ddwx_ai_travel_photo_scene gsp', 'gsp.id = gst.scene_id')
    ->field('gst.id,gst.age_tag,gst.face_count,gst.is_multi_template,gsp.name as scene_name')
    ->limit(20)
    ->select();

echo "样本数据（前20条）:\n";
foreach($templates as $t) {
    echo "ID:{$t['id']} - age_tag: " . ($t['age_tag'] ?: 'NULL') . 
         " - scene: {$t['scene_name']}\n";
}

echo "\n=== InsightFace返回的原始年龄数据 ===\n";
// 检查最近的分析记录
$recent = Db::table('ddwx_ai_travel_photo_portrait')
    ->field('id,image_url,analysis_result,create_time')
    ->order('id DESC')
    ->limit(5)
    ->select();

foreach($recent as $r) {
    $result = json_decode($r['analysis_result'], true);
    echo "ID:{$r['id']} - ";
    if ($result && isset($result['age_group'])) {
        echo "age_group: {$result['age_group']}";
    } else {
        echo "no age_group in result";
    }
    echo "\n";
}
