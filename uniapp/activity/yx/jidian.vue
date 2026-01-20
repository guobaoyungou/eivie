<template>
<view class="container">
	<block v-if="isload">
		<view class="wrap">
			<parse :content="guize" @navigate="navigate"></parse>
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
		
			textset:{},
      haspwd: 0,
			guize:''
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
		getdata: function (e) {
			var that = this;
			var bid = that.opt.bid || 0;
			that.loading = true;
			app.post("ApiCoupon/jidian", {bid:bid}, function (res) {
				that.loading = false;
			  if (res.status == 1) {
			    that.guize = res.guize;
			  } else {
			    app.error(res.msg);
			  }
				that.loaded();
			});
		},
  }
};
</script>
<style>
	.wrap {background-color: #ffffff;}
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
</style>