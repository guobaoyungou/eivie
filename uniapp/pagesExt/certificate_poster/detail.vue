<template>
<view class="container">
	<block v-if="isload">
		<view style="padding-top:50rpx;padding-left:20rpx;padding-right:20rpx;display:flex;align-items:center">
			<image :src="poster" class="sjew-img" @tap="previewImage" :data-url="poster" mode="widthFix"></image>
		</view>
		
		<view v-if="info.explain" style="padding:40rpx 20rpx">
			<parse :content="info.explain"/>
		</view>
		<view class="sjew-he"></view>
		<view class="sjew-bottom" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
			<view class="sjew-bottom-content">
				<view class="sjew-bot-a1" :style="{background:t('color2')}" @tap="saveImage">保存证书</view>
				<view class="sjew-bot-a2" :style="{background:t('color1')}" @tap="previewImage" :data-url="poster">查看{{certificate_text}}</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
			menuindex:-1,
			
			tel:'',
			poster:'',
			info:{},
      shopset: [],
			certificate_text:'成绩'
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.tel) this.tel = this.opt.tel;
		if(this.opt && this.opt.posterid) this.posterid = this.opt.posterid;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			var posterid = that.posterid;
			var tel = that.tel;
			that.loading = true;
			app.get('ApiCertificatePoster/detail', {posterid:posterid,tel:tel}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
				}
				that.poster = res.poster;
				that.info = res.info;
				if(res.set && res.set.certificate_text){
					uni.setNavigationBarTitle({
						title: '查看'+res.set.certificate_text
					});
					that.certificate_text = res.set.certificate_text;
				}else{
					uni.setNavigationBarTitle({
						title: '查看成绩'
					});
				}
				that.loaded();
			});
		},
		saveImage:function(){
			var that = this;
			if(app.globalData.platform == 'mp' || app.globalData.platform == 'h5'){
				app.error('请长按图片保存');return;
			}
			var pic = that.poster;
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
  }
};
</script>
<style>
.sjew-img{ width:100%; display: block;}
.sjew-box{ width:100%; background:#FFF; margin-bottom:120rpx;padding-bottom:10px;margin-top:20rpx}
.sjew-tle{ width:100%;padding:0 2%; border-bottom:1px #E6E6E6 solid;  height:100rpx;line-height:100rpx; color:#111;}
.sjew-ic1{width:46rpx;height:46rpx;display: block;margin-right:10rpx;}
.sjew-sp1{ width:100%; padding:0 20rpx; color:#666; margin-top:20rpx; }
.sjew-sp2{ width:78%; color:#999; margin-top:20rpx; }
.sjew-sp3{ width:96%; margin:20rpx 2%;background:#fe924a; padding:16rpx 2%; color:#fff;  box-sizing: border-box;}

.sjew-bottom{ width:94%; position:fixed; bottom:0px;height:110rpx;line-height:110rpx;border-top:1px #ececec solid;background:#fff;display:flex;padding:15rpx 3%;box-sizing:content-box}
.sjew-bottom-content{height:80rpx;line-height:80rpx;width:100%;border-radius:50rpx;display:flex;border-radius:45rpx;overflow:hidden}
.sjew-bot-a1{flex:1;height:80rpx;line-height:80rpx;text-align:center;color:#fff;}
.sjew-bot-a2{flex:1;height:80rpx;line-height:80rpx;text-align:center;color:#fff;}
.sjew-he{width: 100%;height:120rpx;}

.arrowleft{transform: rotateY(180deg);background:rgba(0,0,0,0.3);color:#fff;border-radius:50%;width:50rpx;height:50rpx;line-height:50rpx;text-align:center;flex-shrink:0;margin-right:10rpx}
.arrowright{background:rgba(0,0,0,0.3);color:#fff;border-radius:50%;width:50rpx;height:50rpx;line-height:50rpx;text-align:center;flex-shrink:0;margin-left:10rpx}

</style>