<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待付款','待发货','待收货','已完成','退款/售后']" :itemst="['all','0','1','2','3','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:100rpx"></view>
		<!-- #ifndef H5 || APP-PLUS -->
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入订单号/商品名搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<!--  #endif -->
		<!-- 类型筛选 -->
		<order-type-filter :types="enabledTypes" :value="orderType" @change="onTypeChange"></order-type-filter>
		<!-- 订单列表 -->
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<unified-order-card :item="item" @refresh="getdata"></unified-order-card>
			</block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
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
			st: 'all',
			orderType: 'all',
			keyword: '',
			datalist: [],
			pagenum: 1,
			nomore: false,
			nodata: false,
			enabledTypes: [
				'shop', 'collage', 'seckill', 'tuangou', 'kanjia',
				'lucky_collage', 'scoreshop', 'yuyue', 'kecheng', 'cycle', 'ai_pick',
				'ai_image', 'ai_video'
			]
		};
	},

	onLoad: function(opt) {
		this.opt = app.getopts(opt);
		if (this.opt.st !== undefined) {
			this.st = this.opt.st;
		}
		if (this.opt.order_type) {
			this.orderType = this.opt.order_type;
		}
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

	onNavigationBarSearchInputConfirmed: function(e) {
		this.searchConfirm({detail: {value: e.text}});
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
			var orderType = that.orderType;
			var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;

			app.post('ApiUnifiedOrder/orderlist', {
				st: st,
				pagenum: pagenum,
				keyword: keyword,
				order_type: orderType
			}, function(res) {
				that.loading = false;
				var data = res.datalist || [];
				if (pagenum == 1) {
					that.datalist = data;
					if (data.length == 0) {
						that.nodata = true;
					}
					that.isload = true;
					that.loaded();
				} else {
					if (data.length == 0) {
						that.nomore = true;
					} else {
						that.datalist = that.datalist.concat(data);
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
		},

		onTypeChange: function(val) {
			this.orderType = val;
			this.getdata();
		},

		searchConfirm: function(e) {
			this.keyword = e.detail.value;
			this.getdata();
		}
	}
};
</script>

<style>
.container { width: 100%; }
.topsearch { width: 94%; margin: 10rpx 3%; }
.topsearch .f1 { height: 60rpx; border-radius: 30rpx; border: 0; background-color: #fff; flex: 1; }
.topsearch .f1 .img { width: 24rpx; height: 24rpx; margin-left: 10px; }
.topsearch .f1 input { height: 100%; flex: 1; padding: 0 20rpx; font-size: 28rpx; color: #333; }
.order-content { display: flex; flex-direction: column; }
</style>
