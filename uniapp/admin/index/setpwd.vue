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
				<label class="label">原 密 码</label>
				<view class="f2 flex flex1">
					<input type="text" password="true" name="oldpwd" value="" placeholder="请输入原密码" placeholder-style="font-size:28rpx" autocomplete="off"></input>
				</view>
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
			app.get('ApiAdminIndex/setpwd', {}, function (res) {
				that.loading = false;
				that.user = res.user;
				that.loaded();
			});
		},
    confirm: function (e) {
      var that = this;
      var formdata = e.detail.value;
      var oldpwd = formdata.oldpwd;
      var pwd = formdata.pwd;
      var repwd = formdata.repwd;

      if(!oldpwd){
        app.error('请输入原密码');
        return;
      }
      if(!pwd){
        app.error('请输入新密码');
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
			app.showLoading('修改中');
      app.post('ApiAdminIndex/setpwd',{oldpwd: oldpwd,pwd: pwd,repwd: repwd},function(res){
				app.showLoading(false);
        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('index');
          }, 1000);
        } else {
          app.error(res.msg);
        }
      });
    }
  }
};
</script>
<style>

.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{color:#222;height: 60rpx; line-height: 60rpx; text-align:left;width:200rpx;padding-right:20rpx}
.form-item .input{ flex:1;height: 60rpx; line-height: 60rpx;text-align:right}

.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }
</style>