<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit">
			<view class="mymoney" :style="{background:t('color1')}">
					<view class="f1">我的{{t('股东分红')}}额度</view>
					<view class="f2"><text style="font-size:26rpx">￥</text>{{userinfo.gudong_remain}}</view>
			</view>
			<view class="content2">
					<view class="item2"><view class="f1">转换额度</view></view>
					<view class="item3"><view class="f1"></view><view class="f2"><input class="input" type="digit" name="money" value="" placeholder="请输入转换额度" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"></input></view></view>
					<view class="tips-box">
						<view class="tips">
							1{{t('股东分红')}}额度可转换{{sysset.fenhongmax_to_score_bili}}{{t('积分')}} 
						</view>
					</view>
			</view>
			<button class="btn" :style="{background:t('color1')}" @tap="formSubmit">立即转换</button>
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
		sysset: false,
		pre_url:app.globalData.pre_url,
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
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAgent/fenhongmaxToScore', {}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: that.t('股东分红额度') + '转' + that.t('积分')
				});
				var sysset = res.sysset;
				that.sysset = sysset;
				that.userinfo = res.userinfo;
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
    
    formSubmit: function () {
      var that = this;
      var usermoney = parseFloat(this.userinfo.fenhong_max_add);
      var money = parseFloat(that.money);
      if (isNaN(money) || money <= 0) {
        app.error('兑换额度必须大于0');
        return;
      }
      if (money > usermoney) {
        app.error('额度不足');
        return;
      }
	 this.toFormSubmit();
		
    },
	toFormSubmit(){
		var that = this;
		 var money = parseFloat(that.money);
		 var paytype = this.paytype;
		app.showLoading('提交中');
		app.post('ApiAgent/fenhongmaxToScore', {money: money}, function (data) {
			app.showLoading(false);
		  if (data.status == 0) {
		    app.error(data.msg);
		    return;
		  } else {
			  app.success(data.msg);
			  that.subscribeMessage(function () {
				setTimeout(function () {
				  app.goto('/activity/commission/index');
				}, 1000);
			  });
		  }
		});
	},
	onadload(e) {},
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
.content2 .item2{display:flex;width:100%;padding:0 30rpx;padding-top:10rpx}
.content2 .item2 .f1{height:80rpx;line-height:80rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:0 30rpx;padding-bottom:20rpx}
.content2 .item3 .f1{height:100rpx;line-height:100rpx;font-size:60rpx;color:#333333;font-weight:bold;margin-right:20rpx}
.content2 .item3 .f2{display:flex;align-items:center;font-size:60rpx;color:#333333;font-weight:bold}
.content2 .item3 .f2 .input{font-size:60rpx;height:100rpx;line-height:100rpx;}
.content2 .item4{display:flex;}
.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}
.tips-box{width:94%;margin:0 3%;padding: 20rpx 0;border-top:1px solid #F0F0F0;}
.tips{color:#8C8C8C;font-size:28rpx;line-height: 50rpx;}
</style>