<template>
	<view class="container"></view>
</template>

<script>
var app = getApp();
export default {
  data() {
    return {
			opt:{}
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var url = '/pagesExt/my/levelup'
		if(this.opt.type){
			 url +='?type='+this.opt.type
		}
		app.goto(url,'redirect');
  }
}
</script>