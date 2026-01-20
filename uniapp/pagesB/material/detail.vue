<template>
	<view class="container">
		<view style="width:100%;height: 100%;">
			<view class="images-box" v-if="detail.ctype == 0">
				<image :src="detail.url" :show-menu-by-longpress="true" mode="widthFix" />
			</view>
			<view v-else-if="detail.ctype == 1">
				 <video class="video" id="video" style="height: 88vh;" :autoplay="true" src="https://image.wxx1.com/upload/1/20210826/a8ff20404b7ce6762ec83a450ea247ad.mp4" :show-loading="true" @pause="pause"></video>
			</view>
		</view>
		<view class="bottom-share">
			<view class="share-box">
				<image class="img" @tap="download" :src="pre_url+'/static/img/download.png'"/>
				<text>保存{{detail.ctype == 1 ? '视频' : '海报'}}</text>
			</view>
			<view class="share-box" @tap="shareapp" v-if="getplatform() == 'app'">
				<image class="img" :src="pre_url+'/static/img/weixin.png'"/>
				<text>分享好友</text>
			</view>
			<view class="share-box" @tap="shareapp" v-else-if="getplatform() == 'mp'">
				<image class="img" :src="pre_url+'/static/img/weixin.png'"/>
				<text>分享好友</text>
			</view>
			<button class="share-box remove-button" open-type="share" v-else-if="getplatform() != 'h5'">
				<image class="img" :src="pre_url+'/static/img/weixin.png'"/>
				<text>分享给好友</text>
			</button>
		</view>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt:{},
				loading:false,
				pre_url:app.globalData.pre_url,
				detail:[]
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
				if(that.detail.ctype == 1){
					// H5下载视频的代码
					const a = document.createElement('a');
					a.href = that.detail.url;
					a.download = that.detail.name ? that.detail.name : 'video.mp4'; // 设置下载文件名
					document.body.appendChild(a);
					a.click();
					document.body.removeChild(a);
					return;
				}else{
					if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
						app.error('请长按图片保存');
						return;
					}
				}
			},
			payvideo: function () {
				this.isplay = 1;
				uni.createVideoContext('video').play();
			},
			parsevideo: function () {
				this.isplay = 0;
				uni.createVideoContext('video').stop();
			},
			pause:function(){
				//将暂停播放时间请求
				var that = this
				var id = that.opt.id ? that.opt.id : '';
				that.addstudy();
			},
		}
	}
</script>

<style>
.container{display:flex;flex-direction:column;align-items:center;background-color:#f5f5f5;width:100%;height:100vh}
.images-box{width:100%}
.images-box image{width:100%;height:100vh;object-fit:contain}
.bottom-share{display:flex;justify-content:space-around;width:100%}
.share-box{display:flex;flex-direction:column;align-items:center;margin-top: 40rpx;}
.share-box .img{width:60rpx;height:60rpx}
.share-box text{margin-top:5px;font-size:14px}
.video{width: 100%;height: 100vh;}
.remove-button {
  border: none; 
  background-color: transparent;
  padding: 0;
  margin: 40rpx;
  line-height: normal;
  font-size: inherit;
  color: inherit;
  text-align: inherit;
}
</style>
