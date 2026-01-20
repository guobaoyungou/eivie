<template>
<view class="container">
	<block v-if="isload">
		<view class="coupon-list">
			<view v-for="(item, index) in datalist" :key="index" class="coupon" @tap.stop="goto" :data-url="'coupondetail?rid=' + item.id" :style="(item.isgive == 1 || item.isgive == 2)?'padding-left:40rpx':''">
				<view class="pt_left">
					<view class="pt_left-content">
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==1"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==10"><text class="t1">{{item.discount/10}}</text><text class="t0">折</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==2">礼品券</view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==3"><text class="t1">{{item.limit_count}}</text><text class="t2">次</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==4">抵运费</view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==5"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
						<view class="f2" :style="{color:t('color1')}" v-if="item.type==1 || item.type==4 || item.type==5">
							<text v-if="item.minprice>0">满{{item.minprice}}元可用</text>
							<text v-else>无门槛</text>
						</view>
            <view class="f1" :style="{color:t('color1')}" v-if="item.type==20">券包</view>
					</view>
				</view>
				<view class="pt_right">
					<view class="f1">
						<view class="t1">{{item.couponname}}</view>
						<text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="item.type==1">代金券</text>
						<text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="item.type==2">礼品券</text>
						<text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="item.type==3">计次券</text>
						<text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="item.type==4">运费抵扣券</text>
						<text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="item.type==5">餐饮券</text>
            <text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}" v-if="item.type==20">券包</text>
						<view class="t4" v-if="item.bid>0">适用商家：{{item.bname}}</view>
						<view class="t3" v-if="item.type!=20" :style="item.bid>0?'margin-top:0':'margin-top:10rpx'">有效期至 {{item.endtime}}</view>
					</view>
				</view>
			</view>
		</view>
		<view style="width:100%;height:120rpx"></view>
		<view class="giveopbox">
			<view class="btn-give" @tap="receiveCoupon" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">立即领取({{datalist.length}}张)</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
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
			pre_url:app.globalData.pre_url,
			
      st: 0,
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    getdata: function () {
      var that = this;
			that.loading = true;
			that.nomore = false;
			that.nodata = false;
      app.post('ApiCoupon/coupongive', {rids: that.opt.rids,frommid:that.opt.pid}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: '领取' + that.t('优惠券')
				});
        var data = res.data;
				that.datalist = data;
				that.loaded();
      });
    },
		receiveCoupon:function(e){
			var that = this;
			var couponinfo = that.datalist[0];
			if (app.globalData.platform == 'wx' && couponinfo && couponinfo.rewardedvideoad && wx.createRewardedVideoAd) {
				app.showLoading();
				if(!app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad]){
					app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad] = wx.createRewardedVideoAd({ adUnitId: couponinfo.rewardedvideoad});
				}
				var rewardedVideoAd = app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad];
				rewardedVideoAd.load().then(() => {app.showLoading(false);rewardedVideoAd.show();}).catch(err => { app.alert('加载失败');});
				rewardedVideoAd.onError((err) => {
					app.showLoading(false);
					app.alert(err.errMsg);
					console.log('onError event emit', err)
					rewardedVideoAd.offLoad()
					rewardedVideoAd.offClose();
				});
				rewardedVideoAd.onClose(res => {
					app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad] = null;
					if (res && res.isEnded) {
						//app.alert('播放结束 发放奖励');
						that.receiveCouponConfirm(e);
					} else {
						console.log('播放中途退出，不下发奖励');
					}
					rewardedVideoAd.offLoad()
					rewardedVideoAd.offClose();
				});
			}else{
				that.receiveCouponConfirm(e);
			}
		},
		receiveCouponConfirm:function(e){
			var that = this;
			app.showLoading('领取中');
			app.post('ApiCoupon/receiveCoupon2', {rids: that.opt.rids,frommid:that.opt.pid}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.error(data.msg);
				} else {
					app.alert(data.msg,function(){
						app.goto('/pages/my/usercenter');
					});
				}
			});
		},
  }
};
</script>
<style>

.coupon-list{width:100%;padding:20rpx}
.coupon{width:100%;display:flex;margin-bottom:20rpx;border-radius:10rpx;overflow:hidden;align-items:center;position:relative;background: #fff;}
.coupon .pt_left{background: #fff;min-height:200rpx;color: #FFF;width:30%;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left-content{width:100%;height:100%;margin:30rpx 0;border-right:1px solid #EEEEEE;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left .f1{font-size:40rpx;font-weight:bold;text-align:center;}
.coupon .pt_left .t0{padding-right:0;}
.coupon .pt_left .t1{font-size:60rpx;}
.coupon .pt_left .t2{padding-left:10rpx;}
.coupon .pt_left .f2{font-size:20rpx;color:#4E535B;text-align:center;}
.coupon .pt_right{background: #fff;width:70%;display:flex;min-height:200rpx;text-align: left;padding:20rpx 20rpx;position:relative}
.coupon .pt_right .f1{flex-grow: 1;flex-shrink: 1;}
.coupon .pt_right .f1 .t1{font-size:28rpx;color:#2B2B2B;font-weight:bold;height:60rpx;line-height:60rpx;overflow:hidden}
.coupon .pt_right .f1 .t2{height:36rpx;line-height:36rpx;font-size:20rpx;font-weight:bold;padding:0 16rpx;border-radius:4rpx; margin-right: 16rpx;}
.coupon .pt_right .f1 .t2:last-child {margin-right: 0;}
.coupon .pt_right .f1 .t3{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;}
.coupon .pt_right .f1 .t4{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;}
.coupon .pt_right .btn{position:absolute;right:30rpx;top:50%;margin-top:-28rpx;border-radius:28rpx;width:160rpx;height:56rpx;line-height:56rpx;color:#fff}
.coupon .pt_right .sygq{position:absolute;right:30rpx;top:50%;margin-top:-50rpx;width:100rpx;height:100rpx;}

.coupon .pt_left.bg3{background:#ffffff;color:#b9b9b9!important}
.coupon .pt_right.bg3 .t1{color:#b9b9b9!important}
.coupon .pt_right.bg3 .t3{color:#b9b9b9!important}
.coupon .pt_right.bg3 .t4{color:#999999!important}

.giveopbox{position:fixed;bottom:0;left:0;width:100%;}
.btn-give{width:90%;margin:30rpx 5%;height:96rpx; line-height:96rpx; text-align:center;color: #fff;font-size:30rpx;font-weight:bold;border-radius:48rpx;}
</style>