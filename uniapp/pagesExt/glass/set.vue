<template>
<view v-if="isload" class="container">
	<view class="content">
		<rich-text :nodes="content"></rich-text>
	</view>
	<loading v-if="loading"></loading>
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
			field:'',
			content:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.field = this.opt.field || '';
		this.getdata();
  },
	onPullDownRefresh: function () {
	},
  onReachBottom: function () {
  },
  methods: {
    getdata: function (loadmore) {
      var that = this;
			that.loading = true;
      app.post('ApiGlass/getset', {field:that.field}, function (res) {
				that.loading = false;
				that.content = res.content
				that.loaded()
			});
    }
  }
};
</script>
<style>
	page{background: #FFFFFF;}
.content{padding: 30rpx;line-height: 50rpx;}
</style>