<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:52:07
  from '/www/wwwroot/19.71jc.cn/wall/themes/meepo/header.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_6660273741e359_68416969',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '57199b31cac9d2f459559534a33b37fda55aad47' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/wall/themes/meepo/header.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6660273741e359_68416969 (Smarty_Internal_Template $_smarty_tpl) {
?><!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <title>签到 - <?php echo $_smarty_tpl->tpl_vars['title']->value;?>
</title>
    <link rel="stylesheet" type="text/css" href="themes/meepo/assets/css/basic.css">
    
	 <?php echo '<script'; ?>
 type="text/javascript">
      var WALL_INFO = {
        "id": "9",
        "scene_id": 9,
        "title": "<?php echo $_smarty_tpl->tpl_vars['title']->value;?>
",//网页顶部标题
        "show_style": "<?php echo intval($_smarty_tpl->tpl_vars['wall_config']->value['msg_showstyle']);?>
",//滚动 或者 翻转 0滚动1反转
        "show_type": "<?php echo intval($_smarty_tpl->tpl_vars['wall_config']->value['circulation']);?>
",//最新不循环或者随机循环0最新不循环1随机循环
        "re_time": "<?php echo intval($_smarty_tpl->tpl_vars['wall_config']->value['refreshtime']);?>
",
        "chistory": "<?php echo intval($_smarty_tpl->tpl_vars['wall_config']->value['msg_historynum']);?>
",//显示历史记录数
		"show_big":"<?php echo intval($_smarty_tpl->tpl_vars['wall_config']->value['msg_showbig']);?>
",//放大处理0关闭 1开启
		"show_big_time":"<?php echo intval($_smarty_tpl->tpl_vars['wall_config']->value['msg_showbigtime']);?>
"
      };
	  var PATH_URL = "themes/meepo/assets/images/piao/";
	  var show_msg_2=null;
	  var check_msg=null;
	  <?php echo '</script'; ?>
>
	  <style>
	  
		#show_hide_nav{width: 100%;position: fixed;bottom: -10px;text-align: center;z-index:9998}	
		.Panel.Top{z-index:1}
	  
	  </style>
	<?php echo '<script'; ?>
 src="themes/meepo/assets/js/tool.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="themes/meepo/assets/js/jquery-2.0.3.min.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
     <?php echo '<script'; ?>
 type="text/javascript" src="themes/meepo/assets/js/jquery.hotkeys.min.js"><?php echo '</script'; ?>
>

<?php }
}
