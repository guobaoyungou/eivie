<template>
	<view>
		<block v-if="isload">
			<!-- 地图 -->
			<map class="map" :longitude="info.longitude" :latitude="info.latitude" scale="14" :markers="markers"></map>
			<view @tap="showMap" :data-name="info.shopname" :data-address="info.address" :data-longitude="info.longitude" :data-latitude="info.latitude" style="position: absolute; right: 10px; top:10px; background-color: rgba(0, 0, 0, 0.6); color: #fff; padding: 4px 6px; border-radius: 5px;">导航到店</view>
			<!-- 卡片 -->
			<view class="agent-card">
				<view class="flex-y-center row1">
					<image class="logo" :src="info.logo"/>
					<view class="text">
						<view class="title limitText flex">{{info.shopname}}</view>
						<view class="limitText grey-text">{{info.address}}</view>
						<view class="grey-text flex-y-center">
							<image class="img" :src="pre_url+'/static/img/my.png'" ></image><view>{{info.name}}</view>
							<image class="img" :src="pre_url+'/static/img/tel.png'" style="margin-left: 30rpx;"></image><view @tap="goto" :data-url="'tel::'+info.tel" style="position: relative;">{{info.tel}}<view class="btn" @tap="goto" :data-url="'tel::'+info.tel">拨打</view></view>
						</view>
					</view>
					<view class="right"><image :src="pre_url+'/static/img/shop_vip.png'" mode="aspectFit" style="width: 180rpx; height: 48.5rpx;"></image></view>
				</view>
			</view>
			<!-- 图文 -->
			<view class="detail_title"><view class="t1"></view><view class="t2"></view><view class="t0">店铺介绍</view><view class="t2"></view><view class="t1"></view></view>
			<view class="detail">
				<dp :pagecontent="pagecontent"></dp>
			</view>
		</block>
		
		<loading v-if="loading"></loading>
	</view>
	
</template>

<script>
	
	var app = getApp();
	export default {
		data() {
			return {
				opt:{},
				loading:false,
				isload: false,
				pre_url:app.globalData.pre_url,
				
				info:{},
				pagecontent: "",
				markers:[]
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods: {
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiIndex/agentCard', {pid:app.globalData.pid}, function (res) {
					that.loading = false;
					that.info = res.info;
					that.pagecontent = res.pagecontent ? res.pagecontent : [];
					that.markers = [{
						id:0,
						latitude:that.info.latitude,
						longitude:that.info.longitude,
						iconPath: `${that.pre_url}/static/img/peisong/marker_business.png`,
						width:'44',
						height:'54',
						callout:{
							content:that.info.shopname,
							fontSize:14,
							borderRadius:5,
							display:'ALWAYS'
						}
					}];
					that.loaded();
				});
			}
		}
	}
</script>

<style>
	.map{width:100%;height:500rpx;overflow:hidden}
	
	.limitText{flex: 1;display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 1;
	overflow: hidden; color: #666;}
	.agent-card{height: auto; position: relative;background-color: #fff; margin: 30rpx 20rpx 10rpx; font-size: 24rpx; border-radius: 0 10rpx 10rpx 10rpx; overflow: hidden;box-shadow: 0 0 8rpx 0px rgb(0 0 0 / 30%);}
	.agent-card .row1 {padding:20rpx 10rpx 20rpx 20rpx;}
	.agent-card .logo{ width:120rpx;height:120rpx; border-radius: 50%;}
	.agent-card .text { flex: 1; margin-left: 20rpx;color:#666; line-height: 180%;}
	.agent-card .title { color: #333;font-weight: bold; font-size: 32rpx;}
	.agent-card .right {height: 120rpx;}
	.agent-card .btn {position: absolute; right: -100rpx;padding:0 14rpx; top:0; border: 1px solid #B6C26E; border-radius: 10rpx; color: #B6C26E;}
	.agent-card .img { margin-right: 6rpx;width: 30rpx; height: 30rpx}
	.agent-card .img2 {width: 32rpx; height: 32rpx}
	.grey-text{color: #999;font-weight: normal;}
	
	.detail{min-height:200rpx;}
	
	.detail_title{width:100%;display:flex;align-items:center;justify-content:center;margin-top:60rpx;margin-bottom:30rpx}
	.detail_title .t0{font-size:28rpx;font-weight:bold;color:#222222;margin:0 20rpx}
	.detail_title .t1{width:12rpx;height:12rpx;background:rgba(253, 74, 70, 0.2);transform:rotate(45deg);margin:0 4rpx;margin-top:6rpx}
	.detail_title .t2{width:18rpx;height:18rpx;background:rgba(253, 74, 70, 0.4);transform:rotate(45deg);margin:0 4rpx}

</style>
