<template>
<view class="container">
	<block v-if="isload">
		<view class="mymoney" :style="{background:t('color1')}">
			<view class="f1">待还{{t('信用额度')}}</view>
			<view class="f2">{{userinfo.overdraft_money}}</view>
			<view class="f3" @tap="goto" data-url="/pagesA/overdraft/moneylog"><text>明细记录</text><text class="iconfont iconjiantou" style="font-size:20rpx"></text></view>
		</view>
		<view class="content2">
			<view class="item2"><view class="f1">额度还款(元)</view></view>
				<view class="item3"><view class="f2"><input type="digit" name="money" :value="money" placeholder="请输入还款金额" placeholder-style="color:#999;font-size:40rpx" @input="moneyinput" style="font-size:60rpx"/></view></view>
			<view style="padding:20rpx 30rpx;line-height:42rpx;font-size: 24rpx;color: #666;" v-if="1==2">
				还款后，额度恢复
			</view>
		</view>
		<view class="op">
			<view class="btn" @tap="topay" :style="{background:t('color1')}">立即还款</view>
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
      userinfo: {},
			shuoming:'',
      money: ''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			app.loading = true;
			app.get('ApiOverdraftMoney/recharge', {}, function (res) {
				uni.setNavigationBarTitle({
					title: that.t('信用额度') + '还款'
				});
				app.loading = false;
				that.isload = true;
				that.userinfo = res.userinfo;
				if(that.userinfo.overdraft_money<0){
					that.money = that.userinfo.overdraft_money*-1
				}
				that.loaded();
			});
		},
    moneyinput: function (e) {
      var money = e.detail.value;
      if (parseFloat(money) < 0) {
        app.error('必须大于0');
      } else {
        this.money = money;
      }
    },
    selectgiveset: function (e) {
      var money = e.currentTarget.dataset.money;
      this.money = money;
    },
    topay: function (e) {
      var that = this;
      var money = that.money;
	  if (parseFloat(money) < 0) {
	    app.error('必须大于0');
	  }
			var paytype = e.currentTarget.dataset.paytype;
			that.loading = true;
      app.post('ApiOverdraftMoney/recharge',{money: money},function (res) {
				that.loading = false;
        if (res.status == 0) {
          app.error(res.msg);
          return;
        }
				app.goto('/pagesExt/pay/pay?id='+res.payorderid);
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

.content2 .item2{display:flex;width:100%;padding:0 30rpx}
.content2 .item2 .f1{height:120rpx;line-height:120rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:0 30rpx;border-bottom:1px solid #F0F0F0;}
.content2 .item3 .f1{height:120rpx;line-height:120rpx;font-size:60rpx;color:#333333;font-weight:bold;margin-right:20rpx}
.content2 .item3 .f2{display:flex;align-items:center;font-size:60rpx;color:#333333;font-weight:bold}
.content2 .item3 .f2 input{height:120rpx;line-height:120rpx;}

.op{width:96%;margin:20rpx 2%;display:flex;align-items:center;margin-top:40rpx}
.op .btn{flex:1;height:100rpx;line-height:100rpx;background:#07C160;width:90%;margin:0 10rpx;border-radius:10rpx;color: #fff;font-size:28rpx;font-weight:bold;display:flex;align-items:center;justify-content:center}
.op .btn .img{width:48rpx;height:48rpx;margin-right:20rpx}

.giveset{width:100%;padding:20rpx 0rpx;display:flex;flex-wrap:wrap;justify-content:center}
.giveset .item{margin:10rpx;padding:15rpx 0;width:210rpx;height:120rpx;background:#FDF6F6;border-radius:10rpx;display:flex;flex-direction:column;align-items:center;justify-content:center}
.giveset .item .t1{color:#545454;font-size:32rpx;}
.giveset .item .t2{color:#8C8C8C;font-size:20rpx;margin-top:6rpx}
.giveset .item.active .t1{color:#fff;font-size:32rpx}
.giveset .item.active .t2{color:#fff;font-size:20rpx}
</style>