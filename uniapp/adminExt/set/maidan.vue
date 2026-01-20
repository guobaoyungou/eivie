<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" >
		<view class="form">
			<view class="form-item">
				<textarea name="copyinfo" placeholder="请输入剪切板内容" :value="info.copyinfo"></textarea>
			</view>
			<view class="form-item" style="height: 80rpx;color: red;">
				注：若设置内容，买单页面将自动复制到剪贴板上
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
			bid:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid  : 0;
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this
			that.loading = true;
			app.get('ApiMaidan/set', {bid:that.bid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				that.info = res.data;
				that.loaded();
			});
		},
    formSubmit: function (e) {
      var formdata = e.detail.value;
      app.showLoading('提交中');
      app.post("ApiMaidan/set", {bid:this.bid,formdata:formdata}, function (data) {
        app.showLoading(false);
        if (data.status == 1) {
          app.success(data.msg);
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
.form{ width:94%;margin:40rpx 3%;border-radius:5px;padding:20rpx 20rpx;padding: 0 3%;background: #FFF;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:300rpx;line-height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
.form-item textarea {width: 100%;height: 280rpx;padding: 10rpx;line-height: 1.6;}
</style>