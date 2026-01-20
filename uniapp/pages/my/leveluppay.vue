<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<view class="info-item">
				<text class="t1">订单名称:</text>
				<text class="t2">{{orderinfo.title}}</text>
			</view>
			<view class="info-item">
				<text class="t1">订单编号:</text>
				<text class="t2">{{orderinfo.ordernum}}</text>
			</view>
			<view class="info-item">
				<text class="t1">支付金额:</text>
				<text class="t2" style="color:#e94745">￥{{orderinfo.totalprice}}</text>
			</view>
		</view>
		<button class="fpay-btn" @tap="topay" data-typeid="1" v-if="wxpayst==1">微信支付</button>
		<button class='fpay-btn' @tap="topay" data-typeid="10" v-if="alipay==1" style="background:#108EE9;margin-top:20rpx">支付宝支付</button>
		<block v-if="moneypay==1">
			<button class="fpay-btn2" @tap="modalinput" v-if="userinfo.haspwd">{{t('余额')}}支付（当前余额¥{{userinfo.money}}）</button>
			<button class="fpay-btn2" @tap="topay" data-typeid="2" v-else>{{t('余额')}}支付（当前余额¥{{userinfo.money}}）</button>
		</block>

		<view v-if="userinfo.haspwd" :class="'weui-demo-dialog ' + (!hiddenmodalput ? 'weui-demo-dialog_show' : '')">
			<view class="weui-mask" @tap="cancel"></view>
			<view class="weui-dialog__wrp">
				<view class="weui-dialog">
					<view class="weui-dialog__hd">
						<view class="weui-dialog__title">支付密码</view>
					</view>
					<view class="weui-dialog__bd">
						<view class="flex-y-center flex-x-center" style="margin:20rpx 130rpx;">
							<text style="font-size:40rpx;color:#000"></text><input type="digit" placeholder="请输入支付密码" @input="getpwd"></input>
						</view> 
					</view>
					<view class="weui-dialog__ft">
						<view class="weui-dialog__btn weui-dialog__btn_default" @tap="cancel">取消</view>
						<view class="weui-dialog__btn" @tap="topay" data-typeid="2">确定</view>
					</view>
				</view>
			</view>
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
			menuindex:-1,
			
			wxpayst:0,
			alipay:0,
			moneypay:0,
			userinfo:{},
      paypwd: '',
      hiddenmodalput: true,
      orderinfo: []
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiWxpay/uppay', {orderid: that.opt.orderid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.error(res.msg);
				} else {
					that.wxpayst = res.wxpayst;
					that.alipay = res.alipay;
					that.moneypay = res.moneypay;
					that.orderinfo = res.orderinfo;
					that.userinfo = res.userinfo;
				}
				that.loaded();
			});
		},
    getpwd: function (e) {
      var paypwd = e.detail.value;
      this.paypwd = paypwd
    },
    cancel: function () {
      this.hiddenmodalput = true
    },
    modalinput: function () {
      this.hiddenmodalput = !this.hiddenmodalput
    },
    topay: function (e) {
      var that = this;
      var typeid = e.currentTarget.dataset.typeid;
      var orderid = this.orderinfo.id;
      if (typeid == 2) {
        that.hiddenmodalput = true;
        app.confirm('确定用' + that.t('余额') + '支付吗?', function () {
          app.post('ApiWxpay/uppay', {orderid: orderid,typeid: typeid,paypwd: that.paypwd}, function (data) {
            if (data.status == 0) {
              app.error(data.msg);
              return;
            }
            if (data.status == 2) {
              //无需付款
              app.success(data.msg);
              setTimeout(function () {
                app.goto('/pages/my/usercenter');
              }, 1000);
              return;
            }
          });
        });
      } else {
        app.post('ApiWxpay/uppay', {orderid: orderid,typeid: typeid}, function (data) {
          if (data.status == 0) {
            app.error(data.msg);
            return;
          }
          if (data.status == 2) {
            //无需付款
            app.success(data.msg);
            setTimeout(function () {
              app.goto('/pages/my/usercenter');
            }, 1000);
            return;
          }
          var opt = data.data;
					uni.requestPayment({
						'timeStamp': opt.timeStamp,
						'nonceStr': opt.nonceStr,
						'package': opt.package,
						'signType': 'MD5',
						'paySign': opt.paySign,
						'success': function (res) {
							app.success('付款完成');
							setTimeout(function () {
								app.goto('/pages/my/usercenter');
							}, 1000);
						},
						'fail': function (res) {}
					});
				})
			}
    }
  }
}
</script>
<style>
.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:20rpx 20rpx;}
.info-item2{ display:flex;width: 100%; background: #fff;padding: 0 3%;padding:20rpx 20rpx; margin-bottom:20rpx;}
.info-item2 .t1{ width:70rpx; }
.info-item2 .t2{ color: #000;}
.info-item2 .x2{ color: #888;}

.info-item{ display:flex;align-items:center;width: 100%; background: #fff;padding: 0 3%;  border-bottom: 1px #f3f3f3 solid;}
.info-item:last-child{border:none}
.info-item .t1{ width: 200rpx; height: 80rpx; line-height: 80rpx; color: #000; }
.info-item .t2{ height: 80rpx;line-height: 80rpx; color: #000;text-align:right;flex:1}

.fpay-btn{ width: 90%; margin: 0 5%; height: 40px; line-height: 40px; margin-top: 20px; float: left; border-radius: 5px; color: #fff; background: #1aac19; border: none; font-size: 15px; }
.fpay-btn2{ width: 90%; margin: 0 5%; height: 40px; line-height: 40px; margin-top: 10px; float: left; border-radius: 5px; color: #fff; background: #e2cc05; border: none; font-size: 15px; }
</style>