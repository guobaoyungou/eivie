<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="form">
			<view class="form-item">
				<text class="label">微信号</text>
				<input type="text" class="input" placeholder="请输入微信号" placeholder-style="color:#BBBBBB;font-size:28rpx" name="weixin" :value="userinfo.weixin"/>
			</view>
		</view>
		<view class="form">
			<view class="form-item">
				<text class="label">支付宝账号</text>
				<input type="text" class="input" placeholder="请输入支付宝账号" placeholder-style="color:#BBBBBB;font-size:28rpx" name="aliaccount" :value="userinfo.aliaccount"/>
			</view>
		</view>
		<view class="form">
			<view class="form-item">
					<text class="label">开户行</text>
					<picker class="picker" mode="selector" name="bankname" value="0" :range="banklist" @change="bindBanknameChange">
						<view v-if="bankname">{{bankname}}</view>
						<view v-else>请选择开户行</view>
					</picker>
			</view>
			<view class="form-item">
					<text class="label">持卡人姓名</text>
					<input type="text" class="input" placeholder="请输入持卡人姓名" name="bankcarduser" :value="userinfo.bankcarduser" placeholder-style="color:#BBBBBB;font-size:28rpx"/>
			</view>
			<view class="form-item">
					<text class="label">银行卡号</text>
					<input type="text" class="input" placeholder="请输入银行卡号" name="bankcardnum" :value="userinfo.bankcardnum" placeholder-style="color:#BBBBBB;font-size:28rpx"/>
			</view>
		</view>
		<view class="form">
			<view class="form-item">
				<text class="label">短信验证码</text>
				<input type="text" class="input" placeholder="请输入短信验证码" placeholder-style="color:#BBBBBB;font-size:28rpx" name="code" value=""/>
				<view class="code" @tap="smscode">{{smsdjs||'获取验证码'}}</view>
			</view>
		</view>
		<button class="set-btn" form-type="submit">保 存</button>
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
			
			smsdjs:'',
      banklist: ['工商银行', '农业银行', '中国银行', '建设银行', '招商银行', '邮储银行', '交通银行', '浦发银行', '民生银行', '兴业银行', '平安银行', '中信银行', '华夏银行', '广发银行', '光大银行', '北京银行', '宁波银行'],
      bankname: '',
			userinfo:{},
			textset:{},
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.isload = true
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiYueke/set', {}, function (data) {
				that.loading = false;
				that.userinfo = data.userinfo;
				that.bankname = data.userinfo.bankname;
				that.loaded();
			});
		},
    formSubmit: function (e) {
      var formdata = e.detail.value;
			var bankname = this.bankname
			var bankcarduser = formdata.bankcarduser
			var bankcardnum = formdata.bankcardnum
			var weixin = formdata.weixin
			var aliaccount = formdata.aliaccount
			var code = formdata.code || ''
      if (bankname == '') {
        app.alert('请选择开户行');return;
      }
			app.showLoading('提交中');
      app.post("ApiYueke/set", {bankname:bankname,bankcarduser:bankcarduser,bankcardnum:bankcardnum,weixin:weixin,aliaccount:aliaccount,code:code}, function (data) {
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
    bindBanknameChange: function (e) {
      this.bankname = this.banklist[e.detail.value];
    },
    smscode: function () {
      var that = this;
      if (that.hqing == 1) return;
      that.hqing = 1;
      var tel = that.userinfo.tel;
      if (tel == '') {
        app.alert('请输入手机号码');
        that.hqing = 0;
        return false;
      }
      if (!app.isPhone(tel)) {
        app.alert("手机号码有误，请重填");
        that.hqing = 0;
        return false;
      }
      app.post("ApiIndex/sendsms", {tel: tel}, function (data) {
        if (data.status != 1) {
          app.alert(data.msg);
        }
      });
      var time = 120;
      var interval1 = setInterval(function () {
        time--;
        if (time < 0) {
          that.smsdjs = '重新获取';
          that.hqing = 0;
          clearInterval(interval1);
        } else if (time >= 0) {
          that.smsdjs = time + '秒';
        }
      }, 1000);
    }
  }
};
</script>
<style>
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.form-item .picker{height: 60rpx;line-height:60rpx;margin-left: 0;flex:1;color: #000;}
.form-item .code{color:#06A051}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;background:linear-gradient(-90deg, #06A051 0%, #03B269 100%)}
</style>