<template>
<view style="width:100%">
	<view class="dp-product-itemlist">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/activity/yuyue/product?id='+item[idfield]">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="showname == 1">{{item.name}}</view>
				<view class="p2" v-if="showprice != '0'">
					<view class="t1" :style="{color:t('color1')}">{{item.sell_price}}<text style="font-size:24rpx;padding-left:4rpx">元/{{item.danwei}}</text></view>
				</view>
				<view class="p3">
					<view class="p3-1" v-if="showsales=='1' && item.sales>0"><text style="overflow:hidden">已售{{item.sales}}</text></view>
				</view>
		
			</view>
		</view>
	</view>
	<buydialog v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
</view>
</template>
<script>
	export default {
		data(){
			return {
				buydialogShow:false,
				proid:0,
			}
		},
		props: {
			menuindex:{default:-1},
			saleimg:{default:''},
			showname:{default:1},
			namecolor:{default:'#333'},
			showprice:{default:'1'},
			showsales:{default:'1'},
			showcart:{default:'1'},
			cartimg:{default:'/static/imgsrc/cart.svg'},
			data:{},
			idfield:{default:'id'}
		},
		methods: {
			buydialogChange: function (e) {
				if(!this.buydialogShow){
					this.proid = e.currentTarget.dataset.proid
				}
				this.buydialogShow = !this.buydialogShow;
			},
		}
	}
</script>
<style>
.dp-product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-product-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:20rpx;border-radius:10rpx}
.dp-product-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-product-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-product-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.dp-product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-product-itemlist .product-info .p2{margin-top:20rpx;height:56rpx;line-height:56rpx;overflow:hidden;}
.dp-product-itemlist .product-info .p2 .t1{font-size:36rpx;}
.dp-product-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.dp-product-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.dp-product-itemlist .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.dp-product-itemlist .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-product-itemlist .product-info .p4 .img{width:100%;height:100%}
</style>