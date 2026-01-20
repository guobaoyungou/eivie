<template>
	<view class="container">
	<view v-if="isload">
		<view class="bg-view"></view>
		<view class="content-view flex-col">
			<view class="info-view">
				<view class="image-view">
					<image :src="member.headimg"></image>
				</view>
				<view class="info-view-text flex-col">
					<view class="title-text">{{member.nickname}}</view>
					<view class="inventory-text">{{member.tel}}</view>
				</view>
				<!-- <view class="but-class">签章</view>
				<view class="job-class">项目经理</view> -->
			</view>
			<view class="content-view-data flex-col">
				<view class="title-text">门店列表</view>
				<view class="options-view flex-bt" v-for="(item,index) in datalist" :key="index" @click="goto" :data-url="'/pagesA/businessFenxiao/mendian?bid='+item.id">
					<view class="image-view">
						<image :src="item.logo"></image>
					</view>
					<view class="info-view-data flex-col">
						<view class="title-text">{{item.name}}</view>
						<view class="inventory-text">{{item.stage}}</view>
					</view>
				</view>
			</view>
			
		</view>
	</view>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
	<loading v-if="loading"></loading>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				opt:{},
				loading:false,
				isload: false,
				menuindex:-1,
				
				nodata:false,
				nomore:false,
				datalist: [],
				pagenum: 1,
				type:0,
				member:{}
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.type = this.opt.type || 0;
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		onReachBottom: function () {
			if (!this.nomore && !this.nodata) {
				this.pagenum = this.pagenum + 1;
				this.getdata(true);
			}
		},
		methods:{
			getdata: function (loadmore) {
				if(!loadmore){
					this.pagenum = 1;
					this.datalist = [];
				}
				var that = this;
				that.loading = true;
				that.nodata = false;
				that.nomore = false;
				var pagenum = that.pagenum;
				app.post('ApiBusinessFenxiao/mendianlists', {pagenum: that.pagenum,type:that.type}, function (res) {
					that.loading = false;
					var data = res.data;
					if (pagenum == 1) {
						that.datalist = data;
						if (data.length == 0) {
							that.nodata = true;
						}
						that.member = res.member;
						that.loaded();
					}else{
						if (data.length == 0) {
							that.nomore = true;
						} else {
							var datalist = that.datalist;
							var newdata = datalist.concat(data);
							that.datalist = newdata;
						}
					}
				});
			},
		}
	}
</script>

<style>
	.bg-view{width: 150%;background: #eb8c2b;height: 500rpx;border-radius: 50%;position: relative;top:-260rpx;left: 50%;transform: translateX(-50%);z-index: 1;}
	.content-view{width: 90%;height: auto;position: absolute;top:30rpx;z-index:2;left: 50%;transform: translateX(-50%);}
	.info-view{background: #fff;height:200rpx;width: 100%;margin:20rpx auto;padding:30rpx 40rpx;align-items: center;display: flex;align-items: center;justify-content: flex-start;border-radius: 16rpx;
	position: relative;}
	.info-view .image-view{width: 128rpx;height: 128rpx;background: #F5F7F9;border-radius: 50%;}
	.info-view .image-view image{width: 100%;height: 100%;}
	.info-view .info-view-text{padding: 0rpx 20rpx;}
	.info-view .info-view-text .title-text{font-size: 30rpx;color: #3A4463;font-weight: bold;}
	.info-view .info-view-text .inventory-text{color: rgba(58, 68, 99, 0.5);font-size: 24rpx;padding-top: 20rpx;}
	.info-view .but-class{width: 110rpx;height: 50rpx;line-height: 50rpx;color: #fff;border-radius:8rpx;text-align: center;font-size: 24rpx;background-color: #eb8c2b;
	position: absolute;right: 40rpx;bottom: 50rpx;}
	.job-class{position: absolute;right: 50rpx;top: 20rpx;font-size: 24rpx;font-weight: bold;color: #333;}
	.content-view-data{width: 100%;height: auto;}
	.content-view-data .title-text{font-size: 24rpx;font-weight: bold;color: #333;padding: 15rpx 20rpx;}
	.options-view{background: #fff;width: 100%;margin-bottom:5rpx;padding: 15rpx 20rpx;align-items: center;display: flex;align-items: center;justify-content: space-between;
	margin-top: 15rpx;border-radius: 16rpx;box-shadow: 0rpx 0rpx 12rpx 1rpx rgba(0,0,0,.1);}
	.options-view .image-view{width: 128rpx;height: 128rpx;background: #F5F7F9;border-radius: 12rpx;}
	.options-view .image-view image{width: 100%;height: 100%;}
	.info-view-data{width: 75%;}
	.info-view-data .title-text{font-size: 30rpx;font-family:500;color: #3A4463;padding: 10rpx 0rpx;}
	.info-view-data .inventory-text{color: rgba(58, 68, 99, 0.5);font-size: 24rpx;padding: 10rpx 0rpx;}
</style>