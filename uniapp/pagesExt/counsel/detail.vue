<template>
    <view v-if="content" style="width: 700rpx;margin: 0 auto;white-space: pre-wrap;word-wrap: break-word;">
        {{content}}
    </view>
</template>

<script>
var app = getApp();
export default {
	data() {
		return {
			opt:{},
			menuindex:-1,
			pre_url:app.globalData.pre_url,
            content:'',
            type_id:'',
            province_name:'',
		}
	},
	onLoad: function (opt) {
        var that = this;
		var opt = app.getopts(opt);
        that.opt = opt;
        if(opt.type_id){
            that.type_id = opt.type_id;
        }
        if(opt.province_name){
            that.province_name = opt.province_name;
        }
		that.getdata();
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiCounselFee/get_detail',{type_id:that.type_id,province_name:that.province_name},function (res){
                if(res.status == 1){
                    var data = res.data;
                    if(data){
                        that.content = data.content;
                        uni.setNavigationBarTitle({
                            title: data.title
                        });
                    }
                }else{
                    if(res.msg){
                        app.alert(res.msg);
                    }else{
                        app.alert('系统暂时无法访问');
                    }
                }
				
			});
		}
	}
};
</script>
<style>
</style>
