<template>
<view class="container">
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
			pre_url:app.globalData.pre_url,
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
    	app.post('ApiBusiness/mybusiness', {}, function (res) {
    		that.loading = false;
    		if(res.status == 1 || res.status == 2){
          if(res.status == 1){
            var gotoUrl="/pagesExt/business/index?id="+ res.bid
          }else{
            var gotoUrl="/pagesExt/business/blist?ids="+ res.bids
          }
          app.goto(gotoUrl,'redirect');
        }else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url){
                app.goto(res.url);
              }else if(res.goback){
                app.goback();
              }
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
    	});
    },
  }
};
</script>