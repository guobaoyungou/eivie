<template>
	<view>
		<view class="module">
			<view
				:style="{top: y - size / 2 +'px', left: x - size / 2 +'px',height:size+'px',width:size+'px',transform: 'rotate(' + angle + 'deg)'}"
				class="move_module" :class="focus?'move_active':''" @click.passive="focusClick">
				<img :src="url" class="move_image" alt="" @touchstart='siteStart($event)' @touchmove='siteMove($event)'
					@touchend='siteEnd($event)'>
				<view v-if="focus" @touchstart='spinStart($event)' @touchmove='spinMove($event)'
					@touchend='spinEnd($event)' class="move_spin">
					<img src="https://v2d.diandashop.com/static/img/diylight_spin.png" alt="">
				</view>
				<view v-if="focus" @touchstart='sizeStart($event)' @touchmove='sizeMove($event)'
					@touchend='sizeEnd($event)' class="move_size">
					<img src="https://v2d.diandashop.com/static/img/diylight_size.png" alt="">
				</view>
			</view>
		</view>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				x: 200,
				y: 200,

				chaX: 0,
				chaY: 0,
				chaR: 0,

				size: 100,
				url: "https://image.wxx1.com/upload/1/20220518/1e6b0e349bb96e83e3d157b0aa027d58.png",
				angle: 0,
				focus: true
			}
		},
		onLoad: function() {

		},
		methods: {
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
				this.chaR = this.getAngle(centerx, centery, endx, endy) - this.angle;
			},
			spinMove(event) {
				let centerx = this.size / 2 + this.x;
				let centery = this.size / 2 + this.y;
				let endx = event.touches[0].pageX;
				let endy = event.touches[0].pageY;
				let rotate = this.getAngle(centerx, centery, endx, endy) - this.chaR;
				this.angle = rotate;
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
	.module {
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
	}

	.move_module {
		position: absolute;
		height: 100%;
		width: 100%;
		border: 2px dashed rgba(0, 0, 0, 0);
	}

	.move_active {
		border: 2px dashed #e0e0e0;
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
</style>
