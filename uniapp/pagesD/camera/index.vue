<template>
	<view class="container">
		<uni-image-upload
			v-model="images"
			:maxCount="1"
			:enableCamera="true"
			:enableAlbum="true"
			:enableChat="true"
			:cameraPosition="cameraPosition"
			:params="params"
			@upload-success="onUploadSuccess"
			@upload-fail="onUploadFail"
			@delete="onImageDelete"
		></uni-image-upload>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				images: [],
				cameraPosition: 'back',
				params: {
					bgcolor: '#ffffff',
					margin_x: 0,
					margin_y: 0,
					padding_x: 10,
					padding_y: 10,
					device_position: 'back',
					quality: 'high'
				}
			};
		},
		onLoad(opt) {
			this.opt = app.getopts(opt);
			if (this.opt.quality) {
				this.params.quality = this.opt.quality;
			}
			if (this.opt.device_position) {
				this.cameraPosition = this.opt.device_position;
				this.params.device_position = this.opt.device_position;
			}
		},
		methods: {
			/**
			 * 上传成功 - 弹出保存到相册确认
			 */
			onUploadSuccess: function(e) {
				var that = this;
				console.log('上传成功', e);
				if (e.url) {
					uni.showModal({
						title: '上传成功',
						content: '是否保存图片到相册？',
						success: function(res) {
							if (res.confirm) {
								that.saveToAlbum(e.url);
							}
						}
					});
				}
			},

			/**
			 * 保存到相册
			 */
			saveToAlbum: function(imageUrl) {
				// 先下载图片再保存
				uni.downloadFile({
					url: imageUrl,
					success: function(downloadRes) {
						if (downloadRes.statusCode === 200) {
							uni.saveImageToPhotosAlbum({
								filePath: downloadRes.tempFilePath,
								success: function() {
									uni.showToast({ title: '已保存到相册', icon: 'success' });
								},
								fail: function(err) {
									var errMsg = err.errMsg || '';
									if (errMsg.indexOf('auth') > -1) {
										uni.showModal({
											title: '提示',
											content: '需要授权保存图片到相册',
											success: function(res) {
												if (res.confirm) {
													uni.openSetting();
												}
											}
										});
									} else {
										uni.showToast({ title: '保存失败', icon: 'none' });
									}
								}
							});
						}
					},
					fail: function() {
						uni.showToast({ title: '下载图片失败', icon: 'none' });
					}
				});
			},

			/**
			 * 上传失败
			 */
			onUploadFail: function(e) {
				console.log('上传失败', e);
			},

			/**
			 * 删除图片
			 */
			onImageDelete: function(e) {
				console.log('删除图片', e);
			}
		}
	}
</script>

<style>
	.container {
		min-height: 100vh;
		background-color: #f5f5f5;
		padding: 20rpx;
	}
</style>
