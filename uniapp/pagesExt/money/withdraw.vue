<template>
<view class="container">
	<block v-if="isload">
        <form @submit="formSubmit">
            <view class="mymoney" :style="{background:t('color1')}">
                <view class="f1">我的{{t('余额')}}</view>
                <view class="f2"><text style="font-size:26rpx">￥</text>{{userinfo.money}}</view>
                <view class="f3" @tap="goto" data-url="moneylog?st=2"><text>提现记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
            </view>
            <view class="content2">
                <view class="item2"><view class="f1">提现金额(元)</view></view>
                <view class="item3"><view class="f1">￥</view><view class="f2"><input class="input" type="digit" name="money" value="" placeholder="请输入提现金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"/></view></view>
                <view class="item4">
                    <view v-if="sysset.withdrawmin>0">最低提现金额{{sysset.withdrawmin}}元 </view>
                    <view v-if="sysset.withdrawfee>0">提现手续费{{sysset.withdrawfee}}% </view>
                    <view v-if="sysset.withdrawmul>0">提现金额需为{{sysset.withdrawmul}}倍数 </view>
                </view>
            </view>
            <view class="withdrawtype">
                <view class="f1">选择提现方式：</view>
                <view class="f2">
                    <view class="item" v-if="sysset.withdraw_weixin==1" @tap.stop="changeradio" data-paytype="微信钱包">
                        <view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'"/>微信钱包</view>
                        <view class="radio" :style="paytype=='微信钱包' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                    </view>
                    <view class="item" v-if="sysset.withdraw_aliaccount==1" @tap.stop="changeradio" data-paytype="支付宝">
                        <view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>支付宝</view>
                        <view class="radio" :style="paytype=='支付宝' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                    </view>
                    <view class="item" v-if="sysset.withdraw_bankcard==1" @tap.stop="changeradio" data-paytype="银行卡">
                        <view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>银行卡</view>
                        <view class="radio" :style="paytype=='银行卡' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                    </view>
										<view class="item" v-if="sysset.withdraw_adapay==1" @tap.stop="changeradio" data-paytype="汇付天下银行卡">
												<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>汇付天下银行卡</view>
												<view class="radio" :style="paytype=='汇付天下银行卡' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
										</view>
										<view class="item" v-if="sysset.withdraw_aliaccount_xiaoetong==1" @tap.stop="changeradio" data-paytype="小额通支付宝">
												<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>小额通支付宝</view>
												<view class="radio" :style="paytype=='小额通支付宝' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
										</view>
										<view class="item" v-if="sysset.withdraw_bankcard_xiaoetong==1" @tap.stop="changeradio" data-paytype="小额通银行卡">
												<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>小额通银行卡</view>
												<view class="radio" :style="paytype=='小额通银行卡' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
										</view>
                    <view class="item" v-if="sysset.withdraw_aliaccount_linghuoxin==1" @tap.stop="changeradio" data-paytype="灵活薪支付宝">
                    		<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>灵活薪支付宝</view>
                    		<view class="radio" :style="paytype=='灵活薪支付宝' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                    </view>
                    <view class="item" v-if="sysset.withdraw_bankcard_linghuoxin==1" @tap.stop="changeradio" data-paytype="灵活薪银行卡">
                    		<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>灵活薪银行卡</view>
                    		<view class="radio" :style="paytype=='灵活薪银行卡' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                    </view>
                    <view class="item" v-if="sysset.withdraw_bankcard_allinpayYunst==1" @tap.stop="changeradio" data-paytype="通联支付银行卡">
                    		<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>通联支付银行卡</view>
                    		<view class="radio" :style="paytype=='通联支付银行卡' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                    </view>
                    <view class="item" v-if="sysset.withdraw_paycode==1" @tap.stop="changeradio" data-paytype="收款码">
                    		<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>收款码</view>
                    		<view class="radio" :style="paytype=='收款码' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                    </view>
                    <view class="item" v-if="sysset.custom_status==1 && sysset.custom_name" @tap.stop="changeradio" :data-paytype="sysset.custom_name">
                    		<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>{{sysset.custom_name}}</view>
                    		<view class="radio" :style="paytype == sysset.custom_name ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
                    </view>
                </view>
								
								<view class="banklist" v-if="selectbank && paytype=='银行卡' && bank">
									  <view class="f1">默认银行卡：{{bank.bankname}} {{bank.bankcardnum}}</view>
										<view class="t2" @tap="goto" data-url="/pagesA/banklist/bank?fromPage=commission">修改</view>
								</view>
            </view>
            <button class="btn" @tap="formSubmit" :style="{background:t('color1')}">立即提现</button>
            <view v-if="paytype=='支付宝'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesExt/my/setaliaccount">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
            <view v-if="paytype=='银行卡' && !sysset.withdraw_huifu" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesExt/my/setbankinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
						<view v-if="paytype=='银行卡' && sysset.withdraw_huifu == 1" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesExt/my/sethuifuinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
						<view v-if="paytype=='小额通支付宝'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesExt/my/setaliaccount">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
						<view v-if="paytype=='小额通银行卡'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesExt/my/setbankinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
            <view v-if="paytype=='灵活薪支付宝'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesExt/my/setaliaccount">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
            <view v-if="paytype=='灵活薪银行卡'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesExt/my/setbankinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
            <!-- <view v-if="paytype=='灵活薪银行卡'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesExt/my/setbankinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view> -->
            <view v-if="paytype=='收款码'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesB/my/setpaycode">设置收款码<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
            <view v-if="paytype == sysset.custom_name" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesB/my/setcustomaccount">设置{{sysset.custom_name}}账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
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
      paytype: '',
      show: 0,
			money:0,
			tmplids:[],
			selectbank:false,
			bank:[],
      pre_url: app.globalData.pre_url,
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
			app.get('ApiMy/withdraw', {}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: that.t('余额') + '提现'
				});
				if(res.status == 0){
					app.alert(res.msg);
					return;
				}
				that.userinfo = res.userinfo;
				that.sysset = res.sysset;
				that.tmplids = res.tmplids;
				var sysset = res.sysset;
				var paytype = '';
				if (sysset.withdraw_weixin == 1) {
					paytype = '微信钱包';
				}
				if ((!sysset.withdraw_weixin || sysset.withdraw_weixin == 0) && sysset.withdraw_aliaccount == 1) {
					paytype = '支付宝';
				}
				if ((!sysset.withdraw_weixin || sysset.withdraw_weixin == 0) && (!sysset.withdraw_aliaccount || sysset.withdraw_aliaccount == 0) && sysset.withdraw_bankcard == 1) {
					paytype = '银行卡';
				}
        if(sysset.withdraw_aliaccount_linghuoxin == 1 && paytype == ''){
        	paytype = '灵活薪支付宝';
        }
        if(sysset.withdraw_bankcard_linghuoxin == 1 && paytype == ''){
        	paytype = '灵活薪银行卡';
        }
        if(sysset.withdraw_bankcard_allinpayYunst == 1 && paytype == ''){
        	paytype = '通联支付银行卡';
        }
        if(sysset.withdraw_paycode == 1 && paytype == ''){
        	paytype = '收款码';
        }
        if(sysset.withdraw_paycode == 1 && paytype == ''){
        	paytype = sysset.custom_name;
        }
				that.paytype = paytype;
				that.selectbank = res.selectbank
				that.bank = res.bank
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
			this.money = money;
    },
    changeradio: function (e) {
      var that = this;
      var paytype = e.currentTarget.dataset.paytype;
      that.paytype = paytype;
    },
    formSubmit: function () {
      var that = this;
      var usermoney = parseFloat(this.userinfo.money);
      var withdrawmin = parseFloat(this.sysset.withdrawmin); //var formdata = e.detail.value;
      var withdrawmul = parseFloat(this.sysset.withdrawmul); 
      var money = parseFloat(that.money);
      var paytype = this.paytype;
			if (paytype == ''){
				app.error('暂无可用提现方式');
				return;
			}
			
      if (isNaN(money) || money <= 0) {
        app.error('提现金额必须大于0');
        return;
      }
      if (withdrawmin > 0 && money < withdrawmin) {
        app.error('提现金额必须大于¥' + withdrawmin);
        return;
      }
			
			if(!isNaN(withdrawmul) && withdrawmul>0){
				if(!Number.isInteger((money*100)/(withdrawmul*100))){
					app.error('提现金额需为' + withdrawmul+ '的整数倍');
					return;
				}
			}
      if (money > usermoney) {
        app.error(this.t('余额') + '不足');
        return;
      }
      if (paytype == '支付宝' && !this.userinfo.aliaccount) {
        app.alert('请先设置支付宝账号', function () {
          app.goto('/pagesExt/my/setaliaccount');
        });
        return;
      }
			if(this.selectbank){
					if (paytype == '银行卡' && !this.bank){
						app.alert('请先设置完整银行卡信息', function () {
						  app.goto('/pagesA/banklist/bankadd');
						});
					}
			}else{
				if (paytype == '银行卡' && (!this.userinfo.bankname || !this.userinfo.bankcarduser || !this.userinfo.bankcardnum)) {
					app.alert('请先设置完整银行卡信息', function () {
						app.goto('/pagesExt/my/setbankinfo');
					});
					return;
				}
			}
      if(paytype == '汇付天下银行卡' && (this.userinfo.to_set_adapay && this.userinfo.to_set_adapay==1)) {
        app.alert('请先设置汇付天银行卡信息', function () {
          app.goto('/pagesExt/my/setadapayinfo');
        });
        return;
      }
      //自定义提现方式
      if(paytype == this.sysset.custom_name && (!this.userinfo.customaccountname || !this.userinfo.customaccount || !this.userinfo.customtel)){
        app.alert('请先设置'+ this.sysset.custom_name +'账户信息', function () {
          app.goto('/pagesB/my/setcustomaccount');
        });
        return;
      }
	  var wx_max_money = parseFloat(that.sysset.wx_max_money);
	  if(paytype=='微信钱包' && !this.userinfo.realname && money>=wx_max_money){
		  app.alert('请先设置姓名', function () {
		    app.goto('/pagesExt/my/setrealname');
		  });
		  return;
	  }
      app.showLoading('提交中');
      app.post('ApiMy/withdraw', {money: money,paytype: paytype}, function (res) {
				app.showLoading(false);
        if (res.status == 0) {
          app.error(res.msg);
          return;
        } else {
			if(res.need_confirm==1 && res.id){
				//需要用户主动确认收款
				return that.shoukuan(res.id,'member_withdrawlog','moneylog?st=2');
			}else{
				app.success(res.msg);
				that.subscribeMessage(function () {
				  setTimeout(function () {
				    app.goto('moneylog?st=2');
				  }, 1000);
				});
			}
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
.content2 .item4{display:flex;width:94%;margin:0 3%;border-top:1px solid #F0F0F0;line-height:50rpx;color:#8C8C8C;font-size:28rpx; flex-direction: column;}

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

.banklist{ padding:0 20rpx 20rpx;margin-left: 10rpx;display: flex; width: 100%; }
.banklist .t2{ line-height: 90rpx;width: 80rpx;text-align: right;}
</style>