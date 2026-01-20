<template>
<view class="container">
	<block v-if="isload">
        <form @submit="formSubmit">
            <view class="mymoney" :style="{background:t('color1')}">
                <view class="f1">我的业绩</view>
                <view class="f2">{{userinfo.rechargeyj_money}}</view>
                <view class="f3" @tap="goto" data-url="log?st=1"><text>提现记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
            </view>
            <view v-if="sysset && sysset.rechargeyj_withdraw">
              <view v-if="yj_tip || sysset.rechargeyj_withdrawmin || sysset.rechargeyj_withdrawfee" class="content2" style="display: block;">
                <view class="item4" style="display: block;height: auto;line-height: 50rpx;padding: 10rpx 0;">
                    <view v-if="yj_tip">{{yj_tip}}</view>
                    <view v-if="sysset.rechargeyj_withdrawmin>0" style="margin-right:10rpx">最低提现金额{{sysset.rechargeyj_withdrawmin}}元 </view>
                    <view v-if="sysset.rechargeyj_withdrawfee>0">提现手续费{{sysset.rechargeyj_withdrawfee}}% </view>
                </view>
              </view>
            </view>
            <view v-if="sysset && sysset.rechargeyj_withdraw == 1">
              <!-- <view class="content2">
                  <view class="item2"><view class="f1">提现金额(元)</view></view>
                  <view class="item3"><view class="f1">￥</view><view class="f2"><input class="input" type="digit" name="money" value="" placeholder="请输入提现金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"/></view></view>
                  <view class="item4" v-if="sysset.rechargeyj_withdrawfee>0 || sysset.rechargeyj_withdrawmin>0">
                      <text v-if="sysset.rechargeyj_withdrawmin>0" style="margin-right:10rpx">最低提现金额{{sysset.rechargeyj_withdrawmin}}元 </text>
                      <text v-if="sysset.rechargeyj_withdrawfee>0">提现手续费{{sysset.rechargeyj_withdrawfee}}% </text>
                  </view>
              </view> -->
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
              <button class="btn" @tap="formSubmit" :style="{background:t('color1')}">立即提现</button>
            </view>
            <view v-if="paytype=='支付宝'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesExt/my/setaliaccount">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
            <view v-if="paytype=='银行卡'" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesExt/my/setbankinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
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
            
            userinfo: [],
            sysset: false,
            paytype: '微信钱包',
            show: 0,
            money:0,
            tmplids:[],
            yj_tip:'',
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
          app.get('ApiMy/rechargeyj_withdraw', {}, function (res) {
            if(res.status == 1){
                that.loading = false;
                uni.setNavigationBarTitle({
                  title: '业绩提现'
                });
                that.userinfo = res.userinfo;
                that.sysset   = res.sysset;
                that.tmplids  = res.tmplids;
                that.yj_tip   = res.yj_tip;
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
            }else{
                app.error(res.msg);
            }
          });
        },
        moneyinput: function (e) {
            var usermoney = parseFloat(this.userinfo.rechargeyj_money);
            var money = parseFloat(e.detail.value);
            if (money < 0) {
                app.error('必须大于0');
            } else if (money > usermoney) {
                app.error('可提现业绩收益不足');
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
            var usermoney = parseFloat(this.userinfo.rechargeyj_money);
            var withdrawmin = parseFloat(this.sysset.rechargeyj_withdrawmin); //var formdata = e.detail.value;
            var money = parseFloat(that.money);
            var paytype = this.paytype;
            /* if (isNaN(money) || money <= 0) {
                app.error('提现金额必须大于0');
                return;
            }
            if (withdrawmin > 0 && money < withdrawmin) {
                app.error('提现金额必须大于¥' + withdrawmin);
                return;
            }
            if (money > usermoney) {
                app.error('业绩不足');
                return;
            } */
            if (paytype == '支付宝' && !this.userinfo.aliaccount) {
                app.alert('请先设置支付宝账号', function () {
                  app.goto('/pagesExt/my/setaliaccount');
                });
                return;
            }
            if (paytype == '银行卡' && (!this.userinfo.bankname || !this.userinfo.bankcarduser || !this.userinfo.bankcardnum)) {
                app.alert('请先设置完整银行卡信息', function () {
                  app.goto('/pagesExt/my/setbankinfo');
                });
                return;
            }
            app.confirm('确定要删除该订单吗?', function () {
              app.showLoading('提交中');
              app.post('ApiMy/rechargeyj_withdraw', {money: money,paytype: paytype}, function (res) {
                  app.showLoading(false);
                  if (res.status == 1) {
                    app.success(res.msg);
                    that.subscribeMessage(function () {
                      setTimeout(function () {
                        app.goto('log?st=1');
                      }, 1000);
                    });
                    
                  } else {
                    app.error(res.msg);
                    return;
                  }
              });
            })
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

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold;margin-bottom: 40rpx;}
.opbtn2{width: 90%;margin: 0 5%;margin-top: 20rpx;height: 80rpx;line-height: 80rpx;border:1px solid #eee;background:#fff;margin-bottom: 20rpx;}
</style>