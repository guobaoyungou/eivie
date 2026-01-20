<template>
<view class="dp-menu" :style="{
	fontSize:params.fontsize*2+'rpx',
	backgroundColor:params.bgcolor,
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',
	borderRadius:params.boxradius*2.2+'rpx'
}">
	<view style="padding-top:16rpx;">
		<view v-if="params.showtitle==1" class="menu-title" :style="{color:params.titlecolor,fontSize:params.titlesize*2.2+'rpx'}">{{params.title}}</view>
		<block v-if="params.newdata.length >1">
		<swiper :autoplay="false" :indicator-dots="false" :current="0" @change="bannerchange" :style="{
					width:'100%',
					height:(params.newdata_linenum*(params.iconsize*2.2 + params.fontsize*2 + 50)+'rpx' || '350rpx'),
					overflow:'hidden'
				}">
			<swiper-item v-for="item in params.newdata" :key="item.id">
				<view class="swiper-item" :style="{
					width:'100%',
					height:(params.newdata_linenum*(params.iconsize*2.2 + params.fontsize*2 + 50)+'rpx' || '350rpx'),
					overflow:'hidden'
				}">
					<view v-for="item2 in item" :key="item2.id" :class="'menu-nav'+params.num+' '+(params.showicon==0 && params.showline==1 ? ' showline':'')" @click="goto" :data-url="item2.hrefurl">
						<image v-if="params.showicon==1" :src="item2.imgurl" :style="{borderRadius:params.radius/2+'%',width:params.iconsize*2.2+'rpx',height:params.iconsize*2.2+'rpx'}"></image>
						<view class="menu-text" :style="{color:item2.color,height:params.fontheight*2.2+'rpx',lineHeight:params.fontheight*2.2+'rpx'}">{{item2.text|| '按钮文字'}}</view>
					</view>
				</view>
			</swiper-item>
		</swiper>
		<view class="swiper-pagination" style="justify-content:center;bottom:8px">
			<block v-for="(item,index) in params.newdata" :key="item.id">
				<view v-if="bannerindex==index" class="swiper-shape4 swiper-shape4-active" style="background-color:#3db51e"></view>
				<view v-else class="swiper-shape4" style="background-color:#edeef0"></view>
			</block>
		</view>
		</block>
		<block v-else>
		<view class="swiper-item">
			<view v-for="item in data" :key="item.id" :class="'menu-nav'+params.num+' '+(params.showicon==0 && params.showline==1 ? ' showline':'')" @click="goto" :data-url="item.hrefurl"
			 v-if="item.ismendian==0 || (!item.ismendian) || (isapplymendian==1 && item.ismendian==1) || (isapplymendian==2 && item.ismendian==2) ">
				<image v-if="params.showicon==1" :src="item.imgurl" :style="{borderRadius:params.radius/2+'%',width:params.iconsize*2.2+'rpx',height:params.iconsize*2.2+'rpx'}"></image>
				<view class="menu-text" :style="{color:item.color,height:params.fontheight*2.2+'rpx',lineHeight:params.fontheight*2.2+'rpx'}">{{item.text|| '按钮文字'}}</view>
			</view>
		</view>
		</block>
	</view>
</view>
</template>
<script>
	export default {
		data(){
			return {"bannerindex":0}
    },
		props: {
			params:{},
			data:{},
			isapplymendian:{default:0}
		},
		methods:{
			bannerchange:function(e){
				console.log(this.params)
				var that = this
				var idx = e.detail.current;
				that.bannerindex = idx
			}
		}
	}
</script>
<style>
.dp-menu {height:auto;position:relative;padding-left:20rpx; padding-right:20rpx; background: #fff;}
.dp-menu .menu-title{width:100%;font-size:30rpx;color:#333333;font-weight:bold;padding:0 0 32rpx 24rpx}
.dp-menu .swiper-item{display:flex;flex-wrap:wrap;flex-direction: row;height:auto;overflow: hidden;align-items: flex-start;}
.dp-menu .menu-nav {flex:1;text-align:center;}
.dp-menu .menu-nav5 {width:20%;text-align:center;margin-bottom:16rpx;position:relative}
.dp-menu .menu-nav4 {width:25%;text-align:center;margin-bottom:16rpx;position:relative}
.dp-menu .menu-nav3 {width:33.3%;text-align:center;margin-bottom:16rpx;position:relative}
.dp-menu .menu-nav2 {width:50%;text-align:center;margin-bottom:16rpx;position:relative}
.dp-menu .showline:after{position:absolute;top:50%;right:0;margin-top:-16rpx;content:'';height:36rpx;border-right:1px solid #eee}
.dp-menu .menu-nav2.showline:nth-child(2n+2):after{border-right:0}
.dp-menu .menu-nav3.showline:nth-child(3n+3):after{border-right:0}
.dp-menu .menu-nav4.showline:nth-child(4n+4):after{border-right:0}
.dp-menu .menu-nav5.showline:nth-child(5n+5):after{border-right:0}
.swiper-pagination{padding:0 10px;bottom:12px;left:0;position:absolute;display:flex;justify-content:center;width:100%}
.swiper-shape0{width:3px;height:3px;margin:0 2px!important;}
.swiper-shape0-active{width:13px;border-radius:1.5px;}
.swiper-shape1{width:12px;height:6px;border-radius:0;margin:0 2px}
.swiper-shape2{width:8px;height:8px;border-radius:0;margin:0 2px}
.swiper-shape3{width:8px;height:8px;border-radius:50%;margin:0 2px;}
.swiper-shape4{width:8px;height:3px;border-radius:50%;margin:0 1px;}
.swiper-shape4-active{width:13px;border-radius:1.5px;}
</style>