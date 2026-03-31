<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:52:36
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/threedimensionalsign.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666027548b37e2_75429048',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '79187f24d01db5a3fcdc157de2a9cab3a316314d' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/threedimensionalsign.html',
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
function content_666027548b37e2_75429048 (Smarty_Internal_Template $_smarty_tpl) {
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

						<small>设置3D签到页面的拼装Logo</small>
					</h3>
						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
									
									<div class="row">
										<div class="col-xs-6 ">
											<div class="pagedata">
											</div>
											<div class="row">
												<div class="col-sm-9 col-sm-offset-3">
												<button type="button" class="btn btn-purple btn-sm btn-add-item" onclick="">
															添加一页
												</button>
												</div>
											</div>
												
										</div>
									<div class="col-xs-6">
										<form class="form-horizontal">
											<div class="form-group">
												<label class="col-sm-3 control-label no-padding-right" >头像数量</label>
												<div class="col-sm-9">
													<input type="text"  name="avatarnum" placeholder="使用的头像数量" class="col-xs-10 col-sm-5" value=<?php echo intval($_smarty_tpl->tpl_vars['threedimensional_config']->value['avatarnum']);?>
 />
													<span class="input-group-btn">
														<button type="button" class="btn btn-purple btn-sm " onclick="setavatarnum()">
															修改
														</button>
														
													</span>
													<p class="help-block">根据您的电脑性能设置使用的头像数量，如果太卡了就调小这个数值，一般情况默认即可</p>
												</div>
												
											</div>
										</form>
										
										<form class="form-horizontal">
											<div class="form-group">
												<label class="col-sm-3 control-label no-padding-right" >头像大小</label>
												<div class="col-sm-9">
													<input type="text"  name="avatarsize" placeholder="头像大小" class="col-xs-10 col-sm-5" value=<?php echo intval($_smarty_tpl->tpl_vars['threedimensional_config']->value['avatarsize']);?>
 />
													<span class="input-group-btn">
														<button type="button" class="btn btn-purple btn-sm " onclick="setavatarsize()">
															修改
														</button>
														<p class="help-block">根据您的电脑性能设置使用的头像大小，数字越小，头像越小</p>
													</span>
													
												</div>
												
											</div>
										</form>
										
										<form class="form-horizontal">
											<div class="form-group">
												<label class="col-sm-3 control-label no-padding-right" >头像间距</label>
												<div class="col-sm-9">
													<input type="text"  name="avatargap" placeholder="使用的头像间距" class="col-xs-10 col-sm-5" value=<?php echo intval($_smarty_tpl->tpl_vars['threedimensional_config']->value['avatargap']);?>
 />
													<span class="input-group-btn">
														<button type="button" class="btn btn-purple btn-sm " onclick="setavatargap()">
															修改
														</button>
														<p class="help-block">根据您的电脑性能设置使用的头像间距，数字月小，头像越密集，对电脑性能要求越高，一般情况默认即可</p>
													</span>
													
												</div>
												
											</div>
										</form>
										
									</div>
									
							</div>
							<div id="page-form" class="modal" tabindex="-1">
									<div class="modal-dialog">
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close" data-dismiss="modal">&times;</button>
												<h4 class="blue bigger">增加一个3D签到页</h4>
											</div>

											<div class="modal-body">
											<div class="row">
											<!-- #section:elements.tab -->
												<div class="tabbable">
													<ul class="nav nav-tabs" id="myTab">
														<li class="active">
															<a data-toggle="tab" href="#image" >
																自定义图形
															</a>
														</li>
														<li>
															<a data-toggle="tab" href="#text" >
																文字
															</a>
														</li>
														<li>
															<a data-toggle="tab" href="#shape" >
																预设图形
															</a>
														</li>
														<li>
															<a data-toggle="tab" href="#countdown" >
																倒计时
															</a>
														</li>
														
													</ul>
		
													<div class="tab-content">
														<div id="image" class="tab-pane fade in active">
															<form id="savepicform" action="dothreedimensionalsign.php?action=setlogo_pj" method="post" enctype="multipart/form-data" >
															<input type="file" class="imageuploader" name="logo_pj"/>
															<div class="hr hr-12 dotted"></div>
															<!-- <button type="submit" class="btn btn-sm btn-primary btn-save-pic">保存图片</button> -->
															</form>
															<input type="hidden" name="pjlogopath" value=''/>
														</div>
														<div id="text" class="tab-pane fade">
															<div class="form-group">
																	<label for="text3d">文字内容：</label>
																	<div>
																		<input type="text" id="text3d" placeholder="请输入您想组合的文字,4个字左右" value="" style="width:80%;"/>
																	</div>
																</div>
														</div>
														<div id="shape" class="tab-pane fade">
															<div class="form-group">
																<select class="chosen-select" id="shape3d" style="width:60%;">
																	<option value="#sphere">球形</option>
																	<option value="#torus">隧道</option>
																	<option value="#helix">螺旋</option>
																	<option value="#grid">网格</option>
																	<option value="#gene">基因</option>
																	<option value="#earth">地球</option>
																	<option value="#earth_1">地球(晚)</option>
																	<!-- <option value="#earth_2">地球(透明)</option> -->
																</select>
															</div>
														</div>
														<div id="countdown" class="tab-pane fade">
															<div class="form-group">
																	<label for="countdown3d">倒计时：</label>
																	<div>
																		<input type="text" id="countdown3d" placeholder="请输入您的倒计时时间，单位秒" value="" style="width:80%;"/>
																	</div>
																</div>
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
echo '<script'; ?>
 type="text/javascript" src="assets/js/jquery.form.js"><?php echo '</script'; ?>
>
<!-- 写每个页面自定的js -->
<?php echo '<script'; ?>
 type="text/javascript">
$datastr='<?php echo $_smarty_tpl->tpl_vars['threedimensional_config']->value['datastr'];?>
';

function setavatarsize(){
	var avatarsize=$('input[name=avatarsize]').val();
	$.ajax({
		'url':'dothreedimensionalsign.php?action=setavatarsize',
		'type':'post',
		'data':{'avatarsize':avatarsize},
		'dataType':'json',
		'success':function(json){
			//if(json.code<0){
				alert(json.message);
				return false;
			//}
		}
	})
}
function setavatargap(){
	var avatargap=$('input[name=avatargap]').val();
	$.ajax({
		'url':'dothreedimensionalsign.php?action=setavatargap',
		'type':'post',
		'data':{'avatargap':avatargap},
		'dataType':'json',
		'success':function(json){
			//if(json.code<0){
				alert(json.message);
				return false;
			//}
		}
	})
}
//设置使用的头像数量，头像数量如果太多浏览器可能会卡，具体设置请按照自己电脑配置决定
function setavatarnum(){
	var avatarnum=$('input[name=avatarnum]').val();
	$.ajax({
		'url':'dothreedimensionalsign.php?action=setavatarnum',
		'type':'post',
		'data':{'avatarnum':avatarnum},
		'dataType':'json',
		'success':function(json){
			//if(json.code<0){
				alert(json.message);
				return false;
			//}
		}
	})
}
jQuery(function($){
	pagetable.initpagetable();
	//$('.btn-delete-item').remove($(this).parent());
	//$('.pagedata')
	
	$('.imageuploader').ace_file_input({
		style: 'well',
		btn_choose: '点击此处选择图片',
		btn_change: null,
		no_icon: 'ace-icon fa fa-cloud-upload',
		droppable: true,
		maxSize: 550000,
		//previewHeight:200,
		allowExt: ["jpeg", "jpg", "png", "gif"],
		allowMime: ["image/jpg", "image/jpeg", "image/png", "image/gif"],
		thumbnail: 'large'//large | fit
		,
		previewHeight:200,
		preview_error : function(filename, error_code) {
		}

	}).on('change', function(){
		
	});
	$('.btn-save-item').bind('click',function(){
		var type=$('#myTab').find('.active a').attr('href');
		var content='';
		if(type=='#image'){
			$('#savepicform').ajaxSubmit({
				dataType: 'json',
				success:function(json){
					if(json.code>0){
						$('input[name=pjlogopath]').val(json.filepath);
						content='#icon '+$('input[name=pjlogopath]').val();
						savepagetable(content);
						$('#page-form').modal('hide');
					  return false;
					}else{
						alert(json.message);
						return false;
					}
				}
			});	
			return false;
		}
		if(type=='#text'){
			content=$('#text3d').val();
		}
		if(type=='#shape'){
			content=$('#shape3d').val();
		}
		if(type=='#countdown'){
			content='#countdown '+$('#countdown3d').val();
		}
		savepagetable(content);
		$('#page-form').modal('hide');
		console.log(pagetable.data_arr);
	});

	function savepagetable(content){
		var index=$('input[name=index]').val();
		pagetable.datastring2dataarray();
		if(index==-1){
			pagetable.data_arr.push(content);
		}else{
			pagetable.data_arr.insert(index,content);
		}
		pagetable.dataarray2datastring();
		pagetable.savedata();
	}
});
String.prototype.replaceAll = function(s1,s2){ 
	return this.replace(new RegExp(s1,"gm"),s2); 
}
Array.prototype.insert = function (index, item) {  
	this.splice(index, 0, item);  
};
var pagetable={
	'data':	'<?php echo $_smarty_tpl->tpl_vars['threedimensional_config']->value['datastr'];?>
',
	'data_arr':[],
	'content':{'#icon':'<img src="{{url}}"/>','#sphere':'球形','#torus':'隧道','#helix':'螺旋','#grid':"网格",'#gene':"基因",'#earth':"地球",'#earth_1':"地球(晚)",'#earth_2':"地球(透明)"},
	'rowtemplate':'<div class="row dataitem" data="{{str}}" id="item-{{index}}" item-index="{{index}}"><div class="col-sm-9" >{{content}}</div><div class="col-sm-3"><button class="btn btn-xs btn-primary btn-insert-item">插入</button> <button class="btn btn-xs btn-danger btn-delete-item">删除</button></div></div><div class="hr hr-16 hr-dotted"></div>',
	'init':function(){
		this.datastring2dataarray();
		$('.pagedata').html('');
	},
	'drawtable':function(){
		var html='';
		var len=this.data_arr.length;
		console.log(this.data_arr);
		if(this.data_arr[0]==''){
			return;
		}
		for(var i=0;i<len;i++){
			var row=this.rowtemplate;
			row=row.replaceAll('{{index}}',i);
			row=row.replace('{{str}}',this.data_arr[i]);
			row=row.replace('{{content}}',this.setitemcontent(this.data_arr[i]));
			$(row).appendTo('.pagedata');
		}
	},
	'setitemcontent':function(data){
		var content=this.content[data]!=undefined?this.content[data]:'';
		if(content==''){
			if(data.indexOf('#icon ')!=-1){
				var tempcontent=data.replace('#icon','');
				tempcontent=tempcontent.replaceAll(' ','');
				content='<img src="'+tempcontent+'" style="width: auto;height: 50px;"/>';
			}
			if(data.indexOf('#countdown ')!=-1){
				var tempcontent=data.replace('#countdown','');
				tempcontent=tempcontent.replaceAll(' ','');
				content='倒计时  '+tempcontent+'秒';
			}
		}
		if(content==''){
			content=data;
		}
		return content;
	},
	'bindevent':function(){
		var self=this;
		$('.pagedata .btn-insert-item').unbind('click');
		$('.pagedata .btn-delete-item').unbind('click');
		
		$('.btn-add-item').unbind('click');
		
		$('.pagedata .btn-delete-item').bind('click',function(){
			var data=$(this).parent().parent().attr('data');
			var index=$(this).parent().parent().attr('item-index');
			self.data_arr.splice(index,1);
			self.dataarray2datastring();
			self.savedata();
		});
		//绑定插入按钮
		$('.pagedata .btn-insert-item').bind('click',function(){
			var index=$(this).parent().parent().attr('item-index');
			$('input[name=pjlogopath]').val('');
			$('input[name=index]').val(index);
			$('#page-form').modal('show');
		});
		//绑定添加一页按钮
		$('.btn-add-item').bind('click',function(){
			$('input[name=pjlogopath]').val('');
			$('input[name=index]').val('-1');
			$('#page-form').modal('show');
		});
	},
	'dataarray2datastring':function(){
		this.data=this.data_arr.join('|');
	},
	'datastring2dataarray':function(){
		if(this.data!='')
			this.data_arr=this.data.split('|');
	},
	'initpagetable':function(){
		this.init();
		this.drawtable();
		this.bindevent();
		
	},
	'savedata':function(){
		var self=this;
		$.ajax({
			'url':'dothreedimensionalsign.php?action=setpages',
			'type':'post',
			'dataType':'json',
			'data':{'datastr':this.data},
			'success':function(json){
				if(json.code>0){
					self.initpagetable();
				}
				else{
					alert(json.message);
					return false;
				}
			}
		});
	}
};


<?php echo '</script'; ?>
>
<?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
