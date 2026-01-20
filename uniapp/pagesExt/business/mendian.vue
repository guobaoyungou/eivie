<template>
	<view class="container">
		<block v-if="isload">
			<view class="content">
				<!-- 门店地图打点 -->
				<map id="maps" :latitude="latitude" :longitude="longitude" :markers="markers" :scale="mapScale" enable-zoom="true" :class="[isshowlist ? 'map-class-list':'map-class']" :enable-satellite="maptype"></map>
			</view>
			<view class="content1">
				<view class="toggle" @tap="mendianToggle">
					<image :class="isshowlist?'toggle-icon':'toggle-icon up'" :src="pre_url+'/static/img/arrowdown.png'"></image>
				</view>
				<view class="mendian-box" v-if="isshowlist">
					<block v-for="(mendian,index) in mendianlist" :key="index">
						<view class="mendian-info">
							<view class="b1" @tap="goto" :data-url="'/pages/shop/mendian?id='+mendian.id"><image :src="mendian.pic"></image></view>
							<view class="b2">
								<view class="t1" @tap="goto" :data-url="'/pages/shop/mendian?id='+mendian.id">{{mendian.name}}</view>
								<view class="t2 flex-y-center">
									<view class="mendian-distance">{{mendian.distance}}</view>
									<block v-if="mendian.address || mendian.area">
										<view class="line"> </view>
										<view class="mendian-address"> {{mendian.address?mendian.address:mendian.area}}</view>
									</block>
								</view>
							</view>
							<view class="b3">
								<!-- <view @tap="callMendian" :data-tel="mendian.tel"><image :src="pre_url+'/static/img/location/tel.png'"></image></view> -->
								<!-- #ifndef MP-ALIPAY-->
								<image @tap="toMendian" :data-address="mendian.address" :data-longitude="mendian.longitude" :data-latitude="mendian.latitude" :src="pre_url+'/static/img/location/daohang.png'"></image>
								<!-- #endif -->
							</view>
						</view>
					</block>
				</view>
			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" index='3'></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
		<wxxieyi></wxxieyi>
	</view>
</template>

<script>
	var app = getApp();

	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				pre_url: app.globalData.pre_url,
				menuindex: -1,
				mendianlist:[],
				markers: [],
				longitude: '',
				latitude: '',
				customMarkerIds:[],
				maptype:false,
				proid:'',//商品id，多个用逗号分隔，售卖某商品的店
				isshowlist:true,
				mapScale:11,
        type:''//查询商品类型，默认查询商城商品
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.bid = this.opt.bid || 0;
			this.proid = this.opt.proid || '';
      this.type = this.opt.type || '';
			var that = this;
			app.getLocation(function(res) {
				that.latitude = res.latitude;
				that.longitude = res.longitude;
				that.getdata();
			});
			uni.setNavigationBarTitle({
				title: that.t('门店')
			});
			that.getdata();
		},
		onPullDownRefresh: function() {
			return
		},
		methods: {
			getdata: function() {
				var that = this; 
				//that.loading = false;
				app.post('ApiMendian/mendianlist', {
					bid:that.bid,
					latitude: that.latitude,
					longitude: that.longitude,
					proid:that.proid,
          type:that.type
				}, function(res) {
					if (res.status == 1) {
						//默认显示第一个，即距离最近的一个
						if (res.data.length > 0) {
							var mendianIndex = 0;
						//	that.latitude = res.data[mendianIndex].latitude
						//	that.longitude = res.data[mendianIndex].longitude
						}
						that.mendianlist = res.data;
						//console.log(that.mendianlist);
						//初始化markers
						var data = res.data;
						var markers = [];
						var customMarkerIds = []
						for (var i = 0; i < data.length; i++) {
							var iconPath = that.pre_url+'/static/img/location/marker_business.png'
							if (data[i].guimo_icon) {
								iconPath = data[i].guimo_icon
							}
							if(i==0){
								that.mapScale = app.getMapZoom(data[i].distanceNumM);
							}
							var _marker = {
								id: i,
								ids:data[i].id,
								latitude: data[i].latitude,
								longitude: data[i].longitude,
								title: data[i].name,
								iconPath: iconPath,
								height:50,
								width:40,
								customCallout:{  
									anchorY: 0, // Y轴偏移量
									anchorX: 100, // X轴偏移量
									display:"BYCLICK",
									borderRadius:'5px'
								}
							}
							markers.push(_marker)
							customMarkerIds.push(i)
						}
						that.data = res.data
						that.markers = markers
						that.customMarkerIds = customMarkerIds
						that.loaded()
					} else {
						app.alert(res.msg)
					}
				})
			},
			toMendian:function(e){
				var latitude = parseFloat(e.currentTarget.dataset.latitude);
				var longitude = parseFloat(e.currentTarget.dataset.longitude);
				var address = e.currentTarget.dataset.address;
				if(!latitude || !longitude){
					return;
				}
				uni.openLocation({
				 latitude:latitude,
				 longitude:longitude,
				 name:address,
				 scale: 13
				})
			},
			mendianToggle:function(){
				this.isshowlist = this.isshowlist?false:true
			}
		}
	};
</script>
<style>
	page{width: #eef1f6;}
	.container {
		display: flex;
		flex-direction: column;
	}
	.content{ width: 100%;}
	.calloutContent{background-color: #fff; padding: 20rpx;border-radius: 10rpx;;}
	.customCallout {
			box-sizing: border-box;
			background-color: #fff;
			border: 1px solid #ccc;
			border-radius: 30px;
			width: 150px;
			height: 40px;
			display: inline-flex;
			padding: 5px 20px;
			justify-content: center;
			align-items: center;
		}
.calloutSub{ margin-top: 20rpx;}
/* #ifdef APP-PLUS */
.content1{ width: 96%;margin: 0 2%;height: auto;background: #FFFFFF;border-radius: 16rpx 16rpx 0 0 ;max-height: 60%;overflow-y: scroll;font-family: PingFang SC;}
/* #endif */
/* #ifndef APP-PLUS*/
.content1{ position: fixed; z-index: 1000;bottom: 0;width: 96%;margin: 0 2%;height: auto;background: #FFFFFF;border-radius: 16rpx 16rpx 0 0 ;max-height: 60%;overflow-y: scroll;font-family: PingFang SC;}
/* #endif */
.mendian-box{padding: 0 20rpx 20rpx 20rpx;min-height: 700rpx;}
.mendian-info{display: flex;align-items: center;width: 100%;border-bottom: 1rpx solid #F6F6F6;margin-bottom: 20rpx;padding-bottom: 16rpx;}
.mendian-info .b1{background-color: #fbfbfb;}
.mendian-info .b1 image{height: 100rpx;width:100rpx;border-radius: 6rpx;border: 1px solid #e8e8e8;}
.mendian-info .b2{flex:1;line-height: 38rpx;margin-left: 20rpx;overflow: hidden;}
.mendian-info .b2 .t1{padding-bottom: 10rpx;}
.mendian-info .b2 .t2{font-size: 24rpx;color: #999;}
.mendian-info .b3{display: flex;justify-content: flex-end;flex-shrink: 0;padding-left: 20rpx;}
.mendian-info .b3 image{width: 40rpx;height: 40rpx;}
.mendian-info .tag{padding:0 10rpx;margin-right: 10rpx;display: inline-block;font-size: 22rpx;border-radius: 8rpx;flex-shrink: 0;}
.mendian-info .mendian-address{text-overflow: ellipsis;flex:1;width: 300rpx;white-space: nowrap;}
.mendian-info .line{border-right: 1rpx solid #999;width: 10rpx;flex-shrink: 0;height: 16rpx;padding-left:10rpx;margin-right: 12rpx;}
.mendian-info .mendian-distance{color: #3b3b3b;font-weight: 600;flex-shrink: 0;}
.toggle{display: flex;align-items: center;justify-content: center;height: 50rpx;}
.toggle-icon{width: 34rpx;height: 28rpx;}
.toggle-icon.up{ -moz-transform:scaleY(-1);-webkit-transform:scaleY(-1);-o-transform:scaleY(-1);transform:scaleY(-1)}
.map-class-list{min-height:800rpx;width:100%}
/* #ifdef APP-PLUS */
.map-class{height:1300rpx;width:100%;}
/* #endif */
/* #ifndef APP-PLUS*/
.map-class{min-height:1500rpx;width:100%}
/* #endif */
</style>
