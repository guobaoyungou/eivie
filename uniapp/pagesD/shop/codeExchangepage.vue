<template>
  <view class="container">
    <block v-if="isload">
      <view class="mian" :style="'background: url('+set.exchange_page_bgpic+');background-size: 100% 100%;background-repeat: no-repeat;'">
        <view v-if="isgoback" class="goback" @tap="goback" :style="{top:gobacktopHeight+'px'}">
        	<image class="goback-img" :src="pre_url+'/static/img/goback.png'" />
        </view>
        <view style="width: 100%;position: absolute;top:60%">
          <view style="width: 680rpx;margin: 0 auto;">
            <view class="codecss">
              <span>卡密：</span>
              <input :disabled="true" :value="code" style="flex: 1;height: 80rpx;line-height:80rpx ;"/>
            </view>
            <view class="gourlcss" @tap="gotourl"  :style="'background:'+t('color1')">
              点 击 使 用
            </view>
          </view>
        </view>
      </view>
    </block>
    <loading v-if="loading"></loading>
    <dp-tabbar :opt="opt"></dp-tabbar>
    <popmsg ref="popmsg"></popmsg>
    <wxxieyi></wxxieyi>
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
			platform:app.globalData.platform,
			pre_url:app.globalData.pre_url,
      
      orderid:0,
			code:'',
			set:{},
      gobacktopHeight: 40,
      isgoback:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt){
      if(this.opt.isgoback) this.isgoback = this.opt.isgoback;
      if(this.opt.code) this.code = this.opt.code;
      if(this.opt.orderid) this.orderid = this.opt.orderid;
    } 
		this.getdata();
  },
  onReady() {
  	this.init();
  	this.getdata();
  	var sysinfo = uni.getWindowInfo();
  	this.pageHeight = sysinfo.windowHeight;
  	if (sysinfo && sysinfo.statusBarHeight) {
  		this.gobacktopHeight = sysinfo.statusBarHeight;
  	}
  	// #ifdef H5
  	this.gobacktopHeight = 20;
  	// #endif
  	if(uni.getWindowInfo().uniPlatform=='mp-alipay'||uni.getWindowInfo().uniPlatform=='mp-baidu'){
  		this.isalipay = true;
  	}
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiOrder/code_exchangepage', {orderid:that.orderid}, function (res) {
				that.loading = false;
        if(res.status == 1){
          that.set  = res.set;
          that.code = res.code;
          that.loaded();
        //直接跳转
        }else if(res.status == 2){
          app.goto(res.url,'redirect');
        }else{
          app.alert(res.msg, function () {
            if(res.url) app.goto(res.url);
          });
        }
			});
		},
    inputDhcode:function(e){
      this.code = e.detail.value;
    },
    gotourl: function (e) {
      var that = this;
      var code = that.code;
      uni.setClipboardData({
        data: code,
        showToast:false,
      });
      setTimeout(function(){
        app.goto(that.set.exchange_page_tourl);
      },900)
    },
    goback: function() {
      var that = this;
    	app.goback();
    },
  }
}
</script>
<style>
  page{width: 100%;height: 100%;}
  .container{width: 100%;height: 100%;}
  .mian{width: 100%;height: 100%;}
  .codecss{width: 100%;line-height: 80rpx;background-color: #fff;border-radius: 80rpx;padding: 0 20rpx;display: flex;font-size: 28rpx;}
  .gourlcss{width: 640rpx;margin: 0 auto;text-align: center;color: #fff;margin-top: 40rpx;line-height: 80rpx;border-radius: 80rpx;}
  .goback {
  	position: absolute;
  	z-index: 4;
  	top: 40px;
  	left: 15px;
  	width: 30px;
  	height: 30px;
  	display: flex;
  	flex-direction: column;
  	align-items: center
  }
  
  .goback-img {
  	width: 30px;
  	height: 30px
  }
</style>