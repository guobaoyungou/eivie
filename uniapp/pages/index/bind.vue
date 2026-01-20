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

      url: '',
      type:0
    };
  },
  onLoad: function (opt) {
    this.opt = app.getopts(opt);
    this.type= this.opt.type || 0;
		this.getdata();
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.post('ApiIndex/bind',{id:that.opt.id,token:that.opt.token,type:that.type},function(res){
				that.loading = false;
				if(res.status == 1){
					app.alert(res.msg,function(){
            if(!that.type){
              app.goto('/admin/index/index');
            }else{
              app.goto('/pages/index/index');
            }
					});
				}else{
					app.alert(res.msg);
				}
			});
		}
	}
};
</script>
