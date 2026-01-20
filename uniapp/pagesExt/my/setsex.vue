<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="form">
			<view class="form-item">
					<text class="label">性别</text>
					<radio-group class="radio-group" name="sex">
					<label class="radio">
						<radio value="1"></radio>男
					</label>
					<label class="radio">
						<radio value="2"></radio>女
					</label>
					</radio-group>
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
      haspwd: 0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.loaded();
  },
	onPullDownRefresh: function () {
		//this.getdata();
	},
  methods: {
    formSubmit: function (e) {
      var formdata = e.detail.value;
			var sex = formdata.sex
      if (sex == '') {
        app.alert('请选择性别');return;
      }
      var mid = '';
      if(this.opt && this.opt.mid){
        mid = this.opt.mid;
      }
      app.showLoading('提交中');
      app.post("ApiMy/setfield", {mid:mid,sex:sex}, function (data) {
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
.form-item .radio{height: 60rpx;line-height: 60rpx;color: #666;margin-right:30rpx}
.form-item .radio radio{transform: scale(0.8);}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
</style>