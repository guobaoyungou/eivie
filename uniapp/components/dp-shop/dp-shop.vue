<template>
<view>
	<view v-if="params.style == 1" class="dp-shop-1">
		<image class="shop1-img" :src="params.bgimg"></image>
		<view v-if="params.menu == 1" class="shop1-menu" :style="{color:params.navcolor}">
			<view v-for="item in data" :key="item.id" @click="goto" :data-url="item.hrefurl">
				<view class="shop1-nav">
					<image :src="item.imgurl" style="width:36rpx;height:36rpx"/>
					<view>{{item.text}}</view>
				</view>
			</view>
		</view>
		<view class="shop1-shopname" v-if="params.name == 1">
			<view class="shop1-name">{{shopinfo.name}}</view>
		</view>
		<view class="shop1-shoplogo" v-if="params.logo == 1"> 
			<view class="shop1-shoplogo-img">
				<image class="shop1-shoplogo-img-img" :src="shopinfo.logo"></image>
			</view>
		</view>
	</view>
	<view v-if="params.style == 2" class="dp-shop-2">
		<image class="shop2-img" :src="params.bgimg"></image>
		<view v-if="params.logo == 1" class="shop2-shoplogo">
			<image class="shop2-shoplogo-img" :src="shopinfo.logo"></image>
		</view>
		<view v-if="params.name == 1" class="shop2-shopname">{{shopinfo.name}}</view>
		<view v-if="params.menu == 1" class="shop2-menu" :style="{color:params.navcolor}">
			<view class="shop2-nav" v-for="item in data" :key="item.id" @click="goto" :data-url="item.hrefurl">
				<image :src="item.imgurl" style="width:36rpx;height:36rpx"/>
				<view style="font-size:24rpx;">{{item.text}}</view>
			</view>
		</view>
	</view>
</view>
</template>
<script>
	var app = getApp();
	export default {
		props: {
			params:{},
			data:{},
			shopinfo:{},
		}
	}
</script>
<style>
.dp-shop-1{height: auto; position: relative;min-height:348rpx;}
.dp-shop-1 .shop1-img {width: 100%; display: block;}
.dp-shop-1 .shop1-menu {height: 90rpx; width: 100%; position: absolute; bottom: 0px; left: 0px; background: rgba(0,0,0,0.3); color:#fff;line-height:34rpx}
.dp-shop-1 .shop1-nav {height:80rpx; width:25%; padding-top:10rpx; float:left; text-align:center; font-size:24rpx;}
.dp-shop-1 .shop1-nav-on{height:90rpx; border-bottom:6rpx solid #dd2322;}
.dp-shop-1 .shop1-shopname {height:48rpx; width:100%; position: absolute; bottom: 110rpx; left: 0px;}
.dp-shop-1 .shop1-name {height: 48rpx; width: auto; background: rgba(0,0,0,0.2); line-height: 48rpx; margin: auto; display: table; border-radius: 48rpx; font-size: 28rpx; color: #fff; padding: 0px 30rpx;}
.dp-shop-1 .shop1-shoplogo {height:160rpx; width:160rpx; padding:8rpx; border:1px solid #fff; border-radius:90rpx; margin-left: -80rpx; position: absolute; bottom: 176rpx; left: 50%;}
.dp-shop-1 .shop1-shoplogo-img {height:140rpx; width:140rpx; padding:8rpx; border:1px solid #fff; border-radius:140rpx; margin:auto;}
.dp-shop-1 .shop1-shoplogo-img-img {height: 120rpx; width: 120rpx; border-radius: 120rpx;}

.dp-shop-2{height: auto; position: relative;min-height:200rpx;}
.dp-shop-2 .shop2-img {width: 100%;display:block}
.dp-shop-2 .shop2-menu {height:90rpx; padding:10rpx 0px 0px 180rpx; text-align:center; background: rgba(0,0,0,0.3);line-height:34rpx;display:flex;position:absolute; left:0; bottom:0;width:100%;z-index:2}
.dp-shop-2 .shop2-nav {height: 80rpx; width: 25%;}
.dp-shop-2 .shop2-shoplogo {height:132rpx; width:132rpx; background:#f5f5f5; position:absolute; left:28rpx; bottom:34rpx; border:1px solid #fff;box-shadow:0px 0px 2px rgba(0,0,0,0.1);z-index:3}
.dp-shop-2 .shop2-shoplogo-img {height:128rpx; width:128rpx;}
.dp-shop-2 .shop2-shopname {height:80rpx; width:auto; position:absolute; left:180rpx; bottom:90rpx; font-size:32rpx; line-height:80rpx; font-size:36rpx; color:#fff; text-shadow:2px 2px 2px rgba(0,0,0,0.2);z-index:3}
</style>