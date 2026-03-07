<template>
<view class="container">
	<!-- Tab栏 -->
	<view class="tab-bar">
		<view 
			v-for="(tab, idx) in tabs" 
			:key="idx" 
			:class="['tab-item', currentTab == idx ? 'active' : '']"
			@tap="switchTab(idx)"
		>
			<text>{{tab.name}}</text>
		</view>
	</view>
	
	<!-- 类型筛选 -->
	<view class="type-filter">
		<view :class="['filter-item', generationType == 0 ? 'active' : '']" @tap="changeType(0)">全部</view>
		<view :class="['filter-item', generationType == 1 ? 'active' : '']" @tap="changeType(1)">照片</view>
		<view :class="['filter-item', generationType == 2 ? 'active' : '']" @tap="changeType(2)">视频</view>
	</view>
	
	<!-- 订单列表 -->
	<view class="order-list">
		<view v-for="(item, idx) in list" :key="idx" class="order-card" @tap="goDetail(item.id)">
			<view class="order-header">
				<text class="ordernum">订单号: {{item.ordernum}}</text>
				<text :class="['status-tag', getStatusClass(item)]">{{getStatusText(item)}}</text>
			</view>
			<view class="order-body">
				<image :src="item.cover_image || '/static/img/placeholder.png'" class="scene-cover" mode="aspectFill"></image>
				<view class="order-info">
					<text class="scene-name">{{item.scene_name || item.template_name || '场景模板'}}</text>
					<text class="type-label">{{item.generation_type == 1 ? '照片生成' : '视频生成'}}</text>
					<text class="price">¥{{item.pay_price}}</text>
				</view>
			</view>
			<view class="order-footer">
				<text class="time">{{item.createtime_text}}</text>
				<view class="actions">
					<view v-if="item.pay_status == 0" class="btn btn-primary" @tap.stop="goPay(item)">去支付</view>
					<view v-if="item.can_refund" class="btn btn-outline" @tap.stop="goRefund(item)">申请退款</view>
					<view v-if="item.refund_status == 1" class="btn btn-outline" @tap.stop="cancelRefund(item)">撤销退款</view>
				</view>
			</view>
		</view>
		
		<!-- 空状态 -->
		<view v-if="isload && list.length == 0" class="empty-state">
			<image src="/static/img/empty.png" class="empty-icon"></image>
			<text class="empty-text">暂无订单</text>
		</view>
	</view>
	
	<!-- 加载状态 -->
	<loading v-if="loading"></loading>
	<view v-if="nomore && list.length > 0" class="nomore">没有更多了</view>
	
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			nomore: false,
			page: 1,
			list: [],
			tabs: [
				{ name: '全部', status: -1 },
				{ name: '待支付', status: 0 },
				{ name: '生成中', status: 1 },
				{ name: '已完成', status: 2 },
				{ name: '退款', status: 3 }
			],
			currentTab: 0,
			generationType: 0
		};
	},
	
	onLoad(opt) {
		this.opt = app.getopts(opt);
		if (opt.tab) {
			this.currentTab = parseInt(opt.tab);
		}
		if (opt.type) {
			this.generationType = parseInt(opt.type);
		}
		this.getdata();
	},
	
	onPullDownRefresh() {
		this.page = 1;
		this.nomore = false;
		this.list = [];
		this.getdata();
	},
	
	onReachBottom() {
		if (!this.nomore) {
			this.getdata();
		}
	},
	
	methods: {
		getdata() {
			var that = this;
			if (that.loading) return;
			that.loading = true;
			
			var params = {
				generation_type: that.generationType,
				status: that.tabs[that.currentTab].status,
				page: that.page,
				limit: 20
			};
			
			app.get('ApiAivideo/generation_order_list', params, function(res) {
				that.loading = false;
				that.isload = true;
				uni.stopPullDownRefresh();
				
				if (res.status == 1) {
					var newList = res.data.list || [];
					// 格式化数据
					newList.forEach(function(item) {
						item.can_refund = (item.pay_status == 1 && item.task_status == 3 && (item.refund_status == 0 || item.refund_status == 3));
					});
					
					if (that.page == 1) {
						that.list = newList;
					} else {
						that.list = that.list.concat(newList);
					}
					
					if (newList.length < 20) {
						that.nomore = true;
					} else {
						that.page++;
					}
				} else {
					app.alert(res.msg);
				}
			});
		},
		
		switchTab(idx) {
			if (this.currentTab == idx) return;
			this.currentTab = idx;
			this.page = 1;
			this.nomore = false;
			this.list = [];
			this.getdata();
		},
		
		changeType(type) {
			if (this.generationType == type) return;
			this.generationType = type;
			this.page = 1;
			this.nomore = false;
			this.list = [];
			this.getdata();
		},
		
		getStatusClass(item) {
			if (item.refund_status == 1) return 'warning';
			if (item.refund_status == 2) return 'info';
			if (item.pay_status == 0) return 'warning';
			if (item.task_status == 3) return 'danger';
			if (item.task_status == 2) return 'success';
			if (item.task_status == 1) return 'info';
			return 'default';
		},
		
		getStatusText(item) {
			if (item.refund_status == 1) return '退款审核中';
			if (item.refund_status == 2) return '已退款';
			if (item.refund_status == 3) return '退款已驳回';
			if (item.pay_status == 0) return '待支付';
			if (item.pay_status == 2) return '已取消';
			if (item.task_status == 0) return '待处理';
			if (item.task_status == 1) return '生成中';
			if (item.task_status == 2) return '已完成';
			if (item.task_status == 3) return '生成失败';
			return '未知';
		},
		
		goDetail(id) {
			uni.navigateTo({
				url: '/pagesZ/generation/orderdetail?id=' + id
			});
		},
		
		goPay(item) {
			// 跳转到支付页面
			uni.navigateTo({
				url: '/pages/pay/pay?ordernum=' + item.ordernum + '&tablename=generation'
			});
		},
		
		goRefund(item) {
			uni.navigateTo({
				url: '/pagesZ/generation/refundApply?id=' + item.id
			});
		},
		
		cancelRefund(item) {
			var that = this;
			app.confirm('确定要撤销退款申请吗？', function() {
				app.showLoading('处理中');
				app.post('ApiAivideo/generation_refund_cancel', { order_id: item.id }, function(res) {
					app.showLoading(false);
					if (res.status == 1) {
						app.success('撤销成功');
						that.page = 1;
						that.nomore = false;
						that.list = [];
						that.getdata();
					} else {
						app.alert(res.msg);
					}
				});
			});
		}
	}
};
</script>

<style>
.container { background: #f5f5f5; min-height: 100vh; padding-bottom: 120rpx; }

.tab-bar { display: flex; background: #fff; padding: 20rpx 0; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 100; }
.tab-item { flex: 1; text-align: center; font-size: 28rpx; color: #666; padding: 10rpx 0; position: relative; }
.tab-item.active { color: #FF6B00; font-weight: bold; }
.tab-item.active::after { content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%); width: 40rpx; height: 4rpx; background: #FF6B00; border-radius: 2rpx; }

.type-filter { display: flex; background: #fff; padding: 16rpx 30rpx; gap: 20rpx; margin-bottom: 20rpx; }
.filter-item { padding: 10rpx 30rpx; font-size: 26rpx; color: #666; background: #f5f5f5; border-radius: 30rpx; }
.filter-item.active { background: #FFF0E5; color: #FF6B00; }

.order-list { padding: 0 20rpx; }
.order-card { background: #fff; border-radius: 16rpx; margin-bottom: 20rpx; overflow: hidden; }

.order-header { display: flex; justify-content: space-between; align-items: center; padding: 20rpx; border-bottom: 1px solid #f5f5f5; }
.ordernum { font-size: 24rpx; color: #999; }
.status-tag { font-size: 24rpx; padding: 4rpx 16rpx; border-radius: 20rpx; }
.status-tag.success { background: #E8F5E9; color: #4CAF50; }
.status-tag.danger { background: #FFEBEE; color: #F44336; }
.status-tag.warning { background: #FFF3E0; color: #FF9800; }
.status-tag.info { background: #E3F2FD; color: #2196F3; }
.status-tag.default { background: #F5F5F5; color: #9E9E9E; }

.order-body { display: flex; padding: 20rpx; }
.scene-cover { width: 160rpx; height: 160rpx; border-radius: 12rpx; flex-shrink: 0; background: #f5f5f5; }
.order-info { flex: 1; margin-left: 20rpx; display: flex; flex-direction: column; justify-content: space-between; }
.scene-name { font-size: 28rpx; color: #333; line-height: 1.4; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; }
.type-label { font-size: 24rpx; color: #999; }
.price { font-size: 32rpx; color: #FF6B00; font-weight: bold; }

.order-footer { display: flex; justify-content: space-between; align-items: center; padding: 20rpx; border-top: 1px solid #f5f5f5; }
.time { font-size: 24rpx; color: #999; }
.actions { display: flex; gap: 16rpx; }
.btn { padding: 10rpx 30rpx; font-size: 26rpx; border-radius: 30rpx; }
.btn-primary { background: #FF6B00; color: #fff; }
.btn-outline { background: #fff; color: #FF6B00; border: 1px solid #FF6B00; }

.empty-state { display: flex; flex-direction: column; align-items: center; padding: 100rpx 0; }
.empty-icon { width: 200rpx; height: 200rpx; opacity: 0.5; }
.empty-text { font-size: 28rpx; color: #999; margin-top: 20rpx; }

.nomore { text-align: center; padding: 30rpx; font-size: 26rpx; color: #999; }
</style>
