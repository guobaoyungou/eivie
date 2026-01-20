<template>
<view class="dp-coupon" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'
}">
	<scroll-view scroll-x="true">
	<view class="item">
		<view class="coupon" v-for="item in data" :key="item.id" :style="{background:'url('+params.bgpic+')',backgroundSize:'100%'}" @click="goto" :data-url="'/pagesExt/coupon/coupondetail?id='+item.couponid">
			<view class="f1" :style="{color:params.titlecolor}" v-if="item.type==1"><text style="font-size:20rpx">￥</text>{{item.money}}<text style="font-size:24rpx"> {{t('优惠券')}}</text></view>
			<view class="f1" :style="[{color:params.titlecolor}]" v-if="item.type!=1"><text style="font-size:28rpx">{{item.name}}</text></view>
			<view class="f2" :style="[{color:params.remarkcolor}]" v-if="item.type==1&&item.minprice!=0">满{{item.minprice}}元可用</view>
			<view class="f2" :style="[{color:params.remarkcolor}]" v-if="item.type==1&&item.minprice==0">无门槛</view>
			<view class="f2" :style="[{color:params.remarkcolor}]" v-if="item.type==2">礼品券</view>
			<view class="f2" :style="[{color:params.remarkcolor}]" v-if="item.type==3">计次券</view>
			<view class="f2" :style="[{color:params.remarkcolor}]" v-if="item.type==4">运费抵扣券</view>
		</view>
	</view>
	</scroll-view>
	</view>
</template>
<script>
	export default {
		props: {
			params:{},
			data:{},
			textset:{}
		}
	}
</script>
<style>
.dp-coupon{height: auto; position: relative;}
.dp-coupon .item{display:flex}
.dp-coupon .item .coupon{flex-shrink:0;width:200rpx;height:120rpx;background-size:100%;color:#fff;text-align:center;margin:0 8rpx}
.dp-coupon .item .coupon .f1{font-size:40rpx;padding-top:6rpx;margin-bottom:6rpx}
.dp-coupon .item .coupon .f2{font-size:22rpx;color:#eee}
</style>