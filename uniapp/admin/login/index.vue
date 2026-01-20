<template>
<view>
<view style="padding:30px 0 10px 0;">
	<view style="text-align: center;font-size:28px;color: #3cc51f;font-weight: 400;margin: 0 15%;">管理员登录</view>
</view>
<view style="margin:0 ">
	<form @submit="subform">
		<view class="weui-cells weui-cells_form">
			<view class="weui-cell">
				<view class="weui-cell__hd" style="width:105px"><label class="weui-label">账号</label></view>
				<view class="weui-cell__bd">
					<input class="weui-input" type="text" name="username" placeholder="请输入账号"></input>
				</view>
			</view>
			<view class="weui-cell">
				<view class="weui-cell__hd" style="width:105px"><label class="weui-label">密码</label></view>
				<view class="weui-cell__bd">
					<input class="weui-input" type="text" password="true" name="password" placeholder="请输入密码"></input>
				</view>
			</view>
			<view class="weui-cell">
				<view class="weui-cell__hd" style="width:105px"><label class="weui-label">验证码</label></view>
				<view class="weui-cell__bd">
					<input class="weui-input" type="text" name="captcha" placeholder="请输入验证码"></input>
				</view>
				<view class="weui-cell__ft">
					<image :src="pre_url + '/am.php?s=/login/captcha&aid=' + aid + '&session_id=' + session_id + '&t=' + randt" id="LAY-user-get-vercode" style="width:120px;height:37px" @tap="regetcaptcha"></image>
				</view>
			</view>
		</view>
		<view class="weui-btn-area">
			<button class="weui-btn weui-btn_primary" form-type="submit" style="background:#1aad19;color:#fff;font-size:16px;height:40px;line-height:40px;border-radius:4px">确定</button>
		</view>
	</form>
</view>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
      datalist: [],
      pagenum: 1,
      randt: '1',
      needAuth: "",
      aid: "",
      session_id: ""
    };
  },
  onLoad: function () {
    this.setData({
      needAuth: app.needAuth,
      aid: app.aid,
      session_id: app.config.session_id
    });
    var that = this;
  },
  methods: {
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
      app.post(app.pre_url + "/am.php?s=/login/index", formdata, function (res) {
        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('../index/index', 'redirect');
          }, 1000);
        } else {
          app.error(res.msg);
          that.regetcaptcha();
        }
      });
    },
    regetcaptcha: function () {
      this.setData({
        randt: this.randt + '1'
      });
    }
  }
};
</script>
<style>
/* am/pages/login/index.wxss */
</style>