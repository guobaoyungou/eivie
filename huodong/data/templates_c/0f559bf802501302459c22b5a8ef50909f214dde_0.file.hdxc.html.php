<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:55:03
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/hdxc.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666027e732de27_92608489',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '0f559bf802501302459c22b5a8ef50909f214dde' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/hdxc.html',
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
function content_666027e732de27_92608489 (Smarty_Internal_Template $_smarty_tpl) {
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

						<small>活动行程设置，上传活动行程图片</small>
					</h3>
						<div class="row">
								<?php echo $_smarty_tpl->tpl_vars['diyad']->value;?>

							<div class="col-sm-12 col-md-6">
								<!-- PAGE CONTENT BEGINS -->
									<form class="form-horizontal" method="post" action="dohdxc.php?action=setimage" role="form" enctype="multipart/form-data">
									
									<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right" >活动行程图：</label>
										<div class="col-sm-9">
											<input type="file" class="imageuploader"   name="imagepath"/>
											<div class="hr hr-12 dotted"></div>
											<button type="submit" class="btn btn-sm btn-primary">保存</button>
											<div class="space-4"></div>
											<div class="row">
										<span class="col-sm-12 alert-info well">设置手机屏幕大小<br/></span>
											</div>
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
	$('.imageuploader').ace_file_input({
		style: 'well',
		btn_choose: '点击此处选择图片',
		btn_change: null,
		no_icon: 'ace-icon fa fa-cloud-upload',
		droppable: true,
		maxSize: 5000000,
		//previewHeight:200,
		allowExt: ["jpeg", "jpg", "png", "gif"],
		allowMime: ["image/jpg", "image/jpeg", "image/png", "image/gif"],
		thumbnail: 'large'//large | fit
		//,icon_remove:null//set null, to hide remove/reset button
		/**,before_change:function(files, dropped) {
			//Check an example below
			//or examples/file-upload.html
			return true;
		}*/
		/**,before_remove : function() {
			return true;
		}*/
		,
		//previewSize:400,
		previewHeight:200,
		preview_error : function(filename, error_code) {
			//name of the file that failed
			//error_code values
			//1 = 'FILE_LOAD_FAILED',
			//2 = 'IMAGE_LOAD_FAILED',
			//3 = 'THUMBNAIL_FAILED'
			//alert(error_code);
		}

	}).on('change', function(){
		//console.log($(this).data('ace_input_files'));
		//console.log($(this).data('ace_input_method'));
	});
	

	
	<?php if ($_smarty_tpl->tpl_vars['hdxc_config']->value['image'] != '') {?>
	
	$('input[name=imagepath]')
	.ace_file_input('show_file_list', [
		{type: 'image', name: '活动行程', path: '<?php echo $_smarty_tpl->tpl_vars['hdxc_config']->value['image'];?>
'},
	]);
	
	<?php }?>
	
})

<?php echo '</script'; ?>
>
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
