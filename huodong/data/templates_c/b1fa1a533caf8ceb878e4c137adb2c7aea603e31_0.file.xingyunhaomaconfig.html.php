<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:55:14
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/xingyunhaomaconfig.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666027f2196c52_18251978',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'b1fa1a533caf8ceb878e4c137adb2c7aea603e31' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/xingyunhaomaconfig.html',
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
function content_666027f2196c52_18251978 (Smarty_Internal_Template $_smarty_tpl) {
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

						<small>幸运号码设置信息</small>
					</h3>
						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
									<form class="form-horizontal" role="form">
									<!-- #section:elements.form -->
									<div class="space-4"></div>
									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" >幸运号码最小值</label>
										<div class="col-sm-9">
											<input name="minnum" class="form-control " value="<?php echo $_smarty_tpl->tpl_vars['xingyunhaomaconfig']->value['minnum'];?>
" placeholder="幸运号码最小值，默认为0" />
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" >幸运号码最大值</label>
										<div class="col-sm-9">
											<input name="maxnum" class="form-control " value="<?php echo $_smarty_tpl->tpl_vars['xingyunhaomaconfig']->value['maxnum'];?>
" placeholder="幸运号码最大值，默认为2000" />
										</div>
									</div>
									<div class="clearfix form-actions">
										<div class="col-md-offset-3 col-md-9">
											<button class="btn btn-info btn-save" type="button">
												<i class="ace-icon fa fa-check bigger-110"></i>
												保存
											</button>
											&nbsp; &nbsp; &nbsp;
											<button class="btn" type="reset">
												<i class="ace-icon fa fa-undo bigger-110"></i>
												重置
											</button>
										</div>
									</div>
									

								

								</form>
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
	$('.btn-save').bind('click',function(){
		$.ajax({
			'url':'doxingyunhaoma.php?action=setxingyunhaomaconfig',
			'data':{'minnum':$('input[name=minnum]').val(),'maxnum':$('input[name=maxnum]').val()},
			'type':'post',
			'dataType':'json',
			'success':function(json){
				alert(json.message);
				return false;
			}
		});
	});
})

<?php echo '</script'; ?>
>
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
