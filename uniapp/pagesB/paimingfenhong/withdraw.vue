<template>
<view class="container">
	<block v-if="isload">
			<form @submit="formSubmit">
					<view class="mymoney" :style="{background:t('color1')}">
							<view class="f1">我的可转出{{t('排名分红')}}</view>
							<view class="f2"><text style="font-size:26rpx">￥</text>{{userinfo.paiming_fenhong_money}}</view>
					</view>
					<view class="content2">
							<view class="item2"><view class="f1">转出金额(元)</view></view>
							<view class="item3"><view class="f1">￥</view><view class="f2"><input class="input" type="digit" name="money" value="" placeholder="请输入转出金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput"></input></view></view>
							<view class="tips-box">
								<view class="item4 tips" v-if="sysset.max_point_amount>0">
										<text style="margin-right:10rpx">最低转出金额{{sysset.max_point_amount}} </text>
								</view>
								<view class="tips" v-if="sysset.max_point_amount>0">
										最低转出金额按
										{{sysset.max_point_amount}}
										整倍数转出
								</view>
							</view>
					</view>
				
					<button class="btn" :style="{background:t('color1')}" @tap="formSubmit">立即转出</button>
					
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
			app.get('ApiPaimingFenhong/withdraw', {}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: that.t('排名分红') + '转出'
				});
				var sysset = res.sysset;
				that.sysset = sysset;
				that.userinfo = res.userinfo;
				that.loaded();
			});
		},
		
    moneyinput: function (e) {
      var usermoney = parseFloat(this.userinfo.paiming_fenhong_money);
      var money = parseFloat(e.detail.value);
      if (money < 0) {
        app.error('必须大于0');
      } else if (money > usermoney) {
        app.error('可转出' + this.t('排名分红') + '不足');
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
      var usermoney = parseFloat(this.userinfo.paiming_fenhong_money);
      var max_point_amount = parseFloat(this.sysset.max_point_amount); //var formdata = e.detail.value;

      var money = parseFloat(that.money);
      var paytype = this.paytype;
      if (isNaN(money) || money <= 0) {
        app.error('转出金额必须大于0');
        return;
      }
      if (max_point_amount > 0 && money < max_point_amount) {
        app.error('转出金额必须大于¥' + max_point_amount);
        return;
      }

      if (money > usermoney) {
        app.error(that.t('排名分红') + '不足');
        return;
      }
	  //转出控制整倍数
	  if (this.sysset.max_point_amount) {
		  var max_point_amount = this.sysset.max_point_amount; 
		  if(max_point_amount > 0 && (money % max_point_amount) != 0){			  
			  app.error('请输入整数');
			  return;
		  }
      }

	app.showLoading('提交中');
      app.post('ApiPaimingFenhong/withdraw', {money: money}, function (data) {
				app.showLoading(false);
        if (data.status == 0) {
          app.error(data.msg);
          return;
        } else {
          app.success(data.msg);
		  that.getdata();
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

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold}

.tips-box{width:94%;margin:0 3%;padding: 20rpx 0;border-top:1px solid #F0F0F0;}
.tips{color:#8C8C8C;font-size:28rpx;line-height: 50rpx;}

</style>