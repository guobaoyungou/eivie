<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:00:28
  from '/www/wwwroot/19.71jc.cn/Modules/Choujiang/templates/admin/index.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666277cc9f8191_31424028',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '3bd91348951a0aa58848269e97ba007d970f76c4' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/Modules/Choujiang/templates/admin/index.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_666277cc9f8191_31424028 (Smarty_Internal_Template $_smarty_tpl) {
echo $_smarty_tpl->tpl_vars['html_header']->value;?>

<link rel="stylesheet" href="<?php echo $_smarty_tpl->tpl_vars['assets']->value;?>
/ace_v1.4/components/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.css" />
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
											<?php echo $_smarty_tpl->tpl_vars['title']->value;?>

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
															标题
														</th>
														<th>
															主题
														</th>
														<th>
															默认可参与次数
														</th>
														<th>
															手机显示剩余
														</th>
														<th>
															开始/结束时间
														</th>
														
														<th >操作</th>
													</tr>
												</thead>
												<tbody>
												<?php if ($_smarty_tpl->tpl_vars['configs']->value != '') {?>
												<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['configs']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
													<tr >
														<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
</td>
														<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['title'];?>
</td>
														<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['themename'];?>
</td>
														<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['defaultnum'];?>
</td>
														<td ><?php if ($_smarty_tpl->tpl_vars['item']->value['showleftnum'] == 2) {?>不显示<?php } else { ?>显示<?php }?></td>
                                                        <td >开始:<?php echo $_smarty_tpl->tpl_vars['item']->value['started_at'];?>
<br/>
                                                        结束:<?php echo $_smarty_tpl->tpl_vars['item']->value['ended_at'];?>
</td>
														<td >
															<button class="btn btn-primary btn-xs btn_edit" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">修改</button>
															<button class="btn btn-primary btn-xs btn_prize" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">奖品设置</button>
															<button class="btn btn-primary btn-xs btn_designated" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
" data-title="<?php echo $_smarty_tpl->tpl_vars['item']->value['themename'];?>
">内定设置</button>
															<button class="btn btn-danger btn-xs btn_reset" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">清空数据</button>
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
												<?php }?>
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
                                                <h4 class="blue bigger">手机端游戏</h4>
                                            </div>
                                            <div class="modal-body">
                                                <form id="savemenuform" action="/Modules/module.php?m=menu&c=admin&a=ajax_act_save_menu" method="POST" enctype="multipart/form-data">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <div class="space-4"></div>
                                                        <div class="row">
                                                            <div class="form-group">
                                                                <label class="col-xs-2" style="text-align: right;">标题：</label>
                                                                <div class="col-xs-10">
                                                                    <input type="text"  name="title"  class="form-control" value="" />
                                                                </div>
                                                                <div class="col-xs-offset-2 col-xs-10"><span class="help-block"><i class="fa fa-info-circle"></i>尽量不要超过4个汉字</span></div>
                                                            </div>
                                                        </div>
                                                    <div class="space-4"></div>
                                                    <div class="row">
                                                        <div class="form-group">
                                                            <label class="col-xs-2 " style="text-align: right;">默认抽取次数：</label>
                                                            <div class="col-xs-10">
                                                                <input type="number"  name="defaultnum"  class="form-control" value="" min="1"/>
                                                            </div>
                                                            <div class="col-xs-offset-2 col-xs-10"><span class="help-block"><i class="fa fa-info-circle"></i>可以参与的抽奖次数。</span></div>
                                                        </div>
                                                    </div>

                                                    <div class="space-4"></div>
                                                    <div class="row">
                                                        <div class="form-group">
                                                            <label class="col-xs-2 " style="text-align: right;">主题：</label>
                                                            <div class="col-xs-10">
                                                                <select name="themeid">
                                                                   
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
													<div class="row">
                                                        <div class="form-group">
                                                            <label class="col-xs-2 " style="text-align: right;">显示剩余数：</label>
                                                            <div class="col-xs-10">
                                                                <div style="padding-top:8px;">
																	<label>
																		<input name="showleftnum" class="ace ace-switch" type="checkbox" value="1"/>
																		<span class="lbl"></span>
																	</label>
																	</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="space-4"></div>
                                                    <div class="row">
                                                        <div class="form-group">
                                                            <label class="col-xs-2" style="text-align: right;">起止时间：</label>
                                                            <div class="col-xs-10">
                                                                从<input type="text" class="start" name="started_at" value=""/>到<input type="text" class="end" name="ended_at" value=""/>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="space-4"></div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <input name="id" type="hidden" value="0"/>
                                                <button type="button" class="btn btn-sm" data-dismiss="modal" >
                                                    <i class="ace-icon fa fa-times"></i>
                                                    取消
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary btn_save">
                                                    <i class="ace-icon fa fa-check"></i>
                                                    保存
                                                </button>
                                            </div>
                                        </form>
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
 src="<?php echo $_smarty_tpl->tpl_vars['assets']->value;?>
/ace_v1.4/components/moment/moment.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['assets']->value;?>
/ace_v1.4/components/eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 >
var THEMES=<?php echo $_smarty_tpl->tpl_vars['themesjson']->value;?>
;
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['module_assets']->value;?>
/js/admin_index.js"><?php echo '</script'; ?>
>
<?php echo $_smarty_tpl->tpl_vars['html_footer']->value;
}
}
