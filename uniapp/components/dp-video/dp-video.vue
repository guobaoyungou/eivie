<template>
<view class="dp-video" :style="{
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx',
	height: 'calc('+Height * (params.videoheight ? params.videoheight:30) / 100+'px - '+ `${hastabbar ? '110rpx - env(safe-area-inset-bottom)':'env(safe-area-inset-bottom)'}`+')',
	minHeight:(Height*0.3).toFixed(2)+'px'
}">
		<!-- 普通视频 -->
		<video v-if="!params.type || params.type==0" class="dp-video-video" :src="params.src" :show-mute-btn="(params.muted ? params.muted:0) == 1" :play-btn-position="(params.butposition ? params.butposition:'center')" :object-fit="(params.objectfit ? params.objectfit:'contain')" :poster="params.pic" :controls="(params.controls ? params.controls:1) == 1" :autoplay='(params.autoplay ? params.autoplay:0) == 1' :loop="(params.loop ? params.loop:0) == 1" :muted="params.muted == 1" :show-fullscreen-btn="(params.fullscreen ? params.fullscreen:1) == 1" ></video>
		<!-- #ifdef MP-WEIXIN  -->
		<!-- 内嵌视频号视频 -->
		<channel-video v-if="params.type==1" class="dp-video-video" :feed-token="params.video_feedtoken" :feed-id="params.video_feedid" :finder-user-name="params.video_finderuser" :object-fit="(params.objectfit ? params.objectfit:'contain')" :poster="params.pic" :autoplay='(params.autoplay ? params.autoplay:0) == 1' :loop="(params.loop ? params.loop:0) == 1" :muted="params.muted == 1"></channel-video>
		<!-- 跳转视频号首页 -->
		<view v-if="params.type==2" class="dp-video-poster" :style="'height:100%;background:#222222;background-image:url('+params.pic+');background-size:cover;'" @tap="goto" :data-url="params.src"><image class="dp-video-playicon" :src="pre_url+'/static/img/shortvideo_playnum.png'"></view>
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
				pre_url:getApp().globalData.pre_url,
				hastabbar:false
			}
		},
		mounted() {
			let that = this;
			uni.getSystemInfo({
				success(res) {
					that.Height = res.windowHeight;
				}
			});
			var pages = getCurrentPages(); //获取加载的页面
			var currentPage = pages[pages.length - 1]; //获取当前页面的对象
			var currenturl = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
			if (app.globalData.platform == 'baidu') {
				var opts = currentPage.options;
			} else {
				var opts = currentPage.$vm.opt;
			}
			if (opts && opts.id) {
				currenturl += '?id=' + opts.id
			} else if (opts && opts.cid) {
				currenturl += '?cid=' + opts.cid
			} else if (opts && opts.gid) {
				currenturl += '?gid=' + opts.gid
			} else if (opts && opts.bid) {
				currenturl += '?bid=' + opts.bid
			}
			var menudata = JSON.parse(JSON.stringify(app.globalData.initdata.menudata));
			var tablist = menudata['list'];
			for (var i = 0; i < tablist.length; i++) {
				if (tablist[i]['pagePath'] == currenturl) {
					this.hastabbar = true;
				}
			}
			if (this.hastabbar == false) {
				var menu2data = JSON.parse(JSON.stringify(app.globalData.initdata.menu2data))
				
				if (menu2data.length > 0) {
					for (var i in menu2data) {
						if (opts && opts.bid)
							menu2data[i].indexurl = (menu2data[i].indexurl).replace('[bid]', opts.bid);
						if (menu2data[i].indexurl == currenturl) {
							this.hastabbar = true;
						}
					}
				}
			}
		}
	}
</script>
<style>
.dp-video{ position: relative; font-size: 0;}
.dp-video-video{width: 100%; margin: 0px; padding: 0px;height: 100%;}
.dp-video-poster{display: flex;align-items: center;justify-content: center;}
.dp-video-playicon{width: 60rpx;height: 60rpx;}
</style>