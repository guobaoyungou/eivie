<template>
	<view class="unified-order-card" @tap="goDetail">
		<!-- 顶部栏 -->
		<view class="card-head">
			<view class="head-left">
				<text class="type-tag" :style="{background: tagColor}">{{item.order_type_name}}</text>
				<view class="order-info">
					<text class="ordernum">{{item.ordernum}}</text>
					<text class="create-time" v-if="item.create_time">{{item.create_time}}</text>
				</view>
			</view>
			<text class="status-text" :class="'st' + item.status">{{item.status_text}}</text>
		</view>
		<!-- 内容区 -->
		<view class="card-content">
			<image v-if="item.cover_image" :src="item.cover_image" mode="aspectFill" class="cover-img"></image>
			<view class="goods-info">
				<text class="goods-name">{{item.title}}</text>
				<text class="goods-desc" v-if="item.extra_info && item.extra_info.buy_type">{{buyTypeText}}</text>
				<text class="goods-desc" v-else>共{{item.item_count}}件商品</text>
			</view>
		</view>
		<!-- AI选片订单的片源清单（默认折叠） -->
		<block v-if="item.order_type === 'ai_pick' && item.prolist && item.prolist.length > 0">
			<view class="goods-list-toggle" @tap.stop="toggleGoodsList">
				<text class="toggle-text">片源清单({{item.prolist.length}})</text>
				<text class="toggle-icon">{{showGoodsList ? '▲' : '▼'}}</text>
			</view>
			<view v-if="showGoodsList" class="goods-list">
				<view v-for="(goods, gidx) in item.prolist" :key="gidx" class="goods-item">
					<image v-if="goods.pic" :src="goods.pic" mode="aspectFill" class="goods-thumb"></image>
					<view class="goods-item-info">
						<text class="goods-item-name">{{goods.name}}</text>
						<view class="goods-item-bottom">
							<text class="goods-item-price">￥{{goods.sell_price}}</text>
							<text class="goods-item-num">x{{goods.num}}</text>
						</view>
					</view>
				</view>
			</view>
		</block>
		<!-- 底部栏 -->
		<view class="card-bottom">
			<text class="total-info">共计{{item.item_count}}件商品 实付:￥{{item.total_price}}</text>
			<text class="refund-info" v-if="item.refund_status==1">退款中</text>
			<text class="refund-info" v-if="item.refund_status==2">已退款</text>
		</view>
		<!-- 操作区 -->
		<view class="card-op">
			<!-- 待付款状态显示关闭订单和去付款按钮 -->
			<block v-if="item.status == 0">
				<view class="btn-cancel" @tap.stop="closeOrder">关闭订单</view>
				<view class="btn-pay" :style="{background: themeColor}" @tap.stop="goPay">去付款</view>
			</block>
			<!-- 已完成状态且是AI选片订单，根据成片状态显示按钮 -->
			<block v-if="item.status == 1 && item.order_type === 'ai_pick'">
				<view v-if="item.result_status === 'expired'" class="btn-expired">已过期</view>
				<view v-else class="btn-download" :style="{background: themeColor}" @tap.stop="goDownload">去下载</view>
			</block>
			<view class="btn-detail" :style="{background: themeColor}" @tap.stop="goDetail">详情</view>
		</view>
	</view>
</template>

<script>
var app = getApp();

export default {
	name: 'unified-order-card',
	props: {
		item: {
			type: Object,
			default: function() { return {}; }
		}
	},
	data() {
		return {
			showGoodsList: false  // 商品清单默认折叠
		};
	},
	computed: {
		tagColor: function() {
			var map = {
				'shop': '#ff5722',
				'collage': '#e91e63',
				'seckill': '#f44336',
				'tuangou': '#ff9800',
				'kanjia': '#9c27b0',
				'lucky_collage': '#673ab7',
				'scoreshop': '#4caf50',
				'yuyue': '#00bcd4',
				'kecheng': '#2196f3',
				'cycle': '#009688',
				'ai_pick': '#607d8b'
			};
			return map[this.item.order_type] || '#999';
		},
		themeColor: function() {
			return app.globalData.initdata && app.globalData.initdata.color1 ? app.globalData.initdata.color1 : '#ff5722';
		},
		buyTypeText: function() {
			if (!this.item.extra_info) return '';
			var bt = this.item.extra_info.buy_type;
			if (bt == 1) return '标准套餐';
			if (bt == 2) return '精修套餐';
			return '共' + this.item.item_count + '件商品';
		}
	},
	methods: {
		toggleGoodsList: function() {
			this.showGoodsList = !this.showGoodsList;
		},
		goDetail: function() {
			console.log('[unified-order-card] goDetail 被调用, detail_url:', this.item.detail_url);
			if (this.item.detail_url) {
				console.log('[unified-order-card] 跳转到详情页:', this.item.detail_url);
				app.goto(this.item.detail_url);
			} else {
				console.log('[unified-order-card] detail_url 不存在');
			}
		},
		goPay: function() {
			console.log('[unified-order-card] goPay 被调用, order_type:', this.item.order_type);
			// AI选片订单：列表中没有payorderid，需要先跳转到详情页
			if (this.item.order_type === 'ai_pick') {
				console.log('[unified-order-card] AI选片订单，跳转到详情页，然后再去付款');
				// 跳转到详情页，详情页会从后端获取payorderid
				if (this.item.detail_url) {
					// 带上参数，让详情页知道需要自动跳转到支付页
					var url = this.item.detail_url + '&auto_pay=1';
					console.log('[unified-order-card] 跳转到:', url);
					app.goto(url);
				} else {
					console.log('[unified-order-card] detail_url 不存在');
					uni.showToast({
						title: '订单详情页不存在',
						icon: 'none',
						duration: 2000
					});
				}
				return;
			}
			
			// 其他类型订单：直接使用payorderid
			if (!this.item.payorderid || this.item.payorderid == 0) {
				uni.showToast({
					title: '支付订单不存在',
					icon: 'none',
					duration: 2000
				});
				return;
			}
			app.goto('/pagesExt/pay/pay?id=' + this.item.payorderid);
		},
		goDownload: function() {
			if (this.item.download_url) {
				app.goto(this.item.download_url);
			} else if (this.item.detail_url) {
				app.goto(this.item.detail_url);
			}
		},
		// 关闭订单
		closeOrder: function() {
			var that = this;
			uni.showModal({
				title: '提示',
				content: '确定要关闭订单吗？',
				success: function(res) {
					if (res.confirm) {
						app.post('ApiUnifiedOrder/closeOrder', {
							id: that.item.id
						}, function(res) {
							if (res.status == 1) {
								uni.showToast({
									title: '订单已关闭',
									icon: 'success',
									duration: 2000,
									success: function() {
										// 触发父组件刷新列表
										setTimeout(function() {
											that.$emit('refresh');
										}, 1500);
									}
								});
							} else {
								uni.showToast({
									title: res.msg || '关闭失败',
									icon: 'none',
									duration: 2000
								});
							}
						});
					}
				}
			});
		}
	}
};
</script>

<style>
.unified-order-card {
	width: 94%;
	margin: 10rpx 3%;
	padding: 0 3%;
	background: #fff;
	border-radius: 8px;
}
.card-head {
	display: flex;
	width: 100%;
	border-bottom: 1px #f4f4f4 solid;
	height: 70rpx;
	line-height: 70rpx;
	overflow: hidden;
	color: #999;
	justify-content: space-between;
	align-items: center;
}
.head-left {
	display: flex;
	align-items: center;
	flex: 1;
	overflow: hidden;
}
.type-tag {
	font-size: 20rpx;
	color: #fff;
	padding: 4rpx 12rpx;
	border-radius: 4rpx;
	margin-right: 12rpx;
	flex-shrink: 0;
}
.order-info {
	display: flex;
	flex-direction: column;
	flex: 1;
	overflow: hidden;
}
.ordernum {
	font-size: 24rpx;
	color: #333;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	line-height: 32rpx;
}
.create-time {
	font-size: 20rpx;
	color: #999;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	line-height: 28rpx;
}
.status-text {
	font-size: 28rpx;
	flex-shrink: 0;
	margin-left: 10rpx;
}
.status-text.st0 { color: #ff8758; }
.status-text.st1 { color: #ffc702; }
.status-text.st2 { color: #ff4246; }
.status-text.st3 { color: #999; }
.status-text.st4 { color: #bbb; }

.card-content {
	display: flex;
	width: 100%;
	padding: 16rpx 0;
	border-bottom: 1px #f4f4f4 dashed;
	align-items: center;
}
.cover-img {
	width: 140rpx;
	height: 140rpx;
	flex-shrink: 0;
	border-radius: 8rpx;
}
.goods-info {
	display: flex;
	flex-direction: column;
	margin-left: 14rpx;
	flex: 1;
	overflow: hidden;
}
.goods-name {
	font-size: 26rpx;
	line-height: 36rpx;
	margin-bottom: 10rpx;
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
}
.goods-desc {
	font-size: 24rpx;
	color: #999;
	height: 36rpx;
	line-height: 36rpx;
}

/* 商品清单展开/折叠 */
.goods-list-toggle {
	display: flex;
	justify-content: space-between;
	align-items: center;
	width: 100%;
	padding: 20rpx 0;
	border-bottom: 1px #f4f4f4 solid;
	cursor: pointer;
}
.toggle-text {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
}
.toggle-icon {
	font-size: 24rpx;
	color: #999;
}
.goods-list {
	width: 100%;
	padding: 10rpx 0;
	border-bottom: 1px #f4f4f4 dashed;
}
.goods-item {
	display: flex;
	width: 100%;
	padding: 10rpx 0;
	align-items: center;
}
.goods-thumb {
	width: 100rpx;
	height: 100rpx;
	flex-shrink: 0;
	border-radius: 6rpx;
}
.goods-item-info {
	display: flex;
	flex-direction: column;
	margin-left: 14rpx;
	flex: 1;
	overflow: hidden;
}
.goods-item-name {
	font-size: 24rpx;
	line-height: 32rpx;
	color: #333;
	margin-bottom: 8rpx;
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
}
.goods-item-bottom {
	display: flex;
	justify-content: space-between;
	align-items: center;
}
.goods-item-price {
	font-size: 26rpx;
	color: #ff5722;
	font-weight: 500;
}
.goods-item-num {
	font-size: 24rpx;
	color: #999;
}

.card-bottom {
	width: 100%;
	padding: 10rpx 0;
	border-top: 1px #f4f4f4 solid;
	color: #555;
	font-size: 24rpx;
}
.total-info {
	color: #555;
}
.refund-info {
	color: red;
	padding-left: 10rpx;
}

.card-op {
	display: flex;
	flex-wrap: wrap;
	justify-content: flex-end;
	align-items: center;
	width: 100%;
	padding: 10rpx 0;
	border-top: 1px #f4f4f4 solid;
}
.btn-cancel,
.btn-pay,
.btn-download,
.btn-detail {
	margin-left: 20rpx;
	max-width: 160rpx;
	height: 60rpx;
	line-height: 60rpx;
	border-radius: 3px;
	text-align: center;
	padding: 0 20rpx;
	font-size: 26rpx;
}
.btn-cancel {
	color: #666;
	background: #fff;
	border: 1px solid #ddd;
}
.btn-pay,
.btn-download,
.btn-detail {
	color: #fff;
}
.btn-expired {
	margin-left: 20rpx;
	max-width: 160rpx;
	height: 60rpx;
	line-height: 60rpx;
	color: #999;
	border-radius: 3px;
	text-align: center;
	padding: 0 20rpx;
	font-size: 26rpx;
	background: #f0f0f0;
}
</style>
