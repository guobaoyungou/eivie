<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:55:08
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/xingyunshoujihao.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666027ec451d61_65224364',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'de55382a19737c1c8f359370f31ed0c308f0ff6d' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/xingyunshoujihao.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:templates/html_header.html' => 1,
    'file:templates/html_sidebar.html' => 1,
    'file:templates/html_footercontent.html' => 1,
    'file:templates/html_footer.html' => 1,
  ),
),false)) {
function content_666027ec451d61_65224364 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_subTemplateRender("file:templates/html_header.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
		<!-- /section:basics/navbar.layout -->
		<div class="main-container ace-save-state" id="main-container">
			<?php echo '<script'; ?>
 type="text/javascript">
			
				try{ace.settings.loadState('main-container')}catch(e){}
			
			<?php echo '</script'; ?>
>
			<!-- #section:basics/sidebar -->
			<?php $_smarty_tpl->_subTemplateRender("file:templates/html_sidebar.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
			<!-- /section:basics/sidebar -->
			<div class="main-content">
				<div class="main-content-inner">
					<!-- #section:basics/content.breadcrumbs -->
					<div class="breadcrumbs ace-save-state" id="breadcrumbs">
						<ul class="breadcrumb">
							<li>
								<i class="ace-icon fa fa-home home-icon"></i>
								<a href="index.php">首页</a>
							</li>

							<li>
								<a href="#">幸运手机号</a>
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

						<small>幸运手机号中奖记录</small>
					</h3>
						<div class="row">
							<div class="col-xs-12">
							<div class="widget-box widget-color-blue" id="widget-box-2">
										<div class="widget-header">
											<h5 class="widget-title bigger lighter">
												<i class="ace-icon fa fa-users"></i>
												幸运手机号
											</h5>
											<div class="widget-toolbar no-border">
												<label>
												<button class="btn btn-xs btn-warning btn_add" onclick="clearlucknum()">清除中奖记录</button>
												</label>
												
											</div>
										</div>
										<div class="widget-body">
													<div class="widget-main no-padding">
								<!-- PAGE CONTENT BEGINS -->
								<table class="table table-striped table-bordered table-hover">
									<thead class="thin-border-bottom">
										<tr>
											<th>微信</th>
											<th>
												中奖号码
											</th>
											<th>
												中奖状态
											</th>
											<th >操作</th>
										</tr>
									</thead>
									<tbody>
									<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['xingyunshoujihao']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
										<tr >
											<td class="avatar"><img src="<?php echo $_smarty_tpl->tpl_vars['item']->value['avatar'];?>
" style="width:30px;height:30px;"/><?php echo $_smarty_tpl->tpl_vars['item']->value['nickname'];?>
</td>
											<td class="phone"><?php echo $_smarty_tpl->tpl_vars['item']->value['phone'];?>
</td>
											<td class="status"><?php echo $_smarty_tpl->tpl_vars['item']->value['statustext'];?>
</td>
											<td >
												<a href="###" onclick="del(<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
)"><span class="label label-danger">删除</span></a>
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
								<!-- PAGE CONTENT ENDS -->
							</div><!-- /.col -->
						</div><!-- /.row -->
					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footercontent.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
<!-- 写每个页面自定的js -->
<?php echo '<script'; ?>
 type="text/javascript">

function del(id){
	if(!confirm("确认删除这条中奖记录吗？"))return false;
	$.ajax({
		"url":"doxingyunshoujihao.php?action=delitem",
		"data":{"id":id},
		"type":"get",
		"dataType":"json",
		"success":function(json){
			if(json.code>0){
				alert(json.message);
				window.location.reload();
			}else
				alert(json.message);
		}
		
	});
}
function clearlucknum(){
	if(!confirm('确认要清空中奖记录吗？'))return false;
	$.ajax({
		"url":"doxingyunshoujihao.php?action=clearlucknum",
		"type":"get",
		"dataType":"json",
		"success":function(json){
			alert(json.message);
			if(json.code>0){
				window.location.reload();
			}
		}
	});
}

<?php echo '</script'; ?>
>
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
