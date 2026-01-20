<template>
<view class="addressadd">
	<block v-if="isload">
	<form id="setform" @submit="confirm">
		<view class="form">
			<view class="form-item">
				<label class="label">登录账户</label>
				<view class="f2 flex flex1">{{user.un}}</view>
			</view>
			<view class="form-item">
				<label class="label">新 密 码</label>
				<view class="f2 flex flex1">
					<input type="text" password="true" name="pwd" value="" placeholder="请输入新密码" placeholder-style="font-size:28rpx" autocomplete="off"></input>
				</view>
			</view>
			<view class="form-item">
				<label class="label">确认密码</label>
				<view class="f2 flex flex1">
					<input type="text" password="true" name="repwd" value="" placeholder="请再次输入密码" placeholder-style="font-size:28rpx" autocomplete="off"></input>
				</view>
			</view>
		</view>
		
		<view class="form">
			<view class="form-item">
				<text class="label">短信验证码</text>
				<input type="text" class="input" placeholder="请输入短信验证码" placeholder-style="color:#BBBBBB;font-size:28rpx" name="code" value=""/>
				<view class="code" @tap="smscode">{{smsdjs||'获取验证码'}}</view>
			</view>
		</view>
		<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">确定修改</button>
	</form>
	</block>
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
      user: [],
			smsdjs:'',
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiAdminIndex/editpwd', {}, function (res) {
				that.loading = false;
				that.user = res.user;
				that.loaded();
			});
		},
    confirm: function (e) {
      var that = this;
      var formdata = e.detail.value;
			console.log(formdata)
      var pwd = formdata.pwd;
      var repwd = formdata.repwd;
      if(!pwd){
        app.error('请输入新密码');
        return;
      }
			if (formdata.pwd.length < 6) {
			  app.alert('新密码不小于6位');
			  return;
			}
      if(!repwd){
        app.error('请再次输入新密码');
        return;
      }
      if(pwd != repwd){
        app.error('两次新密码输入不一致');
        return;
      }
			var code = formdata.code;
			if(!code){
			  app.error('请获取短信验证码');
			  return;
			}
			
			app.showLoading('修改中');
      app.post('ApiAdminIndex/editpwd',{pwd: pwd,repwd: repwd,smscode:code},function(res){
				app.showLoading(false);
        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('/admin/index/index');
          }, 1000);
        } else {
          app.error(res.msg);
        }
      });
    },
		smscode: function () {
		  var that = this;
		  if (that.hqing == 1) return;
		  that.hqing = 1;
		  app.post("ApiAdminIndex/sendsms", {}, function (data) {
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
.savebtn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;background:linear-gradient(-90deg, #06A051 0%, #03B269 100%)}
</style>