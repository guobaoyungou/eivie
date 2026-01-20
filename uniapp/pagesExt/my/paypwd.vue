<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="form">
			<view class="form-item" v-if="haspwd==1">
				<text class="label">原密码</text>
				<input type="text" class="input" placeholder="输入您的原密码" placeholder-style="font-size:28rpx;color:#BBBBBB" name="oldpaypwd" value="" password="true"></input>
			</view>
			<view class="form-item">
				<text class="label">新密码</text>
				<input type="text" class="input" placeholder="输入您的新密码" placeholder-style="font-size:28rpx;color:#BBBBBB" name="paypwd" value="" password="true"></input>
			</view>
			<view class="form-item">
				<text class="label">确认新密码</text>
				<input type="text" class="input" placeholder="再次输入新密码" placeholder-style="font-size:28rpx;color:#BBBBBB" name="repaypwd" value="" password="true"></input>
			</view>
		</view>
		<view style="width:94%;margin:0 3%;padding:20rpx;color:#BBBBBB">注：用于{{t('余额')}}支付等敏感操作时输入，请妥善保管</view>
		<button class="set-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确 认</button>
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
		
      haspwd: 0
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
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiMy/paypwd', {}, function (data) {
				that.loading = false;
				that.haspwd = data.haspwd;
				that.loaded();
			});
		},
    formSubmit: function (e) {
      var formdata = e.detail.value;
      if (this.haspwd == 1) {
        if (formdata.oldpaypwd == '') {
          app.alert('请输入原密码');
          return;
        }
      } else {
        formdata.oldpaypwd = '';
      }
      if (formdata.paypwd == '') {
        app.alert('请输入新密码');
        return;
      }
      if (formdata.paypwd.length < 6) {
        app.alert('新密码不小于6位');
        return;
      }
      if (formdata.repaypwd == '') {
        app.alert('请再次输入新密码');
        return;
      }
      if (formdata.paypwd != formdata.repaypwd) {
        app.alert('两次密码不一致');
        return;
      }
			app.showLoading('提交中');
      app.post("ApiMy/paypwd", {oldpaypwd: formdata.oldpaypwd,paypwd: formdata.paypwd}, function (data) {
				app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
          setTimeout(function () {
            app.goback();
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
.container{overflow: hidden;}
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 14rpx 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:100rpx;line-height:100rpx;}
.form-item:last-child{border:0}
.form-item .label{color:#8B8B8B;font-weight:bold;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
</style>