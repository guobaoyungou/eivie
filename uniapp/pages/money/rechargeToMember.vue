<template>
	<view class="container"></view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {

    }
  },
  onLoad: function (opt){
		var tourl = (app._fullurl()).replace('/pages/','/pagesExt/');
		app.goto(tourl,'redirect');
  }
}
</script>