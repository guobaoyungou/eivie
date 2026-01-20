<template>
<view>
<web-view :src="url"></web-view>
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
      type:''
    };
  },

  onLoad: function (opt) {
    var that = this;
    that.opt = app.getopts(opt);
    that.type = that.opt.type || '';

    if(that.type == 'elecalbum' && that.opt.eaid){
      that.url = app.globalData.pre_url+'/elecalbum/mobile/index.html?aid='+app.globalData.aid+'&eaid='+that.opt.eaid;
      console.log(that.url)
    }else{
      that.url = decodeURIComponent(opt.url);
    }

  },
	onShareAppMessage:function(){
		let onSharelink = '/pages/index/webView?url=' + encodeURIComponent(this.url);
		return this._sharewx({title:'',pic:'',link:onSharelink});
	},
};
</script>
