<template>
<view class="container">
	<block v-if="isload">
    <form report-submit="true" @submit="subconfirm" style="width:100%">
      <view class="title">请扫描码或输入随机码进行绑定</view>
      <view class="inputdiv">
        <input id="bindcode" type="text" name="bindcode" :value="bindcode" placeholder-style="color:#666;" placeholder="扫描码或输入随机码"/>
        <view class="scanicon" @tap="saoyisao">
          <image :src="pre_url+'/static/img/scan-icon2.png'"></image>
        </view>	
      </view>

      <button class="btn" form-type="submit">立即绑定</button>
      <view class="f0" @tap="goto" data-url="bindlog"><text>查看绑定记录</text></view>
    </form>
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

			bindcode:'',
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.bindcode) this.bindcode = this.opt.bindcode;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiAdminIndex/bindqrcodevar', {}, function (res) {
				that.loading = false;
        if(res.status == 1){
          that.loaded();
        }else{
          app.error(res.msg);
          return;
        }
			});
		},
    subconfirm: function (e) {
      var that = this;
      var bindcode = e.detail.value.bindcode;
			that.loading = true;
      app.confirm('确定提交绑定此码为收款码',function(){
        app.post('ApiAdminIndex/bindqrcodevar', {bindcode: bindcode}, function (res) {
        	that.loading = false;
          if(res.status == 1) {
            app.success(res.msg);
          }else{
            app.alert(res.msg);
            return;
          }
        });
      })
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
								var bindcode = content;
							}else{
								var contentArr = content.split('=');
								var bindcode = contentArr.pop();
							}
							if(bindcode.indexOf(',') !== -1){
								var contentArr = bindcode.split(',');
								bindcode = contentArr.pop();
							}
							that.bindcode = bindcode;
							//app.goto('prodh?bindcode='+params);
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
							var bindcode = content;
						}else{
							var contentArr = content.split('=');
							var bindcode = contentArr.pop();
						}
						if(bindcode.indexOf(',') !== -1){
							var contentArr = bindcode.split(',');
							bindcode = contentArr.pop();
						}
						that.bindcode = bindcode;
						//app.goto('prodh?bindcode='+params);
					}
				});
				// #endif
			}
    }
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
.container .scanicon{width:80rpx;height:80rpx;position:absolute;top:20rpx;right:20rpx;z-index:9}
.container .scanicon image{width:100%;height:100%}
.qd_guize{width:100%;margin:30rpx 0 20rpx 0;}
.qd_guize .gztitle{width:100%;text-align:center;font-size:32rpx;color:#656565;font-weight:bold;height:100rpx;line-height:100rpx}
.guize_txt{box-sizing: border-box;padding:0 30rpx;line-height:42rpx;}
</style>