<template>
<view class="dp-line" :style="{
	backgroundColor:params.bgcolor,margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx'
}">
	<view class="dp-line-line" :style="{
		borderTopWidth:params.height*2.2+'rpx',
		borderTopStyle:params.style,
		borderTopColor:params.color
	}"></view>
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
.dp-line{height: auto; position: relative;background-color: #ffffff;}
.dp-line-line {height:0px; margin:10px 0px; border-top:2px dashed #666;background-color: #ffffff;}
</style>