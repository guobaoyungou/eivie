<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit">
		<view class="mymoney" :style="{background:'#06A051'}">
			<view class="f1">可提现余额</view>
			<view class="f2"><text style="font-size:26rpx">￥</text>{{userinfo.money}}</view>
			<view class="f3" @tap="goto" data-url="moneylog?st=2"><text>提现记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
		</view>
		<view class="content2">
			<view class="item2"><view class="f1">提现金额(元)</view></view>
			<view class="item3"><view class="f1">￥</view><view class="f2"><input class="input" type="digit" name="money" value="" placeholder="请输入提现金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"/></view></view>
			<view class="item4" v-if="sysset.withdrawfee>0 || sysset.withdrawmin>0">
				<text v-if="sysset.withdrawmin>0" style="margin-right:10rpx">最低提现金额{{sysset.withdrawmin}}元 </text>
				<text v-if="sysset.withdrawfee>0">提现手续费{{sysset.withdrawfee}}% </text>
			</view>
		</view>
		
		<block >
			<view class="withdrawtype">
				<view class="f1">选择提现方式：</view>
				<view class="f2">
					
					<view class="item"  @tap.stop="changeradio" data-paytype="微信钱包" v-if="  sysset.withdraw_weixin==1"> 
						<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'"/>微信钱包</view>
						<view class="radio" :style="paytype=='微信钱包' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
					</view>
					<view class="item2" v-if="sysset.withdraw_weixincode==1">
						<view class="pay-item"  @tap.stop="changeradio" data-paytype="微信收款码">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'"/>微信收款码</view>
							<view class="radio" :style="paytype=='微信收款码' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<view class="upload-img flex-xy-center" v-if="paytype=='微信收款码'">
							<image class="upload-img-class" :src="wechat_payment_code" @click="previewImage(wechat_payment_code)" v-if="wechat_payment_code"></image>
							<view class="upload-icon-view flex-xy-center" :data-key="'wechat_payment_code'" @tap="uploadimg" v-else>
								<image class="icon-class" :src="pre_url+'/static/img/deposit/xiangjixiao.png'"></image>
							</view>
							<view class="close-view" @click="wechat_payment_code = ''" v-if="wechat_payment_code">
								<image :src="pre_url+'/static/img/ico-del.png'"></image>
							</view>
						</view>
					</view>
					<view class="item2"  v-if="sysset.withdraw_alipaycode==1">
						<view class="pay-item" @tap.stop="changeradio" data-paytype="支付宝收款码">
							<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-alipay.png'"/>支付宝收款码</view>
							<view class="radio" :style="paytype=='支付宝收款码' ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<view class="upload-img flex-xy-center" v-if="paytype=='支付宝收款码'">
							<image class="upload-img-class" :src="alipay_payment_code" @click="previewImage(alipay_payment_code)" v-if="alipay_payment_code"></image>
							<view class="upload-icon-view flex-xy-center" :data-key="'alipay_payment_code'" @tap="uploadimg" v-else>
								<image class="icon-class" :src="pre_url+'/static/img/deposit/xiangjixiao.png'"></image>
							</view>
							<view class="close-view" @click="alipay_payment_code = ''" v-if="alipay_payment_code">
								<image :src="pre_url+'/static/img/ico-del.png'"></image>
							</view>
						</view>
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
		</block>
		
		<button class="btn" form-type="submit" :style="{background:'#06A051'}">立即提现</button>
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
      pre_url: app.globalData.pre_url,
	  wechat_payment_code:'',
	  alipay_payment_code:''
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
			app.get('ApiPeisong/withdraw', {}, function (res) {
				that.loading = false;
				that.userinfo = res.userinfo;
				that.sysset = res.sysset;
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
				
					
				var paytype = '微信收款码';
				if (sysset.withdraw_weixin == 1) {
					paytype = '微信收款码';
				}
				if (!sysset.withdraw_weixin || sysset.withdraw_weixin == 0) {
					paytype = '支付宝收款码';
				}
				if(that.userinfo.wechat_payment_code){
					that.wechat_payment_code = that.userinfo.wechat_payment_code
				}
				if(that.userinfo.alipay_payment_code){
					that.alipay_payment_code = that.userinfo.alipay_payment_code
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
	uploadimg(e){
		var that = this;
		var field = e.currentTarget.dataset.key;
		var pics = that[field]
		if(!pics) pics = [];
		var paytype = that.paytype;
		console.log(paytype);
		app.chooseImage(function(urls){
			for(var i=0;i<urls.length;i++){
				pics.push(urls[i]);
			}	
			if(field == 'wechat_payment_code') that.wechat_payment_code = pics[0];
			if(field == 'alipay_payment_code') that.alipay_payment_code = pics[0];
			that.paytype = paytype;
			console.log(that.paytype);
		},1)
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
          app.goto('setinfo');
        });
        return;
      }
	  if(this.sysset.bid && this.sysset.bid > 0){
		  var wechat_payment_code = this.wechat_payment_code;
		  var alipay_payment_code = this.alipay_payment_code;
		if(paytype == '微信收款码' && wechat_payment_code.length <= 0 ){
			app.alert('请上传微信收款码');
			return;
		}
		if(paytype == '支付宝收款码' && this.alipay_payment_code  <= 0 ){
			app.alert('请上传支付宝收款码');
			return;
		}
		if(paytype == '支付宝账号' && !this.userinfo.aliaccount ){
			app.alert('请先设置支付宝账号', function () {
			  app.goto('setinfo');
			});
			return;
		}
	  }
      if (paytype == '银行卡' && (!this.userinfo.bankname || !this.userinfo.bankcarduser || !this.userinfo.bankcardnum)) {
        app.alert('请先设置完整银行卡信息', function () {
          app.goto('setinfo');
        });
        return;
      }
			app.showLoading('提交中');
      app.post('ApiPeisong/withdraw', {money: money,paytype: paytype,wechat_payment_code:that.wechat_payment_code,alipay_payment_code:that.alipay_payment_code}, function (res) {
				app.showLoading(false);
        if (res.status == 0) {
          app.error(res.msg);
          return;
        } else {
			if(res.need_confirm==1 && res.id){
				//需要用户主动确认收款
				return that.shoukuan(res.id,'yuyue_worker_withdrawlog','/yuyue/yuyue/moneylog?st=2');
			}else{
				app.success(res.msg);
				that.subscribeMessage(function () {
				  setTimeout(function () {
				    app.goto('my');
				  }, 1000);
				});
			}
        }
      });
    }
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

.withdrawtype .item2{border-bottom: 1px solid #f5f5f5;}
.pay-item{height:100rpx;display:flex;align-items:center}
.withdrawtype .f2 .pay-item:last-child{border-bottom:0}
.withdrawtype .f2 .pay-item .t1{flex:1;display:flex;align-items:center;color:#333}
.withdrawtype .f2 .pay-item .t1 .img{width:44rpx;height:44rpx;margin-right:40rpx}

.withdrawtype .f2 .pay-item .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.withdrawtype .f2 .pay-item .radio .radio-img{width:100%;height:100%}

.upload-img{width: 150rpx;height: 150rpx;border-radius: 16rpx;overflow: hidden;position: relative;margin-bottom: 20rpx;}
.upload-img .upload-img-class{width: 100%;height:100%}
.upload-img .upload-icon-view {width: 100%;height: 100%;background: #f9fafb;}
.upload-img .upload-icon-view .icon-class{width: 70rpx;height:70rpx;}
.upload-img .close-view{width: 40rpx;height: 40rpx;border-radius: 50%;position: absolute;top: 5rpx;right: 5rpx;}
.upload-img .close-view image{width: 100%;height: 100%;}
</style>