<template>
	<view v-if="isload">
		<view class="top-view flex-col">
			<view class="top-data-list flex flex-y-center">
				<view class="data-options flex-col">
					<view class="title-text">本月新增业绩</view>
					<view class="num-text">{{total.month_yeji || 0}}</view>
				</view>
				<view class="line-class"></view>
				<view class="data-options flex-col">
					<view class="title-text">总业绩</view>
					<view class="num-text">{{total.total_yeji || 0}}</view>
				</view>
			</view>
		</view>
		<block v-for="(item,index) in datalist">
			<view class="options-view">
				<view class="left-view flex-col">
					<view class='left-title'>{{item.sendmonth}}</view>
					<view class="flex flex-wrap">
						<view class="time-text flex-1">新增业绩：{{item.add_yeji}}</view>
						<view class="time-text flex-1">总业绩：{{item.team_yeji}}</view>
					</view>
					<view class="flex">
						<view class="time-text flex-1">业绩奖励：{{item.commission}}</view>
					</view>
				</view>
			</view>
		</block>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
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
				isload: true,
				pre_url: app.globalData.pre_url,
				nodata: false,
				nomore: false,
				loading: false,
				menuindex: -1,
				datalist: [],
				pagenum: 1,
				info: {},
				total:[]
			}
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.id = this.opt.id || 0;
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		onReachBottom: function() {
			if (!this.nodata && !this.nomore) {
				this.pagenum = this.pagenum + 1;
				this.getdata(true);
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
				app.post('ApiAgent/getMemberTeamYejiLog', {pagenum: pagenum}, function(res) {
					that.loading = false;
					var data = res.data;
					if (pagenum == 1) {
						that.datalist = data;
						if (data.length == 0) {
							that.nodata = true;
						}
						that.total = res.total;
						that.loaded();
					} else {
						if (data.length == 0) {
							that.nomore = true;
						} else {
							var datalist = that.data;
							var newdata = datalist.concat(data);
							that.datalist = newdata;
						}
					}
				});
			},
		}
	}
</script>

<style>
	.top-view {
		width: 100%;
		background: #FC2D41;
		align-items: center;
	}

	.top-data-list {
		width: 100%;
		padding: 35rpx 0rpx;
		justify-content: center;
	}

	.top-data-list .data-options {
		text-align: center;
		max-width: 48%;
		width: auto;
		min-width: 45%;
	}

	.top-data-list .line-class {
		height: 50rpx;
		border-left: 1rpx #e5d734 solid;
	}

	.top-data-list .data-options .title-text {
		font-size: 20rpx;
		color: rgba(255, 255, 255, .8);
		font-weight: bold;
		white-space: nowrap;
	}

	.top-data-list .data-options .num-text {
		font-size: 34rpx;
		color: #ecdd36;
		margin-top: 15rpx;
	}

	.options-view {
		background: #fff;
		border-bottom: 1px #f6f6f6 solid;
		padding: 30rpx 20rpx;
		align-items: center;
	}

	.options-view .left-view .left-title {
		font-size: 26rpx;
		color: #333;
		font-weight: bold;
		white-space: nowrap;
	}

	.options-view .left-view .time-text {
		font-size: 24rpx;
		margin-top: 20rpx;
		color: #828282;
		width: 50%;
	}
</style>