<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:00:21
  from '/www/wwwroot/19.71jc.cn/Modules/Ydj/templates/admin/index.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666277c5879f94_29620409',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '1efda35cf5807d4888dbc9d7f52a33c89b5d3bfc' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/Modules/Ydj/templates/admin/index.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_666277c5879f94_29620409 (Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['html_header']->value;?>

<!--轮次设置-->
		<!-- /section:basics/navbar.layout -->
		<div class="main-container ace-save-state" id="main-container">
			<?php echo '<script'; ?>
 type="text/javascript">
			
				try{ace.settings.loadState('main-container')}catch(e){}
			
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
								<div class="widget-box widget-color-blue" >
									<div class="widget-header">
										<h5 class="widget-title bigger lighter">
											<i class="ace-icon fa fa-users"></i>
											摇大奖
										</h5>
										<div class="widget-toolbar no-border">
											<label>
											<button class="btn btn-xs btn-warning btn_add">添加</button>
											</label>
										</div>
									</div>
									<div class="widget-body">
										<div class="widget-main no-padding">
											<table class="table table-striped table-bordered table-hover">
												<thead class="thin-border-bottom">
													<tr>
														<th>序号</th>
														<th>
															持续时间（秒）
														</th>
														<th>
															状态
														</th>
														<th>
															每人可中奖次数
														</th>
														<th>
															中奖用户重复参与
														</th>
														<th>
															结果显示方式
														</th>
														<!-- <th>
															主题
														</th> -->
														<th >操作</th>
													</tr>
												</thead>
												<tbody>
												<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['configs']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
													<tr >
														<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
</td>
														<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['duration'];?>
秒 </td>
														<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['statustext'];?>
</td>
														<td ><?php if ($_smarty_tpl->tpl_vars['item']->value['winningagain'] == 0) {?>不限次数<?php } else {
echo $_smarty_tpl->tpl_vars['item']->value['winningagain'];
}?></td>
														<td ><?php if ($_smarty_tpl->tpl_vars['item']->value['joinagain'] == 2) {?>不可重复<?php } else { ?>可重复<?php }?></td>
														<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['showstyletext'];?>
</td>
														<!-- <td ><?php echo $_smarty_tpl->tpl_vars['item']->value['themename'];?>
</td> -->
														<td >
															<button class="btn btn-primary btn-xs btn_edit" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">修改</button>
															<button class="btn btn-primary btn-xs btn_prize" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">奖品设置</button>
															<button class="btn btn-primary btn-xs btn_designated" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">内定设置</button>
															<button class="btn btn-danger btn-xs btn_reset" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">重置游戏</button>
															<button class="btn btn-danger btn-xs btn_delete" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">删除</button>
															<button class="btn btn-primary btn-xs btn_records" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">游戏结果</button>
														</td>
													</tr>
												<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
												</tbody>
											</table>
										</div>
									</div>
								</div>
								<div id="configmodal" class="modal" tabindex="-1">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header">
													<button type="button" class="close" data-dismiss="modal">&times;</button>
													<h4 class="blue bigger">摇大奖设置</h4>
												</div>
												<div class="modal-body">
													<div class="row">
														<div class="col-sm-12">
														<div class="space-4"></div>
														<div class="row">
															<div class="form-group">
																<label class="col-xs-4 shake-duration" style="text-align: right;">持续时间（秒）：</label>
																<div>
																	<input type="number"  name="duration"  class="col-xs-5" value="" min="20"/>
																</div>
																<div class="col-xs-offset-4 col-xs-5"><span class="help-block"><i class="fa fa-info-circle"></i>设置游戏时间不能小于20秒哦。</span></div>
															</div>
														</div>
														<div class="space-4"></div>
														<div class="row">
															<div class="form-group">
																<label class="col-xs-4" style="text-align: right;">每人可中奖次数：</label>
																<div>
																	<input type="number"  name="winningagain"  class="col-xs-5" value="" /><br/>
																</div>
																<div class="col-xs-offset-4 col-xs-5"><span class="help-block"><i class="fa fa-info-circle"></i>单轮活动中，每人可获得奖品的次数</span></div>
															</div>
														</div>
														<div class="space-4"></div>
														<div class="row">
															<div class="form-group">
																<label class="col-xs-4" style="text-align: right;">可重复中奖：</label>
																<div>
																	<select class="chosen-select col-xs-5" name="joinagain" data-placeholder="请选择显示类型">
																		<option value="2">不可以</option>
																		<option value="1">可以</option>
																	</select>
																</div>
																<div class="col-xs-offset-4 col-xs-5"><span class="help-block"><i class="fa fa-info-circle"></i>在其他轮次中，中过奖的人，是否可以在本轮活动中再次中奖</span></div>
															</div>
														</div>
														<div class="space-4"></div>
														<div class="row">
															<div class="form-group">
																<label class="col-xs-4" style="text-align: right;">显示类型：</label>
																<div>
																	<select class="chosen-select col-xs-5" name="showstyle" data-placeholder="请选择显示类型">
																		<option value="1">昵称</option>
																		  <option value="2">姓名</option>
																		  <option value="3">手机号</option>
																	</select>
																</div>
															</div>
														</div>
														
														<!-- <div class="space-4"></div>
														<div class="row">
															<div class="form-group">
																<label class="col-xs-4" style="text-align: right;">主题：</label>
																<div>
																	<select class="chosen-select col-xs-5" name="themeid" data-placeholder="请选择主题">
																		<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['themes']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
																		<option value="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
"><?php echo $_smarty_tpl->tpl_vars['item']->value['themename'];?>
</option>
																		<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
																	</select>
																</div>
															</div>
														</div> -->
														<div class="space-4"></div>
													</div>
												</div>
	
												<div class="modal-footer">
													<input name="id" type="hidden" value="0"/>
													<button class="btn btn-sm" data-dismiss="modal" >
														<i class="ace-icon fa fa-times"></i>
														取消
													</button>
													<button class="btn btn-sm btn-primary btn_save">
														<i class="ace-icon fa fa-check"></i>
														保存
													</button>
												</div>
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
 type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['module_assets']->value;?>
/js/admin_index.js"><?php echo '</script'; ?>
>
<?php echo $_smarty_tpl->tpl_vars['html_footer']->value;
}
}
