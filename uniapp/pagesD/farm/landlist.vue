<template>
<view class="container">
	<block v-if="isload">
		<!-- 顶部导航 -->
		<view class="top-nav">
			<view class="nav-item" :class="st==2?'active':''" @tap="changeStatus" data-status="2">
				<text>全部</text>
			</view>
			<view class="nav-item" :class="st==1?'active':''" @tap="changeStatus" data-status="1">
				<text>已种植</text>
			</view>
			<view class="nav-item" :class="st==0?'active':''" @tap="changeStatus" data-status="0">
				<text>未种植</text>
			</view>
		</view>
		
		<block>
			<!-- 女王地列表 -->
			<view class="farm-list">
				<view class="item" v-for="(item, index) in datalist" :key="item.id">
					<text class="num">{{item.sort}}号</text>
					<view class="media">
						<image class="icon" :src="item.logo" mode="widthFix" style="max-width: 100rpx;;" />
						<text class="label">{{item.name}}</text>
						<text class="label" style="font-size: 16rpx;">(ID:{{item.id}})</text>
					</view>
					<block v-if="item.tree_status==1">
						<view class="progress-bar">
							<image class="background" :src="`${pre_url}/static/img/farm/jdt_bg.png`" mode="widthFix"/>
							<view class="inner">
								<view class="clip-box" :style="{width:`${item.tree_progress}%`,backgroundImage:`url(${pre_url}/static/img/farm/jdt.png)`}"></view>
							</view>
						</view>
						<text class="progress">{{item.tree_progress}}%</text>
					</block>
					<block v-if="item.tree_status!=1">
						<view class="progress-bar"></view>
					</block>
					<text class="button" v-if="item.tree_status==1" @tap="goto" :data-url="'/pagesD/farm/landdetail?id='+item.id" >进入</text>
					<text class="button" v-else @tap="addtree" :data-id="item.id" >种植</text>
				</view>
			</view>
		</block>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			menuindex: -1,
			pre_url: app.globalData.pre_url,
			currentTab: 0, // 0-女王地 1-摇钱树 2-兑换
			st: 2, // 1-已种植 0-未种植 2-全部
			datalist: [],
			land_count:0,
			farm_textset:{},
			pagenum: 1,
			nodata: false,
			nomore: false,
			fert_types:{},//可兑换的数据
			selectedItem: {},//选中的兑换项
			exchangeQuantity: 1,//兑换数量
			totalScore:0,//购买需要的积分数量
		};
	},

	onLoad: function(opt) {
		this.opt = app.getopts(opt);
		this.currentTab = this.opt.tab_st || 0;
		this.getdata();
	},
	
	onPullDownRefresh: function() {
		this.getdata();
	},
	
	onReachBottom: function() {
		if (!this.nodata && !this.nomore) {
			this.pagenum = this.pagenum + 1;
			if(this.currentTab==0){
				this.getdata(true);
			}
		}
	},
	
	methods: {
		getdata: function(loadmore) {
			if (!loadmore) {
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
			app.post('ApiFarm/land_list', {
				pagenum: pagenum,
				st: that.st
			}, function(res) {
				that.loading = false;
				var data = res.data;
				if (pagenum == 1) {
					that.datalist = data;
					if (data.length == 0) {
						that.nodata = true;
					}
					that.land_count = res.count;
					that.farm_textset = res.farm_textset;
					that.loaded();
				} else {
					if (data.length == 0) {
						that.nomore = true;
					} else {
						var datalist = that.datalist;
						var newdata = datalist.concat(data);
						that.datalist = newdata;
					}
				}
			});
		},
		addtree:function(e){
			var that = this;
			var land_id = e.currentTarget.dataset.id;
			app.confirm('确定要种植吗？', function() {
				that.loading = true;
				app.post('ApiFarm/add_tree', {land_id:land_id}, function (res) {
					that.loading = false;
					if(res.status==0){
						app.error(res.msg);
						return;
					}
					app.success('操作成功');
					setTimeout(() => {
						that.getdata();
					}, 1000)
				});
			})
		},
		changeTab: function(e) {
			var tab = e.currentTarget.dataset.tab;
			this.currentTab = parseInt(tab);
			if (tab == 0) {
				// 女王地
				this.getdata();
			} else if (tab == 1) {
				// 摇钱树
				// app.goto('/pagesD/farm/tree');
			} else if (tab == 2) {
				// 兑换
				this.getdata();
				// app.goto('/pagesD/farm/exchange');
			}
		},
		
		changeStatus: function(e) {
			var status = e.currentTarget.dataset.status;
			this.st = parseInt(status);
			this.getdata();
		},
	},

};
</script>

<style>
.container {
	min-height: 100vh;
	background: #f5f5f5;
}

/* 顶部导航 */
.top-nav {
	width: 94%;
	margin: 20rpx 3%;
	background: linear-gradient(135deg, #F5A623 0%, #F8B739 100%);
	border-radius: 20rpx;
	padding: 30rpx 40rpx;
	display: flex;
	justify-content: space-around;
	align-items: center;
}

.nav-item {
	flex: 1;
	text-align: center;
	font-size: 32rpx;
	color: #FFFFFF;
	font-weight: bold;
	opacity: 0.7;
	transition: all 0.3s;
}

.nav-item.active {
	opacity: 1;
	font-size: 36rpx;
}

/* 已种植/未种植 tab */
.status-tab {
	width: 94%;
	margin: 20rpx 3%;
	display: flex;
	justify-content: center;
	align-items: center;
}

.tab-item {
	padding: 10rpx 40rpx;
	font-size: 28rpx;
	color: #333333;
	margin: 0 20rpx;
	position: relative;
}

.tab-item.active {
	font-weight: bold;
	color: #000000;
}

.tab-item.active::after {
	content: '';
	position: absolute;
	bottom: -10rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 40rpx;
	height: 4rpx;
	background: #000000;
	border-radius: 2rpx;
}

/* 女王地列表 */
.farm-list {
	width: 94%;
	margin: 20rpx 3%;
	display: flex;
	flex-direction: column;
}

.farm-list .item {
	width: 100%;
	display: flex;
	flex-direction: row;
	align-items: center;
	background: #e4e4e4;
	padding: 20rpx;
	box-sizing: border-box;
	border-radius: 32rpx;
	margin-bottom: 20rpx;
}

.farm-list .item .num {
	width: 90rpx;
}

.farm-list .item .media {
	width: 100rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.farm-list .item .media .icon {
	width: 100%;
}

.farm-list .item .progress-bar {
	margin: 0 10rpx;
	flex-grow: 1;
	position: relative;
}

.farm-list .item .progress-bar .background {
	width: 100%;
}

.farm-list .item .progress-bar .inner {
	position: absolute;
	inset: 20% 4.3% 35% 4.3%;
}

.farm-list .item .progress-bar .inner .clip-box {
	width: 100%;
	height: 100%;
	background-repeat: no-repeat;
	background-size: cover;
}

.farm-list .item .progress {
	width: 80rpx;
}

.farm-list .item .button {
	width: 120rpx;
	background: #fd4a46;
	color: #fff;
	border-radius: 99999rpx;
	text-align: center;
	padding: 10rpx;
	box-sizing: border-box;
}

/* 兑换列表样式 */
.exchange-list {
	width: 94%;
	margin: 20rpx 3%;
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}

.exchange-item {
	background: #FFFFFF;
	border-radius: 20rpx;
	padding: 30rpx;
	display: flex;
	flex-direction: row;
	justify-content: space-between;
	align-items: center;
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
}

.item-left {
	display: flex;
	flex-direction: row;
	align-items: center;
	flex: 1;
}

.item-logo {
	width: 120rpx;
	height: 120rpx;
	border-radius: 16rpx;
	margin-right: 24rpx;
	object-fit: cover;
}

.item-info {
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}

.item-name {
	font-size: 32rpx;
	font-weight: bold;
	color: #333333;
}

.item-owned {
	font-size: 24rpx;
	color: #999999;
}

.item-right {
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	gap: 16rpx;
}

.item-score {
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	gap: 4rpx;
}

.score-label {
	font-size: 24rpx;
	color: #999999;
}

.score-value {
	font-size: 32rpx;
	font-weight: bold;
	color: #F5A623;
}

.exchange-btn {
	background: linear-gradient(135deg, #F5A623 0%, #F8B739 100%);
	color: #FFFFFF;
	font-size: 28rpx;
	font-weight: bold;
	padding: 12rpx 40rpx;
	border-radius: 40rpx;
	box-shadow: 0 4rpx 12rpx rgba(245, 166, 35, 0.3);
}

/* 兑换弹窗样式 */
.exchange-popup {
	width: 600rpx;
	background: #FFFFFF;
	border-radius: 24rpx;
	padding: 40rpx;
	box-sizing: border-box;
}

.popup-title {
	font-size: 36rpx;
	font-weight: bold;
	color: #333333;
	text-align: center;
	margin-bottom: 40rpx;
}

.popup-content {
	display: flex;
	flex-direction: column;
	gap: 30rpx;
	margin-bottom: 40rpx;
}

.score-info {
	display: flex;
	flex-direction: row;
	justify-content: space-between;
	align-items: center;
	padding: 20rpx;
	background: #F5F5F5;
	border-radius: 12rpx;
}

.info-label {
	font-size: 28rpx;
	color: #666666;
}

.info-value {
	font-size: 32rpx;
	font-weight: bold;
	color: #F5A623;
}

.quantity-selector {
	display: flex;
	flex-direction: row;
	justify-content: space-between;
	align-items: center;
}

.selector-label {
	font-size: 28rpx;
	color: #666666;
}

.selector-control {
	display: flex;
	flex-direction: row;
	align-items: center;
	gap: 20rpx;
}

.control-btn {
	width: 60rpx;
	height: 60rpx;
	background: #F5F5F5;
	border-radius: 12rpx;
	font-size: 40rpx;
	color: #333333;
	display: flex;
	align-items: center;
	justify-content: center;
	text-align: center;
	line-height: 60rpx;
}

.quantity-input {
	width: 120rpx;
	height: 60rpx;
	background: #F5F5F5;
	border-radius: 12rpx;
	text-align: center;
	font-size: 32rpx;
	color: #333333;
}

.total-score {
	display: flex;
	flex-direction: row;
	justify-content: space-between;
	align-items: center;
	padding: 20rpx;
	background: #FFF7E6;
	border-radius: 12rpx;
	border: 2rpx solid #F5A623;
}

.total-label {
	font-size: 28rpx;
	color: #666666;
}

.total-value {
	font-size: 36rpx;
	font-weight: bold;
	color: #F5A623;
}

.popup-actions {
	display: flex;
	flex-direction: row;
	gap: 20rpx;
}

.action-btn {
	flex: 1;
	height: 88rpx;
	border-radius: 44rpx;
	font-size: 32rpx;
	font-weight: bold;
	display: flex;
	align-items: center;
	justify-content: center;
	text-align: center;
	line-height: 88rpx;
}

.action-btn.cancel {
	background: #F5F5F5;
	color: #666666;
}

.action-btn.confirm {
	background: linear-gradient(135deg, #F5A623 0%, #F8B739 100%);
	color: #FFFFFF;
	box-shadow: 0 4rpx 12rpx rgba(245, 166, 35, 0.3);
}
</style>
