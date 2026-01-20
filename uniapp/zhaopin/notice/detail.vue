<template>
<view class="container">
	<block v-if="isload">
		<view class="box flex-sb">
			<view class="pic">
				<image src="../../static/img/clock.png"></image>
			</view>
			<view class="content">
				<view class="title">收到的招聘</view>
				<view class="tips">收到新的招聘信息</view>
			</view>
			<view class="time">3分钟前</view>
		</view>
		<view class="box flex-sb">
			<view class="pic">
				<image src="../../static/img/clock.png"></image>
			</view>
			<view class="content">
				<view class="title">收到的简历</view>
				<view class="tips">收到新的招聘信息</view>
			</view>
			<view class="time">3分钟前</view>
		</view>
		<view class="box flex-sb">
			<view class="pic">
				<image src="../../static/img/clock.png"></image>
			</view>
			<view class="content">
				<view class="title">平台客服</view>
				<view class="tips">收到新的招聘信息</view>
			</view>
			<view class="time">3分钟前</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,

			nomore:false,
			nodata:false,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid : 0;
		//console.log(this.bid);
		if(this.opt.keyword) {
			this.keyword = this.opt.keyword;
		}
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getlist(true);
    }
  },
  methods: {
		getdata:function(){
			var that = this;
			that.loaded();
		}
  }
};
</script>
<style>
	@import "../common.css";
	.container{padding: 0;}
	.box{background: #FFFFFF;padding: 20rpx 30rpx;border-bottom: 1rpx solid #efefef;}
	.box:last-child{border: none;}
	.box .content{flex: 1;}
	.box .title{font-size: 32rpx;font-weight: bold;line-height: 60rpx;}
	.box .pic{flex-shrink: 0;width: 120rpx;}
	.box .time{color: #CCCCCC;}
	.box .pic image{height: 100rpx;width: 100rpx;}
</style>