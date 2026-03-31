<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:52:55
  from '/www/wwwroot/19.71jc.cn/Modules/Game/templates/admin/index.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_66602767916d32_34670102',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '91018b06ad5bc090ffdfda416226e467d82eeb3d' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/Modules/Game/templates/admin/index.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_66602767916d32_34670102 (Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['html_header']->value;?>

<style>
.pointer{
	cursor: pointer;
}
.float-right{
	float: right;
}
.prize-img img{
	width: 70px;
	height:70px;
}
</style>
<!--轮次设置-->
<!-- /section:basics/navbar.layout -->
<div class="main-container ace-save-state" id="main-container">
	<?php echo '<script'; ?>
 type="text/javascript">
		
		try {
			ace.settings.loadState('main-container')
		} catch (e) {} 
		
	<?php echo '</script'; ?>
>
	<!-- #section:basics/sidebar -->
	<?php echo $_smarty_tpl->tpl_vars['html_sidebar']->value;?>

	<!-- /section:basics/sidebar -->
	<div class="main-content">
		<div class="main-content-inner">
			<!-- #section:basics/content.breadcrumbs -->
			<div class="breadcrumbs ace-save-state" id="breadcrumbs">
				<ul class="breadcrumb">
					<li>
						<i class="ace-icon fa fa-home home-icon"></i>
						<a href="/myadmin/index.php">首页</a>
					</li>
					<li class="active"><?php echo $_smarty_tpl->tpl_vars['title']->value;?>
</li>
				</ul><!-- /.breadcrumb -->
				<!-- /section:basics/content.searchbox -->
			</div>

			<!-- /section:basics/content.breadcrumbs -->
			<div class="page-content">
				<h3 class="header smaller lighter blue">
					<?php echo $_smarty_tpl->tpl_vars['title']->value;?>

					<small><?php echo $_smarty_tpl->tpl_vars['description']->value;?>
</small>
				</h3>
				<div class="row">
					<div class="col-xs-12">
						<!-- PAGE CONTENT BEGINS -->
                        <div class="row">
							<div class="col-xs-3 roundlist">
								<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['configs']->value, 'val', false, NULL, 'config', array (
  'iteration' => true,
));
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['val']->value) {
$_smarty_tpl->tpl_vars['__smarty_foreach_config']->value['iteration']++;
?>
								<div class="well">
									<span class="btn btn-minier btn-white <?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['id'] == $_smarty_tpl->tpl_vars['val']->value['id']) {?>btn-success<?php }?> btn-gameconfig" data-id="<?php echo $_smarty_tpl->tpl_vars['val']->value['id'];?>
">第<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_foreach_config']->value['iteration']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_config']->value['iteration'] : null);?>
轮游戏<?php if ($_smarty_tpl->tpl_vars['val']->value['status'] == 2) {?>（进行中）<?php }
if ($_smarty_tpl->tpl_vars['val']->value['status'] == 3) {?>（结束）<?php }?></span><span  class="pointer float-right btn-delgameconfig" data-id="<?php echo $_smarty_tpl->tpl_vars['val']->value['id'];?>
"><i class="fa fa-times"></i></span>
								</div>
								<hr/>
								<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
								<button class="btn btn-info" style="width:100%" id="btn-openmodaladdgameconfig">添加</button>
							</div>
							<div class="col-xs-9">
								<?php if ($_smarty_tpl->tpl_vars['currentconfig']->value) {?>
								<div class="tabbable">
									<ul class="nav nav-tabs" id="myTab">
										<li class="active">
											<a data-toggle="tab" href="#gameconfig">
												<i class="green ace-icon fa fa-gear bigger-120"></i>
												配置
											</a>
										</li>
										<?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['themeconfig'] != '') {?>
										<li>
											<a data-toggle="tab" href="#gametheme"  id="btn-themeconfig" data-id="<?php echo $_smarty_tpl->tpl_vars['currentconfig']->value['id'];?>
">
												<i class="red ace-icon fa fa-tachometer bigger-120"></i>	
												主题设置
											</a>
										</li>
										<?php }?>
										<?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['status'] == 3) {?>
										<li >
											<a data-toggle="tab" href="#winners" id="btn-showwinners" data-id="<?php echo $_smarty_tpl->tpl_vars['currentconfig']->value['id'];?>
">
												<i class="red ace-icon fa fa-list bigger-120"></i>	
												中奖名单
											</a>
										</li>
										<?php }?>
									</ul>

									<div class="tab-content">
										<div id="gameconfig" class="tab-pane fade in active">
											<div class="row">
												<div class="col-sm-12">
													<form class="form-horizontal" role="form" class="gameconfigform" >
														<?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['status'] != 1) {?>
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1-1">游戏状态：</label>
															<div class="col-sm-9">
																<div style="padding-top:8px;">
																<?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['status'] == 2) {?><span class="label label-primary label-white middle">进行中</span><?php }
if ($_smarty_tpl->tpl_vars['currentconfig']->value['status'] == 3) {?><span class="label label-danger label-white middle">结束</span><?php }?> <?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['status'] != 1) {?><span class="btn btn-minier  btn-danger btn-reset" data-id="<?php echo $_smarty_tpl->tpl_vars['currentconfig']->value['id'];?>
"><i class="fa fa-repeat"></i>点此重置游戏状态</span><?php }?>
																</div>
															</div>
														</div>
														<?php }?>
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1-1">前n名中奖：</label>
															<div class="col-sm-9">
																<div style="padding-top:8px;">
																	<input type="number" name="toprank" value="<?php echo $_smarty_tpl->tpl_vars['currentconfig']->value['toprank'];?>
" min="1" max="200">
																</div>
															</div>
														</div>
														<div class="form-group">
																<label class="col-sm-3 control-label no-padding-right" for="form-field-1">选择主题：</label>
																<div class="col-sm-9">
																		<select name="themeid" class="col-xs-10 col-sm-5">
																			<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['themes']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
																			<option value='<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
' <?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['themeid'] == $_smarty_tpl->tpl_vars['item']->value['id']) {?>selected<?php }?>><?php echo $_smarty_tpl->tpl_vars['item']->value['themename'];?>
</option>
																			<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
																		</select>
																</div>
														</div>
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1-1">可重复参与：</label>
															<div class="col-sm-9">
																<div style="padding-top:8px;">
																	<label>
																		<input name="winagain" class="ace ace-switch" type="checkbox" value="2" <?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['winagain'] == 2) {?>checked<?php }?>/>
																		<span class="lbl"></span>
																	</label>
																</div>
															</div>
														</div>
														<!-- /section:elements.form -->
														<div class="space-4"></div>
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-2">结果显示：</label>
															<div class="col-sm-9">
																	<div class="radio">
																		<label>
																			<input name="showtype" type="radio" class="ace" <?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['showtype'] == 'nickname') {?>checked<?php }?> value="nickname"/>
																			<span class="lbl">昵称</span>
																		</label>
																		<label>
																			<input name="showtype" type="radio" class="ace"  <?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['showtype'] == 'signname') {?>checked<?php }?> value="signname"/>
																			<span class="lbl">姓名</span>
																		</label>
																		<label>
																			<input name="showtype" type="radio" class="ace"  <?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['showtype'] == 'phone') {?>checked<?php }?> value="phone"/>
																			<span class="lbl">手机号</span>
																		</label>
																	</div>
															</div>
														</div>
														<div class="clearfix form-actions">
															<div class="col-md-offset-3 col-md-9">
																<input type="hidden" name="id" value="<?php echo $_smarty_tpl->tpl_vars['currentconfig']->value['id'];?>
"/>
																<button class="btn btn-info btn-savegameconfig" type="button">
																	<i class="ace-icon fa fa-check bigger-110"></i>
																	保存
																</button>
															</div>
														</div>
													</form>
												</div>
											</div>
										</div>
										<?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['themeconfig'] != '') {?>
										<div id="gametheme" class="tab-pane fade">
											<p>网速不是很好，没有加载成功哦，请刷新页面后再试试</p>
										</div>
										<?php }?>
										<?php if ($_smarty_tpl->tpl_vars['currentconfig']->value['status'] == 3) {?>
										<div id="winners" class="tab-pane fade">
											<p>网速不是很好，没有加载成功哦，请刷新页面后再试试</p>
										</div>
										<?php }?>
									</div>
								</div>
								<?php }?>
							</div>
						</div>
						<div class="modal fade" id="addgameconfigmodal" aria-hidden="true">
								<div class="modal-dialog">
									<div class="modal-content">
										<div class="modal-header">
											<button aria-hidden="true" class="close" data-dismiss="modal" type="button">&times;</button>
											<h4 class="blue bigger">添加游戏</h4>
										</div>
										<div class="modal-body">
											<div class="row">
												<div class="col-sm-12">
													<form class="form-horizontal" id="addgameconfig" role="form" >
																												
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right" for="form-field-1-1">前n名中奖：</label>
															<div class="col-sm-9">
																<div style="padding-top:8px;">
																	<input type="number" name="toprank" value="3">
																</div>
															</div>
														</div>
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right">选择主题:</label>
															<div class="col-sm-9">
																<select name="themeid">
																	<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['themes']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
																	<option value='<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
'><?php echo $_smarty_tpl->tpl_vars['item']->value['themename'];?>
</option>
																	<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
																</select>
															</div>
														</div>
														
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right">可重复参与:</label>
															<div class="col-sm-9">
																<div style="padding-top:8px;">
																<label>
																	<input name="winagain" class="ace ace-switch" type="checkbox" value="2"/>
																	<span class="lbl"></span>
																</label>
																</div>
															</div>
														</div>
														<div class="form-group">
															<label class="col-sm-3 control-label no-padding-right">中奖结果:</label>
															<div class="col-sm-9">
																	<div class="radio">
																		<label>
																			<input name="showtype" type="radio" class="ace" checked value="nickname"/>
																			<span class="lbl">昵称</span>
																		</label>
																		<label>
																			<input name="showtype" type="radio" class="ace" value="signname"/>
																			<span class="lbl">姓名</span>
																		</label>
																		<label>
																			<input name="showtype" type="radio" class="ace" value="phone"/>
																			<span class="lbl">手机号</span>
																		</label>
																	</div>
															</div>
														</div>
													</form>
												</div>
											</div>
										</div>
										<div class="modal-footer">
											<button class="btn btn-sm btn-primary btn-addgameconfig">
												<i class="ace-icon fa fa-check"></i>
												保存
											</button>
											<button class="btn btn-sm " data-dismiss="modal" type="button">关闭</button>
										</div>
									</div>
								</div>
							</div>
						<!-- PAGE CONTENT ENDS -->
					</div><!-- /.col -->
				</div><!-- /.row -->
			</div><!-- /.page-content -->
		</div>
	</div><!-- /.main-content -->
	<?php echo $_smarty_tpl->tpl_vars['html_footercontent']->value;?>

	<!-- 写每个页面自定的js -->
	<?php echo '<script'; ?>
 type="text/javascript" src="/myadmin/assets/js/jquery.form.js"><?php echo '</script'; ?>
>
	<!--引入CSS-->
	<link rel="stylesheet" type="text/css" href="/myadmin/assets/webuploader-0.1.5/webuploader.css">
	<!--引入JS-->
	<?php echo '<script'; ?>
 type="text/javascript" src="/myadmin/assets/webuploader-0.1.5/webuploader.min.js"><?php echo '</script'; ?>
>
	<?php echo '<script'; ?>
 type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['module_assets']->value;?>
/js/admin_index.js"><?php echo '</script'; ?>
>
	<?php echo $_smarty_tpl->tpl_vars['html_footer']->value;
}
}
