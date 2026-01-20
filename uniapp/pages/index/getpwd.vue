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
		app.goto('/pagesB/index/getpwd','redirect');
  },
}
</script>