<?php /*a:4:{s:45:"/www/wwwroot/eivie/app/view/maidan/index.html";i:1764308960;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;s:49:"/www/wwwroot/eivie/app/view/public/copyright.html";i:1648714895;}*/ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>买单记录</title>
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
</head>
<body>
  <div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-card layui-col-md12">
          <div class="layui-card-header"><i class="fa fa-list"></i> 买单记录
			<?php if(input('param.isopen')==1): ?><i class="layui-icon layui-icon-close" style="font-size:18px;font-weight:bold;cursor:pointer" onclick="closeself()"></i><?php endif; ?>
		  </div>
          <div class="layui-card-body" pad15>
						<div style="float:left;padding-bottom:10px">
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="datadel(0)">删除</button>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" data-form-export="<?php echo url('excel'); ?>">导出EXCEL</button>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="shoukuan()">扫码收款</button>
						</div>
						<div class="layui-form layui-form-search">
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label"><?php echo t('会员'); ?>ID</label>
								<div class="layui-input-inline">
									<input type="text" name="mid" autocomplete="off" class="layui-input" value="">
								</div>
							</div>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">昵称</label>
								<div class="layui-input-inline">
									<input type="text" name="nickname" autocomplete="off" class="layui-input" value="">
								</div>
							</div>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">订单号</label>
								<div class="layui-input-inline">
									<input type="text" name="ordernum" autocomplete="off" class="layui-input">
								</div>
							</div>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">所属门店</label>
								<div class="layui-input-inline">
									<select name="mdid">
										<option value="">全部</option>
										<?php foreach($mdArr as $k=>$v): ?>
										<option value="<?php echo $k; ?>"><?php echo $v; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<div class="layui-inline">
								<label class="layui-form-label">付款时间</label>
								<div class="layui-input-inline" style="width:180px">
									<input type="text" name="ctime" id="ctime" autocomplete="off" class="layui-input">
								</div>
							</div>
							<div class="layui-inline">
								<button class="layui-btn layuiadmin-btn-replys" lay-submit="" lay-filter="LAY-app-forumreply-search">
									<i class="layui-icon layui-icon-search layuiadmin-button-btn"></i>
								</button>
							</div>
						</div>
						<div class="layui-col-md12">
							<table id="tabledata" lay-filter="tabledata"></table>
						</div>
          </div>
        </div>
    </div>
  </div>
  <script id="refundCheckTpl" type="text/html">
	  <div class="orderdetail">
		  <div class="orderinfo">
			  <div class="item">
				  <span class="t1">申请退款金额</span>
				  <span class="t2 red">￥{{d.refund_money}}</span>
			  </div>
			  <div class="item">
				  <span class="t1">申请退款原因</span>
				  <span class="t2 red">{{d.refund_reason}}</span>
			  </div>
			  <div class="item">
				  <span class="t1">申请退款时间</span>
				  <span class="t2">{{date('Y-m-d H:i:s',d.refund_time)}}</span>
			  </div>
			  <div class="item">
				  <span class="t1">审核备注</span>
				  <span class="t2">
					<textarea name="" id="" cols="30" rows="5">
						         {{d.refund_checkremark ? d.refund_checkremark : ''}}
					</textarea>
					  <!--					<input type="text" name="refund_checkremark" value="{{d.refund_checkremark ? d.refund_checkremark : ''}}" style="height:30px"/>-->
				</span>
			  </div>
		  </div>
	  </div>
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
	layui.laydate.render({ 
		elem: '#ctime',
		trigger: 'click',
		range: '~', //或 range: '~' 来自定义分割字符
		type:'datetime'
	});
  var table = layui.table;
	var datawhere = {};
  //数据表
  var tableIns = table.render({
    elem: '#tabledata'
    ,url: "<?php echo app('request')->url(); ?>" //数据接口
    ,page: true //开启分页
    ,cols: [[ //表头
			{type:"checkbox"},
      {field: 'id', title: 'ID',sort:true,width:80},
      {field: 'ordernum', title: '订单号',sort:true,width:160},
      {field: 'mdname', title: '所属门店'},
      {field: 'mid', title: '<?php echo t('会员'); ?>ID',width:80},
      {field: 'nickname', title: '<?php echo t('会员'); ?>信息',templet:'<div>{{# if(d.nickname){ }}<img src="{{d.headimg}}" style="width:50px"> {{d.nickname}}{{# } }}</div>'},
		  
      {field: 'money', title: '付款金额',sort:true,templet:function(d){return '￥'+d.money}},
      {field: 'paymoney', title: '实付金额',sort:true,templet:function(d){
	  	var html = '';
			html += '<p style="color:#f55555">￥'+d.paymoney+'</p>';
			  if(d.refund_money>0){
				  html += '<p style="color:red">退款金额:￥'+d.refund_money+'</p>';
				  if(d.refund_reason){
					  html += '<p style="color:red">退款原因:'+d.refund_reason+'</p>';
			  		}
			  }
			return html;
	  },minWidth:110},
      {field: 'disprice', title: '<?php echo t('会员'); ?>折扣',sort:true,templet:function(d){return '￥'+d.disprice}},
      {field: 'scoredk', title: '<?php echo t('积分'); ?>抵扣',sort:true,templet:function(d){return '￥'+d.scoredk}},
      {field: 'couponmoney', title: '<?php echo t('优惠券'); ?>抵扣',sort:true,templet:function(d){return '￥'+d.couponmoney}},
      
		  
      
      
      {field: 'paytime', title: '付款时间',sort:true,templet:function(d){ return date('Y-m-d H:i',d.paytime)}},
      {field: 'paytype', title: '付款方式'},
      {field: 'remark', title: '备注'},
      {field: 'uid', title: '操作员',templet:function(d){
	  		var html = '';
	  		if(d.uid){
	  			html = d.un+"(操作员ID:"+d.uid+')';
	  		}
	  		return html;
	  	}},
      {field: 'op', title: '操作',templet:function(d){
            var html = '<div>';
            html+='<button class="table-btn" onclick="datadel('+d.id+')">删除</button>';
			html+='<button class="table-btn" onclick="wifiprint('+d.id+')">打印小票</button>';
			if(d.can_refund_money>0){
				html+='<button class="table-btn" onclick="refund('+d.id+','+d.can_refund_money+')">退款</button>';
			}
			  <?php if($view_order_fenhong==1): ?>
				  // html += '<button class="table-btn" onclick="openmax(\'<?php echo url('Commission/record'); ?>/isopen/1/type/maidan/orderid/'+d.id+'\')">分销明细</button>'
			  html += '<button class="table-btn" onclick="openmax(\'<?php echo url('Commission/fenhonglog'); ?>/isopen/1/type/maidan/orderid/'+d.id+'\')">分红明细</button>'
			<?php endif; ?>
				
			html+='</div>';
            return html;
	  },width:100}
    ]]
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
		var ids = [];
		if(id==0){
			var checkStatus = table.checkStatus('tabledata')
			var checkData = checkStatus.data; //得到选中的数据
			if(checkData.length === 0){
				 return layer.msg('请选择数据');
			}
			var ids = [];
			for(var i=0;i<checkData.length;i++){
				ids.push(checkData[i]['id']);
			}
		}else{
			ids.push(id)
		}
		layer.confirm('确定要删除吗？删除后无法恢复！',{icon: 7, title:'操作确认'}, function(index){
			//do something
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('logdel'); ?>",{ids:ids},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}

	
	function shoukuan(){
		var html = '<div style="margin:20px auto;">';
		html+='<form class="layui-form form-label-w6" lay-filter="">';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label">付款码编号：</label>';
		html+='		<div class="layui-input-inline" style="width:200px">';
		html+='			<input type="text" name="auth_code" class="layui-input">';
		html+='		</div>';
		html+='		<div class="layui-form-mid layui-word-aux">请用扫码枪扫描微信付款码</div>';
		html+='	</div>';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label"><?php echo t('会员'); ?>信息：</label>';
		html+='		<div class="layui-form-mid layui-word-aux" id="memberinfo"></div>';
		html+='	</div>';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label">使用<?php echo t('余额'); ?>：</label>';
		html+='		<div class="layui-input-inline" style="width:40px">';
		html+='			<input type="checkbox" name="usemoney" value="1" lay-skin="primary" title="" checked>';
		html+='		</div>';
		html+='		<div class="layui-form-mid" ><span id="membermoney" style="color:green"></span></div>';
		html+='	</div>';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label">收款金额：</label>';
		html+='		<div class="layui-input-inline" style="width:200px">';
		html+='			<input type="text" name="skmoney" class="layui-input">';
		html+='		</div>';
		html+='		<div class="layui-form-mid layui-word-aux">请输入收款金额</div>';
		html+='	</div>';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label">实收金额：</label>';
		html+='		<div class="layui-form-mid" style="color:red;font-size:16px" id="realmoney"></div>';
		html+='	</div>';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label"></label>';
		html+='		<div class="layui-input-inline">';
		html+='			<button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit_shoukuan">确定收款</button>';
		html+='		</div>';
		html+='	</div>';
		html+='</div>';
		html+='</form>'
		layer.open({type:1,area:['600px','420px'],content:html,title:'扫码收款'});
		layui.form.render();
		var discount = 10;
		var mid = 0;
		var openid = '';
		var realmoney = 0;
		$('input[name=auth_code]').focus();
		$('input[name=skmoney]').bind('input change propertychange',function(){
			var skmoney = $(this).val();
			skmoney = skmoney*1
			if (isNaN(skmoney) || skmoney <= 0) {
				$('#realmoney').text('￥0.00');
				return;
			}
			realmoney = Math.round(skmoney * (0.1*discount) *100)/100;
			console.log(realmoney)
			$('#realmoney').text('￥'+realmoney.toFixed(2));
		});
		layui.form.on('submit(submit_shoukuan)', function(obj){
			var field = obj.field;
			if(!field.usemoney) field.usemoney = 0;
			
			if(field.auth_code && field.skmoney){
				var index= layer.load();
				$.post("<?php echo url('shoukuan'); ?>",{auth_code:field.auth_code,skmoney:field.skmoney,usemoney:field.usemoney,mid:mid,openid:openid,realmoney:realmoney.toFixed(2)},function(data){
					layer.close(index);
					dialog(data.msg,data.status,data.url);
				})
			}else if(field.auth_code){
				var index= layer.load();
				mid = 0;
				openid = '';
				realmoney = 0;
				$.post("<?php echo url('getmember'); ?>",field,function(data){
					layer.close(index);
					if(data.status==0){
						dialog(data.msg);
					}else if(data.status==2){
						$('#memberinfo').html(data.msg);
					}else{
						var memberinfo = data.member
						discount = data.discount;
						mid = memberinfo.id 
						openid = data.openid 
						var memberhtml = '';
						memberhtml+= '<img src="'+memberinfo.headimg+'" style="width:40px;height:40px"> '+memberinfo.nickname;
						memberhtml+= '&nbsp;&nbsp;<span style="color:red">['+memberinfo.levelname+'] ' + (discount!=10 ? discount+'折':'不打折') + '</span>';
						$('#memberinfo').html(memberhtml);
						$('#membermoney').html('可用<?php echo t('余额'); ?> ￥'+memberinfo.money);
						$('input[name=skmoney]').focus();
					}
				})
			}
			return false;
		})
	}
	function refund(id,can_refund_money){
		var html = '<div class="layui-tab-content">';
		html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
		html+='		<label class="layui-form-label" style="width:80px">退款金额</label>';
		html+='		<div class="layui-input-inline" style="width:350px;display: flex;align-items: center">';
		html+='			<input id="refundmoney" value="'+can_refund_money+'" class="layui-input" /> 元';
		html+='		</div>';
		html+='	</div>';
		html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
		html+='		<label class="layui-form-label" style="width:80px">退款原因</label>';
		html+='		<div class="layui-input-inline" style="width:350px">';
		html+='			<textarea type="text" id="tuireason" class="layui-textarea"></textarea>';
		html+='		</div>';
		html+='	</div>';
		html+='</div>';
		var refundLayer = layer.open({type:1,area:['580px','310px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
			yes:function(){
				var index = layer.load();
				$.post("<?php echo url('refund'); ?>",{id:id,reason:$('#tuireason').val(),money:$('#refundmoney').val()},function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(refundLayer);
					tableIns.reload()
				})
			}
		})
	}

	
	</script>
	
</body>
</html>