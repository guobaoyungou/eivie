<template>
<view class="container">
	<block v-if="isload">
    <view style="position: fixed;top:40%;left: 0;width: 100%;color:#fff;text-align: center;line-height: 80rpx;font-size: 30rpx;">
      <view class="gobtn" @tap="goto" :data-url="'/pagesD/businessShareholder/index?type=1'" :style="'background:'+t('color1')">参与门店投资</view>
      <view class="gobtn" @tap="goto" :data-url="'/pagesD/businessShareholder/index?type=0'" :style="'margin-top: 60rpx;background:'+t('color1')">发起门店投资</view>
    </view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
		getdata:function(){
			var that = this;
      that.loading = true;
      app.post('ApiBusinessShareholder/index', {type: that.type,id: that.id}, function (res) {
        that.loading = false;
        if(res.status == 1){

          that.loaded();
        }else{
          app.alert(res.msg);
        }
      });
		},
  }
};
</script>
<style>
page{background-color: #fff;}
.gobtn{width: 400rpx;margin: 0 auto;border-radius: 12rpx 12rpx;}
</style>