<template>
	<view v-if="isload">
		 <view class="schedule-view flex-x-center">
			 <view class="schedule-options flex-col">
					<view class="num-text active-class" :style="{background:t('color1')}">1</view>
					<view class="tips-text">填写微信号</view>
          <view style="text-align: center;color:#9E9E9E;font-size: 24rpx;line-height: 50rpx;">请输入正确的微信号</view>
			 </view>
			 <view class="dashed-line" style="border:2rpx solid #f1f1f1"></view>
			 <view class="schedule-options flex-col">
					<view class="num-text" :style="imgurl?'background:'+t('color1'):''">2</view>
					<view class="tips-text" :style="imgurl?'':'color: #9E9E9E;'">绑定分享员</view>
          <view style="text-align: center;color:#9E9E9E;font-size: 24rpx;line-height: 50rpx;">识别二维码接受邀请</view>
			 </view>
		 </view>
		 <view class="form-view">
			 <view class="form-title">填写微信号</view>
			 <view class="form-item">
				 <view class="label">微信号</view>
				 <input placeholder="请输入微信号" v-model="weixin" placeholder-style="height: 90rpx;line-height: 90rpx;padding: 0 20rpx" style="background-color: #f9f9f9;height: 90rpx;line-height: 90rpx;padding: 0 20rpx"/>
          <view style="color: #D81E06;line-height: 50rpx;font-size: 26rpx;display: flex;align-items: center;">
           <image :src="pre_url+'/static/img/workorder/ts.png'" style="width: 30rpx;height: 30rpx;margin-right: 20rpx;">必须是真实的微信号，不能是手机号
          </view>
			 </view>
       
       <block v-if="imgurl">
         <view style="margin-top: 20rpx;">
            <view class="form-title">保存二维码，用微信扫一扫识别二维码，接受邀请</view>
            <view class="form-item">
              <view style="width: 600rpx;height: 600rpx;margin: 0 auto;">
                <image :src="imgurl" @tap="previewImage" :data-url="imgurl" :data-urls="imgurls" style="width: 100%;height: 100%;"></image>
              </view>
            </view>
         </view>
         <view style="display: flex;justify-content: space-between;margin: 100rpx auto;">
           <button class="set-btn" @click="hasBand" :style="'margin:0;border:2rpx solid '+t('color1')+';color:'+t('color1')">确定已绑定</button>
           <button class="set-btn" @click="goBand" :style="{margin:'0',background:t('color1')}">重新生成</button>
         </view>
         
       </block>
      <block v-else>
        <button class="set-btn" @click="goBand" :style="{background:t('color1')}">立即提交</button>
      </block>
		 </view>
     
     <view class="form-view">
     		<view class="form-title">提示</view>
        <view class="form-item" style="line-height: 50rpx;margin-top: 20rpx;">
          <view>1.绑定分享员期间，请勿关闭页面</view>
          <view>2.根据页面提示操作即可完成绑定</view>
        </view>
     </view>
     <view style="width: 100%;height: 40rpx;"></view>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
        opt:{},
        loading:false,
        isload: false,
        bid:0,
        pre_url:app.globalData.pre_url,
				weixin:'',
        imgurl:'',
        imgurls:[]
			}
		},
		onLoad: function (opt) {
      this.opt = app.getopts(opt);
      this.bid = this.opt.bid || 0;
			this.getdata();
		},
		onPullDownRefresh: function () {
			this.getdata();
		},
		methods:{
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiWxChannelsSharer/apply', {bid:that.bid}, function (res) {
					that.loading = false;
          if(res.status == 1){
            that.weixin = res.weixin
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
			goBand(){
				let that = this;
				if(!that.weixin) return app.error('请填写微信号');
				uni.showLoading({title:'提交中...'})
				app.post('ApiWxChannelsSharer/apply',{bid:that.bid,weixin:that.weixin}, function(res){
					if(res.status == 1){
						uni.hideLoading();
            if(res.imgurl){
              that.imgurl  = res.imgurl;
              that.imgurls = [res.imgurl];
              uni.previewImage({
              	current: res.imgurl, 
              	urls: [res.imgurl] 
              })
            }else{
              app.alert('获取失败请重试');
            }
					}else{
						app.error(res.msg);
					}
				})
			},
      hasBand(){
      	let that = this;
        app.confirm('确定已绑定？',function(){
          uni.showLoading()
          app.post('ApiWxChannelsSharer/deal_applylog',{bid:that.bid,weixin:that.weixin}, function(res){
          	if(res.status == 1){
          		uni.hideLoading();
              if(res.imgurl){
                that.imgurl  = res.imgurl;
                that.imgurls = [res.imgurl];
                uni.previewImage({
                	current: res.imgurl, 
                	urls: [res.imgurl] 
                })
              }else{
                app.alert('获取失败请重试');
              }
          	}else{
          		app.error(res.msg);
          	}
          })
        })
      	
      }
		}
	}
</script>

<style>
	.content-text{width:94%;margin: 0 auto;padding: 30rpx 10rpx;font-size: 26rpx;color: #e60000;font-weight: bold;}
	.schedule-view{width:94%;margin: 0 auto;border-radius:10rpx;background:#fff;align-items: flex-start;padding: 40rpx 0rpx;}
	.schedule-view .schedule-options{align-items: center;}
	.schedule-view .schedule-options .active-class{}
	.schedule-view .schedule-options .num-text{width: 40rpx;height: 40rpx;text-align: center;line-height: 40rpx;background: #cbcbcb;color: #fff;border-radius: 50%;font-size: 24rpx;}
	.schedule-view .schedule-options .tips-text{font-size: 28rpx;color: #666;margin-top: 10rpx;}
	.schedule-view .dashed-line{border: 1px #b3b3b3 dashed;width: 200rpx;margin-top: 20rpx;}
	.form-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 30rpx 20rpx;}
	.form-view .form-title{font-size: 32rpx;color: #9E9E9E;}
	.form-view .form-item{line-height:90rpx;margin: 0 auto;}
	.form-item .label{color: #000;width:160rpx;font-size: 28rpx;}

	.success-view{width:94%;margin: 20rpx auto;background:#fff;border-radius:10rpx;padding: 50rpx 20rpx;align-items: center;}
	.success-view .icon-view{width: 90rpx;height: 90rpx;border-radius: 50%;background: #a9000e;display: flex;align-items: center;justify-content: center;}
	.success-view .icon-view image{width: 80rpx;height: 80rpx;}
	
	.but-view{margin:60rpx 5%;width:94%;margin: 50rpx auto;}
	.set-btn{width: 300rpx;height:90rpx;line-height:90rpx;border-radius:12rpx;color:#FFFFFF;font-weight:bold;margin: 0 auto;margin-top: 100rpx;}
</style>