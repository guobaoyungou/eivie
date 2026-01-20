<template>
  <view class="all_view" :style="'background: '+t('color1')">
    <view class="img_view">
        <image @tap="previewImage" :data-url="imagsrc" :src="imagsrc"  style="width: 640rpx;height: 640rpx;"></image>
    </view>
  </view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
        opt:{},
        levelid:0,
        loading:false,
        isload: false,
        menuindex:-1,
        pre_url:app.globalData.pre_url,
        platform: app.globalData.platform,
        
        imagsrc:"",
        msg:''
    };
  },

  onLoad: function (opt) {
    var that = this;
		var opt = app.getopts(opt);
    that.opt = opt;
    that.getdata();
  },
	onPullDownRefresh: function () {

	},
  onPullDownRefresh: function () {

  },
  methods: {
		getdata: function () {
			var that = this;
			app.post('ApiTour/getTg', {platform:that.platform}, function (res) {
				app.showLoading(false);
				if (res.status == 1) {
					that.imagsrc = res.url;
				}else{
					app.alert(res.msg);
					return;
				}
			});
		},
  }
};
</script>
<style>
page{background:#f1f1f1;width: 100%;height: 100%;}
.all_view{width: 100%;height: 100%;overflow: hidden;}
.img_view{width: 680rpx;height: 680rpx;padding: 20rpx;margin: 0 auto;background-color: #fff;border-radius: 8rpx;margin-top: 30rpx;}
</style>