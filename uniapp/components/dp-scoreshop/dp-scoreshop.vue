<template>
<view class="dp-scoreshop" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+params.padding_x*2.2+'rpx'
}">
	<view class="dp-scoreshop-item" v-if="params.style=='1' || params.style=='2' || params.style=='3'">
		<view class="item" v-for="(item,index) in data" :style="params.style==2 ? 'width:49%;margin-right:'+(index%2==0?'2%':0) : (params.style==3 ? 'width:32%;margin-right:'+(index%3!=2?'2%':0) :'width:100%')" :key="item.id" @click="goto" :data-url="'/activity/scoreshop/product?id='+item.proid">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
				<view class="p2">
					<view class="p2-1" v-if="params.showprice != '0'">
						<view class="t1" :style="{color:t('color1')}">{{item.score_price}}{{t('积分')}}<text v-if="item.money_price>0 && params.showprice == '1'">+{{item.money_price}}元</text></view>
						<text class="t2" v-if="params.showprice == '1'">￥{{item.sell_price}}</text>
					</view>
				</view>
				<view class="p3" v-if="params.showsales=='1' && item.sales>0">已兑换{{item.sales}}件</view>
			</view>
		</view>
	</view>
	<view class="dp-scoreshop-itemlist" v-if="params.style=='list'">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/activity/scoreshop/product?id='+item.proid">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
				<view class="p2" v-if="params.showprice != '0'">
					<view class="t1" :style="{color:t('color1')}">{{item.score_price}}{{t('积分')}}<text v-if="item.money_price>0 && params.showprice == '1'">+{{item.money_price}}元</text></view>
					<text class="t2" v-if="params.showprice == '1'">￥{{item.sell_price}}</text>
				</view>
				<view class="p3">
					<view class="p3-1" v-if="params.showsales=='1' && item.sales>0"><text style="overflow:hidden">已兑换{{item.sales}}件</text></view>
				</view>
			</view>
		</view>
	</view>
	<view class="dp-scoreshop-itemline" v-if="params.style=='line'">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/activity/scoreshop/product?id='+item.proid">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
			</view>
			<view class="product-info">
				<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
				<view class="p2">
					<view class="p2-1" v-if="params.showprice != '0'">
						<view class="t1" :style="{color:t('color1')}">{{item.score_price}}{{t('积分')}}<text v-if="item.money_price>0 && params.showprice == '1'">+{{item.money_price}}元</text></view>
						<text class="t2" v-if="params.showprice == '1'">￥{{item.sell_price}}</text>
					</view>
				</view>
				<view class="p3" v-if="params.showsales=='1' && item.sales>0">已兑换{{item.sales}}件</view>
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
.dp-scoreshop{height: auto; position: relative;overflow: hidden; padding: 0px; background: #fff;}
.dp-scoreshop-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-scoreshop-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden}
.dp-scoreshop-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-scoreshop-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-scoreshop-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-scoreshop-item .product-info {padding:20rpx 20rpx;position: relative;}
.dp-scoreshop-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-scoreshop-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-scoreshop-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-scoreshop-item .product-info .p2-1 .t1{font-size:30rpx;}
.dp-scoreshop-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-scoreshop-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-scoreshop-item .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx}

.dp-scoreshop-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-scoreshop-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:20rpx;border-radius:10rpx}
.dp-scoreshop-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-scoreshop-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-scoreshop-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-scoreshop-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.dp-scoreshop-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-scoreshop-itemlist .product-info .p2{margin-top:20rpx;height:56rpx;line-height:56rpx;overflow:hidden;}
.dp-scoreshop-itemlist .product-info .p2 .t1{font-size:30rpx;}
.dp-scoreshop-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-scoreshop-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx}
.dp-scoreshop-itemlist .product-info .p3-1{font-size:20rpx;height:30rpx;line-height:30rpx;text-align:right;color:#999}

.dp-scoreshop-itemline{width:100%;display:flex;overflow-x:scroll;overflow-y:hidden}
.dp-scoreshop-itemline .item{width: 220rpx;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;margin-right:4px}
.dp-scoreshop-itemline .product-pic {width:220rpx;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-scoreshop-itemline .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-scoreshop-itemline .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-scoreshop-itemline .product-info {padding:20rpx 20rpx;position: relative;}
.dp-scoreshop-itemline .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-scoreshop-itemline .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-scoreshop-itemline .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-scoreshop-itemline .product-info .p2-1 .t1{font-size:30rpx;}
.dp-scoreshop-itemline .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-scoreshop-itemline .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-scoreshop-itemline .product-info .p3{color:#999999;font-size:20rpx;margin-top:10rpx}
</style>
