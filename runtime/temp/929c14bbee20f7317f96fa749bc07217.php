<?php /*a:3:{s:52:"/www/wwwroot/eivie/app/view/member_poster/index.html";i:1755845277;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;}*/ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title></title>
  <meta name="renderer" content="webkit">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
  <link rel="stylesheet" type="text/css" href="/static/admin/layui/css/layui.css?v=20200519" media="all">
<link rel="stylesheet" type="text/css" href="/static/admin/layui/css/modules/formSelects-v4.css?v=20200516" media="all">
<link rel="stylesheet" type="text/css" href="/static/admin/css/admin.css?v=202409" media="all">
<link rel="stylesheet" type="text/css" href="/static/admin/css/font-awesome.min.css?v=20200516" media="all">
<link rel="stylesheet" type="text/css" href="/static/admin/webuploader/webuploader.css?v=<?php echo time(); ?>" media="all">
<link rel="stylesheet" type="text/css" href="/static/admin/css/designer.css?v=202410" media="all">
<link rel="stylesheet" type="text/css" href="/static/fonts/iconfont.css?v=20201218" media="all">
	<link rel="stylesheet" href="/static/admin/poster/jquery.contextMenu.css"/>
	<style type='text/css'>
	#editor-content{ height:602px;}
	ul.right-menu{
		list-style:none;
		margin: 0 0 20px 0;
		padding: 0px;
		width: 100%;
		float:left;
	}
	ul.right-menu li{
		cursor:pointer;
		float:left;
		margin: 2px 5px;
		padding: 10px 18px;
		color:#428bca;
		font-size:14px;
	}
	ul.right-menu li.active{
		background-color: #428BCA;
		color: #fff;
	}

	#poster{width:344px;height:600px;border:0px solid #ccc;position:relative;overflow:hidden}
	#poster::-webkit-scrollbar { /*滚动条整体样式*/
		width: 2px;     /*高宽分别对应横竖滚动条的尺寸*/
		height: 0px;
	}
	#poster::-webkit-scrollbar-thumb { /*滚动条里面小方块*/
		border-radius: 1px;
		-webkit-box-shadow: inset 0 0 1px rgba(0,0,0,0.5);
		background: rgba(0,0,0,0.5);
	}
	#poster::-webkit-scrollbar-track { /*滚动条里面轨道*/
		-webkit-box-shadow: inset 0 0 0px rgba(0,0,0,0.2);
		border-radius: 0;
		background: rgba(0,0,0,0);
	}
	#poster .bg{position:absolute;width:100%;z-index:0;}
	#poster .drag[type=img] img,#poster .drag[type=thumb] img {width:100%;height:100%; }

	#poster .drag {position: absolute; width:80px;height:80px; border:1px solid rgba(0,0,0,0.5); }
		 
	#poster .drag[type=nickname]{ width:80px;height:40px; font-size:14px; line-height:14px; font-family: 黑体;}
	#poster .drag[type=text]{ width:auto;height:auto; font-size:14px; line-height:14px; font-family: 黑体;}
	#poster .drag[type=textarea]{overflow:hidden;font-size:14px; line-height:14px; font-family: 黑体;}
	#poster .drag[type=pro_img]{ background:#eee;overflow:hidden}
	#poster .drag[type=reginvitecode]{ width:auto;height:auto; font-size:14px; line-height:14px;font-family: 黑体;}
	#poster .drag img { position:absolute;z-index:0;width:100%; }
	#poster .drag .shadow {width:100%;height:100%}

	#poster .rRightDown,.rLeftDown,.rLeftUp,.rRightUp,.rRight,.rLeft,.rUp,.rDown{position:absolute;width:7px;height:7px;z-index:1;font-size:0;}    
	#poster .rRightDown,.rLeftDown,.rLeftUp,.rRightUp,.rRight,.rLeft,.rUp,.rDown{background:rgba(200,0,0,0.5);}
	.rLeftDown,.rRightUp{ cursor:ne-resize;}
	.rRightDown,.rLeftUp{ cursor:nw-resize;}
	.rRight,.rLeft{ cursor:e-resize;}
	.rUp,.rDown{ cursor:n-resize;}
	.rLeftDown{ left:-4px;bottom:-4px;}
	.rRightUp{ right:-4px;top:-4px;}
	.rRightDown{ right:-4px;bottom:-4px;}
	.rRightDown{ background-color:#00F;}   
				 
	.rLeftUp{ left:-4px;top:-4px;}
	.rRight{ right:-4px;top:50%;margin-top:-4px;}
	.rLeft{ left:-4px;top:50%;margin-top:-4px;}
	.rUp{ top:-4px;left:50%;margin-left:-4px;}
	.rDown{ bottom:-4px;left:50%;margin-left:-4px;}
	.context-menu-layer { z-index:9999;}
	.context-menu-list { z-index:9999;}
	</style>
</head>
<body>
	<div class="layui-fluid">
		<div class="layui-row layui-col-space15">
			<div class="layui-card layui-col-md12">
			<div class="layui-card-header"><i class="fa fa-cog"></i> 分享海报设置</div>
			<div class="layui-card-body" pad15>
				<?php if(count($platform) >1): ?>
				<div class="layui-tab layui-tab-brief">
					<ul class="layui-tab-title">
						<?php foreach($platform as $pn): ?>
						<li <?php if($type==$pn): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url(); ?>/type/<?php echo $pn; ?>'"><?php echo getplatformname($pn); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php endif; ?>
				<div class="layui-col-md12" style="margin-top:10px;margin-left:20px">
					<?php foreach($posterlist as $k=>$v): ?>
					<a href="<?php echo url(); ?>/type/<?php echo $type; ?>/id/<?php echo $v['id']; ?>" class='layui-btn layui-btn-sm <?php if($posterid != $v['id']): ?>layui-btn-primary<?php endif; ?>' style="margin-bottom:5px">海报<?php echo $k+1; ?></a>
					<?php endforeach; ?>
					<a href="<?php echo url(); ?>/type/<?php echo $type; ?>/op/add" class='layui-btn layui-btn-sm layui-btn-primary' style="margin-bottom:5px">增加+</a>
				</div>
				<table style='width:100%;margin-top:20px'>
					<tr>
						<td style='width:340px;padding:10px;' valign='top'>

						<div class="dsn-phone " style="float:right" >
							<div class="dsn-phone-left"></div>
							<div class="dsn-phone-center">
								<div class="dsn-phone-top"></div>
								<div class="dsn-phone-main">
									<div id="editor">
										<div class="dsn-mod dsn-topbar dsn-mod-nohover" style="color:#fff;background:#000;height:30px">
											<div style="float:left;width:100%;font-size:12px">
												<div style="float:left;width:30%">&nbsp;<i class="fa fa-signal"></i> wechat <i class="fa fa-wifi"></i></div>
												<div style="float:left;text-align:center;width:40%">12:00</div>
												<div style="float:left;text-align:right;width:30%">100% <i class="fa fa-battery-full"></i>&nbsp;</div>
											</div>
											<div style="float:left;width:98%;margin:2px 1% 0 1%;display:none">
												<div style="float:left;width:30%">&nbsp;</div>
												<div style="float:left;text-align:center;width:40%;font-size:16px;height:27px;line-height:27px">首页海报</div>
												<div style="float:right;width:30%;border-radius:20px;width:70px;height: 25px;border:1px solid rgba(255,255,255,0.2);text;text-align:center;overflow:hidden" ng-style="{'border-color':navigationBarTextStyle=='black'?'rgba(0,0,0,0.2)':'rgba(255,255,255,0.2)'}">
													<div style="float:left;width:49%;font-size:17px;height:27px;line-height:27px">
														<div style="float:left;margin-left:2px">&nbsp;&bull;</div>
														<div style="float:left;font-size:27px">&bull;</div>
														<div style="float:left;">&bull;</div>
													</div>
													<div style="float:left;width:2%;"><span style="border-right:1px solid;opacity:0.2;"></span></div>
													<div style="float:right;width:49%;"><i class="fa fa-dot-circle-o" style="font-size:19px;height:25px;line-height:25px"></i></div>
												</div>
											</div>
										</div>
										<div id="editor-content">

											<div id='poster'>
												<?php if(!empty($poster_bg)): ?>
												<img src="<?php echo $poster_bg; ?>" class='bg'/>
												<?php endif; if(!empty($poster_data)): foreach($poster_data as $key=>$d): ?>
												<div class="drag" type="<?php echo $d['type']; ?>" index="<?php echo $key+1; ?>" style="zindex:<?php echo $key+1; ?>;left:<?php echo $d['left']; ?>;top:<?php echo $d['top']; ?>;<?php if($d['type']!='text'): ?>width:<?php echo $d['width']; ?>;height:<?php echo $d['height']; ?><?php endif; ?>" src="<?php echo $d['src']; ?>" size="<?php echo $d['size']; ?>" color="<?php echo $d['color']; ?>" content="<?php echo $d['content']; ?>">
													<?php if($d['type']=='qrwx'): ?>
														<img src="/static/admin/poster/qrwx.jpg" />
													<?php elseif($d['type']=='qrbaidu'): ?>
														<img src="/static/admin/poster/qrwx.jpg" />
													<?php elseif($d['type']=='qrtoutiao'): ?>
														<img src="/static/admin/poster/qrwx.jpg" />
													<?php elseif($d['type']=='qralipay'): ?>
														<img src="/static/admin/poster/qralipay.jpg" />
													<?php elseif($d['type']=='qrwxreg'): ?>
														<img src="/static/admin/poster/qrwx.jpg" />
													<?php elseif($d['type']=='qrmp'): ?>
														<img src="/static/admin/poster/qrmp.jpg" />
													<?php elseif($d['type']=='qrgz'): ?>
														<img src="/static/admin/poster/qrmp.jpg" />
													<?php elseif($d['type']=='head'): ?>
														<img src="/static/admin/poster/default-avatar.png" radius="<?php echo $d['radius']; ?>" style="border-radius:<?php echo $d['radius']/2; ?>%"/>
													<?php elseif($d['type']=='nickname'): ?>
														<div class="text" style="font-size:<?php echo $d['size']; ?>;line-height:<?php echo $d['size']; ?>;color:<?php echo $d['color']; ?>">[昵称]</div>
													<?php elseif($d['type']=='text'): ?>
														<div class="text" style="font-size:<?php echo $d['size']; ?>;line-height:<?php echo $d['size']; ?>;color:<?php echo $d['color']; ?>"><?php echo $d['content']; ?></div>
													<?php elseif($d['type']=='textarea'): ?>
														<div class="textarea" style="font-size:<?php echo $d['size']; ?>;line-height:<?php echo $d['size']; ?>;color:<?php echo $d['color']; ?>"><?php echo $d['content']; ?></div>
													<?php elseif($d['type']=='img'): ?>
														<img src="<?php echo $d['src']; ?>" />
													<?php elseif($d['type']=='pro_img'): ?>
														<img src="/static/admin/poster/product.jpg" />
													<?php elseif($d['type']=='reginvitecode'): ?>
														<div class="reginvitecode" style="font-size:<?php echo $d['size']; ?>;line-height:<?php echo $d['size']; ?>;color:<?php echo $d['color']; ?>;">[邀请码]</div>
													<?php endif; ?>
													<div class="rRightDown"> </div><div class="rLeftDown"> </div><div class="rRightUp"> </div><div class="rLeftUp"> </div><div class="rRight"> </div><div class="rLeft"> </div><div class="rUp"> </div><div class="rDown"></div>
												</div>
												<?php endforeach; ?>
												<?php endif; ?>
											</div>

										</div>
									</div>
								</div>
								<div class="dsn-phone-bottom"></div>
							</div>
							<div class="dsn-phone-right"></div>
						</div>

						</td>
						<td valign='top' style='padding:10px;padding-top:150px'>
								<form class="layui-form" lay-filter="">
									<div class="layui-form-item">
										<label class="layui-form-label">背景图片</label>
										<input type="hidden" id="poster_bg" class="layui-input" value="<?php echo $poster_bg; ?>">
										<button style="float:left;" type="button" class="layui-btn layui-btn-primary" onclick="setpic('pic_poster_bg','poster_bg')">选择或上传</button>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">建议尺寸：宽680像素，高1300像素</div>
									</div>

									<div class="layui-form-item">
										<label class="layui-form-label">海报元素</label>
										<div class="layui-input-block">
											 <button class='layui-btn layui-btn-primary btn-com' type='button' data-type='head' style="margin-bottom:5px">头像</button>
											 <button class='layui-btn layui-btn-primary btn-com' type='button' data-type='text' style="margin-bottom:5px">文字</button>
											 <button class='layui-btn layui-btn-primary btn-com' type='button' data-type='img' style="margin-bottom:5px">图片</button>
											 <button class='layui-btn layui-btn-primary btn-com' type='button' data-type='shadow' style="margin-bottom:5px">阴影区</button>
											 <button class='layui-btn layui-btn-primary btn-com' type='button' data-type='textarea' style="margin-bottom:5px">文本块</button>
											 
										</div>
									</div>

									<div class="layui-form-item">
										<label class="layui-form-label">二维码元素</label>
										<div class="layui-input-block">
											<button class='layui-btn layui-btn-primary btn-com' type='button' data-type='qrmp' style="margin-bottom:5px">网页二维码</button>
											<?php if(in_array('mp',$platform)): ?>
											<button class='layui-btn layui-btn-primary btn-com' type='button' data-type='qrgz' style="margin-bottom:5px">关注二维码</button>
											<?php endif; if(in_array('wx',$platform)): ?>
											<button class='layui-btn layui-btn-primary btn-com' type='button' data-type='qrwx' style="margin-bottom:5px">微信小程序码</button>

											

											<?php endif; ?>
											
											<!-- <?php if(in_array('baidu',$platform)): ?>
											<button class='layui-btn layui-btn-primary btn-com' type='button' data-type='qrbaidu' style="margin-bottom:5px">百度小程序码</button>
											<?php endif; if(in_array('toutiao',$platform)): ?>
											<button class='layui-btn layui-btn-primary btn-com' type='button' data-type='qrtoutiao' style="margin-bottom:5px">头条小程序码</button>
											<?php endif; ?> -->
										</div>
									</div>

									<div id='headset' style='display:none'>
										<div class="layui-form-item">
											<label class="layui-form-label">圆角度</label>
											<div class="layui-input-inline" style="width:200px">
												<div id="headslider" style="margin-top:18px;margin-left:5px"></div>
											</div>
											<div class="layui-form-mid" style="margin-left:10px;">0表示方形,100表示圆形</div>
										</div>
									</div>

									<div id='pro_imgset' style='display:none'>
										<!-- <div class="layui-form-item">
											<label class="layui-form-label">是否显示下方阴影区域</label>
											<div class="layui-input-inline">
												<select id='pro_imgshowyy' lay-ignore style="width:120px;;line-height:38px;height: 38px;border: 1px solid #e6e6e6;border-radius: 2px;">
													<option value='1'>显示</option>
													<option value='0'>不显示</option>
												</select>
											</div>
										</div> -->
									</div>
									<div id='textset' style='display:none'>
										<div class="layui-form-item">
											<label class="layui-form-label">文字内容</label>
											<div class="layui-input-inline" style="width:400px">
												<input type="text" value="" id="textcontent" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux" style="clear:both;margin-left:110px;">[昵称]、[姓名]、[手机号]、[商城名称]会自动替换为当前用户的昵称、姓名、手机号 和 商城名称</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label">文字颜色</label>
											<div class="layui-input-inline">
												<input class="layui-input" id="textcolor" type="text" value="">
											</div>
											<div class="_colorpicker"></div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label">文字大小</label>
											<div class="layui-input-inline">
												<input type="text" id="textsize" class="layui-input" placeholder="例如: 14,16"/>
											</div>
											<div class="layui-form-mid" style="margin-left:10px;">px</div>
										</div>
									</div>
									
									<div id='textareaset' style='display:none'>
										<div class="layui-form-item">
											<label class="layui-form-label">文本内容</label>
											<div class="layui-input-inline" style="width:400px">
												<input type="text" value="" id="textareacontent" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux" style="clear:both;margin-left:110px;">[昵称]、[姓名]、[手机号]、[商城名称]会自动替换为当前用户的昵称、姓名、手机号 和 商城名称</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label">文本颜色</label>
											<div class="layui-input-inline">
												<input class="layui-input" id="textareacolor" type="text" value="">
											</div>
											<div class="_colorpicker"></div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label">文本大小</label>
											<div class="layui-input-inline">
												<input type="text" id="textareasize" class="layui-input" placeholder="例如: 14,16"/>
											</div>
											<div class="layui-form-mid" style="margin-left:10px;">px</div>
										</div>
									</div>
									<div id="shadowset" style="display:none">
										<div class="layui-form-item">
											<label class="layui-form-label">颜色及透明度</label>
											<div class="layui-input-inline" style="width:200px">
												<input type="text" id="shadowcolor" value="rgba(0,0,0,0.5)" class="layui-input" />
											</div>
											<div id="shadowcolorpicker"></div>
										</div>
									</div>
									<div id='imgset' style='display:none'>
										<div class="layui-form-item">
											<label class="layui-form-label">图片：</label>
											<input type="hidden" id="imgurl" value="">
											<button style="float:left;" type="button" class="layui-btn layui-btn-primary" onclick="setpic2('img_pic','imgurl')">上传图片</button>
											<div style="float:left;padding-top:10px;margin-left:110px;clear: both;">
												<div class="layui-imgbox" style="width:100px;"><div class="layui-imgbox-img"><img id="img_pic" src=""/></div></div>
											</div>
										</div>
									</div>

									<div class="layui-form-item" style="margin-top:100px">
										<label class="layui-form-label">规则说明</label>
										<div class="layui-input-inline" style="width:500px">
											<textarea class="layui-textarea" name="guize" style="height:150px"><?php echo $guize; ?></textarea>
										</div>
									</div>

									<div class="layui-form-item" style="margin-top:50px;display:none">
										<label class="layui-form-label">清除之前<br>海报缓存</label>
										<div class="layui-input-inline">
											<input type="radio" name="clearhistory" title="是" value="1" checked/>
											<input type="radio" name="clearhistory" title="否" value="0"/>
										</div>
									</div>
									<div class="layui-form-item">
										<div class="layui-input-block" style="margin-left:110px;">
											<button class="layui-btn layui-btn-normal layui-btn-lg" lay-submit lay-filter="formsubmit">保 存</button>
											<?php if($posterid != $posterlist[0]['id']): ?>
											<button class="layui-btn layui-btn-primary layui-btn-lg" type="button" onclick="delposter()">删 除</button>
											<?php endif; ?>
										</div>
									</div>
								</form>

							</td>
					</tr>
				</table>
				<input type="hidden" name="poster_data" value="" />
			</div>
			</div>
		</div>
	</div>
	
	<script type="text/javascript" src="/static/admin/layui/layui.all.js?v=20210226"></script>
<script type="text/javascript" src="/static/admin/layui/lay/modules/flow.js?v=1"></script>
<script type="text/javascript" src="/static/admin/layui/lay/modules/formSelects-v4.js"></script>
<script type="text/javascript" src="/static/admin/js/jquery-ui.min.js?v=20200228"></script>
<script type="text/javascript" src="/static/admin/ueditor/ueditor.js?v=20220707"></script>
<script type="text/javascript" src="/static/admin/ueditor/135editor.js?v=20200228"></script>
<script type="text/javascript" src="/static/admin/webuploader/webuploader.js?v=2024"></script>
<script type="text/javascript" src="/static/admin/js/qrcode.min.js?v=20200228"></script>
<script type="text/javascript" src="/static/admin/js/dianda.js?v=2022"></script>
<script type="text/javascript" src="/static/admin/js/inputTags.js?v=2026"></script>

<div id="NewsToolBox"></div>
<script type="text/javascript">
    // 解释文字浮层展示
    $('.layui-text-popover').mouseenter(function(){
        let pageHeight = $(window).height() + $(document).scrollTop();
        let bottom = pageHeight - $(this).offset().top
        let topNum = ($(this).offset().top - $(document).scrollTop()).toFixed(2);
        let Height = $(this).find('.layui-textpopover-div').outerHeight();
        let Width = $(this).find('.layui-textpopover-div').outerWidth();
        let leftNum = ($(window).width() - $(this).offset().left).toFixed(2);
        $(this).find('.layui-textpopover-div').show();
        let that = this;
        setTimeout(function(){
            if(topNum < (Height/2-15)){
                $(that).find('.layui-textpopover-div').css({'top':-topNum+10+'px','left':'0'})
            }else if(bottom < (Height/2-15)){
                $(that).find('.layui-textpopover-div').css({'top': bottom-Height-10 +'px','left':'0'})
            }else{
                $(that).find('.layui-textpopover-div').css({'top':-(Height/2-15)+'px','left':'0'})
            }
            if(Width > leftNum){
                $(that).find('.layui-textpopover-div').css({'right':'26px','top':'0','left':''})
            }
        },50)
        setTimeout(function(){
            $(that).find('.layui-textpopover-div').css({'opacity':1})
        },100)
    })
    $('.layui-text-popover').mouseleave(function(){
        $(this).find('.layui-textpopover-div').css({'opacity':0})
        $(this).find('.layui-textpopover-div').hide()
    })
    $('.layui-textpopover-div').mouseenter(function(){
        $(this).find('.layui-textpopover-div').show()
    })
    $('.layui-textpopover-div').mouseleave(function(){
        $(this).find('.layui-textpopover-div').css({'opacity':0})
        $(this).find('.layui-textpopover-div').hide()
    })
    // 图片浮层展示 示例
    $('.layui-popover').mouseenter(function(){
        let pageHeight = $(window).height() + $(document).scrollTop();
        let bottom = pageHeight - $(this).offset().top
        let topNum = ($(this).offset().top - $(document).scrollTop()).toFixed(2);
        let Height = $(this).find('.layui-popover-div').outerHeight();
        $(this).find('.layui-popover-div').show()
        let that = this;
        setTimeout(function(){
            if(topNum < (Height/2-15)){
                $(that).find('.layui-popover-div').css({'top':-topNum+10+'px','opacity':1,'transition':'opacity .3s'})
            }else if(bottom < (Height/2-15)){
                $(that).find('.layui-popover-div').css({'top': bottom-Height-10 +'px','opacity':1,'transition':'opacity .3s'})
            }else{
                $(that).find('.layui-popover-div').css({'top':-(Height/2-15)+'px','opacity':1,'transition':'opacity .3s'})
            }
        },100)
    })
    $('.layui-popover').mouseleave(function(){
        $(this).find('.layui-popover-div').css({'opacity':0})
        $(this).find('.layui-popover-div').hide()
    })
    function copyText(text) {
        var top = document.documentElement.scrollTop;
        var textarea = document.createElement("textarea"); //创建input对象
        var currentFocus = document.activeElement; //当前获得焦点的元素
        var toolBoxwrap = document.getElementById('NewsToolBox'); //将文本框插入到NewsToolBox这个之后
        toolBoxwrap.appendChild(textarea); //添加元素
        textarea.value = text;
        textarea.focus();
        document.documentElement.scrollTop = top;
        if (textarea.setSelectionRange) {
            textarea.setSelectionRange(0, textarea.value.length); //获取光标起始位置到结束位置
        } else {
            textarea.select();
        }
        try {
            var flag = document.execCommand("copy"); //执行复制
        } catch (eo) {
            var flag = false;
        }
        toolBoxwrap.removeChild(textarea); //删除元素
        currentFocus.focus();
        if(flag) layer.msg('复制成功');
        return flag;
    }
    // 查看链接
    function viewLink(path,url=''){
        var pagepath = path;
        if(!url){
            var url = "<?php echo m_url('"+pagepath+"'); ?>"; //拼接 H5 链接
        }
        <?php if(!in_array('mp',$platform)): ?>
        showwxqrcode(pagepath);
        return;
        <?php endif; ?>
            var html = '';
            html+='<div style="margin:20px">';
            html+='	<div style="width:100%;margin:10px 0" id="urlqr"></div>';
            <?php if(in_array('wx',$platform)): ?>
            html+='	<div style="width:100%;text-align:center"><button class="layui-btn layui-btn-sm layui-btn-primary" onclick="showwxqrcode(\''+pagepath+'\')">查看小程序码</button></div>';
            <?php endif; ?>
                html+='	<div style="line-height:25px;"><div><span style="width: 70px;display: inline-block;">链接地址：</span><button class="layui-btn layui-btn-xs layui-btn-primary" onclick="copyText(\''+url+'\')">复制</button></div><div>'+url+'</div></div>';
                html+='	<div style="height:50px;line-height:25px;"><div><span style="width: 70px;display: inline-block;">页面路径：</span><button style="box-sizing: border-box;" class="layui-btn layui-btn-xs layui-btn-primary" onclick="copyText(\'/'+pagepath+'\')">复制</button></div><div>/'+pagepath+'</div></div>';
                html+='</div>';
                layer.open({type:1,'title':'查看链接',area:['500px','430px'],shadeClose:true,'content':html})
                var qrcode = new QRCode('urlqr', {
                    text: 'your content',
                    width: 200,
                    height: 200,
                    colorDark : '#000000',
                    colorLight : '#ffffff',
                    correctLevel : QRCode.CorrectLevel.L
                });
                qrcode.clear();
                qrcode.makeCode(url);
            }
            // 查看小程序码
            function showwxqrcode(pagepath){
                var index = layer.load();
                $.post("<?php echo url('DesignerPage/getwxqrcode'); ?>",{path:pagepath},function(res){
                    layer.close(index);
                    if(res.status==0){
                        layer.open({type:1,area:['300px','350px'],content:'<div style="margin:auto auto;text-align:center"><div style="color:red;width:280px;height:180px;margin-top:100px">'+res.msg+'</div><div style="height:25px;line-height:25px;">'+'/'+pagepath+'</div></div>',title:false,shadeClose:true})
                    }else{
                        layer.open({type:1,area:['300px','350px'],content:'<div style="margin:auto auto;text-align:center"><img src="'+res.url+'" style="margin-top:20px;max-width:280px;max-height:280px"/><div style="height:25px;line-height:25px;">'+'/'+pagepath+'</div></div>',title:false,shadeClose:true})
                    }
                })
            }
</script>
<!-------使用js导出excel文件--------->
<script src="/static/admin/excel/excel.js?v=2024"></script>
<script src="/static/admin/excel/layui_exts/excel.js"></script>
<script>

    var excel = new Excel();
    var excel_name = '<?php echo $excel_name; ?>';
    excel.bind(function (data,title) {
        var excel_field = JSON.parse('<?php echo $excel_field; ?>');
        if(title && title!=undefined){
            //接口返回的title
            var excel_title = title;
        }else{
            //excel_field.php 配置的title
            var excel_title = JSON.parse('<?php echo $excel_title; ?>');
        }
        if(!excel_title || excel_title.length<=0){
            //上面两种都没有title,读取table表格cols中的title，同时filed也更新为table表格cols中的field
            excel_title = [];
            excel_field = [];
            var cols = tableIns.config.cols;
            cols.forEach(function (cols_item, cols_index) {
                console.log(cols_item);
                cols_item.forEach(function (cols_item2, cols_index2) {
                    console.log(cols_item2);
                    if(cols_item2.title){
                        excel_title.push(cols_item2.title)
                        excel_field.push(cols_item2.field)
                    }
                })
            })
        }
        // if(!excel_title || excel_title.length<=0){
        //     layer.msg('未设置标题');
        //     return;
        // }

        // 设置表格内容
        data.forEach(function (item, index) {
            var _data = [];
            excel_title.forEach(function (title, index2) {
                var field = excel_field[index2];
                if(item[field] && item[field]!=undefined){
                    //有filed 匹配field
                    var field_val = item[field];
                    //是整数 长度为10 字段名包含time 判定为时间戳
                    if(parseInt(field_val) == field_val && (field_val.toString()).length==10 && field.includes('time')){
                        field_val = date('Y-m-d H:i:s',field_val);
                    }
                }else{
                    //没有filed 根据顺序来
                    var field_val = item[index2];
                }
                _data.push(field_val);
            })
            data[index] = _data;
        });
        // 设置表头内容
        if(excel_title && excel_title.length>0){
            data.unshift(excel_title);
        }
        // 应用表格样式
        return this.withStyle(data);

    }, excel_name+layui.util.toDateString(Date.now(), '_yyyyMMdd_HHmmss'));

</script>
	<script src="/static/admin/js/jscolor.js"></script>
	<script language='javascript' src="/static/admin/poster/designer.js"></script>
	<script language='javascript' src="/static/admin/poster/jquery.contextMenu.js"></script>
	<script language='javascript'>
	var headsliderValue = 0;
  var headslider = layui.slider.render({
    elem: '#headslider',  //绑定元素
		change: function(value){
			headsliderValue = value
		}
  });
	function delposter(){
		layer.confirm('确定要删除该海报吗?',{icon: 7, title:'操作确认'}, function(index){
			var posterid = '<?php echo $posterid; ?>';
			var type = '<?php echo $type; ?>';
			$.post("<?php echo url('delposter'); ?>",{posterid:posterid,type:type},function(data){
				dialog(data)
			})
		})
	}
	//var shadowcolorValue = 'rgba(0,0,0,0.5)'
	function shadowcolor(){
		layui.colorpicker.render({
			elem: '#shadowcolorpicker',
			format:'rgb',
			alpha: true,
			color:$('#shadowcolor').val(),
			predefine: true,
			colors: ['#ff4444','#e64340','#ec8b89','#ed3f14','#ff9900',
				'#06bf04','#179b16','#9ed99d','#19be6b',
				'#3388ff','#2b85e4','#5cadff',
				'#000000','#333333','#666666','#999999','#c9c9c9','#f7f7f8','#1c2438','#495060','#dddee1','#e9eaec'],
			change:function(color){
				//shadowcolorValue = color;
				$('#shadowcolor').val(color)
			}
		});
	}
	
	//上传图片
	function setpic(imgid,hideid){
		fileUploader.show(function(imgs) {
			var params = '';
			console.log(imgs);
			if (imgs.length == 0) {
					return;
			} else {
				$('#'+imgid).attr('src',imgs.url + params);
				$('#'+hideid).val(imgs.url + params);

				var bg = $(':input[name=bg]').val();
				$('#poster .bg').remove();
				var bgh = $("<img src='"+imgs.url + params +"' class='bg' />");
				var first = $('#poster .drag:first');
				if(first.length>0){
					bgh.insertBefore(first);  
				} else{
					$('#poster').append(bgh);      
				}

				//调整窗口大小 二维码可以拖动至图片最底部
				var bgHeight = $('#poster .bg').height();
				//图片大于600（poster尺寸）时进行变更 定位与手机端存在偏差-10px
				if(bgHeight > 600){
					$("#poster").height(bgHeight - 10);
				}else{
					$("#poster").height(600);
				}

			}
		},{type: 'image',direct: false,multi: false});
	}
	//上传图片
	function setpic2(imgid,hideid){
		fileUploader.show(function(imgs) {
			console.log(imgs);
			if (imgs.length == 0) {
					return;
			} else {
				$('#'+imgid).attr('src',imgs.url);
				$('#'+hideid).val(imgs.url);
			}
		},{type: 'image',direct: false,multi: false});
	}

	function chushihua(){
		if(confirm('初始化后您设计的海报将被清除,还原到初始状态,确定要初始化吗')){
			$.post("<?php echo url('chushihua'); ?>",{},function(data){
				dialog(data.msg)
				location.href = location.href + (location.href.indexOf('?') > -1 ? '&' : '?') + 't={time()}';
			})
		}
	}
  layui.form.on('submit(formsubmit)', function(formobj){
		console.log(formobj);
			var data = [];
			$('.drag').each(function(){
					var obj = $(this);
					var type = obj.attr('type');
					var left = obj.css('left'),top = obj.css('top');
					var d= { left:left,top:top,type:obj.attr('type'),width:obj.css('width'),height:obj.css('height')};
					if(type=='qrwx'|| type=='qrmp'|| type=='qrgz'|| type=='qrtoutiao'|| type=='qrbaidu' || type=='qralipay' || type=='qrwxreg'){
							d.size = obj.attr('size');
					} else if(type=='img'){
							d.src = obj.attr('src');
					} else if(type=='text'){
							d.size = obj.attr('size');
							d.color = obj.attr('color');
							d.content = obj.attr('content');
					} else if(type=='textarea'){
							d.size = obj.attr('size');
							d.color = obj.attr('color');
							d.content = obj.attr('content');
					} else if(type=='pro_img'){
							d.showyy = obj.attr('showyy');
					} else if(type=='shadow'){
							d.shadow = obj.find('.shadow').attr('shadow');
					} else if(type=='head'){
							d.radius = obj.find('img').attr('radius');
					} else if(type=='reginvitecode'){
						d.size    = obj.attr('size');
						d.color   = obj.attr('color');
						d.content = obj.attr('content');
					}
					data.push(d);
			});
			var type = '<?php echo $type; ?>';
			var posterid = '<?php echo $posterid; ?>';
			var poster_bg = $('#poster_bg').val();
			var poster_data = JSON.stringify(data);
			var clearhistory = formobj.field.clearhistory
			var guize = formobj.field.guize;
			$.post("<?php echo url('save'); ?>",{type:type,posterid:posterid,poster_bg:poster_bg,poster_data:poster_data,clearhistory:clearhistory,guize:guize},function(data){
				dialog(data.msg,data.status,data.url);
			})
			return false;
	});
        
	function bindEvents(obj){
		var index = obj.attr('index');
		var type = obj.attr('type');
		if(type!='text'){
			var rs = new Resize(obj, { Max: true, mxContainer: "#poster" });
			rs.Set($(".rRightDown",obj), "right-down");
			rs.Set($(".rLeftDown",obj), "left-down");
			rs.Set($(".rRightUp",obj), "right-up");
			rs.Set($(".rLeftUp",obj), "left-up");
			rs.Set($(".rRight",obj), "right");
			rs.Set($(".rLeft",obj), "left");
			rs.Set($(".rUp",obj), "up");
			rs.Set($(".rDown",obj), "down"); 
			rs.Scale = false;
			if(type=='head' || type=='qrwx' || type=='qrmp' || type=='qrgz' || type=='qrtoutiao'|| type=='qrbaidu' || type=='qralipay' || type=='qrwxreg'){
				rs.Scale = true;
			}
		}
		//调整窗口大小 二维码可以拖动至图片最底部
		var bgHeight = $(".bg").height();
		//图片大于600（poster尺寸）时进行变更 定位与手机端存在偏差-10px
		if(bgHeight > 600){
			$("#poster").height(bgHeight - 10);
		}
		new Drag(obj, { Limit: true, mxContainer: "#poster" });
		$('.drag .remove').unbind('click').click(function(){
			$(this).parent().remove();
		});
    	$.contextMenu({
			selector:'.drag[index=' + index + ']',
			callback: function(key, options) {
				var index = parseInt($(this).attr('zindex'));
				if(key=='next'){
					var nextdiv = $(this).next('.drag');
					if(nextdiv.length>0 ){
					   nextdiv.insertBefore($(this));  
					}
				} else if(key=='prev'){
					var prevdiv = $(this).prev('.drag');
					if(prevdiv.length>0 ){
					   $(this).insertBefore(prevdiv);  
					} 
				} else if(key=='last'){
					var len = $('.drag').length;
					 if(index >=len-1){
						return;
					} 
					var last = $('#poster .drag:last');
					if(last.length>0){
					   $(this).insertAfter(last);  
					}
				}else if(key=='first'){
					var index = $(this).index();
					if(index<=1){
						return;
					}
					var first = $('#poster .drag:first');
					if(first.length>0){
					   $(this).insertBefore(first);  
					}
				}else if(key=='delete'){
				   $(this).remove();
				}
				var n =1 ;
				$('.drag').each(function(){
					$(this).css("z-index",n);
					n++; 
				})
			},
			items: {
				"next": { name: "调整到上层"},
				"prev": { name: "调整到下层"},
				"last": { name: "调整到最顶层"},
				"first": { name: "调整到最低层"},
				"delete": { name: "删除元素"}
			}
		});
		obj.unbind('click').click(function(){
			bind($(this));
		});    
	}
	var imgsettimer = 0 ;
	var nametimer = 0;
	var bgtimer = 0 ;
	function clearTimers(){
		clearInterval(imgsettimer);
		clearInterval(nametimer);
		clearInterval(bgtimer);
	}
  function bind(obj){
	var imgset = $('#imgset'),qrwxset = $('#qrwxset'),qrmpset = $('#qrmpset'),qrgzset = $('#qrgzset'),qralipayset = $('#qralipayset'),qrbaiduset = $('#qrbaiduset'),qrtoutiaoset = $('#qrtoutiaoset'),textset = $('#textset'),textareaset = $('#textareaset'),pro_imgset=$('#pro_imgset'),shadowset=$('#shadowset'),headset=$('#headset'),reginvitecodeset = $('#reginvitecodeset');
    imgset.hide(),qrwxset.hide(),qrmpset.hide(),qrgzset.hide(),textset.hide(),textareaset.hide(),pro_imgset.hide(),shadowset.hide(),headset.hide(),reginvitecodeset.hide();
    clearTimers();
    var type = obj.attr('type');
    if(type=='img'){
			imgset.show();
			var src = obj.attr('src');
			var input = imgset.find('input');
			var img = imgset.find('img');
			if(typeof(src)!='undefined' && src!=''){
				input.val(src); 
        		img.attr('src',src);
			}     
			imgsettimer = setInterval(function(){
			   if(input.val()!=src && input.val()!=''){
				   var url = input.val();
				   obj.attr('src',input.val()).find('img').attr('src',url);
			   }
			},10);       
		} else if(type=='qr'){
			/*qrset.show();
			var size = obj.attr('size') || "3";
			var sel = qrset.find('#qrsize');
			sel.val(size);
			sel.unbind('change').change(function(){
			  obj.attr('size',sel.val()) 
			});*/
		} else if(type=='pro_img'){
			pro_imgset.show();
			var showyy = obj.attr('showyy') || "1";
			var sel = pro_imgset.find('#pro_imgshowyy');
			sel.val(showyy);
			sel.unbind('change').change(function(){
			  obj.attr('showyy',sel.val()) 
			});
		}  else if(type=='text'){
			textset.show();
			var content = obj.attr('content') || "[昵称]";
			var color = obj.attr('color') || "#000";
			var size = obj.attr('size') || "16";
			var textcontent = textset.find('#textcontent');
			var textcolor = textset.find('#textcolor');
			var textsize = textset.find('#textsize');
			var picker = textset.find('.sp-preview-inner');
			textcontent.val(content); 
			textcolor.val(color); 
			textsize.val(size.replace("px",""));  
			picker.find('.text').text( content );
			picker.css( { 'background-color':color,'font-size':size});
			initcolorpicker();
			nametimer = setInterval(function(){
			   obj.attr('content',textcontent.val()).find('.text').text(textcontent.val());
			   obj.attr('color',textcolor.val()).find('.text').css('color',textcolor.val());
			   obj.attr('size',textsize.val() +"px").find('.text').css({'font-size':textsize.val() +"px",'line-height':textsize.val() +"px"});
			},10);    
		} else if(type=='textarea'){
			textareaset.show();
			var content = obj.attr('content') || "文本内容";
			var color = obj.attr('color') || "#000";
			var size = obj.attr('size') || "16";
			var textareacontent = textareaset.find('#textareacontent');
			var textareacolor = textareaset.find('#textareacolor');
			var textareasize = textareaset.find('#textareasize');
			var picker = textareaset.find('.sp-preview-inner');
			textareacontent.val(content); 
			textareacolor.val(color); 
			textareasize.val(size.replace("px",""));  
			picker.find('.textarea').text( content );
			picker.css( { 'background-color':color,'font-size':size});
			initcolorpicker();
			nametimer = setInterval(function(){
			   obj.attr('content',textareacontent.val()).find('.textarea').text(textareacontent.val());
			   obj.attr('color',textareacolor.val()).find('.textarea').css('color',textareacolor.val());
			   obj.attr('size',textareasize.val() +"px").find('.textarea').css({'font-size':textareasize.val() +"px",'line-height':textareasize.val() +"px"});
			},10);    
		}else if(type=='shadow'){
			shadowset.show();
			var shadowcolorValue = obj.find('.shadow').attr('shadow') || "rgba(0,0,0,0.5)";
			$('#shadowcolor').val(shadowcolorValue)
			shadowcolor();
			nametimer = setInterval(function(){
			  obj.find('.shadow').attr('shadow',$('#shadowcolor').val());
				obj.find('.shadow').css('background',$('#shadowcolor').val());
			},10);
		}else if(type=='head'){
			headset.show();
			headsliderValue = obj.find('img').attr('radius') || 0;
			headslider.setValue(headsliderValue);
			nametimer = setInterval(function(){
			  obj.find('img').attr('radius',headsliderValue);
				obj.find('img').css('border-radius',headsliderValue/2+'%');
			},10);
		}else if(type=='reginvitecode'){
			reginvitecodeset.show();
			var color  = obj.attr('color') || "#000";
			var size   = obj.attr('size') || "14";
			var textcolor = reginvitecodeset.find('#reginvitecodetextcolor');
			var textsize = reginvitecodeset.find('#reginvitecodetextsize');
			textcolor.val(color);
			textsize.val(size.replace("px",""));
			initcolorpicker();
			nametimer = setInterval(function(){
				obj.attr('color',textcolor.val()).find('.text').css('color',textcolor.val());
				obj.attr('size',textsize.val() +"px").find('.text').css({'font-size':textsize.val() +"px",'line-height':textsize.val() +"px"});
			},10);
		}
	}
         
  $(function(){                 
		$('.drag').each(function(){
			bindEvents($(this));
		})        
		$('.btn-com').click(function(){
			$('#imgset').hide()
			$('#qrwxset').hide()
			$('#qrmpset').hide()
			$('#qrgzset').hide()
			$('#qrbaiudset').hide()
			$('#qralipayset').hide()
			$('#qrtoutiaoset').hide()
			$('#textset').hide();
			$('#textareaset').hide();
			$('#reginvitecodeset').hide();
			$('#rqrwxregeset').hide();
			clearTimers();
			if($('#poster img').length<=0){
				//alert('请选择背景图片!');
				//return;
			}
			var type = $(this).data('type');
			var img = "";
			if(type=='qrwx'){
				img = '<img src="/static/admin/poster/qrwx.jpg" />';
			}else if(type=='qrmp'){
				img = '<img src="/static/admin/poster/qrmp.jpg" />';
			}else if(type=='qrgz'){
				img = '<img src="/static/admin/poster/qrmp.jpg" />';
			}else if(type=='qrbaidu'){
				img = '<img src="/static/admin/poster/qrwx.jpg" />';
			}else if(type=='qralipay'){
				img = '<img src="/static/admin/poster/qralipay.jpg" />';
			}else if(type=='qrtoutiao'){
				img = '<img src="/static/admin/poster/qrwx.jpg" />';
            }else if(type=='qrwxreg'){
                img = '<img src="/static/admin/poster/qrwx.jpg" />';
			}else if(type=='head'){
				img = '<img src="/static/admin/poster/default-avatar.png" />';
			}else  if(type=='img'){
				img = '<img src="/static/admin/poster/img.png" />';
			}else if(type=='pro_img'){
				img = '<img src="/static/admin/poster/product.jpg" />';
			}else if(type=='text'){
				img = '<div class="text">[昵称]</div>';
			}else if(type=='textarea'){
				img = '<div class="textarea">文本内容</div>';
			}else if(type=='shadow'){
				img = '<div class="shadow" shadow="rgba(0,0,0,0.5)" style="background:rgba(0,0,0,0.5)"></div>';
			}else if(type=='reginvitecode'){
				img = '<div class="reginvitecode">[邀请码]</div>';
			}
			var index = $('#poster .drag').length+1;
			var obj = $('<div class="drag" type="' + type +'" index="' + index +'" style="z-index:' + index+'">' + img+'<div class="rRightDown"> </div><div class="rLeftDown"> </div><div class="rRightUp"> </div><div class="rLeftUp"> </div><div class="rRight"> </div><div class="rLeft"> </div><div class="rUp"> </div><div class="rDown"></div></div>');
			$('#poster').append(obj);
			bindEvents(obj);
		});
		$('.drag').click(function(){
			bind($(this));
		})
	})
</script>
</body>
</html>