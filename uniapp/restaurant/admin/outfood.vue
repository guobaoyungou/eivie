<template>
<view>
	<view class="container" >
		 <view  v-if="btnshow" class="btn-add"    @tap="saoyisao">扫码出餐</view>
		<loading v-if="loading"></loading>
	</view>
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
        pre_url:app.globalData.pre_url,
        co:'',
        type:'',
        order:{},
		btnshow:true
    };
  },
  
    onLoad: function (opt) {
        this.opt   = app.getopts(opt);
        this.co    = this.opt.co? this.opt.co:'';
        this.type  = this.opt.type;
		if(this.co && this.type){
			 this.getdata();
		}
    },
	onPullDownRefresh: function () {
		this.getdata();
	},
    methods: {
        getdata: function () {
            var that = this;
			that.btnshow  = false;
            that.loading = true;
            var co       = that.co;
			var id  = that.opt.id;
            app.post('ApiAdminRestaurantShopOrder/outfood',{type:that.type,co:co,id:id}, function (res) {
				that.loading = false;
				if(res.status ==1){
					app.success(res.msg);
				}else{
					app.error(res.msg);
				}
				setTimeout(function() {
					that.type = '';
					that.co = '';
					that.btnshow  = true;
				}, 1000);
				return;
            });
        },
		saoyisao: function (d) {
		  var that = this;
			if(app.globalData.platform == 'h5'){
				app.alert('请使用微信扫一扫功能扫码核销');return;
			}else if(app.globalData.platform == 'mp'){
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							var params = content.split('?')[1];
							var url = '/admin/hexiao/hexiao?'+params;
							if(param['type'] =='outfood'){
								url = '/restaurant/admin/outfood?'+params;
							}
							app.goto(url);
							//if(content.length == 18 && (/^\d+$/.test(content))){ //是十八位数字 付款码
							//	location.href = "{:url('shoukuan')}/aid/{$aid}/auth_code/"+content
							//}else{
							//	location.href = content;
							//}
						},
						fail:function(err){
							app.error(err.errMsg);
						}
					});
				});
			}else{
				uni.scanCode({
					success: function (res) {
						console.log(res);
						var content = res.result;
						var params = content.split('?')[1];
						var url = '/admin/hexiao/hexiao?'+params;
						var param = app.getparams(url);
						if(param['type'] =='outfood'){
							url = '/restaurant/admin/outfood?'+params;
						}
						app.goto(url);
					},
					fail:function(err){
						app.error(err.errMsg);
					}
				});
			}
		},
	}	
};
</script>
<style>
.btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:20rpx;background-color: #4CAF50;}
</style>