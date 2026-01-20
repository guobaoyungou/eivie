<template>
	<view v-if="isload">
		 <view class="content-text">请填写真实信息，完成签约</view>
		 <view class="schedule-view flex-x-center">
			 <view class="schedule-options flex-col">
					<view class="num-text active-class">1</view>
					<view class="tips-text">填写提现信息</view>
			 </view>
			 <view class="dashed-line" :style="{border:`${signstatus}` == true ? '1px dashed #a9000e':'1px dashed #b3b3b3'}"></view>
			 <view class="schedule-options flex-col">
					<view :class="[signstatus ? 'active-class':'','num-text']">2</view>
					<view class="tips-text">灵活薪签约</view>
			 </view>
		 </view>
		 <view class="success-view flex-col" v-if="signstatus">
       <view class="icon-view">
         <image :src="pre_url+'/static/img/checkd.png'"></image>
       </view>
       <view style="font-size: 28rpx;color: #000;font-weight: bold;margin-top: 50rpx;">提现信息已成功提交</view>
       <view style="font-size: 24rpx;color: #7a7a7a;padding: 30rpx 0rpx;">请前往灵活薪进行签约</view>
       <view style="font-size: 24rpx;color: #7a7a7a;">签约成功后重新进入此页面时进行提现</view>
		 </view>
		 <view class="form-view" v-else>
			 <view class="form-title">身份信息</view>
			 <view class="form-item">
				 <view class="label">姓名</view>
				 <input placeholder="请输入姓名" v-model="userinfo.realname" />
			 </view>
			 <view class="form-item">
				 <view class="label">手机号</view>
				 <input placeholder="请输入手机号" v-model="userinfo.tel" />
			 </view>
			 <view class="form-item">
			 	 <view class="label">身份证号</view>
			 	 <input placeholder="请输入身份证号" v-model="userinfo.usercard" />
			 </view>
		 </view>
		 <view class="but-view">
			  <button v-if="signstatus" class="set-btn" @click="goSigning" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">去签约</button>
			 <button v-else class="set-btn" @click="getNext" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">下一步</button>
		 </view>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
        loading:false,
        isload: false,
				userinfo:{
					realname:'',
					usercard:'',
					tel:''
				},
				signstatus:false,
				signurl:'',
        signurl2:'',
        signappid:'',
		pre_url:app.globalData.pre_url,
			}
		},
		  onLoad: function (opt) {
			this.getdata();
		  },
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods:{
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiMy/linghuoxinsign', {}, function (res) {
					that.loading = false;
          if(res.status == 1){
            if(res.signstatus){
              that.signstatus = res.signstatus;
            }
            if(res.userinfo){
              that.userinfo = res.userinfo;
            }
            that.loaded();
          } else {
          		if (res.msg) {
          			app.alert(res.msg, function() {
          				if (res.url) app.goto(res.url);
          			});
          		} else if (res.url) {
          			app.goto(res.url);
          		} else {
          			app.alert('您无查看权限');
          		}
          }
				});
			},
			getNext(){
				let that = this;
				if(!that.userinfo.realname) return app.error('请填写姓名');
				if(!that.userinfo.tel) return app.error('请填写手机号');
				if(!that.userinfo.usercard) return app.error('请填写身份证号');
				uni.showLoading({title:'提交中...'})
				app.post('ApiMy/linghuoxinsign',that.userinfo, function(res){
					if(res.status == 1){
						uni.hideLoading();
						that.signstatus = true;
						that.signurl  = res.signurl;
            that.signurl2 = res.signurl2;
            that.signappid= res.signappid;
					}else{
						app.error(res.msg);
					}
				})
			},
			goSigning(){
        var that = this;
				if(!that.signurl) return app.error('信息未提交成功');
        // #ifdef H5
        window.location.href = that.signurl;
        return;
        // #endif
        // #ifdef MP-WEIXIN
        uni.navigateToMiniProgram({
          appId: that.signappid,
          path: 'pages/webview/webview?url='+encodeURIComponent(that.signurl2),
          envVersion: 'release', 
          success(res) {
          }
        })
        return;
        // #endif
        // #ifndef H5 || MP-WEIXIN 
				uni.navigateTo({
					url:'/pages/index/webView?url='+ encodeURIComponent(this.signurl),
				})
        // #endif
			}
		}
	}
</script>

<style>
	.content-text{width:94%;margin: 0 auto;padding: 30rpx 10rpx;font-size: 26rpx;color: #e60000;font-weight: bold;}
	.schedule-view{width:94%;margin: 0 auto;border-radius:10rpx;background:#fff;align-items: flex-start;padding: 40rpx 0rpx;}
	.schedule-view .schedule-options{align-items: center;}
	.schedule-view .schedule-options .active-class{background: #a9000e !important;}
	.schedule-view .schedule-options .num-text{width: 40rpx;height: 40rpx;text-align: center;line-height: 40rpx;background: #cbcbcb;color: #fff;border-radius: 50%;font-size: 24rpx;}
	.schedule-view .schedule-options .tips-text{font-size: 26rpx;color: #666;margin-top: 10rpx;}
	.schedule-view .dashed-line{border: 1px #b3b3b3 dashed;width: 200rpx;margin-top: 20rpx;}
	.form-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 30rpx 20rpx;}
	.form-view .form-title{font-size: 32rpx;color: #333;}
	.form-view .form-item{display:flex;align-items:center;width:96%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;margin: 0 auto;}
	.form-item .label{color: #000;width:160rpx;font-size: 24rpx;}
	.success-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 50rpx 20rpx;align-items: center;}
	.success-view .icon-view{width: 90rpx;height: 90rpx;border-radius: 50%;background: #a9000e;display: flex;align-items: center;justify-content: center;}
	.success-view .icon-view image{width: 80rpx;height: 80rpx;}
	
	.but-view{margin:60rpx 5%;width:94%;margin: 50rpx auto;}
	.set-btn{width: 100%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
</style>