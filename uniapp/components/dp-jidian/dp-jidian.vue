<template>
	<view class="dp-jidian" :style="{backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
	borderRadius:params.borderradius+'px'}">
		<view class="f1 flex-y-center" :style="{backgroundImage:'url('+pre_url+'/static/img/dzp/jiangpin.png)'}"></view>
		<view class="f2" @tap="goto" :data-url="'/activity/yx/jidian?bid='+params.bid">
			<view class="text-big">{{data.name}}</view>
			<view>再下<text>{{data.reward_num-data.have_num}}单</text>得<text>{{data.reward_name}}</text>优惠券</view>
		</view>
		<view class="f3">
			<block v-for="(item,index) in data.have_num">
				<view class="circle circleCheck"><view class="gou"></view></view>
			</block>
			<block v-for="(item,index) in data.total_num-data.have_num">
				<view class="circle"></view>
			</block>
		</view>
	</view>
</template>

<script>
	var app =getApp();
	export default {
		name:"dp-jidian",
		data() {
			return {
				pre_url:getApp().globalData.pre_url
				
			};
		},
		props: {
			params:{},
			data:{},
		},
		methods:{
		}
	}
</script>

<style>
.dp-jidian{padding:10rpx 0;height: auto; position: relative; display: flex;align-items: center;}
.dp-jidian .f1{height:48px;width:36px;color: #666;border: 0px;padding: 0px;margin: 0px;background-position: center;background-repeat: no-repeat;background-size:20px;}
.dp-jidian .f2{height:48px;flex:1; color: #666;}
.dp-jidian .f3{width:35%; text-align: right; padding-right: 10rpx;}
.dp-jidian .text-big {font-size: 16px; color: #333;}
.dp-jidian .circle { border: 1px solid #D4D5D0; border-radius: 50%; width: 15px;height: 15px;margin: 0 4rpx; display: inline-block; background: #fff;}
.dp-jidian .circleCheck { border:none; border-radius: 50%; background: #F9806D; position: relative;overflow: hidden;}
.dp-jidian .circleCheck .gou {
    width: 12px;
    height: 6px;
    display: inline-block;
    border: 1px solid #ffffff;
    border-width: 0 0 2px 2px;
    transform: rotate(-45deg);
    -ms-transform: rotate(-45deg);
    -moz-transform: rotate(-45deg);
    -webkit-transform: rotate(-45deg);
    -o-transform: rotate(-45deg);
    vertical-align: baseline;
    position: absolute;
    right: 0;
    top: 2px;
}
</style>
