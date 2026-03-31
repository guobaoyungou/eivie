<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:55:05
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/voteconfig.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666027e90974e4_68558029',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '2ec1e6ea6a0518c4f93c37d936ba02e5a8f1d058' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/voteconfig.html',
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
function content_666027e90974e4_68558029 (Smarty_Internal_Template $_smarty_tpl) {
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
							<li class="active"><?php echo $_smarty_tpl->tpl_vars['title']->value;?>
</li>
						</ul><!-- /.breadcrumb -->
						<!-- /section:basics/content.searchbox -->
					</div>

					<!-- /section:basics/content.breadcrumbs -->
					<div class="page-content">
					<h3 class="header smaller lighter blue">
						<?php echo $_smarty_tpl->tpl_vars['title']->value;?>

						<small>投票的主题列表</small>
					</h3>
						<div class="row">
							<div class="col-xs-12">
							<div class="widget-box widget-color-blue" id="widget-box-2">
								<div class="widget-header">
									<h5 class="widget-title bigger lighter">
										<i class="ace-icon fa fa-users"></i>
										投票主题
									</h5>
									<div class="widget-toolbar no-border">
										<label>
										<button class="btn btn-xs btn-warning btn_add" onclick="openvoteform(0)">添加</button>
										</label>
										
									</div>
								</div>
							<div class="widget-body">
								<div class="widget-main no-padding">
									<!-- PAGE CONTENT BEGINS -->
									<table class="table table-striped table-bordered table-hover">
										<thead class="thin-border-bottom">
											<tr>
												<th>序号</th>
												<th>主题</th>
												<th>
													状态
												</th>
												<th>
													显示方式
												</th>
												<th>
													单位
												</th>
											
												<th >操作</th>
											</tr>
										</thead>
										<tbody>
										<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['votes']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
											<tr >
												<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
</td>
												<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['votetitle'];?>
</td>
												<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['statustext'];?>
</td>
												<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['showtypetext'];?>
</td>
												<td ><?php echo $_smarty_tpl->tpl_vars['item']->value['unit'];?>
</td>
												<td >
													<a href="###" onclick="openvoteform(<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
)"><span class="label label-primary">修改</span></a>
													<a href="voteitems.php?id=<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
" ><span class="label label-primary">投票选项</span></a>
													<a href="voterecord.php?id=<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
" ><span class="label label-primary">投票结果</span></a>
													<a href="###" onclick="del(<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
)"><span class="label label-danger">删除</span></a>
													<a href="###" onclick="clearvote(<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
)"><span class="label label-danger">清空投票记录</span></a>
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

								<div id="voteconfigmodal" class="modal" tabindex="-1">
									<div class="modal-dialog">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal">&times;</button>
												<h4 class="blue bigger">投票主题设置</h4>
											</div>
											<div class="modal-body">
												<div class="row">
													<div class="col-sm-12">
													<div class="row">
														<div class="form-group">
															<label for="form-field-select-3" class="col-xs-2">投票主题：</label>
															<div>
																<input type="text" id="form-field-1" name="votetitle" placeholder="投票主题" class="col-xs-8" value="" />
															</div>
														</div>
													</div>
													<div class="space-4"></div>
													<div class="row">
														<div class="form-group">
															<label class="col-xs-2">投票模式：</label>
															<div>
																<select class="chosen-select" name="votemode" data-placeholder="请选择显示类型">
																	<option value="1">最大投票数</option>
										                          	<option value="2">固定投票数</option>
										                          	<option value="3">最小投票数</option>
																</select>
															</div>
														</div>
													</div>
													<div class="space-4"></div>
													<div class="row">
														<div class="form-group">
															<label for="form-field-select-3" class="col-xs-2">每个用户可投票数：</label>
															<div>
																<input type="number" id="form-field-1" name="votenum" placeholder="每个用户可投票数" class="col-xs-8" value="" />
															</div>
														</div>
													</div>
													<div class="space-4"></div>
													<div class="row">
														<div class="form-group">
															<label for="form-field-select-3" class="col-xs-2">单位：</label>
															<div>
																<input type="text" name="unit" placeholder="比如：票" class="col-xs-8" value="" />
															</div>
														</div>
													</div>
													<div class="space-4"></div>
														<div class="row">
														<div class="form-group">
															<label for="form-field-select-3" class="col-xs-2">状态：</label>
															<div>
																<select class="chosen-select" class="col-xs-8" name="status" >
																	<option value="1">进行中</option>
										                          	<option value="2">结束</option>
																</select>
															</div>
														</div>
													</div>
														
													<div class="space-4"></div>
													<div class="row">
														<div class="form-group">
															<label class="col-xs-2">显示类型：</label>
															<div>
																<select class="chosen-select" name="showtype" data-placeholder="请选择显示类型">
																	<option value="1">横向</option>
										                          	<option value="2">纵向</option>
										                          	<option value="3">图片式</option>
																</select>
															</div>
														</div>
													</div>
													
													</div>
													<div class="space-4"></div>
													<!-- <div class="row">
														<div class="form-group">
															<label class="col-xs-2">可改投：</label>
															<div>
																<select class="chosen-select" name="editable">
																	<option value="1">不可以</option>
										                          	<option value="2">可以</option>
																</select>
															</div>
														</div>
													</div> -->
													</div>
												</div>
											</div>

											<div class="modal-footer">
												<input name="id" type="hidden" value="0"/>
												<button class="btn btn-sm" data-dismiss="modal" >
													<i class="ace-icon fa fa-times"></i>
													取消
												</button>
												<button class="btn btn-sm btn-primary" onclick="savevoteconfig();">
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

function del(id){
	if(!confirm("确认删除这条投票记录吗？"))return false;
	$.ajax({
		"url":"dovoteconfig.php?action=delitem",
		"data":{"id":id},
		"type":"post",
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
function clearvote(id){
	if(!confirm("确认删除这个投票的数据吗？"))return false;
	$.ajax({
		"url":"dovoteconfig.php?action=clearvote",
		"data":{"id":id},
		"type":"post",
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
function openvoteform(id){
	$('input[name=id]').val(id);
	if(id>0){
		$.ajax({
			"url":"dovoteconfig.php?action=getvoteconfig",
			"type":"post",
			"data":{"id":id},
			"dataType":"json",
			"success":function(json){
				if(json.code>0){
					console.log(json.data);
					$('input[name=votetitle]').val(json.data.votetitle);
					$('input[name=votenum]').val(json.data.votenum);
					$('select[name=status]').val(json.data.status);
					$('input[name=unit]').val(json.data.unit);
					$('select[name=showtype]').val(json.data.showtype);
					$('select[name=votemode]').val(json.data.votemode);
					// $('select[name=editable]').val(json.data.editable);
				}
			}
		})
	}else{
		$('input[name=votetitle]').val('');
		$('input[name=votenum]').val(1);
		$('select[name=status]').val(1);
		$('select[name=showtype]').val(1);
		$('select[name=votemode]').val(1);
		$('input[name=unit]').val('');
		// $('select[name=editable]').val(1);
	}
	
	$('#voteconfigmodal').modal('show');
}

function savevoteconfig(){
	var id=$('input[name=id]').val();
	var votetitle=$('input[name=votetitle]').val();
	var votenum=$('input[name=votenum]').val();
	var status=$('select[name=status]').val();
	var votemode=$('select[name=votemode]').val();
	var showtype=$('select[name=showtype]').val();
	var editable=$('select[name=editable]').val();
	var unit=$('input[name=unit]').val();
	$.ajax({
		"url":"dovoteconfig.php?action=savevoteconfig",
		"type":"post",
		"data":{"id":id,"votetitle":votetitle,"votemode":votemode,"votenum":votenum,"status":status,"showtype":showtype,"editable":editable,'unit':unit},
		"dataType":"json",
		"success":function(json){
			if(json.code>0){
				// alert(json.message);
				window.location.reload();
			}else{
				alert(json.message);
			}
		}
	});
}

<?php echo '</script'; ?>
>
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
