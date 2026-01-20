<template>
	<view class="dp-wxad" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx'
}">
		<ad :unit-id="params.unitid" v-if="params.adtype == 'banner'"></ad>
		<ad :unit-id="params.unitid" ad-type="video" ad-theme="white" v-if="params.adtype == 'video'"></ad>
		<ad-custom :unit-id="params.unitid" v-if="params.adtype == 'custom'"></ad-custom>
	</view>
</template>
<script>
	export default {
		props: {
			params:{},
			data:{}
		},
		methods:{
			
		}
	}
</script>
<style>
.dp-wxad{position:relative;}
</style>