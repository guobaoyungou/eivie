<?php /*a:4:{s:43:"/www/wwwroot/eivie/app/view/maidan/add.html";i:1692146765;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;s:49:"/www/wwwroot/eivie/app/view/public/copyright.html";i:1648714895;}*/ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>买单扣费</title>
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
	
	.product{width:500px; padding:5px;background: #FFF;margin-top:5px;border:1px solid #f1f1f1}
	.product .content{display:flex;position:relative;width: 100%; padding:15px 10px;border-bottom: 1px #e5e5e5 dashed;position:relative;box-sizing:border-box;}
	.product .content:last-child{ border-bottom: 0; }
	.product .content img{ width: 70px; height: 70px;}
	.product .content .detail{display:flex;flex-direction:column;margin-left:7px;flex:1}
	.product .content .detail .t1{height: 30px;line-height: 15px;color: #000;}
	.product .content .detail .t2{height: 23px;line-height: 23px;color: #999;overflow: hidden;font-size:13px;}
	.product .content .detail .t3{display:flex;height:15px;line-height:15px;color: #ff4246;}
	.product .content .detail .x1{ flex:1}
	.product .content .detail .x2{ width:50px;font-size:16px;text-align:right;margin-right:4px}
	.product .content .comment{position:absolute;top:32px;right:5px;border: 1px #ffc702 solid; border-radius:5px;background:#fff; color: #ffc702;  padding: 0 5px; height: 23px; line-height: 23px;}
	#ggnamediv .layui-input,#ggvaldiv .layui-input{ display:inline;height:30px}
	.input-show-text {border:none;}
	</style>
</head>
<body>
  <div class="layui-fluid">
    <div class="layui-row layui-col-space15">
      <div class="layui-card layui-col-md12">
				<div class="layui-card-header"><i class="fa fa-cog"></i> 买单扣费</div>
				<div class="layui-card-body" pad15>
					<div class="layui-form form-label-w6" lay-filter="">
						<div class="layui-form-item">
							<label class="layui-form-label"><?php echo t('会员'); ?>手机号：</label>
							<div class="layui-input-inline">
								<input type="text" name="tel" value="" class="layui-input">
							</div>
							<button class="layui-btn layui-btn-primary" style="float:left" onclick="search()">查找<?php echo t('会员'); ?></button>
						</div>
						<div class="layui-form-item">
							<label class="layui-form-label">昵称：</label>
							<div class="layui-input-inline"><input type="text" name="mname" value="暂无" class="layui-input input-show-text"></div>
							<div class="layui-form-mid layui-word-aux">请输入手机号点击查找</div>
						</div>
						<div class="layui-form-item">
							<label class="layui-form-label">余额：</label>
							<div class="layui-input-inline"><input type="text" name="mmoney" value="暂无" class="layui-input input-show-text"></div>
							<div class="layui-form-mid layui-word-aux">请输入手机号点击查找</div>
						</div>
                        <div class="layui-form-item">
                            <label class="layui-form-label">所属门店</label>
                            <div class="layui-input-inline" >
                                <select name="mdid">
                                    <option value="0">请选择</option>
                                    <?php foreach($mendian as $k=>$v): ?>
                                    <option value="<?php echo $v['id']; ?>"><?php echo $v['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
						<div class="layui-form-item">
							<label class="layui-form-label">支付金额：</label>
							<div class="layui-input-inline"><input type="text" name="money" value="0" class="layui-input"></div>
							<div class="layui-form-mid layui-word-aux">仅支持<?php echo t('余额'); ?>支付</div>
						</div>

<!--						<div class="layui-form-item">-->
<!--							<label class="layui-form-label">实付金额：</label>-->
<!--							<div class="layui-input-inline"><input type="text" name="paymoney" readonly value="0" class="layui-input"></div>-->
<!--							<div class="layui-form-mid layui-word-aux">自动计算 无需输入</div>-->
<!--						</div>-->

						<div class="layui-form-item">
							<label class="layui-form-label"></label>
							<div class="layui-input-block">
								<input type="hidden" name="mid" value="0">
								<button class="layui-btn" lay-submit lay-filter="formsubmit">提 交</button>
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
	<script>

	layui.form.on('submit(formsubmit)', function(obj){
		var field = obj.field
		console.log(field);
		if(field.tel==''){
			layer.msg('请输入<?php echo t('会员'); ?>手机号');return;
		}
		if(field.money<=0){
			layer.msg('请输入正确的金额');return;
		}
		var index = layer.load();
		$.post("<?php echo url('add'); ?>",field,function(res){
			layer.close(index);
			dialog(res);
		})
	})

	function search()
	{
		var tel = $("input[name='tel']").val();
		$.post("<?php echo url('getMemberByTel'); ?>",{tel:tel},function(res){
			if(res.status == 1){
				$("input[name='mid']").val(res.data.id);
				$("input[name='mname']").val(res.data.nickname);
				$("input[name='mmoney']").val(res.data.money);
			}else{
				dialog(res);
			}
		})
	}

  </script>
	
</body>
</html>