<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:01:59
  from '/www/wwwroot/19.71jc.cn/Modules/Ydj/templates/front/index.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666278279ffd78_75345526',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '71d31785aef429f9c6fc2b419d83a96e8644615e' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/Modules/Ydj/templates/front/index.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_666278279ffd78_75345526 (Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['html_header']->value;?>

<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['assets']->value;?>
/js/layer.js?t=1" type="text/javascript"><?php echo '</script'; ?>
>
<link href="<?php echo $_smarty_tpl->tpl_vars['assets']->value;?>
/css/footer.css?T=1" type="text/css" rel="stylesheet"  >
<link href="<?php echo $_smarty_tpl->tpl_vars['assets']->value;?>
/css/app_common.css" type="text/css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="<?php echo $_smarty_tpl->tpl_vars['assets']->value;?>
/fonts/font_864467_uc9k8ie5j6/iconfont.css">
<?php echo '<script'; ?>
 type="text/javascript">
	var over = "0";
	var WEB_URL = "";
	var game_status = 1;
<?php echo '</script'; ?>
>
<link href="<?php echo $_smarty_tpl->tpl_vars['module_assets']->value;?>
/css/front_ydj.css" type="text/css" rel="stylesheet">
<link href="<?php echo $_smarty_tpl->tpl_vars['module_assets']->value;?>
/css/screen_ydj.css?T=1" type="text/css" rel="stylesheet">
<link href="<?php echo $_smarty_tpl->tpl_vars['assets']->value;?>
/css/animate.min.css?v=1.1.0" type="text/css" rel="stylesheet" >
<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['module_assets']->value;?>
/js/ydj_base.js?T=1" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['module_assets']->value;?>
/js/screen_ydj.js?T=1" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['assets']->value;?>
/js/jquery.nicescroll.min.js" type="text/javascript" ><?php echo '</script'; ?>
>
</head>
<body class="FUN WALL">
<div class="grab_redenvelop screen_show" style="opacity:0">
  <div class="main">
    <div class="begin">
      <div class="tips">
        <div class="circle">
          <img class="shake-hand" src="<?php echo $_smarty_tpl->tpl_vars['module_assets']->value;?>
/images/shake_begin_shake.png">
        </div>
        <span class="begin_txt">准备开始摇大奖</span>
      </div>
      <div class="button">
        <div class="round">轮次编号：1</div>
        <span id="start" class="start green"><i class="iconfont icon-kaishi" style="font-size: 1em;"></i>开始游戏</span>
        <p><span class="btn_prev"><i>&lt;</i>上一轮</span><span class="btn_next">下一轮<i>&gt;</i></span><br/><span class="btn_result" style="display:none;">游戏结果</span></p>
      </div>
      <div style="right: 0px;bottom: 100px;position: absolute; " id="reset_btn">
        <i class="icon iconfont icon-tingzhi-yuanshijituantubiao" style="font-size:6em;cursor: pointer;"></i>
      </div>
    </div>
    
    <!-- 背景动画 -->
    <div class="bg_rotate animate_rotate"></div>
    <!-- 钱袋 -->
    <div class="top_package">
      <!--<div class="top_package_in"></div>-->
      <!---<div class="top_package_out"></div>-->
      <p class="countNum">
      <b class="count-title">倒计时</b>
      <b class="sec">60</b>
      <b>:</b>
      <b class="hm">00</b></p>
    </div>
    <!--掉落的红包图片-->
    <div class="redenvelop_box" style="display:none;">
      <ul class="redenvelop_list" style="margin:0px;padding:0px"></ul>
    </div>
    <!--掉落的红包图片-->
    <!--<div class="title"></div>
    <a href="javascript:;" class="start" id="start"></a>-->
    <!-- 页面底部元素 -->
    <!--<div class="bottom_sphere" style="bottom:0px"></div>
    <div class="bottom_money" style="bottom:0px"></div>
    <div class="bottom_phone" style="bottom:0px"></div>-->
    <!-- 中奖名单列表 -->
    <div class="right_winninglist">
      <dl>
        <dt><h4>★中奖名单★</h4></dt>
        <dd class="list pop_winninglist_a">
          <ul></ul>
        </dd>
        <dd style="height:10px; background-color:#fff;"></dd>
        <dd class="bottom_arrowBox">
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
          <span class="arrow_left"></span>
          <span class="arrow_right"></span>
        </dd>
      </dl>
    </div>
    <!-- 参与用户列表 -->
    <div class="left_userlist">
      <div class="list_img">
        <ul class="clearfix">
        </ul>
      </div>
      <div class="user_total">
        <b>0</b>
        <p>参与人数</p>
      </div>
    </div>
  </div>
  <!-- 弹窗 -->
  <div class="pop_mask"></div>
  <!-- 单个中奖弹窗 -->
  <div class="pop_winning clearfix">
    <img src="" class="user_photo">
    <div class="user_info">
      <p>实物奖品</p>
      <span>恭喜ad司法获得</span>
    </div>
    <img src="" class="prize_img">
  </div>
  <!-- 中奖名单列表弹窗 -->
  <div class="pop_winninglist">
    <dl>
      <dt>
        <h4>★中奖名单★</h4>
        <a href="javascript:;" class="btn_close" style="display:block">
          <p>关闭</p>
        </a>
      </dt>
      <dd style="height:10px; background-color:#fff;"></dd>
      <dd class="list">
        <ul class="clearfix"></ul>
      </dd>
      <dd style="height:10px; background-color:#fff;"></dd>
      <dd class="bottom_arrowBox">
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
        <span class="arrow_left"></span>
        <span class="arrow_right"></span>
      </dd>
    </dl>
  </div>
</div>
<?php echo '<script'; ?>
 type="text/javascript">
var ROUNDINFO=<?php echo $_smarty_tpl->tpl_vars['configjson']->value;?>
;
var PRIZENUM=<?php echo $_smarty_tpl->tpl_vars['PrizeNum']->value;?>
;
var PRIZES=<?php echo $_smarty_tpl->tpl_vars['prizesjson']->value;?>
;
var WINNERS=<?php echo $_smarty_tpl->tpl_vars['winners']->value;?>
;
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="/wall/themes/meepo/assets/plugs/hotkeys-master/dist/hotkeys.min.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="/wall/themes/meepo/assets/js/bindhotkeys.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['module_assets']->value;?>
/js/front_ydj.js" type="text/javascript"><?php echo '</script'; ?>
>
<?php echo $_smarty_tpl->tpl_vars['html_footer']->value;
}
}
