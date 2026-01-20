<template>
<view class="container">
	<block v-if="isload">
    <view class="topcontent" :style="'background:'+t('color1')">
      <view>可提现</view>
      <view style="display: flex;align-items: center;margin: 20rpx 0;">
        <view style="font-size: 40rpx;font-weight: bold;">￥{{userinfo.commission}}</view>
        <!-- <view class="gowithdraw">提现</view> -->
      </view>
      <view style="display: flex;align-items: center;line-height: 50rpx;">
        <view style="border-right:2rpx  solid #fff ;width: 33.33%;">
          <view>总收入</view>
          <view style="font-size:32rpx;font-weight: bold;">￥{{userinfo.commission1}}</view>
        </view>
        <view style="border-right:2rpx  solid #fff ;width: 33.33%;padding-left: 20rpx;">
          <view>待提现</view>
          <view style="font-size:32rpx;font-weight: bold;">￥{{userinfo.commission2}}</view>
        </view>
        <view style="width: 33.33%;padding-left: 20rpx;">
          <view>已提现</view>
          <view style="font-size:32rpx;font-weight: bold;">￥{{userinfo.commission3}}</view>
        </view>
      </view>
    </view>
    
    <view style="width: 710rpx;margin: 0 auto;">
      <view style="display: flex;justify-content: space-between;margin: 20rpx 0;">
        <view style="font-weight: bold;">推广店铺</view>
        <!-- <view class="changebusiness">切换店铺</view> -->
      </view>
      
      <block v-if="datalist && datalist.length>0">
        <view v-for="(item, index) in datalist" :key="index"  style="background-color: #fff;border-radius: 12rpx;padding: 30rpx;margin-bottom: 20rpx;">
          <view style="display: flex;align-items: center;">
             <image :src="item.logo" style="width: 90rpx;height: 90rpx;border-radius: 50% 50%;background-color: #f1f1f1;"></image>
             <text style="margin-left: 10rpx;">{{item.name}}</text>
             <text v-if="item.bindmsg" style="margin-left: 10rpx;color:red">
              ({{item.bindmsg}})
             </text>
          </view>
          <view style="height: 60rpx;display: flex;justify-content: flex-end;margin-top: 20rpx;">
            <block v-if="item.bid && item.bid>0">
              <view @tap="goto" :data-url="'sharercommissionrecord?id='+item.id+'&bid='+item.bid" class="gotuiguang">
                 {{t('佣金')}}记录
              </view>
              <view @tap="goto" :data-url="'sharercommissionlog?id='+item.id+'&bid='+item.bid" class="gotuiguang" style="margin-left: 10rpx;">
                 {{t('佣金')}}明细
              </view>
              <view @tap="goto" :data-url="'sharerwithdraw?id='+item.id+'&bid='+item.bid" class="gotuiguang" style="margin-left: 10rpx;">
                 去提现
              </view>
            </block>
            <block v-else>
              <view @tap="goto" :data-url="'/activity/commission/commissionrecord?id='+item.id" class="gotuiguang">
                 {{t('佣金')}}记录
              </view>
              <view @tap="goto" :data-url="'/activity/commission/commissionlog?id='+item.id" class="gotuiguang" style="margin-left: 10rpx;">
                 {{t('佣金')}}明细
              </view>
              <view @tap="goto" :data-url="'/activity/commission/withdraw?id='+item.id" class="gotuiguang" style="margin-left: 10rpx;">
                 去提现
              </view>
            </block>
          </view>
        </view>
      </block>
      <nodata v-if="nodata"></nodata>
      <nomore v-if="nomore"></nomore>
    </view>
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
      nodata: false,
      nomore: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,

      userinfo:{
        commission:0,
        commission1:0,
        commission2:0,
        commission3:0
      },
      
      pagenum: 1,
      datalist: [],
      bid:0,
    };
  },
  onLoad: function (opt) {
    this.opt = app.getopts(opt);
    this.bid = this.opt.bid || 0;
  },
  onShow: function () {
  	this.getdata();
  },
  onPullDownRefresh: function () {
  	this.getdata();
  },
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
    	if(!loadmore){
    		this.pagenum = 1;
    		this.datalist = [];
    	}
      var that = this;
      var pagenum = that.pagenum;
      that.nodata = false;
      that.nomore = false;
      that.loading = true;
      app.post('ApiWxChannelsSharer/center', {pagenum: pagenum,bid:that.bid}, function (res) {
				that.loading = false;
        if(res.status == 1){
          var data = res.data;
          if (pagenum == 1) {
            that.userinfo = res.userinfo;
          	that.datalist = data;
            if (data.length == 0) {
              that.nodata = true;
            }
          	that.loaded();
          }else{
            if (data.length == 0) {
              that.nomore = true;
            } else {
              var datalist = that.datalist;
              var newdata = datalist.concat(data);
              that.datalist = newdata;
            }
          }
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
    changetab: function (e) {
      var st = e.currentTarget.dataset.st;
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
  }
};
</script>
<style>
  .topcontent{width: 710rpx;margin: 20rpx auto;border-radius: 12rpx;padding: 40rpx 30rpx;color: #fff;}
  .gowithdraw{height: 60rpx;line-height: 60rpx;width: 120rpx;text-align: center;color: #000;margin-left: 20rpx;background-color: #fff;border-radius: 8rpx;}
  .changebusiness{background-color: #F6AE02;border-radius: 40rpx 40rpx;color: #fff;height: 40rpx;line-height: 40rpx;padding: 0 30rpx;font-size: 26rpx;}
  .gotuiguang{height: 60rpx;line-height: 60rpx;width: 160rpx;border-radius: 8rpx; border: 2rpx solid #F3F3F3;text-align: center;}
</style>