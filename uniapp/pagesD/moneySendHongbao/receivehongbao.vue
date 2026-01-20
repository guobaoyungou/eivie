<template>
<view>
	<block v-if="isload">
		<view class="bg-view">
			<image :src="data.pic" mode='center'></image>
		</view>
		<view class="content-view"> 
			<view class="info-view flex flex-y-center">
				<view class="head-img">
					<image :src="data.headimg"></image>
				</view>
				<view class="name-view">
					{{data.nickname}} 发出的红包
				</view>
			</view>
			<view class="price-view flex flex-y-center">
				<view>{{data.money}}</view>
				<view style="font-size: 30rpx;margin-left: 10rpx;margin-top: 10rpx;">元</view>
			</view>
			<view class="tisp-text flex flex-y-center" @tap="torecord">
				<view>领取成功，红包已存入{{data.receive_account==1?'佣金':t('余额')}}</view>
				<view class="icon-class">
					<image :src="pre_url+'/static/img/redenvelope/jiantou.png'" @tap="torecord"></image>
				</view>
			</view>
			
		</view>
		<view class="hb-log" @tap="goto" :data-url="'hongbaolog?type=0'">红包记录</view>
	</block>
	<loading v-if="loading"></loading>
</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				isload: false,
				loading:false,
				pre_url: app.globalData.pre_url,
				id:0,
				data:[]
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			if(this.opt.id){
				this.id = this.opt.id
			}
			this.getdata();
		},
		methods:{
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiMoneySendHongbao/getLogDetail', {id:that.id}, function (res) {
					uni.setNavigationBarTitle({
						title: '发红包领取'
					});
					that.loading = false;
					that.data = res.data;
					that.loaded();
				});
			},
			torecord(){
				var that = this;
				if(that.data.receive_account==1){//佣金
					app.goto('/activity/commission/commissionlog')
				}else{
					app.goto('/pagesExt/money/moneylog?st=1')
				}
			}
		}
	}
</script>

<style>
	.bg-view{width: 100%;height: 40vh;border-radius:0rpx 0rpx 120rpx 120rpx;overflow: hidden;}
	.bg-view image{width: 100%;height: 100%;}
	.content-view{width: 94%;border-radius: 16rpx;background: #fff;position: relative;top:-110rpx;left: 50%;transform: translateX(-50%);box-shadow: 0rpx 0rpx 10rpx 0rpx rgba(0,0,0,.3);padding: 60rpx 0rpx;}
	.content-view .info-view{width: 100%;justify-content: center;margin-bottom: 20rpx;}
	.content-view .info-view .head-img{width: 58rpx;height: 58rpx;border-radius: 10rpx;overflow: hidden;}
	.content-view .info-view .head-img image{width: 100%;height: 100%;}
	.content-view .info-view .name-view{font-size: 34rpx;font-weight: bold;color: #000;padding-left: 20rpx;}
	.content-view .price-view{justify-content: center;font-size: 80rpx;font-weight: bold;color: #e4c130;margin-top: 20rpx;}
	.tisp-text{text-align: center;font-size: 26rpx;color: #e4c130;justify-content: center;}
	.icon-class{width: 34rpx;height: 34rpx;}
	.icon-class image{width: 100%;height: 100%;}
	.hb-log{font-size: 28rpx;color: #1e67c1;position: absolute;left: 50%;transform: translateX(-50%);bottom: calc(30rpx + env(safe-area-inset-bottom));}

</style>