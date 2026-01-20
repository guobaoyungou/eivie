<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit">
		<view class="myscore" :style="{background:t('color1')}">
			<view class="f1">允提{{t('积分')}}</view>
			<view class="f2">{{myscore}}</view>
		</view>
		<view class="content2">
			<view class="item2"><view class="f1">提现数量</view></view>
			<view class="item3"><view class="f2"><input class="input" type="number" name="integral" value="" placeholder="请输入提现数量" placeholder-style="color:#999;font-size:36rpx" @input="moneyinput"></input></view></view>
			<view class="item4">
				<text style="margin-right:10rpx">{{t('积分')}}提现到{{t('余额')}}比例为：1：{{score_to_money_percent}}</text>
				<text v-if="score_to_money_min_money > 0">,最低兑换金额{{score_to_money_min_money}}元</text>
			</view>
		</view>
		<button class="btn" :style="{background:t('color1')}" form-type="submit">提现到{{t('余额')}}</button>
		<view class='text-center' @tap="goto" data-url='/pages/my/usercenter'><text>返回{{t('会员')}}中心</text></view>
	</form>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
      myscore: 0,
			score_to_money_percent:0,
			score_to_money_min_money:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);

		var that = this;
		// app.checkLogin();
	
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			app.get('ApiMy/scoreWithdraw', {}, function (res) {
				if(res.status == 0) {
					app.alert(res.msg);return;
				}
				if(res.status == 1) {
					that.myscore = res.myscore;
					that.score_to_money_percent = res.score_to_money_percent;
					that.score_to_money_min_money = res.score_to_money_min_money;
				}
				var title = that.t('积分') + '提现';
				uni.setNavigationBarTitle({
					title:title
				});
				that.loaded();
			});
		},
    moneyinput: function (e) {
      var money = parseInt(e.detail.value);
      
    },
    formSubmit: function (e) {
      var that = this;
      var money = parseInt(e.detail.value.integral);
      if (isNaN(money) || money <= 0) {
        app.error('数量必须大于0');
        return;
      }
			
			if (money < 0) {
        app.error('数量必须大于0');return;
      } else if (money > that.myscore) {
        app.error(this.t('积分') + '不足');return;
      }

			app.confirm('确定要提现吗？', function(){
				app.showLoading();
				app.post('ApiMy/scoreWithdraw', {integral: money}, function (data) {
					app.showLoading(false);
				  if (data.status == 0) {
				    app.error(data.msg);
				    return;
				  } else {
				    app.success(data.msg);
				    that.subscribeMessage(function () {
				      setTimeout(function () {
				        app.goto('/pages/my/usercenter');
				      }, 1000);
				    });
				  }
				}, '提交中');
			})
    }
  }
};
</script>
<style>
	.myscore{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
	.myscore .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
	.myscore .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold; position: relative;}
	
.container{display:flex;flex-direction:column}
.content2{width:94%;margin:10rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff}
.content2 .item1{display:flex;width:100%;border-bottom:1px solid #F0F0F0;padding:0 30rpx}
.content2 .item1 .f1{flex:1;font-size:32rpx;color:#333333;font-weight:bold;height:120rpx;line-height:120rpx}
.content2 .item1 .f2{color:#FC4343;font-size:44rpx;font-weight:bold;height:120rpx;line-height:120rpx}

.content2 .item2{display:flex;width:100%;padding:0 30rpx;padding-top:10rpx}
.content2 .item2 .f1{height:80rpx;line-height:80rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:0 30rpx;padding-bottom:20rpx}
.content2 .item3 .f1{height:100rpx;line-height:100rpx;font-size:60rpx;color:#333333;font-weight:bold;margin-right:20rpx}
.content2 .item3 .f2{display:flex;align-items:center;font-size:36rpx;color:#333333;font-weight:bold}
.content2 .item3 .f2 .input{font-size:36rpx;height:100rpx;line-height:100rpx;}
.content2 .item4{display:flex;width:94%;margin:0 3%;border-top:1px solid #F0F0F0;height:100rpx;line-height:100rpx;color:#8C8C8C;font-size:28rpx}

.text-center {text-align: center; margin-top: 20rpx;}

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}
</style>