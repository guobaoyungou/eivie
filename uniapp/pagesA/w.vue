<template>
	<view class="container">
		<view>
			<uni-popup ref="inputDialog" type="dialog">
				<uni-popup-dialog ref="inputClose" mode="input" title="绑定商户" value="" placeholder="请输入商户ID"
					@confirm="bindBusiness"></uni-popup-dialog>
			</uni-popup>
		</view>
		<loading v-if="loading"></loading>
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
				showbindbusiness: false,
				code:'',

				pre_url: app.globalData.pre_url
			};
		},

		onLoad: function(options) {
			this.opt = app.getopts(options);
			console.log('------------pagesA w load')
			console.log(options)
			if(this.opt.a)
				this.code = this.opt.a;
			else
				this.code = app.globalData.wcode;
			console.log('wcode',app.globalData.wcode)
		
			this.getdata();
			
		},
		methods: {
			getdata: function() {
				console.log('------------pagesA w getdata')
				var that = this;
				that.loading = true;
				
				app.get('ApiIndex/w', {
					code: that.code
				}, function(res) {
					if (res.data && res.data.bindstatus_business == 1 && !res.data.param_bid) {
							that.loading = false;
							that.$refs.inputDialog.open();
							return;
					} else {
						if (res.status == 1 && res.url) {
							if(res.pid > 0){
								app.globalData.pid = res.pid;
								uni.setStorageSync('pid', app.globalData.pid);
							}							
							app.goto(res.url);
						}
					}

					that.loaded();
				});
			},
			
			bindBusiness:function(done, bid){
				var that = this;
				app.showLoading('提交中');
				app.post('ApiIndex/wBindBusiness', {code: that.code,bid:bid}, function (res) {
					app.showLoading(false);
					if (res.status == 0) {
			      app.error(res.msg);
			    } else {
			      app.success(res.msg);
						that.$refs.inputDialog.close();
						location.reload();
					}
				})
			},
		}
	}
</script>
