<template>
	<view>
		<block v-if="isload">
			<view class="container2">
				<image :src="set.banner" style="width:100%;height:auto" mode="widthFix" v-if="set && set.banner"></image>
				<view class="navbox">
					<block v-for="(item, index) in clist" :key="index" >
						<view class="nav_li cp" @tap="goto" :data-url="'apply?cid='+item.id">
							<image :src="item.pic"></image>
							<view>{{item.name}}补贴申请</view>
						</view>
					</block>
				</view>
			</view>
		</block>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<popmsg ref="popmsg"></popmsg>
		<wxxieyi></wxxieyi>
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
				set: '',
				clist:[]
			};
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onShow() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.post('ApiChinaumsSubsidy/index', {}, function(res) {
					that.loading = false;
					if(res.status == 1){
						let data = res.data;
						that.clist = data.list;
						that.set = data.set;
						if(data.set && data.set.title){
							uni.setNavigationBarTitle({
								title: data.set.title
							});
						}
					}
					that.loaded();
				});
			},
		}
	};
</script>
<style>
	.container2{width:100%;padding:20rpx;background:#fff;height: 100vh;box-sizing: border-box;}
	.navbox{background:#fff;height:auto;overflow:hidden}
	.nav_li{width:33%;text-align:center;box-sizing:border-box;padding:30rpx 0 10rpx;float:left;color:#222;font-size:24rpx}
	.nav_li image{width:100rpx;height:100rpx;margin-bottom:10rpx;border-radius: 20rpx;}
	.cp{cursor:pointer;}
</style>