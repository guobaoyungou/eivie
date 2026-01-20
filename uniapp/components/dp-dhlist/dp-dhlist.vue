<template>
<view class="dp-dhlist" :style="{
	color:params.color,
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0'
}">
	<block v-for="item in data" :key="item.id">
	<view class="dp-dhlist-item" :style="{padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx'}" @click="goto" :data-url="item.hrefurl">
		<view class="dp-dhlist-text1" :style="{fontSize:params.fontsize1*2+'rpx',color:params.color1}">
			<image class="image" v-if="item.showicon==1" :src="item.imgurl" :style="{width:item.iconsize*2+'rpx'}" mode="widthFix"/>
			<text v-if="item.title1!=''" class="dp-dhlist-title1" :style="{'margin-left':params.titlemarginleft*2+'rpx'}">{{item.title1}}</text>
		</view>
		<view class="dp-dhlist-text2" :style="{fontSize:params.fontsize2*2+'rpx',color:params.color2}">{{item.title2}}</view>
		<image :src="pre_url+'/static/img/arrowright.png'" style="width:32rpx;height:32rpx" v-if="params.arrowshow==1"/> 
	</view>
	</block>
</view>
</template>
<script>
var app = getApp();
	export default {
		props: {
			params:{},
			data:{},
		},
        data() {
            return {
                pre_url:app.globalData.pre_url,
            }
        }
	}
</script>
<style>
.dp-dhlist{height: auto; position: relative;}
.dp-dhlist-item {width:100%;height: auto;overflow: hidden;display:flex;align-items:center;border-bottom:1px solid #f5f5f5}
.dp-dhlist-item:last-child{border-bottom:0}
.dp-dhlist-text{padding:0;}
.dp-dhlist-text1{flex:1;display:flex;align-items:center;}
.dp-dhlist-text1 .image{max-width:100rpx;max-height:100rpx;margin:0 0px;height:auto}
.dp-dhlist-title1{margin:0 16rpx}
.dp-dhlist-text2{text-align:right;height:32rpx;line-height:32rpx}
</style>