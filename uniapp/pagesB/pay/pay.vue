<template>
  <view class="container">
	  <button v-if="plate!='h5'" @click="topay()" class="btn1" style="background:#0256FF;color: #F7F7F8">
	    继续支付
	  </button>
    <button v-if="plate=='wx'" @click="goback()" class="btn1" style="background:#0256FF;color: #F7F7F8">
      已完成支付
    </button>
	<view v-if="plate=='h5'" class="btn2" @click="goback()">  
	  如已完成支付，请返回APP页面
	</view>
	<navigator v-if="plate=='ali'" open-type="exit" class="btn1" style="background:#0256FF;color: #F7F7F8">已完成支付</navigator>
    <web-view v-if="url" :src="url"></web-view>
  </view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      opt: {},
      loading: false,
      isload: false,
      menuindex: -1,
      pre_url: app.globalData.pre_url,
      url: '',
      show_back: 0,
      session_id: '',
      orderid: 0,
      ali_appid:'',
	  plate:'h5'
    };
  },

  onLoad: function (opt) {
		// #ifdef H5
		if (opt && opt.wx_url) {
			this.url = decodeURIComponent(opt.wx_url);
			return false;
		}
		// #endif
		// console.log('获取参数');
		// console.log(opt);
		// console.log('第二次获取参数');
		var args = uni.getLaunchOptionsSync();
		// console.log(args);
		if(JSON.stringify(opt)=='{}' && args && args.query){
			opt = args.query;
		}
		// console.log(opt);
		if (opt.session_id) {
			this.session_id = opt.session_id;
			uni.setStorageSync('session_id', opt.session_id);
		}
		this.opt = app.getopts(opt);
		this.orderid = opt.orderid;
		this.ali_appid = opt.ali_appid;
		// #ifdef MP-ALIPAY
		this.plate='ali';
		// #endif
		// #ifdef MP-WEIXIN
		this.plate='wx';
		// #endif
		// console.log(opt);
		this.getdata();
  },
  methods: {
    getdata: function () {
      app.showLoading('支付中');
      var that = this;
      var session_id = that.session_id;
	    console.log('获取openid');
      app.get('ApiPay/getOpenid', {sxpay:1,session_id:session_id},function (res) {
		  console.log(res);
        if (res.openid) {
          that.topay();
        } else {
          // #ifdef MP-ALIPAY
          that.alilogin();
          // #endif
          // #ifdef MP-WEIXIN
          that.wxlogin();
          // #endif
        }
      });
    },
    topay: function () {
      var that = this;
			// #ifdef MP-ALIPAY
			that.alipay();
			// #endif
			// #ifdef MP-WEIXIN
			that.wxpay();
			// #endif
    },
    wxpay: function (e) {
      console.log('进入微信支付');
      var that = this;
      var orderid = that.orderid;
      app.post('ApiPay/sxfpay_app', {op: 'submit', orderid: orderid}, function (res) {
        app.showLoading(false);
        console.log(res);
        if(!res.status){
            app.error(res.msg);
            return;
        }
        var opt = res.data;
        //随行付
        if (opt.sxpay && opt.path) {
          //随行付
		  app.confirm('确定要支付吗?', function () {
			  uni.openEmbeddedMiniProgram({
				appId: opt.appId,
				path: decodeURIComponent(opt.path),
				extraData: {},
				success(res) {
				  console.log('随行付半屏小程序打开');
				},
				fail(res){
					console.log('调用半屏错误');
					console.log(res);
				}
			  })
		  })
        } else {
          uni.requestPayment({
            'provider': 'wxpay',
            'timeStamp': opt.timeStamp,
            'nonceStr': opt.nonceStr,
            'package': decodeURIComponent(opt.package),
            'signType': opt.signType ? opt.signType : 'MD5',
            'paySign': decodeURIComponent(opt.paySign),
            'success': function (res2) {
              app.success('付款完成');
            },
            'fail': function (res2) {
              //app.alert(JSON.stringify(res2))
            }
          });
        }
      })
    },
    alipay: function () {
      console.log('进入支付宝支付');
      var that = this;
      var orderid = that.orderid;
      app.post('ApiPay/sxfpay_app', {op: 'submit', orderid: orderid}, function (res) {
        app.showLoading(false);
        // console.log(res);
        if(!res.status){
          app.error(res.msg);
          return;
        }
        var opt = res.data;
				console.log('支付宝支付参数');
				console.log(opt);
        uni.requestPayment({
					'provider':'alipay',
					'orderInfo': opt.trade_no,
					'success': function (res2) {
						// console.log(res2)
						if(res2.resultCode == '6001'){
							return;
						}
						app.success('付款完成');
						that.subscribeMessage(function () {
					
						});
					},
					'fail': function (res2) {
						//app.alert(JSON.stringify(res2))
					}
				});
      })
    },
    wxlogin: function () {
      var that = this;
      wx.login({
        success(res1) {
          // console.log(res1);
          var code = res1.code;
          //用户允许授权
          app.post('ApiIndex/setwxopenid', {code: code}, function (res2) {
            if (res2.status == 1) {
              //app.success(res2.msg);
              that.topay();
            } else {
              app.error(res2.msg);
            }
            return;
          })
        }
      });
    },
    alilogin: function () {
      // #ifdef MP-ALIPAY
      var that = this;
      var ali_appid = that.ali_appid;

      if (ali_appid) {
        my.getAuthCode({
          appId: ali_appid,
          scopes: ['auth_base'],
        }, function (res) {
          console.log('支付宝授权信息');
          console.log(res);
          //var res = JSON.stringify(res);
          if (!res.error && res.authCode) {
            app.post('ApiIndex/setalipayopenid', {
              code: res.authCode,
              silent: 1,
              //platform:"h5"
            }, function (res2) {
              if (res2.status != 0) {
                that.topay();
              } else {
                app.error(res2.msg);
              }
            });
          } else {
            app.showLoading(false);

            if (res.errorMessage) {
              app.alert(res.errorMessage);
            } else if (res.errorDesc) {
              app.alert(res.errorDesc);
            } else {
              app.alert('授权出错');
            }
            return
          }
        });
      } else {
        app.alert('系统未配置支付宝参数');
        return
      }
      // #endif
    },
    goback: function () {
      // #ifdef MP-WEIXIN
      uni.exitMiniProgram({
        success: function() {
          console.log('退出小程序成功');
        },
        fail: function(err) {
          console.log('退出小程序失败', err);
        }
      })
      // #endif
      // #ifdef H5
	  uni.navigateBack()
      // #endif
    }
  }
}
</script>
<style>
	.container{
		padding-top: 55%;
	}
.btn1 {
  width: 80%;
  height: 100rpx;
  margin: 50rpx auto;
  border-radius: 100rpx;
  line-height: 100rpx;
  text-align: center;
  font-weight: bold;
  color: #A9A9A9;
  font-size: 30rpx
}
.btn2 {
  width: 80%;
  height: 100rpx;
  line-height: 100rpx;
  margin: 50rpx auto;
  text-align: center;
  font-weight: bold;
  color: #A9A9A9;
  font-size: 30rpx
}
</style>