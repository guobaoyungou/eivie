<template>
<view class="dp-channelslive" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'
}">
		<!-- #ifdef MP-WEIXIN  -->
		<view class="dp-channelslive-view flex">
			<view class="dp-channelslive-live" :style="{width:params.live_height+'%'}">
				<channel-live :feed-id="liveInfo.feedId" :finder-user-name="params.channelsLive"></channel-live>
			</view>	
		</view>
		<!-- #endif -->
	</view>
</template>
<script>
	var app = getApp();
	export default {
		props: {
			params:{},
			data:{}
		},
		data(){
			return {
				Height:'',
				hastabbar:false,
				liveInfo:[]
			}
		},
		mounted:function(){
			var that = this;
			//#ifdef MP-WEIXIN
			wx.getChannelsLiveInfo({
				finderUserName: that.params.channelsLive,
				success(res) {
					if(res.errMsg == 'getChannelsLiveInfo:ok'){
						that.liveInfo = res;
					}
				},
				fail(err) {
					console.log(err);
				},
			})
			//#endif
		}
	}
</script>
<style>
.dp-channelslive{ position: relative; font-size: 0;}
.dp-channelslive-view{background-color: black;}
.dp-channelslive-live{ margin: 0 auto;min-height: 300rpx;}
</style>