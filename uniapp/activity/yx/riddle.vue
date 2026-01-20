<template>
<view class="container">
	<block v-if="isload">
		<view class="main box">
			<parse :content="detail.content" @navigate="navigate"></parse>
		</view>
		<form @submit="formSubmit" @reset="formReset">
		<view class="form box">
			<view class="form-item">
				<input type="text" class="input" :placeholder="'请输入'+detail.alias" placeholder-style="color:#BBBBBB;font-size:28rpx" name="kouling" value=""></input>
			</view>
		</view>
		<button class="set-btn" form-type="submit" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">确 定</button>
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
      haspwd: 0,
			detail:{}
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
		getdata: function (e) {
			var that = this;
			var id = that.opt.id || 0;
			var select_bid =app.getCache('select_bid');
			that.loading = true;
			app.post("ApiCoupon/riddle", {id:id,select_bid:select_bid}, function (res) {
				that.loading = false;
			  if (res.status == 1) {
			    that.detail = res.detail;
					uni.setNavigationBarTitle({
						title: res.detail.title
					});
			  } else {
			    app.error(res.msg);
			  }
				that.loaded();
			});
		},
    formSubmit: function (e) {
			var that = this;
      var formdata = e.detail.value;
			var kouling = formdata.kouling
      if (kouling == '') {
        app.alert('请输入'+that.detail.alias);return;
      }
			var id = that.opt.id || 0;
			var select_bid =app.getCache('select_bid');
			app.showLoading('提交中');
      app.post("ApiCoupon/riddle", {id:id,kouling:kouling,select_bid:select_bid,action:'submit'}, function (data) {
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
	.box{background: #FFFFFF;width:94%;margin:20rpx 3%;border-radius:5px;padding: 20rpx;}
.form{width:94%;margin:20rpx 3%;padding: 0 3%;}
.form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;line-height:98rpx;}
.form-item:last-child{border:0}
.form-item .label{color: #000;width:200rpx;}
.form-item .input{flex:1;color: #000;}
.set-btn{width: 90%;margin:60rpx 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}
</style>