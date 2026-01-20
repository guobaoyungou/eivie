<template>
	<view v-if="needAuthorization" class="xieyibox" @touchmove.stop.prevent=''>
		<view class="wxxieyibox-content">
			<view class="title-view">隐私政策协议</view>
			<view class="content-text">尊敬的用户，我们将按照相关法律法规的要求，尽力保护您的个人信息安全可控。请您点击同意之前，仔细阅读<text class="link-text"  @click="handleOpenPrivacyContract">{{privacyContractName || '《小程序隐私保护协议》'}}</text>并充分理解，请点击“同意”开始使用。</view>
			<view class="but-view flex-col">
				<button class="but-class" :style="{background: (t('color1') == undefined ? '#FD4A46':t('color1'))}" id="agree-btn" open-type="agreePrivacyAuthorization" @agreeprivacyauthorization="handleAgreePrivacyAuthorization">同意</button>
				<navigator open-type="exit" target="miniProgram" class="but-class-no" hover-class="none">不同意并退出</navigator>
			</view>
		</view>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data() {
			return{
				needAuthorization: false,
				privacyContractName:'',
				resolvePrivacyAuthorization:''
			}
		},
		mounted() {
			// #ifdef MP-WEIXIN
				wx.onNeedPrivacyAuthorization((resolve, eventInfo) => {
					// console.log('触发本次事件的接口是：' + eventInfo.referrer)
					this.getPrivacySetting();
					this.resolvePrivacyAuthorization = resolve
				})
			// #endif
		},
		methods:{
			getPrivacySetting(){
				wx.getPrivacySetting({
					success:(res) => {
						if(res.needAuthorization){
							uni.hideLoading();
							this.needAuthorization = res.needAuthorization;
							this.privacyContractName = res.privacyContractName;
							// console.log(res,'是否需要弹出隐私指引')
						}
					},
					fail:(err) => {
						app.error(err);
					}
				})
			},
			handleAgreePrivacyAuthorization(){
				this.needAuthorization = false;
				this.resolvePrivacyAuthorization({ buttonId: 'agree-btn', event: 'agree' })
        setTimeout(function(){
          if(app.globalData.copyinfo){
          	app.copy(app.globalData.copyinfo);
          }
        },1000)
			},
			handleOpenPrivacyContract(){
				wx.openPrivacyContract();
			},
		}
	}
</script>

<style>
	.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:9999999999999;background:rgba(0,0,0,0.7)}
	.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;}
	.wxxieyibox-content{width:80%;margin:0 auto;margin-top:50%;background:#fff;color:#333;padding:0rpx 15px;border-radius:20rpx;}
	.wxxieyibox-content .title-view{width: 100%;font-size: 30rpx;text-align: center;color: #333;padding: 35rpx 0rpx 20rpx;font-weight: bold;}
	.wxxieyibox-content .content-text{font-size: 26rpx;color: #333;line-height: 42rpx;letter-spacing: 4rpx;padding: 0rpx 10rpx;}
	.wxxieyibox-content .content-text .link-text{color: #51B1F5;font-weight: bold;letter-spacing: 2rpx;}
	.wxxieyibox-content .but-view{padding: 20rpx 0rpx;margin-top: 20rpx;}
	.wxxieyibox-content .but-view .but-class{width:100%;font-size: 28rpx;color: #fff;border-radius: 40rpx;margin-bottom: 10rpx;padding: 5rpx;}
	.wxxieyibox-content .but-view .but-class-no{color: #aaa;width:100%;font-size: 28rpx;text-align: center;padding: 13rpx 0rpx;}
</style>