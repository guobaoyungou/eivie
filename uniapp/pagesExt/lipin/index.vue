<template>
<view class="container">
	<block v-if="isload">
	<form report-submit="true" @submit="subconfirm" style="width:100%">
		<view class="title">请输入{{lipinset.dhmtxt || '兑换码'}}进行兑换</view>
		
		<block v-if="lipinset.needno == 1">
			<view class="inputdiv">
				<input id="cardno" type="text" name="cardno" :value="cardno" placeholder-style="color:#666;" placeholder="请输入您的卡号"/>
				<view class="scanicon" @tap="saoyisao2" v-if="platform!='h5' && lipinset.scanshow==1">
					<image :src="pre_url+'/static/img/scan-icon2.png'"></image>
				</view>	
			</view>
			<view class="inputdiv">
				<input id="dhcode" type="text" name="dhcode" :value="dhcode" placeholder-style="color:#666;" placeholder="请输入您的密码"/>
				<view class="scanicon" @tap="saoyisao" v-if="platform!='h5' && lipinset.scanshow==1">
					<image :src="pre_url+'/static/img/scan-icon2.png'"></image>
				</view>	
			</view>
		</block>
		<block v-else>
			<view class="inputdiv">
				<input id="dhcode" type="text" name="dhcode" :value="dhcode" placeholder-style="color:#666;" placeholder="请输入您的兑换码"/>
				<view class="scanicon" @tap="saoyisao" v-if="platform!='h5' && lipinset.scanshow==1">
					<image :src="pre_url+'/static/img/scan-icon2.png'"></image>
				</view>	
			</view>
		</block>

		<button class="btn" form-type="submit" :style="{backgroundColor:t('color1')}">立即兑换</button>
		<view class="f0" @tap="goto" data-url="dhlog"><text>查看兑换记录</text></view>
	</form>
	<view class="qd_guize">
		<!-- <view class="gztitle"> — 兑换规则 — </view> -->
		<view class="guize_txt">
			<parse :content="lipinset.guize" />
		</view>
	</view>

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
			platform:app.globalData.platform,
			pre_url:app.globalData.pre_url,

      userinfo: [],
      money: '',
      moneyduan: 0,
			dhcode:'',
			cardno:'',
			lipinset:{},
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.dhcode) this.dhcode = this.opt.dhcode;
		if(this.opt && this.opt.cardno) this.cardno = this.opt.cardno;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiLipin/index', {}, function (res) {
				that.loading = false;
				that.lipinset = res.lipinset;
				that.loaded();
			});
		},
    subconfirm: function (e) {
      var that = this;
      var dhcode = e.detail.value.dhcode;
			var cardno = '';
			if(that.lipinset.needno == 1){
				cardno = e.detail.value.cardno;
			}
			that.loading = true;
      app.post('ApiLipin/index', {dhcode: dhcode,cardno:cardno}, function (res) {
				that.loading = false;
        if (res.status == 0) {
          app.error(res.msg);
          return;
        }

        if (res.status == 1) {
          app.alert(res.msg, function () {
            app.goto('/pages/my/usercenter');
          });
        }
        if (res.status == 2) {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('prodh?dhcode='+dhcode+'&cardno='+cardno);
          }, 1000);
        }
				if (res.status == 3) {
				  app.alert(res.msg, function () {
				    app.goto('/pagesExt/coupon/mycoupon');
				  });
				}
				if (res.status == 6) {
				  app.alert(res.msg, function () {
				    app.goto(res.url);
				  });
				}
				that.loaded();
      });
    },
    saoyisao: function (d) {
      var that = this;
			if(app.globalData.platform == 'h5'){
				app.alert('请使用微信扫一扫功能扫码');return;
			}else if(app.globalData.platform == 'mp'){
				// #ifdef H5
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							if(content.indexOf('?') === -1){
								var dhcode = content;
							}else{
								var contentArr = content.split('=');
								var dhcode = contentArr.pop();
							}
							if(dhcode.indexOf(',') !== -1){
								var contentArr = dhcode.split(',');
								dhcode = contentArr.pop();
							}
							that.dhcode = dhcode;
							//app.goto('prodh?dhcode='+params);
						}
					});
				});
				// #endif
			}else{
				// #ifndef H5
				uni.scanCode({
					success: function (res) {
						console.log(res);
						var content = res.result;
						if(content.indexOf('?') === -1){
							var dhcode = content;
						}else{
							var contentArr = content.split('=');
							var dhcode = contentArr.pop();
						}
						if(dhcode.indexOf(',') !== -1){
							var contentArr = dhcode.split(',');
							dhcode = contentArr.pop();
						}
						that.dhcode = dhcode;
						//app.goto('prodh?dhcode='+params);
					}
				});
				// #endif
			}
    },
    saoyisao2: function (d) {
      var that = this;
			if(app.globalData.platform == 'h5'){
				app.alert('请使用微信扫一扫功能扫码');return;
			}else if(app.globalData.platform == 'mp'){
				// #ifdef H5
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							if(content.indexOf('?') === -1){
								var cardno = content;
							}else{
								var contentArr = content.split('=');
								var cardno = contentArr.pop();
							}
							if(cardno.indexOf(',') !== -1){
								var contentArr = cardno.split(',');
								cardno = contentArr.pop();
							}
							that.cardno = cardno;
							//app.goto('prodh?cardno='+params);
						}
					});
				});
				// #endif
			}else{
				// #ifndef H5
				uni.scanCode({
					success: function (res) {
						console.log(res);
						var content = res.result;
						if(content.indexOf('?') === -1){
							var cardno = content;
						}else{
							var contentArr = content.split('=');
							var cardno = contentArr.pop();
						}
						if(cardno.indexOf(',') !== -1){
							var contentArr = cardno.split(',');
							cardno = contentArr.pop();
						}
						that.cardno = cardno;
						//app.goto('prodh?cardno='+params);
					}
				});
				// #endif
			}
    },
  }
}
</script>
<style>
.container{display:flex;flex-direction:column;}
.container .title{display:flex;justify-content:center;width:100%;color:#555;font-size:40rpx;text-align:center;height:100rpx;line-height:100rpx;margin-top:60rpx}
.container .inputdiv{display:flex;width:90%;margin:0 auto;margin-top:40rpx;margin-bottom:40rpx;position:relative}
.container .inputdiv input{background:#fff;width:100%;height:120rpx;line-height:120rpx;padding:0 40rpx;font-size:40rpx;border:1px solid #f5f5f5;border-radius:20rpx}
.container .btn{ height: 88rpx;line-height: 88rpx;background: #FC4343;width:90%;margin:0 auto;border-radius:8rpx;margin-top:60rpx;color: #fff;font-size: 36rpx;}
.container .f0{width:100%;margin-top:40rpx;height:60rpx;line-height:60rpx;color:#FC4343;font-size:30rpx;display:flex;align-items:center;justify-content:center}
.container .scanicon{width:44rpx;height:44rpx;position:absolute;top:50%;right:20rpx;z-index:9;transform: translateY(-50%);}
.container .scanicon image{width:100%;height:100%}
.qd_guize{width:100%;margin:30rpx 0 20rpx 0;}
.qd_guize .gztitle{width:100%;text-align:center;font-size:32rpx;color:#656565;font-weight:bold;height:100rpx;line-height:100rpx}
.guize_txt{box-sizing: border-box;padding:0 30rpx;line-height:42rpx;}
</style>