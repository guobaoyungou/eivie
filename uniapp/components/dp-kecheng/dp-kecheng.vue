<template>
<view class="dp-product" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',
	width:'calc(100% - '+params.margin_x*2.2*2+'rpx)'
}">
	<!--123排-->
	<dp-kecheng-item v-if="params.style=='1' || params.style=='2' || params.style=='3'" :showstyle="params.style" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" idfield="proid" :menuindex="menuindex"></dp-kecheng-item>
	<!--横排-->
	<dp-kecheng-itemlist v-if="params.style=='list'" :data="data" :saleimg="params.saleimg" :showpic="params.showpic" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" idfield="proid" :menuindex="menuindex"></dp-kecheng-itemlist>
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