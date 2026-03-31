<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:01:58
  from '/www/wwwroot/19.71jc.cn/wall/themes/meepo/bimu.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_66627826b8a698_18793979',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '008e11f87bcb987246896abd33523e0165b825b5' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/wall/themes/meepo/bimu.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:themes/meepo/top_panel.html' => 1,
  ),
),false)) {
function content_66627826b8a698_18793979 (Smarty_Internal_Template $_smarty_tpl) {
?><style>

.Panel.bimu{
	margin-top:110px;
	margin-bottom:81px;
    position: absolute;
    width: 80%;
    text-align: center;
    padding-left: 10%;
}

</style>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/base2.js?20154223" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/screen_bimu.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
</head>
<body class="FUN WALL" >
<?php $_smarty_tpl->_subTemplateRender("file:themes/meepo/top_panel.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
if ($_smarty_tpl->tpl_vars['bimuconfig']->value['fullscreen'] == 2) {?>
<style>
	body{
		background: url(<?php echo $_smarty_tpl->tpl_vars['bimuconfig']->value['image'];?>
) #000 no-repeat;
	    background-size: 100% 100%;
	}

</style>
<?php } else { ?>
<div class="Panel bimu" style="display: none; opacity: 1;">
     <img src="<?php echo $_smarty_tpl->tpl_vars['bimuconfig']->value['image'];?>
" style="width:auto;height:auto;max-width: 1200px;max-height:1000px;"/>
</div>
<?php }
echo '<script'; ?>
 src="themes/meepo/assets/plugs/hotkeys-master/dist/hotkeys.min.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/bindhotkeys.js"><?php echo '</script'; ?>
><?php }
}
