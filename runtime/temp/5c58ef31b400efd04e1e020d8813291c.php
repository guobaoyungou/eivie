<?php /*a:4:{s:50:"/www/wwwroot/eivie/app/view/money/withdrawlog.html";i:1764308965;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;s:49:"/www/wwwroot/eivie/app/view/public/copyright.html";i:1648714895;}*/ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>提现记录</title>
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
          <div class="layui-card-header"><i class="fa fa-list"></i> 提现记录<?php if(input('param.isopen')==1): ?><i class="layui-icon layui-icon-close" style="font-size:18px;font-weight:bold;cursor:pointer" onclick="closeself()"></i><?php endif; ?></div>
          <div class="layui-card-body" pad15>
						<div class="layui-col-md4" style="padding-bottom:10px;">
							<!-- <button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="setst(0,1)">审核</button>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="setst(0,2)">驳回</button> -->
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="datadel(0)">删除</button>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" data-form-export="<?php echo url('withdrawlogexcel'); ?>">导出EXCEL</button>
							
						</div>
						<div class="layui-form layui-col-md8 layui-form-search">
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label"><?php echo t('会员'); ?>ID</label>
								<div class="layui-input-inline">
									<input type="text" name="mid" autocomplete="off" class="layui-input" value="<?php echo app('request')->param('mid'); ?>">
								</div>
							</div>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">昵称</label>
								<div class="layui-input-inline">
									<input type="text" name="nickname" autocomplete="off" class="layui-input" value="<?php echo app('request')->param('nickname'); ?>">
								</div>
							</div>
							<div class="layui-inline">
								<label class="layui-form-label">状态</label>
								<div class="layui-input-inline">
									<select name="status">
										<option value="">全部</option>
										<option value="0">审核中</option>
										<option value="1">已审核</option>
										<option value="2">已驳回</option>
										<option value="3">已打款</option>
									</select>
								</div>
							</div>

							<div class="layui-inline">
								<label class="layui-form-label">提现时间</label>
								<div class="layui-input-inline" style="width:200px">
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
    ,url: '<?php echo app('request')->url(); ?>' //数据接口
    ,page: true //开启分页
    ,cols: [[ //表头
			{type:"checkbox"},
      //{field: 'id', title: 'ID',sort:true,width:80},
      {field: 'mid', title: '<?php echo t('会员'); ?>ID',width:80},
      {field: 'nickname', title: '<?php echo t('会员'); ?>信息',templet:'<div>{{# if(d.nickname){ }}<img src="{{d.headimg}}" style="width:50px"> {{d.nickname}}{{# } }}</div>'},
		  
		{field: 'tel', title: '手机号',width:120},
      {field: 'txmoney', title: '提现金额',width:120},
      {field: 'money', title: '打款金额',sort:true,width:120},
		  <?php if(getcustom('product_givetongzheng')){ ?>
		  {field: 'tongzheng', title: '<?php echo t("通证数量"); ?>',width:80},
	  <?php } ?>
      {field: 'paytype', title: '提现方式',width:120},
      {field: 'aliaccount', title: '收款账号',templet:function(d){ 
      	var html = '<div>';
      	if(d.paytype=="支付宝" || d.paytype=="小额通支付宝" || d.paytype=="灵活薪支付宝"){
      		html += d.aliaccountname+'<br>'+d.aliaccount;
      	}else if(d.paytype=="收款码"){
      		if(d.wxpaycode){
      			html += '<img src="'+d.wxpaycode+'" onclick="previewImg(\''+d.wxpaycode+'\')" style="width:120px;height:auto;margin-right: 10px">';
      		}
      		if(d.alipaycode){
      			html += '<img src="'+d.alipaycode+'" onclick="previewImg(\''+d.alipaycode+'\')" style="width:120px;height:auto">';
      		}
      	}else if(d.paytype=="银行卡" || d.paytype=="小额通银行卡"  || d.paytype=="灵活薪银行卡"){
      		html += d.bankname+'<br>'+d.bankcarduser+'<br>'+d.bankcardnum;
      	}else if(d.customwithdraw === 1){
            html += d.customaccountname+'<br>'+d.customaccount+'<br>'+d.customtel;
        }
      	html += "</div>";
      	return html;
      }},
		  {field: 'realname', title: '身份信息' ,templet:function(d){
				  var  html = '';
				  if(d.realname)html += d.realname+'<br>';
				  if(d.usercard)html += d.usercard;
				  return html;
			  }},
      {field: 'createtime', title: '提现时间',sort:true,templet:function(d){ return date('Y-m-d H:i',d.createtime)}},
      {field: 'status', title: '状态',width:150,templet:function(d){ 
      		var html = '';
					if(d.status==0) html += '<p style="color:#555">审核中</p>';
					if(d.status==1) html += '<p style="color:#58a">已审核</p>';
					if(d.status==2) html += '<p style="color:#aaa">已驳回</p>';
					if(d.status==3) html += '<p style="color:green">已打款</p>';
			  		if(d.status==4) {
			  			if(d.wx_transfer_bill_no){
							html += '<p style="color:orange">待用户确认<span style="color:#aaa;margin-right:15px;cursor:pointer" onclick="stQuery('+d.id+')"><i class="fa fa-refresh"></i></span></p>';
						}else{
							html += '<p style="color:orange">处理中<span style="color:#aaa;margin-right:15px;cursor:pointer" onclick="stQuery('+d.id+')"><i class="fa fa-refresh"></i></span></p>';
						}
					}
					if(d.reason){
						html += '<p style="color:red">'+d.reason+'</p>';
					}
				return html;
			}},
      {field: 'operation', title: '操作',templet:function(d){ 
				var ht = '';
				if(d.status==0){
					<?php if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('MoneyWithdrawAudit',$auth_data))): ?>
					ht+= '<button class="table-btn" onclick="setst('+d.id+',1)">审核</button>';
					<?php endif; if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('MoneyWithdrawReject',$auth_data))): ?>
					ht+= '<button class="table-btn" onclick="setst('+d.id+',2)">驳回</button>';
					<?php endif; ?>
				}
				if(d.status==1){
					<?php if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('MoneyWithdrawDakuan',$auth_data))): ?>
					ht+= '<button class="table-btn" onclick="setst('+d.id+',3)">改为已打款</button>';
					<?php endif; ?>
					if(d.paytype=='微信钱包'){
						<?php if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('MoneyWithdrawWxDakuan',$auth_data))): ?>
						ht+= '<button class="table-btn" onclick="setst('+d.id+',10)">微信打款</button>';
						<?php endif; ?>
					}
					
					
					
					
					
					<?php if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('MoneyWithdrawReject',$auth_data))): ?>
					ht+= '<button class="table-btn" onclick="setst('+d.id+',2)">驳回</button>';
					<?php endif; ?>
				}
				if(d.status==2) {};
				if(d.status==3) {};
				<?php if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('MoneyWithdrawDel',$auth_data))): ?>
				ht+= '<button class="table-btn" onclick="datadel('+d.id+')">删除</button>';
				<?php endif; ?>
				return ht;
			},width:200}
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

	  function stQuery(id){
		  var index = layer.load();
		  $.post("<?php echo url('withdrawlogQuery'); ?>",{id:id},function(res){
			  layer.close(index);
			  dialog(res.msg,res.status);
			  tableIns.reload()
		  });
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
			$.post("<?php echo url('withdrawlogdel'); ?>",{ids:ids},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}
	//改状态
	function setst(id,st){
		if(st==2){ //驳回
			var html = '';
			html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
			html+='		<label class="layui-form-label" style="width:80px">驳回原因</label>';
			html+='		<div class="layui-input-inline" style="width:350px">';
			html+='			<input type="text" id="tuireason" class="layui-input"/>';
			html+='		</div>';
			html+='	</div>';
			var refundLayer = layer.open({type:1,area:['500px','250px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
				yes:function(){
					var index = layer.load();
					$.post("<?php echo url('withdrawlogsetst'); ?>",{id:id,st:st,reason:$('#tuireason').val()},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(refundLayer);
						tableIns.reload()
					})
				}
			})
		}else{
			var msg = '';
			if(st==1){
				msg = '确定要审核通过吗?';
			}
			if(st==2){
				msg = '确定要驳回吗?';
			}
			if(st==3){
				msg = '确定您已经通过其他方式打款给用户了吗？此操作仅修改状态，并不会进行打款。';
			}
			if(st==10){
				msg = '确定要微信打款吗?';
			}
			
			
			
			
			
			layer.confirm(msg,{icon: 7, title:'操作确认'}, function(index){
				//do something
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('withdrawlogsetst'); ?>",{id:id,st:st,reason:''},function(data){
					layer.close(index);
					dialog(data.msg,data.status);
					tableIns.reload()
				})
			});
		}
	}
	</script>
	
</body>
</html>