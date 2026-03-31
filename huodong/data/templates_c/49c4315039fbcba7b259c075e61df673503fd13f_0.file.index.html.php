<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:52:48
  from '/www/wwwroot/19.71jc.cn/Modules/Prize/templates/admin/index.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666027609aa7d7_01249292',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '49c4315039fbcba7b259c075e61df673503fd13f' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/Modules/Prize/templates/admin/index.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_666027609aa7d7_01249292 (Smarty_Internal_Template $_smarty_tpl) {
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
											奖品列表
										</h5>
										<div class="widget-toolbar no-border">
											<label>
											<button class="btn btn-xs btn-warning btn_add">添加</button>
											</label>
										</div>
									</div>
									<div class="widget-body">
										<div class="widget-main no-padding">
											<style>
											.prizestable tbody img{
												width:60px;
												height:60px;
											}
											</style>
											<table class="table table-striped table-bordered table-hover prizestable">
												<thead class="thin-border-bottom">
													<tr>
														<th>序号</th>
														<th>
															奖品名称和数量
														</th>
														<!-- <th>
															类型
														</th> -->
														<th>获奖概率</th>
														<th>
															图片
														</th>
														<th >操作</th>
													</tr>
												</thead>
												<tbody>
												<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['prizes']->value['data'], 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
													<tr >
														<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
</td>
														<td >
															名称：<?php echo $_smarty_tpl->tpl_vars['item']->value['prizename'];?>
<br/>
															总数：<?php echo $_smarty_tpl->tpl_vars['item']->value['num'];?>
<br/>
															冻结：<?php echo $_smarty_tpl->tpl_vars['item']->value['freezenum'];?>
<br/>
															剩余：<?php echo $_smarty_tpl->tpl_vars['item']->value['leftnum'];?>

														</td>
														<!-- <td ><?php echo $_smarty_tpl->tpl_vars['item']->value['typetext'];?>
</td> -->
														<td><?php echo $_smarty_tpl->tpl_vars['item']->value['rate']/10000;?>
%</td>
														<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['formatedtext']['html'];?>
</td>
														<td >
															<button class="btn btn-primary btn-xs btn_edit" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">修改</button>
															<button class="btn btn-danger btn-xs btn_delete" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">删除</button>
														</td>
													</tr>
												<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
												</tbody>
											</table>
											<?php echo $_smarty_tpl->tpl_vars['pagehtml']->value;?>

										</div>
									</div>
								</div>
								<div id="editformmodal" class="modal" tabindex="-1">
									<div class="modal-dialog">
										<div class="modal-content">
											<form action="module.php?m=prize&c=admin&a=ajax_act_save_prize" method="POST" id="editform" enctype="multipart/form-data">
												<div class="modal-header">
													<button type="button" class="close" data-dismiss="modal">&times;</button>
													<h4 class="blue bigger">奖品信息</h4>
												</div>
												<div class="modal-body">
													<div class="row">
														<div class="col-sm-12 columnitems">
																
								
														</div>
													</div>
												</div>
												<div class="modal-footer">
													<input name="id" type="hidden" value="0"/>
													<input name="activityid" type="hidden" value="<?php echo $_smarty_tpl->tpl_vars['activityid']->value;?>
"/>
													<input name="plugname" type="hidden" value="<?php echo $_smarty_tpl->tpl_vars['plugname']->value;?>
"/>
													<button class="btn btn-sm" data-dismiss="modal" >
														<i class="ace-icon fa fa-times"></i>
														取消
													</button>
													<button class="btn btn-sm btn-primary btn_save">
														<i class="ace-icon fa fa-check"></i>
														保存
													</button>
												</div>	
											</form>	
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
<?php echo '<script'; ?>
 type="text/javascript">
var ACTIVITYID=<?php echo $_smarty_tpl->tpl_vars['activityid']->value;?>
;
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['module_assets']->value;?>
/js/page_index.js"><?php echo '</script'; ?>
>
<?php echo $_smarty_tpl->tpl_vars['html_footer']->value;
}
}
