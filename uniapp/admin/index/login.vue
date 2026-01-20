<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="title">管理员登录</view>
		<view class="loginform">
			<view class="form-item">
				<image :src="pre_url+'/static/img/reg-tel.png'" class="img"/>
				<input type="text" class="input" placeholder="请输入登录账号" placeholder-style="font-size:30rpx;color:#B2B5BE" name="username" value="" v-model="username"/>
			</view>
			<view class="form-item" v-if="logintype == 1 && (smslogin == 0 || smslogin == 1)">
				<image :src="pre_url+'/static/img/reg-pwd.png'" class="img"/>
				<input type="text" class="input" placeholder="请输入密码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="password" value="" :password="true"/>
			</view>
			<view class="form-item" v-if="logintype == 2 || smslogin == 2">
				<image :src="pre_url+'/static/img/reg-code.png'" class="img"/>
				<input type="text" class="input" placeholder="请输入短信验证码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="sms_code" value=""/>
				<button class="mini-btn" type="default" size="mini" @tap="smscode" :disabled="!!smsdjs" :style="{color: smsdjs ? '#999' : t('color1')}">
					{{ smsdjs || '获取验证码' }}
				</button>
			</view>
			<view class="form-item">
					<image :src="pre_url+'/static/img/reg-code.png'" class="img"/>
					<input type="text" class="input" placeholder="请输入验证码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="captcha" value=""/>
					<image @tap="regetcaptcha" :src="pre_url+'/?s=/ApiIndex/captcha&aid='+aid+'&session_id='+session_id+'&t='+randt" style="width:240rpx;height:80rpx"/>
				</view>
			<view v-if="forgetpwd" style="height:50rpx;">	
				<text @tap="goto" data-url="/adminExt/set/forgetPwd" data-opentype="redirect" style="color:#B2B5BE;float:right;padding: 20rpx 0 20rpx 20rpx;">忘记密码</text>
			</view>
			<button class="form-btn" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" form-type="submit">登录</button>
			<view class="switch-login-type" @tap="switchLoginType" :style="{color: t('color1')}" v-if="smslogin == 1">
				{{ logintype == 1 ? '短信登录' : '密码登录' }}
			</view>
		</view>
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
			aid:app.globalData.aid,
			session_id:app.globalData.session_id,
			captcha_src:'',
			randt:'',
			username:'',
			smslogin:0,
			logintype:1, //1: 密码登录 2：短信登录
			smsdjs:'',
			forgetpwd:false
    }
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
			that.loading = true;
			app.get("ApiAdminIndex/login", {}, function (res) {
				that.loading = false;
				that.smslogin = res.smslogin;
				if(res.smslogin == 0 || res.smslogin == 1){
					that.logintype = 1; //密码登录
				}else if(res.smslogin == 2){
					that.logintype = 2; //短信登录
				}
				that.forgetpwd = res.forgetpwd;
				
				that.loaded();
			});
		},
    formSubmit: function (e) {
			var that = this;
      var formdata = e.detail.value;
			if(that.logintype == 2){
				if (formdata.username == ''){
				  app.alert('请输入手机号');
				  return;
				}
				
				if(formdata.sms_code == ''){
					return app.alert('请输入短信验证码');
				}
			}else{
				if (formdata.username == ''){
				  app.alert('请输入账号');
				  return;
				}
				
				if (formdata.password == '') {
					app.alert('请输入密码');
					return;
				}
			}
			if (formdata.captcha == '') {
				app.alert('请输入验证码');
				return;
			}
			app.showLoading('提交中');
      app.post("ApiAdminIndex/login", {
        username:formdata.username,
        password:formdata.password,
        captcha:formdata.captcha,
        sms_code:formdata.sms_code,
        login_type:that.logintype
    }, function (res) {
				app.showLoading(false);
        if (res.status == 1) {
          app.success(res.msg);
		  var tourl = 'index';
		  if(res.tourl){
			  tourl = res.tourl;
		  }
          setTimeout(function () {
            app.goto(tourl);
          }, 1000);
        } else {
          app.error(res.msg);
					if(res.status==2){
						that.randt = that.randt+'1';
					}
        }
      });
    },
		regetcaptcha:function(){
			this.randt = this.randt+'1';
		},
		smscode: function () {
		  var that = this;
		  var username = that.username;
		  // 校验手机号是否为空
		  if (!username) {
		    app.alert('请输入手机号');
		    return;
		  }
		
		  // 校验手机号格式（简单校验）
		  var phoneReg = /^1[3-9]\d{9}$/;
		  if (!phoneReg.test(username)) {
		    app.alert('请输入正确的手机号');
		    return;
		  }
		
		  // 防止重复点击
		  if (that.smsdjs) {
		    return;
		  }
		
		  app.showLoading('发送中');
		  app.post("ApiIndex/sendsms", { tel: username,aid:1}, function (res) {
		    app.showLoading(false);
		    if (res.status == 1) {
		      app.success('验证码已发送');
		      
		      // 启动倒计时
		      var count = 120; // 倒计时时长
		      that.smsdjs = count + 's后重试';
		      var timer = setInterval(function () {
		        count--;
		        if (count > 0) {
		          that.smsdjs = count + 's后重试';
		        } else {
		          clearInterval(timer);
		          that.smsdjs = '';
		        }
		      }, 1000);
		    } else {
		      app.error(res.msg || '验证码发送失败');
		    }
		  });
		},
    switchLoginType:function() {
      if(this.smslogin == 0 || this.smslogin == 2) return;
      if(this.logintype == 1) {
        this.logintype = 2;
        this.$set(this, 'password', '');
      } else {
        this.logintype = 1;
        this.$set(this, 'sms_code', '');
      }
    }
  }
};
</script>

<style>
page{background:#ffffff}
.container{width:100%;}
.title{margin:70rpx 50rpx 50rpx 40rpx;height:60rpx;line-height:60rpx;font-size: 48rpx;font-weight: bold;color: #000000;}
.loginform{ width:100%;padding:0 50rpx;border-radius:5px;background: #FFF;}
.loginform .form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:88rpx;line-height:88rpx;border-bottom:1px solid #F0F3F6;margin-top:20rpx}
.loginform .form-item:last-child{border:0}
.loginform .form-item .img{width:44rpx;height:44rpx;margin-right:30rpx}
.loginform .form-item .input{flex:1;color: #000;}
.loginform .form-item .code{font-size:30rpx}
.loginform .form-btn{margin-top:60rpx;width:100%;height:96rpx;line-height:96rpx;color:#fff;font-size:30rpx;border-radius: 48rpx;}
.switch-login-type { margin-top: 30rpx; text-align: center; font-size: 28rpx; text-decoration: underline;}
</style>