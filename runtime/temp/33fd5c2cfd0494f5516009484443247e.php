<?php /*a:4:{s:51:"/www/wwwroot/eivie/app/view/shop_product/index.html";i:1764308976;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;s:49:"/www/wwwroot/eivie/app/view/public/copyright.html";i:1648714895;}*/ ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>商品管理</title>
  <meta name="renderer" content="webkit">
	<!-- <meta name="referrer" content="no-referrer" /> -->
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
	.layui-form-select dl{ height:200px}
	#ggnamediv .layui-input,#ggvaldiv .layui-input{ display:inline;height:30px}
	.layui-table-view .layui-table td .table-imgbox{width:70px;height:70px;float:left;display: flex;align-items: center;justify-content: center}
	.layui-table-view .layui-table td .table-imgbox img{max-width:70px;max-height:70px;float:left;}
	.layui-option-but button{margin-bottom:10px}
	.layui-form-search .layui-inline{margin: 4px 0}
  </style>
</head>
<body>
  <div class="layui-fluid">
    <div class="layui-row layui-col-space15">
        <div class="layui-card layui-col-md12">
          <div class="layui-card-header">商品管理<?php if(input('param.isopen')==1): ?><i class="layui-icon layui-icon-close" style="font-size:18px;font-weight:bold;cursor:pointer" onclick="closeself()"></i><?php endif; ?></div>
          <div class="layui-card-body" pad15>
						<div class="layui-col-md12 layui-option-but">
							<?php if(input('param.showtype')!=2 && $add_product==1): ?><a style="margin-bottom:10px" class="layui-btn layuiadmin-btn-list" href="javascript:void(0)" onclick="openmax('<?php echo url('edit'); if(input('param.showtype')==21): ?>&bid=-1<?php endif; if($fromwxvideo==1): ?>&fromwxvideo=1<?php endif; ?>')">添加</a><?php endif; ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="datadel(0)">删除</button>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="setst(0,1)">上架</button>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="setst(0,0)">下架</button>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="daoru()">导入</button>
							<?php if(getcustom('product_update_excel')): ?>
							<!--<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="dataexcel()">导出</button>-->
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" data-form-export="<?php echo url('excel'); ?>">导出</button>
							<?php endif; if($cancopy): ?>
								<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="copyProduct(0)">复制商品</button>
							<?php endif; if(getcustom('jushuitan') && $admin['jushuitan_status']==1 && $bid==0): ?>
								<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="tongbu()">同步商品</button>
							<?php endif; if($fromwxvideo==1): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="towxvideo(0)">上传</button>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="wxvideoupdatest()">更新</button>
							<?php endif; if(getcustom('product_category_batch_update')): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="editCategory()">编辑分类</button>
							<?php if($bid > 0): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="editCategory2()">编辑商家分类</button>
							<?php endif; ?>
							<?php endif; if(getcustom('product_sync_business') && $bid==0): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="to_business(0)">同步到商家</button>
							<?php endif; if(getcustom('shop_add_stock')  && $bid==0): ?>
								<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="openmax('<?php echo url('ShopStock/add'); ?>/isopen/1')">库存录入</button>	
							<?php endif; if(getcustom('erp_wangdiantong') && $erpWdtOpen==1): ?>
								<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="refreshWdtStock(0)">刷新库存</button>
							<?php endif; if(getcustom('product_batch_changeprice') && $auth_data=='all' || in_array('product_batch_changeprice',$auth_data)): ?>
								<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="batchChangeprice(0)">批量改价</button>
							<?php endif; if(getcustom('erp_hupun') && $auth_data=='all' || in_array('HupunSet/*',$auth_data)): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="synchupun()">同步商品</button>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="refreshWlnStock()">同步库存</button>
							<?php endif; if(getcustom('supply_yongsheng')): if($auth_data=='all' || in_array('SupplyYongshengProduct/*',$auth_data)): ?>
								<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="addYongshengProduct()">添加<?php echo $yongshengname; ?>商品</button>
								<?php endif; ?>
							<?php endif; ?>
						</div>
						<div class="layui-form layui-form-search layui-col-md12">
								<?php if(getcustom('shop_product_code_search')): ?>
								<div class="layui-inline layuiadmin-input-useradmin">
									<label class="layui-form-label">商品编码</label>
									<div class="layui-input-inline">
										<input type="text" name="code" autocomplete="off" class="layui-input">
									</div>
								</div>
								<?php endif; if(getcustom('product_supplier')): ?>
								<div class="layui-inline layuiadmin-input-useradmin">
									<label class="layui-form-label">供应商编号</label>
									<div class="layui-input-inline">
										<input type="text" name="supplier_number" autocomplete="off" class="layui-input">
									</div>
								</div>
								<?php endif; ?>
								<div class="layui-inline layuiadmin-input-useradmin">
									<label class="layui-form-label">商品名称</label>
									<div class="layui-input-inline">
										<input type="text" name="name" autocomplete="off" class="layui-input">
									</div>
								</div>
								<div class="layui-inline">
									<label class="layui-form-label"><?php if($bid > 0): ?>平台<?php endif; ?>分类</label>
									<div class="layui-input-inline">
										<select name="cid">
											<option value="">全部</option>
											<?php foreach($clist as $cv): ?>
												<option value="<?php echo $cv['id']; ?>" <?php if($cv['id']==input('param.cid')): ?>selected<?php endif; ?>><?php echo $cv['name']; ?></option>
												<?php foreach($cv['child'] as $v): ?>
												<option value="<?php echo $v['id']; ?>" <?php if($v['id']==input('param.cid')): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;<?php echo $v['name']; ?></option>
													<?php foreach($v['child'] as $v3): ?>
													<option value="<?php echo $v3['id']; ?>" <?php if($v3['id']==input('param.cid')): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $v3['name']; ?></option>
													<?php endforeach; ?>
												<?php endforeach; ?>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
								<?php if($bid > 0): ?>
								<div class="layui-inline">
									<label class="layui-form-label">商家分类</label>
									<div class="layui-input-inline">
										<select name="cid2">
											<option value="">全部</option>
											<?php foreach($clist2 as $cv): ?>
												<option value="<?php echo $cv['id']; ?>" <?php if($cv['id']==input('param.cid')): ?>selected<?php endif; ?>><?php echo $cv['name']; ?></option>
												<?php foreach($cv['child'] as $v): ?>
												<option value="<?php echo $v['id']; ?>" <?php if($v['id']==input('param.cid')): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;<?php echo $v['name']; ?></option>
												<?php endforeach; ?>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
								<?php endif; ?>
								<div class="layui-inline">
									<label class="layui-form-label">分组</label>
									<div class="layui-input-inline">
										<select name="gid">
											<option value="">全部</option>
											<?php foreach($glist as $g): ?><option value="<?php echo $g['id']; ?>" <?php if($g['id']==input('param.gid')): ?>selected<?php endif; ?>><?php echo $g['name']; ?></option><?php endforeach; ?>
										</select>
									</div>
								</div>
								<div class="layui-inline">
									<label class="layui-form-label">状态</label>
									<div class="layui-input-inline">
										<select name="status">
											<option value="">全部</option>
											<option value="1">已上架</option>
											<option value="0">未上架</option>
										</select>
									</div>
								</div>
								<?php if(input('param.showtype')==2 || $bid!=0): ?>
								<div class="layui-inline">
									<label class="layui-form-label">审核状态</label>
									<div class="layui-input-inline">
										<select name="ischecked">
											<option value="">全部</option>
											<option value="0">待审核</option>
											<option value="1">已通过</option>
											<option value="2">已驳回</option>
										</select>
									</div>
								</div>
								<?php endif; if(input('param.showtype')==2): ?>
							<div class="layui-inline" style="text-align:left;width: 130px">
								<div class="layui-input-block" style="margin-left: 0">
									<select name="bid" lay-search>
										<option value="">请选择商户</option>
										<?php foreach($business_list as $k=>$v): ?>
										<option value="<?php echo $v['id']; ?>"><?php echo $v['name']; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<?php endif; if($fromwxvideo==1): ?>
								<div class="layui-inline">
									<label class="layui-form-label">视频号状态</label>
									<div class="layui-input-block">
										<select name="wxvideo_status">
											<option value="">全部</option>
											<option value="0">未上传</option>
											<option value="1">未审核</option>
											<option value="2">审核中</option>
											<option value="3">审核失败</option>
											<option value="4">审核成功</option>
											<option value="5">已上架</option>
											<option value="11">已下架</option>
											<option value="13">违规/风控</option>
										</select>
									</div>
								</div>
								<?php endif; if(getcustom('supply_zhenxin') || getcustom('supply_yongsheng')): ?>
								<div class="layui-inline">
									<label class="layui-form-label">商品来源</label>
									<div class="layui-input-inline">
										<select name="source">
											<option value="">全部</option>
											<option value="self">系统</option>
											<?php if(getcustom('supply_zhenxin')): ?><option value="supply_zhenxin" <?php if(input('?param.source') && input('param.source') == "supply_zhenxin"): ?>selected<?php endif; ?>>甄新汇选</option><?php endif; if(getcustom('supply_yongsheng')): ?><option value="supply_yongsheng" <?php if(input('?param.source') && input('param.source') == "supply_yongsheng"): ?>selected<?php endif; ?>><?php echo $yongshengname; ?></option><?php endif; ?>
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
						<div class="layui-col-md12">
							<table id="tabledata" lay-filter="tabledata"></table>
						</div>
          </div>
        </div>
    </div>
  </div>
  <?php if(getcustom('product_category_batch_update')): ?>
  <div class="layui-form" lay-filter="" style="margin-top:20px; display: none" id="selectCategory">
	  <div class="layui-form-item">
		  <label class="layui-form-label">商品分类：</label>
		  <div class="layui-input-inline" style="width:400px">
			  <select name="info[cid]" id="cid" xm-select="selectCid" xm-select-max="5" xm-select-search >
				  <option value="">--请选择--</option>
				  <?php foreach($clist as $cv): ?>
				  <option value="<?php echo $cv['id']; ?>" <?php if(in_array($cv['id'],$info['cid'])): ?>selected<?php endif; ?>><?php echo $cv['name']; ?></option>
				  <?php foreach($cv['child'] as $k=>$v): if($k < count($cv['child'])-1): ?>
				  <option value="<?php echo $v['id']; ?>" <?php if(in_array($v['id'],$info['cid'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;├ <?php echo $v['name']; ?></option>
				  <?php else: ?>
				  <option value="<?php echo $v['id']; ?>" <?php if(in_array($v['id'],$info['cid'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;└ <?php echo $v['name']; ?></option>
				  <?php endif; foreach($v['child'] as $k2=>$v2): if($k2 < count($v['child'])-1): ?>
				  <option value="<?php echo $v2['id']; ?>" <?php if(in_array($v2['id'],$info['cid'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;├ <?php echo $v2['name']; ?></option>
				  <?php else: ?>
				  <option value="<?php echo $v2['id']; ?>" <?php if(in_array($v2['id'],$info['cid'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└ <?php echo $v2['name']; ?></option>
				  <?php endif; ?>
				  <?php endforeach; ?>
				  <?php endforeach; ?>
				  <?php endforeach; ?>
			  </select>
		  </div>
		  <div class="layui-form-mid layui-word-aux">最多可选5个分类</div>
		  <!-- <div class="layui-form-mid layui-word-aux"><a href="categoryadd.php">创建分类</a></div> -->
	  </div>
  </div>
  <div class="layui-form" lay-filter="" style="margin-top:20px; display: none" id="selectCategory2">
	  <div class="layui-form-item">
		  <label class="layui-form-label">商家商品分类：</label>
		  <div class="layui-input-inline" style="width:400px">
			  <select name="info[cid2]" id="cid2" xm-select="selectCid" xm-select-max="5" xm-select-search >
				  <option value="">--请选择--</option>
				  <?php foreach($clist2 as $cv): ?>
				  <option value="<?php echo $cv['id']; ?>" <?php if(in_array($cv['id'],$info['cid2'])): ?>selected<?php endif; ?>><?php echo $cv['name']; ?></option>
				  <?php foreach($cv['child'] as $k=>$v): if($k < count($cv['child'])-1): ?>
				  <option value="<?php echo $v['id']; ?>" <?php if(in_array($v['id'],$info['cid2'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;├ <?php echo $v['name']; ?></option>
				  <?php else: ?>
				  <option value="<?php echo $v['id']; ?>" <?php if(in_array($v['id'],$info['cid2'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;└ <?php echo $v['name']; ?></option>
				  <?php endif; foreach($v['child'] as $k2=>$v2): if($k2 < count($v['child'])-1): ?>
				  <option value="<?php echo $v2['id']; ?>" <?php if(in_array($v2['id'],$info['cid2'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;├ <?php echo $v2['name']; ?></option>
				  <?php else: ?>
				  <option value="<?php echo $v2['id']; ?>" <?php if(in_array($v2['id'],$info['cid2'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└ <?php echo $v2['name']; ?></option>
				  <?php endif; ?>
				  <?php endforeach; ?>
				  <?php endforeach; ?>
				  <?php endforeach; ?>
			  </select>
		  </div>
		  <div class="layui-form-mid layui-word-aux">最多可选5个分类</div>
		  <!-- <div class="layui-form-mid layui-word-aux"><a href="categoryadd.php">创建分类</a></div> -->
	  </div>
  </div>
  <?php endif; if(getcustom('jushuitan') && $admin['jushuitan_status']==1): ?>
	<div id="tongbuModel" style="width:500px;display:none;margin-top:30px">
		<div class="layui-form" lay-filter="">
			<div class="layui-form-item">
				<label class="layui-form-label" style="width: 130px">选择时间:</label>
				<div class="layui-input-inline" style="width: 210px">
					<input type="text" name="ctime" id="ctime" autocomplete="off" class="layui-input">
				</div>
				<div class="layui-form-mid layui-word-aux" style="width: 400px;margin-left: 160px;">商品在聚水潭的更新的时间</div>
			</div>
			<div class="layui-form-item">
				<div class="layui-input-block">
					<button class="layui-btn layui-btn-normal" lay-submit lay-filter="subtongbu">确定</button>
				</div>
			</div>
		</div>
	</div>
	<?php endif; if(getcustom('supply_yongsheng')): ?>
	<script id="sourceStatusMsgTpl" type="text/html">
		<div class="layui-form" style="padding:20px">
			<div class="layui-form-item">
				<label class="layui-form-label" style="width: 130px"></label>
				<div class="layui-input-inline" style="width: auto">
					{{d}}
				</div>
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
  var table = layui.table;
	var datawhere = {};
	// 图片懒加载必须使用
	layui.use('flow', function(){
		var flow = layui.flow;
		flow.lazyimg();
	});
  //数据表
  var tableIns = table.render({
    elem: '#tabledata'
    ,url: "<?php echo app('request')->url(); ?>" //数据接口
    ,page: true //开启分页
	,limits:[10,20,50,100,200]
    ,cols: [[ //表头
			{type:"checkbox"},
      {field: 'id', title: 'ID',  sort: true,width:80},
			<?php if(input('param.showtype')==2): ?>
      {field: 'bname', title: '所属商户',width:160},
			<?php endif; if(getcustom('product_supplier_admin')): ?>
		  {field: 'supplier_name', title: '所属供应商'},
		  <?php endif; ?>
      {field: 'name', title: '商品信息',width:320,templet:function(d){
				var html = '';
				html += '<div class="table-imgbox"><img lay-src="'+d.pic+'" src="/static/admin/layui/css/modules/layer/default/loading-2.gif"/></div>';
				html += '<div style="float: left;width:200px;margin-left: 10px;white-space:normal;line-height:20px;">';
				html += '	<div style="width:100%;">'+(d.cid!=0?'<span style="color:red">['+d.cname+']'+(d.cname2?'['+d.cname2+']':'')+'</span>':'')+d.name+'</div>';
				<?php if(getcustom('price_dollar')): ?>
					if(d.usdprice>0){
						html += '	<div style="padding-top:5px;color:#f60;float:left;margin-right:10px">$'+d.usdprice+'</div>';	
					}
				<?php endif; ?>
				if(d.price_type == 1)
					html += '	<div style="padding-top:5px;color:#f60">询价</div>';
				else
					<?php if(getcustom('product_service_fee') && ($auth_data=='all' || in_array('service_fee_switch',$auth_data))): ?>
						if(d.service_fee_switch && d.service_fee>0){
							d.sell_price = d.sell_price+' + ' + d.service_fee + '<?php echo t('服务费'); ?>';
						}
					<?php endif; ?>
					html += '	<div style="padding-top:5px;color:#f60">￥'+d.sell_price+'</div>';

					<?php if(getcustom('freeze_money')): ?>
					if(d.freezemoney_price && d.freezemoney_price>0){
						html += '	<div style="padding-top:5px;color:#f60">+'+d.freezemoney_price+' <?php echo t('冻结资金'); ?></div>';
					}
					<?php endif; ?>
				html += '</div>';
				return html;
			}},
	  <?php if(getcustom('product_stock_warning')): ?>
		   {field: 'ggdata', title: '商品规格',sort: true},
	  <?php endif; ?>
      {field: 'stock', title: '库存',sort: true},
      {field: 'sales', title: '显示销量',sort: true},
      {field: 'realsalenum', title: '实际销量'},
      <?php if($fromwxvideo!=1): ?>{field: 'sort', title: '序号',sort: true},<?php endif; ?>
      {field: 'createtime', title: '创建时间',sort: true,templet:function(d){ return date('Y-m-d H:i',d.createtime)}},
      {field: 'status', title: '商品状态',templet:function(d){ 
      	var html = d.status==1?'<span style="color:green">已上架</span>':'<span style="color:red">未上架</span>';
      	<?php if(getcustom('supply_yongsheng')): ?>
      		  if(d.sproduct_status) html += '<div style="color:red">'+d.sproduct_status+'</div>'
      		  if(d.source_status_msg){
      		  	html += '<div style="color:red">供应链商品发生变动</div>'
      		  	html += '<button class="table-btn" onclick="lookSourceChange(\''+d.source_status_msg+'\')">查看变动</button>'
      		  } 
      	<?php endif; ?>
      	return html;
      },width:100},
			<?php if(input('param.showtype')==2 || $bid!=0): ?>
			{field: 'status', title: '审核状态',templet:function(d){ 
				if(d.ischecked==0) return '<span style="color:blue">待审核</span>';
				if(d.ischecked==1) return '<span style="color:green">已通过</span>';
				if(d.ischecked==2) return '<span style="color:red">已驳回</span>';
			},width:100},
			<?php endif; if($fromwxvideo==1): ?>
			{field: 'status', title: '审核状态',templet:function(d){ 
				if(d.wxvideo_product_id=='') return '<button class="table-btn" style="color:#999;">未上传</button>';
				if(d.wxvideo_edit_status==0) return '<button class="table-btn" style="color:#999;">初始状态</button>';
				if(d.wxvideo_edit_status==1) return '<button class="table-btn" style="color:blue">未审核</button>';
				if(d.wxvideo_edit_status==2) return '<button class="table-btn" style="color:blue" onclick="wxvideo_del_audit(\''+d.id+'\')">审核中</button>';
				if(d.wxvideo_edit_status==3) return '<button class="table-btn" style="color:red" onclick="viewrejectReason(\''+d.id+'\')">审核失败</button>';
				if(d.wxvideo_edit_status==4) return '<button class="table-btn" style="color:green">审核成功</button>';
				return '';
			},width:100},
			{field: 'status', title: '上架状态',templet:function(d){ 
				if(d.wxvideo_status==0) return '<button class="table-btn" style="color:#999;">未上架</button>';
				if(d.wxvideo_status==5) return '<button class="table-btn" style="color:green" onclick="wxvideo_delisting(\''+d.id+'\')">已上架</button>';
				if(d.wxvideo_status==11) return '<button class="table-btn" style="color:red" onclick="wxvideo_listing(\''+d.id+'\')">已下架</button>';
				if(d.wxvideo_status==13) return '<button class="table-btn" style="color:red">违规/风控</button>';
				return '';
			},width:100},
			<?php endif; if(getcustom('supply_zhenxin') || getcustom('supply_yongsheng')): ?>
			{field: 'source', title: '商品来源',templet:function(d){
				var html = '';
				if(d.issource==0){
					html = '系统';
				}else{
					if(d.source == 'supply_zhenxin'){
						<?php if(getcustom('supply_zhenxin')): ?>
						html += '甄新汇选<br>ID:'+d.sproid+'<br>';
          	html += '<button class="table-btn" onclick="showzxproduct(\''+d.sproid+'\')">点击查看</button>';
          	<?php endif; ?>
					}else if(d.source == 'supply_yongsheng'){
						<?php if(getcustom('supply_yongsheng')): ?>
							html += '<?php echo $yongshengname; ?><br>ID:'+d.sproid+'<br>';
	          	html += '<button class="table-btn" onclick="showysproduct(\''+d.sproid+'\')">点击查看</button>';
						<?php endif; ?>
					}
				}
				return html;
			},width:100},
			<?php endif; ?>
      {field: 'operation', title: '操作',templet:function(d){
				if('<?php echo $bid; ?>'!=0 && d.linkid>0 && !d.iscustomoption){
					return '';
				}
			  if('<?php echo $bid; ?>'!=0 && d.plate_id>0){
				  	var html = '';
				  <?php if($status_product==1): ?>
					  if(d.status == 0){
						  html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){setst('+d.id+',1)})">上架</button>';
					  }else{
						  html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){setst('+d.id+',0)})">下架</button>';
					  }
				  <?php endif; if($stock_product==1): ?>
			  		html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){changeprice('+d.id+')})">修改价格</button>';
					  <?php endif; ?>
					return html;
			  }
				var html = '';
				html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){showqr('+d.id+')})">查看链接</button>';
				html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){procopy('+d.id+')})">复制</button>';
				html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){openmax(\'<?php echo url('edit'); ?>/id/'+d.id+'<?php if($fromwxvideo==1): ?>&fromwxvideo=1<?php endif; ?>\')})">编辑</button>';
				html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){datadel('+d.id+')})">删除</button>';
				if(d.status == 0){
					html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){setst('+d.id+',1)})">上架</button>';
				}else{
					html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){setst('+d.id+',0)})">下架</button>';
				}
				if(d.freighttype == 4){
					html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){openmax(\'<?php echo url('ShopCode/codelist'); ?>/proid/'+d.id+'/isopen/1\')})">卡密信息</button>';
				}
				if('<?php echo $bid; ?>'==0 && d.bid>0){
					if(d.ischecked == 0 || d.ischecked == 2){
						html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){setcheckst(\''+d.id+'\',1)})">通过</button>';
					}
					if(d.ischecked == 0 || d.ischecked == 1){
						html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){setcheckst(\''+d.id+'\',2)})">驳回</button>';
					}
				}
				html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){changeprice('+d.id+')})">改价</button>';
				<?php if(getcustom('guige_split')): ?>
				html += '<button class="table-btn" onclick="checklock('+d.islock+',function(){guige_split('+d.id+')})">规格拆分</button>';
				<?php endif; if(getcustom('product_weight')): ?>
					if(d.product_type==2){
						html += '<button class="table-btn" onclick="openmax(\'<?php echo url('ShCustomerPrice/add'); ?>/isopen/1/proid/'+d.id+'\')">客户定价</button>';
					}
				<?php endif; if(getcustom('edit_locking') && session('IS_ADMIN')>0): ?>
				if(d.islock == 1){
					html += '<button class="table-btn" onClick="checklock('+d.islock+',function(){dolock('+d.id+',0)})">解锁</button>';
				}else{
					html += '<button class="table-btn" onClick="dolock('+d.id+',1)">锁定</button>';
				}
				<?php endif; if(getcustom('product_mendian_hexiao_givemoney')): ?>
					html += '<button class="table-btn" onclick="openmax(\'<?php echo url('mendian_hexiao_set'); ?>/proid/'+d.id+'\')" >设置门店提成</button>';
				<?php endif; if(getcustom('product_sync_business')): ?>
					html += '<button class="table-btn" onclick="openmax(\'<?php echo url('Business/index'); ?>/isopen/1/plate_proid/'+d.id+'\')" >已同步商家</button>';
					<?php endif; if(getcustom('shop_add_stock')): ?>
				if('<?php echo $bid; ?>'==0){
					html += '<button class="table-btn" onclick="openmax(\'<?php echo url('ShopStock/index'); ?>/isopen/1/proid/'+d.id+'\')">库存记录</button>';
				}	
				<?php endif; if(getcustom('erp_wangdiantong') && $erpWdtOpen==1): ?>

				html += '<button class="table-btn" onclick="erpUnBind('+d.id+',2)">解绑ERP</button>';
				html += '<button class="table-btn" onclick="erpUnBind('+d.id+',1)">绑定ERP</button>';
				html += '<button class="table-btn" onclick="refreshWdtStock('+d.id+')">刷新库存</button>';

				<?php endif; ?>
				return html;
      }}
    ]],
		// 监听表格的数据更新
		done: function(res, curr, count){
			if(document.documentElement.scrollTop >= 300){
				document.documentElement.scrollTop = document.documentElement.scrollTop - 5
			}else{
				document.documentElement.scrollTop = document.documentElement.scrollTop + 5 
				$(".layui-table-body .table-imgbox").each(function(){
					 var src = $(this).find('img').attr('lay-src');
					 $(this).find('img').attr('src',src)
				})
			}
		}
  });
	// 查看链接
	function showqr(id){
		var pagepath = 'pages/shop/product?id='+id;
		viewLink(pagepath); 
	}
	// 家庭
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
	function procopy(id){
		layer.confirm('确定要复制该活动吗?',{icon: 7, title:'操作确认'}, function(index){
			//do something
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('procopy'); ?>",{id:id},function(data){
				layer.close(index);
				if(data.status == 1){
					tableIns.reload();
					layer.confirm('复制成功，是否前去编辑？', {icon: 1, title:'提示',btn:['是','否']}, function(index){
						layer.close(index);
						openmax("<?php echo url('edit'); ?>/id/"+data.proid);
					},function(index){
						layer.close(index);
					});
				}else{
					dialog(data.msg,data.status);
				}
			})
		});
	}
	<?php if(getcustom('erp_wangdiantong')): ?>
	function refreshWdtStock(id){
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
		var index = layer.load();
		$.post("<?php echo url('refreshWdt'); ?>",{ids:ids},function(data){
			layer.close(index);
			dialog(data.msg,data.status);
			if(data.status == 1){
				tableIns.reload();
			}
		})
	}
	<?php endif; if(getcustom('product_batch_changeprice')): ?>
	function batchChangeprice(id){
		var ids = [];
		if(id==0){
			var checkStatus = table.checkStatus('tabledata')
			var checkData = checkStatus.data; //得到选中的数据
			if(checkData.length === 0){
			//	return layer.msg('请选择数据');
			}
			var ids = [];
			for(var i=0;i<checkData.length;i++){
				ids.push(checkData[i]['id']);
			}
		}else{
			ids.push(id)
		}
		
		var html = '';
		html ='<div style="margin:20px auto;">';
		html+='<div class="layui-form" lay-filter="batchChangeprice">';
		html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
		html+='		<label class="layui-form-label">改价商品：</label>';
		html+='		<div class="layui-input-block" style="width:500px">';
		html+='			<input type="radio" name="batchchangeprice_type" value="0" title="选中商品('+ids.length+'件)" lay-filter="batchChangepriceType" checked>';
		html+='			<input type="radio" name="batchchangeprice_type" value="1" title="选择分类" lay-filter="batchChangepriceType">';
		html+='		</div>';
		html+='	</div>';
		html+='	<div class="layui-form-item" id="batchChangepriceType_1" style="display:none">';
		html+='	  <label class="layui-form-label">商品分类：</label>';
		html+='	  <div class="layui-input-inline" style="width:400px">';
		html+='		  <select name="batchchangeprice_cid" id="cid">';
		html+='			  <option value="">--请选择--</option>';
		<?php foreach($clist as $cv): ?>
		html+='			  <option value="<?php echo $cv['id']; ?>" <?php if(in_array($cv['id'],$info['cid'])): ?>selected<?php endif; ?>><?php echo $cv['name']; ?></option>';
			<?php foreach($cv['child'] as $k=>$v): if($k < count($cv['child'])-1): ?>
		html+='			  <option value="<?php echo $v['id']; ?>" <?php if(in_array($v['id'],$info['cid'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;├ <?php echo $v['name']; ?></option>';
				  <?php else: ?>
		html+='			  <option value="<?php echo $v['id']; ?>" <?php if(in_array($v['id'],$info['cid'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;└ <?php echo $v['name']; ?></option>';
				  <?php endif; foreach($v['child'] as $k2=>$v2): if($k2 < count($v['child'])-1): ?>
		html+='			  <option value="<?php echo $v2['id']; ?>" <?php if(in_array($v2['id'],$info['cid'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;├ <?php echo $v2['name']; ?></option>';
				  <?php else: ?>
		html+='			  <option value="<?php echo $v2['id']; ?>" <?php if(in_array($v2['id'],$info['cid'])): ?>selected<?php endif; ?>>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└ <?php echo $v2['name']; ?></option>';
				  <?php endif; ?>
				  <?php endforeach; ?>
			<?php endforeach; ?>
		<?php endforeach; ?>
		html+='		  </select>';
		html+='	  </div>';
	  html+='	</div>';

		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label">加价类型：</label>';
		html+='		<div class="layui-input-block" style="width:500px">';
		html+='			<input type="radio" name="batchchangeprice_type2" value="0" title="固定金额(元)" lay-filter="batchChangepriceType2" checked>';
		html+='			<input type="radio" name="batchchangeprice_type2" value="1" title="百分比(%)" lay-filter="batchChangepriceType2">';
		html+='		</div>';
		html+='	</div>';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label"><span id="batchChangepriceType2_0">加价金额</span><span id="batchChangepriceType2_1" style="display:none">加价比例</span>：</label>';
		html+='		<div style="margin-left:110px">';
		<?php foreach($levellist as $level): ?>
		html+='		<div class="layui-input-inline layui-module-itemL" style="margin-bottom:5px">';
		html+='			<div><?php echo $level['name']; ?></div>';
		html+='			<input type="text" name="batchchangeprice_num_<?php echo $level['id']; ?>" class="layui-input" value="">';
		html+='		</div>';
		<?php endforeach; ?>
		html+='		</div>';
		html+='	  <div class="layui-form-mid layui-word-aux" style="margin-left:110px">加价金额以成本价为基础增加固定金额或百分比，留空表示不修改<br>如果商品未开启会员价则将销售价按<?php echo $levellist[0]['name']; ?>填写的加价幅度修改</div>';
		html+='	</div>';
		html+='	<div class="layui-form-item" style="margin-top:30px">';
		html+='		<label class="layui-form-label"></label>';
		html+='		<div class="layui-input-inline">';
		html+='			<button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit_batchChangeprice">确定</button>';
		html+='		</div>';
		html+='	</div>';
		html+='</div>';
		html+='</div>';
		var batchChangepriceLayer = layer.open({type:1,area: ['800px', '600px'],title:'批量改价',content:html,shadeClose:true})
		layui.form.render();
		layui.form.on('radio(batchChangepriceType)', function(data){
			if(data.value == '1'){
				$('#batchChangepriceType_1').show();
			}else{
				$('#batchChangepriceType_1').hide();
			}
		});
		layui.form.on('radio(batchChangepriceType2)', function(data){
			if(data.value == '1'){
				$('#batchChangepriceType2_1').show();
				$('#batchChangepriceType2_0').hide();
			}else{
				$('#batchChangepriceType2_0').show();
				$('#batchChangepriceType2_1').hide();
			}
		});
		layui.form.on('submit(submit_batchChangeprice)', function(obj){
			var field = obj.field;
			field.ids = ids;
			var index= layer.load();
			$.post("<?php echo url('batchChangeprice'); ?>",field,function(res){
				layer.close(index);
				dialog(res.msg,res.status);
				layer.close(batchChangepriceLayer);
				tableIns.reload();
			})
		})
	}
	<?php endif; ?>
	
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
			ids.push(id)
		}
		layer.confirm('确定要'+(st==0?'下架':'上架')+'吗?',{icon: 7, title:'操作确认'}, function(index){
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

	//改价
	function changeprice(proid){
		var index = layer.load();
		$.post("<?php echo url('getproduct'); ?>",{proid:proid},function(data){
			layer.close(index);
			var product = data.product;
			var specs = data.guigedata;
			var gglist = data.gglist;
			var len = specs.length;
			var newlen = 1; 
			var h = new Array(len); 
			var rowspans = new Array(len); 
			var html = '<div style="margin:10px" class="layui-form" lay-filter="changepriceForm"><table id="ggvaldiv" class="layui-table"><thead><tr>';
			for(var i=0;i<len;i++){
				html+="<th>" + specs[i].title + "</th>";
				var itemlen = specs[i].items.length;
				if(itemlen<=0) { itemlen = 1 };
				newlen*=itemlen;
				h[i] = new Array(newlen);
				for(var j=0;j<newlen;j++){
					h[i][j] = new Array();
				}
				var l = specs[i].items.length;
				rowspans[i] = 1;
				for(j=i+1;j<len;j++){
					rowspans[i]*= specs[j].items.length;
				}
			}
			html += '<th>市场价（元）</th>';
			html += '<th>成本价（元）</th>';
			if(product.lvprice==1){
				<?php foreach($levellist as $level): ?>
				html += '<th><?php echo $level['name']; ?>（元）</th>';
				<?php endforeach; ?>
			}else{
				html += '<th>销售价（元）</th>';
			}
			html += '<th>库存</th>';
			html += '</tr></thead>';
			
			for(var m=0;m<len;m++){
				var k = 0,kid = 0,n=0;
				for(var j=0;j<newlen;j++){
					var rowspan = rowspans[m]; 
					if( j % rowspan==0){
						h[m][j]={ k:specs[m].items[kid].k,title: specs[m].items[kid].title, html: "<td rowspan='" +rowspan + "'>"+ specs[m].items[kid].title+"</td>\r\n",id: specs[m].items[kid].id};
					}else{
						h[m][j]={ k:specs[m].items[kid].k,title:specs[m].items[kid].title, html: "",id: specs[m].items[kid].id};	
					}
					n++;
					if(n==rowspan){
						kid++; if(kid>specs[m].items.length-1) { kid=0; }
						n=0;
					}
				}
			}
			var hh = "";
			for(var i=0;i<newlen;i++){
				hh+="<tr>";
				var ks = [];
				var titles = [];
				for(var j=0;j<len;j++){
					hh+=h[j][i].html; 
					ks.push( h[j][i].k);
					titles.push( h[j][i].title);
				}
				ks =ks.join(',');
				titles =titles.join(',');
				if(typeof(gglist[ks])!='undefined'){
					var val = gglist[ks];
				}else{
					var val = { procode:'',market_price:'',cost_price:'',sell_price:'',weight:'',stock:'1000',pic:''};
				}

				hh += '<td>';
				hh += '<input name="option['+ks+'][ggid]" type="hidden" value="'+(val.id)+'"/>';
				hh += '<input name="option['+ks+'][market_price]" type="text" class="layui-input" style="width:70px" value="'+(val.market_price==null?'':val.market_price)+'"/>';
				if(i==0){
					hh += ' <i class="fa fa-hand-o-down" style="cursor:pointer" title="批量设置" onclick="plset(\'market_price\')"></i>';
				}
				hh += '</td>';
				hh += '<td>';
				hh += '	<input name="option['+ks+'][cost_price]" type="text" class="layui-input" style="width:70px" value="'+(val.cost_price==null?'':val.cost_price)+'"/>';
				if(i==0){
					hh += ' <i class="fa fa-hand-o-down" style="cursor:pointer" title="批量设置" onclick="plset(\'cost_price\')"></i>';
				}
				hh += '</td>';
				if(product.lvprice==1){
					val.lvprice_data = JSON.parse(val['lvprice_data']);
					<?php foreach($levellist as $lk=>$level): ?>
					hh += '<td>';
					hh += '	<input name="option['+ks+'][sell_price_<?php echo $level['id']; ?>]" type="text" style="width:70px" value="'+(val.lvprice_data==null || val.lvprice_data[<?php echo $level['id']; ?>]==null?'':val.lvprice_data[<?php echo $level['id']; ?>])+'" class="layui-input" id="levelprice_'+i+'_<?php echo $lk; ?>"/>';
					if(i==0){
						<?php if($lk==0): ?>
						hh += ' <i class="fa fa-hand-o-down" style="cursor:pointer" title="批量设置" onclick="plset(\'sell_price_<?php echo $level['id']; ?>\')"></i>';
						<?php else: ?>
						hh += ' <i class="fa fa-hand-o-down" style="cursor:pointer" title="批量设置" onclick="plsetlevelprice(\''+i+'\',\'<?php echo $lk; ?>\')"></i>';
						<?php endif; ?>
					}
					hh += '</td>';
					<?php endforeach; ?>
				}else{
					hh += '<td>';
					hh += '	<input name="option['+ks+'][sell_price]" type="text" style="width:70px" value="'+(val.sell_price==null?'':val.sell_price)+'" class="layui-input"/>';
					if(i==0){
						hh += ' <i class="fa fa-hand-o-down" style="cursor:pointer" title="批量设置" onclick="plset(\'sell_price\')"></i>';
					}
					hh += '</td>';
				}
				hh += '<td>';
				if(val.caneditstock==1) {
					hh += ' <input name="option[' + ks + '][stock]" type="text" style="width:60px" value="' + (val.stock == null ? '' : val.stock) + '" class="layui-input"/>';
					if (i == 0) {
						hh += ' <i class="fa fa-hand-o-down" style="cursor:pointer" title="批量设置" onclick="plset(\'stock\')"></i>';
					}
				}else{
					hh +='-'
					hh += ' <input name="option[' + ks + '][stock]" type="hidden" style="width:60px" value="' + (val.stock == null ? '' : val.stock) + '" class="layui-input"/>';
				}
				hh += '</td>';
				hh += "</tr>";
			}
			html+=hh;
			html+='</table></div>';
			layer.open({type:1,title:product.name,content:html,area:['900px','600px'],shadeClose:true,btn:['确定','取消'],
				yes:function(index){
					layer.close(index);
					var formdata = layui.form.val("changepriceForm");
					formdata['proid'] = proid;
					console.log(formdata)
					var index = layer.load();
					$.post("<?php echo url('changeprice'); ?>",formdata,function(data){
						layer.close(index);
						dialog(data.msg,data.status);
						tableIns.reload()
					})
				}
			});
		});
	}
<?php if(getcustom('erp_wangdiantong')): ?>
	function erpUnBind(proid,type){
		var index = layer.load();
		$.post("<?php echo url('getproduct'); ?>",{proid:proid},function(data){
			layer.close(index);
			var product = data.product;
			var specs = data.guigedata;
			var gglist = data.gglist;
			var len = specs.length;
			var newlen = 1;
			var h = new Array(len);
			var rowspans = new Array(len);
			var html = '<div style="margin:10px" class="layui-form" lay-filter="erpUnBindForm"><table id="ggvaldiv" class="layui-table"><thead><tr>';
			for(var i=0;i<len;i++){
				html+="<th>" + specs[i].title + "</th>";
				var itemlen = specs[i].items.length;
				if(itemlen<=0) { itemlen = 1 };
				newlen*=itemlen;
				h[i] = new Array(newlen);
				for(var j=0;j<newlen;j++){
					h[i][j] = new Array();
				}
				var l = specs[i].items.length;
				rowspans[i] = 1;
				for(j=i+1;j<len;j++){
					rowspans[i]*= specs[j].items.length;
				}
			}
			html += '<th>市场价（元）</th>';
			html += '<th>成本价（元）</th>';
			if(product.lvprice==1){
				<?php foreach($levellist as $level): ?>
				html += '<th><?php echo $level['name']; ?>（元）</th>';
				<?php endforeach; ?>
				}else{
					html += '<th>销售价（元）</th>';
				}
				html += '<th>库存</th>';
				html += '<th>操作</th>';
				html += '</tr></thead>';

				for(var m=0;m<len;m++){
					var k = 0,kid = 0,n=0;
					for(var j=0;j<newlen;j++){
						var rowspan = rowspans[m];
						if( j % rowspan==0){
							h[m][j]={ k:specs[m].items[kid].k,title: specs[m].items[kid].title, html: "<td rowspan='" +rowspan + "'>"+ specs[m].items[kid].title+"</td>\r\n",id: specs[m].items[kid].id};
						}else{
							h[m][j]={ k:specs[m].items[kid].k,title:specs[m].items[kid].title, html: "",id: specs[m].items[kid].id};
						}
						n++;
						if(n==rowspan){
							kid++; if(kid>specs[m].items.length-1) { kid=0; }
							n=0;
						}
					}
				}
				var hh = "";
				for(var i=0;i<newlen;i++){
					hh+="<tr>";
					var ks = [];
					var ks2 = '';
					var titles = [];
					for(var j=0;j<len;j++){
						hh+=h[j][i].html;
						ks.push( h[j][i].k);
						titles.push( h[j][i].title);
					}
					ks2 = ks.join('_');
					ks =ks.join(',');
					titles =titles.join(',');
					if(typeof(gglist[ks])!='undefined'){
						var val = gglist[ks];
					}else{
						var val = { procode:'',market_price:'',cost_price:'',sell_price:'',weight:'',stock:'1000',pic:''};
					}

					hh += '<td>';
					hh += val.market_price==null?'':val.market_price;
					hh += '</td>';
					hh += '<td>';
					hh += val.cost_price==null?'':val.cost_price;
					hh += '</td>';
					if(product.lvprice==1){
						val.lvprice_data = JSON.parse(val['lvprice_data']);
						<?php foreach($levellist as $lk=>$level): ?>
						hh += '<td>';
						hh += val.lvprice_data==null || val.lvprice_data[<?php echo $level['id']; ?>]==null?'':val.lvprice_data[<?php echo $level['id']; ?>];
						hh += '</td>';
						<?php endforeach; ?>
					}else{
						hh += '<td>';
						hh += (val.sell_price==null?'':val.sell_price);
						hh += '</td>';
					}
					hh += '<td>';
					if(val.caneditstock==1){
							hh += (val.stock==null?'':val.stock);
					}else{
							hh += '-';
					}
					hh += '</td>';
					hh += '<td>';
                        if(type == 2){
                            hh += val.wdt_status === 2 ? '<span style="color:#999">已解绑</span>' : '<input type="checkbox" name="ggid[]" value="'+(val.id)+'" lay-skin="primary">';
                        }else{
	                        hh += val.wdt_status === 1 ? '<span style="color:green">已绑定</span>' : '<input type="checkbox" name="ggid[]" value="'+(val.id)+'" lay-skin="primary">';
                        }
					hh +='</td>';
					hh += "</tr>";
			   }
						html+=hh;
						html+='</table></div>';

                        let title  = type == 2 ? '解绑ERP' : '绑定ERP';
						layer.open({type:1,title:title,content:html,area:['900px','600px'],shadeClose:true,btn:['确定','取消'],
							yes:function(index){
								layer.close(index);
								var formdata = layui.form.val("erpUnBindForm");
								formdata['proid'] = proid;
                                formdata['type'] = type;
								var index = layer.load();
								$.post("<?php echo url('erpUnBind'); ?>",formdata,function(data){
									layer.close(index);
									dialog(data.msg,data.status);
									// tableIns.reload()
								})
							},success:function(){ layui.form.render('checkbox')}
						});
					});
				}
	<?php endif; ?>
	function plset(name){
		$("input[name$='["+name+"]']").val($("input[name$='["+name+"]']").eq(0).val());
	}
  function plsetlevelprice(i,k){
	  var thisprice = $('#levelprice_'+i+'_'+k).val();
	  console.log(thisprice)
	  var level0price = $('#levelprice_0_0').val();
	  console.log(level0price)
	  var pricepercent = thisprice / level0price;
	  console.log(pricepercent)
	  $("input[name$='][market_price]']").each(function(i2,v2){
		  var this_price = ($('#levelprice_'+i2+'_0').val() * pricepercent).toFixed(2);
		  console.log(this_price)
		  this_price = parseInt(this_price*100)/100;
		  $('#levelprice_'+i2+'_'+k).val(this_price);
	  })
  }
	//批量编辑分类
	function editCategory(){
			var ids = [];
			var checkStatus = table.checkStatus('tabledata')
			var checkData = checkStatus.data; //得到选中的数据
			if(checkData.length === 0){
				 return layer.msg('请选择需要编辑的商品');
			}
			for(var i=0;i<checkData.length;i++){
				ids.push(checkData[i]['id']);
			}
			var checkLayer = layer.open({type:1,area:['600px','400px'],title:'批量编辑分类',content:$("#selectCategory"),shadeClose:true,btn: ['确定', '取消'],
				yes:function(){
					var cid = 	$("input[name='info[cid]']").val(); 
					if(!cid){
						dialog('请选择分类');
						return false;
					}
					var index = layer.load();
					$.post("<?php echo url('editManyCategory'); ?>",{ids:ids,cid:cid},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(checkLayer);
						tableIns.reload();
					})
				}
			});
			layui.form.render();
	
	}
	//批量编辑商家分类
	function editCategory2(){
			var ids = [];
			var checkStatus = table.checkStatus('tabledata')
			var checkData = checkStatus.data; //得到选中的数据
			if(checkData.length === 0){
				 return layer.msg('请选择需要编辑的商品');
			}
			for(var i=0;i<checkData.length;i++){
				ids.push(checkData[i]['id']);
			}
			var checkLayer = layer.open({type:1,area:['600px','400px'],title:'批量编辑分类',content:$("#selectCategory2"),shadeClose:true,btn: ['确定', '取消'],
				yes:function(){
					var cid = 	$("input[name='info[cid2]']").val(); 
					if(!cid){
						dialog('请选择分类');
						return false;
					}
					var index = layer.load();
					$.post("<?php echo url('editManyCategory2'); ?>",{ids:ids,cid:cid},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(checkLayer);
						tableIns.reload();
					})
				}
			});
			layui.form.render();
	
	}
  //上传商品到视频号
  function towxvideo(id){
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
		  ids.push(id)
	  }
	  var html = '';
	  html += '<div class="layui-form" lay-filter="" style="margin-top:20px">';
	  html += '<div class="layui-form-item">';
	  html += '	<label class="layui-form-label" style="width:120px">类目ID：</label>';
	  html += '	<div class="layui-input-inline" style="width:200px">';
	  html += '		<input type="text" name="info[third_cat_id]" id="third_cat_id" lay-verify="required" lay-verType="tips" class="layui-input" value="">';
	  html += '	</div>';
	  html += '	<button type="button" class="layui-btn layui-btn-primary" onclick="showChooseCategory()">选择</button>';
	  html += '</div>';
	  html += '<div class="layui-form-item">';
	  html += '	<label class="layui-form-label" style="width:120px">品牌：</label>';
	  html += '	<div class="layui-input-inline" style="width:200px">';
	  html += '		<select name="info[brand_id]" id="brand_id">';
	  <?php foreach($brand_list as $v): ?>
	  html += '			<option value="<?php echo $v['brand_id']; ?>"><?php echo $v['brand_wording']; ?></option>';
	  <?php endforeach; ?>
		  html += '		</select>';
		  html += '	</div>';
		  html += '</div>';

		  html += '<div class="layui-form-item" id="trademark_registration_certificate_div">';
		  html += '	<label class="layui-form-label" style="width:120px">商品资质图片：</label>';
		  html += '	<input type="hidden" name="info[qualification_pics]" value="" id="qualification_pics">';
		  html += '	<button style="float:left;" type="button" class="layui-btn layui-btn-primary" onclick="uploader(this,true)" upload-input="qualification_pics" upload-preview="qualification_picsList" >上传图片</button>';
		  html += '	<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">如所选类目需要商品资质，请在此上传</div>';
		  html += '	<div id="qualification_picsList" style="float:left;padding-top:10px;padding-left:150px;clear:both">';
		  html += '	</div>';
		  html += '</div>';
		  html += '</div>';
		  var checkLayer = layer.open({type:1,area:['600px','400px'],title:'上传商品到视频号',content:html,shadeClose:true,btn: ['确定', '取消'],
			  yes:function(){
				  var third_cat_id = $('#third_cat_id').val();
				  var qualification_pics = $('#qualification_pics').val();
				  var brand_id = $('#brand_id').val();
				  if(!third_cat_id || !brand_id){
					  dialog('请选择类目和品牌');
					  return false;
				  }
				  var index = layer.load();
				  $.post("<?php echo url('towxvideo'); ?>",{ids:ids,third_cat_id:third_cat_id,qualification_pics:qualification_pics,brand_id:brand_id},function(res){
					  layer.close(index);
					  dialog(res.msg,res.status);
					  layer.close(checkLayer);
					  tableIns.reload();
				  })
			  }
		  });
		  layui.form.render();
	  }
	function showChooseCategory(){
		layer.open({type:2,title:'选择类目',content:"<?php echo url('Wxvideo/allcategory'); ?>&ischoose=1",area:['900px','600px'],shadeClose:true});
	}
	function chooseCategory(data){
		console.log(data)
		$("input[name='info[third_cat_id]']").val(data.third_cat_id);
	}
	//复制商品
	function copyProduct(id){
		var ids = [];
		if(id==0){
			var checkStatus = table.checkStatus('tabledata')
			var checkData = checkStatus.data; //得到选中的数据
			if(checkData.length === 0){
				 return layer.msg('请选择要复制的商品');
			}
			var ids = [];
			for(var i=0;i<checkData.length;i++){
				ids.push(checkData[i]['id']);
			}
		}else{
			ids.push(id)
		}
	
		//选择账号
		var html = '<div style="margin:20px auto;">';
		html+='<div class="layui-form" lay-filter="">';
			<?php if(getcustom('admin_user_group')): ?>
			html+='<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
			html+='	<label class="layui-form-label" style="width:150px">选择分组</label>';
			html+='	<div class="layui-input-inline" style="width:180px">';
			html+='		<select id="togroupid" name="togroupid" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;">';
			html+='			<option value="">请选择分组</option>';
			<?php foreach($groupArr as $k=>$v): ?>
			html+='			<option value="<?php echo $k; ?>"><?php echo $v; ?></option>';
			<?php endforeach; ?>
			html+='		</select>';
			html+='	</div>';
			html+='</div>';
			<?php endif; ?>
		html+='<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
		html+='	<label class="layui-form-label" style="width:150px">选择账号</label>';
		html+='	<div class="layui-input-inline" style="width:180px">';
		html+='		<select id="toaid" name="toaid" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;" lay-filter="changeuserid">';
		html+='			<option value="">请选择账号</option>';
		<?php foreach($userlist as $k=>$v): ?>
		html+='			<option value="<?php echo $v["aid"]; ?>"><?php echo $v["un"]; ?></option>';
		<?php endforeach; ?>
		html+='		</select>';
		html+='	</div>';
		html+='</div>';
		
		html+='<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
		html+='	<label class="layui-form-label" style="width:150px">选择分类</label>';
		html+='	<div class="layui-input-inline" style="width:180px">';
		html+='		<select id="tocid" name="tocid" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;">';
		html+='		</select>';
		html+='	</div>';
		html+='</div>';

		html+='</div>';
		html+='</div>'
		var copyLayer = layer.open({type:1,title:'复制商品到其他账号',area:['500px','500px'],content:html,shadeClose:true,btn: ['确定', '取消'],
			yes:function(){
				var index = layer.load();
				layer.confirm('确定要复制到该账号吗?',{icon: 7, title:'操作确认'}, function(index){
					//do something
					layer.close(index);
					var index = layer.load();
					var togroupid = $('#togroupid').val();
					var toaid = $('#toaid').val();
					var tocid = $('#tocid').val();
					$.post("<?php echo url('userProcopy'); ?>",{ids:ids,toaid:toaid,tocid:tocid,togroupid:togroupid},function(data){
						layer.close(index);
						if(data.status == 1){
							dialog(data.msg,data.status);
							layer.close(copyLayer);
						}else{
							dialog(data.msg,data.status);
						}
					})
				},function(){
					layer.close(index);
				});
			}
		})
		layui.form.render();
		layui.form.on('select(changeuserid)', function(obj){
			//var field = obj.field;
			var toaid = obj.value;
			var index= layer.load();
			$.post("<?php echo url('getcategory'); ?>",{toaid:toaid},function(res){
				layer.close(index);
				var clist = res.data;
				var html = '';
				for(var i in clist){
					html += '<option value="'+clist[i].id+'">'+clist[i].name+'</option>';
					if(clist[i].child){
						for(var j in clist[i].child){
							html += '<option value="'+clist[i].child[j].id+'">&nbsp;&nbsp;&nbsp;'+clist[i].child[j].name+'</option>';
							for(var k in clist[i].child[j].child){
								html += '<option value="'+clist[i].child[j].child[k].id+'">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+clist[i].child[j].child[k].name+'</option>';
							}
						}
					}
				}
				$('#tocid').html(html);
				layui.form.render();
			})
		})

	}

				var pagenum = 1;
				var pagelimit = 1;
	//导入
	function daoru(){
		var html = '<div style="margin:20px auto;">';
		html+='<div class="layui-form" lay-filter="">';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label" style="width:150px">上传EXCEL文件：</label>';
		html+='		<div class="layui-input-inline" style="width:300px">';
		html+='			<input type="text" name="upload_file" id="upload_file" class="layui-input">';
		html+='		</div>';
		html+='		<button style="float:left;" type="button" class="layui-btn layui-btn-primary uploadexcel" upload-input="upload_file">上传</button>';
		html+='	</div>';
		html+='	<div class="layui-form-item" style="padding:0 20px">';
		html+='		<div class="layui-form-mid" style="color:red;">请按导入格式进行导入</div>';
		html+='	</div>';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label" style="width:150px"></label>';
		var demo = '<div class="layui-form-mid"> <a href="/static/demo_product.xlsx">点击下载查看导入格式</a></div>';
		<?php if(getcustom('product_field_buy')): ?>
			demo ='		<div class="layui-form-mid"> <a href="/static/demo_product2.xlsx">点击下载查看导入格式</a></div>';
		<?php endif; if(getcustom('product_update_excel')): ?>
			demo ='		<div class="layui-form-mid"> <a href="/static/demo_product3.xlsx">点击下载查看导入格式</a></div>';
		<?php endif; ?>
		html += demo;
		html+='	</div>';
		html+='	<div class="layui-form-item" style="margin-top:30px">';
		html+='		<label class="layui-form-label" style="width:150px"></label>';
		html+='		<div class="layui-input-inline">';
		html+='			<button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit_excel">确定导入</button>';
		html+='		</div>';
		html+='	</div>';
		html+='</div>';
		html+='</div>'
		layer.open({type:1,area: ['600px', '360px'],title:'导入商品',content:html,shadeClose:true})
		layui.form.render();
		//文件上传
		layui.upload.render({
			elem: '.uploadexcel',
			accept:'file',
			url: "<?php echo url('upload/index'); ?>",
			done: function(res){
				if(res.status==0){
					dialog(res.msg,0);
				}else{
					var item = this.item;
					var inputid = $(this.item).attr('upload-input');
					if(inputid){
						$('#'+inputid).val(res.url);
						$('#'+inputid).change();
					}
				}
			}
		});
		layui.form.on('submit(submit_excel)', function(obj){
			var field = obj.field;
			console.log(field);
			var index= layer.load();
			var url = "<?php echo url('importexcel'); ?>";
			pagenum = 1;

			<?php if(getcustom('product_update_excel')): ?>
			url = "<?php echo url('importexcelnew'); ?>";
			<?php endif; ?>
			_tplsend(field,url);

		})
	}
			function _tplsend(field,url){

				console.log(pagenum);
				var index = layer.load();

				$.ajax({
					type:'POST',
					url:url+"/pagenum/"+pagenum+'/pagelimit/'+pagelimit,
					dataType:'json',
					data:{file:field.upload_file},
					success:function(data){
						layer.close(index);
						if(data.status==1){
							pagenum++;
							if(data.status==1){
								layer.msg(data.msg,{offset:'100px'});
							}
							if(data.status==1){
								if(data.remain <= 0){
									dialog(data.msg,data.status);
									setTimeout(function(){
										layer.closeAll();
										tableIns.reload()
									},1000);
								}else
									_tplsend(field,url);
							}else{
								dialog(data.msg,data.status);
								setTimeout(function(){
									layer.closeAll();
									tableIns.reload()
								},1000);
							}
						}else{
							dialog(data.msg,data.status);
							setTimeout(function(){
								layer.closeAll();
								tableIns.reload()
							},1000);
						}
					},
					error:function(){
						layer.close(index);
						dialog('未知错误',0);
					}
				})
			}
	function wxvideoupdatest(){
		layer.confirm('当出现状态不一致时可使用此功能，是否更新全部商品的视频号状态?',function(){
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('wxvideoupdatest'); ?>",{},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}
	//撤回审核
	function wxvideo_del_audit(proid){
		layer.confirm('是否要撤回审核?',function(){
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('wxvideo_del_audit'); ?>",{proid:proid},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}
	//视频号上架
	function wxvideo_listing(proid){
		layer.confirm('是否要上架?',function(){
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('wxvideo_listing'); ?>",{proid:proid},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}
	//视频号下架
	function wxvideo_delisting(proid){
		layer.confirm('是否要下架?',function(){
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('wxvideo_delisting'); ?>",{proid:proid},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}
	function viewrejectReason(proid){
		var index = layer.load();
		$.post("<?php echo url('getproduct'); ?>",{proid:proid},function(data){
			layer.close(index);
			layer.open({type:1,area:['300px','300px'],content:'<div style="margin:auto auto;word-break:break-word;padding:20px">'+data.product.wxvideo_reject_reason+'</div>',title:false,shadeClose:true})
		});
	}
	//规格拆分
	var gglist;
	function guige_split(proid){
		var index = layer.load();
		$.post("<?php echo url('getsplitdata'); ?>",{proid:proid},function(res){
			layer.close(index);
			var splitlist = res.splitlist;
			gglist = res.gglist;
			var html = '<div style="margin:20px" class="layui-form">';
			html+='<div id="ggsplit_div">';
			for(var i in splitlist){
				html+='<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
				html+='	<label class="layui-form-label">拆分公式</label>';
				html+='	<div class="layui-input-inline" style="width:180px">';
				html+='		<select name="ggid1[]">';
				html+='			<option value="">请选择规格</option>';
				for(var j in gglist){
					html+='		<option value="'+gglist[j]['id']+'" '+(splitlist[i].ggid1 == gglist[j]['id'] ? 'selected' : '')+'>'+gglist[j]['name']+'</option>';
				}
				html+='		</select>';
				html+='	</div>';
				html+='	<div class="layui-form-mid"> = </div>';
				html+='	<div class="layui-input-inline" style="width:180px">';
				html+='		<select name="ggid2[]">';
				html+='			<option value="">请选择规格</option>';
				for(var j in gglist){
					html+='		<option value="'+gglist[j]['id']+'" '+(splitlist[i].ggid2 == gglist[j]['id'] ? 'selected' : '')+'>'+gglist[j]['name']+'</option>';
				}
				html+='		</select>';
				html+='	</div>';
				html+='	<div class="layui-form-mid"> × </div>';
				html+='	<div class="layui-input-inline" style="width:70px">';
				html+='		<input type="number" name="multiple[]" class="layui-input" value="'+splitlist[i].multiple+'">';
				html+='	</div>';
				if(i == 0){
					html+='<button type="button" class="layui-btn layui-btn-primary" onclick="addggsplit()">添加</button>';
				}else{
					html+='<button type="button" class="layui-btn layui-btn-primary" onclick="delggsplit(this)">删除</button>';
				}
				html+='</div>';
			}
			html+='</div>';
			html+='<input type="hidden" name="proid" value="'+proid+'"/>';

			html+='<div class="layui-form-item" style="margin-top:50px">';
			html+='	<label class="layui-form-label"></label>';
			html+='	<div class="layui-input-inline">';
			html+='		<button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit_set_ggsplit">确 定</button>';
			html+='	</div>';
			html+='</div>';
			html+='</div>';
			
			layer.open({type:1,area:['800px','600px'],content:html,title:'规格拆分',shadeClose:true});
			layui.form.render();
			layui.form.on('submit(submit_set_ggsplit)', function(obj){
				var field = obj.field
				var index = layer.load();
				$.post("<?php echo url('ggsplit'); ?>",field,function(data){
					layer.close(index);
					dialog(data.msg,data.status,data.url);
				});
			});
		});
	}
	function addggsplit(){
		var html='<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
		html+='	<label class="layui-form-label">拆分公式</label>';
		html+='	<div class="layui-input-inline" style="width:180px">';
		html+='		<select name="ggid1[]">';
		html+='			<option value="">请选择规格</option>';
		for(var j in gglist){
			html+='		<option value="'+gglist[j]['id']+'">'+gglist[j]['name']+'</option>';
		}
		html+='		</select>';
		html+='	</div>';
		html+='	<div class="layui-form-mid"> = </div>';
		html+='	<div class="layui-input-inline" style="width:180px">';
		html+='		<select name="ggid2[]">';
		html+='			<option value="">请选择规格</option>';
		for(var j in gglist){
			html+='		<option value="'+gglist[j]['id']+'">'+gglist[j]['name']+'</option>';
		}
		html+='		</select>';
		html+='	</div>';
		html+='	<div class="layui-form-mid"> × </div>';
		html+='	<div class="layui-input-inline" style="width:70px">';
		html+='		<input type="number" name="multiple[]" id="third_cat_id" class="layui-input" value="">';
		html+='	</div>';
		html+='	<button type="button" class="layui-btn layui-btn-primary" onclick="delggsplit(this)">删除</button>';
		html+='</div>';
		$('#ggsplit_div').append(html);
		layui.form.render();
	}
	function delggsplit(obj){
		$(obj).parent().remove();
	}
	function dolock(id,st){
		layer.confirm('确定要'+(st==1?'锁定':'解除锁定')+'吗?',{icon: 7, title:'操作确认'}, function(index){
			//do something
			layer.close(index);
			var index = layer.load();
			$.post("<?php echo url('dolock'); ?>",{id:id,st:st},function(data){
				layer.close(index);
				dialog(data.msg,data.status);
				tableIns.reload()
			})
		});
	}
	function checklock(islock,func){
		if(islock == 0){
			func();return;
		}
		var html = '';
		html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
		html+='		<label class="layui-form-label" style="width:140px">输入解锁密码：</label>';
		html+='		<div class="layui-input-inline" style="width:200px">';
		html+='			<input type="password" id="lockpwd" class="layui-input"/>';
		html+='		</div>';
		html+='	</div>';
		var openmaxneedpwdLayer = layer.open({type:1,area:['600px','200px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
			yes:function(){
				var index = layer.load();
				$.post("<?php echo url('inputlockpwd'); ?>",{lockpwd:$('#lockpwd').val()},function(data){
					layer.close(index);
					if(data.status == 1){
						layer.close(openmaxneedpwdLayer);
						func();
					}else{
						dialog(data.msg,data.status);
					}
				});
			}
		});
	}


	//日期范围选择
	layui.laydate.render({
		elem: '#ctime',
		trigger: 'click',
		type:'datetime',
		range: '~'
	});
	function tongbu(){
		tongbulayer = layer.open({type:1,area: ['600px', '400px'],title:'同步聚水潭商品',content:$('#tongbuModel'),shadeClose:true})

		/*layer.confirm('如果商品较多,同步时间会比较长,请耐心等待,确定要同步吗?',{icon: 7, title:'从聚水潭同步商品'}, function(index){
			layer.close(index)
			infonum = 0;
			next_openid = '';
			totalpage = 0;
			getopenidnum = 0;
			openids = [];
			countopenid = 0;
			loadingdialog = layer.open({type:1,content:'<div style="padding:10px 5px"><div class="flex-y-center"><img src="/static/admin/img/loading.gif"/><span style="font-size:20px;padding-left:5px">正在同步请勿操作...</span></div></div>',area:'300px',title:false,closeBtn:false});
			$.post("<?php echo url('jsttongbu'); ?>",{ op:'getpage'},function(data){
				if(data.status==0){
					layer.close(loadingdialog);
					dialog(data.msg,data.status);
				}else{
					layer.msg('同步成功',{offset:'100px'});
					layer.close(loadingdialog);
					tableIns.reload()
				}
			},'json');
		});
		*/
	}


	var totalpage = 0;
	var totalnums = 0;
	var pagenum=1;
	var loadingdialog;
	//聚水潭同步
	 layui.form.on('submit(subtongbu)', function(obj){
		 console.log(obj);
		if(!obj.field.ctime){
			layer.msg('请选择时间');return;
		}
		layer.confirm('如果商品较多,同步时间会比较长,请耐心等待,确定要同步吗?',{icon: 7, title:'从聚水潭同步商品'}, function(index){
			layer.close(index)
			totalpage = 0;
			obj.field.pagenum=pagenum;
			loadingdialog = layer.open({type:1,content:'<div style="padding:10px 5px"><div class="flex-y-center"><img src="/static/admin/img/loading.gif"/><span style="font-size:20px;padding-left:5px">正在同步请勿操作...</span></div></div>',area:'300px',title:false,closeBtn:false});
			$.post("<?php echo url('jsttongbu'); ?>", obj.field,function(data){
				layer.close(loadingdialog);
				if(data.status==0){
					layer.close(loadingdialog);
					dialog(data.msg,data.status);
				}else{
					layer.msg('共计更新商品'+data.data_count+'个',{offset:'100px'});
					totalnums= data.data_count;
					if(data.has_next){
						pagenum++;
						var ctime =  obj.field.ctime
						setTimeout(function(){
							getprolist(pagenum,ctime);
						},1000)
					}else{
						layer.close(loadingdialog);
						tableIns.reload()
					}
				}
			},'json');
		});
	 });
	function getprolist(pagenum,ctime){

		layer.msg('正在获取第'+ (pagenum <  totalnums ? pagenum : totalnums)+'条商品',{offset:'100px'});
		$.post("<?php echo url('jsttongbu'); ?>",{ 'pagenum':pagenum,'ctime':ctime},function(data){
			if(data.status==1){
				if(data.has_next){
					pagenum++;
					setTimeout(function(){
						getprolist(pagenum,ctime);	
					},1000)
				}else{
					dialog(data.msg+'共计更新商品'+data.data_count+'个',data.status);
					setTimeout(function(){
						layer.close(loadingdialog);
						tableIns.reload();
					},1000)
				}
			}else if(data.status==0){
				layer.close(loadingdialog);
				dialog(data.msg,data.status);
			}else if(data.code==0){
				layer.close(loadingdialog);
				dialog(data.msg);
			}
		},'json');
	}
	



	//导出
	function dataexcel(){
		var ids = '';

		var checkStatus = table.checkStatus('tabledata')
		var checkData = checkStatus.data; //得到选中的数据

		for(var i=0;i<checkData.length;i++){
			ids = ids+','+checkData[i]['id'];
		}

		datawhere.ids = ids;
		console.log(datawhere);
		//return false;
		window.location.href='<?php echo url('excel'); ?>'+urlEncode(datawhere)
	}
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
					$.post("<?php echo url('del'); ?>",{ids:ids},function(data){
						layer.close(index);
						dialog(data.msg,data.status);
						tableIns.reload()
					})
				});
			}

			//复制商品
			function to_business(id){
				var ids = [];
				if(id==0){
					var checkStatus = table.checkStatus('tabledata')
					var checkData = checkStatus.data; //得到选中的数据
					if(checkData.length === 0){
						//return layer.msg('请选择要复制的商品');
					}
					var ids = [];
					for(var i=0;i<checkData.length;i++){
						ids.push(checkData[i]['id']);
					}
				}else{
					ids.push(id)
				}
				var ids_str = ids;
				if(ids.length==0){
					ids_str = '全部商品';
				}

				//选择账号
				var html = '<div style="margin:20px auto;">';
				html+='<div class="layui-form" lay-filter="">';
				html+='<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
				html+='	<label class="layui-form-label" style="width:150px">已选择商品：</label>';
				html+='	<div class="layui-input-inline" style="width:180px;padding-top: 10px;word-wrap: break-word;">';
				html+= ids_str;
				html+='	</div>';
				html+='</div>';
				html+='<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
				html+='	<label class="layui-form-label" style="width:150px">选择商家</label>';
				html+='	<div class="layui-input-inline" style="width:180px">';
				html+='		<select id="tobid" name="tobid" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;">';
				html+='			<option value="0">全部商家</option>';
				<?php foreach($business_lists as $k=>$v): ?>
				html+='			<option value="<?php echo $v["id"]; ?>"><?php echo $v["name"]; ?></option>';
				<?php endforeach; ?>
					html+='		</select>';
					html+='	</div>';
					html+='</div>';


					html+='</div>';
					html+='</div>'
					var copyLayer = layer.open({type:1,title:'同步商品到其他商家',area:['500px','500px'],content:html,shadeClose:true,btn: ['确定', '取消'],
						yes:function(){
							var index = layer.load();
							layer.confirm('确定要同步到该商家吗?',{icon: 7, title:'操作确认'}, function(index){
								//do something
								layer.close(index);
								var index = layer.load();
								var togroupid = $('#togroupid').val();
								var tobid = $('#tobid').val();
								$.post("<?php echo url('businessProcopy'); ?>",{ids:ids,tobid:tobid},function(data){
									layer.close(index);
									if(data.status == 1){
										dialog(data.msg,data.status);
										layer.close(copyLayer);
									}else{
										dialog(data.msg,data.status);
									}
								})
							},function(){
								layer.close(index);
							});
						}
					})
					layui.form.render();
			}


				//改价
				function changestock(proid){
					var index = layer.load();
					$.post("<?php echo url('getproduct'); ?>",{proid:proid},function(data){
						layer.close(index);
						var product = data.product;
						var specs = data.guigedata;
						var gglist = data.gglist;
						var len = specs.length;
						var newlen = 1;
						var h = new Array(len);
						var rowspans = new Array(len);
						var html = '<div style="margin:10px" class="layui-form" lay-filter="changestockForm"><table id="ggvaldiv" class="layui-table"><thead><tr>';
						for(var i=0;i<len;i++){
							html+="<th>" + specs[i].title + "</th>";
							var itemlen = specs[i].items.length;
							if(itemlen<=0) { itemlen = 1 };
							newlen*=itemlen;
							h[i] = new Array(newlen);
							for(var j=0;j<newlen;j++){
								h[i][j] = new Array();
							}
							var l = specs[i].items.length;
							rowspans[i] = 1;
							for(j=i+1;j<len;j++){
								rowspans[i]*= specs[j].items.length;
							}
						}
						html += '<th>市场价（元）</th>';
						html += '<th>成本价（元）</th>';
						if(product.lvprice==1){
							<?php foreach($levellist as $level): ?>
							html += '<th><?php echo $level['name']; ?>（元）</th>';
							<?php endforeach; ?>
							}else{
								html += '<th>销售价（元）</th>';
							}
							html += '<th>库存</th>';
							html += '</tr></thead>';

							for(var m=0;m<len;m++){
								var k = 0,kid = 0,n=0;
								for(var j=0;j<newlen;j++){
									var rowspan = rowspans[m];
									if( j % rowspan==0){
										h[m][j]={ k:specs[m].items[kid].k,title: specs[m].items[kid].title, html: "<td rowspan='" +rowspan + "'>"+ specs[m].items[kid].title+"</td>\r\n",id: specs[m].items[kid].id};
									}else{
										h[m][j]={ k:specs[m].items[kid].k,title:specs[m].items[kid].title, html: "",id: specs[m].items[kid].id};
									}
									n++;
									if(n==rowspan){
										kid++; if(kid>specs[m].items.length-1) { kid=0; }
										n=0;
									}
								}
							}
							var hh = "";
							for(var i=0;i<newlen;i++){
								hh+="<tr>";
								var ks = [];
								var titles = [];
								for(var j=0;j<len;j++){
									hh+=h[j][i].html;
									ks.push( h[j][i].k);
									titles.push( h[j][i].title);
								}
								ks =ks.join(',');
								titles =titles.join(',');
								if(typeof(gglist[ks])!='undefined'){
									var val = gglist[ks];
								}else{
									var val = { procode:'',market_price:'',cost_price:'',sell_price:'',weight:'',stock:'1000',pic:''};
								}

								hh += '<td>';
								hh += '<input name="option['+ks+'][ggid]" type="hidden" value="'+(val.id)+'"/>';
								hh += (val.market_price==null?'':val.market_price);
								hh += '</td>';
								hh += '<td>';
								hh += (val.cost_price==null?'':val.cost_price);
								hh += '</td>';
								if(product.lvprice==1){
									val.lvprice_data = JSON.parse(val['lvprice_data']);
									<?php foreach($levellist as $lk=>$level): ?>
									hh += '<td>';
									hh += (val.lvprice_data==null || val.lvprice_data[<?php echo $level['id']; ?>]==null?'':val.lvprice_data[<?php echo $level['id']; ?>]);
										hh += '</td>';
										<?php endforeach; ?>
										}else{
											hh += '<td>';
											hh += (val.sell_price==null?'':val.sell_price);

											hh += '</td>';
										}
										hh += '<td>';
										hh += ' <input name="option['+ks+'][stock]" type="text" style="width:60px" value="'+(val.stock==null?'':val.stock)+'" class="layui-input"/>';
										if(i==0){
											hh += ' <i class="fa fa-hand-o-down" style="cursor:pointer" title="批量设置" onclick="plset(\'stock\')"></i>';
										}
										hh += '</td>';
										hh += "</tr>";
									}
									html+=hh;
									html+='</table></div>';
									layer.open({type:1,title:product.name,content:html,area:['900px','600px'],shadeClose:true,btn:['确定','取消'],
										yes:function(index){
											layer.close(index);
											var formdata = layui.form.val("changestockForm");
											formdata['proid'] = proid;
											console.log(formdata)
											var index = layer.load();
											$.post("<?php echo url('changestock'); ?>",formdata,function(data){
												layer.close(index);
												dialog(data.msg,data.status);
												tableIns.reload()
											})
										}
									});
								});
				}
			<?php if(getcustom('supply_zhenxin')): ?>
	   	function showzxproduct(sproid){
      	layer.open({type:2,title:'查看商品',content:"<?php echo url('SupplyZhenxinProduct/choosepro'); ?>/nosel/1/sproid/"+sproid,area:['90%','80%'],shadeClose:true,});
    	}
    	<?php endif; if(getcustom('erp_hupun') && $auth_data=='all' || in_array('HupunSet/*',$auth_data)): ?>
        function synchupun(obj){
            var checkStatus = table.checkStatus('tabledata');
            var ids = checkStatus.data.map(item => item.id);
            if(ids.length === 0){
                layer.msg('请先选择要同步的商品');
                return;
            }

            layer.confirm(`本次共选择 ${ids.length} 个商品，确定要同步到万里牛吗？`, {icon: 7, title:'同步商品到万里牛'}, function(index){
                layer.close(index);
                totalpage = 0;
                pagenum = 1;

                loadingdialog = layer.open({
                    type:1,
                    content:'<div style="padding:10px 5px"><div class="flex-y-center">'
                        + '<img src="/static/admin/img/loading.gif"/>'
                        + '<span style="font-size:20px;padding-left:5px">正在同步中，请勿操作...</span></div></div>',
                    area:'300px',
                    title:false,
                    closeBtn:false
                });

                // 分批提交数据
                syncBatch(ids, pagenum);
            });
        }

        //分批同步
        function syncBatch(ids, currentPage, batchSize = 50) {
            const start = (currentPage - 1) * batchSize;
            const end = start + batchSize;
            const batchIds = ids.slice(start, end);

            $.post("<?php echo url('batchSync'); ?>", { ids: batchIds }, function(data) {
                if(data.status === 1) {
                    const processed = currentPage * batchSize;
                    const progress = Math.min(processed, ids.length);

                    layer.msg(`已同步 ${progress}/${ids.length} 个商品`, {offset:'100px'});

                    if(progress < ids.length) {
                        syncBatch(ids, currentPage + 1, batchSize);
                    } else {
                        layer.close(loadingdialog);
                        layer.msg(`同步完成，成功 ${data.data.success} 个，失败 ${data.data.fail} 个`, {time:5000});
                        tableIns.reload();
                    }
                } else {
                    layer.close(loadingdialog);
                    layer.msg('同步失败: ' + data.msg, {icon:2});
                }
            }, 'json');
        }
		function refreshWlnStock(){
            let loadings = layer.open({
                type:1,
                content:'<div style="padding:10px 5px"><div class="flex-y-center">'
                    + '<img src="/static/admin/img/loading.gif"/>'
                    + '<span style="font-size:20px;padding-left:5px">正在同步库存中，请勿操作...</span></div></div>',
                area:'300px',
                title:false,
                closeBtn:false
            });
			$.post("<?php echo url('refreshWlnStock'); ?>",{},function(data){
                layer.close(loadings);
				dialog(data.msg,data.status);
				if(data.status == 1){
					tableIns.reload();
				}
			})
		}
        <?php endif; if(getcustom('supply_yongsheng')): if($auth_data=='all' || in_array('SupplyYongshengProduct/*',$auth_data)): ?>
					function addYongshengProduct(sproid){
		      	layer.open({type:2,title:'查看商品',content:"<?php echo url('SupplyYongshengProduct/chooseproduct'); ?>/type/shop",area:['95%','95%'],shadeClose:true,});
		    	}
		    	function chooseSupplyYongshengProduct(goodsId){
		    		openmax("<?php echo url('ShopProduct/edit'); ?>/sproid/"+goodsId+"/source/supply_yongsheng/isopen/1");
		    	}
				<?php endif; ?>
		   	function showysproduct(sproid){
	      	layer.open({type:2,title:'查看商品',content:"<?php echo url('SupplyYongshengProduct/index'); ?>/isopen/1/goodsId/"+sproid,area:['90%','80%'],shadeClose:true,});
	    	}
	    	function lookSourceChange(source_status_msg){
  		  	layui.laytpl(sourceStatusMsgTpl.innerHTML).render(source_status_msg, function(html){
						var sourceStatusMsgLayer = layer.open({type:1,title:false,content:html,area:'500px',shadeClose:true})
					})
  		  }
			<?php endif; ?>
	</script>
	
</body>
</html>