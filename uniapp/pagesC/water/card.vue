<template>
	<view class="container">
		<block v-if="isload">
			<form report-submit="true" @submit="subconfirm" style="width:100%">
				<block v-if="cardlist.length > 0">
					<view class="top" v-for="(item, index) in cardlist" :key="index">
						<view class="cardbg" v-if="item.status == 1" :style="'background: '+t('color1')"></view>
						<view class="cardbg" v-else></view>
						<view class="hyk flex">
							<view class="f1">
								<image :src="set.logo">
							</view>
							<view class="f2">
								<text class="t1">{{set.name}}</text>
								<text class="t2">{{item.card_no}}</text>
								<text class="t3" v-if="item.status == -1">已挂失</text>
								<text class="t3" v-if="item.status == 0">停用</text>
							</view>
						</view>
						
						<view class="fr">
							<view class="btn-mini" :data-cardno="item.card_no" @tap.stop="unbind">解绑</view>
						</view>
					</view>
				</block>
				<block v-else>
					<block v-if="set.bind_card_status == 1">
						<view class="title">请扫码进行绑定</view>
						<view class="inputdiv">
							<input id="cardno" type="text" name="cardno" :value="cardno" placeholder-style="color:#666;"
								placeholder="请输入您的卡号" />
							<view class="scanicon" @tap="saoyisao">
								<image :src="pre_url+'/static/img/scan-icon2.png'"></image>
							</view>
						</view>
						<button class="btn" form-type="submit">绑 定</button>
					</block>
				</block>
				
				<!-- <view class="f0" @tap="goto" data-url="dhlog"><text>查看兑换记录</text></view> -->
			</form>


		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt"></dp-tabbar>
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
				menuindex: -1,
				platform: app.globalData.platform,
				pre_url: app.globalData.pre_url,

				cardlist: [],
				cardno: '',
				set: {},
			};
		},

		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			if (this.opt && this.opt.cardno) this.cardno = this.opt.cardno;
			this.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiWaterTongyuan/index', {}, function(res) {
					that.loading = false;
					that.set = res.set;
					that.cardlist = res.data;
					that.loaded();
				});
			},
			subconfirm: function(e) {
				var that = this;
				var cardno = e.detail.value.cardno;
				that.loading = true;
				app.post('ApiWaterTongyuan/bind', {
					cardno: cardno
				}, function(res) {
					that.loading = false;
					if (res.status == 0) {
						app.error(res.msg);
						return;
					}
					if (res.status == 1) {
						app.success(res.msg, function() {
							setTimeout(function() {
								// 获取当前页面实例
								const pages = getCurrentPages();
								const currentPage = pages[pages.length - 1];

								// 重新执行页面的onLoad方法（传入当前页面的参数）
								currentPage.onLoad(currentPage.options);
							}, 1000);
						});
					}
				});
			},
			unbind: function(e) {
				var that = this;
				var cardno = e.currentTarget.dataset.cardno;
				app.confirm('确定要解绑吗？解绑后不可撤销', function () {
				  that.loading = true;
				  app.post('ApiWaterTongyuan/unbind', {
				  	cardno: cardno
				  }, function(res) {
				  	that.loading = false;
				  	if (res.status == 0) {
				  		app.error(res.msg);
				  		return;
				  	}
				  	if (res.status == 1) {
				  		app.success(res.msg, function() {
				  			setTimeout(function() {
				  				// 获取当前页面实例
				  				const pages = getCurrentPages();
				  				const currentPage = pages[pages.length - 1];
				  
				  				// 重新执行页面的onLoad方法（传入当前页面的参数）
				  				currentPage.onLoad(currentPage.options);
				  			}, 1000);
				  		});
				  	}
				  });
				});
				
			},
			saoyisao: function(d) {
				var that = this;
				if (app.globalData.platform == 'h5') {
					app.alert('请使用微信扫一扫功能扫码');
					return;
				} else if (app.globalData.platform == 'mp') {
					// #ifdef H5
					var jweixin = require('jweixin-module');
					jweixin.ready(function() { //需在用户可能点击分享按钮前就先调用
						jweixin.scanQRCode({
							needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
							scanType: ["qrCode", "barCode"], // 可以指定扫二维码还是一维码，默认二者都有
							success: function(res) {
								var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
								if (content.indexOf('-') !== -1) {
									var contentArr = content.split('-');
									var cardno = contentArr[1];
								}
								that.cardno = cardno;
								//90AC2C33-100000519726-e0ceca1363c150a13c5f05292b05cc53
							}
						});
					});
					// #endif
				} else {
					// #ifndef H5
					uni.scanCode({
						success: function(res) {
							console.log(res);
							var content = res.result;
							if (content.indexOf('-') !== -1) {
								var contentArr = content.split('-');
								var cardno = contentArr[1];
							}
							that.cardno = cardno;
							//app.goto('prodh?cardno='+params);
						}
					});
					// #endif
				}
			}
		}
	}
</script>
<style>
	.container {display: flex;flex-direction: column;}
	
	.top{ position: relative; height:280rpx; width: 100%;margin: 20rpx 0;}
	.cardbg{ position: absolute; width: 88%; height:280rpx;margin: 50rpx;border-radius: 10rpx; background-color: #CCCCCC}
	.cardbg image{ width: 100%; height:100%;border-radius: 10rpx;}
	.hyk{ margin-top: 30rpx; z-index: 1000;position: absolute; left:10%; top:20%;}
	.hyk .f1 image{ width: 100rpx;height: 100rpx; border-radius:50%}
	.hyk .f2 { display: flex;flex-direction: column;margin-left: 20rpx;}
	.hyk .f2 .t1{ margin-top: 10rpx;color: #fff;font-size: 30rpx;font-weight: bold;}
	.hyk .f2 .t2{ color: #F5F2F2;font-size: 28rpx; margin: 10rpx 0;}
	.hyk .f2 .t3{ color: #FC4343;font-size: 28rpx;}
	.top .fr{ margin-top: 30rpx; z-index: 1000;position: absolute; right:10%; top:20%}
	.btn-mini{color: #fff;border: 1px solid;padding: 6rpx 20rpx;border-radius: 5rpx;}

	.container .title {display: flex;justify-content: center;width: 100%;color: #555;font-size: 40rpx;text-align: center;height: 100rpx;line-height: 100rpx;margin-top: 60rpx}

	.container .inputdiv {display: flex;width: 90%;margin: 0 auto;margin-top: 40rpx;margin-bottom: 40rpx;position: relative}

	.container .inputdiv input {background: #fff;width: 100%;height: 120rpx;line-height: 120rpx;padding: 0 40rpx;font-size: 40rpx;border: 1px solid #f5f5f5;border-radius: 20rpx}

	.container .btn {height: 88rpx;line-height: 88rpx;background: #FC4343;width: 90%;margin: 0 auto;border-radius: 8rpx;margin-top: 60rpx;color: #fff;font-size: 36rpx;}

	.container .f0 {width: 100%;margin-top: 40rpx;height: 60rpx;line-height: 60rpx;color: #FC4343;font-size: 30rpx;display: flex;align-items: center;justify-content: center}

	.container .scanicon {width: 52rpx;height: 52rpx;position: absolute;top: 34rpx;right: 20rpx;z-index: 9}

	.container .scanicon image {width: 100%;height: 100%}
</style>