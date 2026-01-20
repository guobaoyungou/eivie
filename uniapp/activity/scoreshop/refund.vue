<template>
<view class="container">
	<form @submit="formSubmit" @reset="formReset" report-submit="true">
		<view class="form-item">
			<text class="label">退款原因</text>
			<view class="input-item"><textarea placeholder="请输入退款原因" placeholder-style="color:#999;" name="reason"></textarea></view>
		</view>
		<view class="form-item">
			<text class="label">退款金额(元)</text>
			<view class="flex"><input name="money" type="digit" disabled="true" :value="totalprice" placeholder="请输入退款金额" placeholder-style="color:#999;"></input></view>
		</view>
    <view class="form-item">
      <text class="label">退回{{t('积分')}}</text>
      <view class="flex"><input name="score" type="number" disabled="true" :value="totalscore" placeholder="请输入退款金额" placeholder-style="color:#999;"></input></view>
    </view>
		<button class="ref-btn" form-type="submit">确定</button>
	</form>
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

      orderid: '',
      totalprice: 0,
      totalscore: 0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.orderid = this.opt.orderid;
		this.totalprice = this.opt.price;
    this.totalscore = this.opt.score;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiOrder/refundinit', {}, function (res) {
				that.loading = false;
				that.tmplids = res.tmplids;
				that.loaded();
			});
		},
    formSubmit: function (e) {
      var that = this;
      var orderid = that.orderid;
      var reason = e.detail.value.reason;
      var money = parseFloat(e.detail.value.money);
      var score = parseInt(e.detail.value.score);

      if (reason == '') {
        app.alert('请填写退款原因');
        return;
      }
			app.showLoading('提交中');
      app.post('ApiScoreshop/refund', {orderid: orderid,reason: reason,money: money,score:score}, function (res) {
        app.showLoading(false);
        app.alert(res.msg);
        if (res.status == 1) {
          that.subscribeMessage(function () {
            setTimeout(function () {
              app.goback(true);
            }, 1000);
          });
        }
      });
    }
  }
};
</script>
<style>
.form-item{ width:100%;background: #fff; padding: 32rpx 20rpx;}
.form-item .label{ width:100%;height:60rpx;line-height:60rpx}
.form-item .input-item{ width:100%;}
.form-item input{ width:100%;border: 1px #eee solid;padding: 10rpx;height:80rpx; background-color: #EEEEEE;}
.form-item input{ width:100%;border: 1px #eee solid;padding: 10rpx;height:80rpx}
.form-item .mid{ height:80rpx;line-height:80rpx;padding:0 20rpx;}
.ref-btn{ width: 90%; margin: 0 5%; height: 40px; line-height: 40px; text-align: center; color: #fff; font-size: 16px; border-radius: 8px;border: none; background: #ff8758; }
</style>