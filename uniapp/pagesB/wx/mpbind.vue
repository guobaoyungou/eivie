<template>
	<view>
		<view class="content">
			<view class="message">
				<view class="image-container">
					<image :src="pre_url + '/static/img/success_1.png'" mode="widthFix" class="img" v-if="error == 1"/>
					<image :src="pre_url + '/static/img/warning.png'" mode="widthFix" class="img" v-else/>
				</view>
				<view class="message-text ft30">{{msg}}</view>
			</view>
			<view class="qr-code" v-if="mpinfo.nickname && mpinfo.qrcode">
				<view class="image-container">
					<image :src="mpinfo.qrcode" mode="aspectFit" show-menu-by-longpress class="img"/>
				</view>
				<view class="qr-text ft30">长按识别或者保存二维码</view>
				<view class="qr-text ft30">关注公众号：{{mpinfo.nickname}}</view>
				<view class="qr-text c9e" v-if="subscribe == 1">已关注公众号</view>
				<view class="qr-text c9e" v-else>暂未关注公众号</view>
			</view>
			<view class="tips">
				<view class="tip-text c9e">请按返回键重新进入小程序</view>
			</view>
		</view>
		<loading v-if="loading"></loading>
	</view>
</template>

<script>
var app = getApp();

export default {
	data() {
		return {
			pre_url:app.globalData.pre_url,
			loading:false,
			isload: false,
			error:0,
			mpinfo:[],
			subscribe:0,
			msg:'绑定异常，请稍后再试'
		};
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if (this.opt && !app.isNull(this.opt.error)) {  
			
			const errorMessages = {
				1000:'未获取到当前绑定用户，请稍后再试',
				1003:'未查询到当前绑定用户，请稍后再试',
				1001:'绑定异常，请联系管理员',
				1002:'绑定异常，请联系管理员',
				0:'绑定异常，请稍后再试'
			}
			var err = this.opt.error;
			if (err == 1) {
				if(this.opt.subscribe){
					this.subscribe = this.opt.subscribe;  
				}
				this.msg = '您已绑定公众号，可在公众号接收订单通知';
			}else{
				this.msg = errorMessages[err] || this.msg;
			}
			this.error = err;  
		}
		this.getdata();
	},
	methods: {
		getdata: function(loadmore) {
			var that = this;
			that.loading = true;
			app.get('ApiMpBind/getMpInfo', {aid:app.globalData.aid}, function(res) {
				that.loading = false;
				if(res.status == 1){
					that.mpinfo = res.data;
				}
			});
		},
	}
};
</script>
<style>
.content {
  margin: 20rpx 3%;
  border-radius: 5px;
  padding: 20rpx 40rpx;
  text-align: center;
}


.img {
  width: 100%;
  height: 100%;
}

.message{
	margin: 100rpx 0 50rpx 0;
}

.message .image-container {
  display: inline-block;
  width: 250rpx;
  height: 250rpx;
}

.message .message-text{
	margin-top: 70rpx;
}

.info-text, .qr-text, .tip-text {
  margin-top: 10rpx;
}
.qr-code .image-container {
  display: inline-block;
  width: 400rpx;
  height: 400rpx;
}
.qr-code .qr-text {
  margin-top: 35rpx;
}
.tips {
  margin-top: 55rpx;
	font-size: 26rpx;
}
.ft30{
	font-size: 30rpx;
}
.c9e{
	color: #9e9e9e;
}
</style>

