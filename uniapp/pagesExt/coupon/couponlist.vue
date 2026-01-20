<template>
<view class="container">
	<block v-if="isload">
		<view class="coupon-list">
			<view v-for="(item, index) in datalist" :key="item.id" class="coupon" @tap.stop="goto" :data-url="'coupondetail?id=' + item.id">
				<view class="pt_left">
					<view class="pt_left-content">
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==1"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==10"><text class="t1">{{item.discount/10}}</text><text class="t2">折</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==3"><text class="t1">{{item.limit_count}}</text><text class="t2">次</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==5"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
						<block v-if="item.type!=1 && item.type!=10 && item.type!=3 && item.type!=5">
							<view class="f1" :style="{color:t('color1')}">{{item.type_txt}}</view>
						</block>
						<view class="f2" :style="{color:t('color1')}" v-if="item.type==1 || item.type==10 || item.type==4 || item.type==5 || item.type==10">
							<text v-if="item.minprice>0">满{{item.minprice}}元可用</text>
							<text v-else>无门槛</text>
						</view>
					</view>
				</view>
				<view class="pt_right">
					<view class="f1">
						<view class="t1">{{item.name}}</view>
						<text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">{{item.type_txt}}</text>
						<view class="t4" v-if="item.house_status">一户仅限一次</view>
						<view class="t3" v-if="item.type!=20" :style="item.bid>0?'margin-top:0':'margin-top:10rpx'">有效期至 {{item.yxqdate}}</view>
						<view class="t4" v-if="item.bid>0">适用商家：{{item.bname}}</view>
					</view>
					<button class="btn" v-if="item.perlimit > 0 && item.haveget>=item.perlimit" style="background:#9d9d9d">已领取</button>
					<button class="btn" v-else-if="item.stock<=0" style="background:#9d9d9d">已抢光了</button>
					<button class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-else-if="item.use_tongzheng==1" @tap.stop="getcouponbytongzheng" :data-id="item.id" :data-tongzheng="item.tongzheng" :data-key="index">兑换</button>
					
					<block v-else-if="item.is_birthday_coupon==1 && item.birthday_coupon_status > 0">
						<button class="btn" v-if="item.birthday_coupon_status==1" style="background:#9d9d9d">不可领取</button>
						<button class="btn" v-else :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap.stop="goto" :data-url="'/pagesExt/my/setbirthday'"   :data-key="index">设置生日</button>
					</block>
					
					<button class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-else @tap.stop="getcoupon" :data-id="item.id" :data-price="item.price" :data-score="item.score" :data-key="index">{{item.price > 0 ? '购买' : (item.score>0?'兑换':'领取')}}</button>
				</view>
			</view>
		</view>
		<nodata v-if="nodata" :text="'暂无可领' + t('优惠券')"></nodata>
		<nomore v-if="nomore"></nomore>

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
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			
			nomore:false,
			nodata:false,
      datalist: [],
      pagenum: 1
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
    this.pagenum = 1;
    this.datalist = [];
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata();
    }
  },
  methods: {
    getdata: function () {
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
      var bid = that.opt && (that.opt.bid || that.opt.bid === '0') ? that.opt.bid : '';
			that.loading = true;
			that.nomore = false;
			that.nodata = false;
      app.post('ApiCoupon/couponlist', {st: st,pagenum: pagenum,bid: bid}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(data);
            that.datalist = newdata;
          }
        }
				that.loaded();
      });
    },
		getcoupon:function(e){
			var that = this;
			var datalist = that.datalist;
			var key = e.currentTarget.dataset.key;
			var couponinfo = datalist[key];
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
						that.getcouponconfirm(e);
					} else {
						console.log('播放中途退出，不下发奖励');
					}
					rewardedVideoAd.offLoad()
					rewardedVideoAd.offClose();
				});
			}else{
				that.getcouponconfirm(e);
			}
		},
    getcouponconfirm: function (e) {
			var that = this;
			var datalist = that.datalist;
			var id = e.currentTarget.dataset.id;
			var score = parseInt(e.currentTarget.dataset.score);
			var price = e.currentTarget.dataset.price;
			var key = e.currentTarget.dataset.key;

			if (price > 0) {
				app.post('ApiCoupon/buycoupon', {id: id}, function (res) {
					if(res.status == 0) {
							app.error(res.msg);
					} else {
						app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
					}
				})
				return;
			}
			if (score > 0) {
				app.confirm('确定要消耗' + score + '' + that.t('积分') + '兑换吗?', function () {
					app.showLoading('兑换中');
					app.post('ApiCoupon/getcoupon', {id: id}, function (data) {
						app.showLoading(false);
						if (data.status == 0) {
							app.error(data.msg);
						} else {
							app.success(data.msg);
							datalist[key]['haveget'] = data.haveget;
							that.datalist = datalist;
						}
					});
				});
			} else {
				app.showLoading('领取中');
				app.post('ApiCoupon/getcoupon', {id: id}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.error(data.msg);
					} else {
						app.success(data.msg);
						datalist[key]['haveget'] = data.haveget;
						that.datalist = datalist;
					}
				});
			}
		},
		getcouponbytongzheng: function (e) {
				var that = this;
				var datalist = that.datalist;
				var id = e.currentTarget.dataset.id;
				var tongzheng = parseInt(e.currentTarget.dataset.tongzheng);
				var price = e.currentTarget.dataset.price;
				var key = e.currentTarget.dataset.key;
				if (tongzheng > 0) {
					app.confirm('确定要消耗' + tongzheng + '' + that.t('通证') + '兑换吗?', function () {
						app.showLoading('兑换中');
						app.post('ApiCoupon/getcoupon', {id: id}, function (data) {
							app.showLoading(false);
							if (data.status == 0) {
								app.error(data.msg);
							} else {
								app.success(data.msg);
								datalist[key]['haveget'] = data.haveget;
								that.datalist = datalist;
							}
						});
					});
				} else {
					app.showLoading('领取中');
					app.post('ApiCoupon/getcoupon', {id: id}, function (data) {
						app.showLoading(false);
						if (data.status == 0) {
							app.error(data.msg);
						} else {
							app.success(data.msg);
							datalist[key]['haveget'] = data.haveget;
							that.datalist = datalist;
						}
					});
				}
			}
  }
};
</script>
<style>
.coupon-list{width:100%;padding:20rpx}
.coupon{width:100%;display:flex;margin-bottom:20rpx;border-radius:10rpx;overflow:hidden}
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
.coupon .pt_right .f1 .t2{height:36rpx;line-height:36rpx;font-size:20rpx;font-weight:bold;padding:0 16rpx;border-radius:4rpx}
.coupon .pt_right .f1 .t3{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;}
.coupon .pt_right .f1 .t4{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;max-width: 76%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}
.coupon .pt_right .btn{position:absolute;right:16rpx;top:49%;margin-top:-28rpx;border-radius:28rpx;width:150rpx;height:56rpx;line-height:56rpx;color:#fff}
.coupon .pt_right .sygq{position:absolute;right:30rpx;top:50%;margin-top:-50rpx;width:100rpx;height:100rpx;}

</style>