<template>
<view class="dp-product" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',
	width:'calc(100% - '+params.margin_x*2.2*2+'rpx)'
}">
	<!--单排-->
	<dp-zhaopin-itemlist v-if="params.style=='1'" :data="data" idfield="proid" :menuindex="menuindex"></dp-zhaopin-itemlist>
	<!--双排-->
	<dp-zhaopin-item v-if="params.style=='2'" :showstyle="params.style" :data="data" idfield="proid" :menuindex="menuindex"></dp-zhaopin-item>
	</view>
</template>
<script>
	export default {
		props: {
			menuindex:{default:-1},
			params:{},
			data:{}
		}
	}
</script>
<style>
.dp-product{width:100%;height: auto; position: relative;overflow: hidden; padding: 0px; background: #fff;}
</style>