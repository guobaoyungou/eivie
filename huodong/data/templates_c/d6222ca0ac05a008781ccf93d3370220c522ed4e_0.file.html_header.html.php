<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:51:57
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/html_header.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_6660272dca5e39_54471713',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'd6222ca0ac05a008781ccf93d3370220c522ed4e' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/html_header.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6660272dca5e39_54471713 (Smarty_Internal_Template $_smarty_tpl) {
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php echo $_smarty_tpl->tpl_vars['title']->value;?>
</title>

		<meta name="description" content="" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/ace_v1.4/assets/css/bootstrap.css" />
		<link rel="stylesheet" href="assets/ace_v1.4/components/font-awesome/css/font-awesome.css" />

		<!-- page specific plugin styles -->
		<link rel="stylesheet" href="assets/ace_v1.4/components/_mod/jqgrid/ui.jqgrid.css" />
		<link rel="stylesheet" href="assets/ace_v1.4/components/mjolnic-bootstrap-colorpicker/dist/css/bootstrap-colorpicker.css" />
		
		<!-- text fonts -->
		<link rel="stylesheet" href="assets/ace_v1.4/assets/css/ace-fonts.css" />

		<!-- ace styles -->
		<link rel="stylesheet" href="assets/ace_v1.4/assets/css/ace.css" class="ace-main-stylesheet" id="main-ace-style" />

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/ace_v1.4/assets/css/ace-part2.css" class="ace-main-stylesheet" />
		<![endif]-->
		<link rel="stylesheet" href="assets/ace_v1.4/assets/css/ace-skins.css" />
		<link rel="stylesheet" href="assets/ace_v1.4/assets/css/ace-rtl.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="assets/ace_v1.4/assets/css/ace-ie.css" />
		<![endif]-->
		<!-- inline styles related to this page -->
		<!-- ace settings handler -->
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/ace-extra.js"><?php echo '</script'; ?>
>
		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->
		<!--[if lte IE 8]>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/components/html5shiv/dist/html5shiv.min.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/components/respond/dest/respond.min.js"><?php echo '</script'; ?>
>
		<![endif]-->
	</head>
	<body class="no-skin">
		<!-- #section:basics/navbar.layout -->
		<div id="navbar" class="navbar navbar-default          ace-save-state">
			<div class="navbar-container ace-save-state" id="navbar-container">
				<!-- #section:basics/sidebar.mobile.toggle -->
				<button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
					<span class="sr-only">Toggle sidebar</span>

					<span class="icon-bar"></span>

					<span class="icon-bar"></span>

					<span class="icon-bar"></span>
				</button>

				<!-- /section:basics/sidebar.mobile.toggle -->
				<div class="navbar-header pull-left">
					<!-- #section:basics/navbar.layout.brand -->
					<a href="index.php" class="navbar-brand">
						<small>
							现场活动大屏幕系统
						</small>
					</a>
					
					
					<a href="/index.php" class="navbar-brand">
						<span class="btn btn-xs btn-danger">去大屏幕界面</span>
					</a>
					<a href="/Modules/module.php?m=exportdata&c=admin&a=index" class="navbar-brand">
						<span class="btn btn-xs btn-warning">活动结束去导出数据</span>
					</a>
					<!-- /section:basics/navbar.layout.brand -->

					<!-- #section:basics/navbar.toggle -->

					<!-- /section:basics/navbar.toggle -->
				</div>

				<!-- #section:basics/navbar.dropdown -->
				<div class="navbar-buttons navbar-header pull-right" role="navigation">
					<ul class="nav ace-nav">
						<!-- #section:basics/navbar.user_menu -->
						<li class="light-blue dropdown-modal">
							<a data-toggle="dropdown" href="#" class="dropdown-toggle">
								<img class="nav-user-photo" src="assets/ace_v1.4/assets/avatars/user.jpg" alt="Jason's Photo" />
								<span class="user-info">
									<small>欢迎,</small>
									<?php echo $_smarty_tpl->tpl_vars['admin']->value['user'];?>

								</span>
								<i class="ace-icon fa fa-caret-down"></i>
							</a>

							<ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li>
									<a href="#" class="openeditpasswordform" >
										<i class="ace-icon fa fa-cog"></i>
										修改密码
									</a>
								</li>
								<li class="divider"></li>

								<li>
									<a href="logout.php">
										<i class="ace-icon fa fa-power-off"></i>
										退出登录
									</a>
								</li>
							</ul>
						</li>

						<!-- /section:basics/navbar.user_menu -->
					</ul>
				</div>

				<!-- /section:basics/navbar.dropdown -->
			</div><!-- /.navbar-container -->
		</div><?php }
}
