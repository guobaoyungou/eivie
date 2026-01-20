<template>
<view style="width:100%">
	<view class="dp-product-itemline">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto"  :data-url="showtype == '0' ? '/restaurant/takeaway/product?id='+item[idfield] : '/restaurant/shop/product?id='+item[idfield]">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="showname == 1">{{item.name}}</view>
				<view class="p2">
					<view class="p2-1" v-if="showprice != '0'">
						<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
            <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 22rpx;font-weight: 400;">{{item.price_show_text}}</text>
						<text class="t2" v-if="showprice == '1' && item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
					</view>
				</view>
        <!-- 商品处显示会员价 -->
        <view v-if="item.price_show && item.price_show == 1" style="line-height: 44rpx;">
          <text style="font-size:24rpx">￥{{item.sell_putongprice}}</text>
        </view>
        <view v-if="item.priceshows && item.priceshows.length>0">
          <view v-for="(item2,index2) in item.priceshows" style="line-height: 44rpx;">
            <text style="font-size:24rpx">￥{{item2.sell_price}}</text>
            <text style="margin-left: 15rpx;font-size: 22rpx;font-weight: 400;">{{item2.price_show_text}}</text>
          </view>
        </view>
				<view class="p3" v-if="showsales=='1' && item.sales>0">已售{{item.sales}}件</view>
				<!-- <view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="showcart==1" @click.stop="buydialogChange" :data-proid="item[idfield]"><text class="iconfont icon_gouwuche"></text></view>
				<view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="showcart==2" @click.stop="buydialogChange" :data-proid="item[idfield]"><image :src="cartimg" class="img"/></text></view> -->
			</view>
		</view>
	</view>
	<buydialog v-if="buydialogShow" :proid="proid" @addcart="addcart" @buydialogChange="buydialogChange" :menuindex="menuindex" :controller="showtype == '0' ? 'ApiRestaurantTakeaway' : 'ApiRestaurantShop'"></buydialog>
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
			showstyle:{default:2},
			menuindex:{default:-1},
			saleimg:{default:''},
			showname:{default:1},
			namecolor:{default:'#333'},
			showprice:{default:'1'},
			showsales:{default:'1'},
			showcart:{default:'1'},
			showtype:{default:'0'},
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
				console.log(this.buydialogShow);
			},
			addcart:function(){
				this.$emit('addcart');
			}
		}
	}
</script>
<style>
.dp-product-itemline{width:100%;display:flex;overflow-x:scroll;overflow-y:hidden}
.dp-product-itemline .item{width: 220rpx;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;margin-right:4px}
.dp-product-itemline .product-pic {width:220rpx;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-product-itemline .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-product-itemline .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-product-itemline .product-info {padding:20rpx 20rpx;position: relative;}
.dp-product-itemline .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-product-itemline .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-product-itemline .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-product-itemline .product-info .p2-1 .t1{font-size:36rpx;}
.dp-product-itemline .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-product-itemline .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-product-itemline .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx}
.dp-product-itemline .product-info .p4{width:48rpx;height:48rpx;border-radius:50%;position:absolute;display:relative;bottom:20rpx;right:20rpx;text-align:center;}
.dp-product-itemline .product-info .p4 .icon_gouwuche{font-size:28rpx;height:48rpx;line-height:48rpx}
.dp-product-itemline .product-info .p4 .img{width:100%;height:100%}
</style>