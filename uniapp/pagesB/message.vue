<template>
	<view class="container">
		<view class="center">
			{{msg}}
		</view>
	</view>
</template>

<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				msg:'',

				pre_url: app.globalData.pre_url
			};
		},

		onLoad: function(options) {
			this.opt = app.getopts(options);
			console.log(this.opt)
			if(this.opt.msg)
				this.msg = this.opt.msg;
			
		},
		methods: {
			
		}
	}
</script>
<style>
	.container {height: 100%;}
	.center {font-size: 36rpx; text-align: center;padding-top: 50%;}
</style>
