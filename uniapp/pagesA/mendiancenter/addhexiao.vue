<template>
<view>
	<block v-if="isload">
		<view class="content">
			<view class="label">
				<text class="t1">邀请员工扫码，完成添加</text>
			</view>
			<view class="qrcode">
				<image :src="qrcode"></image>
			</view>
		</view>
	</block>
	<popmsg ref="popmsg"></popmsg>
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
			pre_url:app.globalData.pre_url,
			qrcode:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata(){
		  var that = this;
			app.showLoading();
			app.post('ApiMendianCenter/gethxqrcode', { }, function (data) {
				app.showLoading(false);
				if(data.status == 0){
					app.alert(data.msg);
				}else{
					that.qrcode = data.qrcode
					that.loaded()
				}
			});
		},
  }
};
</script>
<style>

.content{width: 94%;margin:0 3%;background: #fff;border-radius:16rpx;text-align: center;}
.content .label{display:flex;width: 100%;padding:24rpx 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .qrcode{ }
.content .qrcode image{ width: 300rpx;height: 300rpx;}
</style>