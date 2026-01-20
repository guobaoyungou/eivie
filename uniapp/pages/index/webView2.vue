<template>
<view>
<web-view :src="url" v-if="url!=''"></web-view>
<view v-if="showBrowserOpen" style="width:100%;height:100vh;overflow:hidden">
	<image :src="pre_url + '/static/img/showBrowserOpen.jpg'" style="width:100%;" mode="widthFix"/>
</view>
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
			showBrowserOpen:false,
			pre_url:app.globalData.pre_url,
      combines:{'moneypay':0,'wxpay':0,'alipay':0}
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		if(this.opt.tourl) this.tourl = decodeURIComponent(this.opt.tourl);
    this.combines.moneypay = opt.moneypay || 0;
		this.getdata();
  },
  methods: {
		getdata: function(){
			this.orderid = this.opt.orderid;
			var that = this;
			var typeid = parseInt(this.opt.typeid);
			if((typeid == '23' || typeid == '3' || (typeid >=302 && typeid <=330)) && app.globalData.platform == 'mp'){
				that.showBrowserOpen = true;
				return;
			}
			app.globalData.session_id = this.opt.session_id;
			app.showLoading('提交中');
			app.post('ApiPay/pay',{op:'submit',orderid: this.orderid,typeid: typeid,combines:that.combines},function(res){
				app.showLoading(false);
				console.log(res)
				if(app.globalData.platform == 'h5'){
					if(typeid == '3' || (typeid >=302 && typeid <=330)){
						if (res.status == 0) {
							app.error(res.msg);return;
            }
            if (res.status == 2) {
							app.success(res.msg);return;
						}
						if(res.type=='adapay'){
							window.location.href = res.data;
						}else{
							document.body.innerHTML = res.data;
							document.forms['alipaysubmit'].submit();
						}
					}else{
						app.goto(res.url);
					}
				}else{
					that.url = res.url;
				}
			});
		}
  }
};
</script>
