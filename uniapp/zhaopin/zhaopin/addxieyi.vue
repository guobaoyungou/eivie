<template>
	<view class="content" v-if="isload">
		<view class="xieyi">
			<rich-text :nodes="zhaopinset.xieyi"></rich-text>
		</view>
		<view class="bottom">
			<view class="button" :style="{background:lefttime>0?'#ccc':'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">
				<text v-if="lefttime>0" class="counter" >（{{lefttime}}）</text>
				<text v-if="lefttime>0">确定</text>
				<text v-else @tap="next">确定</text>
			</view>
		</view>
		<loading v-if="loading"></loading>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				pre_url: app.globalData.pre_url,
				zhaopinset:{},
				lefttime:15
			}
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			if(!this.opt.id){
				app.alert('参数错误');
				app.goback()
			}else{
				this.getdata();
			}
		},
		onPullDownRefresh: function(e) {
			
		},
		methods: {
			getdata:function(e){
				var that = this
				app.get('ApiZhaopin/zhaopinSet', {}, function (res) {
						that.loading = false;
						that.zhaopinset = res.zhaopinset.zhaopin;
						that.loaded();
						setInterval(function () {
							if(that.lefttime>0){
								that.lefttime = that.lefttime - 1;
							}else{
								return;
							}
						}, 1000);
				});
			},
			next:function(e){
				app.goto('addstep2?id='+this.opt.id)
			}
	}
}
</script>
<style>
	@import "../common.css";
	page{background: #FFFFFF;}
	.content{color:#222222;padding: 30rpx;}
	.xieyi{line-height: 50rpx;overflow-y: scroll;min-height: 1000rpx;margin-bottom: 60rpx;}
	.bottom{position: fixed;bottom: 0;left: 0;width: 100%;background: #FFFFFF;}
	.bottom .button{text-align: center;width: 90%;height: 90rpx;line-height: 90rpx;font-size: 36rpx;font-weight: bold;margin: 10rpx 5%;border-radius: 60rpx;}
	.disabled{background: #ccc;}
	.counter{}
	</style>
