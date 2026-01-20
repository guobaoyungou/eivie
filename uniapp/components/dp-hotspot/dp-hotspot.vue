<template>
<view class="dp-hotspot" :style="{
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx 0'
}">
	<image class="image" :src="params.imgurl" mode="widthFix"/>
	<view v-for="(item,index) in data" :key="item.id" @click="gotoUrl(item.hrefurl,item)" :style="{
		width:(item.width*2.2)+'rpx',
		height:(item.height*2.2)+'rpx',
		left:(item.left*2.2)+'rpx',
		top:(item.top*2.2)+'rpx'
	}" class="hotarea"></view>
</view>
</template>
<script>
	var app = getApp();
	export default {
		props: {
			params:{},
			data:{}
		},
		methods: {
			gotoUrl:function(url,item){
				if(item.showtip){
					if(item.tip){
						app.error(item.tip);
					}
				}else{
					app.goto(url);
				}
			},
		}
	}
</script>
<style>
.dp-hotspot {position:relative;font-size: 0;}
.dp-hotspot .image{width:100%;height:auto;margin: 0px; padding: 0px;}
.dp-hotspot .hotarea{position:absolute;z-index:3}
</style>