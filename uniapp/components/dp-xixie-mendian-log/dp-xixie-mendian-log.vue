<template>
    <view>
        <view class="dp-userinfo-order" style="margin:22rpx;margin-top: 20rpx;" >
            <view class="head">
                <text class="f1">门店订单</text>
                <view class="f2" @tap="goto" data-url="/xixie/mendianorder"><text>查看全部订单</text><image src="/static/img/arrowright.png" class="image"/></view>
            </view>
            <view class="head">
                <text class="f1">门店余额</text>
                <view class="f2" @tap="goto" data-url="/xixie/mendianwithdraw"><text>查看门店余额</text><image src="/static/img/arrowright.png" class="image"/></view>
            </view>
        </view>
    </view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
				textset:app.globalData.textset,
				platform:app.globalData.platform
			}
		},
		props: {
			params:{},
			data:{}
		},
		methods:{
			
		}
	}
</script>
<style>
.dp-userinfo-order{background:#fff;padding:0 20rpx;border-radius:16rpx;position:relative}
.dp-userinfo-order .head{ display:flex;align-items:center;width:100%;padding:16rpx 0;}
.dp-userinfo-order .head .f1{flex:auto;font-size:30rpx;padding-left:16rpx;font-weight:bold;color:#333}
.dp-userinfo-order .head .f2{ display:flex;align-items:center;color:#999;width:200rpx;padding:10rpx 0;text-align:right;justify-content:flex-end}
.dp-userinfo-order .head .f2 .image{ width:30rpx;height:30rpx;}
.dp-userinfo-order .head .t3{ width:40rpx; height:40rpx;}
.dp-userinfo-order .content{ display:flex;width:100%;padding:0 0 10rpx 0;align-items:center;font-size:24rpx}
.dp-userinfo-order .content .item{padding:10rpx 0;flex:1;display:flex;flex-direction:column;align-items:center;position:relative}
.dp-userinfo-order .content .item .image{ width:50rpx;height:50rpx}
.dp-userinfo-order .content .item .iconfont{font-size:60rpx}
.dp-userinfo-order .content .item .t3{ padding-top:3px}
.dp-userinfo-order .content .item .t2{display:flex;align-items:center;justify-content:center;background: red;color: #fff;border-radius:50%;padding: 0 10rpx;position: absolute;top: 0px;right:20rpx;width:35rpx;height:35rpx;text-align:center;}
</style>