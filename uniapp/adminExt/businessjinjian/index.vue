<template>
<view class="container">
		<view>
			<!-- 微信 -->
			<view class="order-box" v-if="set.includes(0) || set.includes(1)">
				<view class="head">
					<view class="wx"></view>
					<view class="f1">微信支付</view>
				</view>
				<view class="op">
					<view class="btn1 flex-x-center" @tap.stop="goto" :data-url="'subject'">
						<image :src="pre_url+'/static/img/login-weixin2.png'"></image>
						<text>开通微信官方收款账户</text>
					</view>
				</view>
			</view>
			
			<!-- 支付宝 -->
			<view class="order-box" v-if="set.includes(0) || set.includes(2)">
				<view class="head">
					<view class="zfb"></view>
					<view class="f1">支付宝</view>
				</view>
				<view class="op">
					<view class="btn2 flex-x-center" @tap.stop="goto" :data-url="'zfbjinjian'">
						<image :src="pre_url+'/static/img/login-alipay.png'"></image>
						<text>签约支付宝商户服务</text>
					</view>
				</view>
			</view>
			
		</view>
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
			set:"",
			pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onUnload:function(){
		clearInterval(this.interval1);
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    getdata: function (loadmore) {
      var that = this;
      app.get('ApiAdminBusinessJinjian/index', {}, function (res) {
				if(res.status==0){
					app.alert(res.msg);
					return;
				}
        that.set = res.set;
      });
    }
  }
};
</script>
<style>
.container{ width:100%;display:flex;flex-direction:column}
.wx{background: linear-gradient(-90deg, #06A051 0%, #03B269 100%);margin: 24rpx 10rpx;padding: 5rpx;}
.zfb{background-color: #0AACF1;margin: 24rpx 10rpx;padding: 5rpx;}
.order-box{ width: 94%;margin:30rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f5f5f5 solid; height:88rpx; line-height:88rpx; overflow: hidden; color: #999;}
.order-box .head .f1{color:#222222;font-size: 28rpx;font-weight: bold;}

.order-box .op{display:flex;justify-content:center;align-items:center;width:100%; padding:40rpx 0px;line-height: 50rpx;}
.order-box .op .btn1{width: 420rpx;color:#03B269;border-radius:10rpx;text-align:center;font-size:28rpx; padding: 20rpx 40rpx;border:1px solid #03B269;}
.order-box .op .btn2{width: 420rpx;color:#0AACF1;border-radius:10rpx;text-align:center;font-size:28rpx; padding: 20rpx 40rpx;border:1px solid #0AACF1;}
.order-box .op .btn1 image,.order-box .op .btn2 image{width: 50rpx;height: 50rpx;}
</style>