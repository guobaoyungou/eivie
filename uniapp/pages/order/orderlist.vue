<template>
<view class="container">
	<block v-if="isload"></block>
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
			platform:app.globalData.platform,
			pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.st){
			app.goto('/pagesExt/order/orderlist?st='+this.opt.st,'redirect');
		}else{
			app.goto('/pagesExt/order/orderlist','redirect');
		}
  },
}
</script>