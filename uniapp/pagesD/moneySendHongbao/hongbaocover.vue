<template>
<view class="">
	<block v-if="isload">
	<view class="page-content flex flex-bt">
		<block v-for="(item,index) in sysset.pics" :key="index">
			<view class="cover-options">
				<image :src="item" mode="scaleToFill" />
				<view class="choose-view" :data-url="item" @click="change">使用</view>
			</view>
		</block>
	</view>
	</block>
	<loading v-if="loading"></loading>
</view>
</template>

<script>
	var app = getApp();
	export default {
		data(){
			return{
				isload: false,
				pre_url: app.globalData.pre_url,
				loading:false,
				sysset:[]
			}
		},
		onLoad: function (opt) {
		  this.getdata();
		},
		methods:{
			getdata: function () {
				var that = this;
				that.loading = true;
				app.get('ApiMoneySendHongbao/index', {}, function (res) {
					uni.setNavigationBarTitle({
						title: '选择红包封面'
					});
					that.loading = false;
					that.sysset = res.sysset;
					
					that.loaded();
				});
			},
			change(e){
				var url =  e.currentTarget.dataset.url;
				uni.$emit('pic',{picurl:url})
				app.goback();
			}
		}
	}
</script>

<style>
	.page-content{width: 100%;padding: 30rpx;flex-wrap: wrap;}
	.cover-options{width: 337rpx;margin-bottom: 20rpx;height: 500rpx;border-radius: 16rpx;overflow: hidden;position: relative;}
	.cover-options image{width: 100%;height: 100%;}
	.choose-view{position: absolute;left: 50%;transform: translateX(-50%);bottom: 8%;background: #fef7ec;color: #772b26;font-size: 26rpx;font-weight: bold;padding: 10rpx 45rpx;border-radius: 40rpx;}
</style>