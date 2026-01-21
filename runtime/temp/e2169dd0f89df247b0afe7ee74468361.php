<?php /*a:4:{s:50:"/www/wwwroot/eivie/app/view/money/rechargelog.html";i:1755845280;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;s:49:"/www/wwwroot/eivie/app/view/public/copyright.html";i:1648714895;}*/ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>充值记录</title>
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
		.orderinfo{ width:100%;margin-top:5px;padding: 5px;background: #FFF;}
		.orderinfo .item{display:flex;width:100%;padding:10px 0;border-bottom:1px dashed #ededed;overflow:hidden}
		.orderinfo .item:last-child{ border-bottom: 0;}
		.orderinfo .item .t1{width:100px;flex-shrink:0}
		.orderinfo .item .t2{flex:1;text-align:right;}
		.orderinfo .item .red{color:red}

	</style>
</head>
<body>
  <div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-card layui-col-md12">
          <div class="layui-card-header"><i class="fa fa-list"></i> 充值记录
					<?php if(input('param.isopen')==1): ?><i class="layui-icon layui-icon-close" style="font-size:18px;font-weight:bold;cursor:pointer" onclick="closeself()"></i><?php endif; ?>
					</div>
          			<div class="layui-card-body" pad15>
						
						<div class="layui-col-md2" style="padding-bottom:10px">
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="datadel(0)">删除</button>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" data-form-export="<?php echo url('rechargelogexcel'); ?>">导出EXCEL</button>
						</div>
						<div class="layui-form layui-col-md10 layui-form-search">
							
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
							<?php if(getcustom('pay_transfer') && getcustom('money_recharge_transfer')): ?>
								<div class="layui-inline">
									<label class="layui-form-label">充值状态</label>
									<div class="layui-input-inline">
										<select name="status">
											<option value="">全部</option>
											<option value="1" <?php if(input('param.status')==='1'): ?>selected<?php endif; ?>>已充值</option>
											<option value="2" <?php if(input('param.status')==='2'): ?>selected<?php endif; ?>>未充值</option>
										</select>
									</div>
								</div>
								<div class="layui-inline">
									<label class="layui-form-label">转账审核</label>
									<div class="layui-input-inline">
										<select name="transfer_check">
											<option value="">全部</option>
											<option value="0" <?php if(input('param.transfer_check')==='0'): ?>selected<?php endif; ?>>待审核</option>
											<option value="1" <?php if(input('param.transfer_check')==='1'): ?>selected<?php endif; ?>>已通过</option>
											<option value="-1" <?php if(input('param.transfer_check')==='-1'): ?>selected<?php endif; ?>>已驳回</option>
										</select>
									</div>
								</div>
							<?php endif; if(getcustom('recharge_use_mendian')): ?>
							<div class="layui-inline">
								<label class="layui-form-label">所属门店</label>
								<div class="layui-input-inline">
									<select name="mdid">
										<option value="">全部</option>
										<?php foreach($mdlist as $val): ?>
										<option value="<?php echo $val['id']; ?>" ><?php echo $val['name']; ?></option>
									    <?php endforeach; ?>
									</select>
								</div>
							</div>
							<?php endif; ?>
							<div class="layui-inline">
								<label class="layui-form-label">充值时间</label>
								<div class="layui-input-inline" style="width:180px">
									<input type="text" name="ctime" id="ctime" autocomplete="off" class="layui-input">
								</div>
							</div>
							<!-- <div class="layui-inline">
								<label class="layui-form-label">状态</label>
								<div class="layui-input-inline">
									<select name="status">
										<option value="">全部</option>
										<option value="1">充值成功</option>
										<option value="0">充值失败</option>
									</select>
								</div>
							</div> -->
							<div class="layui-inline">
								<button class="layui-btn layuiadmin-btn-replys" lay-submit="" lay-filter="LAY-app-forumreply-search">
									<i class="layui-icon layui-icon-search layuiadmin-button-btn"></i>
								</button>
							</div>
						</div>
						<div class="layui-col-md12" style="padding-bottom:10px;text-align: right">
							<div class="layui-inline " >
								<label class="layui-form-label" style="width: auto">小计充值金额：</label>
								<div class="layui-input-inline" style="line-height: 38px;font-weight: bold" id="totalmoney"></div>
							</div>
							<?php if(getcustom('member_recharge_detail_refund')): ?>
							<div class="layui-inline ">
								<label class="layui-form-label" style="width: auto">小计退款金额：</label>
								<div class="layui-input-inline" style="line-height: 38px;font-weight: bold" id="totalrefundmoney"></div>
							</div>

							<div class="layui-inline ">
								<label class="layui-form-label" style="width: auto">总计充值入账（实收金额）：</label>
								<div class="layui-input-inline" style="line-height: 38px;font-weight: bold" id="totalrealmoney"></div>
							</div>
							<div class="layui-inline " >
								<label class="layui-form-label" style="width: auto">小计充值赠送：</label>
								<div class="layui-input-inline" style="line-height: 38px;font-weight: bold" id="totalgivemoney"></div>
							</div>
							<?php endif; ?>
						</div>
						<div class="layui-col-md12">
							<table id="tabledata" lay-filter="tabledata"></table>
						</div>
          </div>
        </div>
    </div>
  </div>
  <?php if(getcustom('member_recharge_detail_refund')): ?>
  <script id="detailtpl" type="text/html">
	  
	  <div class="orderdetail">
		  <div class="orderinfo">
			  <div class="item">
				  <span class="t1">会员信息</span>
				  <span class="t2"><img src="{{d.detail.headimg}}" style="width:40px"/> {{d.detail.nickname}} {{# if(d.detail.realname){ }}{{#} }}</span>
			  </div>
			  <div class="item">
				  <span class="t1">充值金额</span>
				  <span class="t2 red">￥{{d.detail.money}}</span>
			  </div>
			  <div class="item">
				  <span class="t1">赠送金额</span>
				  <span class="t2 red">￥{{d.detail.give_money}}</span>
			  </div>
			  {{# if(d.detail.refund_money >0 ){ }}
			  <div class="item">
				  <span class="t1">退款金额</span>
				  <span class="t2 red">￥{{d.detail.refund_money}}</span>
			  </div>
			  <div class="item">
				  <span class="t1">退款时间</span>
				  <span class="t2 ">{{date('Y-m-d H:i',d.detail.refund_time)}}</span>
			  </div>
			  {{# } }}
			  <div class="item">
				  <span class="t1">充值时间</span>
				  <span class="t2">{{date('Y-m-d H:i',d.detail.createtime)}}</span>
			  </div>
			  <div class="item">
				  <span class="t1">支付方式</span>
				  <span class="t2">{{d.detail.paytype}}</span>
			  </div>
			  <div class="item">
				  <span class="t1">付款单号</span>
				  <span class="t2">{{d.detail.paynum}}</span>
			  </div>
			  <div class="item">
				  <span class="t1">付款时间</span>
				  <span class="t2">{{date('Y-m-d H:i',d.detail.paytime)}}</span>
			  </div>
			  <div class="item">
				  <span class="t1">状态</span>
				  <span class="t2">
					  {{# if(d.detail.status ==0){ }}
					  充值失败
					  {{# } }}	
					 {{# if(d.detail.status ==1){ }}
					  充值成功
					  {{# } }}	
				  </span>
			  </div>
			  
		  </div>
	  </div>
  </script>
  <script id="refundtpl" type="text/html">
	  <div class="layui-form-item" style="margin:40px 20px 0px 20px">
		  <label class="layui-form-label" style="width:90px">退款金额</label>
		  <div class="layui-input-inline" style="width:180px">
			  <input type="number" id="refund_money" class="layui-input" value="">
		  </div>
	  </div>
	  <div class="layui-form-item" style="margin:20px 20px 0px 20px">
		  <label class="layui-form-label" style="width:90px">退款赠送金额</label>
		  <div class="layui-input-inline" style="width:180px">
			  <input type="number" id="refund_give_money" class="layui-input" value="">
		  </div>
		  <div class="layui-form-mid " style="color:red;margin-left: 20px">* 建议退款前先查询此消费者的消费明细，确定退款后无法撤销，损失无法挽回</div>
	  </div>
	  <div class="layui-form-item" style="margin:0px 20px 0px 20px">
		  <label class="layui-form-label" style="width:80px">退款原因</label>
		  <div class="layui-input-inline" style="width:180px">
			  <input type="text" id="refund_reason" class="layui-input" value="">
		  </div>
	  </div>
  </script>
  <?php endif; ?>
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
      //{field: 'id', title: 'ID',sort:true,width:80},
      {field: 'mid', title: '<?php echo t('会员'); ?>ID',width:80},
	  <?php if(getcustom('recharge_use_mendian')): ?>
		  {field: 'mendian_name', title: '所属门店',width:80},
		  {field: 'uid', title: '操作员',templet:function(d){
			  var html = '';
			  if(d.uid){
				  html = d.un+"(操作员ID:"+d.uid+')';
			  }
			  return html;
		  }},
	  <?php endif; ?>
      {field: 'nickname', title: '<?php echo t('会员'); ?>信息',templet:'<div>{{# if(d.nickname){ }}<img src="{{d.headimg}}" style="width:50px"> {{d.nickname}}{{# } }}</div>'},
      {field: 'money', title: '充值金额',sort:true,width:120},
		  <?php if(getcustom('member_recharge_detail_refund')): ?>
		  {field: 'give_money', title: '赠送金额',sort:true,width:120},
		  {field: 'refund_money', title: '退款金额',sort:true,width:120,templet:function(d){ 
		  	if(d.refund_money >0){
		  	     return '<span style="color:red;">'+d.refund_money+'</span>';
			  }else {
				return 	'<span >'+d.refund_money+'</span>';
			}
		  }},
		  {field: 'refund_give_money', title: '退款赠送金额',sort:true,width:120,templet:function(d){
				  if(d.refund_give_money >0){
					  return '<span style="color:red;">'+d.refund_give_money+'</span>';
				  }else {
					  return 	'<span >'+d.refund_give_money+'</span>';
				  }
			  }},
		  <?php endif; ?>
      {field: 'createtime', title: '充值时间',sort:true,templet:function(d){ return date('Y-m-d H:i',d.createtime)}},
			{field: 'paytype', title: '支付方式'},
      {field: 'paynum', title: '订单号/付款单号',templet:function(d){
      	var html ='';
      	html+='<div>订 单 号：'+d.ordernum+'</div>';
				if(d.paynum){
					html+='<div>付款单号：'+d.paynum+'</div>';
				}
		return html;
	  },width:210},
      {field: 'paytime', title: '付款时间',templet:function(d){ if(d.paytime) return date('Y-m-d H:i',d.paytime);else return ''}},
      {field: 'status_name', title: '状态',width:120},
      // {field: 'op', title: '操作',width:100,templet:'<div><button class="table-btn" onclick="datadel({{d.id}})">删除</button></div>'},
		  {field: 'op', title: '操作',templet:function(d){
			var html='<div>'
					if(d.money_recharge_transfer && d.paytypeid==5 && d.payorder && d.payorder.check_status>=0 && d.paytype != '随行付支付'){
						if(d.transfer_check == 1){
							html += '<button class="table-btn" onclick="doOperation('+d.id+',\'付款凭证\')">付款凭证</button>';
						}else if(d.transfer_check == 0){
							html += '<button class="table-btn" onclick="doOperation('+d.id+',\'转账审核\')">转账审核</button>';
						}else if(d.transfer_check == -1){
							html += '<button class="table-btn" style="background:#eee">转账已驳回</button>';
						}
					}
				  <?php if(getcustom('recharge_order_wifiprint')): ?>
				  html += '<button class="table-btn" onclick="wifiprint('+d.id+')">打印小票</button>';
				  <?php endif; if(getcustom('member_recharge_detail_refund')): ?>
				  html+='<button class="table-btn" onclick="showdetail('+d.id+')">详情</button>';
				  <?php endif; if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('MoneyRechargelogDel',$auth_data))): ?>
			html+='<button class="table-btn" onclick="datadel('+d.id+')">删除</button>';
			<?php endif; ?>
		  	html +='</div>';
			return html;
		  }}
    ]] 	,done:function(res, curr, count){
		  if(curr == 1){
			  $('#totalmoney').html('￥'+res.tdata.total_money);
			  <?php if(getcustom('member_recharge_detail_refund')): ?>
			  $('#totalrefundmoney').html('￥'+res.tdata.total_refund_money);
			  $('#totalrealmoney').html('￥'+res.tdata.total_real_money);
			  $('#totalgivemoney').html('￥'+res.tdata.total_give_money);
			  <?php endif; ?>
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

	function wifiprint(id){
		layer.confirm('确定要打印小票吗?',{icon: 7, title:'操作确认'}, function(index){
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('rechargeprint'); ?>",{id:id},function(data){
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
			$.post("<?php echo url('rechargelogdel'); ?>",{ids:ids},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}
	<?php if(getcustom('member_recharge_detail_refund')): ?>
	var detailLayer;
	function showdetail(id){
		var index = layer.load();
		$.post("<?php echo url('getrechargedetail'); ?>",{id:id},function(data){
			layer.close(index);
			var html = '';
			layui.laytpl(detailtpl.innerHTML).render(data, function(html){
				var detailBtn = [];
				if(data.detail.refund_money > 0){
					detailBtn.push('退款打印');
				}
				if(data.detail.status==1 && data.detail.refund_status ==0){ //
					detailBtn.push('退款');
				}
				detailLayer = layer.open({type:1,area:'500px',title:'充值详情',content:html,resize:true,shadeClose:true,btn:detailBtn,
					yes:function(){
						return btnOperation(detailBtn[0],data);
					} ,
					btn2:function(){
						return btnOperation(detailBtn[1],data);
					},
				});
			});
		})
	}
	function btnOperation(type,data){
		if(type == '退款'){
			var orderid = data.detail.id;
			layui.laytpl(refundtpl.innerHTML).render({}, function(html){
				var refundLayer = layer.open({type:1,title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
					yes:function(){
						var index = layer.load();
						$.post("<?php echo url('rechargerefund'); ?>",{orderid:orderid,money:$('#refund_money').val(),give_money:$('#refund_give_money').val(),refund_reason:$('#refund_reason').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(refundLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					}
				})
			});
		}
		if(type == '退款打印'){
			<?php if(getcustom('member_recharge_detail_refund')): ?> 
			var orderid = data.detail.id;
			layer.confirm('确定要打印小票吗?',{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('rechargerefundprint'); ?>",{id:orderid},function(data){
					layer.close(index);
					dialog(data.msg,data.status);
				})
			})
			<?php endif; ?>
		}
	}
	<?php endif; if(getcustom('money_recharge_transfer')): ?>
	var orderdata = {};
	function doOperation(orderid,type){
		var index_load = layer.load();
		$.post("<?php echo url('getrechargeorderdetail'); ?>",{orderid:orderid,optionType:type},function(data){
			layer.close(index_load);
			orderdata = data;
			btnOperation(type,data);
		})
	}
	function btnOperation(type,data) {
		var orderid = data.order.id;
		var ann = ['确认已支付', '驳回'];
		if (type == '付款凭证') {
			layui.laytpl(payCheckTpl.innerHTML).render(data.payorder, function (html) {
				var refundCheckLayer = layer.open({
					type: 1, title: false, area: 'auto', content: html, shadeClose: true, btn: ann,
					yes: function () {
						var index = layer.load();
						$.post("<?php echo url('payCheck'); ?>", {
							orderid: orderid,
							st: 1,
							remark: $('input[name=check_remark]').val()
						}, function (res) {
							layer.close(index);
							dialog(res.msg, res.status);
							layer.close(refundCheckLayer);
							tableIns.reload()
						})
					},
					btn2: function () {
						var index = layer.load();
						$.post("<?php echo url('payCheck'); ?>", {
							orderid: orderid,
							st: 2,
							remark: $('input[name=check_remark]').val()
						}, function (res) {
							layer.close(index);
							dialog(res.msg, res.status);
							layer.close(refundCheckLayer);
							tableIns.reload()
						})
					},
				})
			})
		}
		if (type == '转账审核') {
			var html = '';
			var refundCheckLayer = layer.open({type: 1, title: '转账审核', content: html, shadeClose: true, btn: ['同意可转账', '驳回可转账'],
				yes: function () {
					var index = layer.load();
					$.post("<?php echo url('transferCheck'); ?>", {orderid: orderid, st: 1}, function (res) {
						layer.close(index);
						dialog(res.msg, res.status);
						layer.close(refundCheckLayer);
						tableIns.reload()
					})
				},
				btn2: function () {
					var index = layer.load();
					$.post("<?php echo url('transferCheck'); ?>", {orderid: orderid, st: -1}, function (res) {
						layer.close(index);
						dialog(res.msg, res.status);
						layer.close(refundCheckLayer);
						tableIns.reload()
					})
				}
			})
		}
	}
	<?php endif; ?>
	</script>
	<?php if(getcustom('pay_transfer')): ?>
	<script id="payCheckTpl" type="text/html">
		<div class="orderdetail">
			<div class="orderinfo">
				<div class="item">
					<span class="t1">审核状态</span>
					<span class="t2 red">{{d.check_status_label}}</span>
				</div>
				<div class="item">
					<span class="t1">凭证图片</span>
					<span class="t2 red">
					{{#  if(d.paypics){ }}
					{{d.paypics_html}}
					{{# } }}
				</span>
				</div>
				<div class="item">
					<span class="t1">审核备注</span>
					<span class="t2"><input type="text" name="check_remark" value="{{d.check_remark ? d.check_remark : ''}}" style="height:30px"/></span>
				</div>
				{{#  if(d.transfer_order_parent_check){ }}
				<div class="item">
					<span class="t1">订单操作记录</span>
				</div>
				<div class="item">
					{{# if((d.transfer_order_parent_check_log.length>0)){ }}
					<ul class="layui-timeline">
						{{#  layui.each(d.transfer_order_parent_check_log, function(index2, item2){ }}
						<li class="layui-timeline-item">
							<i class="layui-icon layui-timeline-axis {{index2==0?'on':''}}"></i>
							<div class="layui-timeline-content layui-text">
								<p>{{item2.cztime}}</p>
								<p>
									{{item2.info}}
								</p>
							</div>
						</li>
						{{# }); }}
					</ul>
					{{# }else{ }}
					<div style="padding:20px 0 20px 0;font-size:16px">暂无信息</div>
					{{# } }}
				</div>
				{{# } }}
			</div>
		</div>
	</script>
	<?php endif; ?>
	
</body>
</html>