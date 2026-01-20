<template>
	<view class="dp-blank" :style="{
		height:params.height*2.2+'rpx',
		backgroundColor:params.bgcolor,
		margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx'
	}"></view>
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

</style>