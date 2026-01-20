<template>
<view>
	<loading v-if="loading"></loading>
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
      url: ''
    };
  },
  onLoad: function (opt) {
    this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			if(app.globalData.platform =='wx'){
				wx.login({
					success(res1) {
						var code = res1.code;
						app.post('ApiIndex/cashdeskMemberBind',{id:that.opt.id,token:that.opt.token,code:code},function(res){
							that.loading = false;
							if(res.status == 1){
								app.alert(res.msg,function(){
									app.goto('/pages/index/index');
								});
							}else{
								app.alert(res.msg);
							}
						});
					}
				});
			}else if(app.globalData.platform =='mp'){
				var frompage = '/pages/index/index';
				location.href = app.globalData.pre_url + '/index.php?s=ApiIndex/cashdeskMemberBind&aid=' + app.globalData
					.aid + '&session_id=' + app.globalData.session_id + '&id=' + that.opt.id +
					'&frompage=' + encodeURIComponent(frompage);
			}
			
		}
	}
};
</script>
