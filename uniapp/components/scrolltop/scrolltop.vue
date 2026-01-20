<template>
<view>
	<view class="scrolltop" v-show="isshow" @tap="gotop"><image class="image" :src="pre_url+'/static/img/gotop.png'"/></view>
</view>
</template>
<script>
	export default {
		props: {
			isshow:{}
		},
		data () {
			return {
				pre_url:getApp().globalData.pre_url
			}
		},
		methods:{
			gotop: function () {
				uni.pageScrollTo({
					scrollTop: 0,
					duration:100
				});
			}
		}
	}
</script>
<style>
.scrolltop{position:fixed;bottom:160rpx;right:20rpx;width:60rpx;height:60rpx;background:rgba(0,0,0,0.4);color:#fff;border-radius:50%;padding:12rpx 10rpx 8rpx 10rpx;z-index:9;}
.scrolltop .image{width:100%;height:100%;}
</style>