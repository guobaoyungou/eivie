<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:55:17
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/xingyunhaomadesignated.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666027f5907f88_85327977',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '9bf7f816ed22abaa87b9987a854e44b4f45cbb6d' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/xingyunhaomadesignated.html',
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
function content_666027f5907f88_85327977 (Smarty_Internal_Template $_smarty_tpl) {
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
								<a href="#">幸运号码</a>
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

						<small>幸运号码中奖记录和内定设置</small>
					</h3>
						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
									<div class="widget-box widget-color-blue" id="widget-box-2">
										<div class="widget-header">
											<h5 class="widget-title bigger lighter">
												<i class="ace-icon fa fa-users"></i>
												幸运号码中奖记录和内定列表
											</h5>
											<div class="widget-toolbar no-border">
												<label>
												<button class="btn btn-xs btn-warning btn_add" onclick="opendesignatedform()">添加内定</button>
												</label>
											</div>
										</div>
										<div class="widget-body">
													<div class="widget-main no-padding">
														<table class="table table-striped table-bordered table-hover">
															<thead class="thin-border-bottom">
																<tr>
																	<th>
																		中奖顺序
																	</th>
																	<th>
																		中奖号码
																	</th>
																	<th>
																		中奖状态
																	</th>
																	<th>
																		内定状态
																	</th>
																	<th >操作</th>
																</tr>
															</thead>

															<tbody>
															<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['xingyunhaoma']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>

																<tr id="item<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">
																	<td class="ordernum"><?php echo $_smarty_tpl->tpl_vars['item']->value['ordernum'];?>
</td>
																	<td class="lucknum"><?php echo $_smarty_tpl->tpl_vars['item']->value['lucknum'];?>
</td>
																	<td class="designated" data="<?php echo $_smarty_tpl->tpl_vars['item']->value['designated'];?>
"><?php echo $_smarty_tpl->tpl_vars['item']->value['designatedtext'];?>
</td>
																	<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['statustext'];?>
</td>
																	<td >
																		<a href="###" onclick="opendesignatedform('<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
')"><span class="label label-info">修改</span></a>
																		<a href="###" onclick="del('<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
')"><span class="label label-danger">删除...</span></a>
																	</td>
																</tr>
															<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
															</tbody>
														</table>
													</div>
											<?php echo $_smarty_tpl->tpl_vars['pagehtml']->value;?>

												</div>
										
									</div>
									<div id="designatedmodal" class="modal" tabindex="-1">
									<div class="modal-dialog">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal">&times;</button>
												<h4 class="blue bigger">内定设置</h4>
											</div>
											<div class="modal-body">
												<div class="row">
													<div class="col-sm-12">
														<div class="form-group">
															<label for="form-field-select-3">中奖序号：</label>
															<div>
																<input class="form-control" name="ordernum" value="" placeholder="设置第几位执行，要第5位中奖就设置5"/>
															</div>
														</div>
														<div class="form-group">
															<label for="form-field-select-3">中奖数字：</label>
															<div>
																<input class="form-control" name="lucknum" value="" placeholder="不能小于最小值，或者大于最大值"/>
															</div>
														</div>

														<div class="space-4"></div>
														<div class="form-group">
															<label >内定状态</label>
															<div>
																<select class="chosen-select" name="designated" >
										                          	<option value="2">必中</option>
										                          	<option value="3">不会中</option>
																</select>
															</div>
														</div>
													</div>
												</div>
											</div>

											<div class="modal-footer">
												<input type="hidden" name="id" value=""/>
												<button class="btn btn-sm" data-dismiss="modal" >
													<i class="ace-icon fa fa-times"></i>
													取消
												</button>
												<button class="btn btn-sm btn-primary" onclick="submitdesignatedform();">
													<i class="ace-icon fa fa-check"></i>
													保存
												</button>
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
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footercontent.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
<!-- 写每个页面自定的js -->
<?php echo '<script'; ?>
 type="text/javascript">

function opendesignatedform(id){
	id=id==undefined?0:id;
	if(id==0){
		$('input[name=ordernum]').val('');
		$('input[name=lucknum]').val('');
		$('select[name=designated]').val(2);
	}else{
		var ordernum=$('#item'+id).find('.ordernum').text();
		var lucknum=$('#item'+id).find('.lucknum').text();
		var designated=$('#item'+id).find('.designated').attr('data');
		$('input[name=ordernum]').val(ordernum);
		$('input[name=lucknum]').val(lucknum);
		$('select[name=designated]').val(designated);
	}
	$('input[name=id]').val(id);
	$('#designatedmodal').modal('show');
}
function submitdesignatedform(){
	var ordernum=$('input[name=ordernum]').val();;
	var lucknum=$('input[name=lucknum]').val();
	var designated=$('select[name=designated]').val();
	var id=$('input[name=id]').val();
	$.ajax({
		"url":"doxingyunhaoma.php?action=setdesignated",
		"type":"post",
		"data":{"ordernum":ordernum,"lucknum":lucknum,"designated":designated,"id":id},
		"dataType":"json",
		"success":function(json){
			alert(json.message);
			if(json.code>0){
				window.location.reload();
				//$('#designatedmodal').modal('hide');
			}
			
		}
	});
}
function del(id){
	if(!confirm('确认要删除这条记录吗？'))return false;
	$.ajax({
		"url":"doxingyunhaoma.php?action=deletelucknum",
		"type":"post",
		"data":{"id":id},
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
