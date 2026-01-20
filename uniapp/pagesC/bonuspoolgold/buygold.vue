<template>
<view class="container">
	<block v-if="isload">
			<form @submit="formSubmit">
					<view class="mymoney" :style="{background:t('color1')}">
						<view class="f1">我的{{t('金币')}}</view>
						<view class="f2"><text style="font-size:26rpx"></text>{{userinfo.gold}}</view>
						<view class="f1">当前{{t('金币')}}价值：{{set.gold_price}}</view>
						<view class="f3" @tap="goto" data-url="/pagesC/bonuspoolgold/goldlog"><text>{{t('金币')}}记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
					</view>
					<view class="content2">
						<view class="item2"><view class="f1">购买金额(元)</view></view>
						<view class="item3" v-if="set.caninput==1 || giveset.length==0">
							<view class="f2"><input class="input" type="digit" name="buy_num" :value="buy_num" placeholder="请输入购买金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"></input></view>
						</view>
						<view class="giveset" v-if="giveset.length>0">
							<view v-for="(item, index) in giveset" :key="index" v-if="item > 0" class="item" :class="buy_num==item?'active':''" :style="buy_num==item?'background:'+t('color1'):''" @tap="selectgiveset" :data-money="item">
								<text class="t1">{{item}}元</text>
							</view>
						</view>
						<view class="tips-box" v-if="set.show_buy_fee==1">
							<view class="item4 tips">
								<text v-if="set.bonuspool_gold_rate>0">购买手续费{{set.bonuspool_gold_rate}}% </text>
							</view>
						</view>
					</view>
					<view class="withdrawtype">
						<view class="f1">选择支付方式：</view>
						<view class="f2">
							<block v-for="(item,index) in wallet_arr">
								<view class="item" @tap.stop="changeradio" :data-paytype="item.type">
									<view class="t1">{{item.name}}<text v-if="item.type!='online'">(余额：{{item.remain}})</text></view>
									<view class="radio" :style="paytype==item.type ? 'background:'+t('color1')+';border:0' :''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
								</view>
							</block>
						</view>
					</view>
					<button class="btn" :style="{background:t('color1')}" @tap="formSubmit">立即购买</button>
			</form>
			<view class="withdraw_desc" v-if="set.desc">
					<view class="title">说明</view>
					<text>{{set.desc}}</text>
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
		buy_num: 0,
		set: false,
		paytype: 'money',
		tmplids:[],
		bid:0,
		selectbank:false,
		wallet_arr:[],
		pre_url:app.globalData.pre_url,
		giveset:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiBonusPoolGold/buygold', {}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: '购买'+that.t('金币') 
				});
				if (res.status == 0) {
					app.error(res.msg);
					setTimeout(function () {
					  app.goto('/pages/my/usercenter','reLaunch');
					}, 1000);
					return;
				}
				var set = res.set;
				that.set = set;
				that.tmplids = res.tmplids;
				that.userinfo = res.member;
				that.wallet_arr = res.wallet_arr;
				that.giveset = res.giveset;
				that.loaded();
			});
		},
		
    moneyinput: function (e) {
		var buy_num = parseFloat(e.detail.value);
		if (buy_num < 0) {
			app.error('必须大于0');
		} 
		this.buy_num = buy_num;
    },
    changeradio: function (e) {
      var that = this;
      var paytype = e.currentTarget.dataset.paytype;
      that.paytype = paytype;
    },
    formSubmit: function () {
      var that = this;
      var buy_num = parseFloat(that.buy_num);
      var paytype = this.paytype;

	  if (paytype == '') {
	    app.error('请选择支付方式');
	    return;
	  }
      if (isNaN(buy_num) || buy_num <= 0) {
        app.error('购买金额必须大于0');
        return;
      }
      
		app.showLoading('提交中');
      app.post('ApiBonusPoolGold/buygold', {buy_num: buy_num,paytype: paytype}, function (data) {
		app.showLoading(false);
        if (data.status == 0) {
			app.error(data.msg);
			return;
        } else {
			app.success(data.msg);
			if(data.status == 1){
				that.subscribeMessage(function () {
				setTimeout(function () {
				  app.goto('/pagesC/bonuspoolgold/goldlog','reLaunch');
				}, 1000);
			});
		  }else{
			 app.goto('/pagesExt/pay/pay?id='+data.payorderid);
		  }
        }
      });
    },
	selectgiveset: function (e) {
	  var money = e.currentTarget.dataset.money;
	  this.buy_num = money;
	},
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

.giveset{width:100%;padding:20rpx 0rpx;display:flex;flex-wrap:wrap;justify-content:center}
.giveset .item{margin:10rpx;padding:15rpx 0;width:210rpx;height:120rpx;background:#FDF6F6;border-radius:10rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}
.giveset .item .t1{color:#545454;font-size:32rpx;}
.giveset .item .t2{color:#8C8C8C;font-size:20rpx;margin-top:6rpx}
.giveset .item.active .t1{color:#fff;font-size:32rpx}
.giveset .item.active .t2{color:#fff;font-size:20rpx}
</style>