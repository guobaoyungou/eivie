<template>
<view class="container">
	<block v-if="isload">
		<form @submit="formSubmit" @reset="formReset">
		<view class="form">
			<view class="form-item">
					<text class="label">生日</text>
					<picker class="picker" mode="date" value="" start="1900-01-01" :end="dateFormat('','Y-m-d')" @change="bindDateChange">
						<view v-if="birthday">{{birthday}}</view>
						<view v-else>请选择生日</view>
					</picker>
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
      birthday: '',
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
			var birthday = this.birthday
      if (birthday == '') {
        app.alert('请选择生日');return;
      }
      var mid = '';
        if(this.opt && this.opt.mid){
        mid = this.opt.mid;
      }
      app.showLoading('提交中');
      app.post("ApiMy/setfield", {mid:mid,birthday:birthday}, function (data) {
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
    bindDateChange: function (e) {
      this.birthday = e.detail.value;
    },
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
.form-item .picker{height: 60rpx;line-height:60rpx;margin-left: 20rpx;flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
</style>