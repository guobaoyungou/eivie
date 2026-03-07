<template>
	<view>
		<view class="navbox flex flex-wp">
			<view v-for="(item, index) in data" :key="index" class="nav_li" @tap="goto" :data-url="'/pages/shop/prolist?'+(bid>0?'bid='+bid+'&cid2':'cid')+'=' + item.id">
				<image :src="item.pic" mode="aspectFill"></image>
				<view class="title">{{item.name}}</view>
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
				menuindex: -1,

				data: [],
				bid: 0,
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.bid = this.opt.bid ? this.opt.bid : 0;
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiShop/category1', {
					bid: that.bid
				}, function(res) {
					that.loading = false;
					that.data = res.data;
					that.loaded();
				});
			},
		}
	};
</script>
<style>
	page {
		background-color: #fff
	}

	.navbox {
		margin-top: 12rpx;
		height: auto;
		overflow: hidden;
		padding-bottom: 20rpx;
	}

	.nav_li {
		width: 33%;
		text-align: center;
		box-sizing: border-box;
		padding: 40rpx 0 20rpx;
	}

	.nav_li image {
		width: 100rpx;
		height: 100rpx;
		margin-bottom: 20rpx;
	}
	.nav_li .title{
		padding: 0 20rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
</style>
