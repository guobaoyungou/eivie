<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="apply_box">
				<view class="apply_item">
					<view>视频号ID<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="channels" :value="info.channels" placeholder="请输入视频号ID"></input></view>
				</view>
			</view>
			<view style="padding:30rpx 0"><button form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">确 定</button></view>
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
			pre_url:app.globalData.pre_url,
      datalist: [],
      info: {},
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
			app.get('ApiAdminIndex/setinfo', {}, function (res) {
				that.loading = false;
				if (res.status == 2) {
					app.alert(res.msg, function () {
						app.goto('/admin/index/index', 'redirect');
					})
					return;
				}
				uni.setNavigationBarTitle({
					title: '视频号'
				});
				that.info = res.info;
				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var info = e.detail.value;
      if (info.channels == '') {
        app.error('请上传视频号ID');
        return false;
      }
			app.showLoading('提交中');
      app.post("ApiAdminIndex/setBusinessChannels", {info: info}, function (res) {
				app.showLoading(false);
        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('/admin/index/index', 'redirect');
          }, 1000);
        } else {
          app.error(res.msg);
        }
      });
    },
  }
}
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.apply_box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.apply_title { background: #fff}
.apply_title .qr_goback{ width:18rpx;height:32rpx; margin-left:24rpx;     margin-top: 34rpx;}
.apply_title .qr_title{ font-size: 36rpx; color: #242424;   font-weight:bold;margin: 0 auto; line-height: 100rpx;}

.apply_item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.apply_box .apply_item:last-child{ border:none}
.apply_item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.apply_item input::placeholder{ color:#999999}
.apply_item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.apply_item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.apply_item .upload_pic image{ width: 32rpx;height: 32rpx; }
.set-btn{width: 90%;margin:0 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>