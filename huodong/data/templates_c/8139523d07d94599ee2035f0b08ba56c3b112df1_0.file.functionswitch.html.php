<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:53:16
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/functionswitch.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_6660277cb18f53_69382609',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '8139523d07d94599ee2035f0b08ba56c3b112df1' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/functionswitch.html',
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
function content_6660277cb18f53_69382609 (Smarty_Internal_Template $_smarty_tpl) {
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
								<a href="#">系统设置</a>
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

						<small>功能开关</small>
					</h3>
						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
									<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['plugs']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
									<?php if ($_smarty_tpl->tpl_vars['item']->value['name'] == 'redpacket') {?>
									<?php if ($_smarty_tpl->tpl_vars['wall_config']->value['rentweixin'] == 1) {?>
										<div class="form-group">
										<div class="row">
											<label class="col-sm-3 control-label no-padding-right" for="form-field-1"><?php echo $_smarty_tpl->tpl_vars['item']->value['title'];?>
</label>
											<div class="col-sm-3">
												<label>
													<input name="<?php echo $_smarty_tpl->tpl_vars['item']->value['name'];?>
" class="ace ace-switch ace-switch-3" type="checkbox" <?php if ($_smarty_tpl->tpl_vars['item']->value['switch'] == 1) {?>checked<?php }?> />
									<span class="lbl" onclick="switchplug('<?php echo $_smarty_tpl->tpl_vars['item']->value['name'];?>
')"></span>
												</label>
											</div>
											<div class="col-sm-6">
												<div class="input-group" style="width:220px;">
													<span class="input-group-addon">
														快捷键：ctrl+
													</span>
													<input type="text" name="hotkey_<?php echo $_smarty_tpl->tpl_vars['item']->value['name'];?>
" class="form-control plugs_hotkey" value="<?php echo $_smarty_tpl->tpl_vars['item']->value['key'];?>
"/>
													<span class="input-group-btn">
														<button type="button" class="btn btn-purple btn-sm btn-set-hotkey">
															修改
														</button>
													</span>
												</div>
											</div>
										</div>
										</div>
										<?php }?>
										<?php } elseif ($_smarty_tpl->tpl_vars['item']->value['name'] != 'qdq') {?>
										<div class="form-group">
										<div class="row">
											<label class="col-sm-3 control-label no-padding-right" for="form-field-1"><?php echo $_smarty_tpl->tpl_vars['item']->value['title'];?>
</label>
											<div class="col-sm-3">
												<label>
													<input name="<?php echo $_smarty_tpl->tpl_vars['item']->value['name'];?>
" class="ace ace-switch ace-switch-3" type="checkbox" <?php if ($_smarty_tpl->tpl_vars['item']->value['switch'] == 1) {?>checked<?php }?> />
									<span class="lbl" onclick="switchplug('<?php echo $_smarty_tpl->tpl_vars['item']->value['name'];?>
')"></span>
												</label>
											</div>
											<div class="col-sm-3" >
													<?php if ($_smarty_tpl->tpl_vars['item']->value['name'] != 'choujiang') {?>
												<div class="input-group" style="width:220px;">
													<span class="input-group-addon">
														快捷键：ctrl+
													</span>
													<input type="text" name="hotkey_<?php echo $_smarty_tpl->tpl_vars['item']->value['name'];?>
" class="form-control plugs_hotkey" value="<?php echo $_smarty_tpl->tpl_vars['item']->value['key'];?>
"/>
													<span class="input-group-btn">
														<button type="button" class="btn btn-purple btn-sm btn-set-hotkey">
															修改
														</button>
													</span>
												</div>
												<?php }?>
											</div>
											</div>
										</div>
										<?php }?>
										<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
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

$(function(){
	$('.plugs_hotkey').on('keypress',function(){
		$(this).val('');
	});
	$('.btn-set-hotkey').on('click',function(){
		var input=$(this).parent().parent().find('input');
		var name=$(input).attr('name');
		var hotkey=$(input).val();
		$.ajax({
			"url":'dofunctionswitch.php?action=sethotkey',
			"data":{'plugname':name,'hotkey':hotkey},
			'type':'post',
			'dataType':'json',
			"success":function(json){
				alert(json.message);
			}
		});
	})
})
function switchplug(name){
	var switchstatus=$('input[name="'+name+'"]').get(0).checked?0:1;
	jQuery.ajax({
		'url':'doindex.php?action=switchplugs',
		'data':{'name':name,'switchstatus':switchstatus},
		'type':'post',
		'dataType':'json',
		'success':function(json){
			if(json.code<0){
				alert(json.message);
				return false;
			}
		}
	});
}

<?php echo '</script'; ?>
>
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
