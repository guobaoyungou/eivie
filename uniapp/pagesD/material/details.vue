<template>
	<block v-if="!loading">
	  <view class="container">
			<view class="poster-title">
				<text>{{detail.name}}</text>
			</view>

			<view class="poster-content">
				<image :src="detail.url" mode="widthFix" class="poster-image"></image>
			</view>

			<view class="footer-bar">
				<view class="footer-left">
					<button class="footer-btn footer-button" open-type="share" v-if="getplatform() != 'h5'">
						<image :src="pre_url+'/static/img/lt_share.png'" class="footer-icon filter"></image>
						<text>分享</text>
					</button>
					<view class="footer-btn" @click="collect">
						<image :src="pre_url+'/static/img/material/collect_active.png'" class="footer-icon" v-if="is_collected == 1"></image>
						<image :src="pre_url+'/static/img/material/collect.png'" class="footer-icon" v-else></image>
						<text>收藏</text>
					</view>
				</view>
				
				<view class="footer-right">
					<button class="save-btn" @click="download">保存到相册</button>
				</view>
			</view>
	  </view>
	</block>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt:{},
				loading:true,
				pre_url:app.globalData.pre_url,
				detail:[],
				isplay: 0,
        is_collected: 0
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		methods: {
			getdata:function(){
				var that = this;
				var id = that.opt.id;
				that.loading = true;
				app.get('ApiMaterial/detail', {id: id}, function (res) {
					that.loading = false;
					if (res.status == 1){
						that.detail = res.data;
            that.is_collected = res.data.is_collect || 0;
            console.log(that.is_collected);
					} else {
						return app.alert(res.msg);
					}
				});
			},
			download:function(){
				// #ifdef MP-WEIXIN
				this.downloadwx();
				// #endif
								
				// #ifndef MP
				this.downloadH5();
				// #endif
			},
			downloadwx:function(){
				var that = this;
				app.showLoading('保存中');
				uni.downloadFile({
					url: that.detail.url,
					success (res) {
						if (res.statusCode === 200) {
							uni.saveImageToPhotosAlbum({
								filePath: res.tempFilePath,
								success:function () {
									app.success('保存成功');
								},
								fail:function(){
									app.showLoading(false);
									app.error('保存失败');
								}
							})
						}
					},
					fail:function(){
						app.showLoading(false);
						app.error('下载失败');
					}
				});
			},
			downloadH5:function(){
				var that = this;
				if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
					app.error('请长按图片保存');
					return;
				}
			},
			collect: function() {
				var that = this;
				app.post('ApiMaterial/collect', {id: that.opt.id}, function(res) {
					if(res.status == 1) {
						that.is_collected = res.data;
						app.success(res.msg);
					} else {
						app.error(res.msg);
					}
				});
			}
		}
	}
</script>

<style>
	.poster-title{font-size:28rpx;text-align:center;padding:32rpx 0;color: #333;}
	.poster-content{margin-top:30rpx;display:flex;justify-content:center}
	.poster-image{max-width:100%;}
	.video-container{width:100%}
	.video-player{width:100%;height:400rpx}
	.footer-bar{position:fixed;bottom:0;left:0;right:0;height:100rpx;background-color:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 30rpx;box-shadow:0 -2rpx 10rpx rgba(0,0,0,0.1)}
	.footer-left{display:flex;align-items:center;flex:1}
	.footer-right{width:60%}
	.footer-btn{display:flex;flex-direction:column;align-items:center;margin-left:45rpx;font-size:24rpx;color:#666;justify-content:center}
	.footer-icon{width:40rpx;height:40rpx;margin-bottom:10rpx;}
	.filter{filter: invert(54%) saturate(0%) brightness(100%);}
	.save-btn{background-color:#000;color:#fff;padding:0 40rpx;height:70rpx;line-height:70rpx;font-size:28rpx;margin:0;}
	.save-btn::after{border:none}
	.footer-button{ display: flex;
    flex-direction: column;
    align-items: center;
    margin-right: 40rpx;
    font-size: 24rpx;
    color: #666;
    background: none;
    padding: 0;
    line-height: 1;
    border: none;
    outline: none;
    position: relative;}
	.footer-button::after {
    border: none;
	}
	.footer-btn > image,
	.footer-btn > text {
	    display: block;
	}
</style>