<template>
<view class="container">
	<block v-if="isload">
    <!-- s -->
    <view style="width:100%;height: 100%;">
      <view class="bg_div1">
        <view style="overflow: hidden;">
          <view class="content_div1">
            <view class="card_div1">
              <form @submit="formSubmit" @reset="formReset">
              <view class="title">重置密码</view>
              <view class="regform">
                <view class="form-item">
                  <image :src="pre_url+'/static/img/reg-tel.png'" class="img"/>
                  <input type="text" class="input" placeholder="请输入手机号" placeholder-style="font-size:30rpx;color:#B2B5BE" name="tel" value="" @input="telinput"/>
                </view>
                <view class="form-item">
                  <image :src="pre_url+'/static/img/reg-code.png'" class="img"/>
                  <input type="text" class="input" placeholder="请输入验证码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="smscode" value=""/>
                  <view class="code" @tap="smscode">{{smsdjs||'获取验证码'}}</view>
                </view>
                <view class="form-item">
                  <image :src="pre_url+'/static/img/reg-pwd.png'" class="img"/>
                  <input type="text" class="input" placeholder="6-16位字母数字组合密码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="pwd" value="" :password="true"/>
                </view>
                <view class="form-item">
                  <image :src="pre_url+'/static/img/reg-pwd.png'" class="img"/>
                  <input type="text" class="input" placeholder="再次输入登录密码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="repwd" value="" :password="true"/>
                </view>
								<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">确定</button>
              </view>
              </form>
            </view>
          </view>
        </view>
      </view>
    </view>
    <!-- e -->
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
      smsdjs: '',
			tel:'',
      hqing: 0,
      pre_url:app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminIndex/getpwd', {pid:app.globalData.pid}, function (res) {
				that.loading = false;
				if(res.status == 0){
					app.alert(res.msg);return;
				}
				that.loaded();
			});
		},
    formSubmit: function (e) {
			var that = this;
      var formdata = e.detail.value;
      if (formdata.tel == ''){
        app.alert('请输入手机号');
        return;
      }
      if (formdata.pwd == '') {
        app.alert('请输入密码');
        return;
      }
      if (formdata.pwd.length < 6) {
        app.alert('新密码不小于6位');
        return;
      }
			if (formdata.pwd.length > 16) {
			  app.alert('新密码不大于16位');
			  return;
			}
      if (formdata.repwd == '') {
        app.alert('请再次输入新密码');
        return;
      }
      if (formdata.pwd != formdata.repwd) {
        app.alert('两次密码不一致');
        return;
      }
			if (formdata.smscode == '') {
				app.alert('请输入短信验证码');
				return;
			}
			
			app.showLoading('提交中');
      app.post("ApiAdminIndex/getpwd", {tel:formdata.tel,pwd:formdata.pwd,smscode:formdata.smscode}, function (data) {
				app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
          setTimeout(function () {
            app.goto('/admin/index/login');
          }, 1000);
        } else {
          app.error(data.msg);
        }
      });
    },
    telinput: function (e) {
      this.tel = e.detail.value
    },
    smscode: function () {
      var that = this;
      if (that.hqing == 1) return;
      that.hqing = 1;
      var tel = that.tel;
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
page{background:#ffffff;width: 100%;height:100%;}
.container{width:100%;height:100%;}
.title{margin:70rpx 50rpx 50rpx 40rpx;height:60rpx;line-height:60rpx;font-size: 48rpx;font-weight: bold;color: #000000;}
.regform{ width:100%;padding:0 50rpx;border-radius:5px;background: #FFF;}
.regform .form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:88rpx;line-height:88rpx;border-bottom:1px solid #F0F3F6;margin-top:20rpx}
.regform .form-item:last-child{border:0}
.regform .form-item .img{width:44rpx;height:44rpx;margin-right:30rpx}
.regform .form-item .input{flex:1;color: #000;}
.regform .form-item .code{font-size:30rpx;color:#06A051}

.bg_div1{width:100%;min-height: 100%;overflow: hidden;}
.content_div1{width: 700rpx; margin: 0 auto;margin-bottom: 60rpx;}

.savebtn{margin-top:60rpx;width:100%;height:96rpx;line-height:96rpx;color:#fff;font-size:30rpx;border-radius: 48rpx;}
</style>