<template>
<view class="dp-collage" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+params.padding_x*2.2+'rpx'
}">
	<view class="dp-collage-item" v-if="params.style=='1' || params.style=='2' || params.style=='3'">
		<view class="item" v-for="(item,index) in data" :style="params.style==2 ? 'width:49%;margin-right:'+(index%2==0?'2%':0) : (params.style==3 ? 'width:32%;margin-right:'+(index%3!=2?'2%':0) :'width:100%')" :key="item.id" @click="goto" :data-url="'/pagesExt/cycle/product?id='+item.proid">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
				<view class="p2">
					<view class="p2-1" v-if="params.showprice != '0'">
						<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
						<text class="t2" v-if="params.showprice == '1' && item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
					</view>
				</view>
				<view class="p3">
					<view class="p3-1" :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}">{{item.pspl}}</view>
					<view class="p3-2" v-if="params.showsales=='1' && item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
				</view>
			</view>
		</view>
	</view>
	<view class="dp-collage-itemlist" v-if="params.style=='list'">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/pagesExt/cycle/product?id='+item.proid">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
				<view class="p2" v-if="params.showprice != '0'">
					<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx;padding-right:1px">￥</text>{{item.sell_price}}</text>
					<text class="t2" v-if="params.showprice == '1' && item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
				</view>
				<view class="p3">
					<view class="p3-1" :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}">{{item.pspl}}</view>
					<view class="p3-2" v-if="params.showsales=='1' && item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
				</view>
			</view>
		</view>
	</view>
	<view class="dp-collage-itemline" v-if="params.style=='line'">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/pagesExt/cycle/product?id='+item.proid">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
				<view class="p2">
					<view class="p2-1" v-if="params.showprice != '0'">
						<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
						<text class="t2" v-if="params.showprice == '1' && item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
					</view>
				</view>
				<view class="p3">
					<view class="p3-1" :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}">{{item.pspl}}</view>
					<view class="p3-2" v-if="params.showsales=='1' && item.sales>0"><text style="overflow:hidden">已售{{item.sales}}件</text></view>
				</view>
			</view>
		</view>
	</view>
</view>
</template>
<script>
	export default {
		props: {
			params:{},
			data:{}
		}
	}
</script>
<style>
.dp-collage{height: auto; position: relative;overflow: hidden; padding: 0px; background: #fff;}
.dp-collage-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-collage-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden}
.dp-collage-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-collage-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-collage-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-collage-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-collage-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-collage-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-collage-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-collage-item .product-info .p2-1 .t1{font-size:36rpx;}
.dp-collage-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-collage-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-collage-item .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-collage-item .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-collage-item .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}

.dp-collage-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-collage-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:20rpx;border-radius:10rpx}
.dp-collage-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-collage-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-collage-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-collage-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.dp-collage-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-collage-itemlist .product-info .p2{margin-top:20rpx;height:56rpx;line-height:56rpx;overflow:hidden;}
.dp-collage-itemlist .product-info .p2 .t1{font-size:36rpx;}
.dp-collage-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-collage-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-collage-itemlist .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-collage-itemlist .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}

.dp-collage-itemline{width:100%;display:flex;overflow-x:scroll;overflow-y:hidden}
.dp-collage-itemline .item{width: 220rpx;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;margin-right:4px}
.dp-collage-itemline .product-pic {width:220rpx;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-collage-itemline .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-collage-itemline .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-collage-itemline .product-info {padding:20rpx 20rpx;position: relative;}
.dp-collage-itemline .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-collage-itemline .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-collage-itemline .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-collage-itemline .product-info .p2-1 .t1{font-size:36rpx;}
.dp-collage-itemline .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-collage-itemline .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-collage-itemline .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-collage-itemline .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-collage-itemline .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}
</style>