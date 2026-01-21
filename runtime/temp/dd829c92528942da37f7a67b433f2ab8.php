<?php /*a:4:{s:49:"/www/wwwroot/eivie/app/view/shop_order/index.html";i:1766974801;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;s:49:"/www/wwwroot/eivie/app/view/public/copyright.html";i:1648714895;}*/ ?>
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

	.product{width:100%; padding:5px;background: #FFF;margin: 5px 0px;}
	.product .content{display:flex;margin: 10px 0px;}
	.product .box{position:relative;width: 100%; padding:5px 0px;border-bottom: 1px #e5e5e5 dashed;position:relative}
	.product .content{display:flex;position:relative;}
	.product .box:last-child{ border-bottom: 0; }
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

	.glassitem{    background: #f5f5f5;
		padding: 5px;
		font-size: 12px;
		border-radius: 6px;
		margin-top: 5px;}
	.glassitem .bt{border-top:1px solid #e3e3e3}
	.search-form .layui-inline{margin-bottom: 6px}
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
				<?php if($mendian_show_tip == 1): ?>
				<blockquote class="layui-elem-quote">社区团购订单前往【<?php echo t('门店'); ?>发货】进行发货</blockquote>
				<?php endif; ?>
				<ul class="layui-tab-title">
					<li <?php if(!input('?param.status') || input('param.status')===''): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/showtype/<?php echo app('request')->param('showtype'); ?>/source/<?php echo app('request')->param('source'); ?>'">全部订单</li>
					<li <?php if(input('param.status')==='0'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/0/showtype/<?php echo app('request')->param('showtype'); ?>/source/<?php echo app('request')->param('source'); ?>'">未支付</li>
					<li <?php if(input('param.status')=='1'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/1/showtype/<?php echo app('request')->param('showtype'); ?>/source/<?php echo app('request')->param('source'); ?>'">待发货</li>
					<li <?php if(input('param.status')=='2'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/2/showtype/<?php echo app('request')->param('showtype'); ?>/source/<?php echo app('request')->param('source'); ?>'">已发货</li>
					<li <?php if(input('param.status')=='3'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/3/showtype/<?php echo app('request')->param('showtype'); ?>/source/<?php echo app('request')->param('source'); ?>/source/<?php echo app('request')->param('source'); ?>'">已完成</li>
					<li <?php if(input('param.status')=='4'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/4/showtype/<?php echo app('request')->param('showtype'); ?>/source/<?php echo app('request')->param('source'); ?>'">已关闭</li>
					<?php if(getcustom('invite_free')): ?>
						<li <?php if(input('param.is_free')=='1'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/is_free/1/showtype/<?php echo app('request')->param('showtype'); ?>/source/<?php echo app('request')->param('source'); ?>'">免单</li>
					<?php endif; if(getcustom('douyin_groupbuy')): ?>
						<li <?php if(input('param.isdygroupbuy')=='1'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/isdygroupbuy/1/showtype/<?php echo app('request')->param('showtype'); ?>'">抖音团购券</li>
					<?php endif; if($mendian_upgrade): ?>
						<li <?php if(input('param.status')=='8'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/8/showtype/<?php echo app('request')->param('showtype'); ?>/source/<?php echo app('request')->param('source'); ?>'">待提货</li>
					<?php endif; ?>
					<!-- <li <?php if(input('param.status')=='5'): ?>class="layui-this"<?php endif; ?> onclick="location.href='<?php echo url('index'); ?>/status/5'">退款中</li> -->
				</ul>
			</div>
			<?php if(input('param.isopen')==1): ?><i class="layui-icon layui-icon-close" style="font-size:18px;font-weight:bold;cursor:pointer" onclick="closeself()"></i><?php endif; ?>
          </div>
          <div class="layui-card-body" pad15 <?php if($mendian_show_tip == 1): ?>style="margin-top: 55px;"<?php endif; ?>>
						<div class="layui-col-md12" style="padding-bottom:10px">
							<?php if($is_fuwu==0): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list"  data-form-export="<?php echo url('excel'); ?>">导出</button>
							<?php if(getcustom('import_order')): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list"  onclick="import_order()">导入</button>
							<?php endif; if(getcustom('shoporder_plfh2')): ?><button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="plfh2()">发货</button><?php endif; if($is_hid_btn == 0): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="plfh()">批量发货</button>
							<?php endif; if(getcustom('shoporder_batch_del')): ?><button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="datadel(0)">删除</button><?php endif; if(getcustom('plug_xiongmao')): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="plfhyc()">云仓批量发货</button>
							<?php endif; if(getcustom('shd_print') && $adminset['mode'] != 3): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="plshd(0)">批量打印送货单</button>
							<?php endif; if(getcustom('shop_shd_print2') && $adminset['mode'] != 3): ?>
							
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="plshd(0,2)">批量打印送货单(合并)</button>
							<?php endif; if(getcustom('miandan_batch_shipping')): if(bid==0 && $miandanst==1): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="plmiandanfh()">物流助手批量发货</button>
							<?php endif; ?>
							<?php endif; if($adminset['fhjiesuantime_type']==0 && $adminset['fhjiesuantime'] == 10): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="fhjiesuan()">结算分红</button>
							<?php endif; if(getcustom('shop_order_merge_export')): if($is_merge_export): ?>
							<button class="layui-btn layui-btn-primary layuiadmin-btn-list" data-form-export="<?php echo url('excel'); ?>/merge/1">订单合一导出</button>
							<?php endif; ?>
							<?php endif; if(getcustom('erp_wangdiantong') && $erpWdtOpen==1): ?>
							 <button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="refreshWdtExpress(0)">ERP发货同步</button>
							<?php endif; if(getcustom('shop_order_excel_countpay')): ?>
								<button class="layui-btn layui-btn-primary layuiadmin-btn-list" data-form-export="<?php echo url('excel'); ?>/countpay/1">导出明细</button>
							<?php endif; ?>
							<?php endif; ?>
						</div>
						<div class="layui-col-md12 layui-form search-form layui-form-search">
							<?php if(input('?param.showtype') && input('param.showtype')!==''): ?>
							<input type="hidden" name="showtype" value="<?php echo app('request')->param('showtype'); ?>">
							<?php endif; ?>
							<div class="layui-inline layuiadmin-input-useradmin" style="display:none">
								<label class="layui-form-label"><?php echo t('会员'); ?>ID</label>
								<div class="layui-input-inline">
									<input type="text" name="mid" value="<?php echo app('request')->param('mid'); ?>" autocomplete="off" class="layui-input">
								</div>
							</div>
							<?php if(getcustom('school_product') && $needschool==1): ?>
							<div class="layui-inline" style="text-align:left;width: 230px">
								<div class="layui-input-block">
									<select name="school_id">
										<option value="">请选择学校</option>
										<?php if(is_array($schoollist) || $schoollist instanceof \think\Collection || $schoollist instanceof \think\Paginator): $i = 0; $__LIST__ = $schoollist;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$school): $mod = ($i % 2 );++$i;?>
										<option value="<?php echo $school['id']; ?>"><?php echo $school['name']; ?></option>
										<?php endforeach; endif; else: echo "" ;endif; ?>
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
							<?php endif; ?>
							<div class="layui-inline">
								<div class="layui-input-inline" style="width:100px">
									<select name="keyword_type">
										<option value="1">订单号</option>
										<option value="2"><?php echo t('会员'); ?>ID</option>
										<option value="3"><?php echo t('会员'); ?>信息</option>
										<option value="4">收货信息</option>
										<option value="5">快递单号</option>
										<option value="6">商品ID</option>
										<option value="7">商品名称</option>
										<option value="8">商品编码</option>
										<option value="9">核销员</option>
										<option value="10">所属<?php echo t('门店'); ?></option>
										<?php if(getcustom('shop_buy_worknum')): ?><option value="11">工号</option><?php endif; if(getcustom('lipinka_no')): ?><option value="21">兑换卡卡号</option><?php endif; if(getcustom('fuwu_usercenter')): ?><option value="12">所属<?php echo t('服务中心'); ?></option><?php endif; if(getcustom('product_supplier_admin')): ?><option value="13">供应商ID</option><?php endif; ?>
									</select>
								</div>
							</div>
							<div class="layui-inline">
								<div class="layui-input-inline" style="width:150px;margin-left:0px">
									<input type="text" name="keyword" placeholder="" autocomplete="off" class="layui-input">
								</div>
							</div>
							<div class="layui-inline">
								<div class="layui-input-inline" style="width:100px;">
									<select name="time_type">
										<option value="1">下单时间</option>
										<option value="2">付款时间</option>
										<option value="3">发货时间</option>
										<option value="4">完成时间</option>
										<?php if(getcustom('extend_exchange_card_yuyue_send') && ($auth_data=='all' || in_array('ExchangeCardYuyueSend',$auth_data))): ?>
										<option value="5">预约提货时间</option>
										<?php endif; ?>	
									</select>
								</div>
							</div>
							<div class="layui-inline">
								<div class="layui-input-inline" style="width:300px;margin-left:0px">
									<input type="text" name="ctime" id="ctime" autocomplete="off" class="layui-input">
								</div>
							</div>
							<div class="layui-inline">
								<label class="layui-form-label">配送方式</label>
								<div class="layui-input-inline">
									<select name="freight_id">
										<option value="">全部</option>
										<?php foreach($freight as $item): ?>
										<option value="<?php echo $item['id']; ?>" <?php if(input('param.freight_id')==$item['id']): ?>selected<?php endif; ?>><?php echo $item['name']; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<?php if(getcustom('invite_free')): ?>
							<div class="layui-inline">
								<label class="layui-form-label">免单</label>
								<div class="layui-input-inline">
									<select name="is_free">
										<option value="">全部</option>
										<option value="0" <?php if(input('param.is_free')==='0'): ?>selected<?php endif; ?>>否</option>
										<option value="1" <?php if(input('param.is_free')==='1'): ?>selected<?php endif; ?>>是</option>
									</select>
								</div>
							</div>
							<?php endif; if(getcustom('douyin_groupbuy')): ?>
							<div class="layui-inline">
								<label class="layui-form-label">抖音团购券</label>
								<div class="layui-input-inline">
									<select name="isdygroupbuy">
										<option value="">全部</option>
										<option value="0" <?php if(input('param.isdygroupbuy')==='0'): ?>selected<?php endif; ?>>否</option>
										<option value="1" <?php if(input('param.isdygroupbuy')==='1'): ?>selected<?php endif; ?>>是</option>
									</select>
								</div>
							</div>
							<?php endif; if(getcustom('product_extend')): ?>
							<div class="layui-inline">
								<label class="layui-form-label">商品类型</label>
								<div class="layui-input-inline">
									<select name="product_type">
										<option value="">全部</option>
										<option value="0" <?php if(input('param.product_type')==='0'): ?>selected<?php endif; ?>>普通商品</option>
										<?php if(getcustom('product_wholesale')): ?>
										<option value="4" <?php if(input('param.product_type')==='1'): ?>selected<?php endif; ?>>批发商品</option>
										<?php endif; if(getcustom('extend_exchange_card_yuyue_send') && ($auth_data=='all' || in_array('ExchangeCardYuyueSend',$auth_data))): ?>
										<option value="11" <?php if(input('param.product_type')==='11'): ?>selected<?php endif; ?>>兑换预售</option>
										<?php endif; ?>
									</select>
								</div>
							</div>
							<?php endif; if($mendian_upgrade): ?>
							<div class="layui-inline">
								<label class="layui-form-label"><?php echo t('门店'); ?>分组</label>
								<div class="layui-input-inline">
									<select name="mdgid">
										<option value="">全部</option>
										<?php foreach($mendian_groups as $gk=>$gv): ?>
										<option value="<?php echo $gk; ?>"><?php echo $gv; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<?php endif; if(getcustom('pay_transfer') && $bid==0): ?>
							<div class="layui-inline">
								<label class="layui-form-label">转账审核</label>
								<div class="layui-input-inline">
									<select name="transfer_check">
										<option value="">全部</option>
										<option value="-1" <?php if(input('param.transfer_check')==='0'): ?>selected<?php endif; ?>>已驳回</option>
										<option value="0" <?php if(input('param.transfer_check')==='1'): ?>selected<?php endif; ?>>待审核</option>
										<option value="1" <?php if(input('param.transfer_check')==='2'): ?>selected<?php endif; ?>>已通过</option>
									</select>
								</div>
							</div>
							<?php endif; if(getcustom('supply_zhenxin') || getcustom('supply_yongsheng')): if(!input('param.source')): ?>
								<div class="layui-inline">
									<label class="layui-form-label">商品来源</label>
									<div class="layui-input-inline">
										<select name="source">
											<option value="">全部</option>
											<option value="self">本系统</option>
											<?php if(getcustom('supply_zhenxin')): ?><option value="supply_zhenxin">甄新汇选</option><?php endif; if(getcustom('supply_yongsheng')): ?><option value="supply_yongsheng"><?php echo $yongshengname; ?></option><?php endif; ?>
										</select>
									</div>
								</div>
								<?php endif; ?>
								<div class="layui-inline">
									<label class="layui-form-label">来源订单号</label>
									<div class="layui-input-inline">
										<input type="text" name="sordernum" autocomplete="off" class="layui-input">
									</div>
								</div>
							<?php endif; ?>
							<div class="layui-inline">
								<label class="layui-form-label">状态</label>
								<div class="layui-input-inline">
									<select name="status">
										<option value="">全部</option>
										<option value="0" <?php if(input('param.status')==='0'): ?>selected<?php endif; ?>>未支付</option>
										<option value="1" <?php if(input('param.status')==='1'): ?>selected<?php endif; ?>>待发货</option>
										<option value="2" <?php if(input('param.status')==='2'): ?>selected<?php endif; ?>>已发货</option>
										<?php if(getcustom('yunyuzhou')): ?><option value="22" <?php if(input('param.status')==='22'): ?>selected<?php endif; ?>>部分发货</option><?php endif; ?>
										<option value="3" <?php if(input('param.status')==='3'): ?>selected<?php endif; ?>>已完成</option>
										<option value="4" <?php if(input('param.status')==='4'): ?>selected<?php endif; ?>>已关闭</option>
										<!-- <option value="5" <?php if(input('param.status')==='5'): ?>selected<?php endif; ?>>退款待审核</option> -->
										<option value="6" <?php if(input('param.status')==='6'): ?>selected<?php endif; ?>>已退款</option>
										<?php if($mendian_upgrade): ?><option value="8" <?php if(input('param.status')==='8'): ?>selected<?php endif; ?>>待提货</option><?php endif; ?>
										<!-- <option value="7" <?php if(input('param.status')==='7'): ?>selected<?php endif; ?>>退款驳回</option> -->
									</select>
								</div>
							</div>
							<?php if(getcustom('product_chinaums_subsidy')): ?>
							<div class="layui-inline">
								<label class="layui-form-label">国补订单</label>
								<div class="layui-input-inline">
									<select name="subsidy_order">
										<option value="">全部</option>
										<option value="1">国补订单</option>
										<option value="0">非国补订单</option>
									</select>
								</div>
							</div>
							<?php endif; ?>
							<div class="layui-inline">
								<label class="layui-form-label">订单金额</label>
								<div class="layui-input-inline" style="width:60px;margin-left:0px">
									<input type="number" name="totalpricemin" placeholder="" autocomplete="off" class="layui-input" min="0">
								</div>
								<div class="layui-input-inline" style="width:8px;">
									-
								</div>
								<div class="layui-input-inline" style="width:60px;margin-left:0px">
									<input type="number" name="totalpricemax" placeholder="" autocomplete="off" class="layui-input" min="0">
								</div>
							</div>
							<div class="layui-inline">
								<button class="layui-btn layuiadmin-btn-replys" lay-submit="" lay-filter="LAY-app-forumreply-search">
									<i class="layui-icon layui-icon-search layuiadmin-button-btn"></i>
								</button>
							</div>
						</div>
						<?php if(input('param.fxmid')): ?>
						<div class="layui-col-md12" style="font-weight:bold"><?php echo t('会员'); ?>ID:<?php echo app('request')->param('fxmid'); ?>&nbsp;&nbsp;获得的累计佣金￥<span id="totalcommission"></span></div>
						<?php endif; if(getcustom('shop_order_excel_countpay')): ?>
						<div class="layui-col-md12" >
							<span>支付方式统计</span>
							<span style="margin-left:10px">现金：</span><span id="countxianjin">0</span> 
							<span style="margin-left:10px"><?php echo t('余额'); ?>：</span><span id="countmoney" style="font-weight: bold">0</span> 
							<span style="margin-left:10px"><?php echo t('积分'); ?>：</span><span id="countscore" style="font-weight: bold">0</span> 
							<span style="margin-left:10px">成本价：</span><span id="countcostPrice" style="font-weight: bold">0</span>
						</div>
						<?php endif; ?>
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
	<script type="text/javascript" src="https://www.diandashop.com/index/api/commonjs"></script>
	<script>
  var table = layui.table;
	var datawhere = {};
	<?php if(input('?param.status') && input('param.status')!==''): ?>
	datawhere['status'] = "<?php echo app('request')->param('status'); ?>";
	<?php endif; if(input('?param.showtype') && input('param.showtype')!==''): ?>
	datawhere['showtype'] = "<?php echo app('request')->param('showtype'); ?>";
	<?php endif; if(input('?param.fxmid') && input('param.fxmid')!==''): ?>
	datawhere['fxmid'] = "<?php echo app('request')->param('fxmid'); ?>";
	<?php endif; if(input('?param.source') && input('param.source')!==''): ?>
	datawhere['source'] = "<?php echo app('request')->param('source'); ?>";
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
	  ,height:'full-180'
		//,size:'sm'
    ,cols: [[ //表头
			{type:"checkbox"},
      {field: 'id', title: 'ID',  sort: true,width:60},
			<?php if(input('param.showtype')==2): ?>
      {field: 'bname', title: '所属商户',width:160},
			<?php endif; if(getcustom('fuwu_usercenter')): ?>
	  {field: 'fuwu_name', title: '<?php echo t('服务中心'); ?>'},
	  <?php endif; if(getcustom('product_supplier_admin')): ?>
		  {field: 'supplier_name', title: '所属供应商'},
		  <?php endif; ?>
      {field: 'goodsdata', title: '商品信息',width:260},
      {field: 'ordernum', title: '订单号/下单时间',sort: true,width:160,templet:function(d){
		  var ordernumhtml = d.ordernum +'<div style="color:#080">'+ date('Y年m月d日 H:i',d.createtime)+'</div>';
		  if(d.transaction_num) ordernumhtml += '<div>支付流水号：'+ d.transaction_num+'</div>';
		  return ordernumhtml}},
      {field: 'product_price', title: '商品总价/实付款',width:150,templet:function(d){
		var html='';
		html+='<div><div>总价：￥'+d.product_price+'</div>';
		html+='<div style="font-weight:bold">实付：'+(d.usd_totalprice>0?'$'+d.usd_totalprice+'&nbsp':'')+' ￥'+d.totalprice+'</div></div>';
		  <?php if(getcustom('freeze_money')): ?>
			  if(d.total_freezemoney_price>0){
				  html+='<div><div><?php echo t('冻结资金'); ?>：￥'+d.total_freezemoney_price+'</div>';
			  }
		  <?php endif; ?>
		return html;
      }},
      //{field: 'totalprice', title: '实付款'},
      {field: 'address', title: '收货地址',templet: '<div><div style="font-weight:bold">{{d.linkman}} {{#if(d.company){ }}{{d.company}}{{# } }} {{d.tel}}</div><div style="line-height:20px;font-size:12px">{{d.area}} {{d.address}}</div>{{#if(d.message){ }}<!-- <div style="line-height:20px;font-size:12px;color:#e55555">客户留言：{{d.message}}</div> -->{{# } }}</div>'},
	  <?php if(getcustom('product_pingce')): if($auth_data=='all' || in_array('ProductPingce',$auth_data)): ?>

	    {field: 'address',  width:300 ,title: '测评信息',templet:function(d){
		   var html='';
		    if(d.pingce){
	 			html+='<div style="font-weight:bold">姓名：'+d.pingce.name+'</div>';
				html+='<div style="font-weight:bold">性别：'+d.pingce.gender+'</div>';
				html+='<div style="font-weight:bold">年龄：'+d.pingce.age+'</div>';
				html+='<div style="font-weight:bold">手机：'+d.pingce.tel+'</div>';
  	 			html+='<div style="font-weight:bold">邮箱：'+d.pingce.email+'</div>';
	 			html+='<div style="font-weight:bold">学校：'+d.pingce.school+'</div>';
	 			html+='<div style="font-weight:bold">专业：'+d.pingce.major+'</div>';
	 			html+='<div style="font-weight:bold">学历：'+d.pingce.education+'</div>';
	 			html+='<div style="font-weight:bold">入学年份：'+d.pingce.enrol+'年</div>';
				if(d.pingce.faculties){
					html+='<div style="font-weight:bold">院系：'+d.pingce.faculties+'</div>';
				}
				if(d.pingce.class_name){
	 				html+='<div style="font-weight:bold">班级：'+d.pingce.class_name+'</div>';
				}
			}
			return html;
		}},
	   <?php endif; ?>
	  <?php endif; if(getcustom('shop_buy_worknum')): ?>
	  		{field: 'worknum', title: '工号',width:120},
	  	<?php endif; if(getcustom('shop_product_fenqi_pay')): ?>
	  		{field: 'is_fenqi', title: '分期订单',width:120,templet:function(d){
				var html = '否';
				if(d.is_fenqi) html ='分'+d.fenqi_num+'期';
				return html;
      }},
	  	<?php endif; ?>
			{field: 'platform', title: '来源',width:100},
			{field: 'nickname', title: '头像昵称',width:120,templet:function(d){
				var html = '';
				if(d.headimg) html +='<img src="'+d.headimg+'" style="height:50px"/> <br>';
				if(d.nickname) html += d.nickname;
				html += '<br><?php echo t('会员'); ?>ID：'+d.mid;
				if(d.m_remark){
					html += '<br><span style="color:#f55">'+d.m_remark+'</span>';
				}
				return html;
      }},
		  <?php if($order_show_member_apply): ?>
		  {field: 'member_apply_info', title: '下单人',width:120},
		  <?php endif; ?>
			{field: 'paytype', title: '支付方式',width:150,templet:function(d){
				var html = '';
				if(d.paytype){
					html += d.paytype;
				}
				if(d.is_yuanbao_pay){
					html += "<br>(元宝："+d.total_yuanbao+")";
				}
				return html;
			}},
			<?php if($mendian_upgrade): ?>
				{field: 'shequ', title: '社区/<?php echo t('门店'); ?>',width:150,templet:function(d){
					var html = '';
					if(d.mdid>0){
						html+='<div>'+d.mdname+ ' ' +d.mdtel+' </div>';
						html+='<div>小区：<span style="color:#82BFB5">'+d.mdxqname+' </span></div>';
					}
					return html;
				}},
			<?php endif; ?>
			{field: 'freight_text', title: '配送方式',width:150,templet:function(d){
					var html = d.freight_text;
					<?php if(getcustom('up_floor_fee')): ?>
						if(d.up_floor_fee>0){
							html+='<div><div>上楼费：￥'+d.up_floor_fee+'</div>';
						}
					<?php endif; ?>
					return html;
				}},
		  <?php if(getcustom('erp_wangdiantong') && $erpWdtOpen==1): ?>
		  {field: 'expressdata', title: '快递信息',width:120},
		  <?php endif; if(getcustom('product_quanyi')): ?>
		  {field: 'hexiao_num_total', title: '核销次数/已核销',width:120,templet:function(d){
				  return d.hexiao_num_total+' / '+d.hexiao_num_used;
			  }},
		  <?php endif; if(getcustom('shop_giveorder')): ?>
			{field: 'usegiveorder', title: '赠好友',templet:function(d){
				var html = '';
				if(d.usegiveorder==0){
					html = '否';
				}else{
					if(d.giveordermid){
						html +='<div style="margin-top:10px">';
						if(d.givemember){
							if(d.givemember.headimg) html +='<img src="'+d.givemember.headimg+'" style="height:50px"/>';
							if(d.givemember.nickname) html += d.givemember.nickname;
						}
						html += "(ID:"+d.giveordermid+")";
						html +='</div>';
						html += '<div><span style="color:green">是</span><span style="color:green;">(已领取)</span></div>';
					}else{
						html += '<div><span style="color:green">是</span><span style="color:red;">(未领取)</span></div>';
					}
				}
				return html;
			}},
			<?php endif; if(getcustom('mendian_usercenter')): ?>
		  {field: 'lock_mdid', title: '所属<?php echo t("门店"); ?>',templet:function(d){
				  var html = '<ul>';
				  if(d.mdid <= 0) return '无';
				  html+='<li>ID：'+d.mdid+'</li>';
				  html+='<li>名称：'+d.mendian+'</li>';
				  html+' </ul>';
				  return html;
			  }},
		  <?php endif; ?>
      {field: 'status', title: '状态',templet:function(d){
				var html = '';
				if(d.status==0) html+='<div style="color:#ff8758">未支付</div>';
				if(d.status==1 && d.paytypeid!='4') html+='<div style="color:#008000">已支付</div>';
				if(d.status==1 && d.paytypeid=='4') html+='<div style="color:#008000">待发货</div>';
				if(d.status==2){
					if(d.express_isbufen == 1){
						html+='<div style="color:#ff4246">部分发货</div>';
					}else{
						html+='<div style="color:#ff4246">已发货</div>';
					}
				}
				if(d.status==3) html+='<div style="color:#999">已完成</div>';
				if(d.status==4) html+='<div style="color:#bbb">已关闭</div>';
				<?php if($mendian_upgrade): ?>
					if(d.status==8) html+='<div style="color:#999">待提货</div>';
				<?php endif; ?>
				if(d.refund_status == 1) html+='<div style="color:red">退款待审核,￥'+d.refund_money+'</div>';
				if(d.refund_status == 2) html+='<div style="color:red">已退款,￥'+d.refund_money+'</div>';
				if(d.refund_status == 3) html+='<div style="color:red">退款驳回,￥'+d.refund_money+'</div>';
				if(d.balance_price > 0) html+='<div style="color:red">尾款￥'+d.balance_price+','+(d.balance_pay_status==0?'未支付':'已支付')+'</div>';
				if(d.refundCount > 0)  html+='<div><a href="javascript:void(0)" style="color:red" onclick="openmax(\'<?php echo url('ShopRefundOrder/index'); ?>/bid/'+d.bid+'/orderid/'+d.id+'/isopen/1\')">有售后（'+d.refundCount+'）</a></div>';

				<?php if(getcustom('supply_yongsheng')): ?>
				if(d.status==1 && d.source_status && d.source_status == 2){
					html+='<div style="color:red;min-width:200px">供应链下单失败</div>';
					if(d.source_status_msg) html+='<div style="color:red">原因：'+d.source_status_msg+'</div>';
					html += '<button class="table-btn" onclick="createOrderYs('+d.id+')">手动下单</button>';
				}
				<?php endif; ?>
				return html;
			},width:110},
		<?php if(getcustom('invite_free')): ?>
	  		{field: 'is_free', title: '是否免单',templet:function(d){
				var html = '';
				if(d.is_free==0) html+='<div style="color:#008000">否</div>';
				if(d.is_free==1) html+='<div style="color:#ff8758">是</div>';
				return html;
			},width:80},
	  <?php endif; if(getcustom('shop_yuding') || getcustom('product_weight')): ?>
	  {field: 'yuding_type', title: '订单类型',templet:function(d){
			  var html = '';
			  if(d.yuding_type==0 ||d.yuding_type==null ) html+='<div style="color:#ff8758">普通订单</div>';
			  if(d.yuding_type==1) html+='<div style="color:#008000">预定订单</div>';
			  if(d.yuding_type==2) html+='<div style="color:#4681f5">称重订单</div>';
			  return html;
		  },width:80},
	  <?php endif; if(getcustom('douyin_groupbuy')): ?>
			{field: 'isdygroupbuy', title: '抖音团购券',templet:function(d){
				var html = '';
				if(d.isdygroupbuy==0) html+='<div style="color:#008000">否</div>';
				if(d.isdygroupbuy==1){
					html+='<div style="color:#ff8758">是</div>';
					html+='<div>抖音团购券信息:</div>';
					html+='<div>'+d.dyorderids+'</div>';
				} 
				return html;
			},width:180},
	  <?php endif; if(getcustom('supply_zhenxin') || getcustom('supply_yongsheng')): ?>
		{field: 'source', title: '商品来源',templet:function(d){
			var html = '';
			if(d.issource==0){
				html = '本系统';
			}else{
				if(d.source == 'supply_zhenxin'){
					html += '甄新汇选<br>';
					html += '订单号：<br>';
					html += d.sordernum;
				}else if(d.source == 'supply_yongsheng'){
					html += '<?php echo $yongshengname; ?><br>';
					html += '订单号：<br>';
					html += d.sordernum;
				}
			}
			return html;
		},width:160},
		<?php endif; ?>
	  {field: 'message', title: "备注",templet:function(d){
		  var html = '';
		  if(d.message != null) html += d.message;
		  if(d.remark != null) html+='<div style="">后台备注：'+d.remark+'</div>';
		  return html;
	  },width:150},
      //{field: 'createtime', title: '下单时间',sort: true,templet:function(d){ return date('Y-m-d H:i',d.createtime)},width:150},
			  
	  <?php if(getcustom('extend_exchange_card_yuyue_send') && ($auth_data=='all' || in_array('ExchangeCardYuyueSend',$auth_data))): ?>
		  {field: 'exchange_card_take_date', title: '预约提货时间',sort: true,templet:function(d){ 
		  	
			if(d.exchange_card_take_date){
				return d.exchange_card_take_date
			}else{
				return  '无';
			  }  
		 },width:150},
	  <?php endif; ?>
      {field: 'operation', title: '操作',minWidth:160,templet:function(d){
				var html = '';
			  <?php if($is_fuwu==1): ?>return html;<?php endif; if($bid==0): ?>
					if(d.paytypeid==5 && d.payorder && d.payorder.check_status>=0){
						if(d.transfer_check == 1){
							html += '<button class="table-btn" onclick="doOperation('+d.id+',\'付款凭证\')">付款凭证</button>';
						}else if(d.transfer_check == 0){
							html += '<button class="table-btn" onclick="doOperation('+d.id+',\'转账审核\')">转账审核</button>';
						}else if(d.transfer_check == -1){
							html += '<button class="table-btn" style="background:#eee">转账已驳回</button>';
						}
					}
				<?php endif; if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderDetail',$auth_data))): ?>
				html += '<button class="table-btn" onclick="showdetail('+d.id+')">详情</button>';
				<?php endif; if($shopset['shd_style']==1): ?>
				if(d.status==1 || d.status==2 || d.status==3) html += '<button class="table-btn" onclick="openmax(\'<?php echo url('shd1'); ?>/id/'+d.id+'\')">水洗贴</button>';
				<?php else: if((!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderShd',$auth_data)))): ?>
						html += '<button class="table-btn" onclick="openmax(\'<?php echo url('shd'); ?>/id/'+d.id+'\')">送货单</button>';
					<?php endif; ?>
				<?php endif; if($hasprint): ?>
				html += '<button class="table-btn" onclick="wifiprint('+d.id+')">打印小票</button>';
				<?php endif; if(getcustom('shoporder_update') && (!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderEdit',$auth_data)))): ?>
					if(d.canEdit) html += '<button class="table-btn" onclick="openmax(\'<?php echo url('edit'); ?>/id/'+d.id+'\')">修改</button>';
				<?php endif; if($bid==0 && !getcustom('shoporder_update') && (!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderEdit',$auth_data)))): ?>
					if(d.canEdit) html += '<button class="table-btn" onclick="openmax(\'<?php echo url('edit'); ?>/id/'+d.id+'\')">修改</button>';
				<?php endif; if($logdel_auth && (!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderDel',$auth_data))) && (!getcustom('shoporder_del_auth') || ($auth_data=='all' || in_array('OrderDelAuth',$auth_data)))): ?>
					if(!d.sell_business) {
                        html += '<button class="table-btn" onclick="datadel(' + d.id + ')">删除</button><br/>';
                    }
					<?php endif; ?>
				if(d.refund_status==1){ //退款待审核
					html += '<button class="table-btn" onclick="doOperation('+d.id+',\'退款审核\')">退款审核</button>';
				}
				if(d.status==0){ //未支付
					html += '<button class="table-btn" onclick="doOperation('+d.id+',\'关闭订单\')">关闭订单</button>';
					<?php if($bid==0 && $order_admin_payorder_switch==1): ?>
						if(!d.isdygroupbuy && d.canPay){
							html += '<button class="table-btn" onclick="doOperation('+d.id+',\'已支付\')">改为已支付</button>';
						}
					<?php endif; ?>
				}
				if(d.balance_price > 0 && d.balance_pay_status==0){ //尾款未支付
					html += '<button class="table-btn" onclick="doOperation('+d.id+',\'尾款已支付\')">改为已支付</button>';
				}
                <?php if(getcustom('product_glass_custom')): ?>
                    if(d.glass_custom_id){
                        html += '<button class="table-btn" onclick="glassCustomDetail('+d.id+',\'定制参数\')">定制参数</button>';
                    }
                <?php endif; ?>
				if(d.status==1){ //已支付
					if((d.source != 'supply_zhenxin' && d.source != 'supply_yongsheng')  && !d.sell_business){
						if(d.yuding_type==2){
							<?php if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderSend',$auth_data))): ?>
							html += '<button class="table-btn" onclick="openmax(\'<?php echo url('WeightOrder/fahuo'); ?>/id/'+d.id+'\')">发货</button>';
							<?php endif; ?>
						}else{
							if(d.can_fahuo==1 && d.is_hid_btn == 0) {
								<?php if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderSend',$auth_data))): ?>
								html += '<button class="table-btn" onclick="doOperation(' + d.id + ',\'发货\')">发货</button>';
								<?php endif; ?>
							}
						}
					}
					if(d.freight_type==1 && d.is_quanyi==0){
						<?php if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderHexiao',$auth_data))): ?>
						html += '<button class="table-btn" onclick="doOperation('+d.id+',\'核销\')">核销</button>';
						<?php endif; ?>
						if(d.hexiao_code_member){html += '<button class="table-btn" onclick="dialog(\''+d.hexiao_code_member+'\')"><?php echo t('会员'); ?>核销密码</button>';}
					}
					if(d.freight_type==2 ){
							if(d.wx_express_intracity){
								html += '<button class="table-btn" onclick="showxtc('+d.id+',\'微信同城配送\')">微信同城配送</button>';
							}
							if('<?php echo $peisong_set['status']; ?>'==1  && !d.sell_business){
								if('<?php echo $peisong_set['myt_status']; ?>'==1){
									//html += '<button class="table-btn" onclick="doOperation('+d.id+',\'麦芽田配送\')">麦芽田配送</button>';
									html += '<button class="table-btn" onclick="showmyt('+d.id+',\'麦芽田配送\')">麦芽田配送</button>';
								}else if('<?php echo $peisong_set['express_wx_status']; ?>'==1){
									html += '<button class="table-btn" onclick="doOperation('+d.id+',\'即时配送\')">即时配送派单</button>';
								}else{
									html += '<button class="table-btn" onclick="doOperation('+d.id+',\'配送员配送\')">配送</button>';
								}
							}
					}
					<?php if($is_can_refund==1): ?>
					html += '<button class="table-btn" onclick="doOperation('+d.id+',\'退款\')">退款</button>';
					<?php endif; ?>
				}
				if(d.status==2){ //已发货
					html += '<button class="table-btn" onclick="doOperation('+d.id+',\'查物流\')">查物流</button>';
					if(d.can_fahuo==1) {
						html += '<button class="table-btn" onclick="doOperation('+d.id+',\'发货\')">改物流</button>';
					}
					if(d.freight_type==1 && d.is_quanyi==0){
						<?php if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderHexiao',$auth_data))): ?>
						html += '<button class="table-btn" onclick="doOperation('+d.id+',\'核销\')">核销</button>';
						<?php endif; ?>
					}
					<?php if($bid==0): if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderConfirm',$auth_data))): ?>
					html += '<button class="table-btn" onclick="doOperation('+d.id+',\'确认收货\')">确认收货</button>';
					<?php endif; else: ?>
					if(d.paytypeid==4){
						<?php if(!getcustom('handle_auth') || ($auth_data=='all' || in_array('ShopOrderConfirm',$auth_data))): ?>
						html += '<button class="table-btn" onclick="doOperation('+d.id+',\'确认收货\')">确认收货</button>';
						<?php endif; ?>
					}
					<?php endif; if($is_can_refund==1): ?>
					html += '<button class="table-btn" onclick="doOperation('+d.id+',\'退款\')">退款</button>';
					<?php endif; ?>
				}
				if(d.status==3){ //已完成
					html += '<button class="table-btn" onclick="doOperation('+d.id+',\'查物流\')">查物流</button>';
				}
				<?php if(getcustom('product_handwork')): ?>
					if(d.ishand == 1){
						html += '<button class="table-btn" onclick="openmax(\'<?php echo url('ShopHandOrder/index'); ?>/isopen/1/ordernum/'+d.ordernum+'\')">查看回寄订单</button>'
					}
			  <?php endif; if(getcustom('shop_product_fenqi_pay')): ?>
					if(d.is_fenqi == 1){
						html += '<button class="table-btn" onclick="fenqidetail('+d.id+')">查看分期</button>'
					}
			  <?php endif; if(getcustom('product_pingce')): if($auth_data=='all' || in_array('ProductPingce',$auth_data)): ?>
					if(d.is_pingce == 1){
						html += '<button class="table-btn" onclick="pingcedetail('+d.id+')">查看测评报告</button>'
					}
	  			<?php endif; ?>
			  <?php endif; if(getcustom('mendian_no_select')): ?>
				  if(d.freight_type == 1){
					  html += '<button class="table-btn" onclick="openmax(\'<?php echo url('Hexiao/index'); ?>/isopen/1/orderid/'+d.id+'\')">查看核销记录</button>'
				  }
				  <?php endif; if(getcustom('shop_product_certificate') &&  ($auth_data=='all' || in_array('ShopProductCertificate',$auth_data))): ?>
						html += '<button class="table-btn" onclick="openmax(\'<?php echo url('CertificatePoster/productCertificatePoster'); ?>/isopen/1/orderid/'+d.id+'\')">商品证书</button>'
				  <?php endif; if(getcustom('shopbuy_sign') && ($auth_data=='all' || in_array('ShopbuySign',$auth_data))): ?>
					if(d.sign_contract_file){
						html += '<button class="table-btn" onclick="showhetong(\''+d.sign_contract_file+'\')">查看合同</button>';
					}
					<?php endif; if(getcustom('shoporder_copy')): ?>
						html += '<button class="table-btn" onclick="copyOrder(\''+d.id+'\')">复制订单</button>';
					<?php endif; if(getcustom('shoporder_update_member')): ?>
						if(d.status == 0){
							html += '<button class="table-btn" onclick="updateMember(\''+d.id+'\')">更换下单人</button>';
						}
					<?php endif; ?>
				  	  
				return html;
      }}
    ]]
		,done:function(res, curr, count){
			<?php if(input('param.fxmid')): ?>
			if(curr == 1){
				$('#totalcommission').html(res.totalcommission);
			}
			<?php endif; if(getcustom('shop_order_excel_countpay')): ?>
				var countpays = res.countpays
				$('#countxianjin').html(countpays.countxianjin);
				$('#countmoney').html(countpays.countmoney);
				$('#countscore').html(countpays.countscore);
				$('#countcostPrice').html(countpays.countcostPrice);
			<?php endif; ?>
			if(document.documentElement.scrollTop >= 300){
				document.documentElement.scrollTop = document.documentElement.scrollTop - 10
				$(".layui-table-body .table-imgbox").each(function(){
					 var src = $(this).find('img').attr('lay-src');
					 $(this).find('img').attr('src',src)
				})
			}else{
				document.documentElement.scrollTop = document.documentElement.scrollTop + 10 
				$(".layui-table-body .table-imgbox").each(function(){
					 var src = $(this).find('img').attr('lay-src');
					 $(this).find('img').attr('src',src)
				})
			}
			
		}
  });
    var url = window.location.href;  
	var mdid = '';  
	var match = url.match(/mdid\/(\d+)/);  
	if (match) {  
	    mdid = match[1];  
	    datawhere.mdid = mdid;

	}
	 //监听表格复选框选择  
	table.on('checkbox(tabledata)', function(obj){  
		var ids = '';
		var checkStatus = table.checkStatus('tabledata')
		var checkData = checkStatus.data; //得到选中的数据
		for(var i=0;i<checkData.length;i++){
			ids = ids+','+checkData[i]['id'];
		}
		datawhere.ids = ids;
		// if(obj.type === 'all'){ //全选  
		//     console.log(obj);  
		// } else if(obj.type === 'one'){ //单选  
		//     console.log(obj);  
		// }  
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
		type:'datetime',
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
	var tuikuandata = {};
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
				<?php if($view_order_fenxiao==1): ?>
				detailBtn.push('分销明细');
				<?php endif; if($view_order_fenhong==1): ?>
				detailBtn.push('分红明细');
				<?php endif; ?>
				if(data.order.refund_status==1){ //退款待审核
					detailBtn.push('退款审核');
				}
				if(data.order.status==0){ //未支付
					detailBtn.push('关闭订单');
					<?php if($bid==0): ?>
						if(!data.order.isdygroupbuy && data.order.canPay && data.order.order_admin_payorder_switch==1){
							detailBtn.push('已支付');
						}
					<?php endif; ?>
					if(data.shopset.changeprice_status==1) {
						detailBtn.push('改价');
					}
				}
				if(data.order.status==1){ //已支付
					if(data.order.canFahuo==1){
						detailBtn.push('发货');
					}
					if(data.order.freight_type==1 && data.order.is_quanyi==0)
						detailBtn.push('核销');
					if(data.order.freight_type==2 && data.order.wx_express_intracity){
						detailBtn.push('微信同城配送');
					}
					if(data.order.freight_type==2 &&'<?php echo $peisong_set['status']; ?>'==1){
						if('<?php echo $peisong_set['myt_status']; ?>'==1){
							detailBtn.push('麦芽田配送');
						}else if('<?php echo $peisong_set['express_wx_status']; ?>'==1){
							detailBtn.push('即时配送');
						}else{
							detailBtn.push('配送员配送');
						}
					}
					if(data.order.is_can_refund == 1){
						detailBtn.push('退款');
					}
				}
				if(data.order.status==2){ //已发货
					detailBtn.push('查物流');
					if(data.order.freight_type==1){
						detailBtn.push('核销');
					}
					<?php if($bid==0): ?>
					detailBtn.push('确认收货');
					<?php else: ?>
					if(data.order.paytypeid==4){
						detailBtn.push('确认收货');
					}
					<?php endif; ?>
				}
				if(data.order.status==3){ //已完成
					detailBtn.push('查物流');
				}
				//if(data.order.status==4){ //已关闭
					detailBtn.push('删除');
				//}
				detailBtn.push('设置备注');
				detailLayer = layer.open({type:1,title:'订单详情',area:'500px',content:html,resize:true,shadeClose:true,maxmin:true,btn:detailBtn,
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
	function putAway(obj){
		$(obj).html($(obj).html().substring(0,2) == '收起' ? '展开 &#9660' : '收起 &#9650');
		$(obj).prevAll().slice(0, $(obj).prevAll().length - 3).toggle();
	}
	var orderdata = {};
	function doOperation(orderid,type){
		var index_load = layer.load();
		$.post("<?php echo url('getdetail'); ?>",{orderid:orderid,optionType:type},function(data){
			layer.close(index_load);
			orderdata = data;
			btnOperation(type,data);
		})
	}
	function btnOperation(type,data){
		var orderid = data.order.id;
		var transfer_order_parent_check = data.payorder.transfer_order_parent_check;

		<?php if(getcustom('pay_transfer')): ?>
		if(transfer_order_parent_check && data.payorder.ptstatus == 1){
			var ann = ['确认已支付', '驳回','确认收款','取消订单'];
		}else{
			var ann = ['确认已支付', '驳回'];
		}
		if(type=='付款凭证'){
			layui.laytpl(payCheckTpl.innerHTML).render(data.payorder, function(html){
				var refundCheckLayer = layer.open({type:1,title:false,area:'auto',content:html,shadeClose:true,btn: ann,
					yes:function(){
						var index = layer.load();
						$.post("<?php echo url('payCheck'); ?>",{orderid:orderid,st:1,remark:$('input[name=check_remark]').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(refundCheckLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					},
					btn2:function(){
						var index = layer.load();
						$.post("<?php echo url('payCheck'); ?>",{orderid:orderid,st:2,remark:$('input[name=check_remark]').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(refundCheckLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					},
					btn3:function(){
						//layer.confirm('确定要收款吗?',{icon: 7, title:'操作确认'}, function(index){
							var index = layer.load();
							$.post("<?php echo url('OrderParentCheck'); ?>",{orderid:orderid,type:1},function(res){
								layer.close(index);
								dialog(res.msg,res.status);
								layer.close(refundCheckLayer);
								layer.close(detailLayer);
								tableIns.reload()
							})
						//});
					},
					btn4:function(){
						layer.confirm('确定要取消订单吗?',{icon: 7, title:'操作确认'}, function(index){
							var index = layer.load();
							$.post("<?php echo url('OrderParentCheck'); ?>",{orderid:orderid,type:2},function(res){
								layer.close(index);
								dialog(res.msg,res.status);
								layer.close(refundCheckLayer);
								layer.close(detailLayer);
								tableIns.reload()
							})
						});
					},
				})
			})
		}
		if(type=='转账审核'){
			var refundCheckLayer = layer.open({type:1,title:'转账审核',content:html,shadeClose:true,btn: ['同意可转账', '驳回可转账'],
				yes:function(){
					var index = layer.load();
					$.post("<?php echo url('transferCheck'); ?>",{orderid:orderid,st:1},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(refundCheckLayer);
						layer.close(detailLayer);
						tableIns.reload()
					})
				},
				btn2:function(){
					var index = layer.load();
					$.post("<?php echo url('transferCheck'); ?>",{orderid:orderid,st:-1},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(refundCheckLayer);
						layer.close(detailLayer);
						tableIns.reload()
					})
				}
			})
		}
		<?php endif; ?>
		if(type=='退款审核'){
			layui.laytpl(refundCheckTpl.innerHTML).render(data.order, function(html){
				var refundCheckLayer = layer.open({type:1,title:false,content:html,shadeClose:true,btn: ['同意并退款', '驳回退款申请'],
					yes:function(){
						var index = layer.load();
						$.post("<?php echo url('refundCheck'); ?>",{orderid:orderid,st:1,remark:$('input[name=refund_checkremark]').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(refundCheckLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					},
					btn2:function(){
						var index = layer.load();
						$.post("<?php echo url('refundCheck'); ?>",{orderid:orderid,st:2,remark:$('input[name=refund_checkremark]').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(refundCheckLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					}
				})
			})
		}else if(type == '发货'){
			//卡密发货
			if(data.order.freight_type==4){
				layer.confirm('确定要卡密发货吗?',{icon: 7, title:'操作确认'}, function(index){
					layer.close(index);
					var index = layer.load();
					$.post("<?php echo url('sendExpress'); ?>",{orderid:orderid},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(sendExpressLayer);
						layer.close(detailLayer);
						tableIns.reload()
					});
				})
				return;
			}
	      
			if(data.order.freight_type==10){ //货运托运
				var html = '<div style="margin:20px auto;">';
				html+='<div class="layui-form" lay-filter="">';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">物流单照片：</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="express_pic" id="express_pic" class="layui-input">';
				html+='		</div>';
				html+='		<button style="float:left;" type="button" class="layui-btn layui-btn-primary" upload-input="express_pic" upload-preview="express_picPreview" onclick="uploader(this)">上传</button>';
				html+='		<div id="express_picPreview" style="float:left;padding-top:10px;padding-left:140px;clear: both;">';
				html+='			<div class="layui-imgbox" style="width:100px;"><div class="layui-imgbox-img"><img src=""/></div></div>';
				html+='		</div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">发货人</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="express_fhname" id="express_fhname" class="layui-input">';
				html+='		</div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">发货地点</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="express_fhaddress" id="express_fhaddress" class="layui-input">';
				html+='		</div>';
				html+='		<div class="layui-form-mid"></div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">收货人</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="express_shname" id="express_shname" class="layui-input">';
				html+='		</div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">收货地点</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="express_shaddress" id="express_shaddress" class="layui-input">';
				html+='		</div>';
				html+='		<div class="layui-form-mid"></div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:110px">备注</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="express_remark" id="express_remark" class="layui-input">';
				html+='		</div>';
				html+='		<div class="layui-form-mid"></div>';
				html+='	</div>';
				//html+='	<div class="layui-form-item" style="margin-top:30px">';
				//html+='		<label class="layui-form-label" style="width:110px"></label>';
				//html+='		<div class="layui-input-inline">';
				//html+='			<button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit_excel">确定</button>';
				//html+='		</div>';
				//html+='	</div>';
				html+='</div>';
				html+='</div>'
				var sendExpressLayer = layer.open({type:1,area: ['600px', '530px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],yes:function(){
						var index = layer.load();
						$.post("<?php echo url('sendExpress'); ?>",{orderid:orderid,pic:$('#express_pic').val(),fhname:$('#express_fhname').val(),fhaddress:$('#express_fhaddress').val(),shname:$('#express_shname').val(),shaddress:$('#express_shaddress').val(),remark:$('#express_remark').val()},function(res){
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
			}
			if(data.canmiandan==1){
				var fahuotypeLayer = layer.open({type:0,title:'请选择发货方式',content:'是否要使用小程序物流助手发货?',shadeClose:true,btn: ['使用物流助手发货', '手动发货'],
					yes:function(){
						layer.close(fahuotypeLayer);
						layer.close(detailLayer);
						layer.open({type:2,title:'创建运单',area:['900px','90%'],content:"<?php echo url('miandan/addorder'); ?>/ordertype/shop_order/orderid/"+orderid,scrollbar:false});
					},
					btn2:function(){
						layui.laytpl(sendExpressTpl.innerHTML).render(data, function(html){
							var sendExpressLayer = layer.open({type:1,title:'订单发货',area:['600px','600px'],content:html,shadeClose:true,btn: ['确定', '取消'],
								yes:function(){
									var express_com = [];
									$("select[name='send_express[]']").each(function(){
										express_com.push($(this).val());
									})
									var express_no = [];
									$("input[name='send_express_no[]']").each(function(){
										express_no.push($(this).val());
									})
									var express_ogids = [];
									$(".send_express_ogids").each(function(){
										var thisogids = [];
										$(this).find('.layui-form-checked').each(function(){
											thisogids.push($(this).prev().val());
										});
										express_ogids.push(thisogids.join(','));
									});
									var index = layer.load();
									$.post("<?php echo url('sendExpress'); ?>",{orderid:orderid,express_com:express_com,express_no:express_no,express_ogids:express_ogids},function(res){
										layer.close(index);
										dialog(res.msg,res.status);
										layer.close(sendExpressLayer);
										layer.close(detailLayer);
										tableIns.reload()
									})
								}
							});
							layui.form.render();
						});
					}
				})
			}else{
				layui.laytpl(sendExpressTpl.innerHTML).render(data, function(html){
					var sendExpressLayer = layer.open({type:1,title:'订单发货',area:['600px','600px'],content:html,shadeClose:true,btn: ['确定', '取消'],
						yes:function(){
							var express_com = [];
							$("select[name='send_express[]']").each(function(){
								express_com.push($(this).val());
							})
							var express_no = [];
							$("input[name='send_express_no[]']").each(function(){
								express_no.push($(this).val());
							});
							var express_ogids = [];
							$(".send_express_ogids").each(function(){
								var thisogids = [];
								$(this).find('.layui-form-checked').each(function(){
									thisogids.push($(this).prev().val());
								});
								express_ogids.push(thisogids.join(','));
							});
							var index = layer.load();
							$.post("<?php echo url('sendExpress'); ?>",{orderid:orderid,express_com:express_com,express_no:express_no,express_ogids:express_ogids},function(res){
								layer.close(index);
								dialog(res.msg,res.status);
								layer.close(sendExpressLayer);
								layer.close(detailLayer);
								tableIns.reload()
							})
						}
					});
					layui.form.render();
				});
			}
		}else if(type == '配送员配送'){
			var index = layer.load();
			$.post("<?php echo url('Peisong/getpeisonguser'); ?>",{type:'shop_order',orderid:orderid},function(res){
				layer.close(index);
				if(res.status == 0) {
					dialog(res.msg,res.status);return;
				}
				var peisonguser = res.peisonguser
				var paidantype = res.paidantype
				var psfee = res.psfee
				var ticheng = res.ticheng
				var html = '';
				html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
				if(paidantype == 1){
					html+='		<label class="layui-form-label" style="width:80px">选择配送员</label>';
					html+='		<div class="layui-input-inline" style="width:350px">';
					html+='			<select id="peisonguser" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;">';
					for(var i in peisonguser){
						html+='			<option value="'+peisonguser[i].id+'">'+peisonguser[i].title+'</option>';
					}
					html+='			</select>';
					html+='		</div>';
				}else{
					
					if(data.order.bid == 0 || (res.set && res.set.bid && res.set.bid > 0 && res.set.status ==1)){
						html+='		<div class="layui-form-mid" style="margin:0 20px">选择配送员配送，订单将发布到抢单大厅由配送员抢单，配送员提成￥'+ticheng+'，确定要配送员配送吗？</div>';
					}else{
						var business_up_floor_msg = '';
						if(res.business_up_floor_fee > 0){
							business_up_floor_msg = '，上楼费￥'+res.business_up_floor_fee;
						}
						html+='		<div class="layui-form-mid" style="margin:0 20px">选择配送员配送，订单将发布到抢单大厅由配送员抢单，需扣除配送费￥'+psfee+business_up_floor_msg+'，确定要配送员配送吗？</div>';
					}
				}
				html+='	</div>';
				var peisongLayer = layer.open({type:1,area:['500px','250px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
					yes:function(){
						var index = layer.load();
						if(paidantype == 1){
							var psid = $('#peisonguser').val();
						}else if(paidantype == 2){
							var psid = '-1';
						}else{
							var psid = '0';
						}
						$.post("<?php echo url('Peisong/peisong'); ?>",{type:'shop_order',orderid:orderid,psid:psid},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(peisongLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					}
				})
				layui.form.render();
			})
		}else if(type == '即时配送'){
			var index = layer.load();layer.close(index);
			var psfee = data.freight_price
			var html = '';
			html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
			if(data.order.bid == 0){
				html+='		<div class="layui-form-mid" style="margin:0 20px">选择即时配送，订单将派单到第三方配送平台，并扣除相应费用，确定要派单吗？</div>';
			}else{
				html+='		<div class="layui-form-mid" style="margin:0 20px">选择即时配送，订单将派单到第三方配送平台，需扣除配送费￥'+psfee+'，确定要派单吗？</div>';
			}
			html+='	</div>';
			var peisongLayer = layer.open({type:1,area:['500px','250px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
				yes:function(){
					var index = layer.load();
					var psid = '0';
					$.post("<?php echo url('Peisong/wx_addorder'); ?>",{type:'shop_order',orderid:orderid,psid:psid},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(peisongLayer);
						layer.close(detailLayer);
						tableIns.reload()
					})
				}
			})
			layui.form.render();
		}else if(type == '麦芽田配送'){
			showmyt(orderid);
			return
			var html = '';
			html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
			html+='		<label class="layui-form-label" style="width:80px">重量</label>';
			html+='		<div class="layui-input-inline" >';
			html+='			<input type="text" id="weight" class="layui-input" placeholder="请输入重量(选填)" value="">';
			html+='		</div>';
			html+='	</div>';
			html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
			html+='		<label class="layui-form-label" style="width:80px">备注</label>';
			html+='		<div class="layui-input-inline" >';
			html+='			<input type="text" id="remark" class="layui-input" placeholder="请输入备注(选填)" value="">';
			html+='		</div>';
			html+='	</div>';
			var refundLayer = layer.open({type:1,area:['500px','250px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
				yes:function(){
					var index = layer.load();
					$.post("<?php echo url('Peisong/peisong'); ?>",{type:'shop_order',orderid:orderid,myt_weight:$('#weight').val(),myt_remark:$('#remark').val(),myt_shop_id:0,psid:-2},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(refundLayer);
						layer.close(detailLayer);
						tableIns.reload()
					})
				}
			})

		}else if(type == '微信同城配送'){
			showxtc(orderid);
		}else if(type == '退款'){
			// 商品
			// 退款价格
			// var html = '';
			// html+='	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
			// html+='		<label class="layui-form-label" style="width:80px">退款原因</label>';
			// html+='		<div class="layui-input-inline" style="width:350px">';
			// html+='			<textarea type="text" id="tuireason" class="layui-textarea"></textarea>';
			// html+='		</div>';
			// html+='	</div>';
			// var refundLayer = layer.open({type:1,area:['500px','250px'],title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
			// 	yes:function(){
			// 		var index = layer.load();
			// 		$.post("<?php echo url('refund'); ?>",{orderid:orderid,reason:$('#tuireason').val()},function(res){
			// 			layer.close(index);
			// 			dialog(res.msg,res.status);
			// 			layer.close(refundLayer);
			// 			layer.close(detailLayer);
			// 			tableIns.reload()
			// 		})
			// 	}
			// })
			var index_load = layer.load();
			$.post("<?php echo url('refundinit'); ?>",{orderid:orderid},function(data) {
				tuikuandata = data;
				layer.close(index_load);
				layui.laytpl(refundtpl.innerHTML).render(data, function(html){
					var refundLayer = layer.open({type:1,title:'退款',area:'500px',content:html,resize:true,shadeClose:true,maxmin:true,btn: ['确定', '取消'],
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
							$.post("<?php echo url('refund'); ?>",{orderid:orderid,money: money,reason:reason,refundNum:refundNum},function(res){
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

		}else if(type == '查物流'){
			var index = layer.load();
			$.post("<?php echo url('getExpress'); ?>",{orderid:orderid},function(res){
				var expressdata = res.data
				layer.close(index);
				//货运托运
				if(data.order.freight_type==10){
					var html = '';
					html+='<div class="orderinfo" style="width:450px;margin-left:20px">';
					html+='	<div class="item">';
					html+='		<span class="t1">物流单照片</span>';
					html+='		<span class="t2"><img src="'+expressdata.pic+'" style="width:350px;height:auto"/></span>';
					html+='	</div>';
					html+='	<div class="item">';
					html+='		<span class="t1">发货人</span>';
					html+='		<span class="t2">'+expressdata.fhname+'</span>';
					html+='	</div>';
					html+='	<div class="item">';
					html+='		<span class="t1">发货地址</span>';
					html+='		<span class="t2">'+expressdata.fhaddress+'</span>';
					html+='	</div>';
					html+='	<div class="item">';
					html+='		<span class="t1">收货人</span>';
					html+='		<span class="t2">'+expressdata.shname+'</span>';
					html+='	</div>';
					html+='	<div class="item">';
					html+='		<span class="t1">收货地址</span>';
					html+='		<span class="t2">'+expressdata.shaddress+'</span>';
					html+='	</div>';
					html+='	<div class="item">';
					html+='		<span class="t1">备注</span>';
					html+='		<span class="t2">'+expressdata.remark+'</span>';
					html+='	</div>';
					html+='</div>';
					var logisticsLayer = layer.open({type:1,area:['500px','650px'],title:false,content:html,shadeClose:true,btn: ['修改', '关闭'],
						yes:function(){
							if(data.order.issource && (data.order.source == 'supply_zhenxin' || data.order.source == 'supply_yongsheng')){
								dialog('改订单不支持修改',0);
								return;
							}
							layer.close(logisticsLayer);
							doOperation(orderid,'发货');
						}
					})
					return ;
				}else{
					//普通快递
					layui.laytpl(logisticsTpl.innerHTML).render(res, function(html){
						var logisticsLayer = layer.open({type:1,title:'查看物流',content:html,area:"800px",shadeClose:true,btn: ['修改', '关闭'],
							yes:function(){
								if(data.order.issource && (data.order.source == 'supply_zhenxin' || data.order.source == 'supply_yongsheng')){
									dialog('改订单不支持修改',0);
									return;
								}
								layer.close(logisticsLayer);
								doOperation(orderid,'发货');
							}
						})
					})
				}
			});
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
							tableIns.reload()
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
		}else if(type == '尾款已支付'){
			layer.confirm('确定要改为尾款已支付吗?',{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('ispaybalance'); ?>",{orderid:orderid},function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(detailLayer);
					tableIns.reload()
				})
			})
		}else if(type == '改价'){
			layui.laytpl(changepricetpl.innerHTML).render({totalprice:data.order.totalprice}, function(html){
				var changepriceLayer = layer.open({type:1,title:false,content:html,shadeClose:true,btn: ['确定', '取消'],
					yes:function(){
						var index = layer.load();
						$.post("<?php echo url('changeprice'); ?>",{orderid:orderid,newprice:$('#changepricecontent').val()},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							layer.close(changepriceLayer);
							layer.close(detailLayer);
							tableIns.reload()
						})
					}
				})
			});
		}else if(type == '关闭订单'){
			layer.confirm('确定要关闭该订单吗?',{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('closeOrder'); ?>",{orderid:orderid},function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(detailLayer);
					tableIns.reload()
				})
			})
		}else if(type == '删除'){
			layer.confirm('确定要删除该订单吗?',{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('del'); ?>",{id:orderid},function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(detailLayer);
					tableIns.reload()
				})
			})
		}else if(type == '确认收货'){
			layer.confirm('确定要改为已完成状态吗?',{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('orderCollect'); ?>",{orderid:orderid},function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(detailLayer);
					tableIns.reload()
				})
			})
		}else if(type == '核销'){
			layer.confirm('确定要核销并改为已完成状态吗?',{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('orderHexiao'); ?>",{orderid:orderid},function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(detailLayer);
					tableIns.reload()
				})
			})
		}else if(type == '权益核销'){
			layer.confirm('确定要核销吗?当前剩余核销次数'+data.order.hexiao_num_remain+'次',{icon: 7, title:'操作确认'}, function(index){
				layer.close(index);
				var index = layer.load();
				$.post("<?php echo url('orderHexiao'); ?>",{orderid:orderid},function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					layer.close(detailLayer);
					tableIns.reload()
				})
			})
		}else if(type == '分销明细'){
			<?php if($view_order_fenxiao==1): ?>
			openmax('<?php echo url('Commission/record'); ?>/isopen/1/type/shop/orderid/'+orderid)
			<?php endif; ?>
		}else if(type == '分红明细'){
			<?php if($view_order_fenhong==1): ?>
			openmax('<?php echo url('Commission/fenhonglog'); ?>/isopen/1/type/shop/orderid/'+orderid)
			<?php endif; ?>
		}
		return false;
	}
	//批量打印
	function plshd(id,type=1){
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
		if(type == 1){
			openmax("<?php echo url('plshd'); ?>/ids/"+ids)
		}else if(type == 2){
			<?php if(getcustom('shop_shd_print2')): ?>
			openmax("<?php echo url('plshd2'); ?>/ids/"+ids)
			<?php endif; ?>
		}
	}
	//物流助手批量发货
	function plmiandanfh(){		
		var checkStatus = table.checkStatus('tabledata')
		var checkData = checkStatus.data; //得到选中的数据
		if(checkData.length === 0){
			return layer.msg('请选择数据');
		}
		var ids = [];
		for(var i=0;i<checkData.length;i++){
			ids.push(checkData[i]['id']);
		}
		var orderids = ids.join(",");
		
		layer.open({type:2,title:'创建运单',area:['900px','90%'],content:"<?php echo url('miandan/pladdorder'); ?>/ordertype/shop_order/orderid/"+orderids,scrollbar:false});
		// if(type == 1){
		// 	window.location.href = "<?php echo url('plshd'); ?>/ids/"+ids
		// }
		
	}
	//批量发货
	function plfh(){
		var html = '<div style="margin:20px auto;">';
		html+='<div class="layui-form" lay-filter="">';
		html+='<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
		html+='	<label class="layui-form-label" style="width:150px">快递公司</label>';
		html+='	<div class="layui-input-inline" style="width:180px">';
		html+='		<select id="plfh_express" name="plfh_express" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;">';
		<?php foreach($express_data as $k=>$v): ?>
		html+='			<option><?php echo $k; ?></option>';
		<?php endforeach; ?>
		html+='		</select>';
		html+='	</div>';
		html+='</div>';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label" style="width:150px">上传EXCEL文件</label>';
		html+='		<div class="layui-input-inline" style="width:300px">';
		html+='			<input type="text" name="plfh_file" id="plfh_file" class="layui-input">';
		html+='		</div>';
		html+='		<button style="float:left;" type="button" class="layui-btn layui-btn-primary uploadexcel" upload-input="plfh_file">上传</button>';
		//html+='		<div class="layui-form-mid layui-word-aux"></div>';
		html+='	</div>';
		html+='	<div class="layui-form-item">';
		html+='		<label class="layui-form-label" style="width:150px"></label>';
		html+='		<div class="layui-form-mid"> <a href="/static/demo_plfh.xls">点击下载查看导入格式</a></div>';
		html+='	</div>';
		html+='	<div class="layui-form-item" style="margin-top:30px">';
		html+='		<label class="layui-form-label" style="width:150px"></label>';
		html+='		<div class="layui-input-inline">';
		html+='			<button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit_excel">确定导入发货</button>';
		html+='		</div>';
		html+='	</div>';
		html+='</div>';
		html+='</div>'
		var plfhLayer = layer.open({type:1,area: ['600px', '460px'],title:'导入EXCEl文件',content:html,shadeClose:true})
		layui.form.render();
		//文件上传
		layui.upload.render({
			elem: '.uploadexcel',
			accept:'file',
			url:"<?php echo url('upload/index'); ?>",
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
			var index= layer.load();
			$.post("<?php echo url('plfh'); ?>",field,function(res){
				layer.close(index);
				dialog(res.msg,res.status);
				layer.close(plfhLayer);
				tableIns.reload();
			})
		})
	}
		//批量发货-云仓
		function plfhyc(){
			var html = '<div style="margin:20px auto;">';
			html+='<div class="layui-form" lay-filter="">';
			html+='<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';

				html+='</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:150px">上传EXCEL文件：</label>';
				html+='		<div class="layui-input-inline" style="width:300px">';
				html+='			<input type="text" name="plfhyc_file" id="plfhyc_file" class="layui-input">';
				html+='		</div>';
				html+='		<button style="float:left;" type="button" class="layui-btn layui-btn-primary uploadexcel2" upload-input="plfhyc_file">上传</button>';
				//html+='		<div class="layui-form-mid layui-word-aux"></div>';
				html+='	</div>';
				html+='	<div class="layui-form-item">';
				html+='		<label class="layui-form-label" style="width:150px"></label>';
				html+='		<div class="layui-form-mid"> <a href="/static/demo_plfhyc.xlsx">点击下载查看导入格式</a></div>';
				html+='	</div>';
				html+='	<div class="layui-form-item" style="margin-top:30px">';
				html+='		<label class="layui-form-label" style="width:150px"></label>';
				html+='		<div class="layui-input-inline">';
				html+='			<button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit_excel2">确定导入发货</button>';
				html+='		</div>';
				html+='	</div>';
				html+='</div>';
				html+='</div>'
				var plfhLayer = layer.open({type:1,area: ['600px', '460px'],title:'导入EXCEl文件',content:html,shadeClose:true})
				layui.form.render();
				//文件上传
				layui.upload.render({
					elem: '.uploadexcel2',
					accept:'file',
					url:"<?php echo url('upload/index'); ?>",
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
				layui.form.on('submit(submit_excel2)', function(obj){
					var field = obj.field;
					var index= layer.load();
					$.post("<?php echo url('plfhyc'); ?>",field,function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(plfhLayer);
						tableIns.reload();
					})
				})
			}
			<?php if(getcustom('express_maiyatian')): ?>
			function showmyt(orderid){
				var go_url = "<?php echo url('Peisong/mytprice'); ?>/orderid/"+orderid+"/type/shop_order";
				layer.open({type:2,shadeClose:true,area:['1100px', '800px'],'title':'麦芽田配送',content:go_url})
			}
			<?php endif; if(getcustom('wx_express_intracity')): ?>
			function showxtc(orderid){
				var go_url = "<?php echo url('WxExpressIntracity/wxtcprice'); ?>/orderid/"+orderid+"/type/shop_order";
				layer.open({type:2,shadeClose:true,area:['800px', '500px'],'title':'微信同城配送',content:go_url,
					yes: function (index, layero) {
						tableIns.reload();
					},
					cancel: function (index, layero) {
						tableIns.reload();
					},
					end: function (index, layero) {
						tableIns.reload();
					}
				})
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
					<span class="t2"><input type="text" name="refund_checkremark" value="{{d.refund_checkremark ? d.refund_checkremark : ''}}" style="height:30px"/></span>
				</div>
		</div>
		</div>
	</script>

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
					<div class="product" style='border-bottom:1px #e6e6e6 solid;padding-bottom: 10px'>
					{{#  layui.each(item.oglist, function(index2, item2){ }}
					<div class="content">
						<div>
							<img src="{{item2.pic}}"/>
						</div>
						<div class="detail">
							<span class="t1">{{item2.name}}</span>
							<span class="t2">{{item2.ggname}}</span>
							{{# if(item2.refund_num>0){ }}
							<div class="t3"><span class="x1 flex1">￥{{item2.sell_price}}</span><span class="x2">×{{item2.num}}</span><span class="x2">[退×{{item2.refund_num}}]</span></div>
							{{# }else{ }}
							<div class="t3"><span class="x1 flex1">￥{{item2.sell_price}}</span><span class="x2">×{{item2.num}}</span></div>
							{{# } }}
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

	<script id="sendExpressTpl" type="text/html">
	<div id="sendExpressDiv" style="margin:0 20px" class="layui-form" lay-filter="">
		<div id="sendExpressContent">
			{{# if(d.order.express_content){ }}
			{{#  layui.each(d.order.express_content, function(index, item){ }}
			<div class="sendExpressContentadd">
			<div class="layui-form-item" style="margin-top:40px;">
				<label class="layui-form-label" style="width:60px">快递公司</label>
				<div class="layui-input-inline" style="width:180px">
					<select name="send_express[]" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;">
						<?php foreach($express_data as $k=>$v): ?>
						<option {{# if(item.express_com=='<?php echo $k; ?>'){ }}selected{{# } }}><?php echo $k; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				{{# if(index > 0){ }}
				<div class="layui-form-mid" onclick="$(this).parent().parent().remove()" style="cursor:pointer">移除</div>
				{{# } }}
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label" style="width:60px">快递单号</label>
				<div class="layui-input-inline" style="width:180px">
					<input type="text" name="send_express_no[]" class="layui-input" value="{{# if(item.express_no){ }}{{item.express_no}}{{# } }}">
				</div>
			</div>
			{{# if((d.oglist).length > 1){ }}
			<div class="layui-form-item">
				<label class="layui-form-label" style="width:60px">发货商品</label>
				<div class="layui-input-inline send_express_ogids" style="width:400px">
					{{#  layui.each(d.oglist, function(index2, item2){ }}
						{{# if(item2.num > item2.refund_num){ }}
							<input type="checkbox" class="layui-input" value="{{item2.id}}" {{# if($.inArray(item2.id+'',item.express_ogids) > -1){ }}checked{{# } }} title="{{item2.name}}({{item2.ggname}})" lay-skin="primary">
						{{# } }}
					{{# }); }}
				</div>
			</div>
			{{# } }}
			<hr>
			</div>

			{{# }); }}
			{{# }else{  }}
			<div class="layui-form-item" style="margin-top:40px;">
				<label class="layui-form-label" style="width:60px">快递公司</label>
				<div class="layui-input-inline" style="width:180px">
					<select name="send_express[]" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;">
						<?php foreach($express_data as $k=>$v): ?>
						<option {{# if(d.order.express_com=='<?php echo $k; ?>'){ }}selected{{# } }}><?php echo $k; ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="layui-form-item">
				<label class="layui-form-label" style="width:60px">快递单号</label>
				<div class="layui-input-inline" style="width:180px">
					<input type="text" name="send_express_no[]" class="layui-input" value="{{# if(d.order.express_no){ }}{{d.order.express_no}}{{# } }}">
				</div>
			</div>
			{{# if((d.oglist).length > 1){ }}
			<div class="layui-form-item">
				<label class="layui-form-label" style="width:60px">发货商品</label>
				<div class="layui-input-inline send_express_ogids" style="width:400px">
					{{#  layui.each(d.oglist, function(index2, item2){ }}
						{{# if(item2.num > item2.refund_num){ }}
							<input type="checkbox" value="{{item2.id}}" {{# if($.inArray(item2.id+'',d.order.express_ogids) > -1){ }}checked{{# } }} title="{{item2.name}}({{item2.ggname}})" lay-skin="primary">
						{{# } }}
					{{# }); }}
				</div>
			</div>
			{{# } }}
			<hr>
			{{# } }}
		</div>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:void(0)" onclick="addsendExpressContent()">新增一个</a>
	</div>
	</script>
	<script>
	function addsendExpressContent(){
		console.log(orderdata)
		var addhtml = '<div class="sendExpressContentadd">';
		addhtml += '	<div class="layui-form-item" style="margin-top:40px;">';
		addhtml += '		<label class="layui-form-label" style="width:60px">快递公司</label>';
		addhtml += '		<div class="layui-input-inline" style="width:180px">';
		addhtml += '			<select name="send_express[]" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;">';
						<?php foreach($express_data as $k=>$v): ?>
		addhtml += '				<option><?php echo $k; ?></option>';
						<?php endforeach; ?>
		addhtml += '			</select>';
		addhtml += '		</div>';
		addhtml += '		<div class="layui-form-mid" onclick="$(this).parent().parent().remove()" style="cursor:pointer">移除</div>';
		addhtml += '	</div>';
		addhtml += '	<div class="layui-form-item">';
		addhtml += '		<label class="layui-form-label" style="width:60px">快递单号</label>';
		addhtml += '		<div class="layui-input-inline" style="width:180px">';
		addhtml += '			<input type="text" name="send_express_no[]" class="layui-input" value="">';
		addhtml += '		</div>';
		addhtml += '	</div>';
		if((orderdata.oglist).length > 1){
			addhtml += '	<div class="layui-form-item">';
			addhtml += '		<label class="layui-form-label" style="width:60px">发货商品</label>';
			addhtml += '		<div class="layui-input-inline send_express_ogids" style="width:400px">';
			layui.each(orderdata.oglist, function(index2, item2){
				if(item2.num>item2.refund_num){
					addhtml += '	<input type="checkbox" value="'+item2.id+'" title="'+item2.name+'('+item2.ggname+')" lay-skin="primary">';
				}
			});
			addhtml += '		</div>';
			addhtml += '	</div>';
		}
		addhtml += '	<hr>';
		addhtml += '</div>';
		$('#sendExpressContent').append(addhtml);
		layui.form.render();
	}
	</script>

	<script id="remarktpl" type="text/html">
	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">
		<label class="layui-form-label" style="width:40px">备注</label>
		<div class="layui-input-inline" style="width:180px">
			<input type="text" id="remarkcontent" class="layui-input" value="{{d.remark?d.remark : ''}}">
		</div>
	</div>
	</script>
	<script id="changepricetpl" type="text/html">
	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">
		<label class="layui-form-label" style="width:40px">价格</label>
		<div class="layui-input-inline" style="width:180px">
			<input type="text" id="changepricecontent" class="layui-input" value="{{d.totalprice?d.totalprice : ''}}">
		</div>
	</div>
	</script>

	<script id="detailtpl" type="text/html">
	<div class="orderdetail">
		<div class="address">
			<div class="img">
				<img src="/static/admin/img/address.png" class="buy-addimg"/>
			</div>
			<div class="info">
				<span class="t1">{{d.order.linkman}}{{# if(d.order.company){ }} {{d.order.company}} {{# } }} {{d.order.tel}}</span>
				<span class="t2">{{d.order.area}}{{d.order.address}}</span>
			</div>
		</div>
		<?php if(getcustom('product_thali')): ?>
		{{# if(d.shopset.product_shop_school == 1){ }}
		<div class="address">
			<div class="img">

			</div>
			<div class="info">
				<span class="t1">学生姓名：{{d.order.product_thali_student_name}}</span>
				<span class="t2">学校信息：{{d.order.product_thali_school}}</span>
			</div>
		</div>
		{{# } }}
		<?php endif; ?>
		{{# if(d.order.send_time && d.order.freight_type!=3 && d.order.freight_type!=4){ }}
		<div class="express">
			<div class="f1"><i class="fa fa-truck"></i></div>
			<div class="f2">
				{{# if(d.order.express_content){ }}
				{{#  layui.each(d.order.express_content, function(index, item){ }}
				<span class="log-te1">{{item.express_com}}：{{item.express_no}}</span>
				{{# }); }}
				{{# }else{ }}
				<span class="log-te1">快递公司：{{d.order.express_com}}</span>
				<span class="log-te1">快递单号：{{d.order.express_no}}</span>
				{{# } }}
			</div>
		</div>
		{{# } }}

		{{# if(d.order.send_time && (d.order.freight_type==3 || d.order.freight_type==4)){ }}
		<div class="express">
			<div class="f1"><i class="fa fa-file-text"></i></div>
			<div class="f2"><pre>{{d.order.freight_content}}</pre></div>
		</div>
		{{# } }}

		<div class="product">
			{{#  layui.each(d.oglist, function(index, item){ }}
			<div class="box">
				<div class="content">
					<div>
						<img src="{{item.pic}}"/>
					</div>
					<div class="detail">
						<span class="t1">{{item.name}}</span>
						<span class="t2">{{item.ggname}}</span>
						{{#  if(item.is_quanyi==1){ }}
						<button class="table-btn" onclick="quanyihexiao({{item.id}},{{item.hexiao_num_remain}})" style="width: 100px;position: absolute;right: 0;">权益核销</button>
						{{# } }}
						{{# if(item.refund_num>0){ }}
						<div class="t3"><span class="x1 flex1">￥{{item.sell_price}}</span><span class="x2">×{{item.num}}</span><span class="x2">[退×{{item.refund_num}}]</span></div>
						{{# }else{ }}
						<div class="t3"><span class="x1 flex1">￥{{item.sell_price}}</span><span class="x2">×{{item.num}}</span></div>
						{{# } }}
						<?php if(getcustom('product_service_fee') && $shopset['show_shd_remark'] == 1): ?>
							{{# if(item.shd_remark){ }}
								<div class="t2" style="margin-top: 5px;height: auto">备注：{{item.shd_remark}}</div>
							{{# } }}
						<?php endif; if(getcustom('freeze_money')): ?>
						{{# if(item.freezemoney_price > 0){ }}
						+{{item.freezemoney_price}}<?php echo t('冻结资金'); ?>
						{{# } }}
						<?php endif; ?>
					</div>
					<?php if(getcustom('shop_product_form')): ?>
					{{#  if(item.form_orderid >0 ){ }}
					<div style="display: flex;align-items: center;padding-left: 10px"><button class="layui-btn layui-btn-sm " onclick="openmax('<?php echo url('Form/record'); ?>/id/{{item.form_orderid}}/formid/{{item.formid}}')">商品表单</button></div>
					{{# } }}
					<?php endif; ?>
				</div>
				{{# if(item.glassrecord){ }}
				<div class="glassitem">
					<div>
						{{item.glassrecord.name}}
						{{#  if(item.glassrecord.nickname){ }}
						{{item.glassrecord.nickname}}
						{{# } }}
						{{#  if(item.glassrecord.check_time){ }}
						{{item.glassrecord.check_time}}
						{{# } }}
						{{#  if(item.glassrecord.type==1){ }}
						近视
						{{# }else if(item.glassrecord.type==2){ }}
						远视
						{{# }else{ }}
						远近两用
						{{# } }}
						{{#  if(item.glassrecord.double_ipd==0 && item.glassrecord.ipd){ }}
						 PD{{item.glassrecord.ipd}}
						{{# } }}
						{{#  if(item.glassrecord.double_ipd==1){ }}
						PD R{{item.glassrecord.ipd_right}} L{{item.glassrecord.ipd_left}}
						{{# } }}
					</div>
					<div>
						R
						{{item.glassrecord.degress_right}}
						/
						{{#  if(item.glassrecord.ats_right){ }}
						{{item.glassrecord.ats_right}}
						{{# }else{ }}
						0.00
						{{# } }}

						{{#  if(item.glassrecord.ats_zright){ }}
						 *{{item.glassrecord.ats_zright}}
						{{# }else{ }}
						 *0
						{{# } }}
						{{#  if(item.glassrecord.type==3 && item.glassrecord.add_right){ }}
						 ADD+{{item.glassrecord.add_right}}
						{{# } }}
					</div>
					<div>
						L
						{{item.glassrecord.degress_left}}
						/
						{{#  if(item.glassrecord.ats_left){ }}
						{{item.glassrecord.ats_left}}
						{{# }else{ }}
						0.00
						{{# } }}

						{{#  if(item.glassrecord.ats_zleft){ }}
						*{{item.glassrecord.ats_zleft}}
						{{# }else{ }}
						*0
						{{# } }}
						{{#  if(item.glassrecord.type==3 && item.glassrecord.add_left){ }}
						ADD+{{item.glassrecord.add_left}}
						{{# } }}
					</div>
					{{#  if(item.glassrecord.remark){ }}
						<div>备注：{{item.glassrecord.remark}}</div>
					{{# } }}
				</div>
				{{# } }}
			</div>
			{{# }); }}
			<?php if(getcustom('ciruikang_fenxiao')): ?>
				{{# if(d.order.crk_givenum>0){ }}
					<div style="padding-top:0px;color:#f60;line-height:35px">+随机赠送{{d.order.crk_givenum}}件</div>
				{{# } }}
		<?php endif; ?>
		</div>
		
		{{#  if((d.order.formdata).length>0){ }}
		<div class="orderinfo">
			{{#  layui.each(d.order.formdata, function(index, item){ }}
			<div class="item" style="align-items: center">
				<span class="t1">{{item[0]}}</span>
				{{#  if(item[2]=='upload'){ }}
				<span class="t2"><img src="{{item[1]}}" style="width:150px;height:auto" onclick="preview(this)"/></span>
				{{# }
				else if(item[2]=='upload_pics'){ }}
					{{#  layui.each(item[1], function(index1, item1){ }}
					<span class="t2"><img src="{{item1}}" style="width:150px;height:150px" onclick="preview(this)"/></span>
					{{# }); }}
				{{# }
				else{ }}
				<span class="t2">{{item[1]}}</span>
				{{# } }}
			</div>
			{{# }); }}
		</div>
		{{# } }}
		
		{{#  if(d.order.message || d.order.remark || d.order.field1 || d.order.field2 || d.order.field3 || d.order.field4 || d.order.field5){ }}
		<div class="orderinfo">
			{{#  if(d.order.message){ }}
			<div class="item">
				<span class="t1">客户备注</span>
				<span class="t2 red">{{d.order.message ? d.order.message : '无'}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.field1){ }}
			<div class="item">
				<span class="t1">{{d.order.field1data[0]}}</span>
				<span class="t2 red">{{d.order.field1data[1]}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.field2){ }}
			<div class="item">
				<span class="t1">{{d.order.field2data[0]}}</span>
				<span class="t2 red">{{d.order.field2data[1]}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.field3){ }}
			<div class="item">
				<span class="t1">{{d.order.field3data[0]}}</span>
				<span class="t2 red">{{d.order.field3data[1]}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.field4){ }}
			<div class="item">
				<span class="t1">{{d.order.field4data[0]}}</span>
				<span class="t2 red">{{d.order.field4data[1]}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.field5){ }}
			<div class="item">
				<span class="t1">{{d.order.field5data[0]}}</span>
				<span class="t2 red">{{d.order.field5data[1]}}</span>
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
				<span class="t1">订单编号</span>
				<span class="t2">{{d.order.ordernum}}</span>
			</div>
			<div class="item">
				<span class="t1">下单时间</span>
				<span class="t2">{{date('Y-m-d H:i:s',d.order.createtime)}}</span>
			</div>
			{{#  if(d.order.status>0 && d.order.paytime){ }}
			<div class="item">
				<span class="t1">支付时间</span>
				<span class="t2">{{date('Y-m-d H:i:s',d.order.paytime)}}</span>
			</div>
			<div class="item">
				<span class="t1">支付方式</span>
				<span class="t2">{{d.order.paytype}}</span>
			</div>
			{{# } }}
			
			{{#  if(d.order.duihuan_cardno){ }}
			<div class="item">
				<span class="t1">兑换卡卡号</span>
				<span class="t2">{{d.order.duihuan_cardno}}</span>
			</div>
			{{# } }}

			{{#  if(d.order.status>1 && d.order.send_time){ }}
			<div class="item">
				<span class="t1">发货时间</span>
				<span class="t2">{{date('Y-m-d H:i:s',d.order.send_time)}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.status==3 && d.order.collect_time){ }}
			<div class="item">
				<span class="t1">收货时间</span>
				<span class="t2">{{date('Y-m-d H:i:s',d.order.collect_time)}}</span>
			</div>
			{{# } }}
		</div>
		<div class="orderinfo">
			<div class="item">
				<span class="t1">商品金额</span>
				<span class="t2 red">
					¥{{d.order.product_price}}
					<?php if(getcustom('product_service_fee') && ($auth_data=='all' || in_array('service_fee_switch',$auth_data))): ?>
						{{# if(d.order.service_fee > 0){ }}
							+{{d.order.service_fee}}<?php echo t('服务费'); ?>
						{{# } }}
					<?php endif; if(getcustom('freeze_money')): ?>
						{{# if(d.order.total_freezemoney_price > 0){ }}
							+{{d.order.total_freezemoney_price}}<?php echo t('冻结资金'); ?>
						{{# } }}
					<?php endif; ?>
				</span>
			</div>
			{{#  if(d.order.weight_price> 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('包装费'); ?></span>
				<span class="t2 red">￥{{d.order.weight_price}}</span>
			</div>
			{{# } }}
		
			<div class="item">
				<span class="t1">配送方式</span>
				<span class="t2">{{d.order.freight_text}}</span>
			</div>
			<div class="item">
				<span class="t1">配送费/服务费</span>
				<span class="t2 red">￥{{d.order.freight_price}}</span>
			</div>
			{{#  if(d.order.up_floor_fee> 0){ }}
			<div class="item">
				<span class="t1">上楼费</span>
				<span class="t2 red">￥{{d.order.up_floor_fee}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.freight_type==11){ }}
			<div class="item">
				<span class="t1">发货地址</span>
				<span class="t2">{{d.order.freight_content.send_address}} - {{d.order.freight_content.send_tel}}</span>
			</div>
			<div class="item">
				<span class="t1">收货地址</span>
				<span class="t2">{{d.order.freight_content.receive_address}} - {{d.order.freight_content.receive_tel}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.freight_time){ }}
			<div class="item">
				<span class="t1">{{d.order.freight_type!=1?'配送':'提货'}}时间</span>
				<span class="t2">{{d.order.freight_time}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.invoice_money > 0){ }}
			<div class="item">
				<span class="t1">发票费用</span>
				<span class="t2 red">¥{{d.order.invoice_money}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.leveldk_money > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('会员'); ?>折扣</span>
				<span class="t2 red">-¥{{d.order.leveldk_money}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.discount_money_admin > 0){ }}
			<div class="item">
				<span class="t1">管理员优惠</span>
				<span class="t2 red">-¥{{d.order.discount_money_admin}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.manjian_money > 0){ }}
			<div class="item">
				<span class="t1">满减活动</span>
				<span class="t2 red">-¥{{d.order.manjian_money}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.coupon_money > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('优惠券'); ?>抵扣</span>
				<span class="t2 red">-¥{{d.order.coupon_money}}</span>
			</div>
			{{#  if(d.couponnames){ }}
			<div class="item">
				<span class="t1"><?php echo t('优惠券'); ?>名称</span>
				<span class="t2">{{d.couponnames}}</span>
			</div>
			{{# } }}
			{{# } }}
			<?php if(getcustom('deposit')): ?>
			{{#  if(d.order.water_coupon_money > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('电子水票'); ?>抵扣</span>
				<span class="t2 red">-¥{{d.order.water_coupon_money}}</span>
			</div>
			{{# } }}
			<?php endif; ?>
			{{#  if(d.order.scoredk_money > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('积分'); ?>抵扣</span>
				<span class="t2 red">-¥{{d.order.scoredk_money}}</span>
			</div>
			{{# } }}
			<?php if(getcustom('money_dec')): ?>
			{{#  if(d.order.dec_money > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('余额'); ?>抵扣</span>
				<span class="t2 red">-¥{{d.order.dec_money}}</span>
			</div>
			{{# } }}
			<?php endif; ?>
			{{#  if(d.order.discount_rand_money > 0){ }}
			<div class="item">
				<span class="t1">随机立减</span>
				<span class="t2 red">-¥{{d.order.discount_rand_money}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.silvermoneydec && d.order.silvermoneydec > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('银值'); ?>抵扣</span>
				<span class="t2 red">-¥{{d.order.silvermoneydec}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.goldmoneydec && d.order.goldmoneydec > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('金值'); ?>抵扣</span>
				<span class="t2 red">-¥{{d.order.goldmoneydec}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.dedamount_dkmoney && d.order.dedamount_dkmoney > 0){ }}
			<div class="item">
				<span class="t1">抵扣金抵扣</span>
				<span class="t2 red">-¥{{d.order.dedamount_dkmoney}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.shopscoredk_money > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('产品积分'); ?>抵扣</span>
				<span class="t2 red">-¥{{d.order.shopscoredk_money}}</span>
			</div>
			{{# } }}
			<div class="item">
				<span class="t1">实付款</span>
				<span class="t2 red">
				<?php if(getcustom('price_dollar') && d.order.usd_totalprice>0): ?>
				${{d.order.usd_totalprice}}
				<?php endif; ?>
				¥{{d.order.totalprice}}
				<?php if(getcustom('product_service_fee') && ($auth_data=='all' || in_array('service_fee_switch',$auth_data))): ?>
					{{# if(d.order.service_fee > 0){ }}
						+{{d.order.service_fee_money}}<?php echo t('服务费'); ?>
					{{# } }}
				<?php endif; if(getcustom('freeze_money')): ?>
						{{# if(d.order.total_freezemoney_price > 0){ }}
							+{{d.order.total_freezemoney_price}}<?php echo t('冻结资金'); ?>
						{{# } }}
					<?php endif; ?>
				</span>
			</div>
			<?php if(getcustom('pay_money_combine')): ?>
			{{#  if(d.order.combine_money && d.order.combine_money > 0){ }}
			<div class="item">
				<span class="t1"><?php echo t('余额'); ?>已付</span>
				<span class="t2 red">-¥{{d.order.combine_money}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.paytypeid == 2 && d.order.combine_wxpay && d.order.combine_wxpay > 0){ }}
			<div class="item">
				<span class="t1">微信已付</span>
				<span class="t2 red">-¥{{d.order.combine_wxpay}}</span>
			</div>
			{{# } }}
			{{#  if((d.order.paytypeid == 3 || (d.order.paytypeid>=302 && d.order.paytypeid<=330)) && d.order.combine_alipay && d.order.combine_alipay > 0){ }}
			<div class="item">
				<span class="t1">支付宝已付</span>
				<span class="t2 red">-¥{{d.order.combine_alipay}}</span>
			</div>
			{{# } }}
			<?php endif; ?>
		
			<div class="item">
				<span class="t1">订单状态</span>
				{{#  if(d.order.status==0){ }}
				<span class="t2" style="color:#ff8758">未付款</span>
				{{# } }}
				{{#  if(d.order.status==1 && d.order.paytypeid=='4'){ }}
				<span class="t2" style="color:#008000">待发货</span>
				{{# } }}
				{{#  if(d.order.status==1 && d.order.paytypeid!='4'){ }}
				<span class="t2" style="color:#008000">已支付</span>
				{{# } }}
				{{#  if(d.order.status==2){ }}
				<span class="t2" style="color:#ff4246">已发货</span>
				{{# } }}
				{{#  if(d.order.status==3){ }}
				<span class="t2" style="color:#999">已收货</span>
				{{# } }}
				{{#  if(d.order.status==4){ }}
				<span class="t2" style="color:#bbb">已关闭</span>
				{{# } }}
				<?php if($mendian_upgrade): ?>
					{{#  if(d.order.status==8){ }}
					<span class="t2" style="color:#bbb">待提货</span>
					{{# } }}
				<?php endif; ?>
			</div>
			{{#  if(d.order.balance_price>0){ }}
			<div class="item">
				<span class="t1">尾款金额</span>
				<span class="t2 red">¥{{d.order.balance_price}}</span>
			</div>
			<div class="item">
				<span class="t1">尾款状态</span>
				{{#  if(d.order.balance_pay_status==1){ }}
				<span class="t2">已支付</span>
				{{# } }}
				{{#  if(d.order.balance_pay_status==0){ }}
				<span class="t2">未支付</span>
				{{# } }}
			</div>
			{{# } }}
			{{#  if(d.order.refund_status>0){ }}
			<div class="item">
				<span class="t1">退款状态</span>
				{{#  if(d.order.refund_status==1){ }}
				<span class="t2 red">审核中,¥{{d.order.refund_money}}</span>
				{{# } }}
				{{#  if(d.order.refund_status==2){ }}
				<span class="t2 red">已退款,¥{{d.order.refund_money}}</span>
				{{# } }}
				{{#  if(d.order.refund_status==3){ }}
				<span class="t2 red">已驳回,¥{{d.order.refund_money}}</span>
				{{# } }}
			</div>
			{{# } }}
			{{#  if(d.order.refund_checkremark){ }}
			<div class="item">
				<span class="t1">审核备注</span>
				<span class="t2 red">{{d.order.refund_checkremark}}</span>
			</div>
			{{# } }}
			{{#  if(d.order.poshopid && d.order.poshopid>0){ }}
			<div class="item">
				<span class="t1">所购店铺</span>
				<span class="t2"><img src="{{d.order.poshop.pic}}" style="width:40px"/> {{d.order.poshop.name}} (ID:{{d.order.poshopid}})</span>
			</div>
			{{# } }}
		</div>
		{{#  if(d.order.checkmemid > 0){ }}
		<div class="orderinfo">
			<div class="item">
				<span class="t1">所选<?php echo t('会员'); ?></span>
				<span class="t2"><img src="{{d.checkmember.headimg}}" style="width:30px"/> {{d.checkmember.nickname}}</span>
			</div>
		</div>
		{{# } }}
		{{#  if(d.order.isdygroupbuy){ }}
		<div class="orderinfo">
			<div class="item">
				<span class="t1">抖音团购券信息</span>
				<span class="t2">{{d.order.dyorderids}}</span>
			</div>
		</div>
		{{# } }}
		{{#  if(d.comdata.parent1.mid){ }}
		<div class="orderinfo">
			<div class="item">
				<span class="t1">一级<?php echo t('佣金'); ?></span>
				<span class="t2"><img src="{{d.comdata.parent1.headimg}}" style="width:30px"/> {{d.comdata.parent1.nickname}}{{#  if(d.comdata.parent1.level_name){ }}[{{d.comdata.parent1.level_name}}]{{# } }}
					<span style="color:#ff8758">
						{{d.comdata.parent1.money}}元 {{#  if(d.comdata.parent1.score){ }}+{{d.comdata.parent1.score}}<?php echo t('积分'); ?>{{# } }}
						<?php if(getcustom('commission_money_percent')): ?>
							{{#  if(d.comdata.parent1.moneypercent){ }}
							+{{d.comdata.parent1.moneypercent}}<?php echo t('余额'); ?>
							{{# } }}
						<?php endif; if(getcustom('commission_xianjin_percent')): ?>
							{{#  if(d.comdata.parent1.xianjinpercent){ }}
							+{{d.comdata.parent1.xianjinpercent}}<?php echo t('现金'); ?>
							{{# } }}
						<?php endif; if(getcustom('yx_buyer_subsidy')): ?>
							{{#  if(d.comdata.parent1.subsidyscore){ }}
							+{{d.comdata.parent1.subsidyscore}}<?php echo t('返现积分'); ?>
							{{# } }}
						<?php endif; if(getcustom('yx_farm')): ?>
							{{#  if(d.comdata.parent1.farmseed){ }}
							+{{d.comdata.parent1.farmseed}}<?php echo $farm_textset['种子']; ?>
							{{# } }}
						<?php endif; ?>
					</span>
				</span>
			</div>
			<div class="item">
				<span class="t1">二级<?php echo t('佣金'); ?></span>
				<span class="t2"><img src="{{d.comdata.parent2.headimg}}" style="width:30px"/> {{d.comdata.parent2.nickname}}{{#  if(d.comdata.parent2.level_name){ }}[{{d.comdata.parent2.level_name}}]{{# } }}
					<span style="color:#ff8758">
						{{d.comdata.parent2.money}}元 {{#  if(d.comdata.parent2.score){ }}+{{d.comdata.parent2.score}}<?php echo t('积分'); ?>{{# } }}
						<?php if(getcustom('commission_money_percent')): ?>
							{{#  if(d.comdata.parent2.moneypercent){ }}
							+{{d.comdata.parent2.moneypercent}}<?php echo t('余额'); ?>
							{{# } }}
						<?php endif; if(getcustom('commission_xianjin_percent')): ?>
							{{#  if(d.comdata.parent2.xianjinpercent){ }}
							+{{d.comdata.parent2.xianjinpercent}}<?php echo t('现金'); ?>
							{{# } }}
						<?php endif; if(getcustom('yx_buyer_subsidy')): ?>
							{{#  if(d.comdata.parent2.subsidyscore){ }}
							+{{d.comdata.parent2.subsidyscore}}<?php echo t('返现积分'); ?>
							{{# } }}
						<?php endif; if(getcustom('yx_farm')): ?>
							{{#  if(d.comdata.parent2.farmseed){ }}
							+{{d.comdata.parent2.farmseed}}<?php echo $farm_textset['种子']; ?>
							{{# } }}
						<?php endif; ?>
					</span>
				</span>
			</div>
			<div class="item">
				<span class="t1">三级<?php echo t('佣金'); ?></span>
				<span class="t2"><img src="{{d.comdata.parent3.headimg}}" style="width:30px"/> {{d.comdata.parent3.nickname}}{{#  if(d.comdata.parent3.level_name){ }}[{{d.comdata.parent3.level_name}}]{{# } }}
					<span style="color:#ff8758">
						{{d.comdata.parent3.money}}元  {{#  if(d.comdata.parent3.score){ }}+{{d.comdata.parent3.score}}<?php echo t('积分'); ?>{{# } }}
						<?php if(getcustom('commission_money_percent')): ?>
							{{#  if(d.comdata.parent3.moneypercent){ }}
							+{{d.comdata.parent3.moneypercent}}<?php echo t('余额'); ?>
							{{# } }}
						<?php endif; if(getcustom('commission_xianjin_percent')): ?>
							{{#  if(d.comdata.parent3.xianjinpercent){ }}
							+{{d.comdata.parent3.xianjinpercent}}<?php echo t('现金'); ?>
							{{# } }}
						<?php endif; if(getcustom('yx_buyer_subsidy')): ?>
							{{#  if(d.comdata.parent3.subsidyscore){ }}
							+{{d.comdata.parent3.subsidyscore}}<?php echo t('返现积分'); ?>
							{{# } }}
						<?php endif; if(getcustom('yx_farm')): ?>
							{{#  if(d.comdata.parent3.farmseed){ }}
							+{{d.comdata.parent3.farmseed}}<?php echo $farm_textset['种子']; ?>
							{{# } }}
						<?php endif; ?>
					</span>
				</span>
			</div>
		</div>
		{{# } }}
		{{#  if(d.comdata.parent_pj1.mid || d.comdata.parent_pj2.mid || d.comdata.parent_pj3.mid){ }}
		<div class="orderinfo">
			<div class="item">
				<span class="t1">一级平级<?php echo t('佣金'); ?></span>
				<span class="t2"><img src="{{d.comdata.parent_pj1.headimg}}" style="width:30px"/> {{d.comdata.parent_pj1.nickname}}{{#  if(d.comdata.parent_pj1.level_name){ }}[{{d.comdata.parent_pj1.level_name}}]{{# } }}
					<span style="color:#ff8758">
						{{d.comdata.parent_pj1.money}}元
					</span>
				</span>
			</div>
			<div class="item">
				<span class="t1">二级平级<?php echo t('佣金'); ?></span>
				<span class="t2"><img src="{{d.comdata.parent_pj2.headimg}}" style="width:30px"/> {{d.comdata.parent_pj2.nickname}}{{#  if(d.comdata.parent_pj2.level_name){ }}[{{d.comdata.parent_pj2.level_name}}]{{# } }}
					<span style="color:#ff8758">
						{{d.comdata.parent_pj2.money}}元
					</span>
				</span>
			</div>
			<div class="item">
				<span class="t1">三级平级<?php echo t('佣金'); ?></span>
				<span class="t2"><img src="{{d.comdata.parent_pj3.headimg}}" style="width:30px"/> {{d.comdata.parent_pj3.nickname}}{{#  if(d.comdata.parent_pj3.level_name){ }}[{{d.comdata.parent_pj3.level_name}}]{{# } }}
					<span style="color:#ff8758">
						{{d.comdata.parent_pj3.money}}元
					</span>
				</span>
			</div>
		</div>
		{{# } }}
	</div>
	</script>
	<script>
		function fenqidetail(id){
			var index = layer.load();
			$.post("<?php echo url('getdetail'); ?>",{orderid:id},function(data){
				layer.close(index);
				var html = '';
				layui.laytpl(fenqitpl.innerHTML).render(data, function(html){
					detailLayer = layer.open({type:1,title:'分期详情',area:'500px',content:html,resize:true,shadeClose:true});
				});
			})
		}
	</script>
	<script id="fenqitpl" type="text/html">
		<div class="orderdetail">
		<div class="orderinfo">
			{{#  layui.each(d.order.fenqi_data, function(index, item){ }}
			<div class="item">
				<span class="t1">{{item.fenqi_num}}期</span>
				<span class="t2"></span>
			</div>
			<div class="item">
				<span class="t1">支付金额</span>
				<span class="t2">{{item.fenqi_money}}元</span>
			</div>
			<div class="item">
				<span class="t1">赠送数量</span>
				<span class="t2">{{item.fenqi_give_num}}张</span>
			</div>
			<div class="item">
				<span class="t1">分销赠送数量</span>
				<span class="t2">{{item.fenqi_fx_num}}张</span>
			</div>
			<div class="item">
				<span class="t1">状态</span>
				{{#  if(item.status==1){ }}
				<span class="t2 red">已付款</span>
				{{# } }}
				{{#  if(item.status==0){ }}
				<span class="t2 red">未支付</span>
				{{# } }}
				{{#  if(item.status==2){ }}
				<span class="t2 red">已过期</span>
				{{# } }}
			</div>
			{{# }); }}
		</div>
	</div>
		</script>

  <script id="refundtpl" type="text/html">
	  <div class="orderdetail">
		  <div class="product">

			  {{#  layui.each(d.prolist, function(index, item){ }}
			  <div class="content">
				  <div>
					  <img src="{{item.pic}}"/>
				  </div>
				  <div class="detail">
					  <span class="t1">{{item.name}}</span>
					  <span class="t2">{{item.ggname}}</span>
					  <div class="t3"><span class="x1 flex1">￥{{item.sell_price}}×{{item.num}}</span><span style="color: #999;font-size: 12px;">最多可退{{item.canRefundNum}}件</span>
						  <div class="layui-form-item">
						  	<div class="layui-input-inline" style="width:50px">
								<input type="number" name="refundNum[]" min="0" max="{{item.canRefundNum}}" data-ogid="{{item.id}}" data-order="{{d.detail.id}}" value="{{item.canRefundNum}}" class="layui-input refundNum" onchange="refundChange(this)" style="height: 28px; margin-top: -8px; margin-left: 2px;"></div>
					 	  </div>
					  </div>
				  </div>
			  </div>
			  {{# }); }}
		  </div>

		  <div class="orderinfo">
			  <div class="layui-form-item">
				  <label class="layui-form-label" style="width:90px">退款原因</label>
				  <div class="layui-input-inline" style="width:300px">
					  <textarea type="text" id="tuireason" class="layui-textarea"></textarea>
				  </div>
			  </div>
			  <div class="layui-form-item">
				  <label class="layui-form-label" style="width:90px">退款金额</label>
				  <div class="layui-input-inline" style="width:300px">
					  <input type="number" name="money" id="refund_money" min="0" max="{{d.detail.returnTotalprice}}" value="{{d.detail.returnTotalprice}}" class="layui-input">
				  </div>
			  </div>
		  </div>
	  </div>

  </script>
  <script id="glassCustomTpl" type="text/html">
	  <div class="orderdetail">
		  <div class="orderinfo">
			  <div class="item" style="align-items: center;text-align: center;font-weight: bold">
				  <span class="t1" style="width: 33%">定制参数</span>
				  <span class="t2" style="text-align: center">R</span>
				  <span class="t2" style="text-align: center">L</span>
			  </div>
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">球镜</span>
				  <span class="t2" style="text-align: center">{{d.sph_right}}</span>
				  <span class="t2" style="text-align: center">{{d.sph_left}}</span>
			  </div>
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">柱镜</span>
				  <span class="t2" style="text-align: center">{{d.cyl_right}}</span>
				  <span class="t2" style="text-align: center">{{d.cyl_left}}</span>
			  </div>
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">轴位</span>
				  <span class="t2" style="text-align: center">{{d.ax_right}}</span>
				  <span class="t2" style="text-align: center">{{d.ax_left}}</span>
			  </div>
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">下加光</span>
				  <span class="t2" style="text-align: center">{{d.add_right}}</span>
				  <span class="t2" style="text-align: center">{{d.add_left}}</span>
			  </div>
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">数量</span>
				  <span class="t2" style="text-align: center">{{d.qty_right}}</span>
				  <span class="t2" style="text-align: center">{{d.qty_left}}</span>
			  </div>
			  {{#  if(d.double_ipd){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">瞳距(PD)</span>
				  <span class="t2" style="text-align: center">{{d.ipd_right}}</span>
				  <span class="t2" style="text-align: center">{{d.ipd_left}}</span>
			  </div>
			  {{# }else{ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">瞳距(PD)</span>
				  <span class="t2" style="text-align: center">{{d.pd}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.double_npd){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">近瞳距(NPD)</span>
				  <span class="t2" style="text-align: center">{{d.npd_right}}</span>
				  <span class="t2" style="text-align: center">{{d.npd_left}}</span>
			  </div>
			  {{# }else{ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">近瞳距(NPD)</span>
				  <span class="t2" style="text-align: center">{{d.npd}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.seg_right || d.seg_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">瞳高</span>
				  <span class="t2" style="text-align: center">{{d.seg_right}}</span>
				  <span class="t2" style="text-align: center">{{d.seg_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.corrlen_right || d.corrlen_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">通道</span>
				  <span class="t2" style="text-align: center">{{d.corrlen_right}}</span>
				  <span class="t2" style="text-align: center">{{d.corrlen_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.frame_number_right || d.frame_number_lef){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镜框型号</span>
				  <span class="t2" style="text-align: center">{{d.frame_number_right}}</span>
				  <span class="t2" style="text-align: center">{{d.frame_number_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.frame_firm_right || d.frame_firm_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">厂商</span>
				  <span class="t2" style="text-align: center">{{d.frame_firm_right}}</span>
				  <span class="t2" style="text-align: center">{{d.frame_firm_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.frame_color_right || d.frame_color_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">颜色</span>
				  <span class="t2" style="text-align: center">{{d.frame_color_right}}</span>
				  <span class="t2" style="text-align: center">{{d.frame_color_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.frame_type_right || d.frame_type_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镜框类型</span>
				  <span class="t2" style="text-align: center">{{d.frame_type_right}}</span>
				  <span class="t2" style="text-align: center">{{d.frame_type_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.hbox_right || d.hbox_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镜高</span>
				  <span class="t2" style="text-align: center">{{d.hbox_right}}</span>
				  <span class="t2" style="text-align: center">{{d.hbox_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.vbox_right || d.vbox_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镜宽</span>
				  <span class="t2" style="text-align: center">{{d.vbox_right}}</span>
				  <span class="t2" style="text-align: center">{{d.vbox_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.dbl_right || d.dbl_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">鼻梁距</span>
				  <span class="t2" style="text-align: center">{{d.dbl_right}}</span>
				  <span class="t2" style="text-align: center">{{d.dbl_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.fed_right || d.fed_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">有效直径</span>
				  <span class="t2" style="text-align: center">{{d.fed_right}}</span>
				  <span class="t2" style="text-align: center">{{d.fed_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.fwd_right || d.fwd_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">眼镜总宽</span>
				  <span class="t2" style="text-align: center">{{d.fwd_right}}</span>
				  <span class="t2" style="text-align: center">{{d.fwd_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.panto_right || d.panto_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">前倾角</span>
				  <span class="t2" style="text-align: center">{{d.panto_right}}</span>
				  <span class="t2" style="text-align: center">{{d.panto_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.ztilt_right || d.ztilt_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镜面倾斜角</span>
				  <span class="t2" style="text-align: center">{{d.ztilt_right}}</span>
				  <span class="t2" style="text-align: center">{{d.ztilt_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.bvd_right || d.bvd_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镜眼距</span>
				  <span class="t2" style="text-align: center">{{d.bvd_right}}</span>
				  <span class="t2" style="text-align: center">{{d.bvd_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.prvm_x_right || d.prvm_x_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">水平棱镜</span>
				  <span class="t2" style="text-align: center">{{d.prvm_x_right}}</span>
				  <span class="t2" style="text-align: center">{{d.prvm_x_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.prva_x_right || d.prva_x_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">水平底向</span>
				  <span class="t2" style="text-align: center">{{d.prva_x_right}}</span>
				  <span class="t2" style="text-align: center">{{d.prva_x_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.prvm_y_right || d.prvm_y_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">垂直棱镜</span>
				  <span class="t2" style="text-align: center">{{d.prvm_y_right}}</span>
				  <span class="t2" style="text-align: center">{{d.prvm_y_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.prva_y_right || d.prva_y_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">垂直底向</span>
				  <span class="t2" style="text-align: center">{{d.prva_y_right}}</span>
				  <span class="t2" style="text-align: center">{{d.prva_y_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.fcoat_right || d.fcoat_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镀膜</span>
				  <span class="t2" style="text-align: center">{{d.fcoat_right}}</span>
				  <span class="t2" style="text-align: center">{{d.fcoat_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.tint_right || d.tint_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">染色</span>
				  <span class="t2" style="text-align: center">{{d.tint_right}}</span>
				  <span class="t2" style="text-align: center">{{d.tint_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.colr_right || d.colr_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">颜色</span>
				  <span class="t2" style="text-align: center">{{d.colr_right}}</span>
				  <span class="t2" style="text-align: center">{{d.colr_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.crib_right || d.crib_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镜片直径</span>
				  <span class="t2" style="text-align: center">{{d.crib_right}}</span>
				  <span class="t2" style="text-align: center">{{d.crib_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.minedg_right || d.minedg_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镜片边缘厚度</span>
				  <span class="t2" style="text-align: center">{{d.minedg_right}}</span>
				  <span class="t2" style="text-align: center">{{d.minedg_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.minctr_right || d.minctr_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镜片中心厚度</span>
				  <span class="t2" style="text-align: center">{{d.minctr_right}}</span>
				  <span class="t2" style="text-align: center">{{d.minctr_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.mbase_right || d.mbase_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">镜片基弯</span>
				  <span class="t2" style="text-align: center">{{d.mbase_right}}</span>
				  <span class="t2" style="text-align: center">{{d.mbase_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.inkmask_right || d.inkmask_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">标记</span>
				  <span class="t2" style="text-align: center">{{d.inkmask_right}}</span>
				  <span class="t2" style="text-align: center">{{d.inkmask_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.bcerin_right || d.bcerin_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">BCERIN</span>
				  <span class="t2" style="text-align: center">{{d.bcerin_right}}</span>
				  <span class="t2" style="text-align: center">{{d.bcerin_left}}</span>
			  </div>
			  {{# } }}
			  {{#  if(d.bcerup_right || d.bcerup_left){ }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">BCERUP</span>
				  <span class="t2" style="text-align: center">{{d.bcerup_right}}</span>
				  <span class="t2" style="text-align: center">{{d.bcerup_left}}</span>
			  </div>
			  {{# } }}
			  <div class="item" style="align-items: center;text-align: center">
				  <span class="t1" style="width: 33%">备注</span>
				  <span class="t2" style="text-align: left">{{d.remark}}</span>
			  </div>
		  </div>
	  </div>
  </script>
  <script>

	  function refundChange(e)
	  {
		  var order = tuikuandata;
		  var newnum = parseInt($(e).val());
		  var maxnum = parseInt($(e).attr('max'));
		  if (newnum > maxnum) {
			  layer.msg('请输入正确数量');return false;
		  }
		  var total = 0;
		  var refundTotalNum = 0;
		  var refundNum = [];
		  $(".refundNum").each(function(){
			  refundNum.push({ogid:$(this).attr('data-ogid'),num:$(this).val()})
		  });
			console.log(order,'-')
		  for(var i in refundNum) {
			  if(refundNum[i].num == order.prolist[i].num)
				  total += parseFloat(order.prolist[i].real_totalmoney);
			  else {
				  total += refundNum[i].num * parseFloat(order.prolist[i].real_totalmoney) / order.prolist[i].num;
			  }
			  refundTotalNum += Number(refundNum[i].num);
		  }
		  if(refundTotalNum == order.detail.totalNum || refundTotalNum == order.detail.canRefundNum) {
			  total = order.detail.returnTotalprice;
		  }
		  total = parseFloat(total);
		  total = total.toFixed(2);
		  $('#refund_money').val(total);
	  }

		function fhjiesuan() {
			var html = '';
			html += '	<div class="layui-form-item" style="margin-top:40px;margin-right:20px;">';
			html += '		<label class="layui-form-label" style="width:80px">时间范围：</label>';
			html += '		<div class="layui-input-inline" style="width:200px">';
			html += '			<input type="text" name="jiesuan_ctime" id="jiesuan_ctime" autocomplete="off" class="layui-input">';
			html += '		</div>';
			html += '		<div class="layui-form-mid layui-word-aux" style="margin-left:110px">订单确认收货的时间范围</div>';
			html += '	</div>';
			html += '	<div class="layui-form-item" style="margin-right:20px;">';
			html += '		<label class="layui-form-label" style="width:80px">订单数：</label>';
			html += '		<div class="layui-form-mid" id="jiesuan_ordercount" style="font-weight:bold"></div>';
			html += '	</div>';
			html += '	<div class="layui-form-item" style="margin-right:20px;">';
			html += '		<label class="layui-form-label" style="width:80px">订单金额：</label>';
			html += '		<div class="layui-form-mid" id="jiesuan_orderprice" style="font-weight:bold"></div>';
			html += '	</div>';
			var addscoreLayer = layer.open({
				type: 1, area: ['500px', '300px'], title: false, content: html, shadeClose: true, btn: ['确定', '取消'],
				yes: function () {
					var index = layer.load();
					$.post("<?php echo url('fhjiesuan'); ?>", {ctime: $('#jiesuan_ctime').val()}, function (res) {
						layer.close(index);
						dialog(res.msg, res.status);
						layer.close(addscoreLayer);
						tableIns.reload();
					})
				}
			});
			layui.form.render();
			//日期范围选择
			layui.laydate.render({
				elem: '#jiesuan_ctime',
				trigger: 'click',
				range: '~', //或 range: '~' 来自定义分割字符
				done: function (value) {
					var index = layer.load();
					$.post("<?php echo url('getordercount'); ?>", {ctime: value}, function (res) {
						layer.close(index);
						$('#jiesuan_ordercount').html(res.ordercount + '单');
						$('#jiesuan_orderprice').html('￥' + res.orderprice);
					});
				}
			});
		}
		function plfh2(){
			var checkStatus = table.checkStatus('tabledata')
			var checkData = checkStatus.data; //得到选中的数据
			//if(checkData.length === 0){
			//	 return layer.msg('请选择数据');
			//}
			var ids = [];
			for(var i=0;i<checkData.length;i++){
				ids.push(checkData[i]['id']);
			}

			var html = '';
			html+='	<div class="layui-form-item" style="margin-top:40px;">';
			html+='		<label class="layui-form-label" style="width:60px">快递公司</label>';
			html+='		<div class="layui-input-inline" style="width:180px">';
			html+='			<select id="plfh2_express" style="width:100%;height: 38px;line-height: 38px;border:1px solid #e6e6e6;background-color: #fff;border-radius: 2px;">';
			<?php foreach($express_data as $k=>$v): ?>
			html+='				<option><?php echo $k; ?></option>';
			<?php endforeach; ?>
			html+='			</select>';
			html+='		</div>';
			html+='	</div>';
			html+='	<div class="layui-form-item">';
			html+='		<label class="layui-form-label" style="width:60px">快递单号</label>';
			html+='		<div class="layui-input-inline" style="width:180px">';
			html+='			<input type="text" id="plfh2_express_no" class="layui-input" value="">';
			html+='		</div>';
			html+='	</div>';


			var checkLayer = layer.open({type:1,area:['500px','250px'],title:false,content:html,shadeClose:true,btn: ['选中项发货','全部发货', '取消'],
				yes:function(){
					if(ids.length === 0){
						 return layer.msg('请选择数据');
					}
					var index = layer.load();
					$.post("<?php echo url('plfh2'); ?>",{type:1,ids:ids,express_com:$('#plfh2_express').val(),express_no:$('#plfh2_express_no').val()},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(checkLayer);
						tableIns.reload()
					})
				},
				btn2:function(){
					var index = layer.load();
					$.post("<?php echo url('plfh2'); ?>"+urlEncode(datawhere),{type:2,ids:ids,express_com:$('#plfh2_express').val(),express_no:$('#plfh2_express_no').val()},function(res){
						layer.close(index);
						dialog(res.msg,res.status);
						layer.close(checkLayer);
						tableIns.reload()
					})
				}
			})

		}
		<?php if(getcustom('erp_wangdiantong')): ?>
		function  refreshWdtExpress(id){
			layer.confirm('ERP物流刷新需间隔5分钟，是否现在刷新？',function(){
				var index = layer.load();
				$.post("<?php echo url('refreshWdtExpress'); ?>",function(res){
					layer.close(index);
					dialog(res.msg,res.status);
					tableIns.reload()
				})
			})
		}
	  <?php endif; if(getcustom('product_pingce')): ?>
	  	function pingcedetail(id){
		  	var index = layer.load();
            $.post("<?php echo url('pingceorder'); ?>",{id:id},function(res){
                if(res.status != 2){
					layer.close(index);
					return layer.msg('未完成测评');
                }
				layer.close(index);
				var html = '';
				if(res.report_arr.bolePsyReport){
					html+='	<div class="layui-form-item" style="margin:40px;">';
					html+='		<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="openView(\''+res.report_arr.bolePsyReport+'\')">32种人才心理特质报告</button>';
					html+='	</div>';
				}
				if(res.report_arr.bolePostfitReport)
				{
					html += '	<div class="layui-form-item" style="margin:40px;">';
					html += '		<button class="layui-btn layui-btn-primary layuiadmin-btn-list" onclick="openView(\''+res.report_arr.bolePostfitReport+'\')">42种职场岗位适配报</button>';
					html += '	</div>';
				}
				var checkLayer = layer.open({type:1,area:['500px','250px'],title:'测评报告',content:html,shadeClose:true,btn: false,

				})
            })
		}
		function openView(url){
			layui.layer.open({
			    type: 2,
			    title: '测评报告',
			    area: ['90%', '90%'],
			    content: url
			});
	  }
	  <?php endif; ?>
		  function quanyihexiao(ogid,remain_num){
			  var html = '';
			  html+='	<div class="layui-form-item" style="margin-top:40px;">';
			  html+='		<label class="layui-form-label" style="width:60px">核销次数</label>';
			  html+='		<div class="layui-input-inline" style="width:180px">';
			  html+='			<input type="text" id="hexiao_num" class="layui-input" value="">';
			  html+='		</div>';
			  html += '		<div class="layui-form-mid layui-word-aux" style="margin-left:110px">剩余核销次数：'+remain_num+'</div>';
			  html+='	</div>';
			  var checkLayer = layer.open({type:1,area:['500px','250px'],title:false,content:html,shadeClose:true,btn: ['核销', '取消'],
				  yes:function(){
					  var index = layer.load();
					  $.post("<?php echo url('quanyiHexiao'); ?>",{ogid:ogid,hexiao_num:$('#hexiao_num').val()},function(res){
						  layer.close(index);
						  dialog(res.msg,res.status);
						  layer.close(checkLayer);
						  tableIns.reload()
					  })
				  }
			  })
		  }
          <?php if(getcustom('product_glass_custom')): ?>
          function glassCustomDetail(id){
              var index = layer.load();
              $.post("<?php echo url('glassCustomDetail'); ?>",{id:id},function(res){
                  if(res.status != 1){
                      layer.close(index);
                      return layer.msg(res.msg);
                  }
                  layer.close(index);
                  let customId = res.data.id;
                  console.log(customId);
                  layui.laytpl(glassCustomTpl.innerHTML).render(res.data, function(html){

                      detailLayer = layer.open({type:1,title:'定制参数',area:'500px',content:html,resize:true,shadeClose:true,btn: ['修改','导出'],
                          yes:function(){
                              openmax("<?php echo url('glassCustomEdit'); ?>/id/"+customId+"/oid/"+id);
                          },
	                      btn2:function(){
                              layer.confirm('确定要导出定制参数吗?',{icon: 7, title:'操作确认'}, function(cfm){
                                  layer.close(cfm);
                                  let index = layer.load();
                                  window.location.href = "<?php echo url('shopOrder/exportGlassCustom'); ?>/id/" + customId;
                                  setTimeout(function() {
                                      layer.close(index);
                                  }, 1000);
                              })
                          }
                      });
                  });
              })
          }
          <?php endif; if(getcustom('import_order')): ?>
			  //导入订单
			  function import_order(){
				  var html = '<div style="margin:20px auto;">';
				  html+='<div class="layui-form" lay-filter="">';
					  html+='	<div class="layui-form-item">';
					  html+='		<label class="layui-form-label" style="width:150px">上传EXCEL文件</label>';
					  html+='		<div class="layui-input-inline" style="width:300px">';
					  html+='			<input type="text" name="plfh_file" id="plfh_file" class="layui-input">';
					  html+='		</div>';
					  html+='		<button style="float:left;" type="button" class="layui-btn layui-btn-primary uploadexcel" upload-input="plfh_file">上传</button>';
					  //html+='		<div class="layui-form-mid layui-word-aux"></div>';
					  html+='	</div>';
					  html+='	<div class="layui-form-item" style="margin-top:30px">';
					  html+='		<label class="layui-form-label" style="width:150px"></label>';
					  html+='		<div class="layui-input-inline">';
					  html+='			<button class="layui-btn layui-btn-normal" lay-submit lay-filter="submit_excel">确定导入订单</button>';
					  html+='		</div>';
					  html+='	</div>';
					  html+='</div>';
					  html+='</div>'
					  var plfhLayer = layer.open({type:1,area: ['600px', '460px'],title:'导入EXCEl文件',content:html,shadeClose:true})
					  layui.form.render();
					  //文件上传
					  layui.upload.render({
						  elem: '.uploadexcel',
						  accept:'file',
						  url:"<?php echo url('upload/index'); ?>",
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
						  var index= layer.load();
						  $.post("<?php echo url('importorder'); ?>",field,function(res){
							  layer.close(index);
							  dialog(res.msg,res.status);
							  layer.close(plfhLayer);
							  tableIns.reload();
						  })
					  })
				  }
			  <?php endif; if(getcustom('shop_product_form')): ?>
				  function toFormOrder(id,formid){
					  window.location.href = "<?php echo url('Form/record'); ?>/id/" + id+'/formid/'+formid;
			  	  }
				  <?php endif; if(getcustom('supply_yongsheng')): ?>
					function createOrderYs(id){
						layer.confirm('确定手动发起下单吗?',{icon: 7, title:'操作确认'}, function(cfm){
							var index= layer.load();
							$.post("<?php echo url('SupplyYongshengOrder/createOrder'); ?>",{id:id,type:'shop'},function(res){
								  layer.close(index);
								  dialog(res.msg,res.status);
								  if(res.status == 1){
								  	layer.close(plfhLayer);
								  	tableIns.reload();
								  }
							  })
						})
					}
				<?php endif; if(getcustom('shopbuy_sign')): ?>
				function showhetong(wordurl){
					window.location.href=''+wordurl+'';
				}
				<?php endif; if(getcustom('shoporder_copy')): ?>
				function copyOrder(id){
					layer.confirm('确定复制此订单吗?',{icon: 7, title:'操作确认'}, function(cfm){
						var index= layer.load();
						$.post("<?php echo url('ShopOrder/copyOrder'); ?>",{id:id},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							if(res.status == 1){
								tableIns.reload();
							}
						})
					})
				}
				<?php endif; if(getcustom('shoporder_update_member')): ?>
				function updateMember(id){
					var html = '<div style="margin-top:50px;padding-left: 20px">';
					html+='<div class="layui-form" lay-filter="">';
					html+='	<div class="layui-form-item">';
					html+='		<label class="layui-form-label" style="width:150px">要更换的下单人会员ID</label>';
					html+='		<div class="layui-input-inline" style="width:150px">';
					html+='			<input type="text" name="updatemid" id="updatemid" class="layui-input">';
					html+='		</div>';
					html+='		<button style="float:left;" type="button" class="layui-btn layui-btn-primary" onclick="showChooseMember(this)">查看会员</button>';
					html+='	</div>';
					html+='	<div class="layui-form-item" style="margin-top:30px">';
					html+='		<label class="layui-form-label" style="width:150px"></label>';
					html+='		<div class="layui-input-inline">';
					html+='			<button class="layui-btn layui-btn-normal" lay-submit lay-filter="updateMemberBt">确定更换</button>';
					html+='		</div>';
					html+='	</div>';
					html+='</div>';
					html+='</div>'
					var plfhLayer = layer.open({type:1,area: ['500px', '280px'],title:'更换下单人',content:html,shadeClose:true})
					layui.form.render();
					layui.form.on('submit(updateMemberBt)', function(obj){
						var updatemid = $('#updatemid').val();
						if(!updatemid){
							dialog('请填写会员ID',0);
							return;
						}
						var index= layer.load();
						$.post("<?php echo url('updateMember'); ?>",{id:id,updatemid:updatemid},function(res){
							layer.close(index);
							dialog(res.msg,res.status);
							if(res.status == 1){
								layer.close(plfhLayer);
								tableIns.reload();
							}
						})
					})
				}
				var selectmemberLayer;
				function showChooseMember(obj){
					mobj = obj;
					selectmemberLayer = layer.open({type:2,title:'选择<?php echo t('会员'); ?>',content:"<?php echo url('Member/choosemember'); ?>",area:['1000px','600px'],shadeClose:true});
				}
				function choosemember(res){
					$('#updatemid').val(res.id)
					layer.close(selectmemberLayer);
				}
				<?php endif; ?>
  </script>
	
</body>
</html>