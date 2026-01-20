<template>
	<view>
		<view class="page">
			<view class="title">请输入关键词开始你的创作</view>
			<view class="textarea">
				<textarea class="textarea_module" placeholder="多个关键字以逗号拼接,最多输入100字"></textarea>
				<view class="textarea_text">0/100</view>
			</view>
			<view class="try flex-y-center">
				<view class="try_title">试一试：</view>
				<scroll-view :scroll-x="true" class="try_module">
					<view class="try_item try_active">泰迪熊跳舞</view>
					<view class="try_item">泰迪熊跳舞</view>
					<view class="try_item">泰迪熊跳舞</view>
					<view class="try_item">泰迪熊跳舞</view>
					<view class="try_item">泰迪熊跳舞</view>
				</scroll-view>
			</view>
			<view class="title">渲染引擎</view>
			<scroll-view :scroll-x="true" class="render">
				<view class="render_item render_active">泰迪熊跳舞</view>
				<view class="render_item">泰迪熊跳舞</view>
				<view class="render_item">泰迪熊跳舞</view>
				<view class="render_item">泰迪熊跳舞</view>
				<view class="render_item">泰迪熊跳舞</view>
			</scroll-view>
			<view class="title">选择风格</view>
			<scroll-view :scroll-x="true" class="style">
				<view class="style_item">
					<image src="https://img1.baidu.com/it/u=1828704935,3528948129&fm=253&fmt=auto&app=138&f=JPEG?w=357&h=500" mode=""></image>
					<view class="style_title style_active">壁纸兴趣社</view>
				</view>
				<view class="style_item">
					<image src="https://img1.baidu.com/it/u=1828704935,3528948129&fm=253&fmt=auto&app=138&f=JPEG?w=357&h=500" mode=""></image>
					<view class="style_title">壁纸兴趣社</view>
				</view>
				<view class="style_item">
					<image src="https://img1.baidu.com/it/u=1828704935,3528948129&fm=253&fmt=auto&app=138&f=JPEG?w=357&h=500" mode=""></image>
					<view class="style_title">壁纸兴趣社</view>
				</view>
				<view class="style_item">
					<image src="https://img1.baidu.com/it/u=1828704935,3528948129&fm=253&fmt=auto&app=138&f=JPEG?w=357&h=500" mode=""></image>
					<view class="style_title">壁纸兴趣社</view>
				</view>
				<view class="style_item">
					<image src="https://img1.baidu.com/it/u=1828704935,3528948129&fm=253&fmt=auto&app=138&f=JPEG?w=357&h=500" mode=""></image>
					<view class="style_title">壁纸兴趣社</view>
				</view>
			</scroll-view>
			<view class="title">图片比例</view>
			<view class="size flex">
				<view class="size_module">
					<view class="size_item flex-xy-center size_active">
						<view class="size_tag1"></view>
					</view>
					<view class="size_title">1:1</view>
				</view>
				<view class="size_module">
					<view class="size_item flex-xy-center">
						<view class="size_tag2"></view>
					</view>
					<view class="size_title">2:3</view>
				</view>
				<view class="size_module">
					<view class="size_item flex-xy-center">
						<view class="size_tag3"></view>
					</view>
					<view class="size_title">3:2</view>
				</view>
			</view>
			<view class="btn">
				开始绘画<text class="btn_tip">(消费10积分)</text>
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

	.textarea_text {
		position: absolute;
		width: 100%;
		bottom: 30rpx;
		text-align: right;
		font-size: 26rpx;
		color: #999;
		padding: 0 30rpx;
		box-sizing: border-box;
	}

	.try {
		padding: 30rpx 0;
		overflow: hidden;
	}

	.try_title {
		font-size: 26rpx;
		color: #333;
		width: 120rpx;
		flex-shrink: 0;
	}

	.try_module {
		white-space: nowrap;
		width: calc(100% - 120rpx);
	}

	.try_item {
		padding: 10rpx 15rpx;
		font-size: 24rpx;
		color: #333;
		border: 1px solid #f0f0f0;
		border-radius: 4rpx;
		margin: 0 15rpx 0 0;
		display: inline-block;
	}

	.try_item:last-child {
		margin: 0 0 0 0;
	}
	.try_active{
		color: #FF9900;
		border: 1px solid #FF9900;
	}
	
	
	.render {
		white-space: nowrap;
		padding: 30rpx 0;
	}
	
	.render_item {
		padding: 10rpx 15rpx;
		font-size: 24rpx;
		color: #333;
		border: 1px solid #f0f0f0;
		border-radius: 4rpx;
		margin: 0 15rpx 0 0;
		display: inline-block;
	}
	
	.render_item:last-child {
		margin: 0 0 0 0;
	}
	.render_active{
		color: #FF9900;
		border: 1px solid #FF9900;
	}
	
	.style {
		white-space: nowrap;
		padding: 30rpx 0;
	}
	.style_item {
		position: relative;
		width: 170rpx;
		height: 170rpx;
		margin: 0 15rpx 0 0;
		display: inline-block;
	}
	.style_item image{
		width: 100%;
		height: 100%;
	}
	.style_title{
		position: absolute;
		bottom: 0;
		width: 100%;
		box-sizing: border-box;
		height: 35rpx;
		line-height: 35rpx;
		text-align: center;
		padding: 0 10rpx;
		font-size: 22rpx;
		color: #fff;
		background: rgba(0, 0, 0, 0.5);
	}
	.style_item:last-child {
		margin: 0 0 0 0;
	}
	.style_active{
		background: #FF9900;
	}
	
	.size{
		position: relative;
		padding: 30rpx 0;
	}
	.size_module{
		margin: 0 30rpx 0 0;
	}
	.size_item{
		width: 150rpx;
		height: 150rpx;
		border: 2px solid #ddd;
		border-radius: 8rpx;
	}
	.size_tag1{
		width: 120rpx;
		height: 120rpx;
		background: #ddd;
		border-radius: 8rpx;
	}
	.size_tag2{
		width: 84rpx;
		height: 120rpx;
		background: #ddd;
		border-radius: 8rpx;
	}
	.size_tag3{
		height: 84rpx;
		width: 120rpx;
		background: #ddd;
		border-radius: 8rpx;
	}
	.size_title{
		font-size: 26rpx;
		color: #333;
		margin: 20rpx 0 0 0;
		text-align: center;
	}
	.size_active{
		border-color: #FF9900;
	}
	.size_active view{
		background: #FF9900;
	}
	
	.btn{
		display: block;
		height: 90rpx;
		border-radius: 100rpx;
		background: #FF9900;
		color: #fff;
		text-align: center;
		line-height: 90rpx;
		font-size: 28rpx;
	}
	.btn_tip{
		font-size: 22rpx;
	}
</style>