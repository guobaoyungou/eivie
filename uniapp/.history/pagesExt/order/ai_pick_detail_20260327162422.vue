<template>
<view class="container">
	<block v-if="isload">
		<!-- 订单状态头部 -->
		<view class="ordertop" :style="'background:' + t('color1')">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
				<view class="t2">请尽快完成支付</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">已成功付款</view>
				<view class="t2">感谢您的购买</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">订单已完成</view>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">订单已关闭</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">订单已退款</view>
			</view>
		</view>

		<!-- 订单信息 -->
		<view class="orderinfo">
			<view class="title">订单信息</view>
			<view class="item">
				<text class="t1">订单号</text>
				<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item">
				<text class="t1">订单金额</text>
				<text class="t2 price">￥{{detail.totalprice}}</text>
			</view>
		</view>

		<!-- 商品清单 -->
		<view class="product">
			<view class="product-title">片源清单</view>
			<view v-for="(item, idx) in prolist" :key="idx" class="box">
				<view class="content">
					<image v-if="item.pic" :src="item.pic" mode="aspectFill"></image>
					<view class="detail">
						<text class="t1">{{item.name}}</text>
						<view class="t3">
							<text class="x1 flex1">￥{{item.sell_price}}</text>
							<text class="x2">×{{item.num}}</text>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 底部操作按钮 -->
		<view class="bottom-bar">
			<!-- 待付款状态 -->
			<block v-if="detail.status==0">
				<view class="btn btn-cancel" @tap="closeOrder">关闭订单</view>
				<view class="btn btn-pay" :style="{background: t('color1')}" @tap="goPay">去付款</view>
			</block>
			<!-- 已完成状态 -->
			<block v-if="detail.status==1">
				<view class="btn btn-default" @tap="viewResults">查看成片</view>
			</block>
		</view>
	</block>
	<loading v-if="!isload && loading"></loading>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();

export default {
	data() {
		return {
			opt: {},
			loading: false,
			isload: false,
			pre_url: app.globalData.pre_url,
			detail: {},
			prolist: []
		};
	},

	onLoad: function(opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},

	methods: {
		getdata: function() {
			var that = this;
			that.loading = true;

			app.post('ApiUnifiedOrder/detail', {
				id: that.opt.id
			}, function(res) {
				that.loading = false;
				if (res.status == 1) {
					that.detail = res.data;
					that.prolist = res.data.prolist || [];
					that.isload = true;
					
					// 如果是从订单列表点击“去付款”跳转过来的，且订单状态为待付款，则自动跳转到支付页
					if (that.opt.auto_pay == 1 && res.data.status == 0 && res.data.payorderid) {
						console.log('[ai_pick_detail] 自动跳转到支付页，payorderid:', res.data.payorderid);
						setTimeout(function() {
							app.goto('/pagesExt/pay/pay?id=' + res.data.payorderid, 'redirectTo');
						}, 300);
					}
				} else {
					that.$refs.popmsg.show({
						content: res.msg || '订单不存在',
						confirm: function() {
							app.goback();
						}
					});
				}
			});
		},

		// 关闭订单
		closeOrder: function() {
			var that = this;
			that.$refs.popmsg.show({
				content: '确定要关闭订单吗？',
				showcancel: true,
				confirm: function() {
					app.post('ApiUnifiedOrder/closeOrder', {
						id: that.detail.id
					}, function(res) {
						if (res.status == 1) {
							that.$refs.popmsg.show({
								content: '订单已关闭',
								confirm: function() {
									app.goback();
								}
							});
						} else {
							that.$refs.popmsg.show({
								content: res.msg || '关闭失败'
							});
						}
					});
				}
			});
		},

		// 去付款
		goPay: function() {
			if (this.detail.payorderid) {
				app.goto('/pagesExt/pay/pay?id=' + this.detail.payorderid);
			} else {
				this.$refs.popmsg.show({
					content: '支付订单不存在'
				});
			}
		},

		// 查看成片
		viewResults: function() {
			// 这里可以跳转到成片查看页面
			// 暂时使用提示
			this.$refs.popmsg.show({
				content: '查看成片功能开发中'
			});
		},

		t: app.t
	}
};
</script>

<style>
.container {
	width: 100%;
	background: #f5f5f5;
	min-height: 100vh;
	padding-bottom: 120rpx;
}

.ordertop {
	width: 100%;
	padding: 40rpx 3%;
	color: #fff;
}
.ordertop .f1 {
	text-align: center;
}
.ordertop .t1 {
	font-size: 32rpx;
	font-weight: bold;
}
.ordertop .t2 {
	font-size: 24rpx;
	margin-top: 10rpx;
	opacity: 0.8;
}

.orderinfo {
	width: 94%;
	margin: 20rpx 3%;
	background: #fff;
	border-radius: 8rpx;
	padding: 20rpx;
}
.orderinfo .title {
	font-size: 28rpx;
	font-weight: bold;
	padding-bottom: 20rpx;
	border-bottom: 1px solid #f0f0f0;
}
.orderinfo .item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20rpx 0;
	border-bottom: 1px solid #f8f8f8;
}
.orderinfo .item:last-child {
	border-bottom: none;
}
.orderinfo .t1 {
	font-size: 26rpx;
	color: #666;
}
.orderinfo .t2 {
	font-size: 26rpx;
	color: #333;
}
.orderinfo .price {
	color: #ff5722;
	font-weight: bold;
}

.product {
	width: 94%;
	margin: 20rpx 3%;
	background: #fff;
	border-radius: 8rpx;
	padding: 20rpx;
}
.product-title {
	font-size: 28rpx;
	font-weight: bold;
	padding-bottom: 20rpx;
	border-bottom: 1px solid #f0f0f0;
}
.product .box {
	padding: 20rpx 0;
	border-bottom: 1px solid #f8f8f8;
}
.product .box:last-child {
	border-bottom: none;
}
.product .content {
	display: flex;
	align-items: center;
}
.product .content image {
	width: 140rpx;
	height: 140rpx;
	border-radius: 8rpx;
	flex-shrink: 0;
}
.product .detail {
	flex: 1;
	margin-left: 20rpx;
}
.product .t1 {
	font-size: 26rpx;
	color: #333;
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
}
.product .t3 {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-top: 20rpx;
}
.product .x1 {
	font-size: 28rpx;
	color: #ff5722;
	font-weight: bold;
}
.product .x2 {
	font-size: 24rpx;
	color: #999;
}

.bottom-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	background: #fff;
	padding: 20rpx 3%;
	border-top: 1px solid #f0f0f0;
	display: flex;
	justify-content: flex-end;
	align-items: center;
	box-shadow: 0 -2px 10rpx rgba(0,0,0,0.05);
	z-index: 100;
}
.btn {
	padding: 0 30rpx;
	height: 60rpx;
	line-height: 60rpx;
	border-radius: 30rpx;
	font-size: 26rpx;
	margin-left: 20rpx;
	text-align: center;
}
.btn-cancel {
	border: 1px solid #ddd;
	color: #666;
	background: #fff;
}
.btn-pay,
.btn-default {
	color: #fff;
}
</style>
