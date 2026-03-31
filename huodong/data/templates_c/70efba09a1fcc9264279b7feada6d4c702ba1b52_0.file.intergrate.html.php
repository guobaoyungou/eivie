<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:53:06
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/intergrate.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_66602772a8f1d4_32070805',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '70efba09a1fcc9264279b7feada6d4c702ba1b52' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/intergrate.html',
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
function content_66602772a8f1d4_32070805 (Smarty_Internal_Template $_smarty_tpl) {
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

						<small>此处对接公众号系统</small>
					</h3>
						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
								<div class="tabbable">
									<ul class="nav nav-tabs padding-12 tab-color-blue background-blue"
										id="messagelisttab">
										<li class="active"><a data-toggle="tab" href="#intergrate"  ret="0">对接设置</a>
										</li>
		
										<li><a data-toggle="tab" href="#wxpay"   ret="1">微信支付设置</a></li>
		
										
									</ul>

									<div class="tab-content">
										<div id="intergrate" class="tab-pane in active">
										<!-- 对接设置 -->
											<form class="form-horizontal" role="form">
												<!-- #section:elements.form -->
												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="form-field-1">使用默认公众号授权</label>
													<div class="col-sm-9">
														<label style="padding-top:9px">
															<input name="rentweixin" class="ace ace-switch ace-switch-3" type="checkbox" <?php if ($_smarty_tpl->tpl_vars['wall_config']->value['rentweixin'] == 2) {?>checked<?php }?> />
															<span class="lbl" onclick="switchrentweixin()"></span>
														</label>
													</div>
												</div>
												<div class="space-4"></div>
												<div id="intergratesettings" <?php if ($_smarty_tpl->tpl_vars['wall_config']->value['rentweixin'] == 2) {?>style="display:none;"<?php }?>>
												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="form-field-1">您公众号的APPID</label>
													<div class="col-sm-9">
														<input type="text" id="form-field-1" name="appid" placeholder="公众号的APPID" class="col-xs-10 col-sm-5" value="<?php echo $_smarty_tpl->tpl_vars['weixin_config']->value['appid'];?>
" />
														<span class="input-group-btn">
															<button type="button" class="btn btn-purple btn-sm " onclick="setappid()">
																修改
															</button>
														</span>
													</div>
												</div>
												<div class="space-4"></div>
												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" for="form-field-1">您公众号的APPSECRET</label>
													<div class="col-sm-9">
														<input type="text" id="form-field-1" name="appsecret" placeholder="公众号的APPSECRET" class="col-xs-10 col-sm-5" value="<?php echo $_smarty_tpl->tpl_vars['weixin_config']->value['appsecret'];?>
" />
														<span class="input-group-btn">
															<button type="button" class="btn btn-purple btn-sm " onclick="setappsecret()">
																修改
															</button>
														</span>
													</div>
												</div>
												<div class="form-group">
												<label class="col-sm-3 control-label no-padding-right" for="form-field-1"></label>
												<div class="col-sm-9">
												<div class="well">
														<h4 class="red smaller lighter">注意</h4>
														请在公众号后台=》公众号设置=》功能设置=》网页授权域名处 填写<span style="font-weight: bolder; color:blue;"><?php echo $_smarty_tpl->tpl_vars['domain']->value;?>
</span>，并下载txt文件上传到服务器网站根目录，如果是租用我们的系统，请把txt文件发给客服。
														<div class="space-4"></div>
														<h6 class="black smaller bolder">参考图片：</h6>
														<img src="assets/images/help.jpg" style=" width: 90%;"/>
														</div>
												</div>
												</div>
												</div>
											</form>
										</div>
										<div id="wxpay" class="tab-pane">
										<!-- 微信支付设置 -->
											<form class="form-horizontal" role="form">
												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" >商户号：</label>
													<div class="col-sm-9">
														<input type="text"  name="mchid" placeholder="商户ID" class="col-xs-10 col-sm-5" value="<?php echo $_smarty_tpl->tpl_vars['weixin_config']->value['mch_id'];?>
" />
													</div>
												</div>
												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" >API密钥：</label>
													<div class="col-sm-9">
														<input type="text"  name="mchsecret" placeholder="商户密钥" class="col-xs-10 col-sm-5" value="<?php echo $_smarty_tpl->tpl_vars['weixin_config']->value['mchsecret'];?>
" />
														
													</div>
												</div>
												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" >API证书(apiclient_cert.pem)：</label>
													<div class="col-sm-9">
														<textarea class="form-control" name="apiclient_cert"   rows="5" cols="" placeholder="用记事本打开apiclient_cert.pem,全选内容复制粘贴到此处"></textarea>
													</div>
												</div>
												<div class="form-group">
													<label class="col-sm-3 control-label no-padding-right" >API证书密钥(apiclient_key.pem)：</label>
													<div class="col-sm-9">
														<textarea class="form-control" name="apiclient_key"   rows="5" cols="" placeholder="用记事本打开apiclient_key.pem,全选内容复制粘贴到此处"></textarea>
													</div>
												</div>
												<div class="clearfix form-actions">
													<div class="col-md-offset-3 col-md-9">
														<button class="btn btn-info btn-save-wxpay" type="button">
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

jQuery(function($){
	$('.btn-save-wxpay').bind('click',function(){
		var mchid=$('input[name=mchid]').val();
		var mchsecret=$('input[name=mchsecret]').val();
		var apiclient_cert=$('textarea[name=apiclient_cert]').val();
		var apiclient_key=$('textarea[name=apiclient_key]').val();
		var rootca=$('textarea[name=rootca]').val();
		$.ajax({
			"url":"dointergrate.php?action=savewxpay",
			"data":{"mchid":mchid,"mchsecret":mchsecret,"apiclient_cert":apiclient_cert,"apiclient_key":apiclient_key,"rootca":rootca},
			"dataType":"json",
			"type":"post",
			"success":function(json){
				alert(json.message);
			}
		})
	});
});
function switchrentweixin(){
	var rentweixin=$('input[name=rentweixin]:checked').val()=='on'?'1':'2';
	$.ajax({
		'url':'dointergrate.php?action=switchrentweixin',
		'data':{'rentweixin':rentweixin},
		'type':'post',
		'dataType':'json',
		'success':function(json){
			window.location.reload();
			/*if(json.code>0){
				if(rentweixin==2){
					$('#intergratesettings').slideUp();
				}else{
					$('#intergratesettings').slideDown();
				}
			}*/
			
		}
	});
	
	//console.log('1');
}

function setappid(){
	var appid=$('input[name=appid]').val();
	$.ajax({
		'url':'dointergrate.php?action=setappid',
		'data':{'appid':appid},
		'type':'post',
		'dataType':'json',
		'success':function(json){
			alert(json.message);
			
		}
	});
}

function setappsecret(){
	var appsecret=$('input[name=appsecret]').val();
	$.ajax({
		'url':'dointergrate.php?action=setappsecret',
		'data':{'appsecret':appsecret},
		'type':'post',
		'dataType':'json',
		'success':function(json){
			alert(json.message);
		}
	});
}

<?php echo '</script'; ?>
>
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
