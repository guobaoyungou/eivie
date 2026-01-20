<template>
<view class="container">
	<block v-if="isload">
			<form @submit="formSubmit">
					<view class="mymoney" :style="{background:t('color1')}">
							<view class="f1">我的可提现{{t('佣金')}}</view>
							<view class="f2"><text style="font-size:26rpx">￥</text>{{userinfo.commission}}</view>
							<view class="f3" v-if="sysset.commissionrecord_withdrawlog_show == 1" @tap="goto" data-url="commissionlog?st=1"><text>提现记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
							<view class="f1" v-if="userinfo.show_cash_count">累计提现：{{userinfo.cash_yongji_total}}</view>
							<view class="f1" v-if="userinfo.show_cash_count">累计返现：{{userinfo.cashback_total}}</view>
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
								<view class="tips" v-if="sysset.comwithdraw_integer_type>0">
										最低提现金额按
										<text v-if="sysset.comwithdraw_integer_type==1">个</text>
										<text v-if="sysset.comwithdraw_integer_type==2">十</text>
										<text v-if="sysset.comwithdraw_integer_type==3">百</text>
										<text v-if="sysset.comwithdraw_integer_type==4">千</text>
										位整数提现
								</view>
								<view class="tips" v-if="sysset.comwithdraw_duipeng_score_bili>0">
										提现积分{{userinfo.commission_withdraw_score}}按照{{sysset.comwithdraw_duipeng_score_bili}}:1同比减少 
								</view>
								<view v-if="sysset.comwithdrawbl && sysset.comwithdrawbl!=100" class="tips">提现金额的{{100-sysset.comwithdrawbl}}%将直接转到余额用于复购 </view>
								<view v-if="sysset.comwithdraw_need_score && sysset.comwithdraw_need_score==1" class="tips">
									提现需消耗{{t('积分')}},{{sysset.commission_score_exchange_num}}{{t('积分')}}=1{{t('佣金')}}
								</view>
                <view v-if="sysset.commission_withdraw_limit !== false" class="tips">
                  可用提现额度：{{sysset.commission_withdraw_limit}}
                </view>
							</view>
					</view>
					<view class="content2" v-if="sysset && sysset.commission_withdraw_upload_invoice">
						<view class="item2" style="justify-content: space-between;align-items: center;">
							<view class="f1"><span style="color: red;" v-if="sysset.commission_withdraw_upload_invoice == 2">*</span>上传发票 (支持PDF/图片)</view>
							<view class="imgbox">
								<view class="layui-imgbox" v-if="invoice_url">
									<view class="layui-imgbox-close" @tap="removeInvoice">
										<image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image>
									</view>
									<view class="layui-imgbox-img">
										<image :src="invoice_url" @tap="previewImage" :data-url="invoice_url" mode="widthFix" v-if="!isPDF(invoice_url)"></image>
										<image :src="pre_url+'/static/img/article/pdf.png'" @tap="previewImage" :data-url="invoice_url" mode="widthFix" v-else></image>
									</view>
								</view>
								<view class="imgitem-pics" v-else>
									<view class="uploadbtn1" @tap="uploadInvoice" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 44rpx',backgroundSize:'60rpx 60rpx',backgroundColor:'#F3F3F3'}">
									</view>
								</view>
							</view>
						</view>
					</view>
					<view class="withdrawtype">
						<view class="f1">选择提现方式：</view>
						<view class="f2">
							<view class="item" v-if="sysset.withdraw_weixin==1" @tap.stop="changeradio" data-paytype="微信钱包">
									<view class="t1"><image class="img" :src="pre_url+'/static/img/withdraw-weixin.png'"/>微信钱包</view>
									<view class="radio" :style="paytype=='微信钱包' ? 'background:'+t('color1')+';border:0' :''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
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
				
					<button class="btn" :style="{background:t('color1')}" @tap="formSubmit">立即提现</button>
					<view v-if="paytype=='支付宝'" class="textbtn" @tap="goto" data-url="/pagesExt/my/setaliaccount">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
					<view v-if="paytype=='银行卡' && !sysset.withdraw_huifu" class="textbtn" @tap="goto" data-url="/pagesExt/my/setbankinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
					<view v-if="paytype=='银行卡' && sysset.withdraw_huifu == 1" class="textbtn" @tap="goto" data-url="/pagesExt/my/sethuifuinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
					<view v-if="paytype=='小额通支付宝'" class="textbtn" @tap="goto" data-url="/pagesExt/my/setaliaccount">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
					<view v-if="paytype=='小额通银行卡'" class="textbtn" @tap="goto" data-url="/pagesExt/my/setbankinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
          <view v-if="paytype=='灵活薪支付宝'" class="textbtn" @tap="goto" data-url="/pagesExt/my/setaliaccount">设置支付宝账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
          <view v-if="paytype=='灵活薪银行卡'" class="textbtn" @tap="goto" data-url="/pagesExt/my/setbankinfo">设置银行卡账户<image :src="pre_url+'/static/img/arrowright.png'" /></view>
          <view v-if="paytype=='收款码'" class="textbtn" @tap="goto" data-url="/pagesB/my/setpaycode">设置收款码<image :src="pre_url+'/static/img/arrowright.png'" /></view>
          <view v-if="paytype == sysset.custom_name" style="width:100%;margin-top:40rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="/pagesB/my/setcustomaccount">设置{{sysset.custom_name}}账户<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
			</form>
			<view class="withdraw_desc" v-if="sysset.withdraw_desc">
					<view class="title">说明</view>
					<text>{{sysset.withdraw_desc}}</text>
			</view>
			<!-- #ifdef APP-PLUS -->
				<ad-rewarded-video ref="adRewardedVideo"  :adpid="sysset.commission_withdraw_adpid" :preload="false" :loadnext="false" :disabled="true" v-slot:default="{loading, error}"  @close="onadclose"	@error="onaderror">
				</ad-rewarded-video>
			<!-- #endif -->
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
			bid:0,
			selectbank:false,
			bank:[],
			pre_url:app.globalData.pre_url,
			invoice_url:'', //上传发票
			is_play_adset:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.bid){
			this.bid = this.opt.bid || 0;
		}
		var that = this;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onReady() {
		this.is_play_adset = 0;
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAgent/commissionWithdraw', {bid:that.bid}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: that.t('佣金') + '提现'
				});
				var sysset = res.sysset;
				that.sysset = sysset;
				that.tmplids = res.tmplids;
				that.userinfo = res.userinfo;
				var paytype = '';
				if (sysset.withdraw_weixin == 1) {
					paytype = '微信钱包';
				}
				if ((!sysset.withdraw_weixin || sysset.withdraw_weixin == 0) && sysset.withdraw_aliaccount == 1) {
					paytype = '支付宝';
				}
				if ((!sysset.withdraw_weixin || sysset.withdraw_weixin == 0) && (!sysset.withdraw_aliaccount || sysset.withdraw_aliaccount == 0) && sysset.withdraw_bankcard==1) {
					paytype = '银行卡';
				}
				if(sysset.withdraw_aliaccount_xiaoetong == 1 && paytype == ''){
					paytype = '小额通支付宝';
				}
				if(sysset.withdraw_bankcard_xiaoetong == 1 && paytype == ''){
					paytype = '小额通银行卡';
				}
        if(sysset.withdraw_aliaccount_linghuoxin == 1 && paytype == ''){
        	paytype = '灵活薪支付宝';
        }
        if(sysset.withdraw_bankcard_linghuoxin == 1 && paytype == ''){
        	paytype = '灵活薪银行卡';
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
			if(!isNaN(comwithdrawmul) && comwithdrawmul>0){
				if(!Number.isInteger((money*100)/(comwithdrawmul*100))){
					app.error('提现金额需为' + comwithdrawmul+ '的整数倍');
					return;
				}
			}
			
      if (money > usermoney) {
        app.error(that.t('佣金') + '不足');
        return;
      }
	  //提现控制整倍数
	  if (this.sysset.comwithdraw_integer_type) {
		  var comwithdraw_integer_type = this.sysset.comwithdraw_integer_type; 
		  if(comwithdraw_integer_type == 1 && (money % 1) != 0){			  
			  app.error('请输入整数');
			  return;
		  }else if(comwithdraw_integer_type == 2 && (money % 10) != 0){
			  app.error('请输入10整倍整数');
			  return;
		  }else if(comwithdraw_integer_type == 3 && (money % 100) != 0){
			  app.error('请输入100整倍整数');
			  return;
		  }else if(comwithdraw_integer_type == 4 && (money % 1000) != 0){
			  app.error('请输入1000整倍整数');
			  return;
		  }
      }
	  //计算提现积分
	  if (this.sysset.comwithdraw_duipeng_score_bili) {
	  		  var comwithdraw_duipeng_score_bili = this.sysset.comwithdraw_duipeng_score_bili; 
	  		  var commission_withdraw_score = this.userinfo.commission_withdraw_score; 
			  var comwithdraw_integer_type = this.sysset.comwithdraw_integer_type; 
			  var use_commission_withdraw_score = commission_withdraw_score / comwithdraw_duipeng_score_bili
	  		  if(use_commission_withdraw_score < money){
				  var use_money = money;
				  if(comwithdraw_integer_type == 1){
				  		use_money =  Math.floor(use_commission_withdraw_score);
				  }else if(comwithdraw_integer_type == 2){
				  		use_money =  Math.floor(use_commission_withdraw_score/10) * 10;
				  }else if(comwithdraw_integer_type == 3){
				  		use_money =  Math.floor(use_commission_withdraw_score/100) * 100;
				  }else if(comwithdraw_integer_type == 4){
				  		use_money =  Math.floor(use_commission_withdraw_score/1000) * 1000;
				  }
	  			  app.error('提现积分不足，最高可提现'+use_money);
	  			  return;
	  		  }
	  }
			if(this.sysset.commission_withdraw_upload_invoice && this.sysset.commission_withdraw_upload_invoice == 2){
				if(!this.invoice_url){
					return app.error('请上传发票');
				}
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
			if(app.globalData.platform == 'app'){
				console.log(this.sysset);
				if(this.sysset.commission_withdraw_adset_st && this.sysset.commission_withdraw_adset_st==1 && that.is_play_adset == 0){	
					setTimeout(() => {
						this.$refs.adRewardedVideo.show();
					},500)
				}else{
					this.toFormSubmit();
				}
			}else{
				this.toFormSubmit();
			}
		
    },
	toFormSubmit(){
		var that = this;
		 var money = parseFloat(that.money);
		 var paytype = this.paytype;
		app.showLoading('提交中');
		app.post('ApiAgent/commissionwithdraw', {money: money,paytype: paytype,bid:that.bid,invoice:that.invoice_url,is_play_adset:that.is_play_adset}, function (data) {
						app.showLoading(false);
		  if (data.status == 0) {
		    app.error(data.msg);
		    return;
		  } else {
			  that.is_play_adset = 0;//观看广告 重置
				  if(data.need_confirm==1 && data.id){
				  	//需要用户主动确认收款
					var redirect_url = '';
					if(that.sysset.commissionrecord_withdrawlog_show == 1){
						redirect_url = '/activity/commission/commissionlog?st=1';
					}
				  	return that.shoukuan(data.id,'member_commission_withdrawlog',redirect_url);
				  }else{
					  app.success(data.msg);
					  if(that.sysset.commissionrecord_withdrawlog_show == 1){
						  that.subscribeMessage(function () {
							setTimeout(function () {
							  app.goto('commissionlog?st=1');
							}, 1000);
						  });
					  }else{
						 that.getdata();
						 //app.goto('/activity/commission/withdraw?bid='+that.bid)
					  }
				  }
		  }
		});
	},
	onadload(e) {},
	onadclose(e) {
		var that = this;
		const detail = e.detail
		// 用户点击了【关闭广告】按钮
		if (detail && detail.isEnded) {
			that.is_play_adset = 1;
			that.toFormSubmit();
		} else {
			// 播放中途退出
			console.log('播放中途退出，不下发奖励');
		}
	},
	onaderror(e) {
		var error = e.detail;
		this.errMsg = error.errMsg;
	  // 广告加载失败
		uni.showModal({
			title: '错误',
			content: this.errMsg,
			showCancel:false,
			confirmText:'已知晓'
		});
	  
	},
		uploadInvoice: function(e) {
			var that = this;
			app.chooseFile(function(data){
				that.invoice_url = data;
			})
		},
		removeInvoice:function(e){
			var that = this;
			that.invoice_url = '';
		},
		isPDF(url) {
			if (!url) return false; 
			const ext = url.split('.').pop().toLowerCase();
			return ext === 'pdf'; 
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

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:150rpx;height:150rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.imgitem-pics{width:150rpx;height:150rpx;margin:8rpx;background:#F0F0F0;overflow: hidden;}
.uploadbtn1{width:150rpx;height:150rpx}
</style>