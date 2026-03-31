<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:52:48
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/module_html_sidebar.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_66602760984419_88669964',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'f03ebe12850bf884b15672c37650715344438487' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/module_html_sidebar.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_66602760984419_88669964 (Smarty_Internal_Template $_smarty_tpl) {
?><div id="sidebar" class="sidebar responsive ace-save-state">
	<?php echo '<script'; ?>
 type="text/javascript">
	
		try{ace.settings.loadState('sidebar')}catch(e){}
	
	<?php echo '</script'; ?>
>
	<div class="sidebar-shortcuts" id="sidebar-shortcuts">
		<div class="sidebar-shortcuts-large" id="sidebar-shortcuts-large">
			<button class="btn btn-success" onclick="window.location.href='/myadmin/wallmessage.php'">
				<i class="ace-icon fa fa-comments"></i>
			</button>

			<button class="btn btn-info" onclick="window.location.href='/myadmin/wallnotice.php'">
				<i class="ace-icon fa fa-pencil"></i>
			</button>

			<!-- #section:basics/sidebar.layout.shortcuts -->
			<button class="btn btn-warning" onclick="window.location.href='/myadmin/qiandao.php'">
				<i class="ace-icon fa fa-users" ></i>
			</button>

			<button class="btn btn-danger" onclick="window.location.href='/myadmin/intergrate.php'">
				<i class="ace-icon fa fa-cogs"></i>
			</button>

			<!-- /section:basics/sidebar.layout.shortcuts -->
		</div>

		<div class="sidebar-shortcuts-mini" id="sidebar-shortcuts-mini">
			<span class="btn btn-success"></span>

			<span class="btn btn-info"></span>

			<span class="btn btn-warning"></span>

			<span class="btn btn-danger"></span>
		</div>
	</div><!-- /.sidebar-shortcuts -->

	<ul class="nav nav-list">
		<li class="">
			<a href="/myadmin/index.php">
				<i class="menu-icon fa fa-tachometer"></i>
				<span class="menu-text"> 首页 </span>
			</a>
			<b class="arrow"></b>
		</li>
		<li class="">
			<a href="#" class="dropdown-toggle">
				<i class="menu-icon fa fa-users"></i>
				<span class="menu-text">签到设置</span>
				<b class="arrow fa fa-angle-down"></b>
			</a>

			<b class="arrow"></b>

			<ul class="submenu">
				<li class="">
					<a href="/myadmin/qiandaosettings.php">
						<i class="menu-icon fa fa-caret-right"></i>
						签到设置
					</a>
					<b class="arrow"></b>
				</li>
				<li class="">
					<a href="/myadmin/qiandao.php">
						<i class="menu-icon fa fa-caret-right"></i>
						签到列表
					</a>
					<b class="arrow"></b>
				</li>
				<li class="">
					<a href="/myadmin/mobileqiandao.php">
						<i class="menu-icon fa fa-caret-right"></i>
						手机签到
					</a>
					<b class="arrow"></b>
				</li>
				
			</ul>
		</li>
		<li class="">
			<a href="/myadmin/threedimensionalsign.php">
				<i class="menu-icon fa  fa-desktop"></i>
				<span class="menu-text">3D签到设置</span>
			</a>
			<b class="arrow"></b>
		</li>
		<li class="">
			<a href="#" class="dropdown-toggle">
				<i class="menu-icon fa fa-desktop"></i>
				<span class="menu-text">
					上墙消息管理
				</span>

				<b class="arrow fa fa-angle-down"></b>
			</a>

			<b class="arrow"></b>

			<ul class="submenu">
				

				<li class="">
					<a href="/myadmin/wallsettings.php">
						<i class="menu-icon fa fa-caret-right"></i>
						上墙设置
					</a>
					<b class="arrow"></b>
				</li>
				<li class="">
					<a href="/myadmin/danmusettings.php">
						<i class="menu-icon fa fa-caret-right"></i>
						弹幕设置
					</a>
					<b class="arrow"></b>
				</li>
				<li class="">
					<a href="/myadmin/wallmessage.php">
						<i class="menu-icon fa fa-caret-right"></i>
						消息列表
					</a>

					<b class="arrow"></b>
				</li>

				<li class="">
					<a href="/myadmin/wallnotice.php">
						<i class="menu-icon fa fa-caret-right"></i>
						发布公告
					</a>

					<b class="arrow"></b>
				</li>
			</ul>
		</li>

		<li class="">
			<a href="/myadmin/voteconfig.php">
				<i class="menu-icon fa  fa-signal"></i>
				<span class="menu-text">投票管理</span>
			</a>
			<b class="arrow"></b>
		</li>
                <li class="">
			<a href="/myadmin/danye.php">
				<i class="menu-icon fa  fa-signal"></i>
				<span class="menu-text">单页设置</span>
			</a>
			<b class="arrow"></b>
		</li>
		<li class="">
			<a href="#" class="dropdown-toggle">
				<i class="menu-icon fa fa-gift"></i>
				<span class="menu-text">幸运手机号</span>
				<b class="arrow fa fa-angle-down"></b>
			</a>

			<b class="arrow"></b>

			<ul class="submenu">
				
				<li class="">
					<a href="/myadmin/xingyunshoujihao.php">
						<i class="menu-icon fa fa-caret-right"></i>
						中奖记录
					</a>

					<b class="arrow"></b>
				</li>
				<li class="">
					<a href="/myadmin/xingyunshoujihaodesignatedlist.php">
						<i class="menu-icon fa fa-caret-right"></i>
						内定记录
					</a>

					<b class="arrow"></b>
				</li>
			</ul>
		</li>
		<li class="">
			<a href="#" class="dropdown-toggle">
				<i class="menu-icon fa fa-gift"></i>
				<span class="menu-text">幸运号码</span>
				<b class="arrow fa fa-angle-down"></b>
			</a>

			<b class="arrow"></b>

			<ul class="submenu">
				<li class="">
					<a href="/myadmin/xingyunhaomaconfig.php">
						<i class="menu-icon fa fa-caret-right"></i>
						幸运号码设置
					</a>

					<b class="arrow"></b>
				</li>
				<li class="">
					<a href="/myadmin/xingyunhaoma.php">
						<i class="menu-icon fa fa-caret-right"></i>
						中奖记录
					</a>

					<b class="arrow"></b>
				</li>
				<li class="">
					<a href="/myadmin/xingyunhaomadesignated.php">
						<i class="menu-icon fa fa-caret-right"></i>
						内定记录
					</a>

					<b class="arrow"></b>
				</li>
			</ul>
		</li>
		<li class="">
			<a href="/myadmin/xiangce.php">
				<i class="menu-icon fa  fa-picture-o"></i>
				<span class="menu-text">相册管理</span>
			</a>
			<b class="arrow"></b>
		</li>

	
		<li class="">
			<a href="/myadmin/kaimu.php">
				<i class="menu-icon fa  fa-desktop"></i>
				<span class="menu-text">开幕墙 </span>
			</a>
			<b class="arrow"></b>
		</li>
		<li class="">
			<a href="/myadmin/bimu.php">
				<i class="menu-icon fa  fa-desktop"></i>
				<span class="menu-text">闭幕墙 </span>
			</a>
			<b class="arrow"></b>
		</li>
		<?php if ($_smarty_tpl->tpl_vars['wall_config']->value['rentweixin'] == 1) {?>
		<li class="">
			<a href="#" class="dropdown-toggle">
				<i class="menu-icon fa fa-gift"></i>
				<span class="menu-text">红包管理</span>
				<b class="arrow fa fa-angle-down"></b>
			</a>

			<b class="arrow"></b>

			<ul class="submenu">
				<li class="">
					<a href="/myadmin/redpacketconfig.php">
						<i class="menu-icon fa fa-caret-right"></i>
						红包基本设置
					</a>

					<b class="arrow"></b>
				</li>
				<li class="">
					<a href="/myadmin/redpacket.php">
						<i class="menu-icon fa fa-caret-right"></i>
						红包设置列表
					</a>

					<b class="arrow"></b>
				</li>
			</ul>
		</li>
		<?php }?>
		<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['enabledplug_configs']->value, 'config');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['config']->value) {
?>
		<?php if ($_smarty_tpl->tpl_vars['config']->value['config']['admin']) {?>
		<?php if ($_smarty_tpl->tpl_vars['config']->value['config']['admin']['menu']) {?>
		<?php if ($_smarty_tpl->tpl_vars['config']->value['config']['admin']['menu']['submenu']) {?>
		<li class="">
			<a href="#" class="dropdown-toggle">
				<i class="menu-icon fa fa-gift"></i>
				<span class="menu-text"><?php echo $_smarty_tpl->tpl_vars['config']->value['config']['admin']['menu']['name'];?>
</span>
				<b class="arrow fa fa-angle-down"></b>
			</a>
			<b class="arrow"></b>
			<ul class="submenu">
				<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['config']->value['config']['admin']['menu']['submenu'], 'submenu');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['submenu']->value) {
?>
				<li class="">
					<a href="<?php echo $_smarty_tpl->tpl_vars['submenu']->value['link'];?>
">
						<i class="menu-icon fa fa-caret-right"></i>
						<?php echo $_smarty_tpl->tpl_vars['submenu']->value['name'];?>

					</a>

					<b class="arrow"></b>
				</li>
				<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
			</ul>
		</li>
		<?php } else { ?>
		<li class="">
			<a href="<?php echo $_smarty_tpl->tpl_vars['config']->value['config']['admin']['menu']['link'];?>
">
				<i class="menu-icon fa fa-gift"></i>
				<span class="menu-text"><?php echo $_smarty_tpl->tpl_vars['config']->value['config']['admin']['menu']['name'];?>
</span>
			</a>
			<b class="arrow"></b>
		</li>
		<?php }?>
		<?php }?>
		<?php }?>
		<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
		
		<li class="">
			<a href="#" class="dropdown-toggle">
				<i class="menu-icon fa fa-gear"></i>
				<span class="menu-text">系统设置</span>
				<b class="arrow fa fa-angle-down"></b>
			</a>
			<b class="arrow"></b>
			<ul class="submenu">
				<!-- <li class="">
					<a href="intergrate.php">
						<i class="menu-icon fa fa-caret-right"></i>
						公众号对接设置
					</a>

					<b class="arrow"></b>
				</li> -->
				<li class="">
					<a href="/myadmin/functionswitch.php">
						<i class="menu-icon fa fa-caret-right"></i>
						功能开关
					</a>
					<b class="arrow"></b>
				</li>
				<li class="">
					<a href="/myadmin/systemsettings.php">
						<i class="menu-icon fa fa-caret-right"></i>
						系统设置
					</a>
					<b class="arrow"></b>
				</li>
				
			</ul>
		</li>
		<li class="">
			<a href="/myadmin/music.php">
				<i class="menu-icon fa fa-music"></i>
				<span class="menu-text"> 配乐 </span>
			</a>
			<b class="arrow"></b>
		</li>
		<li class="">
			<a href="/myadmin/background.php">
				<i class="menu-icon fa fa-image"></i>
				<span class="menu-text">背景图</span>
			</a>
			<b class="arrow"></b>
		</li>
	</ul><!-- /.nav-list -->
</div><?php }
}
