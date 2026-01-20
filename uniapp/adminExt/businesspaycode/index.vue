<template>
<view class="container">
	<block v-if="isload">
		<view class="row" v-if="data">
			<view class="title">{{data.name}}</view>
			<image :src="data.maidan_paycode_url" style="width:60%" @tap="previewImage" :data-url="data.maidan_paycode_url" mode="widthFix"></image>
			<view class="edit-paycode" @tap="goto" :data-url="'set?bid='+data.id">编辑二维码</view>
			<!-- <view class="btn" @tap="savpic" :data-pic="data.qrcode_h5" :style="{background:t('color1')}">下载</view> -->
		</view>
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
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
			app.get('ApiAdminMaidan/businessPayCode', {}, function (res) {
				that.loading = false;
				that.data = res.data;
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
 .edit-paycode{font-size:26rpx;padding:20rpx 0;text-decoration: underline;color: #666;}
</style>