<template>
<view>
	<block v-if="guanggaotype != 3 && (guanggaopic || param.unitid) && guanggaostatus=='1' && hasLaunchedad">
		<view class="advert flex-xy-center" :class="param.ggcover=='1'?'advert_cover':''" @touchmove.stop.prevent=" ">
			<view class="advert_module">
        <!-- 图片链接 -->
        <block v-if="guanggaotype=='1'">
          <image class="advert_close_img" @tap="guanggaoClick" :src="pre_url+'/static/img/close2.png'" v-if="guanggaotype=='1' && param.ggcover=='1' && param.ggskip=='1' && param.skiptype!=2" alt=""/>
          <image :src="guanggaopic" @tap="goto" :data-url="guanggaourl" :mode="param.ggcover=='1'?'acceptFill':'widthFix'" class="advert_poster" alt=""/>
        </block>
        <!-- 小程序广告 -->
        <block v-if="guanggaotype=='4'"><dp-wxad :params="param"></dp-wxad></block>
				<view @tap="guanggaoClick" class="advert_close advert_close_bottom flex-xy-center" v-if="(guanggaotype=='1' || guanggaotype=='4') && param.ggcover!='1' && param.ggskip=='1'">
				<!-- <view @tap="guanggaoClick" class="advert_close advert_close_bottom flex-xy-center" v-if="param.ggskip=='1'"> -->
					<image :src="pre_url+'/static/img/close2.png'" alt=""/>
				</view>
			</view>
		</view>
    <!-- 视频 -->
		<view class="advert_video" :class="param.ggcover=='1'?'advert_cover':''" v-if="guanggaotype=='2'">
			<text v-if="guanggaotype=='2' && param.ggcover=='1' && param.ggskip=='1' && param.skiptype!=2" class="advert_close_txt" @tap="guanggaoClick">跳过</text>
			<video style="max-width: 100%; height: 850rpx;" class="dp-guanggao-video" :style="{height:windowHeight+'px',width:'100%'}" :src="guanggaopic" :show-mute-btn="true" :play-btn-position="'center'"
			:object-fit="param.ggcover==1?'cover':''" :controls="false" :autoplay="true" :loop="false" :show-center-play-btn="cpbtn" @ended="playend"></video>
			<view @tap="guanggaoClick" class="advert_close advert_close_top flex-xy-center" v-if="isend">
				<image :src="pre_url+'/static/img/close2.png'" alt=""/>
			</view>
		</view>
		<view @tap="guanggaoClick" class="dp-bottom-close-btn" v-if="(param.showgg=='2' || (param.showgg=='1' && param.ggcover=='1')) && param.ggskip=='1' &&  param.skiptype==2">
			<view class="dp-ggskip-btn" >跳过</view>
		</view>
	</block>
	<!-- #ifdef APP-PLUS -->
	<block v-if='guanggaotype == 3'>
		<ad-interstitial ref="adInterstitial" :adpid="param.adpid" :loadnext="false" v-slot:default="{loading, error}" @load="onadload" @close="onadclose" @error="onaderror">
		  <view v-if="error">{{error}}</view>
		</ad-interstitial>
	</block>
	<!-- #endif -->
</view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
				guanggaostatus:'1',
				windowHeight:560,
				isend:false,
				cpbtn:false,
        hasLaunchedad:false,
				pre_url:app.globalData.pre_url,
				errMsg:''
			}
		},
		props: {
			guanggaourl:"",
			guanggaopic:"",
			guanggaotype:{default:'1'},
			param:{ggcover:0,ggskip:0,skiptype:1,cishu:0}

		},
		mounted:function(){

			var sysinfo = uni.getSystemInfoSync();
			this.windowHeight = sysinfo.windowHeight;
			if(app.globalData.platform=='h5' || app.globalData.platform=='mp'){
				this.cpbtn = true
			}


      //如果开启了首次启动显示
      if(this.param.cishu == 0){
        const isFirstLaunch = uni.getStorageSync('hasLaunched');
        if (!isFirstLaunch) {

          // 是首次启动，显示广告
          this.hasLaunchedad = true;
					// #ifdef APP-PLUS
					if(this.guanggaotype == 3){
						this.showAd();
					}
					// #endif
          // 设置已启动标识
          uni.setStorageSync('hasLaunched', true);
        }
      } else {
				// #ifdef APP-PLUS
				if(this.guanggaotype == 3){
					this.showAd();
				}
				// #endif
        this.hasLaunchedad = true;
        uni.setStorageSync('hasLaunched', false);
        uni.setStorageSync('guanggacishu', 0);
      }
      this.autoclose();//播放时长，过时关闭
		},


		methods:{

			guanggaoClick(){
        this.guanggaostatus='0'
			},


			playend:function(e){
				this.guanggaostatus='0';
				this.isend = true;
			},
			showAd() {
				setTimeout(() => {
					this.$refs.adInterstitial.show();
				},500)
			},
			onadload(e) {},
			onadclose(e) {},
			onaderror(e) {
				this.errMsg = JSON.stringify(e.detail);
			  // 广告加载失败
				uni.showModal({
					title: '错误',
					content: this.errMsg,
					showCancel:false,
					confirmText:'已知晓'
				});
			  
			},
      autoclose:function(type){
        var that = this;
        //播放时长，过时关闭
        if(that.param.duration && that.param.duration>0){
          var duration = that.param.duration*1000;
          setTimeout(function(){
            that.guanggaoClick();
          },duration)
        }
      }
		}
	}
</script>
<style>
	.advert{
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		z-index: 2000000;
		background: rgba(0, 0, 0, 0.7);
	}
	.advert_module{
		position: relative;
		width: 80%;
	}
	.advert_cover .advert_module{
		width: 100%;
		height: 100%;
	}
	.advert_poster{
		position: relative;
		width: 85%;
		display: block;
		margin: 0 auto;
	}
	.advert.advert_cover{z-index: 2000000;}
	.advert_cover .advert_poster{
		height: 100%;
	}
	.advert_cover .advert_module .advert_poster{
		width: 100%;height: 100%;
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
	}
	.advert_cover .advert_close_txt{
		position: absolute;
		top: 6rpx;
		right: 6rpx;
		width: 100rpx;
		height: 46rpx;
		background: rgba(0, 0, 0, 0.7);
		color: #ffffff;
		z-index: 2000050;
		border-radius: 30rpx;
		font-size: 22rpx;
		display: flex;
		justify-content: center;
		align-items: center;
	}
	.advert_cover .advert_module .advert_close_img{
		position: absolute;
		top: 6rpx;
		right: 6rpx;
		width: 46rpx;
		height: 46rpx;
		border-radius: 50%;
		z-index: 2000050;
	}
	.advert_close_bottom{
		position: relative;
		border: 1px solid #fff;
		margin-top: 10rpx;
	}
	.advert_close_top{
		position: fixed;
		top: 14rpx;
		right: 14rpx;
		background: rgba(0, 0, 0, 0.7);
	}
	.advert_close{
		height: 60rpx;
		width: 60rpx;
		margin: 0 auto;
		border-radius: 100rpx;
	}
	.advert_close image{
		height: 50rpx;
		width: 50rpx;
	}
	
	.advert_video{
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		z-index: 2000000;
		background: rgba(0, 0, 0, 0.7);
	}
	
	.dp-bottom-close-btn{display: flex;align-items: center;justify-content: center;position: fixed;bottom: 90rpx;width: 100%;	z-index: 2000070;}
	.dp-ggskip-btn{width: 120rpx;color: #fff;background: rgba(0, 0, 0, 0.7);border-radius: 40rpx;text-align: center; padding: 8rpx 20rpx;}
</style>