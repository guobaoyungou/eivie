<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
      <view class="form-box">
        <view class="form-item flex-col" style="border-bottom:0">
          <view class="f1">微信收款码</view>
          <view class="f2">
            <view class="layui-imgbox" v-if="wxpaycode">
              <view class="layui-imgbox-close" @tap="removeimg" data-field="wxpaycode"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
              <view class="layui-imgbox-img"><image :src="wxpaycode" @tap="previewImage" :data-url="wxpaycode" mode="widthFix"></image></view>
            </view>
            <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="wxpaycode" data-pernum="1" v-if="!wxpaycode"></view>
          </view>
          <input type="text" hidden="true" name="wxpaycode" :value="wxpaycode" maxlength="-1"/>
        </view>
        <view class="form-item flex-col" style="border-bottom:0">
          <view class="f1">支付宝收款码</view>
          <view class="f2">
            <view class="layui-imgbox" v-if="alipaycode">
              <view class="layui-imgbox-close" @tap="removeimg" data-field="alipaycode"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
              <view class="layui-imgbox-img"><image :src="alipaycode" @tap="previewImage" :data-url="alipaycode" mode="widthFix"></image></view>
            </view>
            <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="alipaycode" data-pernum="1" v-if="!alipaycode"></view>
          </view>
          <input type="text" hidden="true" name="alipaycode" :value="alipaycode" maxlength="-1"/>
        </view>
      </view>
      <button class="set-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">保 存</button>
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
			pre_url:app.globalData.pre_url,
		
			info:{},
			wxpaycode:'',
			alipaycode:''
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
			app.get('ApiMy/set',{},function (data){
				if(data){
					that.info= data.userinfo;
          that.wxpaycode  = that.info.wxpaycode || '';
          that.alipaycode = that.info.alipaycode || '';
					that.loaded();
				}
			});
		},
		formSubmit: function (e) {
      var formdata = e.detail.value;
			if(formdata.wxpaycode == '' && formdata.alipaycode == ''){
				app.alert('请上传一个收款码');return;
			}
      var wxpaycode  = formdata.wxpaycode
      var alipaycode = formdata.alipaycode
			app.showLoading('提交中');
      app.post("ApiMy/setfield", {wxpaycode:wxpaycode,alipaycode:alipaycode}, function (data) {
				app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        } else {
          app.error(data.msg);
        }
      });
    },
		uploadimg:function(e){
			var that = this;
			var pernum = parseInt(e.currentTarget.dataset.pernum);
			if(!pernum) pernum = 1;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				if(field == 'wxpaycode')  that.wxpaycode  = pics[0];
				if(field == 'alipaycode') that.alipaycode = pics[0];
			},pernum);
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'wxpaycode'){
				that.wxpaycode = '';
			}else if(field == 'alipaycode'){
				that.alipaycode = '';
			}
		},
  }
};
</script>
<style>
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>