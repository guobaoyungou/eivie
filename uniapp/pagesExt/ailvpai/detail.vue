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
				<view class="t2-wrap" @tap="copyOrderNum">
					<text class="t2" user-select="true" selectable="true">{{detail.ordernum}}</text>
					<text class="copy-btn">复制</text>
				</view>
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
		<view class="product" v-if="prolist && prolist.length > 0">
			<view class="product-title">片源清单</view>
			<view v-for="(item, idx) in prolist" :key="idx" class="box">
				<view class="content">
					<image v-if="item.pic" :src="item.pic + '?x-oss-process=image/resize,w_200,h_200'" mode="aspectFill"></image>
					<view class="detail-info">
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
			<!-- 已付款状态 -->
			<block v-if="detail.status==1">
				<view v-if="detail.result_status === 'expired'" class="btn btn-expired">已过期</view>
				<view v-else class="btn btn-download" :style="{background: t('color1')}" @tap="goDownload">去下载</view>
			</block>
		</view>
	</block>
	<loading v-if="!isload && loading"></loading>
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

	onPullDownRefresh: function() {
		this.getdata();
	},

	onShow: function() {
		// 从支付页返回时刷新数据
		if (this.isload) {
			this.getdata();
		}
	},

	methods: {
		getdata: function() {
			var that = this;
			that.loading = true;

			app.post('ApiUnifiedOrder/detail', {
				id: that.opt.id
			}, function(res) {
				that.loading = false;
				uni.stopPullDownRefresh();
				if (res.status == 1) {
					that.detail = res.data;
					that.prolist = res.data.prolist || [];
					that.isload = true;
					
					// 调试：打印订单数据
					console.log('[ailvpai/detail] 订单数据:', res.data);
					console.log('[ailvpai/detail] payorderid:', res.data.payorderid);
					console.log('[ailvpai/detail] auto_pay:', that.opt.auto_pay);

					// 如果是从订单列表点击"去付款"跳转过来的，且订单状态为待付款，则自动跳转到支付页
					if (that.opt.auto_pay === '1' && res.data.status == 0 && res.data.payorderid) {
						console.log('[ailvpai/detail] 满足条件，自动跳转到支付页');
						// 清除auto_pay标记，防止返回时再次自动跳转
						that.opt.auto_pay = '0';
						setTimeout(function() {
							app.goto('/pagesExt/pay/pay?id=' + res.data.payorderid, 'redirectTo');
						}, 300);
					}
				} else {
					uni.showModal({
						title: '提示',
						content: res.msg || '订单不存在',
						showCancel: false,
						success: function() {
							app.goback();
						}
					});
				}
			});
		},

		// 复制订单号
		copyOrderNum: function() {
			var that = this;
			uni.setClipboardData({
				data: that.detail.ordernum,
				success: function() {
					uni.showToast({
						title: '已复制订单号',
						icon: 'success'
					});
				}
			});
		},

		// 关闭订单
		closeOrder: function() {
			var that = this;
			uni.showModal({
				title: '提示',
				content: '确定要关闭订单吗？',
				success: function(modalRes) {
					if (modalRes.confirm) {
						app.post('ApiUnifiedOrder/closeOrder', {
							id: that.detail.id
						}, function(res) {
							if (res.status == 1) {
								uni.showModal({
									title: '提示',
									content: '订单已关闭',
									showCancel: false,
									success: function() {
										app.goback();
									}
								});
							} else {
								uni.showToast({
									title: res.msg || '关闭失败',
									icon: 'none'
								});
							}
						});
					}
				}
			});
		},

		// 去付款
		goPay: function() {
			console.log('[ailvpai/detail] goPay 被调用');
			console.log('[ailvpai/detail] detail.payorderid:', this.detail.payorderid);
			if (this.detail.payorderid) {
				app.goto('/pagesExt/pay/pay?id=' + this.detail.payorderid);
			} else {
				uni.showToast({
					title: '支付订单不存在',
					icon: 'none'
				});
			}
		},

		// 去下载
		goDownload: function() {
			app.goto('/pagesExt/ailvpai/download?id=' + this.detail.id);
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
	flex-shrink: 0;
}
.orderinfo .t2-wrap {
	display: flex;
	align-items: center;
}
.orderinfo .t2 {
	font-size: 26rpx;
	color: #333;
}
.orderinfo .copy-btn {
	font-size: 22rpx;
	color: #999;
	border: 1px solid #ddd;
	border-radius: 4rpx;
	padding: 2rpx 10rpx;
	margin-left: 10rpx;
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
.product .detail-info {
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
.btn-download {
	color: #fff;
}
.btn-expired {
	background: #f0f0f0;
	color: #999;
}
</style>
