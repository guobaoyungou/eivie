<template>
<view class="container">
	<block v-if="isload">
		<view class="row" v-if="data && data.url_h5">
			<view class="title">链接</view>
			<view>{{data.url_h5}}</view>
			<view class="btn" @tap="goto" :data-url="'copy::'+data.url_h5" :style="{background:t('color1')}">复制</view>
		</view>
		<view class="row" v-if="data && data.qrcode_h5">
			<view class="title">收款码</view>
			<image :src="data.qrcode_h5" style="width:60%" @tap="previewImage" :data-url="poster" mode="widthFix"></image>
			<view class="btn" @tap="savpic" :data-pic="data.qrcode_h5" :style="{background:t('color1')}">下载</view>
		</view>
		
		<view class="row" v-if="data && data.qrcode_wx">
			<view class="title">收款码</view>
			<image :src="data.qrcode_wx" style="width:60%" @tap="previewImage" :data-url="poster" mode="widthFix"></image>
			<view class="btn" @tap="savpic" :data-pic="data.qrcode_wx" :style="{background:t('color1')}">下载</view>
		</view>
		
		<view class="row" v-if="data && data.qrcode_design">
			<view class="title">收款码设计</view>
			<image :src="data.qrcode_design" style="width:60%" @tap="previewImage" :data-url="poster" mode="widthFix"></image>
			<view class="btn" @tap="savpic" :data-pic="data.qrcode_design" :style="{background:t('color1')}">下载</view>
		</view>
		
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
      data: {},
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiAdminMaidan/getPayQrcode', {}, function (res) {
				that.loading = false;
				that.data = res;
				that.loaded();
			});
		},
		savpic:function(e){
	
			var pics = e.currentTarget.dataset.pic;
			console.log(pics);
			if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
				app.error('请长按图片保存');return;
			}
			this.savpic2(pics);
		},
		savpic2:function(pic){
			var that = this;
			app.showLoading('图片保存中');
			uni.downloadFile({
				url: pic,
				success (res) {
					if (res.statusCode === 200) {
						uni.saveImageToPhotosAlbum({
							filePath: res.tempFilePath,
							success:function () {
								app.success('已保存到相册');
							},
							fail:function(failres){
								console.log(failres);
								app.showLoading(false);
								app.error('保存失败');
							}
						})
					}
				},
				fail:function(failres){
					console.log(failres);
					app.showLoading(false);
					app.error('下载失败');
				}
			});
		},
	}
};
</script>
<style>
 .container {width: 100%;padding:30rpx; text-align:center;}
 .row {margin-top:30rpx;padding:20rpx; border-radius:20rpx;word-break: break-all;background-color:#fff;}
 .title{margin:20rpx 0;font-size:32rpx;font-weight: bold;}
 .btn {margin: 16rpx auto 0;width:270rpx;height:70rpx;line-height:70rpx; text-align:center;color:#fff; border-radius:12rpx}
</style>