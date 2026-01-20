<template>
<view class="container">
	<block v-if="isload">
    <!-- s -->
    <view style="width:100%;height: 100%;">
      <view class="bg_div1" :style="loginset_data.bgtype==1?'background:'+loginset_data.bgcolor:'background:url('+loginset_data.bgimg+') no-repeat center;background-size:100% 100%'">
        <view style="overflow: hidden;">
          <view class="content_div1">
            <view class="card_div1" :style="'background:'+loginset_data.cardcolor">
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
                  <view class="code" :style="'color:'+loginset_data.codecolor" @tap="smscode">{{smsdjs||'获取验证码'}}</view>
                </view>
                <view class="form-item">
                  <image :src="pre_url+'/static/img/reg-pwd.png'" class="img"/>
                  <input type="text" class="input" placeholder="6-16位字母数字组合密码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="pwd" value="" :password="true"/>
                </view>
                <view class="form-item">
                  <image :src="pre_url+'/static/img/reg-pwd.png'" class="img"/>
                  <input type="text" class="input" placeholder="再次输入登录密码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="repwd" value="" :password="true"/>
                </view>

                <block v-if="loginset_data.btntype==1">
                  <button class="btn1" form-type="submit" :style="'background:rgba('+t('color1rgb')+');color: '+loginset_data.btnwordcolor" >确定</button>
                </block>
                <block v-if="loginset_data.btntype==2">
                  <button class="btn1"  form-type="submit":style="'background-color:'+loginset_data.btncolor+';color:'+loginset_data.btnwordcolor" >确定</button>
                </block>
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
			
			logintype_1:true,
			logintype_2:false,
			logintype_3:false,
			xystatus:0,
			xycontent:'',
			needsms:false,
			showxieyi:false,
			isagree:false,
      smsdjs: '',
			tel:'',
      hqing: 0,
      loginset_type:0,
      loginset_data:'',
      pre_url:app.globalData.pre_url,
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
			app.get('ApiIndex/getpwd', {pid:app.globalData.pid}, function (res) {
				that.loading = false;
				if(res.status == 0){
					app.alert(res.msg);return;
				}
			  if(res.loginset_type){
			    that.loginset_type = res.loginset_type
			  }
			  if(res.loginset_data){
			    that.loginset_data = res.loginset_data
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
      app.post("ApiIndex/getpwd", {tel:formdata.tel,pwd:formdata.pwd,smscode:formdata.smscode}, function (data) {
				app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
          setTimeout(function () {
            app.goto('/pages/index/login');
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
.regform .form-item .code{font-size:30rpx}
.regform .xieyi-item{display:flex;align-items:center;margin-top:50rpx}
.regform .xieyi-item{font-size:24rpx;color:#B2B5BE}
.regform .xieyi-item .checkbox{transform: scale(0.6);}
.regform .form-btn{margin-top:60rpx;width:100%;height:96rpx;line-height:96rpx;color:#fff;font-size:30rpx;border-radius: 48rpx;}

.othertip{height:auto;overflow: hidden;display:flex;align-items:center;width:580rpx;padding:20rpx 20rpx;margin:0 auto;margin-top:60rpx;}
.othertip-line{height: auto; padding: 0; overflow: hidden;flex:1;height:0;border-top:1px solid #F2F2F2}
.othertip-text{padding:0 32rpx;text-align:center;display:flex;align-items:center;justify-content:center}
.othertip-text .txt{color:#A3A3A3;font-size:22rpx}

.othertype{width:70%;margin:20rpx 15%;display:flex;justify-content:center;}
.othertype-item{width:50%;display:flex;flex-direction:column;align-items:center;}
.othertype-item .img{width:88rpx;height:88rpx;margin-bottom:20rpx}
.othertype-item .txt{color:#A3A3A3;font-size:24rpx}

.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}

.bg_div1{width:100%;min-height: 100%;overflow: hidden;}
.content_div1{width: 700rpx; margin: 0 auto;margin-bottom: 60rpx;}
.title1{opacity: 1;font-size: 50rpx;font-weight: bold;line-height: 90rpx;text-align: left;margin-top: 80rpx;}
.subhead1{font-size: 28rpx;font-weight: 500;line-height: 40rpx;}
.card_div1{width: 100%;padding:40rpx;border-radius: 24rpx;margin-top: 40rpx;}
.tel1{width:100%;height:88rpx;border-radius: 88rpx;line-height: 88rpx;background-color: #F5F7FA;padding:0 40rpx;margin: 20rpx 0;margin-top: 30rpx;}
.code1{height: 88rpx;font-size: 24rpx;line-height: 88rpx;float: right;}
.btn1{width:100%;height:88rpx;border-radius: 88rpx;line-height: 88rpx;margin: 20rpx 0;text-align: center;font-weight: bold;}
.other_line{width: 106rpx;height: 2rpx;background: #D8D8D8;margin-top: 20rpx;}
.logo2{width: 200rpx;height: 200rpx;margin: 0 auto;margin-top:40rpx;margin-bottom: 40rpx;border-radius: 12rpx;overflow: hidden;}
.inputcode{width:300rpx;height:88rpx;line-height: 88rpx;display: inline-block;}
.input_val{width:100%;height:88rpx;line-height: 88rpx;}
.xycss1{line-height: 60rpx;font-size: 24rpx;overflow: hidden;margin-top: 20rpx;}
.other_login{width:420rpx;margin: 60rpx auto;}
.overflow_ellipsis{overflow: hidden;text-overflow: ellipsis;white-space: nowrap;width: 380rpx;}
.other_content{overflow: hidden;width: 100%;margin-top: 60rpx;text-align: center;display: flex;justify-content:center;}
</style>