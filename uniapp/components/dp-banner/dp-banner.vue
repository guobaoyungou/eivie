<template>
<view class="dp-banner" :class="{ 'zindex1': params.navigation_bg == 1 }" :style="bannerStyle">
	<block v-if="params.style && params.style==1">
		<view class="sbox">
			<view :style="{height:'40rpx'}"></view>
			<view class="bgswiper" :style="{height:params.height*1.2+'rpx',background:'url('+data[bannerindex].imgurl+')'}"></view>
			<swiper :autoplay="false" @change="bannerchange" :circular="true" displayMultipleItems="1" indicatorActiveColor="white" :indicatorDots="false" nextMargin="80rpx" snapToEdge="true" :style="{height:(params.height*2.2)+'rpx','padding-top':'20rpx'}">
				<swiper-item @click="goto" :data-url="item.hrefurl" class="switem" v-for="(item,index) in data" :key="item.id" :style="{height:(params.height*2.2)+'rpx','padding-top':'20rpx'}">
					<image class="sitem" :class="bannerindex==index?'active':'noactive'" mode="scaleToFill" :src="item.imgurl" :style="{height:(params.height*2.2-20)+'rpx'}"></image>
				</swiper-item>
			</swiper>
		</view>
	</block>
	<block v-else>
    <swiper class="dp-banner-swiper" :autoplay="true" :indicator-dots="false" :current="0" :circular="true" :style="swiperStyle" :interval="params.interval*1000" @change="bannerchange">
      <block v-for="item in data" :key="item.id"> 
        <swiper-item>
			<view :style="{height:(params.height*2.2)+'rpx',borderRadius:(params.borderradius)+'px',overflow:'hidden'}" @click="goto" :data-url="item.hrefurl">
				<image :src="item.imgurl" class="dp-banner-swiper-img" mode="widthFix"/>
			</view>
		</swiper-item>
      </block>
    </swiper>
	</block>
	<view v-if="params.indicatordots=='1'" class="dp-banner-swiper-pagination" :style="{justifyContent:(params.align=='center'?'center':(params.align=='left'?'flex-start':'flex-end')),bottom:(params.dotSite)+'px'}">
		<block v-for="(item,index) in data" :key="item.id">
			<block v-if="params.shape==''">
				<view v-if="bannerindex==index" class="dp-banner-swiper-shape0 dp-banner-swiper-shape0-active" :style="{backgroundColor:params.indicatoractivecolor}"></view>
				<view v-else class="dp-banner-swiper-shape0" :style="{backgroundColor:params.indicatorcolor}"></view>
			</block>
			<block v-if="params.shape=='shape1'">
				<view v-if="bannerindex==index" class="dp-banner-swiper-shape1" :style="{backgroundColor:params.indicatoractivecolor}"></view>
				<view v-else class="dp-banner-swiper-shape1" :style="{backgroundColor:params.indicatorcolor}"></view> 
			</block>
			<block v-if="params.shape=='shape2'">
				<view v-if="bannerindex==index" class="dp-banner-swiper-shape2" :style="{backgroundColor:params.indicatoractivecolor}"></view>
				<view v-else class="dp-banner-swiper-shape2" :style="{backgroundColor:params.indicatorcolor}"></view>
			</block>
			<block v-if="params.shape=='shape3'">
				<view v-if="bannerindex==index" class="dp-banner-swiper-shape3" :style="{backgroundColor:params.indicatoractivecolor}"></view>
				<view v-else class="dp-banner-swiper-shape3" :style="{backgroundColor:params.indicatorcolor}"></view>
			</block>
		</block>
	</view>
	<!--  #ifdef MP-WEIXIN -->
	<view class="banner-bg" :style="{'background-image':'url('+ bannerbg +')'}" v-if="params.navigation_bg == 1"></view>
	<!--  #endif -->
</view>
</template>
<script>
	export default {
		data(){
			return {
				"bannerindex":0,
				"bannerbg":'',
			}
    },
		props: {
			params:{},
			data:{}
		},
		created() {
			if(this.params.navigation_bg && this.data && this.data.length > 0) {
				this.bannerbg = this.data[0].imgurl;
				this.bannerindex = 0;
			}
		},
		computed: {
			bannerStyle(){
				let params = this.params;
				// #ifdef MP-WEIXIN
				if(params.navigation_bg == 1){
					let style = '';
					style += `padding: ${params.padding_y * 2.2 + 20}rpx ${params.padding_x * 2.2 + 20}rpx`;
					return style;
				}
				// #endif
				let style = '';
				if(params.bgcolor){
					style += `background-color: ${params.bgcolor};`;
				}else{
					style += `background-color: #fff;`;
				}
				style += `margin: ${params.margin_y * 2.2}rpx ${params.margin_x * 2.2}rpx 0;`;
				style += `padding: ${params.padding_y * 2.2}rpx ${params.padding_x * 2.2}rpx;`;
				return style;
			},
			swiperStyle() {
				let params = this.params;
				let style = '';
				// #ifdef MP-WEIXIN
				if(params.navigation_bg == 1){
					style += `margin: ${params.margin_y * 2.2}rpx ${params.margin_x * 2.2}rpx;`;
					style += `border-radius: ${params.borderradius}px;`;
				}
				// #endif
				style += `height: ${params.height * 2.2}rpx;`;
				return style;
			}
		},
		methods:{
			bannerchange:function(e){
				var that = this
				var idx = e.detail.current;
				that.bannerindex = idx
				// #ifdef MP-WEIXIN
				that.bannerbg = that.data[idx].imgurl
				that.$emit('banner-change', {
					currentIndex: idx,
					currentImg: that.bannerbg
				})
				// #endif
			}
		}
	}
</script>
<style>
.dp-banner{position:relative;}
.dp-banner-swiper{width:100%;height:380rpx}
.dp-banner-swiper-img{width:100%;height:auto}
.dp-banner-swiper-pagination{padding:0 10px;bottom:12px;left:0;position:absolute;display:flex;justify-content:center;width:100%}
.dp-banner-swiper-shape0{width:3px;height:3px;margin:0 2px!important;}
.dp-banner-swiper-shape0-active{width:13px;border-radius:1.5px;}
.dp-banner-swiper-shape1{width:12px;height:6px;border-radius:0;margin:0 2px}
.dp-banner-swiper-shape2{width:8px;height:8px;border-radius:0;margin:0 2px}
.dp-banner-swiper-shape3{width:8px;height:8px;border-radius:50%;margin:0 2px;}
.dp-banner-swiper-shape4{width:8px;height:3px;border-radius:50%;margin:0 1px;}
.dp-banner-swiper-shape4-active{width:13px;border-radius:1.5px;}

.sbox{overflow:hidden;position:relative}
.switem{margin-left:40rpx;width:526rpx!important}
.sitem{border-radius:24rpx;overflow:hidden;width:526rpx}
.noactive{-webkit-transform:scale(.84);transform:scale(.84);transition:all .2s ease-in 0s;z-index:20}
.active{-webkit-transform:scale(1.01);transform:scale(1.01);transition:.5s;z-index:20}
.bgswiper{-webkit-backdrop-filter:blur(100rpx);backdrop-filter:blur(100rpx);background-origin:center center;background-repeat:no-repeat;background-size:cover;border-radius:0 0 30% 30%;-webkit-filter:blur(6rpx);filter:blur(6rpx);left:0;position:absolute;right:0;top:0;transition:.2s linear;width:100vw;}
.banner-bg {position: absolute;top: 0;left: 0;width: 100%;height: 100%;background-size: 100% 200%;filter: blur(10px);z-index: -1;background-position: bottom;background-repeat: no-repeat;clip-path: inset(-20px -20px 0 -20px);}
.zindex1{z-index: 1}
</style>