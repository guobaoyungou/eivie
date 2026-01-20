<template>
<view>
	<block v-if="isload">
    <view style="padding: 20rpx">
      <parse :content="set.desc" ></parse>
    </view>
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
      nomore: false,
      nodata:false,
      menuindex:-1,
      pre_url:app.globalData.pre_url,
      
      set:{},
    }
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
			app.get('ApiBonusPoolGold/bonuspoolset', {}, function (res) {

				that.loading = false;
				if(res.status == 1){

					var set   = res.set;
					that.set = set;
          that.loaded();
				} else {
					if (res.msg) {
						app.alert(res.msg, function() {
							if (res.url) app.goto(res.url);
						});
					} else if (res.url) {
						app.goto(res.url);
					} else {
						app.alert('您无查看权限');
					}
				}
			});
		},
    
  }
};
</script>
<style></style>