<template>
<view class="dp-product" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',
	width:'calc(100% - '+params.margin_x*2.2*2+'rpx)'
}">
	<!--123排-->
	<dp-product-item v-if="params.style=='1' || params.style=='2' || params.style=='3'" :showstyle="params.style" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showcost="params.showcost" :showsales="params.showsales" :showcart="params.showcart" :showbname="params.showbname" :showbdistance="params.showbdistance" :showcommission="params.showcommission" :cartimg="params.cartimg" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" :params="params"></dp-product-item>
	<!--横排-->
	<dp-product-itemlist v-if="params.style=='list'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showcost="params.showcost" :showsales="params.showsales" :showcart="params.showcart" :showbname="params.showbname" :showbdistance="params.showbdistance" :showcommission="params.showcommission" :cartimg="params.cartimg" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor"></dp-product-itemlist>
	<!--左右滑动-->
	<dp-product-itemline v-if="params.style=='line'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showcost="params.showcost" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor"></dp-product-itemline>
	<!--瀑布流-->
	<dp-product-waterfall v-if="params.style=='waterfall'" :list="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showcost="params.showcost" :showsales="params.showsales" :showcart="params.showcart" :showstock="params.showstock" :showbname="params.showbname" :showbdistance="params.showbdistance" :showcommission="params.showcommission" :showcoupon="params.showcoupon"  :cartimg="params.cartimg" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor"></dp-product-waterfall>
	<!-- 营销横排 --> <!-- 佣金横排 -->
	<dp-product-yx-itemlist v-if="params.style=='yxlist' || params.style=='yjlist'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showcost="params.showcost" :showsales="params.showsales" :showcart="params.showcart" :showbname="params.showbname" :showbdistance="params.showbdistance" :showcommission="params.showcommission" :cartimg="params.cartimg" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor"></dp-product-yx-itemlist>
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