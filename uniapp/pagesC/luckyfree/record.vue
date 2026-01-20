<template>
	<view class="container">
		<block v-if="isload">
			<!-- 列表 -->
			<view class="content" id="datalist">
				<view class="list-item" v-for="(item, index) in datalist" :key="index">
					<view class="item-row" style="margin-bottom: 40rpx;">
						<view>
							<text class="tag" :style="{backgroundColor:t('color1')}" v-if="activity.business">{{activity.business}}免单</text>
							<text class="tag" :style="{backgroundColor:t('color1')}" v-else>平台免单</text>
						</view>
						<view style="font-weight: bold;">第 {{item.ranking}}名</view>
						<view style="font-weight: bold;" v-if="item.status == 0">到达 {{item.opennum}}人开奖</view>
						<view style="font-weight: bold;" v-else>
							<text v-if="item.is_winner" style="color: red;">已中奖</text>
							<text v-else style="color: grey;">未中奖</text>
						</view>
					</view>
					<view class="item-row-text"><text>活动名称</text>：{{item.name}}</view>
					<view class="item-row-text"><text>订单号</text>：{{item.ordernum}}</view>
					<view class="item-row-text"><text>消费金额</text>：{{item.consumption_amt}}</view>
					<view class="item-row-text"><text>参与用户</text>：{{item.nickname}}</view>
					<view class="item-row-text"><text>排队时间</text>：{{item.createtime}}</view>
					<view class="item-row-text" v-if="item.mdname"><text>所在门店</text>：{{item.mdname}}</view>
				</view>
			</view>
			<nodata v-if="nodata"></nodata>
			<nomore v-if="nomore"></nomore>
		</block>
		<button class="coverguize" @tap="changemaskrule" :style="{backgroundColor:t('color1')}">活动规则</button>
		<view id="mask-rule" v-if="showmaskrule">
			<view class="box-rule" :style="{backgroundColor:t('color1')}">
				<view class="h2">活动规则</view>
				<view id="close-rule" @tap="changemaskrule" :style="'background-image:url('+pre_url+'/static/img/dzp/close.png);background-size:100%'"></view>
				<view class="con">
					<view class="text">
						<text decode="true" space="true" v-if="activity.hd && activity.hd.guize">{{activity.hd.guize}}</text>
					</view>
				</view>
			</view>
		</view>
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
				pre_url: app.globalData.pre_url,
				datalist: [],
				pagenum: 1,
				nodata: false,
				nomore: false,
				showmaskrule: false,
				activity: []
			};
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.acid = this.opt.acid || 0;
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
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
				app.post('ApiLuckyfree/activityRecord', {
					acid: that.acid,
					pagenum: pagenum,
				}, function(res) {
					that.loading = false;
					var data = res.data;
					if (pagenum == 1) {
						that.datalist = data;
						that.activity = res.activity;
						if (data.length == 0) {
							that.nodata = true;
						}
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
			changemaskrule: function() {
				this.showmaskrule = !this.showmaskrule;
			},
		}
	};
</script>

<style>
	.container {
		background-color: #f5f5f5;
		min-height: 100vh
	}

	.tips-text {
		text-align: center;
		color: #D85356;
		font-weight: bold;
		padding: 10rpx
	}

	.myscore {
		width: 94%;
		margin: 20rpx 3%;
		border-radius: 10rpx 56rpx 10rpx 10rpx;
		position: relative;
		display: flex;
		flex-direction: column;
		padding: 70rpx 0
	}

	.myscore .f1 {
		margin: 0 0 0 60rpx;
		color: rgba(255, 255, 255, 0.8);
		font-size: 24rpx
	}

	.myscore .f2 {
		margin: 20rpx 0 0 60rpx;
		color: #fff;
		font-size: 64rpx;
		font-weight: bold;
		position: relative
	}

	.btn-mini {
		right: 32rpx;
		top: 28rpx;
		width: 130rpx;
		height: 50rpx;
		text-align: center;
		border: 1px solid #e6e6e6;
		border-radius: 10rpx;
		color: #fff;
		font-size: 24rpx;
		font-weight: bold;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		position: absolute
	}

	.topsearch {
		width: 94%;
		margin: 10rpx 3%
	}

	.topsearch .f1 {
		height: 60rpx;
		border-radius: 30rpx;
		border: 0;
		background-color: #fff;
		flex: 1
	}

	.topsearch .f1 .img {
		width: 24rpx;
		height: 24rpx;
		margin-left: 10px
	}

	.topsearch .f1 input {
		height: 100%;
		flex: 1;
		padding: 0 20rpx;
		font-size: 28rpx;
		color: #333
	}

	.content {
		margin-top: 20rpx
	}

	.list-item {
		background-color: white;
		margin: 20rpx;
		padding: 20rpx;
		border-radius: 10rpx
	}

	.list-item .tag {
		color: #fff;
		margin-right: 10rpx;
		padding: 8rpx;
		border-radius: 10rpx
	}

	.list-item .my-progress {
		padding: 10rpx 0
	}

	.item-row {
		display: flex;
		justify-content: space-between;
		margin-bottom: 20rpx
	}

	.item-row:last-child {
		align-items: center
	}

	.column {
		display: flex;
		flex-direction: column
	}

	.column text {
		margin-bottom: 10rpx;
		font-size: 26rpx
	}

	.highlight {
		font-weight: bold
	}

	.time {
		color: #999;
		font-size: 24rpx;
		flex: 1
	}

	.btn-mini2,
	.btn-mini3 {
		width: 140rpx;
		height: 50rpx;
		text-align: center;
		border: 1px solid #e6e6e6;
		border-radius: 10rpx;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		font-size: 24rpx;
		margin-left: auto
	}

	.btn-mini2 {
		background-color: #ff4d4f;
		color: white
	}

	.btn-mini3 {
		background-color: #A5A5A5;
		color: white
	}

	.top-data-list {
		width: 100%;
		padding: 35rpx 0rpx;
		justify-content: center
	}

	.top-data-list .data-options {
		text-align: center;
		max-width: 32%;
		width: auto;
		min-width: 30%
	}

	.top-data-list .line-class {
		height: 50rpx;
		border-left: 1rpx #e5d734 solid
	}

	.top-data-list .data-options .title-text {
		font-size: 20rpx;
		color: #fff;
		font-weight: bold;
		white-space: nowrap
	}

	.top-data-list .data-options .num-text {
		font-size: 34rpx;
		color: #ecdd36;
		margin-top: 15rpx
	}

	.item-row-text {
		margin-bottom: 20rpx;
	}

	.item-row-text text {
		display: inline-block;
		width: 120rpx;
		text-align-last: justify;
	}

	.coverguize,
	.coverrecord {
		position: absolute;
		cursor: pointer;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		z-index: 9999;
		top: 50%;
		right: 0;
		color: #fff;
		background-color: rgba(17, 17, 17, 0.3);
		border-radius: 15rpx 0px 0px 15rpx;
		width: 55rpx;
		padding: 10rpx;
		word-break: break-all;
		height: auto;
		line-height: 35rpx;
		transform: translateY(-50%)
	}

	.coverrecord {
		z-index: 99999;
		margin-top: 200rpx
	}

	.coverguize {
		background-color: #D85356;
	}

	#mask-rule,
	#mask {
		position: fixed;
		left: 0;
		top: 0;
		z-index: 99999;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.85)
	}

	#mask-rule .box-rule {
		position: relative;
		margin: 30% auto;
		padding-top: 40rpx;
		width: 90%;
		height: 675rpx;
		border-radius: 20rpx;
		background-color: #D85356
	}

	#mask-rule .box-rule .star {
		position: absolute;
		left: 50%;
		top: -100rpx;
		margin-left: -130rpx;
		width: 259rpx;
		height: 87rpx
	}

	#mask-rule .box-rule .h2 {
		width: 100%;
		text-align: center;
		line-height: 34rpx;
		font-size: 34rpx;
		font-weight: normal;
		color: #fff
	}

	#mask-rule #close-rule {
		position: absolute;
		right: 34rpx;
		top: 38rpx;
		width: 40rpx;
		height: 40rpx
	}

	#mask-rule .con {
		overflow: auto;
		position: relative;
		margin: 40rpx auto;
		padding-right: 15rpx;
		width: 580rpx;
		height: 82%;
		line-height: 48rpx;
		font-size: 26rpx;
		color: #fff
	}

	#mask-rule .con .text {
		position: absolute;
		top: 0;
		left: 0;
		width: inherit;
		height: auto
	}
</style>