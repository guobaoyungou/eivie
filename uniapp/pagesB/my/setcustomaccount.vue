<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="form">
			<view class="form-item">
					<text class="label">账户名称</text>
					<input type="text" class="input" placeholder="请输入账户名称" name="customaccountname" :value="account.customaccountname" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
			</view>
			<view class="form-item">
					<text class="label">账户编号</text>
					<input type="text" class="input" placeholder="请输入账户编号" name="customaccount" :value="account.customaccount" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
			</view>
			<view class="form-item">
					<text class="label">手机号</text>
					<input type="text" class="input" placeholder="请输入手机号" name="customtel" :value="account.customtel" placeholder-style="color:#BBBBBB;font-size:28rpx"></input>
			</view>
			<view class="withdraw_desc" v-if="sysset.withdraw_desc">
					<view class="title">说明</view>
					<text>{{sysset.withdraw_desc}}</text>
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
		
			sysset:{},
      account: {},
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.loaded();
		this.getdata();
  },
	onPullDownRefresh: function () {
		//this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiMy/setCustomAccess', {}, function (res) {
				that.loading = false;
				that.sysset = res.sysset;
				that.account = res.account;
				that.loaded();
				uni.setNavigationBarTitle({
					title: "设置"+ res.sysset.custom_name+"账户"
				});
			});
		},
    formSubmit: function (e) {
      var formdata = e.detail.value;
			var customtel = formdata.customtel
			var customaccount = formdata.customaccount
			var customaccountname = formdata.customaccountname
      
      if (customaccountname == '') {
        app.alert('请输入账户名称');return;
      }
			if (customaccount == '') {
			  app.alert('请输入账户编号');return;
			}
			if (customtel == '') {
			  app.alert('请输入手机号');return;
			}
			app.showLoading('提交中');
      app.post("ApiMy/setfield", {
				customaccount:customaccount,
				customaccountname:customaccountname,
				customtel:customtel,
			}, function (data) {
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
.container{overflow: hidden;}
.form{ width:94%;margin:20rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
.withdraw_desc{margin-top: 30rpx;}
.withdraw_desc .title{font-size: 30rpx;color: #5E5E5E;font-weight: bold;padding: 10rpx 0;}
.withdraw_desc text{width: 100%; line-height: 46rpx;font-size: 24rpx;color: #222222;}
</style>