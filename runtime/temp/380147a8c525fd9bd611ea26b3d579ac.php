<?php /*a:4:{s:49:"/www/wwwroot/eivie/app/view/restaurant/index.html";i:1717657336;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;s:49:"/www/wwwroot/eivie/app/view/public/copyright.html";i:1648714895;}*/ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>欢迎使用</title>
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
	.layui-col-sm6,.layui-col-sm12{padding-top:0;padding-left:0}
	</style>
</head>
<body>
  <div class="layui-fluid">
    <div class="layui-row layui-col-space15">

       <!-- <div class="layui-col-sm6 layui-col-md3">
        <div class="layui-card">
          <div class="layui-card-header">客户总数</div>
          <div class="layui-card-body layuiadmin-card-list">
            <p class="layuiadmin-big-font" style="cursor:pointer" onclick="location.href='<?php echo url('member/index'); ?>'"><?php echo number_format($memberCount); ?></p>
            <p>昨日新增 <span class="layuiadmin-span-color"><?php echo number_format($memberLastDayCount); ?></span></p>
            <p>本月新增 <span class="layuiadmin-span-color"><?php echo number_format($memberThisMonthCount); ?></span></p>
          </div>
        </div>
      </div>
			<div class="layui-col-sm6 layui-col-md3">
        <div class="layui-card">
          <div class="layui-card-header">收款金额</div>
          <div class="layui-card-body layuiadmin-card-list">
            <p class="layuiadmin-big-font" style="color:#558800"><span style="font-size:16px;height:20px;line-height:20px">￥</span><?php echo number_format($wxpayCount,2); ?></p>
            <p>昨日新增 <span class="layuiadmin-span-color">￥<?php echo number_format($wxpayLastDayCount,2); ?></span></p>
            <p>本月新增 <span class="layuiadmin-span-color">￥<?php echo number_format($wxpayThisMonthCount,2); ?></span></p>
          </div>
        </div>
      </div>
			<div class="layui-col-sm6 layui-col-md3">
        <div class="layui-card">
          <div class="layui-card-header">已提现金额</div>
          <div class="layui-card-body layuiadmin-card-list">
            <p class="layuiadmin-big-font"><span style="font-size:16px;height:20px;line-height:20px">￥</span><?php echo number_format($withdrawCount,2); ?></p>
            <p>昨日新增 <span class="layuiadmin-span-color">￥<?php echo number_format($withdrawLastDayCount,2); ?></span></p>
            <p>本月新增 <span class="layuiadmin-span-color">￥<?php echo number_format($withdrawThisMonthCount,2); ?></span></p>
          </div>
        </div>
      </div>-->
			<div class="layui-col-sm6 layui-col-md3">
        <div class="layui-card">
          <div class="layui-card-header">菜品数量</div>
          <div class="layui-card-body layuiadmin-card-list">
            <p class="layuiadmin-big-font" style="cursor:pointer" onclick="location.href='<?php echo url('RestaurantProduct/index'); ?>'"><?php echo number_format($data['productCount']); ?></p>
            <p>未上架 <span class="layuiadmin-span-color"><?php echo number_format($data['productCount0']); ?></span></p>
            <p>已上架 <span class="layuiadmin-span-color"><?php echo number_format($data['productCount1']); ?></span></p>
          </div>
        </div>
      </div>
        <div class="layui-col-sm6 layui-col-md3">
            <div class="layui-card">
              <div class="layui-card-header">堂食订单</div>
              <div class="layui-card-body layuiadmin-card-list">
                <p class="layuiadmin-big-font" style="cursor:pointer" onclick="location.href='<?php echo url('RestaurantShopOrder/index'); ?>'"><?php echo number_format($data['shopOrderCount']); ?></p>
                <p>昨日新增 <span class="layuiadmin-span-color"><?php echo number_format($data['shopOrderLastDayCount']); ?></span></p>
                <p>本月新增 <span class="layuiadmin-span-color"><?php echo number_format($data['shopOrderThisMonthCount']); ?></span></p>
              </div>
            </div>
        </div>
        <div class="layui-col-sm6 layui-col-md3">
            <div class="layui-card">
              <div class="layui-card-header">堂食订单金额</div>
              <div class="layui-card-body layuiadmin-card-list">
                <p class="layuiadmin-big-font" style="cursor:pointer" onclick="location.href='<?php echo url('RestaurantShopOrder/index'); ?>'"><?php echo number_format($data['shopOrderMoney'],2); ?></p>
                <p>昨日新增 <span class="layuiadmin-span-color"><?php echo number_format($data['shopOrderMoneyLastDayCount']); ?></span></p>
                <p>本月新增 <span class="layuiadmin-span-color"><?php echo number_format($data['shopOrderMoneyThisMonthCount']); ?></span></p>
              </div>
            </div>
        </div>
        <div class="layui-col-sm6 layui-col-md3">
            <div class="layui-card">
                <div class="layui-card-header">堂食订单退款金额</div>
                <div class="layui-card-body layuiadmin-card-list">
                    <p class="layuiadmin-big-font" style="color:#885500"><span style="font-size:16px;height:20px;line-height:20px">￥</span><?php echo number_format($data['refundMoney'],2); ?></p>
                    <p>昨日新增 <span class="layuiadmin-span-color">￥<?php echo number_format($data['refundMoneyLastDay'],2); ?></span></p>
                    <p>本月新增 <span class="layuiadmin-span-color">￥<?php echo number_format($data['refundMoneyThisMonth'],2); ?></span></p>
                </div>
            </div>
        </div>

        <div class="layui-col-sm6 layui-col-md3">
            <div class="layui-card">
                <div class="layui-card-header">餐桌</div>
                <div class="layui-card-body layuiadmin-card-list">
                    <p class="layuiadmin-big-font" style="cursor:pointer" onclick="location.href='<?php echo url('RestaurantTable/index'); ?>'"><?php echo number_format($data['tableCount']); ?></p>
                    <p>空闲 <span class="layuiadmin-span-color"><?php echo number_format($data['tableCount0']); ?></span></p>
                    <p>入座 <span class="layuiadmin-span-color"><?php echo number_format($data['tableCount2']); ?></span></p>
                </div>
            </div>
        </div>
        <div class="layui-col-sm6 layui-col-md3">
            <div class="layui-card">
                <div class="layui-card-header">外卖订单</div>
                <div class="layui-card-body layuiadmin-card-list">
                    <p class="layuiadmin-big-font" style="cursor:pointer" onclick="location.href='<?php echo url('RestaurantTakeawayOrder/index'); ?>'"><?php echo number_format($data['takeawayOrderCount']); ?></p>
                    <p>昨日新增 <span class="layuiadmin-span-color"><?php echo number_format($data['takeawayOrderLastDayCount']); ?></span></p>
                    <p>本月新增 <span class="layuiadmin-span-color"><?php echo number_format($data['takeawayOrderThisMonthCount']); ?></span></p>
                </div>
            </div>
        </div>
        <div class="layui-col-sm6 layui-col-md3">
            <div class="layui-card">
                <div class="layui-card-header">外卖订单金额</div>
                <div class="layui-card-body layuiadmin-card-list">
                    <p class="layuiadmin-big-font" style="cursor:pointer" onclick="location.href='<?php echo url('RestaurantTakeawayOrder/index'); ?>'"><?php echo number_format($data['takeawayOrderMoney'],2); ?></p>
                    <p>昨日新增 <span class="layuiadmin-span-color"><?php echo number_format($data['takeawayOrderMoneyLastDayCount']); ?></span></p>
                    <p>本月新增 <span class="layuiadmin-span-color"><?php echo number_format($data['takeawayOrderMoneyThisMonthCount']); ?></span></p>
                </div>
            </div>
        </div>
        <div class="layui-col-sm6 layui-col-md3">
            <div class="layui-card">
              <div class="layui-card-header">外卖订单退款金额</div>
              <div class="layui-card-body layuiadmin-card-list">
                <p class="layuiadmin-big-font" style="color:#885500"><span style="font-size:16px;height:20px;line-height:20px">￥</span><?php echo number_format($data['takeawayRefundMoney'],2); ?></p>
                <p>昨日新增 <span class="layuiadmin-span-color">￥<?php echo number_format($data['takeawayRefundMoneyLastDay'],2); ?></span></p>
                <p>本月新增 <span class="layuiadmin-span-color">￥<?php echo number_format($data['takeawayRefundMoneyThisMonth'],2); ?></span></p>
              </div>
            </div>
        </div>
        
      <div class="layui-col-sm12">
				<div class="layui-card"> 
					<div class="layui-card-header">最近30天数据曲线</div>
					<div class="layui-card-body">
						<div class="layui-row">
							<div class="layui-col-sm9">
								<div class="layui-carousel layadmin-carousel layadmin-dataview" style="width:100%" id="chart1"></div>
							</div>
							<div class="layui-col-sm3">
								<div style="padding-top:70px"></div>
								<?php if($bid == 0): ?>
								<div style="margin:5px 0;"><span onclick="getchart(1,'客户总数')" style="cursor:pointer">客户总数</span></div>
								<div style="margin:5px 0;"><span onclick="getchart(2,'新增客户数')" style="cursor:pointer">新增客户数</span></div>
								<div style="margin:5px 0;"><span onclick="getchart(3,'收款金额')" style="cursor:pointer">收款金额</div>
								<?php endif; ?>
								<div style="margin:5px 0;"><span onclick="getchart(4,'订单金额')" style="cursor:pointer">订单金额</div>
								<div style="margin:5px 0;"><span onclick="getchart(5,'订单数')" style="cursor:pointer">订单数</div>
							</div>
						</div>
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
	<script src="/static/admin/js/echarts.min.js"></script>
	<script type="text/javascript">
	var nowtime = "<?php echo time(); ?>";
	$(function(){
		setInterval(function(){
			nowtime++;
			var date = getLocalTime(nowtime);
			$('#systime').html(date);
		},1000)
	})
	function getLocalTime(time) {     
		var date = new Date(time * 1000);  
		var year = date.getFullYear();
		var month = date.getMonth() + 1;
		var day = parseInt(date.getDate());
		var hour = date.getHours();
		var minute = date.getMinutes();
		var second = date.getSeconds();
		return year + '-' + (month <10 ? '0' + month : month) + '-' + (day < 10 ? ('0' + day) : day) + ' '+(hour <10 ? '0' + hour : hour)+':'+(minute <10 ? '0' + minute : minute)+':'+(second <10 ? '0' + second : second);  
	}
	var myChart = echarts.init(document.getElementById("chart1"));
	<?php if($bid == 0): ?>
	option = {
		title: {
			text: '客户数',textStyle:{fontSize:12},x: "center"
		},
		tooltip: {
			trigger: 'axis'
		},
		xAxis: {
			//name:'日期',
			boundaryGap: false,
			type: 'category',
			data: <?php echo json_encode($dateArr); ?>
		},
		yAxis: {
			type: 'value'
		},
		series: [{
			name:'客户数',
			data: <?php echo json_encode($dataArr); ?>,
			type: 'line'
		}]
	}
	<?php else: ?>
	option = {
		title: {
			text: '订单金额',textStyle:{fontSize:12},x: "center"
		},
		tooltip: {
			trigger: 'axis'
		},
		xAxis: {
			//name:'日期',
			boundaryGap: false,
			type: 'category',
			data: <?php echo json_encode($dateArr); ?>
		},
		yAxis: {
			type: 'value'
		},
		series: [{
			name:'订单金额',
			data: <?php echo json_encode($dataArr); ?>,
			type: 'line'
		}]
	}
	<?php endif; ?>
	myChart.setOption(option,true);
	//getchart(1,'客户数');
	function getchart(type,title){
		var index = layer.load();
		$.post('',{op:'getdata',type:type},function(res){
			layer.close(index);
			option = {
				title: {
					text: title,textStyle:{fontSize:12},x: "center"
				},
				tooltip: {
					trigger: 'axis'
				},
				xAxis: {
					//name:'日期',
					boundaryGap: false,
					type: 'category',
					data: res.dateArr
				},
				yAxis: {
					type: 'value'
				},
				series: [{
					name:title,
					data: res.dataArr,
					type: 'line'
				}]
			}
			myChart.setOption(option,true);
		})
		
	}
	</script>
	
</body>
</html>