<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:54:59
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/qiandao.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666027e34b6816_77115396',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '88bd82d897a1be24c1f0b97f361df1334cc8dafe' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/qiandao.html',
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
function content_666027e34b6816_77115396 (Smarty_Internal_Template $_smarty_tpl) {
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
						签到用户列表1
						<div class="nav-search pull-right" id="nav-search" style="position:relative;">
							<form class="form-search">
								<span class="input-icon">
									<input type="text" name="search" placeholder="昵称、姓名、手机号" value="<?php echo $_smarty_tpl->tpl_vars['searchtext']->value;?>
" class="nav-search-input" id="nav-search-input" autocomplete="off" />
									<i class="ace-icon fa fa-search nav-search-icon"></i>
								</span>
							</form>
						</div>
					</h3>
						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
									<div class="widget-box widget-color-blue" id="widget-box-2">
										<div class="widget-header">
											<h5 class="widget-title bigger lighter">
												<i class="ace-icon fa fa-users"></i>
												签到用户列表
											</h5>
											<div class="widget-toolbar no-border">
												<!-- <label>
													<button class="btn btn-xs btn-warning" onclick="window.location.href='neiding.php';">去内定列表</button>
												</label> -->
												<label>
												<button class="btn btn-xs btn-warning btn_export">导出excel</button>
												</label>
											</div>
										</div>
										<div class="widget-body">
													<div class="widget-main no-padding">
														<table class="table table-striped table-bordered table-hover">
															<thead class="thin-border-bottom">
																<tr>
																	<th>
																		头像
																	</th>
																	<th>
																		
																		昵称
																	</th>
																	<th>
																		
																		姓名
																	</th>
																	<th>
																		手机号
																	</th>
                                                                                                                                        <th>
																		签到时间
																	</th>
																	<th>自定义信息</th>
																	<th>状态</th>
																	<th >操作</th>
																</tr>
															</thead>

															<tbody>
															<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['flaglist']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
																<tr id="item_<?php echo $_smarty_tpl->tpl_vars['item']->value['openid'];?>
">
																	<td class=""><img src="<?php echo $_smarty_tpl->tpl_vars['item']->value['avatar'];?>
" style="width:40px;height:40px;"/></td>
																	<td class=""><?php echo $_smarty_tpl->tpl_vars['item']->value['nickname'];?>
</td>
																	<td class=""><?php echo $_smarty_tpl->tpl_vars['item']->value['signname'];?>
</td>
                                                                                                                                        
																	<td>
																		<?php echo $_smarty_tpl->tpl_vars['item']->value['phone'];?>

																	</td>
                                                                                                                                        <td class=""><?php echo $_smarty_tpl->tpl_vars['item']->value['datetime'];?>
</td>
																	<td>
																		<?php if ($_smarty_tpl->tpl_vars['item']->value['extentions'] != '') {?>
																		<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['item']->value['extentions'], 'ext');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['ext']->value) {
?>
																		<?php echo $_smarty_tpl->tpl_vars['ext']->value['title'];?>
:<?php echo $_smarty_tpl->tpl_vars['ext']->value['val'];?>
<br>
																		<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
																		<?php }?>
																		
																	</td>
																	<td>
																		<?php if ($_smarty_tpl->tpl_vars['item']->value['status'] == 1) {?><span class="green" >审核通过</span><?php }
if ($_smarty_tpl->tpl_vars['item']->value['status'] == 2) {?><span class="red" >待审核</span><?php }?>
																	</td>
																	<td >
																		<?php if ($_smarty_tpl->tpl_vars['item']->value['status'] == 1) {?>
																		<a href="###" onclick="submitbandform('<?php echo $_smarty_tpl->tpl_vars['item']->value['openid'];?>
',2)"><span class="label label-info">取消审核</span></a>
																		<?php }?>
																		<?php if ($_smarty_tpl->tpl_vars['item']->value['status'] == 2) {?>
																		<a href="###" onclick="submitbandform('<?php echo $_smarty_tpl->tpl_vars['item']->value['openid'];?>
',1)"><span class="label label-info">通过审核</span></a>
																		<?php }?>
																		<a href="###" onclick="submitbandform('<?php echo $_smarty_tpl->tpl_vars['item']->value['openid'];?>
',3)"><span class="label label-danger">删除</span></a>
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
								<input type="hidden" name="openid" value=""/>
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


jQuery(function($){
	$('.btn_export').bind('click',function(){
		window.open('exportqd.php');
	});
});

function submitbandform(openid,status){
	if(status==3){
		if(!confirm('确认要删除这个用户的信息吗？')){
			return false;
		}
	}
	$.ajax({
		"url":"doqiandao.php?action=setband",
		"type":"post",
		"dataType":"json",
		"data":{"openid":openid,"status":status},
		"success":function(json){
			if(json.code==1){
				alert(json.message);
				window.location.reload();
			}
			if(json.code==2){
				$('#item_'+openid).remove();
			}
		}
	});
}


<?php echo '</script'; ?>
>
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
