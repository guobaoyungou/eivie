<template>
<view class="dp-shortvideo" :style="{
	color:params.color,
	backgroundColor:params.bgcolor,
	margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'
}">
	<view class="dp-shortvideo-content">
		<view v-for="(item, index) in data" :key="index" class="item" :style="{marginRight:index%2==0?'2%':'0'}">
			<image class="ff" mode="widthFix" :src="item.coverimg" @tap="goto"  :data-url="'/activity/shortvideo/detail?id=' + item.videoId +'&cid='+params.category"></image>
			<view class="f2">
				<view class="t1" v-if="params.showlogo==1"><image class="touxiang" :src="item.logo"/></view>
				<view class="t2" v-if="params.showviewnum==1"><image class="tubiao" :src="pre_url+'/static/img/shortvideo_playnum.png'"/>{{item.view_num}}</view>
				<view class="t3" v-if="params.showzannum==1"><image class="tubiao" :src="pre_url+'/static/img/shortvideo_likenum.png'"/>{{item.zan_num}}</view>
			</view>
		</view>
	</view>
</view>
</template>
<script>
	export default {
		data(){
			return {
				pre_url:getApp().globalData.pre_url
			}
		},
		props: {
			params:{},
			data:{}
		}
	}
</script>
<style>
.dp-shortvideo{height: auto;position: relative; }
.dp-shortvideo-content{position:relative;display:flex;flex-wrap:wrap}
.dp-shortvideo-content .item{width:49%;height:500rpx;background:#fff;overflow:hidden;border-radius:8rpx;margin-bottom:20rpx;position:relative}
.dp-shortvideo-content .item .ff{width:100%;height:100%;display:block;}
.dp-shortvideo-content .item .f2{position: absolute;bottom:20rpx;left:20rpx;display:flex;align-items:center;color:#fff;font-size:22rpx}
.dp-shortvideo-content .item .f2 .t1{display:flex;align-items:center;text-shadow: 0px 6px 12px rgba(0, 0, 0, 0.12);}
.dp-shortvideo-content .item .f2 .t2{display:flex;align-items:center;margin-left:30rpx;text-shadow: 0px 6px 12px rgba(0, 0, 0, 0.12);}
.dp-shortvideo-content .item .f2 .t3{display:flex;align-items:center;margin-left:30rpx;text-shadow: 0px 6px 12px rgba(0, 0, 0, 0.12);}
.dp-shortvideo-content .item .f2 .tubiao{display:block;height:28rpx;width:28rpx;margin-right:10rpx}
.dp-shortvideo-content .item .f2 .touxiang{display:block;width:40rpx;height:40rpx;border-radius:50%;}
</style>