<template>
<view class="container">
	<block v-if="isload">
		<!-- 顶部提示栏 -->
		<view class="top-tips" v-if="isExpired">
			<text class="tips-icon">⚠</text>
			<text class="tips-text">成片已过期，无法下载</text>
		</view>
		<view class="top-tips top-tips-normal" v-else>
			<text class="tips-text">点击图片可预览大图，点击下方按钮保存全部</text>
		</view>

		<!-- 图片网格区 -->
		<view class="grid-wrap" :class="{expired: isExpired}">
			<view class="grid-item" v-for="(item, idx) in picList" :key="idx" @tap="previewImage(idx)">
				<image :src="item + '?x-oss-process=image/resize,w_400,h_400'" mode="aspectFill" class="grid-img"></image>
				<view class="expired-mask" v-if="isExpired"></view>
			</view>
		</view>

		<!-- 无图片提示 -->
		<view class="no-pic" v-if="picList.length === 0 && !isExpired">
			<text>暂无成片数据</text>
		</view>

		<!-- 底部操作栏 -->
		<view class="bottom-bar" v-if="!isExpired && picList.length > 0">
			<view class="btn-save-all" :style="{background: themeColor}" @tap="saveAll">
				<text>保存全部 ({{picList.length}}张)</text>
			</view>
		</view>
	</block>
	<loading v-if="!isload && loading"></loading>

	<!-- H5长按保存预览弹层 -->
	<view class="h5-preview-mask" v-if="showH5Preview" @tap="showH5Preview = false">
		<view class="h5-preview-body" @tap.stop="">
			<text class="h5-preview-tips">请长按图片保存</text>
			<image :src="h5PreviewUrl" mode="widthFix" class="h5-preview-img" show-menu-by-longpress></image>
			<view class="h5-preview-close" @tap="showH5Preview = false">关闭</view>
		</view>
	</view>
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
			detail: {},
			picList: [],
			isExpired: false,
			picindex: 0,
			showH5Preview: false,
			h5PreviewUrl: ''
		};
	},

	computed: {
		themeColor: function() {
			return app.globalData.initdata && app.globalData.initdata.color1 ? app.globalData.initdata.color1 : '#ff5722';
		}
	},

	onLoad: function(opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},

	methods: {
		getdata: function() {
			var that = this;
			that.loading = true;

			app.post('ApiUnifiedOrder/detail', {
				id: that.opt.id
			}, function(res) {
				that.loading = false;
				if (res.status == 1) {
					that.detail = res.data;
					// 提取成片图片列表
					that.picList = res.data.result_pics || [];
					that.isExpired = res.data.result_status === 'expired';
					that.isload = true;
				} else {
					uni.showModal({
						title: '提示',
						content: res.msg || '订单不存在',
						showCancel: false,
						success: function() {
							app.goback();
						}
					});
				}
			});
		},

		// 图片预览
		previewImage: function(idx) {
			if (this.isExpired) return;
			uni.previewImage({
				current: idx,
				urls: this.picList
			});
		},

		// 保存全部
		saveAll: function() {
			var that = this;
			var platform = app.globalData.platform;

			if (platform == 'mp' || platform == 'wx') {
				// 微信小程序 逐张下载保存
				that.picindex = 0;
				app.showLoading('[1/' + that.picList.length + ']保存中...');
				that.saveOnePic(that.picList, that.picList.length);
			} else if (platform == 'app') {
				// APP 逐张下载保存
				that.picindex = 0;
				app.showLoading('[1/' + that.picList.length + ']保存中...');
				that.saveOnePic(that.picList, that.picList.length);
			} else {
				// H5环境
				that.saveForH5();
			}
		},

		// 逐张保存（小程序/APP）
		saveOnePic: function(pics, total) {
			var that = this;
			var picindex = that.picindex;
			if (picindex >= pics.length) {
				app.showLoading(false);
				uni.showToast({
					title: '已保存到相册',
					icon: 'success'
				});
				return;
			}
			var pic = pics[picindex];
			uni.downloadFile({
				url: pic,
				success: function(res) {
					if (res.statusCode === 200) {
						uni.saveImageToPhotosAlbum({
							filePath: res.tempFilePath,
							success: function() {
								that.picindex++;
								app.showLoading('[' + (that.picindex + 1) + '/' + total + ']保存中...');
								that.saveOnePic(pics, total);
							},
							fail: function(err) {
								app.showLoading(false);
								var errMsg = err.errMsg || '';
								if (errMsg.indexOf('auth') > -1 || errMsg.indexOf('deny') > -1) {
									uni.showModal({
										title: '提示',
										content: '需要授权保存图片到相册，请前往设置开启权限',
										success: function(modalRes) {
											if (modalRes.confirm) {
												uni.openSetting();
											}
										}
									});
								} else {
									uni.showToast({
										title: '保存失败',
										icon: 'none'
									});
								}
							}
						});
					}
				},
				fail: function() {
					app.showLoading(false);
					uni.showToast({
						title: '下载图片失败',
						icon: 'none'
					});
				}
			});
		},

		// H5环境保存
		saveForH5: function() {
			var that = this;
			var ua = navigator.userAgent.toLowerCase();
			if (ua.indexOf('micromessenger') > -1) {
				// 微信浏览器：显示预览，提示长按保存
				if (that.picList.length > 0) {
					that.h5PreviewUrl = that.picList[0];
					that.showH5Preview = true;
				}
			} else {
				// 其他浏览器：触发下载
				that.picList.forEach(function(pic, idx) {
					var link = document.createElement('a');
					link.href = pic;
					link.download = 'photo_' + (idx + 1) + '.jpg';
					link.target = '_blank';
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
				});
				uni.showToast({
					title: '已开始下载',
					icon: 'success'
				});
			}
		},

		t: app.t
	}
};
</script>

<style>
.container {
	width: 100%;
	background: #f5f5f5;
	min-height: 100vh;
	padding-bottom: 140rpx;
}

.top-tips {
	width: 94%;
	margin: 20rpx 3%;
	background: #fff3cd;
	border-radius: 8rpx;
	padding: 20rpx;
	display: flex;
	align-items: center;
}
.top-tips-normal {
	background: #e8f5e9;
}
.tips-icon {
	font-size: 30rpx;
	margin-right: 10rpx;
}
.tips-text {
	font-size: 24rpx;
	color: #666;
}

.grid-wrap {
	width: 94%;
	margin: 20rpx 3%;
	display: flex;
	flex-wrap: wrap;
}
.grid-wrap.expired {
	opacity: 0.5;
}
.grid-item {
	width: 31.33%;
	margin-right: 3%;
	margin-bottom: 20rpx;
	position: relative;
	border-radius: 8rpx;
	overflow: hidden;
}
.grid-item:nth-child(3n) {
	margin-right: 0;
}
.grid-img {
	width: 100%;
	height: 220rpx;
	display: block;
}
.expired-mask {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(255,255,255,0.5);
}

.no-pic {
	text-align: center;
	padding: 100rpx 0;
	color: #999;
	font-size: 28rpx;
}

.bottom-bar {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	background: #fff;
	padding: 20rpx 3%;
	border-top: 1px solid #f0f0f0;
	box-shadow: 0 -2px 10rpx rgba(0,0,0,0.05);
	z-index: 100;
}
.btn-save-all {
	width: 100%;
	height: 80rpx;
	line-height: 80rpx;
	border-radius: 40rpx;
	text-align: center;
	color: #fff;
	font-size: 30rpx;
}

/* H5长按保存弹层 */
.h5-preview-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0,0,0,0.7);
	z-index: 999;
	display: flex;
	align-items: center;
	justify-content: center;
}
.h5-preview-body {
	width: 80%;
	background: #fff;
	border-radius: 16rpx;
	padding: 30rpx;
	text-align: center;
}
.h5-preview-tips {
	display: block;
	font-size: 28rpx;
	color: #666;
	margin-bottom: 20rpx;
}
.h5-preview-img {
	width: 100%;
	border-radius: 8rpx;
}
.h5-preview-close {
	margin-top: 20rpx;
	font-size: 28rpx;
	color: #999;
	padding: 10rpx 0;
}
</style>
