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

      userinfo: [],
      money: '',
      moneyduan: 0,
			dhcode:'',
			lipinset:{},
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt && this.opt.dhcode) this.dhcode = this.opt.dhcode;
		app.goto('/pagesExt/lipin/index?dhcode='+this.dhcode,'redirect');
  },
}
</script>