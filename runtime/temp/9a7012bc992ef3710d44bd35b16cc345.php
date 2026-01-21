<?php /*a:4:{s:56:"/www/wwwroot/eivie/app/view/shop_refund_order/index.html";i:1766974802;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;s:49:"/www/wwwroot/eivie/app/view/public/copyright.html";i:1648714895;}*/ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>订单管理</title>
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
	<style>
	.orderdetail{background:#f7f7f8;width: 94%;box-sizing:border-box;margin:0 10px;display:flex;flex-direction:column;}
	.orderdetail *{box-sizing:border-box;}
	.address{ display:flex;align-items:center;width: 100%; padding:10px 0; background: #FFF;color:#222}
	.address .img{width:45px;padding:0 10px}
	.address img{width:25px; height:25px;}
	.address .info{flex:1;display:flex;flex-direction:column;}
	.address .info .t1{ font-weight:bold}

	.express { width:100%;background: #fff; padding:10px;display:flex;align-items:center;margin-top:5px;color:#333}
	.express .f1{ width:40px;font-size:20px;color:#999}
	.express .f2{display:flex;flex-direction:column;flex:auto}

	.product{width:100%; padding:5px;background: #FFF;margin-top:5px;}
	.product .content{display:flex;position:relative;width: 100%; padding:5px 0px;border-bottom: 1px #e5e5e5 dashed;position:relative;}
	.product .content:last-child{ border-bottom: 0; }
	.product .content img{ width: 70px; height: 70px;}
	.product .content .detail{display:flex;flex-direction:column;margin-left:7px;flex:1}
	.product .content .detail .t1{height: 30px;line-height: 15px;color: #000;}
	.product .content .detail .t2{height: 23px;line-height: 23px;color: #999;overflow: hidden;font-size:13px;}
	.product .content .detail .t3{display:flex;height:15px;line-height:15px;color: #ff4246;}
	.product .content .detail .x1{ flex:1}
	.product .content .detail .x2{ width:50px;font-size:16px;text-align:right;margin-right:4px}
	.product .content .comment{position:absolute;top:32px;right:5px;border: 1px #ffc702 solid; border-radius:5px;background:#fff; color: #ffc702;  padding: 0 5px; height: 23px; line-height: 23px;}

	.orderinfo{ width:100%;margin-top:5px;padding: 5px;background: #FFF;}
	.orderinfo .item{display:flex;width:100%;padding:10px 0;border-bottom:1px dashed #ededed;overflow:hidden}
	.orderinfo .item:last-child{ border-bottom: 0;}
	.orderinfo .item .t1{width:100px;flex-shrink:0}
	.orderinfo .item .t2{flex:1;text-align:right;}
	.orderinfo .item .red{color:red}

	.logistics{ width: 94%;  background: #fff; padding: 0 10px;display:flex;flex-direction:column;color: #979797;}
	.logistics .on{color: #23aa5e;}
	.logistics .item{display:flex;width: 96%;  margin: 0 2%;/*border-left: 1px #dadada solid;*/padding:0 0}
	.logistics .item .f1{ width:30px;position:relative}
	/*.logistics img{width: 15px; height: 15px; position: absolute; left: -8px; top:11px;}*/
	.logistics .item .f1 img{ width: 15px; height: 100%;}

	.logistics .item .f2{display:flex;flex-direction:column;flex:auto;padding:5px 0}
	.logistics .item .f2 .t1{font-size: 15px;}
	.logistics .item .f2 .t1{font-size: 13px;}
	.layui-table-view .layui-table td .table-imgbox{width:60px;height:60px;float:left;display: flex;align-items: center;justify-content: center}
	.layui-table-view .layui-table td .table-imgbox img{max-width:60px;max-height:60px;float:left;}
	</style>
</head>
<body>
  <div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-card layui-col-md12">
          <div class="layui-card-header">
						<div class="layui-tab layui-tab-brief">
							<ul class="layui-tab-title">
								<li <?php if(!input('?param.status') || input('param.status')===''): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>'">全部</li>
								<li <?php if(input('param.status')=='1'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/1'">待审核</li>
								<li <?php if(input('param.status')=='2'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/2'">审核通过</li>
								<li <?php if(input('param.status')=='3'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/3'">驳回</li>
								<li <?php if(input('param.status')==='0'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/0'">取消</li>
								
							</ul>
						</div> 
						<?php if(input('param.isopen')==1): ?><i class="layui-icon layui-icon-close" style="font-size:18px;font-weight:bold;cursor:pointer" onclick="closeself()"></i><?php endif; ?>
          </div>
          <div class="layui-card-body" pad15>
						<div class="layui-col-md12">
							<!-- <div class="layui-col-md3" style="text-align:right;padding-bottom:10px"></div> -->
							<div class="layui-col-md12 layui-form layui-form-search">
								<div class="layui-inline layuiadmin-input-useradmin" style="display:none">
									<label class="layui-form-label"><?php echo t('会员'); ?>ID</label>
									<div class="layui-input-inline">
										<input type="text" name="mid" value="<?php echo app('request')->param('mid'); ?>" autocomplete="off" class="layui-input">
									</div>
								</div>
								<div class="layui-inline">
									<label class="layui-form-label">退款单号</label>
									<div class="layui-input-inline">
										<input type="text" name="refund_ordernum" autocomplete="off" class="layui-input">
									</div>
								</div>
								<div class="layui-inline">
									<label class="layui-form-label">退货快递单号</label>
									<div class="layui-input-inline">
										<input type="text" name="express_no" autocomplete="off" class="layui-input">
									</div>
								</div>
								<div class="layui-inline layuiadmin-input-useradmin">
									<label class="layui-form-label">订单号</label>
									<div class="layui-input-inline">
										<input type="text" name="ordernum" autocomplete="off" class="layui-input">
									</div>
								</div>
								<div class="layui-inline">
									<label class="layui-form-label">手机号</label>
									<div class="layui-input-inline">
										<input type="text" name="tel" autocomplete="off" class="layui-input">
									</div>
								</div>
								<div class="layui-inline">
									<label class="layui-form-label">申请时间</label>
									<div class="layui-input-inline">
										<input type="text" name="ctime" id="ctime" autocomplete="off" class="layui-input">
									</div>
								</div>
								

								<div class="layui-inline">
									<label class="layui-form-label">状态</label>
									<div class="layui-input-inline">
										<select name="status">
											<option value="">全部</option>
											<option value="0" <?php if(input('param.status')==='0'): ?>selected<?php endif; ?>>取消</option>
											<option value="1" <?php if(input('param.status')==='1'): ?>selected<?php endif; ?>>待审核</option>
											<option value="2" <?php if(input('param.status')==='2'): ?>selected<?php endif; ?>>已退款</option>
											<option value="3" <?php if(input('param.status')==='3'): ?>selected<?php endif; ?>>退款驳回</option>
											<option value="4" <?php if(input('param.status')==='4'): ?>selected<?php endif; ?>>审核通过，待退货</option>
											<option value="41" <?php if(input('param.status')==='41'): ?>selected<?php endif; ?>>审核通过，已寄回</option>
											
										</select>
									</div>
								</div>
								
								<div class="layui-inline">
									<button class="layui-btn layuiadmin-btn-replys" lay-submit="" lay-filter="LAY-app-forumreply-search">
										<i class="layui-icon layui-icon-search layuiadmin-button-btn"></i>
									</button>
								</div>
							</div>
						</div>
						<div class="layui-col-md12">
							<table id="tabledata" lay-filter="tabledata"></table>
						</div>
          </div>
        </div>
    </div>
  </div>
  <script id="logisticsTpl" type="text/html">
	<div style="padding:5px"></div>
	{{# if(d.data.length==0){ }}<div style="font-size:14px;color:#f05555;padding:10px;"><i class="fa fa-info-circle"></i> 暂未查到物流信息</div>{{# } }}
	{{# if(d.data.length>0){ }}
	<div class="layui-tab layui-tab-brief" lay-filter="mytab">
		<ul class="layui-tab-title">
			{{#  layui.each(d.data, function(index, item){ }}
			<li  class="{{index==0?'layui-this':''}}" lay-id="{{index}}">{{item.express_com}}-{{item.express_no}}</li>
			{{# }); }}
		</ul>
		<div class="layui-tab-content">
			{{#  layui.each(d.data, function(index, item){ }}
			<div class="layui-tab-item {{index==0?'layui-show':''}}">
				{{# if((item.oglist).length>0){ }}
					<div class="product">
					{{#  layui.each(item.oglist, function(index2, item2){ }}
					<div class="content">
						<div>
							<img src="{{item2.pic}}"/>
						</div>
						<div class="detail">
							<span class="t1">{{item2.name}}</span>
							<span class="t2">{{item2.ggname}}</span>
							<div class="t3"><span class="x1 flex1">￥{{item2.sell_price}}</span><span class="x2">×{{item2.num}}</span></div>
						</div>
					</div>
					{{# }); }}
					</div>
				{{# } }}
				{{# if((item.express_data).length>0){ }}
				<ul class="layui-timeline">
				{{#  layui.each(item.express_data, function(index2, item2){ }}
					<li class="layui-timeline-item">
						<i class="layui-icon layui-timeline-axis {{index2==0?'on':''}}"></i>
						<div class="layui-timeline-content layui-text">
							<p>{{item2.time}}</p>
							<p>
								{{item2.context}}
							</p>
						</div>
					</li>
				{{# }); }}
				</ul>
				{{# }else{ }}
				<div style="padding:20px 0 20px 0;font-size:16px">暂未查到物流信息</div>
				{{# } }}
			</div>
			{{# }); }}
		</div>
	</div>
	{{# } }}
	</script>
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
	<script>
  var table = layui.table;
	var datawhere = {};
	<?php if(input('?param.status') && input('param.status')!==''): ?>
	datawhere['status'] = "<?php echo app('request')->param('status'); ?>";
	<?php endif; ?>
	layui.use('flow', function(){
		var flow = layui.flow;
		flow.lazyimg();
	});
  //数据表
  var tableIns = table.render({
    elem: '#tabledata'
    ,url: "<?php echo app('request')->url(); ?>" //数据接口
    ,page: true //开启分页
		//,size:'sm'
    ,cols: [[ //表头
			//{type:"checkbox"},
      {field: 'id', title: 'ID',  sort: true,width:60},
			<?php if(input('param.showtype')==2): ?>
      {field: 'bname', title: '所属商户',width:160},
			<?php endif; ?>
      {field: 'goodsdata', title: '商品信息',width:240},
      {field: 'refund_ordernum', title: '退款单号/申请时间',sort: true,width:160,templet:function(d){ return d.refund_ordernum +'<div style="color:#080">'+ date('Y年m月d日 H:i',d.createtime)+'</div>'}},
	  {field: 'ordernum', title: '订单号',sort: true,width:160},
	  {field: 'refund_type_label', title: '类型',width:150,},
	  {field: 'refund_money', title: '退款金额',width:150},
		  
	  {field: 'express_no', title: '退货快递信息',width:200,templet:function(d){ 
			var html = '';
			if(d.express_com){
				html += '快递公司：'+d.express_com + '<br>';
			}
			if(d.express_no){
				html += '快递单号：'+d.express_no+ '<br>';
				html += '<button class="table-btn" onclick="getexpress('+d.id+')">查物流</button>';
			}
			return html;
		}},
    {field: 'status', title: '状态',templet:function(d){
			var html = '';
			if(d.refund_status==0) html+='<div style="color:#999">已取消</span>';
			if(d.refund_status == 1) html+='<div style="color:red">退款待审核</div>';
			if(d.refund_status == 2) html+='<div style="color:#008000">已退款</div>';
			if(d.refund_status == 3) html+='<div style="color:red">退款驳回</div>';
			if(d.refund_status == 4 && !d.isexpress && d.refund_type != 'exchange') html+='<div style="color:red">审核通过，待退货</div>';
			if(d.refund_status == 4 && d.isexpress && d.refund_type != 'exchange') html+='<div style="color:red">审核通过，已寄回</div>';
			if(d.refund_status == 4 && d.refund_type == 'exchange') html+='<div style="color:red">用户已寄回</div>';
			
			return html;
		}},
		
      //{field: 'createtime', title: '下单时间',sort: true,templet:function(d){ return date('Y-m-d H:i',d.createtime)},width:150},
      {field: 'operation', title: '操作',templet:function(d){
				var html = '';
				html += '<button class="table-btn" onclick="showdetail('+d.id+')">详情</button>';
				if(d.status==0 || d.status==1 || d.status==2){
				}

				if(d.refund_status==4 && !d.isexpress && !d.return_name && !d.return_address) { //审核通过 待退货订单无退货地址的需要填写退货地址
					html += '<button class="table-btn" onclick="doOperation('+d.id+',\'填写寄回地址\')">填写寄回地址</button>';
				}
				if(d.cancheck){
					if(d.refund_status==1){ //退款待审核
						if(d.refund_type == 'refund'){
							html += '<button class="table-btn" onclick="doOperation('+d.id+',\'退款审核\')">退款审核</button>';
						} else if (d.refund_type == 'return'){
							html += '<button class="table-btn" onclick="doOperation('+d.id+',\'退货退款审核\')">退货退款审核</button>';
						}
					}
				  if(d.refund_status==4) { //审核通过 待退货
						if(d.refund_type!='exchange'){
							html += '<button class="table-btn" onclick="doOperation('+d.id+',\'退款\')">退款</button>';
						}else if(d.refund_type=='exchange'){
							
						}
				  }
				}
				

			  
			  html += '<button class="table-btn" onclick="datadel('+d.id+')">删除</button><br/>';
				
			  return html;
      }}
    ]],
		// 监听表格的数据更新
		done: function(res, curr, count){
			if(document.documentElement.scrollTop >= 300){
				document.documentElement.scrollTop = document.documentElement.scrollTop-1
			}else{
				document.documentElement.scrollTop = 5
				if(document.documentElement.scrollTop>0){
				}else{
					$(".layui-table-body .table-imgbox").each(function(){
						 var src = $(this).find('img').attr('lay-src');
						 $(this).find('img').attr('src',src)
					})
				}
			}
		}
  });
	//排序
	table.on('sort(tabledata)', function(obj){
		datawhere.field = obj.field;
		datawhere.order = obj.type;
		tableIns.reload({
			initSort: obj,
			where: datawhere
		});
	});
	//日期范围选择
	layui.laydate.render({ 
		elem: '#ctime',
		trigger: 'click',
		range: '~' //或 range: '~' 来自定义分割字符
	});
	//检索
	layui.form.on('submit(LAY-app-forumreply-search)', function(obj){
		var field = obj.field
		var olddatawhere = datawhere
		datawhere = field
		datawhere.field = olddatawhere.field
		datawhere.order = olddatawhere.order
		tableIns.reload({
			where: datawhere,
			page: {curr: 1}
		});
	})
	//打印小票
	function wifiprint(id){
		layer.confirm('确定要打印小票吗?',{icon: 7, title:'操作确认'}, function(index){
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('wifiprint'); ?>",{id:id},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
			})
		})
	}
	//删除
	function datadel(id){
		layer.confirm('删除后数据不可恢复！确定要删除吗？删除后无法恢复！',{icon: 7, title:'操作确认'}, function(index){
			//do something
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('del'); ?>",{id:id},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}
	var detailLayer;
	function showdetail(orderid){
		var index = layer.load();
		$.post("<?php echo url('getdetail'); ?>",{orderid:orderid},function(data){
			layer.close(index);
			var html = '';
			//var data = { //数据
			//	"title":"Layui常用模块",
			//	"list":[{"modname":"弹层","alias":"layer","site":"layer.layui.com"},{"modname":"表单","alias":"form"}]
			//}
			layui.laytpl(detailtpl.innerHTML).render(data, function(html){
				//view.innerHTML = html;
				var detailBtn = [];
				if(data.order.refund_status==1 && data.order.refund_type == 'refund' && data.cancheck){ //退款待审核
					detailBtn.push('退款审核');
				}
				if(data.order.refund_status==1 && data.order.refund_type == 'return' && data.cancheck){ //退货退款待审核
					detailBtn.push('退货退款审核');
				}
				if(data.order.refund_status==4){
					if(data.order.refund_type!='exchange'){
						detailBtn.push('退款');
					}else if(data.order.refund_type=='exchange'){
						
					}
				}
				
				detailLayer = layer.open({type:1,title:'订单详情',area:['500px','90%'],content:html,resize:true,shadeClose:true,maxmin:true,btn:detailBtn,
					yes:function(){
						return btnOperation(detailBtn[0],data);
					},
					btn2:function(){
						return btnOperation(detailBtn[1],data);
					},
					btn3:function(){
						return btnOperation(detailBtn[2],data);
					},
					btn4:function(){
						return btnOperation(detailBtn[3],data);
					},
					btn5:function(){
						return btnOperation(detailBtn[4],data);
					}
				});
			});
			//layer.open({type:1,area:['600px','600px'],title:false,shadeClose:true,btn: ['发货', '退款审核', '设置备注']});
		})
	}
	function doOperation(orderid,type){
		$.post("<?php echo url('getdetail'); ?>",{orderid:orderid},function(data){
			
			btnOperation(type,data);
		})
	}
	function btnOperation(type,data){
		var orderid = data.order.id;
		if(type=='退款审核'){
			layui.laytpl(refundCheckTpl.innerHTML).render(data, function(html){
				var refundCheckLayer = layer.open({type:1,area:['400px'],title:false,content:html,shadeClose:true,btn: ['同意并退款', '驳回退款申请'],
					yes:function(){
						var index = layer.load();
						$.post("<?php echo url('refundCheck'); ?>",{orderid:orderid,st:1,remark:$('textarea[name=refund_checkremark]').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(refundCheckLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					},
					btn2:function(){
						var index = layer.load();
						$.post("<?php echo url('refundCheck'); ?>",{orderid:orderid,st:2,remark:$('textarea[name=refund_checkremark]').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(refundCheckLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					}
				})
			})
		}else if(type == '退货退款审核'){
			layui.laytpl(refundCheckTpl.innerHTML).render(data, function(html){
				var refundCheckLayer = layer.open({type:1,area:['400px'],title:false,content:html,shadeClose:true,btn: ['同意申请等待买家退货', '驳回退款申请'],
					yes:function(){
						var index = layer.load();
						$.post("<?php echo url('returnCheck'); ?>",{orderid:orderid,st:1,remark:$('textarea[name=refund_checkremark]').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(refundCheckLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					},
					btn2:function(){
						var index = layer.load();
						$.post("<?php echo url('refundCheck'); ?>",{orderid:orderid,st:2,remark:$('textarea[name=refund_checkremark]').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(refundCheckLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					}
				})
			})
		}else if(type == '配送员配送'){

		}else if(type == '退款'){

			layer.confirm('您确认收到退货并进行退款吗?',{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('refund'); ?>",{orderid:orderid},function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(detailLayer);
					tableIns.reload()
				})
			})
		}else if(type == '查物流'){
		}else if(type == '设置备注'){
			layui.laytpl(remarktpl.innerHTML).render({remark:data.order.remark}, function(html){
				var remarkLayer = layer.open({type:1,title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
					yes:function(){
						var index = layer.load();
						$.post("<?php echo url('setremark'); ?>",{orderid:orderid,content:$('#remarkcontent').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(remarkLayer);
							layer.close(detailLayer);
						})
					}
				})
			});
		}else if(type == '已支付'){
			layer.confirm('确定要改为已支付吗?',{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('ispay'); ?>",{orderid:orderid},function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(detailLayer);
					tableIns.reload()
				})
			})
		}else if(type == '改价'){

		}else if(type == '关闭订单'){

		}else if(type == '删除'){

		}else if(type == '确认收货'){
			
		}else if(type == '填写寄回地址'){
				var html = '<div style="margin:20px auto;">';
				html+='<div class="layui-form" lay-filter="">';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">联系人</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="return_name" id="return_name" class="layui-input" value="<?php echo $returnInfo['return_name']; ?>">';
				html+='		</div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">联系方式</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="return_tel" id="return_tel" class="layui-input" value="<?php echo $returnInfo['return_tel']; ?>">';
				html+='		</div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">省份</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="return_province" id="return_province" class="layui-input" value="<?php echo $returnInfo['return_province']; ?>">';
				html+='		</div>';
				html+='		<div class="layui-form-mid"></div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">城市</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="return_city" id="return_city" class="layui-input" value="<?php echo $returnInfo['return_city']; ?>">';
				html+='		</div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">区县</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="return_area" id="return_area" class="layui-input" value="<?php echo $returnInfo['return_area']; ?>">';
				html+='		</div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">详细地址</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="return_address" id="return_address" class="layui-input" value="<?php echo $returnInfo['return_address']; ?>">';
				html+='		</div>';
				html+='		<div class="layui-form-mid"></div>';
				html+='	</div>';
				html+='</div>';
				html+='</div>'
				var sendExpressLayer = layer.open({type:1,area: ['600px', '530px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],yes:function(){
						var index = layer.load();
						$.post("<?php echo url('writeReturnaddress'); ?>",{orderid:orderid,return_name:$('#return_name').val(),return_tel:$('#return_tel').val(),return_province:$('#return_province').val(),return_city:$('#return_city').val(),return_area:$('#return_area').val(),return_address:$('#return_address').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(sendExpressLayer);
							layer.close(detailLayer);
							tableIns.reload()
						});
					}
				});
				layui.form.render();
				return;
		}else if(type == '换货驳回'){
			
		}else if(type == '发货'){
			
		}
		return false
	}
	function putAway(obj){
		$(obj).html($(obj).html().substring(0,2) == '收起' ? '展开 &#9660' : '收起 &#9650');
		$(obj).prevAll().slice(0, $(obj).prevAll().length - 3).toggle();
	}
	function getexpress(id,st=0){
		var index = layer.load();
		$.post("<?php echo url('getExpress'); ?>",{orderid:id,st:st},function(res){
			console.log(res)
			var expressdata = res.data
			layer.close(index);
			layui.laytpl(logisticsTpl.innerHTML).render(res, function(html){
				var btn = [];
				var logisticsLayer = layer.open({type:1,title:'查看物流',content:html,area:"800px",shadeClose:true,btn: btn,
					yes:function(){
						layer.close(logisticsLayer);
						doOperation(orderid,'发货');
					}
				})
			})
		});
	}
	
	</script>
	<script id="refundCheckTpl" type="text/html">
	<div class="orderdetail">
	<div class="orderinfo">
		<div class="item">
			<span class="t1">类型</span>
			<span class="t2 red">{{d.order.refund_type_label}}</span>
		</div>
		<div class="item">
			<span class="t1">申请退款金额</span>
			<span class="t2 red">￥{{d.order.refund_money}}</span>
		</div>
		
		<div class="item">
			<span class="t1">申请退款原因</span>
			<span class="t2 red">{{d.order.refund_reason}}</span>
		</div>
		<div class="item">
			<span class="t1">图片</span>
			<span class="t2 red">
				{{#  if(d.order.refund_pics){ }}
				{{d.order.refund_pics_html}}
				{{# } }}
			</span>
		</div>
		<div class="item">
			<span class="t1">申请退款时间</span>
			<span class="t2">{{date('Y-m-d H:i:s',d.order.refund_time)}}</span>
		</div>
		{{#  if(d.return && d.return.show_return_address && d.order.refund_type=='return'){ }}
		<div class="item">
			<span class="t1">退货地址</span>
			<span class="t2">
				<p>{{d.return.return_name}} {{d.return.return_tel}}</p>
				<p>{{d.return.return_province}} {{d.return.return_city}} {{d.return.return_area}} {{d.return.return_address}}</p>
			</span>
		</div>
		{{# } }}
		<div class="item">
			<span class="t1">审核备注</span>
			<span class="t2"><textarea name="refund_checkremark" class="layui-textarea">{{d.order.refund_checkremark ? d.order.refund_checkremark : ''}}</textarea></span>
		</div>
	</div>
	</div>
	</script>

	<script id="remarktpl" type="text/html">
	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">
		<label class="layui-form-label" style="width:40px">备注</label>
		<div class="layui-input-inline" style="width:180px">
			<input type="text" id="remarkcontent" class="layui-input" value="{{d.remark?d.remark : ''}}">
		</div>
	</div>
	</script>

	<script id="detailtpl" type="text/html">
	<div class="orderdetail">
		{{# if(d.order.send_time && (d.order.freight_type==3 || d.order.freight_type==4)){ }}
		<div class="express">
			<div class="f1"><i class="fa fa-file-text"></i></div>
			<div class="f2"><pre>{{d.order.freight_content}}</pre></div>
		</div>
		{{# } }}

		<div class="product">
			{{#  layui.each(d.oglist, function(index, item){ }}
			<div class="content">
				<div>
					<img src="{{item.pic}}"/>
				</div>
				<div class="detail">
					<span class="t1">{{item.name}}</span>
					<span class="t2">{{item.ggname}}</span>
					<div class="t3"><span class="x1 flex1">￥{{item.sell_price}}</span><span class="x2">×{{item.refund_num}}</span></div>
				</div>
			</div>
			{{# }); }}
		</div>

		
		
		{{#  if(d.order.message || d.order.remark || d.order.field1 || d.order.field2 || d.order.field3 || d.order.field4 || d.order.field5){ }}
		<div class="orderinfo">
			{{#  if(d.order.message){ }}
			<div class="item">
				<span class="t1">客户备注</span>
				<span class="t2 red">{{d.order.message ? d.order.message : '无'}}</span>
			</div>
			{{# } }}

			{{#  if(d.order.remark){ }}
			<div class="item">
				<span class="t1">后台备注</span>
				<span class="t2 red">{{d.order.remark ? d.order.remark : '无'}}</span>
			</div>
			{{# } }}
		</div>
		{{# } }}
		<div class="orderinfo">
			<div class="item">
				<span class="t1">下单人</span>
				<span class="t2"><img src="{{d.member.headimg}}" style="width:40px"/> {{d.member.nickname}} {{# if(d.member.realname){ }}[{{d.member.realname}} {{d.member.tel}}]{{#} }}</span>
			</div>
			<div class="item">
				<span class="t1"><?php echo t('会员'); ?>ID</span>
				<span class="t2">{{d.member.id}}</span>
			</div>
			{{#  if(d.order.school_info){ }}
			<div class="item">
				<span class="t1">班级信息</span>
				<span class="t2">{{d.order.school_info}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.member_content){ }}
			<div class="item">
				<span class="t1">学生信息</span>
				<span class="t2">{{d.order.member_content}}</span>
			</div>
			{{# } }}
		</div>
		<div class="orderinfo">
			<div class="item">
				<span class="t1">类型</span>
				<span class="t2">{{d.order.refund_type_label}}</span>
			</div>
			<div class="item">
				<span class="t1">退款编号</span>
				<span class="t2">{{d.order.refund_ordernum}}</span>
			</div>
			<div class="item">
				<span class="t1">退款金额</span>
				<span class="t2 red">¥{{d.order.refund_money}}</span>
			</div>
			<div class="item">
				<span class="t1">本单已退款金额</span>
				<span class="t2 red">¥{{d.order.refundMoneyTotal}}</span>
			</div>
			<div class="item">
				<span class="t1">申请时间</span>
				<span class="t2">{{date('Y-m-d H:i:s',d.order.createtime)}}</span>
			</div>
			<div class="item">
				<span class="t1">退款状态</span>
				{{#  if(d.order.refund_status==0){ }}
				<span class="t2 red">已取消</span>
				{{# } }}
				{{#  if(d.order.refund_status==1){ }}
				<span class="t2 red">审核中</span>
				{{# } }}
				{{#  if(d.order.refund_status==2){ }}
				<span class="t2 red">已退款</span>
				{{# } }}
				{{#  if(d.order.refund_status==3){ }}
				<span class="t2 red">已驳回</span>
				{{# } }}
				{{#  if(d.order.refund_status==4 && !d.order.isexpress){ }}
				<span class="t2 red">审核通过，待退货</span>
				{{# } }}
				{{#  if(d.order.refund_status==4 && d.order.isexpress){ }}
				<span class="t2 red">审核通过，已寄回</span>
				{{# } }}
			</div>
			<div class="item">
				<span class="t1">退款原因</span>
				<span class="t2 red">{{d.order.refund_reason}}</span>
			</div>
			<div class="item">
				<span class="t1">图片</span>
				<span class="t2 red">
					{{#  if(d.order.refund_pics){ }}
					{{d.order.refund_pics_html}}
					{{# } }}
				</span>
			</div>

			{{#  if(d.order.refund_status==2 || d.order.refund_status==4){ }}
				{{#  if(d.order.return_address){ }}
					<div class="item">
						<span class="t1">寄回地址</span>
						<span class="t2">{{d.order.return_province}} {{d.order.return_city}} {{d.order.return_area}} {{d.order.return_address}}</span>
					</div>
				{{# } }}
				{{#  if(d.order.return_name){ }}
					<div class="item">
						<span class="t1">寄回联系人</span>
						<span class="t2">{{d.order.return_name}} {{d.order.return_tel}}</span>
					</div>
				{{# } }}
				{{#  if(d.order.express_com){ }}
					<div class="item">
						<span class="t1">快递公司</span>
						<span class="t2">{{d.order.express_com}}</span>
					</div>
				{{# } }}
				{{#  if(d.order.express_no){ }}
					<div class="item">
						<span class="t1">快递单号</span>
						<span class="t2">{{d.order.express_no}}</span>
					</div>
				{{# } }}
      {{# } }}

			{{#  if(d.order.refund_checkremark){ }}
			<div class="item">
				<span class="t1">审核备注</span>
				<span class="t2 red">{{d.order.refund_checkremark}}</span>
			</div>
			{{# } }}
		</div>

		

		<div class="orderinfo">
			<div class="item">
				<span class="t1">订单编号</span>
				<span class="t2">{{d.orderdetail.ordernum}}</span>
			</div>
			<div class="item">
				<span class="t1">下单时间</span>
				<span class="t2">{{date('Y-m-d H:i:s',d.orderdetail.createtime)}}</span>
			</div>
			{{#  if(d.orderdetail.status>0 && d.orderdetail.paytime){ }}
			<div class="item">
				<span class="t1">支付时间</span>
				<span class="t2">{{date('Y-m-d H:i:s',d.orderdetail.paytime)}}</span>
			</div>
			<div class="item">
				<span class="t1">支付方式</span>
				<span class="t2">{{d.orderdetail.paytype}}</span>
			</div>
			{{# } }}
			{{#  if(d.orderdetail.status>1 && d.orderdetail.send_time){ }}
			<div class="item">
				<span class="t1">发货时间</span>
				<span class="t2">{{date('Y-m-d H:i:s',d.orderdetail.send_time)}}</span>
			</div>
			{{# } }}
			{{#  if(d.orderdetail.status==3 && d.orderdetail.collect_time){ }}
			<div class="item">
				<span class="t1">收货时间</span>
				<span class="t2">{{date('Y-m-d H:i:s',d.orderdetail.collect_time)}}</span>
			</div>
			{{# } }}
		</div>
		<div class="orderinfo">
			<div class="item">
				<span class="t1">商品金额</span>
				<span class="t2 red">¥{{d.orderdetail.product_price}}</span>
			</div>
			{{#  if(d.orderdetail.leveldk_money > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('会员'); ?>折扣</span>
				<span class="t2 red">-¥{{d.orderdetail.leveldk_money}}</span>
			</div>
			{{# } }}
			{{#  if(d.orderdetail.manjian_money > 0){ }}
			<div class="item">
				<span class="t1">满减活动</span>
				<span class="t2 red">-¥{{d.orderdetail.manjian_money}}</span>
			</div>
			{{# } }}
			<div class="item">
				<span class="t1">配送方式</span>
				<span class="t2">{{d.orderdetail.freight_text}}</span>
			</div>
			{{#  if(d.orderdetail.freight_type==11){ }}
			<div class="item">
				<span class="t1">发货地址</span>
				<span class="t2">{{d.orderdetail.freight_content.send_address}} - {{d.orderdetail.freight_content.send_tel}}</span>
			</div>
			<div class="item">
				<span class="t1">收货地址</span>
				<span class="t2">{{d.orderdetail.freight_content.receive_address}} - {{d.orderdetail.freight_content.receive_tel}}</span>
			</div>
			{{# } }}
			{{#  if(d.orderdetail.freight_time){ }}
			<div class="item">
				<span class="t1">{{d.orderdetail.freight_type!=1?'配送':'提货'}}时间</span>
				<span class="t2">{{d.orderdetail.freight_time}}</span>
			</div>
			{{# } }}
			{{#  if(d.orderdetail.coupon_money > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('优惠券'); ?>抵扣</span>
				<span class="t2 red">-¥{{d.orderdetail.coupon_money}}</span>
			</div>
 			{{#  if(d.couponrecord){ }}
			<div class="item">
				<span class="t1"><?php echo t('优惠券'); ?>名称</span>
				<span class="t2">{{d.couponrecord.couponname}}</span>
			</div>
			{{# } }}
			{{# } }}
			{{#  if(d.orderdetail.scoredk_money > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('积分'); ?>抵扣</span>
				<span class="t2 red">-¥{{d.orderdetail.scoredk_money}}</span>
			</div>
			{{# } }}
			<div class="item">
				<span class="t1">实付款</span>
				<span class="t2 red">¥{{d.orderdetail.totalprice}}</span>
			</div>
			
			<div class="item">
				<span class="t1">订单状态</span>
				{{#  if(d.orderdetail.status==0){ }}
				<span class="t2" style="color:#ff8758">未付款</span>
				{{# } }}
				{{#  if(d.orderdetail.status==1 && d.orderdetail.paytypeid=='4'){ }}
				<span class="t2" style="color:#008000">待发货</span>
				{{# } }}
				{{#  if(d.orderdetail.status==1 && d.orderdetail.paytypeid!='4'){ }}
				<span class="t2" style="color:#008000">已支付</span>
				{{# } }}
				{{#  if(d.orderdetail.status==2){ }}
				<span class="t2" style="color:#ff4246">已发货</span>
				{{# } }}
				{{#  if(d.orderdetail.status==3){ }}
				<span class="t2" style="color:#999">已收货</span>
				{{# } }}
				{{#  if(d.orderdetail.status==4){ }}
				<span class="t2" style="color:#bbb">已关闭</span>
				{{# } }}
			</div>
			{{#  if(d.orderdetail.balance_price>0){ }}
			<div class="item">
				<span class="t1">尾款金额</span>
				<span class="t2 red">¥{{d.orderdetail.balance_price}}</span>
			</div>
			<div class="item">
				<span class="t1">尾款状态</span>
				{{#  if(d.orderdetail.balance_pay_status==1){ }}
				<span class="t2">已支付</span>
				{{# } }}
				{{#  if(d.orderdetail.balance_pay_status==0){ }}
				<span class="t2">未支付</span>
				{{# } }}
			</div>
			{{# } }}
			{{#  if(d.orderdetail.refund_status>0){ }}
			<div class="item">
				<span class="t1">退款状态</span>
				{{#  if(d.orderdetail.refund_status==1){ }}
				<span class="t2 red">审核中,¥{{d.orderdetail.refund_money}}</span>
				{{# } }}
				{{#  if(d.orderdetail.refund_status==2){ }}
				<span class="t2 red">已退款,¥{{d.orderdetail.refund_money}}</span>
				{{# } }}
				{{#  if(d.orderdetail.refund_status==3){ }}
				<span class="t2 red">已驳回,¥{{d.orderdetail.refund_money}}</span>
				{{# } }}
			</div>
			{{# } }}
			{{#  if(d.returndetail){ }}
				<div class="item">
					<span class="t1">退货方式</span>
					{{#  if(d.returndetail.status==0){ }}
					<span class="t2">用户未填写</span>
					{{# } }}
					{{#  if(d.returndetail.status==1){ }}
					<span class="t2">在线预约</span>
					{{# } }}
					{{#  if(d.returndetail.status==2){ }}
					<span class="t2">自主填写</span>
					{{# } }}
				</div>
				{{#  if(d.returndetail.status ==1 || d.returndetail.status==2){ }}
				<div class="item">
					<span class="t1">运单号</span>
					<span class="t2">{{d.returndetail.delivery_name}} {{d.returndetail.waybill_id}}</span>
				</div>
				<div class="item">
					<span class="t1">退货状态</span>
					<span class="t2">{{d.returndetail.order_status_txt}}</span>
				</div>
				{{# } }}
			{{# } }}
			
		</div>
	</div>
	</script>
	<script id="sendExpressTpl" type="text/html">
		<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">
			<label class="layui-form-label" style="width:60px">快递公司</label>
			<div class="layui-input-inline" style="width:180px">
				<select id="send_express" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;">
					<?php foreach($express_data as $k=>$v): ?>
						<option><?php echo $k; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="layui-form-item">
			<label class="layui-form-label" style="width:60px">快递单号</label>
			<div class="layui-input-inline" style="width:180px">
				<input type="text" id="send_express_no" class="layui-input" value="">
			</div>
		</div>
	</script>
	
</body>
</html>