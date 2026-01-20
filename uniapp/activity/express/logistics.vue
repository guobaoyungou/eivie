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
      isload: false,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		app.goto('/pagesB/express/logistics','redirect');
  },
}
</script>