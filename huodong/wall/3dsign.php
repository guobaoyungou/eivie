<?php
@header("Content-type: text/html; charset=utf-8");
require_once(dirname(__FILE__) . '/../smarty/Smarty.class.php');
require_once(dirname(__FILE__) . '/../common/db.class.php');
require_once(dirname(__FILE__) . '/../common/session_helper.php');
require_once(dirname(__FILE__) . '/../common/function.php');
$style='meepo';
$load->model('Wall_model');
$wall_config=$load->wall_model->getConfig();
$load->model('Weixin_model');
$weixin_config=$load->weixin_model->getConfig();
$qd_maxid=0;
$load->model("Flag_model");
$flag=$load->flag_model->getRecentSignedUsers(30);

// 从 screen_config 中获取嘉宾显示方式配置
$sign_show_style = isset($scfg['sign_show_style']) ? $scfg['sign_show_style'] : 1;
$use_wx_avatar = isset($scfg['use_wx_avatar']) ? intval($scfg['use_wx_avatar']) : 1;

// 如果新配置源不可用，回退到旧 weixin_system_config
if (empty($scfg) || !isset($scfg['sign_show_style'])) {
    try {
        $load->model('System_Config_model');
        $oldShowtype = $load->system_config_model->get('signnameshowstyle');
        if (isset($oldShowtype['configvalue'])) {
            $sign_show_style = $oldShowtype['configvalue'];
        }
    } catch (Exception $e) {
        // 忽略错误，使用默认值 1
    }
}

$flag=array_reverse($flag);
include("../wall/biaoqing.php");
foreach($flag as $k=>$v){
	$v['nickname'] = processNickname($v, $sign_show_style);
	$v['avatar'] = processAvatar($v, $use_wx_avatar);
	$v['content']=pack('H*', $v['content']);
	$v= emoji_unified_to_html(emoji_softbank_to_unified($v));
	$v['content']=biaoqing($v['content']);
	$flag[$k]=$v;
}

$qd_nums=count($flag);
$qd_maxid=$qd_nums>0?$flag[$qd_nums-1]['signorder']:0;

// ---- 从新系统数据库读取3D配置和效果列表 ----
$threedimensional = array('avatarnum'=>30, 'avatarsize'=>7, 'avatargap'=>15, 'datastr'=>'#sphere|#torus|#grid|#helix|#cylinder|#gene', 'play_mode'=>'sequential', 'idle_enabled'=>true, 'idle_delay'=>5000, 'card_style'=>'normal', 'highlight_scale'=>3, 'highlight_duration'=>2000);
$scfg = array();
try {
    $newDbConfig = @include(dirname(__FILE__) . '/../../config.php');
    if ($newDbConfig && !empty($newDbConfig['hostname'])) {
        $newConn = @new mysqli($newDbConfig['hostname'], $newDbConfig['username'], $newDbConfig['password'], $newDbConfig['database'], (int)$newDbConfig['hostport']);
        if (!$newConn->connect_error) {
            $newConn->set_charset('utf8mb4');
            $prefix = isset($newDbConfig['prefix']) ? $newDbConfig['prefix'] : 'ddwx_';
            $actId = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
            // 如果没有指定 activity_id，取第一个活动
            if ($actId <= 0) {
                $actRes = $newConn->query("SELECT id, screen_config FROM {$prefix}hd_activity ORDER BY id DESC LIMIT 1");
                if ($actRes && $actRow = $actRes->fetch_assoc()) {
                    $actId = intval($actRow['id']);
                    $scfg = $actRow['screen_config'] ? json_decode($actRow['screen_config'], true) : array();
                } else {
                    $scfg = array();
                }
            } else {
                $actRes = $newConn->query("SELECT screen_config FROM {$prefix}hd_activity WHERE id = {$actId}");
                $scfg = ($actRes && $actRow = $actRes->fetch_assoc()) ? (json_decode($actRow['screen_config'], true) ?: array()) : array();
            }
            if ($actId > 0) {
                $threedimensional['avatarnum']  = isset($scfg['threed_avatarnum'])  ? intval($scfg['threed_avatarnum'])  : 30;
                $threedimensional['avatarsize'] = isset($scfg['threed_avatarsize']) ? intval($scfg['threed_avatarsize']) : 7;
                $threedimensional['avatargap']  = isset($scfg['threed_avatargap'])  ? intval($scfg['threed_avatargap'])  : 15;
                $threedimensional['play_mode']  = isset($scfg['threed_play_mode'])  ? $scfg['threed_play_mode'] : 'sequential';
                $threedimensional['idle_enabled'] = isset($scfg['threed_idle_enabled']) ? (bool)$scfg['threed_idle_enabled'] : true;
                $threedimensional['idle_delay']   = isset($scfg['threed_idle_delay'])   ? intval($scfg['threed_idle_delay']) : 5000;
                $threedimensional['card_style']     = isset($scfg['threed_card_style'])     ? $scfg['threed_card_style'] : 'normal';
                $threedimensional['highlight_scale']    = isset($scfg['threed_highlight_scale'])    ? floatval($scfg['threed_highlight_scale']) : 3;
                $threedimensional['highlight_duration'] = isset($scfg['threed_highlight_duration']) ? intval($scfg['threed_highlight_duration']) : 2000;
                // 优先使用 screen_config 中已同步的 datastr
                if (!empty($scfg['threed_datastr'])) {
                    $threedimensional['datastr'] = $scfg['threed_datastr'];
                } else {
                    // 从效果表动态生成
                    $effRes = $newConn->query("SELECT type, content FROM {$prefix}hd_3d_effects WHERE activity_id = {$actId} ORDER BY sort ASC");
                    $parts = array();
                    if ($effRes) {
                        while ($eRow = $effRes->fetch_assoc()) {
                            switch ($eRow['type']) {
                                case 'preset_shape': $parts[] = '#' . $eRow['content']; break;
                                case 'image_logo':   $parts[] = '#icon ' . $eRow['content']; break;
                                case 'text_logo':    $parts[] = $eRow['content']; break;
                                case 'countdown':    $parts[] = '#countdown ' . $eRow['content']; break;
                            }
                        }
                    }
                    if (!empty($parts)) {
                        $threedimensional['datastr'] = implode('|', $parts);
                    }
                }
            }
            $newConn->close();
        }
    }
} catch (Exception $e) {
    // 新数据库连接失败时使用默认值
}

$smarty = new Smarty;
$smarty->caching = false;
$apppath=str_replace(DIRECTORY_SEPARATOR.'wall', '', dirname(__FILE__));
$smarty->compile_dir = $apppath.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'templates_c'.DIRECTORY_SEPARATOR;
$smarty->assign('from','qiandao');
$smarty->assign('wall_config',$wall_config);
$smarty->assign('qd_maxid',$qd_maxid);
$smarty->assign('personJson',json_encode($flag));
$smarty->assign('erweima',$weixin_config['erweima']);
$smarty->assign('threedimensional_config',$threedimensional);
$smarty->assign('threedimensional_play_mode',$threedimensional['play_mode']);
$smarty->assign('threed_idle_enabled', !empty($threedimensional['idle_enabled']) ? 'true' : 'false');
$smarty->assign('threed_idle_delay', isset($threedimensional['idle_delay']) ? $threedimensional['idle_delay'] : 5000);
$smarty->assign('threed_card_style', isset($threedimensional['card_style']) ? $threedimensional['card_style'] : 'normal');
$smarty->assign('threed_highlight_scale', isset($threedimensional['highlight_scale']) ? $threedimensional['highlight_scale'] : 3);
$smarty->assign('threed_highlight_duration', isset($threedimensional['highlight_duration']) ? $threedimensional['highlight_duration'] : 2000);
$smarty->assign('title',"");
$smarty->display('themes/'.$style.'/header.html');
$smarty->display('themes/'.$style.'/3dsign.html');
$smarty->display('themes/'.$style.'/footer.html');