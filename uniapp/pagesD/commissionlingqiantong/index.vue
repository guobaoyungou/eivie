<template>
<view class="container">
	<block v-if="isload">
			<form @submit="formSubmit">
					<view class="mymoney" :style="{background:t('color1')}">
							<view class="f1">我的可转入{{t('佣金')}}</view>
							<view class="f2"><text style="font-size:26rpx">￥</text>{{userinfo.commission}}</view>
							<view class="f3" @tap="goto" data-url="order?st=1"><text>收益明细</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
					</view>
					<view class="content2">
							<view class="item2"><view class="f1">转入金额(元)</view></view>
							<view class="item3"><view class="f1">￥</view><view class="f2"><input class="input" type="digit" name="money" :value="money" placeholder="请输入转入金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"></input></view></view>
							<view class="withdraw_desc" v-if="income_set.desc" >
									<view class="title">说明：</view>
									<text>{{income_set.desc}}</text>
							</view>
					</view>
					
					<view class="withdrawtype">
						<view class="f1">选择转入周期：</view>
						<view class="f2">
							<view class="item">
									<view class="t1">周期产品</view>
									<view class="t1">收益率</view>
									<view class="radio1 "> </view>
							</view>
							<block v-for="(item, index) in income_data" :key="index">
							<view class="item"  @tap.stop="changeradio" :data-paytype="index">
									<view class="t1">{{item.day}}天</view>
									<view class="t1">{{item.rate}}%</view>
									<view class="radio" :style="paytype==index ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
							</block>							
						</view>
					</view>
					<view style="height: 120rpx;"></view>
						<view class="fixed-bottom">
							<button class="btn" :style="{background:t('color1')}" @tap="formSubmit">立即转入</button>
						</view>
					

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
			money: 0,
			income_set: [],
			sysset: [],
			paytype: '微信钱包',
			tmplids:[],
			bid:0,
			selectbank:false,
			pre_url:app.globalData.pre_url,
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
			app.get('ApiCommissionLingqiantong/index', {bid:that.bid}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: that.t('佣金') + '转' + that.t('零钱通')
				});
				that.income_set = res.income_set;
				that.userinfo = res.userinfo;				
				that.money = res.userinfo.commission;				
				that.income_data = res.income_data

				that.loaded();
			});
		},
		
    moneyinput: function (e) {
      var usermoney = parseFloat(this.userinfo.commission);
      var money = parseFloat(e.detail.value);
      if (money < 0) {
        app.error('必须大于0');
      } else if (money > usermoney) {
        app.error('可转入' + this.t('佣金') + '不足');
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
      var money = parseFloat(that.money);
      var paytype = this.paytype;

	  // if (paytype == '') {
	  //   app.error('请选择转入方式'+paytype);
	  //   return;
	  // }
      if (isNaN(paytype) || paytype < 0) {
        app.error('请选择转入方式');
        return;
      }		
			
      if (money > usermoney) {
        app.error(that.t('佣金') + '不足');
        return;
      }
		this.toFormSubmit();
    },
	toFormSubmit(){
		var that = this;
		 var money = parseFloat(that.money);
		 var paytype = this.paytype;
		app.showLoading('提交中');
		app.post('ApiCommissionLingqiantong/index', {money: money,paytype: paytype,bid:that.bid}, function (data) {
			app.showLoading(false);
		  if (data.status == 1) {
			  that.subscribeMessage(function () {
			  	setTimeout(function () {
			  	  app.goto('order');
			  	}, 1000);
			  });		    
		  } else {
			app.error(data.msg);
			return;
		  }
		});
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
.withdrawtype .f1{height:50rpx;line-height:50rpx;padding:0 30rpx;color:#333333;font-weight:bold}


.withdrawtype .f2{padding:0 30rpx}
.withdrawtype .f2 .item{border-bottom:1px solid #f5f5f5;height:100rpx;display:flex;align-items:center}
.withdrawtype .f2 .item:last-child{border-bottom:0}
.withdrawtype .f2 .item .t1{flex:1;display:flex;align-items:center;color:#333}
.withdrawtype .f2 .item .t1 .img{width:44rpx;height:44rpx;margin-right:40rpx}

.withdrawtype .f2 .item .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.withdrawtype .f2 .item .radio1{flex-shrink:0;width: 36rpx;height: 36rpx;}
.withdrawtype .f2 .item .radio .radio-img{width:100%;height:100%}

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}

.withdraw_desc{width:94%;margin:0 3%;padding: 20rpx 0;color: #FFA06D;border-top:1px solid #F0F0F0;}
.withdraw_desc .title{font-size: 30rpx;padding: 10rpx 0;}
.withdraw_desc text{width: 100%; line-height: 46rpx;font-size: 30rpx;}
.tips-box{width:94%;margin:0 3%;padding: 20rpx 0;border-top:1px solid #F0F0F0;}
.tips{color:#8C8C8C;font-size:28rpx;line-height: 50rpx;}

.fixed-bottom {
	  position: fixed;
	  bottom: 0;
	  left: 0;
	  right: 0;
	  /* 增加底部安全区域高度 */
	  padding-bottom: constant(safe-area-inset-bottom);
	  padding-bottom: env(safe-area-inset-bottom);
	}

</style>