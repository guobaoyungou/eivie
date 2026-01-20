<template>
<view class="addressadd">
	<block v-if="isload">
		<image :src="poster" style="width:100%" @tap="previewImage" :data-url="poster" mode="widthFix"></image>
	</block>
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

      poster: '',
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiAdminIndex/getbusinessqr', {}, function (res) {
				that.loading = false;
				that.poster = res.posterurl;
				that.loaded();
			});
		},
	}
};
</script>
<style>

</style>