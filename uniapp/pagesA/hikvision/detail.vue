<template>
	<view class="page" v-if="isload" :style="{height:pageHeight+'px'}">
		<video 
		        id="myVideo" 
		        :src="video_url" 
		        binderror="videoErrorCallback" 
		        show-casting-button
		        show-screen-lock-button
		        show-center-play-btn='true' 
		        show-play-btn="true" 
		        picture-in-picture-mode="['push', 'pop']"
		        bindenterpictureinpicture='bindVideoEnterPictureInPicture'
		        bindleavepictureinpicture='bindVideoLeavePictureInPicture'
		        enable-auto-rotation="true"
						style="width: 100%;"
		      ></video>
		<view v-if="!isalipay" class="goback" @tap="goback" :style="{top:gobacktopHeight+'px'}">
			<image class="goback-img" :src="pre_url+'/static/img/goback.png'"" />
		</view>
		
		<!-- 
			<view class="dp-menu" style="padding-top:16rpx;">
				<view class="menu-title" style="color:#666666;fontSize:28rpx"></view>
				<block>
					<view v-for="item in control" class="menu-nav5" @click="movevideo(item)" >
						<view class="menu-text" style="height:56rpx;lineHeight:56rpx">{{item.title}}</view>
					</view>
				</block>
			</view> -->
			<view class="menu-view">
				<view class="content-view">
					<view class="one-view-view">
						<button v-if="fxList.right_top" class="one-view" @click="Change('right_top')" :style="{transform: 'rotate(0deg) skewY(-45deg)'}"></button>
						<button v-if="fxList.right" class="one-view" @click="Change('right')" :style="{transform: 'rotate(45deg) skewY(-45deg)'}"></button>
						<button v-if="fxList.right_down" class="one-view" @click="Change('right_down')" :style="{transform: 'rotate(90deg) skewY(-45deg)'}"></button>
						<button v-if="fxList.down" class="one-view" @click="Change('down')" :style="{transform: 'rotate(135deg) skewY(-45deg)'}"></button>
						<button v-if="fxList.left_down" class="one-view" @click="Change('left_down')" :style="{transform: 'rotate(180deg) skewY(-45deg)'}"></button>
						<button v-if="fxList.left" class="one-view" @click="Change('left')" :style="{transform: 'rotate(225deg) skewY(-45deg)'}"></button>
						<button v-if="fxList.left_top" class="one-view" @click="Change('left_top')" :style="{transform: 'rotate(270deg) skewY(-45deg)'}"></button>
						<button v-if="fxList.top" class="one-view" @click="Change('top')" :style="{transform: 'rotate(315deg) skewY(-45deg)'}"></button>
					</view>
					<!-- 方向 -->
					<view class="two-view">
						<view class="img-view" style="transform: rotate(22.5deg);">
							<view class="img-view-child"  v-if="fxList.right">
								<image :src="pre_url+'/static/img/xiangshang.png'" class="calss-img1 right"></image>
							</view>
						</view>
						<view class="img-view" style="transform: rotate(67.5deg);">
							<view class="img-view-child"  v-if="fxList.right_down">
								<image :src="pre_url+'/static/img/xiangshang.png'" class="calss-img2"></image>
							</view>
						</view>
						<view class="img-view" style="transform: rotate(112.5deg);">
							<view class="img-view-child"  v-if="fxList.down">
								<image :src="pre_url+'/static/img/xiangshang.png'" class="calss-img1"></image>
							</view>
						</view>
						<view class="img-view" style="transform: rotate(157.5deg);">
							<view class="img-view-child"  v-if="fxList.left_down">
								<image :src="pre_url+'/static/img/xiangshang.png'" class="calss-img2"></image>
							</view>
						</view>
						<view class="img-view" style="transform: rotate(202.5deg);">
							<view class="img-view-child"  v-if="fxList.left">
								<image :src="pre_url+'/static/img/xiangshang.png'" class="calss-img1"></image>
							</view>
						</view>
						<view class="img-view" style="transform: rotate(247.5deg);">
							<view class="img-view-child"  v-if="fxList.left_top">
								<image :src="pre_url+'/static/img/xiangshang.png'" class="calss-img2"></image>
							</view>
						</view>
						<view class="img-view" style="transform: rotate(292.5deg);">
							<view class="img-view-child"  v-if="fxList.top">
								<image :src="pre_url+'/static/img/xiangshang.png'" class="calss-img1"></image>
							</view>
						</view>
						<view class="img-view" style="transform: rotate(337.5deg);">
							<view class="img-view-child" v-if="fxList.right_top">
								<image :src="pre_url+'/static/img/xiangshang.png'" class="calss-img2"></image>
							</view>
						</view>
					</view>
					
					<!-- 开始/暂停 -->
					<view class="start-view flex-xy-center" @click.stop="Change('start_video')" v-if="fxList.start_video || fxList.stop_video">
						<image v-if="type" :src="pre_url+'/static/img/kaishi.png'"></image>
						<image v-else :src="pre_url+'/static/img/zanting.png'"></image>
					</view>
				</view>
				<view class="bottom-but flex-aw">
					<view v-if="fxList.big" class="options-view" @click="Change('big')"><image :src="pre_url+'/static/img/fangda.png'"></image></view>
					<view v-if="fxList.small" class="options-view" @click="Change('small')"><image :src="pre_url+'/static/img/suoxiao.png'"></image></view>
					<view v-if="fxList.far" class="options-view" @click="Change('far')"><image :src="pre_url+'/static/img/yuanjiao.png'"></image></view>
					<view v-if="fxList.near" class="options-view" @click="Change('near')"><image :src="pre_url+'/static/img/jinjiao.png'"></image></view>
				</view>
			</view>

		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>

	</view>
</template>
<script>
	var app = getApp();
  var interval = null;
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				nomore: false,
				nodata: false,
				platform: app.globalData.platform,
				pre_url: app.globalData.pre_url,
				gobacktopHeight: 40,
				id:0,
				video_url:'',
				control:{},
				deviceSerial:'',
				id:0,
				fxList:{},
				videoType:true,
				isalipay:false,
				type:1,
				direction:0
			}
		},
		onLoad: function(opt) {
			var that = this;
			var opt = app.getopts(opt);
			that.opt = opt;
			that.id = opt.id || 0;
			console.log(opt)
		},
		onReady() {
			this.getdata();
			var sysinfo = uni.getSystemInfoSync();
			this.pageHeight = sysinfo.windowHeight;
			if (sysinfo && sysinfo.statusBarHeight) {
				this.gobacktopHeight = sysinfo.statusBarHeight;
			}
			// #ifdef H5
			this.gobacktopHeight = 20;
			// #endif
			if(uni.getSystemInfoSync().uniPlatform=='mp-alipay'||uni.getSystemInfoSync().uniPlatform=='mp-baidu'){
				this.isalipay = true;
			}
		},
		methods: {
			getdata:function(){
				var that = this;
				that.isload = true;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.post('ApiHikvision/getdetail', {id: that.id}, function (res) {
					that.loading = false;
					if(res.status==1){
						that.video_url = res.video_url;
						that.control = res.control;
						Object.keys(that.control).forEach(item => {
							that.fxList[item] = that.control[item].is_show == 1 ? true:false;
						})
					}else{
						uni.showToast({
							title: res.msg,
							icon:'none',
							duration: 2000
						});
					}
					
				})
			},
			movevideo:function(e){
				var that = this;
				console.log(e);
				app.post('ApiHikvision/control', {id: that.id,type:e.type,value:e.value}, function (res) {
					
				})
			},
			Change(e){
				let that = this;
				let data = {};
				switch (e) {
					case 'right_top':
					data = that.control.right_top;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'right':
					data = that.control.right;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'right_down':
					data = that.control.right_down;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'down':
					data = that.control.down;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'left_top':
					data = that.control.left_top;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'left_down':
					data = that.control.left_down;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'top':
					data = that.control.top;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'left':
					data = that.control.left;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'big':
					data = that.control.big;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'small':
					data = that.control.small;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'far':
					data = that.control.far;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'near':
					data = that.control.near;
					that.direction = data.value;
					that.type = 0;
					break;
					case 'start_video':
					that.videoType = !that.videoType;
					data = that.videoType ? that.control.start_video : that.control.stop_video;
					that.type = 1;
					break;
				}
				app.post('ApiHikvision/control', {id: that.id,type:that.type,direction:that.direction}, function (res) {
					console.log(res);
					if(res.status==1){
						//app.alert('操作成功，请等待');
						uni.showToast({
							title: '操作成功，请等待',
							icon:'none',
							duration: 1000
						});

					}
				})
			}
		}
	}
</script>

<style>
	/* #ifndef APP-PLUS */
	page {
		width: 750rpx;
		height: 100vh;
		overflow: hidden;
		position: relative;
		background: #fff;
	}

	/* #endif */
	.page {
		position: absolute;
		width: 750rpx;
		top: 0;
		left: 0;
		/* width: 750rpx; */
	}

	.swiper {
		position: absolute;
		width: 750rpx;
		top: 0;
		left: 0;
	}

	.video {
		position: absolute;
		width: 750rpx;
		top: 0;
		left: 0;
	}

	.flex-row {
		display: flex;
		flex-direction: row
	}

	.goback {
		position: absolute;
		z-index: 4;
		top: 40px;
		left: 15px;
		width: 30px;
		height: 30px;
		display: flex;
		flex-direction: column;
		align-items: center
	}

	.goback-img {
		width: 30px;
		height: 30px
	}

	.playbox {
		position: absolute;
		width: 750rpx;
		height: 100%;
		flex: 1;
		background: rgba(0, 0, 0, 0.5);
		z-index: 3
	}


	@supports(bottom: env(safe-area-inset-bottom)) {
		.dp-tabbar-bot {
			padding-bottom: 0 !important;
		}

		.dp-tabbar-bar {
			padding-bottom: 0 !important;
		}
	}
  .gbdesc{float: right;background: linear-gradient(-90deg, #FD4A46 0%, rgba(253, 74, 70, 0.76) 100%);overflow: hidden;border-radius:10rpx;padding: 0rpx 24rpx;flex-direction: unset;font-weight: 500;font-size: 28rpx;color: #fff;position: unset;}
  .gbshortdesc{margin-top: 14rpx;color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;height:36rpx}
  .bottombar{ width: 100%; position: fixed;bottom: 0px; left: 0px; background: #fff;height:110rpx;align-items:center;box-sizing:content-box}
  .bottombar .cart{width: 50%;font-size:26rpx;color:#707070}
  .bottombar .cart .img{ width:50rpx;height:50rpx}
  .bottombar .cart .t1{font-size:24rpx;color:#222222;height:30rpx;line-height:30rpx;margin-top:6rpx}
  .bottombar .tobuy{ width:50%; height: 90rpx;border-radius:8rpx;color: #fff;background: linear-gradient(-90deg, #FD4A46 0%, rgba(253, 74, 70, 0.76) 100%); font-size:28rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:45rpx;margin-right:16rpx;padding-right:20rpx}
  
  .bottomshare{width: 100%;height: 150rpx;position: fixed;bottom: 0;flex-direction: unset;color: #000;background-color: #fff;}
  .bottomshare_item{width: 100%;line-height: 100rpx;margin-top: 15rpx;text-align: center;display: block;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;margin: 10rpx;margin-top: 15rpx;padding: 10rpx;border-radius: 8rpx;height: 120rpx;box-shadow: 6rpx 6rpx 16rpx #ccc;}
  .bottomshare_headimg{width: 50rpx;height: 50rpx;overflow: hidden;border: 2rpx solid #ccc;border-radius: 50%;}
  .bottomshare_title{font-size: 24rpx;line-height: 50rpx;text-align: center;display: block;overflow: hidden;}
  .nocss{flex-shrink: unset;flex-direction: unset;} 
  
  
  .dp-menu {height:auto;position:relative;padding-left:20rpx; padding-right:20rpx; background: #fff;}
  .dp-menu .menu-title{width:100%;font-size:30rpx;color:#333333;font-weight:bold;padding:0 0 32rpx 24rpx}
  .dp-menu .swiper-item{display:flex;flex-wrap:wrap;flex-direction: row;height:auto;overflow: hidden;align-items: flex-start;}
  .dp-menu .menu-nav {flex:1;text-align:center;}
  .dp-menu .menu-nav5 {
		width:20%;text-align:center;margin-bottom:16rpx;position:relative;display: inline-block;
		background: gray;
		height: 56rpx;
		line-height: 56rpx;
		border-radius: 20rpx;
		color: white;
	    margin-left: 20rpx;
	}


.menu-view{width: 100%;background: #fff;position: relative;height: auto;transform: rotate);padding: 40rpx 0rpx;margin-top: 5vh;}
.menu-view .content-view{width: 400rpx;height: 400rpx;border-radius: 50%;background:#e5e6e7;margin: auto;position: relative;transform: rotate(22.5deg);overflow: hidden;}
.two-view{position: absolute;left: 0;top:0;width: 100%;height: 100%;z-index: 1;overflow: hidden;}
.one-view-view{position: absolute;left: 0;top:0;width: 100%;height: 100%;z-index: 2;overflow: hidden;}
.menu-view .content-view .one-view{width: 400rpx;height: 400rpx;position: absolute;left: 50%;top: -50%;transform-origin: 0% 100%;}
.menu-view .content-view .img-view{width: 200rpx;height: 200rpx;position: absolute;left: 50%;top: 0;transform-origin: 0% 100%;overflow: hidden;}
.menu-view .content-view .img-view .img-view-child{width: 100%;height: 100%;display: flex;flex-direction: column;align-items: center;justify-content: center;transform: rotate(45deg) translate(0px, -11%);}
.menu-view .content-view .img-view .img-view-child .calss-img1{width: 35rpx;height: 35rpx;}
.menu-view .content-view .img-view .img-view-child .calss-img2{width: 25rpx;height: 25rpx;}
.menu-view .content-view .start-view{width: 200rpx;height: 200rpx;border-radius: 50%;background: #f3f3f3;position: absolute;left: 50%;top:50%;transform:translate(-50%,-50%) rotate(-22.5deg);z-index: 3;}
.menu-view .content-view .start-view image{width: 100rpx;height: 100rpx;}
.menu-view .bottom-but{border-top: 1px #e5e6e7 solid;margin: 60rpx auto 0rpx;padding: 30rpx 100rpx}
.menu-view .bottom-but .options-view{padding: 0rpx 20rpx;}
.menu-view .bottom-but .options-view image{width: 50rpx;height: 50rpx;}
</style>
