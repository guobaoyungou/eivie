<template>
<view class="dp-product" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',
	width:'calc(100% - '+params.margin_x*2.2*2+'rpx)'
}">
	<!--123排-->
	<dp-tour-item v-if="params.style=='1' || params.style=='2' || params.style=='3'" :showstyle="params.style" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :showbname="params.showbname" :showbdistance="params.showbdistance" :cartimg="params.cartimg" :idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" :params="params"></dp-tour-item>
</view>
</template>
<script>
	export default {
		props: {
			menuindex:{default:-1},
			params:{},
			data:{},
      proid:0
		}
	}
</script>
<style>
.dp-product{width:100%;height: auto; position: relative;overflow: hidden; padding: 0px; background: #fff;}
</style>