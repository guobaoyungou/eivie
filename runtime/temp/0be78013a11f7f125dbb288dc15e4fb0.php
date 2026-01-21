<?php /*a:4:{s:50:"/www/wwwroot/eivie/app/view/backstage/welcome.html";i:1768886477;s:43:"/www/wwwroot/eivie/app/view/public/css.html";i:1764308967;s:42:"/www/wwwroot/eivie/app/view/public/js.html";i:1766974798;s:49:"/www/wwwroot/eivie/app/view/public/copyright.html";i:1648714895;}*/ ?>
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
		.scroll-diy::-webkit-scrollbar {width: 2px; height: 0px;}
		.scroll-diy::-webkit-scrollbar-thumb {border-radius: 1px;-webkit-box-shadow: inset 0 0 1px rgba(150, 150, 150, 0.5);background: rgba(150, 150, 150, 0.5);}
		.scroll-diy::-webkit-scrollbar-track {-webkit-box-shadow: inset 0 0 0px rgba(150, 150, 150, 0.2);border-radius: 0;background: rgba(150, 150, 150, 0);}
		.page-body{display: flex;flex-direction: column;}
		.page-body .page-header{background: #FFFFFF;height: 80px;position: fixed;top: 0;width: 100%;display: flex;align-items: center;justify-content: space-between;padding: 0px 30px;
		box-sizing: border-box}
		.page-header .logo-div{display: flex;align-items: center;justify-content: flex-start;cursor: pointer;}
		.page-header .logo-div .logo-img{width: 50px;height: 50px;}
		.page-header .logo-div .logo-text-div{display: flex;flex-direction: column;margin-left: 10px;}
		.page-header .logo-div .logo-text-div .logo-title{color: #27304B;font-size: 18px;}
		.page-header .logo-div .logo-text-div .logo-introduce{color: #8E98A4;font-size: 10px;margin-top: 4px;}
		.page-header .header-right-div{display: flex;align-items: center;justify-content: flex-end;flex: 1;}
		.header-right-div .search-div{background: #F3F5F9;display: flex;align-items: center;justify-content: space-between;padding: 15px 20px;border-radius: 8px;width: 360px;margin-right: 110px;}
		.header-right-div .search-div input {color: #626C7D;font-size: 16px;height: 100%;border: none;outline: none;background: #F3F5F9;flex: 1;}
		.header-right-div .personal-info-div{display: flex;align-items: center;justify-content: space-around;}
		.personal-info-div .options-img{width: 22px;height: 22px;margin-left: 30px;cursor: pointer;}
		.personal-info-div .head-portrait{width: 48px;height: 48px;border-radius: 50%;overflow: hidden;margin-left: 30px;cursor: pointer;}
		.personal-info-div .head-portrait img{width: 100%;height: 100%;}
		.personal-info-div .message-div{margin-left: 30px;cursor: pointer;position: relative;}
		.personal-info-div .message-div .message-num-div{width: 14px;height: 14px;border-radius: 50%;background: #EF493B;color: #FFFFFF;font-size: 10px;
		position: absolute;top: -5px;right: -5px;text-align: center;line-height: 14px;}
		.personal-info-div .message-div img{width: 22px;height: 22px;}
		.page-center{width: 100%;display: flex;align-items: flex-start;justify-content: space-between;box-sizing: border-box;height: 100%;}
		.page-center .nav-list-div{width: 250px;height:820px;overflow-y: scroll;background: #fff;padding: 20px;}
		.page-center .nav-list-div::-webkit-scrollbar {width: 2px; height: 0px;}
		.page-center .nav-list-div::-webkit-scrollbar-thumb {border-radius: 1px;-webkit-box-shadow: inset 0 0 1px rgba(150, 150, 150, 0.5);background: rgba(150, 150, 150, 0.5);}
		.page-center .nav-list-div::-webkit-scrollbar-track {-webkit-box-shadow: inset 0 0 0px rgba(150, 150, 150, 0.2);border-radius: 0;background: rgba(150, 150, 150, 0);}
		.page-center .nav-list-div .option-nav{border-radius: 8px;color: #49566D;font-weight: 500;font-size: 18px;display: flex;align-items: center;justify-content: flex-start;
		padding:18px 20px;margin: 15px 0px;cursor: pointer;}
		.page-center .nav-list-div .option-nav img{width: 32px;height: 32px;margin-right: 20px;}
		.page-center .nav-list-div .option-nav:hover{box-shadow: 0px 4px 16px 0px rgba(97, 97, 249, 0.4);background: #2384FA;color: #FFFFFF;}
		.page-center .nav-list-div .option-nav-active{box-shadow: 0px 4px 16px 0px rgba(97, 97, 249, 0.4);background: #2384FA;color: #FFFFFF;}
		.page-center .center-div{flex: 1;overflow-y: scroll;background: #f3f5f9;padding: 20px;box-sizing: border-box;display: flex;flex-direction: column;}
		.page-center .center-div::-webkit-scrollbar {width: 2px; height: 0px;}
		.page-center .center-div::-webkit-scrollbar-thumb {border-radius: 1px;-webkit-box-shadow: inset 0 0 1px rgba(150, 150, 150, 0.5);background: rgba(150, 150, 150, 0.5);}
		.page-center .center-div::-webkit-scrollbar-track {-webkit-box-shadow: inset 0 0 0px rgba(150, 150, 150, 0.2);border-radius: 0;background: rgba(150, 150, 150, 0);}
		.page-center .center-div .center-div-options-class{background: #FFFFFF;border-radius: 8px;padding: 20px;}
		.page-center .center-div .center-div-options-title{color: #1E2E48;font-size: 20px;font-weight: bold;white-space: nowrap;}
		.page-center .center-div .operate-div{display: flex;flex-direction: column;}
		.operate-div-head{display: flex;align-items: center;justify-content: space-between;}
		.operate-div-head .operate-screen-div{display: flex;align-items: center;border: 1px solid #8E98A4;border-radius: 4px;}
		.operate-screen-div .operate-screen-options{padding: 5px 15px;cursor: pointer;}
		.operate-screen-div .screen-options-active{color: #2384FA;font-weight: bold;}
		.more-text{color: #8E98A4;font-size: 14px;cursor: pointer;}
		.operate-center-div{display: flex;align-items: center;justify-content: space-between;margin-top: 24px;flex-wrap: wrap;}
		.operate-center-div .center-div-options{background: rgba(232, 240, 251, 0.4);border-radius: 8px;display: flex;align-items: center;flex-direction: column;justify-content: center;
		padding: 20px;flex: 1;margin: 0px 10px;cursor: pointer;}
		.center-div-options .operate-num{color: #1E2E48;font-size: 24px;flex: 1;text-align: center;font-weight: bold;}
		.center-div-options .operate-text-div{display: flex;align-items: center;justify-content: center;color: #49566D;font-size: 18px;font-weight: 500;margin-top: 10px;
		white-space: nowrap;}
		.center-div-options .operate-text-div label{font-size: 14px;color: #8E98A4}
		.center-flex-div{display: flex;align-items: center;justify-content: space-between;margin-top:16px;}
		.order-centent-class{margin: 0px 20px;}
		.center-order{height: 380px;width: 33.5%;}
		.member-view{width: 24%;max-height: 420rpx;height: 380px;}
		.member-view .member-info-div{display: flex;flex-direction: column;margin: 40px 0px;}
		.member-view .member-info-div .member-info-options{display: flex;align-items: center;justify-content: flex-start;}
		.member-info-options .member-head-img{border: 1px solid #D2D6DB;width: 64px;height: 64px;border-radius: 50%;overflow: hidden;}
		.member-info-options .member-head-img img{width: 100%;height: 100%;}
		.member-info-options .member-info-text{margin-left: 20px;display: flex;flex-direction: column;}
		.member-info-text .member-info-name{white-space: nowrap;flex: 1;text-overflow: ellipsis;overflow: hidden;color: #49566D;font-size: 16px;}
		.member-info-text .member-info-type{display: flex;align-items: center;justify-content: flex-start;margin-top: 12px;}
		.member-info-type .type-text{color: #8E98A4;font-size: 14px;}
		.member-info-type .interval-div{width: 1px;height: 14px;background: #8E98A4;margin: 0px 5px;}
		.member-info-type .verified{color: #1BD294;font-size: 14px;display: flex;align-items: center;}
		.member-info-type .not-verified{color: #F94949;font-size: 14px;display: flex;align-items: center;}
		.not-verified img{width: 16px; height: 16px;margin-right:5px ;}
		.verified img{width: 16px; height: 16px;margin-right:5px ;}
		.member-view  .current-info{display: flex;flex-direction: column;}
		.current-info .current-info-options{display: flex;align-items: center;justify-content: flex-start;margin-bottom: 16px;}
		.current-info-options .title-text{color: #8E98A4;font-size: 14px;}
		.current-info-options  .centen-text{color: #626C7D;font-size: 14px;margin-left: 30px;}
		.member-price-view{width: 24%;height: 540px;}

		.member-price-view .member-price-center{display: flex;flex-wrap: wrap;justify-content: space-between;margin-top:10px;}
		.member-price-center .price-center-options{background: rgba(232, 240, 251, 0.3);padding: 25px 16px;border-radius: 8px;display: flex;flex-direction: column;
		margin-top: 20px;width:47%;box-sizing: border-box;}
		.member-price-center .price-center-options .title-text{color: #8E98A4;font-size: 14px;}
		.member-price-center .price-center-options .price-text{font-weight: bold;font-size: 20px;color: #1E2E48;margin: 30px 0px 10px;word-wrap: break-word;}
		.member-price-center .price-center-options .centen-text-div{display: flex;align-items: center;color: rgba(0, 0, 0, 0.24);font-size: 12px;}
		.member-price-center .price-center-options .centen-text-div .rise-text{color: #00AE41;font-size: 14px;font-weight: bold;}
		.hot-shoplist{height: 540px;width: 43%;}
		.hot-shoplist .top-view-left{flex: 1;display: flex;align-items: center;justify-content: space-between;padding-right: 20px;}
		.hot-shoplist .hot-shop-title{border-bottom: 1px #E2EEEF solid;margin-top: 10px;display: flex;align-items: center;color: #49566D;font-size: 14px;padding: 15px 0px;
		width: 100%;justify-content: space-between;}
		.hot-shoplist .hot-shop-title .hot-shop-title-shop{width: 58.8%;white-space: nowrap;}
		.hot-shoplist .hot-shop-title .hot-shop-title-sales-volume{width: 12%;text-align: center;white-space: nowrap;}
		.hot-shoplist .hot-shop-title .hot-shop-title-price-text{width: 19%;text-align: center;white-space: nowrap;}
		.hot-shoplist .hot-shop-title-change-text{white-space: nowrap;white-space: nowrap;width:6%;text-align: center;}
		.hot-shoplist .hot-shop-title .hot-shop-title-change-tex{width: 130px;}
		.hot-shoplist .list-div{height: 430px;overflow-y: scroll;display: flex;flex-direction: column;}
		.hot-shoplist .list-div .shop-options{display: flex;align-items: center;justify-content: space-between;margin: 7px 0px;width: 100%;}
		.list-div .shop-options .shop-img{width: 70px;height: 70px;border: 1px #E2EEEF solid;border-radius: 12px;overflow: hidden;}
		.list-div .shop-options .shop-img img{width: 100%;height: 100%;}
		.list-div .shop-options .shop-info-div{display: flex;flex-direction: column;width: 49%;}
		.shop-options .shop-info-div .shop-name{color: #49566D;font-size: 16px;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;width: 100%;}
		.shop-options .shop-info-div .shop-sku{font-size: 14px;color: #8E98A4;margin-top: 10px;}
		.shop-options .sales-volume{color: #49566D;font-size: 16px;width: 12%;text-align: center;}
		.shop-options .price-text{color: #49566D;font-size: 16px;width: 19%;text-align: center;word-break: break-all;}
		.shop-options .change-text{color: #2384FA;font-size: 16px;cursor: pointer;white-space: nowrap;width:6%;text-align: center;}
		.message-list{margin-top: 10px;display: flex;flex-direction: column;height: 504px;overflow-y: scroll;}
		.message-list .message-options{display: flex;flex-direction: column;margin-top: 20px;cursor: pointer;}
		.message-options .message-title{color: #626C7D;font-size: 14px;position: relative;text-indent: 18px;flex: 1;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;}
		.message-options .message-title::after{content: " ";display: block;width: 5px;height: 5px;border-radius: 50%;position: absolute;left: 0px;top:7px;background-color: #D8D8D8;}
		.message-options .message-time{color: #86909C;font-size: 12px;margin-top: 12px;text-indent: 18px;}
		.sales-trends-div{flex: 1;}
		.shop-type-selset{width: 100px;padding: 11px 0px;}
		.shop-type-selset .layui-input,.layui-select,.layui-textarea {height: 31px;line-height: 1.3;line-height: 38px\9;border-width: 1px;border-style: solid; background-color: #fff;
    border-radius: 2px}
		.empty-div{position: relative;width: 100%;height:100%;display: flex;flex-direction: column;align-items: center;}
		.empty-div img{width: 88%;}
		.empty-div .empty-text{color: #8E98A4;font-size: 16px;flex: 1;text-align: center;margin-top: 256px;position: absolute;}
		.getMonthOrder-class{width:100%;height: 95%;display: flex;align-items: center;justify-content: space-between;}
		.getMonthOrder-class .statistics-div-class{display: flex;flex-direction: column;}
		.statistics-div-class .statistics-div-options{display: flex;align-items: center;justify-content: flex-start;margin-bottom: 22px;}
		.statistics-div-options .color-div{width: 16px;height: 16px;border-radius: 1px;}
		.statistics-div-options .statistics-text{color: #626C7D;font-size: 14px;width:80px;text-align: left;margin-left: 15px;}
		.statistics-div-options .statistics-num{color: #8E98A4;font-size: 14px;padding-left: 10px;text-align: left;width: 100px;}
		.getMonthOrder-chart{position: relative;width: 389px;height: 332px;}
		.total-orders{display: flex;flex-direction: column;position: absolute;top: 5%;align-items: center;justify-content: center;left: 50%;top:50%;transform: translate(-50%,-50%);}
		.total-orders .total-orders-num{font-size: 26px;font-weight: bold;margin-top: 10px;}
		.member-chart-div{width: 100%;display: flex;align-items: center;justify-content: space-between;}
		.tongji-chart-member{display: flex;flex-direction: column;width: 50%;margin-top: 50px;}
		.tongji-chart-member .top-view{display: flex;align-items: center;justify-content: flex-start;width: 100%;}
		.top-view .top-view-options{display: flex;flex-direction: column;align-items: flex-start;width: 50%;justify-content: flex-end;}
		.top-view .top-view-options .num-text{color: #1E2E48;font-size: 30px;font-family:bold;height: 41px;}
		.top-view .top-view-options .tisp-text{color: #909399;font-size: 12px;margin-top: 5px;white-space: nowrap;}
		.getMemberGailan-chart{width: 50%;height: 352px;}
		.gailan-div-class{display: flex;flex-direction: column;margin-top: 40px;margin-left: 20px;}
		.gailan-div-options{display: flex;align-items: center;justify-content: flex-start;margin-bottom: 25px;}
		.gailan-div-options .color-div{width: 12px;height: 12px;border-radius: 1px;}
		.gailan-div-options .statistics-text{color: #626C7D;font-size: 14px;width:80px;text-align: left;margin-left: 15px;}
		.shop-type-selset .layui-input, .layui-select, .layui-textarea {border-color: #8E98A4;border-radius: 4px;}
		.layui-form-select .layui-edge {border-top-color: #8E98A4;}
		.layui-select-title input::placeholder{color: #666;}
		/* 1280 > 1024 > 960 > 800 >768 > 640 */
		@media screen and (max-width: 1280px) {
			.empty-div img{width: 40%;}
			.operate-center-div{margin-top: 10px;}
			.operate-center-div .center-div-options{flex: none;width: 48%;margin:0px 0px;margin-top: 3%;box-sizing: border-box;}
			.center-flex-div{flex-direction: column;}
			.center-order{width: 100% !important;box-sizing: border-box;}
			.order-centent-class{margin: 20px 0px;}
			.member-view{width: 100%;max-height: 500rpx;height: 400px;box-sizing: border-box}
			.member-price-view{width: 100%;height: 580px;box-sizing: border-box}
			.member-price-center .price-center-options .price-text{margin: 20px 0px 10px;}
			.hot-shoplist{flex: none;width:100% !important;height: 640px;box-sizing: border-box;}
			.sales-trends-div{flex: none;width: 100%;box-sizing: border-box}
			.hot-shoplist .hot-shop-title .hot-shop-title-shop{width: 58%;}
			.tongji-chart-member{width: 30%;}
			.getMemberGailan-chart{width: 40%;}
			.getMonthOrder-class{justify-content: space-around;}
			.member-chart-div{justify-content: space-around;}
		}
		@media screen and (max-width: 800px) {
		.empty-div{width: 100%;height:100%;margin-top: 30rpx;}
		.empty-div img{width: 55%;}
		.empty-div .empty-text{margin-top: 200px;}
		}
		@media screen and (max-width: 768px) {
			.list-div .shop-options .shop-info-div{width: 45%;}
			.list-div .shop-options .shop-img{width: 60px;height: 60px;border-radius: 10px;}
			.empty-div img{width: 60%;}
		}
		@media screen and (max-width: 640px) {
			.operate-div-head{align-items: flex-start;flex-direction: column;}
			.operate-div-head .operate-screen-div{margin-top: 10px;}
			.operate-screen-div .operate-screen-options {white-space: nowrap;}
			.hot-shoplist .top-view-left{flex: none;width: 100%;padding-right: none;}
			.list-div .shop-options .shop-info-div{width: 40%;}
			.list-div .shop-options .shop-img{min-width: 45px;width: 45px;height: 45px;border-radius: 6px;}
			.hot-shoplist .hot-shop-title .hot-shop-title-shop {width: 52%;}
			.chart_body{margin-top: 10px;}
			.empty-div img{width: 70%;}
			.getMonthOrder-class{flex-direction: column;}
			.member-chart-div{flex-direction: column-reverse;}
			.center-order{height: auto;}
			.getMemberGailan-chart{width: 100%;height: 352px;}
			.tongji-chart-member{width: 100%;}
			.top-view-options{align-items: center !important;width: 50%;}
			.getMonthOrder-class .statistics-div-class{width: 90%;}
			.statistics-div-options{justify-content: center !important;margin-bottom: 15px !important;}
			.statistics-div-options .statistics-num{width: auto !important;white-space: nowrap;}
			.gailan-div-class{margin-top: 20px !important;margin-left: 0px !important;}
			.gailan-div-options{justify-content:center !important;margin-bottom: 15px;}
		}
		
	</style>
</head>
<body>
<div class="page-body">
	<div class="page-center">
		<div class="center-div">
			<div class="operate-div center-div-options-class">
				<div class="operate-div-head">
					<div class="center-div-options-title">运营数据概览</div>
					<div class="operate-screen-div">
						<div class='operate-screen-options screen-options-active' onclick="changeOperateData(this,1)">今日</div>
						<div class='operate-screen-options' style="border-left: 1px solid #8E98A4;" onclick="changeOperateData(this,2)">昨日</div>
						<div class='operate-screen-options' style="border-left: 1px solid #8E98A4;border-right: 1px solid #8E98A4;" onclick="changeOperateData(this,7)">近7日</div>
						<div class='operate-screen-options' onclick="changeOperateData(this,30)">近30日</div>
					</div>
				</div>
				<div class="operate-center-div">
					<?php if($bid == 0): ?>
					<div class="center-div-options" onclick="openmax('<?php echo url('Payorder/index'); ?>&isopen=1')">
						<div class="operate-num" id="payMoneyDayCount">0</div>
						<div class="operate-text-div">
							收款金额<label>(元)</label>
							<div class="layui-text-popover layui-text-popover-size">
								<i class="layui-icon layui-icon-about layui-default-link"></i>
								<div class="layui-textpopover-div">
									<div class="layui-textpopover-text">
										所有订单类型的收款金额（仅含支付宝、微信、现金付款的金额，不含余额、到店付款的金额；包含已退款订单金额）
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<div class="center-div-options" onclick="openmax('<?php echo url('ShopOrder/index'); ?>&isopen=1')">
						<div class="operate-num" id="orderMoneyDayCount">0</div>
						<div class="operate-text-div">
							订单金额<label>(元)</label>
							<div class="layui-text-popover layui-text-popover-size">
								<i class="layui-icon layui-icon-about layui-default-link"></i>
								<div class="layui-textpopover-div">
									<div class="layui-textpopover-text">
										商城商品的订单金额（包含退款中订单，不含已退款订单）
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="center-div-options" onclick="openmax('<?php echo url('ShopOrder/index'); ?>&isopen=1')">
						<div class="operate-num" id="orderDayCount">0</div>
						<div class="operate-text-div">
							订单数量
							<div class="layui-text-popover layui-text-popover-size">
								<i class="layui-icon layui-icon-about layui-default-link"></i>
								<div class="layui-textpopover-div">
									<div class="layui-textpopover-text">
										商城商品的订单数量
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="center-div-options" onclick="openmax('<?php echo url('ShopRefundOrder/index'); ?>&isopen=1')">
						<div class="operate-num" id="refundMoneyDayCount">0</div>
						<div class="operate-text-div">
							退款金额<label>(元)</label>
							<div class="layui-text-popover layui-text-popover-size">
								<i class="layui-icon layui-icon-about layui-default-link"></i>
								<div class="layui-textpopover-div">
									<div class="layui-textpopover-text">
										商城商品的已退款订单金额（不含退款中的订单）
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="center-div-options" onclick="openmax('<?php echo url('ShopRefundOrder/index'); ?>&isopen=1')">
						<div class="operate-num" id="refundDayCount">0</div>
						<div class="operate-text-div">
							退款数量
							<div class="layui-text-popover layui-text-popover-size">
								<i class="layui-icon layui-icon-about layui-default-link"></i>
								<div class="layui-textpopover-div">
									<div class="layui-textpopover-text">
										商城商品的已退款订单数量（不含退款中的订单）
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="center-div-options" onclick="openmax('<?php echo url('ShopOrder/index'); ?>&isopen=1&status=1')">
						<div class="operate-num" id="orderNoFahuoDayCount">0</div>
						<div class="operate-text-div">
							待发货
							<div class="layui-text-popover layui-text-popover-size">
								<i class="layui-icon layui-icon-about layui-default-link"></i>
								<div class="layui-textpopover-div">
									<div class="layui-textpopover-text">
										商城商品待发货订单数量
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="center-div-options" onclick="openmax('<?php echo url('ShopRefundOrder/index'); ?>&isopen=1')">
						<div class="operate-num" id="orderShouhouDayCount">0</div>
						<div class="operate-text-div">
							待售后
							<div class="layui-text-popover layui-text-popover-size">
								<i class="layui-icon layui-icon-about layui-default-link"></i>
								<div class="layui-textpopover-div">
									<div class="layui-textpopover-text">
										商城商品待售后订单数量
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="center-div-options" onclick="openmax('<?php echo url('ShopOrder/index'); ?>&isopen=1&status=0')">
						<div class="operate-num" id="orderNoPayDayCount">0</div>
						<div class="operate-text-div">
							待支付
							<div class="layui-text-popover layui-text-popover-size">
								<i class="layui-icon layui-icon-about layui-default-link"></i>
								<div class="layui-textpopover-div">
									<div class="layui-textpopover-text">
										商城商品待支付订单数量
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="center-flex-div">
				<div class="center-div-options-class center-order">
					<div class="center-div-options-title">本月订单统计</div>
					<div class="getMonthOrder-class">
						<div class="chart_body getMonthOrder-chart">
							<?php if($orderMonthCountAll > 0): ?>
							<div class="layui-carousel layadmin-carousel layadmin-dataview"  id="getMonthOrder"></div>
							<div class="total-orders">
								<div>总订单数</div>
								<div class="total-orders-num"><?php echo $orderMonthCountAll; ?></div>
							</div>
							<?php else: ?>
							<div class="empty-div">
								<img src="/static/admin/img/index/empty.png"/>
								<div class="empty-text">暂无数据~</div>
							</div>
							<?php endif; ?>
						</div>

						<div class="statistics-div-class">
							<div class="statistics-div-options">
								<div class="color-div" style="background: #ed7330;"></div>
								<div class="statistics-text">待支付订单</div>
								<div class="statistics-num"><?php echo $orderNoZhifuMonthCount; ?>单</div>
							</div>
							<div class="statistics-div-options">
								<div class="color-div" style="background: #FFB723;"></div>
								<div class="statistics-text">待发货订单</div>
								<div class="statistics-num"><?php echo $orderNoFahuoMonthCount; ?>单</div>
							</div>
							<div class="statistics-div-options">
								<div class="color-div" style="background: #1ECF8F;"></div>
								<div class="statistics-text">运输中订单</div>
								<div class="statistics-num"><?php echo $orderFahuoMonthCount; ?>单</div>
							</div>
							<div class="statistics-div-options">
								<div class="color-div" style="background: #5FB8FC;"></div>
								<div class="statistics-text">已完成订单</div>
								<div class="statistics-num"><?php echo $orderFinishMonthCount; ?>单</div>
							</div>
							<div class="statistics-div-options">
								<div class="color-div" style="background: #2384FA;"></div>
								<div class="statistics-text">异常订单</div>
								<div class="statistics-num"><?php echo $orderClosehMonthCount; ?>单</div>
							</div>
							<div class="statistics-div-options">
								<div class="color-div" style="background: #333E6A;"></div>
								<div class="statistics-text">已退款订单</div>
								<div class="statistics-num"><?php echo $orderRefundMonthCount; ?>单</div>
							</div>
						</div>
					</div>

				</div>
				<div id='MemberOverview' class="center-div-options-class center-order order-centent-class">
					<div class="center-div-options-title"><?php echo t(会员); ?>概览</div>
					<?php if($memberCount == 0): ?>
					 <div class="empty-div">
						 <img src="/static/admin/img/index/empty.png" style="width: 50%;" />
						 <div class="empty-text" >暂无数据~</div>
					 </div>
					 <?php else: ?>
					<div class="member-chart-div">
						<div class="tongji-chart-member">
							<div class="top-view">
								<div class="top-view-options">
									<div class="num-text"><?php echo $memberCount; ?></div>
									<div class="tisp-text"><?php echo t(会员); ?>总数</div>
								</div>
								<div class="top-view-options">
									<div class="num-text"><?php echo $memberHuoyueCount; ?></div>
									<div class="tisp-text">30日活跃用户</div>
								</div>
							</div>
							<div class="gailan-div-class">
								<div class="gailan-div-options">
									<div class="color-div" style="background: #1E9FFF;"></div>
									<div class="statistics-text">下单<?php echo t(会员); ?>数</div>
									<div class="statistics-num"><?php echo $orderMemberCount; ?></div>
								</div>
								<div class="gailan-div-options">
									<div class="color-div" style="background: rgba(30, 159, 255, 0.6);"></div>
									<div class="statistics-text">复购<?php echo t(会员); ?>数</div>
									<div class="statistics-num"><?php echo $orderMemberFugouCount; ?></div>
								</div>
								<div class="gailan-div-options">
									<div class="color-div" style="background: rgba(30, 159, 255, 0.4);"></div>
									<div class="statistics-text"><?php echo t(余额); ?><?php echo t(会员); ?>数</div>
									<div class="statistics-num"><?php echo $memberMoneyCount; ?></div>
								</div>
								<div class="gailan-div-options">
									<div class="color-div" style="background:rgba(30, 159, 255, 0.2);"></div>
									<div class="statistics-text"><?php echo t(佣金); ?><?php echo t(会员); ?>数</div>
									<div class="statistics-num"><?php echo $memberCommissionCount; ?></div>
								</div>
							</div>
						</div>
						<?php if($memberMoneyCount >0 && $memberCommissionCount >0 && $orderMemberCount > 0 && $orderMemberFugouCount): ?>
						<div class="chart_body getMemberGailan-chart">
							<div class="layui-carousel layadmin-carousel layadmin-dataview"  id="getMemberGailan"></div>
						</div>
						<?php else: ?>
						<div class="empty-div" style="height: 80%;">
							<img src="/static/admin/img/index/empty.png"/>
							<div class="empty-text" >暂无数据~</div>
						</div>
						<?php endif; ?>
					</div>
					<?php endif; ?>
<!-- 					<?php if(bid > 0): ?>
					 <div class="empty-div" style="bottom: 0px; max-height: 320px;">
						 <img src="/static/admin/img/index/empty.png"/>
						 <div class="empty-text" style="bottom: 0;">暂无数据~</div>
					 </div>
					 <?php endif; if(bid == 0): ?>

					<div class="chart_body">
						<div class="layui-carousel layadmin-carousel layadmin-dataview" style="width:98%" id="getMemberGailan"></div>
					</div>

					 <?php endif; ?> -->
				</div>

				<div class="center-div-options-class member-view">
					<div class="center-div-options-title">管理员中心</div>
					<?php if(bid==0): ?>
					<div class="member-info-div">
						<?php if(in_array('mp',$platform)): if($mpinfo && $mpinfo['appid']): ?>
							<a href="<?php echo url('binding/index'); ?>"><div class="member-info-options">
								<div class="member-head-img" onclick="location.href='<?php echo url('binding/index'); ?>'">
									<img src="<?php echo (isset($mpinfo['headimg']) && ($mpinfo['headimg'] !== '')?$mpinfo['headimg']:'/static/img/wxtx.png'); ?>" />
								</div>
								<div class="member-info-text">
									<div class="member-info-name"><?php echo (isset($mpinfo['nickname']) && ($mpinfo['nickname'] !== '')?$mpinfo['nickname']:"暂无昵称"); ?></div>
									<div class="member-info-type">
										<div class="type-text"><?php if($mpinfo['level']==2 || $mpinfo['level']==4): ?>服务号<?php else: ?>订阅号<?php endif; ?></div>
										<div class="interval-div"></div>
										<div class="verified">
											<?php if($mpinfo['level']==3 || $mpinfo['level']==4): ?>
											<img src="/static/admin/img/index/authentication.png"/>
											已认证
											<?php else: ?>
											<img src="/static/admin/img/index/notauthentication.png"/>
											未认证
											<?php endif; ?>	
										</div>
									</div>
								</div>
							</div></a>
							<?php else: ?>
							<div class="member-info-options">
								<div class="member-info-text">
									<a href="<?php echo url('binding/index'); ?>" style="color:#1E9FFF;font-size:16px;height:20px;line-height:20px">未绑定公众号</a>
								</div>
							</div>
							<?php endif; ?>
						<?php endif; if(in_array('wx',$platform)): if($wxapp && $wxapp['appid']): ?>
						<a href="<?php echo url('binding/index'); ?>"><div class="member-info-options" style="margin-top: 20px;">
							<div class="member-head-img">
								<img src="<?php echo (isset($wxapp['headimg']) && ($wxapp['headimg'] !== '')?$wxapp['headimg']:'/static/img/wxtx.png'); ?>" />
							</div>
							<div class="member-info-text">
								<div class="member-info-name"><?php echo (isset($wxapp['nickname']) && ($wxapp['nickname'] !== '')?$wxapp['nickname']:"暂无昵称"); ?></div>
								<div class="member-info-type">
									<div class="type-text">小程序</div>
									<!-- <div class="interval-div"></div>
									<div class="not-verified">
										<img src="/static/admin/img/index/notauthentication.png"/>
										未认证
									</div> -->
								</div>
							</div>
						</div></a>
						<?php else: ?>
							<div class="member-info-options" style="margin-top: 20px;">
								<div class="member-info-text">
									<a href="<?php echo url('binding/index'); ?>" style="color:#1E9FFF;font-size:16px;height:20px;line-height:20px">未绑定小程序</a>
								</div>
							</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
					<?php else: ?>
					<div class="member-info-div">
						<div class="member-info-options" style="margin-top: 20px;">
							<div class="member-head-img">
								<img src="<?php echo (isset($business['logo']) && ($business['logo'] !== '')?$business['logo']:'/static/img/wxtx.png'); ?>" />
							</div>
							<div class="member-info-text">
								<div class="member-info-name"><?php echo $business['name']; ?></div>
								<div class="member-info-type">
									<div class="type-text">商家</div>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<div class="current-info">
						<div class="current-info-options">
							<div class="title-text">当前账号：</div>
							<div class="centen-text"><?php if(bid==0): ?><?php echo session('ADMIN_NAME'); else: ?><?php echo $business['name']; ?><?php endif; ?></div>
						</div>
						<div class="current-info-options">
							<div class="title-text">系统时间：</div>
							<div class="centen-text"><span id="systime"><?php echo date('Y-m-d H:i:s'); ?></span></div>
						</div>
						<div class="current-info-options">
							<div class="title-text">到期时间：</div>
							<div class="centen-text"><?php if($endtime): ?><?php echo date('Y-m-d H:i',$endtime); else: ?>无<?php endif; ?></div>
						</div>
						
					</div>
				</div>
			</div>
			
			<div class="center-flex-div">
				 <div id='MemberOverviewPrice' class="center-div-options-class member-price-view">
					 <div class="center-div-options-title"><?php echo t(会员); ?>金额概览</div>
					 <?php if($memberCount == 0): ?>
					 <div class="empty-div">
						 <img src="/static/admin/img/index/empty.png"/>
						 <div class="empty-text" >暂无数据~</div>
					 </div>
					 <?php else: ?>					
					 <div class="member-price-center" >
						 <div class="price-center-options">
							 <div class="title-text">
								储值余额
								<div class="layui-text-popover">
									<i class="layui-icon layui-icon-about layui-default-link"></i>
									<div class="layui-textpopover-div">
										<div class="layui-textpopover-text" style="width: auto;white-space: nowrap;">
											会员的余额汇总
										</div>
									</div>
								</div>
							 </div>
							 <div class="price-text"><?php echo $memberMoney; ?></div>
							 <!-- <div class="centen-text-div">
								 <div>环比上周</div>
								 <div class="rise-text">12.88%</div>
							 </div> -->
						 </div>
						 <div class="price-center-options">
								<div class="title-text">
									储值总额
									<div class="layui-text-popover">
										<i class="layui-icon layui-icon-about layui-default-link"></i>
										<div class="layui-textpopover-div">
											<div class="layui-textpopover-text" style="width: auto;white-space: nowrap;">
												会员充值金额汇总（不含后台充值）
											</div>
										</div>
									</div>
								</div>
						 	 <div class="price-text"><?php echo $memberMoneySum; ?></div>
						 </div>
						 <div class="price-center-options">
						 	<div class="title-text">
								<?php echo t(佣金); ?>余额
								<div class="layui-text-popover">
									<i class="layui-icon layui-icon-about layui-default-link"></i>
									<div class="layui-textpopover-div">
										<div class="layui-textpopover-text" style="width: auto;white-space: nowrap;">
											会员的<?php echo t(佣金); ?>汇总
										</div>
									</div>
								</div>
							</div>
						 	<div class="price-text"><?php echo $memberCommission; ?></div>
						 </div>
						 <?php if($hide_wallet_total==0): ?>
						 <div class="price-center-options">
						 	<div class="title-text">
								<?php echo t(佣金); ?>总额
								<div class="layui-text-popover">
									<i class="layui-icon layui-icon-about layui-default-link"></i>
									<div class="layui-textpopover-div">
										<div class="layui-textpopover-text" style="width: auto;white-space: nowrap;">
											通过分销分红获得的累计金额（含已提现金额）
										</div>
									</div>
								</div>
							</div>
						 	<div class="price-text"><?php echo $memberCommissionSum; ?></div>
						 </div>
						 <?php endif; if($hide_wallet_total==1): ?>
						 <div class="price-center-options">
							 <div class="title-text">可提现<?php echo t(佣金); ?></div>
							 <div class="price-text"><?php echo $able_withdraw_commission_total; ?></div>
						 </div>
						 <?php endif; ?>
						 <div class="price-center-options">
						 	<div class="title-text">
								<?php echo t(积分); ?>余额
								<div class="layui-text-popover">
									<i class="layui-icon layui-icon-about layui-default-link"></i>
									<div class="layui-textpopover-div">
										<div class="layui-textpopover-text" style="width: auto;white-space: nowrap;">
											会员的<?php echo t(积分); ?>汇总
										</div>
									</div>
								</div>
							</div>
						 	<div class="price-text"><?php echo $memberScore; ?></div>
						 </div>
						 <?php if($hide_wallet_total==0): ?>
						 <div class="price-center-options">
						 	<div class="title-text">
								<?php echo t(积分); ?>总额
								<div class="layui-text-popover">
									<i class="layui-icon layui-icon-about layui-default-link"></i>
									<div class="layui-textpopover-div">
										<div class="layui-textpopover-text" style="width: auto;white-space: nowrap;">
											会员累计获得的<?php echo t(积分); ?>（含后台添加和已提现<?php echo t(积分); ?>）
										</div>
									</div>
								</div>
							</div>
						 	<div class="price-text"><?php echo $memberScoreSum; ?></div>
						 </div>
						 <?php endif; ?>
					 </div>
					 <?php endif; ?>
				 </div>


				 <div class="center-div-options-class hot-shoplist order-centent-class">
						<div class="operate-div-head">
							<input type="hidden" name="moneychangesaleday" id="moneychangesaleday" value="1"/>
							<div class="top-view-left">
								<div class="center-div-options-title">热卖商品</div>
								<div class="layui-form">
									<div class="layui-input-inline shop-type-selset" >
										<select name="moneygoodssalescat" id="moneygoodssalescat" onchange="moneychangeGoodsSalecat()" lay-filter="moneychangeGoodsSalecat">
											<option value="">商品分类</option>
											<?php foreach($clist as $cv): ?>
												<option value="<?php echo $cv['id']; ?>" ><?php echo $cv['name']; ?></option>
												<?php foreach($cv['child'] as $v): ?>
												<option value="<?php echo $v['id']; ?>" >&nbsp;&nbsp;&nbsp;<?php echo $v['name']; ?></option>
													<?php foreach($v['child'] as $v3): ?>
													<option value="<?php echo $v3['id']; ?>" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $v3['name']; ?></option>
													<?php endforeach; ?>
												<?php endforeach; ?>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
							</div>
							<div class="operate-screen-div">
								<div class='operate-screen-options screen-options-active' onclick="moneychangeGoodsSaleday(this,1)">今日</div>
								<div class='operate-screen-options' style="border-left: 1px solid #8E98A4;border-right: 1px solid #8E98A4;" onclick="moneychangeGoodsSaleday(this,7)">近7日</div>
								<div class='operate-screen-options' onclick="moneychangeGoodsSaleday(this,30)">近30日</div>
								<div class='operate-screen-options' style="border-left: 1px solid #8E98A4;" onclick="moneychangeGoodsSaleday(this,365)">今年</div>
							</div>
						</div>
<!-- 						<div class="empty-div" style="display: none;">
								<img src="/static/admin/img/index/empty.png" />
								<div class="empty-text">暂无数据~</div>
						</div> -->
						<div class="hot-shop-title">
							<div class="hot-shop-title-shop">商品信息</div>
							<div class="hot-shop-title-sales-volume">销量</div>
							<div class="hot-shop-title-price-text">实收金额</div>
							<div class="hot-shop-title-change-text">操作</div>
						</div>
						<div class="list-div scroll-diy" id="moneygoodsdata" >
							<!-- <div class="shop-options">
								<div class="shop-img">
									<img src="https://image.wxx1.com/upload/1/20240712/2b03403e17699a0efbadf81a312589ae.png" />
								</div>
								<div class="shop-info-div">
									<div class="shop-name">羊毛混合植物多喝点</div>
									<div class="shop-sku">CH852963852854</div>
								</div>
								<div class="sales-volume">
									555
								</div>
								<div class="price-text">
									￥632.00
								</div>
								<div class="change-text">
									操作
								</div>
							</div> -->
							
						</div>
				 </div>
				 <div class="center-div-options-class member-price-view">
						<div class="operate-div-head" style="display: flex;align-items: center;justify-content: space-between;flex-direction: row;">
							<div class="center-div-options-title">通知列表</div>
							<div class="more-text" onclick="location.href='<?php echo url('notice/index'); ?>'">
								更多
							</div>
						</div>						
						<?php if($noticedata): ?>
						<div class="message-list scroll-diy">
							
							<?php foreach($noticedata as $v): ?>
							<div class="message-options" onclick="openmax('<?php echo url('notice/detail'); ?>/id/<?php echo $v['id']; ?>')">
								<div class="message-title"><?php echo $v['title']; ?></div>
								<div class="message-time"><?php echo date('Y年m月d日',$v['createtime']); ?></div>
							</div>
							<?php endforeach; ?>
												
						</div>
						<?php else: ?>
						<div class="empty-div">
							<img src="/static/admin/img/index/empty.png"/>
							<div class="empty-text">暂无数据~</div>
						</div>
						<?php endif; ?>
				 </div>
			</div>
			<div class="center-flex-div">
				 <div class="center-div-options-class sales-trends-div">
					 <div class="operate-div-head">
					 	<div class="center-div-options-title">数据趋势图</div>
					 	<div class="operate-screen-div">
					 		<div class='operate-screen-options screen-options-active' onclick="changeDataChartday(this,1)">本月</div>
					 		<div class='operate-screen-options' style="border-left: 1px solid #8E98A4;border-right: 1px solid #8E98A4;" onclick="changeDataChartday(this,2)">上月</div>
					 		<div class='operate-screen-options' onclick="changeDataChartday(this,3)">前月</div>
					 	</div>
					 </div>
					 
					 <div class="chart_body">
						<div class="layui-carousel layadmin-carousel layadmin-dataview" style="width:98%" id="dataChart"></div>
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
<script src="/static/admin/js/echarts5.5.1.min.js"></script>
<script type="text/javascript">
//运营数据概览
function changeOperateData(obj,day){
	$(obj).siblings().removeClass('screen-options-active');
	$(obj).addClass('screen-options-active');
	getOperateData(day);
}
function getOperateData(day){
		$.post('',{op:'getOperateData',day:day},function(res){
			
			$('#payMoneyDayCount').html(res.payMoneyDayCount);
			$('#orderMoneyDayCount').html(res.orderMoneyDayCount);
			$('#orderDayCount').html(res.orderDayCount);
			$('#refundMoneyDayCount').html(res.refundMoneyDayCount);
			$('#refundDayCount').html(res.refundDayCount);
			$('#orderNoFahuoDayCount').html(res.orderNoFahuoDayCount);
			$('#orderShouhouDayCount').html(res.orderShouhouDayCount);
			$('#orderNoPayDayCount').html(res.orderNoPayDayCount);
		})
		
	}


var nowtime = "<?php echo time(); ?>";
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

//热卖商品
function moneychangeGoodsSaleday(obj,day){
		$('#moneychangesaleday').val(day);
		var cid = $('#moneygoodssalescat').val();
		$(obj).siblings().removeClass('screen-options-active');
		$(obj).addClass('screen-options-active');
		getsalesGoodsData(day,cid);
}
layui.form.on('select(moneychangeGoodsSalecat)',function(data){
	var day = $('#moneychangesaleday').val();
	var cid = $('#moneygoodssalescat').val();
	getsalesGoodsData(day,cid);
})
function moneychangeGoodsSalecat(){
	var day = $('#moneychangesaleday').val();
	var cid = $('#moneygoodssalescat').val();
	getsalesGoodsData(day,cid);
}
function getsalesGoodsData(day,cid){
		$.post('',{op:'getgoodssalesmoney',day:day,cid:cid},function(res){
			var html = "";
			var goodsdata = res.goodsdata;
			for(var i=0;i<goodsdata.length;i++){
				/*<div class="shop-options">
								<div class="shop-img">
									<img src="https://image.wxx1.com/upload/1/20240712/2b03403e17699a0efbadf81a312589ae.png" />
								</div>
								<div class="shop-info-div">
									<div class="shop-name">羊毛混合植物多喝点</div>
									<div class="shop-sku">CH852963852854</div>
								</div>
								<div class="sales-volume">
									555
								</div>
								<div class="price-text">
									￥632.00
								</div>
								<div class="change-text">
									操作
								</div>
							</div>*/
				html +='<div class="shop-options">';
				html +='<div class="shop-img"><img src="'+goodsdata[i]['pic']+'"/></div>';
				if(goodsdata[i]['procode'] == null){
					goodsdata[i]['procode'] = '';
				}
				html +='<div class="shop-info-div"><div class="shop-name">'+goodsdata[i]['name']+'</div><div class="shop-sku">'+goodsdata[i]['procode']+'</div></div>';
				html +='<div class="sales-volume">'+goodsdata[i]['num']+'</div>';
				html +='<div class="price-text">￥'+goodsdata[i]['totalprice']+'</div>';
				var url = "<?php echo url('ShopProduct/edit'); ?>/id/"+goodsdata[i]['proid'];
				//html +='<div class="change-text" onclick="location.href=\''+url+'\'">详情</div>';
				html +='<div class="change-text" onclick="openmax(\''+url+'\')">详情</div>';
				//html +='<div class="change-text" onclick="location.href='/?s=/notice/index'">详情</div>';
				html +='</div>';
									
			}
			if(goodsdata.length <=0){
				html +='<div class="empty-div">';
				html +='	<img style="width:38%" src="/static/admin/img/index/empty.png" / >';
				html +='	<div class="empty-text">暂无数据~</div>';
				html +='</div>';
			}
			
			$('#moneygoodsdata').html(html);
		})
		
	}
$(function(){
	<?php if($bid > 0): ?>
		$('.total-orders').css({'left':'47%'})
		$('#MemberOverview').hide();
		$('#MemberOverviewPrice').hide();
		$('.center-order').css({'width': '70.5%','margin':'0px 20px 0px 0px',});
		$('.hot-shoplist').css({'width': '70.5%','margin':'0px 20px 0px 0px',});
		$('.hot-shop-title-shop').css({'width': '56.5%'});
		let windowWidth = $(window).width();
		if(windowWidth < 1264){
			$('.member-view').css({'margin-top':'20px'});
			$('.member-price-view').css({'margin-top':'20px'});
			$('.center-order').css({'margin':'0px'});
			$('.hot-shoplist').css({'margin':'0px'});
		}else{
			$('.member-view').css({'margin-top':'0px'});
			$('.member-price-view').css({'margin-top':'0px'});
		}
		 $(window).resize(function() {
			 let windowWidth = $(window).width();
			 if(windowWidth < 1264){
				 $('.member-view').css({'margin-top':'20px'});
				 $('.member-price-view').css({'margin-top':'20px'});
				 $('.center-order').css({'margin':'0px'});
				 $('.hot-shoplist').css({'margin':'0px'});
			 }else{
				 $('.member-view').css({'margin-top':'0px'});
				 $('.member-price-view').css({'margin-top':'0px'});
				 $('.center-order').css({'margin':'0px 20px 0px 0px'});
				 $('.hot-shoplist').css({'margin':'0px 20px 0px 0px'});
			 }
		 })
	<?php endif; ?>
	setInterval(function(){
			nowtime++;
			var date = getLocalTime(nowtime);
			$('#systime').html(date);
		},1000)

		// 延迟加载数据以确保DOM完全渲染
		setTimeout(function() {
			getOperateData(1);
			getsalesGoodsData(1,'');
			getDataChart(1);
		}, 200);

});
//数据趋势图
function changeDataChartday(obj,day){
	$(obj).siblings().removeClass('screen-options-active');
	$(obj).addClass('screen-options-active');
	getDataChart(day);
}
// 确保DOM元素存在后再初始化图表
var dataChartDom = document.getElementById("dataChart");
var dataChart = null;
if (dataChartDom) {
    dataChart = echarts.init(dataChartDom);
}
function getDataChart(day){
		//var index = layer.load();
		$.post('',{op:'getDataChart',day:day},function(res){
			//layer.close(index);
			option_channel = {
					title: {
						text: ''
					},
					tooltip: {
						trigger: 'axis'
					},
					legend: {
						data: res.title
					},
					grid: {
						left: '3%',
						right: '4%',
						bottom: '3%',
						containLabel: true
					},
					// toolbox: {
					// 	feature: {
					// 	saveAsImage: {}
					// 	}
					// },
					xAxis: {
						type: 'category',
						boundaryGap: false,
						data: res.dateArr
					},
					yAxis: {
						type: 'value'
					},
					series: res.series
					};
					if (dataChart) {
					    dataChart.setOption(option_channel,true);
					}
		})
		
	}

	//本月订单统计
	<?php if($orderMonthCountAll > 0): ?>
	var getMonthOrderDom = document.getElementById("getMonthOrder");
	var getMonthOrder = null;
	if (getMonthOrderDom) {
	    getMonthOrder = echarts.init(getMonthOrderDom);
	}
	var channel_order_data = "<?php echo $channel_order_data; ?>";
	var orderMonthCountAll = <?php echo $orderMonthCountAll; ?>;
	option_channel = {
	tooltip: {
		//trigger: 'item'
		formatter: function(params) {
			var tooltipHtml = '<div> <span style="display:inline-block;margin-right:4px;border-radius:10px;width:10px;height:10px;background-color:'+params.data.itemStyle.color+';"></span>'+ params.name+' ' + params.value+ "单</div>";
			return tooltipHtml;	
		}
	},
	color:['#5470c6', '#91cc75', '#fac858', '#ee6666', '#73c0de', '#3ba272', '#fc8452', '#9a60b4', '#ea7ccc'],
	series: [
		{
		type: 'pie',
		padAngle: 3,
		itemStyle: {
		  borderRadius: 0.5
		},
		radius: ['35%', '45%'],
		avoidLabelOverlap: false,
		// data: [
		//         { value: 1048, name: '15.4%' },
		//         { value: 735, name: '1.4%' },
		//         { value: 580, name: '35.4%' },
		//         { value: 484, name: '5.4%' },
		//         { value: 300, name: '50.1%' }
		//       ],
		data: <?php echo json_encode($channel_order_data); ?>,
		}
	]
	};
	if (getMonthOrder) {
	    getMonthOrder.setOption(option_channel,true);
	}
	<?php endif; ?>
	//会员概览
	<?php if(bid == 0 || $memberCount > 0): ?>
	var getMemberGailanDom = document.getElementById("getMemberGailan");
	var getMemberGailan = null;
	if (getMemberGailanDom) {
	    getMemberGailan = echarts.init(getMemberGailanDom);
	}
	var channel_order_data = "<?php echo $channel_order_data; ?>";
	var orderMonthCountAll = <?php echo $orderMonthCountAll; ?>;
	// 判断字数超出后字体大小改变
	<?php if($memberCount && $memberHuoyueCount): ?>
		var memberCountLength = <?php echo $memberCount; ?>;
		var memberHuoyueCountLength = <?php echo $memberHuoyueCount; ?>;
		if((memberCountLength.toString().length >= 6) || (memberHuoyueCountLength.toString().length >= 6)){
			$('.top-view-options').find('.num-text').css({'fontSize': '20px'})
		}else{
			$('.top-view-options').find('.num-text').css({'fontSize': '30px'})
		}
	<?php endif; ?>
	option_member = {
		title: [
			{
			text: ''
			}
		],
		polar: {
			radius: [20, '75%']
		},
		angleAxis: {
			show: false, 
			max: 100,
			startAngle: 90,
			axisLine: {
					show: true, // x轴刻度分割线
				},
				axisLabel: {
					show: true // x轴刻度文本
				},
				splitLine: {
					show: true // 切分线显示
				}
		},
		radiusAxis: {
			type: 'category',
			axisLine: {
					show:true , // x轴刻度分割线
					lineStyle: {
                color: 'E3E9F3' // 修改为你想要的颜色
            }
				
				},
				axisLabel: {
					show: true // x轴刻度文本
				},
				splitLine: {
					show: true // 切分线显示
				},
			//data: ['a', 'b', 'c','d']
			data: <?php echo json_encode($memberChartDataname); ?>,
		},
		tooltip: {
			formatter: function(params) {
			var tooltipHtml = '<div> <span style="display:inline-block;margin-right:4px;border-radius:10px;width:10px;height:10px;background-color:'+params.data.itemStyle.color+';"></span>'+ params.name+' ' + params.value+'%'+' '+params.data.num + "个</div>";
			return tooltipHtml;
		}
		},
		
		series: {
			type: 'bar',
			barCategoryGap: '50%', // 每个类别之间的空隙
			 data: <?php echo json_encode($memberChartData); ?>,
			// data: [
			// 		{
			// 		value: 60,
			// 		itemStyle: {
			// 			normal: {
			// 			color: 'rgba(97, 97, 249, 0.4)'
			// 			}
			// 		}
			// 		}
			// 		],
			coordinateSystem: 'polar'
		}
		};

	if (getMemberGailan) {
	    getMemberGailan.setOption(option_member,true);
	}
	layui.form.render('select');
	<?php endif; ?>
</script>

</body>
</html>