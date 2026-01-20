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
			menuindex:-1
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		app.goto('/pagesB/shop/classify2','redirect');
  },
}
</script>