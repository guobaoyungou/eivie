<template>
	<view class="loading-wrap" :style="loadstyle">
		<image  class="loading-img" :src="pre_url+'/upload/loading/icon_'+aid+'.png'"></image>
	</view>
</template>
<script>
	export default {
		data(){
			return {
				aid:getApp().globalData.aid,
				pre_url:getApp().globalData.pre_url
			}
		},
		props: {
			loadstyle:{default:''}
		}
	}
</script>
<style>
.loading-wrap {position: fixed;width: 64rpx;height: 64rpx;left: 50%;top: 38%;z-index: 99999;text-align: center;margin-left: -32rpx;border-radius: 35rpx;}
.loading-img {width: 64rpx;height: 64rpx;position: absolute;animation: loading 0.8s linear 0s infinite;left: 0;top: 0;}
.loading-img0 {width: 64rpx;height: 64rpx;position: absolute;animation: loading 0.8s linear 0s infinite;left: 0;top: 0;}
.loading-img1 {width: 64rpx;height: 64rpx;position: absolute;animation: loading 0.8s ease 0s infinite;left: 0;top: 0;}
@keyframes loading {0% {transform: rotate(0);}100% {transform: rotate(360deg);}}
@-webkit-keyframes loading {0% {transform: rotate(0);}100% {transform: rotate(360deg);}}
</style>