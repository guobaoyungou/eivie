<template>
<view class="nomore">
	<view class="nomore-line" :style="{borderColor:linecolor}"></view>
	<view class="nomore-text">
		<text class="txt" :style="{color:textcolor}">{{text}}</text>
	</view>
	<view class="nomore-line" :style="{borderColor:linecolor}"></view>
</view>
</template>
<script>
	export default {
		props: {
			text:{default:'没有更多数据了'},
			textcolor:{default:'#999'},
			linecolor:{default:'#eee'},
		}
	}
</script>
<style>
.nomore{height:auto;overflow: hidden;display:flex;align-items:center;width:500rpx;padding:30rpx 20rpx;margin:0 auto}
.nomore-line{height: auto; padding: 0; overflow: hidden;flex:1;height:0;border-top:1px solid #eee}
.nomore-text{padding:0 32rpx;text-align:center;display:flex;align-items:center;justify-content:center}
.nomore-text .txt{color:#999;font-size:28rpx}
</style>