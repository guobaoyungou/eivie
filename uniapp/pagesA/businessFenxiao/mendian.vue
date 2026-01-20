<template>
	<view v-if="isload">
		<view class="bg-view"></view>
		<view class="content-view flex-col">
			<view class="info-view">
				<view class="data-num-view flex-aw">
					<view class="options-data flex-col">
						<view class="num">{{yeji_day_total}}</view>
						<view class="text">昨日营业额</view>
					</view>
					<view class="options-data flex-col">
						<view class="num">{{yeji_month_total}}</view>
						<view class="text">本月营业额</view>
					</view>
				</view>
			</view>
			<view class="sort-option-view flex-aw">
				<view class="sort-options flex-xy-center" @click="goto" :data-url="'/pagesA/businessFenxiao/bonuslog?bid='+bid">
					<view class="title-text">我的收入</view>
					<view class="classification-text">{{bonus_total}}</view>
				</view>
				<view class="sort-options flex-xy-center" @click="goto" :data-url="'/pagesA/businessFenxiao/mingxi?bid='+bid">
					<view class="title-text">销售明细</view>
					<view class="classification-text"></view>
				</view>
			</view>
			<view class="sort-option-view-new flex-bt" style="margin-top: 50rpx;" v-if="able_withdraw>0" @click="goto" :data-url="'/activity/commission/withdraw?bid='+bid">
				<view class="title-text">申请提现</view>
				<view class="right-text flex-row">
					可提现金额：{{able_withdraw}}<image :src="pre_url+'/static/img/left_jiantou.png'"></image>
				</view>
			</view>
			<view class="title-page">团队人员</view>
			<view class="option-personnel flex-bt">
				<view class="sort-options flex-xy-center" v-for="(item,index) in datalist" :key="index">
					<view class="img-view"><image :src="item.headimg"></image></view>
					<view class="title-text">{{item.nickname}}</view>
					<view class="classification-text">{{item.role_name}}</view>
				</view>
				
			</view>
		</view>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				pre_url:app.globalData.pre_url,
				datalist: [],
				bid:0,
				isload: false,
				yeji_day_total:0,
				yeji_month_total:0,
				datalist:[],
				bonus_total:0,
				able_withdraw:0
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.bid = this.opt.bid || 0;
			this.getdata();
		},
		methods:{
			getdata: function (loadmore) {
				var that = this;
				that.isload = true;
				var pagenum = that.pagenum;
				app.post('ApiBusinessFenxiao/mendiandetail', {bid: that.bid}, function (res) {
					var data = res.data;
					that.datalist = data.partner;
					that.yeji_day_total = data.yeji_day_total;
					that.yeji_month_total = data.yeji_month_total;
					that.bonus_total = data.bonus_total;
					that.able_withdraw = data.able_withdraw;
				});
			},
		}
	}
</script>

<style>
	.bg-view{width: 150%;background: #eb8c2b;height: 500rpx;border-radius: 50%;position: relative;top:-260rpx;left: 50%;transform: translateX(-50%);z-index: 1;}
	.content-view{width: 90%;height: auto;position: absolute;top:30rpx;z-index:2;left: 50%;transform: translateX(-50%);}
	.info-view{width: 100%;height: auto;}
	.data-num-view{width: 100%;background: #fff;padding: 50rpx 0rpx;border-radius: 16rpx;margin: 40rpx 0rpx 20rpx;}
	.data-num-view .options-data{align-items: center;width: 32%;}
	.data-num-view .options-data .num{color: #3A4463;font-size: 26rpx;font-weight: bold;}
	.data-num-view .options-data .text{color: rgba(58, 68, 99, 0.55);font-size: 20rpx;margin-top: 30rpx;}
	.sort-option-view{width: 100%;flex-wrap: wrap;}
	.sort-option-view .sort-options{width:270rpx;height: 150rpx;border-radius: 16rpx;background: linear-gradient(141deg, #FFFFFF 28%, #F7FFFA 100%);flex-direction: column;margin-top: 28rpx;}
	.sort-option-view .sort-options .title-text{color: #3A4463;font-size: 26rpx;font-weight: bold;}
	.sort-option-view .sort-options .classification-text{color: rgba(58, 68, 99, 0.55);font-size: 24rpx;margin-top: 10rpx;}
	.title-page{font-size: 28rpx;color: #333;font-weight: bold;padding: 35rpx 0rpx;}
	.option-personnel{width: 100%;flex-wrap: wrap;}
	.option-personnel .sort-options{width:30%;height: 250rpx;border-radius: 16rpx;background: linear-gradient(141deg, #FFFFFF 28%, #F7FFFA 100%);flex-direction: column;margin-top: 28rpx;}
	.option-personnel .sort-options .title-text{color: #3A4463;font-size: 26rpx;font-weight: bold;padding: 15rpx 0rpx;}
	.option-personnel .sort-options .classification-text{background: #eb8c2b;font-size: 24rpx;color: #fff;padding: 5rpx 20rpx;border-radius: 10rpx;}
	.option-personnel .sort-options .img-view{width: 120rpx;height: 120rpx;border-radius: 50%;}
	.option-personnel .sort-options .img-view image{width: 100%;height: 100%;}
	
	
	.sort-option-view-new{width: 100%;align-items: center;border-radius:12rpx;padding: 35rpx;margin-bottom: 20rpx;background: #fff;}
	.sort-option-view-new .title-text{color: #3A4463;font-size: 26rpx;font-weight: 500;}
	.sort-option-view-new .right-text{color: rgba(58, 68, 99, 0.4);font-size: 24rpx;}
	.sort-option-view-new .right-text image{width: 35rpx;height: 35rpx;margin-left: 30rpx;}
</style>