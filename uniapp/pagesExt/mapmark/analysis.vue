<template>
	<view>
		<image class="banner"
			src="https://img2.baidu.com/it/u=1901263674,4189773948&fm=253&fmt=auto&app=138&f=JPG?w=1250&h=500"
			mode="widthFix"></image>
		<view class="page">
			<view class="title">今日播报</view>
			<view class="textarea">
				<textarea class="textarea_module" placeholder="请粘贴分享链接"></textarea>
			</view>
			<view class="btn">去水印解析</view>
			<view class="sustain">
				<view class="sustain_title">支持平台</view>
				<view class="sustain_module">
					<view v-for="(item,index) in 10" :key="index">
						<image src="../static/images/card_m0.png" mode="widthFix" class="sustain_image"></image>
						<view class="sustain_text">抖音</view>
					</view>
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

			};
		},

		onLoad: function() {

		},
		methods: {

		}
	};
</script>
<style>
	page {
		background: #fff;
	}

	.banner {
		position: relative;
		width: 100%;
		display: block;
	}

	.page {
		padding: 30rpx;
	}

	.title {
		font-size: 32rpx;
		font-weight: bold;
	}

	.textarea {
		position: relative;
	}

	.textarea_module {
		position: relative;
		padding: 30rpx;
		margin: 20rpx 0 0 0;
		display: block;
		border-radius: 20rpx;
		background: #f0f0f0;
		height: 260rpx;
		font-size: 28rpx;
		width: 100%;
		box-sizing: border-box;
	}

	.btn {
		display: block;
		height: 90rpx;
		border-radius: 100rpx;
		background: #FF9900;
		color: #fff;
		text-align: center;
		line-height: 90rpx;
		font-size: 28rpx;
		margin-top: 30rpx;
	}

	.sustain {
		position: relative;
		margin-top: 50rpx;
	}

	.sustain_title {
		font-size: 28rpx;
		color: #333;
		font-weight: bold;
	}

	.sustain_module {
		position: relative;
		display: grid;
		margin: 15rpx 0 0 0;
		grid-template-columns: repeat(5, 1fr);
		grid-column-gap: 10rpx;
		grid-row-gap: 10rpx;
	}

	.sustain_image {
		width: 90rpx;
		height: 90rpx;
		display: block;
		margin: 0 auto;
	}

	.sustain_text {
		font-size: 24rpx;
		color: #333;
		text-align: center;
		margin: 10rpx 0 0 0;
	}
</style>