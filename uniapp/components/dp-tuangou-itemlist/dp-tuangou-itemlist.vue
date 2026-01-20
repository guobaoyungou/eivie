<template>
<view style="width:100%">
	<view class="dp-tuangou-itemlist">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/activity/tuangou/product?id='+item[idfield]">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="showname == 1">{{item.name}}</view>
				<view class="p2" v-if="showprice != '0'">
					<text class="t1" :style="{color:t('color1')}">
						<block v-if="item.usd_minprice">
							<text style="font-size:24rpx">低至$</text>{{item.usd_minprice}}
							<text style="font-size:24rpx;padding-right:3px">￥{{item.min_price}}</text>
						</block>
						<block v-else>
							<text style="font-size:24rpx;padding-right:1px">低至￥</text>{{item.min_price}}
						</block>
						</text>
					<text class="t2" v-if="showprice == '1' && item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
				</view>
				<view class="p3">
					<view class="p3-1" v-if="showsales=='1' && item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
				</view>
				<view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="showcart==1" @click.stop="buydialogChange" :data-proid="item[idfield]"><text class="iconfont icon_gouwuche"></text></view>
				<view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="showcart==2" @click.stop="buydialogChange" :data-proid="item[idfield]"><image :src="cartimg" class="img"/></text></view>
			</view>
		</view>
	</view>
	<buydialog-tuangou v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" controller="ApiTuangou" :menuindex="menuindex"></buydialog-tuangou>
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
.dp-tuangou-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-tuangou-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:20rpx;border-radius:10rpx}
.dp-tuangou-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-tuangou-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-tuangou-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-tuangou-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.dp-tuangou-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-tuangou-itemlist .product-info .p2{margin-top:20rpx;height:56rpx;line-height:56rpx;overflow:hidden;}
.dp-tuangou-itemlist .product-info .p2 .t1{font-size:36rpx;}
.dp-tuangou-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-tuangou-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.dp-tuangou-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}
.dp-tuangou-itemlist .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:6rpx;right:4rpx;text-align:center;}
.dp-tuangou-itemlist .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-tuangou-itemlist .product-info .p4 .img{width:100%;height:100%}
</style>