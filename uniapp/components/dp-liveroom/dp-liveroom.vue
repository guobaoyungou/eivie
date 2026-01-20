<template>
<view class="dp-liveroom" :style="{
	color:params.color,
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'
}">
	<view class="dp-liveroom-liveroom">
		<view class="item" v-for="(item,index) in data" :key="item.id" :style="{marginRight:index%2==0?'2%':'0'}" @click="goto" :data-url="'plugin-private://wx2b03c6e691cd7370/pages/live-player-plugin?room_id='+item.roomId">
			<view class="bgpic">
				<image class="image" :src="item.coverImg" mode="widthFix"/>
			</view>
			<view class="f1" v-if="item.status=='0'"><view class="t1">预告</view><view class="t2">{{item.showtime}}</view></view>
			<view class="f2" v-if="item.status=='1'"><image :src="pre_url+'/static/img/liveing.gif'" class="t1"/><view class="t2">直播中</view></view>
			<view class="f3" v-if="item.status=='2'"><view class="t1">已结束</view></view>
			<view class="f10">
				 <view class="title">{{item.name}}</view>
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
.dp-liveroom{height: auto;position: relative; }
.dp-liveroom-liveroom{height: auto; overflow: hidden; padding: 0px;display:flex;flex-wrap:wrap}
.dp-liveroom-liveroom .item{width: 49%;display: inline-block;position: relative;margin-bottom:16rpx;background: #fff;border-radius:16rpx;overflow:hidden}
.dp-liveroom-liveroom .item .bgpic {width:100%;height:0;overflow:hidden;background: #fff;padding-bottom:140%;position: relative;}
.dp-liveroom-liveroom .item .bgpic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-liveroom-liveroom .item .f1{display:flex;align-items:center;position:absolute;top:20rpx;left:20rpx;z-index:9;background:rgba(0,0,0,0.3);color:#ffffff;height:16px;line-height:32rpx;border-radius:16rpx;padding:0 20rpx;font-size:20rpx;padding-left:0;overflow:hidden}
.dp-liveroom-liveroom .item .f1 .t1{background:#FFDC30;color:#050505;padding-left:20rpx;padding-right:10rpx}
.dp-liveroom-liveroom .item .f1 .t2{padding-left:10rpx}
.dp-liveroom-liveroom .item .f2{display:flex;align-items:center;position:absolute;top:20rpx;left:20rpx;z-index:9;background:rgba(0,0,0,0.3);color:#ffffff;height:16px;line-height:32rpx;border-radius:16rpx;padding:0 20rpx;font-size:20rpx}
.dp-liveroom-liveroom .item .f2 .t1{width:20rpx;height:20rpx}
.dp-liveroom-liveroom .item .f2 .t2{padding-left:2px}
.dp-liveroom-liveroom .item .f3{display:flex;align-items:center;position:absolute;top:20rpx;left:20rpx;z-index:9;background:rgba(0,0,0,0.3);color:#ffffff;height:16px;line-height:16px;border-radius:16rpx;padding:0 20rpx;font-size:20rpx}
.dp-liveroom-liveroom .item .f10{position:absolute;bottom:40rpx;left:10%;width:80%;z-index:9;background:rgba(0,0,0,0.3);padding:0 20rpx;color:#ffffff;height:40rpx;line-height:40rpx;overflow:hidden;text-align:center;border-radius:20rpx;font-size:24rpx}
</style>