<template>
<view class="dp-luckycollage" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+params.padding_x*2.2+'rpx'
}">
	<view class="dp-collage-item" v-if="params.style=='2'">
		<view class="item" v-for="(item,index) in data" :style="params.style==2 ? 'width:49%;margin-right:'+(index%2==0?'2%':0) : (params.style==3 ? 'width:32%;margin-right:'+(index%3!=2?'2%':0) :'width:100%')" :key="item.id" @click="goto" :data-url="'/activity/luckycollage/'+ (item.linktype==1?'product2':'product')+'?id='+item.proid">
			<view class="product-pic">
				<image class="image" :src="item.pic" mode="widthFix"/>
				<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				<view class="desc" v-if="item.show_teamnum == 1">
					<text v-if="item.gua_num>0" > {{item.teamnum}}人拼团 {{item.gua_num}}人得商品</text>
					<text v-else style="line-height: 80rpx;"> {{item.teamnum}}人拼团 {{item.gua_num}}人得商品</text>
					<view v-if="item.linktype==1">
						<text v-if="item.teamnum-item.gua_num>0 && item.bzjl_type==1">{{item.teamnum-item.gua_num}}人{{item.teamnum-item.gua_num>1?'各':''}}得{{item.money}}元参与奖</text>
						<text v-if="item.teamnum-item.gua_num>0 && item.bzjl_type==2">{{item.teamnum-item.gua_num}}人{{item.teamnum-item.gua_num>1?'各':''}}得{{item.bzj_score}}积分</text>
						<text v-if="item.teamnum-item.gua_num>0 && item.bzjl_type==3">{{item.teamnum-item.gua_num}}人{{item.teamnum-item.gua_num>1?'各':''}}得{{item.bzj_commission}}元佣金</text>
						<text v-if="item.teamnum-item.gua_num>0 && item.bzjl_type==4">{{item.teamnum-item.gua_num}}人{{item.teamnum-item.gua_num>1?'各':''}}得{{item.money}}优惠券</text>
					</view>
					<view v-else>
						<text v-if="item.teamnum-item.gua_num>0">{{item.teamnum-item.gua_num}}人{{item.teamnum-item.gua_num>1?'各':''}}得{{item.money}}元参与奖</text>
					</view>
				</view>
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
					<view class="p3-1" :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}">{{item.teamnum}}人拼</view>
					<view class="p3-2" v-if="params.showsales=='1' && item.sales>0"><text style="overflow:hidden">已拼成{{item.sales}}件</text></view>
				</view>
			</view>
		</view>
	</view>
	<view class="dp-collage-itemlist" v-if="params.style=='list'">
		<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/activity/luckycollage/'+ (item.linktype==1?'product2':'product')+'?id='+item.proid">
			<view class="flex">
				<view class="product-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
					<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1" v-if="params.showname == 1"><text class="team_text">{{item.teamnum}}人拼</text>{{item.name}}</view>
					<view class="p2 flex-bt" v-if="params.showprice != '0'">
						<view>
							<text class="t1" :style="{color:t('color1')}"><text style="font-size:24rpx;padding-right:1px">￥</text>{{item.sell_price}}</text>
							<text class="t2" v-if="params.showprice == '1' && item.market_price*1 > item.sell_price*1">￥{{item.market_price}}</text>
						</view>
						<view class="p3">
							<view class="p3-2" v-if="params.showsales=='1' && item.sales>0"><text style="overflow:hidden">已拼成{{item.sales}}件</text></view>
						</view>
					</view>
				
				</view>
			</view>
					<view v-if="item.linktype==1">
						<view class="desc" v-if="item.bzjl_type==1"><text>{{item.teamnum}}人拼团 {{item.gua_num}}人得商品</text><text v-if="item.teamnum-item.gua_num>0">{{item.teamnum-item.gua_num}}人{{item.teamnum-item.gua_num>1?'各':''}}得{{item.money}}元参与奖</text></view>
						<view class="desc" v-if="item.bzjl_type==2"><text>{{item.teamnum}}人拼团 {{item.gua_num}}人得商品</text><text v-if="item.teamnum-item.gua_num>0">{{item.teamnum-item.gua_num}}人{{item.teamnum-item.gua_num>1?'各':''}}得{{item.bzj_score}}积分</text></view>
						<view class="desc" v-if="item.bzjl_type==3"><text>{{item.teamnum}}人拼团 {{item.gua_num}}人得商品</text><text v-if="item.teamnum-item.gua_num>0">{{item.teamnum-item.gua_num}}人{{item.teamnum-item.gua_num>1?'各':''}}得{{item.bzj_commission}}元佣金</text></view>
						<view class="desc" v-if="item.bzjl_type==4"><text>{{item.teamnum}}人拼团 {{item.gua_num}}人得商品</text><text v-if="item.teamnum-item.gua_num>0">{{item.teamnum-item.gua_num}}人{{item.teamnum-item.gua_num>1?'各':''}}得{{item.money}}优惠券</text></view>
					</view>
					<view v-else>
						<view class="desc" v-if="item.show_teamnum == 1" ><text>{{item.teamnum}}人拼团 {{item.gua_num}}人得商品</text><text v-if="item.gua_num>0">{{item.teamnum-item.gua_num}}人{{item.teamnum-item.gua_num>1?'各':''}}得{{item.money}}元参与奖</text></view>
							</view>
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
.dp-collage-item .product-pic .desc{ display: flex; width:100%;  flex-wrap: wrap; position: absolute; bottom: 0; background: #FF3143; opacity: 0.7; color:#fff;font-size: 20rpx;height:80rpx; padding:5rpx 10rpx}
.dp-collage-item .product-pic .desc text{ width: 100%;}


.dp-collage-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-collage-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;padding:20rpx;border-radius:10rpx}
.dp-collage-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-collage-itemlist .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-collage-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-collage-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;position: relative;}
.dp-collage-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:60rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.dp-collage-itemlist .product-info .p2{margin-top:20rpx;height:56rpx;line-height:56rpx;overflow:hidden;}
.dp-collage-itemlist .product-info .p2 .t1{font-size:36rpx;}
.dp-collage-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-collage-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-collage-itemlist .product-info .p3-1{height:40rpx;line-height:40rpx;border:1px #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-collage-itemlist .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}
.dp-collage-itemlist .product-info .p1 .team_text{ margin-right:10rpx;border-radius: 4rpx; color: #FF3143; background:#FFDED9 ; font-size: 20rpx; padding: 8rpx 6rpx;}

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
.dp-collage-itemline .product-info .p3-1{height:40rpx;line-height:40rpx;border:1px #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-collage-itemline .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}
.dp-collage-itemlist  .item .desc{ display: flex; margin-top: 15rpx;background:linear-gradient(to right, #FF3143, #FF8F99); justify-content: space-between;  padding:0 10rpx; color: #fff;border-radius: 6rpx; line-height: 60rpx; font-size: 20rpx;}
::-webkit-scrollbar{width: 0;height: 0;color: transparent;}
</style>
</style>