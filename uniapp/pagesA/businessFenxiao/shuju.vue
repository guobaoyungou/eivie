<template>
	<view v-if="isload">
		<view class="bg-view"></view>
		<view class="content-view flex-col">
			<view class="info-view">
				<view class="data-num-view flex-xy-center">
					<view class="options-data flex-col">
						<view class="num">{{bonus_day}}</view>
						<view class="text">昨日收益</view>
					</view>
					<view class="options-data flex-col">
						<view class="num">{{bonus_month}}</view>
						<view class="text">本月收益</view>
					</view>
					<view class="options-data flex-col">
						<view class="num">{{bonus_total}}</view>
						<view class="text">累计收益</view>
					</view>
				</view>
				<view class="sort-option-view flex-bt" style="margin-top: 50rpx;" @click="goto" :data-url="'/pagesA/businessFenxiao/mendianlist?type=0'">
					<view class="title-text">我的门店</view>
					<view class="right-text flex-row">
						<image :src="pre_url+'/static/img/left_jiantou.png'"></image>
					</view>
				</view>
				<view class="sort-option-view flex-bt" @click="goto" :data-url="'/pagesA/businessFenxiao/mendianlist?type=1'">
					<view class="title-text">我邀请的门店</view>
					<view class="right-text flex-row">
						<image :src="pre_url+'/static/img/left_jiantou.png'"></image>
					</view>
				</view>
			</view>
		</view>
		<loading v-if="loading"></loading>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				pre_url:app.globalData.pre_url,
				loading: false,
				isload: false,
				bonus_day:'0.00',
				bonus_month:'0.00',
				bonus_total:'0.00',
			}
		},
		onLoad: function(opt) {
			var that = this;
			var opt = app.getopts(opt);
			that.opt = opt;
			that.id = opt.id || 0;
			console.log(opt)
			this.getdata();
		},
		methods:{
			getdata:function(){
				var that = this;
				that.isload = true;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.post('ApiBusinessFenxiao/shouyi', {id: that.id}, function (res) {
					that.loading = false;
					if(res.status==1){
						that.bonus_day = res.data.bonus_day;
						that.bonus_month = res.data.bonus_month;
						that.bonus_total = res.data.bonus_total;
					}else{
						uni.showToast({
							title: res.msg,
							icon:'none',
							duration: 2000
						});
					}
					
				})
			}
		}
	}
</script>

<style>
	.bg-view{width: 150%;background: #eb8c2b;height: 500rpx;border-radius: 50%;position: relative;top:-260rpx;left: 50%;transform: translateX(-50%);z-index: 1;}
	.content-view{width: 90%;height: auto;position: absolute;top:30rpx;z-index:2;left: 50%;transform: translateX(-50%);}
	.info-view{width: 100%;height: auto;position: absolute;top:0;left: 50%;transform: translateX(-50%);z-index: 2;}
	.data-num-view{width: 100%;background: #fff;padding: 50rpx 0rpx;border-radius: 16rpx;margin: 40rpx 0rpx 20rpx;}
	.data-num-view .options-data{align-items: center;width: 32%;}
	.data-num-view .options-data .num{color: #3A4463;font-size: 26rpx;font-weight: bold;}
	.data-num-view .options-data .text{color: rgba(58, 68, 99, 0.55);font-size: 20rpx;margin-top: 30rpx;}
	.data-num-view .options-data:nth-child(2){border-left: 1px rgba(58, 68, 99, 0.1) solid;border-right: 1px rgba(58, 68, 99, 0.1) solid;}
	.sort-option-view{width: 100%;align-items: center;border-radius:12rpx;padding: 35rpx;margin-bottom: 20rpx;background: #fff;}
	.sort-option-view .title-text{color: #3A4463;font-size: 26rpx;font-weight: 500;}
	.sort-option-view .right-text{color: rgba(58, 68, 99, 0.4);font-size: 24rpx;}
	.sort-option-view .right-text image{width: 35rpx;height: 35rpx;margin-left: 30rpx;}
</style>