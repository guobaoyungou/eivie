<template>
<view class="content">
		<form @submit="formSubmit">
			<view class="itembox">
				<view class="item"><label>公司名称</label><input type="text" placeholder="请输入公司名称" placeholder-style="line-height:80rpx" value="" name="company"></view>
				<view class="item"><label>我的签字</label>
					<view class="signimg" v-if="sign_image"><image :src="sign_image" mode="widthFix"/></view>
					<view class="signtext" v-else @tap="goto" data-url="qianshu">去签字</view>
				</view>
				<button class="button" form-type="submit" :style="{background:'#06A051'}">生成合同</button>
			</view>
		
		</form>
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
			
			set:{},
			psuser:{},
			resultUrl:'',
			hetong:[],
			sign_image:''
    };
  },

	onPullDownRefresh: function () {
		this.getdata();
	},
	onShow: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},
  methods: {
		
		getdata:function(e){
				var that = this;
				app.post('ApiMy/getHetong', { }, function (res) {
					 var data = res.set
					 var hetong = res.ht
					 that.set = data
					 that.hetong = hetong
					 that.sign_image = hetong?hetong.sign_image:this.sign_image
					 console.log(hetong.length);
				})
		},
		formSubmit:function(e){
			var that=this;
			//var image_url = that.resultUrl
		  var company = e.detail.value.company;
			if(company==''){
					app.error('请填写公司名称');return;
			}
			app.post('ApiMy/htconfirm2', { company:company }, function (res) {
					if(res.status==1){
						app.success(res.msg);
						setTimeout(function () {
						  app.goto('index');
						}, 1000);
					}else{
						app.error(res.msg)
					}
			})
		},
		
		
  }
};
</script>
<style>
	.itembox{ background: #fff; padding:30rpx;margin-top: 30rpx; }
	.itembox .item{ height: 100rpx;line-height: 100rpx;display: flex;}
	.itembox .item label{ margin-right: 10rpx;color: #999;}
	.itembox .item input{height: 100rpx;}
	.text{ display:flex;justify-content: flex-end;}
	.item .signimg{ width: 150rpx;}
	.item .signimg image{ width: 100%;}
	.itembox .button{ height: 80rpx; width:80%;background:#1890ff ; color: #fff;margin: 0 auto;border-radius: 50rpx;text-align: center;line-height: 80rpx;margin-top: 30rpx;}
	.signtext{ background: #f0f0f0;padding:0 20rpx;}
</style>