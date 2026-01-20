<template>
<view>
	<view v-if="isload" class="main">
		<view class="cardtop" :style="'background:rgba('+t('color2rgb')+',0.2);'">
			<image :src="userinfo.headimg"></image>
			<text>{{userinfo.nickname}}</text>
		</view>
		<view class="codeimg mt50" v-if="set.code_type==1 || set.code_type==2">
			<image :src="userinfo.member_barcode_img" class="barcodeimg"></image>
			<view>{{userinfo.tel?userinfo.tel:userinfo.member_code}}</view>
		</view>
		<view class="codeimg" v-if="set.code_type==0 || set.code_type==2">
			<image :src="userinfo.member_code_img"></image>
			<view>{{userinfo.member_code}}</view>
		</view>
		
		<view class="moneybox" v-if="showmeberinfo && tablist.length>0">
			<view class="moneytab" v-for="(item,index) in tablist" :key="index" @tap="goto" :data-url="item.path">
				<view class="txt">{{item.name}}</view>
				<view class="money">{{item.tag}}{{item.value}}</view>
			</view>
		</view>
		<view class="wxpay" @tap="wxpay" v-if="set.iswxpay==1">
			<image class="wxicon" :src="pre_url+'/static/img/wxpay2.png'"></image>
			<text>使用微信支付</text>
		</view>
		
	</view>
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
			pre_url:app.globalData.pre_url,
			
      userinfo: [],
			tablist:[],
			showmeberinfo:false,
			set:{}
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
			uni.setNavigationBarTitle({
			  title: '会员码'
			});
			var that = this;
			that.loading = true;
			app.get('ApiMy/getMemberCode', {}, function (res) {
				that.loading = false;
				that.userinfo = res.userinfo;
				that.set = res.set;
				that.showmeberinfo = res.showmeberinfo;
				that.tablist = res.tablist;
				that.loaded();
			});
		},
    cancel: function () {
      this.hiddenmodalput = true;
    },
		wxpay:function(){
			var that =  this;
			that.loading = true;
			app.get('ApiMy/wxOfflinePayView', {}, function (res) {
				var data = res.data;
				that.loading = false;
				if(res.status==1){
					wx.openOfflinePayView({
						appId: data.appId,
						timeStamp: data.timeStamp,
						nonceStr: data.nonceStr,
						package: data.package,
						signType: data.signType,
						paySign: data.paySign,
						success: function (res) {
							console.log('成功', res)
						},
						fail: function (err) {
							console.log(err)
							console.log('失败')
						}
					})
				}
				that.loaded();
			});
		}
  }
};
</script>
<style>
	.main {background-color: #fff;margin: 50rpx 30rpx;border-radius: 20rpx;padding-bottom: 30rpx;}
.codeimg { display: flex;flex-direction: column;justify-content: center;align-items: center;margin-top: 20rpx;}
.mt50{margin-top: 40rpx;}
.codeimg image {width: 350rpx;height: 350rpx;margin-bottom: 20rpx;}
.codeimg .barcodeimg {width: 350rpx;height: 150rpx;margin-bottom: 20rpx;}
.cardtop{display: flex;justify-content: center;align-items: center;padding: 20rpx;border-radius: 26rpx 26rpx 0 0;font-weight: bold;font-size: 30rpx;}
.cardtop image{width: 60rpx; height: 60rpx;border-radius: 50%;margin-right: 20rpx;}
.moneybox{display: flex;justify-content: space-around;align-items: center; margin-top: 20rpx; border-top: 1px dashed #d9d9d9;padding: 40rpx 20rpx;}
.moneytab{display: flex;flex-direction: column;justify-content: center;align-items: center;}
.moneytab .txt{color: #666;}
.moneytab .money{margin-top: 10rpx;font-size: 30rpx;font-weight: bold;}
.wxpay{display: flex;justify-content: center;align-items: center;border: 1px solid #09bb07;width: 90%; border-radius: 50rpx;height: 90rpx; margin: 40rpx 5% 20rpx 5%;}
.wxpay image{width: 40rpx; height: 40rpx;margin-right: 20rpx;}
</style>