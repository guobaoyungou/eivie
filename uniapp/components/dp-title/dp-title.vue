<template>
<view class="dp-title" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx'
}">
	<view class="dp-title-s1">
		<view class="dp-title-s1-line" :style="{borderColor:params.linecolor}"></view>
		<view class="dp-title-s1-text">
			<view class="dp-title-s1-text1" :style="{fontSize:params.fontsize*2+'rpx',color:params.color}">
				<image class="image" v-if="params.showicon==1" :src="params.imgurl" :style="{width:params.iconsize*2+'rpx'}" mode="widthFix"/>
				<text v-if="params.title!=''" class="dp-title-s1-title1">{{params.title}}</text>
			</view>
		</view>
		<view class="dp-title-s1-line" :style="{borderColor:params.linecolor}"></view>
	</view>
</view>
</template>
<script>
	export default {
		props: {
			params:{},
			data:{}
		}
	}
</script>
<style>
.dp-title{height: auto; position: relative;}
.dp-title-s1 {height: auto;overflow: hidden;display:flex;align-items:center}
.dp-title-s1-line{height: auto; padding: 0; overflow: hidden;flex:1;height:0;border-top:1px solid #ccc}
.dp-title-s1-text{padding:0 32rpx;text-align:center;}
.dp-title-s1-text1{text-align:center;display:flex;align-items:center;justify-content:center}
.dp-title-s1-text1 .image{text-align:center;max-width:400rpx;max-height:100rpx;margin:0 4rpx;height:auto}
.dp-title-s1-title1{margin:0 4rpx}
.dp-title-s1-text2{text-align:center;height:32rpx;line-height:32rpx}

.dp-title-s2 {width:100%;height: auto;overflow: hidden;display:flex;align-items:center}
.dp-title-s2-text{padding:0;}
.dp-title-s2-text1{flex:1;display:flex;align-items:center;}
.dp-title-s2-text1 .image{max-width:100rpx;max-height:100rpx;margin:0 2px}
.dp-title-s2-title1{margin:0 4rpx}
.dp-title-s2-text2{text-align:right;}
</style>