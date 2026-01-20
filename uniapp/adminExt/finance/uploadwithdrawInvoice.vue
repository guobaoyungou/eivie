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
                <view class="f2">{{datalist.num}}</view>
              </view>
              <view class="dkdiv-item flex-y-center" style="border-bottom: 1px #ededed solid;">
                <view class="f1 mz">账单金额</view>
                <view class="f2">￥{{datalist.money ? datalist.money : 0}}</view>
              </view>
            </view>
          </view>
          <view class="dkdiv-item flex" style="padding: 0;border: 0;font-weight:bold;font-size: 18px;margin-top: 20rpx">
            <view class="f1">发票信息</view>
          </view>
          <view>
            <view class="dkdiv-item flex-y-center" style="justify-content: space-between;">
              <view class="form-item flex-col">
                <view class="label f1"><sapn style="color: #5d5c5c;font-size: 12px">(最多上传三张，仅支持jpg，png格式)</sapn></view>
                <view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
                  <view v-for="(item, index) in invoice_pics" :key="index" class="layui-imgbox">
                    <view  v-if="datalist.status == 1  || datalist.status == 4" class="layui-imgbox-close" @tap="removeimg2" :data-index="index" data-field="invoice_pics"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
                    <view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
                  </view>
                  <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg2" data-field="invoice_pics" v-if="invoice_pics.length<3 && (datalist.status == 1 || datalist.status == 4)"></view>
                </view>
              </view>
            </view>
          </view>
          <view v-if="datalist.status == 1 || datalist.status == 4" class="op" style="margin-top: 120rpx">
            <view class="btn" @tap="toPay" :style="{background:t('color1')}">提交</view>
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
      userinfo: "",
      datalist: [],
      invoice_pics: []
    };
  },

  onLoad: function(opt) {
    this.opt = app.getopts(opt);

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
      app.get('ApiAdminFinance/getWithdrawInvoice', {
        id: that.opt.id || 0,
      }, function(res) {
        that.loading = false;
        if (res.status == 0) {
          app.alert(res.msg, function() {
          });
          return;
        }

        that.datalist = res.data;
        that.invoice_pics = res.data.invoice_pics;
        that.loaded();
      });
    },

    uploadimg2:function(e){
      var that = this;
      var field= e.currentTarget.dataset.field
      var invoice_pics = that[field]
      if(!invoice_pics) invoice_pics = [];
      app.chooseImage(function(urls){
        for(var i=0;i<urls.length;i++){
          invoice_pics.push(urls[i]);
        }
      },1)
    },
    removeimg2:function(e){
      var that = this;
      var index= e.currentTarget.dataset.index
      var field= e.currentTarget.dataset.field
      var invoice_pics = that[field]
      invoice_pics.splice(index,1)
    },

    toPay:function(e){
      var that = this;
      var orderid = that.payorderid;
      if(!that.invoice_pics || that.invoice_pics.length <= 0){
        app.error('请上传发票');
        return;
      }
      app.showLoading('提交中');
      app.post('ApiAdminFinance/getWithdrawInvoice', {
        id: that.opt.id || 0,
        invoice_pics: that.invoice_pics,
      }, function (res) {
        app.showLoading(false);

        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('/adminExt/finance/withdrawInvoice?st=1', 'reLaunch');
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


.dkdiv {
  margin-top: 20rpx
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

.dkdiv-item .f3 {
  width: 30rpx;
  height: 30rpx;
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
.layui-imgbox{position: relative;margin-top: 10px}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>
