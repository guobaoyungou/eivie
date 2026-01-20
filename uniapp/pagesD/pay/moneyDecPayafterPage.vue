<template>
<view class="container">
    <block v-if="isload">
      <view style="width: 100%;margin-top: 50rpx;">
        <view style="width: 90rpx;height: 90rpx;margin: 0 auto;">
          <image :src="pre_url+'/static/img/success.png'" style="width: 100%;height: 100%;"></image>
        </view>
        <view style="width: 100%;margin-top: 20rpx;text-align: center;font-weight: bold;">支付已完成</view>
      </view>
      <view class="tipmsg" :style="'background: linear-gradient(to bottom, rgba('+t('color1rgb')+',0.6),'+t('color1')">
        下次消费可抵扣<text>¥{{userinfo.money}}</text>
      </view>
      
      <view @tap="goto" data-url="/pagesExt/money/recharge" data-opentype="reLaunch" class="tipmsg2" :style="'background:'+t('color1')">
        前往查看
      </view>
			
			<!-- 抽奖活动 -->
			<view class="lottery-section" v-if="ischoujiang == 1">
				<view class="lottery-text"  @tap="goto"  data-url="/pagesD/choujiang/index"  data-opentype="reLaunch" :style="{ color: t('color1') }">
					<text>您已成功参与消费免单抽奖活动，</text>
					<text>抽奖活动将于{{open_award_time}}开奖。</text>
				</view>

				<view @tap="goto" data-url="/pagesD/choujiang/index" data-opentype="reLaunch" class="lottery-btn" :style="{ background: t('color1') }">
					前往活动查看
				</view>
			</view>
			<!-- END 抽奖活动 -->
			
    </block>
    <loading v-if="loading"></loading>
    <dp-tabbar :opt="opt"></dp-tabbar>
    <popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
  		opt:{},
      isload: false,
  		loading:false,
  		menuindex:-1,
  		pre_url:app.globalData.pre_url,
      
      userinfo:{},
      ischoujiang:0,
      open_award_time:0,//抽奖开奖时间。
    };
  },
  onLoad: function (opt) {
  	this.opt = app.getopts(opt);
    this.getdata();
  },
  methods: {
  	getdata: function () {
      var that = this;
      that.loading = true;
      var payid = '';
      if(that.opt && that.opt.payid){
        payid = that.opt.payid;
      }
      app.get('ApiMoney/recharge', {payid:payid}, function (res) {
        that.loading = false;
        if (res.status == 0) {
        	app.alert(res.msg,function(){
            if(res.url) app.goto(res.url,'reLaunch');
          });
        	return;
        }else{
          that.userinfo = res.userinfo;
          if(res.ischoujiang){
            that.ischoujiang =  res.ischoujiang;
            that.open_award_time = res.open_award_time;
          }
          that.loaded();
        }
      })
    }
  }
}
</script>
<style>
  page{width: 100%;height: 100%;background-color: #fff;}
  .tipmsg{width: 680rpx;margin: 0 auto;text-align: center;font-size: 36rpx;color: #fff;line-height: 100rpx;border-radius: 12rpx;margin-top: 140rpx;}
  .tipmsg2{width: 640rpx;margin: 0 auto;text-align: center;font-size: 30rpx;color: #fff;line-height: 70rpx;border-radius: 70rpx 70rpx;margin-top: 50rpx;}
  .lottery-section{width:100%;margin-top:80rpx;padding:0 35rpx;box-sizing:border-box;text-align:center}
  .lottery-text{font-size:26rpx;line-height:1.6;color:#666;margin-bottom:30rpx;padding:0 20rpx;background-color:#f9f9f9;border-radius:12rpx;padding:24rpx 30rpx}
  .lottery-text text{display: block;width: 100%;text-align: center;margin-bottom: 10rpx;}
  .lottery-btn{width:640rpx;margin:0 auto;text-align:center;font-size:30rpx;color:#fff;line-height:70rpx;border-radius:70rpx;box-shadow:0 6rpx 16rpx rgba(0,0,0,0.12);transition:transform 0.2s}
  .lottery-btn:active{transform:scale(0.96)}
</style>