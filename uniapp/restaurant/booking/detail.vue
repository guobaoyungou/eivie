<template>
	<view class="container">
		<block v-if="isload">
			<view class="head-bg">
				<view class="title" v-if="detail.check_status == 0 && detail.status == 0">
					<view v-if="is_book_order==1 && !hasproinfo">预定成功，请继续下单</view>
					<view v-else-if="is_book_order==1 && hasproinfo">预定成功，等待审核</view>
					<view v-else>预定成功，请支付</view>
				</view>
				<view class="title" v-if="detail.check_status == 0 && (detail.status == 1 || detail.status == 2 ||detail.status == 3)">预定成功，等待审核</view>
				<view class="title" v-if="(detail.check_status == 0 || detail.check_status == 1) && detail.status == 4">预定已关闭</view>
				<view class="title" v-if="detail.check_status == 1 && detail.status > 0 && detail.status != 4">预定成功</view>
				<view class="title" v-if="detail.check_status == 1 && detail.status == 0">预定成功，请支付</view>
				<view class="title" v-if="detail.check_status == -1 && detail.status > 0">预定失败，商家驳回</view>
				
				<view class="text-center mt20" v-if="detail.check_status != -1 && business.show_tip==1 && detail.status != 4">请在预定时间20分钟内到店。</view>
				<view class="text-center mt20" v-if="detail.check_status == -1 && detail.status == 0">请重新预定。</view>
			</view>
			<view class="card-view">
				<view class="card-wrap">
					<view class="card-title">{{business.name}}</view>
					<view class="mt20">{{business.address}}</view>
				</view>
				<view class="card-wrap">
					<view class="card-title">预定信息</view>
					<view class="info-item mt">
						<view class="t1">预定人</view>
						<view class="t2">{{detail.linkman}}</view>
					</view>
					<view class="info-item">
						<view class="t1">手机号</view>
						<view class="t2">{{detail.tel}}</view>
					</view>
					<view class="info-item">
						<view class="t1">预定时间</view>
						<view class="t2">{{detail.booking_time}}</view>
					</view>
					<view class="info-item">
						<view class="t1">{{textset['用餐人数']}}</view>
						<view class="t2">{{detail.seat}}</view>
					</view>
					<view class="info-item">
						<view class="t1">{{textset['预定桌台']}}</view>
						<view class="t2">{{detail.tableName}}</view>
					</view>
					<view class="info-item info-textarea">
						<view class="t1">备注信息</view>
						<view class="t2">{{detail.message}}</view>
					</view>
				</view>
				
				<view v-if="hasproinfo && is_book_order==1" class="card-wrap">
					<view class="card-title">菜品列表</view>
					<view class="product">
						<view v-for="(item, idx) in prolist" :key="idx" class="content">
							<view @tap="goto" :data-url="'product?id=' + item.proid">
								<image :src="item.pic"></image>
							</view>
							<view class="detail">
								<text class="t1">{{item.name}}</text>
								<view class="t2 flex flex-y-center flex-bt">
									<view v-if="(item.ggtext && item.ggtext.length)" class="flex-col">
										<block v-for="(item2,index) in item.ggtext">
											<text class="t2">{{item2}}</text>
										</block>
									</view>
									<text v-if="item.ggname">{{item.ggname}} </text><text v-if="item.jltitle">{{item.jltitle}}</text>	
								</view>
								<view class="t3">
									<text class="x1 flex1" v-if="item.jlprice">￥{{parseFloat(parseFloat(item.sell_price)+parseFloat(item.jlprice)).toFixed(2)}}<text v-if="item.product_type && item.product_type==1" style="font-size: 20rpx;">斤</text></text>
									<text class="x1 flex1" v-else>￥{{item.sell_price}}<text v-if="item.product_type && item.product_type==1" style="font-size: 20rpx;">斤</text></text>
									<text class="x2">×{{item.num}}<text v-if="item.product_type && item.product_type==1" style="font-size: 20rpx;">斤</text></text>
								</view>
							</view>
						</view>
					</view>
								
					<view v-if="hasproinfo && is_book_order == 1" class="orderinfo">
						<view class="item">
							<text class="t1">商品金额</text>
							<text class="t2 red">¥{{proinfo.product_price}}</text>
						</view>
						<view class="item">
							<text class="t1">实付款</text>
							<text class="t2 red">¥{{proinfo.totalprice}}</text>
						</view>
						<view class="item">
							<text class="t1">订单状态</text>
							<text class="t2" v-if="proinfo.status==0">未付款</text>
							<text class="t2" v-if="proinfo.status==1 && proinfo.paytypeid != 4">已付款</text>
							<text class="t2" v-if="proinfo.status==1 && proinfo.paytypeid == 4">线下支付</text>
							<text class="t2" v-if="proinfo.status==12">商家已接单</text>
							<text class="t2" v-if="proinfo.status==2">已发货</text>
							<text class="t2" v-if="proinfo.status==3">已收货</text>
							<text class="t2" v-if="proinfo.status==4">已关闭</text>
						</view>
						<view class="item" v-if="proinfo.refund_status>0">
							<text class="t1">退款状态</text>
							<text class="t2 red" v-if="proinfo.refund_status==1">审核中,¥{{proinfo.refund_money}}</text>
							<text class="t2 red" v-if="proinfo.refund_status==2">已退款,¥{{proinfo.refund_money}}</text>
							<text class="t2 red" v-if="proinfo.refund_status==3">已驳回,¥{{proinfo.refund_money}}</text>
						</view>
						<view class="item" v-if="proinfo.refund_status>0">
							<text class="t1">退款时间</text>
							<text class="t2 red">{{proinfo.refund_time}}</text>
						</view>
						<view class="item" v-if="proinfo.refund_status>0">
							<text class="t1">退款原因</text>
							<text class="t2 red">{{proinfo.refund_reason}}</text>
						</view>
						<view class="item" v-if="proinfo.refund_checkremark">
							<text class="t1">审核备注</text>
							<text class="t2 red">{{proinfo.refund_checkremark}}</text>
						</view>
					</view>
				</view>
			</view>
			<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>
			<view class="bottom notabbarbot">
				<view v-if="hasproinfo && proinfo.status==0 && is_book_order==1" class="btn1" @tap="toPay" :data-id="proinfo.payorderid" >去付款</view>
				<view v-if="!hasproinfo && is_book_order==1" class="btn1" @tap="toOrder" >继续点餐</view>
				<view v-if="detail.status != 4" class="btn1" @tap="cancel">取消</view>
				<view v-if="detail.status == 0 && is_book_order==0" class="btn1" @click="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去支付</view>

			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
	</view>
</template>

<script>
var app = getApp();
	export default {
		data() {
			return {
				opt:{},
				loading:false,
				isload: false,
				
				detail:{},
				business:{},
				textset:[],
				proinfo:{},
				hasproinfo:false,
				prolist: [],
				is_book_order:0
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods: {
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiRestaurantBooking/detail', {id:that.opt.id}, function (res) {
					that.loading = false;
					if(res.status == 0){
						app.alert(res.msg,function(){
							app.goback();
						});return;
					}
					that.detail = res.data;
					that.business = res.business;
					that.textset = res.textset;
					that.proinfo = res.proinfo || {};
					that.hasproinfo = Object.keys(that.proinfo).length != 0;
					that.prolist = res.prodetail;
					that.is_book_order = res.is_book_order;
					that.loaded();
					//
				});
			},
			toOrder:function () {
				var that = this;
				if(that.is_book_order==1){
					app.goto('/restaurant/shop/index?tableId='+that.detail.tableid+'&isbook=1&renshu='+that.detail.seat+'&bookid='+that.opt.id);
				}
			},
			cancel:function () {
				var that = this;
				that.loading = true;
				app.get('ApiRestaurantBooking/del', {id:that.opt.id}, function (res) {
					that.loading = false;
					if(res.status == 0){
						app.alert(res.msg,function(){
							app.goback();
						});return;
					}
					if(res.status == 1){
						app.alert(res.msg,function(){
							app.goback();
						});return;
					}
					//
				});
			},
			
			toPay:function(e){
				console.log(e);
				var payorderid = e.currentTarget.dataset.id;
				var url ='/pagesExt/pay/pay?id='+payorderid
				app.goto(url);
			},
		}
	}
</script>

<style>
	.text-center { text-align: center;}
	.head-bg {width: 100%;height: 400rpx; background: linear-gradient(120deg, #FF7D15 0%, #FC5729 100%); color: #fff;padding-top:100rpx;}
	.head-bg .title { text-align: center; }
	
	.card-wrap { background-color: #FFFFFF; border-radius: 10rpx;padding: 30rpx; margin: 30rpx auto 0; width: 94%;}
	.card-wrap:first-child{ margin-top: -100rpx; }
	.card-wrap .card-title {font-size: 34rpx; color: #333; font-weight: bold;}
	
.info-item{ display:flex;align-items:center;width: 100%; background: #fff;padding:0 3%;  border-bottom: 1px #f3f3f3 solid;height:96rpx;line-height:96rpx}
.info-item:last-child{border:none}
.info-item .t1{ width: 200rpx;color: #8B8B8B;font-weight:bold;height:96rpx;line-height:96rpx}
.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.info-item .t3{ width: 26rpx;height:26rpx;margin-left:20rpx}
.info-textarea { height: auto; line-height: 40rpx;}
.info-textarea .t2{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp: unset;overflow: scroll;}

.product{width:100%; padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}

.product .content .detail .t1{font-size:26rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{color: #999;font-size: 26rpx;margin-top: 10rpx;}
.product .content .detail .t3{display:flex;color: #ff4246;margin-top: 10rpx;}
.product .content .detail .t4{margin-top: 10rpx;}

.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.orderinfo{ width:100%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:calc(92rpx + env(safe-area-inset-bottom));padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px; left: 0px;display:flex;justify-content:flex-end;align-items:center;}
.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;background:#FB4343;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
</style>
