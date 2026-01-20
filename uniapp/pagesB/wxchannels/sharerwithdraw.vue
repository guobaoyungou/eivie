<template>
<view class="container">
	<block v-if="isload">
			<form @submit="formSubmit">
					<view class="mymoney" :style="{background:t('color1')}">
							<view class="f1">我的可提现{{t('佣金')}}</view>
							<view class="f2"><text style="font-size:26rpx">￥</text>{{userinfo.commission}}</view>
							<view class="f3" v-if="sysset.commissionrecord_withdrawlog_show == 1" @tap="goto" data-url="sharercommissionlog?st=1"><text>提现记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
					</view>
					<view class="content2">
							<view class="item2"><view class="f1">提现金额(元)</view></view>
							<view class="item3"><view class="f1">￥</view><view class="f2"><input class="input" type="digit" name="money" value="" placeholder="请输入提现金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"></input></view></view>
							<view class="tips-box">
								<view class="item4 tips" v-if="sysset.comwithdrawfee>0 || sysset.comwithdrawmin>0">
										<text v-if="sysset.comwithdrawmin>0" style="margin-right:10rpx">最低提现金额{{sysset.comwithdrawmin}}元 </text>
										<text v-if="sysset.comwithdrawfee>0">提现手续费{{sysset.comwithdrawfee}}% </text>
								</view>
								<view class="item4 tips" v-if="sysset.comwithdrawmul && sysset.comwithdrawmul>0">
										<text style="margin-right:10rpx">提现金额需为{{sysset.comwithdrawmul}}的整数倍</text>
								</view>
							</view>
					</view>
					<view class="withdrawtype">
						<view class="f1">选择提现方式：</view>
						<view class="f2">
							<label class="item" v-if="sysset.withdraw_aliaccount==1" @tap.stop="changeradio" data-paytype="支付宝">
									<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>支付宝</view>
									<view class="radio" :style="paytype=='支付宝' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</label>
							<label class="item" v-if="sysset.withdraw_bankcard==1" @tap.stop="changeradio" data-paytype="银行卡">
									<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>银行卡</view>
									<view class="radio" :style="paytype=='银行卡' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</label>
              <label class="item" v-if="sysset.withdraw_paycode==1" @tap.stop="changeradio" data-paytype="收款码">
              		<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-cash.png'"/>收款码</view>
              		<view class="radio" :style="paytype=='收款码' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
              </label>
						</view>
						<view class="banklist" v-if="selectbank && paytype=='银行卡' && bank">
							  <view class="f1">默认银行卡：{{bank.bankname}} {{bank.bankcardnum}}</view>
								<view class="t2" @tap="goto" data-url="/pagesA/banklist/bank?fromPage=commission">修改</view>
						</view>
					</view>
				
					<button class="btn" :style="{background:t('color1')}" @tap="formSubmit">立即提现</button>
					<view v-if="paytype=='支付宝'" class="textbtn" @tap="goto" data-url="/pagesExt/my/setaliaccount">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
					<view v-if="paytype=='银行卡'" class="textbtn" @tap="goto" data-url="/pagesExt/my/setbankinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
          <view v-if="paytype=='收款码'" class="textbtn" @tap="goto" data-url="/pagesB/my/setpaycode">设置收款码<image :src="pre_url+'/static/img/arrowright.png'" /></view>
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
      money: 0,
      sysset: false,
      paytype: '微信钱包',
			tmplids:[],
			selectbank:false,
			bank:[],
      
      id:0,
      bid:0,
	  pre_url: app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
    var that = this;
		that.opt = app.getopts(opt);
    that.id = that.opt.id || 0;
    that.bid = that.opt.bid || 0;
		that.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiWxChannelsSharer/commissionWithdraw', {id:that.id,bid:that.bid}, function (res) {
				that.loading = false;
        var title = res.bname+that.t('佣金') + '提现';
				uni.setNavigationBarTitle({
					title: title
				});
				var sysset = res.sysset;
				that.sysset = sysset;
				that.tmplids = res.tmplids;
				that.userinfo = res.userinfo;
				var paytype = '';
				if ((!sysset.withdraw_weixin || sysset.withdraw_weixin == 0) && sysset.withdraw_aliaccount == 1) {
					paytype = '支付宝';
				}
				if ((!sysset.withdraw_weixin || sysset.withdraw_weixin == 0) && (!sysset.withdraw_aliaccount || sysset.withdraw_aliaccount == 0) && sysset.withdraw_bankcard==1) {
					paytype = '银行卡';
				}
        if(sysset.withdraw_paycode == 1 && paytype == ''){
        	paytype = '收款码';
        }
				that.paytype = paytype;
				that.selectbank = res.selectbank
				that.bank = res.bank
				that.loaded();
			});
		},
		
    moneyinput: function (e) {
      var usermoney = parseFloat(this.userinfo.commission);
      var money = parseFloat(e.detail.value);
      if (money < 0) {
        app.error('必须大于0');
      } else if (money > usermoney) {
        app.error('可提现' + this.t('佣金') + '不足');
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
      var usermoney = parseFloat(this.userinfo.commission);
      var withdrawmin = parseFloat(this.sysset.withdrawmin); //var formdata = e.detail.value;
			var comwithdrawmul = parseFloat(this.sysset.comwithdrawmul);

      var money = parseFloat(that.money);
      var paytype = this.paytype;

      if (paytype == '') {
        app.error('请选择提现方式');
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

      if (money > usermoney) {
        app.error(that.t('佣金') + '不足');
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
      app.confirm('确定提交？',function(){
        app.showLoading('提交中');
        app.post('ApiWxChannelsSharer/commissionwithdraw', {id:that.id,bid:that.bid,money: money,paytype: paytype}, function (data) {
        	app.showLoading(false);
          if (data.status == 0) {
            app.error(data.msg);
            return;
          } else {
            app.success(data.msg);
            if(that.sysset.commissionrecord_withdrawlog_show == 1){
              that.subscribeMessage(function () {
              setTimeout(function () {
                app.goto('sharercommissionlog?st=1&id='+that.id+'&bid='+that.bid);
              }, 1000);
              });
            }else{
              that.getdata();
              //app.goto('/activity/commission/withdraw?bid='+that.bid)
            }
          }
        });
      })
    }
  }
};
</script>
<style>
.container{display:flex;flex-direction:column;padding-bottom: 40rpx;}
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
.content2 .item4{display:flex;}

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
.textbtn {width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center}
.textbtn image {width:30rpx;height:30rpx}

.withdraw_desc{padding: 30rpx;}
.withdraw_desc .title{font-size: 30rpx;color: #5E5E5E;font-weight: bold;padding: 10rpx 0;}
.withdraw_desc text{width: 100%; line-height: 46rpx;font-size: 24rpx;color: #222222;}
.tips-box{width:94%;margin:0 3%;padding: 20rpx 0;border-top:1px solid #F0F0F0;}
.tips{color:#8C8C8C;font-size:28rpx;line-height: 50rpx;}

.banklist{ padding:0 20rpx 20rpx;margin-left: 10rpx;display: flex; width: 100%; }
.banklist .t2{ line-height: 90rpx;width: 80rpx;text-align: right;}
</style>