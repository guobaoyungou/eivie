<template>
<view class="container">
	<block v-if="isload">
		<view class="wrap" style=" height: 100%;">
			<view class="title" v-if="info.banner" :style="'background-image:url(' + info.banner + ');background-size:100% 100%;'"></view>
			<form @submit="formSubmit" @reset="formReset">
			<view class="form">
				<view class="form-item">
					<input type="text" class="input" placeholder="请输入口令" placeholder-style="color:#BBBBBB;font-size:28rpx" name="kouling" value=""></input>
				</view>
			</view>
			<button class="set-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确 定</button>
			</form>
			
			<view class="guizeBox" v-if="info.guize">
				<view class="gztitle"> — 活动规则 — </view>
				<view class="guize_txt">
						<text decode="true" space="true">{{info.guize}}</text>
				</view>
				
			</view>
			
		</view>
	</block>
	<loading v-if="loading"></loading>
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
      info: {}
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
			that.loading = true;
			var select_bid =app.getCache('select_bid');
			app.post('ApiCoupon/koulingSet', {select_bid:select_bid,bid:this.opt.bid}, function (res) {
				that.loading = false;
				if(res.status == 0){
					app.alert(res.msg);
					return;
				}
				that.info = res.info;
				that.loaded();	
			});
		},
    formSubmit: function (e) {
      var formdata = e.detail.value;
			var kouling = formdata.kouling
      if (kouling == '') {
        app.alert('请输入口令');return;
      }
			var select_bid =app.getCache('select_bid');
			app.showLoading('提交中');
      app.post("ApiCoupon/kouling", {kouling:kouling,select_bid:select_bid,bid:this.opt.bid}, function (data) {
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
    }
  }
};
</script>
<style>
	.wrap {width:100%;height:100%;padding-top: 20rpx;}
	.title {width:94%;height:316rpx;margin: auto;}
	.guizeBox{width:100%;margin:0;margin-bottom:20rpx}
	.guizeBox .gztitle{width:100%;text-align:center;font-size:32rpx;color:#888;font-weight:bold;height:100rpx;line-height:100rpx}
	.guize_txt{box-sizing: border-box;padding:0 30rpx;line-height:42rpx;color:#666;}
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
</style>