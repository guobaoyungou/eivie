<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="apply_box">
				<view class="apply_item">
					<view>姓名<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="name" placeholder="请填写姓名"></input></view>
				</view>
				<view class="apply_item">
					<view>联系电话<text style="color:red"> *</text></view>
					<view class="flex-y-center"><input type="text" name="tel" placeholder="请填写手机号码"></input></view>
				</view>
			</view>
			<view style="padding:30rpx 0"><button  form-type="submit" class="set-btn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">提交</button>
</view>
		</form>
		
	</block>
	
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
			regiondata:'',
			pre_url:app.globalData.pre_url,
    };
  },

  onLoad: function (opt) {
		this.getdata();
		this.opt = app.getopts(opt);
		this.mdid = this.opt.mdid
		var that = this;
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loaded()
		},  
		subform: function (e) {
      var that = this;
      var info = e.detail.value;
      if (info.name == '') {
        app.error('请填写姓名');
        return false;
      }

      if (info.tel == '') {
        app.error('请填写电话');
        return false;
      }
			//console.log(info);return;
			app.showLoading('提交中');
      app.post("ApiMendianup/addhexiaouser", {info: info,mdid:this.mdid}, function (res) {
				app.showLoading(false);
  
				if(res.status == 1){
					setTimeout(function () {
						that.getdata()
						app.goto('pages/index/index','reLaunch');
						//app.goto(app.globalData.indexurl);
					}, 1000);
				}else{
					   app.error(res.msg);
				}
				
      });
    },
		
  }
}
</script>
<style>
.apply_box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.apply_item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee;align-items: center; }
.apply_item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right;}
.set-btn{width: 90%;margin:0 5%;height:96rpx;line-height:96rpx;border-radius:48rpx;color:#FFFFFF;font-weight:bold;}

</style>