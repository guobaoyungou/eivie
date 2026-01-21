<?php /*a:3:{s:47:"/www/wwwroot/eivie/app/view/business/index.html";i:1766974785;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;}*/ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>商户管理</title>
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
          <div class="layui-card-header">商户管理<?php if(input('param.isopen')==1): ?><i class="layui-icon layui-icon-close" style="font-size:18px;font-weight:bold;cursor:pointer" onclick="closeself()"></i><?php endif; ?></div>
          <div class="layui-card-body" pad15>
			  <?php if($business_num_limit > 0): ?><blockquote class="layui-elem-quote">商户数量上限：<?php echo $business_num_limit; ?></blockquote><?php endif; ?>
						<div class="layui-form layui-col-md12 layui-form-search">
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">ID</label>
								<div class="layui-input-inline">
									<input type="text" name="id" autocomplete="off" class="layui-input">
								</div>
							</div>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">名称</label>
								<div class="layui-input-inline">
									<input type="text" name="name" autocomplete="off" class="layui-input">
								</div>
							</div>
							<?php if($clist): ?>
							<div class="layui-inline">
								<label class="layui-form-label">分类</label>
								<div class="layui-input-inline">
									<select name="cid" lay-search>
										<option value="">全部</option>
										<?php foreach($clist as $cv): ?>
										<option value="<?php echo $cv['id']; ?>"><?php echo $cv['name']; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<?php endif; if(getcustom('member_dedamount')): ?>
								<div class="layui-inline">
									<label class="layui-form-label">让利比例审核</label>
									<div class="layui-input-inline">
										<select name="paymoney_givepercent2">
											<option value="">全部</option>
											<option value="0">待审核</option>
											<option value="1">已审核</option>
										</select>
									</div>
								</div>
							<?php endif; ?>
							<div class="layui-inline">
								<label class="layui-form-label">状态</label>
								<div class="layui-input-inline">
									<select name="status">
										<option value="">全部</option>
										<option value="0">待审核</option>
										<option value="1">已通过</option>
										<option value="2">已驳回</option>
										<option value="-1">已过期</option>
									</select>
								</div>
							</div>
							<div class="layui-inline">
								<label class="layui-form-label">营业状态</label>
								<div class="layui-input-inline">
									<select name="is_open">
										<option value="">全部</option>
										<option value="0">休息</option>
										<option value="1">营业</option>
									</select>
								</div>
							</div>
							<?php if(getcustom('finance_statistics')): ?>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">会员ID</label>
								<div class="layui-input-inline">
									<input type="text" name="mid" autocomplete="off" class="layui-input">
								</div>
							</div>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">会员昵称</label>
								<div class="layui-input-inline">
									<input type="text" name="m_nickname" autocomplete="off" class="layui-input" placeholder="昵称/手机号">
								</div>
							</div>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">推荐人ID</label>
								<div class="layui-input-inline">
									<input type="text" name="pid" autocomplete="off" class="layui-input">
								</div>
							</div>
							<div class="layui-inline">
								<label class="layui-form-label">时间</label>
								<div class="layui-input-inline" style="width:180px">
									<input type="text" name="ctime" id="ctime" autocomplete="off" class="layui-input">
								</div>
							</div>
							<?php endif; if(getcustom('business_pc_search_area')): ?>
							<div class="layui-inline">
								<label class="layui-form-label">创建时间</label>
								<div class="layui-input-inline" style="width:180px">
									<input type="text" name="ctime" id="ctime" autocomplete="off" class="layui-input">
								</div>
							</div>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">店铺省</label>
								<div class="layui-input-inline">
									<input type="text" name="province" autocomplete="off" class="layui-input">
								</div>
							</div>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">店铺市</label>
								<div class="layui-input-inline">
									<input type="text" name="city" autocomplete="off" class="layui-input">
								</div>
							</div>
							<div class="layui-inline layuiadmin-input-useradmin">
								<label class="layui-form-label">店铺区县</label>
								<div class="layui-input-inline">
									<input type="text" name="district" autocomplete="off" class="layui-input">
								</div>
							</div>
							<?php endif; if(getcustom('yx_buyer_subsidy')): ?>
							<div class="layui-inline">
								<label class="layui-form-label">抽成审核</label>
								<div class="layui-input-inline">
									<select name="feepercent_audit">
										<option value="">全部</option>
										<option value="1">未审核</option>
									</select>
								</div>
							</div>
							<?php endif; ?>
							<div class="layui-inline">
								<button class="layui-btn layuiadmin-btn-replys" lay-submit="" lay-filter="LAY-app-forumreply-search">
									<i class="layui-icon layui-icon-search layuiadmin-button-btn"></i>
								</button>
							</div>
						</div>
					  <?php if(getcustom('finance_statistics') && ($auth_data=='all' || in_array('business_finance_statistics',$auth_data))): ?>
					  <div class="layui-col-md12">
						  <table class="layui-table" lay-skin="line" lay-size="lg">
							  <thead>
							  <tr>
								  <th class="layui-col-md2">本月营业额：<span id="month_order_price" style="font-weight:bold">计算中..</span></th>
								  <th class="layui-col-md2">上月营业额：<span id="lastmonth_order_price" style="font-weight:bold">计算中..</span></th>
								  <th class="layui-col-md2">今日营业额：<span id="today_order_price" style="font-weight:bold">计算中..</span></th>
								  <th class="layui-col-md2">商家余额：<span id="business_money" style="font-weight:bold">计算中..</span></th>
								  <th class="layui-col-md2">商家数量：<span id="business_num" style="font-weight:bold">计算中..</span></th>
								  <th class="layui-col-md2">已通过：<span id="business_num1" style="font-weight:bold">计算中..</span></th>
								  <th class="layui-col-md2">已驳回：<span id="business_num2" style="font-weight:bold">计算中..</span></th>
								  <th class="layui-col-md2">已到期：<span id="business_num3" style="font-weight:bold">计算中..</span></th>
								  <th class="layui-col-md2">已休息：<span id="business_num4" style="font-weight:bold">计算中..</span></th>
								  <th class="layui-col-md2">当日入驻：<span id="business_num5" style="font-weight:bold">计算中..</span></th>
							  </tr>
							  </thead>
						  </table>
					  </div>
					  <?php endif; ?>
						<div class="layui-col-md12">
							<table id="tabledata" lay-filter="tabledata"></table>
						</div>
          </div>
        </div>
    </div>
  </div>
	<div id="rechargeModel" style="width:500px;display:none;margin-top:30px">
		<div class="layui-form" lay-filter="">
			<input type="hidden" name="rechargemid" id="rechargemid"/>
			<div class="layui-form-item">
				<label class="layui-form-label">充值金额</label>
				<div class="layui-input-inline">
					<input type="text" name="rechargemoney" required lay-verify="required" placeholder="" autocomplete="off" class="layui-input">
				</div>
				<div class="layui-form-mid layui-word-aux">输入负值表示扣除金额</div>
			</div>
			<div class="layui-form-item">
				<div class="layui-input-block">
					<button class="layui-btn layui-btn-normal" lay-submit lay-filter="formRecharge">确定充值</button>
				</div>
			</div>
		</div>
	</div>
  <div id="depositModel" style="width:500px;display:none;margin-top:30px">
	  <div class="layui-form" lay-filter="">
		  <input type="hidden" name="depositmid" id="depositmid"/>
		  <div class="layui-form-item">
			  <label class="layui-form-label">充值金额</label>
			  <div class="layui-input-inline">
				  <input type="text" name="depositmoney" required lay-verify="required" placeholder="" autocomplete="off" class="layui-input">
			  </div>
			  <div class="layui-form-mid layui-word-aux">输入负值表示扣除金额</div>
		  </div>
		  <div class="layui-form-item">
			  <label class="layui-form-label">备注</label>
			  <div class="layui-input-inline">
				  <input type="text" name="depositremark" lay-verify="required" placeholder="" autocomplete="off" class="layui-input">
			  </div>
			  <div class="layui-form-mid layui-word-aux"></div>
		  </div>
		  <div class="layui-form-item">
			  <div class="layui-input-block">
				  <button class="layui-btn layui-btn-normal" lay-submit lay-filter="formdeposit">确定充值</button>
			  </div>
		  </div>
	  </div>
  </div>
  <script type="text/html" id="leftbtn">
	  <div class="layui-btn-container">
		  <a class="layui-btn layuiadmin-btn-list" href="javascript:void(0)" onclick="openmax('<?php echo url('edit'); ?>')">添加</a>
		  <button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="datadel(0)">删除</button>
		  <?php if(getcustom('business_fenxiao') || getcustom('business_excel')): ?>
		  <button class="layui-btn layui-btn-primary layuiadmin-btn-list" data-form-export="<?php echo url('excel'); ?>">导出</button>
		  <?php endif; ?>
		  <button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="showHexiaoLoginQr()">登录地址</button>
		  <?php if(getcustom('business_pc_yeji_ranking') && getcustom('yx_new_score_active')): ?>
		  <button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="refreshYeji()">更新业绩</button>
		  <?php endif; ?>
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
	var shopsel = false;
	var restaurantsel = false;
  var table = layui.table;
	var datawhere = {};
  //数据表
  var tableIns = table.render({
    elem: '#tabledata'
    ,url: "<?php echo app('request')->url(); ?>" //数据接口
    ,page: true //开启分页
	  ,autoSort:false
	  ,toolbar: '#leftbtn'
	  ,defaultToolbar: [
		  'filter', // 列筛选
	  ]
    ,cols: [[ //表头
			{type:"checkbox"},
      {field: 'id', title: 'ID',  sort: true,width:80},
      {field: 'cname', title: '商家分类'},
      {field: 'name', title: '商家名称'},
      {field: 'logo', title: '商家图片',templet:function(d){return '<img src="'+d.logo+'" style="width:80px;"/>';},width: 100},
      {field: 'address', title: '商家地址'},
      {field: 'nickname', title: '店主信息',templet:function(d){
				if(!d.nickname) return '';
				var html = '';
				if(d.headimg){
			        html +='<img src="'+d.headimg+'" style="width:40px;height:40px"/>';
		  		}
			  	html += d.nickname;
                <?php if(getcustom('business_list_showmember')): ?>
                html += '<br>ID：'+d.mid;
                if(d.levelname){
                    html += '<br>等级：'+d.levelname;
                }
                <?php endif; ?>
				return html;
      }},
      <?php if(getcustom('business_list_showmember')): ?>
      {field: 'parent_name', title: '推荐信息',templet:function(d){
				if(!d.parent_name) return '';
				var html = '';
				if(d.parent_headimg){
			        html +='<img src="'+d.parent_headimg+'" style="width:40px;height:40px"/>';
		  		}
			  	html += d.parent_name;
                html += '<br>ID：'+d.parent_id;
                if(d.parent_level){
                    html += '<br>等级：'+d.parent_level;
                }
				return html;
      }},
      <?php endif; ?>
      {field: 'linkman', title: '联系人',templet:function(d){ return d.linkman + '('+d.linktel+')' }},
			{field: 'money', title: "余额",templet:function(d){
				return '<a href="javascript:void(0)" onclick="openmax(\'<?php echo url('BusinessMoney/moneylog'); ?>&isopen=1&bid='+d.id+'\')">'+d.money+'</a>';
			},sort:true},
		  <?php if(getcustom('business_deposit')): ?>
		  {field: 'money', title: "<?php echo t('入驻保证金'); ?>",templet:function(d){
		  	  	 var html = '<div><a href="javascript:void(0)" onclick="openmax(\'<?php echo url('BusinessMoney/depositlog'); ?>&isopen=1&bid='+d.id+'\')">'+d.deposit+'</a></div>';
				  <?php if(getcustom('business_deposit_refund')): ?>
				  		if(d.deposit_refund_status ==1){
html+='<div><a href="javascript:void(0)" onclick="checkdepositrefund('+d.deposit_orderid+')"  style="color: red">退款申请</a></div>';
			  			}
				  <?php endif; ?>
				return html;
			  },sort:true},
		  <?php endif; if(getcustom('business_maidan_team_fenhong')): ?>
		  {field: 'money', title: "收款买单金额",templet:function(d){
		  			var html = '<div style="color: #1E9FFF">￥'+d.maidan_money+'</div>';
				  	if(d.maidan_fenhong_jl_minprice<=0){
					  html+='<div style="color: red">未设置</div>';
			  		}else{
					  if(d.is_maidan_fenhong ==1){
						  html+='<div style="color: green">已达标</div>';
					  }else{
						  html+='<div style="color: red">不达标</div>';
					  }
			  		}
				return html;
		   }},
		  <?php endif; if($bset['business_selfscore']==1): ?>
			{field: 'score', title: "<?php echo t('积分'); ?>",templet:function(d){
				return '<a href="javascript:void(0)" onclick="openmax(\'<?php echo url('BusinessScore/scorelog'); ?>&isopen=1&bid='+d.id+'\')">'+d.score+'</a>';
			},sort:true},
			<?php endif; if(getcustom('yx_buyer_subsidy') && ($auth_data=='all' || in_array('Subsidy/*',$auth_data))): ?>
		  {field: 'subsidy_score', title: '<?php echo t('返现积分'); ?>',width:100,templet:function(d){
				  return '<a href="javascript:void(0)" onclick="openmax(\'<?php echo url('Subsidy/score_log'); ?>&is_business=1&isopen=1&bid='+d.id+'\')">'+d.subsidy_score+'</a>';
			  }},
		  <?php endif; if(getcustom('blist_showadminnum')): ?>
			{field: 'adminnum', title: '店员数'},
			<?php endif; if(getcustom('business_sales_quota')): ?>
			{field: 'total_sales_quota', title: '已销售额度'},
			{field: 'syquota', title: '剩余销售额度'},
			<?php endif; ?>
			{field: 'viewnum', title: '浏览量',width:70},
      {field: 'createtime', title: '创建时间',templet:function(d){ return date('Y-m-d H:i',d.createtime)},sort:true},
		  {field: 'endtime', title: '到期时间',templet:function(d){ return date('Y-m-d',d.endtime)},sort:true},
		  <?php if(getcustom('business_pc_yeji_ranking')): ?>
		 	 {field: 'yeji', title: '业绩',templet:function(d){ return d.yeji},sort:true},
		  <?php endif; if(getcustom('member_dedamount')): ?>
		 	 {field: 'paymoney_givepercent', title: '让利比例',templet:function(d){ 
		 	 	var html = '';
		 	 		html += '<div>'+d.paymoney_givepercent+'%</div>';
		 	 		if(d.paymoney_givepercent2 >=0){
		 	 			html += '<div style="color:red">待审核：'+d.paymoney_givepercent2+'%</div>';
		 	 			html += '<button class="table-btn" onclick="setPaymoneyGivepercent(\''+d.id+'\',1)">通过</button>';
		 	 			html += '<button class="table-btn" onclick="setPaymoneyGivepercent(\''+d.id+'\',2)">驳回</button>';
		 	 		}
		 	 	return html;
		 	 },sort:true},
		  <?php endif; if(getcustom('business_expert')): ?>
		 	 {field: 'expertid', title: '达人信息',templet:function(d){
		 	 	var html = '';
		 	 	if(d.expertid){
		 	 		var expertinfo = d.expert?d.expert.linkman:'';
		 	 		html += '<a href="javascript:void(0)" onclick="openmax(\'<?php echo url('BusinessExpert/index'); ?>&isopen=1&id='+d.expertid+'\')">'+expertinfo+'(ID:'+d.expertid+')'+'</a>';
		 	 	}
		 	 	return html;
       }},
		  <?php endif; if(getcustom('yx_new_score_active')): ?>
		  {field: 'newscore_total', title: '<?php echo t("新积分"); ?>总让利',templet:function(d){
				  var html = '';
				  html += '<a href="javascript:void(0)" onclick="openmax(\'<?php echo url('NewScore/rl_log'); ?>&isopen=1&bid='+d.id+'\')">'+d.newscore_total+'</a>';
				  return html;
			  }},
		  <?php endif; ?>
      {field: 'status', title: '状态',templet:function(d){ 
				if(d.status==0){
					return '<span style="color:red">待审核</span>';
				}else if(d.status==1){
					return '<span style="color:green">已通过</span>';
				}else if(d.status==2){
					return '<span style="color:red">已驳回</span>';
				}else if(d.status==-1){
					return '<span style="color:red">已过期</span>';
				}
			}},
		  {field: 'is_open', title: '营业状态',templet:function(d){
				  if(d.is_open==0){
					  return '<span style="color:red">休息</span>';
				  }else if(d.is_open==1){
					  return '<span style="color:green">营业</span>';
				  }
			  }},
		  <?php if(getcustom('finance_statistics')): ?>
		  {field: 'total_order_price', title: '商家营业额'},
		  <?php endif; if(getcustom('finance_statistics') || getcustom('yx_buyer_subsidy')): ?>
		  {field: 'feepercent', title: '抽成费率(%)',templet:function(d){
				  var html = '';
				  html += '<div>'+d.feepercent+'%</div>';
				  if(d.feepercent_audit >0){
					  html += '<div style="color:red">待审核：'+d.feepercent_audit+'%</div>';
					  html += '<button class="table-btn" onclick="setFeepercent(\''+d.id+'\',1)">通过</button>';
					  html += '<button class="table-btn" onclick="setFeepercent(\''+d.id+'\',2)">驳回</button>';
				  }
				  return html;
			  },sort:true},
		  <?php endif; ?>
      {field: 'operation', title: '操作',templet: function(d){
				var html = '';
			  <?php if($handle_auth['edit']==1): ?>
				html += '<button class="table-btn" onclick="openmax(\'<?php echo url('edit'); ?>/id/'+d.id+'\')">编辑</button>';
			  	html += '<button class="table-btn" onclick="datadel('+d.id+')">删除</button>';
			  <?php endif; if($handle_auth['recharge']==1): ?>
				html += '<button class="table-btn" onclick="recharge('+d.id+')">充值</button>';
			  <?php endif; if($bset['business_selfscore']==1): ?>
				html += '<button class="table-btn" onclick="addscore('+d.id+')">加<?php echo t('积分'); ?></button>';
				<?php endif; if(getcustom('business_deposit')): ?>
				html += '<button class="table-btn" onclick="adddeposit('+d.id+')">加<?php echo t('入驻保证金'); ?></button>';
				<?php endif; ?>
				html += '<button class="table-btn" onclick="blogin('+d.id+')">登录</button>';
				if(d.status == 0 || d.status == 2){
					html += '<button class="table-btn" onclick="setcheckst(\''+d.id+'\',1)">通过</button>';
				}
				if(d.status == 0 || d.status == 1){
					html += '<button class="table-btn" onclick="setcheckst(\''+d.id+'\',2)">驳回</button>';
				}
				html += '<button class="table-btn" onclick="copydata(\''+d.id+'\')">复制数据</button>';
				<?php if(getcustom('plug_businessqr')): ?>
				html += '<button class="table-btn" onclick="openmax(\'<?php echo url('PlugBusinessqrPay/index'); ?>&bid='+d.id+'\')">快捷支付</button>';
				<?php endif; if(getcustom('restaurant_product_import')): if($isadminlogin): ?>
						if(d.restaurant_auth){
						html += '<button class="table-btn" onclick="showChooseRestaurantProduct(\''+d.id+'\')">导入菜品</button>';
						}
					<?php endif; ?>
				<?php endif; if(getcustom('alipay_fenzhang')): if($bset['alifw_status'] == 1 && ( $auth_data=='all' || in_array('alipayFenzhang',$auth_data))): ?>
					html += '<button class="table-btn" onclick="alipayset(\''+d.id+'\')">支付宝收款</button>';
					<?php endif; ?>
				<?php endif; ?>	
				return html;
      },width:250}
    ]]
	,done:function(res, curr, count){
		if(curr == 1){
			<?php if(getcustom('finance_statistics')): ?>
			$.post("<?php echo url('business_statistics'); ?>",datawhere,function(res){
				$('#month_order_price').html(res.totaldata.month_order_price ?? 0);
				$('#lastmonth_order_price').html(res.totaldata.lastmonth_order_price ?? 0);
				$('#today_order_price').html(res.totaldata.today_order_price ?? 0);
				$('#business_money').html(res.totaldata.business_money ?? 0);
				$('#business_num').html(res.totaldata.business_num ?? 0);
				$('#business_num1').html(res.totaldata.business_num1 ?? 0);
				$('#business_num2').html(res.totaldata.business_num2 ?? 0);
				$('#business_num3').html(res.totaldata.business_num3 ?? 0);
				$('#business_num4').html(res.totaldata.business_num4 ?? 0);
				$('#business_num5').html(res.totaldata.business_num5 ?? 0);
			})
			<?php endif; ?>
		}
	}
  });
	var rechargelayer
	function recharge(id){
		$('#rechargemid').val(id);
		rechargelayer = layer.open({type:1,area: ['500px', '200px'],title:"<?php echo t('余额'); ?>充值",content:$('#rechargeModel'),shadeClose:true})
	}
	
	function showChooseRestaurantProduct(bid){
		<?php if(getcustom('restaurant_product_import')): ?>
		layer.open({type:2,title:'选择商品',content:"<?php echo url('RestaurantProduct/chooseproduct'); ?>/is_import/1/bid/"+bid,area:['1000px','600px'],shadeClose:true});
		<?php endif; ?>
	}
	function addscore(id){
		var html = '';
		html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
		html+='		<label class="layui-form-label" style="width:80px">增加数量：</label>';
		html+='		<div class="layui-input-inline" style="width:200px">';
		html+='			<input type="text" id="addscorescore" class="layui-input"/>';
		html+='		</div>';
		html+='		<div class="layui-form-mid layui-word-aux">输入负值表示扣除<?php echo t('积分'); ?></div>';
		html+='	</div>';
		html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
		html+='		<label class="layui-form-label" style="width:80px">备注信息：</label>';
		html+='		<div class="layui-input-inline" style="width:350px">';
		html+='			<input type="text" id="addscoreremark" class="layui-input"/>';
		html+='		</div>';
		html+='	</div>';
		var addscoreLayer = layer.open({type:1,area:['500px','300px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
			yes:function(){
				var index = layer.load();
				$.post("<?php echo url('addscore'); ?>",{id:id,score:$('#addscorescore').val(),remark:$('#addscoreremark').val()},function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(addscoreLayer);
					tableIns.reload()
				})
			}
		})
	}

	function blogin(id){
		window.open("<?php echo url('blogin'); ?>/id/"+id);
	}
	//充值提交
  layui.form.on('submit(formRecharge)', function(obj){
		var index= layer.load();
    $.post("<?php echo url('recharge'); ?>",obj.field,function(data){
			layer.close(index);
			dialog(data.msg,data.status,data.url);
			if(data.status==1){
				tableIns.reload({
					where: datawhere
				});
			}
			layer.close(rechargelayer);
		})
  });
			  var depositlayer
			  function adddeposit(id){
				  $('#depositmid').val(id);
				  depositlayer = layer.open({type:1,area: ['500px', '300px'],title:"保证金充值",content:$('#depositModel'),shadeClose:true})
			  }

			  //充值提交
			  layui.form.on('submit(formdeposit)', function(obj){
				  var index= layer.load();
				  $.post("<?php echo url('deposit'); ?>",obj.field,function(data){
					  layer.close(index);
					  dialog(data.msg,data.status,data.url);
					  if(data.status==1){
						  tableIns.reload({
							  where: datawhere
						  });
					  }
					  layer.close(depositlayer);
				  })
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
	
	//审核
	function setcheckst(id,st){
		if(st == 2){
			var html = '';
			html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
			html+='		<label class="layui-form-label" style="width:80px">驳回原因</label>';
			html+='		<div class="layui-input-inline" style="width:350px">';
			html+='			<textarea type="text" id="check_reason" class="layui-textarea"></textarea>';
			html+='		</div>';
			html+='	</div>';
			var checkLayer = layer.open({type:1,area:['500px','250px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
				yes:function(){
					var index = layer.load();
					$.post("<?php echo url('setcheckst'); ?>",{id:id,st:st,reason:$('#check_reason').val()},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(checkLayer);
						tableIns.reload()
					})
				}
			})
		}else{
			layer.confirm('确定要审核通过吗?',{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('setcheckst'); ?>",{id:id,st:st},function(data){
					layer.close(index);
					dialog(data.msg,data.status);
					tableIns.reload()
				})
			});
		}
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
			for(var i=0;i<checkData.length;i++){
				ids.push(checkData[i]['id']);
			}
		}else{
			ids.push(id);
		}
		layer.confirm('删除商家后，该商家下的商品会直接下架，确定要删除吗？删除后无法恢复！商户可能存在未处理完的售后问题，请保存好商家的相关资料后再删除！',{icon: 7, title:'操作确认'}, function(index){
			//do something
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('del'); ?>",{ids:ids},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}
	//上下架
	function setst(id,st){
		var ids = [];
		if(id==0){
			var checkStatus = table.checkStatus('tabledata')
			var checkData = checkStatus.data; //得到选中的数据
			if(checkData.length === 0){
				 return layer.msg('请选择数据');
			}
			for(var i=0;i<checkData.length;i++){
				ids.push(checkData[i]['id']);
			}
		}else{
			ids.push(id);
		}
		layer.confirm('确定要'+(st==0?'关闭':'开启')+'吗?',{icon: 7, title:'操作确认'}, function(index){
			//do something
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('setst'); ?>",{ids:ids,st:st},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}
	function showHexiaoLoginQr(){
		layer.open({type:1,area:['500px','450px'],content:'<div style="margin-top:40px;text-align:center"><div style="margin:20px 110px;text-align:center" id="hexiaologinqr"></div><div style="font-size:30px;color:#e94745"><span style="font-size:24px"></span></div><div style="font-size:14px;text-align:center;margin:10px 20px">手机端：<?php echo m_url('admin/index/index'); ?></div><div style="font-size:14px;text-align:center;margin:10px 20px">电脑端：<?php echo PRE_URL; ?></div></div>',title:false,shadeClose:true});
		var qrcode = new QRCode('hexiaologinqr', {
			text: 'your content',
			width: 280,
			height: 280,
			colorDark : '#000000',
			colorLight : '#ffffff',
			correctLevel : QRCode.CorrectLevel.H
		});
		qrcode.clear();
		qrcode.makeCode("<?php echo m_url('admin/index/index'); ?>");
	}

	//复制数据
	function copydata(id){
		var html = '';
		html ='<div style="margin:20px auto;">';
		html+='	<blockquote class="layui-elem-quote" style="margin:10px">复制操作会对复制到的商户的数据产生较大影响，请提前做好备份</blockquote>';
		html+='	<div class="layui-form" lay-filter="">';
		html+='		<div class="layui-form-item">';
		html+='			<label class="layui-form-label" style="width:130px">复制到的商户ID：</label>';
		html+='			<div class="layui-input-inline">';
		html+='				<input type="text" name="info[toid]" value="'+id+'" required lay-verify="required" placeholder="" autocomplete="off" class="layui-input">';
		html+='			</div>';
		html+='		</div>';
		html+='		<div class="layui-form-item">';
		html+='			<label class="layui-form-label" style="width:130px">要复制的数据：</label>';
		html+='			<div class="layui-input-block" style="margin-left:150px;">';
		html+='				<div style="margin-top:10px;color:#303030; font-size:14px; font-weight:600; ">';
		html+='					<input type="checkbox" title="全部选择" lay-skin="primary" lay-filter="checkall_all"/>';
		html+='				</div>';
		html+='				<div style="float:left;margin-left:0">';
		html+='					<div style="margin-left:10px">';
		<?php if($auth_data=='all' || in_array('ShopProduct/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="商城商品" name="module_data[]"  title="商城商品" lay-skin="primary" lay-filter="shopset"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('CollageProduct/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="拼团商品" name="module_data[]"  title="拼团商品" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('KanjiaProduct/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="砍价商品" name="module_data[]"  title="砍价商品" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('SeckillProduct/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="秒杀商品" name="module_data[]"  title="秒杀商品" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('TuangouProduct/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="团购商品" name="module_data[]"  title="团购商品" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('LuckyCollageProduct/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="<?php echo t('幸运拼团'); ?>商品" name="module_data[]"  title="<?php echo t('幸运拼团'); ?>商品" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('Shortvideo/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="短视频" name="module_data[]"  title="短视频" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('Article/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="文章列表" name="module_data[]"  title="文章列表" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('Yuyue/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="预约服务商品" name="module_data[]"  title="预约服务商品" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('KechengList/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="知识付费课程" name="module_data[]"  title="知识付费课程" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('Restaurant/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="餐饮菜品" name="module_data[]"  title="餐饮菜品" lay-skin="primary" lay-filter="restaurantset"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('ShopCategory/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="商城商品分类" name="module_data[]"  title="商城商品分类" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if($auth_data=='all' || in_array('Coupon/*',$auth_data)): ?>
		html+='						<div style="min-width:100px;float: left;">';
		html+='							<input type="checkbox" value="优惠券" name="module_data[]"  title="优惠券" lay-skin="primary"/>';
		html+='						</div>';
		<?php endif; if(getcustom('form_copy')): if($auth_data=='all' || in_array('Form/*',$auth_data)): ?>
			html+='						<div style="min-width:100px;float: left;">';
			html+='							<input type="checkbox" value="自定义表单" name="module_data[]"  title="自定义表单" lay-skin="primary"/>';
			html+='						</div>';
			<?php endif; ?>
		<?php endif; if(getcustom('extend_gift_bag')): if($auth_data=='all' || in_array('GiftBag/*',$auth_data)): ?>
			html+='						<div style="min-width:100px;float: left;">';
			html+='							<input type="checkbox" value="礼包" name="module_data[]"  title="礼包" lay-skin="primary"/>';
			html+='						</div>';
			<?php endif; ?>
		<?php endif; ?>
		//html+='						<div style="min-width:100px;float: left;">';
		//html+='							<input type="checkbox" value="设计页面" name="module_data[]"  title="设计页面" lay-skin="primary"/>';
		//html+='						</div>';
		html+='					</div>';
		html+='				</div>';
		html+='			</div>';
		html+='		</div>';
		<?php if(getcustom('business_copy_shopcategory')): if($auth_data=='all' || in_array('ShopCategory/*',$auth_data)): ?>
			html+='<div class="layui-form-item" style="display:none;margin-top:50px" id="shopcat">';
			html+='	<label class="layui-form-label" style="width:130px">商品分类：</label>';
			html+='	<div class="layui-input-inline" style="width:400px">';
			html+='		<select name="info[shopcids]" id="cid" xm-select="selectShopCid" xm-select-max="" xm-select-search >';
			html+='			<option value="">--全部--</option>';
									<?php foreach($shopclist as $cv): ?>
			html+='			<option value="<?php echo $cv['id']; ?>" ><?php echo $cv['name']; ?></option>';
										<?php foreach($cv['child'] as $k=>$v): if($k < count($cv['child'])-1): ?>
			html+='					<option value="<?php echo $v['id']; ?>" >&nbsp;&nbsp;&nbsp;&nbsp;├ <?php echo $v['name']; ?></option>';
											<?php else: ?>
			html+='					<option value="<?php echo $v['id']; ?>" >&nbsp;&nbsp;&nbsp;&nbsp;└ <?php echo $v['name']; ?></option>';
											<?php endif; foreach($v['child'] as $k2=>$v2): if($k2 < count($v['child'])-1): ?>
			html+='						<option value="<?php echo $v2['id']; ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;├ <?php echo $v2['name']; ?></option>';
												<?php else: ?>
			html+='						<option value="<?php echo $v2['id']; ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└ <?php echo $v2['name']; ?></option>';
												<?php endif; ?>
											<?php endforeach; ?>
										<?php endforeach; ?>
									<?php endforeach; ?>
			html+='		</select>';
			html+='	</div>';
			html+='	<div class="layui-form-mid layui-word-aux">不选择默认全部</div>';
			html+='</div>';
			<?php endif; ?>
		<?php endif; if(getcustom('business_copy_restaurantcategory')): if($auth_data=='all' || in_array('RestaurantProductCategory/*',$auth_data)): ?>
			html+='<div class="layui-form-item" style="display:none;margin-top:50px" id="restaurantcat">';
			html+='	<label class="layui-form-label" style="width:130px">餐饮分类：</label>';
			html+='	<div class="layui-input-inline" style="width:400px">';
			html+='		<select name="info[restaurantcids]" id="cid" xm-select="selectRestauranCid" xm-select-max="" xm-select-search >';
			html+='			<option value="">--全部--</option>';
									<?php foreach($restaurantclist as $rv): ?>
			html+='			<option value="<?php echo $rv['id']; ?>" ><?php echo $rv['name']; ?></option>';
										<?php foreach($rv['child'] as $k=>$v): if($k < count($rv['child'])-1): ?>
			html+='					<option value="<?php echo $v['id']; ?>" >&nbsp;&nbsp;&nbsp;&nbsp;├ <?php echo $v['name']; ?></option>';
											<?php else: ?>
			html+='					<option value="<?php echo $v['id']; ?>" >&nbsp;&nbsp;&nbsp;&nbsp;└ <?php echo $v['name']; ?></option>';
											<?php endif; foreach($v['child'] as $k2=>$v2): if($k2 < count($v['child'])-1): ?>
			html+='						<option value="<?php echo $v2['id']; ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;├ <?php echo $v2['name']; ?></option>';
												<?php else: ?>
			html+='						<option value="<?php echo $v2['id']; ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└ <?php echo $v2['name']; ?></option>';
												<?php endif; ?>
											<?php endforeach; ?>
										<?php endforeach; ?>
									<?php endforeach; ?>
			html+='		</select>';
			html+='	</div>';
			html+='	<div class="layui-form-mid layui-word-aux">不选择默认全部</div>';
			html+='</div>';
			<?php endif; ?>
		<?php endif; ?>
		html+='		<div class="layui-form-item">';
		html+='			<label class="layui-form-label" style="width:130px">删除原有数据：</label>';
		html+='			<div class="layui-input-inline">';
		html+='				<input type="radio" name="info[delold]" value="0" title="否" checked>';
		html+='				<input type="radio" name="info[delold]" value="1" title="是">';
		html+='			</div>';
		html+='			<div class="layui-form-mid layui-word-aux">是否删除掉要复制到的商户的原来的数据</div>';
		html+='		</div>';
		html+='		<div class="layui-form-item" style="margin-top:40px">';
		html+='			<label class="layui-form-label" style="width:130px"></label>';
		html+='			<div class="layui-input-block">';
		html+='				<button class="layui-btn layui-btn-normal" lay-submit lay-filter="formCopydata">确定复制</button>';
		html+='			</div>';
		html+='		</div>';
		html+='	</div>';
		html+='</div>';
		<?php if(getcustom('business_copy_shopcategory')): ?>
			shopsel = false;
			$('#shopcat').hide();
		<?php endif; if(getcustom('business_copy_restaurantcategory')): ?>
			restaurantsel = false;
			$('#restaurantcat').hide();
		<?php endif; ?>
		var copydatalayer = layer.open({type:1,area:['800px','800px'],content:html,title:'平台数据复制到商户',shadeClose:true});
		layui.formSelects.render();
		layui.form.render();
		layui.form.on('submit(formCopydata)', function(obj){
			var field = obj.field;
			console.log(field);
			var index= layer.load();
			$.post("<?php echo url('copydata'); ?>",field,function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				if(data.status == 1){
					<?php if(getcustom('business_copy_shopcategory')): ?>
						shopsel = false;
						$('#shopcat').hide();
					<?php endif; if(getcustom('business_copy_restaurantcategory')): ?>
						restaurantsel = false;
						$('#restaurantcat').hide();
					<?php endif; ?>
					layer.close(copydatalayer);
				}
			})
		})
		layui.form.on('checkbox(checkall_all)',function(data){
			if(data.elem.checked){
				$(data.elem).parent().parent().find('input[type=checkbox]').prop('checked',true);
				<?php if(getcustom('business_copy_shopcategory')): ?>
					shopsel = true;
					$('#shopcat').show();
				<?php endif; if(getcustom('business_copy_restaurantcategory')): ?>
					restaurantsel = true;
					$('#restaurantcat').show();
				<?php endif; ?>
			}else{
				$(data.elem).parent().parent().find('input[type=checkbox]').prop('checked',false);
				<?php if(getcustom('business_copy_shopcategory')): ?>
					shopsel = false;
					$('#shopcat').hide();
				<?php endif; if(getcustom('business_copy_restaurantcategory')): ?>
					restaurantsel = false;
					$('#restaurantcat').hide();
				<?php endif; ?>
			}

			layui.form.render('checkbox'); 
		});
		layui.form.on('checkbox(checkall)',function(data){
			if(data.elem.checked){
				$(data.elem).parent().parent().find('input[type=checkbox]').prop('checked',true);
			}else{
				$(data.elem).parent().parent().find('input[type=checkbox]').prop('checked',false);
			}
			layui.form.render('checkbox'); 
		})
	}

	<?php if(getcustom('zhaopin')): ?>
		function openZhaopin(bid){
			$.post("<?php echo url('zhaopin/openZhaopin'); ?>",{bid:bid},function(data){
				dialog(data.msg,data.status);
				if(data.status == 1){
					layer.close(copydatalayer);
				}
			})
		}
	<?php endif; if(getcustom('business_copy_shopcategory')): ?>
			layui.form.on('checkbox(shopset)',function(data){
				shopsel = !shopsel;
				if(shopsel){
					$('#shopcat').show();
				}else{
					$('#shopcat').hide();
				}
			})
	<?php endif; if(getcustom('business_copy_restaurantcategory')): ?>
			layui.form.on('checkbox(restaurantset)',function(data){
				restaurantsel = !restaurantsel;
				if(restaurantsel){
					$('#restaurantcat').show();
				}else{
					$('#restaurantcat').hide();
				}
			})
	<?php endif; ?>
	//显示退款订单	
	function showDepositOrder(orderid){
		var index_load = layer.load();
		$.post("<?php echo url('getdepositorder'); ?>",{orderid:orderid},function(data) {
			tuikuandata = data;
			layer.close(index_load);
			layui.laytpl(ordertpl.innerHTML).render(data, function(html){
				var refundLayer = layer.open({type:1,title:'退款详情',area:'500px',content:html,resize:true,shadeClose:true,maxmin:true,btn: ['确定', '取消'],
					yes:function(){
						var reason = $('#tuireason').val();
						var refundNum = [];
						var money = $('#refund_money').val();
						$(".refundNum").each(function(){
							refundNum.push({ogid:$(this).attr('data-ogid'),num:$(this).val()})
						});
						var refund_type = $("input[name=refund_type]").val();
						// console.log(refund_type);return;
						if(reason == '') {
							layer.msg('请填写退款原因');return false;
						}
						if(money < 0) {
							layer.msg('请输入正确的退款金额');return false;
						}

						var index = layer.load();
						$.post("<?php echo url('refund'); ?>",{orderid:orderid},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(refundLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					}
				})
			});
		});
	}

	function checkdepositrefund(orderid){

		var html = '';
		html+='	<div  style="padding: 20px 20px;text-align: center">';
		html+='审核通过后，商户保证金将清零，请线下支付给商户';
		html+='	</div>';
		var checkLayer = layer.open({type:1,area:['400px','120px'],title:false,content:html,shadeClose:true,btn: ['通过', '驳回'],
			btn1:function(){
				var index = layer.load();
				$.post("<?php echo url('setdepositrefund'); ?>",{orderid:orderid,st:2},function(res){
					if(res.code ==0){
						layer.error(res.msg);
					}
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(checkLayer);
					tableIns.reload()
				})
			},	btn2:function(){
				var index = layer.load();
				$.post("<?php echo url('setdepositrefund'); ?>",{orderid:orderid,st:3},function(res){
					if(res.code ==0){
						layer.error(res.msg);
					}
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(checkLayer);
					tableIns.reload()
				})
			}
		})
		
	}
	<?php if(getcustom('alipay_fenzhang')): ?>
	var makelayer = null;
	function alipayset(id) {
		layer.close(makelayer)
		var index = layer.load();
		$.get("<?php echo url('alipaySet'); ?>",{id:id},function(res){
			var data = res.data; 
			layer.close(index);
		
			var alipayst1 = data.alipayst =='1'?'checked':'';
			var alipayst0 = data.alipayst =='0'?'checked':'';
			console.log(alipayst1);
			console.log(alipayst0,'alipayst0');
			var html = '<div style="margin:20px auto;">';
			html+='<div class="layui-form" lay-filter="">';
			html+=' <input type="hidden" class="layui-input"  name="id" value="'+data.id+'" disabled="true">';
			
			html+=' <div class="layui-form-item">';
			html+='   <label class="layui-form-label" style="width:150px">独立收款：</label>';
			html+='   <div class="layui-input-inline" style="width:300px">';
			html+='     <input type="radio" name="alipayst" '+alipayst1+' value="1" title="开启"/>';
			html+='     <input type="radio" name="alipayst" '+alipayst0+' value="0"  title="关闭"/>';
			html+='   </div>';
			html+='  <div class="layui-form-mid" style="margin-left:180px;display: block">开启后，服务商代商家收款，且开启分账，请在[控制台]-[第三方应用]中配置第三方应用信息。</div>';
			html+=' </div>';
			
			html+=' <div class="layui-form-item">';
			html+='   <label class="layui-form-label" style="width:150px">APPID：</label>';
			html+='   <div class="layui-input-inline" style="width:300px;display: flex">';
			html+='     <input type="text" class="layui-input" name="alipayappid" value="'+data.alipayappid+'" >';
			html+='     <button class="layui-btn layui-btn-primary" onclick="alipayIsvAuth(this)">授权</button>';
			if(data.alipay_app_auth_token){
				html+='     <div style="width: 100px; line-height: 40px;margin-left: 10px;color: green;">已授权</div>';
			}
	
			html+='   </div>';
			html+='  <div class="layui-form-mid" style="margin-left:180px;display: block">商家应用的APPID,登录<a href="https://b.alipay.com" target="_blank">商家平台</a>在[账号中心]-[APPID绑定]-[已绑定应用]中查找需要授权的APPID。</div>';
			html+=' </div>';


			html+=' <div class="layui-form-item" style="margin-top:20px">';
			html+='   <label class="layui-form-label" style="width:150px">授权token：</label>';
			html+='   <div class="layui-input-inline" style="width:300px;display: flex">';
			html+='     <input type="text" class="layui-input" name="alipay_app_auth_token" value="'+data.alipay_app_auth_token+'" >';
			html+='   </div>';
			html+='  <div class="layui-form-mid" style="margin-left:180px;display: block">可进入支付宝<a href="https://open.alipay.com/" target="_blank">开放平台</a>在[商家授权]-[邀请商家授权]后复制粘贴[授权token]。</div>';
			html+=' </div>';
			
			html+=' <div class="layui-form-item" style="margin-top:30px">';
			html+='   <label class="layui-form-label" style="width:150px"></label>';
			html+='   <div class="layui-input-inline">';
			html+='     <button class="layui-btn layui-btn-success" data-type="refresh"  lay-submit lay-filter="submit_alipay">刷 新</button>';
			html+='     <button class="layui-btn layui-btn-normal" data-type="submit" lay-submit lay-filter="submit_alipay">确 定</button>';
			html+='   </div>';
			html+=' </div>';
			
			html+='</div>';
			html+='</div>'
			 makelayer = layer.open({type:1,area: ['45%', '480px'],title:'支付宝独立收款',content:html,shadeClose:true})
			layui.form.render();
			layui.form.on('submit(submit_alipay)', function(obj){
			
				var type = $(this).attr('data-type');
				var field = obj.field;
				var index= layer.load();
				$.post("<?php echo url('alipaySet'); ?>",field,function(data){
					layer.close(index);
					if(type =='submit'){
						dialog(data.msg,data.status,data.url)
					}
					if(data.status == 1){
						layer.close(makelayer)
						if(type =='refresh'){
							alipayset(id);
						}
						tableIns.reload()
					}
				})
			})
		})
		
	}
		function alipayIsvAuth(){
			var alipayappid  = $('#alipayappid').val();
			if(alipayappid ==''){
				dialog("请输入Appid");
				return;
			}
			var index = layer.load();
			$.post("<?php echo url('alipayIsvAuthorization'); ?>", {appid:alipayappid},function(res){
				layer.close(index);
				if(res.status == 1){
					var html = '';
					html += '<div style="margin:auto auto;text-align:center">';
					html += '<img src="'+res.data+'" style="margin-top:20px;max-width:280px;max-height:280px"/>';
					html += '<div style="height:25px;line-height:25px;">商家使用【支付宝客户端】扫描二维码授权</div>';
					html += '</div>';
					loginlayer = layer.open({type:1,area:['300px','350px'],content:html,title:false,shadeClose:false,cancel:function(){
							//clearInterval(timer);
						}});
				}else{
					dialog(res.msg,res.status);
				}
			})
		}
		function refresh(id){
			
		}
	<?php endif; if(getcustom('member_dedamount')): ?>
		function setPaymoneyGivepercent(id,st){
				if(st == 1){
					var msg = '确定要通过让利比例吗?';
				}else{
					var msg = '确定要驳回让利比例吗?';
				}
				layer.confirm(msg,{icon: 7, title:'操作确认'}, function(index){
					layer.close(index);
					var index = layer.load();
					$.post("<?php echo url('setPaymoneyGivepercent'); ?>",{id:id,st:st},function(data){
						layer.close(index);
						dialog(data.msg,data.status);
						tableIns.reload()
					})
				});
		}
	<?php endif; if(getcustom('yx_buyer_subsidy')): ?>
		function setMaidanRate(id,st){
			if(st == 1){
				var msg = '确定要通过买单抽成费率吗?';
			}else{
				var msg = '确定要驳回买单抽成费率吗?';
			}
			layer.confirm(msg,{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('setMaidanRate'); ?>",{id:id,st:st},function(data){
					layer.close(index);
					dialog(data.msg,data.status);
					tableIns.reload()
				})
			});
		}
		function setFeepercent(id,st){
			if(st == 1){
				var msg = '确定要通过商品抽成费率吗?';
			}else{
				var msg = '确定要驳回商品抽成费率吗?';
			}
			layer.confirm(msg,{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('setFeepercent'); ?>",{id:id,st:st},function(data){
					layer.close(index);
					dialog(data.msg,data.status);
					tableIns.reload()
				})
			});
		}
		<?php endif; if(getcustom('business_pc_yeji_ranking') && getcustom('yx_new_score_active')): ?>
				function refreshYeji(){
					layer.confirm('确认更新所有商户业绩吗？',{icon: 7, title:'操作确认'}, function(index){
						var index = layer.load();
						$.post("<?php echo url('refreshYeji'); ?>",{},function(data){
							layer.close(index);
							dialog('操作成功',data.status);
							tableIns.reload()
						})
					});
				}
			<?php endif; ?>
	</script>
</body>
</html>