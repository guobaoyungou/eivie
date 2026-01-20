<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit">
		<view class="mymoney" :style="{background:t('color1')}">
			<view class="f1">可提现佣金</view>
			<view class="f2"><text style="font-size:26rpx">￥</text>{{userinfo.money}}</view>
			<view class="f3" @tap="goto" data-url="withdrawlog"><text>提现记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text>
			</view>
				<view @tap="tomoney"  class="data_btn flex-xy-center">转到{{t('余额')}}账户<image :src="pre_url+'/static/imgsrc/commission_dw.png'"/></view>
		</view>
		<view class="content2">
			<view class="item2"><view class="f1">提现金额(元)</view></view>
			<view class="item3"><view class="f1">￥</view><view class="f2"><input class="input" type="digit" name="money" value="" placeholder="请输入提现金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"/></view></view>
			<view class="item4" v-if="sysset.withdrawfee>0 || sysset.withdrawmin>0">
				<text v-if="sysset.withdrawmin>0" style="margin-right:10rpx">最低提现金额{{sysset.withdrawmin}}元 </text>
				<text v-if="sysset.withdrawfee>0">提现手续费{{sysset.withdrawfee}}% </text>
			</view>
		</view>
		<view class="withdrawtype">
			<view class="f1">选择提现方式：</view>
			<view class="f2">
				<view class="item" v-if="sysset.withdraw_weixin==1" @tap.stop="changeradio" data-paytype="微信钱包">
					<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'"/>微信钱包</view>
					<view class="radio" :style="paytype=='微信钱包' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
				</view>
				<label class="item" v-if="sysset.withdraw_aliaccount==1" @tap.stop="changeradio" data-paytype="支付宝">
					<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>支付宝</view>
					<view class="radio" :style="paytype=='支付宝' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
				</label>
				<label class="item" v-if="sysset.withdraw_bankcard==1" @tap.stop="changeradio" data-paytype="银行卡">
					<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>银行卡</view>
					<view class="radio" :style="paytype=='银行卡' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
				</label>
			</view>
		</view>
		<button class="btn" form-type="submit" :style="{background:t('color1')}">立即提现</button>
		
		<uni-popup id="dialogInput" ref="dialogInput" type="dialog">
			<uni-popup-dialog mode="input" :title=" '佣金转入' + t('余额')" value="" placeholder="请输入转入金额" @confirm="tomonenyconfirm"></uni-popup-dialog>
		</uni-popup>

			
		<view v-if="paytype=='支付宝'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="mdtxset">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
		<view v-if="paytype=='银行卡'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="mdtxset">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
	</form>
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
      userinfo: [],
      sysset: false,
      paytype: '微信钱包',
      show: 0
    };
  },

  onShow: function (opt) {
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
			app.get('ApiMendianCenter/withdraw', {}, function (res) {
				that.loading = false;
				that.userinfo = res.userinfo;
				that.sysset = res.sysset;
				that.tmplids = res.tmplids;
				var sysset = res.sysset;
				var paytype = '微信钱包';
				if (sysset.withdraw_weixin == 1) {
					paytype = '微信钱包';
				}
				if (!sysset.withdraw_weixin || sysset.withdraw_weixin == 0) {
					paytype = '支付宝';
				}
				if ((!sysset.withdraw_weixin || sysset.withdraw_weixin == 0) && (!sysset.withdraw_aliaccount || sysset.withdraw_aliaccount == 0)) {
					paytype = '银行卡';
				}
				that.paytype = paytype;
				that.loaded();
			});
		},
    moneyinput: function (e) {
      var usermoney = parseFloat(this.userinfo.money);
      var money = parseFloat(e.detail.value);
      if (money < 0) {
        app.error('必须大于0');
      } else if (money > usermoney) {
        app.error('可提现' + this.t('余额') + '不足');
      }
    },
    changeradio: function (e) {
      var that = this;
      var paytype = e.currentTarget.dataset.paytype;
      that.paytype = paytype;
    },
    formSubmit: function (e) {
      var that = this;
			console.log(e.detail.value)
      var usermoney = parseFloat(this.userinfo.money);
      var withdrawmin = parseFloat(this.sysset.withdrawmin); //var formdata = e.detail.value;
      var money = parseFloat(e.detail.value.money);
      var paytype = this.paytype;
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
      if (paytype == '支付宝' && !this.userinfo.aliaccount) {
        app.alert('请先设置支付宝账号', function () {
          app.goto('mdtxset');
        });
        return;
      }
      if (paytype == '银行卡' && (!this.userinfo.bankname || !this.userinfo.bankcarduser || !this.userinfo.bankcardnum)) {
        app.alert('请先设置完整银行卡信息', function () {
          app.goto('mdtxset');
        });
        return;
      }
			app.showLoading('提交中');
      app.post('ApiMendianCenter/withdraw', {money: money,paytype: paytype}, function (res) {
				app.showLoading(false);
        if (res.status == 0) {
					app.alert(res.msg, function () {
						if(res.url) app.goto(res.url);
					});
          return;
        } else {
          app.success(res.msg);
          that.subscribeMessage(function () {
            setTimeout(function () {
              app.goto('withdrawlog');
            }, 1000);
          });
        }
      });
    },
		tomoney: function () {
		  this.$refs.dialogInput.open()
		},
		tomonenyconfirm: function (done, val) {
			console.log(val)
		  var that = this;
		  var money = val;
		  if (money == '' || parseFloat(money) <= 0) {
		    app.alert('请输入转入金额');
		    return;
		  }
		  if (parseFloat(money) > this.userinfo.money) {
		    app.alert('可转佣金不足');
		    return;
		  }
			done();
			that.exchangeSubmit(money)
		},
		exchangeSubmit:function(money){
			var that = this;
			app.showLoading('提交中');
			app.post('ApiMendianCenter/commission2money', {money: money}, function (data) {
				app.showLoading(false);
			  if (data.status == 0) {
			    app.error(data.msg);
			  } else {
			    that.hiddenmodalput = true;
			    app.success(data.msg);
			    setTimeout(function () {
			      that.getdata();
			    }, 1000);
			  }
			});
		},
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}
.mymoney{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.mymoney .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.mymoney .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold}
.mymoney .f3{height:56rpx;padding:0 10rpx 0 20rpx;border-radius: 28rpx 0px 0px 28rpx;background:rgba(255,255,255,0.2);font-size:20rpx;font-weight:bold;color:#fff;display:flex;align-items:center;position:absolute;top:94rpx;right:0}

.content2{width:94%;margin:10rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff}
.content2 .item1{display:flex;width:100%;border-bottom:1px solid #F0F0F0;padding:0 30rpx}
.content2 .item1 .f1{flex:1;font-size:32rpx;color:#333333;font-weight:bold;height:120rpx;line-height:120rpx}
.content2 .item1 .f2{color:#FC4343;font-size:44rpx;font-weight:bold;height:120rpx;line-height:120rpx}

.content2 .item2{display:flex;width:100%;padding:0 30rpx;padding-top:10rpx}
.content2 .item2 .f1{height:80rpx;line-height:80rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:0 30rpx;padding-bottom:20rpx}
.content2 .item3 .f1{height:100rpx;line-height:100rpx;font-size:60rpx;color:#333333;font-weight:bold;margin-right:20rpx}
.content2 .item3 .f2{display:flex;align-items:center;font-size:60rpx;color:#333333;font-weight:bold}
.content2 .item3 .f2 .input{font-size:60rpx;height:100rpx;line-height:100rpx;}
.content2 .item4{display:flex;width:94%;margin:0 3%;border-top:1px solid #F0F0F0;height:100rpx;line-height:100rpx;color:#8C8C8C;font-size:28rpx}

.withdrawtype{width:94%;margin:20rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;margin-top:20rpx;background:#fff}
.withdrawtype .f1{height:100rpx;line-height:100rpx;padding:0 30rpx;color:#333333;font-weight:bold}


.withdrawtype .f2{padding:0 30rpx}
.withdrawtype .f2 .item{border-bottom:1px solid #f5f5f5;height:100rpx;display:flex;align-items:center}
.withdrawtype .f2 .item:last-child{border-bottom:0}
.withdrawtype .f2 .item .t1{flex:1;display:flex;align-items:center;color:#333}
.withdrawtype .f2 .item .t1 .img{width:44rpx;height:44rpx;margin-right:40rpx}

.withdrawtype .f2 .item .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.withdrawtype .f2 .item .radio .radio-img{width:100%;height:100%}

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}

.data_btn{ position: absolute;background:rgba(255,255,255,0.2);color: #fff; right: 0;width: 200rpx; top:180rpx; height:60rpx;line-height: 60rpx; font-size:24rpx; display:flex;align-items: center; }
.data_btn image{height: 24rpx;width: 24rpx;margin-left: 6rpx;}
</style>