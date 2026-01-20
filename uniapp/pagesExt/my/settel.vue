<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="form">
			<view class="form-item">
				<input type="text" class="input" placeholder="请输入手机号" placeholder-style="color:#BBBBBB;font-size:28rpx" name="tel" value="" @input="telinput"></input>
			</view>
			<view class="form-item" v-if="needsms">
				<input type="text" class="input" placeholder="请输入验证码" placeholder-style="font-size:30rpx;color:#B2B5BE" name="smscode" value=""/>
				<view class="code" @tap="smscode">{{smsdjs||'获取验证码'}}</view>
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
		
			textset:{},
			smsdjs: '',
			tel:'',
      hqing: 0,
      needsms: false
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
			this.loading = true
			app.get('ApiMy/settel',{},function(res){
				that.loading = false;
				that.needsms = res.needsms;
				that.loaded();
			})
		},
    formSubmit: function (e) {
      var formdata = e.detail.value;
			var tel = formdata.tel
			var smscode = formdata.smscode
      if (tel == '') {
        app.alert('请输入手机号');return;
      }
	  if(!app.isPhone(tel)){
		  return app.alert("请填写正确的手机号");
	  }
			app.showLoading('提交中');
      app.post("ApiMy/settelsub", {tel:tel,smscode:smscode}, function (data) {
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
.container{overflow: hidden;}
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
</style>