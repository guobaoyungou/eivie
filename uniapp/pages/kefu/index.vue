<template>
<view class="container">
</view>
</template>

<script>
var app = getApp();

export default {
	data() {
	  return {
	
	  }
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var bid = this.opt.bid || 0;
		app.goto('/pagesB/kefu/index?bid='+bid,'redirect');
  },
}
</script>