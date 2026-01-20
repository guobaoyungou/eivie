<template>
	<view>
		<camera device-position="back" flash="off" class="camera"></camera>
		<view class="module">
			<view :style="{top: y - size / 2 +'px',left:x - size / 2 +'px',height:size+'px',width:size+'px',transform: 'rotate(' + rotate + 'deg)'}" class="move_module" :class="focus?'move_active':''" @click.passive="focusClick">
				<img :src="url" class="move_image" alt="" @touchstart='siteStart($event)' @touchmove='siteMove($event)' @touchend='siteEnd($event)'>
				<view v-if="focus" @touchstart='spinStart($event)' @touchmove='spinMove($event)' @touchend='spinEnd($event)' class="move_spin">
					<img src="https://v2d.diandashop.com/static/img/diylight_spin.png" alt="">
				</view>
				<view v-if="focus" @touchstart='sizeStart($event)' @touchmove='sizeMove($event)' @touchend='sizeEnd($event)' class="move_size">
					<img src="https://v2d.diandashop.com/static/img/diylight_size.png" alt="">
				</view>
			</view>
			<view class="module_btn" @click="takePhoto"></view>
		</view>
		<canvas class="canvasDraw" v-if="canvasState" canvas-id="myCanvas" id="myCanvas"></canvas>
		<wxxieyi></wxxieyi>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				x: 200,
				y: 300,
				
				chaX: 0,
				chaY: 0,
				chaR: 0,
				
				size: 100,
				url: "https://image.wxx1.com/upload/1/20220518/1e6b0e349bb96e83e3d157b0aa027d58.png",
				rotate: 0,
				
				focus: true,
				
				canvasState:false
			}
		},
		onLoad: function() {
			uni.getImageInfo({
				src: this.url,
				success: (image)=> {
					this.url = image.path;
				}
			});
		},
		methods: {
			takePhoto() {
				this.canvasState = true;
				uni.showLoading({
					title: '加载中',
					mask: true
				});
				const photo = uni.createCameraContext();
				photo.takePhoto({
					quality: 'high',
					success: (ret) => {
						uni.getSystemInfo({
							success: (res) => {
								let heightPage = res.screenHeight || res.windowHeight;
								let widthPage = res.screenWidth || res.windowWidth;
								var ctx = uni.createCanvasContext('myCanvas');
								ctx.drawImage(ret.tempImagePath, 0, 0, widthPage, heightPage);
								let itemSize = this.size;
								let rotateData = this.rotate % 360;
								ctx.translate(this.x, this.y);
								ctx.rotate(rotateData * Math.PI / 180);
								ctx.drawImage(this.url, -itemSize / 2, -itemSize / 2, itemSize, itemSize);
								ctx.draw(false, () => {
									uni.canvasToTempFilePath({
										canvasId: 'myCanvas',
										x: 0,
										y: 0,
										width: widthPage,
										height: heightPage,
										destWidth: widthPage * 3,
										destHeight: heightPage * 3,
										success: (
											res
										) => {
											this.canvasState = false;
											uni.hideLoading();
											uni.getImageInfo({
												src: res.tempFilePath,
												success: (image) => {
													uni.saveImageToPhotosAlbum({
														filePath: image.path,
														success: () => {
															uni.showModal({
																title: '保存成功',
																content: '图片已成功保存到相册',
																showCancel: false
															});
														}
													});
												}
											});
										}
									}, this)
								})
							}
						});
					}
				});
			},
			focusClick() {
				if (this.focus) {
					this.focus = false;
				} else {
					this.focus = true;
				}
			},
			siteStart(event) {
				var tranX = event.touches[0].pageX - this.x;
				var tranY = event.touches[0].pageY - this.y;
			
				this.chaX = tranX;
				this.chaY = tranY;
			},
			siteMove(event) {
				this.focus = true;
				this.x = event.touches[0].clientX - this.chaX;
				this.y = event.touches[0].clientY - this.chaY;
			},
			siteEnd(event) {},
			sizeStart(event) {},
			sizeMove(event) {
				let sizeX = this.x;
				let sizeY = this.y;
			
				let pageX = event.touches[0].clientX;
				let pageY = event.touches[0].clientY;
			
				let cutX = pageX - sizeX;
				let cutY = pageY - sizeY;
			
				if (cutX > 0 && cutY > 0) {
					this.size = (event.touches[0].clientX - this.x) + (event.touches[0].clientY - this.y)
				}
				if (cutX < 0 && cutY < 0) {
					this.size = (this.x - event.touches[0].clientX) + (this.y - event.touches[0].clientY)
				}
				if (cutX < 0 && cutY > 0) {
					this.size = (this.x - event.touches[0].clientX) + (event.touches[0].clientY - this.y)
				}
				if (cutX > 0 && cutY < 0) {
					this.size = (event.touches[0].clientX - this.x) + (this.y - event.touches[0].clientY)
				}
			},
			sizeEnd(event) {},
			spinStart(event) {
				let centerx = this.size / 2 + this.x;
				let centery = this.size / 2 + this.y;
				let endx = event.touches[0].pageX;
				let endy = event.touches[0].pageY;
				this.chaR = this.getAngle(centerx, centery, endx, endy) - this.rotate;
			},
			spinMove(event) {
				let centerx = this.size / 2 + this.x;
				let centery = this.size / 2 + this.y;
				let endx = event.touches[0].pageX;
				let endy = event.touches[0].pageY;
				let rotate = this.getAngle(centerx, centery, endx, endy) - this.chaR;
				this.rotate = rotate;
			},
			spinEnd(event) {},
			getAngle(centerx, centery, endx, endy) {
				var diff_x = endx - centerx;
				var diff_y = endy - centery;
				var c = 360 * Math.atan2(diff_y, diff_x) / (2 * Math.PI);
				c = c <= -90 ? (360 + c) : c;
				return c + 90;
			}
		}
	}
</script>

<style>
	.camera {
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
	}

	.module {
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
	}

	.module_btn {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 100rpx;
		height: 70rpx;
		width: 70rpx;
		border: 15rpx solid #e0e0e0;
		border-radius: 100rpx;
		background: #fff;
		margin: 0 auto;
	}

	.move_module {
		position: absolute;
		height: 100%;
		width: 100%;
		border: 2px dashed rgba(0, 0, 0, 0);
	}

	.move_active {
		border: 2px dashed #f0f0f0;
	}


	.move_image {
		position: absolute;
		height: 100%;
		width: 100%;
	}

	.move_spin {
		position: absolute;
		height: 40rpx;
		width: 40rpx;
		top: -20rpx;
		right: -20rpx;
		border-radius: 100rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #666;
	}

	.move_spin img {
		height: 30rpx;
		width: 30rpx;
		display: block;
	}

	.move_size {
		position: absolute;
		height: 40rpx;
		width: 40rpx;
		right: -20rpx;
		bottom: -20rpx;
		border-radius: 100rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		background: #666;
	}

	.move_size img {
		height: 30rpx;
		width: 30rpx;
		transform: rotate(90deg);
		display: block;
	}
	
	.canvasDraw {
		position: fixed;
		width: 100%;
		height: 100%;
		top: 0;
	}
</style>
