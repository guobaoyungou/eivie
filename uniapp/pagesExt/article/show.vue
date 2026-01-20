<template>
<view class="container" v-if="isload">
	<view class="main flex-y-center">
			<image v-if="showtype =='png'" style="width: 100%;" class="img" :src="fujianpic" role="img" mode="widthFix" @tap="previewImage" :data-url="fujianpic">
			</image>
			<view class="play video" v-else-if="showtype =='mp3'">
				<view class="play-left">
				<image :src="pre_url+'/static/img/video_icon.png'" v-show="playshow" :data-url ="fujianpic"  @tap="play"></image>   
			    <image :src="pre_url+'/static/img/play.png'" v-show="!playshow" @tap="pauseaudio"></image> 
			    <text>{{nowtime}}</text>
			  </view>
			  <view class="play-right">
					<slider style="margin-top: 30rpx;" @change="sliderChange"  @changing="sliderChanging" class="slider" block-size="16"  :min="0" :max="time"  :value="currentTime" activeColor="#595959"  />
			  </view>
				<view class="play-end"><text>{{duration}}</text></view>
			</view>			
			<video v-else class="video" id="video" style="z-index: 100;" :autoplay="true" :src="fujianpic" ></video>
		<view class="save" v-if="down_auth =='1'">
			<button class="btn_save" @tap="savevideo"><text v-if="showtype =='mp3'">保存到手机</text> <text v-else>保存至手机相册</text></button>
		</view>	
	</view>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();
var interval = null;
export default {
	data() {
		return {
			pre_url:app.globalData.pre_url,
			opt:{},
			loading:false,
			isload: false,
			menuindex:-1,
			nodata:false,
			pagenum: 1,
			datalist: [],
			fujianpic:'',
			showtype:'',
			picindex:0,
			videoindex:0,
			audioindex:0,
			down_auth:0,
			nowtime:'',
			startTime:'',
			playshow:true, //播放的图片
			stipshow:false, //暂停的图片
			lock: false, // 锁
			status: 1, // 1暂停 2播放
			currentTime: 0,  //当前进度
			duration: '', // 总进度
			innerAudioContext: '',
			seek: false ,
			playJd:0,
			time:'',
		};
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		console.log(this.opt);
		if(this.opt){
			this.isload = true;
			this.fujianpic = decodeURIComponent(this.opt.url);
			this.showtype = this.opt.type;
			this.down_auth = this.opt.auth
			this.innerAudioContext = uni.createInnerAudioContext();
		}
	},
	onUnload: function () {
		clearInterval(interval);
		var that=this
		this.innerAudioContext.stop();
	},
	onHide(){
		this.playshow = false
	},
	methods: {
		openFile(e){
			var that = this;
			var file  = e.currentTarget.dataset.url;
			var type  = e.currentTarget.dataset.type;
			var pngtype = ['png','jpg','gif','jepg','webp'];
			if(pngtype.indexOf(type) !== -1 || type =='mp4'){
				that.showfujian =true;
				that.fujianpic = file;
				that.showtype= type =='mp4'?'mp4':'png';
			}
			const filetype = ['pptx', 'ppt', 'docx', 'doc', 'xlsx', 'xls', 'pdf']
			if(filetype.indexOf(type) !== -1){
				// #ifdef H5
				    window.location.href= file;
				// #endif
				
				// #ifdef MP-WEIXIN
				uni.downloadFile({
					url: file, 
					success: (res) => {
				        var filePath = res.tempFilePath;
						if (res.statusCode === 200) {
							uni.openDocument({
				              filePath: filePath,
				              showMenu: true,
				              success: function (res) {
				                console.log('打开文档成功');
				              }
				            });
						}
					}
				});
				// #endif
			}	
		},
		imageClose: function() {
			this.showfujian = false;
		},
		savevideo:function(video){
			var that = this;
			console.log(that.showtype,'----');
			if(that.showtype =='mp4'){
				if(that.videoindex >0){
					app.showLoading(false);
					app.success('已保存到相册');
					return;
				}
				app.showLoading('视频下载中');
				uni.downloadFile({
					url: that.fujianpic,
					success (res) {
						if (res.statusCode === 200) {
							uni.saveVideoToPhotosAlbum({
								filePath: res.tempFilePath,
								success:function () {
									that.videoindex++;
									app.showLoading(false);
									app.success('视频保存成功');
								},
								fail:function(){
									app.showLoading(false);
									app.error('视频保存失败');
								}
							})
						}
					},
					fail:function(){
						app.showLoading(false);
						app.error('视频下载失败!');
					}
				});
			}else if(that.showtype =='mp3'){
				if(that.audioindex >0){
					app.showLoading(false);
					app.success('已保存到相册');
					return;
				}
				app.showLoading('音频下载中');
				uni.downloadFile({
					url: that.fujianpic,
					success (res) {
						if (res.statusCode === 200) {
							uni.getFileSystemManager().saveFile({
							  tempFilePath: res.tempFilePath,
							  success: function (res) {
								var savedFilePath  = res.savedFilePath;
								app.success('音频保存成功');
							  }
							});
						}else{
							app.error('音频下载失败!');
						}
					},
					fail:function(){
						app.showLoading(false);
						app.error('音频下载失败!');
					}
				});
			}
			else{
				var that = this;
				var picindex = this.picindex;
				if(picindex >0){
					app.showLoading(false);
					app.success('已保存到相册');
					return;
				}
				app.showLoading('文件保存中');
				uni.downloadFile({
					url: that.fujianpic,
					success (res) {
						if (res.statusCode === 200) {
							uni.saveImageToPhotosAlbum({
								filePath: res.tempFilePath,
								success:function () {
									app.showLoading(false);
									that.picindex++;
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
			}
		},
		// 播放
		play(e) {
			// var file  = e.currentTarget.dataset.url;
			// var audiourl = '';
			// uni.downloadFile({
			// 	url: file,
			// 	success (res) {
			// 		audiourl  = res.tempFilePath;
			// 	},
			// 	fail:function(){
			// 		app.showLoading(false);
			// 		app.error('音频下载失败!');
			// 	}
			// });
			// console.log(audiourl,'=====');
			var that=this
			this.playshow=true;
			this.innerAudioContext.autoplay = true;
			this.innerAudioContext.src = that.fujianpic;
			this.innerAudioContext.play();
			this.innerAudioContext.onCanplay(()=> {
				this.innerAudioContext.duration;
				setTimeout(() => {
					that.time = this.innerAudioContext.duration.toFixed(0);
					var min = Math.floor(that.time/60);
					var second = that.time%60
					this.duration = (min>10?min:'0'+min)+':'+(second>10?second:'0'+second);	
				}, 1000)
			})  
			
			this.innerAudioContext.seek(that.startTime)
			this.innerAudioContext.onPlay(() => {
				that.playshow=false;   
			});
			this.innerAudioContext.onPause(() => {
				that.playshow=true;
			});
			this.innerAudioContext.onEnded(() => {
				that.playJd = 100;
				clearInterval(interval);
				that.playshow=true;
			});
			this.innerAudioContext.onTimeUpdate(() => {
				var nowtime = this.innerAudioContext.currentTime.toFixed(0)
				var min = Math.floor(nowtime/60)
				var second = nowtime%60
				that.nowtime = (min>=10?min:'0'+min)+':'+(second>=10?second:'0'+second)
				  //计算进度
				if(that.playJd < 100 && that.innerAudioContext.duration > 0){
					that.playJd = ((nowtime/that.innerAudioContext.duration).toFixed(2))*100;
					if(that.playJd>100) that.playJd=100
				}
				that.currentTime = this.innerAudioContext.currentTime;
			});
		}, 
		// 暂停
		pauseaudio() {
			var that=this
			this.innerAudioContext.pause();
		},
		// 拖动进度条
		sliderChange(data) {
			var that=this;
			that.currentTime = data.detail.value;
			that.innerAudioContext.seek(data.detail.value)
		},
		//拖动中
		sliderChanging(data) {	
			this.currentTime = data.detail.value	
		}
	}
};

</script>
<style>
page{background:#000; }
.main{width: 100%;height: 90%;position: absolute;}
.btn_yl{height: 35rpx;line-height: 33rpx;color: #03a9f4;border: 1px solid #03a9f4;border-radius: 32rpx;padding: 0 15rpx;font-size: 24rpx;}
.save{width: 100%; position: fixed;bottom: 10rpx;padding: 30rpx 0;left: 0;}
.btn_save{ width: 300rpx; height: 60rpx;line-height: 60rpx; background-color: #03a9f4; color: #FFFFFF;border-radius: 32rpx;flex-shrink: 0;padding: 0 50rpx;font-size: 24rpx; font-weight: bold;margin: 0 auto;justify-content: center;}
.video{width:100%;}
.image{width: 100%;}
.play{ background-color:rgba(255,255,255,0.5);width: 100%; height: 100rpx; }
.play-left text{ margin-top: 1px; color: #fff;  font-size: 13px; line-height: 90rpx;  position: absolute; left: 13%;    }
.play-end text{ margin-top: 1px; color: #fff;  font-size: 13px; line-height: 90rpx; right: 5%;  position: absolute;      }
.play image{   width: 26px; height: 26px; margin: 25rpx 4px 0 20rpx;float: left;  }
.play-left{width: 170rpx;height: 100rpx;    float: left;  border-radius: 38px;  }
.play-right{ width: 66%;  float: left; height: 100rpx; position: relative; }
.slider{  width: 366rpx; position: relative; margin-top: 42rpx;  color: black; float: left;}
</style>