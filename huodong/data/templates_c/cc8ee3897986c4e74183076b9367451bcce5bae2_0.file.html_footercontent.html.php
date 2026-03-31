<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:51:57
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/html_footercontent.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_6660272dcf54b8_48019933',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'cc8ee3897986c4e74183076b9367451bcce5bae2' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/html_footercontent.html',
      1 => 1717500506,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6660272dcf54b8_48019933 (Smarty_Internal_Template $_smarty_tpl) {
?>			
			<div class="footer">
				<div class="footer-inner">
					<!-- #section:basics/footer -->
					<div class="footer-content">
						<span class="bigger-120">
							<span class="blue bolder">现场活动大屏幕系统</span>
							<a href="<?php echo $_smarty_tpl->tpl_vars['wall_config']->value['copyrightlink'];?>
"><?php echo $_smarty_tpl->tpl_vars['wall_config']->value['copyright'];?>
</a> &copy; 2016-2024
						</span>
						&nbsp; &nbsp;
						<span class="action-buttons">
							<a href="https://mp.weixin.qq.com/"  target="_blank">
								公众号平台
							</a>

							<a href="http://cli.im/" target="_blank">
								二维码生成工具
							</a>
						</span>
					</div>

					<!-- /section:basics/footer -->
				</div>
			</div>
			
			<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
			</a>
			<div id="editpasswordform" class="modal" tabindex="-1">
									<div class="modal-dialog">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal">&times;</button>
												<h4 class="blue bigger">修改管理员密码</h4>
											</div>

											<div class="modal-body">
											<div class="row">
											<!-- #section:elements.tab -->
											<div class="well">
											<div class="form-group">
													<label >原密码：</label>
													<div>
														<input type="password" name="oldpwd"   placeholder="请输入原密码" value="" style="width:80%;"/>
													</div>
												</div>
												<div class="form-group">
													<label >新密码：</label>
													<div>
														<input type="password" name="newpwd"   placeholder="请输入新密码" value="" style="width:80%;"/>
													</div>
												</div>
															<div class="form-group">
																<label >确认密码：</label>
																<div>
																	<input type="password" name="validpwd" placeholder="请输入再次输入新密码" value="" style="width:80%;"/>
																</div>
															</div>
											</div>
										<!-- /section:elements.tab -->
											</div>
												
											</div>

											<div class="modal-footer">
												<input type="hidden" name="index" value="-1" />
												<button class="btn btn-sm" data-dismiss="modal">
													<i class="ace-icon fa fa-times"></i>
													取消
												</button>

												<button class="btn btn-sm btn-primary btn-save-password">
													<i class="ace-icon fa fa-check"></i>
													保存
												</button>
											</div>
										</div>
									</div>
								</div>
		</div><!-- /.main-container -->

		<!-- basic scripts -->

		<!--[if !IE]> -->
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/components/jquery/dist/jquery.js"><?php echo '</script'; ?>
>

		<!-- <![endif]-->

		<!--[if IE]>
<?php echo '<script'; ?>
 src="assets/ace_v1.4/components/jquery.1x/dist/jquery.js"><?php echo '</script'; ?>
>
<![endif]-->
		<?php echo '<script'; ?>
 type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<?php echo '<script'; ?>
 src='assets/ace_v1.4/components/_mod/jquery.mobile.custom/jquery.mobile.custom.js'>"+"<"+"/script>");
		<?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/components/bootstrap/dist/js/bootstrap.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/components/mjolnic-bootstrap-colorpicker/dist/js/bootstrap-colorpicker.js"><?php echo '</script'; ?>
>
		
		<!-- page specific plugin scripts -->

		<!-- ace scripts -->
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/elements.scroller.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/elements.colorpicker.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/elements.fileinput.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/elements.typeahead.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/elements.wysiwyg.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/elements.spinner.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/elements.treeview.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/elements.wizard.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/elements.aside.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.basics.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.scrolltop.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.ajax-content.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.touch-drag.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.sidebar.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.sidebar-scroll-1.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.submenu-hover.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.widget-box.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.settings.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.settings-rtl.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.settings-skin.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.widget-on-reload.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="assets/ace_v1.4/assets/js/src/ace.searchbox-autocomplete.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 type="text/javascript">
		
		jQuery(function($){
			$('.openeditpasswordform').bind('click',function(){
				$('#editpasswordform').modal('show');
			});
			$('.btn-save-password').bind('click',function(){
				var oldpwd=$('input[name=oldpwd]').val();
				var newpwd=$('input[name=newpwd]').val();
				var validpwd=$('input[name=validpwd]').val();
				$.ajax({
					'url':'doindex.php?action=changepwd',
					'type':'post',
					'dataType':'json',
					'data':{'oldpwd':oldpwd,'newpwd':newpwd,'validpwd':validpwd},
					'success':function(json){
						alert(json.message);
						return false;
					}
				})
			})
		})
		
		<?php echo '</script'; ?>
>
<?php }
}
