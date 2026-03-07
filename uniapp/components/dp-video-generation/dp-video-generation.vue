<template>
<view class="dp-video-generation" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',
	width:'calc(100% - '+params.margin_x*2.2*2+'rpx)'
}">
	<!--123排-->
	<dp-product-item v-if="params.style=='1' || params.style=='2' || params.style=='3'" :showstyle="params.style" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" :params="params" saleslabel="已用" covertype="video" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :card_gap="cardGap" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=2"></dp-product-item>
	<!--横排-->
	<dp-product-itemlist v-if="params.style=='list'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" saleslabel="已用" covertype="video" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=2"></dp-product-itemlist>
	<!--左右滑动-->
	<dp-product-itemline v-if="params.style=='line'" :data="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" saleslabel="已用" covertype="video" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=2"></dp-product-itemline>
	<!--瀑布流-->
	<dp-product-waterfall v-if="params.style=='waterfall'" :list="data" :saleimg="params.saleimg" :showname="params.showname" :showprice="params.showprice" :showsales="params.showsales" :showcart="params.showcart" :cartimg="params.cartimg" :carttext="params.carttext" idfield="proid" :menuindex="menuindex" :probgcolor="params.probgcolor" saleslabel="已用" covertype="video" :cover_ratio="coverRatio" :cover_radius="coverRadius" :card_radius="cardRadius" :btn_position="btnPosition" :card_gap="cardGap" :info_padding="infoPadding" detailurl="/pagesZ/generation/create?type=2"></dp-product-waterfall>
</view>
</template>
<script>
	export default {
		props: {
			menuindex:{default:-1},
			params:{},
			data:{}
		},
		computed: {
			// 封面比例，默认3:4
			coverRatio() {
				return this.params.cover_ratio || '3:4';
			},
			// 封面圆角，默认8rpx
			coverRadius() {
				return this.params.cover_radius !== undefined ? this.params.cover_radius : 8;
			},
			// 卡片圆角，默认8rpx
			cardRadius() {
				return this.params.card_radius !== undefined ? this.params.card_radius : 8;
			},
			// 按钮位置，默认bottom-right
			btnPosition() {
				return this.params.btn_position || 'bottom-right';
			},
			// 卡片间距，默认12rpx
			cardGap() {
				return this.params.card_gap !== undefined ? this.params.card_gap : 12;
			},
			// 信息区内边距，默认12rpx
			infoPadding() {
				return this.params.info_padding !== undefined ? this.params.info_padding : 12;
			}
		},
		methods: {}
	}
</script>
<style>
.dp-video-generation{width:100%;height: auto; position: relative;overflow: hidden; padding: 0px; background: #fff;}
</style>
