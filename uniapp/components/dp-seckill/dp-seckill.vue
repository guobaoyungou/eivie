<template>
<view class="dp-seckill" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+params.padding_x*2.2+'rpx'
}">
    <view v-if="params.shopstyle=='2'">
    	<view v-if="params.showtitle=='1'">
			<view v-if="params.titlestyle==1" class="dp-time flex-y-center">
				<image mode="widthFix" class="dp-time-back" :src="pre_url+'/static/imgsrc/decoration_crush.png'" alt=""/>
				<view class="dp-time-module flex flex-bt flex-y-center">
					<text class="dp-time-title">限时秒杀</text>
					<view class="dp-time-content">
						<text v-if="data[0].seckill_status == 0">距开抢</text>
						<text v-if="data[0].seckill_status == 1">还剩余</text>
						<text v-if="data[0].seckill_status == 2">活动已结束</text>
						<uni-countdown v-if="data[0].seckill_status != 2" :show-day="false" color="#fd4a46" background-color="#fff" :hour="data[0].hour" :minute="data[0].minute" :second="data[0].second" splitorColor="#fff"></uni-countdown>
					</view>
				</view>
			</view>
			<view v-if="params.titlestyle==2" class="dp-bTime flex-y-center">
				<image mode="widthFix" class="dp-bTime-back" :src="pre_url+'/static/imgsrc/decoration_crush.png'" alt=""/>
				<view class="dp-bTime-module flex flex-bt flex-y-center">
					<text class="dp-bTime-title">限时秒杀</text>
					<view class="dp-bTime-content">
						<text v-if="data[0].seckill_status == 0">距开抢</text>
						<text v-if="data[0].seckill_status == 1">还剩余</text>
						<text v-if="data[0].seckill_status == 2">活动已结束</text>
						<uni-countdown v-if="data[0].seckill_status != 2" :show-day="false" color="#fff" background-color="#000" :hour="data[0].hour" :minute="data[0].minute" :second="data[0].second" splitorColor="#999ca7"></uni-countdown>
					</view>
				</view>
			</view>
		</view>
    </view>
	
	<view v-if="!params.shopstyle||params.shopstyle==1">
		<view class="dp-seckill-item" v-if="params.style=='1' || params.style=='2' || params.style=='3'">
			<view class="item" v-for="(item,index) in data" :style="params.style==2 ? 'width:49%;margin-right:'+(index%2==0?'2%':0) : (params.style==3 ? 'width:32%;margin-right:'+(index%3!=2?'2%':0) :'width:100%')" :key="item.id" @click="goto" :data-url="'/activity/seckill/product?id='+item.proid">
				<view class="product-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
					<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
					<view class="p2">
						<view class="p2-1" v-if="params.showprice != '0'">
							<text class="t1" :style="{color:t('color1')}">
								<block v-if="item.usd_sellprice">
									<text style="font-size:24rpx">${{item.usd_sellprice}}</text>
									<text style="font-size: 28rpx;"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
								</block>
								<block v-else>
									<text style="font-size:24rpx">￥</text>{{item.sell_price}}
								</block>
							</text>
							<text class="t2" v-if="params.showprice == '1'">￥{{item.market_price}}</text>
						</view>
					</view>
					<view v-if="params.showtime == 1 && params.style!='3'" style="color:#333;font-size:24rpx">
						<view v-if="item.seckill_status == 2">活动已结束</view>
						<view v-if="item.seckill_status == 1" class="flex-row"><view class="h24">还剩余</view><view class="flex1"></view><uni-countdown :show-day="false" color="#FFFFFF" background-color="#fd4a46" :hour="item.hour" :minute="item.minute" :second="item.second" splitorColor="#333"></uni-countdown></view>
						<view v-if="item.seckill_status == 0" class="flex-row"><view class="h24">距开抢</view><view class="flex1"></view><uni-countdown :show-day="false" color="#FFFFFF" background-color="#fd4a46" :hour="item.hour" :minute="item.minute" :second="item.second" splitorColor="#333"></uni-countdown></view>
					</view>
					
					<view class="p3">
						<view class="p3-1" :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}">秒杀</view>
						<view class="p3-2" v-if="params.showsales=='1' && item.sales>0"><text style="overflow:hidden">已抢购{{item.sales}}件</text></view>
					</view>
				</view>
			</view>
		</view>
		<view class="dp-seckill-itemlist" v-if="params.style=='list'">
			<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/activity/seckill/product?id='+item.proid">
				<view class="product-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
					<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
					<view class="p2" v-if="params.showprice != '0'">
						<text class="t1" :style="{color:t('color1')}">
							<block v-if="item.usd_sellprice">
								<text style="font-size:24rpx">$</text>{{item.usd_sellprice}}
								<text style="font-size: 28rpx;"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
							</block>
							<block v-else>
								<text style="font-size:24rpx">￥</text>{{item.sell_price}}
							</block>
						</text>
						<text class="t2" v-if="params.showprice == '1'">￥{{item.market_price}}</text>
					</view>
					<view v-if="params.showtime == 1" style="color:#333;font-size:24rpx">
						<view v-if="item.seckill_status == 2">活动已结束</view>
						<view v-if="item.seckill_status == 1" class="flex-row"><view class="h24">距活动结束</view><view class="flex1"></view>
							<uni-countdown v-if="item.day > 0" :show-day="true" color="#FFFFFF" background-color="#fd4a46" :day="item.day" :hour="item.day_hour" :minute="item.minute" :second="item.second" splitorColor="#333"></uni-countdown>
							<uni-countdown v-else :show-day="false" color="#FFFFFF" background-color="#fd4a46" :day="item.day" :hour="item.hour" :minute="item.minute" :second="item.second"></uni-countdown>
						</view>
						<view v-if="item.seckill_status == 0" class="flex-row"><view class="h24">距活动开始</view><view class="flex1"></view>
							<uni-countdown v-if="item.day > 0" :show-day="true" color="#FFFFFF" background-color="#fd4a46" :day="item.day" :hour="item.day_hour" :minute="item.minute" :second="item.second" splitorColor="#333"></uni-countdown>
							<uni-countdown v-else :show-day="false" color="#FFFFFF" background-color="#fd4a46" :day="item.day" :hour="item.hour" :minute="item.minute" :second="item.second"></uni-countdown>
						</view>
					</view>
					<view class="p3">
						<view class="p3-1" :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}">秒杀</view>
						<view class="p3-2" v-if="params.showsales=='1' && item.sales>0"><text style="overflow:hidden">已抢购{{item.sales}}件</text></view>
					</view>
				</view>
			</view>
		</view>
		<view class="dp-seckill-itemline" v-if="params.style=='line'">
			<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/activity/seckill/product?id='+item.proid">
				<view class="product-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
					<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
					<view class="p2">
						<view class="p2-1" v-if="params.showprice != '0'">
							<text class="t1" :style="{color:t('color1')}">
								<block v-if="item.usd_sellprice">
									<text style="font-size:24rpx">$</text>{{item.usd_sellprice}}
									<text style="font-size: 28rpx;"><text style="font-size:24rpx">￥</text>{{item.sell_price}}</text>
								</block>
								<block v-else>
									<text style="font-size:24rpx">￥</text>{{item.sell_price}}
								</block>
							</text>
							<text class="t2" v-if="params.showprice == '1'">￥{{item.market_price}}</text>
						</view>
					</view>
					<view class="p3">
						<view class="p3-1" :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}">秒杀</view>
						<view class="p3-2" v-if="params.showsales=='1' && item.sales>0"><text style="overflow:hidden">已抢购{{item.sales}}件</text></view>
					</view>
				</view>
			</view>
		</view>
	</view>
	
	<view v-if="params.shopstyle==2">
		<view class="dp-seckill-item" style="overflow: visible;" v-if="params.style=='2'">
			<view class="item" style="overflow: visible;" v-for="(item,index) in data" :style="params.style==2 ? 'width:49%;margin-right:'+(index%2==0?'2%':0) : (params.style==3 ? 'width:32%;margin-right:'+(index%3!=2?'2%':0) :'width:100%')" :key="item.id" @click="goto" :data-url="'/activity/seckill/product?id='+item.proid">
				<view class="product-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
					<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
					<view class="rate flex-y-center flex-bt">
						<view class="rate_module">
							<view :style="{width:(item.sales/item.stock)*100 + '%'}" class="rate_item">
								<image :src="pre_url+'/static/imgsrc/decoration_tag.png'"></image>
							</view>
						</view>
						<text>仅剩{{item.stock - item.sales}}件</text>
					</view>
					<view class="cost">
						原价：<text>￥{{item.market_price}}</text>
					</view>
					<view class="price flex-y-center flex-bt">
						<block v-if="item.usd_sellprice">
							<text :style="{color:t('color1')}">${{item.usd_sellprice}} ￥{{item.sell_price}}</text>
						</block>
						<block v-else>
							<text :style="{color:t('color1')}">￥{{item.sell_price}}</text>
						</block>
						<view :style="{background:'rgba('+t('color1rgb')+',1)'}" class="flex-xy-center">
							<image :src="pre_url+'/static/imgsrc/decoration_add.png'"></image>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="dp-seckill-itemlist1" v-if="params.style=='list'">
			<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/activity/seckill/product?id='+item.proid">
				<view class="product-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
					<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
				</view>
				<view class="product-info">
					<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
					<!-- <view class="text">平价实用</view> -->
					<view class="rate flex-y-center flex-bt">
						<view class="rate_module">
							<view :style="{width:(item.sales/item.stock)*100 + '%'}" class="rate_item">
								<image :src="pre_url+'/static/imgsrc/decoration_tag.png'"></image>
							</view>
						</view>
						<text>仅剩{{item.stock - item.sales}}件</text>
					</view>
					<view class="cost">
						原价：<text>￥{{item.market_price}}</text>
					</view>
					<view class="price flex-y-center flex-bt">
						<block v-if="item.usd_sellprice">
							<text :style="{color:t('color1')}">${{item.usd_sellprice}} ￥{{item.sell_price}}</text>
						</block>
						<block v-else>
							<text :style="{color:t('color1')}">￥{{item.sell_price}}</text>
						</block>
						<view :style="{background:'rgba('+t('color1rgb')+',1)'}" class="flex-xy-center">马上抢</view>
					</view>
				</view>
			</view>
		</view>
		<view class="dp-seckill-itemline" v-if="params.style=='line'">
			<view class="item" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/activity/seckill/product?id='+item.proid">
				<view class="product-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
					<image class="saleimg" :src="params.saleimg" v-if="params.saleimg!=''" mode="widthFix"/>
					<view :style="{background:'rgba('+t('color1rgb')+',1)'}" class="tag">秒杀</view>
				</view>
				<view class="product-info">
					<view class="p1" v-if="params.showname == 1">{{item.name}}</view>
					<view class="flex">
						<view :style="{background:'rgba('+t('color1rgb')+',0.12)',color:t('color1')}" class="tag">剩余{{item.stock - item.sales}}件</view>
					</view>
					<view class="price flex-y-center flex-bt">
						<text :style="{color:t('color1')}">￥{{item.sell_price}}</text>
						<view :style="{background:'rgba('+t('color1rgb')+',1)'}" class="flex-xy-center">
							<image :src="pre_url+'/static/imgsrc/decoration_add.png'"></image>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</view>
</template>
<script>
	export default {
		data(){
			return {
				pre_url:getApp().globalData.pre_url
			}
		},
		props: {
			params:{},
			data:{}
		}
	}
</script>
<style>
.dp-time{position: relative;border-radius: 10rpx;overflow: hidden;margin-bottom: 20rpx;}
.dp-time-back{width: 100%;overflow: hidden;}
.dp-time-module{position: absolute;height: 100%;width: 100%;top: 0;left: 0;padding: 0 20rpx;}
.dp-time-title{font-size: 33rpx;color: rgb(255, 255, 255);font-style: italic;font-weight: bold;}
.dp-time-content{display: flex;align-items: center;font-size: 22rpx;color: #fff;}
.dp-time-tag{background: #fff;border-radius: 4rpx;color: #fd463e;text-align: center;line-height: 36rpx;margin: 0 5rpx;padding: 0 5rpx;}

.dp-bTime{position: relative;border-radius: 10rpx;overflow: hidden;margin-bottom: 20rpx;}
.dp-bTime-back{width: 100%;overflow: hidden;opacity: 0;}
.dp-bTime-module{position: absolute;height: 100%;width: 100%;top: 0;left: 0;padding: 0 20rpx;}
.dp-bTime-title{font-size: 33rpx;color: #000;font-weight: bold;}
.dp-bTime-content{display: flex;align-items: center;font-size: 22rpx;color: rgb(153, 156, 167);}
.dp-bTime-tag{background: rgb(55, 56, 58);border-radius: 4rpx;color: #fff;text-align: center;line-height: 36rpx;margin: 0 5rpx;padding: 0 5rpx;}

.dp-seckill{height: auto; position: relative;overflow: hidden; padding: 0px; background: #fff;}
.dp-seckill-item{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-seckill-item .item{display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;overflow:hidden}
.dp-seckill-item .product-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-seckill-item .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-seckill-item .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-seckill-item .product-info {padding:20rpx 10rpx;position: relative;}
.dp-seckill-item .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-seckill-item .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-seckill-item .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-seckill-item .product-info .p2-1 .t1{font-size:36rpx;}
.dp-seckill-item .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-seckill-item .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-seckill-item .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-seckill-item .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-seckill-item .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}
.dp-seckill-item .product-info .h24 {height: 48rpx; line-height: 48rpx;padding: 2rpx 0; margin: 4rpx 0;}

.dp-seckill-item .product-info .rate{font-size:24rpx;color: #FF3143;}
.dp-seckill-item .product-info .rate_module{width: 190rpx;height: 15rpx;border-radius: 500rpx;background: #ffc1c1;}
.dp-seckill-item .product-info .rate_item{height: 15rpx;background: #ff065e;border-radius: 500rpx;position: relative;}
.dp-seckill-item .product-info .rate_item image{position: absolute;top: 0;bottom: 0;right: -15rpx;margin: auto 0;height: 30rpx;width: 30rpx;}
.dp-seckill-item .product-info .cost{font-size:24rpx;color: #999ca7;margin-top: 10rpx;}
.dp-seckill-item .product-info .cost text{text-decoration: line-through;}
.dp-seckill-item .product-info .price{font-weight: bold;color: #fd463e;position: relative;margin-top: 20rpx;font-size: 32rpx;}
.dp-seckill-item .product-info .price view{height: 50rpx;width: 50rpx;background: #fd463e;border-radius: 100rpx;}
.dp-seckill-item .product-info .price view image{height: 30rpx;width: 30rpx;display: block;}

.dp-seckill-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-seckill-itemlist .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:20rpx;border-radius:10rpx}
.dp-seckill-itemlist .product-pic {width: 30%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;border-radius:4px;}
.dp-seckill-itemlist .product-pic .image{width: 100%;height:auto}
.dp-seckill-itemlist .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-seckill-itemlist .product-info {width: 70%;padding:6rpx 0rpx 5rpx 20rpx;position: relative;}
.dp-seckill-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-seckill-itemlist .product-info .p2 {height:56rpx;line-height:56rpx;overflow:hidden;}
.dp-seckill-itemlist .product-info .p2 .t1{font-size:36rpx;}
.dp-seckill-itemlist .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-seckill-itemlist .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-seckill-itemlist .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-seckill-itemlist .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}
.dp-seckill-itemlist .product-info .h24 {height: 48rpx; line-height: 48rpx;padding: 2rpx 0; margin: 4rpx 0;}

.dp-seckill-itemlist1{height: auto; position: relative;overflow: hidden; padding: 0px; display:flex;flex-wrap:wrap}
.dp-seckill-itemlist1 .item{width:100%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;display:flex;padding:20rpx;border-radius:10rpx}
.dp-seckill-itemlist1 .product-pic {width: 35%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 33%;position: relative;border-radius:4px;}
.dp-seckill-itemlist1 .product-pic .image{width: 100%;height:auto}
.dp-seckill-itemlist1 .product-pic .saleimg{ position: absolute;width: 120rpx;height: auto; top: -6rpx; left:-6rpx;}
.dp-seckill-itemlist1 .product-info {width: 70%;padding:6rpx 0rpx 5rpx 20rpx;position: relative;}
.dp-seckill-itemlist1 .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-seckill-itemlist1 .product-info .p2 {height:56rpx;line-height:56rpx;overflow:hidden;}
.dp-seckill-itemlist1 .product-info .p2 .t1{font-size:36rpx;}
.dp-seckill-itemlist1 .product-info .p2 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-seckill-itemlist1 .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-seckill-itemlist1 .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-seckill-itemlist1 .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}
.dp-seckill-itemlist1 .product-info .h24 {height: 48rpx; line-height: 48rpx;padding: 2rpx 0; margin: 4rpx 0;}
.dp-seckill-itemlist1 .product-info .rate{font-size:24rpx;color: #FF3143;margin-top: 15rpx;}
.dp-seckill-itemlist1 .product-info .rate_module{width: 200rpx;height: 15rpx;border-radius: 500rpx;background: #ffc1c1;}
.dp-seckill-itemlist1 .product-info .rate_item{height: 15rpx;background: #ff065e;border-radius: 500rpx;position: relative;}
.dp-seckill-itemlist1 .product-info .rate_item image{position: absolute;top: 0;bottom: 0;right: -15rpx;margin: auto 0;height: 30rpx;width: 30rpx;}
.dp-seckill-itemlist1 .product-info .text{font-size:24rpx;color: #999ca7;margin-top: 10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.dp-seckill-itemlist1 .product-info .cost{font-size:24rpx;color: #999ca7;margin-top: 10rpx;}
.dp-seckill-itemlist1 .product-info .cost text{text-decoration: line-through;}
.dp-seckill-itemlist1 .product-info .price{font-weight: bold;color: #fd463e;position: relative;margin-top: 15rpx;font-size: 32rpx;}
.dp-seckill-itemlist1 .product-info .price view{position: absolute;right: 0;bottom: 0;background: rgb(253, 70, 62);color: rgb(255, 255, 255);line-height: 60rpx;border-radius: 100rpx;font-size: 26rpx;font-weight: 700;width: 140rpx;}

.dp-seckill-itemline{width:100%;display:flex;overflow-x:scroll;overflow-y:hidden}
.dp-seckill-itemline .item{width: 220rpx;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:10rpx;margin-right:4px}
.dp-seckill-itemline .product-pic {width:220rpx;height:0;overflow:hidden;background: #ffffff;padding-bottom: 100%;position: relative;}
.dp-seckill-itemline .product-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-seckill-itemline .product-pic .saleimg{ position: absolute;width: 60px;height: auto; top: -3px; left:-3px;}
.dp-seckill-itemline .product-pic .tag{padding: 0 15rpx;line-height: 35rpx;display: inline-block;font-size: 24rpx;color: #fff;background: linear-gradient(to bottom right,#ff88c0,#ec3eda);position: absolute;left: 0;bottom: 0;border-radius: 0 10rpx 0 0}
.dp-seckill-itemline .product-info {padding:20rpx 20rpx;position: relative;}
.dp-seckill-itemline .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;height:72rpx}
.dp-seckill-itemline .product-info .p2{display:flex;align-items:center;overflow:hidden;padding:2px 0}
.dp-seckill-itemline .product-info .p2-1{flex-grow:1;flex-shrink:1;height:40rpx;line-height:40rpx;overflow:hidden;white-space: nowrap}
.dp-seckill-itemline .product-info .p2-1 .t1{font-size:36rpx;}
.dp-seckill-itemline .product-info .p2-1 .t2 {margin-left:10rpx;font-size:24rpx;color: #aaa;text-decoration: line-through;/*letter-spacing:-1px*/}
.dp-seckill-itemline .product-info .p2-2{font-size:20rpx;height:40rpx;line-height:40rpx;text-align:right;padding-left:20rpx;color:#999}
.dp-seckill-itemline .product-info .p3{display:flex;align-items:center;overflow:hidden;margin-top:10rpx;justify-content:space-between}
.dp-seckill-itemline .product-info .p3-1{height:40rpx;line-height:40rpx;border:0 #FF3143 solid;border-radius:10rpx;color:#FF3143;padding:0 24rpx;font-size:24rpx}
.dp-seckill-itemline .product-info .p3-2{color:#999999;font-size:20rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;}
.dp-seckill-itemline .product-info .tag{padding: 2rpx 8rpx;background: #ffe7e7;color: #FF3143;font-size: 24rpx;}
.dp-seckill-itemline .product-info .price{font-weight: bold;color: #fd463e;position: relative;margin-top: 10rpx;font-size: 27rpx;}
.dp-seckill-itemline .product-info .price view{height: 45rpx;width: 45rpx;background: #fd463e;border-radius: 100rpx;}
.dp-seckill-itemline .product-info .price view image{height: 25rpx;width: 25rpx;display: block;}
</style>