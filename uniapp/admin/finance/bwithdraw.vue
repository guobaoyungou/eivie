<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit">
		<view class="mymoney" :style="{background:t('color1')}">
			<view class="f1">可提现余额</view>
			<view class="f2"><text style="font-size:26rpx">￥</text>{{userinfo.money}}</view>
			<view class="f3" @tap="goto" data-url="bwithdrawlog"><text>提现记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
		</view>
		<view class="content2">
			<view class="item2"><view class="f1">提现金额(元)</view></view>
			<view class="item3"><view class="f1">￥</view><view class="f2"><input class="input" type="digit" name="money" value="" placeholder="请输入提现金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"/></view></view>
			<view class="item4" v-if="sysset.withdrawfee>0 || sysset.withdrawmin>0">
				<text v-if="sysset.withdrawmin>0" style="margin-right:10rpx">最低提现金额{{sysset.withdrawmin}}元 </text>
        <block v-if="paytype == '通联支付银行卡'">
          <text v-if="sysset.yunstwithdrawfeetype && sysset.yunstwithdrawfeetype == 1 && sysset.yunstwithdrawfee>0">
            提现手续费{{sysset.yunstwithdrawfee}}元
          </text>
          <text v-else-if="sysset.withdrawfee>0">提现手续费{{sysset.withdrawfee}}% </text>
        </block>
        <block v-else>
          <text v-if="sysset.withdrawfee>0">提现手续费{{sysset.withdrawfee}}% </text>
        </block>
			</view>
		</view>
		<view class="content2" v-if="sysset.smscode">
			<view class="item2"><view class="f1">手机号</view></view>
			<view class="item2 item-form">
				<input class="input" type="text" name="tel" v-model="tel" placeholder="请输入手机号" placeholder-style="color:#999;"/>
			</view>
			<view class="item2"><view class="f1">验证码</view></view>
			<view class="item2 item-form">
				<input class="input" type="text" name="smscode" v-model="smscode" placeholder="请输入短信验证码" placeholder-style="color:#999;"/>
				<view>
					<button class="mini-btn" type="default" size="mini" @tap="sendcode" :disabled="!!smsdjs" :style="{color: smsdjs ? '#999' : t('color1')}">
						{{ smsdjs || '获取验证码' }}
					</button>
				</view>
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
        <label class="item" v-if="sysset.withdraw_bankcard_allinpayYunst==1" @tap.stop="changeradio" data-paytype="通联支付银行卡">
        	<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>通联支付银行卡</view>
        	<view class="radio" :style="paytype=='通联支付银行卡' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
        </label>
				<label class="item" v-if="sysset.withdraw_huifu==1" @tap.stop="changeradio" data-paytype="汇付斗拱">
					<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>汇付斗拱</view>
					<view class="radio" :style="paytype=='汇付斗拱' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
				</label>
				<label class="item" v-if="sysset.withdraw_huifu_dianzhang==1" @tap.stop="changeradio" data-paytype="店长汇付打款">
					<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>店长汇付打款</view>
					<view class="radio" :style="paytype=='店长汇付打款' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
				</label>
        <label class="item" v-if="sysset.withdraw_business_admin_money==1" @tap.stop="changeradio" data-paytype="商家管理员余额">
          <view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>商家管理员余额</view>
          <view class="radio" :style="paytype=='商家管理员余额' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
        </label>
			</view>
		</view>
		<button class="btn" form-type="submit" :style="{background:t('color1')}">立即提现</button>
		<block v-if="can_edit_account==1">
			<view v-if="paytype=='支付宝'" style="width:100%;margin-top:40rpx;margin-bottom:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/admin/finance/txset">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
			<view v-if="paytype=='银行卡' && !sysset.withdraw_huifu  && !sysset.withdraw_huifu_dianzhang" style="width:100%;margin-top:40rpx;margin-bottom:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/admin/finance/txset">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
		</block>
	</form>
  <view class="withdraw_desc" v-if="sysset.withdraw_desc">
      <view class="title">说明</view>
      <text>{{sysset.withdraw_desc}}</text>
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
			
      userinfo: [],
      sysset: false,
      paytype: '微信钱包',
      show: 0,
      pre_url: app.globalData.pre_url,
      tel:'',
      smscode:'',
      smsdjs:'',
	  can_edit_account:0,//是否可以修改提现账户
    };
  },
  onLoad: function (opt) {
  	this.opt = app.getopts(opt);
  },
  onShow: function () {
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminFinance/bwithdraw', {}, function (res) {
				that.loading = false;
				that.userinfo = res.userinfo;
				that.sysset = res.sysset;
				that.tmplids = res.tmplids;
				var sysset = res.sysset;
				var paytype = '';
				if (sysset.withdraw_weixin == 1 && paytype == '') {
					paytype = '微信钱包';
				}
				if (sysset.withdraw_aliaccount == 1 && paytype == '') {
					paytype = '支付宝';
				}
				if (sysset.withdraw_bankcard == 1 && paytype == '') {
					paytype = '银行卡';
				}
        if(sysset.withdraw_bankcard_allinpayYunst == 1 && paytype == ''){
        	paytype = '通联支付银行卡';
        }
				if(sysset.withdraw_huifu == 1 && paytype == ''){
					paytype = '汇付斗拱';
				}
				if(sysset.withdraw_huifu_dianzhang == 1 && paytype == ''){
					paytype = '店长汇付打款';
				}
				that.paytype = paytype;
				that.can_edit_account = res.can_edit_account || 0;
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
	  if (paytype == '支付宝' &&  this.userinfo.aliaccountname =='' ) {
	    app.alert('请先设置支付宝户名', function () {
	      app.goto('txset');
	    });
	    return;
	  }
      if (paytype == '支付宝' && !this.userinfo.aliaccount ) {
        app.alert('请先设置支付宝账号', function () {
          app.goto('txset');
        });
        return;
      }
      if (paytype == '银行卡' && (!this.userinfo.bankname || !this.userinfo.bankcarduser || !this.userinfo.bankcardnum)) {
        app.alert('请先设置完整银行卡信息', function () {
          app.goto('txset');
        });
        return;
      }
			if(that.sysset && that.sysset.smscode){
				if(!that.tel){
					return app.error('请输入手机号');
				}
				if(!that.smscode){
					return app.error('请输入短信验证码');
				}
			}

      //提现支付现金
      if(that.sysset.withdrawfee_cash_status && that.sysset.withdrawfee_cash_rate > 0){
        var cash = ((that.sysset.withdrawfee_cash_rate/100) * money)
        cash = ( parseInt( cash * 100 ) / 100 ).toFixed(2);//不四舍五入

        if(cash > 0){
          var cash_msg = '提现需要支付提现金额'+that.sysset.withdrawfee_cash_rate+'%的现金';
        }else {
          var cash_msg = '确定提现吗？';
        }

        app.confirm(cash_msg, function () {
          app.showLoading('提交中');
          app.post('ApiAdminFinance/withdrawfeeCashOrder', {money: money,opt:{money: money,paytype: paytype,tel:that.tel,smscode:that.smscode}}, function (data) {
            if ((data.status == 1 && data.payorderid) || data.status == 2) {

              app.post('ApiAdminFinance/bwithdraw', {money: money,paytype: paytype,tel:that.tel,smscode:that.smscode,payorderid:data.payorderid}, function (res) {
                app.showLoading(false);
                if (res.status == 0) {
                  app.alert(res.msg, function () {
                    if(res.url) app.goto(res.url);
                  });
                  return;
                } else {
                  if(res.need_confirm==1 && res.id){
                    //需要用户主动确认收款
                    return that.shoukuan(res.id,'business_withdrawlog','/admin/finance/index');
                  }else{
                    if(data.status == 2){
                      app.success(res.msg);
                      that.subscribeMessage(function () {
                        setTimeout(function () {
                          app.goto('index');
                        }, 1000);
                      });
                    }else {
                      app.goto('/pagesExt/pay/pay?id=' + data.payorderid,'redirectTo');
                    }
                  }
                }
              });
            } else {
              app.error(data.msg);
              return;
            }
          });
        });
      }else {
        app.showLoading('提交中');
        app.post('ApiAdminFinance/bwithdraw', {money: money,paytype: paytype,tel:that.tel,smscode:that.smscode}, function (res) {
          app.showLoading(false);
          if (res.status == 0) {
            app.alert(res.msg, function () {
              if(res.url) app.goto(res.url);
            });
            return;
          } else {
            if(res.need_confirm==1 && res.id){
              //需要用户主动确认收款
              return that.shoukuan(res.id,'business_withdrawlog','/admin/finance/index');
            }else{
              app.success(res.msg);
              that.subscribeMessage(function () {
                setTimeout(function () {
                  app.goto('index');
                }, 1000);
              });
            }
          }
        });
      }
    },
		sendcode: function () {
		  var that = this;
		  var tel = that.tel;
		  // 校验手机号是否为空
		  if (!tel) {
		    app.alert('请输入手机号');
		    return;
		  }
		
		  // 校验手机号格式（简单校验）
		  var phoneReg = /^1[3-9]\d{9}$/;
		  if (!phoneReg.test(tel)) {
		    app.alert('请输入正确的手机号');
		    return;
		  }
		
		  // 防止重复点击
		  if (that.smsdjs) {
		    return;
		  }
		
		  app.showLoading('发送中');
		  app.post("ApiIndex/sendsms", { tel: tel,aid:1}, function (res) {
		    app.showLoading(false);
		    if (res.status == 1) {
		      app.success('验证码已发送');
		      
		      // 启动倒计时
		      var count = 120; // 倒计时时长
		      that.smsdjs = count + 's后重试';
		      var timer = setInterval(function () {
		        count--;
		        if (count > 0) {
		          that.smsdjs = count + 's后重试';
		        } else {
		          clearInterval(timer);
		          that.smsdjs = '';
		        }
		      }, 1000);
		    } else {
		      app.error(res.msg || '验证码发送失败');
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

.withdraw_desc{padding: 30rpx;}
.withdraw_desc .title{font-size: 30rpx;color: #5E5E5E;font-weight: bold;padding: 10rpx 0;}
.withdraw_desc text{width: 100%; line-height: 46rpx;font-size: 24rpx;color: #222222;}
.item-form{height: 80rpx;line-height: 80rpx;display: flex;align-items: center;justify-content: space-between;border-bottom: 1px solid #F0F0F0;}
.sendcode{border: 1px solid #C9C9C9;background-color: #fff;color: #555;display: inline-block;padding: 0 20rpx;font-size: 24rpx;}
.content2 .item-form:last-child{margin-bottom: 10rpx;border:none}
</style>