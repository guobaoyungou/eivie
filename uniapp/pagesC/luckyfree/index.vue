<template>
	<view class="container">
		<block v-if="isload">
			<!-- 选项卡 -->
			<dd-tab :itemdata="['门店队列','全网队列','我的队列']" :itemst="['business','platform','me']" :st="st" :isfixed="true"
				@changetab="changetab"></dd-tab>
			<view style="width:100%;height:100rpx"></view>
			<view class="tips-text">幸运免单将订单结算之后进入排队，请耐心等待</view>
			<!-- 列表 -->
			<view class="content" id="datalist">
				<view class="list-item" v-for="(item, index) in datalist" :key="index" @tap.stop="goto" :data-url="'record?acid=' + item.id" :data-id="item.id">
					<view class="item-row">
						<view v-if="item.business">
							<text class="tag" :style="{backgroundColor:t('color1')}">{{item.business}}免单</text>
						</view>
						<view v-else>
							<text class="tag" :style="{backgroundColor:t('color1')}">平台免单</text>
						</view>
						<view style="font-weight: bold;" v-if="st == 'me' && item.status == 0">已开奖</view>
						<view style="font-weight: bold;" v-else>达到 {{item.opennum}}人开奖</view>
					</view>
					<progress :percent="item.activity_num_ratio" stroke-width="6" border-radius="10" :show-info="true" :font-size="14" activeColor="#fb743b" class="my-progress" />
					<view class="item-row">活动名称: {{item.name}}</view>
					<view class="item-row" v-if="item.free_money > 0">免单金额: {{item.free_money}}</view>
					<view class="item-row" v-if="item.status == 0">中奖用户: {{item.win_nickname}}</view>
					<view class="item-row">排队用户: {{item.activity_num}}人</view>
					<view class="item-row">创建时间: {{item.createtime}}</view>
				</view>
			</view>
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
				pre_url: app.globalData.pre_url,
				st: 'platform',
				datalist: [],
				pagenum: 1,
				nodata: false,
				nomore: false,
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
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
				var st = that.st;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.post('ApiLuckyfree/activityList', {
					st: st,
					pagenum: pagenum,
				}, function(res) {
					that.loading = false;
					var data = res.data;
					if (pagenum == 1) {
						that.datalist = data;
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
			changetab: function(st) {
				this.st = st;
				uni.pageScrollTo({
					scrollTop: 0,
					duration: 0
				});
				this.getdata();
			}
		}
	};
</script>

<style>
	.container{background-color:#f5f5f5;min-height:100vh}
	.tips-text{text-align:center;color:#b90000;font-weight:bold;padding:10rpx}
	.myscore{width:94%;margin:20rpx 3%;border-radius:10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
	.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx}
	.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold;position:relative}
	.btn-mini{right:32rpx;top:28rpx;width:130rpx;height:50rpx;text-align:center;border:1px solid #e6e6e6;border-radius:10rpx;color:#fff;font-size:24rpx;font-weight:bold;display:inline-flex;align-items:center;justify-content:center;position:absolute}
	.topsearch{width:94%;margin:10rpx 3%}
	.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
	.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
	.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333}
	.content{margin-top:20rpx}
	.list-item{background-color:white;margin:20rpx;padding:20rpx;border-radius:10rpx}
	.list-item .tag{color:#fff;margin-right:10rpx;padding:8rpx;border-radius:10rpx}
	.list-item .my-progress{padding:10rpx 0}
	.item-row{display:flex;justify-content:space-between;margin-bottom:20rpx}
	.item-row:last-child{align-items:center}
	.column{display:flex;flex-direction:column}
	.column text{margin-bottom:10rpx;font-size:26rpx}
	.highlight{font-weight:bold}
	.time{color:#999;font-size:24rpx;flex:1}
	.btn-mini2,.btn-mini3{width:140rpx;height:50rpx;text-align:center;border:1px solid #e6e6e6;border-radius:10rpx;display:inline-flex;align-items:center;justify-content:center;font-size:24rpx;margin-left:auto}
	.btn-mini2{background-color:#ff4d4f;color:white}
	.btn-mini3{background-color:#A5A5A5;color:white}
	.top-data-list{width:100%;padding:35rpx 0rpx;justify-content:center}
	.top-data-list .data-options{text-align:center;max-width:32%;width:auto;min-width:30%}
	.top-data-list .line-class{height:50rpx;border-left:1rpx #e5d734 solid}
	.top-data-list .data-options .title-text{font-size:20rpx;color:#fff;font-weight:bold;white-space:nowrap}
	.top-data-list .data-options .num-text{font-size:34rpx;color:#ecdd36;margin-top:15rpx}
</style>