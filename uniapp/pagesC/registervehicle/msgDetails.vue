<template>
	<view class="page-class">
		<block v-if="isload">
		<view class="title-view-class flex flex-y-center flex-bt">
			<view class="title-text">{{info.title}}</view>
			<view class="time-text">{{dateFormat(info.createtime)}}</view>
		</view>
		<view class="info-text">{{info.content}}</view>
		</block>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				isload: false,
				pre_url: app.globalData.pre_url,
				opt:{},
				info:{}
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		methods:{
			getdata:function(){
				var that = this;
				that.loading = true;
				app.get('ApiMemberNotice/detail', {id:that.opt.id}, function (res) {
					that.loading = false;
					var data = res.data || {};
					that.info = data;
					that.loaded();
				});
			},
		}
	}
</script>

<style>
	page{background: #fff;}
	.page-class{padding:30rpx;position: relative;}
	.title-view-class{width: 100%;}
	.title-view-class .title-text{font-size: 30rpx;font-weight: bold;color: #000;}
	.title-view-class .time-text{font-size: 24rpx;color: #a09fa6;}
	.info-text{font-size: 28rpx;color: #a09fa6;width: 100%;word-break: break-all;margin-top: 40rpx;}
</style>