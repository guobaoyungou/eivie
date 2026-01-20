<template>
  <view>
    <block v-if="isload">
      <view class="container">
        <view class="page">
          <view class="dkdiv-item flex" style="padding: 0;border: 0;font-weight:bold;font-size: 18px">
            <view class="f1">账单信息</view>
          </view>
          <view>
            <view class="info-box">
              <view class="dkdiv-item flex-y-center" >
                <view class="f1 mz">账单数量</view>
                <view class="f2">{{datalist.zdnum}}</view>
              </view>
              <view class="dkdiv-item flex-y-center" style="border-bottom: 1px #ededed solid;">
                <view class="f1 mz">账单金额</view>
                <view class="f2">￥{{datalist.zdmoney ? datalist.zdmoney : 0}}</view>
              </view>
            </view>
          </view>
          <view class="dkdiv-item flex" style="padding: 0;border: 0;font-weight:bold;font-size: 18px;margin-top: 20rpx">
            <view class="f1">收票方信息</view>
          </view>
          <view>
            <view class="info-box">
              <view class="dkdiv-item flex-y-center" >
                <view class="f1 mz">发票抬头</view>
                <view class="f2">{{datalist.set.invoice_account}}</view>
              </view>
              <view class="dkdiv-item flex-y-center" >
                <view class="f1 mz">企业地址</view>
                <view class="f2">{{datalist.set.invoice_address}}</view>
              </view>
              <view class="dkdiv-item flex-y-center" >
                <view class="f1 mz">收票方税号</view>
                <view class="f2">{{datalist.set.invoice_taxpayer_num}}</view>
              </view>
              <view class="dkdiv-item flex-y-center" >
                <view class="f1 mz">电话</view>
                <view class="f2">{{datalist.set.invoice_tel}}</view>
              </view>
              <view class="dkdiv-item flex-y-center" >
                <view class="f1 mz">开户行</view>
                <view class="f2">{{datalist.set.invoice_bankname}}</view>
              </view>
              <view class="dkdiv-item flex-y-center" style="border-bottom: 1px #ededed solid;">
                <view class="f1 mz">银行卡号</view>
                <view class="f2">{{datalist.set.invoice_bankcardnum}}</view>
              </view>
            </view>
          </view>
          <view v-if="st==0" class="op" style="margin-top: 120rpx">
            <view class="btn" @tap="toPay" :style="{background:t('color1')}">确认</view>
          </view>
        </view>
      </view>
    </block>
    <loading v-if="loading"></loading>
    <nomore v-if="nomore"></nomore>
    <nodata v-if="nodata"></nodata>
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
      pre_url: app.globalData.pre_url,
      opt: {},
      loading: false,
      nomore: false,
      nodata: false,
      isload: false,
      menuindex: -1,
      bid: 0,
      ymid: 0,
      hiddenmodalput: true,
      wxpayst: '',
      alipay: '',
      paypwd: '',
      moneypay: '',
      mdlist: "",
      name: "",
      userinfo: "",
      datalist: [],
      st:0
    };
  },

  onLoad: function(opt) {
    this.opt = app.getopts(opt);
    this.st = this.opt.st
    this.getdata();
  },
  onShow:function(){
    if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
      uni.hideHomeButton();
    }

  },
  onPullDownRefresh: function() {
    this.getdata();
  },
  methods: {
    getdata: function() {
      var that = this; //获取产品信息
      that.loading = true;
      app.get('ApiAdminFinance/withdrawInvoiceInfo', {
        st: that.opt.st,
        stime: that.opt.stime,
        etime: that.opt.etime,
        id: that.opt.id || 0,
      }, function(res) {
        that.loading = false;
        if (res.status == 0) {
          app.alert(res.msg, function() {
          });
          return;
        }

        that.datalist = res.data;
        that.loaded();
      });
    },

    toPay:function(e){
      var that = this;
      var orderid = that.payorderid;
      app.showLoading('提交中');
      app.post('ApiAdminFinance/setWithdrawInvoice', {
        stime: that.opt.stime,
        etime: that.opt.etime,
      }, function (res) {
        app.showLoading(false);

        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('/adminExt/finance/withdrawInvoice?st'+that.opt.st, 'reLaunch');
          }, 1000);
          return;
        }else{
          app.error(res.msg);
          return;
        }
      });
    },

  }
}
</script>
<style>
page {
  background: #f0f0f0;
}

.container {
  position: fixed;
  height: 100%;
  width: 100%;
  /* overflow: hidden; */
  overflow-y: scroll;
  z-index: 5;
}

.page {
  position: relative;
  padding: 20rpx 50rpx 20rpx 50rpx;
  border-radius: 30rpx 30rpx 0 0;
  background: #fff;
  box-sizing: border-box;
  width: 100%;
  min-height: calc(100% - 150rpx);
}

@keyframes twinkling {
  0% {
    opacity: 0;
  }

  90% {
    opacity: .8;
  }

  100% {
    opacity: 1;
  }
}

.info-box {
  position: relative;
  background: #fff;
}

.dkdiv-item {
  width: 100%;
  padding: 30rpx 0;
  background: #fff;
  border-bottom: 1px #ededed solid;
}

.dkdiv-item:last-child {
  border: none;
}

.dkdiv-item .mz {margin-left: 15rpx}

.dkdiv-item .f2 {
  text-align: right;
  flex: 1
}
.op {
  width: 96%;
  margin: 20rpx 2%;
  display: flex;
  align-items: center;
  margin-top: 40rpx
}

.op .btn {
  flex: 1;
  height: 100rpx;
  line-height: 100rpx;
  background: #07C160;
  width: 90%;
  margin: 0 10rpx;
  border-radius: 10rpx;
  color: #fff;
  font-size: 28rpx;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center
}
</style>
