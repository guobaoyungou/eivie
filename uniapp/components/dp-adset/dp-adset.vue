<template>
	<!-- #ifdef APP-PLUS -->
	<view class="dp-wxad" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx'
}">
		<ad :adpid="params.adpid" @load="onload" @close="onclose" @error="onerror"></ad>
		<view class="ad-error" v-if="errMsg">{{errMsg}}</view>
	</view>
	<!-- #endif -->
</template>
<script>
	// #ifdef APP
	export default {
		props: {
			params:{},
			data:{}
		},
		data(){
			return{
				errMsg:'',
			}
		},
		methods:{
			onload(e) {},
			onclose(e) {},
			onerror(e) {
				this.errMsg = JSON.stringify(e.detail);
			}
		}
	}
	// #endif	
</script>
<style>
.dp-wxad{position:relative;}
.ad-error{text-align: center;}
</style>
