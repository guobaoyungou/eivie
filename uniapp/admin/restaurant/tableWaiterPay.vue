<template>
	<view class="container">
		<block v-if="isload">
			<view class="head-bg">
				<h1 class="text-center">{{detail.name}}</h1>
			</view>
			<form @submit="subform">
			<view class="card-view" v-if="detail.status == 2">
				<view class="card-wrap">
					<view class="card-title">餐桌信息</view>
					<view class="info-item">
						<view class="t1">桌台</view>
						<view class="t2">{{detail.name}}</view>
					</view>
					<view class="info-item">
						<view class="t1">人数/座位数</view>
						<view class="t2">{{order.renshu}}/{{detail.seat}}</view>
					</view>
				</view>
				<view class="card-wrap" v-if="detail.timing_fee_type && detail.timing_fee_type > 0">
					<view class="card-title">计时收费</view>
					<view class="info-item" style="justify-content: center;" v-if="detail.timing_log.length > 0" v-for="(item,index) in detail.timing_log ">
						<view class="t1">{{item.start_time}} ~ {{item.end_time}}</view>
						<view class="t2" style="flex: 0.3">{{item.num}} 分钟</view>
					</view>
					<view class="info-item info-textarea">
						<view class="t1">{{detail.timing_fee_text}}</view>
						<view class="t2">￥{{detail.timing_money}}</view>
					</view>
				</view>
				
				<view class="card-wrap card-goods" v-if="orderGoods.length > 0">
					<view class="card-title">已点菜品({{orderGoodsSum}})</view>
					<view class="info-item" v-for="(item,index) in orderGoods" :key="index">
						<view class="t1">{{item.name}}[{{item.ggname}}]</view>
						<view class="t2">x{{item.num}}</view>
						<view class="t3">￥{{item.real_totalprice}}</view>
					</view>
					<view class="info-item" v-if="order.tea_fee > 0">
						<view class="t1">餐位费</view>
						<view class="t2">{{order.renshu}}人</view>
						<view class="t3">￥{{order.tea_fee}}</view>
					</view>
					<view class="info-item">
						<view class="t1">合计</view>
						<view class="t2">x{{orderGoodsSum}}</view>
						<view class="t3">￥{{order.totalprice}}</view>
					</view>
					<view class="info-item">
						<view class="t1">优惠</view>
						<view class="t2" style="text-align: right;"><input type="digit" v-model="discount" @input="input" data-name="discount" name="discount" placeholder="输入优惠金额"></view>
					</view>
					<view class="info-item">
						<view class="t1">实付</view>
						<view class="t2">￥{{real_totalprice}}</view>
					</view>
				</view>
			</view>
			<view class="btn-view button-sp-area">
				<!-- <button type="default" class="btn-default">取消</button> -->
				<button type="primary" form-type="submit" v-if="detail.status == 2">线下收款</button>
			</view>
			</form>
				
			<view class="btn-view button-sp-area mb">
				<button type="default" class="btn-default" @tap="goto" data-url="tableWaiter">返回餐桌列表</button>
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
				order:{},
				orderGoods:[],
				business:{},
				nindex:0,
				orderGoodsSum:0,
				real_totalprice:0,
				numArr:[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20],
				discount:''
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
				app.get('ApiAdminRestaurantTable/detail', {id:that.opt.id}, function (res) {
					that.loading = false;
					if(res.status == 0){
						app.alert(res.msg,function(){
							app.goback();
						});return;
					}
					that.detail = res.info;
					that.order = res.order;
					that.orderGoods = res.order_goods;
					that.orderGoodsSum = res.order_goods_sum;
					that.real_totalprice = res.order.totalprice;
					that.settleTiming();
					that.loaded();
					//
				});
			},
			subform: function (e) {
				var that = this;
				var info = e.detail.value;
				info.tableId = that.opt.id;
			
				app.confirm('请确认已线下收款，如用户已在线支付，请返回上一页', function(){
						app.showLoading('提交中');
					app.post("ApiAdminRestaurantShopOrder/pay", {info: info}, function (res) {
						app.showLoading(false);
						if(res.status == 0) {
							app.alert(res.msg);
						}
						if(res.status == 1) {
							app.alert(res.msg,function(){
								app.goback();
							});
						}
					});
				})
			},
			
			input: function (e) {
			  // var discount = e.detail.value;
				var discount = parseFloat(this.discount);
				 discount = isNaN(discount)?0:discount;
				if(discount < 0) {
					app.error('请输入正确的金额');return;
				}
				if(discount > this.real_totalprice) {
					app.error('优惠金额不能大于订单金额');return;
				}
				this.real_totalprice = this.order.totalprice;
				this.real_totalprice =parseFloat( parseFloat( this.real_totalprice) - parseFloat( discount) ).toFixed(2);
			},
			//结算计时
			settleTiming(){
				var that = this;
				var tableid = that.opt.id;
				app.post("ApiAdminRestaurantTable/settleTimingMoney", {tableid: tableid}, function (res) {
					
				});
			}
		}
	}
</script>

<style>
	.mb {margin-bottom: 10rpx;}
	.text-center {text-align: center;}
	.container {padding-bottom: 20rpx;}
	.head-bg {width: 100%;height: 320rpx; background: linear-gradient(-90deg, #FFCF34 0%, #FFD75F 100%); color: #333;}
	.head-bg h1 { line-height: 100rpx; font-size: 42rpx;}
	.head-bg .title { align-items: center; width: 94%; margin: 0 auto;}
	.head-bg .image{ width:80rpx;height:80rpx; margin: 0 10rpx;}
	
	.card-wrap { background-color: #FFFFFF; border-radius: 10rpx;padding: 30rpx; margin: 30rpx auto 0; width: 94%;}
	.card-view{ margin-top: -140rpx; }
	.card-wrap .card-title {font-size: 34rpx; color: #333; font-weight: bold;}

	
.info-item{ display:flex;align-items:center;width: 100%; background: #fff; /* border-bottom: 1px #f3f3f3 solid; */height:auto;line-height:70rpx}
.info-item:last-child{border:none}
.info-item .t1{ width: 200rpx;color: #8B8B8B;line-height:70rpx;line-height:70rpx}
form .info-item .t1 {color: #333; font-size: 30rpx;}
.info-item .t2{ color:#444444;text-align:right;flex:1;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden; padding-right: 10rpx;}
.info-item .t3{ }
.card-goods .t1 {width: 70%;}
.card-goods .t2 {width: 8%; padding-right: 2%;}
.card-goods .t3 {width: 20%;}
.info-textarea { height: auto; line-height: 40rpx;}
.info-textarea textarea {height: 80rpx;}
.info-textarea .t2{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp: unset;overflow: scroll;}

.btn-view { display: flex;justify-content: space-between; margin: 30rpx 0;}
.btn-view button{ width: 90%; border-radius: 10rpx;background: linear-gradient(-90deg, #F7D156 0%, #F9D873 100%); color: #333; font-weight: bold;}
.btn-default {background-color: #FFFFFF;}

.content{ display:flex;width:100%;padding:0 0 10rpx 0;align-items:center;font-size:24rpx}
.content .item{padding:10rpx 0;flex:1;display:flex;flex-direction:column;align-items:center;position:relative}
.content .item .image{ width:80rpx;height:80rpx}
.content .item .iconfont{font-size:60rpx}
.content .item .t3{ padding-top:3px}
.content .item .t2{display:flex;align-items:center;justify-content:center;background: red;color: #fff;border-radius:50%;padding: 0 10rpx;position: absolute;top: 0px;right:20rpx;width:35rpx;height:35rpx;text-align:center;}


</style>
