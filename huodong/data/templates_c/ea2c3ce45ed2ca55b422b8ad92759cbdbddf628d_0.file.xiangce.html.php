<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:00:16
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/xiangce.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666277c0926a38_20649071',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'ea2c3ce45ed2ca55b422b8ad92759cbdbddf628d' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/xiangce.html',
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
function content_666277c0926a38_20649071 (Smarty_Internal_Template $_smarty_tpl) {
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

						<small>相册图片列表</small>
						<div class="nav-search pull-right" id="nav-search" style="position:relative;">
							<button class="btn btn-xs btn-primary btn-add-item"><i class="fa fa-plus"></i> 添加照片</button>
						</div>
					</h3>
						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
									<div class="row">
									<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['xiangce']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
									<div class="grid-item col-sm-3" id="item<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">
										<div class="widget-box widget-color-pink"  data="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">
											<div class="widget-header">
												<h5 class="widget-title">
													
												</h5>
											</div>
											<div class="widget-body">
												<div class="widget-main" style="height:310px;"><img src="<?php echo $_smarty_tpl->tpl_vars['item']->value['imagepath'];?>
" style="height:auto;width:auto;max-width:100%;max-height:300px;" /></div>
												<div class="widget-toolbox padding-8 clearfix">
												<span class="pull-right btn btn-sx btn-danger" onclick="deleteitem(<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
)">删除</span>
												</div>
											</div>
										</div>
									</div>
									
									
									
									<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
									</div>
									<div>
									<?php echo $_smarty_tpl->tpl_vars['pagehtml']->value;?>

									</div>
									<div id="addphoto-form" class="modal" tabindex="-1">
									<div class="modal-dialog">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal">&times;</button>
												<h4 class="blue bigger">添加一张照片</h4>
											</div>

											<div class="modal-body">
											<div class="row">
											<!-- #section:elements.tab -->
												
											<form id="savepicform" action="doxiangce.php?action=addphoto" method="post" enctype="multipart/form-data" >
												<input type="file" class="imageuploader" name="photo"/>
											</form>
											
										
											
											<!-- /section:elements.tab -->
											</div>
												
											</div>

											<div class="modal-footer">
												<input type="hidden" name="index" value="-1" />
												<button class="btn btn-sm" data-dismiss="modal">
													<i class="ace-icon fa fa-times"></i>
													取消
												</button>

												<button class="btn btn-sm btn-primary btn-save-item">
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

<?php echo '<script'; ?>
 type="text/javascript" src="assets/js/jquery.form.js"><?php echo '</script'; ?>
>
<!-- 写每个页面自定的js -->
<?php echo '<script'; ?>
 type="text/javascript">

jQuery(function($){
	//$('.'+$(this).attr('data')).masonry({});
	$('.btn-add-item').bind('click',function(){
		$('#addphoto-form').modal('show');
	});
	
	$('.imageuploader').ace_file_input({
		style: 'well',
		btn_choose: '点击此处选择图片',
		btn_change: null,
		no_icon: 'ace-icon fa fa-cloud-upload',
		droppable: true,
		maxSize: 4000000,
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
		//console.log($(this));
		//console.log($(this).data('ace_input_files'));
		//console.log($(this).data('ace_input_method'));
	});
	
	$('.btn-save-item').bind('click',function(){
		$('#savepicform').ajaxSubmit({
			dataType: 'json',
			success:function(json){
				$('input[name=pjlogopath]').val(json.filepath);
				alert(json.message);
				if(json.code>0){
					window.location.reload();
				}
			}
		});
		return false;
		
	});
});

//删除一个照片
function deleteitem(id){
	if(!confirm('确认要删除吗？'))return false;
	jQuery.ajax({
		"url":"doxiangce.php?action=deleteitem",
		"data":{'id':id},
		"type":"post",
		"dataType":"json",
		"success":function(json){
			if(json.code>0){
				jQuery('#item'+id).remove();
			}
		}
	});
}

<?php echo '</script'; ?>
>
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
