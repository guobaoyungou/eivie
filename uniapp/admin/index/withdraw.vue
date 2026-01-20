<template>
<view>
<form report-submit="true" @submit="formSubmit">
<view class="container">
	<image :src="pre_url + '/static/m/images/money.png'" class="img"></image>
	<view class="f1">您的当前余额</view>
	<view class="f2">￥{{binfo.money}}</view>
	<view class="f3">
		<text class="t1">提现金额</text>
		<view class="t2">
			<text class="x1">￥</text>
			<input type="digit" name="money" placeholder="请输入金额" placeholder-style="color:#666;font-size:32rpx" placeholder-class="pclass" @input="moneyinput"></input>
		</view>
	</view>
  <view class="f4" v-if="bset.withdrawfee>0 || bset.withdrawmin>0">
		<text v-if="bset.withdrawmin>0">最低提现金额{{bset.withdrawmin}}元 </text>
		<text v-if="bset.withdrawfee>0">提现手续费{{bset.withdrawfee}}% </text>
	</view>
	<view class="withdrawtype flex flex-x-center flex-y-center" style="width:100%;margin:20rpx 0">
		<view style="font-size:26rpx;margin-right:10rpx">提现方式：</view>
		<radio-group class="radio-group flex" name="paytype">
			<label class="radio flex-y-center" v-if="bset.withdraw_weixin==1">
				<radio value="微信钱包" checked="checked"></radio>微信钱包
			</label>
			<label class="radio flex-y-center" v-if="bset.withdraw_aliaccount==1">
				<radio value="支付宝" v-if="!bset.withdraw_weixin || bset.withdraw_weixin==0" checked="checked"></radio>
				<radio value="支付宝" v-else></radio>支付宝
			</label>
			<label class="radio flex-y-center" v-if="bset.withdraw_bankcard==1">
				<radio value="银行卡" v-if="(!bset.withdraw_weixin || bset.withdraw_weixin==0)&&(!bset.withdraw_aliaccount || bset.withdraw_aliaccount==0)" checked="checked"></radio>
				<radio value="银行卡" v-else></radio>银行卡
			</label>
		</radio-group>
	</view>
	<button class="btn" form-type="submit">立即提现</button>
	<view class="f0" @tap="goto" data-url="moneylog?st=1">
		<text>查看提现记录</text>
		<image :src="pre_url+'/static/img/arrowright.png'"></image>
	</view>
</view>
</form>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      binfo: [],
      money: 0,
      bset: false,
      pre_url:app.globalData.pre_url,
    };
  },

  onLoad: function (options) {
    var that = this;
    app.get('businessht/withdraw', {}, function (res) {
      that.setData(res);
    });
  },
  methods: {
    moneyinput: function (e) {
      var usermoney = parseFloat(this.binfo.money);
      var money = parseFloat(e.detail.value);

      if (money < 0) {
        app.error('必须大于0');
      } else if (money > usermoney) {
        app.error('可提现余额不足');
        return {
          value: usermoney
        };
      } else {
        this.setData({
          money: money
        });
      }
    },
    formSubmit: function (e) {
      var that = this;
      var usermoney = parseFloat(this.binfo.money);
      var withdrawmin = parseFloat(this.bset.withdrawmin);
      var formdata = e.detail.value;
      var money = parseFloat(formdata.money);
      var paytype = formdata.paytype;

      if (isNaN(money) || money <= 0) {
        app.error('提现金额必须大于0');
        return;
      }

      if (withdrawmin > 0 && money < withdrawmin) {
        app.error('提现金额必须大于¥' + withdrawmin);
        return;
      }

      if (money > usermoney) {
        app.error('余额不足');
        return;
      }

      if (paytype == '支付宝' && !this.binfo.aliaccount) {
        app.alert('请先设置支付宝账号', function () {
          app.goto('txset');
        });
        return;
      }

      if (paytype == '银行卡' && (!this.binfo.bankname || !this.binfo.bankcarduser || !this.binfo.bankcardnum)) {
        app.alert('请先设置完整银行卡信息', function () {
          app.goto('txset');
        });
        return;
      }
			
			app.showLoading('提交中');
      app.post('ApiBusinessht/withdraw', {
        money: money,
        paytype: paytype
      }, function (data) {
				app.showLoading(false);
        if (data.status == 0) {
          app.error(data.msg);
          return;
        } else {
          app.success(data.msg);
          setTimeout(function () {
            app.goto('moneylog?st=2');
          }, 1000);
        }
      });
    }
  }
};
</script>
<style>
.container{ width:100%;display:flex;flex-direction:column;align-items:center}
.img{width:160rpx;height:160rpx;margin-top:100rpx;}
.f1{color: #555;font-size: 28rpx;margin-top:30rpx;}
.f2{color: #555;font-size: 48rpx;margin-top: 10px;}
.f3{width:380rpx;display:flex;height: 80rpx;line-height: 80rpx;margin-top:26rpx;}
.f3 .t1{ font-size:32rpx;width:150rpx}
.f3 .t2{ flex:1;border-bottom:1px solid #888;display:flex;align-items:center;font-size:40rpx}
.f3 .t2 .x1{color:#000}
.f3 .t2 input{ border: 0;color: #000;flex:1;}
.btn{ height: 80rpx;line-height: 80rpx;background: #31cd00;width:500rpx;border-radius: 10rpx;margin-top: 20rpx;color: #fff;font-size: 32rpx;}
.f0{margin-top:16rpx;height:60rpx;line-height:60rpx;overflow: hidden;color: #666;font-size:32rpx;display:flex;align-items:center;}
.f0 image{width: 16px;height: 16px;}
.f4{margin-top:20rpx;color:red}

.radio radio{transform:scale(0.7);}
.pclass{font-size:32rpx;}

</style>