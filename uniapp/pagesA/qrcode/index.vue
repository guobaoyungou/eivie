<template>
  <view ></view>
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

    }
  },
  onLoad: function (opt) {
    var that = this;
		var opt  = app.getopts(opt);
    that.opt = opt;

    var code  = opt.code || '';
    if(!code){
      app.goto('/pages/index/index');
    }else{
      that.getdata(code);
    }
  },
  methods: {
		getdata:function(code){
			var that = this;
			app.post('ApiQrcode/index', {code:code}, function (res) {
        if(res.status == 1){
          app.globalData.pid = res.pid;
          var formurl = res.formurl;
          app.goto(formurl,'reLaunch');
        }else if(res.status == 2){
          app.globalData.qrcode = res.qrcode;
          app.goto('/pages/index/login','reLaunch');
        }else{
          app.goto('/pages/index/index','reLaunch');
        }
			});
		},
  }
};
</script>
