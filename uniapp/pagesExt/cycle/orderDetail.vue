<template>
	<view v-if="isload">
		
		<view class="banner" v-if="detail.refund_status ==1 || detail.refund_status ==1">
			<img :src="pre_url+'/static/img/week/week_banner.png'" class="banner_img" alt="" />
			<img :src="pre_url+'/static/img/week/week_icon.png'" class="banner_icon" alt="" />
			<view class="banner_data" v-if="detail.refund_status==1">
				<view class="banner_title">退款中</view>
				<view class="banner_text" >
					等待平台审核中
				</view>
			</view>
			<view class="banner_data" 	v-if="detail.refund_status==2">
				<view class="banner_title">退款成功</view>
				<view class="banner_text" >
					审核通过，已退款
				</view>
			</view>
		</view>
		<view class="banner" v-else>
			<img :src="pre_url+'/static/img/week/week_banner.png'" class="banner_img" alt="" />
			<img :src="pre_url+'/static/img/week/week_icon.png'" class="banner_icon" alt="" />
			<view class="banner_data" v-if="detail.status==0">
				<view class="banner_title">
					待支付
				</view>
			<!-- 	<view class="banner_text" v-if="detail.status == 0">
					请尽快完成支付
				</view> -->
			</view>
			<view class="banner_data" 	v-if="detail.status==1">
				<view class="banner_title" >
					<text v-if="detail.freight_type ==1">待取货</text>
					<text v-else>待发货</text>
				</view>
				<view class="banner_text" >
					<view class="t1">{{detail.paytype=='货到付款' ? '已选择'+codtxt : '已成功付款'}}
						<text v-if="detail.freight_type!=1">，我们会尽快为您配送</text>
					</view>
				</view>
			</view>
			
			<view class="banner_data" 	v-if="detail.status==2">
				<view class="banner_title"  v-if="detail.freight_type ==1">已取货</view>
				<view class="banner_title"  v-else>已发货</view>
				<view class="banner_text" v-if="detail.status == 0">
					具体发货信息请查看每期订单
				</view>
			</view>
			<view class="banner_data" 	v-if="detail.status==3">
				<view class="banner_title">已完成</view>
			</view>
			<view class="banner_data" 	v-if="detail.status==4">
				<view class="banner_title">已关闭</view>
			</view>
		</view>

		<view class="body">
			<view class="address">
				
				<view class="img">
					<image :src="pre_url+'/static/img/address3.png'"></image>
				</view>
				<view class="info">
					<text class="t1" user-select="true" selectable="true">{{detail.linkman}} {{detail.tel}}</text>
					<text class="t2" v-if="detail.freight_type!=1 && detail.freight_type!=3" user-select="true" selectable="true">地址：{{detail.area}}{{detail.address}}</text>
					<text class="t2" v-if="detail.freight_type==1" @tap="openMendian" :data-storeinfo="storeinfo" user-select="true" selectable="true">取货地点：{{storeinfo.name}} - {{storeinfo.address}} </text>
				</view>
				
			</view>
			<!-- <view class="body_title">
				服务信息
			</view> -->
			<view class="body_module">
				<img :src="detail.propic"
					class="body_img" alt="" />
				<view class="body_data">
					<view class="body_name">
						{{detail.proname}}
					</view>
					<view class="body_text">
						{{detail.ggname}} | {{detail.pspl}}<text v-if="detail.every_day">,{{detail.every_day}}</text>
					</view>
					<view class="body_price flex flex-bt flex-y-bottom">
						<text>￥{{detail.sell_price}}</text>
						<!-- <text class="body_num">x{{detail.num}}</text> -->
					</view>
				</view>
			</view>
			<view class="body_list">
				<text>订单号</text>
				<text>{{detail.ordernum}}</text>
			</view>
			<view class="body_list">
				<text>下单时间</text>
				<text>{{detail.createtime}}</text>
			</view>
			<view class="body_list">
				<text>配送期数</text>
				<text>共 {{detail.qsnum}} 期</text>
			</view>
			<view class="body_list">
				<text>每期数量</text>
				<text>共 {{detail.num}} 件</text>
			</view>
			<view class="body_list">
				<text>配送计划</text>
				<view class="body_time" v-if="detail.status ==4">
					<view>{{detail.start_date}}起<text style="margin-left: 10rpx;" v-if="detail.every_day">{{detail.every_day}}</text><text style="margin-left: 10rpx;" v-else>{{detail.pspl}}</text></view>
					<!-- <img :src="pre_url+'/static/img/week/week_detail.png'" alt="" /> -->
				</view>
				<view v-else class="body_time" @tap="toPlanDetail" :data-url="'/pagesExt/cycle/planList?id=' + detail.id">
					<view>{{detail.start_date}}起<text style="margin-left: 10rpx;" v-if="detail.every_day">{{detail.every_day}}</text><text style="margin-left: 10rpx;" v-else>{{detail.pspl}}</text></view>
					<img :src="pre_url+'/static/img/week/week_detail.png'" alt="" />
				</view>
			</view>
			<view class="body_list">
				<text>配送方式</text>
				<text>{{detail.freight_text}}</text>
			</view>
			
			<view class="body_list">
				<text>商品总金额</text>
				<text class="body_color">￥{{detail.product_price}}</text>
			</view>
			<view class="body_list"  v-if="detail.leveldk_money > 0">
				<text>会员折扣</text>
				<text class="body_color">-￥{{detail.leveldk_money}}</text>
			</view>
			<view class="body_list" v-if="detail.coupon_money > 0">
				<text>优惠券抵扣</text>
				<text class="body_color">-￥{{detail.coupon_money}}</text>
			</view>
			<view class="body_list">
				<text>实付金额</text>
				<text class="body_color">￥{{detail.totalprice}}</text>
			</view>
			<view class="body_list" v-if="detail.status==4">
				<text>订单状态</text>
				<text>已关闭</text>
			</view>
			<view class="body_list" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 body_color" v-if="detail.refund_status==1">审核中,¥{{detail.refund_money}}</text>
				<text class="t2 body_color" v-if="detail.refund_status==2">已退款,¥{{detail.refund_money}}</text>
				<text class="t2 body_color" v-if="detail.refund_status==3">已驳回,¥{{detail.refund_money}}</text>
			</view>
			
			<view class="orderinfo" v-if="(detail.formdata).length > 0">
				
				<view class="item" v-for="item in detail.formdata" :key="index">
					<text class="t1">{{item[0]}}</text>
					<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
					<text class="t2" v-else user-select="true" selectable="true">{{item[1]}}</text>
				</view>
			</view>
		</view>

		<view class="opt" v-if="detail.refund_status!=1">
			<view class="opt_module">
				<view class="opt_btn" @tap="toclose" :data-id="detail.id" v-if="detail.status == 0">关闭订单</view>
				<view class="opt_btn" style="    background: #FF9900;color: #fff;border: none;" v-if="detail.status == 0" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去支付</view>
				<view class="opt_btn" v-if="detail.status == 4" @tap="todel" :data-id="detail.id" >删除订单</view>
				<view class="opt_btn" v-if="detail.status==3 && detail.iscomment==0 && shopset.comment==1" @tap.stop="goto" :data-url="'comment?orderid=' + detail.id">去评价</view>
				<view class="opt_btn" v-if="detail.status==3 && detail.iscomment==1" @tap.stop="goto" :data-url="'comment?orderid=' + detail.id">查看评价</view>
				<view style="width: 100%;" class="flex flex-x-bottom" v-if=" (detail.status == 1 ||  detail.status ==2 )   && (detail.refund_status==3 ||detail.refund_status==0 )">
					<view class="opt_btn"  @tap.stop="goto" :data-url="'refund?orderid=' + detail.id">申请退款</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				isload: false,
				opt:{},
				pre_url: app.globalData.pre_url,
				detail:{},
				djs: '',
				shopset:{},
				storeinfo:[]
			}
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onShow() {
			this.getdata();
		},
		methods: {
			getdata: function () {
				var that = this;
				app.showLoading();
				app.get('ApiCycle/orderdetail', {id: that.opt.id}, function (res) {
					that.detail = res.detail;
					that.shopset = res.shopset;
					that.storeinfo = res.storeinfo;
					if (res.lefttime > 0) {
						interval = setInterval(function () {
							that.lefttime = that.lefttime - 1;
							that.getdjs();
						}, 1000);
					}
					app.showLoading(false);
					that.isload = true;
				});
			},
			toPlanDetail(e){
				var that = this;
				 var url = e.currentTarget.dataset.url;
				if(that.detail.status==0){
					return;
				}else{
					app.goto(url);
				}
			},
			toclose: function (e) {
			  var that = this;
			  var orderid = e.currentTarget.dataset.id;
			  app.confirm('确定要取消该订单吗?', function () {
				app.showLoading('提交中');
			    app.post('ApiCycle/closeOrder', {orderid: orderid}, function (data) {
							app.showLoading(false);
			      app.success(data.msg);
			      setTimeout(function () {
			        that.getdata();
			      }, 1000);
			    });
			  });
			},
			todel: function (e) {
			  var that = this;
			  var orderid = e.currentTarget.dataset.id;
			  app.confirm('确定要删除该订单吗?', function () {
						app.showLoading('删除中');
			    app.post('ApiCycle/delOrder', {orderid: orderid}, function (data) {
							app.showLoading(false);
			      app.success(data.msg);
			      setTimeout(function () {
			        app.goback(true);
			      }, 1000);
			    });
			  });
			},
		}
	}
</script>
<style>
	page {
		background: #F6F6F6;
	}
</style>
<style scoped>
	.banner {
		position: relative;
		width: 100%;
	}

	.banner_img {
		width: 100%;
		display: block;
	}

	.banner_icon {
		position: absolute;
		right: 70rpx;
		top: 30rpx;
		height: 124rpx;
		width: 124rpx;
	}

	.banner_data {
		position: absolute;
		top: 0;
		width: 100%;
		box-sizing: border-box;
		padding: 60rpx 60rpx 0 60rpx;
	}

	.banner_title {
		font-size: 40rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #FFFFFF;
	}

	.banner_text {
		font-size: 26rpx;
		font-family: PingFang SC;
		font-weight: 500;
		color: rgba(255, 255, 255, 0.6);
		margin-top: 28rpx;
	}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999} 

	.body {
		position: relative;
		width: 690rpx;
		box-sizing: border-box;
		padding: 30rpx 30rpx 0 30rpx;
		margin: -235rpx auto 0 auto;
		background: #fff;
		border-radius: 10rpx;
	}

	.body_title {
		font-size: 30rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #111111;
	}

	.body_module {
		padding: 45rpx 0 20rpx 0;
		display: flex;
	}

	.body_data {
		flex: 1;
	}

	.body_img {
		width: 172rpx;
		height: 172rpx;
		border-radius: 10rpx;
		margin-right: 30rpx;
		flex-shrink: 0;
	}

	.body_name {
		font-size: 28rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #323232;
	}

	.body_text {
		font-size: 24rpx;
		font-weight: 500;
		color: #999999;
		margin-top: 15rpx;
	}

	.body_price {
		font-size: 32rpx;
		font-family: Arial;
		font-weight: bold;
		color: #FD4A46;
		margin-top: 30rpx;
	}

	.body_tag {
		font-size: 20rpx;
		font-family: PingFang SC;
		font-weight: 500;
		color: #FD4A46;
	}

	.body_num {
		font-size: 28rpx;
		font-family: PingFang SC;
		font-weight: 500;
		color: #222222;
	}

	.body_list {
		position: relative;
		width: 630rpx;
		height: 88rpx;
		margin: 0 auto;
		font-size: 26rpx;
		font-family: PingFang SC;
		font-weight: 500;
		color: #222222;
		display: flex;
		align-items: center;
		justify-content: space-between;
		border-bottom: 1px solid #f7f7f7;
	}

	.body_list:last-child {
		border-bottom: 0;
	}

	.body_time {
		display: flex;
		align-items: center;
	}

	.body_time img {
		height: 35rpx;
		width: 35rpx;
		margin-left: 15rpx;
	}

	.body_color {
		color: #FF5347;
	}

	.opt {
		position: relative;
		width: 100%;
		height: 105rpx;
		margin-top: 30rpx;
	}

	.opt_module {
		position: fixed;
		height: 105rpx;
		width: 100%;
		background: #fff;
		bottom: 0;
		padding: 0 40rpx;
		box-sizing: border-box;
		display: flex;
		align-items: center;
		justify-content: flex-end;
		box-shadow: 0px 0px 18px 0px rgba(132, 132, 132, 0.3200);
	}

	.opt_btn {
		width: 160rpx;
		height: 60rpx;
		border-radius: 8rpx;
		font-size: 24rpx;
		font-family: PingFang SC;
		font-weight: bold;
		color: #333;
		background: #fff;
		text-align: center;
		line-height: 60rpx;
		margin-left: 10rpx;
		border: 1px solid #cdcdcd
	}
.orderinfo{width:100%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 0;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0;font-size: 26rpx}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .t3{ margin-top: 3rpx;}
.orderinfo .item .red{color:red}

/* 	.opt_btn:last-child {
		color: #fff;
		background: #FD4A46;
	} */
</style>
