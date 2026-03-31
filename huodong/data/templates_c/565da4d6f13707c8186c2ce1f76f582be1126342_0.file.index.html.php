<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:51:57
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/index.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_6660272dc93b04_48697336',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '565da4d6f13707c8186c2ce1f76f582be1126342' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/index.html',
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
function content_6660272dc93b04_48697336 (Smarty_Internal_Template $_smarty_tpl) {
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

						</ul><!-- /.breadcrumb -->
						<!-- /section:basics/content.searchbox -->
					</div>

					<!-- /section:basics/content.breadcrumbs -->
					<div class="page-content">
						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
									<div class="row">
										<div class="col-sm-6">
											<div class="row">
												<div class="col-sm-12">
													<div class="widget-box">
														<div class="widget-header">
															<h4 class="smaller">
																修改活动二维码
															</h4>
														</div>
														<div class="widget-body">
															<div class="widget-main">
											
																<form class="form-horizontal" method="post" action="dosystemsettings.php?action=seterweima"
																	role="form" enctype="multipart/form-data">
																	<div class="form-group">
																		<label class="col-sm-3 control-label no-padding-right" for="logo">活动二维码：</label>
																		<div class="col-sm-9">
																			<input multiple="" type="file" id="erweima" class="imageuploader" name="erweima" />
																			<div class="hr hr-12 dotted"></div>
																			<button type="submit" class="btn btn-sm btn-primary">保存</button>
																			<span class="alert-info">尺寸400*400像素,如果不使用公众号二维码,这里留空的就是默认的签到二维码
																				<a href="/wall/defaultqrcode.php?s=30&action=download" target="_blank"
																					style="color:red;"><i class="fa fa-download"></i>点此下载默认签到二维码<i
																						class="fa fa-qrcode"></i></a>
																			</span>
																		</div>
																	</div>
																</form>
											
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-sm-12">
													<div class="widget-box">
														<div class="widget-header">
															<h4 class="smaller">
																清空上墙系统数据
															</h4>
														</div>
														<div class="widget-body">
															<div class="widget-main">
																<div class="row">
																	<div class="col-sm-12">
																		<p>数据包括所有签到用户信息，发送的上墙消息，抽奖结果，摇一摇数据，投票数据，红包数据等</p>
																		<button class="btn btn-danger col-sm-12" onclick="cleardata();"><i
																				class="ace-icon fa fa-exclamation-triangle bigger-120"></i>点此清空所有上墙数据</button>
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="row">
												<div class="col-sm-12">
													<div class="widget-box">
														<div class="widget-header">
															<h4 class="smaller">
																各页面的入口地址
															</h4>
														</div>
														<div class="widget-body">
															<div class="widget-main">
																<div class="row">
																	<div class="col-sm-12">
																		<ul class="list-unstyled spaced">
																			<li>大屏幕地址：<?php echo $_smarty_tpl->tpl_vars['scheme']->value;?>
://<?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
/index.php</li>
																			<li>后台管理地址：<?php echo $_smarty_tpl->tpl_vars['scheme']->value;?>
://<?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
/myadmin/index.php</li>
																			<li>
																				<h4>手机端地址：</h4>
																				<div class="well">
																					<ul style="list-style: none;">
																						<li>签到地址（常用）：<span class="mobileurl"
																								style="color:red;"><?php echo $_smarty_tpl->tpl_vars['scheme']->value;?>
://<?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
/mobile/qiandao.php?vcode=<?php echo $_smarty_tpl->tpl_vars['wall_config']->value['verifycode'];?>
</span>
																						</li>
																						<li>上墙地址：<span
																								class="mobileurl"><?php echo $_smarty_tpl->tpl_vars['scheme']->value;?>
://<?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
/mobile/index.php?vcode=<?php echo $_smarty_tpl->tpl_vars['wall_config']->value['verifycode'];?>
</span>
																						</li>
																						<li>投票地址：<span
																								class="mobileurl"><?php echo $_smarty_tpl->tpl_vars['scheme']->value;?>
://<?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
/mobile/vote.php?vcode=<?php echo $_smarty_tpl->tpl_vars['wall_config']->value['verifycode'];?>
</span>
																						</li>
																						<li>游戏地址：<span
																								class="mobileurl"><?php echo $_smarty_tpl->tpl_vars['scheme']->value;?>
://<?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
/Modules/module.php?m=game&c=mobile&a=index&vcode=<?php echo $_smarty_tpl->tpl_vars['wall_config']->value['verifycode'];?>
</span>
																						</li>
																						<li>摇大奖地址：<span
																								class="mobileurl"><?php echo $_smarty_tpl->tpl_vars['scheme']->value;?>
://<?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
/Modules/module.php?m=ydj&c=mobile&a=index&vcode=<?php echo $_smarty_tpl->tpl_vars['wall_config']->value['verifycode'];?>
</span>
																						</li>
																						<li>红包地址：<span
																								class="mobileurl"><?php echo $_smarty_tpl->tpl_vars['scheme']->value;?>
://<?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
/mobile/redpacket.php?vcode=<?php echo $_smarty_tpl->tpl_vars['wall_config']->value['verifycode'];?>
</span>
																						</li>
																						<li>中奖结果地址：<span
																								class="mobileurl"><?php echo $_smarty_tpl->tpl_vars['scheme']->value;?>
://<?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
/mobile/cjresult.php?vcode=<?php echo $_smarty_tpl->tpl_vars['wall_config']->value['verifycode'];?>
</span>
																						</li>
																					</ul>
																				</div>
																			</li>
																		</ul>
																	</div>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-sm-12">
													<div class="widget-box">
														<div class="widget-header">
															<h4 class="smaller">
																开场密码
															</h4>
														</div>
														<div class="widget-body">
															<div class="widget-main">
																<div class="form-group">
																	<div class="input-group">
																		<span class="input-group-addon">
																			<i class="ace-icon fa fa-key"></i>
																		</span>
									
																		<input type="text" name="screenpaw" class="form-control" placeholder="输入新密码"
																			value="<?php echo $_smarty_tpl->tpl_vars['wall_config']->value['screenpaw'];?>
" />
																		<span class="input-group-btn">
																			<button type="button" class="btn btn-purple btn-sm btn-password">
																				修改
																			</button>
																		</span>
																	</div>
									
																</div>
															</div>
														</div>
													</div>
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

function resetmobileurl(){
	var domain='<?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
';
	//http://{$domain}/mobile/qiandao.php?vcode=
	jQuery.ajax({
		'url':'doindex.php?action=resetmobileurl',
		'type':'get',
		'dataType':'json',
		'success':function(json){
			if(json.code>0){
				var mobileurl_arr=$('.mobileurl');
				$(mobileurl_arr[0]).text('http://'+domain+'/mobile/qiandao.php?vcode='+json.vcode);
				$(mobileurl_arr[1]).text('http://'+domain+'/mobile/index.php?vcode='+json.vcode);
				$(mobileurl_arr[2]).text('http://'+domain+'/mobile/shake.php?vcode='+json.vcode);
				$(mobileurl_arr[3]).text('http://'+domain+'/mobile/vote.php?vcode='+json.vcode);
				$(mobileurl_arr[4]).text('http://'+domain+'/mobile/redpacket.php?vcode='+json.vcode);
				$(mobileurl_arr[5]).text('http://'+domain+'/mobile/cjresult.php?vcode='+json.vcode);
			}else{
				alert(json.message);
			}
		}
	})
}

function cleardata(){
	if(!confirm('确认要清空所有由粉丝产生的数据吗？')){return false;}
	jQuery.ajax({
		'url':'doindex.php?action=cleardata',
		'type':'post',
		'dataType':'json',
		'success':function(json){
			alert(json.message);
			return false;
			
		}
	});
}

jQuery(function($){
	$('.btn-password').bind('click',function(){
		var screenpaw=$('input[name=screenpaw]').val();
		$.ajax({
			'url':'doindex.php?action=changescreenpaw',
			'data':{'screenpaw':screenpaw},
			'type':'post',
			'dataType':'json',
			'success':function(json){
				alert(json.message);
				return false;
			}
		})
	});
	$('.imageuploader').ace_file_input({
		style: 'well',
		btn_choose: '点击此处选择图片',
		btn_change: null,
		no_icon: 'ace-icon fa fa-cloud-upload',
		droppable: true,
		maxSize: 550000,
		allowExt: ["jpeg", "jpg", "png", "gif"],
		allowMime: ["image/jpg", "image/jpeg", "image/png", "image/gif"],
		thumbnail: 'large',//large | fit
		previewHeight:200,
		preview_error : function(filename, error_code) {
		}

	}).on('change', function(){
	});
	
	

	
		<?php if ($_smarty_tpl->tpl_vars['weixin_config']->value['erweima'] != '') {?>
		
		$('#erweima')
		.ace_file_input('show_file_list', [
			{type: 'image', name: '活动二维码', path: '<?php echo $_smarty_tpl->tpl_vars['weixin_config']->value['erweima'];?>
'},
		]);
		
		<?php }?>
		
});



<?php echo '</script'; ?>
>
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
