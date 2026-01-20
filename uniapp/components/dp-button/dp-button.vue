<template>
<view class="dp-button" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx',
	fontSize:(params.fontsize*2)+'rpx'
}">
	<button :style="{
		backgroundColor:params.btnbgcolor,
		border:'1px solid '+params.btnbordercolor,
		fontSize:(params.btnfontsize*2)+'rpx',
		color:params.btncolor,
		width:(params.btnwidth*2.2)+'rpx',
		height:(params.btnheight*2.2)+'rpx',
		lineHeight:(params.btnheight*2.2)+'rpx',
		borderRadius:(params.btnradius*2.2)+'rpx',
		margin:'auto'
	}" :open-type="params.hrefurl=='contact::'?'contact':(params.hrefurl=='share::'?'share':'')" @click="goto" :data-url="params.hrefurl" show-message-card="true">{{params.btntext}}</button>
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
.dp-button{margin: 0 auto;background: #ff4f4f;color: #fff;margin-top: 15px;margin-bottom:10px;text-align:center}
</style>