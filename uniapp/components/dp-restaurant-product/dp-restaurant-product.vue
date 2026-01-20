<template>
<view class="dp-product" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx'
}">
	<!--123排-->
	<dp-restaurant-product-item v-if="params.style=='1' || params.style=='2' || params.style=='3'" :showstyle="params.style" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showtype="params.showtype" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" idfield="proid" :menuindex="menuindex"></dp-restaurant-product-item>
	<!--横排-->
	<dp-restaurant-product-itemlist v-if="params.style=='list'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showtype="params.showtype" :showprice="params.showprice" :showsales="params.showsales" :cartimg="params.showcart" idfield="proid" :menuindex="menuindex"></dp-restaurant-product-itemlist>
	<!--左右滑动-->
	<dp-restaurant-product-itemline v-if="params.style=='line'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showtype="params.showtype" :showprice="params.showprice" :showsales="params.showsales" :cartimg="params.showcart" idfield="proid" :menuindex="menuindex"></dp-restaurant-product-itemline>
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