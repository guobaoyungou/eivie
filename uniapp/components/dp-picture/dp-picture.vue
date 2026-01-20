<template>
<view class="dp-picture" :style="{
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx'
}">
	<view v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="item.hrefurl" style="box-sizing: border-box;">
		<button class="picture-button" v-if="item.hrefurl=='contact::'" open-type="contact" show-message-card="true">
			<image class="picture-img" show-menu-by-longpress='true' :src="item.imgurl" mode="widthFix" :style="{borderRadius:(params.borderradius*2.2)+'rpx'}"></image>
		</button>
		<button class="picture-button" v-else-if="item.hrefurl=='share::'" open-type="share">
			<image class="picture-img" show-menu-by-longpress='true' :src="item.imgurl" mode="widthFix" :style="{borderRadius:(params.borderradius*2.2)+'rpx'}"></image>
		</button>
		<image v-else class="picture-img" show-menu-by-longpress='true' :src="item.imgurl" mode="widthFix" :style="{borderRadius:(params.borderradius*2.2)+'rpx'}" @click="previewImage" :data-url="preview&&!item.hrefurl?item.imgurl:''"></image>
	</view>
</view>
</template>
<script>
	export default {
		props: {
			preview:false,
			params:{},
			data:{}
		}
	}
</script>
<style>
.dp-picture{height: auto; position: relative; font-size: 0;}
.dp-picture .picture-img {width: 100%;height:auto; margin: 0px; padding: 0px;border: 0px;box-sizing: border-box;}
.dp-picture .picture-button {line-height:0}
</style>