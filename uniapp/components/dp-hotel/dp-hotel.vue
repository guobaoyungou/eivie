<template>
<view class="dp-kanjia" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+params.margin_x*2.2+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+params.padding_x*2.2+'rpx'
}">
	<view class="dp-hotel-item">
		<view class="hotels-list">
			<block v-for="(item,index) in data" >
				<view class="hotels-options" @tap="goto" :data-url="'/hotel/index/hoteldetails?id='+item.hotelid">
					<view class="hotel-img">
						<image :src="item.pic"></image>
					</view>
					<view class="hotel-info">
						<view class="hotel-title"  v-if="params.showname==1">{{item.name}}</view>
						<view class="hotel-address">{{item.address}}</view>
						<view class="hotel-characteristic" v-if="params.showtag==1 && item.tag.length>0">
							<block v-for="(items,indexs) in item.tag">
								<view class="characteristic-options" :style="'background:rgba('+t('color1rgb')+',0.05);color:'+t('color1')">{{items}}</view>
							</block>
						</view>
						<view class="hotel-but-view">
							<view class="make-info">
                <block v-if="params.showprice==1">
                  <view class="hotel-price" :style="{color:t('color1')}" v-if="item.min_daymoney">
                    <view class="hotel-price-num">{{item.min_daymoney}}{{t('余额单位')}}</view>
                    <view>/晚起</view>
                  </view>
                  <view class="hotel-price" :style="{color:t('color1')}" v-else>
                    <view>￥</view>
                    <view class="hotel-price-num">{{item.sell_price}}</view>
                    <view>起</view>
                  </view>
                </block>
								<view v-if="params.showsales==1" class="hotel-text">{{item.sales}}人预定</view>
							</view>
							<view class="hotel-make"  :style="'background:rgba('+t('color1rgb')+',0.8);color:#FFF'">预约</view>
						</view>
					</view>
				</view>
			</block>
		</view>
	</view>
</view>
</template>
<script>
	var app = getApp();
	export default {
		props: {
			params:{},
			data:{}
		},
		methods: {
		
		}
	}
</script>
<style>
	/*  */
	.hotels-list{width: 96%;margin: 20rpx auto 0rpx;display: flex;align-items: center;justify-content: space-between;flex-direction:column;}
	.hotels-list .hotels-options{width: 100%;padding: 20rpx;display: flex;align-items: center;justify-content: space-between;border-radius: 8px;background: #FFFFFF;margin-bottom: 20rpx;}
	.hotels-list .hotels-options .hotel-img{width: 98px;height: 130px;border-radius: 15rpx;overflow: hidden;}
	.hotels-list .hotels-options .hotel-img image{width: 100%;height: 100%;}
	.hotels-list .hotels-options .hotel-info{flex: 1;padding-left: 20rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-title{width: 100%;color: #343536;font-size: 30rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-address{width: 100%;color: #7B8085;font-size: 24rpx;margin-top: 7rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-characteristic{width: 100%;display: flex; flex-wrap: wrap; align-items: center;justify-content: flex-start;margin-top: 7rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-characteristic .characteristic-options{font-size: 20rpx;padding: 7rpx 13rpx;flex-wrap: wrap;margin-right: 20rpx; margin-top: 6rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view{width: 100%;display: flex;align-items: center;justify-content: space-between;margin-top: 25rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view .make-info{display: flex;flex-direction: column;justify-content: flex-start;}
	.hotels-options .hotel-info .hotel-but-view .make-info .hotel-price{display: flex;align-items: center;justify-content: flex-start;font-size: 24rpx;}
	.hotel-info .hotel-but-view .make-info .hotel-price .hotel-price-num{font-size: 40rpx;font-weight: bold;padding: 0rpx 3rpx;}
	.hotels-options .hotel-info .hotel-but-view .make-info .hotel-text{color: #7B8085;font-size: 24rpx;margin-top: 15rpx;}
	.hotels-list .hotels-options .hotel-info .hotel-but-view .hotel-make{background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%);width: 72px;height: 32px;line-height: 32px;
	text-align: center;border-radius: 36px;color: #FFFFFF;font-size: 28rpx;font-weight: bold;}
	
</style>