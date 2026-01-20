<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">视频标题</view>
					<view class="f2">{{info.title}}</view>
				</view>
				<view class="form-item">
					<view class="f1">视频封面</view>
					<view class="f2">
						<view class="layui-imgbox">
							<view class="layui-imgbox-img"><image :src="info.cover" @tap="previewImage" :data-url="info.cover" mode="widthFix"></image></view>
						</view>
					</view>
				</view>
				<view class="form-item">
					<view class="f1">视频链接</view>
					<view class="f2">
					<view @click="copyUrl(info.url)"> 复制视频链接</view>
					</view>
				</view>
				<view class="form-item">
						 <video id="myVideo" :src="info.url" @error="videoErrorCallback" controls></video>
				</view>
				<view class="form-item">
					<view class="f1">
					<view @click="downVideo(info.url)">点击下载视频</view>
					</view>
				</view>
			</view>

			<view style="height:50rpx"></view>
		</form>
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
		isload:false,
		loading:false,
		pre_url:app.globalData.pre_url,
		info:{},
    };
  },
 onReady: function(res) {
        // #ifndef MP-ALIPAY
        this.videoContext = uni.createVideoContext('myVideo')
        // #endif
    },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiVideoSpider/getDetail',{id:that.opt.id}, function (res) {
				console.log(res);
				if(res.status==0){
					uni.showModal({
						content:res.msg,
						showCancel: true,
						complete:function (res){
							uni.navigateTo({
								url:'/pagesExt/videospider/analysis'
							})
						}
					})

				}else{
					that.loading = false;
					that.info = res.data;
					that.loaded();
				}

			});
		},

		videoErrorCallback: function(e) {
			uni.showModal({
				content: '视频加载错误！'+e.target.errMsg,
				showCancel: false
			})
		},
		getRandomColor: function() {
			const rgb = []
			for (let i = 0; i < 3; ++i) {
				let color = Math.floor(Math.random() * 256).toString(16)
				color = color.length == 1 ? '0' + color : color
				rgb.push(color)
			}
			return '#' + rgb.join('')
		},
		copyUrl(url) {
			uni.showModal({
				content: url,
				confirmText: '复制内容',
				success: () => {
					uni.setClipboardData({
						data: url,
						success: function() {
							uni.hideToast({
								title: '复制成功',
								duration: 2000,
								icon: 'none'
							});
						},
						fail: function(err) {
							uni.showToast({
								title: '复制失败',
								duration: 2000,
								icon: 'none'
							});
						}
					});
				}
			});
		},
		downVideo(url){
			uni.showLoading({
				title: '下载中'
			});
			//获取用户的当前设置。获取相册权限
				uni.getSetting({
					success: (res) => {
						//如果没有相册权限
						if (!res.authSetting["scope.writePhotosAlbum"]) {
							//向用户发起授权请求
							uni.authorize({
								scope: "scope.writePhotosAlbum",
								success: () => {
									//授权成功保存图片到系统相册
									//开始下载
									uni.downloadFile({
										url: url, //仅为示例，并非真实的资源
										//filePath:'/video',
										success: (res) => {
											if (res.statusCode === 200) {
												uni.saveVideoToPhotosAlbum({
													filePath: res.tempFilePath, //临时路径
													success: function(res) {
														console.log(res);
														uni.hideLoading();
														uni.showToast({
															icon: 'none',
															mask: true,
															title: '文件已保存', //保存路径
															duration: 3000,
														});
													},
													fail(err) {
														uni.hideLoading();
														uni.showToast({
															icon: 'none',
															mask: true,
															title: '下载失败', //保存路径
															duration: 3000,
														});

													  console.log(err);
													}
												});

											}
										},fail(err) {
											uni.hideLoading();
											uni.showToast({
												icon: 'none',
												mask: true,
												title: '下载失败', //保存路径
												duration: 3000,
											});
										  console.log(err);
										}
									});
								},
								//授权失败
								fail: () => {
									uni.hideLoading();
									uni.showModal({
										title: "您已拒绝获取相册权限",
										content: "是否进入权限管理，调整授权？",
										success: (res) => {
											if (res.confirm) {
												//调起客户端小程序设置界面，返回用户设置的操作结果。（重新让用户授权）
												uni.openSetting({
													success: (res) => {
													console.log(res.authSetting);
													},
												});
											} else if (res.cancel) {
												return uni.showToast({
													title: "已取消！",
												});
											}
										},
									});
								},
							});
						} else {
							//如果已有相册权限，直接保存图片到系统相册
							//开始下载
							uni.downloadFile({
								url: url, //仅为示例，并非真实的资源
								//filePath:'/video',
								success: (res) => {
									if (res.statusCode === 200) {
										uni.saveVideoToPhotosAlbum({
											filePath: res.tempFilePath, //临时路径
											success: function(res) {
												console.log(res);
												uni.hideLoading();
												uni.showToast({
													icon: 'none',
													mask: true,
													title: '文件已保存', //保存路径
													duration: 3000,
												});
											},
											fail(err) {
											  console.log(err);
											}
										});

									}
								},fail(err) {
								  console.log(err);
								}
							});
						}
					},
					fail: (res) => {},
				});

		}
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.ggtitle{height:60rpx;line-height:60rpx;color:#111;font-weight:bold;font-size:26rpx;display:flex;border-bottom:1px solid #f4f4f4}
.ggtitle .t1{width:200rpx;}
.ggcontent{line-height:60rpx;margin-top:10rpx;color:#111;font-size:26rpx;display:flex}
.ggcontent .t1{width:200rpx;display:flex;align-items:center;flex-shrink:0}
.ggcontent .t1 .edit{width:40rpx;height:40rpx}
.ggcontent .t2{display:flex;flex-wrap:wrap;align-items:center}
.ggcontent .ggname{background:#f55;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-bottom:10rpx;font-size:24rpx;position:relative}
.ggcontent .ggname .close{position:absolute;top:-14rpx;right:-14rpx;background:#fff;height:28rpx;width:28rpx;border-radius:14rpx}
.ggcontent .ggnameadd{background:#ccc;font-size:36rpx;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-left:10rpx;position:relative}
.ggcontent .ggadd{font-size:26rpx;color:#558}

.ggbox{line-height:50rpx;}


.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}


.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

.freightitem{width:100%;height:60rpx;display:flex;align-items:center;margin-left:40rpx}
.freightitem .f1{color:#666;flex:1}

.detailop{display:flex;line-height:60rpx}
.detailop .btn{border:1px solid #ccc;margin-right:10rpx;padding:0 16rpx;color:#222;border-radius:10rpx}
.detaildp{position:relative;line-height:50rpx}
.detaildp .op{width:100%;display:flex;justify-content:flex-end;font-size:24rpx;height:60rpx;line-height:60rpx;margin-top:10rpx}
.detaildp .op .btn{background:rgba(0,0,0,0.4);margin-right:10rpx;padding:0 10rpx;color:#fff}
.detaildp .detailbox{border:2px dashed #00a0e9}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}
.btn2{margin-left:20rpx; margin-top: 10rpx;max-width:2000rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 20rpx;}

</style>
