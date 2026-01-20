<template>
<view class="dp-cover">
	<button @click="goto" :data-url="params.hrefurl" :open-type="params.hrefurl=='contact::'?'contact':(params.hrefurl=='share::'?'share':'')" class="dp-cover-cover" :style="{
		zIndex:10,
		top:params.top+'vh',
		left:params.left+'vw',
		color:params.color,
		backgroundColor:params.bgcolor,
		width:(params.width*2.2)+'rpx',
		height:(params.height*2.2)+'rpx',
		margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
		padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx',
		fontSize:(params.fontsize*2.2)+'rpx',
		border:(params.border*2.2)+'rpx solid '+params.bordercolor,
		borderRadius:(params.radius*2.2)+'rpx'
	}" show-message-card="true">
		<text v-if="params.style==1" style="padding:0 4rpx">{{params.text}}</text>
		<image v-if="params.style==2" :src="params.pic" :style="{width:(params.picwidth*2.2)+'rpx',height:(params.picheight*2.2)+'rpx'}"/>
	</button>
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
.dp-cover{height: auto; position: relative;}
.dp-cover-cover{position:fixed;z-index:99999;cursor:pointer;display:flex;align-items:center;justify-content:center;overflow:hidden}
</style>